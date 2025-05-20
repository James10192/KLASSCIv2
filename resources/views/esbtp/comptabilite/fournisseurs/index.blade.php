@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="fw-bold mb-0"><i class="fas fa-truck me-2 text-primary"></i> Fournisseurs</h2>
            <a href="{{ route('esbtp.comptabilite.fournisseurs.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nouveau fournisseur
            </a>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if($fournisseurs->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fournisseurs as $fournisseur)
                                <tr>
                                    <td>{{ $loop->iteration + ($fournisseurs->currentPage() - 1) * $fournisseurs->perPage() }}</td>
                                    <td class="fw-semibold">{{ $fournisseur->nom }}</td>
                                    <td>{{ $fournisseur->contact ?? '-' }}</td>
                                    <td>{{ $fournisseur->email ?? '-' }}</td>
                                    <td>
                                        @if($fournisseur->statut === 'actif')
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('esbtp.comptabilite.fournisseurs.show', $fournisseur->id) }}" class="btn btn-sm btn-outline-info me-1" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.comptabilite.fournisseurs.edit', $fournisseur->id) }}" class="btn btn-sm btn-outline-warning me-1" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('esbtp.comptabilite.fournisseurs.destroy', $fournisseur->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Supprimer ce fournisseur ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 px-3">
                    {{ $fournisseurs->links() }}
                </div>
            @else
                <div class="alert alert-info m-4">
                    <i class="fas fa-info-circle me-2"></i> Aucun fournisseur enregistré pour le moment.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
