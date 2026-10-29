# Database Reset PowerShell Script
# 
# This script helps you reset your database completely and run migrations and seeders.
# It's like giving your database a fresh start - imagine cleaning your room completely
# and then putting everything back in an organized way!

# Display a welcome message
Write-Host "==========================================="
Write-Host "      DATABASE RESET UTILITY SCRIPT       "
Write-Host "==========================================="
Write-Host "This script will:"
Write-Host "1. Drop all tables in your database"
Write-Host "2. Run all migrations"
Write-Host "3. Seed the database with initial data"
Write-Host ""
Write-Host "WARNING: This will DELETE ALL DATA in your database!" -ForegroundColor Red
Write-Host "==========================================="
Write-Host ""

# Ask for confirmation
$confirmation = Read-Host "Are you sure you want to continue? (yes/no)"
if ($confirmation -ne "yes") {
    Write-Host "Operation cancelled." -ForegroundColor Yellow
    exit
}

# Automatically detect PHP path
$phpPath = "C:\wamp64\bin\php\php8.2.26\php.exe"
if (-not (Test-Path $phpPath)) {
    # Try to find PHP in common locations
    $possiblePaths = @(
        "C:\wamp64\bin\php\php8.2.26\php.exe",
        "C:\wamp64\bin\php\php8.1.0\php.exe",
        "C:\wamp64\bin\php\php8.0.0\php.exe",
        "C:\wamp64\bin\php\php7.4.0\php.exe",
        "C:\xampp\php\php.exe"
    )
    
    foreach ($path in $possiblePaths) {
        if (Test-Path $path) {
            $phpPath = $path
            break
        }
    }
    
    # If still not found, ask the user
    if (-not (Test-Path $phpPath)) {
        $phpPath = Read-Host "Enter the full path to your PHP executable (e.g., C:\wamp64\bin\php\php8.2.26\php.exe)"
    }
}

if (-not (Test-Path $phpPath)) {
    Write-Host "Error: PHP executable not found at the specified path." -ForegroundColor Red
    exit
}

Write-Host "Using PHP at: $phpPath" -ForegroundColor Green
Write-Host "`nResetting database..." -ForegroundColor Cyan

# Step 1: Drop all tables
Write-Host "Step 1: Dropping all tables..." -ForegroundColor Cyan
$command = "& '$phpPath' artisan db:wipe --force"
Write-Host "Running: $command"
$result = Invoke-Expression $command
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Failed to drop tables. Error code: $LASTEXITCODE" -ForegroundColor Red
    exit
}
Write-Host $result

# Step 2: Run migrations
Write-Host "`nStep 2: Running migrations..." -ForegroundColor Cyan
$command = "& '$phpPath' artisan migrate --force"
Write-Host "Running: $command"
$result = Invoke-Expression $command
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Failed to run migrations. Error code: $LASTEXITCODE" -ForegroundColor Red
    exit
}
Write-Host $result

# Step 3: Seed the database
Write-Host "`nStep 3: Seeding the database..." -ForegroundColor Cyan
$command = "& '$phpPath' artisan db:seed --force"
Write-Host "Running: $command"
$result = Invoke-Expression $command
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Failed to seed the database. Error code: $LASTEXITCODE" -ForegroundColor Red
    exit
}
Write-Host $result

Write-Host "`n==========================================="
Write-Host "Database reset completed successfully!" -ForegroundColor Green
Write-Host "==========================================="

# Wait for user input before closing
Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 