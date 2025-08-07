@extends('layouts.app')

@section('title', 'Créer un emploi du temps - ESBTP-yAKRO')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    /* Animation pour la flèche */
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }
    
    /* Amélioration des gradients */
    .bg-gradient-primary {
        background: linear-gradient(45deg, #007bff, #0056b3) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #28a745, #1e7e34) !important;
    }
    
    /* Amélioration des cartes */
    .card.border-primary {
        box-shadow: 0 0 20px rgba(0, 123, 255, 0.15) !important;
    }
    
    /* Amélioration des badges */
    .badge.fs-6 {
        font-size: 0.875rem !important;
        padding: 0.5rem 1rem !important;
    }
    
    /* Style des progress bars */
    .progress {
        border-radius: 10px;
        background-color: #e9ecef;
    }
    
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    
    /* Amélioration des alertes */
    .alert {
        border-radius: 15px;
    }
    
    /* Style pour les icônes circulaires */
    .rounded-circle {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Amélioration des tableaux */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
        transform: translateX(2px);
        transition: all 0.2s ease;
    }
    
    /* Style pour les cards avec shadow */
    .shadow-lg {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Créer un nouvel emploi du temps</h5>
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.emploi-temps.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="titre" class="form-label">Titre de l'emploi du temps *</label>
                                    <input type="text" class="form-control @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre') }}" required>
                                    @error('titre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ex: Emploi du temps BTS 1ère année Génie Civil - Semestre 1</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="classe_id" class="form-label">Classe *</label>
                                    <select class="form-select @error('classe_id') is-invalid @enderror" id="classe_id" name="classe_id" required>
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $classe)
                                            <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                                {{ $classe->name }} ({{ $classe->filiere->name }} - {{ $classe->niveau->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('classe_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="annee_universitaire_id" class="form-label">Année universitaire *</label>
                                    <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                                        <option value="">Sélectionner une année universitaire</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}" {{ 
                                                old('annee_universitaire_id') == $annee->id || 
                                                (empty(old('annee_universitaire_id')) && isset($anneeActive) && $anneeActive->id == $annee->id) ||
                                                request('annee_universitaire_id') == $annee->id
                                                ? 'selected' : '' }}>
                                                {{ $annee->name }}
                                                @if($anneeActive && $anneeActive->id == $annee->id)
                                                    (Année en cours)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('annee_universitaire_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="semestre" class="form-label">Période *</label>
                                    <select class="form-select @error('semestre') is-invalid @enderror" id="semestre" name="semestre" required>
                                        <option value="">Sélectionner une période</option>
                                        <option value="Semestre 1">Semestre 1</option>
                                        <option value="Semestre 2">Semestre 2</option>
                                        <option value="Année complète">Année complète</option>
                                    </select>
                                    @error('semestre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section d'information sur la planification académique -->
                        @if(request('classe_id'))
                            <div class="alert alert-info">
                                <strong>Debug:</strong> Classe ID reçu = {{ request('classe_id') }}
                                @if(isset($classeSelectionnee))
                                    | Classe trouvée = {{ $classeSelectionnee->name ?? 'NULL' }}
                                @else
                                    | Classe non trouvée en base
                                @endif
                                @if(isset($planificationData))
                                    | Données planification = OUI
                                @else
                                    | Données planification = NON
                                @endif
                            </div>
                        @endif
                        
                        <!-- Section Planification Académique - TOUJOURS VISIBLE -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-primary shadow-lg" style="border-width: 3px !important;">
                                    <div class="card-header bg-gradient-primary text-white py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1">
                                                    <i class="fas fa-calendar-check me-2"></i>
                                                    <strong>Planification Académique & Suivi des Heures</strong>
                                                </h5>
                                                <small class="opacity-75">Vérifiez la charge horaire planifiée avant de créer l'emploi du temps</small>
                                            </div>
                                            <div id="status-indicator">
                                                @if(isset($planificationData) && $classeSelectionnee)
                                                    @if($planificationData['planifications_configurees'])
                                                        <span class="badge bg-success fs-6 px-3 py-2">
                                                            <i class="fas fa-check-circle me-1"></i>Configurée
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>Non configurée
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary fs-6 px-3 py-2">
                                                        <i class="fas fa-hourglass-half me-1"></i>En attente
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body p-4">
                                        @if(isset($planificationData) && $classeSelectionnee)
                                            @if($planificationData['planifications_configurees'])
                                                <!-- PLANIFICATION CONFIGURÉE -->
                                                <div class="alert alert-success border-success mb-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="fas fa-check-circle fa-2x text-success"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1"><strong>Planification configurée avec succès ✅</strong></h6>
                                                            <p class="mb-0">
                                                                {{ $planificationData['matieres_planifiees']->count() }} matière(s) planifiée(s) 
                                                                pour la classe <strong>{{ $classeSelectionnee->name }}</strong>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-lg-8">
                                                        <div class="card bg-light border-0">
                                                            <div class="card-header bg-primary text-white">
                                                                <h6 class="mb-0">
                                                                    <i class="fas fa-book-open me-2"></i>
                                                                    Détail des matières et progression
                                                                </h6>
                                                            </div>
                                                            <div class="card-body p-0">
                                                                <div class="table-responsive">
                                                                    <table class="table table-hover mb-0">
                                                                        <thead class="table-dark">
                                                                            <tr>
                                                                                <th width="30%">Matière</th>
                                                                                <th width="20%">Enseignant</th>
                                                                                <th width="15%" class="text-center">H. Planifiées</th>
                                                                                <th width="15%" class="text-center">H. Restantes</th>
                                                                                <th width="20%" class="text-center">Progression</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($planificationData['matieres_planifiees'] as $index => $matiere)
                                                                            <tr class="{{ $matiere['heures_restantes'] <= 0 ? 'table-warning' : ($index % 2 == 0 ? 'table-light' : '') }}">
                                                                                <td>
                                                                                    <div class="d-flex align-items-center">
                                                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; flex-shrink: 0;">
                                                                                            <i class="fas fa-book" style="font-size: 12px;"></i>
                                                                                        </div>
                                                                                        <div>
                                                                                            <strong class="d-block">{{ $matiere['matiere']->name }}</strong>
                                                                                            <small class="text-muted">
                                                                                                Vol. total: {{ $matiere['volume_horaire_total'] }}h
                                                                                                @if(isset($matiere['semestre']))
                                                                                                    | S{{ $matiere['semestre'] }}
                                                                                                @endif
                                                                                            </small>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    @if($matiere['enseignant_principal'])
                                                                                        <div class="d-flex align-items-center">
                                                                                            <i class="fas fa-user-tie text-secondary me-2"></i>
                                                                                            <span class="text-truncate">{{ $matiere['enseignant_principal']->name }}</span>
                                                                                        </div>
                                                                                    @else
                                                                                        <span class="text-muted fst-italic">
                                                                                            <i class="fas fa-user-slash me-1"></i>Non assigné
                                                                                        </span>
                                                                                    @endif
                                                                                </td>
                                                                                <td class="text-center">
                                                                                    <span class="badge bg-primary fs-6 px-3">{{ $matiere['volume_horaire_total'] }}h</span>
                                                                                </td>
                                                                                <td class="text-center">
                                                                                    <span class="badge bg-{{ $matiere['heures_restantes'] > 0 ? 'success' : 'warning' }} fs-6 px-3">
                                                                                        {{ $matiere['heures_restantes'] }}h
                                                                                    </span>
                                                                                </td>
                                                                                <td class="text-center">
                                                                                    @php
                                                                                        $pourcentage = $matiere['pourcentage_utilise'] ?? 0;
                                                                                        $progressColor = $pourcentage >= 100 ? 'success' : ($pourcentage >= 75 ? 'warning' : 'info');
                                                                                    @endphp
                                                                                    <div class="progress mb-1" style="height: 20px; min-width: 80px;">
                                                                                        <div class="progress-bar bg-{{ $progressColor }}" 
                                                                                             role="progressbar" 
                                                                                             style="width: {{ min(100, $pourcentage) }}%"
                                                                                             title="{{ $pourcentage }}% effectué">
                                                                                            <small><strong>{{ $pourcentage }}%</strong></small>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-lg-4">
                                                        <!-- Résumé global avec style amélioré -->
                                                        <div class="card border-0 shadow-sm h-100">
                                                            <div class="card-header bg-gradient-success text-white">
                                                                <h6 class="mb-0 text-center">
                                                                    <i class="fas fa-chart-pie me-2"></i>
                                                                    <strong>Résumé Global</strong>
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- Statistiques principales -->
                                                                <div class="row text-center mb-4">
                                                                    <div class="col-6">
                                                                        <div class="bg-primary text-white p-3 rounded-3 shadow-sm">
                                                                            <h3 class="mb-1">{{ $planificationData['heures_totales'] }}</h3>
                                                                            <small><strong>Heures planifiées</strong></small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="bg-success text-white p-3 rounded-3 shadow-sm">
                                                                            <h3 class="mb-1">{{ $planificationData['heures_restantes'] }}</h3>
                                                                            <small><strong>Heures restantes</strong></small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Détails supplémentaires -->
                                                                <div class="mb-3">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <span><i class="fas fa-books text-primary me-2"></i>Matières :</span>
                                                                        <strong>{{ $planificationData['matieres_planifiees']->count() }}</strong>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span><i class="fas fa-graduation-cap text-info me-2"></i>Classe :</span>
                                                                        <strong class="text-primary">{{ $classeSelectionnee->name }}</strong>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Alerte selon statut -->
                                                                @if($planificationData['heures_restantes'] <= 0)
                                                                    <div class="alert alert-warning border-warning">
                                                                        <div class="text-center">
                                                                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                                                            <h6><strong>Attention</strong></h6>
                                                                            <p class="mb-0 small">Toutes les heures de cette classe ont été programmées.</p>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="alert alert-success border-success">
                                                                        <div class="text-center">
                                                                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                                                                            <h6><strong>Parfait !</strong></h6>
                                                                            <p class="mb-0 small">Il reste {{ $planificationData['heures_restantes'] }}h à programmer pour cette classe.</p>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <!-- PLANIFICATION NON CONFIGURÉE -->
                                                <div class="alert alert-warning border-warning py-4">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-2 text-center">
                                                            <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <h5 class="alert-heading mb-2">
                                                                <strong>Planification académique non configurée</strong>
                                                            </h5>
                                                            <p class="mb-2">{{ $planificationData['message_configuration'] }}</p>
                                                            <p class="mb-0 text-muted small">
                                                                <i class="fas fa-lightbulb me-1"></i>
                                                                <strong>Conseil :</strong> Configurez d'abord la planification académique pour définir les heures de cours par matière et optimiser la création de l'emploi du temps.
                                                            </p>
                                                        </div>
                                                        <div class="col-md-2 text-center">
                                                            @if($planificationData['lien_configuration'])
                                                                <a href="{{ $planificationData['lien_configuration'] }}" 
                                                                   class="btn btn-warning btn-lg shadow" target="_blank">
                                                                    <i class="fas fa-cog me-2"></i>
                                                                    <strong>Configurer</strong>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <!-- AUCUNE CLASSE SÉLECTIONNÉE -->
                                            <div class="text-center py-5">
                                                <div class="mb-4">
                                                    <i class="fas fa-arrow-up text-primary" style="font-size: 4rem; animation: bounce 2s infinite;"></i>
                                                </div>
                                                <h4 class="text-primary mb-3"><strong>Sélectionnez d'abord une classe</strong></h4>
                                                <p class="text-muted mb-4">
                                                    Les informations de planification académique s'afficheront automatiquement<br>
                                                    après avoir choisi une classe dans le formulaire ci-dessus.
                                                </p>
                                                <div class="row justify-content-center">
                                                    <div class="col-md-6">
                                                        <div class="card bg-light border-0">
                                                            <div class="card-body">
                                                                <h6 class="text-primary mb-3">
                                                                    <i class="fas fa-info-circle me-2"></i>Informations affichées
                                                                </h6>
                                                                <ul class="list-unstyled text-start mb-0">
                                                                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Matières planifiées</li>
                                                                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Heures totales par matière</li>
                                                                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Heures restantes à programmer</li>
                                                                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Enseignants assignés</li>
                                                                    <li><i class="fas fa-check text-success me-2"></i>Progression par matière</li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_debut" class="form-label">Date de début *</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', $semaineCourante['date_debut'] ?? '') }}" required>
                                        <button type="button" class="btn btn-outline-secondary" id="btn-semaine-courante">
                                            <i class="fas fa-calendar-week"></i> Semaine courante
                                        </button>
                                    </div>
                                    @error('date_debut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_fin" class="form-label">Date de fin *</label>
                                    <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', $semaineCourante['date_fin'] ?? '') }}" required>
                                    @error('date_fin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> La période doit être de 6 jours maximum (du lundi au samedi).
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Emploi du temps actif
                            </label>
                            <div class="form-text">
                                <span class="text-info"><i class="fas fa-info-circle me-1"></i>Info :</span>
                                Un seul emploi du temps peut être actif par classe à la fois. Si vous activez cet emploi du temps, les autres emplois du temps pour la même classe seront automatiquement désactivés et celui-ci sera défini comme l'emploi du temps courant.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Remarque importante</h6>
                            <p class="mb-0">Après avoir créé l'emploi du temps, vous pourrez y ajouter des séances de cours. Assurez-vous que la classe sélectionnée a des matières et des enseignants assignés avant de créer l'emploi du temps.</p>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Amélioration des listes déroulantes avec Select2
        $('#classe_id, #annee_universitaire_id, #semestre').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionnez un élément'
        });

        // Attendre que Select2 soit chargé puis attacher l'événement
        $(document).ready(function() {
            // Vérifier si jQuery et Select2 sont disponibles
            if (typeof $ === 'undefined') {
                console.error('jQuery non disponible');
                return;
            }
            
            // Attendre un peu que Select2 soit initialisé
            setTimeout(function() {
                // Fonction pour recharger les données de planification
                function rechargerPlanification() {
                    const classeId = $('#classe_id').val();
                    const anneeId = $('#annee_universitaire_id').val();
                    
                    if (classeId && anneeId) {
                        // Construire l'URL avec les paramètres nécessaires
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('classe_id', classeId);
                        currentUrl.searchParams.set('annee_universitaire_id', anneeId);
                        
                        // Conserver les autres champs déjà remplis
                        const titre = document.getElementById('titre').value;
                        const dateDebut = document.getElementById('date_debut').value;
                        const dateFin = document.getElementById('date_fin').value;
                        const semestre = $('#semestre').val();
                        const isActive = document.getElementById('is_active').checked;
                        
                        if (titre) currentUrl.searchParams.set('titre', titre);
                        if (dateDebut) currentUrl.searchParams.set('date_debut', dateDebut);
                        if (dateFin) currentUrl.searchParams.set('date_fin', dateFin);
                        if (semestre) currentUrl.searchParams.set('semestre', semestre);
                        if (isActive) currentUrl.searchParams.set('is_active', '1');
                        
                        // Rediriger vers l'URL mise à jour
                        window.location.href = currentUrl.toString();
                    }
                }

                // Événements sur classe et année universitaire
                $('#classe_id').on('change', rechargerPlanification);
                $('#annee_universitaire_id').on('change', rechargerPlanification);
            }, 500); // Attendre 500ms
            
            // Alternative : événement direct sur les selects (au cas où Select2 pose problème)
            function fallbackRechargerPlanification() {
                const classeId = document.getElementById('classe_id').value;
                const anneeId = document.getElementById('annee_universitaire_id').value;
                
                if (classeId && anneeId) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('classe_id', classeId);
                    currentUrl.searchParams.set('annee_universitaire_id', anneeId);
                    console.log('Redirection vers:', currentUrl.toString());
                    window.location.href = currentUrl.toString();
                }
            }
            
            document.getElementById('classe_id').addEventListener('change', fallbackRechargerPlanification);
            document.getElementById('annee_universitaire_id').addEventListener('change', fallbackRechargerPlanification);
        });

        // Restaurer les valeurs depuis les paramètres URL au chargement
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Restaurer les valeurs des champs depuis l'URL
            if (urlParams.get('titre')) {
                document.getElementById('titre').value = urlParams.get('titre');
            }
            if (urlParams.get('date_debut')) {
                document.getElementById('date_debut').value = urlParams.get('date_debut');
            }
            if (urlParams.get('date_fin')) {
                document.getElementById('date_fin').value = urlParams.get('date_fin');
            }
            if (urlParams.get('annee_universitaire_id')) {
                $('#annee_universitaire_id').val(urlParams.get('annee_universitaire_id')).trigger('change');
            }
            if (urlParams.get('semestre')) {
                $('#semestre').val(urlParams.get('semestre')).trigger('change');
            }
            if (urlParams.get('is_active') === '1') {
                document.getElementById('is_active').checked = true;
            }
        });

        // Bouton pour définir la semaine courante
        document.getElementById('btn-semaine-courante').addEventListener('click', function() {
            document.getElementById('date_debut').value = '{{ $semaineCourante['date_debut'] }}';
            document.getElementById('date_fin').value = '{{ $semaineCourante['date_fin'] }}';
        });

        // Validation côté client pour la période de 5 jours maximum
        document.getElementById('date_fin').addEventListener('change', function() {
            const dateDebut = new Date(document.getElementById('date_debut').value);
            const dateFin = new Date(this.value);

            // Calculer la différence en jours
            const diffTime = Math.abs(dateFin - dateDebut);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 4) {
                alert('La période de l\'emploi du temps ne doit pas dépasser 5 jours (du lundi au vendredi).');
                this.value = '';
            }
        });
    });
</script>
@endsection
