# PowerShell script to check the Laravel environment
Set-Location -Path $PSScriptRoot\..

Write-Host "=== Laravel Environment Check ===" -ForegroundColor Cyan
Write-Host ""

# Check PHP version
$phpVersion = php -v | Select-String -Pattern "PHP ([0-9\.]+)" | ForEach-Object { $_.Matches.Groups[1].Value }
Write-Host "PHP Version: $phpVersion" -ForegroundColor Green

# Check Laravel version
$laravelVersion = php artisan --version | Select-String -Pattern "Laravel Framework ([0-9\.]+)" | ForEach-Object { $_.Matches.Groups[1].Value }
Write-Host "Laravel Version: $laravelVersion" -ForegroundColor Green

# Check database connection
Write-Host "Checking database connection..." -ForegroundColor Yellow
$dbResult = php artisan db:show 2>&1
if ($dbResult -match "Connected successfully") {
    Write-Host "Database: Connected successfully" -ForegroundColor Green
} else {
    Write-Host "Database: Connection failed" -ForegroundColor Red
    Write-Host $dbResult
}

# Check storage permissions
Write-Host "Checking storage permissions..." -ForegroundColor Yellow
if (Test-Path -Path "storage/logs" -PathType Container) {
    Write-Host "Storage: Permissions OK" -ForegroundColor Green
} else {
    Write-Host "Storage: Permissions issue" -ForegroundColor Red
}

# Check if .env file exists
Write-Host "Checking .env file..." -ForegroundColor Yellow
if (Test-Path -Path ".env" -PathType Leaf) {
    Write-Host ".env file: Found" -ForegroundColor Green
} else {
    Write-Host ".env file: Not found" -ForegroundColor Red
}

# Check if key is generated
$envContent = Get-Content -Path ".env" -ErrorAction SilentlyContinue
if ($envContent -match "APP_KEY=base64:") {
    Write-Host "Application key: Generated" -ForegroundColor Green
} else {
    Write-Host "Application key: Not generated" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Environment Check Complete ===" -ForegroundColor Cyan
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 