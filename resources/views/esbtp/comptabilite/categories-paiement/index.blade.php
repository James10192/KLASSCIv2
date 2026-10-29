@extends('layouts.app')

@section('title', 'Gestion des catégories de paiement')

@section('styles')
<style>
    .category-card {
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        margin-bottom: 20px;
        position: relative;
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    
    .category-header {
        padding: 15px;
        border-radius: 10px 10px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .category-icon {
        font-size: 2rem;
        margin-right: 15px;
    }
    
    .category-content {
        padding: 15px;
        background: #fff;
    }
    
    .category-actions {
        padding: 10px 15px;
        background-color: #f8f9fa;
        border-top: 1px solid #eee;
    }
    
    .inactive-category {
        opacity: 0.6;
    }
    
    .badge-mandatory {
        background-color: #e74c3c;
    }
    
    .badge-optional {
        background-color: #3498db;
    }
    
    .subcategory-list {
        margin-top: 10px;
        padding-left: 20px;
        border-left: 2px solid #eee;
    }
    
    .subcategory-item {
        padding: 8px 0;
        border-bottom: 1px dashed #eee;
    }
    
    .subcategory-item:last-child {
        border-bottom: none;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Gestion des catégories de paiement</h5>
            <a href="{{ route('esbtp.comptabilite.categories-paiement.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle catégorie
            </a>
        </div>
        <div class="card-body">
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
            
            <!-- Catégories principales -->
            <div class="row">
                @forelse($categoriesParentes as $categorie)
                <div class="col-md-4">
                    <div class="category-card {{ !$categorie->est_actif ? 'inactive-category' : '' }}">
                        <div class="category-header" style="background-color: {{ $categorie->couleur }}; color: white;">
                            <div class="d-flex align-items-center">
                                <i class="{{ $categorie->icone }} category-icon"></i>
                                <h5 class="mb-0">{{ $categorie->nom }}</h5>
                            </div>
                            <div>
                                @if($categorie->est_obligatoire)
                                <span class="badge bg-danger">Obligatoire</span>
                                @else
                                <span class="badge bg-info">Optionnel</span>
                                @endif
                                
                                @if(!$categorie->est_actif)
                                <span class="badge bg-dark">Inactif</span>
                                @endif
                            </div>
                        </div>
                        <div class="category-content">
                            <p class="mb-2"><strong>Code:</strong> {{ $categorie->code }}</p>
                            @if($categorie->description)
                            <p class="mb-2">{{ Str::limit($categorie->description, 100) }}</p>
                            @endif
                            
                            @if($categorie->enfants->count() > 0)
                            <div class="subcategory-list">
                                <h6 class="mb-2">Sous-catégories:</h6>
                                @foreach($categorie->enfants as $enfant)
                                <div class="subcategory-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="{{ $enfant->icone }}" style="{{ $enfant->style_icone }}"></i>
                                        <span class="ms-2">{{ $enfant->nom }}</span>
                                    </div>
                                    <div>
                                        @if(!$enfant->est_actif)
                                        <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-muted mb-0 fst-italic">Aucune sous-catégorie</p>
                            @endif
                        </div>
                        <div class="category-actions">
                            <div class="btn-group w-100" role="group">
                                <a href="{{ route('esbtp.comptabilite.categories-paiement.show', $categorie) }}" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye"></i> Détails
                                </a>
                                <a href="{{ route('esbtp.comptabilite.categories-paiement.edit', $categorie) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <form action="{{ route('esbtp.comptabilite.categories-paiement.toggle-status', $categorie) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $categorie->est_actif ? 'warning' : 'success' }}">
                                        <i class="fas fa-{{ $categorie->est_actif ? 'eye-slash' : 'eye' }}"></i>
                                        {{ $categorie->est_actif ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Aucune catégorie de paiement n'est définie. 
                        <a href="{{ route('esbtp.comptabilite.categories-paiement.create') }}" class="alert-link">Créer votre première catégorie</a>.
                    </div>
                </div>
                @endforelse
            </div>
            
            <!-- Tableau des catégories -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Liste complète des catégories</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Code</th>
                                    <th>Parent</th>
                                    <th>Statut</th>
                                    <th>Ordre</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $cat)
                                <tr>
                                    <td>{{ $cat->id }}</td>
                                    <td>
                                        <i class="{{ $cat->icone }}" style="{{ $cat->style_icone }}"></i>
                                        <span class="ms-2">{{ $cat->nom }}</span>
                                    </td>
                                    <td><code>{{ $cat->code }}</code></td>
                                    <td>{{ $cat->parent ? $cat->parent->nom : '-' }}</td>
                                    <td>
                                        @if($cat->est_actif)
                                        <span class="badge bg-success">Actif</span>
                                        @else
                                        <span class="badge bg-danger">Inactif</span>
                                        @endif
                                        
                                        @if($cat->est_obligatoire)
                                        <span class="badge badge-mandatory">Obligatoire</span>
                                        @else
                                        <span class="badge badge-optional">Optionnel</span>
                                        @endif
                                    </td>
                                    <td>{{ $cat->ordre }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('esbtp.comptabilite.categories-paiement.show', $cat) }}" class="btn btn-sm btn-info" title="Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.comptabilite.categories-paiement.edit', $cat) }}" class="btn btn-sm btn-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('esbtp.comptabilite.categories-paiement.toggle-status', $cat) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-{{ $cat->est_actif ? 'warning' : 'success' }}" title="{{ $cat->est_actif ? 'Désactiver' : 'Activer' }}">
                                                    <i class="fas fa-{{ $cat->est_actif ? 'eye-slash' : 'eye' }}"></i>
                                                </button>
                                            </form>
                                            @if($cat->paiements->count() == 0 && $cat->enfants->count() == 0)
                                            <form action="{{ route('esbtp.comptabilite.categories-paiement.destroy', $cat) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie?')">
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
                </div>
            </div>
            
            <!-- Informations et aide -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations sur les catégories de paiement</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5>Guide d'utilisation</h5>
                        <p>Les catégories de paiement vous permettent d'organiser et de classifier les différents types de paiements dans le système. Voici quelques points clés :</p>
                        <ul>
                            <li><strong>Catégories principales et sous-catégories</strong> : Vous pouvez créer une hiérarchie de catégories pour une organisation plus fine.</li>
                            <li><strong>Catégories obligatoires</strong> : Certaines catégories peuvent être marquées comme obligatoires, indiquant qu'elles correspondent à des frais obligatoires pour les étudiants.</li>
                            <li><strong>Activation/désactivation</strong> : Vous pouvez activer ou désactiver temporairement une catégorie sans la supprimer.</li>
                            <li><strong>Suppression</strong> : Une catégorie ne peut être supprimée que si elle n'a pas de paiements associés ni de sous-catégories.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 