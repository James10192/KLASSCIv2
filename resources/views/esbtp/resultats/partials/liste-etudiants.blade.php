{{-- Template pour la première page avec structure table complète --}}
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th width="40">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-all">
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
                <th width="200">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($etudiants as $etudiant)
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input student-checkbox" type="checkbox" value="{{ $etudiant->id }}">
                        </div>
                    </td>
                    <td>
                        <span class="fw-medium">{{ $etudiant->matricule }}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                                <small class="text-muted">{{ $etudiant->email ?? 'Pas d\'email' }}</small>
                                @if(($studentWorkflowAlerts[$etudiant->id]['show_banner'] ?? false) && !empty($include_all_statuses))
                                    <div class="mt-1">
                                        <span class="badge text-bg-warning">Inscription non validée</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    @if(!isset($classe) || !$classe)
                    <td>
                        @php
                            $inscription = $etudiant->inscriptions->where('annee_universitaire_id', $annee_id)->first();
                            $etudiantClasse = $inscription ? $inscription->classe : null;
                        @endphp
                        <span class="badge bg-light text-dark border">
                            {{ $etudiantClasse ? $etudiantClasse->name : 'N/A' }}
                        </span>
                    </td>
                    @endif
                    <td>
                        @if(isset($moyennes[$etudiant->id]))
                            @php
                                $moyenne = $moyennes[$etudiant->id];
                                $badgeClass = $moyenne >= 16 ? 'success' : ($moyenne >= 14 ? 'info' : ($moyenne >= 12 ? 'warning' : ($moyenne >= 10 ? 'primary' : 'danger')));
                            @endphp
                            <span class="badge bg-{{ $badgeClass }} fs-6">
                                {{ number_format($moyenne, 2) }}/20
                            </span>
                            @if(($annualValueStatuses[$etudiant->id]['state'] ?? null) === 'annual_incomplete')
                                <span class="badge bg-warning text-dark ms-1">
                                    {{ $annualValueStatuses[$etudiant->id]['label'] ?? 'Provisoire' }}
                                </span>
                            @endif
                        @elseif(($annualValueStatuses[$etudiant->id]['state'] ?? null) === 'no_data')
                            <span class="badge bg-secondary">
                                <i class="fas fa-ban me-1"></i>Aucune note
                            </span>
                        @else
                            <span class="badge bg-secondary">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if(isset($rangs[$etudiant->id]))
                            <div class="d-flex align-items-center">
                                @php
                                    $rang = $rangs[$etudiant->id];
                                    $iconClass = $rang == 1 ? 'fa-trophy text-warning' : ($rang <= 3 ? 'fa-medal text-info' : 'fa-hashtag text-muted');
                                @endphp
                                <i class="fas {{ $iconClass }} me-2"></i>
                                <span class="fw-bold">{{ $rang }}<sup>{{ $rang == 1 ? 'er' : 'ème' }}</sup></span>
                                <small class="text-muted ms-1">/ {{ count($rangs) }}</small>
                            </div>
                        @else
                            <span class="badge bg-secondary">N/A</span>
                        @endif
                    </td>
                    <td>
                        @php $annualState = $annualValueStatuses[$etudiant->id]['state'] ?? null; @endphp
                        @if(isset($moyennes[$etudiant->id]))
                            @if($annualState === 'annual_incomplete')
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock me-1"></i>Partiel
                                </span>
                            @elseif($moyennes[$etudiant->id] >= 10)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Admis
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i>Échec
                                </span>
                            @endif
                        @else
                            <span class="badge bg-secondary">
                                <i class="fas fa-question me-1"></i>Non évalué
                            </span>
                        @endif
                    </td>
                    <td>
                        @php
                            $inscription = $etudiant->inscriptions->where('annee_universitaire_id', $annee_id)->first();
                            $studentClasseId = $inscription ? $inscription->classe_id : null;
                            $actualClasseId = ($classe ? $classe->id : null) ?? $studentClasseId;
                        @endphp
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('esbtp.resultats.etudiant', array_filter(['etudiant' => $etudiant->id, 'classe_id' => $actualClasseId, 'annee_universitaire_id' => $annee_id, 'periode' => $detail_periode ?? 'annuel', 'include_all_statuses' => !empty($include_all_statuses) ? 1 : null])) }}" class="btn btn-sm btn-info" title="Voir détails">
                                <i class="fas fa-chart-line"></i>
                            </a>
                            @if(isset($bulletins[$etudiant->id]))
                                <button type="button"
                                        class="btn btn-sm btn-secondary btn-bulletin-periode"
                                        title="Voir bulletin"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalChoixPeriodeBulletin"
                                        data-etudiant-id="{{ $etudiant->id }}"
                                        data-bulletin-id="{{ $bulletins[$etudiant->id] }}"
                                        data-classe-id="{{ $actualClasseId }}"
                                        data-annee-id="{{ $annee_id }}"
                                        data-action="show">
                                    <i class="fas fa-file-alt"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-danger btn-bulletin-periode"
                                        title="Télécharger PDF"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalChoixPeriodeBulletin"
                                        data-etudiant-id="{{ $etudiant->id }}"
                                        data-bulletin-id="{{ $bulletins[$etudiant->id] }}"
                                        data-classe-id="{{ $actualClasseId }}"
                                        data-annee-id="{{ $annee_id }}"
                                        data-action="pdf">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-secondary" disabled title="Bulletin non généré">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

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
