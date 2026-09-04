@extends('layouts.app')

@section('title', 'Unités d\'Enseignement (UE) — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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
    .lu-toast { position: fixed; top: 1rem; right: 1rem; z-index: 9999; padding: .65rem 1.1rem; border-radius: 10px; font-size: .85rem; font-weight: 600; color: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); transition: all .3s; }
    .lu-toast--success { background: #059669; }
    .lu-toast--error { background: #dc2626; }

    /* ── Maquette de travail ──
       Elle remplace l'ancien filtre « Parcours » : deux selecteurs de parcours
       cote a cote laisseraient croire a deux notions differentes. */
    .lu-maquette-bar { background: #fff; border: 1px solid #e8ecf1; border-left: 4px solid #0453cb; border-radius: 14px; padding: .9rem 1.25rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; box-shadow: 0 1px 3px rgba(0,0,0,.04); animation: lu-fadeUp .45s ease-out .05s both; }
    .lu-maquette-lead { display: flex; align-items: center; gap: .6rem; min-width: 0; }
    .lu-maquette-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(4,83,203,.08); color: #0453cb; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
    .lu-maquette-lead-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
    .lu-maquette-lead-sub { font-size: .74rem; color: #94a3b8; }
    .lu-maquette-picker { flex: 1; min-width: 220px; display: flex; }
    .lu-maquette-picker .au-select-trigger { width: 100%; }
    .lu-maquette-etat { font-size: .76rem; font-weight: 600; padding: .3rem .7rem; border-radius: 8px; }
    .lu-maquette-etat--une { background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.2); }
    .lu-maquette-etat--toutes { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

    /* Rappel de la maquette dans chaque modal : un modal recouvre la page,
       sans ce rappel on ne voit plus sur quelle maquette on ecrit. */
    .lu-maquette-rappel { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem; border-radius: 8px; font-size: .72rem; font-weight: 600; background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.28); color: #fff; }
    .lu-maquette-rappel--sombre { background: rgba(4,83,203,.08); border-color: rgba(4,83,203,.2); color: #0453cb; }

    /* Avertissement — jaune. Le bleu est la couleur de marque, il ne signale rien. */
    .lu-warn { display: flex; align-items: flex-start; gap: .45rem; padding: .55rem .8rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 9px; font-size: .76rem; color: #92400e; line-height: 1.45; }
    .lu-warn i { margin-top: 2px; flex-shrink: 0; }

    /* Compteur d'elements de la ligne d'unite */
    .lu-count-sub { font-size: .68rem; color: #94a3b8; margin-top: .2rem; white-space: nowrap; }

    /* Choix « quels elements entrent » : cartes radio monochromes */
    .lu-choix { display: flex; flex-direction: column; gap: .45rem; }
    .lu-choix-card { display: flex; align-items: flex-start; gap: .6rem; padding: .65rem .85rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff; cursor: pointer; transition: border-color .15s, background .15s; }
    .lu-choix-card:hover { border-color: #c7d4e5; background: #f8fbff; }
    .lu-choix-card--on { border-color: #0453cb; background: rgba(4,83,203,.05); }
    .lu-choix-card--off { opacity: .5; cursor: not-allowed; }
    .lu-choix-titre { font-size: .84rem; font-weight: 600; color: #1e293b; }
    .lu-choix-aide { font-size: .74rem; color: #64748b; margin-top: .1rem; }
    .lu-choix-liste { margin-top: .5rem; max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: .2rem; border: 1px solid #e8ecf1; border-radius: 10px; padding: .5rem; }
    .lu-choix-item { display: flex; align-items: center; gap: .5rem; font-size: .82rem; color: #334155; padding: .25rem .35rem; border-radius: 6px; }
    .lu-choix-item:hover { background: #f8fafc; }

    /* Fenetres pilotees par Alpine (confirmation, journal).
       Un confirm() natif se valide en tapant Entree au reflexe : ce n'est pas
       une confirmation, et il ne peut rien expliquer. */
    .lu-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.45); backdrop-filter: blur(2px); z-index: 1080; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .lu-dialog { background: #fff; border-radius: 18px; width: 100%; max-width: 540px; box-shadow: 0 25px 80px rgba(0,0,0,.22); overflow: hidden; }
    .lu-dialog--large { max-width: 680px; }
    .lu-dialog-hero { padding: 1.35rem 1.6rem; background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%); color: #fff; display: flex; align-items: center; gap: .8rem; }
    .lu-dialog-hero--danger { background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 55%, #dc2626 100%); }
    .lu-dialog-hero-icon { width: 42px; height: 42px; border-radius: 11px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .lu-dialog-title { font-size: 1.05rem; font-weight: 700; margin: 0; }
    .lu-dialog-sub { font-size: .77rem; opacity: .75; margin-top: .1rem; }
    .lu-dialog-body { padding: 1.35rem 1.6rem; font-size: .87rem; color: #334155; line-height: 1.55; }
    .lu-dialog-footer { padding: .95rem 1.6rem; background: #fafbfc; border-top: 1px solid #e8ecf1; display: flex; justify-content: flex-end; gap: .5rem; }
    .lu-dialog-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.2rem; border-radius: 10px; font-size: .85rem; font-weight: 600; border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; }
    .lu-dialog-btn:hover { background: #f1f5f9; }
    .lu-dialog-btn--primary { background: #0453cb; border-color: #0453cb; color: #fff; }
    .lu-dialog-btn--primary:hover { background: #0340a0; color: #fff; }
    .lu-dialog-btn--danger { background: #dc2626; border-color: #dc2626; color: #fff; }
    .lu-dialog-btn--danger:hover { background: #b91c1c; color: #fff; }

    /* Journal des gestes */
    .lu-journal-liste { display: flex; flex-direction: column; gap: .5rem; max-height: 420px; overflow-y: auto; }
    .lu-journal-item { display: flex; align-items: flex-start; gap: .7rem; padding: .7rem .9rem; border: 1px solid #e8ecf1; border-radius: 10px; background: #fff; }
    .lu-journal-item--annulee { opacity: .55; }
    .lu-journal-puce { width: 30px; height: 30px; border-radius: 8px; background: rgba(4,83,203,.08); color: #0453cb; display: flex; align-items: center; justify-content: center; font-size: .74rem; flex-shrink: 0; }
    .lu-journal-libelle { font-size: .84rem; font-weight: 600; color: #1e293b; }
    .lu-journal-meta { font-size: .73rem; color: #94a3b8; margin-top: .12rem; }
    .lu-journal-annuler { border: 1.5px solid #e2e8f0; background: #fff; color: #0453cb; border-radius: 8px; font-size: .74rem; font-weight: 700; padding: .3rem .7rem; cursor: pointer; white-space: nowrap; }
    .lu-journal-annuler:hover { background: rgba(4,83,203,.06); border-color: #0453cb; }

    /* Toast avec action (annuler tout de suite, sans ouvrir le journal) */
    .lu-toast { display: flex; align-items: center; gap: .8rem; }
    .lu-toast-action { background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.45); color: #fff; border-radius: 7px; font-size: .76rem; font-weight: 700; padding: .22rem .6rem; cursor: pointer; }
    .lu-toast-action:hover { background: rgba(255,255,255,.32); }

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
@endpush

@section('content')
<div class="lu-page" x-data="ueManager()" x-init="loadUes()">

    {{-- ══ Toast ══ --}}
    <div class="lu-toast" :class="toast.type === 'error' ? 'lu-toast--error' : 'lu-toast--success'"
         x-show="toast.show" x-transition x-cloak>
        <span x-text="toast.message"></span>
        {{-- L'annulation est proposee la ou le geste vient d'avoir lieu :
             l'ouvrir depuis le journal suppose de savoir que le journal existe. --}}
        <button type="button" class="lu-toast-action" x-show="toast.annulation" x-cloak
                @click="annulerDepuisToast()">Annuler</button>
    </div>

    {{-- ══ Hero ══ --}}
    <div class="lu-hero">
        <div class="lu-hero-top">
            <div class="lu-hero-left">
                <div class="lu-hero-icon"><i class="fas fa-cubes"></i></div>
                <div class="lu-hero-info">
                    <h1>Unités d'Enseignement</h1>
                    <p>Gestion des UE et de leurs ECUEs associés</p>
                </div>
            </div>
            <div style="display:flex; gap:.5rem; align-items:center;">
                <x-lmd-reconciliation-trigger variant="glass" />
                <button type="button" class="lu-hero-btn--solid lu-hero-btn" @click="openCreateModal()">
                    <i class="fas fa-plus"></i>Nouvelle UE
                </button>
            </div>
        </div>
        <div class="lu-hero-kpis">
            <div class="lu-kpi lu-kpi--ue">
                <div class="lu-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="lu-kpi-value" x-text="pagination.total ?? 0"></div>
                    <div class="lu-kpi-label">Unités (UE)</div>
                </div>
            </div>
            <div class="lu-kpi lu-kpi--ecue">
                <div class="lu-kpi-icon"><i class="fas fa-book"></i></div>
                <div>
                    <div class="lu-kpi-value" x-text="ues.reduce((s,u) => s + (u.matieres_count || 0), 0)"></div>
                    <div class="lu-kpi-label">Matières (ECUE)</div>
                </div>
            </div>
            <div class="lu-kpi lu-kpi--credits">
                <div class="lu-kpi-icon"><i class="fas fa-award"></i></div>
                <div>
                    <div class="lu-kpi-value" x-text="ues.reduce((s,u) => s + (u.credit || 0), 0)"></div>
                    <div class="lu-kpi-label">Crédits ECTS</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Maquette de travail ══
         Elle prend la place de l'ancien filtre « Parcours » : c'est le meme
         geste, nomme par ce qu'il fait. Elle est memorisee d'une visite a
         l'autre et rappelee dans chaque modal. --}}
    <div class="lu-maquette-bar">
        <div class="lu-maquette-lead">
            <div class="lu-maquette-icon"><i class="fas fa-route"></i></div>
            <div>
                <div class="lu-maquette-lead-title">Maquette de travail</div>
                <div class="lu-maquette-lead-sub">Ce que vous voyez et ce que vous saisissez suit ce choix.</div>
            </div>
        </div>
        <div class="lu-maquette-picker">
            <x-au-select
                name="maquette_travail"
                icon="fa-graduation-cap"
                placeholder="Toutes les maquettes"
                :searchable="true"
                :options="$parcours->mapWithKeys(fn ($p) => [$p->id => trim(($p->code ? $p->code.' — ' : '').$p->name)])->all()"
                x-model="filters.parcours_id"
                @change="changerMaquette()" />
        </div>
        <span class="lu-maquette-etat lu-maquette-etat--une" x-show="filters.parcours_id" x-cloak
              x-text="'Vous travaillez sur ' + nomMaquette(filters.parcours_id)"></span>
        <span class="lu-maquette-etat lu-maquette-etat--toutes" x-show="!filters.parcours_id">
            <i class="fas fa-exclamation-triangle"></i> Aucune maquette choisie — vous voyez tout
        </span>
    </div>

    {{-- ══ Filters ══ --}}
    <div class="lu-filters">
        <div class="lu-filter-group" style="flex:2;">
            <label class="lu-filter-label">Recherche</label>
            <input type="text" class="lu-filter-control" x-model.debounce.400ms="filters.search" placeholder="Code ou intitulé..." @input="loadUes()">
        </div>
        <div class="lu-filter-group" style="max-width:220px;">
            <label class="lu-filter-label">Type UE</label>
            <x-au-select
                name="type_ue"
                icon="fa-tag"
                placeholder="Tous les types"
                :options="collect(\App\Enums\TypeUE::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()"
                x-model="filters.type_ue"
                @change="loadUes()" />
        </div>
    </div>

    {{-- ══ Table ══ --}}
    <div class="lu-table-card">
        <div class="lu-table-header">
            <div class="lu-table-title"><i class="fas fa-list-ul"></i> Liste des UEs</div>
            <div class="lu-table-count" x-text="(pagination.total ?? 0) + ' unité' + ((pagination.total ?? 0) > 1 ? 's' : '')"></div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" style="text-align:center; padding:3rem; color:#94a3b8;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <div style="margin-top:.75rem;">Chargement...</div>
        </div>

        {{-- Empty --}}
        <template x-if="!loading && ues.length === 0">
            <div class="lu-empty">
                <div class="lu-empty-icon"><i class="fas fa-cubes"></i></div>
                <div class="lu-empty-title">Aucune Unité d'Enseignement</div>
                <div class="lu-empty-text">Créez votre première UE pour structurer votre offre LMD.</div>
                <button type="button" class="lu-hero-btn--solid lu-hero-btn" @click="openCreateModal()" style="display:inline-flex;">
                    <i class="fas fa-plus"></i>Créer une UE
                </button>
            </div>
        </template>

        {{-- Table --}}
        <div class="lu-table-wrapper" x-show="!loading && ues.length > 0">
            <table class="lu-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Code</th>
                        <th>Intitulé</th>
                        <th>Type UE</th>
                        <th>Maquettes</th>
                        <th style="text-align:center;">Crédits</th>
                        <th style="text-align:center;">Éléments</th>
                        <th style="text-align:right; width:160px;">Actions</th>
                    </tr>
                </thead>
                <template x-for="ue in ues" :key="ue.id">
                    <tbody>
                        {{-- UE row --}}
                        <tr class="lu-ue-row" @click="openRow = openRow === ue.id ? null : ue.id">
                            <td>
                                <span class="lu-arrow" :class="{ 'lu-open': openRow === ue.id }">&#9654;</span>
                            </td>
                            <td><span class="lu-code" x-text="ue.code"></span></td>
                            <td><span class="lu-name" x-text="ue.name"></span></td>
                            <td>
                                <span x-show="ue.type_ue" class="lu-type-badge"
                                      :class="'lu-type--' + ue.type_ue"
                                      x-text="ue.type_ue ? ue.type_ue.charAt(0).toUpperCase() + ue.type_ue.slice(1) : ''"></span>
                                <span x-show="!ue.type_ue" style="color:#94a3b8;">—</span>
                            </td>
                            <td>
                                <div class="lu-parcours-badges" x-show="ue.parcours && ue.parcours.length">
                                    <template x-for="p in (ue.parcours || [])" :key="p.id">
                                        <span class="lu-parcours-badge">
                                            <span x-text="p.code"></span>
                                            <span class="lu-parcours-badge-sem" x-text="'(' + (p.semestres || []).map(s => 'S'+s).join(',') + ')'"></span>
                                        </span>
                                    </template>
                                </div>
                                <span x-show="!ue.parcours || !ue.parcours.length" style="color:#94a3b8; font-size:.8rem;">—</span>
                            </td>
                            <td style="text-align:center;"><span class="lu-credit-pill" x-text="ue.credit ?? 0"></span></td>
                            {{-- Ce que voit la maquette de travail, pas le total tous parcours
                                 confondus : un compteur qui additionne les elements des autres
                                 parcours ment a la personne qui saisit. --}}
                            <td style="text-align:center;">
                                <span class="lu-ecue-count" x-text="ue.elements_visibles ?? 0"></span>
                                <div class="lu-count-sub" x-show="(ue.elements_reserves ?? 0) > 0" x-cloak
                                     x-text="'dont ' + ue.elements_reserves + (ue.elements_reserves > 1 ? ' propres' : ' propre') + ' à un parcours'"></div>
                            </td>
                            <td @click.stop>
                                <div class="lu-actions">
                                    <button type="button" class="lu-act" title="Ajouter un élément" style="color:#059669;" @click="openEcueModal(ue)">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <button type="button" class="lu-act" title="Lier à des parcours" style="color:#4338ca;" @click="openLinkParcoursModal(ue)">
                                        <i class="fas fa-route"></i>
                                    </button>
                                    <button type="button" class="lu-act" title="Historique des gestes sur les maquettes" @click="ouvrirJournal(ue)">
                                        <i class="fas fa-clock-rotate-left"></i>
                                    </button>
                                    <button type="button" class="lu-act lu-act--edit" title="Modifier" @click="openEditModal(ue)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="lu-act lu-act--delete" title="Supprimer" @click="deleteUe(ue)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        {{-- ECUE sub-rows --}}
                        <template x-for="ecue in (ue.ecues || [])" :key="ecue.id">
                            <tr class="lu-sub-row" x-show="openRow === ue.id" x-cloak>
                                <td></td>
                                <td><span class="lu-ecue-code" x-text="ecue.code || '—'"></span></td>
                                <td><span class="lu-ecue-indent" x-text="ecue.name"></span></td>
                                <td><span class="lu-ecue-coeff" x-text="'Coeff. ' + (ecue.coefficient ?? '—')"></span></td>
                                {{-- Les memes pastilles que la ligne d'unite : l'element dit
                                     dans quelles maquettes il se trouve. Pas de mot
                                     « commun » : le jour ou un troisieme parcours est
                                     rattache, un element « commun » y basculerait sans
                                     que personne ne l'ait decide. --}}
                                <td>
                                    <div class="lu-parcours-badges" x-show="(ecue.maquettes || []).length">
                                        <template x-for="m in (ecue.maquettes || [])" :key="m.id">
                                            <span class="lu-parcours-badge">
                                                <span x-text="m.code"></span>
                                                <span class="lu-parcours-badge-sem" x-text="'(' + (m.semestres || []).map(s => 'S'+s).join(',') + ')'"></span>
                                            </span>
                                        </template>
                                    </div>
                                    <span x-show="!(ecue.maquettes || []).length" style="color:#94a3b8; font-size:.8rem;">—</span>
                                </td>
                                <td style="text-align:center;"><span class="lu-credit-pill" style="background:#f1f5f9; color:#334155;" x-text="ecue.credit ?? '—'"></span></td>
                                <td style="text-align:center;">
                                    <span x-show="ecue.ordre > 0" style="font-size:.75rem; color:#94a3b8;" x-text="'#' + ecue.ordre"></span>
                                </td>
                                <td @click.stop>
                                    <div class="lu-actions">
                                        <button type="button" class="lu-act lu-act--edit" title="Modifier cet élément" @click="openEcueEditModal(ue, ecue)">
                                            <i class="fas fa-pen" style="font-size:.7rem;"></i>
                                        </button>
                                        <button type="button" class="lu-act lu-act--delete"
                                                :title="libelleRetrait(ue, ecue)" @click="retirerEcue(ue, ecue)">
                                            <i class="fas fa-right-from-bracket" style="font-size:.7rem;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr class="lu-sub-row" x-show="openRow === ue.id && (!ue.ecues || ue.ecues.length === 0)" x-cloak>
                            <td></td>
                            <td colspan="7" style="color:#94a3b8; font-style:italic; font-size:.84rem;">Aucun ECUE rattaché.</td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="lu-pagination" x-show="pagination.last_page > 1">
            <button class="lu-page-btn" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <template x-for="p in paginationPages()" :key="p">
                <button class="lu-page-btn" :class="{ 'lu-page-btn--active': p === pagination.current_page }"
                        x-text="p" @click="goPage(p)"></button>
            </template>
            <button class="lu-page-btn" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    {{-- ══ Confirmation ══
         Elle remplace les confirm() natifs : elle nomme la maquette concernée
         et dit ce qui n'est PAS supprimé. --}}
    <div class="lu-overlay" x-show="confirmation.open" x-cloak x-transition.opacity
         @keydown.escape.window="confirmation.open = false">
        <div class="lu-dialog" @click.outside="confirmation.open = false">
            <div class="lu-dialog-hero" :class="confirmation.danger ? 'lu-dialog-hero--danger' : ''">
                <div class="lu-dialog-hero-icon"><i class="fas" :class="confirmation.danger ? 'fa-triangle-exclamation' : 'fa-circle-question'"></i></div>
                <div>
                    <h5 class="lu-dialog-title" x-text="confirmation.titre"></h5>
                    <div class="lu-dialog-sub" x-text="confirmation.sousTitre" x-show="confirmation.sousTitre"></div>
                </div>
            </div>
            <div class="lu-dialog-body">
                <div x-text="confirmation.corps"></div>
                <div class="lu-warn" style="margin-top:.85rem;" x-show="confirmation.note" x-cloak>
                    <i class="fas fa-circle-info"></i><span x-text="confirmation.note"></span>
                </div>
            </div>
            <div class="lu-dialog-footer">
                <button type="button" class="lu-dialog-btn" @click="confirmation.open = false">Annuler</button>
                <button type="button" class="lu-dialog-btn"
                        :class="confirmation.danger ? 'lu-dialog-btn--danger' : 'lu-dialog-btn--primary'"
                        @click="validerConfirmation()" x-text="confirmation.libelleAction"></button>
            </div>
        </div>
    </div>

    {{-- ══ Journal des gestes ══
         « Pourquoi cette matière n'est plus dans TIR ? » — la réponse se lit ici. --}}
    <div class="lu-overlay" x-show="journal.open" x-cloak x-transition.opacity
         @keydown.escape.window="journal.open = false">
        <div class="lu-dialog lu-dialog--large" @click.outside="journal.open = false">
            <div class="lu-dialog-hero">
                <div class="lu-dialog-hero-icon"><i class="fas fa-clock-rotate-left"></i></div>
                <div>
                    <h5 class="lu-dialog-title">Historique des maquettes</h5>
                    <div class="lu-dialog-sub" x-text="journal.ueNom"></div>
                </div>
            </div>
            <div class="lu-dialog-body">
                <div x-show="journal.chargement" style="text-align:center; padding:1.5rem; color:#94a3b8;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
                <div x-show="!journal.chargement && journal.entrees.length === 0" x-cloak
                     style="text-align:center; padding:1.5rem; color:#94a3b8; font-size:.86rem;">
                    Aucun geste enregistré sur les maquettes de cette unité.
                </div>
                <div class="lu-journal-liste" x-show="!journal.chargement && journal.entrees.length > 0" x-cloak>
                    <template x-for="entree in journal.entrees" :key="entree.id">
                        <div class="lu-journal-item" :class="entree.annulee ? 'lu-journal-item--annulee' : ''">
                            <div class="lu-journal-puce"><i class="fas" :class="iconeAction(entree.action)"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="lu-journal-libelle" x-text="entree.libelle"></div>
                                <div class="lu-journal-meta">
                                    <span x-text="entree.auteur"></span> — <span x-text="entree.date"></span>
                                    <span x-show="entree.annulee" x-cloak> · annulé</span>
                                </div>
                            </div>
                            <button type="button" class="lu-journal-annuler" x-show="entree.annulable" x-cloak
                                    @click="annulerGeste(entree)">Annuler ce geste</button>
                        </div>
                    </template>
                </div>
            </div>
            <div class="lu-dialog-footer">
                <button type="button" class="lu-dialog-btn" @click="journal.open = false">Fermer</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ MODAL UE — Create / Edit ══ --}}
<div class="modal fade lu-modal" id="modalUE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="formUE" method="POST">
                @csrf
                <input type="hidden" name="_method" id="ue_method" value="POST">
                <div class="modal-header">
                    <div class="lu-modal-hero w-100">
                        <div class="lu-modal-hero-top">
                            <div class="lu-modal-hero-left">
                                <div class="lu-modal-icon"><i class="fas fa-cubes"></i></div>
                                <div>
                                    <h5 class="lu-modal-title"><span id="modalUETitleText">Nouvelle Unité d'Enseignement</span></h5>
                                    <div class="lu-modal-subtitle">
                                        Configurer les paramètres de l'UE
                                        {{-- Rappel de la maquette : un modal recouvre la page. --}}
                                        <span class="lu-maquette-rappel" style="margin-left:.4rem;"><i class="fas fa-route"></i><span id="ue_rappel_maquette">Toutes les maquettes</span></span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="lu-field-group">
                        <div class="lu-field-group-title"><i class="fas fa-circle"></i> Identité de l'UE</div>
                        <div class="lu-field-row">
                            <div>
                                <label for="ue_name"><i class="fas fa-font"></i> Intitulé <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ue_name" name="name" required placeholder="Ex: Technologie de Construction">
                            </div>
                            <div>
                                <label for="ue_code"><i class="fas fa-hashtag"></i> Code</label>
                                <input type="text" class="form-control" id="ue_code" name="code" placeholder="Ex: MAG2001" style="font-family: 'SF Mono', 'Consolas', monospace;">
                                <small class="text-muted" style="font-size:.72rem;">Optionnel — laisser vide pour une UE virtuelle UEMOA (ex: <em>UE de Méthodologie</em>).</small>
                            </div>
                        </div>
                    </div>
                    <div class="lu-field-group">
                        <div class="lu-field-group-title"><i class="fas fa-circle"></i> Paramètres académiques</div>
                        <div class="lu-field-row">
                            <div>
                                <label for="ue_credit"><i class="fas fa-award"></i> Crédits CECT <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="ue_credit" name="credit" required min="1" max="30" value="3">
                            </div>
                            <div>
                                <label for="ue_type_ue"><i class="fas fa-tag"></i> Type UE <span class="text-danger">*</span></label>
                                <select class="form-select" id="ue_type_ue" name="type_ue" required>
                                    @foreach(\App\Enums\TypeUE::cases() as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-text" style="margin-top:.5rem;">
                            <i class="fas fa-info-circle me-1"></i>Le semestre, la filière et le niveau sont définis via la liaison au parcours (bouton <i class="fas fa-route" style="color:#4338ca;"></i>).
                        </div>
                    </div>
                    <div class="lu-field-group">
                        <div class="lu-field-group-title"><i class="fas fa-circle"></i> Description</div>
                        <label for="ue_description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea class="form-control" id="ue_description" name="description" rows="2" placeholder="Optionnel..."></textarea>
                    </div>
                    <div id="ue_errors" class="alert alert-danger d-none" style="border-radius: 10px; margin-top: 1rem;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lu-modal-btn lu-modal-btn--cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                    <button type="submit" class="lu-modal-btn lu-modal-btn--submit" id="ue_submit_btn"><i class="fas fa-save"></i> <span id="ue_submit_text">Enregistrer</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ MODAL ECUE — Create new / Link existing (restored from original) ══ --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    #modalECUE.modal { overflow: visible !important; }
    #modalECUE .modal-dialog { overflow: visible !important; }
    #modalECUE .modal-content { overflow: visible !important; }
    #ecue_matiere_select { display: none; }
    #modalECUE .select2-container { width: 100% !important; }
    #modalECUE .select2-container--bootstrap-5 .select2-selection { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: .45rem .75rem; font-size: .88rem; min-height: 42px; }
    #modalECUE .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 1.6; color: #1e293b; font-weight: 500; padding-right: 3.5rem; }
    #modalECUE .select2-container--bootstrap-5 .select2-selection--single .select2-selection__clear { position: absolute; right: 1.8rem; top: 50%; transform: translateY(-50%); font-size: .9rem; color: #94a3b8; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #f1f5f9; line-height: 1; padding: 0; }
    #modalECUE .select2-container--bootstrap-5 .select2-selection--single .select2-selection__clear:hover { color: #fff; background: #dc2626; }
    #modalECUE .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    #modalECUE .select2-container--bootstrap-5.select2-container--open .select2-selection { border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.08); }
    .select2-container--bootstrap-5 .select2-dropdown { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 12px 40px rgba(0,0,0,.14); padding: 6px; }
    .select2-container--bootstrap-5 .select2-results__option { border-radius: 8px; padding: .5rem .75rem; font-weight: 500; font-size: .86rem; }
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] { background: linear-gradient(135deg, #059669, #34d399) !important; color: #fff; }
    .ecue-tab-btn { flex: 1; padding: .6rem .5rem; border: none; background: transparent; font-size: .82rem; font-weight: 600; color: #94a3b8; cursor: pointer; border-bottom: 2.5px solid transparent; transition: all .2s; border-radius: 0; }
    .ecue-tab-btn:hover { color: #334155; background: #f8fafc; }
    .ecue-tab-btn.active { color: #059669; border-bottom-color: #059669; }
    .ecue-tab-content { display: none; }
    .ecue-tab-content.active { display: block; }
    .ecue-matiere-preview { display: none; margin-top: .6rem; padding: .6rem .85rem; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; font-size: .82rem; }
    .ecue-matiere-preview strong { color: #065f46; }
</style>
<div class="modal fade" id="modalECUE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow:0 25px 80px rgba(0,0,0,.18); overflow:hidden;">
            <form id="ecue_form" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="ecue_method" value="POST">
                <input type="hidden" name="matiere_id" id="ecue_matiere_id" value="">
                {{-- Maquette de travail transmise au serveur : c'est elle qui
                     decide si l'element est reserve a un parcours. --}}
                <input type="hidden" name="parcours_travail_id" id="ecue_parcours_travail" value="">
                <input type="hidden" name="reserve" id="ecue_reserve" value="0">
                <div style="padding:1.5rem 1.75rem 1.25rem; background:linear-gradient(135deg, #065f46 0%, #059669 50%, #34d399 100%); color:#fff; position:relative; overflow:hidden;">
                    <div style="position:absolute; top:-50%; right:-15%; width:280px; height:280px; background:radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%); pointer-events:none;"></div>
                    <div style="display:flex; align-items:center; justify-content:space-between; position:relative; z-index:1;">
                        <div style="display:flex; align-items:center; gap:.75rem;">
                            <div style="width:42px; height:42px; border-radius:11px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1rem;">
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <div>
                                <h5 style="font-size:1.1rem; font-weight:700; margin:0;" id="ecue_modal_title">Nouvel élément</h5>
                                <div style="font-size:.78rem; opacity:.7;">
                                    UE : <strong id="ecue_ue_label">—</strong>
                                    <span class="lu-maquette-rappel" style="margin-left:.4rem;"><i class="fas fa-route"></i><span id="ecue_rappel_maquette">Toutes les maquettes</span></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:brightness(0) invert(1); opacity:.7; flex-shrink:0; margin:0;"></button>
                    </div>
                </div>
                <div class="modal-body" style="padding:1.5rem 1.75rem;">
                    <div id="ecue_error" style="display:none; padding:.6rem 1rem; border-radius:10px; background:#fef2f2; color:#dc2626; font-size:.85rem; margin-bottom:1rem; border:1px solid #fecaca;"></div>

                    {{-- Jauge crédits --}}
                    <div id="ecue_credit_gauge" style="display:none; margin-bottom:1rem; padding:.65rem .85rem; border-radius:10px; border:1px solid #e8ecf1; background:#f8fafc;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.4rem;">
                            <span style="font-size:.78rem; font-weight:600; color:#334155;"><i class="fas fa-award" style="color:#059669; margin-right:.3rem;"></i>Crédits de l'UE</span>
                            <span id="ecue_credit_label" style="font-size:.82rem; font-weight:700; color:#1e293b;">0 / 0</span>
                        </div>
                        <div style="height:6px; border-radius:3px; background:#e5e7eb; overflow:hidden;">
                            <div id="ecue_credit_bar" style="height:100%; border-radius:3px; background:#059669; transition:width .3s, background .3s; width:0%;"></div>
                        </div>
                        <div id="ecue_credit_warning" style="display:none; margin-top:.35rem; font-size:.74rem; color:#dc2626; font-weight:600;">
                            <i class="fas fa-exclamation-triangle me-1"></i><span id="ecue_credit_warning_text"></span>
                        </div>
                    </div>

                    {{-- Jauge impossible a calculer : on le dit plutot que d'afficher
                         la reference d'une autre maquette. Une jauge fausse est pire
                         que pas de jauge. --}}
                    <div id="ecue_credit_absente" class="lu-warn" style="display:none; margin-bottom:1rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Aucun total de crédits n'est défini pour cette unité dans la maquette courante : la jauge est masquée. Le total sera vérifié à l'enregistrement.</span>
                    </div>

                    {{-- A quelle maquette cet element appartient-il ? --}}
                    <div id="ecue_maquette_bloc" style="display:none; margin-bottom:1rem; background:#f8fafc; border:1px solid #e8ecf1; border-radius:12px; padding:1rem 1.1rem;">
                        <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#0453cb; margin-bottom:.6rem;">
                            <i class="fas fa-route" style="font-size:.65rem; margin-right:.3rem;"></i>Où cet élément doit-il figurer ?
                        </div>
                        <div class="lu-choix">
                            <label class="lu-choix-card" id="ecue_maquette_seule_card">
                                <input type="radio" name="ecue_maquette_choix" value="seule" id="ecue_maquette_seule" checked style="margin-top:2px; accent-color:#0453cb;">
                                <span>
                                    <span class="lu-choix-titre">Cette maquette uniquement — <span id="ecue_maquette_seule_nom">—</span></span>
                                    <span class="lu-choix-aide">Les autres parcours de cette unité ne le verront pas.</span>
                                </span>
                            </label>
                            <label class="lu-choix-card" id="ecue_maquette_toutes_card">
                                <input type="radio" name="ecue_maquette_choix" value="toutes" id="ecue_maquette_toutes" style="margin-top:2px; accent-color:#0453cb;">
                                <span>
                                    <span class="lu-choix-titre">Toutes les maquettes de cette unité</span>
                                    <span class="lu-choix-aide" id="ecue_maquette_toutes_liste">Il apparaîtra dans chacune d'elles.</span>
                                </span>
                            </label>
                        </div>
                        <div class="lu-warn" id="ecue_maquette_indispo" style="display:none; margin-top:.6rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Sur cette instance, un élément appartient encore à toutes les maquettes de son unité. Le choix ci-dessus sera pris en compte dès que le partage par parcours sera disponible.</span>
                        </div>
                    </div>

                    {{-- Tabs: Créer / Lier existant --}}
                    <div id="ecue_tabs" style="display:flex; border-bottom:1.5px solid #e8ecf1; margin-bottom:1rem;">
                        <button type="button" class="ecue-tab-btn active" onclick="switchEcueTab('create')">
                            <i class="fas fa-plus me-1"></i>Créer un ECUE
                        </button>
                        <button type="button" class="ecue-tab-btn" onclick="switchEcueTab('link')">
                            <i class="fas fa-link me-1"></i>Lier un existant
                        </button>
                    </div>

                    {{-- TAB 1: Créer --}}
                    <div id="ecue_tab_create" class="ecue-tab-content active">
                        <div style="background:#f8fafc; border-radius:12px; border:1px solid #e8ecf1; padding:1.25rem;">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem 1.25rem;">
                                <div>
                                    <label style="font-size:.82rem; font-weight:600; color:#334155; margin-bottom:.3rem; display:block;"><i class="fas fa-tag" style="font-size:.7rem; color:#94a3b8; margin-right:.25rem;"></i>Nom</label>
                                    <input type="text" class="form-control" name="name" id="ecue_name" placeholder="Ex: Résistance des Matériaux" style="border-radius:10px; border:1.5px solid #e2e8f0; padding:.55rem .85rem; font-size:.88rem;">
                                </div>
                                <div>
                                    <label style="font-size:.82rem; font-weight:600; color:#334155; margin-bottom:.3rem; display:block;"><i class="fas fa-barcode" style="font-size:.7rem; color:#94a3b8; margin-right:.25rem;"></i>Code</label>
                                    <input type="text" class="form-control" name="code" id="ecue_code" placeholder="Ex: RDM101" style="border-radius:10px; border:1.5px solid #e2e8f0; padding:.55rem .85rem; font-size:.88rem;">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: Lier existant --}}
                    <div id="ecue_tab_link" class="ecue-tab-content">
                        <div style="background:#f8fafc; border-radius:12px; border:1px solid #e8ecf1; padding:1.25rem;">
                            <label style="font-size:.82rem; font-weight:600; color:#334155; margin-bottom:.3rem; display:block;"><i class="fas fa-search" style="font-size:.7rem; color:#94a3b8; margin-right:.25rem;"></i>Rechercher une matière existante</label>
                            <select id="ecue_matiere_select" style="width:100%;"><option value="">— Sélectionner une matière —</option></select>
                            <div id="ecue_matiere_preview" class="ecue-matiere-preview">
                                <strong id="ecue_preview_name"></strong>
                                <span id="ecue_preview_code" style="color:#64748b; font-size:.78rem; margin-left:.5rem;"></span>
                            </div>
                            <div id="ecue_link_loading" style="display:none; padding:1rem; text-align:center; color:#94a3b8; font-size:.84rem;">
                                <i class="fas fa-spinner fa-spin me-1"></i>Chargement des matières...
                            </div>
                        </div>
                    </div>

                    {{-- Champs pivot communs --}}
                    <div style="background:#f8fafc; border-radius:12px; border:1px solid #e8ecf1; padding:1.25rem; margin-top:.75rem;">
                        <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#059669; margin-bottom:.65rem;">
                            <i class="fas fa-sliders-h" style="font-size:.65rem; margin-right:.3rem;"></i>Paramètres dans cette UE
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:.75rem;">
                            <div>
                                <label style="font-size:.82rem; font-weight:600; color:#334155; margin-bottom:.3rem; display:block;"><i class="fas fa-balance-scale" style="font-size:.7rem; color:#94a3b8; margin-right:.25rem;"></i>Coefficient</label>
                                <input type="number" class="form-control" name="coefficient_ecue" id="ecue_coefficient" min="0" step="0.5" placeholder="1" style="border-radius:10px; border:1.5px solid #e2e8f0; padding:.55rem .85rem; font-size:.88rem;">
                            </div>
                            <div>
                                <label style="font-size:.82rem; font-weight:600; color:#334155; margin-bottom:.3rem; display:block;"><i class="fas fa-award" style="font-size:.7rem; color:#94a3b8; margin-right:.25rem;"></i>Crédits</label>
                                <input type="number" class="form-control" name="credit_ecue" id="ecue_credit" min="0" placeholder="2" oninput="updateCreditGauge()" style="border-radius:10px; border:1.5px solid #e2e8f0; padding:.55rem .85rem; font-size:.88rem;">
                            </div>
                            <div>
                                <label style="font-size:.82rem; font-weight:600; color:#334155; margin-bottom:.3rem; display:block;"><i class="fas fa-sort-numeric-up" style="font-size:.7rem; color:#94a3b8; margin-right:.25rem;"></i>Ordre</label>
                                <input type="number" class="form-control" name="ordre_bulletin" id="ecue_ordre" min="0" value="0" placeholder="0" style="border-radius:10px; border:1.5px solid #e2e8f0; padding:.55rem .85rem; font-size:.88rem;">
                            </div>
                        </div>
                        <div style="margin-top:.6rem; padding:.45rem .65rem; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; font-size:.72rem; color:#92400e; display:flex; align-items:flex-start; gap:.35rem;">
                            <i class="fas fa-info-circle" style="margin-top:1px; flex-shrink:0;"></i>
                            <span>Valeurs contextuelles à cette UE. Un même ECUE peut avoir des valeurs différentes dans une autre UE.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8ecf1; padding:1rem 1.75rem; background:#fafbfc;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px; font-weight:600; font-size:.85rem; padding:.5rem 1.1rem;">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    {{-- Une vingtaine d'unites et une soixantaine d'elements par
                         semestre : sans ce bouton, c'est autant d'ouvertures de
                         fenetre. Il garde l'unite, la maquette de travail et le
                         choix de maquette. --}}
                    <button type="button" id="ecue_submit_encore" style="display:none; border-radius:10px; font-weight:600; font-size:.85rem; padding:.5rem 1.1rem; background:#fff; color:#059669; border:1.5px solid #059669;">
                        <i class="fas fa-plus me-1"></i>Enregistrer et ajouter un autre
                    </button>
                    <button type="submit" id="ecue_submit" style="border-radius:10px; font-weight:600; font-size:.85rem; padding:.5rem 1.1rem; background:#059669; color:#fff; border:none; box-shadow:0 2px 8px rgba(5,150,105,.2);">
                        <i class="fas fa-check me-1"></i><span id="ecue_submit_text">Créer l'élément</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ MODAL Lier Parcours ══ --}}
<div class="modal fade lu-modal" id="modalLinkParcours" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="lu-modal-hero w-100" style="background: linear-gradient(135deg, #312e81 0%, #4338ca 50%, #6366f1 100%);">
                    <div class="lu-modal-hero-top">
                        <div class="lu-modal-hero-left">
                            <div class="lu-modal-icon"><i class="fas fa-route"></i></div>
                            <div>
                                <h5 class="lu-modal-title">Lier à des Parcours</h5>
                                <div class="lu-modal-subtitle">
                                    UE : <strong id="lp_ue_label">—</strong>
                                    <span class="lu-maquette-rappel" style="margin-left:.4rem;"><i class="fas fa-route"></i><span id="lp_rappel_maquette">Toutes les maquettes</span></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <div id="lp_error" style="display:none; padding:.65rem 1rem; border-radius:10px; background:#fef2f2; color:#dc2626; font-size:.85rem; margin-bottom:1rem; border:1px solid #fecaca;"></div>
                <div id="lp_loading" style="padding:2rem; text-align:center; color:#94a3b8;"><i class="fas fa-spinner fa-spin fa-2x"></i><div style="margin-top:.75rem;">Chargement...</div></div>
                <div id="lp_content" style="display:none;">
                    <p style="font-size:.82rem; color:#64748b; margin-bottom:1rem;"><i class="fas fa-info-circle me-1"></i>Cochez les parcours. Cliquez sur les semestres.</p>
                    <div id="lp_checkboxes" style="display:flex; flex-direction:column; gap:.4rem; max-height:300px; overflow-y:auto;"></div>

                    {{-- Le geste le plus lourd de l'ecran : cocher un parcours y fait
                         entrer tous les elements de l'unite et y deplace ses credits.
                         On dit combien, et on demande lesquels. --}}
                    <div id="lp_impact" style="margin-top:1rem;">
                        <div class="lu-warn" id="lp_impact_texte"></div>
                    </div>

                    <div id="lp_elements" style="margin-top:.85rem; display:none;">
                        <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#0453cb; margin-bottom:.5rem;">
                            Quels éléments entrent dans les maquettes nouvellement cochées ?
                        </div>
                        <div class="lu-choix">
                            <label class="lu-choix-card" data-lp-choix="tous">
                                <input type="radio" name="lp_elements_choix" value="tous" checked style="margin-top:2px; accent-color:#0453cb;">
                                <span>
                                    <span class="lu-choix-titre">Tous les éléments</span>
                                    <span class="lu-choix-aide" id="lp_choix_tous_aide">Chaque élément de l'unité entre dans les nouvelles maquettes.</span>
                                </span>
                            </label>
                            <label class="lu-choix-card" data-lp-choix="aucun">
                                <input type="radio" name="lp_elements_choix" value="aucun" style="margin-top:2px; accent-color:#0453cb;">
                                <span>
                                    <span class="lu-choix-titre">Aucun pour l'instant</span>
                                    <span class="lu-choix-aide">Le parcours est rattaché à l'unité, vous ajouterez ses éléments ensuite.</span>
                                </span>
                            </label>
                            <label class="lu-choix-card" data-lp-choix="choisis">
                                <input type="radio" name="lp_elements_choix" value="choisis" style="margin-top:2px; accent-color:#0453cb;">
                                <span>
                                    <span class="lu-choix-titre">Choisir</span>
                                    <span class="lu-choix-aide">Vous désignez les éléments qui entrent.</span>
                                </span>
                            </label>
                        </div>
                        <div class="lu-choix-liste" id="lp_elements_liste" style="display:none;"></div>
                        <div class="lu-warn" id="lp_elements_indispo" style="display:none; margin-top:.6rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Sur cette instance, un élément appartient encore à toutes les maquettes de son unité : il n'est pas possible de n'en faire entrer qu'une partie. Liez le parcours, puis retirez de sa maquette ce qui ne doit pas y figurer — le geste reste annulable.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lu-modal-btn lu-modal-btn--cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                <button type="button" class="lu-modal-btn lu-modal-btn--submit" id="lp_submit" style="background:#4338ca;">
                    <i class="fas fa-check"></i> <span id="lp_submit_text">Enregistrer</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/fr.js"></script>
<script>
const CSRF = '{{ csrf_token() }}';
const BASE = '/esbtp/lmd/ue';

@php
    // Prepares hors de l'attribut Alpine : un tableau litteral multiligne dans
    // une directive Blade fait mal compter les accolades au compilateur.
    $luParcoursOptions = $parcours->map(fn ($p) => [
        'id' => (int) $p->id,
        'code' => $p->code,
        'name' => $p->name,
    ])->values();
    $luReglages = $reglagesMaquette ?? ['reserver_par_defaut' => true, 'masquer_jauge_sans_reference' => true, 'partage_disponible' => false];
@endphp

// Reglages d'instance : rien de tout cela n'est fige dans le code.
const LU_REGLAGES = @json($luReglages);
const LU_PARCOURS = @json($luParcoursOptions);
const LU_MEMOIRE_MAQUETTE = 'lmd.ue.maquette_travail';

// La maquette de travail est memorisee : elle ne se redemande pas a chaque
// visite. L'URL prime, pour qu'un lien partage reste fidele.
function luMaquetteInitiale(depuisUrl) {
    if (depuisUrl) return String(depuisUrl);
    try {
        return window.localStorage.getItem(LU_MEMOIRE_MAQUETTE) || '';
    } catch (e) {
        return '';
    }
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function ueManager() {
    return {
        ues: [],
        loading: true,
        openRow: null,
        filters: {
            search: @json(request('search', '')),
            parcours_id: luMaquetteInitiale(@json((string) request('parcours_id', ''))),
            type_ue: @json(request('type_ue', '')),
        },
        pagination: { current_page: 1, last_page: 1, total: 0 },
        toast: { show: false, message: '', type: 'success', annulation: null },
        confirmation: { open: false, titre: '', sousTitre: '', corps: '', note: '', libelleAction: 'Confirmer', danger: false, action: null },
        journal: { open: false, chargement: false, ueId: null, ueNom: '', entrees: [] },

        // ── Load UEs via AJAX ──
        async loadUes(page) {
            this.loading = true;
            const p = page || 1;
            const params = new URLSearchParams({ format: 'json', page: p });
            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.parcours_id) params.set('parcours_id', this.filters.parcours_id);
            if (this.filters.type_ue) params.set('type_ue', this.filters.type_ue);

            try {
                const resp = await fetch(`${BASE}?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await resp.json();
                this.ues = data.ues || [];
                this.pagination = data.pagination || { current_page: 1, last_page: 1, total: 0 };
            } catch (e) { console.error(e); this.showToast('Erreur de chargement', 'error'); }
            this.loading = false;
        },

        goPage(p) { if (p >= 1 && p <= this.pagination.last_page) this.loadUes(p); },

        paginationPages() {
            const c = this.pagination.current_page, l = this.pagination.last_page;
            const pages = [];
            for (let i = Math.max(1, c - 2); i <= Math.min(l, c + 2); i++) pages.push(i);
            return pages;
        },

        showToast(msg, type = 'success', annulation = null) {
            this.toast = { show: true, message: msg, type, annulation };
            // Un geste annulable laisse plus de temps : le message disparait
            // avant qu'on ait fini de lire, l'annulation serait perdue.
            setTimeout(() => this.toast.show = false, annulation ? 9000 : 3000);
        },

        // ── Maquette de travail ──
        nomMaquette(id) {
            const p = LU_PARCOURS.find(x => String(x.id) === String(id));
            if (!p) return 'toutes les maquettes';
            return (p.code ? p.code + ' — ' : '') + p.name;
        },

        // Le libelle rappele dans les modals : un modal recouvre la page, sans
        // ce rappel on ne voit plus sur quelle maquette on ecrit.
        rappelMaquette() {
            return this.filters.parcours_id
                ? 'Maquette : ' + this.nomMaquette(this.filters.parcours_id)
                : 'Toutes les maquettes';
        },

        changerMaquette() {
            try {
                window.localStorage.setItem(LU_MEMOIRE_MAQUETTE, this.filters.parcours_id || '');
            } catch (e) { /* navigation privee : la memoire est un confort, pas une dependance */ }

            // L'URL suit, pour qu'un lien partage montre la meme maquette.
            const url = new URL(window.location.href);
            if (this.filters.parcours_id) url.searchParams.set('parcours_id', this.filters.parcours_id);
            else url.searchParams.delete('parcours_id');
            window.history.replaceState({}, '', url);

            this.loadUes();
        },

        // ── Confirmation (remplace confirm()) ──
        demanderConfirmation(options) {
            this.confirmation = {
                open: true,
                titre: options.titre || 'Confirmer',
                sousTitre: options.sousTitre || '',
                corps: options.corps || '',
                note: options.note || '',
                libelleAction: options.libelleAction || 'Confirmer',
                danger: !!options.danger,
                action: options.action,
            };
        },

        async validerConfirmation() {
            const action = this.confirmation.action;
            this.confirmation.open = false;
            if (typeof action === 'function') await action();
        },

        // ── Journal des gestes ──
        iconeAction(action) {
            if (action === 'liaison_parcours') return 'fa-route';
            if (action === 'retrait_element') return 'fa-right-from-bracket';
            return 'fa-plus';
        },

        async ouvrirJournal(ue) {
            this.journal = { open: true, chargement: true, ueId: ue.id, ueNom: ue.name, entrees: [] };
            try {
                const resp = await fetch(`${BASE}/${ue.id}/journal`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await resp.json();
                this.journal.entrees = data.entrees || [];
            } catch (e) {
                this.showToast('Historique indisponible', 'error');
            }
            this.journal.chargement = false;
        },

        async annulerGeste(entree) {
            await this.annulerEntree(this.journal.ueId, entree.id);
            if (this.journal.open) await this.ouvrirJournal({ id: this.journal.ueId, name: this.journal.ueNom });
        },

        async annulerDepuisToast() {
            const cible = this.toast.annulation;
            this.toast.show = false;
            if (cible) await this.annulerEntree(cible.ueId, cible.journalId);
        },

        async annulerEntree(ueId, entreeId) {
            try {
                const resp = await fetch(`${BASE}/${ueId}/journal/${entreeId}/annuler`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();
                if (data.success) {
                    this.showToast(data.message || 'Geste annulé');
                    this.loadUes(this.pagination.current_page);
                } else {
                    this.showToast(data.message || "Annulation impossible", 'error');
                }
            } catch (e) { this.showToast('Erreur réseau', 'error'); }
        },

        // ── Create UE ──
        openCreateModal() {
            document.getElementById('formUE').reset();
            document.getElementById('ue_method').value = 'POST';
            document.getElementById('formUE').action = `${BASE}`;
            document.getElementById('modalUETitleText').textContent = 'Nouvelle Unité d\'Enseignement';
            document.getElementById('ue_submit_text').textContent = 'Enregistrer';
            document.getElementById('ue_credit').value = '3';
            document.getElementById('ue_errors').classList.add('d-none');
            document.getElementById('ue_rappel_maquette').textContent = this.rappelMaquette();
            new bootstrap.Modal(document.getElementById('modalUE')).show();
        },

        // ── Edit UE ──
        async openEditModal(ue) {
            document.getElementById('formUE').reset();
            document.getElementById('ue_method').value = 'PUT';
            document.getElementById('formUE').action = `${BASE}/${ue.id}`;
            document.getElementById('modalUETitleText').textContent = 'Modifier l\'UE';
            document.getElementById('ue_submit_text').textContent = 'Mettre à jour';
            document.getElementById('ue_errors').classList.add('d-none');
            document.getElementById('ue_rappel_maquette').textContent = this.rappelMaquette();

            try {
                const resp = await fetch(`${BASE}/${ue.id}/json`, { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();
                document.getElementById('ue_name').value = data.name || '';
                document.getElementById('ue_code').value = data.code || '';
                document.getElementById('ue_credit').value = data.credit || '';
                document.getElementById('ue_type_ue').value = data.type_ue || '';
                document.getElementById('ue_description').value = data.description || '';
            } catch (e) { console.error(e); }

            new bootstrap.Modal(document.getElementById('modalUE')).show();
        },

        // ── Delete UE ──
        deleteUe(ue) {
            this.demanderConfirmation({
                titre: 'Supprimer l\'unité « ' + ue.name + ' » ?',
                sousTitre: this.rappelMaquette(),
                corps: 'L\'unité disparaîtra de toutes ses maquettes'
                    + ((ue.parcours || []).length ? ' (' + ue.parcours.map(p => p.code).join(', ') + ')' : '')
                    + '.',
                note: 'Les matières ne sont pas supprimées de l\'école : elles sortent seulement de cette unité.',
                libelleAction: 'Supprimer l\'unité',
                danger: true,
                action: () => this.supprimerUe(ue),
            });
        },

        async supprimerUe(ue) {
            try {
                const resp = await fetch(`${BASE}/${ue.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();
                if (data.success) {
                    this.ues = this.ues.filter(u => u.id !== ue.id);
                    this.pagination.total--;
                    this.showToast('UE supprimée');
                } else {
                    this.showToast(data.message || 'Erreur', 'error');
                }
            } catch (e) { this.showToast('Erreur réseau', 'error'); }
        },

        // ── ECUE modals (delegate to full standalone functions) ──
        // Le contexte de maquette voyage avec l'ouverture : le modal doit
        // pouvoir rappeler sur quelle maquette on ecrit, et reserver l'element.
        contexteMaquette() {
            return {
                parcoursId: this.filters.parcours_id || null,
                rappel: this.rappelMaquette(),
                nom: this.filters.parcours_id ? this.nomMaquette(this.filters.parcours_id) : null,
            };
        },

        openEcueModal(ue) {
            // Crédits déjà consommés, vus depuis la maquette de travail : ue.ecues
            // ne contient que ce que cette maquette voit.
            const creditsUsed = (ue.ecues || []).reduce((s, e) => s + (parseInt(e.credit) || 0), 0);
            openEcueCreateModal(ue, creditsUsed, this.contexteMaquette());
        },

        openEcueEditModal(ue, ecue) {
            const creditsUsed = (ue.ecues || []).reduce((s, e) => s + (parseInt(e.credit) || 0), 0);
            openEcueEditModalFn(ue, {
                id: ecue.id,
                name: ecue.name,
                code: ecue.code,
                coefficient_ecue: ecue.coefficient,
                credit_ecue: ecue.credit,
                ordre_bulletin: ecue.ordre || 0,
            }, creditsUsed, this.contexteMaquette());
        },

        // ── Retirer un élément d'une maquette ──
        // « Détacher » ne disait ni d'où, ni ce qui restait. Le titre nomme la
        // maquette, et la note dit ce qui n'est PAS supprimé.
        libelleRetrait(ue, ecue) {
            if (this.filters.parcours_id && LU_REGLAGES.partage_disponible) {
                return 'Retirer de la maquette ' + this.nomMaquette(this.filters.parcours_id);
            }
            return 'Retirer de l\'unité « ' + ue.name + ' »';
        },

        retirerEcue(ue, ecue) {
            const cible = this.filters.parcours_id && LU_REGLAGES.partage_disponible
                ? this.nomMaquette(this.filters.parcours_id)
                : null;

            this.demanderConfirmation({
                titre: cible
                    ? 'Retirer « ' + ecue.name + ' » de la maquette ' + cible + ' ?'
                    : 'Retirer « ' + ecue.name + ' » de l\'unité « ' + ue.name + ' » ?',
                sousTitre: this.rappelMaquette(),
                corps: cible
                    ? 'Les autres maquettes de cette unité continueront de le voir.'
                    : 'Il sortira de toutes les maquettes de cette unité'
                        + ((ue.parcours || []).length ? ' (' + ue.parcours.map(p => p.code).join(', ') + ')' : '')
                        + '.',
                note: 'La matière n\'est pas supprimée de l\'école, elle sort seulement de cette unité.',
                libelleAction: 'Retirer',
                danger: true,
                action: () => this.confirmerRetraitEcue(ue, ecue),
            });
        },

        async confirmerRetraitEcue(ue, ecue) {
            const params = new URLSearchParams();
            if (this.filters.parcours_id) params.set('parcours_travail_id', this.filters.parcours_id);
            try {
                const resp = await fetch(`${BASE}/${ue.id}/ecue/${ecue.id}?${params}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();
                if (data.success) {
                    // Rechargement plutôt que retrait local : les compteurs et
                    // les pastilles de maquette changent aussi.
                    this.loadUes(this.pagination.current_page);
                    this.showToast(
                        data.message || 'Élément retiré',
                        'success',
                        data.journal_id ? { ueId: ue.id, journalId: data.journal_id } : null
                    );
                } else {
                    this.showToast(data.message || 'Erreur', 'error');
                }
            } catch (e) { this.showToast('Erreur réseau', 'error'); }
        },

        // ── Link Parcours ──
        _linkParcoursUeId: null,

        async openLinkParcoursModal(ue) {
            this._linkParcoursUeId = ue.id;
            document.getElementById('lp_ue_label').textContent = ue.name;
            document.getElementById('lp_rappel_maquette').textContent = this.rappelMaquette();
            document.getElementById('lp_loading').style.display = 'block';
            document.getElementById('lp_content').style.display = 'none';
            document.getElementById('lp_error').style.display = 'none';
            preparerImpactLiaison(ue);
            new bootstrap.Modal(document.getElementById('modalLinkParcours')).show();

            try {
                const resp = await fetch(`${BASE}/${ue.id}/parcours-disponibles`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const data = await resp.json();
                const container = document.getElementById('lp_checkboxes');
                container.innerHTML = '';
                (data.lies || []).forEach(p => container.insertAdjacentHTML('beforeend', buildParcoursCheckbox(p, true)));
                (data.disponibles || []).forEach(p => container.insertAdjacentHTML('beforeend', buildParcoursCheckbox(p, false)));
                if ((data.lies || []).length === 0 && (data.disponibles || []).length === 0) {
                    container.innerHTML = '<div style="padding:1.5rem; text-align:center; color:#94a3b8;">Aucun parcours trouvé.</div>';
                }
                document.getElementById('lp_loading').style.display = 'none';
                document.getElementById('lp_content').style.display = 'block';
            } catch (e) {
                document.getElementById('lp_loading').style.display = 'none';
                document.getElementById('lp_error').style.display = 'block';
            }
        },
    };
}

// ── Build parcours checkbox with sem chips ──
function buildParcoursCheckbox(p, checked) {
    const activeSems = p.semestres || [];
    const hasAnySem = activeSems.length > 0;
    const semChips = [1,2,3,4,5,6,7,8,9,10].map(s => {
        const active = activeSems.includes(s);
        return `<span class="lp-sem-chip ${active ? 'lp-sem-chip--on' : ''}" data-parcours-id="${p.id}" data-sem="${s}" onclick="this.classList.toggle('lp-sem-chip--on'); var row=this.closest('.lp-row'); var cb=row.querySelector('.lp-parcours-check'); cb.checked=!!row.querySelector('.lp-sem-chip--on');">S${s}</span>`;
    }).join('');
    return `<div class="lp-row" style="display:flex; align-items:center; gap:.65rem; padding:.6rem .85rem; border-radius:10px; background:${hasAnySem ? '#eef2ff' : '#f8fafc'}; border:1.5px solid ${hasAnySem ? '#4338ca' : '#e8ecf1'}; margin-bottom:.1rem;">
        <input type="checkbox" class="lp-parcours-check" value="${p.id}" ${hasAnySem ? 'checked' : ''} style="width:1.1em; height:1.1em; accent-color:#4338ca; cursor:pointer; flex-shrink:0;">
        <div style="flex:1; min-width:0;">
            <div style="font-size:.86rem; font-weight:600; color:#1e293b;">${escHtml(p.code || '')} — ${escHtml(p.name)}</div>
            <div style="display:flex; gap:.25rem; flex-wrap:wrap; margin-top:.35rem;">${semChips}</div>
        </div>
    </div>`;
}

// ── Impact de la liaison : ce que le geste va reellement faire ──
// Cocher un parcours y fait entrer TOUS les elements de l'unite et y deplace
// ses credits. On l'ecrit avant le geste, chiffres a l'appui, et on demande
// lesquels entrent.
let lpUeCourante = null;

function preparerImpactLiaison(ue) {
    lpUeCourante = ue;
    const partages = ue.elements_partages || [];
    const credits = ue.credits_partages || 0;

    const texte = document.getElementById('lp_impact_texte');
    texte.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>'
        + 'Cocher un parcours y fera entrer <strong>' + partages.length + ' élément'
        + (partages.length > 1 ? 's' : '') + '</strong> de cette unité'
        + (credits > 0 ? ' et y déplacera <strong>' + credits + ' crédit' + (credits > 1 ? 's' : '') + '</strong>' : '')
        + '. Décocher un parcours retire l\'unité entière de sa maquette.'
        + '</span>';

    // La liste ne sert qu'a designer ce qui entre : sans element partage, il
    // n'y a rien a choisir.
    const bloc = document.getElementById('lp_elements');
    bloc.style.display = partages.length ? 'block' : 'none';

    const aide = document.getElementById('lp_choix_tous_aide');
    aide.textContent = 'Les ' + partages.length + ' élément' + (partages.length > 1 ? 's' : '')
        + ' de l\'unité entrent dans les nouvelles maquettes.';

    const liste = document.getElementById('lp_elements_liste');
    liste.innerHTML = partages.map(e =>
        '<label class="lu-choix-item"><input type="checkbox" class="lp-element-check" value="' + e.id
        + '" checked style="accent-color:#0453cb;"><span>' + escHtml(e.name)
        + (e.code ? ' <span style="color:#94a3b8; font-size:.74rem;">(' + escHtml(e.code) + ')</span>' : '')
        + '</span></label>'
    ).join('');
    liste.style.display = 'none';

    // Tant que le partage par parcours n'existe pas, restreindre est un
    // mensonge : on le dit, et on n'offre pas le choix.
    const indispo = document.getElementById('lp_elements_indispo');
    indispo.style.display = LU_REGLAGES.partage_disponible ? 'none' : 'flex';
    document.querySelectorAll('#lp_elements input[name="lp_elements_choix"]').forEach(radio => {
        const restreint = radio.value !== 'tous';
        radio.disabled = restreint && !LU_REGLAGES.partage_disponible;
        radio.closest('.lu-choix-card').classList.toggle('lu-choix-card--off', radio.disabled);
    });
    const parDefaut = document.querySelector('#lp_elements input[value="tous"]');
    if (parDefaut) parDefaut.checked = true;
    majChoixLiaison();
}

function majChoixLiaison() {
    const choix = document.querySelector('#lp_elements input[name="lp_elements_choix"]:checked');
    const valeur = choix ? choix.value : 'tous';
    document.getElementById('lp_elements_liste').style.display = valeur === 'choisis' ? 'flex' : 'none';
    document.querySelectorAll('#lp_elements .lu-choix-card').forEach(card => {
        const radio = card.querySelector('input[name="lp_elements_choix"]');
        card.classList.toggle('lu-choix-card--on', radio && radio.checked);
    });
}

document.addEventListener('change', function (ev) {
    if (ev.target && ev.target.name === 'lp_elements_choix') majChoixLiaison();
});

// ── Save Link Parcours (global, called by button onclick) ──
document.getElementById('lp_submit').addEventListener('click', async function() {
    const mgr = Alpine.$data(document.querySelector('[x-data]'));
    const btn = this;

    const checkboxes = document.querySelectorAll('#lp_checkboxes .lp-parcours-check:checked');
    const parcours = Array.from(checkboxes).map(cb => {
        const semChips = document.querySelectorAll(`.lp-sem-chip--on[data-parcours-id="${cb.value}"]`);
        return { id: cb.value, semestres: Array.from(semChips).map(c => parseInt(c.dataset.sem)) || [1] };
    }).filter(p => p.semestres.length > 0);

    const choix = document.querySelector('#lp_elements input[name="lp_elements_choix"]:checked');
    const modeElements = choix ? choix.value : 'tous';
    const elementIds = Array.from(document.querySelectorAll('#lp_elements_liste .lp-element-check:checked'))
        .map(c => parseInt(c.value));

    btn.disabled = true;
    document.getElementById('lp_submit_text').textContent = 'Enregistrement...';
    document.getElementById('lp_error').style.display = 'none';

    try {
        const resp = await fetch(`${BASE}/${mgr._linkParcoursUeId}/sync-parcours`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                parcours,
                elements: modeElements,
                element_ids: modeElements === 'choisis' ? elementIds : [],
            })
        });
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalLinkParcours')).hide();
            mgr.loadUes(mgr.pagination.current_page);
            mgr.showToast(
                'Maquettes mises à jour',
                'success',
                data.journal_id ? { ueId: mgr._linkParcoursUeId, journalId: data.journal_id } : null
            );
        } else {
            const err = document.getElementById('lp_error');
            err.textContent = data.message || 'Erreur';
            err.style.display = 'block';
        }
    } catch (e) { document.getElementById('lp_error').style.display = 'block'; }
    btn.disabled = false;
    document.getElementById('lp_submit_text').textContent = 'Enregistrer';
});

// ── UE Form submit (create/edit) ──
document.getElementById('formUE').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errorsDiv = document.getElementById('ue_errors');
    errorsDiv.classList.add('d-none');
    const btn = document.getElementById('ue_submit_btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';

    try {
        const resp = await fetch(this.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(this),
        });
        const data = await resp.json();
        if (!resp.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().map(m => escHtml(m)).join('<br>') : escHtml(data.message || 'Erreur');
            errorsDiv.innerHTML = msgs;
            errorsDiv.classList.remove('d-none');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('modalUE')).hide();
        const mgr = Alpine.$data(document.querySelector('[x-data]'));
        mgr.loadUes(mgr.pagination.current_page);
        mgr.showToast(data.message || 'UE enregistrée');
    } catch (err) {
        errorsDiv.textContent = 'Erreur réseau.';
        errorsDiv.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> <span id="ue_submit_text">Enregistrer</span>';
    }
});

// ================================================================
//  MODAL ECUE — Full credit gauge, tabs, Select2 (restored)
// ================================================================
let ecueCreditReference = null, ecueCreditUnite = 0, ecueCreditsUsed = 0, ecueOwnCredit = 0, ecueCurrentUeId = null;
let ecueActiveTab = 'create', ecueIsEditMode = false, select2Initialized = false, lastLoadedUeId = null;
let ecueMaquette = { parcoursId: null, rappel: 'Toutes les maquettes', nom: null };
let ecueEnchainer = false;

const LU_MEMOIRE_CHOIX = 'lmd.ue.choix_maquette';

/**
 * Reference de credits de la jauge, ou null si elle est inconnue.
 *
 * La jauge se calculait sur le credit de l'unite. Le jour ou le credit devient
 * propre au parcours, ce nombre est celui d'une AUTRE maquette et rien ne le
 * signale. Une jauge fausse est pire que pas de jauge : sans reference, on
 * masque et on le dit.
 */
function referenceCredits() {
    if (ecueCreditReference !== null && ecueCreditReference !== undefined) return ecueCreditReference;
    return LU_REGLAGES.masquer_jauge_sans_reference ? null : ecueCreditUnite;
}

function updateCreditGauge() {
    const gauge = document.getElementById('ecue_credit_gauge');
    const absente = document.getElementById('ecue_credit_absente');
    const submitBtn = document.getElementById('ecue_submit');
    const reference = referenceCredits();

    if (reference === null) {
        gauge.style.display = 'none';
        absente.style.display = 'flex';
        submitBtn.disabled = false; submitBtn.style.opacity = '1';
        return;
    }

    absente.style.display = 'none';

    if (!reference) { gauge.style.display = 'none'; submitBtn.disabled = false; submitBtn.style.opacity = '1'; return; }
    gauge.style.display = 'block';
    const inputVal = parseInt(document.getElementById('ecue_credit').value) || 0;
    const othersUsed = ecueCreditsUsed - ecueOwnCredit;
    const total = othersUsed + inputVal;
    const pct = Math.min(100, Math.round(total / reference * 100));
    const restant = reference - othersUsed;
    document.getElementById('ecue_credit_label').textContent = total + ' / ' + reference;
    const bar = document.getElementById('ecue_credit_bar');
    bar.style.width = pct + '%';
    bar.style.background = total > reference ? '#dc2626' : (pct >= 80 ? '#f59e0b' : '#059669');
    const warning = document.getElementById('ecue_credit_warning');
    if (total > reference) {
        warning.style.display = 'block';
        document.getElementById('ecue_credit_warning_text').textContent = 'Dépassement de ' + (total - reference) + ' crédit(s). Maximum restant : ' + restant;
        submitBtn.disabled = true; submitBtn.style.opacity = '.5';
    } else {
        warning.style.display = 'none'; submitBtn.disabled = false; submitBtn.style.opacity = '1';
    }
}

/**
 * Prepare le choix « ou cet element doit-il figurer ? ».
 *
 * Le defaut est « cette maquette uniquement », coche d'avance, memorise et
 * affiche — pas un blocage repete, qu'on contourne au reflexe au douzieme
 * element. Reserver par erreur se voit ; mettre par erreur dans toutes les
 * maquettes ne se voit pas.
 */
function preparerChoixMaquette(ue) {
    const bloc = document.getElementById('ecue_maquette_bloc');

    if (!ecueMaquette.parcoursId || ecueIsEditMode) {
        bloc.style.display = 'none';
        document.getElementById('ecue_parcours_travail').value = ecueMaquette.parcoursId || '';
        document.getElementById('ecue_reserve').value = '0';
        return;
    }

    bloc.style.display = 'block';
    document.getElementById('ecue_maquette_seule_nom').textContent = ecueMaquette.nom;
    const autres = (ue.parcours || []).map(p => p.code).join(', ');
    document.getElementById('ecue_maquette_toutes_liste').textContent = autres
        ? 'Il apparaîtra dans : ' + autres + '.'
        : 'Il apparaîtra dans chacune d\'elles.';

    let choix = null;
    try { choix = window.localStorage.getItem(LU_MEMOIRE_CHOIX); } catch (e) { /* navigation privee */ }
    if (choix !== 'seule' && choix !== 'toutes') {
        choix = LU_REGLAGES.reserver_par_defaut ? 'seule' : 'toutes';
    }
    document.getElementById('ecue_maquette_seule').checked = choix === 'seule';
    document.getElementById('ecue_maquette_toutes').checked = choix === 'toutes';

    document.getElementById('ecue_maquette_indispo').style.display =
        LU_REGLAGES.partage_disponible ? 'none' : 'flex';

    document.getElementById('ecue_parcours_travail').value = ecueMaquette.parcoursId;
    majChoixMaquette();
}

function majChoixMaquette() {
    const seule = document.getElementById('ecue_maquette_seule');
    const reserve = seule && seule.checked;
    document.getElementById('ecue_reserve').value = reserve ? '1' : '0';
    document.getElementById('ecue_maquette_seule_card').classList.toggle('lu-choix-card--on', !!reserve);
    document.getElementById('ecue_maquette_toutes_card').classList.toggle('lu-choix-card--on', !reserve);
    try { window.localStorage.setItem(LU_MEMOIRE_CHOIX, reserve ? 'seule' : 'toutes'); } catch (e) { /* navigation privee */ }
}

document.addEventListener('change', function (ev) {
    if (ev.target && ev.target.name === 'ecue_maquette_choix') majChoixMaquette();
});

function switchEcueTab(tab) {
    ecueActiveTab = tab;
    document.querySelectorAll('.ecue-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.ecue-tab-content').forEach(c => c.classList.remove('active'));
    if (tab === 'create') {
        document.querySelector('.ecue-tab-btn:first-child').classList.add('active');
        document.getElementById('ecue_tab_create').classList.add('active');
        document.getElementById('ecue_matiere_id').value = '';
        document.getElementById('ecue_submit_text').textContent = 'Créer l\'élément';
    } else {
        document.querySelector('.ecue-tab-btn:last-child').classList.add('active');
        document.getElementById('ecue_tab_link').classList.add('active');
        document.getElementById('ecue_submit_text').textContent = 'Lier l\'élément';
        loadMatieresDisponibles();
    }
}

function formatMatiereResult(data) {
    if (!data.id) return data.text;
    var el = data.element;
    var name = el.dataset.name || data.text;
    var code = el.dataset.code || '';

    var $row = $('<div class="d-flex align-items-center gap-2 py-1"></div>');
    var initial = (name || '?')[0].toUpperCase();
    var $icon = $('<div></div>').text(initial).css({
        width: '34px', height: '34px', borderRadius: '8px',
        background: 'linear-gradient(135deg, #059669, #34d399)',
        color: '#fff', display: 'flex', alignItems: 'center',
        justifyContent: 'center', fontWeight: '700', fontSize: '0.82rem',
        flexShrink: '0'
    });
    var $info = $('<div class="flex-grow-1 min-w-0"></div>');
    $info.append($('<div class="fw-semibold text-truncate" style="font-size:.84rem;line-height:1.3;"></div>').text(name));
    if (code) {
        $info.append($('<span style="font-size:.72rem;color:#64748b;background:#f1f5f9;padding:1px 6px;border-radius:4px;font-weight:500;"></span>').text(code));
    }
    $row.append($icon, $info);
    return $row;
}

function formatMatiereSelection(data) {
    if (!data.id) return data.text;
    var el = data.element;
    var name = el.dataset.name || data.text;
    var code = el.dataset.code || '';

    var $sel = $('<span class="d-inline-flex align-items-center gap-1"></span>');
    $sel.append($('<span class="fw-semibold"></span>').text(name));
    if (code) {
        $sel.append($('<span style="font-size:.75rem;color:#059669;font-weight:600;"></span>').text('(' + code + ')'));
    }
    return $sel;
}

async function loadMatieresDisponibles() {
    if (!ecueCurrentUeId) return;
    if (lastLoadedUeId === ecueCurrentUeId && select2Initialized) return;
    const loading = document.getElementById('ecue_link_loading');
    const sel = document.getElementById('ecue_matiere_select');
    loading.style.display = 'block';
    try {
        const resp = await fetch(`${BASE}/${ecueCurrentUeId}/matieres-disponibles`, { headers: { 'Accept': 'application/json' } });
        const matieres = await resp.json();
        if (select2Initialized && $.fn.select2) { $(sel).select2('destroy'); select2Initialized = false; }
        sel.innerHTML = '<option value="">— Sélectionner une matière —</option>';
        matieres.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = (m.code ? m.code + ' — ' : '') + m.name;
            opt.dataset.name = m.name; opt.dataset.code = m.code || '';
            opt.dataset.coeff = m.coefficient_ecue || ''; opt.dataset.credit = m.credit_ecue || '';
            sel.appendChild(opt);
        });
        if ($.fn.select2) {
            $(sel).select2({
                theme: 'bootstrap-5',
                language: 'fr',
                placeholder: '— Rechercher une matière —',
                allowClear: true,
                dropdownParent: $('#modalECUE .modal-content'),
                width: '100%',
                templateResult: formatMatiereResult,
                templateSelection: formatMatiereSelection
            })
            .off('select2:select select2:clear')
            .on('select2:select', e => onMatiereSelected(e.params.data.element))
            .on('select2:clear', () => onMatiereCleared());
            select2Initialized = true;
        }
        lastLoadedUeId = ecueCurrentUeId;
        loading.style.display = 'none';
    } catch (err) {
        console.error('loadMatieresDisponibles error:', err);
        loading.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erreur';
        sel.style.display = 'block';
    }
}

function onMatiereSelected(option) {
    document.getElementById('ecue_matiere_id').value = option.value;
    const preview = document.getElementById('ecue_matiere_preview');
    document.getElementById('ecue_preview_name').textContent = option.dataset.name || '';
    document.getElementById('ecue_preview_code').textContent = option.dataset.code ? '(' + option.dataset.code + ')' : '';
    preview.style.display = 'block';
    if (option.dataset.coeff && !document.getElementById('ecue_coefficient').value) document.getElementById('ecue_coefficient').value = option.dataset.coeff;
    if (option.dataset.credit && !document.getElementById('ecue_credit').value) { document.getElementById('ecue_credit').value = option.dataset.credit; updateCreditGauge(); }
}

function onMatiereCleared() {
    document.getElementById('ecue_matiere_id').value = '';
    document.getElementById('ecue_matiere_preview').style.display = 'none';
}

function openEcueCreateModal(ue, creditsUsed, maquette) {
    ecueCreditReference = (ue.credit_reference === null || ue.credit_reference === undefined)
        ? null : parseInt(ue.credit_reference);
    ecueCreditUnite = parseInt(ue.credit) || 0;
    ecueCreditsUsed = creditsUsed; ecueOwnCredit = 0; ecueCurrentUeId = ue.id; ecueIsEditMode = false;
    ecueMaquette = maquette || { parcoursId: null, rappel: 'Toutes les maquettes', nom: null };
    document.getElementById('ecue_form').action = `${BASE}/${ue.id}/ecue`;
    document.getElementById('ecue_form').reset();
    document.getElementById('ecue_method').value = 'POST';
    document.getElementById('ecue_matiere_id').value = '';
    document.getElementById('ecue_ue_label').textContent = ue.name;
    document.getElementById('ecue_rappel_maquette').textContent = ecueMaquette.rappel;
    document.getElementById('ecue_modal_title').textContent = 'Ajouter un élément';
    document.getElementById('ecue_error').style.display = 'none';
    document.getElementById('ecue_matiere_preview').style.display = 'none';
    document.getElementById('ecue_tabs').style.display = 'flex';
    document.getElementById('ecue_submit_encore').style.display = 'inline-flex';
    switchEcueTab('create');
    preparerChoixMaquette(ue);
    updateCreditGauge();
    new bootstrap.Modal(document.getElementById('modalECUE')).show();
}

function openEcueEditModalFn(ue, ecue, creditsUsed, maquette) {
    ecueCreditReference = (ue.credit_reference === null || ue.credit_reference === undefined)
        ? null : parseInt(ue.credit_reference);
    ecueCreditUnite = parseInt(ue.credit) || 0;
    ecueCreditsUsed = creditsUsed;
    ecueOwnCredit = ecue.credit_ecue === '—' ? 0 : (parseInt(ecue.credit_ecue) || 0);
    ecueCurrentUeId = ue.id; ecueIsEditMode = true;
    ecueMaquette = maquette || { parcoursId: null, rappel: 'Toutes les maquettes', nom: null };
    document.getElementById('ecue_form').action = `${BASE}/${ue.id}/ecue/${ecue.id}`;
    document.getElementById('ecue_method').value = 'PUT';
    document.getElementById('ecue_matiere_id').value = '';
    document.getElementById('ecue_modal_title').textContent = 'Modifier l\'élément';
    document.getElementById('ecue_ue_label').textContent = ue.name || '—';
    document.getElementById('ecue_rappel_maquette').textContent = ecueMaquette.rappel;
    document.getElementById('ecue_submit_encore').style.display = 'none';
    preparerChoixMaquette(ue);
    document.getElementById('ecue_submit_text').textContent = 'Mettre à jour';
    document.getElementById('ecue_name').value = ecue.name || '';
    document.getElementById('ecue_code').value = ecue.code || '';
    document.getElementById('ecue_coefficient').value = ecue.coefficient_ecue === '—' ? '' : (ecue.coefficient_ecue || '');
    document.getElementById('ecue_credit').value = ecue.credit_ecue === '—' ? '' : (ecue.credit_ecue || '');
    document.getElementById('ecue_ordre').value = ecue.ordre_bulletin || 0;
    document.getElementById('ecue_error').style.display = 'none';
    document.getElementById('ecue_tabs').style.display = 'none';
    document.getElementById('ecue_tab_create').classList.add('active');
    document.getElementById('ecue_tab_link').classList.remove('active');
    ecueActiveTab = 'create';
    updateCreditGauge();
    new bootstrap.Modal(document.getElementById('modalECUE')).show();
}

document.getElementById('ecue_form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('ecue_submit');
    const errBox = document.getElementById('ecue_error');

    // Tab validation
    if (ecueActiveTab === 'link' && !document.getElementById('ecue_matiere_id').value) {
        errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Veuillez sélectionner une matière existante.';
        errBox.style.display = 'block'; return;
    }
    if (ecueActiveTab === 'create' && !ecueIsEditMode) {
        if (!document.getElementById('ecue_name').value.trim() || !document.getElementById('ecue_code').value.trim()) {
            errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Le nom et le code sont requis.';
            errBox.style.display = 'block'; return;
        }
    }

    btn.disabled = true; errBox.style.display = 'none';
    const formData = new FormData(form);
    const body = {};
    formData.forEach((v, k) => { if (v !== '' && k !== '_method') body[k] = v; });
    if (ecueActiveTab === 'link') { delete body.name; delete body.code; }

    const isPut = document.getElementById('ecue_method').value === 'PUT';
    try {
        const url = isPut ? form.action + '?_method=PUT' : form.action;
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await resp.json();
        if (resp.ok && data.success !== false) {
            const mgr = Alpine.$data(document.querySelector('[x-data]'));
            mgr.loadUes(mgr.pagination.current_page);
            mgr.showToast(data.message || 'Élément enregistré');

            if (ecueEnchainer) {
                // On garde l'unité, la maquette de travail et le choix de
                // maquette : seuls les champs de l'élément repartent à vide.
                reinitialiserChampsElement();
            } else {
                bootstrap.Modal.getInstance(document.getElementById('modalECUE')).hide();
            }
        } else {
            const msgs = data.errors ? Object.values(data.errors).flat().map(m => escHtml(m)).join('<br>') : escHtml(data.message || 'Erreur');
            errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + msgs;
            errBox.style.display = 'block';
        }
    } catch (err) {
        errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erreur réseau';
        errBox.style.display = 'block';
    }
    btn.disabled = false;
    ecueEnchainer = false;
    updateCreditGauge();
});

// « Enregistrer et ajouter un autre » : même soumission, la fenêtre reste ouverte.
document.getElementById('ecue_submit_encore').addEventListener('click', function () {
    ecueEnchainer = true;
    const form = document.getElementById('ecue_form');
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
});

function reinitialiserChampsElement() {
    // Les crédits déjà consommés viennent d'augmenter : lu AVANT de vider le
    // champ, sinon la jauge repartirait du total d'avant l'enregistrement.
    ecueCreditsUsed += parseInt(document.getElementById('ecue_credit').value) || 0;
    ecueOwnCredit = 0;

    ['ecue_name', 'ecue_code', 'ecue_coefficient', 'ecue_credit'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('ecue_ordre').value = 0;
    document.getElementById('ecue_matiere_id').value = '';
    document.getElementById('ecue_matiere_preview').style.display = 'none';
    document.getElementById('ecue_error').style.display = 'none';

    switchEcueTab('create');
    updateCreditGauge();
    document.getElementById('ecue_name').focus();
}

// ── UE virtuelle UEMOA : auto-fill Intitulé depuis Type quand Code est vide ──
(function () {
    const codeEl = document.getElementById('ue_code');
    const nameEl = document.getElementById('ue_name');
    const typeEl = document.getElementById('ue_type_ue');
    if (!codeEl || !nameEl || !typeEl) return;

    typeEl.addEventListener('change', () => {
        if (!codeEl.value.trim() && !nameEl.value.trim() && typeEl.value) {
            nameEl.value = typeEl.options[typeEl.selectedIndex]?.text || '';
        }
    });
})();
</script>
@endpush
