# Script de sauvegarde du projet ESBTP
# Ce script crée une sauvegarde des fichiers importants du projet

# Définition des variables
$dateFormat = Get-Date -Format "yyyy-MM-dd_HH-mm"
$backupDir = ".\backups\$dateFormat"
$sourceDir = "..\..\"  # Chemin relatif vers la racine du projet

# Création du répertoire de sauvegarde
Write-Host "Création du répertoire de sauvegarde..." -ForegroundColor Green
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null

# Liste des fichiers à sauvegarder
$filesToBackup = @(
    "resources\views\welcome.blade.php",
    "public\css\esbtp-colors.css",
    "public\images\esbtp_logo.png",
    "public\images\esbtp_logo_white.png"
)

# Fonction pour copier un fichier avec création de répertoire si nécessaire
function Copy-FileWithDirectoryStructure {
    param (
        [string]$sourceFile,
        [string]$destinationDir
    )
    
    $destFile = Join-Path -Path $destinationDir -ChildPath $sourceFile
    $destDir = Split-Path -Path $destFile -Parent
    
    # Création du répertoire de destination s'il n'existe pas
    if (-not (Test-Path -Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }
    
    # Copie du fichier
    Copy-Item -Path (Join-Path -Path $sourceDir -ChildPath $sourceFile) -Destination $destFile -Force
    
    return $destFile
}

# Sauvegarde des fichiers
Write-Host "Sauvegarde des fichiers..." -ForegroundColor Yellow
foreach ($file in $filesToBackup) {
    $destFile = Copy-FileWithDirectoryStructure -sourceFile $file -destinationDir $backupDir
    Write-Host "Fichier sauvegardé: $destFile" -ForegroundColor Cyan
}

# Création d'un fichier de log
$logContent = @"
Sauvegarde effectuée le $(Get-Date)
Fichiers sauvegardés:
$(($filesToBackup | ForEach-Object { "- $_" }) -join "`n")
"@

$logFile = Join-Path -Path $backupDir -ChildPath "backup_log.txt"
$logContent | Out-File -FilePath $logFile -Encoding utf8

Write-Host "Sauvegarde terminée avec succès!" -ForegroundColor Green
Write-Host "Emplacement: $backupDir" -ForegroundColor Green 