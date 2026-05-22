{{--
    Styles partagés des pages /esbtp/audit/* — namespace `au-*`.

    Inclus via `@include('esbtp.audit._styles')` à l'intérieur d'un seul
    `@push('styles')` parent. Les styles spécifiques à une vue (ex: hero
    activity, show grid, mini bars heures de pointe) restent dans la vue
    correspondante après cet include.

    Palette : monochrome KLASSCI bleu + sémantiques (event, risk).
--}}
[x-cloak] { display: none !important; }

.au-page { padding: 1rem 0; }

/* ───── HERO ───── */
.au-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.15);
    animation: au-fadeDown .5s ease-out;
}
@keyframes au-fadeDown { from { opacity: 0; transform: translateY(-15px); } to { opacity: 1; transform: translateY(0); } }
.au-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.au-hero-left { display: flex; align-items: center; gap: 1rem; }
.au-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0; color: #fff;
}
.au-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
.au-hero-info p { margin: 0; opacity: .8; font-size: .88rem; color: rgba(255,255,255,.7); }
.au-hero-info p strong { color: #fff; }
.au-hero-actions { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }

/* Boutons hero */
.au-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1rem; border-radius: 10px; font-size: .82rem;
    font-weight: 600; text-decoration: none; transition: all .2s;
    border: 1px solid transparent; cursor: pointer; white-space: nowrap;
}
.au-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.au-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.au-btn--white { background: #fff; color: #0453cb; }
.au-btn--white:hover { background: #f0f4ff; color: #0453cb; }
.au-btn--primary { background: #0453cb; color: #fff; }
.au-btn--primary:hover { background: #033a8e; color: #fff; }
.au-btn--ghost { background: transparent; color: #1e293b; border-color: #e2e8f0; }
.au-btn--ghost:hover { background: #f1f5f9; color: #0453cb; border-color: #cbd5e1; }

.au-dropdown-menu {
    background: #fff; border: 1px solid #e8ecf1; border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,.12); padding: .35rem; min-width: 200px; z-index: 1050;
}
.au-dropdown-menu .dropdown-item { color: #1e293b; padding: .5rem .85rem; border-radius: 8px; font-size: .85rem; transition: all .15s; }
.au-dropdown-menu .dropdown-item:hover { background: #f1f5f9; }

/* KPIs */
.au-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.au-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
    transition: background .2s;
}
.au-kpi:hover { background: rgba(255,255,255,.15); }
.au-kpi-icon {
    width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .95rem; color: #fff;
}
.au-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.au-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; text-transform: uppercase; letter-spacing: .04em; }
.au-kpi--alert { border-color: rgba(252,165,165,.4); background: rgba(220,38,38,.18); }
.au-kpi--alert .au-kpi-icon { background: rgba(220,38,38,.3); }

/* ───── FILTRES ───── */
.au-filters {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 1rem 1.25rem; margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.au-filters-row { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
.au-filter-field {
    position: relative; display: flex; align-items: center;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    transition: all .2s; min-width: 140px;
}
.au-filter-field--grow { flex: 1; min-width: 220px; }
.au-filter-grow { flex: 1; min-width: 220px; }
.au-filter-field:focus-within { background: #fff; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
.au-filter-field label { padding: 0 .65rem; color: #64748b; font-size: .85rem; }
.au-filter-field input, .au-filter-field select {
    border: none; background: transparent; outline: none; padding: .55rem .65rem .55rem 0;
    font-size: .85rem; color: #1e293b; flex: 1; min-width: 0;
}
.au-filter-field select { padding-left: .65rem; cursor: pointer; }
.au-filter-reset {
    width: 38px; height: 38px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff;
    display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer;
    transition: all .15s; text-decoration: none;
}
.au-filter-reset:hover { background: #fee2e2; border-color: #fecaca; color: #dc2626; }

/* ───── CARD ───── */
.au-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); overflow: hidden;
}
.au-card-header {
    padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid #f1f5f9; background: #fafbfc;
}
.au-card-title {
    display: flex; align-items: center; gap: .6rem;
    font-size: 1rem; font-weight: 700; color: #0f172a; flex-wrap: wrap;
}
.au-card-title i { color: #0453cb; }
.au-card-body { padding: 1.25rem; }
.au-card-body--flush { padding: 0; }
.au-badge-count {
    background: #eff6ff; color: #0453cb; padding: .2rem .55rem; border-radius: 8px;
    font-size: .72rem; font-weight: 600; border: 1px solid #dbeafe;
}

.au-icon-btn {
    width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff;
    display: inline-flex; align-items: center; justify-content: center; color: #64748b;
    cursor: pointer; transition: all .15s; text-decoration: none;
}
.au-icon-btn:hover { background: #f1f5f9; color: #0453cb; border-color: #cbd5e1; }
.au-icon-btn--primary { background: #eff6ff; border-color: #dbeafe; color: #0453cb; }
.au-icon-btn--primary:hover { background: #dbeafe; color: #033a8e; }

/* ───── TABLEAU ───── */
.au-table-wrap { position: relative; min-height: 240px; overflow-x: auto; }
.au-table { width: 100%; border-collapse: collapse; }
.au-table thead th {
    background: #f8fafc; color: #475569; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em; padding: .85rem 1rem; text-align: left;
    border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
.au-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.au-table tbody tr:hover { background: #f8fafc; }
.au-table--clickable tbody tr,
.au-table--expandable tbody tr.au-row:not(.au-row--inert) { cursor: pointer; }
.au-table--clickable tbody tr:hover,
.au-table--expandable tbody tr.au-row:not(.au-row--inert):hover { background: #eff6ff; }
.au-table--expandable tbody tr.au-row--inert { cursor: default; }
.au-table--expandable tbody tr.au-row--inert .au-toggle { cursor: not-allowed; }
.au-table tbody tr:last-child { border-bottom: none; }
.au-table tbody td { padding: .85rem 1rem; font-size: .85rem; color: #1e293b; vertical-align: middle; }
.au-th-actions, .au-td-actions { text-align: center; width: 60px; }

.au-cell-date { display: flex; align-items: center; gap: .45rem; color: #64748b; font-variant-numeric: tabular-nums; font-size: .82rem; }
.au-cell-date i { color: #94a3b8; font-size: .8rem; }
.au-cell-user { display: flex; align-items: center; gap: .55rem; }
.au-avatar {
    width: 28px; height: 28px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .72rem; font-weight: 700; flex-shrink: 0;
}
.au-code {
    font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: .78rem;
    background: #f1f5f9; padding: .15rem .45rem; border-radius: 6px; color: #475569;
    word-break: break-all;
}
.au-code--block { display: inline-block; padding: .35rem .55rem; }
.au-changes { font-size: .8rem; color: #475569; }
.au-changes--empty { color: #94a3b8; font-style: italic; }

/* ───── CHIPS (sémantiques) ───── */
.au-chip {
    display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600; line-height: 1.2;
    border: 1px solid transparent; white-space: nowrap;
}
.au-chip--lg { padding: .35rem .8rem; font-size: .8rem; }
.au-chip--created { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.au-chip--updated { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
.au-chip--deleted { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.au-chip--restored { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.au-chip--retrieved { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.au-chip--neutral { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.au-chip--risk-critique { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.au-chip--risk-eleve { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.au-chip--risk-moyen { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
.au-chip--risk-faible { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

/* ───── AMOUNT DIFF ───── */
.au-amount-diff { display: inline-flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
.au-amount-old { color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; font-variant-numeric: tabular-nums; }
.au-amount-new { color: #065f46; background: #d1fae5; border: 1px solid #a7f3d0; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; font-variant-numeric: tabular-nums; }
.au-amount-arrow { color: #94a3b8; font-size: .75rem; }
.au-meta-empty { color: #94a3b8; font-style: italic; font-size: .82rem; }

/* ───── EXPANDABLE ROW (audit-links inline) ───── */
.au-table--expandable .au-row td { transition: background .15s ease; }
.au-table--expandable .au-row--open td { background: #f8faff; }
.au-cell-toggle { width: 42px; text-align: center; padding-left: .5rem !important; padding-right: 0 !important; }
.au-toggle {
    width: 28px; height: 28px; border-radius: 8px;
    border: 1px solid #e2e8f0; background: #fff; color: #64748b;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s ease;
}
.au-toggle:hover:not(:disabled) { background: #eff6ff; color: #0453cb; border-color: #dbeafe; }
.au-toggle:disabled { opacity: .35; cursor: not-allowed; background: #f8fafc; }
.au-toggle--open { background: #eff6ff; border-color: #dbeafe; color: #0453cb; }
.au-toggle-caret { transition: transform .2s ease; font-size: .75rem; }
.au-toggle--open .au-toggle-caret { transform: rotate(90deg); }
.au-toggle-caret--open { transform: rotate(180deg); }

.au-row-expand td { padding: 0; }
.au-row-expand-cell { padding: .65rem 1rem 1rem !important; background: #f8faff; border-top: 1px dashed rgba(4,83,203,.16); }

.au-links-pill {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .3rem .65rem; border-radius: 999px;
    background: #eff6ff; border: 1px solid #dbeafe; color: #0453cb;
    font-size: .72rem; font-weight: 700; cursor: pointer;
    transition: all .15s ease;
}
.au-links-pill:hover { background: #dbeafe; border-color: #93c5fd; color: #033a8e; }
.au-links-pill i { font-size: .7rem; }
.au-links-pill--sm { padding: .22rem .55rem; font-size: .68rem; }

.au-timeline-actions-row { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-top: .4rem; }
.au-timeline-links { margin-top: .65rem; padding-left: 0; }

/* ───── PAGINATION ───── */
.au-pagination {
    padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    border-top: 1px solid #f1f5f9; background: #fafbfc; flex-wrap: wrap;
}
.au-page-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem .85rem; border-radius: 10px; border: 1px solid #e2e8f0;
    background: #fff; color: #475569; font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .15s;
}
.au-page-btn:hover:not(:disabled) { background: #eff6ff; color: #0453cb; border-color: #dbeafe; }
.au-page-btn:disabled { opacity: .4; cursor: not-allowed; }
.au-page-info { font-size: .85rem; color: #64748b; }
.au-page-info strong { color: #0f172a; }
.au-pagination-laravel { padding: 1rem 1.25rem; border-top: 1px solid #f1f5f9; background: #fafbfc; display: flex; justify-content: center; }
.au-pagination-laravel .pagination { margin: 0; }
.au-pagination-laravel .page-link { color: #0453cb; border-color: #e2e8f0; }
.au-pagination-laravel .page-item.active .page-link { background: #0453cb; border-color: #0453cb; color: #fff; }

/* ───── LOADING / EMPTY ───── */
.au-loading {
    padding: 3rem 1rem; text-align: center; color: #64748b;
    display: flex; flex-direction: column; align-items: center; gap: .85rem;
}
.au-spinner {
    width: 36px; height: 36px; border-radius: 50%;
    border: 3px solid #e2e8f0; border-top-color: #0453cb; animation: au-spin 1s linear infinite;
}
@keyframes au-spin { to { transform: rotate(360deg); } }
.au-empty {
    padding: 3rem 1rem; text-align: center; color: #64748b;
    display: flex; flex-direction: column; align-items: center; gap: .65rem;
}
.au-empty i { font-size: 2.5rem; color: #cbd5e1; }
.au-empty h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.au-empty p { margin: 0; font-size: .85rem; }
.au-empty--small { padding: 1.5rem 1rem; }
.au-empty--small i { font-size: 1.5rem; }

/* ───── MODAL ───── */
.au-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,.55);
    display: flex; align-items: center; justify-content: center; z-index: 1080; padding: 1rem;
    overflow-y: auto;
}
.au-modal {
    background: #fff; border-radius: 16px; width: 100%; max-width: 760px;
    box-shadow: 0 20px 50px rgba(15,23,42,.25); max-height: 90vh; display: flex; flex-direction: column;
}
.au-modal-header {
    padding: 1.1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;
}
.au-modal-title {
    display: flex; align-items: center; gap: .65rem; font-size: 1.05rem; font-weight: 700; color: #0f172a;
}
.au-modal-title i { color: #0453cb; }
.au-modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }
.au-modal-footer {
    padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; gap: .65rem; justify-content: flex-end;
    background: #fafbfc;
}

.au-meta-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: .85rem 1.5rem; margin-bottom: 1.25rem;
}
.au-meta-grid > div { display: flex; flex-direction: column; gap: .15rem; }
.au-meta-grid strong { font-size: .7rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
.au-meta-grid span, .au-meta-grid code { font-size: .88rem; color: #1e293b; }

.au-diff-list h4 {
    font-size: .9rem; font-weight: 700; color: #0f172a; margin: 0 0 .65rem;
    display: flex; align-items: center; gap: .5rem;
}
.au-diff-list h4 i { color: #0453cb; }
.au-diff-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.au-diff-table--full { border-radius: 0; border: none; }
.au-diff-table thead th {
    background: #f8fafc; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    color: #475569; padding: .65rem .85rem; text-align: left; border-bottom: 1px solid #e2e8f0;
}
.au-diff-table tbody td { padding: .65rem .85rem; font-size: .82rem; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
.au-diff-table tbody tr:last-child td { border-bottom: none; }
.au-diff-table tbody tr:hover { background: #fafbfc; }
.au-diff-old { display: inline-block; color: #991b1b; background: #fee2e2; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; word-break: break-all; }
.au-diff-new { display: inline-block; color: #065f46; background: #d1fae5; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; word-break: break-all; }
.au-diff-old--block, .au-diff-new--block { display: block; padding: .65rem; white-space: pre-wrap; max-height: 300px; overflow-y: auto; }
.au-link-btn { background: none; border: none; color: #0453cb; font-size: .78rem; font-weight: 600; cursor: pointer; padding: .3rem 0; margin-top: .35rem; display: inline-flex; align-items: center; gap: .25rem; text-decoration: none; }
.au-link-btn:hover { color: #033a8e; text-decoration: underline; }

/* ════════════════════════════════════════════════════════════════════
   AUDIT LINKS (composant Blade `audit-links`)
   Namespace : al-*
   Inclus directement ici pour que le rendu Alpine `<template x-for>` du
   quickModal (index.blade.php) ait les styles disponibles même quand le
   composant Blade n'est pas instancié server-side.
   ════════════════════════════════════════════════════════════════════ */
.al-wrap {
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 14px;
    padding: 1rem 1.15rem 1.15rem;
}
.al-wrap--compact { background: transparent; border: none; padding: .35rem 0 0; }
.al-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: .65rem; margin-bottom: .85rem; padding-bottom: .65rem;
    border-bottom: 1px dashed rgba(4,83,203,.18); flex-wrap: wrap;
}
.al-title {
    display: inline-flex; align-items: center; gap: .5rem;
    font-size: .88rem; font-weight: 700; color: #0f172a; letter-spacing: -.01em;
}
.al-title i { color: #0453cb; font-size: .82rem; }
.al-count {
    background: #eff6ff; color: #0453cb; border: 1px solid #dbeafe;
    padding: .2rem .55rem; border-radius: 8px; font-size: .7rem;
    font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
}
.al-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: .65rem;
}
.al-grid--compact {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .45rem;
}
.al-item {
    display: flex; align-items: center; gap: .7rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 11px;
    padding: .65rem .8rem; text-decoration: none; color: inherit;
    transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease, background .15s ease;
    position: relative; min-width: 0;
}
.al-grid--compact .al-item { padding: .5rem .65rem; gap: .55rem; border-radius: 9px; }
.al-item--linkable:hover {
    transform: translateY(-1px); border-color: rgba(4,83,203,.35); background: #f8faff;
    box-shadow: 0 4px 12px rgba(4,83,203,.08), 0 1px 3px rgba(15,23,42,.04);
}
.al-item--linkable:hover .al-arrow { opacity: 1; transform: translateX(0); }
.al-item--linkable:focus-visible { outline: 2px solid #0453cb; outline-offset: 2px; }
.al-icon {
    width: 36px; height: 36px; flex-shrink: 0; border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
    font-size: .85rem; box-shadow: 0 2px 6px rgba(4,83,203,.22);
}
.al-grid--compact .al-icon { width: 30px; height: 30px; font-size: .75rem; border-radius: 8px; }
.al-item--primary .al-icon { background: linear-gradient(135deg, #033a8e, #0453cb); }
.al-item--muted .al-icon {
    background: linear-gradient(135deg, #94a3b8, #cbd5e1);
    box-shadow: 0 1px 3px rgba(100,116,139,.20);
}
.al-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
.al-label {
    font-size: .64rem; color: #64748b; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em; line-height: 1.1;
}
.al-value {
    font-size: .85rem; color: #0f172a; font-weight: 600; line-height: 1.25;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.al-grid--compact .al-value { font-size: .8rem; }
.al-item--primary .al-value { color: #033a8e; }
.al-item--muted .al-value { color: #64748b; font-style: italic; }
.al-sub {
    font-size: .72rem; color: #64748b; line-height: 1.2;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
}
.al-arrow {
    color: #0453cb; font-size: .75rem; opacity: 0;
    transform: translateX(-4px);
    transition: opacity .15s ease, transform .15s ease;
    flex-shrink: 0; margin-left: .15rem;
}
@@media (max-width: 768px) {
    .al-grid, .al-grid--compact { grid-template-columns: 1fr; }
    .al-item { padding: .6rem .7rem; }
}

/* ───── QUICK LINKS (quickModal AJAX) ───── */
.au-quick-links-section { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9; }
.au-quick-links-section h4 {
    font-size: .9rem; font-weight: 700; color: #0f172a; margin: 0 0 .85rem;
    display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
}
.au-quick-links-section h4 i { color: #0453cb; }
.au-quick-links-loading { display: flex; align-items: center; gap: .65rem; padding: 1rem; color: #64748b; font-size: .85rem; }
.au-spinner--sm { width: 20px; height: 20px; border-width: 2px; }

.au-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.au-form-grid label { display: block; font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
.au-form-grid input, .au-form-grid select {
    width: 100%; padding: .55rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: .85rem; color: #1e293b; transition: all .2s;
}
.au-form-grid input:focus, .au-form-grid select:focus {
    outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}

/* ───── META LIST (show) ───── */
.au-meta-list { margin: 0; }
.au-meta-list > div { display: grid; grid-template-columns: 130px 1fr; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid #f1f5f9; }
.au-meta-list > div:last-child { border-bottom: none; }
.au-meta-list dt { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; padding-top: .15rem; }
.au-meta-list dd { margin: 0; font-size: .88rem; color: #1e293b; }
.au-meta-strong { font-weight: 600; color: #0f172a; }
.au-meta-sub { font-size: .78rem; color: #64748b; }
.au-meta-label { font-size: .8rem; color: #64748b; font-weight: 500; }

/* ───── ENTITY (show) ───── */
.au-entity-info { display: flex; flex-direction: column; gap: .55rem; }
.au-entity-row { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }

.au-warning {
    background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: .75rem .9rem;
    color: #92400e; font-size: .85rem; display: flex; align-items: center; gap: .55rem;
}
.au-warning i { color: #b45309; }

/* ───── TIMELINE (show) ───── */
.au-timeline { list-style: none; padding: 0; margin: 0; }
.au-timeline-item { display: flex; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; align-items: flex-start; }
.au-timeline-item:last-child { border-bottom: none; }
.au-timeline-icon {
    width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: .85rem; color: #fff;
}
.au-timeline-item--created .au-timeline-icon { background: #10b981; }
.au-timeline-item--updated .au-timeline-icon { background: #3b7ddb; }
.au-timeline-item--deleted .au-timeline-icon { background: #dc2626; }
.au-timeline-item--restored .au-timeline-icon { background: #f59e0b; }
.au-timeline-item--retrieved .au-timeline-icon { background: #94a3b8; }
.au-timeline-content { flex: 1; min-width: 0; }
.au-timeline-meta { font-size: .85rem; color: #475569; display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.au-timeline-meta strong { color: #0f172a; }
.au-timeline-actions { margin-top: .25rem; }

/* ───── RESPONSIVE ───── */
@media (max-width: 992px) {
    .au-hero { padding: 1.5rem 1.5rem 1rem; }
    .au-hero-info h1 { font-size: 1.2rem; }
    .au-meta-grid { grid-template-columns: 1fr; }
    .au-form-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .au-filters-row { flex-direction: column; align-items: stretch; }
    .au-filter-field { min-width: 0; width: 100%; }
    .au-filter-reset { align-self: flex-end; }
    .au-table thead { display: none; }
    .au-table tbody, .au-table tr, .au-table td { display: block; width: 100%; }
    .au-table tbody tr { padding: .85rem; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: .65rem; }
    .au-table tbody td { padding: .35rem 0; border: none; }
    .au-meta-list > div { grid-template-columns: 1fr; gap: .25rem; }
    .au-diff-table thead { display: none; }
    .au-diff-table tbody, .au-diff-table tr, .au-diff-table td { display: block; width: 100%; }
    .au-diff-table tbody tr { padding: .85rem; border-bottom: 1px solid #f1f5f9; }
    .au-diff-table tbody td { padding: .25rem 0; border: none; }
}
@media (max-width: 576px) {
    .au-hero-actions { width: 100%; }
    .au-hero-actions .au-btn { flex: 1; justify-content: center; }
    .au-kpis { gap: .5rem; }
    .au-kpi { min-width: calc(50% - .25rem); padding: .65rem .75rem; }
    .au-kpi-value { font-size: 1.1rem; }
}
