{{-- Styles du journal d'audit lisible — namespace jda-*. Inclus dans un @push('styles') parent
     par le journal, le detail d'une action et l'activite des personnes. --}}
<style>
.jda { --jda-primary: #0453cb; --jda-primary-d: #033a8e; --jda-dark: #0f172a; --jda-text: #1e293b; --jda-muted: #64748b; --jda-line: #e2e8f0; --jda-surface: #f8fafc; --jda-soft: #e6eefb; --jda-danger: #b91c1c; color: var(--jda-text); }
.jda [x-cloak] { display: none !important; }

/* Hero */
.jda-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%); border-radius: 18px; padding: 1.5rem 1.75rem 0; color: #fff; margin-bottom: 1rem; }
.jda-hero-top { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
.jda-hero-gauche { display: flex; gap: 1rem; align-items: center; min-width: 0; }
.jda-hero-icone { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.jda-hero h1 { font-size: 1.45rem; font-weight: 800; margin: 0; color: #fff; }
.jda-hero p { margin: .2rem 0 0; font-size: .88rem; color: rgba(255,255,255,.75); }
.jda-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
.jda-btn { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem 1rem; border-radius: 10px; font-size: .82rem; font-weight: 700; text-decoration: none; border: 1px solid transparent; cursor: pointer; white-space: nowrap; font-family: inherit; transition: background .2s ease, color .2s ease; }
.jda-btn--glass { background: rgba(255,255,255,.14); color: #fff; border-color: rgba(255,255,255,.24); }
.jda-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.jda-btn--white { background: #fff; color: var(--jda-primary); }
.jda-btn--ghost { background: #fff; color: var(--jda-primary); border-color: var(--jda-line); }
.jda-btn--ghost:hover { border-color: #c9daf6; background: #f7faff; }
.jda-onglets { display: flex; gap: .25rem; margin-top: 1.25rem; overflow-x: auto; scrollbar-width: none; }
.jda-onglets::-webkit-scrollbar { display: none; }
.jda-onglet { padding: .7rem 1rem; border-radius: 10px 10px 0 0; color: rgba(255,255,255,.88); font-weight: 600; font-size: .85rem; text-decoration: none; white-space: nowrap; }
.jda-onglet:hover { color: #fff; background: rgba(255,255,255,.08); }
.jda-onglet.is-actif { background: #f3f6fb; color: var(--jda-primary); font-weight: 800; }
.jda-onglet-compte { display: inline-block; margin-left: .35rem; padding: 0 .45rem; border-radius: 999px; background: #fef2f2; color: var(--jda-danger); font-size: .72rem; font-weight: 800; }

/* Filtres */
.jda-filtres { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; }
.jda-recherche { flex: 1 1 280px; display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid var(--jda-line); border-radius: 10px; padding: 0 .75rem; min-height: 42px; }
.jda-recherche:focus-within { border-color: var(--jda-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.14); }
.jda-recherche input { border: 0; outline: none; flex: 1; font: inherit; font-size: .85rem; background: transparent; min-width: 0; }
.jda-recherche kbd { font-size: .7rem; background: var(--jda-surface); border: 1px solid var(--jda-line); border-radius: 5px; padding: .05rem .35rem; color: var(--jda-muted); }
.jda-filtre { flex: 0 1 220px; min-width: 180px; display: flex; }
.jda-filtre > * { width: 100%; }
.jda-filtre--large { flex-basis: 280px; }
.jda-bascule { display: inline-flex; align-items: center; gap: .45rem; min-height: 42px; padding: 0 .9rem; border-radius: 10px; border: 1px solid var(--jda-line); background: #fff; color: #334155; font: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; }
.jda-bascule.is-actif { background: var(--jda-soft); border-color: #c9daf6; color: var(--jda-primary); }

/* Liste */
.jda-carte { background: #fff; border: 1px solid var(--jda-line); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.jda-liste { position: relative; transition: opacity .2s ease; }
.jda-liste.is-chargement { opacity: .55; pointer-events: none; }
.jda-jour { padding: .6rem 1.25rem; font-size: .72rem; font-weight: 800; letter-spacing: .08em; color: var(--jda-muted); background: var(--jda-surface); border-bottom: 1px solid #eef2f7; }
.jda-jour:first-child { border-radius: 14px 14px 0 0; }
.jda-ligne { display: flex; gap: .9rem; align-items: flex-start; padding: .85rem 1.25rem; border-bottom: 1px solid #f1f5f9; color: inherit; text-decoration: none; transition: background .15s ease; }
.jda-ligne:hover { background: #f7faff; color: inherit; }
.jda-ligne:focus-visible { outline: none; box-shadow: inset 0 0 0 2px rgba(4,83,203,.45); }
.jda-ligne.is-alerte { box-shadow: inset 3px 0 0 var(--jda-danger); }
.jda-av { width: 36px; height: 36px; border-radius: 50%; background: var(--jda-soft); color: var(--jda-primary); font-weight: 800; font-size: .78rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.jda-av--auto { background: #f1f5f9; color: #475569; }
.jda-corps { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .35rem; }
.jda-phrase { font-size: .9rem; line-height: 1.45; color: var(--jda-text); overflow-wrap: anywhere; }
.jda-phrase strong { color: var(--jda-dark); }
.jda-role { color: var(--jda-muted); }
.jda-phrase .jda-cible { color: var(--jda-primary); }
.jda-sup { font-size: .72rem; font-weight: 700; color: var(--jda-muted); background: #f1f5f9; border-radius: 5px; padding: .05rem .35rem; }
.jda-puces { display: flex; gap: .35rem; flex-wrap: wrap; }
.jda-puce { font-size: .75rem; font-weight: 600; color: #334155; background: #f1f5f9; border-radius: 6px; padding: .12rem .5rem; }
.jda-puce--change { background: #eef4ff; border: 1px solid #d6e3f8; color: var(--jda-dark); }
.jda-droite { display: flex; flex-direction: column; align-items: flex-end; gap: .35rem; flex-shrink: 0; }
.jda-droite time { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; color: #475569; }
.jda-motif { font-size: .72rem; font-weight: 700; color: var(--jda-danger); background: #fef2f2; border-radius: 999px; padding: .12rem .55rem; white-space: nowrap; }
.jda-vide { padding: 2.5rem 1.25rem; text-align: center; color: var(--jda-muted); font-size: .88rem; }
.jda-vide strong { display: block; color: var(--jda-dark); font-size: .95rem; margin-bottom: .25rem; }
.jda-auto { display: flex; justify-content: space-between; align-items: center; gap: .75rem; margin: .85rem 1.25rem; padding: .7rem .9rem; border: 1px dashed #c9daf6; border-radius: 10px; font-size: .82rem; color: #334155; }
.jda-lien { background: none; border: 0; padding: 0; font: inherit; font-weight: 700; color: var(--jda-primary); cursor: pointer; text-decoration: none; }
.jda-lien:hover { color: var(--jda-primary-d); text-decoration: underline; }
.jda .li-bas { padding: .75rem 1.25rem; }

/* Detail d'une action */
.jda-hero--detail { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.25rem; padding: 1.5rem 1.75rem; }
.jda-d-titre { display: flex; flex-direction: column; gap: .6rem; min-width: 0; flex: 1 1 520px; }
.jda-d-retour { color: rgba(255,255,255,.82); font-size: .82rem; font-weight: 600; text-decoration: none; display: inline-flex; gap: .4rem; align-items: center; }
.jda-d-retour:hover { color: #fff; }
.jda-hero--detail h1 { font-size: 1.45rem; line-height: 1.3; max-width: 900px; overflow-wrap: anywhere; }
.jda-d-badges { display: flex; gap: .5rem; flex-wrap: wrap; }
.jda-d-badge { font-size: .78rem; font-weight: 600; background: rgba(255,255,255,.16); border-radius: 999px; padding: .3rem .7rem; }
.jda-d-badge--alerte { background: #fff; color: var(--jda-danger); font-weight: 700; }
.jda-d-grille { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; align-items: start; }
.jda-d-principal { grid-column: span 2; display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
.jda-d-cote { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
.jda-d-bloc { background: #fff; border: 1px solid var(--jda-line); border-radius: 14px; padding: 1.25rem; }
.jda-d-bloc h2, .jda-d-tech summary { margin: 0 0 .85rem; font-size: .8rem; font-weight: 800; letter-spacing: .08em; color: var(--jda-muted); }
.jda-d-cartes { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .75rem; }
.jda-d-carte { display: flex; flex-direction: column; gap: .2rem; padding: .85rem; border: 1px solid var(--jda-line); border-radius: 12px; color: inherit; text-decoration: none; min-width: 0; transition: border-color .2s ease, background .2s ease; }
a.jda-d-carte:hover { border-color: #c9daf6; background: #f7faff; color: inherit; }
.jda-d-carte--objet { border-color: #c9daf6; background: #f7faff; }
.jda-d-carte-type { font-size: .75rem; font-weight: 700; color: var(--jda-primary); }
.jda-d-carte-nom { font-size: 1rem; font-weight: 800; color: var(--jda-dark); overflow-wrap: anywhere; }
.jda-d-carte-info { font-size: .78rem; color: #475569; }
.jda-d-table { display: flex; flex-direction: column; }
.jda-d-entete, .jda-d-rangee { display: grid; grid-template-columns: 1fr 1.2fr 1.6fr; gap: .75rem; padding: .7rem .75rem; }
.jda-d-table--2 .jda-d-entete, .jda-d-table--2 .jda-d-rangee { grid-template-columns: 1fr 2fr; }
.jda-d-entete { font-size: .72rem; font-weight: 700; letter-spacing: .06em; color: var(--jda-muted); padding-top: .2rem; }
.jda-d-rangee { border-top: 1px solid #f1f5f9; font-size: .88rem; align-items: center; }
.jda-d-champ { font-weight: 700; }
.jda-d-avant { color: var(--jda-muted); text-decoration: line-through; overflow-wrap: anywhere; }
.jda-d-apres { font-weight: 600; color: var(--jda-dark); overflow-wrap: anywhere; }
.jda-d-mobile { display: none; }
.jda-d-vide { margin: 0; color: var(--jda-muted); font-size: .88rem; }
.jda-d-vie { list-style: none; margin: 0; padding: 0; }
.jda-d-vie li { position: relative; padding: 0 0 1rem 1.5rem; display: flex; flex-direction: column; gap: .2rem; align-items: flex-start; }
.jda-d-vie li::before { content: ''; position: absolute; left: 5px; top: 14px; bottom: 0; width: 2px; background: #c9daf6; }
.jda-d-vie li:last-child { padding-bottom: 0; }
.jda-d-vie li:last-child::before { display: none; }
.jda-d-point { position: absolute; left: 0; top: 3px; width: 12px; height: 12px; border-radius: 50%; background: #c9daf6; }
.jda-d-vie li.is-courant .jda-d-point { background: var(--jda-primary); box-shadow: 0 0 0 4px rgba(4,83,203,.15); }
.jda-d-vie li.is-alerte .jda-d-point { background: var(--jda-danger); }
.jda-d-vie-titre { font-weight: 700; font-size: .88rem; color: var(--jda-dark); text-decoration: none; }
a.jda-d-vie-titre:hover { color: var(--jda-primary); text-decoration: underline; }
.jda-d-vie-meta { font-size: .78rem; color: var(--jda-muted); }
.jda-d-tech summary { cursor: pointer; margin: 0; list-style: none; }
.jda-d-tech summary::-webkit-details-marker { display: none; }
.jda-d-tech summary::after { content: ' +'; }
.jda-d-tech[open] summary { margin-bottom: .85rem; }
.jda-d-tech[open] summary::after { content: ' −'; }
.jda-d-tech dl { display: grid; grid-template-columns: auto 1fr; gap: .45rem .9rem; margin: 0; font-size: .82rem; }
.jda-d-tech dt { color: var(--jda-muted); font-weight: 600; }
.jda-d-tech dd { margin: 0; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .78rem; overflow-wrap: anywhere; }

/* Activite des personnes */
.jda-kpis { display: flex; gap: .75rem; margin-top: 1.25rem; padding-bottom: 1.5rem; flex-wrap: wrap; }
.jda-kpi { flex: 1; min-width: 140px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .85rem 1rem; color: #fff; text-decoration: none; }
a.jda-kpi:hover { background: rgba(255,255,255,.16); color: #fff; }
.jda-kpi-valeur { font-size: 1.35rem; font-weight: 800; white-space: nowrap; }
.jda-kpi-libelle { font-size: .74rem; color: rgba(255,255,255,.72); margin-top: .1rem; }
.jda-kpi--alerte .jda-kpi-valeur { color: #fff; }
.jda-activite { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1rem; align-items: start; }
.jda-heures { display: grid; grid-template-columns: repeat(24, 1fr); gap: 2px; align-items: end; height: 90px; }
.jda-heures span { background: var(--jda-primary); border-radius: 3px 3px 0 0; min-height: 2px; opacity: .85; }
.jda-heures-axe { display: flex; justify-content: space-between; font-size: .7rem; color: var(--jda-muted); margin-top: .3rem; }
.jda-classement { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .45rem; font-size: .85rem; }
.jda-classement li { display: flex; justify-content: space-between; gap: .75rem; }
.jda-classement strong { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
.jda-periode { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
.jda-periode input[type=date] { min-height: 42px; border: 1px solid var(--jda-line); border-radius: 10px; padding: 0 .6rem; font: inherit; font-size: .82rem; background: #fff; }

@media (max-width: 991px) {
    .jda-d-grille, .jda-activite { grid-template-columns: 1fr; }
    .jda-d-principal { grid-column: auto; }
    .jda-activite .jda-d-cote { order: -1; }
}
@media (max-width: 575px) {
    .jda-hero--detail h1 { font-size: 1.15rem; }
    .jda-d-entete { display: none; }
    .jda-d-rangee, .jda-d-table--2 .jda-d-rangee { grid-template-columns: 1fr; gap: .15rem; }
    .jda-d-mobile { display: inline; color: var(--jda-muted); font-weight: 500; }
}

@media (max-width: 767px) {
    .jda-hero { padding: 1.25rem 1.1rem 0; border-radius: 14px; }
    .jda-hero--detail { padding-bottom: 1.25rem; }
    .jda-hero h1 { font-size: 1.2rem; }
    .jda-hero-top .jda-hero-gauche p { display: none; }
    .jda-actions { width: 100%; display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); }
    .jda-actions > *, .jda-actions .jda-btn { width: 100%; justify-content: center; }
    .jda-actions .jda-btn { white-space: normal; text-align: center; }
    .jda-filtres { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .jda-recherche, .jda-bascule, .jda-filtre--large { grid-column: 1 / -1; }
    .jda-recherche kbd { display: none; }
    .jda-filtre { min-width: 0; }
    .jda-bascule { justify-content: center; }
    .jda-ligne { display: grid; grid-template-columns: 32px minmax(0, 1fr); gap: .35rem .7rem; padding: .8rem 1rem; }
    .jda-av { width: 32px; height: 32px; }
    .jda-droite { grid-column: 2; flex-direction: row; align-items: center; flex-wrap: wrap; gap: .4rem; }
    .jda-auto { flex-direction: column; align-items: flex-start; }
    .jda-periode { grid-column: 1 / -1; }
    .jda-periode input[type=date] { flex: 1; min-width: 0; }
}
</style>
