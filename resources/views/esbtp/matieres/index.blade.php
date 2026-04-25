@extends('layouts.app')

@section('title', 'Liste des matières')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
@endsection

@push('styles')
<style>
/* ════════════════════════════════════════════════════════════════
   MI-* — Matières Index (premium namespace)
   Pattern : planning-header 2-rows + KPIs glass + filtres hybrid
   + bulk-bar sticky-top + table card. Cohérent avec pi-* / ii-*.
   ════════════════════════════════════════════════════════════════ */

/* ─── HERO ─── */
.mi-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.mi-hero::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 280px;
    height: 280px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
    pointer-events: none;
}
.mi-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.25rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}
.mi-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
}
.mi-hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    color: #fff;
}
.mi-hero h1 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 .15rem;
    letter-spacing: -.01em;
}
.mi-hero p {
    color: rgba(255,255,255,.72);
    font-size: .88rem;
    margin: 0;
}
.mi-hero-actions {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
}
.mi-hero-search {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff;
    border-radius: 10px;
    padding: .55rem 1rem .55rem 2.4rem;
    font-size: .85rem;
    width: 260px;
    transition: all .2s;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' opacity='0.65'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: .85rem center;
    background-size: 14px;
}
.mi-hero-search::placeholder { color: rgba(255,255,255,.55); }
.mi-hero-search:focus {
    outline: none;
    background-color: rgba(255,255,255,.18);
    border-color: rgba(255,255,255,.35);
    box-shadow: 0 0 0 3px rgba(255,255,255,.1);
}
.mi-btn--white,
.mi-btn--glass {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .55rem 1rem;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .2s ease;
    white-space: nowrap;
}
.mi-btn--white {
    background: #fff;
    color: #0453cb;
}
.mi-btn--white:hover {
    background: #f8fafc;
    color: #033a8e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
}
.mi-btn--glass {
    background: rgba(255,255,255,.15);
    color: #fff;
    border-color: rgba(255,255,255,.2);
    backdrop-filter: blur(8px);
}
.mi-btn--glass:hover {
    background: rgba(255,255,255,.22);
    color: #fff;
    transform: translateY(-1px);
}

/* ─── KPIs ─── */
.mi-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .75rem;
    margin-top: 1.5rem;
    position: relative;
    z-index: 1;
}
.mi-kpi {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    transition: all .2s ease;
}
.mi-kpi:hover {
    background: rgba(255,255,255,.16);
    border-color: rgba(255,255,255,.22);
    transform: translateY(-1px);
}
.mi-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(255,255,255,.18);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
}
.mi-kpi-text { min-width: 0; }
.mi-kpi-value {
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}
.mi-kpi-label {
    font-size: .72rem;
    color: rgba(255,255,255,.65);
    margin-top: .2rem;
    letter-spacing: .02em;
}

@media (max-width: 992px) {
    .mi-kpis { grid-template-columns: repeat(2, 1fr); }
    .mi-hero-search { width: 220px; }
}
@media (max-width: 576px) {
    .mi-hero { padding: 1.25rem 1.25rem 1rem; }
    .mi-hero h1 { font-size: 1.2rem; }
    .mi-kpis { grid-template-columns: 1fr 1fr; }
    .mi-hero-search { width: 100%; }
}

/* ─── FILTRES (hybrid : 3 visibles + collapsible advanced) ─── */
.mi-filters {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.mi-filters-bar {
    display: grid;
    grid-template-columns: minmax(220px, 1.6fr) 1fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
    padding: 1rem 1.25rem;
}
.mi-filter-group { min-width: 0; }
.mi-filter-input--search {
    padding-left: 2.4rem;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: .85rem center;
    background-size: 14px;
}
.mi-filter-input--search::-webkit-search-cancel-button { cursor: pointer; }
.mi-filter-label {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: .35rem;
}
.mi-filter-input,
.mi-filter-select {
    width: 100%;
    padding: .55rem .75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #1e293b;
    font-size: .88rem;
    transition: border-color .2s, box-shadow .2s;
}
.mi-filter-input:focus,
.mi-filter-select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.mi-filters-actions {
    display: flex;
    gap: .5rem;
    align-items: center;
}
.mi-advanced-btn,
.mi-btn-clear {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem .85rem;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s ease;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    white-space: nowrap;
}
.mi-advanced-btn:hover,
.mi-btn-clear:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: rgba(4,83,203,.04);
}
.mi-advanced-btn.is-open {
    background: rgba(4,83,203,.08);
    border-color: #0453cb;
    color: #0453cb;
}
.mi-advanced-btn .mi-advanced-count {
    background: #0453cb;
    color: #fff;
    font-size: .68rem;
    font-weight: 700;
    padding: .05rem .4rem;
    border-radius: 999px;
    margin-left: .15rem;
    min-width: 18px;
    text-align: center;
}
.mi-advanced-btn .fa-chevron-down {
    font-size: .65rem;
    transition: transform .2s ease;
    margin-left: .15rem;
}

.mi-advanced-panel {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    padding: 1rem 1.25rem 1.25rem;
    border-top: 1px dashed #e2e8f0;
    background: #f8fafc;
}

.mi-filters-foot {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .65rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: .8rem;
}
.mi-filters-foot i { color: #0453cb; }

.mi-active-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    padding: .65rem 1.25rem;
    border-top: 1px dashed #e2e8f0;
    background: #f8fafc;
}
.mi-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .25rem .5rem .25rem .65rem;
    background: rgba(4,83,203,.08);
    color: #0453cb;
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
}
.mi-chip-remove {
    background: rgba(4,83,203,.15);
    color: #0453cb;
    border: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .65rem;
    transition: all .15s;
}
.mi-chip-remove:hover {
    background: #0453cb;
    color: #fff;
}

.mi-apply-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    border: 0;
}

@media (max-width: 1100px) {
    .mi-filters-bar { grid-template-columns: 1fr 1fr 1fr auto; }
    .mi-filter-group--search { grid-column: 1 / -1; }
}
@media (max-width: 768px) {
    .mi-filters-bar { grid-template-columns: 1fr; }
    .mi-filter-group--search { grid-column: auto; }
    .mi-filters-actions { justify-content: flex-end; }
    .mi-advanced-panel { grid-template-columns: 1fr 1fr; }
}

/* ─── RESULTS CARD ─── */
.mi-results-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: hidden;
    position: relative;
}
.mi-results-card .table-responsive {
    overflow-x: auto;
}
.mi-results-card .table {
    margin-bottom: 0;
}
.mi-results-card .table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-weight: 600;
    padding: .85rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    border-top: none;
    white-space: nowrap;
}
.mi-results-card .table tbody tr {
    transition: background-color .15s ease;
}
.mi-results-card .table tbody tr:hover {
    background-color: #f0f4fa;
}
.mi-results-card .table tbody td {
    padding: .85rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.mi-results-card .table tbody tr:last-child td {
    border-bottom: none;
}
.mi-results-card .pagination-wrapper {
    padding: .85rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

/* ─── BULK-BAR sticky-top INSIDE results-card ─── */
.matieres-bulk-bar,
.mi-bulk-bar {
    position: sticky;
    top: 0;
    z-index: 30;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 100%);
    color: #fff;
    padding: .85rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 16px rgba(4,83,203,.18);
    animation: mi-bulk-slide-down .25s ease-out;
}
.mi-bulk-info {
    display: flex;
    align-items: center;
    gap: .65rem;
    font-size: .9rem;
    font-weight: 500;
}
.mi-bulk-info i { font-size: 1rem; }
.mi-bulk-actions {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
}
.mi-bulk-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .85rem;
    background: rgba(255,255,255,.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .18s ease;
    white-space: nowrap;
}
.mi-bulk-btn:hover {
    background: rgba(255,255,255,.25);
    border-color: rgba(255,255,255,.4);
}
.mi-bulk-btn--danger {
    background: rgba(220,38,38,.25);
    border-color: rgba(248,113,113,.5);
}
.mi-bulk-btn--danger:hover {
    background: rgba(220,38,38,.4);
    border-color: rgba(248,113,113,.7);
}
.mi-bulk-btn--ghost {
    background: transparent;
    border-color: rgba(255,255,255,.3);
}

@keyframes mi-bulk-slide-down {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
}

/* ─── EMPTY STATE ─── */
.mi-empty-state {
    padding: 3.5rem 1.5rem;
    text-align: center;
    color: #64748b;
}
.mi-empty-state-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #e2e8f0 0%, #f1f5f9 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 1.6rem;
}
.mi-empty-state h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 .35rem;
}
.mi-empty-state p {
    font-size: .88rem;
    color: #64748b;
    margin: 0 0 1rem;
    max-width: 380px;
    margin-left: auto;
    margin-right: auto;
}
.mi-empty-state .mi-empty-cta {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem .9rem;
    background: #0453cb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s;
    text-decoration: none;
}
.mi-empty-state .mi-empty-cta:hover {
    background: #033a8e;
    color: #fff;
}

/* ─── ROW ACTIONS — kebab menu pour secondaires ─── */
.mi-actions {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
}
.mi-action-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0369a1;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    font-size: .8rem;
}
.mi-action-primary:hover {
    background: #e0f2fe;
    border-color: #0369a1;
    color: #0369a1;
}
.mi-action-kebab-wrap { position: relative; }
.mi-action-kebab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
    font-size: .85rem;
}
.mi-action-kebab:hover,
.mi-action-kebab[aria-expanded="true"] {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #1e293b;
}
.mi-action-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    min-width: 180px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 10px 32px rgba(15,23,42,.12), 0 2px 6px rgba(15,23,42,.06);
    padding: .35rem;
    z-index: 100;
    display: none;
}
.mi-action-menu.is-open {
    display: block;
    animation: mi-menu-fade-in .15s ease;
}
@keyframes mi-menu-fade-in {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.mi-action-item {
    display: flex;
    align-items: center;
    gap: .5rem;
    width: 100%;
    padding: .5rem .65rem;
    border-radius: 6px;
    background: none;
    border: none;
    color: #1e293b;
    font-size: .85rem;
    text-align: left;
    cursor: pointer;
    text-decoration: none;
    transition: background .12s;
}
.mi-action-item:hover {
    background: #f1f5f9;
    color: #0453cb;
}
.mi-action-item i {
    font-size: .82rem;
    color: #64748b;
    width: 14px;
    text-align: center;
}
.mi-action-item:hover i { color: #0453cb; }
.mi-action-item--danger { color: #dc2626; }
.mi-action-item--danger i { color: #dc2626; }
.mi-action-item--danger:hover {
    background: #fef2f2;
    color: #b91c1c;
}
.mi-action-item--danger:hover i { color: #b91c1c; }
.mi-action-divider {
    height: 1px;
    background: #e2e8f0;
    margin: .25rem -.35rem;
}

/* ════════════════════════════════════════════════════════════════
   JS-REFERENCED CLASSES — must remain unchanged (preserved from old)
   ════════════════════════════════════════════════════════════════ */
.matiere-actions-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 32px;
}
.matiere-actions-spinner {
    display: none;
    min-width: 32px;
    align-items: center;
    justify-content: center;
}
.matiere-actions-wrapper.is-loading .matiere-actions-buttons {
    display: none !important;
}
.matiere-actions-wrapper.is-loading .matiere-actions-spinner {
    display: inline-flex !important;
}

tr[data-matiere-id] {
    position: relative;
    overflow: hidden;
    transition: background-color 0.3s ease;
}

.matiere-row-highlight {
    position: absolute;
    top: 0;
    left: -65%;
    width: 150%;
    height: 100%;
    pointer-events: none;
    opacity: 0;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(4, 83, 203, 0) 0%, rgba(4, 83, 203, 0.65) 50%, rgba(4, 83, 203, 0) 100%);
    z-index: 5;
}
.matiere-row-highlight.reject {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.65) 50%, rgba(220, 53, 69, 0) 100%);
}
.matiere-row-highlight.animate {
    animation: matiere-row-highlight-move 2.8s ease-out forwards;
}
.matiere-row-flash {
    animation: matiere-row-flash 0.8s ease-in-out;
}
.matiere-row-flash.reject {
    animation-name: matiere-row-flash-reject;
}

@keyframes matiere-row-highlight-move {
    0% { opacity: 0; transform: translateX(-65%) skewX(-12deg); }
    18% { opacity: 0.9; }
    55% { opacity: 0.7; }
    100% { opacity: 0; transform: translateX(115%) skewX(-12deg); }
}
@keyframes matiere-row-flash {
    0% { background-color: transparent; }
    25% { background-color: rgba(4, 83, 203, 0.15); }
    100% { background-color: transparent; }
}
@keyframes matiere-row-flash-reject {
    0% { background-color: transparent; }
    25% { background-color: rgba(220, 53, 69, 0.15); }
    100% { background-color: transparent; }
}

#combinations-preview .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.modal-xl { max-width: 1200px; }
</style>
@endpush

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
                <input type="search"
                       class="mi-hero-search"
                       id="matieres-header-search"
                       placeholder="Rechercher par nom ou code..."
                       value="{{ $filters['search'] ?? '' }}"
                       aria-label="Rechercher une matière par nom ou code">
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

        {{-- Submit button hidden but kept (no-JS fallback) --}}
        <button type="submit" id="matieres-apply-filters" class="mi-apply-hidden" tabindex="-1" aria-hidden="true">Appliquer</button>

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
{{-- common.js fournit window.iiConfirm() et window.iiToast(). --}}
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<script>
(function () {
    const FILTER_DEBOUNCE = 350;

    const filtersForm = document.getElementById('matieres-filter-form');
    const headerSearch = document.getElementById('matieres-header-search');
    const summaryElement = document.getElementById('results-count');
    const resultsContainer = document.getElementById('matieres-results');
    const clearFiltersBtn = document.getElementById('matieres-clear-filters');
    const applyFiltersBtn = document.getElementById('matieres-apply-filters');
    const bulkBar = document.getElementById('matieres-bulk-bar');
    const bulkCount = document.getElementById('matieres-selected-count');
    const bulkAttachBtn = document.getElementById('matieres-bulk-attach');
    const bulkConfigureBtn = document.getElementById('matieres-bulk-configure');
    const bulkDeleteBtn = document.getElementById('matieres-bulk-delete');
    const bulkClearBtn = document.getElementById('matieres-bulk-clear');
    let filterTimer = null;
    const selectedIds = new Set();

    function showToast(type, message) {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
        } else if (typeof window.showToast === 'function' && window.showToast !== showToast) {
            // common.js exposes window.showToast(message, type, duration)
            window.showToast(message, type, 3500);
        } else {
            const logMethod = type === 'error' ? 'error' : 'log';
            console[logMethod](message);
        }
    }

    function getSelectedIdsArray() {
        return Array.from(selectedIds);
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

    function syncHeaderSearchFromForm() {
        if (!filtersForm || !headerSearch) {
            return;
        }

        const formSearch = filtersForm.querySelector('#filter-search');
        if (formSearch) {
            headerSearch.value = formSearch.value || '';
        }
    }

    function syncFormSearchFromHeader() {
        if (!filtersForm || !headerSearch) {
            return;
        }

        const formSearch = filtersForm.querySelector('#filter-search');
        if (formSearch) {
            formSearch.value = headerSearch.value || '';
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

    function refreshMatiereRow(matiereId, actionType = 'update') {
        const url = `{{ route('esbtp.matieres.refresh-ligne', ['matiere' => '__ID__']) }}`.replace('__ID__', matiereId);

        setMatiereRowLoadingState(matiereId, true);

        return fetch(url, {
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
            })
            .catch((error) => {
                debugError('Erreur lors du rafraîchissement de la matière:', error);
            })
            .finally(() => {
                setMatiereRowLoadingState(matiereId, false);
                initTableInteractions();
            });
    }
    window.refreshMatiereRow = refreshMatiereRow;

    function submitFilterForm(pushHistory = true) {
        if (!filtersForm || !resultsContainer) {
            return;
        }

        const formData = new FormData(filtersForm);
        const params = new URLSearchParams(formData);
        const refreshUrl = resultsContainer.dataset.refreshUrl;
        const url = `${refreshUrl}?${params.toString()}`;

        resultsContainer.classList.add('position-relative');
        const overlay = document.createElement('div');
        overlay.className = 'd-flex align-items-center justify-content-center';
        overlay.style.position = 'absolute';
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.background = 'rgba(255,255,255,0.8)';
        overlay.style.zIndex = '5';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        resultsContainer.appendChild(overlay);

        fetch(url, {
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
                if (!data.html) {
                    throw new Error('Template manquant dans la réponse');
                }
                resultsContainer.innerHTML = data.html;
                resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                if (pushHistory && data.url) {
                    history.pushState({}, '', data.url);
                }
                updateSummary(data.summary || {});
                updateKpis(data.kpis || {});
                clearSelection();
            })
            .catch((error) => {
                debugError('Erreur lors du rafraîchissement des matières:', error);
            })
            .finally(() => {
                overlay.remove();
                resultsContainer.classList.remove('position-relative');
                initTableInteractions();
            });
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
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const href = link.getAttribute('href');
                if (!href || href === '#') {
                    return;
                }
                fetch(href, {
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
                        if (!data.html) {
                            throw new Error('Template manquant dans la réponse');
                        }
                        resultsContainer.innerHTML = data.html;
                        resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                        if (data.url) {
                            history.pushState({}, '', data.url);
                        }
                        updateSummary(data.summary || {});
                        updateKpis(data.kpis || {});
                        clearSelection();
                        initTableInteractions();
                    })
                    .catch((error) => {
                        debugError('Erreur pagination matières:', error);
                    });
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

    if (headerSearch) {
        headerSearch.addEventListener('input', () => {
            syncFormSearchFromHeader();
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });

        headerSearch.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                syncFormSearchFromHeader();
                submitFilterForm();
            }
        });
    }

    // Visible filter-search input (in the filters bar) : miroir du headerSearch.
    // L'input event sync vers le hero search puis débounce le submit.
    const filterSearchInput = document.getElementById('filter-search');
    if (filterSearchInput) {
        filterSearchInput.addEventListener('input', () => {
            syncHeaderSearchFromForm();
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });
        filterSearchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                syncHeaderSearchFromForm();
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
            if (headerSearch) {
                headerSearch.value = '';
            }
            submitFilterForm();
        });
    }

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', (event) => {
            event.preventDefault();
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

            const confirmFn = typeof window.iiConfirm === 'function' ? window.iiConfirm : null;
            const message = `Supprimer ${ids.length} matière(s) sélectionnée(s) ? Cette action est irréversible.`;
            const ok = confirmFn
                ? await confirmFn({ title: 'Suppression en lot', message, confirmLabel: 'Supprimer', cancelLabel: 'Annuler', danger: true })
                : window.confirm(message);
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
                showToast('success', `${ids.length} matière(s) supprimée(s).`);
                submitFilterForm(false);
            } catch (error) {
                debugError('Erreur lors de la suppression en lot:', error);
                showToast('error', 'Impossible de supprimer les matières sélectionnées.');
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
                const confirmFn = typeof window.iiConfirm === 'function' ? window.iiConfirm : null;
                const message = 'Aucune liaison sélectionnée. Voulez-vous tout de même continuer (cela supprimera toutes les liaisons existantes) ?';
                const ok = confirmFn
                    ? await confirmFn({ title: 'Aucune liaison', message, confirmLabel: 'Continuer', cancelLabel: 'Annuler', danger: true })
                    : window.confirm(message);
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
                        showToast('success', data.message || 'Liaisons mises à jour avec succès.');
                    })
                    .catch((error) => {
                        debugError('Erreur lors de la mise à jour des liaisons:', error);
                        showToast('error', error.message || 'Impossible de mettre à jour les liaisons.');
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
        if (headerSearch) headerSearch.value = '';
        submitFilterForm();
    });

    updateSummary(JSON.parse(resultsContainer.dataset.summary || '{}'));
    syncHeaderSearchFromForm();
    initTableInteractions();

    window.addEventListener('popstate', () => {
        applyQueryToForm(window.location.href);
        syncHeaderSearchFromForm();
        submitFilterForm(false);
    });
})();
</script>
@endpush
