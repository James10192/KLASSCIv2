# PowerShell script to optimize the Laravel application for production
Set-Location -Path $PSScriptRoot\..
Write-Host "Optimizing the application for production..."
Write-Host "Caching configuration..."
php artisan config:cache
Write-Host "Caching routes..."
php artisan route:cache
Write-Host "Caching views..."
php artisan view:cache
Write-Host "Running final optimization..."
php artisan optimize
Write-Host "Application optimized for production!"
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 