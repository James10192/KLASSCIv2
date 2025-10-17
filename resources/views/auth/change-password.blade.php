@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="dashboard-header">
        <div class="header-left">
            <h1>Changement de mot de passe requis</h1>
            <p class="header-subtitle">Veuillez changer votre mot de passe pour continuer</p>
        </div>
    </div>

    <div class="card-moderne" style="max-width: 600px; margin: 0 auto; padding: var(--space-xl);">
        @if (session('warning'))
            <div class="alert alert-warning mb-lg" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning); padding: var(--space-md); border-radius: var(--radius-medium); margin-bottom: var(--space-lg);">
                <i class="fas fa-exclamation-triangle" style="margin-right: var(--space-sm);"></i>
                {{ session('warning') }}
            </div>
        @endif

        <div style="text-align: center; margin-bottom: var(--space-xl);">
            <div style="width: 80px; height: 80px; background-color: var(--warning); border-radius: var(--radius-circle); margin: 0 auto var(--space-lg); display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-key" style="font-size: 24px; color: white;"></i>
            </div>
            <h2 style="color: var(--text-primary); margin-bottom: var(--space-sm);">Sécurité renforcée</h2>
            <p style="color: var(--text-secondary); margin: 0;">Pour votre sécurité, vous devez créer un nouveau mot de passe personnalisé.</p>
        </div>

        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf

            <div style="margin-bottom: var(--space-lg);">
                <label for="current_password" style="display: block; font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-sm);">
                    Mot de passe actuel
                </label>
                <div style="position: relative;">
                    <input id="current_password"
                           type="password"
                           name="current_password"
                           required
                           style="width: 100%; padding: var(--space-md) 3rem var(--space-md) var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); background-color: var(--surface);">
                    <i class="fas fa-eye" id="toggleCurrentPassword" onclick="togglePasswordVisibility('current_password')" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
                @error('current_password')
                    <span style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs); display: block;">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div style="margin-bottom: var(--space-lg);">
                <label for="password" style="display: block; font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-sm);">
                    Nouveau mot de passe
                </label>
                <div style="position: relative;">
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           style="width: 100%; padding: var(--space-md) 3rem var(--space-md) var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); background-color: var(--surface);">
                    <i class="fas fa-eye" id="togglePassword" onclick="togglePasswordVisibility('password')" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
                @error('password')
                    <span style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs); display: block;">
                        {{ $message }}
                    </span>
                @enderror
                <div style="margin-top: var(--space-sm); padding: var(--space-sm); background-color: #f3f4f6; border-radius: var(--radius-small);">
                    <p style="font-size: var(--text-small); color: var(--text-secondary); margin: 0; margin-bottom: var(--space-xs);">
                        <strong>Exigences du mot de passe :</strong>
                    </p>
                    <ul style="font-size: var(--text-small); color: var(--text-secondary); margin: 0; padding-left: var(--space-md);">
                        <li>Au moins 8 caractères</li>
                        <li>Au moins une lettre majuscule et une minuscule</li>
                        <li>Au moins un chiffre</li>
                        <li>Au moins un symbole spécial (!@#$%^&*)</li>
                    </ul>
                </div>
            </div>

            <div style="margin-bottom: var(--space-xl);">
                <label for="password_confirmation" style="display: block; font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-sm);">
                    Confirmer le nouveau mot de passe
                </label>
                <div style="position: relative;">
                    <input id="password_confirmation"
                           type="password"
                           name="password_confirmation"
                           required
                           style="width: 100%; padding: var(--space-md) 3rem var(--space-md) var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); background-color: var(--surface);">
                    <i class="fas fa-eye" id="togglePasswordConfirmation" onclick="togglePasswordVisibility('password_confirmation')" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
            </div>

            <button type="submit" 
                    class="btn-acasi primary" 
                    style="width: 100%; padding: var(--space-md); font-size: var(--text-normal); font-weight: 600;">
                <i class="fas fa-check" style="margin-right: var(--space-sm);"></i>
                Changer le mot de passe
            </button>
        </form>

        <div style="margin-top: var(--space-lg); padding-top: var(--space-lg); border-top: 1px solid #e5e7eb; text-align: center;">
            <p style="font-size: var(--text-small); color: var(--text-muted); margin: 0;">
                <i class="fas fa-shield-alt" style="margin-right: var(--space-xs);"></i>
                Cette étape est obligatoire pour sécuriser votre compte
            </p>
        </div>
    </div>
</div>

<script>
// Fonction pour toggle la visibilité des mots de passe
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const toggleIcon = document.getElementById('toggle' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1).replace('_', ''));

    if (field.type === 'password') {
        field.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');

    function validatePassword() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        // Validation en temps réel si nécessaire
        if (confirm && password !== confirm) {
            confirmInput.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirmInput.setCustomValidity('');
        }
    }

    passwordInput.addEventListener('input', validatePassword);
    confirmInput.addEventListener('input', validatePassword);
});
</script>
@endsection