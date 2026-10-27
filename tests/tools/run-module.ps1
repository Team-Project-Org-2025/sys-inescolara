<#
.SYNOPSIS
    Ejecuta las tres suites de un solo modulo y compara el resultado con la
    matriz de trazabilidad (tests/traceability.json).

.DESCRIPTION
    Uso:
        powershell -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 Planta
        powershell -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 01
        powershell -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 "Cuenta por cobrar"

    Genera:
        tests\results\module-<Modelo>-unit.xml
        tests\results\module-<Modelo>-integration.xml
        tests\results\module-<Modelo>-smoke.xml

    Y muestra una tabla con lo ejecutado vs. lo esperado. Sale con codigo 1 si
    algo no coincide.
#>
param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$Module
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

$entry = $data.modulos | Where-Object {
    $_.modelo -ieq $normalized -or
    $_.moduloSidebar -ieq $normalized -or
    $_.idRequisito -ieq $normalized -or
    ('{0:00}' -f [int]$_.numero) -eq $normalized -or
    [string]$_.numero -eq $normalized
} | Select-Object -First 1

if ($null -eq $entry) {
    throw "No se encontro el modulo '$Module'. Usa el nombre del modelo (Planta), el numero (01) o el nombre del sidebar."
}

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
        [string]$LogName
    )

    $logPath = Join-Path $root "tests\results\$LogName"
    if (Test-Path $logPath) {
        Remove-Item $logPath -Force
    }

    if ($TestPath) {
        Write-Host ""
        Write-Host ">>> $Suite :: $TestPath" -ForegroundColor Cyan

        & php 'vendor\phpunit\phpunit\phpunit' `
            -c 'tests\phpunit.xml' `
            $TestPath `
            --log-junit $logPath `
            --do-not-cache-result
    } else {
        Write-Host ""
        Write-Host ">>> $Suite :: --filter $Filter" -ForegroundColor Cyan

        & php 'vendor\phpunit\phpunit\phpunit' `
            -c 'tests\phpunit.xml' `
            --testsuite $Suite `
            --filter $Filter `
            --log-junit $logPath `
            --do-not-cache-result
    }

    $exitCode = $LASTEXITCODE

    if (-not (Test-Path $logPath)) {
        throw "PHPUnit no genero el log $logPath"
    }

    $xml = [xml](Get-Content $logPath -Raw)
    $node = $xml.testsuites.testsuite
    if ($node -is [array]) {
        $node = $node[0]
    }

    return [pscustomobject]@{
        Suite      = $Suite
        Filter     = if ($TestPath) { $TestPath } else { $Filter }
        ExitCode   = $exitCode
        Tests      = [int]$node.tests
        Assertions = [int]$node.assertions
        Failures   = [int]$node.failures
        Errors     = [int]$node.errors
        Skipped    = [int]$node.skipped
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
        -and $Actual.Failures -eq [int]$Esperado.fail `
        -and $Actual.Errors -eq 0 `
        -and $Actual.Skipped -eq [int]$Esperado.skip

    $estado = if ($ok) { 'COINCIDE' } else { 'NO COINCIDE' }
    $color = if ($ok) { 'Green' } else { 'Red' }

    [pscustomobject]@{
        Suite      = $Nombre
        Ejecutadas = $Actual.Tests
        Esperadas  = [int]$Esperado.pruebas
        Pass       = [int]$Esperado.pass
        Fail       = $Actual.Failures
        Skip       = $Actual.Skipped
        Aserciones = $Actual.Assertions
        Estado     = $estado
        Color      = $color
        Log        = $Actual.Log
    }
}

$unitCase = $entry.casos[0]
$integrationCase = $entry.casos[1]
$smokeCase = $entry.casos[2]

$unitPath = Get-TestPath $unitCase.evidencia
$integrationPath = Get-TestPath $integrationCase.evidencia
$smokeId = [regex]::Match($smokeCase.evidencia, 'CP-SMOKE-\d{2}').Value

if ([string]::IsNullOrWhiteSpace($smokeId)) {
    throw "No se pudo extraer el ID de humo desde: $($smokeCase.evidencia)"
}

$unitResult = Invoke-Suite -Suite 'Unit' -TestPath $unitPath -LogName "module-$($entry.modelo)-unit.xml"
$integrationResult = Invoke-Suite -Suite 'Integration' -TestPath $integrationPath -LogName "module-$($entry.modelo)-integration.xml"
$smokeResult = Invoke-Suite -Suite 'Smoke' -Filter $smokeId -LogName "module-$($entry.modelo)-smoke.xml"

$rows = @(
    Test-SuiteResult -Nombre 'Unitaria' -Actual $unitResult -Esperado $unitCase
    Test-SuiteResult -Nombre 'Integracion' -Actual $integrationResult -Esperado $integrationCase
    Test-SuiteResult -Nombre 'Humo' -Actual $smokeResult -Esperado $smokeCase
)

Write-Host ""
Write-Host "==================================================================" -ForegroundColor Yellow
Write-Host ("Modulo: {0} ({1}) - {2}" -f $entry.modelo, $entry.idRequisito, $entry.moduloSidebar) -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Yellow

$rows | Format-Table Suite, Ejecutadas, Esperadas, Pass, Fail, Skip, Aserciones, Estado -AutoSize

foreach ($row in $rows) {
    Write-Host ("{0}: {1}" -f $row.Suite, $row.Estado) -ForegroundColor $row.Color
    Write-Host ("  Log: {0}" -f $row.Log) -ForegroundColor DarkGray
}

$allOk = ($rows | Where-Object { $_.Estado -ne 'COINCIDE' }).Count -eq 0

Write-Host ""
if ($allOk) {
    Write-Host "RESULTADO: COINCIDE con tests/traceability.json" -ForegroundColor Green
    exit 0
}

Write-Host "RESULTADO: NO COINCIDE con tests/traceability.json" -ForegroundColor Red
exit 1
