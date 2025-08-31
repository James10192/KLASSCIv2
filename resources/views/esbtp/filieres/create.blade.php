@extends('layouts.app')

@section('title', 'Ajouter une filière - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-graduation-cap me-2"></i>Nouvelle Filière</h1>
                <p class="header-subtitle">Créer une nouvelle filière dans le système</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.filieres.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

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

        <!-- Formulaire -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-plus-circle"></i>
                    Informations de la filière
                </div>
                <div class="main-card-subtitle">Remplissez les informations de base de la nouvelle filière</div>
            </div>
            <div class="main-card-body">
                <form action="{{ route('esbtp.filieres.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nom de la filière</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required>
                            @error('code')
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
                        <label for="parent_id" class="form-label">Filière parente (optionnel)</label>
                        <select class="form-control @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                            <option value="">Aucune (Filière principale)</option>
                            @foreach($filieres as $f)
                                <option value="{{ $f->id }}" {{ old('parent_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="niveau_ids" class="form-label">Niveaux d'études associés</label>
                        <select class="form-control select2 @error('niveau_ids') is-invalid @enderror" id="niveau_ids" name="niveau_ids[]" multiple>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ in_array($niveau->id, old('niveau_ids', [])) ? 'selected' : '' }}>{{ $niveau->name }}</option>
                            @endforeach
                        </select>
                        @error('niveau_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="matiere_ids" class="form-label">Matières associées</label>
                        <select class="form-control select2 @error('matiere_ids') is-invalid @enderror" id="matiere_ids" name="matiere_ids[]" multiple>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}" {{ in_array($matiere->id, old('matiere_ids', [])) ? 'selected' : '' }}>{{ $matiere->name }}</option>
                            @endforeach
                        </select>
                        @error('matiere_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input @error('is_active') is-invalid @enderror" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Filière active</label>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-secondary">Annuler</button>
                        <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
                            <i class="fas fa-save"></i> Créer la filière
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Configuration Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionnez...',
            allowClear: true,
            closeOnSelect: false
        });

        // Génération automatique du code basé sur le nom
        $('#name').on('blur', function() {
            if ($('#code').val() === '') {
                let words = $(this).val().split(' ');
                let code = '';

                words.forEach(function(word) {
                    if (word.length > 0) {
                        code += word.charAt(0).toUpperCase();
                    }
                });

                if (code.length < 2 && words[0] && words[0].length > 1) {
                    code += words[0].charAt(1).toUpperCase();
                }

                $('#code').val(code);
            }
        });
    });
</script>
@endsection