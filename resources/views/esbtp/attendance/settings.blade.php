@extends('layouts.app')

@section('title', 'Paramètres d\'Émargement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Paramètres d'Émargement
                    </h5>
                </div>
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

                    <form action="{{ route('esbtp.attendance-codes.settings.update') }}" method="POST">
                        @csrf

                        <!-- Paramètres des Codes -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">Paramètres des Codes</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="code_validity_hours" class="form-label">Durée de validité (heures)</label>
                                            <input type="number" class="form-control" id="code_validity_hours" name="settings[code_validity_hours]"
                                                value="{{ $settings['code_validity_hours'] ?? 24 }}" min="1" max="72">
                                            <small class="text-muted">Durée de validité d'un code après sa génération</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_attempts" class="form-label">Tentatives maximales</label>
                                            <input type="number" class="form-control" id="max_attempts" name="settings[max_attempts]"
                                                value="{{ $settings['max_attempts'] ?? 3 }}" min="1" max="10">
                                            <small class="text-muted">Nombre maximum de tentatives avant blocage</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paramètres de Géolocalisation -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0">Paramètres de Géolocalisation</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="geolocation_required"
                                                    name="settings[geolocation_required]" value="1"
                                                    {{ ($settings['geolocation_required'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="geolocation_required">
                                                    Exiger la géolocalisation
                                                </label>
                                            </div>
                                            <small class="text-muted">Activer pour exiger la position GPS lors de l'émargement</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_distance_meters" class="form-label">Distance maximale (mètres)</label>
                                            <input type="number" class="form-control" id="max_distance_meters"
                                                name="settings[max_distance_meters]" value="{{ $settings['max_distance_meters'] ?? 100 }}"
                                                min="10" max="1000">
                                            <small class="text-muted">Distance maximale autorisée du point d'émargement</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="school_latitude" class="form-label">Latitude de l'établissement</label>
                                            <input type="text" class="form-control" id="school_latitude"
                                                name="settings[school_latitude]" value="{{ $settings['school_latitude'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="school_longitude" class="form-label">Longitude de l'établissement</label>
                                            <input type="text" class="form-control" id="school_longitude"
                                                name="settings[school_longitude]" value="{{ $settings['school_longitude'] ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paramètres Horaires -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">Paramètres Horaires</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="allowed_early_minutes" class="form-label">Minutes autorisées avant le cours</label>
                                            <input type="number" class="form-control" id="allowed_early_minutes"
                                                name="settings[allowed_early_minutes]" value="{{ $settings['allowed_early_minutes'] ?? 30 }}"
                                                min="0" max="60">
                                            <small class="text-muted">Minutes autorisées avant le début du cours pour émarger</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="allowed_late_minutes" class="form-label">Minutes de retard autorisées</label>
                                            <input type="number" class="form-control" id="allowed_late_minutes"
                                                name="settings[allowed_late_minutes]" value="{{ $settings['allowed_late_minutes'] ?? 15 }}"
                                                min="0" max="60">
                                            <small class="text-muted">Minutes de retard autorisées pour émarger</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Enregistrer les paramètres
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Activer/désactiver les champs de géolocalisation
    document.getElementById('geolocation_required').addEventListener('change', function() {
        const fields = ['max_distance_meters', 'school_latitude', 'school_longitude'];
        fields.forEach(field => {
            document.getElementById(field).disabled = !this.checked;
        });
    });

    // Initialiser l'état des champs au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const geoRequired = document.getElementById('geolocation_required').checked;
        const fields = ['max_distance_meters', 'school_latitude', 'school_longitude'];
        fields.forEach(field => {
            document.getElementById(field).disabled = !geoRequired;
        });
    });
</script>
@endpush
@endsection
