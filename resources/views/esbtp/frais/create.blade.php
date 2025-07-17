@extends('layouts.app')

@section('title', 'Nouvelle Catégorie de Frais - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Nouvelle Catégorie de Frais</h1>
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

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        Informations de la Catégorie
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('esbtp.frais.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           required 
                                           placeholder="Ex: Frais de transport">
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
                                           value="{{ old('code') }}" 
                                           required 
                                           placeholder="Ex: TRANSPORT">
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
                                      rows="3" 
                                      placeholder="Description de la catégorie de frais...">{{ old('description') }}</textarea>
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
                                           value="{{ old('default_amount') }}" 
                                           min="0" 
                                           step="0.01" 
                                           required 
                                           placeholder="0">
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
                                           value="{{ old('payment_deadline_days', 30) }}" 
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
                                           value="{{ old('icon') }}" 
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
                                        <option value="primary" {{ old('color') == 'primary' ? 'selected' : '' }}>Bleu (Primary)</option>
                                        <option value="success" {{ old('color') == 'success' ? 'selected' : '' }}>Vert (Success)</option>
                                        <option value="info" {{ old('color') == 'info' ? 'selected' : '' }}>Cyan (Info)</option>
                                        <option value="warning" {{ old('color') == 'warning' ? 'selected' : '' }}>Orange (Warning)</option>
                                        <option value="danger" {{ old('color') == 'danger' ? 'selected' : '' }}>Rouge (Danger)</option>
                                        <option value="secondary" {{ old('color') == 'secondary' ? 'selected' : '' }}>Gris (Secondary)</option>
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
                                       {{ old('is_mandatory') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_mandatory">
                                    <strong>Frais obligatoire</strong>
                                </label>
                                @error('is_mandatory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Les frais obligatoires doivent être configurés pour toutes les classes</div>
                            </div>
                        </div>

                        <!-- Exemples prédéfinis -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb me-1"></i> Exemples de catégories courantes :</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li><strong>Transport :</strong> Code TRANSPORT, avec variants par arrêt</li>
                                        <li><strong>Cantine :</strong> Code CANTINE, avec variants par menu</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li><strong>Hébergement :</strong> Code HEBERGEMENT, avec variants par type</li>
                                        <li><strong>Matériel :</strong> Code MATERIEL, optionnel par filière</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Créer la catégorie
                            </button>
                            <a href="{{ route('esbtp.frais.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Aide -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Comment utiliser les variants ?
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Une fois la catégorie créée, vous pourrez :</p>
                    <ul class="mb-0">
                        <li>Ajouter des <strong>variants</strong> (ex: différents arrêts de transport, types de menus)</li>
                        <li>Configurer les montants par classe via l'interface matricielle</li>
                        <li>Gérer les relances automatiques pour les retards de paiement</li>
                        <li>Suivre les statistiques de paiement par catégorie</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-générer le code basé sur le nom
document.getElementById('name').addEventListener('input', function() {
    const codeField = document.getElementById('code');
    if (!codeField.value) {
        const name = this.value;
        const code = name.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 20);
        codeField.value = code;
    }
});
</script>
@endpush