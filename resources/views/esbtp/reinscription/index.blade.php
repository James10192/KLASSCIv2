@extends('layouts.app')

@section('title', 'Gestion des Réinscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   RÉINSCRIPTIONS — PREMIUM (re-*) — namespace isolé.

   JS contracts (lus par le <script> en bas de fichier — NE PAS rename) :
     - IDs form (#search, #filiere_id, #niveau_id, #statut_reinscription,
       #statut_paiement, #reinscriptionFiltersForm) → lus par loadTabContent()
       et applyFilters()
     - IDs tabs : #myTab, #myTabContent + 7 panes (#passages, #rattrapages,
       #redoublements, #valides, #abandons-annee, #abandons-ecole, #errors)
       + 7 links suffixés -tab → activés par BS4 .tab('show')
     - Modal #yearChangeModal → ouvert par showYearChangeInfo()
     - Classes .reinscription-spinner(.hidden), .content-container,
       .load-more-btn, .load-more-container → manipulées par
       loadTabContent() (show/hide spinner, injection HTML, pagination)
     - data-attrs : data-toggle="tab", data-dismiss="modal" (BS4),
       data-category="X" (lu par loadTabContent pour mapper pane→endpoint)

   API : Bootstrap 4 (jQuery 3 + bootstrap@4.6.2). NE PAS migrer en BS5
   sans refactor coordonné des handlers .tab/.modal.

   Pourquoi !important sur .re-tab.nav-link : BS4 nav-tabs ship des
   selectors plus spécifiques que nos classes ; sans !important le chip
   custom revient au style nav-tab par défaut.
   ═══════════════════════════════════════════════════════════════════════════ */

/* Hero gradient ----------------------------------------------------------- */
.re-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}
.re-hero::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 360px; height: 360px;
    border-radius: 0 18px 0 0;
    background: radial-gradient(circle at top right, rgba(255,255,255,0.08) 0%, transparent 60%);
    pointer-events: none;
}
.re-hero-top {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.re-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}
.re-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    color: #fff;
}
.re-hero-text { flex: 1; min-width: 0; }
.re-hero h1 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.re-hero p {
    color: rgba(255,255,255,.7);
    font-size: .88rem;
    margin: 0.25rem 0 0;
}
.re-hero-meta {
    margin-top: 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.re-hero-pill {
    display: inline-flex; align-items: center; gap: 0.4rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 999px;
    padding: 0.3rem 0.85rem;
    font-size: 0.78rem;
    color: rgba(255,255,255,.92);
    font-weight: 500;
}
.re-pill-link {
    background: none;
    border: none;
    color: #fff;
    text-decoration: underline;
    font-size: 0.75rem;
    padding: 0;
    margin-left: 0.4rem;
    cursor: pointer;
    font-family: inherit;
}
.re-pill-link:hover { color: #fbbf24; }
.re-help-btn {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    color: #fff;
    width: 26px; height: 26px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    margin-left: 0.5rem;
    transition: all 0.2s ease;
    padding: 0;
}
.re-help-btn:hover {
    background: rgba(255,255,255,.22);
    transform: scale(1.05);
}

.re-hero-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.re-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 1.1rem;
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.2s ease;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    white-space: nowrap;
    font-family: inherit;
}
.re-btn--glass {
    background: rgba(255,255,255,.15);
    color: #fff;
    border-color: rgba(255,255,255,.2);
}
.re-btn--glass:hover {
    background: rgba(255,255,255,.25);
    color: #fff;
    text-decoration: none;
}
.re-btn--white {
    background: #fff;
    color: #0453cb;
}
.re-btn--white:hover {
    background: #f8fafc;
    color: #0453cb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    text-decoration: none;
}

/* KPIs glass row dans le hero -------------------------------------------- */
.re-kpis {
    position: relative;
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}
.re-kpi {
    flex: 1 1 calc((100% / 7) - 0.65rem);
    min-width: 130px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: 0.85rem 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    transition: all 0.2s ease;
    cursor: pointer;
    color: #fff;
    text-align: left;
    text-decoration: none;
    font-family: inherit;
}
.re-kpi:hover {
    background: rgba(255,255,255,.16);
    border-color: rgba(255,255,255,.28);
    transform: translateY(-1px);
    color: #fff;
    text-decoration: none;
}
/* KPI dont la tab correspondante n'est pas rendue (count = 0) :
   visuel atténué, non cliquable, accessibilité préservée. */
.re-kpi--empty {
    opacity: 0.5;
    pointer-events: none;
    cursor: default;
}
.re-kpi--empty:hover {
    background: rgba(255,255,255,.1);
    border-color: rgba(255,255,255,.15);
    transform: none;
}
.re-kpi-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    background: rgba(255,255,255,.12);
    color: rgba(255,255,255,.85);
}
.re-kpi-icon.success { background: rgba(52,211,153,.18); color: #34d399; }
.re-kpi-icon.warning { background: rgba(251,191,36,.18); color: #fbbf24; }
.re-kpi-icon.danger  { background: rgba(248,113,113,.18); color: #f87171; }
.re-kpi-icon.neutral { background: rgba(147,197,253,.15); color: #93c5fd; }
.re-kpi-icon.muted   { background: rgba(255,255,255,.08); color: rgba(255,255,255,.6); }

.re-kpi-content {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
    flex: 1;
}
.re-kpi-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}
.re-kpi-label {
    font-size: 0.72rem;
    color: rgba(255,255,255,.7);
    margin-top: 0.18rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Filters card ----------------------------------------------------------- */
.re-filters-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.re-filters-section-title {
    display: flex; align-items: center; gap: 0.5rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.85rem;
}
.re-filters-section-title i {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: rgba(4,83,203,0.08);
    color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.78rem;
}
.re-filters-row {
    display: grid;
    grid-template-columns: 2fr 1.2fr 1.2fr;
    gap: 0.85rem;
    margin-bottom: 0.85rem;
}
.re-filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.re-filter-group label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin: 0;
}
.re-filter-group input,
.re-filter-group select {
    height: 40px;
    padding: 0 0.85rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.88rem;
    background: #fff;
    color: #1e293b;
    transition: all 0.2s ease;
    font-family: inherit;
}
.re-filter-group input:focus,
.re-filter-group select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
}

.re-filters-advanced-toggle {
    background: none;
    border: 1px dashed #cbd5e1;
    color: #0453cb;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 0.85rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-family: inherit;
}
.re-filters-advanced-toggle:hover {
    background: rgba(4,83,203,0.05);
    border-color: #0453cb;
}
.re-filters-advanced-toggle i { transition: transform 0.2s ease; }
.re-filters-advanced-toggle.is-open i { transform: rotate(180deg); }

.re-filters-advanced {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.85rem;
    margin-bottom: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px dashed #e2e8f0;
}

.re-filters-actions {
    display: flex;
    gap: 0.65rem;
    align-items: center;
    flex-wrap: wrap;
}
.re-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.25rem;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    text-decoration: none;
    font-family: inherit;
}
.re-filter-btn--primary {
    background: linear-gradient(135deg, #0453cb, #1b64d4);
    color: #fff;
    box-shadow: 0 2px 8px rgba(4,83,203,0.25);
}
.re-filter-btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,0.35);
    color: #fff;
    text-decoration: none;
}
.re-filter-btn--ghost {
    background: #fff;
    color: #64748b;
    border-color: #e2e8f0;
}
.re-filter-btn--ghost:hover {
    background: #f8fafc;
    color: #1e293b;
    text-decoration: none;
}
.re-filters-meta {
    margin-left: auto;
    font-size: 0.78rem;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

/* Tabs (chips style) ----------------------------------------------------- */
.re-content-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.re-tabs-wrapper {
    border-bottom: 1px solid #f1f5f9;
    padding: 1.1rem 1.5rem 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.re-tabs.nav-tabs {
    border: none !important;
    display: flex !important;
    gap: 0.5rem !important;
    flex-wrap: nowrap !important;
    margin-bottom: 0 !important;
    min-width: max-content;
}
.re-tabs .nav-item { border: none !important; }
.re-tab.nav-link {
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    padding: 0.55rem 0.95rem !important;
    color: #64748b !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    background: #fff !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: all 0.2s ease !important;
    white-space: nowrap !important;
    margin-bottom: 1.1rem !important;
}
.re-tab.nav-link:hover {
    border-color: #0453cb !important;
    color: #0453cb !important;
    background: rgba(4,83,203,0.04) !important;
}
.re-tab.nav-link.active {
    background: linear-gradient(135deg, #0453cb, #1b64d4) !important;
    border-color: #0453cb !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(4,83,203,0.2) !important;
}
.re-tab-icon { font-size: 0.85rem; line-height: 1; }
.re-tab-icon.success { color: #10b981; }
.re-tab-icon.warning { color: #f59e0b; }
.re-tab-icon.danger  { color: #dc2626; }
.re-tab-icon.neutral { color: #3b82f6; }
.re-tab-icon.muted   { color: #94a3b8; }
.re-tab.nav-link.active .re-tab-icon { color: rgba(255,255,255,0.92); }
.re-tab-count {
    background: #f1f5f9;
    color: #64748b;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    min-width: 24px;
    text-align: center;
    line-height: 1.4;
}
.re-tab.nav-link.active .re-tab-count {
    background: rgba(255,255,255,0.25) !important;
    color: #fff !important;
}

.re-tab-body {
    padding: 1.5rem;
}

/* Spinner (classes JS-dépendantes — préservées) -------------------------- */
.reinscription-spinner {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-height: 280px !important;
    text-align: center !important;
    padding: 40px !important;
}
.reinscription-spinner.hidden { display: none !important; }
.reinscription-spinner-icon {
    display: block !important;
    margin-bottom: 16px !important;
    text-align: center !important;
}
.reinscription-spinner-icon i {
    font-size: 38px !important;
    color: #0453cb !important;
    animation: reinscription-spin 1s linear infinite !important;
    transform-origin: center center !important;
}
.reinscription-spinner-text {
    display: block !important;
    color: #64748b !important;
    font-size: 0.88rem !important;
    margin: 0 !important;
    text-align: center !important;
}
@keyframes reinscription-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Container AJAX (classes JS-dépendantes — préservées) ------------------- */
.content-container {
    width: 100% !important;
    min-height: 200px;
}
.tab-pane { width: 100% !important; }
.tab-content { width: 100% !important; }
.content-container .table-responsive {
    width: 100% !important;
    margin: 0;
    border-radius: 10px;
    overflow: hidden;
}
.content-container .table-responsive table {
    width: 100% !important;
    margin: 0;
}

/* Alerts session ---------------------------------------------------------- */
.re-alert {
    border-radius: 12px;
    padding: 0.85rem 1.1rem;
    margin-bottom: 1.1rem;
    border: 1px solid transparent;
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    font-size: 0.88rem;
}
.re-alert--success {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.2);
    color: #047857;
}
.re-alert--danger {
    background: rgba(239,68,68,0.06);
    border-color: rgba(239,68,68,0.18);
    color: #991b1b;
}
.re-alert ul { margin: 0; padding-left: 1.2rem; }

/* Help dropdown (Alpine) -------------------------------------------------- */
.re-help-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    z-index: 1050;
    background: #fff;
    color: #1e293b;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(15,23,42,0.18);
    border: 1px solid #e2e8f0;
    min-width: 340px;
    max-width: min(400px, calc(100vw - 2rem));
    overflow: hidden;
    text-align: left;
}
.re-help-dropdown__header {
    background: linear-gradient(135deg, #0453cb, #1b64d4);
    color: #fff;
    padding: 0.7rem 1rem;
    font-weight: 600;
    font-size: 0.88rem;
}
.re-help-dropdown__body {
    padding: 0.85rem 1.05rem;
    font-size: 0.82rem;
    line-height: 1.55;
}
.re-help-dropdown__body p { margin: 0 0 0.5rem; }
.re-help-dropdown__body ul { margin: 0; padding-left: 1.1rem; }
.re-help-dropdown__body strong { color: #0453cb; }

/* Responsive ------------------------------------------------------------- */
@media (max-width: 1280px) {
    .re-kpi { flex: 1 1 calc((100% / 4) - 0.65rem); }
}
@media (max-width: 992px) {
    .re-hero { padding: 1.25rem 1.5rem; }
    .re-kpi { flex: 1 1 calc(50% - 0.5rem); min-width: 0; }
    .re-filters-row { grid-template-columns: 1fr 1fr; }
    .re-filters-advanced { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .re-hero { padding: 1rem 1.1rem; border-radius: 14px; }
    .re-hero h1 { font-size: 1.2rem; }
    .re-hero-top { flex-direction: column; }
    .re-hero-actions { width: 100%; }
    .re-btn { flex: 1; justify-content: center; }
    .re-filters-row { grid-template-columns: 1fr; }
    .re-tab.nav-link { padding: 0.45rem 0.75rem !important; font-size: 0.78rem !important; }
    .re-filters-meta { width: 100%; margin-left: 0; margin-top: 0.5rem; }
    /* Mobile : popover aligné à droite pour éviter overflow viewport */
    .re-help-dropdown { left: auto; right: 0; min-width: min(320px, calc(100vw - 2rem)); }
}

[x-cloak] { display: none !important; }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── HERO PREMIUM (re-hero) ─────────────────────────────────── --}}
        <div class="re-hero">
            <div class="re-hero-top">
                <div class="re-hero-left">
                    <div class="re-hero-icon">
                        <i class="fas fa-redo"></i>
                    </div>
                    <div class="re-hero-text">
                        <h1>
                            Réinscriptions
                            <span x-data="{open: false}" @click.outside="open = false" style="position:relative; display:inline-block;">
                                <button type="button"
                                        class="re-help-btn"
                                        @click="open = !open"
                                        :aria-expanded="open ? 'true' : 'false'"
                                        title="Comment fonctionne le système de réinscription ?"
                                        aria-label="Aide sur le système de réinscription">
                                    <i class="fas fa-question"></i>
                                </button>
                                <div class="re-help-dropdown" x-show="open" x-cloak x-transition>
                                    <div class="re-help-dropdown__header">
                                        <i class="fas fa-info-circle me-1"></i> Nouveau système de réinscription
                                    </div>
                                    <div class="re-help-dropdown__body">
                                        <p><strong>Principe :</strong> chaque réinscription crée une <strong>nouvelle inscription</strong> avec recalcul complet des frais selon la nouvelle classe.</p>
                                        <ul>
                                            <li><strong>Condition :</strong> étudiant entièrement soldé (100&nbsp;%)</li>
                                            <li><strong>Historique :</strong> anciennes inscriptions visibles dans le profil</li>
                                            <li><strong>Frais :</strong> nouveaux frais optionnels sélectionnables</li>
                                            <li><strong>Facture :</strong> générée automatiquement</li>
                                        </ul>
                                    </div>
                                </div>
                            </span>
                        </h1>
                        <p>Gestion des passages, rattrapages et redoublements</p>
                        <div class="re-hero-meta">
                            <span class="re-hero-pill">
                                <i class="fas fa-calendar-alt"></i>
                                Année {{ $anneeAcademique }}
                                <button type="button" class="re-pill-link" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                                    Changer
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="re-hero-actions">
                    <a href="{{ route('esbtp.reinscription.regles.index') }}" class="re-btn re-btn--glass">
                        <i class="fas fa-cogs"></i> Règles Académiques
                    </a>
                    <button type="button" class="re-btn re-btn--white" onclick="exportResults()">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>
            </div>

            {{-- ── KPIs glass row : cliquables si la tab existe (count > 0).
                 Les 4 dernières catégories ont leur tab gated par @if(count > 0)
                 plus bas → on désactive visuellement le KPI pour éviter le clic
                 silencieux qui ne ferait rien. --}}
            @php
                $kpiCards = [
                    ['key' => 'passages',       'tab' => 'passages-tab',       'icon' => 'success', 'fa' => 'fa-arrow-up',           'label' => 'Passages',       'always' => true],
                    ['key' => 'rattrapages',    'tab' => 'rattrapages-tab',    'icon' => 'warning', 'fa' => 'fa-exclamation-triangle', 'label' => 'Rattrapages',    'always' => true],
                    ['key' => 'redoublements',  'tab' => 'redoublements-tab',  'icon' => 'danger',  'fa' => 'fa-redo',               'label' => 'Redoublements',  'always' => true],
                    ['key' => 'abandons_annee', 'tab' => 'abandons-annee-tab', 'icon' => 'danger',  'fa' => 'fa-user-slash',         'label' => 'Abandons année', 'always' => false],
                    ['key' => 'abandons_ecole', 'tab' => 'abandons-ecole-tab', 'icon' => 'muted',   'fa' => 'fa-graduation-cap',     'label' => 'Abandons école', 'always' => false],
                    ['key' => 'valides',        'tab' => 'valides-tab',        'icon' => 'success', 'fa' => 'fa-check-double',       'label' => 'Validés',        'always' => false],
                    ['key' => 'errors',         'tab' => 'errors-tab',         'icon' => 'neutral', 'fa' => 'fa-user-clock',         'label' => 'Non validés',    'always' => false],
                ];
            @endphp
            <div class="re-kpis">
                @foreach($kpiCards as $card)
                    @php
                        $count = $statistiques[$card['key']] ?? 0;
                        $tabExists = $card['always'] || $count > 0;
                        $emptyClass = $tabExists ? '' : 're-kpi--empty';
                    @endphp
                    <a class="re-kpi {{ $emptyClass }}"
                       href="#{{ str_replace('_', '-', $card['key']) }}"
                       @if($tabExists) data-rekpi-target="{{ $card['tab'] }}" @else aria-disabled="true" tabindex="-1" @endif>
                        <div class="re-kpi-icon {{ $card['icon'] }}"><i class="fas {{ $card['fa'] }}"></i></div>
                        <div class="re-kpi-content">
                            <div class="re-kpi-value">{{ $count }}</div>
                            <div class="re-kpi-label">{{ $card['label'] }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ── Alertes session ────────────────────────────────────────── --}}
        @php
            $errorsList = array_filter($errors->all(), fn($error) => trim((string) $error) !== '');
        @endphp
        @if (!empty($errorsList))
            <div class="re-alert re-alert--danger">
                <i class="fas fa-exclamation-circle mt-1"></i>
                <ul>
                    @foreach ($errorsList as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('success'))
            <div class="re-alert re-alert--success">
                <i class="fas fa-check-circle mt-1"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- ── Filtres hybrides (3 visibles + 2 collapsibles Alpine) ──── --}}
        @php
            // Filtres avancés ouverts par défaut si l'utilisateur arrive avec un
            // filtre actif sur Niveau ou Paiement (sinon il ne les verrait pas).
            $advancedFiltersOpen = (bool) (request('niveau_id') || request('statut_paiement'));
        @endphp
        <div class="re-filters-card"
             data-advanced-default='@json($advancedFiltersOpen)'
             x-data="{ advanced: JSON.parse($el.dataset.advancedDefault || 'false') }">
            <div class="re-filters-section-title">
                <i class="fas fa-filter"></i>
                Filtres de réinscription
            </div>
            <form method="GET" action="{{ route('esbtp.reinscription.index') }}" id="reinscriptionFiltersForm" onsubmit="return applyFilters()">
                {{-- ID #annee_academique conservé sans `name` : aucun JS du fichier
                     ne le lit, mais on garde l'ID au cas où une partie externe
                     (debug helpers) y ferait référence. Sans `name` → pas
                     submitté avec le form GET → ne pollue pas l'URL. --}}
                <input type="hidden" id="annee_academique" value="{{ $anneeAcademique }}">

                {{-- Ligne principale : 3 visibles (Recherche, Filière, Statut) --}}
                <div class="re-filters-row">
                    <div class="re-filter-group">
                        <label for="search">Recherche</label>
                        <input type="text" name="search" id="search"
                               value="{{ request('search') }}"
                               placeholder="Nom, matricule…">
                    </div>
                    <div class="re-filter-group">
                        <label for="filiere_id">Filière</label>
                        <select name="filiere_id" id="filiere_id">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                    {{ $filiere->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="re-filter-group">
                        <label for="statut_reinscription">Statut</label>
                        <select name="statut_reinscription" id="statut_reinscription">
                            <option value="">Tous les statuts</option>
                            <option value="passage" {{ request('statut_reinscription') == 'passage' ? 'selected' : '' }}>Passage</option>
                            <option value="rattrapage" {{ request('statut_reinscription') == 'rattrapage' ? 'selected' : '' }}>Rattrapage</option>
                            <option value="redoublement" {{ request('statut_reinscription') == 'redoublement' ? 'selected' : '' }}>Redoublement</option>
                            <option value="abandon" {{ request('statut_reinscription') == 'abandon' ? 'selected' : '' }}>Abandon</option>
                            <option value="valide" {{ request('statut_reinscription') == 'valide' ? 'selected' : '' }}>Validé</option>
                        </select>
                    </div>
                </div>

                {{-- Toggle filtres avancés --}}
                <button type="button"
                        class="re-filters-advanced-toggle"
                        :class="{'is-open': advanced}"
                        :aria-expanded="advanced ? 'true' : 'false'"
                        aria-controls="re-filters-advanced-section"
                        @click="advanced = !advanced">
                    <span x-show="!advanced"><i class="fas fa-chevron-down"></i> Plus de filtres</span>
                    <span x-show="advanced" x-cloak><i class="fas fa-chevron-up"></i> Moins de filtres</span>
                </button>

                {{-- Filtres avancés collapsibles --}}
                <div id="re-filters-advanced-section" class="re-filters-advanced" x-show="advanced" x-cloak x-transition>
                    <div class="re-filter-group">
                        <label for="niveau_id">Niveau</label>
                        <select name="niveau_id" id="niveau_id">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="re-filter-group">
                        <label for="statut_paiement">Paiement</label>
                        <select name="statut_paiement" id="statut_paiement">
                            <option value="">Tous</option>
                            <option value="solde" {{ request('statut_paiement') == 'solde' ? 'selected' : '' }}>Soldé</option>
                            <option value="impaye" {{ request('statut_paiement') == 'impaye' ? 'selected' : '' }}>Impayé</option>
                        </select>
                    </div>
                </div>

                {{-- Actions filtres --}}
                <div class="re-filters-actions">
                    <button type="submit" class="re-filter-btn re-filter-btn--primary">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="{{ route('esbtp.reinscription.index') }}" class="re-filter-btn re-filter-btn--ghost">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                    <span class="re-filters-meta">
                        <i class="fas fa-calendar-alt"></i> {{ $anneeAcademique }}
                    </span>
                </div>
            </form>
        </div>

        {{-- ── Card content (tabs chips + tab-content) ────────────────── --}}
        <div class="re-content-card">
            <div class="re-tabs-wrapper">
                <ul class="re-tabs nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="re-tab nav-link" id="passages-tab" data-toggle="tab" href="#passages" role="tab">
                            <i class="fas fa-arrow-up re-tab-icon success"></i>
                            <span>Passages</span>
                            <span class="re-tab-count">{{ $statistiques['passages'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="re-tab nav-link" id="rattrapages-tab" data-toggle="tab" href="#rattrapages" role="tab">
                            <i class="fas fa-exclamation-triangle re-tab-icon warning"></i>
                            <span>Rattrapages</span>
                            <span class="re-tab-count">{{ $statistiques['rattrapages'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="re-tab nav-link" id="redoublements-tab" data-toggle="tab" href="#redoublements" role="tab">
                            <i class="fas fa-redo re-tab-icon danger"></i>
                            <span>Redoublements</span>
                            <span class="re-tab-count">{{ $statistiques['redoublements'] ?? 0 }}</span>
                        </a>
                    </li>
                    @if(($statistiques['valides'] ?? 0) > 0)
                    <li class="nav-item">
                        <a class="re-tab nav-link" id="valides-tab" data-toggle="tab" href="#valides" role="tab">
                            <i class="fas fa-check-double re-tab-icon success"></i>
                            <span>Validés</span>
                            <span class="re-tab-count">{{ $statistiques['valides'] ?? 0 }}</span>
                        </a>
                    </li>
                    @endif
                    @if(($statistiques['abandons_annee'] ?? 0) > 0)
                    <li class="nav-item">
                        <a class="re-tab nav-link" id="abandons-annee-tab" data-toggle="tab" href="#abandons-annee" role="tab">
                            <i class="fas fa-user-slash re-tab-icon danger"></i>
                            <span>Abandons Année</span>
                            <span class="re-tab-count">{{ $statistiques['abandons_annee'] ?? 0 }}</span>
                        </a>
                    </li>
                    @endif
                    @if(($statistiques['abandons_ecole'] ?? 0) > 0)
                    <li class="nav-item">
                        <a class="re-tab nav-link" id="abandons-ecole-tab" data-toggle="tab" href="#abandons-ecole" role="tab">
                            <i class="fas fa-graduation-cap re-tab-icon muted"></i>
                            <span>Abandons École</span>
                            <span class="re-tab-count">{{ $statistiques['abandons_ecole'] ?? 0 }}</span>
                        </a>
                    </li>
                    @endif
                    @if(($statistiques['errors'] ?? 0) > 0)
                    <li class="nav-item">
                        <a class="re-tab nav-link" id="errors-tab" data-toggle="tab" href="#errors" role="tab">
                            <i class="fas fa-user-clock re-tab-icon neutral"></i>
                            <span>Non validés</span>
                            <span class="re-tab-count">{{ $statistiques['errors'] ?? 0 }}</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="re-tab-body">
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade" id="passages" role="tabpanel" data-category="passages">
                        <div class="reinscription-spinner">
                            <div class="reinscription-spinner-icon"><i class="fas fa-spinner"></i></div>
                            <div class="reinscription-spinner-text">Chargement des passages…</div>
                        </div>
                        <div class="content-container" style="display: none;"></div>
                    </div>
                    <div class="tab-pane fade" id="rattrapages" role="tabpanel" data-category="rattrapages">
                        <div class="reinscription-spinner">
                            <div class="reinscription-spinner-icon"><i class="fas fa-spinner"></i></div>
                            <div class="reinscription-spinner-text">Chargement des rattrapages…</div>
                        </div>
                        <div class="content-container" style="display: none;"></div>
                    </div>
                    <div class="tab-pane fade" id="redoublements" role="tabpanel" data-category="redoublements">
                        <div class="reinscription-spinner">
                            <div class="reinscription-spinner-icon"><i class="fas fa-spinner"></i></div>
                            <div class="reinscription-spinner-text">Chargement des redoublements…</div>
                        </div>
                        <div class="content-container" style="display: none;"></div>
                    </div>
                    @if(($statistiques['valides'] ?? 0) > 0)
                    <div class="tab-pane fade" id="valides" role="tabpanel" data-category="valides">
                        <div class="reinscription-spinner">
                            <div class="reinscription-spinner-icon"><i class="fas fa-spinner"></i></div>
                            <div class="reinscription-spinner-text">Chargement des validés…</div>
                        </div>
                        <div class="content-container" style="display: none;"></div>
                    </div>
                    @endif
                    @if(($statistiques['abandons_annee'] ?? 0) > 0)
                    <div class="tab-pane fade" id="abandons-annee" role="tabpanel" data-category="abandons_annee">
                        <div class="reinscription-spinner">
                            <div class="reinscription-spinner-icon"><i class="fas fa-spinner"></i></div>
                            <div class="reinscription-spinner-text">Chargement des abandons année…</div>
                        </div>
                        <div class="content-container" style="display: none;"></div>
                    </div>
                    @endif
                    @if(($statistiques['abandons_ecole'] ?? 0) > 0)
                    <div class="tab-pane fade" id="abandons-ecole" role="tabpanel" data-category="abandons_ecole">
                        <div class="reinscription-spinner">
                            <div class="reinscription-spinner-icon"><i class="fas fa-spinner"></i></div>
                            <div class="reinscription-spinner-text">Chargement des abandons école…</div>
                        </div>
                        <div class="content-container" style="display: none;"></div>
                    </div>
                    @endif
                    @if(($statistiques['errors'] ?? 0) > 0)
                    <div class="tab-pane fade" id="errors" role="tabpanel" data-category="errors">
                        <div class="reinscription-spinner">
                            <div class="reinscription-spinner-icon"><i class="fas fa-spinner"></i></div>
                            <div class="reinscription-spinner-text">Chargement des non validés…</div>
                        </div>
                        <div class="content-container" style="display: none;"></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour informations changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changement d'année académique</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour changer d'année académique :</strong></p>
                <ol>
                    <li>Accédez au menu <strong>"Gestion" → "Années Universitaires"</strong></li>
                    <li>Activez l'année souhaitée en cliquant sur <strong>"Définir comme courante"</strong></li>
                    <li>Revenez sur cette page pour voir les données de la nouvelle année</li>
                </ol>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    Seule l'année marquée comme "courante" est affichée dans les réinscriptions.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-primary">
                    Gérer les Années
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fallback si debug-helper n'est pas encore chargé
window.debugLog = window.debugLog || function () {};
window.debugError = window.debugError || function () {};

// LOGS IMMÉDIATS AVANT CHARGEMENT JQUERY (PAGE REINSCRIPTIONS)
debugLog('🟢 DEBUG REINSCRIPTIONS: Script debug DÉBUT');
debugLog('🟢 DEBUG REINSCRIPTIONS: jQuery disponible avant chargement?', typeof $ !== 'undefined');
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// LOGS APRÈS JQUERY (PAGE REINSCRIPTIONS)
debugLog('🟢 DEBUG REINSCRIPTIONS: jQuery chargé?', typeof $ !== 'undefined');
debugLog('🟢 DEBUG REINSCRIPTIONS: jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'N/A');
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// LOGS APRÈS BOOTSTRAP (PAGE REINSCRIPTIONS)
debugLog('🟢 DEBUG REINSCRIPTIONS: Bootstrap 4.6.2 chargé?', typeof $.fn.modal !== 'undefined');
debugLog('🟢 DEBUG REINSCRIPTIONS: Bootstrap version:', typeof bootstrap !== 'undefined' ? bootstrap : 'N/A');

$(document).ready(function() {
    debugLog('🟢 DEBUG REINSCRIPTIONS: Document ready');
    
    // Debug du modal "Changer d'année"
    const yearChangeModal = $('#yearChangeModal');
    debugLog('🟢 DEBUG REINSCRIPTIONS: Modal changement année trouvé?', yearChangeModal.length > 0);
    
    const yearChangeButton = $('button[onclick="showYearChangeInfo()"]');
    debugLog('🟢 DEBUG REINSCRIPTIONS: Bouton changement année trouvé?', yearChangeButton.length > 0);
    
    // Debug du bouton "Changer d'année" existant
    const changeYearBtn = $('button[onclick="showYearChangeInfo()"]');
    debugLog('🟢 DEBUG REINSCRIPTIONS: Bouton "Changer d\'année" trouvé?', changeYearBtn.length > 0);
    if (changeYearBtn.length > 0) {
        debugLog('🟢 DEBUG REINSCRIPTIONS: Texte du bouton:', changeYearBtn.text().trim());
        debugLog('🟢 DEBUG REINSCRIPTIONS: Attribut onclick:', changeYearBtn.attr('onclick'));
    }
    
    // Intercepter les clics sur le bouton "Changer d'année" 
    changeYearBtn.on('click', function() {
        debugLog('🖱️ DEBUG REINSCRIPTIONS: Clic détecté sur bouton "Changer d\'année"');
        debugLog('🎯 DEBUG REINSCRIPTIONS: Tentative d\'ouverture du modal #yearChangeModal');
    });
    
    // Écouter les événements du modal
    $('#yearChangeModal').on('show.bs.modal', function (e) {
        debugLog('🎭 DEBUG REINSCRIPTIONS: Événement show.bs.modal déclenché');
    });
    
    $('#yearChangeModal').on('shown.bs.modal', function (e) {
        debugLog('✅ DEBUG REINSCRIPTIONS: Modal affiché avec succès');
    });
});
</script>

<script>
// Variables pour le système de lazy loading
let loadedTabs = {};
let currentPage = {};

$(document).ready(function() {
    debugLog("🚀 DEBUG: Page ready, initialisation du lazy loading");
    
    // CORRECTION: Charger automatiquement l'onglet avec le plus d'étudiants
    const statistiques = {
        passages: {{ $statistiques['passages'] ?? 0 }},
        redoublements: {{ $statistiques['redoublements'] ?? 0 }},
        rattrapages: {{ $statistiques['rattrapages'] ?? 0 }},
        valides: {{ $statistiques['valides'] ?? 0 }},
        abandons_annee: {{ $statistiques['abandons_annee'] ?? 0 }},
        abandons_ecole: {{ $statistiques['abandons_ecole'] ?? 0 }},
        errors: {{ $statistiques['errors'] ?? 0 }}
    };
    
    debugLog("📊 DEBUG: Statistiques reçues:", statistiques);
    
    // Trouver la catégorie avec le plus d'étudiants
    let maxCategory = 'passages';
    let maxCount = 0;
    for (const [category, count] of Object.entries(statistiques)) {
        debugLog(`📈 DEBUG: Catégorie "${category}": ${count} étudiants`);
        if (count > maxCount) {
            maxCount = count;
            maxCategory = category;
        }
    }
    
    debugLog(`🎯 DEBUG: Catégorie principale détectée: "${maxCategory}" avec ${maxCount} étudiants`);
    
    // Charger cette catégorie au démarrage
    if (maxCount > 0) {
        debugLog(`🔄 DEBUG: Activation de l'onglet "${maxCategory}"`);
        
        // Activer l'onglet correspondant
        $('a[data-toggle="tab"]').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        const tabLink = $(`a[href="#${maxCategory}"]`);
        const tabPane = $(`#${maxCategory}`);
        
        debugLog(`🔍 DEBUG: Tab link trouvé:`, tabLink.length > 0);
        debugLog(`🔍 DEBUG: Tab pane trouvé:`, tabPane.length > 0);
        
        tabLink.addClass('active');
        tabPane.addClass('show active');
        
        // Cacher le spinner de cette catégorie car elle va être chargée
        const maxTabPane = $(`#${maxCategory}`);
        const maxSpinner = maxTabPane.find('.reinscription-spinner');
        maxSpinner.addClass('hidden');
        
        debugLog(`📞 DEBUG: Appel loadTabContent("${maxCategory}")`);
        loadTabContent(maxCategory);
        
        // Marquer cette catégorie comme chargée
        loadedTabs[maxCategory] = true;
    } else {
        debugLog("⚠️ DEBUG: Aucune catégorie avec des étudiants trouvée");
    }
    
    // Gérer les clics sur les onglets - Approche multiple pour plus de robustesse
    debugLog(`🔍 DEBUG: Configuration des gestionnaires d'onglets`);
    
    // Méthode 1: Bootstrap shown.bs.tab
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        debugLog(`🔗 DEBUG: Bootstrap shown.bs.tab détecté`);
        const targetTab = $(e.target).attr('href').substring(1);
        debugLog(`🎯 DEBUG: targetTab: "${targetTab}"`);
        
        const tabPane = $('#' + targetTab);
        const category = tabPane.data('category');
        debugLog(`📂 DEBUG: category extraite: "${category}"`);
        debugLog(`💾 DEBUG: loadedTabs status:`, loadedTabs);
        debugLog(`❓ DEBUG: "${category}" déjà chargé?`, loadedTabs[category] || false);
        
        if (category) {
            if (loadedTabs[category]) {
                debugLog(`✅ DEBUG: Catégorie "${category}" déjà en cache, pas de rechargement`);
            } else {
                debugLog(`🚀 DEBUG: Chargement Bootstrap de la catégorie "${category}"`);
                loadTabContent(category);
            }
        }
    });
    
    // Méthode 2: Clic direct comme fallback
    $('a[data-toggle="tab"]').on('click', function (e) {
        debugLog(`👆 DEBUG: Clic direct sur onglet détecté`);
        const targetTab = $(this).attr('href').substring(1);
        debugLog(`🎯 DEBUG: targetTab: "${targetTab}"`);
        
        // Attendre un peu que l'onglet soit activé
        setTimeout(() => {
            const tabPane = $('#' + targetTab);
            const category = tabPane.data('category');
            debugLog(`📂 DEBUG: category extraite après timeout: "${category}"`);
            debugLog(`💾 DEBUG: loadedTabs status:`, loadedTabs);
            debugLog(`❓ DEBUG: "${category}" déjà chargé?`, loadedTabs[category] || false);
            
            if (category) {
                if (loadedTabs[category]) {
                    debugLog(`✅ DEBUG: Catégorie "${category}" déjà en cache, pas de rechargement`);
                } else {
                    debugLog(`🚀 DEBUG: Chargement par clic de la catégorie "${category}"`);
                    loadTabContent(category);
                }
            }
        }, 100);
    });
});

// Fonction pour forcer le rechargement d'un onglet (efface le cache)
function refreshTab(category) {
    debugLog(`🔄 DEBUG: Forcer le rechargement de "${category}"`);
    loadedTabs[category] = false;
    
    // Remettre le spinner et cacher le contenu
    const tabPane = $(`[data-category="${category}"]`);
    const loadingSpinner = tabPane.find('.reinscription-spinner');
    const contentContainer = tabPane.find('.content-container');
    
    loadingSpinner.removeClass('hidden');
    contentContainer.hide().html('');
    
    // Recharger
    loadTabContent(category);
}

// Fonction principale de chargement lazy
function loadTabContent(category, page = 1) {
    debugLog(`🔥 DEBUG: loadTabContent("${category}", ${page})`);
    
    const tabPane = $(`[data-category="${category}"]`);
    const loadingSpinner = tabPane.find('.reinscription-spinner');
    const contentContainer = tabPane.find('.content-container');
    
    debugLog(`🔍 DEBUG: Éléments trouvés:`);
    debugLog(`  - tabPane:`, tabPane.length > 0, tabPane);
    debugLog(`  - loadingSpinner:`, loadingSpinner.length > 0, loadingSpinner);
    debugLog(`  - contentContainer:`, contentContainer.length > 0, contentContainer);
    
    // DEBUG ULTRA: Vérifier les états avant/après
    debugLog(`🔍 DEBUG ÉTATS AVANT:`);
    debugLog(`  - spinner visible:`, loadingSpinner.is(':visible'));
    debugLog(`  - container visible:`, contentContainer.is(':visible'));
    debugLog(`  - spinner display:`, loadingSpinner.css('display'));
    debugLog(`  - container display:`, contentContainer.css('display'));
    
    // Afficher le spinner si c'est la première page
    if (page === 1) {
        debugLog(`🔄 DEBUG: Affichage du spinner pour page 1`);
        loadingSpinner.removeClass('hidden');
        contentContainer.hide();
    }
    
    const ajaxUrl = `{{ route('esbtp.reinscription.load-category', ':category') }}`.replace(':category', category);
    debugLog(`📡 DEBUG: URL AJAX: ${ajaxUrl}`);
    
    // Récupérer les paramètres de filtres depuis le formulaire
    const filterParams = {};
    const searchValue = $('#search').val();
    const filiereValue = $('#filiere_id').val();
    const niveauValue = $('#niveau_id').val();
    const statutReinscriptionValue = $('#statut_reinscription').val();
    const statutPaiementValue = $('#statut_paiement').val();
    
    if (searchValue) filterParams.search = searchValue;
    if (filiereValue) filterParams.filiere_id = filiereValue;
    if (niveauValue) filterParams.niveau_id = niveauValue;
    if (statutReinscriptionValue) filterParams.statut_reinscription = statutReinscriptionValue;
    if (statutPaiementValue) filterParams.statut_paiement = statutPaiementValue;
    
    debugLog(`🔍 DEBUG: Paramètres de filtres:`, filterParams);
    
    // Faire la requête AJAX
    $.ajax({
        url: ajaxUrl,
        method: 'GET',
        data: {
            page: page,
            per_page: 50,
            ...filterParams // Inclure les paramètres de filtres
        },
        success: function(response) {
            debugLog(`✅ DEBUG: AJAX Success pour "${category}", page ${page}`);
            debugLog(`📊 DEBUG: Response total:`, response.total);
            debugLog(`📄 DEBUG: Response HTML length:`, response.html ? response.html.length : 0);
            debugLog(`🔄 DEBUG: Response has_more:`, response.has_more);
            
            if (page === 1) {
                debugLog(`🎯 DEBUG: Traitement première page`);
                // Première page : remplacer le contenu
                debugLog(`🚫 DEBUG: Masquage du spinner`);
                debugLog(`🔍 DEBUG AVANT addClass('hidden'):`, loadingSpinner.hasClass('hidden'));
                loadingSpinner.addClass('hidden');
                debugLog(`🔍 DEBUG APRÈS addClass('hidden'):`, loadingSpinner.hasClass('hidden'));
                
                // CORRECTION: Gérer les catégories vides
                if (response.total === 0) {
                    debugLog(`⚠️ DEBUG: Catégorie vide, affichage message`);
                    const emptyHtml = `
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-info-circle fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">Aucun étudiant dans cette catégorie</h5>
                            <p class="text-muted">Tous les étudiants ont été traités ou il n'y a pas de données pour cette période.</p>
                        </div>
                    `;
                    contentContainer.html(emptyHtml);
                } else {
                    debugLog(`📝 DEBUG: Injection du HTML (${response.html.length} chars)`);
                    contentContainer.html(response.html);
                }
                
                debugLog(`👁️ DEBUG: Affichage du contenu`);
                debugLog(`🔍 DEBUG AVANT show():`, contentContainer.is(':visible'));
                
                // FORCE l'affichage avec plusieurs méthodes
                contentContainer.show();
                contentContainer.css('display', 'block');
                contentContainer.css('width', '100%');
                contentContainer.css('visibility', 'visible');
                
                debugLog(`🔍 DEBUG APRÈS show():`, contentContainer.is(':visible'));
                debugLog(`🔍 DEBUG CSS display:`, contentContainer.css('display'));
                debugLog(`🔍 DEBUG largeur:`, contentContainer.css('width'));
                
                // Vérifier que le contenu a bien été injecté
                const injectedContent = contentContainer.html();
                debugLog(`📝 DEBUG contenu injecté (taille):`, injectedContent.length);
                debugLog(`🎨 DEBUG contient table-responsive:`, injectedContent.includes('table-responsive'));
                
                // Forcer l'affichage de tous les éléments
                const tableResponsive = contentContainer.find('.table-responsive');
                const table = contentContainer.find('table');
                const thead = contentContainer.find('thead');
                const tbody = contentContainer.find('tbody');
                
                debugLog(`📋 DEBUG éléments trouvés:`);
                debugLog(`  - table-responsive: ${tableResponsive.length}`);
                debugLog(`  - table: ${table.length}`);
                debugLog(`  - thead: ${thead.length}`);
                debugLog(`  - tbody: ${tbody.length}`);
                
                // Forcer l'affichage de chaque élément
                if (tableResponsive.length > 0) {
                    debugLog(`🔧 Force affichage table-responsive`);
                    tableResponsive.css({
                        'display': 'block !important',
                        'width': '100% !important',
                        'visibility': 'visible !important'
                    });
                }
                
                if (table.length > 0) {
                    debugLog(`🔧 Force affichage table`);
                    table.css({
                        'display': 'table !important',
                        'width': '100% !important',
                        'visibility': 'visible !important'
                    });
                }
                
                if (thead.length > 0) {
                    debugLog(`🔧 Force affichage thead`);
                    thead.css({
                        'display': 'table-header-group !important',
                        'visibility': 'visible !important'
                    });
                }
                
                if (tbody.length > 0) {
                    debugLog(`🔧 Force affichage tbody`);
                    tbody.css({
                        'display': 'table-row-group !important',
                        'visibility': 'visible !important'
                    });
                }
                loadedTabs[category] = true;
                currentPage[category] = 1;
                debugLog(`💾 DEBUG: Catégorie "${category}" mise en cache pour éviter les rechargements`);
            } else {
                debugLog(`➕ DEBUG: Ajout page ${page}`);
                // Pages suivantes : ajouter les lignes au tbody existant
                const existingTable = contentContainer.find('table tbody');
                if (existingTable.length > 0) {
                    debugLog(`📝 DEBUG: Ajout des lignes au tbody existant`);
                    existingTable.append(response.html);
                } else {
                    debugLog(`⚠️ DEBUG: Pas de tbody trouvé, ajout classique`);
                    contentContainer.append(response.html);
                }
            }
            
            // Gérer le bouton "Charger plus"
            const loadMoreBtn = contentContainer.find('.load-more-btn');
            loadMoreBtn.remove(); // Supprimer l'ancien bouton
            
            if (response.has_more) {
                const nextPage = page + 1;
                const btnHtml = `
                    <div class="text-center mt-4 load-more-container">
                        <button class="btn-acasi secondary load-more-btn" 
                                onclick="loadMore('${category}', ${nextPage})"
                                data-category="${category}" data-page="${nextPage}">
                            <i class="fas fa-plus-circle"></i>
                            Charger plus (${response.total - (page * 50)} restants)
                        </button>
                    </div>
                `;
                contentContainer.append(btnHtml);
            }
            
            currentPage[category] = page;
        },
        error: function(xhr, status, error) {
            debugError(`❌ DEBUG: AJAX Error pour "${category}", page ${page}`);
            debugError(`🔴 DEBUG: Status:`, status);
            debugError(`🔴 DEBUG: Error:`, error);
            debugError(`🔴 DEBUG: XHR Status:`, xhr.status);
            debugError(`🔴 DEBUG: XHR Response:`, xhr.responseText);
            
            const errorHtml = `
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                    <h5 class="text-danger">Erreur de chargement</h5>
                    <p class="text-muted">Impossible de charger les données. 
                        <button class="btn-link" onclick="loadTabContent('${category}')">Réessayer</button>
                    </p>
                    <small class="text-muted">Erreur: ${xhr.status} - ${error}</small>
                </div>
            `;
            
            if (page === 1) {
                debugLog(`🛑 DEBUG: Masquage spinner et affichage erreur`);
                loadingSpinner.addClass('hidden');
                contentContainer.html(errorHtml).show();
            }
        }
    });
}

// Fonction pour charger plus d'étudiants
function loadMore(category, page) {
    const loadMoreBtn = $(`.load-more-btn[data-category="${category}"]`);
    const originalText = loadMoreBtn.html();
    
    // Afficher un spinner sur le bouton
    loadMoreBtn.html('<i class="fas fa-spinner fa-spin"></i> Chargement...')
              .prop('disabled', true);
    
    loadTabContent(category, page);
}

// Fonctions utilitaires pour les actions sur les étudiants
function validerReinscription(etudiantId, decision) {
    const observations = prompt(`Valider la réinscription avec décision: ${decision}\n\nObservations (optionnel):`);
    
    if (observations === null) return; // Annulé
    
    if (confirm(`Confirmer la validation de la réinscription ?\n\nDécision: ${decision}\nObservations: ${observations || 'Aucune'}`)) {
        $.ajax({
            url: `{{ url('esbtp/reinscription') }}/${etudiantId}/valider`,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({
                decision: decision,
                observations: observations
            }),
            success: function(data) {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Recharger la page pour voir les changements
                } else {
                    alert('Erreur: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                debugError('Erreur:', error);
                alert('Erreur lors de la validation');
            }
        });
    }
}

function marquerAbandonModal(etudiantId) {
    const typeAbandon = confirm('Type d\'abandon:\n\nOUI = Abandon année scolaire (n\'a pas soldé, ne vient plus)\nNON = Abandon école (année réussie mais quitte l\'établissement)') 
        ? 'annee_scolaire' : 'ecole';
    
    const motif = prompt('Motif de l\'abandon (optionnel):');
    if (motif === null) return; // Annulé
    
    if (confirm(`Confirmer l'abandon de type "${typeAbandon === 'annee_scolaire' ? 'Année scolaire' : 'École'}" ?\n\nMotif: ${motif || 'Non précisé'}`)) {
        $.ajax({
            url: `{{ url('esbtp/reinscription') }}/${etudiantId}/abandon`,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({
                motif_abandon: motif,
                abandon_type: typeAbandon
            }),
            success: function(data) {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                debugError('Erreur:', error);
                alert('Erreur lors de l\'enregistrement de l\'abandon');
            }
        });
    }
}

function exportResults() {
    window.location.href = '{{ route("esbtp.reinscription.export") }}';
}

function showYearChangeInfo() {
    debugLog('🟢 DEBUG REINSCRIPTIONS: Fonction showYearChangeInfo() appelée');
    debugLog('🟢 DEBUG REINSCRIPTIONS: Modal #yearChangeModal existe?', $('#yearChangeModal').length > 0);
    try {
        $('#yearChangeModal').modal('show');
        debugLog('✅ DEBUG REINSCRIPTIONS: Commande modal(show) exécutée dans showYearChangeInfo()');
    } catch (error) {
        debugError('❌ DEBUG REINSCRIPTIONS: Erreur dans showYearChangeInfo():', error);
    }
}

// Fonction pour recharger les données avec les filtres
function applyFilters() {
    debugLog('🔍 Applying filters and reloading all tabs...');
    
    // Réinitialiser le statut des onglets chargés
    loadedTabs = {};
    
    // Recharger l'onglet actuellement actif
    const activeTab = $('.tab-pane.active');
    if (activeTab.length > 0) {
        const category = activeTab.attr('id');
        debugLog(`🎯 Rechargement de l'onglet actif: ${category}`);
        loadTabContent(category, 1);
        loadedTabs[category] = true;
    }
    
    return false; // Empêcher la soumission normale du formulaire
}

// FONCTION TEST DEBUG TEMPORAIRE
function testInjection() {
    debugLog('🧪 TEST INJECTION HTML');
    
    const testHtml = `
        <div class="table-responsive" style="width: 100% !important; border: 2px solid red;">
            <h3 style="color: red; text-align: center;">TEST INJECTION</h3>
            <table class="table table-hover" style="width: 100% !important; border: 2px solid blue;">
                <thead style="background-color: #0453cb !important; color: white !important;">
                    <tr>
                        <th style="padding: 16px !important;">TEST HEADER 1</th>
                        <th style="padding: 16px !important;">TEST HEADER 2</th>
                        <th style="padding: 16px !important;">TEST HEADER 3</th>
                    </tr>
                </thead>
                <tbody style="background-color: white;">
                    <tr>
                        <td style="padding: 16px;">TEST DATA 1</td>
                        <td style="padding: 16px;">TEST DATA 2</td>
                        <td style="padding: 16px;">TEST DATA 3</td>
                    </tr>
                    <tr>
                        <td style="padding: 16px;">TEST DATA 4</td>
                        <td style="padding: 16px;">TEST DATA 5</td>
                        <td style="padding: 16px;">TEST DATA 6</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    
    // Trouver le content-container actif
    const activeTabPane = $('.tab-pane.active, .tab-pane.show');
    let targetContainer;
    
    if (activeTabPane.length > 0) {
        targetContainer = activeTabPane.find('.content-container');
        debugLog('🎯 Container actif trouvé:', activeTabPane.attr('id'));
    } else {
        // Si pas d'onglet actif, utiliser redoublements
        targetContainer = $('#redoublements .content-container');
        debugLog('🎯 Utilise container redoublements par défaut');
    }
    
    if (targetContainer.length > 0) {
        debugLog('✅ Container trouvé, injection du HTML test');
        
        // Masquer le spinner
        targetContainer.siblings('.reinscription-spinner').addClass('hidden');
        
        // Injecter le HTML test
        targetContainer.html(testHtml);
        
        // Forcer l'affichage
        targetContainer.show();
        targetContainer.css({
            'display': 'block !important',
            'width': '100% !important',
            'visibility': 'visible !important'
        });
        
        debugLog('✅ HTML test injecté avec succès');
        alert('HTML TEST injecté! Regardez si la table s\'affiche avec les headers.');
        
    } else {
        debugLog('❌ Container non trouvé');
        alert('Erreur: Container non trouvé');
    }
}

// Gérer la fermeture de la modal
$('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
    $('#yearChangeModal').modal('hide');
});

$('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
    $('#yearChangeModal').modal('hide');
});

/* ───────────────────────────────────────────────────────────
   KPI hero click → active la tab BS4 correspondante + scroll.
   Requires: jQuery + BS4 .tab() plugin (chargés plus haut dans
   ce même <script>). Aucun handler existant n'est altéré.
   Sécurité : seuls les KPIs avec data-rekpi-target sont liés
   (les KPIs --empty n'ont pas l'attribut → pas d'écouteur).
   ─────────────────────────────────────────────────────────── */
$(document).ready(function() {
    $('.re-kpi[data-rekpi-target]').on('click', function(e) {
        e.preventDefault();
        var targetTabId = $(this).data('rekpi-target');
        var $tabLink = $('#' + targetTabId);
        if ($tabLink.length === 0) {
            // Defensive : tab non rendue (race condition au chargement). No-op.
            return;
        }
        $tabLink.tab('show');
        // Scroll doux vers la zone tabs.
        // Offset 90px = hauteur du topbar fixe de layouts.app — à ajuster
        // si le topbar change de hauteur.
        var $tabsAnchor = $('#myTab');
        if ($tabsAnchor.length) {
            $('html, body').animate({
                scrollTop: $tabsAnchor.offset().top - 90
            }, 280);
        }
    });
});
</script>

@endsection
