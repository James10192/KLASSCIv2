<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KLASSCI') }} - Connexion</title>

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
        .btn-social {
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin: 0 8px;
            background: #f8fafc;
            border: 1px solid #e3eaf6;
            color: #0453cb;
            transition: all 0.2s;
        }
        .btn-social:hover {
            background: #0453cb;
            color: #fff;
        }
        .login-footer {
            text-align: center;
            color: #b0b8c9;
            font-size: 0.95rem;
            margin-top: 2rem;
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
            <img src="{{ asset('images/Images landingPage/logo_klassci.png') }}" alt="KLASSCI Logo" class="login-logo">
            <div style="max-width: 420px; width: 100%; text-align: left;">
                <h1 class="login-accent" style="font-size:2.2rem;line-height:1.2;">
                    Un clic, <span>une gestion simplifiée</span>
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
                    <h2 class="login-title">Connexion</h2>
                    <div class="login-subtitle">Connectez-vous à votre compte KLASSCI</div>
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
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autofocus placeholder="Votre nom d'utilisateur">
                        @error('username')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Votre mot de passe">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="small text-primary">Mot de passe oublié ?</a>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Connexion
                    </button>
                </form>
                <div class="login-footer mt-4">
                    &copy; {{ date('Y') }} KLASSCI. Tous droits réservés.
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
