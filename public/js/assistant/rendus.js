/*
 * Assistant KLASSCI — Rendus des résultats (widgets) : tableau, graphique, diagramme, indicateurs et formats hérités.
 *
 * Fichier 3/5 de public/js/assistant/, chargés dans l'ordre par le
 * composant resources/views/components/chatbot/assistant.blade.php. Les
 * fichiers partagent l'espace window.KlassciAst.
 */
(function (A) {
    'use strict';

    if (A.rendus) {
        return;
    }

    var PALETTE = A.PALETTE,
        LIB_GRAPHIQUE = A.LIB_GRAPHIQUE,
        chargerScript = A.chargerScript,
        uid = A.uid,
        mouvementReduit = A.mouvementReduit,
        urlSure = A.urlSure,
        classeIcone = A.classeIcone,
        el = A.el,
        icone = A.icone,
        bouton = A.bouton,
        ancre = A.ancre,
        lien = A.lien,
        FORMAT_COMPACT = A.FORMAT_COMPACT,
        nombre = A.nombre,
        montant = A.montant,
        pluriel = A.pluriel,
        copierTexte = A.copierTexte,
        confirmerCopie = A.confirmerCopie,
        csv = A.csv,
        badge = A.badge,
        initiales = A.initiales,
        dessinerDiagramme = A.dessinerDiagramme;
    // ─── Rendus des résultats (widgets) ───

    /** Carte commune : titre, compteur, outils, corps, pied avec total et lien. */
    function carte(options) {
        var racine = el('section', 'ast-w' + (options.classe ? ' ' + options.classe : ''));
        var tete = null;
        if (options.titre || options.outils) {
            tete = el('header', 'ast-w-head');
            var titres = el('div', 'ast-w-titles');
            if (options.icone) {
                var ic = el('span', 'ast-w-icon');
                ic.appendChild(icone(options.icone));
                tete.appendChild(ic);
            }
            if (options.titre) { titres.appendChild(el('h4', 'ast-w-title', options.titre)); }
            if (options.sousTitre) { titres.appendChild(el('div', 'ast-w-sub', options.sousTitre)); }
            tete.appendChild(titres);
            if (options.outils) {
                var outils = el('div', 'ast-w-tools');
                options.outils.forEach(function (o) { outils.appendChild(o); });
                tete.appendChild(outils);
            }
            racine.appendChild(tete);
        }
        var corps = el('div', 'ast-w-body');
        racine.appendChild(corps);
        return { racine: racine, corps: corps, tete: tete };
    }

    function piedCarte(racine, total, affiches, url, libelle) {
        var a = url ? lien(url, libelle || 'Tout voir dans KLASSCI', 'fas fa-arrow-right') : null;
        if (!a && !total) { return false; }
        var f = el('footer', 'ast-w-foot');
        var info = '';
        if (total && affiches && total > affiches) {
            info = affiches + ' sur ' + nombre(total);
        } else if (total) {
            info = pluriel(nombre(total), 'résultat', 'résultats');
        }
        f.appendChild(el('span', 'ast-w-count', info));
        if (a) { f.appendChild(a); }
        racine.appendChild(f);
        return !!a;
    }

    function piedLegacy(racine, data, affiches) {
        return piedCarte(racine, data.total_available || data.total_count, data.total_count || affiches, data.deep_link, null);
    }

    function piedNouveau(racine, data, affiches) {
        var l = data.lien || {};
        return piedCarte(racine, data.total, affiches, l.url, l.libelle);
    }

    /** Montre `premiers` éléments puis un bouton qui déplie le reste. */
    function depliable(elements, premiers, conteneurBouton, libelle) {
        if (elements.length <= premiers + 1) { return; }
        elements.slice(premiers).forEach(function (e) { e.hidden = true; });
        var b = bouton('ast-more', 'fas fa-chevron-down', libelle(elements.length));
        b.setAttribute('aria-expanded', 'false');
        b.addEventListener('click', function () {
            var ouvert = b.getAttribute('aria-expanded') === 'true';
            elements.slice(premiers).forEach(function (e) { e.hidden = ouvert; });
            b.setAttribute('aria-expanded', ouvert ? 'false' : 'true');
            b.querySelector('span').textContent = ouvert ? libelle(elements.length) : 'Réduire';
        });
        conteneurBouton.appendChild(b);
    }

    /**
     * Tableau commun : en-tête collant, première colonne collante, chiffres
     * alignés, 8 lignes puis dépliage, copie au format CSV.
     * colonnes : [{ libelle, num }] ; lignes : [{ cellules:[Node|string], brut:[string] }]
     */
    function tableauCommun(options) {
        var boutonCsv = bouton('ast-tool-btn', 'far fa-copy', null, 'Copier au format CSV');
        var c = carte({ titre: options.titre, icone: 'fas fa-table', outils: [boutonCsv], classe: 'ast-w--table' });
        var defil = el('div', 'ast-tbl-scroll');
        defil.setAttribute('tabindex', '0');
        defil.setAttribute('role', 'region');
        defil.setAttribute('aria-label', options.titre || 'Tableau');
        var table = el('table', 'ast-tbl');
        var thead = el('thead');
        var tr = el('tr');
        options.colonnes.forEach(function (col) {
            var th = el('th', col.num ? 'is-num' : null, col.libelle);
            th.scope = 'col';
            tr.appendChild(th);
        });
        thead.appendChild(tr);
        table.appendChild(thead);
        var tbody = el('tbody');
        var rangs = options.lignes.map(function (ligne) {
            var r = el('tr');
            ligne.cellules.forEach(function (cellule, i) {
                var td = el(i === 0 ? 'th' : 'td', options.colonnes[i] && options.colonnes[i].num ? 'is-num' : null);
                if (i === 0) { td.scope = 'row'; }
                if (cellule && cellule.nodeType) { td.appendChild(cellule); } else { td.textContent = cellule == null || cellule === '' ? '—' : String(cellule); }
                r.appendChild(td);
            });
            tbody.appendChild(r);
            return r;
        });
        table.appendChild(tbody);
        defil.appendChild(table);
        defil.addEventListener('scroll', function () { defil.classList.toggle('is-scrolled', defil.scrollLeft > 2); }, { passive: true });
        c.corps.appendChild(defil);
        if (!rangs.length) { c.corps.appendChild(el('p', 'ast-w-empty', 'Aucune ligne à afficher.')); }
        var bas = el('div', 'ast-w-more');
        depliable(rangs, 8, bas, function (n) { return 'Voir les ' + n + ' lignes'; });
        if (bas.firstChild) { c.corps.appendChild(bas); }
        boutonCsv.addEventListener('click', function () {
            var donnees = [options.colonnes.map(function (col) { return col.libelle; })].concat(options.lignes.map(function (l) { return l.brut; }));
            copierTexte(csv(donnees)).then(function () { confirmerCopie(boutonCsv); });
        });
        return c;
    }

    function celluleTypee(type, valeur) {
        if (type === 'lien' && valeur && typeof valeur === 'object') {
            var a = ancre(valeur.url, 'ast-cell-link');
            if (!a) { return { n: valeur.texte || '—', b: valeur.texte || '' }; }
            a.textContent = valeur.texte || 'Ouvrir';
            return { n: a, b: valeur.texte || '' };
        }
        if (type === 'statut' && valeur && typeof valeur === 'object') {
            return { n: badge(valeur.texte, valeur.ton || valeur.texte), b: valeur.texte || '' };
        }
        if (type === 'montant') {
            var m = montant(valeur);
            return { n: el('span', 'ast-money', m), b: m };
        }
        if (type === 'nombre') {
            var nb = nombre(valeur);
            return { n: el('span', 'ast-nb', nb), b: nb };
        }
        if (type === 'date') {
            return { n: el('span', 'ast-date', valeur), b: valeur == null ? '' : String(valeur) };
        }
        var s = valeur == null ? '' : (typeof valeur === 'object' ? (valeur.texte || '') : String(valeur));
        return { n: s, b: s };
    }

    var ESTIME_MONTANT = /(\d[\d\s  .,]*\s?(F\s?CFA|FCFA|XOF|F)\b)/i;

    // Chaque rendu reçoit (data, ctx, vue) et rend un nœud ; ctx = composant Alpine.
    var RENDUS = {
        tableau: function (data) {
            var colonnes = (data.colonnes || []).map(function (c) {
                return { cle: c.cle, type: c.type, libelle: c.libelle || c.cle, num: c.type === 'montant' || c.type === 'nombre' };
            });
            var lignes = (data.lignes || []).map(function (ligne) {
                var cellules = [];
                var brut = [];
                colonnes.forEach(function (col) {
                    var r = celluleTypee(col.type, ligne[col.cle]);
                    cellules.push(r.n);
                    brut.push(r.b);
                });
                return { cellules: cellules, brut: brut };
            });
            var c = tableauCommun({ titre: data.titre, colonnes: colonnes, lignes: lignes });
            piedNouveau(c.racine, data, lignes.length);
            return c.racine;
        },

        table: function (data) {
            var aDesActions = (data.rows || []).some(function (r) { return r.actions && r.actions.length; });
            var colonnes = (data.columns || []).map(function (col) {
                return { libelle: col.label, num: /montant|reste|pay|total|effectif|affect|moyenne|nombre/i.test(col.label || '') };
            });
            if (aDesActions) { colonnes.push({ libelle: '' }); }
            var lignes = (data.rows || []).map(function (row) {
                var cellules = [];
                var brut = [];
                (row.cells || []).forEach(function (cell) {
                    if (cell.badge) {
                        cellules.push(badge(cell.value, cell.badge));
                    } else if (ESTIME_MONTANT.test(String(cell.value || ''))) {
                        cellules.push(el('span', 'ast-money', cell.value));
                    } else {
                        cellules.push(cell.value);
                    }
                    brut.push(cell.value);
                });
                if (aDesActions) {
                    var actions = el('span', 'ast-cell-actions');
                    (row.actions || []).forEach(function (a) {
                        var l = ancre(a.url, 'ast-cell-link');
                        if (!l) { return; }
                        l.appendChild(el('span', null, a.label));
                        actions.appendChild(l);
                    });
                    cellules.push(actions);
                    brut.push('');
                }
                return { cellules: cellules, brut: brut };
            });
            var c = tableauCommun({ titre: data.title || data.titre, colonnes: colonnes, lignes: lignes });
            piedLegacy(c.racine, data, lignes.length);
            return c.racine;
        },

        /** Liste d'entités (étudiants, débiteurs…) : rangées compactes, pas de grandes cartes. */
        cards: function (data) {
            var cartes = data.cards || [];
            var c = carte({ titre: data.title || data.titre || null, classe: 'ast-w--list' });
            var liste = el('ul', 'ast-rows');
            var items = cartes.map(function (card) {
                var meta = (card.meta || []).slice();
                var cleMontant = -1;
                meta.forEach(function (m, i) {
                    if (cleMontant < 0 && /(reste|solde|dû|du$|impay|montant)/i.test(m.label || '')) { cleMontant = i; }
                });
                if (cleMontant < 0) {
                    meta.forEach(function (m, i) { if (cleMontant < 0 && ESTIME_MONTANT.test(String(m.value || ''))) { cleMontant = i; } });
                }
                var principal = cleMontant >= 0 ? meta[cleMontant] : null;
                var cleTaux = -1;
                meta.forEach(function (m, i) { if (cleTaux < 0 && i !== cleMontant && /^\s*\d{1,3}([.,]\d+)?\s?%\s*$/.test(String(m.value || ''))) { cleTaux = i; } });
                var taux = cleTaux >= 0 ? parseFloat(String(meta[cleTaux].value).replace(',', '.')) : null;
                var reste = meta.filter(function (m, i) { return i !== cleMontant && i !== cleTaux; });

                var li = el('li', 'ast-row');
                var premiere = (card.actions || []).map(function (a) { return urlSure(a.url) ? a : null; }).filter(Boolean)[0];
                var cible = premiere ? ancre(premiere.url, 'ast-row-in') : el('div', 'ast-row-in');
                if (premiere) { cible.setAttribute('aria-label', (card.title || '') + ' — ' + (premiere.label || 'Ouvrir')); }
                cible.appendChild(el('span', 'ast-avatar', card.initials ? String(card.initials).toUpperCase().slice(0, 2) : initiales(card.title)));
                var centre = el('div', 'ast-row-main');
                // Le nom a toute la largeur ; les badges passent sur la ligne d'en dessous,
                // sinon un panneau de 440 px ne laisse que quelques lettres du nom.
                var l1 = el('div', 'ast-row-title');
                var nom = el('span', 'ast-row-name', card.title);
                nom.title = card.title || '';
                l1.appendChild(nom);
                centre.appendChild(l1);
                var badges = (card.badges || []).slice(0, 2);
                var sous = [card.subtitle].concat(reste.map(function (m) { return (m.label ? m.label + ' ' : '') + m.value; })).filter(Boolean);
                if (badges.length || sous.length) {
                    var l2 = el('div', 'ast-row-sub');
                    badges.forEach(function (b) { l2.appendChild(badge(b.label, b.style)); });
                    if (sous.length) { l2.appendChild(el('span', 'ast-row-subtxt', sous.join(' · '))); }
                    centre.appendChild(l2);
                }
                cible.appendChild(centre);
                var droite = el('div', 'ast-row-side');
                if (principal) {
                    var somme = el('span', 'ast-row-amount', principal.value);
                    if (principal.label) { somme.title = principal.label; somme.setAttribute('aria-label', principal.label + ' ' + principal.value); }
                    droite.appendChild(somme);
                }
                if (taux !== null && isFinite(taux)) {
                    var barre = el('span', 'ast-row-bar');
                    barre.setAttribute('role', 'img');
                    barre.setAttribute('aria-label', meta[cleTaux].label + ' ' + meta[cleTaux].value);
                    var remplissage = el('span');
                    remplissage.style.width = Math.max(2, Math.min(100, taux)) + '%';
                    barre.appendChild(remplissage);
                    var ligneTaux = el('span', 'ast-row-rate');
                    ligneTaux.appendChild(barre);
                    ligneTaux.appendChild(el('span', 'ast-row-pct', Math.round(taux) + ' %'));
                    droite.appendChild(ligneTaux);
                }
                cible.appendChild(droite);
                if (premiere) { cible.appendChild(icone('fas fa-chevron-right ast-row-go')); }
                li.appendChild(cible);
                liste.appendChild(li);
                return li;
            });
            c.corps.appendChild(liste);
            if (!items.length) { c.corps.appendChild(el('p', 'ast-w-empty', 'Aucun résultat.')); }
            var bas = el('div', 'ast-w-more');
            depliable(items, 6, bas, function (n) { return 'Voir les ' + n; });
            if (bas.firstChild) { c.corps.appendChild(bas); }
            piedLegacy(c.racine, data, items.length);
            return c.racine;
        },

        kpis: function (data) {
            var grille = el('div', 'ast-kpis');
            (data.elements || []).forEach(function (k) {
                var tuile = urlSure(k.url) ? ancre(k.url, 'ast-kpi is-link') : el('div', 'ast-kpi');
                tuile.appendChild(el('span', 'ast-kpi-label', k.libelle));
                var v = el('span', 'ast-kpi-value');
                v.appendChild(document.createTextNode(typeof k.valeur === 'number' ? nombre(k.valeur) : String(k.valeur == null ? '—' : k.valeur)));
                if (k.unite) { v.appendChild(el('small', null, k.unite)); }
                tuile.appendChild(v);
                if (k.repere) {
                    var t = k.ton === 'succes' ? 'ok' : (k.ton === 'danger' ? 'alerte' : 'neutre');
                    tuile.appendChild(el('span', 'ast-kpi-ref ast-kpi-ref--' + t, k.repere));
                }
                grille.appendChild(tuile);
            });
            if (!data.titre && !data.lien) { return grille; }
            var c = carte({ titre: data.titre, classe: 'ast-w--kpis' });
            c.corps.appendChild(grille);
            piedNouveau(c.racine, data, 0);
            return c.racine;
        },

        'stat-cards': function (data) {
            var grille = el('div', 'ast-kpis');
            (data.stats || []).forEach(function (s) {
                var tuile = el('div', 'ast-kpi');
                var tete = el('span', 'ast-kpi-label');
                tete.appendChild(icone(classeIcone(s.icon, 'fas fa-chart-simple') + ' ast-kpi-ic'));
                tete.appendChild(document.createTextNode(s.label || ''));
                tuile.appendChild(tete);
                tuile.appendChild(el('span', 'ast-kpi-value', s.value));
                if (s.detail) { tuile.appendChild(el('span', 'ast-kpi-ref ast-kpi-ref--neutre', s.detail)); }
                grille.appendChild(tuile);
            });
            var frag = el('div', 'ast-w-bare');
            frag.appendChild(grille);
            if (data.deep_link || data.total_count) {
                var c = carte({});
                c.corps.appendChild(grille);
                piedLegacy(c.racine, data, 0);
                return c.racine;
            }
            return frag;
        },

        graphique: function (data, ctx, vue) {
            var c = carte({ titre: data.titre || 'Graphique', icone: 'fas fa-chart-column', classe: 'ast-w--chart' });
            var zone = el('div', 'ast-chart');
            var canvas = el('canvas');
            canvas.setAttribute('role', 'img');
            canvas.setAttribute('aria-label', (data.titre || 'Graphique') + ' : ' + (data.series || []).map(function (s) { return s.nom; }).join(', '));
            zone.appendChild(canvas);
            var attente = el('div', 'ast-chart-wait');
            attente.appendChild(el('span', 'ast-shimmer', 'Préparation du graphique…'));
            zone.appendChild(attente);
            c.corps.appendChild(zone);
            piedNouveau(c.racine, data, 0);
            var detruit = false;
            var graphique = null;
            vue.aNettoyer(function () { detruit = true; if (graphique) { graphique.destroy(); graphique = null; } });
            chargerScript(LIB_GRAPHIQUE).then(function (Chart) {
                if (detruit) { return; }
                attente.remove();
                graphique = new Chart(canvas, configGraphique(data, Chart));
            }).catch(function () {
                attente.textContent = 'Graphique indisponible.';
            });
            return c.racine;
        },

        diagramme: function (data) {
            var c = carte({ titre: data.titre || null, classe: 'ast-w--diagram' });
            var bloc = el('div', 'ast-mmd');
            var src = el('pre', 'ast-mmd-src', data.mermaid || '');
            src.hidden = true;
            bloc.appendChild(src);
            var attente = el('div', 'ast-mmd-attente');
            attente.appendChild(el('span', 'ast-shimmer', 'Préparation du diagramme…'));
            bloc.appendChild(attente);
            c.corps.appendChild(bloc);
            setTimeout(function () { dessinerDiagramme(bloc); }, 0);
            piedNouveau(c.racine, data, 0);
            return c.racine;
        },

        'fee-groups': function (data) {
            var pile = el('div', 'ast-w-stack');
            (data.groups || []).forEach(function (g) {
                var obligatoire = g.type === 'mandatory';
                var cols = obligatoire
                    ? [{ libelle: 'Filière / niveau' }, { libelle: 'Affectés', num: true }, { libelle: 'Réaffectés', num: true }, { libelle: 'Non affectés', num: true }]
                    : [{ libelle: 'Formule' }, { libelle: 'Montant', num: true }];
                var lignes = obligatoire
                    ? (g.items || []).map(function (i) { return [i.label, i.affectes, i.reaffectes, i.non_affectes]; })
                    : (g.options || []).map(function (o) { return [o.name, o.montant]; });
                var c = carte({ titre: g.title, icone: 'fas fa-tags', sousTitre: obligatoire ? 'Frais obligatoire' : 'Frais optionnel', classe: 'ast-w--fees' });
                var table = el('table', 'ast-tbl ast-tbl--compact');
                var thead = el('thead');
                var tr = el('tr');
                cols.forEach(function (col) { tr.appendChild(el('th', col.num ? 'is-num' : null, col.libelle)); });
                thead.appendChild(tr);
                table.appendChild(thead);
                var tbody = el('tbody');
                lignes.forEach(function (cells) {
                    var r = el('tr');
                    cells.forEach(function (v, idx) { r.appendChild(el('td', idx ? 'is-num ast-money' : null, v || '—')); });
                    tbody.appendChild(r);
                });
                table.appendChild(tbody);
                var defil = el('div', 'ast-tbl-scroll');
                defil.appendChild(table);
                c.corps.appendChild(defil);
                pile.appendChild(c.racine);
            });
            var fin = el('div');
            piedLegacy(fin, data, 0);
            if (fin.firstChild) { pile.appendChild(fin.firstChild); }
            return pile;
        },

        'payment-groups': function (data) {
            var pile = el('div', 'ast-w-stack');
            (data.groups || []).forEach(function (g) {
                var insc = g.inscription || {};
                var sous = [insc.filiere, insc.annee, insc.type].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                var total = el('div', 'ast-w-total');
                total.appendChild(el('span', 'ast-money', g.total_paye || '0 FCFA'));
                total.appendChild(el('span', 'ast-w-total-label', 'payé'));
                var c = carte({ titre: insc.classe || 'Inscription', sousTitre: sous || null, icone: 'fas fa-receipt', outils: [total], classe: 'ast-w--payments' });
                if (insc.statut) { c.tete.querySelector('.ast-w-titles').appendChild(badge(insc.statut)); }
                var liste = el('ul', 'ast-lines');
                (g.payments || []).forEach(function (p) {
                    var li = el('li', 'ast-line');
                    var gauche = el('div', 'ast-line-main');
                    gauche.appendChild(el('span', 'ast-line-title', p.categorie && p.categorie !== 'N/A' ? p.categorie : 'Paiement'));
                    var det = [p.date, p.mode, p.tranche].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                    if (det) { gauche.appendChild(el('span', 'ast-line-sub', det)); }
                    li.appendChild(gauche);
                    var droite = el('div', 'ast-line-side');
                    droite.appendChild(el('span', 'ast-money', p.montant || '0 FCFA'));
                    if (p.statut) { droite.appendChild(badge(p.statut)); }
                    li.appendChild(droite);
                    liste.appendChild(li);
                });
                if (!(g.payments || []).length) { liste.appendChild(el('li', 'ast-w-empty', 'Aucun paiement enregistré')); }
                c.corps.appendChild(liste);
                piedCarte(c.racine, 0, 0, insc.lien, "Voir l'inscription");
                pile.appendChild(c.racine);
            });
            var fin = el('div');
            piedLegacy(fin, data, 0);
            if (fin.firstChild) { pile.appendChild(fin.firstChild); }
            return pile;
        },

        timetable: function (data) {
            var meta = [data.filiere, data.semestre, data.annee, data.periode].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
            var c = carte({ titre: data.classe || 'Emploi du temps', sousTitre: meta || null, icone: 'far fa-calendar', classe: 'ast-w--timetable' });
            (data.days || []).forEach(function (d) {
                var jour = el('div', 'ast-day');
                jour.appendChild(el('div', 'ast-day-name', d.jour || 'Jour'));
                var slots = d.slots || [];
                if (!slots.length) { jour.appendChild(el('div', 'ast-day-empty', 'Aucun cours')); }
                slots.forEach(function (s) {
                    var row = el('div', 'ast-slot');
                    row.appendChild(el('span', 'ast-slot-time', s.horaire));
                    var info = el('div', 'ast-slot-info');
                    info.appendChild(el('div', 'ast-slot-subject', s.matiere || '—'));
                    var det = [s.enseignant, s.salle].filter(function (v) { return v && v !== 'N/A'; }).join(' · ');
                    if (det) { info.appendChild(el('div', 'ast-slot-sub', det)); }
                    row.appendChild(info);
                    jour.appendChild(row);
                });
                c.corps.appendChild(jour);
            });
            piedLegacy(c.racine, data, 0);
            return c.racine;
        },

        checklist: function (data) {
            var c = carte({ titre: data.title || 'Configuration', icone: 'fas fa-list-check', classe: 'ast-w--checklist' });
            if (typeof data.progress_percent === 'number') {
                var pct = Math.max(0, Math.min(100, data.progress_percent));
                var ligne = el('div', 'ast-progress-line');
                var barre = el('div', 'ast-progress');
                barre.setAttribute('role', 'progressbar');
                barre.setAttribute('aria-valuenow', String(pct));
                barre.setAttribute('aria-valuemin', '0');
                barre.setAttribute('aria-valuemax', '100');
                var fill = el('span');
                fill.style.width = pct + '%';
                barre.appendChild(fill);
                ligne.appendChild(barre);
                ligne.appendChild(el('span', 'ast-progress-pct', pct + ' %'));
                c.corps.appendChild(ligne);
            }
            var icones = { done: 'fas fa-check', next: 'fas fa-arrow-right', blocked: 'fas fa-lock', todo: 'far fa-circle' };
            (data.sections || []).forEach(function (sec) {
                var s = el('div', 'ast-check-sec');
                if (sec.title) { s.appendChild(el('div', 'ast-check-title', sec.title)); }
                (sec.steps || []).forEach(function (step) {
                    var etat = icones[step.status] ? step.status : 'todo';
                    var row = el('div', 'ast-check ast-check--' + etat);
                    var ic = el('span', 'ast-check-icon');
                    ic.appendChild(icone(icones[etat]));
                    row.appendChild(ic);
                    var txt = el('div', 'ast-check-txt');
                    txt.appendChild(el('div', 'ast-check-name', step.title));
                    if (step.description) { txt.appendChild(el('div', 'ast-check-sub', step.description)); }
                    var l = lien(step.deep_link, step.action_label || 'Ouvrir');
                    if (l) { txt.appendChild(l); }
                    row.appendChild(txt);
                    s.appendChild(row);
                });
                c.corps.appendChild(s);
            });
            return c.racine;
        },

        form: function (data, ctx) {
            var form = el('form', 'ast-w ast-form');
            form.noValidate = false;
            if (data.title) { form.appendChild(el('div', 'ast-form-title', data.title)); }
            if (data.description) { form.appendChild(el('p', 'ast-form-desc', data.description)); }
            (data.fields || []).forEach(function (f) {
                var id = uid('champ');
                var groupe = el('div', 'ast-field' + (f.type === 'checkbox' ? ' ast-field--check' : ''));
                var input;
                if (f.type === 'select') {
                    // Select natif habillé (apparence retirée, chevron maison) : le
                    // formulaire est construit hors d'Alpine, le composant premium n'y est pas disponible.
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
                    input = el('input', f.type === 'checkbox' ? 'ast-check-input' : 'ast-input');
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
                if (f.help) { groupe.appendChild(el('small', 'ast-field-help', f.help)); }
                form.appendChild(groupe);
            });
            var erreur = el('div', 'ast-form-error');
            erreur.setAttribute('role', 'alert');
            form.appendChild(erreur);
            var envoi = el('button', 'ast-btn ast-btn--primary', data.submit_label || 'Envoyer');
            envoi.type = 'submit';
            form.appendChild(envoi);
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
                envoi.disabled = true;
                erreur.textContent = '';
                ctx.posterFormulaire(data.action_url, corps).then(function (res) {
                    if (res.ok) {
                        form.classList.add('is-sent');
                        envoi.textContent = 'Envoyé';
                    } else {
                        erreur.textContent = res.message;
                        envoi.disabled = false;
                    }
                });
            });
            return form;
        },

        'approval-request': function (data, ctx) {
            var box = el('div', 'ast-w ast-approval');
            var head = el('div', 'ast-approval-head');
            head.appendChild(icone('fas fa-shield-halved'));
            head.appendChild(el('span', null, 'Validation requise'));
            box.appendChild(head);
            box.appendChild(el('p', null, data.summary || 'Une action attend votre validation.'));
            if (data.payload) {
                var dl = el('dl', 'ast-kv');
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
    RENDUS.proposal = RENDUS['approval-request'];

    /**
     * Proposition de l'assistant (outil proposer_*) : ce qui sera écrit, ligne à
     * ligne, avec « Valider » et « Refuser ». Rien n'est enregistré avant le clic ;
     * le serveur revérifie tout et refuse si les données ont changé entre-temps.
     */
    var ETATS_PROPOSITION = {
        en_attente: null,
        executee: 'Enregistré.',
        refusee: 'Refusée : rien n\'a été enregistré.',
        expiree: 'Expirée : demandez une nouvelle proposition.',
        perimee: 'Les données ont changé : rien n\'a été enregistré.',
        traitee: 'Déjà traitée.',
        echec: 'Échec : rien n\'a été modifié.'
    };
    RENDUS.approbation = function (data, ctx) {
        var c = carte({ titre: data.titre || 'Proposition', sousTitre: data.resume || null, icone: 'fas fa-pen-to-square', classe: 'ast-approval ast-prop' + (data.risque === 'eleve' ? ' ast-prop--eleve' : '') });
        var ruban = el('div', 'ast-prop-ruban');
        ruban.appendChild(icone('fas fa-shield-halved'));
        ruban.appendChild(el('span', null, 'Rien n\'est enregistré tant que vous n\'avez pas validé'));
        c.corps.appendChild(ruban);
        if ((data.colonnes || []).length && (data.lignes || []).length) {
            var cols = data.colonnes.map(function (libelle, i) { return { cle: 'c' + i, libelle: libelle }; });
            c.corps.appendChild(RENDUS.tableau({
                colonnes: cols,
                lignes: data.lignes.map(function (ligne) {
                    var o = {};
                    cols.forEach(function (col, i) { o[col.cle] = ligne[i]; });
                    return o;
                })
            }));
        }
        if ((data.avertissements || []).length) {
            var alertes = el('ul', 'ast-prop-alertes');
            data.avertissements.forEach(function (a) {
                var li = el('li');
                li.appendChild(icone('fas fa-triangle-exclamation'));
                li.appendChild(el('span', null, a));
                alertes.appendChild(li);
            });
            c.corps.appendChild(alertes);
        }
        var etat = el('div', 'ast-approval-state');
        etat.setAttribute('role', 'status');
        var actions = el('div', 'ast-approval-actions');
        var ok = el('button', 'ast-btn ast-btn--primary', 'Valider');
        ok.type = 'button';
        var non = el('button', 'ast-btn ast-btn--ghost', 'Refuser');
        non.type = 'button';
        actions.appendChild(ok);
        actions.appendChild(non);
        c.corps.appendChild(actions);
        c.corps.appendChild(etat);

        function clore(statut, message, lienResultat) {
            actions.remove();
            etat.textContent = message || ETATS_PROPOSITION[statut] || '';
            etat.className = 'ast-approval-state ' + (statut === 'executee' ? 'is-ok' : 'is-ko');
            var l = lienResultat ? lien(lienResultat, 'Voir') : null;
            if (l) { etat.appendChild(document.createTextNode(' ')); etat.appendChild(l); }
            c.racine.classList.add('is-close');
        }
        function envoyer(url, corps, attente) {
            ok.disabled = true;
            non.disabled = true;
            etat.className = 'ast-approval-state';
            etat.textContent = attente;
            ctx.repondreProposition(url, corps).then(function (res) {
                if (res.ok || ETATS_PROPOSITION[res.statut] !== undefined) {
                    clore(res.statut, res.message, res.lien);
                } else {
                    // Refus récupérable (réseau, droit) : on laisse la main.
                    etat.textContent = res.message;
                    etat.className = 'ast-approval-state is-ko';
                    ok.disabled = false;
                    non.disabled = false;
                }
            });
        }
        ok.addEventListener('click', function () { envoyer(data.valider_url, { jeton: data.jeton }, 'Enregistrement…'); });
        non.addEventListener('click', function () { envoyer(data.refuser_url, {}, 'Refus…'); });
        if (data.etat && data.etat !== 'en_attente') {
            clore(data.etat, null, null);
        }
        return c.racine;
    };

    /** Clés qui portent un lien de pied : le bouton « Ouvrir la page » du message devient alors redondant. */
    function widgetALien(kind, data) {
        if (!data) { return false; }
        if (data.lien && data.lien.url) { return true; }
        return ['table', 'cards', 'fee-groups', 'payment-groups', 'stat-cards', 'timetable'].indexOf(kind) >= 0 && !!data.deep_link;
    }

    function configGraphique(data, Chart) {
        var type = data.type === 'courbe' ? 'line' : (data.type === 'anneau' ? 'doughnut' : 'bar');
        var unite = data.unite || null;
        var reduit = mouvementReduit();
        var police = getComputedStyle(document.body).fontFamily;
        if (Chart.defaults && Chart.defaults.font) { Chart.defaults.font.family = police; }
        var series = (data.series || []).map(function (s, i) {
            var couleur = PALETTE[i % PALETTE.length];
            var jeu = { label: s.nom, data: (s.valeurs || []).map(Number) };
            if (type === 'doughnut') {
                jeu.backgroundColor = (s.valeurs || []).map(function (v, j) { return PALETTE[j % PALETTE.length]; });
                jeu.borderColor = '#fff';
                jeu.borderWidth = 2;
            } else if (type === 'line') {
                jeu.borderColor = couleur;
                jeu.backgroundColor = i === 0 ? 'rgba(4,83,203,.08)' : 'transparent';
                jeu.fill = i === 0;
                jeu.tension = 0.35;
                jeu.pointRadius = 3;
                jeu.pointBackgroundColor = couleur;
                jeu.borderWidth = 2;
            } else {
                jeu.backgroundColor = couleur;
                jeu.borderRadius = 6;
                jeu.maxBarThickness = 36;
            }
            return jeu;
        });
        function fmt(v) { return unite === 'FCFA' ? montant(v, 'FCFA') : nombre(v) + (unite ? ' ' + unite : ''); }
        var axes = type === 'doughnut' ? {} : {
            x: { grid: { display: false }, border: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
            y: {
                beginAtZero: true,
                grid: { color: '#eef2f7' },
                border: { display: false },
                ticks: { color: '#64748b', font: { size: 11 }, maxTicksLimit: 5, callback: function (v) { return FORMAT_COMPACT.format(v); } }
            }
        };
        return {
            type: type,
            data: { labels: data.libelles || [], datasets: series },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reduit ? false : { duration: 500 },
                cutout: type === 'doughnut' ? '62%' : undefined,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: series.length > 1 || type === 'doughnut', position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, color: '#475569', padding: 14 } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { weight: '600' },
                        callbacks: {
                            label: function (c) {
                                var v = type === 'doughnut' ? c.parsed : c.parsed.y;
                                return ' ' + (c.dataset.label ? c.dataset.label + ' : ' : '') + fmt(v);
                            }
                        }
                    }
                },
                scales: axes
            }
        };
    }

    A.RENDUS = RENDUS;
    A.widgetALien = widgetALien;
    A.rendus = true;
})(window.KlassciAst = window.KlassciAst || {});
