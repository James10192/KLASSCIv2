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

    <!-- Vue d'ensemble matricielle -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        Vue Matricielle des Frais par Classe
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Filière / Niveau</th>
                                    @php
                                        $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->get();
                                    @endphp
                                    @foreach($niveaux as $niveau)
                                        <th class="text-center">{{ $niveau->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->get();
                                @endphp
                                @foreach($filieres as $filiere)
                                    <tr>
                                        <td class="fw-bold">{{ $filiere->name }}</td>
                                        @foreach($niveaux as $niveau)
                                            @php
                                                $fraisConfigures = \App\Models\ESBTPFraisRule::where('filiere_id', $filiere->id)
                                                    ->where('niveau_id', $niveau->id)
                                                    ->count();
                                                $obligatoires = \App\Models\ESBTPFraisRule::where('filiere_id', $filiere->id)
                                                    ->where('niveau_id', $niveau->id)
                                                    ->whereHas('fraisCategory', function($q) { $q->where('is_mandatory', true); })
                                                    ->count();
                                                $totalObligatoires = $categories->where('is_mandatory', true)->count();
                                            @endphp
                                            <td class="text-center">
                                                @if($fraisConfigures > 0)
                                                    <div class="mb-1">
                                                        <span class="badge bg-primary">{{ $fraisConfigures }} frais</span>
                                                    </div>
                                                    @if($obligatoires == $totalObligatoires)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle"></i> Complet
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-triangle"></i> Partiel
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times-circle"></i> Non configuré
                                                    </span>
                                                @endif
                                                <div class="mt-1">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="showClassDetails({{ $filiere->id }}, {{ $niveau->id }}, '{{ $filiere->name }}', '{{ $niveau->name }}')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Légende et actions rapides -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Légende et Actions
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Statuts de configuration :</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <span class="badge bg-success me-2">Complet</span>
                            Tous les frais obligatoires configurés
                        </li>
                        <li class="mb-2">
                            <span class="badge bg-warning me-2">Partiel</span>
                            Frais obligatoires partiellement configurés
                        </li>
                        <li class="mb-2">
                            <span class="badge bg-secondary me-2">Non configuré</span>
                            Aucun frais configuré
                        </li>
                    </ul>
                    
                    <hr>
                    
                    <h6>Actions rapides :</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-primary">
                            <i class="fas fa-cogs me-1"></i>Configuration par Classe
                        </a>
                        <button class="btn btn-info" onclick="showVariantsModal()">
                            <i class="fas fa-list me-1"></i>Voir les Variants
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des catégories -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Catégories de Frais et Variants</h5>
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
                                <th>Variants</th>
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
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($category->variants()->exists())
                                                <span class="badge bg-success">
                                                    {{ $category->variants()->count() }} variant(s)
                                                </span>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="showCategoryVariants({{ $category->id }}, '{{ $category->name }}')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @else
                                                <span class="badge bg-secondary">Aucun variant</span>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="addVariant({{ $category->id }}, '{{ $category->name }}')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            @endif
                                        </div>
                                        <div class="mt-1">
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="showOverdueStudents({{ $category->id }}, '{{ $category->name }}')"
                                                    title="Voir les étudiants en retard">
                                                <i class="fas fa-exclamation-triangle"></i> Retards
                                            </button>
                                        </div>
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
                                            <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn btn-sm btn-info" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn btn-sm btn-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('esbtp.frais.toggle-active', $category->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-{{ $category->is_active ? 'warning' : 'success' }}" title="{{ $category->is_active ? 'Désactiver' : 'Activer' }}">
                                                    <i class="fas fa-{{ $category->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            @if(!$category->is_mandatory)
                                                <form action="{{ route('esbtp.frais.destroy', $category->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
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

<!-- Modal pour les détails d'une classe -->
<div class="modal fade" id="classDetailsModal" tabindex="-1" aria-labelledby="classDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="classDetailsModalLabel">Détails des Frais - <span id="classTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="classDetailsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les variants d'une catégorie -->
<div class="modal fade" id="categoryVariantsModal" tabindex="-1" aria-labelledby="categoryVariantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryVariantsModalLabel">Variants - <span id="categoryTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="categoryVariantsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un variant -->
<div class="modal fade" id="addVariantModal" tabindex="-1" aria-labelledby="addVariantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVariantModalLabel">Ajouter un Variant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="addVariantForm">
                <div class="modal-body">
                    <input type="hidden" id="variantCategoryId" name="category_id">
                    <div class="mb-3">
                        <label for="variantName" class="form-label">Nom du variant</label>
                        <input type="text" class="form-control" id="variantName" name="name" required>
                        <div class="form-text">Ex: "Arrêt Centre-ville", "Menu Standard"</div>
                    </div>
                    <div class="mb-3">
                        <label for="variantDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="variantDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="variantAmount" class="form-label">Montant (FCFA)</label>
                        <input type="number" class="form-control" id="variantAmount" name="amount" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="variantIsDefault" name="is_default">
                            <label class="form-check-label" for="variantIsDefault">
                                Variant par défaut
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour tous les variants -->
<div class="modal fade" id="allVariantsModal" tabindex="-1" aria-labelledby="allVariantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="allVariantsModalLabel">Tous les Variants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="allVariantsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les étudiants en retard -->
<div class="modal fade" id="overdueStudentsModal" tabindex="-1" aria-labelledby="overdueStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="overdueStudentsModalLabel">Étudiants en Retard - <span id="overdueCategory"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="overdueStudentsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal pour planifier les relances -->
<div class="modal fade" id="scheduleRemindersModal" tabindex="-1" aria-labelledby="scheduleRemindersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleRemindersModalLabel">Planifier des Relances</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="scheduleRemindersForm">
                <div class="modal-body">
                    <input type="hidden" id="reminderCategoryId" name="category_id">
                    <div class="mb-3">
                        <label for="reminderLevel" class="form-label">Niveau de relance</label>
                        <select class="form-select" id="reminderLevel" name="niveau" required>
                            <option value="1">1er rappel (doux)</option>
                            <option value="2">2ème rappel (ferme)</option>
                            <option value="3">Dernière relance (urgent)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reminderType" class="form-label">Type de relance</label>
                        <select class="form-select" id="reminderType" name="type" required>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="courrier">Courrier</option>
                            <option value="appel">Appel téléphonique</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reminderDelay" class="form-label">Délai d'envoi (jours)</label>
                        <input type="number" class="form-control" id="reminderDelay" name="delai_jours" min="1" max="90" value="3" required>
                        <div class="form-text">Nombre de jours avant l'envoi de la relance</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Planifier les relances</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Fonction pour afficher les détails d'une classe
function showClassDetails(filiereId, niveauId, filiereName, niveauName) {
    document.getElementById('classTitle').textContent = filiereName + ' - ' + niveauName;
    const modal = new bootstrap.Modal(document.getElementById('classDetailsModal'));
    
    const content = document.getElementById('classDetailsContent');
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Charger les détails via AJAX
    fetch(`/esbtp/frais/class-details/${filiereId}/${niveauId}`)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="row">';
            
            if (data.categories && data.categories.length > 0) {
                data.categories.forEach(category => {
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="card border-${category.is_mandatory ? 'danger' : 'info'}">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="${category.icon || 'fas fa-money-bill'} me-2"></i>
                                        ${category.name}
                                        <span class="badge bg-${category.is_mandatory ? 'danger' : 'info'} ms-2">
                                            ${category.is_mandatory ? 'Obligatoire' : 'Optionnel'}
                                        </span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>Montant:</strong> ${category.amount ? category.amount.toLocaleString() + ' FCFA' : 'Non configuré'}</p>
                                    ${category.variants && category.variants.length > 0 ? `
                                        <p class="mb-2"><strong>Variants:</strong></p>
                                        <div class="ms-3">
                                            ${category.variants.map(variant => `
                                                <div class="d-flex justify-content-between">
                                                    <span>${variant.name}</span>
                                                    <span class="text-muted">${variant.amount.toLocaleString()} FCFA</span>
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : ''}
                                    ${category.description ? `<small class="text-muted">${category.description}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<div class="col-12"><div class="alert alert-info">Aucun frais configuré pour cette classe.</div></div>';
            }
            
            html += '</div>';
            html += `
                <div class="text-center mt-3">
                    <a href="/esbtp/frais/configure?filiere_id=${filiereId}&niveau_id=${niveauId}" class="btn btn-primary">
                        <i class="fas fa-cogs me-1"></i>Configurer les frais
                    </a>
                </div>
            `;
            
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails.</div>';
        });
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
    fetch(`/esbtp/frais/category-variants/${categoryId}`)
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
                                <button class="btn btn-sm btn-primary" onclick="editVariant(${variant.id})">
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
    
    const modal = new bootstrap.Modal(document.getElementById('addVariantModal'));
    modal.show();
}

// Fonction pour afficher tous les variants
function showVariantsModal() {
    const modal = new bootstrap.Modal(document.getElementById('allVariantsModal'));
    
    const content = document.getElementById('allVariantsContent');
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Charger tous les variants via AJAX
    fetch('/esbtp/frais/all-variants')
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            if (data.categories && data.categories.length > 0) {
                data.categories.forEach(category => {
                    html += `
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="${category.icon || 'fas fa-money-bill'} me-2"></i>
                                    ${category.name}
                                    <span class="badge bg-${category.is_mandatory ? 'danger' : 'info'} ms-2">
                                        ${category.is_mandatory ? 'Obligatoire' : 'Optionnel'}
                                    </span>
                                </h6>
                            </div>
                            <div class="card-body">
                    `;
                    
                    if (category.variants && category.variants.length > 0) {
                        html += `
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Variant</th>
                                            <th>Montant</th>
                                            <th>Défaut</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        category.variants.forEach(variant => {
                            html += `
                                <tr>
                                    <td>
                                        <strong>${variant.name}</strong>
                                        ${variant.description ? `<br><small class="text-muted">${variant.description}</small>` : ''}
                                    </td>
                                    <td>${variant.amount.toLocaleString()} FCFA</td>
                                    <td>
                                        ${variant.is_default ? '<span class="badge bg-success">Défaut</span>' : '-'}
                                    </td>
                                    <td>
                                        <span class="badge bg-${variant.is_active ? 'success' : 'secondary'}">
                                            ${variant.is_active ? 'Actif' : 'Inactif'}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += '</tbody></table></div>';
                    } else {
                        html += '<p class="text-muted">Aucun variant configuré</p>';
                    }
                    
                    html += `
                                <div class="text-end">
                                    <button class="btn btn-sm btn-primary" onclick="addVariant(${category.id}, '${category.name}')">
                                        <i class="fas fa-plus me-1"></i>Ajouter variant
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                html = '<div class="alert alert-info">Aucune catégorie trouvée.</div>';
            }
            
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des variants.</div>';
        });
}

// Gestionnaire de soumission du formulaire d'ajout de variant
document.getElementById('addVariantForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/esbtp/frais/variants', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addVariantModal')).hide();
            // Recharger la page ou mettre à jour l'affichage
            location.reload();
        } else {
            alert('Erreur lors de l\'ajout du variant');
        }
    })
    .catch(error => {
        alert('Erreur lors de l\'ajout du variant');
    });
});

// Fonction pour éditer un variant
function editVariant(variantId) {
    // Implémenter l'édition de variant
    console.log('Edit variant:', variantId);
}

// Fonction pour supprimer un variant
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

// Fonction pour afficher les étudiants en retard
function showOverdueStudents(categoryId, categoryName) {
    document.getElementById('overdueCategory').textContent = categoryName;
    const modal = new bootstrap.Modal(document.getElementById('overdueStudentsModal'));
    
    const content = document.getElementById('overdueStudentsContent');
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-warning" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Charger les étudiants en retard via AJAX
    fetch(`/esbtp/frais/${categoryId}/overdue-students`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            if (data.students && data.students.length > 0) {
                html += `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>${data.count} étudiant(s) en retard</strong> pour les frais de ${data.category.name}
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Filière - Niveau</th>
                                    <th>Montant dû</th>
                                    <th>Jours de retard</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.students.forEach(student => {
                    html += `
                        <tr>
                            <td>
                                <strong>${student.prenom} ${student.nom}</strong>
                                <br><small class="text-muted">${student.email || 'Email non renseigné'}</small>
                            </td>
                            <td>
                                <span class="badge bg-info">${student.filiere || 'N/A'} - ${student.niveau || 'N/A'}</span>
                            </td>
                            <td>
                                <strong class="text-danger">${student.amount ? student.amount.toLocaleString() : 'N/A'} FCFA</strong>
                            </td>
                            <td>
                                <span class="badge bg-danger">${student.jours_retard} jours</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="openReminderForm(${categoryId}, '${categoryName}')">
                                    <i class="fas fa-bell"></i> Relancer
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
                html += `
                    <div class="text-center mt-3">
                        <button class="btn btn-warning" onclick="openReminderForm(${categoryId}, '${categoryName}')">
                            <i class="fas fa-bell me-1"></i>Planifier des relances pour tous
                        </button>
                    </div>
                `;
            } else {
                html = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Aucun étudiant en retard</strong> pour les frais de ${data.category.name}
                    </div>
                `;
            }
            
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des étudiants en retard.</div>';
        });
}

// Fonction pour ouvrir le formulaire de planification de relances
function openReminderForm(categoryId, categoryName) {
    document.getElementById('reminderCategoryId').value = categoryId;
    
    const modal = new bootstrap.Modal(document.getElementById('scheduleRemindersModal'));
    modal.show();
    
    // Fermer le modal des étudiants en retard s'il est ouvert
    const overdueModal = bootstrap.Modal.getInstance(document.getElementById('overdueStudentsModal'));
    if (overdueModal) {
        overdueModal.hide();
    }
}

// Gestionnaire de soumission du formulaire de planification de relances
document.getElementById('scheduleRemindersForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const categoryId = document.getElementById('reminderCategoryId').value;
    
    fetch(`/esbtp/frais/${categoryId}/schedule-reminders`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('scheduleRemindersModal')).hide();
            alert(data.message);
            // Recharger l'affichage des étudiants en retard
            location.reload();
        } else {
            alert(data.message || 'Erreur lors de la planification des relances');
        }
    })
    .catch(error => {
        alert('Erreur lors de la planification des relances');
    });
});
</script>
@endpush