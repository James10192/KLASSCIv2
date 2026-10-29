# Database Seeding PowerShell Script
# 
# This script helps you seed your database with initial data.
# It's like planting seeds in a garden - you're preparing your database
# with the initial data it needs to grow into a full application!

# Display a welcome message
Write-Host "==========================================="
Write-Host "      DATABASE SEEDING UTILITY SCRIPT     "
Write-Host "==========================================="
Write-Host "This script will seed your database with initial data."
Write-Host "You can choose which seeders to run."
Write-Host "==========================================="
Write-Host ""

# Get the path to PHP executable
$phpPath = Read-Host "Enter the full path to your PHP executable (e.g., C:\wamp64\bin\php\php8.1.31\php.exe)"

if (-not (Test-Path $phpPath)) {
    Write-Host "Error: PHP executable not found at the specified path." -ForegroundColor Red
    exit
}

# List available seeders
Write-Host "`nAvailable seeders:" -ForegroundColor Cyan
Write-Host "1. All seeders (DatabaseSeeder)"
Write-Host "2. Role seeder only"
Write-Host "3. User seeder only"
Write-Host "4. Super Admin seeder only"
Write-Host "5. UFR seeder only"
Write-Host "6. Formation seeder only"
Write-Host "7. Parcours seeder only"
Write-Host "8. Unite Enseignement seeder only"
Write-Host "9. Element Constitutif seeder only"
Write-Host "10. Exit"

# Ask which seeder to run
$choice = Read-Host "`nEnter the number of the seeder you want to run (1-10)"

switch ($choice) {
    '1' {
        Write-Host "`nRunning all seeders..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '2' {
        Write-Host "`nRunning Role seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=RoleSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '3' {
        Write-Host "`nRunning User seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=UserSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '4' {
        Write-Host "`nRunning Super Admin seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=SuperAdminSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '5' {
        Write-Host "`nRunning UFR seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=UFRSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '6' {
        Write-Host "`nRunning Formation seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=FormationSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '7' {
        Write-Host "`nRunning Parcours seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=ParcoursSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '8' {
        Write-Host "`nRunning Unite Enseignement seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=UniteEnseignementSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '9' {
        Write-Host "`nRunning Element Constitutif seeder..." -ForegroundColor Cyan
        $command = "$phpPath artisan db:seed --class=ElementConstitutifSeeder"
        Write-Host "Running: $command"
        $result = Invoke-Expression $command
        Write-Host $result
    }
    '10' {
        Write-Host "`nExiting..." -ForegroundColor Yellow
        exit
    }
    default {
        Write-Host "`nInvalid choice. Exiting..." -ForegroundColor Red
        exit
    }
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Failed to run seeder. Error code: $LASTEXITCODE" -ForegroundColor Red
    exit
}

Write-Host "`n==========================================="
Write-Host "Database seeding completed successfully!" -ForegroundColor Green
Write-Host "==========================================="

# Wait for user input before closing
Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 