@extends('layouts.app')

@section('title', 'Niveaux d\'études - ESBTP-yAKRO')

@section('styles')
<style>
.gradient-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}

.modern-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.stats-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    border-radius: 15px;
    color: white;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: scale(1.05);
}

.stats-card.primary {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stats-card.success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stats-card.warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stats-card.danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);
}

.animated-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.table-modern {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.table-modern thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.table-modern tbody tr {
    transition: all 0.3s ease;
}

.table-modern tbody tr:hover {
    background-color: #f8f9ff;
    transform: scale(1.01);
}

.badge-modern {
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.btn-modern {
    border-radius: 25px;
    padding: 8px 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.floating-add-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-size: 24px;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
}

.floating-add-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
    border-radius: 0 0 25px 25px;
}

.progress-ring {
    width: 80px;
    height: 80px;
}

.progress-ring-circle {
    transition: stroke-dasharray 0.35s;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.level-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 10px;
}

.level-1 { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
.level-2 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
.level-3 { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
.level-4 { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-graduation-cap animated-icon me-3"></i>
                    Gestion des Niveaux d'Études
                </h1>
                <p class="mb-0 mt-2 opacity-75">Gérez les différents niveaux d'études de votre établissement</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex justify-content-end align-items-center">
                    <div class="text-center me-4">
                        <div class="h2 mb-0">{{ $niveauxEtudes->count() }}</div>
                        <small class="opacity-75">Niveaux Total</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card primary h-100">
                <div class="card-body text-center">
                    <i class="fas fa-layer-group fa-3x mb-3 animated-icon"></i>
                    <h3 class="mb-1">{{ $niveauxEtudes->count() }}</h3>
                    <p class="mb-0">Total Niveaux</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card success h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x mb-3 animated-icon"></i>
                    <h3 class="mb-1">{{ $niveauxEtudes->where('is_active', true)->count() }}</h3>
                    <p class="mb-0">Niveaux Actifs</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card warning h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x mb-3 animated-icon"></i>
                    <h3 class="mb-1">{{ $niveauxEtudes->sum(function($niveau) { return $niveau->classes->count(); }) }}</h3>
                    <p class="mb-0">Total Classes</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card danger h-100">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-3x mb-3 animated-icon"></i>
                    <h3 class="mb-1">{{ $niveauxEtudes->sum(function($niveau) { return $niveau->matieres->count(); }) }}</h3>
                    <p class="mb-0">Total Matières</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header gradient-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">
                                <i class="fas fa-table me-2"></i>
                                Liste des Niveaux d'Études
                            </h5>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('esbtp.niveaux-etudes.create') }}" class="btn btn-light btn-modern">
                                <i class="fas fa-plus me-2"></i>Nouveau Niveau
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-modern table-hover mb-0" id="niveaux-etudes-table">
                            <thead>
                                <tr>
                                    <th class="border-0">Niveau</th>
                                    <th class="border-0">Informations</th>
                                    <th class="border-0">Statistiques</th>
                                    <th class="border-0">Statut</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($niveauxEtudes as $niveauEtude)
                                    <tr>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center">
                                                <div class="level-icon level-{{ $niveauEtude->niveau ?? 1 }}">
                                                    {{ $niveauEtude->niveau ?? '1' }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $niveauEtude->name }}</div>
                                                    <small class="text-muted">{{ $niveauEtude->code }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div>
                                                <strong>{{ $niveauEtude->name }}</strong><br>
                                                <small class="text-muted">Code: {{ $niveauEtude->code }}</small>
                                                @if($niveauEtude->libelle)
                                                    <br><small class="text-info">{{ Str::limit($niveauEtude->libelle, 50) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="row g-2">
                                                <div class="col-4 text-center">
                                                    <div class="badge badge-modern bg-primary">
                                                        {{ $niveauEtude->filieres->count() }}
                                                    </div>
                                                    <div><small>Filières</small></div>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="badge badge-modern bg-success">
                                                        {{ $niveauEtude->matieres->count() }}
                                                    </div>
                                                    <div><small>Matières</small></div>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="badge badge-modern bg-warning">
                                                        {{ $niveauEtude->classes->count() }}
                                                    </div>
                                                    <div><small>Classes</small></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            @if($niveauEtude->is_active)
                                                <span class="badge badge-modern bg-success">
                                                    <i class="fas fa-check me-1"></i>Actif
                                                </span>
                                            @else
                                                <span class="badge badge-modern bg-danger">
                                                    <i class="fas fa-times me-1"></i>Inactif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('esbtp.niveaux-etudes.show', $niveauEtude) }}"
                                                   class="btn btn-sm btn-outline-info" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.niveaux-etudes.edit', $niveauEtude) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal{{ $niveauEtude->id }}"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal de confirmation de suppression -->
                                    <div class="modal fade" id="deleteModal{{ $niveauEtude->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content modern-card">
                                                <div class="modal-header gradient-card text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        Confirmation de suppression
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="text-center mb-3">
                                                        <i class="fas fa-trash fa-3x text-danger mb-3"></i>
                                                        <h6>Êtes-vous sûr de vouloir supprimer ce niveau d'étude ?</h6>
                                                        <p class="text-muted">{{ $niveauEtude->name }}</p>
                                                    </div>

                                                    @if($niveauEtude->filieres->count() > 0 || $niveauEtude->matieres->count() > 0 || $niveauEtude->classes->count() > 0)
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <strong>Attention :</strong> Ce niveau d'étude est lié à :
                                                            <ul class="mb-0 mt-2">
                                                                @if($niveauEtude->filieres->count() > 0)
                                                                    <li>{{ $niveauEtude->filieres->count() }} filière(s)</li>
                                                                @endif
                                                                @if($niveauEtude->matieres->count() > 0)
                                                                    <li>{{ $niveauEtude->matieres->count() }} matière(s)</li>
                                                                @endif
                                                                @if($niveauEtude->classes->count() > 0)
                                                                    <li>{{ $niveauEtude->classes->count() }} classe(s)</li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-1"></i>Annuler
                                                    </button>
                                                    <form action="{{ route('esbtp.niveaux-etudes.destroy', $niveauEtude) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-modern">
                                                            <i class="fas fa-trash me-1"></i>Confirmer
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<button class="floating-add-btn" onclick="window.location.href='{{ route('esbtp.niveaux-etudes.create') }}'">
    <i class="fas fa-plus"></i>
</button>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // DataTable avec style personnalisé
        $('#niveaux-etudes-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
            },
            responsive: true,
            order: [[0, 'asc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });

        // Animation d'entrée pour les cartes stats
        $('.stats-card').each(function(index) {
            $(this).css('opacity', '0').css('transform', 'translateY(20px)');
            setTimeout(() => {
                $(this).animate({
                    opacity: 1
                }, 500).css('transform', 'translateY(0)');
            }, index * 100);
        });

        // Effet tooltip sur les boutons
        $('[title]').tooltip();
    });
</script>
@endsection
