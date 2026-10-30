    /* Filtres BTS/LMD : grille premium responsive, sans rechargement. */
    .ln-filter-panel { margin: 0 0 1.4rem; padding: 1.2rem 1.35rem; background: #fff; border: 1px solid #e8ecf1; border-radius: 14px; box-shadow: 0 2px 12px rgba(15,23,42,.035); }
    .ln-filter-heading { display: flex; align-items: center; gap: .55rem; color: #1e293b; font-size: .94rem; font-weight: 700; margin-bottom: .9rem; }
    .ln-filter-heading i { color: #0453cb; }
    .ln-filter-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; }
    .ln-filter-field { display: flex; flex-direction: column; min-width: 0; gap: .35rem; }
    .ln-filter-field label { color: #475569; font-size: .75rem; font-weight: 700; }
    .ln-filter-field .au-select, .ln-eval-field .au-select { display: flex; width: 100%; min-width: 0; }
    .ln-filter-search { width: 100%; min-height: 44px; border: 1px solid #e2e8f0; border-radius: 10px; padding: .55rem .85rem; font-size: .86rem; color: #1e293b; }
    .ln-filter-search:focus { outline: 0; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
    .ln-filter-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .7rem; margin-top: 1rem; }
    .ln-filter-btn { border: 1px solid #0453cb; background: #0453cb; color: #fff; padding: .55rem 1rem; border-radius: 9px; font-size: .82rem; font-weight: 700; }
    .ln-filter-btn--reset { border-color: #e2e8f0; background: #fff; color: #475569; }
    .ln-filter-btn:focus-visible { outline: 3px solid rgba(4,83,203,.2); outline-offset: 2px; }
    .ln-filter-count { margin-left: auto; color: #64748b; font-size: .82rem; }
    .ln-filter-feedback { margin-top: .55rem; color: #b91c1c; font-size: .8rem; min-height: 1em; }
    #lmd-classes-results.ln-results-loading { opacity: .45; pointer-events: none; }
    .ln-modal-toolbar .au-select { flex: 1 1 195px; min-width: 0; }
    .ln-modal-toolbar .au-select-trigger { min-height: 42px; }
    .ln-eval-field .au-select-trigger { min-height: 40px; }
    @media (max-width: 1000px) { .ln-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) {
        .ln-filter-panel { padding: 1rem; }
        .ln-filter-grid { grid-template-columns: minmax(0, 1fr); }
        .ln-filter-actions { align-items: stretch; }
        .ln-filter-count { margin-left: 0; width: 100%; }
    }

