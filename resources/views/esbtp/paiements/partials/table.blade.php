<div class="card-moderne">
    <div class="p-lg">
        <div class="section-title mb-md d-flex align-items-center justify-content-between">
            <div>
                <i class="fas fa-list me-2"></i>
                Liste des Paiements
            </div>
            <div class="text-muted small">
                {{ $paiements->total() }} résultat(s)
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" class="form-check-input" title="Tout sélectionner">
                        </th>
                        <th>N° Reçu</th>
                        <th>Étudiant</th>
                        <th>Catégorie</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paiements as $paiement)
                        <tr>
                            <td>
                                @if($paiement->status == 'en_attente' && auth()->user()->hasRole('superAdmin'))
                                    <input type="checkbox" class="form-check-input paiement-checkbox"
                                           value="{{ $paiement->id }}"
                                           data-status="{{ $paiement->status }}">
                                @endif
                            </td>
                            <td>
                                <strong class="color-primary">{{ $paiement->numero_recu }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-primary me-2">
                                        {{ substr($paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet, 0, 2) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('esbtp.etudiants.show', $paiement->etudiant_id) }}" class="text-decoration-none">
                                            <strong>{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet }}</strong>
                                        </a>
                                        <div class="text-muted small">
                                            {{ $paiement->etudiant->matricule ?? 'Matricule n/a' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $categoryInfo = null;
                                    $categoryColors = [
                                        'academic' => 'success',
                                        'service' => 'warning', 
                                        'administrative' => 'info'
                                    ];
                                    $categoryIcons = [
                                        'academic' => 'fas fa-graduation-cap',
                                        'service' => 'fas fa-cogs',
                                        'administrative' => 'fas fa-file-alt'
                                    ];
                                    
                                    if ($paiement->fraisCategory) {
                                        $categoryInfo = [
                                            'name' => $paiement->fraisCategory->name,
                                            'type' => $paiement->fraisCategory->category_type ?? 'academic',
                                            'source' => 'Nouveau système'
                                        ];
                                    } elseif ($paiement->categorie) {
                                        $categoryInfo = [
                                            'name' => $paiement->categorie->nom ?? 'Catégorie ancienne',
                                            'type' => $paiement->categorie->nom && str_contains(strtolower($paiement->categorie->nom), 'cantine') ? 'service' : 'academic',
                                            'source' => 'Ancien système'
                                        ];
                                    } elseif ($paiement->motif || $paiement->type_paiement) {
                                        $motifLower = strtolower($paiement->motif ?? $paiement->type_paiement ?? '');
                                        $type = 'academic';
                                        if (str_contains($motifLower, 'cantine') || str_contains($motifLower, 'transport')) {
                                            $type = 'service';
                                        } elseif (str_contains($motifLower, 'documentation') || str_contains($motifLower, 'examen')) {
                                            $type = 'administrative';
                                        }
                                        $categoryInfo = [
                                            'name' => ucfirst($paiement->motif ?? $paiement->type_paiement ?? 'Paiement'),
                                            'type' => $type,
                                            'source' => 'Inféré du motif'
                                        ];
                                    }
                                    
                                    $color = $categoryColors[$categoryInfo['type'] ?? 'academic'] ?? 'secondary';
                                    $icon = $categoryIcons[$categoryInfo['type'] ?? 'academic'] ?? 'fas fa-money-bill';
                                @endphp
                                
                                @if($categoryInfo)
                                    <div class="badge bg-{{ $color }} d-flex align-items-center" style="max-width: 150px;">
                                        <i class="{{ $icon }} me-1"></i>
                                        <span class="text-truncate">{{ $categoryInfo['name'] }}</span>
                                    </div>
                                    <small class="text-muted d-block">{{ ucfirst($categoryInfo['type']) }}</small>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-question me-1"></i>Non définie
                                    </span>
                                @endif
                            </td>
                            <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            <td>
                                <strong class="color-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $paiement->mode_paiement }}</span>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'validé' => 'success',
                                        'en_attente' => 'warning', 
                                        'rejeté' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$paiement->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusColor }}">
                                    {{ $paiement->status_formatte }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" 
                                       class="btn btn-outline-info" title="Détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($paiement->status != 'validé')
                                        @can('edit-paiements')
                                        <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}" 
                                           class="btn btn-outline-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        
                                        @if(auth()->user()->hasRole('superAdmin'))
                                        <form action="{{ route('esbtp.paiements.valider', $paiement->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-outline-success"
                                                    title="Valider"
                                                    onclick="return confirm('Êtes-vous sûr de vouloir valider ce paiement ?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>

                                        @if($paiement->status == 'en_attente')
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="Rejeter"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rejetModal{{ $paiement->id }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @endif
                                        @endif
                                    @endif
                                    
                                    @if($paiement->status == 'validé')
                                        <div class="dropdown pdf-dropdown">
                                            <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                                    id="pdfDropdown{{ $paiement->id }}" data-bs-toggle="dropdown" 
                                                    aria-expanded="false" title="Options PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pdfDropdown{{ $paiement->id }}">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('esbtp.paiements.preview', $paiement->id) }}">
                                                        <i class="fas fa-eye me-1"></i>Prévisualiser
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('esbtp.paiements.recu', $paiement->id) }}">
                                                        <i class="fas fa-download me-1"></i>Télécharger
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif

                                    @if(auth()->user()->hasRole('superAdmin'))
                                        <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}"
                                           class="btn btn-outline-warning btn-sm"
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if(auth()->user()->hasRole('superAdmin'))
                        <div class="modal fade" id="rejetModal{{ $paiement->id }}" tabindex="-1" role="dialog" aria-labelledby="rejetModalLabel{{ $paiement->id }}" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('esbtp.paiements.rejeter', $paiement->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="rejetModalLabel{{ $paiement->id }}">Rejeter le paiement {{ $paiement->numero_recu }}</h5>
                                            <button type="button" class="close btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="motif_rejet{{ $paiement->id }}">Motif du rejet</label>
                                                <textarea name="motif_rejet" id="motif_rejet{{ $paiement->id }}" class="form-control" rows="4" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-danger">Rejeter</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <br><span class="text-muted">Aucun paiement trouvé</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $paiements->appends(request()->query())->links() }}
        </div>
    </div>
</div>
