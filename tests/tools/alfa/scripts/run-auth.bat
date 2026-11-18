@echo off
rem Prueba ALFA independiente: Autenticación y acceso.
rem Equivale a: run-alfa.bat --test=auth
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=auth %*
exit /b %ERRORLEVEL%
