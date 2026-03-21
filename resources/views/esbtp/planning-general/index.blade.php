@extends('layouts.app')

@section('title', 'Planning Général - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<!-- Correction des modales Bootstrap -->
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
<style>
    /* Amélioration Mobile-First */
    .planning-nav {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        container-type: inline-size;
    }

    .planning-nav .nav-tabs {
        border: none;
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--radius-small);
        padding: var(--space-xs);
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .planning-nav .nav-tabs::-webkit-scrollbar {
        display: none;
    }

    .planning-nav .nav-link {
        border: none;
        color: var(--text-secondary);
        background: transparent;
        border-radius: var(--radius-small);
        padding: var(--space-sm) var(--space-md);
        margin: 0 var(--space-xs);
        transition: all 0.3s ease;
        white-space: nowrap;
        min-width: 120px;
        text-align: center;
    }

    .planning-nav .nav-link.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
        transform: translateY(-2px);
    }

    /* Stats améliorées avec micro-interactions */
    .stats-planning {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .stat-planning {
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    .stat-planning:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .stat-planning::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.6s ease;
    }

    .stat-planning:hover::before {
        transform: scaleX(1);
    }

    .stat-planning.primary::before { background: linear-gradient(90deg, var(--primary), #60a5fa); }
    .stat-planning.success::before { background: linear-gradient(90deg, var(--success), #34d399); }
    .stat-planning.warning::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
    .stat-planning.info::before { background: linear-gradient(90deg, var(--info), #38bdf8); }

    .stat-icon-planning {
        width: 64px;
        height: 64px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-md);
        font-size: 28px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }

    .stat-planning:hover .stat-icon-planning {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .stat-planning.primary .stat-icon-planning { color: var(--primary); }
    .stat-planning.success .stat-icon-planning { color: var(--success); }
    .stat-planning.warning .stat-icon-planning { color: var(--warning); }
    .stat-planning.info .stat-icon-planning { color: var(--info); }

    /* Amélioration des valeurs statistiques */
    .stat-value {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--text-primary);
        line-height: 1;
        margin-bottom: var(--space-sm);
        transition: all 0.3s ease;
    }

    .stat-planning:hover .stat-value {
        transform: scale(1.05);
    }

    .stat-label {
        font-size: var(--text-base);
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Trend indicators */
    .stat-trend {
        font-size: var(--text-sm);
        font-weight: 500;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        margin-top: var(--space-sm);
        display: inline-block;
    }

    .stat-trend.positive {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .stat-trend.neutral {
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
    }

    /* Quick Actions améliorées */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-xl);
        margin-top: var(--space-xl);
    }

    .action-card {
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        border-radius: var(--radius-large);
    }

    .action-card:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        text-decoration: none;
        color: inherit;
    }

    .action-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: radial-gradient(circle, rgba(var(--primary-rgb), 0.1), transparent);
        transform: translate(50%, -50%);
        transition: all 0.4s ease;
    }

    .action-card:hover::after {
        transform: translate(30%, -30%) scale(1.5);
    }

    /* Sélecteurs améliorés */
    .filter-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }

    .filter-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .form-select-modern {
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        font-weight: 500;
        transition: all 0.3s ease;
        background: var(--background);
    }

    .form-select-modern:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        transform: translateY(-2px);
    }

    /* Context card pour sélection active */
    .context-active {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05));
        border: 2px solid rgba(var(--primary-rgb), 0.2);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }

    .context-active::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
        pointer-events: none;
    }

    .context-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-top: var(--space-lg);
    }

    .context-item {
        background: rgba(255, 255, 255, 0.8);
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        border-left: 4px solid var(--primary);
        text-align: center;
    }

    .context-value {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--text-primary);
        display: block;
    }

    .context-label {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: var(--space-xs);
    }

    /* Responsive amélioré */
    @container (max-width: 768px) {
        .stats-planning {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--space-md);
        }

        .stat-icon-planning {
            width: 48px;
            height: 48px;
            font-size: 20px;
        }

        .stat-value {
            font-size: 2rem;
        }

        .quick-actions {
            grid-template-columns: 1fr;
            gap: var(--space-lg);
        }

        .context-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @container (max-width: 480px) {
        .stats-planning {
            grid-template-columns: repeat(2, 1fr);
        }

        .context-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Accessibilité et réduction de mouvement */
    @media (prefers-reduced-motion: reduce) {
        .stat-planning,
        .action-card,
        .stat-icon-planning,
        .stat-value {
            transition: none;
        }

        .stat-planning:hover,
        .action-card:hover {
            transform: none;
        }
    }

    /* Indicateurs de statut */
    .status-indicator {
        position: absolute;
        top: var(--space-sm);
        right: var(--space-sm);
        width: 12px;
        height: 12px;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    .status-indicator.active { background: var(--success); }
    .status-indicator.pending { background: var(--warning); }
    .status-indicator.inactive { background: var(--danger); }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .status-indicator {
            animation: none;
        }
    }

    /* Action Cards Styles */
    .action-card {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, transparent 0%, rgba(99, 102, 241, 0.02) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .action-card:hover::before {
        opacity: 1;
    }

    .action-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        margin-bottom: var(--space-md);
    }

    .action-icon.calendar { background: linear-gradient(135deg, #5e91de, #0453cb); }
    .action-icon.chart { background: linear-gradient(135deg, #06b6d4, #67e8f9); }
    .action-icon.users { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .action-icon.settings { background: linear-gradient(135deg, #ef4444, #f87171); }

    /* ================================
       INTERFACE MODERNE DE FILTRAGE
    ================================ */
    .modern-filter-container {
        background: var(--surface);
        border-radius: var(--radius-large);
        box-shadow: var(--shadow-card);
        overflow: hidden;
        position: sticky;
        top: 20px;
        z-index: 100;
        margin-bottom: var(--space-xl);
    }

    .filter-sticky-header {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05));
        border-bottom: 1px solid rgba(var(--primary-rgb), 0.1);
        padding: var(--space-lg);
    }

    .filter-search-container {
        display: flex;
        gap: var(--space-md);
        align-items: center;
        margin-bottom: var(--space-md);
    }

    .search-input-wrapper {
        flex: 1;
        position: relative;
        max-width: 400px;
    }

    .search-input-wrapper .search-icon {
        position: absolute;
        left: var(--space-md);
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        z-index: 2;
    }

    .search-input-wrapper .form-input-moderne {
        padding-left: 40px;
        padding-right: 40px;
        background: var(--background);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        transition: all 0.3s ease;
        font-size: var(--text-normal);
    }

    .search-input-wrapper .form-input-moderne:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        background: white;
    }

    .clear-search-btn {
        position: absolute;
        right: var(--space-sm);
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        padding: var(--space-xs);
        border-radius: var(--radius-small);
        transition: all 0.2s ease;
    }

    .clear-search-btn:hover {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }

    #advanced-filters-toggle {
        white-space: nowrap;
        display: flex;
        align-items: center;
    }

    #advanced-filters-toggle .toggle-icon {
        transition: transform 0.3s ease;
    }

    #advanced-filters-toggle.active .toggle-icon {
        transform: rotate(180deg);
    }

    /* Zone des chips filtres actifs */
    .active-filters-chips {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding-top: var(--space-md);
        border-top: 1px solid rgba(var(--primary-rgb), 0.1);
        animation: slideDown 0.3s ease;
    }

    .chips-label {
        font-weight: 600;
        color: var(--text-secondary);
        font-size: var(--text-small);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .chips-container {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
        flex: 1;
    }

    .filter-chip {
        background: var(--primary);
        color: white;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: var(--text-small);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        animation: chipAppear 0.3s ease;
    }

    .filter-chip .remove-chip {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 10px;
    }

    .filter-chip .remove-chip:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .btn-clear-all {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--text-secondary);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-medium);
        font-size: var(--text-small);
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .btn-clear-all:hover {
        border-color: var(--danger);
        color: var(--danger);
        background: rgba(var(--danger-rgb), 0.05);
    }

    /* Drawer de filtres avancés */
    .advanced-filters-drawer {
        background: var(--background);
        border-top: 1px solid var(--border);
        padding: var(--space-xl);
        animation: slideDown 0.4s ease;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-xl);
        margin-bottom: var(--space-xl);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }

    .filter-label {
        font-weight: 600;
        color: var(--text-primary);
        font-size: var(--text-normal);
        display: flex;
        align-items: center;
    }

    .custom-select-wrapper {
        position: relative;
    }

    .custom-select {
        appearance: none;
        background: var(--surface);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md) 40px var(--space-md) var(--space-md);
        font-size: var(--text-normal);
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        font-weight: 500;
    }

    .custom-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        background: white;
    }

    .select-arrow {
        position: absolute;
        right: var(--space-md);
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        pointer-events: none;
        transition: transform 0.3s ease;
    }

    .custom-select:focus + .select-arrow {
        transform: translateY(-50%) rotate(180deg);
        color: var(--primary);
    }

    .filter-actions {
        grid-column: 1 / -1;
        display: flex;
        justify-content: center;
        gap: var(--space-md);
        padding-top: var(--space-lg);
        border-top: 1px solid var(--border);
    }

    .filter-loading {
        padding: var(--space-lg);
        text-align: center;
        color: var(--text-secondary);
        background: var(--background);
        border-top: 1px solid var(--border);
        animation: pulse 1.5s ease-in-out infinite;
    }

    .search-results-counter {
        position: absolute;
        top: var(--space-sm);
        right: var(--space-lg);
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: var(--text-small);
        font-weight: 600;
        animation: slideDown 0.3s ease;
    }

    /* ================================
       MODAL DE DISPONIBILITÉ ENSEIGNANT
    ================================ */
    .modal-xl {
        max-width: 1200px;
    }

    .weekly-calendar {
        background: var(--surface);
        border-radius: var(--radius-medium);
        overflow: hidden;
        border: 1px solid var(--border);
    }

    .calendar-header-row {
        display: grid;
        grid-template-columns: 80px repeat(6, 1fr);
        background: var(--background);
        border-bottom: 2px solid var(--border);
    }

    .calendar-hour-cell,
    .calendar-day-cell {
        padding: var(--space-md);
        text-align: center;
        font-weight: 600;
        color: var(--text-secondary);
        border-right: 1px solid var(--border);
    }

    .calendar-day-cell:last-child {
        border-right: none;
    }

    .calendar-time-slot {
        display: grid;
        grid-template-columns: 80px repeat(6, 1fr);
        border-bottom: 1px solid rgba(var(--border-rgb), 0.3);
        min-height: 60px;
    }

    .calendar-time-slot:hover {
        background: rgba(var(--primary-rgb), 0.02);
    }

    .time-label {
        padding: var(--space-sm);
        text-align: center;
        font-size: var(--text-small);
        color: var(--text-secondary);
        background: var(--background);
        border-right: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .calendar-cell {
        padding: var(--space-xs);
        border-right: 1px solid rgba(var(--border-rgb), 0.3);
        position: relative;
        min-height: 60px;
    }

    .calendar-cell:last-child {
        border-right: none;
    }

    .course-block {
        background: var(--primary);
        color: white;
        border-radius: var(--radius-small);
        padding: var(--space-xs);
        font-size: var(--text-small);
        margin: 2px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .course-block:hover {
        background: var(--secondary);
        transform: scale(1.02);
    }

    .course-block.conflict {
        background: var(--danger);
        animation: pulse 2s infinite;
    }

    .conflict-item {
        background: rgba(var(--warning-rgb), 0.1);
        border: 1px solid var(--warning);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-md);
    }

    .conflict-item .conflict-header {
        display: flex;
        align-items: center;
        justify-content: between;
        margin-bottom: var(--space-sm);
    }

    .conflict-severity {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: var(--text-small);
        font-weight: 600;
    }

    .conflict-severity.high {
        background: var(--danger);
        color: white;
    }

    .conflict-severity.medium {
        background: var(--warning);
        color: var(--text-primary);
    }

    .conflict-severity.low {
        background: var(--info);
        color: white;
    }

    .suggestion-card {
        background: rgba(var(--success-rgb), 0.1);
        border: 1px solid var(--success);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .suggestion-card:hover {
        background: rgba(var(--success-rgb), 0.15);
        transform: translateY(-2px);
        box-shadow: var(--shadow-card);
    }

    .days-selector {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-sm);
    }

    .days-selector .form-check {
        margin: 0;
    }

    .unavailability-slot {
        background: rgba(var(--danger-rgb), 0.1);
        border: 1px solid var(--danger);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        justify-content: between;
    }

    /* Animations */
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes chipAppear {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-search-container {
            flex-direction: column;
            align-items: stretch;
        }

        .search-input-wrapper {
            max-width: none;
        }

        .filters-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .filter-actions {
            flex-direction: column;
        }

        .weekly-calendar {
            overflow-x: auto;
        }

        .calendar-header-row,
        .calendar-time-slot {
            min-width: 600px;
        }

        .chips-container {
            flex-direction: column;
            align-items: flex-start;
        }

        .active-filters-chips {
            flex-direction: column;
            align-items: stretch;
        }
    }

    /* ================================
       STYLES POUR LES CARTES DE CONFIGURATION MODERNES
    ================================ */

    /* Grid des combinaisons */
    .combinaisons-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    /* Carte de combinaison moderne */
    .combinaison-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        position: relative;
        min-height: 220px;
        display: flex;
        flex-direction: column;
    }

    .combinaison-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15), 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    /* Header section avec logo et statut */
    .card-header-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 1.5rem 1.5rem 1rem 1.5rem;
    }

    .card-logo-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
    }

    .school-logo {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);
    }

    .filiere-niveau-info {
        flex: 1;
        min-width: 0;
    }

    .filiere-name {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }

    .niveau-name {
        font-size: 0.875rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    /* Badge de statut moderne */
    .status-badge {
        padding: 0.5rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        min-width: 32px;
        height: 32px;
        flex-shrink: 0;
    }

    .status-badge.configured {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .status-badge.partial {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .status-badge.not-configured {
        background: rgba(156, 163, 175, 0.1);
        color: #6b7280;
    }

    /* Section du corps avec statistiques */
    .card-body-section {
        flex: 1;
        padding: 0 1.5rem 1rem 1.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: rgba(var(--primary-rgb), 0.03);
        border-radius: 12px;
        border: 1px solid rgba(var(--primary-rgb), 0.08);
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .stat-number-lines {
        font-size: 1rem;
        line-height: 1.3;
        margin-bottom: 0.5rem;
    }

    .stat-line {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }

    .stat-pill {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 0.15rem 0.4rem;
        border-radius: 999px;
        background: rgba(var(--primary-rgb), 0.12);
        color: var(--primary);
    }

    .stat-description {
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }

    /* Section footer avec bouton */
    .card-footer-section {
        padding: 1rem 1.5rem 1.5rem 1.5rem;
        margin-top: auto;
    }

    .btn-configure-modern {
        width: 100%;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.875rem 1rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-configure-modern:hover {
        background: var(--secondary);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.3);
    }

    .btn-configure-modern:active {
        transform: translateY(0);
    }

    /* Légende moderne */
    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .legend-badge {
        padding: 0.25rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        min-width: 20px;
        height: 20px;
    }

    .legend-badge.configured {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .legend-badge.partial {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .legend-badge.not-configured {
        background: rgba(156, 163, 175, 0.1);
        color: #6b7280;
    }

    /* ================================
       STYLES POUR LE MODAL DE CONFIGURATION
    ================================ */

    .config-matiere-card {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: var(--space-lg);
        padding: var(--space-lg);
        border: 1px solid var(--border);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-md);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--surface);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .config-matiere-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15);
        transform: translateY(-1px);
    }

    .config-matiere-card.configured {
        border-color: var(--success);
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
    }

    .config-matiere-card.configured::before {
        content: '✓';
        position: absolute;
        top: var(--space-sm);
        right: var(--space-sm);
        width: 24px;
        height: 24px;
        background: var(--success);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }

    .config-matiere-card {
        position: relative;
    }

    .matiere-details {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }

    .matiere-name {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.2;
    }

    .matiere-description {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        line-height: 1.4;
        margin: 0;
    }

    .matiere-config {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
        min-width: 280px;
        padding: var(--space-md);
        background: rgba(var(--primary-rgb), 0.02);
        border: 1px solid rgba(var(--primary-rgb), 0.1);
        border-radius: var(--radius-medium);
    }

    .config-section {
        display: flex;
        flex-direction: column;
        gap: var(--space-xs);
    }

    .config-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .config-label i {
        color: var(--primary);
    }

    .volume-config {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .teacher-config .form-select {
        font-size: 0.85rem;
        border-radius: var(--radius-small);
        min-height: 38px;
    }

    .volume-config-section {
        padding-bottom: var(--space-sm);
        border-bottom: 1px solid rgba(var(--border-rgb), 0.3);
    }

    .teacher-config-section {
        padding-top: var(--space-sm);
    }

    /* Conteneur des checkboxes enseignants */
    .teacher-checkboxes-container {
        max-height: 250px;
        overflow-y: auto;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 8px;
        background-color: #fafafa;
    }

    /* Item checkbox individuel */
    .teacher-checkbox-item {
        margin-bottom: 4px;
    }

    /* Label cliquable entier */
    .teacher-checkbox-label {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
        border: 1px solid #e0e0e0;
        margin: 0;
    }

    .teacher-checkbox-label:hover {
        background: #f8f9fa;
        border-color: var(--primary);
        transform: translateX(3px);
    }

    /* Checkbox input caché - SEULEMENT pour l'ancien système avec teacher-checkbox-label */
    .teacher-checkbox-label .teacher-checkbox {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    /* Checkboxes du tableau - visibles et normales */
    .teacher-selection-table .teacher-checkbox {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }

    /* Checkbox header - un peu plus grande */
    .teacher-select-all-checkbox {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }

    /* Checkbox custom visuel */
    .teacher-checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #d0d0d0;
        border-radius: 4px;
        margin-right: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.2s ease;
        background: white;
    }

    /* Checkmark quand coché - Carré plein avec coche blanche */
    .teacher-checkbox:checked ~ .teacher-checkbox-custom {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        border-color: #0d6efd;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    }

    .teacher-checkbox:checked ~ .teacher-checkbox-custom::after {
        content: '\f00c';  /* FontAwesome check icon */
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        color: white;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }

    /* Highlight du label quand coché */
    .teacher-checkbox:checked ~ .teacher-name {
        font-weight: 600;
        color: var(--primary);
    }

    /* Nom de l'enseignant */
    .teacher-name {
        flex: 1;
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }

    /* Spécialisation */
    .teacher-spec {
        font-size: 12px;
        color: #666;
        font-style: italic;
        margin-left: 8px;
    }

    /* Compteur de sélection */
    .teacher-selection-count {
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .teacher-selection-count.has-selection {
        background: #d4edda !important;
        border: 1px solid #c3e6cb;
    }

    .teacher-selection-count.has-selection .count-text {
        color: #155724;
        font-weight: 600;
    }

    .volume-input {
        width: 80px;
        text-align: center;
    }

    .volume-unit {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        font-weight: 500;
    }

    /* Responsive pour les cartes modernes */
    @media (max-width: 992px) {
        .combinaisons-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .combinaisons-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .combinaison-card {
            min-height: 200px;
        }

        .card-header-section {
            padding: 1.25rem 1.25rem 0.75rem 1.25rem;
        }

        .card-body-section {
            padding: 0 1.25rem 0.75rem 1.25rem;
        }

        .card-footer-section {
            padding: 0.75rem 1.25rem 1.25rem 1.25rem;
        }

        .school-logo {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }

        .filiere-name {
            font-size: 0.9rem;
        }

        .niveau-name {
            font-size: 0.8rem;
        }

        .stat-number {
            font-size: 1.25rem;
        }

        .stat-description {
            font-size: 0.7rem;
        }
    }

    @media (max-width: 480px) {
        .combinaison-card {
            min-height: 180px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .stat-item {
            padding: 0.75rem;
        }

        .card-logo-info {
            gap: 0.75rem;
        }
    }

        .config-matiere-card {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .matiere-config {
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header avec hero + KPIs + tabs -->
        <x-planning-header
            title="Planning Général"
            subtitle="Vue d'ensemble du planning académique et organisation des cours"
            active-tab="overview"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
            :stats="$anneeSelectionnee ? $stats : null"
        />

        <div id="pg-tab-content">
        @if(!$anneeSelectionnee)
            <div class="alert alert-warning" style="border-radius:12px; border:none; background:#fef3c7; color:#92400e;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Aucune année universitaire sélectionnée. Veuillez en choisir une pour afficher le planning.
            </div>
        @else
            <!-- Configuration des Volumes Horaires par Combinaison -->
            <div class="pg-section" style="background:#fff; border-radius:14px; border:1px solid #e8ecf1; box-shadow:0 1px 3px rgba(0,0,0,.04); padding:1.5rem; margin-bottom:1.25rem;">
                {{-- Section header --}}
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem;">
                    <div style="display:flex; align-items:center; gap:.75rem;">
                        <div style="width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#0453cb,#3b7ddb); display:flex; align-items:center; justify-content:center; color:#fff; font-size:.95rem; flex-shrink:0;">
                            <i class="fas fa-th-large"></i>
                        </div>
                        <div>
                            <div style="font-size:1.05rem; font-weight:700; color:#1e293b;">Configuration des Volumes Horaires</div>
                            <div style="font-size:.8rem; color:#64748b;">Par combinaison filière / niveau — {{ $anneeSelectionnee->name }}</div>
                        </div>
                    </div>
                    <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
                        <span style="display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; color:#10b981;"><i class="fas fa-check-circle" style="font-size:.65rem;"></i> Complet</span>
                        <span style="display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; color:#f59e0b;"><i class="fas fa-exclamation-triangle" style="font-size:.65rem;"></i> Partiel</span>
                        <span style="display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; color:#94a3b8;"><i class="fas fa-plus-circle" style="font-size:.65rem;"></i> Non configuré</span>
                    </div>
                </div>

                {{-- Filtres inline --}}
                <form method="GET" action="{{ route('esbtp.planning-general.index') }}" id="filters-form">
                    <div style="display:flex; gap:.75rem; margin-bottom:1.25rem; flex-wrap:wrap;">
                        <div style="flex:1; min-width:180px;">
                            <select name="annee_id" id="annee_selector" class="form-select" style="border-radius:10px; border-color:#e2e8f0; font-size:.85rem;" onchange="document.getElementById('filters-form').submit()">
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id }}" {{ ($anneeSelectionnee && $anneeSelectionnee->id == $annee->id) ? 'selected' : '' }}>
                                        {{ $annee->name }}
                                        @if(optional($annee)->is_current) (En cours) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex:1; min-width:180px;">
                            <select name="filiere_filter" id="filiere_filter" class="form-select" style="border-radius:10px; border-color:#e2e8f0; font-size:.85rem;" onchange="document.getElementById('filters-form').submit()">
                                <option value="">Toutes les filières</option>
                                @php $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->orderBy('name')->get(); @endphp
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_filter') == $filiere->id ? 'selected' : '' }}>{{ $filiere->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex:1; min-width:180px;">
                            <select name="niveau_filter" id="niveau_filter" class="form-select" style="border-radius:10px; border-color:#e2e8f0; font-size:.85rem;" onchange="document.getElementById('filters-form').submit()">
                                <option value="">Tous les niveaux</option>
                                @php $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->orderBy('year')->get(); @endphp
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_filter') == $niveau->id ? 'selected' : '' }}>{{ $niveau->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>

                    <!-- Cards des combinaisons filière/niveau -->
                    <div class="combinaisons-grid">
                        @foreach($combinaisons as $combinaison)
                            <div class="combinaison-card {{ $combinaison['status_class'] }}"
                                 data-filiere-id="{{ $combinaison['filiere']->id }}"
                                 data-niveau-id="{{ $combinaison['niveau']->id }}"
                                 data-combinaison-name="{{ $combinaison['name'] }}">

                                <!-- Header avec logo école et badge statut -->
                                <div class="card-header-section">
                                    <div class="card-logo-info">
                                        <div class="school-logo">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="filiere-niveau-info">
                                            <div class="filiere-name">{{ $combinaison['filiere']->name }}</div>
                                            <div class="niveau-name">{{ $combinaison['niveau']->name }}</div>
                                        </div>
                                    </div>
                                    <div class="status-badge {{ $combinaison['status_class'] }}">
                                        <i class="fas {{ $combinaison['status_icon'] }}"></i>
                                    </div>
                                </div>

                                <!-- Corps de la carte avec statistiques -->
                                <div class="card-body-section">
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <div class="stat-number stat-number-lines">
                                                <div class="stat-line">
                                                    <span class="stat-pill">S1</span>
                                                    <span>{{ $combinaison['matieres_configurees_s1'] }}/{{ $combinaison['total_matieres'] }}</span>
                                                </div>
                                                <div class="stat-line">
                                                    <span class="stat-pill">S2</span>
                                                    <span>{{ $combinaison['matieres_configurees_s2'] }}/{{ $combinaison['total_matieres'] }}</span>
                                                </div>
                                            </div>
                                            <div class="stat-description">Matières configurées</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-number stat-number-lines">
                                                <div class="stat-line">
                                                    <span class="stat-pill">S1</span>
                                                    <span>{{ $combinaison['total_heures_s1'] }}h</span>
                                                </div>
                                                <div class="stat-line">
                                                    <span class="stat-pill">S2</span>
                                                    <span>{{ $combinaison['total_heures_s2'] }}h</span>
                                                </div>
                                            </div>
                                            <div class="stat-description">Volume horaire total</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer avec bouton de configuration -->
                                <div class="card-footer-section">
                                    @if($combinaison['total_matieres'] == 0)
                                        <!-- Bouton pour ajouter des matières à une combinaison vide -->
                                        <button class="btn-configure-modern add-subjects-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addMatieresModal"
                                                data-empty-combo="true"
                                                data-filiere-id="{{ $combinaison['filiere']->id }}"
                                                data-niveau-id="{{ $combinaison['niveau']->id }}"
                                                data-filiere-name="{{ $combinaison['filiere']->name }}"
                                                data-niveau-name="{{ $combinaison['niveau']->name }}"
                                                style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                            <i class="fas fa-plus me-2"></i>Ajouter des matières
                                        </button>
                                    @else
                                        <!-- Combinaison avec matières : petit bouton + et grand bouton configurer -->
                                        <div class="d-flex gap-2 w-100">
                                            <!-- Petit bouton pour ajouter encore des matières -->
                                            <button class="btn btn-success btn-sm add-subjects-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addMatieresModal"
                                                    data-empty-combo="true"
                                                    data-filiere-id="{{ $combinaison['filiere']->id }}"
                                                    data-niveau-id="{{ $combinaison['niveau']->id }}"
                                                    data-filiere-name="{{ $combinaison['filiere']->name }}"
                                                    data-niveau-name="{{ $combinaison['niveau']->name }}"
                                                    title="Ajouter d'autres matières"
                                                    style="min-width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-plus"></i>
                                            </button>

                                            <!-- Grand bouton principal pour configurer les volumes -->
                                            <button class="btn-configure-modern flex-grow-1" data-bs-toggle="modal" data-bs-target="#volumeConfigModal">
                                                <i class="fas fa-cog me-2"></i>Configurer les volumes
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

            </div>

            <!-- Actions rapides -->
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-bolt me-2"></i>
                        Actions Rapides
                    </div>

                    <div class="quick-actions">
                        <a href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon calendar">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h6 class="font-semibold">Planning Annuel</h6>
                                <p class="text-muted mb-0">Visualisez le calendrier complet de l'année académique</p>
                            </div>
                        </a>

                        <a href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon chart">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <h6 class="font-semibold">Répartition Matières</h6>
                                <p class="text-muted mb-0">Analysez la distribution des heures par matière</p>
                            </div>
                        </a>

                        <a href="{{ route('esbtp.emploi-temps.index') }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon users">
                                    <i class="fas fa-table"></i>
                                </div>
                                <h6 class="font-semibold">Emplois du Temps</h6>
                                <p class="text-muted mb-0">Gérez les emplois du temps par classe</p>
                            </div>
                        </a>

                        <a href="{{ route('esbtp.planning-general.impact-emargements', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon settings" style="background: linear-gradient(135deg, #10b981, #34d399);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h6 class="font-semibold">Impact Émargements</h6>
                                <p class="text-muted mb-0">Visualisez l'impact des émargements sur la progression</p>
                            </div>
                        </a>

                        @canany(['manage-planning', 'view-all-timetables'])
                        <a href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon settings">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h6 class="font-semibold">Gestion Avancée</h6>
                                <p class="text-muted mb-0">Outils de coordination et d'administration</p>
                            </div>
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
        @endif
        </div>{{-- #pg-tab-content --}}
    </div>
</div>

<!-- Modal de Configuration des Volumes Horaires -->
<div class="modal fade" id="volumeConfigModal" tabindex="-1" aria-labelledby="volumeConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:none; border-radius:18px; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,.15);">
            {{-- Header premium --}}
            <div class="modal-header" style="background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border:none; padding:1.5rem 1.75rem 1.25rem; position:relative;">
                <div style="position:relative; z-index:1;">
                    <div style="display:flex; align-items:center; gap:.75rem;">
                        <div style="width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,.12); display:flex; align-items:center; justify-content:center; font-size:.9rem; color:#fff; border:1px solid rgba(255,255,255,.15);">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" id="volumeConfigModalLabel" style="color:#fff; font-weight:700; font-size:1.1rem; margin:0;">
                                Configuration des Volumes Horaires
                            </h5>
                            <div style="font-size:.78rem; color:rgba(255,255,255,.7); margin-top:.15rem;" id="config-combination-name">—</div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:brightness(0) invert(1); opacity:.7;"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body" style="padding:1.5rem 1.75rem; background:#f8fafc;">
                <form id="volume-config-form">
                    <input type="hidden" id="config-filiere-id" name="filiere_id">
                    <input type="hidden" id="config-niveau-id" name="niveau_id">
                    <input type="hidden" id="config-annee-id" name="annee_id" value="{{ $anneeSelectionnee ? $anneeSelectionnee->id : '' }}">

                    {{-- Semestre selector --}}
                    <div style="background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:1rem 1.25rem; margin-bottom:1.25rem;">
                        <label for="config-semestre" style="font-size:.78rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; display:flex; align-items:center; gap:.4rem; margin-bottom:.5rem;">
                            <i class="fas fa-calendar-alt" style="color:#0453cb;"></i>Semestre
                        </label>
                        <div style="display:flex; gap:.5rem;">
                            <select id="config-semestre" name="semestre" class="form-select" style="border-radius:10px; border-color:#e2e8f0; font-size:.88rem; max-width:200px;">
                                <option value="1" selected>Semestre 1</option>
                                <option value="2">Semestre 2</option>
                            </select>
                        </div>
                        <div style="font-size:.72rem; color:#94a3b8; margin-top:.4rem;">La configuration s'applique au semestre sélectionné</div>
                    </div>

                    {{-- Loading --}}
                    <div class="config-loading text-center py-4" id="config-loading" style="display: none;">
                        <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#0453cb,#3b7ddb); display:inline-flex; align-items:center; justify-content:center; color:#fff; margin-bottom:.75rem;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <p style="color:#64748b; font-size:.88rem; margin:0;">Chargement des matières...</p>
                    </div>

                    {{-- Matières container (AJAX) --}}
                    <div id="matieres-container">
                    </div>
                </form>
            </div>

            {{-- Footer premium --}}
            <div class="modal-footer" style="border-top:1px solid #e8ecf1; padding:1rem 1.75rem; background:#fff; gap:.5rem;">
                <button type="button" class="btn" data-bs-dismiss="modal" style="border-radius:10px; padding:.55rem 1.25rem; font-size:.85rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; transition:all .2s;">
                    <i class="fas fa-times me-1" style="font-size:.75rem;"></i>Annuler
                </button>
                <button type="button" class="btn" id="save-volume-config" style="border-radius:10px; padding:.55rem 1.25rem; font-size:.85rem; font-weight:600; color:#fff; background:linear-gradient(135deg,#0453cb,#3b7ddb); border:none; box-shadow:0 2px 8px rgba(4,83,203,.25); transition:all .2s;">
                    <i class="fas fa-save me-1" style="font-size:.75rem;"></i>Sauvegarder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Gestion de Disponibilité Enseignant -->
<div class="modal fade" id="teacherAvailabilityModal" tabindex="-1" aria-labelledby="teacherAvailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="teacherAvailabilityModalLabel">
                    <i class="fas fa-calendar-user me-2"></i>
                    Disponibilité de <span id="modal-teacher-name">l'enseignant</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Navigation tabs -->
                <ul class="nav nav-tabs mb-4" id="availabilityTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
                                data-bs-target="#overview" type="button" role="tab">
                            <i class="fas fa-chart-pie me-1"></i>Aperçu
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="calendar-tab" data-bs-toggle="tab"
                                data-bs-target="#calendar" type="button" role="tab">
                            <i class="fas fa-calendar-alt me-1"></i>Calendrier
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="conflicts-tab" data-bs-toggle="tab"
                                data-bs-target="#conflicts" type="button" role="tab">
                            <i class="fas fa-exclamation-triangle me-1"></i>Conflits
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="preferences-tab" data-bs-toggle="tab"
                                data-bs-target="#preferences" type="button" role="tab">
                            <i class="fas fa-cog me-1"></i>Préférences
                        </button>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content" id="availabilityTabContent">
                    <!-- Aperçu -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <div class="row">
                            <!-- KPI Cards -->
                            <div class="col-md-3">
                                <div class="card bg-primary text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock fa-2x mb-2"></i>
                                        <h4 id="total-hours">0h</h4>
                                        <small>Heures assignées</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <h4 id="available-slots">0</h4>
                                        <small>Créneaux libres</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                        <h4 id="conflicts-count">0</h4>
                                        <small>Conflits détectés</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-percentage fa-2x mb-2"></i>
                                        <h4 id="load-percentage">0%</h4>
                                        <small>Taux de charge</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Matières assignées -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6><i class="fas fa-book me-2"></i>Matières assignées cette année</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm" id="assigned-subjects-table">
                                        <thead>
                                            <tr>
                                                <th>Matière</th>
                                                <th>Filière/Niveau</th>
                                                <th>Volume horaire</th>
                                                <th>Progression</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendrier -->
                    <div class="tab-pane fade" id="calendar" role="tabpanel">
                        <div class="calendar-container">
                            <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar me-2"></i>
                                    Planning de <span id="calendar-teacher-name">l'enseignant</span>
                                </h6>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="prev-week">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="today-btn">
                                        Aujourd'hui
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="next-week">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Vue calendrier hebdomadaire -->
                            <div class="weekly-calendar" id="weekly-calendar">
                                <!-- Generated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Conflits -->
                    <div class="tab-pane fade" id="conflicts" role="tabpanel">
                        <div class="conflicts-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    Conflits d'horaires détectés
                                </h6>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="refresh-conflicts">
                                    <i class="fas fa-sync-alt me-1"></i>Actualiser
                                </button>
                            </div>

                            <div id="conflicts-list">
                                <!-- Populated by JavaScript -->
                            </div>

                            <!-- Solutions suggérées -->
                            <div class="mt-4">
                                <h6><i class="fas fa-lightbulb text-warning me-2"></i>Solutions suggérées</h6>
                                <div id="suggested-solutions">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Préférences -->
                    <div class="tab-pane fade" id="preferences" role="tabpanel">
                        <form id="teacher-preferences-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-clock me-2"></i>Contraintes horaires</h6>

                                    <div class="mb-3">
                                        <label class="form-label">Charge horaire maximale par semaine</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="max-hours-per-week"
                                                   min="1" max="40" value="20">
                                            <span class="input-group-text">heures</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Heures de début préférées</label>
                                        <select class="form-select" id="preferred-start-times" multiple>
                                            <option value="08:00">08:00</option>
                                            <option value="09:00">09:00</option>
                                            <option value="10:00">10:00</option>
                                            <option value="11:00">11:00</option>
                                            <option value="13:00">13:00</option>
                                            <option value="14:00">14:00</option>
                                            <option value="15:00">15:00</option>
                                            <option value="16:00">16:00</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6><i class="fas fa-calendar-times me-2"></i>Indisponibilités</h6>

                                    <!-- Jours indisponibles -->
                                    <div class="mb-3">
                                        <label class="form-label">Jours non disponibles</label>
                                        <div class="days-selector">
                                            @foreach(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'] as $day)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="unavailable-{{ strtolower($day) }}"
                                                           value="{{ strtolower($day) }}">
                                                    <label class="form-check-label" for="unavailable-{{ strtolower($day) }}">
                                                        {{ $day }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Créneaux spécifiques indisponibles -->
                                    <div class="mb-3">
                                        <label class="form-label">Créneaux spécifiques indisponibles</label>
                                        <div id="specific-unavailabilities">
                                            <!-- Dynamic content -->
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-unavailability">
                                            <i class="fas fa-plus me-1"></i>Ajouter un créneau
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
                <button type="button" class="btn btn-primary" id="save-preferences">
                    <i class="fas fa-save me-1"></i>Sauvegarder les préférences
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout de matières aux combinaisons vides -->
<div class="modal fade" id="addMatieresModal" tabindex="-1" aria-labelledby="addMatieresModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="addMatieresModalLabel" style="font-weight: 600;">
                        <i class="fas fa-plus me-2"></i>Ajouter matières à la combinaison
                    </h4>
                    <p class="mb-0" style="opacity: 0.9; font-size: 0.9rem;">
                        Matière : <span id="modal-matiere-name" style="font-weight: 500;"></span>
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <form id="configureLiaisonsForm">
                    @csrf
                    <input type="hidden" id="modal-matiere-id" name="matiere_id">

                    <div class="row">
                        <!-- Filières disponibles -->
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-graduation-cap"></i>Filières
                                    </h3>
                                    <p class="main-card-subtitle" id="filiere-subtitle">Sélectionnez les filières concernées</p>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group">
                                        <div id="filieres-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                            @foreach(\App\Models\ESBTPFiliere::where('is_active', true)->get() as $filiere)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input filiere-checkbox" type="checkbox"
                                                       value="{{ $filiere->id }}" id="filiere-{{ $filiere->id }}" name="filieres[]"
                                                       style="margin-top: 0.35rem;">
                                                <label class="form-check-label" for="filiere-{{ $filiere->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $filiere->name }}</span>
                                                            @if($filiere->code)
                                                                <span class="badge secondary ms-2">{{ $filiere->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Niveaux disponibles -->
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-layer-group"></i>Niveaux d'étude
                                    </h3>
                                    <p class="main-card-subtitle" id="niveau-subtitle">Sélectionnez les niveaux concernés</p>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group">
                                        <div id="niveaux-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                            @foreach(\App\Models\ESBTPNiveauEtude::where('is_active', true)->get() as $niveau)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input niveau-checkbox" type="checkbox"
                                                       value="{{ $niveau->id }}" id="niveau-{{ $niveau->id }}" name="niveaux[]"
                                                       style="margin-top: 0.35rem;">
                                                <label class="form-check-label" for="niveau-{{ $niveau->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $niveau->name }}</span>
                                                            @if($niveau->code)
                                                                <span class="badge secondary ms-2">{{ $niveau->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sélection des matières (pour les combinaisons vides) -->
                    <div class="row mt-4" id="matieres-selection-container" style="display: none;">
                        <div class="col-12">
                            <div class="card-moderne">
                                <div class="main-card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="main-card-title">
                                            <i class="fas fa-book"></i>Matières disponibles
                                        </h3>
                                        <p class="main-card-subtitle">Sélectionnez les matières à ajouter à cette combinaison</p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-view-details">
                                            <i class="fas fa-eye me-1"></i>Vue détaillée des matières
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" id="btn-create-new">
                                            <i class="fas fa-plus me-1"></i>Créer nouvelle matière
                                        </button>
                                    </div>
                                </div>
                                <div class="main-card-body">
                                    <div id="matieres-list" style="max-height: 400px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                        <!-- Les matières seront chargées ici dynamiquement -->
                                    </div>

                                    <!-- Actions de sélection rapide -->
                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <small>Les matières déjà assignées sont marquées en vert</small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="btn-select-all">
                                                <i class="fas fa-check-square me-1"></i>Tout sélectionner
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-select-none">
                                                <i class="fas fa-square me-1"></i>Tout désélectionner
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aperçu des combinaisons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-eye"></i>Aperçu des combinaisons
                                    </h3>
                                    <p class="main-card-subtitle">Combinaisons filières/niveaux sélectionnées</p>
                                </div>
                                <div class="main-card-body">
                                    <div id="combinations-preview" class="card-moderne" style="background: #e7f3ff; border: 1px solid #0ea5e9; padding: 1.5rem; border-radius: 8px;">
                                        <div class="d-flex align-items-center" style="color: #0369a1;">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <span>Sélectionnez des filières et des niveaux pour voir les combinaisons possibles.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-light); padding: 1.5rem 2rem; background: var(--bg-light); border-radius: 0 0 12px 12px;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <small>Les modifications seront sauvegardées immédiatement</small>
                    </div>
                    <div>
                        <button type="button" class="btn-acasi secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="button" class="btn-acasi primary" id="save-liaisons-btn">
                            <i class="fas fa-save me-1"></i>Enregistrer les liaisons
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de création rapide d'enseignant (configuration volumes horaires) -->
<div class="modal fade" id="teacherCreateModal" tabindex="-1" aria-labelledby="teacherCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="teacherCreateModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Créer un enseignant
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="teacherCreateFormPlanning" action="{{ route('esbtp.enseignants.quick-create') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" id="teacherCreateErrorsPlanning" style="display: none;"></div>

                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Cet enseignant sera créé sans rattachement au planning général.
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted small">
                            Matière en cours de configuration :
                            <strong id="teacher_create_matiere_label">-</strong>
                        </div>
                    </div>

                    @php
                        $teacherDepartments = \App\Models\ESBTPDepartment::where('is_active', true)->orderBy('name')->get();
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Spécialisation <span class="text-danger">*</span></label>
                            <input type="text" name="specialization" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Département <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Sélectionner</option>
                                @foreach($teacherDepartments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de contrat <span class="text-danger">*</span></label>
                            <select name="type_contrat" class="form-select" required>
                                <option value="">Sélectionner</option>
                                <option value="permanent">Permanent</option>
                                <option value="temporaire">Temporaire</option>
                                <option value="vacataire">Vacataire</option>
                                <option value="consultant">Consultant</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut d'emploi <span class="text-danger">*</span></label>
                            <select name="statut_emploi" class="form-select" required>
                                <option value="">Sélectionner</option>
                                <option value="temps_plein">Temps Plein</option>
                                <option value="temps_partiel">Temps Partiel</option>
                                <option value="vacations">Vacations</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'embauche <span class="text-danger">*</span></label>
                            <input type="date" name="date_embauche" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Charge horaire max/semaine</label>
                            <input type="number" name="charge_horaire_max_semaine" class="form-control" min="1" max="60" value="40">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="teacherCreateSubmitPlanning">
                        <i class="fas fa-save me-1"></i>Créer l'enseignant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // ================================
    // ANIMATION DES CARTES AU SCROLL
    // ================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, observerOptions);

    // Observer toutes les cartes
    $('.card-moderne').each(function() {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)',
            'transition': 'all 0.6s ease-out'
        });
        observer.observe(this);
    });

    // ================================
    // CONFIGURATION DES VOLUMES HORAIRES
    // ================================

    // Variables globales pour le modal
    let currentFiliereId = null;
    let currentNiveauId = null;
    let currentCombinaisonName = '';

    // Ouverture du modal de configuration
    $(document).on('click', '.btn-configure-modern', function() {
        const $card = $(this).closest('.combinaison-card');
        currentFiliereId = $card.data('filiere-id');
        currentNiveauId = $card.data('niveau-id');
        currentCombinaisonName = $card.data('combinaison-name');

        // Mettre à jour le titre du modal
        $('#config-combination-name').text(currentCombinaisonName);
        $('#config-filiere-id').val(currentFiliereId);
        $('#config-niveau-id').val(currentNiveauId);

        // Charger les matières pour cette combinaison
        loadMatieresForConfiguration();
    });

    $('#config-semestre').on('change', function() {
        if (currentFiliereId && currentNiveauId && $('#config-annee-id').val()) {
            loadMatieresForConfiguration();
        }
    });

    // ================================
    // CRÉATION RAPIDE D'ENSEIGNANT (AJAX)
    // ================================
    $(document).on('click', '.create-teacher-btn', function() {
        const $card = $(this).closest('.config-matiere-card');
        const matiereName = $card.find('.matiere-name').text().trim();
        $('#teacher_create_matiere_label').text(matiereName || '-');
        $('#teacherCreateErrorsPlanning').hide().html('');
        const form = document.getElementById('teacherCreateFormPlanning');
        if (form) {
            form.reset();
        }
    });

    $(document).on('submit', '#teacherCreateFormPlanning', function(event) {
        event.preventDefault();
        const form = event.target;
        const submitBtn = document.getElementById('teacherCreateSubmitPlanning');
        const errorBox = document.getElementById('teacherCreateErrorsPlanning');
        if (!form || !submitBtn) {
            return;
        }

        if (errorBox) {
            errorBox.style.display = 'none';
            errorBox.innerHTML = '';
        }

        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Création...';

        const formData = new FormData(form);

        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload.success) {
                    throw payload;
                }
                return payload;
            })
            .then(() => {
                showAlert('success', "Enseignant créé avec succès.");
                if (currentFiliereId && currentNiveauId && $('#config-annee-id').val()) {
                    loadMatieresForConfiguration();
                }
                const modalElement = document.getElementById('teacherCreateModal');
                if (modalElement) {
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                form.reset();
            })
            .catch(error => {
                if (!errorBox) {
                    showAlert('error', "Impossible de créer l'enseignant.");
                    return;
                }
                const messages = [];
                if (error && error.errors) {
                    Object.values(error.errors).forEach(list => {
                        list.forEach(item => messages.push(`<li>${item}</li>`));
                    });
                }
                if (messages.length === 0) {
                    messages.push("<li>Impossible de créer l'enseignant. Vérifiez les champs.</li>");
                }
                errorBox.innerHTML = `<ul class="mb-0">${messages.join('')}</ul>`;
                errorBox.style.display = 'block';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    });

    // Fonction pour charger les matières via AJAX
    function loadMatieresForConfiguration() {
        const anneeId = $('#config-annee-id').val();

        if (!currentFiliereId || !currentNiveauId || !anneeId) {
            showAlert('error', 'Données manquantes pour charger les matières');
            return;
        }

        // Afficher le loading
        $('#config-loading').show();
        $('#matieres-container').hide();

        $.ajax({
            url: '{{ route("esbtp.planning-general.get-matieres-configuration") }}',
            method: 'GET',
            data: {
                filiere_id: currentFiliereId,
                niveau_id: currentNiveauId,
                annee_id: anneeId,
                semestre: $('#config-semestre').val()
            },
            success: function(response) {
                $('#config-loading').hide();

                if (response.success) {
                    $('#matieres-container').html(response.html).show();

                    // Ajouter les event listeners sur les inputs
                    $('.volume-input').on('input', function() {
                        const $card = $(this).closest('.config-matiere-card');
                        const value = parseInt($(this).val()) || 0;

                        if (value > 0) {
                            $card.addClass('configured');
                        } else {
                            $card.removeClass('configured');
                        }
                    });

                    // Gérer les tableaux de professeurs avec compteur dynamique
                    debugLog('🔍 Initialisation teacher tables...');

                    // Fonction pour mettre à jour le compteur et l'état du checkbox header
                    function updateTeacherCount(matiereId) {
                        debugLog('📊 updateTeacherCount called for matiere:', matiereId);

                        const $tableContainer = $('.teacher-table-container[data-matiere-id="' + matiereId + '"]');
                        const $allCheckboxes = $tableContainer.find('.teacher-checkbox');
                        const $visibleRows = $tableContainer.find('.teacher-row:visible');
                        const $visibleCheckboxes = $visibleRows.find('.teacher-checkbox');
                        const $headerCheckbox = $('.teacher-select-all-checkbox[data-matiere-id="' + matiereId + '"]');
                        const $countDiv = $('.teacher-selection-count[data-matiere-id="' + matiereId + '"]');
                        const $countText = $countDiv.find('.count-text');

                        const totalCount = $allCheckboxes.length;
                        const visibleCount = $visibleCheckboxes.length;
                        const selectedCount = $allCheckboxes.filter(':checked').length;
                        const visibleSelectedCount = $visibleCheckboxes.filter(':checked').length;

                        debugLog('  📈 Total teachers:', totalCount);
                        debugLog('  👁️ Visible teachers:', visibleCount);
                        debugLog('  ✅ Total selected:', selectedCount);
                        debugLog('  ✅ Visible selected:', visibleSelectedCount);

                        // Mettre à jour le compteur
                        if (selectedCount > 0) {
                            $countDiv.addClass('has-selection');
                            $countText.html('<i class="fas fa-check-circle"></i> ' + selectedCount + ' enseignant(s) sélectionné(s) sur ' + totalCount);
                        } else {
                            $countDiv.removeClass('has-selection');
                            $countText.html('Sélectionnez un ou plusieurs enseignants');
                        }

                        // Mettre à jour l'état du checkbox header (basé sur lignes VISIBLES)
                        if (visibleCount === 0) {
                            $headerCheckbox.prop('checked', false);
                            $headerCheckbox.prop('indeterminate', false);
                            debugLog('  🔲 Header: unchecked (no visible rows)');
                        } else if (visibleSelectedCount === 0) {
                            $headerCheckbox.prop('checked', false);
                            $headerCheckbox.prop('indeterminate', false);
                            debugLog('  🔲 Header: unchecked');
                        } else if (visibleSelectedCount === visibleCount) {
                            $headerCheckbox.prop('checked', true);
                            $headerCheckbox.prop('indeterminate', false);
                            debugLog('  ✅ Header: checked');
                        } else {
                            $headerCheckbox.prop('checked', false);
                            $headerCheckbox.prop('indeterminate', true);
                            debugLog('  ➖ Header: indeterminate');
                        }
                    }

                    // Initialiser tous les tableaux
                    $('.teacher-table-container').each(function() {
                        const matiereId = $(this).data('matiere-id');
                        debugLog('🎬 Initializing table for matiere:', matiereId);
                        updateTeacherCount(matiereId);
                    });

                    // Gérer le changement d'une checkbox individuelle
                    $(document).on('change', '.teacher-checkbox', function() {
                        const matiereId = $(this).closest('.teacher-table-container').data('matiere-id');
                        debugLog('🔄 Individual checkbox changed in matiere:', matiereId);
                        updateTeacherCount(matiereId);
                    });

                    // Gérer le checkbox header (select all / deselect all pour lignes VISIBLES)
                    $(document).on('change', '.teacher-select-all-checkbox', function() {
                        const matiereId = $(this).data('matiere-id');
                        const isChecked = $(this).prop('checked');

                        debugLog('🔍 Header checkbox clicked - Matiere:', matiereId);
                        debugLog('  🎯 Action:', isChecked ? 'SELECT ALL VISIBLE' : 'DESELECT ALL VISIBLE');

                        const $tableContainer = $('.teacher-table-container[data-matiere-id="' + matiereId + '"]');
                        const $visibleCheckboxes = $tableContainer.find('.teacher-row:visible .teacher-checkbox');

                        debugLog('  👁️ Visible rows before:', $visibleCheckboxes.length);
                        debugLog('  ✅ Checked before:', $visibleCheckboxes.filter(':checked').length);

                        // Cocher/décocher uniquement les lignes VISIBLES
                        $visibleCheckboxes.prop('checked', isChecked);

                        debugLog('  ✅ Checked after:', $visibleCheckboxes.filter(':checked').length);

                        updateTeacherCount(matiereId);
                    });

                    // Fonction de calcul de distance de Levenshtein (similarité entre chaînes)
                    function levenshteinDistance(str1, str2) {
                        const len1 = str1.length;
                        const len2 = str2.length;
                        const matrix = [];

                        // Initialiser la matrice
                        for (let i = 0; i <= len1; i++) {
                            matrix[i] = [i];
                        }
                        for (let j = 0; j <= len2; j++) {
                            matrix[0][j] = j;
                        }

                        // Remplir la matrice
                        for (let i = 1; i <= len1; i++) {
                            for (let j = 1; j <= len2; j++) {
                                const cost = str1[i - 1] === str2[j - 1] ? 0 : 1;
                                matrix[i][j] = Math.min(
                                    matrix[i - 1][j] + 1,      // Suppression
                                    matrix[i][j - 1] + 1,      // Insertion
                                    matrix[i - 1][j - 1] + cost // Substitution
                                );
                            }
                        }

                        return matrix[len1][len2];
                    }

                    // Fonction de calcul du pourcentage de similarité
                    function calculateSimilarity(str1, str2) {
                        const maxLen = Math.max(str1.length, str2.length);
                        if (maxLen === 0) return 100;
                        const distance = levenshteinDistance(str1, str2);
                        return ((maxLen - distance) / maxLen) * 100;
                    }

                    // Fonction de normalisation de texte (supprime accents, met en minuscule)
                    function normalizeText(text) {
                        return text
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '') // Supprime les accents
                            .replace(/[^a-z0-9\s]/g, ''); // Garde seulement lettres, chiffres, espaces
                    }

                    // Fonction de recherche floue (fuzzy search)
                    function fuzzyMatch(searchText, targetText, threshold = 80) {
                        const normalizedSearch = normalizeText(searchText);
                        const normalizedTarget = normalizeText(targetText);

                        // 1. Correspondance exacte (substring)
                        if (normalizedTarget.includes(normalizedSearch)) {
                            debugLog('    ✅ Exact match:', targetText);
                            return true;
                        }

                        // 2. Correspondance par mots individuels
                        const searchWords = normalizedSearch.split(/\s+/).filter(w => w.length > 0);
                        const targetWords = normalizedTarget.split(/\s+/).filter(w => w.length > 0);

                        // Vérifier si tous les mots de recherche matchent au moins un mot de la cible
                        const allWordsMatch = searchWords.every(searchWord => {
                            return targetWords.some(targetWord => {
                                // Correspondance exacte du mot
                                if (targetWord.includes(searchWord)) {
                                    return true;
                                }
                                // Correspondance floue du mot (80%+)
                                const similarity = calculateSimilarity(searchWord, targetWord);
                                return similarity >= threshold;
                            });
                        });

                        if (allWordsMatch) {
                            debugLog('    ✅ Word match:', targetText);
                            return true;
                        }

                        // 3. Similarité globale de la chaîne complète
                        const globalSimilarity = calculateSimilarity(normalizedSearch, normalizedTarget);
                        if (globalSimilarity >= threshold) {
                            debugLog('    ✅ Fuzzy match (', globalSimilarity.toFixed(1), '%):', targetText);
                            return true;
                        }

                        // 4. Tenter avec inversion des mots (Jean KOUASSI vs KOUASSI Jean)
                        if (searchWords.length === 2 && targetWords.length >= 2) {
                            const reversed = searchWords.reverse().join(' ');
                            if (normalizedTarget.includes(reversed)) {
                                debugLog('    ✅ Reversed match:', targetText);
                                return true;
                            }
                        }

                        debugLog('    ❌ No match:', targetText, '- Similarity:', globalSimilarity.toFixed(1), '%');
                        return false;
                    }

                    // Gérer le filtre de recherche avec fuzzy matching
                    $(document).on('keyup', '.teacher-search-input', function() {
                        const matiereId = $(this).data('matiere-id');
                        const searchText = $(this).val().trim();

                        debugLog('🔍 Fuzzy search for matiere:', matiereId, '- Query:', searchText);

                        const $tableContainer = $('.teacher-table-container[data-matiere-id="' + matiereId + '"]');
                        const $rows = $tableContainer.find('.teacher-row');
                        const $noResults = $('.teacher-no-results[data-matiere-id="' + matiereId + '"]');

                        let visibleCount = 0;

                        if (searchText === '') {
                            // Afficher toutes les lignes
                            $rows.show();
                            $noResults.hide();
                            $tableContainer.show();
                            visibleCount = $rows.length;
                            debugLog('  ✅ Search cleared - showing all', visibleCount, 'rows');
                        } else {
                            // Filtrer les lignes avec fuzzy matching
                            $rows.each(function() {
                                const teacherName = $(this).data('teacher-name');
                                const teacherSpec = $(this).data('teacher-spec');

                                debugLog('  🔎 Checking:', teacherName);

                                // Recherche floue sur le nom OU la spécialisation (seuil 80%)
                                const matchName = fuzzyMatch(searchText, teacherName, 80);
                                const matchSpec = fuzzyMatch(searchText, teacherSpec, 80);

                                if (matchName || matchSpec) {
                                    $(this).show();
                                    visibleCount++;
                                } else {
                                    $(this).hide();
                                }
                            });

                            debugLog('  📊 Fuzzy search results:', visibleCount, 'rows visible');

                            // Afficher message si aucun résultat
                            if (visibleCount === 0) {
                                $tableContainer.hide();
                                $noResults.show();
                                debugLog('  ⚠️ No results found');
                            } else {
                                $tableContainer.show();
                                $noResults.hide();
                            }
                        }

                        // Mettre à jour le compteur et l'état du header checkbox
                        updateTeacherCount(matiereId);
                    });
                } else {
                    showAlert('error', response.message || 'Erreur lors du chargement des matières');
                    $('#matieres-container').html('<div class="text-center text-muted py-4">Erreur lors du chargement</div>').show();
                }
            },
            error: function(xhr) {
                $('#config-loading').hide();
                debugError('Erreur AJAX:', xhr);
                showAlert('error', 'Erreur de communication avec le serveur');
                $('#matieres-container').html('<div class="text-center text-muted py-4">Erreur de chargement</div>').show();
            }
        });
    }

    // Sauvegarde de la configuration
    $('#save-volume-config').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.html();

        debugLog('🚀 ========== DÉBUT SAUVEGARDE PLANNING GÉNÉRAL ==========');

        // Récupérer les données du formulaire
        const formData = {
            filiere_id: currentFiliereId,
            niveau_id: currentNiveauId,
            annee_id: $('#config-annee-id').val(),
            semestre: $('#config-semestre').val(),
            volumes: {},
            teachers: {}
        };

        debugLog('📋 Données de base:', {
            filiere_id: formData.filiere_id,
            niveau_id: formData.niveau_id,
            annee_id: formData.annee_id,
            semestre: formData.semestre
        });

        // Collecter tous les volumes
        $('.volume-input').each(function() {
            const matiereId = $(this).attr('name').match(/volumes\[(\d+)\]/)[1];
            const rawValue = $(this).val();
            if (rawValue === '' || rawValue === null) {
                return;
            }
            const volume = parseInt(rawValue, 10);
            if (Number.isNaN(volume)) {
                return;
            }
            formData.volumes[matiereId] = volume;

            if (volume > 0) {
                debugLog('  📊 Volume Matière ' + matiereId + ': ' + volume + 'h');
            }
        });

        debugLog('📚 Total volumes:', Object.keys(formData.volumes).length + ' matières');

        if (Object.keys(formData.volumes).length === 0 && Object.keys(formData.teachers).length === 0) {
            showAlert('info', 'Aucune modification détectée.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('volumeConfigModal'));
            if (modal) {
                modal.hide();
            }
            return;
        }

        // Collecter toutes les assignations de professeurs (checkboxes)
        const $teacherContainers = $('.teacher-table-container');
        debugLog('🔍 Conteneurs de professeurs trouvés:', $teacherContainers.length);

        $teacherContainers.each(function() {
            const $container = $(this);
            const $checkedBoxes = $container.find('.teacher-checkbox:checked');

            if ($checkedBoxes.length > 0) {
                // Récupérer le matiere_id depuis le name du premier checkbox
                const firstName = $checkedBoxes.first().attr('name');
                const matiereId = firstName.match(/teachers\[(\d+)\]/)[1];

                // Collecter tous les teacher_id cochés pour cette matière
                const selectedTeachers = [];
                $checkedBoxes.each(function() {
                    selectedTeachers.push($(this).val());
                });

                formData.teachers[matiereId] = selectedTeachers;

                debugLog('👨‍🏫 Matière ' + matiereId + ': ' + selectedTeachers.length + ' enseignants sélectionnés', selectedTeachers);
            }
        });

        debugLog('📝 Résumé enseignants:', {
            'Matières avec enseignants': Object.keys(formData.teachers).length,
            'Détails': formData.teachers
        });

        debugLog('📦 FormData complet à envoyer:', JSON.stringify(formData, null, 2));

        // Validation
        if (!formData.filiere_id || !formData.niveau_id || !formData.annee_id) {
            debugError('❌ Validation échouée: Données manquantes');
            showAlert('error', 'Données manquantes pour la sauvegarde');
            return;
        }

        debugLog('✅ Validation OK, envoi de la requête AJAX...');

        // Afficher loading sur le bouton
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...');

        $.ajax({
            url: '{{ route("esbtp.planning-general.save-volume-configuration") }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                debugLog('✅ Réponse serveur SUCCESS:', response);

                if (response.success) {
                    debugLog('🎉 Sauvegarde réussie!', response.message);
                    showAlert('success', response.message);

                    // Fermer le modal
                    $('#volumeConfigModal').modal('hide');

                    // Mise à jour AJAX de la card concernée (sans reload)
                    if (response.card_stats) {
                        const s = response.card_stats;
                        const $card = $(`.combinaison-card[data-filiere-id="${currentFiliereId}"][data-niveau-id="${currentNiveauId}"]`);
                        if ($card.length) {
                            // Mettre à jour les stats S1/S2
                            const $stats = $card.find('.stat-number-lines');
                            if ($stats.length >= 2) {
                                // Matières configurées
                                $stats.eq(0).find('.stat-line').eq(0).find('span:last').text(s.matieres_configurees_s1 + '/' + s.total_matieres);
                                $stats.eq(0).find('.stat-line').eq(1).find('span:last').text(s.matieres_configurees_s2 + '/' + s.total_matieres);
                                // Volume horaire
                                $stats.eq(1).find('.stat-line').eq(0).find('span:last').text(s.total_heures_s1 + 'h');
                                $stats.eq(1).find('.stat-line').eq(1).find('span:last').text(s.total_heures_s2 + 'h');
                            }
                            // Mettre à jour le badge statut
                            $card.removeClass('configured partial not-configured').addClass(s.status_class);
                            $card.find('.status-badge').removeClass('configured partial not-configured').addClass(s.status_class)
                                 .find('i').attr('class', 'fas ' + s.status_icon);

                            // Animation flash de confirmation
                            $card.css('transition', 'box-shadow .3s').css('box-shadow', '0 0 0 3px rgba(16,185,129,.4)');
                            setTimeout(() => $card.css('box-shadow', ''), 1500);

                            debugLog('✅ Card mise à jour sans rechargement');
                        }
                    }
                } else {
                    debugError('❌ Sauvegarde échouée (success=false):', response.message);
                    showAlert('error', response.message || 'Erreur lors de la sauvegarde');
                }

                debugLog('========== FIN SAUVEGARDE PLANNING GÉNÉRAL ==========');
            },
            error: function(xhr) {
                debugError('❌ ========== ERREUR AJAX ==========');
                debugError('Status:', xhr.status);
                debugError('Status Text:', xhr.statusText);
                debugError('Response:', xhr.responseJSON);
                debugError('Full XHR:', xhr);

                let message = 'Erreur lors de la sauvegarde';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                    debugError('Message d\'erreur:', message);
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    message = errors.join(', ');
                    debugError('Erreurs de validation:', errors);
                }

                showAlert('error', message);
                debugError('========== FIN ERREUR ==========');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reset du modal à la fermeture
    $('#volumeConfigModal').on('hidden.bs.modal', function() {
        $('#matieres-container').empty();
        $('#config-combination-name').text('-');
        currentFiliereId = null;
        currentNiveauId = null;
        currentCombinaisonName = '';
    });

    // Fonction utilitaire pour afficher les alertes
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

        // Insérer l'alerte au début du contenu principal
        $('.main-content').prepend($alert);

        // Auto-hide après 5 secondes
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }


    // ================================
    // MODAL DE DISPONIBILITÉ ENSEIGNANT
    // ================================

    let currentTeacherId = null;
    let currentWeekStart = new Date();

    // Ouverture du modal
    $('#teacherAvailabilityModal').on('show.bs.modal', function(e) {
        const $trigger = $(e.relatedTarget);
        currentTeacherId = $trigger.data('teacher-id');
        const teacherName = $trigger.data('teacher-name');
        const planificationId = $trigger.data('planification-id');

        // Mise à jour des noms d'enseignant dans le modal
        $('#modal-teacher-name, #calendar-teacher-name').text(teacherName);

        // Charger les données de l'enseignant
        loadTeacherData(currentTeacherId);

        // Initialiser la vue calendrier
        initializeWeeklyCalendar();

        // Reset sur le premier tab
        $('#overview-tab').trigger('click');
    });

    // Fonction pour charger les données de l'enseignant
    function loadTeacherData(teacherId) {
        // Simulation des données - à remplacer par un appel AJAX réel
        const mockData = {
            totalHours: 24,
            availableSlots: 15,
            conflictsCount: 2,
            loadPercentage: 60,
            subjects: [
                {
                    name: 'Mathématiques',
                    filiere: 'Informatique',
                    niveau: 'L1',
                    hours: '40h',
                    progress: 75,
                    status: 'En cours'
                },
                {
                    name: 'Algorithmes',
                    filiere: 'Informatique',
                    niveau: 'L2',
                    hours: '30h',
                    progress: 60,
                    status: 'Planifié'
                }
            ]
        };

        // Mise à jour des KPIs
        $('#total-hours').text(mockData.totalHours + 'h');
        $('#available-slots').text(mockData.availableSlots);
        $('#conflicts-count').text(mockData.conflictsCount);
        $('#load-percentage').text(mockData.loadPercentage + '%');

        // Mise à jour du tableau des matières
        const $tbody = $('#assigned-subjects-table tbody');
        $tbody.empty();

        mockData.subjects.forEach(subject => {
            const $row = $(`
                <tr>
                    <td><strong>${subject.name}</strong></td>
                    <td>${subject.filiere} - ${subject.niveau}</td>
                    <td>${subject.hours}</td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar" role="progressbar"
                                 style="width: ${subject.progress}%"
                                 aria-valuenow="${subject.progress}"
                                 aria-valuemin="0" aria-valuemax="100">
                                ${subject.progress}%
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${subject.status === 'En cours' ? 'primary' : 'secondary'}">
                            ${subject.status}
                        </span>
                    </td>
                </tr>
            `);
            $tbody.append($row);
        });
    }

    // Initialisation du calendrier hebdomadaire
    function initializeWeeklyCalendar() {
        generateWeeklyCalendar();
    }

    // Génération du calendrier
    function generateWeeklyCalendar() {
        const $calendar = $('#weekly-calendar');

        // Header avec les jours
        const days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        let headerHtml = '<div class="calendar-header-row">';
        headerHtml += '<div class="calendar-hour-cell">Heure</div>';
        days.forEach(day => {
            headerHtml += `<div class="calendar-day-cell">${day}</div>`;
        });
        headerHtml += '</div>';

        // Slots horaires (8h-18h) - cohérence avec les pages enseignants
        let slotsHtml = '';
        for (let hour = 8; hour <= 18; hour++) {
            slotsHtml += '<div class="calendar-time-slot">';
            slotsHtml += `<div class="time-label">${hour.toString().padStart(2, '0')}:00</div>`;

            for (let day = 0; day < 6; day++) {
                slotsHtml += `<div class="calendar-cell" data-hour="${hour}" data-day="${day}">`;

                // Exemple de cours (à remplacer par des données réelles)
                if (hour === 9 && day === 0) {
                    slotsHtml += '<div class="course-block">Mathématiques<br>L1-Info</div>';
                } else if (hour === 14 && day === 2) {
                    slotsHtml += '<div class="course-block conflict">Algorithmes<br>L2-Info<br><small>CONFLIT!</small></div>';
                }

                slotsHtml += '</div>';
            }
            slotsHtml += '</div>';
        }

        $calendar.html(headerHtml + slotsHtml);
    }

    // Navigation semaine précédente/suivante
    $('#prev-week').on('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() - 7);
        generateWeeklyCalendar();
    });

    $('#next-week').on('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        generateWeeklyCalendar();
    });

    $('#today-btn').on('click', function() {
        currentWeekStart = new Date();
        generateWeeklyCalendar();
    });

    // Gestion des conflits
    $('#refresh-conflicts').on('click', function() {
        loadConflicts();
    });

    function loadConflicts() {
        const mockConflicts = [
            {
                id: 1,
                severity: 'high',
                title: 'Conflit horaire détecté',
                description: 'Cours de Mathématiques L1 et Algorithmes L2 programmés au même créneau',
                time: 'Mardi 14h00-16h00',
                suggestions: [
                    'Déplacer Algorithmes L2 à 16h00-18h00',
                    'Assigner Mathématiques L1 à un autre enseignant'
                ]
            }
        ];

        const $conflictsList = $('#conflicts-list');
        $conflictsList.empty();

        if (mockConflicts.length === 0) {
            $conflictsList.html(`
                <div class="text-center text-success py-4">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h6>Aucun conflit détecté</h6>
                    <p class="text-muted">Le planning de cet enseignant ne présente aucun conflit d'horaires.</p>
                </div>
            `);
            return;
        }

        mockConflicts.forEach(conflict => {
            const $conflict = $(`
                <div class="conflict-item">
                    <div class="conflict-header">
                        <div>
                            <h6 class="mb-1">${conflict.title}</h6>
                            <span class="conflict-severity ${conflict.severity}">
                                ${conflict.severity.toUpperCase()}
                            </span>
                        </div>
                        <small class="text-muted">${conflict.time}</small>
                    </div>
                    <p class="mb-0">${conflict.description}</p>
                </div>
            `);
            $conflictsList.append($conflict);
        });

        // Suggestions
        const $solutions = $('#suggested-solutions');
        $solutions.empty();

        mockConflicts[0].suggestions.forEach(suggestion => {
            const $suggestion = $(`
                <div class="suggestion-card">
                    <i class="fas fa-lightbulb text-warning me-2"></i>
                    ${suggestion}
                </div>
            `);
            $solutions.append($suggestion);
        });
    }

    // Sauvegarde des préférences
    $('#save-preferences').on('click', function() {
        const preferences = {
            maxHoursPerWeek: $('#max-hours-per-week').val(),
            preferredStartTimes: $('#preferred-start-times').val(),
            unavailableDays: $('.days-selector input:checked').map(function() {
                return $(this).val();
            }).get()
        };

        // Simulation de sauvegarde
        $(this).html('<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...');

        setTimeout(() => {
            $(this).html('<i class="fas fa-check me-1"></i>Sauvegardé!');
            setTimeout(() => {
                $(this).html('<i class="fas fa-save me-1"></i>Sauvegarder les préférences');
            }, 2000);
        }, 1000);
    });

    // Ajout de créneaux d'indisponibilité
    $('#add-unavailability').on('click', function() {
        const $container = $('#specific-unavailabilities');
        const $newSlot = $(`
            <div class="unavailability-slot">
                <div class="row flex-grow-1">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm">
                            <option>Lundi</option>
                            <option>Mardi</option>
                            <option>Mercredi</option>
                            <option>Jeudi</option>
                            <option>Vendredi</option>
                            <option>Samedi</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="time" class="form-control form-control-sm" value="09:00">
                    </div>
                    <div class="col-md-3">
                        <input type="time" class="form-control form-control-sm" value="10:00">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-unavailability">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);
        $container.append($newSlot);
    });

    // Suppression de créneaux d'indisponibilité
    $(document).on('click', '.remove-unavailability', function() {
        $(this).closest('.unavailability-slot').remove();
    });

    // ===== GESTION DU MODAL D'AJOUT DE MATIÈRES =====

    // Ouvrir le modal et charger les données
    $('#addMatieresModal').on('show.bs.modal', function (event) {
        debugLog('Modal addMatieresModal ouvert !');
        const button = event.relatedTarget;
        debugLog('Button:', button);
        const isEmptyCombo = button.getAttribute('data-empty-combo');
        const filiereId = button.getAttribute('data-filiere-id');
        const niveauId = button.getAttribute('data-niveau-id');
        const filiereName = button.getAttribute('data-filiere-name');
        const niveauName = button.getAttribute('data-niveau-name');

        debugLog('Params:', {isEmptyCombo, filiereId, niveauId, filiereName, niveauName});

        if (isEmptyCombo === 'true') {
            // Mode ajout à combinaison vide
            $('#modal-matiere-name').text(`Combinaison ${filiereName} + ${niveauName}`);
            $('#modal-matiere-id').val('empty-combo');
            // Ce modal a déjà le bon titre, pas besoin de le changer

            // Pré-sélectionner et DÉSACTIVER la filière et le niveau (combinaison fixe)
            $('.filiere-checkbox, .niveau-checkbox').prop('checked', false).prop('disabled', true);
            if (filiereId) {
                $(`#filiere-${filiereId}`).prop('checked', true);
            }
            if (niveauId) {
                $(`#niveau-${niveauId}`).prop('checked', true);
            }

            // Changer les textes et sous-titres
            $('#filiere-subtitle').text('Filière sélectionnée (fixe)');
            $('#niveau-subtitle').text('Niveau sélectionné (fixe)');
            $('#addMatieresModalLabel').html('<i class="fas fa-plus me-2"></i>Ajouter matières à la combinaison');
            $('#save-liaisons-btn').html('<i class="fas fa-plus me-1"></i>Ajouter les matières sélectionnées');

            // Afficher toutes les matières disponibles
            loadAvailableMatieres(filiereId, niveauId);

            // Masquer l'aperçu des combinaisons (pas nécessaire en mode fixe)
            $('#combinations-preview').parent().parent().hide();

            // Stocker les IDs pour les boutons d'action
            window.currentFiliereId = filiereId;
            window.currentNiveauId = niveauId;
            window.currentFiliereName = filiereName;
            window.currentNiveauName = niveauName;
        }
    });

    // Fonction pour charger les matières disponibles pour une combinaison vide
    function loadAvailableMatieres(filiereId, niveauId) {
        const matieresListDiv = $('#matieres-list');
        const matieresContainer = $('#matieres-selection-container');

        // Afficher le conteneur
        matieresContainer.show();

        // Afficher un loader pendant le chargement
        matieresListDiv.html(`
            <div class="d-flex justify-content-center align-items-center py-4">
                <div class="spinner-border text-primary me-2" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <span>Chargement des matières disponibles...</span>
            </div>
        `);

        fetch(`/esbtp/matieres/available-for-combination?filiere_id=${filiereId}&niveau_id=${niveauId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.matieres.length > 0) {
                    let matieresHtml = '';
                    data.matieres.forEach(matiere => {
                        const isLinked = matiere.is_already_linked;
                        const cardClass = isLinked ? 'border-success bg-light-success' : '';
                        const statusBadge = isLinked ? '<span class="badge bg-success ms-2"><i class="fas fa-check"></i> Déjà assignée</span>' : '';

                        matieresHtml += `
                            <div class="form-check mb-3 p-2 ${cardClass}" style="border-radius: 6px; transition: all 0.2s ease; border: 1px solid ${isLinked ? '#198754' : 'var(--border-light)'};">
                                <input class="form-check-input matiere-checkbox" type="checkbox"
                                       value="${matiere.id}" id="matiere-${matiere.id}" name="selected_matieres[]"
                                       style="margin-top: 0.35rem;" ${isLinked ? 'checked' : ''}>
                                <label class="form-check-label" for="matiere-${matiere.id}" style="cursor: pointer; width: 100%;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="font-semibold color-dark">${matiere.name}</span>
                                            ${matiere.code ? `<span class="badge secondary ms-2">${matiere.code}</span>` : ''}
                                            ${statusBadge}
                                        </div>
                                        <div class="text-muted small">
                                            ${matiere.coefficient ? `Coeff: ${matiere.coefficient}` : ''}
                                            ${matiere.total_heures ? `• ${matiere.total_heures}h` : ''}
                                        </div>
                                    </div>
                                    ${matiere.description ? `<small class="text-muted d-block mt-1">${matiere.description}</small>` : ''}
                                </label>
                            </div>
                        `;
                    });
                    matieresListDiv.html(matieresHtml);
                } else {
                    matieresListDiv.html(`
                        <div class="d-flex align-items-center justify-content-center py-4 text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>Aucune matière trouvée</span>
                        </div>
                    `);
                }
            })
            .catch(error => {
                debugError('Erreur lors du chargement des matières:', error);
                matieresListDiv.html(`
                    <div class="d-flex align-items-center justify-content-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span>Erreur lors du chargement des matières</span>
                    </div>
                `);
            });
    }

    // Mise à jour de l'aperçu des combinaisons
    function updateCombinationsPreview() {
        const selectedFilieres = [];
        const selectedNiveaux = [];

        $('.filiere-checkbox:checked').each(function() {
            const label = $(this).next('label').find('span.font-semibold').text();
            selectedFilieres.push({
                id: $(this).val(),
                name: label
            });
        });

        $('.niveau-checkbox:checked').each(function() {
            const label = $(this).next('label').find('span.font-semibold').text();
            selectedNiveaux.push({
                id: $(this).val(),
                name: label
            });
        });

        const previewDiv = $('#combinations-preview');

        if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
            previewDiv.html(`
                <div class="d-flex align-items-center" style="color: #0369a1;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>Sélectionnez au moins une filière et un niveau pour voir les combinaisons possibles.</span>
                </div>
            `).css({
                'background': '#e7f3ff',
                'border': '1px solid #0ea5e9',
                'padding': '1.5rem',
                'border-radius': '8px'
            });
            return;
        }

        let combinationsHtml = `
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-check-circle me-2" style="color: #059669;"></i>
                <strong style="color: #047857;">${selectedFilieres.length * selectedNiveaux.length} combinaison(s) sélectionnée(s)</strong>
            </div>
            <div class="d-flex flex-wrap gap-2">
        `;

        selectedFilieres.forEach(filiere => {
            selectedNiveaux.forEach(niveau => {
                combinationsHtml += `
                    <span class="badge text-bg-success px-3 py-2">
                        ${filiere.name} + ${niveau.name}
                    </span>
                `;
            });
        });

        combinationsHtml += '</div>';

        previewDiv.html(combinationsHtml).css({
            'background': '#f0f9f0',
            'border': '1px solid #059669',
            'padding': '1.5rem',
            'border-radius': '8px'
        });
    }

    // Écouter les changements dans les checkboxes
    $(document).on('change', '.filiere-checkbox, .niveau-checkbox', updateCombinationsPreview);

    // Sauvegarde des liaisons
    $('#save-liaisons-btn').on('click', function() {
        const matiereId = $('#modal-matiere-id').val();
        const saveBtn = $(this);
        const originalText = saveBtn.html();

        if (matiereId === 'empty-combo') {
            // Mode ajout de matières à combinaison vide
            const selectedMatieres = $('.matiere-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            const selectedFilieres = $('.filiere-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            const selectedNiveaux = $('.niveau-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedMatieres.length === 0) {
                alert('Veuillez sélectionner au moins une matière.');
                return;
            }

            if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                alert('Veuillez sélectionner au moins une filière et un niveau.');
                return;
            }

            // Désactiver le bouton pendant la sauvegarde
            saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Ajout en cours...');

            fetch('/esbtp/matieres/add-to-combination', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    matiere_ids: selectedMatieres,
                    filiere_ids: selectedFilieres,
                    niveau_ids: selectedNiveaux
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    $('#addMatieresModal').modal('hide');

                    // Afficher message de succès et recharger la page
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Erreur lors de l\'ajout');
                }
            })
            .catch(error => {
                debugError('Erreur:', error);
                showAlert('error', 'Erreur lors de l\'ajout des matières: ' + error.message);
            })
            .finally(() => {
                // Réactiver le bouton
                saveBtn.prop('disabled', false).html(originalText);
            });
        }
    });

    // Gestionnaires pour les boutons d'action
    $(document).on('click', '#btn-view-details', function() {
        // Ouvrir la page index des matières dans un nouvel onglet
        window.open('/esbtp/matieres', '_blank');
    });

    $(document).on('click', '#btn-create-new', function() {
        // Rediriger vers la page de création avec paramètres pré-remplis
        const filiereId = window.currentFiliereId;
        const niveauId = window.currentNiveauId;
        window.location.href = `/esbtp/matieres/create?filiere_id=${filiereId}&niveau_id=${niveauId}`;
    });

    // Gestionnaires pour la sélection rapide des matières (MODAL)
    $(document).on('click', '#btn-select-all', function() {
        const $checkboxes = $('.matiere-checkbox');
        debugLog('🔍 Matières - Tout sélectionner clicked');
        debugLog('  📊 Total matiere checkboxes:', $checkboxes.length);
        debugLog('  ✅ Checked before:', $checkboxes.filter(':checked').length);

        $checkboxes.prop('checked', true);

        debugLog('  ✅ Checked after:', $checkboxes.filter(':checked').length);
    });

    $(document).on('click', '#btn-select-none', function() {
        const $checkboxes = $('.matiere-checkbox');
        debugLog('🔍 Matières - Tout désélectionner clicked');
        debugLog('  📊 Total matiere checkboxes:', $checkboxes.length);
        debugLog('  ✅ Checked before:', $checkboxes.filter(':checked').length);

        $checkboxes.prop('checked', false);

        debugLog('  ✅ Checked after:', $checkboxes.filter(':checked').length);
    });

    // Reset du modal à la fermeture
    $('#addMatieresModal').on('hidden.bs.modal', function() {
        $('#matieres-selection-container').hide();
        $('#matieres-list').empty();
        $('.filiere-checkbox, .niveau-checkbox, .matiere-checkbox').prop('checked', false).prop('disabled', false);
        $('#modal-matiere-id').val('');
        $('#filiere-subtitle').text('Sélectionnez les filières concernées');
        $('#niveau-subtitle').text('Sélectionnez les niveaux concernés');
        $('#combinations-preview').parent().parent().show();
        updateCombinationsPreview();
    });
});
</script>
@endpush
