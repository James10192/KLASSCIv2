# PowerShell script to create a symbolic link for storage
Set-Location -Path $PSScriptRoot\..
Write-Host "Creating storage link..."
php artisan storage:link
Write-Host "Storage link created!"
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 