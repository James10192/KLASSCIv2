@extends('layouts.app')

@section('title', 'Administration des Inscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Administration des Inscriptions</h1>
                <p class="header-subtitle">Gestion et validation des inscriptions en attente</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux inscriptions
                </a>
            </div>
        </div>

        <div class="p-lg">
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card-moderne text-center">
                        <div class="p-md">
                            <div class="text-primary mb-2">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['total_en_attente'] }}</h3>
                            <p class="text-muted mb-0">Total en attente</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card-moderne text-center">
                        <div class="p-md">
                            <div class="text-success mb-2">
                                <i class="fas fa-credit-card fa-2x"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['avec_paiement'] }}</h3>
                            <p class="text-muted mb-0">Avec paiement</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card-moderne text-center">
                        <div class="p-md">
                            <div class="text-warning mb-2">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['sans_paiement'] }}</h3>
                            <p class="text-muted mb-0">Sans paiement</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card-moderne text-center">
                        <div class="p-md">
                            <div class="text-info mb-2">
                                <i class="fas fa-user-plus fa-2x"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['prospects'] }}</h3>
                            <p class="text-muted mb-0">Prospects</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres de recherche -->
            <div class="card-moderne mb-4">
                <div class="p-lg">
                    <div class="section-title mb-md">
                        <i class="fas fa-filter"></i>Filtrer les inscriptions en attente
                    </div>
                    <form method="GET" action="{{ route('esbtp.inscriptions.administration') }}">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="search" class="form-label">
                                    <i class="fas fa-search me-1"></i>Recherche par nom ou matricule
                                </label>
                                <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Tapez pour rechercher...">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="filiere" class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Filière
                                </label>
                                <select class="form-select" id="filiere" name="filiere">
                                    <option value="">Toutes les filières</option>
                                    @foreach($filieres as $fil)
                                        <option value="{{ $fil->id }}" {{ request('filiere') == $fil->id ? 'selected' : '' }}>
                                            {{ $fil->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="niveau" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>Niveau d'études
                                </label>
                                <select class="form-select" id="niveau" name="niveau">
                                    <option value="">Tous les niveaux</option>
                                    @foreach($niveaux as $niv)
                                        <option value="{{ $niv->id }}" {{ request('niveau') == $niv->id ? 'selected' : '' }}>
                                            {{ $niv->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="workflow_step" class="form-label">
                                    <i class="fas fa-tasks me-1"></i>Étape du workflow
                                </label>
                                <select class="form-select" id="workflow_step" name="workflow_step">
                                    <option value="">Toutes les étapes</option>
                                    <option value="prospect" {{ request('workflow_step') == 'prospect' ? 'selected' : '' }}>Prospect</option>
                                    <option value="documents_complets" {{ request('workflow_step') == 'documents_complets' ? 'selected' : '' }}>Documents complets</option>
                                    <option value="en_validation" {{ request('workflow_step') == 'en_validation' ? 'selected' : '' }}>En validation</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="has_payment" class="form-label">
                                    <i class="fas fa-credit-card me-1"></i>Statut paiement
                                </label>
                                <select class="form-select" id="has_payment" name="has_payment">
                                    <option value="">Tous</option>
                                    <option value="yes" {{ request('has_payment') == 'yes' ? 'selected' : '' }}>Avec paiement</option>
                                    <option value="no" {{ request('has_payment') == 'no' ? 'selected' : '' }}>Sans paiement</option>
                                </select>
                            </div>
                            <div class="col-md-1 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-search"></i>Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des inscriptions -->
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-md">
                        <i class="fas fa-list"></i>Inscriptions en attente de validation ({{ $inscriptions->total() }})
                    </div>
                    @if($inscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Matricule</th>
                                        <th>Nom complet</th>
                                        <th>Filière</th>
                                        <th>Niveau</th>
                                        <th>Classe</th>
                                        <th>Étape</th>
                                        <th>Paiement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inscriptions as $inscription)
                                    <tr>
                                        <td><strong>{{ $inscription->etudiant->matricule }}</strong></td>
                                        <td>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</td>
                                        <td>{{ $inscription->filiere->nom }}</td>
                                        <td>{{ $inscription->niveau->nom }}</td>
                                        <td>{{ $inscription->classe->nom }}</td>
                                        <td>
                                            @switch($inscription->workflow_step)
                                                @case('prospect')
                                                    <span class="badge bg-secondary">Prospect</span>
                                                    @break
                                                @case('documents_complets')
                                                    <span class="badge bg-info">Documents complets</span>
                                                    @break
                                                @case('en_validation')
                                                    <span class="badge bg-warning">En validation</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-light text-dark">{{ $inscription->workflow_step }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($inscription->paiements->count() > 0)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Payé
                                                </span>
                                                <small class="d-block text-muted mt-1">
                                                    {{ number_format($inscription->paiements->sum('montant'), 0, ',', ' ') }} F
                                                </small>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Non payé
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" 
                                                   class="btn btn-sm btn-outline-info" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if($inscription->paiements->count() == 0)
                                                    <button class="btn btn-sm btn-outline-warning" 
                                                            onclick="openPaymentModal({{ $inscription->id }})"
                                                            title="Associer un paiement">
                                                        <i class="fas fa-credit-card"></i>
                                                    </button>
                                                @endif
                                                
                                                @if($inscription->paiement_validation_id)
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="openValidationModal({{ $inscription->id }})"
                                                            title="Valider définitivement">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $inscriptions->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-inbox fa-3x text-muted"></i>
                            </div>
                            <h4>Aucune inscription trouvée</h4>
                            <p class="text-muted">Aucune inscription en attente ne correspond aux filtres appliqués.</p>
                            <a href="{{ route('esbtp.inscriptions.administration') }}" class="btn-acasi primary">
                                <i class="fas fa-refresh"></i>Réinitialiser les filtres
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour associer un paiement -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="fas fa-credit-card me-2"></i>Associer un paiement à l'inscription
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="montant" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Montant payé <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="montant" name="montant" min="0" step="0.01" required placeholder="Entrez le montant...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fee_category_id" class="form-label">
                                    <i class="fas fa-tags me-1"></i>Catégorie de frais <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="fee_category_id" name="fee_category_id" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    @if(isset($categoriesfrais))
                                        @foreach($categoriesfrais as $categorie)
                                            <option value="{{ $categorie->id }}">{{ $categorie->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mode_paiement" class="form-label">
                                    <i class="fas fa-credit-card me-1"></i>Mode de paiement <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                                    <option value="">Sélectionnez un mode</option>
                                    <option value="especes">Espèces</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reference_paiement" class="form-label">
                                    <i class="fas fa-hashtag me-1"></i>Référence du paiement
                                </label>
                                <input type="text" class="form-control" id="reference_paiement" name="reference_paiement" placeholder="Numéro de chèque, référence virement...">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_paiement" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Date du paiement <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="observations" class="form-label">
                                    <i class="fas fa-comment me-1"></i>Observations
                                </label>
                                <textarea class="form-control" id="observations" name="observations" rows="3" placeholder="Commentaires sur le paiement..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Associer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour validation définitive -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Validation définitive de l'inscription
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="validationForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Cette action va convertir le prospect en étudiant et activer son compte utilisateur.
                    </div>
                    <div class="mb-3">
                        <label for="validation_observations" class="form-label">Observations</label>
                        <textarea class="form-control" id="validation_observations" name="observations" rows="3" placeholder="Commentaires sur la validation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i>Valider définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openPaymentModal(inscriptionId) {
        console.log('openPaymentModal called with ID:', inscriptionId);
        
        const form = document.getElementById('paymentForm');
        const modalElement = document.getElementById('paymentModal');
        
        if (!form) {
            console.error('Form paymentForm not found');
            return;
        }
        
        if (!modalElement) {
            console.error('Modal paymentModal not found');
            return;
        }
        
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded');
            alert('Erreur: Bootstrap n\'est pas chargé. Veuillez recharger la page.');
            return;
        }
        
        // Définir l'action correcte du formulaire
        form.action = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;
        
        // Réinitialiser le formulaire
        form.reset();
        const dateInput = document.getElementById('date_paiement');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        
        // Ouvrir le modal
        try {
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
            console.log('Modal should be open now');
        } catch (error) {
            console.error('Error opening modal:', error);
        }
    }

    function openValidationModal(inscriptionId) {
        const form = document.getElementById('validationForm');
        form.action = `/esbtp/inscriptions/${inscriptionId}/valider-definitivement`;
        
        // Réinitialiser le formulaire
        form.reset();
        
        // Ouvrir le modal
        const modal = new bootstrap.Modal(document.getElementById('validationModal'));
        modal.show();
    }
</script>
@endsection