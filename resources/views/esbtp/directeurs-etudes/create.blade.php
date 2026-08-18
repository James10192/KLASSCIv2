@extends('layouts.app')

@section('title', 'Créer un directeur des études')

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl" style="background-color: var(--primary); color: white; border-radius: var(--radius-medium);">
        <h1 style="color:white;margin:0;">Nouveau directeur des études</h1>
        <p style="color:rgba(255,255,255,.8);margin:6px 0 0;">Compte pédagogique sans accès finance ni système.</p>
    </div>

    <form method="POST" action="{{ route('esbtp.directeurs-etudes.store') }}" class="card-moderne" style="padding: var(--space-xl);">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom complet</label>
                <input class="form-control" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone</label>
                <input class="form-control" name="telephone" value="{{ old('telephone') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Spécialité</label>
                <input class="form-control" name="specialite" value="{{ old('specialite') }}">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn-acasi" type="submit">Créer</button>
            <a class="btn-acasi secondary" href="{{ route('esbtp.personnel.unified.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
