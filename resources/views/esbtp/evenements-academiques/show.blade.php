@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .show-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--space-xl);
        margin-top: var(--space-md);
    }
    
    .show-main {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
    }
    
    .show-sidebar {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
        height: fit-content;
    }
    
    .info-section {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-xl);
    }
    
    .info-table {
        display: table;
        width: 100%;
    }
    
    .info-row {
        display: table-row;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        display: table-cell;
        padding: var(--space-sm) 0;
        font-weight: 600;
        color: var(--text-secondary);
        width: 40%;
    }
    
    .info-value {
        display: table-cell;
        padding: var(--space-sm) 0;
        color: var(--text-primary);
    }
    
    .section-title-small {
        font-size: var(--title-section);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .content-block {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
        border: 1px solid #f3f4f6;
    }
    
    .timeline-date-block {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border-left: 4px solid var(--primary);
    }
    
    .date-principal {
        font-size: var(--text-normal);
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: var(--space-xs);
    }
    
    .date-fin {
        font-size: var(--text-normal);
        color: var(--success);
        font-weight: 500;
    }
    
    .horaires {
        background: var(--surface);
        padding: var(--space-md);
        border-radius: var(--radius-small);
        margin-top: var(--space-md);
    }
    
    .sidebar-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
        border: 1px solid #f3f4f6;
    }
    
    .sidebar-card-title {
        font-size: var(--title-section);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-md);
    }
    
    .system-info {
        font-size: var(--text-small);
        color: var(--text-muted);
        line-height: 1.6;
    }
    
    .system-info strong {
        color: var(--text-secondary);
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
    
    .w-100 {
        width: 100%;
    }
    
    @media (max-width: 768px) {
        .show-layout {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="card-moderne">
            <div class="card-header-moderne">
                <h1 class="section-title">
                    <i class="fas fa-{{ $evenementAcademique->icone }} color-{{ $evenementAcademique->couleur }} me-2"></i>
                    {{ $evenementAcademique->titre }}
                </h1>
                <div class="actions-top">
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
                
            <div class="card-body-moderne">
                <div class="show-layout">
                    <div class="show-main">
                        <!-- Informations principales -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="text-muted">Informations générales</h5>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Type :</strong></td>
                                            <td>
                                                <span class="badge badge-{{ $evenementAcademique->couleur }}">
                                                    {{ $evenementAcademique->type_libelle }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Statut :</strong></td>
                                            <td>
                                                @php
                                                    $statusColor = match($evenementAcademique->statut) {
                                                        'planifie' => 'secondary',
                                                        'confirme' => 'success',
                                                        'annule' => 'danger',
                                                        'reporte' => 'warning',
                                                        'termine' => 'info',
                                                        default => 'light'
                                                    };
                                                @endphp
                                                <span class="badge badge-{{ $statusColor }}">
                                                    {{ $evenementAcademique->statut_libelle }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Année :</strong></td>
                                            <td>{{ $evenementAcademique->anneeUniversitaire->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Durée :</strong></td>
                                            <td>{{ $evenementAcademique->duree }}</td>
                                        </tr>
                                        @if($evenementAcademique->lieu)
                                        <tr>
                                            <td><strong>Lieu :</strong></td>
                                            <td>{{ $evenementAcademique->lieu }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="text-muted">Dates et horaires</h5>
                                    <div class="timeline-date-block">
                                        <div class="date-principal">
                                            <i class="fas fa-calendar-day text-primary mr-2"></i>
                                            <strong>{{ $evenementAcademique->date_debut->format('d/m/Y') }}</strong>
                                        </div>
                                        @if($evenementAcademique->date_fin)
                                        <div class="date-fin mt-2">
                                            <i class="fas fa-calendar-check text-success mr-2"></i>
                                            <strong>{{ $evenementAcademique->date_fin->format('d/m/Y') }}</strong>
                                        </div>
                                        @endif
                                        
                                        @if($evenementAcademique->heure_debut || $evenementAcademique->heure_fin)
                                        <div class="horaires mt-3">
                                            <h6 class="text-muted">Horaires</h6>
                                            @if($evenementAcademique->heure_debut)
                                                <div>
                                                    <i class="fas fa-clock text-info mr-2"></i>
                                                    Début : {{ $evenementAcademique->heure_debut->format('H:i') }}
                                                </div>
                                            @endif
                                            @if($evenementAcademique->heure_fin)
                                                <div>
                                                    <i class="fas fa-clock text-warning mr-2"></i>
                                                    Fin : {{ $evenementAcademique->heure_fin->format('H:i') }}
                                                </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-4">
                                <h5 class="text-muted">Description</h5>
                                <div class="bg-light p-3 rounded">
                                    {{ $evenementAcademique->description }}
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            @if($evenementAcademique->notes)
                            <div class="mb-4">
                                <h5 class="text-muted">Notes supplémentaires</h5>
                                <div class="bg-light p-3 rounded">
                                    {{ $evenementAcademique->notes }}
                                </div>
                            </div>
                            @endif
                            
                            <!-- Participants -->
                            <div class="mb-4">
                                <h5 class="text-muted">Participants</h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-users mr-2"></i>
                                    {{ $evenementAcademique->participants_formatted }}
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="show-sidebar">
                        <!-- Barre latérale -->
                        <!-- Statut et actions -->
                        <div class="sidebar-card">
                            <h5 class="sidebar-card-title">Actions</h5>
                            <div class="sidebar-card-body">
                                @if($evenementAcademique->isEditable())
                                    <a href="{{ route('esbtp.evenements-academiques.edit', $evenementAcademique) }}" 
                                       class="btn-acasi primary w-100 mb-md">
                                        <i class="fas fa-edit me-2"></i>
                                        Modifier
                                    </a>
                                @endif
                                
                                <form method="POST" action="{{ route('esbtp.evenements-academiques.duplicate', $evenementAcademique) }}" class="mb-md">
                                    @csrf
                                    <button type="submit" class="btn-acasi secondary w-100">
                                        <i class="fas fa-copy me-2"></i>
                                        Dupliquer
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
                                            Supprimer
                                        </button>
                                    </form>
                                @endif
                                </div>
                            </div>
                            
                            <!-- Paramètres d'affichage -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title">Paramètres d'affichage</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" 
                                               {{ $evenementAcademique->afficher_calendrier ? 'checked' : '' }} disabled>
                                        <label class="form-check-label">
                                            Afficher dans le calendrier
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" 
                                               {{ $evenementAcademique->afficher_timeline ? 'checked' : '' }} disabled>
                                        <label class="form-check-label">
                                            Afficher dans la timeline
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informations système -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Informations système</h5>
                                </div>
                                <div class="card-body">
                                    <small class="text-muted">
                                        <div><strong>Créé par :</strong> {{ $evenementAcademique->createdBy->name ?? 'Système' }}</div>
                                        <div><strong>Créé le :</strong> {{ $evenementAcademique->created_at->format('d/m/Y à H:i') }}</div>
                                        @if($evenementAcademique->updated_at && $evenementAcademique->updated_at != $evenementAcademique->created_at)
                                            <div><strong>Modifié le :</strong> {{ $evenementAcademique->updated_at->format('d/m/Y à H:i') }}</div>
                                            @if($evenementAcademique->updatedBy)
                                                <div><strong>Modifié par :</strong> {{ $evenementAcademique->updatedBy->name }}</div>
                                            @endif
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.timeline-date-block {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #007bff;
}

.date-principal {
    font-size: 1.1rem;
    color: #495057;
}

.date-fin {
    font-size: 1rem;
    color: #28a745;
}

.horaires {
    background: white;
    padding: 10px;
    border-radius: 4px;
    margin-top: 10px;
}
</style>
@endpush