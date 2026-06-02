@extends('layouts.app')

@section('title', 'Gestion des étudiants - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Responsive fixes - Éviter débordement */
    * {
        box-sizing: border-box;
    }

    /* Empêcher TOUT scroll horizontal sur mobile */
    html {
        overflow-x: hidden !important;
        width: 100%;
        max-width: 100vw;
        position: relative;
    }

    body {
        overflow-x: hidden !important;
        width: 100%;
        max-width: 100vw;
        position: relative;
        margin: 0;
        padding: 0;
    }

    .dashboard-acasi {
        max-width: 100%;
        overflow-x: hidden !important;
        width: 100%;
        margin: 0 auto;
        padding: 0;
        box-sizing: border-box;
    }

    /* Forcer tout wrapper/container à être centré sur mobile */
    @media (max-width: 992px) {
        .dashboard-acasi,
        .dashboard-acasi > *,
        .main-content > * {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }

    /* ========================================
       SYSTÈME 8PX GRID - SPACING STANDARD
       8, 16, 24, 32, 40, 48, 56, 64px
       ======================================== */

    .main-content {
        max-width: 100%;
        overflow-x: hidden;
        width: 100%;
        margin: 0 auto;
        padding-left: 16px;  /* 8px grid */
        padding-right: 16px;
        box-sizing: border-box;
    }

    /* Border-radius sur main-content quand cards visibles (mobile) */
    @media (max-width: 992px) {
        .main-content {
            border-radius: 16px;  /* 8px grid × 2 */
            background: transparent;
        }
    }

    .card-moderne {
        max-width: 100%;
        overflow-x: hidden;
        word-wrap: break-word;
        margin-bottom: 32px;  /* 8px grid × 4 - Séparation claire entre sections */
        margin-left: 0;
        margin-right: 0;
        width: 100%;
        box-sizing: border-box;
    }

    .card-moderne .p-lg {
        overflow-x: hidden;
        padding: 24px;  /* 8px grid × 3 - Internal padding cohérent */
        box-sizing: border-box;
    }

    /* Permettre au searchable-select dropdown de dépasser la card */
    .card-moderne:has(.searchable-select.active) {
        overflow: visible;
    }

    .card-moderne:has(.searchable-select.active) .p-lg {
        overflow: visible;
    }

    @media (max-width: 992px) {
        .card-moderne {
            margin-bottom: 24px;  /* 8px grid × 3 */
        }

        .card-moderne .p-lg {
            padding: 16px;  /* 8px grid × 2 */
        }

        .main-content {
            padding-left: 16px;  /* 8px grid × 2 */
            padding-right: 16px;
        }
    }

    @media (max-width: 576px) {
        .card-moderne {
            margin-bottom: 16px;  /* 8px grid × 2 */
        }

        .card-moderne .p-lg {
            padding: 12px;  /* Un peu moins que 16px pour très petits écrans */
        }

        .main-content {
            padding-left: 8px;  /* 8px grid × 1 */
            padding-right: 8px;
        }
    }

    .dashboard-header {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;  /* 8px grid × 2 */
        align-items: center;
        justify-content: space-between;
        /* padding: var(--space-lg) hérité de dashboard-moderne.css */
    }

    @media (max-width: 576px) {
        .dashboard-header {
            gap: 8px;  /* 8px grid × 1 - Plus compact sur mobile */
        }
    }

    /* ========================================
       SÉPARATION ICÔNE/TITRE - Visual Hierarchy
       Principe: Icône doit avoir un espace clair du texte
       ======================================== */
    .section-title {
        display: flex;
        align-items: center;
        gap: 12px;  /* 8px + 4px pour équilibre visuel */
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 24px;  /* 8px grid × 3 */
    }

    .section-title i {
        flex-shrink: 0;  /* Empêcher l'icône de se compresser */
        font-size: 1.1em;  /* Légèrement plus petite que le texte */
        color: var(--primary, #0453cb);
    }

    /* ========================================
       INDICATEUR FILTRES ACTIFS
       Affiche les filtres actuellement appliqués avec option de suppression
       ======================================== */
    .active-filters-container {
        margin-bottom: 24px;  /* 8px grid × 3 */
        padding: 16px;  /* 8px grid × 2 */
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
        border-radius: 12px;  /* 8px + 4px */
        display: flex;
        flex-wrap: wrap;
        gap: 8px;  /* 8px grid × 1 */
        align-items: center;
    }

    .active-filters-label {
        font-size: 14px;
        font-weight: 600;
        color: #075985;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .active-filters-label i {
        font-size: 16px;
    }

    .filter-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;  /* 8px grid × 1 */
        padding: 8px 12px;  /* 8px grid × 1, 8px + 4px */
        background: #ffffff;
        border: 1px solid #0ea5e9;
        border-radius: 8px;  /* 8px grid × 1 */
        font-size: 14px;
        color: #0c4a6e;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .filter-tag:hover {
        background: #f0f9ff;
        border-color: #0284c7;
        transform: translateY(-1px);
    }

    .filter-tag-label {
        font-weight: 700;
        color: #075985;
    }

    .filter-tag-value {
        color: #0c4a6e;
    }

    .filter-tag-remove {
        margin-left: 4px;
        padding: 2px 6px;
        background: #fee2e2;
        border: none;
        border-radius: 4px;
        color: #dc2626;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .filter-tag-remove:hover {
        background: #fecaca;
        color: #b91c1c;
    }

    .clear-all-filters {
        padding: 8px 16px;  /* 8px grid × 1, 8px grid × 2 */
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        border: none;
        border-radius: 8px;  /* 8px grid × 1 */
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .clear-all-filters:hover {
        background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
        transform: translateY(-1px);
    }

    /* Responsive */
    @media (max-width: 576px) {
        .active-filters-container {
            padding: 12px;  /* 8px + 4px */
        }

        .filter-tag {
            font-size: 13px;
            padding: 6px 10px;
        }

        .clear-all-filters {
            font-size: 13px;
            padding: 6px 12px;
        }
    }

    .header-left {
        flex: 1 1 auto;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .header-left h1 {
        word-wrap: break-word;
        overflow-wrap: break-word;
        font-size: clamp(1.5rem, 5vw, 2rem);
        margin: 0;
    }

    .header-left .header-subtitle {
        word-wrap: break-word;
        overflow-wrap: break-word;
        font-size: clamp(0.875rem, 3vw, 1rem);
    }

    .header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        flex-shrink: 0;
        max-width: 100%;
    }

    .header-actions .btn-acasi {
        font-size: clamp(0.875rem, 2.5vw, 1rem);
        padding: 0.5rem 1rem;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .header-actions {
            width: 100%;
            justify-content: stretch;
        }

        .header-actions .btn-acasi {
            flex: 1;
            min-width: 0;
            white-space: normal;
            text-align: center;
        }
    }

    .table-responsive {
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #search-form {
        max-width: 100%;
        overflow-x: hidden;
        width: 100%;
    }

    /* Permettre au dropdown de dépasser le formulaire aussi */
    #search-form:has(.searchable-select.active) {
        overflow: visible;
    }

    #search-form .row {
        margin-left: 0;
        margin-right: 0;
        width: 100%;
        max-width: 100%;
    }

    #search-form .row > [class*='col-'] {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* Sur mobile, pas de marges négatives */
    @media (max-width: 992px) {
        #search-form .row,
        .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }

    /* Permettre aux colonnes de dépasser aussi */
    #search-form .row:has(.searchable-select.active) {
        overflow: visible;
    }

    #search-form .row > [class*='col-']:has(.searchable-select.active) {
        overflow: visible;
    }

    /* S'assurer que tous les containers respectent la largeur */
    .container-fluid,
    .container {
        max-width: 100%;
        overflow-x: hidden;
    }

    .form-control,
    .form-select,
    .searchable-select-trigger {
        max-width: 100%;
    }

    .btn-acasi {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ========================================
       RESPONSIVE DESIGN 2025 - BEST PRACTICES
       ======================================== */

    /* Vue Desktop/Mobile Toggle */
    .desktop-view {
        display: block;
    }

    .mobile-view {
        display: none;
    }

    @media (max-width: 992px) {
        .desktop-view {
            display: none;
        }

        .mobile-view {
            display: block;
        }
    }

    /* ========================================
       MOBILE FILTER DRAWER (Standards 2025)
       ======================================== */

    /* Bouton FAB flottant Filtres - Positionné à GAUCHE pour ne pas chevaucher chatbot */
    .mobile-filter-fab {
        position: fixed;
        bottom: 24px;
        left: 24px;  /* Gauche au lieu de droite */
        z-index: 1000;
        display: none;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        color: white;
        border: none;
        box-shadow: 0 8px 24px rgba(4, 83, 203, 0.4);
        font-size: 24px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mobile-filter-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 12px 32px rgba(4, 83, 203, 0.5);
    }

    .mobile-filter-fab:active {
        transform: scale(0.95);
    }

    @media (max-width: 992px) {
        .mobile-filter-fab {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    /* Drawer overlay */
    .filter-drawer-overlay {
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

    .filter-drawer-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Drawer panel */
    .filter-drawer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100vw;
        max-width: 100vw;
        max-height: 90vh;
        background: #ffffff;
        border-radius: 24px 24px 0 0;
        box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.2);
        z-index: 1050;
        transform: translateY(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
    }

    .filter-drawer.active {
        transform: translateY(0);
    }

    /* Drawer header */
    .filter-drawer-header {
        padding: 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .filter-drawer-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
    }

    .filter-drawer-close {
        background: #f1f5f9;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #64748b;
        font-size: 20px;
        transition: all 0.2s ease;
    }

    .filter-drawer-close:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* Drawer body (scrollable) */
    .filter-drawer-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    .filter-drawer-body .form-group {
        margin-bottom: 24px;
    }

    .filter-drawer-body .form-label {
        display: block;
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .filter-drawer-body .form-control,
    .filter-drawer-body .form-select,
    .filter-drawer-body .searchable-select-trigger {
        width: 100%;
        padding: 16px;
        font-size: 18px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        min-height: 56px;
        transition: all 0.2s ease;
    }

    .filter-drawer-body .form-control:focus,
    .filter-drawer-body .form-select:focus,
    .filter-drawer-body .searchable-select-trigger:focus {
        border-color: #0453cb;
        outline: none;
        box-shadow: 0 0 0 4px rgba(4, 83, 203, 0.1);
    }

    /* Drawer footer (sticky) */
    .filter-drawer-footer {
        padding: 24px;
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
        flex-shrink: 0;
        display: flex;
        gap: 12px;
    }

    .filter-drawer-footer .btn {
        flex: 1;
        min-height: 56px;
        font-size: 18px;
        font-weight: 600;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .filter-drawer-footer .btn-primary {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        color: white;
    }

    .filter-drawer-footer .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(4, 83, 203, 0.3);
    }

    .filter-drawer-footer .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
    }

    .filter-drawer-footer .btn-secondary:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* Cache les filtres desktop sur mobile */
    @media (max-width: 992px) {
        .desktop-filters {
            display: none;
        }
    }

    /* ========================================
       STUDENT CARDS - DESIGN MODERNE 2025
       ======================================== */

    /* Grid responsive : 1 col mobile, 2 cols desktop, 3 cols large */
    .students-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        padding: 0;
        margin-top: 24px;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    /* 2 colonnes à partir de 1200px */
    @media (min-width: 1200px) {
        .students-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
        }
    }

    /* 3 colonnes à partir de 1400px */
    @media (min-width: 1400px) {
        .students-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }
    }

    /* S'assurer que le contenu résultats ne déborde pas */
    #etudiants-results {
        max-width: 100%;
        overflow-x: hidden;
        width: 100%;
    }

    /* ========================================
       STUDENT CARD - DESIGN MODERNE 2025
       Typography: 18-24px | Padding: 24-32px | Touch targets: 56px
       ======================================== */

    .student-card {
        background: #ffffff;
        border-radius: 16px;  /* 8px grid × 2 */
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);  /* 16px = 8px grid × 2 */
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        overflow: hidden;
        border: 1px solid #e5e7eb;
        max-width: 100%;
        width: 100%;
    }

    .student-card:hover {
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);  /* 32px = 8px grid × 4 */
        transform: translateY(-4px);
    }

    .student-card.pending-inscription {
        border-left: 5px solid #f59e0b;
    }

    /* Card Header */
    .student-card-header {
        display: flex;
        align-items: center;
        gap: 16px;  /* 8px grid × 2 */
        padding: 24px;  /* 8px grid × 3 */
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e5e7eb;
    }

    .student-photo img,
    .photo-placeholder {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .photo-placeholder {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6366f1;
        font-size: 32px;
    }

    .student-info-header {
        flex: 1;
        min-width: 0;
    }

    .student-name {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 8px 0;
        color: #1e293b;
        line-height: 1.3;
        letter-spacing: -0.02em;
    }

    .student-matricule {
        font-size: 16px;
        color: #64748b;
        margin: 0 0 8px 0;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .student-status {
        flex-shrink: 0;
    }

    .student-status .badge {
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    /* Card Body */
    .student-card-body {
        padding: 24px;  /* 8px grid × 3 */
        display: flex;
        flex-direction: column;
        gap: 16px;  /* 8px grid × 2 - Séparation entre info rows */
    }

    .info-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;  /* 8px + 4px pour équilibre visuel */
    }

    .info-row > i {
        font-size: 22px;
        margin-top: 2px;
        flex-shrink: 0;
        width: 28px;
        text-align: center;
    }

    .info-content {
        flex: 1;
        min-width: 0;
    }

    .info-label {
        display: block;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .info-value {
        display: block;
        font-size: 18px;
        color: #1e293b;
        font-weight: 500;
        line-height: 1.5;
        word-wrap: break-word;
    }

    .info-value small {
        font-size: 15px;
        color: #64748b;
    }

    /* Card Footer */
    .student-card-footer {
        padding: 24px;  /* 8px grid × 3 */
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 12px;  /* 8px + 4px pour équilibre visuel */
        flex-wrap: wrap;
    }

    .student-card-footer .btn {
        flex: 1;
        min-width: fit-content;
        min-height: 56px;  /* 8px grid × 7 - Touch target optimal */
        font-size: 16px;  /* 8px grid × 2 */
        font-weight: 600;
        padding: 0 24px;  /* 8px grid × 3 */
        border-radius: 12px;  /* 8px + 4px */
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;  /* 8px grid × 1 */
    }

    .student-card-footer .btn:hover {
        transform: translateY(-2px);
    }

    .student-card-footer .btn i {
        font-size: 16px;
    }

    /* Badges dans cards */
    .student-card .badge {
        font-size: 14px;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 8px;
    }

    /* Textes muted plus gros */
    .student-card small.text-muted {
        font-size: 15px;
        color: #64748b;
    }

    /* Les cards sont déjà optimisées mobile-first (voir grid breakpoints ci-dessus) */

    /* Règles mobile strictes pour éviter tout débordement */
    @media (max-width: 992px) {
        /* Réduire gap de la grille sur mobile */
        .students-grid {
            gap: 16px;
            padding: 0;
            margin-top: 16px;
        }

        .student-card,
        .student-card-header,
        .student-card-body,
        .student-card-footer {
            max-width: 100%;
            width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        .student-name,
        .student-matricule,
        .info-value,
        .info-label {
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            max-width: 100%;
        }

        /* Réduire padding des cards sur mobile (8px grid) */
        .student-card-header {
            padding: 16px;  /* 8px grid × 2 */
            gap: 12px;  /* 8px + 4px */
        }

        .student-card-body {
            padding: 16px;  /* 8px grid × 2 */
            gap: 12px;  /* 8px + 4px */
        }

        .student-card-footer {
            padding: 16px;  /* 8px grid × 2 */
            gap: 8px;  /* 8px grid × 1 */
        }

        /* Boutons footer plus compacts */
        .student-card-footer .btn {
            font-size: 14px;
            min-height: 48px;  /* 8px grid × 6 */
            padding: 0 16px;  /* 8px grid × 2 */
        }

        /* Photos */
        .student-photo img,
        .photo-placeholder {
            width: 64px;
            height: 64px;
        }

        /* Textes plus petits mais lisibles */
        .student-name {
            font-size: 18px;
        }

        .student-matricule {
            font-size: 14px;
        }

        .info-value {
            font-size: 16px;
        }

        .info-row > i {
            font-size: 18px;
        }
    }

    @media (max-width: 576px) {
        /* Gap encore plus réduit sur très petit écran (8px grid) */
        .students-grid {
            gap: 12px;  /* 8px + 4px */
            margin-top: 12px;
        }

        .student-card-header {
            padding: 12px;  /* 8px + 4px - Compact mais respirable */
        }

        .student-card-body {
            padding: 12px;  /* 8px + 4px */
            gap: 12px;
        }

        .student-card-footer {
            padding: 12px;  /* 8px + 4px */
        }

        .student-card-footer .btn {
            font-size: 13px;
            min-height: 44px;  /* Minimum touch target acceptable */
            padding: 0 12px;  /* 8px + 4px */
        }

        /* Photos légèrement plus petites sur très petit écran */
        .student-photo img,
        .photo-placeholder {
            width: 56px;
            height: 56px;
        }
    }

    /* Règles ULTRA strictes pour petits écrans iPhone (390px) */
    @media (max-width: 400px) {
        /* Forcer tout le contenu à rester dans la largeur */
        * {
            max-width: 100vw !important;
        }

        /* Paddings ÉGAUX des deux côtés pour centrage parfait */
        .main-content {
            padding-left: 6px !important;
            padding-right: 6px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .card-moderne {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .card-moderne .p-lg {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .students-grid {
            gap: 8px !important;
            padding: 0 !important;
        }

        .student-card {
            border-radius: 12px !important;
        }

        .student-card-header,
        .student-card-body,
        .student-card-footer {
            padding: 8px !important;
        }

        .student-card-header {
            gap: 8px !important;
        }

        .student-card-body {
            gap: 10px !important;
        }

        .student-photo img,
        .photo-placeholder {
            width: 48px !important;
            height: 48px !important;
        }

        .student-name {
            font-size: 16px !important;
        }

        .student-matricule {
            font-size: 13px !important;
        }

        .info-label {
            font-size: 11px !important;
        }

        .info-value {
            font-size: 14px !important;
        }

        .info-row > i {
            font-size: 16px !important;
        }

        .student-card-footer .btn {
            font-size: 12px !important;
            min-height: 40px !important;
            padding: 0 8px !important;
        }

        .dashboard-header {
            padding: 0 !important;
        }

        .header-left h1 {
            font-size: 1.25rem !important;
        }

        .header-subtitle {
            font-size: 0.75rem !important;
        }
    }


    .modal-modern .modal-dialog {
        width: 98vw !important;
        max-width: 98vw !important;
        min-width: auto;
        height: 95vh !important;
        max-height: 95vh !important;
        position: relative;
        margin: 2.5vh auto !important;
    }

    .modal-modern .modal-content {
        border-radius: 24px;
        border: none;
        box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        will-change: transform;
    }

    .modal-modern .modal-header {
        border-bottom: none;
        padding: 20px 28px 12px 28px;
        background: transparent;
        flex-shrink: 0;
    }

    .modal-modern .modal-body {
        padding: 8px 28px 24px 28px;
        overflow: hidden;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .student-tabs-container {
        position: relative;
        margin-bottom: 0;
        flex-shrink: 0;
    }

    .student-tabs-container .nav-tabs {
        border: none;
        margin-bottom: 0;
        position: relative;
        z-index: 10;
        display: flex;
        gap: 8px;
        padding-left: 0;
    }

    .student-tabs-container .nav-link {
        border: none !important;
        border-radius: 16px 16px 0 0 !important;
        padding: 14px 24px !important;
        color: #6b7280 !important;
        background: #f8fafc !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12) !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    .student-tabs-container .nav-link:hover {
        background: #eef2ff !important;
        color: #1f2937 !important;
        transform: translateY(-2px) !important;
    }

    .student-tabs-container .nav-link.active {
        background: #ffffff !important;
        color: #111827 !important;
        font-weight: 700 !important;
        box-shadow: 0 -2px 20px rgba(15, 23, 42, 0.12) !important;
        border-bottom: none !important;
    }

    .student-tabs-container .nav-link .tab-label {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .student-tabs-container .nav-link .tab-label i {
        font-size: 14px;
    }

    .modern-tab-content {
        position: relative;
        z-index: 5;
        background: #ffffff;
        border-radius: 0 16px 16px 16px;
        margin-top: -1px;
        box-shadow: inset 0 1px 0 rgba(229, 231, 235, 0.8), 0 20px 35px rgba(15, 23, 42, 0.15);
        padding: 0;
        border: 1px solid #e5e7eb;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
    }

    .modern-tab-content .tab-pane {
        padding: 0;
        border: none;
        background: transparent;
        display: none;
        overflow: hidden;
        min-height: 0;
    }

    .modern-tab-content .tab-pane.show.active {
        display: flex;
        flex: 1;
        flex-direction: column;
    }

    .category-card {
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,1) 100%);
        border-radius: 20px;
        border: 1px solid rgba(15, 23, 42, 0.06);
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
        padding: 24px;
    }

    .category-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 25px 55px rgba(15, 23, 42, 0.18);
    }

    .category-card .modal-card-body {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .modal-iframe-wrapper {
        border-radius: 0;
        overflow: hidden;
        border: none;
        background: #ffffff;
        width: 100%;
        height: 100%;
        flex: 1;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .iframe-loader {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #fff;
        z-index: 5;
        transition: opacity 0.3s;
    }

    .iframe-loader.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .modal-iframe-wrapper iframe {
        width: 100%;
        height: 100%;
        flex: 1;
        border: none;
    }

    #inscriptions-accordion-container {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        min-height: 0;
    }

    #etudiants-table th button.table-sort {
        color: inherit;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    #etudiants-table th button.table-sort:hover {
        opacity: 0.85;
    }

    /* Indicateurs de tri actif avec flèches */
    #etudiants-table th button.table-sort.sorted-asc::after {
        content: ' ▲';
        font-size: 10px;
        color: var(--primary, #0453cb);
    }

    #etudiants-table th button.table-sort.sorted-desc::after {
        content: ' ▼';
        font-size: 10px;
        color: var(--primary, #0453cb);
    }

    .accordion-modern .accordion-item {
        border: none;
        border-radius: 16px;
        margin-bottom: 12px;
        overflow: hidden;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
    }

    .accordion-modern .accordion-button {
        background: #f8fafc;
        border: none;
        font-weight: 600;
        color: #0f172a;
        padding: 16px 20px;
    }

    .accordion-modern .accordion-body {
        background: #ffffff;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .accordion-modern .accordion-body .modal-iframe-wrapper {
        min-height: 500px;
        height: 60vh;
    }

    .accordion-modern .accordion-body .modal-iframe-wrapper iframe {
        width: 100%;
        height: 100%;
    }

    #editStudentTabContent {
        /* No transition — prevents flash when iframe content resizes */
    }

    /* Removed: modal-dialog::after with filter:blur(20px) — caused iframe repaint flash */

    @media (max-width: 1400px) {
        .modal-modern .modal-dialog {
            width: 94vw;
            max-width: 94vw;
        }
    }

    @media (max-width: 1200px) {
        .modal-modern .modal-dialog {
            width: 96vw;
            max-width: 96vw;
            height: 90vh;
            max-height: 90vh;
            margin: 5vh auto;
        }
    }

    @media (max-width: 992px) {
        .modal-modern .modal-dialog {
            width: 98vw;
            max-width: 98vw;
            height: 92vh;
            max-height: 92vh;
            margin: 4vh auto;
        }

        .accordion-modern .accordion-body .modal-iframe-wrapper {
            min-height: 400px;
            height: 50vh;
        }

        .category-card {
            padding: 16px;
        }

        /* Main content padding réduit */
        .main-content {
            padding-left: 10px;
            padding-right: 10px;
        }

        /* Filtres en colonne complète sur tablette */
        #search-form .row > [class*='col-'] {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        #search-form .row {
            row-gap: 1rem;
            margin: 0;
        }

        /* Header responsive */
        .dashboard-header {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
            width: 100%;
        }

        .dashboard-header .header-left {
            width: 100%;
        }

        .dashboard-header .header-left h1 {
            font-size: 1.75rem;
        }

        .dashboard-header .header-subtitle {
            font-size: 0.9rem;
        }

        /* Boutons header en colonne */
        .header-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .header-actions .btn-acasi {
            width: 100%;
            justify-content: center;
            max-width: 100%;
        }

        /* Boutons filtres responsive */
        .col-md-4.d-flex.align-items-end {
            flex-direction: column !important;
            align-items: stretch !important;
            width: 100% !important;
        }

        .col-md-4.d-flex.align-items-end .btn-acasi {
            width: 100%;
            max-width: 100%;
            margin-bottom: 0.5rem;
        }

        .col-md-4.d-flex.align-items-end .btn-acasi.me-2 {
            margin-right: 0 !important;
        }

        /* Modal header padding réduit */
        .modal-modern .modal-header {
            padding: 16px 20px 10px 20px;
        }

        .modal-modern .modal-body {
            padding: 6px 20px 20px 20px;
        }

        /* Card padding réduit */
        .card-moderne .p-lg {
            padding: 1.5rem 1rem !important;
        }

        /* Tabs padding */
        .student-tabs-container .nav-link {
            padding: 12px 16px !important;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 768px) {
        /* Main content padding minimal */
        .main-content {
            padding-left: 8px;
            padding-right: 8px;
        }

        /* Header encore plus compact */
        .dashboard-header {
            width: 100%;
        }

        .dashboard-header .header-left h1 {
            font-size: 1.5rem;
        }

        /* Section titles plus petits */
        .section-title {
            font-size: 1rem;
        }

        /* Form labels - GARDER TAILLE LISIBLE */
        #search-form label.form-label {
            font-size: 0.9375rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        /* Inputs et selects - GARDER TAILLE NORMALE LISIBLE */
        #search-form .form-control,
        #search-form .form-select,
        #search-form .searchable-select-trigger {
            font-size: 1rem;
            padding: 12px 16px;
            min-height: 48px;
            width: 100%;
        }

        /* Boutons filtres - GARDER TAILLE NORMALE */
        #search-form .btn-acasi {
            font-size: 1rem;
            padding: 12px 20px;
            width: 100%;
            max-width: 100%;
            min-height: 48px;
        }

        /* Card padding réduit */
        .card-moderne .p-lg {
            padding: 1rem 0.75rem !important;
        }

        /* Modal tabs en colonne sur petit écran */
        .student-tabs-container .nav-tabs {
            flex-direction: row;
            flex-wrap: wrap;
        }

        .student-tabs-container .nav-item {
            flex: 1 1 50%;
        }

        .student-tabs-container .nav-link {
            border-radius: 12px 12px 0 0 !important;
            font-size: 0.85rem;
            padding: 10px 12px !important;
        }

        .student-tabs-container .nav-link .tab-label {
            gap: 6px;
        }

        .student-tabs-container .nav-link .tab-label i {
            font-size: 12px;
        }
    }

    @media (max-width: 576px) {
        /* Main content minimal padding */
        .main-content {
            padding-left: 5px;
            padding-right: 5px;
        }

        /* Header très compact */
        .dashboard-header {
            width: 100%;
            margin-bottom: 1rem;
        }

        .dashboard-header .header-left {
            width: 100%;
        }

        .dashboard-header .header-left h1 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .dashboard-header .header-subtitle {
            font-size: 0.8rem;
        }

        /* Boutons header pleine largeur */
        .header-actions {
            width: 100%;
        }

        .header-actions .btn-acasi {
            width: 100%;
            max-width: 100%;
            justify-content: center;
        }

        /* Card padding minimal */
        .card-moderne {
            width: 100%;
        }

        .card-moderne .p-lg {
            padding: 0.75rem 0.5rem !important;
        }

        /* Modal fullscreen sur mobile */
        .modal-modern .modal-dialog {
            width: 100vw;
            max-width: 100vw;
            height: 100vh;
            max-height: 100vh;
            margin: 0;
            border-radius: 0;
        }

        .modal-modern .modal-content {
            border-radius: 0;
            height: 100%;
        }

        .modal-modern .modal-header {
            padding: 12px 16px 8px 16px;
        }

        .modal-modern .modal-body {
            padding: 4px 16px 16px 16px;
        }

        /* Modal title plus petit */
        .modal-modern .modal-title {
            font-size: 1.1rem;
        }

        /* Tabs en pile verticale sur très petit écran */
        .student-tabs-container .nav-tabs {
            flex-direction: column;
        }

        .student-tabs-container .nav-item {
            flex: 1 1 100%;
        }

        .student-tabs-container .nav-link {
            border-radius: 12px !important;
            margin-bottom: 4px;
        }

        /* Accordion plus compact */
        .accordion-modern .accordion-button {
            padding: 12px;
            font-size: 0.85rem;
        }

        .accordion-modern .accordion-body {
            padding: 12px;
        }

        .accordion-modern .accordion-body .modal-iframe-wrapper {
            min-height: 300px;
            height: 40vh;
        }

        /* Section title */
        .section-title {
            font-size: 1.125rem;
            margin-bottom: 1rem !important;
        }

        /* Labels - GARDER TAILLE LISIBLE */
        #search-form label.form-label {
            font-size: 0.9375rem;
            font-weight: 600;
        }

        /* Inputs - GARDER TAILLE NORMALE */
        #search-form .form-control,
        #search-form .form-select,
        #search-form .searchable-select-trigger {
            font-size: 1rem;
            padding: 12px 16px;
            min-height: 48px;
        }

        /* Searchable select icon */
        .searchable-select-icon {
            font-size: 1rem;
            right: 16px;
        }

        /* Dropdown searchable select */
        .searchable-select-dropdown {
            max-height: 60vh;
        }

        .searchable-select-search input {
            font-size: 1rem;
            padding: 12px 16px;
        }

        .searchable-select-option {
            padding: 12px 16px;
            font-size: 1rem;
        }

        /* Boutons filtres - GARDER TAILLE NORMALE */
        #search-form .btn-acasi {
            font-size: 1rem;
            padding: 12px 20px;
            min-height: 48px;
        }

        #search-form .btn-acasi i {
            font-size: 1rem;
        }
    }

    /* Très petits écrans (moins de 400px) */
    @media (max-width: 400px) {
        /* Main content ultra minimal */
        .main-content {
            padding-left: 3px;
            padding-right: 3px;
        }

        /* Header ultra compact */
        .dashboard-header {
            width: 100%;
        }

        .dashboard-header .header-left {
            width: 100%;
        }

        .dashboard-header .header-left h1 {
            font-size: 1.1rem;
        }

        /* Card ultra compact */
        .card-moderne {
            width: 100%;
        }

        .card-moderne .p-lg {
            padding: 0.5rem 0.25rem !important;
        }

        .section-title {
            font-size: 1.125rem;
        }

        /* Form - GARDER TAILLE LISIBLE même sur petit écran */
        #search-form .row {
            margin: 0;
        }

        #search-form .row > [class*='col-'] {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        #search-form .form-control,
        #search-form .form-select,
        #search-form .searchable-select-trigger {
            font-size: 1rem;
            padding: 12px 16px;
            min-height: 48px;
            width: 100%;
        }

        #search-form .btn-acasi {
            font-size: 1rem;
            padding: 12px 20px;
            width: 100%;
            max-width: 100%;
            min-height: 48px;
        }

        .modal-modern .modal-title {
            font-size: 1rem;
        }

        /* Header actions ultra compact */
        .header-actions .btn-acasi {
            width: 100%;
            max-width: 100%;
        }
    }

    /* Responsive Table Styles */
    @media (max-width: 992px) {
        /* Table avec scroll horizontal sur tablette */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Réduire padding colonnes table */
        #etudiants-table th,
        #etudiants-table td {
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        /* Boutons d'action plus compacts */
        #etudiants-table .btn-sm {
            padding: 0.25rem 0.4rem;
            font-size: 0.75rem;
        }

        #etudiants-table .btn-sm i {
            font-size: 0.75rem;
        }

        /* Badges plus compacts */
        #etudiants-table .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        /* Photo plus petite */
        #etudiants-table img,
        #etudiants-table .rounded-circle {
            width: 40px !important;
            height: 40px !important;
        }
    }

    @media (max-width: 768px) {
        /* Table très compact sur mobile */
        #etudiants-table th,
        #etudiants-table td {
            padding: 0.4rem;
            font-size: 0.8rem;
        }

        /* Headers table avec moins de padding */
        #etudiants-table th .btn-link {
            font-size: 0.75rem;
        }

        #etudiants-table th .fas.fa-sort {
            font-size: 0.65rem;
        }

        /* Actions en colonne */
        #etudiants-table .d-flex.flex-wrap {
            flex-direction: column !important;
            gap: 0.25rem !important;
        }

        #etudiants-table .d-flex.flex-wrap .btn {
            width: 100%;
        }

        /* Photo encore plus petite */
        #etudiants-table img,
        #etudiants-table .rounded-circle {
            width: 35px !important;
            height: 35px !important;
        }

        /* Badges très compacts */
        #etudiants-table .badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
    }

    @media (max-width: 576px) {
        /* Table ultra compact */
        #etudiants-table th,
        #etudiants-table td {
            padding: 0.3rem 0.2rem;
            font-size: 0.75rem;
            white-space: nowrap;
        }

        /* Cacher certaines colonnes moins importantes sur mobile */
        #etudiants-table th:nth-child(2), /* Photo */
        #etudiants-table td:nth-child(2),
        #etudiants-table th:nth-child(4), /* Genre */
        #etudiants-table td:nth-child(4),
        #etudiants-table th:nth-child(6), /* Résidence */
        #etudiants-table td:nth-child(6),
        #etudiants-table th:nth-child(8), /* Date inscription */
        #etudiants-table td:nth-child(8) {
            display: none;
        }

        /* Pagination compact */
        .pagination {
            font-size: 0.75rem;
        }

        .pagination .page-link {
            padding: 0.25rem 0.5rem;
        }
    }

    @media (max-width: 400px) {
        /* Cacher encore plus de colonnes sur très petit écran */
        #etudiants-table th:nth-child(9), /* Statut affectation */
        #etudiants-table td:nth-child(9) {
            display: none;
        }

        /* Table ultra minimal */
        #etudiants-table th,
        #etudiants-table td {
            padding: 0.25rem 0.15rem;
            font-size: 0.7rem;
        }
    }

    /* Modern Searchable Select Component */
    .searchable-select {
        position: relative;
        width: 100%;
    }

    .searchable-select-trigger {
        width: 100%;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 40px 10px 14px;
        font-size: 14px;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 42px;
    }

    .searchable-select-trigger:hover {
        border-color: #cbd5e1;
    }

    .searchable-select-trigger:focus,
    .searchable-select.active .searchable-select-trigger {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    .searchable-select-trigger-text {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .searchable-select-trigger-text.placeholder {
        color: #94a3b8;
    }

    .searchable-select-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        transition: transform 0.2s;
        color: #64748b;
        pointer-events: none;
    }

    .searchable-select.active .searchable-select-icon {
        transform: translateY(-50%) rotate(180deg);
    }

    .searchable-select-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12), 0 4px 10px rgba(15, 23, 42, 0.08);
        z-index: 9999;
        max-height: 320px;
        display: flex;
        flex-direction: column;
        animation: slideDown 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        isolation: isolate;
    }

    .searchable-select-search {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .searchable-select-search input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        transition: all 0.2s;
    }

    .searchable-select-search input:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    .searchable-select-search input::placeholder {
        color: #94a3b8;
    }

    .searchable-select-options {
        overflow-y: auto;
        max-height: 240px;
    }

    .searchable-select-option {
        padding: 10px 14px;
        cursor: pointer;
        transition: background-color 0.15s;
        font-size: 14px;
        color: #1e293b;
    }

    .searchable-select-option:hover {
        background-color: #f8fafc;
    }

    .searchable-select-option.selected {
        background-color: #eff6ff;
        color: #0453cb;
        font-weight: 500;
    }

    .searchable-select-option.highlighted {
        background-color: #0453cb;
        color: white;
    }

    .searchable-select-no-results {
        padding: 24px 14px;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Scrollbar styling */
    .searchable-select-options::-webkit-scrollbar {
        width: 8px;
    }

    .searchable-select-options::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .searchable-select-options::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .searchable-select-options::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Alpine.js cloak */
    [x-cloak] {
        display: none !important;
    }

    /* Fix z-index conflict avec card-moderne hover */
    .card-moderne:has(.searchable-select.active) {
        transform: none !important;
        position: relative;
        z-index: 1;
    }

    .card-moderne:has(.searchable-select.active):hover {
        transform: none !important;
    }

    /* ========================================
       COMPTEUR D'ÉTUDIANTS AVEC FILTRES
       ======================================== */
    .students-counter-widget {
        display: flex;
        align-items: center;
        padding: 24px;  /* 8px grid × 3 */
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #0284c7;
        border-radius: 16px;  /* 8px grid × 2 */
        box-shadow: 0 4px 12px rgba(4, 83, 203, 0.08);
        animation: fadeInDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .counter-content {
        display: flex;
        align-items: center;
        gap: 16px;  /* 8px grid × 2 */
        width: 100%;
    }

    .counter-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56px;  /* 8px grid × 7 */
        height: 56px;
        background: linear-gradient(135deg, #0453cb 0%, #0284c7 100%);
        border-radius: 12px;  /* 8px grid × 1.5 */
        color: white;
        font-size: 24px;
        flex-shrink: 0;
    }

    .counter-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex-grow: 1;
    }

    .counter-label {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #075985;
    }

    .counter-value {
        font-size: 32px;
        font-weight: 700;
        color: #0453cb;
        line-height: 1;
    }

    .counter-context {
        font-size: 14px;
        color: #0c4a6e;
        opacity: 0.8;
        font-weight: 500;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Animation fadeInDown */
    @keyframes fadeInDown {
        0% {
            opacity: 0;
            transform: translateY(-20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* RESPONSIVE - Tablet */
    @media (max-width: 768px) {
        .students-counter-widget {
            padding: 20px;  /* 8px grid × 2.5 */
        }

        .counter-icon {
            width: 48px;  /* 8px grid × 6 */
            height: 48px;
            font-size: 20px;
        }

        .counter-value {
            font-size: 28px;
        }

        .counter-label {
            font-size: 12px;
        }

        .counter-context {
            font-size: 13px;
        }
    }

    /* RESPONSIVE - Mobile */
    @media (max-width: 576px) {
        .students-counter-widget {
            flex-direction: column;
            align-items: flex-start;
            padding: 16px;  /* 8px grid × 2 */
        }

        .counter-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .counter-icon {
            width: 44px;  /* 8px grid × 5.5 */
            height: 44px;
            font-size: 18px;
        }

        .counter-value {
            font-size: 24px;
        }

        .counter-context {
            margin-top: 4px;
            font-size: 12px;
        }
    }

    /* ========================================
       EXPORT MODAL — Premium Multi-Select
       ======================================== */
    .export-modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow:
            0 8px 16px -4px rgba(0, 0, 0, 0.08),
            0 20px 40px -8px rgba(0, 0, 0, 0.12);
    }

    .export-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 20px 24px 16px;
        background: linear-gradient(135deg, #f8faff 0%, #f0f4ff 100%);
        border-bottom: 1px solid #e2e8f0;
    }

    .export-modal-title {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .export-modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--primary, #0453cb);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        flex-shrink: 0;
    }

    .export-modal-title h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.01em;
    }

    .export-modal-subtitle {
        margin: 2px 0 0;
        font-size: 13px;
        color: #64748b;
        font-weight: 400;
    }

    .export-modal-close {
        width: 32px;
        height: 32px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: white;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }

    .export-modal-close:hover {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    .export-modal-body {
        padding: 20px 24px;
        max-height: 55vh;
        overflow-y: auto;
    }

    /* Selection grid: 2 columns for Filières + Niveaux */
    .export-filiere-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        background: #fafbfc;
        transition: border-color 0.2s, background 0.2s;
    }

    .export-filiere-card:hover {
        border-color: #c7d5f0;
    }

    .export-filiere-card.has-selection {
        border-color: #93b4e8;
        background: #f5f8ff;
    }

    .export-niveau-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        border: 1.5px solid #e2e8f0;
        background: white;
        font-size: 12px;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }

    .export-niveau-pill:hover {
        border-color: #93b4e8;
        background: #f0f4ff;
    }

    .export-niveau-pill .export-pill-check {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 1.5px solid #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: transparent;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .export-niveau-pill.active {
        border-color: #0453cb;
        background: #e8f0fe;
        color: #0453cb;
        font-weight: 600;
    }

    .export-niveau-pill.active .export-pill-check {
        background: #0453cb;
        border-color: #0453cb;
        color: white;
    }

    .export-classes-auto {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        background: #f8fafc;
    }

    .export-classes-auto-title {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .export-class-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 6px;
        background: #e8f0fe;
        color: #0453cb;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1.5px solid transparent;
        user-select: none;
    }
    .export-class-tag:hover {
        background: #d6e4fd;
    }
    .export-class-tag.unchecked {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
    }
    .export-class-tag.unchecked:hover {
        background: #e8f0fe;
        color: #0453cb;
        border-color: transparent;
    }
    .export-class-tag .tag-check {
        font-size: 8px;
        transition: transform 0.15s ease;
    }
    .export-class-tag.unchecked .tag-check {
        opacity: 0.4;
    }

    .export-class-tag .tag-filiere {
        font-size: 9px;
        color: #64748b;
        margin-left: 2px;
    }

    .export-select-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: white;
        border-bottom: 1px solid #f1f5f9;
    }

    .export-select-title {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .export-select-title i {
        color: var(--primary, #0453cb);
        font-size: 13px;
    }

    .export-select-counter {
        font-size: 12px;
        font-weight: 600;
        color: var(--primary, #0453cb);
        background: #eff6ff;
        padding: 3px 8px;
        border-radius: 6px;
    }

    .export-toggle-all {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
        user-select: none;
        transition: background 0.12s ease;
    }

    .export-toggle-all:hover {
        background: #f1f5f9;
    }

    .export-toggle-inline {
        border-bottom: none;
        padding: 0;
        background: none;
        flex-shrink: 0;
    }

    .export-toggle-inline:hover {
        background: none;
    }

    /* Custom checkbox */
    .export-checkbox {
        width: 18px;
        height: 18px;
        border: 2px solid #cbd5e1;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: white;
        transition: all 0.12s ease;
        flex-shrink: 0;
        background: white;
    }

    .export-checkbox.checked {
        background: var(--primary, #0453cb);
        border-color: var(--primary, #0453cb);
    }

    .export-checkbox.partial {
        background: #94a3b8;
        border-color: #94a3b8;
    }

    .export-checkbox-list {
        padding: 4px 8px 8px;
        max-height: 180px;
        overflow-y: auto;
    }

    .export-checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        cursor: pointer;
        user-select: none;
        transition: background 0.1s ease;
    }

    .export-checkbox-item:hover {
        background: #f1f5f9;
    }

    .export-checkbox-label {
        font-size: 13px;
        color: #334155;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .export-checkbox-item-compact {
        padding: 5px 6px;
    }

    .export-checkbox-item-compact .export-checkbox-label {
        font-size: 12.5px;
    }

    .export-checkbox-item-compact .export-checkbox {
        width: 16px;
        height: 16px;
        font-size: 9px;
        border-radius: 4px;
    }

    /* Classes toolbar: toggle all + search side by side */
    .export-classes-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 14px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    .export-search-mini {
        display: flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 5px 10px;
        flex: 1;
        max-width: 240px;
        transition: border-color 0.15s ease;
    }

    .export-search-mini:focus-within {
        border-color: var(--primary, #0453cb);
        box-shadow: 0 0 0 2px rgba(4, 83, 203, 0.1);
    }

    .export-search-mini i {
        color: #94a3b8;
        font-size: 12px;
        flex-shrink: 0;
    }

    .export-search-mini input {
        border: none;
        outline: none;
        font-size: 12.5px;
        color: #334155;
        width: 100%;
        background: transparent;
    }

    .export-search-mini input::placeholder {
        color: #94a3b8;
    }

    .export-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2px;
        padding: 6px 8px 10px;
        max-height: 200px;
        overflow-y: auto;
    }

    .export-empty-hint {
        grid-column: 1 / -1;
        text-align: center;
        padding: 16px;
        color: #94a3b8;
        font-size: 13px;
    }

    .export-empty-hint i {
        margin-right: 4px;
    }

    /* Group by section */
    .export-panel-section {
        padding: 0;
    }

    .export-panel-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .export-group-options {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 6px;
    }

    .export-radio {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12.5px;
        font-weight: 500;
        color: #475569;
        transition: all 0.15s ease;
        background: #fafbfc;
        user-select: none;
    }

    .export-radio input[type="radio"] {
        display: none;
    }

    .export-radio i {
        font-size: 12px;
        color: #94a3b8;
        transition: color 0.15s ease;
    }

    .export-radio:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .export-radio.active {
        border-color: var(--primary, #0453cb);
        background: #eff6ff;
        color: var(--primary, #0453cb);
        font-weight: 600;
    }

    .export-radio.active i {
        color: var(--primary, #0453cb);
    }

    /* Footer */
    .export-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        gap: 16px;
    }

    .export-format-buttons {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .export-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 18px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        background: white;
        transition: all 0.2s ease;
        text-align: left;
    }

    .export-btn i {
        font-size: 20px;
        flex-shrink: 0;
    }

    .export-btn strong {
        display: block;
        font-size: 13px;
        color: #1e293b;
        font-weight: 600;
    }

    .export-btn small {
        display: block;
        font-size: 11px;
        color: #94a3b8;
        font-weight: 400;
        margin-top: 1px;
    }

    .export-btn-excel i { color: #10b981; }
    .export-btn-excel:hover {
        border-color: #10b981;
        background: #ecfdf5;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
    }

    .export-btn-pdf i { color: var(--primary, #0453cb); }
    .export-btn-pdf:hover {
        border-color: var(--primary, #0453cb);
        background: #eff6ff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(4, 83, 203, 0.15);
    }

    .export-panel-hint {
        font-size: 12px;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .export-panel-hint i {
        font-size: 13px;
        color: #cbd5e1;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .export-select-grid {
            grid-template-columns: 1fr;
        }

        .export-checkbox-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .export-group-options {
            grid-template-columns: 1fr 1fr;
        }

        .export-modal-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .export-format-buttons {
            justify-content: stretch;
        }

        .export-format-buttons .export-btn {
            flex: 1;
        }

        .export-modal-body {
            max-height: 60vh;
        }
    }

    @media (max-width: 576px) {
        .export-modal-content {
            border-radius: 16px 16px 0 0;
        }

        .modal-dialog.modal-lg {
            margin: 0;
            max-width: 100%;
            align-items: flex-end;
            min-height: 100%;
        }

        .export-checkbox-grid {
            grid-template-columns: 1fr;
        }

        .export-classes-toolbar {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .export-search-mini {
            max-width: 100%;
        }
    }

    /* Scrollbar styling for checkbox lists */
    .export-checkbox-list::-webkit-scrollbar,
    .export-checkbox-grid::-webkit-scrollbar,
    .export-modal-body::-webkit-scrollbar {
        width: 5px;
    }

    .export-checkbox-list::-webkit-scrollbar-track,
    .export-checkbox-grid::-webkit-scrollbar-track,
    .export-modal-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .export-checkbox-list::-webkit-scrollbar-thumb,
    .export-checkbox-grid::-webkit-scrollbar-thumb,
    .export-modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .export-checkbox-list::-webkit-scrollbar-thumb:hover,
    .export-checkbox-grid::-webkit-scrollbar-thumb:hover,
    .export-modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Étudiants</h1>
                <p class="header-subtitle">Gestion des étudiants de l'établissement</p>
            </div>
            <div class="header-actions">
                @can('inscriptions.create')
                <a href="{{ route('esbtp.inscriptions.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Ajouter un étudiant
                </a>
                @endcan
                @can('inscriptions.view')
                <a href="{{ route('esbtp.reinscription.index') }}" class="btn-acasi success">
                    <i class="fas fa-user-graduate"></i>Réinscriptions
                </a>
                @endcan
                @can('inscriptions.create')
                <button type="button"
                        class="btn-acasi success"
                        data-bs-toggle="modal"
                        data-bs-target="#bulkReinscriptionModal"
                        title="Lancer une réinscription groupée — diagnostic moyenne/décision/frais soldés par étudiant">
                    <i class="fas fa-layer-group"></i>Réinscription groupée
                </button>
                @endcan

                {{-- Export modal trigger --}}
                <button type="button" class="btn-acasi secondary" data-bs-toggle="modal" data-bs-target="#exportModal" style="gap: 6px;">
                    <i class="fas fa-download"></i>Exporter
                </button>
            </div>
        </div>

        {{-- ============================================================
             EXPORT MODAL — Multi-select avec checkboxes
             ============================================================ --}}
        <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true"
             x-data="exportModal()" x-init="init()">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content export-modal-content">
                    {{-- Header --}}
                    <div class="export-modal-header">
                        <div class="export-modal-title">
                            <div class="export-modal-icon">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <div>
                                <h5 id="exportModalLabel">Exporter la liste des étudiants</h5>
                                <p class="export-modal-subtitle">
                                    Sélectionnez les éléments à inclure dans l'export
                                </p>
                            </div>
                        </div>
                        <button type="button" class="export-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="export-modal-body">
                        {{-- Global toolbar --}}
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label class="export-toggle-all export-toggle-inline" @click.prevent="toggleAll()">
                                    <span class="export-checkbox" :class="{ 'checked': allSelected, 'partial': someSelected && !allSelected }">
                                        <i x-show="allSelected" class="fas fa-check"></i>
                                        <i x-show="someSelected && !allSelected" class="fas fa-minus"></i>
                                    </span>
                                    <span x-text="allSelected ? 'Tout désélect.' : 'Tout sélect.'"></span>
                                </label>
                                <span class="export-select-counter" style="font-size: 12px; background: #f0f4ff; padding: 2px 10px; border-radius: 20px; color: #0453cb; font-weight: 600;">
                                    <span x-text="selectedCombinations.length"></span> sélection<span x-show="selectedCombinations.length > 1">s</span>
                                </span>
                            </div>
                        </div>

                        {{-- Filières & Niveaux grouped (like matières pattern) --}}
                        <div style="display: flex; flex-direction: column; gap: 10px; max-height: 340px; overflow-y: auto; padding-right: 4px;">
                            @foreach($filieres as $f)
                            <div class="export-filiere-card" :class="{ 'has-selection': hasFiliereSelection({{ $f->id }}) }">
                                {{-- Filière header --}}
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary, #0453cb); flex-shrink: 0;"></span>
                                        <span style="font-weight: 600; font-size: 13px; color: #1e293b;">{{ $f->name }}</span>
                                        @if($f->code)
                                        <span style="font-size: 10px; background: #e0e7ff; color: #3730a3; padding: 1px 6px; border-radius: 4px; font-weight: 500;">{{ $f->code }}</span>
                                        @endif
                                    </div>
                                    <label style="font-size: 11px; color: var(--primary, #0453cb); cursor: pointer; display: flex; align-items: center; gap: 4px; font-weight: 500;"
                                           @click.prevent="toggleAllNiveauxOfFiliere({{ $f->id }})">
                                        <i class="fas" :class="isFiliereFullySelected({{ $f->id }}) ? 'fa-times-circle' : 'fa-check-double'" style="font-size: 10px;"></i>
                                        <span x-text="isFiliereFullySelected({{ $f->id }}) ? 'Tout désélect.' : 'Tout sélect.'"></span>
                                    </label>
                                </div>
                                {{-- Niveaux pills --}}
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    @foreach($niveaux as $n)
                                    @php
                                        $hasClasses = $classes->where('filiere_id', $f->id)->where('niveau_etude_id', $n->id)->isNotEmpty();
                                    @endphp
                                    @if($hasClasses)
                                    <span class="export-niveau-pill"
                                          :class="{ 'active': isCombinationSelected({{ $f->id }}, {{ $n->id }}) }"
                                          @click="toggleCombination({{ $f->id }}, {{ $n->id }})">
                                        <span class="export-pill-check">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        {{ $n->name }}
                                        @if($n->code)
                                        <span style="font-size: 9px; opacity: 0.65; margin-left: 2px;">{{ $n->code }}</span>
                                        @endif
                                    </span>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Selectable classes --}}
                        <div class="export-classes-auto" x-show="comboClasses.length > 0" style="margin-top: 12px;">
                            <div class="export-classes-auto-title">
                                <i class="fas fa-chalkboard"></i>
                                Classes incluses
                                <span class="export-select-counter" style="font-size: 11px;" x-text="'(' + selectedClassIds.length + ' / ' + comboClasses.length + ')'"></span>
                                <span style="margin-left: auto; font-size: 11px; color: var(--primary, #0453cb); cursor: pointer; font-weight: 500;"
                                      @click="toggleAllClasses()">
                                    <i class="fas" :class="selectedClassIds.length === comboClasses.length ? 'fa-times-circle' : 'fa-check-double'" style="font-size: 10px; margin-right: 2px;"></i>
                                    <span x-text="selectedClassIds.length === comboClasses.length ? 'Tout décocher' : 'Tout cocher'"></span>
                                </span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <template x-for="cls in comboClasses" :key="cls.id">
                                    <span class="export-class-tag"
                                          :class="{ 'unchecked': !isClassSelected(cls.id) }"
                                          @click="toggleClass(cls.id)">
                                        <i class="fas tag-check" :class="isClassSelected(cls.id) ? 'fa-check' : 'fa-plus'"></i>
                                        <span x-text="cls.name"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div x-show="comboClasses.length === 0" style="margin-top: 12px; text-align: center; color: #94a3b8; font-size: 12px; padding: 16px;">
                            <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                            Cliquez sur les niveaux dans les filières pour sélectionner les classes à exporter
                        </div>

                        {{-- Group by --}}
                        <div class="export-panel-section">
                            <label class="export-panel-label">Regrouper par</label>
                            <div class="export-group-options">
                                <label class="export-radio" :class="{ 'active': exportGroupBy === '' }" @click="exportGroupBy = ''">
                                    <input type="radio" name="export_group" value="" x-model="exportGroupBy">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Tout combiné</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'classe' }" @click="exportGroupBy = 'classe'">
                                    <input type="radio" name="export_group" value="classe" x-model="exportGroupBy">
                                    <i class="fas fa-chalkboard"></i>
                                    <span>Par classe</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'filiere' }" @click="exportGroupBy = 'filiere'">
                                    <input type="radio" name="export_group" value="filiere" x-model="exportGroupBy">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Par filière</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'niveau' }" @click="exportGroupBy = 'niveau'">
                                    <input type="radio" name="export_group" value="niveau" x-model="exportGroupBy">
                                    <i class="fas fa-signal"></i>
                                    <span>Par niveau</span>
                                </label>
                                <label class="export-radio" :class="{ 'active': exportGroupBy === 'filiere_niveau' }" @click="exportGroupBy = 'filiere_niveau'">
                                    <input type="radio" name="export_group" value="filiere_niveau" x-model="exportGroupBy">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Par filière et niveau</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="export-modal-footer">
                        <div class="export-panel-hint">
                            <i class="fas fa-info-circle"></i>
                            <span>Les filtres de la page seront aussi appliqués</span>
                        </div>
                        <div class="export-format-buttons">
                            <button type="button" class="export-btn export-btn-excel" @click="doExport('excel')">
                                <i class="fas fa-file-excel"></i>
                                <div>
                                    <strong>Excel</strong>
                                    <small>.xlsx</small>
                                </div>
                            </button>
                            <button type="button" class="export-btn export-btn-pdf" @click="doExport('pdf')">
                                <i class="fas fa-file-pdf"></i>
                                <div>
                                    <strong>PDF</strong>
                                    <small>.pdf</small>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
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


                <!-- Filtres de recherche (Desktop uniquement) -->
                <div class="desktop-filters">
                    <div class="section-title mb-md" style="display:flex; align-items:center; justify-content:space-between;">
                        <span><i class="fas fa-filter me-2"></i>Filtres de recherche</span>
                        <button type="button" id="toggle-advanced-filters-btn" style="background:none;border:1px solid #e0e0e0;border-radius:6px;padding:0.35rem 0.9rem;font-size:0.8rem;color:#5c5c5c;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;font-weight:500;">
                            <i class="fas fa-sliders-h"></i> Filtres avancés <i class="fas fa-chevron-down" style="font-size:0.6rem;"></i>
                        </button>
                    </div>
                    @php
                        $etSysteme = in_array(request('systeme'), ['BTS', 'LMD'], true) ? request('systeme') : '';
                    @endphp
                    <form method="GET" action="{{ route('esbtp.etudiants.index') }}" id="search-form" x-data="{ etSysteme: '{{ $etSysteme }}' }">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="search" class="form-label">Recherche</label>
                                        <input type="text" class="form-control search-bar" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        {{-- Switch Système BTS/LMD : pilote l'affichage du 2e filtre. --}}
                                        <label for="systeme" class="form-label">Système</label>
                                        <select class="form-select year-selector" id="systeme" name="systeme" x-model="etSysteme">
                                            <option value="" @selected(!request('systeme'))>Tous</option>
                                            <option value="BTS" @selected(request('systeme') === 'BTS')>BTS</option>
                                            <option value="LMD" @selected(request('systeme') === 'LMD')>LMD</option>
                                        </select>
                                    </div>
                                    {{-- BTS / Tous : Filière BTS classique --}}
                                    <div class="col-md-3 mb-3" x-show="etSysteme !== 'LMD'" x-cloak>
                                        <label for="filiere" class="form-label">Filière BTS</label>
                                        <select class="form-select year-selector" id="filiere" name="filiere">
                                            <option value="">Toutes les filières</option>
                                            @foreach($filieres as $f)
                                                <option value="{{ $f->id }}" {{ isset($filiere) && $filiere == $f->id ? 'selected' : '' }}>
                                                    {{ $f->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- LMD : Mention picker + Parcours picker premium --}}
                                    <div class="col-md-3 mb-3" x-show="etSysteme === 'LMD'" x-cloak>
                                        <label class="form-label">Mention LMD</label>
                                        <x-au-mention-picker
                                            name="mention"
                                            :value="request('mention')"
                                            :mentions="$mentions"
                                            placeholder="Toutes les mentions"
                                        />
                                    </div>
                                    <div class="col-md-2 mb-3" x-show="etSysteme === 'LMD'" x-cloak>
                                        <label class="form-label">Parcours</label>
                                        <x-au-parcours-picker
                                            name="parcours"
                                            :value="request('parcours')"
                                            :parcours="$parcoursList"
                                            :mention-filter="request('mention')"
                                        />
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="niveau" class="form-label">Niveau d'études</label>
                                        <select class="form-select year-selector" id="niveau" name="niveau">
                                            <option value="">Tous les niveaux</option>
                                            @foreach($niveaux as $n)
                                                <option value="{{ $n->id }}" {{ isset($niveau) && $niveau == $n->id ? 'selected' : '' }}>
                                                    {{ $n->name }} ({{ $n->type }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div id="advanced-filters" style="display:none;">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="classe" class="form-label">Classe</label>
                                        <div x-data="searchableSelect({
                                            options: [
                                                { value: '', label: 'Toutes les classes' },
                                                @foreach($classes as $classeOption)
                                                {
                                                    value: '{{ $classeOption->id }}',
                                                    label: '{!! str_replace("'", "\\'", $classeOption->name) !!} @if($classeOption->filiere || $classeOption->niveauEtude)({!! str_replace("'", "\\'", $classeOption->filiere->name ?? "Filière N/A") !!} - {!! str_replace("'", "\\'", $classeOption->niveauEtude->name ?? "Niveau N/A") !!})@endif'
                                                },
                                                @endforeach
                                            ],
                                            selected: '{{ $classe ?? '' }}',
                                            name: 'classe',
                                            placeholder: 'Rechercher une classe...'
                                        })" class="searchable-select" :class="{ 'active': open }" @click.away="open = false">
                                            <input type="hidden" name="classe" :value="selectedValue" id="classe">
                                            <button type="button" class="searchable-select-trigger" @click="open = !open">
                                                <span class="searchable-select-trigger-text" :class="{ 'placeholder': !selectedLabel }">
                                                    <span x-text="selectedLabel || placeholder">Rechercher une classe...</span>
                                                </span>
                                                <i class="fas fa-chevron-down searchable-select-icon"></i>
                                            </button>
                                            <div x-show="open" class="searchable-select-dropdown" x-cloak>
                                                <div class="searchable-select-search">
                                                    <input type="text" x-model="search" @input="filterOptions" placeholder="Tapez pour rechercher..." @click.stop x-ref="searchInput">
                                                </div>
                                                <div class="searchable-select-options">
                                                    <template x-if="filteredOptions.length === 0">
                                                        <div class="searchable-select-no-results">
                                                            <i class="fas fa-search mb-2"></i>
                                                            <div>Aucune classe trouvée</div>
                                                        </div>
                                                    </template>
                                                    <template x-for="option in filteredOptions" :key="option.value">
                                                        <div class="searchable-select-option" :class="{ 'selected': option.value === selectedValue }" @click="selectOption(option)">
                                                            <span x-text="option.label"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="annee" class="form-label">Année universitaire</label>
                                        <select class="form-select year-selector" id="annee" name="annee">
                                            <option value="">Toutes les années</option>
                                            @foreach($annees as $a)
                                                <option value="{{ $a->id }}" {{ isset($annee) && $annee == $a->id ? 'selected' : '' }}>
                                                    {{ $a->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">Statut</label>
                                        <select class="form-select year-selector" id="status" name="status">
                                            <option value="">Tous les statuts</option>
                                            <option value="actif" {{ isset($status) && $status == 'actif' ? 'selected' : '' }}>Actif</option>
                                            <option value="inactif" {{ isset($status) && $status == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="affectation_status" class="form-label">Statut d'affectation ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                                        <select class="form-select year-selector" id="affectation_status" name="affectation_status">
                                            <option value="">Tous les statuts d'affectation</option>
                                            <option value="affecté" {{ isset($affectationStatus) && $affectationStatus == 'affecté' ? 'selected' : '' }}>Affecté</option>
                                            <option value="réaffecté" {{ isset($affectationStatus) && $affectationStatus == 'réaffecté' ? 'selected' : '' }}>Réaffecté</option>
                                            <option value="non_affecté" {{ isset($affectationStatus) && $affectationStatus == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="inscrit_annee_courante" class="form-label">Inscription validée ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                                        <select class="form-select year-selector" id="inscrit_annee_courante" name="inscrit_annee_courante">
                                            <option value="">Tous</option>
                                            <option value="validee" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'validee' ? 'selected' : '' }}>Oui (Validée)</option>
                                            <option value="en_attente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                            <option value="absente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'absente' ? 'selected' : '' }}>Absente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="est_transfert" class="form-label">Transfert</label>
                                        <select class="form-select year-selector" id="est_transfert" name="est_transfert">
                                            <option value="">Tous</option>
                                            <option value="1" {{ isset($estTransfert) && $estTransfert == '1' ? 'selected' : '' }}>Oui (Transferts)</option>
                                            <option value="0" {{ isset($estTransfert) && $estTransfert == '0' ? 'selected' : '' }}>Non (Locaux)</option>
                                        </select>
                                    </div>
                                    @can('students.accessibility.view')
                                    <div class="col-md-4 mb-3">
                                        <label for="accessibility" class="form-label">
                                            <i class="fas fa-universal-access text-primary me-1"></i>Accessibilité
                                        </label>
                                        <select class="form-select year-selector" id="accessibility" name="accessibility">
                                            @include('esbtp.etudiants.partials.accessibility-filter-options', ['current' => $accessibility ?? null])
                                        </select>
                                    </div>
                                    @endcan
                                    <div class="col-md-4 d-flex align-items-end mb-3">
                                        <button type="submit" class="btn-acasi primary me-2">
                                            <i class="fas fa-search"></i>Filtrer
                                        </button>
                                        <button type="button" class="btn-acasi secondary" id="desktop-reset-btn">
                                            <i class="fas fa-redo-alt"></i>Réinitialiser
                                        </button>
                                    </div>
                                </div>
                                </div><!-- /#advanced-filters -->
                    </form>
                </div><!-- /.desktop-filters -->
            </div>
        </div>

        <!-- ========================================
             MOBILE FILTER DRAWER (Standards 2025)
             ======================================== -->

        <!-- Bouton FAB flottant (visible sur mobile uniquement) -->
        <button type="button" class="mobile-filter-fab" id="mobile-filter-fab">
            <i class="fas fa-filter"></i>
        </button>

        <!-- Drawer overlay -->
        <div class="filter-drawer-overlay" id="filter-drawer-overlay"></div>

        <!-- Drawer panel -->
        <div class="filter-drawer" id="filter-drawer">
            <!-- Header -->
            <div class="filter-drawer-header">
                <h3>
                    <i class="fas fa-filter me-2"></i>
                    Filtres de recherche
                </h3>
                <button type="button" class="filter-drawer-close" id="filter-drawer-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body scrollable avec tous les filtres -->
            <div class="filter-drawer-body">
                <form method="GET" action="{{ route('esbtp.etudiants.index') }}" id="mobile-search-form" x-data="{ etSysteme: '{{ $etSysteme }}' }">
                    <!-- Recherche -->
                    <div class="form-group">
                        <label for="mobile-search" class="form-label">Recherche</label>
                        <input type="text" class="form-control" id="mobile-search" name="search" value="{{ $search ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                    </div>

                    <!-- Système BTS/LMD -->
                    <div class="form-group">
                        <label for="mobile-systeme" class="form-label">Système</label>
                        <select class="form-select" id="mobile-systeme" name="systeme" x-model="etSysteme">
                            <option value="" @selected(!request('systeme'))>Tous</option>
                            <option value="BTS" @selected(request('systeme') === 'BTS')>BTS</option>
                            <option value="LMD" @selected(request('systeme') === 'LMD')>LMD</option>
                        </select>
                    </div>

                    <!-- Filière BTS (mode BTS / Tous) -->
                    <div class="form-group" x-show="etSysteme !== 'LMD'" x-cloak>
                        <label for="mobile-filiere" class="form-label">Filière BTS</label>
                        <select class="form-select" id="mobile-filiere" name="filiere">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $f)
                                <option value="{{ $f->id }}" {{ isset($filiere) && $filiere == $f->id ? 'selected' : '' }}>
                                    {{ $f->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mention LMD + Parcours (mode LMD) -->
                    <div class="form-group" x-show="etSysteme === 'LMD'" x-cloak>
                        <label class="form-label">Mention LMD</label>
                        <x-au-mention-picker
                            name="mention"
                            :value="request('mention')"
                            :mentions="$mentions"
                            placeholder="Toutes les mentions"
                        />
                    </div>
                    <div class="form-group" x-show="etSysteme === 'LMD'" x-cloak>
                        <label class="form-label">Parcours</label>
                        <x-au-parcours-picker
                            name="parcours"
                            :value="request('parcours')"
                            :parcours="$parcoursList"
                            :mention-filter="request('mention')"
                        />
                    </div>

                    <!-- Niveau d'études -->
                    <div class="form-group">
                        <label for="mobile-niveau" class="form-label">Niveau d'études</label>
                        <select class="form-select" id="mobile-niveau" name="niveau">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $n)
                                <option value="{{ $n->id }}" {{ isset($niveau) && $niveau == $n->id ? 'selected' : '' }}>
                                    {{ $n->name }} ({{ $n->type }} - Année {{ $n->year }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Classe avec Alpine.js searchable-select (COPIE EXACTE) -->
                    <div class="form-group">
                        <label for="mobile-classe" class="form-label">Classe</label>
                        <div x-data="searchableSelect({
                            options: [
                                { value: '', label: 'Toutes les classes' },
                                @foreach($classes as $classeOption)
                                {
                                    value: '{{ $classeOption->id }}',
                                    label: '{{ $classeOption->name }} @if($classeOption->filiere || $classeOption->niveauEtude)({{ $classeOption->filiere->name ?? "Filière N/A" }} - {{ $classeOption->niveauEtude->name ?? "Niveau N/A" }})@endif'
                                },
                                @endforeach
                            ],
                            selected: '{{ $classe ?? '' }}',
                            name: 'classe',
                            placeholder: 'Rechercher une classe...'
                        })" class="searchable-select" :class="{ 'active': open }" @click.away="open = false">
                            <input type="hidden" name="classe" :value="selectedValue" id="mobile-classe">
                            <button type="button" class="searchable-select-trigger" @click="open = !open">
                                <span class="searchable-select-trigger-text" :class="{ 'placeholder': !selectedLabel }">
                                    <span x-text="selectedLabel || placeholder">Rechercher une classe...</span>
                                </span>
                                <i class="fas fa-chevron-down searchable-select-icon"></i>
                            </button>
                            <div x-show="open" class="searchable-select-dropdown" x-cloak>
                                <div class="searchable-select-search">
                                    <input type="text" x-model="search" @input="filterOptions" placeholder="Tapez pour rechercher..." @click.stop x-ref="searchInput">
                                </div>
                                <div class="searchable-select-options">
                                    <template x-if="filteredOptions.length === 0">
                                        <div class="searchable-select-no-results">
                                            <i class="fas fa-search mb-2"></i>
                                            <div>Aucune classe trouvée</div>
                                        </div>
                                    </template>
                                    <template x-for="option in filteredOptions" :key="option.value">
                                        <div class="searchable-select-option" :class="{ 'selected': option.value === selectedValue }" @click="selectOption(option)">
                                            <span x-text="option.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Année universitaire -->
                    <div class="form-group">
                        <label for="mobile-annee" class="form-label">Année universitaire</label>
                        <select class="form-select" id="mobile-annee" name="annee">
                            <option value="">Toutes les années</option>
                            @foreach($annees as $a)
                                <option value="{{ $a->id }}" {{ isset($annee) && $annee == $a->id ? 'selected' : '' }}>
                                    {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Statut -->
                    <div class="form-group">
                        <label for="mobile-status" class="form-label">Statut</label>
                        <select class="form-select" id="mobile-status" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="actif" {{ isset($status) && $status == 'actif' ? 'selected' : '' }}>Actif</option>
                            <option value="inactif" {{ isset($status) && $status == 'inactif' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>

                    <!-- Statut d'affectation -->
                    <div class="form-group">
                        <label for="mobile-affectation_status" class="form-label">Statut d'affectation ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                        <select class="form-select" id="mobile-affectation_status" name="affectation_status">
                            <option value="">Tous les statuts d'affectation</option>
                            <option value="affecté" {{ isset($affectationStatus) && $affectationStatus == 'affecté' ? 'selected' : '' }}>Affecté</option>
                            <option value="réaffecté" {{ isset($affectationStatus) && $affectationStatus == 'réaffecté' ? 'selected' : '' }}>Réaffecté</option>
                            <option value="non_affecté" {{ isset($affectationStatus) && $affectationStatus == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                        </select>
                    </div>

                    <!-- Inscription validée -->
                    <div class="form-group">
                        <label for="mobile-inscrit_annee_courante" class="form-label">Inscription validée ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                        <select class="form-select" id="mobile-inscrit_annee_courante" name="inscrit_annee_courante">
                            <option value="">Tous</option>
                            <option value="validee" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'validee' ? 'selected' : '' }}>Oui (Validée)</option>
                            <option value="en_attente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="absente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'absente' ? 'selected' : '' }}>Absente</option>
                        </select>
                    </div>

                    <!-- Transfert -->
                    <div class="form-group">
                        <label for="mobile-est_transfert" class="form-label">Transfert</label>
                        <select class="form-select" id="mobile-est_transfert" name="est_transfert">
                            <option value="">Tous</option>
                            <option value="1" {{ isset($estTransfert) && $estTransfert == '1' ? 'selected' : '' }}>Oui (Transferts)</option>
                            <option value="0" {{ isset($estTransfert) && $estTransfert == '0' ? 'selected' : '' }}>Non (Locaux)</option>
                        </select>
                    </div>

                    @can('students.accessibility.view')
                    <!-- Accessibilité -->
                    <div class="form-group">
                        <label for="mobile-accessibility" class="form-label">
                            <i class="fas fa-universal-access text-primary me-1"></i>Accessibilité
                        </label>
                        <select class="form-select" id="mobile-accessibility" name="accessibility">
                            @include('esbtp.etudiants.partials.accessibility-filter-options', ['current' => $accessibility ?? null])
                        </select>
                    </div>
                    @endcan
                </form>
            </div>

            <!-- Footer sticky avec boutons -->
            <div class="filter-drawer-footer">
                <button type="button" class="btn btn-secondary" id="filter-drawer-reset">
                    <i class="fas fa-redo-alt"></i>
                    Réinitialiser
                </button>
                <button type="submit" form="mobile-search-form" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Filtrer
                </button>
            </div>
        </div>

        <!-- Tableau des étudiants -->
        <div class="card-moderne">
            <div class="p-lg">
                <div class="section-title mb-md" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                    <span><i class="fas fa-list me-2"></i>Liste des étudiants</span>
                    <span id="student-count-badge" style="font-size:0.85rem; font-weight:600; color:#0453cb; background:rgba(4,83,203,0.08); padding:0.35rem 1rem; border-radius:20px;"></span>
                </div>

                <!-- Indicateur filtres actifs -->
                <div id="active-filters-container" class="active-filters-container" style="display: none;">
                    <!-- Sera rempli dynamiquement via JavaScript -->
                </div>

                <div id="etudiants-results">
                    @include('esbtp.etudiants.partials.results', ['etudiants' => $etudiants])
</div>
</div>
</div>
</div>
</div>

<!-- Modal d'édition rapide premium — namespace eqe-* (Edit Quick Etudiant) -->
<style>
.eqe-modal .modal-content { border-radius: 16px; overflow: hidden; border: 1px solid rgba(4,83,203,.12); }
.eqe-header { background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%); color: #fff; padding: 1rem 1.25rem; display: flex; align-items: center; gap: .85rem; border-bottom: none; }
.eqe-header-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,.16); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.eqe-header-text { flex: 1; min-width: 0; }
.eqe-header-eyebrow { color: rgba(255,255,255,.7); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin: 0 0 .15rem; }
.eqe-header-title { color: #fff; font-size: 1.05rem; font-weight: 700; margin: 0; line-height: 1.15; }
.eqe-close { background: rgba(255,255,255,.16); color: #fff; border: 1px solid rgba(255,255,255,.22); border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s ease; }
.eqe-close:hover { background: rgba(255,255,255,.28); }
.eqe-body { padding: 0; background: #f8fafc; }
.eqe-tabs { display: flex; gap: .35rem; padding: .55rem .85rem 0; background: #fff; border-bottom: 1px solid #e2e8f0; }
.eqe-tab { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1rem; border-radius: 9px 9px 0 0; background: transparent; color: #475569; border: none; border-bottom: 2px solid transparent; font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .12s ease; }
.eqe-tab:hover { color: #0453cb; background: rgba(4,83,203,.05); }
.eqe-tab.active { color: #0453cb; border-bottom-color: #0453cb; background: rgba(4,83,203,.05); }
.eqe-tab-content { padding: 0; min-height: 480px; }
.eqe-pane { display: none; padding: 0; }
.eqe-pane.active { display: block; }
.eqe-iframe-wrap { position: relative; min-height: 520px; }
.eqe-iframe-loader { position: absolute; inset: 0; background: rgba(255,255,255,.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 5; color: #475569; font-size: .82rem; gap: .5rem; }
.eqe-iframe-loader .spinner-border { color: #0453cb; }
.eqe-iframe-wrap iframe { width: 100%; min-height: 520px; border: none; background: #fff; }
.eqe-inscriptions { padding: 1.25rem; background: #fff; }
</style>
<div class="modal fade eqe-modal" id="etudiantEditModal" tabindex="-1" aria-labelledby="etudiantEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header eqe-header">
                <div class="eqe-header-icon"><i class="fas fa-user-pen"></i></div>
                <div class="eqe-header-text">
                    <p class="eqe-header-eyebrow">Édition rapide</p>
                    <h5 class="eqe-header-title" id="etudiantEditModalLabel">Modifier l'étudiant</h5>
                </div>
                <button type="button" class="eqe-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body eqe-body">
                <div class="eqe-tabs" role="tablist">
                    <button type="button" class="eqe-tab active" id="tab-etudiant-link" data-bs-toggle="tab" data-bs-target="#tab-etudiant" role="tab">
                        <i class="fas fa-user-edit"></i>Étudiant
                    </button>
                    <button type="button" class="eqe-tab" id="tab-inscriptions-link" data-bs-toggle="tab" data-bs-target="#tab-inscriptions" role="tab">
                        <i class="fas fa-graduation-cap"></i>Inscriptions
                    </button>
                </div>
                <div class="tab-content eqe-tab-content" id="editStudentTabContent">
                    <div class="tab-pane fade show active eqe-pane active" id="tab-etudiant" role="tabpanel">
                        <div class="eqe-iframe-wrap">
                            <div id="student-edit-loader" class="eqe-iframe-loader">
                                <div class="spinner-border" role="status" style="width:2rem;height:2rem;"></div>
                                <span>Chargement du formulaire...</span>
                            </div>
                            <iframe id="student-edit-frame" src="about:blank" title="Édition étudiant" loading="eager"></iframe>
                        </div>
                    </div>
                    <div class="tab-pane fade eqe-pane" id="tab-inscriptions" role="tabpanel">
                        <div id="inscriptions-accordion-container" class="eqe-inscriptions">
                            <p style="color:#94a3b8; font-size:.82rem; margin:0;">Sélectionnez un étudiant pour afficher ses inscriptions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('esbtp.reinscription.partials.bulk-modal', ['students' => $etudiantsForBulk ?? $etudiants])

@if(request()->boolean('open_bulk'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('bulkReinscriptionModal');
        if (modalEl && window.bootstrap) {
            new bootstrap.Modal(modalEl).show();
        }
    });
</script>
@endif
@endsection

@push('scripts')
<script>
    // ========================================
    // MOBILE FILTER DRAWER - JavaScript
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        const fab = document.getElementById('mobile-filter-fab');
        const drawer = document.getElementById('filter-drawer');
        const overlay = document.getElementById('filter-drawer-overlay');
        const closeBtn = document.getElementById('filter-drawer-close');
        const resetBtn = document.getElementById('filter-drawer-reset');

        if (!fab || !drawer || !overlay) {
            debugLog('⚠️ Drawer elements not found');
            return;
        }

        // Fonction pour ouvrir le drawer
        function openDrawer() {
            debugLog('📂 Opening filter drawer');
            drawer.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Empêcher le scroll du body
        }

        // Fonction pour fermer le drawer
        function closeDrawer() {
            debugLog('📁 Closing filter drawer');
            drawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restaurer le scroll
        }

        // Event listeners
        fab.addEventListener('click', openDrawer);
        closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);

        // Bouton réinitialiser dans le drawer (AJAX - pas de refresh)
        resetBtn.addEventListener('click', function() {
            debugLog('🔄 Réinitialisation des filtres (drawer mobile)');

            // Utiliser clearAllFilters qui gère tout (AJAX + reset selects)
            if (typeof clearAllFilters === 'function') {
                clearAllFilters();

                // Fermer le drawer après l'AJAX
                setTimeout(closeDrawer, 300);
            } else {
                // Fallback si clearAllFilters n'est pas disponible
                window.location.href = '{{ route('esbtp.etudiants.index') }}';
            }
        });

        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('active')) {
                closeDrawer();
            }
        });

        // ========================================
        // AJAX SUBMISSION DU DRAWER (PAS DE REFRESH PAGE)
        // ========================================
        const mobileForm = document.getElementById('mobile-search-form');
        if (mobileForm) {
            mobileForm.addEventListener('submit', function(e) {
                e.preventDefault();  // Empêcher la soumission normale (refresh page)
                debugLog('📤 Soumission AJAX du drawer mobile');

                // Construire les paramètres depuis le formulaire mobile
                const formData = new FormData(mobileForm);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (value) {  // Ignorer les valeurs vides
                        params.append(key, value);
                    }
                }

                const url = mobileForm.action + '?' + params.toString();
                debugLog('📍 URL AJAX:', url);

                // Utiliser la fonction fetchResults existante (définie plus bas dans le script)
                if (typeof window.fetchResultsGlobal === 'function') {
                    window.fetchResultsGlobal(url, { pushState: true });

                    // Fermer le drawer après la soumission
                    setTimeout(closeDrawer, 300);  // Petit délai pour UX smooth
                } else {
                    debugError('❌ fetchResultsGlobal non disponible');
                }
            });
        }

        debugLog('✅ Mobile filter drawer initialized');
    });

    // ========================================
    // ALPINE.JS SEARCHABLE SELECT COMPONENT
    // ========================================
    // Alpine.js Searchable Select Component - Défini globalement
    window.searchableSelect = function(config) {
        debugLog('🔧 Initialisation searchableSelect avec config:', config);
        return {
            options: config.options || [],
            filteredOptions: [],
            search: '',
            open: false,
            selectedValue: config.selected || '',
            selectedLabel: '',
            placeholder: config.placeholder || 'Sélectionner...',

            init() {
                debugLog('✅ searchableSelect init() appelé');
                debugLog('📊 Nombre d\'options:', this.options.length);
                this.filteredOptions = this.options;
                this.updateSelectedLabel();
                debugLog('🏷️ Label sélectionné:', this.selectedLabel);

                // Watch for open changes to focus search input
                this.$watch('open', value => {
                    debugLog('👁️ Dropdown open:', value);
                    if (value) {
                        this.$nextTick(() => {
                            this.$refs.searchInput?.focus();
                        });
                    } else {
                        this.search = '';
                        this.filteredOptions = this.options;
                    }
                });

                // Écouter les events de reset
                const componentName = config.name;

                // Reset individuel (pour ce composant spécifique)
                window.addEventListener('reset-searchable-select', (e) => {
                    if (e.detail && e.detail.name === componentName) {
                        debugLog('🔄 Reset event received for:', componentName);
                        this.selectedValue = '';
                        this.selectedLabel = '';
                        this.search = '';
                        this.filteredOptions = this.options;
                        this.open = false;
                    }
                });

                // Reset tous les composants
                window.addEventListener('reset-all-searchable-selects', () => {
                    debugLog('🔄 Reset ALL event received for:', componentName);
                    this.selectedValue = '';
                    this.selectedLabel = '';
                    this.search = '';
                    this.filteredOptions = this.options;
                    this.open = false;
                });
            },

            filterOptions() {
                const searchLower = this.search.toLowerCase();
                this.filteredOptions = this.options.filter(option =>
                    option.label.toLowerCase().includes(searchLower)
                );
                debugLog('🔍 Filtrage:', this.search, '→', this.filteredOptions.length, 'résultats');
            },

            selectOption(option) {
                debugLog('✅ Option sélectionnée:', option);
                this.selectedValue = option.value;
                this.selectedLabel = option.label;
                this.open = false;
                this.search = '';
                this.filteredOptions = this.options;

                // Trigger AJAX refresh instead of form submission
                this.$nextTick(() => {
                    if (typeof window.triggerFilterChange === 'function') {
                        debugLog('📤 Déclenchement AJAX refresh...');
                        window.triggerFilterChange();
                    }
                });
            },

            updateSelectedLabel() {
                const selected = this.options.find(opt => opt.value === this.selectedValue);
                this.selectedLabel = selected ? selected.label : '';
                debugLog('🔄 updateSelectedLabel - value:', this.selectedValue, 'label:', this.selectedLabel);
            }
        }
    }

    debugLog('✅ Fonction searchableSelect définie globalement');

    // Update student count badge from partial data
    function updateStudentCountBadge() {
        var el = document.getElementById('student-count-inline');
        var badge = document.getElementById('student-count-badge');
        if (el && badge) {
            var total = el.dataset.total;
            var page = el.dataset.page;
            var hasPages = el.dataset.hasPages === '1';
            badge.textContent = total + ' étudiant' + (parseInt(total) > 1 ? 's' : '') + (hasPages ? ' (' + page + ' sur cette page)' : '');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        updateStudentCountBadge();

        // Advanced filters toggle
        var toggleBtn = document.getElementById('toggle-advanced-filters-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                var panel = document.getElementById('advanced-filters');
                var icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');
                if (panel.style.display === 'none') {
                    panel.style.display = 'block';
                    if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                } else {
                    panel.style.display = 'none';
                    if (icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            });
        }

        const form = document.getElementById('search-form');
        const resultsContainer = document.getElementById('etudiants-results');
        const submitButton = form.querySelector('button[type="submit"]');
        const filterInputs = form.querySelectorAll('select');
        const modalElement = document.getElementById('etudiantEditModal');
        const inscriptionsContainer = document.getElementById('inscriptions-accordion-container');
        const studentFrame = document.getElementById('student-edit-frame');
        const studentEditLoader = document.getElementById('student-edit-loader');
        let editModal = null;

        function setLoading(isLoading) {
            if (submitButton) {
                submitButton.disabled = isLoading;
            }
            if (isLoading) {
                resultsContainer.classList.add('opacity-50');
            } else {
                resultsContainer.classList.remove('opacity-50');
            }
        }

        // Fonction globale pour déclencher le refresh AJAX depuis le composant Alpine
        window.triggerFilterChange = function() {
            debugLog('🔄 triggerFilterChange appelée');
            const formData = new FormData(form);
            const params = new URLSearchParams();

            // Construire les paramètres depuis le formulaire
            for (const [key, value] of formData.entries()) {
                if (value) {  // Ignorer les valeurs vides
                    params.append(key, value);
                }
            }

            const url = form.action + '?' + params.toString();
            debugLog('📍 URL AJAX:', url);
            fetchResults(url, { pushState: true });
        };

        function bindPagination() {
            // Infinite scroll : remplace la pagination Laravel.
            // Observer la sentinelle, fetch page suivante, append rows au tbody.
            bindInfiniteScroll();
            // Click sur row → navigation vers fiche étudiant (sauf action buttons).
            bindRowClick();
        }

        let infiniteObserver = null;
        let infiniteLoading = false;
        function bindInfiniteScroll() {
            if (infiniteObserver) { infiniteObserver.disconnect(); infiniteObserver = null; }
            const sentinel = document.getElementById('etudiants-sentinel');
            const tbody = document.getElementById('etudiants-tbody');
            if (!sentinel || !tbody) return;
            infiniteObserver = new IntersectionObserver(async (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting || infiniteLoading) continue;
                    if (tbody.dataset.hasMore !== '1') { infiniteObserver.disconnect(); continue; }
                    infiniteLoading = true;
                    const spinner = sentinel.querySelector('.eu-sentinel-spinner');
                    if (spinner) spinner.style.display = 'flex';
                    try {
                        const nextPage = parseInt(tbody.dataset.nextPage || '2', 10);
                        const url = new URL(window.location.href);
                        url.searchParams.set('page', String(nextPage));
                        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        const tmp = document.createElement('div');
                        tmp.innerHTML = data.html || '';
                        const newTbody = tmp.querySelector('#etudiants-tbody');
                        if (newTbody) {
                            Array.from(newTbody.children).forEach((row) => tbody.appendChild(row));
                            tbody.dataset.hasMore = newTbody.dataset.hasMore || '0';
                            tbody.dataset.nextPage = newTbody.dataset.nextPage || String(nextPage + 1);
                            tbody.dataset.currentPage = newTbody.dataset.currentPage || String(nextPage);
                        }
                        const newSentinel = tmp.querySelector('#etudiants-sentinel');
                        if (newSentinel) sentinel.innerHTML = newSentinel.innerHTML;
                        // Re-bind row click on new rows
                        bindRowClick();
                    } catch (e) {
                        console.error('Erreur infinite scroll étudiants:', e);
                    } finally {
                        infiniteLoading = false;
                        if (spinner) spinner.style.display = 'none';
                    }
                }
            }, { rootMargin: '200px' });
            infiniteObserver.observe(sentinel);
        }

        function bindRowClick() {
            document.querySelectorAll('.eu-row').forEach((tr) => {
                if (tr.dataset.clickBound === '1') return;
                tr.dataset.clickBound = '1';
                tr.addEventListener('click', function (ev) {
                    // Ignore si clic sur action button ou enfant marqué stop-propagation
                    if (ev.target.closest('[data-stop-propagation]')) return;
                    if (ev.target.closest('.eu-actions')) return;
                    if (ev.target.closest('a, button')) return;
                    const url = this.dataset.showUrl;
                    if (url) window.location.href = url;
                });
            });
        }

        function initTableSorting(scope = document) {
            const table = scope.querySelector('#etudiants-table');
            if (!table) {
                return;
            }

            scope.querySelectorAll('.table-sort').forEach((button) => {
                if (button.dataset.sortInit === '1') {
                    return;
                }
                button.dataset.sortInit = '1';
                button.addEventListener('click', function () {
                    const column = this.dataset.column;
                    if (!column) {
                        return;
                    }

                    // Récupérer la direction actuelle et alterner
                    const currentDirection = this.dataset.sortDirection || 'desc';
                    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                    this.dataset.sortDirection = newDirection;

                    // Retirer les indicateurs de tri sur les autres colonnes
                    scope.querySelectorAll('.table-sort').forEach((other) => {
                        if (other !== this) {
                            delete other.dataset.sortDirection;
                            other.classList.remove('sorted-asc', 'sorted-desc');
                        }
                    });

                    // Ajouter classe CSS pour indiquer le tri actif
                    this.classList.remove('sorted-asc', 'sorted-desc');
                    this.classList.add(`sorted-${newDirection}`);

                    // Construire l'URL avec les paramètres de tri
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('sort', column);
                    urlParams.set('order', newDirection);

                    // Garder la page actuelle si elle existe
                    if (!urlParams.has('page')) {
                        urlParams.set('page', '1');
                    }

                    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;

                    debugLog('🔀 Tri par colonne:', column, '→', newDirection);
                    debugLog('📍 URL:', newUrl);

                    // Faire l'appel AJAX pour récupérer les résultats triés
                    window.fetchResultsGlobal(newUrl, { pushState: true });
                }, { once: false });
            });
        }

        function formatStatusLabel(status) {
            if (!status) {
                return '';
            }
            const normalized = status.replace(/_/g, ' ');
            const classes = {
                'active': 'bg-success',
                'en attente': 'bg-warning text-dark',
                'en_attente': 'bg-warning text-dark',
                'annulée': 'bg-danger',
                'terminée': 'bg-secondary',
            };
            const key = status.toLowerCase();
            const badgeClass = classes[key] || 'bg-primary';
            return `<span class="badge ${badgeClass} text-uppercase">${normalized}</span>`;
        }

        function attachAccordionListeners(container) {
            if (!container) {
                return;
            }
            function loadIframeWithLoader(iframe) {
                if (!iframe || iframe.src) return;
                const loader = iframe.closest('.modal-iframe-wrapper')?.querySelector('.inscription-loader');
                if (loader) loader.classList.remove('hidden');
                const separator = iframe.dataset.src.includes('?') ? '&' : '?';
                iframe.src = `${iframe.dataset.src}${separator}_=${Date.now()}`;
                iframe.addEventListener('load', function h() {
                    if (loader) loader.classList.add('hidden');
                    iframe.removeEventListener('load', h);
                });
            }

            container.querySelectorAll('.accordion-collapse').forEach((collapseEl) => {
                collapseEl.addEventListener('show.bs.collapse', function () {
                    loadIframeWithLoader(this.querySelector('iframe[data-src]'));
                }, { once: true });
            });

            const firstVisible = container.querySelector('.accordion-collapse.show');
            if (firstVisible) {
                const iframe = firstVisible.querySelector('iframe[data-src]');
                if (iframe && !iframe.src) {
                    loadIframeWithLoader(iframe);
                }
            }
        }

        function formatWorkflowStepBadge(workflowStep) {
            if (!workflowStep) {
                return '';
            }

            const workflowSteps = {
                'prospect': { label: 'Prospect', class: 'bg-secondary', icon: 'fa-user-plus' },
                'documents_complets': { label: 'Documents complets', class: 'bg-info', icon: 'fa-file-check' },
                'en_validation': { label: 'En validation', class: 'bg-warning', icon: 'fa-hourglass-half' },
                'valide': { label: 'Validé', class: 'bg-success', icon: 'fa-check' },
                'etudiant_cree': { label: 'Étudiant créé', class: 'bg-primary', icon: 'fa-graduation-cap' }
            };

            const step = workflowSteps[workflowStep];
            if (step) {
                return `<span class="badge ${step.class} ms-2"><i class="fas ${step.icon} me-1"></i>${step.label}</span>`;
            }

            return `<span class="badge bg-light text-dark ms-2">${workflowStep}</span>`;
        }

        function renderInscriptionsAccordion(payload) {
            if (!inscriptionsContainer) {
                return;
            }

            const inscriptions = payload?.inscriptions ?? [];
            if (!inscriptions.length) {
                inscriptionsContainer.innerHTML = '<div class="alert alert-info mb-0">Aucune inscription disponible pour cet étudiant.</div>';
                return;
            }

            const accordionId = 'inscriptionsAccordion';
            const items = inscriptions.map((inscription, index) => {
                const collapseId = `inscription-collapse-${inscription.id}`;
                const headingId = `inscription-heading-${inscription.id}`;
                const affectation = inscription.affectation_status ? `<span class="badge bg-secondary ms-2 text-uppercase">${inscription.affectation_status}</span>` : '';
                const statusBadge = formatStatusLabel(inscription.status);
                const typeBadge = inscription.type ? `<span class="badge bg-info text-dark text-uppercase ms-2">${inscription.type}</span>` : '';
                const currentYearBadge = inscription.is_current_year ? `<span class="badge bg-primary text-white ms-2">Année courante</span>` : '';
                const dateChip = inscription.date_label ? `<span class="badge bg-light text-dark border ms-2"><i class="far fa-calendar-alt me-1"></i>${inscription.date_label}</span>` : '';
                const workflowBadge = formatWorkflowStepBadge(inscription.workflow_step);

                return `
<div class="accordion-item mb-2">
    <h2 class="accordion-header" id="${headingId}">
        <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${index === 0}" aria-controls="${collapseId}">
            <div class="d-flex flex-column flex-md-row w-100 justify-content-between">
                <div>
                    <strong>${inscription.annee}</strong> ${currentYearBadge} — ${inscription.classe}
                    ${dateChip}
                </div>
                <div>
                    ${statusBadge || ''}
                    ${workflowBadge}
                    ${affectation}
                    ${typeBadge}
                </div>
            </div>
        </button>
    </h2>
    <div id="${collapseId}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#${accordionId}">
        <div class="accordion-body">
            <div class="mb-3 row g-3 text-muted small">
                ${inscription.filiere ? `<div class=\"col-md-4\"><i class=\"fas fa-book me-2 text-primary\"></i>${inscription.filiere}</div>` : ''}
                ${inscription.niveau ? `<div class=\"col-md-4\"><i class=\"fas fa-layer-group me-2 text-primary\"></i>${inscription.niveau}</div>` : ''}
                ${inscription.affectation_status ? `<div class=\"col-md-4\"><i class=\"fas fa-map-marker-alt me-2 text-primary\"></i>${inscription.affectation_status}</div>` : ''}
            </div>
            <div class="mb-3">
                <a href="/esbtp/inscriptions/${inscription.id}" target="_blank" class="btn btn-info btn-sm">
                    <i class="fas fa-eye me-1"></i>Voir l'inscription
                </a>
            </div>
            <div class="modal-iframe-wrapper" style="position:relative;">
                <div class="iframe-loader inscription-loader">
                    <div class="spinner-border text-primary" role="status" style="width:1.5rem;height:1.5rem;"></div>
                    <span style="font-size:0.8rem;color:#64748b;margin-top:0.4rem;">Chargement...</span>
                </div>
                <iframe class="border-0 inscription-frame" data-src="${inscription.edit_url}" title="Inscription #${inscription.id}" loading="eager"></iframe>
            </div>
        </div>
    </div>
</div>`;
            }).join('');

            inscriptionsContainer.innerHTML = `<div class="accordion accordion-modern" id="${accordionId}">${items}</div>`;
            attachAccordionListeners(inscriptionsContainer);
        }

        function openEditModal(datasetString) {
            if (!modalElement || !datasetString) {
                return;
            }
            if (!editModal) {
                editModal = new bootstrap.Modal(modalElement);
                modalElement.addEventListener('hidden.bs.modal', () => {
                    if (studentFrame) {
                        studentFrame.src = 'about:blank';
                        if (studentEditLoader) { studentEditLoader.classList.remove('hidden'); }
                    }
                    if (inscriptionsContainer) {
                        inscriptionsContainer.innerHTML = '<div class="text-muted">Sélectionnez un étudiant pour afficher ses inscriptions.</div>';
                    }
                });
            }

            let payload;
            try {
                payload = JSON.parse(datasetString);
            } catch (error) {
                debugError('Impossible de parser les données de l\'étudiant', error);
                return;
            }

            const modalTitle = document.getElementById('etudiantEditModalLabel');
            if (modalTitle) {
                const identifiant = payload.matricule ? ` (#${payload.matricule})` : '';
                modalTitle.textContent = `Modifier ${payload.name ?? 'l\'étudiant'}${identifiant}`;
            }

            if (studentFrame && payload.edit_url) {
                if (studentEditLoader) { studentEditLoader.classList.remove('hidden'); }
                const separator = payload.edit_url.includes('?') ? '&' : '?';
                studentFrame.src = `${payload.edit_url}${separator}_=${Date.now()}`;
                studentFrame.addEventListener('load', function handleLoad() {
                    if (studentEditLoader) { studentEditLoader.classList.add('hidden'); }
                    studentFrame.removeEventListener('load', handleLoad);
                });
            }

            // Charger TOUTES les inscriptions via AJAX (pas seulement l'année courante)
            fetch(`/esbtp/etudiants/${payload.id}/all-inscriptions`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.inscriptions) {
                    // Remplacer les inscriptions dans le payload avec toutes les inscriptions
                    payload.inscriptions = data.inscriptions;
                }
                renderInscriptionsAccordion(payload);
            })
            .catch(error => {
                debugError('Erreur chargement inscriptions:', error);
                // Afficher quand même avec les inscriptions par défaut (année courante)
                renderInscriptionsAccordion(payload);
            });

            const studentTab = document.getElementById('tab-etudiant-link');
            if (studentTab) {
                const tabInstance = bootstrap.Tab.getOrCreateInstance(studentTab);
                tabInstance.show();
            }
            editModal.show();
        }

        if (resultsContainer) {
            resultsContainer.addEventListener('click', function (event) {
                const trigger = event.target.closest('.btn-open-edit-modal');
                if (!trigger) {
                    return;
                }
                event.preventDefault();
                openEditModal(trigger.getAttribute('data-student'));
            });
        }

        function fetchResults(url, options = {}) {
            if (!url) {
                return Promise.resolve();
            }

            setLoading(true);

            return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des étudiants.');
                }
                return response.json();
            })
            .then(data => {
                resultsContainer.innerHTML = data.html;
                updateStudentCountBadge();
                if (options.pushState !== false) {
                    window.history.pushState({ url: data.url }, '', data.url);
                }
                bindPagination();
                initTableSorting(resultsContainer);

                // Mettre à jour l'indicateur APRÈS pushState
                updateActiveFiltersIndicator();
            })
            .catch(error => {
                debugError(error);
                alert('Impossible de charger les étudiants. Veuillez réessayer.');
            })
            .finally(() => setLoading(false));
        }

        // Exposer fetchResults globalement pour le drawer mobile
        window.fetchResultsGlobal = fetchResults;

        // ========================================
        // INDICATEUR FILTRES ACTIFS
        // ========================================

        // Mapping des classes (ID → Label) pour l'indicateur de filtres
        const classesMapping = {
            @foreach($classes as $classeOption)
            '{{ $classeOption->id }}': '{{ $classeOption->name }}@if($classeOption->filiere || $classeOption->niveauEtude) ({{ $classeOption->filiere->name ?? "Filière N/A" }} - {{ $classeOption->niveauEtude->name ?? "Niveau N/A" }})@endif',
            @endforeach
        };

        function updateActiveFiltersIndicator() {
            const container = document.getElementById('active-filters-container');
            if (!container) return;

            // Récupérer les paramètres de l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeFilters = [];

            // Mapping des paramètres vers des labels lisibles
            const filterLabels = {
                'search': 'Recherche',
                'filiere': 'Filière',
                'niveau': 'Niveau',
                'classe': 'Classe',
                'annee': 'Année universitaire',
                'statut': 'Statut',
                'affectation_status': 'Statut affectation',
                'inscrit_annee_courante': 'Inscription validée',
                'est_transfert': 'Transfert',
                'accessibility': 'Accessibilité'
            };

            // Récupérer les options de select pour avoir les labels
            const getSelectLabel = (name, value) => {
                // Pour le champ recherche, retourner la valeur directement
                if (name === 'search') {
                    return value;
                }

                // Pour la classe (searchable select Alpine.js)
                if (name === 'classe') {
                    // Utiliser le mapping créé depuis les data Laravel
                    return classesMapping[value] || value;
                }

                // Pour les autres selects standards
                const select = document.querySelector(`select[name="${name}"], #mobile-${name}`);
                if (select) {
                    const option = select.querySelector(`option[value="${value}"]`);
                    return option ? option.textContent.trim() : value;
                }

                return value;
            };

            // Parcourir les paramètres
            for (const [key, value] of urlParams) {
                if (value && filterLabels[key]) {
                    activeFilters.push({
                        key: key,
                        label: filterLabels[key],
                        value: value,
                        displayValue: getSelectLabel(key, value)
                    });
                }
            }

            // Afficher ou masquer le conteneur
            if (activeFilters.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'flex';

            // Générer le HTML
            let html = `
                <div class="active-filters-label">
                    <i class="fas fa-filter"></i>
                    <span>Filtres actifs :</span>
                </div>
            `;

            // Ajouter chaque filtre
            activeFilters.forEach(filter => {
                html += `
                    <div class="filter-tag" data-filter-key="${filter.key}">
                        <span class="filter-tag-label">${filter.label}:</span>
                        <span class="filter-tag-value">${filter.displayValue}</span>
                        <button class="filter-tag-remove" data-filter-key="${filter.key}" title="Supprimer ce filtre">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });

            // Bouton tout effacer
            html += `
                <button class="clear-all-filters" id="clear-all-filters-btn">
                    <i class="fas fa-times-circle"></i>
                    <span>Tout effacer</span>
                </button>
            `;

            container.innerHTML = html;

            // Attacher les event listeners
            container.querySelectorAll('.filter-tag-remove').forEach(btn => {
                btn.addEventListener('click', function() {
                    const key = this.getAttribute('data-filter-key');
                    removeFilter(key);
                });
            });

            const clearAllBtn = container.querySelector('#clear-all-filters-btn');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function() {
                    clearAllFilters();
                });
            }
        }

        // Fonction pour reset un select spécifique (desktop + mobile + Alpine.js)
        function resetSelectByName(name) {
            debugLog('🔄 Reset select:', name);

            // Reset select desktop standard
            const desktopSelect = document.querySelector(`select[name="${name}"]`);
            if (desktopSelect) {
                desktopSelect.value = '';
                debugLog('  ✅ Desktop select reset');
            }

            // Reset select mobile standard
            const mobileSelect = document.querySelector(`#mobile-${name}`);
            if (mobileSelect) {
                mobileSelect.value = '';
                debugLog('  ✅ Mobile select reset');
            }

            // Reset input recherche si c'est le champ search
            if (name === 'search') {
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.value = '';
                    debugLog('  ✅ Search input reset');
                }
                const mobileSearchInput = document.querySelector('#mobile-search');
                if (mobileSearchInput) {
                    mobileSearchInput.value = '';
                    debugLog('  ✅ Mobile search input reset');
                }
            }

            // Reset composant Alpine.js (classe searchable select)
            if (name === 'classe') {
                // Dispatcher un event custom pour reset le composant Alpine
                window.dispatchEvent(new CustomEvent('reset-searchable-select', {
                    detail: { name: 'classe' }
                }));
                debugLog('  ✅ Alpine.js classe component reset event dispatched');
            }
        }

        // Fonction pour reset TOUS les selects
        function resetAllSelects() {
            debugLog('🔄 Reset ALL selects');

            // Reset formulaire desktop
            if (form) {
                form.reset();
                debugLog('  ✅ Desktop form reset');
            }

            // Reset formulaire mobile
            const mobileForm = document.getElementById('mobile-search-form');
            if (mobileForm) {
                mobileForm.reset();
                debugLog('  ✅ Mobile form reset');
            }

            // Reset tous les composants Alpine.js
            window.dispatchEvent(new CustomEvent('reset-all-searchable-selects'));
            debugLog('  ✅ Alpine.js reset event dispatched');
        }

        function removeFilter(key) {
            debugLog('🗑️ Suppression du filtre:', key);
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete(key);

            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;

            // Faire l'appel AJAX puis reset le select correspondant
            window.fetchResultsGlobal(newUrl, { pushState: true }).then(() => {
                // Reset le select correspondant après l'AJAX
                resetSelectByName(key);
            });
        }

        function clearAllFilters() {
            debugLog('🗑️ Suppression de tous les filtres');

            // Faire l'appel AJAX puis reset tous les selects
            window.fetchResultsGlobal(window.location.pathname, { pushState: true }).then(() => {
                // Reset TOUS les selects après l'AJAX
                resetAllSelects();
            });
        }

        // Mettre à jour l'indicateur au chargement initial
        updateActiveFiltersIndicator();

        // Bouton "Réinitialiser" desktop
        const desktopResetBtn = document.getElementById('desktop-reset-btn');
        if (desktopResetBtn) {
            desktopResetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                debugLog('🔄 Réinitialisation des filtres (desktop)');
                clearAllFilters();
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;
            // Utiliser window.fetchResultsGlobal pour déclencher l'update automatique
            window.fetchResultsGlobal(targetUrl, { pushState: true });
            return false;
        });

        filterInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!form) {
                    return;
                }
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                const targetUrl = `${form.action}?${params.toString()}`;
                // Utiliser window.fetchResultsGlobal pour déclencher l'update automatique
                window.fetchResultsGlobal(targetUrl, { pushState: true });
            });
        });

        // Relai AJAX pour les pickers LMD premium (au-mention-picker / au-parcours-picker).
        // Ces composants exposent des <input type="hidden" name="mention" / "parcours">
        // qui ne sont PAS captés par filterInputs (qui ne sélectionne que les <select>).
        // Custom event 'mention:changed' (cf composant) + input event natif sur hidden.
        const triggerLmdRefresh = () => {
            if (!form) return;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;
            window.fetchResultsGlobal(targetUrl, { pushState: true });
        };
        window.addEventListener('mention:changed', triggerLmdRefresh);
        document.addEventListener('input', (ev) => {
            if (ev.target && (ev.target.name === 'mention' || ev.target.name === 'parcours')) {
                triggerLmdRefresh();
            }
        });

        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        window.addEventListener('popstate', function (event) {
            const targetUrl = event.state?.url || window.location.href;
            // Utiliser window.fetchResultsGlobal pour déclencher l'update automatique
            window.fetchResultsGlobal(targetUrl, { pushState: false });
        });

        bindPagination();
        initTableSorting(resultsContainer);

    });

    // ========================================
    // EXPORT MODAL - Alpine Component
    // ========================================
    function exportModal() {
        return {
            exportGroupBy: '',
            classSearch: '',

            // Data arrays
            allFilieres: @php
                $exportFilieres = $filieres->map(function($f) { return ['id' => $f->id, 'name' => $f->name, 'code' => $f->code]; })->values();
            @endphp {!! json_encode($exportFilieres) !!},
            allNiveaux: @php
                $exportNiveaux = $niveaux->map(function($n) { return ['id' => $n->id, 'name' => $n->name, 'code' => $n->code]; })->values();
            @endphp {!! json_encode($exportNiveaux) !!},
            allClasses: @php
                $exportClasses = $classes->map(function($c) { return ['id' => $c->id, 'name' => $c->name, 'filiere_id' => $c->filiere_id, 'niveau_etude_id' => $c->niveau_etude_id]; })->values();
            @endphp {!! json_encode($exportClasses) !!},

            // Selected combinations: array of {filiere_id, niveau_id} pairs
            selectedCombinations: [],
            // Individual class selection (IDs)
            selectedClassIds: [],

            init() {
                // Start with all valid combinations selected
                var self = this;
                this.allClasses.forEach(function(c) {
                    if (c.filiere_id && c.niveau_etude_id) {
                        var exists = self.selectedCombinations.some(function(combo) {
                            return combo.filiere_id === c.filiere_id && combo.niveau_id === c.niveau_etude_id;
                        });
                        if (!exists) {
                            self.selectedCombinations.push({ filiere_id: c.filiere_id, niveau_id: c.niveau_etude_id });
                        }
                        // All classes selected by default
                        self.selectedClassIds.push(c.id);
                    }
                });
            },

            // Is a (filière, niveau) combination selected?
            isCombinationSelected(filiereId, niveauId) {
                return this.selectedCombinations.some(function(c) {
                    return c.filiere_id === filiereId && c.niveau_id === niveauId;
                });
            },

            // Toggle a specific (filière, niveau) combination + sync classes
            toggleCombination(filiereId, niveauId) {
                var self = this;
                var comboClassIds = this.allClasses
                    .filter(function(c) { return c.filiere_id === filiereId && c.niveau_etude_id === niveauId; })
                    .map(function(c) { return c.id; });

                var idx = -1;
                for (var i = 0; i < this.selectedCombinations.length; i++) {
                    if (this.selectedCombinations[i].filiere_id === filiereId && this.selectedCombinations[i].niveau_id === niveauId) {
                        idx = i;
                        break;
                    }
                }
                if (idx > -1) {
                    // Uncheck combo → remove its classes
                    this.selectedCombinations.splice(idx, 1);
                    this.selectedClassIds = this.selectedClassIds.filter(function(id) {
                        return comboClassIds.indexOf(id) === -1;
                    });
                } else {
                    // Check combo → add its classes
                    this.selectedCombinations.push({ filiere_id: filiereId, niveau_id: niveauId });
                    comboClassIds.forEach(function(id) {
                        if (self.selectedClassIds.indexOf(id) === -1) {
                            self.selectedClassIds.push(id);
                        }
                    });
                }
            },

            // Does a filière have at least one selected combination?
            hasFiliereSelection(filiereId) {
                return this.selectedCombinations.some(function(c) { return c.filiere_id === filiereId; });
            },

            // Are ALL niveaux of a filière selected?
            isFiliereFullySelected(filiereId) {
                var self = this;
                // Get niveaux that have classes in this filière
                var niveauIds = [];
                this.allClasses.forEach(function(c) {
                    if (c.filiere_id === filiereId && c.niveau_etude_id && niveauIds.indexOf(c.niveau_etude_id) === -1) {
                        niveauIds.push(c.niveau_etude_id);
                    }
                });
                if (niveauIds.length === 0) return false;
                return niveauIds.every(function(nId) { return self.isCombinationSelected(filiereId, nId); });
            },

            // Toggle all niveaux of a filière + sync classes
            toggleAllNiveauxOfFiliere(filiereId) {
                var self = this;
                var niveauIds = [];
                var filiereClassIds = [];
                this.allClasses.forEach(function(c) {
                    if (c.filiere_id === filiereId && c.niveau_etude_id) {
                        if (niveauIds.indexOf(c.niveau_etude_id) === -1) {
                            niveauIds.push(c.niveau_etude_id);
                        }
                        filiereClassIds.push(c.id);
                    }
                });

                if (this.isFiliereFullySelected(filiereId)) {
                    // Remove all combos + classes of this filière
                    this.selectedCombinations = this.selectedCombinations.filter(function(c) {
                        return c.filiere_id !== filiereId;
                    });
                    this.selectedClassIds = this.selectedClassIds.filter(function(id) {
                        return filiereClassIds.indexOf(id) === -1;
                    });
                } else {
                    // Add missing combos + classes
                    niveauIds.forEach(function(nId) {
                        if (!self.isCombinationSelected(filiereId, nId)) {
                            self.selectedCombinations.push({ filiere_id: filiereId, niveau_id: nId });
                        }
                    });
                    filiereClassIds.forEach(function(id) {
                        if (self.selectedClassIds.indexOf(id) === -1) {
                            self.selectedClassIds.push(id);
                        }
                    });
                }
            },

            // Classes matching selected combos (visible in the class list)
            get comboClasses() {
                var self = this;
                return this.allClasses.filter(function(c) {
                    return self.selectedCombinations.some(function(combo) {
                        return combo.filiere_id === c.filiere_id && combo.niveau_id === c.niveau_etude_id;
                    });
                });
            },

            // Resolved classes = only checked ones (for export)
            get resolvedClasses() {
                var self = this;
                return this.comboClasses.filter(function(c) {
                    return self.selectedClassIds.indexOf(c.id) !== -1;
                });
            },

            // Individual class toggle
            isClassSelected(classId) {
                return this.selectedClassIds.indexOf(classId) !== -1;
            },

            toggleClass(classId) {
                var idx = this.selectedClassIds.indexOf(classId);
                if (idx > -1) {
                    this.selectedClassIds.splice(idx, 1);
                } else {
                    this.selectedClassIds.push(classId);
                }
                // Sync combo: if no classes left for a combo, uncheck the combo
                this.syncCombosFromClasses();
            },

            toggleAllClasses() {
                var self = this;
                var comboIds = this.comboClasses.map(function(c) { return c.id; });
                var allChecked = comboIds.every(function(id) { return self.selectedClassIds.indexOf(id) !== -1; });

                if (allChecked) {
                    // Uncheck all visible classes
                    this.selectedClassIds = this.selectedClassIds.filter(function(id) {
                        return comboIds.indexOf(id) === -1;
                    });
                } else {
                    // Check all visible classes
                    comboIds.forEach(function(id) {
                        if (self.selectedClassIds.indexOf(id) === -1) {
                            self.selectedClassIds.push(id);
                        }
                    });
                }
            },

            // Sync: if all classes of a combo are unchecked, remove the combo
            syncCombosFromClasses() {
                var self = this;
                this.selectedCombinations = this.selectedCombinations.filter(function(combo) {
                    var comboClassIds = self.allClasses
                        .filter(function(c) { return c.filiere_id === combo.filiere_id && c.niveau_etude_id === combo.niveau_id; })
                        .map(function(c) { return c.id; });
                    // Keep combo if at least one class is still selected
                    return comboClassIds.some(function(id) { return self.selectedClassIds.indexOf(id) !== -1; });
                });
            },

            // Global toggle states
            get allSelected() {
                return this.allClasses.length > 0 && this.selectedClassIds.length === this.allClasses.length;
            },
            get someSelected() { return this.selectedClassIds.length > 0; },

            toggleAll() {
                var self = this;
                if (this.allSelected) {
                    this.selectedCombinations = [];
                    this.selectedClassIds = [];
                } else {
                    var combos = [];
                    var classIds = [];
                    this.allClasses.forEach(function(c) {
                        if (c.filiere_id && c.niveau_etude_id) {
                            var exists = combos.some(function(x) { return x.filiere_id === c.filiere_id && x.niveau_id === c.niveau_etude_id; });
                            if (!exists) combos.push({ filiere_id: c.filiere_id, niveau_id: c.niveau_etude_id });
                            classIds.push(c.id);
                        }
                    });
                    this.selectedCombinations = combos;
                    this.selectedClassIds = classIds;
                }
            },

            // Export action
            doExport(format) {
                var params = new URLSearchParams();

                var urlParams = new URLSearchParams(window.location.search);
                ['search', 'annee', 'status', 'affectation_status', 'inscrit_annee_courante', 'est_transfert'].forEach(function(key) {
                    if (urlParams.has(key) && urlParams.get(key)) {
                        params.set(key, urlParams.get(key));
                    }
                });

                var form = document.getElementById('search-form');
                if (form) {
                    var formData = new FormData(form);
                    ['search', 'annee', 'status', 'affectation_status', 'inscrit_annee_courante', 'est_transfert'].forEach(function(key) {
                        var val = formData.get(key);
                        if (val && !params.has(key)) {
                            params.set(key, val);
                        }
                    });
                }

                // Send selected class IDs
                if (this.selectedClassIds.length > 0 && this.selectedClassIds.length < this.allClasses.length) {
                    this.selectedClassIds.forEach(function(id) { params.append('classes[]', id); });
                }

                if (this.exportGroupBy) {
                    params.set('group_by', this.exportGroupBy);
                }

                var baseUrl = format === 'excel'
                    ? '{{ route("esbtp.etudiants.export.excel") }}'
                    : '{{ route("esbtp.etudiants.export.pdf") }}';

                var modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                if (modal) modal.hide();

                window.location.href = baseUrl + '?' + params.toString();
            }
        };
    }
</script>
@endpush
