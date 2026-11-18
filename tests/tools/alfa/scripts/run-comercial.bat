@echo off
rem Prueba ALFA independiente: Comercial.
rem Equivale a: run-alfa.bat --test=comercial
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=comercial %*
exit /b %ERRORLEVEL%
