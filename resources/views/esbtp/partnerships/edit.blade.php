@extends('layouts.app')

@section('title', 'Modifier un Partenariat')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Modifier le Partenariat: {{ $partnership->name }}</h3>
                </div>
                <!-- /.card-header -->
                
                <!-- form start -->
                <form method="POST" action="{{ route('esbtp.partnerships.update', $partnership->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Nom du partenariat -->
                                <div class="form-group">
                                    <label for="name">Nom du partenariat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $partnership->name) }}" required placeholder="Entrez le nom du partenariat">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Type de partenariat -->
                                <div class="form-group">
                                    <label for="type">Type de partenariat <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('type') is-invalid @enderror" id="type" name="type" required>
                                        <option value="">Sélectionnez un type</option>
                                        <option value="Académique" {{ old('type', $partnership->type) == 'Académique' ? 'selected' : '' }}>Académique</option>
                                        <option value="Industriel" {{ old('type', $partnership->type) == 'Industriel' ? 'selected' : '' }}>Industriel</option>
                                        <option value="Gouvernemental" {{ old('type', $partnership->type) == 'Gouvernemental' ? 'selected' : '' }}>Gouvernemental</option>
                                        <option value="ONG" {{ old('type', $partnership->type) == 'ONG' ? 'selected' : '' }}>ONG</option>
                                        <option value="International" {{ old('type', $partnership->type) == 'International' ? 'selected' : '' }}>International</option>
                                        <option value="Recherche" {{ old('type', $partnership->type) == 'Recherche' ? 'selected' : '' }}>Recherche</option>
                                        <option value="Autre" {{ old('type', $partnership->type) == 'Autre' ? 'selected' : '' }}>Autre</option>
                                    </select>
                                    @error('type')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Personne de contact -->
                                <div class="form-group">
                                    <label for="contact_person">Personne de contact</label>
                                    <input type="text" class="form-control @error('contact_person') is-invalid @enderror" id="contact_person" name="contact_person" value="{{ old('contact_person', $partnership->contact_person) }}" placeholder="Nom de la personne de contact">
                                    @error('contact_person')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Email de contact -->
                                <div class="form-group">
                                    <label for="email">Email de contact</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $partnership->email) }}" placeholder="Email de contact">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Téléphone de contact -->
                                <div class="form-group">
                                    <label for="phone">Téléphone de contact</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $partnership->phone) }}" placeholder="Téléphone de contact">
                                    @error('phone')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Adresse -->
                                <div class="form-group">
                                    <label for="address">Adresse</label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $partnership->address) }}" placeholder="Adresse du partenaire">
                                    @error('address')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Logo du partenariat -->
                                <div class="form-group">
                                    <label for="logo">Logo du partenariat</label>
                                    @if($partnership->logo)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $partnership->logo) }}" alt="{{ $partnership->name }}" class="img-thumbnail" style="max-height: 100px;">
                                            <div class="custom-control custom-checkbox mt-2">
                                                <input type="checkbox" class="custom-control-input" id="remove_logo" name="remove_logo" value="1">
                                                <label class="custom-control-label" for="remove_logo">Supprimer le logo actuel</label>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input @error('logo') is-invalid @enderror" id="logo" name="logo">
                                            <label class="custom-file-label" for="logo">Choisir un nouveau fichier</label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Formats acceptés: jpeg, png, jpg, gif. Taille max: 2Mo</small>
                                    @error('logo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                
                                <!-- Statut actif/inactif -->
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $partnership->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Partenariat actif</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Site web -->
                        <div class="form-group">
                            <label for="website">Site web</label>
                            <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website', $partnership->website) }}" placeholder="https://www.exemple.com">
                            @error('website')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        
                        <!-- Description du partenariat -->
                        <div class="form-group">
                            <label for="description">Description du partenariat</label>
                            <textarea data-editeur-riche class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Description détaillée du partenariat">{{ \App\Support\TexteRiche::pourEditeur(old('description', $partnership->description)) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- /.card-body -->
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning">Mettre à jour</button>
                        <a href="{{ route('esbtp.partnerships.show', $partnership->id) }}" class="btn btn-default">Annuler</a>
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