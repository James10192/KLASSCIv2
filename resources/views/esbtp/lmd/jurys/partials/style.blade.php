<style>
[x-cloak]{display:none !important;}
.juy-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;padding:1.75rem 2.25rem;color:#fff;margin-bottom:1.25rem;}
.juy-hero h1{margin:0;font-size:1.4rem;}
.juy-hero p{margin:.3rem 0 0;color:rgba(255,255,255,.75);font-size:.85rem;}
.juy-meta{display:flex;gap:1.25rem;flex-wrap:wrap;margin-top:1rem;}
.juy-meta-item{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:.5rem .85rem;}
.juy-meta-label{font-size:.6rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.5px;}
.juy-meta-value{font-size:.9rem;font-weight:700;color:#fff;margin-top:.15rem;}
.juy-tabs{display:flex;gap:.4rem;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.4rem;margin-bottom:1.25rem;flex-wrap:wrap;}
.juy-tab{padding:.55rem 1rem;border-radius:9px;font-size:.85rem;font-weight:600;background:transparent;border:none;cursor:pointer;color:#475569;transition:.15s;display:inline-flex;align-items:center;gap:.4rem;}
.juy-tab:hover{background:rgba(4,83,203,.06);color:#0453cb;}
.juy-tab--active{background:#0453cb;color:#fff;}
.juy-tab--active:hover{background:#033a8e;color:#fff;}
.juy-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;margin-bottom:1rem;}
.juy-card h2{margin:0 0 1rem;font-size:1rem;color:#1e293b;display:flex;align-items:center;gap:.5rem;}
.juy-card h2 i{color:#0453cb;}
.juy-quorum-ok{padding:.6rem 1rem;border-radius:10px;background:rgba(16,185,129,.10);color:#047857;font-size:.85rem;display:flex;align-items:center;gap:.5rem;font-weight:600;}
.juy-quorum-ko{padding:.6rem 1rem;border-radius:10px;background:#fff7ed;border-color:#fed7aa;color:#9a3412;font-size:.85rem;display:flex;flex-direction:column;gap:.25rem;font-weight:600;}
.juy-membre-row{display:flex;align-items:center;justify-content:space-between;padding:.65rem .85rem;background:#f8fafc;border-radius:8px;margin-bottom:.4rem;font-size:.82rem;}
.juy-membre-info{display:flex;align-items:center;gap:.55rem;}
.juy-role-chip{padding:.15rem .5rem;border-radius:5px;font-size:.65rem;text-transform:uppercase;font-weight:700;}
.juy-role-chip--president{background:rgba(4,83,203,.15);color:#0453cb;}
.juy-role-chip--assesseur{background:rgba(59,125,219,.10);color:#3b7ddb;}
.juy-role-chip--secretaire{background:rgba(16,185,129,.10);color:#047857;}
.juy-role-chip--consultatif{background:rgba(100,116,139,.10);color:#475569;}
.juy-decision-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.82rem;}
.juy-decision-table th{background:#f8fafc;color:#475569;font-weight:600;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;padding:.6rem .75rem;text-align:left;border-bottom:1px solid #e2e8f0;}
.juy-decision-table td{padding:.7rem .75rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.juy-decision-table tbody tr:hover{background:#f8fafc;cursor:pointer;}
.juy-dec-chip{display:inline-flex;padding:.15rem .55rem;border-radius:5px;font-size:.68rem;font-weight:700;text-transform:uppercase;}
.juy-dec-chip--admis{background:rgba(16,185,129,.10);color:#047857;}
.juy-dec-chip--admission_rattrapage{background:rgba(245,158,11,.10);color:#b45309;}
.juy-dec-chip--ajourne{background:rgba(220,38,38,.10);color:#b91c1c;}
.juy-dec-chip--exclu{background:rgba(220,38,38,.18);color:#7f1d1d;font-weight:800;}
.juy-dec-chip--admis_sous_condition{background:rgba(245,158,11,.18);color:#92400e;}
.juy-dec-chip--defere{background:rgba(100,116,139,.10);color:#475569;}
.juy-override-badge{display:inline-flex;padding:.1rem .35rem;border-radius:4px;font-size:.6rem;font-weight:700;background:rgba(245,158,11,.18);color:#92400e;margin-left:.3rem;}
.juy-stats-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:.75rem;}
.juy-stat-card{padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;text-align:center;}
.juy-stat-value{font-size:1.8rem;font-weight:700;color:#0453cb;line-height:1;}
.juy-stat-label{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:.35rem;}
.juy-btn{padding:.5rem 1rem;border-radius:9px;font-size:.82rem;font-weight:600;border:1px solid;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;min-height:2.75rem;}
.juy-btn--primary{background:#0453cb;color:#fff;border-color:#0453cb;}
.juy-btn--secondary{background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
.juy-btn--warning{background:rgba(245,158,11,.10);color:#b45309;border-color:rgba(245,158,11,.25);}
.juy-btn--success{background:rgba(16,185,129,.10);color:#047857;border-color:rgba(16,185,129,.25);}
.juy-btn--danger{background:rgba(220,38,38,.08);color:#b91c1c;border-color:rgba(220,38,38,.2);}
.juy-btn:disabled{opacity:.5;cursor:not-allowed;}
.juy-modal{position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:1050;display:flex;align-items:center;justify-content:center;padding:1rem;}
.juy-modal-body{background:#fff;border-radius:16px;padding:1.5rem;max-width:560px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,.3);}
.juy-modal-body h2{margin:0 0 1rem;color:#0453cb;font-size:1.15rem;display:flex;align-items:center;gap:.5rem;}
.juy-actions-bar{display:flex;gap:.5rem;flex-wrap:wrap;padding:1rem 1.25rem;background:#fff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:1.25rem;align-items:center;}
.juy-actions-bar > .pv-numero{margin-left:auto;font-family:'Courier New',monospace;font-size:.85rem;color:#0453cb;font-weight:700;background:rgba(4,83,203,.08);padding:.35rem .65rem;border-radius:7px;}
.juy-readiness{display:flex;align-items:flex-start;gap:.65rem;padding:.8rem 1rem;border-radius:12px;margin-bottom:1.25rem;font-size:.82rem;border:1px solid;}
.juy-readiness--ok{background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.25);color:#047857;}
.juy-readiness--ko{background:#fff7ed;border-color:#fed7aa;color:#9a3412;}
.juy-signature-canvas{width:100%;height:180px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;touch-action:none;cursor:crosshair;}
</style>
