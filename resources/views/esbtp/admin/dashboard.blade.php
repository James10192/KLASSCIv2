@extends('layouts.app')

@section('title', 'Tableau de Bord')
p
@section('page-title', 'Tableau de Bord Super Administrateur')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card card-stats">
                <div class="card-header card-header-primary">
                    <i class="fa fa-users fa-2x"></i>
                </div>
                <div class="card-body">
                    <h4 class="card-title">Étudiants</h4>
                    <p class="card-category">{{ $stats['totalEtudiants'] }} inscrits</p>
                </div>
                <div class="card-footer">
                    <div class="stats">
                        <a href="{{ route('esbtp.etudiants.index') }}" class="text-primary">Voir la liste com plète</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card card-stats">
                <div class="card-header card-header-success">
                    <i class="fa fa-school fa-2x"></i>
                </div>
                <div class="card-body">
                    <h4 class="card-title">Classes</h4>
                    <p class="card-category">{{ $stats['totalClasses'] }} créées</p>
                </div>
                <div class="card-footer">
                    <div class="stats">
                        <a href="{{ route('esbtp.classes.index') }}" class="text-success">Gérer les classes</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card card-stats">
                <div class="card-header card-header-warning">
                    <i class="fa fa-graduation-cap fa-2x"></i>
                </div>
                <div class="card-body">
                    <h4 class="card-title">Filières</h4>
                    <p class="card-category">{{ $stats['totalFilieres'] }} disponibles</p>
                </div>
                <div class="card-footer">
                    <div class="stats">
                        <a href="{{ route('esbtp.filieres.index') }}" class="text-warning">Voir les filières</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card card-stats">
                <div class="card-header card-header-info">
                    <i class="fa fa-book fa-2x"></i>
                </div>
                <div class="card-body">
                    <h4 class="card-title">Matières</h4>
                    <p class="card-category">{{ $stats['totalMatieres'] }} enregistrées</p>
                </div>
                <div class="card-footer">
                    <div class="stats">
                        <a href="{{ route('esbtp.matieres.index') }}" class="text-info">Gérer les matières</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header card-header-primary">
                    <h4 class="card-title">Derniers Étudiants Inscrits</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="text-primary">
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Date d'inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dernierEtudiants as $etudiant)
                                <tr>
                                    <td>{{ $etudiant->user->name }}</td>
                                    <td>{{ $etudiant->user->email }}</td>
                                    <td>{{ $etudiant->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Aucun étudiant inscrit récemment</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Actions Rapides</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('esbtp.inscriptions.create') }}" class="btn btn-primary btn-block">
                                <i class="fa fa-user-plus"></i> Ajouter un Étudiant
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('esbtp.classes.create') }}" class="btn btn-success btn-block">
                                <i class="fa fa-plus-circle"></i> Créer une Classe
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('esbtp.matieres.create') }}" class="btn btn-info btn-block">
                                <i class="fa fa-book-medical"></i> Ajouter une Matière
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn btn-warning btn-block">
                                <i class="fa fa-calendar-alt"></i> Créer un Emploi du Temps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
