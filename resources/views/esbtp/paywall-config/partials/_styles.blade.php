{{-- Feuille de style des pages du paywall (namespace pwc-*). Incluse par
     index, blocked et upgrade, dans leur bloc de styles. --}}
<style>
.pwc { --pwc-primary:#0453cb; --pwc-primary-d:#033a8e; --pwc-accent:#3b7ddb; --pwc-dark:#0f172a; --pwc-text:#1e293b; --pwc-muted:#64748b; --pwc-border:#e2e8f0; --pwc-surface:#f8fafc; --pwc-success:#10b981; --pwc-warning:#b45309; --pwc-danger:#dc2626; color:var(--pwc-text); max-width:1320px; margin:0 auto; padding:clamp(1rem,2.5vw,1.5rem) clamp(.75rem,2.5vw,1.5rem) 2rem; overflow-x:clip; }
.pwc *, .pwc *::before, .pwc *::after { box-sizing:border-box; }
.pwc-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:clamp(1.25rem,3vw,2rem) clamp(1rem,3vw,2.5rem) clamp(1.1rem,2.5vw,1.5rem); color:#fff; margin-bottom:1.25rem; box-shadow:0 8px 30px rgba(4,83,203,.18); }
.pwc-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.pwc-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; flex:1 1 auto; }
.pwc-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; color:#fff; }
.pwc-hero h1 { font-size:clamp(1.15rem,2.6vw,1.45rem); font-weight:700; color:#fff; margin:0; line-height:1.25; }
.pwc-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:.2rem 0 0; line-height:1.4; }
.pwc-hero-actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
.pwc-source { display:inline-flex; align-items:center; gap:.4rem; font-size:.74rem; font-weight:600; padding:.32rem .7rem; border-radius:999px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); color:#fff; white-space:nowrap; }
.pwc-source-dot { width:8px; height:8px; border-radius:50%; background:#fff; flex-shrink:0; }
.pwc-source--ok .pwc-source-dot { background:#6ee7b7; }
.pwc-source--ko .pwc-source-dot { background:#fca5a5; }
.pwc-btn { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; border-radius:10px; padding:.55rem 1rem; font-size:.82rem; font-weight:600; border:1px solid transparent; cursor:pointer; text-decoration:none; transition:all .2s ease; white-space:nowrap; line-height:1.2; }
.pwc-btn:disabled { opacity:.6; cursor:wait; }
.pwc-btn--glass { background:rgba(255,255,255,.15); color:#fff; border-color:rgba(255,255,255,.22); }
.pwc-btn--glass:hover { background:rgba(255,255,255,.24); color:#fff; }
.pwc-btn--white { background:#fff; color:var(--pwc-primary); }
.pwc-btn--white:hover { background:#eef4fd; color:var(--pwc-primary-d); }
.pwc-btn--primary { background:var(--pwc-primary); color:#fff; }
.pwc-btn--primary:hover { background:var(--pwc-primary-d); color:#fff; }
.pwc-btn--ghost { background:#fff; color:var(--pwc-primary); border-color:#c7d4e5; }
.pwc-btn--ghost:hover { background:#eef4fd; color:var(--pwc-primary-d); }
.pwc-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr)); gap:.75rem; margin-top:1.4rem; }
.pwc-kpi { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.85rem 1rem; min-width:0; }
.pwc-kpi-label { font-size:.7rem; letter-spacing:.04em; text-transform:uppercase; color:rgba(255,255,255,.7); font-weight:600; }
.pwc-kpi-value { font-size:clamp(1.05rem,2.4vw,1.35rem); font-weight:700; color:#fff; margin-top:.25rem; line-height:1.25; }
.pwc-nowrap { white-space:nowrap; }
.pwc-kpi-sub { font-size:.74rem; color:rgba(255,255,255,.72); margin-top:.15rem; line-height:1.35; }
.pwc-alert { display:flex; gap:.75rem; align-items:flex-start; border-radius:12px; padding:.9rem 1rem; margin-bottom:1rem; border:1px solid var(--pwc-border); background:#fff; font-size:.86rem; line-height:1.45; }
.pwc-alert i { margin-top:.15rem; flex-shrink:0; }
.pwc-alert ul { margin:.35rem 0 0; padding-left:1.1rem; }
.pwc-alert strong { color:var(--pwc-dark); }
.pwc-alert--info { border-color:rgba(4,83,203,.22); background:rgba(4,83,203,.04); }
.pwc-alert--info i { color:var(--pwc-primary); }
.pwc-alert--warning { border-color:rgba(245,158,11,.35); background:rgba(245,158,11,.06); }
.pwc-alert--warning i { color:var(--pwc-warning); }
.pwc-alert--danger { border-color:rgba(220,38,38,.3); background:rgba(220,38,38,.05); }
.pwc-alert--danger i { color:var(--pwc-danger); }
.pwc-alert--success { border-color:rgba(16,185,129,.3); background:rgba(16,185,129,.06); }
.pwc-alert--success i { color:var(--pwc-success); }
.pwc-section-title { display:flex; align-items:center; gap:.65rem; margin:1.5rem 0 .85rem; }
.pwc-section-title h2 { font-size:1.02rem; font-weight:700; color:var(--pwc-dark); margin:0; }
.pwc-section-title span { font-size:.78rem; color:var(--pwc-muted); }
.pwc-section-icon { width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
.pwc-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr)); gap:.9rem; }
.pwc-card { background:#fff; border:1px solid var(--pwc-border); border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04),0 1px 2px rgba(15,23,42,.06); padding:1.05rem 1.1rem; min-width:0; transition:box-shadow .2s ease, border-color .2s ease; }
.pwc-card:hover { box-shadow:0 8px 26px rgba(4,83,203,.08),0 2px 6px rgba(15,23,42,.04); border-color:#c7d4e5; }
.pwc-usage-head { display:flex; align-items:center; gap:.6rem; }
.pwc-usage-icon { width:36px; height:36px; border-radius:10px; background:rgba(4,83,203,.08); color:var(--pwc-primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pwc-usage-label { font-size:.82rem; font-weight:600; color:var(--pwc-muted); line-height:1.3; }
.pwc-usage-value { font-size:clamp(1.15rem,2.2vw,1.45rem); font-weight:700; color:var(--pwc-dark); margin-top:.6rem; white-space:nowrap; }
.pwc-usage-value small { font-size:.78rem; font-weight:600; color:var(--pwc-muted); }
.pwc-usage-value--muted { color:var(--pwc-muted); font-size:1rem; }
.pwc-gauge { height:8px; border-radius:999px; background:#eef2f7; margin-top:.65rem; overflow:hidden; }
.pwc-gauge-bar { height:100%; border-radius:999px; background:linear-gradient(90deg,#0453cb,#3b7ddb); }
.pwc-gauge-bar--warning { background:#f59e0b; }
.pwc-gauge-bar--danger { background:var(--pwc-danger); }
.pwc-usage-foot { font-size:.75rem; color:var(--pwc-muted); margin-top:.45rem; line-height:1.35; }
.pwc-usage-foot--warning { color:var(--pwc-warning); font-weight:600; }
.pwc-usage-foot--danger { color:var(--pwc-danger); font-weight:600; }
.pwc-chips { display:flex; flex-wrap:wrap; gap:.45rem; }
.pwc-chip { display:inline-flex; align-items:center; gap:.35rem; font-size:.76rem; font-weight:600; padding:.3rem .65rem; border-radius:8px; background:rgba(220,38,38,.07); color:var(--pwc-danger); border:1px solid rgba(220,38,38,.2); }
.pwc-meta { display:flex; flex-wrap:wrap; gap:.35rem 1.25rem; font-size:.78rem; color:var(--pwc-muted); margin-top:1rem; }
.pwc-meta i { color:var(--pwc-primary); margin-right:.25rem; }
.pwc-row { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; }
.pwc-row-text { flex:1 1 280px; min-width:0; }
.pwc-row-text h3 { font-size:.95rem; font-weight:700; color:var(--pwc-dark); margin:0 0 .2rem; }
.pwc-row-text p { font-size:.82rem; color:var(--pwc-muted); margin:0; line-height:1.45; }
.pwc-switch { position:relative; display:inline-flex; align-items:center; gap:.6rem; cursor:pointer; font-size:.85rem; font-weight:600; color:var(--pwc-dark); white-space:nowrap; }
.pwc-switch input { position:absolute; opacity:0; width:1px; height:1px; }
.pwc-switch-track { width:44px; height:24px; border-radius:999px; background:#cbd5e1; position:relative; transition:background .2s ease; flex-shrink:0; }
.pwc-switch-track::after { content:''; position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.2); transition:transform .2s ease; }
.pwc-switch input:checked + .pwc-switch-track { background:var(--pwc-primary); }
.pwc-switch input:checked + .pwc-switch-track::after { transform:translateX(20px); }
.pwc-switch input:focus-visible + .pwc-switch-track { outline:2px solid var(--pwc-accent); outline-offset:2px; }
.pwc-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:.9rem 1rem; margin-top:1rem; }
.pwc-field { display:flex; flex-direction:column; gap:.3rem; min-width:0; }
.pwc-field label { font-size:.78rem; font-weight:600; color:var(--pwc-dark); }
.pwc-field input { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:.55rem .75rem; font-size:.88rem; color:var(--pwc-text); background:#fff; }
.pwc-field input:focus { outline:none; border-color:var(--pwc-primary); box-shadow:0 0 0 3px rgba(4,83,203,.12); }
.pwc-field small { font-size:.74rem; color:var(--pwc-muted); line-height:1.35; }
.pwc-actions { display:flex; flex-wrap:wrap; gap:.55rem; margin-top:1.1rem; }
.pwc-code { margin-top:1rem; border:1px dashed rgba(4,83,203,.35); background:rgba(4,83,203,.04); border-radius:12px; padding:1rem; }
.pwc-code-line { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; margin-bottom:.55rem; font-size:.84rem; }
.pwc-code-line strong { color:var(--pwc-dark); }
.pwc-mono { font-family:'Courier New',ui-monospace,monospace; font-size:.85rem; background:#fff; border:1px solid var(--pwc-border); border-radius:7px; padding:.25rem .5rem; overflow-wrap:anywhere; word-break:break-all; min-width:0; max-width:100%; color:var(--pwc-primary-d); }
.pwc-list { list-style:none; margin:.75rem 0 0; padding:0; display:flex; flex-direction:column; gap:.5rem; }
.pwc-list li { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.4rem .75rem; padding:.6rem .75rem; border:1px solid var(--pwc-border); border-radius:10px; font-size:.8rem; background:var(--pwc-surface); }
.pwc-list li span:last-child { color:var(--pwc-muted); white-space:nowrap; }
.pwc-empty { font-size:.82rem; color:var(--pwc-muted); margin:.6rem 0 0; }
.pwc-modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; padding:1rem; z-index:1060; }
.pwc-modal { background:#fff; border-radius:16px; width:100%; max-width:440px; padding:1.25rem; box-shadow:0 20px 50px rgba(15,23,42,.25); }
.pwc-modal h3 { font-size:1rem; font-weight:700; margin:0 0 .75rem; color:var(--pwc-dark); }
[x-cloak] { display:none !important; }
@media (max-width:576px) {
    .pwc-hero-actions { width:100%; }
    .pwc-hero-actions .pwc-btn { flex:1 1 100%; }
    .pwc-source { flex:1 1 100%; justify-content:center; }
    .pwc-actions .pwc-btn { flex:1 1 100%; }
    .pwc-switch { white-space:normal; }
}
</style>
