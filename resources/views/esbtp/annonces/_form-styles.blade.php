{{--
    Shared CSS partial for /esbtp/annonces/create + /esbtp/annonces/edit.
    Namespace ac-* (Annonce Composer). Edit-specific styles (status pills,
    existing-file row, danger modal, success/danger buttons) stay inline
    in edit.blade.php to keep this partial small and focused.
--}}
<style>
/* ============================================================
   Annonce Composer — Namespace ac-*
   Shared styles for create.blade.php + edit.blade.php
   Design system KLASSCI : monochrome bleu #0453cb, border 14px,
   ombres multicouches, transitions <300ms, mobile-first.
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

/* ----- File upload zone ----- */
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

/* ----- Sidebar actions (boutons) ----- */
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
