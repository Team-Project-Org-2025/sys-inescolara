@echo off
rem Prueba ALFA independiente: Sistema.
rem Equivale a: run-alfa.bat --test=sistema
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-alfa.bat" --test=sistema %*
exit /b %ERRORLEVEL%
