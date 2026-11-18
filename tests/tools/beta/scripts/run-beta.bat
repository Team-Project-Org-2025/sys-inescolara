@echo off
rem =====================================================================
rem  Pruebas BETA - Sys Inescolara
rem  Envoltura para Windows de tests\tools\beta\scripts\run-beta.php
rem
rem  Uso:
rem    tests\tools\beta\scripts\run-beta.bat
rem    tests\tools\beta\scripts\run-beta.bat humo --base-url=https://mi-app.onrender.com
rem    tests\tools\beta\scripts\run-beta.bat api --usuario=admin
rem    tests\tools\beta\scripts\run-beta.bat checklist
rem =====================================================================
setlocal EnableExtensions

set "SCRIPT_DIR=%~dp0"
set "ROOT_DIR=%SCRIPT_DIR%..\..\..\..\"
set "RUNNER=%SCRIPT_DIR%run-beta.php"

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
