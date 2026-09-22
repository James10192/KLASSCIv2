{{-- Partial réutilisable pour une ligne de paiement dans le tableau --}}
<tr data-paiement-id="{{ $paiement->id }}" class="pi-payment-row pi-payment-row--{{ $paiement->status }}">
    <td class="pi-cell-selection">
        @if($paiement->status == 'en_attente' && auth()->user()->can('paiements.validate'))
            <input type="checkbox" class="form-check-input paiement-checkbox"
                   value="{{ $paiement->id }}"
                   data-status="{{ $paiement->status }}">
        @endif
    </td>
    <td class="pi-cell-receipt">
        <div class="pi-receipt">
            <span class="pi-receipt-label">Reçu</span>
            <strong class="pi-receipt-number">{{ $paiement->numero_recu }}</strong>
        </div>
        @if($paiement->isAvoir())
            <div class="small" style="color:#0453cb;font-weight:700;">{{ $paiement->avoir_kind === 'refund' ? 'Remboursement' : 'Avoir crédit' }}</div>
        @endif
    </td>
    <td class="pi-cell-student">
        <div class="pi-student">
            <div class="avatar-circle pi-student-avatar">
                {{ substr($paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet, 0, 2) }}
            </div>
            <div class="pi-student-details">
                <a href="{{ route('esbtp.inscriptions.situation-financiere.preview', $paiement->inscription_id) }}" class="pi-student-name">
                    {{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet }}
                </a>
                <div class="pi-student-meta">
                    <i class="fas fa-id-card"></i>{{ $paiement->etudiant->matricule ?? 'Matricule n/a' }}
                </div>
            </div>
        </div>
    </td>
    <td class="d-none d-md-table-cell pi-cell-category">
        @php
            // Une seule lecture de la ventilation, partagee avec le PDF,
            // l'Excel et le recu : le repli vers la categorie du guichet vit
            // dans le modele, plus dans chaque surface qui l'affiche.
            $ventilation = $paiement->ventilation();
            $noms = $ventilation->pluck('nom');

            $type = $ventilation->first()['type'] ?? 'academic';
            // Versement historique sans categorie : le type se devine au motif,
            // faute de mieux. Conserve pour ne pas repeindre l'existant.
            if (($ventilation->first()['frais_id'] ?? null) === null) {
                $motifLower = mb_strtolower($paiement->motif ?? $paiement->type_paiement ?? '', 'UTF-8');
                if (str_contains($motifLower, 'cantine') || str_contains($motifLower, 'transport')) {
                    $type = 'service';
                } elseif (str_contains($motifLower, 'documentation') || str_contains($motifLower, 'examen')) {
                    $type = 'administrative';
                }
            }

            $categoryInfo = [
                'name' => $noms->count() > 1
                    ? $noms->take(2)->implode(', ').($noms->count() > 2 ? ' +'.($noms->count() - 2) : '')
                    : $noms->first(),
                'type' => $type,
                'detail' => $ventilation->count() > 1
                    ? $ventilation->map(fn ($l) => $l['nom'].' : '.number_format($l['montant'], 0, ',', ' ').' FCFA')->implode('  ·  ')
                    : null,
            ];

            $color = $categoryColors[$categoryInfo['type'] ?? 'academic'] ?? 'secondary';
            $icon = $categoryIcons[$categoryInfo['type'] ?? 'academic'] ?? 'fas fa-money-bill';
        @endphp

        @if($categoryInfo)
            <div class="pi-category-chip pi-category-chip--{{ $color }}" style="max-width: 170px;"
                 @if(!empty($categoryInfo['detail'])) title="{{ $categoryInfo['detail'] }}" @endif>
                <i class="{{ $icon }} me-1"></i>
                <span class="text-truncate">{{ $categoryInfo['name'] }}</span>
            </div>
            <small class="pi-category-type">{{ ucfirst($categoryInfo['type']) }}</small>
        @else
            <span class="pi-category-chip pi-category-chip--secondary">
                <i class="fas fa-question"></i>Non définie
            </span>
        @endif
    </td>
    <td class="pi-cell-date">
        <div class="pi-date">
            <i class="far fa-calendar-alt"></i>
            <span>{{ $paiement->date_paiement->format('d/m/Y') }}</span>
        </div>
    </td>
    <td class="pi-cell-amount">
        @php
            // Filtre par frais actif : la ligne doit dire ce que CE frais a
            // recu, pas le versement entier. Sur un versement de 255 000 F
            // dont 60 000 sont alles a la tenue, afficher 255 000 sur une
            // liste filtree "Tenue" ferait croire que la tenue a encaisse
            // 255 000 — et le total du bas annoncerait de l'argent que
            // l'ecole n'a jamais recu sur ce frais.
            $fraisFiltre = request('frais_category_id');
            $partFrais = $fraisFiltre ? $paiement->partPourCategorie((int) $fraisFiltre) : null;
            $montantAffiche = $partFrais ?? $paiement->montant;
        @endphp
        @if($paiement->isAvoir())
            <strong class="pi-amount pi-amount--credit">− {{ number_format($montantAffiche, 0, ',', ' ') }} <span>FCFA</span></strong>
        @else
            <strong class="pi-amount">{{ number_format($montantAffiche, 0, ',', ' ') }} <span>FCFA</span></strong>
        @endif
        @if($partFrais !== null && (float) $partFrais !== (float) $paiement->montant)
            <small class="text-muted d-block" style="font-size:.7rem;"
                   title="Part imputée à ce frais ; le versement complet vaut {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA">
                sur {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
            </small>
        @endif
    </td>
    <td class="d-none d-md-table-cell pi-cell-mode">
        <span class="pi-mode"><i class="fas fa-wallet"></i>{{ $paiement->mode_paiement }}</span>
    </td>
    {{-- Meme regle que l'en-tete de table.blade.php. Le repli ne doit pas etre
         `false` : refresh-ligne rend cette ligne seule apres une validation, et
         une cellule manquante decalait toute la ligne sous l'en-tete. --}}
    @if($showCreatorColumn ?? (auth()->user()?->can('paiements.view') ?? false))
        {{-- Lot 13 — Encaisseur (visible uniquement pour les users avec paiements.view) --}}
        <td class="d-none d-lg-table-cell pi-cell-creator">
            @if($paiement->creator)
                <span class="pi-creator" title="{{ $paiement->creator->name }}">
                    <i class="fas fa-user-circle"></i>{{ $paiement->creator->name }}
                </span>
            @else
                <span class="text-muted fst-italic">—</span>
            @endif
        </td>
    @endif
    <td class="pi-cell-status">
        @php
            $statusColors = [
                'validé' => 'success',
                'en_attente' => 'warning',
                'rejeté' => 'danger'
            ];
            $statusColor = $statusColors[$paiement->status] ?? 'secondary';
        @endphp
        <span class="pi-status pi-status--{{ $statusColor }}">
            <i class="fas fa-circle"></i>{{ $paiement->status_formatte }}
        </span>
    </td>
    <td class="pi-cell-actions">
        <div class="paiement-actions-wrapper" data-paiement-actions="{{ $paiement->id }}">
            <div class="btn-group btn-group-sm paiement-actions-buttons">
                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}"
                   class="btn btn-outline-info" title="Détails">
                    <i class="fas fa-eye"></i>
                </a>

                @if($paiement->status != 'validé')
                    @can('paiements.edit')
                        <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}"
                           class="btn btn-outline-warning" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endcan

                    @if($paiement->status == 'en_attente' && auth()->user()->can('paiements.validate'))
                        <button type="button"
                                class="btn btn-outline-success valider-paiement-btn"
                                title="Valider"
                                data-paiement-id="{{ $paiement->id }}"
                                data-action-url="{{ route('esbtp.paiements.valider', $paiement->id) }}">
                            <i class="fas fa-check"></i>
                        </button>

                        <button type="button"
                                class="btn btn-outline-danger"
                                title="Rejeter"
                                data-bs-toggle="modal"
                                data-bs-target="#rejetModal{{ $paiement->id }}">
                            <i class="fas fa-times"></i>
                        </button>
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
                            @if($paiement->isAvoir())
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.paiements.avoir.pdf', [$paiement->id, 'inline' => 1]) }}" target="_blank" rel="noopener">
                                    <i class="fas fa-eye me-1"></i>Prévisualiser l'avoir
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.paiements.avoir.pdf', $paiement->id) }}">
                                    <i class="fas fa-download me-1"></i>Télécharger l'avoir
                                </a>
                            </li>
                            @else
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.paiements.preview-pdf', $paiement->id) }}" target="_blank" rel="noopener">
                                    <i class="fas fa-eye me-1"></i>Prévisualiser
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.paiements.recu', $paiement->id) }}">
                                    <i class="fas fa-download me-1"></i>Télécharger
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                    {{-- Corriger un mode mal saisi. Ne parait que sur un versement
                         valide non rapproche : ailleurs, la correction passe par
                         l'ecran de modification ou par la reconciliation. --}}
                    @can('paiements.correct_mode')
                        @if($paiement->status === 'validé' && ! $paiement->isAvoir() && ! $paiement->reconciliation_locked_at)
                        <button type="button" class="btn btn-outline-secondary" title="Corriger le mode de règlement"
                                data-bs-toggle="modal" data-bs-target="#modeReglementModal{{ $paiement->id }}">
                            <i class="fas fa-right-left"></i>
                        </button>
                        @endif
                    @endcan
                    @can('paiements.avoir')
                        @if(! $paiement->isAvoir() && $paiement->avoir_disponible > 0)
                        <button type="button" class="btn btn-outline-primary" title="Émettre un avoir"
                                data-bs-toggle="modal" data-bs-target="#avoirModal{{ $paiement->id }}">
                            <i class="fas fa-file-invoice"></i>
                        </button>
                        @endif
                    @endcan
                @endif

                @can('cancelOwnRecent', $paiement)
                <form action="{{ route('esbtp.paiements.cancel-own', $paiement->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning" title="Annuler ma saisie"
                            onclick="return confirm('Annuler ce versement ?')">
                        <i class="fas fa-undo"></i>
                    </button>
                </form>
                @endcan

                @can('paiements.edit')
                    <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}"
                       class="btn btn-outline-warning btn-sm"
                       title="Modifier">
                        <i class="fas fa-edit"></i>
                    </a>
                @endcan
            </div>
            <div class="paiement-actions-spinner" aria-hidden="true">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </td>
</tr>

<!-- Modal de rejet individuel -->
@if($paiement->status == 'en_attente' && auth()->user()->can('paiements.validate'))
<div class="modal fade" id="rejetModal{{ $paiement->id }}" tabindex="-1" aria-labelledby="rejetModalLabel{{ $paiement->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('esbtp.paiements.rejeter', $paiement->id) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejetModalLabel{{ $paiement->id }}">
                        <i class="fas fa-times-circle me-2"></i>
                        Rejeter le paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Vous êtes sur le point de rejeter le paiement <strong>{{ $paiement->numero_recu }}</strong>
                        de <strong>{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet }}</strong>.
                    </p>
                    <div class="mb-3">
                        <label for="motif_rejet{{ $paiement->id }}" class="form-label" style="cursor: default;">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="motif_rejet{{ $paiement->id }}" name="motif_rejet" rows="4"
                                  placeholder="Expliquez pourquoi ce paiement est rejeté..." style="cursor: text;"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmer_rejet{{ $paiement->id }}" style="cursor: pointer;">
                        <label class="form-check-label" for="confirmer_rejet{{ $paiement->id }}" style="cursor: pointer;">
                            Je confirme le rejet de ce paiement
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-danger rejeter-paiement-submit-btn" data-paiement-id="{{ $paiement->id }}">
                        <i class="fas fa-times-circle me-1"></i>Rejeter le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@include('esbtp.paiements.partials.avoir-modal', ['paiement' => $paiement, 'modalId' => 'avoirModal'.$paiement->id])
@include('esbtp.paiements.partials.mode-reglement-modal', ['paiement' => $paiement])
