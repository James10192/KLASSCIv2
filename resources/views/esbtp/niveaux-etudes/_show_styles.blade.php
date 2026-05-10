{{-- Styles for niveaux-etudes show page — extracted for no-god-code compliance. --}}
@once
@push('styles')
<style>
.ne-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.75rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.ne-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.ne-hero-left { display:flex; align-items:center; gap:1rem; }
.ne-hero-avatar { width:56px; height:56px; border-radius:14px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:1.1rem; font-weight:700; flex-shrink:0; color:#fff; backdrop-filter: blur(6px); }
.ne-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0 0 .25rem; }
.ne-hero p { color:rgba(255,255,255,.78); font-size:.88rem; margin:0; }
.ne-hero-chips { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.55rem; }
.ne-hero-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .65rem; border-radius:20px; font-size:.74rem; font-weight:600; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.2); color:#fff; }
.ne-hero-chip.ko { background:rgba(254,202,202,.2); border-color:rgba(254,202,202,.4); color:#fee2e2; }
.ne-hero-chip.warn { background:rgba(254,215,170,.22); border-color:rgba(254,215,170,.45); color:#ffedd5; }
.ne-hero-actions { display:flex; gap:.55rem; flex-wrap:wrap; }
.ne-btn-white { background:#fff; color:#0453cb; border:none; border-radius:10px; padding:.55rem 1.05rem; font-size:.84rem; font-weight:600; display:inline-flex; align-items:center; gap:.4rem; text-decoration:none; transition:all .2s; box-shadow:0 2px 8px rgba(0,0,0,.12); }
.ne-btn-white:hover { background:#eff6ff; color:#0453cb; transform:translateY(-1px); }
.ne-btn-glass { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.22); border-radius:10px; padding:.55rem 1.05rem; font-size:.84rem; font-weight:600; display:inline-flex; align-items:center; gap:.4rem; text-decoration:none; transition:all .2s; }
.ne-btn-glass:hover { background:rgba(255,255,255,.22); color:#fff; }
.ne-kpis { display:flex; gap:.75rem; margin-top:1.5rem; flex-wrap:wrap; }
.ne-kpi { flex:1; min-width:130px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.9rem 1rem; display:flex; align-items:center; gap:.75rem; }
.ne-kpi-icon { width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.9rem; color:#fff; }
.ne-kpi-value { font-size:1.35rem; font-weight:700; color:#fff; line-height:1.1; }
.ne-kpi-label { font-size:.72rem; color:rgba(255,255,255,.65); margin-top:.15rem; }
.ne-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }
@@media (max-width: 992px) {
    .ne-grid { grid-template-columns:1fr; }
    .ne-hero { padding:1.5rem 1.25rem 1.25rem; }
    .ne-hero h1 { font-size:1.2rem; }
}
.ne-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); overflow:hidden; }
.ne-card-head { padding:1rem 1.25rem .85rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.65rem; }
.ne-card-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #0453cb, #3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.88rem; flex-shrink:0; }
.ne-card-head h5 { margin:0; font-size:.95rem; font-weight:700; color:#1e293b; }
.ne-card-head p { margin:.05rem 0 0; font-size:.76rem; color:#64748b; }
.ne-info-row { display:flex; padding:.65rem 1.25rem; border-bottom:1px solid #f8fafc; align-items:center; gap:1rem; }
.ne-info-row:last-child { border-bottom:none; }
.ne-info-label { width:42%; font-size:.78rem; color:#64748b; font-weight:500; display:flex; align-items:center; gap:.4rem; }
.ne-info-label i { color:#94a3b8; font-size:.74rem; width:14px; }
.ne-info-value { flex:1; font-size:.86rem; color:#1e293b; font-weight:600; }
.ne-info-value.muted { color:#94a3b8; font-weight:400; font-style:italic; }
.ne-status-on { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .6rem; border-radius:20px; font-size:.72rem; font-weight:600; background:#d1fae5; color:#065f46; }
.ne-status-off { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .6rem; border-radius:20px; font-size:.72rem; font-weight:600; background:#fee2e2; color:#991b1b; }
.ne-classes-list { padding:.5rem 0; }
.ne-class-row { display:flex; align-items:center; gap:.75rem; padding:.65rem 1.25rem; border-bottom:1px solid #f8fafc; transition:background .15s; }
.ne-class-row:last-child { border-bottom:none; }
.ne-class-row:hover { background:#f8fafc; }
.ne-class-avatar { width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg, #0453cb, #3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:700; flex-shrink:0; }
.ne-class-info { flex:1; min-width:0; }
.ne-class-name { font-size:.85rem; font-weight:600; color:#1e293b; }
.ne-class-meta { font-size:.74rem; color:#64748b; margin-top:.1rem; }
.ne-class-places { font-size:.74rem; color:#64748b; white-space:nowrap; }
.ne-class-places strong { color:#0453cb; font-size:.85rem; }
.ne-empty-soft { padding:2rem 1.25rem; text-align:center; color:#94a3b8; font-size:.85rem; }
.ne-empty-soft i { font-size:1.85rem; color:#cbd5e1; display:block; margin-bottom:.5rem; }
.ne-card-footer-link { padding:.7rem 1.25rem; background:#f8fafc; border-top:1px solid #f1f5f9; text-align:center; font-size:.8rem; color:#0453cb; font-weight:600; text-decoration:none; display:block; transition:background .15s; }
.ne-card-footer-link:hover { background:#eff6ff; color:#0453cb; }
.ne-desc-body { padding:1.1rem 1.25rem; font-size:.88rem; color:#475569; line-height:1.55; }
.ne-bottom-actions { display:flex; justify-content:space-between; gap:.6rem; padding:1rem 0 0; flex-wrap:wrap; }
.ne-bottom-right { display:flex; gap:.6rem; flex-wrap:wrap; }
</style>
@endpush
@endonce
