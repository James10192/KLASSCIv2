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

---

*Implémentation terminée le 2025-01-14 - Design unifié et règle 15 minutes opérationnelle*