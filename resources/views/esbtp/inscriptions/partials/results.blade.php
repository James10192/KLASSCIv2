@if($inscriptions->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    @if(auth()->user()->hasRole('superAdmin'))
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all-inscriptions" class="form-check-input">
                    </th>
                    @endif
                    <th>N° Inscription</th>
                    <th>Matricule</th>
                    <th>Étudiant</th>
                    <th>Filière</th>
                    <th>Niveau</th>
                    <th>Année Universitaire</th>
                    <th>Statut</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inscriptions as $inscription)
                    @php
                        $hasProbleme = session('inscriptions_problemes') && isset(session('inscriptions_problemes')[$inscription->id]);
                        $problemeInfo = $hasProbleme ? session('inscriptions_problemes')[$inscription->id] : null;
                        $problemeClass = $hasProbleme ? ($problemeInfo['type'] === 'error' ? 'table-danger' : 'table-warning') : '';
                    @endphp
                    <tr class="{{ $problemeClass }}">
                        @if(auth()->user()->hasRole('superAdmin'))
                        <td>
                            @if($inscription->status == 'pending' || $inscription->status == 'en_attente')
                            <input type="checkbox" class="form-check-input inscription-checkbox"
                                   value="{{ $inscription->id }}"
                                   data-inscription-id="{{ $inscription->id }}">
                            @endif
                        </td>
                        @endif
                        <td style="max-width: 250px;">
                            @if($hasProbleme)
                                <div class="d-flex flex-column gap-2">
                                    <span class="badge {{ $problemeInfo['type'] === 'error' ? 'bg-danger' : 'bg-warning text-dark' }} d-inline-flex align-items-start"
                                          style="font-size: 0.75rem; white-space: normal; word-wrap: break-word; text-align: left; line-height: 1.3;">
                                        <i class="fas {{ $problemeInfo['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle' }} me-1 mt-1" style="flex-shrink: 0;"></i>
                                        <span style="word-break: break-word;">{{ $problemeInfo['message'] }}</span>
                                    </span>

                                    @php
                                        $raison = $problemeInfo['message'];
                                        $isPaiementNonValide = str_contains($raison, 'paiement') && str_contains($raison, 'validé');
                                        $isClassePleine = str_contains($raison, 'Classe pleine') || str_contains($raison, 'classe pleine');
                                        $isSansPaiement = str_contains($raison, 'Aucun paiement') || str_contains($raison, 'sans paiement');
                                    @endphp

                                    @if($isPaiementNonValide)
                                        <button type="button" class="btn btn-sm btn-action-quick"
                                                onclick="ouvrirModalValiderPaiement({{ $inscription->id }})"
                                                style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; border: none; border-radius: 8px; padding: 6px 12px; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-check-circle me-1"></i>Valider paiement
                                        </button>
                                    @elseif($isClassePleine)
                                        <button type="button" class="btn btn-sm btn-action-quick"
                                                onclick="ouvrirModalChangerClasse({{ $inscription->id }})"
                                                style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; border: none; border-radius: 8px; padding: 6px 12px; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-exchange-alt me-1"></i>Changer classe
                                        </button>
                                    @elseif($isSansPaiement)
                                        <button type="button" class="btn btn-sm btn-action-quick"
                                                onclick="ouvrirModalCreerPaiement({{ $inscription->id }})"
                                                style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; border: none; border-radius: 8px; padding: 6px 12px; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-plus-circle me-1"></i>Créer paiement
                                        </button>
                                    @endif
                                </div>
                            @else
                                {{ $inscription->numero_inscription }}
                            @endif
                        </td>
                        <td>{{ $inscription->etudiant->matricule ?? 'N/A' }}</td>
                        <td>{{ $inscription->etudiant->nom ?? '' }} {{ $inscription->etudiant->prenoms ?? '' }}</td>
                        <td>{{ $inscription->filiere->name ?? ($inscription->filiere->nom ?? 'N/A') }}</td>
                        <td>{{ $inscription->niveau->name ?? ($inscription->niveau->nom ?? 'N/A') }}</td>
                        <td>{{ $inscription->anneeUniversitaire->name ?? ($inscription->anneeUniversitaire->annee_scolaire ?? 'N/A') }}</td>
                        <td>
                            @if($inscription->status == 'pending' || $inscription->status == 'en_attente')
                                <span class="badge bg-warning text-dark px-3 py-2">En attente</span>
                            @elseif($inscription->status == 'validated' || $inscription->status == 'active')
                                <span class="badge bg-success px-3 py-2">Validée</span>
                            @elseif($inscription->status == 'cancelled')
                                <span class="badge bg-danger px-3 py-2">Annulée</span>
                            @else
                                <span class="badge bg-secondary px-3 py-2">{{ ucfirst($inscription->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $inscription->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="d-flex">
                                @can('inscriptions.view')
                                <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                
                                @can('edit inscriptions')
                                @if($inscription->status == 'pending')
                                <a href="{{ route('esbtp.inscriptions.edit', $inscription->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                @endcan

                                @if($inscription->status == 'pending')
                                    @can('valider inscriptions')
                                    <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1 valider-btn" 
                                            data-id="{{ $inscription->id }}" title="Valider l'inscription">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <form id="valider-form-{{ $inscription->id }}" action="{{ route('esbtp.inscriptions.valider', $inscription->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    @endcan
                                @endif

                                @if($inscription->status == 'pending')
                                    @can('annuler inscriptions')
                                    <button type="button" class="btn btn-warning btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1 annuler-btn" 
                                            data-id="{{ $inscription->id }}" data-bs-toggle="modal" 
                                            data-bs-target="#annulerModal{{ $inscription->id }}" title="Annuler l'inscription">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    
                                    <!-- Modal d'annulation -->
                                    <div class="modal fade" id="annulerModal{{ $inscription->id }}" tabindex="-1" aria-labelledby="annulerModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="annulerModalLabel">Annulation d'inscription</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Êtes-vous sûr de vouloir annuler l'inscription de <strong>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenom }}</strong> ?</p>
                                                    <form action="{{ route('esbtp.inscriptions.annuler', $inscription->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="form-group">
                                                            <label for="motif">Motif d'annulation</label>
                                                            <textarea class="form-control" id="motif" name="motif" rows="3" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-warning">Confirmer l'annulation</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endcan
                                @endif

                                @can('delete inscriptions')
                                <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 delete-btn" 
                                        data-id="{{ $inscription->id }}" data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal{{ $inscription->id }}" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                                
                                <!-- Modal de suppression -->
                                <div class="modal fade" id="deleteModal{{ $inscription->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $inscription->id }}" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel{{ $inscription->id }}">Supprimer l'inscription</h5>
                                                <button type="button" class="close btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer cette inscription ? Cette action est irréversible.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="{{ route('esbtp.inscriptions.destroy', $inscription->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $inscriptions->appends(request()->query())->links() }}
    </div>
@else
    <div class="alert alert-info">
        Aucune inscription ne correspond à vos critères de recherche.
    </div>
@endif
