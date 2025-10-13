{{-- Partial réutilisable pour une ligne de paiement dans le tableau --}}
<tr data-paiement-id="{{ $paiement->id }}">
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
                <button type="button"
                        class="btn btn-outline-success valider-paiement-btn"
                        title="Valider"
                        data-paiement-id="{{ $paiement->id }}"
                        data-action-url="{{ route('esbtp.paiements.valider', $paiement->id) }}">
                    <i class="fas fa-check"></i>
                </button>

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
