@echo off
rem Prueba BETA independiente: API con Postman.
rem Equivale a: run-beta.bat --test=api
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-beta.bat" --test=api %*
exit /b %ERRORLEVEL%
