<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-primary text-white">
            <tr>
                <th>Matricule</th>
                <th>Photo</th>
                <th>Nom complet</th>
                <th>Genre</th>
                <th>Contact</th>
                <th>Résidence</th>
                <th>Classe actuelle</th>
                <th>Statut d'affectation</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($etudiants as $etudiant)
                @php $pendingInscription = $etudiant->pending_inscriptions->first(); @endphp
                <tr @if($pendingInscription) class="table-warning" @endif>
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
                        @php
                            $anneeCouranteClasse = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
                            $inscriptionCouranteClasse = $etudiant->inscriptions->where('annee_universitaire_id', $anneeCouranteClasse?->id)->first();
                        @endphp
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
                        @php
                            $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
                            $inscriptionCourante = $etudiant->inscriptions->where('annee_universitaire_id', $anneeCourante?->id)->first();

                            // Labels des étapes du workflow
                            $workflowLabels = [
                                'prospect' => 'Prospect',
                                'documents_complets' => 'Documents complets',
                                'en_validation' => 'En validation',
                                'valide' => 'Validé',
                                'etudiant_cree' => 'Étudiant créé'
                            ];
                        @endphp
                        @if($inscriptionCourante)
                            @if($inscriptionCourante->workflow_step == 'etudiant_cree')
                                {{-- Workflow terminé: afficher uniquement le statut d'affectation --}}
                                @if($inscriptionCourante->affectation_status == 'affecté')
                                    <span class="badge bg-success px-3 py-2">Affecté</span>
                                @elseif($inscriptionCourante->affectation_status == 'réaffecté')
                                    <span class="badge bg-info px-3 py-2">Réaffecté</span>
                                @elseif($inscriptionCourante->affectation_status == 'non_affecté')
                                    <span class="badge bg-danger px-3 py-2">Non affecté</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            @else
                                {{-- Workflow en cours: afficher l'étape + statut d'affectation --}}
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="fas fa-tasks me-1"></i>{{ $workflowLabels[$inscriptionCourante->workflow_step] ?? $inscriptionCourante->workflow_step }}
                                    </span>
                                    @if($inscriptionCourante->affectation_status)
                                        <div>
                                            @if($inscriptionCourante->affectation_status == 'affecté')
                                                <span class="badge bg-success px-2 py-1" style="font-size: 0.7rem;">Affecté</span>
                                            @elseif($inscriptionCourante->affectation_status == 'réaffecté')
                                                <span class="badge bg-info px-2 py-1" style="font-size: 0.7rem;">Réaffecté</span>
                                            @elseif($inscriptionCourante->affectation_status == 'non_affecté')
                                                <span class="badge bg-danger px-2 py-1" style="font-size: 0.7rem;">Non affecté</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            <span class="text-muted small">Pas d'inscription ({{ $anneeCourante?->name ?? 'N/A' }})</span>
                        @endif
                    </td>
                    <td>
                        @if($etudiant->statut == 'actif')
                            <span class="badge bg-success px-3 py-2">Actif</span>
                        @else
                            <span class="badge bg-danger px-3 py-2">Inactif</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex">
                            <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Voir les détails">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('esbtp.etudiants.edit', $etudiant) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($pendingInscription)
                                @can('inscriptions.validate')
                                <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" data-bs-toggle="modal" data-bs-target="#validationModal{{ $pendingInscription->id }}" title="Valider l'inscription">
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
                    <td colspan="10" class="text-center">Aucun étudiant trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $etudiants->links() }}
</div>
