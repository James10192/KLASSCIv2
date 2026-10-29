# Script PowerShell pour réinitialiser la base de données

Write-Host "Réinitialisation de la base de données..." -ForegroundColor Green

# Chemin vers PHP - ajustez selon votre installation
$phpPath = "C:\wamp64\bin\php\php8.2.0\php.exe"

# Chemin vers le script PHP
$scriptPath = Join-Path $PSScriptRoot "reset_database.php"

# Exécuter le script PHP
& $phpPath $scriptPath

Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 