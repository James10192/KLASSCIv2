<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KLASSCI') }} - Réinitialisation du mot de passe</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Animation CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --esbtp-orange: #f29400;
            --esbtp-green: #01632f;
            --esbtp-white: #ffffff;
            --esbtp-light-green: rgba(1, 99, 47, 0.05);
            --esbtp-light-orange: rgba(242, 148, 0, 0.1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--esbtp-light-green), var(--esbtp-light-orange));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before, body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            z-index: -1;
        }
        
        body::before {
            background: radial-gradient(var(--esbtp-light-orange), transparent 70%);
            top: -100px;
            right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        
        body::after {
            background: radial-gradient(var(--esbtp-light-green), transparent 70%);
            bottom: -100px;
            left: -100px;
            animation: float 10s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }
        
        .reset-container {
            width: 100%;
            max-width: 500px;
            perspective: 1000px;
        }
        
        .card {
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transform-style: preserve-3d;
            transition: all 0.5s ease;
        }
        
        .card:hover {
            transform: translateY(-5px) rotateX(5deg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--esbtp-green), #014a23);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 5px solid var(--esbtp-orange);
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            transform: rotate(30deg);
        }
        
        .reset-icon {
            display: inline-block;
            font-size: 2.5rem;
            color: var(--esbtp-orange);
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--esbtp-orange), #f2a730);
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(242, 148, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--esbtp-green), #018a42);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(1, 99, 47, 0.4);
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--esbtp-orange);
            box-shadow: 0 0 0 3px rgba(242, 148, 0, 0.1);
            background-color: white;
        }
        
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            color: var(--esbtp-green);
        }
        
        .form-control {
            border-radius: 0 10px 10px 0;
        }
        
        .login-link {
            color: var(--esbtp-green);
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .login-link:hover {
            color: var(--esbtp-orange);
            transform: translateX(-5px);
        }
        
        .login-link i {
            margin-right: 8px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Animation pour les champs du formulaire */
        .animate__animated {
            animation-duration: 0.6s;
        }
        
        .animate__delay-1 {
            animation-delay: 0.1s;
        }
        
        .animate__delay-2 {
            animation-delay: 0.2s;
        }
        
        /* Effet de particules */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
            animation: particleFloat 15s infinite linear;
        }
        
        @keyframes particleFloat {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-100vh) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Particules d'arrière-plan -->
    <div class="particles" id="particles"></div>
    <div class="container">
        <div class="reset-container animate__animated animate__fadeIn">
            <div class="card">
            <div class="card-header">
                    <div class="reset-icon animate__animated animate__bounceIn">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3 class="mb-0 animate__animated animate__fadeInDown">Réinitialisation du mot de passe</h3>
                    <p class="mb-0 mt-2 animate__animated animate__fadeInUp">ESBTP - Système de Gestion Universitaire</p>
                </div>
                <div class="card-body p-4">
                    @if (session('status'))
                        <div class="alert alert-success mb-3 animate__animated animate__fadeIn" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('status') }}
            </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3 animate__animated animate__fadeIn">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                        <div class="mb-4 animate__animated animate__fadeInUp animate__delay-1">
                        <label for="email" class="form-label">Adresse e-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus readonly>
                            </div>
                        </div>
                        <div class="mb-3 animate__animated animate__fadeInUp animate__delay-2">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                        </div>
                        </div>
                        <div class="mb-4 animate__animated animate__fadeInUp animate__delay-2">
                        <label for="password-confirm" class="form-label">Confirmer le mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>
                        <div class="d-grid gap-2 animate__animated animate__fadeInUp animate__delay-2">
                        <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-2"></i> Réinitialiser le mot de passe
                        </button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4 animate__animated animate__fadeIn animate__delay-2">
                        <a href="{{ route('login') }}" class="login-link">
                            <i class="fas fa-arrow-left"></i> Retour à la connexion
                        </a>
                    </div>
            <div class="login-footer mt-4">
                &copy; {{ date('Y') }} KLASSCI. Tous droits réservés.
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Création des particules d'arrière-plan
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const colors = ['#01632f33', '#f2940033', '#01632f22', '#f2940022'];
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                // Taille aléatoire
                const size = Math.random() * 30 + 10;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                // Position aléatoire
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                // Couleur aléatoire
                particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                // Durée d'animation aléatoire
                particle.style.animationDuration = `${Math.random() * 20 + 10}s`;
                // Délai d'animation aléatoire
                particle.style.animationDelay = `${Math.random() * 5}s`;
                particlesContainer.appendChild(particle);
                }
        });
    </script>
</body>
</html> 