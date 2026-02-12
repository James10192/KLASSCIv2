@extends('layouts.app')

@section('title', 'Profil Enseignant - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .teacher-profile {
        padding: 0;
    }
    
    .profile-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-xl);
        position: relative;
        overflow: hidden;
        border-radius: var(--radius-large);
        box-shadow: var(--shadow-card);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-xl);
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .profile-hero {
        display: flex;
        align-items: center;
        gap: var(--space-lg);
        position: relative;
        z-index: 2;
        flex: 1;
        min-width: 0;
        flex-wrap: wrap;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: bold;
        border: 4px solid rgba(255,255,255,0.3);
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    
    .profile-info h1 {
        margin: 0 0 var(--space-xs) 0;
        font-size: 2rem;
        font-weight: 700;
    }
    
    .profile-info p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .profile-meta {
        display: flex;
        gap: var(--space-md);
        margin-top: var(--space-md);
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.9rem;
    }
    
    .meta-item i {
        line-height: 1;
        transform: translateY(1px);
    }
    
    .profile-actions {
        position: static;
        display: flex;
        gap: var(--space-sm);
        z-index: 3;
        flex-shrink: 0;
    }
    
    .profile-content {
        padding: var(--space-xl);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--space-xl);
    }
    
    .info-section {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        border: 1px solid var(--border);
    }
    
    .section-title {
        color: var(--primary);
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 0.9rem;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-sm) 0;
        border-bottom: 1px solid var(--border);
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 500;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .info-value {
        color: var(--text-primary);
        font-weight: 500;
    }
    
    .status-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }
    
    .status-inactive {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }
    
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(255,255,255,0.85));
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        text-align: center;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: var(--space-xs);
        min-height: 110px;
    }
    
    .stat-number {
        font-size: 1.7rem;
        font-weight: 700;
        color: var(--primary);
        display: block;
        line-height: 1.1;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
        line-height: 1.3;
    }

    .teaching-overview {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        border: 1px solid var(--border);
        margin-bottom: var(--space-xl);
    }

    .teaching-overview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-lg);
        margin-bottom: var(--space-lg);
    }

    .teaching-overview-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    /* =============================================
       TEACHING SUMMARY KPI CARDS — Modern KLASSCI
       ============================================= */
    .teaching-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .teaching-summary-card {
        background: #fff;
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        display: flex;
        flex-direction: column;
        gap: 4px;
        position: relative;
        overflow: hidden;
        text-align: left;
    }

    .teaching-summary-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--kpi-color, #0453cb);
        border-radius: 14px 14px 0 0;
    }

    .teaching-summary-card:nth-child(1) { --kpi-color: #0453cb; }
    .teaching-summary-card:nth-child(2) { --kpi-color: #5e91de; }
    .teaching-summary-card:nth-child(3) { --kpi-color: #10b981; }
    .teaching-summary-card:nth-child(4) { --kpi-color: #f59e0b; }

    .teaching-summary-card .kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin-bottom: 4px;
        background: color-mix(in srgb, var(--kpi-color, #0453cb) 12%, transparent);
        color: var(--kpi-color, #0453cb);
    }

    .teaching-summary-card .value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .teaching-summary-card .label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
    }

    /* =============================================
       CLASS TEACHING CARDS — Modern design
       ============================================= */
    .class-teaching-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        margin-bottom: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        overflow: hidden;
    }

    .class-teaching-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    .class-teaching-title a {
        color: #0453cb;
        font-weight: 700;
        text-decoration: none;
        font-size: 1rem;
    }

    .class-teaching-meta {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 2px;
    }

    .class-teaching-stats {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .class-teaching-stat {
        text-align: center;
        min-width: 70px;
    }

    .class-teaching-stat .value {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
    }

    .class-teaching-stat .label {
        font-size: 0.72rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 600;
    }

    .class-teaching-label {
        font-size: 0.72rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 600;
    }

    .class-teaching-body {
        padding: 16px 20px;
    }

    /* =============================================
       MATIÈRE TEACHING CARDS — 2-column layout
       ============================================= */
    .matiere-teaching-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 16px 20px;
        margin-bottom: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
        display: grid;
        grid-template-columns: 1fr 220px;
        gap: 20px;
        align-items: center;
        transition: box-shadow .2s;
    }

    .matiere-teaching-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,.08);
    }

    .matiere-teaching-left {
        min-width: 0;
    }

    .matiere-teaching-right {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .matiere-teaching-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .matiere-teaching-title {
        font-weight: 700;
        color: #1e293b;
        font-size: .95rem;
    }

    .matiere-teaching-stats {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .matiere-teaching-stats .value {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
    }

    /* Progress bar — modern KLASSCI */
    .matiere-progress {
        height: 10px;
        background: #f1f5f9;
        border-radius: 99px;
        overflow: hidden;
        margin-top: 4px;
    }

    .matiere-progress-fill {
        height: 100%;
        border-radius: 99px;
        transition: width .7s cubic-bezier(.4,0,.2,1);
    }

    .matiere-progress-fill.level-low  { background: linear-gradient(90deg, #fca5a5, #ef4444); }
    .matiere-progress-fill.level-mid  { background: linear-gradient(90deg, #fcd34d, #f59e0b); }
    .matiere-progress-fill.level-good { background: linear-gradient(90deg, #6ee7b7, #10b981); }
    .matiere-progress-fill.level-done { background: linear-gradient(90deg, #93c5fd, #0453cb); }

    /* Percent badge pill */
    .teaching-percent-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        padding: 3px 10px;
        border-radius: 99px;
        font-size: .82rem;
        font-weight: 700;
        white-space: nowrap;
    }
    
    .sidebar-actions {
        position: sticky;
        top: var(--space-lg);
    }
    
    .action-card {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        border: 1px solid var(--border);
    }
    
    .action-grid {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }
    
    .bio-text {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        color: var(--text-secondary);
        font-style: italic;
        line-height: 1.6;
        border-left: 4px solid var(--primary);
    }
    
    .timeline-item {
        display: flex;
        gap: var(--space-md);
        padding: var(--space-md) 0;
        border-bottom: 1px solid var(--border);
    }
    
    .timeline-item:last-child {
        border-bottom: none;
    }
    
    .timeline-date {
        flex-shrink: 0;
        width: 80px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .timeline-content {
        flex: 1;
    }
    
    .timeline-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .timeline-desc {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    
    .skills-grid {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
    }
    
    .skill-tag {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-sm) 0;
        border-bottom: 1px solid var(--border);
    }
    
    .contact-item:last-child {
        border-bottom: none;
    }
    
    .contact-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    .availability-mini-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        margin-top: var(--space-sm);
    }
    
    .availability-mini-day {
        text-align: center;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--text-secondary);
        padding: var(--space-xs);
    }
    
    .availability-mini-slot {
        height: 4px;
        background: var(--border);
        border-radius: var(--radius-small);
    }
    
    .availability-mini-slot.available {
        background: var(--success);
    }
    
    .availability-mini-slot.preferred {
        background: var(--primary);
    }
    
    /* Section de disponibilité principale */
    .availability-main-section {
        grid-column: 1 / -1;
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin: var(--space-xl) 0;
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-card);
    }
    
    .availability-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-lg);
    }
    
    .availability-title {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }
    
    .availability-title h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    
    .availability-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: var(--shadow-medium);
    }
    
    .availability-grid {
        display: grid;
        grid-template-columns: 100px repeat(7, 1fr);
        gap: var(--space-sm);
        background: white;
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        border: 1px solid var(--border-light);
    }
    
    .availability-time-header {
        grid-column: 1;
        font-weight: 600;
        text-align: center;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .availability-day-header {
        text-align: center;
        font-weight: 700;
        color: var(--primary);
        padding: var(--space-sm);
        background: rgba(var(--primary-rgb), 0.1);
        border-radius: var(--radius-small);
        font-size: 0.9rem;
    }
    
    .availability-time-slot {
        text-align: center;
        padding: var(--space-sm);
        font-size: 0.8rem;
        color: var(--text-secondary);
        border-right: 1px solid var(--border-light);
    }
    
    .availability-slot {
        padding: var(--space-sm);
        text-align: center;
        border-radius: var(--radius-small);
        font-size: 0.8rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .availability-slot.available {
        background: var(--success);
        color: white;
        font-weight: 600;
    }
    
    .availability-slot.preferred {
        background: var(--primary);
        color: white;
        font-weight: 600;
    }
    
    .availability-slot.unavailable {
        background: var(--border);
        color: var(--text-muted);
    }
    
    .availability-legend {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        margin-top: var(--space-lg);
        flex-wrap: wrap;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.9rem;
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: var(--radius-small);
    }
    
    /* Styles pour les boutons d'édition de disponibilité */
    .availability-actions {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
    }
    
    .btn-edit-availability {
        background: var(--primary);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-edit-availability:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
    }
    
    .btn-save-availability {
        background: var(--success);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-save-availability:hover {
        background: var(--success-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--success-rgb), 0.3);
    }
    
    .btn-cancel-availability {
        background: var(--danger);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-cancel-availability:hover {
        background: var(--danger-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--danger-rgb), 0.3);
    }
    
    /* Mode édition pour les créneaux */
    .availability-slot.editable {
        cursor: pointer;
        border: 2px solid transparent;
        position: relative;
    }
    
    .availability-slot.editable:hover {
        border-color: var(--primary);
        transform: scale(1.05);
    }
    
    .availability-slot.editable.available:hover {
        border-color: white;
        box-shadow: 0 0 0 2px var(--success);
    }
    
    .availability-slot.editable.preferred:hover {
        border-color: white;
        box-shadow: 0 0 0 2px var(--primary);
    }
    
    .availability-slot.editable.unavailable:hover {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px var(--primary);
    }
    
    /* Indicateur de modification */
    .availability-slot.modified::after {
        content: '●';
        position: absolute;
        top: 2px;
        right: 2px;
        color: var(--warning);
        font-size: 0.6rem;
    }
    
    @media (max-width: 1024px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-hero {
            flex-direction: column;
            text-align: center;
        }
        
        .profile-actions {
            justify-content: center;
            margin-top: var(--space-md);
        }
    }
    
    @media (max-width: 768px) {
        .profile-meta {
            flex-direction: column;
            gap: var(--space-sm);
        }

        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .teaching-summary {
            grid-template-columns: repeat(2, 1fr);
        }

        .matiere-teaching-card {
            grid-template-columns: 1fr;
        }

        .class-teaching-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const overviewContainer = document.getElementById('teaching-overview-content');
    if (!overviewContainer) {
        return;
    }

    const fetchTeachingOverview = (periode) => {
        const url = new URL(window.location.href);
        url.searchParams.set('periode', periode);

        return fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextOverview = doc.querySelector('#teaching-overview-content');
                if (nextOverview) {
                    overviewContainer.innerHTML = nextOverview.innerHTML;
                }
                window.history.replaceState({}, '', url.toString());
            })
            .catch(() => {
                window.location.href = url.toString();
            });
    };

    overviewContainer.addEventListener('click', (event) => {
        const button = event.target.closest('button[name="periode"]');
        if (!button) {
            return;
        }
        event.preventDefault();
        fetchTeachingOverview(button.value);
    });
});
</script>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="teacher-profile">
            <!-- En-tête du profil -->
            <div class="profile-header">
                <div class="profile-hero">
                    <div class="profile-avatar">
                        {{ $teacher->user ? substr($teacher->user->name, 0, 2) : 'NN' }}
                    </div>
                    <div class="profile-info">
                        <h1>{{ $teacher->user->name ?? 'Nom non disponible' }}</h1>
                        <p>{{ $teacher->specialization }}</p>
                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-id-card"></i>
                                <span>{{ $teacher->matricule }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-building"></i>
                                <span>{{ $teacher->department->name ?? 'Aucun département' }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Depuis {{ $teacher->created_at ? $teacher->created_at->format('M Y') : 'Non disponible' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="{{ route('esbtp.enseignants.edit', ['enseignant' => $teacher->id]) }}" class="btn-acasi primary">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </a>
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                </div>
            </div>
            
            <!-- Contenu du profil -->
            <div class="profile-content">
                <!-- Statistiques rapides -->
                <div class="quick-stats">
                    <div class="stat-card">
                        <span class="stat-number">{{ (int)($teacher->teaching_hours_due ?? 0) }}</span>
                        <div class="stat-label">Heures dues</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{ $teacher->seancesCours->count() ?? 0 }}</span>
                        <div class="stat-label">Séances données</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{ $profileData->annees_experience_enseignement ?? 0 }}</span>
                        <div class="stat-label">Années d'expérience</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number status-badge {{ $teacher->status === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                        </span>
                        <div class="stat-label">Statut</div>
                    </div>
                </div>

                <div id="teaching-overview-content" class="teaching-overview">
                    <div class="teaching-overview-header">
                        <div class="teaching-overview-title">
                            <i class="fas fa-chalkboard"></i>
                            Charge pédagogique par classe
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">{{ $anneeCourante?->name ?? 'Année en cours' }}</span>
                            <form method="GET" action="{{ route('esbtp.enseignants.show', ['enseignant' => $teacher->id]) }}" class="d-flex gap-2">
                                <button type="submit" name="periode" value="semestre1" class="btn btn-sm btn-outline-primary {{ ($periode ?? 'annee') === 'semestre1' ? 'active' : '' }}">S1</button>
                                <button type="submit" name="periode" value="semestre2" class="btn btn-sm btn-outline-primary {{ ($periode ?? 'annee') === 'semestre2' ? 'active' : '' }}">S2</button>
                                <button type="submit" name="periode" value="annee" class="btn btn-sm btn-outline-primary {{ ($periode ?? 'annee') === 'annee' ? 'active' : '' }}">Année</button>
                            </form>
                        </div>
                    </div>

                    <div class="teaching-summary">
                        <div class="teaching-summary-card">
                            <div class="kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="value">{{ $teachingPlanning['stats']['classes'] ?? 0 }}</div>
                            <div class="label">Classes enseignées</div>
                        </div>
                        <div class="teaching-summary-card">
                            <div class="kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="value">{{ number_format($teachingPlanning['stats']['heures_planifiees'] ?? 0, 1) }}h</div>
                            <div class="label">Heures planifiées</div>
                        </div>
                        <div class="teaching-summary-card">
                            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="value">{{ number_format($teachingPlanning['stats']['heures_realisees'] ?? 0, 1) }}h</div>
                            <div class="label">Heures réalisées</div>
                        </div>
                        <div class="teaching-summary-card">
                            <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="value">{{ $teachingPlanning['stats']['taux_realisation'] ?? 0 }}%</div>
                            <div class="label">Taux de réalisation</div>
                        </div>
                    </div>

                    @if(!empty($teachingPlanning['classes']) && $teachingPlanning['classes']->isNotEmpty())
                        @foreach($teachingPlanning['classes'] as $classeData)
                            @php
                                $classe = $classeData['classe'];
                                $collapseId = 'teacher-class-' . $classe->id;
                            @endphp
                            @php
                                $classTaux = $classeData['stats']['taux_realisation'] ?? 0;
                                $classBadgeBg = $classTaux >= 100 ? '#dbeafe' : ($classTaux >= 75 ? '#d1fae5' : ($classTaux >= 40 ? '#fef3c7' : '#fee2e2'));
                                $classBadgeTxt = $classTaux >= 100 ? '#1d4ed8' : ($classTaux >= 75 ? '#065f46' : ($classTaux >= 40 ? '#92400e' : '#991b1b'));
                            @endphp
                            <div class="class-teaching-card">
                                <div class="class-teaching-header">
                                    <div>
                                        <div class="class-teaching-title">
                                            <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}">
                                                {{ $classe->name }}
                                            </a>
                                        </div>
                                        <div class="class-teaching-meta">
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $classe->filiere->name ?? 'N/A' }}</span>
                                            · {{ $classe->niveau->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="class-teaching-stats">
                                        <div class="class-teaching-stat">
                                            <div class="value">{{ number_format($classeData['stats']['heures_planifiees'], 1) }}h</div>
                                            <div class="label">Planifiées</div>
                                        </div>
                                        <div class="class-teaching-stat">
                                            <div class="value">{{ number_format($classeData['stats']['heures_realisees'], 1) }}h</div>
                                            <div class="label">Réalisées</div>
                                        </div>
                                        <div class="class-teaching-stat">
                                            <div class="value">
                                                <span class="teaching-percent-badge" style="background:{{ $classBadgeBg }};color:{{ $classBadgeTxt }};">{{ $classTaux }}%</span>
                                            </div>
                                            <div class="label">Réalisation</div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="true" aria-controls="{{ $collapseId }}">
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                </div>
                                <div id="{{ $collapseId }}" class="collapse show">
                                    <div class="class-teaching-body">
                                    @if($classeData['matieres']->isNotEmpty())
                                        @foreach($classeData['matieres'] as $matiere)
                                            @php
                                                $mpct = $matiere['pourcentage_realise'] ?? 0;
                                                $mlevel = $mpct >= 100 ? 'level-done' : ($mpct >= 75 ? 'level-good' : ($mpct >= 40 ? 'level-mid' : 'level-low'));
                                                $mbg = $mpct >= 100 ? '#dbeafe' : ($mpct >= 75 ? '#d1fae5' : ($mpct >= 40 ? '#fef3c7' : '#fee2e2'));
                                                $mtxt = $mpct >= 100 ? '#1d4ed8' : ($mpct >= 75 ? '#065f46' : ($mpct >= 40 ? '#92400e' : '#991b1b'));
                                            @endphp
                                            <div class="matiere-teaching-card">
                                                <!-- Left: name + progress -->
                                                <div class="matiere-teaching-left">
                                                    <div class="matiere-teaching-header">
                                                        <div class="matiere-teaching-title">{{ $matiere['matiere']->name ?? 'Matière inconnue' }}</div>
                                                        @if($matiere['est_configure'])
                                                            <span class="teaching-percent-badge" style="background:{{ $mbg }};color:{{ $mtxt }};">{{ $mpct }}%</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark" style="font-size:.72rem;">Non configuré</span>
                                                        @endif
                                                    </div>
                                                    <div class="matiere-progress">
                                                        <div class="matiere-progress-fill {{ $mlevel }}" style="width: {{ min($mpct, 100) }}%"></div>
                                                    </div>
                                                    @if($matiere['est_configure'])
                                                        <div class="d-flex justify-content-between mt-1">
                                                            <small style="color:#10b981;font-size:.75rem;">✓ {{ number_format($matiere['heures_realisees'], 1) }}h réalisées</small>
                                                            @if($matiere['heures_restantes'] > 0)
                                                                <small style="color:#f59e0b;font-size:.75rem;">⏱ {{ number_format($matiere['heures_restantes'], 1) }}h restantes</small>
                                                            @else
                                                                <small style="color:#10b981;font-size:.75rem;">✅ Objectif atteint</small>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                <!-- Right: KPIs -->
                                                <div class="matiere-teaching-right">
                                                    <div class="matiere-teaching-stats">
                                                        <div>
                                                            <div class="value">{{ number_format($matiere['heures_realisees'], 1) }}h</div>
                                                            <div class="class-teaching-label">Réalisées</div>
                                                        </div>
                                                        <div>
                                                            <div class="value">{{ number_format($matiere['heures_planifiees'], 1) }}h</div>
                                                            <div class="class-teaching-label">Planifiées</div>
                                                        </div>
                                                        <div>
                                                            <div class="value">{{ $matiere['nb_seances'] }}</div>
                                                            <div class="class-teaching-label">Séances</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-3 text-muted">Aucune matière trouvée pour cette classe.</div>
                                    @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4 text-muted">
                            Aucun cours trouvé pour cet enseignant sur l'année en cours.
                        </div>
                    @endif
                </div>
                
                <div class="info-grid">
                    <!-- Colonne principale -->
                    <div class="main-info">
                        <!-- Informations personnelles -->
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                Informations Personnelles
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Nom complet</span>
                                <span class="info-value">{{ $teacher->user->name ?? 'Non disponible' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ $teacher->user->email ?? 'Non disponible' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Téléphone</span>
                                <span class="info-value">{{ $teacher->user->phone ?? 'Non renseigné' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Titre académique</span>
                                <span class="info-value">{{ $profileData->titre_academique ?? 'Non renseigné' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Grade académique</span>
                                <span class="info-value">{{ $profileData->grade_academique ?? 'Non renseigné' }}</span>
                            </div>
                        </div>
                        
                        <!-- Qualifications -->
                        @if($profileData)
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                Qualifications & Formation
                            </div>
                            
                            @if($profileData->diplome_principal)
                            <div class="info-row">
                                <span class="info-label">Diplôme principal</span>
                                <span class="info-value">{{ $profileData->diplome_principal }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->universite_diplome)
                            <div class="info-row">
                                <span class="info-label">Université</span>
                                <span class="info-value">{{ $profileData->universite_diplome }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->annee_diplome)
                            <div class="info-row">
                                <span class="info-label">Année d'obtention</span>
                                <span class="info-value">{{ $profileData->annee_diplome }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->annees_experience_enseignement)
                            <div class="info-row">
                                <span class="info-label">Expérience enseignement</span>
                                <span class="info-value">{{ $profileData->annees_experience_enseignement }} années</span>
                            </div>
                            @endif
                            
                            @if($profileData->annees_experience_professionnelle)
                            <div class="info-row">
                                <span class="info-label">Expérience professionnelle</span>
                                <span class="info-value">{{ $profileData->annees_experience_professionnelle }} années</span>
                            </div>
                            @endif
                        </div>
                        @endif
                        
                        <!-- Informations professionnelles -->
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                Informations Professionnelles
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Spécialisation</span>
                                <span class="info-value">{{ $teacher->specialization }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Département</span>
                                <span class="info-value">{{ $teacher->department->name ?? 'Non assigné' }}</span>
                            </div>
                            @if($teacher->laboratory)
                            <div class="info-row">
                                <span class="info-label">Laboratoire</span>
                                <span class="info-value">{{ $teacher->laboratory->name ?? 'Non disponible' }}</span>
                            </div>
                            @endif
                            <div class="info-row">
                                <span class="info-label">Heures d'enseignement</span>
                                <span class="info-value">{{ (int)($teacher->teaching_hours_due ?? 0) }}h/semaine</span>
                            </div>
                            @if($profileData && $profileData->type_contrat)
                            <div class="info-row">
                                <span class="info-label">Type de contrat</span>
                                <span class="info-value">{{ ucfirst($profileData->type_contrat) }}</span>
                            </div>
                            @endif
                            @if($profileData && $profileData->statut_emploi)
                            <div class="info-row">
                                <span class="info-label">Statut d'emploi</span>
                                <span class="info-value">{{ str_replace('_', ' ', ucfirst($profileData->statut_emploi)) }}</span>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Biographie -->
                        @if($teacher->bio)
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                À propos
                            </div>
                            
                            <div class="bio-text">
                                {{ $teacher->bio }}
                            </div>
                        </div>
                        @endif
                        
                        <!-- Motivation et objectifs -->
                        @if($profileData && ($profileData->motivation || $profileData->objectifs_pedagogiques))
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                Motivation & Objectifs
                            </div>
                            
                            @if($profileData->motivation)
                            <div class="info-row">
                                <span class="info-label">Motivation</span>
                                <span class="info-value">{{ $profileData->motivation }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->objectifs_pedagogiques)
                            <div class="info-row">
                                <span class="info-label">Objectifs pédagogiques</span>
                                <span class="info-value">{{ $profileData->objectifs_pedagogiques }}</span>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    
                    <!-- Barre latérale -->
                    <div class="sidebar-actions">
                        <!-- Actions rapides -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                Actions Rapides
                            </div>
                            
                            <div class="action-grid">
                                <a href="{{ route('esbtp.enseignants.edit', ['enseignant' => $teacher->id]) }}" class="btn-acasi primary">
                                    <i class="fas fa-edit me-2"></i>Modifier le profil
                                </a>

                                <a href="{{ route('esbtp.enseignants.matieres', ['teacher' => $teacher]) }}" class="btn-acasi secondary">
                                    <i class="fas fa-book me-2"></i>Gérer les matières
                                </a>

                                @if($teacher->status === 'active')
                                <form action="{{ route('esbtp.enseignants.toggleStatus', ['teacher' => $teacher]) }}" method="POST" 
                                      onsubmit="return confirm('Désactiver cet enseignant ?')">
                                    @csrf
                                    <button type="submit" class="btn-acasi warning w-100">
                                        <i class="fas fa-pause me-2"></i>Désactiver
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('esbtp.enseignants.toggleStatus', ['teacher' => $teacher]) }}" method="POST" 
                                      onsubmit="return confirm('Activer cet enseignant ?')">
                                    @csrf
                                    <button type="submit" class="btn-acasi success w-100">
                                        <i class="fas fa-play me-2"></i>Activer
                                    </button>
                                </form>
                                @endif
                                
                                <form action="{{ route('esbtp.enseignants.destroy', ['enseignant' => $teacher]) }}" method="POST" 
                                      onsubmit="return confirm('Supprimer définitivement cet enseignant ? Cette action est irréversible.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-acasi danger w-100">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Informations de contact -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-address-card"></i>
                                </div>
                                Contact
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <a href="mailto:{{ $teacher->user->email ?? '#' }}" class="info-value">
                                    {{ $teacher->user->email ?? 'Non disponible' }}
                                </a>
                            </div>
                            
                            @if($teacher->user && $teacher->user->phone)
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <a href="tel:{{ $teacher->user->phone }}" class="info-value">
                                    {{ $teacher->user->phone }}
                                </a>
                            </div>
                            @endif
                            
                            @if($teacher->website)
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <a href="{{ $teacher->website }}" target="_blank" class="info-value">
                                    Site web
                                </a>
                            </div>
                            @endif
                        </div>

                        <!-- Compte utilisateur -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                Compte utilisateur
                            </div>

                            @if(session('new_password'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 1rem;">
                                    <h6 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Mot de passe réinitialisé!</h6>
                                    <hr>
                                    <p class="mb-0"><strong>Nouveau mot de passe:</strong> <code class="text-dark">{{ session('new_password') }}</code></p>
                                    <hr>
                                    <p class="mb-0 small"><i class="fas fa-info-circle me-1"></i>Communiquez ces identifiants à l'enseignant.</p>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if($teacher->user)
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-success me-2">Actif</span>
                                    <span>{{ $teacher->user->email }}</span>
                                </div>
                                <div class="mb-3">
                                    <p><strong>Nom d'utilisateur:</strong> {{ $teacher->user->username ?: $teacher->user->email }}</p>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="showResetPasswordModal()">
                                        <i class="fas fa-key me-1"></i>Réinitialiser le mot de passe
                                    </button>
                                </div>
                            @else
                                <div class="alert alert-warning" style="margin-bottom: 0;">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Aucun compte utilisateur associé
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Section Disponibilités Principale -->
                    <div class="availability-main-section">
                        <div class="availability-header">
                            <div class="availability-title">
                                <div class="availability-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3>Disponibilités Hebdomadaires</h3>
                            </div>
                            <div class="availability-status">
                                <span class="status-badge {{ $teacher->status === 'active' ? 'success' : 'warning' }}">
                                    {{ $teacher->status === 'active' ? 'Disponible' : 'Non disponible' }}
                                </span>
                                <div class="availability-actions">
                                    <button id="editAvailabilityBtn" class="btn-edit-availability" onclick="toggleEditMode()">
                                        <i class="fas fa-edit me-1"></i>
                                        <span class="edit-text">Modifier</span>
                                    </button>
                                    <button id="saveAvailabilityBtn" class="btn-save-availability" onclick="saveAvailability()" style="display: none;">
                                        <i class="fas fa-save me-1"></i>
                                        Sauvegarder
                                    </button>
                                    <button id="cancelAvailabilityBtn" class="btn-cancel-availability" onclick="cancelEditMode()" style="display: none;">
                                        <i class="fas fa-times me-1"></i>
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="availability-grid">
                            <!-- En-têtes -->
                            <div class="availability-time-header">Horaires</div>
                            <div class="availability-day-header">Lundi</div>
                            <div class="availability-day-header">Mardi</div>
                            <div class="availability-day-header">Mercredi</div>
                            <div class="availability-day-header">Jeudi</div>
                            <div class="availability-day-header">Vendredi</div>
                            <div class="availability-day-header">Samedi</div>
                            <div class="availability-day-header">Dimanche</div>
                            
                            <!-- Créneaux horaires -->
                            @php
                                // Créneaux horaires par heure pour cohérence avec la page edit
                                $hours = range(8, 18); // 08:00 à 18:00
                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                
                                // Utiliser les vraies données de disponibilité préparées par le contrôleur
                                $availability = $realAvailability ?? [
                                    'monday' => array_fill(0, 11, 'unavailable'),    // 8h à 18h = 11 heures
                                    'tuesday' => array_fill(0, 11, 'unavailable'),
                                    'wednesday' => array_fill(0, 11, 'unavailable'),
                                    'thursday' => array_fill(0, 11, 'unavailable'),
                                    'friday' => array_fill(0, 11, 'unavailable'),
                                    'saturday' => array_fill(0, 11, 'unavailable'),
                                    'sunday' => array_fill(0, 11, 'unavailable')
                                ];
                            @endphp
                            
                            @foreach($hours as $index => $hour)
                                <div class="availability-time-slot">{{ sprintf('%02d:00', $hour) }}</div>
                                @foreach($days as $day)
                                    @php
                                        $status = $availability[$day][$index] ?? 'unavailable';
                                        $icon = $status === 'preferred' ? '★' : ($status === 'available' ? '✓' : '✗');
                                    @endphp
                                    <div class="availability-slot {{ $status }}" 
                                         id="slot-{{ $index }}-{{ array_search($day, $days) }}"
                                         data-day="{{ array_search($day, $days) }}" 
                                         data-hour="{{ $hour }}" 
                                         data-time-index="{{ $index }}" 
                                         data-original-status="{{ $status }}"
                                         title="{{ ucfirst($day) }} {{ sprintf('%02d:00', $hour) }} - {{ ucfirst($status) }}">
                                        {{ $icon }}
                                    </div>
                                @endforeach
                            @endforeach
                        </div>

                        @if(config('app.debug'))
                        <!-- DEBUG VISIBLE PAGE SHOW -->
                        <div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 2px solid #ffc107; border-radius: 5px;">
                            <h4>🔍 DEBUG PAGE SHOW - Données de disponibilité</h4>
                            <p><strong>Timestamp:</strong> {{ date('Y-m-d H:i:s') }}</p>
                            <p><strong>$realAvailability:</strong> {{ $realAvailability ? 'EXISTE' : 'NULL' }}</p>
                            <p><strong>Données depuis teacher->availabilities:</strong> {{ $teacher->availabilities ? $teacher->availabilities->count() : '0' }} éléments</p>
                            @if($teacher->availabilities && $teacher->availabilities->count() > 0)
                                <details>
                                    <summary>Voir les données brutes ({{ $teacher->availabilities->count() }} entrées)</summary>
                                    <pre style="background: white; padding: 5px; overflow-x: auto;">{{ json_encode($teacher->availabilities->toArray(), JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @endif
                            <details>
                                <summary>Voir les données finales utilisées pour l'affichage</summary>
                                <pre style="background: white; padding: 5px; overflow-x: auto;">{{ json_encode($availability, JSON_PRETTY_PRINT) }}</pre>
                            </details>

                            <script>
                            @if(config('app.debug'))
                            // DEBUG JavaScript sur page SHOW
                            document.addEventListener('DOMContentLoaded', function() {
                                debugLog('🔍 DEBUG PAGE SHOW CHARGÉE à {{ date('H:i:s') }}');

                                @if($teacher->availabilities && $teacher->availabilities->count() > 0)
                                let availCount = {{ $teacher->availabilities->count() }};
                                let debugShowInfo = `🔍 PAGE SHOW CHARGÉE\n\n`;
                                debugShowInfo += `Heure: {{ date('H:i:s') }}\n`;
                                debugShowInfo += `Disponibilités en DB: ${availCount} entrées\n`;

                                // Compter les créneaux par statut dans les données finales
                                let finalData = @json($availability);
                                let countByStatus = {available: 0, preferred: 0, unavailable: 0};
                                Object.keys(finalData).forEach(day => {
                                    finalData[day].forEach(status => {
                                        countByStatus[status]++;
                                    });
                                });

                                debugShowInfo += `Créneaux finaux:\n`;
                                debugShowInfo += `- Disponible: ${countByStatus.available}\n`;
                                debugShowInfo += `- Préféré: ${countByStatus.preferred}\n`;
                                debugShowInfo += `- Indisponible: ${countByStatus.unavailable}`;

                                // Afficher après 1 seconde pour laisser la page se charger
                                setTimeout(() => {
                                    if(confirm(debugShowInfo + '\n\nVoulez-vous voir les détails dans la console ?')) {
                                        debugLog('🔍 Données brutes DB:', @json($teacher->availabilities->toArray()));
                                        debugLog('🔍 Données finales:', finalData);
                                    }
                                }, 1000);
                                @endif
                            });
                            @endif
                            </script>
                        </div>
                        @endif

                        <!-- Légende -->
                        <div class="availability-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--primary);"></div>
                                <span>★ Créneaux préférés</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--success);"></div>
                                <span>✓ Disponible</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--border);"></div>
                                <span>✗ Non disponible</span>
                            </div>
                        </div>
                        
                        <!-- JavaScript pour l'édition de disponibilités -->
                        <script>
                        let isEditMode = false;
                        let originalData = {};
                        let modifiedSlots = new Set();
                        
                        function toggleEditMode() {
                            isEditMode = !isEditMode;
                            const slots = document.querySelectorAll('.availability-slot');
                            const editBtn = document.getElementById('editAvailabilityBtn');
                            const saveBtn = document.getElementById('saveAvailabilityBtn');
                            const cancelBtn = document.getElementById('cancelAvailabilityBtn');
                            
                            if (isEditMode) {
                                // Activer le mode édition
                                slots.forEach(slot => {
                                    slot.classList.add('editable');
                                    slot.onclick = () => toggleSlotStatus(slot);
                                    // Sauvegarder l'état original
                                    originalData[slot.id] = slot.dataset.originalStatus;
                                });
                                
                                editBtn.style.display = 'none';
                                saveBtn.style.display = 'flex';
                                cancelBtn.style.display = 'flex';
                                
                                // Changer le style du header pour indiquer le mode édition
                                document.querySelector('.availability-main-section').style.background = 'linear-gradient(135deg, #fef3c7, #fde68a)';
                                
                                // Afficher un message d'aide
                                showNotification('Mode édition activé. Cliquez sur les créneaux pour modifier la disponibilité.', 'info');
                            } else {
                                // Désactiver le mode édition
                                slots.forEach(slot => {
                                    slot.classList.remove('editable');
                                    slot.onclick = null;
                                });
                                
                                editBtn.style.display = 'flex';
                                saveBtn.style.display = 'none';
                                cancelBtn.style.display = 'none';
                                
                                document.querySelector('.availability-main-section').style.background = 'linear-gradient(135deg, #f8fafc, #e2e8f0)';
                            }
                        }
                        
                        function toggleSlotStatus(slot) {
                            if (!isEditMode) return;
                            
                            const statuses = ['unavailable', 'available', 'preferred'];
                            const icons = ['✗', '✓', '★'];
                            const currentClasses = Array.from(slot.classList);
                            let currentStatus = statuses.find(status => currentClasses.includes(status)) || 'unavailable';
                            
                            // Passer au statut suivant
                            const currentIndex = statuses.indexOf(currentStatus);
                            const nextIndex = (currentIndex + 1) % statuses.length;
                            const nextStatus = statuses[nextIndex];
                            
                            // Supprimer l'ancienne classe de statut
                            statuses.forEach(status => slot.classList.remove(status));
                            
                            // Ajouter la nouvelle classe
                            slot.classList.add(nextStatus);
                            
                            // Changer l'icône
                            slot.textContent = icons[nextIndex];
                            
                            // Marquer comme modifié si différent de l'original
                            if (nextStatus !== originalData[slot.id]) {
                                slot.classList.add('modified');
                                modifiedSlots.add(slot.id);
                            } else {
                                slot.classList.remove('modified');
                                modifiedSlots.delete(slot.id);
                            }
                            
                            // Mettre à jour le tooltip
                            const dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                            const hours = Array.from({length: 11}, (_, i) => String(i + 8).padStart(2, '0') + ':00'); // 08:00 à 18:00
                            const statusNames = { unavailable: 'Non disponible', available: 'Disponible', preferred: 'Préféré' };
                            
                            const dayIndex = parseInt(slot.dataset.day);
                            const timeIndex = parseInt(slot.dataset.timeIndex);
                            slot.title = `${dayNames[dayIndex]} ${hours[timeIndex]} - ${statusNames[nextStatus]}`;
                        }
                        
                        function cancelEditMode() {
                            // Restaurer les états originaux
                            modifiedSlots.forEach(slotId => {
                                const slot = document.getElementById(slotId);
                                const originalStatus = originalData[slotId];
                                const statuses = ['unavailable', 'available', 'preferred'];
                                const icons = ['✗', '✓', '★'];
                                
                                // Supprimer toutes les classes de statut
                                statuses.forEach(status => slot.classList.remove(status));
                                
                                // Restaurer le statut original
                                slot.classList.add(originalStatus);
                                slot.textContent = icons[statuses.indexOf(originalStatus)];
                                slot.classList.remove('modified');
                            });
                            
                            modifiedSlots.clear();
                            toggleEditMode();
                            showNotification('Modifications annulées', 'warning');
                        }
                        
                        function saveAvailability() {
                            if (modifiedSlots.size === 0) {
                                showNotification('Aucune modification à sauvegarder', 'warning');
                                return;
                            }
                            
                            // Préparer les données à envoyer
                            const changedSlots = [];
                            modifiedSlots.forEach(slotId => {
                                const slot = document.getElementById(slotId);
                                const statuses = ['unavailable', 'available', 'preferred'];
                                const currentStatus = statuses.find(status => slot.classList.contains(status));
                                
                                // Calculer les heures réelles à partir du timeIndex
                                const timeIndex = parseInt(slot.dataset.timeIndex);
                                const startHour = 8 + timeIndex; // timeIndex 0 = 8h, timeIndex 1 = 9h, etc.
                                const endHour = startHour + 1;   // Créneau d'1 heure
                                
                                changedSlots.push({
                                    day: parseInt(slot.dataset.day),
                                    startTime: String(startHour).padStart(2, '0') + ':00',
                                    endTime: String(endHour).padStart(2, '0') + ':00',
                                    status: currentStatus
                                });
                            });
                            
                            // Envoyer les données via AJAX
                            const teacherId = {{ $teacher->id }};
                            @if(config('app.debug'))
                            debugLog('Données à envoyer:', { changes: changedSlots });
                            @endif

                            fetch(`/esbtp/enseignants/${teacherId}/update-availability`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ changes: changedSlots })
                            })
                            .then(response => {
                                @if(config('app.debug'))
                                debugLog('Réponse reçue:', response);
                                @endif
                                return response.json();
                            })
                            .then(data => {
                                @if(config('app.debug'))
                                debugLog('Données reçues:', data);
                                @endif
                                if (data.success) {
                                    showNotification('Disponibilités mises à jour avec succès !', 'success');
                                    
                                    // Mettre à jour les données originales
                                    modifiedSlots.forEach(slotId => {
                                        const slot = document.getElementById(slotId);
                                        const statuses = ['unavailable', 'available', 'preferred'];
                                        const currentStatus = statuses.find(status => slot.classList.contains(status));
                                        originalData[slotId] = currentStatus;
                                        slot.dataset.originalStatus = currentStatus;
                                        slot.classList.remove('modified');
                                    });
                                    
                                    modifiedSlots.clear();
                                    toggleEditMode();
                                } else {
                                    showNotification('Erreur lors de la sauvegarde : ' + (data.message || 'Erreur inconnue'), 'danger');
                                }
                            })
                            .catch(error => {
                                debugError('Erreur complète:', error);
                                showNotification('Erreur de connexion lors de la sauvegarde: ' + error.message, 'danger');
                            });
                        }
                        
                        function showNotification(message, type) {
                            // Créer une notification toast
                            const notification = document.createElement('div');
                            notification.className = `notification toast-${type}`;
                            notification.style.cssText = `
                                position: fixed;
                                top: 20px;
                                right: 20px;
                                background: ${type === 'success' ? '#10b981' : type === 'danger' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
                                color: white;
                                padding: 12px 20px;
                                border-radius: 8px;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                                z-index: 1000;
                                font-weight: 500;
                                transform: translateX(100%);
                                transition: transform 0.3s ease;
                            `;
                            notification.textContent = message;
                            
                            document.body.appendChild(notification);
                            
                            // Animer l'entrée
                            setTimeout(() => {
                                notification.style.transform = 'translateX(0)';
                            }, 100);
                            
                            // Supprimer après 4 secondes
                            setTimeout(() => {
                                notification.style.transform = 'translateX(100%)';
                                setTimeout(() => {
                                    document.body.removeChild(notification);
                                }, 300);
                            }, 4000);
                        }
                        </script>
                    </div>
                    
                    <div class="info-grid">
                        
                        <!-- Informations système -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                Informations Système
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">ID</span>
                                <span class="info-value">#{{ $teacher->id }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Créé le</span>
                                <span class="info-value">{{ $teacher->created_at ? $teacher->created_at->format('d/m/Y') : 'Non disponible' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Modifié le</span>
                                <span class="info-value">{{ $teacher->updated_at ? $teacher->updated_at->format('d/m/Y') : 'Non disponible' }}</span>
                            </div>
                            @if($teacher->createdBy)
                            <div class="info-row">
                                <span class="info-label">Créé par</span>
                                <span class="info-value">{{ $teacher->createdBy->name ?? 'Non disponible' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Réinitialisation Mot de Passe -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Réinitialiser le mot de passe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="{{ route('esbtp.enseignants.reset-password', ['enseignant' => $teacher->id]) }}">
                @csrf
                <div class="modal-body" style="padding: 2rem;">
                    <!-- Alert warning -->
                    <div style="
                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                        border-left: 4px solid #f59e0b;
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #f59e0b, #d97706);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: #92400e; font-weight: 500; margin-bottom: 0.25rem;">Attention</div>
                                <div style="color: #78350f; font-size: 0.9rem;">
                                    Cette action va réinitialiser le mot de passe à <strong>"Bonjour@2025"</strong> pour l'enseignant
                                    <strong>{{ $teacher->user->name ?? 'l\'enseignant' }}</strong>. L'enseignant devra changer son mot de passe à la première connexion.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info enseignant -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                            <i class="fas fa-user me-1" style="color: #f59e0b;"></i>
                            Enseignant concerné
                        </label>
                        <div style="
                            background: #f8f9fa;
                            border: 2px solid #dee2e6;
                            border-radius: 8px;
                            padding: 0.75rem;
                            font-weight: 500;
                        ">
                            {{ $teacher->user->name ?? 'N/A' }} ({{ $teacher->user->email ?? 'N/A' }})
                        </div>
                    </div>

                    <!-- Confirmation mot de passe réinitialisé -->
                    <div id="newPasswordDisplay" style="display: none;" class="mb-3">
                        <label class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                            <i class="fas fa-check-circle me-1" style="color: #10b981;"></i>
                            Mot de passe réinitialisé
                        </label>
                        <div style="
                            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                            border: 2px solid #10b981;
                            border-radius: 8px;
                            padding: 1rem;
                            font-family: monospace;
                            font-size: 1.2rem;
                            font-weight: 700;
                            text-align: center;
                            color: #047857;
                            letter-spacing: 2px;
                        " id="newPasswordValue"></div>
                        <div class="form-text text-center mt-2" style="color: #047857;">
                            <i class="fas fa-info-circle me-1"></i>
                            Communiquez ce mot de passe à l'enseignant. Il devra le changer à la première connexion.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 15px 15px; padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 500;
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-warning" id="resetPasswordBtn" style="
                        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                        border: none;
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-key me-1"></i>Réinitialiser à Bonjour@2025
                    </button>
                    <button type="button" class="btn btn-primary" id="copyPasswordBtn" style="display: none;" onclick="copyPassword()">
                        <i class="fas fa-copy me-1"></i>Copier le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResetPasswordModal() {
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

// Gérer la soumission du formulaire en AJAX
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('resetPasswordBtn');
    const originalBtnText = submitBtn.innerHTML;

    // Désactiver le bouton et afficher un loader
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Génération...';

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher le nouveau mot de passe
            document.getElementById('newPasswordValue').textContent = data.password;
            document.getElementById('newPasswordDisplay').style.display = 'block';

            // Cacher le bouton de génération, afficher celui de copie
            submitBtn.style.display = 'none';
            document.getElementById('copyPasswordBtn').style.display = 'inline-block';

            // Notification succès
            showNotification('Mot de passe réinitialisé avec succès !', 'success');
        } else {
            showNotification('Erreur : ' + (data.message || 'Une erreur est survenue'), 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        debugError('Erreur:', error);
        showNotification('Erreur de connexion', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

function copyPassword() {
    const password = document.getElementById('newPasswordValue').textContent;
    navigator.clipboard.writeText(password).then(() => {
        showNotification('Mot de passe copié dans le presse-papiers !', 'success');
    }).catch(err => {
        debugError('Erreur copie:', err);
        showNotification('Erreur lors de la copie', 'danger');
    });
}

// Réinitialiser le modal quand il est fermé
document.getElementById('resetPasswordModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newPasswordDisplay').style.display = 'none';
    document.getElementById('newPasswordValue').textContent = '';
    document.getElementById('resetPasswordBtn').style.display = 'inline-block';
    document.getElementById('resetPasswordBtn').disabled = false;
    document.getElementById('resetPasswordBtn').innerHTML = '<i class="fas fa-key me-1"></i>Générer nouveau mot de passe';
    document.getElementById('copyPasswordBtn').style.display = 'none';
});
</script>
@endsection 
