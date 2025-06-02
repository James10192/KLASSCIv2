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
                                <div class="menu-text">Émargement enseignant</div>
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

            // Navbar functionality
            loadNavbarData();
            setupSearchFunctionality();
        });

        // Load navbar data (notifications, messages, quick actions)
        function loadNavbarData() {
            // Load notifications
            fetch('{{ route("navbar.notifications") }}')
                .then(response => response.json())
                .then(data => {
                    updateNotifications(data.notifications, data.unread_count);
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    document.getElementById('notifications-list').innerHTML = '<li class="text-center py-3 text-muted">Erreur de chargement</li>';
                });

            // Load messages
            fetch('{{ route("navbar.messages") }}')
                .then(response => response.json())
                .then(data => {
                    updateMessages(data.messages, data.unread_count);
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    document.getElementById('messages-list').innerHTML = '<li class="text-center py-3 text-muted">Erreur de chargement</li>';
                });

            // Load quick actions
            fetch('{{ route("navbar.quick-actions") }}')
                .then(response => response.json())
                .then(data => {
                    updateQuickActions(data.actions);
                })
                .catch(error => {
                    console.error('Error loading quick actions:', error);
                    document.getElementById('quick-actions-list').innerHTML = '<div class="text-center py-3 text-muted">Erreur de chargement</div>';
                });
        }

        // Update notifications
        function updateNotifications(notifications, unreadCount) {
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
                        <a class="dropdown-item notification-item ${notification.read ? '' : 'unread'}" href="#" onclick="markNotificationAsRead(${notification.id}, '${notification.url || '#'}')">
                            <div class="d-flex">
                                <div class="notification-icon me-2">
                                    <i class="${notification.icon}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="notification-title">${notification.title}</div>
                                    <div class="notification-text">${notification.message}</div>
                                    <div class="notification-time">${notification.time}</div>
                                </div>
                            </div>
                        </a>
                    </li>
                `;
            });
            notificationsList.innerHTML = html;
        }

        // Update messages
        function updateMessages(messages, unreadCount) {
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
                        <a class="dropdown-item message-item" href="${message.url}">
                            <div class="d-flex">
                                <div class="message-avatar me-2">
                                    ${message.avatar ? `<img src="${message.avatar}" alt="${message.sender}">` : '<i class="fas fa-user"></i>'}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="message-title">${message.title}</div>
                                    <div class="message-text">${message.message}</div>
                                    <div class="message-time">${message.time}</div>
                                </div>
                            </div>
                        </a>
                    </li>
                `;
            });
            messagesList.innerHTML = html;
        }

        // Update quick actions
        function updateQuickActions(actions) {
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
            const searchInput = document.getElementById('global-search');
            const searchResults = document.getElementById('search-results');
            let searchTimeout;

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();

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
                    if (this.value.trim().length >= 2 && searchResults.innerHTML.trim() !== '') {
                        searchResults.style.display = 'block';
                        searchResults.classList.add('show');
                    }
                });
            }
        }

        // Perform search
        function performSearch(query) {
            const searchResults = document.getElementById('search-results');

            searchResults.innerHTML = '<div class="loading-text"><div class="loading-spinner"></div> Recherche...</div>';
            searchResults.style.display = 'block';
            searchResults.classList.add('show');

            fetch(`{{ route("search.global") }}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data);
                })
                .catch(error => {
                    console.error('Error performing search:', error);
                    searchResults.innerHTML = '<div class="search-no-results">Erreur de recherche</div>';
                });
        }

        // Display search results
        function displaySearchResults(data) {
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
        function markNotificationAsRead(notificationId, url) {
            fetch(`{{ url('navbar/notifications') }}/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && url !== '#') {
                    window.location.href = url;
                }
                // Reload notifications
                loadNavbarData();
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }

        // Mark all notifications as read
        document.getElementById('mark-all-notifications-read')?.addEventListener('click', function() {
            fetch('{{ route("navbar.notifications.mark-all-read") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNavbarData();
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        });
    </script>

    <!-- Scripts additionnels -->
    @stack('scripts')
    @stack('modals')
</body>
</html>
