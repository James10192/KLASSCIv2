# Script PowerShell pour publier les fichiers CSS ESBTP dans le répertoire public
# Ce script copie le fichier CSS ESBTP dans le répertoire public/css

# Définition des chemins
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$CssSource = Join-Path -Path $ScriptDir -ChildPath "esbtp-colors.css"
$CssDest = Join-Path -Path $ProjectRoot -ChildPath "public\css\esbtp-colors.css"

# Création du répertoire CSS s'il n'existe pas
Write-Host "Vérification du répertoire CSS..."
$CssDir = Join-Path -Path $ProjectRoot -ChildPath "public\css"
if (-not (Test-Path -Path $CssDir)) {
    Write-Host "Création du répertoire CSS..."
    New-Item -ItemType Directory -Path $CssDir -Force | Out-Null
}

# Copie du fichier CSS
Write-Host "Copie du fichier CSS ESBTP..."
try {
    Copy-Item -Path $CssSource -Destination $CssDest -Force
    Write-Host "Le fichier CSS a été copié avec succès vers $CssDest" -ForegroundColor Green
} catch {
    Write-Host "Erreur lors de la copie du fichier CSS: $_" -ForegroundColor Red
    exit 1
}

Write-Host "Publication terminée avec succès!" -ForegroundColor Green
Write-Host "Le fichier CSS ESBTP est maintenant disponible à l'URL: /css/esbtp-colors.css"
Write-Host "Vous pouvez l'inclure dans vos pages avec:"
Write-Host "<link rel=`"stylesheet`" href=`"{{ asset('css/esbtp-colors.css') }}`">" -ForegroundColor Cyan 