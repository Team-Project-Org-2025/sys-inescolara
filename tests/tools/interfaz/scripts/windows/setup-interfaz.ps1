<#
.SYNOPSIS
    Prepara el entorno de las pruebas de interfaz (Selenium + ChromeDriver).

.DESCRIPTION
    Verifica PHP, PHPUnit y php-webdriver, comprueba ChromeDriver, crea la
    semilla del usuario de prueba y prepara las carpetas de resultados.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 -SkipSeed

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 -TestRecaptcha
#>
param(
    [switch]$SkipSeed,
    [switch]$Install,
    [switch]$TestRecaptcha,
    [switch]$RestoreRecaptcha
)

$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..\..')
Set-Location $root

$envFile = Join-Path $root '.env'
$envBackup = Join-Path $root 'tests\Interfaz\.env.interfaz.bak'
$recaptchaTestSite = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'
$recaptchaTestSecret = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'

function Set-EnvValue {
    param([string]$Path, [string]$Key, [string]$Value)

    $lines = @(Get-Content -LiteralPath $Path -Encoding UTF8)
    $found = $false
    $out = @(foreach ($line in $lines) {
        if ($line -match "^$([regex]::Escape($Key))=") {
            $found = $true
            "$Key=$Value"
        } else {
            $line
        }
    })
    if (-not $found) { $out += "$Key=$Value" }
    # UTF-8 sin BOM: el cargador de .env no espera marca al inicio del archivo.
    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllLines($Path, [string[]]$out, $encoding)
}

if ($RestoreRecaptcha) {
    if (Test-Path $envBackup) {
        Copy-Item -LiteralPath $envBackup -Destination $envFile -Force
        Remove-Item -LiteralPath $envBackup -Force
        Write-Host 'Claves de reCAPTCHA originales restauradas desde el respaldo.' -ForegroundColor Green
    } else {
        Write-Warning 'No hay respaldo de reCAPTCHA que restaurar.'
    }
}

if ($TestRecaptcha) {
    if (-not (Test-Path $envFile)) {
        throw 'No existe .env; no se pudieron aplicar las claves de prueba de reCAPTCHA.'
    }
    if (-not (Test-Path $envBackup)) {
        Copy-Item -LiteralPath $envFile -Destination $envBackup -Force
    }
    Set-EnvValue -Path $envFile -Key 'RECAPTCHA_SITE_KEY' -Value $recaptchaTestSite
    Set-EnvValue -Path $envFile -Key 'RECAPTCHA_SECRET_KEY' -Value $recaptchaTestSecret
    Write-Host "Claves de prueba de reCAPTCHA aplicadas. Respaldo: $envBackup" -ForegroundColor Green
}

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
$driver = if ($env:INTERFAZ_DRIVER) { $env:INTERFAZ_DRIVER } else { Join-Path $root 'tests\Interfaz\bin\chromedriver.exe' }
if (-not (Test-Path $driver)) {
    Write-Host "     No se encontró $driver; descargando la versión compatible…"
    & php 'tests\tools\interfaz\scripts\lib\install-chromedriver.php' "--output=$driver"
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $driver)) {
        throw "No se pudo instalar ChromeDriver automáticamente. Descárguelo desde https://googlechromelabs.github.io/chrome-for-testing/ y colóquelo en $driver."
    }
}
& $driver --version

Write-Host '==> Preparando carpetas de resultados' -ForegroundColor Cyan
foreach ($dir in @('tests\tools\interfaz\formatos\resultados', 'tests\tools\interfaz\formatos\resultados\screenshots', 'tests\tools\interfaz\formatos\resultados\downloads')) {
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
}

if (-not $SkipSeed) {
    Write-Host '==> Creando/actualizando usuario de prueba' -ForegroundColor Cyan
    & php 'tests\tools\interfaz\scripts\lib\seed-interfaz.php'
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el usuario de prueba.' }
}

Write-Host '==> Comprobando reCAPTCHA para las pruebas' -ForegroundColor Cyan
$secret = ''
if (Test-Path $envFile) {
    $line = Select-String -Path $envFile -Pattern '^RECAPTCHA_SECRET_KEY=' | Select-Object -Last 1
    if ($line) {
        $secret = ($line.Line -replace '^RECAPTCHA_SECRET_KEY=', '').Trim().Trim([char[]]@('"', "'"))
    }
}
if ($secret -and $secret -ne $recaptchaTestSecret) {
    Write-Warning 'El .env usa una clave reCAPTCHA real; las pruebas de login y de recuperación de contraseña no pueden resolver el reto.'
    Write-Host '       Para la suite E2E usa las claves de prueba de Google en el .env:' -ForegroundColor Yellow
    Write-Host "         RECAPTCHA_SITE_KEY=$recaptchaTestSite" -ForegroundColor Yellow
    Write-Host "         RECAPTCHA_SECRET_KEY=$recaptchaTestSecret" -ForegroundColor Yellow
    Write-Host '       O ejecuta este script con -TestRecaptcha (reversible con -RestoreRecaptcha).' -ForegroundColor Yellow
} else {
    Write-Host 'Claves de reCAPTCHA compatibles con la automatización.' -ForegroundColor Green
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
Write-Host 'Ejecute: powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\run-interfaz.ps1'
