# Script PowerShell pour trouver le chemin de PHP sur le système

Write-Host "Recherche du chemin de PHP sur votre système..." -ForegroundColor Green
Write-Host ""

# Vérifier dans les emplacements courants de WAMP
Write-Host "Vérification des emplacements WAMP..." -ForegroundColor Cyan

$wampPhpDir = "C:\wamp64\bin\php"
if (-not (Test-Path $wampPhpDir)) {
    Write-Host "Le répertoire $wampPhpDir n'existe pas." -ForegroundColor Yellow
    Write-Host "Veuillez vérifier votre installation WAMP." -ForegroundColor Yellow
}
else {
    Write-Host "Répertoires PHP trouvés dans WAMP:" -ForegroundColor Cyan
    
    # Lister tous les répertoires PHP dans WAMP
    $phpVersions = Get-ChildItem -Path $wampPhpDir -Directory | 
                   Where-Object { $_.Name -match "^\d+\.\d+\.\d+$" } |
                   Sort-Object -Property Name -Descending
    
    foreach ($phpVersion in $phpVersions) {
        $phpPath = Join-Path $phpVersion.FullName "php.exe"
        if (Test-Path $phpPath) {
            Write-Host "PHP $($phpVersion.Name) trouvé à: $phpPath" -ForegroundColor Green
            
            # Vérifier la version de PHP
            try {
                $phpVersionOutput = & $phpPath -v
                Write-Host "  Version: $($phpVersionOutput[0])" -ForegroundColor Gray
            }
            catch {
                Write-Host "  Impossible d'obtenir la version" -ForegroundColor Red
            }
        }
    }
}

Write-Host ""
Write-Host "Vérification de PHP dans le PATH système..." -ForegroundColor Cyan

# Vérifier si PHP est dans le PATH
try {
    $phpInPath = Get-Command php -ErrorAction Stop
    Write-Host "PHP est disponible dans le PATH système." -ForegroundColor Green
    Write-Host "Chemin complet: $($phpInPath.Source)" -ForegroundColor Green
    
    # Vérifier la version de PHP
    try {
        $phpVersionOutput = & php -v
        Write-Host "Version: $($phpVersionOutput[0])" -ForegroundColor Gray
    }
    catch {
        Write-Host "Impossible d'obtenir la version" -ForegroundColor Red
    }
}
catch {
    Write-Host "PHP n'est pas disponible dans le PATH système." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Vérification terminée. Utilisez un des chemins ci-dessus dans vos scripts." -ForegroundColor Green
Write-Host ""

# Vérifier si WAMP est en cours d'exécution
$wampProcess = Get-Process wampmanager -ErrorAction SilentlyContinue
if ($wampProcess) {
    Write-Host "WAMP est en cours d'exécution." -ForegroundColor Green
}
else {
    Write-Host "WAMP ne semble pas être en cours d'exécution." -ForegroundColor Yellow
    Write-Host "Assurez-vous que WAMP est démarré avant d'exécuter les migrations." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Appuyez sur une touche pour quitter..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 