@extends('auth.layouts.portal')
@section('title', 'KLASSCI — Nouveau mot de passe')
@section('heading', 'Créer un nouveau mot de passe')
@section('subtitle', 'Protégez votre compte avec un accès personnel.')
@section('content')
<div class="portal-security"><i class="fas fa-lock" aria-hidden="true"></i> Choisissez un mot de passe robuste et différent de vos anciens mots de passe.</div>
<form action="{{ route('password.update') }}" method="POST" class="portal-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="field">
        <label for="email">Adresse e-mail</label>
        <div class="input-wrap"><i class="fas fa-envelope icon" aria-hidden="true"></i>
            <input id="email" name="email" type="email" value="{{ old('email', $email ?? '') }}" required autocomplete="email" class="@error('email') is-invalid @enderror">
        </div>
        @error('email')<div class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
    </div>
    @foreach(['password' => 'Nouveau mot de passe', 'password_confirmation' => 'Confirmer le nouveau mot de passe'] as $field => $label)
        <div class="field">
            <label for="{{ $field }}">{{ $label }}</label>
            <div class="input-wrap"><i class="fas fa-lock icon" aria-hidden="true"></i>
                <input id="{{ $field }}" name="{{ $field }}" type="password" required minlength="8" autocomplete="new-password" placeholder="8 caractères minimum" class="@error($field) is-invalid @enderror">
                <button class="portal-toggle" type="button" data-password-toggle="{{ $field }}" aria-label="Afficher le mot de passe" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
            </div>
            @error($field)<div class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>
    @endforeach
    <p class="portal-help">Utilisez au moins 8 caractères, avec des lettres et des chiffres.</p>
    <button class="btn-login" type="submit"><i class="fas fa-check-circle" aria-hidden="true"></i> Enregistrer mon nouveau mot de passe</button>
</form>
<a href="{{ route('login') }}" class="portal-link"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retour à la connexion</a>
@endsection
