<style>
/* ===== Activité du personnel (ap-*) ===== */
.ap-shell { display: grid; gap: 1.25rem; -webkit-font-smoothing: antialiased; }
.ap-shell > * { min-width: 0; }
.ap-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; }
.ap-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.ap-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.ap-hero-icon { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); }
.ap-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0 0 .2rem; }
.ap-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: 0; }
.ap-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.ap-hero-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .75rem; margin-top: 1.5rem; }
.ap-kpi { display: flex; align-items: center; gap: .75rem; min-width: 0; text-align: left; padding: .9rem 1rem; border-radius: 12px; background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.15); color: #fff; text-decoration: none; cursor: pointer; transition: background .2s ease; }
a.ap-kpi:hover, button.ap-kpi:hover { background: rgba(255,255,255,.18); color: #fff; }
.ap-kpi--attente { min-height: 76px; cursor: default; }
.ap-kpi-icone { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.15); }
.ap-kpi-texte { display: flex; flex-direction: column; min-width: 0; }
.ap-kpi-valeur { font-size: clamp(1.15rem, 2vw, 1.45rem); font-weight: 700; line-height: 1.1; white-space: nowrap; font-variant-numeric: tabular-nums; }
.ap-kpi-valeur small { font-size: .8em; color: rgba(255,255,255,.7); font-weight: 600; }
.ap-kpi-libelle { font-size: .74rem; color: rgba(255,255,255,.75); margin-top: .2rem; line-height: 1.3; }
.ap-kpi-repere { font-size: .7rem; color: rgba(255,255,255,.85); margin-top: .15rem; }
.ap-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .5rem 1rem; font-size: .82rem; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; white-space: nowrap; transition: all .2s ease; }
.ap-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.22); }
.ap-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
.ap-btn--ghost { background: #fff; color: #0453cb; border-color: #cfdcf2; }
.ap-btn--ghost:hover { background: #f1f6fe; color: #033a8e; }
.ap-btn--compact { padding: .35rem .7rem; font-size: .76rem; }
.ap-filtres { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: .85rem 1rem; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); position: relative; z-index: 30; }
.ap-filtres:has(.au-select-trigger--open) { z-index: 1400; }
.ap-filtres .au-select { flex: 0 1 220px; }
.ap-recherche { flex: 1 1 220px; display: flex; align-items: center; gap: .5rem; border: 1px solid #e2e8f0; border-radius: 10px; padding: .45rem .75rem; color: #64748b; }
.ap-recherche input { border: 0; outline: none; flex: 1; font-size: .85rem; min-width: 0; }
.ap-etat { font-size: .8rem; color: #64748b; display: inline-flex; align-items: center; gap: .4rem; }
.ap-corps { display: grid; gap: 1.25rem; transition: opacity .2s ease; }
.ap-corps--charge { opacity: .55; }
.ap-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.35rem; min-width: 0; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.ap-card-tete { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: 1rem; }
.ap-card-tete h2 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.ap-card-tete p { font-size: .8rem; color: #64748b; margin: .15rem 0 0; line-height: 1.4; }
.ap-section-icone { width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .95rem; }
.ap-file { display: grid; gap: .5rem; }
.ap-file-ligne { display: flex; align-items: center; gap: .75rem; padding: .7rem .85rem; border: 1px solid #e8eef7; border-radius: 12px; }
.ap-file-qui { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.ap-file-ligne strong { font-size: .88rem; color: #0f172a; }
.ap-file-ligne span { font-size: .76rem; color: #64748b; }
.ap-avatar { width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: rgba(4,83,203,.10); color: #0453cb; font-weight: 700; font-size: .85rem; }
.ap-vide { display: flex; flex-direction: column; align-items: center; text-align: center; gap: .3rem; padding: 1.5rem 1rem; border: 1px dashed #d7e1ee; border-radius: 12px; background: #f8fafc; color: #64748b; font-size: .82rem; }
.ap-vide i { color: #0453cb; font-size: 1.2rem; }
.ap-vide strong { color: #1e293b; font-size: .9rem; }
.ap-table-wrap { overflow-x: auto; margin: 0 -1.35rem -1.25rem; }
.ap-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.ap-table th { text-align: left; font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; padding: .6rem .75rem; background: #f8fafc; border-top: 1px solid #eef2f7; border-bottom: 1px solid #eef2f7; vertical-align: bottom; }
.ap-table td { padding: .65rem .75rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.ap-table .ap-num { text-align: right; font-variant-numeric: tabular-nums; }
.ap-table td.ap-num { white-space: nowrap; }
.ap-ligne { cursor: pointer; transition: background .15s ease; }
.ap-ligne:hover { background: #f5f8fe; }
.ap-personne { display: flex; align-items: center; gap: .6rem; }
.ap-personne div { display: flex; flex-direction: column; min-width: 0; }
.ap-personne strong { font-size: .86rem; color: #0f172a; white-space: nowrap; }
.ap-personne span { font-size: .72rem; color: #64748b; }
.ap-ratio { display: flex; align-items: center; gap: .5rem; justify-content: flex-end; }
.ap-ratio-piste { width: 56px; height: 6px; border-radius: 3px; background: #eef2f7; overflow: hidden; }
.ap-ratio-barre { height: 100%; background: #0453cb; border-radius: 3px; }
.ap-ratio-barre.is-bas { background: #f59e0b; }
.ap-muet { color: #cbd5e1; }
.ap-alerte { color: #b45309; font-weight: 700; }
.ap-note { font-size: .76rem; color: #64748b; margin: .75rem 0 0; }
.ap-grille { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; align-items: start; }
.ap-preuve { display: flex; align-items: baseline; gap: .6rem; padding: .5rem 0; border-bottom: 1px solid #f1f5f9; font-size: .82rem; }
.ap-preuve:last-child { border-bottom: 0; }
.ap-preuve time { color: #64748b; font-variant-numeric: tabular-nums; white-space: nowrap; width: 5.5em; }
.ap-preuve span { flex: 1; min-width: 0; color: #1e293b; }
.ap-preuve em { font-style: normal; color: #64748b; white-space: nowrap; }
.ap-segments { display: inline-flex; gap: .25rem; padding: .25rem; background: rgba(255,255,255,.12); border-radius: 10px; }
.ap-segments a { color: rgba(255,255,255,.85); text-decoration: none; border-radius: 8px; padding: .35rem .75rem; font-size: .78rem; font-weight: 600; }
.ap-segments a.is-active { background: #fff; color: #0453cb; }
@media (max-width: 768px) {
    .ap-hero { padding: 1.4rem 1.2rem 1.1rem; }
    .ap-hero-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ap-card { padding: 1rem; }
    .ap-table-wrap { margin: 0 -1rem -1rem; }
    .ap-filtres .au-select { flex: 1 1 100%; }
    /* Sur téléphone, le nom et le motif prennent toute la ligne, les actions passent dessous. */
    .ap-file-ligne { flex-wrap: wrap; }
    .ap-file-qui { flex: 1 1 calc(100% - 3.5rem); }
    .ap-file-ligne .ap-btn { flex: 1 1 0; justify-content: center; }
}
@media (max-width: 576px) {
    .ap-hero-kpis { grid-template-columns: minmax(0, 1fr); }
}
</style>
