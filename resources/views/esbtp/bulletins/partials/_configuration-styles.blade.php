<style>
.bcfg-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}
.bcfg-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.bcfg-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.bcfg-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.bcfg-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.bcfg-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: .25rem 0 0; }
.bcfg-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600; text-decoration: none;
    border: 1px solid transparent; cursor: pointer; transition: all .2s ease;
}
.bcfg-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.bcfg-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.bcfg-btn--white { background: #fff; color: #0453cb; }
.bcfg-btn--white:hover { background: #f1f5fc; color: #0453cb; }
.bcfg-btn--ghost { background: #fff; color: #0453cb; border-color: #dbe4f3; }
.bcfg-btn--ghost:hover { background: #f8fafc; }
.bcfg-btn--save {
    background: #0453cb; color: #fff;
    box-shadow: 0 4px 16px rgba(4,83,203,.18);
}
.bcfg-btn--save:hover { background: #033a8e; color: #fff; }
.bcfg-btn:disabled { opacity: .6; cursor: wait; }
.bcfg-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.bcfg-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.bcfg-kpi-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(255,255,255,.14);
    display: flex; align-items: center; justify-content: center;
}
.bcfg-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.bcfg-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
.bcfg-tabs {
    display: flex; gap: .5rem; margin-bottom: 1.25rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: .4rem;
}
.bcfg-tab {
    flex: 1; border: 0; background: transparent; color: #475569;
    border-radius: 9px; padding: .65rem 1rem; font-size: .86rem; font-weight: 700;
    cursor: pointer;
}
.bcfg-tab--active { background: #0453cb; color: #fff; }
.bcfg-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
    overflow: visible;
}
.bcfg-card-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #f1f5f9;
}
.bcfg-section-header { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.bcfg-section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
}
.bcfg-section-header h3 { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
.bcfg-section-header p { font-size: .78rem; color: #64748b; margin: .15rem 0 0; }
.bcfg-card-badge {
    font-size: .7rem; font-weight: 700; color: #0453cb;
    background: rgba(4,83,203,.08); padding: .25rem .65rem; border-radius: 999px;
}
.bcfg-card-body { padding: 1.25rem 1.4rem 1.4rem; }
.bcfg-label {
    display: block; font-size: .72rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .04em; margin-bottom: .35rem;
}
.bcfg-input, .bcfg-select, .bcfg-textarea {
    width: 100%; padding: .6rem .85rem;
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .88rem; color: #1e293b; background: #fff;
}
.bcfg-input:focus, .bcfg-select:focus, .bcfg-textarea:focus {
    outline: none; border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.bcfg-hint { font-size: .75rem; color: #64748b; margin-top: .35rem; }
.bcfg-toggles {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: .5rem;
}
.bcfg-toggle {
    display: flex; align-items: center; gap: .65rem;
    padding: .65rem .85rem; border-radius: 8px;
    background: #f8fafc; border: 1px solid #e2e8f0; cursor: pointer;
}
.bcfg-toggle-label { font-size: .84rem; font-weight: 500; color: #1e293b; flex: 1; }
.bcfg-toggle .form-check-input {
    width: 2.2em; height: 1.15em; margin: 0; cursor: pointer; flex-shrink: 0;
}
.bcfg-toggle .form-check-input:checked {
    background-color: #0453cb; border-color: #0453cb;
}
.bcfg-style-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.bcfg-style {
    display: flex; align-items: flex-start; gap: .75rem;
    border: 1.5px solid #e2e8f0; border-radius: 12px; padding: .9rem 1rem;
    background: #fff; cursor: pointer;
}
.bcfg-style--active { border-color: #0453cb; background: rgba(4,83,203,.04); }
.bcfg-style-title { font-size: .9rem; font-weight: 700; color: #0f172a; }
.bcfg-style-desc { font-size: .78rem; color: #64748b; margin-top: .2rem; }
.bcfg-preview {
    border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;
    background: #fff;
}
.bcfg-preview-head {
    background: #0453cb; color: #fff; padding: .7rem .9rem; font-weight: 700;
}
.bcfg-preview-row {
    display: grid; grid-template-columns: 2fr 1fr 1fr; gap: .5rem;
    padding: .55rem .9rem; border-top: 1px solid #e2e8f0; color: #111827;
}
.bcfg-preview-foot { padding: .8rem .9rem; color: #111827; }
.bcfg-footer {
    display: flex; justify-content: flex-end; gap: .75rem;
    position: sticky; bottom: 0; z-index: 10;
    background: #f8fafc; border-top: 1px solid #e2e8f0;
    padding: 1rem 0; margin-top: .5rem;
}
@media (max-width: 768px) {
    .bcfg-hero { padding: 1.25rem; }
    .bcfg-style-grid { grid-template-columns: 1fr; }
    .bcfg-toggles { grid-template-columns: 1fr; }
    .bcfg-kpis { flex-direction: column; }
}
</style>
