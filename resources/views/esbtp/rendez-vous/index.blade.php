@extends('layouts.app')

@section('title', 'Rendez-vous d\'inscription - KLASSCI')

@push('styles')
@include('esbtp.rendez-vous.partials._styles')
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
                @can('inscriptions.rdv.accueil')
                    <a class="rdv-btn rdv-btn--glass" href="{{ route('esbtp.rendez-vous.accueil.index') }}"><i class="fas fa-clipboard-check"></i>Accueil du jour</a>
                @endcan
                <button type="button" class="rdv-btn rdv-btn--glass" data-page-tour-open><i class="fas fa-route"></i>Guide</button>
                <button type="button" class="rdv-btn rdv-btn--glass" data-page-help-open><i class="fas fa-circle-question"></i>Aide</button>
                @if($peutConfigurer)
                    <button type="button" class="rdv-btn rdv-btn--glass" data-rdv-ouvrir-reglages><i class="fas fa-sliders"></i>Réglages</button>
                @endif
                <x-export-modal
                    button-class="rdv-btn rdv-btn--glass"
                    label="Familles à prévenir"
                    :preview-url="route('esbtp.rendez-vous.familles.apercu')"
                    :pdf-url="route('esbtp.rendez-vous.familles.pdf')"
                    :excel-url="route('esbtp.rendez-vous.familles.excel')" />
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

    <div class="rdv-modale" id="rdv-aide" role="dialog" aria-modal="true" aria-labelledby="rdv-aide-titre">
        <div class="rdv-modale-boite rdv-aide">
            <h2 id="rdv-aide-titre"><span class="rdv-section-icon"><i class="fas fa-book-open"></i></span>Comment fonctionnent les rendez-vous</h2>
            <h3>Le chemin d'une famille</h3>
            <ol>
                <li><strong>Réglages</strong> — jours, horaires du guichet, durée et nombre de familles par créneau.</li>
                <li><strong>Générer les créneaux</strong> — les créneaux se créent pour toute la période. Les créneaux déjà réservés ne sont jamais touchés.</li>
                <li><strong>Ouvrir la prise de rendez-vous</strong> — sans elle, le site klassci.com répond « pas ouverte », même si des créneaux existent.</li>
                <li><strong>La famille réserve</strong> sur le site, ou vous la placez avec « Placer et convoquer ».</li>
                <li><strong>La convocation part par e-mail</strong>, avec un lien vers son PDF.</li>
            </ol>
            <h3>L'état d'une convocation</h3>
            <ul class="rdv-aide-etats">
                <li><span class="rdv-badge rdv-badge--succes">Convocation envoyée</span> MailPulse l'a acceptée ; l'heure est affichée.</li>
                <li><span class="rdv-badge rdv-badge--attente">En attente d'envoi</span> elle partira au prochain envoi : par la tâche planifiée si elle est active sur votre établissement, ou avec le bouton « Envoyer ».</li>
                <li><span class="rdv-badge rdv-badge--echec">Envoi échoué</span> la raison est écrite sous le badge. « Relancer les échecs » les renvoie.</li>
                <li><span class="rdv-badge rdv-badge--neutre">Sans e-mail</span> aucune adresse valide : prévenez la famille autrement.</li>
                <li><span class="rdv-badge rdv-badge--neutre">Sans objet</span> le créneau était passé au moment de l'envoi.</li>
                <li><span class="rdv-badge rdv-badge--inconnu">Non suivie</span> réservation d'avant le suivi des envois : on ne sait pas si le courriel est parti.</li>
            </ul>
            <h3>Fermer un créneau</h3>
            <p>L'interrupteur retire le créneau du site. Les familles qui l'ont déjà réservé gardent leur rendez-vous.</p>
            <div class="rdv-modale-pied">
                <button type="button" class="rdv-btn rdv-btn--primary" data-rdv-aide-fermer>J'ai compris</button>
            </div>
        </div>
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
    // La page a ses propres toasts : le relais du shell mobile les doublerait.
    document.body.setAttribute('data-m-toast', 'off');
    const jeton = document.querySelector('meta[name="csrf-token"]')?.content || '';
    // Semaine affichee au chargement : l'URL sans ?debut y revient au retour arriere.
    const debutInitial = page.dataset.debut;

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
            const retour = document.activeElement;
            modale.querySelector('[data-rdv-modale-texte]').textContent = texte;
            modale.classList.add('is-ouverte');
            const oui = modale.querySelector('[data-rdv-modale="oui"]');
            oui.focus();
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
                if (r.en_cours) {
                    // Un autre envoi (tache planifiee, autre onglet) tient le verrou :
                    // on attend qu'il libere plutot que de doubler les courriels.
                    titre.textContent = 'Un autre envoi est en cours, reprise dans un instant…';
                    await new Promise((ok) => setTimeout(ok, 4000));
                    titre.textContent = 'Envoi des convocations…';
                    continue;
                }
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
        rafraichir(debut || debutInitial, false);
    });

    if (window.location.hash === '#reglages') ouvrirReglages();
})();
</script>
<script>
window.__rdvGuideEtapes = [
    { sel: '.rdv-kpis', titre: 'Le point du jour', texte: 'Rendez-vous à venir, convocations parties et à traiter, places encore libres : tout ce qui compte en un coup d\'œil.' },
    { sel: '#rdv-chaine', titre: 'L\'état de la chaîne', texte: 'Si une famille ne peut pas réserver ou ne reçoit rien, la raison est ici, avec le lien pour la régler.' },
    { sel: '#reglages', titre: 'Les réglages', texte: 'Jours, horaires, durée et nombre de familles par créneau. N\'oubliez pas d\'ouvrir la prise de rendez-vous aux familles.' },
    { sel: '.rdv-hero [data-rdv-action="generer"]', titre: 'Générer les créneaux', texte: 'Crée les créneaux de toute la période. Un créneau déjà réservé n\'est jamais modifié.' },
    { sel: '.rdv-slot', titre: 'Un créneau', texte: 'La jauge montre les places prises. L\'interrupteur retire le créneau du site sans annuler les réservations.' },
    { sel: '.rdv-resas', titre: 'Les familles attendues', texte: 'Dépliez pour voir qui vient, et si sa convocation est partie — ou pourquoi elle a échoué.', ouvrir: true },
    { sel: '.rdv-hero [data-rdv-action="placer"]', titre: 'Placer et convoquer', texte: 'Place les dossiers en attente sur les premiers créneaux libres, puis envoie leurs convocations, avec une barre de progression.' },
    { sel: '.rdv-semaine', titre: 'Changer de semaine', texte: 'Les flèches parcourent le planning sans recharger la page. « Aujourd\'hui » revient à la semaine en cours.' },
];

// Semaine vide : un creneau d'exemple, marque comme tel, jamais envoye au serveur.
window.__rdvGuideAvant = function () {
    const reglages = document.getElementById('reglages');
    if (reglages) reglages.open = false;
    if (document.querySelector('#rdv-tableau .rdv-slot')) return;
    const cible = document.querySelector('#rdv-tableau .rdv-semaine');
    if (!cible) return;
    const demo = document.createElement('section');
    demo.className = 'rdv-card rdv-jour rdv-tour-demo';
    demo.setAttribute('data-tour-demo', '');
    demo.innerHTML = '<span class="rdv-tour-demo-etiquette">Exemple du guide</span>'
        + '<header class="rdv-jour-tete"><h3>Lundi (exemple)</h3><span class="rdv-jour-resume">1 créneau · <strong>2</strong> / 4 places prises</span></header>'
        + '<div class="rdv-slots"><div class="rdv-slot rdv-slot--ouvert"><div class="rdv-slot-ligne">'
        + '<span class="rdv-heure">08:00 – 08:30</span><span class="rdv-jauge"><span style="width:50%"></span></span>'
        + '<span class="rdv-slot-compte"><strong>2</strong> / 4</span><span class="rdv-etat rdv-etat--ouvert">Ouvert</span>'
        + '<span class="rdv-switch" aria-hidden="true" style="background:#0453cb"><span class="rdv-switch-rond" style="left:21px"></span></span></div>'
        + '<details class="rdv-resas" open><summary><i class="fas fa-chevron-right"></i>2 familles attendues</summary><ul>'
        + '<li class="rdv-resa"><div class="rdv-resa-qui"><strong>Famille exemple A</strong><span>07 00 00 00 00</span></div><div class="rdv-resa-conv"><span class="rdv-badge rdv-badge--succes">Convocation envoyée</span></div></li>'
        + '<li class="rdv-resa"><div class="rdv-resa-qui"><strong>Famille exemple B</strong><span>05 00 00 00 00</span></div><div class="rdv-resa-conv"><span class="rdv-badge rdv-badge--echec">Envoi échoué</span><small class="rdv-resa-erreur">Adresse refusée</small></div></li>'
        + '</ul></details></div></div>';
    cible.insertAdjacentElement('afterend', demo);
};
</script>
@include('esbtp.rendez-vous.partials._guide')
@endpush
