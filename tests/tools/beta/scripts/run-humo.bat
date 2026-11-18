@echo off
rem Prueba BETA independiente: humo HTTP.
rem Equivale a: run-beta.bat --test=humo
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-beta.bat" --test=humo %*
exit /b %ERRORLEVEL%
