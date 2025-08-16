# Test des Notifications Manuelles

Puisque les hooks Claude ne se déclenchent pas, voici des tests manuels des notifications :

## Test Notification Windows Toast
```bash
python3 .claude/ccnotify/windows-notify.py "Claude Code" "Test de notification"
```

## Test Notification Terminal Colorée
```bash
.claude/ccnotify/hybrid-notify.py Notification
```

## Test Son Système Simple
```bash
echo -e '\a\a\a' && echo "🔔 Notification sonore !"
```

## Test Notification Complète
```bash
bash .claude/test-claude-hooks.sh
```

Ces commandes fonctionnent indépendamment des hooks Claude et vous permettent de tester tous les types de notifications.