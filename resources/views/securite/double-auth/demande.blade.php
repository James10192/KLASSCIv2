@extends('layouts.app')

@section('title', 'Code de vérification - KLASSCI')

@push('styles')
<style>
    .dm-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 2.25rem; max-width: 440px; margin: 3rem auto; box-shadow: 0 4px 16px rgba(4,83,203,.06); text-align: center; }
    .dm-icone { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 1.1rem; }
    .dm-card h1 { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin: 0 0 .5rem; }
    .dm-card p { font-size: .89rem; color: #64748b; line-height: 1.6; margin: 0 0 1.5rem; }
    .dm-input { font-family: 'Courier New', monospace; font-size: 1.5rem; letter-spacing: .35em; text-align: center; width: 100%; max-width: 240px; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 10px; margin: 0 auto .6rem; display: block; }
    .dm-secours { font-size: .82rem; color: #64748b; margin-top: 1.25rem; }
    .dm-secours a { color: #0453cb; }
</style>
@endpush

@section('content')
<div class="dm-card">
    <div class="dm-icone"><i class="fas fa-shield-halved"></i></div>

    <h1>Code de vérification</h1>
    <p>Saisissez les six chiffres affichés par votre application d'authentification.</p>

    <form method="POST" action="{{ route('securite.double-auth.verifier') }}">
        @csrf
        <input type="text" name="code" class="dm-input @error('code') is-invalid @enderror"
               inputmode="numeric" autocomplete="one-time-code" maxlength="9"
               placeholder="000000" required autofocus>

        @error('code')
            <div class="text-danger" style="font-size:.85rem;margin-bottom:.6rem;">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn-acasi primary" style="width:100%;max-width:240px;">
            Continuer
        </button>
    </form>

    <p class="dm-secours">
        Vous n'avez plus votre téléphone ? Saisissez l'un de vos codes de secours dans le même
        champ.
    </p>

    <p class="dm-secours">
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('deconnexion-2fa').submit();">
            Se déconnecter
        </a>
    </p>
    <form id="deconnexion-2fa" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
</div>
@endsection
