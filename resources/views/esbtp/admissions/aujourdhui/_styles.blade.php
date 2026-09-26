{{-- Aujourd'hui a l'accueil, namespace adj-*. Hero et KPIs : pattern planning-header.
     Bleu monochrome ; vert (reçue, inscrite) et orange (retard, contact) portent un etat. --}}
<style>
.adj { --adj-primary: #0453cb; --adj-primary-d: #033a8e; --adj-accent: #3b7ddb; --adj-dark: #0f172a; --adj-text: #1e293b; --adj-muted: #64748b; --adj-line: #e2e8f0; --adj-surface: #f8fafc; --adj-soft: #e6eefb; --adj-success: #047857; --adj-warning: #92400e; --adj-danger: #b91c1c; color: var(--adj-text); }
.adj *, .adj *::before, .adj *::after { box-sizing: border-box; }
.adj [hidden] { display: none !important; }

.adj-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 1.75rem 2rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
.adj-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.adj-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.adj-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
.adj-hero-sur { display: block; font-size: .74rem; font-weight: 700; letter-spacing: .04em; color: rgba(255,255,255,.72); margin-bottom: .1rem; }
.adj-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.adj-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .2rem 0 0; max-width: 560px; }
.adj-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

.adj-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 44px; padding: .5rem 1rem; border-radius: 10px; font-size: .84rem; font-weight: 700; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background .2s ease, color .2s ease, box-shadow .2s ease; white-space: nowrap; }
.adj-btn:focus-visible { outline: 3px solid rgba(4,83,203,.35); outline-offset: 2px; }
.adj-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.24); }
.adj-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
.adj-btn--white { background: #fff; color: var(--adj-primary); }
.adj-btn--white:hover { color: var(--adj-primary-d); box-shadow: 0 4px 14px rgba(15,23,42,.18); }
.adj-btn--primary { background: var(--adj-primary); color: #fff; }
.adj-btn--primary:hover { background: var(--adj-primary-d); color: #fff; }
.adj-btn--ghost { background: #fff; color: var(--adj-primary); border-color: #c9daf6; }
.adj-btn--ghost:hover { background: var(--adj-soft); color: var(--adj-primary-d); }
.adj-btn--sm { font-size: .8rem; padding: .45rem .85rem; }
.adj-btn--bloc { width: 100%; }

.adj-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-top: 1.4rem; }
.adj-kpi { display: flex; flex-direction: column; gap: .3rem; padding: .85rem 1rem; border-radius: 12px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); min-width: 0; }
.adj-kpi-libelle { font-size: .78rem; font-weight: 700; color: rgba(255,255,255,.92); }
.adj-kpi-valeur { font-size: 1.6rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
.adj-kpi-repere { font-size: .72rem; color: rgba(255,255,255,.72); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.adj-kpi--succes .adj-kpi-libelle::before, .adj-kpi--alerte .adj-kpi-libelle::before { content: ''; display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: .4rem; vertical-align: middle; }
.adj-kpi--succes .adj-kpi-libelle::before { background: #34d399; }
.adj-kpi--alerte .adj-kpi-libelle::before { background: #fbbf24; }

.adj-corps { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 1.1rem; align-items: start; }
.adj-carte { background: #fff; border: 1px solid var(--adj-line); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.adj-barre { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin-bottom: .9rem; }
.adj-recherche { flex: 1 1 280px; display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid var(--adj-line); border-radius: 12px; padding: 0 .85rem; min-height: 44px; }
.adj-recherche:focus-within { border-color: var(--adj-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.14); }
.adj-recherche i { color: var(--adj-muted); font-size: .85rem; }
.adj-recherche input { flex: 1; border: 0; outline: none; background: transparent; font-size: .88rem; min-width: 0; color: var(--adj-dark); }
.adj-maj { font-size: .75rem; color: var(--adj-muted); display: inline-flex; gap: .35rem; align-items: center; }
.adj-sans-resultat { font-size: .84rem; color: var(--adj-muted); background: #fff; border: 1px dashed #c9daf6; border-radius: 12px; padding: .85rem 1rem; }
#adj-creneaux { display: flex; flex-direction: column; gap: .9rem; transition: opacity .2s ease; }
#adj-creneaux.is-chargement { opacity: .6; }

.adj-creneau { overflow: hidden; }
.adj-creneau-tete { display: flex; align-items: center; gap: .75rem; padding: .7rem 1.1rem; border-bottom: 1px solid #eef2f7; }
.adj-creneau--en_cours .adj-creneau-tete { background: #f3f7ff; box-shadow: inset 3px 0 0 var(--adj-primary); }
.adj-heure { font-family: ui-monospace, 'JetBrains Mono', 'Courier New', monospace; font-weight: 700; font-size: .92rem; color: var(--adj-dark); }
.adj-creneau-info { font-size: .8rem; color: var(--adj-muted); }
.adj-creneau-etat { margin-left: auto; font-size: .74rem; font-weight: 700; color: var(--adj-muted); }
.adj-creneau--en_cours .adj-creneau-etat { color: var(--adj-primary); }
.adj-creneau--termine .adj-creneau-etat { color: var(--adj-success); }
.adj-familles { list-style: none; margin: 0; padding: 0; }
.adj-famille { display: grid; grid-template-columns: 44px minmax(0, 1fr) auto auto; gap: .85rem; align-items: center; padding: .7rem 1.1rem; border-bottom: 1px solid #f1f5f9; }
.adj-famille:last-child { border-bottom: 0; }
.adj-famille.is-recue { background: #f8fbff; }
.adj-coche { width: 44px; height: 44px; border-radius: 12px; border: 2px solid #cbd5e1; background: #fff; color: transparent; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s ease, border-color .15s ease, color .15s ease; font-size: .95rem; }
.adj-coche:hover { border-color: var(--adj-primary); color: #c9daf6; }
.adj-coche:focus-visible { outline: 3px solid rgba(4,83,203,.35); outline-offset: 2px; }
.adj-coche[aria-pressed="true"], .adj-coche.is-fige { background: var(--adj-primary); border-color: var(--adj-primary); color: #fff; }
.adj-coche.is-fige { background: var(--adj-success); border-color: var(--adj-success); cursor: default; }
.adj-coche.is-occupe { opacity: .55; cursor: progress; }
.adj-qui { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.adj-qui strong { font-size: .9rem; font-weight: 700; color: var(--adj-dark); }
.adj-qui > span { font-size: .78rem; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.adj-qui small { font-size: .74rem; color: var(--adj-muted); }
.adj-mono { font-family: ui-monospace, 'JetBrains Mono', 'Courier New', monospace; }
.adj-conv--succes { color: var(--adj-success); font-weight: 600; }
.adj-conv--echec { color: var(--adj-danger); font-weight: 600; }
.adj-conv--alerte { color: var(--adj-warning); font-weight: 600; }
.adj-badge { font-size: .72rem; font-weight: 700; padding: .25rem .65rem; border-radius: 999px; white-space: nowrap; }
.adj-badge--neutre { background: #f1f5f9; color: #334155; }
.adj-badge--primaire { background: var(--adj-soft); color: var(--adj-primary); }
.adj-badge--alerte { background: #fef3c7; color: var(--adj-warning); }
.adj-badge--succes { background: #dcfce7; color: var(--adj-success); }

.adj-vide { text-align: center; padding: 3rem 1.5rem; color: var(--adj-muted); }
.adj-vide i { font-size: 1.6rem; color: var(--adj-primary); background: var(--adj-soft); width: 56px; height: 56px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: .75rem; }
.adj-vide h3 { font-size: 1rem; color: var(--adj-dark); margin: 0 0 .3rem; font-weight: 700; }
.adj-vide p { font-size: .84rem; margin: 0 auto .9rem; max-width: 440px; }

.adj-guichet { position: sticky; top: 84px; background: #f3f7ff; border: 1px solid #c9daf6; border-radius: 14px; padding: 1.1rem; max-height: calc(100vh - 100px); overflow: auto; }
.adj-g-tete { display: flex; flex-direction: column; gap: .15rem; margin-bottom: .8rem; }
.adj-g-sur { font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--adj-primary); }
.adj-g-tete strong { font-size: 1rem; color: var(--adj-dark); }
.adj-g-vide { text-align: center; font-size: .84rem; color: var(--adj-muted); padding: 1.5rem .5rem; }
.adj-g-vide i { font-size: 1.3rem; color: var(--adj-accent); display: block; margin-bottom: .5rem; }
.adj-g-liste { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .75rem; }
.adj-g-famille { background: #fff; border: 1px solid var(--adj-line); border-radius: 12px; padding: .85rem; display: flex; flex-direction: column; gap: .35rem; }
.adj-g-famille > strong { font-size: .95rem; color: var(--adj-dark); }
.adj-g-famille > span { font-size: .78rem; color: #334155; }
.adj-g-famille dl { display: grid; grid-template-columns: auto 1fr; gap: .2rem .75rem; margin: .3rem 0 .4rem; font-size: .78rem; }
.adj-g-famille dt { color: var(--adj-muted); font-weight: 500; }
.adj-g-famille dd { margin: 0; text-align: right; font-weight: 600; color: var(--adj-dark); }
.adj-g-alerte { color: var(--adj-warning) !important; }
.adj-g-note { font-size: .72rem; color: var(--adj-muted); }

@media (max-width: 1199px) {
    .adj-corps { grid-template-columns: minmax(0, 1fr); }
    .adj-guichet { position: static; max-height: none; order: -1; }
}
@media (max-width: 767px) {
    .adj-hero { padding: 1.25rem 1.1rem; border-radius: 14px; }
    .adj-hero-actions { width: 100%; }
    .adj-hero-actions .adj-btn { flex: 1; }
    .adj-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .adj-famille { grid-template-columns: 44px minmax(0, 1fr); grid-template-areas: "coche qui" "coche badge" ". action"; row-gap: .45rem; }
    .adj-famille > :nth-child(1) { grid-area: coche; align-self: start; }
    .adj-famille > :nth-child(2) { grid-area: qui; }
    .adj-famille > :nth-child(3) { grid-area: badge; justify-self: start; }
    .adj-famille > :nth-child(4) { grid-area: action; justify-self: start; }
    .adj-qui > span { white-space: normal; }
}
</style>
