@extends('layouts.app')

@section('title', 'Mes Absences')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour la page absences */
    .absences-container {
        --absences-primary: var(--primary);
        --absences-secondary: var(--secondary);
        --absences-surface: var(--surface);
        --absences-border: rgba(0, 0, 0, 0.08);
    }

    .filter-section {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--absences-border);
    }

    .filter-section h5 {
        font-weight: 700;
        font-size: var(--text-lg);
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .stat-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--absences-border);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .stat-card.primary::before {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .stat-card.success::before {
        background: linear-gradient(135deg, var(--success), #10b981);
    }

    .stat-card.danger::before {
        background: linear-gradient(135deg, var(--danger), #f43f5e);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
    }

    .stat-card-content {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-large);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .stat-card.primary .stat-icon {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
    }

    .stat-card.success .stat-icon {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }

    .stat-card.danger .stat-icon {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }

    .stat-info h6 {
        font-size: var(--text-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1;
        margin-bottom: var(--space-xs);
    }

    .stat-description {
        font-size: var(--text-xs);
        color: var(--text-secondary);
    }

    .absences-table-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--absences-border);
    }

    .absences-table-card h5 {
        font-weight: 700;
        font-size: var(--text-lg);
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .table-modern {
        width: 100%;
        margin-bottom: 0;
    }

    .table-modern thead th {
        background: rgba(var(--primary-rgb), 0.05);
        color: var(--text-primary);
        font-weight: 600;
        font-size: var(--text-sm);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: var(--space-md);
        border: none;
    }

    .table-modern tbody td {
        padding: var(--space-md);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        color: var(--text-primary);
        font-size: var(--text-sm);
    }

    .table-modern tbody tr:last-child td {
        border-bottom: none;
    }

    .table-modern tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.02);
    }

    .badge-status {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-xs);
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-status.success {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }

    .badge-status.danger {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }

    .info-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--absences-border);
        margin-bottom: var(--space-lg);
    }

    .info-card h5 {
        font-weight: 700;
        font-size: var(--text-base);
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .alert-modern {
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: flex-start;
        gap: var(--space-sm);
    }

    .alert-modern.info {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
    }

    .alert-modern.warning {
        background: rgba(var(--warning-rgb), 0.1);
        color: var(--warning);
    }

    .alert-modern i {
        flex-shrink: 0;
        margin-top: 2px;
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .student-header .d-flex {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: var(--space-md);
        }

        .student-header h1 {
            font-size: 1.5rem !important;
        }

        .student-header .header-subtitle {
            font-size: 0.875rem !important;
        }

        .student-header .text-end {
            text-align: left !important;
            width: 100%;
        }

        .student-header .badge {
            display: inline-block;
            width: auto;
        }

        .filter-section .row > div {
            margin-bottom: var(--space-sm);
        }

        .table-modern {
            font-size: var(--text-xs);
        }

        .stat-card-content {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi absences-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-calendar-times me-3"></i>
                        Mes Absences
                    </h1>
                    <p class="header-subtitle">
                        Consultez vos absences et justifiez-les
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                        <i class="fas fa-calendar me-2"></i>
                        Année {{ $anneeCourante->name ?? (date('Y').'-'.(date('Y')+1)) }}
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="margin: var(--space-lg) 0;">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="margin: var(--space-lg) 0;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <!-- Filtres -->
        <div class="filter-section">
            <h5>
                <i class="fas fa-filter"></i>
                Filtres de recherche
            </h5>
            <form action="{{ route('esbtp.mes-absences.index') }}" method="GET" class="row">
                <div class="col-md-3">
                    <label for="annee_universitaire_id" class="form-label">Année Universitaire</label>
                    <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-control">
                        @foreach($anneesUniversitaires as $annee)
                            <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="mois" class="form-label">Mois</label>
                    <select name="mois" id="mois" class="form-control">
                        <option value="">Tous les mois</option>
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $mois == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="justifie" class="form-label">Justification</label>
                    <select name="justifie" id="justifie" class="form-control">
                        <option value="">Toutes les absences</option>
                        <option value="1" {{ $justifie === '1' ? 'selected' : '' }}>Justifiées</option>
                        <option value="0" {{ $justifie === '0' ? 'selected' : '' }}>Non justifiées</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i>
                        Filtrer
                    </button>
                    <a href="{{ route('esbtp.mes-absences.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="stat-info">
                        <h6>Total des absences</h6>
                        <div class="stat-value">{{ $totalAbsences }}</div>
                        <div class="stat-description">Toutes les absences enregistrées</div>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h6>Absences justifiées</h6>
                        <div class="stat-value">{{ $absencesJustifiees }}</div>
                        <div class="stat-description">{{ $totalAbsences > 0 ? round(($absencesJustifiees / $totalAbsences) * 100, 2) : 0 }}% du total</div>
                    </div>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h6>Absences non justifiées</h6>
                        <div class="stat-value">{{ $absencesNonJustifiees }}</div>
                        <div class="stat-description">{{ $totalAbsences > 0 ? round(($absencesNonJustifiees / $totalAbsences) * 100, 2) : 0 }}% du total</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Liste des absences -->
                <div class="absences-table-card">
                    <h5>
                        <i class="fas fa-list"></i>
                        Liste de mes absences
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Matière</th>
                                    <th>Heure</th>
                                    <th>Justifiée</th>
                                    <th>Commentaire</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($absences as $absence)
                                    <tr>
                                        <td>{{ $absence->seance->date ? $absence->seance->date->format('d/m/Y') : 'N/A' }}</td>
                                        <td>{{ $absence->seance->matiere->nom ?? 'N/A' }}</td>
                                        <td>{{ $absence->seance->heure_debut ? $absence->seance->heure_debut->format('H:i') : 'N/A' }} - {{ $absence->seance->heure_fin ? $absence->seance->heure_fin->format('H:i') : 'N/A' }}</td>
                                        <td>
                                            @if($absence->justifie)
                                                <span class="badge-status success">Oui</span>
                                            @else
                                                <span class="badge-status danger">Non</span>
                                            @endif
                                        </td>
                                        <td>{{ $absence->commentaire ?? 'Aucun commentaire' }}</td>
                                        <td>
                                            @if(!$absence->justifie)
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#justifierModal{{ $absence->id }}">
                                                    <i class="fas fa-file-upload"></i>
                                                    Justifier
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center" style="padding: var(--space-xl);">
                                            <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-muted); margin-bottom: var(--space-md);"></i>
                                            <p style="color: var(--text-secondary); margin: 0;">Aucune absence trouvée.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $absences->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Graphique des absences -->
                <div class="info-card">
                    <h5>
                        <i class="fas fa-chart-bar"></i>
                        Évolution des absences
                    </h5>
                    <div class="chart-container">
                        <canvas id="absencesChart"></canvas>
                    </div>
                </div>

                <!-- Règlement des absences -->
                <div class="info-card">
                    <h5>
                        <i class="fas fa-info-circle"></i>
                        Règlement des absences
                    </h5>
                    <div class="alert-modern info">
                        <i class="fas fa-question-circle"></i>
                        <div>
                            <strong>Comment justifier une absence :</strong>
                            <ol style="margin: var(--space-xs) 0 0; padding-left: var(--space-lg);">
                                <li>Cliquez sur le bouton "Justifier" à côté de l'absence concernée.</li>
                                <li>Téléchargez un document justificatif (certificat médical, convocation administrative, etc.).</li>
                                <li>Ajoutez un commentaire expliquant la raison de votre absence.</li>
                                <li>Soumettez votre demande de justification.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="alert-modern warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Attention :</strong> Les justifications sont soumises à validation par l'administration.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour justifier les absences -->
@foreach($absences as $absence)
    @if(!$absence->justifie)
        <div class="modal fade" id="justifierModal{{ $absence->id }}" tabindex="-1" aria-labelledby="justifierModalLabel{{ $absence->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="justifierModalLabel{{ $absence->id }}">Justifier l'absence du {{ $absence->seance->date ? $absence->seance->date->format('d/m/Y') : 'N/A' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('esbtp.mes-absences.justify', $absence->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="attendance_id" value="{{ $absence->id }}">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="motif{{ $absence->id }}" class="form-label">Motif de l'absence</label>
                                <select name="motif" id="motif{{ $absence->id }}" class="form-control" required>
                                    <option value="">Sélectionnez un motif</option>
                                    <option value="Maladie">Maladie</option>
                                    <option value="Accident">Accident</option>
                                    <option value="Rendez-vous médical">Rendez-vous médical</option>
                                    <option value="Problème de transport">Problème de transport</option>
                                    <option value="Cas de force majeure">Cas de force majeure</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="commentaire{{ $absence->id }}" class="form-label">Détails / Commentaire</label>
                                <textarea name="commentaire" id="commentaire{{ $absence->id }}" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="document{{ $absence->id }}" class="form-label">Document justificatif (PDF, JPG, PNG)</label>
                                <input type="file" name="document" id="document{{ $absence->id }}" class="form-control" required>
                                <small class="form-text text-muted">Téléchargez un certificat médical ou tout autre document justifiant votre absence.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Soumettre la justification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('absencesChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($moisLabels),
                datasets: [{
                    label: 'Nombre d\'absences',
                    data: @json($absencesData),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
