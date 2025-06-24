@extends('layouts.app')

@section('title', 'Gestion des Frais')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Frais</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.fees.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveaux Frais
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
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Catégorie</th>
                                    <th>Année Académique</th>
                                    <th>Montant</th>
                                    <th>Échéance</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fees as $fee)
                                    <tr>
                                        <td>
                                            @if($fee->inscription && $fee->inscription->etudiant)
                                                {{ $fee->inscription->etudiant->nom }} {{ $fee->inscription->etudiant->prenom }}
                                                <br>
                                                <small class="text-muted">{{ $fee->inscription->etudiant->matricule }}</small>
                                            @else
                                                <span class="text-muted">Non assigné</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fee->class)
                                                {{ $fee->class->name }}
                                                @if($fee->class->filiere)
                                                    <br><small class="text-muted">{{ $fee->class->filiere->name }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Non définie</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fee->category)
                                                {{ $fee->category->name }}
                                            @else
                                                <span class="text-muted">Non définie</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fee->academicYear)
                                                {{ $fee->academicYear->name }}
                                            @else
                                                <span class="text-muted">Non définie</span>
                                            @endif
                                        </td>
                                        <td>{{ $fee->formatted_amount }}</td>
                                        <td>
                                            @if($fee->due_date)
                                                {{ $fee->due_date->format('d/m/Y') }}
                                                @if($fee->due_date->isPast() && $fee->status === 'pending')
                                                    <br><small class="text-danger">En retard</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Non définie</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $fee->status_color }}">
                                                {{ $fee->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('esbtp.fees.show', $fee) }}" class="btn btn-sm btn-info" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.fees.edit', $fee) }}" class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('esbtp.fees.destroy', $fee) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ces frais ?')"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Aucun frais de scolarité enregistré</p>
                                                <a href="{{ route('esbtp.fees.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Créer les premiers frais
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
