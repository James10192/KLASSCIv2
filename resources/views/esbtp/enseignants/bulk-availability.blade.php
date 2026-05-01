@extends('layouts.app')

@section('title', 'Modification rapide des disponibilités')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .bulk-availability-header {
        background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }

    .bulk-availability-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 60%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }

    .bulk-availability-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 var(--space-sm) 0;
        position: relative;
        z-index: 1;
    }

    .bulk-availability-header p {
        opacity: 0.9;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .accordion-item {
        border: none;
        border-radius: var(--radius-large) !important;
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .main-card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        padding: var(--space-lg);
        border-bottom: 1px solid var(--border);
    }

    .main-card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .main-card-title i {
        color: var(--primary);
    }

    .accordion-toggle {
        border: none;
        background: var(--surface-secondary);
        border-radius: var(--radius-medium);
        padding: var(--space-sm) var(--space-md);
        transition: all 0.2s ease;
    }

    .accordion-toggle:hover {
        background: var(--primary);
        color: white;
    }

    .accordion-toggle-icon {
        transition: transform 0.3s ease;
    }

    .accordion-button:not(.collapsed) .accordion-toggle-icon {
        transform: rotate(180deg);
    }

    .accordion-body {
        padding: 0;
    }

    /* =============================================
       CALENDRIER HEBDOMADAIRE - DESIGN MODERNE
       ============================================= */

    .availability-grid {
        display: grid;
        grid-template-columns: 72px repeat(7, 1fr);
        background: #ffffff;
        padding: 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        gap: 0;
        overflow: hidden;
    }

    /* En-tête "Horaires" */
    .availability-time-header {
        grid-column: 1;
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        text-align: center;
        padding: 10px 4px;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        border-right: 2px solid #e2e8f0;
    }

    /* En-têtes des jours */
    .availability-day-header {
        text-align: center;
        font-weight: 700;
        font-size: 0.8rem;
        color: #1e40af;
        padding: 10px 4px;
        background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
        border-bottom: 2px solid #e2e8f0;
        border-right: 1px solid #f1f5f9;
        letter-spacing: 0.3px;
    }

    .availability-day-header:last-of-type {
        border-right: none;
    }

    /* Créneaux horaires (colonne gauche) */
    .availability-time-slot {
        text-align: center;
        padding: 0 4px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        border-right: 2px solid #e2e8f0;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        min-height: 38px;
    }

    /* Cellules du calendrier */
    .availability-slot {
        padding: 4px 2px;
        text-align: center;
        font-size: 0.72rem;
        font-weight: 600;
        transition: all 0.15s ease;
        min-height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f1f5f9;
        position: relative;
        gap: 3px;
        user-select: none;
    }

    .availability-slot:last-child {
        border-right: none;
    }

    /* Statut: Disponible */
    .availability-slot.available {
        background: #dcfce7;
        color: #15803d;
        border-bottom-color: #bbf7d0;
    }

    .availability-slot.available i {
        color: #16a34a;
        font-size: 0.7rem;
    }

    /* Statut: Préféré */
    .availability-slot.preferred {
        background: #dbeafe;
        color: #1d4ed8;
        border-bottom-color: #bfdbfe;
    }

    .availability-slot.preferred i {
        color: #2563eb;
        font-size: 0.7rem;
    }

    /* Statut: Indisponible */
    .availability-slot.unavailable {
        background: #fee2e2;
        color: #991b1b;
        border-bottom-color: #fecaca;
    }

    .availability-slot.unavailable i {
        color: #dc2626;
        font-size: 0.65rem;
        opacity: 0.6;
    }

    /* Hover */
    .availability-slot:hover {
        filter: brightness(0.95);
        box-shadow: inset 0 0 0 2px rgba(0,0,0,0.08);
    }

    /* Cellule modifiée (en mode édition) */
    .availability-slot.modified {
        position: relative;
        box-shadow: inset 0 0 0 2px #f59e0b;
    }

    .availability-slot.modified::after {
        content: '';
        position: absolute;
        top: 3px;
        right: 3px;
        width: 6px;
        height: 6px;
        background: #f59e0b;
        border-radius: 50%;
    }

    /* Zebra striping léger pour les rangées */
    .availability-grid > .availability-time-slot:nth-of-type(odd),
    .availability-grid > .availability-slot:nth-child(14n+9),
    .availability-grid > .availability-slot:nth-child(14n+10),
    .availability-grid > .availability-slot:nth-child(14n+11),
    .availability-grid > .availability-slot:nth-child(14n+12),
    .availability-grid > .availability-slot:nth-child(14n+13),
    .availability-grid > .availability-slot:nth-child(14n+14) {
        /* Subtle zebra via slightly different border */
    }

    /* =============================================
       LÉGENDE
       ============================================= */
    .availability-legend {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        color: #475569;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
    }

    /* =============================================
       STATS BADGES
       ============================================= */
    .availability-stats {
        display: flex;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .stat-mini {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 500;
    }

    .stat-mini.stat-preferred {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .stat-mini.stat-available {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .stat-mini.stat-unavailable {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .stat-mini .stat-value {
        font-weight: 700;
        font-size: 0.95rem;
    }

    /* =============================================
       BLOC ENSEIGNANT
       ============================================= */
    .bulk-enseignant-block {
        background: #ffffff;
        padding: 20px;
    }

    .bulk-enseignant-block .availability-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .btn-edit-availability,
    .btn-save-availability,
    .btn-cancel-availability {
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-edit-availability {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
    }

    .btn-edit-availability:hover {
        box-shadow: 0 4px 8px rgba(37, 99, 235, 0.4);
        transform: translateY(-1px);
    }

    .btn-save-availability {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(22, 163, 74, 0.3);
    }

    .btn-save-availability:hover {
        box-shadow: 0 4px 8px rgba(22, 163, 74, 0.4);
        transform: translateY(-1px);
    }

    .btn-cancel-availability {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .btn-cancel-availability:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }

    /* =============================================
       RESPONSIVE
       ============================================= */
    @media (max-width: 768px) {
        .availability-grid {
            grid-template-columns: 52px repeat(7, 1fr);
            padding: 8px;
            font-size: 0.65rem;
        }

        .availability-day-header {
            padding: 6px 2px;
            font-size: 0.68rem;
        }

        .availability-time-slot {
            font-size: 0.65rem;
            padding: 0 2px;
        }

        .availability-slot {
            min-height: 32px;
            font-size: 0.6rem;
            padding: 2px 1px;
        }

        .availability-slot .slot-label {
            display: none;
        }

        .availability-stats {
            gap: 6px;
        }

        .stat-mini {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
    }

    @media (max-width: 480px) {
        .availability-grid {
            grid-template-columns: 44px repeat(7, 1fr);
        }

        .availability-slot {
            min-height: 28px;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="bulk-availability-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1><i class="fas fa-calendar-check me-2"></i>Modification rapide des disponibilités</h1>
                    <p>Modifiez les disponibilités de plusieurs enseignants en une seule page.</p>
                </div>
                <div>
                    <a href="{{ route('esbtp.enseignants.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Accordéon des enseignants -->
        <div class="accordion" id="bulkAvailabilityAccordion">
            @forelse($enseignantsData as $data)
                @php
                    $enseignant = $data['enseignant'];
                    $collapseId = 'collapse-enseignant-' . $enseignant->id;
                    $headingId = 'heading-enseignant-' . $enseignant->id;
                @endphp
                <div class="accordion-item mb-3" id="enseignant-block-{{ $enseignant->id }}">
                    <h2 class="accordion-header" id="{{ $headingId }}">
                        <div class="main-card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <div class="main-card-title">
                                    <i class="fas fa-user-tie"></i>
                                    {{ $enseignant->user->name ?? 'Enseignant #' . $enseignant->id }}
                                </div>
                                <div class="text-muted small">
                                    {{ $enseignant->specialization ?? 'Spécialisation non définie' }}
                                    · Matricule: {{ $enseignant->matricule ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $enseignant->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $enseignant->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                                <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant->id]) }}"
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>Voir
                                </a>
                                <button class="btn btn-sm btn-outline-secondary accordion-toggle"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}" aria-expanded="true">
                                    <i class="fas fa-chevron-down accordion-toggle-icon"></i>
                                </button>
                            </div>
                        </div>
                    </h2>
                    <div id="{{ $collapseId }}" class="accordion-collapse collapse show"
                         aria-labelledby="{{ $headingId }}">
                        <div class="accordion-body p-0">
                            @include('esbtp.enseignants.partials.availability-block', $data)
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Aucun enseignant sélectionné.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer le toggle des accordéons
    document.querySelectorAll('.accordion-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const icon = this.querySelector('.accordion-toggle-icon');
            icon.style.transform = icon.style.transform === 'rotate(180deg)' ? '' : 'rotate(180deg)';
        });
    });
});

// Fonction pour rafraîchir un bloc enseignant via AJAX
async function refreshBlock(enseignantId) {
    try {
        const response = await fetch(`{{ url('/esbtp/enseignants') }}/${enseignantId}/availability-section`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) {
            throw new Error('Erreur lors du rafraîchissement');
        }
        const payload = await response.json();
        const container = document.querySelector(`#enseignant-block-${enseignantId} .accordion-body`);
        if (container && payload.html) {
            container.innerHTML = payload.html;
        }
    } catch (error) {
        console.error('Erreur refresh:', error);
    }
}

// Notification toast
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'danger' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        font-weight: 500;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => { notification.style.transform = 'translateX(0)'; }, 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => { document.body.removeChild(notification); }, 300);
    }, 4000);
}
</script>
@endsection
