@extends('layouts.app')

@section('title', 'Gestion des Relances')

@push('styles')
<style>
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 15px;
    color: white;
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stats-card.success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stats-card.danger {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.relance-card {
    border-radius: 10px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.relance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.badge-niveau {
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.btn-action {
    padding: 6px 12px;
    border-radius: 20px;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-1px);
}

.filter-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-bell text-primary me-2"></i>
                        Gestion des Relances
                    </h1>
                    <p class="text-muted mb-0">Suivi et gestion des relances de paiement</p>
                </div>
                <div>
                    <a href="{{ route('esbtp.comptabilite.relances.analytics') }}" class="btn btn-info me-2">
                        <i class="fas fa-chart-line me-1"></i>
                        Analytics
                    </a>
                    <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalPlanifierRelances">
                        <i class="fas fa-calendar-plus me-1"></i>
                        Planifier Relances
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConfigTemplates">
                        <i class="fas fa-cog me-1"></i>
                        Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">Total Relances</h6>
                            <h3 class="mb-0">{{ number_format($statistiques['total']) }}</h3>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">Planifiées</h6>
                            <h3 class="mb-0">{{ number_format($statistiques['planifiees']) }}</h3>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">Envoyées</h6>
                            <h3 class="mb-0">{{ number_format($statistiques['envoyees']) }}</h3>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">Échecs</h6>
                            <h3 class="mb-0">{{ number_format($statistiques['echecs']) }}</h3>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section de filtrage -->
    <div class="filter-section">
        <form method="GET" id="filterForm">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-select" name="statut" id="statut">
                        <option value="">Tous les statuts</option>
                        <option value="planifiee" {{ request('statut') == 'planifiee' ? 'selected' : '' }}>Planifiée</option>
                        <option value="envoyee" {{ request('statut') == 'envoyee' ? 'selected' : '' }}>Envoyée</option>
                        <option value="echec" {{ request('statut') == 'echec' ? 'selected' : '' }}>Échec</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="niveau" class="form-label">Niveau</label>
                    <select class="form-select" name="niveau" id="niveau">
                        <option value="">Tous niveaux</option>
                        <option value="1" {{ request('niveau') == '1' ? 'selected' : '' }}>1er rappel</option>
                        <option value="2" {{ request('niveau') == '2' ? 'selected' : '' }}>2ème rappel</option>
                        <option value="3" {{ request('niveau') == '3' ? 'selected' : '' }}>Dernière relance</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" name="type" id="type">
                        <option value="">Tous types</option>
                        <option value="email" {{ request('type') == 'email' ? 'selected' : '' }}>Email</option>
                        <option value="sms" {{ request('type') == 'sms' ? 'selected' : '' }}>SMS</option>
                        <option value="courrier" {{ request('type') == 'courrier' ? 'selected' : '' }}>Courrier</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" name="search" id="search"
                           placeholder="Nom étudiant..." value="{{ request('search') }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>
                        Filtrer
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des relances -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Liste des Relances ({{ $relances->total() }} résultats)
            </h5>
        </div>
        <div class="card-body p-0">
            @if($relances->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Étudiant</th>
                                <th>Type</th>
                                <th>Niveau</th>
                                <th>Statut</th>
                                <th>Date d'envoi</th>
                                <th>Montant dette</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($relances as $relance)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $relance->etudiant->nom ?? 'N/A' }} {{ $relance->etudiant->prenoms ?? '' }}</div>
                                                <small class="text-muted">{{ $relance->etudiant->email ?? 'Pas d\'email' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{
                                            $relance->type === 'email' ? 'info' :
                                            ($relance->type === 'sms' ? 'warning' : 'secondary')
                                        }}">
                                            <i class="fas fa-{{
                                                $relance->type === 'email' ? 'envelope' :
                                                ($relance->type === 'sms' ? 'sms' : 'file-alt')
                                            }} me-1"></i>
                                            {{ $relance->type_formatte }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-niveau bg-{{ $relance->niveau == 1 ? 'success' : ($relance->niveau == 2 ? 'warning' : 'danger') }}">
                                            {{ $relance->niveau_formatte }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $relance->statut_class }}">
                                            <i class="fas fa-{{
                                                $relance->statut === 'planifiee' ? 'clock' :
                                                ($relance->statut === 'envoyee' ? 'check' : 'times')
                                            }} me-1"></i>
                                            {{ $relance->statut_formatte }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($relance->date_envoi)
                                            <div>{{ $relance->date_envoi->format('d/m/Y') }}</div>
                                            <small class="text-muted">{{ $relance->date_envoi->format('H:i') }}</small>
                                        @else
                                            <span class="text-muted">Non envoyée</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($relance->facture)
                                            <strong class="text-danger">{{ number_format($relance->facture->montant_total, 0, ',', ' ') }} FCFA</strong>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#modalVoirRelance"
                                                    onclick="voirRelance({{ $relance->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            @if($relance->statut === 'echec' || ($relance->statut === 'planifiee' && $relance->est_en_retard))
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                        onclick="renvoyerRelance({{ $relance->id }})">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            @endif

                                            @if($relance->type === 'email' && $relance->statut === 'envoyee')
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                        data-bs-toggle="tooltip" title="Voir statistiques">
                                                    <i class="fas fa-chart-line"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center p-3">
                    <div class="text-muted">
                        Affichage de {{ $relances->firstItem() }} à {{ $relances->lastItem() }}
                        sur {{ $relances->total() }} résultats
                    </div>
                    {{ $relances->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune relance trouvée</h5>
                    <p class="text-muted">Aucune relance ne correspond à vos critères de recherche.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPlanifierRelances">
                        <i class="fas fa-plus me-1"></i>
                        Planifier des relances
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Planifier Relances -->
<div class="modal fade" id="modalPlanifierRelances" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Planifier des Relances
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPlanifierRelances">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="critere_dette" class="form-label">Montant dette minimum</label>
                            <input type="number" class="form-control" id="critere_dette" name="critere_dette"
                                   value="50000" min="0" step="1000">
                        </div>
                        <div class="col-md-6">
                            <label for="critere_jours" class="form-label">Jours de retard minimum</label>
                            <input type="number" class="form-control" id="critere_jours" name="critere_jours"
                                   value="30" min="1" max="365">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="type_relance" class="form-label">Type de relance</label>
                            <select class="form-select" id="type_relance" name="type_relance">
                                <option value="auto">Automatique (par niveau)</option>
                                <option value="email">Email uniquement</option>
                                <option value="sms">SMS uniquement</option>
                                <option value="courrier">Courrier uniquement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="date_envoi" class="form-label">Date d'envoi prévue</label>
                            <input type="datetime-local" class="form-control" id="date_envoi" name="date_envoi"
                                   value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Aperçu des étudiants concernés</label>
                        <div id="apercu_etudiants" class="border rounded p-3 bg-light">
                            <div class="text-center text-muted">
                                <i class="fas fa-spinner fa-spin me-1"></i>
                                Chargement de l'aperçu...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-calendar-plus me-1"></i>
                        Planifier les Relances
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Configuration Templates -->
<div class="modal fade" id="modalConfigTemplates" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog me-2"></i>
                    Configuration des Templates
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="config-templates-content">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Voir Relance -->
<div class="modal fade" id="modalVoirRelance" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Détails de la Relance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="details-relance-content">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh des aperçus lors de changement de critères
    const critereDette = document.getElementById('critere_dette');
    const critereJours = document.getElementById('critere_jours');

    [critereDette, critereJours].forEach(element => {
        element.addEventListener('change', debounce(chargerApercuEtudiants, 500));
    });

    // Chargement initial de l'aperçu
    chargerApercuEtudiants();

    // Soumission du formulaire de planification
    document.getElementById('formPlanifierRelances').addEventListener('submit', function(e) {
        e.preventDefault();
        planifierRelances();
    });
});

function chargerApercuEtudiants() {
    const dette = document.getElementById('critere_dette').value;
    const jours = document.getElementById('critere_jours').value;
    const container = document.getElementById('apercu_etudiants');

    container.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Chargement...</div>';

    fetch(`{{ route('esbtp.comptabilite.relances.apercu') }}?dette=${dette}&jours=${jours}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h4 class="text-primary">${data.count}</h4>
                            <small class="text-muted">Étudiants concernés</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-warning">${data.total_dette} FCFA</h4>
                            <small class="text-muted">Dette totale</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-info">${data.moyenne_dette} FCFA</h4>
                            <small class="text-muted">Dette moyenne</small>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = '<div class="text-center text-danger">Erreur lors du chargement</div>';
            }
        })
        .catch(error => {
            container.innerHTML = '<div class="text-center text-danger">Erreur de connexion</div>';
        });
}

function planifierRelances() {
    const form = document.getElementById('formPlanifierRelances');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Planification...';
    submitBtn.disabled = true;

    fetch('{{ route("esbtp.comptabilite.relances.planifier") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalPlanifierRelances')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Erreur lors de la planification');
    })
    .finally(() => {
        submitBtn.innerHTML = '<i class="fas fa-calendar-plus me-1"></i>Planifier les Relances';
        submitBtn.disabled = false;
    });
}

function voirRelance(id) {
    const container = document.getElementById('details-relance-content');
    container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';

    fetch(`{{ route('esbtp.comptabilite.relances.show', '') }}/${id}`)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(error => {
            container.innerHTML = '<div class="text-center text-danger">Erreur lors du chargement</div>';
        });
}

function renvoyerRelance(id) {
    if (confirm('Êtes-vous sûr de vouloir renvoyer cette relance ?')) {
        fetch(`{{ route('esbtp.comptabilite.relances.renvoyer', '') }}/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.success ? 'success' : 'error', data.message);
            if (data.success) {
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => {
            showAlert('error', 'Erreur lors du renvoi');
        });
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endpush
