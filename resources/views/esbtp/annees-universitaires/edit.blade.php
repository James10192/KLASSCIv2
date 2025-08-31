@extends('layouts.app')

@section('title', 'Modifier l\'année universitaire : ' . $anneesUniversitaire->name)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Modifier l'année universitaire</h1>
                <p class="header-subtitle">{{ $anneesUniversitaire->name }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
                @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-center glass-alert mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('esbtp.annees-universitaires.update', $anneesUniversitaire) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-lg rounded-4 premium-glass mb-4">
                                <div class="card-header bg-white border-0 rounded-top-4">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-calendar-alt me-2"></i> Informations de l'année universitaire
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Nom de l'année universitaire <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $anneesUniversitaire->name) }}" placeholder="ex: 2023-2024" required>
                                            <small class="form-text text-muted">Le nom complet de l'année universitaire (ex: 2023-2024)</small>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Description détaillée de l'année universitaire">{{ old('description', $anneesUniversitaire->description) }}</textarea>
                                            <small class="form-text text-muted">Une description détaillée de l'année universitaire (optionnel)</small>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="start_date" class="form-label">Date de rentrée <span class="text-danger">*</span>
                                                <span data-bs-toggle="tooltip" title="Cette date sert de référence pour le calcul des échéances et des rappels de paiement." style="cursor: help; color: #0ea5e9;">&#9432;</span>
                                            </label>
                                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $anneesUniversitaire->start_date->format('Y-m-d')) }}" required>
                                            <small class="form-text text-muted">La date de début de l'année universitaire</small>
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $anneesUniversitaire->end_date->format('Y-m-d')) }}" required>
                                            <small class="form-text text-muted">La date de fin de l'année universitaire</small>
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $anneesUniversitaire->is_active) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">Année universitaire active</label>
                                            </div>
                                            <small class="form-text text-muted">Une année universitaire inactive ne pourra pas être sélectionnée pour de nouvelles inscriptions.</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" id="is_current" name="is_current" value="1" {{ old('is_current', optional($anneesUniversitaire)->is_current) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_current">Définir comme année universitaire en cours</label>
                                            </div>
                                            <small class="form-text text-muted">L'année universitaire en cours est celle qui sera sélectionnée par défaut pour les nouvelles inscriptions.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('esbtp.annees-universitaires.show', $anneesUniversitaire) }}" class="btn btn-lg btn-secondary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
