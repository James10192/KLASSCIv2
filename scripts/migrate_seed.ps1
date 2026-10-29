# PowerShell script to run database migrations with seed data
Set-Location -Path $PSScriptRoot\..
Write-Host "Running database migrations with seed data..."
php artisan migrate:fresh --seed
Write-Host "Database migrations and seeding completed!"
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 