<?php

declare(strict_types=1);

/**
 * Genera la matriz de trazabilidad a partir de los logs JUnit de PHPUnit.
 *
 * Uso:
 *   php tests/tools/generate_traceability.php
 *
 * Lee (si existen):
 *   tests/results/junit-unit.xml
 *   tests/results/junit-integration.xml
 *   tests/results/junit-smoke.xml
 *
 * Escribe:
 *   tests/TRACEABILITY.md
 *   tests/traceability.json
 */

$root = dirname(__DIR__, 2);
$testsDir = $root . '/tests';
$resultsDir = $testsDir . '/results';

/**
 * Orden canónico = orden del sidebar. Los modelos de soporte se ubican al
 * final de su sección.
 *
 * @return list<array{
 *     model: string,
 *     module: string,
 *     requirement: string,
 *     unit: class-string,
 *     integration: class-string,
 *     smoke: string
 * }>
 */
function modules(): array
{
    return [
        [
            'model' => 'Planta',
            'module' => 'Administrar planta',
            'requirement' => 'El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Catalogo\\PlantaTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Catalogo\\PlantaRepositoryTest',
            'smoke' => 'CP-SMOKE-01 Planta',
        ],
        [
            'model' => 'Especie',
            'module' => 'Administrar especie',
            'requirement' => 'El sistema debe permitir administrar el catálogo de especies botánicas asociadas a las plantas.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Catalogo\\EspecieTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Catalogo\\EspecieRepositoryTest',
            'smoke' => 'CP-SMOKE-02 Especie',
        ],
        [
            'model' => 'Ubicacion',
            'module' => 'Administrar ubicación',
            'requirement' => 'El sistema debe permitir administrar las ubicaciones físicas del vivero y su capacidad.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Catalogo\\UbicacionTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Catalogo\\UbicacionRepositoryTest',
            'smoke' => 'CP-SMOKE-03 Ubicacion',
        ],
        [
            'model' => 'Inventory',
            'module' => 'Inventario',
            'requirement' => 'El sistema debe consolidar el inventario de plantas, insumos, herramientas y lotes en una sola vista.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Inventario\\InventoryTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Inventario\\InventoryRepositoryTest',
            'smoke' => 'CP-SMOKE-04 Inventory',
        ],
        [
            'model' => 'Lote',
            'module' => 'Lote',
            'requirement' => 'El sistema debe permitir administrar lotes con su estado, categoría, origen y existencias.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Catalogo\\LoteTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Catalogo\\LoteRepositoryTest',
            'smoke' => 'CP-SMOKE-05 Lote',
        ],
        [
            'model' => 'Trazabilidad',
            'module' => 'Trazabilidad',
            'requirement' => 'El sistema debe permitir consultar la trazabilidad y disponibilidad de los lotes.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Operaciones\\TrazabilidadTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Operaciones\\TrazabilidadRepositoryTest',
            'smoke' => 'CP-SMOKE-06 Trazabilidad',
        ],
        [
            'model' => 'Insumo',
            'module' => 'Insumos',
            'requirement' => 'El sistema debe permitir administrar insumos, su unidad de medida y sus existencias.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Inventario\\InsumoTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Inventario\\InsumoRepositoryTest',
            'smoke' => 'CP-SMOKE-07 Insumo',
        ],
        [
            'model' => 'Herramienta',
            'module' => 'Herramientas',
            'requirement' => 'El sistema debe permitir administrar herramientas, su disponibilidad y su uso en tareas.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Inventario\\HerramientaTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Inventario\\HerramientaRepositoryTest',
            'smoke' => 'CP-SMOKE-08 Herramienta',
        ],
        [
            'model' => 'UnidadMedida',
            'module' => 'Unidad de medida',
            'requirement' => 'El sistema debe permitir administrar el catálogo de unidades de medida.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Catalogo\\UnidadMedidaTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Catalogo\\UnidadMedidaRepositoryTest',
            'smoke' => 'CP-SMOKE-09 UnidadMedida',
        ],
        [
            'model' => 'Merma',
            'module' => 'Mermas',
            'requirement' => 'El sistema debe permitir registrar mermas y consultar el inventario en cuarentena.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Operaciones\\MermaTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Operaciones\\MermaRepositoryTest',
            'smoke' => 'CP-SMOKE-10 Merma',
        ],
        [
            'model' => 'RegistroInsumo',
            'module' => 'Registro de insumos (soporte)',
            'requirement' => 'El sistema debe registrar el consumo de insumos por lote o por asignación de tarea.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Inventario\\RegistroInsumoTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Inventario\\RegistroInsumoRepositoryTest',
            'smoke' => 'CP-SMOKE-11 RegistroInsumo',
        ],
        [
            'model' => 'UsoHerramienta',
            'module' => 'Uso de herramientas (soporte)',
            'requirement' => 'El sistema debe registrar el uso de herramientas asociado a una asignación de tarea.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Inventario\\UsoHerramientaTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Inventario\\UsoHerramientaRepositoryTest',
            'smoke' => 'CP-SMOKE-12 UsoHerramienta',
        ],
        [
            'model' => 'Venta',
            'module' => 'Procesar venta',
            'requirement' => 'El sistema debe permitir procesar ventas de contado o crédito con detalles y pagos.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Ventas\\VentaTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Ventas\\VentaRepositoryTest',
            'smoke' => 'CP-SMOKE-13 Venta',
        ],
        [
            'model' => 'LotePrecio',
            'module' => 'Gestionar precio',
            'requirement' => 'El sistema debe permitir calcular y administrar los precios de los lotes.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Inventario\\LotePrecioTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Inventario\\LotePrecioRepositoryTest',
            'smoke' => 'CP-SMOKE-14 LotePrecio',
        ],
        [
            'model' => 'Cliente',
            'module' => 'Gestionar cliente',
            'requirement' => 'El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los clientes.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Ventas\\ClienteTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Ventas\\ClienteRepositoryTest',
            'smoke' => 'CP-SMOKE-15 Cliente',
        ],
        [
            'model' => 'CuentaCobrar',
            'module' => 'Cuenta por cobrar',
            'requirement' => 'El sistema debe permitir consultar cuentas por cobrar, registrar pagos y actualizar su estado.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Ventas\\CuentaCobrarTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Ventas\\CuentaCobrarRepositoryTest',
            'smoke' => 'CP-SMOKE-16 CuentaCobrar',
        ],
        [
            'model' => 'CuentaPagar',
            'module' => 'Cuentas por pagar',
            'requirement' => 'El sistema debe permitir administrar cuentas por pagar, sus pagos y saldos.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Compras\\CuentaPagarTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Compras\\CuentaPagarRepositoryTest',
            'smoke' => 'CP-SMOKE-17 CuentaPagar',
        ],
        [
            'model' => 'Purchase',
            'module' => 'Gestionar compra',
            'requirement' => 'El sistema debe permitir registrar compras, sus detalles, recepción y actualización de stock.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Compras\\PurchaseTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Compras\\PurchaseRepositoryTest',
            'smoke' => 'CP-SMOKE-18 Purchase',
        ],
        [
            'model' => 'Ornato',
            'module' => 'Gestionar ornatos',
            'requirement' => 'El sistema debe permitir registrar y administrar los servicios de ornato.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Ventas\\OrnatoTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Ventas\\OrnatoRepositoryTest',
            'smoke' => 'CP-SMOKE-19 Ornato',
        ],
        [
            'model' => 'Ampliacion',
            'module' => 'Gestionar ampliación',
            'requirement' => 'El sistema debe permitir registrar ampliaciones e intercambios de plantas entre clientes.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Operaciones\\AmpliacionTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Operaciones\\AmpliacionRepositoryTest',
            'smoke' => 'CP-SMOKE-20 Ampliacion',
        ],
        [
            'model' => 'Proveedor',
            'module' => 'Gestionar proveedor',
            'requirement' => 'El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los proveedores.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Compras\\ProveedorTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Compras\\ProveedorRepositoryTest',
            'smoke' => 'CP-SMOKE-21 Proveedor',
        ],
        [
            'model' => 'Tarea',
            'module' => 'Asignar tarea',
            'requirement' => 'El sistema debe permitir asignar tareas, actualizarlas, completarlas o cancelarlas.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Operaciones\\TareaTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Operaciones\\TareaRepositoryTest',
            'smoke' => 'CP-SMOKE-22 Tarea',
        ],
        [
            'model' => 'SeedCollection',
            'module' => 'Gestionar recolección',
            'requirement' => 'El sistema debe permitir registrar recolecciones de semillas, sus detalles y su cierre.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Operaciones\\SeedCollectionTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Operaciones\\SeedCollectionRepositoryTest',
            'smoke' => 'CP-SMOKE-23 SeedCollection',
        ],
        [
            'model' => 'Reports',
            'module' => 'Reportes',
            'requirement' => 'El sistema debe generar reportes por módulo con filtros y datos consolidados.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Analitica\\ReportsTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Analitica\\ReportsRepositoryTest',
            'smoke' => 'CP-SMOKE-24 Reports',
        ],
        [
            'model' => 'DashboardData',
            'module' => 'Inicio / Dashboard',
            'requirement' => 'El sistema debe mostrar indicadores, alertas y actividad reciente en el dashboard.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Analitica\\DashboardDataTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Analitica\\DashboardDataRepositoryTest',
            'smoke' => 'CP-SMOKE-25 DashboardData',
        ],
        [
            'model' => 'Usuario',
            'module' => 'Usuario',
            'requirement' => 'El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Seguridad\\UsuarioTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Seguridad\\UsuarioRepositoryTest',
            'smoke' => 'CP-SMOKE-26 Usuario',
        ],
        [
            'model' => 'Role',
            'module' => 'Roles',
            'requirement' => 'El sistema debe permitir administrar roles y validar su existencia y asignación.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Seguridad\\RoleTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Seguridad\\RoleRepositoryTest',
            'smoke' => 'CP-SMOKE-27 Role',
        ],
        [
            'model' => 'AuditLog',
            'module' => 'Bitácora',
            'requirement' => 'El sistema debe registrar y consultar la bitácora de auditoría de las acciones.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Seguridad\\AuditLogTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Seguridad\\AuditLogRepositoryTest',
            'smoke' => 'CP-SMOKE-28 AuditLog',
        ],
        [
            'model' => 'Backup',
            'module' => 'Respaldo',
            'requirement' => 'El sistema debe permitir crear, listar, descargar y restaurar respaldos de la base de datos.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Analitica\\BackupTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Analitica\\BackupRepositoryTest',
            'smoke' => 'CP-SMOKE-29 Backup',
        ],
        [
            'model' => 'Notification',
            'module' => 'Notificaciones (soporte)',
            'requirement' => 'El sistema debe mostrar y administrar las notificaciones de cada usuario.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Seguridad\\NotificationTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Seguridad\\NotificationRepositoryTest',
            'smoke' => 'CP-SMOKE-30 Notification',
        ],
        [
            'model' => 'PasswordReset',
            'module' => 'Recuperación de contraseña (soporte)',
            'requirement' => 'El sistema debe permitir generar, validar y consumir tokens de recuperación de contraseña.',
            'unit' => 'SysInescolara\\Tests\\Unit\\Seguridad\\PasswordResetTest',
            'integration' => 'SysInescolara\\Tests\\Integration\\Seguridad\\PasswordResetRepositoryTest',
            'smoke' => 'CP-SMOKE-31 PasswordReset',
        ],
    ];
}

/**
 * @return array<string, list<array{name: string, status: string}>>
 */
function loadJunit(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $xml = @simplexml_load_file($file);
    if ($xml === false) {
        return [];
    }

    $cases = [];
    foreach ($xml->xpath('//testcase') ?: [] as $testcase) {
        // PHPUnit escribe el atributo `classname` con puntos
        // (SysInescolara.Tests...), pero el atributo `class` conserva los
        // backslashes del FQCN. Preferimos `class` y normalizamos el otro.
        $class = (string) ($testcase['class'] ?? $testcase['classname']);
        $class = str_replace('.', '\\', $class);
        $name = (string) $testcase['name'];

        $status = 'PASS';
        if (isset($testcase->failure) || isset($testcase->error)) {
            $status = 'FAIL';
        } elseif (isset($testcase->skipped)) {
            $status = 'SKIP';
        }

        $cases[$class][] = ['name' => $name, 'status' => $status];
    }

    return $cases;
}

/**
 * @param list<array{name: string, status: string}> $cases
 * @return array{status: string, total: int, pass: int, fail: int, skip: int}
 */
function summarize(array $cases): array
{
    if ($cases === []) {
        return ['status' => 'NO EJECUTADO', 'total' => 0, 'pass' => 0, 'fail' => 0, 'skip' => 0];
    }

    $pass = 0;
    $fail = 0;
    $skip = 0;
    foreach ($cases as $case) {
        match ($case['status']) {
            'PASS' => $pass++,
            'FAIL' => $fail++,
            'SKIP' => $skip++,
            default => null,
        };
    }

    $status = $fail > 0 ? 'FAIL' : ($pass === 0 && $skip > 0 ? 'SKIP' : 'PASS');

    return [
        'status' => $status,
        'total' => count($cases),
        'pass' => $pass,
        'fail' => $fail,
        'skip' => $skip,
    ];
}

/**
 * @param array<string, list<array{name: string, status: string}>> $cases
 * @return list<array{name: string, status: string}>
 */
function smokeCases(array $cases, string $dataset): array
{
    $class = 'SysInescolara\\Tests\\Smoke\\ModelsSmokeTest';
    $found = [];

    foreach ($cases[$class] ?? [] as $case) {
        $isContract = str_contains($case['name'], 'el_modelo_cumple_su_contrato_basico');
        $isDouble = str_contains($case['name'], 'el_doble_sin_base_de_datos_es_instanciable');

        if (($isContract || $isDouble) && str_contains($case['name'], $dataset)) {
            $found[] = $case;
        }
    }

    return $found;
}

function badge(string $status): string
{
    return match ($status) {
        'PASS' => '✅ PASS',
        'FAIL' => '❌ FAIL',
        'SKIP' => '⚠️ SKIP',
        default => '⬜ NO EJECUTADO',
    };
}

function evidence(array $summary): string
{
    if ($summary['total'] === 0) {
        return 'Sin log JUnit';
    }

    $parts = [sprintf('%d/%d PASS', $summary['pass'], $summary['total'])];
    if ($summary['fail'] > 0) {
        $parts[] = sprintf('%d FAIL', $summary['fail']);
    }
    if ($summary['skip'] > 0) {
        $parts[] = sprintf('%d SKIP', $summary['skip']);
    }

    return implode(' · ', $parts);
}

$unitCases = loadJunit($resultsDir . '/junit-unit.xml');
$integrationCases = loadJunit($resultsDir . '/junit-integration.xml');
$smokeCasesAll = loadJunit($resultsDir . '/junit-smoke.xml');

$json = [
    'generado' => date('c'),
    'orden' => 'sidebar',
    'resumen' => [
        'requisitos' => 0,
        'casos' => 0,
        'pass' => 0,
        'fail' => 0,
        'skip' => 0,
        'noEjecutado' => 0,
        'pruebas' => 0,
        'pruebasPass' => 0,
        'pruebasFail' => 0,
        'pruebasSkip' => 0,
    ],
    'omitidas' => [],
    'modulos' => [],
];

$rows = [];
$moduleRows = [];
$skippedRows = [];

foreach (modules() as $index => $module) {
    $number = $index + 1;
    $unit = summarize($unitCases[$module['unit']] ?? []);
    $integration = summarize($integrationCases[$module['integration']] ?? []);
    $smoke = summarize(smokeCases($smokeCasesAll, $module['smoke']));

    $suiteCases = [
        'Unitaria' => [$module['unit'], $unitCases[$module['unit']] ?? []],
        'Integración' => [$module['integration'], $integrationCases[$module['integration']] ?? []],
        'Humo' => ['SysInescolara\\Tests\\Smoke\\ModelsSmokeTest', smokeCases($smokeCasesAll, $module['smoke'])],
    ];

    foreach ($suiteCases as $tipo => [$suiteClass, $testCases]) {
        foreach ($testCases as $testCase) {
            if ($testCase['status'] === 'SKIP') {
                $skippedRows[] = [
                    'requisito' => sprintf('RF-%02d', $number),
                    'modelo' => $module['model'],
                    'tipo' => $tipo,
                    'prueba' => $testCase['name'],
                    'evidencia' => $suiteClass,
                ];
                $json['omitidas'][] = [
                    'idRequisito' => sprintf('RF-%02d', $number),
                    'modelo' => $module['model'],
                    'tipo' => $tipo,
                    'prueba' => $testCase['name'],
                    'evidencia' => $suiteClass,
                ];
            }
        }
    }

    $cases = [
        [
            'id' => sprintf('CP-UNI-%02d', $number),
            'tipo' => 'Unitaria',
            'descripcion' => sprintf(
                'Validar getters, setters, fill, validaciones y lógica pura de %s sin base de datos.',
                $module['model']
            ),
            'resultadoEsperado' => sprintf(
                'Todas las pruebas unitarias de %s pasan sin abrir MySQL.',
                $module['model']
            ),
            'evidencia' => $module['unit'],
            'resumen' => $unit,
        ],
        [
            'id' => sprintf('CP-INT-%02d', $number),
            'tipo' => 'Integración',
            'descripcion' => sprintf(
                'Ejecutar el SQL real de %s contra SQLite en memoria: CRUD, consultas y flujos.',
                $module['model']
            ),
            'resultadoEsperado' => sprintf(
                'Todas las pruebas de integración de %s pasan sobre SQLite.',
                $module['model']
            ),
            'evidencia' => $module['integration'],
            'resumen' => $integration,
        ],
        [
            'id' => sprintf('CP-SMOKE-%02d', $number),
            'tipo' => 'Humo',
            'descripcion' => sprintf(
                'Validar autoload, contrato de clase, interfaces, métodos públicos y doble NoDb de %s.',
                $module['model']
            ),
            'resultadoEsperado' => sprintf(
                '%s se autocarga, extiende Database (salvo Backup), implementa sus interfaces, expone sus métodos y su doble NoDb es instanciable.',
                $module['model']
            ),
            'evidencia' => 'SysInescolara\\Tests\\Smoke\\ModelsSmokeTest [' . $module['smoke'] . '] (contrato + doble NoDb)',
            'resumen' => $smoke,
        ],
    ];

    $statuses = array_column(array_column($cases, 'resumen'), 'status');
    $moduleStatus = in_array('FAIL', $statuses, true)
        ? 'FAIL'
        : (in_array('NO EJECUTADO', $statuses, true) ? 'NO EJECUTADO' : (in_array('SKIP', $statuses, true) ? 'SKIP' : 'PASS'));

    $moduleRows[] = [
        'n' => $number,
        'model' => $module['model'],
        'module' => $module['module'],
        'unit' => $unit,
        'integration' => $integration,
        'smoke' => $smoke,
        'skip' => $unit['skip'] + $integration['skip'] + $smoke['skip'],
        'status' => $moduleStatus,
    ];

    $json['modulos'][] = [
        'numero' => $number,
        'modelo' => $module['model'],
        'moduloSidebar' => $module['module'],
        'idRequisito' => sprintf('RF-%02d', $number),
        'descripcionRequisito' => $module['requirement'],
        'estado' => $moduleStatus,
        'casos' => array_map(
            static fn (array $case): array => [
                'id' => $case['id'],
                'tipo' => $case['tipo'],
                'descripcion' => $case['descripcion'],
                'resultadoEsperado' => $case['resultadoEsperado'],
                'estado' => $case['resumen']['status'],
                'evidencia' => $case['evidencia'],
                'pruebas' => $case['resumen']['total'],
                'pass' => $case['resumen']['pass'],
                'fail' => $case['resumen']['fail'],
                'skip' => $case['resumen']['skip'],
            ],
            $cases
        ),
    ];

    foreach ($cases as $case) {
        $rows[] = [
            'requisito' => sprintf('RF-%02d', $number),
            'descripcionRequisito' => $module['requirement'],
            'id' => $case['id'],
            'tipo' => $case['tipo'],
            'descripcion' => $case['descripcion'],
            'esperado' => $case['resultadoEsperado'],
            'estado' => $case['resumen']['status'],
            'evidencia' => $case['evidencia'] . ' (' . evidence($case['resumen']) . ')',
        ];

        $json['resumen']['casos']++;
        $json['resumen']['pruebas'] += $case['resumen']['total'];
        $json['resumen']['pruebasPass'] += $case['resumen']['pass'];
        $json['resumen']['pruebasFail'] += $case['resumen']['fail'];
        $json['resumen']['pruebasSkip'] += $case['resumen']['skip'];
        match ($case['resumen']['status']) {
            'PASS' => $json['resumen']['pass']++,
            'FAIL' => $json['resumen']['fail']++,
            'SKIP' => $json['resumen']['skip']++,
            default => $json['resumen']['noEjecutado']++,
        };
    }
}

$json['resumen']['requisitos'] = count($json['modulos']);

$markdown = "# Matriz de Trazabilidad de Pruebas — Sys Inescolara\n\n";
$markdown .= sprintf("Generado: `%s` · Orden: sidebar del sistema\n\n", date('Y-m-d H:i:s'));
$markdown .= "## Resumen\n\n";
$markdown .= "| Requisitos | Casos | Casos PASS | Casos FAIL | Casos SKIP | No ejecutados |\n";
$markdown .= "| ---: | ---: | ---: | ---: | ---: | ---: |\n";
$markdown .= sprintf(
    "| %d | %d | %d | %d | %d | %d |\n\n",
    $json['resumen']['requisitos'],
    $json['resumen']['casos'],
    $json['resumen']['pass'],
    $json['resumen']['fail'],
    $json['resumen']['skip'],
    $json['resumen']['noEjecutado']
);
$markdown .= sprintf(
    "Pruebas individuales ejecutadas: **%d** · PASS: **%d** · FAIL: **%d** · SKIP: **%d**.\n\n",
    $json['resumen']['pruebas'],
    $json['resumen']['pruebasPass'],
    $json['resumen']['pruebasFail'],
    $json['resumen']['pruebasSkip']
);
$modulesWithSkips = array_filter($moduleRows, static fn (array $row): bool => $row['skip'] > 0);
$markdown .= sprintf(
    "Módulos con al menos una prueba omitida: **%d** (%s).\n\n",
    count($modulesWithSkips),
    implode(', ', array_map(static fn (array $row): string => $row['model'], $modulesWithSkips)) ?: 'ninguno'
);
$markdown .= "## Resumen por módulo\n\n";
$markdown .= "| # | Módulo | Modelo | Unitaria | Integración | Humo | Omitidos | Estado |\n";
$markdown .= "| ---: | --- | --- | --- | --- | --- | ---: | --- |\n";
foreach ($moduleRows as $row) {
    $markdown .= sprintf(
        "| %02d | %s | `%s` | %s (%d/%d) | %s (%d/%d) | %s (%d/%d) | %d | %s |\n",
        $row['n'],
        $row['module'],
        $row['model'],
        badge($row['unit']['status']),
        $row['unit']['pass'],
        $row['unit']['total'],
        badge($row['integration']['status']),
        $row['integration']['pass'],
        $row['integration']['total'],
        badge($row['smoke']['status']),
        $row['smoke']['pass'],
        $row['smoke']['total'],
        $row['skip'],
        badge($row['status'])
    );
}

$markdown .= "\n## Matriz detallada\n\n";
$markdown .= "| ID Requisito / HU | Descripción del Requisito (o HU) | ID Caso de Prueba | Tipo de Prueba | Descripción del Caso de Prueba | Resultado Esperado | Estado | Evidencia |\n";
$markdown .= "| --- | --- | --- | --- | --- | --- | --- | --- |\n";

foreach ($rows as $row) {
    $markdown .= sprintf(
        "| %s | %s | %s | %s | %s | %s | %s | %s |\n",
        $row['requisito'],
        str_replace('|', '\\|', $row['descripcionRequisito']),
        $row['id'],
        $row['tipo'],
        str_replace('|', '\\|', $row['descripcion']),
        str_replace('|', '\\|', $row['esperado']),
        badge($row['estado']),
        str_replace('|', '\\|', $row['evidencia'])
    );
}

$markdown .= "\n## Pruebas omitidas (SKIP)\n\n";
if ($skippedRows === []) {
    $markdown .= "No hay pruebas omitidas.\n";
} else {
    $markdown .= "| Requisito | Módulo | Tipo | Prueba | Evidencia | Motivo |\n";
    $markdown .= "| --- | --- | --- | --- | --- | --- |\n";
    foreach ($skippedRows as $row) {
        $markdown .= sprintf(
            "| %s | %s | %s | `%s` | `%s` | Limitación documentada en el test (SQL exclusivo de MySQL o modelo que abre una conexión real). |\n",
            $row['requisito'],
            $row['modelo'],
            $row['tipo'],
            str_replace('|', '\\|', $row['prueba']),
            str_replace('|', '\\|', $row['evidencia'])
        );
    }
}

$markdown .= "\n## Cómo leer esta matriz\n\n";
$markdown .= "- **PASS:** todos los casos del tipo ejecutaron sin fallos. Un caso PASS puede incluir pruebas SKIP; revisa la columna **Omitidos** y la sección de pruebas omitidas.\n";
$markdown .= "- **FAIL:** al menos un caso falló; revisa `tests/results/junit-*.xml`.\n";
$markdown .= "- **SKIP:** el caso se omitió por una limitación documentada (por ejemplo SQL exclusivo de MySQL).\n";
$markdown .= "- **NO EJECUTADO:** no existe el log JUnit de esa suite; ejecuta los comandos de `GUIA_PRUEBAS.md`.\n";
$markdown .= "- La evidencia incluye la clase/método y el conteo `PASS/total`.\n";

if (!is_dir($resultsDir)) {
    mkdir($resultsDir, 0777, true);
}

file_put_contents($testsDir . '/TRACEABILITY.md', $markdown);
file_put_contents(
    $testsDir . '/traceability.json',
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
);

printf(
    "Matriz generada: %d requisitos, %d casos (%d PASS, %d FAIL, %d SKIP, %d sin ejecutar).\n",
    $json['resumen']['requisitos'],
    $json['resumen']['casos'],
    $json['resumen']['pass'],
    $json['resumen']['fail'],
    $json['resumen']['skip'],
    $json['resumen']['noEjecutado']
);
