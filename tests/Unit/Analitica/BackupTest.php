<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Analitica;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\models\Backup;

/**
 * Backup no extiende Database: trabaja con el sistema de archivos y con las
 * variables de entorno de conexión. Las pruebas usan el directorio real
 * _backups/ pero solo crean y borran archivos con un prefijo único para no
 * tocar los respaldos existentes.
 */
final class BackupTest extends TestCase
{
    private Backup $backup;

    private string $prefijo;

    /** @var list<string> */
    private array $creados = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backup = new Backup();
        $this->prefijo = 'phpunit_bk_' . bin2hex(random_bytes(4)) . '_';
    }

    protected function tearDown(): void
    {
        foreach ($this->creados as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }

        $residuales = glob($this->backup->getBackupDir() . $this->prefijo . '*');
        foreach ($residuales === false ? [] : $residuales as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }

        $this->creados = [];

        parent::tearDown();
    }

    #[Test]
    public function getBackupDir_apunta_al_directorio_real_de_respaldos(): void
    {
        $directorio = $this->backup->getBackupDir();

        $this->assertSame(ROOT_PATH . '_backups' . DIRECTORY_SEPARATOR, $directorio);
        $this->assertDirectoryExists($directorio);
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR, $directorio);
    }

    #[Test]
    public function getDbNames_devuelve_las_bases_principal_y_de_seguridad(): void
    {
        $nombres = $this->backup->getDbNames();

        $this->assertSame(['main', 'security'], array_keys($nombres));
        $this->assertSame(getenv('DB_NAME') ?: 'sysinescolara', $nombres['main']);
        $this->assertSame(
            getenv('DB_SEC_NAME') !== false ? getenv('DB_SEC_NAME') : 'SysInescolara-Seguridad',
            $nombres['security']
        );
    }

    #[Test]
    public function getDbConfigs_devuelve_ambas_conexiones_etiquetadas(): void
    {
        $configs = $this->backup->getDbConfigs();

        $this->assertSame(['main', 'security'], array_keys($configs));
        $this->assertSame(
            ['host', 'port', 'user', 'pass', 'name', 'label'],
            array_keys($configs['main'])
        );
        $this->assertSame('Datos', $configs['main']['label']);
        $this->assertSame('Seguridad', $configs['security']['label']);
        $this->assertSame(getenv('DB_HOST') ?: 'localhost', $configs['main']['host']);
        $this->assertSame(getenv('DB_PORT') ?: '3306', $configs['main']['port']);
        $this->assertSame(getenv('DB_USER') ?: 'root', $configs['main']['user']);
    }

    #[Test]
    public function list_devuelve_solo_archivos_sql_con_marca_de_tiempo_y_los_ordena_del_mas_reciente(): void
    {
        $antiguo = $this->crearArchivo('datos_20240101_120000.sql', 'hola');
        $reciente = $this->crearArchivo('datos_20240202_130000.sql', str_repeat('x', 2048));
        $this->crearArchivo('sin_marca_de_tiempo.sql', 'ignorado');

        $listado = $this->soloPropios($this->backup->list());

        $this->assertCount(2, $listado);
        $this->assertSame(basename($reciente), $listado[0]['filename']);
        $this->assertSame(basename($antiguo), $listado[1]['filename']);
        $this->assertSame($this->prefijo . 'datos', $listado[0]['db_label']);
        $this->assertSame('20240202_130000', $listado[0]['timestamp']);
        $this->assertSame('02/02/2024 01:00:00 PM', $listado[0]['date']);
        $this->assertSame(2048, $listado[0]['size']);
        $this->assertSame('2.00 KB', $listado[0]['size_formatted']);
        $this->assertSame('4 B', $listado[1]['size_formatted']);
    }

    #[Test]
    public function list_incluye_las_claves_esperadas_de_cada_respaldo(): void
    {
        $this->crearArchivo('datos_20240101_120000.sql', 'contenido');

        $listado = $this->soloPropios($this->backup->list());

        $this->assertCount(1, $listado);
        $this->assertSame(
            ['filename', 'filepath', 'db_label', 'timestamp', 'date', 'size', 'size_formatted'],
            array_keys($listado[0])
        );
    }

    #[Test]
    public function delete_elimina_el_archivo_y_devuelve_false_si_no_existe(): void
    {
        $archivo = $this->crearArchivo('datos_20240101_120000.sql', 'borrame');
        $nombre = basename($archivo);

        $this->assertTrue($this->backup->delete($nombre));
        $this->assertFileDoesNotExist($archivo);
        $this->assertFalse($this->backup->delete($nombre));
    }

    #[Test]
    public function getFilePath_devuelve_la_ruta_solo_si_el_archivo_existe(): void
    {
        $archivo = $this->crearArchivo('datos_20240101_120000.sql', 'x');

        $this->assertSame($archivo, $this->backup->getFilePath(basename($archivo)));
        $this->assertNull($this->backup->getFilePath($this->prefijo . 'inexistente_20240101_120000.sql'));
    }

    #[Test]
    public function restore_de_un_archivo_inexistente_devuelve_mensaje_de_error(): void
    {
        $resultado = $this->backup->restore($this->prefijo . 'no_existe_20240101_120000.sql');

        $this->assertSame(
            ['success' => false, 'message' => 'El archivo de respaldo no existe.'],
            $resultado
        );
    }

    #[Test]
    public function restore_sin_base_detectable_devuelve_mensaje_de_error(): void
    {
        $archivo = $this->crearArchivo('datos_20240101_120000.sql', "SELECT 1;\n");

        $resultado = $this->backup->restore(basename($archivo));

        $this->assertSame(
            ['success' => false, 'message' => 'No se pudo detectar la base de datos en el archivo.'],
            $resultado
        );
    }

    #[Test]
    public function restore_de_una_base_desconocida_no_ejecuta_mysql(): void
    {
        $archivo = $this->crearArchivo(
            'datos_20240101_120000.sql',
            "-- Current Database: `base_desconocida_phpunit`\nSELECT 1;\n"
        );

        $resultado = $this->backup->restore(basename($archivo));

        $this->assertSame(
            [
                'success' => false,
                'message' => "No se encontró configuración para la base de datos 'base_desconocida_phpunit'.",
            ],
            $resultado
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function soloPropios(array $listado): array
    {
        return array_values(array_filter(
            $listado,
            fn (array $item): bool => str_starts_with($item['filename'], $this->prefijo)
        ));
    }

    private function crearArchivo(string $sufijo, string $contenido): string
    {
        $ruta = $this->backup->getBackupDir() . $this->prefijo . $sufijo;

        $this->assertNotFalse(file_put_contents($ruta, $contenido));
        $this->creados[] = $ruta;

        return $ruta;
    }
}
