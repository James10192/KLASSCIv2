# Commande de suppression d'étudiant

## Description

La commande `esbtp:delete-student` permet de supprimer complètement un étudiant et toutes ses données liées de la base de données.

⚠️ **ATTENTION** : Cette action est **IRRÉVERSIBLE** !

## Utilisation

### Syntaxe de base
```bash
php artisan esbtp:delete-student {identifier} [options]
```

### Paramètres

- `identifier` : Matricule, ID ou email de l'étudiant à supprimer

### Options disponibles

- `--dry-run` : Mode simulation - Affiche ce qui serait supprimé sans effectuer la suppression
- `--force` : Passer la confirmation interactive
- `--keep-user` : Garder le compte utilisateur associé à l'étudiant

## Exemples d'utilisation

### 1. Mode simulation (recommandé en premier)
```bash
php artisan esbtp:delete-student MESBTP25-0027 --dry-run
```

### 2. Suppression avec confirmation
```bash
php artisan esbtp:delete-student MESBTP25-0027
```

### 3. Suppression forcée (sans confirmation)
```bash
php artisan esbtp:delete-student MESBTP25-0027 --force
```

### 4. Suppression en gardant le compte utilisateur
```bash
php artisan esbtp:delete-student MESBTP25-0027 --keep-user
```

### 5. Recherche par ID
```bash
php artisan esbtp:delete-student 2465 --dry-run
```

### 6. Recherche par email
```bash
php artisan esbtp:delete-student student@example.com --dry-run
```

## Données supprimées

La commande supprime automatiquement toutes les données liées à l'étudiant :

### 📚 Données académiques
- **Inscriptions** : Toutes les inscriptions de l'étudiant
- **Notes** : Toutes les notes et évaluations
- **Absences** : Historique des absences
- **Présences** : Registre des présences
- **Bulletins** : Bulletins de notes
- **Résultats** : Résultats d'examens

### 💰 Données financières
- **Paiements** : Tous les paiements effectués
- **Frais Subscriptions** : Souscriptions aux frais de scolarité
- **Factures** : Factures liées aux inscriptions

### 👪 Relations
- **Relations parents** : Liens avec les parents/tuteurs
- **Compte utilisateur** : Compte de connexion (optionnel)

### 📞 Communications
- **Relances** : Historique des relances de paiement

## Processus de sécurité

### 1. Vérification d'existence
La commande vérifie d'abord que l'étudiant existe en recherchant par :
- Matricule
- ID
- Email

### 2. Analyse complète
Avant toute suppression, la commande :
- Analyse toutes les données liées
- Compte le nombre d'éléments à supprimer
- Détecte les erreurs potentielles

### 3. Confirmation
En mode normal, la commande demande une confirmation explicite :
```
⚠️  ATTENTION: Cette action est IRRÉVERSIBLE!
Voulez-vous vraiment supprimer cet étudiant et toutes ses données? (yes/no) [no]:
```

### 4. Transaction atomique
Toute la suppression s'effectue dans une transaction de base de données :
- Si une erreur survient, toutes les modifications sont annulées
- Garantit la cohérence des données

## Exemples de sortie

### Mode dry-run
```
🗑️  Commande de suppression d'étudiant
=======================================
🔍 MODE DRY-RUN - Aucune suppression ne sera effectuée

✅ Étudiant trouvé:
   • Nom: Kouame Jean Pierre
   • Matricule: MESBTP25-0027
   • Email: jean.kouame@example.com
   • ID: 123

📋 RÉSUMÉ DE LA SUPPRESSION:
============================
+------------------------+----------------------+
| Type de données        | Nombre d'éléments    |
+------------------------+----------------------+
| 👤 Étudiant            | 1 (Kouame Jean Pierre)|
| 📚 Inscriptions        | 2                    |
| 💰 Paiements           | 5                    |
| 📄 Frais Subscriptions | 4                    |
| 🧾 Factures            | 2                    |
| 📝 Notes               | 12                   |
| 🚫 Absences            | 3                    |
| ✅ Présences           | 45                   |
| 📊 Bulletins           | 2                    |
| 🏆 Résultats           | 8                    |
| 📞 Relances            | 1                    |
| 👪 Relations parents   | 2                    |
| 🔐 Compte utilisateur  | 1                    |
+------------------------+----------------------+

📊 TOTAL: 88 éléments seront supprimés
```

### Suppression réussie
```
✅ SUPPRESSION RÉUSSIE!
========================
+------------------------+--------+
| Type supprimé          | Nombre |
+------------------------+--------+
| 👤 Étudiant            | 1      |
| 📚 Inscriptions        | 2      |
| 💰 Paiements           | 5      |
| 📄 Frais Subscriptions | 4      |
| 🧾 Factures            | 2      |
| 📝 Notes               | 12     |
| 🚫 Absences            | 3      |
| ✅ Présences           | 45     |
| 📊 Bulletins           | 2      |
| 🏆 Résultats           | 8      |
| 📞 Relances            | 1      |
| 👪 Relations parents   | 2      |
| 🔐 Compte utilisateur  | 1      |
+------------------------+--------+

🎯 Total supprimé: 88 éléments
```

## Bonnes pratiques

### 1. Toujours commencer par un dry-run
```bash
# ✅ Correct
php artisan esbtp:delete-student MESBTP25-0027 --dry-run
# Puis, après vérification
php artisan esbtp:delete-student MESBTP25-0027
```

### 2. Sauvegarder avant suppression importante
```bash
# Sauvegarde de la base complète
mysqldump -u user -p database_name > backup_before_deletion_$(date +%Y%m%d_%H%M%S).sql
```

### 3. Utiliser les logs
La commande génère des logs détaillés dans `storage/logs/laravel.log` pour audit et traçabilité.

### 4. Cas d'usage pour --keep-user
Utilisez `--keep-user` si l'étudiant peut se réinscrire plus tard ou si le compte utilisateur est partagé.

## Cas d'erreur

### Étudiant inexistant
```
❌ Étudiant introuvable avec l'identifiant: INEXISTANT
💡 Utilisez le matricule, l'ID ou l'email de l'étudiant
```

### Erreur de suppression
```
❌ ERREUR lors de la suppression!
Erreur: Foreign key constraint fails...
Toutes les modifications ont été annulées.
```

## Support et dépannage

En cas de problème :
1. Vérifiez les logs dans `storage/logs/laravel.log`
2. Utilisez d'abord le mode `--dry-run` pour diagnostiquer
3. Vérifiez les contraintes de clés étrangères
4. Contactez l'administrateur système si nécessaire

## Sécurité

- ⚠️ Commande administrative uniquement
- 🔒 Nécessite les droits appropriés sur la base de données
- 📝 Toutes les actions sont loggées pour audit
- 🔄 Utilise les transactions pour garantir la cohérence