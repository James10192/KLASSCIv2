@echo off
REM This batch file runs the PowerShell database manager script
REM It's like a control panel for your database - one place to manage everything!

echo Starting database management utility...
powershell -ExecutionPolicy Bypass -File "%~dp0database_manager.ps1" 