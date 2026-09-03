@extends('layouts.app')

@section('title', 'Vos codes de secours - KLASSCI')

@push('styles')
<style>
    .cs-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 2rem; max-width: 620px; margin: 2rem auto; box-shadow: 0 4px 16px rgba(4,83,203,.06); }
    .cs-card h1 { font-size: 1.3rem; font-weight: 700; color: #1e293b; margin: 0 0 .6rem; }
    .cs-intro { font-size: .92rem; color: #475569; line-height: 1.65; margin-bottom: 1.5rem; }
    .cs-grille { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; margin-bottom: 1.5rem; }
    .cs-code { font-family: 'Courier New', monospace; font-size: 1.05rem; letter-spacing: .08em; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: .7rem; text-align: center; color: #1e293b; font-weight: 600; }
    .cs-avert { background: rgba(245,158,11,.08); border-left: 3px solid #f59e0b; padding: .9rem 1.1rem; border-radius: 0 8px 8px 0; font-size: .88rem; color: #78350f; line-height: 1.6; margin-bottom: 1.5rem; }
    @@media print { .cs-noprint { display: none !important; } }
</style>
@endpush

@section('content')
<div class="cs-card">
    <h1>Vos codes de secours</h1>

    <p class="cs-intro">
        Chacun de ces codes vous permet de vous connecter une fois, si vous perdez l'accès à
        votre téléphone. Imprimez-les ou recopiez-les, et rangez-les ailleurs que dans le
        téléphone lui-même.
    </p>

    <div class="cs-avert">
        <strong>Ils ne seront plus affichés.</strong> Nous ne les conservons pas en clair :
        c'est ce qui fait qu'une lecture de notre base ne donne pas accès à votre compte.
        Si vous les perdez tous, il faudra désactiver puis remettre en place la double
        authentification.
    </div>

    <div class="cs-grille">
        @foreach ($codes as $code)
            <div class="cs-code">{{ $code }}</div>
        @endforeach
    </div>

    <div class="cs-noprint" style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <button type="button" class="btn-acasi secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>
        <a href="{{ route('securite.double-auth.reglages') }}" class="btn-acasi primary">
            Je les ai notés
        </a>
    </div>
</div>
@endsection
