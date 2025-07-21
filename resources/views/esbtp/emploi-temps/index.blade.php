@extends('layouts.app')

@section('title', 'Emplois du temps - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour les emplois du temps */
    .emploi-temps-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .emploi-temps-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .emploi-stat-card {
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .emploi-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }
    
    .emploi-stat-card.primary::before { background: var(--primary); }
    .emploi-stat-card.success::before { background: var(--success); }
    .emploi-stat-card.info::before { background: var(--accent-blue); }
    .emploi-stat-card.warning::before { background: var(--warning); }
    
    .emploi-stat-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-sm);
        font-size: 20px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .emploi-stat-card.primary .emploi-stat-icon { color: var(--primary); }
    .emploi-stat-card.success .emploi-stat-icon { color: var(--success); }
    .emploi-stat-card.info .emploi-stat-icon { color: var(--accent-blue); }
    .emploi-stat-card.warning .emploi-stat-icon { color: var(--warning); }
    
    .emploi-stat-value {
        font-size: var(--amount-large);
        font-weight: bold;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }
    
    .emploi-stat-label {
        color: var(--text-secondary);
        font-size: var(--text-small);
        font-weight: 500;
    }
    
    .emploi-filter-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
    }
    
    .emploi-table-container {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: none;
    }
    
    .emploi-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
        margin-bottom: var(--space-md);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .emploi-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }
    
    .emploi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary);
    }
    
    .emploi-card.active::before {
        background: var(--success);
    }
    
    .emploi-card-header {
        padding: var(--space-md);
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: between;
        align-items: center;
    }
    
    .emploi-card-title {
        font-weight: 600;
        font-size: var(--text-normal);
        color: var(--primary);
        margin: 0;
    }
    
    .emploi-card-body {
        padding: var(--space-md);
    }
    
    .emploi-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: var(--space-sm);
        margin-bottom: var(--space-md);
    }
    
    .emploi-info-item {
        text-align: center;
    }
    
    .emploi-info-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-bottom: 2px;
        font-weight: 500;
    }
    
    .emploi-info-value {
        font-size: var(--text-normal);
        color: var(--text-primary);
        font-weight: 600;
    }
    
    .emploi-actions {
        display: flex;
        gap: var(--space-xs);
        justify-content: flex-end;
        padding-top: var(--space-sm);
        border-top: 1px solid #f3f4f6;
        margin-top: var(--space-sm);
    }
    
    .table-moderne {
        margin-bottom: 0;
    }
    
    .table-moderne th {
        background-color: var(--background);
        color: var(--text-primary);
        font-weight: 600;
        font-size: var(--text-small);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
        padding: var(--space-md);
        white-space: nowrap;
        min-width: fit-content;
    }
    
    .table-moderne td {
        padding: var(--space-md);
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        white-space: nowrap;
        min-width: fit-content;
    }
    
    /* Largeurs spécifiques pour les colonnes */
    .table-moderne .col-classe {
        min-width: 120px;
        font-weight: 600;
    }
    
    .table-moderne .col-filiere {
        min-width: 140px;
    }
    
    .table-moderne .col-niveau {
        min-width: 100px;
    }
    
    .table-moderne .col-annee {
        min-width: 150px;
    }
    
    .table-moderne .col-periode {
        min-width: 120px;
    }
    
    .table-moderne .col-statut {
        min-width: 180px;
        white-space: nowrap;
    }
    
    .table-moderne .col-statut .badge-moderne {
        display: inline-block;
        margin-right: 4px;
        margin-bottom: 2px;
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .table-moderne .col-actions {
        min-width: 120px;
        text-align: center;
    }
    
    .table-moderne tbody tr:hover {
        background-color: rgba(30, 58, 138, 0.02);
    }
    
    .badge-moderne {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        font-weight: 500;
    }
    
    .badge-moderne.primary {
        background-color: rgba(30, 58, 138, 0.1);
        color: var(--primary);
    }
    
    .badge-moderne.success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }
    
    .badge-moderne.secondary {
        background-color: rgba(107, 114, 128, 0.1);
        color: var(--neutral);
    }
    
    .badge-moderne.info {
        background-color: rgba(6, 182, 212, 0.1);
        color: var(--accent-blue);
    }
    
    .btn-group-moderne {
        display: flex;
        gap: var(--space-xs);
    }
    
    .btn-moderne {
        padding: var(--space-xs) var(--space-sm);
        border: none;
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    
    .btn-moderne.info {
        background-color: var(--accent-blue);
        color: white;
    }
    
    .btn-moderne.warning {
        background-color: var(--warning);
        color: white;
    }
    
    .btn-moderne.danger {
        background-color: var(--danger);
        color: white;
    }
    
    .btn-moderne:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne des emplois du temps -->
        <div class="emploi-temps-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="emploi-stat-icon me-4">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h1 class="mb-1">Gestion des emplois du temps</h1>
                        <p class="mb-0 opacity-75">Administration avancée des plannings scolaires avec intégration planning</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->can('create_timetable'))
                        <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus-circle me-2"></i>Nouveau
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('superAdmin'))
                    <form action="{{ url('esbtp/activate-all-timetables') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-acasi success" title="Active uniquement l'emploi du temps le plus récent pour chaque classe">
                            <i class="fas fa-check-circle me-2"></i>Activer récents
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistiques des emplois du temps -->
        <div class="kpi-grid mb-xl">
            <div class="card-moderne emploi-stat-card primary">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $totalEmploisTemps }}</div>
                    <div class="emploi-stat-label">Total emplois du temps</div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card success">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $emploisTempsActifs }}</div>
                    <div class="emploi-stat-label">Emplois du temps actifs</div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card info">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $totalSeances }}</div>
                    <div class="emploi-stat-label">Total séances de cours</div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card warning">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $emploisTempsAnneeEnCours }}</div>
                    <div class="emploi-stat-label">Année en cours</div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-lg" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-lg" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Main content -->
            <div class="col-lg-8">
                <div class="emploi-table-container">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>Liste des emplois du temps
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-moderne datatable" id="emploiTempsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="col-classe">Classe</th>
                                    <th class="col-filiere">Filière</th>
                                    <th class="col-niveau">Niveau</th>
                                    <th class="col-annee">Année universitaire</th>
                                    <th class="col-periode">Période</th>
                                    <th class="col-statut">Statut</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                    @forelse($emploisTemps as $emploiTemps)
                                        <tr>
                                            <td class="col-classe">{{ $emploiTemps->classe->name ?? 'Non définie' }}</td>
                                            <td class="col-filiere">{{ $emploiTemps->classe->filiere->name ?? 'Non définie' }}</td>
                                            <td class="col-niveau">{{ $emploiTemps->classe->niveau->name ?? 'Non défini' }}</td>
                                            <td class="col-annee">{{ $emploiTemps->annee->name ?? 'Non définie' }}</td>
                                            <td class="col-periode">
                                                @if($emploiTemps->semestre == 'Semestre 1')
                                                    <span class="badge-moderne primary">Semestre 1</span>
                                                @elseif($emploiTemps->semestre == 'Semestre 2')
                                                    <span class="badge-moderne primary">Semestre 2</span>
                                                @else
                                                    <span class="badge-moderne primary">Année complète</span>
                                                @endif
                                            </td>
                                            <td class="col-statut">
                                                @if($emploiTemps->is_active)
                                                    <span class="badge-moderne success">Actif</span>
                                                @else
                                                    <span class="badge-moderne secondary">Inactif</span>
                                                @endif
                                                @if(optional($emploiTemps)->is_current)
                                                    <span class="badge-moderne info">Courant</span>
                                                @endif
                                            </td>
                                            <td class="col-actions">
                                                <div class="btn-group-moderne">
                                                    <a href="{{ route('esbtp.emploi-temps.show', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-moderne info" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->can('edit_timetables'))
                                                    <a href="{{ route('esbtp.emploi-temps.edit', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-moderne warning" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endif
                                                    @if(auth()->user()->hasRole('superAdmin') && auth()->user()->can('delete_timetables'))
                                                    <button type="button" class="btn-moderne danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $emploiTemps->id }}" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    @endif
                                                </div>

                                            @if(auth()->user()->hasRole('superAdmin') && auth()->user()->can('delete_timetables'))
                                            <!-- Modal de confirmation de suppression -->
                                            <div class="modal fade" id="deleteModal{{ $emploiTemps->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $emploiTemps->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $emploiTemps->id }}">Confirmation de suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                <strong>Attention :</strong> Cette action est irréversible.
                                                            </div>
                                                            <p>Êtes-vous sûr de vouloir supprimer cet emploi du temps ?</p>
                                                            <p><strong>Classe :</strong> {{ $emploiTemps->classe->name ?? 'Non définie' }}</p>
                                                            <p><strong>Année universitaire :</strong> {{ $emploiTemps->annee->name ?? 'Non définie' }}</p>
                                                            <p class="text-danger"><strong>Attention :</strong> Cette action supprimera également toutes les séances de cours associées à cet emploi du temps.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="{{ route('esbtp.emploi-temps.destroy', ['emploi_temp' => $emploiTemps->id]) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="fas fa-trash me-2"></i> Supprimer
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div class="py-5">
                                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                    <p class="mb-0">Aucun emploi du temps n'a été créé.</p>
                                                    <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn-acasi primary mt-3">
                                                        <i class="fas fa-plus-circle me-1"></i>Créer un emploi du temps
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar avec filtres -->
            <div class="col-lg-4">
                <div class="emploi-filter-card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filtrer les emplois du temps
                        </h6>
                    </div>
                    <div class="card-body">
                    <form action="{{ route('esbtp.emploi-temps.index') }}" method="GET" id="filterForm">
                        <div class="mb-3">
                            <label for="filiere_id" class="form-label">Filière</label>
                            <select class="form-select select2" id="filiere_id" name="filiere_id">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="niveau_id" class="form-label">Niveau d'études</label>
                            <select class="form-select select2" id="niveau_id" name="niveau_id">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="annee_id" class="form-label">Année universitaire</label>
                            <select class="form-select select2" id="annee_id" name="annee_id">
                                <option value="">Toutes les années</option>
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs uniquement</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactifs uniquement</option>
                                <option value="current" {{ request('status') == 'current' ? 'selected' : '' }}>Courants uniquement</option>
                            </select>
                        </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-search me-2"></i>Appliquer les filtres
                                </button>
                                <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn-acasi secondary">
                                    <i class="fas fa-redo-alt me-2"></i>Réinitialiser
                                </a>
                            </div>
                    </form>
                </div>
            </div>

                <!-- Actions rapides -->
                <div class="emploi-filter-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Actions rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn-acasi success">
                                <i class="fas fa-plus-circle me-2"></i>Créer emploi du temps
                            </a>
                            <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="btn-acasi info">
                                <i class="fas fa-chart-pie me-2"></i>Répartition matières
                            </a>
                            <a href="{{ route('esbtp.planning-general.annuel') }}" class="btn-acasi warning">
                                <i class="fas fa-calendar-alt me-2"></i>Planning annuel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const table = $('#emploiTempsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
            },
            pageLength: 10,
            responsive: true,
            order: [[3, 'desc'], [1, 'asc']]
        });

        // Initialize Select2 for enhanced selects
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Submit filter form when selects change
        $('#filiere_id, #niveau_id, #annee_id, #status').change(function() {
            $('#filterForm').submit();
        });
    });
</script>
@endsection
