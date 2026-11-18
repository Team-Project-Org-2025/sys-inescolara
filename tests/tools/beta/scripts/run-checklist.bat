@echo off
rem Prueba BETA independiente: checklist manual guiado.
rem Equivale a: run-beta.bat --test=checklist
setlocal EnableExtensions
set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%run-beta.bat" --test=checklist %*
exit /b %ERRORLEVEL%
