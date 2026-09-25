<style>
    /* ══════════════════════════════════════════════
       LMD UE Index — Full AJAX with Alpine.js
       Prefix: lu- (lmd-ue)
       ══════════════════════════════════════════════ */

    .lu-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .lu-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem;
        color: #fff; margin-bottom: 1.5rem; overflow: hidden;
        animation: lu-fadeDown .5s ease-out;
    }
    .lu-hero::before { content: ''; position: absolute; top: -60%; right: -10%; width: 420px; height: 420px; background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%); pointer-events: none; }
    .lu-hero::after { content: ''; position: absolute; bottom: -40%; left: 5%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%); pointer-events: none; }
    .lu-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1; }
    .lu-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lu-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0; }
    .lu-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
    .lu-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }
    .lu-hero-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.1rem; border-radius: 10px; font-size: .84rem; font-weight: 600; border: 1.5px solid rgba(255,255,255,.3); color: #fff; background: rgba(255,255,255,.08); text-decoration: none; transition: all .2s; backdrop-filter: blur(4px); cursor: pointer; }
    .lu-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
    .lu-hero-btn--solid { background: #fff; color: #0453cb; border-color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
    .lu-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }
    .lu-hero-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; position: relative; z-index: 1; flex-wrap: wrap; }
    .lu-kpi { flex: 1; min-width: 150px; background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; transition: background .2s; }
    .lu-kpi:hover { background: rgba(255,255,255,.15); }
    .lu-kpi-icon { width: 38px; height: 38px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
    .lu-kpi--ue .lu-kpi-icon { background: rgba(255,255,255,.18); color: #fff; }
    .lu-kpi--ecue .lu-kpi-icon { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .lu-kpi--credits .lu-kpi-icon { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .lu-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1; color: #fff; }
    .lu-kpi-label { font-size: .75rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* ── Filters ── */
    .lu-filters { background: #fff; border-radius: 14px; padding: 1rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03); border: 1px solid #e8ecf1; display: flex; align-items: flex-end; gap: .85rem; flex-wrap: wrap; animation: lu-fadeUp .45s ease-out .1s both; }
    .lu-filter-group { display: flex; flex-direction: column; gap: .3rem; flex: 1; min-width: 140px; }
    .lu-au-full { display: flex !important; width: 100%; }
    .lu-au-full .au-select-trigger { width: 100%; }
    .lu-filter-label { font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
    .lu-filter-control { padding: .5rem .75rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: .86rem; color: #1e293b; background: #f8fafc; transition: all .2s; width: 100%; }
    .lu-filter-control:focus { outline: none; border-color: #0453cb; background: #fff; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }

    /* ── Table card ── */
    .lu-table-card { background: #fff; border-radius: 14px; border: 1px solid #e8ecf1; box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03); overflow: hidden; animation: lu-fadeUp .45s ease-out .2s both; }
    .lu-table-header { padding: 1.15rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; }
    .lu-table-title { font-size: 1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: .5rem; }
    .lu-table-title i { color: #0453cb; font-size: .9rem; }
    .lu-table-count { font-size: .8rem; color: #94a3b8; font-weight: 500; }
    .lu-table-wrapper { overflow-x: auto; }
    .lu-table { width: 100%; border-collapse: collapse; }
    .lu-table thead th { padding: .75rem 1rem; font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; background: #fafbfc; border-bottom: 1px solid #f1f5f9; white-space: nowrap; }
    .lu-table tbody tr { transition: background .15s; border-bottom: 1px solid #f8fafc; }
    .lu-table tbody td { padding: .8rem 1rem; font-size: .87rem; color: #475569; vertical-align: middle; }
    .lu-ue-row { cursor: pointer; user-select: none; }
    .lu-ue-row:hover { background: #f8fbff; }
    .lu-arrow { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 6px; background: #f1f5f9; color: #0453cb; font-size: .65rem; transition: transform .2s, background .2s; }
    .lu-arrow.lu-open { transform: rotate(90deg); background: #e0ecff; }
    .lu-code { font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: .82rem; font-weight: 600; color: #1e293b; letter-spacing: .02em; }
    .lu-name { font-weight: 600; color: #1e293b; }
    .lu-type-badge { display: inline-flex; align-items: center; padding: .2rem .6rem; border-radius: 20px; font-size: .72rem; font-weight: 600; letter-spacing: .01em; }
    .lu-type--fondamentale { background: #dbeafe; color: #1e40af; }
    .lu-type--methodologique { background: #d1fae5; color: #065f46; }
    .lu-type--decouverte { background: #fef3c7; color: #92400e; }
    .lu-type--transversale { background: #e0e7ff; color: #3730a3; }
    .lu-credit-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; padding: .15rem .5rem; border-radius: 6px; font-size: .82rem; font-weight: 700; background: #ecfdf5; color: #059669; }
    .lu-ecue-count { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; padding: .15rem .45rem; border-radius: 6px; font-size: .82rem; font-weight: 700; background: #f1f5f9; color: #334155; }
    .lu-actions { display: flex; gap: .3rem; justify-content: flex-end; }
    .lu-act { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e8ecf1; background: #fff; color: #64748b; font-size: .8rem; cursor: pointer; transition: all .2s; text-decoration: none; }
    .lu-act:hover { color: #fff; text-decoration: none; }
    .lu-act--edit:hover { background: #0453cb; border-color: #0453cb; color: #fff; }
    .lu-act--delete:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
    .lu-sub-row td { background: #fafbfc; border-top: 1px dashed #e8ecf1; padding-top: .6rem !important; padding-bottom: .6rem !important; }
    .lu-ecue-indent { display: inline-flex; align-items: center; gap: .4rem; padding-left: 1.5rem; color: #64748b; font-size: .84rem; }
    .lu-ecue-indent::before { content: '└'; color: #cbd5e1; font-size: .9rem; }
    .lu-ecue-code { font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: .78rem; color: #64748b; }
    .lu-ecue-coeff { font-size: .78rem; color: #94a3b8; }
    .lu-parcours-badges { display: flex; gap: .25rem; flex-wrap: wrap; }
    .lu-parcours-badge { display: inline-flex; align-items: center; gap: .2rem; padding: .12rem .4rem; border-radius: 5px; font-size: .7rem; font-weight: 600; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .lu-parcours-badge-sem { font-size: .6rem; color: #818cf8; }
    /* Portee d'un element : commun a toutes les maquettes, ou reserve a un parcours */
    .lu-portee-badge { display: inline-flex; align-items: center; gap: .25rem; margin-left: .45rem; padding: .1rem .4rem; border-radius: 5px; font-size: .64rem; font-weight: 600; letter-spacing: .02em; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; vertical-align: middle; }
    .lu-double-alerte { display: inline-flex; align-items: center; gap: .3rem; margin-left: .5rem; padding: .12rem .45rem; border-radius: 6px; font-size: .66rem; font-weight: 700; background: rgba(245,158,11,.12); color: #b45309; border: 1px solid rgba(245,158,11,.35); vertical-align: middle; }
    .lu-double-ligne td { background: #fffbeb; }
    .lu-double-texte { font-size: .8rem; color: #92400e; line-height: 1.5; }
    .lu-portee-badge--reserve { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
    .lu-empty { text-align: center; padding: 4rem 2rem; }
    .lu-empty-icon { width: 76px; height: 76px; border-radius: 20px; background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; color: #cbd5e1; margin-bottom: 1.15rem; }
    .lu-empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: .4rem; }
    .lu-empty-text { font-size: .88rem; color: #94a3b8; margin-bottom: 1.25rem; }
    .lu-pagination { padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: .35rem; }
    .lu-page-btn { padding: .35rem .7rem; border-radius: 7px; font-size: .82rem; font-weight: 600; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .15s; }
    .lu-page-btn:hover { background: #f1f5f9; }
    .lu-page-btn--active { background: #0453cb; color: #fff; border-color: #0453cb; }
    .lu-page-btn:disabled { opacity: .4; cursor: default; }

    /* ── Modals ── */
    .lu-modal .modal-content { border-radius: 18px; border: none; box-shadow: 0 25px 80px rgba(0,0,0,.18), 0 8px 24px rgba(4,83,203,.08); overflow: hidden; }
    .lu-modal .modal-header { position: relative; padding: 0; border: none; }
    .lu-modal-hero { padding: 1.75rem 2rem 1.5rem; background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%); color: #fff; position: relative; overflow: hidden; }
    .lu-modal-hero::before { content: ''; position: absolute; top: -50%; right: -15%; width: 320px; height: 320px; background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%); pointer-events: none; }
    .lu-modal-hero-top { display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 1; }
    .lu-modal-hero-left { display: flex; align-items: center; gap: .85rem; }
    .lu-modal-icon { width: 46px; height: 46px; border-radius: 12px; background: rgba(255,255,255,.15); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; flex-shrink: 0; }
    .lu-modal-title { font-size: 1.2rem; font-weight: 700; margin: 0; color: #fff; }
    .lu-modal-subtitle { font-size: .8rem; opacity: .7; margin-top: .15rem; }
    .lu-modal .btn-close { filter: brightness(0) invert(1); opacity: .7; position: relative; z-index: 2; }
    .lu-modal .btn-close:hover { opacity: 1; }
    .lu-modal .modal-body { padding: 1.75rem 2rem; }
    .lu-field-group { background: #f8fafc; border-radius: 12px; border: 1px solid #e8ecf1; padding: 1.25rem; margin-bottom: 1rem; }
    .lu-field-group:last-child { margin-bottom: 0; }
    .lu-field-group-title { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #0453cb; margin-bottom: .85rem; display: flex; align-items: center; gap: .4rem; }
    .lu-field-group-title i { font-size: .65rem; }
    .lu-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem 1.25rem; }
    .lu-field-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem 1.25rem; }
    .lu-field-full { grid-column: 1 / -1; }
    .lu-modal label { font-size: .82rem; font-weight: 600; color: #334155; margin-bottom: .3rem; display: flex; align-items: center; gap: .3rem; }
    .lu-modal label i { font-size: .7rem; color: #94a3b8; }
    .lu-modal .form-control, .lu-modal .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: .55rem .85rem; font-size: .88rem; transition: all .2s; background: #fff; }
    .lu-modal .form-control:focus, .lu-modal .form-select:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); background: #fff; }
    .lu-modal textarea.form-control { min-height: 70px; resize: vertical; }
    .lu-modal .form-text { font-size: .76rem; color: #94a3b8; margin-top: .25rem; }
    .lu-modal .modal-footer { border-top: 1px solid #e8ecf1; padding: 1rem 2rem; background: #fafbfc; display: flex; gap: .5rem; justify-content: flex-end; }
    .lu-modal-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.2rem; border-radius: 10px; font-size: .85rem; font-weight: 600; border: none; cursor: pointer; transition: all .2s; }
    .lu-modal-btn--cancel { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .lu-modal-btn--cancel:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .lu-modal-btn--submit { background: #0453cb; color: #fff; box-shadow: 0 2px 8px rgba(4,83,203,.2); }
    .lu-modal-btn--submit:hover { background: #0340a0; }
    .lu-modal.fade .modal-dialog { transform: translateY(20px) scale(.98); transition: transform .25s ease-out, opacity .2s; }
    .lu-modal.show .modal-dialog { transform: translateY(0) scale(1); }

    /* Sem chips */
    .lp-sem-chip { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; padding: .15rem .35rem; border-radius: 5px; font-size: .7rem; font-weight: 700; cursor: pointer; transition: all .15s; border: 1px solid #e2e8f0; background: #f8fafc; color: #94a3b8; user-select: none; }
    .lp-sem-chip--on { border-color: #4338ca; background: #4338ca; color: #fff; }
    .lp-sem-chip:hover:not(.lp-sem-chip--on) { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }

    /* Toast notification */
    /* Un refus explique OU aller corriger : il tient en plusieurs lignes, et il
       lui faut la place et le temps d'etre lu. Sans largeur bornee, le message
       s'etirait sur toute la fenetre. */
    .lu-toast { position: fixed; top: 1rem; right: 1rem; z-index: 9999; padding: .65rem 1.1rem; border-radius: 10px; font-size: .85rem; font-weight: 600; color: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); transition: all .3s; max-width: min(440px, calc(100vw - 2rem)); white-space: normal; line-height: 1.45; }
    .lu-toast--success { background: #059669; }
    .lu-toast--error { background: #dc2626; }

    @keyframes lu-fadeDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes lu-fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    [x-cloak] { display: none !important; }

    @media (max-width: 768px) {
        .lu-hero { padding: 1.5rem; border-radius: 14px; }
        .lu-hero-top { flex-direction: column; }
        .lu-hero-kpis { flex-direction: column; }
        .lu-filters { flex-direction: column; align-items: stretch; }
        .lu-field-row, .lu-field-row-3 { grid-template-columns: 1fr; }
    }
</style>
