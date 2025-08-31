@echo off
REM This batch file runs the PowerShell script to check database status
REM It's like a quick health check for your database!

echo Starting database status check utility...
powershell -ExecutionPolicy Bypass -File "%~dp0check_database.ps1" 