@push('styles')
<style>
.cpa-shell { display: grid; gap: 1rem; -webkit-font-smoothing: antialiased; }
.cpa-shell > *,
.cpa-toolbar,
.cpa-tabs,
.cpa-panel-head,
.cpa-grid,
.cpa-hero-top,
.cpa-hero-kpis { min-width: 0; max-width: 100%; }
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
.cpa-tabs { display: flex; gap: .35rem; overflow-x: auto; padding: .35rem; background: #fff; border: 1px solid #e8ecf1; border-radius: 14px; scrollbar-width: none; }
.cpa-tabs::-webkit-scrollbar { display: none; }
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
.cpa-audit-icon { width: 28px; height: 28px; margin-bottom: .35rem; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; color: #0453cb; background: #eff6ff; }
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
.cpa-split { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(280px, .65fr); gap: 1rem; align-items: start; }

/* ===== Vue direction ===== */
/* Deliberement asymetrique : la question principale occupe la colonne large,
   les deux ventilations complementaires tiennent dans le rail. */
.cpa-direction { display: grid; grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr); gap: 1rem; align-items: start; }
.cpa-direction-rail { display: grid; gap: 1rem; }
.cpa-chart-wrap { position: relative; width: 100%; height: 280px; margin-top: .75rem; }
.cpa-chart-wrap--court { height: 210px; }
.cpa-chart-legende { display: flex; flex-wrap: wrap; gap: .9rem; margin-top: .75rem; padding-top: .75rem; border-top: 1px solid #eef2f7; font-size: .76rem; color: #475569; }
.cpa-legende-item { display: inline-flex; align-items: center; gap: .35rem; }
.cpa-legende-pastille { width: 9px; height: 9px; border-radius: 3px; flex-shrink: 0; }
.cpa-lien-bloc {
    display: flex; align-items: center; justify-content: center; gap: .4rem;
    width: 100%; margin-top: .85rem; padding: .55rem;
    border: 1px dashed #dbe3ec; border-radius: 9px; background: transparent;
    font-size: .8rem; font-weight: 600; color: #0453cb; cursor: pointer;
    transition: background .15s ease, border-color .15s ease;
}
.cpa-lien-bloc:hover { background: rgba(4, 83, 203, .05); border-color: #0453cb; }

/* ===== Tendances de l'annee ===== */
.cpa-tendances { grid-column: 1 / -1; }
.cpa-tendances-grille { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-top: .85rem; }
.cpa-tendance { border: 1px solid #e8ecf1; border-radius: 12px; padding: .9rem 1rem 1rem; background: #fff; }
.cpa-tendance-tete { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; }
.cpa-tendance-titre { font-size: .88rem; font-weight: 700; color: #1e293b; }
.cpa-tendance-dernier { font-size: 1.05rem; font-weight: 800; color: #0453cb; font-variant-numeric: tabular-nums; white-space: nowrap; }
.cpa-tendance-aide { margin: .2rem 0 .6rem; font-size: .74rem; color: #64748b; }
.cpa-tendance-toile { position: relative; width: 100%; height: 170px; }
.cpa-tendance-vide {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .5rem; text-align: center; height: 170px; padding: 1rem;
    background: #f8fafc; border: 1px dashed #dbe3ec; border-radius: 10px;
    font-size: .78rem; color: #64748b;
}
.cpa-tendance-vide i { font-size: 1.1rem; color: #94a3b8; }
.cpa-drawer { border: 1px solid #dbe5f2; border-radius: 8px; background: #f8fafc; padding: 1rem; min-height: 160px; }
.cpa-muted { color: #64748b; font-size: .82rem; margin: 0; }
.cpa-error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
.cpa-loading { opacity: .65; pointer-events: none; }
.cpa-btn[disabled] { opacity: .65; cursor: not-allowed; }
.cpa-row-actions { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; flex-wrap: wrap; }
.cpa-btn--compact { min-height: 36px; padding: .4rem .6rem; font-size: .75rem; }
.cpa-btn--danger { color: #b91c1c; border-color: #fecaca; background: #fff; }
.cpa-btn--danger:hover { color: #991b1b; border-color: #fca5a5; background: #fef2f2; }
.cpa-sheet-drawer { display: grid; align-content: start; gap: 1rem; }
.cpa-sheet-card { grid-template-columns: 1fr; align-items: start; gap: .85rem; padding: 1rem; }
.cpa-sheet-title-line { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.cpa-sheet-title-line .cpa-row-title { font-size: 1rem; text-wrap: balance; }
.cpa-sheet-reference { display: flex; align-items: center; gap: .4rem; margin-top: .3rem; color: #64748b; font-size: .7rem; }
.cpa-sheet-reference code { color: #475569; font-size: .68rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.cpa-sheet-stage { display: grid; justify-items: end; gap: .25rem; flex-shrink: 0; }
.cpa-sheet-stage small { color: #64748b; font-size: .7rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.cpa-sheet-progress { margin-top: .8rem; padding: .7rem .8rem; border-radius: 10px; background: #f8fafc; box-shadow: inset 0 0 0 1px #e8ecf1; }
.cpa-sheet-progress-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .45rem; color: #334155; font-size: .75rem; font-weight: 800; }
.cpa-sheet-progress-head strong { color: #0453cb; font-variant-numeric: tabular-nums; }
.cpa-sheet-progress small { display: block; margin-top: .4rem; color: #64748b; font-size: .68rem; line-height: 1.35; text-wrap: pretty; }
.cpa-sheet-card .cpa-row-meta span { display: inline-flex; align-items: center; min-width: 0; }
.cpa-sheet-card .cpa-row-meta span + span::before { content: ''; width: 4px; height: 4px; margin-right: .45rem; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
.cpa-sheet-card .cpa-audit { grid-template-columns: repeat(3, minmax(150px, 1fr)); gap: .55rem; }
.cpa-sheet-card .cpa-audit-item { min-height: 62px; padding: .6rem .7rem; }
.cpa-sheet-card .cpa-audit-value { line-height: 1.35; }
.cpa-sheet-card .cpa-row-actions { justify-content: flex-start; padding-top: .8rem; border-top: 1px solid #eef2f7; }
.cpa-sheet-card .cpa-btn--compact { min-height: 44px; }
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
.cpa-note-coverage { display: grid; gap: 1rem; margin-bottom: 1rem; }
.cpa-note-coverage-empty { min-height: 96px; }
.cpa-note-cockpit { display: grid; gap: 1rem; }
.cpa-note-command {
    display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: stretch; gap: 1rem;
    border: 1px solid #dbe8fb; border-radius: 12px; padding: 1rem;
    background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
    box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
}
.cpa-note-command-copy { display: grid; align-content: center; gap: .25rem; min-width: 0; }
.cpa-eyebrow { display: inline-flex; align-items: center; gap: .35rem; color: #0453cb; font-size: .72rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0; }
.cpa-note-command h3 { margin: 0; color: #0f172a; font-size: 1.1rem; font-weight: 900; letter-spacing: 0; }
.cpa-note-command p { margin: 0; color: #475569; font-size: .78rem; font-weight: 700; overflow-wrap: anywhere; }
.cpa-note-command-score {
    min-width: 156px; border: 1px solid #cfe0f6; border-radius: 10px; padding: .8rem;
    background: #fff; display: grid; justify-items: end; align-content: center; gap: .15rem;
}
.cpa-note-command-score span { border-radius: 999px; padding: .2rem .5rem; font-size: .68rem; font-weight: 900; }
.cpa-note-command-score strong { color: #0453cb; font-size: 1.8rem; font-weight: 950; line-height: 1; font-variant-numeric: tabular-nums; }
.cpa-note-command-score small { color: #64748b; font-size: .72rem; font-weight: 800; }
.cpa-coverage-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
.cpa-coverage-kpi {
    min-height: 92px; border: 1px solid #dfe8f4; border-radius: 10px; padding: .8rem .85rem;
    background: #fff; box-shadow: inset 3px 0 0 #0453cb, 0 8px 22px rgba(15, 23, 42, .05);
}
.cpa-coverage-kpi span { display: block; color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0; }
.cpa-coverage-kpi strong { display: block; margin-top: .22rem; color: #0f172a; font-size: 1.28rem; font-weight: 950; font-variant-numeric: tabular-nums; }
.cpa-coverage-kpi small { display: block; margin-top: .15rem; color: #475569; font-size: .72rem; font-weight: 750; }
.cpa-note-workbench { display: grid; grid-template-columns: minmax(280px, .9fr) minmax(0, 1.35fr); gap: .9rem; align-items: start; }
.cpa-note-priorities,
.cpa-note-progress-panel { border: 1px solid #dfe8f4; border-radius: 12px; padding: .85rem; background: #fff; box-shadow: 0 10px 26px rgba(15, 23, 42, .05); }
.cpa-note-section-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .75rem; }
.cpa-note-section-head h4 { margin: 0; color: #0f172a; font-size: .88rem; font-weight: 950; letter-spacing: 0; }
.cpa-note-section-head p { margin: .15rem 0 0; color: #64748b; font-size: .72rem; font-weight: 700; line-height: 1.35; }
.cpa-note-section-head > span { flex: 0 0 auto; border-radius: 999px; padding: .22rem .5rem; background: #eff6ff; color: #0453cb; font-size: .68rem; font-weight: 900; }
.cpa-note-priority-card { display: grid; gap: .7rem; border: 1px solid #fed7aa; border-radius: 10px; padding: .8rem; background: #fffaf5; }
.cpa-note-priority-card + .cpa-note-priority-card { margin-top: .6rem; }
.cpa-note-priority-main { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .75rem; align-items: start; }
.cpa-note-priority-main strong,
.cpa-note-subject-main strong,
.cpa-note-evaluation summary strong { color: #0f172a; font-size: .84rem; font-weight: 950; overflow-wrap: anywhere; }
.cpa-note-priority-main span,
.cpa-note-subject-main span,
.cpa-note-evaluation summary > div:first-child span { color: #64748b; font-size: .72rem; font-weight: 750; }
.cpa-note-priority-count { min-width: 78px; border-radius: 10px; padding: .5rem; background: #fff; text-align: right; box-shadow: inset 0 0 0 1px #fed7aa; }
.cpa-note-priority-count strong { display: block; color: #b91c1c; font-size: 1.25rem; font-variant-numeric: tabular-nums; }
.cpa-note-priority-count small { color: #7f1d1d; font-size: .68rem; font-weight: 900; }
.cpa-note-priority-progress > div:first-child { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .35rem; color: #475569; font-size: .72rem; font-weight: 850; }
.cpa-note-blockers { display: grid; gap: .4rem; }
.cpa-note-blocker-row { min-height: 44px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .65rem; align-items: center; border-radius: 8px; padding: .55rem .65rem; background: #fff; box-shadow: inset 0 0 0 1px #ffedd5; }
.cpa-note-blocker-row strong { color: #0f172a; font-size: .75rem; font-weight: 900; overflow-wrap: anywhere; }
.cpa-note-blocker-row span { color: #64748b; font-size: .7rem; font-weight: 800; }
.cpa-note-blocker-row > span { color: #b91c1c; text-align: right; font-variant-numeric: tabular-nums; }
.cpa-note-details { border-top: 1px dashed #fed7aa; padding-top: .55rem; }
.cpa-note-details > summary,
.cpa-note-incomplete > summary { list-style: none; min-height: 44px; display: flex; align-items: center; justify-content: space-between; gap: .6rem; cursor: pointer; color: #0453cb; font-size: .74rem; font-weight: 900; }
.cpa-note-details > summary::-webkit-details-marker,
.cpa-note-incomplete > summary::-webkit-details-marker { display: none; }
.cpa-note-subjects { display: grid; gap: .55rem; }
.cpa-note-subject,
.cpa-note-evaluation { border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; overflow: hidden; }
.cpa-note-subject > summary,
.cpa-note-evaluation > summary {
    list-style: none; cursor: pointer; min-height: 56px; padding: .72rem .8rem;
    display: grid; grid-template-columns: minmax(0, 1fr) minmax(170px, .62fr); align-items: center; gap: .8rem;
}
.cpa-note-subject > summary::-webkit-details-marker,
.cpa-note-evaluation > summary::-webkit-details-marker { display: none; }
.cpa-note-subject-main { display: grid; min-width: 0; }
.cpa-note-orphan { display: inline-flex; margin-left: .35rem; border-radius: 999px; padding: .12rem .38rem; background: #fff7ed; color: #c2410c !important; font-size: .66rem !important; font-weight: 900 !important; }
.cpa-note-subject-meter { display: grid; gap: .28rem; min-width: 0; }
.cpa-note-subject-meter span { justify-self: end; color: #475569; font-size: .7rem; font-weight: 900; font-variant-numeric: tabular-nums; }
.cpa-note-subject-meter .is-danger { color: #b91c1c; }
.cpa-note-subject-stats { display: flex; align-items: center; justify-content: flex-end; gap: .35rem; flex-wrap: wrap; min-width: 0; }
.cpa-note-subject-stats span { border-radius: 999px; padding: .18rem .45rem; color: #475569; background: #f1f5f9; font-size: .7rem; font-weight: 850; font-variant-numeric: tabular-nums; }
.cpa-note-subject-stats .is-danger { color: #b91c1c; background: #fef2f2; }
.cpa-note-subject-body,
.cpa-note-evaluation-body { border-top: 1px solid #eef2f7; padding: .75rem; background: #fbfdff; }
.cpa-note-evaluations { display: grid; gap: .5rem; }
.cpa-note-missing { margin-bottom: .65rem; }
.cpa-note-missing-title { margin: 0 0 .4rem; color: #334155; font-size: .72rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0; }
.cpa-note-chip-list { display: flex; gap: .35rem; flex-wrap: wrap; }
.cpa-note-chip { display: inline-flex; align-items: center; min-height: 28px; max-width: 100%; border-radius: 999px; padding: .2rem .5rem; background: #eff6ff; color: #0453cb; font-size: .72rem; font-weight: 850; overflow-wrap: anywhere; }
.cpa-note-chip--warn { background: #fff7ed; color: #c2410c; }
.cpa-note-chip--muted { background: #f1f5f9; color: #475569; }
.cpa-note-students { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem; }
.cpa-note-student-row { min-height: 48px; border: 1px solid #e8ecf1; border-radius: 8px; padding: .5rem .6rem; background: #fff; display: grid; gap: .1rem; }
.cpa-note-student-row.is-missing { border-color: #fecaca; background: #fff7f7; }
.cpa-note-student-row span { color: #0f172a; font-size: .76rem; font-weight: 850; overflow-wrap: anywhere; }
.cpa-note-student-row strong { color: #0453cb; font-size: .72rem; font-weight: 900; }
.cpa-note-student-row small { color: #64748b; font-size: .68rem; overflow-wrap: anywhere; }
.cpa-note-incomplete { margin-top: .75rem; border: 1px solid #fed7aa; border-radius: 10px; padding: .35rem .75rem .6rem; background: #fff7ed; }
.cpa-note-empty-compact { min-height: 56px; border: 1px dashed #cbd5e1; border-radius: 10px; padding: .75rem; background: #f8fafc; color: #475569; font-size: .78rem; font-weight: 800; display: flex; align-items: center; gap: .5rem; }
.cpa-progress-bar.is-complete { background: #10b981; }
.cpa-progress-bar.is-warning { background: #f97316; }
.cpa-modal-backdrop { position: fixed; inset: 0; z-index: 1600; display: grid; place-items: center; padding: 1rem; background: rgba(15,23,42,.5); }
.cpa-modal { width: min(100%, 520px); max-height: calc(100vh - 2rem); overflow: auto; border: 1px solid #dbe5f2; border-radius: 8px; padding: 1.25rem; background: #fff; box-shadow: 0 24px 50px rgba(15,23,42,.24); }
.cpa-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.cpa-icon-btn { width: 44px; min-width: 44px; height: 44px; border: 1px solid #dbe5f2; border-radius: 8px; background: #fff; color: #475569; display: inline-flex; align-items: center; justify-content: center; }
.cpa-icon-btn:hover { background: #f8fafc; color: #0453cb; }
.cpa-field-label { display: block; margin: 1rem 0 .4rem; color: #334155; font-size: .8rem; font-weight: 800; }
.cpa-textarea { display: block; width: 100%; min-height: 104px; resize: vertical; border: 1px solid #cbd5e1; border-radius: 8px; padding: .7rem; color: #0f172a; font: inherit; }
.cpa-textarea:focus { outline: 2px solid rgba(4,83,203,.2); border-color: #0453cb; }
.cpa-modal-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1rem; flex-wrap: wrap; }
.cpa-assignment-panel { overflow: visible; }
.cpa-assignment-panel:has(.au-select-trigger--open),
.cpa-assignment-panel:has(.au-up-trigger--open) { position: relative; z-index: 1400; }
.cpa-assignment-composer {
    display: grid;
    grid-template-columns: minmax(260px, 1.35fr) minmax(260px, 1.15fr) minmax(220px, 1fr) auto;
    gap: .85rem;
    align-items: end;
    padding: 1rem;
    border-radius: 8px;
    background: #f8fafc;
    box-shadow: inset 0 0 0 1px #e8ecf1;
}
.cpa-assignment-form { display: grid; }
.cpa-field { min-width: 0; }
.cpa-field .cpa-field-label { margin-top: 0; }
.cpa-field .au-select,
.cpa-field .au-select-trigger,
.cpa-field .au-up,
.cpa-field .au-up-trigger { width: 100%; }
.cpa-assignment-class .au-select-menu { width: min(520px, calc(100vw - 24px)); }
.cpa-assignment-class .au-select-option { align-items: flex-start; gap: .65rem; }
.cpa-assignment-class .au-select-option-label { white-space: normal; line-height: 1.3; overflow: visible; text-overflow: clip; }
.cpa-assignment-user .au-up-menu { width: min(520px, calc(100vw - 24px)); max-width: min(520px, calc(100vw - 24px)); }
.cpa-assignment-submit { min-width: 112px; justify-content: center; }
.cpa-assignment-empty { min-height: 112px; display: grid; place-content: center; }
.cpa-assignment-panel .cpa-row { box-shadow: 0 4px 14px rgba(15,23,42,.04); }
[x-cloak] { display: none !important; }
@media (max-width: 1100px) { .cpa-grid, .cpa-filters, .cpa-hero-kpis, .cpa-assignment-form, .cpa-coverage-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cpa-split, .cpa-note-workbench, .cpa-direction, .cpa-tendances-grille { grid-template-columns: 1fr; } .cpa-assignment-submit { width: 100%; } }
@media (max-width: 900px) { .cpa-audit { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) {
    .cpa-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
    .cpa-hero-title { align-items: flex-start; }
    .cpa-hero-kpis, .cpa-grid, .cpa-filters, .cpa-audit, .cpa-assignment-form, .cpa-coverage-kpis, .cpa-note-students, .cpa-note-command { grid-template-columns: 1fr; }
    .cpa-row { grid-template-columns: 1fr; }
    .cpa-note-command-score { justify-items: start; }
    .cpa-note-subject > summary, .cpa-note-evaluation > summary { align-items: stretch; grid-template-columns: 1fr; }
    .cpa-note-subject-meter span { justify-self: start; }
    .cpa-note-subject-stats { justify-content: flex-start; }
    .cpa-sheet-card .cpa-audit { grid-template-columns: 1fr; }
    .cpa-sheet-title-line { align-items: stretch; flex-direction: column; }
    .cpa-sheet-stage { justify-items: start; }
    .cpa-sheet-card .cpa-row-actions { align-items: stretch; }
    .cpa-sheet-card .cpa-row-actions .cpa-btn { flex: 1 1 auto; justify-content: center; }
    .cpa-assignment-composer { padding: .85rem; }
}
</style>
@endpush
