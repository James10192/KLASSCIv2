<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'KLASSCI') }} - Mot de passe oublié</title>
    
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
            background: 
                linear-gradient(135deg, rgba(46, 94, 170, 0.85) 0%, rgba(30, 74, 140, 0.9) 50%, rgba(255, 107, 53, 0.85) 100%),
                url('{{ asset('images/Images landingPage/Sans titre - 2-03.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
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
        .icon-4 { top: 70%; right: 8%; animation-delay: 3s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(-40px) rotate(0deg); }
            75% { transform: translateY(-20px) rotate(-5deg); }
        }
        
        /* Container principal avec glassmorphism */
        .email-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 32px;
            box-shadow: 
                0 32px 64px -12px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            margin: 2rem;
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(50px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        
        /* Header section */
        .email-header {
            background: 
                linear-gradient(135deg, rgba(46, 94, 170, 0.3) 0%, rgba(74, 123, 200, 0.2) 100%);
            padding: 3rem 2rem;
            text-align: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }
        
        .email-header::before {
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
        
        .klassci-logo {
            height: 60px;
            width: auto;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }
        
        .email-icon {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); }
        }
        
        .email-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .email-title {
            color: white;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .email-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.6;
        }
        
        /* Form section avec glassmorphism */
        .form-section {
            padding: 3rem 2rem;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }
        
        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
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
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: relative;
            color: var(--gray-800);
        }
        
        .form-control::placeholder {
            color: rgba(51, 65, 85, 0.6);
        }
        
        .form-control:focus {
            outline: none;
            border-color: rgba(46, 94, 170, 0.5);
            box-shadow: 0 0 0 3px rgba(46, 94, 170, 0.15), 0 8px 32px rgba(46, 94, 170, 0.15);
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.3);
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.1rem;
            z-index: 5;
            pointer-events: none;
        }
        
        /* Bouton */
        .btn-email {
            width: 100%;
            background: 
                linear-gradient(135deg, rgba(46, 94, 170, 0.9) 0%, rgba(30, 74, 140, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                0 8px 32px rgba(46, 94, 170, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .btn-email::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-email:hover::before {
            left: 100%;
        }
        
        .btn-email:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 12px 40px rgba(46, 94, 170, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            background: 
                linear-gradient(135deg, rgba(46, 94, 170, 1) 0%, rgba(30, 74, 140, 1) 100%);
        }
        
        .login-link {
            color: var(--klassci-orange);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
        }
        
        .login-link:hover {
            color: var(--klassci-blue);
        }
        
        .login-link i {
            margin-right: 0.5rem;
        }
        
        /* Alerts */
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
        
        /* Footer */
        .login-footer {
            text-align: center;
            color: var(--gray-700);
            font-size: 0.85rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 8px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            margin-top: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                background-attachment: scroll;
            }
            
            .email-container {
                margin: 1rem;
                max-width: 100%;
                background: rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(30px);
                -webkit-backdrop-filter: blur(30px);
            }
            
            .email-header {
                padding: 2rem 1.5rem;
            }
            
            .email-title {
                font-size: 1.5rem;
            }
            
            .klassci-logo {
                height: 45px;
            }
            
            .email-icon {
                width: 60px;
                height: 60px;
            }
            
            .email-icon i {
                font-size: 1.5rem;
            }
            
            .form-section {
                padding: 2rem 1.5rem;
                background: rgba(255, 255, 255, 0.4);
            }
            
            .login-footer {
                background: rgba(255, 255, 255, 0.2);
                color: var(--gray-800);
                text-shadow: 0 1px 3px rgba(255, 255, 255, 1);
            }
            
            .floating-elements,
            .educational-bg {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Arrière-plan éducatif avec motifs géométriques -->
    <div class="educational-bg">
        <div class="geometric-pattern pattern-1"></div>
        <div class="geometric-pattern pattern-2"></div>
        <div class="geometric-pattern pattern-3"></div>
    </div>
    
    <!-- Éléments éducatifs flottants -->
    <div class="floating-elements">
        <div class="floating-icon icon-1"><i class="fas fa-envelope"></i></div>
        <div class="floating-icon icon-2"><i class="fas fa-paper-plane"></i></div>
        <div class="floating-icon icon-3"><i class="fas fa-at"></i></div>
        <div class="floating-icon icon-4"><i class="fas fa-mail-bulk"></i></div>
    </div>
    
    <!-- Container principal -->
    <div class="email-container">
        <!-- Header avec logo KLASSCI -->
        <div class="email-header">
            <img src="{{ asset('images/Images landingPage/logo_klassci.png') }}" alt="KLASSCI" class="klassci-logo">
            <div class="email-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h1 class="email-title">Mot de passe oublié</h1>
            <p class="email-subtitle">Recevez un lien de réinitialisation sécurisé par e-mail</p>
        </div>
        
        <!-- Section formulaire -->
        <div class="form-section">
            <!-- Messages d'alerte -->
            @if(session('status'))
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
            
            <!-- Formulaire -->
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <div class="position-relative">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            id="email" 
                            type="email" 
                            class="form-control @error('email') is-invalid @enderror" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus
                            placeholder="votre@email.com"
                        >
                        @error('email')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                        @enderror
                    </div>
                </div>
                
                <button type="submit" class="btn-email">
                    <i class="fas fa-paper-plane me-2"></i>
                    Envoyer le lien de réinitialisation
                </button>
            </form>
            
            <!-- Lien retour connexion -->
            <div class="text-center">
                <a href="{{ route('login') }}" class="login-link">
                    <i class="fas fa-arrow-left"></i>
                    Retour à la connexion
                </a>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>&copy; {{ date('Y') }} KLASSCI. Tous droits réservés.</p>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 