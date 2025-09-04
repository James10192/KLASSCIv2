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
    <!-- Polices personnalisées -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/futura-round" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- ======= DESIGN SYSTEM HOSTINGER MODERNE ======= -->
    <style>
    * {
      box-sizing: border-box;
    }

    :root {
      /* Couleurs primaires KLASSCI */
      --primary-blue: #1E4FC4;
      --primary-blue-dark: #1840A0;
      --primary-blue-light: #4F7FE7;
      
      /* Couleurs neutres modernes */
      --text-primary: #1A1A1A;
      --text-secondary: #6B7280;
      --text-light: #9CA3AF;
      
      /* Backgrounds */
      --bg-white: #FFFFFF;
      --bg-gray-50: #F9FAFB;
      --bg-gray-100: #F3F4F6;
      --bg-gray-200: #E5E7EB;
      
      /* Borders */
      --border-color: #E5E7EB;
      --border-light: #F3F4F6;
      
      /* Shadows */
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      
      /* Success/Accent colors */
      --success: #10B981;
      --success-light: #34D399;
      --warning: #F59E0B;
      --accent: #8B5CF6;

      /* Legacy compatibility */
      --klassci-blue-dark: var(--primary-blue);
      --klassci-blue: var(--primary-blue);
      --klassci-blue-light: var(--primary-blue-light);
      --klassci-bg-light: var(--bg-gray-50);
    }

    /* Base styles */
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 16px;
      line-height: 1.6;
      color: var(--text-primary);
      background: var(--bg-white);
      margin: 0;
      padding: 0;
    }

    /* Container moderne */
    .container, .container-fluid {
      max-width: 1200px;
      margin: 0 auto;
      padding-left: 1rem;
      padding-right: 1rem;
    }

    /* Navigation exactement style Hostinger */
    .navbar {
      background: var(--bg-white) !important;
      border-bottom: 1px solid #e1e5e9;
      padding: 0.25rem 0;
      position: sticky;
      top: 0;
      z-index: 1000;
      min-height: auto;
    }

    .navbar .container {
      display: grid !important;
      grid-template-columns: 1fr 2fr 1fr;
      align-items: center;
      width: 100%;
      gap: 1rem;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--primary-blue) !important;
      text-decoration: none;
      justify-self: start;
    }

    .navbar-collapse {
      display: flex !important;
      align-items: center;
      justify-content: center;
      justify-self: center;
    }

    .navbar-nav {
      display: flex;
      align-items: center;
      margin: 0 !important;
      list-style: none;
      padding: 0;
    }

    .navbar-nav .nav-item {
      display: flex;
      align-items: center;
    }

    .navbar-nav .nav-link {
      font-family: 'Futura Round', 'Inter', sans-serif;
      font-weight: 400;
      font-size: 1rem;
      color: #9CA3AF !important;
      margin: 0 3.5rem;
      padding: 0.5rem 0 !important;
      border-radius: 0;
      transition: color 0.2s ease;
      background: transparent !important;
      text-decoration: none;
      height: auto;
      line-height: 1.4;
    }

    .navbar-nav .nav-link:hover {
      color: var(--primary-blue) !important;
      background: transparent !important;
    }

    .navbar-toggler {
      display: none;
    }

    /* Bouton à droite */
    .navbar-button {
      justify-self: end;
    }

    /* Responsive mobile styles */
    @media (max-width: 991.98px) {
      .navbar {
        padding: 0.75rem 0;
      }
      
      .navbar .container {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        position: relative;
      }
      
      .navbar-brand {
        justify-self: auto;
        order: 1;
      }
      
      .navbar-button {
        justify-self: auto;
        order: 2;
        display: none; /* Caché sur mobile, affiché dans le menu déroulant */
      }
      
      .navbar-toggler {
        display: block;
        order: 3;
        background: none;
        border: 1px solid #e1e5e9;
        border-radius: 6px;
        padding: 0.5rem 0.625rem;
        font-size: 1rem;
        color: #2D3748;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      
      .navbar-toggler:hover {
        background-color: #f7fafc;
        border-color: #cbd5e0;
      }
      
      .navbar-toggler:focus {
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
      }
      
      .navbar-toggler i {
        font-size: 1.1rem;
      }
      
      .navbar-collapse {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #e1e5e9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
        justify-self: auto;
        z-index: 1000;
        display: none;
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s ease;
      }
      
      .navbar-collapse.show {
        display: flex !important;
        opacity: 1;
        transform: translateY(0);
      }
      
      .navbar-nav {
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        margin: 0 0 1rem 0;
      }
      
      .navbar-nav .nav-item {
        width: 100%;
      }
      
      .navbar-nav .nav-link {
        margin: 0;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f7fafc;
        color: #2D3748 !important;
        font-weight: 400;
      }
      
      .navbar-nav .nav-link:hover {
        background-color: #f7fafc;
        color: var(--primary-blue) !important;
      }
      
      /* Bouton dans le menu mobile */
      .navbar-collapse .mobile-login-btn {
        width: 100%;
        background: var(--primary-blue);
        color: white !important;
        border: none;
        padding: 0.875rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-align: center;
        margin-top: 0.5rem;
      }
      
      .navbar-collapse .mobile-login-btn:hover {
        background: #4338ca;
        transform: none;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
      }
    }
    
    /* Tablette styles */
    @media (min-width: 768px) and (max-width: 991.98px) {
      .navbar .container {
        padding: 0 2rem;
      }
      
      .navbar-nav .nav-link {
        margin: 0 0.75rem;
        font-size: 0.875rem;
      }
    }

    /* Boutons exactement style Hostinger */
    .btn {
      font-weight: 500;
      border-radius: 8px;
      padding: 0.625rem 1.25rem;
      font-size: 0.875rem;
      text-transform: none;
      letter-spacing: normal;
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: 1px solid transparent;
    }

    .btn-primary {
      background: var(--primary-blue);
      color: white;
      border: 1px solid var(--primary-blue);
    }

    .btn-primary:hover {
      background: var(--primary-blue-dark);
      border-color: var(--primary-blue-dark);
      color: white;
    }

    /* Style exact bouton "Mon compte" Hostinger */
    .btn-outline-secondary, .navbar .btn-outline-secondary {
      background: white;
      color: #2D3748;
      border: 1.5px solid #374151;
      font-weight: 500;
      padding: 0.625rem 1.25rem;
    }

    .btn-outline-secondary:hover {
      background: #f8fafc;
      color: #2D3748;
      border: 2px solid #1f2937;
    }

    .btn-success {
      background: var(--success);
      color: white;
    }

    .btn-success:hover {
      background: #059669;
      transform: translateY(-1px);
      color: white;
    }

    /* Cards modernes */
    .card, .feature-card, .module-card, .pricing-card, .event-card {
      background: var(--bg-white);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 2rem;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm);
      height: 100%;
    }

    .card:hover, .feature-card:hover, .module-card:hover, .pricing-card:hover, .event-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary-blue-light);
    }

    .card-icon, .feature-card .icon, .module-card .icon {
      width: 48px;
      height: 48px;
      background: var(--primary-blue);
      color: white;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }

    .card h3, .feature-card h3, .module-card h3 {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.75rem;
    }

    .card p, .feature-card p, .module-card p {
      color: var(--text-secondary);
      margin-bottom: 0;
    }

    /* Sections */
    .section, .features-section, .modules-section, .pricing-section, .events-section, .contact-blue-section {
      padding: 5rem 0;
    }

    .section-white, #features, .features-section, .modules-section, .events-section {
      background: var(--bg-white);
    }

    .section-gray, .pricing-section, #pricing, .contact-blue-section, #contact {
      background: var(--bg-gray-50);
    }

    .section-title {
      text-align: center;
      margin-bottom: 3rem;
    }

    .section-title h2 {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 1rem;
    }

    .section-title p {
      font-size: 1.125rem;
      color: var(--text-secondary);
      max-width: 600px;
      margin: 0 auto;
    }

    /* Hero Section exactement style Hostinger */
    .hero-section {
      background: #ffffff;
      padding: 8rem 0 0 0;
      min-height: auto;
      overflow: visible;
      position: relative;
      display: flex;
      align-items: flex-start;
      padding-top: 8rem;
    }

    .hero-content {
      text-align: left;
      max-width: none;
      margin: 0;
      padding: 0;
    }

    .hero-section h1 {
      font-size: clamp(1.875rem, 3.5vw, 2.75rem);
      font-weight: 700;
      line-height: 1.2;
      color: var(--text-primary);
      margin-bottom: 1.5rem;
    }

    .hero-section .lead {
      font-size: 1.125rem;
      color: var(--text-secondary);
      margin-bottom: 2rem;
      font-weight: 400;
      line-height: 1.6;
    }

    .hero-buttons {
      display: flex;
      gap: 1rem;
      justify-content: flex-start;
      flex-wrap: wrap;
      margin-bottom: 2rem;
    }

    /* Layout deux colonnes style Hostinger */
    .hero-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      align-items: center;
    }

    .hero-image-container {
      position: relative;
      text-align: center;
    }

    .hero-image-container img {
      max-width: 100%;
      height: auto;
      border-radius: 12px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    /* Styles pour le nouveau design */
    .btn-commencer:hover {
      background: linear-gradient(135deg, #e55a2b 0%, #e8851a 100%) !important;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
    }
    
    .hero-tablet-container img:hover {
      transform: perspective(1000px) rotateX(2deg) rotateY(-1deg) scale(1.02);
    }
    
    /* Effet hover bouton Se connecter */
    .navbar-button a:hover {
      background-color: #E5E7EB !important;
      color: #4B5563 !important;
    }

    /* Responsive Hero Section */
    @media (max-width: 768px) {
      .navbar {
        padding: 0.25rem 0;
      }
      
      .navbar-brand img {
        height: 70px !important;
      }
      
      .hero-section {
        padding: 1rem 0 0 0 !important;
        padding-top: 1rem !important;
      }
      
      .hero-slogan {
        font-size: 1.5rem !important;
        margin-bottom: 0.5rem !important;
      }
      
      .hero-title {
        font-size: clamp(1.8rem, 5vw, 2.2rem) !important;
        margin-bottom: 1rem !important;
      }
      
      .hero-description {
        font-size: 1rem !important;
        padding: 0 1rem;
        margin-bottom: 2rem !important;
      }
      
      .btn-commencer {
        padding: 0.75rem 1.5rem !important;
        font-size: 0.9rem !important;
      }
      
      .hero-tablet-container {
        margin-top: 1rem !important;
      }
      
      .hero-tablet-container img {
        width: 100% !important;
        margin-left: 0 !important;
        max-width: 100% !important;
      }
    }
      
      .hero-image-container img {
        max-width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      }
    }
    
    /* Corrections spécifiques pour très petits écrans */
    @media (max-width: 480px) {
      .container {
        padding-left: 1rem;
        padding-right: 1rem;
      }
      
      .hero-section {
        padding: 1.5rem 0;
      }
      
      .hero-content {
        padding: 0 0.5rem;
      }
      
      .navbar {
        padding: 0.5rem 0;
      }
      
      .navbar-brand {
        font-size: 1.25rem;
      }
      
      .navbar-collapse {
        margin: 0 -1rem; /* Étendre sur toute la largeur */
      }
    }

    /* Footer moderne */
    .footer, .klassci-footer {
      background: var(--text-primary);
      color: var(--bg-gray-200);
      padding: 3rem 0 1rem 0;
    }

    .footer h5, .klassci-footer h5, .klassci-footer .footer-title {
      color: white;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    .footer a, .klassci-footer a {
      color: var(--bg-gray-200);
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .footer a:hover, .klassci-footer a:hover {
      color: white;
    }

    .footer-bottom, .klassci-footer .footer-bottom {
      border-top: 1px solid #374151;
      margin-top: 2rem;
      padding-top: 1rem;
      text-align: center;
      color: var(--text-light);
    }

    /* Forms */
    .form-control, input, textarea, select {
      border: 2px solid var(--border-color);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: border-color 0.2s ease;
      width: 100%;
      background: white;
      color: var(--text-primary);
    }

    .form-control:focus, input:focus, textarea:focus, select:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(30, 79, 196, 0.1);
    }

    .form-label, label {
      font-weight: 500;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
      display: block;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .hero-buttons {
        flex-direction: column;
        align-items: center;
      }
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
    <style>
      .premium-form-bg {
        background: transparent !important;
        box-shadow: none !important;
        border-radius: 0 !important;
      }
      .premium-card-field {
        background: #fff;
        box-shadow: 0 8px 32px 0 #a78bfa18, 0 1.5px 8px 0 #a78bfa11;
        border-radius: 1.7rem;
        padding: 1.3rem 1.1rem;
        margin-bottom: 2rem;
        transition: box-shadow 0.22s cubic-bezier(.4,0,.2,1);
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
      }
      .premium-card-field:focus-within {
        box-shadow: 0 12px 48px 0 #a78bfa33, 0 2px 12px 0 #7c3aed22;
      }
      .premium-label {
        color: #6366f1;
        font-size: 1.12rem;
        font-weight: 600;
        margin-bottom: 0.7rem;
        display: block;
        letter-spacing: 0.1px;
      }
      .premium-input {
        width: 100%;
        background: #f9fafb !important;
        border: 2.5px solid #a78bfa33 !important;
        color: #1e293b !important;
        font-weight: 400 !important;
        font-size: 1.13rem !important;
        border-radius: 1.1rem !important;
        transition: border 0.22s, box-shadow 0.22s, background 0.22s;
        padding: 1.1rem 1rem !important;
        min-height: 56px !important;
        margin-bottom: 0;
        box-shadow: none !important;
      }
      .premium-input:focus {
        border: 2.5px solid #7c3aed !important;
        box-shadow: 0 4px 32px 0 #a78bfa33 !important;
        background: #fff !important;
        color: #1e293b !important;
        outline: none;
      }
      textarea.premium-input {
        min-height: 120px !important;
        resize: vertical;
      }
      .premium-form-row { margin-bottom: 0; }
      .contact-premium-btn-float {
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        display: block;
        background: linear-gradient(90deg,#6366f1 0%,#7c3aed 100%);
        color: #fff;
        font-size: 1.18rem;
        font-weight: 600;
        border: none;
        border-radius: 1.2rem;
        padding: 1.1rem 0;
        box-shadow: 0 4px 24px 0 #a78bfa33;
        transition: background 0.18s, box-shadow 0.18s;
      }
      .contact-premium-btn-float:hover {
        background: linear-gradient(90deg,#7c3aed 0%,#6366f1 100%);
        box-shadow: 0 8px 32px 0 #a78bfa33;
      }
    </style>
</head>
<body>
    <!-- LOADER KLASSCI -->
    <div id="klassci-loader" style="position:fixed;z-index:9999;top:0;left:0;width:100%;height:100vh;background:#fff;display:flex;align-items:center;justify-content:center;transition:opacity 0.5s;">
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
            <a class="navbar-brand" href="#home" style="text-decoration: none;">
                <img src="/images/Images landingPage/logo_klassci.png" alt="KLASSCI" style="height: 100px; width: auto;">
            </a>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fonctionnalités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#modules">Modules</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="#pricing">Tarifs</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <!-- Bouton mobile dans le menu déroulant -->
                <a href="{{ route('login') }}" class="mobile-login-btn d-lg-none">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Se connecter
                </a>
            </div>

            <div class="navbar-button">
                <a href="{{ route('login') }}" class="btn" style="font-family: 'Futura Round', 'Inter', sans-serif; background-color: #F9FAFB; color: #6B7280; border: 2px solid #E5E7EB; padding: 0.75rem 1.75rem; border-radius: 50px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; font-size: 0.95rem;">
                    Se connecter
                </a>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section - Design exact -->
    <section id="home" class="hero-section">
      <div class="container">
        <div class="row justify-content-center text-center">
          <div class="col-lg-8">
            <!-- Slogan avec police Merry Christmas -->
            <p class="hero-slogan" style="font-family: 'Dancing Script', cursive; font-size: 2.5rem; color: #2d3748; margin-bottom: 1rem; font-weight: 600;">
              Plus simple la vie à l'école !
            </p>
            
            <!-- Titre principal -->
            <h1 class="hero-title" style="font-family: 'Futura Round', 'Inter', sans-serif; font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 700; color: #1a202c; line-height: 1.2; margin-bottom: 2rem;">
              Un clic pour tout piloter,<br>
              de l'administratif à la pédagogie.
            </h1>
            
            <!-- Description -->
            <p class="hero-description" style="font-size: 1.25rem; color: #4a5568; max-width: 700px; margin: 0 auto 3rem; line-height: 1.6;">
              Simplifiez l'essentiel de vos tâches pédagogiques et administratives, et visualisez en un seul clic l'état global de la gestion de votre établissement.
            </p>
            
            <!-- Bouton Commencer -->
            <button class="btn-commencer" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; border: none; padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 25px; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3); transition: all 0.3s ease; cursor: pointer; font-family: 'Futura Round', 'Inter', sans-serif;">
              Commencer
            </button>
          </div>
        </div>
        
        <!-- Image tablette pleine largeur -->
        <div style="width: 100%; margin-left: 0; margin-top: 0.5rem; padding: 0; overflow-x: hidden;">
          <div class="hero-tablet-container" style="position: relative; text-align: center; padding: 0; margin: 0;">
            <img src="/images/Images landingPage/Sans titre - 2_Plan de travail 1.png" alt="KLASSCI Dashboard" 
                 style="width: 100%; height: auto; display: block; margin: 0; padding: 0; border-radius: 0;">
          </div>
        </div>
      </div>
    </section>

    <!-- Section CRM - Design exact reproduction -->
    <section style="background: #f8f9fa; padding: 2rem 0 5rem 0; position: relative; overflow-x: hidden; width: 100%; max-width: 100%;">
      <div class="container-fluid" style="overflow-x: hidden; max-width: 100%; width: 100%;">
        <!-- Titre principal avec style exact -->
        <div style="text-align: center; margin-bottom: -6rem;">
          <h2 class="mobile-title-contrast" style="font-family: 'Futura Round', sans-serif; font-size: clamp(2rem, 4vw, 2.8rem); color: #1a202c; line-height: 1.3; margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto; font-weight: 400; text-align: center;">
            Découvrez pourquoi KLASSCI est le <span style="font-weight: 800;">CRM éducatif le plus complet</span>, pensé pour la <span style="font-weight: 800;">performance et l'efficacité pédagogique</span>.
          </h2>
        </div>

        <!-- Layout exact : Chapeau tout à gauche + Palette centre + Fonctionnalités -->
        <div class="row align-items-center" style="min-height: 1000px; margin: 0; width: 100%; overflow-x: hidden;">
          <!-- Version mobile : titre et toque adaptés -->
          <style>
            @media (max-width: 768px) {
              .mobile-toque-container {
                height: 400px !important;
                min-height: 400px !important;
              }
              .mobile-toque-img {
                width: 90% !important;
                height: 350px !important;
                margin-left: 0 !important;
                max-width: 90% !important;
              }
              .mobile-text-contrast {
                color: #1a202c !important;
                font-weight: 700 !important;
                text-shadow: 1px 1px 2px rgba(255,255,255,0.8) !important;
                background-color: rgba(255,255,255,0.9) !important;
                padding: 2px 4px !important;
                border-radius: 3px !important;
              }
              .mobile-section-height {
                min-height: 800px !important;
              }
              .mobile-title-contrast {
                color: #1a202c !important;
                font-weight: 700 !important;
                text-shadow: 1px 1px 3px rgba(255,255,255,0.9) !important;
                background-color: rgba(255,255,255,0.95) !important;
                padding: 4px 8px !important;
                border-radius: 4px !important;
                display: inline !important;
              }
              body, html {
                overflow-x: hidden !important;
                max-width: 100% !important;
                width: 100% !important;
              }
              .container-fluid {
                overflow-x: hidden !important;
                max-width: 100% !important;
                width: 100% !important;
              }
              .row {
                margin: 0 !important;
                overflow-x: hidden !important;
                max-width: 100% !important;
              }
            }
          </style>
          <!-- Chapeau de diplômé complètement à gauche -->
          <div class="col-lg-4 col-md-4" style="padding: 0; margin: 0; overflow: hidden; max-width: 33.33%;">
            <div class="mobile-toque-container" style="display: flex; align-items: center; justify-content: center; height: 1000px; width: 100%; position: relative; z-index: 2;">
              <img src="/images/Images landingPage/Sans titre - 2-06.png" alt="Chapeau de diplômé" 
                   class="mobile-toque-img" style="width: 100%; height: 950px; object-fit: contain; filter: drop-shadow(0 15px 35px rgba(0, 0, 0, 0.2)); margin-left: 0;">
            </div>
          </div>

          <!-- Fonctionnalités avec cercles exacts -->
          <div class="col-lg-8 col-md-8" style="padding-left: 2rem; max-width: 66.66%; overflow-x: hidden;">
            <div class="row">
              <!-- Fonctionnalité 1 -->
              <div class="col-md-6 mb-4">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                  <div style="background: transparent; color: #1E4FC4; width: 32px; height: 32px; border: 2px solid #1E4FC4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">1</div>
                  <div>
                    <h5 class="mobile-title-contrast" style="color: #1E4FC4; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; font-family: 'Futura Round', sans-serif; text-transform: uppercase;">DIGITALISER LES INSCRIPTIONS</h5>
                    <p class="mobile-text-contrast" style="color: #666; font-size: 0.9rem; line-height: 1.5; margin: 0;">Via un processus simple, organisé et automatisé le suivi des inscriptions.</p>
                  </div>
                </div>
              </div>

              <!-- Fonctionnalité 2 -->
              <div class="col-md-6 mb-4">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                  <div style="background: transparent; color: #1E4FC4; width: 32px; height: 32px; border: 2px solid #1E4FC4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">2</div>
                  <div>
                    <h5 class="mobile-title-contrast" style="color: #1E4FC4; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; font-family: 'Futura Round', sans-serif; text-transform: uppercase;">CRÉATION DE DOSSIER NUMÉRIQUE</h5>
                    <p class="mobile-text-contrast" style="color: #666; font-size: 0.9rem; line-height: 1.5; margin: 0;">Centralise les informations des étudiants, les ordonnées, les archives et donne un meilleur suivi et une disponibilité constante au dossier.</p>
                  </div>
                </div>
              </div>

              <!-- Fonctionnalité 3 -->
              <div class="col-md-6 mb-4">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                  <div style="background: transparent; color: #1E4FC4; width: 32px; height: 32px; border: 2px solid #1E4FC4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">3</div>
                  <div>
                    <h5 class="mobile-text-contrast" style="color: #1E4FC4; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; font-family: 'Futura Round', sans-serif; text-transform: uppercase;">PROGRAMMATION AUTOMATIQUE DES EMPLOIS DU TEMPS</h5>
                    <p class="mobile-text-contrast" style="color: #666; font-size: 0.9rem; line-height: 1.5; margin: 0;">Créez des emplois du temps optimisés en tenant compte des disponibilités, des salles et des contraintes pédagogiques. Modifications et notifications instantanées.</p>
                  </div>
                </div>
              </div>

              <!-- Fonctionnalité 4 -->
              <div class="col-md-6 mb-4">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                  <div style="background: transparent; color: #1E4FC4; width: 32px; height: 32px; border: 2px solid #1E4FC4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">4</div>
                  <div>
                    <h5 class="mobile-text-contrast" style="color: #1E4FC4; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; font-family: 'Futura Round', sans-serif; text-transform: uppercase;">ASSURER LA TRAÇABILITÉ DU TRAVAIL ENSEIGNANT</h5>
                    <p class="mobile-text-contrast" style="color: #666; font-size: 0.9rem; line-height: 1.5; margin: 0;">Calcul de manière automatique et sécurisé le nombre d'heures effectuées par les professeurs vacataires et titulaires.</p>
                  </div>
                </div>
              </div>

              <!-- Fonctionnalité 5 -->
              <div class="col-md-6 mb-4">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                  <div style="background: transparent; color: #1E4FC4; width: 32px; height: 32px; border: 2px solid #1E4FC4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">5</div>
                  <div>
                    <h5 class="mobile-text-contrast" style="color: #1E4FC4; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; font-family: 'Futura Round', sans-serif; text-transform: uppercase;">SUIVI DES PRÉSENCES ET ABSENCES EN TEMPS RÉEL</h5>
                    <p class="mobile-text-contrast" style="color: #666; font-size: 0.9rem; line-height: 1.5; margin: 0;">Enregistrez les présences en un clic, visualisez les absences et retards, et générez des rapports détaillés pour un meilleur suivi des élèves.</p>
                  </div>
                </div>
              </div>

              <!-- Fonctionnalité 6 -->
              <div class="col-md-6 mb-4">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                  <div style="background: transparent; color: #1E4FC4; width: 32px; height: 32px; border: 2px solid #1E4FC4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">6</div>
                  <div>
                    <h5 class="mobile-text-contrast" style="color: #1E4FC4; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; font-family: 'Futura Round', sans-serif; text-transform: uppercase;">GESTION COMPTABLE INTÉGRÉE</h5>
                    <p class="mobile-text-contrast" style="color: #666; font-size: 0.9rem; line-height: 1.5; margin: 0;">Suivez les paiements, gérez la facturation, les relances et obtenez une vision claire de la situation financière de l'établissement.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Nouvelle Section Modules - Design moderne -->
    <section style="padding: 5rem 0; background: linear-gradient(180deg, #ffffff 0%, #f0f4ff 100%); position: relative;">
      <div class="container" style="max-width: 100%;">
        <!-- Titre centré -->
        <div class="text-center mb-5" style="max-width: 800px; margin: 0 auto;">
          <h2 style="font-family: 'Futura Round', sans-serif; font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 400; 
                    color: #1a202c; margin-bottom: 2rem; line-height: 1.3;">
            Des modules<br>
            <span style="font-weight: 800;">au cœur de la performance</span><br>
            <span style="font-weight: 400;">de votre établissement.</span>
          </h2>
        </div>
        
        <!-- Grille 3x2 des modules -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); 
                   gap: 2rem; max-width: 1200px; margin: 0 auto;">
          
          <!-- Module 1: Gestion Académique - Card pleine -->
          <div style="background: #5B8DEF; 
                     border-radius: 35px; padding: 2rem; text-align: left; 
                     box-shadow: 0 8px 25px rgba(91, 141, 239, 0.3); 
                     position: relative;">
            <!-- Arcs bleus décoratifs - bas gauche et haut droite -->
            <div style="position: absolute; bottom: -3px; left: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-bottom: 3px solid rgba(255, 255, 255, 0.4); border-left: 3px solid rgba(255, 255, 255, 0.4); 
                       border-radius: 25px;"></div>
            <div style="position: absolute; top: -3px; right: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-top: 3px solid rgba(255, 255, 255, 0.4); border-right: 3px solid rgba(255, 255, 255, 0.4); 
                       border-radius: 25px;"></div>
            <div style="width: 56px; height: 56px; background: rgba(255, 255, 255, 0.25); 
                       border-radius: 16px; display: flex; align-items: center; justify-content: center; 
                       margin-bottom: 1.5rem;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 14l9-5-9-5-9 5 9 5z" fill="white"/>
                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" fill="white"/>
              </svg>
            </div>
            <h4 style="color: white; font-weight: 700; margin-bottom: 1rem; font-size: 1.3rem; font-family: 'Futura Round', sans-serif;">
              Gestion Académique
            </h4>
            <p style="color: rgba(255, 255, 255, 0.9); margin: 0; line-height: 1.6; font-size: 0.95rem;">
              Inscription et gestion des étudiants, organisation des filières et classes, gestion des matières et programmes, suivi du parcours académique, génération de certificats.
            </p>
          </div>

          <!-- Module 2: Évaluations & Notes - Card outline -->
          <div style="background: white; 
                     border-radius: 35px; padding: 2rem; text-align: left; 
                     box-shadow: 0 8px 25px rgba(91, 141, 239, 0.15); 
                     position: relative;">
            <!-- Arcs bleus décoratifs - bas gauche et haut droite -->
            <div style="position: absolute; bottom: -3px; left: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-bottom: 3px solid #5B8DEF; border-left: 3px solid #5B8DEF; 
                       border-radius: 25px;"></div>
            <div style="position: absolute; top: -3px; right: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-top: 3px solid #5B8DEF; border-right: 3px solid #5B8DEF; 
                       border-radius: 25px;"></div>
            
            <div style="width: 56px; height: 56px; background: #5B8DEF; 
                       border-radius: 16px; display: flex; align-items: center; justify-content: center; 
                       margin-bottom: 1.5rem;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h4 style="color: #5B8DEF; font-weight: 700; margin-bottom: 1rem; font-size: 1.3rem; font-family: 'Futura Round', sans-serif;">
              Évaluations & Notes
            </h4>
            <p style="color: #7B9CFF; margin: 0; line-height: 1.6; font-size: 0.95rem;">
              Création d'examens et évaluations, saisie et calcul automatique des notes, génération de bulletins personnalisés, statistiques et analyses de performance, système de coefficients flexible.
            </p>
          </div>

          <!-- Module 3: Planning & Emplois du Temps - Card pleine -->
          <div style="background: #5B8DEF; 
                     border-radius: 35px; padding: 2rem; text-align: left; 
                     box-shadow: 0 8px 25px rgba(91, 141, 239, 0.3); 
                     position: relative;">
            <!-- Arcs bleus décoratifs - bas gauche et haut droite -->
            <div style="position: absolute; bottom: -3px; left: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-bottom: 3px solid rgba(255, 255, 255, 0.4); border-left: 3px solid rgba(255, 255, 255, 0.4); 
                       border-radius: 25px;"></div>
            <div style="position: absolute; top: -3px; right: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-top: 3px solid rgba(255, 255, 255, 0.4); border-right: 3px solid rgba(255, 255, 255, 0.4); 
                       border-radius: 25px;"></div>
            <div style="width: 56px; height: 56px; background: rgba(255, 255, 255, 0.25); 
                       border-radius: 16px; display: flex; align-items: center; justify-content: center; 
                       margin-bottom: 1.5rem;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h4 style="color: white; font-weight: 700; margin-bottom: 1rem; font-size: 1.3rem; font-family: 'Futura Round', sans-serif;">
              Planning &<br>Emplois du Temps
            </h4>
            <p style="color: rgba(255, 255, 255, 0.9); margin: 0; line-height: 1.6; font-size: 0.95rem;">
              Planification automatique des cours, gestion des salles et ressources, détection des conflits d'horaires, notifications et rappels, export et impression des plannings.
            </p>
          </div>

          <!-- Module 4: Communication - Card outline -->
          <div style="background: white; 
                     border-radius: 35px; padding: 2rem; text-align: left; 
                     box-shadow: 0 8px 25px rgba(91, 141, 239, 0.15); 
                     position: relative;">
            <!-- Arcs bleus décoratifs - bas gauche et haut droite -->
            <div style="position: absolute; bottom: -3px; left: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-bottom: 3px solid #5B8DEF; border-left: 3px solid #5B8DEF; 
                       border-radius: 25px;"></div>
            <div style="position: absolute; top: -3px; right: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-top: 3px solid #5B8DEF; border-right: 3px solid #5B8DEF; 
                       border-radius: 25px;"></div>
            
            <div style="width: 56px; height: 56px; background: #5B8DEF; 
                       border-radius: 16px; display: flex; align-items: center; justify-content: center; 
                       margin-bottom: 1.5rem;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h4 style="color: #5B8DEF; font-weight: 700; margin-bottom: 1rem; font-size: 1.3rem; font-family: 'Futura Round', sans-serif;">
              Communication
            </h4>
            <p style="color: #7B9CFF; margin: 0; line-height: 1.6; font-size: 0.95rem;">
              Système d'annonces et notifications, messagerie interne intégrée, communication avec les parents, alertes automatiques, historique des communications.
            </p>
          </div>

          <!-- Module 5: Gestion Comptable - Card pleine -->
          <div style="background: #5B8DEF; 
                     border-radius: 35px; padding: 2rem; text-align: left; 
                     box-shadow: 0 8px 25px rgba(91, 141, 239, 0.3); 
                     position: relative;">
            <!-- Arcs bleus décoratifs - bas gauche et haut droite -->
            <div style="position: absolute; bottom: -3px; left: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-bottom: 3px solid rgba(255, 255, 255, 0.4); border-left: 3px solid rgba(255, 255, 255, 0.4); 
                       border-radius: 25px;"></div>
            <div style="position: absolute; top: -3px; right: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-top: 3px solid rgba(255, 255, 255, 0.4); border-right: 3px solid rgba(255, 255, 255, 0.4); 
                       border-radius: 25px;"></div>
            <div style="width: 56px; height: 56px; background: rgba(255, 255, 255, 0.25); 
                       border-radius: 16px; display: flex; align-items: center; justify-content: center; 
                       margin-bottom: 1.5rem;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 1v22m5-18H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h4 style="color: white; font-weight: 700; margin-bottom: 1rem; font-size: 1.3rem; font-family: 'Futura Round', sans-serif;">
              Gestion Comptable
            </h4>
            <p style="color: rgba(255, 255, 255, 0.9); margin: 0; line-height: 1.6; font-size: 0.95rem;">
              Suivi des paiements, gestion de la facturation, relances, vision claire de la situation financière, calcul simplifié des salaires des professeurs vacataires.
            </p>
          </div>

          <!-- Module 6: Personnalisation & Évolutivité - Card outline -->
          <div style="background: white; 
                     border-radius: 35px; padding: 2rem; text-align: left; 
                     box-shadow: 0 8px 25px rgba(91, 141, 239, 0.15); 
                     position: relative;">
            <!-- Arcs bleus décoratifs - bas gauche et haut droite -->
            <div style="position: absolute; bottom: -3px; left: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-bottom: 3px solid #5B8DEF; border-left: 3px solid #5B8DEF; 
                       border-radius: 25px;"></div>
            <div style="position: absolute; top: -3px; right: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-top: 3px solid #5B8DEF; border-right: 3px solid #5B8DEF; 
                       border-radius: 25px;"></div>
            
            <div style="width: 56px; height: 56px; background: #5B8DEF; 
                       border-radius: 16px; display: flex; align-items: center; justify-content: center; 
                       margin-bottom: 1.5rem;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h4 style="color: #5B8DEF; font-weight: 700; margin-bottom: 1rem; font-size: 1.3rem; font-family: 'Futura Round', sans-serif;">
              Personnalisation<br>& Évolutivité
            </h4>
            <p style="color: #7B9CFF; margin: 0; line-height: 1.6; font-size: 0.95rem;">
              KLASSCI s'adapte à vos besoins spécifiques : modules activables, interface personnalisable, évolutions régulières selon vos retours.
            </p>
          </div>
        </div>

        <!-- Style responsive pour mobile -->
        <style>
          @media (max-width: 768px) {
            .container > div[style*="grid-template-columns"] {
              grid-template-columns: 1fr !important;
              gap: 1.5rem !important;
            }
          }
          @media (min-width: 769px) and (max-width: 1024px) {
            .container > div[style*="grid-template-columns"] {
              grid-template-columns: repeat(2, 1fr) !important;
            }
          }
        </style>
      </div>
    </section>

    <!-- Section Tarification -->
    <section style="padding: 5rem 0; background: white; position: relative;">
      <div class="container" style="max-width: 100%;">
        <!-- Titre centré -->
        <div class="text-center mb-5" style="max-width: 800px; margin: 0 auto;">
          <h2 style="font-family: 'Futura Round', sans-serif; font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 400; 
                    color: #1a202c; margin-bottom: 2rem; line-height: 1.3;">
            Choisissez votre <span style="font-weight: 800;">pack</span>
          </h2>
          <p style="font-size: 1.1rem; color: #666; margin: 0; line-height: 1.6;">
            Choisissez votre pack et faites de KLASSCI votre atout.
          </p>
        </div>
        
        <!-- Grille des plans de tarification -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); 
                   gap: 2rem; max-width: 1200px; margin: 0 auto;">
          
          <!-- Plan Essentiel -->
          <div style="background: #f8f9ff; 
                     border-radius: 20px; padding: 2.5rem 2rem; text-align: left; 
                     box-shadow: 0 4px 20px rgba(59, 130, 246, 0.1); 
                     position: relative;">
            <h3 style="color: #ff6b35; font-weight: 800; font-size: 2rem; margin-bottom: 2rem; font-family: 'Futura Round', sans-serif; text-align: center;">
              Essentiel
            </h3>
            
            <!-- Liste des fonctionnalités -->
            <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; color: #374151;">
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Installation sur 4 postes
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Capacité : 700 étudiants
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Capacité : 20 professeurs
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Maintenance à l'année
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Manuel d'utilisation
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Assistance 6/7
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Mise à jour de l'ergonomie
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Formation
              </li>
            </ul>
            
            <!-- Prix -->
            <div style="text-align: center; margin-top: 2rem;">
              <div style="margin-bottom: 1rem;">
                <span style="font-size: 1.75rem; font-weight: 800; color: #1a202c;">1 500 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">POUR LA PREMIÈRE ANNÉE</span>
              </div>
              <div style="margin-bottom: 1rem;">
                <span style="font-size: 1.5rem; font-weight: 700; color: #1a202c;">1 200 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">CHAQUE ANNÉE</span>
              </div>
              <div style="font-size: 0.875rem; color: #666; margin: 1rem 0;">OU</div>
              <div>
                <span style="font-size: 1.25rem; font-weight: 700; color: #1a202c;">120 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">CHAQUE MOIS</span>
              </div>
            </div>
          </div>

          <!-- Plan Pro (Recommandé) -->
          <div style="background: #ff9a7a; 
                     border-radius: 20px; padding: 2.5rem 2rem; text-align: left; 
                     box-shadow: 0 8px 30px rgba(255, 107, 53, 0.3); 
                     position: relative;
                     transform: scale(1.05);
                     z-index: 2;">
            <!-- Arcs bleus décoratifs - bas gauche et haut droite -->
            <div style="position: absolute; bottom: -3px; left: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-bottom: 3px solid #3b82f6; border-left: 3px solid #3b82f6; 
                       border-radius: 25px;"></div>
            <div style="position: absolute; top: -3px; right: -3px; width: 70px; height: 70px; 
                       border: 3px solid transparent; border-top: 3px solid #3b82f6; border-right: 3px solid #3b82f6; 
                       border-radius: 25px;"></div>
            
            <h3 style="color: #ff6b35; font-weight: 800; font-size: 2rem; margin-bottom: 2rem; font-family: 'Futura Round', sans-serif; text-align: center;">
              Pro
            </h3>
            
            <!-- Liste des fonctionnalités -->
            <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; color: #374151;">
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Installation sur 9 postes
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Capacité : 3 000 étudiants
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Capacité : 30 professeurs
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Maintenance à l'année
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Manuel d'utilisation
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Assistance 6/7
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Mise à jour de l'ergonomie
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Accès gratuit aux nouvelles fonctionnalités
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Formation
              </li>
            </ul>
            
            <!-- Prix -->
            <div style="text-align: center; margin-top: 2rem;">
              <div style="margin-bottom: 1rem;">
                <span style="font-size: 1.75rem; font-weight: 800; color: #1a202c;">3 000 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">POUR LA PREMIÈRE ANNÉE</span>
              </div>
              <div style="margin-bottom: 1rem;">
                <span style="font-size: 1.5rem; font-weight: 700; color: #1a202c;">2 400 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">CHAQUE ANNÉE</span>
              </div>
              <div style="font-size: 0.875rem; color: #666; margin: 1rem 0;">OU</div>
              <div>
                <span style="font-size: 1.25rem; font-weight: 700; color: #1a202c;">240 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">CHAQUE MOIS</span>
              </div>
            </div>
          </div>

          <!-- Plan Elite -->
          <div style="background: #f8f9ff; 
                     border-radius: 20px; padding: 2.5rem 2rem; text-align: left; 
                     box-shadow: 0 4px 20px rgba(59, 130, 246, 0.1); 
                     position: relative;">
            <h3 style="color: #ff6b35; font-weight: 800; font-size: 2rem; margin-bottom: 2rem; font-family: 'Futura Round', sans-serif; text-align: center;">
              Elite
            </h3>
            
            <!-- Liste des fonctionnalités -->
            <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; color: #374151;">
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Installation sur 30 postes
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Capacité : illimitée
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Capacité : illimitée
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Maintenance à l'année
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Assistance 7/7
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Manuel d'utilisation
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Mise à jour de l'ergonomie
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Accès gratuit aux nouvelles fonctionnalités
              </li>
              <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start;">
                <span style="width: 6px; height: 6px; background: #374151; border-radius: 50%; margin-top: 0.5rem; margin-right: 0.75rem; flex-shrink: 0;"></span>
                Formation
              </li>
            </ul>
            
            <!-- Prix -->
            <div style="text-align: center; margin-top: 2rem;">
              <div style="margin-bottom: 1rem;">
                <span style="font-size: 1.75rem; font-weight: 800; color: #1a202c;">6 000 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">POUR LA PREMIÈRE ANNÉE</span>
              </div>
              <div style="margin-bottom: 1rem;">
                <span style="font-size: 1.5rem; font-weight: 700; color: #1a202c;">4 800 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">CHAQUE ANNÉE</span>
              </div>
              <div style="font-size: 0.875rem; color: #666; margin: 1rem 0;">OU</div>
              <div>
                <span style="font-size: 1.25rem; font-weight: 700; color: #1a202c;">480 000 XOF</span><br>
                <span style="font-size: 0.875rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">CHAQUE MOIS</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Style responsive pour la grille de tarification -->
        <style>
          @media (max-width: 768px) {
            div[style*="grid-template-columns: repeat(3, 1fr)"] {
              grid-template-columns: 1fr !important;
              gap: 1.5rem !important;
            }
            div[style*="transform: scale(1.05)"] {
              transform: scale(1) !important;
            }
          }
          @media (min-width: 769px) and (max-width: 1024px) {
            div[style*="grid-template-columns: repeat(3, 1fr)"] {
              grid-template-columns: repeat(2, 1fr) !important;
            }
          }
        </style>
      </div>
    </section>

    <!-- Section Partenaire 0 FCFA -->
    <section style="padding: 0; margin: 0; width: 100%; display: block; height: 1020px;">
      <img src="/images/Images landingPage/Sans titre - 2-02.png" 
           alt="Dashboard KLASSCI - 0 FCFA" 
           style="width: 100%; height: 100%; object-fit: fill; display: block; margin: 0; padding: 0;">
    </section>

    <!-- Section Témoignage -->
    <section style="padding: 5rem 0; background: #f8f9fa; position: relative;">
      <div class="container">
        <div class="row align-items-center">
          <!-- Vidéo à gauche -->
          <div class="col-lg-5 col-md-12">
            <div style="position: relative; background: #6c757d; border-radius: 20px; 
                        aspect-ratio: 16/10; display: flex; align-items: center; justify-content: center;
                        cursor: pointer; overflow: hidden;">
              <!-- Bouton Play -->
              <div style="width: 80px; height: 80px; background: rgba(255, 255, 255, 0.9); 
                         border-radius: 50%; display: flex; align-items: center; justify-content: center;
                         box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); transition: all 0.3s ease;"
                   onmouseover="this.style.transform='scale(1.1)'; this.style.background='rgba(255, 255, 255, 1)';"
                   onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(255, 255, 255, 0.9)';">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" style="margin-left: 3px;">
                  <path d="M8 5v14l11-7z" fill="#6c757d"/>
                </svg>
              </div>
            </div>
          </div>
          
          <!-- Témoignage à droite -->
          <div class="col-lg-7 col-md-12">
            <div style="padding-left: 3rem;">
              <!-- Citation -->
              <blockquote style="font-size: 1.1rem; line-height: 1.6; color: #333; margin-bottom: 1.5rem; font-style: italic;">
                « Avant, pour l'édition de nos bulletins, nous utilisions Excel et rencontrions beaucoup de difficultés. Depuis que nous utilisons KLASSCI, nous n'avons plus d'erreurs et il nous facilite énormément le travail. »
              </blockquote>
              
              <!-- Lien Lire plus -->
              <a href="#" style="color: #1565c0; text-decoration: none; font-weight: 600; margin-bottom: 1rem; display: inline-block;"
                 onmouseover="this.style.textDecoration='underline';"
                 onmouseout="this.style.textDecoration='none';">
                Lire plus
              </a>
              
              <!-- Nom et qualité -->
              <div>
                <div style="font-weight: 700; color: #333; margin-bottom: 0.25rem;">Nom et prénom</div>
                <div style="color: #666; font-size: 0.9rem;">Qualité</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Sécurité -->
    <section style="padding: 5rem 0; background: #f5f5f7; position: relative;">
      <div class="container">
        <div class="row align-items-center">
          <!-- Image sécurité à gauche -->
          <div class="col-lg-6 col-md-12">
            <div style="text-align: center; margin-bottom: 2rem;">
              <img src="/images/Images landingPage/Sans titre - 2-04.png" 
                   alt="Sécurité et protection des données KLASSCI" 
                   style="max-width: 100%; height: auto; border-radius: 20px;">
            </div>
          </div>
          
          <!-- Contenu sécurité à droite -->
          <div class="col-lg-6 col-md-12">
            <div style="padding-left: 2rem;">
              <!-- Titre -->
              <h2 style="font-family: 'Futura Round', 'Inter', sans-serif; font-size: clamp(2.5rem, 5vw, 3.5rem); 
                         font-weight: 700; color: #2563eb; margin-bottom: 2rem; line-height: 1.2;">
                Sécurité
                <br>
                <span style="font-weight: 600; font-size: 44px;">et confiance totales.</span>
              </h2>
              
              <!-- Paragraphe 1 -->
              <p style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                        font-size: 1.1rem; color: #4a5568; margin-bottom: 1.5rem; line-height: 1.6;">
                • Nous garantissons la protection optimale de vos données, dans le respect strict des normes RGPD.
              </p>
              
              <!-- Paragraphe 2 -->
              <p style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                        font-size: 1.1rem; color: #4a5568; margin-bottom: 0; line-height: 1.6;">
                • Notre équipe dédiée à la cyber sécurité travaille en continu pour renforcer et améliorer nos dispositifs de protection.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Support Client -->
    <section style="padding: 6rem 0; background: #ffffff; position: relative;">
      <div class="container" style="max-width: 1200px;">
        <div class="row align-items-center">
          <!-- Titre et liste à gauche -->
          <div class="col-lg-6 col-md-12">
            <div style="padding-right: 3rem;">
              <!-- Titre sur deux lignes -->
              <h2 style="font-family: 'Futura Round', 'Inter', sans-serif; font-size: clamp(2.2rem, 4vw, 3.2rem); 
                         font-weight: 700; color: #2563eb; margin-bottom: 3rem; line-height: 1.2; white-space: nowrap;">
                Support client disponible<br>
                24h/24 et 7j/7
              </h2>
              
              <!-- Liste des avantages -->
              <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                           font-size: 1.1rem; color: #4a5568; margin-bottom: 2rem; line-height: 1.6; 
                           position: relative; padding-left: 25px;">
                  <span style="position: absolute; left: 0; top: 0.2rem; color: #2563eb; font-weight: bold; font-size: 1.2rem;">•</span>
                  Accédez à des informations fiables à tout moment grâce à notre chatbot.
                </li>
                <li style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                           font-size: 1.1rem; color: #4a5568; margin-bottom: 2rem; line-height: 1.6; 
                           position: relative; padding-left: 25px;">
                  <span style="position: absolute; left: 0; top: 0.2rem; color: #2563eb; font-weight: bold; font-size: 1.2rem;">•</span>
                  Échangez directement avec notre service client par email, WhatsApp ou Telegram.
                </li>
                <li style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                           font-size: 1.1rem; color: #4a5568; margin-bottom: 2rem; line-height: 1.6; 
                           position: relative; padding-left: 25px;">
                  <span style="position: absolute; left: 0; top: 0.2rem; color: #2563eb; font-weight: bold; font-size: 1.2rem;">•</span>
                  Nos agents maîtrisent parfaitement le français et l'anglais, pour une communication fluide.
                </li>
                <li style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                           font-size: 1.1rem; color: #4a5568; margin-bottom: 0; line-height: 1.6; 
                           position: relative; padding-left: 25px;">
                  <span style="position: absolute; left: 0; top: 0.2rem; color: #2563eb; font-weight: bold; font-size: 1.2rem;">•</span>
                  Temps d'attente minimal : une réponse en moins de 2 minutes dans la majorité des cas.
                </li>
              </ul>
            </div>
          </div>
          
          <!-- Image des bulles de chat -->
          <div class="col-lg-6 col-md-12">
            <div style="text-align: center; padding: 2rem 0;">
              <img src="/images/Images landingPage/bulles.png" 
                   alt="Bulles de chat support KLASSCI" 
                   style="max-width: 100%; height: auto;">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Image CTA -->
    <section style="padding: 0; margin: 0; width: 100%; display: block; height: 720px;">
      <img src="/images/Images landingPage/Sans titre - 2-03.png" 
           alt="CTA Transform KLASSCI" 
           style="width: 100%; height: 100%; object-fit: fill; display: block; margin: 0; padding: 0;">
    </section>

    <!-- Section CTA -->
    <section style="background: #ffffff; padding: 6rem 0; position: relative;">
      <div class="container">
        <div class="row">
          <!-- Titre et boutons à gauche -->
          <div class="col-lg-7 col-md-12">
            <!-- Titre principal -->
            <h2 style="font-family: 'Futura Round', 'Inter', sans-serif; 
                       font-size: clamp(2.5rem, 5vw, 3.6rem); 
                       font-weight: 700; 
                       color: #1a202c; 
                       margin-bottom: 3rem; 
                       line-height: 1.1;">
              Prêt à transformer<br>
              votre établissement ?
            </h2>
            
            <!-- Boutons CTA -->
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
              <!-- Bouton orange -->
              <a href="#contact" style="background: #ff6b35; 
                                      color: white; 
                                      padding: 1.2rem 2.5rem; 
                                      font-size: 1.1rem; 
                                      font-weight: 700; 
                                      border-radius: 50px; 
                                      text-decoration: none; 
                                      display: inline-block; 
                                      transition: all 0.3s ease; 
                                      border: none;
                                      box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
                                      font-family: 'Futura Round', 'Inter', sans-serif;"
                 onmouseover="this.style.background='#e55a2b'; this.style.transform='translateY(-2px)';"
                 onmouseout="this.style.background='#ff6b35'; this.style.transform='translateY(0)';">
                Commencer maintenant
              </a>
              
              <!-- Bouton noir -->
              <a href="#demo" style="background: #2d3748; 
                                   color: white; 
                                   padding: 1.2rem 2.5rem; 
                                   font-size: 1.1rem; 
                                   font-weight: 700; 
                                   border-radius: 50px; 
                                   text-decoration: none; 
                                   display: inline-block; 
                                   transition: all 0.3s ease; 
                                   border: none;
                                   box-shadow: 0 4px 15px rgba(45, 55, 72, 0.3);
                                   font-family: 'Futura Round', 'Inter', sans-serif;"
                 onmouseover="this.style.background='#1a202c'; this.style.transform='translateY(-2px)';"
                 onmouseout="this.style.background='#2d3748'; this.style.transform='translateY(0)';">
                Demandez une démo
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <style>
      @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
      }
      
      /* Responsive */
      @media (max-width: 768px) {
        .partnership-section h2 {
          font-size: 2.5rem !important;
        }
        
        .partnership-logo {
          width: 60px !important;
          height: 60px !important;
        }
        
        .partnership-logo span {
          font-size: 2rem !important;
        }
        
        .partnership-section .col-lg-6:first-child {
          text-align: center;
          margin-bottom: 3rem;
        }
      }
    </style>


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



    <style>
      @media (max-width: 768px) {
        #modules {
          padding: 3rem 0 !important;
        }
        
        #modules > div:first-child > div:first-child {
          margin-bottom: 2rem !important;
        }
        
        #modules > div:first-child > div:last-child {
          grid-template-columns: 1fr !important;
          gap: 1.5rem !important;
          margin-top: 1rem !important;
        }
      }
    </style>

    <!-- Pricing Section -->
    <!--<section id="pricing" class="py-5" style="background:#f8fafc;">
      <div class="container">
        <div class="text-center mb-5">
          <span class="text-uppercase" style="color:#6366f1; font-weight:700; letter-spacing:1px;">TARIFS</span>
          <h2 class="fw-bold" style="font-size:2.2rem; color:#1e293b;">Choisissez la formule adaptée à votre établissement</h2>
        </div>
        <div class="row g-4 justify-content-center">
          <!-- Essentiel -->
          <!--<div class="col-lg-4 col-md-6">
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
          <!--<div class="col-lg-4 col-md-6">
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
          <!--<div class="col-lg-4 col-md-6">
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

    
    <!-- Styles pour le CTA moderne -->
    <style>
      .btn-cta-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(124, 58, 237, 0.6);
        color: white !important;
        text-decoration: none !important;
      }
      
      @media (max-width: 768px) {
        .cta-card-modern {
          padding: 2.5rem 1.5rem !important;
        }
        
        .cta-card-modern h2 {
          font-size: 2.2rem !important;
        }
        
        .btn-cta-modern {
          padding: 1rem 1.8rem !important;
          font-size: 1rem !important;
        }
      }
    </style>


    <!-- Footer Modern -->
    <footer style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 4rem 0 2rem 0; position: relative; overflow: hidden;">
        <!-- Motifs décoratifs -->
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 100%; background: url('data:image/svg+xml,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 100 100&quot;><defs><pattern id=&quot;grid&quot; width=&quot;20&quot; height=&quot;20&quot; patternUnits=&quot;userSpaceOnUse&quot;><path d=&quot;M 20 0 L 0 0 0 20&quot; fill=&quot;none&quot; stroke=&quot;rgba(255,255,255,0.05)&quot; stroke-width=&quot;1&quot;/></pattern></defs><rect width=&quot;100%&quot; height=&quot;100%&quot; fill=&quot;url(%23grid)&quot;/></svg>'); opacity: 0.5;"></div>
        
        <div class="container" style="position: relative; z-index: 2; max-width: 1200px;">
            <div class="row">
                <!-- Logo et description -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: #ffffff; font-weight: 800; font-size: 2rem; margin-bottom: 1rem;">
                            <i class="fas fa-graduation-cap" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            KLASSCI
                        </h3>
                        <p style="color: rgba(255,255,255,0.8); font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem;">
                            La solution moderne de gestion scolaire pour les établissements d'enseignement supérieur. Transformez votre établissement avec notre technologie innovante.
                        </p>
                        <!-- Réseaux sociaux -->
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="https://web.facebook.com/profile.php?id=61576039683640" target="_blank" rel="noopener" 
                               style="background: rgba(255,255,255,0.1); color: #ffffff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease; backdrop-filter: blur(10px);"
                               onmouseover="this.style.background='#3b82f6'; this.style.transform='translateY(-2px)';"
                               onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)';">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://www.linkedin.com/company/klassci/?viewAsMember=true" target="_blank" rel="noopener"
                               style="background: rgba(255,255,255,0.1); color: #ffffff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease; backdrop-filter: blur(10px);"
                               onmouseover="this.style.background='#0ea5e9'; this.style.transform='translateY(-2px)';"
                               onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)';">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" 
                               style="background: rgba(255,255,255,0.1); color: #ffffff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease; backdrop-filter: blur(10px);"
                               onmouseover="this.style.background='#1da1f2'; this.style.transform='translateY(-2px)';"
                               onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)';">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Section Produit -->
                <div class="col-lg-2 col-md-6 col-sm-6 mb-4">
                    <h5 style="color: #3b82f6; font-weight: 700; font-size: 1.1rem; margin-bottom: 1.5rem; font-family: 'Futura Round', sans-serif;">
                        Produit
                    </h5>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#features" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Fonctionnalités
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#modules" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Modules
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#pricing" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Tarifs
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#demo" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Démo
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Section Support -->
                <div class="col-lg-2 col-md-6 col-sm-6 mb-4">
                    <h5 style="color: #3b82f6; font-weight: 700; font-size: 1.1rem; margin-bottom: 1.5rem; font-family: 'Futura Round', sans-serif;">
                        Support
                    </h5>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#help" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Centre d'aide
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#docs" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Documentation
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#contact" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Contact
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#status" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Statut
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Section Entreprise -->
                <div class="col-lg-2 col-md-6 col-sm-6 mb-4">
                    <h5 style="color: #3b82f6; font-weight: 700; font-size: 1.1rem; margin-bottom: 1.5rem; font-family: 'Futura Round', sans-serif;">
                        Entreprise
                    </h5>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#about" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                À propos
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#careers" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Carrières
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#press" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Presse
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#partners" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Partenaires
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Section Légal -->
                <div class="col-lg-2 col-md-6 col-sm-6 mb-4">
                    <h5 style="color: #3b82f6; font-weight: 700; font-size: 1.1rem; margin-bottom: 1.5rem; font-family: 'Futura Round', sans-serif;">
                        Légal
                    </h5>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#privacy" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Confidentialité
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#terms" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Conditions
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#security" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Sécurité
                            </a>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="#cookies" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.95rem; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#3b82f6';" onmouseout="this.style.color='rgba(255,255,255,0.7)';">
                                Cookies
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Ligne de séparation -->
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 3rem 0 2rem 0; padding-top: 2rem;">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p style="color: rgba(255,255,255,0.6); margin: 0; font-size: 0.9rem;">
                            &copy; 2025 KLASSCI. Tous les droits sont réservés à ADC.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p style="color: rgba(255,255,255,0.6); margin: 0; font-size: 0.9rem;">
                            Développé avec ❤️ pour l'éducation africaine.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chatbox flottant -->
        <div id="chatbox" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <!-- Bouton chat -->
            <button id="chatButton" onclick="toggleChat()" 
                    style="width: 60px; height: 60px; border-radius: 50%; border: none; 
                           background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); 
                           color: white; font-size: 1.5rem; cursor: pointer; 
                           box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); 
                           transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;"
                    onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 30px rgba(59, 130, 246, 0.6)';"
                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 20px rgba(59, 130, 246, 0.4)';">
                <i class="fas fa-comments"></i>
            </button>

            <!-- Fenêtre de chat -->
            <div id="chatWindow" style="display: none; width: 320px; height: 450px; 
                                      background: white; border-radius: 12px; box-shadow: 0 10px 50px rgba(0, 0, 0, 0.2); 
                                      position: absolute; bottom: 70px; right: 0; 
                                      flex-direction: column; border: 1px solid rgba(0,0,0,0.1);">
                <!-- En-tête du chat -->
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); 
                           color: white; padding: 1rem; border-radius: 12px 12px 0 0; 
                           display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h6 style="margin: 0; font-weight: 600;">Support KLASSCI</h6>
                        <small style="opacity: 0.9;">En ligne maintenant</small>
                    </div>
                    <button onclick="toggleChat()" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Messages du chat -->
                <div style="flex: 1; padding: 1rem; overflow-y: auto; background: #f8fafc;">
                    <div style="background: #3b82f6; color: white; padding: 0.75rem 1rem; 
                               border-radius: 18px 18px 18px 4px; margin-bottom: 1rem; max-width: 80%;">
                        Bonjour ! 👋 Comment puis-je vous aider aujourd'hui ?
                    </div>
                    <div style="background: #e2e8f0; color: #334155; padding: 0.75rem 1rem; 
                               border-radius: 18px 18px 4px 18px; margin-bottom: 1rem; max-width: 80%; margin-left: auto;">
                        Je souhaite avoir une démonstration de KLASSCI
                    </div>
                    <div style="background: #3b82f6; color: white; padding: 0.75rem 1rem; 
                               border-radius: 18px 18px 18px 4px; margin-bottom: 1rem; max-width: 80%;">
                        Parfait ! Je vous contacte dans les 24h pour organiser une démonstration personnalisée. 
                        Pouvez-vous me donner votre email ?
                    </div>
                </div>

                <!-- Zone de saisie -->
                <div style="padding: 1rem; border-top: 1px solid #e2e8f0; background: white; border-radius: 0 0 12px 12px;">
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" placeholder="Tapez votre message..." 
                               style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; 
                                      border-radius: 20px; outline: none; font-size: 0.9rem;"
                               onkeypress="if(event.key==='Enter') sendMessage()">
                        <button onclick="sendMessage()" 
                                style="background: #3b82f6; color: white; border: none; 
                                       border-radius: 50%; width: 36px; height: 36px; 
                                       cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleChat() {
            const chatWindow = document.getElementById('chatWindow');
            const chatButton = document.getElementById('chatButton');
            
            if (chatWindow.style.display === 'none' || chatWindow.style.display === '') {
                chatWindow.style.display = 'flex';
                chatButton.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                chatWindow.style.display = 'none';
                chatButton.innerHTML = '<i class="fas fa-comments"></i>';
            }
        }

        function sendMessage() {
            const input = document.querySelector('#chatWindow input');
            const message = input.value.trim();
            if (message) {
                // Ici vous pouvez ajouter la logique pour envoyer le message
                console.log('Message envoyé:', message);
                input.value = '';
                
                // Simulation d'une réponse automatique
                setTimeout(() => {
                    alert('Merci pour votre message ! Notre équipe vous contactera bientôt.');
                }, 1000);
            }
        }

        // Fermer le chat en cliquant à l'extérieur
        document.addEventListener('click', function(event) {
            const chatbox = document.getElementById('chatbox');
            const chatWindow = document.getElementById('chatWindow');
            
            if (!chatbox.contains(event.target) && chatWindow.style.display === 'flex') {
                chatWindow.style.display = 'none';
                document.getElementById('chatButton').innerHTML = '<i class="fas fa-comments"></i>';
            }
        });
    </script>
    <style>
    @keyframes pulseBtn {
      0% { box-shadow: 0 0 0 0 rgba(99,102,241,0.15); transform: scale(1); }
      100% { box-shadow: 0 0 16px 8px rgba(99,102,241,0.10); transform: scale(1.04); }
    }
    .premium-cta-btn {
      background: linear-gradient(90deg,#6366f1,#7c3aed) !important;
      color: #fff !important;
      border: none !important;
      font-weight: 800 !important;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 24px 0 rgba(99,102,241,0.13) !important;
      transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
    }
    .premium-cta-btn:hover {
      background: linear-gradient(90deg,#7c3aed,#6366f1) !important;
      transform: scale(1.06);
      box-shadow: 0 8px 32px 0 rgba(99,102,241,0.18) !important;
    }
    </style>

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


        // Advanced scroll effects
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            // Navbar scroll effect avec ombre progressive
            if (scrolled > 50) {
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
            } else {
                navbar.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.05)';
                navbar.style.backgroundColor = 'white';
                navbar.style.backdropFilter = 'none';
            }
            
            // Parallax effect pour la section dashboard
            const dashboardSection = document.querySelector('.dashboard-preview-section');
            if (dashboardSection) {
                const rect = dashboardSection.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    const speed = (window.innerHeight - rect.top) * 0.1;
                    dashboardSection.style.transform = `translateY(${speed}px)`;
                }
            }
        });
        
        // Intersection Observer pour les animations au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                } else {
                    entry.target.classList.remove('animate-in');
                }
            });
        }, observerOptions);
        
        // Observer les éléments à animer
        document.addEventListener('DOMContentLoaded', function() {
            const elementsToAnimate = document.querySelectorAll('.hero-content, .dashboard-content-side, .dashboard-image-side');
            elementsToAnimate.forEach(el => {
                observer.observe(el);
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            });
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
    <style>
      /* ... styles précédents ... */
      .form-floating-premium.has-value label,
      .premium-input:focus + label,
      .premium-input:not(:placeholder-shown) + label,
      select.premium-input:focus + label,
      select.premium-input:not([value=""]) + label,
      textarea.premium-input:focus + label,
      textarea.premium-input:not(:placeholder-shown) + label {
        top: 0.18rem;
        left: 1.2rem;
        font-size: 0.97rem !important;
        color: #6366f1 !important;
        opacity: 0.85;
        font-style: italic !important;
        background: rgba(255,255,255,0.97);
        padding: 0 0.3rem;
        border-radius: 0.7rem;
        font-weight: 500;
      }
    </style>
    <!-- Ajout du script JS pour floating label universel -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        function updateFloatingLabels() {
          document.querySelectorAll('.premium-input').forEach(function(input) {
            var parent = input.closest('.form-floating-premium');
            if (!parent) return;
            if (input.value && input.value.trim() !== '') {
              parent.classList.add('has-value');
            } else {
              parent.classList.remove('has-value');
            }
          });
        }
        document.querySelectorAll('.premium-input').forEach(function(input) {
          input.addEventListener('input', updateFloatingLabels);
          input.addEventListener('change', updateFloatingLabels);
          input.addEventListener('blur', updateFloatingLabels);
        });
        updateFloatingLabels();
      });
    </script>
</body>
</html>