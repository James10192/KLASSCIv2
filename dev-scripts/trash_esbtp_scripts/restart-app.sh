#!/bin/bash

# Script Bash pour redémarrer l'application Laravel
# Ce script nettoie le cache et redémarre le serveur de développement

# Définition des chemins
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Aller au répertoire du projet
cd "$PROJECT_ROOT"

# Afficher un message de bienvenue
echo -e "\e[36m=== Script de redémarrage de l'application ESBTP ===\e[0m"
echo -e "\e[36mCe script va nettoyer le cache et redémarrer le serveur de développement.\e[0m"
echo ""

# Nettoyer le cache
echo -e "\e[33mNettoyage du cache...\e[0m"
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Publier le fichier CSS ESBTP
echo -e "\e[33mPublication du fichier CSS ESBTP...\e[0m"
bash "$SCRIPT_DIR/publish-css.sh"

# Redémarrer le serveur
echo -e "\e[33mRedémarrage du serveur de développement...\e[0m"
echo -e "\e[32mLe serveur sera accessible à l'adresse: http://127.0.0.1:8000\e[0m"
echo -e "\e[33mAppuyez sur Ctrl+C pour arrêter le serveur.\e[0m"
echo ""

# Démarrer le serveur
php artisan serve 