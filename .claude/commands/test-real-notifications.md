# Test Réel des Notifications CCNotify

Pour tester les vraies notifications CCNotify dans Claude Code :

## 1. Redémarrez Claude Code
Les hooks ne sont actifs qu'après redémarrage de Claude.

## 2. Testez avec une vraie commande
```bash
sleep 5 && echo "Notification test terminé!"
```

Vous devriez voir :
1. 🔵 **Notification de début** quand vous envoyez la commande
2. 🟢 **Notification de fin** après 5 secondes

## 3. Test d'input requis
Toute commande qui demande une interaction déclenchera une notification rouge critique.

## ⚠️ Important
- Les notifications ne fonctionnent que dans Claude Code réel
- Les tests manuels (`.claude/ccnotify/...`) montrent juste le système
- **Redémarrage requis** pour activer les hooks

État actuel : Hooks configurés ✅ - Redémarrage requis pour activation