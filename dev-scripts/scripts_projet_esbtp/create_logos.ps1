# Script pour créer des logos ESBTP localement
# Ce script crée deux fichiers HTML simples qui serviront de logos temporaires

# Fonction pour créer un fichier HTML logo
function Create-Logo-HTML {
    param (
        [string]$OutputPath,
        [string]$BackgroundColor,
        [string]$TextColor
    )
    
    try {
        $htmlContent = @"
<!DOCTYPE html>
<html>
<head>
    <title>ESBTP Logo</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: $BackgroundColor;
        }
        .logo {
            font-family: Arial, sans-serif;
            font-size: 36px;
            font-weight: bold;
            color: $TextColor;
            text-align: center;
        }
        .subtitle {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: $TextColor;
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div>
        <div class="logo">ESBTP</div>
        <div class="subtitle">École Supérieure du Bâtiment et des Travaux Publics</div>
    </div>
</body>
</html>
"@

        Set-Content -Path $OutputPath -Value $htmlContent
        Write-Host "Logo HTML créé: $OutputPath" -ForegroundColor Green
    }
    catch {
        Write-Host "Erreur lors de la création du logo: $_" -ForegroundColor Red
    }
}

# Chemins de sortie
$logoLightPath = Join-Path $PSScriptRoot "esbtp-logo.html"
$logoDarkPath = Join-Path $PSScriptRoot "esbtp-logo-white.html"

# Créer les logos HTML
Create-Logo-HTML -OutputPath $logoLightPath -BackgroundColor "#ffffff" -TextColor "#0056b3"
Create-Logo-HTML -OutputPath $logoDarkPath -BackgroundColor "#0056b3" -TextColor "#ffffff"

Write-Host "`nOpération terminée. Les logos HTML ont été créés dans $PSScriptRoot" -ForegroundColor Green
Write-Host "Note: Ces fichiers HTML sont des placeholders. Pour un site de production, utilisez de vrais fichiers image." -ForegroundColor Yellow 