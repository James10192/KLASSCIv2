# Activer le Bypass des Permissions (Mode Sécurisé)

Active le mode dangereux avec sauvegarde automatique Git

Exécute le script sécurisé :
```bash
bash .claude/secure-permissions.sh on
```

Le script va :
1. 🔄 Créer une branche de sauvegarde avec timestamp
2. 📝 Commit automatique des changements en cours
3. ⚠️ Activer `dangerouslySkipPermissions: true`
4. ✅ Confirmer l'activation

**SÉCURITÉ** : Branche `claude-backup_[branch]_[timestamp]` créée automatiquement.