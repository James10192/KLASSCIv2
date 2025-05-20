@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Nouvelle catégorie de frais</h2>
    <div class="alert alert-info">
        Après avoir créé la catégorie, vous pourrez ajouter des règles de paramétrage avancé (montants par filière, niveau, année, échéancier, etc.).
    </div>
    <form action="{{ route('esbtp.fee-categories.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label for="code" class="form-label">Code</label>
            <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_mandatory" name="is_mandatory" value="1" {{ old('is_mandatory') ? 'checked' : '' }}>
                <label class="form-check-label" for="is_mandatory">
                    <span class="badge bg-primary">Obligatoire</span> (ce frais sera imposé à tous les étudiants concernés)
                </label>
                <div class="form-text">Laisser décoché pour un service optionnel (cantine, transport, etc.).</div>
            </div>
        </div>
        <div class="mb-3">
            <label for="default_amount" class="form-label">Prix par défaut (optionnel)</label>
            <input type="number" step="0.01" name="default_amount" id="default_amount" class="form-control" value="{{ old('default_amount') }}">
        </div>
        <div class="mb-3">
            <label for="is_active" class="form-label">Statut</label>
            <select name="is_active" id="is_active" class="form-select">
                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Actif</option>
                <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>Inactif</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('esbtp.fee-categories.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
