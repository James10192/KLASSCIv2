{{-- Help modal open/close + interactive tour. Independent from Alpine. --}}
@push('scripts')
<script>
(function () {
    const helpBackdrop = document.getElementById('lpHelpBackdrop');
    function openHelp() { if (helpBackdrop) helpBackdrop.classList.add('lp-help-open'); }
    function closeHelp() { if (helpBackdrop) helpBackdrop.classList.remove('lp-help-open'); }
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-page-help-open]')) { e.preventDefault(); openHelp(); }
        if (e.target.closest('[data-help-close]')) { e.preventDefault(); closeHelp(); }
        if (e.target === helpBackdrop) closeHelp();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && helpBackdrop && helpBackdrop.classList.contains('lp-help-open')) closeHelp();
    });

    const tourSteps = [
        { selector: '[data-tour-node="kpis"]', icon: 'fa-chart-line', title: 'Vue d\'ensemble',
          text: 'Total d\'UE, ECUE et crédits CECT pour le parcours / niveau / semestre choisi.' },
        { selector: '[data-tour-node="filter-parcours"]', icon: 'fa-route', title: 'Choisis un parcours',
          text: 'Sélectionne d\'abord la spécialité. Le reste s\'adapte ensuite.' },
        { selector: '[data-tour-node="filter-niveau"]', icon: 'fa-layer-group', title: 'Choisis l\'année',
          text: 'L1, L2, L3, M1... Le filtre semestre se restreint automatiquement.' },
        { selector: '[data-tour-node="filter-semestre"]', icon: 'fa-calendar-alt', title: 'Choisis le semestre',
          text: 'Seuls les semestres réellement importés pour cette année apparaissent.' },
        { selector: '[data-tour-node="listing"] .lp-card-actions', icon: 'fa-link', title: 'Modifier les UE',
          text: 'Lie ou délie des UE à ce parcours·semestre. Changements visibles immédiatement.' },
        { selector: '[data-tour-node="listing"] .lp-table tbody', icon: 'fa-sitemap', title: 'Hiérarchie UE → ECUE',
          text: 'Clique sur une UE pour déplier ses ECUEs (CM/TD/TP/Projet/TPE et CECT).' },
        { selector: '[data-tour-node="listing"]', icon: 'fa-clock', title: 'Volumes UEMOA',
          text: 'Colonnes CM/TD/TP/Projet/TPE viennent des planifications. Total mis en évidence.' },
    ];

    let tourState = { idx: 0, active: false };

    function visibleSteps() {
        return tourSteps.filter(s => {
            const el = document.querySelector(s.selector);
            if (!el) return false;
            const r = el.getBoundingClientRect();
            return r.width > 0 && r.height > 0;
        });
    }

    function cleanup() {
        document.querySelectorAll('.lp-tour-highlight').forEach(el => el.classList.remove('lp-tour-highlight'));
        const card = document.getElementById('lpTourCard');
        if (card) card.remove();
        const ov = document.getElementById('lpTourOverlay');
        if (ov) ov.classList.remove('lp-tour-open');
    }

    function endTour() { tourState.active = false; cleanup(); }

    function showStep() {
        cleanup();
        const steps = visibleSteps();
        if (steps.length === 0 || tourState.idx >= steps.length) { endTour(); return; }
        if (tourState.idx < 0) tourState.idx = 0;

        const step = steps[tourState.idx];
        const target = document.querySelector(step.selector);
        if (!target) { tourState.idx++; showStep(); return; }

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('lp-tour-highlight');
        const ov = document.getElementById('lpTourOverlay');
        if (ov) ov.classList.add('lp-tour-open');

        const card = document.createElement('div');
        card.className = 'lp-tour-card';
        card.id = 'lpTourCard';
        const isLast = tourState.idx === steps.length - 1;
        const isFirst = tourState.idx === 0;
        card.innerHTML =
            '<div class="lp-tour-card-progress">Étape ' + (tourState.idx + 1) + ' / ' + steps.length + '</div>' +
            '<h4><i class="fas ' + step.icon + '"></i>' + step.title + '</h4>' +
            '<p>' + step.text + '</p>' +
            '<div class="lp-tour-card-nav">' +
                '<button type="button" class="lp-tour-btn lp-tour-btn-ghost" data-tour-action="quit">Quitter</button>' +
                (isFirst ? '' : '<button type="button" class="lp-tour-btn lp-tour-btn-secondary" data-tour-action="prev">Retour</button>') +
                '<button type="button" class="lp-tour-btn lp-tour-btn-primary" data-tour-action="next">' + (isLast ? 'Terminer' : 'Suivant') + '</button>' +
            '</div>';
        document.body.appendChild(card);

        const r = target.getBoundingClientRect();
        const cardW = 320;
        const cardH = card.offsetHeight || 180;
        const margin = 14;
        const vw = window.innerWidth, vh = window.innerHeight;
        let top = r.bottom + margin + window.scrollY;
        let left = r.left + window.scrollX;
        if (top + cardH > window.scrollY + vh - margin) top = r.top + window.scrollY - cardH - margin;
        if (top < window.scrollY + margin) top = window.scrollY + margin;
        if (left + cardW > vw - margin) left = vw - cardW - margin;
        if (left < margin) left = margin;
        if (vw > 768) { card.style.top = top + 'px'; card.style.left = left + 'px'; }
        else { card.style.bottom = '1rem'; card.style.left = '1rem'; card.style.right = '1rem'; }
    }

    function startTour() { tourState = { idx: 0, active: true }; showStep(); }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-page-tour-open]')) { e.preventDefault(); startTour(); return; }
        const action = e.target.closest('[data-tour-action]');
        if (action && tourState.active) {
            const a = action.dataset.tourAction;
            if (a === 'quit') endTour();
            else if (a === 'prev') { tourState.idx--; showStep(); }
            else if (a === 'next') {
                const steps = visibleSteps();
                if (tourState.idx >= steps.length - 1) endTour();
                else { tourState.idx++; showStep(); }
            }
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && tourState.active) endTour();
    });
})();
</script>
@endpush
