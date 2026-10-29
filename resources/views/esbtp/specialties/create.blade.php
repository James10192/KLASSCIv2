@extends('layouts.app')

@section('title', 'Créer une Spécialité')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Nouvelle Spécialité</h3>
                </div>
                <!-- /.card-header -->
                
                <!-- form start -->
                <form method="POST" action="{{ route('esbtp.specialties.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Nom de la spécialité -->
                                <div class="form-group">
                                    <label for="name">Nom de la spécialité <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Entrez le nom de la spécialité (ex: Génie Civil)">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Code de la spécialité -->
                                <div class="form-group">
                                    <label for="code">Code de la spécialité <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required placeholder="Entrez le code de la spécialité (ex: GC)">
                                    @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Cycle associé -->
                                <div class="form-group">
                                    <label for="cycle_id">Cycle de formation <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('cycle_id') is-invalid @enderror" id="cycle_id" name="cycle_id" required>
                                        <option value="">Sélectionnez un cycle</option>
                                        @foreach($cycles as $cycle)
                                            <option value="{{ $cycle->id }}" {{ old('cycle_id', request('cycle_id')) == $cycle->id ? 'selected' : '' }}>
                                                {{ $cycle->name }} ({{ $cycle->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cycle_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Département associé -->
                                <div class="form-group">
                                    <label for="department_id">Département <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                        <option value="">Sélectionnez un département</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }} ({{ $department->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Responsable de la spécialité -->
                                <div class="form-group">
                                    <label for="coordinator_name">Responsable de la spécialité</label>
                                    <input type="text" class="form-control @error('coordinator_name') is-invalid @enderror" id="coordinator_name" name="coordinator_name" value="{{ old('coordinator_name') }}" placeholder="Nom du responsable de la spécialité">
                                    @error('coordinator_name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Statut actif/inactif -->
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Spécialité active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description de la spécialité -->
                        <div class="form-group">
                            <label for="description">Description de la spécialité</label>
                            <textarea data-editeur-riche class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Description détaillée de la spécialité">{{ \App\Support\TexteRiche::pourEditeur(old('description')) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        
                        <!-- Débouchés professionnels -->
                        <div class="form-group">
                            <label for="career_opportunities">Débouchés professionnels</label>
                            <textarea data-editeur-riche class="form-control @error('career_opportunities') is-invalid @enderror" id="career_opportunities" name="career_opportunities" rows="4" placeholder="Débouchés professionnels pour cette spécialité">{{ \App\Support\TexteRiche::pourEditeur(old('career_opportunities')) }}</textarea>
                            @error('career_opportunities')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- /.card-body -->
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('esbtp.specialties.index') }}" class="btn btn-default">Annuler</a>
                    </div>
                </form>
            </div>
            <!-- /.card -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
    {{-- Éditeur riche : l'ancienne initialisation appelait Summernote, Select2 et
         bsCustomFileInput sans qu'aucun ne soit chargé, et plantait à la première ligne.
         Les selects et le champ de fichier restent natifs : hors du périmètre de l'éditeur. --}}
    @include('partials.editeur-riche')
@endsection 