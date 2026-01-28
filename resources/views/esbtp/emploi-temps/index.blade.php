@extends('layouts.app')

@section('title', 'Emplois du temps - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour les emplois du temps */
    .emploi-temps-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .emploi-temps-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .emploi-stat-card {
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .emploi-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }
    
    .emploi-stat-card.primary::before { background: var(--primary); }
    .emploi-stat-card.success::before { background: var(--success); }
    .emploi-stat-card.info::before { background: var(--accent-blue); }
    .emploi-stat-card.warning::before { background: var(--warning); }
    
    .emploi-stat-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-sm);
        font-size: 20px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .emploi-stat-card.primary .emploi-stat-icon { color: var(--primary); }
    .emploi-stat-card.success .emploi-stat-icon { color: var(--success); }
    .emploi-stat-card.info .emploi-stat-icon { color: var(--accent-blue); }
    .emploi-stat-card.warning .emploi-stat-icon { color: var(--warning); }
    
    .emploi-stat-value {
        font-size: var(--amount-large);
        font-weight: bold;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }
    
    .emploi-stat-label {
        color: var(--text-secondary);
        font-size: var(--text-small);
        font-weight: 500;
    }
    
    .emploi-filter-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
    }
    
    .emploi-table-container {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: none;
    }
    
    .emploi-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
        margin-bottom: var(--space-md);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .emploi-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }
    
    .emploi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary);
    }
    
    .emploi-card.active::before {
        background: var(--success);
    }

    .emploi-card.expired::before {
        background: var(--danger);
    }

    .emploi-card.upcoming::before {
        background: var(--accent-blue);
    }

    .emploi-card.current-period::before {
        background: var(--warning);
    }

    .timetable-tips-btn {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.35);
        padding: 0.65rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .timetable-tips-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .tips-modal .modal-content {
        border-radius: 18px;
        border: none;
        overflow: hidden;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
    }

    .tips-modal .modal-header {
        background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%);
        color: #ffffff;
        border-bottom: none;
    }

    .tips-modal .modal-title {
        font-weight: 700;
    }

    .tips-modal .modal-body {
        background: #f8fafc;
    }

    .tips-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    .tips-step {
        background: #ffffff;
        border-radius: 18px;
        padding: 1.4rem;
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1);
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        position: relative;
        overflow: hidden;
    }

    .tips-step::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.06), rgba(255, 255, 255, 0));
        opacity: 0.6;
        pointer-events: none;
    }

    .tips-step-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        color: #0f172a;
        z-index: 1;
    }

    .tips-step-title span {
        background: rgba(4, 83, 203, 0.12);
        color: #1d4ed8;
        border-radius: 999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .tips-step img {
        width: 100%;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.12);
        height: 220px;
        object-fit: cover;
        object-position: top;
        z-index: 1;
    }

    @media (min-width: 1200px) {
        .tips-steps {
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        }

        .tips-step img {
            height: 260px;
        }
    }

    .tips-step-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        border-radius: 10px;
        background: rgba(4, 83, 203, 0.1);
        color: #1d4ed8;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid rgba(4, 83, 203, 0.2);
        transition: all 0.2s ease;
        z-index: 1;
        width: fit-content;
    }

    .tips-step-action:hover {
        background: rgba(4, 83, 203, 0.18);
        transform: translateY(-1px);
        color: #1d4ed8;
    }

    .tips-step p {
        color: #475569;
        font-size: 0.9rem;
        z-index: 1;
    }

    .tips-note {
        margin-top: 1rem;
        background: rgba(14, 165, 233, 0.12);
        border: 1px solid rgba(14, 165, 233, 0.25);
        padding: 0.9rem 1rem;
        border-radius: 12px;
        color: #0369a1;
        font-weight: 600;
    }

    .emploi-shortcut-card {
        border: 1px dashed rgba(245, 158, 11, 0.6);
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.02));
    }

    .emploi-shortcut-title {
        font-weight: 700;
        color: var(--warning);
        margin-bottom: var(--space-xs);
    }

    .emploi-shortcut-meta {
        color: var(--text-secondary);
        font-size: var(--text-small);
        margin-bottom: var(--space-sm);
    }

    .emploi-shortcut-stats {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
    }

    .emploi-shortcut-chip {
        background: rgba(245, 158, 11, 0.12);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .emploi-card-header {
        padding: var(--space-md);
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .emploi-card-title {
        font-weight: 600;
        font-size: var(--text-normal);
        color: var(--primary);
        margin: 0;
    }
    
    .emploi-card-body {
        padding: var(--space-md);
    }
    
    .emploi-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--space-sm);
        margin-bottom: var(--space-md);
    }

    .emploi-info-list {
        display: grid;
        gap: 8px;
        margin-bottom: var(--space-md);
    }

    .emploi-info-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .emploi-info-row i {
        color: var(--primary);
        font-size: 13px;
    }

    .emploi-info-key {
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-size: 11px;
    }

    .emploi-info-val {
        font-weight: 600;
        color: var(--text-primary);
    }

    .emploi-info-pills {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
        margin-bottom: var(--space-md);
    }

    .emploi-info-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid transparent;
        color: var(--text-primary);
        background: rgba(59, 130, 246, 0.08);
        border-color: rgba(59, 130, 246, 0.18);
    }

    .emploi-info-pill i {
        font-size: 12px;
        opacity: 0.8;
    }

    .emploi-info-pill.primary {
        background: rgba(37, 99, 235, 0.12);
        border-color: rgba(37, 99, 235, 0.22);
        color: #1d4ed8;
    }

    .emploi-info-pill.info {
        background: rgba(14, 165, 233, 0.12);
        border-color: rgba(14, 165, 233, 0.22);
        color: #0369a1;
    }

    .emploi-info-pill.success {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.22);
        color: #047857;
    }

    .emploi-info-pill.warning {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.28);
        color: #b45309;
    }
    
    .emploi-info-item {
        text-align: left;
    }
    
    .emploi-info-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-bottom: 2px;
        font-weight: 500;
    }
    
    .emploi-info-value {
        font-size: var(--text-normal);
        color: var(--text-primary);
        font-weight: 600;
    }
    
    .emploi-actions {
        display: flex;
        gap: var(--space-xs);
        justify-content: flex-end;
        padding-top: var(--space-sm);
        border-top: 1px solid #f3f4f6;
        margin-top: var(--space-sm);
    }

    .table-container {
        overflow-x: auto;
        border-radius: var(--radius-medium);
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Conteneur des cards */
    .emplois-cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        align-items: stretch;
        grid-auto-flow: row dense;
        width: 100%;
    }

    .emploi-card {
        min-width: 0;
    }
    
    /* Header du toggle vue */
    .emploi-view-toggle {
        display: flex;
        align-items: center;
    }
    
    /* État empty pour les cards */
    .empty-state-card {
        grid-column: 1 / -1;
        background: var(--surface);
        border-radius: var(--radius-medium);
        border: 2px dashed #e5e7eb;
    }
    
    /* Badges dans les cards */
    .emploi-status-badges {
        display: flex;
        gap: var(--space-xs);
        flex-wrap: wrap;
    }
    
    .emploi-status-badges .badge-moderne {
        font-size: 11px;
        padding: 3px 8px;
    }
    
    /* Animation de transition */
    .emploi-card, .table-container {
        transition: all 0.3s ease;
    }

    /* Responsive pour les cards */
    @media (min-width: 1400px) {
        .emplois-cards-container {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
    }

    @media (max-width: 991px) {
        .emplois-cards-container {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .emploi-view-toggle {
            order: -1;
            margin-bottom: var(--space-sm);
        }
        
        .card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--space-sm);
        }
    }
    
    .table-moderne {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 960px;
    }
    
    .table-moderne th {
        background-color: var(--background);
        color: var(--text-primary);
        font-weight: 600;
        font-size: var(--text-small);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
        padding: var(--space-md);
        white-space: nowrap;
        min-width: fit-content;
    }
    
    .table-moderne td {
        padding: var(--space-md);
        border-top: 1px solid #f3f4f6;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        white-space: nowrap;
        min-width: fit-content;
    }

    .table-moderne tbody tr {
        background: #ffffff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    }

    .table-moderne tbody tr td:first-child {
        border-left: 1px solid #f3f4f6;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }

    .table-moderne tbody tr td:last-child {
        border-right: 1px solid #f3f4f6;
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    .table-shortcut-row td {
        background: rgba(245, 158, 11, 0.08);
        border: 1px dashed rgba(245, 158, 11, 0.4);
        border-radius: 10px;
        padding: var(--space-md);
    }
    
    /* Largeurs spécifiques pour les colonnes */
    .table-moderne .col-classe {
        min-width: 120px;
        font-weight: 600;
    }
    
    .table-moderne .col-filiere {
        min-width: 140px;
    }
    
    .table-moderne .col-niveau {
        min-width: 100px;
    }
    
    .table-moderne .col-annee {
        min-width: 150px;
    }
    
    .table-moderne .col-periode {
        min-width: 120px;
    }
    
    .table-moderne .col-statut {
        min-width: 180px;
        white-space: nowrap;
    }
    
    .table-moderne .col-statut .badge-moderne {
        display: inline-block;
        margin-right: 4px;
        margin-bottom: 2px;
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .table-moderne .col-actions {
        min-width: 120px;
        text-align: center;
    }
    
    .table-moderne tbody tr:hover {
        background-color: rgba(30, 58, 138, 0.02);
    }
    
    .badge-moderne {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        font-weight: 500;
    }
    
    .badge-moderne.primary {
        background-color: rgba(30, 58, 138, 0.1);
        color: var(--primary);
    }
    
    .badge-moderne.success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }
    
    .badge-moderne.secondary {
        background-color: rgba(107, 114, 128, 0.1);
        color: var(--neutral);
    }
    
    .badge-moderne.info {
        background-color: rgba(6, 182, 212, 0.1);
        color: var(--accent-blue);
    }

    .badge-moderne.warning {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .badge-moderne.danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }
    
    .btn-group-moderne {
        display: flex;
        gap: var(--space-xs);
    }
    
    .btn-moderne {
        padding: var(--space-xs) var(--space-sm);
        border: none;
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    
    .btn-moderne.info {
        background-color: var(--accent-blue);
        color: white;
    }
    
    .btn-moderne.warning {
        background-color: var(--warning);
        color: white;
    }
    
    .btn-moderne.danger {
        background-color: var(--danger);
        color: white;
    }
    
    .btn-moderne:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne des emplois du temps -->
        <div class="emploi-temps-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="emploi-stat-icon me-4">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h1 class="mb-1">Gestion des emplois du temps</h1>
                        <p class="mb-0 opacity-75">Administration avancée des plannings scolaires avec intégration planning</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="timetable-tips-btn" data-bs-toggle="modal" data-bs-target="#timetableTipsModal">
                        <i class="fas fa-lightbulb"></i>Tips
                    </button>
                    @if(auth()->user()->can('edit_timetables'))
                        <button type="button" class="btn-acasi info" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                            <i class="fas fa-layer-group me-2"></i>Modifier rapidement
                        </button>
                    @endif
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->can('create_timetable'))
                        <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus-circle me-2"></i>Nouveau
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @php
            $anneeCourante = $anneeUniversitaireCourante ?? null;
            $anneeEndDate = $anneeCourante?->end_date ? \Carbon\Carbon::parse($anneeCourante->end_date) : null;
        @endphp
        @if($anneeCourante && $anneeEndDate && $anneeEndDate->isPast())
            <div class="alert alert-warning d-flex align-items-start gap-2 mt-3" role="alert">
                <i class="fas fa-exclamation-triangle mt-1"></i>
                <div>
                    <strong>Année universitaire échue :</strong>
                    {{ $anneeCourante->name }} s'est terminée le {{ $anneeEndDate->format('d/m/Y') }}.
                    Pensez à activer la nouvelle année courante.
                </div>
            </div>
        @endif

        <!-- Statistiques des emplois du temps -->
        <div class="kpi-grid mb-xl">
            <div class="card-moderne emploi-stat-card primary">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $totalEmploisTemps }}</div>
                    <div class="emploi-stat-label">Total emplois du temps</div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card success">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $emploisTempsActifsCount }}</div>
                    <div class="emploi-stat-label">Emplois du temps actifs</div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card info">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $totalSeances }}</div>
                    <div class="emploi-stat-label">Total séances de cours</div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card warning">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $anneeUniversitaireCourante->name ?? 'Année non définie' }}</div>
                    <div class="emploi-stat-label">Année universitaire</div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-lg" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-lg" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Main content -->
            <div class="col-lg-8">
                <div class="card-moderne">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Liste des emplois du temps
                        </h5>
                        <div class="emploi-view-toggle">
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="viewMode" id="cardView" checked>
                                <label class="btn btn-outline-secondary" for="cardView">
                                    <i class="fas fa-th-large"></i> Cards
                                </label>
                                
                                <input type="radio" class="btn-check" name="viewMode" id="tableView">
                                <label class="btn btn-outline-secondary" for="tableView">
                                    <i class="fas fa-table"></i> Tableau
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Vue en cards (par défaut) -->
                        <div id="cardsContainer" class="emplois-cards-container">
                            @include('esbtp.emploi-temps.partials.cards', ['emploisTemps' => $emploisTemps, 'timetableShortcut' => $timetableShortcut ?? null])
                        </div>

                        <!-- Vue tableau (masquée par défaut) -->
                        <div id="tableContainer" class="table-container" style="display: none;">
                            <div class="table-responsive">
                            <table class="table table-moderne datatable" id="emploiTempsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="col-classe">Classe</th>
                                    <th class="col-filiere">Filière</th>
                                    <th class="col-niveau">Niveau</th>
                                    <th class="col-annee">Année universitaire</th>
                                    <th class="col-periode">Période</th>
                                    <th class="col-dates">Dates</th>
                                    <th class="col-statut">Statut</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                    @include('esbtp.emploi-temps.partials.table-rows', ['emploisTemps' => $emploisTemps, 'timetableShortcut' => $timetableShortcut ?? null])
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar avec filtres -->
            <div class="col-lg-4">
                <div class="emploi-filter-card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filtrer les emplois du temps
                        </h6>
                    </div>
                    <div class="card-body">
                    <form action="{{ route('esbtp.emploi-temps.index') }}" method="GET" id="filterForm">
                        <div class="mb-3">
                            <label for="filiere_id" class="form-label">Filière</label>
                            <select class="form-select select2" id="filiere_id" name="filiere_id">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="niveau_id" class="form-label">Niveau d'études</label>
                            <select class="form-select select2" id="niveau_id" name="niveau_id">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="classe_id" class="form-label">Classe</label>
                            <select class="form-select select2" id="classe_id" name="classe_id">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="semaine" class="form-label">
                                <i class="fas fa-calendar-week me-1"></i>Semaine
                            </label>
                            <select class="form-select" id="semaine" name="semaine">
                                <option value="">Toutes les semaines</option>
                                @php
                                    // Récupérer toutes les plages de dates distinctes des emplois du temps
                                    $semaines = \App\Models\ESBTPEmploiTemps::select('date_debut', 'date_fin')
                                        ->whereNotNull('date_debut')
                                        ->whereNotNull('date_fin')
                                        ->distinct()
                                        ->orderBy('date_debut', 'desc')
                                        ->get()
                                        ->map(function($emploi) {
                                            return [
                                                'value' => $emploi->date_debut . '|' . $emploi->date_fin,
                                                'label' => \Carbon\Carbon::parse($emploi->date_debut)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($emploi->date_fin)->format('d/m/Y')
                                            ];
                                        })
                                        ->unique('value');
                                @endphp
                                @foreach($semaines as $semaine)
                                    <option value="{{ $semaine['value'] }}" {{ request('semaine') == $semaine['value'] ? 'selected' : '' }}>
                                        {{ $semaine['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: 8px;">
                                <label for="annee_id" class="form-label mb-0" style="font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Universitaire Courante</label>
                                <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#yearChangeModal" style="background: rgba(6, 182, 212, 0.12); color: var(--accent-blue); border: 1px solid rgba(6, 182, 212, 0.35); border-radius: 999px; padding: 2px 10px; font-weight: 600;">
                                    <i class="fas fa-info-circle me-1"></i>Changer d'année
                                </button>
                            </div>
                            <select name="annee_id" id="annee_id" class="form-select" style="background-color: #f8f9fa; cursor: not-allowed;" disabled>
                                @if(isset($anneeUniversitaireCourante))
                                    <option value="{{ $anneeUniversitaireCourante->id }}" selected>
                                        {{ $anneeUniversitaireCourante->name }} (Année en cours)
                                    </option>
                                @else
                                    <option value="" selected>Aucune année active</option>
                                @endif
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Les emplois du temps sont filtrés par l'année courante.
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="is_current" class="form-label">Emploi du temps courant</label>
                            <select class="form-select" id="is_current" name="is_current">
                                <option value="">Tous</option>
                                <option value="1" {{ request('is_current') == '1' ? 'selected' : '' }}>Courant uniquement</option>
                                <option value="0" {{ request('is_current') == '0' ? 'selected' : '' }}>Non courant</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="period_status" class="form-label">Statut automatique</label>
                            <select class="form-select" id="period_status" name="period_status">
                                <option value="">Tous les statuts</option>
                                <option value="current" {{ request('period_status') == 'current' ? 'selected' : '' }}>Actifs (période en cours)</option>
                                <option value="upcoming" {{ request('period_status') == 'upcoming' ? 'selected' : '' }}>Inactifs (période à venir)</option>
                                <option value="expired" {{ request('period_status') == 'expired' ? 'selected' : '' }}>Expirés</option>
                            </select>
                        </div>

                            <div class="d-grid gap-2">
                                <button type="button" id="applyFiltersBtn" class="btn-acasi primary">
                                    <i class="fas fa-search me-2"></i>Appliquer les filtres
                                </button>
                                <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn-acasi secondary">
                                    <i class="fas fa-redo-alt me-2"></i>Réinitialiser
                                </a>
                            </div>
                    </form>
                </div>
            </div>

                <!-- Actions rapides -->
                <div class="emploi-filter-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Actions rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn-acasi success">
                                <i class="fas fa-plus-circle me-2"></i>Créer emploi du temps
                            </a>
                            <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="btn-acasi info">
                                <i class="fas fa-chart-pie me-2"></i>Répartition matières
                            </a>
                            <a href="{{ route('esbtp.planning-general.annuel') }}" class="btn-acasi warning">
                                <i class="fas fa-calendar-alt me-2"></i>Planning annuel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tips-modal" id="timetableTipsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-route me-2"></i>Guide rapide pour créer un emploi du temps</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="tips-steps">
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 1</span> Créer l’enseignant
                        </div>
                        <p class="text-muted mb-0">Ajoutez l’enseignant et renseignez sa spécialité pour les associations.</p>
                        <img src="{{ asset('assets/guides/timetable/step-1-teacher.png') }}" alt="Créer un enseignant">
                        <a class="tips-step-action" href="{{ route('esbtp.enseignants.create') }}">
                            <i class="fas fa-user-plus"></i>Créer un enseignant
                        </a>
                    </div>
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 2</span> Définir la disponibilité
                        </div>
                        <p class="text-muted mb-0">Configurez les créneaux disponibles avant de planifier.</p>
                        <img src="{{ asset('assets/guides/timetable/step-2-availability.png') }}" alt="Disponibilités enseignant">
                        <a class="tips-step-action" href="{{ url('esbtp/personnel/unified') }}">
                            <i class="fas fa-calendar-check"></i>Gérer les disponibilités
                        </a>
                    </div>
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 3</span> Planning général & volumes
                        </div>
                        <p class="text-muted mb-0">Associez les enseignants aux filières/niveaux et aux volumes horaires.</p>
                        <img src="{{ asset('assets/guides/timetable/step-3-planning.png') }}" alt="Planning général">
                        <a class="tips-step-action" href="{{ route('esbtp.planning-general.index') }}">
                            <i class="fas fa-layer-group"></i>Configurer le planning
                        </a>
                    </div>
                    <div class="tips-step">
                        <div class="tips-step-title">
                            <span>Étape 4</span> Générer l’emploi du temps
                        </div>
                        <p class="text-muted mb-0">Lancez la génération rapide ou créez manuellement la semaine.</p>
                        <img src="{{ asset('assets/guides/timetable/step-4-generate.png') }}" alt="Génération emploi du temps">
                        <a class="tips-step-action" href="{{ route('esbtp.emploi-temps.create') }}">
                            <i class="fas fa-calendar-plus"></i>Créer l’emploi du temps
                        </a>
                    </div>
                </div>
                <div class="tips-note">
                    <i class="fas fa-info-circle me-2"></i>
                    Astuce : sans disponibilité ou volume horaire configuré, la duplication bascule en mode vide.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('esbtp.emploi-temps.bulk-edit') }}" id="bulk-edit-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkEditModalLabel">
                        <i class="fas fa-layer-group me-2"></i>Modifier rapidement les séances
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Sélectionnez les classes avec un emploi du temps actif à modifier.</p>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bulk-edit-select-all">
                            <label class="form-check-label" for="bulk-edit-select-all">Tout sélectionner</label>
                        </div>
                        <span class="badge bg-light text-dark">{{ $emploisTempsActifs->count() }} actif(s)</span>
                    </div>

                    @if($emploisTempsActifs->isEmpty())
                        <div class="alert alert-warning mb-0">
                            Aucun emploi du temps actif n'est disponible pour la modification rapide.
                        </div>
                    @else
                        <div class="list-group">
                            @foreach($emploisTempsActifs as $emploiTemps)
                                <label class="list-group-item d-flex align-items-center gap-3">
                                    <input class="form-check-input bulk-edit-checkbox" type="checkbox" name="ids[]" value="{{ $emploiTemps->id }}">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $emploiTemps->classe->name ?? 'Classe non définie' }}</div>
                                        <div class="small text-muted">
                                            {{ $emploiTemps->titre ?? 'Emploi du temps' }}
                                            @if($emploiTemps->date_debut && $emploiTemps->date_fin)
                                                · {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    @if($emploiTemps->is_current)
                                        <span class="badge bg-success">Actuel</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="bulk-edit-submit" {{ $emploisTempsActifs->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-arrow-right me-1"></i>Ouvrir les emplois du temps
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année universitaire ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les emplois du temps affichés se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois.
                    Changer l'année courante affecte l'affichage des emplois du temps dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les emplois du temps de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les emplois du temps de 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

@if(!empty($timetableShortcut) && ($timetableShortcut['show'] ?? false) && (auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->can('create_timetable')))
<div class="modal fade" id="quickGenerateModal" tabindex="-1" aria-labelledby="quickGenerateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('esbtp.emploi-temps.quick-generate') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="quickGenerateModalLabel">
                        <i class="fas fa-bolt me-2 text-warning"></i>Génération rapide des emplois du temps
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Classes concernées :</strong>
                        <div class="mt-2">
                            @if($timetableShortcut['missing'] > 0)
                                <div>• {{ $timetableShortcut['missing'] }} classe(s) sans emploi du temps (semaine courante)</div>
                            @endif
                            @if($timetableShortcut['expired'] > 0)
                                <div>• {{ $timetableShortcut['expired'] }} emploi(s) expiré(s) (semaine prochaine)</div>
                            @endif
                            @if($timetableShortcut['expiring_soon'] > 0)
                                <div>• {{ $timetableShortcut['expiring_soon'] }} emploi(s) expirant sous 3 jours (semaine prochaine)</div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quick-generate-semestre" class="form-label fw-semibold">
                            Période (obligatoire)
                        </label>
                        <select id="quick-generate-semestre" name="semestre" class="form-select" required>
                            <option value="" selected disabled>Choisir la période actuelle</option>
                            <option value="Semestre 1">Semestre 1</option>
                            <option value="Semestre 2">Semestre 2</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choisir les classes et le mode</label>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input class="form-check-input" type="checkbox" id="quick-generate-select-all">
                                        </th>
                                        <th>Classe</th>
                                        <th>Statut</th>
                                        <th>Période cible</th>
                                        <th>Mode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($timetableShortcut['items'] as $item)
                                        @php
                                            $classe = $item['class'];
                                            $hasSource = !empty($item['source']);
                                            $statusLabel = $item['status'] === 'missing'
                                                ? 'Sans emploi'
                                                : ($item['status'] === 'expiring_soon' ? 'Expire bientôt' : 'Expiré');
                                        @endphp
                                        <tr>
                                            <td>
                                                <input class="form-check-input" type="checkbox" name="classes[]" value="{{ $classe->id }}" checked>
                                            </td>
                                            <td>
                                                <strong>{{ $classe->name }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">{{ $statusLabel }}</span>
                                            </td>
                                            <td>
                                                {{ $item['target_start']->format('d/m') }} → {{ $item['target_end']->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                <select name="modes[{{ $classe->id }}]" class="form-select form-select-sm">
                                                    <option value="empty" {{ $hasSource ? '' : 'selected' }}>Vide</option>
                                                    <option value="duplicate" {{ $hasSource ? 'selected' : 'disabled' }}>Dupliquer</option>
                                                </select>
                                                @if(!$hasSource)
                                                    <small class="text-muted">Aucune base à dupliquer</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <small class="text-muted">
                        Le système évite de créer des doublons si un emploi du temps existe déjà pour la période cible.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link me-auto" data-bs-toggle="modal" data-bs-target="#quickGenerateHelpModal">
                        <i class="fas fa-info-circle me-1"></i>Comment ça marche ?
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-bolt me-1"></i>Générer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quickGenerateConfirmModal" tabindex="-1" aria-labelledby="quickGenerateConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickGenerateConfirmModalLabel">
                    <i class="fas fa-triangle-exclamation text-warning me-2"></i>Confirmer les séances ignorées
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Certaines séances ne pourront pas être dupliquées pour cause d'indisponibilité ou de conflit d'emploi du temps.</p>
                <div id="quickGenerateConflictList" class="d-grid gap-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="quickGenerateConfirmBtn">
                    Continuer quand même
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const quickGenerateModal = document.getElementById('quickGenerateModal');
    if (!quickGenerateModal) {
        return;
    }

    const quickGenerateForm = quickGenerateModal.querySelector('form');
    const submitButton = quickGenerateForm ? quickGenerateForm.querySelector('button[type="submit"]') : null;
    const confirmModalEl = document.getElementById('quickGenerateConfirmModal');
    const confirmList = document.getElementById('quickGenerateConflictList');
    const confirmBtn = document.getElementById('quickGenerateConfirmBtn');
    const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
    let quickGenerateConfirmed = false;

    const selectAllCheckbox = document.getElementById('quick-generate-select-all');
    const checkboxes = () => quickGenerateModal.querySelectorAll('input[name="classes[]"]');

    const updateHeaderState = () => {
        const boxes = Array.from(checkboxes());
        const checked = boxes.filter((box) => box.checked).length;
        if (!selectAllCheckbox) {
            return;
        }
        selectAllCheckbox.checked = checked > 0 && checked === boxes.length;
        selectAllCheckbox.indeterminate = checked > 0 && checked < boxes.length;
    };

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            selectAllCheckbox.indeterminate = false;
        });
    }

    checkboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateHeaderState);
    });

    updateHeaderState();

    if (quickGenerateForm) {
        quickGenerateForm.addEventListener('submit', async (event) => {
            if (quickGenerateConfirmed) {
                return;
            }

            event.preventDefault();

            const formData = new FormData(quickGenerateForm);
            const actionUrl = "{{ route('esbtp.emploi-temps.quick-generate.preview') }}";
            const token = quickGenerateForm.querySelector('input[name="_token"]')?.value || '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analyse...';
            }

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Erreur lors de la prévisualisation.');
                }

                if (payload.total_conflicts > 0 && confirmModal && confirmList) {
                    confirmList.innerHTML = payload.conflicts.map((group) => {
                        const itemsHtml = (group.items || []).map((item) => {
                            const badgeClass = item.reason === 'occupied' ? 'bg-danger' : 'bg-warning text-dark';
                            const reasonLabel = item.reason === 'occupied' ? 'Déjà occupé' : 'Indisponible';
                            return `
                                <li class="d-flex flex-wrap align-items-center gap-2 py-1">
                                    <span class="badge ${badgeClass}">${reasonLabel}</span>
                                    <span class="fw-semibold">${item.matiere}</span>
                                    <span class="text-muted">${item.enseignant}</span>
                                    <span class="text-muted">${item.jour} ${item.heure_debut}–${item.heure_fin}</span>
                                </li>
                            `;
                        }).join('');

                        return `
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="fw-semibold mb-2">${group.class_name}</div>
                                <ul class="list-unstyled mb-0">
                                    ${itemsHtml}
                                </ul>
                            </div>
                        `;
                    }).join('');

                    confirmModal.show();
                } else {
                    quickGenerateConfirmed = true;
                    quickGenerateForm.submit();
                }
            } catch (error) {
                alert(error.message || 'Erreur lors de la prévisualisation.');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-bolt me-1"></i>Générer';
                }
            }
        });
    }

    if (confirmBtn && quickGenerateForm) {
        confirmBtn.addEventListener('click', () => {
            quickGenerateConfirmed = true;
            if (confirmModal) {
                confirmModal.hide();
            }
            quickGenerateForm.submit();
        });
    }
});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bulkEditModal = document.getElementById('bulkEditModal');
    if (!bulkEditModal) {
        return;
    }

    const selectAll = document.getElementById('bulk-edit-select-all');
    const submitButton = document.getElementById('bulk-edit-submit');
    const checkboxes = () => bulkEditModal.querySelectorAll('.bulk-edit-checkbox');

    const updateState = () => {
        const boxes = Array.from(checkboxes());
        const checkedCount = boxes.filter((box) => box.checked).length;
        if (selectAll) {
            selectAll.checked = checkedCount > 0 && checkedCount === boxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
        }
        if (submitButton) {
            submitButton.disabled = checkedCount === 0;
        }
    };

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            selectAll.indeterminate = false;
            updateState();
        });
    }

    checkboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateState);
    });

    updateState();
});
</script>
@endpush

@if(!empty($timetableShortcut) && ($timetableShortcut['show'] ?? false))
<div class="modal fade" id="quickGenerateHelpModal" tabindex="-1" aria-labelledby="quickGenerateHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickGenerateHelpModalLabel">
                    <i class="fas fa-info-circle me-2 text-warning"></i>À propos de la génération rapide
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Objectif :</strong> créer rapidement des emplois du temps pour les classes sans planning, expirées ou qui expirent bientôt.</p>
                <ul class="mb-3">
                    <li><strong>Mode Vide :</strong> crée un emploi du temps sans séances.</li>
                    <li><strong>Mode Dupliquer :</strong> copie le dernier emploi du temps de la classe (séances + horaires).</li>
                </ul>
                <p class="mb-0"><strong>Dates générées automatiquement :</strong></p>
                <ul>
                    <li>Sans emploi du temps → semaine courante.</li>
                    <li>Expiré ou expiring sous 3 jours → semaine prochaine.</li>
                </ul>
                <small class="text-muted">Astuce : décoche les classes que tu veux gérer manuellement.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Basculer entre les vues
        const cardView = document.getElementById('cardView');
        const tableView = document.getElementById('tableView');
        const cardsContainer = document.getElementById('cardsContainer');
        const tableContainer = document.getElementById('tableContainer');
        
        // Gérer le changement de vue
        function toggleView() {
            if (cardView.checked) {
                cardsContainer.style.display = 'grid';
                tableContainer.style.display = 'none';
                localStorage.setItem('emploiTempsViewMode', 'cards');
            } else if (tableView.checked) {
                cardsContainer.style.display = 'none';
                tableContainer.style.display = 'block';
                localStorage.setItem('emploiTempsViewMode', 'table');

                // Réinitialiser DataTable si pas encore initialisé
                // Vérifier que jQuery et DataTables sont chargés
                if (typeof $ !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
                    if (!$.fn.dataTable.isDataTable('#emploiTempsTable')) {
                        initializeDataTable();
                    }
                }
            }
        }
        
        cardView.addEventListener('change', toggleView);
        tableView.addEventListener('change', toggleView);
        
        // Restaurer la vue préférée de l'utilisateur
        const savedViewMode = localStorage.getItem('emploiTempsViewMode');
        if (savedViewMode === 'table') {
            tableView.checked = true;
            toggleView();
        } else {
            cardView.checked = true;
            toggleView();
        }
        
        // Initialize DataTable (seulement quand nécessaire)
        function initializeDataTable() {
            const table = $('#emploiTempsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
                },
                pageLength: 10,
                responsive: true,
                order: [[3, 'desc'], [1, 'asc']]
            });
            return table;
        }

        // AJAX Refresh Function
        function fetchEmploisTempsData() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            debugLog('🔄 Fetching emplois temps with params:', Object.fromEntries(params));

            // Show loading overlay
            showOverlay();

            const targetUrl = `{{ route("esbtp.emploi-temps.refresh") }}?${params.toString()}`;

            fetch(targetUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des emplois du temps.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update cards container
                    document.getElementById('cardsContainer').innerHTML = data.html_cards;

                    // Update table body
                    document.getElementById('tableBody').innerHTML = data.html_table;

                    // Reinitialize DataTable if in table view
                    if (tableView.checked && typeof $ !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
                        if ($.fn.dataTable.isDataTable('#emploiTempsTable')) {
                            $('#emploiTempsTable').DataTable().destroy();
                        }
                        initializeDataTable();
                    }

                    // Update URL without reload
                    const newUrl = new URL(window.location);
                    params.forEach((value, key) => {
                        if (value) {
                            newUrl.searchParams.set(key, value);
                        } else {
                            newUrl.searchParams.delete(key);
                        }
                    });
                    history.pushState({}, '', newUrl);

                    debugLog('✅ Refresh completed: ' + data.count + ' emplois temps');
                } else {
                    debugError('❌ Error:', data.message);
                    alert('Erreur lors du chargement des données');
                }
            })
            .catch(error => {
                debugError('❌ Fetch error:', error);
                alert('Erreur de connexion au serveur: ' + error.message);
            })
            .finally(() => {
                hideOverlay();
            });
        }

        // Helper functions for overlay
        function showOverlay() {
            if (document.getElementById('loadingOverlay')) return;
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.3);z-index:9999;display:flex;align-items:center;justify-content:center;';
            overlay.innerHTML = '<div style="background:white;padding:20px;border-radius:8px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            document.body.appendChild(overlay);
        }

        function hideOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.remove();
        }

        // Initialize Select2 AFTER defining event listeners
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Sélectionner une option',
                allowClear: true
            });
        }

        // Get all filter elements (same pattern as classes.index)
        const form = document.getElementById('filterForm');
        const filterInputs = form.querySelectorAll('select');

        // Add change listeners to ALL select elements (Select2 will trigger 'change' event on original element)
        filterInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                debugLog('🔄 Filter changed:', this.id || this.name, '=', this.value);
                fetchEmploisTempsData();
            });
        });

        // Event listener for "Appliquer les filtres" button
        document.getElementById('applyFiltersBtn').addEventListener('click', function(e) {
            e.preventDefault();
            debugLog('🔄 Apply filters button clicked');
            fetchEmploisTempsData();
        });

        // Prevent default form submission (safety fallback)
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugLog('🔄 Form submit prevented');
            fetchEmploisTempsData();
            return false;
        });

        const searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('quick_generate')) {
            const quickGenerateModal = document.getElementById('quickGenerateModal');
            if (quickGenerateModal && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(quickGenerateModal);
                modal.show();
            }
        }
    });
</script>
@endpush
