@extends('layouts.app')

@section('title', 'Détails - ' . $fraisCategory->name . ' - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            @if($fraisCategory->icon)
                <i class="{{ $fraisCategory->icon }} me-2 text-{{ $fraisCategory->color }}"></i>
            @endif
            {{ $fraisCategory->name }}
        </h1>
        <div>
            <a href="{{ route('esbtp.frais.edit', $fraisCategory->id) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit me-1"></i>Modifier
            </a>
            <a href="{{ route('esbtp.frais.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Informations générales -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations Générales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Code</label>
                                <div><code class="fs-6">{{ $fraisCategory->code }}</code></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Type</label>
                                <div>
                                    @if($fraisCategory->is_mandatory)
                                        <span class="badge bg-danger fs-6">Obligatoire</span>
                                    @else
                                        <span class="badge bg-info fs-6">Optionnel</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Statut</label>
                                <div>
                                    @if($fraisCategory->is_active)
                                        <span class="badge bg-success fs-6">Active</span>
                                    @else
                                        <span class="badge bg-secondary fs-6">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Montant par défaut</label>
                                <div class="fs-5 fw-bold text-primary">{{ number_format($fraisCategory->default_amount, 0, ',', ' ') }} FCFA</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Délai de paiement</label>
                                <div class="fs-6">{{ $fraisCategory->payment_deadline_days }} jours</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Date de création</label>
                                <div class="fs-6">{{ $fraisCategory->created_at->format('d/m/Y à H:i') }}</div>
                            </div>
                        </div>
                    </div>
                    @if($fraisCategory->description)
                        <div class="mt-3">
                            <label class="form-label text-muted">Description</label>
                            <div class="border-start border-3 border-primary ps-3">
                                {{ $fraisCategory->description }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Statistiques -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Statistiques
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <div class="fs-4 fw-bold text-primary">{{ $stats['total_rules'] }}</div>
                                <small class="text-muted">Règles</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="fs-4 fw-bold text-success">{{ $stats['active_rules'] }}</div>
                            <small class="text-muted">Actives</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <div class="fs-4 fw-bold text-info">{{ $stats['total_paiements'] }}</div>
                                <small class="text-muted">Paiements</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="fs-5 fw-bold text-success">{{ number_format($stats['total_amount'], 0, ',', ' ') }}</div>
                            <small class="text-muted">FCFA reçus</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Actions Rapides</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-warning" onclick="showOverdueStudents({{ $fraisCategory->id }}, '{{ $fraisCategory->name }}')">
                            <i class="fas fa-exclamation-triangle me-1"></i>Voir les retards
                        </button>
                        <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-outline-primary">
                            <i class="fas fa-cogs me-1"></i>Configuration
                        </a>
                        @if($fraisCategory->variants()->exists())
                            <button class="btn btn-outline-info" onclick="showCategoryVariants({{ $fraisCategory->id }}, '{{ $fraisCategory->name }}')">
                                <i class="fas fa-list me-1"></i>Voir les variants
                            </button>
                        @else
                            <button class="btn btn-outline-success" onclick="addVariant({{ $fraisCategory->id }}, '{{ $fraisCategory->name }}')">
                                <i class="fas fa-plus me-1"></i>Ajouter variants
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Variants -->
    @if($fraisCategory->variants()->exists())
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Variants ({{ $fraisCategory->variants()->count() }})
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Montant</th>
                                <th>Défaut</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fraisCategory->variants()->ordered()->get() as $variant)
                                <tr>
                                    <td><strong>{{ $variant->name }}</strong></td>
                                    <td>{{ $variant->description ?: '-' }}</td>
                                    <td><strong>{{ number_format($variant->amount, 0, ',', ' ') }} FCFA</strong></td>
                                    <td>
                                        @if($variant->is_default)
                                            <span class="badge bg-success">Défaut</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $variant->is_active ? 'success' : 'secondary' }}">
                                            {{ $variant->is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-primary" onclick="editVariant({{ $variant->id }})" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteVariant({{ $variant->id }})" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-primary" onclick="addVariant({{ $fraisCategory->id }}, '{{ $fraisCategory->name }}')">
                        <i class="fas fa-plus me-1"></i>Ajouter un variant
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Règles de configuration par classe -->
    @if($fraisCategory->rules()->exists())
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>
                    Configuration par Classe ({{ $fraisCategory->rules()->count() }})
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Montant</th>
                                <th>Délai</th>
                                <th>Statut</th>
                                <th>Date effective</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fraisCategory->rules()->with(['filiere', 'niveau'])->get() as $rule)
                                <tr>
                                    <td>{{ $rule->filiere->name ?? 'N/A' }}</td>
                                    <td>{{ $rule->niveau->name ?? 'N/A' }}</td>
                                    <td><strong>{{ number_format($rule->amount, 0, ',', ' ') }} FCFA</strong></td>
                                    <td>{{ $rule->payment_deadline_days }} jours</td>
                                    <td>
                                        <span class="badge bg-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $rule->effective_date ? $rule->effective_date->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Configuration manquante</strong> - Cette catégorie n'est pas encore configurée pour des classes spécifiques.
            <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-sm btn-primary ms-2">
                <i class="fas fa-cogs me-1"></i>Configurer maintenant
            </a>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// Basic functions for the show page modals
function showOverdueStudents(categoryId, categoryName) {
    alert('Fonctionnalité disponible depuis la page principale des frais');
}

function showCategoryVariants(categoryId, categoryName) {
    alert('Fonctionnalité disponible depuis la page principale des frais');
}

function addVariant(categoryId, categoryName) {
    alert('Fonctionnalité disponible depuis la page principale des frais');
}

function editVariant(variantId) {
    alert('Fonctionnalité d\'édition de variants en cours de développement');
}

function deleteVariant(variantId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce variant ?')) {
        fetch(`/esbtp/frais/variants/${variantId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression du variant');
            }
        })
        .catch(error => {
            alert('Erreur lors de la suppression du variant');
        });
    }
}
</script>
@endpush