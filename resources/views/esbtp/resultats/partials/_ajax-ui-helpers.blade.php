    function initCoefficientsAfterSwap() {
        if (typeof window.scmInitCoefficientsModal !== 'function') {
            var source = document.querySelector('#etudiant-resultats-content script[data-scm-init-script]');
            if (source) {
                var script = document.createElement('script');
                script.textContent = source.textContent;
                source.replaceWith(script);
            }
        }
        if (typeof window.scmInitCoefficientsModal === 'function') {
            window.scmInitCoefficientsModal();
        }
    }

    function srAnnounceCoverage() {
        var form = document.getElementById('sr-filter-form');
        if (!form) return;
        var classe = form.querySelector('[name="classe_id"]');
        var annee = form.querySelector('[name="annee_universitaire_id"]');
        var periode = document.getElementById('sr-periode-input');
        window.dispatchEvent(new CustomEvent('couverture:contexte', {
            detail: { classe_id: classe && classe.value, annee_universitaire_id: annee && annee.value, periode: periode && periode.value || 'annuel' }
        }));
    }

    window.srReinitResultsAfterSave = function () {
        initStudentResults(true);
        initCoefficientsAfterSwap();
    };

