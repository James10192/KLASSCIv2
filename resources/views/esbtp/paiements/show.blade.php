@extends('layouts.app')

@section('title', 'Détails du Paiement #' . $paiement->numero_recu . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .payment-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        position: relative;
        overflow: hidden;
    }
    
    .payment-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(50%, -50%);
    }
    
    .payment-amount {
        font-size: 2.5rem;
        font-weight: 800;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .status-badge {
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-full);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: var(--text-small);
    }
    
    .status-badge.validé {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 2px solid var(--success);
    }
    
    .status-badge.en_attente {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 2px solid var(--warning);
    }
    
    .status-badge.rejeté {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 2px solid var(--danger);
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-md) 0;
        border-bottom: 1px solid var(--border-light);
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: var(--text-secondary);
        flex: 0 0 40%;
    }
    
    .info-value {
        color: var(--text-primary);
        font-weight: 500;
        text-align: right;
        flex: 1;
    }
    
    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        margin-right: var(--space-md);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .action-buttons {
        display: flex;
        gap: var(--space-md);
        flex-wrap: wrap;
        align-items: center;
    }
    
    .btn-action {
        padding: var(--space-sm) var(--space-lg);
        border-radius: var(--radius-small);
        border: none;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        transition: all 0.3s ease;
        white-space: nowrap;
        min-width: 120px;
        justify-content: center;
    }
    
    .btn-action.primary {
        background: var(--primary);
        color: white;
    }
    
    .btn-action.secondary {
        background: var(--surface);
        color: var(--text-primary);
        border: 1px solid var(--border);
    }
    
    .btn-action.success {
        background: var(--success);
        color: white;
    }
    
    .btn-action.danger {
        background: var(--danger);
        color: white;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .comment-section {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border-left: 4px solid var(--primary);
    }
    
    .timeline-item {
        font-size: var(--text-small);
        color: var(--text-secondary);
        padding: var(--space-sm);
        background: var(--surface);
        border-radius: var(--radius-small);
        border-left: 3px solid var(--border);
    }
    
    /* Styles pour le dropdown PDF compact sur page show */
    .pdf-dropdown-show {
        position: relative;
        z-index: 1000;
    }
    
    .pdf-dropdown-show .dropdown-menu {
        min-width: 150px;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: 1px solid var(--border);
        position: absolute !important;
        z-index: 1050;
        right: 0;
        left: auto;
    }
    
    .pdf-dropdown-show .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    
    .pdf-dropdown-show .dropdown-item:hover {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
    }
    
    .pdf-dropdown-show .dropdown-item i {
        width: 16px;
        text-align: center;
        margin-right: 0.5rem;
    }
    
    /* Forcer la visibilité du dropdown en surpassant les overflows */
    .action-buttons {
        overflow: visible !important;
    }
    
    .payment-header {
        overflow: visible !important;
    }
    
    .payment-header .text-end {
        overflow: visible !important;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header avec informations principales -->
        <div class="payment-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="mb-2">Paiement #{{ $paiement->numero_recu }}</h1>
                    <div class="payment-amount">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
                    <p class="mb-0 opacity-75">{{ $paiement->date_paiement->format('d/m/Y à H:i') }}</p>
                </div>
                <div class="text-end">
                    <div class="status-badge {{ $paiement->status }}">
                        <i class="fas fa-{{ $paiement->status == 'validé' ? 'check-circle' : ($paiement->status == 'en_attente' ? 'clock' : 'times-circle') }} me-1"></i>
                        {{ $paiement->status_formatte }}
                    </div>
                    <div class="action-buttons mt-3">
                        <a href="{{ route('esbtp.paiements.index') }}" class="btn-action secondary mb-2">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                        @if($paiement->status == 'validé')
                        <div class="dropdown pdf-dropdown-show mb-2">
                            <button class="btn-action primary dropdown-toggle" type="button" 
                                    id="pdfDropdownShow" data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <i class="fas fa-file-pdf me-1"></i>Reçu PDF
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pdfDropdownShow">
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
                            <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}" class="btn-action warning mb-2">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                        @endif

                        @if($paiement->status === 'en_attente')
                            <form action="{{ route('esbtp.paiements.valider', $paiement->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir valider ce paiement ?')">
                                @csrf
                                <button type="submit" class="btn-action success mb-2">
                                    <i class="fas fa-check me-1"></i>Valider
                                </button>
                            </form>

                            <button type="button" class="btn-action danger mb-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="fas fa-times me-1"></i>Rejeter
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Informations du paiement -->
            <div class="col-lg-6 mb-lg">
                <div class="card-moderne">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-receipt me-2"></i>
                            Informations du Paiement
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Numéro de reçu</span>
                            <span class="info-value">
                                <code>{{ $paiement->numero_recu }}</code>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Catégorie de frais</span>
                            <span class="info-value">
                                @if($paiement->fraisCategory)
                                    @php
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
                                        $categoryType = $paiement->fraisCategory->category_type ?? 'academic';
                                        $color = $categoryColors[$categoryType] ?? 'secondary';
                                        $icon = $categoryIcons[$categoryType] ?? 'fas fa-money-bill';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        <i class="{{ $icon }} me-1"></i>{{ $paiement->fraisCategory->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Non définie</span>
                                @endif
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Mode de paiement</span>
                            <span class="info-value">
                                <span class="badge bg-info">{{ ucfirst($paiement->mode_paiement) }}</span>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Référence</span>
                            <span class="info-value">
                                <code>{{ $paiement->reference_paiement ?: 'N/A' }}</code>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Motif</span>
                            <span class="info-value">{{ $paiement->motif ?: 'Non spécifié' }}</span>
                        </div>
                        
                        @if($paiement->status == 'validé' && $paiement->date_validation)
                        <div class="info-item">
                            <span class="info-label">Date de validation</span>
                            <span class="info-value">{{ $paiement->date_validation->format('d/m/Y à H:i') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Validé par</span>
                            <span class="info-value">{{ $paiement->validatedBy ? $paiement->validatedBy->name : 'N/A' }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Informations de l'étudiant -->
            <div class="col-lg-6 mb-lg">
                <div class="card-moderne">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-user-graduate me-2"></i>
                            Informations de l'Étudiant
                        </div>
                        
                        <!-- Avatar et nom -->
                        <div class="d-flex align-items-center mb-lg">
                            <div class="student-avatar">
                                {{ substr($paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet, 0, 2) }}
                            </div>
                            <div>
                                <h5 class="mb-1">{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet }}</h5>
                                <p class="text-muted mb-0">{{ $paiement->etudiant->matricule }}</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Email personnel</span>
                            <span class="info-value">
                                @if($paiement->etudiant->email_personnel)
                                    <a href="mailto:{{ $paiement->etudiant->email_personnel }}" class="text-primary">
                                        {{ $paiement->etudiant->email_personnel }}
                                    </a>
                                @else
                                    <span class="text-muted">Non renseigné</span>
                                @endif
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Email institutionnel</span>
                            <span class="info-value">
                                @if($paiement->etudiant->user && $paiement->etudiant->user->email)
                                    <a href="mailto:{{ $paiement->etudiant->user->email }}" class="text-primary">
                                        {{ $paiement->etudiant->user->email }}
                                    </a>
                                @else
                                    <span class="text-muted">Non disponible</span>
                                @endif
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Filière</span>
                            <span class="info-value">
                                <span class="badge bg-primary">{{ $paiement->inscription->filiere->name ?? 'N/A' }}</span>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Niveau d'étude</span>
                            <span class="info-value">{{ $paiement->inscription->niveauEtude->name ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Année universitaire</span>
                            <span class="info-value">
                                @if($paiement->inscription->anneeUniversitaire)
                                    {{ $paiement->inscription->anneeUniversitaire->libelle ?: $paiement->inscription->anneeUniversitaire->annee_debut . '-' . $paiement->inscription->anneeUniversitaire->annee_fin }}
                                @else
                                    <span class="text-muted">Non définie</span>
                                @endif
                            </span>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="action-buttons mt-lg">
                            <a href="{{ route('esbtp.etudiants.show', $paiement->etudiant_id) }}" class="btn-action primary mb-2">
                                <i class="fas fa-user me-1"></i>Voir le profil
                            </a>
                            <a href="{{ route('esbtp.inscriptions.show', $paiement->inscription_id) }}" class="btn-action secondary mb-2">
                                <i class="fas fa-eye me-1"></i>Voir l'inscription
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section commentaires et historique -->
        <div class="card-moderne">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-comments me-2"></i>
                    Commentaires et Historique
                </div>
                
                @if($paiement->commentaire)
                    <div class="comment-section mb-lg">
                        <h6 class="mb-2">
                            <i class="fas fa-comment text-primary me-2"></i>Commentaire
                        </h6>
                        <p class="mb-0">{{ $paiement->commentaire }}</p>
                    </div>
                @endif
                
                <!-- Historique -->
                <div class="timeline-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-plus-circle text-success me-2"></i>
                            Paiement créé le {{ $paiement->created_at->format('d/m/Y à H:i') }}
                            @if($paiement->createdBy)
                                par <strong>{{ $paiement->createdBy->name }}</strong>
                            @endif
                        </span>
                    </div>
                    
                    @if($paiement->updated_at->gt($paiement->created_at))
                    <div class="mt-2 pt-2 border-top">
                        <i class="fas fa-edit text-warning me-2"></i>
                        Dernière modification le {{ $paiement->updated_at->format('d/m/Y à H:i') }}
                        @if($paiement->updatedBy)
                            par <strong>{{ $paiement->updatedBy->name }}</strong>
                        @endif
                    </div>
                    @endif
                </div>
                
                <!-- Actions de validation -->
                @if($paiement->status == 'en_attente')
                    @can('validate-paiements')
                    <div class="mt-lg">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Ce paiement est en attente de validation.
                        </div>
                        <div class="action-buttons">
                            <button type="button" class="btn-action success" data-bs-toggle="modal" data-bs-target="#modalValider">
                                <i class="fas fa-check"></i>Valider ce paiement
                            </button>
                            <button type="button" class="btn-action danger" data-bs-toggle="modal" data-bs-target="#modalRejeter">
                                <i class="fas fa-times"></i>Rejeter ce paiement
                            </button>
                        </div>
                    </div>
                    @endcan
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Valider -->
@can('validate-paiements')
<div class="modal fade" id="modalValider" tabindex="-1" aria-labelledby="modalValiderLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalValiderLabel">
                    <i class="fas fa-check-circle me-2"></i>Valider le paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Vous êtes sur le point de valider ce paiement.
                </div>
                <div class="row">
                    <div class="col-6"><strong>Montant :</strong></div>
                    <div class="col-6">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Étudiant :</strong></div>
                    <div class="col-6">{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Référence :</strong></div>
                    <div class="col-6"><code>{{ $paiement->numero_recu }}</code></div>
                </div>
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="submit" class="btn btn-success" form="validerForm">
                    <i class="fas fa-check me-1"></i>Confirmer la validation
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

<!-- Modal Rejeter -->
@can('validate-paiements')
<div class="modal fade" id="modalRejeter" tabindex="-1" aria-labelledby="modalRejeterLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalRejeterLabel">
                    <i class="fas fa-times-circle me-2"></i>Rejeter le paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('esbtp.paiements.rejeter', $paiement->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous êtes sur le point de rejeter ce paiement.
                    </div>
                    
                    <div class="form-group-moderne mb-3">
                        <label for="commentaire" class="form-label-moderne">
                            Motif du rejet <span class="text-danger">*</span>
                        </label>
                        <textarea name="commentaire" id="commentaire" rows="4" class="form-control-moderne" required 
                                  placeholder="Veuillez expliquer la raison du rejet..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Cette action est irréversible.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Confirmer le rejet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
$(function() {
    // Animation d'entrée des cartes
    $('.card-moderne').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 100).animate({
            'opacity': '1'
        }, 600).css('transform', 'translateY(0)');
    });
    
    // Copier au clic sur les codes
    $('code').click(function() {
        const text = $(this).text();
        navigator.clipboard.writeText(text).then(() => {
            $(this).attr('title', 'Copié!').tooltip('show');
            setTimeout(() => {
                $(this).attr('title', 'Cliquer pour copier');
            }, 2000);
        });
    }).attr('title', 'Cliquer pour copier').css('cursor', 'pointer');
});
</script>
    // Validation/Rejet des paiements
    $('#validerBtn').click(function() {
        if (confirm('Êtes-vous sûr de vouloir valider ce paiement ?')) {
            $('#validerForm').submit();
        }
    });

    $('#rejeterBtn').click(function() {
        $('#rejetModal').modal('show');
    });
});
</script>

<!-- Modal de rejet -->
<div class="modal fade" id="rejetModal" tabindex="-1" aria-labelledby="rejetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.paiements.rejeter', $paiement->id) }}" method="POST" id="rejetForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejetModalLabel">
                        <i class="fas fa-times-circle text-danger me-2"></i>
                        Rejeter le paiement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous êtes sur le point de rejeter ce paiement. Cette action nécessite une justification.
                    </div>

                    <div class="mb-3">
                        <label for="motif_rejet" class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="motif_rejet" name="motif_rejet" rows="4"
                                  placeholder="Expliquez pourquoi ce paiement est rejeté..." required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmer_rejet" required>
                        <label class="form-check-label" for="confirmer_rejet">
                            Je confirme le rejet de ce paiement
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger" id="confirmerRejet">
                        <i class="fas fa-times-circle me-1"></i>Rejeter le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush