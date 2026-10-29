# Auto Migration PowerShell Script
# 
# This script automatically runs migrations without asking for confirmation.
# It's like having a robot that organizes your room without asking questions!
# Useful for automated deployments or when you're sure you want to migrate.

# Display a welcome message
Write-Host "==========================================="
Write-Host "      AUTO MIGRATION UTILITY SCRIPT       "
Write-Host "==========================================="
Write-Host "This script will automatically run migrations."
Write-Host "==========================================="
Write-Host ""

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
}

if (-not (Test-Path $phpPath)) {
    Write-Host "Error: PHP executable not found. Please specify the path manually." -ForegroundColor Red
    exit
}

Write-Host "Using PHP at: $phpPath" -ForegroundColor Green
Write-Host ""
Write-Host "Running migrations..." -ForegroundColor Cyan

# Run migrations
$command = "& '$phpPath' artisan migrate --force"
Write-Host "Running: $command"
$result = Invoke-Expression $command
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Failed to run migrations. Error code: $LASTEXITCODE" -ForegroundColor Red
    exit
}
Write-Host $result

Write-Host ""
Write-Host "==========================================="
Write-Host "Migrations completed successfully!" -ForegroundColor Green
Write-Host "===========================================" 