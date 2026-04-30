@extends('layouts.app')

@section('title', 'Tableau de bord')

@push('styles')
<style>
/* ===========================================================
   Lot 9 — Dashboard widget-based (namespace dw-*)
   Suit .claude/rules/premium-redesign.md (palette monochrome bleu KLASSCI)
   =========================================================== */

.dw-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}

.dw-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.dw-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dw-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}

.dw-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.dw-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

.dw-hero-actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}

.dw-btn {
    border-radius: 10px;
    padding: .5rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    border: 1px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    text-decoration: none;
    cursor: pointer;
    transition: all .2s ease;
}

.dw-btn--glass {
    background: rgba(255,255,255,.15);
    color: #fff;
    border-color: rgba(255,255,255,.2);
}

.dw-btn--glass:hover {
    background: rgba(255,255,255,.25);
    color: #fff;
}

.dw-btn--white {
    background: #fff;
    color: #0453cb;
}

.dw-btn--white:hover {
    background: #eef4ff;
    color: #033a8e;
}

/* Layout grid */
.dw-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.dw-grid-cell {
    grid-column: span 1;
}

.dw-grid-cell--sm { grid-column: span 1; }
.dw-grid-cell--md { grid-column: span 2; }
.dw-grid-cell--lg { grid-column: span 4; }

@media (max-width: 992px) {
    .dw-grid { grid-template-columns: repeat(2, 1fr); }
    .dw-grid-cell--sm,
    .dw-grid-cell--md { grid-column: span 1; }
    .dw-grid-cell--lg { grid-column: span 2; }
}

@media (max-width: 576px) {
    .dw-grid { grid-template-columns: 1fr; }
    .dw-grid-cell,
    .dw-grid-cell--sm,
    .dw-grid-cell--md,
    .dw-grid-cell--lg { grid-column: span 1; }
    .dw-hero { padding: 1.25rem 1.5rem 1rem; }
}

/* Widget cards */
.dw-widget {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    height: 100%;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    transition: all .2s ease;
}

.dw-widget:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    transform: translateY(-2px);
}

.dw-widget-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
}

.dw-widget--success .dw-widget-icon { background: linear-gradient(135deg, #10b981, #059669); }
.dw-widget--warning .dw-widget-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
.dw-widget--info .dw-widget-icon { background: linear-gradient(135deg, #3b7ddb, #5e91de); }

.dw-widget-body {
    flex: 1;
    min-width: 0;
}

.dw-widget-label {
    font-size: .78rem;
    color: #64748b;
    font-weight: 500;
    margin-bottom: .35rem;
}

.dw-widget-value {
    font-size: 1.65rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.dw-widget-unit {
    font-size: .85rem;
    color: #64748b;
    font-weight: 500;
    margin-left: .25rem;
}

.dw-widget-hint {
    font-size: .78rem;
    color: #64748b;
    margin-top: .35rem;
}

.dw-widget-link {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-top: .5rem;
    font-size: .8rem;
    font-weight: 600;
    color: #0453cb;
    text-decoration: none;
}

.dw-widget-link:hover { color: #033a8e; text-decoration: underline; }

.dw-widget--alert {
    border-color: #f59e0b;
    border-left-width: 4px;
}

/* List widget */
.dw-widget--list {
    flex-direction: column;
    align-items: stretch;
    padding: 1rem 1.25rem;
}

.dw-widget-list-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding-bottom: .75rem;
    margin-bottom: .25rem;
    border-bottom: 1px solid #f1f5f9;
}

.dw-widget-icon--small {
    width: 36px; height: 36px;
    font-size: .9rem;
    border-radius: 10px;
}

.dw-widget-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dw-widget-list-item {
    padding: .65rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.dw-widget-list-item:last-child { border-bottom: none; }

.dw-widget-list-title {
    font-size: .9rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: .2rem;
}

.dw-widget-list-meta {
    font-size: .75rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: .35rem;
}

.dw-widget-list-empty {
    padding: 1rem 0;
    color: #64748b;
    font-size: .85rem;
    text-align: center;
}

.dw-widget-list-empty i { margin-right: .35rem; }

/* Empty layout state */
.dw-empty-state {
    background: #fff;
    border: 2px dashed #e2e8f0;
    border-radius: 14px;
    padding: 3rem 2rem;
    text-align: center;
    color: #64748b;
}

.dw-empty-state-icon {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.dw-empty-state h3 {
    color: #0f172a;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 .35rem;
}

/* Configure modal */
.dw-modal-group { margin-bottom: 1.25rem; }

.dw-modal-group-title {
    font-size: .85rem;
    font-weight: 700;
    color: #0453cb;
    margin: 0 0 .5rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.dw-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .65rem .85rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: .35rem;
    background: #fff;
}

.dw-toggle-row + .dw-toggle-row { margin-top: 0; }

.dw-toggle-row.is-active {
    border-color: #0453cb;
    background: rgba(4,83,203,.04);
}

.dw-toggle-info {
    flex: 1;
    min-width: 0;
}

.dw-toggle-info-title {
    font-size: .9rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: .15rem;
}

.dw-toggle-info-desc {
    font-size: .75rem;
    color: #64748b;
}

.dw-toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.dw-toggle-switch input { opacity: 0; width: 0; height: 0; }

.dw-toggle-slider {
    position: absolute;
    inset: 0;
    background: #cbd5e1;
    border-radius: 24px;
    cursor: pointer;
    transition: .2s;
}

.dw-toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    left: 3px; top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .2s;
}

.dw-toggle-switch input:checked + .dw-toggle-slider { background: #0453cb; }
.dw-toggle-switch input:checked + .dw-toggle-slider::before { transform: translateX(20px); }
</style>
@endpush

@section('content')
<div class="main-content">
    {{-- Hero header --}}
    <div class="dw-hero">
        <div class="dw-hero-top">
            <div class="dw-hero-left">
                <div class="dw-hero-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div>
                    <h1>Tableau de bord</h1>
                    <p>
                        @if ($hasCustomLayout)
                            Configuration personnalisée
                        @else
                            Vue par défaut basée sur votre rôle
                        @endif
                        — {{ $widgets->count() }} widget(s) actif(s)
                    </p>
                </div>
            </div>

            <div class="dw-hero-actions">
                @if ($hasCustomLayout)
                    <form action="{{ route('dashboard.widgets.reset') }}" method="POST" class="dw-reset-form">
                        @csrf
                        <button type="submit" class="dw-btn dw-btn--glass" title="Restaurer les défauts">
                            <i class="fas fa-undo"></i> Restaurer
                        </button>
                    </form>
                @endif
                <button type="button" class="dw-btn dw-btn--white" data-bs-toggle="modal" data-bs-target="#dwConfigureModal">
                    <i class="fas fa-sliders-h"></i> Configurer mon dashboard
                </button>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    {{-- Widgets grid --}}
    @if ($widgets->isEmpty())
        <div class="dw-empty-state">
            <div class="dw-empty-state-icon">
                <i class="fas fa-puzzle-piece"></i>
            </div>
            <h3>Aucun widget actif</h3>
            <p>Vous n'avez pas encore activé de widget. Cliquez sur "Configurer mon dashboard" pour en ajouter.</p>
            <button type="button" class="dw-btn dw-btn--white" data-bs-toggle="modal" data-bs-target="#dwConfigureModal" style="background:#0453cb;color:#fff;border-color:#0453cb;">
                <i class="fas fa-plus"></i> Choisir mes widgets
            </button>
        </div>
    @else
        <div class="dw-grid">
            @foreach ($widgets as $widget)
                @php
                    $size = $widget['size'] ?? 'sm';
                @endphp
                <div class="dw-grid-cell dw-grid-cell--{{ $size }}">
                    @include($widget['partial'], ['widget' => $widget, 'user' => $user])
                </div>
            @endforeach
        </div>
    @endif

    {{-- Configure modal --}}
    @include('dashboard._configure-modal', [
        'availableGrouped' => $availableGrouped,
        'activeKeys' => $activeKeys,
    ])
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // Logique simple : chaque toggle a une checkbox, le formulaire submit la liste
    // ordonnée des widgets cochés. L'ordre est préservé dans le DOM (input
    // hidden auto-écrit en submit handler).
    const form = document.getElementById('dwConfigureForm');
    if (!form) { return; }

    form.addEventListener('submit', function () {
        // Désactive les boutons up/down de tri en repassant par l'ordre du DOM.
        // Aucun JS plus avancé nécessaire : la liste de widgets[] dans le POST
        // suit l'ordre des inputs cochés visibles.
    });

    // Up/Down sort buttons
    document.querySelectorAll('.dw-sort-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const direction = btn.dataset.direction;
            const row = btn.closest('.dw-toggle-row');
            if (!row) { return; }
            const sibling = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling || !sibling.classList.contains('dw-toggle-row')) { return; }
            if (direction === 'up') {
                row.parentNode.insertBefore(row, sibling);
            } else {
                row.parentNode.insertBefore(sibling, row);
            }
        });
    });

    // Toggle visual state when checkbox changes
    document.querySelectorAll('.dw-toggle-switch input[type=checkbox]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const row = cb.closest('.dw-toggle-row');
            if (row) {
                row.classList.toggle('is-active', cb.checked);
            }
        });
    });
})();
</script>
@endpush
