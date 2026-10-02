@extends('layouts.app')

@section('title', 'Gestion des étudiants - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('css/etudiants-index.css') }}?v={{ @filemtime(public_path('css/etudiants-index.css')) ?: '1' }}">
@endsection

@section('content')
@php
    // Shell mobile actif : en-tete, filtres et liste de bureau se cachent sous 992px
    // au profit du partial _index-mobile (m-*). Les modales restent hors de ce
    // masquage, pour que l'ecran du telephone puisse les ouvrir.
    $eimShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $eimDesk = $eimShell ? 'm-only-desktop' : '';
@endphp
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header {{ $eimDesk }}">
            <div class="header-left">
                <h1>Étudiants</h1>
                <p class="header-subtitle">Gestion des étudiants de l'établissement</p>
            </div>
            <div class="header-actions">
                @can('inscriptions.create')
                <a href="{{ route('esbtp.inscriptions.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Ajouter un étudiant
                </a>
                @endcan
                @can('inscriptions.view')
                <a href="{{ route('esbtp.reinscription.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-user-graduate"></i>Réinscriptions
                </a>
                @endcan
                @can('trash.view')
                <a href="{{ route('esbtp.trash.index') }}" class="btn-acasi secondary" title="Voir les étudiants/inscriptions/paiements supprimés">
                    <i class="fas fa-trash-restore"></i>Corbeille
                </a>
                @endcan
                @can('inscriptions.create')
                <button type="button"
                        class="btn-acasi secondary"
                        data-bs-toggle="modal"
                        data-bs-target="#bulkReinscriptionModal"
                        title="Lancer une réinscription groupée — diagnostic moyenne/décision/frais soldés par étudiant">
                    <i class="fas fa-layer-group"></i>Réinscription groupée
                </button>
                @endcan

                {{-- Export modal trigger --}}
                <button type="button" class="btn-acasi secondary" data-bs-toggle="modal" data-bs-target="#exportModal" style="gap: 6px;">
                    <i class="fas fa-download"></i>Exporter
                </button>
            </div>
        </div>

        {{-- ============================================================
             EXPORT MODAL — Multi-select avec checkboxes
             ============================================================ --}}
        <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true"
             x-data="exportModal()">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content export-modal-content">
                    {{-- Header --}}
                    <div class="export-modal-header">
                        <div class="export-modal-title">
                            <div class="export-modal-icon">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <div>
                                <h5 id="exportModalLabel">Exporter la liste des étudiants</h5>
                                <p class="export-modal-subtitle">
                                    Sélectionnez les éléments à inclure dans l'export
                                </p>
                            </div>
                        </div>
                        <button type="button" class="export-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="export-modal-body">
                        {{-- Global toolbar --}}
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label class="export-toggle-all export-toggle-inline" @click.prevent="toggleAll()">
                                    <span class="export-checkbox" :class="{ 'checked': allSelected, 'partial': someSelected && !allSelected }">
                                        <i x-show="allSelected" class="fas fa-check"></i>
                                        <i x-show="someSelected && !allSelected" class="fas fa-minus"></i>
                                    </span>
                                    <span x-text="allSelected ? 'Tout désélect.' : 'Tout sélect.'"></span>
                                </label>
                                <span class="export-select-counter" style="font-size: 12px; background: #f0f4ff; padding: 2px 10px; border-radius: 20px; color: #0453cb; font-weight: 600;">
                                    <span x-text="selectedCombinations.length"></span> sélection<span x-show="selectedCombinations.length > 1">s</span>
                                </span>
                            </div>
                        </div>

                        {{-- Filières & Niveaux grouped (like matières pattern) --}}
                        <div style="display: flex; flex-direction: column; gap: 10px; max-height: 340px; overflow-y: auto; padding-right: 4px;">
                            @foreach($filieres as $f)
                            <div class="export-filiere-card" :class="{ 'has-selection': hasFiliereSelection({{ $f->id }}) }">
                                {{-- Filière header --}}
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary, #0453cb); flex-shrink: 0;"></span>
                                        <span style="font-weight: 600; font-size: 13px; color: #1e293b;">{{ $f->name }}</span>
                                        @if($f->code)
                                        <span style="font-size: 10px; background: #e0e7ff; color: #3730a3; padding: 1px 6px; border-radius: 4px; font-weight: 500;">{{ $f->code }}</span>
                                        @endif
                                    </div>
                                    <label style="font-size: 11px; color: var(--primary, #0453cb); cursor: pointer; display: flex; align-items: center; gap: 4px; font-weight: 500;"
                                           @click.prevent="toggleAllNiveauxOfFiliere({{ $f->id }})">
                                        <i class="fas" :class="isFiliereFullySelected({{ $f->id }}) ? 'fa-times-circle' : 'fa-check-double'" style="font-size: 10px;"></i>
                                        <span x-text="isFiliereFullySelected({{ $f->id }}) ? 'Tout désélect.' : 'Tout sélect.'"></span>
                                    </label>
                                </div>
                                {{-- Niveaux pills --}}
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    @foreach($niveaux as $n)
                                    @php
                                        $hasClasses = $classes->where('filiere_id', $f->id)->where('niveau_etude_id', $n->id)->isNotEmpty();
                                    @endphp
                                    @if($hasClasses)
                                    <span class="export-niveau-pill"
                                          :class="{ 'active': isCombinationSelected({{ $f->id }}, {{ $n->id }}) }"
                                          @click="toggleCombination({{ $f->id }}, {{ $n->id }})">
                                        <span class="export-pill-check">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        {{ $n->name }}
                                        @if($n->code)
                                        <span style="font-size: 9px; opacity: 0.65; margin-left: 2px;">{{ $n->code }}</span>
                                        @endif
                                    </span>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Selectable classes --}}
                        <div class="export-classes-auto" x-show="comboClasses.length > 0" style="margin-top: 12px;">
                            <div class="export-classes-auto-title">
                                <i class="fas fa-chalkboard"></i>
                                Classes incluses
                                <span class="export-select-counter" style="font-size: 11px;" x-text="'(' + selectedClassIds.length + ' / ' + comboClasses.length + ')'"></span>
                                <span style="margin-left: auto; font-size: 11px; color: var(--primary, #0453cb); cursor: pointer; font-weight: 500;"
                                      @click="toggleAllClasses()">
                                    <i class="fas" :class="selectedClassIds.length === comboClasses.length ? 'fa-times-circle' : 'fa-check-double'" style="font-size: 10px; margin-right: 2px;"></i>
                                    <span x-text="selectedClassIds.length === comboClasses.length ? 'Tout décocher' : 'Tout cocher'"></span>
                                </span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <template x-for="cls in comboClasses" :key="cls.id">
                                    <span class="export-class-tag"
                                          :class="{ 'unchecked': !isClassSelected(cls.id) }"
                                          @click="toggleClass(cls.id)">
                                        <i class="fas tag-check" :class="isClassSelected(cls.id) ? 'fa-check' : 'fa-plus'"></i>
                                        <span x-text="cls.name"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div x-show="comboClasses.length === 0" style="margin-top: 12px; text-align: center; color: #94a3b8; font-size: 12px; padding: 16px;">
                            <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                            Cliquez sur les niveaux dans les filières pour sélectionner les classes à exporter
                        </div>

                        {{-- Group by --}}
                        <div class="export-panel-section">
                            <label class="export-panel-label">Regrouper par</label>
                            <div class="export-group-options">
                                <label class="export-radio" :class="{ 'active': exportGroupBy === '' }" @click="exportGroupBy = ''">
                                    <input type="radio" name="export_group" value="" x-model="exportGroupBy">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Tout combiné</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'classe' }" @click="exportGroupBy = 'classe'">
                                    <input type="radio" name="export_group" value="classe" x-model="exportGroupBy">
                                    <i class="fas fa-chalkboard"></i>
                                    <span>Par classe</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'filiere' }" @click="exportGroupBy = 'filiere'">
                                    <input type="radio" name="export_group" value="filiere" x-model="exportGroupBy">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Par filière</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'niveau' }" @click="exportGroupBy = 'niveau'">
                                    <input type="radio" name="export_group" value="niveau" x-model="exportGroupBy">
                                    <i class="fas fa-signal"></i>
                                    <span>Par niveau</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'filiere_niveau' }" @click="exportGroupBy = 'filiere_niveau'">
                                    <input type="radio" name="export_group" value="filiere_niveau" x-model="exportGroupBy">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Par filière et niveau</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="export-modal-footer">
                        <div class="export-panel-hint">
                            <i class="fas fa-info-circle"></i>
                            <span>Les filtres de la page seront aussi appliqués</span>
                        </div>
                        <div class="export-format-buttons">
                            <button type="button" class="export-btn export-btn-excel" @click="doExport('excel')">
                                <i class="fas fa-file-excel"></i>
                                <div>
                                    <strong>Excel</strong>
                                    <small>.xlsx</small>
                                </div>
                            </button>
                            <button type="button" class="export-btn export-btn-pdf" @click="doExport('pdf')">
                                <i class="fas fa-file-pdf"></i>
                                <div>
                                    <strong>PDF</strong>
                                    <small>.pdf</small>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-moderne etu-carte-filtres {{ $eimDesk }}">
            <div class="p-lg">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif


                <!-- Filtres de recherche (Desktop uniquement) -->
                <div class="desktop-filters">
                    <div class="section-title mb-md" style="display:flex; align-items:center; justify-content:space-between;">
                        <span><i class="fas fa-filter me-2"></i>Filtres de recherche</span>
                        <button type="button" id="toggle-advanced-filters-btn" style="background:none;border:1px solid #e0e0e0;border-radius:6px;padding:0.35rem 0.9rem;font-size:0.8rem;color:#5c5c5c;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;font-weight:500;">
                            <i class="fas fa-sliders-h"></i> Filtres avancés <i class="fas fa-chevron-down" style="font-size:0.6rem;"></i>
                        </button>
                    </div>
                    @php
                        $etSysteme = in_array(request('systeme'), ['BTS', 'LMD'], true) ? request('systeme') : '';
                    @endphp
                    <form method="GET" action="{{ route('esbtp.etudiants.index') }}" id="search-form" x-data="{ etSysteme: '{{ $etSysteme }}' }">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="search" class="form-label">Recherche</label>
                                        <input type="text" class="form-control search-bar" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        {{-- Switch Système BTS/LMD : pilote l'affichage du 2e filtre. --}}
                                        <label for="systeme" class="form-label">Système</label>
                                        <select class="form-select year-selector" id="systeme" name="systeme" x-model="etSysteme">
                                            <option value="" @selected(!request('systeme'))>Tous</option>
                                            <option value="BTS" @selected(request('systeme') === 'BTS')>BTS</option>
                                            <option value="LMD" @selected(request('systeme') === 'LMD')>LMD</option>
                                        </select>
                                    </div>
                                    {{-- BTS / Tous : Filière BTS classique --}}
                                    <div class="col-md-3 mb-3" x-show="etSysteme !== 'LMD'" x-cloak>
                                        <label for="filiere" class="form-label">Filière BTS</label>
                                        <select class="form-select year-selector" id="filiere" name="filiere">
                                            <option value="">Toutes les filières</option>
                                            @foreach($filieres as $f)
                                                <option value="{{ $f->id }}" {{ isset($filiere) && $filiere == $f->id ? 'selected' : '' }}>
                                                    {{ $f->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- LMD : Mention picker + Parcours picker premium --}}
                                    <div class="col-md-3 mb-3" x-show="etSysteme === 'LMD'" x-cloak>
                                        <label class="form-label">Mention LMD</label>
                                        <x-au-mention-picker
                                            name="mention"
                                            :value="request('mention')"
                                            :mentions="$mentions"
                                            placeholder="Toutes les mentions"
                                        />
                                    </div>
                                    <div class="col-md-2 mb-3" x-show="etSysteme === 'LMD'" x-cloak>
                                        <label class="form-label">@rang('parcours')</label>
                                        <x-au-parcours-picker
                                            name="parcours"
                                            :value="request('parcours')"
                                            :parcours="$parcoursList"
                                            :mention-filter="request('mention')"
                                        />
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="niveau" class="form-label">Niveau d'études</label>
                                        <select class="form-select year-selector" id="niveau" name="niveau">
                                            <option value="">Tous les niveaux</option>
                                            @foreach($niveaux as $n)
                                                <option value="{{ $n->id }}" {{ isset($niveau) && $niveau == $n->id ? 'selected' : '' }}>
                                                    {{ $n->name }} ({{ $n->type }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div id="advanced-filters" style="display:none;">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="classe" class="form-label">Classe</label>
                                        <div x-data="searchableSelect({
                                            options: [
                                                { value: '', label: 'Toutes les classes' },
                                                @foreach($classes as $classeOption)
                                                {
                                                    value: '{{ $classeOption->id }}',
                                                    label: '{!! str_replace("'", "\\'", $classeOption->name) !!} @if($classeOption->filiere || $classeOption->niveauEtude)({!! str_replace("'", "\\'", $classeOption->filiere->name ?? "Filière N/A") !!} - {!! str_replace("'", "\\'", $classeOption->niveauEtude->name ?? "Niveau N/A") !!})@endif'
                                                },
                                                @endforeach
                                            ],
                                            selected: '{{ $classe ?? '' }}',
                                            name: 'classe',
                                            placeholder: 'Rechercher une classe...'
                                        })" class="searchable-select" :class="{ 'active': open }" @click.away="open = false">
                                            <input type="hidden" name="classe" :value="selectedValue" id="classe">
                                            <button type="button" class="searchable-select-trigger" @click="open = !open">
                                                <span class="searchable-select-trigger-text" :class="{ 'est-indicatif': !selectedLabel }">
                                                    <span x-text="selectedLabel || placeholder">Rechercher une classe...</span>
                                                </span>
                                                <i class="fas fa-chevron-down searchable-select-icon"></i>
                                            </button>
                                            <div x-show="open" class="searchable-select-dropdown" x-cloak>
                                                <div class="searchable-select-search">
                                                    <input type="text" x-model="search" @input="filterOptions" placeholder="Tapez pour rechercher..." @click.stop x-ref="searchInput">
                                                </div>
                                                <div class="searchable-select-options">
                                                    <template x-if="filteredOptions.length === 0">
                                                        <div class="searchable-select-no-results">
                                                            <i class="fas fa-search mb-2"></i>
                                                            <div>Aucune classe trouvée</div>
                                                        </div>
                                                    </template>
                                                    <template x-for="option in filteredOptions" :key="option.value">
                                                        <div class="searchable-select-option" :class="{ 'selected': option.value === selectedValue }" @click="selectOption(option)">
                                                            <span x-text="option.label"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="annee" class="form-label">Année universitaire</label>
                                        <select class="form-select year-selector" id="annee" name="annee">
                                            <option value="">Toutes les années</option>
                                            @foreach($annees as $a)
                                                <option value="{{ $a->id }}" {{ isset($annee) && $annee == $a->id ? 'selected' : '' }}>
                                                    {{ $a->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">Statut</label>
                                        <select class="form-select year-selector" id="status" name="status">
                                            <option value="">Tous les statuts</option>
                                            <option value="actif" {{ isset($status) && $status == 'actif' ? 'selected' : '' }}>Actif</option>
                                            <option value="inactif" {{ isset($status) && $status == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="sexe" class="form-label">Genre</label>
                                        <select class="form-select year-selector" id="sexe" name="sexe">
                                            <option value="">Tous les genres</option>
                                            <option value="M" {{ ($sexe ?? null) === 'M' ? 'selected' : '' }}>Masculin</option>
                                            <option value="F" {{ ($sexe ?? null) === 'F' ? 'selected' : '' }}>Féminin</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="affectation_status" class="form-label">Statut d'affectation ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                                        <select class="form-select year-selector" id="affectation_status" name="affectation_status">
                                            <option value="">Tous les statuts d'affectation</option>
                                            <option value="affecté" {{ isset($affectationStatus) && $affectationStatus == 'affecté' ? 'selected' : '' }}>Affecté</option>
                                            <option value="réaffecté" {{ isset($affectationStatus) && $affectationStatus == 'réaffecté' ? 'selected' : '' }}>Réaffecté</option>
                                            <option value="non_affecté" {{ isset($affectationStatus) && $affectationStatus == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="inscrit_annee_courante" class="form-label">Inscription validée ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                                        <select class="form-select year-selector" id="inscrit_annee_courante" name="inscrit_annee_courante">
                                            <option value="">Tous</option>
                                            <option value="validee" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'validee' ? 'selected' : '' }}>Oui (Validée)</option>
                                            <option value="en_attente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                            <option value="absente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'absente' ? 'selected' : '' }}>Absente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="est_transfert" class="form-label">Transfert</label>
                                        <select class="form-select year-selector" id="est_transfert" name="est_transfert">
                                            <option value="">Tous</option>
                                            <option value="1" {{ isset($estTransfert) && $estTransfert == '1' ? 'selected' : '' }}>Oui (Transferts)</option>
                                            <option value="0" {{ isset($estTransfert) && $estTransfert == '0' ? 'selected' : '' }}>Non (Locaux)</option>
                                        </select>
                                    </div>
                                    @can('students.accessibility.view')
                                    <div class="col-md-4 mb-3">
                                        <label for="accessibility" class="form-label">
                                            <i class="fas fa-universal-access text-primary me-1"></i>Accessibilité
                                        </label>
                                        <select class="form-select year-selector" id="accessibility" name="accessibility">
                                            @include('esbtp.etudiants.partials.accessibility-filter-options', ['current' => $accessibility ?? null])
                                        </select>
                                    </div>
                                    @endcan
                                    <div class="col-md-4 d-flex align-items-end mb-3">
                                        <button type="submit" class="btn-acasi primary me-2">
                                            <i class="fas fa-search"></i>Filtrer
                                        </button>
                                        <button type="button" class="btn-acasi secondary" id="desktop-reset-btn">
                                            <i class="fas fa-redo-alt"></i>Réinitialiser
                                        </button>
                                    </div>
                                </div>
                                </div><!-- /#advanced-filters -->
                    </form>
                </div><!-- /.desktop-filters -->
            </div>
        </div>

        <!-- ========================================
             MOBILE FILTER DRAWER (Standards 2025)
             ======================================== -->

        <!-- Bouton FAB flottant (visible sur mobile uniquement) -->
        <button type="button" class="mobile-filter-fab {{ $eimDesk }}" id="mobile-filter-fab">
            <i class="fas fa-filter"></i>
        </button>

        <!-- Drawer overlay -->
        <div class="filter-drawer-overlay {{ $eimDesk }}" id="filter-drawer-overlay"></div>

        <!-- Drawer panel -->
        <div class="filter-drawer {{ $eimDesk }}" id="filter-drawer">
            <!-- Header -->
            <div class="filter-drawer-header">
                <h3>
                    <i class="fas fa-filter me-2"></i>
                    Filtres de recherche
                </h3>
                <button type="button" class="filter-drawer-close" id="filter-drawer-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body scrollable avec tous les filtres -->
            <div class="filter-drawer-body">
                <form method="GET" action="{{ route('esbtp.etudiants.index') }}" id="mobile-search-form" x-data="{ etSysteme: '{{ $etSysteme }}' }">
                    <!-- Recherche -->
                    <div class="form-group">
                        <label for="mobile-search" class="form-label">Recherche</label>
                        <input type="text" class="form-control" id="mobile-search" name="search" value="{{ $search ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                    </div>

                    <!-- Système BTS/LMD -->
                    <div class="form-group">
                        <label for="mobile-systeme" class="form-label">Système</label>
                        <select class="form-select" id="mobile-systeme" name="systeme" x-model="etSysteme">
                            <option value="" @selected(!request('systeme'))>Tous</option>
                            <option value="BTS" @selected(request('systeme') === 'BTS')>BTS</option>
                            <option value="LMD" @selected(request('systeme') === 'LMD')>LMD</option>
                        </select>
                    </div>

                    <!-- Filière BTS (mode BTS / Tous) -->
                    <div class="form-group" x-show="etSysteme !== 'LMD'" x-cloak>
                        <label for="mobile-filiere" class="form-label">Filière BTS</label>
                        <select class="form-select" id="mobile-filiere" name="filiere">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $f)
                                <option value="{{ $f->id }}" {{ isset($filiere) && $filiere == $f->id ? 'selected' : '' }}>
                                    {{ $f->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mention LMD + Parcours (mode LMD) -->
                    <div class="form-group" x-show="etSysteme === 'LMD'" x-cloak>
                        <label class="form-label">Mention LMD</label>
                        <x-au-mention-picker
                            name="mention"
                            :value="request('mention')"
                            :mentions="$mentions"
                            placeholder="Toutes les mentions"
                        />
                    </div>
                    <div class="form-group" x-show="etSysteme === 'LMD'" x-cloak>
                        <label class="form-label">@rang('parcours')</label>
                        <x-au-parcours-picker
                            name="parcours"
                            :value="request('parcours')"
                            :parcours="$parcoursList"
                            :mention-filter="request('mention')"
                        />
                    </div>

                    <!-- Niveau d'études -->
                    <div class="form-group">
                        <label for="mobile-niveau" class="form-label">Niveau d'études</label>
                        <select class="form-select" id="mobile-niveau" name="niveau">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $n)
                                <option value="{{ $n->id }}" {{ isset($niveau) && $niveau == $n->id ? 'selected' : '' }}>
                                    {{ $n->name }} ({{ $n->type }} - Année {{ $n->year }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Classe avec Alpine.js searchable-select (COPIE EXACTE) -->
                    <div class="form-group">
                        <label for="mobile-classe" class="form-label">Classe</label>
                        <div x-data="searchableSelect({
                            options: [
                                { value: '', label: 'Toutes les classes' },
                                @foreach($classes as $classeOption)
                                {
                                    value: '{{ $classeOption->id }}',
                                    label: '{{ $classeOption->name }} @if($classeOption->filiere || $classeOption->niveauEtude)({{ $classeOption->filiere->name ?? "Filière N/A" }} - {{ $classeOption->niveauEtude->name ?? "Niveau N/A" }})@endif'
                                },
                                @endforeach
                            ],
                            selected: '{{ $classe ?? '' }}',
                            name: 'classe',
                            placeholder: 'Rechercher une classe...'
                        })" class="searchable-select" :class="{ 'active': open }" @click.away="open = false">
                            <input type="hidden" name="classe" :value="selectedValue" id="mobile-classe">
                            <button type="button" class="searchable-select-trigger" @click="open = !open">
                                <span class="searchable-select-trigger-text" :class="{ 'est-indicatif': !selectedLabel }">
                                    <span x-text="selectedLabel || placeholder">Rechercher une classe...</span>
                                </span>
                                <i class="fas fa-chevron-down searchable-select-icon"></i>
                            </button>
                            <div x-show="open" class="searchable-select-dropdown" x-cloak>
                                <div class="searchable-select-search">
                                    <input type="text" x-model="search" @input="filterOptions" placeholder="Tapez pour rechercher..." @click.stop x-ref="searchInput">
                                </div>
                                <div class="searchable-select-options">
                                    <template x-if="filteredOptions.length === 0">
                                        <div class="searchable-select-no-results">
                                            <i class="fas fa-search mb-2"></i>
                                            <div>Aucune classe trouvée</div>
                                        </div>
                                    </template>
                                    <template x-for="option in filteredOptions" :key="option.value">
                                        <div class="searchable-select-option" :class="{ 'selected': option.value === selectedValue }" @click="selectOption(option)">
                                            <span x-text="option.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Année universitaire -->
                    <div class="form-group">
                        <label for="mobile-annee" class="form-label">Année universitaire</label>
                        <select class="form-select" id="mobile-annee" name="annee">
                            <option value="">Toutes les années</option>
                            @foreach($annees as $a)
                                <option value="{{ $a->id }}" {{ isset($annee) && $annee == $a->id ? 'selected' : '' }}>
                                    {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Statut -->
                    <div class="form-group">
                        <label for="mobile-status" class="form-label">Statut</label>
                        <select class="form-select" id="mobile-status" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="actif" {{ isset($status) && $status == 'actif' ? 'selected' : '' }}>Actif</option>
                            <option value="inactif" {{ isset($status) && $status == 'inactif' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>

                    <!-- Genre -->
                    <div class="form-group">
                        <label for="mobile-sexe" class="form-label">Genre</label>
                        <select class="form-select" id="mobile-sexe" name="sexe">
                            <option value="">Tous les genres</option>
                            <option value="M" {{ ($sexe ?? null) === 'M' ? 'selected' : '' }}>Masculin</option>
                            <option value="F" {{ ($sexe ?? null) === 'F' ? 'selected' : '' }}>Féminin</option>
                        </select>
                    </div>

                    <!-- Statut d'affectation -->
                    <div class="form-group">
                        <label for="mobile-affectation_status" class="form-label">Statut d'affectation ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                        <select class="form-select" id="mobile-affectation_status" name="affectation_status">
                            <option value="">Tous les statuts d'affectation</option>
                            <option value="affecté" {{ isset($affectationStatus) && $affectationStatus == 'affecté' ? 'selected' : '' }}>Affecté</option>
                            <option value="réaffecté" {{ isset($affectationStatus) && $affectationStatus == 'réaffecté' ? 'selected' : '' }}>Réaffecté</option>
                            <option value="non_affecté" {{ isset($affectationStatus) && $affectationStatus == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                        </select>
                    </div>

                    <!-- Inscription validée -->
                    <div class="form-group">
                        <label for="mobile-inscrit_annee_courante" class="form-label">Inscription validée ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                        <select class="form-select" id="mobile-inscrit_annee_courante" name="inscrit_annee_courante">
                            <option value="">Tous</option>
                            <option value="validee" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'validee' ? 'selected' : '' }}>Oui (Validée)</option>
                            <option value="en_attente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="absente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'absente' ? 'selected' : '' }}>Absente</option>
                        </select>
                    </div>

                    <!-- Transfert -->
                    <div class="form-group">
                        <label for="mobile-est_transfert" class="form-label">Transfert</label>
                        <select class="form-select" id="mobile-est_transfert" name="est_transfert">
                            <option value="">Tous</option>
                            <option value="1" {{ isset($estTransfert) && $estTransfert == '1' ? 'selected' : '' }}>Oui (Transferts)</option>
                            <option value="0" {{ isset($estTransfert) && $estTransfert == '0' ? 'selected' : '' }}>Non (Locaux)</option>
                        </select>
                    </div>

                    @can('students.accessibility.view')
                    <!-- Accessibilité -->
                    <div class="form-group">
                        <label for="mobile-accessibility" class="form-label">
                            <i class="fas fa-universal-access text-primary me-1"></i>Accessibilité
                        </label>
                        <select class="form-select" id="mobile-accessibility" name="accessibility">
                            @include('esbtp.etudiants.partials.accessibility-filter-options', ['current' => $accessibility ?? null])
                        </select>
                    </div>
                    @endcan
                </form>
            </div>

            <!-- Footer sticky avec boutons -->
            <div class="filter-drawer-footer">
                <button type="button" class="btn btn-secondary" id="filter-drawer-reset">
                    <i class="fas fa-redo-alt"></i>
                    Réinitialiser
                </button>
                <button type="submit" form="mobile-search-form" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Filtrer
                </button>
            </div>
        </div>

        <!-- Tableau des étudiants -->
        <div class="card-moderne {{ $eimDesk }}">
            <div class="p-lg">
                <div class="section-title mb-md" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                    <span><i class="fas fa-list me-2"></i>Liste des étudiants</span>
                    <span id="student-count-badge" style="font-size:0.85rem; font-weight:600; color:#0453cb; background:rgba(4,83,203,0.08); padding:0.35rem 1rem; border-radius:20px;"></span>
                </div>

                <!-- Indicateur filtres actifs -->
                <div id="active-filters-container" class="active-filters-container" style="display: none;">
                    <!-- Sera rempli dynamiquement via JavaScript -->
                </div>

                <div id="etudiants-results">
                    @include('esbtp.etudiants.partials.results', ['etudiants' => $etudiants])
</div>
</div>
</div>
</div>
</div>

<div class="modal fade eqe-modal" id="etudiantEditModal" tabindex="-1" aria-labelledby="etudiantEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header eqe-header">
                <div class="eqe-header-icon"><i class="fas fa-user-pen"></i></div>
                <div class="eqe-header-text">
                    <p class="eqe-header-eyebrow">Édition rapide</p>
                    <h5 class="eqe-header-title" id="etudiantEditModalLabel">Modifier l'étudiant</h5>
                </div>
                <button type="button" class="eqe-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body eqe-body">
                <div class="eqe-tabs" role="tablist">
                    <button type="button" class="eqe-tab active" id="tab-etudiant-link" data-bs-toggle="tab" data-bs-target="#tab-etudiant" role="tab">
                        <i class="fas fa-user-edit"></i>Étudiant
                    </button>
                    <button type="button" class="eqe-tab" id="tab-inscriptions-link" data-bs-toggle="tab" data-bs-target="#tab-inscriptions" role="tab">
                        <i class="fas fa-graduation-cap"></i>Inscriptions
                    </button>
                </div>
                <div class="tab-content eqe-tab-content" id="editStudentTabContent">
                    <div class="tab-pane fade show active eqe-pane active" id="tab-etudiant" role="tabpanel">
                        <div class="eqe-iframe-wrap">
                            <div id="student-edit-loader" class="eqe-iframe-loader">
                                <div class="spinner-border" role="status" style="width:2rem;height:2rem;"></div>
                                <span>Chargement du formulaire...</span>
                            </div>
                            <iframe id="student-edit-frame" src="about:blank" title="Édition étudiant" loading="eager"></iframe>
                        </div>
                    </div>
                    <div class="tab-pane fade eqe-pane" id="tab-inscriptions" role="tabpanel">
                        <div id="inscriptions-accordion-container" class="eqe-inscriptions">
                            <p style="color:#94a3b8; font-size:.82rem; margin:0;">Sélectionnez un étudiant pour afficher ses inscriptions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Étudiants éligibles chargés à l'ouverture du modal, pas à chaque affichage de la liste. --}}
<x-reinscription-bulk-modal :students-url="route('esbtp.etudiants.reinscription-eligibles')" modal-id="bulkReinscriptionModal" />

@if($eimShell)
    @include('esbtp.etudiants.partials._index-mobile', ['listeMobile' => $listeMobile ?? null])
@endif

@if(request()->boolean('open_bulk'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('bulkReinscriptionModal');
        if (modalEl && window.bootstrap) {
            new bootstrap.Modal(modalEl).show();
        }
    });
</script>
@endif
@endsection

@push('scripts')
@php
    // Donnees du script de la page (public/js/etudiants-index.js). Les libelles
    // des classes restent echappes comme avant : l'indicateur de filtres les
    // insere en innerHTML.
    $etuIndexConfig = [
        'indexUrl' => route('esbtp.etudiants.index'),
        'exportExcelUrl' => route('esbtp.etudiants.export.excel'),
        'exportPdfUrl' => route('esbtp.etudiants.export.pdf'),
        'classesMapping' => (object) $classes->mapWithKeys(fn ($c) => [
            (string) $c->id => e($c->name) . (($c->filiere || $c->niveauEtude)
                ? ' (' . e($c->filiere->name ?? 'Filière N/A') . ' - ' . e($c->niveauEtude->name ?? 'Niveau N/A') . ')'
                : ''),
        ])->all(),
        'exportFilieres' => $filieres->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'code' => $f->code])->values(),
        'exportNiveaux' => $niveaux->map(fn ($n) => ['id' => $n->id, 'name' => $n->name, 'code' => $n->code])->values(),
        'exportClasses' => $classes->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'filiere_id' => $c->filiere_id, 'niveau_etude_id' => $c->niveau_etude_id])->values(),
    ];
@endphp
<script>window.etuIndexConfig = @json($etuIndexConfig);</script>
<script src="{{ asset('js/etudiants-index.js') }}?v={{ @filemtime(public_path('js/etudiants-index.js')) ?: '1' }}"></script>
@endpush
