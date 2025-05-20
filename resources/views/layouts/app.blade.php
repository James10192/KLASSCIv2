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
                        <div class="menu-accordion">
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
                        </div>
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
                                <a href="{{ route('esbtp.teacher-attendance.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.teacher-attendance.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Présence enseignants</span>
                                </a>
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
                                <a href="{{ route('esbtp.bulletins.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.bulletins.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Bulletins scolaires</span>
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
                                <a href="{{ route('esbtp.teachers.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.teachers.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Gestion du personnel</span>
                                </a>
                                <a href="{{ route('esbtp.roles.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.roles.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Rôles & Permissions</span>
                                </a>
                            </div>
                        </div>

                        <!-- Partnerships -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.partnerships.index') }}" class="menu-link {{ Request::routeIs('esbtp.partnerships.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-handshake"></i></div>
                                <div class="menu-text">Partenariats</div>
                            </a>
                        </div>
                    @endif

                    <!-- Attendance Section -->
                    @if(auth()->user()->hasRole('teacher'))
                        <div class="menu-category">Présence</div>

                        <!-- Émargement enseignant -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.attendance.mark') }}" class="menu-link {{ Request::routeIs('esbtp.attendance.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                                <div class="menu-text">Émargement enseignant</div>
                            </a>
                        </div>
                    @endif

                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <div class="menu-category">Présence</div>

                        <!-- Historique émargement enseignant -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.attendance.history') }}" class="menu-link {{ Request::routeIs('esbtp.attendance.history') ? 'active' : '' }}">
                                <i class="fas fa-history me-2"></i>
                                Historique émargement enseignant
                            </a>
                        </div>

                        <!-- Générer code d'émargement -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.attendance-codes.index') }}" class="menu-link {{ Request::routeIs('esbtp.attendance-codes.index') ? 'active' : '' }}">
                                <i class="fas fa-key me-2"></i>
                                Générer code d'émargement
                            </a>
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

                    <div class="menu-item">
                        <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="nav-link {{ request()->routeIs('esbtp.mon-emploi-temps.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                            <div class="menu-text">Mon emploi du temps</div>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="{{ route('esbtp.mes-evaluations.index') }}" class="nav-link {{ request()->routeIs('esbtp.mes-evaluations.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                            <div class="menu-text">Mes évaluations</div>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="{{ route('esbtp.mes-notes.index') }}" class="nav-link {{ request()->routeIs('esbtp.mes-notes.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-clipboard-list me-2"></i></div>
                            <div class="menu-text">Mes notes</div>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="{{ route('esbtp.mon-bulletin.index') }}" class="nav-link {{ request()->routeIs('esbtp.mon-bulletin.index') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-file-invoice me-2"></i></div>
                            <div class="menu-text">Mes résultats et bulletin</div>
                        </a>
                    </div>
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
                @endif
            </div>
        </aside>

        <!-- Main Content -->
        <main class="nextadmin-main">
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
                            <input type="text" placeholder="Rechercher..." class="form-control">
                        </div>
                    </div>

                    <div class="navbar-right">
                        <!-- Notifications -->
                        <div class="dropdown">
                            <button class="navbar-icon" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="navbar-badge">3</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="notificationsDropdown">
                                <li>
                                    <h6 class="dropdown-header">Notifications</h6>
                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item notification-item unread" href="#">
                                        <div class="notification-icon bg-primary-light text-primary">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title">Nouvelle inscription</div>
                                            <div class="notification-text">Un nouvel étudiant s'est inscrit</div>
                                            <div class="notification-time">Il y a 5 minutes</div>
                                        </div>
                    </a>
                </li>
                                <li>
                                    <a class="dropdown-item notification-item" href="#">
                                        <div class="notification-icon bg-success-light text-success">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title">Notes publiées</div>
                                            <div class="notification-text">Les notes de mathématiques sont disponibles</div>
                                            <div class="notification-time">Il y a 2 heures</div>
                                        </div>
                    </a>
                </li>
                                <li>
                                    <a class="dropdown-item notification-item" href="#">
                                        <div class="notification-icon bg-info-light text-info">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title">Rappel</div>
                                            <div class="notification-text">Réunion des enseignants demain</div>
                                            <div class="notification-time">Il y a 1 jour</div>
                                        </div>
                    </a>
                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-center view-all" href="#">
                                        Voir toutes les notifications
                    </a>
                </li>
                            </ul>
                        </div>

                        <!-- Messages -->
                        <div class="dropdown">
                            <button class="navbar-icon" type="button" id="messagesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-envelope"></i>
                                <span class="navbar-badge">2</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="messagesDropdown">
                                <li>
                                    <h6 class="dropdown-header">Messages</h6>
                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item message-item unread" href="#">
                                        <div class="message-avatar">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-title">Konan Yves</div>
                                            <div class="message-text">Bonjour, concernant le cours de...</div>
                                            <div class="message-time">Il y a 10 minutes</div>
                                        </div>
                    </a>
                </li>
                                <li>
                                    <a class="dropdown-item message-item" href="#">
                                        <div class="message-avatar">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-title">Touré Fatima</div>
                                            <div class="message-text">Merci pour les informations...</div>
                                            <div class="message-time">Il y a 3 heures</div>
                                        </div>
                            </a>
                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-center view-all" href="#">
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
                                    <div class="quick-actions-grid">
                                        <a href="{{ route('esbtp.etudiants.create') }}" class="quick-action-item">
                                            <div class="quick-action-icon bg-primary-light text-primary">
                                                <i class="fas fa-user-plus"></i>
                            </div>
                                            <div class="quick-action-text">Nouvel étudiant</div>
                                        </a>
                                        <a href="{{ route('esbtp.evaluations.create') }}" class="quick-action-item">
                                            <div class="quick-action-icon bg-info-light text-info">
                                                <i class="fas fa-file-alt"></i>
                                </div>
                                            <div class="quick-action-text">Créer examen</div>
                                        </a>
                                        <a href="{{ route('esbtp.notes.index') }}" class="quick-action-item">
                                            <div class="quick-action-icon bg-success-light text-success">
                                                <i class="fas fa-clipboard-list"></i>
                            </div>
                                            <div class="quick-action-text">Saisie notes</div>
                                        </a>
                                        <a href="{{ route('esbtp.annonces.create') }}" class="quick-action-item">
                                            <div class="quick-action-icon bg-warning-light text-warning">
                                                <i class="fas fa-bullhorn"></i>
                            </div>
                                            <div class="quick-action-text">Annonce</div>
                                        </a>
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
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user-circle me-2"></i> Mon profil
                                </a>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Suppression de l'auto-hide des messages flash
            // Les messages restent affichés jusqu'à fermeture manuelle

            // Sidebar Toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');

                    // Adjust for mobile
                    if (window.innerWidth < 992) {
                        sidebar.classList.toggle('show');
                    }
                });
            }

            // Accordion Toggle
            const accordionButtons = document.querySelectorAll('.menu-accordion-btn');

            accordionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.classList.toggle('active');
                    const content = this.nextElementSibling;

                    if (content.classList.contains('show')) {
                        content.classList.remove('show');
                    } else {
                        content.classList.add('show');
                    }
                });
            });

            // Collapse sidebar on mobile by default
            function checkWidth() {
                if (window.innerWidth < 992 && sidebar) {
                        sidebar.classList.add('collapsed');
                    sidebar.classList.remove('show');
                } else if (sidebar) {
                            sidebar.classList.remove('collapsed');
                        }
                    }

            // Initial check
            checkWidth();

            // Check on resize
            window.addEventListener('resize', checkWidth);
    });
    </script>

    <!-- Scripts additionnels -->
    @stack('scripts')
    @stack('modals')
</body>
</html>
