@extends('layouts.app')

@section('title', 'Gestion des Frais - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════ Namespace fi-* (frais.index premium) ═══════ */
.fi-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    /* overflow visible : dropdowns et elements absolute ne sont pas clipes */
}

/* Hero KPIs (row 2 glass) */
.fi-hero-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .75rem;
    margin-top: 1.5rem;
}
.fi-hero-kpi {
    display: block;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 12px;
    padding: .9rem 1rem;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
    transition: background .2s, border-color .2s, transform .15s;
}
.fi-hero-kpi:hover {
    background: rgba(255,255,255,.16);
    border-color: rgba(255,255,255,.3);
    color: #fff;
    transform: translateY(-2px);
    text-decoration: none;
}
.fi-hero-kpi-head {
    display: flex;
    align-items: center;
    gap: .35rem;
    margin-bottom: .35rem;
    color: rgba(255,255,255,.8);
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.fi-hero-kpi-head i { font-size: .72rem; }
.fi-hero-kpi-value {
    font-size: 1.7rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
    letter-spacing: -.01em;
}
.fi-hero-kpi-meta {
    margin-top: .25rem;
    font-size: .72rem;
    color: rgba(255,255,255,.7);
    font-weight: 500;
}
@media (max-width: 992px) {
    .fi-hero-kpis { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .fi-hero-kpis { grid-template-columns: 1fr; gap: .5rem; }
    .fi-hero-kpi-value { font-size: 1.4rem; }
}
.fi-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.fi-hero-left {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}
.fi-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.fi-hero h1 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 .2rem;
    letter-spacing: -.01em;
}
.fi-hero p {
    color: rgba(255,255,255,.72);
    font-size: .88rem;
    margin: 0 0 .55rem;
}
.fi-hero-chips {
    display: flex; flex-wrap: wrap; gap: .4rem;
}
.fi-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 99px;
    font-size: .74rem; font-weight: 600;
    color: rgba(255,255,255,.94);
}
.fi-chip i { font-size: .7rem; }
.fi-hero-actions {
    display: flex; gap: .5rem; flex-wrap: wrap; align-items: center;
}
.fi-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    border-radius: 10px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .2s ease;
    text-decoration: none;
    white-space: nowrap;
    font-family: inherit;
}
.fi-btn--glass {
    background: rgba(255,255,255,.15);
    color: #fff;
    border-color: rgba(255,255,255,.2);
}
.fi-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.fi-btn--white {
    background: #fff; color: #0453cb;
    border-color: transparent;
}
.fi-btn--white:hover { background: #f8fafc; color: #0453cb; transform: translateY(-1px); }
.fi-btn i { font-size: .78rem; }

/* KPI drill-down (liens) */
.fi-kpi-link {
    display: block;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .2s ease;
}
.fi-kpi-link:hover {
    color: inherit;
    text-decoration: none;
    transform: translateY(-2px);
}
.fi-kpi-link:hover .kpi-card {
    box-shadow: 0 8px 20px rgba(4,83,203,.1);
    border-color: #cbd5e1;
}

/* Action buttons sur category-card — polish consistency */
.category-actions .btn-acasi {
    padding: .4rem .7rem;
    font-size: .8rem;
}

/* Empty state amelioré */
.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #64748b;
}
.empty-state > i {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
    display: block;
}
.empty-state p {
    margin-bottom: 1.25rem;
    font-size: .95rem;
    color: #475569;
}

/* Toast (reuse) */
.fi-toast-container {
    position: fixed;
    top: 80px; right: 20px;
    z-index: 1080;
    display: flex; flex-direction: column;
    gap: .5rem;
    max-width: 360px;
    pointer-events: none;
}
.fi-toast {
    background: #fff;
    border-radius: 12px;
    padding: .85rem 1rem;
    box-shadow: 0 8px 24px rgba(15,23,42,.15);
    display: flex; align-items: flex-start; gap: .6rem;
    border-left: 4px solid #0453cb;
    pointer-events: auto;
    animation: fi-toast-in .25s ease forwards;
    font-size: .86rem;
}
.fi-toast.is-leaving { animation: fi-toast-out .2s ease forwards; }
.fi-toast-icon {
    font-size: 1rem;
    color: #0453cb;
    flex-shrink: 0;
    margin-top: .15rem;
}
.fi-toast-body { flex: 1; color: #1e293b; line-height: 1.4; }
.fi-toast-title { font-weight: 700; color: #0f172a; margin-bottom: .1rem; font-size: .88rem; }
.fi-toast-close {
    background: none; border: none; color: #94a3b8;
    font-size: 1.1rem; cursor: pointer;
    padding: 0 .15rem; line-height: 1;
    margin-left: .15rem;
    transition: color .2s;
}
.fi-toast-close:hover { color: #475569; }
@keyframes fi-toast-in  { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
@keyframes fi-toast-out { to { opacity: 0; transform: translateX(20px); } }

@media (max-width: 992px) {
    .fi-hero-top { flex-direction: column; align-items: stretch; }
    .fi-hero-actions { justify-content: flex-start; }
}
@media (max-width: 576px) {
    .fi-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
    .fi-hero h1 { font-size: 1.2rem; }
    .fi-hero p { font-size: .82rem; }
}

/* Système d'onglets moderne */
.tabs-container {
    background: white;
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    margin-bottom: var(--space-lg);
}

.tabs-navigation {
    display: flex;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tab-button {
    flex: 1;
    padding: var(--space-lg);
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    font-size: var(--text-normal);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    position: relative;
}

.tab-button:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.tab-button.active {
    color: white;
    background: rgba(255, 255, 255, 0.15);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: white;
}

.tab-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-full);
    font-size: var(--text-small);
    font-weight: 600;
}

.tab-content {
    padding: var(--space-lg);
    min-height: 400px;
}

/* Sous-filtre type (chips) */
.fi-type-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1rem;
    padding: .5rem .25rem;
}
.fi-type-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .35rem .8rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 50px;
    background: #fff;
    color: #475569;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
}
.fi-type-chip:hover:not(:disabled) {
    border-color: #5e91de;
    color: #0453cb;
}
.fi-type-chip.active {
    background: linear-gradient(135deg, #0453cb, #5e91de);
    border-color: #0453cb;
    color: #fff;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.fi-type-chip:disabled {
    opacity: .45;
    cursor: not-allowed;
}
.fi-type-chip-count {
    background: rgba(0,0,0,.08);
    padding: 1px 7px;
    border-radius: 50px;
    font-size: .72rem;
    font-weight: 700;
}
.fi-type-chip.active .fi-type-chip-count {
    background: rgba(255,255,255,.25);
    color: #fff;
}

/* Pill secondaire affiché sur chaque card pour rappeler le category_type */
.fi-type-pill {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    padding: 2px 9px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 50px;
    font-size: .7rem;
    font-weight: 600;
    margin-left: .35rem;
}
.fi-type-pill i {
    font-size: .65rem;
    color: #64748b;
}

/* Empty state quand le sous-filtre ne match aucune card */
.fi-type-empty {
    display: none;
    text-align: center;
    padding: 2rem 1rem;
    color: #64748b;
    font-size: .9rem;
}
.fi-type-empty.show { display: block; }

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-header {
    margin-bottom: var(--space-lg);
    text-align: center;
}

.tab-title {
    font-size: var(--title-main);
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
}

.tab-subtitle {
    color: var(--text-secondary);
    font-size: var(--text-normal);
}

/* Bouton "+ Ajouter" : inline dans tab-header, plus en position absolute
   (qui debordait du tab-container) */
.add-category-btn {
    position: static;
    margin-top: 0;
}
.tab-header {
    position: relative;
}
.tab-header .add-category-btn {
    position: absolute;
    top: 0;
    right: 0;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-lg);
}

.category-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    transition: all 0.3s ease;
    overflow: hidden;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary);
}

.category-header {
    padding: var(--space-lg);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.05), rgba(30, 64, 175, 0.02));
}

.category-name {
    font-size: var(--text-large);
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-xs);
}

.category-description {
    font-size: var(--text-small);
    color: var(--text-secondary);
    margin: 0;
}

.category-body {
    padding: var(--space-lg);
}

.category-amount {
    font-size: var(--amount-large);
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-md);
    text-align: center;
}

.category-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-md);
}

.category-variants {
    font-size: var(--text-small);
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.category-actions {
    display: flex;
    gap: var(--space-sm);
    justify-content: center;
    padding-top: var(--space-md);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Modal centering fix - ensures modals are centered regardless of scroll position */
/* NOTE: Must target .modal.show only — targeting .modal causes hidden modals to become
   invisible fullscreen overlays (position:fixed 100vw/100vh) that block all clicks */
.modal.show {
    display: flex !important;
    align-items: center;
    justify-content: center;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 1050;
}

.modal-dialog {
    margin: 0 !important;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-content {
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-body {
    overflow-y: auto;
    max-height: calc(90vh - 120px); /* Adjust based on header/footer height */
}

/* Ensure backdrop covers entire viewport */
.modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 1040;
}

@media (max-width: 768px) {
    .tabs-navigation {
        flex-direction: column;
    }
    
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .category-actions {
        flex-direction: column;
    }
    
    .add-category-btn {
        position: static;
        margin-top: var(--space-md);
        align-self: center;
    }
    
    .modal-dialog {
        width: 95vw !important;
        max-width: 95vw !important;
        margin: 0 !important;
    }
    
    .modal-content {
        max-height: 95vh;
    }
    
    .modal-body {
        max-height: calc(95vh - 120px);
    }
}
</style>
@endsection

@section('content')
<div class="dashboard-main-grid">
    {{-- Hero premium fi-* --}}
    <div class="fi-hero">
        <div class="fi-hero-top">
            <div class="fi-hero-left">
                <span class="fi-hero-icon"><i class="fas fa-euro-sign"></i></span>
                <div>
                    <h1>Gestion des Frais</h1>
                    <p>Configuration et gestion des frais scolaires par catégorie et type</p>
                    <div class="fi-hero-chips">
                        <span class="fi-chip">
                            <i class="fas fa-layer-group"></i>
                            {{ $stats['active_categories'] }}/{{ $stats['total_categories'] }} actives
                        </span>
                    </div>
                </div>
            </div>
            <div class="fi-hero-actions">
                <a href="{{ route('esbtp.frais.optional-config') }}" class="fi-btn fi-btn--glass">
                    <i class="fas fa-globe"></i>
                    <span>Services Optionnels</span>
                </a>
                <a href="{{ route('esbtp.frais.configure') }}" class="fi-btn fi-btn--glass">
                    <i class="fas fa-cogs"></i>
                    <span>Configuration par Classe</span>
                </a>
                <a href="{{ route('esbtp.frais.create') }}" class="fi-btn fi-btn--white">
                    <i class="fas fa-plus"></i>
                    <span>Nouvelle catégorie</span>
                </a>
            </div>
        </div>

        {{-- Row 2 : KPIs glass (drill-down vers onglet correspondant) --}}
        <div class="fi-hero-kpis">
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('mandatory'); return false;" title="Toutes les catégories">
                <div class="fi-hero-kpi-head">
                    <i class="fas fa-layer-group"></i>
                    <span>Total catégories</span>
                </div>
                <div class="fi-hero-kpi-value">{{ $stats['total_categories'] }}</div>
                <div class="fi-hero-kpi-meta">Toutes confondues</div>
            </a>
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('mandatory'); return false;" title="Voir les frais obligatoires">
                <div class="fi-hero-kpi-head">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Frais obligatoires</span>
                </div>
                <div class="fi-hero-kpi-value">{{ $stats['mandatory_categories'] }}</div>
                <div class="fi-hero-kpi-meta">À payer par tous</div>
            </a>
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('optional'); return false;" title="Voir les frais optionnels">
                <div class="fi-hero-kpi-head">
                    <i class="fas fa-star"></i>
                    <span>Frais optionnels</span>
                </div>
                <div class="fi-hero-kpi-value">{{ $stats['optional_categories'] }}</div>
                <div class="fi-hero-kpi-meta">Cantine, transport, électifs…</div>
            </a>
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('mandatory'); return false;" title="Voir les catégories actives">
                <div class="fi-hero-kpi-head">
                    <i class="fas fa-check-circle"></i>
                    <span>Catégories actives</span>
                </div>
                <div class="fi-hero-kpi-value">{{ $stats['active_categories'] }}</div>
                <div class="fi-hero-kpi-meta">Configurées</div>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KPIs deplaces dans fi-hero row 2 ci-dessus (voir hero) --}}

    <!-- Système d'onglets pour les catégories : 2 tabs basés sur is_mandatory -->
    <div class="tabs-container">
        <!-- Navigation des onglets -->
        <div class="tabs-navigation">
            <button class="tab-button active" data-tab="mandatory">
                <i class="fas fa-exclamation-circle"></i>
                Frais obligatoires
                <span class="tab-badge">{{ $categoriesByMandatory['mandatory']->count() }}</span>
            </button>
            <button class="tab-button" data-tab="optional">
                <i class="fas fa-star"></i>
                Frais optionnels
                <span class="tab-badge">{{ $categoriesByMandatory['optional']->count() }}</span>
            </button>
        </div>

        <!-- Contenu des onglets : 2 panes (mandatory / optional) avec sous-filtre par category_type -->
        <div class="tab-content">
            @php
                $typeMeta = [
                    'academic' => ['label' => 'Académique', 'icon' => 'fa-graduation-cap'],
                    'service' => ['label' => 'Service', 'icon' => 'fa-cogs'],
                    'administrative' => ['label' => 'Administratif', 'icon' => 'fa-file-alt'],
                ];
            @endphp

            {{-- ═══ Frais obligatoires ═══ --}}
            <div class="tab-pane active" id="mandatory">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-exclamation-circle color-success"></i>
                        Frais obligatoires
                    </h2>
                    <p class="tab-subtitle">Frais à payer par tous les étudiants — inscription, scolarité, et autres frais réglementaires.</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryByMandatory(1)">
                        <i class="fas fa-plus"></i>Ajouter un frais obligatoire
                    </button>
                </div>

                {{-- Sous-filtre par category_type (chips JS) --}}
                <div class="fi-type-chips" data-tab-target="mandatory">
                    <button type="button" class="fi-type-chip active" data-type="all">
                        <i class="fas fa-list"></i> Tous
                        <span class="fi-type-chip-count">{{ $categoriesByMandatory['mandatory']->count() }}</span>
                    </button>
                    @foreach($typeMeta as $typeKey => $meta)
                        @php $count = $categoriesByMandatory['mandatory']->where('category_type', $typeKey)->count(); @endphp
                        <button type="button" class="fi-type-chip" data-type="{{ $typeKey }}" @if($count === 0) disabled @endif>
                            <i class="fas {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                            <span class="fi-type-chip-count">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                @if($categoriesByMandatory['mandatory']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByMandatory['mandatory'] as $category)
                            @php $cat_type = $category->category_type ?: 'administrative'; @endphp
                            <div class="category-card" data-type="{{ $cat_type }}">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    <div class="category-amount">
                                        <i class="fas fa-graduation-cap"></i>
                                        Configuré par classe
                                    </div>
                                    <div class="category-meta">
                                        <div class="category-variants">
                                            <i class="fas fa-cogs"></i>
                                            {{ $category->configuration_status['message'] ?? 'Aucune configuration' }}
                                        </div>
                                        <span class="badge success">Obligatoire</span>
                                        @isset($typeMeta[$cat_type])
                                            <span class="fi-type-pill"><i class="fas {{ $typeMeta[$cat_type]['icon'] }}"></i> {{ $typeMeta[$cat_type]['label'] }}</span>
                                        @endisset
                                    </div>
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                                            <i class="fas fa-graduation-cap"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Aucun frais obligatoire configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryByMandatory(1)">
                            <i class="fas fa-plus"></i>Ajouter le premier frais obligatoire
                        </button>
                    </div>
                @endif
            </div>

            {{-- ═══ Frais optionnels ═══ --}}
            <div class="tab-pane" id="optional">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-star color-warning"></i>
                        Frais optionnels
                    </h2>
                    <p class="tab-subtitle">Frais à la carte — l'étudiant choisit ou pas (cantine, transport, modules électifs, etc.).</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryByMandatory(0)">
                        <i class="fas fa-plus"></i>Ajouter un frais optionnel
                    </button>
                </div>

                {{-- Sous-filtre par category_type (chips JS) --}}
                <div class="fi-type-chips" data-tab-target="optional">
                    <button type="button" class="fi-type-chip active" data-type="all">
                        <i class="fas fa-list"></i> Tous
                        <span class="fi-type-chip-count">{{ $categoriesByMandatory['optional']->count() }}</span>
                    </button>
                    @foreach($typeMeta as $typeKey => $meta)
                        @php $count = $categoriesByMandatory['optional']->where('category_type', $typeKey)->count(); @endphp
                        <button type="button" class="fi-type-chip" data-type="{{ $typeKey }}" @if($count === 0) disabled @endif>
                            <i class="fas {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                            <span class="fi-type-chip-count">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                @if($categoriesByMandatory['optional']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByMandatory['optional'] as $category)
                            @php $cat_type = $category->category_type ?: 'service'; @endphp
                            <div class="category-card" data-type="{{ $cat_type }}">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    @if($category->configuration_status['is_configured'] ?? false)
                                        <div class="category-amount">
                                            <i class="fas fa-check-circle color-success"></i>
                                            Configuré
                                        </div>
                                    @else
                                        <div class="category-amount">
                                            <i class="fas fa-exclamation-triangle color-warning"></i>
                                            À configurer
                                        </div>
                                    @endif
                                    <div class="category-meta">
                                        <div class="category-variants">
                                            <i class="fas fa-globe"></i>
                                            {{ $category->configuration_status['message'] ?? 'Aucune option' }}
                                        </div>
                                        <span class="badge warning">Optionnel</span>
                                        @isset($typeMeta[$cat_type])
                                            <span class="fi-type-pill"><i class="fas {{ $typeMeta[$cat_type]['icon'] }}"></i> {{ $typeMeta[$cat_type]['label'] }}</span>
                                        @endisset
                                    </div>
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Frais optionnels">
                                            <i class="fas fa-globe"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-star"></i>
                        <p>Aucun frais optionnel configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryByMandatory(0)">
                            <i class="fas fa-plus"></i>Ajouter le premier frais optionnel
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-bolt me-2"></i>
                Actions Rapides
            </h2>
        </div>
        <div class="quick-actions-grid">
            <a href="{{ route('esbtp.frais.configure') }}" class="quick-action-card">
                <i class="fas fa-cogs"></i>
                <span>Configuration par Classe</span>
            </a>
            
            <a href="{{ route('esbtp.frais.create') }}" class="quick-action-card">
                <i class="fas fa-plus"></i>
                <span>Nouvelle Catégorie</span>
            </a>
            
            <a href="{{ route('esbtp.paiements.index') }}" class="quick-action-card">
                <i class="fas fa-credit-card"></i>
                <span>Suivi des Paiements</span>
            </a>
            
            <button type="button" class="quick-action-card" onclick="showGlobalStats()">
                <i class="fas fa-chart-bar"></i>
                <span>Statistiques Globales</span>
            </button>
        </div>
    </div>
</div>

{{-- Toast container --}}
<div id="fi-toast-container" class="fi-toast-container" aria-live="polite" aria-atomic="true"></div>

<x-fab-encaisser />
@endsection

@push('scripts')
<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        });
    }, 5000);

    // Système d'onglets
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            // Désactiver tous les onglets
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Activer l'onglet cliqué
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });

    // Sous-filtre par category_type — chips JS dans chaque tab
    document.querySelectorAll('.fi-type-chips').forEach(chipBar => {
        const tabId = chipBar.getAttribute('data-tab-target');
        const pane = document.getElementById(tabId);
        if (!pane) return;

        chipBar.querySelectorAll('.fi-type-chip').forEach(chip => {
            chip.addEventListener('click', function () {
                if (this.disabled) return;
                const type = this.getAttribute('data-type');

                chipBar.querySelectorAll('.fi-type-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                let visibleCount = 0;
                pane.querySelectorAll('.category-card').forEach(card => {
                    const cardType = card.getAttribute('data-type');
                    const show = (type === 'all') || (cardType === type);
                    card.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                // Toggle empty state si filtre ne match rien
                let emptyHint = pane.querySelector('.fi-type-empty');
                if (visibleCount === 0 && !emptyHint) {
                    emptyHint = document.createElement('div');
                    emptyHint.className = 'fi-type-empty show';
                    emptyHint.innerHTML = '<i class="fas fa-filter"></i> Aucune catégorie de ce type dans cet onglet.';
                    const grid = pane.querySelector('.category-grid');
                    if (grid) grid.parentNode.insertBefore(emptyHint, grid.nextSibling);
                } else if (emptyHint) {
                    emptyHint.classList.toggle('show', visibleCount === 0);
                }
            });
        });
    });
});

// Fonction pour ajouter une catégorie avec is_mandatory pré-sélectionné
function addCategoryByMandatory(isMandatory) {
    window.location.href = `{{ route('esbtp.frais.create') }}?is_mandatory=${isMandatory ? 1 : 0}`;
}

// Drill-down KPI : active l'onglet et scroll vers le contenu
function scrollToTab(tabId) {
    const button = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
    const pane = document.getElementById(tabId);
    if (!button || !pane) return;

    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    button.classList.add('active');
    pane.classList.add('active');

    document.querySelector('.tabs-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Toast helper (remplace alert pour statistiques globales TODO)
function showGlobalStats() {
    if (typeof window.fiToast === 'function') {
        window.fiToast('Les statistiques globales seront disponibles dans une prochaine version.', 'info');
        return;
    }
    // Fallback toast minimal
    const container = document.getElementById('fi-toast-container');
    if (container) {
        const toast = document.createElement('div');
        toast.className = 'fi-toast';
        toast.innerHTML = '<i class="fas fa-info-circle fi-toast-icon"></i><div class="fi-toast-body"><div class="fi-toast-title">Bientôt disponible</div><div>Les statistiques globales seront disponibles dans une prochaine version.</div></div><button type="button" class="fi-toast-close" aria-label="Fermer">&times;</button>';
        container.appendChild(toast);
        toast.querySelector('.fi-toast-close').addEventListener('click', () => toast.remove());
        setTimeout(() => toast.remove(), 5000);
    }
}

</script>
@endpush