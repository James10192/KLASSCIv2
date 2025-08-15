@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .event-show-container {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        margin: var(--space-lg) 0;
    }
    
    .event-show-header {
        padding: var(--space-xl);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
        position: relative;
        overflow: hidden;
    }
    
    .event-show-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
        animation: float 20s linear infinite;
    }
    
    @keyframes float {
        0% { transform: translateX(-100px) translateY(-100px); }
        100% { transform: translateX(100px) translateY(100px); }
    }
    
    .event-show-header h1 {
        margin: 0;
        font-size: var(--title-main);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        position: relative;
        z-index: 1;
    }
    
    .event-show-header .event-meta {
        margin: var(--space-md) 0 0 0;
        opacity: 0.9;
        font-size: var(--text-normal);
        position: relative;
        z-index: 1;
    }
    
    .event-show-header .header-actions {
        position: absolute;
        top: var(--space-lg);
        right: var(--space-xl);
        display: flex;
        gap: var(--space-md);
        z-index: 1;
    }
    
    .event-show-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--space-xl);
        padding: var(--space-xl);
    }
    
    .event-show-main {
        display: flex;
        flex-direction: column;
        gap: var(--space-xl);
    }
    
    .event-show-sidebar {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
    }
    
    .info-card {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .info-card-title {
        font-size: var(--title-section);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-lg);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding-bottom: var(--space-sm);
        border-bottom: 2px solid var(--primary);
    }
    
    .info-card-title i {
        color: var(--primary);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-lg);
    }
    
    .info-table {
        width: 100%;
    }
    
    .info-table tr {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .info-table tr:last-child {
        border-bottom: none;
    }
    
    .info-table td {
        padding: var(--space-md) 0;
        vertical-align: top;
    }
    
    .info-table td:first-child {
        width: 40%;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-size: var(--text-small);
        letter-spacing: 0.5px;
    }
    
    .info-table td:last-child {
        color: var(--text-primary);
    }
    
    .date-timeline-card {
        background: linear-gradient(135deg, var(--background), var(--surface));
        border-left: 4px solid var(--primary);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        position: relative;
    }
    
    .date-timeline-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: radial-gradient(circle, rgba(30, 58, 138, 0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .date-item {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }
    
    .date-item:last-child {
        margin-bottom: 0;
    }
    
    .date-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--text-normal);
        color: white;
        flex-shrink: 0;
    }
    
    .date-icon.primary { background: var(--primary); }
    .date-icon.success { background: var(--success); }
    .date-icon.warning { background: var(--warning); }
    
    .date-content {
        flex: 1;
    }
    
    .date-content strong {
        display: block;
        font-size: var(--text-normal);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .date-content small {
        color: var(--text-secondary);
        font-size: var(--text-small);
    }
    
    .description-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid rgba(0, 0, 0, 0.05);
        line-height: 1.6;
        color: var(--text-primary);
    }
    
    .sidebar-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        position: sticky;
        top: var(--space-lg);
    }
    
    .sidebar-card-title {
        font-size: var(--title-section);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-lg);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .sidebar-card-title i {
        color: var(--primary);
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
    }
    
    .btn-acasi.danger {
        background-color: var(--danger);
        color: white;
    }
    
    .btn-acasi.danger:hover {
        background-color: #dc2626;
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
    }
    
    .system-info-card {
        background: var(--background);
        border-radius: var(--radius-small);
        padding: var(--space-md);
        margin-top: var(--space-lg);
    }
    
    .system-info-card .info-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-xs) 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .system-info-card .info-line:last-child {
        border-bottom: none;
    }
    
    .system-info-card .info-label {
        font-weight: 500;
        color: var(--text-secondary);
        font-size: var(--text-small);
    }
    
    .system-info-card .info-value {
        color: var(--text-primary);
        font-size: var(--text-small);
    }
    
    .w-100 {
        width: 100%;
    }
    
    @media (max-width: 1024px) {
        .event-show-layout {
            grid-template-columns: 1fr;
            gap: var(--space-lg);
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .sidebar-card {
            position: static;
        }
        
        .event-show-header .header-actions {
            position: static;
            margin-top: var(--space-lg);
            justify-content: center;
        }
    }
    
    @media (max-width: 768px) {
        .event-show-header,
        .event-show-layout {
            padding: var(--space-lg);
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="event-show-container">
            <div class="event-show-header">
                <h1>
                    <i class="fas fa-{{ $evenementAcademique->icone }}"></i>
                    {{ $evenementAcademique->titre }}
                </h1>
                <div class="event-meta">
                    <span class="table-badge {{ match($evenementAcademique->statut) {
                        'planifie' => 'neutral',
                        'confirme' => 'success', 
                        'annule' => 'danger',
                        'reporte' => 'warning',
                        'termine' => 'primary',
                        default => 'neutral'
                    } }}">
                        {{ $evenementAcademique->statut_libelle }}
                    </span>
                    • 
                    <span class="table-badge primary">
                        {{ $evenementAcademique->type_libelle }}
                    </span>
                    • 
                    {{ $evenementAcademique->anneeUniversitaire->name }}
                </div>
                <div class="header-actions">
                    <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour
                    </a>
                    @if($evenementAcademique->isEditable())
                        <a href="{{ route('esbtp.evenements-academiques.edit', $evenementAcademique) }}" class="btn-acasi primary">
                            <i class="fas fa-edit me-2"></i>
                            Modifier
                        </a>
                    @endif
                </div>
            </div>
            
            <div class="event-show-layout">
                <div class="event-show-main">
                    <!-- Informations générales -->
                    <div class="info-card">
                        <div class="info-card-title">
                            <i class="fas fa-info-circle"></i>
                            Informations générales
                        </div>
                        <div class="info-grid">
                            <div>
                                <table class="info-table">
                                    <tr>
                                        <td>Type</td>
                                        <td>
                                            <span class="table-badge {{ $evenementAcademique->couleur }}">
                                                {{ $evenementAcademique->type_libelle }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Statut</td>
                                        <td>
                                            <span class="table-badge {{ match($evenementAcademique->statut) {
                                                'planifie' => 'neutral',
                                                'confirme' => 'success', 
                                                'annule' => 'danger',
                                                'reporte' => 'warning',
                                                'termine' => 'primary',
                                                default => 'neutral'
                                            } }}">
                                                {{ $evenementAcademique->statut_libelle }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Année</td>
                                        <td>{{ $evenementAcademique->anneeUniversitaire->name }}</td>
                                    </tr>
                                    @if($evenementAcademique->lieu)
                                    <tr>
                                        <td>Lieu</td>
                                        <td>{{ $evenementAcademique->lieu }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div>
                                <div class="date-timeline-card">
                                    <div class="date-item">
                                        <div class="date-icon primary">
                                            <i class="fas fa-calendar-day"></i>
                                        </div>
                                        <div class="date-content">
                                            <strong>{{ $evenementAcademique->date_debut->format('d/m/Y') }}</strong>
                                            <small>Date de début</small>
                                            @if($evenementAcademique->heure_debut)
                                                <small>à {{ $evenementAcademique->heure_debut->format('H:i') }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($evenementAcademique->date_fin)
                                    <div class="date-item">
                                        <div class="date-icon success">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="date-content">
                                            <strong>{{ $evenementAcademique->date_fin->format('d/m/Y') }}</strong>
                                            <small>Date de fin</small>
                                            @if($evenementAcademique->heure_fin)
                                                <small>à {{ $evenementAcademique->heure_fin->format('H:i') }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="date-item">
                                        <div class="date-icon warning">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="date-content">
                                            <strong>{{ $evenementAcademique->duree }}</strong>
                                            <small>Durée totale</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="info-card">
                        <div class="info-card-title">
                            <i class="fas fa-align-left"></i>
                            Description
                        </div>
                        <div class="description-card">
                            {{ $evenementAcademique->description }}
                        </div>
                    </div>

                    <!-- Notes supplémentaires -->
                    @if($evenementAcademique->notes)
                    <div class="info-card">
                        <div class="info-card-title">
                            <i class="fas fa-sticky-note"></i>
                            Notes supplémentaires
                        </div>
                        <div class="description-card">
                            {{ $evenementAcademique->notes }}
                        </div>
                    </div>
                    @endif

                    <!-- Participants -->
                    @if($evenementAcademique->participants)
                    <div class="info-card">
                        <div class="info-card-title">
                            <i class="fas fa-users"></i>
                            Participants
                        </div>
                        <div class="description-card">
                            <div style="display: flex; align-items: center; gap: var(--space-sm); color: var(--accent-blue);">
                                <i class="fas fa-users"></i>
                                <span>{{ $evenementAcademique->participants_formatted }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="event-show-sidebar">
                    <!-- Actions -->
                    <div class="sidebar-card">
                        <div class="sidebar-card-title">
                            <i class="fas fa-cogs"></i>
                            Actions
                        </div>
                        <div class="action-buttons">
                            @if($evenementAcademique->isEditable())
                                <a href="{{ route('esbtp.evenements-academiques.edit', $evenementAcademique) }}" 
                                   class="btn-acasi primary w-100">
                                    <i class="fas fa-edit me-2"></i>
                                    Modifier l'événement
                                </a>
                            @endif
                            
                            <form method="POST" action="{{ route('esbtp.evenements-academiques.duplicate', $evenementAcademique) }}">
                                @csrf
                                <button type="submit" class="btn-acasi secondary w-100">
                                    <i class="fas fa-copy me-2"></i>
                                    Dupliquer pour l'année suivante
                                </button>
                            </form>
                            
                            @if($evenementAcademique->isDeletable())
                                <form method="POST" 
                                      action="{{ route('esbtp.evenements-academiques.destroy', $evenementAcademique) }}"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-acasi danger w-100">
                                        <i class="fas fa-trash me-2"></i>
                                        Supprimer l'événement
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Configuration d'affichage -->
                        <div style="margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid rgba(0, 0, 0, 0.05);">
                            <h6 style="font-size: var(--text-small); font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: var(--space-md);">Affichage</h6>
                            <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
                                <div class="form-check-moderne">
                                    <input type="checkbox" class="form-check-input-moderne" {{ $evenementAcademique->afficher_calendrier ? 'checked' : '' }} disabled>
                                    <label class="form-check-label-moderne">Calendrier</label>
                                </div>
                                <div class="form-check-moderne">
                                    <input type="checkbox" class="form-check-input-moderne" {{ $evenementAcademique->afficher_timeline ? 'checked' : '' }} disabled>
                                    <label class="form-check-label-moderne">Timeline</label>
                                </div>
                                <div class="form-check-moderne">
                                    <input type="checkbox" class="form-check-input-moderne" {{ $evenementAcademique->notification_active ? 'checked' : '' }} disabled>
                                    <label class="form-check-label-moderne">
                                        Notifications
                                        @if($evenementAcademique->notification_active && $evenementAcademique->jours_notification)
                                            <small>({{ $evenementAcademique->jours_notification }} jours avant)</small>
                                        @endif
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Informations système -->
                        <div class="system-info-card">
                            <div class="info-line">
                                <span class="info-label">Créé par</span>
                                <span class="info-value">{{ $evenementAcademique->createdBy->name ?? 'Système' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Créé le</span>
                                <span class="info-value">{{ $evenementAcademique->created_at->format('d/m/Y à H:i') }}</span>
                            </div>
                            @if($evenementAcademique->updated_at && $evenementAcademique->updated_at != $evenementAcademique->created_at)
                            <div class="info-line">
                                <span class="info-label">Modifié le</span>
                                <span class="info-value">{{ $evenementAcademique->updated_at->format('d/m/Y à H:i') }}</span>
                            </div>
                            @if($evenementAcademique->updatedBy)
                            <div class="info-line">
                                <span class="info-label">Modifié par</span>
                                <span class="info-value">{{ $evenementAcademique->updatedBy->name }}</span>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

