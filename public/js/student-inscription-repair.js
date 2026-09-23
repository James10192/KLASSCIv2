(function () {
    const panel = document.getElementById('inscriptionRepairPanel');
    if (!panel) return;

    const targetSelect = document.getElementById('inscriptionRepairTargetClasse');
    const dryRunBtn = document.getElementById('inscriptionRepairDryRunBtn');
    const applyBtn = document.getElementById('inscriptionRepairApplyBtn');
    const statusEl = document.getElementById('inscriptionRepairStatus');
    const resultEl = document.getElementById('inscriptionRepairResult');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const canRepair = panel.dataset.canRepair === '1';
    let diagnosticAbort = null;
    let busy = false;

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function money(value) {
        if (value === null || value === undefined) {
            return '—';
        }
        return Number(value || 0).toLocaleString('fr-FR') + ' FCFA';
    }

    function setStatus(type, icon, message) {
        statusEl.className = 'insc-repair-status ' + (type || '');
        statusEl.innerHTML = '<i class="' + icon + '"></i><span>' + esc(message) + '</span>';
    }

    function syncButtons() {
        const hasTarget = !!targetSelect?.value;
        if (dryRunBtn) dryRunBtn.disabled = busy || !hasTarget;
        if (applyBtn) applyBtn.disabled = busy || !hasTarget || !canRepair;
    }

    function diagnosticUrl() {
        const params = new URLSearchParams();
        if (panel.dataset.yearId) params.set('annee_universitaire_id', panel.dataset.yearId);
        if (targetSelect?.value) params.set('target_classe_id', targetSelect.value);

        return panel.dataset.diagnoseUrl + '?' + params.toString();
    }

    function renderDiagnostic(diagnostic) {
        const inscriptions = diagnostic.inscriptions || [];
        const decision = diagnostic.decision || {};
        const archiveIds = (decision.archive_inscription_ids || []).map(Number);
        const keepId = Number(decision.keep_inscription_id || 0);
        const target = diagnostic.target_classe;
        const summary = diagnostic.summary || {};

        if (!targetSelect.value && diagnostic.suggested_target_classe_id) {
            const suggested = String(diagnostic.suggested_target_classe_id);
            if (targetSelect.querySelector('option[value="' + suggested + '"]')) {
                targetSelect.value = suggested;
                syncButtons();
            }
        }

        if (!diagnostic.annee) {
            setStatus('error', 'fas fa-exclamation-triangle', "Aucune annee universitaire courante n'est configuree.");
        } else if (!target) {
            setStatus('warn', 'fas fa-arrow-up-right-dots', summary.message || 'Choisissez une classe cible pour simuler la correction.');
        } else if (summary.can_repair) {
            setStatus('warn', 'fas fa-triangle-exclamation', summary.message || 'Une correction est disponible.');
        } else {
            setStatus('ok', 'fas fa-check-circle', summary.message || 'Aucun doublon a corriger pour cette selection.');
        }

        if (!inscriptions.length) {
            resultEl.innerHTML = '<div style="padding:12px;color:#64748b;font-size:.8rem;border:1px dashed #cbd5e1;border-radius:8px;">Aucune inscription trouvee pour cette annee.</div>';
            return;
        }

        const rows = inscriptions.map(function (inscription) {
            const action = Number(inscription.id) === keepId
                ? '<span class="insc-repair-badge keep"><i class="fas fa-check"></i> Conserver</span>'
                : (archiveIds.includes(Number(inscription.id))
                    ? '<span class="insc-repair-badge archive"><i class="fas fa-box-archive"></i> Archiver</span>'
                    : '<span class="insc-repair-badge neutral"><i class="fas fa-circle-info"></i> Reference</span>');
            const payments = inscription.payments || {};

            return '<tr>' +
                '<td><strong>#' + esc(inscription.id) + '</strong><div style="color:#64748b;">' + esc(inscription.date_inscription || inscription.created_at || '') + '</div></td>' +
                '<td><strong>' + esc(inscription.classe || '-') + '</strong><div style="color:#64748b;">' + esc(inscription.filiere || '-') + ' / ' + esc(inscription.niveau || '-') + '</div></td>' +
                '<td>' + esc(inscription.status || '-') + '<div style="color:#64748b;">' + esc(inscription.workflow_step || '-') + '</div></td>' +
                '<td><strong>' + money(payments.valid_total) + '</strong><div style="color:#64748b;">Total: ' + money(payments.total) + '</div></td>' +
                '<td>' + action + '</td>' +
            '</tr>';
        }).join('');

        resultEl.innerHTML = '<table class="insc-repair-table">' +
            '<thead><tr><th>Inscription</th><th>Classe</th><th>Statut</th><th>Paiements</th><th>Action proposee</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table>';
    }

    function loadDiagnostic(allowSuggestion) {
        if (diagnosticAbort) diagnosticAbort.abort();
        diagnosticAbort = new AbortController();
        setStatus('', 'fas fa-spinner fa-spin', 'Chargement du diagnostic...');

        return fetch(diagnosticUrl(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: diagnosticAbort.signal,
        })
            .then(function (response) { return response.json().then(data => ({ response, data })); })
            .then(function (payload) {
                if (!payload.response.ok || !payload.data.success) {
                    throw new Error(payload.data.message || 'Diagnostic indisponible.');
                }

                const diagnostic = payload.data.data;
                if (allowSuggestion && !targetSelect.value && diagnostic?.suggested_target_classe_id) {
                    const suggested = String(diagnostic.suggested_target_classe_id);
                    if (targetSelect.querySelector('option[value="' + suggested + '"]')) {
                        targetSelect.value = suggested;
                        syncButtons();
                        return loadDiagnostic(false);
                    }
                }

                renderDiagnostic(diagnostic);
                syncButtons();
            })
            .catch(function (error) {
                if (error.name === 'AbortError') return;
                setStatus('error', 'fas fa-exclamation-triangle', error.message || 'Erreur de diagnostic.');
                resultEl.innerHTML = '';
                syncButtons();
            });
    }

    function runRepair(dryRun) {
        if (!targetSelect.value) {
            setStatus('warn', 'fas fa-arrow-up-right-dots', 'Choisissez une classe cible avant de continuer.');
            return;
        }

        if (!dryRun && !canRepair) {
            setStatus('error', 'fas fa-lock', 'Permissions insuffisantes pour appliquer la correction.');
            return;
        }

        if (!dryRun && !window.confirm("Appliquer la correction d'inscription pour cet etudiant ?")) {
            return;
        }

        busy = true;
        syncButtons();
        setStatus('', 'fas fa-spinner fa-spin', dryRun ? 'Simulation en cours...' : 'Correction en cours...');

        const body = {
            target_classe_id: Number(targetSelect.value),
            dry_run: !!dryRun,
        };
        if (panel.dataset.yearId) body.annee_universitaire_id = Number(panel.dataset.yearId);

        fetch(panel.dataset.repairUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        })
            .then(function (response) { return response.json().then(data => ({ response, data })); })
            .then(function (payload) {
                if (!payload.response.ok || !payload.data.success) {
                    throw new Error(payload.data.message || 'Correction impossible.');
                }

                if (payload.data.diagnostic) renderDiagnostic(payload.data.diagnostic);
                if (!dryRun) {
                    (payload.data.archived_inscription_ids || []).forEach(function (id) {
                        const card = document.querySelector('[data-inscription-card="' + id + '"]');
                        if (!card) return;
                        card.style.opacity = '.35';
                        card.style.transform = 'scale(.98)';
                        card.style.transition = 'opacity .2s ease, transform .2s ease';
                    });
                }

                setStatus('ok', dryRun ? 'fas fa-vial-circle-check' : 'fas fa-check-circle', payload.data.message || (dryRun ? 'Simulation terminee.' : 'Correction appliquee.'));
            })
            .catch(function (error) {
                setStatus('error', 'fas fa-exclamation-triangle', error.message || 'Erreur reseau.');
            })
            .finally(function () {
                busy = false;
                syncButtons();
            });
    }

    targetSelect?.addEventListener('change', function () { loadDiagnostic(false); });
    dryRunBtn?.addEventListener('click', function () { runRepair(true); });
    applyBtn?.addEventListener('click', function () { runRepair(false); });
    syncButtons();
    loadDiagnostic(true);
})();
