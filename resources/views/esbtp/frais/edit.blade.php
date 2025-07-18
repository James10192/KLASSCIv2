@extends('layouts.app')

@section('title', 'Modifier Catégorie de Frais - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Modifier la Catégorie de Frais</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('esbtp.frais.index') }}">Gestion Frais</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('esbtp.frais.show', $fraisCategory) }}">{{ $fraisCategory->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('esbtp.frais.show', $fraisCategory) }}" class="btn btn-outline-info">
                <i class="fas fa-eye me-1"></i>Voir
            </a>
            <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-outline-primary">
                <i class="fas fa-cogs me-1"></i>Configuration
            </a>
            <a href="{{ route('esbtp.frais.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
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

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Informations de la Catégorie
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('esbtp.frais.update', $fraisCategory->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $fraisCategory->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('code') is-invalid @enderror" 
                                           id="code" 
                                           name="code" 
                                           value="{{ old('code', $fraisCategory->code) }}" 
                                           required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Le code sera automatiquement converti en majuscules</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3">{{ old('description', $fraisCategory->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="default_amount" class="form-label">Montant par défaut (FCFA) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control @error('default_amount') is-invalid @enderror" 
                                           id="default_amount" 
                                           name="default_amount" 
                                           value="{{ old('default_amount', $fraisCategory->default_amount) }}" 
                                           min="0" 
                                           step="0.01" 
                                           required>
                                    @error('default_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_deadline_days" class="form-label">Délai de paiement (jours) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control @error('payment_deadline_days') is-invalid @enderror" 
                                           id="payment_deadline_days" 
                                           name="payment_deadline_days" 
                                           value="{{ old('payment_deadline_days', $fraisCategory->payment_deadline_days) }}" 
                                           min="1" 
                                           max="365" 
                                           required>
                                    @error('payment_deadline_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icône (classe CSS)</label>
                                    <input type="text" 
                                           class="form-control @error('icon') is-invalid @enderror" 
                                           id="icon" 
                                           name="icon" 
                                           value="{{ old('icon', $fraisCategory->icon) }}" 
                                           placeholder="Ex: fas fa-money-bill">
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Utilisez les classes FontAwesome (ex: fas fa-money-bill)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="color" class="form-label">Couleur</label>
                                    <select class="form-select @error('color') is-invalid @enderror" 
                                            id="color" 
                                            name="color">
                                        <option value="">Couleur par défaut</option>
                                        <option value="primary" {{ old('color', $fraisCategory->color) == 'primary' ? 'selected' : '' }}>Bleu (Primary)</option>
                                        <option value="success" {{ old('color', $fraisCategory->color) == 'success' ? 'selected' : '' }}>Vert (Success)</option>
                                        <option value="info" {{ old('color', $fraisCategory->color) == 'info' ? 'selected' : '' }}>Cyan (Info)</option>
                                        <option value="warning" {{ old('color', $fraisCategory->color) == 'warning' ? 'selected' : '' }}>Orange (Warning)</option>
                                        <option value="danger" {{ old('color', $fraisCategory->color) == 'danger' ? 'selected' : '' }}>Rouge (Danger)</option>
                                        <option value="secondary" {{ old('color', $fraisCategory->color) == 'secondary' ? 'selected' : '' }}>Gris (Secondary)</option>
                                    </select>
                                    @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('is_mandatory') is-invalid @enderror" 
                                       type="checkbox" 
                                       id="is_mandatory" 
                                       name="is_mandatory" 
                                       value="1" 
                                       {{ old('is_mandatory', $fraisCategory->is_mandatory) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_mandatory">
                                    <strong>Frais obligatoire</strong>
                                </label>
                                @error('is_mandatory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Les frais obligatoires doivent être configurés pour toutes les classes</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Mettre à jour
                                </button>
                                <a href="{{ route('esbtp.frais.index') }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-1"></i>Annuler
                                </a>
                            </div>
                            <div>
                                @if(!$fraisCategory->is_mandatory)
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteCategory()">
                                        <i class="fas fa-trash me-1"></i>Supprimer
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>

                    @if(!$fraisCategory->is_mandatory)
                        <form id="deleteForm" action="{{ route('esbtp.frais.destroy', $fraisCategory->id) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>
            </div>

            <!-- Information sur les variants -->
            @if($fraisCategory->variants()->exists())
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Variants de cette catégorie
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Montant</th>
                                        <th>Défaut</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fraisCategory->variants as $variant)
                                        <tr>
                                            <td>
                                                <strong>{{ $variant->name }}</strong>
                                                @if($variant->description)
                                                    <br><small class="text-muted">{{ $variant->description }}</small>
                                                @endif
                                            </td>
                                            <td>{{ number_format($variant->amount, 0, ',', ' ') }} FCFA</td>
                                            <td>
                                                @if($variant->is_default)
                                                    <span class="badge bg-success">Défaut</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $variant->is_active ? 'success' : 'secondary' }}">
                                                    {{ $variant->is_active ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-2">
                            <a href="{{ route('esbtp.frais.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-cogs me-1"></i>Gérer les variants
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteCategory() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie de frais ?\n\nCette action supprimera également tous les frais configurés pour cette catégorie.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endpush