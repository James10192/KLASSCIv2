(function () {
    'use strict';

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const $ = (id) => document.getElementById(id);

    /** Remplace un noeud par son clone (donc sans ecouteurs) et rend le clone. */
    function remplacer(id) {
        const vieux = $(id);
        if (!vieux) return null;
        const neuf = vieux.cloneNode(true);
        vieux.parentNode.replaceChild(neuf, vieux);
        return neuf;
    }

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function fmt(n) {
        return Number(n || 0).toLocaleString('fr-FR');
    }

    // Quand la regeneration porte sur plusieurs inscriptions, le nom de
    // l'etudiant est la seule chose qui distingue deux lignes identiques.
    function qui(l) {
        return l.etudiant ? '  ' + esc(l.etudiant) : '';
    }

    function texte(html) {
        return '<p>' + html + '</p>';
    }

    /** Une ligne cochable du journal. */
    function rangee(cle, corps, cochee, note) {
        return '<label class="rf-row">'
            + '<input type="checkbox" value="' + esc(cle) + '"' + (cochee ? ' checked' : '') + '>'
            + '<span class="rf-row-body">' + corps
            + (note ? '<span class="rf-row-note rf-line-warn">' + note + '</span>' : '')
            + '</span></label>';
    }

    function renderPreview(res) {
        const add = res.lignes || [];
        const del = res.lignes_retrait || [];
        const adj = res.lignes_ajustement || [];
        const totalAdd = res.total_ajouter != null ? res.total_ajouter : add.length;
        const totalDel = res.total_retirer != null ? res.total_retirer : del.length;
        const totalAdj = res.total_ajuster != null ? res.total_ajuster : adj.length;
        // Ce qui a ete TROUVE, distinct de ce qui est applicable sans cocher.
        // Un montant retouche a la main est detecte mais protege : le compter
        // dans l'etat vide faisait repondre « les frais sont a jour » a une ecole
        // qui avait justement des corrections devant elle, et la ligne n'etait
        // alors jamais rendue, donc jamais cochable.
        const totalAdjVus = res.total_ajuster_detecte != null
            ? res.total_ajuster_detecte
            : Math.max(totalAdj, adj.length);

        if (!totalAdd && !totalDel && !totalAdjVus) {
            return texte('<span class="rf-line-muted">$ aucun écart — les frais sont à jour</span>');
        }

        const out = [texte('<span class="rf-line-muted">$ dry-run</span>')];

        add.forEach((l) => {
            out.push(rangee(l.cle,
                '<span class="rf-line-add">+ ' + esc(l.categorie || '—') + '   ' + fmt(l.montant) + ' F' + qui(l) + '</span>',
                true, null));
        });

        adj.forEach((l) => {
            // Le tarif a bouge depuis l'inscription : on montre l'ancien ET le
            // nouveau, sinon « ajuste » ne veut rien dire pour la caisse.
            const corps = '<span class="rf-line-adj">≠ ' + esc(l.categorie || '—') + '   '
                + fmt(l.montant_actuel) + ' F → ' + fmt(l.montant) + ' F' + qui(l) + '</span>';

            let note = null;
            if (l.montant_deja_retouche) {
                // Ce montant porte une decision — remise, bourse, arrangement.
                // On ne la remplace pas d'un clic : la case reste decochee.
                note = '! montant déjà retouché à la main'
                    + (l.retouche_le ? ' le ' + esc(l.retouche_le) : '')
                    + ' — décoché par précaution';
            } else if (l.cree_une_dette) {
                note = '! avait soldé — recrée une dette de ' + fmt(l.restera_du) + ' F';
            } else if (l.trop_percu) {
                note = '! déjà payé ' + fmt(l.deja_paye) + ' F — trop-perçu';
            }

            out.push(rangee(l.cle, corps, !l.montant_deja_retouche, note));
        });

        del.forEach((l) => {
            out.push(rangee(l.cle,
                '<span class="rf-line-del">− ' + esc(l.categorie || '—') + '   ' + fmt(l.montant)
                + ' F  #' + esc(l.motif || 'retrait') + qui(l) + '</span>',
                true, null));
        });

        if (res.tronque) {
            out.push(texte('<span class="rf-line-muted">… liste tronquée : les totaux restent exacts, mais seules les lignes affichées peuvent être décochées</span>'));
        }

        const resume = [];
        if (totalAdd) resume.push(totalAdd + ' ajout(s)');
        if (totalAdj) resume.push(totalAdj + ' montant(s) à mettre à jour');
        if (totalDel) resume.push(totalDel + ' retrait(s)');
        out.push(texte('<span class="rf-line-muted">$ ' + resume.join(', ') + ' sur ' + esc(res.inscriptions || 0) + ' inscription(s)</span>'));

        return out.join('');
    }

    async function post(url, formData) {
        const r = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        return r.json();
    }

    window.KlassciRegenererFrais = {
        open: async function (opts) {
            const modalEl = $('rf-modal');
            if (!modalEl || typeof bootstrap === 'undefined') {
                return;
            }
            const term = $('rf-term');
            const bar = $('rf-bar');
            const alerte = $('rf-alert');
            const compteur = $('rf-count');

            // Le bouton et la case « tout selectionner » sont remplaces par un
            // clone AVANT toute capture : c'est ce qui jette les ecouteurs de
            // l'ouverture precedente. Les capturer d'abord puis les cloner
            // laissait `rafraichir()` piloter un noeud detache — le bouton
            // visible ne se desactivait alors plus jamais, et decocher toutes
            // les lignes appliquait tout.
            const toggleAll = remplacer('rf-toggle-all');
            const ok = remplacer('rf-confirm');

            const formData = opts.formData instanceof FormData ? opts.formData : new FormData();
            if (!formData.has('_token')) formData.append('_token', csrf());

            term.innerHTML = texte('<span class="rf-line-muted">$ preview…</span>');
            bar.hidden = true;
            alerte.hidden = true;
            ok.disabled = true;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            let preview;
            try {
                preview = await post(opts.previewUrl, formData);
            } catch (e) {
                term.innerHTML = texte('<span class="rf-line-del">$ impossible de prévisualiser</span>');
                return;
            }

            // Un refus du serveur (portee impossible, droits) arrive en JSON avec
            // un `message` mais sans `success` : sans ce test il serait lu comme
            // un apercu vide, et l'ecran aurait annonce « aucun ecart ».
            if (!preview || preview.success !== true) {
                term.innerHTML = texte('<span class="rf-line-del">$ '
                    + esc((preview && preview.message) || 'la régénération a été refusée')
                    + '</span>');
                return;
            }

            term.innerHTML = renderPreview(preview);

            const cases = Array.from(term.querySelectorAll('.rf-row input[type="checkbox"]'));
            // Meme mesure que l'etat vide de renderPreview : ce qui a ete
            // DETECTE, pas seulement ce qui s'applique sans cocher. Sinon le
            // sous-titre annonce « Rien a appliquer » au-dessus d'une liste de
            // lignes protegees que l'utilisateur peut justement cocher.
            const detectes = preview.total_ajuster_detecte != null
                ? preview.total_ajuster_detecte
                : preview.total_ajuster;
            const vide = !preview.total_ajouter && !preview.total_retirer && !detectes;

            // Sous-titre pose ici pour l'etat vide seulement : quand il y a des
            // lignes, c'est rafraichir() qui l'ecrit, a chaque coche. Annoncer le
            // total detecte juste au-dessus du bouton de confirmation laissait
            // croire qu'on allait tout appliquer, alors que les lignes protegees
            // arrivent decochees et ne partiront pas.
            if (vide) {
                $('rf-modal-sub').textContent = 'Rien à appliquer';
            }

            if (preview.retouches) {
                alerte.hidden = false;
                alerte.innerHTML = '<i class="fas fa-triangle-exclamation" style="margin-top:2px;"></i><span>'
                    + '<strong>' + esc(preview.retouches) + ' montant(s) ont déjà été retouchés à la main</strong> — '
                    + 'une remise, une bourse ou un arrangement. Ils sont laissés décochés : '
                    + 'cochez-les seulement si vous voulez revenir au barème.</span>';
            }

            function rafraichirBouton() {
                const coches = cases.filter((c) => c.checked).length;
                ok.disabled = vide || (cases.length > 0 && coches === 0);
            }

            function rafraichir() {
                const coches = cases.filter((c) => c.checked).length;
                compteur.textContent = coches + ' sélectionnée(s) sur ' + cases.length;
                toggleAll.checked = coches === cases.length && cases.length > 0;
                toggleAll.indeterminate = coches > 0 && coches < cases.length;

                // Ce que le bouton va REELLEMENT ecrire, et rien d'autre.
                const dossiers = new Set(cases.filter((c) => c.checked)
                    .map((c) => String(c.value).split(':')[1] || c.value));
                $('rf-modal-sub').textContent = coches === 0
                    ? 'Aucune ligne cochée — rien ne sera écrit'
                    : (coches + ' ligne(s) sur ' + dossiers.size + ' dossier(s)');

                rafraichirBouton();
            }

            if (cases.length) {
                bar.hidden = false;
                cases.forEach((c) => c.addEventListener('change', rafraichir));
                toggleAll.addEventListener('change', function () {
                    cases.forEach((c) => { c.checked = toggleAll.checked; });
                    rafraichir();
                });
                rafraichir();
            } else {
                ok.disabled = true;
            }

            const suivant = async () => {
                ok.disabled = true;

                const corps = new FormData();
                formData.forEach((v, k) => corps.append(k, v));

                // Des qu'un apercu a montre des cases, la selection fait foi et
                // rien d'autre. `selection_active` le dit au serveur, parce
                // qu'un tableau vide ne se transmet pas en HTTP : sans ce
                // drapeau, « aucune case cochee » arrivait indistinguable de
                // « pas de selection », donc en « tout appliquer ».
                //
                // Consequence voulue : quand l'apercu est tronque, on n'applique
                // que ce qui a ete montre. On ne fait pas confirmer a quelqu'un
                // des lignes qu'il n'a pas vues.
                if (cases.length) {
                    corps.append('selection_active', '1');
                    cases.filter((c) => c.checked)
                        .forEach((c) => corps.append('lignes[]', c.value));
                }

                term.insertAdjacentHTML('beforeend', texte('<span class="rf-line-muted">$ apply…</span>'));
                try {
                    const applied = await post(opts.applyUrl, corps);

                    // Un 422 ou un 403 rend du JSON avec un `message` : sans ce
                    // test il s'affichait en vert, la modale se fermait et la
                    // page se rechargeait — l'utilisateur croyait la
                    // regeneration faite alors que rien n'avait ete ecrit.
                    if (!applied || applied.success !== true) {
                        term.insertAdjacentHTML('beforeend', texte('<span class="rf-line-del">$ '
                            + esc((applied && applied.message) || 'refusé') + '</span>'));
                        ok.disabled = false;
                        return;
                    }

                    term.insertAdjacentHTML('beforeend', texte('<span class="rf-line-add">$ ' + esc(applied.message || 'ok') + '</span>'));
                    setTimeout(() => {
                        modal.hide();
                        if (typeof opts.onDone === 'function') opts.onDone(applied);
                        // EXCEPTION ajax-no-reload-premium : une regeneration de
                        // masse change les montants de chaque ligne ET les
                        // compteurs du bandeau ; un rafraichissement partiel
                        // laisserait l'ecran a moitie faux.
                        else window.location.reload();
                    }, 700);
                } catch (e) {
                    term.insertAdjacentHTML('beforeend', texte('<span class="rf-line-del">$ échec</span>'));
                    ok.disabled = false;
                }
            };

            ok.addEventListener('click', suivant);
            rafraichirBouton();
        }
    };

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-regenerer-frais');
        if (!btn) return;
        e.preventDefault();
        const form = btn.closest('form');
        const formData = form ? new FormData(form) : new FormData();
        if (btn.dataset.inscriptionId && !formData.has('inscription_ids[]')) {
            formData.append('inscription_ids[]', btn.dataset.inscriptionId);
        }
        if (btn.dataset.scope) {
            formData.set('scope', btn.dataset.scope);
        }
        if (btn.dataset.scope === 'annee') {
            // L'annee se lit dans l'URL, PAS dans l'attribut Blade : le filtre
            // Annee change la liste en AJAX sans re-rendre le bandeau, donc
            // `data-annee-id` reste figee sur celle du chargement de page. On
            // aurait realigne les montants d'une promotion qui n'etait plus a
            // l'ecran.
            const anneeAffichee = new URLSearchParams(window.location.search).get('annee');
            const annee = (anneeAffichee || '').trim() || btn.dataset.anneeId;
            if (annee) formData.set('annee_id', annee);
        }
        // Portee « ce que la liste affiche » : on rejoue la query string de la
        // page, c'est elle qui porte les filtres.
        if (btn.dataset.scope === 'filtre') {
            const params = new URLSearchParams(window.location.search);
            // La recherche libre classe par ressemblance, avec un plafond : elle
            // retrouve une personne, elle ne definit pas un ensemble. Le serveur
            // le refuse aussi, mais le dire ici evite un aller-retour.
            if ((params.get('search') || '').trim() !== '') {
                const dire = "Une recherche est en cours : elle ne définit pas une portée fiable. "
                    + "Videz la recherche, ou cochez les lignes à régénérer.";
                if (typeof window.klassciToast === 'function') window.klassciToast('warning', dire, 7000);
                else window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'warning', message: dire } }));
                return;
            }
            params.forEach((v, k) => {
                if (k !== 'page' && v !== '') formData.set(k, v);
            });
            formData.set('scope', 'filtre');
        }
        window.KlassciRegenererFrais.open({
            previewUrl: btn.dataset.preview,
            applyUrl: btn.dataset.apply,
            formData: formData,
        });
    });
})();
