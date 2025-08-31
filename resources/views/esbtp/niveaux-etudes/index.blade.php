@extends('layouts.app')

@section('title', 'Niveaux d\'études')

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl">
        <div class="header-content">
            <h1 class="header-title">Gestion des Niveaux d'Études</h1>
            <p class="header-subtitle">Gérez les différents niveaux d'études de votre établissement</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.niveaux-etudes.create') }}" class="btn-acasi primary">
                <i class="fas fa-plus-circle"></i> Nouveau Niveau
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid mb-4">
        <div class="kpi-card card-moderne" style="background-color: var(--primary);">
            <div style="color: white; text-align: center;">
                <i class="fas fa-layer-group fa-2x mb-md"></i>
                <div class="kpi-title" style="color: white;">Total Niveaux</div>
                <div class="kpi-value" style="color: white;">{{ $niveauxEtudes->count() }}</div>
            </div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--success);">
            <div style="color: white; text-align: center;">
                <i class="fas fa-check-circle fa-2x mb-md"></i>
                <div class="kpi-title" style="color: white;">Niveaux Actifs</div>
                <div class="kpi-value" style="color: white;">{{ $niveauxEtudes->where('is_active', true)->count() }}</div>
            </div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--warning);">
            <div style="color: white; text-align: center;">
                <i class="fas fa-users fa-2x mb-md"></i>
                <div class="kpi-title" style="color: white;">Total Classes</div>
                <div class="kpi-value" style="color: white;">{{ $niveauxEtudes->sum(function($niveau) { return $niveau->classes ? $niveau->classes->count() : 0; }) }}</div>
            </div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--info);">
            <div style="color: white; text-align: center;">
                <i class="fas fa-book fa-2x mb-md"></i>
                <div class="kpi-title" style="color: white;">Total Matières</div>
                <div class="kpi-value" style="color: white;">{{ $niveauxEtudes->sum(function($niveau) { return $niveau->matieres ? $niveau->matieres->count() : 0; }) }}</div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-lg" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); border-radius: var(--radius-medium); padding: var(--space-md);">
            <div style="color: var(--success); font-weight: 600;">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-lg" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-medium); padding: var(--space-md);">
            <div style="color: var(--danger); font-weight: 600;">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Table -->
    <div class="card-moderne mb-4">
        <div class="p-lg">
            <div class="section-title mb-lg">Liste des Niveaux d'Études</div>

            @if($niveauxEtudes->isEmpty())
                <div class="text-center p-xl">
                    <i class="fas fa-graduation-cap fa-3x color-neutral mb-lg"></i>
                    <h3 class="color-neutral">Aucun niveau d'étude</h3>
                    <p class="color-neutral">Aucun niveau d'étude n'a été trouvé dans le système.</p>
                    <a href="{{ route('esbtp.niveaux-etudes.create') }}" class="btn-acasi primary mt-md">
                        <i class="fas fa-plus-circle"></i> Créer le premier niveau
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover" style="border-collapse: separate; border-spacing: 0; border-radius: var(--radius-medium); overflow: hidden; box-shadow: var(--shadow-card);">
                        <thead style="background-color: var(--primary); color: white;">
                            <tr>
                                <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Niveau</th>
                                <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Informations</th>
                                <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Statistiques</th>
                                <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Statut</th>
                                <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody style="background-color: var(--surface);">
                            @foreach($niveauxEtudes as $niveauEtude)
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: var(--space-md);">
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 40px; height: 40px; border-radius: var(--radius-small); background: linear-gradient(135deg, var(--primary), var(--accent-blue)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: var(--space-md);">
                                                {{ $niveauEtude->niveau ?? substr($niveauEtude->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: var(--text-primary);">{{ $niveauEtude->name }}</div>
                                                <small style="color: var(--text-secondary);">{{ $niveauEtude->code }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: var(--space-md);">
                                        <div>
                                            <strong style="color: var(--text-primary);">{{ $niveauEtude->name }}</strong><br>
                                            <small style="color: var(--text-secondary);">Code: {{ $niveauEtude->code }}</small>
                                            @if($niveauEtude->libelle)
                                                <br><small style="color: var(--accent-blue);">{{ Str::limit($niveauEtude->libelle, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: var(--space-md);">
                                        <div style="display: flex; gap: var(--space-sm);">
                                            <div class="badge primary" style="text-align: center;">
                                                {{ $niveauEtude->filieres ? $niveauEtude->filieres->count() : 0 }} Filières
                                            </div>
                                            <div class="badge success" style="text-align: center;">
                                                {{ $niveauEtude->matieres ? $niveauEtude->matieres->count() : 0 }} Matières
                                            </div>
                                            <div class="badge warning" style="text-align: center;">
                                                {{ $niveauEtude->classes ? $niveauEtude->classes->count() : 0 }} Classes
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: var(--space-md);">
                                        @if($niveauEtude->is_active)
                                            <span class="badge success">
                                                <i class="fas fa-check"></i> Actif
                                            </span>
                                        @else
                                            <span class="badge danger">
                                                <i class="fas fa-times"></i> Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: var(--space-md); text-align: center;">
                                        <div style="display: flex; gap: var(--space-xs); justify-content: center;">
                                            <a href="{{ route('esbtp.niveaux-etudes.show', $niveauEtude) }}" class="btn-acasi secondary" style="padding: var(--space-xs) var(--space-sm);" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.niveaux-etudes.edit', $niveauEtude) }}" class="btn-acasi" style="background-color: var(--warning); color: white; padding: var(--space-xs) var(--space-sm);" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn-acasi" style="background-color: var(--danger); color: white; padding: var(--space-xs) var(--space-sm);" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $niveauEtude->id }}" title="Supprimer">
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

                                                    @if(($niveauEtude->filieres ? $niveauEtude->filieres->count() : 0) > 0 || ($niveauEtude->matieres ? $niveauEtude->matieres->count() : 0) > 0 || ($niveauEtude->classes ? $niveauEtude->classes->count() : 0) > 0)
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <strong>Attention :</strong> Ce niveau d'étude est lié à :
                                                            <ul class="mb-0 mt-2">
                                                                @if(($niveauEtude->filieres ? $niveauEtude->filieres->count() : 0) > 0)
                                                                    <li>{{ $niveauEtude->filieres ? $niveauEtude->filieres->count() : 0 }} filière(s)</li>
                                                                @endif
                                                                @if(($niveauEtude->matieres ? $niveauEtude->matieres->count() : 0) > 0)
                                                                    <li>{{ $niveauEtude->matieres ? $niveauEtude->matieres->count() : 0 }} matière(s)</li>
                                                                @endif
                                                                @if(($niveauEtude->classes ? $niveauEtude->classes->count() : 0) > 0)
                                                                    <li>{{ $niveauEtude->classes ? $niveauEtude->classes->count() : 0 }} classe(s)</li>
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
            @endif
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
