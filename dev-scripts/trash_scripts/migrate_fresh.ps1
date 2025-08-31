# Script PowerShell pour exécuter les migrations avec l'option fresh

Write-Host "Exécution des migrations avec l'option fresh et seed..." -ForegroundColor Green

# Chemin vers PHP - ajustez selon votre installation
$phpPath = "C:\wamp64\bin\php\php8.2.0\php.exe"

# Aller au répertoire du projet Laravel
Set-Location -Path (Join-Path $PSScriptRoot "..")

# Exécuter les migrations avec l'option fresh et seed
& $phpPath artisan migrate:fresh --seed

Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 