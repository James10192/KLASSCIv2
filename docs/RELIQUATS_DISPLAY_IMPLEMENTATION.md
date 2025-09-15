# Implémentation de l'affichage des reliquats

## Problème résolu

L'utilisateur a signalé que les reliquats des inscriptions précédentes n'étaient pas visibles sur les fiches étudiants et inscriptions après une réinscription. Cela posait un problème de transparence financière car les montants dus des années antérieures n'étaient pas clairement identifiables.

## Solution implémentée

### 0. Création automatique des reliquats lors de la réinscription

**Problème identifié :** La méthode `effectuerReinscription` ne créait pas de reliquats lors de la réinscription.

**Solution :** Ajout de la méthode `creerReliquatsSiNecessaire()` dans `ReeinscriptionService`

```php
// Dans effectuerReinscription(), après la création de la nouvelle inscription
$this->creerReliquatsSiNecessaire($inscriptionActuelle, $nouvelleInscription);
```

**Logique de création des reliquats :**
1. Récupère tous les frais souscrits de l'inscription source
2. Pour chaque frais, calcule le montant attendu vs le montant payé
3. Si il y a un solde impayé, crée un `ESBTPReliquatDetail`
4. Le reliquat est lié à l'inscription source et destination
5. Log détaillé pour traçabilité

### 1. Modifications des contrôleurs

#### ESBTPEtudiantController@show
```php
// Récupérer les reliquats de l'étudiant
$inscriptionIds = $etudiant->inscriptions->pluck('id');

// Reliquats entrants (provenant d'inscriptions précédentes)
$reliquatsEntrants = \App\Models\ESBTPReliquatDetail::whereIn('inscription_destination_id', $inscriptionIds)
    ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisConfiguration'])
    ->actifs()
    ->get();

// Reliquats sortants (transférés vers des inscriptions futures)
$reliquatsSortants = \App\Models\ESBTPReliquatDetail::whereIn('inscription_source_id', $inscriptionIds)
    ->with(['inscriptionDestination.anneeUniversitaire', 'fraisSubscription.fraisConfiguration'])
    ->get();
```

**Nouvelles statistiques ajoutées :**
- `total_reliquats_entrants` : Montant total des reliquats à payer
- `total_reliquats_sortants` : Montant total des reliquats transférés
- `nombre_reliquats_actifs` : Nombre de reliquats non soldés

#### ESBTPInscriptionController@show
```php
// Récupérer les reliquats pour cette inscription spécifique
$reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
    ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisConfiguration'])
    ->actifs()
    ->get();

$reliquatsSortants = \App\Models\ESBTPReliquatDetail::where('inscription_source_id', $inscription->id)
    ->with(['inscriptionDestination.anneeUniversitaire', 'fraisSubscription.fraisConfiguration'])
    ->get();
```

### 2. Modifications des vues

#### resources/views/esbtp/etudiants/show.blade.php
Ajout d'une section "Reliquats" dans le résumé financier :

```blade
{{-- Section Reliquats --}}
@if($statistiques['total_reliquats_entrants'] > 0 || $statistiques['total_reliquats_sortants'] > 0)
<div class="mt-4">
    <div class="section-title mb-md">
        <i class="fas fa-exchange-alt"></i>Reliquats
    </div>

    @if($statistiques['total_reliquats_entrants'] > 0)
    <div class="alert alert-warning">
        <h6><i class="fas fa-arrow-right me-2"></i>Reliquats à payer</h6>
        <p class="mb-2">Montant dû des inscriptions précédentes: <strong>{{ number_format($statistiques['total_reliquats_entrants'], 0, ',', ' ') }} FCFA</strong></p>
        <small class="text-muted">{{ $statistiques['nombre_reliquats_actifs'] }} reliquat(s) actif(s)</small>
    </div>
    @endif

    @if($statistiques['total_reliquats_sortants'] > 0)
    <div class="alert alert-info">
        <h6><i class="fas fa-arrow-left me-2"></i>Reliquats transférés</h6>
        <p class="mb-0">Montant transféré vers les inscriptions futures: <strong>{{ number_format($statistiques['total_reliquats_sortants'], 0, ',', ' ') }} FCFA</strong></p>
    </div>
    @endif
</div>
@endif
```

#### resources/views/esbtp/inscriptions/show.blade.php
Ajout d'une section détaillée "Reliquats liés à cette inscription" :

```blade
{{-- Section Reliquats --}}
@if(isset($reliquatsEntrants) && $reliquatsEntrants->count() > 0 || isset($reliquatsSortants) && $reliquatsSortants->count() > 0)
    <div class="card-moderne mt-4">
        <div class="p-lg">
            <div class="section-title mb-md">
                <i class="fas fa-exchange-alt"></i>Reliquats liés à cette inscription
            </div>

            {{-- Tables détaillées pour les reliquats entrants et sortants --}}
        </div>
    </div>
@endif
```

## Fonctionnalités

### Vue étudiant (etudiants.show)
- **Affichage synthétique** : Montants totaux des reliquats entrants et sortants
- **Indicateurs visuels** : Alertes colorées (warning pour les dettes, info pour les transferts)
- **Compteurs** : Nombre de reliquats actifs

### Vue inscription (inscriptions.show)
- **Affichage détaillé** : Tableaux complets avec tous les détails des reliquats
- **Traçabilité** : Année source/destination, type de frais, montants
- **Statuts** : Badges colorés pour le statut de chaque reliquat
- **Totaux** : Récapitulatifs en pied de tableau

## Types de reliquats affichés

### Reliquats entrants
- **Définition** : Montants dus d'inscriptions précédentes
- **Source** : `inscription_destination_id` = inscription actuelle
- **Utilité** : Voir ce que l'étudiant doit encore payer des années antérieures

### Reliquats sortants
- **Définition** : Montants transférés vers des inscriptions futures
- **Source** : `inscription_source_id` = inscription actuelle
- **Utilité** : Voir ce qui a été reporté vers les années suivantes

## Modèle de données utilisé

### ESBTPReliquatDetail
- `inscription_source_id` : Inscription d'origine du reliquat
- `inscription_destination_id` : Inscription de destination du reliquat
- `montant_reliquat` : Montant total du reliquat
- `montant_regle` : Montant déjà réglé
- `solde_restant` : Montant restant à payer (calculé)
- `statut` : État du reliquat (actif, partiellement_regle, soldé)

## Impact sur l'expérience utilisateur

### Avant
- ❌ Reliquats invisibles sur les fiches
- ❌ Pas de traçabilité des dettes antérieures
- ❌ Risque d'oubli des montants dus

### Après
- ✅ Reliquats clairement visibles
- ✅ Détail complet par inscription
- ✅ Traçabilité entre les années universitaires
- ✅ Statuts clairs pour le suivi des paiements

## Cas d'usage spécifique

**Étudiant concerné :** ABOUANOU KOUAME SIESMO MELCHISEDECK (MESBTP24-0260)

Après réinscription, l'utilisateur pourra maintenant voir :
1. Sur la fiche étudiant : Le total des reliquats en cours
2. Sur chaque fiche inscription : Le détail des reliquats entrants et sortants
3. L'assurance que les inscriptions précédentes ne deviennent pas "obsolètes"

## Sécurité et performance

- **Eager loading** : Relations préchargées pour éviter le problème N+1
- **Scope actifs** : Seuls les reliquats non soldés sont comptés dans les statistiques
- **Affichage conditionnel** : Sections cachées si aucun reliquat

---
*Documentation créée le {{ date('Y-m-d H:i:s') }}*