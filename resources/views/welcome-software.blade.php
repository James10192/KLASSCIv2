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

        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .category-card {
            border-radius: 0.5rem;
            padding: 1.5rem;
            background-color: white;
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .border-purple {
            border-color: var(--purple) !important;
        }

        .module-block {
            background: #f4f8ff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 24px 0 rgba(80,112,255,0.07);
            border-left: 6px solid var(--mod-color);
            position: relative;
            min-height: 120px;
            margin-bottom: 0;
            padding: 1.5rem 1.5rem 1.5rem 1.2rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .module-block:hover {
            box-shadow: 0 8px 32px 0 rgba(80,112,255,0.13);
            transform: translateY(-4px) scale(1.02);
        }
        .module-block:before, .module-block:after {
            content: '';
            position: absolute;
            left: -6px;
            width: 2px;
            height: 24px;
            background: var(--mod-color);
            border-radius: 2px;
        }
        .module-block:before { top: -18px; }
        .module-block:after { bottom: -18px; }
        .module-icon-big {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 2.2rem;
            flex-shrink: 0;
            box-shadow: 0 2px 12px 0 rgba(80,112,255,0.10);
        }
        .module-content h5 {
            color: #1e293b;
            font-size: 1.18rem;
            font-weight: 700;
        }
        .module-content p {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 0;
        }
        @media (max-width: 991.98px) {
            .module-block { min-height: 110px; }
        }
        @media (max-width: 767.98px) {
            .module-block { flex-direction: column; align-items: flex-start; padding: 1.2rem; }
            .module-icon-big { margin-bottom: 1rem; }
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
                        <div style="font-size: 0.95rem; color: #64748b; letter-spacing: 1px; font-weight: 600; margin-bottom: 0.5rem;">TOUT-EN-UN</div>
                        <h1 style="font-family: 'Inter', 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 2.5rem; line-height: 1.2;">
                            Automatise et simplifie la gestion de votre établissement,<br>
                            conçu pour des établissements plus efficaces, des équipes plus sereines et des élèves mieux suivis.
                        </h1>
                        <p class="lead mt-3" style="font-size: 1.25rem; color: #fff; text-shadow: 0 2px 8px rgba(0,0,0,0.10); font-weight: 500;">
                            KLASSCI est un logiciel intelligent qui digitalise, simplifie et automatise la gestion des tâches administratives, pédagogiques des établissements et offre un suivi complet des performances des étudiants aux parents.
                        </p>
                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <a href="#demo" class="btn btn-warning btn-lg" style="color: #fff; font-weight: 700; min-width: 220px; font-size: 1.25rem; border-radius: 16px;">
                                Demander une démo
                            </a>
                            <a href="#contact" class="btn btn-outline-light btn-lg" style="font-weight: 700; min-width: 220px; font-size: 1.25rem; border-radius: 16px; color: #fff; border: 2px solid #fff; background: rgba(255,255,255,0.08);">
                                Nous contacter
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="hero-image d-flex justify-content-center align-items-center">
                        <img src="/images/tableaudeborddemo.jpg" alt="Aperçu logiciel KLASSCI sur PC et tablette" class="img-fluid" style="max-width: 90%; border-radius: 1rem; box-shadow: 0 8px 32px rgba(0,0,0,0.10);">
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
                    <h2 class="display-5 fw-bold mb-3">Fonctionnalités Clés</h2>
                    <p class="lead text-muted" style="font-size:1.2rem; color:#6b7280; font-weight:500;">
                        Découvrez les atouts qui font de KLASSCI un logiciel de gestion scolaire intelligent, complet et personnalisable.
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-robot"></i></div>
                        <h4>Digitalisation & automatisation des évaluations</h4>
                        <p>Automatisez la création, la distribution et la correction des évaluations. Gagnez du temps et réduisez les erreurs grâce à des processus 100 % numériques.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="150">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
                        <h4>Édition intelligente des bulletins de notes</h4>
                        <p>Générez des bulletins personnalisés en un clic, avec calcul automatique des moyennes, appréciations et export PDF pour chaque élève.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                        <h4>Programmation automatique des emplois du temps</h4>
                        <p>Créez des emplois du temps optimisés en tenant compte des disponibilités, des salles et des contraintes pédagogiques. Modifications et notifications instantanées.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="250">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-coins"></i></div>
                        <h4>Calcul simplifié des salaires des professeurs vacataires</h4>
                        <p>Calculez automatiquement les salaires en fonction des heures effectuées, des absences et des taux horaires. Export facile pour la comptabilité.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-user-check"></i></div>
                        <h4>Suivi des présences et absences en temps réel</h4>
                        <p>Enregistrez les présences en un clic, visualisez les absences et retards, et générez des rapports détaillés pour un meilleur suivi des élèves.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="350">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-calculator"></i></div>
                        <h4>Gestion comptable intégrée</h4>
                        <p>Suivez les paiements, gérez la facturation, les relances et obtenez une vision claire de la situation financière de l'établissement.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
                        <h4>Suivi parental autonome et personnalisé, accessible partout</h4>
                        <p>Offrez aux parents un accès sécurisé à l'évolution scolaire de leur enfant : notes, absences, messages, bulletins, accessible sur tous supports.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="450">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="fas fa-sliders-h"></i></div>
                        <h4>Personnalisable & évolutif</h4>
                        <p>KLASSCI s'adapte à vos besoins spécifiques : modules activables, interface personnalisable, évolutions régulières selon vos retours.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <section id="modules" class="py-5" style="background:#fff;">
      <div class="container">
        <div class="text-center mb-5">
          <span class="text-uppercase" style="color:#6366f1; font-weight:700; letter-spacing:1px;">NOS MODULES CLÉS</span>
          <h2 class="fw-bold" style="font-size:2.2rem; color:#1e293b;">Des modules puissants pour votre établissement</h2>
        </div>
        <div class="row g-4 justify-content-center">
          <!-- Bloc 1 -->
          <div class="col-md-6 col-lg-4 d-flex">
            <div class="module-block d-flex align-items-center w-100" style="--mod-color:#3b82f6;">
              <div class="module-icon-big" style="background:#3b82f6;"><i class="fas fa-user-graduate"></i></div>
              <div class="module-content ms-4">
                <h5 class="fw-bold mb-1">Gestion Académique</h5>
                <p class="mb-0 text-muted">Inscription et gestion des étudiants, organisation des filières et classes, gestion des matières et programmes, suivi du parcours académique, génération de certificats.</p>
              </div>
            </div>
          </div>
          <!-- Bloc 2 -->
          <div class="col-md-6 col-lg-4 d-flex">
            <div class="module-block d-flex align-items-center w-100" style="--mod-color:#f59e0b;">
              <div class="module-icon-big" style="background:#f59e0b;"><i class="fas fa-clipboard-check"></i></div>
              <div class="module-content ms-4">
                <h5 class="fw-bold mb-1">Évaluations & Notes</h5>
                <p class="mb-0 text-muted">Création d'examens et évaluations, saisie et calcul automatique des notes, génération de bulletins personnalisés, statistiques et analyses de performance, système de coefficients flexible.</p>
              </div>
            </div>
          </div>
          <!-- Bloc 3 -->
          <div class="col-md-6 col-lg-4 d-flex">
            <div class="module-block d-flex align-items-center w-100" style="--mod-color:#ef4444;">
              <div class="module-icon-big" style="background:#ef4444;"><i class="fas fa-calendar-week"></i></div>
              <div class="module-content ms-4">
                <h5 class="fw-bold mb-1">Planning & Emplois du Temps</h5>
                <p class="mb-0 text-muted">Planification automatique des cours, gestion des salles et ressources, détection des conflits d'horaires, notifications et rappels, export et impression des plannings.</p>
              </div>
            </div>
          </div>
          <!-- Bloc 4 -->
          <div class="col-md-6 col-lg-4 d-flex">
            <div class="module-block d-flex align-items-center w-100" style="--mod-color:#a3a31a;">
              <div class="module-icon-big" style="background:#a3a31a;"><i class="fas fa-comments"></i></div>
              <div class="module-content ms-4">
                <h5 class="fw-bold mb-1">Communication</h5>
                <p class="mb-0 text-muted">Système d'annonces et notifications, messagerie interne intégrée, communication avec les parents, alertes automatiques, historique des communications.</p>
              </div>
            </div>
          </div>
          <!-- Bloc 5 -->
          <div class="col-md-6 col-lg-4 d-flex">
            <div class="module-block d-flex align-items-center w-100" style="--mod-color:#a78bfa;">
              <div class="module-icon-big" style="background:#a78bfa;"><i class="fas fa-coins"></i></div>
              <div class="module-content ms-4">
                <h5 class="fw-bold mb-1">Gestion Comptable</h5>
                <p class="mb-0 text-muted">Suivi des paiements, gestion de la facturation, relances, vision claire de la situation financière, calcul simplifié des salaires des professeurs vacataires.</p>
              </div>
            </div>
          </div>
          <!-- Bloc 6 -->
          <div class="col-md-6 col-lg-4 d-flex">
            <div class="module-block d-flex align-items-center w-100" style="--mod-color:#10b981;">
              <div class="module-icon-big" style="background:#10b981;"><i class="fas fa-sliders-h"></i></div>
              <div class="module-content ms-4">
                <h5 class="fw-bold mb-1">Personnalisation & Évolutivité</h5>
                <p class="mb-0 text-muted">KLASSCI s'adapte à vos besoins spécifiques : modules activables, interface personnalisable, évolutions régulières selon vos retours.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing" style="background: #f8fafc;">
        <div class="container">
            <div class="text-center mb-5">
                <img src="/images/LOGO-KLASSCI-PNG.png" alt="KLASSCI" style="height: 60px; margin-bottom: 1.5rem;">
                <h2 class="display-5 fw-bold mb-3">Choisissez votre formule</h2>
                <p class="lead text-muted">Des solutions adaptées à la taille et aux besoins de votre établissement</p>
            </div>
            <div class="row g-4 justify-content-center">
                <!-- Essentiel -->
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card h-100" style="border:2px solid #f59e0b;">
                        <h3 class="pricing-title" style="color:#f59e0b;">Essentiel</h3>
                        <div class="pricing-price" style="color:#f59e0b; font-size:2.5rem; font-weight:800;">1 500 000 XOF</div>
                        <div class="pricing-period" style="color:#f59e0b;">1ère année</div>
                        <div class="mb-2" style="color:#f59e0b; font-size:1.1rem;">1 200 000 XOF/an ou 120 000 XOF/mois</div>
                        <ul class="pricing-features">
                            <li>Installation sur 4 postes</li>
                            <li>Capacité : 700 étudiants</li>
                            <li>Capacité : 20 professeurs</li>
                            <li>Maintenance à l'année</li>
                            <li>Manuel d'utilisation</li>
                            <li>Assistance 6/7</li>
                            <li>Mise à jour de l'ergonomie</li>
                            <li>Formation</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-warning w-100" style="font-weight:600; border-width:2px;">Demander un devis</a>
                    </div>
                </div>
                <!-- Pro -->
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card featured h-100" style="border:2px solid #6366f1; position:relative;">
                        <div style="position:absolute;top:-18px;left:50%;transform:translateX(-50%);background:#6366f1;color:#fff;padding:0.4rem 1.2rem;border-radius:999px;font-size:1rem;font-weight:700;box-shadow:0 2px 8px rgba(99,102,241,0.15);">Le plus populaire</div>
                        <h3 class="pricing-title" style="color:#6366f1;">Pro</h3>
                        <div class="pricing-price" style="color:#6366f1; font-size:2.5rem; font-weight:800;">3 000 000 XOF</div>
                        <div class="pricing-period" style="color:#6366f1;">1ère année</div>
                        <div class="mb-2" style="color:#6366f1; font-size:1.1rem;">2 400 000 XOF/an ou 240 000 XOF/mois</div>
                        <ul class="pricing-features">
                            <li>Installation sur 9 postes</li>
                            <li>Capacité : 3 000 étudiants</li>
                            <li>Capacité : 30 professeurs</li>
                            <li>Maintenance à l'année</li>
                            <li>Manuel d'utilisation</li>
                            <li>Assistance 6/7</li>
                            <li>Mise à jour de l'ergonomie</li>
                            <li>Accès gratuit aux nouvelles fonctionnalités</li>
                            <li>Formation</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary w-100" style="background:#6366f1;font-weight:700;font-size:1.1rem;">Choisir Pro</a>
                    </div>
                </div>
                <!-- Elite -->
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card h-100" style="border:2px solid #0ea5e9;">
                        <h3 class="pricing-title" style="color:#0ea5e9;">Elite</h3>
                        <div class="pricing-price" style="color:#0ea5e9; font-size:2.5rem; font-weight:800;">6 000 000 XOF</div>
                        <div class="pricing-period" style="color:#0ea5e9;">1ère année</div>
                        <div class="mb-2" style="color:#0ea5e9; font-size:1.1rem;">4 800 000 XOF/an ou 480 000 XOF/mois</div>
                        <ul class="pricing-features">
                            <li>Installation sur 30 postes</li>
                            <li>Capacité : illimitée (étudiants/professeurs)</li>
                            <li>Maintenance à l'année</li>
                            <li>Assistance 7/7</li>
                            <li>Manuel d'utilisation</li>
                            <li>Mise à jour de l'ergonomie</li>
                            <li>Accès gratuit aux nouvelles fonctionnalités</li>
                            <li>Formation</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-info w-100" style="font-weight:600; border-width:2px;">Contactez-nous</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container text-center" data-aos="fade-up">
            <h2>Prêt à transformer votre établissement ?</h2>
            <p>Commencer dès maintenant ; Nous contacter. Je veux un design avec des motifs. </p>
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

    <!-- Contact Section -->
    <section id="contact" class="py-5" style="background: linear-gradient(135deg, #f8fafc 60%, #e0e7ff 100%); position:relative;">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2 class="fw-bold mb-3" style="font-size:2.3rem; color:#6366f1;">Contactez-nous</h2>
                    <p class="lead text-muted mb-0" style="font-size:1.15rem;">Une question, une démo, un devis ? Notre équipe vous répond rapidement et vous accompagne dans votre projet de digitalisation scolaire.</p>
                </div>
            </div>
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-6">
                    <div class="card shadow-lg border-0 h-100" style="border-radius:1.5rem;">
                        <div class="card-body p-4">
                            <form method="POST" action="#" autocomplete="off">
                                <div class="mb-3">
                                    <label for="contactName" class="form-label">Nom complet</label>
                                    <input type="text" class="form-control form-control-lg" id="contactName" name="name" placeholder="Votre nom" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contactEmail" class="form-label">Adresse email</label>
                                    <input type="email" class="form-control form-control-lg" id="contactEmail" name="email" placeholder="exemple@email.com" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contactPhone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control form-control-lg" id="contactPhone" name="phone" placeholder="Votre numéro">
                                </div>
                                <div class="mb-3">
                                    <label for="contactMessage" class="form-label">Message</label>
                                    <textarea class="form-control form-control-lg" id="contactMessage" name="message" rows="4" placeholder="Décrivez votre besoin..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100" style="background: linear-gradient(90deg,#6366f1,#7c3aed); border:none; border-radius:1rem; font-weight:700;">Envoyer ma demande</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-lg border-0 h-100 d-flex flex-column justify-content-center align-items-center" style="border-radius:1.5rem; background: linear-gradient(135deg,#6366f1 10%,#a5b4fc 100%); color:#fff;">
                        <div class="card-body p-4 w-100">
                            <h4 class="fw-bold mb-3"><i class="fas fa-headset me-2"></i> Assistance & Infos</h4>
                            <ul class="list-unstyled mb-4" style="font-size:1.1rem;">
                                <li class="mb-3"><i class="fas fa-envelope me-2"></i> <a href="mailto:klassci@africandigitconsulting.com" style="color:#fff;text-decoration:underline;">klassci@africandigitconsulting.com</a></li>
                                <li class="mb-3"><i class="fas fa-phone me-2"></i> <a href="tel:+2252732797523" style="color:#fff;text-decoration:underline;">+225 27 32 79 75 23</a> / <a href="tel:+2250595459843" style="color:#fff;text-decoration:underline;">05 95 45 98 43</a></li>
                                <li class="mb-3"><i class="fas fa-map-marker-alt me-2"></i> Abidjan, Côte d'Ivoire</li>
                                <li><i class="fas fa-clock me-2"></i> Lundi - Vendredi : 8h30 - 18h00</li>
                            </ul>
                            <div class="d-flex gap-3 mt-3">
                                <a href="#" class="text-white fs-4"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="text-white fs-4"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="text-white fs-4"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Motif décoratif -->
            <svg style="position:absolute;bottom:-40px;right:-40px;z-index:0;opacity:0.12;" width="220" height="220" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="110" cy="110" r="110" fill="#6366f1"/>
            </svg>
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
