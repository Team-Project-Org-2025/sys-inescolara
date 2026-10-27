<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\core\Database;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\models\Ampliacion;
use SysInescolara\models\AuditLog;
use SysInescolara\models\Backup;
use SysInescolara\models\Cliente;
use SysInescolara\models\CuentaCobrar;
use SysInescolara\models\CuentaPagar;
use SysInescolara\models\DashboardData;
use SysInescolara\models\Especie;
use SysInescolara\models\Herramienta;
use SysInescolara\models\Insumo;
use SysInescolara\models\Inventory;
use SysInescolara\models\Lote;
use SysInescolara\models\LotePrecio;
use SysInescolara\models\Merma;
use SysInescolara\models\Notification;
use SysInescolara\models\Ornato;
use SysInescolara\models\PasswordReset;
use SysInescolara\models\Planta;
use SysInescolara\models\Proveedor;
use SysInescolara\models\Purchase;
use SysInescolara\models\RegistroInsumo;
use SysInescolara\models\Reports;
use SysInescolara\models\Role;
use SysInescolara\models\SeedCollection;
use SysInescolara\models\Tarea;
use SysInescolara\models\Trazabilidad;
use SysInescolara\models\Ubicacion;
use SysInescolara\models\UnidadMedida;
use SysInescolara\models\UsoHerramienta;
use SysInescolara\models\Usuario;
use SysInescolara\models\Venta;

/**
 * Humo de los 31 modelos: autoload, contrato de clases, métodos públicos
 * mínimos y disponibilidad del doble sin base de datos.
 */
final class ModelsSmokeTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, array<class-string>, list<string>}>
     */
    public static function models(): iterable
    {
        $readable = [ReadableInterface::class];
        $deletable = [DeletableInterface::class];
        $both = [ReadableInterface::class, DeletableInterface::class];

        // Orden canónico = orden del sidebar (INVENTARIO → COMERCIAL →
        // OPERACIONES → HERRAMIENTAS → SISTEMA), con los modelos de soporte
        // ubicados al final de su sección.
        yield 'CP-SMOKE-01 Planta' => [Planta::class, $both, ['save', 'find', 'all', 'getAll', 'getById', 'delete', 'restore', 'loadById']];
        yield 'CP-SMOKE-02 Especie' => [Especie::class, $both, ['save', 'find', 'all', 'getAll', 'getById', 'delete', 'restore', 'getLastInsertId']];
        yield 'CP-SMOKE-03 Ubicacion' => [Ubicacion::class, $both, ['save', 'find', 'all', 'getAll', 'getById', 'delete', 'restore', 'getByTipo']];
        yield 'CP-SMOKE-04 Inventory' => [Inventory::class, [], ['getConsolidated']];
        yield 'CP-SMOKE-05 Lote' => [Lote::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'add', 'update', 'getEstados']];
        yield 'CP-SMOKE-06 Trazabilidad' => [Trazabilidad::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'getAvailableBatches', 'deductBatchStock']];
        yield 'CP-SMOKE-07 Insumo' => [Insumo::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'findByNameAndCategory', 'increaseStock']];
        yield 'CP-SMOKE-08 Herramienta' => [Herramienta::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'recordUsageWithStateUpdate', 'getAllWithAvailability']];
        yield 'CP-SMOKE-09 UnidadMedida' => [UnidadMedida::class, $both, ['save', 'find', 'all', 'getAll', 'getById', 'delete', 'restore', 'loadById']];
        yield 'CP-SMOKE-10 Merma' => [Merma::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'registerLoss', 'getAvailableQuarantine']];
        yield 'CP-SMOKE-11 RegistroInsumo' => [RegistroInsumo::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'getByLote', 'getByAsignacion']];
        yield 'CP-SMOKE-12 UsoHerramienta' => [UsoHerramienta::class, $readable, ['getAll', 'getById', 'exists', 'getByAssignment', 'add', 'delete']];
        yield 'CP-SMOKE-13 Venta' => [Venta::class, $both, ['getAll', 'getById', 'agregar', 'obtenerDetalles', 'obtenerPagos', 'cancelar', 'restore', 'delete']];
        yield 'CP-SMOKE-14 LotePrecio' => [LotePrecio::class, $readable, ['getAll', 'getById', 'calcularPrecioLote', 'getDetalleInsumos', 'getPreciosLotes']];
        yield 'CP-SMOKE-15 Cliente' => [Cliente::class, $both, ['save', 'find', 'all', 'getAll', 'getById', 'delete', 'restore', 'loadById']];
        yield 'CP-SMOKE-16 CuentaCobrar' => [CuentaCobrar::class, [], ['obtenerTodos', 'obtenerPorId', 'obtenerPagos', 'registrarPago', 'actualizarEstadoVenta', 'obtenerEstadisticas']];
        yield 'CP-SMOKE-17 CuentaPagar' => [CuentaPagar::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'crear', 'registrarPago', 'anularPago']];
        yield 'CP-SMOKE-18 Purchase' => [Purchase::class, $both, ['getAll', 'getById', 'delete', 'agregar', 'actualizar', 'agregarDetalle', 'aplicarStock']];
        yield 'CP-SMOKE-19 Ornato' => [Ornato::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'agregar', 'agregarDetalles', 'obtenerDetalles']];
        yield 'CP-SMOKE-20 Ampliacion' => [Ampliacion::class, $readable, ['save', 'find', 'all', 'getAll', 'getById', 'delete', 'restore', 'registerExchange']];
        yield 'CP-SMOKE-21 Proveedor' => [Proveedor::class, $both, ['save', 'find', 'getAll', 'getById', 'getByRif', 'delete', 'add', 'update']];
        yield 'CP-SMOKE-22 Tarea' => [Tarea::class, [], ['assignTask', 'updateAssignment', 'getAssignments', 'getAssignmentById', 'getConsumptions', 'completeAssignment', 'cancelAssignment']];
        yield 'CP-SMOKE-23 SeedCollection' => [SeedCollection::class, $both, ['save', 'find', 'getAll', 'getById', 'delete', 'add', 'update', 'complete', 'addDetail']];
        yield 'CP-SMOKE-24 Reports' => [Reports::class, [], ['getModules', 'getModuleFilters', 'getReportData']];
        yield 'CP-SMOKE-25 DashboardData' => [DashboardData::class, [], ['getStats', 'getPlantsBySpecies', 'getInventorySummary', 'getPendingTasks', 'getRecentActivity']];
        yield 'CP-SMOKE-26 Usuario' => [Usuario::class, [], ['authenticate', 'verifyPassword', 'getAll', 'getById', 'add', 'update', 'delete', 'getRoles']];
        yield 'CP-SMOKE-27 Role' => [Role::class, $both, ['getAll', 'getById', 'exists', 'existsByName', 'add', 'update', 'delete']];
        yield 'CP-SMOKE-28 AuditLog' => [AuditLog::class, $readable, ['log', 'getAll', 'record', 'getById']];
        yield 'CP-SMOKE-29 Backup' => [Backup::class, [], ['create', 'restore', 'list', 'delete', 'getFilePath', 'getBackupDir', 'getDbNames', 'getDbConfigs']];
        yield 'CP-SMOKE-30 Notification' => [Notification::class, [], ['getUnreadCount', 'getRecent', 'getAll', 'markAsRead', 'create', 'delete']];
        yield 'CP-SMOKE-31 PasswordReset' => [PasswordReset::class, [], ['createToken', 'validateToken', 'markAsUsed', 'deleteExpiredTokens']];
    }

    /**
     * @param class-string $model
     * @param array<class-string> $interfaces
     * @param list<string> $methods
     */
    #[Test]
    #[DataProvider('models')]
    public function el_modelo_cumple_su_contrato_basico(string $model, array $interfaces, array $methods): void
    {
        $this->assertTrue(class_exists($model), "No se pudo autocargar {$model}");

        $reflection = new \ReflectionClass($model);
        $this->assertTrue($reflection->isInstantiable(), "{$model} debe ser instanciable");
        $this->assertFileExists(dirname(__DIR__, 2) . '/app/models/' . $reflection->getShortName() . '.php');

        if ($model === Backup::class) {
            $this->assertFalse($reflection->isSubclassOf(Database::class));
        } else {
            $this->assertTrue($reflection->isSubclassOf(Database::class), "{$model} debe extender Database");
        }

        $implemented = class_implements($model) ?: [];
        foreach ($interfaces as $interface) {
            $this->assertArrayHasKey($interface, $implemented, "{$model} debe implementar {$interface}");
        }

        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "{$model} no declara {$method}()");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "{$model}::{$method}() debe ser público");
        }
    }

    /**
     * @param class-string $model
     */
    #[Test]
    #[DataProvider('models')]
    public function el_doble_sin_base_de_datos_es_instanciable(string $model): void
    {
        if ($model === Backup::class) {
            $this->assertTrue(class_exists(Backup::class));
            return;
        }

        $short = (new \ReflectionClass($model))->getShortName();
        $double = 'SysInescolara\\Tests\\Support\\NoDb' . $short;

        $this->assertTrue(class_exists($double), "Falta el doble {$double}");
        $this->assertInstanceOf($model, new $double());
    }

    #[Test]
    public function todos_los_modelos_del_directorio_estan_cubiertos(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/app/models/*.php') ?: [];
        $actual = array_map(
            static fn (string $file): string => basename($file, '.php'),
            $files
        );
        sort($actual);

        $covered = [];
        foreach (self::models() as [$model]) {
            $covered[] = (new \ReflectionClass($model))->getShortName();
        }
        sort($covered);

        $this->assertSame($actual, $covered, 'La lista de humo debe cubrir todos los modelos de app/models');
    }
}
