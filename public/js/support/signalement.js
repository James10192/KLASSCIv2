/*
 * KLASSCI Care — fenetre « Aide / Signaler » (components/support/lanceur).
 *
 * Deux etapes : que se passe-t-il et racontez → voici ce que nous transmettons.
 *
 * Le brouillon survit a une fermeture ou a un rechargement (localStorage), avec
 * sa cle d'idempotence : un double clic, ou un renvoi apres coupure, retrouve la
 * meme demande au Master. Le Master ne compare que le texte, la categorie et
 * l'auteur : un contexte recapte entre deux envois ne fait pas echouer le renvoi.
 * Si le texte a change depuis un envoi que le Master a recu, il repond 409 ; on
 * tire alors une cle neuve et on renvoie une fois.
 *
 * Le brouillon est range sous l'identifiant de l'utilisateur, et ceux des autres
 * comptes sont effaces au chargement : sur un poste partage, la personne
 * suivante ne voit ni le texte ni la cle de la precedente.
 *
 * Isole par construction : toute erreur ici reste ici. Si ce fichier ne se
 * charge pas, les boutons « Signaler » deviennent un lien courriel.
 */
(function () {
    'use strict';

    var CONFIG = window.KLASSCI_SUPPORT;
    if (!CONFIG || !window.bootstrap) { return; }

    var racine = document.getElementById('sp-modal');
    if (!racine) { return; }

    var PREFIXE = 'klassci.support.brouillon.';
    var CLE_BROUILLON = PREFIXE + CONFIG.utilisateur;
    var MIN = CONFIG.limites.description_min;

    var modal = bootstrap.Modal.getOrCreateInstance(racine);
    var etat = { categorie: null, description: '', cle: null, codeSuivi: null, envoi: false, declencheur: null };

    function stockage(action) {
        try { return action(window.localStorage); } catch (e) { return null; } /* navigation privee : pas de brouillon */
    }
    function lireBrouillon() {
        return stockage(function (s) { return JSON.parse(s.getItem(CLE_BROUILLON) || 'null'); });
    }
    function ecrireBrouillon() {
        stockage(function (s) {
            s.setItem(CLE_BROUILLON, JSON.stringify({ categorie: etat.categorie, description: etat.description, cle: etat.cle }));
        });
    }
    function effacerBrouillon() {
        stockage(function (s) { s.removeItem(CLE_BROUILLON); });
    }
    function purgerAutresComptes() {
        stockage(function (s) {
            for (var i = s.length - 1; i >= 0; i--) {
                var cle = s.key(i);
                if (cle && cle.indexOf(PREFIXE) === 0 && cle !== CLE_BROUILLON) { s.removeItem(cle); }
            }
            s.removeItem('klassci.support.brouillon'); /* ancien nom, sans utilisateur */
        });
    }

    function nouvelleCle() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') { return window.crypto.randomUUID(); }
        var octets = new Uint8Array(16);
        window.crypto.getRandomValues(octets);
        octets[6] = (octets[6] & 0x0f) | 0x40;
        octets[8] = (octets[8] & 0x3f) | 0x80;
        var h = Array.prototype.map.call(octets, function (o) { return ('0' + o.toString(16)).slice(-2); }).join('');
        return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
    }

    function $(selecteur) { return racine.querySelector(selecteur); }
    function categorie(code) {
        for (var i = 0; i < CONFIG.categories.length; i++) {
            if (CONFIG.categories[i].code === code) { return CONFIG.categories[i]; }
        }
        return null;
    }

    function aller(nom) {
        racine.querySelectorAll('[data-sp-etape]').forEach(function (s) { s.hidden = s.getAttribute('data-sp-etape') !== nom; });
        racine.querySelectorAll('[data-sp-erreur]').forEach(function (e) { e.hidden = true; });

        if (nom === 'saisie') {
            marquerCategorie();
            var zone = $('#sp-description');
            zone.value = etat.description;
            compter();
        }
        if (nom === 'recap') {
            $('[data-sp-recap-categorie]').textContent = (categorie(etat.categorie) || {}).libelle || '';
            $('[data-sp-recap-description]').textContent = etat.description;
            $('[data-sp-recap-page]').textContent = window.location.pathname;
        }
        /* Le bouton clique vient d'etre masque : sans ceci, le focus tombe sur la page.
           A l'ouverture, la fenetre est encore invisible, shown.bs.modal s'en charge. */
        if (racine.classList.contains('show')) { focaliserEtape(); }
    }

    function focaliserEtape() {
        var etape = racine.querySelector('[data-sp-etape]:not([hidden])');
        if (!etape) { return; }
        var cible = etape.getAttribute('data-sp-etape') === 'saisie'
            ? (etat.categorie ? $('#sp-description') : etape.querySelector('.sp-pastille'))
            : etape.querySelector('[data-sp-focus]');
        if (cible) { cible.focus(); }
    }

    function erreur(message) {
        racine.querySelectorAll('[data-sp-etape]:not([hidden]) [data-sp-erreur]').forEach(function (e) {
            e.textContent = message;
            e.hidden = false;
        });
    }

    function compter() {
        $('[data-sp-compteur]').textContent = String(($('#sp-description').value || '').length);
    }

    function marquerCategorie() {
        racine.querySelectorAll('.sp-pastille').forEach(function (b) {
            b.setAttribute('aria-pressed', b.getAttribute('data-code') === etat.categorie ? 'true' : 'false');
        });
    }

    function construireChoix() {
        var conteneur = $('[data-sp-choix]');
        conteneur.innerHTML = '';
        CONFIG.categories.forEach(function (c) {
            var bouton = document.createElement('button');
            bouton.type = 'button';
            bouton.className = 'sp-pastille';
            bouton.setAttribute('data-code', c.code);
            bouton.setAttribute('aria-pressed', 'false');
            var icone = document.createElement('i');
            icone.className = 'fas ' + c.icone;
            icone.setAttribute('aria-hidden', 'true');
            bouton.appendChild(icone);
            bouton.appendChild(document.createTextNode(c.libelle));
            bouton.addEventListener('click', function () {
                etat.categorie = c.code;
                marquerCategorie();
                racine.querySelectorAll('[data-sp-erreur]').forEach(function (e) { e.hidden = true; });
                ecrireBrouillon();
            });
            conteneur.appendChild(bouton);
        });
    }

    /* Ce que seul le navigateur connait. Le reste (route, module, element concerne)
       a ete pose par le serveur au rendu, et il le re-verifiera a la soumission. */
    function contexte() {
        var page = CONFIG.page || {};
        var ctx = {
            route_name: page.route_name || null,
            url_path: window.location.pathname,
            entity: page.entity || null,
            viewport: window.innerWidth + 'x' + window.innerHeight,
            locale: navigator.language || null,
            timezone: (window.Intl && Intl.DateTimeFormat().resolvedOptions().timeZone) || null,
            request_ids: [etat.codeSuivi, CONFIG.requestId].filter(Boolean),
            extras: {}
        };

        /* Une page peut preciser l'element concerne : data-support-context='{"type":..,"id":..}'. */
        var marque = document.querySelector('[data-support-context]');
        if (marque) {
            try {
                var declare = JSON.parse(marque.getAttribute('data-support-context'));
                if (declare && declare.type && declare.id) { ctx.entity = { type: declare.type, id: declare.id }; }
                ['semestre', 'etat_affiche', 'composant', 'periode'].forEach(function (k) {
                    if (declare && declare[k] !== undefined) { ctx.extras[k] = declare[k]; }
                });
                if (declare && declare.class_id) { ctx.class_id = declare.class_id; }
            } catch (e) { /* marque mal formee : ignoree, le reste part */ }
        }
        return ctx;
    }

    function poster() {
        var jeton = document.querySelector('meta[name="csrf-token"]');
        return fetch(CONFIG.url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': jeton ? jeton.getAttribute('content') : ''
            },
            body: JSON.stringify({ categorie: etat.categorie, description: etat.description, cle: etat.cle, contexte: contexte() })
        }).then(function (reponse) {
            return reponse.json().catch(function () { return {}; }).then(function (corps) { return { statut: reponse.status, corps: corps }; });
        });
    }

    function envoyer() {
        if (etat.envoi) { return; }
        etat.envoi = true;
        var bouton = $('[data-sp-envoyer]');
        bouton.disabled = true;
        $('[data-sp-envoyer-libelle]').textContent = 'Envoi…';

        poster().then(function (r) {
            if (r.statut !== 409) { return r; }
            /* Le texte a change depuis un envoi recu : cle neuve, un seul nouvel essai. */
            etat.cle = nouvelleCle();
            ecrireBrouillon();
            return poster();
        }).then(function (r) {
            if (r.statut === 201 || r.statut === 202) {
                effacerBrouillon();
                terminer(r.statut === 202, r.corps);
                return;
            }
            var message = r.corps && r.corps.errors
                ? Object.keys(r.corps.errors).map(function (k) { return r.corps.errors[k][0]; }).join(' ')
                : (r.corps && r.corps.message) || ('Envoi impossible. Écrivez-nous à ' + CONFIG.supportEmail + '.');
            erreur(message);
        }).catch(function () {
            erreur('Connexion perdue. Votre texte est conservé : réessayez dans un instant.');
        }).then(function () {
            etat.envoi = false;
            bouton.disabled = false;
            $('[data-sp-envoyer-libelle]').textContent = 'Envoyer';
        });
    }

    function terminer(enAttente, corps) {
        aller('fin');
        var titre = $('[data-sp-fin-titre]');
        var texte = $('[data-sp-fin-texte]');
        var suivi = $('[data-sp-suivi]');
        texte.textContent = '';
        if (enAttente) {
            titre.textContent = 'Demande enregistrée';
            texte.textContent = corps.message || 'Elle sera transmise au support dès que possible.';
        } else {
            titre.textContent = 'Demande reçue';
            texte.appendChild(document.createTextNode('Votre référence : '));
            var ref = document.createElement('strong');
            ref.textContent = corps.reference || '—';
            texte.appendChild(ref);
            texte.appendChild(document.createTextNode('. Le support vous répondra ici.'));
        }
        if (corps.suivi_url) {
            suivi.href = corps.suivi_url;
            suivi.hidden = false;
        } else {
            suivi.hidden = true;
        }
        if (typeof window.mToast === 'function') { window.mToast(titre.textContent, 'success'); }
        document.dispatchEvent(new CustomEvent('support:demande-envoyee', { detail: corps }));
        etat.categorie = null;
        etat.description = '';
        etat.cle = null;
    }

    function ouvrir(codeSuivi, declencheur) {
        /* Une autre fenetre ouverte (« Nouveautes »…) : on la ferme plutot que d'empiler. */
        document.querySelectorAll('.modal.show').forEach(function (m) {
            if (m !== racine) { bootstrap.Modal.getOrCreateInstance(m).hide(); }
        });

        var brouillon = lireBrouillon();
        etat.categorie = brouillon && brouillon.categorie ? brouillon.categorie : null;
        etat.description = brouillon && brouillon.description ? brouillon.description : '';
        etat.cle = brouillon && brouillon.cle ? brouillon.cle : nouvelleCle();
        etat.codeSuivi = codeSuivi || null;
        etat.declencheur = declencheur || document.activeElement;
        ecrireBrouillon();
        aller('saisie');
        modal.show();
    }

    purgerAutresComptes();
    construireChoix();

    /* Un brouillon ne survit pas a la deconnexion : sur un poste partage, le compte
       suivant ne doit pas le lire. Le formulaire de bureau part par submit(), sans
       evenement submit : c'est le clic qu'on ecoute. */
    document.addEventListener('click', function (ev) {
        if (ev.target.closest('form[action$="/logout"]')) { effacerBrouillon(); }
    }, true);

    $('#sp-description').addEventListener('input', function () {
        etat.description = this.value;
        compter();
        ecrireBrouillon();
    });

    racine.querySelectorAll('[data-sp-aller]').forEach(function (b) {
        b.addEventListener('click', function () {
            var cible = b.getAttribute('data-sp-aller');
            if (cible === 'recap' && !etat.categorie) {
                erreur('Choisissez ce qui correspond le mieux.');
                return;
            }
            if (cible === 'recap' && etat.description.trim().length < MIN) {
                erreur('Décrivez ce qui s\'est passé en quelques mots (' + MIN + ' caractères au moins).');
                return;
            }
            aller(cible);
        });
    });
    $('[data-sp-envoyer]').addEventListener('click', envoyer);

    /* Tant qu'on signale, aucune autre fenetre ne s'ouvre par-dessus (« Nouveautes »
       s'affiche au chargement, parfois apres un lien ?signaler=1). */
    document.addEventListener('show.bs.modal', function (ev) {
        if (ev.target !== racine && racine.classList.contains('show')) { ev.preventDefault(); }
    });

    racine.addEventListener('shown.bs.modal', focaliserEtape);

    /* Le focus revient a ce qui a ouvert la fenetre. */
    racine.addEventListener('hidden.bs.modal', function () {
        if (etat.declencheur && typeof etat.declencheur.focus === 'function' && document.contains(etat.declencheur)) {
            etat.declencheur.focus();
        }
        etat.declencheur = null;
    });

    document.addEventListener('click', function (ev) {
        var declencheur = ev.target.closest('[data-support-ouvrir]');
        if (!declencheur) { return; }
        ev.preventDefault();
        ouvrir(declencheur.getAttribute('data-support-code'), declencheur);
    });

    /* Lien direct : ?signaler=1 (page d'erreur, courriel, guide). */
    var params = new URLSearchParams(window.location.search);
    if (params.get('signaler') === '1') { ouvrir(params.get('code')); }

    window.KlassciSupport = { ouvrir: ouvrir };
})();
