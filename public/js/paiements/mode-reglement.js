/* Formulaire premium partagé par paiements.index et paiements.show.
 * Les fragments AJAX ne rejouent pas leurs balises <script> : définition
 * unique, chargée dans la page, et non répétée sur chaque versement. */
window.modeReglementForm = window.modeReglementForm || function (modeActuel) {
    return {
        confirme: false,
        mode: modeActuel,
        motif: '',
        libelle(cle) {
            const option = Array.from(this.$root.querySelectorAll('option'))
                .find(el => el.value === cle);
            return option ? option.textContent.trim() : cle;
        },
        valide() {
            return this.mode !== ''
                && this.mode !== modeActuel
                && this.motif.trim().length >= 10;
        },
    };
};

// Les fragments AJAX peuvent présenter un Avoir même si la première page
// n'en avait pas : précharger la même fonction que le partial historique,
// dont le <script> est conservé pour les autres écrans de paiements.
window.avoirForm = window.avoirForm || function (max) {
    return {
        confirme: false,
        kind: 'credit',
        montant: max,
        motif: '',
        format(n) {
            return new Intl.NumberFormat('fr-FR').format(n || 0);
        },
        valide() {
            return this.kind !== ''
                && this.montant > 0
                && this.montant <= max
                && this.motif.trim().length >= 10;
        },
    };
};
