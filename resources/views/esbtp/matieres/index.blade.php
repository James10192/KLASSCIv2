@extends('layouts.app')

@section('title', 'Liste des matières')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
<link rel="stylesheet" href="{{ asset('css/matieres-index.css') }}">
@endsection


@php
    $advancedFiltersActive = !empty($filters['coefficient_min']) || !empty($filters['coefficient_max'])
        || !empty($filters['heures_min']) || !empty($filters['heures_max']);
    $advancedFiltersCount = collect([
        $filters['coefficient_min'] ?? null,
        $filters['coefficient_max'] ?? null,
        $filters['heures_min'] ?? null,
        $filters['heures_max'] ?? null,
    ])->filter(fn ($v) => $v !== null && $v !== '')->count();
@endphp

@section('content')
<div class="main-content"
     x-data="{ advancedFilters: {{ $advancedFiltersActive ? 'true' : 'false' }} }">

    {{-- ─── HERO ─── --}}
    <section class="mi-hero" aria-label="En-tête matières">
        <div class="mi-hero-top">
            <div class="mi-hero-left">
                <div class="mi-hero-icon" aria-hidden="true">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <h1>Gestion des matières</h1>
                    <p>Catalogue, coefficients et liaisons filière × niveau</p>
                </div>
            </div>
            <div class="mi-hero-actions">
                <a href="{{ route('esbtp.matieres.create') }}" class="mi-btn--white">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Nouvelle matière
                </a>
            </div>
        </div>

        <div class="mi-kpis" role="group" aria-label="Indicateurs clés">
            <div class="mi-kpi" id="mi-kpi-total">
                <div class="mi-kpi-icon"><i class="fas fa-book" aria-hidden="true"></i></div>
                <div class="mi-kpi-text">
                    <div class="mi-kpi-value" data-kpi="total">{{ $kpis['total'] ?? 0 }}</div>
                    <div class="mi-kpi-label">Total matières</div>
                </div>
            </div>
            <div class="mi-kpi" id="mi-kpi-actifs">
                <div class="mi-kpi-icon"><i class="fas fa-check-circle" aria-hidden="true"></i></div>
                <div class="mi-kpi-text">
                    <div class="mi-kpi-value" data-kpi="actifs">{{ $kpis['actifs'] ?? 0 }}</div>
                    <div class="mi-kpi-label">Actives</div>
                </div>
            </div>
            <div class="mi-kpi" id="mi-kpi-liaisons">
                <div class="mi-kpi-icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <div class="mi-kpi-text">
                    <div class="mi-kpi-value" data-kpi="avec_liaisons">{{ $kpis['avec_liaisons'] ?? 0 }}</div>
                    <div class="mi-kpi-label">Avec liaisons</div>
                </div>
            </div>
            <div class="mi-kpi" id="mi-kpi-heures">
                <div class="mi-kpi-icon"><i class="fas fa-clock" aria-hidden="true"></i></div>
                <div class="mi-kpi-text">
                    <div class="mi-kpi-value"><span data-kpi="heures_totales">{{ $kpis['heures_totales'] ?? 0 }}</span>h</div>
                    <div class="mi-kpi-label">Volume horaire</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── Toast session success ─── --}}
    @if(session('success'))
        <div class="alert alert-success" role="alert" style="border-radius: 12px; margin-bottom: 1.25rem;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- ─── FILTRES — hybrid (3 visibles + collapsible advanced) ─── --}}
    <form id="matieres-filter-form"
          method="GET"
          action="{{ route('esbtp.matieres.index') }}"
          class="mi-filters">

        <div class="mi-filters-bar">
            <div class="mi-filter-group mi-filter-group--search">
                <label for="filter-search" class="mi-filter-label">Recherche</label>
                <input type="search"
                       id="filter-search"
                       name="search"
                       class="mi-filter-input mi-filter-input--search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Nom, code ou description..."
                       autocomplete="off">
            </div>
            <div class="mi-filter-group">
                <label for="filter-filiere" class="mi-filter-label">Filière</label>
                <select name="filiere_filter" id="filter-filiere" class="mi-filter-select">
                    <option value="">Toutes les filières</option>
                    @foreach($filieres as $filiere)
                        <option value="{{ $filiere->id }}" @selected($filters['filiere_filter'] == $filiere->id)>{{ $filiere->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mi-filter-group">
                <label for="filter-niveau" class="mi-filter-label">Niveau</label>
                <select name="niveau_filter" id="filter-niveau" class="mi-filter-select">
                    <option value="">Tous les niveaux</option>
                    @foreach($niveaux as $niveau)
                        <option value="{{ $niveau->id }}" @selected($filters['niveau_filter'] == $niveau->id)>{{ $niveau->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mi-filter-group">
                <label for="filter-statut" class="mi-filter-label">Statut</label>
                <select name="statut_filter" id="filter-statut" class="mi-filter-select">
                    <option value="">Tous</option>
                    <option value="1" @selected($filters['statut_filter'] === '1')>Actif</option>
                    <option value="0" @selected($filters['statut_filter'] === '0')>Inactif</option>
                </select>
            </div>

            <div class="mi-filters-actions">
                <button type="button"
                        class="mi-advanced-btn"
                        :class="{ 'is-open': advancedFilters }"
                        @click="advancedFilters = !advancedFilters"
                        :aria-expanded="advancedFilters.toString()"
                        aria-controls="mi-advanced-panel">
                    <i class="fas fa-sliders-h" aria-hidden="true"></i>
                    Avancés
                    @if($advancedFiltersCount > 0)
                        <span class="mi-advanced-count">{{ $advancedFiltersCount }}</span>
                    @endif
                    <i class="fas fa-chevron-down" aria-hidden="true"
                       :style="advancedFilters ? 'transform: rotate(180deg)' : ''"></i>
                </button>
                <button type="button" id="matieres-clear-filters" class="mi-btn-clear" title="Effacer tous les filtres">
                    <i class="fas fa-eraser" aria-hidden="true"></i>
                    Effacer
                </button>
            </div>
        </div>

        <div class="mi-advanced-panel"
             id="mi-advanced-panel"
             x-show="advancedFilters"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="mi-filter-group">
                <label for="filter-coefficient-min" class="mi-filter-label">Coefficient min.</label>
                <input type="number"
                       id="filter-coefficient-min"
                       name="coefficient_min"
                       class="mi-filter-input"
                       min="0"
                       step="0.1"
                       value="{{ $filters['coefficient_min'] }}">
            </div>
            <div class="mi-filter-group">
                <label for="filter-coefficient-max" class="mi-filter-label">Coefficient max.</label>
                <input type="number"
                       id="filter-coefficient-max"
                       name="coefficient_max"
                       class="mi-filter-input"
                       min="0"
                       step="0.1"
                       value="{{ $filters['coefficient_max'] }}">
            </div>
            <div class="mi-filter-group">
                <label for="filter-heures-min" class="mi-filter-label">Heures min.</label>
                <input type="number"
                       id="filter-heures-min"
                       name="heures_min"
                       class="mi-filter-input"
                       min="0"
                       value="{{ $filters['heures_min'] }}">
            </div>
            <div class="mi-filter-group">
                <label for="filter-heures-max" class="mi-filter-label">Heures max.</label>
                <input type="number"
                       id="filter-heures-max"
                       name="heures_max"
                       class="mi-filter-input"
                       min="0"
                       value="{{ $filters['heures_max'] }}">
            </div>
        </div>

        <div class="mi-filters-foot">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span id="results-count">
                @if(($summary['total'] ?? 0) > 0)
                    {{ $summary['from'] ?? 0 }} - {{ $summary['to'] ?? 0 }} sur {{ $summary['total'] }} matière(s)
                @else
                    Aucun résultat pour le moment.
                @endif
            </span>
        </div>
    </form>

    {{-- ─── BULK BAR : sticky-top, AVANT le wrapper AJAX pour ne pas être      --}}
    {{-- réinjectée à chaque refresh. position:sticky+top:0 colle au viewport.  --}}
    <div id="matieres-bulk-bar"
         class="matieres-bulk-bar mi-bulk-bar"
         role="region"
         aria-label="Actions groupées sur les matières sélectionnées"
         aria-live="polite"
         style="display: none;">
        <div class="mi-bulk-info">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span role="status" aria-atomic="true">
                <strong id="matieres-selected-count">0</strong>
                matière(s) sélectionnée(s)
            </span>
        </div>
        <div class="mi-bulk-actions">
            <button type="button" class="mi-bulk-btn" id="matieres-bulk-attach">
                <i class="fas fa-link" aria-hidden="true"></i>
                Attacher aux combinaisons
            </button>
            <button type="button" class="mi-bulk-btn" id="matieres-bulk-configure">
                <i class="fas fa-sliders-h" aria-hidden="true"></i>
                Configurer
            </button>
            <button type="button" class="mi-bulk-btn mi-bulk-btn--danger" id="matieres-bulk-delete">
                <i class="fas fa-trash" aria-hidden="true"></i>
                Supprimer
            </button>
            <button type="button" class="mi-bulk-btn mi-bulk-btn--ghost" id="matieres-bulk-clear">
                <i class="fas fa-times" aria-hidden="true"></i>
                Annuler
            </button>
        </div>
    </div>

    {{-- ─── RESULTS WRAPPER (#matieres-results contract preserved) ─── --}}
    {{-- L'AJAX innerHTML cible ce conteneur ; il ne contient que le partial.  --}}
    <div id="matieres-results"
         class="mi-results-card"
         data-summary='@json($summary)'
         data-refresh-url="{{ route('esbtp.matieres.refresh') }}">
        @include('esbtp.matieres.partials.results', ['matieres' => $matieres])
    </div>
</div>

<div class="modal fade" id="configureModal" tabindex="-1" aria-labelledby="configureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(4, 83, 203, 0.18);">
            {{-- Header avec gradient bleu KLASSCI — texte blanc contraste WCAG AA --}}
            <div class="modal-header" style="background: linear-gradient(135deg, #0453cb 0%, #1a6ee8 100%); border-radius: 12px 12px 0 0; padding: 1.5rem 2rem;">
                <div class="d-flex align-items-start gap-3 flex-grow-1">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width: 44px; height: 44px; background: rgba(255,255,255,0.15);">
                        <i class="fas fa-link" style="color: #ffffff; font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title mb-1" id="configureModalLabel" style="font-weight: 700; color: #ffffff; font-size: 1.15rem;">
                            Configuration des liaisons
                        </h4>
                        <p class="mb-0" style="font-size: 0.875rem; color: rgba(255,255,255,0.85);">
                            Matière :&nbsp;<span id="modal-matiere-name" style="font-weight: 600; color: #ffffff;"></span>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal"
                        aria-label="Fermer" style="opacity: 0.85;"></button>
            </div>
            <div class="modal-body" style="padding: 1.75rem 2rem; background: #f8fafc;">
                <form id="configureLiaisonsForm">
                    @csrf
                    <input type="hidden" id="modal-matiere-id" name="matiere_id">
                    
                    {{-- ═══════════════════════════════════════════════════
                         SECTION FILIÈRES & NIVEAUX — Design Selectable Cards
                         ═══════════════════════════════════════════════════ --}}
                    <style>
                        /* ── Variables locales de la section ── */
                        .fn-section {
                            --fn-primary: #0453cb;
                            --fn-primary-light: #e8f0fd;
                            --fn-primary-mid: #c2d5fa;
                            --fn-success: #059669;
                            --fn-success-light: #d1fae5;
                            --fn-text-dark: #1e293b;
                            --fn-text-muted: #64748b;
                            --fn-border: #e2e8f0;
                            --fn-bg-card: #ffffff;
                            --fn-bg-section: #f1f5f9;
                            --fn-radius-card: 14px;
                            --fn-radius-pill: 22px;
                            --fn-shadow-card: 0 2px 8px rgba(4, 83, 203, 0.08), 0 0 0 1px rgba(4, 83, 203, 0.06);
                            --fn-shadow-card-active: 0 4px 20px rgba(4, 83, 203, 0.18), 0 0 0 2px rgba(4, 83, 203, 0.22);
                            --fn-transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
                        }

                        /* ── Wrapper principal ── */
                        .fn-section {
                            background: var(--fn-bg-section);
                            border-radius: var(--fn-radius-card);
                            padding: 1.5rem;
                            border: 1px solid var(--fn-border);
                        }

                        /* ── Header de section ── */
                        .fn-section-header {
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                            margin-bottom: 1.25rem;
                            padding-bottom: 1rem;
                            border-bottom: 1px solid var(--fn-border);
                        }
                        .fn-section-icon {
                            width: 38px;
                            height: 38px;
                            background: linear-gradient(135deg, #0453cb 0%, #1a6ee8 100%);
                            border-radius: 10px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            flex-shrink: 0;
                            box-shadow: 0 3px 10px rgba(4, 83, 203, 0.28);
                        }
                        .fn-section-icon i { color: #fff; font-size: 0.9rem; }
                        .fn-section-title {
                            font-size: 0.95rem;
                            font-weight: 700;
                            color: var(--fn-text-dark);
                            margin: 0;
                        }
                        .fn-section-subtitle {
                            font-size: 0.78rem;
                            color: var(--fn-text-muted);
                            margin: 0;
                        }
                        .fn-counter {
                            margin-left: auto;
                            background: var(--fn-primary-light);
                            color: var(--fn-primary);
                            font-size: 0.72rem;
                            font-weight: 700;
                            padding: 0.2rem 0.65rem;
                            border-radius: 20px;
                            border: 1px solid var(--fn-primary-mid);
                            white-space: nowrap;
                            transition: var(--fn-transition);
                        }

                        /* ── Grille des cartes filières ── */
                        .fn-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                            gap: 1rem;
                        }

                        /* ── Carte filière ── */
                        .fn-filiere-card {
                            background: var(--fn-bg-card);
                            border-radius: var(--fn-radius-card);
                            box-shadow: var(--fn-shadow-card);
                            overflow: hidden;
                            transition: var(--fn-transition);
                            position: relative;
                        }
                        .fn-filiere-card:hover {
                            box-shadow: 0 6px 24px rgba(4, 83, 203, 0.14), 0 0 0 1.5px rgba(4, 83, 203, 0.14);
                            transform: translateY(-1px);
                        }
                        .fn-filiere-card.has-selection {
                            box-shadow: var(--fn-shadow-card-active);
                        }

                        /* ── Header de carte filière ── */
                        .fn-filiere-header {
                            padding: 0.85rem 1rem;
                            background: linear-gradient(135deg, #f8faff 0%, #eef3ff 100%);
                            border-bottom: 1px solid var(--fn-primary-mid);
                            display: flex;
                            align-items: center;
                            gap: 0.6rem;
                        }
                        .fn-filiere-dot {
                            width: 8px;
                            height: 8px;
                            border-radius: 50%;
                            background: var(--fn-primary);
                            flex-shrink: 0;
                            transition: var(--fn-transition);
                        }
                        .fn-filiere-card.has-selection .fn-filiere-dot {
                            background: var(--fn-success);
                            box-shadow: 0 0 0 3px var(--fn-success-light);
                        }
                        .fn-filiere-name {
                            font-size: 0.82rem;
                            font-weight: 700;
                            color: var(--fn-text-dark);
                            flex: 1;
                            min-width: 0;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .fn-filiere-code {
                            font-size: 0.67rem;
                            font-weight: 700;
                            color: var(--fn-primary);
                            background: var(--fn-primary-light);
                            border: 1px solid var(--fn-primary-mid);
                            border-radius: 6px;
                            padding: 0.15rem 0.45rem;
                            letter-spacing: 0.03em;
                            flex-shrink: 0;
                        }
                        .fn-filiere-sel-badge {
                            font-size: 0.65rem;
                            font-weight: 600;
                            color: var(--fn-success);
                            background: var(--fn-success-light);
                            border-radius: 10px;
                            padding: 0.15rem 0.4rem;
                            display: none;
                            flex-shrink: 0;
                        }
                        .fn-filiere-card.has-selection .fn-filiere-sel-badge { display: inline; }

                        /* ── Action "Tout sélectionner" par filière ── */
                        .fn-filiere-actions {
                            padding: 0.5rem 1rem 0;
                            display: flex;
                            justify-content: flex-end;
                        }
                        .fn-select-all-btn {
                            font-size: 0.7rem;
                            color: var(--fn-text-muted);
                            cursor: pointer;
                            background: none;
                            border: none;
                            padding: 0.15rem 0.4rem;
                            border-radius: 6px;
                            transition: var(--fn-transition);
                            font-weight: 500;
                            display: flex;
                            align-items: center;
                            gap: 0.3rem;
                        }
                        .fn-select-all-btn:hover {
                            color: var(--fn-primary);
                            background: var(--fn-primary-light);
                        }
                        .fn-select-all-btn.all-selected {
                            color: var(--fn-success);
                        }

                        /* ── Zone des pills de niveaux ── */
                        .fn-niveaux-body {
                            padding: 0.75rem 1rem 1rem;
                            display: flex;
                            flex-wrap: wrap;
                            gap: 0.5rem;
                        }

                        /* ── Checkbox caché — la PILL est le vrai contrôle ── */
                        .fn-niveau-checkbox {
                            position: absolute;
                            opacity: 0;
                            width: 0;
                            height: 0;
                            pointer-events: none;
                        }

                        /* ── Pill de niveau (label cliquable) ── */
                        .fn-niveau-pill {
                            display: inline-flex;
                            align-items: center;
                            gap: 0.35rem;
                            padding: 0.35rem 0.75rem;
                            border-radius: var(--fn-radius-pill);
                            border: 1.5px solid var(--fn-border);
                            background: #f8fafc;
                            color: var(--fn-text-muted);
                            font-size: 0.775rem;
                            font-weight: 600;
                            cursor: pointer;
                            user-select: none;
                            transition: var(--fn-transition);
                            position: relative;
                            white-space: nowrap;
                        }
                        .fn-niveau-pill .fn-pill-check {
                            width: 14px;
                            height: 14px;
                            border-radius: 50%;
                            border: 1.5px solid currentColor;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            flex-shrink: 0;
                            transition: var(--fn-transition);
                            font-size: 0.6rem;
                        }
                        .fn-niveau-pill .fn-pill-check i {
                            opacity: 0;
                            transform: scale(0.4);
                            transition: var(--fn-transition);
                        }
                        .fn-niveau-pill .fn-pill-code {
                            font-size: 0.64rem;
                            opacity: 0.65;
                            font-weight: 500;
                        }

                        /* ── Hover state ── */
                        .fn-niveau-pill:hover {
                            border-color: var(--fn-primary);
                            color: var(--fn-primary);
                            background: var(--fn-primary-light);
                            transform: translateY(-1px);
                            box-shadow: 0 2px 8px rgba(4, 83, 203, 0.12);
                        }

                        /* ── Checked state (via JS, classe .active) ── */
                        .fn-niveau-pill.active {
                            background: linear-gradient(135deg, #0453cb 0%, #1a6ee8 100%);
                            border-color: #0453cb;
                            color: #ffffff;
                            box-shadow: 0 3px 12px rgba(4, 83, 203, 0.32);
                            transform: translateY(-1px);
                        }
                        .fn-niveau-pill.active .fn-pill-check {
                            border-color: rgba(255,255,255,0.7);
                            background: rgba(255,255,255,0.25);
                        }
                        .fn-niveau-pill.active .fn-pill-check i {
                            opacity: 1;
                            transform: scale(1);
                            color: #ffffff;
                        }
                        .fn-niveau-pill.active:hover {
                            background: linear-gradient(135deg, #0342a8 0%, #1058cc 100%);
                            box-shadow: 0 4px 16px rgba(4, 83, 203, 0.4);
                            color: #ffffff;
                        }
                        .fn-niveau-pill.active .fn-pill-code {
                            opacity: 0.8;
                        }

                        /* ── État vide (aucun niveau) ── */
                        .fn-empty-niveaux {
                            width: 100%;
                            text-align: center;
                            padding: 1rem 0.5rem;
                            color: var(--fn-text-muted);
                            font-size: 0.78rem;
                        }

                        /* ── Responsive ── */
                        @media (max-width: 600px) {
                            .fn-grid { grid-template-columns: 1fr; }
                        }

                        /* ── Animation d'entrée des cartes ── */
                        .fn-filiere-card {
                            animation: fn-fadeIn 0.3s ease both;
                        }
                        @keyframes fn-fadeIn {
                            from { opacity: 0; transform: translateY(8px); }
                            to   { opacity: 1; transform: translateY(0); }
                        }
                        .fn-filiere-card:nth-child(1) { animation-delay: 0.04s; }
                        .fn-filiere-card:nth-child(2) { animation-delay: 0.08s; }
                        .fn-filiere-card:nth-child(3) { animation-delay: 0.12s; }
                        .fn-filiere-card:nth-child(4) { animation-delay: 0.16s; }
                        .fn-filiere-card:nth-child(5) { animation-delay: 0.20s; }
                        .fn-filiere-card:nth-child(6) { animation-delay: 0.24s; }
                    </style>

                    <div class="fn-section">
                        {{-- Header de section --}}
                        <div class="fn-section-header">
                            <div class="fn-section-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div>
                                <p class="fn-section-title">Filières &amp; Niveaux</p>
                                <p class="fn-section-subtitle">Cliquez sur un niveau pour activer la liaison</p>
                            </div>
                            <span class="fn-counter" id="fn-global-counter">0 sélection</span>
                        </div>

                        {{-- Grille des cartes filières --}}
                        <div class="fn-grid" id="filieres-niveaux-list">
                            @foreach($filieres as $filiere)
                            <div class="fn-filiere-card" data-filiere-id="{{ $filiere->id }}" id="fn-card-{{ $filiere->id }}">

                                {{-- Header filière --}}
                                <div class="fn-filiere-header">
                                    <span class="fn-filiere-dot"></span>
                                    <span class="fn-filiere-name" title="{{ $filiere->name }}">{{ $filiere->name }}</span>
                                    @if($filiere->code)
                                        <span class="fn-filiere-code">{{ $filiere->code }}</span>
                                    @endif
                                    <span class="fn-filiere-sel-badge" id="fn-badge-{{ $filiere->id }}">✓</span>
                                </div>

                                {{-- Action tout sélectionner --}}
                                <div class="fn-filiere-actions">
                                    <button type="button"
                                            class="fn-select-all-btn"
                                            id="fn-selectall-{{ $filiere->id }}"
                                            onclick="fnToggleAllNiveaux({{ $filiere->id }}, this)">
                                        <i class="fas fa-check-double"></i>
                                        <span>Tout sélectionner</span>
                                    </button>
                                </div>

                                {{-- Pills de niveaux --}}
                                <div class="fn-niveaux-body">
                                    @forelse($niveaux as $niveau)
                                    <span class="fn-niveau-pill"
                                          id="fn-pill-{{ $filiere->id }}-{{ $niveau->id }}"
                                          onclick="fnToggleNiveau(this, {{ $filiere->id }}, {{ $niveau->id }}, '{{ addslashes($filiere->name) }}', '{{ addslashes($niveau->name) }}')"
                                          title="{{ $niveau->name }}">
                                        <span class="fn-pill-check">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        {{ $niveau->name }}
                                        @if($niveau->code)
                                            <span class="fn-pill-code">{{ $niveau->code }}</span>
                                        @endif
                                        {{-- Checkbox caché (pour compatibilité JS existant) --}}
                                        <input class="fn-niveau-checkbox niveau-filiere-checkbox"
                                               type="checkbox"
                                               id="liaison-{{ $filiere->id }}-{{ $niveau->id }}"
                                               data-filiere-id="{{ $filiere->id }}"
                                               data-filiere-label="{{ $filiere->name }}"
                                               data-niveau-id="{{ $niveau->id }}"
                                               data-niveau-label="{{ $niveau->name }}">
                                    </span>
                                    @empty
                                    <div class="fn-empty-niveaux">
                                        <i class="fas fa-inbox me-1"></i>Aucun niveau disponible
                                    </div>
                                    @endforelse
                                </div>

                            </div>
                            @endforeach
                        </div>
                    </div>

                    <script>
                    /**
                     * FN = Filières & Niveaux
                     * Gestion du design "selectable pills" avec mise à jour du counter et compatibilité
                     * avec le système existant (niveau-filiere-checkbox + updateCombinationsPreview).
                     */

                    /** Toggle un niveau (pill) */
                    function fnToggleNiveau(pillEl, filiereId, niveauId, filiereLabel, niveauLabel) {
                        const checkbox = pillEl.querySelector('.niveau-filiere-checkbox');
                        if (!checkbox) return;

                        const isActive = pillEl.classList.toggle('active');
                        checkbox.checked = isActive;

                        // Mettre à jour l'état de la carte filière
                        fnUpdateFiliereCard(filiereId);

                        // Mettre à jour le counter global
                        fnUpdateGlobalCounter();

                        // Déclencher updateCombinationsPreview du système existant
                        if (typeof updateCombinationsPreview === 'function') {
                            updateCombinationsPreview();
                        }
                    }

                    /** Toggle tous les niveaux d'une filière */
                    function fnToggleAllNiveaux(filiereId, btn) {
                        const card = document.getElementById('fn-card-' + filiereId);
                        const pills = card.querySelectorAll('.fn-niveau-pill');
                        const allActive = Array.from(pills).every(p => p.classList.contains('active'));
                        const targetState = !allActive;

                        pills.forEach(pill => {
                            const checkbox = pill.querySelector('.niveau-filiere-checkbox');
                            if (!checkbox) return;
                            pill.classList.toggle('active', targetState);
                            checkbox.checked = targetState;
                        });

                        fnUpdateFiliereCard(filiereId);
                        fnUpdateGlobalCounter();

                        if (typeof updateCombinationsPreview === 'function') {
                            updateCombinationsPreview();
                        }
                    }

                    /** Met à jour l'état visuel d'une carte filière (has-selection, badge, btn) */
                    function fnUpdateFiliereCard(filiereId) {
                        const card = document.getElementById('fn-card-' + filiereId);
                        const pills = card.querySelectorAll('.fn-niveau-pill');
                        const btn = document.getElementById('fn-selectall-' + filiereId);
                        const activeCount = card.querySelectorAll('.fn-niveau-pill.active').length;
                        const totalCount = pills.length;

                        // has-selection sur la card
                        card.classList.toggle('has-selection', activeCount > 0);

                        // Bouton "Tout sélectionner" ↔ "Tout désélectionner"
                        if (btn) {
                            const allSelected = activeCount === totalCount && totalCount > 0;
                            btn.classList.toggle('all-selected', allSelected);
                            const icon = btn.querySelector('i');
                            const span = btn.querySelector('span');
                            if (allSelected) {
                                icon.className = 'fas fa-times-circle';
                                span.textContent = 'Tout désélectionner';
                            } else {
                                icon.className = 'fas fa-check-double';
                                span.textContent = 'Tout sélectionner';
                            }
                        }
                    }

                    /** Met à jour le counter global "X sélection(s)" */
                    function fnUpdateGlobalCounter() {
                        const total = document.querySelectorAll('.fn-niveau-pill.active').length;
                        const counter = document.getElementById('fn-global-counter');
                        if (counter) {
                            counter.textContent = total + ' sélection' + (total > 1 ? 's' : '');
                            counter.style.background = total > 0 ? '#d1fae5' : '';
                            counter.style.color = total > 0 ? '#059669' : '';
                            counter.style.borderColor = total > 0 ? '#a7f3d0' : '';
                        }
                    }

                    /**
                     * Synchronisation pills ↔ checkboxes lors du chargement du modal.
                     * Appelée par le système existant (openConfigureModal) après avoir coché les checkboxes.
                     */
                    function fnSyncPillsFromCheckboxes() {
                        document.querySelectorAll('.niveau-filiere-checkbox').forEach(cb => {
                            const filiereId = cb.dataset.filiereId;
                            const niveauId = cb.dataset.niveauId;
                            const pill = document.getElementById('fn-pill-' + filiereId + '-' + niveauId);
                            if (pill) {
                                pill.classList.toggle('active', cb.checked);
                            }
                        });

                        // Mettre à jour toutes les cartes
                        document.querySelectorAll('.fn-filiere-card[data-filiere-id]').forEach(card => {
                            fnUpdateFiliereCard(card.dataset.filiereId);
                        });

                        fnUpdateGlobalCounter();
                    }

                    // Écouter l'ouverture du modal pour synchroniser
                    document.addEventListener('DOMContentLoaded', function () {
                        const modal = document.getElementById('configureModal');
                        if (modal) {
                            modal.addEventListener('shown.bs.modal', function () {
                                fnSyncPillsFromCheckboxes();
                            });
                        }
                    });
                    </script>

                    <div class="card-moderne mt-4">
                        <div class="main-card-header">
                            <h3 class="main-card-title">
                                <i class="fas fa-lightbulb"></i>Aperçu des combinaisons
                            </h3>
                            <p class="main-card-subtitle">Visualisez les liaisons configurées</p>
                        </div>
                        <div class="main-card-body" id="combinations-preview">
                            <div class="d-flex align-items-center" style="color: #0369a1;">
                                <i class="fas fa-info-circle me-2"></i>
                                <span>Cochez des niveaux dans les filières ci-dessus pour voir les liaisons.</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="padding: 1rem 2rem; background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn" id="save-liaisons-btn"
                        style="background: linear-gradient(135deg, #0453cb 0%, #1a6ee8 100%); color: #ffffff; border: none; font-weight: 600; padding: 0.5rem 1.5rem;">
                    <i class="fas fa-save me-1"></i>Enregistrer les liaisons
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- common.js : fournit window.iiConfirm() et window.showToast(). --}}
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<script>
(function () {
    const FILTER_DEBOUNCE = 350;

    const filtersForm = document.getElementById('matieres-filter-form');
    const summaryElement = document.getElementById('results-count');
    const resultsContainer = document.getElementById('matieres-results');
    const clearFiltersBtn = document.getElementById('matieres-clear-filters');
    const bulkBar = document.getElementById('matieres-bulk-bar');
    const bulkCount = document.getElementById('matieres-selected-count');
    const bulkAttachBtn = document.getElementById('matieres-bulk-attach');
    const bulkConfigureBtn = document.getElementById('matieres-bulk-configure');
    const bulkDeleteBtn = document.getElementById('matieres-bulk-delete');
    const bulkClearBtn = document.getElementById('matieres-bulk-clear');
    let filterTimer = null;
    const selectedIds = new Set();

    function getSelectedIdsArray() {
        return Array.from(selectedIds);
    }

    /* Helper AJAX : factorise X-Requested-With + Accept JSON + status check + parse JSON.
       Throw une Error si !response.ok ou si le payload n'a pas le bon shape. */
    async function ajaxJson(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    }

    /* Applique une réponse de listing (filter / pagination) au DOM. */
    function applyListingResponse(data, { pushHistory = true } = {}) {
        if (!data || !data.html) {
            throw new Error('Template manquant dans la réponse');
        }
        resultsContainer.innerHTML = data.html;
        if (pushHistory && data.url) {
            history.pushState({}, '', data.url);
        }
        updateSummary(data.summary || {});
        updateKpis(data.kpis || {});
        clearSelection();
    }

    function updateKpis(kpis) {
        if (!kpis || typeof kpis !== 'object') return;
        document.querySelectorAll('[data-kpi]').forEach((el) => {
            const key = el.dataset.kpi;
            if (kpis[key] !== undefined && kpis[key] !== null) {
                el.textContent = String(kpis[key]);
            }
        });
    }

    function updateSummary(summary) {
        if (!summaryElement || !summary) {
            return;
        }

        if (summary.total && summary.total > 0) {
            summaryElement.textContent = `${summary.from ?? 0} - ${summary.to ?? 0} sur ${summary.total} matière(s)`;
        } else {
            summaryElement.textContent = 'Aucun résultat pour le moment.';
        }
    }

    function toggleBulkBar() {
        const count = selectedIds.size;
        if (!bulkBar || !bulkCount) {
            return;
        }

        bulkCount.textContent = count;
        bulkBar.style.display = count > 0 ? 'block' : 'none';

        if (bulkConfigureBtn) {
            bulkConfigureBtn.style.display = count === 1 ? 'inline-flex' : 'none';
        }
    }

    function clearSelection() {
        const tableCheckboxes = resultsContainer
            ? resultsContainer.querySelectorAll('.matiere-checkbox')
            : [];

        selectedIds.clear();
        tableCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });
        const selectAll = document.getElementById('matieres-select-all');
        if (selectAll) {
            selectAll.checked = false;
        }
        toggleBulkBar();
    }

    function applyQueryToForm(url) {
        if (!filtersForm) {
            return;
        }

        const parsedUrl = new URL(url, window.location.origin);
        const params = parsedUrl.searchParams;

        filtersForm.querySelectorAll('[name]').forEach((field) => {
            const name = field.getAttribute('name');
            if (!name) {
                return;
            }
            const value = params.get(name) ?? '';
            if (field.tagName === 'SELECT') {
                field.value = value;
            } else if (field.type === 'number' && value !== '') {
                field.value = Number(value);
            } else {
                field.value = value;
            }
        });
    }

    function setMatiereRowLoadingState(matiereId, isLoading) {
        const row = document.querySelector(`tr[data-matiere-id="${matiereId}"]`);
        if (!row) {
            return;
        }
        row.classList.toggle('is-loading', Boolean(isLoading));
        const actionsWrapper = row.querySelector('.matiere-actions-wrapper');
        if (actionsWrapper) {
            actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
    }
    window.setMatiereRowLoadingState = setMatiereRowLoadingState;

    function triggerMatiereRowHighlight(row, actionType = 'update') {
        if (!row) {
            return;
        }

        const isReject = ['reject', 'delete', 'danger'].includes(actionType);
        row.classList.remove('matiere-row-flash', 'reject');
        void row.offsetWidth;

        const highlight = document.createElement('div');
        highlight.className = 'matiere-row-highlight';
        if (isReject) {
            highlight.classList.add('reject');
        }
        row.appendChild(highlight);

        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });

        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };

        highlight.addEventListener('animationend', cleanup);

        row.classList.add('matiere-row-flash');
        if (isReject) {
            row.classList.add('reject');
        }

        setTimeout(() => {
            row.classList.remove('matiere-row-flash', 'reject');
        }, 1200);
    }
    window.triggerMatiereRowHighlight = triggerMatiereRowHighlight;

    async function refreshMatiereRow(matiereId, actionType = 'update') {
        const url = `{{ route('esbtp.matieres.refresh-ligne', ['matiere' => '__ID__']) }}`.replace('__ID__', matiereId);
        setMatiereRowLoadingState(matiereId, true);

        try {
            const data = await ajaxJson(url);
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse invalide');
            }

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const newRow = template.content.querySelector(`tr[data-matiere-id="${matiereId}"]`);
            const existingRow = document.querySelector(`tr[data-matiere-id="${matiereId}"]`);

            if (existingRow && newRow) {
                existingRow.replaceWith(newRow);
                triggerMatiereRowHighlight(newRow, actionType);
            }
        } catch (error) {
            debugError('Erreur lors du rafraîchissement de la matière:', error);
        } finally {
            setMatiereRowLoadingState(matiereId, false);
            initTableInteractions();
        }
    }
    window.refreshMatiereRow = refreshMatiereRow;

    async function submitFilterForm(pushHistory = true) {
        if (!filtersForm || !resultsContainer) {
            return;
        }

        const params = new URLSearchParams(new FormData(filtersForm));
        const url = `${resultsContainer.dataset.refreshUrl}?${params.toString()}`;

        resultsContainer.classList.add('position-relative');
        const overlay = document.createElement('div');
        overlay.className = 'd-flex align-items-center justify-content-center';
        overlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);z-index:5';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        resultsContainer.appendChild(overlay);

        try {
            const data = await ajaxJson(url);
            applyListingResponse(data, { pushHistory });
        } catch (error) {
            debugError('Erreur lors du rafraîchissement des matières:', error);
        } finally {
            overlay.remove();
            resultsContainer.classList.remove('position-relative');
            initTableInteractions();
        }
    }

    function initTableInteractions() {
        const tableCheckboxes = resultsContainer
            ? resultsContainer.querySelectorAll('.matiere-checkbox')
            : [];

        tableCheckboxes.forEach((checkbox) => {
            checkbox.removeEventListener('change', handleRowSelection);
            checkbox.addEventListener('change', handleRowSelection);
            if (selectedIds.has(Number(checkbox.value))) {
                checkbox.checked = true;
            }
        });

        const selectAll = document.getElementById('matieres-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                const checked = selectAll.checked;
                const tableCheckboxes = resultsContainer
                    ? resultsContainer.querySelectorAll('.matiere-checkbox')
                    : [];

                tableCheckboxes.forEach((checkbox) => {
                    checkbox.checked = checked;
                    const id = Number(checkbox.value);
                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                toggleBulkBar();
            });
        }

        resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', async (event) => {
                event.preventDefault();
                const href = link.getAttribute('href');
                if (!href || href === '#') {
                    return;
                }
                try {
                    const data = await ajaxJson(href);
                    applyListingResponse(data);
                    initTableInteractions();
                } catch (error) {
                    debugError('Erreur pagination matières:', error);
                }
            });
        });
    }

    function handleRowSelection(event) {
        const checkbox = event.target;
        const id = Number(checkbox.value);
        if (checkbox.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        toggleBulkBar();
    }

    // Filter-search : input débouncé + Enter immédiat. C'est désormais le seul
    // input search de la page (le hero search a été retiré).
    const filterSearchInput = document.getElementById('filter-search');
    if (filterSearchInput) {
        filterSearchInput.addEventListener('input', () => {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });
        filterSearchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitFilterForm();
            }
        });
    }

    if (filtersForm) {
        filtersForm.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilterForm();
        });

        filtersForm.querySelectorAll('select, input').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.id === 'filter-search') {
                    return;
                }
                clearTimeout(filterTimer);
                filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
            });
        });
    }

    if (clearFiltersBtn && filtersForm) {
        clearFiltersBtn.addEventListener('click', () => {
            filtersForm.reset();
            submitFilterForm();
        });
    }

    if (bulkClearBtn) {
        bulkClearBtn.addEventListener('click', clearSelection);
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', async () => {
            const ids = getSelectedIdsArray();
            if (ids.length === 0) {
                return;
            }

            const ok = await window.iiConfirm({
                title: 'Suppression en lot',
                message: `Supprimer ${ids.length} matière(s) sélectionnée(s) ? Cette action est irréversible.`,
                confirmLabel: 'Supprimer',
                cancelLabel: 'Annuler',
                danger: true,
            });
            if (!ok) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const formData = new FormData();
            formData.append('_token', csrfToken || '');
            formData.append('_method', 'DELETE');
            ids.forEach((id) => formData.append('matieres[]', id));

            bulkDeleteBtn.disabled = true;
            const originalLabel = bulkDeleteBtn.innerHTML;
            bulkDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';

            try {
                const response = await fetch('{{ route('esbtp.matieres.bulk-delete') }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok && response.status !== 302) {
                    throw new Error(`HTTP ${response.status}`);
                }

                clearSelection();
                window.showToast(`${ids.length} matière(s) supprimée(s).`, 'success');
                submitFilterForm(false);
            } catch (error) {
                debugError('Erreur lors de la suppression en lot:', error);
                window.showToast('Impossible de supprimer les matières sélectionnées.', 'error');
            } finally {
                bulkDeleteBtn.disabled = false;
                bulkDeleteBtn.innerHTML = originalLabel;
            }
        });
    }

    if (bulkAttachBtn) {
        bulkAttachBtn.addEventListener('click', () => {
            const ids = getSelectedIdsArray();
            if (ids.length === 0) {
                alert('Sélectionnez au moins une matière à attacher.');
                return;
            }
            openConfigureModal({
                mode: 'bulk',
                matiereName: `${ids.length} matière(s) sélectionnée(s)`,
                selectedIds: ids
            });
        });
    }

    if (bulkConfigureBtn) {
        bulkConfigureBtn.addEventListener('click', () => {
            const ids = getSelectedIdsArray();
            if (ids.length !== 1) {
                return;
            }
            const row = document.querySelector(`tr[data-matiere-id="${ids[0]}"]`);
            const name = row ? row.querySelector('td:nth-child(3) .font-semibold')?.textContent?.trim() : 'Matière';
            openConfigureModal({
                mode: 'single',
                matiereId: ids[0],
                matiereName: name || 'Matière'
            });
        });
    }

    function openConfigureModal(options) {
        const matiereNameElement = document.getElementById('modal-matiere-name');
        const matiereIdInput = document.getElementById('modal-matiere-id');
        const combinationsPreview = document.getElementById('combinations-preview');

        document.querySelectorAll('.niveau-filiere-checkbox').forEach((checkbox) => {
            checkbox.checked = false;
        });
        // Synchroniser les pills (réinitialiser)
        if (typeof fnSyncPillsFromCheckboxes === 'function') fnSyncPillsFromCheckboxes();

        if (combinationsPreview) {
            combinationsPreview.innerHTML = `
                <div class="d-flex align-items-center" style="color: #0369a1;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>Cochez des niveaux dans les filières ci-dessus pour voir les liaisons.</span>
                </div>
            `;
        }

        if (matiereNameElement) {
            matiereNameElement.textContent = options.matiereName || '';
        }
        if (matiereIdInput) {
            matiereIdInput.value = options.matiereId || '';
        }

        if (options.matiereId) {
            loadExistingLiaisons(options.matiereId);
        }

        const modalElement = document.getElementById('configureModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
        }
    }
    window.openConfigureModal = openConfigureModal;

    function loadExistingLiaisons(matiereId) {
        fetch(`/esbtp/matieres/${matiereId}/liaisons`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success) {
                    return;
                }
                // Cocher les checkboxes correspondant aux liaisons filière+niveau
                document.querySelectorAll('.niveau-filiere-checkbox').forEach((checkbox) => {
                    const filiereId = Number(checkbox.dataset.filiereId);
                    const niveauId = Number(checkbox.dataset.niveauId);
                    checkbox.checked = (data.liaisons || []).some(
                        (l) => l.filiere_id === filiereId && l.niveau_id === niveauId
                    );
                });
                // Synchroniser les pills avec les checkboxes pré-cochées
                if (typeof fnSyncPillsFromCheckboxes === 'function') fnSyncPillsFromCheckboxes();
                updateCombinationsPreview();
            })
            .catch((error) => {
                debugError('Erreur chargement liaisons matière:', error);
            });
    }

    window.updateCombinationsPreview = updateCombinationsPreview;
    function updateCombinationsPreview() {
        const combinationsPreview = document.getElementById('combinations-preview');
        if (!combinationsPreview) {
            return;
        }

        const checked = Array.from(document.querySelectorAll('.niveau-filiere-checkbox:checked'));

        if (checked.length === 0) {
            combinationsPreview.innerHTML = `
                <div class="d-flex align-items-center" style="color: #0369a1;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>Cochez des niveaux dans les filières ci-dessus pour voir les liaisons.</span>
                </div>
            `;
            return;
        }

        let html = `
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-check-circle me-2" style="color: #059669;"></i>
                <strong style="color: #047857;">${checked.length} liaison(s) configurée(s)</strong>
            </div>
            <div class="d-flex flex-wrap gap-2">
        `;

        checked.forEach((cb) => {
            html += `
                <span class="badge primary">
                    <i class="fas fa-link me-1"></i>
                    ${cb.dataset.filiereLabel} ↔ ${cb.dataset.niveauLabel}
                </span>
            `;
        });

        html += '</div>';
        combinationsPreview.innerHTML = html;
    }

    document.querySelectorAll('.niveau-filiere-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            updateCombinationsPreview();
        });
    });

    const configureModalElement = document.getElementById('configureModal');
    if (configureModalElement) {
        configureModalElement.addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.niveau-filiere-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
            });
            // Réinitialiser les pills
            if (typeof fnSyncPillsFromCheckboxes === 'function') fnSyncPillsFromCheckboxes();
            updateCombinationsPreview();
            document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        });
    }

    const saveLiaisonsBtn = document.getElementById('save-liaisons-btn');
    if (saveLiaisonsBtn) {
        saveLiaisonsBtn.addEventListener('click', async () => {
            const mode = document.getElementById('modal-mode')?.value ?? 'single';
            const matiereId = document.getElementById('modal-matiere-id')?.value;
            const checkedBoxes = Array.from(document.querySelectorAll('.niveau-filiere-checkbox:checked'));
            const liaisons = checkedBoxes.map((cb) => ({
                filiere_id: Number(cb.dataset.filiereId),
                niveau_id: Number(cb.dataset.niveauId)
            }));

            if (liaisons.length === 0) {
                const ok = await window.iiConfirm({
                    title: 'Aucune liaison',
                    message: 'Voulez-vous tout de même continuer ? Cela supprimera toutes les liaisons existantes.',
                    confirmLabel: 'Continuer',
                    cancelLabel: 'Annuler',
                    danger: true,
                });
                if (!ok) return;
            }

            const modalElement = document.getElementById('configureModal');
            const modalInstance = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;

            saveLiaisonsBtn.disabled = true;
            const originalLabel = saveLiaisonsBtn.innerHTML;
            saveLiaisonsBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';

            if (matiereId) {
                fetch(`/esbtp/matieres/${matiereId}/update-liaisons`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ liaisons })
                })
                    .then((response) => {
                        if (!response.ok) {
                            return response.json().then((data) => {
                                throw new Error(data.message || `HTTP ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (!data.success) {
                            throw new Error(data.message || 'Erreur serveur');
                        }
                        refreshMatiereRow(matiereId);
                        modalInstance?.hide();
                        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('padding-right');
                        window.showToast(data.message || 'Liaisons mises à jour avec succès.', 'success');
                    })
                    .catch((error) => {
                        debugError('Erreur lors de la mise à jour des liaisons:', error);
                        window.showToast(error.message || 'Impossible de mettre à jour les liaisons.', 'error');
                    })
                    .finally(() => {
                        saveLiaisonsBtn.disabled = false;
                        saveLiaisonsBtn.innerHTML = originalLabel;
                    });
            }
        });
    }

    document.addEventListener('click', (event) => {
        const configureBtn = event.target.closest('.configure-matiere-btn');
        if (configureBtn) {
            event.preventDefault();
            const matiereId = Number(configureBtn.dataset.matiereId);
            const matiereName = configureBtn.dataset.matiereName || 'Matière';
            openConfigureModal({
                mode: 'single',
                matiereId,
                matiereName
            });
        }
    });

    /* ───────────────────────────────────────────────────────────
       KEBAB MENU — event delegation, persists across AJAX refreshes
       ─────────────────────────────────────────────────────────── */
    function closeAllKebabMenus(except) {
        document.querySelectorAll('[data-mi-kebab-menu].is-open').forEach((menu) => {
            if (menu === except) return;
            menu.classList.remove('is-open');
            const wrap = menu.closest('.mi-action-kebab-wrap');
            const toggle = wrap?.querySelector('[data-mi-kebab-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-mi-kebab-toggle]');
        if (toggle) {
            event.preventDefault();
            const wrap = toggle.closest('.mi-action-kebab-wrap');
            const menu = wrap?.querySelector('[data-mi-kebab-menu]');
            if (!menu) return;
            const willOpen = !menu.classList.contains('is-open');
            closeAllKebabMenus(willOpen ? menu : null);
            menu.classList.toggle('is-open', willOpen);
            toggle.setAttribute('aria-expanded', String(willOpen));
            return;
        }

        // Click on a menu item → close menu (item handles its own action via data-bs-toggle).
        const menuItem = event.target.closest('[data-mi-kebab-menu] [role="menuitem"]');
        if (menuItem) {
            const menu = menuItem.closest('[data-mi-kebab-menu]');
            if (menu) {
                menu.classList.remove('is-open');
                const wrap = menu.closest('.mi-action-kebab-wrap');
                const t = wrap?.querySelector('[data-mi-kebab-toggle]');
                if (t) t.setAttribute('aria-expanded', 'false');
            }
            return;
        }

        // Click outside any kebab → close all.
        if (!event.target.closest('[data-mi-kebab-menu]')) {
            closeAllKebabMenus(null);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllKebabMenus(null);
        }
    });

    /* Empty-state "Effacer les filtres" — délégation pour survivre aux refresh AJAX */
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('#mi-empty-clear-filters');
        if (!btn) return;
        event.preventDefault();
        if (filtersForm) filtersForm.reset();
        submitFilterForm();
    });

    updateSummary(JSON.parse(resultsContainer.dataset.summary || '{}'));
    initTableInteractions();

    window.addEventListener('popstate', () => {
        applyQueryToForm(window.location.href);
        submitFilterForm(false);
    });
})();
</script>
@endpush
