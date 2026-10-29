# Script de vérification d'intégrité des fichiers du projet ESBTP
# Ce script vérifie que les fichiers principaux du projet existent et ont une taille correcte

# Définition des chemins relatifs depuis la racine du projet
$projectRoot = "C:\wamp64\www\smart_school_new"
$filestoCheck = @(
    @{Path="resources\views\welcome.blade.php"; MinSize=20KB; Description="Page d'accueil principale"},
    @{Path="public\css\esbtp-colors.css"; MinSize=5KB; Description="Feuille de style CSS personnalisée"},
    @{Path="public\img\esbtp_logo.png"; MinSize=5KB; Description="Logo ESBTP standard"},
    @{Path="public\img\esbtp_logo_white.png"; MinSize=5KB; Description="Logo ESBTP blanc"},
    @{Path="scripts_projet_esbtp\README.md"; MinSize=1KB; Description="Documentation des scripts"},
    @{Path="scripts_projet_esbtp\documentation.md"; MinSize=1KB; Description="Documentation du projet"},
    @{Path="scripts_projet_esbtp\backup_projet.ps1"; MinSize=0.5KB; Description="Script de sauvegarde"}
)

# Fonction pour vérifier un fichier
function Check-File {
    param (
        [string]$FilePath,
        [long]$MinSize,
        [string]$Description
    )
    
    $fullPath = Join-Path -Path $projectRoot -ChildPath $FilePath
    
    # Vérification de l'existence du fichier
    if (-not (Test-Path -Path $fullPath)) {
        Write-Host "❌ ERREUR: $Description ($FilePath) est MANQUANT!" -ForegroundColor Red
        return $false
    }
    
    # Vérification de la taille du fichier
    $fileInfo = Get-Item -Path $fullPath
    if ($fileInfo.Length -lt $MinSize) {
        Write-Host "⚠️ AVERTISSEMENT: $Description ($FilePath) est trop petit (${fileInfo.Length} bytes < $MinSize bytes)" -ForegroundColor Yellow
        return $false
    }
    
    # Tout est OK
    Write-Host "✅ OK: $Description ($FilePath) - Taille: $([math]::Round($fileInfo.Length/1KB, 2)) KB" -ForegroundColor Green
    return $true
}

# Fonction principale
function Main {
    Write-Host "=== Vérification d'intégrité du projet ESBTP ===" -ForegroundColor Cyan
    Write-Host "Date de vérification: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
    Write-Host "Répertoire du projet: $projectRoot" -ForegroundColor Cyan
    Write-Host "-------------------------------------------" -ForegroundColor Cyan
    
    $totalFiles = $filestoCheck.Count
    $okFiles = 0
    
    foreach ($file in $filestoCheck) {
        $result = Check-File -FilePath $file.Path -MinSize $file.MinSize -Description $file.Description
        if ($result) { $okFiles++ }
    }
    
    Write-Host "-------------------------------------------" -ForegroundColor Cyan
    Write-Host "Résumé: $okFiles/$totalFiles fichiers OK" -ForegroundColor Cyan
    
    # Affichage du résultat global
    if ($okFiles -eq $totalFiles) {
        Write-Host "✅ SUCCÈS: Tous les fichiers sont présents et valides!" -ForegroundColor Green
    } else {
        $percentOk = [math]::Round(($okFiles / $totalFiles) * 100, 1)
        if ($percentOk -ge 80) {
            Write-Host "⚠️ ATTENTION: $percentOk% des fichiers sont OK. Certains fichiers nécessitent votre attention." -ForegroundColor Yellow
        } else {
            Write-Host "❌ PROBLÈME: Seulement $percentOk% des fichiers sont OK. Plusieurs fichiers sont manquants ou invalides." -ForegroundColor Red
        }
    }
}

# Exécution du script principal
Main

# Explication du script:
# Ce script vérifie l'intégrité des fichiers principaux du projet ESBTP.
# Il s'assure que chaque fichier existe et a une taille minimale attendue.
# Les résultats sont affichés avec des codes couleur pour une meilleure lisibilité:
#   - Vert: Tout est OK
#   - Jaune: Avertissement (fichier trop petit)
#   - Rouge: Erreur (fichier manquant)
# À la fin, un résumé indique le pourcentage de fichiers valides. 