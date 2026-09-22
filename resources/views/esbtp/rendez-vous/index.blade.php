@extends('layouts.app')

@section('title', 'Rendez-vous d\'inscription - KLASSCI')

@push('styles')
<style>
/* ===== Rendez-vous d'inscription — namespace rdv-* ===== */
.rdv-page { --rdv-primary: #0453cb; --rdv-primary-d: #033a8e; --rdv-accent: #3b7ddb; --rdv-dark: #0f172a; --rdv-text: #1e293b; --rdv-muted: #64748b; --rdv-line: #e2e8f0; --rdv-surface: #f8fafc; --rdv-succes: #10b981; --rdv-attente: #f59e0b; --rdv-echec: #dc2626; color: var(--rdv-text); }

/* Hero (modele planning-header) — sans overflow ni transform : rien n'y doit etre coupe */
.rdv-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.rdv-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.rdv-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.rdv-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
.rdv-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.rdv-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .25rem 0 0; }
.rdv-hero-actions { display: flex; gap: .6rem; flex-wrap: wrap; }
.rdv-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.rdv-kpi { flex: 1 1 170px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; min-width: 0; }
.rdv-kpi--alerte { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.35); }
.rdv-kpi-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .9rem; }
.rdv-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1.15; font-variant-numeric: tabular-nums; }
.rdv-kpi-sur { font-size: .85rem; font-weight: 600; color: rgba(255,255,255,.6); }
.rdv-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .15rem; }

/* Boutons */
.rdv-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .55rem 1rem; font-size: .82rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: background .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease; white-space: nowrap; line-height: 1.2; }
.rdv-btn:focus-visible, .rdv-nav-btn:focus-visible, .rdv-switch:focus-visible, .rdv-lien:focus-visible { outline: 3px solid rgba(4,83,203,.35); outline-offset: 2px; }
.rdv-btn[disabled] { opacity: .6; cursor: wait; }
.rdv-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.22); }
.rdv-btn--glass:hover { background: rgba(255,255,255,.24); }
.rdv-btn--white { background: #fff; color: var(--rdv-primary); }
.rdv-btn--white:hover { box-shadow: 0 4px 14px rgba(15,23,42,.18); }
.rdv-btn--primary { background: var(--rdv-primary); color: #fff; }
.rdv-btn--primary:hover { background: var(--rdv-primary-d); }
.rdv-btn--ghost { background: #fff; color: var(--rdv-primary); border-color: #c7d4e5; }
.rdv-btn--ghost:hover { background: rgba(4,83,203,.06); border-color: var(--rdv-primary); }
.rdv-btn--danger { background: var(--rdv-echec); color: #fff; }
.rdv-btn--sm { padding: .4rem .75rem; font-size: .78rem; }
.rdv-lien { background: none; border: none; padding: 0; color: var(--rdv-primary); font-weight: 600; font-size: .8rem; cursor: pointer; white-space: nowrap; }
.rdv-lien:hover { text-decoration: underline; }

/* Cartes et en-tetes de section */
.rdv-card { background: #fff; border: 1px solid var(--rdv-line); border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.rdv-section-head { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: 1rem; }
.rdv-section-head h2 { font-size: 1rem; font-weight: 700; color: var(--rdv-dark); margin: 0; }
.rdv-section-head p { font-size: .82rem; color: var(--rdv-muted); margin: .15rem 0 0; }
.rdv-section-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .95rem; }
.rdv-section-icon--alerte { background: linear-gradient(135deg, #d97706, #f59e0b); }
.rdv-note { font-size: .78rem; color: var(--rdv-muted); margin: .75rem 0 0; display: flex; gap: .4rem; align-items: baseline; }

/* Etat de la chaine */
.rdv-chaine--ok { display: flex; align-items: center; gap: .6rem; padding: .8rem 1.1rem; border-radius: 12px; background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.25); color: #065f46; font-size: .86rem; margin-bottom: 1rem; }
.rdv-chaine--ok i { color: var(--rdv-succes); }
.rdv-chaine--ko { border-left: 4px solid var(--rdv-attente); }
.rdv-maillons { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .5rem; }
.rdv-maillon { display: flex; align-items: center; gap: .75rem; padding: .65rem .8rem; border-radius: 10px; }
.rdv-maillon--ko { background: #fff7ed; border: 1px solid #fed7aa; }
.rdv-maillon--ok { background: var(--rdv-surface); color: var(--rdv-muted); }
.rdv-maillon-icone { width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .8rem; }
.rdv-maillon--ko .rdv-maillon-icone { background: rgba(245,158,11,.16); color: #b45309; }
.rdv-maillon--ok .rdv-maillon-icone { background: rgba(16,185,129,.12); color: var(--rdv-succes); }
.rdv-maillon-texte { flex: 1; min-width: 0; display: flex; flex-direction: column; font-size: .84rem; }
.rdv-maillon-texte strong { color: var(--rdv-text); font-weight: 600; }
.rdv-maillon--ok .rdv-maillon-texte strong { color: var(--rdv-muted); font-weight: 500; }
.rdv-maillon-texte span { color: #7c4a03; font-size: .8rem; margin-top: .1rem; }

/* Convocations */
.rdv-conv-grille { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .6rem; }
.rdv-conv { border-radius: 12px; padding: .75rem .9rem; border: 1px solid var(--rdv-line); font-size: .78rem; color: var(--rdv-muted); background: var(--rdv-surface); }
.rdv-conv span { display: block; font-size: 1.3rem; font-weight: 700; color: var(--rdv-dark); font-variant-numeric: tabular-nums; }
.rdv-conv--succes { border-left: 3px solid var(--rdv-succes); }
.rdv-conv--attente { border-left: 3px solid var(--rdv-attente); }
.rdv-conv--echec { border-left: 3px solid var(--rdv-echec); }
.rdv-conv--neutre, .rdv-conv--inconnu { border-left: 3px solid #94a3b8; }
.rdv-conv-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }

/* Progression d'envoi */
.rdv-envoi { display: none; margin-bottom: 1rem; }
.rdv-envoi.is-actif { display: block; }
.rdv-envoi-tete { display: flex; justify-content: space-between; gap: 1rem; font-size: .85rem; margin-bottom: .55rem; flex-wrap: wrap; }
.rdv-envoi-barre { height: 10px; border-radius: 999px; background: #e8eef8; }
.rdv-envoi-barre span { display: block; height: 100%; width: 0; border-radius: 999px; background: linear-gradient(90deg, #0453cb, #3b7ddb); transition: width .3s ease; }

/* Navigation de semaine */
.rdv-semaine { display: flex; align-items: center; gap: .6rem; margin: 1.5rem 0 1rem; flex-wrap: wrap; }
.rdv-semaine-titre { flex: 1; min-width: 180px; font-size: .95rem; color: var(--rdv-dark); }
.rdv-nav-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--rdv-line); background: #fff; color: var(--rdv-primary); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: border-color .2s ease, background .2s ease; }
.rdv-nav-btn:hover { border-color: var(--rdv-primary); background: rgba(4,83,203,.05); }
.rdv-tableau.is-chargement { opacity: .55; pointer-events: none; transition: opacity .2s ease; }

/* Etat vide */
.rdv-vide { text-align: center; padding: 2.5rem 1.5rem; }
.rdv-vide-icone { width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 18px; background: rgba(4,83,203,.08); color: var(--rdv-primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.rdv-vide h3 { font-size: 1.05rem; font-weight: 700; color: var(--rdv-dark); margin: 0 0 .35rem; }
.rdv-vide p { color: var(--rdv-muted); font-size: .88rem; max-width: 520px; margin: 0 auto 1.1rem; }

/* Jours et creneaux */
.rdv-jour-vide { display: flex; gap: .75rem; align-items: baseline; font-size: .8rem; color: #94a3b8; padding: .35rem .25rem .75rem; }
.rdv-jour-vide span { font-weight: 600; color: var(--rdv-muted); min-width: 150px; }
.rdv-jour--auj { border-color: rgba(4,83,203,.35); box-shadow: 0 0 0 3px rgba(4,83,203,.06); }
.rdv-jour-tete { display: flex; justify-content: space-between; align-items: baseline; gap: .75rem; flex-wrap: wrap; margin-bottom: .85rem; }
.rdv-jour-tete h3 { font-size: .98rem; font-weight: 700; color: var(--rdv-dark); margin: 0; display: flex; align-items: center; gap: .5rem; }
.rdv-jour-resume { font-size: .8rem; color: var(--rdv-muted); }
.rdv-jour-resume strong { color: var(--rdv-text); }
.rdv-puce { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; background: rgba(4,83,203,.1); color: var(--rdv-primary); padding: .15rem .5rem; border-radius: 999px; }
.rdv-slots { display: flex; flex-direction: column; gap: .45rem; }
.rdv-slot { border: 1px solid var(--rdv-line); border-radius: 10px; background: #fff; transition: border-color .2s ease, box-shadow .2s ease; }
.rdv-slot:hover { border-color: #c7d4e5; box-shadow: 0 4px 14px rgba(4,83,203,.06); }
.rdv-slot--ferme { background: var(--rdv-surface); }
.rdv-slot-ligne { display: flex; align-items: center; gap: .85rem; padding: .6rem .85rem; flex-wrap: wrap; }
.rdv-heure { font-variant-numeric: tabular-nums; font-weight: 700; color: var(--rdv-dark); min-width: 110px; font-size: .88rem; }
.rdv-slot--ferme .rdv-heure { color: var(--rdv-muted); }
.rdv-jauge { flex: 1 1 120px; max-width: 260px; height: 8px; border-radius: 999px; background: #e8eef8; }
.rdv-jauge span { display: block; height: 100%; border-radius: 999px; background: var(--rdv-accent); }
.rdv-slot--complet .rdv-jauge span { background: var(--rdv-primary); }
.rdv-slot--ferme .rdv-jauge span { background: #94a3b8; }
.rdv-slot-compte { font-size: .8rem; color: var(--rdv-muted); font-variant-numeric: tabular-nums; min-width: 52px; }
.rdv-slot-compte strong { color: var(--rdv-text); }
.rdv-etat { font-size: .7rem; font-weight: 700; padding: .2rem .6rem; border-radius: 999px; text-transform: uppercase; letter-spacing: .3px; white-space: nowrap; }
.rdv-etat--ouvert { background: rgba(4,83,203,.1); color: var(--rdv-primary); }
.rdv-etat--complet { background: rgba(245,158,11,.14); color: #b45309; }
.rdv-etat--ferme { background: #e2e8f0; color: #475569; }
.rdv-hero .rdv-etat--ouvert { background: rgba(255,255,255,.18); color: #fff; }

/* Interrupteur ouvrir / fermer un creneau */
.rdv-switch { margin-left: auto; position: relative; width: 42px; height: 24px; border-radius: 999px; border: none; padding: 0; cursor: pointer; background: #cbd5e1; transition: background .2s ease; flex-shrink: 0; }
.rdv-switch[aria-checked="true"] { background: var(--rdv-primary); }
.rdv-switch-rond { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(15,23,42,.25); transition: left .2s ease; }
.rdv-switch[aria-checked="true"] .rdv-switch-rond { left: 21px; }
.rdv-switch[disabled] { opacity: .5; cursor: wait; }

/* Reservations d'un creneau */
.rdv-resas { border-top: 1px dashed var(--rdv-line); }
.rdv-resas summary { list-style: none; cursor: pointer; padding: .5rem .85rem; font-size: .8rem; font-weight: 600; color: var(--rdv-primary); display: flex; align-items: center; gap: .45rem; }
.rdv-resas summary::-webkit-details-marker { display: none; }
.rdv-resas summary i { font-size: .65rem; transition: transform .2s ease; }
.rdv-resas[open] summary i { transform: rotate(90deg); }
.rdv-resas ul { list-style: none; margin: 0; padding: 0 .85rem .7rem; display: flex; flex-direction: column; gap: .35rem; }
.rdv-resa { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .5rem .7rem; border-radius: 8px; background: var(--rdv-surface); flex-wrap: wrap; }
.rdv-resa-qui { display: flex; flex-direction: column; min-width: 0; font-size: .82rem; }
.rdv-resa-qui span { color: var(--rdv-muted); font-size: .76rem; overflow-wrap: anywhere; }
.rdv-resa-conv { display: flex; flex-direction: column; align-items: flex-end; gap: .15rem; text-align: right; }
.rdv-resa-conv small { font-size: .72rem; color: var(--rdv-muted); }
.rdv-resa-erreur { color: #b91c1c !important; max-width: 320px; }
.rdv-badge { font-size: .7rem; font-weight: 600; padding: .18rem .55rem; border-radius: 999px; white-space: nowrap; }
.rdv-badge--succes { background: rgba(16,185,129,.12); color: #047857; }
.rdv-badge--attente { background: rgba(245,158,11,.14); color: #b45309; }
.rdv-badge--echec { background: rgba(220,38,38,.1); color: #b91c1c; }
.rdv-badge--neutre { background: #e2e8f0; color: #475569; }
.rdv-badge--inconnu { background: rgba(4,83,203,.08); color: #3b5b8f; }

/* Reglages */
.rdv-reglages { padding: 0; }
.rdv-reglages > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: .85rem; padding: 1rem 1.5rem; flex-wrap: wrap; }
.rdv-reglages > summary::-webkit-details-marker { display: none; }
.rdv-reglages-titre { flex: 1; min-width: 180px; display: flex; flex-direction: column; }
.rdv-reglages-titre strong { font-size: 1rem; color: var(--rdv-dark); }
.rdv-reglages-titre span { font-size: .8rem; color: var(--rdv-muted); }
.rdv-reglages-caret { color: #94a3b8; transition: transform .2s ease; }
.rdv-reglages[open] .rdv-reglages-caret { transform: rotate(180deg); color: var(--rdv-primary); }
.rdv-form { padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--rdv-line); }
.rdv-form fieldset { border: none; margin: 1.25rem 0 0; padding: 0; min-width: 0; }
.rdv-form legend { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--rdv-muted); margin-bottom: .6rem; }
.rdv-grille { display: grid; gap: .85rem; }
.rdv-grille--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.rdv-grille--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.rdv-champ { display: flex; flex-direction: column; gap: .35rem; font-size: .8rem; font-weight: 600; color: var(--rdv-text); margin: 0; min-width: 0; }
.rdv-grille + .rdv-champ { margin-top: .85rem; }
.rdv-champ em { font-style: normal; font-weight: 500; color: #94a3b8; }
.rdv-champ input { width: 100%; min-width: 0; border: 1px solid #cbd5e1; border-radius: 10px; padding: .55rem .75rem; font-size: .88rem; color: var(--rdv-text); background: #fff; transition: border-color .2s ease, box-shadow .2s ease; }
.rdv-champ input:focus { outline: none; border-color: var(--rdv-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.rdv-suffixe { display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; transition: border-color .2s ease, box-shadow .2s ease; }
.rdv-suffixe:focus-within { border-color: var(--rdv-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.rdv-suffixe input { border: none; box-shadow: none !important; }
.rdv-suffixe em { padding: 0 .75rem 0 0; font-size: .78rem; white-space: nowrap; }
.rdv-jours-choix { display: flex; flex-wrap: wrap; gap: .45rem; }
.rdv-chip { position: relative; margin: 0; cursor: pointer; }
.rdv-chip input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.rdv-chip span { display: inline-block; min-width: 54px; text-align: center; padding: .45rem .8rem; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; font-size: .8rem; font-weight: 600; color: var(--rdv-muted); transition: all .2s ease; }
.rdv-chip input:checked + span { background: var(--rdv-primary); border-color: var(--rdv-primary); color: #fff; }
.rdv-chip input:focus-visible + span { box-shadow: 0 0 0 3px rgba(4,83,203,.25); }
.rdv-bascule { display: flex; align-items: flex-start; gap: .8rem; margin: 1.5rem 0 0; padding: 1rem; border-radius: 12px; background: var(--rdv-surface); border: 1px solid var(--rdv-line); cursor: pointer; }
.rdv-bascule input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.rdv-bascule-piste { position: relative; width: 44px; height: 24px; border-radius: 999px; background: #cbd5e1; flex-shrink: 0; transition: background .2s ease; margin-top: 2px; }
.rdv-bascule-piste span { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(15,23,42,.25); transition: left .2s ease; }
.rdv-bascule input:checked + .rdv-bascule-piste { background: var(--rdv-primary); }
.rdv-bascule input:checked + .rdv-bascule-piste span { left: 23px; }
.rdv-bascule input:focus-visible + .rdv-bascule-piste { box-shadow: 0 0 0 3px rgba(4,83,203,.25); }
.rdv-bascule-texte { display: flex; flex-direction: column; font-size: .86rem; }
.rdv-bascule-texte span { font-size: .78rem; color: var(--rdv-muted); margin-top: .15rem; }
.rdv-form-pied { display: flex; align-items: center; gap: 1rem; margin-top: 1.25rem; flex-wrap: wrap; }
.rdv-form-pied .rdv-note { margin: 0; }

/* Confirmation */
.rdv-modale { position: fixed; inset: 0; z-index: 99990; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(15,23,42,.45); }
.rdv-modale.is-ouverte { display: flex; }
.rdv-modale-boite { background: #fff; border-radius: 16px; max-width: 460px; width: 100%; padding: 1.5rem; box-shadow: 0 20px 50px rgba(15,23,42,.3); }
.rdv-modale-boite h2 { font-size: 1.05rem; font-weight: 700; color: var(--rdv-dark); margin: 0 0 .5rem; display: flex; gap: .6rem; align-items: center; }
.rdv-modale-boite p { color: var(--rdv-muted); font-size: .88rem; margin: 0 0 1.25rem; }
.rdv-modale-pied { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }

@media (max-width: 992px) {
    .rdv-grille--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 576px) {
    .rdv-hero { padding: 1.25rem 1.1rem 1.1rem; border-radius: 14px; }
    .rdv-hero h1 { font-size: 1.2rem; }
    .rdv-hero-icon { width: 44px; height: 44px; font-size: 1.1rem; }
    .rdv-hero-actions, .rdv-hero-actions .rdv-btn { width: 100%; }
    .rdv-hero-actions .rdv-btn { justify-content: center; }
    .rdv-kpi { flex: 1 1 calc(50% - .75rem); padding: .75rem; }
    .rdv-kpi-icon { display: none; }
    .rdv-card { padding: 1rem; }
    .rdv-reglages > summary { padding: 1rem; }
    .rdv-form { padding: 0 1rem 1rem; }
    .rdv-grille--2, .rdv-grille--4 { grid-template-columns: 1fr; }
    .rdv-slot-ligne { gap: .5rem; padding: .6rem .7rem; }
    .rdv-heure { min-width: 0; font-size: .84rem; }
    .rdv-slot-compte { min-width: 0; }
    .rdv-jauge { order: 5; flex-basis: 100%; max-width: none; }
    .rdv-resa-conv { align-items: flex-start; text-align: left; }
    .rdv-jour-vide span { min-width: 0; }
}
</style>
@endpush

@section('content')
<div class="container-fluid rdv-page" data-rdv-page
     data-url-index="{{ route('esbtp.rendez-vous.index') }}"
     data-url-generer="{{ route('esbtp.rendez-vous.generer') }}"
     data-url-placer="{{ route('esbtp.rendez-vous.placer') }}"
     data-url-envoyer="{{ route('esbtp.rendez-vous.convocations.envoyer') }}"
     data-url-remettre="{{ route('esbtp.rendez-vous.convocations.remettre') }}"
     data-debut="{{ $debut->toDateString() }}">

    <div class="rdv-hero">
        <div class="rdv-hero-top">
            <div class="rdv-hero-left">
                <div class="rdv-hero-icon"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <h1>Rendez-vous d'inscription</h1>
                    <p>Qui vient au guichet, quand, et si sa convocation est bien partie.</p>
                </div>
            </div>
            <div class="rdv-hero-actions">
                @if($peutConfigurer)
                    <button type="button" class="rdv-btn rdv-btn--glass" data-rdv-ouvrir-reglages><i class="fas fa-sliders"></i>Réglages</button>
                @endif
                @if($peutGerer)
                    <button type="button" class="rdv-btn rdv-btn--glass" data-rdv-action="generer"><i class="fas fa-calendar-plus"></i>Générer les créneaux</button>
                    <button type="button" class="rdv-btn rdv-btn--white" data-rdv-action="placer"
                            data-confirm="Les candidatures et demandes en attente vont être placées sur les premiers créneaux libres, puis leur convocation partira par e-mail.">
                        <i class="fas fa-envelope-open-text"></i>Placer et convoquer
                    </button>
                @endif
            </div>
        </div>
        <div id="rdv-kpis">@include('esbtp.rendez-vous.partials._kpis')</div>
    </div>

    <section class="rdv-card rdv-envoi" id="rdv-envoi" aria-live="polite">
        <div class="rdv-envoi-tete">
            <strong><i class="fas fa-paper-plane"></i> <span data-rdv-envoi-titre>Envoi des convocations…</span></strong>
            <span data-rdv-envoi-compte></span>
        </div>
        <div class="rdv-envoi-barre"><span data-rdv-envoi-barre></span></div>
    </section>

    <div id="rdv-chaine">@include('esbtp.rendez-vous.partials._chaine')</div>

    @if($peutConfigurer)
        @include('esbtp.rendez-vous.partials._reglages')
    @endif

    <div id="rdv-tableau" class="rdv-tableau">@include('esbtp.rendez-vous.partials._tableau')</div>

    <div class="rdv-modale" id="rdv-modale" role="dialog" aria-modal="true" aria-labelledby="rdv-modale-titre">
        <div class="rdv-modale-boite">
            <h2 id="rdv-modale-titre"><span class="rdv-section-icon"><i class="fas fa-circle-question"></i></span>Confirmer</h2>
            <p data-rdv-modale-texte></p>
            <div class="rdv-modale-pied">
                <button type="button" class="rdv-btn rdv-btn--ghost" data-rdv-modale="non">Annuler</button>
                <button type="button" class="rdv-btn rdv-btn--primary" data-rdv-modale="oui">Confirmer</button>
            </div>
        </div>
    </div>
</div>

@include('partials._klassci_toast')
@endsection

@push('scripts')
<script>
(function () {
    if (window.__rdvPageInit) return;
    window.__rdvPageInit = true;

    const page = document.querySelector('[data-rdv-page]');
    if (!page) return;
    const jeton = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function notifier(type, message) {
        const div = document.createElement('div');
        div.textContent = message;
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: div.innerHTML } }));
    }

    async function envoyerPost(url, corps) {
        const reponse = await fetch(url, {
            method: 'POST',
            headers: Object.assign(
                { 'X-CSRF-TOKEN': jeton, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                corps instanceof FormData ? {} : { 'Content-Type': 'application/json' }
            ),
            body: corps instanceof FormData ? corps : JSON.stringify(corps || {}),
        });
        const donnees = await reponse.json().catch(() => ({}));
        if (!reponse.ok) {
            const erreur = new Error(donnees.message || (reponse.status === 429 ? 'Trop de demandes, patientez une minute.' : 'Action impossible (erreur ' + reponse.status + ').'));
            erreur.donnees = donnees;
            erreur.statut = reponse.status;
            throw erreur;
        }
        return donnees;
    }

    async function rafraichir(debut, historique) {
        const tableau = document.getElementById('rdv-tableau');
        const cible = debut || page.dataset.debut;
        tableau.classList.add('is-chargement');
        try {
            const url = new URL(page.dataset.urlIndex, window.location.origin);
            url.searchParams.set('debut', cible);
            url.searchParams.set('fragment', '1');
            const reponse = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!reponse.ok) throw new Error('Impossible de recharger le planning (erreur ' + reponse.status + ').');
            const d = await reponse.json();
            tableau.innerHTML = d.tableau;
            document.getElementById('rdv-kpis').innerHTML = d.kpis;
            document.getElementById('rdv-chaine').innerHTML = d.chaine;
            const resume = document.getElementById('rdv-reglages-resume');
            if (resume && d.reglages) resume.innerHTML = d.reglages;
            page.dataset.debut = cible;
            if (historique) {
                const visible = new URL(window.location.href);
                visible.searchParams.set('debut', cible);
                window.history.pushState({ debut: cible }, '', visible);
            }
        } catch (e) {
            notifier('error', e.message);
        } finally {
            tableau.classList.remove('is-chargement');
        }
    }

    const modale = document.getElementById('rdv-modale');
    function confirmer(texte) {
        return new Promise((resoudre) => {
            modale.querySelector('[data-rdv-modale-texte]').textContent = texte;
            modale.classList.add('is-ouverte');
            const oui = modale.querySelector('[data-rdv-modale="oui"]');
            oui.focus();
            function fermer(reponse) {
                modale.classList.remove('is-ouverte');
                modale.removeEventListener('click', surClic);
                document.removeEventListener('keydown', surTouche);
                resoudre(reponse);
            }
            function surClic(ev) {
                if (ev.target === modale) return fermer(false);
                const choix = ev.target.closest('[data-rdv-modale]');
                if (choix) fermer(choix.dataset.rdvModale === 'oui');
            }
            function surTouche(ev) { if (ev.key === 'Escape') fermer(false); }
            modale.addEventListener('click', surClic);
            document.addEventListener('keydown', surTouche);
        });
    }

    async function occuper(bouton, travail) {
        if (bouton) bouton.disabled = true;
        try { return await travail(); } finally { if (bouton) bouton.disabled = false; }
    }

    // Envoi par paquets : chaque appel s'arrete de lui-meme avant la limite de
    // temps du serveur. On rappelle tant qu'il en reste, et on s'arrete net sur
    // un refus de configuration, que l'on affiche tel quel.
    let envoiEnCours = false;
    async function envoyerConvocations() {
        if (envoiEnCours) return;
        envoiEnCours = true;
        const bloc = document.getElementById('rdv-envoi');
        const titre = bloc.querySelector('[data-rdv-envoi-titre]');
        const compte = bloc.querySelector('[data-rdv-envoi-compte]');
        const barre = bloc.querySelector('[data-rdv-envoi-barre]');
        let envoyees = 0, echecs = 0, total = null;
        bloc.classList.add('is-actif');
        titre.textContent = 'Envoi des convocations…';
        try {
            for (;;) {
                const r = await envoyerPost(page.dataset.urlEnvoyer);
                envoyees += r.envoyees; echecs += r.echecs;
                if (total === null) total = envoyees + echecs + r.restantes;
                const faites = envoyees + echecs;
                barre.style.width = (total > 0 ? Math.round(100 * faites / total) : 100) + '%';
                compte.textContent = faites + ' / ' + total + ' traitées';
                if (r.bloque) {
                    notifier('error', 'Envoi interrompu : ' + r.bloque);
                    break;
                }
                if (r.restantes === 0 || r.envoyees + r.echecs === 0) {
                    notifier(echecs > 0 ? 'warning' : 'success', envoyees + ' convocation(s) envoyée(s)' + (echecs > 0 ? ', ' + echecs + ' refusée(s) — le motif est affiché sur chaque réservation.' : '.'));
                    break;
                }
            }
        } catch (e) {
            notifier('error', e.message);
        } finally {
            envoiEnCours = false;
            titre.textContent = 'Envoi terminé';
            setTimeout(() => bloc.classList.remove('is-actif'), 2500);
            rafraichir();
        }
    }

    function ouvrirReglages() {
        const reglages = document.getElementById('reglages');
        if (!reglages) return;
        reglages.open = true;
        reglages.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.addEventListener('click', async function (ev) {
        const el = ev.target.closest('[data-rdv-ouvrir-reglages], [data-rdv-action], [data-rdv-semaine], [data-rdv-basculer], [data-rdv-envoyer], [data-rdv-remettre]');
        if (!el || !page.contains(el)) return;
        ev.preventDefault();

        if (el.hasAttribute('data-rdv-ouvrir-reglages')) return ouvrirReglages();
        if (el.dataset.rdvSemaine) return rafraichir(el.dataset.rdvSemaine, true);
        if (el.hasAttribute('data-rdv-envoyer')) return envoyerConvocations();
        if (el.dataset.confirm && !(await confirmer(el.dataset.confirm))) return;

        await occuper(el, async () => {
            try {
                if (el.dataset.rdvBasculer) {
                    const r = await envoyerPost(el.dataset.rdvBasculer);
                    notifier('success', r.message);
                    return rafraichir();
                }
                if (el.dataset.rdvRemettre) {
                    const r = await envoyerPost(page.dataset.urlRemettre, { quoi: el.dataset.rdvRemettre });
                    await rafraichir();
                    if (r.a_envoyer > 0) envoyerConvocations();
                    return;
                }
                const action = el.dataset.rdvAction;
                const r = await envoyerPost(action === 'placer' ? page.dataset.urlPlacer : page.dataset.urlGenerer);
                notifier('success', r.message);
                await rafraichir();
                if (action === 'placer' && r.a_envoyer > 0) envoyerConvocations();
            } catch (e) {
                notifier('error', e.message);
            }
        });
    });

    document.addEventListener('submit', async function (ev) {
        const formulaire = ev.target.closest('[data-rdv-reglages]');
        if (!formulaire) return;
        ev.preventDefault();
        const bouton = formulaire.querySelector('[type="submit"]');
        await occuper(bouton, async () => {
            try {
                const r = await envoyerPost(formulaire.action, new FormData(formulaire));
                notifier('success', r.message);
                await rafraichir();
            } catch (e) {
                notifier('error', e.message);
            }
        });
    });

    window.addEventListener('popstate', function () {
        const debut = new URL(window.location.href).searchParams.get('debut');
        rafraichir(debut || undefined, false);
    });

    if (window.location.hash === '#reglages') ouvrirReglages();
})();
</script>
@endpush
