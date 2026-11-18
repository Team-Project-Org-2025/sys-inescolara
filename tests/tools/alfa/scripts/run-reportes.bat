@echo off
rem Prueba ALFA independiente: Reportes.
rem Equivale a: run-alfa.bat --test=reportes
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=reportes %*
exit /b %ERRORLEVEL%
