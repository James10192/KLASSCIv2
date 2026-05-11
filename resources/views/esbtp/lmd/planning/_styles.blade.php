{{-- Styles LMD Planning — extracted from index for no-god-code compliance.
     Includes column tinting (formerly in _listing partial) so AJAX-injected
     listings keep their colors without re-pushing styles. --}}
@once
@push('styles')
<style>
    .lp-page { padding: 1rem 0; }
    .lp-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
    .lp-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .lp-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lp-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
    .lp-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .lp-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .lp-loading .lp-kpis, .lp-loading .lp-content-area { opacity: .55; pointer-events: none; }
    .lp-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; transition: opacity .2s ease; }
    .lp-kpi { flex: 1; min-width: 140px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; }
    .lp-kpi-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem; }
    .lp-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
    .lp-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
    .lp-filters { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(15,23,42,.04); position: relative; }
    .lp-filters-row { display: flex; gap: .75rem; flex-wrap: wrap; align-items: flex-end; }
    .lp-filter-group { flex: 1 1 220px; min-width: 200px; display: flex; flex-direction: column; }
    .lp-filter-label { display: block; font-size: .68rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .35rem; }
    .lp-filter-group .au-select, .lp-filter-group .au-select-trigger { width: 100%; }
    .lp-spinner { position: absolute; top: 1rem; right: 1.25rem; width: 18px; height: 18px; border: 2px solid #e2e8f0; border-top-color: #0453cb; border-radius: 50%; animation: lp-spin .7s linear infinite; }
    @@keyframes lp-spin { to { transform: rotate(360deg); } }
    .lp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); overflow: hidden; }
    .lp-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .lp-card-title { display: flex; align-items: center; gap: .75rem; font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0; }
    .lp-card-title-icon { width: 32px; height: 32px; border-radius: 9px; background: linear-gradient(135deg, #0453cb, #3b7ddb); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .85rem; }
    .lp-card-meta { font-size: .8rem; color: #64748b; }
    .lp-table { width: 100%; border-collapse: collapse; }
    .lp-table th { padding: .65rem 1rem; text-align: left; font-size: .68rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .lp-table th.lp-th-num { text-align: right; }
    .lp-ue-row { background: #f8fafc; cursor: pointer; }
    .lp-ue-row:hover { background: #f1f5f9; }
    .lp-ue-row td { padding: .85rem 1rem; font-weight: 600; color: #0f172a; border-bottom: 1px solid #e2e8f0; }
    .lp-ue-caret { display: inline-block; width: 1rem; text-align: center; color: #0453cb; transition: transform .2s ease; }
    .lp-ue-caret-open { transform: rotate(90deg); }
    .lp-ue-code { font-family: 'SF Mono', Consolas, monospace; font-size: .78rem; background: rgba(4,83,203,.08); color: #0453cb; padding: .15rem .5rem; border-radius: 6px; margin-right: .5rem; }
    .lp-ue-code-virtual { background: rgba(100,116,139,.1); color: #64748b; font-style: italic; font-family: inherit; }
    .lp-ecue-row td { padding: .65rem 1rem; font-size: .87rem; color: #334155; border-bottom: 1px solid #f1f5f9; background: #fff; }
    .lp-ecue-indent { padding-left: 2.5rem !important; }
    .lp-ecue-code { font-family: 'SF Mono', Consolas, monospace; font-size: .76rem; color: #64748b; margin-right: .5rem; }
    .lp-volume { font-variant-numeric: tabular-nums; text-align: right; color: #1e293b; font-weight: 500; }
    .lp-volume-zero { color: #cbd5e1; }
    .lp-volume-total { font-weight: 700; color: #0453cb; }
    .lp-no-planif { font-size: .72rem; color: #94a3b8; font-style: italic; }
    .lp-type-chip { display: inline-block; padding: .15rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.15); }
    .lp-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 3rem 2rem; text-align: center; }
    .lp-empty-icon { width: 64px; height: 64px; border-radius: 16px; background: rgba(4,83,203,.08); color: #0453cb; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
    .lp-empty h3 { font-size: 1.15rem; font-weight: 600; color: #1e293b; margin: 0 0 .5rem; }
    .lp-empty p { color: #64748b; font-size: .9rem; margin: 0 0 1.25rem; max-width: 480px; margin-left: auto; margin-right: auto; }
    .lp-empty-cta { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem 1.1rem; background: #0453cb; color: #fff; font-size: .85rem; font-weight: 600; border-radius: 10px; text-decoration: none; border: none; cursor: pointer; }
    .lp-empty-cta:hover { background: #033a8e; color: #fff; }
    .lp-empty-cta-sm { padding: .4rem .85rem; font-size: .8rem; }
    .lp-card-actions { display: flex; align-items: center; gap: 1rem; }
    .lp-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .lp-btn-glass { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem .95rem; border-radius: 10px; background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); font-size: .82rem; font-weight: 600; cursor: pointer; transition: background .15s, border-color .15s; }
    .lp-btn-glass:hover { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.32); }

    /* Edit hint banner — shown when user has edit perm but missing niveau/semestre filters */
    .lp-edit-hint { display: flex; align-items: center; gap: .65rem; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.18); padding: .75rem 1.1rem; border-radius: 10px; color: #1e293b; font-size: .87rem; margin-bottom: 1rem; }
    .lp-edit-hint i { color: #0453cb; font-size: 1.1rem; flex-shrink: 0; }
    .lp-edit-hint-text { line-height: 1.4; }
    .lp-edit-hint-text strong { color: #0453cb; font-weight: 700; }

    /* Subtle column tinting (loaded once in head — survives AJAX innerHTML replace) */
    .lp-table .lp-col-cm     { background: rgba(4, 83, 203, .04); }
    .lp-table .lp-col-td     { background: rgba(59, 125, 219, .05); }
    .lp-table .lp-col-tp     { background: rgba(94, 145, 222, .05); }
    .lp-table .lp-col-projet { background: rgba(99, 102, 241, .04); }
    .lp-table .lp-col-tpe    { background: rgba(244, 114, 182, .04); }
    .lp-table .lp-col-total  { background: rgba(16, 185, 129, .06); }
    .lp-table .lp-col-cect   { background: rgba(245, 158, 11, .06); }
    .lp-table thead .lp-col-cm     { background: rgba(4, 83, 203, .07); }
    .lp-table thead .lp-col-td     { background: rgba(59, 125, 219, .08); }
    .lp-table thead .lp-col-tp     { background: rgba(94, 145, 222, .08); }
    .lp-table thead .lp-col-projet { background: rgba(99, 102, 241, .07); }
    .lp-table thead .lp-col-tpe    { background: rgba(244, 114, 182, .07); }
    .lp-table thead .lp-col-total  { background: rgba(16, 185, 129, .09); }
    .lp-table thead .lp-col-cect   { background: rgba(245, 158, 11, .09); }
    .lp-table .lp-th-tip { cursor: help; border-bottom: 1px dotted #cbd5e1; }

    /* Help modal */
    .lp-help-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 1rem; opacity: 0; pointer-events: none; transition: opacity .18s ease; }
    .lp-help-backdrop.lp-help-open { opacity: 1; pointer-events: auto; }
    .lp-help-modal { background: #fff; border-radius: 16px; max-width: 720px; width: 100%; max-height: calc(100vh - 4rem); overflow: hidden; box-shadow: 0 20px 60px rgba(15,23,42,.25); display: flex; flex-direction: column; }
    .lp-help-header { padding: 1.1rem 1.5rem; background: linear-gradient(135deg, #0a3d8f, #0453cb 60%, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .lp-help-header h3 { margin: 0; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: .6rem; }
    .lp-help-close { background: rgba(255,255,255,.15); border: none; color: #fff; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .lp-help-close:hover { background: rgba(255,255,255,.25); }
    .lp-help-body { padding: 1.25rem 1.5rem; overflow-y: auto; color: #1e293b; font-size: .9rem; line-height: 1.55; }
    .lp-help-body h4 { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #0453cb; margin: 1.25rem 0 .5rem; font-weight: 700; }
    .lp-help-body h4:first-child { margin-top: 0; }
    .lp-help-glossary { display: grid; grid-template-columns: max-content 1fr; gap: .35rem .9rem; margin: 0; }
    .lp-help-glossary dt { font-family: 'SF Mono', Consolas, monospace; font-size: .78rem; font-weight: 700; color: #0453cb; background: rgba(4,83,203,.08); padding: .12rem .5rem; border-radius: 6px; align-self: start; }
    .lp-help-glossary dd { margin: 0; color: #334155; font-size: .87rem; }
    .lp-help-body code { background: #f1f5f9; padding: .12rem .4rem; border-radius: 5px; font-size: .82rem; color: #0f172a; }
    .lp-help-body ul, .lp-help-body ol { padding-left: 1.25rem; margin: .35rem 0 .75rem; }
    .lp-help-body li { margin-bottom: .35rem; }
    .lp-help-conditions { list-style: none; padding-left: 0; counter-reset: cond; }
    .lp-help-conditions li { position: relative; padding: .55rem .75rem .55rem 2.5rem; margin-bottom: .5rem; background: rgba(4,83,203,.04); border: 1px solid rgba(4,83,203,.12); border-radius: 8px; counter-increment: cond; }
    .lp-help-conditions li::before { content: counter(cond); position: absolute; left: .65rem; top: 50%; transform: translateY(-50%); width: 22px; height: 22px; border-radius: 50%; background: #0453cb; color: #fff; font-size: .72rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
    .lp-help-conditions li i { color: #0453cb; margin-right: .35rem; }
    .lp-help-tip { background: rgba(4,83,203,.04); border-left: 3px solid #0453cb; padding: .55rem .85rem; border-radius: 6px; font-size: .85rem; color: #1e293b; margin-top: .5rem; }
    .lp-help-tip i { color: #0453cb; margin-right: .25rem; }

    /* Tour overlay */
    .lp-tour-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 1990; pointer-events: none; opacity: 0; transition: opacity .2s ease; }
    .lp-tour-overlay.lp-tour-open { opacity: 1; pointer-events: auto; }
    .lp-tour-highlight { position: relative; z-index: 1995 !important; box-shadow: 0 0 0 4px rgba(4,83,203,.55), 0 0 0 8px rgba(255,255,255,.4), 0 12px 38px rgba(15,23,42,.4) !important; border-radius: 14px; transition: box-shadow .2s ease; }
    .lp-tour-card { position: absolute; z-index: 2000; background: #fff; border-radius: 14px; box-shadow: 0 20px 60px rgba(15,23,42,.3); padding: 1rem 1.1rem; max-width: 320px; min-width: 260px; }
    .lp-tour-card-progress { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; }
    .lp-tour-card h4 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: .35rem 0 .35rem; display: flex; align-items: center; gap: .5rem; }
    .lp-tour-card h4 i { color: #0453cb; }
    .lp-tour-card p { font-size: .85rem; color: #475569; margin: 0 0 .85rem; line-height: 1.5; }
    .lp-tour-card-nav { display: flex; gap: .4rem; align-items: center; justify-content: flex-end; flex-wrap: wrap; }
    .lp-tour-btn { padding: .4rem .85rem; border-radius: 8px; font-size: .8rem; font-weight: 600; cursor: pointer; border: none; }
    .lp-tour-btn-ghost { background: transparent; color: #64748b; }
    .lp-tour-btn-ghost:hover { color: #0f172a; background: #f1f5f9; }
    .lp-tour-btn-secondary { background: #f1f5f9; color: #1e293b; }
    .lp-tour-btn-secondary:hover { background: #e2e8f0; }
    .lp-tour-btn-primary { background: #0453cb; color: #fff; }
    .lp-tour-btn-primary:hover { background: #033a8e; }
    @@media (max-width: 768px) {
        .lp-tour-card { left: 1rem !important; right: 1rem !important; bottom: 1rem !important; top: auto !important; max-width: calc(100% - 2rem); }
    }
</style>
@endpush
@endonce
