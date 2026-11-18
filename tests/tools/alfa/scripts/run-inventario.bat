@echo off
rem Prueba ALFA independiente: Inventario.
rem Equivale a: run-alfa.bat --test=inventario
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=inventario %*
exit /b %ERRORLEVEL%
