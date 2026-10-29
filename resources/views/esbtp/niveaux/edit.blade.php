@extends('layouts.app')

@section('title', 'Modifier le niveau d\'études : ' . $niveau->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Modifier le niveau d'études</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn btn-default btn-sm">
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
                    
                    <form action="{{ route('esbtp.niveaux-etudes.update', $niveau) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="name">Nom du niveau d'études <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $niveau->name) }}" required>
                            <small class="form-text text-muted">Le nom complet du niveau d'études (ex: Première année BTS, Licence 1, etc.)</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="code">Code du niveau d'études <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $niveau->code) }}" required>
                            <small class="form-text text-muted">Un code court et unique pour le niveau d'études (ex: BTS1, L1, etc.)</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Type de diplôme <span class="text-danger">*</span></label>
                            <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Sélectionnez un type de diplôme</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ old('type', $niveau->type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Le type de diplôme préparé (BTS, Licence, Master, etc.)</small>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Année du niveau <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ old('year', $niveau->year) }}" min="1" max="7" required>
                            <small class="form-text text-muted">L'année du niveau d'études (ex: 1 pour première année, 2 pour deuxième année, etc.)</small>
                            @error('year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $niveau->description) }}</textarea>
                            <small class="form-text text-muted">Une description détaillée du niveau d'études (optionnel).</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $niveau->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Niveau d'études actif</label>
                            </div>
                            <small class="form-text text-muted">Un niveau d'études inactif ne pourra pas être sélectionné pour de nouvelles inscriptions.</small>
                        </div>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="{{ route('esbtp.niveaux-etudes.show', $niveau) }}" class="btn btn-secondary">
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