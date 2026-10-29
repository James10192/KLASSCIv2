<style>
/* Réclamations de notes — namespace rcl-* (élève et personnel).
   Mobile d'abord : une colonne, puis deux au-delà de 992px. Aucun texte
   tronqué : les libellés longs passent à la ligne (overflow-wrap), les
   nombres restent sur une ligne (nowrap). */
.rcl, .rcl-voile { --rcl-p:#0453cb; --rcl-pd:#033a8e; --rcl-txt:#1e293b; --rcl-mut:#64748b; --rcl-bord:#e2e8f0; --rcl-surf:#f8fafc;
    color: var(--rcl-txt); }
.rcl { max-width: 1280px; margin: 0 auto; padding: 0 0 2rem; min-width: 0; }
.rcl *, .rcl *::before, .rcl *::after, .rcl-voile *, .rcl-voile *::before, .rcl-voile *::after { box-sizing: border-box; }
.rcl [x-cloak], .rcl-voile[x-cloak], .rcl-voile [x-cloak] { display: none !important; }

.rcl-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px;
    padding: 1.25rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.rcl-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.rcl-hero-left { display: flex; align-items: flex-start; gap: .9rem; min-width: 0; flex: 1 1 auto; }
.rcl-hero-icon { width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.rcl-hero-titres { min-width: 0; }
.rcl-hero h1 { font-size: clamp(1.2rem, 1rem + 1vw, 1.45rem); font-weight: 700; color: #fff; margin: 0 0 .25rem; }
.rcl-hero p { color: rgba(255,255,255,.78); font-size: .88rem; margin: 0; line-height: 1.5; overflow-wrap: anywhere; }
.rcl-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: .65rem; margin-top: 1.25rem; }
.rcl-kpi { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .75rem .9rem;
    display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
.rcl-kpi--bouton { text-align: left; cursor: pointer; font: inherit; }
.rcl-kpi--lien { color: #fff; text-decoration: none; transition: background .2s ease; }
.rcl-kpi--lien:hover, .rcl-kpi--lien.rcl-kpi--actif { background: rgba(255,255,255,.2); color: #fff; }
.rcl-kpi-val { font-size: clamp(1.15rem, 1rem + .8vw, 1.4rem); font-weight: 700; white-space: nowrap; }
.rcl-kpi-lbl { font-size: .74rem; color: rgba(255,255,255,.72); line-height: 1.3; }

.rcl-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; border-radius: 10px; padding: .6rem 1rem;
    font-size: .85rem; font-weight: 600; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: all .2s ease;
    white-space: nowrap; }
.rcl-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.22); }
.rcl-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
.rcl-btn--primaire { background: var(--rcl-p); color: #fff; width: 100%; }
.rcl-btn--primaire:hover:not(:disabled) { background: var(--rcl-pd); }
.rcl-btn--secondaire { background: #fff; color: var(--rcl-p); border-color: rgba(4,83,203,.3); }
.rcl-btn--secondaire:hover:not(:disabled) { background: rgba(4,83,203,.06); }
.rcl-btn--danger { background: #fff; color: #dc2626; border-color: rgba(220,38,38,.35); }
.rcl-btn--danger:hover:not(:disabled) { background: rgba(220,38,38,.06); }
.rcl-btn:disabled { opacity: .6; cursor: wait; }

.rcl-grille { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; align-items: start; }
@media (min-width: 992px) { .rcl-grille { grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); } }

.rcl-card { background: #fff; border: 1px solid var(--rcl-bord); border-radius: 14px; padding: 1.1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); min-width: 0; }
.rcl-card-head { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: 1rem; }
.rcl-card-head h2 { font-size: 1.02rem; font-weight: 700; margin: 0; }
.rcl-card-head p { font-size: .82rem; color: var(--rcl-mut); margin: .15rem 0 0; line-height: 1.45; }
.rcl-section-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center; color: #fff; font-size: .9rem; flex-shrink: 0; }

.rcl-form { display: grid; gap: 1rem; }
.rcl-champ { display: flex; flex-direction: column; gap: .4rem; min-width: 0; }
.rcl-label { font-size: .8rem; font-weight: 600; color: var(--rcl-txt); }
.rcl-label em { font-style: normal; font-weight: 500; color: var(--rcl-mut); }
.rcl-au-full { display: flex !important; width: 100%; }
.rcl-au-full .au-select-trigger { width: 100%; }
.rcl-textarea, .rcl-input { width: 100%; border: 1px solid var(--rcl-bord); border-radius: 10px; padding: .65rem .8rem; font-size: .9rem;
    color: var(--rcl-txt); background: #fff; resize: vertical; font-family: inherit; }
.rcl-textarea:focus, .rcl-input:focus { outline: none; border-color: var(--rcl-p); box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.rcl-aide { font-size: .74rem; color: var(--rcl-mut); }
.rcl-erreur { font-size: .78rem; color: #dc2626; font-weight: 600; }

.rcl-depot { position: relative; display: flex; align-items: center; justify-content: center; min-height: 120px; border: 2px dashed rgba(4,83,203,.3);
    border-radius: 12px; background: var(--rcl-surf); cursor: pointer; overflow: hidden; transition: border-color .2s ease; }
.rcl-depot:hover { border-color: var(--rcl-p); }
.rcl-depot input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.rcl-depot-texte { display: flex; flex-direction: column; align-items: center; gap: .4rem; color: var(--rcl-p); font-size: .85rem; font-weight: 600;
    padding: 1rem; text-align: center; overflow-wrap: anywhere; }
.rcl-depot-texte i { font-size: 1.4rem; }
.rcl-depot-img { max-width: 100%; max-height: 220px; object-fit: contain; display: block; }

.rcl-vide { display: flex; flex-direction: column; align-items: center; gap: .5rem; padding: 1.5rem 1rem; text-align: center; color: var(--rcl-mut); }
.rcl-vide i { font-size: 1.6rem; color: rgba(4,83,203,.45); }
.rcl-vide p { margin: 0; font-size: .86rem; line-height: 1.5; }

.rcl-liste { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; }
.rcl-item { border: 1px solid var(--rcl-bord); border-radius: 12px; padding: .9rem; display: grid; gap: .55rem; min-width: 0; }
.rcl-item--cliquable { cursor: pointer; transition: border-color .2s ease, box-shadow .2s ease; }
.rcl-item--cliquable:hover, .rcl-item--cliquable:focus-visible { border-color: #c7d4e5; box-shadow: 0 8px 26px rgba(4,83,203,.08); outline: none; }
.rcl-item-haut { display: flex; align-items: flex-start; justify-content: space-between; gap: .6rem; flex-wrap: wrap; }
.rcl-item-titres { display: flex; flex-direction: column; min-width: 0; flex: 1 1 180px; }
.rcl-item-titres strong { font-size: .92rem; overflow-wrap: anywhere; }
.rcl-item-titres span { font-size: .8rem; color: var(--rcl-mut); overflow-wrap: anywhere; }
.rcl-badge { display: inline-flex; align-items: center; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
.rcl-badge--attente { background: rgba(245,158,11,.12); color: #b45309; }
.rcl-badge--info { background: rgba(4,83,203,.1); color: var(--rcl-p); }
.rcl-badge--ok { background: rgba(16,185,129,.12); color: #047857; }
.rcl-badge--neutre { background: #f1f5f9; color: #475569; }
.rcl-notes { display: flex; align-items: baseline; gap: .6rem; flex-wrap: wrap; }
.rcl-note { font-size: 1.15rem; font-weight: 700; white-space: nowrap; display: inline-flex; align-items: baseline; gap: .15rem; }
.rcl-note small { font-size: .75rem; color: var(--rcl-mut); font-weight: 600; }
.rcl-note--apres { color: #047857; gap: .4rem; }
.rcl-note--proposee { color: var(--rcl-p); }
.rcl-pile { display: grid; gap: 1rem; min-width: 0; }
.rcl-pile--serree { gap: .75rem; align-content: start; }
.rcl-meta--sous-titre { margin-top: .3rem; }
.rcl-note--apres i { font-size: .8rem; color: var(--rcl-mut); }
.rcl-motif, .rcl-decision { margin: 0; font-size: .84rem; line-height: 1.5; overflow-wrap: anywhere; }
.rcl-decision { background: var(--rcl-surf); border-left: 3px solid var(--rcl-p); padding: .5rem .7rem; border-radius: 0 8px 8px 0; }
.rcl-item-bas { display: flex; justify-content: space-between; align-items: center; gap: .5rem; flex-wrap: wrap; font-size: .76rem; color: var(--rcl-mut); }
.rcl-item-bas a { color: var(--rcl-p); font-weight: 600; text-decoration: none; white-space: nowrap; }
.rcl-meta { display: flex; flex-wrap: wrap; gap: .35rem .9rem; font-size: .78rem; color: var(--rcl-mut); }
.rcl-meta span { overflow-wrap: anywhere; }

/* Filtres de la page personnel */
.rcl-filtres { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.rcl-filtre { border: 1px solid var(--rcl-bord); background: #fff; color: var(--rcl-txt); border-radius: 999px; padding: .4rem .85rem;
    font-size: .8rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all .2s ease; }
.rcl-filtre--actif { background: var(--rcl-p); border-color: var(--rcl-p); color: #fff; }
.rcl-recherche { flex: 1 1 200px; min-width: 0; }

/* Fenêtre de traitement */
.rcl-voile { position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 1095; display: flex; align-items: flex-end; justify-content: center; }
@media (min-width: 768px) { .rcl-voile { align-items: center; padding: 1.5rem; } }
.rcl-fenetre { background: #fff; width: 100%; max-width: 760px; max-height: 92vh; max-height: 92dvh; overflow-y: auto; border-radius: 18px 18px 0 0;
    padding: 1.1rem 1.1rem calc(1.25rem + env(safe-area-inset-bottom)); display: grid; gap: 1rem; }
@media (min-width: 768px) { .rcl-fenetre { border-radius: 18px; padding: 1.5rem; } }
.rcl-fenetre-tete { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
.rcl-fenetre-tete h3 { margin: 0; font-size: 1.05rem; font-weight: 700; overflow-wrap: anywhere; }
.rcl-fermer { background: none; border: none; color: var(--rcl-mut); font-size: 1.1rem; cursor: pointer; padding: .25rem; }
.rcl-fenetre-corps { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
@media (min-width: 768px) { .rcl-fenetre-corps { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
.rcl-photo { display: block; border-radius: 12px; border: 1px solid var(--rcl-bord); background: var(--rcl-surf); min-height: 160px; overflow: hidden; }
.rcl-photo { display: flex; align-items: center; justify-content: center; max-height: 240px; }
.rcl-photo img { width: 100%; height: 100%; max-height: 240px; object-fit: contain; display: block; }
@media (min-width: 768px) { .rcl-photo, .rcl-photo img { max-height: 380px; } }
.rcl-bloc { border: 1px solid var(--rcl-bord); border-radius: 12px; padding: .85rem; display: grid; gap: .6rem; }
.rcl-bloc h4 { margin: 0; font-size: .86rem; font-weight: 700; display: flex; align-items: center; gap: .4rem; }
.rcl-choix { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .5rem; }
.rcl-choix label { border: 1px solid var(--rcl-bord); border-radius: 10px; padding: .6rem .7rem; font-size: .82rem; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: .45rem; }
.rcl-choix label.rcl-choix--actif { border-color: var(--rcl-p); background: rgba(4,83,203,.06); color: var(--rcl-p); }
.rcl-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.rcl-actions .rcl-btn { flex: 1 1 160px; }
</style>
