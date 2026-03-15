@extends('layouts.app')

@section('title', 'Créer une nouvelle salle de classe')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Créer une nouvelle salle de classe</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.salles.index') }}" class="btn btn-default btn-sm">
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
                    
                    <form action="{{ route('esbtp.salles.store') }}" method="POST">
                        @csrf
                        
                        <!-- Informations de base -->
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Informations de base</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Nom de la salle <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="ex: Salle A, Amphi B" required>
                                            <small class="form-text text-muted">Le nom complet de la salle</small>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="code">Code de la salle <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="ex: SA1, AB" required>
                                            <small class="form-text text-muted">Un code court et unique pour la salle</small>
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type">Type de salle <span class="text-danger">*</span></label>
                                            <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                                                <option value="">Sélectionnez un type</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Le type de salle (Amphithéâtre, Salle de cours, etc.)</small>
                                            @error('type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="capacity">Capacité <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 0) }}" min="0" required>
                                            <small class="form-text text-muted">La capacité d'accueil de la salle (nombre de places)</small>
                                            @error('capacity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Localisation -->
                        <div class="card card-outline card-info mt-4">
                            <div class="card-header">
                                <h3 class="card-title">Localisation</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="building">Bâtiment</label>
                                            <input type="text" class="form-control @error('building') is-invalid @enderror" id="building" name="building" value="{{ old('building') }}" placeholder="ex: Bloc A, Bâtiment Principal">
                                            <small class="form-text text-muted">Le bâtiment où se trouve la salle (optionnel)</small>
                                            @error('building')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="floor">Étage <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('floor') is-invalid @enderror" id="floor" name="floor" value="{{ old('floor', 0) }}" required>
                                            <small class="form-text text-muted">L'étage où se trouve la salle (0 pour rez-de-chaussée, -1 pour sous-sol, etc.)</small>
                                            @error('floor')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations supplémentaires -->
                        <div class="card card-outline card-secondary mt-4">
                            <div class="card-header">
                                <h3 class="card-title">Informations supplémentaires</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                    <small class="form-text text-muted">Une description détaillée de la salle (équipements disponibles, usage spécifique, etc.)</small>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Salle active</label>
                                    </div>
                                    <small class="form-text text-muted">Une salle inactive ne pourra pas être sélectionnée pour de nouvelles inscriptions ou planification de cours.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group text-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                            <a href="{{ route('esbtp.salles.index') }}" class="btn btn-secondary">
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