@echo off
rem Prueba ALFA independiente: Compras.
rem Equivale a: run-alfa.bat --test=compras
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=compras %*
exit /b %ERRORLEVEL%
