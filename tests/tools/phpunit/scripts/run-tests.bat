@echo off
rem =====================================================================
rem  Ejecutor multiplataforma de pruebas - Sys Inescolara
rem  Envoltura para Windows de tests\tools\phpunit\scripts\run-tests.php
rem
rem  Uso:
rem    tests\tools\phpunit\scripts\run-tests.bat
rem    tests\tools\phpunit\scripts\run-tests.bat --suite=integration
rem    tests\tools\phpunit\scripts\run-tests.bat --suite=smoke
rem    tests\tools\phpunit\scripts\run-tests.bat --module=13
rem =====================================================================
setlocal EnableExtensions

set "SCRIPT_DIR=%~dp0"
set "ROOT_DIR=%SCRIPT_DIR%..\..\..\..\"
set "RUNNER=%SCRIPT_DIR%run-tests.php"

where php >nul 2>nul
if errorlevel 1 (
    echo [ERROR] No se encontro el ejecutable 'php' en el PATH.>&2
    echo         Instale PHP 8.2+ y vuelva a intentarlo.>&2
    exit /b 3
)

if not exist "%RUNNER%" (
    echo [ERROR] No se encontro el ejecutor: %RUNNER%>&2
    exit /b 3
)

pushd "%ROOT_DIR%"
php "%RUNNER%" %*
set "EXIT_CODE=%ERRORLEVEL%"
popd

exit /b %EXIT_CODE%
