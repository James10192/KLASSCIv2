# Notification Simple qui Marche

Envoie une notification Windows avec la méthode PowerShell qui fonctionne :

```bash
bash .claude/notify-simple.sh "Titre" "Message"
```

## Exemples pratiques :

```bash
# Après une migration
/mnt/c/xampp/php/php.exe artisan migrate && bash .claude/notify-simple.sh "Migration" "Base de données mise à jour"

# Après des tests
/mnt/c/xampp/php/php.exe artisan test && bash .claude/notify-simple.sh "Tests" "Tests terminés avec succès"

# Notification générale
bash .claude/notify-simple.sh "ESBTP" "Tâche terminée"
```

Cette méthode utilise exactement la commande PowerShell qui avait fonctionné lors des tests initiaux.