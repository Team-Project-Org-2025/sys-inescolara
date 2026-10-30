<#
.SYNOPSIS
    Ejecuta la suite de pruebas de interfaz con Selenium.

.DESCRIPTION
    Requiere Apache, MySQL y ChromeDriver disponibles. Si no se indica
    -Suite, ejecuta todas las suites. Guarda el JUnit en
    tests\Interfaz\results\junit-<suite>.xml.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\run-interfaz.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\run-interfaz.ps1 -Suite Inventario

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\run-interfaz.ps1 -Filter VentasTest -Headless
#>
param(
    [string]$Suite = 'all',
    [string]$Filter = '',
    [switch]$Headless,
    [switch]$SkipSeed
)

$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $root

if ($Headless) {
    $env:INTERFAZ_HEADLESS = '1'
}

if (-not $SkipSeed) {
    & php 'tests\tools\seed-interfaz.php'
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar el usuario de prueba.' }
}

$resultsDir = Join-Path $root 'tests\Interfaz\results'
if (-not (Test-Path $resultsDir)) { New-Item -ItemType Directory -Path $resultsDir -Force | Out-Null }

$logName = if ($Filter -ne '') { 'filter-' + ($Filter -replace '[^A-Za-z0-9_-]', '_') } else { $Suite.ToLower() }
$logPath = Join-Path $resultsDir "junit-$logName.xml"

$arguments = @(
    '-c', 'tests\Interfaz\phpunit.xml',
    '--do-not-cache-result',
    '--log-junit', $logPath
)

if ($Filter -ne '') {
    $arguments += @('--filter', $Filter)
} elseif ($Suite -ne 'all') {
    $arguments += @('--testsuite', $Suite)
}

Write-Host "==> Ejecutando pruebas de interfaz ($logName)" -ForegroundColor Cyan
& php 'vendor\phpunit\phpunit\phpunit' @arguments
$code = $LASTEXITCODE

Write-Host ''
Write-Host "Resultados: $logPath" -ForegroundColor Cyan
Write-Host "Capturas:   tests\Interfaz\results\screenshots" -ForegroundColor Cyan
exit $code
