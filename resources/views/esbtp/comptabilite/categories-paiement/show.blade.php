@extends('layouts.app')

@section('title', 'Détails de la catégorie : ' . $categorie->nom)

@php
// Helper function to adjust brightness for gradient
function adjustBrightness($hex, $percent) {
    // Remove hash if present
    $hex = ltrim($hex, '#');
    
    // Make sure we have 6 digits
    if (strlen($hex) == 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    // Convert hex to rgb
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Adjust
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    // Convert back to hex
    return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
}

// Get the colors for gradient
$baseColor = $categorie->couleur ?? '#3498db';
$gradientColor = adjustBrightness(ltrim($baseColor, '#'), -20);
@endphp

@section('styles')
<style>
    .category-card {
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        margin-bottom: 1.75rem;
        overflow: hidden;
        border: none;
    }
    
    .category-header {
        color: white;
        padding: 24px;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    .category-header::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: linear-gradient(45deg, rgba(0, 0, 0, 0.1), transparent);
        z-index: 1;
    }
    
    .category-header > * {
        position: relative;
        z-index: 2;
    }
    
    .category-icon {
        font-size: 3rem;
        margin-right: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
    }
    
    .category-content {
        padding: 25px;
        background-color: white;
    }
    
    .category-detail {
        margin-bottom: 1.25rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .category-detail:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #444;
        font-size: 0.9rem;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
        letter-spacing: 0.5px;
    }
    
    .detail-value {
        font-size: 1.1rem;
        color: #333;
        line-height: 1.5;
    }
    
    .badge-mandatory {
        background-color: #f39c12;
        color: white;
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .badge-active {
        background-color: #2ecc71;
        color: white;
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .badge-inactive {
        background-color: #e74c3c;
        color: white;
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .related-category {
        display: inline-block;
        padding: 8px 15px;
        margin: 5px;
        border-radius: 50px;
        background-color: #f8f9fa;
        color: #495057;
        transition: all 0.2s ease;
        text-decoration: none;
        border: 1px solid #e9ecef;
    }
    
    .related-category:hover {
        background-color: #e9ecef;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }
    
    .action-btn {
        margin-right: 10px;
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 50px;
        transition: all 0.2s;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
    }
    
    .action-btn i {
        margin-right: 8px;
    }
    
    .info-icon {
        color: #3498db;
        margin-right: 10px;
    }
    
    .stat-card {
        text-align: center;
        padding: 15px;
        border-radius: 10px;
        background: #f8f9fa;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2c3e50;
    }
    
    .stat-label {
        color: #7f8c8d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .alert {
        border-radius: 10px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <!-- Catégorie principale -->
            <div class="category-card">
                <div class="category-header" style="background: linear-gradient(135deg, {{ $baseColor }}, {{ $gradientColor }});">
                    <div class="category-icon">
                        <i class="{{ $categorie->icone ?? 'fas fa-money-bill' }}"></i>
                    </div>
                    <div>
                        <h2 class="mb-1 fw-bold">{{ $categorie->nom }}</h2>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark me-2">
                                <i class="fas fa-hashtag me-1 opacity-50"></i>{{ $categorie->code }}
                            </span>
                            @if($categorie->est_obligatoire)
                            <span class="badge badge-mandatory">
                                <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                            </span>
                            @endif
                            @if($categorie->est_actif)
                            <span class="badge badge-active ms-2">
                                <i class="fas fa-check-circle me-1"></i>Actif
                            </span>
                            @else
                            <span class="badge badge-inactive ms-2">
                                <i class="fas fa-times-circle me-1"></i>Inactif
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="category-content">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value">{{ $paiements->count() }}</div>
                                <div class="stat-label">Paiements</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value">{{ $categorie->enfants->count() }}</div>
                                <div class="stat-label">Sous-catégories</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value">{{ number_format($paiements->sum('montant'), 0, ',', ' ') }}</div>
                                <div class="stat-label">Total FCFA</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="category-detail">
                        <div class="detail-label">
                            <i class="fas fa-align-left info-icon"></i>Description
                        </div>
                        <div class="detail-value">
                            @if($categorie->description)
                                {{ $categorie->description }}
                            @else
                                <span class="text-muted fst-italic">Aucune description fournie</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="category-detail">
                        <div class="detail-label">
                            <i class="fas fa-level-up-alt info-icon"></i>Catégorie parente
                        </div>
                        <div class="detail-value">
                            @if($categorie->parent)
                            <a href="{{ route('esbtp.comptabilite.categories-paiement.show', $categorie->parent->id) }}" 
                               class="btn btn-outline-primary" style="{{ $categorie->parent->getStyleBoutonAttribute() }}">
                                <i class="{{ $categorie->parent->icone ?? 'fas fa-folder' }} me-2"></i>
                                {{ $categorie->parent->nom }}
                            </a>
                            @else
                            <span class="text-muted">
                                <i class="fas fa-sitemap me-2"></i>
                                Catégorie principale (aucun parent)
                            </span>
                            @endif
                        </div>
                    </div>
                    
                    @if($categorie->enfants && $categorie->enfants->count() > 0)
                    <div class="category-detail">
                        <div class="detail-label">
                            <i class="fas fa-sitemap info-icon"></i>Sous-catégories
                        </div>
                        <div class="detail-value">
                            @foreach($categorie->enfants as $enfant)
                            <a href="{{ route('esbtp.comptabilite.categories-paiement.show', $enfant->id) }}" 
                               class="related-category" style="color: {{ $enfant->couleur }}; border-color: {{ $enfant->couleur }};">
                                <i class="{{ $enfant->icone ?? 'fas fa-folder' }} me-1"></i>
                                {{ $enfant->nom }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <div class="category-detail">
                        <div class="detail-label">
                            <i class="fas fa-cogs info-icon"></i>Détails techniques
                        </div>
                        <div class="detail-value">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ID:</strong> #{{ $categorie->id }}</p>
                                    <p><strong>Code:</strong> {{ $categorie->code }}</p>
                                    <p><strong>Ordre d'affichage:</strong> {{ $categorie->ordre ?? '1' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Créée le:</strong> {{ $categorie->created_at->format('d/m/Y H:i') }}</p>
                                    <p><strong>Mise à jour le:</strong> {{ $categorie->updated_at->format('d/m/Y H:i') }}</p>
                                    <p><strong>Statut:</strong> 
                                        @if($categorie->est_actif)
                                        <span class="text-success fw-bold">
                                            <i class="fas fa-check-circle me-1"></i>Actif
                                        </span>
                                        @else
                                        <span class="text-danger fw-bold">
                                            <i class="fas fa-times-circle me-1"></i>Inactif
                                        </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('esbtp.comptabilite.categories-paiement.index') }}" class="btn btn-outline-secondary action-btn">
                                    <i class="fas fa-arrow-left"></i> Retour à la liste
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('esbtp.comptabilite.categories-paiement.edit', $categorie->id) }}" class="btn btn-primary action-btn">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                
                                @if($categorie->est_actif)
                                <button type="button" class="btn btn-warning action-btn" data-bs-toggle="modal" data-bs-target="#toggleStatusModal">
                                    <i class="fas fa-ban"></i> Désactiver
                                </button>
                                @else
                                <button type="button" class="btn btn-success action-btn" data-bs-toggle="modal" data-bs-target="#toggleStatusModal">
                                    <i class="fas fa-check"></i> Activer
                                </button>
                                @endif
                                
                                <button type="button" class="btn btn-danger action-btn" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Paiements liés -->
            <div class="category-card">
                <div class="card-header" style="background-color: {{ $baseColor }}; color: white; padding: 15px 20px;">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Paiements associés</h5>
                </div>
                <div class="category-content">
                    @if($paiements && $paiements->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paiements as $paiement)
                                <tr>
                                    <td>#{{ $paiement->id }}</td>
                                    <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                    <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('esbtp.paiements.show', $paiement->id) }}"
                                           class="btn btn-sm btn-info rounded-circle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($paiements->count() > 5)
                    <div class="text-center mt-3">
                        <a href="{{ route('esbtp.paiements.index', ['categorie' => $categorie->id]) }}"
                           class="btn btn-outline-primary btn-sm rounded-pill px-4">
                            <i class="fas fa-list me-2"></i>Voir tous les paiements
                        </a>
                    </div>
                    @endif
                    
                    @else
                    <div class="alert alert-info mb-0 d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            Aucun paiement associé à cette catégorie pour le moment.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Informations -->
            <div class="category-card">
                <div class="card-header bg-info text-white" style="padding: 15px 20px;">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informations</h5>
                </div>
                <div class="category-content">
                    <div class="alert alert-light mb-3">
                        <h6 class="alert-heading fw-bold mb-2"><i class="fas fa-lightbulb me-2 text-warning"></i> À savoir</h6>
                        <ul class="mb-0 ps-3">
                            <li class="mb-2">Les catégories <strong>obligatoires</strong> sont automatiquement ajoutées à tous les étudiants.</li>
                            <li class="mb-2">Les catégories <strong>inactives</strong> ne peuvent pas être utilisées pour de nouveaux paiements.</li>
                            <li class="mb-2">Une catégorie avec des sous-catégories ne peut pas être supprimée.</li>
                            <li>Les catégories avec des paiements associés ne peuvent être supprimées que si tous les paiements sont supprimés au préalable.</li>
                        </ul>
                    </div>
                    
                    @if($categorie->est_obligatoire)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Catégorie obligatoire</strong> : Cette catégorie est considérée comme essentielle pour tous les étudiants.
                    </div>
                    @endif
                    
                    @if(!$categorie->est_actif)
                    <div class="alert alert-danger">
                        <i class="fas fa-ban me-2"></i>
                        <strong>Catégorie inactive</strong> : Cette catégorie ne peut actuellement pas être utilisée pour de nouveaux paiements.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de changement de statut -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header {{ $categorie->est_actif ? 'bg-warning' : 'bg-success' }} text-white">
                <h5 class="modal-title" id="toggleStatusModalLabel">
                    @if($categorie->est_actif)
                    <i class="fas fa-ban me-2"></i>Désactiver la catégorie
                    @else
                    <i class="fas fa-check-circle me-2"></i>Activer la catégorie
                    @endif
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($categorie->est_actif)
                <p>Êtes-vous sûr de vouloir désactiver la catégorie <strong>{{ $categorie->nom }}</strong> ?</p>
                <p>Les conséquences seront les suivantes :</p>
                <ul>
                    <li>La catégorie ne sera plus disponible pour de nouveaux paiements</li>
                    <li>Les paiements existants ne seront pas affectés</li>
                    <li>La catégorie restera visible dans les rapports et l'historique</li>
                </ul>
                @else
                <p>Êtes-vous sûr de vouloir activer la catégorie <strong>{{ $categorie->nom }}</strong> ?</p>
                <p>Cette catégorie sera à nouveau disponible pour tous les paiements.</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.comptabilite.categories-paiement.toggle-status', $categorie->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    @if($categorie->est_actif)
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-ban me-2"></i>Confirmer la désactivation
                    </button>
                    @else
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Confirmer l'activation
                    </button>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-trash me-2"></i>Supprimer la catégorie
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading">Attention !</h5>
                            <p class="mb-0">Cette action est irréversible et supprimera définitivement cette catégorie de paiement.</p>
                        </div>
                    </div>
                </div>
                
                <p>Êtes-vous sûr de vouloir supprimer définitivement la catégorie <strong>{{ $categorie->nom }}</strong> ?</p>
                
                @if($categorie->enfants && $categorie->enfants->count() > 0)
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i> 
                    <strong>Impossible de supprimer :</strong> Cette catégorie possède {{ $categorie->enfants->count() }} sous-catégorie(s). 
                    Vous devez d'abord supprimer ou réaffecter toutes les sous-catégories.
                </div>
                @endif
                
                @if($paiements && $paiements->count() > 0)
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Impossible de supprimer :</strong> Cette catégorie est associée à {{ $paiements->count() }} paiement(s).
                    Vous devez d'abord supprimer ou réaffecter tous les paiements.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <form action="{{ route('esbtp.comptabilite.categories-paiement.destroy', $categorie->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" 
                        {{ ($categorie->enfants && $categorie->enfants->count() > 0) || ($paiements && $paiements->count() > 0) ? 'disabled' : '' }}>
                        <i class="fas fa-trash me-2"></i>Confirmer la suppression
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 