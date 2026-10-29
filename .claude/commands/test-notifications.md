# Test des Notifications CCNotify

Teste le système de notifications desktop CCNotify pour WSL Ubuntu

Exécute une tâche de test :
```bash
sleep 3 && echo "Test terminé !"
```

Le système va :
1. 🔔 Enregistrer le début de la tâche (UserPromptSubmit)
2. ⏱️ Attendre 3 secondes
3. ✅ Notifier la fin avec durée (Stop)
4. 🔊 Jouer le son de notification

**Test complet** : Notifications Windows + sons système + tracking des sessions !