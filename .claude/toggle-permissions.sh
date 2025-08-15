#!/bin/bash
# Script pour basculer les permissions Claude Code

SETTINGS_FILE=".claude/settings.json"

if [ ! -f "$SETTINGS_FILE" ]; then
    echo "❌ Fichier de configuration introuvable : $SETTINGS_FILE"
    exit 1
fi

# Vérifier l'état actuel
current_state=$(grep -o '"dangerouslySkipPermissions": [^,]*' "$SETTINGS_FILE" | grep -o '[^: ]*$')

if [ "$current_state" = "true" ]; then
    # Désactiver
    sed -i 's/"dangerouslySkipPermissions": true/"dangerouslySkipPermissions": false/' "$SETTINGS_FILE"
    echo "🔒 Permissions bypass DÉSACTIVÉ - Mode sécurisé"
    echo "   Claude demandera confirmation pour les actions dangereuses"
elif [ "$current_state" = "false" ]; then
    # Activer
    sed -i 's/"dangerouslySkipPermissions": false/"dangerouslySkipPermissions": true/' "$SETTINGS_FILE"
    echo "⚠️  Permissions bypass ACTIVÉ - Mode dangereux"
    echo "   Claude exécutera les commandes sans confirmation"
else
    echo "❓ État des permissions non détecté dans $SETTINGS_FILE"
    exit 1
fi

echo ""
echo "📄 Configuration mise à jour dans $SETTINGS_FILE"
echo "🔄 Redémarrez Claude Code pour appliquer les changements"