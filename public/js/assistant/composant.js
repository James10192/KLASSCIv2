/*
 * Assistant KLASSCI — Fabrique Alpine window.klassciAssistant : panneau, envoi, flux, historique, préférences.
 *
 * Fichier 5/5 de public/js/assistant/, chargés dans l'ordre par le
 * composant resources/views/components/chatbot/assistant.blade.php. Les
 * fichiers partagent l'espace window.KlassciAst.
 */
(function (A) {
    'use strict';

    if (typeof window.klassciAssistant === 'function') {
        return;
    }

    var CLE_CONVERSATION = A.CLE_CONVERSATION,
        chargerLibs = A.chargerLibs,
        uid = A.uid,
        urlSure = A.urlSure,
        envoiSur = A.envoiSur,
        lien = A.lien,
        copierTexte = A.copierTexte,
        Vue = A.Vue,
        Defileur = A.Defileur;
    // ─── Composant Alpine ───

    window.klassciAssistant = function () {
        // Hors de l'état réactif : vues des messages et défileur (objets tenant des nœuds).
        var vues = new Map();
        var defileur = null;
        var relectureLibs = false;

        function vueDe(msg) { return vues.get(msg.key) || null; }

        return {
            ouvert: false,
            large: false,
            vue: 'chat',
            cfg: { routes: {}, suggestions: [], prenom: '', maxLength: 1000, modeles: [] },
            modeleChoisi: null,
            menuModele: false,
            messages: [],
            saisie: '',
            envoiEnCours: false,
            relanceEnCours: false,
            controleur: null,
            conversationId: null,
            conversations: [],
            chargementListe: false,
            chargementHistorique: false,
            suivreBas: true,
            nonLu: false,
            libsPretes: false,
            annonce: '',
            erreurSaisie: '',
            prefs: null,
            prefsEtat: '',
            _mq: null,
            _onMq: null,

            init: function () {
                var noeud = this.$root.querySelector('script[data-ast-config]');
                try {
                    this.cfg = Object.assign(this.cfg, JSON.parse(noeud ? noeud.textContent : '{}'));
                } catch (e) { /* configuration absente : l'assistant reste inerte */ }
                this.modeleChoisi = (this.cfg.modeles && this.cfg.modeles.defaut) || null;
                try {
                    this.conversationId = window.localStorage.getItem(CLE_CONVERSATION) || null;
                } catch (e) { this.conversationId = null; }

                var self = this;
                this._mq = window.matchMedia('(max-width: 767.98px)');
                this._onMq = function () { self.verrouillerDefilement(); };
                if (this._mq.addEventListener) { this._mq.addEventListener('change', this._onMq); }
                this.$nextTick(function () { self.brancherDefileur(); });
            },

            destroy: function () {
                if (this._mq && this._mq.removeEventListener) { this._mq.removeEventListener('change', this._onMq); }
                if (this.controleur) { this.controleur.abort(); }
                if (defileur) { defileur.detruire(); defileur = null; }
                vues.forEach(function (v) { v.detruire(); });
                vues.clear();
                document.documentElement.classList.remove('ast-lock');
            },

            brancherDefileur: function () {
                if (defileur || !this.$refs.fil || !this.$refs.filContenu) { return; }
                var self = this;
                defileur = new Defileur(this.$refs.fil, this.$refs.filContenu, function (presBas, echappe) {
                    self.suivreBas = presBas;
                    self.nonLu = !presBas && echappe && self.envoiEnCours;
                });
            },

            // ─── Ouverture / fermeture ───

            ouvrir: function () {
                var self = this;
                this.ouvert = true;
                this.verrouillerDefilement();
                chargerLibs().then(function () {
                    self.libsPretes = true;
                    if (!relectureLibs) {
                        relectureLibs = true;
                        vues.forEach(function (v) { v.relire(); });
                    }
                }).catch(function () { /* texte échappé en repli */ });
                if (this.conversationId && this.messages.length === 0) {
                    this.chargerConversation(this.conversationId, true);
                }
                this.$nextTick(function () {
                    self.brancherDefileur();
                    if (self.$refs.saisie) { self.$refs.saisie.focus(); }
                    self.defiler(true);
                });
            },

            fermer: function () {
                this.ouvert = false;
                this.menuModele = false;
                this.verrouillerDefilement();
                var self = this;
                this.$nextTick(function () { if (self.$refs.lanceur) { self.$refs.lanceur.focus(); } });
            },

            surEchap: function () {
                if (!this.ouvert) { return; }
                if (this.menuModele) { this.menuModele = false; return; }
                if (this.vue !== 'chat') { this.vue = 'chat'; return; }
                this.fermer();
            },

            basculerTaille: function () {
                this.large = !this.large;
                var self = this;
                this.$nextTick(function () { if (defileur && !defileur.echappe) { defileur.versBas(true); } self.ajusterHauteur(); });
            },

            verrouillerDefilement: function () {
                var telephone = this._mq && this._mq.matches;
                document.documentElement.classList.toggle('ast-lock', this.ouvert && telephone);
            },

            // ─── Rendu ───

            /** Monte la vue d'un message dans son emplacement (x-init du gabarit). */
            monter: function (hote, msg) {
                var v = vueDe(msg);
                if (v) { v.monter(hote); }
            },

            creerVue: function (msg) {
                var v = new Vue(msg, this);
                vues.set(msg.key, v);
                return v;
            },

            oublier: function (liste) {
                (liste || []).forEach(function (m) {
                    var v = vues.get(m.key);
                    if (v) { v.detruire(); vues.delete(m.key); }
                });
            },

            urlSure: urlSure,

            lienMessage: function (msg) {
                if (msg.lienWidget || enCoursStatut(msg)) { return null; }
                for (var i = 0; i < msg.liens.length; i += 1) {
                    var u = urlSure(msg.liens[i]);
                    if (u) { return u; }
                }
                return null;
            },

            suites: function (msg) {
                return msg.suites || { questions: [], actions: [] };
            },

            estDernierAssistant: function (msg) {
                for (var i = this.messages.length - 1; i >= 0; i -= 1) {
                    if (this.messages[i].role === 'assistant') { return this.messages[i] === msg; }
                }
                return false;
            },

            texteDe: function (msg) {
                var v = vueDe(msg);
                return v ? v.markdown() : '';
            },

            enCours: function (msg) {
                return enCoursStatut(msg);
            },

            nouveauMessageAssistant: function (question, etat) {
                this.messages.push({
                    key: uid('m'), role: 'assistant', status: etat || 'streaming', question: question || null,
                    copie: false, erreur: '', aTexte: false, lienWidget: false, liens: [],
                    suites: { questions: [], actions: [] }
                });
                var msg = this.messages[this.messages.length - 1];
                this.creerVue(msg);
                return msg;
            },

            // ─── Défilement ───

            defiler: function (forcer) {
                if (!defileur) { return; }
                if (forcer) { defileur.suivre(true); } else if (!defileur.echappe) { defileur.versBas(); }
            },

            allerEnBas: function () {
                if (defileur) { defileur.suivre(false); }
            },

            // ─── Saisie ───

            ajusterHauteur: function () {
                var t = this.$refs.saisie;
                if (!t) { return; }
                t.style.height = 'auto';
                t.style.height = Math.min(t.scrollHeight, 200) + 'px';
            },

            surTouche: function (ev) {
                if (ev.key === 'Enter' && !ev.shiftKey && !ev.isComposing) {
                    ev.preventDefault();
                    this.envoyer();
                }
            },

            proposer: function (question) {
                this.envoyer(question);
            },

            // ─── Envoi et diffusion ───

            envoyer: function (texteImpose, options) {
                var opts = options || {};
                var texte = String(texteImpose !== undefined ? texteImpose : this.saisie).trim();
                if (!texte || this.envoiEnCours) { return; }
                if (texte.length > this.cfg.maxLength) {
                    this.erreurSaisie = 'Message trop long : ' + this.cfg.maxLength + ' caractères au plus.';
                    return;
                }
                this.erreurSaisie = '';
                this.vue = 'chat';
                this.menuModele = false;

                this.relanceEnCours = !!opts.relance;
                var cleQuestion = null;
                if (!opts.relance) {
                    cleQuestion = uid('m');
                    this.messages.push({ key: cleQuestion, role: 'user', text: texte });
                } else {
                    for (var i = this.messages.length - 1; i >= 0; i -= 1) {
                        if (this.messages[i].role === 'user') { cleQuestion = this.messages[i].key; break; }
                    }
                }
                if (texteImpose === undefined) {
                    this.saisie = '';
                    this.$nextTick(this.ajusterHauteur.bind(this));
                }
                var msg = this.nouveauMessageAssistant(texte);
                vueDe(msg).attendre();

                this.envoiEnCours = true;
                var self = this;
                this.$nextTick(function () {
                    self.brancherDefileur();
                    if (!defileur) { return; }
                    var fil = self.$refs.filContenu;
                    var q = cleQuestion ? fil.querySelector('[data-key="' + cleQuestion + '"]') : null;
                    var r = fil.querySelector('[data-key="' + msg.key + '"]');
                    defileur.ancrer(q, r);
                });

                var diffusion = typeof window.ReadableStream === 'function' && typeof window.TextDecoder === 'function';
                var promesse = diffusion ? this.diffuser(texte, msg) : this.envoyerSansDiffusion(texte, msg);

                promesse.catch(function (e) {
                    if (e && e.name === 'AbortError') {
                        self.clore(msg, true);
                        msg.status = 'stopped';
                        return;
                    }
                    self.clore(msg, true);
                    msg.erreur = (e && e.messageUtilisateur) || 'La connexion a été interrompue.';
                    msg.status = 'error';
                }).finally(function () {
                    // Flux fermé sans « finish » ni « error » (délai du serveur, coupure du
                    // proxy) : ne jamais laisser une réponse muette passer pour aboutie.
                    if (msg.status === 'streaming') {
                        self.clore(msg, true);
                        msg.erreur = 'La réponse a été interrompue avant la fin. Réessayez.';
                        msg.status = 'error';
                    }
                    self.envoiEnCours = false;
                    self.nonLu = false;
                    self.controleur = null;
                    self.annonce = msg.status === 'error' ? 'Erreur : la réponse n\'a pas abouti.' : self.texteDe(msg).slice(0, 600);
                    self.$nextTick(function () { if (self.$refs.saisie && self.ouvert) { self.$refs.saisie.focus(); } });
                });
            },

            libelleModele: function () {
                var liste = (this.cfg.modeles && this.cfg.modeles.liste) || [];
                var choisi = this.modeleChoisi;
                var m = liste.find(function (x) { return x.cle === choisi; });
                return m ? m.libelle : 'Modèle';
            },

            choisirModele: function (cle) {
                this.modeleChoisi = cle;
                this.menuModele = false;
                if (this.$refs.saisie) { this.$refs.saisie.focus(); }
            },

            corpsRequete: function (texte) {
                // Le modèle n'est envoyé que si le sélecteur est proposé (permission assistant.model.choose).
                var avecChoix = this.cfg.modeles && this.cfg.modeles.liste && this.modeleChoisi;
                return JSON.stringify({
                    modele: avecChoix ? this.modeleChoisi : undefined,
                    message: texte,
                    conversation_id: this.conversationId,
                    current_url: window.location.href.slice(0, 2048),
                    current_path: window.location.pathname.slice(0, 1024),
                    page_title: document.title.slice(0, 255),
                    // Réessai : le serveur remplace la réponse ratée au lieu d'ajouter un tour.
                    relance: this.relanceEnCours || undefined
                });
            },

            enTetes: function (accept) {
                return {
                    'Content-Type': 'application/json',
                    'Accept': accept,
                    'X-CSRF-TOKEN': this.cfg.csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                };
            },

            erreurHttp: function (statut) {
                var texte = 'La réponse n\'a pas pu aboutir. Réessayez.';
                if (statut === 419) { texte = 'Votre session a expiré. Rechargez la page.'; }
                if (statut === 429) { texte = 'Trop de messages en peu de temps. Patientez une minute.'; }
                if (statut === 401) { texte = 'Vous avez été déconnecté. Rechargez la page.'; }
                var e = new Error(texte);
                e.messageUtilisateur = texte;
                return e;
            },

            diffuser: function (texte, msg) {
                var self = this;
                var controleur = new AbortController();
                this.controleur = controleur;

                return fetch(this.cfg.routes.messageStream, {
                    method: 'POST',
                    headers: this.enTetes('text/event-stream'),
                    credentials: 'same-origin',
                    body: this.corpsRequete(texte),
                    signal: controleur.signal
                }).then(function (res) {
                    var type = res.headers.get('Content-Type') || '';
                    if (!res.ok || !res.body || type.indexOf('text/event-stream') !== 0) {
                        throw self.erreurHttp(res.status);
                    }
                    return self.lireFlux(res.body.getReader(), msg);
                });
            },

            lireFlux: function (lecteur, msg) {
                var self = this;
                var decodeur = new TextDecoder();
                var tampon = '';

                function traiterBloc(bloc) {
                    bloc.split('\n').forEach(function (ligne) {
                        if (ligne.indexOf('data:') !== 0) { return; }
                        var donnee = ligne.slice(5).trim();
                        if (donnee === '' || donnee === '[DONE]') { return; }
                        var partie;
                        try { partie = JSON.parse(donnee); } catch (e) { return; }
                        self.appliquerPartie(msg, partie);
                    });
                }

                function lire() {
                    return lecteur.read().then(function (r) {
                        if (r.done) {
                            tampon += decodeur.decode();
                            if (tampon.trim()) { traiterBloc(tampon); }
                            return;
                        }
                        tampon += decodeur.decode(r.value, { stream: true }).replace(/\r\n/g, '\n');
                        var idx = tampon.indexOf('\n\n');
                        while (idx >= 0) {
                            traiterBloc(tampon.slice(0, idx));
                            tampon = tampon.slice(idx + 2);
                            idx = tampon.indexOf('\n\n');
                        }
                        return lire();
                    });
                }

                return lire();
            },

            /** Applique une partie du protocole UI message stream v1 au message. */
            appliquerPartie: function (msg, partie) {
                var type = partie.type || '';
                var meta = partie.messageMetadata || null;
                var v = vueDe(msg);
                if (!v) { return; }

                if (meta && meta.conversationId) {
                    this.memoriserConversation(meta.conversationId, meta.title);
                }

                if (type === 'text-start') {
                    v.texteDebut(partie.id);
                } else if (type === 'text-delta') {
                    v.texteDelta(partie.id, partie.delta || '');
                } else if (type === 'text-end') {
                    v.texteFin(partie.id);
                } else if (type === 'data-etape') {
                    v.etape(partie.id, partie.data || {});
                } else if (type === 'data-outil') {
                    // Ancienne puce d'outil : même forme qu'une étape (libelle, etat).
                    v.etape(partie.id, partie.data || {});
                } else if (type === 'data-widget') {
                    var d = partie.data || {};
                    v.widget(partie.id, d.kind, d);
                } else if (type === 'data-lien') {
                    if (partie.data && partie.data.url) { v.lien(partie.data.url); }
                } else if (type === 'data-suites') {
                    v.suites(partie.data || {});
                } else if (type.indexOf('data-') === 0) {
                    // Ancien format : data-<kind> porte directement le display_data.
                    v.widget(partie.id || null, type.slice(5), partie.data || {});
                } else if (type === 'error') {
                    this.clore(msg, true);
                    msg.erreur = partie.errorText || 'La réponse n\'a pas pu aboutir.';
                    msg.status = 'error';
                } else if (type === 'abort') {
                    this.clore(msg, true);
                    msg.status = 'stopped';
                } else if (type === 'finish') {
                    this.clore(msg, false);
                    if (msg.status === 'streaming') { msg.status = 'done'; }
                }
            },

            clore: function (msg, interrompu) {
                var v = vueDe(msg);
                if (v) { v.terminer(interrompu); }
            },

            envoyerSansDiffusion: function (texte, msg) {
                var self = this;
                return fetch(this.cfg.routes.message, {
                    method: 'POST',
                    headers: this.enTetes('application/json'),
                    credentials: 'same-origin',
                    body: this.corpsRequete(texte)
                }).then(function (res) {
                    if (!res.ok) { throw self.erreurHttp(res.status); }
                    return res.json();
                }).then(function (json) {
                    if (!json.success) {
                        var e = new Error(json.message);
                        e.messageUtilisateur = json.message;
                        throw e;
                    }
                    if (json.conversation_id) { self.memoriserConversation(json.conversation_id); }
                    vueDe(msg).chargerLegacy({ content: json.message, display_type: json.display_type, display_data: json.display_data, deep_link: json.deep_link });
                    msg.status = 'done';
                });
            },

            arreter: function () {
                if (this.controleur) { this.controleur.abort(); }
            },

            /**
             * Réessayer une réponse en erreur ou arrêtée. Le serveur (drapeau
             * relance) retire la réponse ratée et réutilise la question déjà
             * enregistrée. Une réponse aboutie ne se régénère pas.
             */
            relancer: function (msg) {
                if (this.envoiEnCours || !(msg.erreur || msg.status === 'stopped')) { return; }
                var question = msg.question;
                if (!question) {
                    var idx = this.messages.indexOf(msg);
                    for (var i = idx - 1; i >= 0; i -= 1) {
                        if (this.messages[i].role === 'user') { question = this.messages[i].text; break; }
                    }
                }
                if (!question) { return; }
                this.oublier([msg]);
                this.messages.splice(this.messages.indexOf(msg), 1);
                this.envoyer(question, { relance: true });
            },

            copier: function (msg) {
                var texte = this.texteDe(msg);
                if (!texte) { return; }
                copierTexte(texte).then(function () {
                    msg.copie = true;
                    setTimeout(function () { msg.copie = false; }, 1600);
                }).catch(function () { /* presse-papiers refusé */ });
            },

            // ─── Conversations ───

            memoriserConversation: function (id, titre) {
                this.conversationId = id;
                try { window.localStorage.setItem(CLE_CONVERSATION, id); } catch (e) { /* stockage indisponible */ }
                if (titre) {
                    var c = this.conversations.find(function (x) { return x.id === id; });
                    if (c) { c.title = titre; }
                }
            },

            nouvelleConversation: function () {
                if (this.controleur) { this.controleur.abort(); }
                this.oublier(this.messages);
                this.messages = [];
                this.conversationId = null;
                try { window.localStorage.removeItem(CLE_CONVERSATION); } catch (e) { /* ignore */ }
                this.vue = 'chat';
                var self = this;
                this.$nextTick(function () { if (self.$refs.saisie) { self.$refs.saisie.focus(); } });
            },

            ouvrirHistorique: function () {
                var self = this;
                this.vue = 'historique';
                this.chargementListe = true;
                this.getJson(this.cfg.routes.conversations).then(function (json) {
                    self.conversations = (json && json.conversations) || [];
                }).catch(function () {
                    self.conversations = [];
                }).finally(function () { self.chargementListe = false; });
            },

            chargerConversation: function (id, silencieux) {
                var self = this;
                this.chargementHistorique = true;
                if (this.controleur) { this.controleur.abort(); }
                return this.getJson(this.cfg.routes.history.replace('__ID__', encodeURIComponent(id))).then(function (json) {
                    self.oublier(self.messages);
                    self.messages = [];
                    ((json && json.messages) || []).forEach(function (m) {
                        if (m.role === 'user') {
                            self.messages.push({ key: uid('m'), role: 'user', text: m.content || '' });
                            return;
                        }
                        var msg = self.nouveauMessageAssistant(null, 'done');
                        var v = vueDe(msg);
                        if (Array.isArray(m.parties) && m.parties.length) {
                            v.chargerParties(m.parties);
                        } else {
                            v.chargerLegacy(m);
                        }
                    });
                    self.memoriserConversation(id);
                    self.vue = 'chat';
                    self.$nextTick(function () { self.defiler(true); });
                }).catch(function () {
                    if (silencieux) {
                        // Conversation disparue (supprimée, autre compte) : on repart d'une page blanche.
                        self.conversationId = null;
                        try { window.localStorage.removeItem(CLE_CONVERSATION); } catch (e) { /* ignore */ }
                    }
                }).finally(function () { self.chargementHistorique = false; });
            },

            supprimerConversation: function (conv) {
                var self = this;
                fetch(this.cfg.routes.delete.replace('__ID__', encodeURIComponent(conv.id)), {
                    method: 'DELETE',
                    headers: this.enTetes('application/json'),
                    credentials: 'same-origin'
                }).then(function (res) {
                    if (!res.ok) { return; }
                    self.conversations = self.conversations.filter(function (c) { return c.id !== conv.id; });
                    if (self.conversationId === conv.id) { self.nouvelleConversation(); self.vue = 'historique'; }
                });
            },

            getJson: function (url) {
                return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (res) {
                        if (!res.ok) { throw new Error(String(res.status)); }
                        return res.json();
                    });
            },

            postJson: function (url, corps, methode) {
                var self = this;
                return fetch(url, {
                    method: methode || 'POST',
                    headers: this.enTetes('application/json'),
                    credentials: 'same-origin',
                    body: JSON.stringify(corps || {})
                }).then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (json) {
                        return { statut: res.status, ok: res.ok && json.success !== false, json: json, message: json.message || (res.ok ? '' : self.erreurHttp(res.status).messageUtilisateur) };
                    });
                });
            },

            ajouterReponse: function (json) {
                if (!json || (!json.message && !json.display_data)) { return; }
                if (json.conversation_id) { this.memoriserConversation(json.conversation_id); }
                var msg = this.nouveauMessageAssistant(null, 'done');
                vueDe(msg).chargerLegacy({ content: json.message, display_type: json.display_type, display_data: json.display_data, deep_link: json.deep_link });
                this.$nextTick(this.allerEnBas.bind(this));
            },

            // ─── Actions des résultats (formulaires, validations, raccourcis) ───

            posterFormulaire: function (url, corps) {
                var self = this;
                if (!envoiSur(url)) { return Promise.resolve({ ok: false, message: 'Formulaire indisponible.' }); }
                return this.postJson(url, corps).then(function (r) {
                    if (r.ok) { self.ajouterReponse(r.json); return { ok: true }; }
                    var detail = r.json && r.json.errors ? Object.values(r.json.errors)[0] : null;
                    return { ok: false, message: (Array.isArray(detail) ? detail[0] : detail) || r.message || 'Envoi impossible.' };
                }).catch(function () { return { ok: false, message: 'Connexion interrompue.' }; });
            },

            /** Valider ou refuser une proposition de l'assistant : la réponse dit ce qui a été fait. */
            repondreProposition: function (url, corps) {
                if (!envoiSur(url)) { return Promise.resolve({ ok: false, statut: 'refus', message: 'Action indisponible.' }); }
                return this.postJson(url, corps).then(function (r) {
                    var j = r.json || {};
                    return { ok: r.ok, statut: j.statut || (r.ok ? 'executee' : 'refus'), message: j.message || r.message, lien: j.lien || null };
                }).catch(function () { return { ok: false, statut: 'reseau', message: 'Connexion interrompue : rien n\'a été confirmé. Rouvrez la conversation pour voir l\'état réel.' }; });
            },

            deciderAction: function (url, approuver) {
                var self = this;
                if (!envoiSur(url)) { return Promise.resolve({ ok: false, message: 'Action indisponible.' }); }
                return this.postJson(url, {}).then(function (r) {
                    if (r.ok && approuver && r.json.display_data) {
                        self.ajouterReponse(r.json);
                    }
                    return { ok: r.ok, message: r.message || (approuver ? 'Action exécutée.' : 'Action rejetée.') };
                }).catch(function () { return { ok: false, message: 'Connexion interrompue.' }; });
            },

            lancerAction: function (action, msg) {
                var self = this;
                var routes = this.cfg.routes;
                if (action.action === 'save_preferred_name') {
                    this.postJson(routes.preferencesMemory, { type: 'preferred_name', value: action.value }).then(function (r) {
                        action.fait = r.ok;
                        self.annonce = r.ok ? 'Nom enregistré.' : 'Enregistrement impossible.';
                    });
                    return;
                }
                if (action.action === 'open_form') {
                    var cible = String(action.value || '');
                    var params = new URLSearchParams();
                    var url = null;
                    if (cible.indexOf('frais_config') === 0) {
                        url = routes.formFraisConfig;
                        if (cible.indexOf(':') > 0) { params.set('category_id', cible.split(':')[1]); }
                    } else if (cible === 'frais_category') {
                        url = routes.formFraisCategory;
                    } else if (cible.indexOf('inscriptions_filter') === 0) {
                        url = routes.formInscriptionsFilter;
                        if (cible.indexOf(':') > 0) { params.set('focus_field', cible.split(':')[1]); }
                    }
                    if (!url) { return; }
                    if (this.conversationId) { params.set('conversation_id', this.conversationId); }
                    this.getJson(url + (params.toString() ? '?' + params.toString() : '')).then(function (json) {
                        self.ajouterReponse(json);
                    }).catch(function () {
                        msg.erreur = "Ce formulaire n'est pas disponible.";
                    });
                }
            },

            // ─── Préférences ───

            ouvrirPreferences: function () {
                var self = this;
                this.vue = 'preferences';
                this.prefsEtat = '';
                this.getJson(this.cfg.routes.preferences).then(function (json) {
                    var p = (json && json.preferences) || {};
                    self.prefs = {
                        preferred_name: p.preferred_name || '',
                        response_style: p.response_style || 'standard',
                        response_tone: p.response_tone || 'pedagogique',
                        clarification_mode: p.clarification_mode || 'auto',
                        notes: p.notes || ''
                    };
                }).catch(function () { self.prefsEtat = 'Préférences indisponibles.'; });
            },

            enregistrerPreferences: function () {
                var self = this;
                if (!this.prefs) { return; }
                this.prefsEtat = 'Enregistrement…';
                this.postJson(this.cfg.routes.preferencesUpdate, this.prefs, 'PUT').then(function (r) {
                    self.prefsEtat = r.ok ? 'Préférences enregistrées.' : (r.message || 'Enregistrement impossible.');
                }).catch(function () { self.prefsEtat = 'Connexion interrompue.'; });
            }
        };
    };

    function enCoursStatut(msg) {
        return msg.status === 'streaming';
    }

    // Exposé pour les tests : fonctions pures du rendu diffusé.

})(window.KlassciAst = window.KlassciAst || {});
