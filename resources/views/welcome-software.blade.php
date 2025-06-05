<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KLASSCI - Système de Gestion Scolaire Moderne</title>
    <meta name="description" content="KLASSCI est un système de gestion scolaire complet et moderne pour les établissements d'enseignement supérieur. Gérez facilement vos étudiants, classes, notes et bien plus.">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #8b5cf6;
            --secondary: #ec4899;
            --secondary-dark: #db2777;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #0ea5e9;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;

            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            --gradient-hero: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --radius-full: 9999px;

            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            overflow-x: hidden;
        }

        /* ============= Navigation ============= */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .navbar-nav .nav-link {
            color: var(--gray-700);
            font-weight: 500;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            transition: all var(--transition-normal);
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary-custom {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            color: white;
        }

        /* ============= Hero Section ============= */
        .hero {
            min-height: 100vh;
            background: var(--gradient-hero);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse"><path d="M 50 0 L 0 0 0 50" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero .lead {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }

        .hero-image {
            position: relative;
            z-index: 2;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
        }

        /* ============= Features Section ============= */
        .features {
            padding: 5rem 0;
            background: white;
        }

        .feature-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            text-align: center;
            transition: all var(--transition-normal);
            border: 1px solid var(--gray-200);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .feature-card h4 {
            color: var(--gray-800);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .feature-card p {
            color: var(--gray-600);
            line-height: 1.6;
        }

        /* ============= Stats Section ============= */
        .stats {
            padding: 5rem 0;
            background: var(--gradient-primary);
            color: white;
        }

        .stat-item {
            text-align: center;
            padding: 2rem 1rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* ============= Modules Section ============= */
        .modules {
            padding: 5rem 0;
            background: var(--gray-100);
        }

        .module-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all var(--transition-normal);
            border: 1px solid var(--gray-200);
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .module-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .module-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-secondary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .module-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .module-features {
            list-style: none;
            padding: 0;
        }

        .module-features li {
            padding: 0.5rem 0;
            color: var(--gray-600);
            position: relative;
            padding-left: 1.5rem;
        }

        .module-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success);
            font-weight: bold;
        }

        /* ============= Pricing Section ============= */
        .pricing {
            padding: 5rem 0;
            background: white;
        }

        .pricing-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2.5rem 2rem;
            text-align: center;
            border: 2px solid var(--gray-200);
            transition: all var(--transition-normal);
            height: 100%;
        }

        .pricing-card.featured {
            border-color: var(--primary);
            transform: scale(1.05);
            box-shadow: var(--shadow-xl);
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .pricing-card.featured:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .pricing-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .pricing-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1rem;
        }

        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .pricing-period {
            color: var(--gray-500);
            margin-bottom: 2rem;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            padding: 0.75rem 0;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-100);
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        /* ============= CTA Section ============= */
        .cta {
            padding: 5rem 0;
            background: var(--gradient-secondary);
            color: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .btn-white {
            background: white;
            color: var(--primary);
            border: none;
            padding: 1rem 2.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 1.1rem;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-lg);
        }

        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            color: var(--primary-dark);
        }

        /* ============= Footer ============= */
        .footer {
            background: var(--gray-900);
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer h5 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .footer a {
            color: var(--gray-400);
            text-decoration: none;
            transition: color var(--transition-normal);
        }

        .footer a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-700);
            padding-top: 2rem;
            margin-top: 2rem;
            text-align: center;
            color: var(--gray-400);
        }

        /* ============= Responsive ============= */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero .lead {
                font-size: 1.1rem;
            }

            .pricing-card.featured {
                transform: none;
            }

            .pricing-card.featured:hover {
                transform: translateY(-10px);
            }
        }

        /* ============= Animations ============= */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="fas fa-graduation-cap me-2"></i>
                KLASSCI
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fonctionnalités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#modules">Modules</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Tarifs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <div class="ms-3">
                    <a href="{{ route('login') }}" class="btn btn-primary-custom">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Se connecter
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="hero-content">
                        <h1>Gérez votre établissement avec <span style="background: linear-gradient(135deg, #fbbf24, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">KLASSCI</span></h1>
                        <p class="lead">
                            La solution complète de gestion scolaire pour les établissements d'enseignement supérieur.
                            Simplifiez la gestion des étudiants, des notes, des emplois du temps et bien plus encore.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="#demo" class="btn btn-white">
                                <i class="fas fa-play me-2"></i>
                                Voir la démo
                            </a>
                            <a href="#contact" class="btn btn-outline-light">
                                <i class="fas fa-phone me-2"></i>
                                Nous contacter
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="hero-image">
                        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="KLASSCI Dashboard" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Établissements</span>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <span class="stat-number">50K+</span>
                        <span class="stat-label">Étudiants</span>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <span class="stat-number">99.9%</span>
                        <span class="stat-label">Disponibilité</span>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Support</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                    <h2 class="display-5 fw-bold mb-3">Pourquoi choisir KLASSCI ?</h2>
                    <p class="lead text-muted">
                        Une solution moderne et intuitive qui révolutionne la gestion de votre établissement
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Gestion des Étudiants</h4>
                        <p>Gérez facilement les inscriptions, profils étudiants, et suivez leur parcours académique en temps réel.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Suivi des Notes</h4>
                        <p>Système complet de notation avec génération automatique de bulletins et statistiques détaillées.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4>Emplois du Temps</h4>
                        <p>Planification intelligente des cours avec gestion des conflits et notifications automatiques.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Interface Mobile</h4>
                        <p>Accès complet depuis n'importe quel appareil avec une interface responsive et intuitive.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Sécurité Avancée</h4>
                        <p>Protection des données avec chiffrement, sauvegardes automatiques et contrôle d'accès granulaire.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h4>Personnalisable</h4>
                        <p>Adaptez KLASSCI aux besoins spécifiques de votre établissement avec des modules configurables.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <section id="modules" class="modules">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                    <h2 class="display-5 fw-bold mb-3">Modules Intégrés</h2>
                    <p class="lead text-muted">
                        Découvrez tous les modules qui composent la suite KLASSCI
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
                    <div class="module-card">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3 class="module-title">Gestion Académique</h3>
                        </div>
                        <ul class="module-features">
                            <li>Inscription et gestion des étudiants</li>
                            <li>Organisation des filières et classes</li>
                            <li>Gestion des matières et programmes</li>
                            <li>Suivi du parcours académique</li>
                            <li>Génération de certificats</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                    <div class="module-card">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h3 class="module-title">Évaluations & Notes</h3>
                        </div>
                        <ul class="module-features">
                            <li>Création d'examens et évaluations</li>
                            <li>Saisie et calcul automatique des notes</li>
                            <li>Génération de bulletins personnalisés</li>
                            <li>Statistiques et analyses de performance</li>
                            <li>Système de coefficients flexible</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-right" data-aos-delay="300">
                    <div class="module-card">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <h3 class="module-title">Planning & Emplois du Temps</h3>
                        </div>
                        <ul class="module-features">
                            <li>Planification automatique des cours</li>
                            <li>Gestion des salles et ressources</li>
                            <li>Détection des conflits d'horaires</li>
                            <li>Notifications et rappels</li>
                            <li>Export et impression des plannings</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="400">
                    <div class="module-card">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3 class="module-title">Communication</h3>
                        </div>
                        <ul class="module-features">
                            <li>Système d'annonces et notifications</li>
                            <li>Messagerie interne intégrée</li>
                            <li>Communication avec les parents</li>
                            <li>Alertes automatiques</li>
                            <li>Historique des communications</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                    <h2 class="display-5 fw-bold mb-3">Tarifs Transparents</h2>
                    <p class="lead text-muted">
                        Choisissez la formule qui correspond à la taille de votre établissement
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="pricing-card">
                        <h3 class="pricing-title">Starter</h3>
                        <div class="pricing-price">Gratuit</div>
                        <div class="pricing-period">Jusqu'à 50 étudiants</div>
                        <ul class="pricing-features">
                            <li>Gestion de base des étudiants</li>
                            <li>Notes et bulletins simples</li>
                            <li>Emploi du temps basique</li>
                            <li>Support par email</li>
                            <li>1 utilisateur admin</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-primary w-100">Commencer gratuitement</a>
                    </div>
                </div>

                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="pricing-card featured">
                        <div class="pricing-badge">Le plus populaire</div>
                        <h3 class="pricing-title">Professional</h3>
                        <div class="pricing-price">49€</div>
                        <div class="pricing-period">par mois / jusqu'à 500 étudiants</div>
                        <ul class="pricing-features">
                            <li>Toutes les fonctionnalités Starter</li>
                            <li>Modules avancés complets</li>
                            <li>Rapports et statistiques</li>
                            <li>Support prioritaire 24/7</li>
                            <li>Utilisateurs illimités</li>
                            <li>Personnalisation avancée</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary-custom w-100">Choisir Professional</a>
                    </div>
                </div>

                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="pricing-card">
                        <h3 class="pricing-title">Enterprise</h3>
                        <div class="pricing-price">Sur mesure</div>
                        <div class="pricing-period">Étudiants illimités</div>
                        <ul class="pricing-features">
                            <li>Toutes les fonctionnalités Pro</li>
                            <li>Intégrations personnalisées</li>
                            <li>Formation dédiée</li>
                            <li>Support dédié</li>
                            <li>SLA garanti</li>
                            <li>Développements sur mesure</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-primary w-100">Nous contacter</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container text-center" data-aos="fade-up">
            <h2>Prêt à transformer votre établissement ?</h2>
            <p>Rejoignez des centaines d'établissements qui font confiance à KLASSCI</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="#demo" class="btn btn-white">
                    <i class="fas fa-play me-2"></i>
                    Demander une démo
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-light">
                    <i class="fas fa-rocket me-2"></i>
                    Commencer maintenant
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>
                        <i class="fas fa-graduation-cap me-2"></i>
                        KLASSCI
                    </h5>
                    <p class="text-muted">
                        La solution moderne de gestion scolaire pour les établissements d'enseignement supérieur.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-muted">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-muted">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-muted">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Produit</h5>
                    <ul class="list-unstyled">
                        <li><a href="#features">Fonctionnalités</a></li>
                        <li><a href="#modules">Modules</a></li>
                        <li><a href="#pricing">Tarifs</a></li>
                        <li><a href="#demo">Démo</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#help">Centre d'aide</a></li>
                        <li><a href="#docs">Documentation</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#status">Statut</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Entreprise</h5>
                    <ul class="list-unstyled">
                        <li><a href="#about">À propos</a></li>
                        <li><a href="#careers">Carrières</a></li>
                        <li><a href="#press">Presse</a></li>
                        <li><a href="#partners">Partenaires</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Légal</h5>
                    <ul class="list-unstyled">
                        <li><a href="#privacy">Confidentialité</a></li>
                        <li><a href="#terms">Conditions</a></li>
                        <li><a href="#security">Sécurité</a></li>
                        <li><a href="#cookies">Cookies</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 KLASSCI. Tous droits réservés. Développé avec ❤️ pour l'éducation.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Counter animation
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
                const increment = target / 100;
                let current = 0;

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = counter.textContent.replace(/\d+/, target);
                        clearInterval(timer);
                    } else {
                        counter.textContent = counter.textContent.replace(/\d+/, Math.floor(current));
                    }
                }, 20);
            });
        }

        // Trigger counter animation when stats section is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</body>
</html>
