@extends('layouts.app')

@section('title', 'Activer mon compte responsable')

@section('content')
<div class="container py-5" style="max-width: 620px">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-2">Bienvenue dans votre espace responsable</h1>
            <p class="text-muted">Bonjour {{ $parent->prenoms }}. Ce compte est personnel : il n'utilise pas le mot de passe de l'étudiant.</p>
            <form method="post" action="{{ route('esbtp.famille.invitation.activer', $token) }}">
                @csrf
                <div class="mb-3">
                    <label for="password" class="form-label">Créer un mot de passe</label>
                    <input id="password" name="password" type="password" class="form-control" minlength="12" required autocomplete="new-password">
                    <div class="form-text">12 caractères minimum. Évitez tout mot de passe générique.</div>
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirmer votre mot de passe</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="12" required autocomplete="new-password">
                </div>
                @if($errors->any())
                    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                @endif
                <button type="submit" class="btn btn-primary w-100">Activer mon espace responsable</button>
            </form>
            <p class="text-muted small mt-4 mb-0">Ce lien à usage unique expire 48 heures après sa création. Contactez votre établissement si le lien ne fonctionne plus.</p>
        </div>
    </div>
</div>
@endsection
