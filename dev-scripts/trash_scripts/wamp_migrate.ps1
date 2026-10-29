# Script PowerShell pour exécuter migrate:fresh en utilisant le PHP de WAMP

Write-Host "Exécution de migrate:fresh avec le PHP de WAMP..." -ForegroundColor Green

# Fonction pour trouver la version la plus récente de PHP dans WAMP
function Find-LatestPhpVersion {
    $phpDir = "C:\wamp64\bin\php"
    if (-not (Test-Path $phpDir)) {
        return $null
    }
    
    $phpVersions = Get-ChildItem -Path $phpDir -Directory | 
                   Where-Object { $_.Name -match "^\d+\.\d+\.\d+$" } |
                   Sort-Object -Property Name -Descending
    
    if ($phpVersions.Count -eq 0) {
        return $null
    }
    
    $latestVersion = $phpVersions[0]
    $phpPath = Join-Path $latestVersion.FullName "php.exe"
    
    if (Test-Path $phpPath) {
        return $phpPath
    }
    
    return $null
}

# Trouver PHP
$phpPath = Find-LatestPhpVersion
if ($null -eq $phpPath) {
    Write-Host "Impossible de trouver PHP dans WAMP. Tentative d'utiliser 'php' directement..." -ForegroundColor Yellow
    $phpPath = "php"
} else {
    Write-Host "PHP trouvé à: $phpPath" -ForegroundColor Green
}

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