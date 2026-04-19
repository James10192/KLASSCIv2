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
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('academic'); return false;" title="Voir les frais académiques">
                <div class="fi-hero-kpi-head">
                    <i class="fas fa-layer-group"></i>
                    <span>Total catégories</span>
                </div>
                <div class="fi-hero-kpi-value">{{ $stats['total_categories'] }}</div>
                <div class="fi-hero-kpi-meta">Toutes confondues</div>
            </a>
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('academic'); return false;" title="Voir les frais obligatoires">
                <div class="fi-hero-kpi-head">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Frais obligatoires</span>
                </div>
                <div class="fi-hero-kpi-value">{{ $stats['mandatory_categories'] }}</div>
                <div class="fi-hero-kpi-meta">À payer par tous</div>
            </a>
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('service'); return false;" title="Voir les services optionnels">
                <div class="fi-hero-kpi-head">
                    <i class="fas fa-star"></i>
                    <span>Services optionnels</span>
                </div>
                <div class="fi-hero-kpi-value">{{ $stats['optional_categories'] }}</div>
                <div class="fi-hero-kpi-meta">Cantine & transport</div>
            </a>
            <a href="#" class="fi-hero-kpi" onclick="scrollToTab('administrative'); return false;" title="Voir les catégories actives">
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

    <!-- Système d'onglets pour les catégories -->
    <div class="tabs-container">
        <!-- Navigation des onglets -->
        <div class="tabs-navigation">
            <button class="tab-button active" data-tab="academic">
                <i class="fas fa-graduation-cap"></i>
                Frais Académiques
                <span class="tab-badge">{{ $categoriesByType['academic']->count() }}</span>
            </button>
            <button class="tab-button" data-tab="service">
                <i class="fas fa-cogs"></i>
                Services Optionnels
                <span class="tab-badge">{{ $categoriesByType['service']->count() }}</span>
            </button>
            <button class="tab-button" data-tab="administrative">
                <i class="fas fa-file-alt"></i>
                Frais Administratifs
                <span class="tab-badge">{{ $categoriesByType['administrative']->count() }}</span>
            </button>
        </div>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            <!-- Onglet Frais Académiques -->
            <div class="tab-pane active" id="academic">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-graduation-cap color-success"></i>
                        Frais Académiques
                    </h2>
                    <p class="tab-subtitle">Frais d'inscription et de scolarité obligatoires selon la filière et le niveau</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryForType('academic')">
                        <i class="fas fa-plus"></i>Ajouter Frais Académique
                    </button>
                </div>

                @if($categoriesByType['academic']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByType['academic'] as $category)
                            <div class="category-card">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    @if($category->is_mandatory)
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
                                        </div>
                                    @else
                                        @if($category->configuration_status['is_configured'] ?? false)
                                            <div class="category-amount">
                                                <i class="fas fa-check-circle color-success"></i>
                                                Service configuré
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
                                            <span class="badge warning">Service Global</span>
                                        </div>
                                    @endif
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($category->is_mandatory)
                                            <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Services optionnels">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <p>Aucun frais académique configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryForType('academic')">
                            <i class="fas fa-plus"></i>Ajouter le premier frais académique
                        </button>
                    </div>
                @endif
            </div>

            <!-- Onglet Services Optionnels -->
            <div class="tab-pane" id="service">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-cogs color-warning"></i>
                        Services Optionnels
                    </h2>
                    <p class="tab-subtitle">Services de cantine, transport et autres prestations avec variants selon les besoins</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryForType('service')">
                        <i class="fas fa-plus"></i>Ajouter Service
                    </button>
                </div>

                @if($categoriesByType['service']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByType['service'] as $category)
                            <div class="category-card">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    @if($category->is_mandatory)
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
                                        </div>
                                    @else
                                        @if($category->configuration_status['is_configured'] ?? false)
                                            <div class="category-amount">
                                                <i class="fas fa-check-circle color-success"></i>
                                                Service configuré
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
                                            <span class="badge warning">Service Global</span>
                                        </div>
                                    @endif
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($category->is_mandatory)
                                            <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Services optionnels">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-cogs"></i>
                        <p>Aucun service optionnel configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryForType('service')">
                            <i class="fas fa-plus"></i>Ajouter le premier service
                        </button>
                    </div>
                @endif
            </div>

            <!-- Onglet Frais Administratifs -->
            <div class="tab-pane" id="administrative">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-file-alt color-accent"></i>
                        Frais Administratifs
                    </h2>
                    <p class="tab-subtitle">Frais de documentation, examens et autres démarches administratives</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryForType('administrative')">
                        <i class="fas fa-plus"></i>Ajouter Frais Administratif
                    </button>
                </div>

                @if($categoriesByType['administrative']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByType['administrative'] as $category)
                            <div class="category-card">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    @if($category->is_mandatory)
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
                                        </div>
                                    @else
                                        @if($category->configuration_status['is_configured'] ?? false)
                                            <div class="category-amount">
                                                <i class="fas fa-check-circle color-success"></i>
                                                Service configuré
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
                                            <span class="badge warning">Service Global</span>
                                        </div>
                                    @endif
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($category->is_mandatory)
                                            <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Services optionnels">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <p>Aucun frais administratif configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryForType('administrative')">
                            <i class="fas fa-plus"></i>Ajouter le premier frais administratif
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
});

// Fonction pour ajouter une catégorie avec un type pré-sélectionné
function addCategoryForType(categoryType) {
    window.location.href = `{{ route('esbtp.frais.create') }}?category_type=${categoryType}`;
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