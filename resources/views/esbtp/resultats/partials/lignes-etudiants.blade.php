{{-- Template pour les pages suivantes - seulement les lignes TR à ajouter au tableau --}}
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
                $coeffMissingHere = !empty($coefficientsMissingMap[$etudiant->id] ?? false);
            @endphp
            <span class="badge bg-{{ $badgeClass }} fs-6"@if($coeffMissingHere) title="Moyenne arithmétique (coefficients à configurer)" @endif>
                {{ number_format($moyenne, 2) }}/20
                @if($coeffMissingHere)<i class="fas fa-triangle-exclamation ms-1" style="font-size:.65em;"></i>@endif
            </span>
            @if(($annualValueStatuses[$etudiant->id]['state'] ?? null) === 'annual_incomplete')
                <span class="badge bg-warning text-dark ms-1">
                    {{ $annualValueStatuses[$etudiant->id]['label'] ?? 'Provisoire' }}
                </span>
            @endif
            @if($coeffMissingHere)
                <div class="small text-warning mt-1" style="font-size:.7rem;line-height:1.1;">
                    <i class="fas fa-info-circle"></i> Coefficients à configurer
                </div>
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
            @php
                $_bulPeriode = $detail_periode ?? 'semestre1';
                $_bulParams = array_filter([
                    'bulletin' => $etudiant->id,
                    'classe_id' => $actualClasseId,
                    'periode' => $_bulPeriode,
                    'annee_universitaire_id' => $annee_id,
                ]);
            @endphp
            <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_bulParams) }}"
               target="_blank"
               class="btn btn-sm btn-secondary"
               title="Voir bulletin ({{ $_bulPeriode === 'annuel' ? 'Annuel' : ($_bulPeriode === 'semestre1' ? 'Semestre 1' : 'Semestre 2') }})">
                <i class="fas fa-file-alt"></i>
            </a>
            <a href="{{ route('esbtp.bulletins.pdf-params', $_bulParams) }}"
               class="btn btn-sm btn-danger"
               title="Télécharger PDF ({{ $_bulPeriode === 'annuel' ? 'Annuel' : ($_bulPeriode === 'semestre1' ? 'Semestre 1' : 'Semestre 2') }})">
                <i class="fas fa-file-pdf"></i>
            </a>
        </div>
    </td>
</tr>
@endforeach
