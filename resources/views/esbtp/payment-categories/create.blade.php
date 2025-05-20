@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Nouvelle catégorie de paiements</h2>
    <form action="{{ route('esbtp.payment-categories.store') }}" method="POST">
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
            <label for="is_active" class="form-label">Statut</label>
            <select name="is_active" id="is_active" class="form-select">
                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Actif</option>
                <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>Inactif</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('esbtp.payment-categories.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
