#!/bin/bash

# Script pour publier les fichiers CSS ESBTP dans le répertoire public
# Ce script copie le fichier CSS ESBTP dans le répertoire public/css
# et s'assure que les permissions sont correctes.

# Définition des chemins
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CSS_SOURCE="$SCRIPT_DIR/esbtp-colors.css"
CSS_DEST="$PROJECT_ROOT/public/css/esbtp-colors.css"

# Création du répertoire CSS s'il n'existe pas
echo "Vérification du répertoire CSS..."
if [ ! -d "$PROJECT_ROOT/public/css" ]; then
    echo "Création du répertoire CSS..."
    mkdir -p "$PROJECT_ROOT/public/css"
fi

# Copie du fichier CSS
echo "Copie du fichier CSS ESBTP..."
cp "$CSS_SOURCE" "$CSS_DEST"

# Vérification de la copie
if [ $? -eq 0 ]; then
    echo "Le fichier CSS a été copié avec succès vers $CSS_DEST"
else
    echo "Erreur lors de la copie du fichier CSS"
    exit 1
fi

# Définition des permissions
echo "Définition des permissions..."
chmod 644 "$CSS_DEST"

echo "Publication terminée avec succès!"
echo "Le fichier CSS ESBTP est maintenant disponible à l'URL: /css/esbtp-colors.css"
echo "Vous pouvez l'inclure dans vos pages avec:"
echo "<link rel=\"stylesheet\" href=\"{{ asset('css/esbtp-colors.css') }}\">"

exit 0 