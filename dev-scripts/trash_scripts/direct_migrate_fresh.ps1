# Script PowerShell pour exécuter directement migrate:fresh

Write-Host "Exécution directe de migrate:fresh avec seed..." -ForegroundColor Green

# Définir le chemin du projet Laravel
$projectPath = "C:\wamp64\www\smart_school_new"

# Vérifier si le répertoire du projet existe
if (-not (Test-Path $projectPath)) {
    Write-Host "ERREUR: Le répertoire du projet n'existe pas: $projectPath" -ForegroundColor Red
    Write-Host "Veuillez modifier le chemin dans le script." -ForegroundColor Red
    Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit
}

# Aller au répertoire du projet
Set-Location -Path $projectPath

# Vérifier si le fichier artisan existe
if (-not (Test-Path "artisan")) {
    Write-Host "ERREUR: Le fichier artisan n'a pas été trouvé dans: $projectPath" -ForegroundColor Red
    Write-Host "Veuillez vérifier le chemin du projet." -ForegroundColor Red
    Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit
}

# Essayer de trouver PHP
$phpPath = "C:\wamp64\bin\php\php8.2.0\php.exe"

if (-not (Test-Path $phpPath)) {
    Write-Host "Le chemin vers PHP n'existe pas: $phpPath" -ForegroundColor Yellow
    Write-Host "Recherche de PHP dans d'autres répertoires..." -ForegroundColor Yellow
    
    # Essayer de trouver PHP dans d'autres répertoires
    $phpDirs = Get-ChildItem -Path "C:\wamp64\bin\php" -Directory
    foreach ($dir in $phpDirs) {
        $testPath = Join-Path $dir.FullName "php.exe"
        if (Test-Path $testPath) {
            $phpPath = $testPath
            Write-Host "PHP trouvé à: $phpPath" -ForegroundColor Green
            break
        }
    }
    
    if (-not (Test-Path $phpPath)) {
        Write-Host "Impossible de trouver PHP. Tentative d'utiliser 'php' directement..." -ForegroundColor Yellow
        $phpPath = "php"
    }
}

# Exécuter la commande artisan
Write-Host "`nExécution de: $phpPath artisan migrate:fresh --seed" -ForegroundColor Green
try {
    & $phpPath artisan migrate:fresh --seed
    Write-Host "`nMigrations et seeders exécutés avec succès!" -ForegroundColor Green
} catch {
    Write-Host "`nErreur lors de l'exécution des migrations: $_" -ForegroundColor Red
}

Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 