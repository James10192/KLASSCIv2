# Script PowerShell pour exécuter les migrations et les seeders

Write-Host "Exécution des migrations et des seeders..." -ForegroundColor Green

# Chemin vers PHP - ajustez selon votre installation
$phpPath = "C:\wamp64\bin\php\php8.2.0\php.exe"

# Chemin vers le script PHP
$scriptPath = Join-Path $PSScriptRoot "run_migrations_and_seed.php"

# Exécuter le script PHP
& $phpPath $scriptPath

Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 