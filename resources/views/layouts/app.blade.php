<!--
    Layout principal de l'application KLASSCI

    Ce fichier a été modifié pour :
    1. Corriger les routes non définies (erreurs 'Route [xxx] not defined')
    2. Organiser la barre latérale en fonction des rôles (superadmin, secretaire, enseignant, etudiant, parent)
    3. Regrouper les fonctionnalités par catégories logiques
    4. Ajouter le logo KLASSCI

    Toutes les routes ont été alignées avec les contrôleurs existants.

    Dernière mise à jour : 02/03/2025
-->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'KLASSCI')</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/nextadmin.css') }}" rel="stylesheet">
    <link href="{{ asset('css/navbar-enhancements.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sidebar-fixes.css') }}" rel="stylesheet">

    <!-- Styles supplémentaires -->
    <style>
        /* Amélioration de la visibilité des éléments de la navbar */
        .navbar-user-name,
        .dropdown-user-name,
        .dropdown-user-email,
        .notification-title,
        .notification-text,
        .message-title,
        .message-text,
        .quick-action-text {
            color: var(--nextadmin-gray-700) !important;
        }

        .navbar-title {
            display: flex;
            align-items: center;
        }

        /* S'assurer que les icônes sont bien visibles */
        .navbar-icon i,
        .menu-icon i {
            color: var(--nextadmin-gray-700);
        }

        /* Améliorer le contraste dans les dropdowns */
        .dropdown-item {
            color: var(--nextadmin-gray-700) !important;
        }

        .dropdown-header {
            color: var(--nextadmin-gray-900) !important;
            font-weight: 600;
        }

        /* ===== AMÉLIORATION DU SEARCH INPUT ===== */
        .navbar-search {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .navbar-search input {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(99, 102, 241, 0.1);
            border-radius: 25px;
            padding: 12px 20px 12px 45px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .navbar-search input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.15);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-1px);
        }

        .navbar-search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            z-index: 10;
            transition: color 0.3s ease;
        }

        .navbar-search input:focus + .navbar-search-icon,
        .navbar-search:hover .navbar-search-icon {
            color: #6366f1;
        }

        /* Résultats de recherche */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 5px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.1);
        }

        .search-results.show {
            animation: searchSlideIn 0.3s ease-out;
        }

        @keyframes searchSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .search-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .search-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
            transform: translateX(5px);
        }

        .search-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
        }

        .search-category {
            padding: 8px 16px;
            font-weight: 600;
            color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== AMÉLIORATION DES ACTIONS RAPIDES ===== */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            padding: 12px;
            min-width: 280px;
        }

        .quick-action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 12px;
            text-decoration: none;
            color: inherit;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .quick-action-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
            color: #6366f1;
        }

        .quick-action-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .quick-action-item:hover .quick-action-icon {
            transform: scale(1.1);
        }

        .quick-action-text {
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            line-height: 1.3;
        }

        /* ===== AMÉLIORATION DE LA SIDEBAR ===== */
        .nextadmin-sidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(99, 102, 241, 0.1);
        }

        /* Scrollbar personnalisée pour la sidebar */
        .sidebar-menu {
            overflow-y: auto;
            overflow-x: hidden;
            max-height: calc(100vh - 80px);
            padding-right: 5px;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-menu::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        /* Firefox scrollbar */
        .sidebar-menu {
            scrollbar-width: thin;
            scrollbar-color: #6366f1 rgba(0, 0, 0, 0.05);
        }

        /* ===== EFFETS RESPONSIVE ===== */
        @media (max-width: 991.98px) {
            .nextadmin-sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 1050;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .nextadmin-sidebar.show {
                left: 0;
                box-shadow: 0 0 50px rgba(0, 0, 0, 0.3);
            }

            .nextadmin-sidebar.collapsed {
                left: -280px;
            }

            /* Overlay pour mobile */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .nextadmin-main {
                margin-left: 0;
            }

            /* Ajustement de la navbar search sur mobile */
            .navbar-search {
                max-width: 200px;
            }

            .navbar-search input {
                padding: 10px 15px 10px 35px;
                font-size: 13px;
            }
        }

        @media (max-width: 767.98px) {
            .navbar-center {
                display: none !important;
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
                min-width: 200px;
            }
        }

        /* ===== ANIMATIONS D'ENTRÉE ===== */
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .nextadmin-sidebar.show {
            animation: slideInLeft 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ===== AMÉLIORATION DU TOGGLE BUTTON ===== */
        .navbar-toggle {
            background: none;
            border: none;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
            color: #6b7280;
        }

        .navbar-toggle:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            color: #6366f1;
            transform: scale(1.05);
        }

        .navbar-toggle:active {
            transform: scale(0.95);
        }

        /* ===== AMÉLIORATION DES DROPDOWNS ===== */
        .custom-dropdown {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border-radius: 15px;
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            animation: dropdownSlideIn 0.3s ease-out;
        }

        @keyframes dropdownSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .custom-dropdown .dropdown-item {
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.2s ease;
        }

        .custom-dropdown .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            transform: translateX(5px);
        }

        /* ===== BADGES ET NOTIFICATIONS ===== */
        .navbar-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="nextadmin-wrapper">
        <!-- Sidebar -->
        <aside class="nextadmin-sidebar" id="sidebar">
        <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon"><img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="Logo KLASSCI" style="width: 30px; height: auto;"></div>
                    <div class="sidebar-logo-text">KLASSCI</div>
                </div>
        </div>

        <div class="sidebar-menu">
                @if(auth()->check())
                    <!-- Dashboard - Common for all roles -->
                        <div class="menu-category">Tableau de bord</div>
                        <div class="menu-item">
                            <a href="{{ route('dashboard') }}" class="menu-link {{ Request::routeIs('dashboard') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-home"></i></div>
                                <div class="menu-text">Accueil</div>
                            </a>
                    </div>

                    <!-- Academic Management Section -->
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <div class="menu-category">Gestion académique</div>

                        <!-- Programs & Classes -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.filieres.*') || Request::routeIs('esbtp.classes.*') || Request::routeIs('esbtp.niveaux-etudes.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-school"></i></div>
                                <div class="menu-text">Filières & Classes</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.filieres.*') || Request::routeIs('esbtp.classes.*') || Request::routeIs('esbtp.niveaux-etudes.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.filieres.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.filieres.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Filières</span>
                                </a>
                                <a href="{{ route('esbtp.classes.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.classes.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Classes</span>
                                </a>
                                <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.niveaux-etudes.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Niveaux d'études</span>
                                </a>
                                <a href="{{ route('esbtp.annees-universitaires.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.annees-universitaires.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Années universitaires</span>
                                </a>
                            </div>
                        </div>

                        <!-- Training Cycles -->
                        <!--<div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.cycles.*') || Request::routeIs('esbtp.specialties.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-sync-alt"></i></div>
                                <div class="menu-text">Formation</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.cycles.*') || Request::routeIs('esbtp.specialties.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.cycles.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.cycles.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Cycles de Formation</span>
                                </a>
                                <a href="{{ route('esbtp.specialties.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.specialties.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Spécialités</span>
                                </a>
                                <a href="{{ route('esbtp.continuing-education.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.continuing-education.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Formation Continue</span>
                                </a>
                            </div>
                        </div>-->
                    @endif

                    <!-- Students Section -->
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <div class="menu-category">Étudiants</div>

                        <!-- Student Management -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.etudiants.*') || Request::routeIs('esbtp.inscriptions.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-user-graduate"></i></div>
                                <div class="menu-text">Étudiants</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.etudiants.*') || Request::routeIs('esbtp.inscriptions.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.etudiants.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.etudiants.*') ? 'active' : '' }}">
                                    <i class="fas fa-list"></i>
                                    <span>Liste des Étudiants</span>
                                </a>
                                <a href="{{ route('esbtp.inscriptions.create') }}" class="menu-sublink {{ Request::routeIs('esbtp.inscriptions.create') ? 'active' : '' }}">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Nouvelle Inscription</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Teaching Section -->
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <div class="menu-category">Enseignement</div>

                        <!-- Schedule Management -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.emploi-temps.index') }}" class="menu-link {{ Request::routeIs('esbtp.emploi-temps.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div class="menu-text">Emplois du temps</div>
                            </a>
                        </div>

                        <!-- Teacher Management -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.teachers.*') || Request::routeIs('esbtp.teacher-attendance.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <div class="menu-text">Enseignants</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.teachers.*') || Request::routeIs('esbtp.teacher-attendance.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.teachers.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.teachers.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Liste des enseignants</span>
                                </a>
                                <!--<a href="{{ route('esbtp.teacher-attendance.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.teacher-attendance.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Présence enseignants</span>
                                </a>-->
                            </div>
                        </div>
                    @endif

                    <!-- Grades & Reports Section -->
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <div class="menu-category">Notes & Rapports</div>

                        <!-- Grades Management -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.notes.*') || Request::routeIs('esbtp.bulletins.*') || Request::routeIs('esbtp.resultats.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-file-alt"></i></div>
                                <div class="menu-text">Notes & Bulletins</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.notes.*') || Request::routeIs('esbtp.bulletins.*') || Request::routeIs('esbtp.resultats.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.notes.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.notes.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Gestion des notes</span>
                                </a>
                                <a href="{{ route('esbtp.resultats.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.resultats.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Résultats & Classements</span>
                                </a>
                            </div>
                        </div>

                        <!-- Exams & Evaluations -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.evaluations.index') }}" class="menu-link {{ Request::routeIs('esbtp.evaluations.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                                <div class="menu-text">Examens & Évaluations</div>
                            </a>
                        </div>
                    @endif

                    <!-- Administration Section -->
                    @if(auth()->user()->hasRole('superAdmin'))
                        <div class="menu-category">Administration</div>

                        <!-- Staff Management -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.staff.*') || Request::routeIs('esbtp.roles.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-users-cog"></i></div>
                                <div class="menu-text">Personnel</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.staff.*') || Request::routeIs('esbtp.roles.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.secretaires.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.secretaires.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Gestion du personnel</span>
                                </a>
                                <!--<a href="{{ route('esbtp.roles.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.roles.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Rôles & Permissions</span>
                                </a>-->
                            </div>
                        </div>

                        <!-- Partnerships -->
                        <!--<div class="menu-item">
                            <a href="{{ route('esbtp.partnerships.index') }}" class="menu-link {{ Request::routeIs('esbtp.partnerships.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-handshake"></i></div>
                                <div class="menu-text">Partenariats</div>
                            </a>
                        </div>-->
                    @endif

                    <!-- Attendance Section -->
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <div class="menu-category">Présence & Absences</div>

                        <!-- Gestion des présences/absences -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.attendances.*') || Request::routeIs('esbtp.absences.*') || Request::routeIs('esbtp.teacher-attendance.*') || Request::routeIs('esbtp.attendance-codes.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="menu-text">Gestion des présences</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.attendances.*') || Request::routeIs('esbtp.absences.*') || Request::routeIs('esbtp.teacher-attendance.*') || Request::routeIs('esbtp.attendance-codes.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.attendances.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.attendances.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Présences étudiants</span>
                                </a>
                                <a href="{{ route('esbtp.attendances.rapport-form') }}" class="menu-sublink {{ Request::routeIs('esbtp.attendances.rapport*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Rapports de présence</span>
                                </a>
                                <a href="{{ route('esbtp.teacher-attendance.history') }}" class="menu-sublink {{ Request::routeIs('esbtp.teacher-attendance.history') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Historique émargement enseignant</span>
                                </a>
                                <a href="{{ route('esbtp.attendance-codes.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.attendance-codes.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Codes d'émargement</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->hasRole('teacher'))
                        <div class="menu-category">Présence</div>

                        <!-- Émargement enseignant -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.attendance.mark') }}" class="menu-link {{ Request::routeIs('esbtp.attendance.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                                <div class="menu-text">Faire les émargements</div>
                            </a>
                        </div>
                    @endif

                    <!-- Messages Section -->
                    @if(auth()->user()->hasRole('etudiant'))
                        <div class="menu-category">Communication</div>

                        <!-- Messages -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.mes-messages.index') }}" class="menu-link {{ Request::routeIs('esbtp.mes-messages.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-envelope"></i></div>
                                <div class="menu-text">Messages</div>
                            </a>
                        </div>

                        <!-- Notifications -->
                        <div class="menu-item">
                            @if(auth()->user()->hasRole('etudiant'))
                                <a href="{{ route('esbtp.mes-notifications.index') }}" class="menu-link {{ Request::routeIs('esbtp.mes-notifications.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-bell"></i></div>
                                    <div class="menu-text">Mes notifications</div>
                                </a>
                            @else
                                <a href="{{ route('notifications.index') }}" class="menu-link {{ Request::routeIs('notifications.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-bell"></i></div>
                                    <div class="menu-text">Notifications</div>
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- Accounting Section -->
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <div class="menu-category">Gestion financière</div>
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.fee-categories.*') || Request::routeIs('esbtp.payment-categories.*') || Request::routeIs('esbtp.fees.*') || Request::routeIs('esbtp.payments.*') || Request::routeIs('esbtp.comptabilite.dashboard') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-coins"></i></div>
                                <div class="menu-text">Comptabilité</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.fee-categories.*') || Request::routeIs('esbtp.payment-categories.*') || Request::routeIs('esbtp.fees.*') || Request::routeIs('esbtp.payments.*') || Request::routeIs('esbtp.comptabilite.dashboard') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.dashboard') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <i class="fas fa-chart-bar me-1"></i>
                                    <span>Dashboard Comptable</span>
                                </a>
                                <a href="{{ route('esbtp.fee-categories.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.fee-categories.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Catégories de frais</span>
                                </a>
                                <a href="{{ route('esbtp.payment-categories.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.payment-categories.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Catégories de paiements</span>
                                </a>
                                <a href="{{ route('esbtp.fees.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.fees.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Frais</span>
                                </a>
                                <a href="{{ route('esbtp.payments.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.payments.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Paiements</span>
                                </a>
                                <a href="{{ route('esbtp.comptabilite.factures') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.factures') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <i class="fas fa-file-invoice me-1"></i>
                                    <span>Factures</span>
                                </a>
                                <a href="{{ route('esbtp.comptabilite.depenses') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.depenses') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <i class="fas fa-money-bill me-1"></i>
                                    <span>Dépenses</span>
                                </a>
                                <a href="{{ route('esbtp.comptabilite.bourses') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.bourses') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <i class="fas fa-piggy-bank me-1"></i>
                                    <span>Bourses</span>
                                </a>
                                <a href="{{ route('esbtp.comptabilite.salaires') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.salaires') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <i class="fas fa-user-tie me-1"></i>
                                    <span>Salaires</span>
                                </a>
                                <a href="{{ route('esbtp.comptabilite.fournisseurs') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.fournisseurs') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <i class="fas fa-truck me-1"></i>
                                    <span>Fournisseurs</span>
                                </a>
                                <a href="{{ route('esbtp.comptabilite.rapports') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.rapports') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <i class="fas fa-chart-line me-1"></i>
                                    <span>Rapports</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Announcements Section -->
                    @canany(['superAdmin', 'secretaire'])
                    <div class="menu-category">Annonces</div>

                    <!-- Announcements Management -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.annonces.index') }}" class="menu-link {{ Request::routeIs('esbtp.annonces.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-bullhorn"></i></div>
                                <div class="menu-text">Annonces</div>
                            </a>
                        </div>
                        @endcan

                    <!-- System Section - SuperAdmin Only -->
                    @if(auth()->user()->hasRole('superAdmin'))
                        <div class="menu-category">Système</div>

                        <!-- System Settings -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.settings.*') || Request::routeIs('esbtp.logs.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-cogs"></i></div>
                                <div class="menu-text">Paramètres</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.settings.*') || Request::routeIs('esbtp.logs.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.settings.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.settings.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Configuration</span>
                                </a>
                                <a href="{{ route('esbtp.logs.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.logs.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Journaux système</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    @canany(['superAdmin', 'secretaire'])
                    <li class="nav-item">
                        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="nav-link {{ request()->routeIs('esbtp.comptabilite.dashboard') ? 'active' : '' }}">
                            <i class="fas fa-chart-bar me-2"></i>
                            <span>Comptabilité</span>
                        </a>
                    </li>
                    @endcanany

                    @role('etudiant')
                    <div class="menu-category">Mon espace étudiant</div>

                    <div class="menu-item">
                        <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="menu-link {{ request()->routeIs('esbtp.mon-emploi-temps.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="menu-text">Mon emploi du temps</div>
                        </a>
                    </div>

                    <div class="menu-item">
                        <a href="{{ route('esbtp.mes-evaluations.index') }}" class="menu-link {{ request()->routeIs('esbtp.mes-evaluations.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                            <div class="menu-text">Mes évaluations</div>
                        </a>
                    </div>

                    <div class="menu-item">
                        <a href="{{ route('esbtp.mes-notes.index') }}" class="menu-link {{ request()->routeIs('esbtp.mes-notes.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-star"></i></div>
                            <div class="menu-text">Mes notes</div>
                        </a>
                    </div>

                    <div class="menu-item">
                        <a href="{{ route('esbtp.mes-absences.index') }}" class="menu-link {{ request()->routeIs('esbtp.mes-absences.*') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="menu-text">Mes absences</div>
                        </a>
                    </div>

                    <div class="menu-item">
                        <a href="{{ route('esbtp.mon-bulletin.index') }}" class="menu-link {{ request()->routeIs('esbtp.mon-bulletin.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-file-invoice"></i></div>
                            <div class="menu-text">Mes résultats et bulletin</div>
                        </a>
                    </div>
                    @endrole

                    <!-- Section profil utilisateur -->
                <div class="menu-category">Mon compte</div>

                    @role('etudiant')
                    <div class="menu-item">
                        <a href="{{ route('esbtp.mon-profil.index') }}" class="menu-link {{ request()->routeIs('esbtp.mon-profil.*') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-user-circle"></i></div>
                            <div class="menu-text">Profil</div>
                        </a>
                    </div>
                    @else
                    <div class="menu-item">
                        <a href="{{ route('admin.profile') }}" class="menu-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-user-circle"></i></div>
                            <div class="menu-text">Profil</div>
                        </a>
                    </div>
                    @endrole

                @endif
            </div>
        </aside>

        <!-- Main Content -->
        <main class="nextadmin-main">
            <!-- Overlay pour mobile -->
            <div class="sidebar-overlay" id="sidebar-overlay"></div>

            <!-- Navbar -->
            <nav class="nextadmin-navbar">
                <div class="navbar-content">
                    <div class="navbar-left">
                        <button class="navbar-toggle" id="sidebar-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="navbar-title d-none d-md-block">
                            <span class="ms-2 fw-bold">KLASSCI</span>
                        </div>
                    </div>

                    <div class="navbar-center d-none d-lg-block">
                        <div class="navbar-search">
                            <div class="navbar-search-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <input type="text" id="global-search" placeholder="Rechercher..." class="form-control" autocomplete="off">
                            <div id="search-results" class="search-results" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="navbar-right">
                        <!-- Notifications -->
                        <div class="dropdown">
                            <button class="navbar-icon" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="navbar-badge" id="notifications-count" style="display: none;">0</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="notificationsDropdown">
                                <li>
                                    <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                        Notifications
                                        <button class="btn btn-sm btn-link text-primary p-0" id="mark-all-notifications-read" style="font-size: 0.75rem;">
                                            Tout marquer comme lu
                                        </button>
                                    </h6>
                </li>
                                <li><hr class="dropdown-divider"></li>
                                <div id="notifications-list">
                                    <li class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                </li>
                                        </div>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-center view-all" href="{{ route('notifications.index') }}">
                                        Voir toutes les notifications
                    </a>
                </li>
                            </ul>
                        </div>

                        <!-- Messages -->
                        <div class="dropdown">
                            <button class="navbar-icon" type="button" id="messagesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-envelope"></i>
                                <span class="navbar-badge" id="messages-count" style="display: none;">0</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="messagesDropdown">
                                <li>
                                    <h6 class="dropdown-header">Messages</h6>
                </li>
                                <li><hr class="dropdown-divider"></li>
                                <div id="messages-list">
                                    <li class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                            </div>
                </li>
                                            </div>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-center view-all" href="{{ route('esbtp.mes-messages.index') }}">
                                        Voir tous les messages
                                    </a>
                </li>
            </ul>
    </div>

                    <!-- Quick Actions -->
                    <div class="dropdown">
                            <button class="navbar-icon" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-th-large"></i>
                        </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="quickActionsDropdown">
                                <li>
                                    <h6 class="dropdown-header">Actions rapides</h6>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <div class="quick-actions-grid" id="quick-actions-list">
                                        <div class="text-center py-3">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="visually-hidden">Chargement...</span>
                            </div>
                                </div>
                        </div>
                                </li>
                            </ul>
                </div>

                <!-- User Profile -->
                <div class="dropdown ms-2">
                    <div class="navbar-user" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="navbar-avatar">
                            @if(auth()->check() && auth()->user()->profile_photo_path)
                                <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}">
                        @else
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                        @endif
                        </div>
                        <div class="navbar-user-info d-none d-md-block">
                            <div class="navbar-user-name">{{ auth()->check() ? auth()->user()->name : 'Invité' }}</div>
                    </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="profileDropdown">
                        <li>
                            <div class="dropdown-user-details">
                                <div class="dropdown-user-avatar">
                                    @if(auth()->check() && auth()->user()->profile_photo_path)
                                        <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}">
                            @else
                                        <div class="user-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="dropdown-user-info">
                                    <div class="dropdown-user-name">{{ auth()->check() ? auth()->user()->name : 'Invité' }}</div>
                                    <div class="dropdown-user-email">{{ auth()->check() ? auth()->user()->email : '' }}</div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        @if(auth()->check())
                            <li>
                                        @role('etudiant')
                                            <a class="dropdown-item" href="{{ route('esbtp.mon-profil.index') }}">
                                    <i class="fas fa-user-circle me-2"></i> Mon profil
                                </a>
                                        @else
                                            <a class="dropdown-item" href="{{ route('admin.profile') }}">
                                                <i class="fas fa-user-circle me-2"></i> Mon profil
                                            </a>
                                        @endrole
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('settings.index') }}">
                                    <i class="fas fa-cog me-2"></i> Paramètres
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                @csrf
                                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                    </a>
                            </form>
                        </li>
                        @else
                            <li>
                                <a class="dropdown-item" href="{{ route('login') }}">
                                    <i class="fas fa-sign-in-alt me-2"></i> Connexion
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
                </div>
            </nav>

            <!-- Content -->
            <div class="nextadmin-content">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

            @yield('content')
        </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="{{ asset('js/navbar-diagnostics.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Initialisation de l\'application...');

            // 1. Initialiser Bootstrap dropdowns
            console.log('🔽 Initialisation des dropdowns Bootstrap...');
            const dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            const dropdownList = [...dropdownElementList].map(dropdownToggleEl => {
                try {
                    return new bootstrap.Dropdown(dropdownToggleEl);
                } catch (error) {
                    console.error('Erreur initialisation dropdown:', error);
                    return null;
                }
            });
            console.log(`✅ ${dropdownList.filter(d => d !== null).length} dropdowns initialisés`);

            // 2. Gestion du toggle sidebar
            console.log('🍔 Configuration du toggle sidebar...');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (sidebarToggle && sidebar) {
                // Supprimer les anciens event listeners
                sidebarToggle.replaceWith(sidebarToggle.cloneNode(true));
                const newSidebarToggle = document.getElementById('sidebar-toggle');

                newSidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🍔 Toggle sidebar cliqué');

                        sidebar.classList.toggle('show');
                    if (overlay) {
                        overlay.classList.toggle('show');
                    }

                    // Ajouter classe au body pour éviter le scroll
                    document.body.classList.toggle('sidebar-open');
                });

                // Fermer sidebar en cliquant sur overlay
                if (overlay) {
                    overlay.addEventListener('click', function() {
                        console.log('📱 Overlay cliqué - fermeture sidebar');
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                        document.body.classList.remove('sidebar-open');
                    });
                }

                console.log('✅ Toggle sidebar configuré');
            } else {
                console.error('❌ Éléments sidebar non trouvés');
            }

            // 3. Gestion des accordéons sidebar
            console.log('🎵 Configuration des accordéons sidebar...');
            const accordionButtons = document.querySelectorAll('.menu-accordion-btn');

            accordionButtons.forEach((button, index) => {
                // Supprimer les anciens event listeners
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);

                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log(`🎵 Accordéon ${index + 1} cliqué`);

                    const content = this.nextElementSibling;
                    const isActive = this.classList.contains('active');

                    // Fermer tous les autres accordéons
                    accordionButtons.forEach(btn => {
                        if (btn !== this) {
                            btn.classList.remove('active');
                            const otherContent = btn.nextElementSibling;
                            if (otherContent) {
                                otherContent.classList.remove('show');
                            }
                        }
                    });

                    // Toggle l'accordéon actuel
                    if (content && content.classList.contains('menu-accordion-content')) {
                        if (isActive) {
                            this.classList.remove('active');
                        content.classList.remove('show');
                    } else {
                            this.classList.add('active');
                        content.classList.add('show');
                        }
                    }
                });
            });

            console.log(`✅ ${accordionButtons.length} accordéons configurés`);

            // 4. Améliorer la scrollbar sidebar
            console.log('📜 Configuration de la scrollbar sidebar...');
            const sidebarMenu = document.querySelector('.sidebar-menu');
            if (sidebarMenu) {
                // Ajouter padding bottom pour éviter que le dernier élément soit coupé
                sidebarMenu.style.paddingBottom = '20px';
                console.log('✅ Scrollbar sidebar configurée');
            }

            // 5. Charger les données navbar
            console.log('📡 Chargement des données navbar...');
            loadNavbarData();

            // 6. Configurer la recherche
            console.log('🔍 Configuration de la recherche...');
            setupSearchFunctionality();

            console.log('🎉 Initialisation terminée !');
        });

        // Load navbar data (notifications, messages, quick actions)
        function loadNavbarData() {
            console.log('📡 Début chargement données navbar...');

            // Load notifications
            fetch('{{ route("navbar.notifications") }}')
                .then(response => {
                    console.log('🔔 Réponse notifications:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('🔔 Données notifications reçues:', data);
                    updateNotifications(data.notifications, data.unread_count);
                })
                .catch(error => {
                    console.error('❌ Erreur chargement notifications:', error);
                    document.getElementById('notifications-list').innerHTML = '<li class="text-center py-3 text-muted">Erreur de chargement</li>';
                });

            // Load messages
            fetch('{{ route("navbar.messages") }}')
                .then(response => {
                    console.log('💬 Réponse messages:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('💬 Données messages reçues:', data);
                    updateMessages(data.messages, data.unread_count);
                })
                .catch(error => {
                    console.error('❌ Erreur chargement messages:', error);
                    document.getElementById('messages-list').innerHTML = '<li class="text-center py-3 text-muted">Erreur de chargement</li>';
                });

            // Load quick actions
            fetch('{{ route("navbar.quick-actions") }}')
                .then(response => {
                    console.log('⚡ Réponse actions rapides:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('⚡ Données actions rapides reçues:', data);
                    updateQuickActions(data.actions);
                })
                .catch(error => {
                    console.error('❌ Erreur chargement actions rapides:', error);
                    document.getElementById('quick-actions-list').innerHTML = '<div class="text-center py-3 text-muted">Erreur de chargement</div>';
                });
        }

        // Update notifications
        function updateNotifications(notifications, unreadCount) {
            console.log('🔔 Mise à jour notifications:', { notifications, unreadCount });
            const notificationsList = document.getElementById('notifications-list');
            const notificationsCount = document.getElementById('notifications-count');

            if (unreadCount > 0) {
                notificationsCount.textContent = unreadCount;
                notificationsCount.style.display = 'inline';
            } else {
                notificationsCount.style.display = 'none';
            }

            if (notifications.length === 0) {
                notificationsList.innerHTML = '<li class="text-center py-3 text-muted">Aucune notification</li>';
                return;
            }

            let html = '';
            notifications.forEach(notification => {
                html += `
                    <li>
                        <a class="dropdown-item notification-item ${notification.read ? '' : 'unread'}" href="${notification.url || '#'}" onclick="markNotificationAsRead(${notification.id})">
                            <div class="notification-icon bg-${notification.type || 'primary'}">
                                <i class="${notification.icon || 'fas fa-bell'}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">${notification.title}</div>
                                <div class="notification-text">${notification.message}</div>
                                <div class="notification-time">${notification.time}</div>
                            </div>
                        </a>
                    </li>
                `;
            });
            notificationsList.innerHTML = html;
        }

        // Update messages
        function updateMessages(messages, unreadCount) {
            console.log('💬 Mise à jour messages:', { messages, unreadCount });
            const messagesList = document.getElementById('messages-list');
            const messagesCount = document.getElementById('messages-count');

            if (unreadCount > 0) {
                messagesCount.textContent = unreadCount;
                messagesCount.style.display = 'inline';
            } else {
                messagesCount.style.display = 'none';
            }

            if (messages.length === 0) {
                messagesList.innerHTML = '<li class="text-center py-3 text-muted">Aucun message</li>';
                return;
            }

            let html = '';
            messages.forEach(message => {
                html += `
                    <li>
                        <a class="dropdown-item message-item ${message.read ? '' : 'unread'}" href="${message.url || '#'}">
                            <div class="message-avatar">
                                ${message.avatar ? `<img src="${message.avatar}" alt="${message.sender}">` : message.sender.charAt(0).toUpperCase()}
                            </div>
                            <div class="message-content">
                                <div class="message-sender">${message.sender}</div>
                                <div class="message-title">${message.title}</div>
                                <div class="message-text">${message.message}</div>
                                <div class="message-time">${message.time}</div>
                            </div>
                        </a>
                    </li>
                `;
            });
            messagesList.innerHTML = html;
        }

        // Update quick actions
        function updateQuickActions(actions) {
            console.log('⚡ Mise à jour actions rapides:', actions);
            const quickActionsList = document.getElementById('quick-actions-list');

            if (actions.length === 0) {
                quickActionsList.innerHTML = '<div class="text-center py-3 text-muted">Aucune action disponible</div>';
                return;
            }

            let html = '';
            actions.forEach(action => {
                html += `
                    <a href="${action.url}" class="quick-action-item">
                        <div class="quick-action-icon text-${action.color}">
                            <i class="${action.icon}"></i>
                        </div>
                        <div class="quick-action-text">${action.title}</div>
                    </a>
                `;
            });
            quickActionsList.innerHTML = html;
        }

        // Setup search functionality
        function setupSearchFunctionality() {
            console.log('🔍 Configuration de la recherche...');
            const searchInput = document.getElementById('global-search');
            const searchResults = document.getElementById('search-results');
            let searchTimeout;

            if (searchInput) {
                console.log('✅ Search input trouvé, ajout des event listeners');

                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    console.log('🔍 Search input - nouvelle valeur:', query);

                    clearTimeout(searchTimeout);

                    if (query.length < 2) {
                        searchResults.style.display = 'none';
                        searchResults.classList.remove('show');
                        return;
                    }

                    searchTimeout = setTimeout(() => {
                        performSearch(query);
                    }, 300);
                });

                // Hide search results when clicking outside
                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                        searchResults.style.display = 'none';
                        searchResults.classList.remove('show');
                    }
                });

                // Show search results when focusing on input if there's content
                searchInput.addEventListener('focus', function() {
                    console.log('🔍 Search input focus');
                    if (this.value.trim().length >= 2 && searchResults.innerHTML.trim() !== '') {
                        searchResults.style.display = 'block';
                        searchResults.classList.add('show');
                    }
                });

                console.log('✅ Search functionality configurée');
            } else {
                console.error('❌ Search input non trouvé');
            }
        }

        // Perform search
        function performSearch(query) {
            console.log('🔍 Exécution recherche pour:', query);
            const searchResults = document.getElementById('search-results');

            searchResults.innerHTML = '<div class="loading-text"><div class="loading-spinner"></div> Recherche...</div>';
            searchResults.style.display = 'block';
            searchResults.classList.add('show');

            fetch(`{{ route("search.global") }}?q=${encodeURIComponent(query)}`)
                .then(response => {
                    console.log('🔍 Réponse recherche:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('🔍 Résultats recherche:', data);
                    displaySearchResults(data);
                })
                .catch(error => {
                    console.error('❌ Erreur recherche:', error);
                    searchResults.innerHTML = '<div class="search-no-results">Erreur de recherche</div>';
                });
        }

        // Display search results
        function displaySearchResults(data) {
            console.log('🔍 Affichage résultats recherche:', data);
            const searchResults = document.getElementById('search-results');

            if (!data.results || data.results.length === 0) {
                searchResults.innerHTML = '<div class="search-no-results">Aucun résultat trouvé</div>';
                return;
            }

            let html = '';

            // Group results by category
            const groupedResults = {};
            data.results.forEach(result => {
                if (!groupedResults[result.category]) {
                    groupedResults[result.category] = [];
                }
                groupedResults[result.category].push(result);
            });

            // Display results by category
            Object.keys(groupedResults).forEach(category => {
                html += `<div class="search-category">${category}</div>`;
                groupedResults[category].forEach(result => {
                    html += `
                        <a href="${result.url}" class="search-item">
                            <div class="search-item-icon bg-${result.color || 'primary'}">
                                <i class="${result.icon}"></i>
                            </div>
                            <div class="search-item-content">
                                <div class="search-item-title">${result.title}</div>
                                <div class="search-item-subtitle">${result.description}</div>
                            </div>
                        </a>
                    `;
                });
            });

            searchResults.innerHTML = html;
        }

        // Mark notification as read
        function markNotificationAsRead(notificationId) {
            console.log('🔔 Marquage notification comme lue:', notificationId);
            fetch(`{{ url('/navbar/notifications') }}/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('🔔 Réponse marquage notification:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('🔔 Notification marquée:', data);
                if (data.success) {
                    // Recharger les notifications
                    loadNavbarData();
                }
            })
            .catch(error => {
                console.error('❌ Erreur marquage notification:', error);
            });
        }

        // Mark all notifications as read
        document.addEventListener('DOMContentLoaded', function() {
            const markAllBtn = document.getElementById('mark-all-notifications-read');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🔔 Marquage toutes notifications comme lues');

                    fetch('{{ url('/navbar/notifications/mark-all-read') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadNavbarData();
                        }
                    })
                    .catch(error => {
                        console.error('❌ Erreur marquage toutes notifications:', error);
                    });
                });
            }
    });
    </script>

    <!-- Scripts additionnels -->
    @stack('scripts')
    @stack('modals')
</body>
</html>
