document.addEventListener('DOMContentLoaded', function() {
    const yearForm = document.getElementById('lmdYearForm');
    const yearSelect = document.getElementById('lmd_annee_universitaire_id');
    const feedback = document.getElementById('lmd-year-feedback');
    const results = document.getElementById('lmd-classes-results');
    const reset = document.getElementById('lmd-reset-filters');
    if (!yearForm || !yearSelect || !results) return;

    let requestSequence = 0;
    let inflight = null;
    let restoring = false;
    let searchTimer = null;
    const names = ['annee_universitaire_id', 'search', 'filiere_id', 'niveau_id', 'statut', 'capacite'];
    const values = () => Object.fromEntries(names.map(name => [name, String(yearForm.elements.namedItem(name)?.value ?? '')]));
    let committed = values();

    function restoreCommitted() {
        restoring = true;
        names.forEach(name => {
            const element = yearForm.elements.namedItem(name);
            if (!element) return;
            element.value = committed[name] ?? '';
            if (element.tagName === 'SELECT') {
                element.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        restoring = false;
    }

    async function refreshClasses() {
        if (restoring) return;
        const requested = values();
        const newYear = Number(requested.annee_universitaire_id) || null;
        // Never discard a live grade sheet while changing academic context.
        if (newYear !== lmdAcademicYearId && document.getElementById('modalNotes')?.classList.contains('show')) {
            feedback.textContent = 'Fermez la saisie des notes avant de changer d’année.';
            restoreCommitted();
            return;
        }
        inflight?.abort();
        const controller = new AbortController();
        inflight = controller;
        const sequence = ++requestSequence;
        feedback.textContent = 'Actualisation des classes…';
        yearForm.setAttribute('aria-busy', 'true');
        results.classList.add('ln-results-loading');

        const url = new URL(yearForm.action, window.location.origin);
        for (const [name, value] of Object.entries(requested)) {
            if (value !== '') url.searchParams.set(name, value);
        }
        url.searchParams.set('classes_ajax', '1');

        try {
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            if (!data.success || typeof data.html !== 'string') throw new Error('Réponse de filtrage invalide.');
            if (sequence !== requestSequence) return;

            const yearChanged = newYear !== lmdAcademicYearId;
            if (yearChanged) {
                lmdAcademicYearId = newYear;
                anneeSuivi = newYear;
                currentClasseId = null;
                currentClasseData = null;
                currentMatiereId = null;
                currentMatiereName = '';
                evaluationsData = {};
                notesData = {};
                evalParamsCache = {};
                classeSemestres = [];
                document.querySelectorAll('#evalCreateForm input[name="annee_universitaire_id"]').forEach(
                    input => { input.value = String(newYear ?? ''); }
                );
                const context = document.getElementById('lmd-year-context');
                if (context) context.textContent = 'Gestion des notes par classe — ' + data.annee_name;
                const heroYear = document.querySelector('.ln-hero-year');
                if (heroYear) {
                    heroYear.textContent = data.annee_name;
                }
                window.dispatchEvent(new CustomEvent('couverture:contexte', {
                    detail: { classe_id: null, annee_universitaire_id: newYear, periode: 'semestre1' }
                }));
            }

            results.innerHTML = data.html;
            document.getElementById('lmd-filter-count').textContent = data.total_classes;
            const kpis = document.querySelectorAll('.ln-hero-kpis .ln-kpi-value');
            if (kpis.length === 3) {
                kpis[0].textContent = data.total_classes;
                kpis[1].textContent = data.total_etudiants;
                kpis[2].textContent = data.total_evaluations;
            }
            committed = requested;
            feedback.textContent = '';
            const currentUrl = new URL(window.location.href);
            names.forEach(name => {
                if (requested[name] === '') currentUrl.searchParams.delete(name);
                else currentUrl.searchParams.set(name, requested[name]);
            });
            currentUrl.searchParams.delete('classe');
            currentUrl.searchParams.delete('ecue');
            history.replaceState(history.state, '', currentUrl.toString());
        } catch (error) {
            if (sequence !== requestSequence || error.name === 'AbortError') return;
            console.error('Filtres des notes LMD :', error);
            restoreCommitted();
            feedback.textContent = 'Impossible de charger les classes. Vos filtres précédents sont conservés.';
        } finally {
            if (sequence === requestSequence) {
                yearForm.removeAttribute('aria-busy');
                results.classList.remove('ln-results-loading');
                inflight = null;
            }
        }
    }

    yearForm.addEventListener('submit', event => {
        event.preventDefault();
        clearTimeout(searchTimer);
        refreshClasses();
    });
    yearForm.querySelectorAll('select').forEach(select => select.addEventListener('change', refreshClasses));
    const search = yearForm.querySelector('[name="search"]');
    search?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(refreshClasses, 300);
    });
    reset?.addEventListener('click', () => {
        clearTimeout(searchTimer);
        restoring = true;
        // Ne pas utiliser form.reset() : les valeurs initiales peuvent venir
        // de l'URL et etre deja filtrees. Reinitialiser explicitement.
        names.filter(name => name !== 'annee_universitaire_id').forEach(name => {
            const control = yearForm.elements.namedItem(name);
            if (control) control.value = '';
        });
        yearSelect.value = String(lmdAcademicYearId ?? '');
        yearForm.querySelectorAll('select').forEach(select => {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
        restoring = false;
        refreshClasses();
    });
});

