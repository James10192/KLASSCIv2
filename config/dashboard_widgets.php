<?php

/**
 * Catalogue des widgets de dashboard configurables (Lot 9).
 *
 * Chaque widget = une carte KPI ou bloc d'info affichable sur le dashboard
 * widget-based. Lu via App\Services\DashboardWidgetRegistry.
 *
 * Format d'un widget :
 * - label              : titre court FR (visible dans la modal de config)
 * - description        : phrase explicative FR
 * - icon               : classe FontAwesome (ex: fa-user-graduate)
 * - color              : primary | success | warning | info (couleur de l'accent)
 * - permission         : permission canonique (registry config/permissions.php)
 *                        requise pour voir le widget
 * - partial            : nom de la vue Blade partial à inclure
 *                        (ex: dashboard.widgets.students-total)
 * - group              : libellé de groupe FR (utilisé pour la modal)
 * - size               : sm | md | lg (1, 2 ou 3 colonnes desktop)
 * - default_for_roles  : liste des rôles qui voient ce widget par défaut
 *                        (si user.dashboard_widgets = NULL)
 *
 * Pour ajouter un nouveau widget :
 * 1. Ajouter une entrée ici avec une clé unique en dot.notation
 * 2. Créer le partial resources/views/dashboard/widgets/<slug>.blade.php
 * 3. Le partial reçoit $widget (ce config-array) et $user (Auth::user())
 * 4. Vérifier que la permission existe dans config/permissions.php
 */

return [

    // ===== Étudiants =====
    'students.total' => [
        'label' => 'Nombre total d\'étudiants',
        'description' => 'Compte global des étudiants actifs',
        'icon' => 'fa-user-graduate',
        'color' => 'primary',
        'permission' => 'students.view',
        'partial' => 'dashboard.widgets.students-total',
        'group' => 'Étudiants',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'secretaire', 'coordinateur'],
    ],

    'students.new_this_month' => [
        'label' => 'Nouveaux étudiants ce mois',
        'description' => 'Étudiants créés depuis le 1er du mois courant',
        'icon' => 'fa-user-plus',
        'color' => 'success',
        'permission' => 'students.view',
        'partial' => 'dashboard.widgets.students-new-this-month',
        'group' => 'Étudiants',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'secretaire'],
    ],

    // ===== Inscriptions =====
    'inscriptions.pending_validation' => [
        'label' => 'Inscriptions en attente',
        'description' => 'Inscriptions à valider (statut en_attente ou workflow incomplet)',
        'icon' => 'fa-hourglass-half',
        'color' => 'warning',
        'permission' => 'inscriptions.validate',
        'partial' => 'dashboard.widgets.inscriptions-pending-validation',
        'group' => 'Inscriptions',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'secretaire', 'coordinateur'],
    ],

    'inscriptions.this_year' => [
        'label' => 'Inscriptions de l\'année',
        'description' => 'Total des inscriptions sur l\'année universitaire en cours',
        'icon' => 'fa-file-signature',
        'color' => 'primary',
        'permission' => 'inscriptions.view',
        'partial' => 'dashboard.widgets.inscriptions-this-year',
        'group' => 'Inscriptions',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'secretaire', 'coordinateur', 'caissier'],
    ],

    // ===== Paiements =====
    'paiements.pending' => [
        'label' => 'Paiements en attente',
        'description' => 'Paiements en statut en_attente à valider',
        'icon' => 'fa-clock',
        'color' => 'warning',
        'permission' => 'paiements.validate',
        'partial' => 'dashboard.widgets.paiements-pending',
        'group' => 'Paiements',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'comptable', 'caissier'],
    ],

    'paiements.month_total' => [
        'label' => 'Encaissements du mois',
        'description' => 'Somme des paiements validés ce mois (en FCFA)',
        'icon' => 'fa-coins',
        'color' => 'success',
        'permission' => 'paiements.view',
        'partial' => 'dashboard.widgets.paiements-month-total',
        'group' => 'Paiements',
        'size' => 'md',
        'default_for_roles' => ['superAdmin', 'comptable', 'caissier'],
    ],

    'paiements.outstanding_balance' => [
        'label' => 'Solde restant à recouvrer',
        'description' => 'Estimation des frais dus restant à payer (année en cours)',
        'icon' => 'fa-balance-scale',
        'color' => 'warning',
        'permission' => 'paiements.view',
        'partial' => 'dashboard.widgets.paiements-outstanding-balance',
        'group' => 'Paiements',
        'size' => 'md',
        'default_for_roles' => ['superAdmin', 'comptable'],
    ],

    // ===== Notes & Bulletins =====
    'bulletins.generated_this_period' => [
        'label' => 'Bulletins générés (période)',
        'description' => 'Bulletins créés au cours des 30 derniers jours',
        'icon' => 'fa-file-alt',
        'color' => 'info',
        'permission' => 'bulletins.view',
        'partial' => 'dashboard.widgets.bulletins-generated-this-period',
        'group' => 'Notes & Bulletins',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'secretaire', 'coordinateur'],
    ],

    'notes.recent' => [
        'label' => 'Notes saisies récemment',
        'description' => 'Notes ajoutées au cours des 7 derniers jours',
        'icon' => 'fa-pen-fancy',
        'color' => 'primary',
        'permission' => 'notes.view',
        'partial' => 'dashboard.widgets.notes-recent',
        'group' => 'Notes & Bulletins',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'coordinateur'],
    ],

    // ===== Présences =====
    'attendances.today_rate' => [
        'label' => 'Taux de présence du jour',
        'description' => 'Pourcentage de présences sur les présences saisies aujourd\'hui',
        'icon' => 'fa-clipboard-check',
        'color' => 'success',
        'permission' => 'attendances.view',
        'partial' => 'dashboard.widgets.attendances-today-rate',
        'group' => 'Présences',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin', 'coordinateur', 'secretaire'],
    ],

    // ===== Communication =====
    'annonces.recent' => [
        'label' => 'Annonces récentes',
        'description' => 'Dernières annonces publiées sur l\'établissement',
        'icon' => 'fa-bullhorn',
        'color' => 'info',
        'permission' => 'annonces.view',
        'partial' => 'dashboard.widgets.annonces-recent',
        'group' => 'Communication',
        'size' => 'lg',
        'default_for_roles' => ['superAdmin', 'secretaire', 'coordinateur', 'comptable', 'caissier', 'enseignant'],
    ],

    // ===== Système =====
    'users.active' => [
        'label' => 'Utilisateurs actifs',
        'description' => 'Comptes utilisateur actifs (is_active = 1)',
        'icon' => 'fa-users',
        'color' => 'primary',
        'permission' => 'users.manage',
        'partial' => 'dashboard.widgets.users-active',
        'group' => 'Système',
        'size' => 'sm',
        'default_for_roles' => ['superAdmin'],
    ],

];
