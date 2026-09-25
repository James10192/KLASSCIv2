@extends('layouts.app')

@section('title', 'Gestion des frais de scolarité')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Gestion des frais de scolarité</h5>
            <a href="{{ route('esbtp.comptabilite.frais-scolarite.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle configuration
            </a>
        </div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            <!-- Filtres de recherche -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i> Filtres
                </div>
                <div class="card-body">
                    <form action="{{ route('esbtp.comptabilite.frais-scolarite') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="filiere" class="form-label">Filière</label>
                            <select class="form-select" id="filiere" name="filiere">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres ?? [] as $filiere)
                                <option value="{{ $filiere->id }}" {{ request('filiere') == $filiere->id ? 'selected' : '' }}>
                                    {{ $filiere->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="niveau" class="form-label">Niveau d'études</label>
                            <select class="form-select" id="niveau" name="niveau">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux ?? [] as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau') == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Rechercher
                            </button>
                            <a href="{{ route('esbtp.comptabilite.frais-scolarite') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des frais de scolarité -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th>Année universitaire</th>
                            <th>Montant total</th>
                            <th>Frais d'inscription</th>
                            <th>Nombre d'échéances</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fsc-tbody">
                        @forelse($fraisScolarites as $frais)
                        @include('esbtp.comptabilite.frais-scolarite._ligne')
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Aucune configuration de frais de scolarité trouvée</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <x-liste-infinie :paginateur="$fraisScolarites" cible="#fsc-tbody" libelle="frais" />
            
            <!-- Tableau récapitulatif -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i> Informations importantes
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-lightbulb me-2"></i>Guide d'utilisation</h5>
                        <ul class="mb-0">
                            <li>Les frais de scolarité doivent être configurés pour chaque combinaison de filière, niveau d'études et année universitaire.</li>
                            <li>Le montant total représente l'ensemble des frais pour l'année universitaire complète.</li>
                            <li>Les frais d'inscription sont généralement payés en une fois lors de l'inscription.</li>
                            <li>Le nombre d'échéances permet de diviser le reste des frais en plusieurs paiements échelonnés.</li>
                            <li>Une configuration inactive n'est pas utilisée pour les nouveaux étudiants mais reste appliquée pour les paiements existants.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 