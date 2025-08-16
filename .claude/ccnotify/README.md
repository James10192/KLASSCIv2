# CCNotify pour WSL Ubuntu

Adaptation de [CCNotify](https://github.com/dazuiba/CCNotify) pour les environnements WSL Ubuntu avec support Windows.

## 🔔 Fonctionnalités

- **Notifications desktop** quand Claude a besoin d'input ou termine des tâches
- **Tracking des sessions** avec durées et projets
- **Support multi-plateforme** : Linux notify-send, Windows Toast, et fallback terminal
- **Intégration transparente** avec Claude Code hooks

## 📦 Installation

### Installation automatique
```bash
bash .claude/ccnotify/install.sh
```

### Installation manuelle

1. **Créer les dossiers**
   ```bash
   mkdir -p ~/.claude/ccnotify
   ```

2. **Copier le script**
   ```bash
   cp ccnotify.py ~/.claude/ccnotify/
   chmod +x ~/.claude/ccnotify/ccnotify.py
   ```

3. **Tester l'installation**
   ```bash
   ~/.claude/ccnotify/ccnotify.py
   # Doit afficher: ok
   ```

## 🔧 Configuration des notifications

### Pour WSL Ubuntu (recommandé)
Le script utilise automatiquement PowerShell pour les notifications Windows :
```bash
# Aucune installation supplémentaire requise
# Les notifications apparaîtront dans Windows 10/11
```

### Pour Linux natif (optionnel)
```bash
sudo apt update
sudo apt install libnotify-bin
```

## ⚙️ Intégration Claude Code

Le fichier `.claude/settings.json` doit contenir ces hooks :

```json
{
  "hooks": [
    {
      "matcher": "UserPromptSubmit",
      "hooks": [
        {
          "type": "command",
          "command": ".claude/ccnotify/ccnotify.py UserPromptSubmit"
        }
      ]
    },
    {
      "matcher": "Stop",
      "hooks": [
        {
          "type": "command",
          "command": ".claude/ccnotify/ccnotify.py Stop"
        }
      ]
    },
    {
      "matcher": "Notification",
      "hooks": [
        {
          "type": "command",
          "command": ".claude/ccnotify/ccnotify.py Notification"
        }
      ]
    }
  ]
}
```

## 🧪 Test du système

Utilisez la commande slash personnalisée :
```
/test-notifications
```

Ou testez manuellement :
```bash
# Test d'une tâche rapide
sleep 3 && echo "Test terminé"
```

## 📊 Types de notifications

### 1. Début de tâche (UserPromptSubmit)
- Enregistre le timestamp de début
- Associe la tâche au projet courant

### 2. Fin de tâche (Stop)
- Calcule la durée d'exécution
- Affiche notification avec temps écoulé
- Format intelligent : secondes → minutes → heures

### 3. Input requis (Notification)
- Urgence critique pour attirer l'attention
- Notifie quand Claude attend une réponse

## 📁 Fichiers créés

- `~/.claude/ccnotify/ccnotify.py` - Script principal
- `~/.claude/ccnotify/ccnotify.db` - Base SQLite des sessions
- `~/.claude/ccnotify/ccnotify.log` - Logs détaillés
- `~/.claude/ccnotify/install.sh` - Script d'installation

## 🔍 Débogage

### Voir les logs
```bash
tail -f ~/.claude/ccnotify/ccnotify.log
```

### Tester les notifications manuellement
```bash
# Test direct
.claude/ccnotify/ccnotify.py Notification

# Test avec Python
python3 -c "
from ccnotify import CCNotify
ccnotify = CCNotify()
ccnotify.send_notification('Test', 'Message de test')
"
```

### Vérifier la base de données
```bash
sqlite3 ~/.claude/ccnotify/ccnotify.db "SELECT * FROM sessions ORDER BY id DESC LIMIT 5;"
```

## 🎯 Différences avec l'original

- **Support WSL** : Utilise PowerShell pour notifications Windows
- **Multi-plateforme** : Fallback intelligent selon l'environnement
- **Database locale** : Aucune donnée envoyée externement
- **Intégration** : Compatible avec la configuration Claude existante

## 🚀 Utilisation

Une fois configuré, CCNotify fonctionne automatiquement :

1. Tapez une commande à Claude → Enregistrement du début
2. Claude travaille → Tracking en arrière-plan  
3. Claude termine → Notification avec durée
4. Claude attend input → Notification urgente

**Redémarrez Claude Code** après configuration pour activer les hooks !

## 🔧 Dépannage

### Notifications n'apparaissent pas
1. Vérifiez que PowerShell fonctionne : `powershell.exe -Command "echo test"`
2. Testez manuellement : `.claude/ccnotify/ccnotify.py Notification`
3. Consultez les logs : `cat ~/.claude/ccnotify/ccnotify.log`

### Erreurs Python
1. Vérifiez Python3 : `python3 --version`
2. Permissions : `chmod +x ~/.claude/ccnotify/ccnotify.py`
3. Chemin : Utilisez le chemin absolu dans settings.json si nécessaire