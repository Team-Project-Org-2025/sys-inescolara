<?php

namespace SysInescolara\Tests\Smoke;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\Tests\Support\ScenarioRunner;

/**
 * Humo del módulo Ubicaciones: ejecuta el controlador real en un subproceso
 * (jsonResponse/handleError llaman a exit()) y verifica rutas, auth,
 * permisos y validaciones, más los assets del módulo.
 */
final class UbicacionSmokeTest extends TestCase
{
    use ScenarioRunner;

    private const HARNESS = __DIR__ . '/../Support/ubicaciones_harness.php';

    #[Test]
    public function modulo_carga_sus_funciones_sin_errores_fatales(): void
    {
        $result = $this->runJsonScenario('load');

        $this->assertTrue($result['json']['success']);
        $this->assertSame(
            [
                'index',
                'get_locations',
                'add_ajax',
                'edit_ajax',
                'delete_ajax',
                'locations_getLocationsAjax',
                'locations_handleAddEdit',
                'locations_handleDelete',
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
    public function agregar_sin_nombre_responde_500_con_mensaje_de_validacion(): void
    {
        $result = $this->runJsonScenario('add_missing_name');

        $this->assertSame('500', $result['status']);
        $this->assertFalse($result['json']['success']);
        $this->assertSame('El nombre de la ubicación es requerido.', $result['json']['message']);
    }

    #[Test]
    public function editar_sin_nombre_responde_500_con_mensaje_de_validacion(): void
    {
        $result = $this->runJsonScenario('edit_missing_name');

        $this->assertSame('500', $result['status']);
        $this->assertSame('El nombre de la ubicación es requerido.', $result['json']['message']);
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
        $this->assertSame('ID de ubicación inválido', $result['json']['message']);
    }

    #[Test]
    public function editar_ubicacion_inexistente_responde_500(): void
    {
        if (!$this->databaseAvailable()) {
            $this->markTestSkipped('MySQL no disponible: se omite la humo de edición inexistente');
        }

        $result = $this->runJsonScenario('edit_not_found');

        $this->assertSame('500', $result['status']);
        $this->assertSame('La ubicación que intenta editar no existe.', $result['json']['message']);
    }

    #[Test]
    public function eliminar_ubicacion_inexistente_responde_500(): void
    {
        if (!$this->databaseAvailable()) {
            $this->markTestSkipped('MySQL no disponible: se omite la humo de eliminación inexistente');
        }

        $result = $this->runJsonScenario('delete_not_found');

        $this->assertSame('500', $result['status']);
        $this->assertSame('No existe la ubicación solicitada.', $result['json']['message']);
    }

    #[Test]
    public function get_locations_responde_el_listado_de_ubicaciones(): void
    {
        if (!$this->databaseAvailable()) {
            $this->markTestSkipped('MySQL no disponible: se omite el humo del listado get_locations');
        }

        $result = $this->runJsonScenario('get_locations_ok');

        $this->assertSame('200', $result['status']);
        $this->assertTrue($result['json']['success']);
        $this->assertIsArray($result['json']['ubicaciones']);
        $this->assertSame(count($result['json']['ubicaciones']), $result['json']['count']);
        if ($result['json']['count'] > 0) {
            $this->assertArrayHasKey('nombre_ubicacion', $result['json']['ubicaciones'][0]);
        }
    }

    #[Test]
    public function vista_del_modulo_esta_presente_y_publica_sus_componentes(): void
    {
        $vista = dirname(__DIR__, 2) . '/app/views/dashboard/ubicaciones.php';
        $this->assertFileExists($vista);

        $html = (string) file_get_contents($vista);
        $this->assertStringContainsString('id="ubicacionesTable"', $html);
        $this->assertStringContainsString('id="btnAddLocation"', $html);
        $this->assertStringContainsString('name="nombre_ubicacion"', $html);
        $this->assertStringContainsString('name="descripcion"', $html);
        $this->assertStringContainsString('name="tipo"', $html);
        $this->assertStringContainsString('id="editLocationName"', $html);
    }

    #[Test]
    public function javascript_del_modulo_declara_sus_endpoints_ajax(): void
    {
        $js = dirname(__DIR__, 2) . '/public/assets/js/dashboard/ubicaciones.js';
        $this->assertFileExists($js);

        $codigo = (string) file_get_contents($js);
        foreach (['get_locations', 'add_ajax', 'edit_ajax', 'delete_ajax'] as $accion) {
            $this->assertStringContainsString(
                "action={$accion}",
                $codigo,
                "ubicaciones.js no referencia la acción '{$accion}'"
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
            $pdo->query('SELECT 1 FROM ubicacion LIMIT 1');
            $available = true;
        } catch (\Throwable $e) {
            $available = false;
        }

        return $available;
    }
}
