@extends('layouts.app')

@section('title', 'Accueil du jour - Rendez-vous - KLASSCI')

@push('styles')
@include('esbtp.rendez-vous.partials._styles')
<style>
/* ===== Accueil du jour — namespace rac-* (s'appuie sur rdv-*) ===== */
.rac-jour-nav { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.rac-jour-nav .rdv-btn--glass { padding: .5rem .75rem; }
.rac-date { display: inline-flex; align-items: center; gap: .4rem; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.22); border-radius: 10px; padding: .25rem .6rem; color: #fff; }
.rac-date input { background: transparent; border: none; color: #fff; font-weight: 600; font-size: .82rem; color-scheme: dark; outline: none; }
.rac-date:focus-within { box-shadow: 0 0 0 3px rgba(255,255,255,.3); }

.rac-avancement { display: flex; align-items: center; gap: .75rem; margin-top: 1rem; font-size: .78rem; color: rgba(255,255,255,.8); font-variant-numeric: tabular-nums; }
.rac-avancement-barre { flex: 1; height: 8px; border-radius: 999px; background: rgba(255,255,255,.18); overflow: hidden; }
.rac-avancement-barre span { display: block; height: 100%; background: #fff; border-radius: 999px; transition: width .3s ease; }

.rac-barre { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }
.rac-recherche { flex: 1 1 280px; display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid var(--rdv-line); border-radius: 12px; padding: 0 .85rem; min-height: 44px; transition: border-color .2s ease, box-shadow .2s ease; }
.rac-recherche:focus-within { border-color: var(--rdv-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.15); }
.rac-recherche i { color: var(--rdv-muted); }
.rac-recherche input { flex: 1; border: none; outline: none; font-size: .88rem; background: transparent; min-width: 0; color: var(--rdv-text); }
.rac-recherche kbd { font-size: .68rem; background: var(--rdv-surface); border: 1px solid var(--rdv-line); border-radius: 6px; padding: .1rem .4rem; color: var(--rdv-muted); }
.rac-filtres { display: flex; gap: .4rem; flex-wrap: wrap; }
.rac-filtre { border: 1px solid #cbd5e1; background: #fff; border-radius: 999px; padding: .45rem .85rem; font-size: .8rem; font-weight: 600; color: var(--rdv-muted); cursor: pointer; transition: background .2s ease, color .2s ease, border-color .2s ease; }
.rac-filtre:hover { border-color: var(--rdv-primary); color: var(--rdv-primary); }
.rac-filtre.is-actif { background: var(--rdv-primary); border-color: var(--rdv-primary); color: #fff; }
.rac-filtre:focus-visible, .rac-coche:focus-visible { outline: 3px solid rgba(4,83,203,.35); outline-offset: 2px; }

.rac-info { display: flex; gap: .5rem; align-items: baseline; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.15); color: #1e3a6e; border-radius: 12px; padding: .75rem 1rem; font-size: .84rem; margin: 0 0 1rem; }

.rac-creneau { padding: 0; overflow: visible; }
.rac-creneau-tete { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .9rem 1.25rem; border-bottom: 1px solid var(--rdv-line); }
.rac-creneau-tete h3 { margin: 0; display: flex; align-items: center; gap: .6rem; font-size: .95rem; font-weight: 700; color: var(--rdv-dark); }
.rac-creneau-compte { font-size: .8rem; color: var(--rdv-muted); font-variant-numeric: tabular-nums; }
.rac-creneau-compte strong { color: var(--rdv-dark); }
.rac-creneau--en-cours { border-color: rgba(4,83,203,.45); box-shadow: 0 0 0 3px rgba(4,83,203,.08), 0 4px 16px rgba(4,83,203,.06); }
.rac-creneau--termine .rac-creneau-tete { background: var(--rdv-surface); border-radius: 14px 14px 0 0; }

.rac-puce { font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; padding: .15rem .5rem; border-radius: 999px; white-space: nowrap; }
.rac-puce--en-cours { background: var(--rdv-primary); color: #fff; }
.rac-puce--a-venir { background: rgba(4,83,203,.1); color: var(--rdv-primary); }
.rac-puce--termine { background: #e2e8f0; color: #475569; }
.rac-puce--retard { background: rgba(245,158,11,.14); color: #b45309; }
.rac-puce--reprog { background: rgba(4,83,203,.08); color: #3b5b8f; text-transform: none; letter-spacing: 0; font-weight: 600; }

.rac-lignes { list-style: none; margin: 0; padding: 0; }
.rac-ligne { display: grid; grid-template-columns: auto minmax(0, 1fr) auto auto; align-items: center; gap: .9rem; padding: .8rem 1.25rem; border-bottom: 1px solid #f1f5f9; transition: background .2s ease; }
.rac-ligne:last-child { border-bottom: none; }
.rac-ligne:hover { background: #fafcff; }
.rac-ligne.is-surlignee { background: rgba(4,83,203,.06); }
.rac-ligne--recu .rac-nom strong { color: var(--rdv-muted); text-decoration: line-through; text-decoration-color: rgba(100,116,139,.5); }
.rac-ligne--absent { background: rgba(220,38,38,.025); }

.rac-coche { width: 38px; height: 38px; border-radius: 50%; border: 2px solid #c7d4e5; background: #fff; color: transparent; display: inline-flex; align-items: center; justify-content: center; font-size: .9rem; cursor: pointer; transition: all .2s ease; flex-shrink: 0; padding: 0; }
button.rac-coche:hover { border-color: var(--rdv-succes); color: var(--rdv-succes); background: rgba(16,185,129,.06); }
.rac-coche.is-cochee { background: var(--rdv-succes); border-color: var(--rdv-succes); color: #fff; }
button.rac-coche.is-cochee:hover { background: #fff; color: var(--rdv-succes); }
.rac-coche--absent { border-color: rgba(220,38,38,.35); color: #b91c1c; background: rgba(220,38,38,.06); cursor: default; }
.rac-coche--attendu { border-style: dashed; color: #94a3b8; cursor: default; }
.rac-coche[disabled] { opacity: .5; cursor: wait; }

.rac-qui { min-width: 0; }
.rac-nom { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
.rac-nom strong { font-size: .9rem; color: var(--rdv-dark); }
.rac-contacts { display: flex; gap: .85rem; flex-wrap: wrap; margin-top: .2rem; font-size: .78rem; color: var(--rdv-muted); }
.rac-contacts a { color: var(--rdv-muted); text-decoration: none; display: inline-flex; gap: .3rem; align-items: center; font-variant-numeric: tabular-nums; }
.rac-contacts a:hover { color: var(--rdv-primary); }
.rac-contacts i { font-size: .7rem; }
.rac-ref { font-family: 'Courier New', monospace; font-weight: 700; color: var(--rdv-primary); background: rgba(4,83,203,.07); padding: 0 .4rem; border-radius: 5px; }
.rac-statut { display: flex; flex-direction: column; align-items: flex-end; gap: .15rem; text-align: right; }
.rac-statut small { font-size: .7rem; color: var(--rdv-muted); }
.rac-actions { display: flex; gap: .4rem; justify-content: flex-end; flex-wrap: wrap; }
.rac-aucun { text-align: center; color: var(--rdv-muted); padding: 1.5rem; font-size: .88rem; }
.rac-aucun i { margin-right: .4rem; }
#rac-liste.is-chargement { opacity: .55; pointer-events: none; transition: opacity .2s ease; }

.rac-cloture { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.rac-cloture .rdv-section-head { flex: 1 1 420px; }
.rac-cloture p { margin: .15rem 0 0; font-size: .82rem; color: var(--rdv-muted); }
.rac-reprogrammees .rac-creneau-tete h3 { color: var(--rdv-dark); }
.rac-reprogrammees .rac-creneau-tete h3 i { color: var(--rdv-primary); }
.rac-ligne--reprog .rac-nom strong { color: var(--rdv-muted); }

/* Modale de reprogrammation */
.rac-reprog { max-width: 560px; }
.rac-choix { list-style: none; margin: 0 0 1rem; padding: 0; max-height: 50vh; overflow-y: auto; border: 1px solid var(--rdv-line); border-radius: 12px; }
.rac-choix-jour { position: sticky; top: 0; background: var(--rdv-surface); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--rdv-muted); padding: .45rem .9rem; border-bottom: 1px solid var(--rdv-line); }
.rac-choix label { display: flex; align-items: center; gap: .75rem; padding: .6rem .9rem; cursor: pointer; border-bottom: 1px solid #f1f5f9; margin: 0; transition: background .15s ease; }
.rac-choix label:hover { background: #fafcff; }
.rac-choix input { accent-color: var(--rdv-primary); width: 16px; height: 16px; }
.rac-choix label:has(input:checked) { background: rgba(4,83,203,.07); }
.rac-choix-heure { font-weight: 700; color: var(--rdv-dark); font-variant-numeric: tabular-nums; font-size: .86rem; }
.rac-choix-libres { margin-left: auto; font-size: .75rem; color: var(--rdv-muted); }
.rac-choix-vide { padding: 1.25rem; text-align: center; color: var(--rdv-muted); font-size: .85rem; }

@media (max-width: 768px) {
    .rac-ligne { grid-template-columns: auto minmax(0, 1fr); gap: .6rem .75rem; padding: .8rem 1rem; }
    .rac-statut { grid-column: 2; align-items: flex-start; flex-direction: row; gap: .5rem; text-align: left; }
    .rac-actions { grid-column: 1 / -1; justify-content: flex-start; }
    .rac-creneau-tete { padding: .8rem 1rem; }
    .rac-recherche kbd { display: none; }
}
@media (max-width: 576px) {
    .rac-jour-nav { grid-column: 1 / -1; flex-wrap: nowrap; }
    .rac-jour-nav .rdv-btn { width: auto; flex: 0 0 auto; }
    .rac-date { flex: 1 1 auto; min-width: 0; justify-content: center; }
    .rac-date input { width: 100%; min-width: 0; }
    .rdv-hero-actions [data-rac-planning] { grid-column: span 2; }
    .rac-filtres { width: 100%; }
    .rac-filtre { flex: 1 1 auto; }
}
</style>
@endpush

@section('content')
@php
    $_hier = $jour->copy()->subDay();
    $_demain = $jour->copy()->addDay();
    $_estAujourdhui = $jour->isToday();
@endphp
<div class="container-fluid rdv-page rac-page" data-rac-page
     data-url-index="{{ route('esbtp.rendez-vous.accueil.index') }}"
     data-url-creneaux="{{ route('esbtp.rendez-vous.accueil.creneaux') }}"
     data-url-cloturer="{{ route('esbtp.rendez-vous.accueil.cloturer') }}"
     data-jour="{{ $jour->toDateString() }}">

    <div class="rdv-hero">
        <div class="rdv-hero-top">
            <div class="rdv-hero-left">
                <div class="rdv-hero-icon"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <h1>Accueil du jour</h1>
                    <p data-rac-titre-jour>{{ ucfirst($jour->translatedFormat('l j F Y')) }}{{ $_estAujourdhui ? ' — aujourd\'hui' : '' }}</p>
                </div>
            </div>
            <div class="rdv-hero-actions">
                <div class="rac-jour-nav" role="group" aria-label="Changer de jour">
                    <button type="button" class="rdv-btn rdv-btn--glass" data-rac-jour="{{ $_hier->toDateString() }}" aria-label="Jour précédent"><i class="fas fa-chevron-left"></i></button>
                    <label class="rac-date"><i class="fas fa-calendar-day"></i><span class="visually-hidden">Date</span>
                        <input type="date" value="{{ $jour->toDateString() }}" data-rac-date>
                    </label>
                    <button type="button" class="rdv-btn rdv-btn--glass" data-rac-jour="{{ $_demain->toDateString() }}" aria-label="Jour suivant"><i class="fas fa-chevron-right"></i></button>
                    <button type="button" class="rdv-btn rdv-btn--glass" data-rac-jour="{{ now()->toDateString() }}" @if($_estAujourdhui) hidden @endif data-rac-aujourdhui>Aujourd'hui</button>
                </div>
                <button type="button" class="rdv-btn rdv-btn--glass" data-page-tour-open><i class="fas fa-route"></i>Guide</button>
                <button type="button" class="rdv-btn rdv-btn--glass" data-page-help-open><i class="fas fa-circle-question"></i>Aide</button>
                @can('inscriptions.rdv.view')
                    <a class="rdv-btn rdv-btn--white" href="{{ route('esbtp.rendez-vous.index', ['debut' => $jour->toDateString()]) }}" data-rac-planning><i class="fas fa-calendar-week"></i>Planning</a>
                @endcan
            </div>
        </div>
        <div id="rac-kpis">@include('esbtp.rendez-vous.accueil.partials._kpis')</div>
    </div>

    <div class="rac-barre">
        <label class="rac-recherche">
            <i class="fas fa-magnifying-glass"></i>
            <span class="visually-hidden">Rechercher une famille</span>
            <input type="search" placeholder="Nom, téléphone ou référence…" autocomplete="off" data-rac-cherche>
            <kbd>Entrée</kbd>
        </label>
        <div class="rac-filtres" role="group" aria-label="Filtrer">
            <button type="button" class="rac-filtre is-actif" data-rac-filtre="tous" aria-pressed="true">Toutes</button>
            <button type="button" class="rac-filtre" data-rac-filtre="attendu" aria-pressed="false">À recevoir</button>
            <button type="button" class="rac-filtre" data-rac-filtre="recu" aria-pressed="false">Reçues</button>
            <button type="button" class="rac-filtre" data-rac-filtre="absent" aria-pressed="false">Absentes</button>
        </div>
    </div>

    <div id="rac-liste" aria-live="polite">@include('esbtp.rendez-vous.accueil.partials._liste')</div>

    <section class="rdv-card rac-cloture" data-rac-bloc-cloture @if($jour->isFuture() && ! $_estAujourdhui) hidden @endif>
        <div class="rdv-section-head" style="margin:0">
            <span class="rdv-section-icon"><i class="fas fa-flag-checkered"></i></span>
            <div>
                <h2>Fin de journée</h2>
                <p>Les familles encore attendues sur un créneau terminé passent « absentes ». Vous les reprogrammez ensuite une à une.</p>
            </div>
        </div>
        <button type="button" class="rdv-btn rdv-btn--primary" data-rac-cloturer><i class="fas fa-flag-checkered"></i>Clôturer la journée</button>
    </section>

    <div class="rdv-modale" id="rac-reprog" role="dialog" aria-modal="true" aria-labelledby="rac-reprog-titre">
        <form class="rdv-modale-boite rac-reprog" data-rac-reprog-form>
            <h2 id="rac-reprog-titre"><span class="rdv-section-icon"><i class="fas fa-calendar-plus"></i></span>Reprogrammer</h2>
            <p>Nouveau rendez-vous pour <strong data-rac-reprog-nom></strong>. La convocation part par e-mail si la famille en a un ; sinon, appelez-la.</p>
            <ul class="rac-choix" data-rac-choix><li class="rac-choix-vide">Chargement des créneaux…</li></ul>
            <div class="rdv-modale-pied">
                <button type="button" class="rdv-btn rdv-btn--ghost" data-rac-reprog-fermer>Annuler</button>
                <button type="submit" class="rdv-btn rdv-btn--primary" data-rac-reprog-valider disabled><i class="fas fa-check"></i>Reprogrammer</button>
            </div>
        </form>
    </div>

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

    <div class="rdv-modale" id="rdv-aide" role="dialog" aria-modal="true" aria-labelledby="rdv-aide-titre">
        <div class="rdv-modale-boite rdv-aide">
            <h2 id="rdv-aide-titre"><span class="rdv-section-icon"><i class="fas fa-book-open"></i></span>L'accueil du jour</h2>
            <h3>Pendant la journée</h3>
            <ol>
                <li><strong>La famille arrive</strong> : cherchez-la par son nom, son téléphone ou sa référence, puis cochez le rond. Si un seul résultat reste, la touche Entrée la coche.</li>
                <li><strong>Une erreur de clic</strong> : recliquez sur le rond vert, la famille redevient attendue.</li>
                <li><strong>« En retard »</strong> apparaît quand le créneau a commencé depuis plus que la tolérance réglée (15 minutes par défaut).</li>
            </ol>
            <h3>En fin de journée</h3>
            <ol>
                <li><strong>Clôturer la journée</strong> marque absentes les familles encore attendues sur un créneau terminé. Rien n'est marqué d'office : c'est vous qui clôturez.</li>
                <li><strong>Reprogrammer</strong> propose les prochains créneaux libres. La famille redevient attendue ce jour-là, et son absence reste comptée : la liste affiche « Reprogrammé · 1 absence ».</li>
            </ol>
            <h3>Les états</h3>
            <ul class="rdv-aide-etats">
                <li><span class="rdv-badge rdv-badge--inconnu">À recevoir</span> attendue, pas encore arrivée.</li>
                <li><span class="rdv-badge rdv-badge--attente">À recevoir</span> en retard sur son créneau.</li>
                <li><span class="rdv-badge rdv-badge--succes">Reçue</span> avec l'heure et l'agent qui l'a reçue.</li>
                <li><span class="rdv-badge rdv-badge--echec">Absente</span> à reprogrammer.</li>
            </ul>
            <div class="rdv-modale-pied">
                <button type="button" class="rdv-btn rdv-btn--primary" data-rdv-aide-fermer>J'ai compris</button>
            </div>
        </div>
    </div>
</div>

@include('partials._klassci_toast')
@endsection

@push('scripts')
<script>
(function () {
    if (window.__racPageInit) return;
    window.__racPageInit = true;

    const page = document.querySelector('[data-rac-page]');
    if (!page) return;
    // La page a ses propres toasts : le relais du shell mobile les doublerait.
    document.body.setAttribute('data-m-toast', 'off');
    const jeton = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const liste = document.getElementById('rac-liste');
    const champ = page.querySelector('[data-rac-cherche]');
    let filtre = 'tous';

    function notifier(type, message) {
        const div = document.createElement('div');
        div.textContent = message;
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: div.innerHTML } }));
    }

    async function appeler(url, options) {
        const reponse = await fetch(url, Object.assign({ headers: { 'X-CSRF-TOKEN': jeton, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' } }, options || {}));
        const donnees = await reponse.json().catch(() => ({}));
        if (!reponse.ok) {
            throw new Error(donnees.message || (reponse.status === 429 ? 'Trop de demandes, patientez une minute.' : 'Action impossible (erreur ' + reponse.status + ').'));
        }
        return donnees;
    }
    const poster = (url, corps) => appeler(url, { method: 'POST', body: JSON.stringify(corps || {}) });

    // Recherche et filtre s'appliquent cote client, et se rejouent apres chaque rechargement de la liste.
    function appliquerFiltres() {
        const q = (champ.value || '').trim().toLocaleLowerCase('fr').replace(/[\s-]+/g, ' ');
        const qCompact = q.replace(/\s/g, '');
        let visibles = [];
        liste.querySelectorAll('.rac-ligne').forEach((li) => {
            const texte = li.dataset.cherche || '';
            const okTexte = q === '' || texte.includes(q) || texte.replace(/\s/g, '').includes(qCompact);
            const okFiltre = filtre === 'tous' || li.dataset.statut === filtre;
            li.hidden = !(okTexte && okFiltre);
            li.classList.remove('is-surlignee');
            if (!li.hidden) visibles.push(li);
        });
        liste.querySelectorAll('[data-rac-creneau]').forEach((s) => {
            s.hidden = !s.querySelector('.rac-ligne:not([hidden])');
        });
        const aucun = liste.querySelector('.rac-aucun');
        if (aucun) aucun.hidden = visibles.length > 0;
        if (q !== '' && visibles.length === 1) visibles[0].classList.add('is-surlignee');
        return visibles;
    }

    async function rafraichir(jour, historique) {
        const cible = jour || page.dataset.jour;
        liste.classList.add('is-chargement');
        try {
            const url = new URL(page.dataset.urlIndex, window.location.origin);
            url.searchParams.set('jour', cible);
            url.searchParams.set('fragment', '1');
            const d = await appeler(url);
            liste.innerHTML = d.liste;
            document.getElementById('rac-kpis').innerHTML = d.kpis;
            if (cible !== page.dataset.jour) majEnTete(cible);
            page.dataset.jour = cible;
            if (historique) {
                const visible = new URL(window.location.href);
                visible.searchParams.set('jour', cible);
                window.history.pushState({ jour: cible }, '', visible);
            }
            appliquerFiltres();
        } catch (e) {
            notifier('error', e.message);
        } finally {
            liste.classList.remove('is-chargement');
        }
    }

    function isoDecale(iso, jours) {
        const d = new Date(iso + 'T12:00:00');
        d.setDate(d.getDate() + jours);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function majEnTete(iso) {
        const aujourdhui = page.querySelector('[data-rac-aujourdhui]').dataset.racJour;
        const d = new Date(iso + 'T12:00:00');
        let libelle = d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        libelle = libelle.charAt(0).toUpperCase() + libelle.slice(1) + (iso === aujourdhui ? ' — aujourd\'hui' : '');
        page.querySelector('[data-rac-titre-jour]').textContent = libelle;
        page.querySelector('[data-rac-date]').value = iso;
        const nav = page.querySelectorAll('.rac-jour-nav [data-rac-jour]');
        nav[0].dataset.racJour = isoDecale(iso, -1);
        nav[1].dataset.racJour = isoDecale(iso, 1);
        page.querySelector('[data-rac-aujourdhui]').hidden = iso === aujourdhui;
        page.querySelector('[data-rac-bloc-cloture]').hidden = iso > aujourdhui;
        const planning = page.querySelector('[data-rac-planning]');
        if (planning) {
            const u = new URL(planning.href);
            u.searchParams.set('debut', iso);
            planning.href = u;
        }
    }

    const modale = document.getElementById('rdv-modale');
    function confirmer(texte) {
        return new Promise((resoudre) => {
            const retour = document.activeElement;
            modale.querySelector('[data-rdv-modale-texte]').textContent = texte;
            modale.classList.add('is-ouverte');
            modale.querySelector('[data-rdv-modale="oui"]').focus();
            function fermer(reponse) {
                modale.classList.remove('is-ouverte');
                modale.removeEventListener('click', surClic);
                document.removeEventListener('keydown', surTouche);
                if (retour && typeof retour.focus === 'function' && document.contains(retour)) retour.focus();
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

    // --- Reprogrammation ---
    const reprog = document.getElementById('rac-reprog');
    const formReprog = reprog.querySelector('[data-rac-reprog-form]');
    const choix = reprog.querySelector('[data-rac-choix]');
    const valider = reprog.querySelector('[data-rac-reprog-valider]');
    let urlReprog = null, retourReprog = null;

    function fermerReprog() {
        reprog.classList.remove('is-ouverte');
        document.removeEventListener('keydown', echapReprog);
        if (retourReprog && document.contains(retourReprog)) retourReprog.focus();
    }
    function echapReprog(ev) { if (ev.key === 'Escape') fermerReprog(); }

    function ligneChoix(c) {
        const label = document.createElement('label');
        const radio = document.createElement('input');
        radio.type = 'radio'; radio.name = 'creneau_id'; radio.value = c.id;
        const heure = document.createElement('span'); heure.className = 'rac-choix-heure'; heure.textContent = c.heure;
        const libres = document.createElement('span'); libres.className = 'rac-choix-libres'; libres.textContent = c.libres + ' place' + (c.libres > 1 ? 's' : '') + ' libre' + (c.libres > 1 ? 's' : '');
        label.append(radio, heure, libres);
        return label;
    }

    async function ouvrirReprog(bouton) {
        urlReprog = bouton.dataset.racReprogrammer;
        retourReprog = bouton;
        reprog.querySelector('[data-rac-reprog-nom]').textContent = bouton.dataset.racNom || '';
        choix.innerHTML = '<li class="rac-choix-vide">Chargement des créneaux…</li>';
        valider.disabled = true;
        reprog.classList.add('is-ouverte');
        document.addEventListener('keydown', echapReprog);
        try {
            const d = await appeler(page.dataset.urlCreneaux);
            choix.innerHTML = '';
            if (!d.creneaux.length) {
                choix.innerHTML = '<li class="rac-choix-vide">Aucun créneau libre à venir. Générez ou ouvrez des créneaux dans le planning.</li>';
                return;
            }
            let jourCourant = null, groupe = null;
            d.creneaux.forEach((c) => {
                if (c.date !== jourCourant) {
                    jourCourant = c.date;
                    const li = document.createElement('li');
                    const titre = document.createElement('div'); titre.className = 'rac-choix-jour'; titre.textContent = c.libelle;
                    groupe = document.createElement('div');
                    li.append(titre, groupe);
                    choix.append(li);
                }
                groupe.append(ligneChoix(c));
            });
            const premier = choix.querySelector('input');
            if (premier) premier.focus();
        } catch (e) {
            choix.innerHTML = '';
            const li = document.createElement('li'); li.className = 'rac-choix-vide'; li.textContent = e.message;
            choix.append(li);
        }
    }

    choix.addEventListener('change', () => { valider.disabled = !choix.querySelector('input:checked'); });
    reprog.addEventListener('click', (ev) => {
        if (ev.target === reprog || ev.target.closest('[data-rac-reprog-fermer]')) fermerReprog();
    });
    formReprog.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const coche = choix.querySelector('input:checked');
        if (!coche || !urlReprog) return;
        valider.disabled = true;
        try {
            const r = await poster(urlReprog, { creneau_id: Number(coche.value) });
            fermerReprog();
            notifier('success', r.message);
            await rafraichir();
        } catch (e) {
            notifier('error', e.message);
            valider.disabled = false;
            if (/rempli|commencé|fermé/.test(e.message)) ouvrirReprog(retourReprog);
        }
    });

    // --- Actions de la liste ---
    async function agir(bouton, url) {
        bouton.disabled = true;
        try {
            const r = await poster(url);
            notifier('success', r.message);
            await rafraichir();
            champ.focus();
            champ.select();
        } catch (e) {
            notifier('error', e.message);
            bouton.disabled = false;
        }
    }

    document.addEventListener('click', async function (ev) {
        const el = ev.target.closest('[data-rac-action], [data-rac-reprogrammer], [data-rac-jour], [data-rac-filtre], [data-rac-cloturer]');
        if (!el || !page.contains(el)) return;
        ev.preventDefault();

        if (el.dataset.racAction) return agir(el, el.dataset.racAction);
        if (el.dataset.racReprogrammer) return ouvrirReprog(el);
        if (el.dataset.racJour) return rafraichir(el.dataset.racJour, true);
        if (el.dataset.racFiltre) {
            filtre = el.dataset.racFiltre;
            page.querySelectorAll('[data-rac-filtre]').forEach((b) => {
                const actif = b === el;
                b.classList.toggle('is-actif', actif);
                b.setAttribute('aria-pressed', actif ? 'true' : 'false');
            });
            return appliquerFiltres();
        }
        if (el.hasAttribute('data-rac-cloturer')) {
            if (!(await confirmer('Les familles encore attendues sur un créneau terminé seront marquées absentes. Les créneaux en cours ne sont pas touchés.'))) return;
            el.disabled = true;
            try {
                const r = await poster(page.dataset.urlCloturer, { jour: page.dataset.jour });
                notifier('success', r.message);
                filtre = 'absent';
                page.querySelectorAll('[data-rac-filtre]').forEach((b) => {
                    const actif = b.dataset.racFiltre === 'absent';
                    b.classList.toggle('is-actif', actif);
                    b.setAttribute('aria-pressed', actif ? 'true' : 'false');
                });
                await rafraichir();
            } catch (e) {
                notifier('error', e.message);
            } finally {
                el.disabled = false;
            }
        }
    });

    champ.addEventListener('input', appliquerFiltres);
    champ.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') { champ.value = ''; appliquerFiltres(); return; }
        if (ev.key !== 'Enter') return;
        ev.preventDefault();
        const visibles = appliquerFiltres();
        if (visibles.length !== 1) {
            if (visibles.length > 1) notifier('info', visibles.length + ' familles correspondent : précisez la recherche.');
            return;
        }
        const coche = visibles[0].querySelector('button.rac-coche:not(.is-cochee)');
        if (coche) agir(coche, coche.dataset.racAction);
    });

    page.querySelector('[data-rac-date]').addEventListener('change', (ev) => {
        if (ev.target.value) rafraichir(ev.target.value, true);
    });

    window.addEventListener('popstate', function () {
        const jour = new URL(window.location.href).searchParams.get('jour');
        rafraichir(jour || page.querySelector('[data-rac-aujourdhui]').dataset.racJour, false);
    });
})();
</script>
<script>
window.__rdvGuideEtapes = [
    { sel: '#rac-kpis', titre: 'Le point de la journée', texte: 'Familles attendues, reçues, encore à recevoir et absentes. La barre montre l\'avancement.' },
    { sel: '.rac-recherche', titre: 'Retrouver une famille', texte: 'Tapez un nom, un téléphone ou une référence. S\'il ne reste qu\'une famille, Entrée la marque reçue.' },
    { sel: '.rac-filtres', titre: 'Filtrer la liste', texte: '« À recevoir » pour voir qui manque encore, « Absentes » pour reprogrammer en fin de journée.' },
    { sel: '#rac-liste .rac-coche', titre: 'Cocher à l\'arrivée', texte: 'Un clic marque la famille reçue, avec l\'heure et votre nom. Un second clic annule.' },
    { sel: '#rac-liste .rac-actions', titre: 'Absente ou à déplacer', texte: '« Absente » une fois le créneau commencé ; « Reprogrammer » propose les prochains créneaux libres et renvoie la convocation.' },
    { sel: '[data-rac-cloturer]', titre: 'Clôturer la journée', texte: 'Marque absentes les familles qui ne sont pas venues. Elles restent listées pour être reprogrammées.' },
    { sel: '.rac-jour-nav', titre: 'Changer de jour', texte: 'Préparez demain ou revenez sur hier sans recharger la page.' },
];

// Journee vide : une ligne d'exemple, marquee comme telle, jamais envoyee au serveur.
window.__rdvGuideAvant = function () {
    if (document.querySelector('#rac-liste .rac-ligne')) return;
    const cible = document.getElementById('rac-liste');
    if (!cible) return;
    const demo = document.createElement('section');
    demo.className = 'rdv-card rac-creneau rac-creneau--en-cours rdv-tour-demo';
    demo.setAttribute('data-tour-demo', '');
    demo.innerHTML = '<span class="rdv-tour-demo-etiquette">Exemple du guide</span>'
        + '<header class="rac-creneau-tete"><h3><span class="rdv-heure">08:00 – 08:30</span><span class="rac-puce rac-puce--en-cours">En cours</span></h3><span class="rac-creneau-compte"><strong>0</strong> / 1 reçue</span></header>'
        + '<ul class="rac-lignes"><li class="rac-ligne"><span class="rac-coche" aria-hidden="true"><i class="fas fa-check"></i></span>'
        + '<div class="rac-qui"><div class="rac-nom"><strong>Famille exemple</strong></div><div class="rac-contacts"><span class="rac-ref">C-EXEMPLE</span><span><i class="fas fa-phone"></i> +225 07 00 00 00 00</span></div></div>'
        + '<div class="rac-statut"><span class="rdv-badge rdv-badge--inconnu">À recevoir</span></div>'
        + '<div class="rac-actions"><span class="rdv-btn rdv-btn--ghost rdv-btn--sm"><i class="fas fa-user-xmark"></i>Absente</span><span class="rdv-btn rdv-btn--ghost rdv-btn--sm"><i class="fas fa-calendar-plus"></i>Reprogrammer</span></div></li></ul>';
    cible.prepend(demo);
};
</script>
@include('esbtp.rendez-vous.partials._guide')
@endpush
