@extends('layouts.app')

@section('title', 'Nouvelle Inscription')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="{{ asset('css/inscription-create.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Nouvelle Inscription</h1>
                <p class="header-subtitle">Enregistrement d'un nouvel étudiant</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <!-- Stepper de progression — indicateur visuel fixed (scroll-driven) -->
        <nav class="form-stepper" id="formStepper" aria-label="Progression du formulaire">
            <div class="step-item active" data-step="1">
                <div class="step-node" data-label="Identité">
                    <div class="step-circle">
                        <span class="step-num">1</span>
                        <i class="fas fa-check step-check"></i>
                    </div>
                </div>
                <div class="step-track" data-track="1">
                    <div class="step-track-fill"></div>
                    <div class="step-track-light"></div>
                </div>
            </div>
            <div class="step-item" data-step="2">
                <div class="step-node" data-label="Académique">
                    <div class="step-circle">
                        <span class="step-num">2</span>
                        <i class="fas fa-check step-check"></i>
                    </div>
                </div>
                <div class="step-track" data-track="2">
                    <div class="step-track-fill"></div>
                    <div class="step-track-light"></div>
                </div>
            </div>
            <div class="step-item" data-step="3">
                <div class="step-node" data-label="Affectation">
                    <div class="step-circle">
                        <span class="step-num">3</span>
                        <i class="fas fa-check step-check"></i>
                    </div>
                </div>
                <div class="step-track" data-track="3">
                    <div class="step-track-fill"></div>
                    <div class="step-track-light"></div>
                </div>
            </div>
            <div class="step-item" data-step="4">
                <div class="step-node" data-label="Parents">
                    <div class="step-circle">
                        <span class="step-num">4</span>
                        <i class="fas fa-check step-check"></i>
                    </div>
                </div>
                <div class="step-track" data-track="4">
                    <div class="step-track-fill"></div>
                    <div class="step-track-light"></div>
                </div>
            </div>
            <div class="step-item" data-step="5">
                <div class="step-node" data-label="Frais">
                    <div class="step-circle">
                        <span class="step-num">5</span>
                        <i class="fas fa-check step-check"></i>
                    </div>
                </div>
                @can('students.accessibility.edit')
                <div class="step-track" data-track="5">
                    <div class="step-track-fill"></div>
                    <div class="step-track-light"></div>
                </div>
                @endcan
            </div>
            @can('students.accessibility.edit')
            <div class="step-item step-item--optional" data-step="6" id="step-accessibility">
                <div class="step-node" data-label="Accessibilité (optionnel)">
                    <div class="step-circle">
                        <i class="fas fa-universal-access step-num" style="font-size:13px;"></i>
                        <i class="fas fa-check step-check"></i>
                    </div>
                </div>
                <!-- pas de track après le dernier -->
            </div>
            @endcan
        </nav>

        <form id="inscriptionForm" method="POST" action="{{ route('esbtp.inscriptions.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="duplicate_override" id="duplicate_override" value="0">

            <!-- Erreurs globales -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation :</h6>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- =============================================
                 SECTION 1 — INFORMATIONS PERSONNELLES
            ============================================== -->
            <div class="form-section" id="section-identite">
                <div class="section-header">
                    <div class="section-number">1</div>
                    <div>
                        <p class="section-title-text">Informations personnelles</p>
                        <p class="section-subtitle">Identité civile de l'étudiant</p>
                    </div>
                </div>

                <!-- Alerte doublon inline (affichée sous le nom/prénom) -->
                <div class="duplicate-inline-alert" id="duplicate-warning">
                    <div class="alert-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Doublon(s) potentiel(s) détecté(s)
                    </div>
                    <div class="alert-body" id="duplicate-warning-text">
                        Veuillez vérifier les informations avant de continuer.
                    </div>
                    <button type="button" class="btn-show-dupes" id="show-duplicates-modal">
                        <i class="fas fa-eye"></i> Voir les doublons
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-user field-icon"></i> Nom <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('nom') is-invalid @enderror"
                                   name="nom"
                                   id="nom-field"
                                   value="{{ old('nom') }}"
                                   required
                                   placeholder="Ex : KOUASSI">
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-user field-icon"></i> Prénom(s) <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('prenoms') is-invalid @enderror"
                                   name="prenoms"
                                   id="prenoms-field"
                                   value="{{ old('prenoms') }}"
                                   required
                                   placeholder="Ex : Jean-Marc">
                            @error('prenoms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-venus-mars field-icon"></i> Genre <span class="req">*</span>
                            </label>
                            <select class="form-control @error('sexe') is-invalid @enderror" name="sexe" required>
                                <option value="">Sélectionner</option>
                                <option value="M" {{ old('sexe') == 'M' ? 'selected' : '' }}>Masculin</option>
                                <option value="F" {{ old('sexe') == 'F' ? 'selected' : '' }}>Féminin</option>
                            </select>
                            @error('sexe')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-calendar field-icon"></i> Date de naissance <span class="req">*</span>
                            </label>
                            <input type="date"
                                   class="form-control @error('date_naissance') is-invalid @enderror"
                                   name="date_naissance"
                                   value="{{ old('date_naissance') }}"
                                   required>
                            @error('date_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt field-icon"></i> Lieu de naissance <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('lieu_naissance') is-invalid @enderror"
                                   name="lieu_naissance"
                                   value="{{ old('lieu_naissance') }}"
                                   required
                                   placeholder="Ex : Abidjan">
                            @error('lieu_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-flag field-icon"></i> Nationalité <span class="req">*</span>
                            </label>
                            <select class="form-control @error('nationalite') is-invalid @enderror" name="nationalite" required>
                                @include('esbtp.partials.nationality-options', ['selected' => old('nationalite')])
                            </select>
                            @error('nationalite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-phone field-icon"></i> Téléphone <span class="req">*</span>
                            </label>
                            <input type="tel"
                                   class="form-control @error('telephone') is-invalid @enderror"
                                   name="telephone"
                                   value="{{ old('telephone') }}"
                                   required
                                   placeholder="+225 XX XX XXX XXX">
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-envelope field-icon"></i> Email <span class="opt">(optionnel)</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('email_personnel') is-invalid @enderror"
                                   name="email_personnel"
                                   value="{{ old('email_personnel') }}"
                                   placeholder="exemple@email.com">
                            @error('email_personnel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4" id="matriculeContainer">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-id-card field-icon"></i>
                                Matricule <span class="req">*</span>
                                <span id="matriculeMode" class="badge bg-info ms-1" style="font-size:9px;"></span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control @error('matricule') is-invalid @enderror"
                                       name="matricule"
                                       id="matriculeInput"
                                       value="{{ old('matricule') }}"
                                       placeholder="Ex: MAT25-0001">
                                <button type="button" class="btn btn-outline-primary" id="generateMatriculeBtn" style="display:none;">
                                    <i class="fas fa-magic"></i> Générer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="checkMatriculeBtn" style="display:none;">
                                    <i class="fas fa-search"></i> Vérifier
                                </button>
                            </div>
                            <small class="text-muted" id="matriculeHelp">Matricule unique de l'étudiant</small>
                            <div id="matriculeStatus" class="mt-1"></div>
                            @error('matricule')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-camera field-icon"></i> Photo <span class="opt">(optionnel)</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('photo') is-invalid @enderror"
                                   name="photo"
                                   accept="image/jpeg,image/png,image/jpg,image/gif">
                            <small class="text-muted">JPEG, PNG, JPG, GIF — max 2 Mo</small>
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-city field-icon"></i> Ville de résidence <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('ville') is-invalid @enderror"
                                   name="ville"
                                   value="{{ old('ville') }}"
                                   required
                                   placeholder="Ex : Abidjan">
                            @error('ville')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label class="form-label">
                                <i class="fas fa-map field-icon"></i> Commune <span class="req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('commune') is-invalid @enderror"
                                   name="commune"
                                   value="{{ old('commune') }}"
                                   required
                                   placeholder="Ex : Cocody">
                            @error('commune')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 2 — INFORMATIONS ACADÉMIQUES
            ============================================== -->
            <div class="form-section" id="section-academique">
                <div class="section-header">
                    <div class="section-number">2</div>
                    <div>
                        <p class="section-title-text">Informations académiques</p>
                        <p class="section-subtitle">Filière, niveau et année universitaire sont déduits de la classe</p>
                    </div>
                </div>

                <div class="alert-kl alert-kl-info mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    <span>Sélectionnez une classe et l'année universitaire d'inscription.</span>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        @include('components.forms.class-selector')
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="annee_universitaire_id">
                                <i class="fas fa-calendar-alt me-1"></i> Année universitaire <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('annee_universitaire_id') is-invalid @enderror"
                                    name="annee_universitaire_id"
                                    id="annee_universitaire_id"
                                    required>
                                @foreach($academicYears->sortByDesc('start_date') as $annee)
                                    <option value="{{ $annee->id }}"
                                        {{ (old('annee_universitaire_id', $anneeEnCours->id ?? '') == $annee->id) ? 'selected' : '' }}
                                        data-is-current="{{ $annee->is_current ? '1' : '0' }}"
                                        data-start-date="{{ $annee->start_date?->format('Y-m-d') ?? '' }}">
                                        {{ $annee->name }}
                                        @if($annee->is_current) (Année courante) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('annee_universitaire_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Bloc inscription sous réserve (visible uniquement si année future) --}}
                <div class="row mt-3" id="sous-reserve-block" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert alert-warning border-left-warning" style="border-left: 4px solid #f59e0b; background: #fffbeb;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle me-3 mt-1" style="color: #f59e0b; font-size: 1.2rem;"></i>
                                <div class="flex-grow-1">
                                    <strong style="color: #92400e;"><i class="fas fa-clock me-1"></i> Inscription pour une année future</strong>
                                    <p class="mb-2 mt-1" style="color: #78350f; font-size: 13px;">
                                        Cette inscription concerne une année universitaire qui n'est pas l'année courante.
                                        Vous pouvez la marquer comme "sous réserve" (ex: en attente du Baccalauréat).
                                    </p>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox"
                                               name="is_sous_reserve" id="is_sous_reserve" value="1"
                                               {{ old('is_sous_reserve') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="is_sous_reserve" style="color: #92400e;">
                                            <i class="fas fa-file-signature me-1"></i> Inscription sous réserve
                                        </label>
                                    </div>
                                    <div id="condition-reserve-field" style="{{ old('is_sous_reserve') ? '' : 'display: none;' }}">
                                        <label for="condition_reserve" class="form-label" style="color: #78350f; font-size: 13px;">
                                            <i class="fas fa-graduation-cap me-1"></i> Condition / Motif de la réserve :
                                        </label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="condition_reserve" id="condition_reserve"
                                               value="{{ old('condition_reserve', 'BACCALAURÉAT') }}"
                                               placeholder="Ex: BACCALAURÉAT, BTS, BEPC..."
                                               style="max-width: 350px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 3 — STATUT D'AFFECTATION
            ============================================== -->
            <div class="form-section" id="section-affectation">
                <div class="section-header">
                    <div class="section-number">3</div>
                    <div>
                        <p class="section-title-text">Statut d'affectation gouvernementale</p>
                        <p class="section-subtitle">Détermine la prise en charge étatique et les frais applicables</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-university field-icon"></i> Statut d'affectation MESRS <span class="req">*</span>
                        </label>
                        <select class="form-select @error('affectation_status') is-invalid @enderror"
                                name="affectation_status"
                                id="affectation_status"
                                required
                                onchange="updateAffectationInfo()">
                            <option value="">Sélectionnez le statut d'affectation</option>
                            <option value="affecté"     {{ old('affectation_status') == 'affecté'     ? 'selected' : '' }}>Affecté</option>
                            <option value="réaffecté"   {{ old('affectation_status') == 'réaffecté'   ? 'selected' : '' }}>Réaffecté</option>
                            <option value="non_affecté" {{ old('affectation_status') == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                        </select>
                        @error('affectation_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-lightbulb me-1"></i>
                            Le statut influence les frais applicables selon la prise en charge étatique
                        </small>
                    </div>
                    <div class="col-md-6">
                        <div class="affectation-info-card" id="affectation-info">
                            <span class="text-muted" style="font-size:13px;">
                                <i class="fas fa-arrow-left me-2"></i>Sélectionnez un statut pour voir les détails
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 4 — PARENTS / TUTEURS (optionnel)
            ============================================== -->
            <div class="mb-4" id="section-parents">
                <!-- Toggle header cliquable -->
                <div class="parents-toggle-header" id="parents-toggle-btn" role="button" tabindex="0"
                     aria-expanded="false" aria-controls="parents-body">
                    <div class="parents-toggle-left">
                        <div class="parents-toggle-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="parents-toggle-title">
                                <i class="fas fa-user-friends me-2" style="color: var(--kl-primary);"></i>
                                Parents / Tuteurs
                                <span class="badge badge-optional ms-2">Optionnel</span>
                            </div>
                            <div class="parents-toggle-sub" id="parents-toggle-sub">
                                Cliquez pour ajouter les informations des parents ou tuteurs
                            </div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down parents-toggle-chevron"></i>
                </div>

                <!-- Corps de la section parents (masqué par défaut) -->
                <div class="parents-body" id="parents-body">
                    <div class="alert-kl alert-kl-info mb-4">
                        <i class="fas fa-info-circle"></i>
                        <span>Vous pouvez ajouter un ou plusieurs parents/tuteurs. Chaque section est optionnelle — si vous laissez les champs vides, aucun parent ne sera créé.</span>
                    </div>

                    <!-- Container des parents -->
                    <div id="parents-container">
                        <!-- Premier parent (index 0) — removable -->
                        <div class="parent-item" id="parent-0">
                            <div class="parent-card-header">
                                <h6 class="parent-card-title">
                                    <i class="fas fa-user-tie"></i> Parent / Tuteur #1
                                </h6>
                                <button type="button" class="btn-remove-parent remove-parent">
                                    <i class="fas fa-times"></i> Supprimer
                                </button>
                            </div>
                            <div class="parent-card-body">
                                <input type="hidden" name="parents[0][type]" value="nouveau">

                                <div class="parent-type-toggle mb-3">
                                    <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_0">
                                    <label class="form-check-label" for="parent_existant_0">
                                        <i class="fas fa-search me-1"></i> Sélectionner un parent existant
                                    </label>
                                </div>

                                <!-- Section parent existant -->
                                <div class="parent-existant-section" style="display:none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-search field-icon"></i> Rechercher un parent
                                            </label>
                                            <select class="form-control parent-select" id="parent_id_0" name="parents[0][parent_id]">
                                                <option value="">Sélectionner un parent</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-link field-icon"></i> Relation avec l'étudiant
                                            </label>
                                            <select class="form-control" name="parents[0][relation]">
                                                <option value="">Sélectionner</option>
                                                <option value="Père">Père</option>
                                                <option value="Mère">Mère</option>
                                                <option value="Tuteur">Tuteur</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section nouveau parent -->
                                <div class="parent-nouveau-section">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-user field-icon"></i> Nom</label>
                                            <input type="text" class="form-control" name="parents[0][nom]" placeholder="Nom du parent">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-user field-icon"></i> Prénom(s)</label>
                                            <input type="text" class="form-control" name="parents[0][prenoms]" placeholder="Prénom(s) du parent">
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-phone field-icon"></i> Téléphone</label>
                                            <input type="tel" class="form-control" name="parents[0][telephone]" placeholder="+225 XX XX XXX XXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-envelope field-icon"></i> Email <span class="opt">(optionnel)</span></label>
                                            <input type="email" class="form-control" name="parents[0][email]" placeholder="email@exemple.com">
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-briefcase field-icon"></i> Profession <span class="opt">(optionnel)</span></label>
                                            <input type="text" class="form-control" name="parents[0][profession]" placeholder="Ex : Ingénieur">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-link field-icon"></i> Relation</label>
                                            <select class="form-control" name="parents[0][relation]">
                                                <option value="">Sélectionner</option>
                                                <option value="Père">Père</option>
                                                <option value="Mère">Mère</option>
                                                <option value="Tuteur">Tuteur</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label"><i class="fas fa-map-marker-alt field-icon"></i> Adresse <span class="opt">(optionnel)</span></label>
                                            <textarea class="form-control" name="parents[0][adresse]" rows="2" placeholder="Adresse complète du parent"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton ajouter un parent supplémentaire -->
                    <button type="button" id="add-parent-btn" class="btn-add-parent">
                        <i class="fas fa-plus"></i>
                        Ajouter un autre parent / tuteur
                    </button>
                </div>
            </div>

            <!-- Template caché pour clonage -->
            <div id="parent-template" style="display:none;">
                <div class="parent-item">
                    <div class="parent-card-header">
                        <h6 class="parent-card-title">
                            <i class="fas fa-user-tie"></i> Parent / Tuteur #<span class="parent-num"></span>
                        </h6>
                        <button type="button" class="btn-remove-parent remove-parent">
                            <i class="fas fa-times"></i> Supprimer
                        </button>
                    </div>
                    <div class="parent-card-body">
                        <input type="hidden" name="parents[template][type]" value="nouveau">

                        <div class="parent-type-toggle mb-3">
                            <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_template">
                            <label class="form-check-label" for="parent_existant_template">
                                <i class="fas fa-search me-1"></i> Sélectionner un parent existant
                            </label>
                        </div>

                        <div class="parent-existant-section" style="display:none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-search field-icon"></i> Rechercher un parent</label>
                                    <select class="form-control parent-select" id="parent_id_template" name="parents[template][parent_id]">
                                        <option value="">Sélectionner un parent</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-link field-icon"></i> Relation</label>
                                    <select class="form-control" name="parents[template][relation]">
                                        <option value="">Sélectionner</option>
                                        <option value="Père">Père</option>
                                        <option value="Mère">Mère</option>
                                        <option value="Tuteur">Tuteur</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="parent-nouveau-section">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-user field-icon"></i> Nom</label>
                                    <input type="text" class="form-control" name="parents[template][nom]" placeholder="Nom du parent">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-user field-icon"></i> Prénom(s)</label>
                                    <input type="text" class="form-control" name="parents[template][prenoms]" placeholder="Prénom(s) du parent">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-phone field-icon"></i> Téléphone</label>
                                    <input type="tel" class="form-control" name="parents[template][telephone]" placeholder="+225 XX XX XXX XXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-envelope field-icon"></i> Email <span class="opt">(optionnel)</span></label>
                                    <input type="email" class="form-control" name="parents[template][email]" placeholder="email@exemple.com">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-briefcase field-icon"></i> Profession <span class="opt">(optionnel)</span></label>
                                    <input type="text" class="form-control" name="parents[template][profession]" placeholder="Ex : Ingénieur">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-link field-icon"></i> Relation</label>
                                    <select class="form-control" name="parents[template][relation]">
                                        <option value="">Sélectionner</option>
                                        <option value="Père">Père</option>
                                        <option value="Mère">Mère</option>
                                        <option value="Tuteur">Tuteur</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-12">
                                    <label class="form-label"><i class="fas fa-map-marker-alt field-icon"></i> Adresse <span class="opt">(optionnel)</span></label>
                                    <textarea class="form-control" name="parents[template][adresse]" rows="2" placeholder="Adresse complète du parent"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 5 — FRAIS D'INSCRIPTION
            ============================================== -->
            <div class="form-section" id="section-frais">
                <div class="section-header">
                    <div class="section-number">5</div>
                    <div>
                        <p class="section-title-text">Frais d'inscription et options</p>
                        <p class="section-subtitle">Les frais obligatoires sont pré-sélectionnés selon la filière et le niveau</p>
                    </div>
                </div>

                <div class="alert-kl alert-kl-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Configuration des frais :</strong> Sélectionnez les options pour chaque catégorie. Sélectionnez d'abord une classe pour charger les frais applicables.</span>
                </div>

                <!-- Conteneur dynamique pour les frais -->
                <div id="fraisContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3 text-muted" style="font-size:14px;">
                            <i class="fas fa-arrow-up me-2"></i>Sélectionnez d'abord une classe pour voir les frais applicables
                        </p>
                    </div>
                </div>

                <!-- Résumé des frais -->
                <div class="resume-frais-card mt-4">
                    <div class="resume-frais-title">
                        <i class="fas fa-calculator"></i>
                        Résumé des frais sélectionnés
                    </div>
                    <div id="resumeFrais">
                        <div class="text-center text-muted py-2" style="font-size:13px;">
                            Sélectionnez une classe et configurez les frais pour voir le résumé
                        </div>
                    </div>
                </div>
            </div>

            {{-- =============================================
                 PROFIL D'ACCESSIBILITÉ (OPTIONNEL — repliable)
                 Namespace ia-* (Inscription Accessibility) — design system KLASSCI
            ============================================== --}}
            @can('students.accessibility.edit')
            @include('esbtp.inscriptions.partials.accessibility-section')
            @endcan

            <!-- =============================================
                 BOUTONS DE SOUMISSION
            ============================================== -->
            <div class="d-flex justify-content-center gap-3 mt-2 mb-4">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-kl-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn-kl-primary">
                    <i class="fas fa-save"></i> Enregistrer l'inscription
                </button>
            </div>
        </form>

        <!-- =============================================
             MODAL DOUBLONS
        ============================================== -->
        <div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="duplicateModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Doublons potentiels détectés
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3" style="font-size:13px;">
                            Les étudiants ci-dessous correspondent aux informations saisies.
                            Vérifiez qu'il ne s'agit pas de la même personne avant de continuer.
                        </p>
                        <div id="duplicate-modal-content">
                            <div class="alert-kl alert-kl-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Aucun doublon détecté.</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i> Corriger les informations
                        </button>
                        <button type="button" class="btn" id="continue-with-duplicate"
                                style="background:var(--kl-success);color:white;border:none;font-weight:600;border-radius:var(--kl-radius);padding:8px 20px;">
                            <i class="fas fa-check me-1"></i> Ce n'est pas un doublon — Continuer
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.main-content -->
</div><!-- /.dashboard-acasi -->
@endsection

@push('scripts')
<!-- Choices.js -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let parentIndex = 1;
    let isLoadingFrais = false;

    // =============================================
    // REFS DOUBLON
    // =============================================
    const duplicateForm          = document.getElementById('inscriptionForm');
    const duplicateOverrideInput = document.getElementById('duplicate_override');
    const duplicateWarning       = document.getElementById('duplicate-warning');
    const duplicateWarningText   = document.getElementById('duplicate-warning-text');
    const duplicateModalElement  = document.getElementById('duplicateModal');
    const duplicateModalContent  = document.getElementById('duplicate-modal-content');
    const showDuplicatesBtn      = document.getElementById('show-duplicates-modal');
    const continueWithDuplicateBtn = document.getElementById('continue-with-duplicate');
    const duplicateCheckUrl      = "{{ route('esbtp.inscriptions.duplicates') }}";
    const nomField               = document.getElementById('nom-field');
    const prenomsField           = document.getElementById('prenoms-field');

    let duplicateModalInstance = null;
    if (duplicateModalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        duplicateModalInstance = new bootstrap.Modal(duplicateModalElement);
    }

    const duplicateState = { results: [], override: false };
    const duplicateInitialData = @json(session('duplicate_suggestions', []));
    const duplicateInitialOverride = {{ old('duplicate_override', '0') === '1' ? 'true' : 'false' }};

    if (Array.isArray(duplicateInitialData) && duplicateInitialData.length) {
        duplicateState.results = duplicateInitialData;
    }
    duplicateState.override = duplicateInitialOverride;
    if (duplicateState.override && duplicateOverrideInput) {
        duplicateOverrideInput.value = '1';
    }

    let duplicateTimer = null;

    function resetDuplicateOverride() {
        duplicateState.override = false;
        if (duplicateOverrideInput) duplicateOverrideInput.value = '0';
    }

    // =============================================
    // ANNÉE UNIVERSITAIRE + SOUS RÉSERVE
    // =============================================
    const anneeSelect = document.getElementById('annee_universitaire_id');
    const sousReserveBlock = document.getElementById('sous-reserve-block');
    const isSousReserveCheck = document.getElementById('is_sous_reserve');
    const conditionField = document.getElementById('condition-reserve-field');

    // Trouver la start_date de l'année courante pour comparer
    const currentYearOption = anneeSelect ? [...anneeSelect.options].find(o => o.dataset.isCurrent === '1') : null;
    const currentYearStartDate = currentYearOption?.dataset?.startDate || '';

    function updateSousReserveVisibility() {
        if (!anneeSelect || !sousReserveBlock) return;
        const selectedOption = anneeSelect.options[anneeSelect.selectedIndex];
        const isCurrent = selectedOption?.dataset?.isCurrent === '1';
        const startDate = selectedOption?.dataset?.startDate || '';
        // Afficher sous réserve uniquement si année FUTURE (pas courante, pas passée)
        const isFuture = !isCurrent && startDate && currentYearStartDate && startDate > currentYearStartDate;
        sousReserveBlock.style.display = isFuture ? '' : 'none';
        // Si pas future, décocher sous réserve
        if (!isFuture && isSousReserveCheck) {
            isSousReserveCheck.checked = false;
            if (conditionField) conditionField.style.display = 'none';
        }
    }

    if (anneeSelect) {
        anneeSelect.addEventListener('change', updateSousReserveVisibility);
        // Exécuter au chargement pour gérer old()
        updateSousReserveVisibility();
    }

    if (isSousReserveCheck) {
        isSousReserveCheck.addEventListener('change', function() {
            if (conditionField) {
                conditionField.style.display = this.checked ? '' : 'none';
            }
        });
    }

    // =============================================
    // INDICATEURS VISUELS INLINE SUR LES CHAMPS
    // =============================================
    function setFieldState(state) {
        if (!nomField || !prenomsField) return;
        const fields = [nomField, prenomsField];
        fields.forEach(f => {
            f.classList.remove('checking-duplicate', 'duplicate-found', 'duplicate-ok');
            if (state) f.classList.add(state);
        });
    }

    // =============================================
    // DÉTECTION DOUBLONS
    // =============================================
    function scheduleDuplicateCheck() {
        if (!duplicateCheckUrl) return;
        if (duplicateTimer) clearTimeout(duplicateTimer);
        duplicateTimer = setTimeout(runDuplicateCheck, 600);
        resetDuplicateOverride();
        setFieldState('checking-duplicate');
    }

    function runDuplicateCheck() {
        if (!duplicateForm || !duplicateCheckUrl) return;

        const nomValue     = nomField     ? nomField.value.trim()     : '';
        const prenomsValue = prenomsField ? prenomsField.value.trim() : '';

        // Le nom seul (≥3 chars) suffit pour déclencher la vérification.
        // Avec seulement le prénom c'est insuffisant (trop de faux positifs).
        if (nomValue.length < 3) {
            duplicateState.results = [];
            setFieldState(null);
            updateDuplicateUI();
            return;
        }

        const dateField = duplicateForm.querySelector('input[name="date_naissance"]');
        const sexeField = duplicateForm.querySelector('select[name="sexe"]');

        const params = new URLSearchParams();
        params.append('nom', nomValue);
        // Prénom optionnel : enrichit le score si renseigné
        if (prenomsValue.length >= 2) params.append('prenoms', prenomsValue);
        if (dateField && dateField.value) params.append('date_naissance', dateField.value);
        if (sexeField && sexeField.value)  params.append('sexe', sexeField.value);

        fetch(`${duplicateCheckUrl}?${params.toString()}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            duplicateState.results = Array.isArray(data.duplicates) ? data.duplicates : [];
            resetDuplicateOverride();
            setFieldState(duplicateState.results.length > 0 ? 'duplicate-found' : 'duplicate-ok');
            updateDuplicateUI();
        })
        .catch(() => {
            duplicateState.results = [];
            setFieldState(null);
            updateDuplicateUI();
        });
    }

    function updateDuplicateUI() {
        if (!duplicateWarning || !duplicateWarningText) return;

        if (duplicateState.results.length > 0) {
            if (duplicateState.override) {
                duplicateWarning.style.display = 'none';
                if (duplicateOverrideInput) duplicateOverrideInput.value = '1';
                return;
            }
            duplicateWarning.style.display = 'block';
            const n = duplicateState.results.length;
            duplicateWarningText.textContent =
                (function() {
                    const best = duplicateState.results[0];
                    const score = Number(best?.score ?? 0);
                    const conf  = best?.confidence ?? (score >= 75 ? 'probable' : 'possible');
                    const confLabel = { 'quasi-certain': 'quasi-certaine', 'probable': 'probable', 'possible': 'possible', 'faible': 'faible' }[conf] || 'possible';
                    return `${n} correspondance${n > 1 ? 's' : ''} détectée${n > 1 ? 's' : ''} — similarité ${confLabel}. Vérifiez avant de continuer.`;
                })();
            renderDuplicateModal();
        } else {
            duplicateWarning.style.display = 'none';
            if (duplicateOverrideInput) duplicateOverrideInput.value = '0';
            if (duplicateModalInstance) duplicateModalInstance.hide();
            if (duplicateModalContent) {
                duplicateModalContent.innerHTML = `
                    <div class="alert-kl alert-kl-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Aucun doublon détecté.</span>
                    </div>`;
            }
        }
    }

    function getInitials(fullName) {
        return (fullName || '?')
            .split(' ')
            .slice(0, 2)
            .map(p => p[0] || '')
            .join('')
            .toUpperCase();
    }

    function renderDuplicateModal() {
        if (!duplicateModalContent) return;

        if (duplicateState.results.length === 0) {
            duplicateModalContent.innerHTML = `
                <div class="alert-kl alert-kl-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Aucun doublon détecté.</span>
                </div>`;
            return;
        }

        const confidenceColors = {
            'quasi-certain': { cls: 'score-high',  icon: 'fa-exclamation-triangle', label: 'Quasi-certain', color: '#dc3545' },
            'probable':      { cls: 'score-high',  icon: 'fa-exclamation-circle',   label: 'Probable',      color: '#e67e22' },
            'possible':      { cls: 'score-med',   icon: 'fa-question-circle',       label: 'Possible',      color: '#f39c12' },
            'faible':        { cls: 'score-low',   icon: 'fa-info-circle',           label: 'Faible',        color: '#6c757d' },
        };

        const cards = duplicateState.results.map(item => {
            const score      = Number(item.score ?? 0);
            const confidence = item.confidence || (score >= 75 ? 'probable' : (score >= 55 ? 'possible' : 'faible'));
            const conf       = confidenceColors[confidence] || confidenceColors['faible'];
            const initials   = getInitials(item.full_name);
            const matricule  = item.matricule  || 'N/A';
            const date       = item.date_naissance || 'N/A';
            const sexe       = item.sexe === 'M' ? 'Masculin' : (item.sexe === 'F' ? 'Féminin' : 'N/A');
            const showUrl    = item.show_url || '#';

            // Détail des champs qui ont matché
            const breakdown = item.breakdown || {};
            const matchDetails = [];
            if (breakdown.nom   > 0) matchDetails.push(`Nom (${breakdown.nom} pts)`);
            if (breakdown.prenoms > 0) matchDetails.push(`Prénom (${breakdown.prenoms} pts)`);
            if (breakdown.date  > 0) matchDetails.push(`Date naissance (${breakdown.date} pts)`);
            const matchSummary = matchDetails.length
                ? `<span style="color:#555;font-size:.78rem"><i class="fas fa-check-double me-1"></i>${matchDetails.join(' · ')}</span>`
                : '';

            return `
                <div class="dupe-card ${conf.cls}" style="border-left:4px solid ${conf.color}">
                    <div class="dupe-avatar" style="background:${conf.color}">${initials}</div>
                    <div class="dupe-info">
                        <div class="dupe-name">${item.full_name ?? ''}</div>
                        <div class="dupe-meta">
                            <span><i class="fas fa-id-card"></i> ${matricule}</span>
                            <span><i class="fas fa-calendar"></i> ${date}</span>
                            <span><i class="fas fa-venus-mars"></i> ${sexe}</span>
                        </div>
                        ${matchSummary ? `<div class="mt-1">${matchSummary}</div>` : ''}
                        <div class="dupe-score-bar mt-2">
                            <div class="dupe-score-fill" style="width:${Math.min(score, 100)}%;background:${conf.color}"></div>
                        </div>
                        <div class="dupe-score-label">
                            <i class="fas ${conf.icon} me-1" style="color:${conf.color}"></i>
                            Doublon ${conf.label.toLowerCase()} — score ${Math.round(score)}/100
                        </div>
                    </div>
                    <div class="dupe-actions">
                        <button type="button" class="btn-dupe-same mark-duplicate" data-show-url="${showUrl}">
                            <i class="fas fa-user-check me-1"></i>C'est la même personne
                        </button>
                        <button type="button" class="btn-dupe-view view-duplicate" data-show-url="${showUrl}">
                            <i class="fas fa-external-link-alt me-1"></i>Voir la fiche
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        duplicateModalContent.innerHTML = cards;
    }

    // Init UI avec données de session (retour après erreur)
    updateDuplicateUI();

    // Listeners sur les champs déclencheurs
    if (nomField)    nomField.addEventListener('input', scheduleDuplicateCheck);
    if (prenomsField) prenomsField.addEventListener('input', scheduleDuplicateCheck);

    const dateInput = duplicateForm ? duplicateForm.querySelector('input[name="date_naissance"]') : null;
    const sexeSelect = duplicateForm ? duplicateForm.querySelector('select[name="sexe"]') : null;
    if (dateInput)  dateInput.addEventListener('change', scheduleDuplicateCheck);
    if (sexeSelect) sexeSelect.addEventListener('change', scheduleDuplicateCheck);

    // Bouton "Voir les doublons"
    if (showDuplicatesBtn) {
        showDuplicatesBtn.addEventListener('click', function() {
            renderDuplicateModal();
            if (duplicateModalInstance) duplicateModalInstance.show();
        });
    }

    // Bouton "Continuer — ce n'est pas un doublon"
    if (continueWithDuplicateBtn) {
        continueWithDuplicateBtn.addEventListener('click', function() {
            duplicateState.override = true;
            if (duplicateOverrideInput) duplicateOverrideInput.value = '1';
            if (duplicateModalInstance) duplicateModalInstance.hide();
            if (duplicateWarning) duplicateWarning.style.display = 'none';
            setFieldState(null);
        });
    }

    // Délégation click pour les boutons dans le modal
    document.addEventListener('click', function(e) {
        const markBtn = e.target.closest('.mark-duplicate');
        if (markBtn) {
            const url = markBtn.getAttribute('data-show-url');
            if (url && url !== '#') window.location.href = url;
            return;
        }
        const viewBtn = e.target.closest('.view-duplicate');
        if (viewBtn) {
            const url = viewBtn.getAttribute('data-show-url');
            if (url && url !== '#') window.open(url, '_blank');
        }
    });

    // Blocage submit si doublons non confirmés
    if (duplicateForm) {
        duplicateForm.addEventListener('submit', function(e) {
            if (duplicateState.results.length > 0 && !duplicateState.override) {
                e.preventDefault();
                renderDuplicateModal();
                if (duplicateModalInstance) duplicateModalInstance.show();
                else alert('Des doublons potentiels ont été détectés. Veuillez vérifier avant de continuer.');
            }
        });
    }

    // Check auto si champs déjà remplis (retour après erreur)
    if ((nomField && nomField.value.trim().length > 1) ||
        (prenomsField && prenomsField.value.trim().length > 1)) {
        scheduleDuplicateCheck();
    }

    // =============================================
    // TOGGLE SECTION PARENTS
    // =============================================
    const parentsToggleBtn = document.getElementById('parents-toggle-btn');
    const parentsBody      = document.getElementById('parents-body');
    const parentsToggleSub = document.getElementById('parents-toggle-sub');

    function updateParentToggleSub() {
        const count = document.querySelectorAll('#parents-container .parent-item').length;
        if (count === 0) {
            parentsToggleSub.textContent = 'Cliquez pour ajouter les informations des parents ou tuteurs';
        } else {
            parentsToggleSub.textContent = `${count} parent${count > 1 ? 's' : ''} ajouté${count > 1 ? 's' : ''}`;
        }
    }

    if (parentsToggleBtn && parentsBody) {
        // Restaurer état si erreur de validation et parents présents
        const hasParentData = document.querySelector('#parents-container input[name*="[nom]"]')?.value?.trim().length > 0
            || document.querySelector('#parents-container input[name*="[prenoms]"]')?.value?.trim().length > 0;

        if (hasParentData) {
            parentsToggleBtn.classList.add('open');
            parentsBody.style.display = 'block';
            parentsToggleBtn.setAttribute('aria-expanded', 'true');
        }

        parentsToggleBtn.addEventListener('click', function() {
            const isOpen = parentsBody.style.display === 'block';
            if (isOpen) {
                parentsBody.style.display = 'none';
                parentsToggleBtn.classList.remove('open');
                parentsToggleBtn.setAttribute('aria-expanded', 'false');
            } else {
                parentsBody.style.display = 'block';
                parentsToggleBtn.classList.add('open');
                parentsToggleBtn.setAttribute('aria-expanded', 'true');
            }
        });

        parentsToggleBtn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); parentsToggleBtn.click(); }
        });
    }

    // =============================================
    // GESTION PARENTS EXISTANTS
    // =============================================
    function loadParentsExistants(selectElement) {
        if (!selectElement) return;
        fetch('/esbtp/api/parents/search', {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.parents) {
                selectElement.innerHTML = '<option value="">Sélectionner un parent</option>';
                data.parents.forEach(parent => {
                    const opt = document.createElement('option');
                    opt.value = parent.id;
                    opt.textContent = `${parent.nom} ${parent.prenoms} - ${parent.telephone}`;
                    selectElement.appendChild(opt);
                });
            }
        })
        .catch(() => {});
    }

    // Checkbox toggle existant/nouveau
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('parent-existant-checkbox')) {
            const parentItem     = e.target.closest('.parent-item');
            const existantSection = parentItem.querySelector('.parent-existant-section');
            const nouveauSection  = parentItem.querySelector('.parent-nouveau-section');
            const typeInput       = parentItem.querySelector('input[name*="[type]"]');

            if (e.target.checked) {
                if (existantSection) {
                    existantSection.style.display = 'block';
                    const sel = existantSection.querySelector('.parent-select');
                    if (sel) loadParentsExistants(sel);
                }
                if (nouveauSection) nouveauSection.style.display = 'none';
                if (typeInput) typeInput.value = 'existant';
            } else {
                if (existantSection) existantSection.style.display = 'none';
                if (nouveauSection) nouveauSection.style.display = 'block';
                if (typeInput) typeInput.value = 'nouveau';
            }
        }
    });

    // Supprimer un parent
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-parent') || e.target.closest('.remove-parent')) {
            e.preventDefault();
            const parentCard = e.target.closest('.parent-item');
            if (parentCard) {
                parentCard.remove();
                updateParentToggleSub();
                // Si plus de parents, fermer la section et ajouter un nouveau automatiquement
                if (document.querySelectorAll('#parents-container .parent-item').length === 0) {
                    // Ne pas fermer — l'utilisateur peut vouloir en rajouter
                }
            }
        }
    });

    // Ajouter un parent
    document.addEventListener('click', function(e) {
        if (e.target.id === 'add-parent-btn' || e.target.closest('#add-parent-btn')) {
            e.preventDefault();
            addNewParent();
        }
    });

    function addNewParent() {
        const template       = document.getElementById('parent-template');
        const parentsContainer = document.getElementById('parents-container');
        if (!template || !parentsContainer) return;

        const newParent = template.cloneNode(true);
        newParent.id = '';
        newParent.style.display = 'block';

        // Remplacer "template" par l'index courant
        newParent.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.name) el.name = el.name.replace('[template]', `[${parentIndex}]`);
            if (el.id)   el.id   = el.id.replace('_template', `_${parentIndex}`);
        });
        newParent.querySelectorAll('label[for]').forEach(l => {
            const f = l.getAttribute('for');
            if (f) l.setAttribute('for', f.replace('_template', `_${parentIndex}`));
        });

        // Mettre à jour le numéro dans le titre
        const numSpan = newParent.querySelector('.parent-num');
        if (numSpan) numSpan.textContent = parentIndex + 1;

        parentsContainer.appendChild(newParent);
        parentIndex++;
        updateParentToggleSub();
        newParent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // =============================================
    // SAUVEGARDE / RESTAURATION DONNÉES FORMULAIRE
    // =============================================
    function saveFormData() {
        const formData = {};
        const form = document.getElementById('inscriptionForm');
        form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"], select, textarea')
            .forEach(input => {
                if (input.name && input.value) formData[input.name] = input.value;
            });
        const photoInput = document.querySelector('input[name="photo"]');
        if (photoInput && photoInput.files.length > 0) {
            formData['photo_filename'] = photoInput.files[0].name;
        }
        return formData;
    }

    function restoreFormData(formData) {
        Object.keys(formData).forEach(name => {
            const input = document.querySelector(`[name="${name}"]`);
            if (input && name !== 'photo_filename') input.value = formData[name];
        });
        if (formData['photo_filename']) {
            const photoInput = document.querySelector('input[name="photo"]');
            if (photoInput && photoInput.files.length === 0) {
                const infoDiv = document.createElement('div');
                infoDiv.className = 'alert-kl alert-kl-info mt-2';
                infoDiv.innerHTML = `<i class="fas fa-info-circle"></i><span>Photo précédemment sélectionnée : ${formData['photo_filename']}. Veuillez la resélectionner si nécessaire.</span>`;
                photoInput.parentNode.appendChild(infoDiv);
            }
        }
    }

    // =============================================
    // CHARGEMENT FRAIS PAR CLASSE
    // =============================================
    document.addEventListener('change', function(e) {
        if (e.target.id === 'classe_id') {
            if (isLoadingFrais) return;
            const classeId = e.target.value;
            const fraisContainer = document.getElementById('fraisContainer');
            if (classeId && fraisContainer) {
                isLoadingFrais = true;
                const savedData = saveFormData();
                fraisContainer.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
                            <span class="visually-hidden">Chargement des frais...</span>
                        </div>
                        <p class="mt-3 text-muted" style="font-size:14px;">Chargement des frais pour cette classe...</p>
                    </div>`;
                const affectationStatus = document.getElementById('affectation_status')?.value || 'affecté';
                fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}?affectation_status=${encodeURIComponent(affectationStatus)}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(data => {
                    if (data.success) {
                        updateFraisContainer(data.frais, data.has_unconfigured_fees, data.configure_url);
                        updateResumeFrais();
                        setTimeout(() => restoreFormData(savedData), 100);
                    } else {
                        fraisContainer.innerHTML = `<div class="alert-kl alert-kl-danger"><i class="fas fa-exclamation-triangle"></i><span>Erreur lors du chargement des frais : ${data.message}</span></div>`;
                    }
                    isLoadingFrais = false;
                })
                .catch(err => {
                    fraisContainer.innerHTML = `<div class="alert-kl alert-kl-danger"><i class="fas fa-exclamation-triangle"></i><span>Erreur lors du chargement des frais. Veuillez réessayer.</span></div>`;
                    setTimeout(() => restoreFormData(savedData), 100);
                    isLoadingFrais = false;
                });
            } else if (fraisContainer) {
                fraisContainer.innerHTML = `
                    <div class="text-center py-5">
                        <p class="text-muted" style="font-size:14px;">
                            <i class="fas fa-arrow-up me-2"></i>Sélectionnez d'abord une classe pour voir les frais applicables
                        </p>
                    </div>`;
                isLoadingFrais = false;
            }
        }
    });

    // Blocage submit pendant chargement frais
    document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
        if (isLoadingFrais) {
            e.preventDefault();
            alert('Veuillez attendre la fin du chargement des frais avant de soumettre le formulaire.');
            return false;
        }
        debugLog && debugLog('SUBMIT EVENT TRIGGERED!');
        const form = e.target;
        const formData = new FormData(form);
        debugLog && debugLog(`Nom: ${formData.get('nom') || 'VIDE'}`);
        debugLog && debugLog(`Classe: ${formData.get('classe_id') || 'VIDE'}`);
        debugLog && debugLog(`Matricule: ${formData.get('matricule') || 'VIDE'}`);
    });

    // =============================================
    // RENDU FRAIS
    // =============================================
    function updateFraisContainer(fraisData, hasUnconfiguredFees, configureUrl) {
        const fraisContainer = document.getElementById('fraisContainer');
        if (!fraisContainer) return;

        let html = '';

        if (hasUnconfiguredFees) {
            html += `
                <div class="alert-kl alert-kl-warning mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Configuration incomplète</strong><br>
                        Certaines catégories de frais pour cette classe n'ont pas de variantes configurées. Les montants par défaut seront utilisés.
                        <br><a href="${configureUrl}" target="_blank" class="btn btn-outline-warning btn-sm mt-2">
                            <i class="fas fa-cog me-1"></i>Configuration rapide
                        </a>
                    </div>
                </div>`;
        }

        const fraisObligatoires = fraisData.filter(f => f.is_mandatory);
        const fraisOptionnels   = fraisData.filter(f => !f.is_mandatory);

        if (fraisObligatoires.length > 0) {
            html += `<p class="fw-bold text-primary mb-3" style="font-size:13px;"><i class="fas fa-star me-2"></i>Frais obligatoires</p>`;
            fraisObligatoires.forEach(frais => { html += generateFraisHTML(frais); });
        }
        if (fraisOptionnels.length > 0) {
            html += `<p class="fw-bold mt-4 mb-3" style="font-size:13px;color:var(--kl-info);"><i class="fas fa-plus-circle me-2"></i>Frais optionnels</p>`;
            fraisOptionnels.forEach(frais => { html += generateFraisHTML(frais); });
        }
        if (fraisData.length === 0) {
            html += `
                <div class="alert-kl alert-kl-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Aucun frais configuré</strong><br>
                        Aucune catégorie de frais n'est configurée pour cette classe.
                        <a href="${configureUrl}" target="_blank" class="btn btn-outline-info btn-sm mt-2">
                            <i class="fas fa-cog me-1"></i>Configurer les frais
                        </a>
                    </div>
                </div>`;
        }

        fraisContainer.innerHTML = html;
    }

    function generateFraisHTML(frais) {
        const category          = frais.category;
        const options           = frais.options || frais.variants || [];
        const defaultAmount     = frais.default_amount;
        const isMandatory       = frais.is_mandatory;
        const isConfigured      = frais.is_configured;
        const configurationType = frais.configuration_type;
        const categoryType      = frais.category_type || 'academic';

        const typeIcons = { 'academic': 'graduation-cap', 'service': 'cogs', 'administrative': 'file-alt' };
        const icon = typeIcons[categoryType] || (isMandatory ? 'star' : 'plus-circle');

        let configBadge = '';
        if (configurationType === 'variant' || configurationType === 'configuration') {
            configBadge = `<div class="alert-kl alert-kl-success mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-check-circle"></i><span>Tarif configuré pour cette classe</span></div>`;
        } else if (configurationType === 'rule') {
            configBadge = `<div class="alert-kl alert-kl-info mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-cog"></i><span>Tarif configuré par règle de classe</span></div>`;
        } else if (configurationType === 'global_options') {
            configBadge = `<div class="alert-kl alert-kl-info mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-globe"></i><span>Options globales disponibles</span></div>`;
        } else {
            configBadge = `<div class="alert-kl alert-kl-warning mb-3" style="font-size:12px;padding:8px 12px;"><i class="fas fa-info-circle"></i><span>Montant par défaut utilisé (non configuré pour cette classe)</span></div>`;
        }

        // ── FRAIS OPTIONNELS : nouvelle UI carte-souscription ──────────────────
        if (!isMandatory) {
            const baseAmt = parseFloat(defaultAmount) || 0;

            // Hint affiché sous le nom (avant souscription)
            const hintText = options.length > 0
                ? `${options.length} formule${options.length > 1 ? 's' : ''} disponible${options.length > 1 ? 's' : ''}`
                : `${baseAmt.toLocaleString('fr-FR')} FCFA`;

            // Zone de sélection (révélée après souscription)
            let selectionHTML = '';

            if (options.length === 0) {
                // Aucune option : montant fixe — radio caché + bloc info
                selectionHTML = `
                    <input class="frais-option optional-variant" type="radio"
                           name="frais[${category.id}][variant_id]"
                           value="default"
                           id="frais_${category.id}_default"
                           data-amount="${baseAmt}"
                           style="display:none;">
                    <div class="optional-options-label">Montant applicable</div>
                    <div class="frais-single-amount">
                        <i class="fas fa-tag"></i>
                        <span>Forfait annuel</span>
                        <strong>${baseAmt.toLocaleString('fr-FR')} FCFA</strong>
                    </div>`;
            } else {
                // Options disponibles : grille de cartes cliquables (pas de radio "montant de base")
                let cards = '';
                options.forEach(option => {
                    const addAmt = parseFloat(option.additional_amount) || parseFloat(option.amount) || 0;
                    let totalAmt = baseAmt + addAmt;
                    if (isNaN(totalAmt) || totalAmt < 0) totalAmt = baseAmt;
                    cards += `
                        <label class="frais-option-card" for="frais_${category.id}_${option.id}">
                            <input class="frais-option optional-variant" type="radio"
                                   name="frais[${category.id}][variant_id]"
                                   value="${option.id}"
                                   id="frais_${category.id}_${option.id}"
                                   data-amount="${totalAmt}"
                                   style="display:none;">
                            <div class="frais-option-check"><i class="fas fa-check"></i></div>
                            <div class="frais-option-name">${option.name}</div>
                            <div class="frais-option-price">${totalAmt.toLocaleString('fr-FR')}</div>
                            <div class="frais-option-unit">FCFA / an</div>
                            ${option.description ? `<small class="frais-option-desc">${option.description}</small>` : ''}
                        </label>`;
                });
                selectionHTML = `
                    <div class="optional-options-label">Choisissez une formule</div>
                    <div class="frais-option-grid">${cards}</div>`;
            }

            return `
                <div class="frais-card optional-frais-card" data-category-id="${category.id}" style="position:relative;overflow:hidden;">
                    <!-- Radio "non souscrit" caché — état par défaut -->
                    <input type="radio" class="frais-option"
                           name="frais[${category.id}][variant_id]"
                           value="" id="frais_${category.id}_none"
                           checked style="display:none;">

                    <!-- En-tête permanent -->
                    <div class="optional-card-header">
                        <div class="optional-card-info">
                            <div class="optional-card-icon">
                                <i class="fas fa-${icon}"></i>
                            </div>
                            <div class="optional-card-text">
                                <h6>${category.name}</h6>
                                ${category.description ? `<p>${category.description}</p>` : ''}
                                <span class="optional-price-hint">${hintText}</span>
                            </div>
                        </div>
                        <div class="optional-card-controls">
                            <span class="badge-optional-pill">Optionnel</span>
                            <!-- Bouton Souscrire (inactif) -->
                            <button type="button" class="btn-opt-subscribe" data-category-id="${category.id}">
                                <i class="fas fa-plus-circle"></i> Souscrire
                            </button>
                            <!-- État souscrit (actif) -->
                            <div class="opt-subscribed-state" style="display:none;">
                                <span class="opt-subscribed-badge">
                                    <i class="fas fa-check-circle"></i> Souscrit
                                </span>
                                <button type="button" class="btn-opt-unsubscribe"
                                        data-category-id="${category.id}"
                                        title="Annuler la souscription">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Zone options — masquée par défaut -->
                    <div class="optional-frais-options"
                         id="options_frais_${category.id}"
                         style="display:none;">
                        ${selectionHTML}
                    </div>
                </div>`;
        }

        // ── FRAIS OBLIGATOIRES : comportement existant ──────────────────────────
        let optionsHTML = '';

        if (configurationType === 'rule' || configurationType === 'variant' || configurationType === 'configuration' || isMandatory) {
            optionsHTML += `
                <div class="form-check mb-2">
                    <input class="form-check-input frais-option" type="radio"
                           name="frais[${category.id}][variant_id]"
                           value="default"
                           id="frais_${category.id}_default"
                           data-amount="${parseFloat(defaultAmount) || 0}"
                           checked>
                    <label class="form-check-label" for="frais_${category.id}_default">
                        ${configurationType === 'variant' ? 'Tarif configuré pour cette classe' :
                          configurationType === 'rule' ? 'Tarif configuré' :
                          configurationType === 'configuration' ? 'Tarif configuré pour cette classe' :
                          'Montant par défaut'} — <strong>${(parseFloat(defaultAmount) || 0).toLocaleString()} FCFA</strong>
                    </label>
                </div>`;
        }

        if (isConfigured && options.length > 0) {
            options.forEach(option => {
                const baseAmount       = parseFloat(defaultAmount) || 0;
                const additionalAmount = parseFloat(option.additional_amount) || parseFloat(option.amount) || 0;
                let totalAmount = configurationType === 'global_options'
                    ? baseAmount + additionalAmount
                    : (additionalAmount || baseAmount);
                if (isNaN(totalAmount) || totalAmount < 0) totalAmount = 0;

                optionsHTML += `
                    <div class="form-check mb-2">
                        <input class="form-check-input frais-option" type="radio"
                               name="frais[${category.id}][variant_id]"
                               value="${option.id}"
                               id="frais_${category.id}_${option.id}"
                               data-amount="${totalAmount}">
                        <label class="form-check-label" for="frais_${category.id}_${option.id}">
                            ${option.name} — <strong>${totalAmount.toLocaleString()} FCFA</strong>
                            ${option.description ? `<small class="text-muted d-block">${option.description}</small>` : ''}
                        </label>
                    </div>`;
            });
        }

        return `
            <div class="frais-card ${!isConfigured ? 'border-warning' : ''}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold mb-0" style="font-size:14px;">
                        <i class="fas fa-${icon} me-2" style="color:var(--kl-primary-light);"></i>
                        ${category.name}
                        ${!isConfigured ? '<i class="fas fa-exclamation-triangle text-warning ms-1" title="Pas d\'options configurées"></i>' : ''}
                    </h6>
                    <div class="d-flex gap-1">
                        <span class="badge bg-danger" style="font-size:10px;">Obligatoire</span>
                        <span class="badge bg-secondary" style="font-size:10px;">${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}</span>
                    </div>
                </div>
                ${category.description ? `<p class="text-muted mb-2" style="font-size:12px;">${category.description}</p>` : ''}
                ${configBadge}
                <div class="frais-options">${optionsHTML}</div>
            </div>`;
    }

    function updateResumeFrais() {
        const resumeContainer = document.getElementById('resumeFrais');
        if (!resumeContainer) return;

        const selectedOptions = document.querySelectorAll('.frais-option:checked');
        let totalAmount = 0;
        let resumeHTML  = '';

        selectedOptions.forEach(option => {
            if (!option.value || option.value === '') return;

            // Lire le montant depuis data-amount (présent sur tous les radios)
            const amount = parseInt(option.dataset.amount) || 0;
            if (amount <= 0) return;

            // Lire le nom de la catégorie depuis le .frais-card parent
            const fraisCard = option.closest('.frais-card');
            let categoryName = 'Frais';
            if (fraisCard) {
                const h6 = fraisCard.querySelector('h6');
                if (h6) {
                    let text = h6.textContent.trim().replace(/[\uF000-\uF8FF]/g, '').trim();
                    categoryName = text.split('\n')[0].trim() || categoryName;
                }
            }

            totalAmount += amount;
            resumeHTML += `<div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                    <span>${categoryName}</span>
                    <span class="fw-bold">${amount.toLocaleString()} FCFA</span>
                </div>`;
        });

        if (resumeHTML) {
            resumeHTML += `<hr><div class="d-flex justify-content-between fw-bold" style="font-size:14px;">
                <span>Total</span>
                <span style="color:var(--kl-primary);">${(totalAmount || 0).toLocaleString()} FCFA</span>
            </div>`;
            resumeContainer.innerHTML = resumeHTML;
        } else {
            resumeContainer.innerHTML = '<div class="text-center text-muted py-2" style="font-size:13px;">Aucun frais sélectionné</div>';
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('frais-option')) updateResumeFrais();
    });

    // ── Souscription frais optionnels ──────────────────────────────────────
    function activateOptionalFrais(categoryId) {
        const card       = document.querySelector(`.optional-frais-card[data-category-id="${categoryId}"]`);
        const optionsDiv = document.getElementById('options_frais_' + categoryId);
        const noneRadio  = document.getElementById('frais_' + categoryId + '_none');
        if (!card) return;

        card.classList.add('is-subscribed');
        if (optionsDiv) optionsDiv.style.display = 'block';
        if (noneRadio)  noneRadio.checked = false;

        card.querySelector('.btn-opt-subscribe').style.display = 'none';
        card.querySelector('.opt-subscribed-state').style.display = 'flex';

        // Auto-sélectionner la première option-card (ou le radio unique)
        const firstCard = optionsDiv && optionsDiv.querySelector('.frais-option-card');
        if (firstCard) {
            firstCard.classList.add('selected');
            const radio = firstCard.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        } else {
            const firstRadio = optionsDiv && optionsDiv.querySelector('.optional-variant');
            if (firstRadio) firstRadio.checked = true;
        }
        updateResumeFrais();
    }

    function deactivateOptionalFrais(categoryId) {
        const card       = document.querySelector(`.optional-frais-card[data-category-id="${categoryId}"]`);
        const optionsDiv = document.getElementById('options_frais_' + categoryId);
        const noneRadio  = document.getElementById('frais_' + categoryId + '_none');
        if (!card) return;

        card.classList.remove('is-subscribed');
        if (optionsDiv) {
            optionsDiv.style.display = 'none';
            optionsDiv.querySelectorAll('.frais-option-card').forEach(c => c.classList.remove('selected'));
            optionsDiv.querySelectorAll('.optional-variant').forEach(r => r.checked = false);
        }
        if (noneRadio) noneRadio.checked = true;

        card.querySelector('.btn-opt-subscribe').style.display = '';
        card.querySelector('.opt-subscribed-state').style.display = 'none';
        updateResumeFrais();
    }

    document.addEventListener('click', function(e) {
        // Souscrire
        const subscribeBtn = e.target.closest('.btn-opt-subscribe');
        if (subscribeBtn) { activateOptionalFrais(subscribeBtn.dataset.categoryId); return; }

        // Désabonner
        const unsubscribeBtn = e.target.closest('.btn-opt-unsubscribe');
        if (unsubscribeBtn) { deactivateOptionalFrais(unsubscribeBtn.dataset.categoryId); return; }

        // Clic sur une option-card → sélectionner
        const optionCard = e.target.closest('.frais-option-card');
        if (optionCard) {
            const grid = optionCard.closest('.frais-option-grid');
            if (grid) grid.querySelectorAll('.frais-option-card').forEach(c => c.classList.remove('selected'));
            optionCard.classList.add('selected');
            const radio = optionCard.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateResumeFrais();
        }
    });

    // =============================================
    // STATUT AFFECTATION
    // =============================================
    window.updateAffectationInfo = function() {
        const select  = document.getElementById('affectation_status');
        const infoDiv = document.getElementById('affectation-info');
        const value   = select.value;
        let content   = '';

        switch (value) {
            case 'affecté':
                content = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-check-circle mt-1" style="color:var(--kl-success);"></i>
                        <div>
                            <div class="fw-bold mb-1" style="color:var(--kl-success);font-size:13px;">Étudiant Affecté par l'État</div>
                            <div class="text-muted" style="font-size:12px;">Plateforme : bac.mesrs-ci.net</div>
                            <ul class="mt-2 mb-0 ps-3" style="font-size:12px;color:#374151;">
                                <li>Affectation officielle par le MESRS après le BAC</li>
                                <li>Éligible à une subvention étatique</li>
                            </ul>
                            <div class="mt-2" style="font-size:12px;color:var(--kl-success);font-weight:600;">
                                <i class="fas fa-coins me-1"></i> Frais réduits applicables
                            </div>
                        </div>
                    </div>`;
                break;
            case 'réaffecté':
                content = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-sync-alt mt-1" style="color:var(--kl-warning);"></i>
                        <div>
                            <div class="fw-bold mb-1" style="color:var(--kl-warning);font-size:13px;">Étudiant Réaffecté par la DOB</div>
                            <div class="text-muted" style="font-size:12px;">Organisme : Direction de l'Orientation et des Bourses</div>
                            <ul class="mt-2 mb-0 ps-3" style="font-size:12px;color:#374151;">
                                <li>Initialement affecté dans un autre établissement</li>
                                <li>Réaffectation officielle après demande</li>
                            </ul>
                            <div class="mt-2" style="font-size:12px;color:var(--kl-warning);font-weight:600;">
                                <i class="fas fa-coins me-1"></i> Subvention étatique maintenue
                            </div>
                        </div>
                    </div>`;
                break;
            case 'non_affecté':
                content = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-times-circle mt-1" style="color:var(--kl-danger);"></i>
                        <div>
                            <div class="fw-bold mb-1" style="color:var(--kl-danger);font-size:13px;">Étudiant Non Affecté</div>
                            <div class="text-muted" style="font-size:12px;">Statut : Inscription directe</div>
                            <ul class="mt-2 mb-0 ps-3" style="font-size:12px;color:#374151;">
                                <li>Non retenu dans le système public d'affectation</li>
                                <li>Inscription directe dans l'établissement</li>
                            </ul>
                            <div class="mt-2" style="font-size:12px;color:var(--kl-danger);font-weight:600;">
                                <i class="fas fa-exclamation-triangle me-1"></i> Tarif complet sans subvention
                            </div>
                        </div>
                    </div>`;
                break;
            default:
                content = `<span class="text-muted" style="font-size:13px;"><i class="fas fa-arrow-left me-2"></i>Sélectionnez un statut pour voir les détails</span>`;
        }

        infoDiv.innerHTML = content;

        if (value && document.getElementById('classe_id')?.value) {
            loadFraisForClasse();
        }
    };

    window.loadFraisForClasse = function() {
        const classeSelect = document.getElementById('classe_id');
        if (!classeSelect || !classeSelect.value) return;
        const changeEvent = new Event('change', { bubbles: true });
        classeSelect.dispatchEvent(changeEvent);
    };

    function initClasseAffectationState() {
        const affectationSelect = document.getElementById('affectation_status');
        if (affectationSelect && affectationSelect.value) updateAffectationInfo();
        const classeSelect = document.getElementById('classe_id');
        if (classeSelect && classeSelect.value) loadFraisForClasse();
    }

    initClasseAffectationState();
    updateParentToggleSub();

    // =============================================
    // STEPPER VISUEL — SCROLL-DRIVEN (fixed, vertical)
    // Algo :
    //   • Pour chaque nœud i, on mesure top(section i) et top(section i+1)
    //   • La progression du trait i = clamp((scrollMid - top_i) / (top_{i+1} - top_i), 0, 1)
    //   • Le nœud i est "done" si scrollMid > top_{i+1}, "active" si scrollMid ∈ [top_i, top_{i+1}]
    //   • L'accordéon parents modifie top(section-frais) → recalcul complet à chaque event
    //   • Animation lumière : déclenchée quand --fill passe de 0% à >5% (début du remplissage)
    // =============================================
    (function initScrollStepper() {
        // sectionIds est aligné sur les data-step de la nav stepper.
        // Le step accessibility (6) est conditionnel : on l'inclut seulement si
        // la section et le step existent dans le DOM (gating Blade côté serveur).
        const sectionIds = [
            'section-identite',
            'section-academique',
            'section-affectation',
            'section-parents',
            'section-frais',
        ];
        if (document.getElementById('section-accessibilite') && document.getElementById('step-accessibility')) {
            sectionIds.push('section-accessibilite');
        }

        const stepItems  = Array.from(document.querySelectorAll('.step-item[data-step]'))
                                .sort((a, b) => +a.dataset.step - +b.dataset.step);
        const trackEls   = Array.from(document.querySelectorAll('.step-track'));  // 4 traits (entre 5 nœuds)

        // Calcule la hauteur réelle de chaque trait (distance entre deux nœuds consécutifs)
        // et l'injecte en CSS var --track-height pour que le trait s'adapte au contenu
        function updateTrackHeights() {
            const circles = stepItems.map(item => item.querySelector('.step-circle'));
            circles.forEach((circ, i) => {
                if (i >= circles.length - 1) return;  // pas de trait après le dernier
                const rectA = circ.getBoundingClientRect();
                const rectB = circles[i + 1].getBoundingClientRect();
                // distance centre-à-centre entre deux cercles consécutifs
                const centerA = rectA.top + rectA.height / 2;
                const centerB = rectB.top + rectB.height / 2;
                const h = Math.max(20, Math.round(centerB - centerA - rectA.height / 2 - rectB.height / 2));
                if (trackEls[i]) trackEls[i].style.setProperty('--track-height', h + 'px');
            });
        }

        // Retourne les tops absolus (window.scrollY + getBCR.top) de chaque section
        function getSectionTops() {
            return sectionIds.map(id => {
                const el = document.getElementById(id);
                if (!el) return 0;
                return el.getBoundingClientRect().top + window.scrollY;
            });
        }

        let rafId = null;
        let prevFills = new Array(4).fill(0);  // pour détecter début de remplissage → lumière

        function updateStepper() {
            const tops     = getSectionTops();
            const scrollMid = window.scrollY + window.innerHeight * 0.42;  // légèrement au-dessus du centre

            // Détermine l'étape active (1-based)
            let active = 1;
            tops.forEach((top, i) => {
                if (scrollMid >= top) active = i + 1;
            });

            // Met à jour les classes done/active sur les nœuds (steps réguliers)
            stepItems.forEach((item, i) => {
                const step = i + 1;
                if (item.classList.contains('step-item--optional')) return; // pilote externe
                item.classList.remove('active', 'done');
                if (step < active)       item.classList.add('done');
                else if (step === active) item.classList.add('active');
            });

            // Calcule le fill de chaque trait (0–100%)
            trackEls.forEach((track, i) => {
                const topStart = tops[i];       // section i   (nœud i)
                const topEnd   = tops[i + 1];   // section i+1 (nœud i+1)

                let fill = 0;
                if (topEnd > topStart) {
                    fill = Math.min(1, Math.max(0, (scrollMid - topStart) / (topEnd - topStart)));
                } else if (scrollMid >= topStart) {
                    fill = 1;
                }

                const fillPct = Math.round(fill * 100);
                track.querySelector('.step-track-fill').style.setProperty('--fill', fillPct + '%');

                // Déclenche l'animation lumière quand le fill commence à monter (passage 0→>3%)
                if (fillPct > 3 && prevFills[i] <= 3 && fillPct < 97) {
                    track.classList.remove('traveling');
                    // Force reflow pour relancer l'animation CSS
                    void track.offsetWidth;
                    track.classList.add('traveling');
                    // Retire la classe après la durée de l'animation (0.7s)
                    setTimeout(() => track.classList.remove('traveling'), 720);
                }
                prevFills[i] = fillPct;
            });
        }

        function onScroll() {
            if (rafId) return;
            rafId = requestAnimationFrame(() => {
                rafId = null;
                updateStepper();
            });
        }

        // Recalcul des hauteurs de traits quand l'accordéon parents s'ouvre/ferme
        const parentsToggle = document.getElementById('parents-toggle-btn');
        if (parentsToggle) {
            parentsToggle.addEventListener('click', () => {
                // Attendre la fin de la transition (300ms) puis recalculer
                setTimeout(() => {
                    updateTrackHeights();
                    updateStepper();
                }, 350);
            });
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', () => { updateTrackHeights(); updateStepper(); }, { passive: true });

        // Init
        updateTrackHeights();
        updateStepper();

        // ─── Pilote externe : step Accessibilité (optionnel) ───
        // Le step 6 ne suit PAS le scroll. Son état est piloté par les events
        // dispatchés depuis la section accessibility (toggle open/close + saisie).
        const accStep = document.getElementById('step-accessibility');
        if (accStep) {
            const accTrack = document.querySelector('.step-track[data-track="5"]'); // trait entre Frais et Accessibilité
            document.addEventListener('ia:accessibility-state', (ev) => {
                const { open, hasData } = ev.detail;
                accStep.classList.remove('active', 'done');
                if (hasData) {
                    accStep.classList.add('done');
                    if (accTrack) accTrack.querySelector('.step-track-fill').style.setProperty('--fill', '100%');
                } else if (open) {
                    accStep.classList.add('active');
                    if (accTrack) accTrack.querySelector('.step-track-fill').style.setProperty('--fill', '50%');
                } else {
                    if (accTrack) accTrack.querySelector('.step-track-fill').style.setProperty('--fill', '0%');
                }
                // Recalc des hauteurs au cas où la section change de hauteur
                updateTrackHeights();
            });
        }
    })();

    // =============================================
    // GESTION MATRICULES (inchangée)
    // =============================================
    const matriculeInput    = document.getElementById('matriculeInput');
    const matriculeContainer = document.getElementById('matriculeContainer');
    const generateBtn       = document.getElementById('generateMatriculeBtn');
    const checkBtn          = document.getElementById('checkMatriculeBtn');
    const matriculeStatus   = document.getElementById('matriculeStatus');
    const matriculeMode     = document.getElementById('matriculeMode');
    const matriculeHelp     = document.getElementById('matriculeHelp');
    const genreSelect       = document.querySelector('select[name="sexe"]');
    const classeSelect      = document.getElementById('classe_id');

    let currentMatriculeMode = 'automatique';
    let niveauConfig = null;

    fetch('/esbtp/matricule-config/mode-info', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => { currentMatriculeMode = data.mode || 'automatique'; updateMatriculeUI(); })
    .catch(() => updateMatriculeUI());

    function updateMatriculeUI() {
        if (currentMatriculeMode === 'automatique') {
            if (matriculeContainer) matriculeContainer.style.display = 'none';
            if (matriculeInput) matriculeInput.value = '';
        } else {
            if (matriculeContainer) matriculeContainer.style.display = 'block';
            if (matriculeMode)  { matriculeMode.textContent = 'MANUEL'; matriculeMode.className = 'badge bg-warning ms-1'; }
            if (matriculeHelp)  matriculeHelp.textContent = 'Saisissez manuellement le matricule (vérification anti-doublon)';
            if (generateBtn) generateBtn.style.display = 'none';
            if (checkBtn) checkBtn.style.display = 'block';
            if (matriculeInput) { matriculeInput.readOnly = false; matriculeInput.placeholder = 'Ex: MAT25-0001'; }
        }
    }

    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            const genre = genreSelect ? genreSelect.value : null;
            if (!genre) { showMatriculeStatus('Veuillez d\'abord sélectionner le genre/sexe', 'warning'); return; }
            if (!niveauConfig) { showMatriculeStatus('Niveau d\'études non configuré. Contactez l\'équipe technique.', 'danger'); return; }
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
            fetch('/esbtp/matricule-config/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
                body: JSON.stringify({ niveau_etude_code: niveauConfig.code, genre, annee: new Date().getFullYear() })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { matriculeInput.value = data.matricule; showMatriculeStatus('Matricule généré avec succès', 'success'); }
                else showMatriculeStatus(data.message || 'Erreur lors de la génération', 'danger');
            })
            .catch(() => showMatriculeStatus('Erreur de connexion', 'danger'))
            .finally(() => { generateBtn.disabled = false; generateBtn.innerHTML = '<i class="fas fa-magic"></i> Générer'; });
        });
    }

    if (checkBtn) checkBtn.addEventListener('click', checkMatriculeManuel);

    if (currentMatriculeMode === 'manuel' && matriculeInput) {
        let checkTimeout;
        matriculeInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            if (this.value.length >= 3) checkTimeout = setTimeout(checkMatriculeManuel, 500);
        });
    }

    function checkMatriculeManuel() {
        const matricule = matriculeInput ? matriculeInput.value.trim() : '';
        if (!matricule) { showMatriculeStatus('', ''); return; }
        if (checkBtn) { checkBtn.disabled = true; checkBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
        fetch('/esbtp/matricule-config/check', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
            body: JSON.stringify({ matricule })
        })
        .then(r => r.json())
        .then(data => {
            if (data.exists) showMatriculeStatus('❌ Ce matricule existe déjà', 'danger');
            else showMatriculeStatus('✅ Matricule disponible', 'success');
        })
        .catch(() => showMatriculeStatus('Erreur de vérification', 'warning'))
        .finally(() => { if (checkBtn) { checkBtn.disabled = false; checkBtn.innerHTML = '<i class="fas fa-search"></i> Vérifier'; } });
    }

    function showMatriculeStatus(message, type) {
        if (!matriculeStatus) return;
        if (!message) { matriculeStatus.innerHTML = ''; return; }
        const cls = { success: 'alert-success', danger: 'alert-danger', warning: 'alert-warning', info: 'alert-info' }[type] || 'alert-info';
        matriculeStatus.innerHTML = `<small class="alert ${cls} p-1 m-0 d-inline-block">${message}</small>`;
    }

    async function generateMatriculeAuto() {
        const genre = genreSelect ? genreSelect.value : null;
        if (!genre || !niveauConfig) return null;
        try {
            const r = await fetch('/esbtp/matricule-config/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
                body: JSON.stringify({ niveau_etude_code: niveauConfig.code, genre, annee: new Date().getFullYear() })
            });
            const data = await r.json();
            return (data.success && data.matricule) ? data.matricule : null;
        } catch { return null; }
    }

    async function checkMatriculeDisponible() {
        const matricule = matriculeInput ? matriculeInput.value.trim() : '';
        if (!matricule) return false;
        try {
            const r = await fetch('/esbtp/matricule-config/check', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
                body: JSON.stringify({ matricule })
            });
            const data = await r.json();
            return !data.exists;
        } catch { return false; }
    }

    if (classeSelect) {
        classeSelect.addEventListener('change', function() {
            const classeId = this.value;
            if (classeId && currentMatriculeMode === 'automatique') {
                fetch(`/esbtp/api/classes/${classeId}/niveau-config`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    niveauConfig = data.niveau_config;
                    if (!niveauConfig && generateBtn) {
                        showMatriculeStatus('⚠️ Niveau non configuré pour génération automatique', 'warning');
                        generateBtn.disabled = true;
                    } else {
                        showMatriculeStatus('', '');
                        if (generateBtn) generateBtn.disabled = false;
                    }
                })
                .catch(() => { niveauConfig = null; });
            }
        });
    }

    const inscriptionForm2 = document.getElementById('inscriptionForm');
    if (inscriptionForm2) {
        inscriptionForm2.addEventListener('submit', async function(e) {
            if (currentMatriculeMode === 'automatique') {
                if (!genreSelect || !genreSelect.value) {
                    e.preventDefault();
                    alert('⚠️ Veuillez sélectionner le genre/sexe avant de soumettre le formulaire.');
                    if (genreSelect) genreSelect.focus();
                    return;
                }
                if (matriculeInput) matriculeInput.value = '';
            } else if (currentMatriculeMode === 'manuel') {
                e.preventDefault();
                const matricule = matriculeInput ? matriculeInput.value.trim() : '';
                if (!matricule) {
                    alert('⚠️ Le matricule est obligatoire.\n\nVeuillez saisir un matricule avant de soumettre le formulaire.');
                    if (matriculeInput) matriculeInput.focus();
                    return;
                }
                const isAvailable = await checkMatriculeDisponible();
                if (isAvailable) {
                    inscriptionForm2.submit();
                } else {
                    alert('❌ Ce matricule existe déjà dans la base de données.\n\nVeuillez en saisir un autre.\n\nMatricule saisi: ' + matricule);
                    if (matriculeInput) { matriculeInput.focus(); matriculeInput.select(); }
                    showMatriculeStatus('❌ Ce matricule existe déjà', 'danger');
                }
            }
        });
    }
});
</script>
@endpush
