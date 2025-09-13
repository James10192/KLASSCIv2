# Système de Frais par Statut d'Affectation - KLASSCI

## Vue d'ensemble

Le système KLASSCI SAAS supporte désormais la gestion différenciée des frais en fonction du statut d'affectation des étudiants dans le cadre du système éducatif ivoirien post-BAC (MESRS).

### Contexte Ivoirien

En Côte d'Ivoire, les étudiants post-BAC peuvent avoir différents statuts d'affectation qui impactent les frais de scolarité :

- **Affecté** : Étudiant placé directement par l'État via bac.mesrs-ci.net (éligible aux subventions)
- **Réaffecté** : Étudiant réassigné par la DOB (Direction de l'Orientation et des Bourses) suite à une demande (subvention maintenue)
- **Non affecté** : Étudiant inscrit directement sans affectation étatique (frais complets)

## Architecture Technique

### Base de Données

#### Table `esbtp_inscriptions`
```sql
-- Nouveau champ pour le statut d'affectation
ALTER TABLE esbtp_inscriptions ADD COLUMN affectation_status ENUM('affecté', 'réaffecté', 'non_affecté') DEFAULT NULL;
ALTER TABLE esbtp_inscriptions ADD INDEX idx_affectation_status (affectation_status);
```

#### Table `esbtp_frais_configurations`
```sql
-- Nouveaux champs pour les montants différenciés
ALTER TABLE esbtp_frais_configurations ADD COLUMN amount_affecte DECIMAL(15,2) NULL COMMENT 'Montant pour étudiants affectés';
ALTER TABLE esbtp_frais_configurations ADD COLUMN amount_reaffecte DECIMAL(15,2) NULL COMMENT 'Montant pour étudiants réaffectés';
ALTER TABLE esbtp_frais_configurations ADD COLUMN amount_non_affecte DECIMAL(15,2) NULL COMMENT 'Montant pour étudiants non affectés';
```

### Modèles

#### ESBTPInscription
```php
// Nouveau champ dans $fillable
protected $fillable = [
    'affectation_status',
    // ... autres champs
];

// Nouveaux scopes
public function scopeAffectes($query) {
    return $query->where('affectation_status', 'affecté');
}

public function scopeReaffectes($query) {
    return $query->where('affectation_status', 'réaffecté');
}

public function scopeNonAffectes($query) {
    return $query->where('affectation_status', 'non_affecté');
}
```

#### ESBTPFraisConfiguration
```php
// Nouveaux champs dans $fillable
protected $fillable = [
    'amount_affecte',
    'amount_reaffecte', 
    'amount_non_affecte',
    // ... autres champs
];

// Méthode clé pour récupérer le montant selon le statut
public function getMontantByStatus($affectationStatus)
{
    return match($affectationStatus) {
        'affecté' => $this->amount_affecte ?? $this->amount,
        'réaffecté' => $this->amount_reaffecte ?? $this->amount,
        'non_affecté' => $this->amount_non_affecte ?? $this->amount,
        default => $this->amount
    };
}

// Vérifier si des montants différenciés sont configurés
public function hasDifferentiatedAmounts()
{
    return !is_null($this->amount_affecte) || 
           !is_null($this->amount_reaffecte) || 
           !is_null($this->amount_non_affecte);
}
```

## Interface Utilisateur

### Page de Configuration des Frais (`/esbtp/frais/configure`)

L'interface affiche maintenant 3 colonnes pour chaque catégorie de frais :

```html
<div class="row">
    <div class="col-md-4">
        <label>Affecté (État)</label>
        <input type="number" name="categories[{{$category->id}}][amount_affecte]" 
               class="form-control" placeholder="Montant subventionné">
    </div>
    <div class="col-md-4">
        <label>Réaffecté (DOB)</label>
        <input type="number" name="categories[{{$category->id}}][amount_reaffecte]" 
               class="form-control" placeholder="Montant subventionné">
    </div>
    <div class="col-md-4">
        <label>Non affecté</label>
        <input type="number" name="categories[{{$category->id}}][amount_non_affecte]" 
               class="form-control" placeholder="Montant complet">
    </div>
</div>
```

**Fonctionnalités de l'interface :**
- Copie automatique entre colonnes
- Validation côté client
- Affichage des variantes configurées
- Support du multi-tenant SAAS

### Page d'Inscription Étudiant (`/esbtp/inscriptions/create`)

Nouveau champ de sélection du statut d'affectation :

```html
<div class="form-group">
    <label for="affectation_status">Statut d'affectation</label>
    <select name="affectation_status" id="affectation_status" class="form-control" required>
        <option value="affecté">Affecté</option>
        <option value="réaffecté">Réaffecté</option>
        <option value="non_affecté">Non affecté</option>
    </select>
</div>
```

**Comportement dynamique :**
- Les frais se mettent à jour automatiquement selon la sélection
- Appel AJAX vers `getFraisByClasse()` avec paramètre `affectation_status`
- Affichage des montants appropriés en temps réel

### Pages d'Affichage

#### Détails Inscription (`/esbtp/inscriptions/show`)
```html
<tr>
    <th>Statut d'affectation</th>
    <td>
        @switch($inscription->affectation_status)
            @case('affecté')
                <span class="badge bg-success">
                    <i class="fas fa-check-circle me-1"></i>Affecté
                </span>
            @case('réaffecté')
                <span class="badge bg-warning">
                    <i class="fas fa-exchange-alt me-1"></i>Réaffecté
                </span>
            @case('non_affecté')
                <span class="badge bg-danger">
                    <i class="fas fa-times-circle me-1"></i>Non affecté
                </span>
        @endswitch
    </td>
</tr>
```

## API et Contrôleurs

### ESBTPFraisController

#### Méthode `updateConfiguration()`
- Supporte la rétrocompatibilité avec l'ancien champ `amount`
- Valide les nouveaux champs `amount_affecte`, `amount_reaffecte`, `amount_non_affecte`
- Calcule automatiquement le montant principal pour la compatibilité

```php
// Validation étendue
$validator = Validator::make($request->all(), [
    'categories.*.amount' => 'nullable|numeric|min:0',
    'categories.*.amount_affecte' => 'nullable|numeric|min:0',
    'categories.*.amount_reaffecte' => 'nullable|numeric|min:0',
    'categories.*.amount_non_affecte' => 'nullable|numeric|min:0',
]);
```

### ESBTPInscriptionController

#### Méthode `getFraisByClasse()`
Modifiée pour accepter le paramètre `affectation_status` :

```php
public function getFraisByClasse($classeId, Request $request)
{
    $affectationStatus = $request->get('affectation_status', 'affecté');
    
    // Utilisation de getMontantByStatus() pour le montant approprié
    if ($configuration) {
        $defaultAmount = $configuration->getMontantByStatus($affectationStatus);
    }
}
```

## Exemples d'Utilisation

### Configuration de Frais Différenciés

```php
// Exemple de configuration pour "Frais d'inscription"
ESBTPFraisConfiguration::create([
    'frais_category_id' => 1,
    'filiere_id' => 1,
    'niveau_id' => 1,
    'amount' => 50000, // Montant principal (non affecté)
    'amount_affecte' => 30000, // 40% de réduction (subvention État)
    'amount_reaffecte' => 35000, // 30% de réduction (subvention DOB)
    'amount_non_affecte' => 50000, // Montant complet
]);
```

### Récupération du Montant selon le Statut

```php
$configuration = ESBTPFraisConfiguration::where('frais_category_id', 1)
    ->where('filiere_id', 1)
    ->where('niveau_id', 1)
    ->first();

$montantAffecte = $configuration->getMontantByStatus('affecté'); // 30000
$montantReaffecte = $configuration->getMontantByStatus('réaffecté'); // 35000
$montantNonAffecte = $configuration->getMontantByStatus('non_affecté'); // 50000
```

## Migration et Déploiement

### Étapes de Migration

1. **Appliquer les migrations :**
   ```bash
   php artisan migrate
   ```

2. **Vérifier les nouvelles colonnes :**
   ```php
   Schema::hasColumn('esbtp_inscriptions', 'affectation_status')
   Schema::hasColumn('esbtp_frais_configurations', 'amount_affecte')
   ```

3. **Tester la configuration :**
   - Accéder à `/esbtp/frais/configure`
   - Configurer les frais avec 3 montants
   - Tester l'inscription avec différents statuts

### Rétrocompatibilité

Le système maintient une compatibilité totale :
- Les anciennes configurations continuent de fonctionner
- Le champ `amount` reste utilisé comme fallback
- Aucune modification des inscriptions existantes requise

## Bonnes Pratiques

### Configuration Recommandée

1. **Affecté (État)** : 60-70% du montant complet
2. **Réaffecté (DOB)** : 70-80% du montant complet  
3. **Non affecté** : 100% du montant complet

### Validation Métier

- Au moins un montant doit être défini par configuration
- Les montants affectés/réaffectés doivent être inférieurs au montant non affecté
- Vérifier la cohérence avec les politiques de subvention gouvernementales

### Performance

- Index sur `affectation_status` pour les requêtes rapides
- Cache des configurations par filière/niveau
- Optimisation des requêtes AJAX pour les formulaires

## Support Multi-tenant

Le système supporte parfaitement l'architecture SAAS multi-tenant :
- Configuration par établissement
- Personnalisation des statuts selon les accords institutionnels
- Gestion centralisée des politiques de frais
- Rapports consolidés par tenant

## Conclusion

Ce système permet aux établissements ivoiriens utilisant KLASSCI de gérer efficacement les différents statuts d'affectation tout en maintenant la flexibilité nécessaire pour un système SAAS multi-tenant.

---

*Documentation mise à jour le 13 septembre 2025*
*Version : 2.0*
*Auteur : Système KLASSCI SAAS*