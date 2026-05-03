{{--
    Shared JS partial for /esbtp/annonces/create + /esbtp/annonces/edit.
    Provides Choices.js init, snapshots, char counters, file preview,
    audience reflection, recipient summaries, modal filters, bulk actions
    and submit validation. Edit/Create page-specific behaviours
    (leave-draft modal for create, delete modal for edit) live in their
    own @push('scripts') block, after this partial is included.

    Required @props equivalent (Blade @include vars):
      - $debugTag : string  ('annonces:create' | 'annonces:edit')
--}}
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
(function () {
    'use strict';

    const DEBUG = @json(config('app.debug'));
    const debugLog   = (...args) => { if (DEBUG) console.log('[{{ $debugTag ?? 'annonces:form' }}]', ...args); };
    const debugError = (...args) => { if (DEBUG) console.error('[{{ $debugTag ?? 'annonces:form' }}]', ...args); };

    // ----- État global Choices.js (exposé pour scripts page-specific) -----
    const choicesInstances = {};
    const originalClassesOptions = [];
    const originalEtudiantsOptions = [];

    const baseChoicesConfig = {
        searchEnabled: true,
        searchChoices: true,
        searchFloor: 1,
        searchResultLimit: 12,
        shouldSort: false,
        placeholder: true,
        noResultsText: 'Aucun résultat',
        noChoicesText: 'Aucun choix disponible',
        itemSelectText: '',
        loadingText: 'Recherche…',
        removeItemButton: true,
        duplicateItemsAllowed: false,
        renderChoiceLimit: 30,
        position: 'bottom',
        allowHTML: true,
    };

    function initChoices(selectEl, extra = {}) {
        if (!selectEl) return null;
        const id = selectEl.id;
        if (choicesInstances[id]) {
            choicesInstances[id].destroy();
            delete choicesInstances[id];
        }
        try {
            const inst = new Choices(selectEl, { ...baseChoicesConfig, ...extra });
            choicesInstances[id] = inst;
            return inst;
        } catch (err) {
            debugError('Init Choices fail', id, err);
            return null;
        }
    }

    function snapshotOriginalOptions(selectEl, target, customMapper) {
        if (!selectEl) return;
        Array.from(selectEl.options).forEach(opt => {
            if (!opt.value) return;
            target.push({
                value: opt.value,
                label: opt.textContent.trim(),
                selected: opt.selected,
                disabled: false,
                customProperties: customMapper(opt),
            });
        });
    }

    // Notification utilisateur — préfère window.showToast, fallback alert
    function notifyUser(message, level = 'warning') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, level);
        } else {
            alert(message);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {

        // --- 1. Snapshot des options originales ---
        const classesSelect = document.getElementById('classes');
        const etudiantsSelect = document.getElementById('etudiants');

        snapshotOriginalOptions(classesSelect, originalClassesOptions, opt => ({
            filiere: opt.dataset.filiere,
            niveau: opt.dataset.niveau,
            currentCount: opt.dataset.currentCount,
        }));
        snapshotOriginalOptions(etudiantsSelect, originalEtudiantsOptions, opt => ({
            classe: opt.dataset.classe,
            currentYear: opt.dataset.currentYear,
        }));
        debugLog('Snapshots', { classes: originalClassesOptions.length, etudiants: originalEtudiantsOptions.length });

        // --- 2. Init Choices.js ---
        if (classesSelect) {
            initChoices(classesSelect, {
                placeholderValue: 'Tapez pour rechercher une classe…',
                maxItemCount: 20,
            });
        }
        if (etudiantsSelect) {
            initChoices(etudiantsSelect, {
                placeholderValue: 'Tapez pour rechercher un étudiant…',
                maxItemCount: 50,
            });
        }

        // --- 3. Compteurs caractères live ---
        document.querySelectorAll('[data-counter-for]').forEach(counterEl => {
            const targetId = counterEl.getAttribute('data-counter-for');
            const target = document.getElementById(targetId);
            if (!target) return;
            const max = parseInt(counterEl.getAttribute('data-max'), 10) || null;
            const display = counterEl.querySelector('[data-counter-current]');
            const update = () => {
                const len = target.value.length;
                if (display) display.textContent = len;
                if (max) {
                    counterEl.classList.remove('ac-counter--warn', 'ac-counter--danger');
                    if (len > max * 0.95) counterEl.classList.add('ac-counter--danger');
                    else if (len > max * 0.80) counterEl.classList.add('ac-counter--warn');
                }
            };
            target.addEventListener('input', update);
            update();
        });

        // --- 4. File upload preview (gère existing file pour edit via #ac-existing-file) ---
        const fileInput = document.getElementById('piece_jointe');
        const fileZone = document.getElementById('ac-file-zone');
        const fileName = document.getElementById('ac-file-name');
        const fileLabel = document.getElementById('ac-file-label');
        const hasExisting = !!document.getElementById('ac-existing-file');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    const file = this.files[0];
                    const sizeKb = Math.round(file.size / 1024);
                    fileName.textContent = `${file.name} · ${sizeKb} KB`;
                    fileLabel.textContent = hasExisting
                        ? 'Nouveau fichier prêt — remplacera l\'ancien'
                        : 'Fichier prêt à être envoyé';
                    fileZone.classList.add('ac-file--has-file');
                } else {
                    fileLabel.textContent = hasExisting
                        ? 'Cliquez pour remplacer le fichier'
                        : 'Cliquez ou déposez un fichier';
                    fileZone.classList.remove('ac-file--has-file');
                }
            });
        }

        // --- 5. Pickers : afficher selon le type radio ---
        const classesPicker = document.getElementById('classes_picker');
        const etudiantsPicker = document.getElementById('etudiants_picker');

        function reflectAudience() {
            const checked = document.querySelector('input[name="type"]:checked');
            const value = checked ? checked.value : 'general';
            if (classesPicker) {
                classesPicker.classList.toggle('ac-picker--show', value === 'classe');
                if (value === 'classe') classesPicker.classList.add('ac-fade-enter');
            }
            if (etudiantsPicker) {
                etudiantsPicker.classList.toggle('ac-picker--show', value === 'etudiant');
                if (value === 'etudiant') etudiantsPicker.classList.add('ac-fade-enter');
            }
        }
        document.querySelectorAll('input[name="type"]').forEach(r => {
            r.addEventListener('change', reflectAudience);
        });
        reflectAudience();

        // --- 6. Récap classes/étudiants sélectionnés ---
        function updateRecipientSummaries() {
            const cInst = choicesInstances['classes'];
            const eInst = choicesInstances['etudiants'];

            if (cInst) {
                const sel = cInst.getValue(true);
                const count = sel.length;
                const studentCount = sel.reduce((s, v) => {
                    const o = originalClassesOptions.find(it => String(it.value) === String(v));
                    const c = parseInt(o?.customProperties?.currentCount || 0, 10);
                    return s + (Number.isNaN(c) ? 0 : c);
                }, 0);
                const sumEl = document.getElementById('classes_summary');
                const badge = document.getElementById('classes_count_badge');
                if (sumEl) {
                    sumEl.textContent = count > 0
                        ? `${count} classe(s) • ${studentCount} étudiant(s) en année courante`
                        : 'Aucune classe sélectionnée';
                }
                if (badge) {
                    badge.textContent = count;
                    badge.classList.toggle('ac-picker-count-badge--empty', count === 0);
                }
            }

            if (eInst) {
                const sel = eInst.getValue(true);
                const count = sel.length;
                const cyCount = sel.reduce((s, v) => {
                    const o = originalEtudiantsOptions.find(it => String(it.value) === String(v));
                    return s + (String(o?.customProperties?.currentYear) === '1' ? 1 : 0);
                }, 0);
                const sumEl = document.getElementById('etudiants_summary');
                const badge = document.getElementById('etudiants_count_badge');
                if (sumEl) {
                    sumEl.textContent = count > 0
                        ? `${count} étudiant(s) • ${cyCount} en année courante`
                        : 'Aucun étudiant sélectionné';
                }
                if (badge) {
                    badge.textContent = count;
                    badge.classList.toggle('ac-picker-count-badge--empty', count === 0);
                }
            }
        }
        document.addEventListener('change', e => {
            if (e.target.id === 'classes' || e.target.id === 'etudiants') {
                updateRecipientSummaries();
            }
        });
        updateRecipientSummaries();

        // --- 7. Filtres modals ---
        function applyClassesFilter() {
            const inst = choicesInstances['classes'];
            if (!inst || originalClassesOptions.length === 0) return;
            const filiereId = document.getElementById('filiere_filter')?.value || '';
            const niveauId  = document.getElementById('niveau_filter')?.value || '';
            const current   = inst.getValue(true);

            const filtered = originalClassesOptions.filter(opt => {
                if (filiereId && opt.customProperties.filiere && String(opt.customProperties.filiere) !== String(filiereId)) return false;
                if (niveauId && opt.customProperties.niveau && String(opt.customProperties.niveau) !== String(niveauId)) return false;
                return true;
            });

            inst.clearStore();
            inst.setChoices(filtered, 'value', 'label', true);
            current.forEach(v => {
                if (filtered.some(c => c.value === v)) inst.setChoiceByValue(v);
            });
            const totalEl = document.getElementById('classes_total_visible');
            if (totalEl) totalEl.textContent = filtered.length;
        }

        function applyEtudiantsFilter() {
            const inst = choicesInstances['etudiants'];
            if (!inst || originalEtudiantsOptions.length === 0) return;
            const classeId = document.getElementById('classe_etudiant_filter')?.value || '';
            const current  = inst.getValue(true);

            const filtered = originalEtudiantsOptions.filter(opt => {
                if (classeId && opt.customProperties.classe && String(opt.customProperties.classe) !== String(classeId)) return false;
                return true;
            });

            inst.clearStore();
            inst.setChoices(filtered, 'value', 'label', true);
            current.forEach(v => {
                if (filtered.some(c => c.value === v)) inst.setChoiceByValue(v);
            });
            const info = document.getElementById('etudiants-info');
            if (info) {
                info.innerHTML = filtered.length > 0
                    ? `<strong>${filtered.length}</strong> étudiant(s) disponible(s)`
                    : 'Aucun étudiant disponible avec ce filtre';
            }
        }

        document.getElementById('filiere_filter')?.addEventListener('change', applyClassesFilter);
        document.getElementById('niveau_filter')?.addEventListener('change', applyClassesFilter);
        document.getElementById('classe_etudiant_filter')?.addEventListener('change', applyEtudiantsFilter);

        document.querySelectorAll('.reset-filters').forEach(btn => {
            btn.addEventListener('click', () => {
                const f = document.getElementById('filiere_filter'); if (f) f.value = '';
                const n = document.getElementById('niveau_filter'); if (n) n.value = '';
                const c = document.getElementById('classe_etudiant_filter'); if (c) c.value = '';
                applyClassesFilter();
                applyEtudiantsFilter();
            });
        });

        // --- 8. Bulk : Tout sélectionner / vider ---
        function selectAll(instKey) {
            const inst = choicesInstances[instKey];
            if (!inst) return;
            const choices = inst._currentState?.choices || [];
            choices.filter(c => !c.disabled).forEach(c => {
                inst._addItem({ value: c.value, label: c.label, id: c.id });
            });
        }
        function clearAll(instKey, source) {
            const inst = choicesInstances[instKey];
            if (!inst) return;
            const cleared = source.map(o => ({ ...o, selected: false }));
            inst.clearStore();
            inst.setChoices(cleared, 'value', 'label', true);
            updateRecipientSummaries();
        }

        document.getElementById('select_all_classes')?.addEventListener('click', () => selectAll('classes'));
        document.getElementById('select_all_etudiants')?.addEventListener('click', () => selectAll('etudiants'));
        document.getElementById('clear_classes_selection')?.addEventListener('click', () => clearAll('classes', originalClassesOptions));
        document.getElementById('clear_etudiants_selection')?.addEventListener('click', () => clearAll('etudiants', originalEtudiantsOptions));

        // --- 9. Validation submit (au moins 1 classe/étudiant si type contraint) ---
        const form = document.getElementById('annonceForm');
        form?.addEventListener('submit', function (e) {
            const checked = document.querySelector('input[name="type"]:checked');
            const type = checked ? checked.value : 'general';

            if (type === 'classe') {
                const inst = choicesInstances['classes'];
                if (inst && inst.getValue().length === 0) {
                    e.preventDefault();
                    notifyUser('Veuillez sélectionner au moins une classe.', 'warning');
                    document.querySelector('[data-bs-target="#classesModal"]')?.click();
                    return;
                }
            } else if (type === 'etudiant') {
                const inst = choicesInstances['etudiants'];
                if (inst && inst.getValue().length === 0) {
                    e.preventDefault();
                    notifyUser('Veuillez sélectionner au moins un étudiant.', 'warning');
                    document.querySelector('[data-bs-target="#etudiantsModal"]')?.click();
                    return;
                }
            }
        });

        // Expose pour scripts page-specific (leave-draft sur create, delete sur edit)
        window.__annonceFormShared = { form, choicesInstances };
    });
})();
</script>
