<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'KLASSCI') }} - Mot de passe oublié</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        .login-main {
            min-height: 100vh;
            display: flex;
            flex-direction: row;
        }
        .login-left {
            background: linear-gradient(135deg, #0453cb 0%, #1b64d4 60%, #5e91de 100%);
            color: #fff;
            flex: 1 1 0%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px 32px;
            position: relative;
        }
        .login-logo {
            position: absolute;
            top: 32px;
            left: 32px;
            max-width: 120px;
        }
        .login-illustration {
            max-width: 340px;
            width: 100%;
            margin: 32px 0 24px 0;
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(4,83,203,0.12);
            background: #fff;
            padding: 16px;
        }
        .login-accent {
            font-weight: 700;
            color: #fff;
        }
        .login-accent span {
            color: #ffd600;
        }
        .login-desc {
            font-size: 1.1rem;
            color: #e3eaf6;
            margin-bottom: 16px;
        }
        .login-right {
            flex: 1 1 0%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            box-shadow: -8px 0 32px rgba(30,100,212,0.04);
            position: relative;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(4,83,203,0.08);
            padding: 40px 32px 32px 32px;
        }
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0453cb;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: #5e91de;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        .form-label {
            color: #1b64d4;
            font-weight: 600;
        }
        .form-control {
            border-radius: 0.75rem;
            border: 1px solid #e3eaf6;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #0453cb;
            box-shadow: 0 0 0 2px #5e91de33;
        }
        .btn-primary {
            background: #0453cb;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.75rem 0;
            box-shadow: 0 4px 16px #0453cb22;
        }
        .btn-primary:hover {
            background: #1b64d4;
        }
        .login-footer {
            text-align: center;
            color: #b0b8c9;
            font-size: 0.95rem;
            margin-top: 2rem;
        }
        .login-link {
            color: #0453cb;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .login-link:hover {
            color: #ffd600;
        }
        @media (max-width: 991.98px) {
            .login-main { flex-direction: column; }
            .login-left, .login-right { min-height: 320px; }
            .login-logo { position: static; margin-bottom: 24px; }
        }
        @media (max-width: 767.98px) {
            .login-main { flex-direction: column; }
            .login-left, .login-right { flex: unset; min-height: unset; padding: 32px 12px; }
            .login-card { padding: 24px 8px; }
        }
    </style>
</head>
<body>
<div class="login-main">
    <!-- Colonne gauche -->
    <div class="login-left">
        <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI Logo" class="login-logo">
        <div style="max-width: 420px; width: 100%; text-align: left;">
            <h1 class="login-accent" style="font-size:2.2rem;line-height:1.2;">
                Débloquez votre <span>potentiel scolaire</span>
            </h1>
            <div class="login-desc mb-4">
                Gérez vos classes, notes, présences et communication en toute simplicité avec KLASSCI.
            </div>
        </div>
        <div class="login-illustration">
            <img src="{{ asset('images/hand-finger-side.jpg') }}" alt="Étudiant avec livres" style="width:100%;border-radius:1.2rem;object-fit:cover;">
        </div>
    </div>
    <!-- Colonne droite -->
    <div class="login-right">
        <div class="login-card">
            <div class="text-center mb-4">
                <h2 class="login-title">Mot de passe oublié</h2>
                <div class="login-subtitle">Recevez un lien de réinitialisation par e-mail</div>
                    </div>
            @if(session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                        </div>
                    @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 list-unstyled">
                        @foreach($errors->all() as $error)
                            <li><i class="fas fa-exclamation-circle me-2"></i>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                <div class="mb-3">
                            <label for="email" class="form-label">Adresse e-mail</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="votre@email.com">
                            </div>
                <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Envoyer le lien de réinitialisation
                            </button>
                        </div>
                    </form>
            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="login-link">
                    <i class="fas fa-arrow-left"></i> Retour à la connexion
                </a>
            </div>
            <div class="login-footer mt-4">
                &copy; {{ date('Y') }} KLASSCI. Tous droits réservés.
            </div>
        </div>
    </div>
</div>
</body>
</html> 