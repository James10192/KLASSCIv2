@echo off
REM This batch file runs the PowerShell script to reset the database
REM It's like a shortcut button that starts the cleaning process!

echo Starting database reset utility...
powershell -ExecutionPolicy Bypass -File "%~dp0reset_database.ps1" 