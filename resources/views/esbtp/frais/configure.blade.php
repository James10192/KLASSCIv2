@extends('layouts.app')

@section('title', 'Configuration des Frais - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Configuration des Frais par Classe</h1>
        <a href="{{ route('esbtp.frais.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Instructions -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Comment ça marche :</strong>
        <ul class="mb-0 mt-2">
            <li><strong>Classe = Filière + Niveau d'étude</strong></li>
            <li>Configurez les frais pour chaque classe en cliquant sur "Configurer"</li>
            <li>Les frais <span class="badge bg-danger">obligatoires</span> doivent être configurés</li>
            <li>Les frais <span class="badge bg-info">optionnels</span> sont facultatifs</li>
        </ul>
    </div>

    <!-- Tableau des classes -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Classes et Configuration des Frais
            </h5>
        </div>
        <div class="card-body">
            @if($classes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="30%">Classe</th>
                                <th width="15%">Effectif</th>
                                <th width="25%">Frais Configurés</th>
                                <th width="20%">Statut</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $classe)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $classe->name }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $classe->filiere->name }} - {{ $classe->niveau->name }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info fs-6">
                                            {{ $classe->effectif }} étudiants
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($classe->obligatoires_configures > 0)
                                                <span class="badge bg-danger">
                                                    {{ $classe->obligatoires_configures }}/{{ $classe->total_obligatoires }} obligatoires
                                                </span>
                                            @endif
                                            @if($classe->optionnels_configures > 0)
                                                <span class="badge bg-success">
                                                    {{ $classe->optionnels_configures }}/{{ $classe->total_optionnels }} optionnels
                                                </span>
                                            @endif
                                            @if($classe->frais_configures->count() == 0)
                                                <span class="badge bg-secondary">Aucun frais configuré</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($classe->obligatoires_configures == $classe->total_obligatoires)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Complet
                                            </span>
                                        @elseif($classe->obligatoires_configures > 0)
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Partiel
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i>Non configuré
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('esbtp.frais.configure') }}?filiere_id={{ $classe->filiere->id }}&niveau_id={{ $classe->niveau->id }}" 
                                           class="btn btn-sm btn-primary" 
                                           title="Configurer les frais">
                                            <i class="fas fa-cogs"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune classe trouvée</h5>
                    <p class="text-muted">Vérifiez que vous avez des filières et niveaux d'étude actifs.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Configuration pour une classe sélectionnée -->
    @if(request('filiere_id') && request('niveau_id'))
        @php
            $selectedFiliere = $filieres->find(request('filiere_id'));
            $selectedNiveau = $niveaux->find(request('niveau_id'));
        @endphp
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Configuration des Frais
                    <span class="badge bg-primary ms-2">
                        {{ $selectedFiliere->name ?? 'Filière' }} - {{ $selectedNiveau->name ?? 'Niveau' }}
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('esbtp.frais.update-configuration') }}">
                    @csrf
                    <input type="hidden" name="filiere_id" value="{{ request('filiere_id') }}">
                    <input type="hidden" name="niveau_id" value="{{ request('niveau_id') }}">

                    <div class="row">
                        @foreach($categories as $category)
                            <div class="col-md-6 mb-3">
                                <div class="card border-{{ $category->is_mandatory ? 'danger' : 'info' }}">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                @if($category->icon)
                                                    <i class="{{ $category->icon }} me-2"></i>
                                                @endif
                                                {{ $category->name }}
                                            </h6>
                                            <span class="badge bg-{{ $category->is_mandatory ? 'danger' : 'info' }}">
                                                {{ $category->is_mandatory ? 'Obligatoire' : 'Optionnel' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @php
                                            $existingRule = $rules->where('frais_category_id', $category->id)->first();
                                        @endphp
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Montant (FCFA)</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="categories[{{ $category->id }}][amount]" 
                                                       value="{{ $existingRule->amount ?? $category->default_amount }}" 
                                                       min="0" 
                                                       step="0.01"
                                                       {{ $category->is_mandatory ? 'required' : '' }}>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Échéance (jours)</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="categories[{{ $category->id }}][deadline_days]" 
                                                       value="{{ $existingRule->payment_deadline_days ?? $category->payment_deadline_days }}" 
                                                       min="1" 
                                                       max="365"
                                                       {{ $category->is_mandatory ? 'required' : '' }}>
                                            </div>
                                        </div>
                                        
                                        @if($category->description)
                                            <div class="mt-2">
                                                <small class="text-muted">{{ $category->description }}</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Enregistrer la Configuration
                        </button>
                        <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-secondary btn-lg ms-2">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
}

.table th {
    border-top: none;
    font-weight: 600;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 0.75em;
}

.fs-6 {
    font-size: 0.875rem;
}

.btn-group .btn {
    border-radius: 0.375rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}
</style>
@endpush