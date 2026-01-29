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

    /* Grille de disponibilité */
    .availability-grid {
        display: grid;
        grid-template-columns: 80px repeat(7, 1fr);
        gap: 2px;
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
        font-size: 0.85rem;
    }

    .availability-day-header {
        text-align: center;
        font-weight: 700;
        color: var(--primary);
        padding: var(--space-sm);
        background: rgba(4, 83, 203, 0.1);
        border-radius: var(--radius-small);
        font-size: 0.85rem;
    }

    .availability-time-slot {
        text-align: center;
        padding: var(--space-xs);
        font-size: 0.75rem;
        color: var(--text-secondary);
        border-right: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .availability-slot {
        padding: var(--space-xs);
        text-align: center;
        border-radius: var(--radius-small);
        font-size: 0.75rem;
        transition: all 0.2s ease;
        cursor: pointer;
        min-height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
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

    .availability-slot:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .availability-slot.modified::after {
        content: '';
        position: absolute;
        top: 2px;
        right: 2px;
        width: 6px;
        height: 6px;
        background: var(--warning);
        border-radius: 50%;
    }

    /* Légende */
    .availability-legend {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        margin-top: var(--space-md);
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.85rem;
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: var(--radius-small);
    }

    /* Bloc enseignant */
    .bulk-enseignant-block {
        background: var(--surface);
        padding: var(--space-lg);
    }

    .bulk-enseignant-block .availability-actions {
        display: flex;
        gap: var(--space-sm);
        margin-bottom: var(--space-md);
        flex-wrap: wrap;
    }

    .btn-edit-availability,
    .btn-save-availability,
    .btn-cancel-availability {
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .btn-edit-availability {
        background: var(--primary);
        color: white;
    }

    .btn-edit-availability:hover {
        background: var(--primary-dark);
    }

    .btn-save-availability {
        background: var(--success);
        color: white;
    }

    .btn-save-availability:hover {
        background: #059669;
    }

    .btn-cancel-availability {
        background: var(--danger);
        color: white;
    }

    .btn-cancel-availability:hover {
        background: #dc2626;
    }

    /* Stats mini */
    .availability-stats {
        display: flex;
        gap: var(--space-md);
        margin-bottom: var(--space-md);
        flex-wrap: wrap;
    }

    .stat-mini {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.85rem;
        padding: var(--space-xs) var(--space-sm);
        background: var(--surface-secondary);
        border-radius: var(--radius-small);
    }

    .stat-mini .stat-value {
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .availability-grid {
            grid-template-columns: 60px repeat(7, 1fr);
            font-size: 0.7rem;
        }

        .availability-day-header {
            padding: var(--space-xs);
            font-size: 0.7rem;
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
                                    @if($enseignant->department)
                                        · {{ $enseignant->department->name }}
                                    @endif
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
