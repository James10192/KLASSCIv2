# Guide des Inscriptions avec Statut d'Affectation KLASSCI

## 📝 Vue d'ensemble

Ce guide détaille les modifications apportées au module d'inscriptions KLASSCI pour supporter l'enregistrement et la gestion des statuts d'affectation gouvernementale des étudiants selon le système ivoirien (MESRS).

## 🎯 Fonctionnalités Ajoutées

### 1. **Sélection du Statut lors de l'Inscription**

#### Page : `/esbtp/inscriptions/create`

**Nouveau champ ajouté :**
```html
<div class="form-group">
    <label for="affectation_status">Statut d'affectation</label>
    <select name="affectation_status" class="form-control" required>
        <option value="affecté" selected>Affecté</option>
        <option value="réaffecté">Réaffecté</option>
        <option value="non_affecté">Non Affecté</option>
    </select>
    <small class="form-text text-muted">
        Le statut d'affectation gouvernementale détermine la prise en charge étatique et les frais applicables.
    </small>
</div>
```

**Impact sur la récupération des frais :**
- La requête AJAX pour récupérer les frais inclut maintenant le paramètre `affectation_status`
- Route modifiée : `/esbtp/inscriptions/frais-by-classe/{classeId}?affectation_status=affecté`
- Les montants retournés correspondent au statut sélectionné

### 2. **Affichage du Statut d'Affectation**

#### Page : `/esbtp/inscriptions/show`

**Badge d'affichage :**
```php
<span class="badge bg-{{ 
    $inscription->affectation_status === 'affecté' ? 'success' : 
    ($inscription->affectation_status === 'réaffecté' ? 'warning' : 'danger') 
}}">
    {{ ucfirst($inscription->affectation_status) }}
</span>
```

**Couleurs utilisées :**
- 🟢 **Affecté** : Badge vert (`badge success`)
- 🟡 **Réaffecté** : Badge orange (`badge warning`)  
- 🔴 **Non Affecté** : Badge rouge (`badge danger`)

### 3. **Historique des Inscriptions**

#### Page : `/esbtp/etudiants/show`

**Colonne ajoutée dans le tableau des inscriptions :**
- Affichage du statut avec le même système de badges
- Information visible dans l'historique pour traçabilité
- Permet de voir l'évolution du statut d'un étudiant

## 🔧 Modifications Techniques

### Modèle ESBTPInscription

**Champs ajoutés :**
```php
protected $fillable = [
    // ... champs existants
    'affectation_status',
];

protected $casts = [
    // ... casts existants
    'affectation_status' => 'string',
];
```

**Nouveaux scopes :**
```php
public function scopeAffectes($query)
{
    return $query->where('affectation_status', 'affecté');
}

public function scopeReaffectes($query)  
{
    return $query->where('affectation_status', 'réaffecté');
}

public function scopeNonAffectes($query)
{
    return $query->where('affectation_status', 'non_affecté');
}
```

### Contrôleur ESBTPInscriptionController

**Méthode `store()` modifiée :**
```php
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        // ... validations existantes
        'affectation_status' => 'required|in:affecté,réaffecté,non_affecté',
    ]);
    
    // ... reste de la logique
}
```

**Méthode `getFraisByClasse()` modifiée :**
```php
public function getFraisByClasse($classeId, Request $request)
{
    $affectationStatus = $request->get('affectation_status', 'affecté');
    
    // Logique pour récupérer les frais selon le statut
    $frais = $this->fraisService->getFraisByClasseAndStatus($classeId, $affectationStatus);
    
    return response()->json($frais);
}
```

## 📊 Impact sur les Statistiques

### Nouveaux indicateurs disponibles :

1. **Répartition par statut :**
   ```php
   $stats = [
       'total_affectes' => ESBTPInscription::affectes()->count(),
       'total_reaffectes' => ESBTPInscription::reaffectes()->count(), 
       'total_non_affectes' => ESBTPInscription::nonAffectes()->count(),
   ];
   ```

2. **Revenus par statut :**
   - Calcul des revenus prévisionnels selon les statuts
   - Analyses financières différenciées
   - Reporting par catégorie d'étudiants

## 🎨 Design et UX

### Principes appliqués :
- **Cohérence visuelle** : Mêmes couleurs dans toute l'application
- **Clarté** : Libellés explicites pour chaque statut
- **Feedback** : Messages d'aide contextuelle
- **Responsive** : Interface adaptée mobile et desktop

### Accessibilité :
- Contraste suffisant pour tous les badges
- Labels explicites sur les formulaires
- Navigation clavier fonctionnelle
- Textes alternatifs pour les éléments visuels

## 🔄 Processus Métier

### Workflow d'inscription :
1. **Saisie** : L'utilisateur sélectionne le statut d'affectation
2. **Validation** : Le système valide le statut choisi
3. **Calcul** : Les frais sont calculés selon le statut
4. **Affichage** : Le récapitulatif montre les montants applicables
5. **Sauvegarde** : L'inscription est créée avec le statut choisi

### Règles de gestion :
- **Défaut** : Statut "affecté" par défaut pour rétrocompatibilité
- **Modification** : Le statut peut être modifié après création
- **Historique** : Tous les changements sont tracés
- **Cohérence** : Les frais s'ajustent automatiquement au statut

## 🧪 Tests Recommandés

### Tests unitaires :
```php
public function test_inscription_creation_with_affectation_status()
{
    $data = [
        'affectation_status' => 'réaffecté',
        // ... autres données
    ];
    
    $inscription = ESBTPInscription::create($data);
    
    $this->assertEquals('réaffecté', $inscription->affectation_status);
}
```

### Tests d'intégration :
- Création d'inscription avec chaque statut
- Récupération correcte des frais par statut  
- Affichage des badges dans les vues
- Fonctionnement des scopes de requête

## 📚 Documentation Utilisateur

### Pour les secrétaires :
- **Quand utiliser chaque statut ?**
  - **Affecté** : Étudiant affecté par l'État via bac.mesrs-ci.net (subvention possible)
  - **Réaffecté** : Étudiant réaffecté par la DOB après demande de changement (subvention maintenue)  
  - **Non affecté** : Étudiant inscrit directement sans affectation étatique (tarif complet)

### Formation requise :
- Comprendre l'impact du statut sur les frais
- Savoir modifier le statut si nécessaire
- Interpréter les badges colorés
- Utiliser les filtres par statut

---

**Version** : 1.0  
**Dernière mise à jour** : 13 septembre 2025