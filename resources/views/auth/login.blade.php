<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KLASSCI') }} - Connexion</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #f59e0b;
            --success: #10b981;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        /* Particules animées en arrière-plan */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 60px; height: 60px; left: 20%; animation-delay: 2s; }
        .particle:nth-child(3) { width: 100px; height: 100px; left: 35%; animation-delay: 4s; }
        .particle:nth-child(4) { width: 120px; height: 120px; left: 50%; animation-delay: 1s; }
        .particle:nth-child(5) { width: 90px; height: 90px; left: 70%; animation-delay: 3s; }
        .particle:nth-child(6) { width: 110px; height: 110px; left: 85%; animation-delay: 5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
            50% { transform: translateY(-100px) rotate(180deg); opacity: 0.3; }
        }
        
        /* Container principal */
        .login-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.05);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Logo et titre */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-container {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .logo-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        .logo-text {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--gray-500);
            font-size: 1rem;
            font-weight: 400;
        }
        
        /* Formulaire */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            position: relative;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-1px);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid var(--gray-300);
            border-radius: 4px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .forgot-link:hover {
            color: var(--primary-dark);
        }
        
        /* Bouton de connexion */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--gray-400);
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
                max-width: 100%;
            }
            
            .login-title {
                font-size: 1.75rem;
            }
            
            .particles {
                display: none;
            }
        }
        
        /* Alerte d'erreur stylisée */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
            border-left: 4px solid #10b981;
        }
    </style>
</head>
<body>
    <!-- Particules animées -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <!-- Container de connexion -->
    <div class="login-container">
        <!-- Header avec logo -->
        <div class="login-header">
            <div class="logo-container">
                <div class="logo-text">K</div>
            </div>
            <h1 class="login-title">Bienvenue</h1>
            <p class="login-subtitle">Connectez-vous à votre compte KLASSCI</p>
        </div>
        
        <!-- Messages d'alerte -->
        @if(session('status'))
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-2"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        
        <!-- Formulaire de connexion -->
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-2"></i>Nom d'utilisateur
                </label>
                <input 
                    id="username" 
                    type="text" 
                    class="form-control @error('username') is-invalid @enderror" 
                    name="username" 
                    value="{{ old('username') }}" 
                    required 
                    autofocus 
                    placeholder="Entrez votre nom d'utilisateur"
                >
                @error('username')
                    <div class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Mot de passe
                </label>
                <input 
                    id="password" 
                    type="password" 
                    class="form-control @error('password') is-invalid @enderror" 
                    name="password" 
                    required 
                    autocomplete="current-password" 
                    placeholder="Entrez votre mot de passe"
                >
                @error('password')
                    <div class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </div>
                @enderror
            </div>
            
            <div class="form-check">
                <div class="d-flex align-items-center">
                    <input 
                        class="form-check-input me-2" 
                        type="checkbox" 
                        name="remember" 
                        id="remember" 
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="remember">
                        Se souvenir de moi
                    </label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                Se connecter
            </button>
        </form>
        
        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; {{ date('Y') }} KLASSCI. Tous droits réservés.</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
