/**
 * Liste des inscriptions : confirmer en masse le statut redoublant.
 *
 * Confirme la valeur qui fait foi des inscriptions sélectionnées qui
 * l'attendaient. Rien n'est changé : une correction se fait sur la fiche,
 * avec un motif. S'appuie sur window.iiSelection (inscriptions/index.js).
 */
(function () {
    'use strict';

    window.iiBulkConfirmerRedoublant = async function () {
        const sel = window.iiSelection;
        if (!sel) return;
        if (sel.vide()) {
            sel.toast('Veuillez sélectionner au moins une inscription.', 'warning');
            return;
        }
        const formData = new FormData();
        formData.append('_token', window.KLASSCI_CSRF_TOKEN || '');
        const choix = sel.ajouter(formData);
        const ok = await window.iiConfirm({
            title: 'Confirmer le statut redoublant',
            message: `Confirmer le statut redoublant de ${choix.n.toLocaleString('fr-FR')} inscription(s), tel qu'il est affiché ? Seules celles qui attendaient une confirmation sont concernées. Pour changer une valeur, ouvrez la fiche et cliquez sur « Corriger ».`,
            confirmLabel: 'Confirmer',
        });
        if (!ok) return;

        try {
            const r = await fetch((window.KLASSCI_INSCRIPTIONS_ROUTES || {}).confirmerRedoublant, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            if (!r.ok) throw new Error(await sel.erreur(r, 'Confirmation impossible.'));
            const data = await r.json();
            sel.toast(data.message, data.confirmees > 0 ? 'success' : 'info');
            sel.effacer();
            sel.recharger();
        } catch (err) {
            sel.toast(err.message || 'Confirmation impossible.', 'error');
        }
    };
})();
