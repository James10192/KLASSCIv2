@extends('layouts.app')

@section('title', 'Résultats des étudiants - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-bar me-2"></i>Résultats des étudiants</h1>
                <p class="header-subtitle">Consultez et gérez les résultats scolaires de l'établissement</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" placeholder="Rechercher un étudiant...">
                @can('edit_bulletins')
                    <a href="{{ route('esbtp.bulletins.configuration') }}" class="btn-acasi primary">
                        <i class="fas fa-cogs"></i>Configuration bulletins
                    </a>
                @endcan
                <a href="{{ route('esbtp.bulletins.select') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour sélection
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Étudiants</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ isset($etudiants) ? $etudiants->count() : 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    Tous les étudiants
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Moyenne Générale</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">
                    @if(isset($moyennes) && count($moyennes) > 0)
                        {{ number_format(array_sum($moyennes) / count($moyennes), 1) }}
                    @else
                        N/A
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calculator"></i>
                    Sur 20 points
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Taux de Réussite</div>
                <div class="kpi-value" style="color: #10b981; font-size: 2.5rem; font-weight: bold;">
                    @if(isset($moyennes) && count($moyennes) > 0)
                        {{ number_format((count(array_filter($moyennes, function($m) { return $m >= 10; })) / count($moyennes)) * 100, 1) }}%
                    @else
                        N/A
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Moyenne ≥ 10/20
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Bulletins Générés</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ isset($bulletins) ? count($bulletins) : 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-file-alt"></i>
                    Bulletins disponibles
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres de recherche
                </div>
                <div class="main-card-subtitle">Affinez votre recherche de résultats</div>
            </div>
            <div class="main-card-body">
                <form action="{{ route('esbtp.resultats.index') }}" method="GET" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Classe</label>
                            <select class="form-select select2" id="classe_id" name="classe_id">
                                <option value="">Sélectionnez une classe</option>
                                @foreach($classes ?? [] as $classeItem)
                                    <option value="{{ $classeItem->id }}" {{ isset($classe_id) && $classe_id == $classeItem->id ? 'selected' : '' }}>
                                        {{ $classeItem->name }} ({{ $classeItem->filiere->name ?? 'N/A' }} - {{ $classeItem->niveau->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Année Universitaire</label>
                            <select class="form-select select2" id="annee_universitaire_id" name="annee_universitaire_id">
                                <option value="">Sélectionnez une année</option>
                                @foreach($annees_universitaires ?? [] as $annee)
                                    <option value="{{ $annee->id }}" {{ isset($annee_universitaire_id) && $annee_universitaire_id == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Période</label>
                            <select class="form-select" id="semestre" name="semestre">
                                <option value="">Toutes les périodes</option>
                                @foreach($periodes as $key => $periodeName)
                                    <option value="{{ $key }}" {{ isset($semestre) && $semestre == $key ? 'selected' : '' }}>
                                        {{ $periodeName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrer
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_all_statuses" name="include_all_statuses" value="1" {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
                                <label class="form-check-label" for="include_all_statuses">
                                    Inclure tous les étudiants (même ceux avec des inscriptions inactives)
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <!-- Section principale des résultats -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des résultats
                </div>
                <div class="main-card-subtitle">
                    @if(isset($etudiants) && $etudiants->count() > 0)
                        {{ $classe->name ?? 'Tous les étudiants' }} - {{ $semestre ? 'Semestre '.$semestre : 'Toutes les périodes' }}
                        @if(isset($anneeUniversitaire))
                            - Année {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}
                        @endif
                    @else
                        Aucun résultat trouvé
                    @endif
                </div>
                @if(isset($classe) && isset($etudiants) && $etudiants->count() > 0)
                <div class="main-card-actions">
                    <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id, 'annee_universitaire_id' => $annee_id, 'periode' => $semestre]) }}" class="btn-acasi primary">
                        <i class="fas fa-chart-bar"></i>Résultats détaillés
                    </a>
                </div>
                @endif
            </div>

            <div class="main-card-body">
                @if(isset($etudiants) && $etudiants->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th>Matricule</th>
                                    <th>Nom et prénom</th>
                                    @if(!isset($classe))
                                    <th>Classe</th>
                                    @endif
                                    <th>Moyenne</th>
                                    <th>Rang</th>
                                    <th>Statut</th>
                                    <th width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($etudiants as $etudiant)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input student-checkbox" type="checkbox" value="{{ $etudiant->id }}">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $etudiant->matricule }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2">
                                                    <i class="fas fa-user-graduate"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                                                    <small class="text-muted">{{ $etudiant->email ?? 'Pas d\'email' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        @if(!isset($classe))
                                        <td>
                                            @php
                                                $inscription = $etudiant->inscriptions->where('annee_universitaire_id', $annee_id)->first();
                                                $etudiantClasse = $inscription ? $inscription->classe : null;
                                            @endphp
                                            <span class="badge bg-light text-dark border">
                                                {{ $etudiantClasse ? $etudiantClasse->name : 'N/A' }}
                                            </span>
                                        </td>
                                        @endif
                                        <td>
                                            @if(isset($moyennes[$etudiant->id]))
                                                @php
                                                    $moyenne = $moyennes[$etudiant->id];
                                                    $badgeClass = $moyenne >= 16 ? 'success' : ($moyenne >= 14 ? 'info' : ($moyenne >= 12 ? 'warning' : ($moyenne >= 10 ? 'primary' : 'danger')));
                                                @endphp
                                                <span class="badge bg-{{ $badgeClass }} fs-6">
                                                    {{ number_format($moyenne, 2) }}/20
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($rangs[$etudiant->id]))
                                                <div class="d-flex align-items-center">
                                                    @php
                                                        $rang = $rangs[$etudiant->id];
                                                        $iconClass = $rang == 1 ? 'fa-trophy text-warning' : ($rang <= 3 ? 'fa-medal text-info' : 'fa-hashtag text-muted');
                                                    @endphp
                                                    <i class="fas {{ $iconClass }} me-2"></i>
                                                    <span class="fw-bold">{{ $rang }}<sup>{{ $rang == 1 ? 'er' : 'ème' }}</sup></span>
                                                    <small class="text-muted ms-1">/ {{ count($rangs) }}</small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($moyennes[$etudiant->id]))
                                                @if($moyennes[$etudiant->id] >= 10)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Admis
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Échec
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-question me-1"></i>Non évalué
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $inscription = $etudiant->inscriptions->where('annee_universitaire_id', $annee_id)->first();
                                                $studentClasseId = $inscription ? $inscription->classe_id : null;
                                                $actualClasseId = $classe_id ?? $studentClasseId;
                                            @endphp
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('esbtp.resultats.etudiant', ['etudiant' => $etudiant->id, 'classe_id' => $actualClasseId, 'annee_universitaire_id' => $annee_id, 'periode' => $semestre]) }}" class="btn btn-sm btn-info" title="Voir détails">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                                @if(isset($bulletins[$etudiant->id]))
                                                    <a href="{{ route('esbtp.bulletins.show', $bulletins[$etudiant->id]) }}" class="btn btn-sm btn-secondary" title="Voir bulletin">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $actualClasseId, 'periode' => $semestre, 'annee_universitaire_id' => $annee_id]) }}" class="btn btn-sm btn-danger" target="_blank" title="Télécharger PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                @else
                                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Bulletin non généré">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(isset($notes) && $notes->isEmpty())
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Aucune note trouvée.</strong> Vérifiez que :
                            <ul class="mb-0 mt-2">
                                <li>Les évaluations sont bien créées pour cette période</li>
                                <li>Les notes sont saisies et liées aux évaluations</li>
                                <li>Les coefficients des évaluations sont > 0</li>
                            </ul>
                        </div>
                    @endif
                @elseif(isset($classe))
                    <div class="text-center py-5">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun étudiant trouvé</h5>
                        <p class="text-muted">Aucun étudiant trouvé pour cette classe et cette période.</p>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Sélectionnez vos critères</h5>
                        <p class="text-muted">Veuillez sélectionner une classe, une année universitaire et une période pour afficher les résultats.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 only if available
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%'
        });
    } else {
        console.log('Select2 not available, skipping initialization');
    }

    // Handle bulk selection
    $('#select-all').change(function() {
        $('.student-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('.student-checkbox').change(function() {
        const totalCheckboxes = $('.student-checkbox').length;
        const checkedCheckboxes = $('.student-checkbox:checked').length;
        $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Auto-select academic year when class is selected
    $('#classe_id').change(function() {
        const classeId = $(this).val();
        if (classeId) {
            // Make an AJAX request to get class details
            $.ajax({
                url: '/esbtp/api/classes/' + classeId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.annee_universitaire_id) {
                        $('#annee_universitaire_id').val(data.annee_universitaire_id).trigger('change');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching class data:', error);
                }
            });
        }
    });

    // Search functionality
    $('.search-bar').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('tbody tr').each(function() {
            const studentName = $(this).find('td:nth-child(3) .fw-semibold').text().toLowerCase();
            const matricule = $(this).find('td:nth-child(2)').text().toLowerCase();
            
            if (studentName.includes(searchTerm) || matricule.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endpush