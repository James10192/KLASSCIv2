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
        @if(isset($moyennes[$etudiant->id]))
            @if($moyennes[$etudiant->id] >= 10)
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
            <a href="{{ route('esbtp.resultats.etudiant', ['etudiant' => $etudiant->id, 'classe_id' => $actualClasseId, 'annee_universitaire_id' => $annee_id, 'periode' => request('semestre')]) }}" class="btn btn-sm btn-info" title="Voir détails">
                <i class="fas fa-chart-line"></i>
            </a>
            @if(isset($bulletins[$etudiant->id]))
                <a href="{{ route('esbtp.bulletins.show', $bulletins[$etudiant->id]) }}" class="btn btn-sm btn-secondary" title="Voir bulletin">
                    <i class="fas fa-file-alt"></i>
                </a>
                <a href="{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $actualClasseId, 'periode' => request('semestre'), 'annee_universitaire_id' => $annee_id]) }}" class="btn btn-sm btn-danger" target="_blank" title="Télécharger PDF">
                    <i class="fas fa-file-pdf"></i>
                </a>
            @else
                <button class="btn btn-sm btn-outline-secondary" disabled title="Bulletin non généré">
                    <i class="fas fa-exclamation-triangle"></i>
                </button>
            @endif
        </div>
    </td>
</tr>
@endforeach