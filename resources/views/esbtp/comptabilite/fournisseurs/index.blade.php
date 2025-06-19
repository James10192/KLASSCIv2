@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('content')
<div class="container-fluid">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="fas fa-truck fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Fournisseurs</h1>
                <div class="text-white-50">Gestion des partenaires et prestataires</div>
            </div>
        </div>
        <a href="{{ route('esbtp.comptabilite.fournisseurs.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
            <i class="fas fa-plus"></i> Nouveau fournisseur
        </a>
    </div>

    <div class="container-fluid animate-fade-in-up">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass mb-4">
                    <div class="card-body p-0">
                        @if($fournisseurs->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle premium-table mb-0">
                                    <thead class="sticky-top bg-gradient-primary text-white rounded-top-4">
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
                                                        <span class="badge bg-success px-3 py-2">Actif</span>
                                                    @else
                                                        <span class="badge bg-secondary px-3 py-2">Inactif</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('esbtp.comptabilite.fournisseurs.show', $fournisseur->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.comptabilite.fournisseurs.edit', $fournisseur->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Éditer">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('esbtp.comptabilite.fournisseurs.destroy', $fournisseur->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Supprimer ce fournisseur ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Supprimer">
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
                            <div class="alert alert-info glass-alert m-4 d-flex align-items-center">
                                <i class="fas fa-info-circle fa-2x me-3 text-primary"></i>
                                <div>Aucun fournisseur enregistré pour le moment.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
