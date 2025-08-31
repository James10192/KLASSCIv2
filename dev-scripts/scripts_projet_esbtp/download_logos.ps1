# Script pour télécharger les logos ESBTP
# Ce script télécharge deux versions du logo ESBTP - une pour le fond clair et une pour le fond sombre

# Fonction pour télécharger une image depuis une URL
function Download-Image {
    param (
        [string]$Url,
        [string]$OutputPath
    )
    
    try {
        Write-Host "Téléchargement de $Url vers $OutputPath..."
        Invoke-WebRequest -Uri $Url -OutFile $OutputPath
        Write-Host "Téléchargement réussi!" -ForegroundColor Green
    }
    catch {
        Write-Host "Erreur lors du téléchargement: $_" -ForegroundColor Red
    }
}

# Créer le répertoire si nécessaire (bien que nous l'ayons déjà créé)
$imgDir = Join-Path $PSScriptRoot ""
if (-not (Test-Path $imgDir)) {
    New-Item -ItemType Directory -Path $imgDir -Force | Out-Null
    Write-Host "Répertoire d'images créé: $imgDir" -ForegroundColor Yellow
}
else {
    Write-Host "Le répertoire d'images existe déjà: $imgDir" -ForegroundColor Cyan
}

# URLs des logos (remplacer par les URLs réelles si disponibles)
# Pour l'instant, nous utilisons des placeholders
$logoLightUrl = "https://via.placeholder.com/200x80/ffffff/0056b3?text=ESBTP+LOGO"
$logoDarkUrl = "https://via.placeholder.com/200x80/0056b3/ffffff?text=ESBTP+LOGO"

# Chemins de sortie
$logoLightPath = Join-Path $imgDir "esbtp-logo.png"
$logoDarkPath = Join-Path $imgDir "esbtp-logo-white.png"

# Télécharger les logos
Download-Image -Url $logoLightUrl -OutputPath $logoLightPath
Download-Image -Url $logoDarkUrl -OutputPath $logoDarkPath

Write-Host "`nOpération terminée. Les logos ont été téléchargés dans $imgDir" -ForegroundColor Green 