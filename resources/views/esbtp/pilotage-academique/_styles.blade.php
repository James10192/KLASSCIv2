@push('styles')
<style>
.cpa-shell { display: grid; gap: 1rem; -webkit-font-smoothing: antialiased; }
.cpa-hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 42%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
}
.cpa-hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.cpa-hero-title { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.cpa-hero-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.35rem;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.16);
}
.cpa-hero h1 { margin: 0 0 .2rem; color: #fff; font-size: 1.45rem; font-weight: 800; letter-spacing: 0; text-wrap: balance; }
.cpa-hero p { margin: 0; color: rgba(255,255,255,.76); font-size: .88rem; text-wrap: pretty; }
.cpa-hero-scope {
    display: inline-flex; align-items: center; gap: .45rem; min-height: 36px;
    padding: .45rem .75rem; border-radius: 10px; font-size: .78rem; font-weight: 800;
    color: #fff; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
}
.cpa-hero-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-top: 1.5rem; }
.cpa-hero-kpi {
    min-width: 0; min-height: 76px; padding: .85rem 1rem; border-radius: 12px;
    display: flex; align-items: center; gap: .75rem;
    background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.15);
}
.cpa-hero-kpi-icon {
    width: 38px; height: 38px; border-radius: 9px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; background: rgba(255,255,255,.15);
}
.cpa-hero-kpi-value { color: #fff; font-size: 1.35rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
.cpa-hero-kpi-label { margin-top: .2rem; color: rgba(255,255,255,.68); font-size: .72rem; font-weight: 700; }
.cpa-filter-panel { overflow: visible; position: relative; z-index: 30; }
.cpa-filter-panel:has(.au-select-trigger--open) { z-index: 1400; }
.cpa-filter-panel .cpa-toolbar { overflow: visible; }
.cpa-filters { display: grid; grid-template-columns: minmax(170px, .9fr) minmax(150px, .75fr) minmax(140px, .7fr) minmax(280px, 1.65fr); gap: .75rem; align-items: end; overflow: visible; }
.cpa-filters .au-select,
.cpa-filters .au-select-trigger { width: 100%; }
.cpa-filters .au-select:has(.au-select-trigger--open) { z-index: 1300; }
.cpa-filters .au-select-menu { left: 0; right: auto; min-width: 100%; width: max(100%, 260px); max-height: min(380px, 46vh); z-index: 1301; }
.cpa-filters .cpa-filter-year .au-select-menu { width: max(100%, 260px); }
.cpa-filters .cpa-filter-class .au-select-menu { width: min(520px, calc(100vw - 48px)); }
.cpa-filters .au-select-option { align-items: flex-start; gap: .65rem; }
.cpa-filters .au-select-option-label { white-space: normal; line-height: 1.25; overflow: visible; text-overflow: clip; }
.cpa-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: .9rem; }
.cpa-tabs { display: flex; gap: .35rem; overflow-x: auto; padding: .35rem; background: #fff; border: 1px solid #e8ecf1; border-radius: 14px; }
.cpa-tab { border: 0; background: transparent; color: #64748b; border-radius: 10px; min-height: 44px; padding: .55rem .9rem; font-weight: 700; font-size: .82rem; display: inline-flex; align-items: center; gap: .45rem; white-space: nowrap; }
.cpa-tab:hover { background: #f1f5f9; color: #0453cb; }
.cpa-tab.is-active { background: #0453cb; color: #fff; box-shadow: 0 8px 20px rgba(4,83,203,.16); }
.cpa-panel { background: #fff; border: 1px solid #e8ecf1; border-radius: 8px; padding: 1rem; box-shadow: 0 12px 28px rgba(15,23,42,.05); }
.cpa-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .85rem; }
.cpa-panel-title { margin: 0; color: #0f172a; font-size: 1rem; font-weight: 800; display: flex; align-items: center; gap: .55rem; }
.cpa-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
.cpa-kpi { border: 1px solid #e8ecf1; border-radius: 10px; padding: .85rem; background: #f8fafc; min-height: 92px; }
.cpa-kpi-label { color: #64748b; font-size: .72rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0; }
.cpa-kpi-value { color: #0f172a; font-size: 1.55rem; line-height: 1.1; font-weight: 900; margin-top: .25rem; font-variant-numeric: tabular-nums; }
.cpa-kpi small { color: #64748b; font-weight: 600; }
.cpa-list { display: grid; gap: .6rem; }
.cpa-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .75rem; align-items: center; border: 1px solid #e8ecf1; border-radius: 10px; padding: .8rem; background: #fff; }
.cpa-row:hover { border-color: #cbd5e1; background: #fbfdff; }
.cpa-row-main { min-width: 0; }
.cpa-row-title { color: #0f172a; font-weight: 800; font-size: .9rem; overflow-wrap: anywhere; }
.cpa-row-meta { display: flex; gap: .45rem; flex-wrap: wrap; margin-top: .3rem; color: #64748b; font-size: .76rem; }
.cpa-audit { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .65rem; }
.cpa-audit-item { border: 1px solid #e8ecf1; border-radius: 8px; padding: .45rem .55rem; background: #f8fafc; min-width: 0; }
.cpa-audit-label { display: block; color: #64748b; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0; }
.cpa-audit-value { display: block; color: #0f172a; font-size: .76rem; font-weight: 800; margin-top: .1rem; overflow-wrap: anywhere; }
.cpa-badge { display: inline-flex; align-items: center; gap: .25rem; min-height: 24px; padding: .18rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 800; background: #eff6ff; color: #0453cb; }
.cpa-badge--ok { background: #ecfdf5; color: #047857; }
.cpa-badge--warn { background: #fff7ed; color: #c2410c; }
.cpa-badge--danger { background: #fef2f2; color: #b91c1c; }
.cpa-btn { border: 1px solid #dbe5f2; background: #fff; color: #0453cb; border-radius: 8px; min-height: 44px; padding: .55rem .85rem; font-weight: 800; display: inline-flex; align-items: center; gap: .45rem; transition: background-color .15s, border-color .15s, color .15s, transform .15s; }
.cpa-btn:hover { background: #eff6ff; border-color: #bfdbfe; }
.cpa-btn:active:not(:disabled), .cpa-icon-btn:active:not(:disabled), .cpa-tab:active { transform: scale(.96); }
.cpa-btn--primary { background: #0453cb; color: #fff; border-color: #0453cb; }
.cpa-btn--primary:hover { background: #0347b0; color: #fff; }
.cpa-state { border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1.2rem; color: #64748b; text-align: center; background: #f8fafc; }
.cpa-state i { color: #0453cb; display: block; font-size: 1.35rem; margin-bottom: .4rem; }
.cpa-split { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr); gap: 1rem; }
.cpa-drawer { border: 1px solid #dbe5f2; border-radius: 14px; background: #f8fafc; padding: 1rem; min-height: 220px; }
.cpa-muted { color: #64748b; font-size: .82rem; margin: 0; }
.cpa-error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
.cpa-loading { opacity: .65; pointer-events: none; }
.cpa-btn[disabled] { opacity: .65; cursor: not-allowed; }
.cpa-row-actions { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; flex-wrap: wrap; }
.cpa-btn--compact { min-height: 36px; padding: .4rem .6rem; font-size: .75rem; }
.cpa-btn--danger { color: #b91c1c; border-color: #fecaca; background: #fff; }
.cpa-btn--danger:hover { color: #991b1b; border-color: #fca5a5; background: #fef2f2; }
.cpa-sheet-drawer { display: grid; align-content: start; gap: 1rem; }
.cpa-sheet-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
.cpa-detail-section { border-top: 1px solid #e2e8f0; padding-top: .9rem; }
.cpa-detail-section h4 { margin: 0 0 .65rem; color: #334155; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0; }
.cpa-detail-list { display: grid; gap: .5rem; }
.cpa-detail-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: .65rem; background: #fff; }
.cpa-detail-item-title { color: #0f172a; font-size: .8rem; font-weight: 800; overflow-wrap: anywhere; }
.cpa-detail-item-meta { margin-top: .2rem; color: #64748b; font-size: .74rem; overflow-wrap: anywhere; }
.cpa-progress-track { height: 8px; overflow: hidden; border-radius: 999px; background: #e2e8f0; }
.cpa-progress-bar { height: 100%; border-radius: inherit; background: #0453cb; transition: width .2s ease; }
.cpa-document-link { color: #0453cb; font-weight: 800; text-decoration: none; }
.cpa-document-link:hover { color: #0347b0; text-decoration: underline; }
.cpa-modal-backdrop { position: fixed; inset: 0; z-index: 1600; display: grid; place-items: center; padding: 1rem; background: rgba(15,23,42,.5); }
.cpa-modal { width: min(100%, 520px); max-height: calc(100vh - 2rem); overflow: auto; border: 1px solid #dbe5f2; border-radius: 8px; padding: 1.25rem; background: #fff; box-shadow: 0 24px 50px rgba(15,23,42,.24); }
.cpa-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.cpa-icon-btn { width: 44px; min-width: 44px; height: 44px; border: 1px solid #dbe5f2; border-radius: 8px; background: #fff; color: #475569; display: inline-flex; align-items: center; justify-content: center; }
.cpa-icon-btn:hover { background: #f8fafc; color: #0453cb; }
.cpa-field-label { display: block; margin: 1rem 0 .4rem; color: #334155; font-size: .8rem; font-weight: 800; }
.cpa-textarea { display: block; width: 100%; min-height: 104px; resize: vertical; border: 1px solid #cbd5e1; border-radius: 8px; padding: .7rem; color: #0f172a; font: inherit; }
.cpa-textarea:focus { outline: 2px solid rgba(4,83,203,.2); border-color: #0453cb; }
.cpa-modal-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1rem; flex-wrap: wrap; }
.cpa-assignment-form { display: grid; grid-template-columns: minmax(240px, 1.3fr) minmax(220px, 1fr) minmax(210px, 1fr) auto; gap: .75rem; align-items: end; }
.cpa-field { min-width: 0; }
.cpa-field .cpa-field-label { margin-top: 0; }
[x-cloak] { display: none !important; }
@media (max-width: 1100px) { .cpa-grid, .cpa-filters, .cpa-hero-kpis, .cpa-assignment-form { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cpa-split { grid-template-columns: 1fr; } }
@media (max-width: 900px) { .cpa-audit { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) {
    .cpa-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
    .cpa-hero-title { align-items: flex-start; }
    .cpa-hero-kpis, .cpa-grid, .cpa-filters, .cpa-audit, .cpa-assignment-form { grid-template-columns: 1fr; }
    .cpa-row { grid-template-columns: 1fr; }
}
</style>
@endpush
