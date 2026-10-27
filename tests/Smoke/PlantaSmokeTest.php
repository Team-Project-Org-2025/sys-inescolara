<?php

namespace SysInescolara\Tests\Smoke;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Humo del módulo Plantas: ejecuta el controlador real en un subproceso
 * (jsonResponse/handleError llaman a exit()) y verifica rutas, auth,
 * permisos y validaciones sin depender de MySQL, más los assets del módulo.
 */
final class PlantaSmokeTest extends TestCase
{
    private const HARNESS = __DIR__ . '/../Support/controller_harness.php';

    /**
     * @return array{stdout: string, stderr: string, status: string|null, exit: int}
     */
    private function runScenario(string $name): array
    {
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([PHP_BINARY, self::HARNESS, $name], $descriptors, $pipes);
        $this->assertIsResource($process, "No se pudo lanzar el escenario '$name'");

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        preg_match('/HARNESS_STATUS:(\S+)/', $stderr, $matches);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'status' => $matches[1] ?? null,
            'exit'   => $exit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runJsonScenario(string $name): array
    {
        $result = $this->runScenario($name);
        $this->assertSame(0, $result['exit'], "Escenario '$name' terminó con errores:\n{$result['stderr']}");
        $this->assertStringNotContainsString('Fatal error', $result['stderr']);
        $this->assertStringNotContainsString('Uncaught', $result['stderr']);

        $json = json_decode($result['stdout'], true);
        $this->assertIsArray($json, "Escenario '$name' no respondió JSON: {$result['stdout']}");

        $result['json'] = $json;
        return $result;
    }

    #[Test]
    public function modulo_carga_sus_funciones_sin_errores_fatales(): void
    {
        $result = $this->runJsonScenario('load');

        $this->assertTrue($result['json']['success']);
        $this->assertSame(
            [
                'index',
                'get_plants',
                'add_ajax',
                'edit_ajax',
                'delete_ajax',
                'plants_handleAddEdit',
                'plants_handleDelete',
                'plants_getPlantsAjax',
            ],
            array_keys($result['json']['defined'])
        );
        $this->assertContainsOnlyBool($result['json']['defined']);
        $this->assertNotContains(false, $result['json']['defined']);
    }

    #[Test]
    public function sin_sesion_en_ajax_responde_401_con_redirect_a_login(): void
    {
        $result = $this->runJsonScenario('no_auth_ajax');

        $this->assertSame('401', $result['status']);
        $this->assertFalse($result['json']['success']);
        $this->assertSame('No autorizado', $result['json']['message']);
        $this->assertStringEndsWith('login', $result['json']['redirect']);
    }

    #[Test]
    public function sin_sesion_en_web_redirige_a_login(): void
    {
        $result = $this->runScenario('no_auth_web');

        $this->assertSame(0, $result['exit']);
        $this->assertSame('302', $result['status']);
        $this->assertSame('', $result['stdout']);
    }

    #[Test]
    public function accion_ajax_inexistente_responde_400(): void
    {
        $result = $this->runJsonScenario('bad_action');

        $this->assertSame('400', $result['status']);
        $this->assertFalse($result['json']['success']);
        $this->assertSame('Acción AJAX inválida', $result['json']['message']);
    }

    #[Test]
    public function metodo_http_no_permitido_para_la_accion_responde_400(): void
    {
        $result = $this->runJsonScenario('bad_method');

        $this->assertSame('400', $result['status']);
        $this->assertSame('Acción AJAX inválida', $result['json']['message']);
    }

    #[Test]
    public function usuario_sin_permiso_responde_403(): void
    {
        $result = $this->runJsonScenario('no_permission');

        $this->assertSame('403', $result['status']);
        $this->assertFalse($result['json']['success']);
        $this->assertSame('No tienes permiso para realizar esta acción.', $result['json']['message']);
    }

    #[Test]
    public function agregar_sin_nombre_comun_responde_500_con_mensaje_de_validacion(): void
    {
        $result = $this->runJsonScenario('add_missing_name');

        $this->assertSame('500', $result['status']);
        $this->assertFalse($result['json']['success']);
        $this->assertSame('El nombre común es requerido.', $result['json']['message']);
    }

    #[Test]
    public function editar_sin_nombre_comun_responde_500_con_mensaje_de_validacion(): void
    {
        $result = $this->runJsonScenario('edit_missing_name');

        $this->assertSame('500', $result['status']);
        $this->assertSame('El nombre común es requerido.', $result['json']['message']);
    }

    #[Test]
    public function editar_con_id_invalido_responde_500(): void
    {
        $result = $this->runJsonScenario('edit_bad_id');

        $this->assertSame('500', $result['status']);
        $this->assertSame('ID inválido', $result['json']['message']);
    }

    #[Test]
    public function eliminar_con_id_invalido_responde_500(): void
    {
        $result = $this->runJsonScenario('delete_bad_id');

        $this->assertSame('500', $result['status']);
        $this->assertSame('ID inválido', $result['json']['message']);
    }

    #[Test]
    public function get_plants_responde_la_lista_de_plantas(): void
    {
        if (!$this->databaseAvailable()) {
            $this->markTestSkipped('MySQL no disponible: se omite el humo del listado get_plants');
        }

        $result = $this->runJsonScenario('get_plants_ok');

        $this->assertSame('200', $result['status']);
        $this->assertTrue($result['json']['success']);
        $this->assertIsArray($result['json']['plantas']);
        $this->assertSame(count($result['json']['plantas']), $result['json']['count']);
    }

    #[Test]
    public function vista_del_modulo_esta_presente_y_publica_sus_componentes(): void
    {
        $vista = dirname(__DIR__, 2) . '/app/views/dashboard/plantas.php';
        $this->assertFileExists($vista);

        $html = (string) file_get_contents($vista);
        $this->assertStringContainsString('id="plantasTable"', $html);
        $this->assertStringContainsString('id="btnAddPlant"', $html);
        $this->assertStringContainsString('name="nombre_comun"', $html);
        $this->assertStringContainsString('id="editPlantName"', $html);
    }

    #[Test]
    public function javascript_del_modulo_declara_sus_endpoints_ajax(): void
    {
        $js = dirname(__DIR__, 2) . '/public/assets/js/dashboard/plantas.js';
        $this->assertFileExists($js);

        $codigo = (string) file_get_contents($js);
        foreach (['get_plants', 'add_ajax', 'edit_ajax', 'delete_ajax'] as $accion) {
            $this->assertStringContainsString(
                "action={$accion}",
                $codigo,
                "plantas.js no referencia la acción '{$accion}'"
            );
        }
    }

    private function databaseAvailable(): bool
    {
        static $available = null;
        if ($available !== null) {
            return $available;
        }

        $loader = dirname(__DIR__, 2) . '/app/helpers/env_loader.php';
        if (is_file($loader)) {
            require_once $loader;
            loadEnvFile();
        }

        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'sysinescolara';
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port, $name),
                $user,
                $password,
                [PDO::ATTR_TIMEOUT => 2]
            );
            $pdo->query('SELECT 1 FROM plantas LIMIT 1');
            $available = true;
        } catch (\Throwable $e) {
            $available = false;
        }

        return $available;
    }
}
