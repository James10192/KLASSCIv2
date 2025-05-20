@extends('layouts.app')

@section('title', 'Nouveau fournisseur')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-10">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white border-bottom-0 d-flex align-items-center">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-truck me-2 text-primary"></i> Nouveau fournisseur</h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('esbtp.comptabilite.fournisseurs.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control" value="{{ old('nom') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="text" name="telephone" id="telephone" class="form-control" value="{{ old('telephone') }}">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" name="adresse" id="adresse" class="form-control" value="{{ old('adresse') }}">
                        </div>
                        <div class="mb-3">
                            <label for="est_actif" class="form-label">Statut</label>
                            <select name="est_actif" id="est_actif" class="form-select">
                                <option value="1" {{ old('est_actif', '1') == '1' ? 'selected' : '' }}>Actif</option>
                                <option value="0" {{ old('est_actif') == '0' ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('esbtp.comptabilite.fournisseurs') }}" class="btn btn-light">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
