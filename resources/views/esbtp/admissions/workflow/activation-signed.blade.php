@extends('layouts.app-public')

@section('title', 'Activer mon espace étudiant - KLASSCI')

@section('styles')
<style>
.mwa{min-height:72vh;display:grid;place-items:center;padding:24px}.mwa-card{width:min(540px,100%);background:#fff;border:1px solid #e6ebf3;border-radius:20px;box-shadow:0 18px 55px rgba(15,23,42,.10);overflow:hidden}.mwa-head{background:linear-gradient(135deg,#0a3d8f,#0453cb);color:#fff;padding:28px}.mwa-head h1{font-size:1.45rem;margin:0 0 7px}.mwa-head p{margin:0;opacity:.88}.mwa-body{padding:26px}.mwa-field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}.mwa-field label{font-weight:800;font-size:.83rem;color:#475569}.mwa-field input{border:1px solid #d9e1ec;border-radius:11px;padding:12px}.mwa-btn{width:100%;border:0;border-radius:11px;padding:12px 16px;background:#0453cb;color:#fff;font-weight:800}.mwa-note{font-size:.82rem;color:#64748b;margin-top:14px}.mwa-chip{display:inline-flex;align-items:center;gap:6px;margin-bottom:14px;padding:6px 10px;border-radius:999px;background:#eef6ff;color:#0453cb;font-size:.78rem;font-weight:800}
</style>
@endsection

@section('content')
<div class="mwa">
    <div class="mwa-card">
        <div class="mwa-head">
            <h1>Activez votre espace étudiant</h1>
            <p>{{ $workflow->candidature?->prenoms }} {{ $workflow->candidature?->nom }} · {{ $workflow->candidature?->reference_publique }}</p>
        </div>
        <div class="mwa-body">
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="mwa-chip">Lien sécurisé WhatsApp</div>
            <p>Choisissez votre mot de passe pour ouvrir votre espace étudiant. Aucun mot de passe provisoire n'est envoyé dans le message.</p>

            <form method="POST" action="{{ $submitUrl }}">
                @csrf
                <div class="mwa-field">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="password" minlength="8" required autocomplete="new-password">
                </div>
                <div class="mwa-field">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                </div>
                <button class="mwa-btn" type="submit">Activer mon espace</button>
            </form>

            <div class="mwa-note">Ce lien est personnel, temporaire et devient inutilisable dès que votre espace est activé.</div>
        </div>
    </div>
</div>
@endsection
