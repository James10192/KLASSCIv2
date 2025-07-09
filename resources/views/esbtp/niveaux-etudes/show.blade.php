@extends('layouts.app')

@section('title', 'Détails du niveau : ' . $niveauxEtude->name)

@section('styles')
<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 30px;
    border-radius: 0 0 30px 30px;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50" cy="50" r="50"><stop offset="0" stop-color="rgba(255,255,255,.1)"/><stop offset="100" stop-color="rgba(255,255,255,0)"/></radialGradient></defs><circle cx="10" cy="10" r="10" fill="url(%23a)"/></svg>') repeat;
    opacity: 0.1;
}

.info-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.info-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 20px;
    padding: 25px;
    text-align: center;
    color: white;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transform: scale(0);
    transition: transform 0.5s ease;
}

.stat-card:hover::before {
    transform: scale(1);
}

.stat-card:hover {
    transform: scale(1.05) rotate(1deg);
}

.stat-card.primary {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-card.warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stat-card.info {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

.level-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 2rem;
    font-weight: bold;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    margin: 0 auto 20px;
}

.detail-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.detail-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.detail-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    margin: -25px -25px 20px -25px;
    border-radius: 15px 15px 0 0;
}

.progress-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: conic-gradient(from 0deg, #4facfe, #00f2fe, #4facfe);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
    position: relative;
}

.action-buttons {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    z-index: 1000;
}

.fab-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fab-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
}

.fab-edit {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.fab-back {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.fab-delete {
    background: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);
}

.animated-icon {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.list-group-item {
    border: none;
    padding: 15px 20px;
    margin-bottom: 8px;
    border-radius: 10px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.badge-custom {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>
@endsection

@section('content')
<!-- Hero Section -->
<div class="hero-section">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-3">
                    <div class="level-badge">
                        {{ $niveauxEtude->niveau ?? '1' }}
                    </div>
                    <div class="ms-4">
                        <h1 class="display-4 mb-0">{{ $niveauxEtude->name }}</h1>
                        <p class="lead mb-0 opacity-75">{{ $niveauxEtude->libelle ?? $niveauxEtude->code }}</p>
                    </div>
                </div>

                @if($niveauxEtude->description)
                    <p class="lead opacity-75">{{ $niveauxEtude->description }}</p>
                @endif
            </div>
            <div class="col-lg-4 text-end">
                <div class="d-flex justify-content-end align-items-center">
                    @if($niveauxEtude->is_active)
                        <span class="badge badge-custom bg-success fs-5">
                            <i class="fas fa-check-circle me-2"></i>Niveau Actif
                        </span>
                    @else
                        <span class="badge badge-custom bg-danger fs-5">
                            <i class="fas fa-times-circle me-2"></i>Niveau Inactif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <i class="fas fa-stream fa-3x mb-3 animated-icon"></i>
            <h3 class="mb-1">{{ $niveauxEtude->filieres->count() }}</h3>
            <p class="mb-0">Filières</p>
            <small class="opacity-75">Associées</small>
        </div>

        <div class="stat-card success">
            <i class="fas fa-book fa-3x mb-3 animated-icon"></i>
            <h3 class="mb-1">{{ $niveauxEtude->matieres->count() }}</h3>
            <p class="mb-0">Matières</p>
            <small class="opacity-75">Enseignées</small>
        </div>

        <div class="stat-card warning">
            <i class="fas fa-users fa-3x mb-3 animated-icon"></i>
            <h3 class="mb-1">{{ $niveauxEtude->classes->count() }}</h3>
            <p class="mb-0">Classes</p>
            <small class="opacity-75">Ouvertes</small>
        </div>

        <div class="stat-card info">
            <i class="fas fa-graduation-cap fa-3x mb-3 animated-icon"></i>
            <h3 class="mb-1">{{ $niveauxEtude->niveau ?? 1 }}{{ $niveauxEtude->niveau == 1 ? 'ère' : 'ème' }}</h3>
            <p class="mb-0">Année</p>
            <small class="opacity-75">d'études</small>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="row">
        <!-- General Information -->
        <div class="col-lg-6 mb-4">
            <div class="detail-card">
                <div class="detail-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations Générales
                    </h5>
                </div>

                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <strong class="text-muted">Nom complet</strong>
                        <div class="h6">{{ $niveauxEtude->name }}</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <strong class="text-muted">Code</strong>
                        <div class="h6">{{ $niveauxEtude->code }}</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <strong class="text-muted">Niveau</strong>
                        <div class="h6">{{ $niveauxEtude->niveau ?? 1 }}{{ ($niveauxEtude->niveau ?? 1) == 1 ? 'ère' : 'ème' }} année</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <strong class="text-muted">Type</strong>
                        <div class="h6">{{ $niveauxEtude->type ?? 'BTS' }}</div>
                    </div>
                </div>

                @if($niveauxEtude->libelle)
                    <div class="mt-3">
                        <strong class="text-muted">Libellé</strong>
                        <div class="h6">{{ $niveauxEtude->libelle }}</div>
                    </div>
                @endif

                @if($niveauxEtude->description)
                    <div class="mt-3">
                        <strong class="text-muted">Description</strong>
                        <p class="text-muted mb-0">{{ $niveauxEtude->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-lg-6 mb-4">
            <div class="detail-card">
                <div class="detail-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Statistiques Détaillées
                    </h5>
                </div>

                <div class="row text-center">
                    <div class="col-4 mb-3">
                        <div class="progress-circle mx-auto mb-2" style="background: conic-gradient(from 0deg, #4facfe 0deg, #00f2fe {{ ($niveauxEtude->filieres->count() / max($niveauxEtude->filieres->count(), 1)) * 360 }}deg, #f8f9fa {{ ($niveauxEtude->filieres->count() / max($niveauxEtude->filieres->count(), 1)) * 360 }}deg);">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <span class="text-dark fw-bold">{{ $niveauxEtude->filieres->count() }}</span>
                            </div>
                        </div>
                        <small class="text-muted">Filières</small>
                    </div>

                    <div class="col-4 mb-3">
                        <div class="progress-circle mx-auto mb-2" style="background: conic-gradient(from 0deg, #43e97b 0deg, #38f9d7 {{ ($niveauxEtude->matieres->count() / max($niveauxEtude->matieres->count(), 1)) * 360 }}deg, #f8f9fa {{ ($niveauxEtude->matieres->count() / max($niveauxEtude->matieres->count(), 1)) * 360 }}deg);">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <span class="text-dark fw-bold">{{ $niveauxEtude->matieres->count() }}</span>
                            </div>
                        </div>
                        <small class="text-muted">Matières</small>
                    </div>

                    <div class="col-4 mb-3">
                        <div class="progress-circle mx-auto mb-2" style="background: conic-gradient(from 0deg, #fa709a 0deg, #fee140 {{ ($niveauxEtude->classes->count() / max($niveauxEtude->classes->count(), 1)) * 360 }}deg, #f8f9fa {{ ($niveauxEtude->classes->count() / max($niveauxEtude->classes->count(), 1)) * 360 }}deg);">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <span class="text-dark fw-bold">{{ $niveauxEtude->classes->count() }}</span>
                            </div>
                        </div>
                        <small class="text-muted">Classes</small>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-primary">
                                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                <div class="h6 mb-0">Créé le</div>
                                <small class="text-muted">{{ $niveauxEtude->created_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-success">
                                <i class="fas fa-edit fa-2x mb-2"></i>
                                <div class="h6 mb-0">Modifié le</div>
                                <small class="text-muted">{{ $niveauxEtude->updated_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Data -->
    <div class="row">
        <!-- Filières associées -->
        @if($niveauxEtude->filieres->count() > 0)
            <div class="col-lg-4 mb-4">
                <div class="detail-card">
                    <div class="detail-header">
                        <h5 class="mb-0">
                            <i class="fas fa-stream me-2"></i>
                            Filières Associées
                        </h5>
                    </div>

                    <div class="list-group list-group-flush">
                        @foreach($niveauxEtude->filieres->take(5) as $filiere)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $filiere->name }}</strong>
                                    <br><small class="text-muted">{{ $filiere->code }}</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">{{ $filiere->id }}</span>
                            </div>
                        @endforeach

                        @if($niveauxEtude->filieres->count() > 5)
                            <div class="text-center mt-2">
                                <small class="text-muted">et {{ $niveauxEtude->filieres->count() - 5 }} autres...</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Matières enseignées -->
        @if($niveauxEtude->matieres->count() > 0)
            <div class="col-lg-4 mb-4">
                <div class="detail-card">
                    <div class="detail-header">
                        <h5 class="mb-0">
                            <i class="fas fa-book me-2"></i>
                            Matières Enseignées
                        </h5>
                    </div>

                    <div class="list-group list-group-flush">
                        @foreach($niveauxEtude->matieres->take(5) as $matiere)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $matiere->name }}</strong>
                                    <br><small class="text-muted">{{ $matiere->code }}</small>
                                </div>
                                <span class="badge bg-success rounded-pill">{{ $matiere->coefficient ?? 1 }}</span>
                            </div>
                        @endforeach

                        @if($niveauxEtude->matieres->count() > 5)
                            <div class="text-center mt-2">
                                <small class="text-muted">et {{ $niveauxEtude->matieres->count() - 5 }} autres...</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Classes ouvertes -->
        @if($niveauxEtude->classes->count() > 0)
            <div class="col-lg-4 mb-4">
                <div class="detail-card">
                    <div class="detail-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Classes Ouvertes
                        </h5>
                    </div>

                    <div class="list-group list-group-flush">
                        @foreach($niveauxEtude->classes->take(5) as $classe)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $classe->name }}</strong>
                                    <br><small class="text-muted">{{ $classe->code ?? 'Code non défini' }}</small>
                                </div>
                                <span class="badge bg-warning rounded-pill">{{ $classe->etudiants->count() ?? 0 }}</span>
                            </div>
                        @endforeach

                        @if($niveauxEtude->classes->count() > 5)
                            <div class="text-center mt-2">
                                <small class="text-muted">et {{ $niveauxEtude->classes->count() - 5 }} autres...</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Empty State -->
    @if($niveauxEtude->filieres->count() == 0 && $niveauxEtude->matieres->count() == 0 && $niveauxEtude->classes->count() == 0)
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune donnée associée</h5>
            <p class="text-muted">Ce niveau d'étude n'a pas encore de filières, matières ou classes associées.</p>
        </div>
    @endif
</div>

<!-- Floating Action Buttons -->
<div class="action-buttons">
    <a href="{{ route('esbtp.niveaux-etudes.edit', $niveauxEtude) }}" class="fab-btn fab-edit" title="Modifier">
        <i class="fas fa-edit"></i>
    </a>
    <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="fab-btn fab-back" title="Retour">
        <i class="fas fa-arrow-left"></i>
    </a>
    <button type="button" class="fab-btn fab-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Supprimer">
        <i class="fas fa-trash"></i>
    </button>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-trash fa-4x text-danger mb-3"></i>
                <h6>Êtes-vous sûr de vouloir supprimer ce niveau d'étude ?</h6>
                <p class="text-muted mb-3">{{ $niveauxEtude->name }}</p>

                @if($niveauxEtude->filieres->count() > 0 || $niveauxEtude->matieres->count() > 0 || $niveauxEtude->classes->count() > 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Ce niveau d'étude possède des données associées qui seront également affectées.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <form action="{{ route('esbtp.niveaux-etudes.destroy', $niveauxEtude) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Confirmer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée pour les cartes stats
    $('.stat-card').each(function(index) {
        $(this).css('opacity', '0').css('transform', 'translateY(30px)');
        setTimeout(() => {
            $(this).animate({
                opacity: 1
            }, 600).css('transform', 'translateY(0)');
        }, index * 150);
    });

    // Animation d'entrée pour les cartes détails
    $('.detail-card').each(function(index) {
        $(this).css('opacity', '0').css('transform', 'translateX(-30px)');
        setTimeout(() => {
            $(this).animate({
                opacity: 1
            }, 600).css('transform', 'translateX(0)');
        }, (index + 4) * 150);
    });

    // Effet tooltip sur les boutons
    $('[title]').tooltip();

    // Animation pour les floating buttons
    $('.fab-btn').hover(
        function() {
            $(this).find('i').addClass('fa-spin');
        },
        function() {
            $(this).find('i').removeClass('fa-spin');
        }
    );
});
</script>
@endsection
