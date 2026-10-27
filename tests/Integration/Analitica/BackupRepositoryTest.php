<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Analitica;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Backup;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * Prueba Backup contra el directorio real _backups/.
 *
 * Cada archivo creado lleva un prefijo único y se elimina en tearDown; los
 * respaldos existentes nunca se leen, modifican ni borran. create() y
 * restore() no se ejecutan contra MySQL: solo se cubren los caminos de error
 * que se resuelven antes de invocar mysqldump/mysql.
 */
final class BackupRepositoryTest extends SqliteTestCase
{
    private Backup $backup;

    private string $prefijo;

    /** @var list<string> */
    private array $creados = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backup = new Backup();
        $this->prefijo = 'phpunit_int_bk_' . bin2hex(random_bytes(4)) . '_';
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
    public function getBackupDir_es_el_directorio_real_y_existe(): void
    {
        $this->assertSame(ROOT_PATH . '_backups' . DIRECTORY_SEPARATOR, $this->backup->getBackupDir());
        $this->assertDirectoryExists($this->backup->getBackupDir());
    }

    #[Test]
    public function getDbNames_devuelve_la_base_principal_y_la_de_seguridad(): void
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
    public function getDbConfigs_devuelve_dos_conexiones_completas_y_etiquetadas(): void
    {
        $configs = $this->backup->getDbConfigs();

        $this->assertSame(['main', 'security'], array_keys($configs));
        $this->assertSame(['host', 'port', 'user', 'pass', 'name', 'label'], array_keys($configs['main']));
        $this->assertSame(['host', 'port', 'user', 'pass', 'name', 'label'], array_keys($configs['security']));
        $this->assertSame('Datos', $configs['main']['label']);
        $this->assertSame('Seguridad', $configs['security']['label']);
        $this->assertSame(getenv('DB_HOST') ?: 'localhost', $configs['main']['host']);
        $this->assertSame(getenv('DB_PORT') ?: '3306', $configs['main']['port']);
        $this->assertSame(getenv('DB_NAME') ?: 'sysinescolara', $configs['main']['name']);
    }

    #[Test]
    public function list_devuelve_los_respaldos_propios_ordenados_del_mas_reciente(): void
    {
        $antiguo = $this->crearArchivo('datos_20240101_120000.sql', 'hola');
        $medio = $this->crearArchivo('datos_20240201_120000.sql', str_repeat('x', 1024));
        $reciente = $this->crearArchivo('seguridad_20240301_120000.sql', str_repeat('y', 2048));
        $this->crearArchivo('sin_marca.sql', 'ignorado');
        $this->crearArchivo('nota.txt', 'ignorado');

        $listado = $this->soloPropios($this->backup->list());

        $this->assertCount(3, $listado);
        $this->assertSame(
            [basename($reciente), basename($medio), basename($antiguo)],
            array_column($listado, 'filename')
        );
        $this->assertSame($this->prefijo . 'seguridad', $listado[0]['db_label']);
        $this->assertSame('20240301_120000', $listado[0]['timestamp']);
        $this->assertSame('01/03/2024 12:00:00 PM', $listado[0]['date']);
        $this->assertSame(2048, $listado[0]['size']);
        $this->assertSame('2.00 KB', $listado[0]['size_formatted']);
        $this->assertSame('1.00 KB', $listado[1]['size_formatted']);
        $this->assertSame('4 B', $listado[2]['size_formatted']);
        $this->assertSame(
            ['filename', 'filepath', 'db_label', 'timestamp', 'date', 'size', 'size_formatted'],
            array_keys($listado[0])
        );
    }

    #[Test]
    public function list_no_falla_si_el_directorio_no_tiene_respaldos_propios(): void
    {
        $this->assertSame([], $this->soloPropios($this->backup->list()));
    }

    #[Test]
    public function getFilePath_devuelve_la_ruta_del_archivo_existente(): void
    {
        $archivo = $this->crearArchivo('datos_20240101_120000.sql', 'x');

        $this->assertSame($archivo, $this->backup->getFilePath(basename($archivo)));
        $this->assertNull($this->backup->getFilePath($this->prefijo . 'no_existe_20240101_120000.sql'));
    }

    #[Test]
    public function delete_borra_solo_el_archivo_indicado(): void
    {
        $uno = $this->crearArchivo('datos_20240101_120000.sql', 'uno');
        $dos = $this->crearArchivo('datos_20240201_120000.sql', 'dos');

        $this->assertTrue($this->backup->delete(basename($uno)));
        $this->assertFileDoesNotExist($uno);
        $this->assertFileExists($dos);

        $this->assertFalse($this->backup->delete(basename($uno)));
    }

    #[Test]
    public function restore_de_un_archivo_inexistente_no_ejecuta_mysql(): void
    {
        $this->assertSame(
            ['success' => false, 'message' => 'El archivo de respaldo no existe.'],
            $this->backup->restore($this->prefijo . 'no_existe_20240101_120000.sql')
        );
    }

    #[Test]
    public function restore_sin_base_detectable_devuelve_mensaje_de_error(): void
    {
        $archivo = $this->crearArchivo('datos_20240101_120000.sql', "SELECT 1;\n");

        $this->assertSame(
            ['success' => false, 'message' => 'No se pudo detectar la base de datos en el archivo.'],
            $this->backup->restore(basename($archivo))
        );
    }

    #[Test]
    public function restore_de_una_base_desconocida_devuelve_mensaje_de_error(): void
    {
        $archivo = $this->crearArchivo(
            'datos_20240101_120000.sql',
            "-- Current Database: `base_desconocida_phpunit`\nSELECT 1;\n"
        );

        $this->assertSame(
            [
                'success' => false,
                'message' => "No se encontró configuración para la base de datos 'base_desconocida_phpunit'.",
            ],
            $this->backup->restore(basename($archivo))
        );
    }

    /**
     * @param array<int, array<string, mixed>> $listado
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
