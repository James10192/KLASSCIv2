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

    <!-- ======= STYLES KLASSCI BLEU ======= -->
    <style>
    :root {
      --klassci-blue-dark: #0453cb;
      --klassci-blue: #1b64d4;
      --klassci-blue-light: #5e91de;
      --klassci-bg-light: #f3f7fd;
    }

    body, .bg-light, .bg-white {
      background: var(--klassci-bg-light) !important;
    }

    /* Navbar */
    .navbar, .navbar.bg-white, .navbar.bg-transparent {
      background: #fff !important;
      border-bottom: 1px solid #e5eaf2;
    }
    .navbar .navbar-brand {
      color: var(--klassci-blue-dark) !important;
      font-weight: bold;
    }
    .navbar .nav-link {
      color: var(--klassci-blue-dark) !important;
      font-weight: 500;
      transition: color 0.2s;
    }
    .navbar .nav-link:hover, .navbar .nav-link.active {
      color: var(--klassci-blue-light) !important;
    }
    .navbar .btn-primary, .navbar .btn {
      background: linear-gradient(90deg, var(--klassci-blue-dark), var(--klassci-blue-light));
      border: none;
      color: #fff;
      font-weight: 600;
      border-radius: 6px;
      transition: background 0.2s;
    }
    .navbar .btn-primary:hover, .navbar .btn:hover {
      background: var(--klassci-blue) !important;
    }

    /* Hero */
    .hero-section {
      background: linear-gradient(120deg, var(--klassci-blue-dark) 0%, var(--klassci-blue-light) 100%) !important;
      color: #fff;
    }
    .hero-section h1, .hero-section h2, .hero-section h3 {
      color: #fff !important;
    }
    .hero-section .btn-primary, .hero-section .btn {
      background: linear-gradient(90deg, var(--klassci-blue-dark), var(--klassci-blue-light));
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      box-shadow: 0 2px 12px 0 rgba(27,100,212,0.08);
    }
    .hero-section .btn-primary:hover, .hero-section .btn:hover {
      background: var(--klassci-blue) !important;
    }

    /* Features/Modules */
    #features, .features-section, .modules-section {
      background: #fff !important;
    }
    .feature-card, .module-card {
      border-radius: 16px;
      box-shadow: 0 2px 16px 0 rgba(27,100,212,0.07);
      border: 1px solid #e5eaf2;
      background: #fff;
      transition: box-shadow 0.2s, border 0.2s;
    }
    .feature-card:hover, .module-card:hover {
      box-shadow: 0 6px 32px 0 rgba(27,100,212,0.13);
      border: 1.5px solid var(--klassci-blue-light);
    }
    .feature-card .icon, .module-card .icon {
      background: linear-gradient(135deg, var(--klassci-blue-dark), var(--klassci-blue-light));
      color: #fff;
      border-radius: 50%;
      padding: 16px;
      font-size: 2rem;
      margin-bottom: 12px;
      box-shadow: 0 2px 8px 0 rgba(27,100,212,0.10);
    }

    /* Pricing */
    .pricing-section, #pricing {
      background: var(--klassci-bg-light) !important;
    }
    .pricing-card {
      border-radius: 18px;
      box-shadow: 0 2px 16px 0 rgba(27,100,212,0.07);
      border: 1px solid #e5eaf2;
      background: #fff;
      transition: box-shadow 0.2s, border 0.2s;
    }
    .pricing-card .badge-popular {
      background: var(--klassci-blue-dark);
      color: #fff;
    }
    .pricing-card .btn-primary {
      background: linear-gradient(90deg, var(--klassci-blue-dark), var(--klassci-blue-light));
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
    }
    .pricing-card .btn-primary:hover {
      background: var(--klassci-blue) !important;
    }

    /* Contact */
    .contact-blue-section, #contact {
      background: #f3f7fd !important;
    }
    .contact-blue-section .contact-form-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 2px 16px 0 rgba(27,100,212,0.07);
      border: 1px solid #e5eaf2;
    }
    .contact-blue-section .contact-info-card {
      background: linear-gradient(120deg, var(--klassci-blue-dark), var(--klassci-blue-light));
      color: #fff;
      border-radius: 18px;
      box-shadow: 0 2px 16px 0 rgba(27,100,212,0.10);
    }
    .contact-blue-section .icon-circle {
      background: #fff;
      color: var(--klassci-blue-dark);
      border-radius: 50%;
      padding: 10px;
      font-size: 1.5rem;
      margin-right: 10px;
      box-shadow: 0 2px 8px 0 rgba(27,100,212,0.10);
    }
    .contact-blue-section .contact-info-card a {
      color: #fff;
      text-decoration: underline;
      transition: color 0.2s;
    }
    .contact-blue-section .contact-info-card a:hover {
      color: var(--klassci-blue-light);
    }
    .contact-blue-section .social-circle {
      background: #fff;
      color: var(--klassci-blue-dark);
      border-radius: 50%;
      padding: 10px;
      font-size: 1.2rem;
      margin-right: 8px;
      box-shadow: 0 2px 8px 0 rgba(27,100,212,0.10);
      transition: background 0.2s, color 0.2s;
    }
    .contact-blue-section .social-circle:hover {
      background: var(--klassci-blue-dark);
      color: #fff;
    }

    /* Events/Actualités */
    .events-section, #events {
      background: #fff !important;
    }
    .event-card {
      border-radius: 16px;
      box-shadow: 0 2px 16px 0 rgba(27,100,212,0.07);
      border: 1px solid #e5eaf2;
      background: #fff;
      transition: box-shadow 0.2s, border 0.2s;
    }
    .event-card .badge {
      background: var(--klassci-blue-dark);
      color: #fff;
    }

    /* CTA */
    .cta-section {
      background: linear-gradient(90deg, var(--klassci-blue-dark), var(--klassci-blue-light));
      color: #fff;
      border-radius: 18px;
      box-shadow: 0 2px 16px 0 rgba(27,100,212,0.10);
    }
    .cta-section .btn-primary {
      background: #fff;
      color: var(--klassci-blue-dark);
      border-radius: 8px;
      font-weight: 600;
      border: none;
      transition: background 0.2s, color 0.2s;
    }
    .cta-section .btn-primary:hover {
      background: var(--klassci-blue-light);
      color: #fff;
    }

    /* Footer */
    .klassci-footer {
      background: var(--klassci-blue-dark) !important;
      color: #fff;
      padding: 48px 0 0 0;
    }
    .klassci-footer .footer-title {
      color: var(--klassci-blue-light);
      font-weight: bold;
      margin-bottom: 16px;
    }
    .klassci-footer a {
      color: #e5eaf2;
      transition: color 0.2s;
    }
    .klassci-footer a:hover {
      color: var(--klassci-blue-light);
    }
    .klassci-footer .footer-social .social-icon {
      background: #fff;
      color: var(--klassci-blue-dark);
      border-radius: 50%;
      padding: 10px;
      font-size: 1.2rem;
      margin-right: 8px;
      box-shadow: 0 2px 8px 0 rgba(27,100,212,0.10);
      transition: background 0.2s, color 0.2s;
    }
    .klassci-footer .footer-social .social-icon:hover {
      background: var(--klassci-blue-light);
      color: #fff;
    }
    .klassci-footer .footer-bottom {
      border-top: 1px solid #1b64d4;
      margin-top: 32px;
      padding: 16px 0;
      color: #e5eaf2;
      font-size: 0.95rem;
    }

    /* Divers */
    .btn-primary, .btn {
      background: linear-gradient(90deg, var(--klassci-blue-dark), var(--klassci-blue-light));
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      box-shadow: 0 2px 12px 0 rgba(27,100,212,0.08);
      transition: background 0.2s, color 0.2s;
    }
    .btn-primary:hover, .btn:hover {
      background: var(--klassci-blue) !important;
      color: #fff;
    }
    input, textarea, select {
      border: 1.5px solid #e5eaf2 !important;
      border-radius: 8px !important;
      background: #fff !important;
      color: var(--klassci-blue-dark) !important;
      font-size: 1rem;
      padding: 12px 16px;
      margin-bottom: 12px;
      transition: border 0.2s;
    }
    input:focus, textarea:focus, select:focus {
      border: 1.5px solid var(--klassci-blue-light) !important;
      outline: none;
    }
    label {
      color: var(--klassci-blue-dark) !important;
      font-weight: 500;
      font-size: 0.98rem;
    }
    </style>

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
    <!-- LOADER KLASSCI -->
    <div id="klassci-loader" style="position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:#fff;display:flex;align-items:center;justify-content:center;transition:opacity 0.5s;">
        <div style="display:flex;flex-direction:column;align-items:center;">
            <img src="/images/LOGO-KLASSCI-PNG.png" alt="KLASSCI Loader" style="width:110px;height:110px;animation:klassci-pulse 1.2s infinite alternate;filter:drop-shadow(0 4px 16px rgba(99,102,241,0.12));"/>
            <div style="margin-top:1.5rem;font-weight:600;color:#6366f1;letter-spacing:2px;font-size:1.1rem;">Chargement...</div>
        </div>
    </div>
    <style>
    @keyframes klassci-pulse {
        0% { transform: scale(1) rotate(0deg); filter:brightness(1); }
        50% { transform: scale(1.08) rotate(8deg); filter:brightness(1.15); }
        100% { transform: scale(1) rotate(-8deg); filter:brightness(1); }
    }
    #klassci-loader.hide { opacity:0; pointer-events:none; transition:opacity 0.5s; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('load', function() {
            setTimeout(function() {
                var loader = document.getElementById('klassci-loader');
                if(loader) loader.classList.add('hide');
                setTimeout(function(){ if(loader) loader.style.display='none'; }, 600);
            }, 400); // délai pour l'effet
        });
    });
    </script>
    <!-- FIN LOADER -->

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
    <section id="home" class="hero" style="background: linear-gradient(120deg, #f8fafc 60%, #e0e7ff 100%); min-height: 100vh; display: flex; align-items: center; position: relative; overflow: hidden; padding-top: 100px;">
      <div class="container">
        <div class="row align-items-center flex-lg-row flex-column-reverse">
          <!-- Texte à gauche -->
          <div class="col-lg-6 text-lg-start text-center">
            <span class="text-uppercase mb-2 d-inline-block" style="color:#6366f1; font-weight:700; letter-spacing:1px; font-size:1rem;">TOUT-EN-UN</span>
            <h1 class="fw-bold mb-3" style="font-size:2.7rem; line-height:1.1; color:#1e293b;">
              Automatise et simplifie la gestion de votre établissement, conçu pour des <span style="color:#6366f1; text-decoration:underline;">établissements plus efficaces</span>,
              des équipes plus sereines et des élèves mieux suivis.
            </h1>
            <p class="lead mb-4" style="color:#475569; font-size:1.25rem;">
              KLASSCI est un logiciel intelligent qui digitalise, simplifie et automatise la gestion des tâches administratives, pédagogiques des établissements et offre un suivi complet des performances des étudiants aux parents.
            </p>
            <div class="d-flex flex-wrap gap-3 justify-content-lg-start justify-content-center mb-4">
              <a href="#demo" class="btn btn-warning btn-lg px-4 py-2" style="color:#fff; font-weight:700; border-radius:999px; font-size:1.15rem; box-shadow:0 4px 16px rgba(245,158,11,0.10);">Demander une démo</a>
              <a href="#contact" class="btn btn-outline-primary btn-lg px-4 py-2" style="font-weight:700; border-radius:999px; font-size:1.15rem;">Nous contacter</a>
            </div>
          </div>
          <!-- Image à droite -->
          <div class="col-lg-6 mb-4 mb-lg-0 text-center position-relative">
            <div class="hero-img-eduo mx-auto" style="width: 580px; height: 580px; border-radius: 50px; overflow: hidden; position: relative; background: #fff; display: flex; align-items: center; justify-content: center;">
              <img src="/images/tableaudeborddemo.jpg" alt="KLASSCI Dashboard" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <!-- Motif SVG décoratif -->
            <svg style="position:absolute;top:-40px;right:-60px;z-index:0;opacity:0.13;" width="180" height="180" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="110" cy="110" r="110" fill="#6366f1"/>
            </svg>
          </div>
        </div>
      </div>
      <style>
        @media (max-width: 1199.98px) {
          .hero-img-eduo { width:340px !important; height:340px !important; }
        }
        @media (max-width: 991.98px) {
          .hero-img-eduo { width:220px !important; height:220px !important; }
        }
        @media (max-width: 767.98px) {
          .hero-img-eduo { width:140px !important; height:140px !important; }
          .hero h1 { font-size:2rem !important; }
        }
      </style>
    </section>

    <!-- Features Section -->
    <!-- <section id="features" class="features">
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
    </section> -->

    <!-- Fonctionnalités Avancées / FAQ Section -->
    <section iid="features" class="features" class="py-5" style="background:#f8fafc;">
      <div class="container">
        <div class="row align-items-center">
          <!-- Image à gauche -->
          <div class="col-lg-5 mb-4 mb-lg-0 text-center">
            <div class="faq-img-circle mx-auto" style="background:#fff; box-shadow:0 4px 24px 0 rgba(80,112,255,0.07); width:480px; height:480px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center;">
              <img src="/images/hand-finger-side.jpg" alt="FAQ Education" style="width:100%; height:100%; object-fit:cover;">
            </div>
          </div>
          <!-- FAQ à droite -->
          <div class="col-lg-7">
            <span class="text-uppercase" style="color:#6366f1; font-weight:700; letter-spacing:1px;">Fonctionnalités Clés
            </span>
            <h2 class="fw-bold mb-2" style="font-size:2.1rem; color:#1e293b;">Découvrez les atouts qui font de KLASSCI un logiciel de gestion scolaire intelligent, complet et personnalisable.</h2>
            <div class="faq-accordion-v2">
              <!-- Question 1 -->
              <div class="faq2-item active" data-color="orange">
                <button class="faq2-question active" type="button"><span class="faq2-icon" style="background:#f59e0b;"><i class="fas fa-robot"></i></span> Digitalisation & automatisation des évaluations <span class="faq2-arrow"><i class="fas fa-chevron-up"></i></span></button>
                <div class="faq2-answer" style="display:block;">
                  Automatisez la création, la distribution et la correction des évaluations. Gagnez du temps et réduisez les erreurs grâce à des processus 100 % numériques.
                </div>
              </div>
              <!-- Question 2 -->
              <div class="faq2-item" data-color="red">
                <button class="faq2-question" type="button"><span class="faq2-icon" style="background:#ef4444;"><i class="fas fa-file-alt"></i></span> Édition intelligente des bulletins de notes <span class="faq2-arrow"><i class="fas fa-chevron-down"></i></span></button>
                <div class="faq2-answer">
                  Générez des bulletins personnalisés en un clic, avec calcul automatique des moyennes, appréciations et export PDF pour chaque élève.
                </div>
              </div>
              <!-- Question 3 -->
              <div class="faq2-item" data-color="blue">
                <button class="faq2-question" type="button"><span class="faq2-icon" style="background:#3b82f6;"><i class="fas fa-calendar-alt"></i></span> Programmation automatique des emplois du temps <span class="faq2-arrow"><i class="fas fa-chevron-down"></i></span></button>
                <div class="faq2-answer">
                  Créez des emplois du temps optimisés en tenant compte des disponibilités, des salles et des contraintes pédagogiques. Modifications et notifications instantanées.
                </div>
              </div>
              <!-- Question 4 -->
              <div class="faq2-item" data-color="red">
                <button class="faq2-question" type="button"><span class="faq2-icon" style="background:#ef4444;"><i class="fas fa-coins"></i></span> Calcul simplifié des salaires des professeurs vacataires <span class="faq2-arrow"><i class="fas fa-chevron-down"></i></span></button>
                <div class="faq2-answer">
                  Calculez automatiquement les salaires en fonction des heures effectuées, des absences et des taux horaires. Export facile pour la comptabilité.
                </div>
              </div>
              <!-- Question 5 -->
              <div class="faq2-item" data-color="blue">
                <button class="faq2-question" type="button"><span class="faq2-icon" style="background:#3b82f6;"><i class="fas fa-user-check"></i></span> Suivi des présences et absences en temps réel <span class="faq2-arrow"><i class="fas fa-chevron-down"></i></span></button>
                <div class="faq2-answer">
                  Enregistrez les présences en un clic, visualisez les absences et retards, et générez des rapports détaillés pour un meilleur suivi des élèves.
                </div>
              </div>
              <!-- Question 6 -->
              <div class="faq2-item" data-color="orange">
                <button class="faq2-question" type="button"><span class="faq2-icon" style="background:#f59e0b;"><i class="fas fa-calculator"></i></span> Gestion comptable intégrée <span class="faq2-arrow"><i class="fas fa-chevron-down"></i></span></button>
                <div class="faq2-answer">
                  Suivez les paiements, gérez la facturation, les relances et obtenez une vision claire de la situation financière de l'établissement.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <style>
        .faq-img-circle img { border-radius:50%; }
        .faq-accordion-v2 { margin-top: 1.5rem; }
        .faq2-item { margin-bottom: 1.1rem; border-radius: 1rem; overflow: hidden; box-shadow: 0 2px 12px 0 rgba(80,112,255,0.06); background: #f4f8ff; transition: box-shadow 0.2s; }
        .faq2-item.active, .faq2-item:hover { box-shadow: 0 8px 32px 0 rgba(80,112,255,0.13); }
        .faq2-question {
          width: 100%;
          text-align: left;
          background: transparent;
          color: #1e293b;
          font-weight: 600;
          font-size: 1.08rem;
          border: none;
          outline: none;
          padding: 1.1rem 1.2rem 1.1rem 0.8rem;
          cursor: pointer;
          display: flex;
          align-items: center;
        }
        .faq2-item[data-color="orange"] .faq2-question.active, .faq2-item[data-color="orange"] .faq2-question:focus {
          background: #f59e0b;
          color: #fff;
        }
        .faq2-item[data-color="red"] .faq2-question.active, .faq2-item[data-color="red"] .faq2-question:focus {
          background: #ef4444;
          color: #fff;
        }
        .faq2-item[data-color="blue"] .faq2-question.active, .faq2-item[data-color="blue"] .faq2-question:focus {
          background: #3b82f6;
          color: #fff;
        }
        .faq2-icon {
          width: 36px;
          height: 36px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #fff;
          font-size: 1.2rem;
          margin-right: 1rem;
          flex-shrink: 0;
        }
        .faq2-arrow {
          margin-left: auto;
          font-size: 1.1rem;
          color: inherit;
        }
        .faq2-answer {
          background: #fff;
          color: #334155;
          font-size: 1rem;
          padding: 1.1rem 1.2rem 1.2rem 3.2rem;
          border-radius: 0 0 1rem 1rem;
          margin-top: 0;
          box-shadow: 0 2px 12px 0 rgba(80,112,255,0.06);
          display: none;
        }
        .faq2-item.active .faq2-answer { display: block; }
        .faq2-item:not(.active) .faq2-answer { display: none; }
        @media (max-width: 1199.98px) {
          .faq-img-circle { width:340px !important; height:340px !important; }
        }
        @media (max-width: 991.98px) {
          .faq-img-circle { width:220px !important; height:220px !important; }
        }
        @media (max-width: 767.98px) {
          .faq-img-circle { width:140px !important; height:140px !important; }
          .faq-accordion-v2 { margin-top: 1rem; }
          .faq2-answer { padding-left: 1.2rem; }
        }
      </style>
      <script>
        // Accordéon FAQ v2
        document.addEventListener('DOMContentLoaded', function() {
          var items = document.querySelectorAll('.faq-accordion-v2 .faq2-item');
          items.forEach(function(item) {
            var btn = item.querySelector('.faq2-question');
            btn.addEventListener('click', function() {
              if(item.classList.contains('active')) {
                item.classList.remove('active');
              } else {
                items.forEach(function(i){ i.classList.remove('active'); });
                item.classList.add('active');
              }
              // Change arrow icon
              document.querySelectorAll('.faq2-arrow i').forEach(function(i){ i.className = 'fas fa-chevron-down'; });
              var arrow = btn.querySelector('.faq2-arrow i');
              if(item.classList.contains('active')) arrow.className = 'fas fa-chevron-up';
            });
          });
        });
      </script>
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
    <!--<section id="pricing" class="py-5" style="background:#f8fafc;">
      <div class="container">
        <div class="text-center mb-5">
          <span class="text-uppercase" style="color:#6366f1; font-weight:700; letter-spacing:1px;">TARIFS</span>
          <h2 class="fw-bold" style="font-size:2.2rem; color:#1e293b;">Choisissez la formule adaptée à votre établissement</h2>
        </div>
        <div class="row g-4 justify-content-center">
          <!-- Essentiel -->
          <div class="col-lg-4 col-md-6">
            <div class="pricing-card h-100 d-flex flex-column align-items-center" style="border:2px solid #f59e0b; border-radius:1.5rem; box-shadow:0 4px 24px 0 rgba(245,158,11,0.07); background:#fff;">
              <h3 class="fw-bold mt-4 mb-2" style="color:#f59e0b; font-size:1.5rem;">Essentiel</h3>
              <div class="fw-bold mb-1" style="color:#f59e0b; font-size:2.5rem;">1 500 000 XOF</div>
              <div class="mb-2" style="color:#f59e0b; font-size:1.1rem;">1ère année</div>
              <div class="mb-2" style="color:#f59e0b; font-size:1.1rem;">1 200 000 XOF/an ou 120 000 XOF/mois</div>
              <ul class="list-unstyled w-100 px-4 mb-4" style="font-size:1.08rem; color:#334155;">
                <li class="py-2 border-bottom">Installation sur 4 postes</li>
                <li class="py-2 border-bottom">Capacité : 700 étudiants</li>
                <li class="py-2 border-bottom">Capacité : 20 professeurs</li>
                <li class="py-2 border-bottom">Maintenance à l'année</li>
                <li class="py-2 border-bottom">Manuel d'utilisation</li>
                <li class="py-2 border-bottom">Assistance 6/7</li>
                <li class="py-2 border-bottom">Mise à jour de l'ergonomie</li>
                <li class="py-2">Formation</li>
              </ul>
            </div>
          </div>
          <!-- Pro -->
          <div class="col-lg-4 col-md-6">
            <div class="pricing-card h-100 d-flex flex-column align-items-center position-relative" style="border:2px solid #6366f1; border-radius:1.5rem; box-shadow:0 4px 24px 0 rgba(99,102,241,0.07); background:#fff;">
              <div style="position:absolute;top:-18px;left:50%;transform:translateX(-50%);background:#6366f1;color:#fff;padding:0.4rem 1.2rem;border-radius:999px;font-size:1rem;font-weight:700;box-shadow:0 2px 8px rgba(99,102,241,0.15);">Le plus populaire</div>
              <h3 class="fw-bold mt-4 mb-2" style="color:#6366f1; font-size:1.5rem;">Pro</h3>
              <div class="fw-bold mb-1" style="color:#6366f1; font-size:2.5rem;">3 000 000 XOF</div>
              <div class="mb-2" style="color:#6366f1; font-size:1.1rem;">1ère année</div>
              <div class="mb-2" style="color:#6366f1; font-size:1.1rem;">2 400 000 XOF/an ou 240 000 XOF/mois</div>
              <ul class="list-unstyled w-100 px-4 mb-4" style="font-size:1.08rem; color:#334155;">
                <li class="py-2 border-bottom">Installation sur 9 postes</li>
                <li class="py-2 border-bottom">Capacité : 3 000 étudiants</li>
                <li class="py-2 border-bottom">Capacité : 30 professeurs</li>
                <li class="py-2 border-bottom">Maintenance à l'année</li>
                <li class="py-2 border-bottom">Manuel d'utilisation</li>
                <li class="py-2 border-bottom">Assistance 6/7</li>
                <li class="py-2 border-bottom">Mise à jour de l'ergonomie</li>
                <li class="py-2 border-bottom">Accès gratuit aux nouvelles fonctionnalités</li>
                <li class="py-2">Formation</li>
              </ul>
            </div>
          </div>
          <!-- Elite -->
          <div class="col-lg-4 col-md-6">
            <div class="pricing-card h-100 d-flex flex-column align-items-center" style="border:2px solid #0ea5e9; border-radius:1.5rem; box-shadow:0 4px 24px 0 rgba(14,165,233,0.07); background:#fff;">
              <h3 class="fw-bold mt-4 mb-2" style="color:#0ea5e9; font-size:1.5rem;">Elite</h3>
              <div class="fw-bold mb-1" style="color:#0ea5e9; font-size:2.5rem;">6 000 000 XOF</div>
              <div class="mb-2" style="color:#0ea5e9; font-size:1.1rem;">1ère année</div>
              <div class="mb-2" style="color:#0ea5e9; font-size:1.1rem;">4 800 000 XOF/an ou 480 000 XOF/mois</div>
              <ul class="list-unstyled w-100 px-4 mb-4" style="font-size:1.08rem; color:#334155;">
                <li class="py-2 border-bottom">Installation sur 30 postes</li>
                <li class="py-2 border-bottom">Capacité : illimitée (étudiants/professeurs)</li>
                <li class="py-2 border-bottom">Maintenance à l'année</li>
                <li class="py-2 border-bottom">Assistance 7/7</li>
                <li class="py-2 border-bottom">Manuel d'utilisation</li>
                <li class="py-2 border-bottom">Mise à jour de l'ergonomie</li>
                <li class="py-2 border-bottom">Accès gratuit aux nouvelles fonctionnalités</li>
                <li class="py-2">Formation</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <style>
        .pricing-card {
          border-radius: 1.5rem;
          box-shadow: 0 4px 24px 0 rgba(80,112,255,0.07);
          transition: box-shadow 0.2s, transform 0.2s;
          background: #fff;
          overflow: hidden;
          min-height: 540px;
        }
        .pricing-card:hover {
          box-shadow: 0 8px 32px 0 rgba(80,112,255,0.13);
          transform: translateY(-4px) scale(1.02);
        }
        .pricing-card .fw-bold {
          letter-spacing: 1px;
        }
        @media (max-width: 991.98px) {
          .pricing-card { min-height: 440px; }
        }
        @media (max-width: 767.98px) {
          .pricing-card { min-height: 340px; }
        }
      </style>
    </section>-->

    <!-- SECTION CONTACT PREMIUM -->
    <section id="contact" class="contact-premium-section" style="background: #f3f7fd; padding: 0 0 4rem 0;">
      <div class="contact-premium-wrapper" style="max-width: 1100px; margin: 0 auto; display: flex; gap: 3.5rem; align-items: stretch; justify-content: center; position: relative; z-index: 1; padding: 3.5rem 1rem 0 1rem;">
        <div class="contact-premium-form-card" style="flex: 1 1 420px; background: rgba(4,83,203,0.10); border-radius: 2.5rem; box-shadow: 0 12px 48px 0 rgba(27,100,212,0.13); padding: 3.2rem 2.5rem; display: flex; align-items: center; justify-content: center; min-width: 0; backdrop-filter: blur(8px);">
          <form class="contact-premium-form-float" style="width: 100%; display: flex; flex-direction: column; gap: 2.2rem;">
            <div class="form-floating-premium">
              <input type="text" name="name" id="contactName" class="form-control-premium" placeholder=" " required autocomplete="off">
              <label for="contactName">Nom complet</label>
            </div>
            <div class="form-floating-premium">
              <input type="email" name="email" id="contactEmail" class="form-control-premium" placeholder=" " required autocomplete="off">
              <label for="contactEmail">Adresse email</label>
            </div>
            <div class="form-floating-premium">
              <input type="tel" name="phone" id="contactPhone" class="form-control-premium" placeholder=" " autocomplete="off">
              <label for="contactPhone">Téléphone</label>
            </div>
            <div class="form-floating-premium">
              <textarea name="message" id="contactMessage" class="form-control-premium" placeholder=" " required style="min-height: 120px; resize: vertical;"></textarea>
              <label for="contactMessage">Message</label>
            </div>
            <button type="submit" class="contact-premium-btn-float">Envoyer ma demande</button>
          </form>
        </div>
        <div class="contact-premium-info-card" style="flex: 1 1 420px; background: linear-gradient(135deg,#0453cb 0%,#5e91de 60%,#1b64d4 100%); border-radius: 2.5rem; box-shadow: 0 12px 48px 0 rgba(27,100,212,0.18); color: #fff; position: relative; overflow: hidden; min-width: 0; display: flex; align-items: center; justify-content: center; padding: 3.2rem 2.5rem;">
          <svg class="contact-premium-bg-svg" width="340" height="340" viewBox="0 0 340 340" fill="none" xmlns="http://www.w3.org/2000/svg" style="position: absolute; right: -60px; bottom: -60px; z-index: 0; pointer-events: none;">
            <circle cx="170" cy="170" r="170" fill="url(#paint0_radial)" fill-opacity="0.18"/>
            <defs>
              <radialGradient id="paint0_radial" cx="0" cy="0" r="1" gradientTransform="translate(170 170) scale(170)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#fff"/>
                <stop offset="1" stop-color="#0453cb"/>
              </radialGradient>
            </defs>
          </svg>
          <div class="contact-premium-info-content" style="position: relative; z-index: 1; width: 100%;">
            <div class="contact-premium-info-title" style="font-size: 1.45rem; font-weight: 800; margin-bottom: 1.7rem; display: flex; align-items: center; gap: 1rem; letter-spacing:0.5px;">
              <span style="background: #fff; color: #0453cb; border-radius: 50%; padding: 0.7rem; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;"><i class="fas fa-headset"></i></span> Assistance & Infos
            </div>
            <div class="contact-premium-info-row" style="display: flex; align-items: center; gap: 1.1rem; margin-bottom: 1.3rem; font-size: 1.13rem;">
              <span class="contact-premium-info-icon" style="background: #fff; color: #0453cb; border-radius: 50%; width: 2.3rem; height: 2.3rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-envelope"></i></span>
              <a href="mailto:klassci@africandigitconsulting.com" style="color: #fff; text-decoration: underline; font-size:1.13rem;">klassci@africandigitconsulting.com</a>
            </div>
            <div class="contact-premium-info-row" style="display: flex; align-items: center; gap: 1.1rem; margin-bottom: 1.3rem; font-size: 1.13rem;">
              <span class="contact-premium-info-icon" style="background: #fff; color: #0453cb; border-radius: 50%; width: 2.3rem; height: 2.3rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-phone"></i></span>
              <a href="tel:+2252732797523" style="color: #fff; text-decoration: underline; font-size:1.13rem;">+225 27 32 797 538</a> / <a href="tel:+2250595459843" style="color: #fff; text-decoration: underline; font-size:1.13rem;">05 95 459 843</a>
            </div>
            <div class="contact-premium-info-row" style="display: flex; align-items: center; gap: 1.1rem; margin-bottom: 1.3rem; font-size: 1.13rem;">
              <span class="contact-premium-info-icon" style="background: #fff; color: #0453cb; border-radius: 50%; width: 2.3rem; height: 2.3rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-map-marker-alt"></i></span>
              <span>Abidjan, Côte d'Ivoire</span>
            </div>
            <div class="contact-premium-info-row" style="display: flex; align-items: center; gap: 1.1rem; margin-bottom: 1.3rem; font-size: 1.13rem;">
              <span class="contact-premium-info-icon" style="background: #fff; color: #0453cb; border-radius: 50%; width: 2.3rem; height: 2.3rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-clock"></i></span>
              <span>Lundi - Vendredi : 8h30 - 18h00</span>
            </div>
            <div class="contact-premium-socials" style="display: flex; gap: 1.2rem; margin-top: 2.2rem;">
              <a href="#" class="contact-premium-social" style="background: #fff; color: #0453cb; border-radius: 50%; width: 2.7rem; height: 2.7rem; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; transition: background 0.2s, color 0.2s, box-shadow 0.2s; box-shadow: 0 2px 8px 0 rgba(4,83,203,0.10);"><i class="fab fa-facebook-f"></i></a>
              <a href="#" class="contact-premium-social" style="background: #fff; color: #0453cb; border-radius: 50%; width: 2.7rem; height: 2.7rem; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; transition: background 0.2s, color 0.2s, box-shadow 0.2s; box-shadow: 0 2px 8px 0 rgba(4,83,203,0.10);"><i class="fab fa-linkedin-in"></i></a>
              <a href="#" class="contact-premium-social" style="background: #fff; color: #0453cb; border-radius: 50%; width: 2.7rem; height: 2.7rem; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; transition: background 0.2s, color 0.2s, box-shadow 0.2s; box-shadow: 0 2px 8px 0 rgba(4,83,203,0.10);"><i class="fab fa-twitter"></i></a>
            </div>
          </div>
        </div>
      </div>
      <style>
      .form-floating-premium {
        position: relative;
        margin-bottom: 0;
      }
      .form-control-premium {
        width: 100%;
        padding: 1.25rem 1.3rem 0.7rem 1.3rem;
        font-size: 1.13rem;
        border-radius: 1.5rem;
        border: 2.5px solid transparent;
        background: rgba(255,255,255,0.65);
        box-shadow: 0 4px 24px 0 rgba(27,100,212,0.10);
        color: #1b64d4;
        font-weight: 500;
        outline: none;
        transition: border 0.25s, box-shadow 0.25s, background 0.25s;
        backdrop-filter: blur(2px);
        min-height: 56px;
      }
      .form-control-premium:focus {
        border-image: linear-gradient(90deg,#0453cb,#5e91de,#1b64d4) 1;
        border-width: 2.5px;
        background: rgba(255,255,255,0.85);
        box-shadow: 0 6px 32px 0 rgba(27,100,212,0.18);
      }
      .form-control-premium::placeholder {
        color: transparent;
      }
      .form-floating-premium label {
        position: absolute;
        top: 1.1rem;
        left: 1.3rem;
        color: #0453cb;
        font-size: 1.08rem;
        font-weight: 700;
        pointer-events: none;
        background: transparent;
        transition: all 0.22s cubic-bezier(.4,0,.2,1);
        z-index: 2;
        letter-spacing: 0.2px;
        padding: 0 0.2rem;
      }
      .form-control-premium:focus + label,
      .form-control-premium:not(:placeholder-shown) + label {
        top: 0.18rem;
        left: 1.1rem;
        font-size: 0.98rem;
        color: #1b64d4;
        background: rgba(255,255,255,0.85);
        padding: 0 0.3rem;
        border-radius: 0.7rem;
        font-weight: 800;
      }
      .form-floating-premium textarea.form-control-premium {
        min-height: 120px;
        padding-top: 1.25rem;
        padding-bottom: 0.7rem;
        resize: vertical;
      }
      .contact-premium-btn-float {
        width: 100%;
        background: linear-gradient(90deg,#0453cb,#5e91de,#1b64d4);
        color: #fff;
        font-weight: 800;
        font-size: 1.18rem;
        border: none;
        border-radius: 1.5rem;
        padding: 1.2rem 0;
        margin-top: 0.5rem;
        box-shadow: 0 4px 24px 0 rgba(27,100,212,0.13);
        transition: background 0.2s, box-shadow 0.2s;
        cursor: pointer;
        letter-spacing:0.5px;
      }
      .contact-premium-btn-float:hover {
        background: linear-gradient(90deg,#1b64d4,#0453cb,#5e91de);
        box-shadow: 0 8px 32px 0 rgba(27,100,212,0.18);
      }
      @media (max-width: 1100px) {
        .contact-premium-wrapper { flex-direction: column; gap: 2.5rem; max-width: 98vw; padding: 2rem 0.5rem 0 0.5rem; }
        .contact-premium-form-card, .contact-premium-info-card { padding: 2rem 1rem; }
      }
      </style>
    </section>

    <!-- CTA Section -->
    <section class="cta" style="background: linear-gradient(120deg, #6366f1 0%, #0ea5e9 100%); padding: 0; position: relative;">
      <div class="container py-5">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="cta-card text-center mx-auto p-5" style="background:rgba(255,255,255,0.97); border-radius:2rem; box-shadow:0 8px 32px 0 rgba(80,112,255,0.13); position:relative;">
              <div class="cta-icon mb-3" style="width:70px; height:70px; background:#6366f1; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto; box-shadow:0 2px 12px 0 rgba(99,102,241,0.10);">
                <i class="fas fa-rocket fa-2x text-white"></i>
              </div>
              <h2 class="fw-bold mb-3" style="font-size:2.3rem; color:#1e293b;">Prêt à transformer votre établissement ?</h2>
              <p class="lead mb-4" style="color:#475569;">Commencer dès maintenant ; Nous contacter. Je veux un design avec des motifs.</p>
              <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="#demo" class="btn btn-primary btn-lg px-4 py-2" style="background:linear-gradient(90deg,#6366f1,#7c3aed); color:#fff; font-weight:700; border-radius:999px; font-size:1.15rem; box-shadow:0 4px 16px rgba(99,102,241,0.10);">
                  <i class="fas fa-play me-2"></i> Demander une démo
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg px-4 py-2" style="font-weight:700; border-radius:999px; font-size:1.15rem;">
                  <i class="fas fa-rocket me-2"></i> Commencer maintenant
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Motif SVG décoratif -->
      <svg style="position:absolute;top:-60px;left:-60px;z-index:0;opacity:0.10;" width="220" height="220" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="110" cy="110" r="110" fill="#fff"/>
      </svg>
      <svg style="position:absolute;bottom:-60px;right:-60px;z-index:0;opacity:0.10;" width="220" height="220" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="110" cy="110" r="110" fill="#fff"/>
      </svg>
      <style>
        .cta-card {
          max-width: 650px;
        }
        @media (max-width: 991.98px) {
          .cta-card { padding:2rem 1rem; }
        }
      </style>
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
                    <p class="text-white">
                        La solution moderne de gestion scolaire pour les établissements d'enseignement supérieur.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-white">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-white">
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
                <p>&copy; 2026 KLASSCI. Tous droits réservés. Développé avec ❤️ pour l'éducation.</p>
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
