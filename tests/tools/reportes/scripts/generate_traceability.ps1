<#
.SYNOPSIS
    Regenera la matriz de trazabilidad (TRACEABILITY.md y traceability.json).

.DESCRIPTION
    Envoltura para Windows de tests\tools\reportes\scripts\lib\generate_traceability.php.
    Lee los JUnit de tests\tools\phpunit\formatos\resultados\ y escribe la
    matriz en tests\tools\reportes\formatos\resultados\.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\reportes\scripts\generate_traceability.ps1
#>
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Arguments
)

$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..')
Set-Location $root

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] No se encontró el ejecutable 'php' en el PATH." -ForegroundColor Red
    exit 3
}

$runner = Join-Path $PSScriptRoot 'lib\generate_traceability.php'
if (-not (Test-Path -LiteralPath $runner)) {
    Write-Host "[ERROR] No se encontró el generador: $runner" -ForegroundColor Red
    exit 3
}

& php $runner @Arguments
exit $LASTEXITCODE
