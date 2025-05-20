<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KLASSCI') }} - Connexion</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: rgba(37, 99, 235, 0.1);
            --secondary: #f97316;
            --secondary-dark: #ea580c;
            --secondary-light: rgba(249, 115, 22, 0.1);
            --dark: #0f172a;
            --dark-light: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --light: #f8fafc;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --info: #06b6d4;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.8) 100%),
                        url('{{ asset('images/login_bg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            z-index: 10;
        }

        .card {
            border-radius: var(--border-radius-lg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: white;
            border-bottom: none;
            padding: 2rem 2rem 0;
            text-align: center;
        }

        .card-body {
            padding: 2rem;
        }

        .login-logo {
            max-width: 100px;
            margin-bottom: 1.5rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .login-title {
            color: var(--dark);
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--gray);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius-md);
            border: 1px solid var(--gray-light);
            background-color: white;
            color: var(--dark);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            height: auto;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-label {
            color: var(--dark);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .input-group-text {
            background-color: var(--gray-light);
            border: 1px solid var(--gray-light);
            color: var(--gray);
            border-radius: var(--border-radius-md) 0 0 var(--border-radius-md);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.25);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .alert {
            border-radius: var(--border-radius-md);
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .back-to-home {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            border-radius: var(--border-radius-full);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .back-to-home:hover {
            background-color: rgba(15, 23, 42, 0.7);
            color: white;
            transform: translateX(-5px);
        }

        .animated-shape {
            position: absolute;
            border-radius: 50%;
            width: 500px;
            height: 500px;
            animation: shapeFloat 20s infinite alternate ease-in-out;
            z-index: 1;
            filter: blur(70px);
        }

        .shape1 {
            top: -250px;
            right: -200px;
            background: rgba(37, 99, 235, 0.1);
        }

        .shape2 {
            bottom: -300px;
            left: -200px;
            width: 600px;
            height: 600px;
            background: rgba(249, 115, 22, 0.1);
            animation-delay: -5s;
        }

        @keyframes shapeFloat {
            0% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(100px, 100px) rotate(10deg); }
            100% { transform: translate(-100px, 50px) rotate(-10deg); }
        }

        @media (max-width: 767.98px) {
            .card-body {
                padding: 1.5rem;
            }

            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <a href="{{ url('/') }}" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        <span>Retour à l'accueil</span>
    </a>

    <div class="animated-shape shape1"></div>
    <div class="animated-shape shape2"></div>

    <div class="container">
        <div class="login-container" data-aos="fade-up" data-aos-duration="800">
            <div class="card">
                <div class="card-header">
                    <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI Logo" class="login-logo">
                    <h1 class="login-title">Bienvenue</h1>
                    <p class="login-subtitle">Connectez-vous pour accéder à votre compte</p>
                </div>
                <div class="card-body">
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
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autofocus placeholder="Votre nom d'utilisateur">
                                @error('username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Utilisez votre nom d'utilisateur (exemple: superadmin)</small>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label mb-0">Mot de passe</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="small text-primary">Mot de passe oublié ?</a>
                                @endif
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Votre mot de passe">
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Connexion
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="small text-muted">&copy; {{ date('Y') }} KLASSCI. Tous droits réservés.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init();
        });
    </script>
</body>
</html>
