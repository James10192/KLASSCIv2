@extends('layouts.app')

@section('title', 'Gestion des étudiants - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .modal-modern .modal-dialog {
        width: clamp(1024px, 80vw, 1800px);
        max-width: 80vw;
        height: 80vh;
        max-height: 80vh;
        position: relative;
        margin: 10vh auto;
    }

    .modal-modern .modal-content {
        border-radius: 24px;
        border: none;
        box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
        background: linear-gradient(135deg, #fdfdfd 0%, #f3f4f6 35%, #ffffff 100%);
        height: 100%;
        display: flex;
        flex-direction: column;
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
        transition: min-height 0.3s ease;
    }

    .modal-modern .modal-dialog::after {
        content: '';
        position: absolute;
        top: -20px;
        right: -20px;
        width: 120px;
        height: 120px;
        background: radial-gradient(circle, rgba(255,255,255,0.45), rgba(99,102,241,0.08));
        filter: blur(20px);
        z-index: -1;
    }

    @media (max-width: 1400px) {
        .modal-modern .modal-dialog {
            width: 85vw;
            max-width: 85vw;
        }
    }

    @media (max-width: 1200px) {
        .modal-modern .modal-dialog {
            width: 90vw;
            max-width: 90vw;
            height: 85vh;
            max-height: 85vh;
            margin: 7.5vh auto;
        }
    }

    @media (max-width: 992px) {
        .modal-modern .modal-dialog {
            width: 95vw;
            max-width: 95vw;
            height: 90vh;
            max-height: 90vh;
            margin: 5vh auto;
        }

        .accordion-modern .accordion-body .modal-iframe-wrapper {
            min-height: 400px;
            height: 50vh;
        }

        .category-card {
            padding: 16px;
        }

        /* Filtres en colonne complète sur tablette */
        #search-form .row > [class*='col-'] {
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }

        #search-form .row {
            row-gap: 1rem;
        }

        /* Header responsive */
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
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
        }

        /* Boutons filtres responsive */
        .col-md-4.d-flex.align-items-end {
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .col-md-4.d-flex.align-items-end .btn-acasi {
            width: 100%;
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
            padding: 1.5rem !important;
        }

        /* Tabs padding */
        .student-tabs-container .nav-link {
            padding: 12px 16px !important;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 768px) {
        /* Header encore plus compact */
        .dashboard-header .header-left h1 {
            font-size: 1.5rem;
        }

        /* Section titles plus petits */
        .section-title {
            font-size: 1rem;
        }

        /* Form labels plus petits */
        #search-form label.form-label {
            font-size: 0.875rem;
            margin-bottom: 0.375rem;
        }

        /* Inputs et selects avec padding réduit */
        #search-form .form-control,
        #search-form .form-select,
        #search-form .searchable-select-trigger {
            font-size: 0.875rem;
            padding: 8px 12px;
            min-height: 38px;
        }

        /* Boutons filtres avec texte plus petit */
        #search-form .btn-acasi {
            font-size: 0.875rem;
            padding: 8px 16px;
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
        /* Header très compact */
        .dashboard-header .header-left h1 {
            font-size: 1.25rem;
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
            justify-content: center;
        }

        /* Card padding minimal */
        .card-moderne .p-lg {
            padding: 1rem !important;
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

        /* Section title très compact */
        .section-title {
            font-size: 0.9rem;
            margin-bottom: 1rem !important;
        }

        /* Labels très compacts */
        #search-form label.form-label {
            font-size: 0.8rem;
        }

        /* Inputs très compacts */
        #search-form .form-control,
        #search-form .form-select,
        #search-form .searchable-select-trigger {
            font-size: 0.8rem;
            padding: 6px 10px;
            min-height: 36px;
        }

        /* Searchable select icon plus petit */
        .searchable-select-icon {
            font-size: 0.8rem;
            right: 10px;
        }

        /* Dropdown searchable select */
        .searchable-select-dropdown {
            max-height: 60vh;
        }

        .searchable-select-search input {
            font-size: 0.85rem;
            padding: 8px 12px;
        }

        .searchable-select-option {
            padding: 10px 12px;
            font-size: 0.85rem;
        }

        /* Boutons filtres compacts */
        #search-form .btn-acasi {
            font-size: 0.8rem;
            padding: 8px 14px;
        }

        #search-form .btn-acasi i {
            font-size: 0.75rem;
        }
    }

    /* Très petits écrans (moins de 400px) */
    @media (max-width: 400px) {
        .dashboard-header .header-left h1 {
            font-size: 1.1rem;
        }

        .card-moderne .p-lg {
            padding: 0.75rem !important;
        }

        .section-title {
            font-size: 0.85rem;
        }

        #search-form .form-control,
        #search-form .form-select,
        #search-form .searchable-select-trigger {
            font-size: 0.75rem;
            padding: 6px 8px;
            min-height: 34px;
        }

        #search-form .btn-acasi {
            font-size: 0.75rem;
            padding: 6px 12px;
        }

        .modal-modern .modal-title {
            font-size: 1rem;
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
                <a href="{{ route('esbtp.inscriptions.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Ajouter un étudiant
                </a>
                @if(auth()->user()->hasRole(['superAdmin', 'secretaire', 'coordinateur']))
                <a href="{{ route('esbtp.reinscription.index') }}" class="btn-acasi success">
                    <i class="fas fa-user-graduate"></i>Réinscriptions
                </a>
                @endif
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


                <!-- Filtres de recherche -->
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres de recherche
                </div>
                            <form method="GET" action="{{ route('esbtp.etudiants.index') }}" id="search-form">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="search" class="form-label">Recherche</label>
                                        <input type="text" class="form-control search-bar" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="filiere" class="form-label">Filière</label>
                                        <select class="form-select year-selector" id="filiere" name="filiere">
                                            <option value="">Toutes les filières</option>
                                            @foreach($filieres as $f)
                                                <option value="{{ $f->id }}" {{ isset($filiere) && $filiere == $f->id ? 'selected' : '' }}>
                                                    {{ $f->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="niveau" class="form-label">Niveau d'études</label>
                                        <select class="form-select year-selector" id="niveau" name="niveau">
                                            <option value="">Tous les niveaux</option>
                                            @foreach($niveaux as $n)
                                                <option value="{{ $n->id }}" {{ isset($niveau) && $niveau == $n->id ? 'selected' : '' }}>
                                                    {{ $n->name }} ({{ $n->type }} - Année {{ $n->year }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="classe" class="form-label">Classe</label>
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
                                    <div class="col-md-4 d-flex align-items-end mb-3">
                                        <button type="submit" class="btn-acasi primary me-2">
                                            <i class="fas fa-search"></i>Filtrer
                                        </button>
                                        <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                                            <i class="fas fa-redo-alt"></i>Réinitialiser
                                        </a>
                                    </div>
                                </div>
                            </form>
            </div>
        </div>

        <!-- Tableau des étudiants -->
        <div class="card-moderne">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-list"></i>Liste des étudiants
                </div>
                <div id="etudiants-results">
                    @include('esbtp.etudiants.partials.results', ['etudiants' => $etudiants])
</div>
</div>
</div>
</div>
</div>

<!-- Modal d'édition rapide -->
<div class="modal fade modal-modern" id="etudiantEditModal" tabindex="-1" aria-labelledby="etudiantEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-uppercase text-muted small mb-1">Edition rapide</p>
                    <h5 class="modal-title" id="etudiantEditModalLabel">Modifier l'étudiant</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="student-tabs-container">
                    <ul class="nav nav-tabs" id="editStudentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-etudiant-link" data-bs-toggle="tab" data-bs-target="#tab-etudiant" type="button" role="tab">
                                <span class="tab-label">
                                    <i class="fas fa-user-edit"></i>
                                    Étudiant
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-inscriptions-link" data-bs-toggle="tab" data-bs-target="#tab-inscriptions" type="button" role="tab">
                                <span class="tab-label">
                                    <i class="fas fa-graduation-cap"></i>
                                    Inscriptions
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content modern-tab-content" id="editStudentTabContent">
                    <div class="tab-pane fade show active" id="tab-etudiant" role="tabpanel">
                        <div class="modal-iframe-wrapper">
                            <iframe id="student-edit-frame" src="about:blank" title="Édition étudiant" loading="lazy" class="border-0"></iframe>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-inscriptions" role="tabpanel">
                        <div id="inscriptions-accordion-container" class="accordion-modern text-muted w-100">
                            Sélectionnez un étudiant pour afficher ses inscriptions.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Alpine.js Searchable Select Component - Défini globalement
    window.searchableSelect = function(config) {
        console.log('🔧 Initialisation searchableSelect avec config:', config);
        return {
            options: config.options || [],
            filteredOptions: [],
            search: '',
            open: false,
            selectedValue: config.selected || '',
            selectedLabel: '',
            placeholder: config.placeholder || 'Sélectionner...',

            init() {
                console.log('✅ searchableSelect init() appelé');
                console.log('📊 Nombre d\'options:', this.options.length);
                this.filteredOptions = this.options;
                this.updateSelectedLabel();
                console.log('🏷️ Label sélectionné:', this.selectedLabel);

                // Watch for open changes to focus search input
                this.$watch('open', value => {
                    console.log('👁️ Dropdown open:', value);
                    if (value) {
                        this.$nextTick(() => {
                            this.$refs.searchInput?.focus();
                        });
                    } else {
                        this.search = '';
                        this.filteredOptions = this.options;
                    }
                });
            },

            filterOptions() {
                const searchLower = this.search.toLowerCase();
                this.filteredOptions = this.options.filter(option =>
                    option.label.toLowerCase().includes(searchLower)
                );
                console.log('🔍 Filtrage:', this.search, '→', this.filteredOptions.length, 'résultats');
            },

            selectOption(option) {
                console.log('✅ Option sélectionnée:', option);
                this.selectedValue = option.value;
                this.selectedLabel = option.label;
                this.open = false;
                this.search = '';
                this.filteredOptions = this.options;

                // Trigger AJAX refresh instead of form submission
                this.$nextTick(() => {
                    if (typeof window.triggerFilterChange === 'function') {
                        console.log('📤 Déclenchement AJAX refresh...');
                        window.triggerFilterChange();
                    }
                });
            },

            updateSelectedLabel() {
                const selected = this.options.find(opt => opt.value === this.selectedValue);
                this.selectedLabel = selected ? selected.label : '';
                console.log('🔄 updateSelectedLabel - value:', this.selectedValue, 'label:', this.selectedLabel);
            }
        }
    }

    console.log('✅ Fonction searchableSelect définie globalement');

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('search-form');
        const resultsContainer = document.getElementById('etudiants-results');
        const submitButton = form.querySelector('button[type="submit"]');
        const filterInputs = form.querySelectorAll('select');
        const modalElement = document.getElementById('etudiantEditModal');
        const inscriptionsContainer = document.getElementById('inscriptions-accordion-container');
        const studentFrame = document.getElementById('student-edit-frame');
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
            console.log('🔄 triggerFilterChange appelée');
            const formData = new FormData(form);
            const params = new URLSearchParams();

            // Construire les paramètres depuis le formulaire
            for (const [key, value] of formData.entries()) {
                if (value) {  // Ignorer les valeurs vides
                    params.append(key, value);
                }
            }

            const url = form.action + '?' + params.toString();
            console.log('📍 URL AJAX:', url);
            fetchResults(url, { pushState: true });
        };

        function bindPagination() {
            resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchResults(this.href, { pushState: true });
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
                    const dataKey = 'sort' + column.charAt(0).toUpperCase() + column.slice(1);
                    const currentDirection = this.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
                    this.dataset.sortDirection = currentDirection;

                    scope.querySelectorAll('.table-sort').forEach((other) => {
                        if (other !== this) {
                            delete other.dataset.sortDirection;
                        }
                    });

                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    const multiplier = currentDirection === 'asc' ? 1 : -1;

                    rows.sort((a, b) => {
                        const rawA = a.dataset[dataKey] || '';
                        const rawB = b.dataset[dataKey] || '';
                        if (column === 'date') {
                            if (rawA === rawB) {
                                return 0;
                            }
                            return (rawA > rawB ? 1 : -1) * multiplier;
                        }

                        const aVal = rawA.toUpperCase();
                        const bVal = rawB.toUpperCase();
                        return aVal.localeCompare(bVal) * multiplier;
                    });

                    const tbody = table.querySelector('tbody');
                    rows.forEach((row) => tbody.appendChild(row));
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
            container.querySelectorAll('.accordion-collapse').forEach((collapseEl) => {
                collapseEl.addEventListener('show.bs.collapse', function () {
                    const iframe = this.querySelector('iframe[data-src]');
                    if (iframe && !iframe.src) {
                        const separator = iframe.dataset.src.includes('?') ? '&' : '?';
                        iframe.src = `${iframe.dataset.src}${separator}_=${Date.now()}`;
                    }
                }, { once: true });
            });

            const firstVisible = container.querySelector('.accordion-collapse.show');
            if (firstVisible) {
                const iframe = firstVisible.querySelector('iframe[data-src]');
                if (iframe && !iframe.src) {
                    const separator = iframe.dataset.src.includes('?') ? '&' : '?';
                    iframe.src = `${iframe.dataset.src}${separator}_=${Date.now()}`;
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
            <div class="modal-iframe-wrapper">
                <iframe class="border-0 inscription-frame" data-src="${inscription.edit_url}" title="Inscription #${inscription.id}" loading="lazy"></iframe>
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
                console.error('Impossible de parser les données de l\'étudiant', error);
                return;
            }

            const modalTitle = document.getElementById('etudiantEditModalLabel');
            if (modalTitle) {
                const identifiant = payload.matricule ? ` (#${payload.matricule})` : '';
                modalTitle.textContent = `Modifier ${payload.name ?? 'l\'étudiant'}${identifiant}`;
            }

            if (studentFrame && payload.edit_url) {
                studentFrame.classList.add('opacity-50');
                const separator = payload.edit_url.includes('?') ? '&' : '?';
                studentFrame.src = `${payload.edit_url}${separator}_=${Date.now()}`;
                studentFrame.addEventListener('load', function handleLoad() {
                    studentFrame.classList.remove('opacity-50');
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
                console.error('Erreur chargement inscriptions:', error);
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
                return;
            }

            setLoading(true);

            fetch(url, {
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
                if (options.pushState !== false) {
                    window.history.pushState({ url: data.url }, '', data.url);
                }
                bindPagination();
                initTableSorting(resultsContainer);
            })
            .catch(error => {
                console.error(error);
                alert('Impossible de charger les étudiants. Veuillez réessayer.');
            })
            .finally(() => setLoading(false));
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;
            fetchResults(targetUrl, { pushState: true });
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
                fetchResults(targetUrl, { pushState: true });
            });
        });

        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        window.addEventListener('popstate', function (event) {
            const targetUrl = event.state?.url || window.location.href;
            fetchResults(targetUrl, { pushState: false });
        });

        bindPagination();
        initTableSorting(resultsContainer);

    });
</script>
@endpush
