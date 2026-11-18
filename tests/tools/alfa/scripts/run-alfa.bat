@echo off
rem =====================================================================
rem  Pruebas ALFA - Sys Inescolara
rem  Envoltura para Windows de tests\tools\alfa\scripts\run-alfa.php
rem
rem  Uso:
rem    tests\tools\alfa\scripts\run-alfa.bat
rem    tests\tools\alfa\scripts\run-alfa.bat core
rem    tests\tools\alfa\scripts\run-alfa.bat auth
rem    tests\tools\alfa\scripts\run-alfa.bat --manual
rem =====================================================================
setlocal EnableExtensions

set "SCRIPT_DIR=%~dp0"
set "ROOT_DIR=%SCRIPT_DIR%..\..\..\..\"
set "RUNNER=%SCRIPT_DIR%run-alfa.php"

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
