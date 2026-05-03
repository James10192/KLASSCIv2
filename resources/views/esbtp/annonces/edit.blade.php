@extends('layouts.app')

@section('title', 'Modifier l\'annonce — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

<style>
/* ============================================================
   /esbtp/annonces/edit — Namespace ac-* (Annonce Composer)
   Réutilise le même design system que create.blade.php pour
   préserver la cohérence visuelle entre création / modification.
   ============================================================ */

/* ----- Layout composer (form 2 colonnes) ----- */
.ac-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 1.25rem;
    align-items: start;
}
.ac-main { display: grid; gap: 1.25rem; min-width: 0; }
.ac-aside {
    display: grid; gap: 1rem;
    position: sticky; top: 92px;
    align-self: start;
}

@media (max-width: 1199.98px) {
    .ac-grid { grid-template-columns: 1fr; }
    .ac-aside { position: static; top: auto; }
}

/* ----- Card premium ----- */
.ac-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: visible;
    transition: box-shadow .2s ease, border-color .2s ease;
}
.ac-card + .ac-card { margin-top: 0; }
.ac-card:hover { border-color: #cbd5e1; }
.ac-card-head {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}
.ac-card-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}
.ac-card-title { font-size: .95rem; font-weight: 600; color: #0f172a; line-height: 1.2; }
.ac-card-sub { font-size: .78rem; color: #64748b; margin-top: 2px; }
.ac-card-body { padding: 1.25rem; display: grid; gap: 1.1rem; }

/* ----- Form fields uniformisés ----- */
.ac-field { display: flex; flex-direction: column; gap: .4rem; min-width: 0; }
.ac-label {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .82rem; font-weight: 600;
    color: #0f172a;
}
.ac-label .ac-req { color: #dc2626; font-weight: 700; }
.ac-help { font-size: .72rem; color: #64748b; line-height: 1.4; }
.ac-error {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .75rem; color: #dc2626; font-weight: 500;
}
.ac-error::before { content: "\26A0"; font-size: .85rem; }

.ac-input,
.ac-textarea {
    width: 100%;
    padding: .7rem .9rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #1e293b;
    font-size: .88rem; font-weight: 500;
    transition: border-color .15s, box-shadow .15s;
    line-height: 1.4;
}
.ac-textarea {
    resize: vertical;
    min-height: 160px;
    font-family: inherit;
}
.ac-input::placeholder,
.ac-textarea::placeholder {
    color: #94a3b8; font-weight: 400;
}
.ac-input:hover,
.ac-textarea:hover { border-color: #cbd5e1; }
.ac-input:focus,
.ac-textarea:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.ac-input.is-invalid,
.ac-textarea.is-invalid {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,.1);
}

/* Counter sous input/textarea */
.ac-counter {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .72rem; color: #64748b;
}
.ac-counter strong { font-weight: 600; color: #0f172a; }
.ac-counter--warn strong { color: #d97706; }
.ac-counter--danger strong { color: #dc2626; }

/* ----- File upload zone (avec preview existant) ----- */
.ac-file {
    position: relative;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    padding: 1.4rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s, transform .15s;
}
.ac-file:hover {
    border-color: #0453cb;
    background: linear-gradient(180deg, #eff6ff, #dbeafe);
}
.ac-file--has-file {
    border-style: solid;
    border-color: #0453cb;
    background: #eff6ff;
}
.ac-file input[type="file"] {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    opacity: 0; cursor: pointer;
}
.ac-file-icon {
    width: 44px; height: 44px;
    margin: 0 auto .5rem;
    border-radius: 12px;
    background: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    color: #0453cb; font-size: 1.1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.06);
}
.ac-file-label { font-size: .85rem; font-weight: 600; color: #0f172a; }
.ac-file-hint { font-size: .72rem; color: #64748b; margin-top: .2rem; }
.ac-file-pill {
    display: none;
    margin-top: .6rem;
    padding: .4rem .75rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #cbd5e1;
    font-size: .78rem; color: #0453cb; font-weight: 500;
}
.ac-file--has-file .ac-file-pill { display: inline-flex; align-items: center; gap: .4rem; }

/* Existing attachment preview row (above the dropzone) */
.ac-existing-file {
    display: flex; align-items: center; gap: .75rem;
    padding: .65rem .85rem;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    background: #eff6ff;
    margin-bottom: .5rem;
}
.ac-existing-file-icon {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: #fff;
    color: #0453cb;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    border: 1px solid #bfdbfe;
}
.ac-existing-file-meta { flex: 1; min-width: 0; }
.ac-existing-file-name {
    font-size: .82rem; font-weight: 600; color: #0f172a;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ac-existing-file-sub { font-size: .72rem; color: #1e40af; }
.ac-existing-file-actions { display: inline-flex; gap: .35rem; flex-shrink: 0; }

/* ----- Radio cards (audience type) ----- */
.ac-audience {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .65rem;
}
@media (max-width: 768px) {
    .ac-audience { grid-template-columns: 1fr; }
}
.ac-audience-opt {
    position: relative;
    display: flex; flex-direction: column; gap: .3rem;
    padding: .9rem 1rem;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.ac-audience-opt:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.ac-audience-opt input[type="radio"] {
    position: absolute; opacity: 0; pointer-events: none;
}
.ac-audience-opt-head {
    display: flex; align-items: center; gap: .5rem;
}
.ac-audience-opt-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #f1f5f9;
    display: inline-flex; align-items: center; justify-content: center;
    color: #475569; font-size: .85rem;
    transition: background .15s, color .15s;
}
.ac-audience-opt-name {
    font-size: .85rem; font-weight: 600; color: #0f172a;
}
.ac-audience-opt-desc {
    font-size: .72rem; color: #64748b; line-height: 1.35;
}
.ac-audience-opt-tick {
    position: absolute; top: 12px; right: 12px;
    width: 18px; height: 18px;
    border-radius: 50%;
    border: 1.5px solid #cbd5e1;
    background: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    color: transparent; font-size: .55rem;
    transition: border-color .15s, background .15s, color .15s;
}
.ac-audience-opt input[type="radio"]:checked ~ .ac-audience-opt-tick {
    border-color: #0453cb;
    background: #0453cb;
    color: #fff;
}
.ac-audience-opt input[type="radio"]:checked ~ .ac-audience-opt-head .ac-audience-opt-icon {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
}
.ac-audience-opt:has(input[type="radio"]:checked) {
    border-color: #0453cb;
    background: linear-gradient(180deg, #eff6ff, #ffffff);
    box-shadow: 0 4px 14px rgba(4,83,203,.12);
}
.ac-audience-opt input[type="radio"]:focus-visible ~ .ac-audience-opt-tick {
    box-shadow: 0 0 0 3px rgba(4,83,203,.20);
}

/* ----- Pickers (classes / étudiants summary cards) ----- */
.ac-picker {
    display: none;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: linear-gradient(180deg, #f8fafc, #ffffff);
    padding: .85rem 1rem;
}
.ac-picker--show { display: block; }
.ac-picker-row {
    display: flex; align-items: center; gap: .75rem;
    flex-wrap: wrap;
}
.ac-picker-meta { flex: 1; min-width: 0; }
.ac-picker-title {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .82rem; font-weight: 600; color: #0f172a;
}
.ac-picker-title i { color: #0453cb; }
.ac-picker-summary {
    margin-top: 2px;
    font-size: .75rem; color: #64748b;
}
.ac-picker-count-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .2rem .55rem;
    border-radius: 999px;
    background: #0453cb;
    color: #fff; font-size: .72rem; font-weight: 600;
    margin-left: .35rem;
}
.ac-picker-count-badge--empty {
    background: #f1f5f9;
    color: #64748b;
}

/* ----- Notice info / warning ----- */
.ac-notice {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: .75rem 1rem;
    background: linear-gradient(180deg, #eff6ff, #dbeafe);
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    color: #1e40af;
}
.ac-notice i {
    color: #0453cb;
    font-size: 1rem;
    margin-top: 2px;
    flex-shrink: 0;
}
.ac-notice-text { font-size: .8rem; line-height: 1.45; }
.ac-notice-text strong { font-weight: 600; }

.ac-notice--warn {
    background: linear-gradient(180deg, #fffbeb, #fef3c7);
    border-color: #fcd34d;
    color: #92400e;
}
.ac-notice--warn i { color: #d97706; }

/* ----- Status pill (en haut du formulaire) ----- */
.ac-status-row {
    display: flex; align-items: center; gap: .5rem;
    flex-wrap: wrap;
    margin-bottom: .5rem;
}
.ac-status-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .3rem .7rem;
    border-radius: 999px;
    font-size: .75rem; font-weight: 600;
    border: 1px solid transparent;
}
.ac-status-pill--draft {
    background: #f1f5f9;
    color: #475569;
    border-color: #e2e8f0;
}
.ac-status-pill--published {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.ac-status-pill--expired {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.ac-status-pill--urgent {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.ac-status-pill--important {
    background: #fffbeb;
    color: #92400e;
    border-color: #fcd34d;
}

/* ----- Sidebar actions ----- */
.ac-actions {
    display: grid; gap: .5rem;
}
.ac-actions .ac-btn { width: 100%; justify-content: center; }
.ac-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    padding: .65rem 1rem;
    border-radius: 10px;
    font-size: .85rem; font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: transform .12s ease, box-shadow .15s, background .15s, border-color .15s;
    line-height: 1.2;
    text-decoration: none;
}
.ac-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(4,83,203,.25);
}
.ac-btn-primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    box-shadow: 0 6px 18px rgba(4,83,203,.25);
}
.ac-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(4,83,203,.32);
    color: #fff;
}
.ac-btn-secondary {
    background: #fff;
    border-color: #e2e8f0;
    color: #0f172a;
}
.ac-btn-secondary:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    color: #0f172a;
}
.ac-btn-ghost {
    background: transparent;
    color: #64748b;
}
.ac-btn-ghost:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.ac-btn-danger {
    background: #fff;
    border-color: #fecaca;
    color: #b91c1c;
}
.ac-btn-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #991b1b;
}
.ac-btn-success {
    background: linear-gradient(135deg, #047857, #10b981);
    color: #fff;
    box-shadow: 0 6px 18px rgba(16,185,129,.25);
}
.ac-btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(16,185,129,.32);
    color: #fff;
}
.ac-actions-help {
    margin-top: .5rem;
    font-size: .72rem; color: #64748b; line-height: 1.4;
}

/* ----- Tips card sidebar ----- */
.ac-tips { display: grid; gap: .55rem; }
.ac-tip {
    display: flex; align-items: flex-start; gap: .6rem;
    padding: .55rem .65rem;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
}
.ac-tip i {
    width: 22px; height: 22px;
    flex-shrink: 0;
    border-radius: 6px;
    background: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    color: #0453cb; font-size: .7rem;
    border: 1px solid #e2e8f0;
}
.ac-tip-text { font-size: .76rem; color: #475569; line-height: 1.45; }
.ac-tip-text strong { color: #0f172a; }

/* ----- Modals premium ----- */
.ac-modal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(15,23,42,.20);
    overflow: hidden;
}
.ac-modal .modal-header {
    background: linear-gradient(135deg, #0a3d8f, #0453cb 60%, #3b7ddb);
    border-bottom: none;
    padding: 1.1rem 1.25rem;
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem;
    color: #fff;
}
.ac-modal--danger .modal-header {
    background: linear-gradient(135deg, #7f1d1d, #b91c1c 60%, #dc2626);
}
.ac-modal-titlewrap { display: flex; flex-direction: column; gap: 2px; }
.ac-modal-title {
    display: inline-flex; align-items: center; gap: .55rem;
    font-size: 1rem; font-weight: 700; color: #fff;
}
.ac-modal-sub { font-size: .75rem; color: rgba(255,255,255,.75); }
.ac-modal-actions { display: flex; align-items: center; gap: .5rem; }
.ac-modal-btn-glass {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff;
    border-radius: 8px;
    padding: .35rem .7rem;
    font-size: .76rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: .35rem;
    cursor: pointer; transition: background .15s;
}
.ac-modal-btn-glass:hover {
    background: rgba(255,255,255,.25);
    color: #fff;
}
.ac-modal-close {
    background: rgba(255,255,255,.15);
    border: none; color: #fff;
    width: 30px; height: 30px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s;
}
.ac-modal-close:hover { background: rgba(255,255,255,.30); }
.ac-modal .modal-body {
    padding: 1.25rem;
    max-height: 70vh;
    overflow: auto;
}
.ac-modal .modal-footer {
    border-top: 1px solid #f1f5f9;
    padding: .85rem 1.25rem;
    background: #fafbfc;
    justify-content: space-between;
    gap: .5rem;
}

/* Filtres bar inside modals */
.ac-filters {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: .65rem;
    margin-bottom: 1rem;
    padding: .85rem 1rem;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
}
.ac-filters--single { grid-template-columns: 1fr auto; }
@media (max-width: 768px) {
    .ac-filters,
    .ac-filters--single { grid-template-columns: 1fr; }
}
.ac-filters select {
    width: 100%;
    padding: .55rem .8rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #1e293b;
    font-size: .82rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 10px center;
    background-repeat: no-repeat;
    padding-right: 40px;
}
.ac-filters select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.ac-filters-info {
    grid-column: 1 / -1;
    font-size: .72rem; color: #64748b;
    margin-top: -.25rem;
}

/* Bulk toolbar for modals */
.ac-bulk {
    display: flex; align-items: center; justify-content: space-between;
    gap: .5rem; flex-wrap: wrap;
    margin-bottom: .75rem;
}
.ac-bulk-info {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .8rem; color: #475569;
}
.ac-bulk-info strong { color: #0f172a; }
.ac-bulk-actions { display: inline-flex; gap: .4rem; flex-wrap: wrap; }
.ac-bulk-btn {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    border-radius: 8px;
    padding: .35rem .7rem;
    font-size: .76rem; font-weight: 500;
    display: inline-flex; align-items: center; gap: .35rem;
    cursor: pointer; transition: border-color .15s, background .15s, color .15s;
}
.ac-bulk-btn:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: #eff6ff;
}
.ac-bulk-btn--ghost {
    border-color: transparent;
    color: #64748b;
}
.ac-bulk-btn--ghost:hover {
    border-color: #fecaca;
    background: #fef2f2;
    color: #dc2626;
}

/* ----- Choices.js — restyling pour matcher KLASSCI ----- */
.ac-modal .choices { margin-bottom: 0; }
.ac-modal .choices__inner {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    min-height: 50px;
    padding: .55rem .65rem;
    transition: border-color .15s, box-shadow .15s;
    box-shadow: none;
    font-size: .85rem;
}
.ac-modal .choices.is-focused .choices__inner,
.ac-modal .choices.is-open .choices__inner {
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.ac-modal .choices__list--multiple .choices__item {
    background: linear-gradient(135deg, #0453cb, #3b7ddb) !important;
    border: none !important;
    border-radius: 999px !important;
    color: #fff !important;
    font-size: .76rem !important;
    font-weight: 600 !important;
    padding: .25rem .65rem !important;
    margin: .15rem .25rem .15rem 0 !important;
    box-shadow: 0 2px 6px rgba(4,83,203,.20);
}
.ac-modal .choices__list--multiple .choices__item .choices__button {
    background-image: none !important;
    background: rgba(255,255,255,.25) !important;
    border-radius: 50% !important;
    width: 16px !important; height: 16px !important;
    margin: 0 0 0 .4rem !important; padding: 0 !important;
    color: #fff !important;
    border-left: none !important;
    position: relative;
}
.ac-modal .choices__list--multiple .choices__item .choices__button::before {
    content: "\00D7";
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; line-height: 1;
}
.ac-modal .choices__list--dropdown {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15,23,42,.10);
    margin-top: 4px;
    z-index: 9999;
}
.ac-modal .choices__list--dropdown .choices__item--selectable {
    padding: .55rem .85rem;
    font-size: .85rem;
    border-bottom: none;
    border-left: 3px solid transparent;
    border-radius: 0;
    margin: 0;
    color: #1e293b;
    transition: background .12s, color .12s, border-color .12s;
}
.ac-modal .choices__list--dropdown .choices__item--selectable:hover,
.ac-modal .choices__list--dropdown .choices__item--selectable.is-highlighted {
    background: #eff6ff !important;
    color: #0453cb !important;
    border-left-color: #0453cb;
    transform: none;
}
.ac-modal .choices__input {
    background: transparent;
    color: #1e293b;
    font-size: .85rem;
    padding: 0 .25rem;
}
.ac-modal .choices__placeholder {
    color: #94a3b8;
    opacity: 1;
    font-style: normal;
    font-weight: 400;
}

/* Hide the underlying native multi-select (kept for form compat) */
.ac-multi-native {
    position: absolute !important;
    width: 1px !important; height: 1px !important;
    opacity: 0 !important;
    pointer-events: none !important;
    clip: rect(0 0 0 0) !important;
}

/* ----- Animations subtiles ----- */
.ac-fade-enter {
    animation: acFadeIn .2s ease-out;
}
@keyframes acFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ----- Mobile polish ----- */
@media (max-width: 768px) {
    .ac-card-body { padding: 1rem; gap: .9rem; }
    .ac-card-head { padding: .85rem 1rem; }
    .ac-modal .modal-body { max-height: 80vh; padding: 1rem; }
}
</style>
@endsection

@php
    // Helpers d'état pour la vue
    $isPublished = (bool) $annonce->is_published;
    $isExpired   = $annonce->isExpired();
    $oldType     = old('type', $annonce->type ?? 'general');
    $oldClasses  = old('classes', $annonce->classes->pluck('id')->map(fn ($id) => (string) $id)->all());
    $oldEtuds    = old('etudiants', $annonce->etudiants->pluck('id')->map(fn ($id) => (string) $id)->all());
    $existingFile = $annonce->piece_jointe ?? null;
    $existingFileName = $existingFile ? basename($existingFile) : null;
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- Header standard (rule premium-redesign : pages edit utilisent dashboard-header) --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-pen-to-square me-2"></i>Modifier l'annonce</h1>
                <p class="header-subtitle">Mettez à jour le contenu, le ciblage ou les paramètres de cette annonce.</p>
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

        {{-- Bandeau statut courant --}}
        <div class="ac-status-row">
            @if($isExpired)
                <span class="ac-status-pill ac-status-pill--expired">
                    <i class="fas fa-hourglass-end"></i>Annonce expirée
                </span>
            @elseif($isPublished)
                <span class="ac-status-pill ac-status-pill--published">
                    <i class="fas fa-check-circle"></i>Publiée
                </span>
            @else
                <span class="ac-status-pill ac-status-pill--draft">
                    <i class="fas fa-file-lines"></i>Brouillon
                </span>
            @endif

            @if((int) $annonce->priorite === 2)
                <span class="ac-status-pill ac-status-pill--urgent">
                    <i class="fas fa-bolt"></i>Priorité urgente
                </span>
            @elseif((int) $annonce->priorite === 1)
                <span class="ac-status-pill ac-status-pill--important">
                    <i class="fas fa-thumbtack"></i>Importante
                </span>
            @endif

            @if($annonce->date_publication)
                <span class="ac-status-pill ac-status-pill--draft" style="background:#fff;border-color:#e2e8f0;">
                    <i class="fas fa-calendar-day"></i>
                    Créée le {{ $annonce->created_at?->format('d/m/Y') }}
                </span>
            @endif
        </div>

        @if($isExpired)
            <div class="ac-notice ac-notice--warn" style="margin-bottom:1rem;">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="ac-notice-text">
                    <strong>Cette annonce est expirée.</strong>
                    Elle n'est plus visible par les étudiants. Vous pouvez la consulter ou la supprimer, mais plus la modifier.
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-modern error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Erreur de validation</h4>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-modern error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <form action="{{ route('esbtp.annonces.update', $annonce) }}" method="POST"
              enctype="multipart/form-data" id="annonceForm">
            @csrf
            @method('PUT')

            <div class="ac-grid">

                {{-- =================== COLONNE PRINCIPALE =================== --}}
                <div class="ac-main">

                    {{-- ===== Carte 1 : Message ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-pen-nib"></i></span>
                            <div>
                                <div class="ac-card-title">Composer le message</div>
                                <div class="ac-card-sub">Objet, contenu et pièce jointe optionnelle</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-field">
                                <label for="titre" class="ac-label">
                                    Objet de l'annonce <span class="ac-req">*</span>
                                </label>
                                <input type="text" id="titre" name="titre"
                                       class="ac-input @error('titre') is-invalid @enderror"
                                       value="{{ old('titre', $annonce->titre) }}"
                                       placeholder="Ex : Conseil pédagogique du 15 mai, Rentrée 2026..."
                                       maxlength="255" required>
                                <div class="ac-counter" data-counter-for="titre" data-max="255">
                                    Soyez clair et concis &middot; <strong><span data-counter-current>0</span></strong>/255
                                </div>
                                @error('titre')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label for="contenu" class="ac-label">
                                    Corps du message <span class="ac-req">*</span>
                                </label>
                                <textarea id="contenu" name="contenu"
                                          class="ac-textarea @error('contenu') is-invalid @enderror"
                                          rows="8" required
                                          placeholder="Rédigez votre annonce. Soyez précis sur la date, le lieu et le public concerné.">{{ old('contenu', $annonce->contenu) }}</textarea>
                                <div class="ac-counter" data-counter-for="contenu">
                                    <span><strong><span data-counter-current>0</span></strong> caractères</span>
                                    <span class="ac-help">Markdown léger non interprété &mdash; texte brut</span>
                                </div>
                                @error('contenu')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label class="ac-label">Pièce jointe (optionnelle)</label>

                                @if($existingFile)
                                    <div class="ac-existing-file" id="ac-existing-file">
                                        <div class="ac-existing-file-icon"><i class="fas fa-paperclip"></i></div>
                                        <div class="ac-existing-file-meta">
                                            <div class="ac-existing-file-name" title="{{ $existingFileName }}">{{ $existingFileName }}</div>
                                            <div class="ac-existing-file-sub">Fichier actuellement attaché à l'annonce</div>
                                        </div>
                                        <div class="ac-existing-file-actions">
                                            <a href="{{ \Storage::disk('public')->url($existingFile) }}"
                                               target="_blank" rel="noopener"
                                               class="ac-btn ac-btn-secondary"
                                               style="padding:.4rem .65rem; font-size:.78rem;">
                                                <i class="fas fa-external-link-alt"></i>Ouvrir
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                <label class="ac-file" id="ac-file-zone" for="piece_jointe">
                                    <input type="file" id="piece_jointe" name="piece_jointe"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    <div class="ac-file-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="ac-file-label" id="ac-file-label">
                                        @if($existingFile)
                                            Cliquez pour remplacer le fichier
                                        @else
                                            Cliquez ou déposez un fichier
                                        @endif
                                    </div>
                                    <div class="ac-file-hint">PDF &middot; Word &middot; Excel &middot; Image &mdash; 5 MB max</div>
                                    <div class="ac-file-pill" id="ac-file-pill">
                                        <i class="fas fa-paperclip"></i><span id="ac-file-name"></span>
                                    </div>
                                </label>
                                @error('piece_jointe')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- ===== Carte 2 : Ciblage ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-users"></i></span>
                            <div>
                                <div class="ac-card-title">Destinataires</div>
                                <div class="ac-card-sub">À qui envoyer cette annonce ?</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-field">
                                <span class="ac-label">Type de diffusion <span class="ac-req">*</span></span>
                                <div class="ac-audience">
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="general" required
                                               {{ $oldType === 'general' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-globe"></i></span>
                                            <span class="ac-audience-opt-name">Tous les étudiants</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Diffusion générale à l'ensemble de l'école.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="classe" required
                                               {{ $oldType === 'classe' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-chalkboard"></i></span>
                                            <span class="ac-audience-opt-name">Classes ciblées</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Une ou plusieurs classes spécifiques.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="etudiant" required
                                               {{ $oldType === 'etudiant' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-user-graduate"></i></span>
                                            <span class="ac-audience-opt-name">Étudiants nominatifs</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Sélection individuelle d'étudiants.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                </div>
                                @error('type')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            {{-- Picker Classes --}}
                            <div id="classes_picker" class="ac-picker">
                                <div class="ac-picker-row">
                                    <div class="ac-picker-meta">
                                        <span class="ac-picker-title">
                                            <i class="fas fa-chalkboard"></i> Classes destinataires
                                            <span class="ac-picker-count-badge ac-picker-count-badge--empty" id="classes_count_badge">0</span>
                                        </span>
                                        <div class="ac-picker-summary" id="classes_summary">Aucune classe sélectionnée</div>
                                    </div>
                                    <button type="button" class="ac-btn ac-btn-secondary"
                                            data-bs-toggle="modal" data-bs-target="#classesModal">
                                        <i class="fas fa-layer-group"></i>Choisir les classes
                                    </button>
                                </div>
                                @error('classes')<div class="ac-error mt-2">{{ $message }}</div>@enderror
                            </div>

                            {{-- Picker Étudiants --}}
                            <div id="etudiants_picker" class="ac-picker">
                                <div class="ac-picker-row">
                                    <div class="ac-picker-meta">
                                        <span class="ac-picker-title">
                                            <i class="fas fa-user-graduate"></i> Étudiants destinataires
                                            <span class="ac-picker-count-badge ac-picker-count-badge--empty" id="etudiants_count_badge">0</span>
                                        </span>
                                        <div class="ac-picker-summary" id="etudiants_summary">Aucun étudiant sélectionné</div>
                                    </div>
                                    <button type="button" class="ac-btn ac-btn-secondary"
                                            data-bs-toggle="modal" data-bs-target="#etudiantsModal">
                                        <i class="fas fa-user-check"></i>Choisir les étudiants
                                    </button>
                                </div>
                                @error('etudiants')<div class="ac-error mt-2">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                </div>

                {{-- =================== COLONNE LATÉRALE =================== --}}
                <aside class="ac-aside">

                    {{-- ===== Sidebar — Actions ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-paper-plane"></i></span>
                            <div>
                                <div class="ac-card-title">Actions</div>
                                <div class="ac-card-sub">Mettre à jour ou publier</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            @if($isPublished)
                                <div class="ac-notice">
                                    <i class="fas fa-check-circle"></i>
                                    <div class="ac-notice-text">
                                        <strong>Annonce déjà publiée.</strong>
                                        Vos modifications seront visibles immédiatement par les étudiants ciblés.
                                    </div>
                                </div>
                            @else
                                <div class="ac-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <div class="ac-notice-text">
                                        <strong>Annonce en brouillon.</strong>
                                        Elle reste invisible aux étudiants tant que vous ne cliquez pas
                                        sur &laquo;&nbsp;Publier maintenant&nbsp;&raquo;.
                                    </div>
                                </div>
                            @endif

                            <div class="ac-actions">
                                <button type="submit" name="action" value="update" class="ac-btn ac-btn-primary">
                                    <i class="fas fa-save"></i>
                                    @if($isPublished)
                                        Enregistrer les modifications
                                    @else
                                        Mettre à jour le brouillon
                                    @endif
                                </button>

                                @if(!$isPublished)
                                    <button type="submit" name="action" value="publish" class="ac-btn ac-btn-success">
                                        <i class="fas fa-paper-plane"></i>Publier maintenant
                                    </button>
                                @endif

                                <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="ac-btn ac-btn-ghost">
                                    <i class="fas fa-times"></i>Annuler les modifications
                                </a>
                            </div>

                            <div class="ac-actions-help">
                                <i class="fas fa-clock me-1"></i>
                                Les annonces publiées peuvent encore être modifiées dans les 15 minutes qui suivent leur publication.
                            </div>

                        </div>
                    </div>

                    {{-- ===== Sidebar — Publication ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-cog"></i></span>
                            <div>
                                <div class="ac-card-title">Paramètres de publication</div>
                                <div class="ac-card-sub">Date d'expiration &amp; priorité</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-field">
                                <label for="date_expiration" class="ac-label">
                                    Date d'expiration <span class="ac-req">*</span>
                                </label>
                                <input type="datetime-local" id="date_expiration" name="date_expiration"
                                       class="ac-input @error('date_expiration') is-invalid @enderror"
                                       value="{{ old('date_expiration', $annonce->date_expiration?->format('Y-m-d\TH:i')) }}"
                                       required>
                                <div class="ac-help">L'annonce sera retirée des fils étudiants après cette date.</div>
                                @error('date_expiration')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label for="priorite" class="ac-label">Niveau d'urgence</label>
                                <x-au-select
                                    name="priorite"
                                    :value="(string) old('priorite', $annonce->priorite ?? '0')"
                                    icon="fa-flag"
                                    :options="[
                                        '0' => 'Normale — visible dans le fil',
                                        '1' => 'Importante — épinglée en haut',
                                        '2' => 'Urgente — notification renforcée',
                                    ]" />
                                @error('priorite')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- ===== Sidebar — Zone de danger ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon" style="background:linear-gradient(135deg,#b91c1c,#dc2626);box-shadow:0 4px 12px rgba(220,38,38,.25);">
                                <i class="fas fa-triangle-exclamation"></i>
                            </span>
                            <div>
                                <div class="ac-card-title">Zone sensible</div>
                                <div class="ac-card-sub">Suppression définitive</div>
                            </div>
                        </div>
                        <div class="ac-card-body">
                            <p style="margin:0; font-size:.78rem; color:#475569; line-height:1.5;">
                                Supprimer cette annonce retire également tous les liens avec les destinataires
                                et les statuts de lecture. Cette action est irréversible.
                            </p>
                            <button type="button" class="ac-btn ac-btn-danger" id="ac-open-delete">
                                <i class="fas fa-trash"></i>Supprimer cette annonce
                            </button>
                        </div>
                    </div>

                </aside>
            </div>

            {{-- =================== MODAL CLASSES =================== --}}
            <div class="modal fade ac-modal" id="classesModal" tabindex="-1"
                 aria-labelledby="classesModalLabel" aria-hidden="true" data-bs-backdrop="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="ac-modal-titlewrap">
                                <span class="ac-modal-title" id="classesModalLabel">
                                    <i class="fas fa-chalkboard"></i> Sélectionner les classes
                                </span>
                                <span class="ac-modal-sub">Filtrez par filière et niveau, puis cochez les classes destinataires.</span>
                            </div>
                            <div class="ac-modal-actions">
                                <button type="button" class="ac-modal-btn-glass" id="select_all_classes">
                                    <i class="fas fa-check-double"></i>Tout sélectionner
                                </button>
                                <button type="button" class="ac-modal-btn-glass" id="clear_classes_selection">
                                    <i class="fas fa-eraser"></i>Vider
                                </button>
                                <button type="button" class="ac-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="modal-body">

                            <div class="ac-filters">
                                <select id="filiere_filter" aria-label="Filtrer par filière">
                                    <option value="">Toutes les filières</option>
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                    @endforeach
                                </select>
                                <select id="niveau_filter" aria-label="Filtrer par niveau">
                                    <option value="">Tous les niveaux</option>
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="ac-bulk-btn reset-filters" title="Réinitialiser les filtres">
                                    <i class="fas fa-rotate-left"></i>Réinitialiser
                                </button>
                            </div>

                            <div class="ac-bulk">
                                <div class="ac-bulk-info">
                                    <i class="fas fa-list"></i>
                                    <span><strong id="classes_total_visible">{{ $classes->count() }}</strong> classes disponibles</span>
                                </div>
                            </div>

                            <div class="ac-field">
                                <label for="classes" class="ac-label">
                                    Classes destinataires <span class="ac-req">*</span>
                                </label>
                                <select class="ac-multi-native @error('classes') is-invalid @enderror"
                                        id="classes" name="classes[]" multiple>
                                    @foreach($classes as $classe)
                                        @php
                                            $isSelected = in_array((string) $classe->id, array_map('strval', (array) $oldClasses), true);
                                        @endphp
                                        <option value="{{ $classe->id }}"
                                                data-filiere="{{ $classe->filiere_id }}"
                                                data-niveau="{{ $classe->niveau_etude_id }}"
                                                data-current-count="{{ $classe->current_inscriptions_count ?? 0 }}"
                                                {{ $isSelected ? 'selected' : '' }}>
                                            {{ $classe->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('classes')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                        <div class="modal-footer">
                            <span class="ac-help"><i class="fas fa-info-circle me-1"></i>Vous pouvez sélectionner jusqu'à 20 classes.</span>
                            <button type="button" class="ac-btn ac-btn-primary" data-bs-dismiss="modal">
                                <i class="fas fa-check"></i>Terminer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =================== MODAL ÉTUDIANTS =================== --}}
            <div class="modal fade ac-modal" id="etudiantsModal" tabindex="-1"
                 aria-labelledby="etudiantsModalLabel" aria-hidden="true" data-bs-backdrop="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="ac-modal-titlewrap">
                                <span class="ac-modal-title" id="etudiantsModalLabel">
                                    <i class="fas fa-user-graduate"></i> Sélectionner les étudiants
                                </span>
                                <span class="ac-modal-sub">Filtrez par classe puis cochez les étudiants destinataires.</span>
                            </div>
                            <div class="ac-modal-actions">
                                <button type="button" class="ac-modal-btn-glass" id="select_all_etudiants">
                                    <i class="fas fa-check-double"></i>Tout sélectionner
                                </button>
                                <button type="button" class="ac-modal-btn-glass" id="clear_etudiants_selection">
                                    <i class="fas fa-eraser"></i>Vider
                                </button>
                                <button type="button" class="ac-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="modal-body">

                            <div class="ac-filters ac-filters--single">
                                <select id="classe_etudiant_filter" aria-label="Filtrer par classe">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="ac-bulk-btn reset-filters" title="Réinitialiser le filtre">
                                    <i class="fas fa-rotate-left"></i>Réinitialiser
                                </button>
                            </div>

                            <div class="ac-bulk">
                                <div class="ac-bulk-info">
                                    <i class="fas fa-list"></i>
                                    <span id="etudiants-info"><strong>{{ $etudiants->count() }}</strong> étudiant(s) disponible(s)</span>
                                </div>
                            </div>

                            <div class="ac-field">
                                <label for="etudiants" class="ac-label">
                                    Étudiants destinataires <span class="ac-req">*</span>
                                </label>
                                <select class="ac-multi-native @error('etudiants') is-invalid @enderror"
                                        id="etudiants" name="etudiants[]" multiple>
                                    @foreach($etudiants as $etudiant)
                                        @php
                                            $isSelected = in_array((string) $etudiant->id, array_map('strval', (array) $oldEtuds), true);
                                            $currentInscriptionId = optional($etudiant->inscriptions->first())->classe_id;
                                        @endphp
                                        <option value="{{ $etudiant->id }}"
                                                data-classe="{{ $currentInscriptionId }}"
                                                data-current-year="1"
                                                {{ $isSelected ? 'selected' : '' }}>
                                            {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('etudiants')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                        <div class="modal-footer">
                            <span class="ac-help"><i class="fas fa-info-circle me-1"></i>Sélection multiple jusqu'à 50 étudiants.</span>
                            <button type="button" class="ac-btn ac-btn-primary" data-bs-dismiss="modal">
                                <i class="fas fa-check"></i>Terminer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        {{-- Form delete (en-dehors du form principal pour éviter les nested forms) --}}
        <form action="{{ route('esbtp.annonces.destroy', $annonce) }}" method="POST" id="deleteAnnonceForm" style="display:none;">
            @csrf
            @method('DELETE')
        </form>

        {{-- =================== MODAL DELETE =================== --}}
        <div class="modal fade ac-modal ac-modal--danger" id="deleteAnnonceModal" tabindex="-1"
             aria-labelledby="deleteAnnonceModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="ac-modal-titlewrap">
                            <span class="ac-modal-title" id="deleteAnnonceModalLabel">
                                <i class="fas fa-triangle-exclamation"></i> Supprimer cette annonce ?
                            </span>
                            <span class="ac-modal-sub">Action irréversible — toutes les relations seront détachées.</span>
                        </div>
                        <button type="button" class="ac-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0 0 .75rem 0; font-size:.88rem; color:#1e293b; line-height:1.5;">
                            Vous êtes sur le point de supprimer définitivement&nbsp;:
                        </p>
                        <div style="padding:.75rem 1rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:1rem;">
                            <div style="font-size:.72rem; color:#64748b; text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Annonce</div>
                            <div style="font-size:.95rem; font-weight:600; color:#0f172a; margin-top:.2rem;">{{ $annonce->titre }}</div>
                        </div>
                        <p style="margin:0; font-size:.8rem; color:#b91c1c; line-height:1.45;">
                            <i class="fas fa-info-circle me-1"></i>
                            Les étudiants ne verront plus cette annonce. Les statistiques de lecture seront perdues.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="ac-btn ac-btn-ghost" data-bs-dismiss="modal">
                            Annuler
                        </button>
                        <button type="button" class="ac-btn ac-btn-danger" id="ac-confirm-delete"
                                style="background:linear-gradient(135deg,#b91c1c,#dc2626);color:#fff;border-color:transparent;box-shadow:0 6px 18px rgba(220,38,38,.25);">
                            <i class="fas fa-trash"></i>Supprimer définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
(function () {
    'use strict';

    // ----- Debug helpers -----
    const DEBUG = @json(config('app.debug'));
    const debugLog   = (...args) => { if (DEBUG) console.log('[annonces:edit]', ...args); };
    const debugError = (...args) => { if (DEBUG) console.error('[annonces:edit]', ...args); };

    // ----- État global Choices.js -----
    const choicesInstances = {};
    const originalClassesOptions = [];
    const originalEtudiantsOptions = [];

    // ----- Configuration Choices.js -----
    const baseChoicesConfig = {
        searchEnabled: true,
        searchChoices: true,
        searchFloor: 1,
        searchResultLimit: 12,
        shouldSort: false,
        placeholder: true,
        noResultsText: 'Aucun résultat',
        noChoicesText: 'Aucun choix disponible',
        itemSelectText: '',
        loadingText: 'Recherche…',
        removeItemButton: true,
        duplicateItemsAllowed: false,
        renderChoiceLimit: 30,
        position: 'bottom',
        allowHTML: true,
    };

    function initChoices(selectEl, extra = {}) {
        if (!selectEl) return null;
        const id = selectEl.id;
        if (choicesInstances[id]) {
            choicesInstances[id].destroy();
            delete choicesInstances[id];
        }
        try {
            const inst = new Choices(selectEl, { ...baseChoicesConfig, ...extra });
            choicesInstances[id] = inst;
            return inst;
        } catch (err) {
            debugError('Init Choices fail', id, err);
            return null;
        }
    }

    function snapshotOriginalOptions(selectEl, target, customMapper) {
        if (!selectEl) return;
        Array.from(selectEl.options).forEach(opt => {
            if (!opt.value) return;
            target.push({
                value: opt.value,
                label: opt.textContent.trim(),
                selected: opt.selected,
                disabled: false,
                customProperties: customMapper(opt),
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {

        // --- 1. Snapshot des options originales ---
        const classesSelect = document.getElementById('classes');
        const etudiantsSelect = document.getElementById('etudiants');

        snapshotOriginalOptions(classesSelect, originalClassesOptions, opt => ({
            filiere: opt.dataset.filiere,
            niveau: opt.dataset.niveau,
            currentCount: opt.dataset.currentCount,
        }));
        snapshotOriginalOptions(etudiantsSelect, originalEtudiantsOptions, opt => ({
            classe: opt.dataset.classe,
            currentYear: opt.dataset.currentYear,
        }));
        debugLog('Snapshots', { classes: originalClassesOptions.length, etudiants: originalEtudiantsOptions.length });

        // --- 2. Init Choices.js ---
        if (classesSelect) {
            initChoices(classesSelect, {
                placeholderValue: 'Tapez pour rechercher une classe…',
                maxItemCount: 20,
            });
        }
        if (etudiantsSelect) {
            initChoices(etudiantsSelect, {
                placeholderValue: 'Tapez pour rechercher un étudiant…',
                maxItemCount: 50,
            });
        }

        // --- 3. Compteurs caractères live ---
        document.querySelectorAll('[data-counter-for]').forEach(counterEl => {
            const targetId = counterEl.getAttribute('data-counter-for');
            const target = document.getElementById(targetId);
            if (!target) return;
            const max = parseInt(counterEl.getAttribute('data-max'), 10) || null;
            const display = counterEl.querySelector('[data-counter-current]');
            const update = () => {
                const len = target.value.length;
                if (display) display.textContent = len;
                if (max) {
                    counterEl.classList.remove('ac-counter--warn', 'ac-counter--danger');
                    if (len > max * 0.95) counterEl.classList.add('ac-counter--danger');
                    else if (len > max * 0.80) counterEl.classList.add('ac-counter--warn');
                }
            };
            target.addEventListener('input', update);
            update();
        });

        // --- 4. File upload preview ---
        const fileInput = document.getElementById('piece_jointe');
        const fileZone = document.getElementById('ac-file-zone');
        const fileName = document.getElementById('ac-file-name');
        const fileLabel = document.getElementById('ac-file-label');
        const existingFile = document.getElementById('ac-existing-file');
        const hasExisting = !!existingFile;
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    const file = this.files[0];
                    const sizeKb = Math.round(file.size / 1024);
                    fileName.textContent = `${file.name} · ${sizeKb} KB`;
                    fileLabel.textContent = hasExisting
                        ? 'Nouveau fichier prêt — remplacera l\'ancien'
                        : 'Fichier prêt à être envoyé';
                    fileZone.classList.add('ac-file--has-file');
                } else {
                    fileLabel.textContent = hasExisting
                        ? 'Cliquez pour remplacer le fichier'
                        : 'Cliquez ou déposez un fichier';
                    fileZone.classList.remove('ac-file--has-file');
                }
            });
        }

        // --- 5. Pickers : afficher selon le type radio ---
        const classesPicker = document.getElementById('classes_picker');
        const etudiantsPicker = document.getElementById('etudiants_picker');

        function reflectAudience() {
            const checked = document.querySelector('input[name="type"]:checked');
            const value = checked ? checked.value : 'general';
            if (classesPicker) {
                classesPicker.classList.toggle('ac-picker--show', value === 'classe');
                if (value === 'classe') classesPicker.classList.add('ac-fade-enter');
            }
            if (etudiantsPicker) {
                etudiantsPicker.classList.toggle('ac-picker--show', value === 'etudiant');
                if (value === 'etudiant') etudiantsPicker.classList.add('ac-fade-enter');
            }
        }
        document.querySelectorAll('input[name="type"]').forEach(r => {
            r.addEventListener('change', reflectAudience);
        });
        reflectAudience();

        // --- 6. Récap classes/étudiants sélectionnés ---
        function updateRecipientSummaries() {
            const cInst = choicesInstances['classes'];
            const eInst = choicesInstances['etudiants'];

            if (cInst) {
                const sel = cInst.getValue(true);
                const count = sel.length;
                const studentCount = sel.reduce((s, v) => {
                    const o = originalClassesOptions.find(it => String(it.value) === String(v));
                    const c = parseInt(o?.customProperties?.currentCount || 0, 10);
                    return s + (Number.isNaN(c) ? 0 : c);
                }, 0);
                const sumEl = document.getElementById('classes_summary');
                const badge = document.getElementById('classes_count_badge');
                if (sumEl) {
                    sumEl.textContent = count > 0
                        ? `${count} classe(s) • ${studentCount} étudiant(s) en année courante`
                        : 'Aucune classe sélectionnée';
                }
                if (badge) {
                    badge.textContent = count;
                    badge.classList.toggle('ac-picker-count-badge--empty', count === 0);
                }
            }

            if (eInst) {
                const sel = eInst.getValue(true);
                const count = sel.length;
                const cyCount = sel.reduce((s, v) => {
                    const o = originalEtudiantsOptions.find(it => String(it.value) === String(v));
                    return s + (String(o?.customProperties?.currentYear) === '1' ? 1 : 0);
                }, 0);
                const sumEl = document.getElementById('etudiants_summary');
                const badge = document.getElementById('etudiants_count_badge');
                if (sumEl) {
                    sumEl.textContent = count > 0
                        ? `${count} étudiant(s) • ${cyCount} en année courante`
                        : 'Aucun étudiant sélectionné';
                }
                if (badge) {
                    badge.textContent = count;
                    badge.classList.toggle('ac-picker-count-badge--empty', count === 0);
                }
            }
        }
        document.addEventListener('change', e => {
            if (e.target.id === 'classes' || e.target.id === 'etudiants') {
                updateRecipientSummaries();
            }
        });
        updateRecipientSummaries();

        // --- 7. Filtres modals ---
        function applyClassesFilter() {
            const inst = choicesInstances['classes'];
            if (!inst || originalClassesOptions.length === 0) return;
            const filiereId = document.getElementById('filiere_filter')?.value || '';
            const niveauId  = document.getElementById('niveau_filter')?.value || '';
            const current   = inst.getValue(true);

            const filtered = originalClassesOptions.filter(opt => {
                if (filiereId && opt.customProperties.filiere && String(opt.customProperties.filiere) !== String(filiereId)) return false;
                if (niveauId && opt.customProperties.niveau && String(opt.customProperties.niveau) !== String(niveauId)) return false;
                return true;
            });

            inst.clearStore();
            inst.setChoices(filtered, 'value', 'label', true);
            current.forEach(v => {
                if (filtered.some(c => c.value === v)) inst.setChoiceByValue(v);
            });
            const totalEl = document.getElementById('classes_total_visible');
            if (totalEl) totalEl.textContent = filtered.length;
        }

        function applyEtudiantsFilter() {
            const inst = choicesInstances['etudiants'];
            if (!inst || originalEtudiantsOptions.length === 0) return;
            const classeId = document.getElementById('classe_etudiant_filter')?.value || '';
            const current  = inst.getValue(true);

            const filtered = originalEtudiantsOptions.filter(opt => {
                if (classeId && opt.customProperties.classe && String(opt.customProperties.classe) !== String(classeId)) return false;
                return true;
            });

            inst.clearStore();
            inst.setChoices(filtered, 'value', 'label', true);
            current.forEach(v => {
                if (filtered.some(c => c.value === v)) inst.setChoiceByValue(v);
            });
            const info = document.getElementById('etudiants-info');
            if (info) {
                info.innerHTML = filtered.length > 0
                    ? `<strong>${filtered.length}</strong> étudiant(s) disponible(s)`
                    : 'Aucun étudiant disponible avec ce filtre';
            }
        }

        document.getElementById('filiere_filter')?.addEventListener('change', applyClassesFilter);
        document.getElementById('niveau_filter')?.addEventListener('change', applyClassesFilter);
        document.getElementById('classe_etudiant_filter')?.addEventListener('change', applyEtudiantsFilter);

        // Reset filters buttons (both modals partagent .reset-filters)
        document.querySelectorAll('.reset-filters').forEach(btn => {
            btn.addEventListener('click', () => {
                const f = document.getElementById('filiere_filter'); if (f) f.value = '';
                const n = document.getElementById('niveau_filter'); if (n) n.value = '';
                const c = document.getElementById('classe_etudiant_filter'); if (c) c.value = '';
                applyClassesFilter();
                applyEtudiantsFilter();
            });
        });

        // --- 8. Bulk : Tout sélectionner / vider ---
        function selectAll(instKey) {
            const inst = choicesInstances[instKey];
            if (!inst) return;
            const choices = inst._currentState?.choices || [];
            choices.filter(c => !c.disabled).forEach(c => {
                inst._addItem({ value: c.value, label: c.label, id: c.id });
            });
        }
        function clearAll(instKey, source) {
            const inst = choicesInstances[instKey];
            if (!inst) return;
            const cleared = source.map(o => ({ ...o, selected: false }));
            inst.clearStore();
            inst.setChoices(cleared, 'value', 'label', true);
            updateRecipientSummaries();
        }

        document.getElementById('select_all_classes')?.addEventListener('click', () => selectAll('classes'));
        document.getElementById('select_all_etudiants')?.addEventListener('click', () => selectAll('etudiants'));
        document.getElementById('clear_classes_selection')?.addEventListener('click', () => clearAll('classes', originalClassesOptions));
        document.getElementById('clear_etudiants_selection')?.addEventListener('click', () => clearAll('etudiants', originalEtudiantsOptions));

        // --- 9. Validation submit (au moins 1 classe/étudiant si type contraint) ---
        const form = document.getElementById('annonceForm');
        form?.addEventListener('submit', function (e) {
            const checked = document.querySelector('input[name="type"]:checked');
            const type = checked ? checked.value : 'general';

            if (type === 'classe') {
                const inst = choicesInstances['classes'];
                if (inst && inst.getValue().length === 0) {
                    e.preventDefault();
                    if (window.showToast) {
                        window.showToast('Veuillez sélectionner au moins une classe.', 'warning');
                    } else {
                        alert('Veuillez sélectionner au moins une classe.');
                    }
                    document.querySelector('[data-bs-target="#classesModal"]')?.click();
                    return;
                }
            } else if (type === 'etudiant') {
                const inst = choicesInstances['etudiants'];
                if (inst && inst.getValue().length === 0) {
                    e.preventDefault();
                    if (window.showToast) {
                        window.showToast('Veuillez sélectionner au moins un étudiant.', 'warning');
                    } else {
                        alert('Veuillez sélectionner au moins un étudiant.');
                    }
                    document.querySelector('[data-bs-target="#etudiantsModal"]')?.click();
                    return;
                }
            }
        });

        // --- 10. Suppression : modal premium custom (pas window.confirm) ---
        const deleteBtn = document.getElementById('ac-open-delete');
        const deleteModalEl = document.getElementById('deleteAnnonceModal');
        const confirmDeleteBtn = document.getElementById('ac-confirm-delete');
        const deleteForm = document.getElementById('deleteAnnonceForm');

        deleteBtn?.addEventListener('click', () => {
            if (deleteModalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(deleteModalEl).show();
            }
        });
        confirmDeleteBtn?.addEventListener('click', () => {
            if (deleteForm) deleteForm.submit();
        });

    });
})();
</script>
@endpush
