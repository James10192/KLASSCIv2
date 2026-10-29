# Script PowerShell pour démarrer le serveur Laravel et vérifier la configuration
# Ce script facilite le démarrage du serveur Laravel et la vérification de la configuration

# Fonction pour afficher un message coloré
function Write-ColoredOutput {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Message,
        
        [Parameter(Mandatory=$false)]
        [string]$ForegroundColor = "White"
    )
    
    Write-Host $Message -ForegroundColor $ForegroundColor
}

# Fonction pour vérifier si un port est utilisé
function Test-PortInUse {
    param (
        [Parameter(Mandatory=$true)]
        [int]$Port
    )
    
    $connections = netstat -ano | Select-String -Pattern "TCP.*:$Port "
    return $connections.Count -gt 0
}

# Afficher un message de bienvenue
Write-ColoredOutput "=== Démarrage du serveur Laravel pour ESBTP ===" -ForegroundColor "Cyan"
Write-ColoredOutput "Ce script va vous aider à démarrer le serveur Laravel et vérifier la configuration." -ForegroundColor "Cyan"

# Vérifier si le port 8000 est déjà utilisé
if (Test-PortInUse -Port 8000) {
    Write-ColoredOutput "ATTENTION: Le port 8000 est déjà utilisé par un autre processus!" -ForegroundColor "Yellow"
    Write-ColoredOutput "Voulez-vous essayer de libérer ce port? (O/N)" -ForegroundColor "Yellow"
    $response = Read-Host
    
    if ($response -eq "O" -or $response -eq "o") {
        Write-ColoredOutput "Tentative de libération du port 8000..." -ForegroundColor "Yellow"
        $process = netstat -ano | Select-String -Pattern "TCP.*:8000 " | ForEach-Object { ($_ -split '\s+')[-1] }
        
        if ($process) {
            Write-ColoredOutput "Arrêt du processus $process..." -ForegroundColor "Yellow"
            Stop-Process -Id $process -Force -ErrorAction SilentlyContinue
            
            if ($?) {
                Write-ColoredOutput "Le port 8000 a été libéré avec succès." -ForegroundColor "Green"
            } else {
                Write-ColoredOutput "Impossible de libérer le port 8000. Veuillez le faire manuellement." -ForegroundColor "Red"
                exit
            }
        }
    } else {
        Write-ColoredOutput "Vous pouvez modifier le port dans le fichier serve.bat." -ForegroundColor "Yellow"
        exit
    }
}

# Vérifier si PHP est installé
try {
    $phpVersion = php -v | Select-String -Pattern "PHP ([0-9]+\.[0-9]+\.[0-9]+)"
    if ($phpVersion) {
        Write-ColoredOutput "PHP est installé: $phpVersion" -ForegroundColor "Green"
    } else {
        Write-ColoredOutput "PHP est installé, mais impossible de déterminer la version." -ForegroundColor "Yellow"
    }
} catch {
    Write-ColoredOutput "PHP n'est pas installé ou n'est pas dans le PATH!" -ForegroundColor "Red"
    Write-ColoredOutput "Veuillez installer PHP ou ajouter son chemin au PATH." -ForegroundColor "Red"
    exit
}

# Vérifier si Composer est installé
try {
    $composerVersion = composer --version | Select-String -Pattern "Composer version ([0-9]+\.[0-9]+\.[0-9]+)"
    if ($composerVersion) {
        Write-ColoredOutput "Composer est installé: $composerVersion" -ForegroundColor "Green"
    } else {
        Write-ColoredOutput "Composer est installé, mais impossible de déterminer la version." -ForegroundColor "Yellow"
    }
} catch {
    Write-ColoredOutput "Composer n'est pas installé ou n'est pas dans le PATH!" -ForegroundColor "Yellow"
    Write-ColoredOutput "Certaines fonctionnalités peuvent ne pas fonctionner correctement." -ForegroundColor "Yellow"
}

# Vérifier le fichier .env
$envFile = ".env"
if (Test-Path $envFile) {
    Write-ColoredOutput "Le fichier .env existe." -ForegroundColor "Green"
    
    # Vérifier l'URL de l'application
    $appUrl = Get-Content $envFile | Select-String -Pattern "APP_URL=" | ForEach-Object { $_ -replace "APP_URL=", "" }
    Write-ColoredOutput "URL de l'application: $appUrl" -ForegroundColor "Cyan"
    
    if ($appUrl -eq "http://127.0.0.1:8000") {
        Write-ColoredOutput "L'URL de l'application est correctement configurée pour le serveur de développement." -ForegroundColor "Green"
    } else {
        Write-ColoredOutput "L'URL de l'application n'est pas configurée pour le serveur de développement." -ForegroundColor "Yellow"
        Write-ColoredOutput "Voulez-vous la modifier? (O/N)" -ForegroundColor "Yellow"
        $response = Read-Host
        
        if ($response -eq "O" -or $response -eq "o") {
            (Get-Content $envFile) -replace "APP_URL=.*", "APP_URL=http://127.0.0.1:8000" | Set-Content $envFile
            Write-ColoredOutput "L'URL de l'application a été mise à jour." -ForegroundColor "Green"
        }
    }
} else {
    Write-ColoredOutput "Le fichier .env n'existe pas!" -ForegroundColor "Red"
    Write-ColoredOutput "Veuillez créer un fichier .env à partir du fichier .env.example." -ForegroundColor "Red"
    exit
}

# Vérifier si le dossier vendor existe
if (Test-Path "vendor") {
    Write-ColoredOutput "Le dossier vendor existe." -ForegroundColor "Green"
} else {
    Write-ColoredOutput "Le dossier vendor n'existe pas!" -ForegroundColor "Red"
    Write-ColoredOutput "Voulez-vous exécuter 'composer install'? (O/N)" -ForegroundColor "Yellow"
    $response = Read-Host
    
    if ($response -eq "O" -or $response -eq "o") {
        Write-ColoredOutput "Exécution de 'composer install'..." -ForegroundColor "Cyan"
        composer install
        
        if ($?) {
            Write-ColoredOutput "Les dépendances ont été installées avec succès." -ForegroundColor "Green"
        } else {
            Write-ColoredOutput "Erreur lors de l'installation des dépendances!" -ForegroundColor "Red"
            exit
        }
    } else {
        Write-ColoredOutput "Le serveur ne fonctionnera pas correctement sans les dépendances." -ForegroundColor "Red"
        exit
    }
}

# Effacer le cache
Write-ColoredOutput "Voulez-vous effacer le cache? (O/N)" -ForegroundColor "Cyan"
$response = Read-Host

if ($response -eq "O" -or $response -eq "o") {
    Write-ColoredOutput "Effacement du cache..." -ForegroundColor "Cyan"
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    Write-ColoredOutput "Le cache a été effacé avec succès." -ForegroundColor "Green"
}

# Démarrer le serveur
Write-ColoredOutput "Démarrage du serveur Laravel..." -ForegroundColor "Cyan"
Write-ColoredOutput "Le serveur sera accessible à l'adresse: http://127.0.0.1:8000" -ForegroundColor "Cyan"
Write-ColoredOutput "Appuyez sur Ctrl+C pour arrêter le serveur." -ForegroundColor "Cyan"

# Démarrer le serveur
php artisan serve --host=127.0.0.1 --port=8000 