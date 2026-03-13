@extends('layouts.app')

@section('title', 'Mes Notes')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour la page notes */
    .notes-container {
        --notes-primary: var(--primary);
        --notes-secondary: var(--secondary);
        --notes-surface: var(--surface);
        --notes-border: rgba(0, 0, 0, 0.08);
    }

    .filter-section {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--notes-border);
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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .stat-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--notes-border);
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

    .stat-card.success::before {
        background: linear-gradient(135deg, var(--success), #10b981);
    }

    .stat-card.info::before {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .stat-card.warning::before {
        background: linear-gradient(135deg, var(--warning), #f59e0b);
    }

    .stat-card.danger::before {
        background: linear-gradient(135deg, var(--danger), #f43f5e);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
    }

    .stat-card h6 {
        font-size: var(--text-sm);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
        margin-bottom: var(--space-sm);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1;
    }

    .notes-table-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--notes-border);
        margin-bottom: var(--space-xl);
    }

    .notes-table-card h5 {
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

    .note-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-weight: 700;
        font-size: var(--text-sm);
    }

    .note-badge.success {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }

    .note-badge.danger {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }

    .chart-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--notes-border);
    }

    .chart-card h5 {
        font-weight: 700;
        font-size: var(--text-lg);
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
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

        .table-modern thead th,
        .table-modern tbody td {
            padding: var(--space-sm);
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi notes-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-graduation-cap me-3"></i>
                        Mes Notes
                    </h1>
                    <p class="header-subtitle">
                        Consultez vos notes par matière et par période d'évaluation
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                        <i class="fas fa-calendar me-2"></i>
                        Année @php echo \App\Models\ESBTPAnneeUniversitaire::where('is_current',true)->value('name') ?? (date('Y').'-'.(date('Y')+1)); @endphp
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-section">
            <h5>
                <i class="fas fa-filter"></i>
                Filtres de recherche
            </h5>
            <form action="{{ route('mes-notes.index') }}" method="GET" class="row">
                <div class="col-md-5">
                    <label for="annee_universitaire_id" class="form-label">Année universitaire</label>
                    <select class="form-control" id="annee_universitaire_id" name="annee_universitaire_id">
                        @foreach($anneesUniversitaires as $annee)
                            <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="periode" class="form-label">Période</label>
                    <select class="form-control" id="periode" name="periode">
                        <option value="">Toutes les périodes</option>
                        <option value="semestre1" {{ $periode == 'semestre1' ? 'selected' : '' }}>Semestre 1</option>
                        <option value="semestre2" {{ $periode == 'semestre2' ? 'selected' : '' }}>Semestre 2</option>
                        <option value="trimestre1" {{ $periode == 'trimestre1' ? 'selected' : '' }}>Trimestre 1</option>
                        <option value="trimestre2" {{ $periode == 'trimestre2' ? 'selected' : '' }}>Trimestre 2</option>
                        <option value="trimestre3" {{ $periode == 'trimestre3' ? 'selected' : '' }}>Trimestre 3</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card success">
                <h6>Moyenne générale</h6>
                <div class="stat-value">{{ number_format($moyenneGenerale, 2) }}/20</div>
            </div>

            <div class="stat-card info">
                <h6>Matières validées</h6>
                <div class="stat-value">{{ $matieresValidees }} / {{ $totalMatieres }}</div>
            </div>

            <div class="stat-card warning">
                <h6>Meilleure note</h6>
                <div class="stat-value">{{ number_format($meilleureNote, 2) }}/20</div>
            </div>

            <div class="stat-card danger">
                <h6>Note la plus basse</h6>
                <div class="stat-value">{{ number_format($noteLaPlusBasse, 2) }}/20</div>
            </div>
        </div>

        <!-- Tableau des notes -->
        <div class="notes-table-card">
            <h5>
                <i class="fas fa-list-alt"></i>
                Liste de mes notes
            </h5>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Coefficient</th>
                            <th>Type d'évaluation</th>
                            <th>Date</th>
                            <th>Note</th>
                            <th>Moyenne de classe</th>
                            <th>Rang</th>
                            <th>Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notes as $note)
                        <tr>
                            <td>{{ $note->evaluation->matiere->libelle }}</td>
                            <td>{{ $note->evaluation->matiere->coefficient }}</td>
                            <td>{{ $note->evaluation->type }}</td>
                            <td>{{ $note->evaluation->date_evaluation->format('d/m/Y') }}</td>
                            <td>
                                <span class="note-badge {{ $note->valeur < 10 ? 'danger' : 'success' }}">
                                    {{ number_format($note->valeur, 2) }}/20
                                </span>
                            </td>
                            <td>{{ number_format($note->moyenne_classe, 2) }}/20</td>
                            <td>{{ $note->rang }}/{{ $note->total_etudiants }}</td>
                            <td>{{ $note->observations }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center" style="padding: var(--space-xl);">
                                <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-muted); margin-bottom: var(--space-md);"></i>
                                <p style="color: var(--text-secondary); margin: 0;">Aucune note disponible pour cette période.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $notes->links() }}
            </div>
        </div>

        <!-- Graphique d'évolution -->
        <div class="chart-card">
            <h5>
                <i class="fas fa-chart-line"></i>
                Évolution de mes notes
            </h5>
            <div class="chart-container">
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('evolutionChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($graphData['labels']) !!},
                datasets: [{
                    label: 'Évolution de mes notes',
                    data: {!! json_encode($graphData['values']) !!},
                    backgroundColor: 'rgba(1, 99, 47, 0.2)',
                    borderColor: 'rgba(1, 99, 47, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }, {
                    label: 'Moyenne de classe',
                    data: {!! json_encode($graphData['moyenneClasse']) !!},
                    backgroundColor: 'rgba(242, 148, 0, 0.2)',
                    borderColor: 'rgba(242, 148, 0, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 20
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
    });
</script>
@endpush
