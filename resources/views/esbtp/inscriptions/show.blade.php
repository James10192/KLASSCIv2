@extends('layouts.app')

@section('title', 'Détails de l\'inscription')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Inscription - {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</h1>
                <p class="header-subtitle">Détails de l'inscription #{{ $inscription->id }} - {{ $inscription->classe->name ?? 'Classe non définie' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn-acasi primary me-2">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="p-lg">
            <div class="card-moderne">
                <div class="card-body">
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

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="section-title mb-0">Statut de l'inscription</h6>
                                <div>
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
                            
                            <span class="badge bg-{{ $inscription->status === 'active' ? 'success' : ($inscription->status === 'en_attente' ? 'warning' : 'danger') }}">
                                {{ ucfirst($inscription->status) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        @can('inscriptions.validate')
                            <a href="{{ route('esbtp.inscriptions.administration') }}" class="btn btn-info me-2">
                                <i class="fas fa-cog me-1"></i>Administration
                            </a>
                            
                            @if($inscription->status === 'en_attente' && !$inscription->paiement_validation_id)
                                <button class="btn btn-success me-2" onclick="openPaymentModal({{ $inscription->id }})">
                                    <i class="fas fa-credit-card me-1"></i>Valider avec paiement
                                </button>
                            @endif
                            
                            @if($inscription->paiement_validation_id && $inscription->workflow_step === 'en_validation')
                                <button class="btn btn-primary me-2" onclick="openValidationModal({{ $inscription->id }})">
                                    <i class="fas fa-check me-1"></i>Valider définitivement
                                </button>
                            @endif
                        @endcan
                        
                        @if($inscription->status === 'en_attente')
                            <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn btn-outline-primary me-2">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn btn-outline-warning me-2">
                                <i class="fas fa-exchange-alt me-1"></i>Modifier la classe
                            </a>
                        @endif
                        
                        <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('account_info'))
                        <div class="alert alert-info">
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
                        </div>
                    @endif

                    <!-- Informations de l'étudiant -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Informations de l'étudiant</h6>
                        </div>
                        <div class="col-md-3">
                            <!-- Photo de l'étudiant -->
                            @if($inscription->etudiant->photo)
                                <div class="text-center mb-3">
                                    <img src="{{ asset('storage/' . $inscription->etudiant->photo) }}" 
                                         alt="Photo de {{ $inscription->etudiant->prenoms }} {{ $inscription->etudiant->nom }}"
                                         class="img-fluid rounded-circle border border-3 border-primary"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                </div>
                            @else
                                <div class="text-center mb-3">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle border border-3 border-secondary bg-light"
                                         style="width: 150px; height: 150px;">
                                        <i class="fas fa-user fa-4x text-secondary"></i>
                                    </div>
                                    <small class="text-muted d-block mt-2">Aucune photo</small>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <p><strong>Nom:</strong> {{ $inscription->etudiant->nom }}</p>
                            <p><strong>Prénoms:</strong> {{ $inscription->etudiant->prenoms }}</p>
                            <p><strong>Matricule:</strong> {{ $inscription->etudiant->matricule }}</p>
                            <p><strong>Email:</strong> {{ $inscription->etudiant->email ?? 'Non renseigné' }}</p>
                        </div>
                        <div class="col-md-5">
                            <p><strong>Date de naissance:</strong> {{ $inscription->etudiant->date_naissance }}</p>
                            <p><strong>Lieu de naissance:</strong> {{ $inscription->etudiant->lieu_naissance ?? 'Non renseigné' }}</p>
                            <p><strong>Genre:</strong> {{ $inscription->etudiant->sexe === 'M' ? 'Homme' : 'Femme' }}</p>
                            <p><strong>Téléphone:</strong> {{ $inscription->etudiant->telephone }}</p>
                            <p><strong>Ville de résidence:</strong> {{ $inscription->etudiant->ville ?? 'Non renseigné' }}</p>
                            <p><strong>Commune de résidence:</strong> {{ $inscription->etudiant->commune ?? 'Non renseigné' }}</p>
                        </div>
                    </div>

                    <!-- Workflow et Historique -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Workflow et Historique</h6>
                        </div>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Progression du workflow</h6>
                                    <div class="progress mb-3" style="height: 25px;">
                                        @php
                                            $steps = ['prospect', 'documents_complets', 'en_validation', 'valide', 'etudiant_cree'];
                                            $currentStepIndex = array_search($inscription->workflow_step, $steps);
                                            $progress = $currentStepIndex !== false ? (($currentStepIndex + 1) / count($steps)) * 100 : 0;
                                        @endphp
                                        <div class="progress-bar progress-bar-striped" role="progressbar" style="width: {{ $progress }}%">
                                            {{ round($progress) }}%
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between small">
                                        <span class="text-{{ $inscription->workflow_step === 'prospect' ? 'primary' : 'muted' }}">
                                            <i class="fas fa-user-plus"></i> Prospect
                                        </span>
                                        <span class="text-{{ $inscription->workflow_step === 'documents_complets' ? 'primary' : 'muted' }}">
                                            <i class="fas fa-file-check"></i> Documents
                                        </span>
                                        <span class="text-{{ $inscription->workflow_step === 'en_validation' ? 'primary' : 'muted' }}">
                                            <i class="fas fa-hourglass-half"></i> Validation
                                        </span>
                                        <span class="text-{{ $inscription->workflow_step === 'valide' ? 'primary' : 'muted' }}">
                                            <i class="fas fa-check"></i> Validé
                                        </span>
                                        <span class="text-{{ $inscription->workflow_step === 'etudiant_cree' ? 'primary' : 'muted' }}">
                                            <i class="fas fa-graduation-cap"></i> Étudiant
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Informations de validation</h6>
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
                    </div>

                    <!-- Informations de l'inscription -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Informations de l'inscription</h6>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Filière:</strong> {{ $inscription->filiere->name }}</p>
                            <p><strong>Niveau:</strong> {{ $inscription->niveau->name }}</p>
                            <p><strong>Classe:</strong> {{ $inscription->classe->name }}
                                @if($inscription->status === 'en_attente')
                                    <span class="text-muted small d-block">Vous pouvez modifier la classe tant que l'inscription n'est pas validée.</span>
                                @else
                                    <span class="text-muted small d-block">La classe ne peut plus être modifiée après validation.</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Année universitaire:</strong> {{ $inscription->anneeUniversitaire->name }}</p>
                            <p><strong>Date d'inscription:</strong> {{ $inscription->date_inscription }}</p>
                            <p><strong>Statut:</strong>
                                <span class="badge bg-{{ $inscription->status === 'active' ? 'success' : ($inscription->status === 'en_attente' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($inscription->status) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            @php
                                $totalAttendu = collect($feeCategoriesWithRules)->where('is_configured', true)->sum('montant_attendu');
                                $totalPaye = collect($feeCategoriesWithRules)->sum('total_paye');
                                $soldeGlobal = $totalAttendu - $totalPaye;
                                $obligatoiresConfigures = collect($feeCategoriesWithRules)->where('is_mandatory', true)->where('is_configured', true)->count();
                                $obligatoiresTotal = collect($feeCategoriesWithRules)->where('is_mandatory', true)->count();
                            @endphp
                            <div class="card border-{{ $soldeGlobal <= 0 ? 'success' : 'warning' }}">
                                <div class="card-body">
                                    <h6 class="card-title text-{{ $soldeGlobal <= 0 ? 'success' : 'warning' }}">
                                        <i class="fas fa-wallet me-1"></i>Résumé Financier
                                    </h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="text-primary">
                                                <strong>{{ number_format($totalAttendu, 0, ',', ' ') }}</strong>
                                                <small class="d-block text-muted">Total attendu</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-success">
                                                <strong>{{ number_format($totalPaye, 0, ',', ' ') }}</strong>
                                                <small class="d-block text-muted">Total payé</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-{{ $soldeGlobal <= 0 ? 'success' : 'danger' }}">
                                                <strong>{{ number_format($soldeGlobal, 0, ',', ' ') }}</strong>
                                                <small class="d-block text-muted">Solde</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $totalAttendu > 0 ? ($totalPaye / $totalAttendu) * 100 : 0 }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $obligatoiresConfigures }}/{{ $obligatoiresTotal }} frais obligatoires configurés</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Situation financière détaillée -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Situation Financière Détaillée</h6>
                        </div>
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Type</th>
                                            <th>Montant Attendu</th>
                                            <th>Montant Payé</th>
                                            <th>Solde</th>
                                            <th>Statut</th>
                                            <th>Derniers Paiements</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($feeCategoriesWithRules as $item)
                                            <tr class="align-middle">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item['category']->icon)
                                                            <i class="{{ $item['category']->icon }} me-2 text-{{ $item['category']->color }}"></i>
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
                                                        <span class="badge bg-danger">Obligatoire</span>
                                                    @else
                                                        <span class="badge bg-info">Optionnel</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['is_configured'])
                                                        <strong class="text-primary">{{ number_format($item['montant_attendu'], 0, ',', ' ') }} FCFA</strong>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Non configuré</span>
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
                                                        <strong class="text-info">{{ number_format(abs($item['solde']), 0, ',', ' ') }} FCFA</strong>
                                                        <br><small class="text-muted">Trop-perçu</small>
                                                    @else
                                                        <strong class="text-success">Soldé</strong>
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
                                                    @if($item['paiements']->count() > 0)
                                                        <div class="small">
                                                            @foreach($item['paiements']->take(2) as $paiement)
                                                                <div class="mb-1">
                                                                    <strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>
                                                                    <br><small class="text-muted">{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}</small>
                                                                </div>
                                                            @endforeach
                                                            @if($item['paiements']->count() > 2)
                                                                <small class="text-muted">... et {{ $item['paiements']->count() - 2 }} autre(s)</small>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Aucun paiement</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        @if($item['is_configured'] && $item['solde'] > 0)
                                                            <button class="btn btn-sm btn-success" onclick="openPaymentModalForCategory({{ $inscription->id }}, {{ $item['category']->id }})" title="Effectuer un paiement">
                                                                <i class="fas fa-credit-card"></i>
                                                            </button>
                                                        @endif
                                                        @if(!$item['is_configured'])
                                                            <a href="{{ route('esbtp.frais.configure') }}?filiere_id={{ $inscription->filiere_id }}&niveau_id={{ $inscription->niveau_id }}" class="btn btn-sm btn-warning" title="Configurer ce frais">
                                                                <i class="fas fa-cogs"></i>
                                                            </a>
                                                        @endif
                                                        @if(!$item['is_mandatory'] && $item['is_subscribed'])
                                                            <button class="btn btn-sm btn-outline-danger" onclick="unsubscribeFromFee({{ $inscription->id }}, {{ $item['category']->id }}, '{{ $item['category']->name }}')" title="Se désabonner de ce frais optionnel">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
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
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="border-bottom pb-2 mb-0">Frais Optionnels Disponibles</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#subscriptionModal">
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
                                        <div class="card border-info h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    @if($category->icon)
                                                        <i class="{{ $category->icon }} me-2 text-{{ $category->color }}"></i>
                                                    @endif
                                                    <h6 class="card-title mb-0">{{ $category->name }}</h6>
                                                </div>
                                                @if($category->description)
                                                    <p class="card-text text-muted small">{{ $category->description }}</p>
                                                @endif
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-info">Optionnel</span>
                                                    <span class="fw-bold text-primary">{{ number_format($category->default_amount, 0, ',', ' ') }} FCFA</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Parents -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Parents/Tuteurs</h6>
                        </div>
                        @forelse($inscription->etudiant->parents as $parent)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $parent->nom }} {{ $parent->prenoms }}</h6>
                                        <p class="mb-1"><strong>Téléphone:</strong> {{ $parent->telephone }}</p>
                                        <p class="mb-1"><strong>Email:</strong> {{ $parent->email }}</p>
                                        <p class="mb-1"><strong>Profession:</strong> {{ $parent->profession }}</p>
                                        <p class="mb-0"><strong>Relation:</strong> {{ ucfirst($parent->pivot->relation) }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted">Aucun parent enregistré</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Paiements liés à l'inscription -->
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Paiements liés à cette inscription</h6>
                        </div>
                        <div class="col-12">
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
                                    <table class="table table-striped">
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
                                                        @if(isset($payment->montant))
                                                            {{ number_format($payment->montant, 0, ',', ' ') }} FCFA
                                                        @elseif(isset($payment->amount))
                                                            {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
                                                        @endif
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
                                            <tr>
                                                <th>Total</th>
                                                <th>
                                                    @php
                                                        $total = 0;
                                                        foreach($allPayments as $payment) {
                                                            $total += $payment->montant ?? $payment->amount ?? 0;
                                                        }
                                                    @endphp
                                                    {{ number_format($total, 0, ',', ' ') }} FCFA
                                                </th>
                                                <th colspan="4"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
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

<!-- Workflow Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>Workflow de validation</h6>
            <p class="mb-0">Vous pouvez valider cette inscription directement depuis cette page avec le bouton <strong>"Valider avec paiement"</strong> 
            ou utiliser l'<strong>interface d'administration</strong> pour une gestion plus complète du workflow prospect → étudiant.</p>
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
                                    @if(isset($categoriesfrais))
                                        @foreach($categoriesfrais as $categorie)
                                            <option value="{{ $categorie->id }}" data-default-amount="{{ $categorie->default_amount }}">
                                                <i class="{{ $categorie->icon }}"></i> {{ $categorie->name }}
                                                @if($categorie->is_mandatory)
                                                    <span class="text-danger">*</span>
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

<!-- Modal pour souscription aux frais optionnels -->
<div class="modal fade" id="subscriptionModal" tabindex="-1" aria-labelledby="subscriptionModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subscriptionModalLabel">Souscrire l'étudiant à un frais optionnel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="subscriptionForm" method="POST" action="{{ route('esbtp.inscriptions.subscribe-optional-fee', $inscription->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Souscription aux frais optionnels</strong><br>
                        Sélectionnez un frais optionnel et définissez le montant à payer pour cet étudiant.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subscription_category_id" class="form-label">Frais optionnel <span class="text-danger">*</span></label>
                                <select class="form-select" id="subscription_category_id" name="frais_category_id" required>
                                    <option value="">Sélectionnez un frais</option>
                                    @if(isset($availableOptionalCategories))
                                        @foreach($availableOptionalCategories as $category)
                                            <option value="{{ $category->id }}" 
                                                    data-default-amount="{{ $category->default_amount }}"
                                                    data-description="{{ $category->description }}">
                                                {{ $category->name }} - {{ number_format($category->default_amount, 0, ',', ' ') }} FCFA
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subscription_amount" class="form-label">Montant à payer (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="subscription_amount" name="amount" 
                                       min="0" step="0.01" required placeholder="0">
                                <small class="form-text text-muted">Le montant peut être différent du montant par défaut</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subscription_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="subscription_notes" name="notes" rows="3" 
                                  placeholder="Commentaires sur cette souscription..."></textarea>
                    </div>
                    
                    <div id="category_description" class="alert alert-light d-none">
                        <strong>Description du frais :</strong>
                        <p class="mb-0" id="description_text"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Souscrire
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Debug : afficher le tableau complet dans la console
    console.log('mandatoryFeeCategoriesWithRules:', @json($mandatoryFeeCategoriesWithRules));
    
    function openPaymentModal(inscriptionId) {
        const form = document.getElementById('paymentForm');
        form.action = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;
        
        // Réinitialiser le formulaire
        form.reset();
        document.getElementById('date_paiement').value = new Date().toISOString().split('T')[0];
        
        // Gérer le changement de catégorie pour pré-remplir le montant
        document.getElementById('fee_category_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const defaultAmount = selectedOption.getAttribute('data-default-amount');
            if (defaultAmount) {
                document.getElementById('montant').value = defaultAmount;
            }
        });
        
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

    // Gestion du modal de souscription aux frais optionnels
    document.addEventListener('DOMContentLoaded', function() {
        const subscriptionCategorySelect = document.getElementById('subscription_category_id');
        const subscriptionAmountInput = document.getElementById('subscription_amount');
        const categoryDescription = document.getElementById('category_description');
        const descriptionText = document.getElementById('description_text');

        if (subscriptionCategorySelect) {
            subscriptionCategorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (selectedOption.value) {
                    // Pré-remplir le montant avec le montant par défaut
                    const defaultAmount = selectedOption.getAttribute('data-default-amount');
                    if (defaultAmount) {
                        subscriptionAmountInput.value = defaultAmount;
                    }
                    
                    // Afficher la description si disponible
                    const description = selectedOption.getAttribute('data-description');
                    if (description && description.trim()) {
                        descriptionText.textContent = description;
                        categoryDescription.classList.remove('d-none');
                    } else {
                        categoryDescription.classList.add('d-none');
                    }
                } else {
                    // Réinitialiser si aucune sélection
                    subscriptionAmountInput.value = '';
                    categoryDescription.classList.add('d-none');
                }
            });
        }
    });

    // Fonction pour se désabonner d'un frais optionnel
    function unsubscribeFromFee(inscriptionId, categoryId, categoryName) {
        if (confirm(`Êtes-vous sûr de vouloir désabonner cet étudiant du frais "${categoryName}" ?\n\nCette action supprimera ce frais de la liste mais conservera l'historique des paiements déjà effectués.`)) {
            // Créer un formulaire temporaire pour envoyer la requête POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/esbtp/inscriptions/${inscriptionId}/unsubscribe-optional-fee`;
            
            // Ajouter le token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            }
            
            // Ajouter l'ID de la catégorie
            const categoryInput = document.createElement('input');
            categoryInput.type = 'hidden';
            categoryInput.name = 'frais_category_id';
            categoryInput.value = categoryId;
            form.appendChild(categoryInput);
            
            // Ajouter au DOM et soumettre
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<style>
/* Fix pour le modal de souscription */
#subscriptionModal {
    z-index: 1060 !important;
}

#subscriptionModal .modal-dialog {
    margin: 1.75rem auto !important;
    max-width: 800px !important;
}

#subscriptionModal .modal-content {
    position: relative !important;
    border: none !important;
    border-radius: 10px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
}

/* S'assurer que le backdrop est visible */
#subscriptionModal.modal.fade.show {
    display: block !important;
}

.modal-backdrop {
    z-index: 1055 !important;
}

/* Fix pour le positionnement centré */
#subscriptionModal .modal-dialog-centered {
    display: flex !important;
    align-items: center !important;
    min-height: calc(100vh - 3.5rem) !important;
}
</style>
@endpush
    </div>
</div>
@endsection
