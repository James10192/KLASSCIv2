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
    <meta name="app-debug" content="{{ config('app.debug') ? '1' : '0' }}">
    <meta name="navbar-mark-all-read-url" content="{{ url('/navbar/notifications/mark-all-read') }}">

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
    <!-- Dashboard Moderne CSS - Design System ACASI 2025 -->
    <link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
    <!-- Modal Z-Index Fix - Doit être chargé après les autres CSS -->
    <link href="{{ asset('css/modal-z-index-fix.css') }}" rel="stylesheet">
    <!-- Form Interaction Fix - Correction des problèmes d'interaction avec les formulaires -->
    <link href="{{ asset('css/form-interaction-fix.css') }}" rel="stylesheet">
    <!-- Modal Force Fix - DEBUG MODE -->
    <link href="{{ asset('css/modal-force-fix.css') }}" rel="stylesheet">
    <!-- Chatbot Widget -->
    <link href="{{ asset('css/chatbot-widget.css') }}" rel="stylesheet">

    <!-- Styles supplémentaires -->
    <style>
        /* Variables CSS ACASI pour cohérence */
        :root {
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --primary: #0453cb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
        }
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

        /* Styles pour les notifications améliorées */
        .notification-item-container {
            position: relative;
        }

        .notification-item {
            display: flex !important;
            align-items: center;
            padding: 12px 24px;
            border: none;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            text-align: left !important;
        }

        .notification-item:hover {
            background-color: rgba(99, 102, 241, 0.08) !important;
            transform: translateX(2px);
        }

        .notification-item.unread {
            background-color: rgba(59, 130, 246, 0.05);
            border-left: 3px solid #3b82f6;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-actions {
            margin-left: 8px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .notification-item:hover .notification-actions {
            opacity: 1;
        }

        .notification-delete-btn {
            background: none;
            border: none;
            color: #dc3545;
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .notification-delete-btn:hover {
            background-color: #dc3545;
            color: white;
            transform: scale(1.1);
        }

        .notification-icon {
            margin-right: 12px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .notification-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .notification-text {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 2px;
            line-height: 1.4;
            white-space: normal;
            word-break: break-word;
        }

        .notification-time {
            font-size: 11px;
            opacity: 0.6;
        }

        /* === DESIGN MODERNE DROPDOWNS NAVBAR - Style ACASI === */
        .custom-dropdown {
            min-width: 360px !important;
            max-width: 380px !important;
            max-height: min(520px, calc(100vh - 100px)) !important;
            border: 1px solid rgba(99, 102, 241, 0.08) !important;
            box-shadow:
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border-radius: 20px !important;
            padding: 8px !important;
            margin-top: 12px !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px) !important;
            overflow: hidden !important;
            flex-direction: column !important;
        }

        .custom-dropdown.show {
            display: flex !important;
        }

        #notifications-list,
        #messages-list {
            max-height: 320px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(99, 102, 241, 0.3) transparent;
        }

        #notifications-list::-webkit-scrollbar,
        #messages-list::-webkit-scrollbar {
            width: 4px;
        }

        #notifications-list::-webkit-scrollbar-track,
        #messages-list::-webkit-scrollbar-track {
            background: transparent;
        }

        #notifications-list::-webkit-scrollbar-thumb,
        #messages-list::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 4px;
        }

        .custom-dropdown::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 16px;
            width: 16px;
            height: 16px;
            background: white;
            border: 1px solid rgba(0,0,0,0.08);
            border-right: none;
            border-bottom: none;
            transform: rotate(45deg);
            z-index: 1001;
        }

        .custom-dropdown .dropdown-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03)) !important;
            color: #1e293b !important;
            font-weight: 700;
            font-size: 16px;
            padding: 20px 24px 16px 24px;
            margin: 0;
            border: none;
            border-radius: 12px;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }

        .custom-dropdown .dropdown-header button {
            color: #0453cb !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            text-decoration: none !important;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .custom-dropdown .dropdown-header button:hover {
            opacity: 1 !important;
        }

        .dropdown-divider {
            display: none !important;
        }

        .dropdown-loading,
        .dropdown-empty {
            padding: 32px 24px !important;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }

        .dropdown-loading .spinner-border {
            width: 24px;
            height: 24px;
            border-width: 2px;
            color: #0453cb;
        }

        .dropdown-empty i {
            font-size: 32px;
            margin-bottom: 12px;
            opacity: 0.3;
            color: #94a3b8;
        }

        .dropdown-empty-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 16px;
            color: #334155;
        }

        .dropdown-empty-text {
            font-size: 14px;
            color: #64748b;
            line-height: 1.4;
        }

        .view-all {
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.03)) !important;
            color: #0453cb !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            padding: 16px 24px !important;
            border: 1px solid rgba(99, 102, 241, 0.1) !important;
            border-radius: 12px !important;
            transition: all 0.3s ease !important;
            margin: 8px 12px 4px !important;
            text-align: center !important;
        }

        .view-all:hover {
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.1), rgba(94, 145, 222, 0.08)) !important;
            color: #0453cb !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(4, 83, 203, 0.15) !important;
        }

        /* Notifications specifiques - Style ACASI moderne */
        .notification-item-container {
            margin: 8px 12px 4px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .notification-item-container:last-child {
            margin-bottom: 8px;
        }

        .notification-item {
            display: flex !important;
            align-items: flex-start !important;
            padding: 16px !important;
            border: none !important;
            transition: all 0.15s ease !important;
            cursor: pointer;
            position: relative;
            gap: 12px;
            background: transparent !important;
            border-radius: 12px;
        }

        .notification-item:hover {
            background: #f8fafc !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            background: linear-gradient(90deg, rgba(4, 83, 203, 0.05), rgba(255, 255, 255, 1)) !important;
            border-left: 3px solid #0453cb !important;
        }

        .notification-icon {
            width: 36px !important;
            height: 36px !important;
            border-radius: 8px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            margin-right: 0 !important;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .notification-icon.bg-primary { background: #3b82f6; color: white; border-color: #2563eb; }
        .notification-icon.bg-success { background: #10b981; color: white; border-color: #059669; }
        .notification-icon.bg-warning { background: #f59e0b; color: white; border-color: #d97706; }
        .notification-icon.bg-danger { background: #ef4444; color: white; border-color: #dc2626; }
        .notification-icon.bg-info { background: #06b6d4; color: white; border-color: #0891b2; }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 600 !important;
            font-size: 14px !important;
            margin-bottom: 4px !important;
            line-height: 1.4 !important;
            color: #1e293b !important;
        }

        .notification-text {
            font-size: 13px !important;
            color: #64748b !important;
            line-height: 1.4 !important;
            margin-bottom: 6px !important;
            white-space: normal;
            word-break: break-word;
        }

        .notification-time {
            font-size: 12px !important;
            color: #94a3b8 !important;
            font-weight: 400 !important;
        }

        .notification-footer {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .notification-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 6px;
        }

        .notification-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
            display: flex;
            align-items: center;
        }

        .notification-item:hover .notification-actions {
            opacity: 1;
        }

        .notification-delete-btn {
            background: #fee2e2 !important;
            color: #dc2626 !important;
            border: none;
            border-radius: 8px;
            padding: 6px 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .notification-delete-btn:hover {
            background: #fecaca !important;
            color: #991b1b !important;
            transform: scale(1.05);
        }

        /* Messages specifiques - Style ACASI moderne */
        #messages-list .dropdown-item {
            margin: 8px 12px 4px !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            background: white !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
            padding: 16px !important;
            transition: all 0.15s ease !important;
        }

        #messages-list .dropdown-item:last-child {
            margin-bottom: 8px !important;
        }

        #messages-list .dropdown-item:hover {
            background: #f8fafc !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }


        .message-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            color: #1e293b;
            line-height: 1.4;
        }

        .message-text {
            font-size: 13px;
            color: #64748b;
            line-height: 1.4;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-time {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 400;
        }

        @media (max-width: 768px) {
            .custom-dropdown {
                min-width: 340px !important;
                max-width: 360px !important;
            }
            
            .notification-item,
            #messages-list .dropdown-item {
                padding: 12px 16px !important;
            }
            
            .custom-dropdown .dropdown-header {
                padding: 16px 16px 12px 16px;
            }
        }

        /* ===== STYLES NAVBAR MODERNES ACASI ===== */
        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            position: relative;
        }

        .search-bar {
            background: rgba(255, 255, 255, 0.98);
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 25px;
            padding: 12px 20px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
            min-width: 300px;
        }

        .search-bar:focus {
            outline: none;
            border-color: #0453cb;
            box-shadow: 0 4px 20px rgba(4, 83, 203, 0.15);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-1px);
        }

        .btn-acasi.icon-only {
            padding: 12px;
            border-radius: 14px;
            border: 1px solid rgba(99, 102, 241, 0.25);
            background: rgba(255, 255, 255, 0.95);
            color: #6b7280;
            font-size: 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-acasi.icon-only:hover {
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.1), rgba(94, 145, 222, 0.08));
            color: #0453cb;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .btn-acasi.profile-btn {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 16px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-acasi.profile-btn:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.1);
        }

        /* Résultats de recherche */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            max-height: 350px;
            overflow-y: auto;
            overflow-x: hidden;
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .search-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
        }

        .search-item-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
            flex-shrink: 0;
        }

        .search-category {
            padding: 8px 16px;
            font-weight: 600;
            color: #0453cb;
            background: rgba(4, 83, 203, 0.05);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .search-item-content {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .search-item-title {
            font-weight: 500;
            color: #1f2937;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        
        .search-item-description {
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Scrollbar personnalisée pour les résultats de recherche */
        .search-results::-webkit-scrollbar {
            width: 6px;
        }
        
        .search-results::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }
        
        .search-results::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #0453cb, #5e91de);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .search-results::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
        
        /* Firefox scrollbar */
        .search-results {
            scrollbar-width: thin;
            scrollbar-color: #0453cb rgba(0, 0, 0, 0.05);
        }

        /* ===== AMÉLIORATION DES ACTIONS RAPIDES ===== */
        /* Dropdown spécifique pour les actions rapides */
        .quick-actions-dropdown {
            min-width: 380px !important;
            max-width: 400px !important;
        }

        /* Force la grille 2x2 avec priorité maximale et spécificité élevée */
        .dropdown-menu.custom-dropdown .quick-actions-grid,
        .dropdown-menu li .quick-actions-grid,
        .quick-actions-dropdown #quick-actions-list,
        ul.dropdown-menu li div#quick-actions-list,
        .quick-actions-dropdown .quick-actions-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            grid-auto-rows: auto !important;
            gap: 12px !important;
            padding: 12px !important;
            margin: 8px 12px !important;
            min-width: 320px !important;
            max-width: 360px !important;
            width: 320px !important;
            box-sizing: border-box !important;
            justify-items: stretch !important;
            align-items: start !important;
        }

        .quick-actions-grid .quick-action-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            padding: 16px 12px !important;
            text-decoration: none !important;
            color: inherit !important;
            border-radius: 12px !important;
            transition: all 0.3s ease !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            max-width: none !important;
        }

        .quick-action-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(4, 83, 203, 0.15);
            color: #0453cb;
        }

        .quick-action-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .quick-action-item:hover .quick-action-icon {
            transform: scale(1.1);
        }

        .quick-action-text {
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            line-height: 1.3;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            background: linear-gradient(135deg, #0453cb, #5e91de);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        /* Firefox scrollbar */
        .sidebar-menu {
            scrollbar-width: thin;
            scrollbar-color: #0453cb rgba(0, 0, 0, 0.05);
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

            .quick-actions-dropdown {
                min-width: 320px !important;
                max-width: 340px !important;
            }

            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
                min-width: 280px;
                gap: 8px;
                padding: 8px;
                margin: 4px 8px;
            }
        }

        /* Seulement sur très petit écran (mobiles portrait) passer à 1 colonne */
        @media (max-width: 479.98px) {
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
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.1), rgba(94, 145, 222, 0.1));
            color: #0453cb;
            transform: scale(1.05);
        }

        .navbar-toggle:active {
            transform: scale(0.95);
        }

        /* ===== AMÉLIORATION DES DROPDOWNS ===== */
        .custom-dropdown {
            border: none;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15), 0 5px 20px rgba(0, 0, 0, 0.1);
            border-radius: 18px;
            backdrop-filter: blur(25px);
            background: rgba(255, 255, 255, 0.97);
            animation: dropdownSlideIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 320px;
            max-width: 380px;
            border: 1px solid rgba(99, 102, 241, 0.08);
            overflow: hidden;
            padding: 8px 0;
        }

        @keyframes dropdownSlideIn {
            from {
                opacity: 0;
                transform: translateY(-15px) scale(0.92);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .custom-dropdown .dropdown-item {
            border-radius: 12px;
            margin: 3px 12px;
            padding: 12px 16px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            border: 1px solid transparent;
            font-size: 14px;
            line-height: 1.4;
        }

        .custom-dropdown .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
            transform: translateX(6px);
            border-color: rgba(99, 102, 241, 0.15);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.1);
        }

        .custom-dropdown .dropdown-item:active {
            transform: translateX(3px) scale(0.98);
        }

        /* En-têtes des dropdowns */
        .custom-dropdown .dropdown-header {
            font-weight: 700;
            font-size: 16px;
            color: #1f2937;
            padding: 16px 20px 12px;
            border-bottom: 2px solid rgba(99, 102, 241, 0.1);
            margin-bottom: 8px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.02), rgba(139, 92, 246, 0.02));
        }

        /* Séparateurs */
        .custom-dropdown .dropdown-divider {
            margin: 12px 16px;
            border-color: rgba(99, 102, 241, 0.12);
            opacity: 1;
        }

        /* Notifications dropdown spécifiques */
        .notification-item {
            padding: 16px !important;
            margin: 4px 12px !important;
            border-radius: 14px !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: 14px;
            position: relative;
            overflow: hidden;
        }

        .notification-item.unread {
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.03));
            border-left: 4px solid #0453cb;
        }

        .notification-item:hover {
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.1), rgba(94, 145, 222, 0.08)) !important;
            transform: translateX(4px) !important;
        }

        .notification-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .notification-text {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 6px;
            white-space: normal;
            word-break: break-word;
        }

        .notification-time {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 500;
        }

        /* Messages dropdown spécifiques */
        .message-item {
            padding: 16px !important;
            margin: 4px 12px !important;
            border-radius: 14px !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: 14px;
            position: relative;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .message-item.unread {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05), rgba(16, 185, 129, 0.03));
            border-left: 4px solid #22c55e;
        }

        .message-item:hover {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.08)) !important;
            transform: translateX(4px) !important;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #06b6d4;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
            border: 1px solid #0891b2;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-right: 12px;
        }

        .message-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .message-content {
            flex: 1;
            min-width: 0;
        }

        .message-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
            display: flex;
            align-items: center;
        }

        .message-item:hover .message-actions {
            opacity: 1;
        }

        .message-delete-btn {
            background: #fee2e2 !important;
            color: #dc2626 !important;
            border: none !important;
            border-radius: 6px !important;
            width: 28px !important;
            height: 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 12px !important;
            transition: all 0.15s ease;
        }

        .message-delete-btn:hover {
            background: #fecaca !important;
            color: #991b1b !important;
            transform: scale(1.05);
        }

        .message-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .message-text {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-time {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 500;
        }

        /* Profile dropdown spécifiques */
        .dropdown-user-details {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03), rgba(139, 92, 246, 0.02));
            border-radius: 16px;
            margin: 8px 12px 12px;
            border: 1px solid rgba(99, 102, 241, 0.08);
        }

        .dropdown-user-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(99, 102, 241, 0.2);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }

        .dropdown-user-avatar img,
        .dropdown-user-avatar .user-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0453cb, #5e91de);
            color: white;
            font-size: 20px;
        }

        .dropdown-user-info {
            flex: 1;
        }

        .dropdown-user-name {
            font-weight: 700;
            color: #1f2937;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .dropdown-user-email {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
        }

        /* Lien "Voir tout" */
        .view-all {
            font-weight: 600 !important;
            color: #0453cb !important;
            text-align: center;
            padding: 14px 20px !important;
            margin: 8px 12px 4px !important;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03)) !important;
            border-radius: 12px !important;
            border: 1px solid rgba(99, 102, 241, 0.1) !important;
        }

        .view-all:hover {
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.1), rgba(94, 145, 222, 0.08)) !important;
            color: #0453cb !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(4, 83, 203, 0.15) !important;
        }

        /* ===== BADGES ET NOTIFICATIONS ===== */
        .navbar-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            animation: badgePulse 2s infinite;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            z-index: 10;
        }

        @keyframes badgePulse {
            0% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4), 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            50% {
                transform: scale(1.1);
                box-shadow: 0 4px 12px rgba(239, 68, 68, 0.6), 0 0 0 8px rgba(239, 68, 68, 0.1);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4), 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        /* Désactivation des anciens styles navbar-icon car remplacés par btn-acasi */
        .navbar-icon {
            /* Styles remplacés par .btn-acasi.icon-only */
        }

        /* User navbar désactivé car remplacé par btn-acasi.profile-btn */
        .navbar-user {
            /* Styles remplacés par .btn-acasi.profile-btn */
        }

        .navbar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(99, 102, 241, 0.15);
            transition: all 0.3s ease;
        }

        .navbar-user:hover .navbar-avatar {
            border-color: rgba(99, 102, 241, 0.3);
            transform: scale(1.05);
        }

        .navbar-avatar img,
        .navbar-avatar .user-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0453cb, #5e91de);
            color: white;
            font-size: 14px;
            font-weight: 600;
        }

        .navbar-user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .navbar-user-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.2;
        }

        /* States pour les dropdowns */
        .dropdown.show .navbar-icon {
            background: linear-gradient(135deg, rgba(4, 83, 203, 0.15), rgba(94, 145, 222, 0.12));
            color: #0453cb;
            transform: translateY(-1px);
        }

        .dropdown.show .navbar-user {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(99, 102, 241, 0.2);
            transform: translateY(-1px);
        }

        /* Responsive des dropdowns */
        @media (max-width: 767.98px) {
            .custom-dropdown {
                min-width: 280px;
                max-width: 300px;
            }

            .notification-item,
            .message-item {
                padding: 12px !important;
            }

            .notification-icon,
            .message-avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .dropdown-user-details {
                padding: 16px;
            }

            .dropdown-user-avatar {
                width: 44px;
                height: 44px;
            }
        }

        /* Loading states */
        .dropdown-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #6b7280;
        }

        .dropdown-loading .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            border-width: 2px;
            border-color: #0453cb;
            border-right-color: transparent;
        }

        /* Empty states */
        .dropdown-empty {
            text-align: center;
            padding: 24px 20px;
            color: #9ca3af;
        }

        .dropdown-empty i {
            font-size: 32px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .dropdown-empty-title {
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .dropdown-empty-text {
            font-size: 13px;
            line-height: 1.4;
        }
    </style>
    @yield('styles')
    @stack('styles')
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
                    @can('module.academique.access')
                    @can('filieres.view')
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
                    @endcan
                    @endcan

                    <!-- Students Section -->
                    @can('module.etudiants.access')
                    @can('students.view')
                        <div class="menu-category">Étudiants</div>

                        <!-- Student Management -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.etudiants.*') || Request::routeIs('esbtp.inscriptions.*') || Request::routeIs('esbtp.reinscription.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-user-graduate"></i></div>
                                <div class="menu-text">Étudiants</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.etudiants.*') || Request::routeIs('esbtp.inscriptions.*') || Request::routeIs('esbtp.reinscription.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.etudiants.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.etudiants.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-list"></i></div>
                                    <div class="menu-text">Liste des Étudiants</div>
                                </a>
                                <a href="{{ route('esbtp.inscriptions.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.inscriptions.index') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                                    <div class="menu-text">Inscriptions</div>
                                </a>
                                <a href="{{ route('esbtp.inscriptions.create') }}" class="menu-sublink {{ Request::routeIs('esbtp.inscriptions.create') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-user-plus"></i></div>
                                    <div class="menu-text">Nouvelle Inscription</div>
                                </a>
                                @can('inscriptions.view')
                                <a href="{{ route('esbtp.reinscription.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.reinscription.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-redo"></i></div>
                                    <div class="menu-text">Réinscriptions</div>
                                </a>
                                @endcan
                                <a href="{{ route('esbtp.inscriptions.sous-reserve') }}" class="menu-sublink {{ Request::routeIs('esbtp.inscriptions.sous-reserve') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                                    <div class="menu-text">Sous réserve</div>
                                </a>
                            </div>
                        </div>
                    @endcan
                    @endcan

                    <!-- Personnel (non-superAdmin — superAdmin has accordion in Administration) -->
                    @can('personnel.manage')
                        @if(!auth()->user()->can('admin.access'))
                        <div class="menu-category">Personnel</div>
                        <div class="menu-item">
                            <a href="{{ route('esbtp.personnel.unified.index') }}" class="menu-link {{ Request::routeIs('esbtp.personnel.unified.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-users-cog"></i></div>
                                <div class="menu-text">Gestion du personnel</div>
                            </a>
                        </div>
                        @endif
                    @elsecan('module.enseignants.access')
                        <div class="menu-category">Personnel</div>
                        <div class="menu-item">
                            <a href="{{ route('esbtp.enseignants.index') }}" class="menu-link {{ Request::routeIs('esbtp.enseignants.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <div class="menu-text">Enseignants</div>
                            </a>
                        </div>
                    @endcan

                    {{-- Coordination pédagogique section removed — all items exist in their canonical sections
                         (Étudiants, Notes & Rapports, Présence & Absences, Administration, Communication, Enseignement).
                         Coordinateurs see these items via their permissions on the canonical sections.
                         Removed 2026-04-11 to fix superAdmin sidebar duplication. --}}

                    <!-- Teaching Section -->
                    @can('module.emploi_temps.access')
                    @can('timetables.view')
                        <div class="menu-category">Enseignement</div>

                        <!-- Schedule Management -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.emploi-temps.index') }}" class="menu-link {{ Request::routeIs('esbtp.emploi-temps.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div class="menu-text">Emplois du temps</div>
                            </a>
                        </div>

                        @can('matieres.view')
                        <!-- Matières -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.matieres.index') }}" class="menu-link {{ Request::routeIs('esbtp.matieres.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-book"></i></div>
                                <div class="menu-text">Matières</div>
                            </a>
                        </div>
                        @endcan

                        <!-- Planning Général -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.planning-general.index') }}" class="menu-link {{ Request::routeIs('esbtp.planning-general.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="menu-text">Planning Général</div>
                            </a>
                        </div>

                    @endcan
                    @endcan

                    <!-- Grades & Reports Section -->
                    @can('module.notes_evaluations.access')
                    @can('notes.view')
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
                    @endcan
                    @endcan

                    <!-- LMD Section (Licence-Master-Doctorat) -->
                    @can('module.lmd.access')
                    @can('notes.view')
                        <div class="menu-category">Système LMD</div>

                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.lmd.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-graduation-cap"></i></div>
                                <div class="menu-text">LMD</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.lmd.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.lmd.parcours-domain.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.lmd.parcours-domain.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Domaines & Parcours</span>
                                </a>
                                <a href="{{ route('esbtp.lmd.ue.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.lmd.ue.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Unités d'Enseignement</span>
                                </a>
                                <a href="{{ route('esbtp.lmd.notes.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.lmd.notes.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Notes LMD</span>
                                </a>
                                <a href="{{ route('esbtp.lmd.resultats.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.lmd.resultats.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Résultats LMD</span>
                                </a>
                                <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.lmd.bulletins.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Bulletins LMD</span>
                                </a>
                            </div>
                        </div>
                    @endcan
                    @endcan

                    <!-- Administration Section -->
                    @can('personnel.manage')
                        <div class="menu-category">Administration</div>

                        <!-- Staff Management -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.staff.*') || Request::routeIs('esbtp.roles.*') || Request::routeIs('esbtp.departments.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-users-cog"></i></div>
                                <div class="menu-text">Personnel</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.staff.*') || Request::routeIs('esbtp.roles.*') || Request::routeIs('esbtp.personnel.unified.*') || Request::routeIs('esbtp.departments.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.personnel.unified.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.personnel.unified.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Gestion du personnel</span>
                                </a>
                                <a href="{{ route('esbtp.departments.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.departments.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Départements</span>
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
                    @endcan

                    <!-- Attendance Section -->
                    @can('module.presences.access')
                    @can('attendances.view')
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
                                <!--   <a href="{{ route('esbtp.teacher-attendance.history') }}" class="menu-sublink {{ Request::routeIs('esbtp.teacher-attendance.history') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Historique émargement enseignant</span>
                                </a>-->
                                @can('attendances.generate_codes')
                                <a href="{{ route('esbtp.attendance-codes.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.attendance-codes.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Codes d'émargement</span>
                                </a>
                                @endcan
                                <a href="{{ route('coordinateur.attendance-dashboard') }}" class="menu-sublink {{ Request::routeIs('coordinateur.attendance-dashboard') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Tableau de Bord Présences</span>
                                </a>
                            </div>
                        </div>
                    @endcan
                    @endcan

                    {{-- Enseignant-only section: hidden for users who already see canonical sections --}}
                    @can('identity.teach')
                    @if(!auth()->user()->can('admin.access'))
                        <div class="menu-category">Enseignement</div>

                        @can('classes.view')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.classes.index') }}" class="menu-link {{ Request::routeIs('esbtp.classes.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-chalkboard"></i></div>
                                <div class="menu-text">Mes classes</div>
                            </a>
                        </div>
                        @endcan

                        @can('module.notes_evaluations.access')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.notes.index') }}" class="menu-link {{ Request::routeIs('esbtp.notes.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                                <div class="menu-text">Gestion des notes</div>
                            </a>
                        </div>
                        @endcan

                        @can('notes.view')
                        <div class="menu-item">
                            <a href="{{ route('teacher.grades') }}" class="menu-link {{ Request::routeIs('teacher.grades') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-pen-to-square"></i></div>
                                <div class="menu-text">Saisie des notes</div>
                            </a>
                        </div>
                        @endcan

                        @can('attendances.view')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.attendance.mark') }}" class="menu-link {{ Request::routeIs('esbtp.attendance.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                                <div class="menu-text">Faire les émargements</div>
                            </a>
                        </div>
                        @endcan
                    @endif
                    @endcan

                    <!-- Caissier Section -->
                    @can('module.caisse.access')
                        <div class="menu-category">Caisse</div>

                        <div class="menu-item">
                            <a href="{{ route('esbtp.inscriptions.pre-inscription') }}" class="menu-link {{ Request::routeIs('esbtp.inscriptions.pre-inscription') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-user-plus"></i></div>
                                <div class="menu-text">Pré-inscription</div>
                            </a>
                        </div>

                        {{-- Consultation : uniquement pour les users caisse SANS acces module etudiants complet --}}
                        @if(!auth()->user()->can('module.etudiants.access'))
                        <div class="menu-category">Consultation</div>

                        @can('students.view')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.etudiants.index') }}" class="menu-link {{ Request::routeIs('esbtp.etudiants.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-user-graduate"></i></div>
                                <div class="menu-text">Etudiants</div>
                            </a>
                        </div>
                        @endcan

                        @can('inscriptions.view')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.inscriptions.index') }}" class="menu-link {{ Request::routeIs('esbtp.inscriptions.*') && !Request::routeIs('esbtp.inscriptions.pre-inscription') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-file-signature"></i></div>
                                <div class="menu-text">Inscriptions</div>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a href="{{ route('esbtp.reinscription.index') }}" class="menu-link {{ Request::routeIs('esbtp.reinscription.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-redo"></i></div>
                                <div class="menu-text">Reinscriptions</div>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a href="{{ route('esbtp.inscriptions.sous-reserve') }}" class="menu-link {{ Request::routeIs('esbtp.inscriptions.sous-reserve') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                                <div class="menu-text">Sous réserve</div>
                            </a>
                        </div>
                        @endcan
                        @endif

                        {{-- Comptabilité simplifiée : uniquement pour les users caisse SANS accès module comptabilité complet --}}
                        @if(!auth()->user()->can('module.comptabilite.access'))
                        <div class="menu-category">Comptabilité</div>

                        <div class="menu-item">
                            <a href="{{ route('dashboard') }}" class="menu-link {{ Request::routeIs('dashboard') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="menu-text">Tableau de bord</div>
                            </a>
                        </div>

                        @can('paiements.view')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.paiements.index') }}" class="menu-link {{ Request::routeIs('esbtp.paiements.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="menu-text">Paiements</div>
                            </a>
                        </div>
                        @endcan

                        @can('comptabilite.relances.send')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="menu-link {{ Request::routeIs('esbtp.comptabilite.relances.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-bell"></i></div>
                                <div class="menu-text">Relances</div>
                            </a>
                        </div>
                        @endcan

                        @can('frais.view')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.frais.index') }}" class="menu-link {{ Request::routeIs('esbtp.frais.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-tags"></i></div>
                                <div class="menu-text">Suivi catégories</div>
                            </a>
                        </div>
                        @endcan
                        @endif
                    @endcan

                    <!-- Messages Section -->
                    @can('identity.student')
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
                            @can('identity.student')
                                <a href="{{ route('esbtp.mes-notifications.index') }}" class="menu-link {{ Request::routeIs('esbtp.mes-notifications.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-bell"></i></div>
                                    <div class="menu-text">Mes notifications</div>
                                </a>
                            @else
                                <a href="{{ route('notifications.index') }}" class="menu-link {{ Request::routeIs('notifications.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-bell"></i></div>
                                    <div class="menu-text">Notifications</div>
                                </a>
                            @endcan
                        </div>
                    @endcan

                    <!-- Accounting Section -->
                    @can('module.comptabilite.access')
                    @can('comptabilite.access')
                        <div class="menu-category">Gestion financière</div>

                        {{-- Dashboard Comptabilité --}}
                        @can('comptabilite.dashboard.view')
                        <div class="menu-item">
                            <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="menu-link {{ Request::routeIs('esbtp.comptabilite.dashboard') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="menu-text">Dashboard Comptable</div>
                            </a>
                        </div>
                        @endcan

                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.comptabilite.*') || Request::routeIs('esbtp.frais.*') || Request::routeIs('esbtp.fee-categories.*') || Request::routeIs('esbtp.payment-categories.*') || Request::routeIs('esbtp.fees.*') || Request::routeIs('esbtp.payments.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-coins"></i></div>
                                <div class="menu-text">Comptabilité</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.comptabilite.*') || Request::routeIs('esbtp.frais.*') || Request::routeIs('esbtp.fee-categories.*') || Request::routeIs('esbtp.payment-categories.*') || Request::routeIs('esbtp.fees.*') || Request::routeIs('esbtp.payments.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.frais.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.frais.index') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Gestion des Frais</span>
                                </a>
                                <a href="{{ route('esbtp.frais.configure') }}" class="menu-sublink {{ Request::routeIs('esbtp.frais.configure') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Configuration Frais</span>
                                </a>
                                <a href="{{ route('esbtp.paiements.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.paiements.index') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Liste des Paiements</span>
                                </a>
                                <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="menu-sublink {{ Request::routeIs('esbtp.paiements.suivi-categories') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Suivi par Catégorie</span>
                                </a>
                                {{-- Relances --}}
                                @can('comptabilite.relances.send')
                                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.comptabilite.relances.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Relances</span>
                                </a>
                                @endcan
                            </div>
                        </div>
                    @endcan
                    @endcan

                    <!-- Announcements Section -->
                    @can('module.communication.access')
                    @can('annonces.view')
                    <div class="menu-category">Communication</div>
                    <!-- Announcements Management -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.annonces.index') }}" class="menu-link {{ Request::routeIs('esbtp.annonces.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-bullhorn"></i></div>
                                <div class="menu-text">Annonces</div>
                            </a>
                        </div>

                        @can('annonces.create')
                        <!-- Create Announcement -->
                        <div class="menu-item">
                            <a href="{{ route('esbtp.annonces.create') }}" class="menu-link {{ Request::routeIs('esbtp.annonces.create') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-plus-circle"></i></div>
                                <div class="menu-text">Créer une annonce</div>
                            </a>
                        </div>
                        @endcan
                    @endcan
                    @endcan

                    <!-- Service Technique Section - ADC Only (role-gated, not permission) -->
                    @role('serviceTechnique')
                        <div class="menu-category">Service Technique ADC</div>

                        <!-- Configuration System -->
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ Request::routeIs('esbtp.paywall-config.*') || Request::routeIs('esbtp.matricule-config.*') ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-tools"></i></div>
                                <div class="menu-text">Configuration</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ Request::routeIs('esbtp.paywall-config.*') || Request::routeIs('esbtp.matricule-config.*') ? 'show' : '' }}">
                                <a href="{{ route('esbtp.paywall-config.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.paywall-config.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span><i class="fas fa-shield-alt me-2"></i>Paywall</span>
                                </a>
                                <a href="{{ route('esbtp.matricule-config.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.matricule-config.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span><i class="fas fa-id-card me-2"></i>Matricule</span>
                                </a>
                                <a href="{{ route('esbtp.roles-permissions.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.roles-permissions.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span><i class="fas fa-user-shield me-2"></i>Rôles & Permissions</span>
                                </a>
                                <a href="{{ route('esbtp.bulletin-style.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.bulletin-style.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span><i class="fas fa-file-alt me-2"></i>Style Bulletin</span>
                                </a>
                            </div>
                        </div>
                    @endrole

                    <!-- System Section -->
                    @can('system.manage')
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
                                <!--<a href="{{ route('esbtp.logs.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.logs.*') ? 'active' : '' }}">
                                    <span class="menu-dot"></span>
                                    <span>Journaux système</span>
                                </a>-->
                            </div>
                        </div>
                    @endcan

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

                    <div class="menu-item">
                        <a href="{{ route('esbtp.mes-paiements.index') }}" class="menu-link {{ request()->routeIs('esbtp.mes-paiements.*') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-wallet"></i></div>
                            <div class="menu-text">Mes paiements</div>
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
                    @elserole('coordinateur')
                    <div class="menu-item">
                        <a href="{{ route('coordinateur.profile') }}" class="menu-link {{ request()->routeIs('coordinateur.profile') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-user-tie"></i></div>
                            <div class="menu-text">Mon profil</div>
                        </a>
                    </div>
                    @elserole('teacher')
                    <div class="menu-item">
                        <a href="{{ route('teacher.profile') }}" class="menu-link {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
                            <div class="menu-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="menu-text">Mon profil</div>
                        </a>
                    </div>
                    @else
                    <div class="menu-item">
                        @php
                            $profileRoute = 'admin.profile';
                            if (auth()->user()->can('identity.teach')) {
                                $profileRoute = 'teacher.profile';
                            } elseif (auth()->user()->can('identity.coordinate')) {
                                $profileRoute = 'coordinateur.profile';
                            }
                        @endphp
                        <a href="{{ route($profileRoute) }}" class="menu-link {{ request()->routeIs($profileRoute) ? 'active' : '' }}">
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
                        <button class="navbar-toggle d-lg-none" id="sidebar-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <!-- Le titre KLASSCI est déjà dans la sidebar, pas besoin de le dupliquer -->
                    </div>

                    <div class="navbar-center d-none d-lg-block">
                        <div class="header-actions">
                            <input type="search" class="search-bar" id="global-search" placeholder="Rechercher dans l'application..." autocomplete="off">
                            <div id="search-results" class="search-results" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="navbar-right">
                        <!-- Notifications -->
                        <div class="dropdown">
                            <button class="btn-acasi icon-only" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                                    <li class="dropdown-loading">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                                        <span class="ms-2">Chargement des notifications...</span>
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
                            <button class="btn-acasi icon-only" type="button" id="messagesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-envelope"></i>
                                <span class="navbar-badge" id="messages-count" style="display: none;">0</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="messagesDropdown">
                                <li>
                                    <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                        Messages
                                        <button class="btn btn-sm btn-link text-primary p-0" id="mark-all-messages-read" style="font-size: 0.75rem;">
                                            Tout marquer comme lu
                                        </button>
                                    </h6>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <div id="messages-list">
                                    <li class="dropdown-loading">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                                        <span class="ms-2">Chargement des messages...</span>
                                    </li>
                                </div>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    @can('identity.student')
                                        <a class="dropdown-item text-center view-all" href="{{ route('esbtp.mes-messages.index') }}">
                                            <i class="fas fa-envelope-open me-1"></i>
                                            Voir tous les messages
                                        </a>
                                    @else
                                        <a class="dropdown-item text-center view-all" href="{{ route('esbtp.annonces.index') }}">
                                            <i class="fas fa-envelope-open me-1"></i>
                                            Voir tous les messages
                                        </a>
                                    @endcan
                                </li>
            </ul>
    </div>

                    <!-- Quick Actions -->
                    <div class="dropdown">
                            <button class="btn-acasi icon-only" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-th-large"></i>
                        </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown quick-actions-dropdown" aria-labelledby="quickActionsDropdown">
                                <li>
                                    <h6 class="dropdown-header">Actions rapides</h6>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <div class="quick-actions-grid" id="quick-actions-list">
                                        @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']))
                                            <a href="{{ route('esbtp.etudiants.create') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                                    <i class="fas fa-user-plus"></i>
                                                </div>
                                                <span class="quick-action-text">Nouvel étudiant</span>
                                            </a>
                                            <a href="{{ route('esbtp.inscriptions.create') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </div>
                                                <span class="quick-action-text">Nouvelle inscription</span>
                                            </a>
                                            <a href="{{ route('esbtp.evaluations.create') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                                                    <i class="fas fa-plus-circle"></i>
                                                </div>
                                                <span class="quick-action-text">Nouvelle évaluation</span>
                                            </a>
                                            <a href="{{ route('esbtp.classes.create') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #0453cb, #5e91de); color: white;">
                                                    <i class="fas fa-school"></i>
                                                </div>
                                                <span class="quick-action-text">Nouvelle classe</span>
                                            </a>
                                        @elseif(auth()->user()->can('identity.coordinate'))
                                            <a href="{{ route('esbtp.emploi-temps.create') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </div>
                                                <span class="quick-action-text">Nouvel emploi du temps</span>
                                            </a>
                                            <a href="{{ route('esbtp.evaluations.create') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                                                    <i class="fas fa-plus-circle"></i>
                                                </div>
                                                <span class="quick-action-text">Nouvelle évaluation</span>
                                            </a>
                                            <a href="{{ route('esbtp.annonces.create') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">
                                                    <i class="fas fa-bullhorn"></i>
                                                </div>
                                                <span class="quick-action-text">Nouvelle annonce</span>
                                            </a>
                                        @elseif(auth()->user()->can('identity.student'))
                                            <a href="{{ route('esbtp.mes-evaluations.index') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                                                    <i class="fas fa-clipboard-list"></i>
                                                </div>
                                                <span class="quick-action-text">Mes évaluations</span>
                                            </a>
                                            <a href="{{ route('esbtp.mes-notes.index') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                                                    <i class="fas fa-star"></i>
                                                </div>
                                                <span class="quick-action-text">Mes notes</span>
                                            </a>
                                            <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                                <span class="quick-action-text">Mon emploi du temps</span>
                                            </a>
                                            <a href="{{ route('esbtp.mes-messages.index') }}" class="quick-action-item">
                                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #0453cb, #5e91de); color: white;">
                                                    <i class="fas fa-envelope"></i>
                                                </div>
                                                <span class="quick-action-text">Mes messages</span>
                                            </a>
                                        @else
                                            <div class="dropdown-empty">
                                                <i class="fas fa-th-large"></i>
                                                <div class="dropdown-empty-title">Actions rapides</div>
                                                <div class="dropdown-empty-text">Aucune action rapide disponible</div>
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            </ul>
                </div>

                <!-- User Profile -->
                <div class="dropdown ms-2">
                    <button class="btn-acasi profile-btn" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                    </button>
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
                                        @elserole('coordinateur')
                                            <a class="dropdown-item" href="{{ route('coordinateur.profile') }}">
                                                <i class="fas fa-user-tie me-2"></i> Mon profil
                                            </a>
                                        @elserole('teacher')
                                            <a class="dropdown-item" href="{{ route('teacher.profile') }}">
                                                <i class="fas fa-chalkboard-teacher me-2"></i> Mon profil
                                            </a>
                                        @elserole('enseignant')
                                            <a class="dropdown-item" href="{{ route('teacher.profile') }}">
                                                <i class="fas fa-chalkboard-teacher me-2"></i> Mon profil
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

                @auth
                    @php
                        $anneeCouranteModal = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
                        $anneeCouranteEndDate = $anneeCouranteModal?->end_date
                            ? \Carbon\Carbon::parse($anneeCouranteModal->end_date)
                            : null;
                        $anneeCouranteExpired = $anneeCouranteModal && $anneeCouranteEndDate && $anneeCouranteEndDate->isPast();
                        $isSuperAdmin = auth()->user()?->can('admin.access');
                        $canValidateInscriptions = auth()->user()?->can('inscriptions.validate');
                        $canAccessTimetable = auth()->user()?->can('timetables.view') || auth()->user()?->can('timetables.view_all');
                        $canAccessEvaluations = auth()->user()?->can('exams.view') || auth()->user()?->can('evaluations.view');
                        $canAccessNotes = auth()->user()?->can('notes.view') || auth()->user()?->can('notes.create') || auth()->user()?->can('notes.edit') || auth()->user()?->can('notes.manage_own');
                        $canSeeGradingReminder = $canAccessEvaluations || $canAccessNotes;
                        $pendingCurrentYearInscriptionsCount = 0;
                        $pendingCurrentYearInscriptionsByStep = [];
                        $timetableShortcut = ['show' => false];
                        $evaluationGradingShortcut = ['show' => false];
                        $evaluationPublishShortcut = ['show' => false];

                        if ($canValidateInscriptions && $anneeCouranteModal) {
                            $pendingCurrentYearQuery = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeCouranteModal->id)
                                ->where(function($query) {
                                    $query->whereIn('status', ['en_attente', 'pending'])
                                        ->orWhere(function($subQuery) {
                                            $subQuery->where('status', 'active')
                                                ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                                        });
                                });

                            $pendingCurrentYearInscriptionsCount = (clone $pendingCurrentYearQuery)->count();
                            $pendingCurrentYearInscriptionsByStep = [
                                'prospect' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'prospect')->count(),
                                'documents_complets' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'documents_complets')->count(),
                                'en_validation' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'en_validation')->count(),
                            ];
                        }

                        if ($canAccessTimetable && $anneeCouranteModal) {
                            $timetableShortcut = app(\App\Services\TimetableShortcutService::class)->getShortcutSummary($anneeCouranteModal);
                        }

                        if ($canSeeGradingReminder && $anneeCouranteModal) {
                            $evaluationGradingShortcut = app(\App\Services\EvaluationGradingShortcutService::class)
                                ->getShortcutSummary($anneeCouranteModal, auth()->user());
                        }

                        if ($canAccessEvaluations && $anneeCouranteModal) {
                            $evaluationPublishShortcut = app(\App\Services\EvaluationPublishShortcutService::class)
                                ->getShortcutSummary($anneeCouranteModal);
                        }
                    @endphp

                    @if($anneeCouranteExpired)
                        <div class="modal fade" id="anneeCouranteExpiredModal" tabindex="-1" aria-labelledby="anneeCouranteExpiredModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning-subtle">
                                        <h5 class="modal-title" id="anneeCouranteExpiredModalLabel">
                                            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Année universitaire échue
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-2">
                                            L'année universitaire courante <strong>{{ $anneeCouranteModal->name }}</strong>
                                            s'est terminée le <strong>{{ $anneeCouranteEndDate->format('d/m/Y') }}</strong>.
                                        </p>

                                        @if($isSuperAdmin)
                                            <div class="alert alert-warning mb-3">
                                                <strong>Action requise :</strong> pensez à activer la nouvelle année courante.
                                            </div>
                                            <ol class="ps-3 mb-0">
                                                <li>Ouvrir la page des années universitaires.</li>
                                                <li>Cliquer sur “Activer” pour la nouvelle année.</li>
                                                <li>Revenir ici pour recharger les données.</li>
                                            </ol>
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                <strong>Info :</strong> merci de signaler à la direction qu'il faut activer la nouvelle année courante.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                            Fermer
                                        </button>
                                        @if($isSuperAdmin)
                                            <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-warning">
                                                <i class="fas fa-calendar-check me-1"></i>Aller aux années
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($canValidateInscriptions && ($pendingCurrentYearInscriptionsCount ?? 0) > 0 && $anneeCouranteModal)
                        <div class="modal fade" id="pendingInscriptionsReminderModal" tabindex="-1" role="dialog" aria-labelledby="pendingInscriptionsReminderModalLabel" aria-hidden="true" data-reminder-key="pendingInscriptionsReminder.user.{{ auth()->id() }}">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="pendingInscriptionsReminderModalLabel">
                                            Inscriptions en attente - {{ $anneeCouranteModal->name }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>{{ $pendingCurrentYearInscriptionsCount }}</strong> inscription(s) sont en attente de validation pour l'année universitaire courante.</p>
                                        @if(!empty($pendingCurrentYearInscriptionsByStep))
                                            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin: 12px 0;">
                                                <div style="font-weight: 600; margin-bottom: 6px;">Répartition par étape :</div>
                                                <ul style="padding-left: 20px; margin: 0;">
                                                    <li>Prospect : <strong>{{ $pendingCurrentYearInscriptionsByStep['prospect'] ?? 0 }}</strong></li>
                                                    <li>Documents complets : <strong>{{ $pendingCurrentYearInscriptionsByStep['documents_complets'] ?? 0 }}</strong></li>
                                                    <li>En validation : <strong>{{ $pendingCurrentYearInscriptionsByStep['en_validation'] ?? 0 }}</strong></li>
                                                </ul>
                                            </div>
                                        @endif
                                        <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                                            <li>Les dossiers dont le workflow n'est pas à <strong>etudiant_cree</strong> restent en attente.</li>
                                            <li>Ces étudiants ne seront pas comptés dans KLASSCI pour l'année <strong>{{ $anneeCouranteModal->name }}</strong>.</li>
                                            <li>Validez ou complétez les dossiers pour finaliser l'inscription.</li>
                                        </ol>
                                        <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                                            <strong>Astuce :</strong><br>
                                            Le workflow doit atteindre <strong>etudiant_cree</strong> pour activer l'étudiant dans l'année courante.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('pendingInscriptionsReminderModal').dataset.reminderKey, String(Date.now()))">
                                            Rappeler plus tard
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        <a href="{{ route('esbtp.inscriptions.administration', ['annee' => $anneeCouranteModal->id]) }}" class="btn btn-primary">
                                            <i class="fas fa-check-circle"></i> Consulter les inscriptions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($canAccessTimetable && !empty($timetableShortcut) && ($timetableShortcut['show'] ?? false))
                        <div class="modal fade" id="timetableReminderModal" tabindex="-1" role="dialog" aria-labelledby="timetableReminderModalLabel" aria-hidden="true" data-reminder-key="timetableReminder.user.{{ auth()->id() }}">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="timetableReminderModalLabel">
                                            Emplois du temps a renouveler
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-3">Certaines classes n'ont pas d'emploi du temps valide pour la periode courante.</p>
                                        <ul class="mb-3">
                                            @if($timetableShortcut['missing'] > 0)
                                                <li><strong>{{ $timetableShortcut['missing'] }}</strong> classe(s) sans emploi du temps</li>
                                            @endif
                                            @if($timetableShortcut['expired'] > 0)
                                                <li><strong>{{ $timetableShortcut['expired'] }}</strong> emploi(s) expire(s)</li>
                                            @endif
                                            @if($timetableShortcut['expiring_soon'] > 0)
                                                <li><strong>{{ $timetableShortcut['expiring_soon'] }}</strong> emploi(s) expirant bientot</li>
                                            @endif
                                        </ul>
                                        <div class="alert alert-warning mb-0">
                                            <strong>Astuce :</strong> utilisez la generation rapide pour creer ou dupliquer les emplois du temps manquants.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('timetableReminderModal').dataset.reminderKey, String(Date.now()))">
                                            Rappeler plus tard
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        <a href="{{ route('esbtp.emploi-temps.index', ['quick_generate' => 1]) }}" class="btn btn-warning">
                                            <i class="fas fa-calendar-plus me-1"></i>Aller aux emplois du temps
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($canSeeGradingReminder && !empty($evaluationGradingShortcut) && ($evaluationGradingShortcut['show'] ?? false))
                        @php
                            $gradingCtaUrl = $canAccessEvaluations
                                ? route('esbtp.evaluations.index')
                                : route('esbtp.notes.index');
                        @endphp
                        <div class="modal fade" id="evaluationGradingReminderModal" tabindex="-1" role="dialog" aria-labelledby="evaluationGradingReminderModalLabel" aria-hidden="true" data-reminder-key="evaluationGradingReminder.user.{{ auth()->id() }}">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="evaluationGradingReminderModalLabel">
                                            Notes a saisir
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-3"><strong>{{ $evaluationGradingShortcut['total'] ?? 0 }}</strong> evaluation(s) sont passees et attendent la saisie des notes.</p>
                                        <ul class="mb-3">
                                            @if(($evaluationGradingShortcut['missing_notes'] ?? 0) > 0)
                                                <li><strong>{{ $evaluationGradingShortcut['missing_notes'] }}</strong> sans notes saisies</li>
                                            @endif
                                            @if(($evaluationGradingShortcut['notes_unpublished'] ?? 0) > 0)
                                                <li><strong>{{ $evaluationGradingShortcut['notes_unpublished'] }}</strong> notes saisies mais non publiees</li>
                                            @endif
                                        </ul>
                                        @if(!empty($evaluationGradingShortcut['items']))
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Evaluation</th>
                                                            <th>Classe</th>
                                                            <th>Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($evaluationGradingShortcut['items'] as $item)
                                                            <tr>
                                                                <td>
                                                                    <div class="fw-semibold">{{ $item['title'] ?? '—' }}</div>
                                                                    <small class="text-muted">{{ $item['matiere'] ?? '—' }}</small>
                                                                </td>
                                                                <td>{{ $item['classe'] ?? '—' }}</td>
                                                                <td>{{ !empty($item['date']) ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y') : '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('evaluationGradingReminderModal').dataset.reminderKey, String(Date.now()))">
                                            Rappeler plus tard
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        <a href="{{ $gradingCtaUrl }}" class="btn btn-primary">
                                            <i class="fas fa-pen-to-square me-1"></i>Aller a la saisie
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($canAccessEvaluations && !empty($evaluationPublishShortcut) && ($evaluationPublishShortcut['show'] ?? false))
                        <div class="modal fade" id="evaluationPublishReminderModal" tabindex="-1" role="dialog" aria-labelledby="evaluationPublishReminderModalLabel" aria-hidden="true" data-reminder-key="evaluationPublishReminder.user.{{ auth()->id() }}">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="evaluationPublishReminderModalLabel">
                                            Evaluations a activer
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-3"><strong>{{ $evaluationPublishShortcut['total'] ?? 0 }}</strong> evaluation(s) sont encore en brouillon.</p>
                                        <ul class="mb-3">
                                            @if(($evaluationPublishShortcut['overdue'] ?? 0) > 0)
                                                <li><strong>{{ $evaluationPublishShortcut['overdue'] }}</strong> en retard (date depassee)</li>
                                            @endif
                                            @if(($evaluationPublishShortcut['soon'] ?? 0) > 0)
                                                <li><strong>{{ $evaluationPublishShortcut['soon'] }}</strong> a publier bientot</li>
                                            @endif
                                            @if(($evaluationPublishShortcut['undated'] ?? 0) > 0)
                                                <li><strong>{{ $evaluationPublishShortcut['undated'] }}</strong> sans date</li>
                                            @endif
                                        </ul>
                                        <div class="alert alert-info mb-0">
                                            <strong>Action :</strong> publiez les evaluations pour activer la saisie des notes.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('evaluationPublishReminderModal').dataset.reminderKey, String(Date.now()))">
                                            Rappeler plus tard
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        <a href="{{ route('esbtp.evaluations.index') }}" class="btn btn-primary">
                                            <i class="fas fa-clipboard-check me-1"></i>Aller aux evaluations
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endauth

            {{-- Alerte expiration mot de passe --}}
            @auth
                @if(\App\Services\UserService::isPasswordExpiringSoon(auth()->user()))
                    @php
                        $refDate = auth()->user()->password_changed_at ?? auth()->user()->created_at;
                        $expiryMonths = (int) \App\Models\Setting::get('password_expiry_months', 6);
                        $expiresAt = $refDate ? $refDate->copy()->addMonths($expiryMonths) : now();
                        $daysLeft = (int) now()->diffInDays($expiresAt, false);
                    @endphp
                    <div style="background: linear-gradient(135deg, #0453cb, #5e91de); color: white; padding: 0.75rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-clock" style="font-size: 1.2rem;"></i>
                            <div>
                                <strong>Votre mot de passe expire dans {{ $daysLeft }} jour{{ $daysLeft > 1 ? 's' : '' }}</strong>
                                <div style="font-size: 0.85rem; opacity: 0.85;">Pour éviter d'être bloqué, changez-le maintenant depuis votre profil.</div>
                            </div>
                        </div>
                        <a href="{{ route('password.change.form') }}" style="background: white; color: #0453cb; padding: 0.4rem 1rem; border-radius: 0.375rem; font-weight: 600; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">
                            Changer maintenant
                        </a>
                    </div>
                @endif
            @endauth

            @yield('content')
        </div>
        </main>
    </div>

    @include('components.chatbot.widget')

    <!-- Debug Helper - Doit être chargé en PREMIER -->
    <script>
        // Variable globale pour activer/désactiver les logs debug
        const debugMeta = document.querySelector('meta[name="app-debug"]');
        window.DEBUG_MODE = debugMeta ? debugMeta.content === '1' : false;
    </script>
    <script src="{{ asset('js/debug-helper.js') }}"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="{{ asset('js/navbar-diagnostics.js') }}"></script>
    <script>
            document.addEventListener('DOMContentLoaded', function() {
                const anneeModal = document.getElementById('anneeCouranteExpiredModal');
                if (anneeModal) {
                const storageKey = 'annee-courante-expired-last-seen';
                const lastSeen = Number(localStorage.getItem(storageKey) || 0);
                const now = Date.now();
                const oneHourMs = 60 * 60 * 1000;

                if (now - lastSeen >= oneHourMs) {
                    const modal = new bootstrap.Modal(anneeModal);
                    modal.show();
                    localStorage.setItem(storageKey, String(now));
                }

                    anneeModal.addEventListener('hidden.bs.modal', function () {
                        localStorage.setItem(storageKey, String(Date.now()));
                    });
                }

                const pendingModalElement = document.getElementById('pendingInscriptionsReminderModal');
                if (pendingModalElement && typeof bootstrap !== 'undefined') {
                    const reminderKey = pendingModalElement.dataset.reminderKey || 'pendingInscriptionsReminder';
                    const lastSeen = Number(localStorage.getItem(reminderKey) || 0);
                    const now = Date.now();
                    const oneHourMs = 60 * 60 * 1000;

                    if (now - lastSeen >= oneHourMs) {
                        const pendingModal = new bootstrap.Modal(pendingModalElement);
                        pendingModal.show();
                        localStorage.setItem(reminderKey, String(now));
                    }

                    pendingModalElement.addEventListener('hidden.bs.modal', function () {
                        localStorage.setItem(reminderKey, String(Date.now()));
                    });
                }

                const timetableModalElement = document.getElementById('timetableReminderModal');
                if (timetableModalElement && typeof bootstrap !== 'undefined') {
                    const reminderKey = timetableModalElement.dataset.reminderKey || 'timetableReminder';
                    const lastSeen = Number(localStorage.getItem(reminderKey) || 0);
                    const now = Date.now();
                    const oneHourMs = 60 * 60 * 1000;

                    if (now - lastSeen >= oneHourMs) {
                        const timetableModal = new bootstrap.Modal(timetableModalElement);
                        timetableModal.show();
                        localStorage.setItem(reminderKey, String(now));
                    }

                    timetableModalElement.addEventListener('hidden.bs.modal', function () {
                        localStorage.setItem(reminderKey, String(Date.now()));
                    });
                }

                const gradingModalElement = document.getElementById('evaluationGradingReminderModal');
                if (gradingModalElement && typeof bootstrap !== 'undefined') {
                    const reminderKey = gradingModalElement.dataset.reminderKey || 'evaluationGradingReminder';
                    const lastSeen = Number(localStorage.getItem(reminderKey) || 0);
                    const now = Date.now();
                    const oneHourMs = 60 * 60 * 1000;

                    if (now - lastSeen >= oneHourMs) {
                        const gradingModal = new bootstrap.Modal(gradingModalElement);
                        gradingModal.show();
                        localStorage.setItem(reminderKey, String(now));
                    }

                    gradingModalElement.addEventListener('hidden.bs.modal', function () {
                        localStorage.setItem(reminderKey, String(Date.now()));
                    });
                }

                const evaluationPublishModalElement = document.getElementById('evaluationPublishReminderModal');
                if (evaluationPublishModalElement && typeof bootstrap !== 'undefined') {
                    const reminderKey = evaluationPublishModalElement.dataset.reminderKey || 'evaluationPublishReminder';
                    const lastSeen = Number(localStorage.getItem(reminderKey) || 0);
                    const now = Date.now();
                    const oneHourMs = 60 * 60 * 1000;

                    if (now - lastSeen >= oneHourMs) {
                        const publishModal = new bootstrap.Modal(evaluationPublishModalElement);
                        publishModal.show();
                        localStorage.setItem(reminderKey, String(now));
                    }

                    evaluationPublishModalElement.addEventListener('hidden.bs.modal', function () {
                        localStorage.setItem(reminderKey, String(Date.now()));
                    });
                }

            debugLog('🚀 Initialisation de l\'application...');

            // 1. Initialiser Bootstrap dropdowns
            debugLog('🔽 Initialisation des dropdowns Bootstrap...');
            const dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            const dropdownList = [...dropdownElementList].map(dropdownToggleEl => {
                try {
                    return new bootstrap.Dropdown(dropdownToggleEl);
                } catch (error) {
                    debugError('Erreur initialisation dropdown:', error);
                    return null;
                }
            });
            debugLog(`✅ ${dropdownList.filter(d => d !== null).length} dropdowns initialisés`);

            // 2. Gestion du toggle sidebar
            debugLog('🍔 Configuration du toggle sidebar...');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (sidebarToggle && sidebar) {
                // Supprimer les anciens event listeners
                sidebarToggle.replaceWith(sidebarToggle.cloneNode(true));
                const newSidebarToggle = document.getElementById('sidebar-toggle');

                newSidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('🍔 Toggle sidebar cliqué');

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
                        debugLog('📱 Overlay cliqué - fermeture sidebar');
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                        document.body.classList.remove('sidebar-open');
                    });
                }

                debugLog('✅ Toggle sidebar configuré');
            } else {
                debugError('❌ Éléments sidebar non trouvés');
            }

            // 3. Gestion des accordéons sidebar
            debugLog('🎵 Configuration des accordéons sidebar...');
            const accordionButtons = document.querySelectorAll('.menu-accordion-btn');

            accordionButtons.forEach((button, index) => {
                // Supprimer les anciens event listeners
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);

                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog(`🎵 Accordéon ${index + 1} cliqué`);

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

            debugLog(`✅ ${accordionButtons.length} accordéons configurés`);

            // 4. Améliorer la scrollbar sidebar
            debugLog('📜 Configuration de la scrollbar sidebar...');
            const sidebarMenu = document.querySelector('.sidebar-menu');
            if (sidebarMenu) {
                // Ajouter padding bottom pour éviter que le dernier élément soit coupé
                sidebarMenu.style.paddingBottom = '20px';
                debugLog('✅ Scrollbar sidebar configurée');
            }

            // 5. Charger les données navbar
            debugLog('📡 Chargement des données navbar...');
            loadNavbarData();

            // 6. Configurer la recherche
            debugLog('🔍 Configuration de la recherche...');
            setupSearchFunctionality();

            debugLog('🎉 Initialisation terminée !');
        });

        // Load navbar data (notifications, messages, quick actions)
        function loadNavbarData() {
            debugLog('📡 Début chargement données navbar...');

            // Load notifications
            fetch('{{ route("navbar.notifications") }}')
                .then(response => {
                    debugLog('🔔 Réponse notifications:', response.status);
                    return response.json();
                })
                .then(data => {
                    debugLog('🔔 Données notifications reçues:', data);
                    updateNotifications(data.notifications, data.unread_count);
                })
                .catch(error => {
                    debugError('❌ Erreur chargement notifications:', error);
                    document.getElementById('notifications-list').innerHTML = `
                        <li class="dropdown-empty">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <div class="dropdown-empty-title">Erreur de chargement</div>
                            <div class="dropdown-empty-text">Impossible de charger les notifications. Veuillez réessayer.</div>
                        </li>
                    `;
                });

            // Load messages
            fetch('{{ route("navbar.messages") }}')
                .then(response => {
                    debugLog('💬 Réponse messages:', response.status);
                    return response.json();
                })
                .then(data => {
                    debugLog('💬 Données messages reçues:', data);
                    updateMessages(data.messages, data.unread_count);
                })
                .catch(error => {
                    debugError('❌ Erreur chargement messages:', error);
                    document.getElementById('messages-list').innerHTML = `
                        <li class="dropdown-empty">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <div class="dropdown-empty-title">Erreur de chargement</div>
                            <div class="dropdown-empty-text">Impossible de charger les messages. Veuillez réessayer.</div>
                        </li>
                    `;
                });

            // Load quick actions
            fetch('{{ route("navbar.quick-actions") }}')
                .then(response => {
                    debugLog('⚡ Réponse actions rapides:', response.status);
                    return response.json();
                })
                .then(data => {
                    debugLog('⚡ Données actions rapides reçues:', data);
                    updateQuickActions(data.actions);
                })
                .catch(error => {
                    debugError('❌ Erreur chargement actions rapides:', error);
                    document.getElementById('quick-actions-list').innerHTML = `
                        <div class="dropdown-empty">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <div class="dropdown-empty-title">Erreur de chargement</div>
                            <div class="dropdown-empty-text">Impossible de charger les actions rapides. Veuillez réessayer.</div>
                        </div>
                    `;
                });
        }

        // Update notifications
        function updateNotifications(notifications, unreadCount) {
            debugLog('🔔 Mise à jour notifications:', { notifications, unreadCount });
            const notificationsList = document.getElementById('notifications-list');
            const notificationsCount = document.getElementById('notifications-count');
            const markAllBtn = document.getElementById('mark-all-notifications-read');

            // Mettre à jour le badge avec le vrai count du serveur
            if (unreadCount > 0) {
                notificationsCount.textContent = unreadCount > 99 ? '99+' : unreadCount;
                notificationsCount.style.display = 'inline';
            } else {
                notificationsCount.style.display = 'none';
            }

            // Mettre à jour le bouton header selon l'état
            if (markAllBtn) {
                if (unreadCount > 0) {
                    markAllBtn.innerHTML = 'Tout marquer comme lu';
                    markAllBtn.onclick = function() { markAllNotificationsAsRead(); };
                } else if (notifications.length > 0) {
                    markAllBtn.innerHTML = 'Tout supprimer';
                    markAllBtn.onclick = function() { deleteAllNotifications(); };
                } else {
                    markAllBtn.style.display = 'none';
                }
            }

            // État vide avec design amélioré
            if (notifications.length === 0) {
                notificationsList.innerHTML = `
                    <li class="dropdown-empty">
                        <i class="fas fa-bell-slash"></i>
                        <div class="dropdown-empty-title">Aucune notification</div>
                        <div class="dropdown-empty-text">Vous êtes à jour ! Aucune nouvelle notification.</div>
                    </li>
                `;
                return;
            }

            let html = '';
            notifications.forEach(notification => {
                const hasLink = notification.url && notification.url !== '#';
                const isVirtual = notification.is_virtual;
                const clickHandler = hasLink && !isVirtual
                    ? `onclick="openNotificationLink('${notification.url}', ${notification.id})"`
                    : hasLink
                        ? `onclick="window.location.href='${notification.url}'"`
                        : `onclick="markNotificationAsRead(${notification.id})"`;
                html += `
                    <li class="notification-item-container">
                        <div class="dropdown-item notification-item ${notification.read ? '' : 'unread'}" data-notification-id="${notification.id}" ${clickHandler}>
                            <div class="notification-icon bg-${notification.type || 'primary'}">
                                <i class="${notification.icon || 'fas fa-bell'}"></i>
                            </div>
                            <div class="notification-content" style="text-align: left; width: 100%;">
                                <div class="notification-title">${escapeHtml(notification.title)}</div>
                                <div class="notification-text">${escapeHtml(notification.message)}</div>
                                <div class="notification-footer">
                                    <div class="notification-time">${notification.time}</div>
                                    ${isVirtual ? '<span class="notification-pill">Action rapide</span>' : ''}
                                </div>
                            </div>
                            <div class="notification-actions">
                                ${isVirtual ? '' : `
                                    <button class="notification-delete-btn" onclick="deleteNotification(${notification.id})" title="Supprimer">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `}
                            </div>
                        </div>
                    </li>
                `;
            });
            notificationsList.innerHTML = html;
        }

        // Update messages
        function updateMessages(messages, unreadCount) {
            debugLog('💬 Mise à jour messages:', { messages, unreadCount });
            const messagesList = document.getElementById('messages-list');
            const messagesCount = document.getElementById('messages-count');
            const markAllMessagesBtn = document.getElementById('mark-all-messages-read');

            // Mettre à jour le badge
            if (unreadCount > 0) {
                messagesCount.textContent = unreadCount > 99 ? '99+' : unreadCount;
                messagesCount.style.display = 'inline';
            } else {
                messagesCount.style.display = 'none';
            }

            // Mettre à jour le bouton header selon l'état
            if (markAllMessagesBtn) {
                if (unreadCount > 0) {
                    markAllMessagesBtn.innerHTML = 'Tout marquer comme lu';
                    markAllMessagesBtn.onclick = function() { markAllMessagesAsRead(); };
                } else if (messages.length > 0) {
                    markAllMessagesBtn.innerHTML = 'Tout supprimer';
                    markAllMessagesBtn.onclick = function() { deleteAllMessages(); };
                } else {
                    markAllMessagesBtn.style.display = 'none';
                }
            }

            // État vide avec design amélioré
            if (messages.length === 0) {
                messagesList.innerHTML = `
                    <li class="dropdown-empty">
                        <i class="fas fa-envelope-open"></i>
                        <div class="dropdown-empty-title">Aucun message</div>
                        <div class="dropdown-empty-text">Votre boîte de réception est vide.</div>
                    </li>
                `;
                return;
            }

            let html = '';
            messages.forEach(message => {
                html += `
                    <li>
                        <div class="dropdown-item message-item ${message.read ? '' : 'unread'}" data-message-id="${message.id}" onclick="openMessageLink('${message.url || '#'}', ${message.id})" style="cursor: pointer;">
                            <div class="message-avatar">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="message-content">
                                <div class="message-title">${escapeHtml(message.title)}</div>
                                <div class="message-text">${escapeHtml(message.message)}</div>
                                <div class="message-time">${message.time} • ${escapeHtml(message.sender || 'Système')}</div>
                            </div>
                            <div class="message-actions">
                                <button class="message-delete-btn" onclick="deleteMessage(${message.id})" title="Supprimer">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </li>
                `;
            });
            messagesList.innerHTML = html;
        }

        // Update quick actions
        function updateQuickActions(actions) {
            debugLog('⚡ Mise à jour actions rapides:', actions);
            const quickActionsList = document.getElementById('quick-actions-list');

            // État vide avec design amélioré
            if (actions.length === 0) {
                quickActionsList.innerHTML = `
                    <div class="dropdown-empty">
                        <i class="fas fa-rocket"></i>
                        <div class="dropdown-empty-title">Aucune action</div>
                        <div class="dropdown-empty-text">Aucune action rapide n'est disponible pour le moment.</div>
                    </div>
                `;
                return;
            }

            let html = '';
            actions.forEach(action => {
                // Mapping des couleurs vers les backgrounds appropriés
                const colorMap = {
                    'primary': 'background: #3b82f6; color: white; border-color: #2563eb;',
                    'success': 'background: #10b981; color: white; border-color: #059669;',
                    'warning': 'background: #f59e0b; color: white; border-color: #d97706;',
                    'danger': 'background: #ef4444; color: white; border-color: #dc2626;',
                    'info': 'background: #06b6d4; color: white; border-color: #0891b2;',
                    'secondary': 'background: #6b7280; color: white; border-color: #4b5563;'
                };
                const iconStyle = colorMap[action.color] || colorMap['primary'];
                
                html += `
                    <a href="${action.url}" class="quick-action-item">
                        <div class="quick-action-icon" style="${iconStyle}">
                            <i class="${action.icon}"></i>
                        </div>
                        <div class="quick-action-text">${escapeHtml(action.title)}</div>
                    </a>
                `;
            });
            quickActionsList.innerHTML = html;
        }

        // Fonction utilitaire pour échapper le HTML
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Setup search functionality
        function setupSearchFunctionality() {
            debugLog('🔍 Configuration de la recherche...');
            const searchInput = document.getElementById('global-search');
            const searchResults = document.getElementById('search-results');
            let searchTimeout;

            if (searchInput) {
                debugLog('✅ Search input trouvé, ajout des event listeners');

                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    debugLog('🔍 Search input - nouvelle valeur:', query);

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
                    debugLog('🔍 Search input focus');
                    if (this.value.trim().length >= 2 && searchResults.innerHTML.trim() !== '') {
                        searchResults.style.display = 'block';
                        searchResults.classList.add('show');
                    }
                });

                debugLog('✅ Search functionality configurée');
            } else {
                debugError('❌ Search input non trouvé');
            }
        }

        // Perform search
        function performSearch(query) {
            debugLog('🔍 Exécution recherche pour:', query);
            const searchResults = document.getElementById('search-results');

            searchResults.innerHTML = '<div class="loading-text"><div class="loading-spinner"></div> Recherche...</div>';
            searchResults.style.display = 'block';
            searchResults.classList.add('show');

            fetch(`{{ route("search.global") }}?q=${encodeURIComponent(query)}`)
                .then(response => {
                    debugLog('🔍 Réponse recherche:', response.status);
                    return response.json();
                })
                .then(data => {
                    debugLog('🔍 Résultats recherche:', data);
                    displaySearchResults(data);
                })
                .catch(error => {
                    debugError('❌ Erreur recherche:', error);
                    searchResults.innerHTML = '<div class="search-no-results">Erreur de recherche</div>';
                });
        }

        // Display search results
        function displaySearchResults(data) {
            debugLog('🔍 Affichage résultats recherche:', data);
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
            debugLog('🔔 Marquage notification comme lue:', notificationId);
            fetch(`{{ url('/navbar/notifications') }}/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                debugLog('🔔 Réponse marquage notification:', response.status);
                return response.json();
            })
            .then(data => {
                debugLog('🔔 Notification marquée:', data);
                if (data.success) {
                    // Recharger les notifications
                    loadNavbarData();
                }
            })
            .catch(error => {
                debugError('❌ Erreur marquage notification:', error);
            });
        }

        // Mark all notifications as read
        document.addEventListener('DOMContentLoaded', function() {
            const markAllBtn = document.getElementById('mark-all-notifications-read');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('🔔 Marquage toutes notifications comme lues');

                    const markAllUrl = document.querySelector('meta[name="navbar-mark-all-read-url"]')?.content;
                    if (!markAllUrl) {
                        return;
                    }

                    fetch(markAllUrl, {
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
                        debugError('❌ Erreur marquage toutes notifications:', error);
                    });
                });
            }

            // Nouvelles fonctions pour notifications améliorées
            window.markNotificationAsRead = function(notificationId) {
                fetch(`{{ url('/navbar/notifications') }}/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour visuellement la notification
                        const notificationElement = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
                        if (notificationElement) {
                            notificationElement.classList.remove('unread');
                        }
                        
                        // Recalculer le badge
                        updateNotificationBadge();
                        
                        debugLog('✅ Notification marquée comme lue:', notificationId);
                    }
                })
                .catch(error => {
                    debugError('❌ Erreur marquage notification comme lue:', error);
                });
            };

            window.updateNotificationBadge = function() {
                // Compter les notifications non lues dans le DOM
                const unreadNotifications = document.querySelectorAll('.notification-item.unread');
                const count = unreadNotifications.length;
                const badge = document.getElementById('notifications-count');
                
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            };

            window.updateMessageBadge = function() {
                // Compter les messages non lus dans le DOM
                const unreadMessages = document.querySelectorAll('.message-item.unread');
                const count = unreadMessages.length;
                const badge = document.getElementById('messages-count');
                
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            };

            window.markMessageAsRead = function(messageId) {
                // Marquer visuellement le message comme lu
                const messageElement = document.querySelector(`.message-item[data-message-id="${messageId}"]`);
                if (messageElement) {
                    messageElement.classList.remove('unread');
                }
                
                // Recalculer le badge
                updateMessageBadge();
                
                debugLog('✅ Message marqué comme lu:', messageId);
            };

            window.openMessageLink = function(url, messageId) {
                if (url && url !== '#') {
                    // Marquer comme lu
                    markMessageAsRead(messageId);
                    // Ouvrir le lien
                    window.location.href = url;
                }
            };

            window.openNotificationLink = function(url, notificationId) {
                if (url && url !== '#') {
                    // Marquer comme lu
                    markNotificationAsRead(notificationId);
                    // Ouvrir le lien
                    window.location.href = url;
                }
            };

            window.deleteNotification = function(notificationId) {
                event.stopPropagation(); // Empêcher le clic sur le parent
                
                if (confirm('Supprimer cette notification ?')) {
                    fetch(`{{ url('/navbar/notifications') }}/${notificationId}/delete`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            debugLog('✅ Notification supprimée:', notificationId);
                            loadNavbarData(); // Recharger les données
                        } else {
                            alert('Erreur lors de la suppression');
                        }
                    })
                    .catch(error => {
                        debugError('❌ Erreur suppression notification:', error);
                        alert('Erreur lors de la suppression');
                    });
                }
            };

            window.deleteMessage = function(messageId) {
                event.stopPropagation(); // Empêcher le clic sur le parent
                
                if (confirm('Supprimer ce message ?')) {
                    fetch(`{{ url('/navbar/messages') }}/${messageId}/delete`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            debugLog('✅ Message supprimé:', messageId);
                            loadNavbarData(); // Recharger les données
                        } else {
                            alert('Erreur lors de la suppression');
                        }
                    })
                    .catch(error => {
                        debugError('❌ Erreur suppression message:', error);
                        alert('Erreur lors de la suppression');
                    });
                }
            };

            // Fonctions pour supprimer tout
            window.deleteAllNotifications = function() {
                if (confirm('Supprimer toutes les notifications ?')) {
                    fetch('{{ route("navbar.notifications.delete-all") }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            debugLog('✅ Toutes les notifications supprimées');
                            loadNavbarData(); // Recharger les données
                        } else {
                            alert('Erreur lors de la suppression');
                        }
                    })
                    .catch(error => {
                        debugError('❌ Erreur suppression toutes notifications:', error);
                        alert('Erreur lors de la suppression');
                    });
                }
            };

            window.deleteAllMessages = function() {
                if (confirm('Supprimer tous les messages ?')) {
                    fetch('{{ route("navbar.messages.delete-all") }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            debugLog('✅ Tous les messages supprimés');
                            loadNavbarData(); // Recharger les données
                        } else {
                            alert('Erreur lors de la suppression');
                        }
                    })
                    .catch(error => {
                        debugError('❌ Erreur suppression tous messages:', error);
                        alert('Erreur lors de la suppression');
                    });
                }
            };

            // Fonctions pour marquer tout comme lu
            window.markAllNotificationsAsRead = function() {
                fetch('{{ route("navbar.notifications.mark-all-read") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        debugLog('✅ Toutes les notifications marquées comme lues');
                        loadNavbarData(); // Recharger les données
                    } else {
                        alert('Erreur lors du marquage');
                    }
                })
                .catch(error => {
                    debugError('❌ Erreur marquage toutes notifications:', error);
                    alert('Erreur lors du marquage');
                });
            };

            window.markAllMessagesAsRead = function() {
                fetch('{{ route("navbar.messages.mark-all-read") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        debugLog('✅ Tous les messages marqués comme lus');
                        loadNavbarData(); // Recharger les données
                    } else {
                        alert('Erreur lors du marquage');
                    }
                })
                .catch(error => {
                    debugError('❌ Erreur marquage tous messages:', error);
                    alert('Erreur lors du marquage');
                });
            };

            // Marquer les notifications comme vues quand on ouvre le dropdown
            document.getElementById('notificationsDropdown').addEventListener('shown.bs.dropdown', function () {
                debugLog('🔔 Dropdown notifications ouvert - marquage comme vu');

                const unreadNotifications = document.querySelectorAll('.notification-item.unread');
                const badge = document.getElementById('notifications-count');
                const badgeCount = badge && badge.textContent ? parseInt(badge.textContent, 10) : 0;

                // Marquer toutes les notifications comme vues (pas supprimées, juste vues)
                if (unreadNotifications.length > 0 || badgeCount > 0) {
                    fetch('{{ route("navbar.notifications.mark-all-read") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mettre à jour visuellement les notifications dans le dropdown
                            unreadNotifications.forEach(item => {
                                item.classList.remove('unread');
                            });

                            // Mettre à jour le badge en utilisant la nouvelle fonction
                            updateNotificationBadge();
                            loadNavbarData();

                            debugLog('✅ Notifications marquées comme vues');
                        }
                    })
                    .catch(error => {
                        debugError('❌ Erreur marquage notifications vues:', error);
                    });
                }
            });

            // Marquer les messages comme vus quand on ouvre le dropdown
            document.getElementById('messagesDropdown').addEventListener('shown.bs.dropdown', function () {
                debugLog('💬 Dropdown messages ouvert - marquage comme vu');
                
                // Pour l'instant, marquer visuellement les messages comme lus
                const messageItems = document.querySelectorAll('.message-item.unread');
                messageItems.forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Mettre à jour le badge
                updateMessageBadge();
                
                debugLog('✅ Messages marqués comme vus');
            });

            // Gérer le bouton "Tout marquer comme lu" pour les messages
            const markAllMessagesBtn = document.getElementById('mark-all-messages-read');
            if (markAllMessagesBtn) {
                markAllMessagesBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    debugLog('📨 Marquage tous messages comme lus...');
                    
                    // Marquer visuellement les messages comme lus
                    const messageItems = document.querySelectorAll('.message-item.unread');
                    messageItems.forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Mettre à jour le badge
                    updateMessageBadge();
                    
                    debugLog('✅ Messages marqués comme lus');
                });
            }
    });
    </script>

    <!-- Scripts additionnels -->
    @stack('scripts')
    @stack('modals')

    {{-- Compte à rebours expiration contrat (affiché max 1x/12h si ≤ 30 jours) --}}
    @if(auth()->check())
        @include('components.contract-expiry-modal')
    @endif
</body>
</html>
