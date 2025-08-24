@extends('layouts.app')

@section('title', 'Détails du Département')
@section('page_title', 'Détails du Département')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-building me-2"></i>{{ $department->name }}</h1>
                <p class="header-subtitle">Détails du département {{ $department->code }} - ESBTP</p>
                <div class="mt-3">
                    @if($department->is_active)
                        <span class="status-badge success">
                            <i class="fas fa-check-circle me-1"></i>Département Actif
                        </span>
                    @else
                        <span class="status-badge warning">
                            <i class="fas fa-pause-circle me-1"></i>Département Inactif
                        </span>
                    @endif
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.departments.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-acasi primary">
                    <i class="fas fa-edit"></i> Modifier
                </a>
            </div>
        </div>

        <div class="form-grid-2">
            <!-- Informations de base -->
            <div class="section-card">
                <div class="section-card-header">
                    <div class="section-card-title">
                        <i class="fas fa-info-circle"></i>
                        Informations de base
                    </div>
                </div>
                <div class="section-card-body">
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-code"></i>
                            Code du département
                        </div>
                        <div class="info-value-moderne highlight">{{ $department->code }}</div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-toggle-on"></i>
                            Statut
                        </div>
                        <div class="info-value-moderne">
                            @if($department->is_active)
                                <span class="status-badge success">
                                    <i class="fas fa-check-circle me-1"></i>Actif
                                </span>
                            @else
                                <span class="status-badge warning">
                                    <i class="fas fa-pause-circle me-1"></i>Inactif
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-align-left"></i>
                            Description
                        </div>
                        <div class="info-value-moderne">{{ $department->description ?: 'Non définie' }}</div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="far fa-calendar-alt"></i>
                            Date de création
                        </div>
                        <div class="info-value-moderne">{{ $department->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="far fa-clock"></i>
                            Dernière modification
                        </div>
                        <div class="info-value-moderne">{{ $department->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Informations du responsable -->
            <div class="section-card">
                <div class="section-card-header">
                    <div class="section-card-title">
                        <i class="fas fa-user-tie"></i>
                        Informations du responsable
                    </div>
                </div>
                <div class="section-card-body">
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-user"></i>
                            Chef de département
                        </div>
                        <div class="info-value-moderne">{{ $department->head_name ?: 'Non défini' }}</div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-graduation-cap"></i>
                            Titre
                        </div>
                        <div class="info-value-moderne">{{ $department->head_title ?: 'Non défini' }}</div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-envelope"></i>
                            Email
                        </div>
                        <div class="info-value-moderne">
                            @if($department->email)
                                <a href="mailto:{{ $department->email }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $department->email }}
                                </a>
                            @else
                                Non défini
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-phone"></i>
                            Téléphone
                        </div>
                        <div class="info-value-moderne">
                            @if($department->phone)
                                <a href="tel:{{ $department->phone }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $department->phone }}
                                </a>
                            @else
                                Non défini
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-item-moderne">
                        <div class="info-label-moderne">
                            <i class="fas fa-map-marker-alt"></i>
                            Bureau
                        </div>
                        <div class="info-value-moderne">{{ $department->office_location ?: 'Non défini' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-chart-bar"></i>
                    Statistiques du département
                </div>
                <div class="main-card-subtitle">Vue d'ensemble des données du département</div>
            </div>
            <div class="main-card-body">
                <div class="kpi-grid">
                    <div class="kpi-card card-moderne bg-primary">
                        <div class="kpi-title">Spécialités</div>
                        <div class="kpi-value color-primary">{{ $department->specialties ? $department->specialties->count() : 0 }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-graduation-cap"></i>
                            Formations disponibles
                        </div>
                    </div>
                    
                    <div class="kpi-card card-moderne bg-success">
                        <div class="kpi-title">Enseignants</div>
                        <div class="kpi-value color-success">{{ $department->teachers ? $department->teachers->count() : 0 }}</div>
                        <div class="kpi-trend positive">
                            <i class="fas fa-chalkboard-teacher"></i>
                            Corps professoral
                        </div>
                    </div>
                    
                    <div class="kpi-card card-moderne bg-accent">
                        <div class="kpi-title">Étudiants</div>
                        <div class="kpi-value color-accent">{{ $department->students ? $department->students->count() : 0 }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-user-graduate"></i>
                            Étudiants inscrits
                        </div>
                    </div>
                    
                    <div class="kpi-card card-moderne bg-warning">
                        <div class="kpi-title">Formations continues</div>
                        <div class="kpi-value color-warning">{{ $department->continuingEducationPrograms ? $department->continuingEducationPrograms->count() : 0 }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-book"></i>
                            Programmes actifs
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-cogs"></i>
                    Actions disponibles
                </div>
            </div>
            <div class="main-card-body">
                <div class="actions-section-premium">
                    <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-action-premium btn-primary">
                        <i class="fas fa-edit"></i> Modifier le département
                    </a>
                    <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="btn-action-premium btn-danger" 
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
