@extends('layouts.app')

@section('title', 'Emploi du temps - ' . (is_object($emploiTemps) && is_object($emploiTemps->classe) ? $emploiTemps->classe->name : 'Non défini') . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ═════════════════════════════════════════════════════════════════
       Emploi du temps — show premium v1 (PR #221)
       Namespace: ets-* (emploi-temps-show)
       Pattern inspire de planning-header (ph-*)
       ═════════════════════════════════════════════════════════════════ */

    .ets-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 1.75rem 2rem 1.25rem;
        color: #fff;
        margin-bottom: 1.25rem;
        /* Pas d'overflow:hidden — le dropdown du menu kebab doit pouvoir s'etendre */
    }
    .ets-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 80% 20%, rgba(255,255,255,.12), transparent 60%);
        pointer-events: none;
        border-radius: inherit;
    }
    /* Safety: dropdown du kebab toujours au-dessus + aligne sur le droit */
    .ets-hero-actions .dropdown-menu {
        z-index: 1060;
    }
    .ets-hero-actions .dropdown-menu-end[data-bs-popper] {
        right: 0;
        left: auto;
    }

    .ets-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        z-index: 2;
    }
    .ets-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .ets-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.14);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .ets-hero-info { min-width: 0; }
    .ets-hero-info h1 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
        letter-spacing: -.01em;
    }
    .ets-hero-info p {
        margin: .15rem 0 0;
        opacity: .78;
        font-size: .85rem;
    }
    .ets-hero-chips {
        display: flex;
        gap: .4rem;
        flex-wrap: wrap;
        margin-top: .55rem;
    }
    .ets-hero-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .65rem;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 99px;
        font-size: .72rem;
        color: rgba(255,255,255,.92);
        font-weight: 500;
    }
    .ets-hero-chip--success { background: rgba(16,185,129,.22); border-color: rgba(16,185,129,.35); color: #a7f3d0; }
    .ets-hero-chip--muted   { background: rgba(148,163,184,.22); border-color: rgba(148,163,184,.3); color: #cbd5e1; }
    .ets-hero-chip i { font-size: .65rem; }

    .ets-hero-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: flex-start;
        position: relative;
        z-index: 3;
    }
    .ets-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem 1rem;
        border-radius: 10px;
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .2s ease;
        border: 1px solid transparent;
        cursor: pointer;
        white-space: nowrap;
    }
    .ets-btn--glass {
        background: rgba(255,255,255,.15);
        color: #fff;
        border-color: rgba(255,255,255,.22);
    }
    .ets-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
    .ets-btn--white {
        background: #fff;
        color: #0453cb;
    }
    .ets-btn--white:hover { background: #eef3ff; color: #033a8e; }

    .ets-dropdown-menu {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 12px 32px rgba(15,23,42,.15);
        padding: .35rem;
        min-width: 240px;
        z-index: 1050;
    }
    .ets-dropdown-menu .dropdown-item {
        color: #1e293b;
        padding: .55rem .85rem;
        border-radius: 8px;
        font-size: .85rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        transition: all .15s ease;
    }
    .ets-dropdown-menu .dropdown-item:hover { background: #f1f5f9; color: #0453cb; }
    .ets-dropdown-menu .dropdown-item i { width: 16px; text-align: center; color: #0453cb; }
    .ets-dropdown-menu .dropdown-item.text-danger i { color: #dc2626; }
    .ets-dropdown-menu .dropdown-item.text-danger:hover { background: rgba(220,38,38,.06); color: #b91c1c; }
    .ets-dropdown-divider {
        height: 1px;
        background: #e2e8f0;
        margin: .3rem 0;
    }

    /* KPIs dans hero */
    .ets-kpis {
        display: flex;
        gap: .65rem;
        margin-top: 1.2rem;
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }
    .ets-kpi {
        flex: 1;
        min-width: 160px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 12px;
        padding: .75rem .9rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .ets-kpi-icon {
        width: 36px; height: 36px;
        border-radius: 9px;
        background: rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .ets-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #fff;
        line-height: 1;
    }
    .ets-kpi-label {
        font-size: .72rem;
        color: rgba(255,255,255,.72);
        margin-top: .2rem;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    @media (max-width: 768px) {
        .ets-hero { padding: 1.25rem 1.25rem 1rem; }
        .ets-hero-info h1 { font-size: 1.15rem; }
        .ets-hero-top { flex-direction: column; }
        .ets-kpi { min-width: calc(50% - .35rem); }
    }
    @media (max-width: 480px) {
        .ets-kpi { min-width: 100%; }
    }

    /* ═══════════════════════════════════════════════════════════
       Modal config volumes — gradient bleu KLASSCI (pattern PR #220)
       ═══════════════════════════════════════════════════════════ */
    .ets-config-modal .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15,23,42,.25), 0 8px 20px rgba(4,83,203,.12);
        overflow: hidden;
    }
    .ets-config-modal .modal-header {
        background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
        color: #fff;
        border-bottom: none;
        padding: 1rem 1.5rem;
    }
    .ets-config-modal .modal-title {
        font-weight: 700;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: .6rem;
    }
    .ets-config-modal .modal-title i {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .88rem;
        color: #fff;
    }
    .ets-config-modal .btn-close {
        filter: invert(1) brightness(2);
        opacity: .85;
    }
    .ets-config-modal .btn-close:hover { opacity: 1; }
    .ets-config-modal .modal-body {
        background: #fff;
        padding: 1.5rem;
    }
    .ets-config-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
        gap: .5rem;
    }
    .ets-config-modal .modal-footer .btn {
        border-radius: 10px;
        font-weight: 600;
        padding: .55rem 1.25rem;
        font-size: .88rem;
    }
    .ets-config-modal .modal-footer .btn-light {
        background: #fff;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .ets-config-modal .modal-footer .btn-light:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #1e293b;
    }
    .ets-config-modal .modal-footer .btn-primary {
        background: #0453cb;
        border-color: #0453cb;
        box-shadow: 0 2px 6px rgba(4,83,203,.2);
    }
    .ets-config-modal .modal-footer .btn-primary:hover {
        background: #033a8e;
        border-color: #033a8e;
        transform: translateY(-1px);
    }
    .ets-config-modal .alert-info {
        background: rgba(4,83,203,.06);
        border: 1px solid rgba(4,83,203,.15);
        color: #033a8e;
        border-radius: 10px;
        padding: .75rem 1rem;
        font-size: .86rem;
    }
    .ets-config-modal .form-control,
    .ets-config-modal .form-select {
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        font-size: .88rem;
        transition: all .15s ease;
    }
    .ets-config-modal .form-control:focus,
    .ets-config-modal .form-select:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.1);
        outline: none;
    }

    /* ═══════════════════════════════════════════════════════════ */

    .timetable-container {
        overflow-x: auto;
    }

    .timetable {
        min-width: 900px;
    }

    .timetable th, .timetable td {
        min-width: 150px;
        height: 60px;
        position: relative;
    }

    .time-column {
        width: 80px;
        font-weight: bold;
        background-color: #f8f9fa;
    }

    .session-cell {
        padding: 5px;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Styles pour les séances qui durent plus d'une heure */
    .session-long {
        position: relative;
        z-index: 10;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: scale(1.02);
        transition: transform 0.2s;
    }

    .session-long:hover {
        transform: scale(1.05);
        z-index: 20;
    }

    .session-cours {
        background-color: var(--primary);
    }

    .session-td {
        background-color: var(--success);
    }

    .session-tp {
        background-color: var(--secondary);
    }

    .session-examen {
        background-color: var(--danger);
    }

    .session-autre {
        background-color: var(--warning);
    }

    /* Nouveaux styles pour les pauses et déjeuners */
    .session-pause {
        background-color: var(--neutral);
    }

    .session-dejeuner {
        background-color: var(--accent-orange);
    }

    .session-info {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .session-matiere {
        font-weight: bold;
        font-size: 0.9rem;
    }

    .session-enseignant {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .session-details {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .session-actions {
        position: absolute;
        top: 5px;
        right: 5px;
        display: none;
    }

    .session-cell:hover .session-actions {
        display: block;
    }

    .session-inactive {
        opacity: 0.6;
    }

    .btn-add-session {
        border: 2px dashed #dee2e6;
        background-color: rgba(0,0,0,0.02);
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        transition: all 0.2s;
    }

    .btn-add-session:hover {
        background-color: rgba(0,0,0,0.05);
        color: #343a40;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        margin-right: 15px;
    }

    .legend-color {
        width: 15px;
        height: 15px;
        border-radius: 3px;
        margin-right: 5px;
    }

    .seance-list-item {
        border-left: 4px solid var(--primary);
    }

    .seance-list-item.td {
        border-left-color: var(--success);
    }

    .seance-list-item.tp {
        border-left-color: var(--secondary);
    }

    .seance-list-item.examen {
        border-left-color: var(--danger);
    }

    .seance-list-item.autre {
        border-left-color: var(--warning);
    }

    /* ================================
       STYLES POUR LE MODAL DE CONFIGURATION
    ================================ */
    
    /* Configuration variables supplémentaires pour le modal */
    .config-section {
        --border: #e5e7eb;
        --text-sm: 0.875rem;
        --primary-rgb: 30, 58, 138;
    }
    
    .config-matiere-card {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: var(--space-lg);
        padding: var(--space-lg);
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-large);
        margin-bottom: var(--space-md);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--surface);
        box-shadow: var(--shadow-card);
        position: relative;
    }
    
    .config-matiere-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
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
        font-size: 0.875rem;
        color: var(--text-secondary);
        line-height: 1.4;
        margin: 0;
    }
    
    .matiere-config {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
        min-width: 280px;
    }
    
    .config-section {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }
    
    .config-label {
        font-weight: 600;
        font-size: var(--text-sm);
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }
    
    .volume-config {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .volume-input {
        flex: 1;
        min-width: 80px;
        padding: 0.5rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .volume-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        outline: none;
    }
    
    .volume-unit {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .teacher-config {
        position: relative;
    }
    
    .teacher-config .form-select {
        padding: 0.5rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .teacher-config .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        outline: none;
    }
    
    /* Modal loading state */
    .config-loading {
        min-height: 200px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .config-matiere-card {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            grid-template-columns: 1fr;
        }
        
        .matiere-config {
            justify-content: center;
            min-width: auto;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ═════════════════════════════════════════════════════════
             HERO PREMIUM (ets-*)
             ═════════════════════════════════════════════════════════ --}}
        <div class="ets-hero">
            <div class="ets-hero-top">
                <div class="ets-hero-left">
                    <div class="ets-hero-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="ets-hero-info">
                        <h1>{{ $emploiTemps->titre ?: ('Emploi du temps — ' . ($emploiTemps->classe->name ?? 'Non défini')) }}</h1>
                        <p>
                            {{ $emploiTemps->classe->filiere->name ?? 'Filière' }} ·
                            {{ $emploiTemps->classe->niveau->name ?? 'Niveau' }} ·
                            {{ $emploiTemps->annee->name ?? 'Année' }}
                        </p>
                        <div class="ets-hero-chips">
                            @if($emploiTemps->is_active ?? true)
                                <span class="ets-hero-chip ets-hero-chip--success">
                                    <i class="fas fa-circle"></i> Actif
                                </span>
                            @else
                                <span class="ets-hero-chip ets-hero-chip--muted">
                                    <i class="fas fa-circle"></i> Inactif
                                </span>
                            @endif
                            @if($emploiTemps->is_current ?? false)
                                <span class="ets-hero-chip">
                                    <i class="fas fa-star"></i> Courant
                                </span>
                            @endif
                            @if(isset($emploiTemps->semestre) && in_array($emploiTemps->semestre, ['Semestre 1', 'Semestre 2'], true))
                                <span class="ets-hero-chip">
                                    <i class="fas fa-calendar"></i> {{ $emploiTemps->semestre }}
                                </span>
                            @endif
                            @if($emploiTemps->date_debut && $emploiTemps->date_fin)
                                <span class="ets-hero-chip">
                                    <i class="fas fa-calendar-day"></i>
                                    {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m') }}
                                    →
                                    {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ets-hero-actions">
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="ets-btn ets-btn--glass">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    @can('create_timetable')
                    <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id]) }}" class="ets-btn ets-btn--white">
                        <i class="fas fa-plus"></i> Séance
                    </a>
                    @endcan
                    <div class="dropdown">
                        <button type="button" class="ets-btn ets-btn--glass" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" aria-label="Actions">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end ets-dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.emploi-temps.preview', ['emploi_temp' => $emploiTemps->id]) }}" target="_blank">
                                    <i class="fas fa-eye"></i> Prévisualiser PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.emploi-temps.export-pdf', ['emploi_temp' => $emploiTemps->id]) }}" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Télécharger PDF
                                </a>
                            </li>
                            <li><div class="ets-dropdown-divider"></div></li>
                            @can('edit_timetables')
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.emploi-temps.edit', ['emploi_temp' => $emploiTemps->id]) }}">
                                    <i class="fas fa-edit"></i> Modifier l'emploi
                                </a>
                            </li>
                            @endcan
                            @can('delete_timetables')
                            <li>
                                <button type="button" class="dropdown-item text-danger" onclick="etsDeleteEmploi()">
                                    <i class="fas fa-trash"></i> Supprimer l'emploi
                                </button>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>

            {{-- KPIs --}}
            <div class="ets-kpis">
                <div class="ets-kpi">
                    <div class="ets-kpi-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="ets-kpi-value">{{ $heroKpis['total_seances'] }}</div>
                        <div class="ets-kpi-label">Séances programmées</div>
                    </div>
                </div>
                <div class="ets-kpi">
                    <div class="ets-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="ets-kpi-value">{{ number_format($heroKpis['heures_planifiees'], 0) }}h</div>
                        <div class="ets-kpi-label">Heures planifiées</div>
                    </div>
                </div>
                <div class="ets-kpi">
                    <div class="ets-kpi-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <div class="ets-kpi-value">{{ $heroKpis['pourcentage_restant'] }}%</div>
                        <div class="ets-kpi-label">Volume restant</div>
                    </div>
                </div>
                <div class="ets-kpi">
                    <div class="ets-kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div>
                        <div class="ets-kpi-value">{{ $heroKpis['enseignants'] }}</div>
                        <div class="ets-kpi-label">Enseignants</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form cache suppression emploi (declenche via kebab menu) --}}
        @can('delete_timetables')
        <form id="ets-delete-emploi-form" method="POST" action="{{ route('esbtp.emploi-temps.destroy', ['emploi_temp' => $emploiTemps->id]) }}" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
        @endcan

        @if (session('success'))
            <div class="alert alert-success border-start border-success border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                    <div>{{ session('success') }}</div>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fs-4"></i>
                    </div>
                    <div>{{ session('error') }}</div>
                </div>
            </div>
        @endif
        <!-- Section: Planification Académique -->
        <x-emploi-temps.planification-section 
            :planificationData="$planificationData" 
            :emploiTemps="$emploiTemps" />

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Attention</h5>
                <p>{{ session('warning') }}</p>
                @if (session('show_force_delete'))
                    <hr>
                    <div class="d-flex justify-content-end">
                        @if(auth()->user()->can('access_admin') && auth()->user()->can('delete_timetables'))
                        <form action="{{ route('esbtp.emploi-temps.destroy', ['emploi_temp' => $emploiTemps->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="force_delete" value="1">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Confirmer la suppression forcée
                            </button>
                        </form>
                        @endif
                    </div>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Section: Informations et Statistiques -->
        <x-emploi-temps.info-stats-section 
            :emploiTemps="$emploiTemps" 
            :matiereStats="$matiereStats ?? []" />


        @php
            if (!isset($timeSlots) || !is_array($timeSlots) || empty($timeSlots)) {
                $timeSlots = [];
                for ($hour = 8; $hour < 18; $hour++) {
                    $timeSlots[] = sprintf('%02d:00', $hour);
                }
            }

            if (!isset($days) || !is_array($days) || empty($days)) {
                $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            }
        @endphp

        <!-- Section: Grille horaire (pleine largeur) -->
        <x-emploi-temps.grille-horaire 
            :seances="$emploiTemps->seances ?? collect()" 
            :emploiTemps="$emploiTemps"
            :timeSlots="$timeSlots"
            :days="$days" />

        <!-- Section: Liste des séances (pleine largeur) -->
        <x-emploi-temps.liste-seances 
            :seances="$emploiTemps->seances ?? collect()" 
            :emploiTemps="$emploiTemps" />
    </div>
</div>

<!-- Modal de Configuration des Volumes Horaires -->
<div class="modal fade ets-config-modal" id="volumeConfigModal" tabindex="-1" aria-labelledby="volumeConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="volumeConfigModalLabel">
                    <i class="fas fa-cog"></i>
                    <span>Configuration des volumes horaires</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Combinaison :</strong>
                    <span id="config-combination-name">{{ $emploiTemps->classe->filiere->name ?? 'Filière' }} · {{ $emploiTemps->classe->niveau->name ?? 'Niveau' }}</span>
                </div>

                <form id="volume-config-form">
                    <input type="hidden" id="config-filiere-id" name="filiere_id" value="{{ $emploiTemps->classe->filiere_id ?? '' }}">
                    <input type="hidden" id="config-niveau-id" name="niveau_id" value="{{ $emploiTemps->classe->niveau_etude_id ?? '' }}">
                    <input type="hidden" id="config-annee-id" name="annee_id" value="{{ $emploiTemps->annee->id ?? '' }}">

                    <div class="config-loading text-center py-4" id="config-loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <p class="text-muted">Chargement des matières...</p>
                    </div>

                    <div id="matieres-container">
                        <!-- Les matières seront chargées ici via AJAX -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary" id="save-volume-config">
                    <i class="fas fa-save me-1"></i>Sauvegarder
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<script>
    // ═════════════════════════════════════════════════════════════════
    // Emploi-temps show — actions premium (delete emploi, delete seance)
    // ═════════════════════════════════════════════════════════════════

    window.etsDeleteEmploi = async function () {
        const ok = await window.iiConfirm({
            title: "Supprimer l'emploi du temps",
            message: "Cette action supprimera l'emploi du temps et toutes ses séances. Les données ne pourront pas être récupérées. Confirmer ?",
            confirmLabel: 'Supprimer',
            cancelLabel: 'Annuler',
            danger: true,
        });
        if (!ok) return;
        const form = document.getElementById('ets-delete-emploi-form');
        if (form) form.submit();
    };

    window.etsDeleteSeance = async function (seanceId, matiereName) {
        const ok = await window.iiConfirm({
            title: "Supprimer la séance",
            message: `Confirmer la suppression de la séance « ${matiereName || 'Séance'} » ? Cette action est irréversible.`,
            confirmLabel: 'Supprimer',
            cancelLabel: 'Annuler',
            danger: true,
        });
        if (!ok) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/esbtp/seances-cours/${seanceId}`;
        form.style.display = 'none';
        form.innerHTML = `
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="DELETE">
        `;
        document.body.appendChild(form);
        form.submit();
    };

    // Variables globales pour le modal de configuration
    let currentFiliereId = null;
    let currentNiveauId = null;
    let currentCombinaisonName = '';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    // Fonction pour ouvrir le modal de configuration des volumes
    function openVolumeConfigModal(button) {
        const filiereId = button.getAttribute('data-filiere-id');
        const niveauId = button.getAttribute('data-niveau-id');
        const anneeId = button.getAttribute('data-annee-id');
        const combinationName = button.getAttribute('data-combination-name');
        
        // Stocker les valeurs globalement
        currentFiliereId = filiereId;
        currentNiveauId = niveauId;
        currentCombinaisonName = combinationName;
        
        // Mettre à jour les champs cachés
        $('#config-filiere-id').val(filiereId);
        $('#config-niveau-id').val(niveauId);
        $('#config-annee-id').val(anneeId);
        $('#config-combination-name').text(combinationName);
        
        // Charger les matières
        loadMatieresForConfiguration(filiereId, niveauId, anneeId);
    }
    
    // Fonction pour charger les matières via AJAX
    function loadMatieresForConfiguration(filiereId, niveauId, anneeId) {
        $('#config-loading').show();
        $('#matieres-container').empty();
        
        $.ajax({
            url: '{{ route("esbtp.planning-general.get-matieres-configuration") }}',
            method: 'GET',
            data: {
                filiere_id: filiereId,
                niveau_id: niveauId,
                annee_id: anneeId
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
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
    
    // Gestionnaire pour la sauvegarde des volumes
    $(document).on('click', '#save-volume-config', function() {
        const $btn = $(this);
        
        // Validation des champs requis
        if (!currentFiliereId || !currentNiveauId) {
            showAlert('error', 'Informations de combinaison manquantes');
            return;
        }
        
        // Collecter les données du formulaire
        const formData = {
            filiere_id: currentFiliereId,
            niveau_id: currentNiveauId,
            annee_id: $('#config-annee-id').val(),
            volumes: {},
            teachers: {}
        };
        
        // Collecter tous les volumes
        $('.volume-input').each(function() {
            const matiereId = $(this).attr('name').match(/volumes\[(\d+)\]/)[1];
            const volume = parseInt($(this).val()) || 0;
            formData.volumes[matiereId] = volume;
        });
        
        // Collecter toutes les assignations de professeurs
        $('.teacher-select').each(function() {
            const matiereId = $(this).attr('name').match(/teachers\[(\d+)\]/)[1];
            const selectedTeachers = $(this).val() || [];
            formData.teachers[matiereId] = selectedTeachers;
        });
        
        // Validation
        if (!formData.filiere_id || !formData.niveau_id || !formData.annee_id) {
            showAlert('error', 'Données manquantes pour la sauvegarde');
            return;
        }
        
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
                if (response.success) {
                    showAlert('success', response.message);
                    
                    // Fermer le modal
                    $('#volumeConfigModal').modal('hide');
                    
                    // Recharger la page pour mettre à jour les données de planification
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('error', response.message || 'Erreur lors de la sauvegarde');
                }
            },
            error: function(xhr) {
                debugError('Erreur AJAX:', xhr);
                let message = 'Erreur lors de la sauvegarde';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    message = errors.join(', ');
                }
                
                showAlert('error', message);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Sauvegarder');
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
        
        // Insérer l'alerte au début du container principal
        $('.container-fluid').prepend($alert);
        
        // Auto-dismiss après 5 secondes
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }
</script>
@endsection
