/*
 * KLASSCI Care — fenetre « Aide / Signaler » (components/support/lanceur).
 *
 * Trois etapes : que se passe-t-il → racontez → voici ce que nous transmettons.
 * Le brouillon survit a une fermeture ou a un rechargement (localStorage), avec
 * sa cle d'idempotence : un double clic, ou un renvoi apres coupure, retrouve la
 * meme demande au Master au lieu d'en creer une seconde.
 *
 * Isole par construction : toute erreur ici reste ici. Si ce fichier ne se
 * charge pas, les boutons « Signaler » deviennent un lien courriel.
 */
(function () {
    'use strict';

    var CONFIG = window.KLASSCI_SUPPORT;
    var CLE_BROUILLON = 'klassci.support.brouillon';
    if (!CONFIG || !window.bootstrap) { return; }

    var racine = document.getElementById('sp-modal');
    if (!racine) { return; }

    var modal = bootstrap.Modal.getOrCreateInstance(racine);
    var etat = { categorie: null, description: '', cle: null, codeSuivi: null, envoi: false };

    function lireBrouillon() {
        try { return JSON.parse(window.localStorage.getItem(CLE_BROUILLON) || 'null'); } catch (e) { return null; }
    }
    function ecrireBrouillon() {
        try {
            window.localStorage.setItem(CLE_BROUILLON, JSON.stringify({
                categorie: etat.categorie, description: etat.description, cle: etat.cle
            }));
        } catch (e) { /* navigation privee : le brouillon ne survit pas, rien d'autre ne change */ }
    }
    function effacerBrouillon() {
        try { window.localStorage.removeItem(CLE_BROUILLON); } catch (e) { /* idem */ }
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

        if (nom === 'description') {
            $('[data-sp-categorie-libelle]').textContent = (categorie(etat.categorie) || {}).libelle || '';
            var zone = $('#sp-description');
            zone.value = etat.description;
            compter();
            setTimeout(function () { zone.focus(); }, 50);
        }
        if (nom === 'recap') {
            $('[data-sp-recap-categorie]').textContent = (categorie(etat.categorie) || {}).libelle || '';
            $('[data-sp-recap-description]').textContent = etat.description;
            $('[data-sp-recap-page]').textContent = document.title || window.location.pathname;
        }
        if (nom === 'choix') {
            var premier = racine.querySelector('.sp-carte');
            if (premier) { setTimeout(function () { premier.focus(); }, 50); }
        }
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

    function construireChoix() {
        var conteneur = $('[data-sp-choix]');
        conteneur.innerHTML = '';
        CONFIG.categories.forEach(function (c) {
            var bouton = document.createElement('button');
            bouton.type = 'button';
            bouton.className = 'sp-carte';
            bouton.setAttribute('role', 'radio');
            bouton.setAttribute('aria-checked', 'false');
            var icone = document.createElement('i');
            icone.className = 'fas ' + c.icone;
            icone.setAttribute('aria-hidden', 'true');
            bouton.appendChild(icone);
            bouton.appendChild(document.createTextNode(c.libelle));
            bouton.addEventListener('click', function () {
                etat.categorie = c.code;
                ecrireBrouillon();
                aller('description');
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
            page_title: document.title,
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

    function envoyer() {
        if (etat.envoi) { return; }
        etat.envoi = true;
        var bouton = $('[data-sp-envoyer]');
        bouton.disabled = true;
        $('[data-sp-envoyer-libelle]').textContent = 'Envoi…';

        var jeton = document.querySelector('meta[name="csrf-token"]');
        fetch(CONFIG.url, {
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

    function ouvrir(codeSuivi) {
        var brouillon = lireBrouillon();
        etat.categorie = brouillon && brouillon.categorie ? brouillon.categorie : null;
        etat.description = brouillon && brouillon.description ? brouillon.description : '';
        etat.cle = brouillon && brouillon.cle ? brouillon.cle : nouvelleCle();
        etat.codeSuivi = codeSuivi || null;
        ecrireBrouillon();
        aller(etat.categorie ? 'description' : 'choix');
        modal.show();
    }

    construireChoix();

    $('#sp-description').addEventListener('input', function () {
        etat.description = this.value;
        compter();
        ecrireBrouillon();
    });

    racine.querySelectorAll('[data-sp-aller]').forEach(function (b) {
        b.addEventListener('click', function () {
            var cible = b.getAttribute('data-sp-aller');
            if (cible === 'recap' && etat.description.trim().length < 10) {
                erreur('Décrivez ce qui s\'est passé en quelques mots (10 caractères au moins).');
                return;
            }
            aller(cible);
        });
    });
    $('[data-sp-envoyer]').addEventListener('click', envoyer);

    document.addEventListener('click', function (ev) {
        var declencheur = ev.target.closest('[data-support-ouvrir]');
        if (!declencheur) { return; }
        ev.preventDefault();
        ouvrir(declencheur.getAttribute('data-support-code'));
    });

    /* Lien direct : ?signaler=1 (page d'erreur, courriel, guide). */
    var params = new URLSearchParams(window.location.search);
    if (params.get('signaler') === '1') { ouvrir(params.get('code')); }

    window.KlassciSupport = { ouvrir: ouvrir };
})();
