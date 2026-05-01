@extends('layouts.app')

@section('title', 'Liste des années universitaires')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Années universitaires</h1>
                <p class="header-subtitle">Gestion des années universitaires de l'établissement</p>
            </div>
            <div class="header-actions">
                @can('annees.create')
                <a href="{{ route('esbtp.annees-universitaires.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle année universitaire
                </a>
                @endcan
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
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

                <!-- Tableau des années universitaires -->
                <div class="section-title mb-md">
                    <i class="fas fa-list"></i>Liste des années universitaires
                </div>
                
                @if($anneesUniversitaires->isEmpty())
                    <div class="alert alert-info">
                        Aucune année universitaire n'a été créée.
                        @can('annees.create')
                            <a href="{{ route('esbtp.annees-universitaires.create') }}">Créer une année universitaire</a>
                        @endcan
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Nom</th>
                                    <th>Date de rentrée</th>
                                    <th>Date de fin</th>
                                    <th>Description</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                                <tbody>
                                    @foreach($anneesUniversitaires->whereNotNull() as $anneeUniversitaire)
                                        @if($anneeUniversitaire)
                                        <tr class="{{ optional($anneeUniversitaire)->is_current ? 'table-success' : '' }}" style="{{ optional($anneeUniversitaire)->is_current ? 'background-color: #d4edda !important;' : '' }}">
                                            <td>{{ $anneeUniversitaire->name ?? 'N/A' }}</td>
                                            <td>{{ $anneeUniversitaire->start_date ? (is_a($anneeUniversitaire->start_date, 'Carbon\Carbon') ? $anneeUniversitaire->start_date->format('d/m/Y') : (\Carbon\Carbon::parse($anneeUniversitaire->start_date)->format('d/m/Y'))) : '-' }}</td>
                                            <td>{{ $anneeUniversitaire->end_date ? (is_a($anneeUniversitaire->end_date, 'Carbon\Carbon') ? $anneeUniversitaire->end_date->format('d/m/Y') : (\Carbon\Carbon::parse($anneeUniversitaire->end_date)->format('d/m/Y'))) : '-' }}</td>
                                            <td>{{ Str::limit($anneeUniversitaire->description, 100) }}</td>
                                            <td>
                                                @if(optional($anneeUniversitaire)->is_current)
                                                    <span class="badge bg-success px-3 py-2">ANNÉE EN COURS</span>
                                                @elseif($anneeUniversitaire->is_active)
                                                    <span class="badge bg-info px-3 py-2">Active</span>
                                                @else
                                                    <span class="badge bg-secondary px-3 py-2">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    @can('annees.view')
                                                    <a href="{{ route('esbtp.annees-universitaires.show', $anneeUniversitaire) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @endcan
                                                    @can('annees.edit')
                                                    <a href="{{ route('esbtp.annees-universitaires.edit', $anneeUniversitaire) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endcan
                                                    @can('annees.set_current')
                                                    @if(!optional($anneeUniversitaire)->is_current)
                                                        <form action="{{ route('esbtp.annees-universitaires.set-current', $anneeUniversitaire) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-warning btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Définir comme année en cours">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @endcan
                                                    @can('annees.delete')
                                                    <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $anneeUniversitaire->id }}" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    @endcan
                                                </div>

                                                <!-- Modal de confirmation de suppression -->
                                                <div class="modal fade" id="deleteModal{{ $anneeUniversitaire->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $anneeUniversitaire->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel{{ $anneeUniversitaire->id }}">Confirmation de suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Êtes-vous sûr de vouloir supprimer l'année universitaire <strong>{{ $anneeUniversitaire->name }}</strong> ?
                                                                <br><br>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i> Cette action est irréversible.
                                                                    <ul class="mt-2">
                                                                        <li>Si des étudiants sont inscrits pour cette année universitaire, vous ne pourrez pas la supprimer.</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <form action="{{ route('esbtp.annees-universitaires.destroy', $anneeUniversitaire) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
