@extends('layouts.app')

@section('title', 'Gestion des Partenariats')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Partenariats</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.partnerships.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Partenariat
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                            <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                    <th>Organisation</th>
                                            <th>Type</th>
                                    <th>Date de début</th>
                                    <th>Date de fin</th>
                                    <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                @foreach($partnerships as $partnership)
                                    <tr>
                                                <td>{{ $partnership->name }}</td>
                                        <td>{{ $partnership->organization }}</td>
                                        <td>
                                            @switch($partnership->type)
                                                @case('academic')
                                                    <span class="badge bg-primary">Académique</span>
                                                    @break
                                                @case('industry')
                                                    <span class="badge bg-success">Industrie</span>
                                                    @break
                                                @case('research')
                                                    <span class="badge bg-info">Recherche</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">Autre</span>
                                            @endswitch
                                                </td>
                                        <td>{{ $partnership->start_date->format('d/m/Y') }}</td>
                                        <td>{{ $partnership->end_date ? $partnership->end_date->format('d/m/Y') : 'N/A' }}</td>
                                        <td>
                                            @switch($partnership->status)
                                                @case('active')
                                                    <span class="badge bg-success">Actif</span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge bg-warning">En attente</span>
                                                    @break
                                                @case('expired')
                                                    <span class="badge bg-danger">Expiré</span>
                                                    @break
                                            @endswitch
                                                </td>
                                                <td>
                                            <a href="{{ route('esbtp.partnerships.show', $partnership) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                                        </a>
                                            <a href="{{ route('esbtp.partnerships.edit', $partnership) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                                        </a>
                                            <form action="{{ route('esbtp.partnerships.destroy', $partnership) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce partenariat ?')">
                                                    <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
    </div>
</div>
@endsection
