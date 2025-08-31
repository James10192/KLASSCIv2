@extends('layouts.app')

@section('title', 'Liste des classes - ESBTP-yAKRO')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Gestion des Classes</h1>
                <p class="header-subtitle">Organisation et suivi des classes par filière et niveau</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->hasRole('superAdmin'))
                <a href="{{ route('esbtp.classes.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle Classe
                </a>
                @endif
            </div>
        </div>
        <!-- Messages d'état -->
        @if(session('success'))
            <div class="card-moderne" style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--success); margin-bottom: var(--space-lg);">
                <div style="padding: var(--space-md);">
                    <div class="color-success font-semibold">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="card-moderne" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); margin-bottom: var(--space-lg);">
                <div style="padding: var(--space-md);">
                    <div class="color-danger font-semibold">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Filtre année académique -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres d'analyse
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les classes sont visibles pour toutes les années, mais les étudiants affichés correspondent à l'année courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            @php
                $totalClasses = $classes->count();
                $classesActives = $classes->where('is_active', true)->count();
                $totalEtudiants = $classes->sum('nombre_etudiants');
                $totalPlaces = $classes->sum('places_totales');
                $placesDisponibles = $classes->sum('places_disponibles');
                $tauxOccupation = $totalPlaces > 0 ? round(($totalEtudiants / $totalPlaces) * 100, 1) : 0;
            @endphp
            
            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-graduation-cap me-1"></i>Total Classes
                </div>
                <div class="kpi-value color-primary">{{ $totalClasses }}</div>
                <div class="kpi-trend {{ $classesActives == $totalClasses ? 'positive' : 'negative' }}">
                    <i class="fas fa-{{ $classesActives == $totalClasses ? 'check' : 'exclamation' }}-circle"></i>
                    {{ $classesActives }} actives
                </div>
            </div>

            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-users me-1"></i>Étudiants Inscrits
                </div>
                <div class="kpi-value color-accent">{{ $totalEtudiants }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-chart-line"></i>
                    {{ $totalPlaces }} places totales
                </div>
            </div>

            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-chair me-1"></i>Places Disponibles
                </div>
                <div class="kpi-value color-{{ $placesDisponibles > 0 ? 'success' : 'danger' }}">{{ $placesDisponibles }}</div>
                <div class="kpi-trend {{ $placesDisponibles > 0 ? 'positive' : 'negative' }}">
                    <i class="fas fa-{{ $placesDisponibles > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    Disponibles
                </div>
            </div>

            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-percentage me-1"></i>Taux Occupation
                </div>
                <div class="kpi-value color-{{ $tauxOccupation > 90 ? 'danger' : ($tauxOccupation > 70 ? 'warning' : 'success') }}">{{ $tauxOccupation }}%</div>
                <div class="kpi-trend {{ $tauxOccupation < 100 ? 'positive' : 'negative' }}">
                    <i class="fas fa-chart-pie"></i>
                    Occupation globale
                </div>
            </div>
        </div>

        <!-- Liste des classes en grid moderne -->
        <div class="card-moderne" style="padding: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-list me-2"></i>Classes par Filière et Niveau
            </div>
            
            @if($classes->count() > 0)
                <div class="resultats-grid" style="margin-top: var(--space-lg);">
                    @foreach($classes as $classe)
                        <div class="card-moderne resultat-card animate-slide-up" style="border-left: 4px solid {{ $classe->is_active ? 'var(--success)' : 'var(--neutral)' }};">
                            <!-- En-tête classe -->
                            <div style="display: flex; justify-content: between; align-items: start; margin-bottom: var(--space-md);">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; margin-bottom: var(--space-sm);">
                                        <div style="width: 40px; height: 40px; background: {{ $classe->is_active ? 'var(--success)' : 'var(--neutral)' }}; border-radius: var(--radius-circle); display: flex; align-items: center; justify-content: center; margin-right: var(--space-sm);">
                                            <i class="fas fa-graduation-cap" style="color: white; font-size: 16px;"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold color-primary" style="font-size: var(--text-normal);">{{ $classe->name }}</div>
                                            <div style="font-size: var(--text-small); color: var(--text-secondary);">Code: {{ $classe->code }}</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Filière et niveau -->
                                    <div style="margin-bottom: var(--space-md);">
                                        @if ($classe->filiere)
                                            <div style="font-size: var(--text-small); color: var(--text-primary); margin-bottom: var(--space-xs);">
                                                <i class="fas fa-layer-group me-1"></i><strong>{{ $classe->filiere->name }}</strong>
                                                @if ($classe->filiere->parent)
                                                    <br><span style="color: var(--text-muted); margin-left: 16px;">Option de {{ $classe->filiere->parent->name }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($classe->niveau)
                                            <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                                <i class="fas fa-level-up-alt me-1"></i>{{ $classe->niveau->name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Statut -->
                                <div>
                                    <span class="badge {{ $classe->is_active ? 'success' : 'danger' }}">
                                        {{ $classe->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Statistiques -->
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-md); margin-bottom: var(--space-md); padding: var(--space-sm); background: rgba(248, 250, 252, 0.5); border-radius: var(--radius-small);">
                                <div class="text-center">
                                    <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Capacité</div>
                                    <div class="font-bold color-primary">{{ $classe->places_totales }}</div>
                                </div>
                                <div class="text-center">
                                    <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Inscrits</div>
                                    <div class="font-bold color-accent">{{ $classe->nombre_etudiants }}</div>
                                </div>
                                <div class="text-center">
                                    <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Disponibles</div>
                                    <div class="font-bold color-{{ $classe->places_disponibles > 0 ? 'success' : 'danger' }}">{{ $classe->places_disponibles }}</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f3f4f6; padding-top: var(--space-md);">
                                <div style="font-size: var(--text-small); color: var(--text-muted);">
                                    @if ($classe->annee)
                                        <i class="fas fa-calendar me-1"></i>{{ $classe->annee->name }}
                                    @endif
                                </div>
                                <div style="display: flex; gap: var(--space-xs);">
                                    <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="btn-acasi secondary" style="padding: var(--space-xs);" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if(auth()->user()->hasRole('superAdmin'))
                                    <a href="{{ route('esbtp.classes.edit', ['classe' => $classe->id]) }}" class="btn-acasi primary" style="padding: var(--space-xs);" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif

                                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                    <a href="{{ route('esbtp.api.classes.matieres.api', ['id' => $classe->id]) }}" class="btn-acasi secondary" style="padding: var(--space-xs);" title="Gérer les matières">
                                        <i class="fas fa-book"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center" style="padding: var(--space-xl); color: var(--text-secondary);">
                    <i class="fas fa-graduation-cap" style="font-size: 48px; margin-bottom: var(--space-lg); color: var(--neutral);"></i>
                    <h5 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Aucune classe trouvée</h5>
                    <p style="color: var(--text-muted);">Commencez par créer votre première classe.</p>
                    @if(auth()->user()->hasRole('superAdmin'))
                        <a href="{{ route('esbtp.classes.create') }}" class="btn-acasi primary" style="margin-top: var(--space-md);">
                            <i class="fas fa-plus-circle"></i>Créer une classe
                        </a>
                    @endif
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
/* Styles spécifiques pour améliorer l'intégration avec dashboard-moderne.css */
.me-1 {
    margin-right: 0.25rem;
}

.me-2 {
    margin-right: 0.5rem;
}

/* Amélioration responsive pour les grilles */
@media (max-width: 768px) {
    .resultats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 480px) {
    .kpi-grid {
        grid-template-columns: 1fr !important;
    }
}

/* Effets hover pour les cards de classe */
.resultat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}
</style>
@endpush

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les étudiants affichés dans chaque classe se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des étudiants dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les étudiants inscrits en 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les étudiants inscrits en 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
    
    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
});
</script>
