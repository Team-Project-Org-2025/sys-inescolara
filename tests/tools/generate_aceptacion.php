<?php

declare(strict_types=1);

/**
 * Genera la matriz de pruebas de aceptación a partir del reporte JUnit de
 * Codeception (`tests/aceptacion/_output/report.xml`).
 *
 * Uso:
 *   php tests/tools/generate_aceptacion.php
 */

$root = dirname(__DIR__, 2);
$acceptanceDir = $root . '/tests/aceptacion';
$reportFile = $acceptanceDir . '/_output/report.xml';
$outputFile = $acceptanceDir . '/ACEPTACION.md';

require_once $root . '/vendor/autoload.php';
require_once $acceptanceDir . '/_support/ModuleCatalog.php';

use Tests\Aceptacion\Support\ModuleCatalog;

$requirements = [
    'RF-01' => 'El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero.',
    'RF-02' => 'El sistema debe permitir administrar el catálogo de especies botánicas asociadas a las plantas.',
    'RF-03' => 'El sistema debe permitir administrar las ubicaciones físicas del vivero y su capacidad.',
    'RF-04' => 'El sistema debe consolidar el inventario de plantas, insumos, herramientas y lotes en una sola vista.',
    'RF-05' => 'El sistema debe permitir administrar lotes con su estado, categoría, origen y existencias.',
    'RF-06' => 'El sistema debe permitir consultar la trazabilidad y disponibilidad de los lotes.',
    'RF-07' => 'El sistema debe permitir administrar insumos, su unidad de medida y sus existencias.',
    'RF-08' => 'El sistema debe permitir administrar herramientas, su disponibilidad y su uso en tareas.',
    'RF-09' => 'El sistema debe permitir administrar el catálogo de unidades de medida.',
    'RF-10' => 'El sistema debe permitir registrar mermas y consultar el inventario en cuarentena.',
    'RF-11' => 'El sistema debe registrar el consumo de insumos por lote o por asignación de tarea.',
    'RF-12' => 'El sistema debe registrar el uso de herramientas asociado a una asignación de tarea.',
    'RF-13' => 'El sistema debe permitir procesar ventas de contado o crédito con detalles y pagos.',
    'RF-14' => 'El sistema debe permitir calcular y administrar los precios de los lotes.',
    'RF-15' => 'El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los clientes.',
    'RF-16' => 'El sistema debe permitir consultar cuentas por cobrar, registrar pagos y actualizar su estado.',
    'RF-17' => 'El sistema debe permitir administrar cuentas por pagar, sus pagos y saldos.',
    'RF-18' => 'El sistema debe permitir registrar compras, sus detalles, recepción y actualización de stock.',
    'RF-19' => 'El sistema debe permitir registrar y administrar los servicios de ornato.',
    'RF-20' => 'El sistema debe permitir registrar ampliaciones e intercambios de plantas entre clientes.',
    'RF-21' => 'El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los proveedores.',
    'RF-22' => 'El sistema debe permitir asignar tareas, actualizarlas, completarlas o cancelarlas.',
    'RF-23' => 'El sistema debe permitir registrar recolecciones de semillas, sus detalles y su cierre.',
    'RF-24' => 'El sistema debe generar reportes por módulo con filtros y datos consolidados.',
    'RF-25' => 'El sistema debe mostrar indicadores, alertas y actividad reciente en el dashboard.',
    'RF-26' => 'El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos.',
    'RF-27' => 'El sistema debe permitir administrar roles y validar su existencia y asignación.',
    'RF-28' => 'El sistema debe registrar y consultar la bitácora de auditoría de las acciones.',
    'RF-29' => 'El sistema debe permitir crear, listar, descargar y restaurar respaldos de la base de datos.',
    'RF-30' => 'El sistema debe mostrar y administrar las notificaciones de cada usuario.',
    'RF-31' => 'El sistema debe permitir generar, validar y consumir tokens de recuperación de contraseña.',
];

$cases = [];

foreach (ModuleCatalog::all() as $rf => $module) {
    $num = (int) substr($rf, 3);
    $cases['CP-ACE-' . (100 + $num)] = [
        'rf' => $rf,
        'module' => $module['module'],
        'tipo' => 'Aceptación · UI',
        'descripcion' => 'Abrir ' . $module['path'] . ' y verificar que el módulo "' . $module['module'] . '" carga con su contenido y tabla operativa.',
        'esperado' => 'La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX.',
        'clase' => 'Tests\\Aceptacion\\Acceptance\\ModulosCest',
        'metodo' => null,
    ];
}

$explicit = [
    'CP-ACE-001' => ['RF-26', 'Autenticación', 'Aceptación · UI', 'Verificar que el formulario de login muestra usuario, contraseña y reCAPTCHA.', 'El formulario es visible y contiene los controles #email, #password, #loginBtn y .g-recaptcha.', 'Tests\\Aceptacion\\Acceptance\\AutenticacionCest', 'elFormularioDeLoginMuestraUsuarioPasswordYCaptcha'],
    'CP-ACE-002' => ['RF-26', 'Autenticación', 'Aceptación · UI', 'Intentar iniciar sesión con credenciales inexistentes.', 'El sistema permanece en /login y muestra un mensaje de credenciales incorrectas.', 'Tests\\Aceptacion\\Acceptance\\AutenticacionCest', 'credencialesInvalidasMuestranErrorSinEntrar'],
    'CP-ACE-003' => ['RF-26', 'Autenticación', 'Aceptación · UI', 'Iniciar sesión con el usuario administrador de pruebas.', 'El sistema redirige a /dashboard y muestra el sidebar y el saludo de bienvenida.', 'Tests\\Aceptacion\\Acceptance\\AutenticacionCest', 'credencialesValidasRedirigenAlDashboard'],
    'CP-ACE-004' => ['RF-26', 'Autenticación', 'Aceptación · UI', 'Cerrar la sesión desde el sistema.', 'El sistema regresa al formulario de login y la sesión deja de estar activa.', 'Tests\\Aceptacion\\Acceptance\\AutenticacionCest', 'cerrarSesionRegresaAlLogin'],
    'CP-ACE-005' => ['RF-26', 'Autenticación', 'Aceptación · UI', 'Acceder a una ruta protegida sin sesión iniciada.', 'El sistema redirige al login y no expone el módulo.', 'Tests\\Aceptacion\\Acceptance\\AutenticacionCest', 'unaRutaProtegidaRedirigeAlLoginSinSesion'],
    'CP-ACE-006' => ['RF-31', 'Recuperación de contraseña', 'Aceptación · UI', 'Abrir el formulario de recuperación de contraseña.', 'El formulario de recuperación es visible con los campos de correo y envío.', 'Tests\\Aceptacion\\Acceptance\\AutenticacionCest', 'laRecuperacionDePasswordMuestraElFormulario'],
    'CP-ACE-201' => ['RF-25', 'Portal público', 'Aceptación · UI', 'Abrir la página de inicio sin autenticación.', 'La portada carga y muestra la propuesta "Propagamos vida para el Estado Lara".', 'Tests\\Aceptacion\\Acceptance\\PublicoCest', 'laPaginaDeInicioEsPublicaYMuestraLaPropuesta'],
    'CP-ACE-202' => ['RF-01', 'Portal público', 'Aceptación · UI', 'Abrir el catálogo público de especies.', 'El catálogo carga y describe las especies disponibles.', 'Tests\\Aceptacion\\Acceptance\\PublicoCest', 'elCatalogoPublicoListaEspecies'],
    'CP-ACE-203' => ['RF-24', 'Portal público', 'Aceptación · UI', 'Abrir la página de servicios.', 'La página describe la oferta de producción y suministro.', 'Tests\\Aceptacion\\Acceptance\\PublicoCest', 'laPaginaDeServiciosDescribeLaOferta'],
    'CP-ACE-204' => ['RF-25', 'Portal público', 'Aceptación · UI', 'Abrir la página "Nosotros".', 'La página presenta al Instituto de Ecosocialismo del Estado Lara.', 'Tests\\Aceptacion\\Acceptance\\PublicoCest', 'laPaginaNosotrosPresentaLaInstitucion'],
    'CP-ACE-205' => ['RF-25', 'Portal público', 'Aceptación · UI', 'Abrir la página de contacto.', 'La página muestra los canales de contacto del vivero.', 'Tests\\Aceptacion\\Acceptance\\PublicoCest', 'laPaginaDeContactoMuestraLosCanales'],
    'CP-ACE-301' => ['RF-02', 'Especies', 'Aceptación · CRUD', 'Crear una especie desde la interfaz, buscarla y eliminarla.', 'La especie aparece en la tabla tras crearla y desaparece después de la baja lógica.', 'Tests\\Aceptacion\\Acceptance\\CatalogosCest', 'crearYeliminarUnaEspecie'],
    'CP-ACE-302' => ['RF-03', 'Ubicaciones', 'Aceptación · CRUD', 'Crear una ubicación desde la interfaz, buscarla y eliminarla.', 'La ubicación aparece en la tabla tras crearla y desaparece después de la baja lógica.', 'Tests\\Aceptacion\\Acceptance\\CatalogosCest', 'crearYeliminarUnaUbicacion'],
    'CP-ACE-303' => ['RF-15', 'Clientes', 'Aceptación · CRUD', 'Crear un cliente desde la interfaz, buscarlo y eliminarlo.', 'El cliente aparece en la tabla tras crearlo y desaparece después de la baja lógica.', 'Tests\\Aceptacion\\Acceptance\\CatalogosCest', 'crearYeliminarUnCliente'],
];

foreach ($explicit as $id => [$rf, $module, $tipo, $descripcion, $esperado, $clase, $metodo]) {
    $cases[$id] = [
        'rf' => $rf,
        'module' => $module,
        'tipo' => $tipo,
        'descripcion' => $descripcion,
        'esperado' => $esperado,
        'clase' => $clase,
        'metodo' => $metodo,
    ];
}

// Asignar el método de las pruebas de módulo por RF.
foreach ($cases as $id => &$case) {
    if ($case['metodo'] !== null) {
        continue;
    }
    $rf = $case['rf'];
    $num = (int) substr($rf, 3);
    $map = [
        1 => 'rf01PlantasCargaYEsOperativa',
        2 => 'rf02EspeciesCargaYEsOperativa',
        3 => 'rf03UbicacionesCargaYEsOperativa',
        4 => 'rf04InventarioCargaYEsOperativo',
        5 => 'rf05LotesCargaYEsOperativo',
        6 => 'rf06TrazabilidadCargaYEsOperativa',
        7 => 'rf07InsumosCargaYEsOperativo',
        8 => 'rf08HerramientasCargaYEsOperativo',
        9 => 'rf09UnidadesMedidaCargaYEsOperativo',
        10 => 'rf10MermasCargaYEsOperativo',
        11 => 'rf11RegistroInsumosSoporteCargaYEsOperativo',
        12 => 'rf12UsoHerramientasSoporteCargaYEsOperativo',
        13 => 'rf13VentasCargaYEsOperativa',
        14 => 'rf14PreciosCargaYEsOperativo',
        15 => 'rf15ClientesCargaYEsOperativo',
        16 => 'rf16CuentasCobrarCargaYEsOperativa',
        17 => 'rf17CuentasPagarCargaYEsOperativa',
        18 => 'rf18ComprasCargaYEsOperativa',
        19 => 'rf19OrnatosCargaYEsOperativo',
        20 => 'rf20AmpliacionCargaYEsOperativa',
        21 => 'rf21ProveedoresCargaYEsOperativo',
        22 => 'rf22TareasCargaYEsOperativa',
        23 => 'rf23RecoleccionSemillasCargaYEsOperativa',
        24 => 'rf24ReportesCargaYEsOperativo',
        25 => 'rf25DashboardCargaYEsOperativo',
        26 => 'rf26UsuariosCargaYEsOperativo',
        27 => 'rf27RolesCargaYEsOperativo',
        28 => 'rf28AuditoriaCargaYEsOperativa',
        29 => 'rf29RespaldosCargaYEsOperativo',
        30 => 'rf30NotificacionesSoporteCargaYEsOperativo',
        31 => 'rf31RecuperacionPasswordSoporteCargaYEsOperativa',
    ];
    $case['metodo'] = $map[$num];
}
unset($case);

// Parsear el reporte JUnit de Codeception.
$results = [];
if (is_file($reportFile)) {
    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $dom->load($reportFile);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if ($loaded) {
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//testcase') as $node) {
            /** @var DOMElement $node */
            $class = $node->getAttribute('class');
            $method = $node->getAttribute('name');
            $state = 'PASS';
            $detail = '';

            foreach ($node->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }
                if ($child->tagName === 'failure' || $child->tagName === 'error') {
                    $state = 'FAIL';
                    $detail = trim($child->textContent);
                    break;
                }
                if ($child->tagName === 'skipped') {
                    $state = 'SKIP';
                }
            }

            $results[$class . '::' . $method] = [
                'state' => $state,
                'time' => (float) $node->getAttribute('time'),
                'detail' => $detail,
            ];
        }
    }
}

foreach ($cases as $id => &$case) {
    $key = $case['clase'] . '::' . $case['metodo'];
    $result = $results[$key] ?? null;
    $case['estado'] = $result === null ? 'NO EJECUTADO' : $result['state'];
    $case['time'] = $result['time'] ?? 0.0;
    $case['detail'] = $result['detail'] ?? '';
    $case['evidencia'] = $case['clase'] . '::' . $case['metodo'];
}
unset($case);

$total = count($cases);
$pass = count(array_filter($cases, static fn ($c) => $c['estado'] === 'PASS'));
$fail = count(array_filter($cases, static fn ($c) => $c['estado'] === 'FAIL'));
$skip = count(array_filter($cases, static fn ($c) => $c['estado'] === 'SKIP'));
$notRun = count(array_filter($cases, static fn ($c) => $c['estado'] === 'NO EJECUTADO'));
$totalTime = array_sum(array_column($cases, 'time'));

$icon = static fn (string $state): string => match ($state) {
    'PASS' => '✅ PASS',
    'FAIL' => '❌ FAIL',
    'SKIP' => '⚠️ SKIP',
    default => '⏳ NO EJECUTADO',
};

$md = [];
$md[] = '# Matriz de Pruebas de Aceptación — Sys Inescolara';
$md[] = '';
$md[] = 'Generado: `' . date('Y-m-d H:i:s') . '` · Codeception 5 + WebDriver (Brave/Chrome headless) · Orden: requisitos RF-01 a RF-31';
$md[] = '';
$md[] = '## Resumen';
$md[] = '';
$md[] = '| Casos | PASS | FAIL | SKIP | No ejecutados | Tiempo total |';
$md[] = '| ---: | ---: | ---: | ---: | ---: | ---: |';
$md[] = sprintf('| %d | %d | %d | %d | %d | %.2f s |', $total, $pass, $fail, $skip, $notRun, $totalTime);
$md[] = '';
$md[] = '## Resumen por requisito';
$md[] = '';
$md[] = '| # | Requisito | Módulo / pantalla | Casos | Estado |';
$md[] = '| ---: | --- | --- | ---: | --- |';
$stateRank = ['FAIL' => 3, 'NO EJECUTADO' => 2, 'SKIP' => 1, 'PASS' => 0];
for ($num = 1; $num <= 31; $num++) {
    $rf = sprintf('RF-%02d', $num);
    $rfCases = array_filter($cases, static fn ($c) => $c['rf'] === $rf);
    if ($rfCases === []) {
        continue;
    }
    $primary = $cases['CP-ACE-' . (100 + $num)] ?? reset($rfCases);
    $worst = 'PASS';
    foreach ($rfCases as $rfCase) {
        if (($stateRank[$rfCase['estado']] ?? 0) > ($stateRank[$worst] ?? 0)) {
            $worst = $rfCase['estado'];
        }
    }
    $md[] = sprintf(
        '| %02d | %s | %s | %d | %s |',
        $num,
        $rf,
        $primary['module'],
        count($rfCases),
        $icon($worst)
    );
}
$md[] = '';
$md[] = '## Matriz detallada';
$md[] = '';
$md[] = '| ID Requisito / HU | Descripción del Requisito (o HU) | ID Caso de Prueba | Tipo de Prueba | Descripción del Caso de Prueba | Resultado Esperado | Estado | Evidencia |';
$md[] = '| --- | --- | --- | --- | --- | --- | --- | --- |';
foreach ($cases as $id => $case) {
    $evidence = '`' . $case['clase'] . '::' . $case['metodo'] . '`';
    if ($case['estado'] === 'PASS') {
        $evidence .= sprintf(' (%.2f s)', $case['time']);
    } elseif ($case['estado'] === 'FAIL') {
        $evidence .= ' — ' . str_replace(["\r", "\n", '|'], ' ', mb_substr($case['detail'], 0, 220));
    }
    $md[] = sprintf(
        '| %s | %s | %s | %s | %s | %s | %s | %s |',
        $case['rf'],
        $requirements[$case['rf']] ?? '',
        $id,
        $case['tipo'],
        $case['descripcion'],
        $case['esperado'],
        $icon($case['estado']),
        $evidence
    );
}
$md[] = '';

if ($fail > 0) {
    $md[] = '## Fallos';
    $md[] = '';
    foreach ($cases as $id => $case) {
        if ($case['estado'] !== 'FAIL') {
            continue;
        }
        $md[] = '### ' . $id . ' — ' . $case['module'];
        $md[] = '';
        $md[] = '```text';
        $md[] = mb_substr($case['detail'], 0, 4000);
        $md[] = '```';
        $md[] = '';
    }
}

$md[] = '## Entorno de ejecución';
$md[] = '';
$md[] = '- **Aplicación:** `' . (getenv('ACEPTACION_URL') ?: 'http://127.0.0.1:8099/') . '` (servidor PHP embebido de aceptación).';
$md[] = '- **Navegador:** `' . (getenv('ACEPTACION_BROWSER') ?: '/opt/brave-bin/brave') . '` en modo headless.';
$md[] = '- **PHP:** ' . PHP_VERSION . '.';
$md[] = '- **Usuario de pruebas:** `' . (getenv('ACEPTACION_USER') ?: 'e2e_admin') . '` (rol Administrador).';
$md[] = '- **Reporte JUnit:** `tests/aceptacion/_output/report.xml`.';
$md[] = '';
$md[] = '## Notas';
$md[] = '';
$md[] = '- La suite usa las claves de prueba oficiales de Google reCAPTCHA v2 en `tests/aceptacion/server/index.php`; no modifica `.env` ni la aplicación en producción.';
$md[] = '- Las pruebas de aceptación de módulos validan carga real, encabezado, contenido y DataTables por AJAX. Las pruebas CRUD crean datos con prefijo `ACE` y los eliminan al final.';
$md[] = '- Un caso `PASS` exige que el escenario completo haya terminado sin excepciones ni aserciones fallidas.';
$md[] = '- Para regenerar esta matriz: `./tests/aceptacion/run.sh` y luego `php tests/tools/generate_aceptacion.php`.';
$md[] = '';

file_put_contents($outputFile, implode(PHP_EOL, $md) . PHP_EOL);

printf(
    "Matriz generada: %d casos (%d PASS, %d FAIL, %d SKIP, %d sin ejecutar)\n%s\n",
    $total,
    $pass,
    $fail,
    $skip,
    $notRun,
    $outputFile
);
