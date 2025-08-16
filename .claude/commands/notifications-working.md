# Notifications Fonctionnelles 

Puisque les hooks Claude automatiques ne fonctionnent pas dans cette version, voici les méthodes de notification qui marchent :

## 🔔 Notifications Manuelles (Fonctionnent)

### Notification Windows Toast
```bash
python3 .claude/ccnotify/windows-notify.py "Titre" "Message"
```

### Notification Terminal Colorée  
```bash
.claude/ccnotify/hybrid-notify.py Stop
```

### Notification de Fin de Commande
```bash
# Ajouter à la fin de vos commandes importantes :
&& bash .claude/notify-completion.sh
```

## 🎯 Exemples d'Usage

### Commandes Laravel avec Notification
```bash
# Migration avec notification
/mnt/c/xampp/php/php.exe artisan migrate && bash .claude/notify-completion.sh

# Tests avec notification  
/mnt/c/xampp/php/php.exe artisan test && bash .claude/notify-completion.sh

# Serveur avec notification
/mnt/c/xampp/php/php.exe artisan serve && bash .claude/notify-completion.sh
```

### Tests Rapides
```bash
/notify-test        # Commande slash pour tester
/notify-working     # Cette page d'aide
```

## ✅ Status

- ✅ Notifications Windows Toast : **Fonctionnent**
- ✅ Notifications Terminal : **Fonctionnent** 
- ✅ Sons système : **Fonctionnent**
- ❌ Hooks automatiques Claude : **Non supportés dans cette version**

**Solution** : Utilisez les notifications manuelles qui sont plus fiables !