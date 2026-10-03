(function () {
    'use strict';

    const SCRIPT_KEY = 'lmdEvaluationTeacherV1';

    function boot(root) {
        const form = (root || document).querySelector('#evaluationCreateForm');
        if (!form || form.dataset[SCRIPT_KEY] === '1') return;
        form.dataset[SCRIPT_KEY] = '1';

        const classe = form.querySelector('[name="classe_id"]');
        const periode = form.querySelector('[name="periode"]');
        const matiere = form.querySelector('[name="matiere_id"]');
        const csrf = form.querySelector('[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content;
        if (!classe || !periode || !matiere) return;

        let contexte = null;
        let expectedSignature = '';

        const info = document.createElement('div');
        info.className = 'ec-info';
        info.id = 'lmd-evaluation-teacher-info';
        info.style.display = 'none';
        info.innerHTML = '<i class="fas fa-user-tie"></i><span></span>';
        matiere.closest('.ec-field')?.appendChild(info);

        function verrouillerAffectation(verrouille) {
            ['enseignant_id', 'enseignant_externe_nom', 'generer_lien_externe'].forEach(function (name) {
                const field = form.querySelector('[name="' + name + '"]');
                if (field) field.disabled = verrouille;
            });
            const card = form.querySelector('#enseignant_externe_nom')?.closest('.ec-card');
            if (card) card.style.display = verrouille ? 'none' : '';
        }

        function selectedElement() {
            return contexte?.is_lmd
                ? (contexte.elements || []).find(row => String(row.matiere_id) === String(matiere.value)) || null
                : null;
        }

        function renderInfo() {
            if (!contexte?.is_lmd) {
                info.style.display = 'none';
                verrouillerAffectation(false);
                return;
            }

            verrouillerAffectation(true);
            info.style.display = 'flex';
            const span = info.querySelector('span');
            const row = selectedElement();
            if (!row) {
                span.textContent = 'En LMD, la matière vient de la maquette du semestre et l’enseignant du planning académique.';
                return;
            }

            if (row.enseignant_nom && row.source_enseignant === 'planning') {
                span.innerHTML = '<strong>Enseignant du planning :</strong> ' + escapeHtml(row.enseignant_nom) + '. Cette affectation sera reprise par l’évaluation et le bulletin.';
                return;
            }

            const action = contexte.can_assign && row.assignable_rapidement
                ? ' <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-lmd-assign-teacher>Assigner maintenant</button>'
                : (contexte.planning_url ? ' <a class="ec-link" href="' + escapeAttr(contexte.planning_url) + '">Ouvrir le planning LMD</a>' : '');
            span.innerHTML = '<strong>Aucun enseignant affecté dans le planning.</strong> ' + escapeHtml(row.message || '') + action;
            span.querySelector('[data-lmd-assign-teacher]')?.addEventListener('click', () => openModal(row));
        }

        function signature(rows) {
            return rows.map(row => String(row.matiere_id)).join(',');
        }

        function currentSignature() {
            return Array.from(matiere.options).slice(1).map(option => String(option.value)).join(',');
        }

        function applyElements(rows) {
            expectedSignature = signature(rows);
            if (currentSignature() === expectedSignature) {
                renderInfo();
                return;
            }

            const current = String(matiere.value || '');
            matiere.innerHTML = '<option value="">— Sélectionner un ECUE —</option>' + rows.map(function (row) {
                const label = [row.ue, [row.code, row.name].filter(Boolean).join(' — ')].filter(Boolean).join(' · ');
                return '<option value="' + row.matiere_id + '">' + escapeHtml(label) + '</option>';
            }).join('');
            if (rows.some(row => String(row.matiere_id) === current)) matiere.value = current;
            matiere.dispatchEvent(new Event('change', { bubbles: true }));
            renderInfo();
        }

        async function loadContext() {
            if (!classe.value || !periode.value) return;
            const params = new URLSearchParams({ classe_id: classe.value, periode: periode.value });
            try {
                const res = await fetch('/esbtp/lmd/evaluation-teacher/context?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!res.ok) throw new Error('Contexte indisponible');
                contexte = await res.json();
                if (contexte.is_lmd) applyElements(contexte.elements || []);
                else renderInfo();
            } catch (e) {
                contexte = null;
                renderInfo();
            }
        }

        function openModal(row) {
            if (!contexte?.can_assign) return;
            let modalEl = document.getElementById('lmdQuickTeacherModal');
            if (!modalEl) {
                modalEl = document.createElement('div');
                modalEl.className = 'modal fade';
                modalEl.id = 'lmdQuickTeacherModal';
                modalEl.tabIndex = -1;
                modalEl.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">' +
                    '<div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Assigner l’enseignant LMD</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                    '<div class="modal-body"><p class="small text-muted" data-lmd-modal-context></p><label class="form-label fw-semibold">Enseignant principal</label><select class="form-select" data-lmd-modal-select></select><div class="alert alert-danger mt-3 d-none" data-lmd-modal-error></div></div>' +
                    '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="button" class="btn btn-primary" data-lmd-modal-save>Assigner</button></div>' +
                    '</div></div>';
                document.body.appendChild(modalEl);
            }

            modalEl.querySelector('[data-lmd-modal-context]').textContent = (row.code ? row.code + ' — ' : '') + row.name + ' · S' + contexte.semestre;
            const select = modalEl.querySelector('[data-lmd-modal-select]');
            select.innerHTML = '<option value="">— Choisir —</option>' + (contexte.teachers || []).map(u => '<option value="' + u.id + '">' + escapeHtml(u.name) + '</option>').join('');
            const error = modalEl.querySelector('[data-lmd-modal-error]');
            error.classList.add('d-none');
            const save = modalEl.querySelector('[data-lmd-modal-save]');
            save.onclick = async function () {
                if (!select.value) return;
                save.disabled = true;
                try {
                    const res = await fetch('/esbtp/lmd/evaluation-teacher/assign', {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf || '', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({
                            classe_id: classe.value,
                            annee_universitaire_id: contexte.annee_universitaire_id,
                            periode: periode.value,
                            matiere_id: row.matiere_id,
                            enseignant_id: select.value
                        })
                    });
                    const json = await res.json();
                    if (!res.ok) throw new Error(json.message || Object.values(json.errors || {})[0]?.[0] || 'Affectation impossible');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    await loadContext();
                } catch (e) {
                    error.textContent = e.message;
                    error.classList.remove('d-none');
                } finally {
                    save.disabled = false;
                }
            };
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        form.addEventListener('submit', function (event) {
            if (!contexte?.is_lmd) return;
            const row = selectedElement();
            if (!row || row.source_enseignant !== 'planning') {
                event.preventDefault();
                renderInfo();
                if (row && contexte.can_assign && row.assignable_rapidement) openModal(row);
            }
        });
        classe.addEventListener('change', loadContext);
        periode.addEventListener('change', loadContext);
        matiere.addEventListener('change', renderInfo);

        new MutationObserver(function () {
            if (contexte?.is_lmd && currentSignature() !== expectedSignature) {
                applyElements(contexte.elements || []);
            }
        }).observe(matiere, { childList: true });

        loadContext();
    }

    function escapeHtml(value) {
        const d = document.createElement('div');
        d.textContent = value == null ? '' : String(value);
        return d.innerHTML;
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }

    function scan() { boot(document); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
    document.addEventListener('shown.bs.modal', scan);
})();
