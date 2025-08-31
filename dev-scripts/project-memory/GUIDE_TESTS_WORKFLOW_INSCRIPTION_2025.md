# Guide de Tests - Workflow de Validation Inscription-Comptabilité

## Contexte

Ce guide présente les tests complets pour valider le workflow de validation inscription-comptabilité implémenté le 16 juillet 2025.

## Prérequis de Test

### 1. Accès Administrateur
- **URL de connexion**: `http://localhost/ESBTP-yAKROv2Pascal/public/login`
- **Identifiants**: username: `superadmin`, password: `password123`
- **Permissions requises**: `inscriptions.view`, `inscriptions.validate`, `inscriptions.edit`

### 2. Données de Test
- Au moins une inscription avec statut `en_attente`
- Catégories de frais configurées dans `esbtp_fee_categories`
- Classes avec places disponibles
- Année universitaire active

## Tests Manuels

### Test 1: Interface d'Administration des Inscriptions

**Objectif**: Vérifier que l'interface d'administration fonctionne correctement

**Étapes**:
1. Se connecter avec les identifiants administrateur
2. Naviguer vers `/esbtp/inscriptions-administration`
3. Vérifier l'affichage des statistiques
4. Tester les filtres (filière, niveau, workflow_step, statut paiement)

**Résultats attendus**:
- ✅ Page charge sans erreur
- ✅ Statistiques affichées correctement
- ✅ Filtres fonctionnels
- ✅ Liste des inscriptions en attente visible
- ✅ Badges de workflow colorés et cohérents

### Test 2: Association d'un Paiement à une Inscription

**Objectif**: Vérifier le processus d'association paiement-inscription

**Étapes**:
1. Depuis l'interface d'administration, cliquer sur "Associer un paiement" pour une inscription sans paiement
2. Remplir le formulaire modal:
   - Montant: 50000
   - Catégorie de frais: Frais d'inscription
   - Mode de paiement: Espèces
   - Date de paiement: Date actuelle
   - Observations: "Paiement initial"
3. Valider le formulaire

**Résultats attendus**:
- ✅ Modal s'ouvre correctement
- ✅ Formulaire se soumet sans erreur
- ✅ Message de succès affiché
- ✅ Inscription mise à jour avec `workflow_step = 'en_validation'`
- ✅ Paiement créé avec numéro de reçu
- ✅ Historique workflow enregistré

### Test 3: Validation Définitive d'une Inscription

**Objectif**: Vérifier la conversion prospect → étudiant

**Étapes**:
1. Sélectionner une inscription avec paiement associé
2. Cliquer sur "Valider définitivement"
3. Ajouter des observations: "Validation test"
4. Confirmer la validation

**Résultats attendus**:
- ✅ Modal de validation s'ouvre
- ✅ Validation réussie
- ✅ Inscription passe à `workflow_step = 'etudiant_cree'`
- ✅ Statut inscription devient `active`
- ✅ Compte utilisateur étudiant activé
- ✅ Historique workflow mis à jour

### Test 4: Vérification des Pages Show Améliorées

**Objectif**: Vérifier l'intégration du workflow dans les pages de détails

**Étapes**:
1. Naviguer vers la page détails d'une inscription (`/esbtp/inscriptions/{id}`)
2. Vérifier la section "Workflow et Historique"
3. Naviguer vers la page détails d'un étudiant (`/esbtp/etudiants/{id}`)
4. Vérifier la colonne "Workflow" dans la table des inscriptions

**Résultats attendus**:
- ✅ Badges de workflow affichés correctement
- ✅ Barre de progression du workflow fonctionnelle
- ✅ Informations de validation visibles
- ✅ Lien vers interface d'administration présent
- ✅ Page étudiant montre le workflow des inscriptions

### Test 5: Vérification des Permissions

**Objectif**: Tester les permissions d'accès aux fonctionnalités

**Étapes**:
1. Créer un utilisateur avec permissions limitées
2. Tester l'accès aux différentes fonctionnalités
3. Vérifier que les boutons d'administration sont masqués pour les utilisateurs non autorisés

**Résultats attendus**:
- ✅ Middleware de permissions fonctionnel
- ✅ Interface d'administration protégée
- ✅ Boutons d'action masqués selon les permissions

## Tests Automatisés Recommandés

### Test 6: Tests PHPUnit pour les Services

**Fichier**: `tests/Unit/InscriptionWorkflowServiceTest.php`

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InscriptionWorkflowService;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InscriptionWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_inscription_with_payment()
    {
        // Arrange
        $inscription = ESBTPInscription::factory()->create([
            'status' => 'en_attente',
            'paiement_validation_id' => null
        ]);
        
        $paiement = ESBTPPaiement::factory()->create([
            'inscription_id' => $inscription->id,
            'status' => 'validated'
        ]);
        
        $inscription->update(['paiement_validation_id' => $paiement->id]);
        
        $service = new InscriptionWorkflowService();
        
        // Act
        $result = $service->validateInscription($inscription);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Inscription prête pour validation.', $result['message']);
    }
    
    public function test_convert_prospect_to_student()
    {
        // Arrange
        $inscription = ESBTPInscription::factory()->create([
            'status' => 'en_attente',
            'workflow_step' => 'en_validation'
        ]);
        
        $service = new InscriptionWorkflowService();
        
        // Act
        $result = $service->convertProspectToStudent($inscription, 'Test conversion');
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('etudiant_cree', $inscription->fresh()->workflow_step);
        $this->assertEquals('active', $inscription->fresh()->status);
    }
}
```

### Test 7: Tests d'Intégration avec la Base de Données

**Commandes à exécuter**:
```bash
# Tester les migrations
php artisan migrate:rollback --step=3
php artisan migrate

# Tester les seeders
php artisan db:seed --class=ESBTPInscriptionSeeder

# Vérifier les contraintes
php artisan tinker
>>> App\Models\ESBTPInscription::where('status', 'en_attente')->count()
>>> App\Models\ESBTPPaiement::where('status', 'validated')->count()
```

### Test 8: Tests de Performance

**Objectif**: Vérifier les performances avec un volume important de données

**Étapes**:
1. Créer 1000 inscriptions en attente
2. Mesurer le temps de chargement de l'interface d'administration
3. Tester les filtres avec des volumes importants

**Commandes**:
```bash
# Créer des données de test
php artisan tinker
>>> App\Models\ESBTPInscription::factory(1000)->create(['status' => 'en_attente'])

# Mesurer les performances
curl -w "@curl-format.txt" -o /dev/null -s "http://localhost/ESBTP-yAKROv2Pascal/public/esbtp/inscriptions-administration"
```

## Tests de Cas Limites

### Test 9: Gestion des Erreurs

**Cas à tester**:
1. **Inscription sans paiement**: Tenter de valider une inscription sans paiement associé
2. **Classe pleine**: Tenter de valider une inscription pour une classe sans places
3. **Inscription déjà validée**: Tenter de revalider une inscription active
4. **Données invalides**: Soumettre des montants négatifs ou des dates futures

**Résultats attendus**:
- ✅ Messages d'erreur appropriés
- ✅ Rollback des transactions en cas d'erreur
- ✅ Logging des erreurs dans les fichiers de log
- ✅ Retour utilisateur informatif

### Test 10: Test de Cohérence des Données

**Objectif**: Vérifier la cohérence des données après les opérations

**Vérifications**:
1. Historique workflow complet et cohérent
2. Numérotation séquentielle des reçus
3. Sommes des paiements correctes
4. Statuts des inscriptions synchronisés

**Requêtes SQL de vérification**:
```sql
-- Vérifier la cohérence des workflow_step
SELECT workflow_step, status, COUNT(*) 
FROM esbtp_inscriptions 
GROUP BY workflow_step, status;

-- Vérifier les paiements validés
SELECT COUNT(*) 
FROM esbtp_paiements 
WHERE status = 'validated' AND inscription_id IS NOT NULL;

-- Vérifier l'historique workflow
SELECT inscription_id, COUNT(*) as actions_count 
FROM esbtp_inscription_workflow_histories 
GROUP BY inscription_id 
ORDER BY actions_count DESC;
```

## Test de Régression

### Test 11: Fonctionnalités Existantes

**Objectif**: S'assurer que les fonctionnalités existantes fonctionnent toujours

**Domaines à tester**:
1. Création d'inscriptions classique
2. Modification d'inscriptions
3. Système de paiements existant
4. Génération de rapports
5. Gestion des classes et filières

**Checklist**:
- ✅ Formulaire de création d'inscription fonctionnel
- ✅ Modification d'inscription avant validation
- ✅ Système de permissions inchangé
- ✅ Rapports existants fonctionnels
- ✅ Interface utilisateur cohérente

## Métriques de Performance

### Temps de Réponse Acceptables

| Fonctionnalité | Temps Maximum | Temps Optimal |
|---|---|---|
| Chargement interface admin | 2 secondes | 1 seconde |
| Association paiement | 3 secondes | 1,5 secondes |
| Validation inscription | 5 secondes | 2 secondes |
| Filtrage liste | 1 seconde | 0,5 secondes |

### Métriques de Qualité

| Métrique | Objectif | Méthode de Mesure |
|---|---|---|
| Taux de réussite des validations | 99% | Logs d'erreurs |
| Cohérence des données | 100% | Requêtes de vérification |
| Performance sous charge | 100 inscriptions/minute | Tests de charge |

## Procédure de Validation

### Phase 1: Tests Manuels Essentiels (30 minutes)
1. Test 1: Interface d'administration
2. Test 2: Association paiement
3. Test 3: Validation inscription
4. Test 4: Pages show améliorées

### Phase 2: Tests de Cas Limites (20 minutes)
1. Test 9: Gestion d'erreurs
2. Test 10: Cohérence des données

### Phase 3: Tests de Régression (15 minutes)
1. Test 11: Fonctionnalités existantes

### Phase 4: Tests Automatisés (si implémentés)
1. Exécution des tests PHPUnit
2. Tests d'intégration base de données
3. Tests de performance

## Critères de Validation

### Critères de Succès
- ✅ Tous les tests manuels passent
- ✅ Aucune erreur dans les logs
- ✅ Données cohérentes en base
- ✅ Performance acceptable
- ✅ Interface utilisateur intuitive

### Critères d'Échec
- ❌ Erreurs critiques non gérées
- ❌ Perte de données
- ❌ Fonctionnalités existantes cassées
- ❌ Performance inacceptable
- ❌ Interface non fonctionnelle

## Dépannage

### Problèmes Courants

1. **Erreur 500 sur interface admin**
   - Vérifier les permissions base de données
   - Vérifier la configuration des routes
   - Consulter les logs Laravel

2. **Paiement non associé**
   - Vérifier la configuration des catégories de frais
   - Vérifier les contraintes de base de données
   - Consulter les logs d'erreur

3. **Workflow bloqué**
   - Vérifier les prérequis de validation
   - Vérifier les permissions utilisateur
   - Réinitialiser le workflow si nécessaire

### Commandes de Diagnostic

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Vérifier les permissions
php artisan permission:show

# Vérifier les routes
php artisan route:list | grep inscription

# Vérifier la configuration
php artisan config:cache
php artisan view:clear
```

## Conclusion

Ce guide de tests complet permet de valider tous les aspects du workflow implémenté. Une exécution complète des tests garantit que le système fonctionne correctement et respecte les spécifications définies dans le prompt_ifran.md.

---

**Auteur**: Claude (Assistant IA)  
**Date**: 16 juillet 2025  
**Version**: 1.0  
**Statut**: Guide de test complet pour workflow inscription-comptabilité