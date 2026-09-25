<style>
/* ===== Pilotage académique — tableau de bord (pa-*) ===== */
.pa-shell { display: grid; gap: 1.25rem; -webkit-font-smoothing: antialiased; }
.pa-shell > * { min-width: 0; }

.pa-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
}
.pa-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.pa-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.pa-hero-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
}
.pa-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0 0 .2rem; }
.pa-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: 0; }
.pa-hero p strong { color: #fff; font-weight: 600; }

.pa-hero-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .75rem; margin-top: 1.5rem; }
.pa-kpi {
    display: flex; align-items: center; gap: .75rem; min-width: 0; text-align: left;
    padding: .9rem 1rem; border-radius: 12px; cursor: pointer;
    background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.15); color: #fff;
    transition: background .2s ease, border-color .2s ease;
}
.pa-kpi:hover, .pa-kpi:focus-visible { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.32); outline: none; }
.pa-kpi--attente { cursor: default; min-height: 76px; }
.pa-kpi-icone {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.15);
}
.pa-kpi-texte { display: flex; flex-direction: column; min-width: 0; }
.pa-kpi-valeur { font-size: clamp(1.15rem, 2vw, 1.45rem); font-weight: 700; line-height: 1.1; white-space: nowrap; font-variant-numeric: tabular-nums; }
.pa-kpi-valeur small { font-size: .8em; font-weight: 600; color: rgba(255,255,255,.7); }
.pa-kpi-libelle { font-size: .74rem; color: rgba(255,255,255,.75); margin-top: .2rem; line-height: 1.3; }
.pa-squelette { display: block; width: 70%; height: 14px; border-radius: 6px; background: rgba(255,255,255,.18); }

.pa-btn {
    display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px;
    padding: .5rem 1rem; font-size: .82rem; font-weight: 600; text-decoration: none; cursor: pointer;
    border: 1px solid transparent; transition: all .2s ease; white-space: nowrap;
}
.pa-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.22); }
.pa-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
.pa-btn--ghost { background: #fff; color: #0453cb; border-color: #cfdcf2; }
.pa-btn--ghost:hover { background: #f1f6fe; color: #033a8e; }
.pa-btn--compact { padding: .35rem .7rem; font-size: .76rem; }

.pa-filtres {
    display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: .85rem 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    position: relative; z-index: 30;
}
.pa-filtres:has(.au-select-trigger--open) { z-index: 1400; }
.pa-filtres .au-select { flex: 0 1 190px; min-width: 150px; }
.pa-filtres .pa-filtre-large { flex: 1 1 260px; }
.pa-filtres .au-select-trigger { width: 100%; }
.pa-etat { font-size: .8rem; color: #64748b; display: inline-flex; align-items: center; gap: .4rem; }

.pa-corps { display: grid; gap: 1.25rem; transition: opacity .2s ease; }
.pa-corps--charge { opacity: .55; }
.pa-grille { display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); gap: 1.25rem; align-items: start; }

.pa-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.35rem; min-width: 0;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.pa-card-tete { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: 1rem; }
.pa-card-tete--actions { flex-wrap: wrap; }
.pa-card-tete--actions > div:nth-child(2) { flex: 1 1 320px; }
.pa-card-tete h2 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.pa-card-tete p { font-size: .8rem; color: #64748b; margin: .15rem 0 0; line-height: 1.4; }
.pa-section-icone {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: .95rem;
}
.pa-attente, .pa-erreur { display: flex; align-items: center; gap: .6rem; color: #64748b; font-size: .88rem; }
.pa-erreur { color: #b91c1c; }

.pa-relance { border: 1px solid #e8eef7; border-radius: 12px; padding: .8rem .9rem; }
.pa-relance + .pa-relance { margin-top: .6rem; }
.pa-relance-tete { display: flex; align-items: center; gap: .7rem; }
.pa-relance-qui { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.pa-relance-qui strong { font-size: .9rem; color: #0f172a; }
.pa-relance-qui span { font-size: .76rem; color: #64748b; }
.pa-relance-lignes { list-style: none; margin: .6rem 0 0 2.75rem; padding: 0; display: grid; gap: .3rem; }
.pa-relance-lignes li { display: flex; align-items: baseline; gap: .6rem; font-size: .8rem; }
.pa-relance-quoi { flex: 1; min-width: 0; color: #1e293b; }
.pa-relance-combien { color: #64748b; white-space: nowrap; font-variant-numeric: tabular-nums; }
.pa-avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(4,83,203,.10); color: #0453cb; font-weight: 700; font-size: .85rem;
}
.pa-lien { background: none; border: 0; padding: 0; color: #0453cb; font-weight: 600; font-size: .78rem; cursor: pointer; white-space: nowrap; }
.pa-lien:hover { text-decoration: underline; }
.pa-note { font-size: .76rem; color: #64748b; margin: .75rem 0 0; display: flex; gap: .4rem; align-items: center; }

.pa-vide {
    display: flex; flex-direction: column; align-items: center; text-align: center; gap: .3rem;
    padding: 1.5rem 1rem; border: 1px dashed #d7e1ee; border-radius: 12px; background: #f8fafc; color: #64748b; font-size: .82rem;
}
.pa-vide i { color: #0453cb; font-size: 1.2rem; }
.pa-vide strong { color: #1e293b; font-size: .9rem; }
.pa-vide--compact { padding: .8rem; }

.pa-mini-titre { font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin: .25rem 0 .4rem; }
.pa-graphe { position: relative; height: 150px; margin-bottom: .6rem; }

.pa-segments { display: inline-flex; gap: .25rem; padding: .25rem; background: #f1f5f9; border-radius: 10px; }
.pa-segments button { border: 0; background: none; border-radius: 8px; padding: .35rem .75rem; font-size: .78rem; font-weight: 600; color: #475569; cursor: pointer; }
.pa-segments button span { font-weight: 700; color: #94a3b8; margin-left: .2rem; }
.pa-segments button.is-active { background: #fff; color: #0453cb; box-shadow: 0 1px 3px rgba(15,23,42,.08); }
.pa-segments button.is-active span { color: #0453cb; }

.pa-table-wrap { overflow-x: auto; margin: 0 -1.35rem -1.25rem; }
.pa-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.pa-table th { text-align: left; font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; padding: .6rem 1rem; background: #f8fafc; border-top: 1px solid #eef2f7; border-bottom: 1px solid #eef2f7; white-space: nowrap; }
.pa-table td { padding: .7rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.pa-table .pa-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.pa-ligne { cursor: pointer; transition: background .15s ease; }
.pa-ligne:hover { background: #f5f8fe; }
.pa-classe { display: flex; flex-direction: column; gap: .2rem; }
.pa-classe strong { color: #0f172a; font-size: .86rem; }
.pa-classe > span { font-size: .72rem; color: #64748b; display: flex; gap: .4rem; align-items: center; }
.pa-badge { display: inline-flex; padding: .12rem .45rem; border-radius: 6px; font-size: .68rem; font-weight: 700; }
.pa-badge--ok { background: rgba(16,185,129,.12); color: #047857; }
.pa-badge--alerte { background: rgba(245,158,11,.14); color: #b45309; }
.pa-badge--neutre { background: rgba(4,83,203,.08); color: #0453cb; }
.pa-jauge { display: flex; align-items: center; gap: .55rem; min-width: 150px; }
.pa-jauge-piste { flex: 1; height: 7px; border-radius: 4px; background: #eef2f7; overflow: hidden; }
.pa-jauge-barre { height: 100%; border-radius: 4px; background: #0453cb; }
.pa-jauge-barre.is-bas { background: #f59e0b; }
.pa-jauge span { font-size: .78rem; font-weight: 700; color: #1e293b; width: 3.2em; text-align: right; font-variant-numeric: tabular-nums; }
.pa-muet { color: #94a3b8; font-size: .78rem; }

.pa-etudiant { display: flex; align-items: center; gap: .75rem; padding: .6rem 0; border-bottom: 1px solid #f1f5f9; }
.pa-etudiant:last-child { border-bottom: 0; }
.pa-etudiant-qui { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.pa-etudiant-qui strong { font-size: .86rem; color: #0f172a; }
.pa-etudiant-qui span, .pa-etudiant-chiffres span { font-size: .74rem; color: #64748b; }
.pa-etudiant-chiffres { display: flex; flex-direction: column; align-items: flex-end; text-align: right; }
.pa-etudiant-chiffres strong { font-size: .95rem; color: #b45309; font-variant-numeric: tabular-nums; }

.pa-pied { font-size: .74rem; color: #94a3b8; margin: 0; text-align: right; }

.pa-panneau { position: fixed; inset: 0; z-index: 1200; display: flex; justify-content: flex-end; }
.pa-panneau-fond { position: absolute; inset: 0; background: rgba(15,23,42,.35); }
.pa-panneau-corps {
    position: relative; width: min(760px, 100vw); height: 100%; overflow-y: auto;
    background: #f8fafc; padding: 1.25rem 1.35rem; box-shadow: -8px 0 30px rgba(15,23,42,.15);
}
.pa-panneau-tete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.pa-panneau-tete h2 { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: .1rem 0 0; }
.pa-surtitre { font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.pa-fermer { border: 1px solid #e2e8f0; background: #fff; border-radius: 10px; width: 36px; height: 36px; color: #475569; cursor: pointer; }

@media (max-width: 992px) {
    .pa-grille { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 768px) {
    .pa-hero { padding: 1.4rem 1.2rem 1.1rem; }
    .pa-hero-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pa-kpi { flex-direction: column; align-items: flex-start; }
    .pa-filtres .au-select { flex: 1 1 100%; }
    .pa-relance-lignes { margin-left: 0; }
    .pa-card { padding: 1rem; }
    .pa-table-wrap { margin: 0 -1rem -1rem; }
    .pa-etudiant { flex-wrap: wrap; }
}
@media (max-width: 576px) {
    .pa-hero-kpis { grid-template-columns: minmax(0, 1fr); }
    .pa-kpi { flex-direction: row; align-items: center; }
}
</style>
