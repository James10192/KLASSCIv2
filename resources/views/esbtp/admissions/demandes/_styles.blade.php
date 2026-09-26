{{-- Demandes d'inscription — namespace dmi-*. Hero et KPIs : pattern planning-header. --}}
<style>
.dmi { --dmi-primary: #0453cb; --dmi-primary-d: #033a8e; --dmi-accent: #3b7ddb; --dmi-dark: #0f172a; --dmi-text: #1e293b; --dmi-muted: #64748b; --dmi-line: #e2e8f0; --dmi-surface: #f8fafc; --dmi-soft: #e6eefb; --dmi-success: #047857; --dmi-warning: #9a5b00; --dmi-danger: #b91c1c; color: var(--dmi-text); }
.dmi *, .dmi *::before, .dmi *::after { box-sizing: border-box; }

/* Hero */
.dmi-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 1.75rem 2rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
.dmi-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.dmi-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.dmi-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
.dmi-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.dmi-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .2rem 0 0; }
.dmi-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.dmi-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 40px; padding: .5rem 1rem; border-radius: 10px; font-size: .84rem; font-weight: 700; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease; white-space: nowrap; }
.dmi-btn:focus-visible { outline: 3px solid rgba(4,83,203,.35); outline-offset: 2px; }
.dmi-btn[disabled], .dmi-btn.is-occupe { opacity: .55; cursor: not-allowed; }
.dmi-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.24); }
.dmi-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
.dmi-btn--white { background: #fff; color: var(--dmi-primary); }
.dmi-btn--white:hover { color: var(--dmi-primary-d); box-shadow: 0 4px 14px rgba(15,23,42,.18); }
.dmi-btn--primary { background: var(--dmi-primary); color: #fff; }
.dmi-btn--primary:hover { background: var(--dmi-primary-d); color: #fff; }
.dmi-btn--ghost { background: #fff; color: var(--dmi-primary); border-color: #c9daf6; }
.dmi-btn--ghost:hover { background: var(--dmi-soft); color: var(--dmi-primary-d); }
.dmi-btn--danger { background: #fff; color: var(--dmi-danger); border-color: #f3c1c1; }
.dmi-btn--danger:hover { background: #fef2f2; }
.dmi-btn--sm { min-height: 36px; padding: .4rem .8rem; font-size: .79rem; border-radius: 9px; }
.dmi-btn--bloc { width: 100%; }

.dmi-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; margin-top: 1.4rem; }
.dmi-kpi { display: flex; flex-direction: column; gap: .3rem; padding: .85rem 1rem; border-radius: 12px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); color: #fff; text-align: left; cursor: pointer; font: inherit; transition: background .2s ease, border-color .2s ease; min-width: 0; }
.dmi-kpi:hover { background: rgba(255,255,255,.17); }
.dmi-kpi.is-actif { background: rgba(255,255,255,.24); border-color: rgba(255,255,255,.55); }
.dmi-kpi:focus-visible { outline: 3px solid rgba(255,255,255,.6); outline-offset: 2px; }
.dmi-kpi-valeur { font-size: 1.55rem; font-weight: 800; line-height: 1; white-space: nowrap; font-variant-numeric: tabular-nums; }
.dmi-kpi-libelle { font-size: .78rem; font-weight: 700; color: rgba(255,255,255,.92); }
.dmi-kpi-repere { font-size: .72rem; color: rgba(255,255,255,.72); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Corps : liste + panneau */
.dmi-corps { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 1.1rem; align-items: start; }
@media (min-width: 1600px) { .dmi-corps { grid-template-columns: minmax(0, 1fr) 400px; } }
.dmi-carte { background: #fff; border: 1px solid var(--dmi-line); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.dmi-barre { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; padding: .85rem 1rem; border-bottom: 1px solid #eef2f7; }
.dmi-seg { display: inline-flex; background: #f1f5fb; border-radius: 10px; padding: 4px; gap: 2px; }
.dmi-seg button { border: 0; background: transparent; color: #334155; font: inherit; font-weight: 600; font-size: .8rem; padding: .45rem .8rem; border-radius: 8px; cursor: pointer; min-height: 36px; white-space: nowrap; }
.dmi-seg button.is-actif { background: var(--dmi-primary); color: #fff; font-weight: 700; }
.dmi-seg button:focus-visible { outline: 3px solid rgba(4,83,203,.35); }
.dmi-recherche { flex: 1 1 240px; display: flex; align-items: center; gap: .5rem; background: var(--dmi-surface); border: 1px solid var(--dmi-line); border-radius: 10px; padding: 0 .75rem; min-height: 40px; }
.dmi-recherche:focus-within { border-color: var(--dmi-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.14); background: #fff; }
.dmi-recherche i { color: var(--dmi-muted); font-size: .85rem; }
.dmi-recherche input { flex: 1; border: 0; outline: none; background: transparent; font-size: .85rem; min-width: 0; color: var(--dmi-dark); }
.dmi-recherche kbd { font-size: .68rem; background: #fff; border: 1px solid var(--dmi-line); border-radius: 6px; padding: .05rem .4rem; color: var(--dmi-muted); }
.dmi-puce { border: 1px solid var(--dmi-line); background: #fff; border-radius: 999px; padding: .4rem .8rem; font: inherit; font-size: .77rem; font-weight: 600; color: #334155; cursor: pointer; min-height: 36px; white-space: nowrap; }
.dmi-puce:hover { border-color: var(--dmi-primary); color: var(--dmi-primary); }
.dmi-puce.is-actif { background: var(--dmi-primary); border-color: var(--dmi-primary); color: #fff; }
.dmi-puce--alerte { border-color: #fde7c2; background: #fffaf0; color: var(--dmi-warning); }
.dmi-puce--alerte.is-actif { background: var(--dmi-warning); border-color: var(--dmi-warning); color: #fff; }
.dmi-etat-actif { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .55rem 1.1rem; font-size: .78rem; color: var(--dmi-muted); border-bottom: 1px solid #eef2f7; background: #fbfcfe; }
.dmi-etat-actif strong { color: var(--dmi-dark); }
.dmi-lien { background: none; border: 0; padding: 0; color: var(--dmi-primary); font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
.dmi-lien:hover { color: var(--dmi-primary-d); text-decoration: underline; }

.dmi-entetes, .dmi-ligne { display: grid; grid-template-columns: minmax(0, 1.9fr) minmax(0, 1.5fr) minmax(0, 1.4fr) auto; gap: .75rem; align-items: center; }
.dmi-entetes { padding: .6rem 1.1rem; font-size: .66rem; font-weight: 800; letter-spacing: .08em; color: var(--dmi-muted); text-transform: uppercase; border-bottom: 1px solid #eef2f7; }
.dmi-liste { position: relative; }
.dmi-liste.is-chargement { opacity: .55; pointer-events: none; transition: opacity .2s ease; }
.dmi-ligne { padding: .8rem 1.1rem; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .15s ease; outline: none; }
.dmi-ligne:hover { background: #f8fbff; }
.dmi-ligne:has(:focus-visible) { box-shadow: inset 0 0 0 2px rgba(4,83,203,.45); }
.dmi-nom:focus-visible { outline: none; }
.dmi-ligne.is-selection { background: #f1f6ff; box-shadow: inset 3px 0 0 var(--dmi-primary); }
.dmi-ligne.is-recue { background: #f6faff; }
.dmi-qui { display: flex; gap: .7rem; align-items: center; min-width: 0; }
.dmi-av { width: 38px; height: 38px; border-radius: 50%; background: var(--dmi-soft); color: var(--dmi-primary); font-weight: 800; font-size: .78rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dmi-nom { background: none; border: 0; padding: 0; margin: 0; text-align: left; font-family: inherit; cursor: pointer; font-size: .88rem; font-weight: 700; color: var(--dmi-dark); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.25; }
.dmi-sous { display: flex; gap: .35rem; align-items: center; font-size: .74rem; color: var(--dmi-muted); flex-wrap: wrap; margin-top: .15rem; }
.dmi-type { font-size: .66rem; font-weight: 800; padding: .12rem .45rem; border-radius: 6px; white-space: nowrap; }
.dmi-type--nouvelle { background: var(--dmi-primary); color: #fff; }
.dmi-type--reinscription { background: var(--dmi-soft); color: var(--dmi-primary); border: 1px solid #c9daf6; }
.dmi-col { display: flex; flex-direction: column; gap: .15rem; min-width: 0; font-size: .82rem; }
.dmi-col strong { font-weight: 600; color: var(--dmi-text); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; }
.dmi-col small { font-size: .74rem; color: var(--dmi-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; }
.dmi-rdv--recue strong { color: var(--dmi-success); }
.dmi-rdv--aucun strong { color: var(--dmi-primary); }
.dmi-rdv--retard strong { color: var(--dmi-warning); }
.dmi-statut { font-size: .7rem; font-weight: 700; border-radius: 999px; padding: .15rem .55rem; white-space: nowrap; }
.dmi-statut--convertie { background: #ecfdf5; color: var(--dmi-success); }
.dmi-statut--rejetee { background: #fef2f2; color: var(--dmi-danger); }
.dmi-contact { font-size: .68rem; font-weight: 700; padding: .1rem .45rem; border-radius: 6px; background: #fffaf0; color: var(--dmi-warning); border: 1px solid #fde7c2; white-space: nowrap; }

.dmi-vide { text-align: center; padding: 3rem 1.5rem; color: var(--dmi-muted); }
.dmi-vide i { font-size: 1.6rem; color: var(--dmi-primary); background: var(--dmi-soft); width: 56px; height: 56px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: .75rem; }
.dmi-vide h3 { font-size: 1rem; color: var(--dmi-dark); margin: 0 0 .3rem; font-weight: 700; }
.dmi-vide p { font-size: .84rem; margin: 0 auto .9rem; max-width: 420px; }

/* Panneau du dossier */
.dmi-panneau { position: sticky; top: 84px; max-height: calc(100vh - 100px); overflow: auto; padding: 1.2rem; box-shadow: 0 8px 30px rgba(4,83,203,.08); }
.dmi-panneau-vide { text-align: center; color: var(--dmi-muted); font-size: .84rem; padding: 2.5rem .75rem; }
.dmi-panneau-vide i { font-size: 1.4rem; color: var(--dmi-accent); margin-bottom: .6rem; display: block; }
.dmi-raccourcis { margin: 1rem auto 0; display: grid; grid-template-columns: auto 1fr; gap: .4rem .6rem; text-align: left; max-width: 250px; font-size: .78rem; }
.dmi-raccourcis kbd { font-size: .7rem; background: var(--dmi-surface); border: 1px solid var(--dmi-line); border-radius: 6px; padding: .05rem .45rem; color: var(--dmi-dark); justify-self: start; }
.dmi-p-tete { display: flex; justify-content: space-between; gap: .75rem; align-items: flex-start; }
.dmi-p-type { font-size: .66rem; font-weight: 800; letter-spacing: .08em; color: var(--dmi-primary); text-transform: uppercase; }
.dmi-p-nom { margin: .15rem 0 .1rem; font-size: 1.15rem; font-weight: 800; color: var(--dmi-dark); }
.dmi-p-sous { font-size: .76rem; color: var(--dmi-muted); }
.dmi-mono { font-family: ui-monospace, 'JetBrains Mono', 'Courier New', monospace; font-size: .76rem; }
.dmi-fermer { width: 36px; height: 36px; border-radius: 9px; border: 1px solid var(--dmi-line); background: #fff; color: #334155; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dmi-fermer:hover { background: var(--dmi-surface); }
.dmi-infos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin-top: 1rem; font-size: .78rem; }
.dmi-info { background: var(--dmi-surface); border-radius: 10px; padding: .55rem .7rem; min-width: 0; }
.dmi-info span { color: var(--dmi-muted); display: block; font-size: .7rem; }
.dmi-info strong { display: block; font-weight: 700; color: var(--dmi-dark); margin-top: .1rem; overflow-wrap: anywhere; }
.dmi-info--large { grid-column: 1 / -1; }
.dmi-depot { margin-top: .6rem; font-size: .78rem; border: 1px solid var(--dmi-line); border-radius: 10px; padding: .5rem .7rem; }
.dmi-depot summary { cursor: pointer; font-weight: 700; color: var(--dmi-primary); }
.dmi-depot dl { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .3rem .8rem; margin: .6rem 0 .1rem; }
.dmi-depot dt { color: var(--dmi-muted); font-weight: 600; }
.dmi-depot dd { margin: 0; color: var(--dmi-dark); overflow-wrap: anywhere; }
.dmi-ok { color: var(--dmi-success); font-weight: 700; }
.dmi-section-titre { font-size: .66rem; font-weight: 800; letter-spacing: .08em; color: var(--dmi-muted); text-transform: uppercase; margin: 1.1rem 0 .6rem; }
.dmi-etapes { list-style: none; margin: 0; padding: 0; }
.dmi-etape { display: flex; gap: .7rem; }
.dmi-etape-rail { display: flex; flex-direction: column; align-items: center; }
.dmi-etape-point { width: 12px; height: 12px; border-radius: 50%; margin-top: 3px; flex-shrink: 0; background: #fff; border: 2px solid #c9daf6; }
.dmi-etape.is-fait .dmi-etape-point { background: var(--dmi-primary); border-color: var(--dmi-primary); }
.dmi-etape.is-prochaine .dmi-etape-point { border-color: var(--dmi-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.15); }
.dmi-etape.is-echec .dmi-etape-point { background: #dc2626; border-color: #dc2626; }
.dmi-etape-trait { width: 2px; flex: 1; background: #dbe6f7; min-height: 14px; }
.dmi-etape:last-child .dmi-etape-trait { display: none; }
.dmi-etape-corps { padding-bottom: .75rem; font-size: .8rem; }
.dmi-etape-corps strong { display: block; color: var(--dmi-dark); font-weight: 700; }
.dmi-etape:not(.is-fait) .dmi-etape-corps strong { color: var(--dmi-muted); font-weight: 600; }
.dmi-etape.is-prochaine .dmi-etape-corps strong { color: var(--dmi-primary); font-weight: 800; }
.dmi-etape-corps small { color: var(--dmi-muted); font-size: .74rem; }
.dmi-encart { display: flex; gap: .6rem; align-items: flex-start; background: #f1f6ff; border: 1px solid #c9daf6; border-radius: 10px; padding: .7rem .8rem; font-size: .8rem; margin-top: .75rem; }
.dmi-encart i { color: var(--dmi-primary); margin-top: .15rem; }
.dmi-encart--alerte { background: #fffaf0; border-color: #fde7c2; }
.dmi-encart--alerte i { color: var(--dmi-warning); }
.dmi-message { font-size: .8rem; background: var(--dmi-surface); border-radius: 10px; padding: .65rem .75rem; color: #334155; white-space: pre-line; }
.dmi-p-actions { display: flex; flex-direction: column; gap: .5rem; margin-top: 1.1rem; padding-top: 1rem; border-top: 1px solid #eef2f7; }
.dmi-p-actions-rangee { display: flex; gap: .5rem; }
.dmi-p-actions-rangee > * { flex: 1; }
.dmi-p-note { font-size: .74rem; color: var(--dmi-muted); }

/* Fenetres */
.dmi-voile { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 1060; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.dmi-fenetre { background: #fff; border-radius: 16px; width: 100%; max-width: 520px; max-height: calc(100vh - 2rem); display: flex; flex-direction: column; box-shadow: 0 24px 60px rgba(15,23,42,.3); }
.dmi-fenetre--large { max-width: 1180px; }
.dmi-f-tete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #eef2f7; }
.dmi-f-tete h2 { margin: .1rem 0 0; font-size: 1.15rem; font-weight: 800; color: var(--dmi-dark); }
.dmi-f-corps { padding: 1.2rem 1.4rem; overflow: auto; }
.dmi-f-pied { display: flex; justify-content: flex-end; gap: .5rem; padding: .9rem 1.4rem; border-top: 1px solid #eef2f7; flex-wrap: wrap; }
.dmi-champ { display: flex; flex-direction: column; gap: .3rem; font-size: .78rem; font-weight: 600; color: #334155; min-width: 0; }
.dmi-champ input, .dmi-champ textarea { border: 1px solid #cbd5e1; border-radius: 10px; padding: .55rem .7rem; font: inherit; font-size: .86rem; font-weight: 500; color: var(--dmi-dark); min-height: 42px; width: 100%; background: #fff; }
.dmi-champ textarea { min-height: 96px; resize: vertical; }
.dmi-champ input:focus, .dmi-champ textarea:focus { outline: none; border-color: var(--dmi-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.14); }
.dmi-champ.is-erreur input, .dmi-champ.is-erreur textarea { border-color: #dc2626; }
.dmi-champ-aide { font-size: .72rem; font-weight: 500; color: var(--dmi-muted); }
.dmi-champ-erreur { font-size: .72rem; font-weight: 600; color: var(--dmi-danger); }
.dmi-grille { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
.dmi-choix { display: flex; gap: .4rem; flex-wrap: wrap; }
.dmi-choix button { border: 1px solid #cbd5e1; background: #fff; border-radius: 10px; padding: .5rem .85rem; font: inherit; font-size: .8rem; font-weight: 600; color: #334155; cursor: pointer; min-height: 40px; }
.dmi-choix button.is-actif { border-color: var(--dmi-primary); background: var(--dmi-soft); color: var(--dmi-primary); box-shadow: inset 0 0 0 1px var(--dmi-primary); }
.dmi-case { display: flex; gap: .55rem; align-items: flex-start; font-size: .8rem; color: #334155; cursor: pointer; }
.dmi-case input { width: 18px; height: 18px; margin-top: 1px; accent-color: var(--dmi-primary); flex-shrink: 0; }

/* Selecteur de classe maison (liste filtrable, places lisibles) */
.dmi-picker { position: relative; }
.dmi-picker-bouton { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: .5rem; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; padding: .55rem .75rem; font: inherit; font-size: .88rem; font-weight: 700; color: var(--dmi-dark); cursor: pointer; min-height: 44px; text-align: left; }
.dmi-picker-bouton:focus-visible, .dmi-picker.is-ouvert .dmi-picker-bouton { border-color: var(--dmi-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.14); outline: none; }
.dmi-picker-menu { position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 30; background: #fff; border: 1px solid var(--dmi-line); border-radius: 12px; box-shadow: 0 16px 40px rgba(15,23,42,.16); padding: .5rem; max-height: 320px; display: flex; flex-direction: column; }
.dmi-picker-menu input { border: 1px solid var(--dmi-line); border-radius: 8px; padding: .45rem .6rem; font: inherit; font-size: .82rem; margin-bottom: .4rem; }
.dmi-picker-liste { overflow: auto; }
/* Dans une fenetre, le corps defile (overflow:auto) : un menu en surimpression y serait
   rogne et masquerait le champ suivant. Il s ouvre donc dans le flux et pousse la suite. */
.dmi-f-corps .dmi-picker-menu { position: static; margin-top: .4rem; box-shadow: 0 4px 14px rgba(15,23,42,.08); max-height: 280px; }
.dmi-picker-option { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: .6rem; border: 0; background: none; border-radius: 8px; padding: .5rem .55rem; font: inherit; text-align: left; cursor: pointer; }
.dmi-picker-option:hover, .dmi-picker-option.is-focus { background: #f1f6ff; }
.dmi-picker-option[disabled] { opacity: .5; cursor: not-allowed; }
.dmi-picker-option strong { font-size: .84rem; color: var(--dmi-dark); display: block; }
.dmi-picker-option small { font-size: .72rem; color: var(--dmi-muted); }
.dmi-voeu { font-size: .64rem; font-weight: 800; color: var(--dmi-primary); background: var(--dmi-soft); border-radius: 6px; padding: .1rem .4rem; margin-left: .35rem; }
.dmi-jauge { display: flex; align-items: center; gap: .45rem; font-size: .74rem; color: var(--dmi-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
.dmi-jauge-barre { width: 64px; height: 6px; border-radius: 99px; background: #e2e8f0; overflow: hidden; }
.dmi-jauge-barre span { display: block; height: 100%; background: var(--dmi-primary); }
.dmi-jauge--pleine .dmi-jauge-barre span { background: #dc2626; }
.dmi-jauge--presque .dmi-jauge-barre span { background: #f59e0b; }

/* Fenetre « Accepter et inscrire » */
.dmi-ins-etapes { display: flex; align-items: center; gap: .5rem; list-style: none; margin: 0; padding: 0; font-size: .78rem; font-weight: 700; color: var(--dmi-muted); }
.dmi-ins-etapes li { display: flex; align-items: center; gap: .35rem; white-space: nowrap; }
.dmi-ins-etapes .rond { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; font-size: .66rem; }
.dmi-ins-etapes li.is-ok { color: var(--dmi-success); }
.dmi-ins-etapes li.is-ok .rond { border-color: var(--dmi-success); background: #ecfdf5; }
.dmi-ins-etapes .sep { width: 22px; height: 2px; background: #e2e8f0; }
.dmi-ins { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1.2rem; }
.dmi-ins-bloc { border: 1px solid var(--dmi-line); border-radius: 12px; padding: 1rem; }
.dmi-ins-bloc + .dmi-ins-bloc { margin-top: .9rem; }
.dmi-ins-bloc h3 { font-size: .9rem; font-weight: 800; margin: 0 0 .75rem; color: var(--dmi-dark); display: flex; justify-content: space-between; gap: .75rem; align-items: baseline; flex-wrap: wrap; }
.dmi-ins-bloc h3 small { font-size: .74rem; font-weight: 600; color: var(--dmi-muted); }
.dmi-ins-bloc--alerte { border-color: #fde7c2; background: #fffcf5; }
.dmi-doublon { display: flex; align-items: center; gap: .7rem; padding: .6rem .7rem; border-radius: 10px; background: #fff; border: 1px solid var(--dmi-line); flex-wrap: wrap; }
.dmi-doublon + .dmi-doublon { margin-top: .5rem; }
.dmi-doublon-qui { flex: 1; min-width: 180px; font-size: .8rem; }
.dmi-doublon-qui strong { display: block; color: var(--dmi-dark); }
.dmi-doublon-qui small { color: var(--dmi-muted); }
.dmi-apercu { background: var(--dmi-surface); border: 1px solid var(--dmi-line); border-radius: 12px; padding: 1rem; position: sticky; top: 0; align-self: start; }
.dmi-apercu h3 { font-size: .66rem; font-weight: 800; letter-spacing: .08em; color: var(--dmi-muted); margin: 0 0 .75rem; text-transform: uppercase; }
.dmi-apercu dl { display: grid; grid-template-columns: auto 1fr; gap: .35rem .75rem; font-size: .8rem; margin: .8rem 0; }
.dmi-apercu dt { color: var(--dmi-muted); font-weight: 500; }
.dmi-apercu dd { margin: 0; font-weight: 700; color: var(--dmi-dark); text-align: right; overflow-wrap: anywhere; }
.dmi-frais { background: #fff; border: 1px solid var(--dmi-line); border-radius: 10px; padding: .7rem .8rem; font-size: .8rem; }
.dmi-frais-ligne { display: flex; justify-content: space-between; gap: .75rem; padding: .15rem 0; }
.dmi-frais-ligne span:last-child { font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums; }
.dmi-frais-total { border-top: 1px solid var(--dmi-line); margin-top: .35rem; padding-top: .45rem; font-weight: 800; color: var(--dmi-dark); }
.dmi-suites { font-size: .76rem; color: #334155; margin: .8rem 0; padding: 0 0 0 1rem; }
.dmi-suites li { margin: .15rem 0; }

@media (max-width: 1199px) {
    .dmi-corps { grid-template-columns: minmax(0, 1fr); }
    .dmi-panneau { position: fixed; top: 0; right: 0; bottom: 0; width: min(440px, 100%); max-height: none; border-radius: 0; z-index: 1050; transform: translateX(100%); transition: transform .25s ease; }
    .dmi-panneau.is-ouvert { transform: translateX(0); }
    .dmi-panneau.is-vide { display: none; }
    .dmi-ins { grid-template-columns: minmax(0, 1fr); }
    .dmi-apercu { position: static; }
}
@media (max-width: 991px) {
    .dmi-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 767px) {
    .dmi-hero { padding: 1.25rem 1.1rem; border-radius: 14px; }
    .dmi-hero-actions { width: 100%; }
    .dmi-hero-actions .dmi-btn { flex: 1; }
    .dmi-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .dmi-entetes { display: none; }
    .dmi-ligne { grid-template-columns: minmax(0, 1fr) auto; grid-template-areas: "qui action" "parcours parcours" "rdv rdv"; row-gap: .4rem; }
    .dmi-ligne > :nth-child(1) { grid-area: qui; }
    .dmi-ligne > :nth-child(2) { grid-area: parcours; padding-left: 48px; }
    .dmi-ligne > :nth-child(3) { grid-area: rdv; padding-left: 48px; }
    .dmi-ligne > :nth-child(4) { grid-area: action; }
    .dmi-barre .dmi-seg { width: 100%; }
    .dmi-barre .dmi-seg button { flex: 1; }
    .dmi-grille { grid-template-columns: minmax(0, 1fr); }
    .dmi-voile { padding: 0; align-items: flex-end; }
    .dmi-fenetre { max-height: 94vh; border-radius: 16px 16px 0 0; }
    .dmi-f-pied .dmi-btn { flex: 1; }
    .dmi-ins-etapes .libelle { display: none; }
    .dmi-f-tete { padding: .9rem 1rem; gap: .6rem; }
    .dmi-f-tete h2 { font-size: 1rem; }
    .dmi-f-tete .dmi-p-type > span { display: none; }
    .dmi-f-corps { padding: 1rem; }
    /* La barre de navigation du bas (shell mobile) couvrirait les actions du panneau. */
    .dmi-panneau { padding-bottom: calc(1.2rem + 96px); }
    .dmi-f-pied { padding-bottom: calc(.9rem + env(safe-area-inset-bottom, 0px)); }
    /* Une fenetre ouverte occupe l'ecran : la barre du bas masquerait ses derniers boutons. */
    body:has(.dmi-voile:not([x-cloak]):not([style*="none"])) :is(.m-bottomnav, .ast-launcher) { display: none !important; }
}
</style>
