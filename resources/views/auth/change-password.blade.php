@extends('layouts.app')
@section('title', 'Sécuriser mon accès — KLASSCI')
@section('styles')
<style>
    .kp-page{max-width:1050px;margin:32px auto 64px;padding:0 18px;font-family:inherit;color:#17233b}
    .kp-hero{position:relative;overflow:hidden;background:linear-gradient(118deg,#06398b 0%,#0453cb 62%,#407cda 100%);border-radius:22px;padding:38px 42px;color:#fff}
    .kp-hero:after{content:"";position:absolute;width:300px;height:300px;border:1px solid rgba(255,255,255,.19);border-radius:50%;right:-80px;top:-110px;box-shadow:0 0 0 46px rgba(255,255,255,.04),0 0 0 104px rgba(255,255,255,.03)}
    .kp-label{display:inline-flex;align-items:center;gap:7px;border:1px solid rgba(255,255,255,.24);padding:7px 12px;border-radius:40px;background:rgba(255,255,255,.1);letter-spacing:.07em;font-size:11px;text-transform:uppercase;font-weight:800}
    .kp-hero h1{color:white;font-size:clamp(25px,3.2vw,36px);letter-spacing:-.03em;margin:20px 0 10px;font-weight:800}
    .kp-hero p{color:#dce8fc;max-width:650px;line-height:1.7;margin:0;font-size:15px}
    .kp-layout{display:grid;grid-template-columns:minmax(0,1fr) 285px;gap:24px;margin-top:24px;align-items:start}
    .kp-card,.kp-info{background:white;border:1px solid #e1e7f0;border-radius:20px;box-shadow:0 14px 38px rgba(13,36,79,.06)}
    .kp-card{padding:32px}.kp-info{padding:24px}
    .kp-head{display:flex;align-items:center;gap:13px;margin-bottom:24px}
    .kp-head-icon{display:grid;place-items:center;height:47px;width:47px;border-radius:14px;background:#e8f0fe;color:#0453cb;font-size:19px}
    .kp-head h2,.kp-info h3{margin:0;color:#17233b;font-size:19px;font-weight:800}
    .kp-head p{font-size:13px;color:#64748b;margin:4px 0 0}
    .kp-field{margin:0 0 22px}.kp-field label{display:block;font-weight:700;color:#253653;font-size:13px;margin-bottom:9px}
    .kp-field input{width:100%;height:50px;border:1px solid #cdd8e8;border-radius:12px;padding:12px 50px 12px 15px;color:#17233b;background:#fcfdff;font-size:15px;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
    .kp-field input:focus{outline:none;border-color:#0453cb;box-shadow:0 0 0 4px rgba(4,83,203,.09)}
    .kp-input{position:relative}.kp-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:transparent;border:0;color:#65748e;min-width:33px;min-height:33px;cursor:pointer}
    .kp-error{display:block;margin-top:7px;color:#b42318;font-size:12px}
    .kp-alert{padding:13px 16px;border:1px solid #f2dcb5;background:#fff9ed;color:#835510;border-radius:11px;font-size:13px;line-height:1.5;margin-bottom:22px}
    .kp-submit{width:100%;height:52px;border:0;border-radius:12px;background:#0453cb;color:#fff;font-size:14px;font-weight:800;box-shadow:0 8px 20px rgba(4,83,203,.16);cursor:pointer;transition:transform .2s}
    .kp-submit:hover{background:#0341a0;transform:translateY(-1px)}
    .kp-info p,.kp-info li{font-size:13px;line-height:1.75;color:#65748e}
    .kp-info ul{padding-left:20px}.kp-info li{margin:8px 0}
    .kp-info i{color:#0453cb;margin-right:6px}
    @media(max-width:790px){.kp-layout{grid-template-columns:1fr}.kp-info{order:-1}.kp-card{padding:23px}.kp-hero{padding:29px 25px}}
</style>
@endsection

@section('content')
@php
    $requiresFirst = (bool) auth()->user()->must_change_password;
    $isExpired = ! $requiresFirst && \App\Services\UserService::isPasswordExpired(auth()->user());
    $requiresCurrent = ! $requiresFirst;
@endphp
<div class="kp-page">
    <header class="kp-hero">
        <span class="kp-label"><i class="fas fa-shield-alt" aria-hidden="true"></i> Sécurité KLASSCI</span>
        <h1>{{ $isExpired ? 'Renouvelez votre mot de passe' : ($requiresFirst ? 'Bienvenue sur KLASSCI' : 'Modifiez votre mot de passe') }}</h1>
        <p>{{ $isExpired ? 'Votre mot de passe a expiré. Renforcez la sécurité de votre compte avec un nouveau secret personnel.' : ($requiresFirst ? 'Votre espace est prêt. Choisissez votre propre mot de passe pour accéder à KLASSCI.' : 'Conservez la maîtrise de votre espace étudiant et de vos informations.') }}</p>
    </header>

    <div class="kp-layout">
        <section class="kp-card" aria-label="Formulaire de mot de passe">
            <div class="kp-head">
                <div class="kp-head-icon"><i class="fas fa-lock" aria-hidden="true"></i></div>
                <div><h2>Votre nouveau mot de passe</h2><p>Quelques secondes pour sécuriser votre compte.</p></div>
            </div>
            @if(session('warning'))
                <div role="alert" class="kp-alert">{{ session('warning') }}</div>
            @endif
            <form method="POST" action="{{ route('password.change.update') }}">
                @csrf
                @if($requiresCurrent)
                <div class="kp-field">
                    <label for="current_password">Mot de passe actuel</label>
                    <div class="kp-input"><input id="current_password" name="current_password" type="password"
                        autocomplete="current-password" required>
                        <button type="button" class="kp-toggle" data-target="current_password" aria-label="Afficher le mot de passe actuel"><i class="fas fa-eye"></i></button></div>
                    @error('current_password') <span class="kp-error">{{ $message }}</span> @enderror
                </div>
                @endif
                <div class="kp-field">
                    <label for="password">Nouveau mot de passe</label>
                    <div class="kp-input"><input id="password" name="password" type="password"
                        autocomplete="new-password" minlength="8" required>
                        <button type="button" class="kp-toggle" data-target="password" aria-label="Afficher le nouveau mot de passe"><i class="fas fa-eye"></i></button></div>
                    @error('password') <span class="kp-error">{{ $message }}</span> @enderror
                </div>
                <div class="kp-field">
                    <label for="password_confirmation">Confirmer le nouveau mot de passe</label>
                    <div class="kp-input"><input id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password" minlength="8" required>
                        <button type="button" class="kp-toggle" data-target="password_confirmation" aria-label="Afficher la confirmation"><i class="fas fa-eye"></i></button></div>
                </div>
                <button type="submit" class="kp-submit"><i class="fas fa-check-circle" aria-hidden="true"></i> Enregistrer mon mot de passe</button>
            </form>
        </section>
        <aside class="kp-info">
            <div class="kp-head-icon" style="margin-bottom:18px"><i class="fas fa-user-shield"></i></div>
            <h3>Votre sécurité d'abord</h3>
            <p>Un mot de passe personnel protège vos notes, vos informations et vos paiements.</p>
            <ul><li>8 caractères minimum</li><li>Au moins une lettre et un chiffre</li><li>Évitez les mots de passe communs</li><li>Ne communiquez jamais votre mot de passe</li></ul>
            <p><i class="fas fa-info-circle"></i>{{ $requiresFirst ? 'Aucun mot de passe précédent n’est nécessaire pour cette première configuration.' : 'Le mot de passe actuel est requis pour confirmer votre identité.' }}</p>
        </aside>
    </div>
</div>
<script>
document.querySelectorAll('.kp-toggle').forEach(function(button) {
    button.addEventListener('click', function() {
        const input = document.getElementById(button.dataset.target);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        button.setAttribute('aria-label', input.type === 'text' ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        button.querySelector('i').className = input.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
});
</script>
@endsection
