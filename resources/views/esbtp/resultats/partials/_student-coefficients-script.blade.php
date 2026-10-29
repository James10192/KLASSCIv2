<script data-scm-init-script>
// La fenêtre est remplacée avec les résultats AJAX. Garder une factory persistante
// évite le piège des <script> inertes après une affectation à innerHTML.
window.scmInitCoefficientsModal = function () {
    'use strict';

    const form    = document.getElementById('scmForm');
    const saveBtn = document.getElementById('scmSaveBtn');
    const success = document.getElementById('scmSuccess');
    const errBox  = document.getElementById('scmError');
    const errText = document.getElementById('scmErrorText');
    const UPDATE_URL = "{{ route('esbtp.evaluations.coefficients.update') }}";
    const READ_URL = "{{ route('esbtp.evaluations.coefficients.read') }}";
    const COMPLETION_URL = "{{ route('esbtp.evaluations.coefficients.completion') }}";
    const COPY_URL = "{{ route('esbtp.evaluations.coefficients.copy') }}";

    if (!form || form.dataset.scmBound === '1') return;
    form.dataset.scmBound = '1';

    /* ═══ SOUS-LOT γ : Switch S1/S2 + Jauge + Copy ═══ */
    const periodeInput = document.getElementById('scmPeriode');
    const filiereId = form.querySelector('input[name="filiere_id"]').value;
    const niveauId = form.querySelector('input[name="niveau_etude_id"]').value;
    const anneeId = form.querySelector('input[name="annee_universitaire_id"]').value;
    const periodeBtns = document.querySelectorAll('.scm-period-btn');
    const copyBtn = document.getElementById('scmCopyBtn');
    const copyTargetSpan = document.getElementById('scmCopyTarget');
    const completionStatus = document.getElementById('scmCompletionStatus');
    const completionLabel = document.getElementById('scmCompletionLabel');
    const completionFill = document.getElementById('scmCompletionFill');
    const completionConfigured = document.getElementById('scmCompletionConfigured');
    const completionTotal = document.getElementById('scmCompletionTotal');

    function currentPeriode() { return periodeInput.value || 'semestre1'; }
    function otherPeriode() { return currentPeriode() === 'semestre1' ? 'semestre2' : 'semestre1'; }
    function periodeLabel(p) { return p === 'semestre1' ? 'S1' : 'S2'; }

    function updateCopyTargetLabel() {
        if (copyTargetSpan) copyTargetSpan.textContent = periodeLabel(otherPeriode());
    }

    async function refreshCompletion() {
        try {
            const url = new URL(COMPLETION_URL, window.location.origin);
            url.searchParams.append('filiere_id', filiereId);
            url.searchParams.append('niveau_etude_id', niveauId);
            url.searchParams.append('annee_universitaire_id', anneeId);
            url.searchParams.append('periode', currentPeriode());
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) return;
            completionConfigured.textContent = data.configured_count;
            completionTotal.textContent = data.total_count;
            completionFill.style.width = data.total_count > 0
                ? ((data.configured_count / data.total_count) * 100) + '%'
                : '0%';
            completionFill.dataset.status = data.status;
            completionStatus.dataset.status = data.status;
            completionLabel.textContent = data.status === 'complete' ? 'Complet'
                : data.status === 'incomplete' ? 'Incomplet'
                : 'Non configuré';
        } catch (e) { console.warn('completion error', e); }
    }

    async function switchToPeriode(target) {
        if (target === currentPeriode()) return;
        periodeBtns.forEach(b => { b.disabled = true; });
        try {
            const url = new URL(READ_URL, window.location.origin);
            url.searchParams.append('filiere_id', filiereId);
            url.searchParams.append('niveau_etude_id', niveauId);
            url.searchParams.append('annee_universitaire_id', anneeId);
            url.searchParams.append('periode', target);
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'erreur');
            // Update all coefficient inputs
            form.querySelectorAll('.scm-input').forEach(input => {
                const name = input.name; // coefficients[X]
                const m = name.match(/coefficients\[(\d+)\]/);
                if (m) {
                    const matiereId = m[1];
                    input.value = (data.coefficients[matiereId] !== undefined) ? data.coefficients[matiereId] : '';
                }
            });
            periodeInput.value = target;
            periodeBtns.forEach(b => b.classList.toggle('active', b.dataset.scmPeriode === target));
            updateCopyTargetLabel();
            await refreshCompletion();
        } catch (e) {
            alert('Erreur switch période : ' + e.message);
        } finally {
            periodeBtns.forEach(b => b.disabled = false);
        }
    }

    periodeBtns.forEach(btn => {
        btn.addEventListener('click', () => switchToPeriode(btn.dataset.scmPeriode));
    });

    /* Copy modal */
    const copyOverlay = document.getElementById('scmCopyOverlay');
    const copyClose = document.getElementById('scmCopyClose');
    const copyCancel = document.getElementById('scmCopyCancel');
    const copyExecute = document.getElementById('scmCopyExecute');
    const copyHeaderSource = document.getElementById('scmCopyHeaderSource');
    const copyHeaderTarget = document.getElementById('scmCopyHeaderTarget');
    const copySummary = document.getElementById('scmCopySummary');
    const copyDetails = document.getElementById('scmCopyDetails');
    const modeOptions = document.querySelectorAll('.scm-mode-option');
    let currentCopyMode = 'override';

    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            copyHeaderSource.textContent = periodeLabel(currentPeriode());
            copyHeaderTarget.textContent = periodeLabel(otherPeriode());
            copyOverlay.classList.remove('d-none');
            refreshCopyPreview();
        });
    }
    [copyClose, copyCancel].forEach(b => b && b.addEventListener('click', () => copyOverlay.classList.add('d-none')));

    modeOptions.forEach(opt => {
        opt.addEventListener('click', () => {
            modeOptions.forEach(o => o.classList.remove('active'));
            opt.classList.add('active');
            opt.querySelector('input').checked = true;
            currentCopyMode = opt.dataset.scmMode;
            refreshCopyPreview();
        });
    });

    async function refreshCopyPreview() {
        copySummary.innerHTML = '<span style="color:#64748b;font-size:.78rem;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="12" height="12" class="scm-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Calcul…</span>';
        copyDetails.innerHTML = '';
        try {
            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            fd.append('filiere_id', filiereId);
            fd.append('niveau_etude_id', niveauId);
            fd.append('annee_universitaire_id', anneeId);
            fd.append('source_periode', currentPeriode());
            fd.append('target_periode', otherPeriode());
            fd.append('mode', currentCopyMode);
            fd.append('dry_run', '1');
            const res = await fetch(COPY_URL, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'erreur');
            const s = data.summary;
            copySummary.innerHTML = `
                <span class="pill pill--create">Créer : <strong>${s.will_create}</strong></span>
                <span class="pill pill--update">Mettre à jour : <strong>${s.will_update}</strong></span>
                <span class="pill pill--skip">Skip : <strong>${s.will_skip}</strong></span>
            `;
            let html = '';
            (data.preview.create || []).forEach(r => {
                html += `<div class="item"><span class="name">${r.matiere_name}</span><span class="change">→ ${r.source_value}</span></div>`;
            });
            (data.preview.update || []).forEach(r => {
                html += `<div class="item"><span class="name">${r.matiere_name}</span><span class="change">${r.target_existing_value} → ${r.source_value}</span></div>`;
            });
            (data.preview.skip || []).forEach(r => {
                html += `<div class="item"><span class="name">${r.matiere_name}</span><span class="change">déjà : ${r.target_existing_value}</span></div>`;
            });
            copyDetails.innerHTML = html;
        } catch (e) {
            copySummary.innerHTML = '<span style="color:#dc2626;">Erreur : ' + e.message + '</span>';
        }
    }

    if (copyExecute) {
        copyExecute.addEventListener('click', async () => {
            copyExecute.disabled = true;
            try {
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                fd.append('filiere_id', filiereId);
                fd.append('niveau_etude_id', niveauId);
                fd.append('annee_universitaire_id', anneeId);
                fd.append('source_periode', currentPeriode());
                fd.append('target_periode', otherPeriode());
                fd.append('mode', currentCopyMode);
                const res = await fetch(COPY_URL, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                alert(`Copie : ${data.summary.created} créés, ${data.summary.updated} mis à jour, ${data.summary.skipped} skippés.`);
                copyOverlay.classList.add('d-none');
                await refreshCompletion();
            } catch (e) {
                alert('Erreur : ' + e.message);
            } finally {
                copyExecute.disabled = false;
            }
        });
    }

    /* Init : load S1 + completion */
    updateCopyTargetLabel();
    refreshCompletion();

    /* Le focus est placé seulement une fois le modal visible, jamais sur un champ masqué. */
    const coeffModal = document.getElementById('studentCoeffModal');
    if (coeffModal) {
        coeffModal.addEventListener('shown.bs.modal', function () {
            const blocking = form.querySelector('.scm-input--blocking');
            if (blocking) blocking.focus({ preventScroll: true });
        });
    }

    function scmSetLoading(loading) {
        const label = saveBtn.querySelector('.scm-btn-label');
        const loadingEl = saveBtn.querySelector('.scm-btn-loading');
        const icon = saveBtn.querySelector('.scm-btn-icon');
        saveBtn.disabled = !!loading;
        if (label) label.classList.toggle('d-none', !!loading);
        if (loadingEl) loadingEl.classList.toggle('d-none', !loading);
        if (icon) icon.classList.toggle('d-none', !!loading);
    }
    // Failsafe : si on rouvre le modal en plein loading (refresh stuck), restaure
    scmSetLoading(false);

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        /* Reset feedback */
        success.classList.add('d-none');
        errBox.classList.add('d-none');

        scmSetLoading(true);

        const body = new FormData(form);

        fetch(UPDATE_URL, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
            },
            body: body,
        })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data }; });
        })
        .then(function ({ ok, data }) {
            if (ok && data.success) {
                success.classList.remove('d-none');
                // Failsafe : restaurer le bouton même si refreshContent fail (5s timeout)
                const restoreTimer = setTimeout(function () { scmSetLoading(false); }, 5000);
                /* Après l'animation (1.5s), rafraîchir le contenu sans recharger toute la page */
                setTimeout(function () {
                    clearTimeout(restoreTimer);
                    scmRefreshContent();
                    // Au cas où le refresh est rapide mais le modal n'est pas remplacé,
                    // on restaure aussi le bouton après un délai supplémentaire
                    setTimeout(function () { scmSetLoading(false); success.classList.add('d-none'); }, 2000);
                }, 1600);
            } else {
                throw new Error(data.message || 'Erreur lors de l\'enregistrement.');
            }
        })
        .catch(function (err) {
            errText.textContent = err.message || 'Une erreur réseau est survenue.';
            errBox.classList.remove('d-none');
            scmSetLoading(false);
        });
    });

    function scmRefreshError() {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: {
                type: 'error',
                message: 'Coefficients enregistrés, mais impossible de rafraîchir les résultats. Vérifiez votre connexion.'
            }
        }));
    }

    /**
     * Rafraîchit le contenu de la page résultats en AJAX sans rechargement complet.
     * Fetche la même URL, extrait #etudiant-resultats-content et le remplace.
     * Ferme le modal proprement avant le swap.
     */
    function scmRefreshContent() {
        var modalEl = document.getElementById('studentCoeffModal');
        var bsModal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
        // Laisser Bootstrap finir sa transition avant de remplacer le DOM :
        // sinon le backdrop / body.modal-open restent parfois bloqués.
        if (bsModal && modalEl.classList.contains('show')) {
            modalEl.addEventListener('hidden.bs.modal', scmRefreshContent, { once: true });
            bsModal.hide();
            return;
        }
        if (bsModal) bsModal.dispose();

        var target = document.getElementById('etudiant-resultats-content');
        if (!target) {
            scmRefreshError();
            return;
        }

        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var fresh = doc.getElementById('etudiant-resultats-content');
            if (fresh) {
                target.innerHTML = fresh.innerHTML;
                // Rebranche les actions et la modale après remplacement du contenu.
                if (typeof window.srReinitResultsAfterSave === 'function') {
                    window.srReinitResultsAfterSave();
                } else if (typeof window.scmInitCoefficientsModal === 'function') {
                    window.scmInitCoefficientsModal();
                }
            } else {
                scmRefreshError();
            }
        })
        .catch(function () {
            scmRefreshError();
        });
    }
};
window.scmInitCoefficientsModal();
</script>
