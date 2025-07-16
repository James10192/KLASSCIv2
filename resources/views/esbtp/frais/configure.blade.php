@extends('layouts.app')

@section('title', 'Configuration des Frais - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Configuration des Frais par Filière/Niveau</h1>
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

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('esbtp.frais.configure') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="filiere_id" class="form-label">Filière <span class="text-danger">*</span></label>
                            <select class="form-select" name="filiere_id" id="filiere_id" required>
                                <option value="">Sélectionnez une filière</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ $filiereId == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="niveau_id" class="form-label">Niveau <span class="text-danger">*</span></label>
                            <select class="form-select" name="niveau_id" id="niveau_id" required>
                                <option value="">Sélectionnez un niveau</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ $niveauId == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="annee_id" class="form-label">Année universitaire</label>
                            <select class="form-select" name="annee_id" id="annee_id">
                                <option value="">Toutes les années</option>
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Filtrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($filiereId && $niveauId)
        <!-- Configuration des règles -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Configuration pour 
                    <span class="text-primary">{{ $filieres->find($filiereId)->name }}</span> - 
                    <span class="text-info">{{ $niveaux->find($niveauId)->name }}</span>
                    @if($anneeId)
                        - <span class="text-success">{{ $annees->find($anneeId)->name }}</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('esbtp.frais.update-configuration') }}">
                    @csrf
                    <input type="hidden" name="filiere_id" value="{{ $filiereId }}">
                    <input type="hidden" name="niveau_id" value="{{ $niveauId }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeId }}">

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Type</th>
                                    <th>Montant (FCFA)</th>
                                    <th>Délai (jours)</th>
                                    <th>Échéancier</th>
                                    <th>Pénalités</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    @php
                                        $existingRule = $rules->where('frais_category_id', $category->id)->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($category->icon)
                                                    <i class="{{ $category->icon }} me-2 text-{{ $category->color }}"></i>
                                                @endif
                                                <div>
                                                    <strong>{{ $category->name }}</strong>
                                                    @if($category->description)
                                                        <br><small class="text-muted">{{ $category->description }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($category->is_mandatory)
                                                <span class="badge bg-danger">Obligatoire</span>
                                            @else
                                                <span class="badge bg-info">Optionnel</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="hidden" name="rules[{{ $loop->index }}][frais_category_id]" value="{{ $category->id }}">
                                            <input type="number" class="form-control" name="rules[{{ $loop->index }}][amount]" 
                                                   value="{{ $existingRule ? $existingRule->amount : $category->default_amount }}" 
                                                   min="0" step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="rules[{{ $loop->index }}][payment_deadline_days]" 
                                                   value="{{ $existingRule ? $existingRule->payment_deadline_days : $category->payment_deadline_days }}" 
                                                   min="1" max="365" required>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="rules[{{ $loop->index }}][installments_allowed]" 
                                                       value="1" {{ $existingRule && $existingRule->installments_allowed ? 'checked' : '' }}
                                                       onchange="toggleInstallmentFields({{ $loop->index }})">
                                                <label class="form-check-label">
                                                    Autorisé
                                                </label>
                                            </div>
                                            <div id="installment-fields-{{ $loop->index }}" style="display: {{ $existingRule && $existingRule->installments_allowed ? 'block' : 'none' }};">
                                                <input type="number" class="form-control form-control-sm mt-2" 
                                                       name="rules[{{ $loop->index }}][max_installments]" 
                                                       value="{{ $existingRule ? $existingRule->max_installments : 1 }}" 
                                                       min="1" max="12" placeholder="Max échéances">
                                                <input type="number" class="form-control form-control-sm mt-2" 
                                                       name="rules[{{ $loop->index }}][min_installment_amount]" 
                                                       value="{{ $existingRule ? $existingRule->min_installment_amount : '' }}" 
                                                       min="0" step="0.01" placeholder="Montant min">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
                                                   name="rules[{{ $loop->index }}][late_fee_percentage]" 
                                                   value="{{ $existingRule ? $existingRule->late_fee_percentage : 0 }}" 
                                                   min="0" max="100" step="0.01" placeholder="% retard">
                                            <input type="number" class="form-control form-control-sm mt-2" 
                                                   name="rules[{{ $loop->index }}][late_fee_amount]" 
                                                   value="{{ $existingRule ? $existingRule->late_fee_amount : 0 }}" 
                                                   min="0" step="0.01" placeholder="Montant fixe">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Enregistrer la configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-filter fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Sélectionnez une filière et un niveau</h5>
                <p class="text-muted">Choisissez une filière et un niveau pour configurer les frais.</p>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleInstallmentFields(index) {
        const checkbox = document.querySelector(`input[name="rules[${index}][installments_allowed]"]`);
        const fields = document.getElementById(`installment-fields-${index}`);
        
        if (checkbox.checked) {
            fields.style.display = 'block';
        } else {
            fields.style.display = 'none';
        }
    }
</script>
@endpush