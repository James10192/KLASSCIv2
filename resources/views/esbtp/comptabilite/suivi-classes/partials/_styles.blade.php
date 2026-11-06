<style>
.sfc-page { max-width:1500px; margin:0 auto; padding:1.25rem; display:flex; flex-direction:column; gap:1.1rem; }

/* Hero : modèle planning-header (icône, titre blanc, indicateurs en rangée 2). */
.sfc-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 45%,#3b7ddb 100%); border-radius:18px; padding:1.75rem 2rem 1.5rem; color:#fff; }
.sfc-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.sfc-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; }
.sfc-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.sfc-crumb { font-size:.76rem; color:rgba(255,255,255,.72); margin-bottom:.15rem; }
.sfc-crumb a { color:#fff; text-decoration:underline; text-underline-offset:2px; }
.sfc-hero h1 { font-size:clamp(1.2rem,2.4vw,1.5rem); font-weight:800; color:#fff; margin:0; }
.sfc-hero-sub { color:rgba(255,255,255,.74); font-size:.86rem; margin:.25rem 0 0; max-width:620px; }
.sfc-hero-actions { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
.sfc-hero .export-menu { z-index:1100; }
.sfc-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; margin-top:1.4rem; }
.sfc-kpi { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.16); border-radius:12px; padding:.9rem 1rem; min-width:0; }
.sfc-kpi-label { font-size:.72rem; color:rgba(255,255,255,.72); font-weight:600; display:flex; align-items:center; gap:.4rem; }
.sfc-kpi-value { font-size:clamp(1.1rem,1.9vw,1.45rem); font-weight:800; color:#fff; margin-top:.3rem; white-space:nowrap; font-variant-numeric:tabular-nums; }
.sfc-kpi-value small { font-size:.7rem; font-weight:600; color:rgba(255,255,255,.7); margin-left:.25rem; }
.sfc-kpi-note { font-size:.72rem; color:rgba(255,255,255,.7); margin-top:.2rem; }
.sfc-kpi-bar { height:5px; border-radius:99px; background:rgba(255,255,255,.18); margin-top:.5rem; overflow:hidden; }
.sfc-kpi-bar i { display:block; height:100%; background:#fff; border-radius:99px; transition:width .3s ease; }

/* Boutons */
.sfc-btn { display:inline-flex; gap:.5rem; align-items:center; justify-content:center; min-height:44px; padding:.55rem .95rem; border:1px solid #d3e0f2; border-radius:10px; background:#fff; color:#0453cb; font-weight:700; font-size:.83rem; text-decoration:none; white-space:nowrap; transition:all .2s ease; }
.sfc-btn:hover { border-color:#0453cb; box-shadow:0 4px 14px rgba(4,83,203,.12); color:#0453cb; }
.sfc-btn:disabled { opacity:.55; cursor:not-allowed; }
.sfc-btn--glass { background:rgba(255,255,255,.15); color:#fff; border-color:rgba(255,255,255,.22); }
.sfc-btn--glass:hover { background:rgba(255,255,255,.24); color:#fff; border-color:rgba(255,255,255,.4); }
.sfc-btn--primary { background:#0453cb; color:#fff; border-color:#0453cb; }
.sfc-btn--primary:hover { background:#033a8e; color:#fff; }
.sfc-btn:focus-visible, .sfc-link:focus-visible, .sfc-chip button:focus-visible { outline:3px solid #9fbef3; outline-offset:2px; }

/* Barre de filtres */
.sfc-filters { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); padding:1rem; display:flex; flex-direction:column; gap:.85rem; }
.sfc-filters-main { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.4fr) minmax(0,1fr) auto; gap:.75rem; align-items:end; }
.sfc-filters-more { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.75rem; padding-top:.85rem; border-top:1px dashed #e2e8f0; }
.sfc-field { display:flex; flex-direction:column; gap:.35rem; min-width:0; }
.sfc-field > label { font-size:.72rem; color:#475569; font-weight:700; }
.sfc-field .au-select { width:100%; max-width:100%; display:flex; }
.sfc-search { position:relative; }
.sfc-search i { position:absolute; left:.8rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.82rem; pointer-events:none; }
.sfc-search input { width:100%; min-height:44px; padding:.6rem .8rem .6rem 2.2rem; border:1px solid #dbe5f3; border-radius:10px; font:inherit; font-size:.85rem; color:#1e293b; }
.sfc-search input:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.12); }
.sfc-filters-actions { display:flex; gap:.5rem; }
.sfc-more-count { display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:20px; padding:0 .35rem; border-radius:99px; background:#0453cb; color:#fff; font-size:.68rem; }
.sfc-chips { display:flex; flex-wrap:wrap; gap:.45rem; align-items:center; }
.sfc-chip { display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .35rem .3rem .7rem; border-radius:99px; background:#eef4fd; color:#0b3f8f; font-size:.75rem; font-weight:600; }
.sfc-chip button { border:0; background:transparent; color:#0453cb; width:22px; height:22px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; }
.sfc-chip button:hover { background:#dbe8fb; }
.sfc-chips-clear { border:0; background:none; color:#64748b; font-size:.75rem; font-weight:600; text-decoration:underline; }

/* À surveiller */
.sfc-watch { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
.sfc-watch-card { background:#fff; border:1px solid #e2e8f0; border-left:4px solid #dc2626; border-radius:12px; padding:.85rem 1rem; display:flex; flex-direction:column; gap:.35rem; min-width:0; }
.sfc-watch-head { display:flex; justify-content:space-between; gap:.5rem; align-items:baseline; }
.sfc-watch-name { font-weight:800; color:#0f172a; font-size:.9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sfc-watch-rate { font-size:.72rem; color:#64748b; white-space:nowrap; }
.sfc-watch-amount { font-size:1.15rem; font-weight:800; color:#b91c1c; font-variant-numeric:tabular-nums; white-space:nowrap; }
.sfc-watch-amount small { font-size:.7rem; color:#64748b; font-weight:600; margin-left:.25rem; }
.sfc-watch-ok { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; border-radius:12px; padding:.8rem 1rem; font-size:.83rem; display:flex; gap:.6rem; align-items:center; }
.sfc-section-title { display:flex; align-items:center; gap:.5rem; font-size:.78rem; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.04em; margin:0 0 .55rem; }

/* Tableau */
.sfc-panel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); overflow:hidden; transition:opacity .2s ease; }
.sfc-panel-head { padding:1rem 1.2rem; display:flex; gap:1rem; align-items:center; justify-content:space-between; flex-wrap:wrap; border-bottom:1px solid #eef2f7; }
.sfc-panel-head h2 { margin:0; font-size:1rem; font-weight:800; color:#0f172a; }
.sfc-panel-head p { margin:.2rem 0 0; font-size:.76rem; color:#64748b; }
.sfc-count { font-size:.78rem; color:#475569; font-weight:600; }
.sfc-table-scroll { overflow-x:auto; }
.sfc-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.sfc-table thead th { background:#f6f9fd; color:#5b6b82; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.03em; padding:.8rem .8rem; text-align:left; white-space:nowrap; border-bottom:1px solid #e2e8f0; }
.sfc-table td { padding:.85rem .8rem; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:middle; }
.sfc-table tbody tr:hover { background:#f8fbff; }
.sfc-table tbody tr:last-child td { border-bottom:0; }
.sfc-num { text-align:right !important; white-space:nowrap; font-variant-numeric:tabular-nums; }
.sfc-class strong { display:block; color:#0f172a; font-weight:750; }
.sfc-class small { display:block; color:#64748b; font-size:.73rem; margin-top:.15rem; }
.sfc-due { color:#b91c1c; font-weight:700; }
.sfc-zero { color:#94a3b8; }
.sfc-rate { min-width:130px; }
.sfc-rate-top { display:flex; justify-content:space-between; font-size:.75rem; font-weight:750; }
.sfc-rate-bar { height:6px; background:#edf2f8; border-radius:99px; margin-top:.35rem; overflow:hidden; }
.sfc-rate-bar i { display:block; height:100%; border-radius:99px; }
.sfc-rate--ok { color:#047857; } .sfc-rate--ok i { background:#10b981; }
.sfc-rate--mid { color:#b45309; } .sfc-rate--mid i { background:#f59e0b; }
.sfc-rate--low { color:#b91c1c; } .sfc-rate--low i { background:#dc2626; }
.sfc-rate--none { color:#94a3b8; }
.sfc-link { display:inline-flex; align-items:center; gap:.35rem; min-height:36px; padding:.35rem .7rem; border:1px solid #d3e0f2; border-radius:9px; background:#f5f9ff; color:#0453cb; font-size:.76rem; font-weight:700; white-space:nowrap; text-decoration:none; }
.sfc-link:hover { background:#0453cb; color:#fff; border-color:#0453cb; }
.sfc-empty { text-align:center; padding:2.5rem 1rem; color:#64748b; }
.sfc-empty i { display:block; font-size:1.6rem; color:#cbd5e1; margin-bottom:.6rem; }
.sfc-foot { padding:.9rem 1.2rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; font-size:.78rem; color:#64748b; border-top:1px solid #eef2f7; }
.sfc-pagination nav { display:flex; max-width:100%; overflow-x:auto; }
.sfc-cards-mobile { display:none; }
.sfc-note { font-size:.76rem; color:#64748b; display:flex; gap:.5rem; align-items:flex-start; padding:0 .2rem; }

/* États */
.sfc-busy { opacity:.55; pointer-events:none; }
.sfc-error { display:flex; align-items:center; gap:.65rem; padding:.75rem 1rem; border:1px solid #fecaca; background:#fef2f2; border-radius:12px; color:#991b1b; font-size:.83rem; }
.sfc-error button { margin-left:auto; border:0; background:transparent; color:inherit; font-weight:700; }
.sfc-sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; }

/* Fenêtre de détail */
.sfc-backdrop { position:fixed; inset:0; background:rgba(8,25,51,.55); z-index:1070; display:flex; align-items:center; justify-content:center; padding:12px; }
.sfc-dialog { background:#fff; border-radius:16px; width:min(1040px,100%); max-height:calc(100dvh - 24px); display:flex; flex-direction:column; box-shadow:0 24px 70px rgba(7,20,45,.3); overflow:hidden; }
.sfc-dialog-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.2rem; border-bottom:1px solid #e2e8f0; }
.sfc-dialog-head h2 { margin:0; font-size:1.05rem; font-weight:800; color:#0f172a; }
.sfc-dialog-close { width:40px; height:40px; border:0; border-radius:10px; background:#f1f5f9; color:#475569; }
.sfc-dialog-close:hover { background:#e2e8f0; }
.sfc-dialog-body { padding:1rem 1.2rem 1.2rem; overflow:auto; }
.sfc-d-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.6rem; margin-bottom:1rem; }
.sfc-d-kpi { background:#f6f9fd; border:1px solid #e2e8f0; border-radius:10px; padding:.65rem .8rem; }
.sfc-d-kpi span { display:block; font-size:.7rem; color:#64748b; font-weight:700; }
.sfc-d-kpi strong { display:block; font-size:1rem; color:#0f172a; font-variant-numeric:tabular-nums; white-space:nowrap; margin-top:.15rem; }
.sfc-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .55rem; border-radius:99px; font-size:.7rem; font-weight:700; white-space:nowrap; }
.sfc-badge--ok { background:#ecfdf5; color:#047857; }
.sfc-badge--late { background:#fef2f2; color:#b91c1c; }
.sfc-badge--open { background:#eff6ff; color:#0453cb; }
.sfc-actions-row { display:flex; gap:.35rem; justify-content:flex-end; }
.sfc-skeleton { height:14px; border-radius:6px; background:linear-gradient(90deg,#eef2f7,#f8fafc,#eef2f7); background-size:200% 100%; animation:sfc-shimmer 1.2s infinite; margin:.6rem 0; }
@keyframes sfc-shimmer { from { background-position:200% 0; } to { background-position:-200% 0; } }

@media (max-width:1150px) {
    .sfc-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .sfc-filters-main { grid-template-columns:1fr 1fr; }
    .sfc-filters-more { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .sfc-watch { grid-template-columns:1fr; }
}
@media (max-width:768px) {
    .sfc-page { padding:.75rem; gap:.8rem; }
    .sfc-hero { padding:1.15rem; border-radius:15px; }
    .sfc-hero-icon { width:44px; height:44px; font-size:1.1rem; }
    .sfc-kpis { gap:.5rem; margin-top:1rem; }
    .sfc-kpi { padding:.7rem .75rem; }
    .sfc-filters-main, .sfc-filters-more { grid-template-columns:1fr; }
    .sfc-filters-actions .sfc-btn { flex:1; }
    .sfc-table-scroll { display:none; }
    .sfc-cards-mobile { display:flex; flex-direction:column; }
    .sfc-mcard { padding:.9rem 1rem; border-bottom:1px solid #f1f5f9; display:flex; flex-direction:column; gap:.55rem; }
    .sfc-mcard:last-child { border-bottom:0; }
    .sfc-mcard-grid { display:grid; grid-template-columns:1fr 1fr; gap:.4rem .8rem; font-size:.78rem; }
    .sfc-mcard-grid span { color:#64748b; display:block; font-size:.7rem; }
    .sfc-mcard-grid strong { font-variant-numeric:tabular-nums; color:#0f172a; }
    .sfc-d-kpis { grid-template-columns:1fr 1fr; }
    .sfc-dialog-body .sfc-table { font-size:.76rem; }
}
</style>
