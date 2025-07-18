@extends('layouts.app')

@section('title', 'Détails de l\'Événement - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .event-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }
    
    .event-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)" /></svg>');
        opacity: 0.3;
    }
    
    .event-header .content {
        position: relative;
        z-index: 1;
    }
    
    .event-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: var(--space-md);
        backdrop-filter: blur(10px);
    }
    
    .status-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
        transition: all 0.3s ease;
    }
    
    .info-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }
    
    .info-card h6 {
        color: var(--primary);
        margin-bottom: var(--space-md);
        font-weight: 600;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        margin-bottom: var(--space-sm);
    }
    
    .info-item:last-child {
        margin-bottom: 0;
    }
    
    .info-item i {
        width: 20px;
        color: var(--text-secondary);
        margin-right: var(--space-sm);
    }
    
    .timeline-item {
        position: relative;
        padding-left: var(--space-xl);
        margin-bottom: var(--space-lg);
        border-left: 2px solid var(--border);
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 0;
        width: 10px;
        height: 10px;
        background: var(--primary);
        border-radius: var(--radius-circle);
    }
    
    .timeline-item:last-child {
        border-left-color: transparent;
    }
    
    .participants-list {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
        margin-top: var(--space-sm);
    }
    
    .participant-tag {
        background: var(--primary);
        color: white;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 0.875rem;
    }
    
    .action-buttons {
        display: flex;
        gap: var(--space-sm);
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .event-header {
            padding: var(--space-lg);
        }
        
        .event-icon {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header de l'événement -->
        <div class="event-header">
            <div class="content">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="event-icon">
                            <i class="fas fa-{{ $evenement->icone }}"></i>
                        </div>
                        <h1 class="mb-2">{{ $evenement->titre }}</h1>
                        <p class="mb-3 opacity-90">{{ $evenement->description }}</p>
                        <div class="d-flex align-items-center gap-3">
                            <span class="status-badge bg-{{ $evenement->couleur }}">
                                {{ ucfirst($evenement->statut) }}
                            </span>
                            <span class="badge bg-light text-dark">
                                {{ ucfirst($evenement->type) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="action-buttons">
                            <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <a href="{{ route('esbtp.evenements-academiques.edit', $evenement) }}" class="btn-acasi warning">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <form method="POST" action="{{ route('esbtp.evenements-academiques.duplicate', $evenement) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn-acasi info">
                                    <i class="fas fa-copy me-1"></i>Dupliquer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Informations principales -->
            <div class="col-md-8">
                <!-- Dates et heures -->
                <div class="info-card">
                    <h6><i class="fas fa-calendar-alt me-2"></i>Dates et Heures</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-play-circle"></i>
                                <div>
                                    <strong>Début :</strong> 
                                    {{ $evenement->date_debut->format('d/m/Y') }}
                                    @if($evenement->heure_debut)
                                        à {{ $evenement->heure_debut->format('H:i') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-stop-circle"></i>
                                <div>
                                    <strong>Fin :</strong> 
                                    @if($evenement->date_fin)
                                        {{ $evenement->date_fin->format('d/m/Y') }}
                                        @if($evenement->heure_fin)
                                            à {{ $evenement->heure_fin->format('H:i') }}
                                        @endif
                                    @else
                                        Non définie
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($evenement->date_debut && $evenement->date_fin)
                    <div class="info-item mt-3">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Durée :</strong> 
                            {{ $evenement->date_debut->diffInDays($evenement->date_fin) + 1 }} jour(s)
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Lieu et contexte -->
                <div class="info-card">
                    <h6><i class="fas fa-map-marker-alt me-2"></i>Lieu et Contexte</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-building"></i>
                                <div>
                                    <strong>Lieu :</strong> 
                                    {{ $evenement->lieu ?: 'Non défini' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-graduation-cap"></i>
                                <div>
                                    <strong>Année :</strong> 
                                    {{ $evenement->anneeUniversitaire->name ?? 'Non définie' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants -->
                @if($evenement->participants && count($evenement->participants) > 0)
                <div class="info-card">
                    <h6><i class="fas fa-users me-2"></i>Participants</h6>
                    <div class="participants-list">
                        @foreach($evenement->participants as $participant)
                            <span class="participant-tag">{{ $participant }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Notes -->
                @if($evenement->notes)
                <div class="info-card">
                    <h6><i class="fas fa-sticky-note me-2"></i>Notes</h6>
                    <p class="mb-0">{{ $evenement->notes }}</p>
                </div>
                @endif
            </div>

            <!-- Panneau latéral -->
            <div class="col-md-4">
                <!-- Statut et visibilité -->
                <div class="info-card">
                    <h6><i class="fas fa-eye me-2"></i>Affichage</h6>
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <div>
                            <strong>Calendrier :</strong> 
                            @if($evenement->afficher_calendrier)
                                <span class="text-success">Oui</span>
                            @else
                                <span class="text-muted">Non</span>
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-stream"></i>
                        <div>
                            <strong>Timeline :</strong> 
                            @if($evenement->afficher_timeline)
                                <span class="text-success">Oui</span>
                            @else
                                <span class="text-muted">Non</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="info-card">
                    <h6><i class="fas fa-bell me-2"></i>Notifications</h6>
                    <div class="info-item">
                        <i class="fas fa-toggle-{{ $evenement->notification_active ? 'on' : 'off' }}"></i>
                        <div>
                            <strong>Statut :</strong> 
                            @if($evenement->notification_active)
                                <span class="text-success">Activées</span>
                            @else
                                <span class="text-muted">Désactivées</span>
                            @endif
                        </div>
                    </div>
                    @if($evenement->notification_active)
                    <div class="info-item">
                        <i class="fas fa-calendar-day"></i>
                        <div>
                            <strong>Délai :</strong> 
                            {{ $evenement->jours_notification }} jour(s) avant
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Historique -->
                <div class="info-card">
                    <h6><i class="fas fa-history me-2"></i>Historique</h6>
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Créé</span>
                            <span>{{ $evenement->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($evenement->createdBy)
                        <small class="text-muted">par {{ $evenement->createdBy->name }}</small>
                        @endif
                    </div>
                    
                    @if($evenement->updated_at != $evenement->created_at)
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Modifié</span>
                            <span>{{ $evenement->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($evenement->updatedBy)
                        <small class="text-muted">par {{ $evenement->updatedBy->name }}</small>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Actions rapides -->
                <div class="info-card">
                    <h6><i class="fas fa-bolt me-2"></i>Actions Rapides</h6>
                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('esbtp.evenements-academiques.change-status', $evenement) }}">
                            @csrf
                            <select name="statut" class="form-select mb-2">
                                <option value="planifie" {{ $evenement->statut == 'planifie' ? 'selected' : '' }}>Planifié</option>
                                <option value="confirme" {{ $evenement->statut == 'confirme' ? 'selected' : '' }}>Confirmé</option>
                                <option value="annule" {{ $evenement->statut == 'annule' ? 'selected' : '' }}>Annulé</option>
                                <option value="reporte" {{ $evenement->statut == 'reporte' ? 'selected' : '' }}>Reporté</option>
                                <option value="termine" {{ $evenement->statut == 'termine' ? 'selected' : '' }}>Terminé</option>
                            </select>
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-sync me-1"></i>Changer Statut
                            </button>
                        </form>
                        
                        <form method="POST" action="{{ route('esbtp.evenements-academiques.destroy', $evenement) }}" 
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-acasi danger w-100">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée pour les cartes
    $('.info-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 300);
        }, index * 100);
    });
    
    // Confirmation pour les actions destructives
    $('form[method="POST"]').on('submit', function(e) {
        if ($(this).find('button[type="submit"]').hasClass('btn-acasi-danger')) {
            if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
@endpush