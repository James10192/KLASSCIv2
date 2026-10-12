@extends('auth.layouts.portal')
@php
    $isExpired = session('password_change_reason', 'first_login') === 'expired';
@endphp
@section('title', 'KLASSCI — Sécuriser mon compte')
@section('heading', $isExpired ? 'Renouveler votre mot de passe' : 'Bienvenue sur KLASSCI')
@section('subtitle', $isExpired ? 'Votre accès doit être renouvelé pour continuer.' : 'Personnalisez votre mot de passe pour commencer.')
@section('content')
<div class="portal-security">
    <i class="fas fa-shield-alt" aria-hidden="true"></i>
    {{ $isExpired ? 'Le renouvellement est nécessaire pour protéger les données de votre établissement.' : 'Cette étape est obligatoire lors de votre première connexion.' }}
</div>
<form method="POST" action="{{ route('password.change.update') }}" class="portal-form">
    @csrf
    @foreach(['current_password' => 'Mot de passe actuel', 'password' => 'Nouveau mot de passe', 'password_confirmation' => 'Confirmer le nouveau mot de passe'] as $field => $label)
        <div class="field">
            <label for="{{ $field }}">{{ $label }}</label>
            <div class="input-wrap"><i class="fas fa-lock icon" aria-hidden="true"></i>
                <input id="{{ $field }}" name="{{ $field }}" type="password" required
                    @if($field !== 'current_password') minlength="8" @endif
                    autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}"
                    class="@error($field) is-invalid @enderror" @if($field === 'current_password') autofocus @endif>
                <button class="portal-toggle" type="button" data-password-toggle="{{ $field }}" aria-label="Afficher le mot de passe" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
            </div>
            @error($field)<div class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>
    @endforeach
    <p class="portal-help">8 caractères minimum, avec des lettres et des chiffres. Votre nouveau mot de passe doit être différent de l'ancien.</p>
    <button type="submit" class="btn-login"><i class="fas fa-check-circle" aria-hidden="true"></i> Sécuriser mon compte</button>
</form>
<p class="portal-footnote"><i class="fas fa-info-circle" aria-hidden="true"></i> Vous retrouverez ensuite votre tableau de bord KLASSCI.</p>
@endsection
