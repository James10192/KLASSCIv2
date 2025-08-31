# Script de mise à jour des informations de contact
# Ce script permet de mettre à jour facilement les informations de contact dans le fichier welcome.blade.php

# Définition des chemins
$projectRoot = "C:\wamp64\www\smart_school_new"
$welcomeFilePath = Join-Path -Path $projectRoot -ChildPath "resources\views\welcome.blade.php"

# Fonction pour créer une sauvegarde du fichier
function Backup-WelcomeFile {
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupPath = Join-Path -Path $projectRoot -ChildPath "scripts_projet_esbtp\backups\welcome_$timestamp.blade.php"
    
    # Créer le dossier de sauvegarde s'il n'existe pas
    $backupDir = Join-Path -Path $projectRoot -ChildPath "scripts_projet_esbtp\backups"
    if (-not (Test-Path -Path $backupDir)) {
        New-Item -Path $backupDir -ItemType Directory | Out-Null
    }
    
    # Copier le fichier
    Copy-Item -Path $welcomeFilePath -Destination $backupPath
    Write-Host "✅ Sauvegarde créée: $backupPath" -ForegroundColor Green
    return $backupPath
}

# Fonction pour mettre à jour les informations de contact
function Update-ContactInfo {
    param (
        [string]$Address,
        [string]$Phone1,
        [string]$Phone2,
        [string]$Email,
        [string]$Website,
        [string]$MapIframe
    )
    
    # Vérifier que le fichier existe
    if (-not (Test-Path -Path $welcomeFilePath)) {
        Write-Host "❌ ERREUR: Le fichier welcome.blade.php n'existe pas à l'emplacement: $welcomeFilePath" -ForegroundColor Red
        return $false
    }
    
    # Lire le contenu du fichier
    $content = Get-Content -Path $welcomeFilePath -Raw
    
    # Créer une sauvegarde avant modification
    $backupPath = Backup-WelcomeFile
    
    # Mettre à jour l'adresse
    if ($Address) {
        $pattern = '(<p class="contact-info"><i class="fas fa-map-marker-alt"></i>).*?(</p>)'
        $replacement = "`${1} $Address`${2}"
        $content = $content -replace $pattern, $replacement
    }
    
    # Mettre à jour les téléphones
    if ($Phone1 -and $Phone2) {
        $pattern = '(<p class="contact-info"><i class="fas fa-phone"></i>).*?(</p>)'
        $replacement = "`${1} $Phone1 / $Phone2`${2}"
        $content = $content -replace $pattern, $replacement
    } elseif ($Phone1) {
        $pattern = '(<p class="contact-info"><i class="fas fa-phone"></i>).*?(</p>)'
        $replacement = "`${1} $Phone1`${2}"
        $content = $content -replace $pattern, $replacement
    }
    
    # Mettre à jour l'email
    if ($Email) {
        $pattern = '(<p class="contact-info"><i class="fas fa-envelope"></i>).*?(</p>)'
        $replacement = "`${1} $Email`${2}"
        $content = $content -replace $pattern, $replacement
    }
    
    # Mettre à jour le site web
    if ($Website) {
        $pattern = '(<p class="contact-info"><i class="fas fa-globe"></i>).*?(</p>)'
        $replacement = "`${1} $Website`${2}"
        $content = $content -replace $pattern, $replacement
    }
    
    # Mettre à jour l'iframe de la carte
    if ($MapIframe) {
        $pattern = '(<div class="map-container">)[\s\S]*?(<iframe[^>]*>[\s\S]*?</iframe>)[\s\S]*?(</div><!-- \.map-container -->)'
        $replacement = "`${1}`n        $MapIframe`n    `${3}"
        $content = $content -replace $pattern, $replacement
    }
    
    # Écrire le contenu mis à jour dans le fichier
    Set-Content -Path $welcomeFilePath -Value $content
    
    Write-Host "✅ Les informations de contact ont été mises à jour avec succès!" -ForegroundColor Green
    return $true
}

# Fonction principale
function Main {
    Write-Host "=== Mise à jour des informations de contact ESBTP ===" -ForegroundColor Cyan
    Write-Host "Ce script met à jour les informations de contact dans le fichier welcome.blade.php" -ForegroundColor Cyan
    Write-Host "-------------------------------------------" -ForegroundColor Cyan
    
    # Demander les nouvelles informations de contact
    Write-Host "Entrez les nouvelles informations de contact (laissez vide pour conserver les valeurs actuelles):" -ForegroundColor Yellow
    
    $address = Read-Host "Adresse"
    $phone1 = Read-Host "Téléphone 1"
    $phone2 = Read-Host "Téléphone 2"
    $email = Read-Host "Email"
    $website = Read-Host "Site web"
    
    $updateMap = Read-Host "Voulez-vous mettre à jour l'iframe de la carte Google Maps? (O/N)"
    $mapIframe = ""
    
    if ($updateMap -eq "O" -or $updateMap -eq "o") {
        Write-Host "Collez le code iframe de Google Maps (commençant par <iframe et se terminant par </iframe>):" -ForegroundColor Yellow
        $mapIframe = Read-Host
    }
    
    # Confirmer les modifications
    Write-Host "-------------------------------------------" -ForegroundColor Cyan
    Write-Host "Vous êtes sur le point de mettre à jour les informations suivantes:" -ForegroundColor Yellow
    
    if ($address) { Write-Host "Adresse: $address" -ForegroundColor White }
    if ($phone1 -or $phone2) { 
        if ($phone1 -and $phone2) {
            Write-Host "Téléphone: $phone1 / $phone2" -ForegroundColor White
        } elseif ($phone1) {
            Write-Host "Téléphone: $phone1" -ForegroundColor White
        }
    }
    if ($email) { Write-Host "Email: $email" -ForegroundColor White }
    if ($website) { Write-Host "Site web: $website" -ForegroundColor White }
    if ($mapIframe) { Write-Host "Carte: [Code iframe mis à jour]" -ForegroundColor White }
    
    $confirm = Read-Host "Confirmez-vous ces modifications? (O/N)"
    
    if ($confirm -eq "O" -or $confirm -eq "o") {
        $result = Update-ContactInfo -Address $address -Phone1 $phone1 -Phone2 $phone2 -Email $email -Website $website -MapIframe $mapIframe
        
        if ($result) {
            Write-Host "✅ Mise à jour terminée avec succès!" -ForegroundColor Green
        } else {
            Write-Host "❌ La mise à jour a échoué." -ForegroundColor Red
        }
    } else {
        Write-Host "❌ Opération annulée par l'utilisateur." -ForegroundColor Red
    }
}

# Exécuter le script principal
Main

# Explication du script:
# Ce script permet de mettre à jour facilement les informations de contact sur la page d'accueil.
# Il fonctionne comme un petit assistant qui vous guide pour modifier:
# - L'adresse de l'école
# - Les numéros de téléphone
# - L'adresse email
# - Le site web
# - L'iframe Google Maps
# 
# Avant chaque modification, une sauvegarde du fichier est créée pour pouvoir revenir en arrière si nécessaire.
# Les sauvegardes sont stockées dans le dossier scripts_projet_esbtp/backups avec un horodatage. 