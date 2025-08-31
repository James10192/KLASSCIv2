Write-Host "======================================================" -ForegroundColor Green
Write-Host " Installation des filieres et niveaux d'etudes ESBTP" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
Write-Host ""

# Définir le chemin vers PHP
$PHP_PATH = "C:\wamp64\bin\php\php8.1.31\php.exe"

Write-Host "Execution des migrations..." -ForegroundColor Cyan
& $PHP_PATH artisan migrate

Write-Host ""
Write-Host "Execution des seeders..." -ForegroundColor Cyan
& $PHP_PATH artisan db:seed --class=ESBTPFiliereSeeder
& $PHP_PATH artisan db:seed --class=ESBTPNiveauEtudeSeeder
& $PHP_PATH artisan db:seed --class=ESBTPAnneeUniversitaireSeeder

Write-Host ""
Write-Host "Nettoyage du cache..." -ForegroundColor Cyan
& $PHP_PATH artisan optimize:clear

Write-Host ""
Write-Host "Installation terminee avec succes!" -ForegroundColor Green
Write-Host ""

Read-Host "Appuyez sur Entrée pour continuer..." 