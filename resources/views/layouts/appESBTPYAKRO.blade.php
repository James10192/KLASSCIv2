<!--
    Layout principal de l'application ESBTP-yAKRO

    Ce fichier a été modifié pour :
    1. Corriger les routes non définies (erreurs 'Route [xxx] not defined')
    2. Organiser la barre latérale en fonction des rôles (superadmin, secretaire, enseignant, etudiant, parent)
    3. Regrouper les fonctionnalités par catégories logiques

    Toutes les routes ont été alignées avec les contrôleurs existants.

    Dernière mise à jour : 02/03/2025
-->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'ESBTP'))</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --esbtp-green: #01632f;
            --esbtp-green-dark: #014a23;
            --esbtp-green-light: #e6f3ee;
            --esbtp-orange: #f29400;
            --esbtp-orange-dark: #d98600;
            --esbtp-orange-light: #fff8eb;
            --esbtp-white: #ffffff;
            --esbtp-light-green: rgba(1, 99, 47, 0.1);
            --esbtp-light-orange: rgba(242, 148, 0, 0.1);
            --esbtp-gray: #f8f9fa;
            --esbtp-dark: #343a40;
            --esbtp-text: #495057;
            --sidebar-width: 280px;
            --topbar-height: 70px;
            --border-radius: 10px;
            --card-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--esbtp-gray);
            color: var(--esbtp-text);
            min-height: 100vh;
            display: flex;
        }

        /* Correction pour les éléments select et option */
        select, select option {
            color: var(--esbtp-text) !important;
            background-color: var(--esbtp-white) !important;
        }

        select option {
            padding: 8px 12px;
        }

        /* Pour Select2 si utilisé */
        .select2-container--default .select2-selection--single .select2-selection__rendered,
        .select2-results__option {
            color: var(--esbtp-text) !important;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--esbtp-white);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            transition: transform var(--transition-speed);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--esbtp-green);
            margin-bottom: 5px;
        }

        .sidebar-subtitle {
            font-size: 0.9rem;
            color: var(--esbtp-orange);
            font-weight: 500;
        }

        .sidebar-menu {
            padding: 20px;
        }

        .menu-category {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--esbtp-text);
            opacity: 0.6;
            margin: 20px 0 10px;
            font-weight: 600;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: var(--esbtp-text);
            border-radius: var(--border-radius);
            transition: all var(--transition-speed);
        }

        .nav-link:hover {
            background-color: var(--esbtp-light-green);
            color: var(--esbtp-green);
        }

        .nav-link.active {
            background-color: var(--esbtp-green);
            color: var(--esbtp-white);
        }

        .nav-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            transition: margin var(--transition-speed);
        }

        .topbar {
            height: var(--topbar-height);
            background-color: var(--esbtp-white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--esbtp-text);
            cursor: pointer;
            margin-right: 15px;
        }

        .page-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--esbtp-dark);
            flex: 1;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .action-item {
            margin-left: 20px;
            position: relative;
        }

        .action-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--esbtp-text);
            cursor: pointer;
            transition: color var(--transition-speed);
        }

        .action-btn:hover {
            color: var(--esbtp-green);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--esbtp-orange);
            color: var(--esbtp-white);
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--esbtp-dark);
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--esbtp-text);
            opacity: 0.8;
        }

        .content {
            padding: 30px;
            flex: 1;
        }

        /* Responsive Styles */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.collapsed {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .toggle-sidebar {
                display: block;
            }
        }

        /* Card Styles */
        .card {
            background-color: var(--esbtp-white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: none;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--esbtp-white);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--esbtp-dark);
            margin: 0;
        }

        .card-body {
            padding: 20px;
        }

        /* Button Styles */
        .btn-primary {
            background-color: var(--esbtp-green);
            border-color: var(--esbtp-green);
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--esbtp-green-dark);
            border-color: var(--esbtp-green-dark);
        }

        .btn-secondary {
            background-color: var(--esbtp-orange);
            border-color: var(--esbtp-orange);
        }

        .btn-secondary:hover, .btn-secondary:focus {
            background-color: var(--esbtp-orange-dark);
            border-color: var(--esbtp-orange-dark);
        }

        .notifications-dropdown {
            width: 320px;
            padding: 0;
            max-height: 400px;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }

        .notifications-header h6 {
            margin: 0;
        }

        .notifications-body {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: var(--esbtp-light-green);
        }

        .notification-item.unread {
            background-color: var(--esbtp-light-orange);
        }

        .notification-item .notification-title {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .notification-item .notification-time {
            font-size: 0.8rem;
            color: #666;
        }

        .notifications-footer {
            border-top: 1px solid #eee;
        }

        .notifications-footer a {
            padding: 10px;
            font-size: 0.9rem;
        }

        #unreadNotificationCount {
            display: none;
        }

        #unreadNotificationCount:not(:empty) {
            display: flex;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="{{ asset('images/esbtp_logo.png') }}" alt="ESBTP Logo" class="sidebar-logo">
            <div class="sidebar-brand">ESBTP</div>
            <div class="sidebar-subtitle">Gestion Universitaire</div>
            <!--<div class="sidebar-brand">KLASSCI</div>
            <div class="sidebar-subtitle">Gestion Universitaire</div>-->
        </div>

        <div class="sidebar-menu">
            <div class="menu-category">Navigation principale</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt nav-icon"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>

                @role('superAdmin')
                <div class="menu-category">Administration</div>
                <li class="nav-item">
                    <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <i class="fas fa-user-tag nav-icon"></i>
                        <span>Rôles et permissions</span>
                    </a>
                </li>
                <!--<li class="nav-item">
                    <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="fas fa-cog nav-icon"></i>
                        <span>Paramètres</span>
                    </a>
                </li>-->
                <li class="nav-item">
                    <a href="{{ route('esbtp.secretaires.index') }}" class="nav-link {{ request()->routeIs('esbtp.secretaires.*') ? 'active' : '' }}">
                        <i class="fas fa-user-tie nav-icon"></i>
                        <span>Gestion des Secrétaires</span>
                    </a>
                </li>
                @endrole

                @hasanyrole('superAdmin|secretaire')
                <div class="menu-category">Structure académique</div>
                @role('superAdmin')
                <li class="nav-item">
                    <a href="{{ route('esbtp.filieres.index') }}" class="nav-link {{ request()->routeIs('esbtp.filieres.*') ? 'active' : '' }}">
                        <i class="fas fa-graduation-cap nav-icon"></i>
                        <span>Filières</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="nav-link {{ request()->routeIs('esbtp.niveaux-etudes.*') ? 'active' : '' }}">
                        <i class="fas fa-layer-group nav-icon"></i>
                        <span>Niveaux d'études</span>
                    </a>
                </li>
                @endrole
                <li class="nav-item">
                    <a href="{{ route('esbtp.classes.index') }}" class="nav-link {{ request()->routeIs('esbtp.classes.*') || request()->routeIs('esbtp.classes.*') ? 'active' : '' }}">
                        <i class="fas fa-chalkboard nav-icon"></i>
                        <span>Classes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.matieres.index') }}" class="nav-link {{ request()->routeIs('esbtp.matieres.*') ? 'active' : '' }}">
                        <i class="fas fa-book nav-icon"></i>
                        <span>Matières</span>
                    </a>
                </li>
                @endhasanyrole

                @hasanyrole('superAdmin|secretaire')
                <div class="menu-category">Gestion des étudiants</div>
                <li class="nav-item">
                    <a href="{{ route('esbtp.etudiants-inscriptions.index') }}" class="nav-link {{ request()->routeIs('esbtp.etudiants-inscriptions.*') || request()->routeIs('esbtp.etudiants.*') || request()->routeIs('esbtp.inscriptions.*') ? 'active' : '' }}">
                        <i class="fas fa-user-graduate nav-icon"></i>
                        <span>Étudiants & Inscriptions</span>
                    </a>
                </li>
                @endhasanyrole

                <!-- Section emploi du temps -->
                <div class="menu-category">Emploi du temps</div>
                @hasanyrole('superAdmin|secretaire')
                <li class="nav-item">
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="nav-link {{ request()->routeIs('esbtp.emploi-temps.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt nav-icon"></i>
                        <span>Gestion des emplois du temps</span>
                    </a>
                </li>
                @endhasanyrole

                @role('etudiant')
                <li class="nav-item">
                    <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="nav-link {{ request()->routeIs('esbtp.mon-emploi-temps.index') ? 'active' : '' }}">
                        <i class="fas fa-calendar-day nav-icon"></i>
                        <span>Mon emploi du temps</span>
                    </a>
                </li>
                @endrole

                <!-- Section examens et notes -->
                <div class="menu-category">Évaluations et notes</div>
                @hasanyrole('superAdmin|secretaire')
                <li class="nav-item">
                    <a href="{{ route('esbtp.evaluations.index') }}" class="nav-link {{ request()->routeIs('esbtp.evaluations.*') ? 'active' : '' }}">
                        <i class="fas fa-file-alt nav-icon"></i>
                        <span>Évaluations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.notes.index') }}" class="nav-link {{ request()->routeIs('esbtp.notes.*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list nav-icon"></i>
                        <span>Notes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.resultats.index') }}" class="nav-link {{ request()->routeIs('esbtp.bulletins.*') || request()->routeIs('esbtp.resultats.*') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice nav-icon"></i>
                        <span>Résultats et Bulletins</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.progression.index') }}" class="nav-link {{ request()->routeIs('esbtp.progression.*') ? 'active' : '' }}">
                        <i class="fas fa-graduation-cap nav-icon"></i>
                        <span>Progression des Étudiants</span>
                    </a>
                </li>
                @endhasanyrole

                @role('etudiant')
                <li class="nav-item">
                    <a href="{{ route('esbtp.mes-evaluations.index') }}" class="nav-link {{ request()->routeIs('esbtp.mes-evaluations.index') ? 'active' : '' }}">
                        <i class="fas fa-file-alt nav-icon"></i>
                        <span>Mes évaluations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.mes-notes.index') }}" class="nav-link {{ request()->routeIs('esbtp.mes-notes.index') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list nav-icon"></i>
                        <span>Mes notes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.mon-bulletin.index') }}" class="nav-link {{ request()->routeIs('esbtp.mon-bulletin.index') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice nav-icon"></i>
                        <span>Mes résultats et bulletin</span>
                    </a>
                </li>
                @endrole

                <!-- Section présences -->
                <div class="menu-category">Présences</div>
                @hasanyrole('superAdmin|secretaire')
                <li class="nav-item">
                    <a href="{{ route('esbtp.attendances.index') }}" class="nav-link {{ request()->routeIs('esbtp.attendances.index') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-check nav-icon"></i>
                        <span>Suivi des présences</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.attendances.rapport-form') }}" class="nav-link {{ request()->routeIs('esbtp.attendances.rapport-form') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span>Rapports</span>
                    </a>
                </li>
                @endhasanyrole

                @role('etudiant')
                <li class="nav-item">
                    <a href="{{ route('esbtp.mes-absences.index') }}" class="nav-link {{ request()->routeIs('esbtp.mes-absences.index') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-check nav-icon"></i>
                        <span>Mes absences</span>
                    </a>
                </li>
                <!-- Debug link moved to a separate nav item if needed -->
                <li class="nav-item">
                    <a href="/esbtp/mes-absences/debug" class="nav-link" style="color: #ff5500;">
                        <i class="nav-icon fas fa-bug"></i>
                        <span>Debug Mes Absences</span>
                    </a>
                </li>
                @endrole

                @role('etudiant')
                <div class="menu-category">Communication</div>
                <li class="nav-item">
                    <a href="{{ route('esbtp.mes-notifications.index') }}" class="nav-link {{ request()->routeIs('esbtp.mes-notifications.index') ? 'active' : '' }}">
                        <i class="fas fa-bell nav-icon"></i>
                        <span>Mes notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('esbtp.mes-messages.index') }}" class="nav-link {{ request()->routeIs('esbtp.mes-messages.index') ? 'active' : '' }}">
                        <i class="fas fa-inbox nav-icon"></i>
                        <span>Mes messages</span>
                    </a>
                </li>
                @endrole

                @role('parent')
                <div class="menu-category">Espace Parent</div>
                <li class="nav-item">
                    <a href="{{ route('parent.dashboard') }}" class="nav-link {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home nav-icon"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('parent.payments') }}" class="nav-link {{ request()->routeIs('parent.payments*') ? 'active' : '' }}">
                        <i class="fas fa-money-bill-wave nav-icon"></i>
                        <span>Paiements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('parent.absences.summary') }}" class="nav-link {{ request()->routeIs('parent.absences*') ? 'active' : '' }}">
                        <i class="fas fa-user-clock nav-icon"></i>
                        <span>Absences</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('parent.bulletins') }}" class="nav-link {{ request()->routeIs('parent.bulletins*') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice nav-icon"></i>
                        <span>Résultats et bulletins</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('parent.messages') }}" class="nav-link {{ request()->routeIs('parent.messages*') ? 'active' : '' }}">
                        <i class="fas fa-envelope nav-icon"></i>
                        <span>Messages</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('parent.notifications') }}" class="nav-link {{ request()->routeIs('parent.notifications*') ? 'active' : '' }}">
                        <i class="fas fa-bell nav-icon"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('parent.settings.index') }}" class="nav-link {{ request()->routeIs('parent.settings*') ? 'active' : '' }}">
                        <i class="fas fa-cog nav-icon"></i>
                        <span>Paramètres</span>
                    </a>
                </li>
                @endrole

                <!-- Communication -->
                @hasanyrole('superAdmin|secretaire')
                <div class="menu-category">Communication</div>

                <li class="nav-item">
                    <a href="{{ route('esbtp.annonces.index') }}" class="nav-link {{ request()->routeIs('esbtp.annonces.*') ? 'active' : '' }}">
                        <i class="fas fa-envelope nav-icon"></i>
                        <span>Envoi de messages</span>
                    </a>
                </li>
                @endhasanyrole

                @role('etudiant')
                <!--<li class="nav-item">
                    <a href="{{ route('esbtp.annonces.index') }}" class="nav-link {{ request()->routeIs('esbtp.annonces.index') ? 'active' : '' }}">
                        <i class="fas fa-inbox nav-icon"></i>
                        <span>Mes messages</span>
                    </a>
                </li>-->
                @endrole

                <!-- Section profil utilisateur -->
                <div class="menu-category">Mon compte</div>
                <li class="nav-item">
                    @role('etudiant')
                    <a href="{{ route('esbtp.mon-profil.index') }}" class="nav-link {{ request()->routeIs('esbtp.mon-profil.*') ? 'active' : '' }}">
                        <i class="fas fa-user-circle nav-icon"></i>
                        <span>Profil</span>
                    </a>
                    @else
                    <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                        <i class="fas fa-user-circle nav-icon"></i>
                        <span>Profil</span>
                    </a>
                    @endrole
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span>Déconnexion</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <div class="main-wrapper">
        <header class="topbar">
            <button id="toggle-sidebar" class="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <h1 class="page-title">@yield('page_title', 'Tableau de bord')</h1>

            <div class="topbar-actions">
                <div class="action-item">
                    <div class="dropdown">
                        <button class="action-btn" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="unreadNotificationCount">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notifications-dropdown" aria-labelledby="notificationsDropdown">
                            <div class="notifications-header">
                                <h6 class="dropdown-header">Notifications</h6>
                                <button class="btn btn-link btn-sm mark-all-read">Tout marquer comme lu</button>
                            </div>
                            <div class="notifications-body" id="notificationsContainer">
                                <div class="text-center p-3">
                                    <small>Chargement...</small>
                                </div>
                            </div>
                            <div class="notifications-footer">
                                <div class="dropdown-divider"></div>
                                <a href="@if(auth()->check() && auth()->user()->hasRole('etudiant')){{ route('esbtp.mes-notifications.index') }}@else{{ route('notifications.index') }}@endif" class="dropdown-item text-center">Voir toutes les notifications</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-item dropdown">
                    <div class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false" id="userDropdown">
                        @php
                            \Log::info('Tentative d\'affichage de la photo de profil', [
                                'user_authenticated' => Auth::check(),
                                'user_id' => Auth::id(),
                                'has_profile_photo' => Auth::user() && Auth::user()->profile_photo_path ? 'yes' : 'no',
                                'profile_photo_path' => Auth::user() ? Auth::user()->profile_photo_path : null,
                                'storage_url' => Auth::user() && Auth::user()->profile_photo_path ? Storage::url(Auth::user()->profile_photo_path) : null
                            ]);
                        @endphp
                        @if(Auth::user() && Auth::user()->profile_photo_path)
                            <img src="{{ Storage::url(Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}" class="user-avatar">
                        @else
                            <img src="{{ asset('images/avatar.jpg') }}" alt="User Avatar" class="user-avatar">
                        @endif
                        <div class="user-info">
                            <div class="user-name">{{ Auth::user()->name ?? 'Utilisateur' }}</div>
                            <div class="user-role">{{ Auth::user()->roles->first()->name ?? 'Rôle' }}</div>
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            @role('etudiant')
                            <a class="dropdown-item" href="{{ route('esbtp.mon-profil.index') }}"><i class="fas fa-user me-2"></i> Mon profil</a>
                            @else
                            <a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="fas fa-user me-2"></i> Mon profil</a>
                            @endrole
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            const toggleBtn = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const notificationsDropdown = document.getElementById('notificationsDropdown');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                });
            }

            // Empêcher la propagation du click sur le dropdown des notifications
            notificationsDropdown?.addEventListener('click', function(event) {
                event.stopPropagation();
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 992) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggleBtn = toggleBtn.contains(event.target);
                    const isClickOnNotifications = notificationsDropdown.contains(event.target);

                    if (!isClickInsideSidebar && !isClickOnToggleBtn && !isClickOnNotifications && !sidebar.classList.contains('collapsed')) {
                        sidebar.classList.add('collapsed');
                    }
                }
            });

            // Adjust sidebar on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('collapsed');
                }
            });
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        const notificationsContainer = document.getElementById('notificationsContainer');
        const unreadCount = document.getElementById('unreadNotificationCount');
        let isLoading = false;

        function updateUnreadCount() {
            @if(auth()->check() && auth()->user()->hasRole('etudiant'))
            fetch('{{ route("esbtp.notifications.unreadCount") }}')
            @else
            fetch('{{ route("notifications.unreadCount") }}')
            @endif
                .then(response => response.json())
                .then(data => {
                    unreadCount.textContent = data.count > 0 ? data.count : '';
                });
        }

        function loadNotifications() {
            if (isLoading) return;
            isLoading = true;

            notificationsContainer.innerHTML = '<div class="text-center p-3"><small>Chargement...</small></div>';

            @if(auth()->check() && auth()->user()->hasRole('etudiant'))
            fetch('{{ route("esbtp.mes-notifications.index") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            @else
            fetch('{{ route("notifications.index") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            @endif
                .then(response => response.text())
                .then(html => {
                    notificationsContainer.innerHTML = html;
                    isLoading = false;
                })
                .catch(() => {
                    notificationsContainer.innerHTML = '<div class="text-center p-3"><small>Erreur de chargement</small></div>';
                    isLoading = false;
                });
        }

        function markAsRead(id) {
            @if(auth()->check() && auth()->user()->hasRole('etudiant'))
            fetch(`{{ url('esbtp/mes-notifications') }}/${id}/read`, {
            @else
            fetch(`{{ url('notifications') }}/${id}/read`, {
            @endif
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'X-Source': 'dropdown'
                }
            })
            .then(response => response.json())
            .then(data => {
                updateUnreadCount();

                // Si la notification doit être masquée, l'enlever du DOM
                if (data.hide) {
                    const notificationItem = document.querySelector(`.notification-item[onclick*="${id}"]`);
                    if (notificationItem) {
                        // Animation de fondu avant la suppression
                        notificationItem.style.opacity = '1';
                        notificationItem.style.transition = 'opacity 0.3s ease-out';

                        // Commencer le fondu
                        setTimeout(() => {
                            notificationItem.style.opacity = '0';
                        }, 10);

                        // Supprimer après l'animation
                        setTimeout(() => {
                            notificationItem.remove();

                            // Afficher un message "aucune notification" si c'était la dernière
                            const notificationsContainer = document.getElementById('notificationsContainer');
                            if (notificationsContainer && !notificationsContainer.querySelector('.notification-item')) {
                                notificationsContainer.innerHTML = '<div class="text-center p-3"><small>Aucune notification</small></div>';
                            }
                        }, 300);
                    }
                }
            });
        }

        document.querySelector('.mark-all-read')?.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            @if(auth()->check() && auth()->user()->hasRole('etudiant'))
            fetch('{{ route("esbtp.mes-notifications.markAllAsRead") }}', {
            @else
            fetch('{{ route("notifications.markAllAsRead") }}', {
            @endif
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(() => {
                updateUnreadCount();
                loadNotifications();
            });
        });

        notificationsDropdown?.addEventListener('click', function() {
            loadNotifications();
        });

        // Initial unread count
        updateUnreadCount();

        // Update unread count every minute
        setInterval(updateUnreadCount, 60000);
    });
    </script>

    <!-- Yield for page-specific scripts -->
    @yield('scripts')
</body>
</html>
