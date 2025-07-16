@extends('layouts.app')

@section('title', 'Gestion des Frais - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gestion des Frais</h1>
        <div>
            <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-info me-2">
                <i class="fas fa-cogs me-1"></i>Configuration
            </a>
            <a href="{{ route('esbtp.frais.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Nouvelle Catégorie
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-list-ul fa-2x text-primary mb-2"></i>
                    <h3 class="text-primary">{{ $stats['total_categories'] }}</h3>
                    <p class="text-muted mb-0">Total Catégories</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                    <h3 class="text-danger">{{ $stats['mandatory_categories'] }}</h3>
                    <p class="text-muted mb-0">Obligatoires</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                    <h3 class="text-warning">{{ $stats['optional_categories'] }}</h3>
                    <p class="text-muted mb-0">Optionnelles</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3 class="text-success">{{ $stats['active_categories'] }}</h3>
                    <p class="text-muted mb-0">Actives</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des catégories -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Catégories de Frais</h5>
        </div>
        <div class="card-body">
            @if($categories->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Montant par défaut</th>
                                <th>Délai (jours)</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($category->icon)
                                                <i class="{{ $category->icon }} me-2 text-{{ $category->color }}"></i>
                                            @endif
                                            <div>
                                                <strong>{{ $category->name }}</strong>
                                                @if($category->description)
                                                    <br><small class="text-muted">{{ $category->description }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ $category->code }}</code>
                                    </td>
                                    <td>
                                        @if($category->is_mandatory)
                                            <span class="badge bg-danger">Obligatoire</span>
                                        @else
                                            <span class="badge bg-info">Optionnel</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($category->default_amount, 0, ',', ' ') }} FCFA</strong>
                                    </td>
                                    <td>
                                        {{ $category->payment_deadline_days }} jours
                                    </td>
                                    <td>
                                        @if($category->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('esbtp.frais.show', $category) }}" class="btn btn-sm btn-info" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.frais.edit', $category) }}" class="btn btn-sm btn-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('esbtp.frais.toggle-active', $category) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-{{ $category->is_active ? 'warning' : 'success' }}" title="{{ $category->is_active ? 'Désactiver' : 'Activer' }}">
                                                    <i class="fas fa-{{ $category->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            @if(!$category->is_mandatory)
                                                <form action="{{ route('esbtp.frais.destroy', $category) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune catégorie de frais trouvée</h5>
                    <p class="text-muted">Commencez par créer une catégorie de frais.</p>
                    <a href="{{ route('esbtp.frais.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Créer une catégorie
                    </a>
                </div>
            @endif
        </div>
        @if($categories->count() > 0)
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">{{ $categories->count() }} catégorie(s) trouvée(s)</small>
                    <div>
                        <form action="{{ route('esbtp.frais.reset-defaults') }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser les catégories par défaut ? Cette action supprimera toutes les catégories personnalisées.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-undo me-1"></i>Réinitialiser par défaut
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection