#!/bin/bash
# Script à ajouter à la fin des commandes importantes pour notifications

# Son système
echo -e '\a\a\a'

# Notification colorée
echo ""
echo -e "\033[42;30m"
echo "════════════════════════════════════════════════════════════"
echo "  ✅ COMMANDE TERMINÉE"
echo "════════════════════════════════════════════════════════════"
echo "  $(date '+%H:%M:%S') - Opération réussie"
echo "  Projet: $(basename "$(pwd)")"
echo "════════════════════════════════════════════════════════════"
echo -e "\033[0m"
echo ""

# Notification Windows si disponible
if command -v python3 >/dev/null 2>&1; then
    python3 .claude/ccnotify/windows-notify.py "ESBTP Terminé" "Commande Laravel terminée avec succès" 2>/dev/null &
fi