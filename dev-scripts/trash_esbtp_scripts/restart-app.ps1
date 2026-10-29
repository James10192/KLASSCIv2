# Script PowerShell pour redémarrer l'application Laravel
# Ce script nettoie le cache et redémarre le serveur de développement

# Définition des chemins
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

# Aller au répertoire du projet
Set-Location -Path $ProjectRoot

# Afficher un message de bienvenue
Write-Host "=== Script de redémarrage de l'application ESBTP ===" -ForegroundColor Cyan
Write-Host "Ce script va nettoyer le cache et redémarrer le serveur de développement." -ForegroundColor Cyan
Write-Host ""

# Nettoyer le cache
Write-Host "Nettoyage du cache..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Publier le fichier CSS ESBTP
Write-Host "Publication du fichier CSS ESBTP..." -ForegroundColor Yellow
& "$ScriptDir\publish-css.ps1"

# Redémarrer le serveur
Write-Host "Redémarrage du serveur de développement..." -ForegroundColor Yellow
Write-Host "Le serveur sera accessible à l'adresse: http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Appuyez sur Ctrl+C pour arrêter le serveur." -ForegroundColor Yellow
Write-Host ""

# Démarrer le serveur
php artisan serve 