@extends('layouts.app')

@section('title', 'Planning Général - Test de Planification')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3>🎯 Planning Général - Interface de Planification Académique</h3>
                    <p class="text-muted">Outil de définition du planning annuel par filière/niveau</p>
                </div>
                
                <div class="card-body">
                    <!-- Formulaire de sélection -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="annee_id" class="form-label">Année Universitaire</label>
                                <select name="annee_id" id="annee_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Sélectionner une année</option>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                            {{ $annee->name }}
                                            @if($annee->is_current) (En cours) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="filiere_id" class="form-label">Filière</label>
                                <select name="filiere_id" id="filiere_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Sélectionner une filière</option>
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}" {{ $filiereId == $filiere->id ? 'selected' : '' }}>
                                            {{ $filiere->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="niveau_id" class="form-label">Niveau</label>
                                <select name="niveau_id" id="niveau_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Sélectionner un niveau</option>
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}" {{ $niveauId == $niveau->id ? 'selected' : '' }}>
                                            {{ $niveau->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="semestre" class="form-label">Semestre</label>
                                <select name="semestre" id="semestre" class="form-select" onchange="this.form.submit()">
                                    <option value="1" {{ $semestre == 1 ? 'selected' : '' }}>Semestre 1</option>
                                    <option value="2" {{ $semestre == 2 ? 'selected' : '' }}>Semestre 2</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Garder les autres paramètres -->
                        @if($anneeId) <input type="hidden" name="annee_id" value="{{ $anneeId }}"> @endif
                        @if($filiereId) <input type="hidden" name="filiere_id" value="{{ $filiereId }}"> @endif
                        @if($niveauId) <input type="hidden" name="niveau_id" value="{{ $niveauId }}"> @endif
                        <input type="hidden" name="semestre" value="{{ $semestre }}">
                    </form>

                    @if($anneeSelectionnee && $filiereSelectionnee && $niveauSelectionne)
                        <!-- Affichage de la sélection -->
                        <div class="alert alert-info">
                            <h5>📋 Planification pour :</h5>
                            <ul class="mb-0">
                                <li><strong>Année :</strong> {{ $anneeSelectionnee->name }}</li>
                                <li><strong>Filière :</strong> {{ $filiereSelectionnee->name }}</li>
                                <li><strong>Niveau :</strong> {{ $niveauSelectionne->name }}</li>
                                <li><strong>Semestre :</strong> {{ $semestre }}</li>
                            </ul>
                        </div>

                        <!-- Statistiques -->
                        @if($statistiques)
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $statistiques['total_matieres_planifiees'] }}</h3>
                                        <p>Matières Planifiées</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $statistiques['total_heures_planifiees'] }}h</h3>
                                        <p>Heures Planifiées</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $statistiques['total_enseignants_assignes'] }}</h3>
                                        <p>Enseignants Assignés</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $statistiques['taux_completion'] }}%</h3>
                                        <p>Taux de Completion</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Formulaire d'ajout de planification -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>➕ Ajouter une Planification</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('esbtp.planning-general.store-planification') }}">
                                    @csrf
                                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeId }}">
                                    <input type="hidden" name="filiere_id" value="{{ $filiereId }}">
                                    <input type="hidden" name="niveau_etude_id" value="{{ $niveauId }}">
                                    <input type="hidden" name="semestre" value="{{ $semestre }}">
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="matiere_id" class="form-label">Matière *</label>
                                            <select name="matiere_id" id="matiere_id" class="form-select" required>
                                                <option value="">Choisir une matière</option>
                                                @foreach($matieres as $matiere)
                                                    <option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_total" class="form-label">Vol. Total (h) *</label>
                                            <input type="number" name="volume_horaire_total" id="volume_horaire_total" 
                                                   class="form-control" min="1" max="200" required>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_cm" class="form-label">CM (h)</label>
                                            <input type="number" name="volume_horaire_cm" id="volume_horaire_cm" 
                                                   class="form-control" min="0">
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_td" class="form-label">TD (h)</label>
                                            <input type="number" name="volume_horaire_td" id="volume_horaire_td" 
                                                   class="form-control" min="0">
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="volume_horaire_tp" class="form-label">TP (h)</label>
                                            <input type="number" name="volume_horaire_tp" id="volume_horaire_tp" 
                                                   class="form-control" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <label for="enseignant_principal_id" class="form-label">Enseignant Principal</label>
                                            <select name="enseignant_principal_id" id="enseignant_principal_id" class="form-select">
                                                <option value="">Assigner plus tard</option>
                                                @foreach($enseignants as $enseignant)
                                                    <option value="{{ $enseignant->id }}">{{ $enseignant->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="coefficient" class="form-label">Coefficient</label>
                                            <input type="number" name="coefficient" id="coefficient" 
                                                   class="form-control" min="0.5" max="10" step="0.5" value="1">
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="credits_ects" class="form-label">Crédits ECTS</label>
                                            <input type="number" name="credits_ects" id="credits_ects" 
                                                   class="form-control" min="1" max="30">
                                        </div>
                                        
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Ajouter la Planification
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Liste des planifications existantes -->
                        <div class="card">
                            <div class="card-header">
                                <h5>📚 Planifications Existantes ({{ $planifications->count() }})</h5>
                            </div>
                            <div class="card-body">
                                @if($planifications->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Matière</th>
                                                    <th>Vol. Total</th>
                                                    <th>CM/TD/TP</th>
                                                    <th>Enseignant</th>
                                                    <th>Coeff.</th>
                                                    <th>ECTS</th>
                                                    <th>Statut</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($planifications as $planification)
                                                <tr>
                                                    <td><strong>{{ $planification->matiere->nom ?? 'N/A' }}</strong></td>
                                                    <td>{{ $planification->volume_horaire_total }}h</td>
                                                    <td>
                                                        <small>
                                                            CM: {{ $planification->volume_horaire_cm }}h<br>
                                                            TD: {{ $planification->volume_horaire_td }}h<br>
                                                            TP: {{ $planification->volume_horaire_tp }}h
                                                        </small>
                                                    </td>
                                                    <td>{{ $planification->enseignantPrincipal->name ?? 'Non assigné' }}</td>
                                                    <td>{{ $planification->coefficient }}</td>
                                                    <td>{{ $planification->credits_ects }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $planification->statut == 'valide' ? 'success' : 'warning' }}">
                                                            {{ ucfirst($planification->statut) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($planification->isModifiable())
                                                            <form method="POST" action="{{ route('esbtp.planning-general.destroy-planification', $planification->id) }}" 
                                                                  style="display: inline;" onsubmit="return confirm('Supprimer cette planification ?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        @if($planification->statut == 'planifie')
                                                            <form method="POST" action="{{ route('esbtp.planning-general.valider-planification', $planification->id) }}" 
                                                                  style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                                    <i class="fas fa-check"></i> Valider
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                        <p>Aucune planification pour cette sélection.</p>
                                        <p>Utilisez le formulaire ci-dessus pour commencer la planification.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                    @else
                        <!-- Message d'invite -->
                        <div class="text-center py-5">
                            <i class="fas fa-arrow-up fa-3x text-muted mb-3"></i>
                            <h4>Sélectionnez d'abord une Année, Filière et Niveau</h4>
                            <p class="text-muted">pour commencer la planification académique</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="position-fixed bottom-0 end-0 p-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erreurs :</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="position-fixed bottom-0 end-0 p-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif
@endsection