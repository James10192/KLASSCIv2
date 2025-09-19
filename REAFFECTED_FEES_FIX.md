# Correction des frais pour étudiants réaffectés

## Problème identifié

Les étudiants avec le statut `affectation_status = 'réaffecté'` avaient incorrectement leurs frais de scolarité calculés avec les montants `amount_non_affecte` au lieu des montants `amount_affecte` dans leurs ESBTPFraisSubscription.

### Logique correcte
- **Affecté** → `amount_affecte`
- **Réaffecté** → `amount_affecte` (même traitement qu'affecté)
- **Non affecté** → `amount_non_affecte`

## Solution : Commande Artisan

Une commande Artisan a été créée pour corriger automatiquement ces frais.

### Utilisation

#### 1. Mode Dry-Run (Recommandé d'abord)
```bash
php artisan esbtp:fix-reaffected-fees --dry-run
```

**Avantages du dry-run :**
- ✅ Aucune modification effectuée
- ✅ Affiche ce qui serait changé
- ✅ Montre les statistiques
- ✅ Identifie les erreurs potentielles

#### 2. Exécution réelle
```bash
php artisan esbtp:fix-reaffected-fees
```

#### 3. Exécution forcée (sans confirmation)
```bash
php artisan esbtp:fix-reaffected-fees --force
```

### Fonctionnalités de sécurité

1. **Dry-run obligatoire** : Toujours tester d'abord
2. **Confirmation interactive** : Demande confirmation avant modification
3. **Transaction DB** : Rollback automatique en cas d'erreur
4. **Logging détaillé** : Notes ajoutées aux souscriptions modifiées
5. **Rapport complet** : Statistiques et détails des changements

### Exemple d'output

```
🔍 Analyzing ESBTPFraisSubscription for reaffected students...
🔍 DRY RUN MODE - No changes will be made
📊 Found 15 reaffected student(s)

👤 Processing: KOUAME Jean Pierre (ID: 123)
👤 Processing: TRAORE Marie Claire (ID: 456)

📋 SUMMARY:
+-------------------+------------------+----------------+----------------+-----------+
| Student           | Category         | Current Amount | Correct Amount | Difference|
+-------------------+------------------+----------------+----------------+-----------+
| KOUAME Jean Pierre| Frais scolarité | 150 000 FCFA  | 200 000 FCFA  | +50 000   |
| TRAORE Marie Claire| Frais scolarité | 150 000 FCFA  | 200 000 FCFA  | +50 000   |
+-------------------+------------------+----------------+----------------+-----------+

📊 STATISTICS:
• Total students affected: 15
• Subscriptions to update: 15
• Total amount change: +750 000 FCFA

🔍 DRY RUN COMPLETE - No changes were made
Run without --dry-run to apply these changes
```

### Déploiement sur serveur distant

#### Étape 1 : Dry-run sur le serveur
```bash
ssh user@your-server
cd /path/to/your/project
php artisan esbtp:fix-reaffected-fees --dry-run
```

#### Étape 2 : Vérification des résultats
- Analyser l'output du dry-run
- Vérifier les montants et différences
- S'assurer que tout semble correct

#### Étape 3 : Application des changements
```bash
php artisan esbtp:fix-reaffected-fees
# Ou si vous êtes sûr :
php artisan esbtp:fix-reaffected-fees --force
```

### Vérification post-exécution

Après l'exécution, vous pouvez vérifier :

1. **Via la commande** :
```bash
php artisan esbtp:fix-reaffected-fees --dry-run
```
(Devrait afficher "All reaffected students already have correct fees!")

2. **Via base de données** :
```sql
SELECT
    i.id as inscription_id,
    CONCAT(e.nom, ' ', e.prenoms) as student_name,
    i.affectation_status,
    fc.name as category_name,
    fs.amount as current_amount,
    fc.amount_affecte as should_be_amount
FROM esbtp_frais_subscriptions fs
JOIN esbtp_inscriptions i ON fs.inscription_id = i.id
JOIN esbtp_etudiants e ON i.etudiant_id = e.id
JOIN esbtp_frais_categories fc ON fs.frais_category_id = fc.id
JOIN esbtp_frais_configurations fconf ON fconf.frais_category_id = fc.id
    AND fconf.filiere_id = i.filiere_id
    AND fconf.niveau_id = i.niveau_id
WHERE i.affectation_status = 'réaffecté'
    AND fs.is_active = true
    AND fs.amount != fconf.amount_affecte;
```

### Sauvegarde recommandée

Avant d'exécuter sur le serveur de production :

```bash
# Sauvegarde de la table des souscriptions
mysqldump -u user -p database_name esbtp_frais_subscriptions > backup_frais_subscriptions_$(date +%Y%m%d_%H%M%S).sql
```

### Logs et traces

La commande ajoute automatiquement des notes aux souscriptions modifiées :
```
"Auto-corrected reaffected fees: 150000 → 200000 FCFA on 2025-09-19 14:30:22"
```

## Support

En cas de problème, la commande fournit des messages d'erreur détaillés et effectue un rollback automatique pour préserver l'intégrité des données.