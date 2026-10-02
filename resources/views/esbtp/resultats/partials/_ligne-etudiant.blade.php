{{-- Une ligne de la liste des résultats. Partagée par liste-etudiants (page 1)
     et lignes-etudiants (pages suivantes, ajoutées par le défilement infini).
     Sous 768 px, la même ligne s'affiche en carte (CSS rsl-*, index.blade.php) :
     une seule source, donc les lignes ajoutées en AJAX ont la même forme. --}}
@php
    $inscription = $etudiant->inscriptions->where('annee_universitaire_id', $annee_id)->first();
    $etudiantClasse = $inscription ? $inscription->classe : null;
    $studentClasseId = $inscription ? $inscription->classe_id : null;
    $actualClasseId = ($classe ? $classe->id : null) ?? $studentClasseId;

    $nomComplet = trim($etudiant->nom . ' ' . $etudiant->prenoms);
    $initiales = mb_strtoupper(mb_substr((string) $etudiant->nom, 0, 1, 'UTF-8') . mb_substr((string) $etudiant->prenoms, 0, 1, 'UTF-8'), 'UTF-8');

    $annualState = $annualValueStatuses[$etudiant->id]['state'] ?? null;
    $aMoyenne = isset($moyennes[$etudiant->id]);
    $moyenne = $aMoyenne ? $moyennes[$etudiant->id] : null;
    $coeffMissingHere = !empty($coefficientsMissingMap[$etudiant->id] ?? false);
    $rang = $rangs[$etudiant->id] ?? null;

    $_bulPeriode = $detail_periode ?? 'semestre1';
    $_bulPeriodeLabel = $_bulPeriode === 'annuel' ? 'Annuel' : ($_bulPeriode === 'semestre1' ? 'Semestre 1' : 'Semestre 2');
    $_bulParams = array_filter([
        'bulletin' => $etudiant->id,
        'classe_id' => $actualClasseId,
        'periode' => $_bulPeriode,
        'annee_universitaire_id' => $annee_id,
    ]);
    $detailUrl = route('esbtp.resultats.etudiant', array_filter([
        'etudiant' => $etudiant->id,
        'classe_id' => $actualClasseId,
        'annee_universitaire_id' => $annee_id,
        'periode' => $detail_periode ?? 'annuel',
        'include_all_statuses' => !empty($include_all_statuses) ? 1 : null,
    ]));
@endphp
<tr class="rsl-row" data-search="{{ mb_strtolower($nomComplet . ' ' . $etudiant->matricule, 'UTF-8') }}">
    <td class="rsl-cell rsl-cell--check">
        <div class="form-check">
            <input class="form-check-input student-checkbox" type="checkbox" value="{{ $etudiant->id }}" aria-label="Sélectionner {{ $nomComplet }}">
        </div>
    </td>
    <td class="rsl-cell rsl-cell--matricule" data-label="Matricule">
        <span class="rsl-matricule">{{ $etudiant->matricule }}</span>
    </td>
    <td class="rsl-cell rsl-cell--nom" data-label="Étudiant">
        <div class="rsl-identity">
            <div class="rsl-avatar user-avatar" aria-hidden="true">{{ $initiales ?: '?' }}</div>
            <div class="rsl-identity-text">
                <div class="fw-semibold rsl-name">{{ $nomComplet }}</div>
                <small class="rsl-email">{{ $etudiant->email ?? 'Pas d\'email' }}</small>
                @if(($studentWorkflowAlerts[$etudiant->id]['show_banner'] ?? false) && !empty($include_all_statuses))
                    <div class="mt-1">
                        <span class="rsl-badge rsl-badge--warning"><i class="fas fa-hourglass-half"></i>Inscription non validée</span>
                    </div>
                @endif
            </div>
        </div>
    </td>
    @if(!isset($classe) || !$classe)
    <td class="rsl-cell rsl-cell--classe" data-label="Classe">
        <span class="rsl-chip">{{ $etudiantClasse ? $etudiantClasse->name : 'N/A' }}</span>
    </td>
    @endif
    <td class="rsl-cell rsl-cell--moyenne" data-label="Moyenne">
        @if($aMoyenne)
            <span class="rsl-moyenne {{ $moyenne < 10 ? 'rsl-moyenne--faible' : '' }}"@if($coeffMissingHere) title="Moyenne arithmétique (coefficients à configurer)" @endif>
                {{ number_format($moyenne, 2) }}<small>/20</small>
                @if($coeffMissingHere)<i class="fas fa-triangle-exclamation rsl-moyenne-alerte"></i>@endif
            </span>
            @if($annualState === 'annual_incomplete')
                <span class="rsl-badge rsl-badge--warning ms-1">
                    {{ $annualValueStatuses[$etudiant->id]['label'] ?? 'Provisoire' }}
                </span>
            @endif
            @if($coeffMissingHere)
                <div class="rsl-hint rsl-hint--warning">
                    <i class="fas fa-info-circle"></i> Coefficients à configurer
                </div>
            @endif
        @elseif($annualState === 'no_data')
            <span class="rsl-badge rsl-badge--muted">
                <i class="fas fa-ban"></i>Aucune note
            </span>
        @else
            <span class="rsl-badge rsl-badge--muted">N/A</span>
        @endif
    </td>
    <td class="rsl-cell rsl-cell--rang" data-label="Rang">
        @if($rang !== null)
            <div class="rsl-rang">
                <i class="fas {{ $rang == 1 ? 'fa-trophy' : ($rang <= 3 ? 'fa-medal' : 'fa-hashtag') }} {{ $rang <= 3 ? 'rsl-rang-icon--podium' : 'rsl-rang-icon' }}"></i>
                <span class="fw-bold">{{ $rang }}<sup>{{ $rang == 1 ? 'er' : 'ème' }}</sup></span>
                <small>/ {{ count($rangs) }}</small>
            </div>
        @else
            <span class="rsl-badge rsl-badge--muted">N/A</span>
        @endif
    </td>
    <td class="rsl-cell rsl-cell--statut" data-label="Statut">
        @if($aMoyenne)
            @if($annualState === 'annual_incomplete')
                <span class="rsl-badge rsl-badge--warning"><i class="fas fa-clock"></i>Partiel</span>
            @elseif($moyenne >= 10)
                <span class="rsl-badge rsl-badge--success"><i class="fas fa-check"></i>Admis</span>
            @else
                <span class="rsl-badge rsl-badge--danger"><i class="fas fa-times"></i>Échec</span>
            @endif
        @else
            <span class="rsl-badge rsl-badge--muted"><i class="fas fa-question"></i>Non évalué</span>
        @endif
    </td>
    <td class="rsl-cell rsl-cell--actions" data-label="Actions">
        <div class="rsl-actions">
            <a href="{{ $detailUrl }}" class="rsl-action-main" title="Voir détails">
                <i class="fas fa-chart-line"></i><span>Détails</span>
            </a>
            <div class="dropdown">
                <button type="button" class="rsl-action-more" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Plus d'actions pour {{ $nomComplet }}">
                    <i class="fas fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end rsl-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('esbtp.bulletins.pdf-params-preview', $_bulParams) }}" target="_blank" title="Voir bulletin ({{ $_bulPeriodeLabel }})">
                            <i class="fas fa-file-alt"></i>Aperçu du bulletin <small>({{ $_bulPeriodeLabel }})</small>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('esbtp.bulletins.pdf-params', $_bulParams) }}" title="Télécharger PDF ({{ $_bulPeriodeLabel }})">
                            <i class="fas fa-file-pdf"></i>Télécharger le PDF <small>({{ $_bulPeriodeLabel }})</small>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </td>
</tr>
