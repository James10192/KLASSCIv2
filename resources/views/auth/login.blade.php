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
            /* Couleurs KLASSCI basées sur le logo */
            --klassci-orange: #FF6B35;
            --klassci-blue: #2E5EAA;
            --klassci-blue-dark: #1E4A8C;
            --klassci-blue-light: #4A7BC8;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--klassci-blue) 0%, var(--klassci-blue-dark) 50%, var(--klassci-orange) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Arrière-plan éducatif avec motifs géométriques */
        .educational-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }
        
        .geometric-pattern {
            position: absolute;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: rotate 20s linear infinite;
        }
        
        .pattern-1 { width: 300px; height: 300px; top: 10%; left: -10%; animation-delay: 0s; }
        .pattern-2 { width: 200px; height: 200px; top: 60%; right: -5%; animation-delay: -5s; }
        .pattern-3 { width: 150px; height: 150px; bottom: 20%; left: 15%; animation-delay: -10s; }
        .pattern-4 { width: 250px; height: 250px; top: 30%; right: 20%; animation-delay: -15s; }
        
        @keyframes rotate {
            0% { transform: rotate(0deg) scale(1); opacity: 0.3; }
            50% { transform: rotate(180deg) scale(1.1); opacity: 0.1; }
            100% { transform: rotate(360deg) scale(1); opacity: 0.3; }
        }
        
        /* Éléments éducatifs flottants */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }
        
        .floating-icon {
            position: absolute;
            color: rgba(255, 255, 255, 0.15);
            font-size: 2rem;
            animation: float 8s ease-in-out infinite;
        }
        
        .icon-1 { top: 15%; left: 8%; animation-delay: 0s; }
        .icon-2 { top: 25%; right: 12%; animation-delay: 2s; }
        .icon-3 { bottom: 30%; left: 10%; animation-delay: 4s; }
        .icon-4 { bottom: 15%; right: 15%; animation-delay: 6s; }
        .icon-5 { top: 50%; left: 5%; animation-delay: 1s; }
        .icon-6 { top: 70%; right: 8%; animation-delay: 3s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(-40px) rotate(0deg); }
            75% { transform: translateY(-20px) rotate(-5deg); }
        }
        
        /* Container principal avec effet d'élévation */
        .main-container {
            position: relative;
            z-index: 10;
            display: flex;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 2rem;
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(50px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        
        /* Section gauche - Visuel éducatif */
        .visual-section {
            flex: 1;
            background: linear-gradient(135deg, var(--klassci-blue) 0%, var(--klassci-blue-light) 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .visual-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: drift 30s linear infinite;
        }
        
        @keyframes drift {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(100px, 100px) rotate(360deg); }
        }
        
        .education-visual {
            position: relative;
            z-index: 2;
        }
        
        .graduation-icon {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); }
        }
        
        .graduation-icon i {
            font-size: 3rem;
            color: white;
        }
        
        .visual-title {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .visual-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 400;
            line-height: 1.6;
            max-width: 300px;
        }
        
        .stats-row {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            text-align: center;
            color: white;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--klassci-orange);
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
        
        /* Section droite - Formulaire de connexion */
        .login-section {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        /* Logo KLASSCI */
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .klassci-logo {
            height: 60px;
            width: auto;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .login-subtitle {
            color: var(--gray-500);
            font-size: 1rem;
            text-align: center;
            margin-bottom: 2rem;
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
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--gray-200);
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            position: relative;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--klassci-blue);
            box-shadow: 0 0 0 3px rgba(46, 94, 170, 0.1);
            transform: translateY(-1px);
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.1rem;
            z-index: 5;
            margin-top: 1.8rem;
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
            border-radius: 6px;
            margin-right: 0.75rem;
        }
        
        .form-check-input:checked {
            background-color: var(--klassci-blue);
            border-color: var(--klassci-blue);
        }
        
        .forgot-link {
            color: var(--klassci-orange);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .forgot-link:hover {
            color: var(--klassci-blue);
        }
        
        /* Bouton de connexion */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--klassci-blue) 0%, var(--klassci-blue-dark) 100%);
            border: none;
            border-radius: 16px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(46, 94, 170, 0.3);
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
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
            box-shadow: 0 12px 35px rgba(46, 94, 170, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            color: var(--gray-400);
            font-size: 0.85rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                margin: 1rem;
                max-width: 100%;
            }
            
            .visual-section {
                padding: 2rem;
                min-height: 300px;
            }
            
            .visual-title {
                font-size: 1.5rem;
            }
            
            .graduation-icon {
                width: 80px;
                height: 80px;
                margin-bottom: 1.5rem;
            }
            
            .graduation-icon i {
                font-size: 2rem;
            }
            
            .stats-row {
                gap: 1rem;
                margin-top: 1.5rem;
            }
            
            .stat-number {
                font-size: 1.4rem;
            }
            
            .login-section {
                padding: 2rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .klassci-logo {
                height: 45px;
            }
            
            .floating-elements,
            .educational-bg {
                display: none;
            }
        }
        
        /* Alerte d'erreur stylisée */
        .alert {
            border-radius: 16px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border-left: 4px solid var(--klassci-orange);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
            border-left: 4px solid var(--klassci-blue);
        }
    </style>
</head>
<body>
    <!-- Arrière-plan éducatif avec motifs géométriques -->
    <div class="educational-bg">
        <div class="geometric-pattern pattern-1"></div>
        <div class="geometric-pattern pattern-2"></div>
        <div class="geometric-pattern pattern-3"></div>
        <div class="geometric-pattern pattern-4"></div>
    </div>
    
    <!-- Éléments éducatifs flottants -->
    <div class="floating-elements">
        <div class="floating-icon icon-1"><i class="fas fa-graduation-cap"></i></div>
        <div class="floating-icon icon-2"><i class="fas fa-book"></i></div>
        <div class="floating-icon icon-3"><i class="fas fa-users"></i></div>
        <div class="floating-icon icon-4"><i class="fas fa-chart-line"></i></div>
        <div class="floating-icon icon-5"><i class="fas fa-lightbulb"></i></div>
        <div class="floating-icon icon-6"><i class="fas fa-trophy"></i></div>
    </div>
    
    <!-- Container principal -->
    <div class="main-container">
        <!-- Section gauche - Visuel éducatif -->
        <div class="visual-section">
            <div class="education-visual">
                <div class="graduation-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2 class="visual-title">Excellence Éducative</h2>
                <p class="visual-subtitle">Propulsez votre établissement vers l'excellence avec KLASSCI, la plateforme CRM éducative de nouvelle génération.</p>
                
                <div class="stats-row">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <div class="stat-label">Écoles</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50k+</span>
                        <div class="stat-label">Étudiants</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <div class="stat-label">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section droite - Formulaire de connexion -->
        <div class="login-section">
            <!-- Logo KLASSCI -->
            <div class="logo-container">
                <img src="{{ asset('images/Images landingPage/logo_klassci.png') }}" alt="KLASSCI" class="klassci-logo">
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
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <div class="position-relative">
                        <i class="fas fa-user input-icon"></i>
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
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="position-relative">
                        <i class="fas fa-lock input-icon"></i>
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
                </div>
                
                <div class="form-check">
                    <div class="d-flex align-items-center">
                        <input 
                            class="form-check-input" 
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
