@extends('layouts.app')

@section('title', 'Détails du Département')
@section('page_title', 'Détails du Département')

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl">
        <div class="header-content">
            <h1 class="header-title">{{ $department->name }}</h1>
            <p class="header-subtitle">Détails du département {{ $department->code }} - ESBTP</p>
            <div style="margin-top: var(--space-md);">
                @if($department->is_active)
                    <span class="badge success">Département Actif</span>
                @else
                    <span class="badge warning">Département Inactif</span>
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
    <div class="row">
        <!-- Informations de base -->
        <div class="col-md-6 mb-lg">
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-lg">Informations de base</div>
                    
                    <div style="display: grid; gap: var(--space-lg);">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <span style="font-weight: 600; color: var(--text-secondary);">Code</span>
                            <span style="font-weight: 700; color: var(--primary); font-size: var(--amount-medium);">{{ $department->code }}</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <span style="font-weight: 600; color: var(--text-secondary);">Statut</span>
                            <div>
                                @if($department->is_active)
                                    <span class="badge success">Actif</span>
                                @else
                                    <span class="badge warning">Inactif</span>
                                @endif
                            </div>
                        </div>
                        
                        <div style="padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Description</div>
                            <div style="color: var(--text-primary);">{{ $department->description ?: 'Non définie' }}</div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <span style="font-weight: 600; color: var(--text-secondary);">Date de création</span>
                            <span style="color: var(--text-primary);">{{ $department->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <span style="font-weight: 600; color: var(--text-secondary);">Dernière modification</span>
                            <span style="color: var(--text-primary);">{{ $department->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations du responsable -->
        <div class="col-md-6 mb-lg">
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-lg">Informations du responsable</div>
                    
                    <div style="display: grid; gap: var(--space-lg);">
                        <div style="padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Chef de département</div>
                            <div style="color: var(--text-primary); font-weight: 600;">{{ $department->head_name ?: 'Non défini' }}</div>
                        </div>
                        
                        <div style="padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Titre</div>
                            <div style="color: var(--text-primary);">{{ $department->head_title ?: 'Non défini' }}</div>
                        </div>
                        
                        <div style="padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Email</div>
                            <div style="color: var(--text-primary);">
                                @if($department->email)
                                    <a href="mailto:{{ $department->email }}" style="color: var(--primary); text-decoration: none;">{{ $department->email }}</a>
                                @else
                                    Non défini
                                @endif
                            </div>
                        </div>
                        
                        <div style="padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Téléphone</div>
                            <div style="color: var(--text-primary);">
                                @if($department->phone)
                                    <a href="tel:{{ $department->phone }}" style="color: var(--primary); text-decoration: none;">{{ $department->phone }}</a>
                                @else
                                    Non défini
                                @endif
                            </div>
                        </div>
                        
                        <div style="padding: var(--space-md); background-color: #f8fafc; border-radius: var(--radius-small);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Bureau</div>
                            <div style="color: var(--text-primary);">{{ $department->office_location ?: 'Non défini' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row">
        <div class="col-12">
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-lg">Statistiques du département</div>
                    
                    <div class="kpi-grid">
                        <div class="kpi-card card-moderne" style="text-align: center; background: linear-gradient(135deg, var(--accent-blue), var(--primary));">
                            <div style="color: white;">
                                <i class="fas fa-graduation-cap fa-2x" style="margin-bottom: var(--space-md);"></i>
                                <div class="kpi-title" style="color: white;">Spécialités</div>
                                <div class="kpi-value" style="color: white;">{{ $department->specialties ? $department->specialties->count() : 0 }}</div>
                            </div>
                        </div>
                        
                        <div class="kpi-card card-moderne" style="text-align: center; background: linear-gradient(135deg, var(--success), var(--accent-blue));">
                            <div style="color: white;">
                                <i class="fas fa-chalkboard-teacher fa-2x" style="margin-bottom: var(--space-md);"></i>
                                <div class="kpi-title" style="color: white;">Enseignants</div>
                                <div class="kpi-value" style="color: white;">{{ $department->teachers ? $department->teachers->count() : 0 }}</div>
                            </div>
                        </div>
                        
                        <div class="kpi-card card-moderne" style="text-align: center; background: linear-gradient(135deg, var(--warning), var(--accent-orange));">
                            <div style="color: white;">
                                <i class="fas fa-user-graduate fa-2x" style="margin-bottom: var(--space-md);"></i>
                                <div class="kpi-title" style="color: white;">Étudiants</div>
                                <div class="kpi-value" style="color: white;">{{ $department->students ? $department->students->count() : 0 }}</div>
                            </div>
                        </div>
                        
                        <div class="kpi-card card-moderne" style="text-align: center; background: linear-gradient(135deg, var(--primary), var(--secondary));">
                            <div style="color: white;">
                                <i class="fas fa-book fa-2x" style="margin-bottom: var(--space-md);"></i>
                                <div class="kpi-title" style="color: white;">Formations continues</div>
                                <div class="kpi-value" style="color: white;">{{ $department->continuingEducationPrograms ? $department->continuingEducationPrograms->count() : 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="row mt-xl">
        <div class="col-12">
            <div style="padding: var(--space-lg); background-color: var(--surface); border-radius: var(--radius-medium); box-shadow: var(--shadow-card);">
                <div style="display: flex; gap: var(--space-md); justify-content: center;">
                    <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-acasi primary" style="padding: var(--space-md) var(--space-xl);">
                        <i class="fas fa-edit"></i> Modifier le département
                    </a>
                    <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-acasi" style="background-color: var(--danger); color: white; padding: var(--space-md) var(--space-xl);" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
