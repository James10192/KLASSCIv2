@extends('layouts.app')

@section('title', 'Modifier le responsable scolarite')

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl" style="background-color: var(--primary); color: white; border-radius: var(--radius-medium);">
        <h1 style="color:white;margin:0;">Modifier {{ $responsable->name }}</h1>
    </div>

    <form method="POST" action="{{ route('esbtp.responsables-scolarite.update', $responsable) }}" class="card-moderne" style="padding: var(--space-xl);">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom complet</label>
                <input class="form-control" name="name" value="{{ old('name', $responsable->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email', $responsable->email) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone</label>
                <input class="form-control" name="telephone" value="{{ old('telephone', $responsable->telephone) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Spécialité</label>
                <input class="form-control" name="specialite" value="{{ old('specialite', $responsable->specialite) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nouveau mot de passe</label>
                <input class="form-control" type="password" name="password">
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirmation</label>
                <input class="form-control" type="password" name="password_confirmation">
            </div>
            <div class="col-md-6">
                <label class="form-label">Statut</label>
                <select class="form-select" name="is_active">
                    <option value="1" @selected(old('is_active', $responsable->is_active))>Actif</option>
                    <option value="0" @selected(! old('is_active', $responsable->is_active))>Inactif</option>
                </select>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn-acasi" type="submit">Enregistrer</button>
            <a class="btn-acasi secondary" href="{{ route('esbtp.personnel.unified.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
