# PowerShell script to clear all Laravel caches
Set-Location -Path $PSScriptRoot\..
Write-Host "Clearing configuration cache..."
php artisan config:clear
Write-Host "Clearing application cache..."
php artisan cache:clear
Write-Host "Clearing route cache..."
php artisan route:clear
Write-Host "Clearing view cache..."
php artisan view:clear
Write-Host "All caches cleared successfully!"
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 