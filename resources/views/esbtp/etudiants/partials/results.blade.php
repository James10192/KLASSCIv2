@php
    $currentYear = $anneeCourante ?? (\App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first());
    $currentYearId = $currentYear->id ?? null;
@endphp

{{-- Compteur inline (affiché dans le header de section via JS) --}}
<div id="student-count-inline" style="display:none;" data-total="{{ $etudiants->total() }}" data-page="{{ $etudiants->count() }}" data-has-pages="{{ $etudiants->total() > $etudiants->perPage() ? '1' : '0' }}"></div>

<style>
    /* Premium table — namespace eu-* (Étudiants index) */
    .eu-table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    .eu-table-wrap { overflow-x: clip; overflow-y: visible; }
    .eu-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .eu-table thead th { background: #f8fafc; color: #475569; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; padding: .85rem 1rem; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
    .eu-table thead .eu-sort-btn { background: none; border: none; color: inherit; font: inherit; padding: 0; cursor: pointer; display: inline-flex; align-items: center; gap: .25rem; }
    .eu-table thead .eu-sort-btn:hover { color: #0453cb; }
    .eu-table tbody tr { transition: background-color .15s ease, transform .15s ease; cursor: pointer; }
    .eu-table tbody tr:hover { background-color: #f0f4fa; }
    .eu-table tbody tr.eu-row-pending { background-color: rgba(245,158,11,.06); }
    .eu-table tbody tr.eu-row-pending:hover { background-color: rgba(245,158,11,.12); }
    .eu-table tbody td { padding: .85rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: .82rem; color: #1e293b; }
    .eu-table tbody tr:last-child td { border-bottom: none; }
    .eu-photo, .eu-photo-placeholder { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; box-shadow: 0 1px 3px rgba(15,23,42,.08); display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #94a3b8; flex-shrink: 0; }
    .eu-name { font-weight: 600; color: #1e293b; }
    .eu-matricule { font-family: 'Courier New', monospace; font-size: .76rem; color: #0453cb; background: rgba(4,83,203,.08); padding: .15rem .45rem; border-radius: 5px; font-weight: 700; letter-spacing: .3px; white-space: nowrap; }
    .eu-classe-main { font-weight: 600; }
    .eu-classe-sub { font-size: .72rem; color: #64748b; margin-top: .15rem; }
    .eu-lmd-badge { font-size: .58rem; font-weight: 700; color: #0453cb; background: rgba(4,83,203,.1); border: 1px solid rgba(4,83,203,.25); padding: .1rem .35rem; border-radius: 4px; letter-spacing: .4px; vertical-align: middle; margin-left: .25rem; }
    .eu-status-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .65rem; border-radius: 999px; font-size: .68rem; font-weight: 700; }
    .eu-status-badge--success { background: rgba(16,185,129,.12); color: #047857; }
    .eu-status-badge--info { background: rgba(4,83,203,.1); color: #0453cb; }
    .eu-status-badge--danger { background: rgba(220,38,38,.12); color: #b91c1c; }
    .eu-status-badge--warning { background: rgba(245,158,11,.14); color: #92400e; }
    .eu-actions { display: flex; gap: .3rem; justify-content: flex-end; }
    .eu-action-btn { width: 30px; height: 30px; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; color: #475569; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s ease; font-size: .78rem; text-decoration: none; }
    .eu-action-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(15,23,42,.1); }
    .eu-action-btn--view { color: #0453cb; }
    .eu-action-btn--view:hover { background: #0453cb; color: #fff; border-color: #0453cb; }
    .eu-action-btn--edit { color: #d97706; }
    .eu-action-btn--edit:hover { background: #d97706; color: #fff; border-color: #d97706; }
    .eu-action-btn--validate { color: #10b981; }
    .eu-action-btn--validate:hover { background: #10b981; color: #fff; border-color: #10b981; }
    .eu-empty { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
    .eu-empty i { font-size: 2rem; margin-bottom: .75rem; display: block; color: #cbd5e1; }
    /* Indication visuelle que la row est cliquable */
    .eu-table tbody tr:hover .eu-name { color: #0453cb; }
</style>

<!-- Vue Desktop : Tableau premium (visible > 992px) -->
<div class="eu-table-card desktop-view">
    <div class="eu-table-wrap">
    <table class="eu-table" id="etudiants-table">
        <thead>
            <tr>
                <th style="width:42px;"></th>
                <th><button type="button" class="eu-sort-btn table-sort" data-column="matricule">Matricule <i class="fas fa-sort"></i></button></th>
                <th><button type="button" class="eu-sort-btn table-sort" data-column="nom">Nom complet <i class="fas fa-sort"></i></button></th>
                <th>Contact</th>
                <th><button type="button" class="eu-sort-btn table-sort" data-column="classe">Classe · Filière <i class="fas fa-sort"></i></button></th>
                <th><button type="button" class="eu-sort-btn table-sort" data-column="date">Inscription <i class="fas fa-sort"></i></button></th>
                <th>Affectation</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody id="etudiants-tbody"
               data-has-more="{{ $etudiants->hasMorePages() ? '1' : '0' }}"
               data-next-page="{{ $etudiants->currentPage() + 1 }}"
               data-current-page="{{ $etudiants->currentPage() }}">
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
                <tr class="eu-row {{ $pendingInscription ? 'eu-row-pending' : '' }}"
                    data-etudiant-id="{{ $etudiant->id }}"
                    data-show-url="{{ route('esbtp.etudiants.show', $etudiant) }}"
                    data-sort-matricule="{{ strtoupper($etudiant->matricule) }}"
                    data-sort-nom="{{ strtoupper(trim($etudiant->nom . ' ' . $etudiant->prenoms)) }}"
                    data-sort-classe="{{ strtoupper(optional($latestInscription?->classe)->name ?? '') }}"
                    data-sort-date="{{ $latestDateSort }}">
                    <td>
                        @if($etudiant->photo_url)
                            <img src="{{ $etudiant->photo_url }}" alt="" class="eu-photo">
                        @else
                            <div class="eu-photo-placeholder"><i class="fas fa-user"></i></div>
                        @endif
                    </td>
                    <td><span class="eu-matricule">{{ $etudiant->matricule }}</span></td>
                    <td>
                        <span class="eu-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span>
                        @can('students.accessibility.view')
                            @if($etudiant->accessibilityProfile)
                                <i class="fas fa-universal-access ms-1" style="color:#0453cb;cursor:help;font-size:.78rem;"
                                   title="{{ $etudiant->accessibilityProfile->summaryBadge() }}{{ $etudiant->accessibilityProfile->short_description ? ' — ' . $etudiant->accessibilityProfile->short_description : '' }}"></i>
                            @endif
                        @endcan
                        @if($pendingInscription)
                            <span class="eu-status-badge eu-status-badge--warning" style="margin-left:.4rem;"><i class="fas fa-hourglass-half"></i>En attente</span>
                        @endif
                        @if(!empty($etudiant->bts_journey_ui))
                            <div style="margin-top:.3rem;">@include('esbtp.partials.bts-journey-badge', ['btsJourney' => $etudiant->bts_journey_ui])</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:.78rem;color:#1e293b;">{{ $etudiant->telephone ?: '—' }}</div>
                        @if($etudiant->email)<div style="font-size:.7rem;color:#64748b;margin-top:.1rem;">{{ $etudiant->email }}</div>@endif
                    </td>
                    <td>
                        @php
                            // Helper LMD-aware : produit l'affichage classe/contexte académique
                            // selon que l'inscription est BTS ou LMD (avec/sans parcours).
                            // Renvoie un tuple [titrePrincipal, sousTitre, isLmd].
                            $renderInscBlock = function ($insc) {
                                $isLmd = ($insc->classe?->systeme_academique ?? '') === 'LMD';
                                $parcours = $isLmd ? $insc->classe?->parcours : null;
                                $mention = $parcours?->mention;
                                $classeName = $insc->classe?->name ?? 'Non assigné';
                                $niveauName = $insc->niveau?->name ?? '';
                                if ($parcours && $mention) {
                                    return [
                                        'main' => $classeName,
                                        'sub' => $mention->name . ' · ' . $parcours->name . ($niveauName ? ' · ' . $niveauName : ''),
                                        'isLmd' => true,
                                    ];
                                }
                                if ($isLmd) {
                                    $mentionTronc = $insc->classe?->filiere?->name;
                                    return [
                                        'main' => $classeName,
                                        'sub' => ($mentionTronc ?: 'Mention LMD') . ' · Tronc commun' . ($niveauName ? ' · ' . $niveauName : ''),
                                        'isLmd' => true,
                                    ];
                                }
                                $filiereName = $insc->filiere?->name ?? '';
                                return [
                                    'main' => $classeName,
                                    'sub' => trim($filiereName . ($niveauName ? ' - ' . $niveauName : ''), ' -'),
                                    'isLmd' => false,
                                ];
                            };
                        @endphp
                        @if($inscriptionCouranteClasse)
                            @php $insBlock = $renderInscBlock($inscriptionCouranteClasse); @endphp
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <span style="display:inline-flex;align-items:center;gap:.4rem;">
                                        <span>{{ $insBlock['main'] }}</span>
                                        @if($insBlock['isLmd'])
                                            <span style="font-size:.62rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.1);border:1px solid rgba(4,83,203,.25);padding:.1rem .35rem;border-radius:4px;letter-spacing:.4px;">LMD</span>
                                        @endif
                                    </span>
                                    <br>
                                    <small>{{ $insBlock['sub'] }}</small>
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
                            @php $insBlock = $renderInscBlock($derniere); @endphp
                            <div>
                                <span style="display:inline-flex;align-items:center;gap:.4rem;">
                                    <span>{{ $insBlock['main'] }}</span>
                                    @if($insBlock['isLmd'])
                                        <span style="font-size:.62rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.1);border:1px solid rgba(4,83,203,.25);padding:.1rem .35rem;border-radius:4px;letter-spacing:.4px;">LMD</span>
                                    @endif
                                </span>
                                <br>
                                <small class="text-muted">
                                    {{ $insBlock['sub'] }}
                                    ({{ $derniere->anneeUniversitaire ? $derniere->anneeUniversitaire->name : '' }})
                                </small>
                            </div>
                        @else
                            <span class="text-muted">Non inscrit</span>
                        @endif
                    </td>
                    <td><span style="font-size:.74rem;color:#475569;">{{ $latestDate }}</span></td>
                    <td>
                        @php
                            $affectStatus = $inscriptionCourante?->affectation_status
                                ?? ($etudiant->inscriptions->isNotEmpty() ? $etudiant->inscriptions->sortByDesc('created_at')->first()->affectation_status : null);
                            $affectMap = [
                                'affecté'     => ['eu-status-badge--success', 'Affecté'],
                                'réaffecté'   => ['eu-status-badge--info', 'Réaffecté'],
                                'non_affecté' => ['eu-status-badge--danger', 'Non affecté'],
                            ];
                            $affectInfo = $affectMap[$affectStatus] ?? null;
                        @endphp
                        @if($affectInfo)
                            <span class="eu-status-badge {{ $affectInfo[0] }}">{{ $affectInfo[1] }}</span>
                        @else
                            <span style="font-size:.7rem;color:#94a3b8;">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="eu-actions" data-row-actions>
                            <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="eu-action-btn eu-action-btn--view" title="Voir les détails" data-stop-propagation>
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('students.edit')
                            <button type="button" class="eu-action-btn eu-action-btn--edit btn-open-edit-modal" title="Modifier" data-stop-propagation
                                data-student='@json($studentDataset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcan
                            @if($pendingInscription)
                                @can('inscriptions.validate')
                                <button type="button" class="eu-action-btn eu-action-btn--validate" data-bs-toggle="modal" data-bs-target="#validationModal{{ $pendingInscription->id }}" title="Valider l'inscription" data-stop-propagation>
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
                    <td colspan="8">
                        <div class="eu-empty">
                            <i class="fas fa-user-slash"></i>
                            <p style="margin:0;font-weight:600;">Aucun étudiant trouvé</p>
                            <p style="margin:.3rem 0 0;font-size:.78rem;">Essayez d'élargir les filtres ou créez une nouvelle inscription.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>{{-- /.eu-table-wrap --}}

    {{-- Sentinel infinite scroll : IntersectionObserver détecte sa visibilité et trigger loadMore() --}}
    @if($etudiants->total() > 0)
        <div id="etudiants-sentinel"
             style="padding: 1.1rem 1rem; text-align: center; border-top: 1px dashed #e2e8f0;"
             data-current-page="{{ $etudiants->currentPage() }}"
             data-last-page="{{ $etudiants->lastPage() }}"
             data-total="{{ $etudiants->total() }}">
            <div class="eu-sentinel-spinner" style="display:none; align-items:center; justify-content:center; gap:.6rem; color:#0453cb; font-size:.85rem; font-weight:600;">
                <i class="fas fa-spinner fa-spin"></i><span>Chargement...</span>
            </div>
            @if(! $etudiants->hasMorePages() && $etudiants->total() > 0)
                <div style="display:flex; align-items:center; justify-content:center; gap:.5rem; color:#64748b; font-size:.78rem; font-style:italic;">
                    <i class="fas fa-check-circle" style="color:#10b981;"></i>
                    <span>Toutes les {{ $etudiants->total() }} fiches affichées.</span>
                </div>
            @endif
        </div>
    @endif
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
                        <h3 class="student-name">
                            {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                            @can('students.accessibility.view')
                                @if($etudiant->accessibilityProfile)
                                    <i class="fas fa-universal-access ms-1" style="color:#0453cb;font-size:.85em;"
                                       title="{{ $etudiant->accessibilityProfile->summaryBadge() }}{{ $etudiant->accessibilityProfile->short_description ? ' — ' . $etudiant->accessibilityProfile->short_description : '' }}"></i>
                                @endif
                            @endcan
                        </h3>
                        <p class="student-matricule">{{ $etudiant->matricule }}</p>
                        @if($pendingInscription)
                            <span class="badge bg-warning text-dark">Inscription en attente</span>
                        @endif
                        @if(!empty($etudiant->bts_journey_ui))
                            <div class="mt-2">
                                @include('esbtp.partials.bts-journey-badge', ['btsJourney' => $etudiant->bts_journey_ui])
                            </div>
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
                            @php
                                $renderInscBlockMobile = function ($insc) {
                                    $isLmd = ($insc->classe?->systeme_academique ?? '') === 'LMD';
                                    $parcours = $isLmd ? $insc->classe?->parcours : null;
                                    $mention = $parcours?->mention;
                                    $classeName = $insc->classe?->name ?? 'Non assigné';
                                    $niveauName = $insc->niveau?->name ?? '';
                                    if ($parcours && $mention) {
                                        return ['main' => $classeName, 'sub' => $mention->name . ' · ' . $parcours->name . ($niveauName ? ' · ' . $niveauName : ''), 'isLmd' => true];
                                    }
                                    if ($isLmd) {
                                        $mentionTronc = $insc->classe?->filiere?->name;
                                        return ['main' => $classeName, 'sub' => ($mentionTronc ?: 'Mention LMD') . ' · Tronc commun' . ($niveauName ? ' · ' . $niveauName : ''), 'isLmd' => true];
                                    }
                                    $filiereName = $insc->filiere?->name ?? '';
                                    return ['main' => $classeName, 'sub' => trim($filiereName . ($niveauName ? ' - ' . $niveauName : ''), ' -'), 'isLmd' => false];
                                };
                            @endphp
                            @if($inscriptionCouranteClasse)
                                @php $insBlock = $renderInscBlockMobile($inscriptionCouranteClasse); @endphp
                                <span class="info-value" style="display:inline-flex;align-items:center;gap:.35rem;">
                                    <span>{{ $insBlock['main'] }}</span>
                                    @if($insBlock['isLmd'])
                                        <span style="font-size:.6rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.1);border:1px solid rgba(4,83,203,.25);padding:.05rem .3rem;border-radius:4px;">LMD</span>
                                    @endif
                                    @if($inscriptionCouranteClasse->workflow_step == 'etudiant_cree')
                                        <i class="fas fa-check-circle text-success ms-1"></i>
                                    @else
                                        <i class="fas fa-hourglass-half text-warning ms-1"></i>
                                    @endif
                                </span>
                                <small class="text-muted d-block">{{ $insBlock['sub'] }}</small>
                            @elseif($etudiant->inscriptions->count() > 0)
                                <?php $derniere = $etudiant->inscriptions->sortByDesc('created_at')->first(); ?>
                                @php $insBlock = $renderInscBlockMobile($derniere); @endphp
                                <span class="info-value" style="display:inline-flex;align-items:center;gap:.35rem;">
                                    <span>{{ $insBlock['main'] }}</span>
                                    @if($insBlock['isLmd'])
                                        <span style="font-size:.6rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.1);border:1px solid rgba(4,83,203,.25);padding:.05rem .3rem;border-radius:4px;">LMD</span>
                                    @endif
                                </span>
                                <small class="text-muted d-block">
                                    {{ $insBlock['sub'] }}
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
                    @can('students.edit')
                    <button type="button"
                        class="btn btn-sm btn-warning btn-open-edit-modal"
                        data-student='@json($studentDataset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    @endcan
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

{{-- Pagination Laravel remplacée par infinite scroll (sentinel ci-dessus, JS dans index) --}}
