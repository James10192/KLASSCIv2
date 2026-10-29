{{-- Première page : structure complète du tableau. Les pages suivantes
     (lignes-etudiants) ajoutent leurs lignes au même tbody. --}}
<div class="table-responsive rsl-table-wrap">
    <table class="table align-middle mb-0 rsl-table">
        <thead>
            <tr>
                <th class="rsl-th-check">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-all" aria-label="Tout sélectionner">
                    </div>
                </th>
                <th>Matricule</th>
                <th>Nom et prénom</th>
                @if(!isset($classe) || !$classe)
                <th>Classe</th>
                @endif
                <th>Moyenne</th>
                <th>Rang</th>
                <th>Statut</th>
                <th class="rsl-th-actions">Actions</th>
            </tr>
        </thead>
        <tbody id="rsl-tbody">
            @foreach($etudiants as $etudiant)
                @include('esbtp.resultats.partials._ligne-etudiant')
            @endforeach
        </tbody>
    </table>
</div>

@isset($paginateurListe)
    {{-- Suite de la liste : public/js/liste-infinie.js rappelle load-etudiants
         avec les mêmes filtres (page, mode=rows) et ajoute les lignes au tbody. --}}
    <x-liste-infinie :paginateur="$paginateurListe" cible="#rsl-tbody" libelle="étudiants" class="rsl-bas" />
@endisset

{{-- Modal choix de période pour bulletin --}}
<div class="modal fade" id="modalChoixPeriodeBulletin" tabindex="-1" aria-labelledby="modalChoixPeriodeBulletinLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChoixPeriodeBulletinLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Choisir la période
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-3">Sélectionnez le semestre pour ce bulletin :</p>
                <div class="d-grid gap-2">
                    <a href="#" id="btnBulletinS1" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i>Semestre 1
                    </a>
                    <a href="#" id="btnBulletinS2" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i>Semestre 2
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var modal = document.getElementById('modalChoixPeriodeBulletin');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        var etudiantId = btn.getAttribute('data-etudiant-id');
        var classeId   = btn.getAttribute('data-classe-id');
        var anneeId    = btn.getAttribute('data-annee-id');
        var action     = btn.getAttribute('data-action'); // 'show' or 'pdf'

        var btnS1 = document.getElementById('btnBulletinS1');
        var btnS2 = document.getElementById('btnBulletinS2');

        if (action === 'show') {
            // Route: esbtp.resultats.etudiant.preview → /esbtp/resultats/etudiant/{id}/preview
            var baseUrl = '{{ url("/esbtp/resultats/etudiant") }}/' + etudiantId + '/preview';
            btnS1.href = baseUrl + '?classe_id=' + classeId + '&annee_universitaire_id=' + anneeId + '&periode=semestre1';
            btnS2.href = baseUrl + '?classe_id=' + classeId + '&annee_universitaire_id=' + anneeId + '&periode=semestre2';
            btnS1.target = '';
            btnS2.target = '';
        } else if (action === 'pdf-preview') {
            // Route: esbtp.bulletins.pdf-params-preview → /esbtp-special/bulletins-pdf/preview?bulletin={etudiant_id}&...
            var baseUrl = '{{ url("/esbtp-special/bulletins-pdf/preview") }}'
                + '?bulletin=' + etudiantId
                + '&classe_id=' + classeId
                + '&annee_universitaire_id=' + anneeId;
            btnS1.href = baseUrl + '&periode=semestre1';
            btnS2.href = baseUrl + '&periode=semestre2';
            btnS1.target = '_blank';
            btnS2.target = '_blank';
        } else {
            // Route: esbtp.bulletins.pdf-params → /esbtp-special/bulletins-pdf?bulletin={etudiant_id}&...
            var baseUrl = '{{ url("/esbtp-special/bulletins-pdf") }}'
                + '?bulletin=' + etudiantId
                + '&classe_id=' + classeId
                + '&annee_universitaire_id=' + anneeId;
            btnS1.href = baseUrl + '&periode=semestre1';
            btnS2.href = baseUrl + '&periode=semestre2';
            btnS1.target = '_blank';
            btnS2.target = '_blank';
        }
    });
})();
</script>
