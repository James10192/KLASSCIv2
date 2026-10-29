(function () {
    'use strict';

    const SCRIPT_KEY = 'lmdEvaluationTeacherV2';

    function boot(root) {
        const form = (root || document).querySelector('#evaluationCreateForm');
        if (!form || form.dataset[SCRIPT_KEY] === '1') return;
        form.dataset[SCRIPT_KEY] = '1';

        const classe = form.querySelector('[name="classe_id"]');
        const periode = form.querySelector('[name="periode"]');
        const matiere = form.querySelector('[name="matiere_id"]');
        const enseignantInput = form.querySelector('[name="enseignant_id"]');
        const externeInput = form.querySelector('[name="enseignant_externe_nom"]');
        const lienExterne = form.querySelector('[name="generer_lien_externe"]');
        const csrf = form.querySelector('[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content;
        if (!classe || !periode || !matiere) return;

        let contexte = null;
        let expectedSignature = '';
        let confirmationForm = null;
        let searchTimer = null;

        const info = document.createElement('div');
        info.className = 'ec-info';
        info.id = 'lmd-evaluation-teacher-info';
        info.style.display = 'none';
        info.innerHTML = '<i class="fas fa-user-tie"></i><span></span>';
        matiere.closest('.ec-field')?.appendChild(info);

        function verrouillerAffectation(verrouille) {
            // L'input enseignant_id doit RESTER actif : en cas de plusieurs
            // professeurs possibles, le choix explicite accompagne la premiere
            // evaluation et devient la preuve de la classe.
            if (externeInput) externeInput.disabled = verrouille;
            if (lienExterne) lienExterne.disabled = verrouille;
            const card = externeInput?.closest('.ec-card');
            if (card) card.style.display = verrouille ? 'none' : '';
        }

        function setTeacherUserId(id) {
            if (!enseignantInput) return;
            enseignantInput.disabled = false;
            enseignantInput.value = id ? String(id) : '';
            enseignantInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function selectedElement() {
            return contexte?.is_lmd
                ? (contexte.elements || []).find(row => String(row.matiere_id) === String(matiere.value)) || null
                : null;
        }

        function isConfirmedForCurrent(row) {
            return confirmationForm && row
                && String(confirmationForm.matiereId) === String(row.matiere_id)
                && confirmationForm.userId;
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
                setTeacherUserId(null);
                span.textContent = 'En LMD, l’ECUE vient de la maquette et le professeur est résolu pour la classe.';
                return;
            }

            if (isConfirmedForCurrent(row)) {
                setTeacherUserId(confirmationForm.userId);
                span.innerHTML = '<strong>Professeur choisi pour cette classe :</strong> ' + escapeHtml(confirmationForm.name) + '. Ce choix sera porté par cette évaluation.';
                return;
            }

            if (row.enseignant_id && !row.confirmation_requise) {
                setTeacherUserId(row.enseignant_id);
                const source = row.source_enseignant === 'classe' ? 'déjà confirmé par les évaluations/séances de la classe' : 'seul professeur du pool planning';
                span.innerHTML = '<strong>' + escapeHtml(row.enseignant_nom) + '</strong> · ' + escapeHtml(source) + '.';
                return;
            }

            setTeacherUserId(null);
            const danger = row.conflit ? '<strong>Incohérence détectée.</strong> ' : '<strong>Professeur à confirmer.</strong> ';
            const action = contexte.can_assign
                ? ' <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-lmd-choose-teacher>Choisir / confirmer</button>'
                : (contexte.planning_url ? ' <a class="ec-link" href="' + escapeAttr(contexte.planning_url) + '">Ouvrir le planning LMD</a>' : '');
            span.innerHTML = danger + escapeHtml(row.message || '') + action;
            span.querySelector('[data-lmd-choose-teacher]')?.addEventListener('click', () => openModal(row));
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
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Contexte indisponible');
                contexte = json;
                if (contexte.is_lmd) applyElements(contexte.elements || []);
                else renderInfo();
            } catch (e) {
                contexte = null;
                renderInfo();
            }
        }

        function ensureModal() {
            let modalEl = document.getElementById('lmdQuickTeacherModal');
            if (modalEl) return modalEl;

            modalEl = document.createElement('div');
            modalEl.className = 'modal fade';
            modalEl.id = 'lmdQuickTeacherModal';
            modalEl.tabIndex = -1;
            modalEl.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">' +
                '<div class="modal-header"><div><h5 class="modal-title mb-0"><i class="fas fa-user-check me-2"></i>Confirmer le professeur de la classe</h5><div class="small text-muted" data-lmd-modal-context></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                '<div class="modal-body">' +
                    '<div class="alert alert-warning d-none" data-lmd-conflict></div>' +
                    '<div class="mb-3"><div class="form-label fw-semibold">Professeurs proposés par KLASSCI</div><div class="d-grid gap-2" data-lmd-candidates></div></div>' +
                    '<div class="border-top pt-3 mt-3"><label class="form-label fw-semibold">Rechercher un enseignant existant</label><input type="search" class="form-control" data-lmd-name-search placeholder="Tapez un nom…"><div class="list-group mt-2 d-none" data-lmd-search-results></div></div>' +
                    '<div class="mt-3" data-lmd-all-wrap><label class="form-label fw-semibold">Ou choisir dans la base</label><select class="form-select" data-lmd-modal-select></select></div>' +
                    '<div class="mt-3 d-none" data-lmd-create-wrap><div class="alert alert-info py-2">Cette personne ne semble pas encore exister. Vous pouvez créer rapidement sa fiche enseignant ; elle sera ajoutée au pool de cet ECUE.</div><div class="row g-2"><div class="col-md-6"><label class="form-label">Nom complet</label><input class="form-control" data-lmd-create-name></div><div class="col-md-6"><label class="form-label">E-mail (optionnel)</label><input type="email" class="form-control" data-lmd-create-email></div></div><button type="button" class="btn btn-outline-primary mt-2" data-lmd-create><i class="fas fa-user-plus me-1"></i>Créer cette nouvelle personne</button></div>' +
                    '<div class="alert alert-danger mt-3 d-none" data-lmd-modal-error></div>' +
                '</div>' +
                '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="button" class="btn btn-primary" data-lmd-modal-save>Confirmer pour cette classe</button></div>' +
            '</div></div>';
            document.body.appendChild(modalEl);
            return modalEl;
        }

        function openModal(row) {
            if (!contexte?.can_assign) return;
            const modalEl = ensureModal();
            modalEl._lmdRow = row;
            modalEl.querySelector('[data-lmd-modal-context]').textContent = (row.code ? row.code + ' — ' : '') + row.name + ' · ' + contexte.classe.name + ' · S' + contexte.semestre;

            const conflict = modalEl.querySelector('[data-lmd-conflict]');
            if (row.conflit) {
                conflict.textContent = row.message || 'Plusieurs professeurs ont été trouvés sur cette classe.';
                conflict.classList.remove('d-none');
            } else conflict.classList.add('d-none');

            const candidates = modalEl.querySelector('[data-lmd-candidates]');
            const proposed = (row.candidats && row.candidats.length ? row.candidats : row.pool) || [];
            candidates.innerHTML = proposed.length ? proposed.map(u =>
                '<button type="button" class="btn btn-outline-primary text-start" data-lmd-candidate="' + u.id + '" data-name="' + escapeAttr(u.name) + '"><i class="fas fa-user me-2"></i><strong>' + escapeHtml(u.name) + '</strong>' + (u.email ? '<span class="small text-muted ms-2">' + escapeHtml(u.email) + '</span>' : '') + '</button>'
            ).join('') : '<div class="text-muted small">Aucun professeur proposé pour le moment.</div>';
            candidates.querySelectorAll('[data-lmd-candidate]').forEach(btn => btn.addEventListener('click', () => confirmTeacher(row, btn.dataset.lmdCandidate, btn.dataset.name, modalEl)));

            const select = modalEl.querySelector('[data-lmd-modal-select]');
            select.innerHTML = '<option value="">— Choisir un enseignant —</option>' + (contexte.teachers || []).map(u => '<option value="' + u.id + '">' + escapeHtml(u.name) + (u.email ? ' · ' + escapeHtml(u.email) : '') + '</option>').join('');

            const search = modalEl.querySelector('[data-lmd-name-search]');
            const results = modalEl.querySelector('[data-lmd-search-results]');
            const createWrap = modalEl.querySelector('[data-lmd-create-wrap]');
            search.value = '';
            results.classList.add('d-none'); results.innerHTML = '';
            createWrap.classList.add('d-none');
            modalEl.querySelector('[data-lmd-modal-error]').classList.add('d-none');

            search.oninput = function () {
                clearTimeout(searchTimer);
                const q = search.value.trim();
                if (q.length < 2) { results.classList.add('d-none'); createWrap.classList.add('d-none'); return; }
                searchTimer = setTimeout(() => searchExisting(row, q, modalEl), 300);
            };

            modalEl.querySelector('[data-lmd-modal-save]').onclick = function () {
                const opt = select.options[select.selectedIndex];
                if (select.value) confirmTeacher(row, select.value, opt.textContent.split(' · ')[0], modalEl);
            };
            modalEl.querySelector('[data-lmd-create]').onclick = () => quickCreate(row, modalEl);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        async function searchExisting(row, name, modalEl) {
            const results = modalEl.querySelector('[data-lmd-search-results]');
            const createWrap = modalEl.querySelector('[data-lmd-create-wrap]');
            try {
                const url = new URL(contexte.duplicate_search_url, window.location.origin);
                url.searchParams.set('name', name);
                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const json = await res.json();
                const duplicates = json.duplicates || [];
                results.innerHTML = duplicates.map(t => '<button type="button" class="list-group-item list-group-item-action" data-user-id="' + (t.user_id || '') + '" data-teacher-id="' + t.id + '" data-name="' + escapeAttr(t.name) + '"><strong>' + escapeHtml(t.name) + '</strong>' + (t.email ? '<small class="d-block text-muted">' + escapeHtml(t.email) + '</small>' : '') + '</button>').join('');
                results.classList.toggle('d-none', duplicates.length === 0);

                // L'endpoint historique renvoie le profil enseignant mais pas
                // toujours son user_id. Dans ce cas on retrouve le User par la
                // liste deja chargee dans le contexte, sans deviner un ID.
                results.querySelectorAll('button').forEach(btn => btn.addEventListener('click', () => {
                    const match = (contexte.teachers || []).find(u => String(u.id) === String(btn.dataset.userId) || normalise(u.name) === normalise(btn.dataset.name));
                    if (match) confirmTeacher(row, match.id, match.name, modalEl);
                }));

                if (contexte.can_create_teacher) {
                    createWrap.classList.remove('d-none');
                    modalEl.querySelector('[data-lmd-create-name]').value = name;
                }
            } catch (e) {
                results.classList.add('d-none');
                if (contexte.can_create_teacher) createWrap.classList.remove('d-none');
            }
        }

        async function quickCreate(row, modalEl) {
            if (!contexte.can_create_teacher) return;
            const button = modalEl.querySelector('[data-lmd-create]');
            const error = modalEl.querySelector('[data-lmd-modal-error]');
            const name = modalEl.querySelector('[data-lmd-create-name]').value.trim();
            const email = modalEl.querySelector('[data-lmd-create-email]').value.trim();
            if (!name) return;
            button.disabled = true;
            try {
                const res = await fetch(contexte.quick_create_url, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf || '', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ name, email: email || null, specialization: row.name || 'Enseignement LMD', planification_id: row.planification_id || null })
                });
                const json = await res.json();
                if (!res.ok || !json.success) throw new Error(json.message || Object.values(json.errors || {})[0]?.[0] || 'Création impossible');
                const userId = json.teacher?.user_id;
                if (!userId) throw new Error('La fiche a été créée mais son compte utilisateur est introuvable.');
                await confirmTeacher(row, userId, json.teacher?.user?.name || name, modalEl);
            } catch (e) {
                error.textContent = e.message; error.classList.remove('d-none');
            } finally { button.disabled = false; }
        }

        async function confirmTeacher(row, userId, name, modalEl) {
            const error = modalEl.querySelector('[data-lmd-modal-error]');
            const buttons = modalEl.querySelectorAll('button');
            buttons.forEach(b => { if (!b.hasAttribute('data-bs-dismiss')) b.disabled = true; });
            error.classList.add('d-none');
            try {
                const res = await fetch('/esbtp/lmd/evaluation-teacher/assign', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf || '', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ classe_id: classe.value, annee_universitaire_id: contexte.annee_universitaire_id, periode: periode.value, matiere_id: row.matiere_id, enseignant_id: userId })
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || Object.values(json.errors || {})[0]?.[0] || 'Confirmation impossible');
                confirmationForm = { matiereId: row.matiere_id, userId: json.enseignant_id || userId, name: json.enseignant_name || name };
                setTeacherUserId(confirmationForm.userId);
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                await loadContext();
                renderInfo();
            } catch (e) {
                error.textContent = e.message; error.classList.remove('d-none');
            } finally { buttons.forEach(b => b.disabled = false); }
        }

        form.addEventListener('submit', function (event) {
            if (!contexte?.is_lmd) return;
            const row = selectedElement();
            if (!row) return;
            if ((row.enseignant_id && !row.confirmation_requise) || isConfirmedForCurrent(row)) return;
            event.preventDefault();
            renderInfo();
            if (contexte.can_assign) openModal(row);
        });

        classe.addEventListener('change', function () { confirmationForm = null; loadContext(); });
        periode.addEventListener('change', function () { confirmationForm = null; loadContext(); });
        matiere.addEventListener('change', renderInfo);

        new MutationObserver(function () {
            if (contexte?.is_lmd && currentSignature() !== expectedSignature) applyElements(contexte.elements || []);
        }).observe(matiere, { childList: true });

        loadContext();
    }

    function normalise(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr').replace(/\s+/g, ' ').trim();
    }

    function escapeHtml(value) {
        const d = document.createElement('div'); d.textContent = value == null ? '' : String(value); return d.innerHTML;
    }

    function escapeAttr(value) { return escapeHtml(value).replace(/"/g, '&quot;'); }

    function scan() { boot(document); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
    document.addEventListener('shown.bs.modal', scan);
})();
