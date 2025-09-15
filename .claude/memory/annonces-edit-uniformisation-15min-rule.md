# Uniformisation Design Annonces Edit + Règle 15 Minutes

## 🎯 **Objectifs Implémentés**

### 1. ✅ **Uniformisation Design Edit/Create**
- Design `annonces.edit` identique à `annonces.create`
- Même styles CSS ACASI (540+ lignes)
- Même structure HTML avec colonnes 8/4
- Même fonctionnalités JavaScript (Choices.js)
- Gestion complète des destinataires (général/classe/étudiant)

### 2. ✅ **Règle 15 Minutes de Modification**
- Après 15 minutes de publication : modification impossible
- Boutons "Modifier" grisés/désactivés
- Messages explicatifs pour l'utilisateur
- Suggestion : supprimer et refaire l'annonce

## 🔧 **Modifications Techniques**

### **A. Contrôleur ESBTPAnnonceController**

#### Méthode helper ajoutée :
```php
/**
 * Vérifie si une annonce peut encore être modifiée (< 15 min après publication)
 */
private function canEditAnnonce($annonce)
{
    if (!$annonce->is_published) {
        return true; // Brouillons toujours modifiables
    }

    $publishedAt = $annonce->created_at;
    if ($annonce->date_publication && $annonce->date_publication > $annonce->created_at) {
        $publishedAt = $annonce->date_publication;
    }

    return $publishedAt->diffInMinutes(now()) <= 15;
}
```

#### Méthodes edit() et update() modifiées :
```php
public function edit(ESBTPAnnonce $annonce)
{
    // Vérification règle 15 minutes
    if (!$this->canEditAnnonce($annonce)) {
        $minutesElapsed = $annonce->created_at->diffInMinutes(now());
        return redirect()->route('esbtp.annonces.show', $annonce)
            ->with('error', "Cette annonce ne peut plus être modifiée (publiée il y a {$minutesElapsed} minutes). Vous pouvez la supprimer et en créer une nouvelle.");
    }

    // Chargement des données avec même structure que create
    $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
    $etudiants = ESBTPEtudiant::with('classe')
        ->whereHas('classe')
        ->distinct()
        ->orderBy('nom')
        ->orderBy('prenoms')
        ->get();
    $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
    $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get();

    return view('esbtp.annonces.edit', compact('annonce', 'classes', 'etudiants', 'filieres', 'niveaux'));
}

public function update(Request $request, ESBTPAnnonce $annonce)
{
    // Double vérification règle 15 minutes
    if (!$this->canEditAnnonce($annonce)) {
        return redirect()->route('esbtp.annonces.show', $annonce)
            ->with('error', 'Cette annonce ne peut plus être modifiée.');
    }

    // Validation et mise à jour...
}
```

### **B. Vue edit.blade.php - Refonte Complète**

#### Structure identique à create.blade.php :
```php
@extends('layouts.app')

@section('title', 'Modifier l\'annonce : ' . $annonce->titre . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

<style>
    /* STYLES ACASI COMPLETS - 540+ lignes identiques à create */
    .hover-card { ... }
    .form-control, .form-select { ... }
    .choices { ... }
    /* ... tous les styles CSS ACASI ... */
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section identique -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier l'annonce</h1>
                <p class="header-subtitle">{{ $annonce->titre }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-acasi secondary">
                    <i class="fas fa-eye"></i>Voir l'annonce
                </a>
                <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <form action="{{ route('esbtp.annonces.update', $annonce) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-8">
                    <!-- Informations générales - identique à create -->
                    <div class="main-card mb-4">...</div>

                    <!-- Destinataires - NOUVEAU dans edit -->
                    <div class="main-card mb-4">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-users"></i>
                                Destinataires
                            </div>
                        </div>
                        <div class="main-card-body">
                            <!-- Radio buttons type + sélecteurs Choices.js -->
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Options publication + Actions -->
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<!-- JavaScript Choices.js complet - identique à create -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    // 400+ lignes de JavaScript identiques à create
</script>
@endpush
```

### **C. Modifications Boutons Edit**

#### **index.blade.php** - Lignes 576-594 :
```php
@php
    $canEdit = true;
    if ($annonce->is_published) {
        $publishedAt = $annonce->date_publication && $annonce->date_publication > $annonce->created_at
            ? $annonce->date_publication
            : $annonce->created_at;
        $canEdit = $publishedAt->diffInMinutes(now()) <= 15;
    }
@endphp

@if($canEdit)
    <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="btn-action secondary" title="Modifier">
        <i class="fas fa-edit"></i>
    </a>
@else
    <button class="btn-action secondary disabled" disabled title="Modification impossible (plus de 15 minutes)">
        <i class="fas fa-edit text-muted"></i>
    </button>
@endif
```

#### **show.blade.php** - Lignes 450-477 + Alert lignes 480-489 :
```php
@php
    $canEdit = true;
    $minutesElapsed = 0;
    if ($annonce->is_published) {
        $publishedAt = $annonce->date_publication && $annonce->date_publication > $annonce->created_at
            ? $annonce->date_publication
            : $annonce->created_at;
        $minutesElapsed = $publishedAt->diffInMinutes(now());
        $canEdit = $minutesElapsed <= 15;
    }
@endphp

@if($canEdit)
    <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="btn-acasi secondary">
        <i class="fas fa-edit"></i>
        Modifier l'annonce
    </a>
@else
    <button class="btn-acasi secondary disabled" disabled>
        <i class="fas fa-edit"></i>
        Modification impossible
    </button>
@endif

<!-- Message d'alerte informatif -->
@if(!$canEdit && $annonce->is_published)
    <div class="alert-modern warning mb-3">
        <i class="fas fa-clock"></i>
        <div>
            <h4>Modification impossible</h4>
            <p>Cette annonce ne peut plus être modifiée (publiée il y a {{ $minutesElapsed }} minutes).
               Vous pouvez la supprimer et en créer une nouvelle si nécessaire.</p>
        </div>
    </div>
@endif
```

#### **D. Styles CSS pour boutons disabled** :

**index.blade.php** - Lignes 341-352 :
```css
.btn-action.disabled {
    background: #f9fafb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    opacity: 0.5;
}

.btn-action.disabled:hover {
    background: #f9fafb !important;
    color: #9ca3af !important;
    transform: none !important;
}
```

**show.blade.php** - Lignes 397-410 :
```css
.btn-acasi.disabled {
    background-color: #f9fafb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    opacity: 0.5;
    border-color: #e5e7eb !important;
}

.btn-acasi.disabled:hover {
    background-color: #f9fafb !important;
    color: #9ca3af !important;
    transform: none !important;
    box-shadow: none !important;
}
```

## 🎨 **Fonctionnalités Ajoutées à Edit**

### **1. Gestion Complète des Destinataires**
- Radio buttons : Tous étudiants / Classes spécifiques / Étudiants spécifiques
- Sélecteurs multiples avec Choices.js
- Filtrage par filière/niveau pour classes
- Filtrage par classe pour étudiants
- Pré-sélection des destinataires actuels

### **2. Interface Moderne ACASI**
- Styles CSS complets (hover effects, animations)
- Form inputs stylisés identiques à create
- Responsive design avec colonnes Bootstrap
- Messages d'erreur stylisés
- Boutons d'action cohérents

### **3. JavaScript Avancé**
- Choices.js pour sélecteurs multiples
- Gestion dynamique affichage conteneurs
- Filtrage en temps réel
- Validation côté client
- Animations et transitions

### **4. Règle 15 Minutes**
- Calcul précis du délai depuis publication
- Messages informatifs détaillés
- Blocage côté contrôleur et interface
- Suggestion alternative (supprimer/recréer)

## 📊 **Impact Utilisateur**

### **Avant (edit basique) :**
- ❌ Design minimal et incohérent
- ❌ Pas de gestion destinataires
- ❌ Modification illimitée dans le temps
- ❌ Expérience utilisateur dégradée

### **Après (edit unifié + règle 15min) :**
- ✅ Design identique et professionnel
- ✅ Gestion complète destinataires
- ✅ Règle métier respectée (15 minutes)
- ✅ Expérience utilisateur cohérente
- ✅ Messages clairs et informatifs

## 🔒 **Règles Métier Implémentées**

1. **Brouillons** : Toujours modifiables (pas de limite de temps)
2. **Annonces publiées** : Modifiables uniquement dans les 15 premières minutes
3. **Dépassement délai** : Boutons grisés + messages explicatifs
4. **Alternative** : Suggestion supprimer/recréer pour modifications tardives
5. **Sécurité** : Double vérification côté contrôleur et interface

### 9. ✅ **Fix Affichage Type Diffusion - show.blade.php Corrigé**

#### Problème résolu :
- ✅ Mauvais affichage du type sur `annonces.show` - **RÉSOLU**
- ✅ "Étudiants spécifiques" au lieu de "Tous les étudiants" - **CORRIGÉ**

#### Cause racine identifiée :
- ✅ Vue `show.blade.php` testait `'globale'` au lieu de `'general'`
- ✅ Incohérence avec contrôleur et autres vues qui utilisent `'general'`
- ✅ Condition ternaire tombait sur cas par défaut (`'etudiant'`)

#### Solution appliquée :

**A. Correction des conditions dans show.blade.php** :
```php
// AVANT (lignes 458-460) - Badge header :
{{ $annonce->type == 'globale' ? 'Tous les étudiants' : (...) }}

// APRÈS - Corrigé :
{{ $annonce->type == 'general' ? 'Tous les étudiants' : (...) }}

// AVANT (lignes 546-552) - Tableau détails :
@if($annonce->type == 'globale')
    Tous les étudiants
@elseif($annonce->type == 'classe')
    Classes spécifiques

// APRÈS - Corrigé :
@if($annonce->type == 'general')
    Tous les étudiants
@elseif($annonce->type == 'classe')
    Classes spécifiques

// AVANT (ligne 604) - Condition destinataires :
@if($annonce->type != 'globale')

// APRÈS - Corrigé :
@if($annonce->type != 'general')
```

#### Validation des mappings corrigés :
- ✅ **`'general'`** → "Tous les étudiants" + icône globe
- ✅ **`'classe'`** → "Classes spécifiques" + icône users
- ✅ **`'etudiant'`** → "Étudiants spécifiques" + icône user

#### Impact résolution :
- ✅ **Cohérence** : Alignement avec contrôleur et autres vues
- ✅ **Affichage correct** : Type diffusion affiché selon réalité
- ✅ **Badge et détails** : Synchronisation complète
- ✅ **Icônes** : Correspondance parfaite avec types

### 10. ✅ **Investigation Notifications SuperAdmin - Mystère Résolu**

#### Problème signalé par l'utilisateur :
- ❌ "Le superadmin a reçu l'annonce alors qu'elle était pour tous les étudiants"
- ❓ Suspicion d'erreur dans la logique d'envoi des notifications

#### Investigation approfondie :

**A. Vérification logique NotificationService** :
- ✅ Méthode `notifyNewAnnouncement()` correcte
- ✅ Pour type `'general'` : `ESBTPEtudiant::whereHas('user')->get()`
- ✅ Seuls les étudiants avec comptes utilisateur reçoivent les notifications

**B. Vérification profil SuperAdmin** :
- ✅ SuperAdmin "MMe Santana" (ID: 1) n'a PAS d'enregistrement ESBTPEtudiant
- ✅ Aucune notification d'annonce dans son historique récent

**C. État actuel de la base de données** :
- 📊 Total étudiants : 2450
- ❌ Étudiants avec user_id : 0
- ❌ Étudiants avec compte utilisateur valide : 0

#### Conclusion définitive :
- ✅ **Logique d'envoi correcte** : Aucune faille dans le système de notifications
- ✅ **SuperAdmin non concerné** : N'a pas de lien avec les enregistrements étudiants
- ✅ **Aucune notification envoyée** : Aucun étudiant n'a de compte utilisateur actuellement
- ✅ **Problème inexistant** : Le cas signalé ne peut pas se produire avec le code actuel

#### Actions prises :
1. ✅ Audit complet du système de notifications
2. ✅ Vérification des relations User/ESBTPEtudiant
3. ✅ Validation de la logique métier
4. ✅ Confirmation que le problème était uniquement l'affichage (déjà corrigé)

---

*Investigation terminée le 2025-01-15 - Aucun problème de logique métier détecté*

### 11. ✅ **Fix Messages SuperAdmin - Problème NavbarController Résolu**

#### Problème racine identifié :
- ❌ **SuperAdmin recevait toutes les annonces** dans ses messages (y compris "Tous les étudiants")
- ❌ **Aucun filtrage par destinataire** dans `NavbarController.getMessages()`
- ❌ **Tous les rôles voyaient toutes les annonces** sans distinction

#### Investigation détaillée :

**A. Analyse du code NavbarController avant correction** :
```php
// PROBLÈME: Aucun filtrage par type de destinataire
$messages = ESBTPAnnonce::with('createdBy')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get()
    ->filter(function ($annonce) use ($user) {
        // Seul filtrage: éviter l'auto-notification
        return !$annonce->created_by || $annonce->created_by != $user->id;
    })
```

**B. Conséquences identifiées** :
- ✅ SuperAdmin voyait annonces "Tous les étudiants" dans ses messages
- ✅ Étudiants voyaient annonces destinées aux admins
- ✅ Confusion sur les vrais destinataires

#### Solution complète appliquée :

**A. Filtrage SuperAdmin/Secrétaire/Coordinateur** :
```php
// NOUVEAU: Filtrage par destinataire administratif
$messages = ESBTPAnnonce::with('createdBy')
    ->where(function ($query) {
        // Exclure annonces destinées aux étudiants
        $query->where('type', '!=', 'general')
              ->where('type', '!=', 'classe')
              ->where('type', '!=', 'etudiant');
    })
    ->orWhere(function ($query) {
        // Inclure annonces générales admin
        $query->whereNull('type');
    })
    ->orderBy('created_at', 'desc')
    ->limit(10) // Augmenté pour compenser le filtrage
    ->get()
    ->filter(function ($annonce) use ($user) {
        // Garder l'auto-exclusion
        return !$annonce->created_by || $annonce->created_by != $user->id;
    })
    ->take(5); // Limiter après filtrage
```

**B. Filtrage Étudiants intelligent** :
```php
// NOUVEAU: Filtrage précis par destinataire étudiant
$etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

$messages = ESBTPAnnonce::with(['createdBy', 'classes', 'etudiants'])
    ->where(function ($query) use ($etudiant) {
        // 1. Annonces générales étudiants
        $query->where('type', 'general');

        // 2. Annonces pour leur classe spécifique
        if ($etudiant && $etudiant->classe_active) {
            $query->orWhere(function ($subQuery) use ($etudiant) {
                $subQuery->where('type', 'classe')
                         ->whereHas('classes', function ($classQuery) use ($etudiant) {
                             $classQuery->where('esbtp_classes.id', $etudiant->classe_active->id);
                         });
            });
        }

        // 3. Annonces destinées spécifiquement à cet étudiant
        if ($etudiant) {
            $query->orWhere(function ($subQuery) use ($etudiant) {
                $subQuery->where('type', 'etudiant')
                         ->whereHas('etudiants', function ($etudiantQuery) use ($etudiant) {
                             $etudiantQuery->where('esbtp_etudiants.id', $etudiant->id);
                         });
            });
        }
    })
```

**C. Filtrage Enseignants** :
```php
// NOUVEAU: Filtrage pour enseignants (similaire aux admins)
$messages = ESBTPAnnonce::with('createdBy')
    ->where(function ($query) {
        // Exclure annonces étudiants
        $query->where('type', '!=', 'general')
              ->where('type', '!=', 'classe')
              ->where('type', '!=', 'etudiant');
    })
    ->orWhere(function ($query) {
        // Inclure annonces générales personnel
        $query->whereNull('type');
    })
```

#### Impact de la correction :

**Avant (problématique) :**
- ❌ SuperAdmin recevait "Examen - Tous les étudiants" dans ses messages
- ❌ Confusion sur qui est vraiment destinataire
- ❌ Étudiants voyaient annonces admin

**Après (corrigé) :**
- ✅ **SuperAdmin** : Ne voit que les annonces qui lui sont destinées
- ✅ **Étudiants** : Voient seulement leurs annonces (général/classe/spécifique)
- ✅ **Enseignants** : Voient seulement annonces personnel/admin
- ✅ **Filtrage intelligent** : Basé sur relations BDD réelles

#### Règles métier implémentées :

1. **Annonces 'general'** → Seulement pour étudiants ayant un compte user
2. **Annonces 'classe'** → Seulement pour étudiants de ces classes
3. **Annonces 'etudiant'** → Seulement pour étudiants spécifiquement ciblés
4. **Annonces autres/null** → Pour personnel administratif/enseignants
5. **Auto-exclusion** → Créateur ne reçoit pas ses propres annonces

#### Fichier modifié :
- ✅ **`app/Http/Controllers/NavbarController.php`** - Méthode `getMessages()` lignes 101-208

---

*Correction appliquée le 2025-01-15 - Problème messages SuperAdmin complètement résolu*