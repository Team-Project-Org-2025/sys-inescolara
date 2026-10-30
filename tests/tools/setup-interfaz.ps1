<#
.SYNOPSIS
    Prepara el entorno de las pruebas de interfaz (Selenium + ChromeDriver).

.DESCRIPTION
    Verifica PHP, PHPUnit y php-webdriver, comprueba ChromeDriver, crea la
    semilla del usuario de prueba y prepara las carpetas de resultados.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\setup-interfaz.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\setup-interfaz.ps1 -SkipSeed
#>
param(
    [switch]$SkipSeed,
    [switch]$Install
)

$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $root

Write-Host '==> Verificando PHP' -ForegroundColor Cyan
& php -v | Select-Object -First 1
if ($LASTEXITCODE -ne 0) { throw 'PHP no está disponible en PATH.' }

Write-Host '==> Verificando dependencias de Composer' -ForegroundColor Cyan
if ($Install -or -not (Test-Path 'vendor\php-webdriver\webdriver')) {
    & composer install --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'No se pudieron instalar las dependencias de Composer.' }
}

if (-not (Test-Path 'vendor\phpunit\phpunit\phpunit')) {
    throw 'PHPUnit no está instalado. Ejecute: composer install --dev'
}

if (-not (Test-Path 'vendor\php-webdriver\webdriver')) {
    throw 'php-webdriver no está instalado. Ejecute: composer require --dev php-webdriver/webdriver'
}

Write-Host '==> Verificando ChromeDriver' -ForegroundColor Cyan
$driver = Join-Path $root 'tests\Interfaz\bin\chromedriver.exe'
if (-not (Test-Path $driver)) {
    throw "No se encontró ChromeDriver en $driver. Descargue la versión compatible con su Chrome en https://googlechromelabs.github.io/chrome-for-testing/ y colóquela en tests\Interfaz\bin."
}
& $driver --version

Write-Host '==> Preparando carpetas de resultados' -ForegroundColor Cyan
foreach ($dir in @('tests\Interfaz\results', 'tests\Interfaz\results\screenshots', 'tests\Interfaz\results\downloads')) {
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
}

if (-not $SkipSeed) {
    Write-Host '==> Creando/actualizando usuario de prueba' -ForegroundColor Cyan
    & php 'tests\tools\seed-interfaz.php'
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el usuario de prueba.' }
}

Write-Host '==> Comprobando la aplicación web' -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri 'http://localhost/sys-inescolara/login' -UseBasicParsing -TimeoutSec 15
    Write-Host "Aplicación disponible (HTTP $($response.StatusCode))." -ForegroundColor Green
} catch {
    Write-Warning 'No se pudo acceder a http://localhost/sys-inescolara/login. Inicie Apache y MySQL en XAMPP antes de ejecutar las pruebas.'
}

Write-Host ''
Write-Host 'Entorno de interfaz listo.' -ForegroundColor Green
Write-Host 'Ejecute: powershell -ExecutionPolicy Bypass -File tests\tools\run-interfaz.ps1'
