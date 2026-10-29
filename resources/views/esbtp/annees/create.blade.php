@extends('layouts.app')

@section('title', 'Créer une nouvelle année universitaire')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Créer une nouvelle année universitaire</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Afficher les erreurs de validation -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.annees-universitaires.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nom de l'année universitaire <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="ex: 2023-2024" required>
                            <small class="form-text text-muted">Le nom complet de l'année universitaire (ex: 2023-2024)</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="start_date" class="form-label">Date de rentrée
                                <span data-bs-toggle="tooltip" title="Cette date sert de référence pour le calcul des échéances et des rappels de paiement." style="cursor: help; color: #0ea5e9;">&#9432;</span>
                            </label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                            <small class="form-text text-muted">La date de début de l'année universitaire</small>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="end_date">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                            <small class="form-text text-muted">La date de fin de l'année universitaire</small>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Une description détaillée de l'année universitaire (optionnel)</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Année universitaire active</label>
                            </div>
                            <small class="form-text text-muted">Une année universitaire inactive ne pourra pas être sélectionnée pour de nouvelles inscriptions.</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_current" name="is_current" value="1" {{ old('is_current') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_current">Définir comme année universitaire en cours</label>
                            </div>
                            <small class="form-text text-muted">L'année universitaire en cours est celle qui sera sélectionnée par défaut pour les nouvelles inscriptions.</small>
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                            <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
