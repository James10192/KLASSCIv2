# Module Frais avec Statuts d'Affectation - Guide Complet KLASSCI

## 📋 Vue d'ensemble

Ce guide détaille les modifications apportées au module de gestion des frais KLASSCI pour supporter la tarification différenciée selon les statuts d'affectation gouvernementale des étudiants en Côte d'Ivoire.

## 🏛️ Contexte Système d'Affectation Ivoirien

### Rappel du processus MESRS
- **Affectés** : Étudiants placés par l'État via bac.mesrs-ci.net (subvention possible)
- **Réaffectés** : Étudiants réaffectés par la DOB après demande (subvention maintenue)
- **Non Affectés** : Inscriptions directes sans affectation étatique (tarif complet)

## 🎯 Objectif Principal pour les Établissements

Permettre aux établissements-clients KLASSCI de configurer des tarifs différents pour chaque combinaison **Filière + Niveau** selon le statut d'affectation gouvernementale :
- **Affecté** : Tarif avec prise en charge étatique potentielle
- **Réaffecté** : Tarif avec maintien de la prise en charge étatique
- **Non Affecté** : Tarif complet sans subvention gouvernementale

## 🗄️ Modifications de Base de Données

### Table `esbtp_frais_configurations`

**Nouveaux champs ajoutés :**
```sql
-- Montants spécifiques par statut d'affectation
amount_affecte DECIMAL(12,2) NULL COMMENT 'Montant pour étudiants affectés'
amount_reaffecte DECIMAL(12,2) NULL COMMENT 'Montant pour étudiants réaffectés'  
amount_non_affecte DECIMAL(12,2) NULL COMMENT 'Montant pour étudiants non affectés'

-- Index pour optimisation des requêtes
INDEX idx_frais_config_affectation (filiere_id, niveau_id, frais_category_id)
```

**Structure complète après migration :**
```sql
CREATE TABLE esbtp_frais_configurations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filiere_id BIGINT UNSIGNED NOT NULL,
    niveau_id BIGINT UNSIGNED NOT NULL,
    frais_category_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,              -- Ancien champ maintenu pour rétrocompatibilité
    amount_affecte DECIMAL(12,2) NULL,          -- Nouveau : montant affectés
    amount_reaffecte DECIMAL(12,2) NULL,        -- Nouveau : montant réaffectés
    amount_non_affecte DECIMAL(12,2) NULL,      -- Nouveau : montant non affectés
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_frais_config_affectation (filiere_id, niveau_id, frais_category_id)
);
```

## 🔧 Modifications du Modèle

### ESBTPFraisConfiguration

**Nouveaux champs dans $fillable :**
```php
protected $fillable = [
    'filiere_id',
    'niveau_id', 
    'frais_category_id',
    'amount',                // Champ existant
    'amount_affecte',        // Nouveau
    'amount_reaffecte',      // Nouveau
    'amount_non_affecte',    // Nouveau
];
```

**Nouvelles méthodes utilitaires :**
```php
/**
 * Récupère le montant selon le statut d'affectation
 */
public function getMontantByStatus($affectationStatus)
{
    return match($affectationStatus) {
        'affecté' => $this->amount_affecte ?? $this->amount,
        'réaffecté' => $this->amount_reaffecte ?? $this->amount,
        'non_affecté' => $this->amount_non_affecte ?? $this->amount,
        default => $this->amount
    };
}

/**
 * Vérifie si des montants différenciés sont configurés
 */
public function hasDifferentiatedAmounts()
{
    return $this->amount_affecte !== null || 
           $this->amount_reaffecte !== null || 
           $this->amount_non_affecte !== null;
}

/**
 * Retourne tous les montants configurés
 */
public function getAllAmounts()
{
    return [
        'affecté' => $this->amount_affecte ?? $this->amount,
        'réaffecté' => $this->amount_reaffecte ?? $this->amount,
        'non_affecté' => $this->amount_non_affecte ?? $this->amount,
    ];
}
```

## 📊 Pages Affectées

### 1. Page Configuration : `/esbtp/frais/configure`

**Nouvelle interface avec 3 colonnes de montants :**

```html
<div class="row">
    <!-- Colonne Affectés -->
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">💚 Étudiants Affectés</h6>
            </div>
            <div class="card-body">
                <input type="number" name="amount_affecte[]" 
                       class="form-control" step="0.01" min="0"
                       placeholder="Montant affectés">
            </div>
        </div>
    </div>

    <!-- Colonne Réaffectés -->
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-header bg-warning text-white">
                <h6 class="mb-0">🧡 Étudiants Réaffectés</h6>
            </div>
            <div class="card-body">
                <input type="number" name="amount_reaffecte[]" 
                       class="form-control" step="0.01" min="0"
                       placeholder="Montant réaffectés">
            </div>
        </div>
    </div>

    <!-- Colonne Non Affectés -->
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">❤️ Étudiants Non Affectés</h6>
            </div>
            <div class="card-body">
                <input type="number" name="amount_non_affecte[]" 
                       class="form-control" step="0.01" min="0"
                       placeholder="Montant non affectés">
            </div>
        </div>
    </div>
</div>
```

**Fonctionnalités supplémentaires :**
- Bouton "Copier sur tous" pour dupliquer un montant sur les 3 statuts
- Validation JavaScript en temps réel
- Aperçu des différences de tarifs
- Sauvegarde intelligente (ne sauvegarde que les champs modifiés)

### 2. Page Affichage : `/esbtp/frais/show`

**Tableau avec tarification différenciée :**

```html
<table class="table table-striped">
    <thead>
        <tr>
            <th>Catégorie de Frais</th>
            <th class="text-success">💚 Affectés</th>
            <th class="text-warning">🧡 Réaffectés</th>
            <th class="text-danger">❤️ Non Affectés</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($fraisConfigurations as $config)
        <tr>
            <td>{{ $config->fraisCategory->nom }}</td>
            <td class="text-success font-weight-bold">
                {{ number_format($config->amount_affecte ?? $config->amount, 0, ',', ' ') }} FCFA
            </td>
            <td class="text-warning font-weight-bold">
                {{ number_format($config->amount_reaffecte ?? $config->amount, 0, ',', ' ') }} FCFA
            </td>
            <td class="text-danger font-weight-bold">
                {{ number_format($config->amount_non_affecte ?? $config->amount, 0, ',', ' ') }} FCFA
            </td>
            <td>
                <a href="{{ route('esbtp.frais.configure', [$config->filiere_id, $config->niveau_id]) }}" 
                   class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i>
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

**Indicateurs visuels :**
- Badges colorés pour différencier les statuts
- Highlight des différences de montants
- Total par statut d'affectation
- Graphique de répartition des tarifs

### 3. Page Index : `/esbtp/frais/index`

**Statistiques mises à jour :**

```php
// Comptage des variantes configurées
$stats = [
    'total_configurations' => ESBTPFraisConfiguration::count(),
    'configurations_differentiees' => ESBTPFraisConfiguration::whereNotNull('amount_affecte')
                                      ->orWhereNotNull('amount_reaffecte')
                                      ->orWhereNotNull('amount_non_affecte')
                                      ->count(),
    'montant_moyen_affectes' => ESBTPFraisConfiguration::avg('amount_affecte'),
    'montant_moyen_reaffectes' => ESBTPFraisConfiguration::avg('amount_reaffecte'),
    'montant_moyen_non_affectes' => ESBTPFraisConfiguration::avg('amount_non_affecte'),
];
```

**Widgets d'affichage :**
- Cartes statistiques par statut d'affectation
- Graphique de répartition des montants
- Tableau de bord avec filtres par statut
- Alertes pour configurations incomplètes

## ⚙️ Modifications du Contrôleur

### ESBTPFraisController

**Méthode `store()` mise à jour :**
```php
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'filiere_id' => 'required|exists:esbtp_filieres,id',
        'niveau_id' => 'required|exists:esbtp_niveaux,id',
        'frais_category_ids' => 'required|array',
        'frais_category_ids.*' => 'exists:esbtp_frais_categories,id',
        'amount_affecte' => 'nullable|array',
        'amount_affecte.*' => 'nullable|numeric|min:0',
        'amount_reaffecte' => 'nullable|array', 
        'amount_reaffecte.*' => 'nullable|numeric|min:0',
        'amount_non_affecte' => 'nullable|array',
        'amount_non_affecte.*' => 'nullable|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
                         ->withErrors($validator)
                         ->withInput();
    }

    foreach ($request->frais_category_ids as $index => $categoryId) {
        ESBTPFraisConfiguration::updateOrCreate(
            [
                'filiere_id' => $request->filiere_id,
                'niveau_id' => $request->niveau_id,
                'frais_category_id' => $categoryId
            ],
            [
                'amount' => $request->amount_affecte[$index] ?? 0, // Rétrocompatibilité
                'amount_affecte' => $request->amount_affecte[$index] ?? null,
                'amount_reaffecte' => $request->amount_reaffecte[$index] ?? null,
                'amount_non_affecte' => $request->amount_non_affecte[$index] ?? null,
            ]
        );
    }

    return redirect()->route('esbtp.frais.show', [$request->filiere_id, $request->niveau_id])
                     ->with('success', 'Configuration des frais mise à jour avec succès');
}
```

**Nouvelle méthode `getFraisByClasseAndStatus()` :**
```php
public function getFraisByClasseAndStatus($classeId, Request $request)
{
    $affectationStatus = $request->get('affectation_status', 'affecté');
    
    $classe = ESBTPClasse::findOrFail($classeId);
    
    $fraisConfigurations = ESBTPFraisConfiguration::where('filiere_id', $classe->filiere_id)
                                                  ->where('niveau_id', $classe->niveau_id)
                                                  ->with('fraisCategory')
                                                  ->get();
    
    $fraisData = [];
    foreach ($fraisConfigurations as $config) {
        $fraisData[] = [
            'id' => $config->frais_category_id,
            'nom' => $config->fraisCategory->nom,
            'montant' => $config->getMontantByStatus($affectationStatus),
            'obligatoire' => $config->fraisCategory->obligatoire,
        ];
    }
    
    return response()->json([
        'frais' => $fraisData,
        'statut_affectation' => $affectationStatus,
        'classe' => [
            'id' => $classe->id,
            'nom' => $classe->nom,
            'filiere' => $classe->filiere->nom,
            'niveau' => $classe->niveau->nom,
        ]
    ]);
}
```

## 🎨 Interface Utilisateur

### Conventions visuelles

**Couleurs par statut :**
- 💚 **Affecté** : Vert (`#28a745`, `success`)
- 🧡 **Réaffecté** : Orange (`#ffc107`, `warning`)
- ❤️ **Non Affecté** : Rouge (`#dc3545`, `danger`)

**Éléments d'interface :**
```css
/* Cartes par statut */
.card-affecte { border-color: #28a745; }
.card-reaffecte { border-color: #ffc107; }
.card-non-affecte { border-color: #dc3545; }

/* Badges de montants */
.badge-montant-affecte { background-color: #28a745; }
.badge-montant-reaffecte { background-color: #ffc107; color: #000; }
.badge-montant-non-affecte { background-color: #dc3545; }

/* Inputs par statut */
.input-affecte:focus { border-color: #28a745; box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25); }
.input-reaffecte:focus { border-color: #ffc107; box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25); }
.input-non-affecte:focus { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25); }
```

### JavaScript pour l'interactivité

```javascript
// Copier un montant sur tous les statuts
function copierMontantSurTous(montant, index) {
    document.querySelector(`input[name="amount_affecte[${index}]"]`).value = montant;
    document.querySelector(`input[name="amount_reaffecte[${index}]"]`).value = montant;
    document.querySelector(`input[name="amount_non_affecte[${index}]"]`).value = montant;
}

// Calculer les totaux en temps réel
function calculerTotaux() {
    let totalAffecte = 0, totalReaffecte = 0, totalNonAffecte = 0;
    
    document.querySelectorAll('input[name^="amount_affecte"]').forEach(input => {
        totalAffecte += parseFloat(input.value) || 0;
    });
    
    document.querySelectorAll('input[name^="amount_reaffecte"]').forEach(input => {
        totalReaffecte += parseFloat(input.value) || 0;
    });
    
    document.querySelectorAll('input[name^="amount_non_affecte"]').forEach(input => {
        totalNonAffecte += parseFloat(input.value) || 0;
    });
    
    // Mise à jour de l'affichage des totaux
    document.getElementById('total-affecte').textContent = totalAffecte.toLocaleString() + ' FCFA';
    document.getElementById('total-reaffecte').textContent = totalReaffecte.toLocaleString() + ' FCFA';
    document.getElementById('total-non-affecte').textContent = totalNonAffecte.toLocaleString() + ' FCFA';
}
```

## 📈 Logique Métier

### Règles de tarification

1. **Fallback intelligent** : Si un montant spécifique n'est pas défini, utiliser le montant principal
2. **Validation cohérente** : Les montants doivent être positifs et cohérents
3. **Traçabilité** : Chaque modification est horodatée et tracée
4. **Flexibilité** : Possibilité de configurer seulement certains statuts

### Cas d'usage typiques

**Scenario 1 - Tarif unique :**
```php
// Configuration simple : même tarif pour tous
$config = new ESBTPFraisConfiguration([
    'amount' => 50000,
    'amount_affecte' => null,     // Utilise amount
    'amount_reaffecte' => null,   // Utilise amount  
    'amount_non_affecte' => null, // Utilise amount
]);
```

**Scenario 2 - Tarifs différenciés :**
```php
// Configuration selon les statuts d'affectation gouvernementale
$config = new ESBTPFraisConfiguration([
    'amount' => 50000,            // Tarif de base (rétrocompatibilité)
    'amount_affecte' => 25000,    // Tarif réduit (subvention étatique)
    'amount_reaffecte' => 25000,  // Tarif réduit (subvention maintenue après réaffectation)
    'amount_non_affecte' => 50000, // Tarif complet (aucune subvention étatique)
]);
```

**Scenario 3 - Tarif partiel :**
```php
// Seuls les réaffectés ont un tarif spécial
$config = new ESBTPFraisConfiguration([
    'amount' => 50000,
    'amount_affecte' => null,     // Utilise amount (50000)
    'amount_reaffecte' => 25000,  // Tarif spécial
    'amount_non_affecte' => null, // Utilise amount (50000)
]);
```

## 🔍 Tests et Validation

### Tests unitaires recommandés

```php
class ESBTPFraisConfigurationTest extends TestCase
{
    public function test_get_montant_by_status_with_specific_amounts()
    {
        $config = ESBTPFraisConfiguration::factory()->create([
            'amount' => 50000,
            'amount_affecte' => 50000,
            'amount_reaffecte' => 30000,
            'amount_non_affecte' => 20000,
        ]);
        
        $this->assertEquals(50000, $config->getMontantByStatus('affecté'));
        $this->assertEquals(30000, $config->getMontantByStatus('réaffecté'));
        $this->assertEquals(20000, $config->getMontantByStatus('non_affecté'));
    }
    
    public function test_fallback_to_main_amount()
    {
        $config = ESBTPFraisConfiguration::factory()->create([
            'amount' => 50000,
            'amount_affecte' => null,
            'amount_reaffecte' => null,
            'amount_non_affecte' => null,
        ]);
        
        $this->assertEquals(50000, $config->getMontantByStatus('affecté'));
        $this->assertEquals(50000, $config->getMontantByStatus('réaffecté'));
        $this->assertEquals(50000, $config->getMontantByStatus('non_affecté'));
    }
}
```

### Tests d'intégration

- Configuration de frais avec statuts différents
- Récupération des montants selon le statut d'inscription
- Interface utilisateur avec 3 colonnes
- Calculs automatiques des totaux
- Validation des formulaires

## 🚀 Déploiement

### Checklist de mise en production

1. ✅ Exécuter la migration de base de données
2. ⏳ Mettre à jour le modèle ESBTPFraisConfiguration
3. ⏳ Modifier le contrôleur ESBTPFraisController
4. ⏳ Mettre à jour les vues configure.blade.php et show.blade.php
5. ⏳ Tester les API de récupération des frais
6. ⏳ Valider les calculs et l'affichage
7. ⏳ Former les utilisateurs sur les nouvelles fonctionnalités

### Monitoring post-déploiement

- Surveiller les performances des requêtes avec les nouveaux index
- Vérifier la cohérence des données migrées
- Contrôler l'utilisation des nouvelles fonctionnalités
- Collecter les retours utilisateurs

---

**Version** : 1.0  
**Date de création** : 13 septembre 2025  
**Module** : Gestion des Frais ESBTP  
**Statut** : En développement