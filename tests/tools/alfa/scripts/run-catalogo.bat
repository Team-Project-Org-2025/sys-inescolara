@echo off
rem Prueba ALFA independiente: Catálogo.
rem Equivale a: run-alfa.bat --test=catalogo
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=catalogo %*
exit /b %ERRORLEVEL%
