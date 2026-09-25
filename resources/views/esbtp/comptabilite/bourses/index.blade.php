@extends('layouts.app')

@section('title', 'Gestion des bourses')

@section('content')
<div class="container-fluid">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="fas fa-graduation-cap fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Gestion des bourses</h1>
                <div class="text-white-50">Suivi et gestion des bourses étudiantes</div>
            </div>
        </div>
        <a href="{{ route('esbtp.comptabilite.bourses.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
            <i class="fas fa-plus-circle"></i> Nouvelle bourse
        </a>
    </div>

    <div class="container-fluid animate-fade-in-up">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-md-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass mb-4">
                    <div class="card-body p-0">
                        @if(session('success'))
                        <div class="alert alert-success d-flex align-items-center glass-alert mb-4">
                            <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        @endif
                        @if(session('error'))
                        <div class="alert alert-danger d-flex align-items-center glass-alert mb-4">
                            <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        @endif
                        <!-- Filtres de recherche -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4 premium-glass">
                            <div class="card-header bg-white border-0 rounded-top-4">
                                <i class="fas fa-filter me-1"></i> Filtres
                            </div>
                            <div class="card-body">
                                <form action="{{ route('esbtp.comptabilite.bourses') }}" method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label for="etudiant" class="form-label">Étudiant</label>
                                        <input type="text" class="form-control" id="etudiant" name="etudiant" value="{{ request('etudiant') }}" placeholder="Nom de l'étudiant">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="type_bourse" class="form-label">Type de bourse</label>
                                        <select class="form-select" id="type_bourse" name="type_bourse">
                                            <option value="">Tous les types</option>
                                            <option value="mérite" {{ request('type_bourse') == 'mérite' ? 'selected' : '' }}>Bourse au mérite</option>
                                            <option value="sociale" {{ request('type_bourse') == 'sociale' ? 'selected' : '' }}>Bourse sociale</option>
                                            <option value="excellence" {{ request('type_bourse') == 'excellence' ? 'selected' : '' }}>Bourse d'excellence</option>
                                            <option value="partielle" {{ request('type_bourse') == 'partielle' ? 'selected' : '' }}>Bourse partielle</option>
                                            <option value="complète" {{ request('type_bourse') == 'complète' ? 'selected' : '' }}>Bourse complète</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="annee" class="form-label">Année universitaire</label>
                                        <select class="form-select" id="annee" name="annee">
                                            <option value="">Toutes les années</option>
                                            @foreach($annees ?? [] as $annee)
                                            <option value="{{ $annee->id }}" {{ request('annee') == $annee->id ? 'selected' : '' }}>
                                                {{ $annee->nom }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="statut" class="form-label">Statut</label>
                                        <select class="form-select" id="statut" name="statut">
                                            <option value="">Tous les statuts</option>
                                            <option value="active" {{ request('statut') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="suspendue" {{ request('statut') == 'suspendue' ? 'selected' : '' }}>Suspendue</option>
                                            <option value="terminée" {{ request('statut') == 'terminée' ? 'selected' : '' }}>Terminée</option>
                                        </select>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i> Rechercher
                                        </button>
                                        <a href="{{ route('esbtp.comptabilite.bourses') }}" class="btn btn-secondary">
                                            <i class="fas fa-redo me-1"></i> Réinitialiser
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Tableau des bourses -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle premium-table mb-0">
                                <thead class="sticky-top bg-gradient-primary text-white rounded-top-4">
                                    <tr>
                                        <th>ID</th>
                                        <th>Étudiant</th>
                                        <th>Type</th>
                                        <th>Montant/Pourcentage</th>
                                        <th>Date début</th>
                                        <th>Date fin</th>
                                        <th>Statut</th>
                                        <th>Organisme financeur</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="bou-tbody">
                                    @forelse($bourses as $bourse)
                                    @include('esbtp.comptabilite.bourses._ligne')
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Aucune bourse trouvée</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-liste-infinie :paginateur="$bourses" cible="#bou-tbody" libelle="bourses" />
                        <!-- Informations complémentaires -->
                        <div class="card mt-4 border-0 shadow-sm rounded-4 premium-glass">
                            <div class="card-header bg-white border-0 rounded-top-4">
                                <i class="fas fa-info-circle me-1"></i> Informations sur les bourses
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info glass-alert">
                                    <h5><i class="fas fa-lightbulb me-2"></i>Guide d'utilisation</h5>
                                    <ul class="mb-0">
                                        <li>Les bourses peuvent être attribuées à des étudiants selon différents critères (mérite, sociale, etc.).</li>
                                        <li>Une bourse peut être définie par un montant fixe ou un pourcentage des frais de scolarité.</li>
                                        <li>Chaque bourse est associée à une année universitaire et a une période de validité définie.</li>
                                        <li>Le statut d'une bourse peut être "active", "suspendue" ou "terminée".</li>
                                        <li>L'organisme financeur est l'entité qui finance la bourse (école, gouvernement, entreprise, etc.).</li>
                                    </ul>
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