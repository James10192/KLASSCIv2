/*
 * Assistant KLASSCI — interface (namespace CSS ast-*).
 *
 * Fabrique Alpine `klassciAssistant()` utilisée par le composant
 * resources/views/components/chatbot/assistant.blade.php.
 *
 * Le serveur répond au protocole « UI message stream » v1 du Vercel AI SDK :
 * des événements SSE `data: {json}` (start, start-step, text-start/delta/end,
 * data-*, message-metadata, error, finish) terminés par `data: [DONE]`.
 *
 * Sécurité : tout texte du modèle passe par marked puis DOMPurify ; tant que
 * ces deux bibliothèques ne sont pas chargées, le texte est échappé. Les
 * résultats riches (tableaux, cartes…) sont construits avec textContent,
 * jamais avec innerHTML sur une donnée.
 */
(function () {
    'use strict';

    if (typeof window.klassciAssistant === 'function') {
        return;
    }

    var CLE_CONVERSATION = 'klassci.assistant.conversation';

    var LIBS = [
        {
            global: 'marked',
            src: 'https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js',
            integrity: 'sha384-/TQbtLCAerC3jgaim+N78RZSDYV7ryeoBCVqTuzRrFec2akfBkHS7ACQ3PQhvMVi'
        },
        {
            global: 'DOMPurify',
            src: 'https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js',
            integrity: 'sha384-+VfUPEb0PdtChMwmBcBmykRMDd+v6D/oFmB3rZM/puCMDYcIvF968OimRh4KQY9a'
        }
    ];

    var chargementLibs = null;

    function chargerLibs() {
        if (chargementLibs) {
            return chargementLibs;
        }
        chargementLibs = Promise.all(LIBS.map(function (lib) {
            if (window[lib.global]) {
                return Promise.resolve();
            }
            return new Promise(function (resolve, reject) {
                var s = document.createElement('script');
                s.src = lib.src;
                s.integrity = lib.integrity;
                s.crossOrigin = 'anonymous';
                s.referrerPolicy = 'no-referrer';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        })).then(function () {
            if (window.DOMPurify && !window.__astPurifyHook) {
                window.__astPurifyHook = true;
                window.DOMPurify.addHook('afterSanitizeAttributes', function (node) {
                    if (node.tagName === 'A' && node.getAttribute('href')) {
                        node.setAttribute('target', '_blank');
                        node.setAttribute('rel', 'noopener noreferrer');
                    }
                });
            }
        });
        return chargementLibs;
    }

    var compteur = 0;
    function uid(prefixe) {
        compteur += 1;
        return (prefixe || 'k') + '_' + Date.now().toString(36) + '_' + compteur;
    }

    function echapper(texte) {
        return String(texte == null ? '' : texte)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /** N'accepte que les liens http(s) ou relatifs au site. */
    function urlSure(url) {
        if (typeof url !== 'string' || url === '') {
            return null;
        }
        try {
            var u = new URL(url, window.location.origin);
            return (u.protocol === 'http:' || u.protocol === 'https:') ? u.href : null;
        } catch (e) {
            return null;
        }
    }

    /** Classes d'icône Font Awesome venues du serveur : lettres, chiffres, tirets. */
    function classeIcone(valeur, defaut) {
        return (typeof valeur === 'string' && /^[a-z0-9 -]+$/i.test(valeur)) ? valeur : defaut;
    }

    function el(tag, classe, texte) {
        var n = document.createElement(tag);
        if (classe) {
            n.className = classe;
        }
        if (texte !== undefined && texte !== null) {
            n.textContent = String(texte);
        }
        return n;
    }

    function icone(classes) {
        var i = document.createElement('i');
        i.className = classes;
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    function lien(url, libelle, classeIco) {
        var sure = urlSure(url);
        if (!sure) {
            return null;
        }
        var a = el('a', 'ast-link');
        a.href = sure;
        if (new URL(sure).origin !== window.location.origin) {
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
        }
        a.appendChild(icone(classeIco || 'fas fa-arrow-right'));
        a.appendChild(el('span', null, libelle));
        return a;
    }

    /** Couleur sémantique autorisée seulement pour un statut. */
    function ton(valeur) {
        var v = String(valeur || '').toLowerCase();
        if (/(^|\b)(success|valid|activ|oui|pay|publi)/.test(v)) { return 'ok'; }
        if (/(warning|attente|retard|partiel)/.test(v)) { return 'attention'; }
        if (/(danger|rejet|annul|^non$|impay|echec|échec)/.test(v)) { return 'alerte'; }
        return 'neutre';
    }

    function badge(texte, style) {
        return el('span', 'ast-badge ast-badge--' + ton(style || texte), texte);
    }

    function pied(data) {
        var total = data.total_available || data.total_count;
        var url = data.deep_link;
        if (!url && !total) {
            return null;
        }
        var f = el('div', 'ast-rich-foot');
        if (data.total_count && data.total_available && data.total_available > data.total_count) {
            f.appendChild(el('span', 'ast-muted', data.total_count + ' sur ' + data.total_available + ' résultats'));
        } else if (data.total_count) {
            f.appendChild(el('span', 'ast-muted', data.total_count + (data.total_count > 1 ? ' résultats' : ' résultat')));
        }
        var a = lien(url, 'Tout voir dans KLASSCI', 'fas fa-arrow-up-right-from-square');
        if (a) {
            f.appendChild(a);
        }
        return f;
    }

    // ─── Rendus des résultats riches (formes display_data du serveur) ───

    var RENDUS = {
        table: function (data) {
            var wrap = el('div', 'ast-table-wrap');
            var table = el('table', 'ast-table');
            var thead = el('thead');
            var tr = el('tr');
            (data.columns || []).forEach(function (c) { tr.appendChild(el('th', null, c.label)); });
            var aDesActions = (data.rows || []).some(function (r) { return r.actions && r.actions.length; });
            if (aDesActions) {
                var th = el('th');
                th.appendChild(el('span', 'ast-sr', 'Actions'));
                tr.appendChild(th);
            }
            thead.appendChild(tr);
            table.appendChild(thead);
            var tbody = el('tbody');
            (data.rows || []).forEach(function (row) {
                var r = el('tr');
                (row.cells || []).forEach(function (cell) {
                    var td = el('td');
                    if (cell.badge) {
                        td.appendChild(badge(cell.value, cell.badge));
                    } else {
                        td.textContent = cell.value;
                    }
                    r.appendChild(td);
                });
                if (aDesActions) {
                    var tdA = el('td', 'ast-table-actions');
                    (row.actions || []).forEach(function (a) {
                        var l = lien(a.url, a.label, classeIcone(a.icon, 'fas fa-eye'));
                        if (l) { tdA.appendChild(l); }
                    });
                    r.appendChild(tdA);
                }
                tbody.appendChild(r);
            });
            table.appendChild(tbody);
            wrap.appendChild(table);
            var frag = el('div', 'ast-rich-block');
            frag.appendChild(wrap);
            var f = pied(data);
            if (f) { frag.appendChild(f); }
            return frag;
        },

        cards: function (data) {
            var frag = el('div', 'ast-rich-block');
            var grid = el('div', 'ast-cards');
            (data.cards || []).forEach(function (c) {
                var card = el('div', 'ast-card');
                var head = el('div', 'ast-card-head');
                var ini = (c.initials || String(c.title || '?').slice(0, 2)).toUpperCase();
                head.appendChild(el('span', 'ast-card-avatar', ini));
                var titres = el('div', 'ast-card-titles');
                titres.appendChild(el('div', 'ast-card-title', c.title));
                if (c.subtitle) { titres.appendChild(el('div', 'ast-card-sub', c.subtitle)); }
                head.appendChild(titres);
                card.appendChild(head);
                if (c.badges && c.badges.length) {
                    var b = el('div', 'ast-card-badges');
                    c.badges.forEach(function (x) { b.appendChild(badge(x.label, x.style)); });
                    card.appendChild(b);
                }
                if (c.meta && c.meta.length) {
                    var dl = el('dl', 'ast-card-meta');
                    c.meta.forEach(function (m) {
                        var row = el('div');
                        row.appendChild(el('dt', null, m.label));
                        row.appendChild(el('dd', null, m.value));
                        dl.appendChild(row);
                    });
                    card.appendChild(dl);
                }
                (c.actions || []).forEach(function (a) {
                    var l = lien(a.url, a.label, classeIcone(a.icon, 'fas fa-eye'));
                    if (l) { card.appendChild(l); }
                });
                grid.appendChild(card);
            });
            frag.appendChild(grid);
            var f = pied(data);
            if (f) { frag.appendChild(f); }
            return frag;
        },

        'stat-cards': function (data) {
            var frag = el('div', 'ast-rich-block');
            var grid = el('div', 'ast-stats');
            (data.stats || []).forEach(function (s) {
                var card = el('div', 'ast-stat');
                var ico = el('span', 'ast-stat-icon');
                ico.appendChild(icone(classeIcone(s.icon, 'fas fa-chart-simple')));
                card.appendChild(ico);
                var corps = el('div');
                corps.appendChild(el('div', 'ast-stat-value', s.value));
                corps.appendChild(el('div', 'ast-stat-label', s.label));
                if (s.detail) { corps.appendChild(el('div', 'ast-stat-detail', s.detail)); }
                card.appendChild(corps);
                grid.appendChild(card);
            });
            frag.appendChild(grid);
            var f = pied(data);
            if (f) { frag.appendChild(f); }
            return frag;
        },

        'fee-groups': function (data) {
            var frag = el('div', 'ast-rich-block');
            (data.groups || []).forEach(function (g) {
                var section = el('div', 'ast-group');
                var head = el('div', 'ast-group-head');
                head.appendChild(el('span', 'ast-group-title', g.title));
                head.appendChild(el('span', 'ast-chip', g.type === 'mandatory' ? 'Obligatoire' : 'Optionnel'));
                section.appendChild(head);
                var wrap = el('div', 'ast-table-wrap');
                var table = el('table', 'ast-table');
                var thead = el('thead');
                var tr = el('tr');
                var cols = g.type === 'mandatory'
                    ? ['Filière / niveau', 'Affectés', 'Réaffectés', 'Non affectés']
                    : ['Formule', 'Montant'];
                cols.forEach(function (c) { tr.appendChild(el('th', null, c)); });
                thead.appendChild(tr);
                table.appendChild(thead);
                var tbody = el('tbody');
                var lignes = g.type === 'mandatory'
                    ? (g.items || []).map(function (i) { return [i.label, i.affectes, i.reaffectes, i.non_affectes]; })
                    : (g.options || []).map(function (o) { return [o.name, o.montant]; });
                lignes.forEach(function (cells) {
                    var r = el('tr');
                    cells.forEach(function (v, idx) { r.appendChild(el('td', idx ? 'ast-num' : null, v || '—')); });
                    tbody.appendChild(r);
                });
                table.appendChild(tbody);
                wrap.appendChild(table);
                section.appendChild(wrap);
                frag.appendChild(section);
            });
            var f = pied(data);
            if (f) { frag.appendChild(f); }
            return frag;
        },

        'payment-groups': function (data) {
            var frag = el('div', 'ast-rich-block');
            (data.groups || []).forEach(function (g) {
                var insc = g.inscription || {};
                var section = el('div', 'ast-group');
                var head = el('div', 'ast-group-head');
                var t = el('div');
                t.appendChild(el('div', 'ast-group-title', insc.classe || 'Inscription'));
                var sous = [insc.filiere, insc.annee, insc.type].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                if (sous) { t.appendChild(el('div', 'ast-muted', sous)); }
                head.appendChild(t);
                var droite = el('div', 'ast-group-meta');
                droite.appendChild(el('span', 'ast-amount', g.total_paye || '0 FCFA'));
                if (insc.statut) { droite.appendChild(badge(insc.statut)); }
                head.appendChild(droite);
                section.appendChild(head);
                var liste = el('ul', 'ast-list');
                (g.payments || []).forEach(function (p) {
                    var li = el('li');
                    var l1 = el('div', 'ast-list-main');
                    l1.appendChild(el('span', 'ast-amount', p.montant || '0 FCFA'));
                    if (p.statut) { l1.appendChild(badge(p.statut)); }
                    li.appendChild(l1);
                    var det = [p.categorie, p.date, p.mode, p.tranche].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                    if (det) { li.appendChild(el('div', 'ast-muted', det)); }
                    liste.appendChild(li);
                });
                if (!(g.payments || []).length) {
                    liste.appendChild(el('li', 'ast-muted', 'Aucun paiement enregistré'));
                }
                section.appendChild(liste);
                var l = lien(insc.lien, "Voir l'inscription", 'fas fa-file-lines');
                if (l) { section.appendChild(l); }
                frag.appendChild(section);
            });
            var f = pied(data);
            if (f) { frag.appendChild(f); }
            return frag;
        },

        timetable: function (data) {
            var frag = el('div', 'ast-rich-block');
            var head = el('div', 'ast-group-head');
            var t = el('div');
            t.appendChild(el('div', 'ast-group-title', data.classe || 'Emploi du temps'));
            var meta = [data.filiere, data.semestre, data.annee, data.periode].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
            if (meta) { t.appendChild(el('div', 'ast-muted', meta)); }
            head.appendChild(t);
            frag.appendChild(head);
            (data.days || []).forEach(function (d) {
                var jour = el('div', 'ast-day');
                jour.appendChild(el('div', 'ast-day-name', d.jour || 'Jour'));
                var slots = d.slots || [];
                if (!slots.length) {
                    jour.appendChild(el('div', 'ast-muted', 'Aucun cours'));
                }
                slots.forEach(function (s) {
                    var row = el('div', 'ast-slot');
                    row.appendChild(el('span', 'ast-slot-time', s.horaire));
                    var info = el('div');
                    info.appendChild(el('div', 'ast-slot-subject', s.matiere || '—'));
                    var det = [s.enseignant, s.salle].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                    if (det) { info.appendChild(el('div', 'ast-muted', det)); }
                    row.appendChild(info);
                    jour.appendChild(row);
                });
                frag.appendChild(jour);
            });
            var f = pied(data);
            if (f) { frag.appendChild(f); }
            return frag;
        },

        checklist: function (data) {
            var frag = el('div', 'ast-rich-block');
            if (typeof data.progress_percent === 'number') {
                var pct = Math.max(0, Math.min(100, data.progress_percent));
                var barre = el('div', 'ast-progress');
                barre.setAttribute('role', 'progressbar');
                barre.setAttribute('aria-valuenow', String(pct));
                barre.setAttribute('aria-valuemin', '0');
                barre.setAttribute('aria-valuemax', '100');
                var fill = el('span');
                fill.style.width = pct + '%';
                barre.appendChild(fill);
                frag.appendChild(barre);
                frag.appendChild(el('div', 'ast-muted', pct + ' % de la configuration'));
            }
            var icones = { done: 'fas fa-check', next: 'fas fa-arrow-right', blocked: 'fas fa-lock', todo: 'far fa-circle' };
            (data.sections || []).forEach(function (sec) {
                var s = el('div', 'ast-steps');
                if (sec.title) { s.appendChild(el('div', 'ast-steps-title', sec.title)); }
                (sec.steps || []).forEach(function (step) {
                    var etat = icones[step.status] ? step.status : 'todo';
                    var row = el('div', 'ast-step ast-step--' + etat);
                    var ic = el('span', 'ast-step-icon');
                    ic.appendChild(icone(icones[etat]));
                    row.appendChild(ic);
                    var txt = el('div');
                    txt.appendChild(el('div', 'ast-step-title', step.title));
                    if (step.description) { txt.appendChild(el('div', 'ast-muted', step.description)); }
                    var l = lien(step.deep_link, step.action_label || 'Ouvrir');
                    if (l) { txt.appendChild(l); }
                    row.appendChild(txt);
                    s.appendChild(row);
                });
                frag.appendChild(s);
            });
            return frag;
        },

        form: function (data, ctx) {
            var form = el('form', 'ast-form');
            form.noValidate = false;
            if (data.title) { form.appendChild(el('div', 'ast-form-title', data.title)); }
            if (data.description) { form.appendChild(el('p', 'ast-muted', data.description)); }
            (data.fields || []).forEach(function (f) {
                var id = uid('champ');
                var groupe = el('div', 'ast-field' + (f.type === 'checkbox' ? ' ast-field--check' : ''));
                var input;
                if (f.type === 'select') {
                    // Select natif habillé (apparence retirée, chevron maison) : le
                    // formulaire est construit hors d'Alpine, <x-au-select> n'y est pas disponible.
                    input = el('select', 'ast-input ast-select');
                    (f.options || []).forEach(function (o) {
                        var opt = el('option', null, o.label);
                        opt.value = o.value;
                        input.appendChild(opt);
                    });
                } else if (f.type === 'textarea') {
                    input = el('textarea', 'ast-input');
                    input.rows = 3;
                } else {
                    input = el('input', f.type === 'checkbox' ? 'ast-check' : 'ast-input');
                    input.type = ['text', 'number', 'checkbox', 'date', 'email'].indexOf(f.type) >= 0 ? f.type : 'text';
                    ['min', 'max', 'step'].forEach(function (k) { if (f[k] !== undefined) { input[k] = f[k]; } });
                    if (f.type === 'checkbox') { input.value = '1'; }
                }
                input.id = id;
                input.name = f.name;
                if (f.required) { input.required = true; }
                if (f.placeholder) { input.placeholder = f.placeholder; }
                if (f.value !== undefined && f.type !== 'checkbox') { input.value = f.value; }
                var label = el('label', 'ast-label', f.label + (f.required ? ' *' : ''));
                label.htmlFor = id;
                if (f.type === 'checkbox') {
                    groupe.appendChild(input);
                    groupe.appendChild(label);
                } else {
                    groupe.appendChild(label);
                    groupe.appendChild(input);
                }
                if (f.help) { groupe.appendChild(el('small', 'ast-muted', f.help)); }
                form.appendChild(groupe);
            });
            var erreur = el('div', 'ast-form-error');
            erreur.setAttribute('role', 'alert');
            form.appendChild(erreur);
            var bouton = el('button', 'ast-btn ast-btn--primary', data.submit_label || 'Envoyer');
            bouton.type = 'submit';
            form.appendChild(bouton);
            if (data.focus_field) {
                setTimeout(function () {
                    var cible = form.querySelector('[name="' + CSS.escape(data.focus_field) + '"]');
                    if (cible) { cible.focus(); }
                }, 120);
            }
            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var corps = {};
                Array.prototype.forEach.call(form.elements, function (champ) {
                    if (!champ.name) { return; }
                    corps[champ.name] = champ.type === 'checkbox' ? (champ.checked ? 1 : 0) : champ.value;
                });
                Object.keys(data.hidden_fields || {}).forEach(function (k) { corps[k] = data.hidden_fields[k]; });
                bouton.disabled = true;
                erreur.textContent = '';
                ctx.posterFormulaire(data.action_url, corps).then(function (res) {
                    if (res.ok) {
                        form.classList.add('is-sent');
                        bouton.textContent = 'Envoyé';
                    } else {
                        erreur.textContent = res.message;
                        bouton.disabled = false;
                    }
                });
            });
            return form;
        },

        'approval-request': function (data, ctx) {
            var box = el('div', 'ast-approval');
            var head = el('div', 'ast-approval-head');
            head.appendChild(icone('fas fa-shield-halved'));
            head.appendChild(el('span', null, 'Validation requise'));
            box.appendChild(head);
            box.appendChild(el('p', null, data.summary || 'Une action attend votre validation.'));
            if (data.payload) {
                var dl = el('dl', 'ast-card-meta');
                [['Élément', data.payload.name], ['Code', data.payload.code], ['Montant', data.payload.default_amount]]
                    .filter(function (x) { return x[1] !== undefined && x[1] !== null && x[1] !== ''; })
                    .forEach(function (x) {
                        var row = el('div');
                        row.appendChild(el('dt', null, x[0]));
                        row.appendChild(el('dd', null, x[1]));
                        dl.appendChild(row);
                    });
                box.appendChild(dl);
            }
            var etat = el('div', 'ast-approval-state');
            etat.setAttribute('role', 'status');
            var actions = el('div', 'ast-approval-actions');
            var ok = el('button', 'ast-btn ast-btn--primary', 'Approuver et exécuter');
            ok.type = 'button';
            var non = el('button', 'ast-btn ast-btn--ghost', 'Rejeter');
            non.type = 'button';
            actions.appendChild(ok);
            actions.appendChild(non);
            box.appendChild(actions);
            box.appendChild(etat);
            var approval = data.approval || {};
            function decider(url, approuver) {
                ok.disabled = true;
                non.disabled = true;
                etat.textContent = approuver ? 'Exécution…' : 'Rejet…';
                ctx.deciderAction(url, approuver).then(function (res) {
                    etat.textContent = res.message;
                    etat.className = 'ast-approval-state ' + (res.ok ? 'is-ok' : 'is-ko');
                    if (res.ok) {
                        actions.remove();
                    } else {
                        ok.disabled = false;
                        non.disabled = false;
                    }
                });
            }
            ok.addEventListener('click', function () { decider(approval.approve_url, true); });
            non.addEventListener('click', function () { decider(approval.reject_url, false); });
            if (data.status && data.status !== 'proposed') {
                actions.remove();
                etat.textContent = data.status === 'executed' ? 'Action déjà exécutée.' : 'Action close (' + data.status + ').';
            }
            return box;
        }
    };

    var AVEC_PIED = ['table', 'cards', 'fee-groups', 'payment-groups', 'stat-cards', 'timetable'];

    /** Transforme un message enregistré (historique, route JSON) en parties. */
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

    window.klassciAssistant = function () {
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
            controleur: null,
            conversationId: null,
            conversations: [],
            chargementListe: false,
            chargementHistorique: false,
            suivreBas: true,
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
            },

            destroy: function () {
                if (this._mq && this._mq.removeEventListener) { this._mq.removeEventListener('change', this._onMq); }
                if (this.controleur) { this.controleur.abort(); }
                document.documentElement.classList.remove('ast-lock');
            },

            // ─── Ouverture / fermeture ───

            ouvrir: function () {
                var self = this;
                this.ouvert = true;
                this.verrouillerDefilement();
                chargerLibs().then(function () { self.libsPretes = true; }).catch(function () { /* texte échappé en repli */ });
                if (this.conversationId && this.messages.length === 0) {
                    this.chargerConversation(this.conversationId, true);
                }
                this.$nextTick(function () {
                    if (self.$refs.saisie) { self.$refs.saisie.focus(); }
                    self.defiler(true);
                });
            },

            fermer: function () {
                this.ouvert = false;
                this.verrouillerDefilement();
                var self = this;
                this.$nextTick(function () { if (self.$refs.lanceur) { self.$refs.lanceur.focus(); } });
            },

            surEchap: function () {
                if (!this.ouvert) { return; }
                if (this.vue !== 'chat') { this.vue = 'chat'; return; }
                this.fermer();
            },

            verrouillerDefilement: function () {
                var telephone = this._mq && this._mq.matches;
                document.documentElement.classList.toggle('ast-lock', this.ouvert && telephone);
            },

            // ─── Rendu ───

            md: function (texte) {
                // libsPretes est lu ici pour que le rendu se refasse une fois marked chargé.
                if (this.libsPretes && window.marked && window.DOMPurify) {
                    var html = window.marked.parse(String(texte || ''), { gfm: true, breaks: true });
                    return window.DOMPurify.sanitize(html, { USE_PROFILES: { html: true }, FORBID_TAGS: ['style', 'form', 'input', 'img'] });
                }
                return echapper(texte).replace(/\n/g, '<br>');
            },

            rendreRiche: function (conteneur, partie) {
                var rendu = RENDUS[partie.kind];
                if (!rendu || !partie.data) { return; }
                conteneur.textContent = '';
                try {
                    conteneur.appendChild(rendu(partie.data, this));
                } catch (e) {
                    conteneur.appendChild(el('p', 'ast-muted', "Ce résultat n'a pas pu être affiché."));
                }
            },

            montrerLien: function (msg) {
                return !msg.parts.some(function (p) {
                    return p.type === 'rich' && AVEC_PIED.indexOf(p.kind) >= 0 && p.data && p.data.deep_link;
                });
            },

            urlSure: urlSure,

            suites: function (msg) {
                var q = [];
                var a = [];
                msg.parts.forEach(function (p) {
                    if ((p.type === 'rich' || p.type === 'suites') && p.data) {
                        (p.data.follow_up || []).forEach(function (s) { if (typeof s === 'string' && q.indexOf(s) < 0) { q.push(s); } });
                        (p.data.follow_up_actions || []).forEach(function (x) { if (x && x.label) { a.push(x); } });
                    }
                });
                return { questions: q.slice(0, 4), actions: a };
            },

            estDernierAssistant: function (msg) {
                for (var i = this.messages.length - 1; i >= 0; i -= 1) {
                    if (this.messages[i].role === 'assistant') { return this.messages[i] === msg; }
                }
                return false;
            },

            texteDe: function (msg) {
                return msg.parts.filter(function (p) { return p.type === 'text'; }).map(function (p) { return p.text; }).join('\n\n');
            },

            enCours: function (msg) {
                return msg.status === 'streaming';
            },

            // ─── Défilement ───

            surDefilement: function () {
                var f = this.$refs.fil;
                if (!f) { return; }
                this.suivreBas = (f.scrollHeight - f.scrollTop - f.clientHeight) < 80;
            },

            defiler: function (forcer) {
                var self = this;
                if (!forcer && !this.suivreBas) { return; }
                window.requestAnimationFrame(function () {
                    var f = self.$refs.fil;
                    if (f) { f.scrollTop = f.scrollHeight; }
                });
            },

            allerEnBas: function () {
                this.suivreBas = true;
                this.defiler(true);
            },

            // ─── Saisie ───

            ajusterHauteur: function () {
                var t = this.$refs.saisie;
                if (!t) { return; }
                t.style.height = 'auto';
                t.style.height = Math.min(t.scrollHeight, 180) + 'px';
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

                if (!opts.relance) {
                    this.messages.push({ key: uid('m'), role: 'user', text: texte });
                }
                if (texteImpose === undefined) {
                    this.saisie = '';
                    this.$nextTick(this.ajusterHauteur.bind(this));
                }
                this.messages.push({ key: uid('m'), role: 'assistant', parts: [], status: 'streaming', question: texte, copie: false });
                var msg = this.messages[this.messages.length - 1];

                this.envoiEnCours = true;
                this.suivreBas = true;
                this.defiler(true);

                var self = this;
                var diffusion = typeof window.ReadableStream === 'function' && typeof window.TextDecoder === 'function';
                var promesse = diffusion ? this.diffuser(texte, msg) : this.envoyerSansDiffusion(texte, msg);

                promesse.catch(function (e) {
                    if (e && e.name === 'AbortError') {
                        self.cloreTextes(msg);
                        msg.status = 'stopped';
                        return;
                    }
                    self.cloreTextes(msg);
                    msg.parts.push({ key: uid('p'), type: 'error', text: (e && e.messageUtilisateur) || 'La connexion a été interrompue.' });
                    msg.status = 'error';
                }).finally(function () {
                    if (msg.status === 'streaming') { msg.status = 'done'; }
                    self.envoiEnCours = false;
                    self.controleur = null;
                    self.annonce = msg.status === 'error' ? 'Erreur : la réponse n\'a pas abouti.' : self.texteDe(msg).slice(0, 600);
                    self.defiler();
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
                    page_title: document.title.slice(0, 255)
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
                        self.defiler();
                        return lire();
                    });
                }

                return lire();
            },

            /** Applique une partie du protocole UI message stream v1 au message. */
            appliquerPartie: function (msg, partie) {
                var type = partie.type || '';
                var meta = partie.messageMetadata || null;

                if (meta && meta.conversationId) {
                    this.memoriserConversation(meta.conversationId, meta.title);
                }

                if (type === 'text-start') {
                    msg.parts.push({ key: uid('p'), type: 'text', id: partie.id, text: '', fini: false });
                } else if (type === 'text-delta') {
                    var t = this.partieTexte(msg, partie.id);
                    if (!t) {
                        msg.parts.push({ key: uid('p'), type: 'text', id: partie.id, text: '', fini: false });
                        t = msg.parts[msg.parts.length - 1];
                    }
                    t.text += partie.delta || '';
                } else if (type === 'text-end') {
                    var fin = this.partieTexte(msg, partie.id);
                    if (fin) { fin.fini = true; }
                } else if (type === 'data-outil') {
                    var existant = msg.parts.find(function (p) { return p.type === 'outil' && p.id === partie.id; });
                    if (existant) {
                        existant.data = partie.data || {};
                    } else {
                        msg.parts.push({ key: uid('p'), type: 'outil', id: partie.id, data: partie.data || {} });
                    }
                } else if (type === 'data-lien') {
                    if (partie.data && partie.data.url) { msg.parts.push({ key: uid('p'), type: 'lien', url: partie.data.url }); }
                } else if (type === 'data-suites') {
                    msg.parts.push({ key: uid('p'), type: 'suites', data: partie.data || {} });
                } else if (type.indexOf('data-') === 0) {
                    msg.parts.push({ key: uid('p'), type: 'rich', kind: type.slice(5), data: partie.data || {} });
                } else if (type === 'error') {
                    this.cloreTextes(msg);
                    msg.parts.push({ key: uid('p'), type: 'error', text: partie.errorText || 'La réponse n\'a pas pu aboutir.' });
                    msg.status = 'error';
                } else if (type === 'abort') {
                    this.cloreTextes(msg);
                    msg.status = 'stopped';
                } else if (type === 'finish') {
                    this.cloreTextes(msg);
                    if (msg.status === 'streaming') { msg.status = 'done'; }
                }
            },

            partieTexte: function (msg, id) {
                return msg.parts.find(function (p) { return p.type === 'text' && p.id === id; });
            },

            cloreTextes: function (msg) {
                msg.parts.forEach(function (p) {
                    if (p.type === 'text') { p.fini = true; }
                    if (p.type === 'outil' && p.data && p.data.etat === 'en_cours') { p.data = Object.assign({}, p.data, { etat: 'interrompu' }); }
                });
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
                    msg.parts = versParties({ content: json.message, display_type: json.display_type, display_data: json.display_data, deep_link: json.deep_link });
                    msg.status = 'done';
                });
            },

            arreter: function () {
                if (this.controleur) { this.controleur.abort(); }
            },

            relancer: function (msg) {
                if (this.envoiEnCours) { return; }
                var question = msg.question;
                if (!question) {
                    var idx = this.messages.indexOf(msg);
                    for (var i = idx - 1; i >= 0; i -= 1) {
                        if (this.messages[i].role === 'user') { question = this.messages[i].text; break; }
                    }
                }
                if (!question) { return; }
                this.messages.splice(this.messages.indexOf(msg), 1);
                this.envoyer(question, { relance: true });
            },

            copier: function (msg) {
                var texte = this.texteDe(msg);
                if (!texte || !navigator.clipboard) { return; }
                navigator.clipboard.writeText(texte).then(function () {
                    msg.copie = true;
                    setTimeout(function () { msg.copie = false; }, 1600);
                });
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
                return this.getJson(this.cfg.routes.history.replace('__ID__', encodeURIComponent(id))).then(function (json) {
                    self.messages = ((json && json.messages) || []).map(function (m) {
                        return m.role === 'user'
                            ? { key: uid('m'), role: 'user', text: m.content || '' }
                            : { key: uid('m'), role: 'assistant', parts: versParties(m), status: 'done', copie: false };
                    });
                    self.memoriserConversation(id);
                    self.vue = 'chat';
                    self.suivreBas = true;
                    self.defiler(true);
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
                this.messages.push({ key: uid('m'), role: 'assistant', parts: versParties({ content: json.message, display_type: json.display_type, display_data: json.display_data, deep_link: json.deep_link }), status: 'done', copie: false });
                this.suivreBas = true;
                this.defiler(true);
            },

            // ─── Actions des résultats (formulaires, validations, raccourcis) ───

            posterFormulaire: function (url, corps) {
                var self = this;
                if (!urlSure(url)) { return Promise.resolve({ ok: false, message: 'Formulaire indisponible.' }); }
                return this.postJson(url, corps).then(function (r) {
                    if (r.ok) { self.ajouterReponse(r.json); return { ok: true }; }
                    var detail = r.json && r.json.errors ? Object.values(r.json.errors)[0] : null;
                    return { ok: false, message: (Array.isArray(detail) ? detail[0] : detail) || r.message || 'Envoi impossible.' };
                }).catch(function () { return { ok: false, message: 'Connexion interrompue.' }; });
            },

            deciderAction: function (url, approuver) {
                var self = this;
                if (!urlSure(url)) { return Promise.resolve({ ok: false, message: 'Action indisponible.' }); }
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
                        msg.parts.push({ key: uid('p'), type: 'error', text: "Ce formulaire n'est pas disponible." });
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
})();
