<#
.SYNOPSIS
    Ejecuta las pruebas de uno o de los 31 modulos y compara contra la matriz.

.DESCRIPTION
    Un modulo:
        powershell -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 Planta
        powershell -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 01
        powershell -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 "Cuenta por cobrar"

    Todos los modulos (31):
        powershell -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 all

    Para cada modulo ejecuta sus tres suites: unitaria, integracion y humo.
    Guarda los JUnit en tests\results\module-<Modelo>-{unit,integration,smoke}.xml
    y compara los conteos contra tests\traceability.json.

    Sale con codigo 0 si todo coincide y 1 si algo difiere.
#>
param(
    [Parameter(Position = 0)]
    [string]$Module = 'all'
)

$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $root

$jsonPath = Join-Path $root 'tests\traceability.json'
if (-not (Test-Path $jsonPath)) {
    throw "No existe $jsonPath. Ejecuta primero: php tests\tools\generate_traceability.php"
}

$data = Get-Content $jsonPath -Raw | ConvertFrom-Json
$normalized = $Module.Trim()
$runAll = $normalized -in @('all', 'todos', 'todo', '*', '-all')

function Get-TestPath([string]$Fqcn) {
    $prefix = 'SysInescolara\Tests\'
    $relative = $Fqcn
    if ($Fqcn.StartsWith($prefix)) {
        $relative = $Fqcn.Substring($prefix.Length)
    }
    return "tests\$relative.php"
}

function Invoke-Suite {
    param(
        [string]$Suite,
        [string]$TestPath,
        [string]$Filter,
        [string]$LogName,
        [switch]$Quiet
    )

    $logPath = Join-Path $root "tests\results\$LogName"
    if (Test-Path $logPath) {
        Remove-Item $logPath -Force
    }

    # PHP escribe los error_log() en stderr. Con ErrorActionPreference=Stop,
    # PowerShell 5.1 convertiria esa salida en un error terminante. Se captura
    # como texto y se decide despues segun el codigo de salida de PHPUnit.
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    if ($TestPath) {
        $output = & php 'vendor\phpunit\phpunit\phpunit' `
            -c 'tests\phpunit.xml' `
            $TestPath `
            --log-junit $logPath `
            --do-not-cache-result 2>&1
    } else {
        $output = & php 'vendor\phpunit\phpunit\phpunit' `
            -c 'tests\phpunit.xml' `
            --testsuite $Suite `
            --filter $Filter `
            --log-junit $logPath `
            --do-not-cache-result 2>&1
    }

    $exitCode = $LASTEXITCODE
    $ErrorActionPreference = $previousPreference

    if (-not $Quiet -or $exitCode -ne 0) {
        foreach ($line in $output) {
            Write-Host $line
        }
    }

    if (-not (Test-Path $logPath)) {
        throw "PHPUnit no genero el log $logPath"
    }

    $xml = [xml](Get-Content $logPath -Raw)
    $node = $xml.testsuites.testsuite
    if ($node -is [array]) {
        $node = $node[0]
    }

    $tests = [int]$node.tests
    $failures = [int]$node.failures
    $errors = [int]$node.errors
    $skipped = [int]$node.skipped

    return [pscustomobject]@{
        Suite      = $Suite
        Filter     = if ($TestPath) { $TestPath } else { $Filter }
        ExitCode   = $exitCode
        Tests      = $tests
        Assertions = [int]$node.assertions
        Failures   = $failures
        Errors     = $errors
        Skipped    = $skipped
        Pass       = $tests - $failures - $errors - $skipped
        Log        = $logPath
    }
}

function Test-SuiteResult {
    param(
        [string]$Nombre,
        [pscustomobject]$Actual,
        [pscustomobject]$Esperado
    )

    $ok = $Actual.ExitCode -eq 0 `
        -and $Actual.Tests -eq [int]$Esperado.pruebas `
        -and $Actual.Pass -eq [int]$Esperado.pass `
        -and $Actual.Failures -eq [int]$Esperado.fail `
        -and $Actual.Errors -eq 0 `
        -and $Actual.Skipped -eq [int]$Esperado.skip

    [pscustomobject]@{
        Suite      = $Nombre
        Ejecutadas = $Actual.Tests
        Esperadas  = [int]$Esperado.pruebas
        Pass       = $Actual.Pass
        EsperadasPass = [int]$Esperado.pass
        Fail       = $Actual.Failures
        Skip       = $Actual.Skipped
        Aserciones = $Actual.Assertions
        Estado     = if ($ok) { 'COINCIDE' } else { 'NO COINCIDE' }
        Ok         = $ok
        Log        = $Actual.Log
    }
}

function Invoke-ModuleCheck {
    param(
        [pscustomobject]$Entry,
        [switch]$Quiet
    )

    $unitCase = $Entry.casos[0]
    $integrationCase = $Entry.casos[1]
    $smokeCase = $Entry.casos[2]

    $unitPath = Get-TestPath $unitCase.evidencia
    $integrationPath = Get-TestPath $integrationCase.evidencia
    $smokeId = [regex]::Match($smokeCase.evidencia, 'CP-SMOKE-\d{2}').Value

    if ([string]::IsNullOrWhiteSpace($smokeId)) {
        throw "No se pudo extraer el ID de humo desde: $($smokeCase.evidencia)"
    }

    if (-not $Quiet) {
        Write-Host ""
        Write-Host ">>> $($Entry.modelo) :: $unitPath" -ForegroundColor Cyan
    }

    $unitResult = Invoke-Suite -Suite 'Unit' -TestPath $unitPath -LogName "module-$($Entry.modelo)-unit.xml" -Quiet:$Quiet

    if (-not $Quiet) {
        Write-Host ">>> $($Entry.modelo) :: $integrationPath" -ForegroundColor Cyan
    }

    $integrationResult = Invoke-Suite -Suite 'Integration' -TestPath $integrationPath -LogName "module-$($Entry.modelo)-integration.xml" -Quiet:$Quiet

    if (-not $Quiet) {
        Write-Host ">>> $($Entry.modelo) :: --filter $smokeId" -ForegroundColor Cyan
    }

    $smokeResult = Invoke-Suite -Suite 'Smoke' -Filter $smokeId -LogName "module-$($Entry.modelo)-smoke.xml" -Quiet:$Quiet

    $rows = @(
        Test-SuiteResult -Nombre 'Unitaria' -Actual $unitResult -Esperado $unitCase
        Test-SuiteResult -Nombre 'Integracion' -Actual $integrationResult -Esperado $integrationCase
        Test-SuiteResult -Nombre 'Humo' -Actual $smokeResult -Esperado $smokeCase
    )

    $ok = ($rows | Where-Object { -not $_.Ok }).Count -eq 0

    if (-not $Quiet) {
        Write-Host ""
        Write-Host "==================================================================" -ForegroundColor Yellow
        Write-Host ("Modulo: {0} ({1}) - {2}" -f $Entry.modelo, $Entry.idRequisito, $Entry.moduloSidebar) -ForegroundColor Yellow
        Write-Host "==================================================================" -ForegroundColor Yellow

        $rows | Format-Table Suite, Ejecutadas, Esperadas, Pass, Fail, Skip, Aserciones, Estado -AutoSize | Out-Host

        foreach ($row in $rows) {
            Write-Host ("{0}: {1}" -f $row.Suite, $row.Estado) -ForegroundColor $(if ($row.Ok) { 'Green' } else { 'Red' })
            Write-Host ("  Log: {0}" -f $row.Log) -ForegroundColor DarkGray
        }
    }

    return [pscustomobject]@{
        Numero      = [int]$Entry.numero
        Modelo      = $Entry.modelo
        Unit        = $rows[0]
        Integration = $rows[1]
        Smoke       = $rows[2]
        Ok          = $ok
    }
}

function Format-Cell([pscustomobject]$Row) {
    $text = "{0}/{1}" -f $Row.Pass, $Row.Ejecutadas
    if ($Row.Skip -gt 0) {
        $text += " ({0} skip)" -f $Row.Skip
    }
    return $text
}

$entries = if ($runAll) {
    @($data.modulos)
} else {
    $entry = $data.modulos | Where-Object {
        $_.modelo -ieq $normalized -or
        $_.moduloSidebar -ieq $normalized -or
        $_.idRequisito -ieq $normalized -or
        ('{0:00}' -f [int]$_.numero) -eq $normalized -or
        [string]$_.numero -eq $normalized
    } | Select-Object -First 1

    if ($null -eq $entry) {
        throw "No se encontro el modulo '$Module'. Usa el nombre del modelo (Planta), el numero (01), el nombre del sidebar o 'all' para los 31."
    }

    @($entry)
}

$started = Get-Date
$results = @()

if ($runAll) {
    Write-Host ""
    Write-Host "Ejecutando los 31 modulos (Unit + Integration + Smoke)..." -ForegroundColor Yellow
    Write-Host "Directorio: $root" -ForegroundColor DarkGray
}

foreach ($item in $entries) {
    $check = Invoke-ModuleCheck -Entry $item -Quiet:$runAll
    $results += $check

    if ($runAll) {
        $estado = if ($check.Ok) { 'COINCIDE' } else { 'NO COINCIDE' }
        $color = if ($check.Ok) { 'Green' } else { 'Red' }
        Write-Host ("[{0:00}/{1:00}] {2,-16} {3}" -f $check.Numero, $entries.Count, $check.Modelo, $estado) -ForegroundColor $color
    }
}

$failed = @($results | Where-Object { -not $_.Ok })
$elapsed = (Get-Date) - $started

Write-Host ""
Write-Host "==================================================================" -ForegroundColor Yellow
Write-Host "RESUMEN" -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Yellow

$summary = $results | ForEach-Object {
    [pscustomobject]@{
        '#'            = '{0:00}' -f $_.Numero
        Modelo         = $_.Modelo
        Unitaria       = Format-Cell $_.Unit
        Integracion    = Format-Cell $_.Integration
        Humo           = Format-Cell $_.Smoke
        Estado         = if ($_.Ok) { 'COINCIDE' } else { 'NO COINCIDE' }
    }
}

$summary | Format-Table -AutoSize | Out-Host

$totalTests = ($results | ForEach-Object { $_.Unit.Ejecutadas + $_.Integration.Ejecutadas + $_.Smoke.Ejecutadas } | Measure-Object -Sum).Sum
$totalAssertions = ($results | ForEach-Object { $_.Unit.Aserciones + $_.Integration.Aserciones + $_.Smoke.Aserciones } | Measure-Object -Sum).Sum
$totalSkips = ($results | ForEach-Object { $_.Unit.Skip + $_.Integration.Skip + $_.Smoke.Skip } | Measure-Object -Sum).Sum
$totalFails = ($results | ForEach-Object { $_.Unit.Fail + $_.Integration.Fail + $_.Smoke.Fail } | Measure-Object -Sum).Sum

Write-Host ("Modulos: {0} | COINCIDE: {1} | NO COINCIDE: {2}" -f $results.Count, ($results.Count - $failed.Count), $failed.Count)
Write-Host ("Pruebas: {0} | Aserciones: {1} | FAIL: {2} | SKIP: {3}" -f $totalTests, $totalAssertions, $totalFails, $totalSkips)
Write-Host ("Tiempo: {0:n1} s" -f $elapsed.TotalSeconds)
Write-Host ("XML por modulo: {0}" -f (Join-Path $root 'tests\results'))

Write-Host ""
if ($failed.Count -eq 0) {
    Write-Host "RESULTADO: COINCIDE con tests/traceability.json" -ForegroundColor Green
    exit 0
}

Write-Host "RESULTADO: NO COINCIDE con tests/traceability.json" -ForegroundColor Red
foreach ($item in $failed) {
    Write-Host ("  - {0:00} {1}" -f $item.Numero, $item.Modelo) -ForegroundColor Red
}
exit 1
