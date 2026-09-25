/*
 * Assistant KLASSCI — Vue d'une réponse (étapes, widgets, texte) et défilement collé en bas.
 *
 * Fichier 4/5 de public/js/assistant/, chargés dans l'ordre par le
 * composant resources/views/components/chatbot/assistant.blade.php. Les
 * fichiers partagent l'espace window.KlassciAst.
 */
(function (A) {
    'use strict';

    if (A.vue) {
        return;
    }

    var SEUIL_BAS = A.SEUIL_BAS,
        uid = A.uid,
        mouvementReduit = A.mouvementReduit,
        el = A.el,
        icone = A.icone,
        lien = A.lien,
        pluriel = A.pluriel,
        duree = A.duree,
        copierTexte = A.copierTexte,
        confirmerCopie = A.confirmerCopie,
        BlocTexte = A.BlocTexte,
        RENDUS = A.RENDUS,
        widgetALien = A.widgetALien;
    // ─── Vue d'une réponse ───

    /**
     * Tient le fil d'une réponse : groupes d'étapes, résultats, blocs de texte,
     * dans l'ordre d'arrivée. Hors de la réactivité d'Alpine ; ne touche au
     * message Alpine (msg) que pour les suites, les liens et les drapeaux.
     */
    function Vue(msg, ctx) {
        this.msg = msg;
        this.ctx = ctx;
        this.root = el('div', 'ast-flow');
        this.segments = [];
        this.etapes = {};
        this.widgets = {};
        this.nettoyages = [];
        this.pensee = el('div', 'ast-pensee');
        this.pensee.appendChild(el('span', 'ast-pensee-dot'));
        this.pensee.appendChild(el('span', 'ast-shimmer', 'Analyse de votre demande…'));
        this.debut = performance.now();
        var self = this;
        this.root.addEventListener('click', function (ev) { self.surClic(ev); });
    }

    Vue.prototype.monter = function (hote) {
        if (this.root.parentNode !== hote) { hote.appendChild(this.root); }
    };

    /** Un nettoyage posé pendant le rendu d'un widget est aussi rattaché à ce widget. */
    Vue.prototype.aNettoyer = function (fn) {
        this.nettoyages.push(fn);
        this.nettoyageEnAttente = fn;
    };

    Vue.prototype.detruire = function () {
        this.nettoyages.forEach(function (fn) { try { fn(); } catch (e) { /* déjà détruit */ } });
        this.nettoyages = [];
        this.segments.forEach(function (s) { if (s.bloc && s.bloc.raf) { window.cancelAnimationFrame(s.bloc.raf); } });
        this.root.remove();
    };

    Vue.prototype.attendre = function () {
        if (!this.segments.length && !this.pensee.parentNode) { this.root.appendChild(this.pensee); }
    };

    Vue.prototype.dernier = function () {
        return this.segments[this.segments.length - 1] || null;
    };

    Vue.prototype.ajouterSegment = function (seg) {
        if (this.pensee.parentNode) { this.pensee.remove(); }
        var precedent = this.dernier();
        this.segments.push(seg);
        seg.el.classList.add('ast-part');
        this.root.appendChild(seg.el);
        if (precedent && precedent.type === 'etapes') { this.replierSiFini(precedent); }
    };

    Vue.prototype.surClic = function (ev) {
        var copie = ev.target.closest('[data-ast-copy]');
        if (copie && this.root.contains(copie)) {
            var bloc = copie.closest('.ast-code');
            var code = bloc ? bloc.querySelector('pre') : null;
            if (code) { copierTexte(code.textContent).then(function () { confirmerCopie(copie); }); }
        }
    };

    // Étapes

    Vue.prototype.etape = function (id, data) {
        data = data || {};
        var cle = id || uid('e');
        var existant = this.etapes[cle];
        if (data.etat === 'retire') {
            if (existant) { this.retirerEtape(cle); }
            return;
        }
        if (!existant) {
            var groupe = this.dernier();
            if (!groupe || groupe.type !== 'etapes' || groupe.clos) {
                groupe = this.nouveauGroupe();
            }
            var item = { id: cle, groupe: groupe, vu: performance.now(), fin: null, data: {} };
            item.el = el('li', 'ast-step');
            item.ic = el('span', 'ast-step-ic');
            var txt = el('div', 'ast-step-txt');
            item.lbl = el('span', 'ast-step-lbl');
            item.det = el('span', 'ast-step-det');
            txt.appendChild(item.lbl);
            txt.appendChild(item.det);
            item.duree = el('span', 'ast-step-time');
            item.el.appendChild(item.ic);
            item.el.appendChild(txt);
            item.el.appendChild(item.duree);
            groupe.items.push(item);
            groupe.liste.appendChild(item.el);
            this.etapes[cle] = item;
            existant = item;
        }
        existant.data = Object.assign({}, existant.data, data);
        this.dessinerEtape(existant);
        this.majGroupe(existant.groupe);
        if (existant.groupe !== this.dernier()) { this.replierSiFini(existant.groupe); }
    };

    Vue.prototype.nouveauGroupe = function () {
        var g = { type: 'etapes', items: [], clos: false };
        g.el = el('div', 'ast-steps is-live');
        g.sommaire = el('button', 'ast-steps-sum');
        g.sommaire.type = 'button';
        g.sommaire.setAttribute('aria-expanded', 'true');
        g.sommaireIc = el('span', 'ast-steps-ic');
        g.sommaireTxt = el('span', 'ast-steps-txt');
        g.sommaire.appendChild(g.sommaireIc);
        g.sommaire.appendChild(g.sommaireTxt);
        g.sommaire.appendChild(icone('fas fa-chevron-down ast-steps-caret'));
        g.corps = el('div', 'ast-steps-body');
        var interieur = el('div', 'ast-steps-inner');
        g.liste = el('ol', 'ast-steps-list');
        interieur.appendChild(g.liste);
        g.corps.appendChild(interieur);
        g.el.appendChild(g.sommaire);
        g.el.appendChild(g.corps);
        g.sommaire.addEventListener('click', function () {
            var ouvert = g.el.classList.toggle('is-open');
            g.sommaire.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });
        this.ajouterSegment(g);
        return g;
    };

    Vue.prototype.dessinerEtape = function (item) {
        var d = item.data;
        var etat = ['en_cours', 'termine', 'echec', 'interrompu'].indexOf(d.etat) >= 0 ? d.etat : 'en_cours';
        item.el.className = 'ast-step is-' + etat;
        while (item.ic.firstChild) { item.ic.removeChild(item.ic.firstChild); }
        if (etat === 'en_cours') {
            item.ic.appendChild(el('span', 'ast-spinner'));
        } else {
            item.ic.appendChild(icone(etat === 'termine' ? 'fas fa-check' : (etat === 'echec' ? 'fas fa-exclamation' : 'fas fa-minus')));
            if (!item.fin) { item.fin = performance.now(); }
        }
        var libelle = d.libelle || d.label || 'Consultation des données';
        if (etat === 'en_cours') {
            item.lbl.textContent = libelle + '…';
            item.lbl.className = 'ast-step-lbl ast-shimmer';
        } else if (etat === 'echec') {
            item.lbl.textContent = d.resume || (libelle + ' : donnée non accessible');
            item.lbl.className = 'ast-step-lbl';
        } else if (etat === 'interrompu') {
            item.lbl.textContent = libelle + ' (interrompu)';
            item.lbl.className = 'ast-step-lbl';
        } else {
            item.lbl.textContent = d.resume || libelle;
            item.lbl.className = 'ast-step-lbl';
        }
        item.det.textContent = d.detail || '';
        item.det.hidden = !d.detail;
        var ms = d.duree_ms || (item.fin ? item.fin - item.vu : 0);
        item.duree.textContent = etat === 'en_cours' ? '' : duree(ms);
    };

    Vue.prototype.majGroupe = function (g) {
        var n = g.items.length;
        var enCours = g.items.filter(function (i) { return (i.data.etat || 'en_cours') === 'en_cours'; }).length;
        var echecs = g.items.filter(function (i) { return i.data.etat === 'echec'; }).length;
        var total = 0;
        g.items.forEach(function (i) { total += i.data.duree_ms || (i.fin ? i.fin - i.vu : 0); });
        while (g.sommaireIc.firstChild) { g.sommaireIc.removeChild(g.sommaireIc.firstChild); }
        g.sommaireIc.appendChild(enCours ? el('span', 'ast-spinner') : icone(echecs ? 'fas fa-exclamation' : 'fas fa-check'));
        g.el.classList.toggle('has-fail', !!echecs && !enCours);
        var morceaux = [pluriel(n, 'étape', 'étapes')];
        if (echecs) { morceaux.push(pluriel(echecs, 'sans résultat', 'sans résultat')); }
        if (!enCours && total) { morceaux.push(duree(total)); }
        g.sommaireTxt.textContent = morceaux.join(' · ');
        g.el.hidden = n === 0;
    };

    Vue.prototype.replierSiFini = function (g) {
        if (g.clos || !g.items.length) { return; }
        var enCours = g.items.some(function (i) { return (i.data.etat || 'en_cours') === 'en_cours'; });
        if (enCours) { return; }
        g.clos = true;
        g.el.classList.remove('is-live', 'is-open');
        g.sommaire.setAttribute('aria-expanded', 'false');
        this.majGroupe(g);
    };

    Vue.prototype.retirerEtape = function (cle) {
        var item = this.etapes[cle];
        delete this.etapes[cle];
        var g = item.groupe;
        g.items = g.items.filter(function (i) { return i !== item; });
        item.el.remove();
        if (!g.items.length) {
            g.el.remove();
            this.segments = this.segments.filter(function (s) { return s !== g; });
            if (!this.segments.length && this.msg.status === 'streaming') { this.attendre(); }
        } else {
            this.majGroupe(g);
        }
    };

    // Résultats

    Vue.prototype.widget = function (id, kind, data) {
        var k = String(kind || '').replace(/_/g, '-');
        var rendu = RENDUS[k];
        if (!rendu || !data) {
            this.suites(data);
            return;
        }
        var noeud;
        this.nettoyageEnAttente = null;
        try {
            noeud = rendu(data, this.ctx, this);
        } catch (e) {
            noeud = el('p', 'ast-w-error', "Ce résultat n'a pas pu être affiché.");
        }
        var hote = el('div', 'ast-widget ast-widget--' + k);
        hote.appendChild(noeud);
        var existant = id ? this.widgets[id] : null;
        if (existant) {
            if (existant.nettoyer) { existant.nettoyer(); }
            existant.el.replaceWith(hote);
            existant.el = hote;
            existant.nettoyer = this.nettoyageEnAttente;
        } else {
            var seg = { type: 'widget', id: id, el: hote, nettoyer: this.nettoyageEnAttente };
            if (id) { this.widgets[id] = seg; }
            this.ajouterSegment(seg);
        }
        if (widgetALien(k, data)) { this.msg.lienWidget = true; }
        this.suites(data);
    };

    // Texte

    Vue.prototype.bloc = function (id) {
        for (var i = this.segments.length - 1; i >= 0; i -= 1) {
            var s = this.segments[i];
            if (s.type === 'texte' && s.id === id) { return s.bloc; }
        }
        return null;
    };

    Vue.prototype.texteDebut = function (id) {
        var existant = this.bloc(id);
        if (existant && !existant.fini) { return existant; }
        var b = new BlocTexte(this, id);
        this.ajouterSegment({ type: 'texte', id: id, el: b.el, bloc: b });
        return b;
    };

    Vue.prototype.texteDelta = function (id, delta) {
        var b = this.bloc(id);
        if (!b || b.fini) { b = this.texteDebut(id); }
        b.ajouter(delta);
    };

    Vue.prototype.texteFin = function (id) {
        var b = this.bloc(id);
        if (b) { b.terminer(); }
    };

    Vue.prototype.signalerTexte = function () {
        if (!this.msg.aTexte) {
            var aTexte = this.segments.some(function (s) { return s.type === 'texte' && s.bloc.src.trim(); });
            if (aTexte) { this.msg.aTexte = true; }
        }
    };

    Vue.prototype.relire = function () {
        this.segments.forEach(function (s) { if (s.type === 'texte') { s.bloc.rendre(performance.now()); } });
    };

    Vue.prototype.markdown = function () {
        return this.segments.filter(function (s) { return s.type === 'texte'; })
            .map(function (s) { return s.bloc.src + s.bloc.tampon; })
            .filter(function (t) { return t.trim(); })
            .join('\n\n');
    };

    // Suites, liens, fin

    Vue.prototype.suites = function (data) {
        if (!data) { return; }
        var s = this.msg.suites;
        (data.follow_up || []).forEach(function (q) { if (typeof q === 'string' && s.questions.indexOf(q) < 0 && s.questions.length < 4) { s.questions.push(q); } });
        (data.follow_up_actions || []).forEach(function (x) { if (x && x.label) { s.actions.push(Object.assign({ fait: false }, x)); } });
    };

    Vue.prototype.lien = function (url) {
        if (url && this.msg.liens.indexOf(url) < 0) { this.msg.liens.push(url); }
    };

    /** Clôt la réponse : textes vidés, étapes en cours marquées interrompues, groupes repliés. */
    Vue.prototype.terminer = function (interrompu) {
        var self = this;
        if (this.pensee.parentNode) { this.pensee.remove(); }
        this.segments.forEach(function (s) {
            if (s.type === 'texte') { s.bloc.terminer(); }
            if (s.type === 'etapes') {
                s.items.forEach(function (i) {
                    if ((i.data.etat || 'en_cours') === 'en_cours') {
                        i.data = Object.assign({}, i.data, { etat: interrompu ? 'interrompu' : 'termine' });
                        self.dessinerEtape(i);
                    }
                });
                self.replierSiFini(s);
                self.majGroupe(s);
            }
        });
        this.signalerTexte();
    };

    /** Réponse enregistrée (historique) : parties ordonnées. */
    Vue.prototype.chargerParties = function (parties) {
        var self = this;
        (parties || []).forEach(function (p) {
            if (!p || !p.type) { return; }
            if (p.type === 'etape') {
                self.etape(p.id, { nom: p.nom, libelle: p.libelle, etat: p.etat || 'termine', resume: p.resume, detail: p.detail, duree_ms: p.duree_ms });
            } else if (p.type === 'widget') {
                var data = p.data || {};
                self.widget(p.id, p.kind || data.kind, data);
            } else if (p.type === 'texte') {
                var b = new BlocTexte(self, uid('t'));
                self.ajouterSegment({ type: 'texte', id: b.id, el: b.el, bloc: b });
                b.charger(p.texte);
            } else if (p.type === 'suites') {
                self.suites(p.data);
            } else if (p.type === 'lien') {
                self.lien(p.url || (p.data && p.data.url));
            }
        });
        this.terminer(false);
    };

    /** Ancien format (content + display_type / display_data) : texte, puis résultat, puis lien. */
    Vue.prototype.chargerLegacy = function (message) {
        var parties = versParties(message);
        var self = this;
        parties.forEach(function (p) {
            if (p.type === 'text') {
                var b = new BlocTexte(self, uid('t'));
                self.ajouterSegment({ type: 'texte', id: b.id, el: b.el, bloc: b });
                b.charger(p.text);
            } else if (p.type === 'rich') {
                self.widget(null, p.kind, p.data);
            } else if (p.type === 'suites') {
                self.suites(p.data);
            } else if (p.type === 'lien') {
                self.lien(p.url);
            }
        });
        this.terminer(false);
    };

    /** Transforme un message enregistré au format historique (route JSON) en parties. */
    function versParties(message) {
        var parties = [];
        if (message.content) {
            parties.push({ key: uid('p'), type: 'text', text: message.content, fini: true });
        }
        var type = message.display_type || 'text';
        var data = message.display_data || null;
        if (type !== 'text' && data) {
            parties.push({ key: uid('p'), type: 'rich', kind: type.replace(/_/g, '-'), data: data });
        } else if (data && (data.follow_up || data.follow_up_actions)) {
            parties.push({ key: uid('p'), type: 'suites', data: data });
        }
        if (message.deep_link) {
            parties.push({ key: uid('p'), type: 'lien', url: message.deep_link });
        }
        return parties;
    }

    // ─── Défilement collé en bas ───

    /**
     * Suit le bas du fil tant que l'utilisateur ne remonte pas. Toute remontée
     * (molette, glisser, touches) détache ; revenir près du bas rattache.
     * Les défilements lancés par le code sont reconnus et ignorés.
     */
    function Defileur(fil, contenu, surEtat) {
        this.fil = fil;
        this.contenu = contenu;
        this.surEtat = surEtat;
        this.echappe = false;
        this.dernierHaut = fil.scrollTop;
        this.attendu = null;
        this.raf = null;
        this.presBas = true;
        var self = this;
        this.surDefil = function () { self.defilement(); };
        this.surRoue = function (ev) { if (ev.deltaY < 0) { self.echapper(); } };
        this.surToucheDebut = function (ev) { self.toucheY = ev.touches[0] ? ev.touches[0].clientY : null; };
        this.surToucheMouv = function (ev) {
            var y = ev.touches[0] ? ev.touches[0].clientY : null;
            if (y !== null && self.toucheY !== null && y > self.toucheY + 4) { self.echapper(); }
            self.toucheY = y;
        };
        this.surClavier = function (ev) {
            if (['PageUp', 'Home', 'ArrowUp'].indexOf(ev.key) >= 0) { self.echapper(); }
        };
        fil.addEventListener('scroll', this.surDefil, { passive: true });
        fil.addEventListener('wheel', this.surRoue, { passive: true });
        fil.addEventListener('touchstart', this.surToucheDebut, { passive: true });
        fil.addEventListener('touchmove', this.surToucheMouv, { passive: true });
        fil.addEventListener('keydown', this.surClavier);
        if (window.ResizeObserver) {
            this.observateur = new ResizeObserver(function () { self.changement(); });
            this.observateur.observe(contenu);
        }
    }

    Defileur.prototype.detruire = function () {
        this.fil.removeEventListener('scroll', this.surDefil);
        this.fil.removeEventListener('wheel', this.surRoue);
        this.fil.removeEventListener('touchstart', this.surToucheDebut);
        this.fil.removeEventListener('touchmove', this.surToucheMouv);
        this.fil.removeEventListener('keydown', this.surClavier);
        if (this.observateur) { this.observateur.disconnect(); }
        if (this.raf) { window.cancelAnimationFrame(this.raf); }
    };

    Defileur.prototype.distance = function () {
        return this.fil.scrollHeight - this.fil.scrollTop - this.fil.clientHeight;
    };

    Defileur.prototype.echapper = function () {
        if (this.echappe) { return; }
        this.echappe = true;
        if (this.raf) { window.cancelAnimationFrame(this.raf); this.raf = null; }
        this.signaler();
    };

    Defileur.prototype.defilement = function () {
        var haut = this.fil.scrollTop;
        var programme = this.attendu !== null && Math.abs(haut - this.attendu) < 2;
        if (!programme) {
            // Une remontée collée au bas n'est pas un geste : c'est le navigateur qui
            // ramène scrollTop quand le contenu raccourcit (étapes repliées, texte recomposé).
            if (haut < this.dernierHaut - 1 && this.distance() > 2) {
                this.echapper();
            } else if (this.distance() <= SEUIL_BAS) {
                this.echappe = false;
            }
        }
        this.dernierHaut = haut;
        this.signaler();
    };

    Defileur.prototype.signaler = function () {
        var pres = this.distance() <= SEUIL_BAS;
        if (pres !== this.presBas || this.echappe !== this.dernierEchappe) {
            this.presBas = pres;
            this.dernierEchappe = this.echappe;
            this.surEtat(pres, this.echappe);
        }
    };

    Defileur.prototype.changement = function () {
        if (!this.echappe) { this.versBas(); }
        this.signaler();
    };

    Defileur.prototype.poser = function (haut) {
        this.fil.scrollTop = haut;
        this.attendu = this.fil.scrollTop;
        this.dernierHaut = this.attendu;
    };

    /** Anime vers le bas (ou vers `cible`) ; instantané si le mouvement est réduit. */
    Defileur.prototype.versBas = function (instantane, cible) {
        var self = this;
        var visee = function () {
            var max = self.fil.scrollHeight - self.fil.clientHeight;
            return cible === undefined ? max : Math.min(cible, max);
        };
        if (instantane || mouvementReduit()) {
            this.poser(visee());
            this.signaler();
            return;
        }
        if (this.raf) { return; }
        var pas = function () {
            self.raf = null;
            if (self.echappe) { return; }
            var but = visee();
            var ecart = but - self.fil.scrollTop;
            if (Math.abs(ecart) < 1) { self.poser(but); self.signaler(); return; }
            var avance = ecart * 0.24;
            if (Math.abs(avance) < 2) { avance = ecart > 0 ? Math.min(2, ecart) : Math.max(-2, ecart); }
            self.poser(self.fil.scrollTop + avance);
            self.raf = window.requestAnimationFrame(pas);
        };
        this.raf = window.requestAnimationFrame(pas);
    };

    Defileur.prototype.suivre = function (instantane) {
        this.echappe = false;
        this.versBas(instantane);
        this.signaler();
    };

    /**
     * À l'envoi : la question se place en haut (64 px du tour précédent restent
     * visibles), la réponse grandit dessous. Un espace réservé donne la hauteur
     * nécessaire ; le suivi du bas ne reprend que lorsque la réponse déborde.
     */
    Defileur.prototype.ancrer = function (question, reponse) {
        Array.prototype.forEach.call(this.contenu.querySelectorAll('[data-ast-spacer]'), function (n) {
            n.style.minHeight = '';
            n.removeAttribute('data-ast-spacer');
        });
        if (!question || !reponse) { this.suivre(); return; }
        var boiteFil = this.fil.getBoundingClientRect();
        var hautQuestion = question.getBoundingClientRect().top - boiteFil.top + this.fil.scrollTop;
        var cible = Math.max(0, hautQuestion - 64);
        var manque = cible + this.fil.clientHeight - this.fil.scrollHeight;
        if (manque > 0) {
            reponse.style.minHeight = (reponse.offsetHeight + manque) + 'px';
            reponse.setAttribute('data-ast-spacer', '');
        }
        this.echappe = false;
        this.versBas(false);
    };

    A.Vue = Vue;
    A.Defileur = Defileur;
    A.vue = true;
})(window.KlassciAst = window.KlassciAst || {});
