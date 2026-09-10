{{--
    Fait suivre au bandeau de couverture deux sélecteurs natifs de la page.

    Les formulaires d'évaluation ne sont pas pilotés par Alpine : leur classe et
    leur période vivent dans des `<select>` (ceux que `<x-au-select>` garde
    cachés et sur lesquels il émet un `change`). Ce petit pont écoute ces deux
    champs et annonce le contexte, plutôt que d'exiger du bandeau qu'il connaisse
    la structure de chaque page hôte.

    Sans `@once` : ces formulaires sont aussi chargés en modale AJAX, où un
    `@push` serait avalé. Le garde par identifiant évite le double branchement.

    @param string   $selectClasse   sélecteur CSS du champ classe
    @param string   $selectPeriode  sélecteur CSS du champ période (optionnel)
    @param int|null $anneeId
    @param string   $periodeDefaut  période quand le champ est vide
--}}
@php
    $_cvnPont = [
        'classe' => $selectClasse ?? '#classe_id',
        'periode' => $selectPeriode ?? null,
        'anneeId' => isset($anneeId) && $anneeId ? (int) $anneeId : null,
        'periodeDefaut' => $periodeDefaut ?? 'annuel',
    ];
@endphp
<script>
(function () {
    var pont = @js($_cvnPont);

    function annoncer() {
        var champClasse = document.querySelector(pont.classe);
        var champPeriode = pont.periode ? document.querySelector(pont.periode) : null;

        window.dispatchEvent(new CustomEvent('couverture:contexte', {
            detail: {
                classe_id: champClasse && champClasse.value ? champClasse.value : null,
                annee_universitaire_id: pont.anneeId,
                periode: (champPeriode && champPeriode.value) || pont.periodeDefaut,
            },
        }));
    }

    function brancher() {
        [pont.classe, pont.periode].forEach(function (selecteur) {
            if (!selecteur) { return; }
            var champ = document.querySelector(selecteur);
            // Un champ deja branche ne doit pas l'etre deux fois : la modale
            // AJAX rejoue ce script a chaque ouverture.
            if (!champ || champ.dataset.cvnBranche === '1') { return; }
            champ.dataset.cvnBranche = '1';
            champ.addEventListener('change', annoncer);
        });

        annoncer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', brancher);
    } else {
        brancher();
    }
})();
</script>
