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
                        @if($etudiant->inscriptions->count() > 0)
                            <?php $derniere = $etudiant->inscriptions->sortByDesc('created_at')->first(); ?>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    {{ $derniere->classe ? $derniere->classe->name : 'Non assigné' }}
                                    <br>
                                    <small>
                                        {{ $derniere->filiere ? $derniere->filiere->name : '' }}
                                        {{ $derniere->niveau ? ' - '.$derniere->niveau->name : '' }}
                                    </small>
                                </div>
                                @if($derniere->status == 'pending' || $derniere->status == 'en_attente')
                                    <div class="ms-2" title="Inscription en attente de validation">
                                        <i class="fas fa-hourglass-half text-warning"></i>
                                    </div>
                                @elseif($derniere->status == 'active')
                                    <div class="ms-2" title="Inscription validée">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                @endif
                            </div>
                        @else
                            <span class="text-muted">Non inscrit</span>
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
                    <td colspan="9" class="text-center">Aucun étudiant trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $etudiants->links() }}
</div>
