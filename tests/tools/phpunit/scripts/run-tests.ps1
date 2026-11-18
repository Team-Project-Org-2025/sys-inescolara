<#
.SYNOPSIS
    Ejecuta las suites de integración y humo de todos los módulos o de uno solo.

.DESCRIPTION
    Envoltura para Windows de tests\tools\phpunit\scripts\lib\run-tests.php.
    Solo comprueba que PHP y el motor existan y reenvía todos los argumentos.

    No ejecuta la suite Unit. Para las tres suites de un módulo usa
    run-module.ps1.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-tests.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-tests.ps1 --suite=smoke --module=13

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-tests.ps1 --list
#>
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Arguments
)

$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..')
Set-Location $root

$php = Get-Command php -ErrorAction SilentlyContinue
if ($null -eq $php) {
    Write-Host "[ERROR] No se encontró el ejecutable 'php' en el PATH." -ForegroundColor Red
    Write-Host '        Instale PHP 8.2+ y vuelva a intentarlo.' -ForegroundColor Red
    exit 3
}

$runner = Join-Path $PSScriptRoot 'lib\run-tests.php'
if (-not (Test-Path -LiteralPath $runner)) {
    Write-Host "[ERROR] No se encontró el ejecutor: $runner" -ForegroundColor Red
    exit 3
}

& php $runner @Arguments
exit $LASTEXITCODE
