<#
.SYNOPSIS
    Ejecuta la suite de aceptación (Codeception + WebDriver) de Sys Inescolara.

.DESCRIPTION
    Equivalente PowerShell de run-aceptacion.sh. Levanta, si no están ya
    activos, la aplicación en un servidor PHP embebido y ChromeDriver, prepara
    el usuario de pruebas y ejecuta Codeception. Guarda el JUnit en
    tests\tools\aceptacion\formatos\resultados\report.xml.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\aceptacion\scripts\windows\run-aceptacion.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\aceptacion\scripts\windows\run-aceptacion.ps1 AutenticacionCest

.EXAMPLE
    $env:ACEPTACION_BROWSER = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
    powershell -ExecutionPolicy Bypass -File tests\tools\aceptacion\scripts\windows\run-aceptacion.ps1
#>
param(
    [Parameter(Position = 0)]
    [string]$Cest = ''
)

$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..\..')
Set-Location $root

$aceDir      = Join-Path $root 'tests\aceptacion'
$appPort     = if ($env:ACEPTACION_PORT) { [int]$env:ACEPTACION_PORT } else { 8099 }
$driverPort  = if ($env:ACEPTACION_DRIVER_PORT) { [int]$env:ACEPTACION_DRIVER_PORT } else { 9515 }
$appUrl      = if ($env:ACEPTACION_URL) { $env:ACEPTACION_URL } else { "http://127.0.0.1:$appPort/" }
$browserBin  = if ($env:ACEPTACION_BROWSER) {
    $env:ACEPTACION_BROWSER
} elseif (Test-Path 'C:\Program Files\Google\Chrome\Application\chrome.exe') {
    'C:\Program Files\Google\Chrome\Application\chrome.exe'
} elseif (Test-Path 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe') {
    'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
} else {
    'C:\Program Files\Google\Chrome\Application\chrome.exe'
}
$driverBin   = if ($env:ACEPTACION_DRIVER_BIN) { $env:ACEPTACION_DRIVER_BIN } else { Join-Path $aceDir 'bin\chromedriver.exe' }
$driverVersion = if ($env:ACEPTACION_DRIVER_VERSION) { $env:ACEPTACION_DRIVER_VERSION } else { '156.0.8078.80' }
$resultDir   = Join-Path $root 'tests\tools\aceptacion\formatos\resultados'

$script:appProcess = $null
$script:driverProcess = $null

function Test-PortOpen {
    param([int]$Port, [int]$TimeoutMs = 500)
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $task = $client.ConnectAsync('127.0.0.1', $Port)
        if ($task.Wait($TimeoutMs) -and $client.Connected) { return $true }
        return $false
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Wait-Port {
    param([int]$Port, [int]$TimeoutSeconds = 30)
    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    while ((Get-Date) -lt $deadline) {
        if (Test-PortOpen -Port $Port) { return }
        Start-Sleep -Milliseconds 100
    }
    throw "El puerto $Port no respondió en ${TimeoutSeconds}s."
}

function Stop-BackgroundProcesses {
    foreach ($proc in @($script:appProcess, $script:driverProcess)) {
        if ($null -ne $proc -and -not $proc.HasExited) {
            try {
                Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
                $proc.WaitForExit(5000) | Out-Null
            } catch { }
        }
    }
}

function Install-ChromeDriver {
    if (Test-Path -LiteralPath $driverBin) { return }

    Write-Host ">>> Descargando ChromeDriver $driverVersion..."
    $binDir = Split-Path -Parent $driverBin
    if (-not (Test-Path -LiteralPath $binDir)) {
        New-Item -ItemType Directory -Path $binDir -Force | Out-Null
    }

    $tmp = Join-Path ([IO.Path]::GetTempPath()) ('chromedriver-' + [Guid]::NewGuid().ToString('N'))
    New-Item -ItemType Directory -Path $tmp -Force | Out-Null
    try {
        $zip = Join-Path $tmp 'chromedriver.zip'
        $url = "https://storage.googleapis.com/chrome-for-testing-public/$driverVersion/win64/chromedriver-win64.zip"
        Invoke-WebRequest -Uri $url -OutFile $zip -UseBasicParsing
        Expand-Archive -LiteralPath $zip -DestinationPath $tmp -Force
        Copy-Item -LiteralPath (Join-Path $tmp 'chromedriver-win64\chromedriver.exe') -Destination $driverBin -Force
    } finally {
        Remove-Item -LiteralPath $tmp -Recurse -Force -ErrorAction SilentlyContinue
    }
}

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw 'PHP no está instalado o no está en el PATH.'
}

if (-not (Test-Path -LiteralPath (Join-Path $root 'vendor\bin\codecept.bat'))) {
    throw 'Falta Codeception. Ejecute: composer install'
}

if (-not (Test-Path -LiteralPath $browserBin)) {
    throw "No se encontró el navegador $browserBin (use la variable ACEPTACION_BROWSER)."
}

if (-not (Test-Path -LiteralPath $resultDir)) {
    New-Item -ItemType Directory -Path $resultDir -Force | Out-Null
}

Install-ChromeDriver

try {
    if (Test-PortOpen -Port $appPort) {
        Write-Host ">>> Servidor de aplicación ya activo en el puerto $appPort"
    } else {
        Write-Host ">>> Levantando aplicación en $appUrl"
        $env:ACEPTACION_URL = $appUrl
        $script:appProcess = Start-Process -FilePath 'php' `
            -ArgumentList @('-S', "127.0.0.1:$appPort", '-t', $root, (Join-Path $aceDir 'server\router.php')) `
            -RedirectStandardOutput (Join-Path $resultDir 'php-server.log') `
            -RedirectStandardError (Join-Path $resultDir 'php-server.err.log') `
            -WindowStyle Hidden -PassThru
        Wait-Port -Port $appPort -TimeoutSeconds 30
    }

    if (Test-PortOpen -Port $driverPort) {
        Write-Host ">>> ChromeDriver ya activo en el puerto $driverPort"
    } else {
        Write-Host ">>> Levantando ChromeDriver en el puerto $driverPort"
        $script:driverProcess = Start-Process -FilePath $driverBin `
            -ArgumentList @("--port=$driverPort", "--log-path=$(Join-Path $resultDir 'chromedriver.log')") `
            -RedirectStandardOutput (Join-Path $resultDir 'chromedriver.out') `
            -RedirectStandardError (Join-Path $resultDir 'chromedriver.err.log') `
            -WindowStyle Hidden -PassThru
        Wait-Port -Port $driverPort -TimeoutSeconds 30
    }

    Write-Host '>>> Preparando usuario de pruebas (e2e_admin)'
    & php 'tests\tools\interfaz\scripts\lib\seed-interfaz.php'
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo preparar el usuario de pruebas.'
    }

    Write-Host '>>> Ejecutando Codeception'
    $env:ACEPTACION_URL = $appUrl
    $env:ACEPTACION_BROWSER = $browserBin
    $env:ACEPTACION_DRIVER_PORT = "$driverPort"

    $codecept = Join-Path $root 'vendor\bin\codecept.bat'
    $codeceptArgs = @('-c', 'tests/aceptacion', 'run')
    if ($Cest -ne '') {
        # Codeception 5 recibe primero la suite y luego el test. El argumento
        # del script es un archivo/Cest, salvo que coincida con una suite.
        $suiteFile = Join-Path $aceDir "$Cest.suite.yml"
        $suiteDir  = Join-Path $aceDir $Cest
        if ((Test-Path -LiteralPath $suiteFile) -or (Test-Path -LiteralPath $suiteDir -PathType Container)) {
            $codeceptArgs += $Cest
        } else {
            $codeceptArgs += @('Acceptance', $Cest)
        }
    }
    $codeceptArgs += @('--xml', '--no-interaction')

    & $codecept @codeceptArgs
    $status = $LASTEXITCODE

    Write-Host ">>> Reporte JUnit: $resultDir\report.xml"
    exit $status
} finally {
    Stop-BackgroundProcesses
}
