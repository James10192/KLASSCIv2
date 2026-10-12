@extends('auth.layouts.portal')
@section('title', 'KLASSCI — Mot de passe oublié')
@section('heading', 'Mot de passe oublié ?')
@section('subtitle', 'Nous vous aidons à retrouver votre accès KLASSCI.')
@section('content')
<p class="portal-help">Indiquez l'adresse e-mail liée à votre compte. Si elle est reconnue, vous recevrez les instructions de réinitialisation.</p>
<form action="{{ route('password.email') }}" method="POST" class="portal-form">
    @csrf
    <div class="field">
        <label for="email">Adresse e-mail du compte</label>
        <div class="input-wrap">
            <i class="fas fa-envelope icon" aria-hidden="true"></i>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autofocus
                value="{{ old('email') }}" placeholder="nom@etablissement.com" required class="@error('email') is-invalid @enderror">
        </div>
        @error('email')<div class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
    </div>
    <div class="portal-security"><i class="fas fa-shield-alt" aria-hidden="true"></i> Pour votre sécurité, ne partagez jamais le lien reçu par e-mail.</div>
    <button type="submit" class="btn-login"><i class="fas fa-paper-plane" aria-hidden="true"></i> Envoyer le lien de réinitialisation</button>
</form>
<a href="{{ route('login') }}" class="portal-link"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retour à la connexion</a>
@endsection
