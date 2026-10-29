# Database Manager PowerShell Script
# 
# This script provides a menu to access all the database management scripts.
# It's like a control panel for your database - one place to manage everything!

# Function to display the menu
function Show-Menu {
    Clear-Host
    Write-Host "==========================================="
    Write-Host "      DATABASE MANAGEMENT UTILITY         "
    Write-Host "==========================================="
    Write-Host "1. Reset Database (Drop all tables and run migrations)"
    Write-Host "2. Check Database Status"
    Write-Host "3. Seed Database"
    Write-Host "4. Exit"
    Write-Host "==========================================="
}

# Function to get PHP path
function Get-PhpPath {
    $phpPath = Read-Host "Enter the full path to your PHP executable (e.g., C:\wamp64\bin\php\php8.1.31\php.exe)"
    
    if (-not (Test-Path $phpPath)) {
        Write-Host "Error: PHP executable not found at the specified path." -ForegroundColor Red
        return $null
    }
    
    return $phpPath
}

# Main loop
do {
    Show-Menu
    $choice = Read-Host "Enter your choice (1-4)"
    
    switch ($choice) {
        '1' {
            # Reset Database
            Write-Host "`nLaunching Database Reset Utility..." -ForegroundColor Cyan
            & "$PSScriptRoot\reset_database.ps1"
        }
        '2' {
            # Check Database Status
            Write-Host "`nLaunching Database Status Check Utility..." -ForegroundColor Cyan
            & "$PSScriptRoot\check_database.ps1"
        }
        '3' {
            # Seed Database
            Write-Host "`nLaunching Database Seeding Utility..." -ForegroundColor Cyan
            & "$PSScriptRoot\seed_database.ps1"
        }
        '4' {
            # Exit
            Write-Host "`nExiting..." -ForegroundColor Yellow
            exit
        }
        default {
            # Invalid choice
            Write-Host "`nInvalid choice. Please try again." -ForegroundColor Red
            Start-Sleep -Seconds 2
        }
    }
} while ($true) 