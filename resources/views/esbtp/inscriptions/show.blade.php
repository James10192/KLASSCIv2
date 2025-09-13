@extends('layouts.app')

@section('title', 'Détails de l\'inscription - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
<style>
/* === CORRECTION SPÉCIFIQUE MODALS INSCRIPTIONS SHOW === */

/* Forcer tous les modals de cette page au premier plan */
#paymentModal.modal,
#validationModal.modal, 
#subscriptionModal.modal,
#transferModal.modal {
    z-index: 9999 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#paymentModal .modal-dialog,
#validationModal .modal-dialog,
#subscriptionModal .modal-dialog,
#transferModal .modal-dialog {
    z-index: 10000 !important;
    position: relative !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#paymentModal .modal-content,
#validationModal .modal-content,
#subscriptionModal .modal-content,
#transferModal .modal-content {
    z-index: 10001 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
}

/* Désactiver animations sur modals show */
#paymentModal.modal.fade .modal-dialog,
#validationModal.modal.fade .modal-dialog,
#subscriptionModal.modal.fade .modal-dialog,
#transferModal.modal.fade .modal-dialog {
    transition: none !important;
    transform: none !important;
}

/* États d'affichage forcés */
#paymentModal.modal.show,
#validationModal.modal.show,
#subscriptionModal.modal.show,
#transferModal.modal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Anti-curseur erratique quand modals ouverts */
body.modal-open * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Empêcher mouvements de curseur */
body.modal-open .btn,
body.modal-open .card,
body.modal-open .form-control {
    animation: none !important;
    transition: none !important;
}

body.modal-open .btn:hover,
body.modal-open .card:hover {
    transform: none !important;
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</h1>
                <p class="header-subtitle">Détails de l'inscription - Matricule: {{ $inscription->etudiant->matricule }}</p>
            </div>
            <div class="header-actions">
                @can('inscriptions.validate')
                    <a href="{{ route('esbtp.inscriptions.administration') }}" class="btn-acasi primary me-2">
                        <i class="fas fa-cog"></i>Administration
                    </a>
                    
                    @if($inscription->status === 'en_attente' && !$inscription->paiement_validation_id)
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="preparePaymentModal({{ $inscription->id }})">
                            <i class="fas fa-credit-card"></i>Valider avec paiement
                        </button>
                    @endif
                    
                    @if($inscription->paiement_validation_id && $inscription->workflow_step === 'en_validation')
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#validationModal" onclick="openValidationModal({{ $inscription->id }})">
                            <i class="fas fa-check"></i>Valider définitivement
                        </button>
                    @endif
                @endcan
                
                @can('inscriptions.edit')
                    <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn-acasi secondary me-2">
                        <i class="fas fa-edit"></i>Modifier
                    </a>
                @endcan
                
                <a href="{{ route('esbtp.etudiants.show', $inscription->etudiant) }}" class="btn-acasi primary me-2">
                    <i class="fas fa-user"></i>Voir l'étudiant
                </a>
                
                <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="p-lg">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('account_info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading"><i class="fas fa-user-check me-2"></i>Informations de connexion générées</h6>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nom d'utilisateur:</strong> {{ session('account_info')['username'] }}</p>
                            <p class="mb-1"><strong>Rôle:</strong> {{ session('account_info')['role'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Mot de passe temporaire:</strong> <span class="badge bg-light text-dark p-2 font-monospace">{{ session('account_info')['password'] }}</span></p>
                            <p class="mb-0 text-muted"><small>Veuillez communiquer ces informations à l'étudiant. Le mot de passe devra être changé à la première connexion.</small></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <!-- Informations étudiant -->
                    <div class="card-moderne">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-user"></i>Informations de l'étudiant
                            </div>
                            <div class="text-center mb-4">
                                @if($inscription->etudiant->photo_url)
                                    <img src="{{ $inscription->etudiant->photo_url }}" alt="Photo de profil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 150px; height: 150px;">
                                        <i class="fas fa-user fa-5x text-secondary"></i>
                                    </div>
                                @endif
                                <h5 class="mt-3">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</h5>
                                <p class="text-muted">
                                    Matricule: <strong>{{ $inscription->etudiant->matricule }}</strong>
                                </p>
                                <div class="mb-2">
                                    @if($inscription->etudiant->statut == 'actif')
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-danger">Inactif</span>
                                    @endif
                                </div>
                            </div>

                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 40%">Genre</th>
                                    <td>{{ $inscription->etudiant->sexe == 'M' ? 'Masculin' : 'Féminin' }}</td>
                                </tr>
                                <tr>
                                    <th>Date de naissance</th>
                                    <td>{{ \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d-m-Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Lieu de naissance</th>
                                    <td>{{ $inscription->etudiant->lieu_naissance ?: 'Non renseigné' }}</td>
                                </tr>
                                <tr>
                                    <th>Téléphone</th>
                                    <td>{{ $inscription->etudiant->telephone }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>{{ $inscription->etudiant->email ?: 'Non renseigné' }}</td>
                                </tr>
                                <tr>
                                    <th>Ville de résidence</th>
                                    <td>{{ $inscription->etudiant->ville ?: 'Non renseignée' }}</td>
                                </tr>
                                <tr>
                                    <th>Commune de résidence</th>
                                    <td>{{ $inscription->etudiant->commune ?: 'Non renseignée' }}</td>
                                </tr>
                                <tr>
                                    <th>Adresse</th>
                                    <td>{{ $inscription->etudiant->adresse ?: 'Non renseignée' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Statut de l'inscription -->
                    <div class="card-moderne mt-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-chart-line"></i>Statut de l'inscription
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Étape du workflow:</span>
                                    @switch($inscription->workflow_step)
                                        @case('prospect')
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-user-plus me-1"></i>Prospect
                                            </span>
                                            @break
                                        @case('documents_complets')
                                            <span class="badge bg-info">
                                                <i class="fas fa-file-check me-1"></i>Documents complets
                                            </span>
                                            @break
                                        @case('en_validation')
                                            <span class="badge bg-warning">
                                                <i class="fas fa-hourglass-half me-1"></i>En validation
                                            </span>
                                            @break
                                        @case('valide')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Validé
                                            </span>
                                            @break
                                        @case('etudiant_cree')
                                            <span class="badge bg-primary">
                                                <i class="fas fa-graduation-cap me-1"></i>Étudiant créé
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">{{ $inscription->workflow_step }}</span>
                                    @endswitch
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Statut:</span>
                                    <span class="badge bg-{{ $inscription->status === 'active' ? 'success' : ($inscription->status === 'en_attente' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($inscription->status) }}
                                    </span>
                                </div>
                            </div>

                            @php
                                $steps = [
                                    'prospect' => ['label' => 'Prospect', 'icon' => 'fas fa-user-plus', 'color' => 'secondary'],
                                    'documents_complets' => ['label' => 'Documents complets', 'icon' => 'fas fa-file-check', 'color' => 'info'],
                                    'en_validation' => ['label' => 'En validation', 'icon' => 'fas fa-hourglass-half', 'color' => 'warning'],
                                    'valide' => ['label' => 'Validé', 'icon' => 'fas fa-check', 'color' => 'success'],
                                    'etudiant_cree' => ['label' => 'Étudiant créé', 'icon' => 'fas fa-graduation-cap', 'color' => 'primary']
                                ];
                                $stepKeys = array_keys($steps);
                                $currentStepIndex = array_search($inscription->workflow_step, $stepKeys);
                                $progress = $currentStepIndex !== false ? (($currentStepIndex + 1) / count($stepKeys)) * 100 : 0;
                            @endphp
                            
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped" role="progressbar" style="width: {{ $progress }}%">
                                    {{ round($progress) }}%
                                </div>
                            </div>

                            <!-- Étapes détaillées du workflow -->
                            <div class="workflow-steps">
                                <div class="row">
                                    @foreach($steps as $stepKey => $stepInfo)
                                        @php
                                            $stepIndex = array_search($stepKey, $stepKeys);
                                            $isCompleted = $stepIndex < $currentStepIndex;
                                            $isCurrent = $stepKey === $inscription->workflow_step;
                                            $isPending = $stepIndex > $currentStepIndex;
                                        @endphp
                                        <div class="col">
                                            <div class="text-center">
                                                <div class="workflow-step-icon mb-2">
                                                    @if($isCompleted)
                                                        <div class="badge bg-success rounded-circle p-3">
                                                            <i class="fas fa-check fa-lg"></i>
                                                        </div>
                                                    @elseif($isCurrent)
                                                        <div class="badge bg-{{ $stepInfo['color'] }} rounded-circle p-3">
                                                            <i class="{{ $stepInfo['icon'] }} fa-lg"></i>
                                                        </div>
                                                    @else
                                                        <div class="badge bg-light text-muted rounded-circle p-3">
                                                            <i class="{{ $stepInfo['icon'] }} fa-lg"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="workflow-step-label">
                                                    <small class="text-{{ $isCompleted ? 'success' : ($isCurrent ? $stepInfo['color'] : 'muted') }}">
                                                        <strong>{{ $stepInfo['label'] }}</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if($inscription->paiement_validation_id)
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Paiement associé
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Aucun paiement associé
                                </div>
                            @endif
                            
                            @if($inscription->date_validation)
                                <p><strong>Date de validation:</strong><br>
                                {{ \Carbon\Carbon::parse($inscription->date_validation)->format('d/m/Y à H:i') }}</p>
                            @endif
                            
                            @if($inscription->validated_by)
                                <p><strong>Validé par:</strong><br>
                                {{ \App\Models\User::find($inscription->validated_by)->name ?? 'Utilisateur inconnu' }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Informations de l'inscription -->
                    <div class="card-moderne mb-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-graduation-cap"></i>Informations académiques
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 40%">Filière</th>
                                            <td>{{ $inscription->filiere->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Niveau</th>
                                            <td>{{ $inscription->niveau->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Classe</th>
                                            <td>{{ $inscription->classe->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Année universitaire</th>
                                            <td>{{ $inscription->anneeUniversitaire->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Statut d'affectation</th>
                                            <td>
                                                @if($inscription->affectation_status)
                                                    @switch($inscription->affectation_status)
                                                        @case('affecté')
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Affecté
                                                            </span>
                                                            @break
                                                        @case('réaffecté')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exchange-alt me-1"></i>Réaffecté
                                                            </span>
                                                            @break
                                                        @case('non_affecté')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Non affecté
                                                            </span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ $inscription->affectation_status }}</span>
                                                    @endswitch
                                                @else
                                                    <span class="text-muted">Non renseigné</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 40%">Date d'inscription</th>
                                            <td>{{ $inscription->date_inscription }}</td>
                                        </tr>
                                        <tr>
                                            <th>Type d'inscription</th>
                                            <td>{{ $inscription->type_inscription }}</td>
                                        </tr>
                                        <tr>
                                            <th>Observations</th>
                                            <td>{{ $inscription->observations ?: 'Aucune' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @php
                                $totalAttendu = collect($feeCategoriesWithRules)->where('is_configured', true)->sum('montant_attendu');
                                $totalPaye = collect($feeCategoriesWithRules)->sum('total_paye');
                                $soldeGlobal = $totalAttendu - $totalPaye;
                                $obligatoiresConfigures = collect($feeCategoriesWithRules)->where('is_mandatory', true)->where('is_configured', true)->count();
                                $obligatoiresTotal = collect($feeCategoriesWithRules)->where('is_mandatory', true)->count();
                            @endphp
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-{{ $soldeGlobal <= 0 ? 'success' : 'warning' }}">
                                        <h6><i class="fas fa-wallet me-2"></i>Résumé Financier</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Total attendu:</strong><br>
                                                <span class="fs-5">{{ number_format($totalAttendu, 0, ',', ' ') }} FCFA</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Total payé:</strong><br>
                                                <span class="fs-5 text-success">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Solde:</strong><br>
                                                <span class="fs-5 text-{{ $soldeGlobal <= 0 ? 'success' : 'danger' }}">{{ number_format($soldeGlobal, 0, ',', ' ') }} FCFA</span>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted">{{ $obligatoiresConfigures }}/{{ $obligatoiresTotal }} frais obligatoires configurés</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parents / Tuteurs -->
                    <div class="card-moderne mb-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-users"></i>Parents / Tuteurs
                            </div>
                            @if($inscription->etudiant->parents->count() > 0)
                                <div class="accordion" id="accordionParents">
                                    @foreach($inscription->etudiant->parents as $index => $parent)
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading{{ $index }}">
                                                <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                                    {{ $parent->nom }} {{ $parent->prenoms }} - {{ $parent->pivot->relation }}
                                                    @if($parent->pivot->is_tuteur)
                                                        <span class="badge bg-warning ms-2">Tuteur principal</span>
                                                    @endif
                                                </button>
                                            </h2>
                                            <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" aria-labelledby="heading{{ $index }}" data-bs-parent="#accordionParents">
                                                <div class="accordion-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <table class="table table-bordered">
                                                                <tr>
                                                                    <th style="width: 40%">Nom complet</th>
                                                                    <td>{{ $parent->nom }} {{ $parent->prenoms }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Relation</th>
                                                                    <td>{{ $parent->pivot->relation }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Téléphone</th>
                                                                    <td>{{ $parent->telephone ?: 'Non renseigné' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Email</th>
                                                                    <td>{{ $parent->email ?: 'Non renseigné' }}</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <table class="table table-bordered">
                                                                <tr>
                                                                    <th style="width: 40%">Profession</th>
                                                                    <td>{{ $parent->profession ?: 'Non renseignée' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Adresse</th>
                                                                    <td>{{ $parent->adresse ?: 'Non renseignée' }}</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun parent ou tuteur n'est associé à cet étudiant.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Situation financière détaillée -->
                    <div class="card-moderne mb-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-chart-line"></i>Situation Financière Détaillée
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Type</th>
                                            <th>Montant Attendu</th>
                                            <th>Montant Payé</th>
                                            <th>Solde</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($feeCategoriesWithRules as $item)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item['category']->icon)
                                                            <i class="{{ $item['category']->icon }} me-2"></i>
                                                        @endif
                                                        <div>
                                                            <strong>{{ $item['category']->name }}</strong>
                                                            @if($item['category']->description)
                                                                <br><small class="text-muted">{{ $item['category']->description }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($item['is_mandatory'])
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                                                        </span>
                                                    @else
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-star me-1"></i>Optionnel
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['is_configured'])
                                                        <strong>{{ number_format($item['montant_attendu'], 0, ',', ' ') }} FCFA</strong>
                                                    @else
                                                        <span class="badge bg-warning">Non configuré</span>
                                                        <br><small class="text-muted">Défaut: {{ number_format($item['category']->default_amount, 0, ',', ' ') }} FCFA</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['total_paye'] > 0)
                                                        <strong class="text-success">{{ number_format($item['total_paye'], 0, ',', ' ') }} FCFA</strong>
                                                    @else
                                                        <span class="text-muted">0 FCFA</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['solde'] > 0)
                                                        <strong class="text-danger">{{ number_format($item['solde'], 0, ',', ' ') }} FCFA</strong>
                                                    @elseif($item['solde'] < 0)
                                                        <strong class="text-success">{{ number_format(abs($item['solde']), 0, ',', ' ') }} FCFA</strong>
                                                        <br><small class="text-success">
                                                            <i class="fas fa-arrow-up me-1"></i>Trop-perçu
                                                            <button class="btn btn-sm btn-outline-primary ms-1" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#transferModal" 
                                                                    onclick="prepareTransferModal({{ $inscription->id }}, {{ $item['category']->id }}, {{ abs($item['solde']) }}, {{ json_encode($item['category']->name) }})"
                                                                    title="Transférer vers un autre frais">
                                                                <i class="fas fa-exchange-alt"></i>
                                                            </button>
                                                        </small>
                                                    @else
                                                        <span class="badge bg-success">Soldé</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @switch($item['status'])
                                                        @case('paid')
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Payé
                                                            </span>
                                                            @break
                                                        @case('partial')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-clock me-1"></i>Partiel
                                                            </span>
                                                            @break
                                                        @case('unpaid')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i>Impayé
                                                            </span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ $item['status'] }}</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        @if($item['is_configured'] && $item['solde'] > 0)
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="preparePaymentModalForCategory({{ $inscription->id }}, {{ $item['category']->id }})" title="Effectuer un paiement">
                                                                <i class="fas fa-credit-card"></i>
                                                            </button>
                                                        @endif
                                                        @if(!$item['is_configured'])
                                                            <a href="{{ route('esbtp.frais.configure') }}?filiere_id={{ $inscription->filiere_id }}&niveau_id={{ $inscription->niveau_id }}" class="btn btn-sm btn-warning" title="Configurer ce frais">
                                                                <i class="fas fa-cogs"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Aucune catégorie de frais configurée pour cette inscription.
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Frais optionnels disponibles -->
                    @if(isset($availableOptionalCategories) && $availableOptionalCategories->count() > 0)
                    <div class="card-moderne mb-4">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-plus-circle"></i>Frais Optionnels Disponibles
                                <button type="button" class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#subscriptionModal">
                                    <i class="fas fa-plus me-1"></i>Souscrire l'étudiant à un frais
                                </button>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>{{ $availableOptionalCategories->count() }} frais optionnels</strong> sont disponibles pour cette filière/niveau.
                                L'administration peut souscrire l'étudiant aux services souhaités.
                            </div>
                            <div class="row">
                                @foreach($availableOptionalCategories as $category)
                                    <div class="col-md-4 mb-3">
                                        <div class="card border">
                                            <div class="card-body text-center">
                                                @if($category->icon)
                                                    <i class="{{ $category->icon }} fa-2x mb-2 text-primary"></i>
                                                @else
                                                    <i class="fas fa-star fa-2x mb-2 text-primary"></i>
                                                @endif
                                                <h6 class="card-title">{{ $category->name }}</h6>
                                                @if($category->description)
                                                    <p class="card-text small text-muted">{{ $category->description }}</p>
                                                @endif
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-info">Optionnel</span>
                                                    <strong class="text-primary">{{ number_format($category->default_amount, 0, ',', ' ') }} FCFA</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Paiements liés à l'inscription -->
                    <div class="card-moderne">
                        <div class="p-lg">
                            <div class="section-title mb-md">
                                <i class="fas fa-money-bill-wave"></i>Paiements liés à cette inscription
                            </div>
                            @php
                                $allPayments = collect();
                                if($inscription->paiements && $inscription->paiements->count()) {
                                    $allPayments = $allPayments->merge($inscription->paiements);
                                }
                                if($inscription->payments && $inscription->payments->count()) {
                                    $allPayments = $allPayments->merge($inscription->payments);
                                }
                            @endphp
                            
                            @if($allPayments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Mode</th>
                                                <th>Référence</th>
                                                <th>Statut</th>
                                                <th>Commentaire</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allPayments as $payment)
                                                <tr>
                                                    <td>
                                                        @if(isset($payment->date_paiement))
                                                            {{ \Carbon\Carbon::parse($payment->date_paiement)->format('d/m/Y') }}
                                                        @elseif(isset($payment->payment_date))
                                                            {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '' }}
                                                        @else
                                                            {{ $payment->date ?? '-' }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <strong>
                                                            @if(isset($payment->montant))
                                                                {{ number_format($payment->montant, 0, ',', ' ') }} FCFA
                                                            @elseif(isset($payment->amount))
                                                                {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
                                                            @endif
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        @if(isset($payment->mode_paiement))
                                                            {{ ucfirst($payment->mode_paiement) }}
                                                        @elseif(isset($payment->payment_method))
                                                            {{ ucfirst($payment->payment_method) }}
                                                        @else
                                                            {{ $payment->methode ?? '-' }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(isset($payment->reference_paiement))
                                                            {{ $payment->reference_paiement ?? '-' }}
                                                        @elseif(isset($payment->reference_number))
                                                            {{ $payment->reference_number ?? '-' }}
                                                        @else
                                                            {{ $payment->reference ?? '-' }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(isset($payment->status))
                                                            <span class="badge bg-{{ $payment->status === 'validated' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                                                                {{ ucfirst($payment->status) }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">Validé</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(isset($payment->observations))
                                                            {{ $payment->observations }}
                                                        @else
                                                            {{ $payment->commentaire ?? '-' }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <th>Total</th>
                                                <th>
                                                    @php
                                                        $total = 0;
                                                        foreach($allPayments as $payment) {
                                                            $total += $payment->montant ?? $payment->amount ?? 0;
                                                        }
                                                    @endphp
                                                    <strong>{{ number_format($total, 0, ',', ' ') }} FCFA</strong>
                                                </th>
                                                <th colspan="4"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun paiement enregistré pour cette inscription.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour associer un paiement - Structure Bootstrap simple -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Associer un paiement à l'inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                <input type="hidden" name="_action" value="valider-avec-paiement">
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
                                    @if(isset($categoriesfrais))
                                        @foreach($categoriesfrais as $categorie)
                                            <option value="{{ $categorie->id }}" data-default-amount="{{ $categorie->default_amount }}">
                                                {{ $categorie->name }}
                                                @if($categorie->is_mandatory)
                                                    (Obligatoire)
                                                @else
                                                    (Optionnel)
                                                @endif
                                            </option>
                                        @endforeach
                                    @endif
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

<!-- Modal pour validation définitive - Structure Bootstrap simple -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validationModalLabel">Validation définitive de l'inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="validationForm" method="POST">
                @csrf
                <input type="hidden" name="_action" value="valider-definitivement">
                <div class="modal-body">
                    <div class="alert alert-info">
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

<!-- Modal pour souscription à un frais optionnel -->
<div class="modal fade" id="subscriptionModal" tabindex="-1" aria-labelledby="subscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subscriptionModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Souscrire l'étudiant à un frais optionnel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="subscriptionForm" method="POST" action="{{ route('esbtp.inscriptions.subscribe-optional-fee', $inscription->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Sélectionnez un frais optionnel pour y souscrire cet étudiant.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="optional_category_id" class="form-label">Frais optionnel <span class="text-danger">*</span></label>
                                <select class="form-select" id="optional_category_id" name="category_id" required>
                                    <option value="">Sélectionnez un frais</option>
                                    @if(isset($availableOptionalCategories))
                                        @foreach($availableOptionalCategories as $category)
                                            <option value="{{ $category->id }}" data-default-amount="{{ $category->default_amount }}">
                                                {{ $category->name }} - {{ number_format($category->default_amount, 0, ',', ' ') }} FCFA
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subscription_amount" class="form-label">Montant <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="subscription_amount" name="amount" min="0" step="0.01" required readonly>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="subscription_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="subscription_notes" name="notes" rows="3" placeholder="Commentaires sur la souscription..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Souscrire l'étudiant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour transfert de trop-perçu - Design moderne avec support multi-destinations -->
<div class="modal fade modal-moderne" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transferModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Gestion des Trop-perçus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transferForm" method="POST" action="{{ route('esbtp.inscriptions.transfer-overpayment', $inscription->id) }}">
                @csrf
                <input type="hidden" id="transfer_source_category" name="source_category_id">
                <input type="hidden" id="transfer_amount_hidden" name="amount">
                
                <div class="modal-body">
                    <!-- Alerte d'information avec design moderne -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Vous allez transférer un trop-perçu de <strong id="transfer_source_name">-</strong> vers un ou plusieurs frais.
                    </div>
                    
                    <!-- Section Source avec design moderne -->
                    <div class="section-card mb-4">
                        <div class="section-card-header" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); border-left: 4px solid var(--success);">
                            <h6 class="section-card-title mb-0">
                                <i class="fas fa-arrow-up me-2 text-success"></i>Source (Trop-perçu)
                            </h6>
                        </div>
                        <div class="section-card-body">
                            <div class="form-grid-2">
                                <div class="form-group-moderne">
                                    <label class="form-label-moderne">Frais source</label>
                                    <div class="form-control-moderne" style="background: var(--background); border: none; padding: 12px 16px;">
                                        <strong id="transfer_source_display">-</strong>
                                    </div>
                                </div>
                                <div class="form-group-moderne">
                                    <label class="form-label-moderne">Montant disponible</label>
                                    <div class="form-control-moderne" style="background: var(--background); border: none; padding: 12px 16px; color: var(--success); font-weight: 600;">
                                        <span id="transfer_amount_display">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Destinations multiples -->
                    <div class="section-card mb-4">
                        <div class="section-card-header" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.05)); border-left: 4px solid var(--primary);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="section-card-title mb-0">
                                    <i class="fas fa-arrow-down me-2 text-primary"></i>Destinations
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add_destination_btn">
                                    <i class="fas fa-plus me-1"></i>Ajouter une destination
                                </button>
                            </div>
                        </div>
                        <div class="section-card-body">
                            <div id="destinations_container">
                                <!-- Les destinations seront ajoutées ici dynamiquement -->
                            </div>
                            
                            <!-- Résumé des transferts -->
                            <div id="transfer_summary" class="alert alert-secondary d-none mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Total à transférer :</strong>
                                        <span id="total_transfer_amount" class="text-primary">0 FCFA</span>
                                    </div>
                                    <div>
                                        <strong>Restant disponible :</strong>
                                        <span id="remaining_amount" class="text-success">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Commentaire -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <h6 class="section-card-title mb-0">
                                <i class="fas fa-comment me-2"></i>Commentaire (optionnel)
                            </h6>
                        </div>
                        <div class="section-card-body">
                            <div class="form-group-moderne">
                                <textarea class="form-control-moderne" 
                                          id="transfer_comment" 
                                          name="comment" 
                                          rows="3" 
                                          placeholder="Motif du transfert..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" id="transfer_submit_btn" disabled>
                        <i class="fas fa-exchange-alt me-1"></i>Effectuer le transfert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

<script>
    console.log('🚀 SCRIPT CHARGÉ - Fonctions modales en cours de définition...');
    
    // Logs simples pour debug - Style class-selector  
    function logModal(modalId, message) {
        console.log(`📝 Modal ${modalId}: ${message}`);
    }

    // Fonction simple pour préparer les modals - comme class-selector
    function setupModalBasic(modalId) {
        console.log(`📝 Configuration basique pour modal ${modalId}`);
        // Laisser Bootstrap gérer tout le reste
    }

    // Fonctions globales simples pour les boutons onclick - Style class-selector
    function preparePaymentModal(inscriptionId) {
        console.log('🎯 preparePaymentModal appelé avec ID:', inscriptionId);
        
        const form = document.getElementById('paymentForm');
        const correctAction = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;
        form.action = correctAction;
        
        // Reset le formulaire
        form.reset();
        
        // Remettre la date du jour
        const dateInput = document.getElementById('date_paiement');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        
        console.log('✅ Formulaire de paiement préparé, action:', form.action);
    }

    function openValidationModal(inscriptionId) {
        console.log('🎯 openValidationModal appelé avec ID:', inscriptionId);
        
        const form = document.getElementById('validationForm');
        const correctAction = `/esbtp/inscriptions/${inscriptionId}/valider-definitivement`;
        form.action = correctAction;
        form.reset();
        
        console.log('✅ Formulaire de validation préparé, action:', form.action);
    }

    function preparePaymentModalForCategory(inscriptionId, categoryId) {
        console.log('🎯 preparePaymentModalForCategory appelé avec ID:', inscriptionId, 'Category:', categoryId);
        
        preparePaymentModal(inscriptionId);
        
        // Attendre que le modal soit prêt
        setTimeout(() => {
            const categorySelect = document.getElementById('fee_category_id');
            if (categorySelect && categoryId) {
                categorySelect.value = categoryId;
                const event = new Event('change');
                categorySelect.dispatchEvent(event);
                console.log('✅ Catégorie pré-sélectionnée:', categoryId);
            }
        }, 100);
    }

    // Variables globales pour la gestion multi-destinations
    let destinationCounter = 0;
    let availableFees = @json(collect($feeCategoriesWithRules)->filter(function($item) { return $item['is_configured'] && $item['solde'] != 0; })->values());
    
    // Fonction pour préparer le modal de transfert de trop-perçu - Améliorée
    // Variables globales pour stocker les données de transfert
    let transferData = {
        inscriptionId: null,
        sourceCategoryId: null,
        availableAmount: null,
        sourceCategoryName: null
    };

    function prepareTransferModal(inscriptionId, sourceCategoryId, availableAmount, sourceCategoryName) {
        console.log('🔄 prepareTransferModal appelé (multi-destinations):', {
            inscriptionId, sourceCategoryId, availableAmount, sourceCategoryName
        });
        
        // Debug : vérifier les types de données
        console.log('📊 Types des données reçues:', {
            inscriptionId: typeof inscriptionId,
            sourceCategoryId: typeof sourceCategoryId,
            availableAmount: typeof availableAmount,
            sourceCategoryName: typeof sourceCategoryName
        });
        
        // Stocker les données dans les variables globales
        transferData = {
            inscriptionId,
            sourceCategoryId,
            availableAmount,
            sourceCategoryName
        };
        
        console.log('📦 Données stockées dans transferData:', transferData);
        
        // Forcer l'application des données immédiatement
        applyTransferData();
        
        // Et aussi avec un délai pour être sûr
        setTimeout(applyTransferData, 50);
        setTimeout(applyTransferData, 200);
        setTimeout(applyTransferData, 500);
    }
    
    function applyTransferData() {
        console.log('🔧 applyTransferData appelé avec:', transferData);
        
        if (!transferData.sourceCategoryId) {
            console.warn('⚠️ Pas de données de transfert disponibles');
            return;
        }
        
        // Rechercher tous les éléments
        const elements = {
            sourceCategoryField: document.getElementById('transfer_source_category'),
            amountHiddenField: document.getElementById('transfer_amount_hidden'),
            sourceNameEl: document.getElementById('transfer_source_name'),
            sourceDisplayEl: document.getElementById('transfer_source_display'),
            amountDisplayEl: document.getElementById('transfer_amount_display'),
            container: document.getElementById('destinations_container'),
            commentField: document.getElementById('transfer_comment'),
            summaryDiv: document.getElementById('transfer_summary'),
            submitBtn: document.getElementById('transfer_submit_btn')
        };
        
        console.log('🔍 Éléments trouvés:', Object.keys(elements).filter(key => elements[key] !== null));
        console.log('❌ Éléments manquants:', Object.keys(elements).filter(key => elements[key] === null));
        
        // Appliquer les données aux champs cachés
        if (elements.sourceCategoryField) {
            elements.sourceCategoryField.value = transferData.sourceCategoryId;
            console.log('✅ Source category définie:', transferData.sourceCategoryId);
        }
        
        if (elements.amountHiddenField) {
            elements.amountHiddenField.value = transferData.availableAmount;
            console.log('✅ Amount défini:', transferData.availableAmount);
        }
        
        // Appliquer les données d'affichage
        if (elements.sourceNameEl) {
            elements.sourceNameEl.textContent = transferData.sourceCategoryName || 'Catégorie inconnue';
            console.log('✅ Source name affiché:', transferData.sourceCategoryName);
        }
        
        if (elements.sourceDisplayEl) {
            elements.sourceDisplayEl.textContent = transferData.sourceCategoryName || 'Catégorie inconnue';
            console.log('✅ Source display affiché:', transferData.sourceCategoryName);
        }
        
        if (elements.amountDisplayEl) {
            const formattedAmount = new Intl.NumberFormat('fr-FR').format(transferData.availableAmount || 0) + ' FCFA';
            elements.amountDisplayEl.textContent = formattedAmount;
            console.log('✅ Amount display affiché:', formattedAmount);
        }
        
        // Réinitialiser et configurer le conteneur des destinations
        if (elements.container) {
            elements.container.innerHTML = '';
            destinationCounter = 0;
            console.log('✅ Container des destinations réinitialisé');
            
            // Ajouter la première destination
            addDestinationRow(transferData.sourceCategoryId, transferData.availableAmount);
        }
        
        // Réinitialiser les autres champs
        if (elements.commentField) {
            elements.commentField.value = '';
        }
        
        if (elements.summaryDiv) {
            elements.summaryDiv.classList.add('d-none');
        }
        
        if (elements.submitBtn) {
            elements.submitBtn.disabled = true;
        }
        
        console.log('✅ Données de transfert appliquées avec succès');
    }
    
    // Fonction pour ajouter une ligne de destination
    function addDestinationRow(sourceCategoryId, totalAvailable) {
        destinationCounter++;
        const container = document.getElementById('destinations_container');
        
        const destinationHtml = `
            <div class="destination-row" data-destination-id="${destinationCounter}">
                <div class="card mb-3" style="border-left: 3px solid var(--primary);">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-bullseye me-2"></i>Destination ${destinationCounter}
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-destination-btn" 
                                onclick="removeDestination(${destinationCounter})" 
                                ${destinationCounter === 1 ? 'style="display: none;"' : ''}>
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="form-grid-2">
                            <div class="form-group-moderne">
                                <label class="form-label-moderne">Frais de destination</label>
                                <select class="form-select-moderne destination-select" 
                                        name="destinations[${destinationCounter}][category_id]" 
                                        data-destination-id="${destinationCounter}" required>
                                    <option value="">Sélectionner un frais...</option>
                                </select>
                            </div>
                            <div class="form-group-moderne">
                                <label class="form-label-moderne">Montant à transférer (FCFA)</label>
                                <input type="number" 
                                       class="form-control-moderne destination-amount" 
                                       name="destinations[${destinationCounter}][amount]" 
                                       data-destination-id="${destinationCounter}"
                                       step="1" 
                                       min="1"
                                       placeholder="Entrez le montant..." required>
                            </div>
                        </div>
                        
                        <div class="destination-info mt-3 d-none" id="destination_info_${destinationCounter}">
                            <div class="alert alert-secondary">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Solde actuel :</strong>
                                        <span class="destination-current-balance">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Après transfert :</strong>
                                        <span class="destination-after-transfer text-primary">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', destinationHtml);
        
        // Remplir les options pour cette destination
        populateDestinationOptions(destinationCounter, sourceCategoryId);
        
        // Attacher les événements
        attachDestinationEvents(destinationCounter);
        
        // Mettre à jour les boutons de suppression
        updateRemoveButtons();
    }
    
    // Fonction pour remplir les options de destination
    function populateDestinationOptions(destinationId, sourceCategoryId) {
        const select = document.querySelector(`select[data-destination-id="${destinationId}"]`);
        if (!select) return;
        
        // Vider les options existantes (sauf la première)
        select.innerHTML = '<option value="">Sélectionner un frais...</option>';
        
        // Obtenir les catégories déjà sélectionnées
        const selectedCategories = getSelectedCategories();
        
        // Ajouter les options disponibles
        availableFees.forEach(item => {
            // Exclure la catégorie source et les catégories déjà sélectionnées
            if (item.category.id != sourceCategoryId && !selectedCategories.includes(item.category.id.toString())) {
                const option = document.createElement('option');
                option.value = item.category.id;
                option.textContent = `${item.category.name}`;
                if (item.solde > 0) {
                    option.textContent += ` (Solde à payer: ${new Intl.NumberFormat('fr-FR').format(item.solde)} FCFA)`;
                } else if (item.solde < 0) {
                    option.textContent += ` (Trop-perçu: ${new Intl.NumberFormat('fr-FR').format(Math.abs(item.solde))} FCFA)`;
                }
                option.dataset.solde = item.solde;
                option.dataset.name = item.category.name;
                select.appendChild(option);
            }
        });
    }
    
    // Fonction pour obtenir les catégories déjà sélectionnées
    function getSelectedCategories() {
        const selected = [];
        document.querySelectorAll('.destination-select').forEach(select => {
            if (select.value) {
                selected.push(select.value);
            }
        });
        return selected;
    }
    
    // Fonction pour attacher les événements à une destination
    function attachDestinationEvents(destinationId) {
        const select = document.querySelector(`select[data-destination-id="${destinationId}"]`);
        const amountInput = document.querySelector(`input[data-destination-id="${destinationId}"]`);
        
        if (select) {
            select.addEventListener('change', function() {
                updateDestinationInfo(destinationId);
                updateAllDestinationOptions();
                updateTransferSummary();
            });
        }
        
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                updateDestinationInfo(destinationId);
                updateTransferSummary();
            });
        }
    }
    
    // Fonction pour mettre à jour les informations d'une destination
    function updateDestinationInfo(destinationId) {
        const select = document.querySelector(`select[data-destination-id="${destinationId}"]`);
        const amountInput = document.querySelector(`input[data-destination-id="${destinationId}"]`);
        const infoDiv = document.getElementById(`destination_info_${destinationId}`);
        
        if (!select || !amountInput || !infoDiv) return;
        
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            const currentSolde = parseFloat(selectedOption.dataset.solde);
            const transferAmount = parseFloat(amountInput.value) || 0;
            
            // Afficher les informations
            const currentBalanceSpan = infoDiv.querySelector('.destination-current-balance');
            const afterTransferSpan = infoDiv.querySelector('.destination-after-transfer');
            
            if (currentSolde > 0) {
                currentBalanceSpan.textContent = `À payer: ${new Intl.NumberFormat('fr-FR').format(currentSolde)} FCFA`;
                currentBalanceSpan.className = 'destination-current-balance text-warning';
            } else if (currentSolde < 0) {
                currentBalanceSpan.textContent = `Trop-perçu: ${new Intl.NumberFormat('fr-FR').format(Math.abs(currentSolde))} FCFA`;
                currentBalanceSpan.className = 'destination-current-balance text-success';
            } else {
                currentBalanceSpan.textContent = 'Soldé';
                currentBalanceSpan.className = 'destination-current-balance text-muted';
            }
            
            if (transferAmount > 0) {
                const newSolde = currentSolde - transferAmount;
                if (newSolde > 0) {
                    afterTransferSpan.textContent = `Restera à payer: ${new Intl.NumberFormat('fr-FR').format(newSolde)} FCFA`;
                    afterTransferSpan.className = 'destination-after-transfer text-warning';
                } else if (newSolde < 0) {
                    afterTransferSpan.textContent = `Nouveau trop-perçu: ${new Intl.NumberFormat('fr-FR').format(Math.abs(newSolde))} FCFA`;
                    afterTransferSpan.className = 'destination-after-transfer text-success';
                } else {
                    afterTransferSpan.textContent = 'Soldé parfaitement';
                    afterTransferSpan.className = 'destination-after-transfer text-success fw-bold';
                }
            } else {
                afterTransferSpan.textContent = '-';
                afterTransferSpan.className = 'destination-after-transfer text-muted';
            }
            
            infoDiv.classList.remove('d-none');
        } else {
            infoDiv.classList.add('d-none');
        }
    }
    
    // Fonction pour mettre à jour toutes les options de destination
    function updateAllDestinationOptions() {
        const sourceCategoryId = document.getElementById('transfer_source_category').value;
        document.querySelectorAll('.destination-select').forEach(select => {
            const destinationId = select.dataset.destinationId;
            const currentValue = select.value;
            populateDestinationOptions(destinationId, sourceCategoryId);
            // Restaurer la valeur sélectionnée si elle est toujours disponible
            if (currentValue) {
                select.value = currentValue;
            }
        });
    }
    
    // Fonction pour mettre à jour le résumé des transferts
    function updateTransferSummary() {
        const totalAvailable = parseFloat(document.getElementById('transfer_amount_hidden').value) || 0;
        let totalToTransfer = 0;
        let hasValidDestinations = false;
        
        document.querySelectorAll('.destination-amount').forEach(input => {
            const amount = parseFloat(input.value) || 0;
            if (amount > 0) {
                totalToTransfer += amount;
                hasValidDestinations = true;
            }
        });
        
        const remaining = totalAvailable - totalToTransfer;
        
        // Mettre à jour l'affichage
        document.getElementById('total_transfer_amount').textContent = 
            new Intl.NumberFormat('fr-FR').format(totalToTransfer) + ' FCFA';
        document.getElementById('remaining_amount').textContent = 
            new Intl.NumberFormat('fr-FR').format(remaining) + ' FCFA';
        
        // Gérer la couleur du montant restant
        const remainingSpan = document.getElementById('remaining_amount');
        if (remaining < 0) {
            remainingSpan.className = 'text-danger fw-bold';
        } else if (remaining === 0) {
            remainingSpan.className = 'text-success fw-bold';
        } else {
            remainingSpan.className = 'text-success';
        }
        
        // Afficher/masquer le résumé
        const summaryDiv = document.getElementById('transfer_summary');
        if (hasValidDestinations) {
            summaryDiv.classList.remove('d-none');
        } else {
            summaryDiv.classList.add('d-none');
        }
        
        // Activer/désactiver le bouton de soumission
        const submitBtn = document.getElementById('transfer_submit_btn');
        submitBtn.disabled = !hasValidDestinations || remaining < 0 || totalToTransfer === 0;
        
        // Vérifier que toutes les destinations ont une catégorie sélectionnée
        const allSelects = document.querySelectorAll('.destination-select');
        let allSelected = true;
        allSelects.forEach(select => {
            if (!select.value) {
                allSelected = false;
            }
        });
        
        if (!allSelected) {
            submitBtn.disabled = true;
        }
    }
    
    // Fonction pour supprimer une destination
    function removeDestination(destinationId) {
        const row = document.querySelector(`div[data-destination-id="${destinationId}"]`);
        if (row) {
            row.remove();
            updateAllDestinationOptions();
            updateTransferSummary();
            updateRemoveButtons();
        }
    }
    
    // Fonction pour mettre à jour la visibilité des boutons de suppression
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-destination-btn');
        const destinationRows = document.querySelectorAll('.destination-row');
        
        removeButtons.forEach(btn => {
            if (destinationRows.length > 1) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        });
    }
    
    console.log('✅ FONCTIONS MODALES DÉFINIES:');
    console.log('  - preparePaymentModal:', typeof preparePaymentModal);
    console.log('  - openValidationModal:', typeof openValidationModal);
    console.log('  - preparePaymentModalForCategory:', typeof preparePaymentModalForCategory);
    console.log('  - prepareTransferModal:', typeof prepareTransferModal);

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Initialisation du diagnostic des modals');
        
        // Surveillance simple des événements de modals - Style class-selector
        const modals = ['paymentModal', 'validationModal', 'subscriptionModal', 'transferModal'];
        
        // Événement spécial pour le modal de transfert - réinitialisation complète
        const transferModal = document.getElementById('transferModal');
        if (transferModal) {
            // À l'ouverture : appliquer les données de transfert
            transferModal.addEventListener('shown.bs.modal', function() {
                console.log('🎯 Modal de transfert ouvert - Application des données');
                
                // Forcer l'application des données stockées
                if (transferData.sourceCategoryId) {
                    applyTransferData();
                    console.log('🔄 Données de transfert réappliquées');
                } else {
                    console.warn('⚠️ Aucune donnée de transfert stockée');
                }
            });
            
            // À la fermeture : nettoyer
            transferModal.addEventListener('hidden.bs.modal', function() {
                // Réinitialiser complètement le modal quand il se ferme
                const container = document.getElementById('destinations_container');
                if (container) {
                    container.innerHTML = '';
                }
                destinationCounter = 0;
                
                // Nettoyer les données stockées
                transferData = {
                    inscriptionId: null,
                    sourceCategoryId: null,
                    availableAmount: null,
                    sourceCategoryName: null
                };
                
                console.log('📝 Modal de transfert réinitialisé et données nettoyées');
            });
        }
        
        // Gérer le bouton d'ajout de destination
        const addDestinationBtn = document.getElementById('add_destination_btn');
        if (addDestinationBtn) {
            addDestinationBtn.addEventListener('click', function() {
                const sourceCategoryId = document.getElementById('transfer_source_category').value;
                const totalAvailable = parseFloat(document.getElementById('transfer_amount_hidden').value);
                
                // Vérifier qu'il reste des catégories disponibles
                const selectedCategories = getSelectedCategories();
                const availableCategories = availableFees.filter(item => 
                    item.category.id != sourceCategoryId && 
                    !selectedCategories.includes(item.category.id.toString())
                );
                
                if (availableCategories.length > 0) {
                    addDestinationRow(sourceCategoryId, totalAvailable);
                } else {
                    alert('Toutes les catégories de frais disponibles ont déjà été sélectionnées.');
                }
            });
        }
        
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Événement pour forcer z-index correct à l'ouverture
                modal.addEventListener('show.bs.modal', function(e) {
                    console.log(`🔧 Préparation modal ${modalId}`);
                    
                    // Désactiver toutes les animations pendant l'ouverture
                    document.body.style.setProperty('overflow', 'hidden', 'important');
                    
                    // Ajouter style anti-cursor
                    const antiCursorStyle = document.createElement('style');
                    antiCursorStyle.id = `anti-cursor-${modalId}`;
                    antiCursorStyle.textContent = `
                        * { animation: none !important; transition: none !important; }
                        *:hover { transform: none !important; }
                    `;
                    document.head.appendChild(antiCursorStyle);
                });
                
                modal.addEventListener('shown.bs.modal', function(e) {
                    console.log(`✅ Modal ${modalId} ouvert - Application des corrections`);
                    
                    // Forcer z-index très élevé
                    modal.style.setProperty('z-index', '9999', 'important');
                    modal.style.setProperty('backdrop-filter', 'none', 'important');
                    modal.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    
                    const modalDialog = modal.querySelector('.modal-dialog');
                    const modalContent = modal.querySelector('.modal-content');
                    
                    if (modalDialog) {
                        modalDialog.style.setProperty('z-index', '10000', 'important');
                        modalDialog.style.setProperty('backdrop-filter', 'none', 'important');
                        modalDialog.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    }
                    
                    if (modalContent) {
                        modalContent.style.setProperty('z-index', '10001', 'important');
                        modalContent.style.setProperty('backdrop-filter', 'none', 'important');
                        modalContent.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                        modalContent.style.setProperty('background', 'white', 'important');
                    }
                    
                    // Forcer backdrop en arrière
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.style.setProperty('z-index', '1040', 'important');
                        backdrop.style.setProperty('backdrop-filter', 'none', 'important');
                        backdrop.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    }
                });
                
                // Nettoyer à la fermeture
                modal.addEventListener('hidden.bs.modal', function(e) {
                    console.log(`🧹 Nettoyage modal ${modalId}`);
                    
                    // Supprimer style anti-cursor
                    const antiCursorStyle = document.getElementById(`anti-cursor-${modalId}`);
                    if (antiCursorStyle) {
                        antiCursorStyle.remove();
                    }
                    
                    // Rétablir overflow
                    document.body.style.overflow = '';
                });
            }
        });
        
        // Auto-remplir le montant selon la catégorie sélectionnée - Style class-selector
        const feeCategorySelect = document.getElementById('fee_category_id');
        const montantInput = document.getElementById('montant');
        
        if (feeCategorySelect && montantInput) {
            feeCategorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const defaultAmount = selectedOption.getAttribute('data-default-amount');
                if (defaultAmount) {
                    montantInput.value = defaultAmount;
                    console.log('💰 Montant auto-rempli:', defaultAmount);
                }
            });
        }

        // Auto-remplir le montant pour la souscription
        const optionalCategorySelect = document.getElementById('optional_category_id');
        const subscriptionAmountInput = document.getElementById('subscription_amount');
        
        if (optionalCategorySelect && subscriptionAmountInput) {
            optionalCategorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const defaultAmount = selectedOption.getAttribute('data-default-amount');
                if (defaultAmount) {
                    subscriptionAmountInput.value = defaultAmount;
                    console.log('💰 Montant souscription auto-rempli:', defaultAmount);
                }
            });
        }
    });
</script>

<!-- Les styles z-index pour les modals sont gérés par modal-force-fix.css -->

