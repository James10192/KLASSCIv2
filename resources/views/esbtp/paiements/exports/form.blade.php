@extends('layouts.app')

@section('title', 'Export détaillé des paiements - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    /* ═══════════════════════════════════════════════
       Namespace pe-* (paiements-export premium)
       ═══════════════════════════════════════════════ */
    .pe-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
    }
    .pe-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .pe-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .pe-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .pe-hero h1 {
        font-size: 1.45rem; font-weight: 700;
        color: #fff; margin: 0 0 .2rem;
        letter-spacing: -.01em;
    }
    .pe-hero p {
        color: rgba(255,255,255,.7);
        font-size: .88rem; margin: 0;
    }
    .pe-hero-actions {
        display: flex; gap: .5rem; align-items: center; flex-wrap: wrap;
    }
    .pe-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .5rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600;
        text-decoration: none; transition: all .2s ease;
        border: 1px solid rgba(255,255,255,.2);
        cursor: pointer;
    }
    .pe-btn--glass { background: rgba(255,255,255,.15); color: #fff; }
    .pe-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }

    /* Card */
    .pe-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        padding: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .pe-section-header {
        display: flex; align-items: center; gap: .75rem;
        margin-bottom: 1rem;
    }
    .pe-section-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
    }
    .pe-section-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .pe-section-subtitle { font-size: .8rem; color: #64748b; margin: 0; }

    .pe-section-toolbar {
        margin-left: auto;
        display: flex; gap: .5rem; align-items: center;
    }
    .pe-reset-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .45rem .85rem;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        font-size: .78rem; font-weight: 600;
        border-radius: 999px;
        cursor: pointer;
        transition: all .15s ease;
    }
    .pe-reset-btn:hover { border-color: #0453cb; color: #0453cb; background: #f0f4ff; }
    .pe-reset-btn i { font-size: .72rem; }

    /* Form */
    .pe-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.25rem;
    }
    @media (max-width: 768px) { .pe-form-grid { grid-template-columns: 1fr; } }

    .pe-field { display: flex; flex-direction: column; gap: .35rem; }
    .pe-field-label {
        font-size: .78rem; font-weight: 600; color: #1e293b;
        display: flex; align-items: center; gap: .35rem;
    }
    .pe-field-label i { color: #0453cb; font-size: .72rem; }
    .pe-field-hint { font-size: .72rem; color: #64748b; margin-top: -.1rem; }

    .pe-input,
    .pe-select {
        width: 100%;
        padding: .55rem .8rem;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        font-size: .85rem;
        color: #1e293b;
        transition: border-color .15s, box-shadow .15s;
    }
    .pe-input:focus,
    .pe-select:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.12);
    }

    .pe-checkbox-group {
        display: flex; flex-wrap: wrap; gap: .5rem;
    }
    .pe-checkbox {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .35rem .75rem;
        border: 1px solid #cbd5e1;
        border-radius: 99px;
        font-size: .78rem;
        background: #f8fafc;
        cursor: pointer;
        transition: all .15s ease;
    }
    .pe-checkbox:hover { border-color: #0453cb; background: #f0f4ff; }
    .pe-checkbox input { margin: 0; }
    .pe-checkbox input:checked + span { color: #0453cb; font-weight: 600; }
    .pe-checkbox-clear {
        margin-left: .25rem;
        font-size: .72rem;
        color: #64748b;
        background: transparent; border: 0;
        cursor: pointer; padding: .2rem .5rem;
        border-radius: 999px;
    }
    .pe-checkbox-clear:hover { color: #dc2626; background: #fef2f2; }

    .pe-format-toggle {
        display: flex; gap: .65rem;
    }
    .pe-format-option {
        flex: 1;
        position: relative;
        cursor: pointer;
    }
    .pe-format-option input { position: absolute; opacity: 0; pointer-events: none; }
    .pe-format-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: flex; align-items: center; gap: .75rem;
        background: #fff;
        transition: all .2s ease;
    }
    .pe-format-card i { font-size: 1.5rem; color: #64748b; }
    .pe-format-card .pe-format-title { font-size: .9rem; font-weight: 700; color: #1e293b; margin: 0; }
    .pe-format-card .pe-format-hint { font-size: .72rem; color: #64748b; margin: 0; }
    .pe-format-option input:checked + .pe-format-card {
        border-color: #0453cb;
        box-shadow: 0 4px 16px rgba(4,83,203,.12);
        background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.04));
    }
    .pe-format-option input:checked + .pe-format-card i { color: #0453cb; }

    /* Actions row */
    .pe-actions-row {
        display: flex; justify-content: space-between; align-items: center;
        gap: .75rem; flex-wrap: wrap;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        margin-top: .75rem;
    }
    .pe-preview-info {
        font-size: .82rem; color: #64748b;
        display: flex; align-items: center; gap: .5rem;
    }
    .pe-preview-info.is-success { color: #10b981; font-weight: 600; }
    .pe-preview-info.is-error { color: #dc2626; font-weight: 600; }

    .pe-action-btn {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .65rem 1.2rem;
        border-radius: 10px;
        font-size: .85rem; font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .2s ease;
    }
    .pe-action-btn--secondary {
        background: #fff; color: #1e293b; border-color: #cbd5e1;
    }
    .pe-action-btn--secondary:hover { border-color: #0453cb; color: #0453cb; }
    .pe-action-btn--primary {
        background: #0453cb; color: #fff;
    }
    .pe-action-btn--primary:hover { background: #033a8e; }
    .pe-action-btn:disabled {
        opacity: .55; cursor: not-allowed;
    }

    /* ─── Select2 premium overrides KLASSCI ─── */
    .pe-card .select2-container .select2-selection,
    .pe-card .select2-container--bootstrap-5 .select2-selection {
        min-height: calc(.55rem * 2 + 1.4rem);
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        font-size: .85rem;
        color: #1e293b;
        padding: .25rem .55rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .pe-card .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .pe-card .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.12);
    }
    /* Multi-select (classes) — tags premium */
    .pe-card .select2-container--bootstrap-5 .select2-selection--multiple {
        padding: .25rem .35rem;
        min-height: 42px;
    }
    .pe-card .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
        gap: .3rem;
        padding: 0;
    }
    .pe-card .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .2rem .65rem .2rem .55rem;
        font-size: .78rem;
        font-weight: 600;
        margin: .15rem .25rem .15rem 0;
        line-height: 1.4;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .pe-card .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255,255,255,.85) !important;
        font-weight: 700;
        margin-right: .2rem;
        border: 0;
        background: transparent;
        font-size: .9rem;
        line-height: 1;
        padding: 0 .15rem;
    }
    .pe-card .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #fff !important;
        background: rgba(255,255,255,.18);
        border-radius: 999px;
    }
    /* Dropdown — premium */
    .select2-container--bootstrap-5 .select2-dropdown {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 12px 40px rgba(15,23,42,.14);
        overflow: hidden;
        padding: 4px;
    }
    .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: .45rem .65rem;
        font-size: .85rem;
        background: #f8fafc;
    }
    .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
        outline: none;
        border-color: #0453cb;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(4,83,203,.10);
    }
    .select2-container--bootstrap-5 .select2-results__option {
        padding: .55rem .7rem;
        font-size: .85rem;
        font-weight: 500;
        color: #1e293b;
        border-radius: 8px;
        margin: 2px 0;
        transition: background .12s ease;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted,
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
        background: linear-gradient(135deg, #0453cb, #3b7ddb) !important;
        color: #fff !important;
        font-weight: 600;
    }
    .select2-container--bootstrap-5 .select2-results__option[aria-selected=true] {
        background: rgba(4,83,203,.08);
        color: #0453cb;
        font-weight: 600;
    }
    /* Placeholder */
    .pe-card .select2-container--bootstrap-5 .select2-selection__placeholder {
        color: #94a3b8;
    }
    /* Allow clear (X) sur single select */
    .pe-card .select2-container--bootstrap-5 .select2-selection__clear {
        color: #94a3b8;
        font-weight: 700;
        margin-right: .35rem;
    }
    .pe-card .select2-container--bootstrap-5 .select2-selection__clear:hover {
        color: #dc2626;
    }
    /* Item template avatar (étudiant + classe) */
    .pe-opt-row {
        display: flex; align-items: center; gap: .65rem;
    }
    .pe-opt-icon {
        flex: 0 0 28px; width: 28px; height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0453cb, #5e91de);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 700;
    }
    .pe-opt-text {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column; gap: 1px;
    }
    .pe-opt-title {
        font-weight: 600; color: #0f172a;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .pe-opt-sub {
        font-size: .72rem; color: #64748b;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    /* Sélection (chips) — étudiant simple : pas d'avatar dans la cellule pour rester compact */
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding: .15rem .55rem;
        line-height: 1.4;
        color: #1e293b;
    }

    @media (max-width: 768px) {
        .pe-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
        .pe-hero-top { flex-direction: column; }
        .pe-card { padding: 1.25rem; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Hero --}}
    <div class="pe-hero">
        <div class="pe-hero-top">
            <div class="pe-hero-left">
                <div class="pe-hero-icon"><i class="fas fa-file-export"></i></div>
                <div>
                    <h1>Export détaillé des paiements</h1>
                    <p>États financiers filtrables — PDF (≤ 500 lignes) ou Excel/CSV (jusqu'à 50&nbsp;000 lignes)</p>
                </div>
            </div>
            <div class="pe-hero-actions">
                <a href="{{ route('esbtp.paiements.index') }}" class="pe-btn pe-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour aux paiements
                </a>
            </div>
        </div>
    </div>

    {{-- Form Card --}}
    <form id="pe-form" method="POST" action="{{ route('esbtp.paiements.export-detaille.generate') }}" novalidate>
        @csrf

        <div class="pe-card">
            <div class="pe-section-header">
                <div class="pe-section-icon"><i class="fas fa-filter"></i></div>
                <div>
                    <h3 class="pe-section-title">Filtres</h3>
                    <p class="pe-section-subtitle">Affinez la sélection des paiements à exporter</p>
                </div>
                <div class="pe-section-toolbar">
                    <button type="button" id="pe-reset-all" class="pe-reset-btn" title="Réinitialiser tous les filtres">
                        <i class="fas fa-rotate-left"></i> Réinitialiser tous les filtres
                    </button>
                </div>
            </div>

            <div class="pe-form-grid">
                {{-- Étudiant (Select2 AJAX) --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-etudiant">
                        <i class="fas fa-user-graduate"></i> Étudiant (matricule ou nom)
                    </label>
                    <select name="etudiant_id" id="pe-etudiant" class="pe-select" data-placeholder="Rechercher un étudiant…">
                        <option value=""></option>
                    </select>
                    <div class="pe-field-hint">Tapez au moins 3 caractères — laisser vide pour tous les étudiants</div>
                </div>

                {{-- Format --}}
                <div class="pe-field">
                    <label class="pe-field-label">
                        <i class="fas fa-file-alt"></i> Format
                    </label>
                    <div class="pe-format-toggle">
                        <label class="pe-format-option">
                            <input type="radio" name="format" value="pdf" checked>
                            <div class="pe-format-card">
                                <i class="fas fa-file-pdf"></i>
                                <div>
                                    <p class="pe-format-title">PDF</p>
                                    <p class="pe-format-hint">≤ 500 lignes</p>
                                </div>
                            </div>
                        </label>
                        <label class="pe-format-option">
                            <input type="radio" name="format" value="excel">
                            <div class="pe-format-card">
                                <i class="fas fa-file-excel"></i>
                                <div>
                                    <p class="pe-format-title">Excel/CSV</p>
                                    <p class="pe-format-hint">≤ 50 000 lignes</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Filière --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-filiere">
                        <i class="fas fa-stream"></i> Filière
                    </label>
                    <select name="filiere_id" id="pe-filiere" class="pe-select" data-placeholder="— Toutes les filières —">
                        <option value=""></option>
                        @foreach($filieres as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Niveau --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-niveau">
                        <i class="fas fa-layer-group"></i> Niveau d'études
                    </label>
                    <select name="niveau_id" id="pe-niveau" class="pe-select" data-placeholder="— Tous les niveaux —">
                        <option value=""></option>
                        @foreach($niveaux as $n)
                            <option value="{{ $n->id }}">{{ $n->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Classes (multi Select2) --}}
                <div class="pe-field" style="grid-column: 1 / -1;">
                    <label class="pe-field-label" for="pe-classes">
                        <i class="fas fa-chalkboard"></i> Classes (multi-sélection)
                    </label>
                    <select name="classe_ids[]" id="pe-classes" class="pe-select" multiple
                            data-placeholder="Sélectionnez une ou plusieurs classes…">
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" data-filiere="{{ $c->filiere->name ?? '' }}">
                                {{ $c->name }}{{ $c->filiere ? ' — ' . $c->filiere->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pe-field-hint">
                        Recherche + clics pour ajouter, X sur chaque tag pour retirer — vide = toutes les classes
                    </div>
                </div>

                {{-- Date début --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-date-debut">
                        <i class="fas fa-calendar-day"></i> Date début
                    </label>
                    <input type="date" name="date_debut" id="pe-date-debut" class="pe-input">
                </div>

                {{-- Date fin --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-date-fin">
                        <i class="fas fa-calendar-check"></i> Date fin
                    </label>
                    <input type="date" name="date_fin" id="pe-date-fin" class="pe-input">
                </div>

                {{-- Modes --}}
                <div class="pe-field" style="grid-column: 1 / -1;">
                    <label class="pe-field-label">
                        <i class="fas fa-money-check-alt"></i> Mode(s) de paiement
                        <button type="button" id="pe-modes-clear" class="pe-checkbox-clear" title="Effacer la sélection">
                            <i class="fas fa-times-circle"></i> Effacer
                        </button>
                    </label>
                    <div class="pe-checkbox-group" id="pe-modes-group">
                        @forelse($modes as $mode)
                            <label class="pe-checkbox">
                                <input type="checkbox" name="modes[]" value="{{ $mode }}">
                                <span>{{ ucfirst($mode) }}</span>
                            </label>
                        @empty
                            <span class="pe-field-hint">Aucun mode disponible</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="pe-actions-row">
                <div class="pe-preview-info" id="pe-preview-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Cliquez sur « Vérifier » pour compter les lignes correspondant aux filtres</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" id="pe-btn-preview" class="pe-action-btn pe-action-btn--secondary">
                        <i class="fas fa-search"></i> Vérifier le volume
                    </button>
                    <button type="button" id="pe-btn-preview-pdf" class="pe-action-btn pe-action-btn--secondary" disabled>
                        <i class="fas fa-eye"></i> Aperçu PDF
                    </button>
                    <button type="submit" id="pe-btn-generate" class="pe-action-btn pe-action-btn--primary" disabled>
                        <i class="fas fa-download"></i> Télécharger
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/fr.js"></script>
<script>
    (function () {
        'use strict';

        // Inline toast helper (utilise window.showToast si déjà défini, sinon fallback)
        function toast(msg, type) {
            type = type || 'info';
            if (typeof window.showToast === 'function') {
                window.showToast(msg, type);
                return;
            }
            let container = document.getElementById('pe-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'pe-toast-container';
                container.style.cssText = 'position:fixed; top:1rem; right:1rem; z-index:10050; display:flex; flex-direction:column; gap:.5rem;';
                document.body.appendChild(container);
            }
            const colors = {
                success: '#10b981', info: '#0453cb', warning: '#f59e0b', error: '#dc2626'
            };
            const el = document.createElement('div');
            el.style.cssText = 'background:' + (colors[type] || colors.info) + '; color:#fff; padding:.85rem 1.1rem; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.15); max-width:380px; font-size:.88rem; font-weight:500;';
            el.textContent = msg;
            container.appendChild(el);
            setTimeout(() => el.remove(), 5000);
        }

        // ─── Select2 templates premium ───
        function initialOf(text) {
            text = (text || '?').trim();
            if (!text) return '?';
            // Prendre l'initiale du dernier mot s'il y a un nom complet
            const parts = text.split(/\s+/);
            return (parts[parts.length - 1][0] || '?').toUpperCase();
        }
        function templateClasse(item) {
            if (!item.id) return item.text;
            const $el = $('<div class="pe-opt-row"></div>');
            const $icon = $('<div class="pe-opt-icon"></div>').text(initialOf(item.text));
            const $text = $('<div class="pe-opt-text"></div>');
            // item.text inclut "Classe — Filière" → on split sur "—"
            const raw = (item.text || '').toString();
            const idx = raw.indexOf('—');
            if (idx > -1) {
                $text.append($('<div class="pe-opt-title"></div>').text(raw.slice(0, idx).trim()));
                $text.append($('<div class="pe-opt-sub"></div>').text(raw.slice(idx + 1).trim()));
            } else {
                $text.append($('<div class="pe-opt-title"></div>').text(raw));
            }
            $el.append($icon, $text);
            return $el;
        }
        function templateClasseSelected(item) {
            if (!item.id) return item.text;
            // Pour les chips, juste le nom de la classe (avant le —)
            const raw = (item.text || '').toString();
            const idx = raw.indexOf('—');
            return idx > -1 ? raw.slice(0, idx).trim() : raw;
        }
        function templateEtudiant(item) {
            if (!item.id) return item.text;
            const $el = $('<div class="pe-opt-row"></div>');
            const $icon = $('<div class="pe-opt-icon"><i class="fas fa-user-graduate"></i></div>');
            const $text = $('<div class="pe-opt-text"></div>');
            // text = "MAT123 - NOM Prénom"
            const raw = (item.text || '').toString();
            const idx = raw.indexOf(' - ');
            if (idx > -1) {
                $text.append($('<div class="pe-opt-title"></div>').text(raw.slice(idx + 3).trim()));
                $text.append($('<div class="pe-opt-sub"></div>').text(raw.slice(0, idx).trim()));
            } else {
                $text.append($('<div class="pe-opt-title"></div>').text(raw));
            }
            $el.append($icon, $text);
            return $el;
        }

        // ─── Init Select2 sur tous les champs ───
        const $etudiant = $('#pe-etudiant');
        const $filiere = $('#pe-filiere');
        const $niveau = $('#pe-niveau');
        const $classes = $('#pe-classes');

        const select2Common = {
            theme: 'bootstrap-5',
            language: 'fr',
            width: '100%',
            allowClear: true,
        };

        $filiere.select2(Object.assign({}, select2Common, {
            placeholder: '— Toutes les filières —',
        }));
        $niveau.select2(Object.assign({}, select2Common, {
            placeholder: '— Tous les niveaux —',
        }));
        $classes.select2(Object.assign({}, select2Common, {
            placeholder: 'Sélectionnez une ou plusieurs classes…',
            templateResult: templateClasse,
            templateSelection: templateClasseSelected,
            closeOnSelect: false,
        }));

        // Étudiant — Select2 AJAX
        $etudiant.select2(Object.assign({}, select2Common, {
            placeholder: 'Rechercher un étudiant (matricule ou nom)…',
            minimumInputLength: 3,
            templateResult: templateEtudiant,
            templateSelection: function (item) {
                if (!item.id) return item.text;
                return $('<span></span>').text(item.text);
            },
            ajax: {
                url: '{{ route('esbtp.api.etudiants.search') }}',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return { q: params.term, page: params.page || 1 };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results || [],
                        pagination: { more: !!(data.pagination && data.pagination.more) },
                    };
                },
                cache: true,
            },
            language: Object.assign({}, $.fn.select2.defaults.defaults.language || {}, {
                inputTooShort: function (args) {
                    var remaining = args.minimum - args.input.length;
                    return 'Entrez ' + remaining + ' caractère' + (remaining > 1 ? 's' : '') + ' supplémentaire' + (remaining > 1 ? 's' : '');
                },
                searching: function () { return 'Recherche…'; },
                noResults: function () { return 'Aucun étudiant trouvé'; },
            }),
        }));

        // ─── Reset all filters ───
        document.getElementById('pe-reset-all').addEventListener('click', function () {
            $etudiant.val(null).trigger('change');
            $filiere.val('').trigger('change');
            $niveau.val('').trigger('change');
            $classes.val(null).trigger('change');
            document.getElementById('pe-date-debut').value = '';
            document.getElementById('pe-date-fin').value = '';
            document.querySelectorAll('input[name="modes[]"]').forEach(cb => cb.checked = false);
            // Format reset à PDF
            const pdfRadio = document.querySelector('input[name="format"][value="pdf"]');
            if (pdfRadio) pdfRadio.checked = true;
            resetPreview();
            toast('Filtres réinitialisés', 'info');
        });

        // Effacer les modes uniquement
        const modesClear = document.getElementById('pe-modes-clear');
        if (modesClear) {
            modesClear.addEventListener('click', function () {
                document.querySelectorAll('input[name="modes[]"]').forEach(cb => cb.checked = false);
                resetPreview();
            });
        }

        // Cohérence dates
        const dateDebut = document.getElementById('pe-date-debut');
        const dateFin = document.getElementById('pe-date-fin');
        if (dateDebut && dateFin) {
            dateDebut.addEventListener('change', () => { dateFin.min = dateDebut.value; });
            dateFin.addEventListener('change', () => { dateDebut.max = dateFin.value; });
        }

        // Format change → reset preview
        document.querySelectorAll('input[name="format"]').forEach(r => r.addEventListener('change', resetPreview));

        // Reset preview state on any filter change (Select2 + checkboxes + dates)
        $etudiant.on('change.select2', resetPreview);
        $filiere.on('change.select2', resetPreview);
        $niveau.on('change.select2', resetPreview);
        $classes.on('change.select2', resetPreview);
        ['pe-date-debut', 'pe-date-fin'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', resetPreview);
        });
        document.querySelectorAll('input[name="modes[]"]').forEach(cb => cb.addEventListener('change', resetPreview));

        function resetPreview() {
            document.getElementById('pe-btn-generate').disabled = true;
            const info = document.getElementById('pe-preview-info');
            info.className = 'pe-preview-info';
            info.querySelector('span').textContent = 'Filtres modifiés — relancez « Vérifier »';
        }

        // Preview AJAX
        document.getElementById('pe-btn-preview').addEventListener('click', function () {
            const form = document.getElementById('pe-form');
            const fd = new FormData(form);

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification…';

            fetch('{{ route('esbtp.paiements.export-detaille.preview') }}', {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async r => {
                const json = await r.json().catch(() => ({}));
                return { ok: r.ok, status: r.status, json: json };
            })
            .then(({ ok, status, json }) => {
                const info = document.getElementById('pe-preview-info');
                const generateBtn = document.getElementById('pe-btn-generate');

                const previewPdfBtn = document.getElementById('pe-btn-preview-pdf');
                const formatPdf = document.querySelector('input[name="format"]:checked')?.value === 'pdf';
                if (ok && json.success) {
                    info.className = 'pe-preview-info is-success';
                    info.querySelector('span').textContent = json.message || (json.count + ' lignes prêtes');
                    generateBtn.disabled = false;
                    previewPdfBtn.disabled = !formatPdf;
                    toast(json.message || 'Prévisualisation OK', 'success');
                } else {
                    info.className = 'pe-preview-info is-error';
                    info.querySelector('span').textContent = json.message || ('Erreur ' + status);
                    generateBtn.disabled = true;
                    previewPdfBtn.disabled = true;
                    toast(json.message || 'Erreur de prévisualisation', 'error');
                }
            })
            .catch((err) => {
                toast('Erreur réseau : ' + err.message, 'error');
                document.getElementById('pe-btn-generate').disabled = true;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search"></i> Vérifier';
            });
        });

        // Submit handler — re-preview before submit (safety)
        document.getElementById('pe-form').addEventListener('submit', function (e) {
            const generateBtn = document.getElementById('pe-btn-generate');
            if (generateBtn.disabled) {
                e.preventDefault();
                toast('Veuillez d\'abord cliquer sur « Vérifier le volume »', 'warning');
                return;
            }
        });

        // Aperçu PDF — submit le form vers preview-pdf (nouvelle tab)
        document.getElementById('pe-btn-preview-pdf').addEventListener('click', function () {
            const form = document.getElementById('pe-form');
            const originalAction = form.action;
            const originalTarget = form.target;
            form.action = '{{ route("esbtp.paiements.export-detaille.preview-pdf") }}';
            form.target = '_blank';
            // Force format=pdf temporairement
            const formatInput = document.querySelector('input[name="format"][value="pdf"]');
            const wasChecked = formatInput?.checked;
            if (formatInput && !wasChecked) formatInput.checked = true;
            form.submit();
            // Restaurer (le submit a déjà été lancé)
            form.action = originalAction;
            form.target = originalTarget;
            if (formatInput && !wasChecked) formatInput.checked = false;
        });
    })();
</script>
@endpush
