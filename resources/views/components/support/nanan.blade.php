{{--
    « Aide & support » piloté par Nanan (namespace CSS nsp-*).

    Inclus par le lanceur du support, qui ne le rend que si KLASSCI Care est
    ouvert à l'instance. Il intercepte les boutons `data-support-ouvrir` (menu du
    compte, feuille mobile, page d'erreur) avant le formulaire classique. Une
    capture d'écran se joint ensuite depuis la page de la demande (« Ajouter une
    capture d'écran », après l'envoi).

    Parcours : accueil (quatre choix + saisie libre) → conversation (une question
    à la fois, le serveur décide : modèle ou questions scriptées) → récapitulatif
    modifiable → envoi par la route du support (support.demandes.store), avec
    l'échange en contexte → référence et suivi.

    Le fil vit dans le navigateur (localStorage, par utilisateur) : rouvrir la
    fenêtre retrouve la conversation. Le serveur ne garde rien entre deux tours.
--}}
@props(['prenom' => ''])
@php
    $_nspConfig = [
        'tour' => route('chatbot.support.tour'),
        'prenom' => $prenom,
    ];
@endphp
<script>
/* Lien direct ?signaler=1 (page d'erreur, courriel) : le formulaire classique
   l'ouvrirait au chargement, avant que Nanan soit prête. On le retient une fois,
   et Nanan s'ouvre à sa place avec le code de suivi. */
(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('signaler') !== '1' || window.__nspLienDirect !== undefined) { return; }
    window.__nspLienDirect = { code: params.get('code') };
    function retenir(ev) {
        if (!ev.target || ev.target.id !== 'sp-modal') { return; }
        ev.preventDefault();
        document.removeEventListener('show.bs.modal', retenir, true);
    }
    document.addEventListener('show.bs.modal', retenir, true);
})();
if (typeof window.klassciNananSupport !== 'function') {
    window.klassciNananSupport = function () {
        var PERIME_MS = 24 * 60 * 60 * 1000;

        function uuid() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') { return window.crypto.randomUUID(); }
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        }

        return {
            ouvert: false,
            vue: 'accueil',
            intention: null,
            fil: [],
            choix: [],
            dernierType: null,
            saisie: '',
            attente: false,
            erreur: '',
            confirmerEffacer: false,
            choisirType: false,
            recap: { titre: '', description: '', categorie: '' },
            transcription: '',
            voirTranscription: false,
            cle: null,
            codeSuivi: null,
            envoi: false,
            resultat: null,
            verification: { etat: 'repos', message: '' },
            declencheur: null,
            support: {},
            config: {},

            init() {
                this.support = window.KLASSCI_SUPPORT || {};
                try { this.config = JSON.parse(this.$root.querySelector('[data-nsp-config]').textContent); } catch (e) { this.config = {}; }
                this._surClic = this.surClic.bind(this);
                window.addEventListener('click', this._surClic, true);

                /* Lien direct ?signaler=1 : le script plus haut a déjà retenu le
                   formulaire classique, Nanan s'ouvre avec le code de suivi. */
                if (window.__nspLienDirect) {
                    var lien = window.__nspLienDirect;
                    window.__nspLienDirect = null;
                    var self = this;
                    this.$nextTick(function () { self.ouvrir(lien.code); });
                }
            },

            destroy() {
                window.removeEventListener('click', this._surClic, true);
            },

            surClic(ev) {
                var bouton = ev.target.closest('[data-support-ouvrir]');
                if (!bouton || bouton.hasAttribute('data-support-classique')) { return; }
                ev.preventDefault();
                ev.stopPropagation();
                this.ouvrir(bouton.getAttribute('data-support-code'), bouton);
            },

            /* ----- Ouverture, brouillon ----- */

            cleBrouillon() { return 'klassci.nanan.support.' + (this.support.utilisateur || 'anonyme'); },

            ouvrir(code, declencheur) {
                this.declencheur = declencheur || document.activeElement;
                this.codeSuivi = code || null;
                this.erreur = '';
                if (this.vue === 'envoye' || this.vue === 'merci') { this.recommencer(); }
                if (this.vue === 'accueil' && this.fil.length === 0) { this.restaurer(); }
                this.ouvert = true;
                document.documentElement.classList.add('nsp-ouvert');
                var self = this;
                this.$nextTick(function () { self.focaliser(); });
            },

            fermer() {
                this.ouvert = false;
                document.documentElement.classList.remove('nsp-ouvert');
                var cible = this.declencheur;
                this.declencheur = null;
                if (cible && typeof cible.focus === 'function' && document.contains(cible)) { cible.focus(); }
            },

            focaliser() {
                var cible = this.$root.querySelector('[data-nsp-focus="' + this.vue + '"]');
                if (cible) { cible.focus({ preventScroll: true }); }
            },

            sauver() {
                var etat = { t: Date.now(), vue: this.vue, intention: this.intention, fil: this.fil, choix: this.choix,
                    dernierType: this.dernierType, recap: this.recap, transcription: this.transcription, cle: this.cle };
                try { window.localStorage.setItem(this.cleBrouillon(), JSON.stringify(etat)); } catch (e) { /* stockage indisponible : la conversation vit le temps de la page */ }
            },

            restaurer() {
                var etat = null;
                try { etat = JSON.parse(window.localStorage.getItem(this.cleBrouillon()) || 'null'); } catch (e) { etat = null; }
                if (!etat || !etat.t || Date.now() - etat.t > PERIME_MS || !Array.isArray(etat.fil) || etat.fil.length === 0) { return; }
                if (['conversation', 'recap'].indexOf(etat.vue) === -1) { return; }
                this.vue = etat.vue;
                this.intention = etat.intention;
                this.fil = etat.fil;
                this.choix = etat.choix || [];
                this.dernierType = etat.dernierType || null;
                this.recap = etat.recap || this.recap;
                this.transcription = etat.transcription || '';
                this.cle = etat.cle || null;
            },

            oublier() {
                try { window.localStorage.removeItem(this.cleBrouillon()); } catch (e) { /* rien à oublier */ }
            },

            recommencer() {
                this.oublier();
                this.vue = 'accueil';
                this.intention = null;
                this.fil = [];
                this.choix = [];
                this.dernierType = null;
                this.saisie = '';
                this.erreur = '';
                this.recap = { titre: '', description: '', categorie: '' };
                this.transcription = '';
                this.voirTranscription = false;
                this.confirmerEffacer = false;
                this.choisirType = false;
                this.cle = null;
                this.resultat = null;
                this.verification = { etat: 'repos', message: '' };
                var self = this;
                this.$nextTick(function () { self.focaliser(); });
            },

            /* ----- Accueil ----- */

            choisir(intention) {
                this.intention = intention;
                this.vue = 'conversation';
                this.tour(false);
            },

            /* Saisie libre depuis l'accueil : l'intention se devine, la personne n'a rien à trier. */
            demarrerLibre() {
                var texte = this.saisie.trim();
                if (texte === '') { return; }
                var bas = texte.toLowerCase();
                if (/(id[ée]e|suggestion|j'aimerais|ce serait bien|il faudrait|pourrait-on ajouter)/.test(bas)) {
                    this.intention = 'idee';
                } else if (/^(comment|o[uù] |est-ce que|peut-on|puis-je|je voudrais savoir|quelle|quel )/.test(bas) || /\?\s*$/.test(texte)) {
                    this.intention = 'comment';
                } else {
                    this.intention = 'probleme';
                }
                this.vue = 'conversation';
                this.repondre();
            },

            /* ----- Conversation ----- */

            repondre(texte) {
                var message = (typeof texte === 'string' ? texte : this.saisie).trim();
                if (message === '' || this.attente) { return; }
                this.fil.push({ role: 'personne', texte: message.slice(0, 1500) });
                this.saisie = '';
                this.choix = [];
                this.tour(false);
            },

            /* La personne a écrit au moins un message : revenir à l'accueil effacerait son travail. */
            aParle() {
                return this.fil.some(function (m) { return m.role === 'personne'; });
            },

            peutRecapituler() {
                return !this.attente && this.fil.filter(function (m) { return m.role === 'personne'; }).length >= 2;
            },

            resolu() {
                this.fil.push({ role: 'personne', texte: 'Oui, ça a résolu ma question.' });
                this.oublier();
                this.vue = 'merci';
                var self = this;
                this.$nextTick(function () { self.focaliser(); });
            },

            pasResolu() {
                this.fil.push({ role: 'personne', texte: "Non, ça n'a pas résolu mon problème." });
                this.dernierType = null;
                /* Un problème mérite encore une ou deux questions ; une question d'usage part telle quelle. */
                this.tour(this.intention !== 'probleme');
            },

            tour(recapitulatif) {
                if (this.attente) { return; }
                this.attente = true;
                this.erreur = '';
                this.defiler();
                var self = this;
                var jeton = document.querySelector('meta[name="csrf-token"]');
                fetch(this.config.tour, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': jeton ? jeton.getAttribute('content') : ''
                    },
                    body: JSON.stringify({
                        intention: this.intention,
                        fil: this.fil,
                        page: Object.assign({}, this.support.page || {}, { url_path: window.location.pathname, titre: document.title }),
                        recapitulatif: !!recapitulatif
                    })
                }).then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (corps) { return { ok: r.ok, corps: corps }; });
                }).then(function (r) {
                    if (!r.ok || !r.corps.action) {
                        self.erreur = (r.corps && r.corps.message) || "Nanan n'a pas pu répondre. Réessayez dans un instant.";
                        return;
                    }
                    self.recevoir(r.corps);
                }).catch(function () {
                    self.erreur = 'Connexion perdue. Votre conversation est conservée : réessayez dans un instant.';
                }).then(function () {
                    self.attente = false;
                    self.sauver();
                    self.defiler();
                    if (self.vue === 'conversation') {
                        self.$nextTick(function () { var champ = self.$refs.saisie; if (champ) { champ.focus({ preventScroll: true }); } });
                    }
                });
            },

            recevoir(tour) {
                this.fil.push({ role: 'nanan', texte: tour.texte });
                this.choix = tour.choix || [];
                this.dernierType = tour.action;
                if (tour.action === 'recapitulatif' && tour.recap) {
                    this.recap = {
                        titre: tour.recap.titre || '',
                        description: tour.recap.description || '',
                        categorie: tour.recap.categorie || ''
                    };
                    this.transcription = tour.transcription || '';
                    if (!this.cle) { this.cle = uuid(); }
                    this.vue = 'recap';
                    var self = this;
                    this.$nextTick(function () { self.focaliser(); });
                }
            },

            relancer() {
                this.tour(false);
            },

            defiler() {
                var self = this;
                this.$nextTick(function () {
                    var zone = self.$refs.fil;
                    if (zone) { zone.scrollTop = zone.scrollHeight; }
                });
            },

            /* ----- Récapitulatif et envoi ----- */

            categories() { return this.support.categories || []; },

            libelleCategorie(code) {
                var c = this.categories().find(function (x) { return x.code === code; });
                return c ? c.libelle : code;
            },

            libelleDescription() {
                return { comment: 'Votre question', idee: 'Votre idée' }[this.intention] || 'Ce qui se passe';
            },

            min() { return (this.support.limites && this.support.limites.description_min) || 10; },
            max() { return (this.support.limites && this.support.limites.description_max) || 5000; },

            /* Ce que le support reçoit : titre, description, puis l'échange, coupé s'il le faut. */
            texteEnvoye() {
                var corps = (this.recap.titre.trim() + '\n\n' + this.recap.description.trim()).trim();
                if (this.transcription) {
                    var entete = '\n\n— Échange avec Nanan —\n';
                    var place = this.max() - corps.length - entete.length;
                    if (place > 80) {
                        var echange = this.transcription.length > place ? this.transcription.slice(0, place - 1) + '…' : this.transcription;
                        corps += entete + echange;
                    }
                }
                return corps.slice(0, this.max());
            },

            peutEnvoyer() {
                return !this.envoi && this.recap.categorie !== '' && this.recap.description.trim().length >= this.min();
            },

            contexte() {
                var page = this.support.page || {};
                var ctx = {
                    route_name: page.route_name || null,
                    url_path: window.location.pathname,
                    entity: page.entity || null,
                    viewport: window.innerWidth + 'x' + window.innerHeight,
                    locale: navigator.language || null,
                    timezone: (window.Intl && Intl.DateTimeFormat().resolvedOptions().timeZone) || null,
                    request_ids: [this.codeSuivi, this.support.requestId].filter(Boolean),
                    extras: { composant: 'nanan_support' }
                };
                var marque = document.querySelector('[data-support-context]');
                if (marque) {
                    try {
                        var declare = JSON.parse(marque.getAttribute('data-support-context'));
                        if (declare && declare.type && declare.id) { ctx.entity = { type: declare.type, id: declare.id }; }
                        ['semestre', 'etat_affiche', 'periode'].forEach(function (k) {
                            if (declare && declare[k] !== undefined) { ctx.extras[k] = declare[k]; }
                        });
                        if (declare && declare.class_id) { ctx.class_id = declare.class_id; }
                    } catch (e) { /* marque mal formée : ignorée, le reste part */ }
                }
                return ctx;
            },

            poster() {
                var jeton = document.querySelector('meta[name="csrf-token"]');
                return fetch(this.support.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': jeton ? jeton.getAttribute('content') : ''
                    },
                    body: JSON.stringify({ categorie: this.recap.categorie, description: this.texteEnvoye(), cle: this.cle, contexte: this.contexte() })
                }).then(function (reponse) {
                    return reponse.json().catch(function () { return {}; }).then(function (corps) { return { statut: reponse.status, corps: corps }; });
                });
            },

            envoyer() {
                if (!this.peutEnvoyer()) { return; }
                this.envoi = true;
                this.erreur = '';
                var self = this;
                this.poster().then(function (r) {
                    if (r.statut !== 409) { return r; }
                    /* Le texte a changé depuis un envoi reçu : clé neuve, un seul nouvel essai. */
                    self.cle = uuid();
                    self.sauver();
                    return self.poster();
                }).then(function (r) {
                    if (r.statut === 201 || r.statut === 202) {
                        self.terminer(r.statut === 202, r.corps);
                        return;
                    }
                    self.erreur = r.corps && r.corps.errors
                        ? Object.keys(r.corps.errors).map(function (k) { return r.corps.errors[k][0]; }).join(' ')
                        : (r.corps && r.corps.message) || ('Envoi impossible. Écrivez-nous à ' + (self.support.supportEmail || 'notre adresse de support') + '.');
                }).catch(function () {
                    self.erreur = 'Connexion perdue. Votre demande est conservée : réessayez dans un instant.';
                }).then(function () {
                    self.envoi = false;
                });
            },

            terminer(enAttente, corps) {
                this.resultat = {
                    enAttente: enAttente,
                    reference: corps.reference || null,
                    message: corps.message || '',
                    suivi: corps.suivi_url || this.support.suivi || null,
                    /* La demande existe au support : la capture se joint depuis sa page. */
                    capture: typeof corps.suivi_url === 'string' ? corps.suivi_url + '#sd-joindre' : null,
                    verifier: corps.email_a_verifier === true && typeof corps.email_verification_url === 'string' ? corps.email_verification_url : null,
                    emailMasque: typeof corps.email_masque === 'string' ? corps.email_masque : null
                };
                this.oublier();
                this.vue = 'envoye';
                if (typeof window.mToast === 'function') { window.mToast(enAttente ? 'Demande enregistrée' : 'Demande envoyée', 'success'); }
                document.dispatchEvent(new CustomEvent('support:demande-envoyee', { detail: corps }));
                var self = this;
                this.$nextTick(function () { self.focaliser(); });
            },

            verifierEmail() {
                if (!this.resultat || !this.resultat.verifier || this.verification.etat === 'envoi') { return; }
                this.verification = { etat: 'envoi', message: '' };
                var self = this;
                var jeton = document.querySelector('meta[name="csrf-token"]');
                fetch(this.resultat.verifier, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': jeton ? jeton.getAttribute('content') : ''
                    }
                }).then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (corps) { return { ok: r.ok, corps: corps }; });
                }).then(function (r) {
                    /* Contrat KLASSCI Care : 200 {envoye:true}, 422 {envoye:false, deja_verifiee}, 503 {envoye:false}.
                       Une adresse déjà vérifiée n'est pas une erreur : le bouton disparaît. */
                    if (r.ok && r.corps.envoye === true) {
                        if (r.corps.email_masque) { self.resultat.emailMasque = r.corps.email_masque; }
                        self.verification = { etat: 'ok', message: r.corps.message || 'Un lien de confirmation vous a été envoyé. Ouvrez votre messagerie.' };
                    } else if (r.corps.deja_verifiee === true) {
                        self.verification = { etat: 'ok', message: r.corps.message || 'Votre adresse est déjà confirmée.' };
                    } else {
                        self.verification = { etat: 'erreur', message: r.corps.message || "L'envoi du lien a échoué. Réessayez dans un instant." };
                    }
                }).catch(function () {
                    self.verification = { etat: 'erreur', message: 'Connexion perdue. Réessayez dans un instant.' };
                });
            }
        };
    };
}
</script>

<div class="nsp-root" x-data="klassciNananSupport()" x-on:keydown.escape.window="ouvert && fermer()">
    <script type="application/json" data-nsp-config>@json($_nspConfig)</script>

    <div class="nsp-voile" x-show="ouvert" x-cloak x-transition.opacity x-on:click="fermer()" aria-hidden="true"></div>

    <section class="nsp-fenetre" x-show="ouvert" x-cloak x-trap.noscroll="ouvert"
             x-transition:enter="nsp-entree" x-transition:enter-start="nsp-entree-debut" x-transition:enter-end="nsp-entree-fin"
             role="dialog" aria-modal="true" aria-labelledby="nsp-titre">

        <header class="nsp-tete">
            {{-- La flèche ne vide jamais une conversation commencée : « Recommencer », en bas, demande confirmation. --}}
            <button type="button" class="nsp-icone-btn" x-show="vue === 'recap' || (vue === 'conversation' && !aParle())"
                    x-on:click="vue === 'recap' ? (vue = 'conversation') : recommencer()" aria-label="Revenir en arrière">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </button>
            <span class="nsp-avatar" x-bind:class="{ 'nsp-avatar--pense': attente }" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none"><rect x="2.5" y="3" width="19" height="18" rx="8" fill="currentColor"/><circle cx="9" cy="11" r="1.6" fill="#0453cb"/><circle cx="15" cy="11" r="1.6" fill="#0453cb"/><path d="M9.2 15.2c1.6 1.3 4 1.3 5.6 0" stroke="#0453cb" stroke-width="1.4" stroke-linecap="round"/></svg>
            </span>
            <div class="nsp-tete-textes">
                <h2 id="nsp-titre">Aide</h2>
                <p x-text="attente ? 'Nanan réfléchit…' : 'Avec Nanan, votre assistante'">Avec Nanan, votre assistante</p>
            </div>
            <button type="button" class="nsp-icone-btn nsp-icone-btn--fermer" x-on:click="fermer()" aria-label="Fermer">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
        </header>

        {{-- Accueil : quatre choix, ou on écrit directement --}}
        <div class="nsp-corps" x-show="vue === 'accueil'">
            <p class="nsp-bonjour" tabindex="-1" data-nsp-focus="accueil">
                <span x-text="config.prenom ? 'Bonjour ' + config.prenom + ',' : 'Bonjour,'"></span>
                je suis Nanan. Que puis-je faire pour vous ?
            </p>
            <div class="nsp-choix-grands">
                <button type="button" class="nsp-grand" x-on:click="choisir('probleme')">
                    <span class="nsp-grand-icone"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span>
                    <span class="nsp-grand-textes"><strong>J'ai un problème</strong><small>Quelque chose ne marche pas ou semble faux</small></span>
                    <i class="fas fa-chevron-right nsp-grand-fleche" aria-hidden="true"></i>
                </button>
                <button type="button" class="nsp-grand" x-on:click="choisir('comment')">
                    <span class="nsp-grand-icone"><i class="fas fa-circle-question" aria-hidden="true"></i></span>
                    <span class="nsp-grand-textes"><strong>Comment faire… ?</strong><small>Je ne trouve pas comment m'y prendre</small></span>
                    <i class="fas fa-chevron-right nsp-grand-fleche" aria-hidden="true"></i>
                </button>
                <button type="button" class="nsp-grand" x-on:click="choisir('idee')">
                    <span class="nsp-grand-icone"><i class="fas fa-lightbulb" aria-hidden="true"></i></span>
                    <span class="nsp-grand-textes"><strong>Une idée, une suggestion</strong><small>J'aimerais pouvoir faire quelque chose</small></span>
                    <i class="fas fa-chevron-right nsp-grand-fleche" aria-hidden="true"></i>
                </button>
                <a class="nsp-grand" x-show="support.suivi" x-bind:href="support.suivi">
                    <span class="nsp-grand-icone"><i class="fas fa-inbox" aria-hidden="true"></i></span>
                    <span class="nsp-grand-textes"><strong>Mes demandes d'aide</strong><small>Voir les réponses de l'équipe support</small></span>
                    <i class="fas fa-chevron-right nsp-grand-fleche" aria-hidden="true"></i>
                </a>
            </div>
            <form class="nsp-libre" x-on:submit.prevent="demarrerLibre()">
                <label class="nsp-libre-label" for="nsp-libre">Ou écrivez directement</label>
                <div class="nsp-barre">
                    <textarea id="nsp-libre" rows="1" class="nsp-champ" x-model="saisie" maxlength="1500"
                              placeholder="Par exemple : je ne trouve pas le bulletin de Koné Awa"
                              x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); demarrerLibre(); }"></textarea>
                    <button type="submit" class="nsp-envoyer" x-bind:disabled="saisie.trim() === ''" aria-label="Envoyer à Nanan">
                        <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>

        {{-- Conversation : une question à la fois --}}
        <div class="nsp-corps nsp-corps--fil" x-show="vue === 'conversation'">
            <div class="nsp-fil" x-ref="fil" aria-live="polite" tabindex="-1" data-nsp-focus="conversation">
                <template x-for="(m, i) in fil" x-bind:key="i">
                    <div class="nsp-bulle" x-bind:class="m.role === 'nanan' ? 'nsp-bulle--nanan' : 'nsp-bulle--personne'">
                        <span class="nsp-bulle-texte" x-text="m.texte"></span>
                    </div>
                </template>
                <div class="nsp-bulle nsp-bulle--nanan nsp-bulle--attente" x-show="attente" aria-label="Nanan réfléchit">
                    <span class="nsp-points"><i></i><i></i><i></i></span>
                </div>

                <div class="nsp-suggestions" x-show="!attente && dernierType === 'question' && choix.length">
                    <template x-for="c in choix" x-bind:key="c">
                        <button type="button" class="nsp-pastille" x-on:click="repondre(c)" x-text="c"></button>
                    </template>
                </div>

                <div class="nsp-resolu" x-show="!attente && dernierType === 'reponse'">
                    <p>Ça a résolu votre question ?</p>
                    <div class="nsp-resolu-actions">
                        <button type="button" class="nsp-btn nsp-btn--primaire" x-on:click="resolu()"><i class="fas fa-check" aria-hidden="true"></i>Oui, merci</button>
                        <button type="button" class="nsp-btn nsp-btn--secondaire" x-on:click="pasResolu()">Non, créer une demande</button>
                    </div>
                </div>

                <div class="nsp-erreur" x-show="erreur" role="alert">
                    <span x-text="erreur"></span>
                    <button type="button" class="nsp-lien" x-on:click="relancer()">Réessayer</button>
                </div>
            </div>

            <div class="nsp-pied">
                <div class="nsp-pied-liens">
                    <button type="button" class="nsp-lien nsp-lien--discret" x-show="aParle() && !confirmerEffacer" x-on:click="confirmerEffacer = true">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i> Recommencer
                    </button>
                    <span class="nsp-confirmer" x-show="confirmerEffacer" role="group" aria-label="Effacer la conversation ?">
                        Effacer la conversation ?
                        <button type="button" class="nsp-lien" x-on:click="recommencer()">Oui</button>
                        <button type="button" class="nsp-lien" x-on:click="confirmerEffacer = false">Non</button>
                    </span>
                    <button type="button" class="nsp-lien nsp-lien--recap" x-show="peutRecapituler() && dernierType === 'question'" x-on:click="tour(true)">
                        J'ai tout dit, préparer ma demande <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
                <form class="nsp-barre" x-on:submit.prevent="repondre()">
                    <label class="visually-hidden" for="nsp-saisie">Votre réponse à Nanan</label>
                    <textarea id="nsp-saisie" x-ref="saisie" rows="1" class="nsp-champ" x-model="saisie" maxlength="1500"
                              x-bind:disabled="attente" placeholder="Votre réponse…"
                              x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); repondre(); }"></textarea>
                    <button type="submit" class="nsp-envoyer" x-bind:disabled="attente || saisie.trim() === ''" aria-label="Envoyer">
                        <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Récapitulatif modifiable --}}
        <div class="nsp-corps" x-show="vue === 'recap'">
            <p class="nsp-question" tabindex="-1" data-nsp-focus="recap">Voici votre demande. Corrigez-la si besoin, puis envoyez-la.</p>

            <label class="nsp-etiquette" for="nsp-recap-titre">Titre</label>
            <input id="nsp-recap-titre" type="text" class="nsp-input" maxlength="120" x-model="recap.titre" x-on:input.debounce.600ms="sauver()">

            <label class="nsp-etiquette" for="nsp-recap-description" x-text="libelleDescription()">Ce qui se passe</label>
            <textarea id="nsp-recap-description" class="nsp-input nsp-input--long" rows="6" x-model="recap.description" x-on:input.debounce.600ms="sauver()"></textarea>
            <p class="nsp-aide" x-show="recap.description.trim().length < min()"
               x-text="'Encore quelques mots : ' + min() + ' caractères au moins.'"></p>

            {{-- Le type est déjà proposé par Nanan : une ligne, et on ne déplie la liste que pour le changer. --}}
            <p class="nsp-type" x-show="recap.categorie !== '' && !choisirType">
                Type : <strong x-text="libelleCategorie(recap.categorie)"></strong> ·
                <button type="button" class="nsp-lien" x-on:click="choisirType = true">Modifier</button>
            </p>
            <p class="nsp-etiquette" id="nsp-recap-categorie" x-show="recap.categorie === '' || choisirType">Type de demande</p>
            <div class="nsp-categories" role="radiogroup" aria-labelledby="nsp-recap-categorie" x-show="recap.categorie === '' || choisirType">
                <template x-for="c in categories()" x-bind:key="c.code">
                    <button type="button" class="nsp-pastille" role="radio"
                            x-bind:aria-checked="recap.categorie === c.code ? 'true' : 'false'"
                            x-on:click="recap.categorie = c.code; choisirType = false; sauver()">
                        <i class="fas" x-bind:class="c.icone" aria-hidden="true"></i><span x-text="c.libelle"></span>
                    </button>
                </template>
            </div>

            <p class="nsp-note"><i class="fas fa-shield-halved" aria-hidden="true"></i>
                Nous joignons votre échange avec Nanan, le nom technique de l'écran ouvert et un code de suivi. Ni le titre ni le contenu de la page ne sont transmis.
                <button type="button" class="nsp-lien" x-show="transcription" x-on:click="voirTranscription = !voirTranscription"
                        x-text="voirTranscription ? 'Masquer l\'échange' : 'Voir l\'échange joint'"></button>
            </p>
            <pre class="nsp-transcription" x-show="voirTranscription" x-text="transcription"></pre>

            <div class="nsp-erreur" x-show="erreur" role="alert"><span x-text="erreur"></span></div>

            <div class="nsp-actions">
                <button type="button" class="nsp-btn nsp-btn--secondaire" x-on:click="vue = 'conversation'">Compléter avec Nanan</button>
                <button type="button" class="nsp-btn nsp-btn--primaire" x-on:click="envoyer()" x-bind:disabled="!peutEnvoyer()">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i><span x-text="envoi ? 'Envoi…' : 'Envoyer au support'">Envoyer au support</span>
                </button>
            </div>
        </div>

        {{-- Envoyée --}}
        <div class="nsp-corps nsp-fin" x-show="vue === 'envoye'">
            <div class="nsp-fin-icone"><i class="fas fa-check" aria-hidden="true"></i></div>
            <p class="nsp-question nsp-question--centre" tabindex="-1" data-nsp-focus="envoye"
               x-text="resultat && resultat.enAttente ? 'Demande enregistrée' : 'Demande envoyée'"></p>
            <p class="nsp-fin-texte" x-show="resultat && !resultat.enAttente">
                Votre référence : <strong x-text="resultat ? resultat.reference : ''"></strong>. L'équipe support vous répondra dans « Mes demandes d'aide ».
            </p>
            <p class="nsp-fin-texte" x-show="resultat && resultat.enAttente" x-text="resultat ? resultat.message : ''"></p>

            <div class="nsp-verifier" x-show="resultat && resultat.verifier">
                <p><i class="fas fa-envelope-circle-check" aria-hidden="true"></i>
                    <span x-text="'Confirmez votre adresse ' + ((resultat && resultat.emailMasque) ? resultat.emailMasque + ' ' : '') + 'pour être averti(e) par e-mail des réponses du support'"></span></p>
                <button type="button" class="nsp-btn nsp-btn--secondaire" x-on:click="verifierEmail()"
                        x-show="verification.etat !== 'ok'" x-bind:disabled="verification.etat === 'envoi'"
                        x-text="verification.etat === 'envoi' ? 'Envoi…' : 'Confirmer mon adresse'"></button>
                <p class="nsp-verifier-message" x-show="verification.message" x-text="verification.message"
                   x-bind:class="verification.etat === 'erreur' ? 'nsp-verifier-message--erreur' : ''" role="status"></p>
            </div>

            <div class="nsp-actions nsp-actions--centre">
                <a class="nsp-btn nsp-btn--secondaire" x-show="resultat && resultat.capture" x-bind:href="resultat && resultat.capture ? resultat.capture : '#'">
                    <i class="fas fa-camera" aria-hidden="true"></i> Ajouter une capture d'écran
                </a>
                <a class="nsp-btn nsp-btn--secondaire" x-show="resultat && resultat.suivi" x-bind:href="resultat ? resultat.suivi : '#'">Suivre ma demande</a>
                <button type="button" class="nsp-btn nsp-btn--primaire" x-on:click="fermer()">Fermer</button>
            </div>
        </div>

        {{-- Résolu sans demande --}}
        <div class="nsp-corps nsp-fin" x-show="vue === 'merci'">
            <div class="nsp-fin-icone"><i class="fas fa-face-smile" aria-hidden="true"></i></div>
            <p class="nsp-question nsp-question--centre" tabindex="-1" data-nsp-focus="merci">Parfait, bonne continuation.</p>
            <p class="nsp-fin-texte">Si autre chose vous bloque, je suis là.</p>
            <div class="nsp-actions nsp-actions--centre">
                <button type="button" class="nsp-btn nsp-btn--secondaire" x-on:click="recommencer()">Autre question</button>
                <button type="button" class="nsp-btn nsp-btn--primaire" x-on:click="fermer()">Fermer</button>
            </div>
        </div>
    </section>
</div>

<style>
    html.nsp-ouvert .m-bottomnav, html.nsp-ouvert .m-fab { z-index: 1040; }
    .nsp-voile { position: fixed; inset: 0; z-index: 1094; background: rgba(15,23,42,.45); }
    .nsp-fenetre { position: fixed; z-index: 1095; left: 50%; top: 50%; transform: translate(-50%, -50%);
        width: min(520px, calc(100vw - 2rem)); height: min(680px, calc(100vh - 2rem));
        display: flex; flex-direction: column; background: #fff; border-radius: 18px; overflow: hidden;
        box-shadow: 0 24px 60px rgba(15,23,42,.22); }
    .nsp-entree { transition: opacity .2s ease, transform .2s ease; }
    .nsp-entree-debut { opacity: 0; transform: translate(-50%, -46%); }
    .nsp-entree-fin { opacity: 1; transform: translate(-50%, -50%); }

    .nsp-tete { display: flex; align-items: center; gap: .7rem; padding: 1rem 1.15rem; color: #fff; flex-shrink: 0;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%); }
    .nsp-avatar { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); color: #fff; }
    .nsp-avatar--pense svg { animation: nsp-respire 1.2s ease-in-out infinite; }
    @keyframes nsp-respire { 50% { transform: scale(.88); } }
    .nsp-tete-textes { min-width: 0; }
    .nsp-tete h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #fff; }
    .nsp-tete p { font-size: .78rem; margin: .1rem 0 0; color: rgba(255,255,255,.75); }
    .nsp-icone-btn { flex-shrink: 0; width: 36px; height: 36px; border-radius: 10px; border: 0; color: #fff;
        background: rgba(255,255,255,.14); display: inline-flex; align-items: center; justify-content: center; }
    .nsp-icone-btn:hover, .nsp-icone-btn:focus-visible { background: rgba(255,255,255,.26); outline: none; }
    .nsp-icone-btn--fermer { margin-left: auto; }

    .nsp-corps { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 1.1rem 1.15rem 1.25rem; }
    .nsp-corps--fil { display: flex; flex-direction: column; padding: 0; overflow: hidden; }
    .nsp-bonjour, .nsp-question { font-weight: 700; color: #1e293b; font-size: 1rem; margin: 0 0 .9rem; outline: none; }
    .nsp-question--centre { text-align: center; }

    .nsp-choix-grands { display: flex; flex-direction: column; gap: .55rem; }
    .nsp-grand { display: flex; align-items: center; gap: .8rem; width: 100%; min-height: 64px; padding: .7rem .85rem; text-align: left;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; color: #1e293b; text-decoration: none;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease; }
    .nsp-grand:hover, .nsp-grand:focus-visible { border-color: #0453cb; background: #f8fbff; color: #1e293b; outline: none;
        box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .nsp-grand-icone { width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        background: rgba(4,83,203,.08); color: #0453cb; font-size: 1.05rem; }
    .nsp-grand-textes { display: flex; flex-direction: column; min-width: 0; }
    .nsp-grand-textes strong { font-size: .95rem; }
    .nsp-grand-textes small { font-size: .78rem; color: #64748b; }
    .nsp-grand-fleche { margin-left: auto; color: #94a3b8; font-size: .8rem; }

    .nsp-libre { margin-top: 1.1rem; }
    .nsp-libre-label, .nsp-etiquette { display: block; font-size: .8rem; font-weight: 600; color: #64748b; margin: 0 0 .4rem; }
    .nsp-etiquette { margin-top: .9rem; }
    .nsp-barre { display: flex; align-items: flex-end; gap: .45rem; padding: .35rem .35rem .35rem .8rem;
        border: 1px solid #cbd5e1; border-radius: 14px; background: #fff; }
    .nsp-barre:focus-within { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .nsp-champ { flex: 1; border: 0; resize: none; padding: .45rem 0; font-size: .95rem; line-height: 1.4; max-height: 140px;
        field-sizing: content; min-height: 1.4em; background: transparent; color: #1e293b; }
    .nsp-champ:focus { outline: none; }
    .nsp-envoyer { width: 40px; height: 40px; border-radius: 11px; border: 0; flex-shrink: 0; background: #0453cb; color: #fff; }
    .nsp-envoyer:disabled { background: #cbd5e1; }
    .nsp-lien { background: none; border: 0; padding: 0; color: #0453cb; font-size: .82rem; font-weight: 600; }

    .nsp-fil { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 1rem 1.15rem; display: flex; flex-direction: column; gap: .55rem; outline: none; }
    .nsp-bulle { max-width: 88%; padding: .65rem .85rem; border-radius: 14px; font-size: .92rem; line-height: 1.45; }
    .nsp-bulle-texte { white-space: pre-line; overflow-wrap: anywhere; }
    .nsp-bulle--nanan { align-self: flex-start; background: #f1f5f9; color: #1e293b; border-bottom-left-radius: 4px; }
    .nsp-bulle--personne { align-self: flex-end; background: #0453cb; color: #fff; border-bottom-right-radius: 4px; }
    .nsp-points { display: inline-flex; gap: 4px; }
    .nsp-points i { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; animation: nsp-point 1s ease-in-out infinite; }
    .nsp-points i:nth-child(2) { animation-delay: .15s; }
    .nsp-points i:nth-child(3) { animation-delay: .3s; }
    @keyframes nsp-point { 50% { opacity: .3; transform: translateY(-2px); } }
    .nsp-suggestions, .nsp-categories { display: flex; flex-wrap: wrap; gap: .45rem; }
    .nsp-suggestions { align-self: flex-end; justify-content: flex-end; }
    .nsp-pastille { display: inline-flex; align-items: center; gap: .4rem; min-height: 40px; padding: .45rem .85rem; border: 1px solid #bfd3f2;
        border-radius: 999px; background: #fff; color: #0453cb; font-size: .84rem; font-weight: 600; text-align: left;
        transition: background .15s ease, border-color .15s ease, color .15s ease; }
    .nsp-pastille:hover, .nsp-pastille:focus-visible { border-color: #0453cb; background: #f8fbff; outline: none; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .nsp-pastille[aria-checked="true"] { background: #0453cb; border-color: #0453cb; color: #fff; }
    .nsp-categories .nsp-pastille { color: #1e293b; border-color: #e2e8f0; }
    .nsp-categories .nsp-pastille i { color: #0453cb; }
    .nsp-categories .nsp-pastille[aria-checked="true"], .nsp-categories .nsp-pastille[aria-checked="true"] i { color: #fff; }
    .nsp-resolu { align-self: stretch; padding: .75rem .85rem; border: 1px solid #e2e8f0; border-radius: 14px; }
    .nsp-resolu p { margin: 0 0 .6rem; font-weight: 700; color: #1e293b; font-size: .9rem; }
    .nsp-resolu-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

    .nsp-pied { flex-shrink: 0; padding: .6rem 1.15rem .85rem; border-top: 1px solid #e2e8f0; background: #fff; }
    .nsp-pied-liens { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .4rem .8rem; margin: 0 0 .5rem; }
    .nsp-pied-liens:empty { display: none; }
    .nsp-lien--recap { margin-left: auto; }
    .nsp-lien--discret { color: #64748b; font-weight: 500; }
    .nsp-confirmer { font-size: .82rem; color: #1e293b; font-weight: 600; display: inline-flex; gap: .55rem; align-items: center; }
    .nsp-type { margin: .9rem 0 0; font-size: .88rem; color: #1e293b; }
    .nsp-type .nsp-lien { font-size: .84rem; }

    .nsp-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: .65rem .8rem; font-size: .92rem; color: #1e293b; }
    .nsp-input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .nsp-input--long { resize: vertical; min-height: 140px; }
    .nsp-aide { margin: .3rem 0 0; font-size: .76rem; color: #64748b; }
    .nsp-note { margin: 1rem 0 0; padding: .65rem .8rem; border-radius: 10px; background: #f8fafc; color: #475569; font-size: .8rem; }
    .nsp-note i { color: #0453cb; margin-right: .3rem; }
    .nsp-note .nsp-lien { margin-left: .3rem; font-size: .8rem; }
    .nsp-transcription { margin: .5rem 0 0; max-height: 180px; overflow: auto; white-space: pre-wrap; font-family: inherit;
        font-size: .8rem; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: .6rem .75rem; }
    .nsp-erreur { margin-top: .4rem; padding: .6rem .8rem; border-radius: 10px; background: rgba(220,38,38,.07); color: #b91c1c; font-size: .84rem; }
    .nsp-erreur .nsp-lien { margin-left: .4rem; color: #b91c1c; text-decoration: underline; }

    .nsp-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .5rem; margin-top: 1.1rem; }
    .nsp-actions--centre { justify-content: center; }
    .nsp-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 44px; border-radius: 11px;
        padding: .6rem 1.1rem; font-size: .88rem; font-weight: 600; border: 1px solid transparent; text-decoration: none; transition: background .15s ease; }
    .nsp-btn--primaire { background: #0453cb; color: #fff; }
    .nsp-btn--primaire:hover { background: #033a8e; color: #fff; }
    .nsp-btn--primaire:disabled { opacity: .55; cursor: not-allowed; }
    .nsp-btn--secondaire { background: #fff; color: #0453cb; border-color: #bfd3f2; }
    .nsp-btn--secondaire:hover { border-color: #0453cb; color: #0453cb; }

    .nsp-fin { text-align: center; padding-top: 1.75rem; }
    .nsp-fin-icone { width: 60px; height: 60px; margin: 0 auto .9rem; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: rgba(16,185,129,.12); color: #10b981; font-size: 1.45rem; }
    .nsp-fin-texte { color: #475569; font-size: .9rem; }
    .nsp-fin-texte strong { font-family: 'Courier New', monospace; color: #0453cb; }
    .nsp-verifier { margin: 1rem auto 0; max-width: 400px; padding: .85rem .95rem; border: 1px solid #bfd3f2; border-radius: 14px; background: #f8fbff; text-align: left; }
    .nsp-verifier p { margin: 0 0 .6rem; font-size: .86rem; color: #1e293b; }
    .nsp-verifier p i { color: #0453cb; margin-right: .35rem; }
    .nsp-verifier-message { margin: .6rem 0 0 !important; color: #475569 !important; }
    .nsp-verifier-message--erreur { color: #b91c1c !important; }

    /* Téléphone : plein écran, la barre de saisie sous le pouce. */
    @media (max-width: 575.98px) {
        .nsp-fenetre { left: 0; top: 0; transform: none; width: 100%; height: 100%; height: 100dvh; border-radius: 0; }
        .nsp-entree-debut { opacity: 0; transform: translateY(16px); }
        .nsp-entree-fin { opacity: 1; transform: none; }
        .nsp-tete { padding: .7rem .85rem; padding-top: calc(.7rem + env(safe-area-inset-top)); }
        .nsp-corps { padding: 1rem .9rem calc(1rem + env(safe-area-inset-bottom)); }
        .nsp-corps--fil { padding: 0; }
        .nsp-fil { padding: .9rem; }
        .nsp-pied { padding: .55rem .9rem calc(.6rem + env(safe-area-inset-bottom)); }
        .nsp-actions { position: sticky; bottom: calc(-1rem - env(safe-area-inset-bottom)); margin: 1rem -.9rem 0; padding: .7rem .9rem;
            background: #fff; border-top: 1px solid #e2e8f0; }
        .nsp-actions .nsp-btn { flex: 1 1 0; }
        .nsp-bulle { max-width: 92%; }
        .nsp-champ { font-size: 16px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .nsp-avatar--pense svg, .nsp-points i { animation: none; }
    }
</style>
