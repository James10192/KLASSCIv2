# Scripts de Test Archivés

Ce dossier contient des scripts de test et de développement qui ne sont plus utilisés en production.

## Scripts Archivés (9 fichiers)

### Tests de Fonctionnalités

1. **create_test_teachers.php** (4.4 KB)
   - **Raison** : Création manuelle de données de test
   - **Remplacé par** : Database seeders Laravel
   - **Archivé le** : 7 novembre 2025

2. **test_reliquats_check.php** (4.7 KB)
   - **Raison** : Diagnostic système de reliquats
   - **Status** : Fonctionnalité stable, tests terminés
   - **Archivé le** : 7 novembre 2025

3. **test_reliquats_simple.php** (3.9 KB)
   - **Raison** : Test simplifié reliquats
   - **Status** : Remplacé par test_reliquats_check.php
   - **Archivé le** : 7 novembre 2025

### Tests d'Export

4. **test-export-pagination.php** (5.9 KB)
   - **Raison** : Test système pagination exports Excel
   - **Status** : Fonctionnalité vérifiée et validée
   - **Archivé le** : 7 novembre 2025

5. **test-export-classe.php** (3.3 KB)
   - **Raison** : Test exports classes
   - **Status** : Fonctionnalité stable
   - **Archivé le** : 7 novembre 2025

### Tests API

6. **test-paywall-api.php** (4.9 KB)
   - **Raison** : Test API limites paywall
   - **Status** : Feature validée, test effectué
   - **Archivé le** : 7 novembre 2025

### Tests Inscriptions & Frais

7. **test_inscription_pending.php** (3.9 KB)
   - **Raison** : Test workflow inscriptions en attente
   - **Status** : Fonctionnalité stable
   - **Archivé le** : 7 novembre 2025

8. **test_fix_inscription_frais.php** (4.0 KB)
   - **Raison** : Test correction frais inscriptions
   - **Status** : Fix déployé en production
   - **Archivé le** : 7 novembre 2025

9. **test_frais_fix.php** (3.8 KB)
   - **Raison** : Diagnostic système frais
   - **Status** : Remplacé par test_fix_inscription_frais.php
   - **Archivé le** : 7 novembre 2025

## Pourquoi Archiver ?

Ces scripts servaient à tester et valider des fonctionnalités pendant le développement. Maintenant que :

- ✅ Les fonctionnalités sont stables et en production
- ✅ Les tests unitaires Laravel couvrent ces cas d'usage
- ✅ Les database seeders remplacent les scripts de génération de données
- ✅ Les fonctionnalités ont été validées et testées

Ces scripts ne sont **plus nécessaires** pour le déploiement ou la maintenance du système.

## Peut-on les Supprimer Définitivement ?

**Oui**, mais ils sont conservés ici pour :
- **Référence historique** : Comprendre comment certaines features ont été testées
- **Documentation** : Voir les cas d'usage originaux
- **Réutilisation** : Si besoin de créer des tests similaires

## Utilisation

⚠️ **Ne PAS exécuter ces scripts en production.**

Si vous devez les utiliser :
```bash
# Depuis la racine du projet
php docs/legacy-scripts/nom-du-script.php
```

## Maintenance

Ces scripts ne sont **plus maintenus** et peuvent ne plus fonctionner avec les futures versions de KLASSCI.

---

*Dernière mise à jour : 7 novembre 2025*
