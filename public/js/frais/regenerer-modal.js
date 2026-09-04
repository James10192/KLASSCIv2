(function () {
    'use strict';

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const $ = (id) => document.getElementById(id);

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

        if (!totalAdd && !totalDel && !totalAdj) {
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
        out.push(texte('<span class="rf-line-muted">$ ' + resume.join(', ') + ' sur ' + (res.inscriptions || 0) + ' inscription(s)</span>'));

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
            const toggleAll = $('rf-toggle-all');
            const ok = $('rf-confirm');

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
            const vide = !preview.total_ajouter && !preview.total_retirer && !preview.total_ajuster;

            $('rf-modal-sub').textContent = vide
                ? 'Rien à appliquer'
                : ((preview.inscriptions || 1) + ' inscription(s) concernée(s)');

            if (preview.retouches) {
                alerte.hidden = false;
                alerte.innerHTML = '<i class="fas fa-triangle-exclamation" style="margin-top:2px;"></i><span>'
                    + '<strong>' + preview.retouches + ' montant(s) ont déjà été retouchés à la main</strong> — '
                    + 'une remise, une bourse ou un arrangement. Ils sont laissés décochés : '
                    + 'cochez-les seulement si vous voulez revenir au barème.</span>';
            }

            function rafraichir() {
                const coches = cases.filter((c) => c.checked).length;
                compteur.textContent = coches + ' sélectionnée(s) sur ' + cases.length;
                toggleAll.checked = coches === cases.length && cases.length > 0;
                toggleAll.indeterminate = coches > 0 && coches < cases.length;
                ok.disabled = coches === 0;
            }

            if (cases.length) {
                bar.hidden = false;
                cases.forEach((c) => c.addEventListener('change', rafraichir));
                toggleAll.onclick = null;
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

                // Rien n'a ete decoche : on n'envoie aucune liste, et le serveur
                // applique TOUT ce qu'il detecte — y compris au-dela de ce que
                // l'apercu a pu afficher quand la liste est tronquee. Des qu'une
                // ligne est decochee, on transmet la selection exacte.
                const coches = cases.filter((c) => c.checked);
                if (coches.length !== cases.length) {
                    coches.forEach((c) => corps.append('lignes[]', c.value));
                }

                term.insertAdjacentHTML('beforeend', texte('<span class="rf-line-muted">$ apply…</span>'));
                try {
                    const applied = await post(opts.applyUrl, corps);
                    term.insertAdjacentHTML('beforeend', texte('<span class="rf-line-add">$ ' + esc(applied.message || 'ok') + '</span>'));
                    setTimeout(() => {
                        modal.hide();
                        if (typeof opts.onDone === 'function') opts.onDone(applied);
                        else window.location.reload();
                    }, 700);
                } catch (e) {
                    term.insertAdjacentHTML('beforeend', texte('<span class="rf-line-del">$ échec</span>'));
                    ok.disabled = false;
                }
            };

            // Le bouton est remplace pour repartir sans les ecouteurs de
            // l'ouverture precedente : sans ca, un deuxieme apercu appliquerait
            // aussi le premier.
            const neuf = ok.cloneNode(true);
            ok.parentNode.replaceChild(neuf, ok);
            neuf.disabled = cases.length === 0 || vide;
            neuf.addEventListener('click', suivant);
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
        if (btn.dataset.anneeId) {
            formData.set('annee_id', btn.dataset.anneeId);
        }
        // Portee « ce que la liste affiche » : on rejoue la query string de la
        // page, c'est elle qui porte les filtres.
        if (btn.dataset.scope === 'filtre') {
            const params = new URLSearchParams(window.location.search);
            // La recherche libre classe par ressemblance, avec un plafond : elle
            // retrouve une personne, elle ne definit pas un ensemble. Le serveur
            // le refuse aussi, mais le dire ici evite un aller-retour.
            if ((params.get('search') || '').trim() !== '') {
                window.alert("Une recherche est en cours : elle ne définit pas une portée fiable.\n\nVidez la recherche, ou cochez les lignes à régénérer.");
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
