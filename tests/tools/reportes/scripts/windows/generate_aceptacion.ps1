<#
.SYNOPSIS
    Regenera la matriz de aceptación (ACEPTACION.md).

.DESCRIPTION
    Envoltura para Windows de tests\tools\reportes\scripts\lib\generate_aceptacion.php.
    Lee el JUnit de Codeception en
    tests\tools\aceptacion\formatos\resultados\report.xml y escribe la matriz
    en tests\tools\reportes\formatos\resultados\.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\reportes\scripts\windows\generate_aceptacion.ps1
#>
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Arguments
)

$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..\..')
Set-Location $root

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] No se encontró el ejecutable 'php' en el PATH." -ForegroundColor Red
    exit 3
}

$runner = Join-Path $PSScriptRoot '..\lib\generate_aceptacion.php'
if (-not (Test-Path -LiteralPath $runner)) {
    Write-Host "[ERROR] No se encontró el generador: $runner" -ForegroundColor Red
    exit 3
}

& php $runner @Arguments
exit $LASTEXITCODE
