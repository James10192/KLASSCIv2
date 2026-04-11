@extends('layouts.app')

@section('title', 'Modifier une filière - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-graduation-cap me-2"></i>Modifier la Filière</h1>
                <p class="header-subtitle">{{ $filiere->name }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.filieres.show', $filiere) }}" class="btn-acasi info">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
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

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Formulaire -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-edit"></i>
                    Modifier les informations
                </div>
                <div class="main-card-subtitle">Modifiez les détails de la filière {{ $filiere->name }}</div>
            </div>
            <div class="main-card-body">
                <form action="{{ route('esbtp.filieres.update', $filiere) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nom de la filière</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $filiere->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $filiere->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $filiere->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input @error('is_active') is-invalid @enderror" id="is_active" name="is_active" value="1" {{ old('is_active', $filiere->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Filière active</label>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if(\App\Helpers\SettingsHelper::get('tronc_commun_enabled', false))
                    <div class="main-card mb-4" style="border-left: 3px solid var(--primary, #0453cb);">
                        <div class="main-card-body">
                            <h6 class="mb-3"><i class="fas fa-code-branch me-2 text-primary"></i>Tronc Commun / Spécialisation</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="is_tronc_commun" name="is_tronc_commun" value="1"
                                               {{ old('is_tronc_commun', $filiere->is_tronc_commun) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_tronc_commun">
                                            Cette filière est un tronc commun
                                        </label>
                                    </div>
                                    <small class="text-muted">Les étudiants choisiront une spécialisation (filière enfant) après le(s) semestre(s) de tronc commun</small>
                                </div>
                                <div class="col-md-6 mb-3" id="semestres_tc_container" style="{{ old('is_tronc_commun', $filiere->is_tronc_commun) ? '' : 'display:none;' }}">
                                    <label for="semestres_tronc_commun" class="form-label">Nombre de semestres tronc commun</label>
                                    <input type="number" class="form-control" id="semestres_tronc_commun" name="semestres_tronc_commun"
                                           value="{{ old('semestres_tronc_commun', $filiere->semestres_tronc_commun ?? 1) }}"
                                           min="1" max="4">
                                </div>
                            </div>
                            @if($filiere->is_tronc_commun && $filiere->options->count() > 0)
                            <div class="mt-2">
                                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Spécialisations disponibles :</small>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @foreach($filiere->options as $spec)
                                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $spec->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div class="row mt-3" id="parent_id_container" style="{{ old('is_tronc_commun', $filiere->is_tronc_commun) ? 'display:none;' : '' }}">
                                <div class="col-md-6 mb-3">
                                    <label for="parent_id" class="form-label">Filière parent (Tronc Commun)</label>
                                    <select class="form-control select2" id="parent_id" name="parent_id">
                                        <option value="">-- Aucune (filière indépendante) --</option>
                                        @foreach($filieres->where('is_tronc_commun', true) as $f)
                                            <option value="{{ $f->id }}" {{ old('parent_id', $filiere->parent_id) == $f->id ? 'selected' : '' }}>
                                                {{ $f->name }} ({{ $f->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Sélectionnez la filière tronc commun dont cette filière est une spécialisation</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('esbtp.filieres.show', $filiere) }}" class="btn btn-secondary">Annuler</a>
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

@section('scripts')
<script>
    $(document).ready(function() {
        // Toggle semestres tronc commun + parent_id
        $('#is_tronc_commun').on('change', function() {
            $('#semestres_tc_container').toggle(this.checked);
            $('#parent_id_container').toggle(!this.checked);
            if (this.checked) {
                $('#parent_id').val('').trigger('change');
            }
        });

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
            if ($('#code').val() === '' || $('#code').val() === '{{ $filiere->code }}') {
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