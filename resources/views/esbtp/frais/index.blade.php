@extends('layouts.app')

@section('title', 'Gestion des Frais - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Gestion des Frais</h1>
                <p class="header-subtitle">Configuration des frais scolaires par catégorie et type</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-cogs"></i>Configuration par Classe
                </a>
                <a href="{{ route('esbtp.frais.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouvelle Catégorie
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
        @include('esbtp.frais.partials.statistics-cards', ['stats' => $stats])

        <!-- Interface à onglets par type de frais -->
        <div class="card-moderne">
            <div class="p-lg">
                <!-- Navigation par onglets -->
                <ul class="nav nav-tabs nav-justified mb-4" id="fraisTypeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button" role="tab">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Frais Académiques
                            <span class="badge bg-success ms-2">{{ $categoriesByType['academic']->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab">
                            <i class="fas fa-cogs me-2"></i>
                            Services Optionnels
                            <span class="badge bg-warning ms-2">{{ $categoriesByType['service']->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="administrative-tab" data-bs-toggle="tab" data-bs-target="#administrative" type="button" role="tab">
                            <i class="fas fa-file-alt me-2"></i>
                            Frais Administratifs
                            <span class="badge bg-info ms-2">{{ $categoriesByType['administrative']->count() }}</span>
                        </button>
                    </li>
                </ul>

                <!-- Contenu des onglets -->
                <div class="tab-content" id="fraisTypeTabsContent">
                    <!-- Onglet Frais Académiques -->
                    <div class="tab-pane fade show active" id="academic" role="tabpanel" aria-labelledby="academic-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">
                                    <i class="fas fa-graduation-cap text-success me-2"></i>
                                    Frais Académiques (Inscription & Scolarité)
                                </h5>
                                <p class="text-muted mb-0">Ces frais dépendent de la filière et du niveau d'étude</p>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" onclick="addCategoryForType('academic')">
                                <i class="fas fa-plus me-1"></i>Ajouter Frais Académique
                            </button>
                        </div>
                        
                        @if($categoriesByType['academic']->count() > 0)
                            <div class="row">
                                @foreach($categoriesByType['academic'] as $category)
                                    @include('esbtp.frais.partials.category-card', ['category' => $category])
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-graduation-cap fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun frais académique configuré</h5>
                                <p class="text-muted">Les frais d'inscription et de scolarité apparaîtront ici.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Onglet Services Optionnels -->
                    <div class="tab-pane fade" id="service" role="tabpanel" aria-labelledby="service-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">
                                    <i class="fas fa-cogs text-warning me-2"></i>
                                    Services Optionnels (Cantine & Transport)
                                </h5>
                                <p class="text-muted mb-0">Ces services ont des variants (menus, arrêts) selon les besoins</p>
                            </div>
                            <button class="btn btn-outline-warning btn-sm" onclick="addCategoryForType('service')">
                                <i class="fas fa-plus me-1"></i>Ajouter Service
                            </button>
                        </div>
                        
                        @if($categoriesByType['service']->count() > 0)
                            <div class="row">
                                @foreach($categoriesByType['service'] as $category)
                                    @include('esbtp.frais.partials.category-card', ['category' => $category])
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-cogs fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun service optionnel configuré</h5>
                                <p class="text-muted">Les services de cantine et transport apparaîtront ici.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Onglet Frais Administratifs -->
                    <div class="tab-pane fade" id="administrative" role="tabpanel" aria-labelledby="administrative-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">
                                    <i class="fas fa-file-alt text-info me-2"></i>
                                    Frais Administratifs (Documents & Examens)
                                </h5>
                                <p class="text-muted mb-0">Ces frais dépendent du contexte spécifique (type d'examen, documents)</p>
                            </div>
                            <button class="btn btn-outline-info btn-sm" onclick="addCategoryForType('administrative')">
                                <i class="fas fa-plus me-1"></i>Ajouter Frais Administratif
                            </button>
                        </div>
                        
                        @if($categoriesByType['administrative']->count() > 0)
                            <div class="row">
                                @foreach($categoriesByType['administrative'] as $category)
                                    @include('esbtp.frais.partials.category-card', ['category' => $category])
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun frais administratif configuré</h5>
                                <p class="text-muted">Les frais de documentation et d'examen apparaîtront ici.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Résumé Global
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border-end">
                                    <h4 class="text-primary">{{ $categoriesByType['academic']->count() + $categoriesByType['service']->count() + $categoriesByType['administrative']->count() }}</h4>
                                    <p class="text-muted mb-0">Total Catégories</p>
                                    <small class="text-muted">Toutes confondues</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border-end">
                                    <h4 class="text-success">{{ $categoriesByType['academic']->where('is_mandatory', true)->count() + $categoriesByType['service']->where('is_mandatory', true)->count() + $categoriesByType['administrative']->where('is_mandatory', true)->count() }}</h4>
                                    <p class="text-muted mb-0">Frais Obligatoires</p>
                                    <small class="text-muted">À payer par tous</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h4 class="text-warning">{{ $categoriesByType['service']->where('is_mandatory', false)->count() }}</h4>
                                <p class="text-muted mb-0">Services Optionnels</p>
                                <small class="text-muted">Cantine & Transport</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Actions Rapides
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-primary">
                                <i class="fas fa-cogs me-1"></i>Configuration par Classe
                            </a>
                            <a href="{{ route('esbtp.frais.create') }}" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Nouvelle Catégorie
                            </a>
                            <a href="{{ route('esbtp.paiements.index') }}" class="btn btn-info">
                                <i class="fas fa-credit-card me-1"></i>Suivi des Paiements
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclure les modaux existants (réutilisés) -->
@include('esbtp.frais.partials.modals')

@endsection

@push('scripts')
<script>
// Fonction pour ajouter une catégorie avec un type pré-sélectionné
function addCategoryForType(categoryType) {
    window.location.href = `{{ route('esbtp.frais.create') }}?category_type=${categoryType}`;
}

// Fonction pour afficher les variants d'une catégorie
function showCategoryVariants(categoryId, categoryName) {
    document.getElementById('categoryTitle').textContent = categoryName;
    const modal = new bootstrap.Modal(document.getElementById('categoryVariantsModal'));
    
    const content = document.getElementById('categoryVariantsContent');
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Charger les variants via AJAX
    fetch(`{{ url('esbtp/frais/category-variants') }}/${categoryId}`)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="table-responsive">';
            
            if (data.variants && data.variants.length > 0) {
                html += `
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
                `;
                
                data.variants.forEach(variant => {
                    html += `
                        <tr>
                            <td><strong>${variant.name}</strong></td>
                            <td>${variant.description || '-'}</td>
                            <td>${variant.amount.toLocaleString()} FCFA</td>
                            <td>
                                ${variant.is_default ? '<span class="badge bg-success">Défaut</span>' : '-'}
                            </td>
                            <td>
                                <span class="badge bg-${variant.is_active ? 'success' : 'secondary'}">
                                    ${variant.is_active ? 'Actif' : 'Inactif'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary me-1" onclick="editVariant(${variant.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteVariant(${variant.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table>';
            } else {
                html += '<div class="alert alert-info">Aucun variant trouvé pour cette catégorie.</div>';
            }
            
            html += '</div>';
            html += `
                <div class="text-center mt-3">
                    <button class="btn btn-primary" onclick="addVariant(${categoryId}, '${categoryName}')">
                        <i class="fas fa-plus me-1"></i>Ajouter un variant
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des variants.</div>';
        });
}

// Fonction pour ajouter un variant
function addVariant(categoryId, categoryName) {
    document.getElementById('variantCategoryId').value = categoryId;
    document.getElementById('addVariantForm').reset();
    
    // Mettre à jour le titre du modal avec le nom de la catégorie
    document.getElementById('addVariantModalLabel').textContent = `Ajouter un Variant - ${categoryName}`;
    
    const modal = new bootstrap.Modal(document.getElementById('addVariantModal'));
    modal.show();
}


// Fonction pour supprimer un variant
function deleteVariant(variantId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce variant ?')) {
        fetch(`{{ url('esbtp/frais/variants') }}/${variantId}`, {
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

// Fonction pour éditer un variant (placeholder)
function editVariant(variantId) {
    alert('Fonctionnalité d\'édition à implémenter pour le variant ' + variantId);
}

// Gestion des onglets et événements au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Activer Bootstrap tabs
    var triggerTabList = [].slice.call(document.querySelectorAll('#fraisTypeTabs button'));
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });

    // Gestionnaire de soumission du formulaire d'ajout de variant
    const addVariantForm = document.getElementById('addVariantForm');
    if (addVariantForm) {
        addVariantForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // S'assurer que is_default est correctement géré
            const isDefaultCheckbox = document.getElementById('variantIsDefault');
            if (isDefaultCheckbox.checked) {
                formData.set('is_default', '1');
            } else {
                formData.set('is_default', '0');
            }
            
            fetch('{{ route("esbtp.frais.variants.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addVariantModal')).hide();
                    
                    // Afficher un message de succès
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>Variant ajouté avec succès !
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
                    
                    // Recharger la page après un délai
                    setTimeout(() => location.reload(), 1500);
                } else {
                    console.error('Erreur API:', data);
                    alert(`Erreur lors de l'ajout du variant: ${data.message || data.error || 'Erreur inconnue'}`);
                }
            })
            .catch(error => {
                console.error('Erreur fetch:', error);
                alert(`Erreur lors de l'ajout du variant: ${error.message}`);
            });
        });
    }
});
</script>
@endpush