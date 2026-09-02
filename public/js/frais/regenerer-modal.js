(function () {
    'use strict';

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    function termEl() {
        return document.getElementById('rf-term');
    }

    function writeTerm(html) {
        const el = termEl();
        if (el) el.innerHTML = html;
    }

    function fmt(n) {
        return Number(n || 0).toLocaleString('fr-FR');
    }

    function renderPreview(res) {
        const add = res.lignes || [];
        const del = res.lignes_retrait || [];
        if (!add.length && !del.length) {
            return '<span class="rf-line-muted">$ aucun écart — les frais sont à jour</span>';
        }
        const lines = ['<span class="rf-line-muted">$ dry-run</span>'];
        add.forEach((l) => {
            lines.push('<span class="rf-line-add">+ ' + (l.categorie || '—') + '   ' + fmt(l.montant) + ' F</span>');
        });
        del.forEach((l) => {
            lines.push('<span class="rf-line-del">− ' + (l.categorie || '—') + '   ' + fmt(l.montant) + ' F  #' + (l.motif || 'retrait') + '</span>');
        });
        lines.push('<span class="rf-line-muted">$ ' + add.length + ' ajout(s), ' + del.length + ' retrait(s)</span>');
        return lines.join('\n');
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
            const modalEl = document.getElementById('rf-modal');
            if (!modalEl || typeof bootstrap === 'undefined') {
                return;
            }
            const formData = opts.formData instanceof FormData ? opts.formData : new FormData();
            if (!formData.has('_token')) formData.append('_token', csrf());

            writeTerm('<span class="rf-line-muted">$ preview…</span>');
            document.getElementById('rf-confirm').disabled = true;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            let preview;
            try {
                preview = await post(opts.previewUrl, formData);
            } catch (e) {
                writeTerm('<span class="rf-line-del">$ impossible de prévisualiser</span>');
                return;
            }

            writeTerm(renderPreview(preview));
            const empty = !preview.total_ajouter && !preview.total_retirer && !preview.total;
            document.getElementById('rf-confirm').disabled = !!empty;
            document.getElementById('rf-modal-sub').textContent = empty
                ? 'Rien à appliquer'
                : ((preview.inscriptions || 1) + ' inscription(s)');

            const ok = document.getElementById('rf-confirm');
            const next = async () => {
                ok.disabled = true;
                writeTerm((termEl().innerHTML || '') + '\n<span class="rf-line-muted">$ apply…</span>');
                try {
                    const applied = await post(opts.applyUrl, formData);
                    writeTerm((termEl().innerHTML || '') + '\n<span class="rf-line-add">$ ' + (applied.message || 'ok') + '</span>');
                    setTimeout(() => {
                        modal.hide();
                        if (typeof opts.onDone === 'function') opts.onDone(applied);
                        else window.location.reload();
                    }, 600);
                } catch (e) {
                    writeTerm((termEl().innerHTML || '') + '\n<span class="rf-line-del">$ échec</span>');
                    ok.disabled = false;
                }
            };
            ok.replaceWith(ok.cloneNode(true));
            document.getElementById('rf-confirm').addEventListener('click', next);
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
        window.KlassciRegenererFrais.open({
            previewUrl: btn.dataset.preview,
            applyUrl: btn.dataset.apply,
            formData: formData,
        });
    });
})();
