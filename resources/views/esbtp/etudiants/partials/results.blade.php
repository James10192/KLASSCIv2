@php
    $currentYear = $anneeCourante ?? (\App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first());
    $currentYearId = $currentYear->id ?? null;
@endphp

{{-- Compteur inline (affiché dans le header de section via JS) --}}
<div id="student-count-inline" style="display:none;" data-total="{{ $etudiants->total() }}" data-page="{{ $etudiants->count() }}" data-has-pages="{{ $etudiants->total() > $etudiants->perPage() ? '1' : '0' }}"></div>

<!-- Vue Desktop : Tableau (visible > 992px) -->
<div class="table-responsive desktop-view">
    <table class="table table-hover align-middle mb-0" id="etudiants-table">
        <thead class="bg-primary text-white">
            <tr>
                <th>
                    <button type="button" class="btn btn-link text-white text-decoration-none p-0 table-sort" data-column="matricule">
                        Matricule <i class="fas fa-sort ms-1"></i>
                    </button>
                </th>
                <th>Photo</th>
                <th>
                    <button type="button" class="btn btn-link text-white text-decoration-none p-0 table-sort" data-column="nom">
                        Nom complet <i class="fas fa-sort ms-1"></i>
                    </button>
                </th>
                <th>Genre</th>
                <th>Contact</th>
                <th>Résidence</th>
                <th>
                    <button type="button" class="btn btn-link text-white text-decoration-none p-0 table-sort" data-column="classe">
                        Classe actuelle <i class="fas fa-sort ms-1"></i>
                    </button>
                </th>
                <th>
                    <button type="button" class="btn btn-link text-white text-decoration-none p-0 table-sort" data-column="date">
                        Date inscription <i class="fas fa-sort ms-1"></i>
                    </button>
                </th>
                <th>Statut d'affectation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($etudiants as $etudiant)
                @php
                    $pendingInscription = $etudiant->pending_inscriptions->first();
                    $latestInscription = $etudiant->inscriptions->sortByDesc(function ($inscription) {
                        return $inscription->date_inscription ?? $inscription->created_at;
                    })->first();
                    $latestDate = optional($latestInscription?->date_inscription)->format('d/m/Y') ?? '—';
                    $latestDateSort = optional($latestInscription?->date_inscription)->format('Y-m-d') ?? '';

                    $inscriptionsPayload = $etudiant->inscriptions
                        ->sortByDesc(function ($inscription) {
                            return $inscription->date_inscription ?? $inscription->created_at;
                        })
                        ->map(function ($inscription) use ($currentYearId) {
                            $anneeLabel = $inscription->anneeUniversitaire->name
                                ?? $inscription->anneeUniversitaire->libelle
                                ?? 'Année non renseignée';

                            return [
                                'id' => $inscription->id,
                                'annee' => $anneeLabel,
                                'classe' => $inscription->classe->name ?? 'Non assignée',
                                'filiere' => $inscription->filiere->name ?? null,
                                'niveau' => $inscription->niveau->name ?? null,
                                'status' => $inscription->status,
                                'affectation_status' => $inscription->affectation_status,
                                'type' => $inscription->type_inscription,
                                'is_current_year' => $currentYearId && $inscription->annee_universitaire_id == $currentYearId,
                                'date_label' => optional($inscription->date_inscription)->format('d/m/Y'),
                                'date_value' => optional($inscription->date_inscription)->format('Y-m-d'),
                                'workflow_step' => $inscription->workflow_step,
                                'paiement_validation_id' => $inscription->paiement_validation_id,
                                'edit_url' => route('esbtp.inscriptions.edit', ['inscription' => $inscription->id, 'embedded' => 1]),
                                'validate_url' => route('esbtp.inscriptions.valider-definitivement', ['inscription' => $inscription->id]),
                            ];
                        })
                        ->values();

                    $studentDataset = [
                        'id' => $etudiant->id,
                        'name' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
                        'matricule' => $etudiant->matricule,
                        'edit_url' => route('esbtp.etudiants.edit', ['etudiant' => $etudiant->id, 'embedded' => 1]),
                        'inscriptions' => $inscriptionsPayload,
                    ];

                    $inscriptionCouranteClasse = $currentYearId ? $etudiant->inscriptions->firstWhere('annee_universitaire_id', $currentYearId) : null;
                    $inscriptionCourante = $currentYearId ? $etudiant->inscriptions
                        ->where('annee_universitaire_id', $currentYearId)
                        ->where('workflow_step', 'etudiant_cree')
                        ->first() : null;
                @endphp
                <tr @if($pendingInscription) class="table-warning" @endif
                    data-sort-matricule="{{ strtoupper($etudiant->matricule) }}"
                    data-sort-nom="{{ strtoupper(trim($etudiant->nom . ' ' . $etudiant->prenoms)) }}"
                    data-sort-classe="{{ strtoupper(optional($latestInscription?->classe)->name ?? '') }}"
                    data-sort-date="{{ $latestDateSort }}">
                    <td>{{ $etudiant->matricule }}</td>
                    <td class="text-center">
                        @if($etudiant->photo_url)
                            <img src="{{ $etudiant->photo_url }}" alt="Photo" class="img-thumbnail rounded-circle shadow" style="width: 50px; height: 50px; object-fit: cover;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center rounded-circle shadow" style="width: 50px; height: 50px;">
                                <i class="fas fa-user text-secondary"></i>
                            </div>
                        @endif
                    </td>
                    <td>
                        {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                        @if($pendingInscription)
                            <span class="badge bg-warning text-dark ms-2">Inscription en attente</span>
                        @endif
                    </td>
                    <td>{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                    <td>
                        {{ $etudiant->telephone }}<br>
                        <small>{{ $etudiant->email }}</small>
                    </td>
                    <td>
                        @if($etudiant->ville || $etudiant->commune)
                            {{ $etudiant->ville }} {{ $etudiant->commune ? ', '.$etudiant->commune : '' }}
                        @else
                            <span class="text-muted">Non renseignée</span>
                        @endif
                    </td>
                    <td>
                        @if($inscriptionCouranteClasse)
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    {{ $inscriptionCouranteClasse->classe ? $inscriptionCouranteClasse->classe->name : 'Non assigné' }}
                                    <br>
                                    <small>
                                        {{ $inscriptionCouranteClasse->filiere ? $inscriptionCouranteClasse->filiere->name : '' }}
                                        {{ $inscriptionCouranteClasse->niveau ? ' - '.$inscriptionCouranteClasse->niveau->name : '' }}
                                    </small>
                                </div>
                                @if($inscriptionCouranteClasse->workflow_step == 'etudiant_cree')
                                    <div class="ms-2" title="Inscription validée - Workflow terminé">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                @else
                                    <div class="ms-2" title="Inscription en cours - Workflow : {{ $inscriptionCouranteClasse->workflow_step }}">
                                        <i class="fas fa-hourglass-half text-warning"></i>
                                    </div>
                                @endif
                            </div>
                        @elseif($etudiant->inscriptions->count() > 0)
                            <?php $derniere = $etudiant->inscriptions->sortByDesc('created_at')->first(); ?>
                            <div>
                                {{ $derniere->classe ? $derniere->classe->name : 'Non assigné' }}
                                <br>
                                <small class="text-muted">
                                    {{ $derniere->filiere ? $derniere->filiere->name : '' }}
                                    {{ $derniere->niveau ? ' - '.$derniere->niveau->name : '' }}
                                    ({{ $derniere->anneeUniversitaire ? $derniere->anneeUniversitaire->name : '' }})
                                </small>
                            </div>
                        @else
                            <span class="text-muted">Non inscrit</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $latestDate }}</span>
                    </td>
                    <td>
                        @if($inscriptionCourante)
                            @if($inscriptionCourante->affectation_status == 'affecté')
                                <span class="badge bg-success px-3 py-2">Affecté</span>
                            @elseif($inscriptionCourante->affectation_status == 'réaffecté')
                                <span class="badge bg-info px-3 py-2">Réaffecté</span>
                            @elseif($inscriptionCourante->affectation_status == 'non_affecté')
                                <span class="badge bg-danger px-3 py-2">Non affecté</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        @elseif($etudiant->inscriptions->count() > 0)
                            <?php $derniere = $etudiant->inscriptions->sortByDesc('created_at')->first(); ?>
                            @if($derniere->affectation_status)
                                @if($derniere->affectation_status == 'affecté')
                                    <span class="badge bg-success px-2 py-1">Affecté</span>
                                @elseif($derniere->affectation_status == 'réaffecté')
                                    <span class="badge bg-info px-2 py-1">Réaffecté</span>
                                @elseif($derniere->affectation_status == 'non_affecté')
                                    <span class="badge bg-danger px-2 py-1">Non affecté</span>
                                @endif
                            @else
                                <span class="text-muted small">Pas d'affectation ({{ $currentYear->name ?? 'N/A' }})</span>
                            @endif
                        @else
                            <span class="text-muted small">Pas d'inscription ({{ $currentYear->name ?? 'N/A' }})</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Voir les détails">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button"
                                class="btn btn-warning btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 btn-open-edit-modal"
                                title="Modifier"
                                data-student='@json($studentDataset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            @if($pendingInscription)
                                @can('inscriptions.validate')
                                <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#validationModal{{ $pendingInscription->id }}" title="Valider l'inscription">
                                    <i class="fas fa-check"></i>
                                </button>
                                @includeIf('esbtp.etudiants._validation_modal', ['pendingInscription' => $pendingInscription, 'etudiant' => $etudiant])
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">Aucun étudiant trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Vue Mobile : Cards Grid (visible ≤ 992px) -->
<div class="mobile-view">
    <div class="students-grid">
        @forelse ($etudiants as $etudiant)
            @php
                $pendingInscription = $etudiant->pending_inscriptions->first();
                $latestInscription = $etudiant->inscriptions->sortByDesc(function ($inscription) {
                    return $inscription->date_inscription ?? $inscription->created_at;
                })->first();
                $latestDate = optional($latestInscription?->date_inscription)->format('d/m/Y') ?? '—';

                $inscriptionsPayload = $etudiant->inscriptions
                    ->sortByDesc(function ($inscription) {
                        return $inscription->date_inscription ?? $inscription->created_at;
                    })
                    ->map(function ($inscription) use ($currentYearId) {
                        $anneeLabel = $inscription->anneeUniversitaire->name
                            ?? $inscription->anneeUniversitaire->libelle
                            ?? 'Année non renseignée';

                        return [
                            'id' => $inscription->id,
                            'annee' => $anneeLabel,
                            'classe' => $inscription->classe->name ?? 'Non assignée',
                            'filiere' => $inscription->filiere->name ?? null,
                            'niveau' => $inscription->niveau->name ?? null,
                            'status' => $inscription->status,
                            'affectation_status' => $inscription->affectation_status,
                            'type' => $inscription->type_inscription,
                            'is_current_year' => $currentYearId && $inscription->annee_universitaire_id == $currentYearId,
                            'date_label' => optional($inscription->date_inscription)->format('d/m/Y'),
                            'date_value' => optional($inscription->date_inscription)->format('Y-m-d'),
                            'workflow_step' => $inscription->workflow_step,
                            'paiement_validation_id' => $inscription->paiement_validation_id,
                            'edit_url' => route('esbtp.inscriptions.edit', ['inscription' => $inscription->id, 'embedded' => 1]),
                            'validate_url' => route('esbtp.inscriptions.valider-definitivement', ['inscription' => $inscription->id]),
                        ];
                    })
                    ->values();

                $studentDataset = [
                    'id' => $etudiant->id,
                    'name' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
                    'matricule' => $etudiant->matricule,
                    'edit_url' => route('esbtp.etudiants.edit', ['etudiant' => $etudiant->id, 'embedded' => 1]),
                    'inscriptions' => $inscriptionsPayload,
                ];

                $inscriptionCouranteClasse = $currentYearId ? $etudiant->inscriptions->firstWhere('annee_universitaire_id', $currentYearId) : null;
                $inscriptionCourante = $currentYearId ? $etudiant->inscriptions
                    ->where('annee_universitaire_id', $currentYearId)
                    ->where('workflow_step', 'etudiant_cree')
                    ->first() : null;
            @endphp

            <div class="student-card {{ $pendingInscription ? 'pending-inscription' : '' }}">
                <!-- Header de la card avec photo et nom -->
                <div class="student-card-header">
                    <div class="student-photo">
                        @if($etudiant->photo_url)
                            <img src="{{ $etudiant->photo_url }}" alt="Photo" class="rounded-circle">
                        @else
                            <div class="photo-placeholder rounded-circle">
                                <i class="fas fa-user"></i>
                            </div>
                        @endif
                    </div>
                    <div class="student-info-header">
                        <h3 class="student-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h3>
                        <p class="student-matricule">{{ $etudiant->matricule }}</p>
                        @if($pendingInscription)
                            <span class="badge bg-warning text-dark">Inscription en attente</span>
                        @endif
                    </div>
                    <div class="student-status">
                        @if($etudiant->statut == 'actif')
                            <span class="badge bg-success">Actif</span>
                        @else
                            <span class="badge bg-danger">Inactif</span>
                        @endif
                    </div>
                </div>

                <!-- Corps de la card avec infos -->
                <div class="student-card-body">
                    <!-- Contact -->
                    <div class="info-row">
                        <i class="fas fa-phone text-primary"></i>
                        <div class="info-content">
                            <span class="info-label">Contact</span>
                            <span class="info-value">{{ $etudiant->telephone }}</span>
                        </div>
                    </div>

                    @if($etudiant->email)
                    <div class="info-row">
                        <i class="fas fa-envelope text-primary"></i>
                        <div class="info-content">
                            <span class="info-label">Email</span>
                            <span class="info-value">{{ $etudiant->email }}</span>
                        </div>
                    </div>
                    @endif

                    <!-- Classe actuelle -->
                    <div class="info-row">
                        <i class="fas fa-graduation-cap text-primary"></i>
                        <div class="info-content">
                            <span class="info-label">Classe actuelle</span>
                            @if($inscriptionCouranteClasse)
                                <span class="info-value">
                                    {{ $inscriptionCouranteClasse->classe ? $inscriptionCouranteClasse->classe->name : 'Non assigné' }}
                                    @if($inscriptionCouranteClasse->workflow_step == 'etudiant_cree')
                                        <i class="fas fa-check-circle text-success ms-1"></i>
                                    @else
                                        <i class="fas fa-hourglass-half text-warning ms-1"></i>
                                    @endif
                                </span>
                                @if($inscriptionCouranteClasse->filiere || $inscriptionCouranteClasse->niveau)
                                <small class="text-muted d-block">
                                    {{ $inscriptionCouranteClasse->filiere ? $inscriptionCouranteClasse->filiere->name : '' }}
                                    {{ $inscriptionCouranteClasse->niveau ? ' - '.$inscriptionCouranteClasse->niveau->name : '' }}
                                </small>
                                @endif
                            @elseif($etudiant->inscriptions->count() > 0)
                                <?php $derniere = $etudiant->inscriptions->sortByDesc('created_at')->first(); ?>
                                <span class="info-value">
                                    {{ $derniere->classe ? $derniere->classe->name : 'Non assigné' }}
                                </span>
                                <small class="text-muted d-block">
                                    {{ $derniere->filiere ? $derniere->filiere->name : '' }}
                                    {{ $derniere->niveau ? ' - '.$derniere->niveau->name : '' }}
                                    ({{ $derniere->anneeUniversitaire ? $derniere->anneeUniversitaire->name : '' }})
                                </small>
                            @else
                                <span class="info-value text-muted">Non inscrit</span>
                            @endif
                        </div>
                    </div>

                    <!-- Statut d'affectation -->
                    @if($inscriptionCourante)
                        <div class="info-row">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            <div class="info-content">
                                <span class="info-label">Affectation ({{ $currentYear->name ?? 'N/A' }})</span>
                                @if($inscriptionCourante->affectation_status == 'affecté')
                                    <span class="badge bg-success">Affecté</span>
                                @elseif($inscriptionCourante->affectation_status == 'réaffecté')
                                    <span class="badge bg-info">Réaffecté</span>
                                @elseif($inscriptionCourante->affectation_status == 'non_affecté')
                                    <span class="badge bg-danger">Non affecté</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Date inscription -->
                    <div class="info-row">
                        <i class="fas fa-calendar text-primary"></i>
                        <div class="info-content">
                            <span class="info-label">Date inscription</span>
                            <span class="info-value">{{ $latestDate }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer avec actions -->
                <div class="student-card-footer">
                    <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                    <button type="button"
                        class="btn btn-sm btn-warning btn-open-edit-modal"
                        data-student='@json($studentDataset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    @if($pendingInscription)
                        @can('inscriptions.validate')
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#validationModal{{ $pendingInscription->id }}">
                            <i class="fas fa-check"></i> Valider
                        </button>
                        @includeIf('esbtp.etudiants._validation_modal', ['pendingInscription' => $pendingInscription, 'etudiant' => $etudiant])
                        @endcan
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted">Aucun étudiant trouvé</p>
            </div>
        @endforelse
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $etudiants->links() }}
</div>
