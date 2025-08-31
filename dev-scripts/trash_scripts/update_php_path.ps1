# Script PowerShell pour mettre à jour le chemin PHP dans tous les scripts

Write-Host "Mise à jour du chemin PHP dans tous les scripts..." -ForegroundColor Green
Write-Host ""

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
    try {
        $phpInPath = Get-Command php -ErrorAction Stop
        $phpPath = $phpInPath.Source
        Write-Host "PHP trouvé dans le PATH système: $phpPath" -ForegroundColor Green
    }
    catch {
        Write-Host "Impossible de trouver PHP automatiquement." -ForegroundColor Red
        Write-Host "Veuillez entrer le chemin complet vers php.exe:" -ForegroundColor Yellow
        $phpPath = Read-Host
        
        if (-not (Test-Path $phpPath)) {
            Write-Host "Le chemin spécifié n'existe pas: $phpPath" -ForegroundColor Red
            Write-Host "Mise à jour annulée." -ForegroundColor Red
            Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            exit
        }
    }
} else {
    Write-Host "PHP trouvé à: $phpPath" -ForegroundColor Green
}

# Confirmer la mise à jour
Write-Host ""
Write-Host "Le chemin PHP suivant sera utilisé pour mettre à jour tous les scripts:" -ForegroundColor Yellow
Write-Host $phpPath -ForegroundColor Yellow
Write-Host ""
$confirmation = Read-Host "Voulez-vous continuer? (O/N)"

if ($confirmation -ne "O" -and $confirmation -ne "o") {
    Write-Host "Mise à jour annulée." -ForegroundColor Red
    Write-Host "`nAppuyez sur une touche pour quitter..." -ForegroundColor Cyan
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit
}

# Échapper les backslashes pour les utiliser dans les expressions régulières
$escapedPhpPath = $phpPath.Replace('\', '\\')

# Mettre à jour les fichiers .bat
$batFiles = Get-ChildItem -Path $PSScriptRoot -Filter "*.bat"
foreach ($file in $batFiles) {
    Write-Host "Mise à jour de $($file.Name)..." -ForegroundColor Cyan
    
    $content = Get-Content -Path $file.FullName -Raw
    $newContent = $content -replace 'SET PHP_PATH=.*\\php\.exe', "SET PHP_PATH=$escapedPhpPath"
    
    if ($content -ne $newContent) {
        Set-Content -Path $file.FullName -Value $newContent
        Write-Host "  Mise à jour réussie!" -ForegroundColor Green
    } else {
        Write-Host "  Aucune modification nécessaire." -ForegroundColor Gray
    }
}

# Mettre à jour les fichiers .ps1 (sauf celui-ci)
$ps1Files = Get-ChildItem -Path $PSScriptRoot -Filter "*.ps1" | Where-Object { $_.Name -ne "update_php_path.ps1" }
foreach ($file in $ps1Files) {
    Write-Host "Mise à jour de $($file.Name)..." -ForegroundColor Cyan
    
    $content = Get-Content -Path $file.FullName -Raw
    $newContent = $content -replace '\$phpPath = ".*\\php\.exe"', "`$phpPath = `"$escapedPhpPath`""
    
    if ($content -ne $newContent) {
        Set-Content -Path $file.FullName -Value $newContent
        Write-Host "  Mise à jour réussie!" -ForegroundColor Green
    } else {
        Write-Host "  Aucune modification nécessaire." -ForegroundColor Gray
    }
}

# Mettre à jour les fichiers .php
$phpFiles = Get-ChildItem -Path $PSScriptRoot -Filter "*.php"
foreach ($file in $phpFiles) {
    Write-Host "Mise à jour de $($file.Name)..." -ForegroundColor Cyan
    
    $content = Get-Content -Path $file.FullName -Raw
    # Pas de mise à jour nécessaire pour les fichiers PHP car ils utilisent 'php' directement
    Write-Host "  Aucune modification nécessaire." -ForegroundColor Gray
}

Write-Host ""
Write-Host "Mise à jour terminée! Tous les scripts utilisent maintenant le chemin PHP:" -ForegroundColor Green
Write-Host $phpPath -ForegroundColor Green
Write-Host ""
Write-Host "Appuyez sur une touche pour quitter..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 