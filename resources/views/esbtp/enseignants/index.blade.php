@extends('layouts.app')

@section('title', 'Gestion des Enseignants')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .stats-enseignants {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .stat-enseignant {
        text-align: center;
        position: relative;
        overflow: hidden;
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
    }

    .stat-enseignant::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }

    .stat-enseignant.total::before { background: linear-gradient(90deg, var(--primary), #60a5fa); }
    .stat-enseignant.actifs::before { background: linear-gradient(90deg, var(--success), #34d399); }
    .stat-enseignant.inactifs::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .filter-section {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: var(--shadow-card);
    }

    .enseignant-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-md);
        box-shadow: var(--shadow-card);
    }

    .enseignant-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-card-hover);
        border-left-color: var(--primary);
    }

    .enseignant-card .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-md);
        flex-wrap: wrap;
    }

    .enseignant-info {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .enseignant-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .enseignant-details h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .enseignant-details p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .enseignant-actions {
        display: flex;
        gap: var(--space-sm);
        flex-wrap: wrap;
    }

    .table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-modern thead th {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        padding: var(--space-md);
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 2px solid var(--border);
        text-align: left;
    }

    .table-modern tbody td {
        padding: var(--space-md);
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: rgba(4, 83, 203, 0.05);
    }

    .bulk-select-checkbox {
        width: 20px;
        height: 20px;
        accent-color: var(--primary);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chalkboard-teacher me-2"></i>Gestion des Enseignants</h1>
                <p class="header-subtitle">Gérez les profils et disponibilités des enseignants</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi warning" data-bs-toggle="modal" data-bs-target="#bulkAvailabilityModal">
                    <i class="fas fa-calendar-check"></i>Modifier Disponibilités
                </button>
                <a href="{{ route('esbtp.enseignants.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouveau Profil
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Statistiques -->
        <div class="stats-enseignants">
            <div class="stat-enseignant total">
                <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                <div class="stat-label">Total Enseignants</div>
            </div>
            <div class="stat-enseignant actifs">
                <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                <div class="stat-label">Actifs</div>
            </div>
            <div class="stat-enseignant inactifs">
                <div class="stat-value">{{ $stats['inactive'] ?? 0 }}</div>
                <div class="stat-label">Inactifs</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-section">
            <form method="GET" action="{{ route('esbtp.enseignants.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" placeholder="Nom, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactifs</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Département</label>
                    <select name="department_id" class="form-select">
                        <option value="">Tous les départements</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-acasi primary w-100">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des enseignants -->
        <div class="card-moderne">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Enseignants</h5>
                <span class="badge bg-primary">{{ $teachers->total() ?? 0 }} résultat(s)</span>
            </div>
            <div class="card-body p-0">
                @if(isset($teachers) && $teachers->count() > 0)
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Enseignant</th>
                                    <th>Contact</th>
                                    <th>Spécialisation</th>
                                    <th>Département</th>
                                    <th>Statut</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teachers as $teacher)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-select-checkbox" value="{{ $teacher->id }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="enseignant-avatar">
                                                    {{ $teacher->user ? strtoupper(substr($teacher->user->name, 0, 2)) : 'NA' }}
                                                </div>
                                                <div>
                                                    <strong>{{ $teacher->user->name ?? 'N/A' }}</strong>
                                                    <br><small class="text-muted">{{ $teacher->matricule ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small>{{ $teacher->user->email ?? 'N/A' }}</small>
                                            @if($teacher->user && $teacher->user->phone)
                                                <br><small class="text-muted">{{ $teacher->user->phone }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $teacher->specialization ?? '-' }}</td>
                                        <td>{{ $teacher->department->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $teacher->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('esbtp.enseignants.show', $teacher) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.enseignants.edit', $teacher) }}"
                                                   class="btn btn-sm btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('esbtp.enseignants.toggleStatus', $teacher) }}"
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-{{ $teacher->status === 'active' ? 'secondary' : 'success' }}"
                                                            title="{{ $teacher->status === 'active' ? 'Désactiver' : 'Activer' }}">
                                                        <i class="fas fa-{{ $teacher->status === 'active' ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="p-3">
                        {{ $teachers->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <h5>Aucun enseignant trouvé</h5>
                        <p class="text-muted">Ajoutez votre premier enseignant ou modifiez vos filtres.</p>
                        <a href="{{ route('esbtp.enseignants.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Ajouter un enseignant
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Sélection pour Bulk Availability -->
<div class="modal fade" id="bulkAvailabilityModal" tabindex="-1" aria-labelledby="bulkAvailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 18px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <form method="GET" action="{{ route('esbtp.enseignants.bulk-availability') }}" id="bulk-availability-form">
                <div class="modal-header" style="background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%); color: white; border-radius: 18px 18px 0 0; padding: 1.25rem 1.5rem;">
                    <h5 class="modal-title fw-bold" id="bulkAvailabilityModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>Modifier les disponibilités
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <p class="text-muted mb-3">Sélectionnez les enseignants dont vous souhaitez modifier les disponibilités.</p>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bulk-select-all-modal">
                            <label class="form-check-label fw-semibold" for="bulk-select-all-modal">
                                Tout sélectionner
                            </label>
                        </div>
                        <span class="badge bg-light text-dark" id="selected-count">0 sélectionné(s)</span>
                    </div>

                    @php
                        $allTeachers = \App\Models\ESBTPTeacher::with(['user', 'department'])
                            ->where('status', 'active')
                            ->orderBy('id')
                            ->get();
                    @endphp

                    @if($allTeachers->isEmpty())
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucun enseignant actif disponible.
                        </div>
                    @else
                        <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                            @foreach($allTeachers as $t)
                                <label class="list-group-item d-flex align-items-center gap-3" style="cursor: pointer;">
                                    <input class="form-check-input bulk-modal-checkbox"
                                           type="checkbox" name="ids[]"
                                           value="{{ $t->id }}">
                                    <div class="enseignant-avatar" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                        {{ $t->user ? strtoupper(substr($t->user->name, 0, 2)) : 'NA' }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $t->user->name ?? 'N/A' }}</div>
                                        <div class="small text-muted">
                                            {{ $t->specialization ?? 'Pas de spécialisation' }}
                                            @if($t->department)
                                                · {{ $t->department->name }}
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" id="bulk-availability-submit" {{ $allTeachers->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-arrow-right me-1"></i>Modifier les disponibilités
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Gestion du modal bulk availability
    const bulkModal = document.getElementById('bulkAvailabilityModal');
    if (bulkModal) {
        const selectAll = document.getElementById('bulk-select-all-modal');
        const submitButton = document.getElementById('bulk-availability-submit');
        const selectedCount = document.getElementById('selected-count');
        const checkboxes = () => bulkModal.querySelectorAll('.bulk-modal-checkbox');

        const updateState = () => {
            const boxes = Array.from(checkboxes());
            const checkedCount = boxes.filter(box => box.checked).length;

            if (selectAll) {
                selectAll.checked = checkedCount > 0 && checkedCount === boxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
            }

            if (submitButton) {
                submitButton.disabled = checkedCount === 0;
            }

            if (selectedCount) {
                selectedCount.textContent = checkedCount + ' sélectionné(s)';
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes().forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                selectAll.indeterminate = false;
                updateState();
            });
        }

        checkboxes().forEach(checkbox => {
            checkbox.addEventListener('change', updateState);
        });

        updateState();
    }

    // Sélection depuis la table principale
    const tableCheckboxes = document.querySelectorAll('.bulk-select-checkbox');
    tableCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            // Synchroniser avec le modal
            const modalCb = document.querySelector(`.bulk-modal-checkbox[value="${this.value}"]`);
            if (modalCb) {
                modalCb.checked = this.checked;
                // Trigger update
                const event = new Event('change');
                modalCb.dispatchEvent(event);
            }
        });
    });
});
</script>
@endpush
@endsection
