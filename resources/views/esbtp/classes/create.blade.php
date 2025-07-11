@extends('layouts.app')

@section('title', 'Créer une classe - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Créer une nouvelle classe</h5>
                    <a href="{{ route('esbtp.student.classes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.classes.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom de la classe <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Ex: 1ère année BTS Génie Civil Option Bâtiment</small>
                            </div>

                            <div class="col-md-6">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Ex: 1BTS-GC-BAT</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="filiere_id" class="form-label">Filière <span class="text-danger">*</span></label>
                                <select class="form-select @error('filiere_id') is-invalid @enderror" id="filiere_id" name="filiere_id" required>
                                    <option value="">Sélectionner une filière</option>
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}" {{ old('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                            {{ $filiere->name }} {{ $filiere->parent ? '(Option de '.$filiere->parent->name.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('filiere_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="niveau_etude_id" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                                <select class="form-select @error('niveau_etude_id') is-invalid @enderror" id="niveau_etude_id" name="niveau_etude_id" required>
                                    <option value="">Sélectionner un niveau</option>
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}" {{ old('niveau_etude_id') == $niveau->id ? 'selected' : '' }}>
                                            {{ $niveau->name }} ({{ $niveau->type }} - Année {{ $niveau->year }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('niveau_etude_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="annee_universitaire_id" class="form-label">Année universitaire <span class="text-danger">*</span></label>
                                <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                                    <option value="">Sélectionner une année</option>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id }}" {{ old('annee_universitaire_id') == $annee->id ? 'selected' : '' }}>
                                            {{ $annee->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('annee_universitaire_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="places_totales" class="form-label">Capacité maximale <span class="text-danger">*</span></label>
                                <input type="number" min="1" class="form-control @error('places_totales') is-invalid @enderror" id="places_totales" name="places_totales" value="{{ old('places_totales', 30) }}" required>
                                @error('places_totales')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('is_active') is-invalid @enderror" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Classe active
                                </label>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Enregistrer la classe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Améliorer les sélecteurs avec select2 si disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $('#filiere_id, #niveau_etude_id, #annee_universitaire_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionner une option',
                allowClear: true
            });
        }

        // Auto-génération du code de classe basé sur le nom
        $('#name').on('blur', function() {
            if ($('#code').val() === '') {
                const name = $(this).val();
                if (name) {
                    // Extraire les premières lettres de chaque mot et les convertir en majuscules
                    const code = name.split(' ')
                        .map(word => word.charAt(0).toUpperCase())
                        .join('');
                    $('#code').val(code);
                }
            }
        });
    });
</script>
@endsection
