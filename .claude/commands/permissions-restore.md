# Restaurer depuis une Sauvegarde

Restaure le projet depuis une branche de sauvegarde Claude

Exécute le script de restauration :
```bash
bash .claude/secure-permissions.sh restore
```

Le script va :
1. 📋 Lister toutes les branches de sauvegarde disponibles
2. 🔢 Vous demander de choisir laquelle restaurer
3. ✅ Vous basculer vers la branche sélectionnée
4. 🔒 Restaurer l'état avant activation du mode dangereux

**Utile** : Pour revenir en arrière après des tests en mode dangereux.