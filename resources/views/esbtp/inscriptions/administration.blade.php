@extends('layouts.app')

@section('title', 'Administration des Inscriptions')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Administration des Inscriptions</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left fa-sm"></i> Retour aux inscriptions
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total en attente
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_en_attente'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Avec paiement
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['avec_paiement'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Sans paiement
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['sans_paiement'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Prospects
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['prospects'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres de recherche -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrer les inscriptions en attente</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('esbtp.inscriptions.administration') }}">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search">Recherche par nom ou matricule</label>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="filiere">Filière</label>
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
                        <label for="niveau">Niveau d'études</label>
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
                        <label for="workflow_step">Étape du workflow</label>
                        <select class="form-select" id="workflow_step" name="workflow_step">
                            <option value="">Toutes les étapes</option>
                            <option value="prospect" {{ request('workflow_step') == 'prospect' ? 'selected' : '' }}>Prospect</option>
                            <option value="documents_complets" {{ request('workflow_step') == 'documents_complets' ? 'selected' : '' }}>Documents complets</option>
                            <option value="en_validation" {{ request('workflow_step') == 'en_validation' ? 'selected' : '' }}>En validation</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="has_payment">Statut paiement</label>
                        <select class="form-select" id="has_payment" name="has_payment">
                            <option value="">Tous</option>
                            <option value="yes" {{ request('has_payment') == 'yes' ? 'selected' : '' }}>Avec paiement</option>
                            <option value="no" {{ request('has_payment') == 'no' ? 'selected' : '' }}>Sans paiement</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des inscriptions -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Inscriptions en attente de validation ({{ $inscriptions->total() }})
            </h6>
        </div>
        <div class="card-body">
            @if($inscriptions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
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
                                <td>{{ $inscription->etudiant->matricule }}</td>
                                <td>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</td>
                                <td>{{ $inscription->filiere->nom }}</td>
                                <td>{{ $inscription->niveau->nom }}</td>
                                <td>{{ $inscription->classe->nom }}</td>
                                <td>
                                    @switch($inscription->workflow_step)
                                        @case('prospect')
                                            <span class="badge badge-secondary">Prospect</span>
                                            @break
                                        @case('documents_complets')
                                            <span class="badge badge-info">Documents complets</span>
                                            @break
                                        @case('en_validation')
                                            <span class="badge badge-warning">En validation</span>
                                            @break
                                        @default
                                            <span class="badge badge-light">{{ $inscription->workflow_step }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($inscription->paiements->count() > 0)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Payé
                                        </span>
                                        <small class="d-block text-muted">
                                            {{ number_format($inscription->paiements->sum('montant'), 0, ',', ' ') }} F
                                        </small>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times"></i> Non payé
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" 
                                           class="btn btn-sm btn-info" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($inscription->paiements->count() == 0)
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="openPaymentModal({{ $inscription->id }})"
                                                    title="Associer un paiement">
                                                <i class="fas fa-credit-card"></i>
                                            </button>
                                        @endif
                                        
                                        @if($inscription->paiement_validation_id)
                                            <button class="btn btn-sm btn-success" 
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
                <div class="d-flex justify-content-center">
                    {{ $inscriptions->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">Aucune inscription en attente trouvée avec les filtres appliqués.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal pour associer un paiement -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Associer un paiement à l'inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="montant" class="form-label">Montant payé <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="montant" name="montant" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fee_category_id" class="form-label">Catégorie de frais <span class="text-danger">*</span></label>
                                <select class="form-select" id="fee_category_id" name="fee_category_id" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    @foreach($categoriesfrais as $categorie)
                                        <option value="{{ $categorie->id }}">{{ $categorie->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mode_paiement" class="form-label">Mode de paiement <span class="text-danger">*</span></label>
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
                                <label for="reference_paiement" class="form-label">Référence du paiement</label>
                                <input type="text" class="form-control" id="reference_paiement" name="reference_paiement" placeholder="Numéro de chèque, référence virement...">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_paiement" class="form-label">Date du paiement <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="observations" class="form-label">Observations</label>
                                <textarea class="form-control" id="observations" name="observations" rows="3" placeholder="Commentaires sur le paiement..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Associer le paiement</button>
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
                <h5 class="modal-title" id="validationModalLabel">Validation définitive de l'inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="validationForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Cette action va convertir le prospect en étudiant et activer son compte utilisateur.
                    </div>
                    <div class="mb-3">
                        <label for="validation_observations" class="form-label">Observations</label>
                        <textarea class="form-control" id="validation_observations" name="observations" rows="3" placeholder="Commentaires sur la validation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Valider définitivement</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openPaymentModal(inscriptionId) {
        const form = document.getElementById('paymentForm');
        form.action = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;
        
        // Réinitialiser le formulaire
        form.reset();
        document.getElementById('date_paiement').value = new Date().toISOString().split('T')[0];
        
        // Ouvrir le modal
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
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