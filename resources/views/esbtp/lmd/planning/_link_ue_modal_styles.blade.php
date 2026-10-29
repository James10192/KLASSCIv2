{{-- Styles for the "Lier des UE" premium modal — extracted for no-god-code. --}}
@once
@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .lpm-overlay {
        position: fixed; inset: 0; z-index: 1080;
        background: rgba(10, 15, 30, .72);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        display: flex; align-items: flex-start; justify-content: center;
        padding: 2rem 1rem;
        overflow-y: auto;
    }
    .lpm-card {
        background: #fff; border-radius: 18px; width: 100%; max-width: 860px;
        box-shadow: 0 32px 80px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.06);
        overflow: hidden; display: flex; flex-direction: column; max-height: calc(100vh - 4rem);
    }
    .lpm-header {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);
        padding: 1.5rem 2rem 1.25rem; color: #fff;
        display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    }
    .lpm-header-left { display: flex; align-items: center; gap: 1rem; }
    .lpm-header-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .lpm-header h2 { font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff; }
    .lpm-header p { font-size: .82rem; color: rgba(255,255,255,.72); margin: .15rem 0 0; }
    .lpm-close {
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
        color: #fff; width: 36px; height: 36px; border-radius: 10px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: .95rem;
        transition: all .15s ease;
    }
    .lpm-close:hover { background: rgba(255,255,255,.22); }

    .lpm-toolbar {
        padding: .85rem 2rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        flex-wrap: wrap;
    }
    .lpm-search {
        flex: 1 1 280px; min-width: 200px;
        padding: .55rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
        font-size: .88rem; color: #1e293b;
        transition: border-color .15s, box-shadow .15s;
    }
    .lpm-search:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .lpm-counter { font-size: .78rem; color: #64748b; font-weight: 600; white-space: nowrap; }
    .lpm-counter strong { color: #0453cb; font-weight: 700; }

    .lpm-body { flex: 1 1 auto; overflow-y: auto; padding: 0; max-height: 80vh; }
    @@media (max-width: 768px) { .lpm-body { max-height: 90vh; } }
    .lpm-table { width: 100%; border-collapse: collapse; }
    .lpm-table th {
        text-align: left; font-size: .68rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .04em;
        padding: .75rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        position: sticky; top: 0; z-index: 1;
    }
    .lpm-table th.lpm-th-num { text-align: center; width: 100px; }
    .lpm-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s ease; }
    .lpm-table tbody tr:hover { background: #f8fafc; }
    .lpm-table tbody tr.lpm-row-selected { background: rgba(4,83,203,.04); }
    .lpm-table td { padding: .65rem 1rem; vertical-align: middle; font-size: .87rem; color: #1e293b; }

    .lpm-cell-check { width: 36px; }
    .lpm-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #0453cb; }
    .lpm-ue-code {
        font-family: 'SF Mono', Consolas, monospace; font-size: .76rem;
        background: rgba(4,83,203,.08); color: #0453cb;
        padding: .15rem .5rem; border-radius: 6px; margin-right: .5rem; font-weight: 600;
    }
    .lpm-ue-code-virtual { background: rgba(100,116,139,.1); color: #64748b; font-style: italic; font-family: inherit; }
    .lpm-cell-sem select, .lpm-cell-opt { cursor: pointer; }
    .lpm-mini-select {
        padding: .35rem .55rem; border: 1px solid #e2e8f0; border-radius: 8px;
        font-size: .82rem; background: #fff; color: #1e293b; font-family: inherit;
    }
    .lpm-mini-select:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 2px rgba(4,83,203,.12); }
    .lpm-mini-select:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }

    .lpm-empty { padding: 3rem 2rem; text-align: center; color: #64748b; font-size: .9rem; }
    .lpm-empty-icon { font-size: 2rem; color: #cbd5e1; margin-bottom: .75rem; }
    .lpm-loading { padding: 3rem; text-align: center; color: #64748b; font-size: .88rem; }
    .lpm-spin {
        display: inline-block; width: 18px; height: 18px;
        border: 2px solid #e2e8f0; border-top-color: #0453cb;
        border-radius: 50%; animation: lpm-spin .7s linear infinite;
        margin-right: .5rem; vertical-align: -3px;
    }
    @@keyframes lpm-spin { to { transform: rotate(360deg); } }

    .lpm-footer {
        padding: 1rem 2rem; border-top: 1px solid #e2e8f0; background: #f8fafc;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        flex-wrap: wrap;
    }
    .lpm-feedback { font-size: .82rem; color: #64748b; }
    .lpm-feedback.lpm-feedback-error { color: #b91c1c; font-weight: 600; }
    .lpm-actions { display: flex; gap: .65rem; }
    .lpm-btn {
        padding: .55rem 1.15rem; border-radius: 10px; font-size: .85rem; font-weight: 600;
        border: 1px solid transparent; cursor: pointer; transition: all .15s ease;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .lpm-btn-secondary { background: #fff; color: #475569; border-color: #e2e8f0; }
    .lpm-btn-secondary:hover { background: #f1f5f9; }
    .lpm-btn-primary { background: #0453cb; color: #fff; }
    .lpm-btn-primary:hover { background: #033a8e; }
    .lpm-btn:disabled { opacity: .55; cursor: not-allowed; }

    @@media (max-width: 640px) {
        .lpm-overlay { padding: 0; }
        .lpm-card { border-radius: 0; max-height: 100vh; height: 100vh; }
        .lpm-header, .lpm-toolbar, .lpm-footer { padding-left: 1rem; padding-right: 1rem; }
        .lpm-table th, .lpm-table td { padding: .55rem .65rem; font-size: .8rem; }
    }
</style>
@endpush
@endonce
