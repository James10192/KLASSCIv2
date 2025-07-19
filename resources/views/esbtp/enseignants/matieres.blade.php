@extends('layouts.app')

@section('title', 'Gestion des Matières - ' . $teacher->user->name)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .teacher-info-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        border: 1px solid var(--border);
    }
    
    .teacher-info-header {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }
    
    .teacher-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: bold;
    }
    
    .matieres-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-xl);
        margin-top: var(--space-lg);
    }
    
    .matieres-section {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
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
    
    .matiere-card {
        background: var(--background);
        border-radius: var(--radius-small);
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        border: 2px solid var(--border);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .matiere-card:hover {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.05);
    }
    
    .matiere-card.selected {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.1);
    }
    
    .matiere-card.assigned {
        border-color: var(--success);
        background: rgba(var(--success-rgb), 0.1);
    }
    
    .matiere-name {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .matiere-details {
        display: flex;
        gap: var(--space-md);
        font-size: var(--text-small);
        color: var(--text-secondary);
        flex-wrap: wrap;
    }
    
    .matiere-detail {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .matiere-code {
        background: var(--primary);
        color: white;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .selection-controls {
        padding: var(--space-md);
        background: var(--background);
        border-radius: var(--radius-small);
        margin-bottom: var(--space-md);
    }
    
    .btn-selection {
        margin-right: var(--space-sm);
        margin-bottom: var(--space-xs);
    }
    
    .form-actions {
        background: var(--background);
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        margin-top: var(--space-lg);
        text-align: center;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    
    .stat-card {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        text-align: center;
        border: 1px solid var(--border);
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        display: block;
    }
    
    .stat-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }
    
    @media (max-width: 768px) {
        .matieres-grid {
            grid-template-columns: 1fr;
        }
        
        .teacher-info-header {
            flex-direction: column;
            text-align: center;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
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
                    <i class="fas fa-book me-2"></i>
                    Gestion des Matières
                </h1>
                <p class="section-subtitle">Attribution des matières à l'enseignant</p>
            </div>
        </div>

        <!-- Informations enseignant -->
        <div class="teacher-info-card">
            <div class="teacher-info-header">
                <div class="teacher-avatar">
                    {{ substr($teacher->user->name, 0, 2) }}
                </div>
                <div class="teacher-details">
                    <h3>{{ $teacher->user->name }}</h3>
                    <p>{{ $teacher->specialization }}</p>
                    <div class="meta-info">
                        <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                            {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>
                <div class="actions">
                    <a href="{{ route('esbtp.enseignants.show', $teacher) }}" class="btn-acasi secondary btn-sm">
                        <i class="fas fa-eye me-1"></i>Voir Profil
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">{{ $matieresAssignees->count() }}</span>
                <div class="stat-label">Matières Assignées</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $matieres->count() }}</span>
                <div class="stat-label">Matières Disponibles</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $matieresAssignees->sum('coefficient') }}</span>
                <div class="stat-label">Total Coefficient</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $matieresAssignees->sum(function($m) { return $m->heures_cm + $m->heures_td + $m->heures_tp; }) }}h</span>
                <div class="stat-label">Total Heures</div>
            </div>
        </div>

        <form action="{{ route('esbtp.enseignants.assign-matieres', $teacher) }}" method="POST" id="matieresForm">
            @csrf
            
            <div class="matieres-grid">
                <!-- Matières assignées -->
                <div class="matieres-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        Matières Assignées ({{ $matieresAssignees->count() }})
                    </div>
                    
                    <div id="matieres-assignees">
                        @if($matieresAssignees->count() > 0)
                            @foreach($matieresAssignees as $matiere)
                            <div class="matiere-card assigned" data-matiere-id="{{ $matiere->id }}">
                                <input type="checkbox" name="matieres[]" value="{{ $matiere->id }}" 
                                       checked style="display: none;">
                                <div class="matiere-name">
                                    <span class="matiere-code">{{ $matiere->code }}</span>
                                    {{ $matiere->name }}
                                </div>
                                <div class="matiere-details">
                                    @if($matiere->coefficient)
                                    <div class="matiere-detail">
                                        <i class="fas fa-weight"></i>
                                        <span>Coef: {{ $matiere->coefficient }}</span>
                                    </div>
                                    @endif
                                    @if($matiere->heures_cm)
                                    <div class="matiere-detail">
                                        <i class="fas fa-clock"></i>
                                        <span>CM: {{ $matiere->heures_cm }}h</span>
                                    </div>
                                    @endif
                                    @if($matiere->heures_td)
                                    <div class="matiere-detail">
                                        <i class="fas fa-users"></i>
                                        <span>TD: {{ $matiere->heures_td }}h</span>
                                    </div>
                                    @endif
                                    @if($matiere->heures_tp)
                                    <div class="matiere-detail">
                                        <i class="fas fa-flask"></i>
                                        <span>TP: {{ $matiere->heures_tp }}h</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted p-4">
                                <i class="fas fa-info-circle mb-2"></i>
                                <p>Aucune matière assignée</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Matières disponibles -->
                <div class="matieres-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        Matières Disponibles
                    </div>
                    
                    <div class="selection-controls">
                        <button type="button" class="btn-acasi secondary btn-sm btn-selection" onclick="selectAll()">
                            <i class="fas fa-check-square me-1"></i>Tout Sélectionner
                        </button>
                        <button type="button" class="btn-acasi secondary btn-sm btn-selection" onclick="unselectAll()">
                            <i class="fas fa-square me-1"></i>Tout Désélectionner
                        </button>
                        <button type="button" class="btn-acasi primary btn-sm btn-selection" onclick="filterByNiveau()">
                            <i class="fas fa-filter me-1"></i>Filtrer
                        </button>
                    </div>
                    
                    <div id="matieres-disponibles">
                        @foreach($matieres as $matiere)
                            @if(!$matieresAssignees->contains('id', $matiere->id))
                            <div class="matiere-card" data-matiere-id="{{ $matiere->id }}" 
                                 data-niveau="{{ $matiere->niveauEtude ? $matiere->niveauEtude->name : '' }}"
                                 onclick="toggleMatiere(this)">
                                <input type="checkbox" name="matieres[]" value="{{ $matiere->id }}" style="display: none;">
                                <div class="matiere-name">
                                    <span class="matiere-code">{{ $matiere->code }}</span>
                                    {{ $matiere->name }}
                                </div>
                                <div class="matiere-details">
                                    @if($matiere->niveauEtude)
                                    <div class="matiere-detail">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span>{{ $matiere->niveauEtude->name }}</span>
                                    </div>
                                    @endif
                                    @if($matiere->coefficient)
                                    <div class="matiere-detail">
                                        <i class="fas fa-weight"></i>
                                        <span>Coef: {{ $matiere->coefficient }}</span>
                                    </div>
                                    @endif
                                    @if($matiere->heures_cm)
                                    <div class="matiere-detail">
                                        <i class="fas fa-clock"></i>
                                        <span>CM: {{ $matiere->heures_cm }}h</span>
                                    </div>
                                    @endif
                                    @if($matiere->heures_td)
                                    <div class="matiere-detail">
                                        <i class="fas fa-users"></i>
                                        <span>TD: {{ $matiere->heures_td }}h</span>
                                    </div>
                                    @endif
                                    @if($matiere->heures_tp)
                                    <div class="matiere-detail">
                                        <i class="fas fa-flask"></i>
                                        <span>TP: {{ $matiere->heures_tp }}h</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('esbtp.enseignants.show', $teacher) }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour au Profil
                </a>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save me-1"></i>Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleMatiere(element) {
    const checkbox = element.querySelector('input[type="checkbox"]');
    const isSelected = element.classList.contains('selected');
    
    if (isSelected) {
        element.classList.remove('selected');
        checkbox.checked = false;
    } else {
        element.classList.add('selected');
        checkbox.checked = true;
    }
}

function selectAll() {
    document.querySelectorAll('#matieres-disponibles .matiere-card').forEach(card => {
        if (!card.classList.contains('selected')) {
            toggleMatiere(card);
        }
    });
}

function unselectAll() {
    document.querySelectorAll('#matieres-disponibles .matiere-card').forEach(card => {
        if (card.classList.contains('selected')) {
            toggleMatiere(card);
        }
    });
}

function filterByNiveau() {
    // Implémenter le filtrage par niveau si nécessaire
    alert('Fonctionnalité de filtrage à implémenter');
}

// Marquer les matières déjà sélectionnées
document.addEventListener('DOMContentLoaded', function() {
    // Les matières assignées sont déjà cochées
    document.querySelectorAll('#matieres-assignees input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });
});
</script>
@endpush