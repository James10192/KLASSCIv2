#!/bin/bash
# Script sécurisé pour gérer les permissions Claude Code avec Git

SETTINGS_FILE=".claude/settings.json"
BRANCH_PREFIX="claude-backup"

# Fonction pour créer une branche de sauvegarde
create_backup_branch() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local current_branch=$(git branch --show-current)
    local backup_branch="${BRANCH_PREFIX}_${current_branch}_${timestamp}"
    
    echo "🔄 Création de la branche de sauvegarde : $backup_branch"
    
    # Vérifier s'il y a des changements non commités
    if ! git diff --quiet || ! git diff --cached --quiet; then
        echo "📝 Commit des changements en cours..."
        git add .
        git commit -m "🔒 Sauvegarde avant activation permissions bypass - $(date '+%Y-%m-%d %H:%M:%S')"
    fi
    
    # Créer la branche de sauvegarde
    git checkout -b "$backup_branch"
    echo "✅ Branche de sauvegarde créée : $backup_branch"
    
    # Retourner à la branche originale
    git checkout "$current_branch"
    echo "🔙 Retour à la branche : $current_branch"
}

# Fonction pour restaurer depuis une branche de sauvegarde
restore_from_backup() {
    echo "📋 Branches de sauvegarde disponibles :"
    git branch | grep "$BRANCH_PREFIX" | nl
    
    echo ""
    read -p "Entrez le numéro de la branche à restaurer (ou 'q' pour quitter) : " choice
    
    if [ "$choice" = "q" ]; then
        echo "❌ Restauration annulée"
        return
    fi
    
    local selected_branch=$(git branch | grep "$BRANCH_PREFIX" | sed -n "${choice}p" | xargs)
    
    if [ -z "$selected_branch" ]; then
        echo "❌ Branche invalide"
        return
    fi
    
    echo "🔄 Restauration depuis : $selected_branch"
    read -p "Confirmer la restauration ? (y/N) : " confirm
    
    if [ "$confirm" = "y" ] || [ "$confirm" = "Y" ]; then
        git checkout "$selected_branch"
        echo "✅ Restauré vers la branche : $selected_branch"
    else
        echo "❌ Restauration annulée"
    fi
}

# Fonction pour activer les permissions bypass
activate_bypass() {
    if [ ! -f "$SETTINGS_FILE" ]; then
        echo "❌ Fichier de configuration introuvable : $SETTINGS_FILE"
        exit 1
    fi
    
    # Créer une sauvegarde automatique
    create_backup_branch
    
    # Activer le bypass
    sed -i 's/"dangerouslySkipPermissions": false/"dangerouslySkipPermissions": true/' "$SETTINGS_FILE"
    
    echo ""
    echo "⚠️  PERMISSIONS BYPASS ACTIVÉ"
    echo "   Claude peut maintenant exécuter toutes les commandes sans confirmation"
    echo "   Branche de sauvegarde créée pour sécurité"
    echo ""
    echo "🔄 Redémarrez Claude Code pour appliquer les changements"
}

# Fonction pour désactiver les permissions bypass
deactivate_bypass() {
    if [ ! -f "$SETTINGS_FILE" ]; then
        echo "❌ Fichier de configuration introuvable : $SETTINGS_FILE"
        exit 1
    fi
    
    # Désactiver le bypass
    sed -i 's/"dangerouslySkipPermissions": true/"dangerouslySkipPermissions": false/' "$SETTINGS_FILE"
    
    echo "🔒 PERMISSIONS BYPASS DÉSACTIVÉ"
    echo "   Claude demandera confirmation pour les actions dangereuses"
    echo ""
    echo "🔄 Redémarrez Claude Code pour appliquer les changements"
}

# Menu principal
case "$1" in
    "on")
        activate_bypass
        ;;
    "off")
        deactivate_bypass
        ;;
    "restore")
        restore_from_backup
        ;;
    "status")
        current_state=$(grep -o '"dangerouslySkipPermissions": [^,]*' "$SETTINGS_FILE" | grep -o '[^: ]*$')
        if [ "$current_state" = "true" ]; then
            echo "⚠️  Mode dangereux ACTIVÉ"
        else
            echo "🔒 Mode sécurisé ACTIVÉ"
        fi
        ;;
    *)
        echo "🛠️  Script de gestion sécurisée des permissions Claude Code"
        echo ""
        echo "Usage: $0 [OPTION]"
        echo ""
        echo "Options:"
        echo "  on        Activer le bypass (avec sauvegarde Git)"
        echo "  off       Désactiver le bypass"
        echo "  restore   Restaurer depuis une branche de sauvegarde"
        echo "  status    Afficher l'état actuel"
        echo ""
        echo "Exemple:"
        echo "  $0 on     # Active le mode dangereux avec sauvegarde"
        echo "  $0 off    # Désactive le mode dangereux"
        echo "  $0 restore # Restaure depuis une sauvegarde"
        ;;
esac