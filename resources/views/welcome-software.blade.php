<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KLASSCI - Solution de Gestion Intelligente</title>

        <!-- Polices Google -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- AOS - Animation On Scroll -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

        <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6366f1;
            --accent: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;

            /* Espacement */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 5rem;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;

            /* Nouveaux styles pour la refonte de l'interface */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            --text-gradient: linear-gradient(to right, #6366f1, #ec4899);
            --primary-soft: rgba(99, 102, 241, 0.1);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --info-soft: rgba(14, 165, 233, 0.1);
        }

        /* Base */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .section {
            padding: var(--spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .subtitle {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: var(--spacing-xs);
            display: inline-block;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 2.75rem;
            margin-bottom: var(--spacing-md);
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 70px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .text-center .section-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: var(--spacing-lg);
            max-width: 800px;
            line-height: 1.7;
        }

        /* Boutons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
            z-index: -1;
            border-radius: var(--border-radius-full);
        }

        .btn:hover::after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background-color: #5558e0;
            border-color: #5558e0;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
        }

        /* Navigation */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .navbar-brand img { height: 75px;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-scrolled .navbar-brand img { height: 65px;
        }

        .navbar-scrolled .nav-link {
            color: var(--dark);
        }

        .navbar-scrolled .nav-link:hover,
        .navbar-scrolled .nav-link.active {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 40px;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-cta {
            background-color: var(--secondary);
            color: white !important;
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            border: none;
        }

        .nav-cta:hover {
            background-color: #5558e0;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(99, 102, 241, 0.1);
        }

        .nav-cta::after {
            display: none;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #8a4baf 0%, #6366f1 100%);
            color: white;
            padding: 140px 0 120px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.75rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-description {
            font-size: 1.35rem;
            line-height: 1.8;
            opacity: 0.95;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
        }

        .text-gradient {
            background: linear-gradient(to right, #f9a8d4, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 900;
        }

        .hero-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }
        
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #f9a8d4;
            display: inline-block;
        }

        .badge-text {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }

        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Animations pour la section hero */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .hero-gradient {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 15% 15%, rgba(236, 72, 153, 0.5) 0%, transparent 25%),
                             radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.5) 0%, transparent 25%);
            opacity: 0.5;
            z-index: -1;
        }

        .hero-particles {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .hero-image-container {
            padding: 30px;
        }

        .hero-image-wrapper {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 3;
        }

        .hero-image {
            transition: all 0.5s ease;
            transform: scale(1);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.02);
        }

        .hero-image-shape-1,
        .hero-image-shape-2 {
            width: 200px;
            height: 200px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            z-index: 1;
        }

        .hero-image-shape-1 {
            top: -50px;
            right: -50px;
            background: var(--secondary-gradient);
            opacity: 0.3;
            animation: morphShape 15s linear infinite alternate;
        }

        .hero-image-shape-2 {
            bottom: -50px;
            left: -50px;
            background: var(--primary-gradient);
            opacity: 0.3;
            animation: morphShape 20s linear infinite alternate-reverse;
        }

        .hero-image-element-1 {
            top: 25%;
            right: -15px;
            transform: translateY(-50%);
            z-index: 4;
            animation: floatElement 5s ease-in-out infinite;
        }

        .hero-image-element-2 {
            bottom: 20%;
            left: -15px;
            transform: translateY(50%);
            z-index: 4;
            animation: floatElement 7s ease-in-out infinite;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Clients Section */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }

        .client-logo {
            transition: all 0.3s ease;
        }

        .client-logo:hover {
            opacity: 1 !important;
            transform: scale(1.05);
        }

        .opacity-60 {
            opacity: 0.6;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
        }

        .bg-info-soft {
            background-color: var(--info-soft);
        }

        .z-index-3 {
            z-index: 3;
        }

        .shadow-hover {
            transition: all 0.3s ease;
        }

        .shadow-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-4 {
            border-radius: 16px;
        }

        /* Animations */
        @keyframes floatElement {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        @keyframes morphShape {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }

        /* Responsive adjustments for new sections */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.25rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-stat {
                padding: 0 1rem;
                text-align: center;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            top: -150px;
            left: -150px;
            z-index: 0;
        }

        .features::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.08) 100%);
            bottom: -100px;
            right: -100px;
            z-index: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: inline-block;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .section-title p {
            font-size: 1.15rem;
            color: var(--gray);
            max-width: 800px;
            margin: 1.5rem auto 0;
            line-height: 1.7;
        }

        .feature-card {
            padding: 2.5rem;
            border-radius: 16px;
            background-color: white;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.06) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            font-size: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
            transform: scale(1.15);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .feature-card:hover h3 {
            color: var(--primary);
        }

        .feature-card p {
            color: var(--gray);
            margin-bottom: 0;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%236366f1' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .testimonial-card {
            background-color: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .testimonial-card::before {
            content: '\201C';
            font-family: Georgia, serif;
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 5rem;
            color: rgba(99, 102, 241, 0.1);
            line-height: 1;
        }

        .testimonial-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .testimonial-rating {
            color: #f59e0b;
            font-size: 1.15rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: 5px;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: auto;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 1.5rem;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1.25rem;
            object-fit: cover;
            border: 3px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover .testimonial-avatar {
            border-color: rgba(99, 102, 241, 0.3);
        }

        .testimonial-author h5 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .testimonial-author p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        @media (max-width: 767.98px) {
            .testimonial-card {
                margin-bottom: 2rem;
            }
        }

        /* CTA Section */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Contact Section */
        .contact {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            border-radius: 50%;
            z-index: 0;
            opacity: 0.5;
        }

        .contact-info {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            display: flex;
            background-color: #fff;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .contact-card:hover .contact-icon {
            background-color: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .contact-text {
            flex: 1;
        }

        .contact-text h5 {
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .contact-text p {
            margin-bottom: 0.25rem;
            color: var(--gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .contact-form-wrapper {
            background-color: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
        }

        .contact-form-wrapper:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .contact-form .form-control,
        .contact-form .form-select {
            border: 1px solid #e9ecef;
            padding: 0.85rem 1.25rem;
            transition: all 0.3s ease;
            background-color: #fafbfc;
            border-radius: 8px;
            color: var(--dark);
            font-size: 1rem;
        }

        .contact-form .form-control:focus,
        .contact-form .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
            background-color: #ffffff;
        }

        .contact-form .form-floating label {
            padding: 0.85rem 1.25rem;
            color: #64748b;
        }

        .contact-form .form-check-label {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .contact-form .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .contact-form .btn-primary {
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-size: 1.05rem;
        }

        @media (max-width: 991.98px) {
            .contact-info {
                margin-bottom: 2.5rem;
            }
        }

        /* Footer */
        .footer {
            padding: 80px 0 40px;
            background-color: var(--dark);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-logo {
            margin-bottom: 1.5rem;
        }

        .footer-logo img {
        /* Styles d'animation ajoutés */
        .footer-logo img:hover {
            transform: scale(1.05);
        } height: 65px; transition: transform 0.3s ease;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .footer h5 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            display: inline-block;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
            border-radius: 10px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.85rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 0;
            display: inline-block;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: none;
            padding-left: 10px;
        }

        .footer-links a::before {
            content: "\f105";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .footer-links a:hover::before {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .social-links a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: var(--secondary);
            z-index: -1;
            transform: scale(0);
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .social-links a:hover {
            background-color: transparent;
            transform: translateY(-5px);
            color: white;
        }

        .social-links a:hover::after {
            transform: scale(1);
        }

        .copyright {
            padding-top: 2rem;
            margin-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        /* Media Queries */
        @media (max-width: 1199.98px) {
            .hero h1 {
                font-size: 3.25rem;
            }
            
            .section-title h2 {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 991.98px) {
            .navbar-brand img {
                height: 50px;
            }
            
            .hero {
                padding: 150px 0 100px;
            }
            
            .hero h1 {
                font-size: 2.75rem;
            }

            .hero-img {
                margin-top: 3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .contact-form-wrapper {
                padding: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                padding: 1rem 0;
            }
            
            .hero {
                padding: 120px 0 80px;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero p {
                font-size: 1.1rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero .btn {
                padding: 0.75rem 1.5rem;
            }
            
            .hero .d-flex {
                justify-content: center;
            }

            .section {
                padding: 80px 0;
            }

            .section-title h2 {
                font-size: 1.85rem;
            }
            
            .section-title p {
                font-size: 1rem;
            }
            
            .feature-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .feature-icon {
                margin-left: auto;
                margin-right: auto;
            }
            
            .cta {
                padding: 80px 0;
            }

            .cta h2 {
                font-size: 1.85rem;
            }
            
            .cta p {
                font-size: 1rem;
            }

            .footer {
                text-align: center;
                padding: 60px 0 30px;
            }
            
            .footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
            
            .footer-links a::before {
                display: none;
            }
            
            .footer-links a:hover {
                padding-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.75rem;
            }
            
            .btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.95rem;
            }
            
            .feature-card {
                padding: 1.75rem;
            }
            
            .testimonial-card {
                padding: 1.75rem;
            }
            
            .contact-form-wrapper {
                padding: 1.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KLASSCI - Solution de Gestion Intelligente</title>

        <!-- Polices Google -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- AOS - Animation On Scroll -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

        <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6366f1;
            --accent: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;

            /* Espacement */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 5rem;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;

            /* Nouveaux styles pour la refonte de l'interface */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            --text-gradient: linear-gradient(to right, #6366f1, #ec4899);
            --primary-soft: rgba(99, 102, 241, 0.1);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --info-soft: rgba(14, 165, 233, 0.1);
        }

        /* Base */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .section {
            padding: var(--spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .subtitle {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: var(--spacing-xs);
            display: inline-block;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 2.75rem;
            margin-bottom: var(--spacing-md);
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 70px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .text-center .section-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: var(--spacing-lg);
            max-width: 800px;
            line-height: 1.7;
        }

        /* Boutons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
            z-index: -1;
            border-radius: var(--border-radius-full);
        }

        .btn:hover::after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background-color: #5558e0;
            border-color: #5558e0;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
        }

        /* Navigation */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .navbar-brand img { height: 75px;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-scrolled .navbar-brand img { height: 65px;
        }

        .navbar-scrolled .nav-link {
            color: var(--dark);
        }

        .navbar-scrolled .nav-link:hover,
        .navbar-scrolled .nav-link.active {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 40px;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-cta {
            background-color: var(--secondary);
            color: white !important;
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            border: none;
        }

        .nav-cta:hover {
            background-color: #5558e0;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(99, 102, 241, 0.1);
        }

        .nav-cta::after {
            display: none;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #8a4baf 0%, #6366f1 100%);
            color: white;
            padding: 140px 0 120px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.75rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-description {
            font-size: 1.35rem;
            line-height: 1.8;
            opacity: 0.95;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
        }

        .text-gradient {
            background: linear-gradient(to right, #f9a8d4, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 900;
        }

        .hero-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }
        
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #f9a8d4;
            display: inline-block;
        }

        .badge-text {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }

        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Animations pour la section hero */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .hero-gradient {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 15% 15%, rgba(236, 72, 153, 0.5) 0%, transparent 25%),
                             radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.5) 0%, transparent 25%);
            opacity: 0.5;
            z-index: -1;
        }

        .hero-particles {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .hero-image-container {
            padding: 30px;
        }

        .hero-image-wrapper {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 3;
        }

        .hero-image {
            transition: all 0.5s ease;
            transform: scale(1);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.02);
        }

        .hero-image-shape-1,
        .hero-image-shape-2 {
            width: 200px;
            height: 200px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            z-index: 1;
        }

        .hero-image-shape-1 {
            top: -50px;
            right: -50px;
            background: var(--secondary-gradient);
            opacity: 0.3;
            animation: morphShape 15s linear infinite alternate;
        }

        .hero-image-shape-2 {
            bottom: -50px;
            left: -50px;
            background: var(--primary-gradient);
            opacity: 0.3;
            animation: morphShape 20s linear infinite alternate-reverse;
        }

        .hero-image-element-1 {
            top: 25%;
            right: -15px;
            transform: translateY(-50%);
            z-index: 4;
            animation: floatElement 5s ease-in-out infinite;
        }

        .hero-image-element-2 {
            bottom: 20%;
            left: -15px;
            transform: translateY(50%);
            z-index: 4;
            animation: floatElement 7s ease-in-out infinite;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Clients Section */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }

        .client-logo {
            transition: all 0.3s ease;
        }

        .client-logo:hover {
            opacity: 1 !important;
            transform: scale(1.05);
        }

        .opacity-60 {
            opacity: 0.6;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
        }

        .bg-info-soft {
            background-color: var(--info-soft);
        }

        .z-index-3 {
            z-index: 3;
        }

        .shadow-hover {
            transition: all 0.3s ease;
        }

        .shadow-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-4 {
            border-radius: 16px;
        }

        /* Animations */
        @keyframes floatElement {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        @keyframes morphShape {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }

        /* Responsive adjustments for new sections */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.25rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-stat {
                padding: 0 1rem;
                text-align: center;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            top: -150px;
            left: -150px;
            z-index: 0;
        }

        .features::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.08) 100%);
            bottom: -100px;
            right: -100px;
            z-index: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: inline-block;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .section-title p {
            font-size: 1.15rem;
            color: var(--gray);
            max-width: 800px;
            margin: 1.5rem auto 0;
            line-height: 1.7;
        }

        .feature-card {
            padding: 2.5rem;
            border-radius: 16px;
            background-color: white;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.06) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            font-size: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
            transform: scale(1.15);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .feature-card:hover h3 {
            color: var(--primary);
        }

        .feature-card p {
            color: var(--gray);
            margin-bottom: 0;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%236366f1' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .testimonial-card {
            background-color: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .testimonial-card::before {
            content: '\201C';
            font-family: Georgia, serif;
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 5rem;
            color: rgba(99, 102, 241, 0.1);
            line-height: 1;
        }

        .testimonial-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .testimonial-rating {
            color: #f59e0b;
            font-size: 1.15rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: 5px;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: auto;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 1.5rem;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1.25rem;
            object-fit: cover;
            border: 3px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover .testimonial-avatar {
            border-color: rgba(99, 102, 241, 0.3);
        }

        .testimonial-author h5 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .testimonial-author p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        @media (max-width: 767.98px) {
            .testimonial-card {
                margin-bottom: 2rem;
            }
        }

        /* CTA Section */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Contact Section */
        .contact {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            border-radius: 50%;
            z-index: 0;
            opacity: 0.5;
        }

        .contact-info {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            display: flex;
            background-color: #fff;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .contact-card:hover .contact-icon {
            background-color: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .contact-text {
            flex: 1;
        }

        .contact-text h5 {
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .contact-text p {
            margin-bottom: 0.25rem;
            color: var(--gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .contact-form-wrapper {
            background-color: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
        }

        .contact-form-wrapper:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .contact-form .form-control,
        .contact-form .form-select {
            border: 1px solid #e9ecef;
            padding: 0.85rem 1.25rem;
            transition: all 0.3s ease;
            background-color: #fafbfc;
            border-radius: 8px;
            color: var(--dark);
            font-size: 1rem;
        }

        .contact-form .form-control:focus,
        .contact-form .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
            background-color: #ffffff;
        }

        .contact-form .form-floating label {
            padding: 0.85rem 1.25rem;
            color: #64748b;
        }

        .contact-form .form-check-label {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .contact-form .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .contact-form .btn-primary {
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-size: 1.05rem;
        }

        @media (max-width: 991.98px) {
            .contact-info {
                margin-bottom: 2.5rem;
            }
        }

        /* Footer */
        .footer {
            padding: 80px 0 40px;
            background-color: var(--dark);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-logo {
            margin-bottom: 1.5rem;
        }

        .footer-logo img {
        /* Styles d'animation ajoutés */
        .footer-logo img:hover {
            transform: scale(1.05);
        } height: 65px; transition: transform 0.3s ease;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .footer h5 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            display: inline-block;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
            border-radius: 10px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.85rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 0;
            display: inline-block;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: none;
            padding-left: 10px;
        }

        .footer-links a::before {
            content: "\f105";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .footer-links a:hover::before {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .social-links a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: var(--secondary);
            z-index: -1;
            transform: scale(0);
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .social-links a:hover {
            background-color: transparent;
            transform: translateY(-5px);
            color: white;
        }

        .social-links a:hover::after {
            transform: scale(1);
        }

        .copyright {
            padding-top: 2rem;
            margin-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        /* Media Queries */
        @media (max-width: 1199.98px) {
            .hero h1 {
                font-size: 3.25rem;
            }
            
            .section-title h2 {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 991.98px) {
            .navbar-brand img {
                height: 50px;
            }
            
            .hero {
                padding: 150px 0 100px;
            }
            
            .hero h1 {
                font-size: 2.75rem;
            }

            .hero-img {
                margin-top: 3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .contact-form-wrapper {
                padding: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                padding: 1rem 0;
            }
            
            .hero {
                padding: 120px 0 80px;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero p {
                font-size: 1.1rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero .btn {
                padding: 0.75rem 1.5rem;
            }
            
            .hero .d-flex {
                justify-content: center;
            }

            .section {
                padding: 80px 0;
            }

            .section-title h2 {
                font-size: 1.85rem;
            }
            
            .section-title p {
                font-size: 1rem;
            }
            
            .feature-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .feature-icon {
                margin-left: auto;
                margin-right: auto;
            }
            
            .cta {
                padding: 80px 0;
            }

            .cta h2 {
                font-size: 1.85rem;
            }
            
            .cta p {
                font-size: 1rem;
            }

            .footer {
                text-align: center;
                padding: 60px 0 30px;
            }
            
            .footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
            
            .footer-links a::before {
                display: none;
            }
            
            .footer-links a:hover {
                padding-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.75rem;
            }
            
            .btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.95rem;
            }
            
            .feature-card {
                padding: 1.75rem;
            }
            
            .testimonial-card {
                padding: 1.75rem;
            }
            
            .contact-form-wrapper {
                padding: 1.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KLASSCI - Solution de Gestion Intelligente</title>

        <!-- Polices Google -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- AOS - Animation On Scroll -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

        <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6366f1;
            --accent: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;

            /* Espacement */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 5rem;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;

            /* Nouveaux styles pour la refonte de l'interface */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            --text-gradient: linear-gradient(to right, #6366f1, #ec4899);
            --primary-soft: rgba(99, 102, 241, 0.1);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --info-soft: rgba(14, 165, 233, 0.1);
        }

        /* Base */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .section {
            padding: var(--spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .subtitle {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: var(--spacing-xs);
            display: inline-block;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 2.75rem;
            margin-bottom: var(--spacing-md);
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 70px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .text-center .section-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: var(--spacing-lg);
            max-width: 800px;
            line-height: 1.7;
        }

        /* Boutons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
            z-index: -1;
            border-radius: var(--border-radius-full);
        }

        .btn:hover::after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background-color: #5558e0;
            border-color: #5558e0;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
        }

        /* Navigation */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .navbar-brand img { height: 75px;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-scrolled .navbar-brand img { height: 65px;
        }

        .navbar-scrolled .nav-link {
            color: var(--dark);
        }

        .navbar-scrolled .nav-link:hover,
        .navbar-scrolled .nav-link.active {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 40px;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-cta {
            background-color: var(--secondary);
            color: white !important;
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            border: none;
        }

        .nav-cta:hover {
            background-color: #5558e0;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(99, 102, 241, 0.1);
        }

        .nav-cta::after {
            display: none;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #8a4baf 0%, #6366f1 100%);
            color: white;
            padding: 140px 0 120px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.75rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-description {
            font-size: 1.35rem;
            line-height: 1.8;
            opacity: 0.95;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
        }

        .text-gradient {
            background: linear-gradient(to right, #f9a8d4, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 900;
        }

        .hero-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }
        
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #f9a8d4;
            display: inline-block;
        }

        .badge-text {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }

        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Animations pour la section hero */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .hero-gradient {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 15% 15%, rgba(236, 72, 153, 0.5) 0%, transparent 25%),
                             radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.5) 0%, transparent 25%);
            opacity: 0.5;
            z-index: -1;
        }

        .hero-particles {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .hero-image-container {
            padding: 30px;
        }

        .hero-image-wrapper {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 3;
        }

        .hero-image {
            transition: all 0.5s ease;
            transform: scale(1);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.02);
        }

        .hero-image-shape-1,
        .hero-image-shape-2 {
            width: 200px;
            height: 200px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            z-index: 1;
        }

        .hero-image-shape-1 {
            top: -50px;
            right: -50px;
            background: var(--secondary-gradient);
            opacity: 0.3;
            animation: morphShape 15s linear infinite alternate;
        }

        .hero-image-shape-2 {
            bottom: -50px;
            left: -50px;
            background: var(--primary-gradient);
            opacity: 0.3;
            animation: morphShape 20s linear infinite alternate-reverse;
        }

        .hero-image-element-1 {
            top: 25%;
            right: -15px;
            transform: translateY(-50%);
            z-index: 4;
            animation: floatElement 5s ease-in-out infinite;
        }

        .hero-image-element-2 {
            bottom: 20%;
            left: -15px;
            transform: translateY(50%);
            z-index: 4;
            animation: floatElement 7s ease-in-out infinite;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Clients Section */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }

        .client-logo {
            transition: all 0.3s ease;
        }

        .client-logo:hover {
            opacity: 1 !important;
            transform: scale(1.05);
        }

        .opacity-60 {
            opacity: 0.6;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
        }

        .bg-info-soft {
            background-color: var(--info-soft);
        }

        .z-index-3 {
            z-index: 3;
        }

        .shadow-hover {
            transition: all 0.3s ease;
        }

        .shadow-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-4 {
            border-radius: 16px;
        }

        /* Animations */
        @keyframes floatElement {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        @keyframes morphShape {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }

        /* Responsive adjustments for new sections */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.25rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-stat {
                padding: 0 1rem;
                text-align: center;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            top: -150px;
            left: -150px;
            z-index: 0;
        }

        .features::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.08) 100%);
            bottom: -100px;
            right: -100px;
            z-index: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: inline-block;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .section-title p {
            font-size: 1.15rem;
            color: var(--gray);
            max-width: 800px;
            margin: 1.5rem auto 0;
            line-height: 1.7;
        }

        .feature-card {
            padding: 2.5rem;
            border-radius: 16px;
            background-color: white;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.06) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            font-size: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
            transform: scale(1.15);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .feature-card:hover h3 {
            color: var(--primary);
        }

        .feature-card p {
            color: var(--gray);
            margin-bottom: 0;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KLASSCI - Solution de Gestion Intelligente</title>

        <!-- Polices Google -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- AOS - Animation On Scroll -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

        <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6366f1;
            --accent: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;

            /* Espacement */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 5rem;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;

            /* Nouveaux styles pour la refonte de l'interface */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            --text-gradient: linear-gradient(to right, #6366f1, #ec4899);
            --primary-soft: rgba(99, 102, 241, 0.1);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --info-soft: rgba(14, 165, 233, 0.1);
        }

        /* Base */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .section {
            padding: var(--spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .subtitle {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: var(--spacing-xs);
            display: inline-block;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 2.75rem;
            margin-bottom: var(--spacing-md);
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 70px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .text-center .section-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: var(--spacing-lg);
            max-width: 800px;
            line-height: 1.7;
        }

        /* Boutons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
            z-index: -1;
            border-radius: var(--border-radius-full);
        }

        .btn:hover::after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background-color: #5558e0;
            border-color: #5558e0;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
        }

        /* Navigation */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .navbar-brand img { height: 75px;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-scrolled .navbar-brand img { height: 65px;
        }

        .navbar-scrolled .nav-link {
            color: var(--dark);
        }

        .navbar-scrolled .nav-link:hover,
        .navbar-scrolled .nav-link.active {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 40px;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-cta {
            background-color: var(--secondary);
            color: white !important;
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            border: none;
        }

        .nav-cta:hover {
            background-color: #5558e0;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(99, 102, 241, 0.1);
        }

        .nav-cta::after {
            display: none;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #8a4baf 0%, #6366f1 100%);
            color: white;
            padding: 140px 0 120px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.75rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-description {
            font-size: 1.35rem;
            line-height: 1.8;
            opacity: 0.95;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
        }

        .text-gradient {
            background: linear-gradient(to right, #f9a8d4, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 900;
        }

        .hero-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }
        
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #f9a8d4;
            display: inline-block;
        }

        .badge-text {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }

        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Animations pour la section hero */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .hero-gradient {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 15% 15%, rgba(236, 72, 153, 0.5) 0%, transparent 25%),
                             radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.5) 0%, transparent 25%);
            opacity: 0.5;
            z-index: -1;
        }

        .hero-particles {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .hero-image-container {
            padding: 30px;
        }

        .hero-image-wrapper {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 3;
        }

        .hero-image {
            transition: all 0.5s ease;
            transform: scale(1);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.02);
        }

        .hero-image-shape-1,
        .hero-image-shape-2 {
            width: 200px;
            height: 200px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            z-index: 1;
        }

        .hero-image-shape-1 {
            top: -50px;
            right: -50px;
            background: var(--secondary-gradient);
            opacity: 0.3;
            animation: morphShape 15s linear infinite alternate;
        }

        .hero-image-shape-2 {
            bottom: -50px;
            left: -50px;
            background: var(--primary-gradient);
            opacity: 0.3;
            animation: morphShape 20s linear infinite alternate-reverse;
        }

        .hero-image-element-1 {
            top: 25%;
            right: -15px;
            transform: translateY(-50%);
            z-index: 4;
            animation: floatElement 5s ease-in-out infinite;
        }

        .hero-image-element-2 {
            bottom: 20%;
            left: -15px;
            transform: translateY(50%);
            z-index: 4;
            animation: floatElement 7s ease-in-out infinite;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Clients Section */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }

        .client-logo {
            transition: all 0.3s ease;
        }

        .client-logo:hover {
            opacity: 1 !important;
            transform: scale(1.05);
        }

        .opacity-60 {
            opacity: 0.6;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
        }

        .bg-info-soft {
            background-color: var(--info-soft);
        }

        .z-index-3 {
            z-index: 3;
        }

        .shadow-hover {
            transition: all 0.3s ease;
        }

        .shadow-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-4 {
            border-radius: 16px;
        }

        /* Animations */
        @keyframes floatElement {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        @keyframes morphShape {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }

        /* Responsive adjustments for new sections */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.25rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-stat {
                padding: 0 1rem;
                text-align: center;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            top: -150px;
            left: -150px;
            z-index: 0;
        }

        .features::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.08) 100%);
            bottom: -100px;
            right: -100px;
            z-index: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: inline-block;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .section-title p {
            font-size: 1.15rem;
            color: var(--gray);
            max-width: 800px;
            margin: 1.5rem auto 0;
            line-height: 1.7;
        }

        .feature-card {
            padding: 2.5rem;
            border-radius: 16px;
            background-color: white;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.06) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            font-size: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
            transform: scale(1.15);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .feature-card:hover h3 {
            color: var(--primary);
        }

        .feature-card p {
            color: var(--gray);
            margin-bottom: 0;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%236366f1' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .testimonial-card {
            background-color: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .testimonial-card::before {
            content: '\201C';
            font-family: Georgia, serif;
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 5rem;
            color: rgba(99, 102, 241, 0.1);
            line-height: 1;
        }

        .testimonial-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .testimonial-rating {
            color: #f59e0b;
            font-size: 1.15rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: 5px;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: auto;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 1.5rem;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1.25rem;
            object-fit: cover;
            border: 3px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover .testimonial-avatar {
            border-color: rgba(99, 102, 241, 0.3);
        }

        .testimonial-author h5 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .testimonial-author p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        @media (max-width: 767.98px) {
            .testimonial-card {
                margin-bottom: 2rem;
            }
        }

        /* CTA Section */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Contact Section */
        .contact {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            border-radius: 50%;
            z-index: 0;
            opacity: 0.5;
        }

        .contact-info {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            display: flex;
            background-color: #fff;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .contact-card:hover .contact-icon {
            background-color: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .contact-text {
            flex: 1;
        }

        .contact-text h5 {
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .contact-text p {
            margin-bottom: 0.25rem;
            color: var(--gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .contact-form-wrapper {
            background-color: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
        }

        .contact-form-wrapper:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .contact-form .form-control,
        .contact-form .form-select {
            border: 1px solid #e9ecef;
            padding: 0.85rem 1.25rem;
            transition: all 0.3s ease;
            background-color: #fafbfc;
            border-radius: 8px;
            color: var(--dark);
            font-size: 1rem;
        }

        .contact-form .form-control:focus,
        .contact-form .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
            background-color: #ffffff;
        }

        .contact-form .form-floating label {
            padding: 0.85rem 1.25rem;
            color: #64748b;
        }

        .contact-form .form-check-label {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .contact-form .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .contact-form .btn-primary {
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-size: 1.05rem;
        }

        @media (max-width: 991.98px) {
            .contact-info {
                margin-bottom: 2.5rem;
            }
        }

        /* Footer */
        .footer {
            padding: 80px 0 40px;
            background-color: var(--dark);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-logo {
            margin-bottom: 1.5rem;
        }

        .footer-logo img {
        /* Styles d'animation ajoutés */
        .footer-logo img:hover {
            transform: scale(1.05);
        } height: 65px; transition: transform 0.3s ease;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .footer h5 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            display: inline-block;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
            border-radius: 10px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.85rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 0;
            display: inline-block;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: none;
            padding-left: 10px;
        }

        .footer-links a::before {
            content: "\f105";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .footer-links a:hover::before {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .social-links a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: var(--secondary);
            z-index: -1;
            transform: scale(0);
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .social-links a:hover {
            background-color: transparent;
            transform: translateY(-5px);
            color: white;
        }

        .social-links a:hover::after {
            transform: scale(1);
        }

        .copyright {
            padding-top: 2rem;
            margin-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        /* Media Queries */
        @media (max-width: 1199.98px) {
            .hero h1 {
                font-size: 3.25rem;
            }
            
            .section-title h2 {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 991.98px) {
            .navbar-brand img {
                height: 50px;
            }
            
            .hero {
                padding: 150px 0 100px;
            }
            
            .hero h1 {
                font-size: 2.75rem;
            }

            .hero-img {
                margin-top: 3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .contact-form-wrapper {
                padding: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                padding: 1rem 0;
            }
            
            .hero {
                padding: 120px 0 80px;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero p {
                font-size: 1.1rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero .btn {
                padding: 0.75rem 1.5rem;
            }
            
            .hero .d-flex {
                justify-content: center;
            }

            .section {
                padding: 80px 0;
            }

            .section-title h2 {
                font-size: 1.85rem;
            }
            
            .section-title p {
                font-size: 1rem;
            }
            
            .feature-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .feature-icon {
                margin-left: auto;
                margin-right: auto;
            }
            
            .cta {
                padding: 80px 0;
            }

            .cta h2 {
                font-size: 1.85rem;
            }
            
            .cta p {
                font-size: 1rem;
            }

            .footer {
                text-align: center;
                padding: 60px 0 30px;
            }
            
            .footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
            
            .footer-links a::before {
                display: none;
            }
            
            .footer-links a:hover {
                padding-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.75rem;
            }
            
            .btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.95rem;
            }
            
            .feature-card {
                padding: 1.75rem;
            }
            
            .testimonial-card {
                padding: 1.75rem;
            }
            
            .contact-form-wrapper {
                padding: 1.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KLASSCI - Solution de Gestion Intelligente</title>

        <!-- Polices Google -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- AOS - Animation On Scroll -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

        <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6366f1;
            --accent: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;

            /* Espacement */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 5rem;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;

            /* Nouveaux styles pour la refonte de l'interface */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            --text-gradient: linear-gradient(to right, #6366f1, #ec4899);
            --primary-soft: rgba(99, 102, 241, 0.1);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --info-soft: rgba(14, 165, 233, 0.1);
        }

        /* Base */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .section {
            padding: var(--spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .subtitle {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: var(--spacing-xs);
            display: inline-block;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 2.75rem;
            margin-bottom: var(--spacing-md);
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 70px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .text-center .section-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: var(--spacing-lg);
            max-width: 800px;
            line-height: 1.7;
        }

        /* Boutons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
            z-index: -1;
            border-radius: var(--border-radius-full);
        }

        .btn:hover::after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background-color: #5558e0;
            border-color: #5558e0;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
        }

        /* Navigation */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .navbar-brand img { height: 75px;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-scrolled .navbar-brand img { height: 65px;
        }

        .navbar-scrolled .nav-link {
            color: var(--dark);
        }

        .navbar-scrolled .nav-link:hover,
        .navbar-scrolled .nav-link.active {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 40px;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-cta {
            background-color: var(--secondary);
            color: white !important;
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            border: none;
        }

        .nav-cta:hover {
            background-color: #5558e0;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(99, 102, 241, 0.1);
        }

        .nav-cta::after {
            display: none;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #8a4baf 0%, #6366f1 100%);
            color: white;
            padding: 140px 0 120px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.75rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-description {
            font-size: 1.35rem;
            line-height: 1.8;
            opacity: 0.95;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
        }

        .text-gradient {
            background: linear-gradient(to right, #f9a8d4, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 900;
        }

        .hero-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }
        
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #f9a8d4;
            display: inline-block;
        }

        .badge-text {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }

        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Animations pour la section hero */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .hero-gradient {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 15% 15%, rgba(236, 72, 153, 0.5) 0%, transparent 25%),
                             radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.5) 0%, transparent 25%);
            opacity: 0.5;
            z-index: -1;
        }

        .hero-particles {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .hero-image-container {
            padding: 30px;
        }

        .hero-image-wrapper {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 3;
        }

        .hero-image {
            transition: all 0.5s ease;
            transform: scale(1);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.02);
        }

        .hero-image-shape-1,
        .hero-image-shape-2 {
            width: 200px;
            height: 200px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            z-index: 1;
        }

        .hero-image-shape-1 {
            top: -50px;
            right: -50px;
            background: var(--secondary-gradient);
            opacity: 0.3;
            animation: morphShape 15s linear infinite alternate;
        }

        .hero-image-shape-2 {
            bottom: -50px;
            left: -50px;
            background: var(--primary-gradient);
            opacity: 0.3;
            animation: morphShape 20s linear infinite alternate-reverse;
        }

        .hero-image-element-1 {
            top: 25%;
            right: -15px;
            transform: translateY(-50%);
            z-index: 4;
            animation: floatElement 5s ease-in-out infinite;
        }

        .hero-image-element-2 {
            bottom: 20%;
            left: -15px;
            transform: translateY(50%);
            z-index: 4;
            animation: floatElement 7s ease-in-out infinite;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Clients Section */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }

        .client-logo {
            transition: all 0.3s ease;
        }

        .client-logo:hover {
            opacity: 1 !important;
            transform: scale(1.05);
        }

        .opacity-60 {
            opacity: 0.6;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
        }

        .bg-info-soft {
            background-color: var(--info-soft);
        }

        .z-index-3 {
            z-index: 3;
        }

        .shadow-hover {
            transition: all 0.3s ease;
        }

        .shadow-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-4 {
            border-radius: 16px;
        }

        /* Animations */
        @keyframes floatElement {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        @keyframes morphShape {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }

        /* Responsive adjustments for new sections */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.25rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-stat {
                padding: 0 1rem;
                text-align: center;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            top: -150px;
            left: -150px;
            z-index: 0;
        }

        .features::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.08) 100%);
            bottom: -100px;
            right: -100px;
            z-index: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: inline-block;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .section-title p {
            font-size: 1.15rem;
            color: var(--gray);
            max-width: 800px;
            margin: 1.5rem auto 0;
            line-height: 1.7;
        }

        .feature-card {
            padding: 2.5rem;
            border-radius: 16px;
            background-color: white;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.06) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            font-size: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
            transform: scale(1.15);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .feature-card:hover h3 {
            color: var(--primary);
        }

        .feature-card p {
            color: var(--gray);
            margin-bottom: 0;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%236366f1' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .testimonial-card {
            background-color: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .testimonial-card::before {
            content: '\201C';
            font-family: Georgia, serif;
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 5rem;
            color: rgba(99, 102, 241, 0.1);
            line-height: 1;
        }

        .testimonial-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .testimonial-rating {
            color: #f59e0b;
            font-size: 1.15rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: 5px;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: auto;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 1.5rem;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1.25rem;
            object-fit: cover;
            border: 3px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover .testimonial-avatar {
            border-color: rgba(99, 102, 241, 0.3);
        }

        .testimonial-author h5 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .testimonial-author p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        @media (max-width: 767.98px) {
            .testimonial-card {
                margin-bottom: 2rem;
            }
        }

        /* CTA Section */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Contact Section */
        .contact {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            border-radius: 50%;
            z-index: 0;
            opacity: 0.5;
        }

        .contact-info {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            display: flex;
            background-color: #fff;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .contact-card:hover .contact-icon {
            background-color: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .contact-text {
            flex: 1;
        }

        .contact-text h5 {
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .contact-text p {
            margin-bottom: 0.25rem;
            color: var(--gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .contact-form-wrapper {
            background-color: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
        }

        .contact-form-wrapper:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .contact-form .form-control,
        .contact-form .form-select {
            border: 1px solid #e9ecef;
            padding: 0.85rem 1.25rem;
            transition: all 0.3s ease;
            background-color: #fafbfc;
            border-radius: 8px;
            color: var(--dark);
            font-size: 1rem;
        }

        .contact-form .form-control:focus,
        .contact-form .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
            background-color: #ffffff;
        }

        .contact-form .form-floating label {
            padding: 0.85rem 1.25rem;
            color: #64748b;
        }

        .contact-form .form-check-label {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .contact-form .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .contact-form .btn-primary {
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-size: 1.05rem;
        }

        @media (max-width: 991.98px) {
            .contact-info {
                margin-bottom: 2.5rem;
            }
        }

        /* Footer */
        .footer {
            padding: 80px 0 40px;
            background-color: var(--dark);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-logo {
            margin-bottom: 1.5rem;
        }

        .footer-logo img {
        /* Styles d'animation ajoutés */
        .footer-logo img:hover {
            transform: scale(1.05);
        } height: 65px; transition: transform 0.3s ease;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .footer h5 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            display: inline-block;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
            border-radius: 10px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.85rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 0;
            display: inline-block;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: none;
            padding-left: 10px;
        }

        .footer-links a::before {
            content: "\f105";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .footer-links a:hover::before {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .social-links a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: var(--secondary);
            z-index: -1;
            transform: scale(0);
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .social-links a:hover {
            background-color: transparent;
            transform: translateY(-5px);
            color: white;
        }

        .social-links a:hover::after {
            transform: scale(1);
        }

        .copyright {
            padding-top: 2rem;
            margin-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        /* Media Queries */
        @media (max-width: 1199.98px) {
            .hero h1 {
                font-size: 3.25rem;
            }
            
            .section-title h2 {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 991.98px) {
            .navbar-brand img {
                height: 50px;
            }
            
            .hero {
                padding: 150px 0 100px;
            }
            
            .hero h1 {
                font-size: 2.75rem;
            }

            .hero-img {
                margin-top: 3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .contact-form-wrapper {
                padding: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                padding: 1rem 0;
            }
            
            .hero {
                padding: 120px 0 80px;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero p {
                font-size: 1.1rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero .btn {
                padding: 0.75rem 1.5rem;
            }
            
            .hero .d-flex {
                justify-content: center;
            }

            .section {
                padding: 80px 0;
            }

            .section-title h2 {
                font-size: 1.85rem;
            }
            
            .section-title p {
                font-size: 1rem;
            }
            
            .feature-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .feature-icon {
                margin-left: auto;
                margin-right: auto;
            }
            
            .cta {
                padding: 80px 0;
            }

            .cta h2 {
                font-size: 1.85rem;
            }
            
            .cta p {
                font-size: 1rem;
            }

            .footer {
                text-align: center;
                padding: 60px 0 30px;
            }
            
            .footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
            
            .footer-links a::before {
                display: none;
            }
            
            .footer-links a:hover {
                padding-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.75rem;
            }
            
            .btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.95rem;
            }
            
            .feature-card {
                padding: 1.75rem;
            }
            
            .testimonial-card {
                padding: 1.75rem;
            }
            
            .contact-form-wrapper {
                padding: 1.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KLASSCI - Solution de Gestion Intelligente</title>

        <!-- Polices Google -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- AOS - Animation On Scroll -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

        <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6366f1;
            --accent: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;

            /* Espacement */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 5rem;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;

            /* Nouveaux styles pour la refonte de l'interface */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            --text-gradient: linear-gradient(to right, #6366f1, #ec4899);
            --primary-soft: rgba(99, 102, 241, 0.1);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --info-soft: rgba(14, 165, 233, 0.1);
        }

        /* Base */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .section {
            padding: var(--spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .subtitle {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: var(--spacing-xs);
            display: inline-block;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 2.75rem;
            margin-bottom: var(--spacing-md);
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 70px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .text-center .section-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: var(--spacing-lg);
            max-width: 800px;
            line-height: 1.7;
        }

        /* Boutons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
            z-index: -1;
            border-radius: var(--border-radius-full);
        }

        .btn:hover::after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background-color: #5558e0;
            border-color: #5558e0;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
        }

        /* Navigation */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .navbar-brand img { height: 75px;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-scrolled .navbar-brand img { height: 65px;
        }

        .navbar-scrolled .nav-link {
            color: var(--dark);
        }

        .navbar-scrolled .nav-link:hover,
        .navbar-scrolled .nav-link.active {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 40px;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-cta {
            background-color: var(--secondary);
            color: white !important;
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            border: none;
        }

        .nav-cta:hover {
            background-color: #5558e0;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(99, 102, 241, 0.1);
        }

        .nav-cta::after {
            display: none;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #8a4baf 0%, #6366f1 100%);
            color: white;
            padding: 140px 0 120px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.75rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-description {
            font-size: 1.35rem;
            line-height: 1.8;
            opacity: 0.95;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
        }

        .text-gradient {
            background: linear-gradient(to right, #f9a8d4, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 900;
        }

        .hero-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }
        
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #f9a8d4;
            display: inline-block;
        }

        .badge-text {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }

        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Animations pour la section hero */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .hero-gradient {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 15% 15%, rgba(236, 72, 153, 0.5) 0%, transparent 25%),
                             radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.5) 0%, transparent 25%);
            opacity: 0.5;
            z-index: -1;
        }

        .hero-particles {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .hero-image-container {
            padding: 30px;
        }

        .hero-image-wrapper {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 3;
        }

        .hero-image {
            transition: all 0.5s ease;
            transform: scale(1);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.02);
        }

        .hero-image-shape-1,
        .hero-image-shape-2 {
            width: 200px;
            height: 200px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            z-index: 1;
        }

        .hero-image-shape-1 {
            top: -50px;
            right: -50px;
            background: var(--secondary-gradient);
            opacity: 0.3;
            animation: morphShape 15s linear infinite alternate;
        }

        .hero-image-shape-2 {
            bottom: -50px;
            left: -50px;
            background: var(--primary-gradient);
            opacity: 0.3;
            animation: morphShape 20s linear infinite alternate-reverse;
        }

        .hero-image-element-1 {
            top: 25%;
            right: -15px;
            transform: translateY(-50%);
            z-index: 4;
            animation: floatElement 5s ease-in-out infinite;
        }

        .hero-image-element-2 {
            bottom: 20%;
            left: -15px;
            transform: translateY(50%);
            z-index: 4;
            animation: floatElement 7s ease-in-out infinite;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Clients Section */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }

        .client-logo {
            transition: all 0.3s ease;
        }

        .client-logo:hover {
            opacity: 1 !important;
            transform: scale(1.05);
        }

        .opacity-60 {
            opacity: 0.6;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
        }

        .bg-info-soft {
            background-color: var(--info-soft);
        }

        .z-index-3 {
            z-index: 3;
        }

        .shadow-hover {
            transition: all 0.3s ease;
        }

        .shadow-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-4 {
            border-radius: 16px;
        }

        /* Animations */
        @keyframes floatElement {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        @keyframes morphShape {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }

        /* Responsive adjustments for new sections */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.25rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-stat {
                padding: 0 1rem;
                text-align: center;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            top: -150px;
            left: -150px;
            z-index: 0;
        }

        .features::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.08) 100%);
            bottom: -100px;
            right: -100px;
            z-index: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: inline-block;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .section-title p {
            font-size: 1.15rem;
            color: var(--gray);
            max-width: 800px;
            margin: 1.5rem auto 0;
            line-height: 1.7;
        }

        .feature-card {
            padding: 2.5rem;
            border-radius: 16px;
            background-color: white;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.06) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            font-size: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
            transform: scale(1.15);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .feature-card:hover h3 {
            color: var(--primary);
        }

        .feature-card p {
            color: var(--gray);
            margin-bottom: 0;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%236366f1' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .testimonial-card {
            background-color: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .testimonial-card::before {
            content: '\201C';
            font-family: Georgia, serif;
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 5rem;
            color: rgba(99, 102, 241, 0.1);
            line-height: 1;
        }

        .testimonial-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .testimonial-rating {
            color: #f59e0b;
            font-size: 1.15rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: 5px;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: auto;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 1.5rem;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1.25rem;
            object-fit: cover;
            border: 3px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover .testimonial-avatar {
            border-color: rgba(99, 102, 241, 0.3);
        }

        .testimonial-author h5 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .testimonial-author p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        @media (max-width: 767.98px) {
            .testimonial-card {
                margin-bottom: 2rem;
            }
        }

        /* CTA Section */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Contact Section */
        .contact {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            border-radius: 50%;
            z-index: 0;
            opacity: 0.5;
        }

        .contact-info {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            display: flex;
            background-color: #fff;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .contact-card:hover .contact-icon {
            background-color: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .contact-text {
            flex: 1;
        }

        .contact-text h5 {
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .contact-text p {
            margin-bottom: 0.25rem;
            color: var(--gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .contact-form-wrapper {
            background-color: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
        }

        .contact-form-wrapper:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .contact-form .form-control,
        .contact-form .form-select {
            border: 1px solid #e9ecef;
            padding: 0.85rem 1.25rem;
            transition: all 0.3s ease;
            background-color: #fafbfc;
            border-radius: 8px;
            color: var(--dark);
            font-size: 1rem;
        }

        .contact-form .form-control:focus,
        .contact-form .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
            background-color: #ffffff;
        }

        .contact-form .form-floating label {
            padding: 0.85rem 1.25rem;
            color: #64748b;
        }

        .contact-form .form-check-label {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .contact-form .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .contact-form .btn-primary {
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-size: 1.05rem;
        }

        @media (max-width: 991.98px) {
            .contact-info {
                margin-bottom: 2.5rem;
            }
        }

        /* Footer */
        .footer {
            padding: 80px 0 40px;
            background-color: var(--dark);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-logo {
            margin-bottom: 1.5rem;
        }

        .footer-logo img {
        /* Styles d'animation ajoutés */
        .footer-logo img:hover {
            transform: scale(1.05);
        } height: 65px; transition: transform 0.3s ease;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .footer h5 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            display: inline-block;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
            border-radius: 10px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.85rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 0;
            display: inline-block;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: none;
            padding-left: 10px;
        }

        .footer-links a::before {
            content: "\f105";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .footer-links a:hover::before {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .social-links a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: var(--secondary);
            z-index: -1;
            transform: scale(0);
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .social-links a:hover {
            background-color: transparent;
            transform: translateY(-5px);
            color: white;
        }

        .social-links a:hover::after {
            transform: scale(1);
        }

        .copyright {
            padding-top: 2rem;
            margin-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        /* Media Queries */
        @media (max-width: 1199.98px) {
            .hero h1 {
                font-size: 3.25rem;
            }
            
            .section-title h2 {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 991.98px) {
            .navbar-brand img {
                height: 50px;
            }
            
            .hero {
                padding: 150px 0 100px;
            }
            
            .hero h1 {
                font-size: 2.75rem;
            }

            .hero-img {
                margin-top: 3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .contact-form-wrapper {
                padding: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                padding: 1rem 0;
            }
            
            .hero {
                padding: 120px 0 80px;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero p {
                font-size: 1.1rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero .btn {
                padding: 0.75rem 1.5rem;
            }
            
            .hero .d-flex {
                justify-content: center;
            }

            .section {
                padding: 80px 0;
            }

            .section-title h2 {
                font-size: 1.85rem;
            }
            
            .section-title p {
                font-size: 1rem;
            }
            
            .feature-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .feature-icon {
                margin-left: auto;
                margin-right: auto;
            }
            
            .cta {
                padding: 80px 0;
            }

            .cta h2 {
                font-size: 1.85rem;
            }
            
            .cta p {
                font-size: 1rem;
            }

            .footer {
                text-align: center;
                padding: 60px 0 30px;
            }
            
            .footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
            
            .footer-links a::before {
                display: none;
            }
            
            .footer-links a:hover {
                padding-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.75rem;
            }
            
            .btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.95rem;
            }
            
            .feature-card {
                padding: 1.75rem;
            }
            
            .testimonial-card {
                padding: 1.75rem;
            }
            
            .contact-form-wrapper {
                padding: 1.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
        .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KLASSCI - Solution de Gestion Intelligente</title>

        <!-- Polices Google -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- AOS - Animation On Scroll -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

        <style>
        :root {
            /* Palette de couleurs */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6366f1;
            --accent: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;

            /* Espacement */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 5rem;

            /* Bordures */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 2rem;
            --border-radius-full: 9999px;

            /* Nouveaux styles pour la refonte de l'interface */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            --text-gradient: linear-gradient(to right, #6366f1, #ec4899);
            --primary-soft: rgba(99, 102, 241, 0.1);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --info-soft: rgba(14, 165, 233, 0.1);
        }

        /* Base */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .section {
            padding: var(--spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .subtitle {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: var(--spacing-xs);
            display: inline-block;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 2.75rem;
            margin-bottom: var(--spacing-md);
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 70px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .text-center .section-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: var(--spacing-lg);
            max-width: 800px;
            line-height: 1.7;
        }

        /* Boutons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
            z-index: -1;
            border-radius: var(--border-radius-full);
        }

        .btn:hover::after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background-color: #5558e0;
            border-color: #5558e0;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius-full);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
        }

        /* Navigation */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .navbar-brand img { height: 75px;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-scrolled .navbar-brand img { height: 65px;
        }

        .navbar-scrolled .nav-link {
            color: var(--dark);
        }

        .navbar-scrolled .nav-link:hover,
        .navbar-scrolled .nav-link.active {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 40px;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(15, 23, 42, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-cta {
            background-color: var(--secondary);
            color: white !important;
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            border: none;
        }

        .nav-cta:hover {
            background-color: #5558e0;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(99, 102, 241, 0.1);
        }

        .nav-cta::after {
                display: none;
            }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #8a4baf 0%, #6366f1 100%);
            color: white;
            padding: 140px 0 120px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.75rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-description {
            font-size: 1.35rem;
            line-height: 1.8;
            opacity: 0.95;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
        }

        .text-gradient {
            background: linear-gradient(to right, #f9a8d4, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 900;
        }

        .hero-badge {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }
        
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #f9a8d4;
            display: inline-block;
        }

        .badge-text {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
        }

        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }

        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Animations pour la section hero */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .hero-gradient {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 15% 15%, rgba(236, 72, 153, 0.5) 0%, transparent 25%),
                             radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.5) 0%, transparent 25%);
            opacity: 0.5;
            z-index: -1;
        }

        .hero-particles {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .hero-image-container {
            padding: 30px;
        }

        .hero-image-wrapper {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 3;
        }

        .hero-image {
            transition: all 0.5s ease;
            transform: scale(1);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.02);
        }

        .hero-image-shape-1,
        .hero-image-shape-2 {
            width: 200px;
            height: 200px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            z-index: 1;
        }

        .hero-image-shape-1 {
            top: -50px;
            right: -50px;
            background: var(--secondary-gradient);
            opacity: 0.3;
            animation: morphShape 15s linear infinite alternate;
        }

        .hero-image-shape-2 {
            bottom: -50px;
            left: -50px;
            background: var(--primary-gradient);
            opacity: 0.3;
            animation: morphShape 20s linear infinite alternate-reverse;
        }

        .hero-image-element-1 {
            top: 25%;
            right: -15px;
            transform: translateY(-50%);
            z-index: 4;
            animation: floatElement 5s ease-in-out infinite;
        }

        .hero-image-element-2 {
            bottom: 20%;
            left: -15px;
            transform: translateY(50%);
            z-index: 4;
            animation: floatElement 7s ease-in-out infinite;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Clients Section */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }

        .client-logo {
            transition: all 0.3s ease;
        }

        .client-logo:hover {
            opacity: 1 !important;
            transform: scale(1.05);
        }

        .opacity-60 {
            opacity: 0.6;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .bg-success-soft {
            background-color: var(--success-soft);
        }

        .bg-primary-soft {
            background-color: var(--primary-soft);
        }

        .bg-warning-soft {
            background-color: var(--warning-soft);
        }

        .bg-info-soft {
            background-color: var(--info-soft);
        }

        .z-index-3 {
            z-index: 3;
        }

        .shadow-hover {
            transition: all 0.3s ease;
        }

        .shadow-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-4 {
            border-radius: 16px;
        }

        /* Animations */
        @keyframes floatElement {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        @keyframes morphShape {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }

        /* Responsive adjustments for new sections */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-description {
                font-size: 1.25rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-description {
                font-size: 1.1rem;
            }
            
            .hero-stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-stat {
                padding: 0 1rem;
                text-align: center;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            top: -150px;
            left: -150px;
            z-index: 0;
        }

        .features::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.08) 100%);
            bottom: -100px;
            right: -100px;
            z-index: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            display: inline-block;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 10px;
        }

        .section-title p {
            font-size: 1.15rem;
            color: var(--gray);
            max-width: 800px;
            margin: 1.5rem auto 0;
            line-height: 1.7;
        }

        .feature-card {
            padding: 2.5rem;
            border-radius: 16px;
            background-color: white;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.06) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            font-size: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 20px;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
            transform: scale(1.15);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .feature-card:hover h3 {
            color: var(--primary);
        }

        .feature-card p {
            color: var(--gray);
            margin-bottom: 0;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%236366f1' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .testimonial-card {
            background-color: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .testimonial-card::before {
            content: '\201C';
            font-family: Georgia, serif;
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 5rem;
            color: rgba(99, 102, 241, 0.1);
            line-height: 1;
        }

        .testimonial-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .testimonial-rating {
            color: #f59e0b;
            font-size: 1.15rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: 5px;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: var(--gray);
            font-style: italic;
            margin-bottom: 2rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: auto;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 1.5rem;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1.25rem;
            object-fit: cover;
            border: 3px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover .testimonial-avatar {
            border-color: rgba(99, 102, 241, 0.3);
        }

        .testimonial-author h5 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .testimonial-author p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        @media (max-width: 767.98px) {
            .testimonial-card {
                margin-bottom: 2rem;
            }
        }

        /* CTA Section */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Contact Section */
        .contact {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }

        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.1) 100%);
            border-radius: 50%;
            z-index: 0;
            opacity: 0.5;
        }

        .contact-info {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            display: flex;
            background-color: #fff;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .contact-card:hover .contact-icon {
            background-color: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .contact-text {
            flex: 1;
        }

        .contact-text h5 {
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .contact-text p {
            margin-bottom: 0.25rem;
            color: var(--gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .contact-form-wrapper {
            background-color: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
        }

        .contact-form-wrapper:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .contact-form .form-control,
        .contact-form .form-select {
            border: 1px solid #e9ecef;
            padding: 0.85rem 1.25rem;
            transition: all 0.3s ease;
            background-color: #fafbfc;
            border-radius: 8px;
            color: var(--dark);
            font-size: 1rem;
        }

        .contact-form .form-control:focus,
        .contact-form .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
            background-color: #ffffff;
        }

        .contact-form .form-floating label {
            padding: 0.85rem 1.25rem;
            color: #64748b;
        }

        .contact-form .form-check-label {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .contact-form .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .contact-form .btn-primary {
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-size: 1.05rem;
        }

        @media (max-width: 991.98px) {
            .contact-info {
                margin-bottom: 2.5rem;
            }
        }

        /* Footer */
        .footer {
            padding: 80px 0 40px;
            background-color: var(--dark);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20L0 20z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-logo {
            margin-bottom: 1.5rem;
        }

        .footer-logo img {
        /* Styles d'animation ajoutés */
        .footer-logo img:hover {
            transform: scale(1.05);
        } height: 65px; transition: transform 0.3s ease;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .footer h5 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            display: inline-block;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
            border-radius: 10px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.85rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 0;
            display: inline-block;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: none;
            padding-left: 10px;
        }

        .footer-links a::before {
            content: "\f105";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .footer-links a:hover::before {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .social-links a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: var(--secondary);
            z-index: -1;
            transform: scale(0);
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .social-links a:hover {
            background-color: transparent;
            transform: translateY(-5px);
            color: white;
        }

        .social-links a:hover::after {
            transform: scale(1);
        }

        .copyright {
            padding-top: 2rem;
            margin-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        /* Media Queries */
        @media (max-width: 1199.98px) {
            .hero h1 {
                font-size: 3.25rem;
            }
            
            .section-title h2 {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 991.98px) {
            .navbar-brand img {
                height: 50px;
            }
            
            .hero {
                padding: 150px 0 100px;
            }
            
            .hero h1 {
                font-size: 2.75rem;
            }

            .hero-img {
                margin-top: 3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .contact-form-wrapper {
                padding: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                padding: 1rem 0;
            }
            
            .hero {
                padding: 120px 0 80px;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero p {
                font-size: 1.1rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero .btn {
                padding: 0.75rem 1.5rem;
            }
            
            .hero .d-flex {
                justify-content: center;
            }

            .section {
                padding: 80px 0;
            }

            .section-title h2 {
                font-size: 1.85rem;
            }
            
            .section-title p {
                font-size: 1rem;
            }
            
            .feature-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .feature-icon {
                margin-left: auto;
                margin-right: auto;
            }
            
            .cta {
                padding: 80px 0;
            }

            .cta h2 {
                font-size: 1.85rem;
            }
            
            .cta p {
                font-size: 1rem;
            }

            .footer {
                text-align: center;
                padding: 60px 0 30px;
            }
            
            .footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
            
            .footer-links a::before {
                display: none;
            }
            
            .footer-links a:hover {
                padding-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.75rem;
            }
            
            .btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.95rem;
            }
            
            .feature-card {
                padding: 1.75rem;
            }
            
            .testimonial-card {
                padding: 1.75rem;
            }
            
            .contact-form-wrapper {
                padding: 1.75rem;
            }
        }

        /* Solutions Section */
        .solutions {
            background-color: #f8fafc;
            padding: 100px 0;
        }
        
        .solution-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .solution-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .solution-badge {
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }
        
        .badge-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
        }
        
            .solution-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .solution-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .solution-features li {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .solution-features li i {
            margin-right: 10px;
            font-size: 0.875rem;
        }
        
        /* Responsive pour la section Solutions */
        @media (max-width: 991.98px) {
            .solutions {
                padding: 80px 0;
            }
            
            .solution-card {
                height: auto !important;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .solution-title {
                font-size: 1.5rem;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            .solutions {
                padding: 60px 0;
            }
            
            .solution-title {
                font-size: 1.25rem;
            }
            
            .solution-features li {
                font-size: 0.9rem;
            }
        }

        /* Free Trial Section */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .free-trial::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.05);
            z-index: 0;
        }

        .free-trial::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.08);
            z-index: 0;
        }

        .free-trial-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .floating-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }

        .floating-icon-1 {
            top: 60px;
            right: -20px;
            background: var(--primary);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            animation-delay: 0.5s;
        }

        .floating-icon-2 {
            bottom: 80px;
            left: -20px;
            background: #FC6736;
            box-shadow: 0 10px 25px rgba(252, 103, 54, 0.3);
            animation-delay: 1.5s;
        }

        .floating-badge {
            position: absolute;
            bottom: -20px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .badge-logo {
            width: 60px;
            height: auto;
        }

        .badge-primary {
            display: inline-block;
            background-color: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .free-trial-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .free-trial-description {
            font-size: 1.15rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .feature-item span {
            font-size: 1.05rem;
            color: var(--dark);
        }

        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .btn-primary:hover {
            background-color: #5a4ecc;
            border-color: #5a4ecc;
        }

        .text-primary {
            color: #6c5ce7 !important;
        }

        /* Media Queries for new sections */
        @media (max-width: 991.98px) {
            .solution-title {
                font-size: 1.9rem;
            }
            
            .free-trial-title {
                font-size: 2.2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.6rem;
            }
            
            .floating-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-badge {
                width: 70px;
                height: 70px;
            }
            
            .badge-logo {
                width: 50px;
            }
        }

        @media (max-width: 767.98px) {
            .solution-card {
                margin-bottom: 4rem;
                text-align: center;
            }
            
            .solution-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .solution-features li {
                justify-content: center;
            }
            
            .solution-badge, 
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            
            .free-trial-title {
                font-size: 1.8rem;
                text-align: center;
            }
            
            .free-trial-subtitle {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .free-trial-description {
                text-align: center;
            }
            
            .badge-primary {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: max-content;
            }
            
            .free-trial-content .btn {
                display: block;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .solution-image-wrapper {
                margin: 0 15px;
            }
            
            .solution-badge {
                left: 15px;
            }
            
            .solution-badge-secondary {
                right: 15px;
                bottom: 15px;
            }
            
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
        }

        /* Media Queries pour une meilleure responsive */
        @media (max-width: 1199.98px) {
            .solution-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .free-trial-content {
                padding-right: 15px;
                padding-left: 15px;
            }
            
            .solution-image-wrapper {
                max-width: 90%;
                margin: 0 auto;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .floating-icon-1,
            .floating-icon-2 {
                display: none;
            }
            
            .solution-badge {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                top: -10px;
                left: 15px;
            }
            
            .solution-badge-secondary {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
                bottom: 15px;
                right: 0;
            }
            
            .solution-title {
                font-size: 1.7rem;
            }
            
            .free-trial-title {
                font-size: 1.7rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.3rem;
            }
            
            .floating-badge {
                width: 60px;
                height: 60px;
                bottom: -15px;
                right: 15px;
            }
            
            .badge-logo {
                width: 40px;
            }
            
            .cta h2 {
                font-size: 1.7rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .cta-buttons .btn {
                width: 100%;
                margin: 0 !important;
            }
        }

        .text-gradient {
            background: var(--text-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        /* Nouvelles classes pour la section des fonctionnalités */
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .feature-icon-bg {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            border-radius: 16px;
            transform: rotate(10deg);
            top: 15px;
            left: 15px;
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon-bg {
            transform: rotate(25deg) scale(1.2);
        }
        
        .feature-list {
            padding-left: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-dark);
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0.35rem;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .feature-link {
            display: inline-block;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-link:hover {
            color: var(--primary-darker);
            transform: translateX(5px);
        }
        
        .advantages-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .advantages-image {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
        }
        
        .advantages-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -15px rgba(0, 0, 0, 0.15);
        }
        
        .advantages-card {
            bottom: 30px;
            right: -20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            z-index: 10;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .advantages-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1rem;
            margin-right: 15px;
        }
        
        .advantages-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .advantages-card-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .features-dots {
            width: 180px;
            height: 180px;
            background-image: radial-gradient(rgba(99, 102, 241, 0.2) 2px, transparent 2px);
            background-size: 18px 18px;
            opacity: 0.3;
            z-index: 1;
        }
        
        .features-circle {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.08) 100%);
            border-radius: 50%;
            top: 50%;
            right: -150px;
            z-index: 1;
        }
        
        /* Media queries pour la section des fonctionnalités */
        @media (max-width: 991.98px) {
            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }
            
            .advantages-image {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .feature-card {
                padding: 1.75rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 {
                font-size: 1.25rem;
            }
            
            .feature-card p {
                font-size: 1rem;
            }
            
            .advantages-card {
                right: 0;
                bottom: 20px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .advantages-card {
                padding: 10px;
            }
            
            .advantages-card-icon {
                width: 30px;
                height: 30px;
                font-size: 0.875rem;
                margin-right: 10px;
            }
            
            .advantages-card-title {
                font-size: 1rem;
            }
            
            .advantages-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* Styles pour la section Free Trial */
        .free-trial {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .free-trial-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .free-trial-subtitle {
            font-size: 1.5rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .free-trial-description {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.7;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            color: var(--gray-dark);
        }
        
        .feature-item i {
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .floating-icon-1, .floating-icon-2 {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon-2 {
            animation-delay: 2s;
        }
        
        .floating-badge {
            animation: float 8s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        /* Responsive pour la section Free Trial */
        @media (max-width: 991.98px) {
            .free-trial-title {
                font-size: 2rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .free-trial-title {
                font-size: 1.75rem;
            }
            
            .free-trial-subtitle {
                font-size: 1.1rem;
            }
            
            .feature-item {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .free-trial-title {
                font-size: 1.5rem;
            }
            
            .floating-icon-1, .floating-icon-2 {
                display: none;
            }
        }

        /* Styles pour la section CTA */
        .cta {
            background-color: var(--primary);
            padding: 80px 0 120px;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .cta-buttons .btn-light {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .cta-buttons .btn-outline-light {
            border: 2px solid white;
            padding: 12px 24px;
        }
        
        .cta-buttons .btn-outline-light:hover {
            background-color: white;
            color: var(--primary);
        }
        
        /* Responsive pour CTA */
        @media (max-width: 991.98px) {
            .cta h2 {
                font-size: 2.25rem;
            }
            
            .cta p {
                font-size: 1.1rem;
            }
            
            .cta-icon {
                width: 90px;
                height: 90px;
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .cta {
                padding: 60px 0 100px;
            }
            
            .cta h2 {
                font-size: 1.8rem;
            }
            
            .cta p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .cta-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .cta h2 {
                font-size: 1.6rem;
            }
            
            .cta-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Styles pour les badges et section trusted-by */
        .trusted-by {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
        }
        
        .trusted-badge {
            transition: all 0.3s ease;
        }
        
        .trusted-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .divider {
            height: 30px;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Assurer la visibilité des boutons et statistiques */
        .hero {
            padding: 140px 0 120px; /* Augmenté la marge inférieure */
        }
        
        .hero-stats {
            margin-top: 3rem !important;
            padding-bottom: 2rem;
        }
        
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .hero-actions .btn:hover {
            transform: translateY(-3px);
        }
        
        @media (max-width: 767.98px) {
            .hero-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .hero-stat {
                width: auto;
                padding: 0 1rem;
                border: none !important;
                margin: 0 !important;
            }
            
            .trusted-badge .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .divider {
                display: none !important;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .hero-actions .btn {
                width: 100%;
            }
        }
        </style>
    </head>
    <body>
        <!-- Barre de navigation -->
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <img src="{{ asset('icons/klassci.jpeg') }}" alt="KLASSCI Logo" class="img-fluid">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#features">Fonctionnalités</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#solutions">Solutions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#pricing">Tarifs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">Contact</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-primary rounded-pill shadow-hover" href="{{ route('login') }}">Se connecter</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero position-relative overflow-hidden">
            <div class="hero-particles position-absolute" id="hero-particles"></div>
            <div class="hero-gradient position-absolute"></div>
            <div class="hero-shape-top position-absolute">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                    <path fill="rgba(255, 255, 255, 0.05)" fill-opacity="1" d="M0,64L48,90.7C96,117,192,171,288,186.7C384,203,480,181,576,165.3C672,149,768,139,864,154.7C960,171,1056,213,1152,218.7C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>
            <div class="container position-relative z-index-3">
                <div class="row align-items-center py-5">
                    <div class="col-lg-6 hero-content" data-aos="fade-right" data-aos-duration="1200">
                        <div class="hero-badge d-inline-flex align-items-center rounded-pill mb-4">
                            <div class="badge-dot bg-white me-2"></div>
                            <span class="badge-text">Logiciel de Gestion Scolaire</span>
                        </div>
                        <h1 class="hero-title mb-4">La solution <span class="text-gradient">ivoirienne</span><br>de gestion <span class="text-gradient">intelligente</span></h1>
                        <p class="hero-description">KLASSCI est un logiciel ivoirien intelligent, organisant le travail de l'administration et des enseignants pour tous les types d'établissements, du primaire à l'université de façon simple et fiable.</p>
                        <div class="hero-actions d-flex flex-wrap gap-3 mt-5">
                            <a href="#demo" class="btn btn-light btn-lg rounded-pill shadow-hover">
                                <i class="fas fa-play-circle me-2"></i>
                                Demander une démo
                            </a>
                            <a href="#" class="btn btn-outline-light btn-lg rounded-pill shadow-hover">
                                En savoir plus
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="hero-stats d-flex flex-wrap mt-5">
                            <div class="hero-stat pe-4 me-4 border-end">
                                <div class="hero-stat-value">1000+</div>
                                <div class="hero-stat-label">Établissements</div>
                            </div>
                            <div class="hero-stat pe-4 me-4 border-end">
                                <div class="hero-stat-value">50K+</div>
                                <div class="hero-stat-label">Utilisateurs</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-value">99%</div>
                                <div class="hero-stat-label">Satisfaction</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mt-5 mt-lg-0" data-aos="fade-left" data-aos-duration="1200" data-aos-delay="200">
                        <div class="hero-image-container position-relative">
                            <div class="hero-image-wrapper floating">
                                <img src="{{ asset('images/tableaudeborddemo.jpg') }}" alt="Dashboard KLASSCI" class="img-fluid rounded-4 hero-image shadow-lg">
                            </div>
                            <div class="hero-image-shape-1 position-absolute"></div>
                            <div class="hero-image-shape-2 position-absolute"></div>
                            <div class="hero-image-element-1 position-absolute p-3 bg-white rounded-4 shadow-lg">
                                <div class="d-flex align-items-center">
                                    <div class="element-icon bg-success-soft rounded-circle p-2 me-2">
                                        <i class="fas fa-chart-line text-success"></i>
                                    </div>
                                    <div class="element-text">
                                        <div class="element-title small fw-bold">Performance</div>
                                        <div class="element-value text-success">+24.5%</div>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-image-element-2 position-absolute p-3 bg-white rounded-4 shadow-lg">
                                <div class="d-flex align-items-center">
                                    <div class="element-icon bg-primary-soft rounded-circle p-2 me-2">
                                        <i class="fas fa-user-graduate text-primary"></i>
                                    </div>
                                    <div class="element-text">
                                        <div class="element-title small fw-bold">Étudiants</div>
                                        <div class="element-value">12.8K</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-shape-bottom position-absolute bottom-0 start-0 w-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                    <path fill="#ffffff" fill-opacity="1" d="M0,96L48,128C96,160,192,224,288,224C384,224,480,160,576,133.3C672,107,768,117,864,144C960,171,1056,213,1152,202.7C1248,192,1344,128,1392,96L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>
        </section>

        <!-- Trusted By Section - Remplacé par une bannière -->
        <section class="trusted-by py-4 bg-light">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 text-center">
                        <h5 class="mb-4 text-dark fw-bold">UTILISÉ PAR LES MEILLEURES INSTITUTIONS EN CÔTE D'IVOIRE</h5>
                        <div class="trusted-badge p-3 bg-white shadow-sm rounded-4">
                            <div class="row align-items-center justify-content-center">
                                <div class="col-auto">
                                    <div class="d-flex align-items-center">
                                        <div class="badge-dot bg-primary me-2"></div>
                                        <span class="fw-bold text-primary">1000+ Établissements</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="divider mx-4 d-none d-md-block"></div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex align-items-center">
                                        <div class="badge-dot bg-success me-2"></div>
                                        <span class="fw-bold text-success">50K+ Utilisateurs actifs</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="divider mx-4 d-none d-md-block"></div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex align-items-center">
                                        <div class="badge-dot bg-warning me-2"></div>
                                        <span class="fw-bold text-warning">Primaire au Supérieur</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <div class="section-title" data-aos="fade-up" data-aos-duration="1000">
                    <h2>Fonctionnalités principales</h2>
                    <p>Découvrez comment KLASSCI peut transformer vos opérations quotidiennes en processus efficaces et automatisés</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                        <div class="feature-card">
                            <div class="feature-icon-wrapper">
                                <div class="feature-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="feature-icon-bg"></div>
                            </div>
                            <h3>Tableaux de bord interactifs</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-check-circle text-primary"></i> Visualisez les performances académiques et les statistiques de présence en temps réel</li>
                                <li><i class="fas fa-check-circle text-primary"></i> Tableaux personnalisables adaptés aux besoins des administrateurs et enseignants</li>
                            </ul>
                            <a href="#" class="feature-link">En savoir plus <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                        <div class="feature-card">
                            <div class="feature-icon-wrapper">
                                <div class="feature-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="feature-icon-bg"></div>
                            </div>
                            <h3>Gestion des utilisateurs</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-check-circle text-primary"></i> Créez et gérez facilement différents profils (administrateurs, enseignants, parents, élèves)</li>
                                <li><i class="fas fa-check-circle text-primary"></i> Niveaux d'accès spécifiques pour sécuriser les données sensibles</li>
                            </ul>
                            <a href="#" class="feature-link">En savoir plus <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">
                        <div class="feature-card">
                            <div class="feature-icon-wrapper">
                                <div class="feature-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="feature-icon-bg"></div>
                            </div>
                            <h3>Automatisation des tâches</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-check-circle text-primary"></i> Automatisez la génération des bulletins et le calcul des moyennes</li>
                                <li><i class="fas fa-check-circle text-primary"></i> Programmation des évaluations et calcul des salaires des vacataires</li>
                            </ul>
                            <a href="#" class="feature-link">En savoir plus <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
                        <div class="feature-card">
                            <div class="feature-icon-wrapper">
                                <div class="feature-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="feature-icon-bg"></div>
                            </div>
                            <h3>Gestion documentaire</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-check-circle text-primary"></i> Stockez et partagez les programmes scolaires et supports de cours</li>
                                <li><i class="fas fa-check-circle text-primary"></i> Organisez les résultats d'examens et documents administratifs</li>
                            </ul>
                            <a href="#" class="feature-link">En savoir plus <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="500">
                        <div class="feature-card">
                            <div class="feature-icon-wrapper">
                                <div class="feature-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="feature-icon-bg"></div>
                            </div>
                            <h3>Notifications et alertes</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-check-circle text-primary"></i> Restez informé des absences d'élèves et des réunions à venir</li>
                                <li><i class="fas fa-check-circle text-primary"></i> Système de notifications personnalisables par email ou dans l'application</li>
                            </ul>
                            <a href="#" class="feature-link">En savoir plus <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="600">
                        <div class="feature-card">
                            <div class="feature-icon-wrapper">
                                <div class="feature-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="feature-icon-bg"></div>
                            </div>
                            <h3>Interface web responsive</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-check-circle text-primary"></i> Accès à toutes les fonctionnalités depuis n'importe quel appareil</li>
                                <li><i class="fas fa-check-circle text-primary"></i> Interface intuitive permettant aux parents de suivre leurs enfants</li>
                            </ul>
                            <a href="#" class="feature-link">En savoir plus <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Solutions Section -->
        <section id="solutions" class="solutions py-5 position-relative overflow-hidden">
            <div class="position-absolute features-dots top-0 left-0"></div>
            <div class="position-absolute features-circle"></div>
            
            <div class="container position-relative">
                <div class="section-title text-center mb-5" data-aos="fade-up" data-aos-duration="1000">
                    <h2>Solutions adaptées à chaque profil</h2>
                    <p>Découvrez nos fonctionnalités ciblées pour répondre précisément à vos besoins</p>
                </div>
                
                <div class="row g-4 mb-5">
                    <div class="col-lg-6" data-aos="fade-up" data-aos-duration="1000">
                        <div class="solution-card p-4 h-100 bg-white rounded-4 shadow-sm position-relative">
                            <div class="solution-badge position-absolute badge-primary">POUR LES ADMINISTRATEURS</div>
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-4 mb-md-0">
                                    <div class="solution-visual bg-white rounded-4 p-4 d-flex flex-column align-items-center justify-content-center" style="height: 250px; position: relative; overflow: hidden;">
                                        <div class="visual-bg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(99, 102, 241, 0.03) 100%); z-index: 1;"></div>
                                        <div class="visual-icon mb-3 position-relative" style="z-index: 2;">
                                            <div class="icon-circle bg-white rounded-circle shadow d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="fas fa-user-tie fa-2x text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="visual-chart d-flex align-items-end justify-content-center gap-2 position-relative" style="z-index: 2; height: 60px; width: 200px;">
                                            <div class="chart-bar bg-primary rounded-top" style="width: 20px; height: 30px;"></div>
                                            <div class="chart-bar bg-primary rounded-top" style="width: 20px; height: 45px;"></div>
                                            <div class="chart-bar bg-primary rounded-top" style="width: 20px; height: 60px;"></div>
                                            <div class="chart-bar bg-primary rounded-top" style="width: 20px; height: 40px;"></div>
                                            <div class="chart-bar bg-primary rounded-top" style="width: 20px; height: 25px;"></div>
                                        </div>
                                        <div class="position-absolute" style="top: 20px; right: 20px; z-index: 2;">
                                            <i class="fas fa-cog fa-spin text-primary opacity-25" style="font-size: 24px;"></i>
                                        </div>
                                        <div class="position-absolute" style="bottom: 20px; left: 20px; z-index: 2;">
                                            <i class="fas fa-chart-line text-primary opacity-25" style="font-size: 24px;"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h3 class="solution-title mb-4">Administrateur et directeurs</h3>
                                    <ul class="solution-features">
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Tableaux de bord analytiques</li>
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Gestion des ressources humaines</li>
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Suivi budgétaire</li>
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Rapports statistiques avancés</li>
                                    </ul>
                                    <a href="#" class="btn btn-primary rounded-pill mt-4">Voir la solution</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                        <div class="solution-card p-4 h-100 bg-white rounded-4 shadow-sm position-relative">
                            <div class="solution-badge position-absolute badge-secondary">POUR LES ENSEIGNANTS</div>
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-4 mb-md-0">
                                    <div class="solution-visual bg-white rounded-4 p-4 d-flex flex-column align-items-center justify-content-center" style="height: 250px; position: relative; overflow: hidden;">
                                        <div class="visual-bg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(236, 72, 153, 0.08) 0%, rgba(236, 72, 153, 0.03) 100%); z-index: 1;"></div>
                                        <div class="visual-icon mb-3 position-relative" style="z-index: 2;">
                                            <div class="icon-circle bg-white rounded-circle shadow d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="fas fa-chalkboard-teacher fa-2x text-accent"></i>
                                            </div>
                                        </div>
                                        <div class="visual-grid position-relative" style="z-index: 2; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; width: 200px;">
                                            <div class="grid-item bg-white rounded shadow-sm d-flex align-items-center justify-content-center" style="height: 60px;">
                                                <i class="fas fa-book text-accent"></i>
                                            </div>
                                            <div class="grid-item bg-white rounded shadow-sm d-flex align-items-center justify-content-center" style="height: 60px;">
                                                <i class="fas fa-graduation-cap text-accent"></i>
                                            </div>
                                            <div class="grid-item bg-white rounded shadow-sm d-flex align-items-center justify-content-center" style="height: 60px;">
                                                <i class="fas fa-pencil-alt text-accent"></i>
                                            </div>
                                            <div class="grid-item bg-white rounded shadow-sm d-flex align-items-center justify-content-center" style="height: 60px;">
                                                <i class="fas fa-calendar-check text-accent"></i>
                                            </div>
                                            <div class="grid-item bg-white rounded shadow-sm d-flex align-items-center justify-content-center" style="height: 60px;">
                                                <i class="fas fa-users text-accent"></i>
                                            </div>
                                            <div class="grid-item bg-white rounded shadow-sm d-flex align-items-center justify-content-center" style="height: 60px;">
                                                <i class="fas fa-chart-bar text-accent"></i>
                                            </div>
                                        </div>
                                        <div class="position-absolute" style="top: 20px; right: 20px; z-index: 2;">
                                            <i class="fas fa-lightbulb text-accent opacity-25" style="font-size: 24px;"></i>
                                        </div>
                                        <div class="position-absolute" style="bottom: 20px; left: 20px; z-index: 2;">
                                            <i class="fas fa-clipboard-check text-accent opacity-25" style="font-size: 24px;"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h3 class="solution-title mb-4">Enseignants du primaire et secondaire</h3>
                                    <ul class="solution-features">
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Gestion des notes et évaluations</li>
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Suivi des présences</li>
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Communication avec les parents</li>
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> Ressources pédagogiques</li>
                                    </ul>
                                    <a href="#" class="btn btn-primary rounded-pill mt-4">Voir la solution</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="testimonials py-5 bg-light">
            <div class="container py-5">
                <div class="section-title" data-aos="fade-up" data-aos-duration="1000">
                    <h2>Ce que nos clients disent</h2>
                    <p>Découvrez comment KLASSCI a transformé l'organisation et la productivité de nos clients à travers ces témoignages</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-6" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                        <div class="testimonial-card">
                            <div class="testimonial-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <p class="testimonial-text">"KLASSCI a complètement révolutionné notre gestion de projets. Nous avons gagné un temps précieux et amélioré la collaboration entre nos équipes de 40%."</p>
                            <div class="testimonial-author">
                                <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sophie Martin" class="testimonial-avatar">
                                <div>
                                    <h5>ESBTP Yamoussoukro</h5>
                                    <p></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-6" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                        <div class="testimonial-card">
                            <div class="testimonial-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="far fa-star"></i>
                            </div>
                            <p class="testimonial-text">"Intuitive et puissante, l'interface de KLASSCI nous permet de suivre nos performances en temps réel. Une solution indispensable pour notre développement."</p>
                            <div class="testimonial-author">
                                <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Thomas Dubois" class="testimonial-avatar">
                                <div>
                                    <h5>ESBTP ABidjan<</h5>
                                    <p></p>
                                </div>
                            </div>
                        </div>
                    </div>


                    </div>
                </div>
            </div>
        </section>

        <!-- Free Trial Section -->
<section class="free-trial py-5 position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(99, 102, 241, 0.1) 100%); border-radius: 20px; margin: 40px 0;">
    <!-- Éléments décoratifs animés -->
    <div class="position-absolute top-0 end-0" style="width: 300px; height: 300px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.15), rgba(99, 102, 241, 0.15)); border-radius: 50%; transform: translate(50%, -50%); filter: blur(60px); animation: float 15s infinite alternate ease-in-out;"></div>
    <div class="position-absolute bottom-0 start-0" style="width: 250px; height: 250px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(236, 72, 153, 0.15)); border-radius: 50%; transform: translate(-50%, 50%); filter: blur(50px); animation: float 20s infinite alternate-reverse ease-in-out;"></div>
    
    <div class="container py-5 position-relative">
        <div class="row align-items-center g-5">
            <!-- Colonne visuelle à gauche -->
            <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-up" data-aos-duration="1000">
                <div class="position-relative text-center">
                    <!-- Image principale avec effet de profondeur -->
                    <div style="transform: perspective(1000px) rotateY(-5deg) rotateX(5deg); transition: all 0.5s ease;">
                        <img src="{{ asset('images/devices-mockup.png') }}" alt="Devices Mockup" class="img-fluid rounded-4 shadow-lg" style="border: 10px solid white; transform: translateZ(20px);">
                    </div>
                    
                    <!-- Icônes flottantes avec ombre et animation -->
                    <div class="position-absolute" style="top: 15%; right: -15px; z-index: 3; animation: float 6s infinite ease-in-out;">
                        <div class="bg-white rounded-circle p-3 shadow-lg d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);">
                            <i class="fas fa-chart-pie fa-2x" style="background: linear-gradient(135deg, #6366f1, #4f46e5); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                        </div>
                    </div>
                    
                    <div class="position-absolute" style="bottom: 20%; left: -15px; z-index: 3; animation: float 7s 2s infinite ease-in-out;">
                        <div class="bg-white rounded-circle p-3 shadow-lg d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; box-shadow: 0 10px 30px rgba(236, 72, 153, 0.3);">
                            <i class="fas fa-calendar-alt fa-2x" style="background: linear-gradient(135deg, #ec4899, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                        </div>
                    </div>
                    
                    <!-- Badge KLASSCI pulsant -->
                    <div class="position-absolute" style="bottom: -25px; right: 25%; animation: pulse 3s infinite ease-in-out;">
                        <div class="bg-white rounded-circle shadow-lg p-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);">
                            <img src="{{ asset('icons/klassci.jpeg') }}" alt="KLASSCI Logo" style="width: 80px; height: auto; border-radius: 50%;">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Colonne contenu à droite -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                <div class="free-trial-content">
                    <!-- Badge premium avec dégradé et animation -->
                    <div style="display: inline-block; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; border-radius: 30px; padding: 10px 20px; font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; box-shadow: 0 10px 20px -10px rgba(99, 102, 241, 0.5); transform: translateY(0); transition: all 0.3s ease;" class="premium-badge">
                        DIGITALISEZ VOTRE ÉCOLE AVEC KLASSCI
                    </div>
                    
                    <!-- Titre principal avec effet de dégradé -->
                    <h2 class="mt-4 mb-4" style="font-size: 2.8rem; font-weight: 800; background: linear-gradient(135deg, #6366f1, #4f46e5); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1.2;">
                        Essai sans engagement de 30 jours
                    </h2>
                    
                    <!-- Sous-titre avec accents colorés -->
                    <h3 class="mb-4 fw-bolder" style="line-height: 1.5; font-size: 1.4rem;">
                        Découvrez une nouvelle façon de gérer votre école: 
                        <span style="color: #6366f1; font-weight: 700;">plus simple</span>, 
                        <span style="color: #6366f1; font-weight: 700;">plus rapide</span> et 
                        <span style="color: #6366f1; font-weight: 700;">plus efficace</span>.
                    </h3>
                    
                    <p class="mb-5" style="font-size: 1.1rem; color: #64748b;">Profitez de votre essai gratuit pour explorer toutes les fonctionnalités!</p>
                    
                    <!-- Caractéristiques avec animation et effet de survol -->
                    <div class="mb-5">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="feature-hover-item" style="display: flex; align-items: center; padding: 16px; border-radius: 12px; background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05); transform: translateY(0); transition: all 0.3s ease;">
                                    <i class="fas fa-check-circle fa-lg me-3" style="color: #6366f1;"></i>
                                    <span style="font-weight: 500;">Accès complet à toutes les fonctionnalités</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="feature-hover-item" style="display: flex; align-items: center; padding: 16px; border-radius: 12px; background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05); transform: translateY(0); transition: all 0.3s ease;">
                                    <i class="fas fa-check-circle fa-lg me-3" style="color: #6366f1;"></i>
                                    <span style="font-weight: 500;">Formation en ligne gratuite</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="feature-hover-item" style="display: flex; align-items: center; padding: 16px; border-radius: 12px; background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05); transform: translateY(0); transition: all 0.3s ease;">
                                    <i class="fas fa-check-circle fa-lg me-3" style="color: #6366f1;"></i>
                                    <span style="font-weight: 500;">Support technique dédié</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="feature-hover-item" style="display: flex; align-items: center; padding: 16px; border-radius: 12px; background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05); transform: translateY(0); transition: all 0.3s ease;">
                                    <i class="fas fa-check-circle fa-lg me-3" style="color: #6366f1;"></i>
                                    <span style="font-weight: 500;">Aucune carte de crédit requise</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Boutons d'action avec animation et ombre -->
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#" class="action-button-primary" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; border: none; padding: 16px 30px; border-radius: 50px; font-weight: 600; letter-spacing: 0.5px; display: inline-flex; align-items: center; box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4); transform: translateY(0); transition: all 0.4s ease; position: relative; overflow: hidden; text-decoration: none;">
                            <span>Commencer mon essai gratuit</span>
                            <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        
                        <a href="#contact" class="action-button-secondary" style="background: transparent; color: #6366f1; border: 2px solid #6366f1; padding: 16px 30px; border-radius: 50px; font-weight: 600; letter-spacing: 0.5px; display: inline-flex; align-items: center; transform: translateY(0); transition: all 0.4s ease; text-decoration: none;">
                            <span>Demander une démo</span>
                            <i class="fas fa-headset ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Animation CSS et JS -->
<style>
@keyframes float {
    0% { transform: translate(0, 0); }
    50% { transform: translate(0, -15px); }
    100% { transform: translate(0, 0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation au survol pour les badges et boutons
    const premiumBadge = document.querySelector('.premium-badge');
    if (premiumBadge) {
        premiumBadge.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 15px 25px -10px rgba(99, 102, 241, 0.6)';
        });
        premiumBadge.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 10px 20px -10px rgba(99, 102, 241, 0.5)';
        });
    }
    
    // Animation au survol pour les éléments de fonctionnalités
    const featureItems = document.querySelectorAll('.feature-hover-item');
    featureItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1)';
            this.style.backgroundColor = 'white';
        });
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 15px -3px rgba(0, 0, 0, 0.05)';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
        });
    });
    
    // Animation au survol pour les boutons d'action
    const primaryButton = document.querySelector('.action-button-primary');
    if (primaryButton) {
        primaryButton.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 15px 30px -5px rgba(99, 102, 241, 0.5)';
        });
        primaryButton.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 10px 20px -5px rgba(99, 102, 241, 0.4)';
        });
    }
    
    const secondaryButton = document.querySelector('.action-button-secondary');
    if (secondaryButton) {
        secondaryButton.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.backgroundColor = 'rgba(99, 102, 241, 0.1)';
        });
        secondaryButton.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.backgroundColor = 'transparent';
        });
    }
});
</script> 

        <!-- Contact Section -->
        <section id="contact" class="contact py-5">
            <div class="container py-5">
                <div class="section-title" data-aos="fade-up" data-aos-duration="1000">
                    <h2>Prêt à discuter de votre projet?</h2>
                    <p>Notre équipe d'experts est prête à vous aider à concrétiser votre vision digitale. Contactez-nous pour discuter de votre projet et découvrir comment nous pouvons transformer vos idées en réalité.</p>
                </div>

                <div class="row g-5">
                    <!-- Contact Information -->
                    <div class="col-lg-5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                        <div class="contact-info">
                            <div class="contact-card mb-4">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <h5>Notre adresse</h5>
                                    <p>Netzify Coworking, Rue L158, Angré, Abidjan</p>
                                </div>
                            </div>

                            <div class="contact-card mb-4">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-text">
                                    <h5>Email</h5>
                                    <p>contact@africandigitconsulting.com</p>
                                </div>
                            </div>

                            <div class="contact-card mb-4">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <h5>Téléphone</h5>
                                    <p>+225 27 32 797 538</p>
                                    <p>+225 05 95 459 843</p>
                                </div>
                            </div>

                            <div class="contact-card">
                                <div class="contact-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-text">
                                    <h5>Horaires d'ouverture</h5>
                                    <p>Lun - Ven: 8h30 - 17h30</p>
                                    <p>Sam: Sur rendez-vous</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="col-lg-7" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                        <div class="contact-form-wrapper">
                            <form id="contactForm" class="contact-form">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="name" placeholder="Votre nom" required>
                                            <label for="name">Nom complet *</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="email" class="form-control" id="email" placeholder="Votre email" required>
                                            <label for="email">Email *</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="tel" class="form-control" id="phone" placeholder="+225 XX XX XX XX XX">
                                            <label for="phone">Téléphone</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="company" placeholder="Votre entreprise">
                                            <label for="company">Entreprise</label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating mb-3">
                                            <select class="form-select" id="service" aria-label="Service intéressé">
                                                <option selected disabled>Développement Web</option>
                                                <option value="gestion-evaluations">Gestion des évaluations</option>
                                                <option value="emplois-du-temps">Emplois du temps intelligents</option>
                                                <option value="gestion-salaires">Gestion des salaires des vacataires</option>
                                                <option value="suivi-presence">Suivi des présences et absences</option>
                                                <option value="portail-parents">Portail parental personnalisé</option>
                                                <option value="gestion-comptable">Gestion comptable intégrée</option>
                                                <option value="autre">Autre service KLASCCI</option>
                                            </select>
                                            <label for="service">Service désiré *</label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="message" placeholder="Votre message" style="height: 150px" required></textarea>
                                            <label for="message">Message *</label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="privacyPolicy" required>
                                            <label class="form-check-label" for="privacyPolicy">
                                                J'accepte que mes données soient traitées conformément à la <a href="#">politique de confidentialité</a>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">Envoyer le message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta py-5 text-white text-center position-relative overflow-hidden">
            <div class="cta-bg position-absolute w-100 h-100 top-0 start-0" style="background: var(--primary-gradient); z-index: -2;"></div>
            <div class="cta-shape position-absolute w-100 bottom-0 start-0" style="z-index: -1;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="#ffffff" fill-opacity="1" d="M0,288L48,272C96,256,192,224,288,197.3C384,171,480,149,576,165.3C672,181,768,235,864,234.7C960,235,1056,181,1152,176C1248,171,1344,213,1392,234.7L1440,256L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
            </div>
            <div class="container py-5 position-relative">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="cta-icon mb-4 mx-auto">
                            <i class="fas fa-rocket fa-3x"></i>
                        </div>
                        <h2 class="mb-4">Prêt à transformer votre établissement ?</h2>
                        <p class="mb-5">Rejoignez les établissements qui ont déjà optimisé leur gestion administrative et pédagogique avec KLASSCI</p>
                        <div class="cta-buttons d-flex flex-wrap justify-content-center gap-3">
                            <a href="#" class="btn btn-light btn-lg rounded-pill shadow-sm">Commencer gratuitement</a>
                            <a href="#" class="btn btn-outline-light btn-lg rounded-pill">Contactez-nous</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 mb-5 mb-lg-0">
                        <div class="footer-logo">
                            <img src="{{ asset('icons/klassci.jpeg') }}" alt="KLASSCI Logo">
                        </div>
                        <p>KLASSCI vous offre des solutions de gestion innovantes pour optimiser vos processus éducatifs et maximiser votre productivité.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-5 mb-md-0">
                        <h5>Société</h5>
                        <ul class="footer-links">
                            <li><a href="#">À propos</a></li>
                            <li><a href="#">Équipe</a></li>
                            <li><a href="#">Carrières</a></li>
                            <li><a href="#">Blog</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-5 mb-md-0">
                        <h5>Produit</h5>
                        <ul class="footer-links">
                            <li><a href="#">Fonctionnalités</a></li>
                            <li><a href="#">Tarifs</a></li>
                            <li><a href="#">Témoignages</a></li>
                            <li><a href="#">FAQ</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <h5>Contact</h5>
                        <ul class="footer-links">
                            <li><i class="fas fa-map-marker-alt me-2"></i> Netzify Coworking, Rue L158, Angré, Abidjan</li>
                            <li><i class="fas fa-phone me-2"></i> +225 27 32 797 538</li>
                            <li><i class="fas fa-envelope me-2"></i> contact@africandigitconsulting.com</li>
                        </ul>
                    </div>
                </div>

                <div class="copyright">
                    <p>&copy; 2023 KLASSCI. Tous droits réservés.</p>
                </div>
            </div>
        </footer>

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialiser AOS (Animation On Scroll)
                AOS.init({
                    duration: 800,
                    easing: 'ease-in-out',
                    once: true
                });

                // Changement de style de la navbar au défilement
                const navbar = document.querySelector('.navbar');
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 50) {
                        navbar.classList.add('navbar-scrolled');
                    } else {
                        navbar.classList.remove('navbar-scrolled');
                    }
                });

                // Déclencher l'événement de défilement pour les rechargements de page
                window.dispatchEvent(new Event('scroll'));
                
                // Fermer la navbar mobile en cliquant sur un lien
                const navLinks = document.querySelectorAll('.nav-link');
                const menuToggle = document.getElementById('navbarNav');
                if (menuToggle) {
                    const bsCollapse = new bootstrap.Collapse(menuToggle, {toggle: false});
                    
                    navLinks.forEach(function(link) {
                        link.addEventListener('click', function() {
                            if (menuToggle.classList.contains('show')) {
                                bsCollapse.toggle();
                            }
                        });
                    });
                }
                
                // Particles pour l'arrière-plan du hero
                const heroParticles = document.getElementById('hero-particles');
                if (heroParticles) {
                    const canvas = document.createElement('canvas');
                    heroParticles.appendChild(canvas);
                    
                    const ctx = canvas.getContext('2d');
                    let width = heroParticles.offsetWidth;
                    let height = heroParticles.offsetHeight;
                    canvas.width = width;
                    canvas.height = height;
                    
                    const particles = [];
                    const particleCount = width > 768 ? 40 : 20;
                    
                    class Particle {
                        constructor() {
                            this.x = Math.random() * width;
                            this.y = Math.random() * height;
                            this.size = Math.random() * 2 + 1;
                            this.speedX = Math.random() * 1 - 0.5;
                            this.speedY = Math.random() * 1 - 0.5;
                            this.opacity = Math.random() * 0.5 + 0.1;
                        }
                        
                        update() {
                            this.x += this.speedX;
                            this.y += this.speedY;
                            
                            if (this.x > width || this.x < 0) {
                                this.speedX = -this.speedX;
                            }
                            
                            if (this.y > height || this.y < 0) {
                                this.speedY = -this.speedY;
                            }
                        }
                        
                        draw() {
                            ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
                            ctx.beginPath();
                            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                            ctx.fill();
                        }
                    }
                    
                    function init() {
                        for (let i = 0; i < particleCount; i++) {
                            particles.push(new Particle());
                        }
                    }
                    
                    function animate() {
                        ctx.clearRect(0, 0, width, height);
                        
                        for (let i = 0; i < particles.length; i++) {
                            particles[i].update();
                            particles[i].draw();
                            
                            for (let j = i; j < particles.length; j++) {
                                const dx = particles[i].x - particles[j].x;
                                const dy = particles[i].y - particles[j].y;
                                const distance = Math.sqrt(dx * dx + dy * dy);
                                
                                if (distance < 100) {
                                    ctx.beginPath();
                                    ctx.strokeStyle = `rgba(255, 255, 255, ${0.1 * (1 - distance/100)})`;
                                    ctx.lineWidth = 0.3;
                                    ctx.moveTo(particles[i].x, particles[i].y);
                                    ctx.lineTo(particles[j].x, particles[j].y);
                                    ctx.stroke();
                                }
                            }
                        }
                        
                        requestAnimationFrame(animate);
                    }
                    
                    // Initialiser les particules
                    init();
                    animate();
                    
                    // Gestion du redimensionnement
                    window.addEventListener('resize', function() {
                        width = heroParticles.offsetWidth;
                        height = heroParticles.offsetHeight;
                        canvas.width = width;
                        canvas.height = height;
                        
                        particles.length = 0;
                        init();
                    });
                }
                
                // Vérification et génération des images thématiques si nécessaire
                if (!localStorage.getItem('imagesGenerated')) {
                    // Appeler le script PHP de génération d'images thématiques
                    fetch('{{ url("scripts/generate-thematic-images.php") }}')
                        .then(response => {
                            console.log('Images thématiques générées avec succès');
                            localStorage.setItem('imagesGenerated', 'true');
                        })
                        .catch(error => {
                            console.error('Erreur lors de la génération des images:', error);
                        });
                }
            });
        </script>
    <script>
// Script pour augmenter la taille des logos
document.addEventListener('DOMContentLoaded', function() {
  // Modification du logo dans la navbar
  const navbarLogo = document.querySelector('.navbar-brand img');
  if (navbarLogo) {
    navbarLogo.style.height = '75px';
    navbarLogo.style.transition = 'all 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
    navbarLogo.style.transformOrigin = 'left center';
  }

  // Mise à jour de la taille lors du défilement
  const updateScrolledLogo = () => {
    const scrolled = document.querySelector('.navbar-scrolled img');
    if (scrolled) {
      scrolled.style.height = '65px';
    }
  };

  // Vérifier si la navbar est déjà scrolled au chargement
  if (document.querySelector('.navbar-scrolled')) {
    updateScrolledLogo();
  }

  // Ajouter un écouteur d'événement pour le défilement
  window.addEventListener('scroll', updateScrolledLogo);

  // Modification du logo dans le footer
  const footerLogo = document.querySelector('.footer-logo img');
  if (footerLogo) {
    footerLogo.style.height = '65px';
    footerLogo.style.transition = 'transform 0.3s ease';
    
    // Ajouter un effet hover
    footerLogo.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.05)';
    });
    
    footerLogo.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  }
}); 
</script>
</body>
</html>