@echo off
rem Prueba ALFA independiente: Operaciones.
rem Equivale a: run-alfa.bat --test=operaciones
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=operaciones %*
exit /b %ERRORLEVEL%
