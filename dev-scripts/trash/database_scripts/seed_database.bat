@echo off
REM This batch file runs the PowerShell script to seed the database
REM It's like planting seeds in your garden with just one click!

echo Starting database seeding utility...
powershell -ExecutionPolicy Bypass -File "%~dp0seed_database.ps1" 