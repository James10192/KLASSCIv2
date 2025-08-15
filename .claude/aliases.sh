# Aliases Claude Code - À ajouter dans ~/.bashrc ou ~/.zshrc

# Scripts sécurisés avec Git
alias claude-safe-on='bash .claude/secure-permissions.sh on'
alias claude-safe-off='bash .claude/secure-permissions.sh off' 
alias claude-restore='bash .claude/secure-permissions.sh restore'
alias claude-status='bash .claude/secure-permissions.sh status'

# Anciens aliases (mode rapide, sans sécurité)
alias claude-toggle='bash .claude/toggle-permissions.sh'
alias claude-yolo-quick='sed -i "s/dangerouslySkipPermissions\": false/dangerouslySkipPermissions\": true/" .claude/settings.json && echo "⚠️ Mode dangereux activé (SANS sauvegarde)"'

# Vérification rapide
alias claude-check='grep "dangerouslySkipPermissions" .claude/settings.json'

# Workflow recommandé
alias claude-work='claude-safe-on && echo "✅ Prêt pour le travail en mode sécurisé"'
alias claude-end='claude-safe-off && echo "🔒 Session terminée, mode sécurisé rétabli"'