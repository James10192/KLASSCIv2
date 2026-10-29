# Script de vérification d'intégrité des fichiers modifiés
# Ce script vérifie que les fichiers importants existent et ont un contenu valide

# Définition des variables
$sourceDir = ".."
$logFile = ".\integrity_check_$(Get-Date -Format 'yyyy-MM-dd').log"

# Fonction pour écrire dans le journal
function Write-Log {
    param (
        [string]$message,
        [string]$level = "INFO"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$level] $message"
    
    # Afficher dans la console avec couleur
    switch ($level) {
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        "WARNING" { Write-Host $logMessage -ForegroundColor Yellow }
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        default { Write-Host $logMessage -ForegroundColor Cyan }
    }
    
    # Écrire dans le fichier journal
    Add-Content -Path $logFile -Value $logMessage
}

# Initialisation du journal
Write-Log "Début de la vérification d'intégrité des fichiers" "INFO"

# Liste des fichiers à vérifier
$filesToCheck = @(
    @{
        Path = "resources\views\welcome.blade.php"
        RequiredContent = @("ESBTP", "Yamoussoukro", "contact", "formations")
        MinSize = 10KB
    },
    @{
        Path = "public\css\esbtp-colors.css"
        RequiredContent = @(":root", "--primary-color", "font-family")
        MinSize = 1KB
    }
)

# Vérification de chaque fichier
$allFilesValid = $true

foreach ($file in $filesToCheck) {
    $fullPath = Join-Path -Path $sourceDir -ChildPath $file.Path
    
    # Vérifier si le fichier existe
    if (-not (Test-Path -Path $fullPath)) {
        Write-Log "Le fichier $($file.Path) n'existe pas!" "ERROR"
        $allFilesValid = $false
        continue
    }
    
    # Vérifier la taille du fichier
    $fileInfo = Get-Item -Path $fullPath
    if ($fileInfo.Length -lt $file.MinSize) {
        Write-Log "Le fichier $($file.Path) est trop petit ($('{0:N2}' -f ($fileInfo.Length / 1KB)) KB < $($file.MinSize / 1KB) KB)" "WARNING"
    } else {
        Write-Log "Taille du fichier $($file.Path): $('{0:N2}' -f ($fileInfo.Length / 1KB)) KB" "INFO"
    }
    
    # Vérifier le contenu requis
    $content = Get-Content -Path $fullPath -Raw
    $missingContent = @()
    
    foreach ($requiredText in $file.RequiredContent) {
        if (-not ($content -match $requiredText)) {
            $missingContent += $requiredText
        }
    }
    
    if ($missingContent.Count -gt 0) {
        Write-Log "Le fichier $($file.Path) ne contient pas le contenu requis: $($missingContent -join ', ')" "ERROR"
        $allFilesValid = $false
    } else {
        Write-Log "Le contenu du fichier $($file.Path) est valide" "SUCCESS"
    }
}

# Résumé de la vérification
if ($allFilesValid) {
    Write-Log "Tous les fichiers ont passé la vérification d'intégrité" "SUCCESS"
} else {
    Write-Log "Certains fichiers n'ont pas passé la vérification d'intégrité. Consultez le journal pour plus de détails." "ERROR"
}

Write-Log "Fin de la vérification d'intégrité des fichiers" "INFO"
Write-Host "Le journal de vérification a été enregistré dans: $logFile" -ForegroundColor Cyan 