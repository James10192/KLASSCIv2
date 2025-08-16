# Configuration CCNotify

Configure le système de notifications CCNotify pour WSL Ubuntu

## Options disponibles :

### 1. Version Hybride (Recommandée)
- Notifications Windows popup + Terminal coloré
- Maximum de visibilité
```bash
# Déjà configuré par défaut
.claude/ccnotify/hybrid-notify.py
```

### 2. Version Complète
- Tracking complet des sessions avec base de données
- Notifications Windows avec fallback
```bash
.claude/ccnotify/ccnotify.py
```

### 3. Version Simple
- Notifications terminal uniquement
- Léger et fiable
```bash
.claude/ccnotify/simple-notify.py
```

## Test du système :
```bash
# Test notification critique
.claude/ccnotify/hybrid-notify.py Notification

# Test completion
.claude/ccnotify/hybrid-notify.py Stop
```

Le système hybride est configuré par défaut pour le meilleur équilibre visibilité/performance dans WSL !