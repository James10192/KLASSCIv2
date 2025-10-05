@extends('layouts.app')

@section('title', 'Rapport de Présence - ' . $classe->name)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .main-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card-header-moderne {
        background: #007bff;
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .card-header-moderne .header-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.125rem;
        font-weight: 600;
        color: white;
    }

    .card-header-moderne .header-title i {
        font-size: 1.25rem;
    }

    .card-body-moderne {
        padding: 1.5rem;
    }

    .table-responsive-moderne {
        overflow-x: auto;
    }

    .table-moderne {
        width: 100%;
        border-collapse: collapse;
    }

    .table-moderne thead {
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
    }

    .table-moderne thead th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .table-moderne tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s;
    }

    .table-moderne tbody tr:hover {
        background: #f9fafb;
    }

    .table-moderne tbody td {
        padding: 1rem 1.5rem;
        color: #6b7280;
        font-size: 0.9375rem;
    }

    .info-group {
        margin-bottom: 1rem;
    }

    .info-label {
        font-weight: 600;
        color: #374151;
        margin-right: 0.5rem;
    }

    .info-value {
        color: #6b7280;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-block;
        min-width: 30px;
        text-align: center;
    }

    .status-badge.present { background: #d1fae5; color: #065f46; }
    .status-badge.absent { background: #fee2e2; color: #991b1b; }
    .status-badge.late { background: #fef3c7; color: #92400e; }
    .status-badge.excuse { background: #dbeafe; color: #1e40af; }

    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        background: #e5e7eb;
        overflow: hidden;
        position: relative;
    }

    .progress-fill {
        height: 100%;
        transition: width 0.3s ease;
    }

    .progress-fill.high { background: #10b981; }
    .progress-fill.medium { background: #f59e0b; }
    .progress-fill.low { background: #ef4444; }

    .chart-container {
        position: relative;
        height: 350px;
        width: 100%;
    }

    @media print {
        .dashboard-header, .btn-acasi, .no-print {
            display: none !important;
        }

        .main-content {
            padding: 0 !important;
        }

        .main-card {
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
            margin-bottom: 20px !important;
        }

        .kpi-grid {
            display: none !important;
        }

        .chart-container {
            page-break-before: always;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-bar me-2"></i>Rapport de Présence</h1>
                <p class="header-subtitle">{{ $classe->name }} - Du {{ \Carbon\Carbon::parse($validatedData['date_debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($validatedData['date_fin'])->format('d/m/Y') }}</p>
            </div>
            <div class="header-actions">
                <form action="{{ route('esbtp.attendances.rapport-pdf') }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="classe_id" value="{{ $validatedData['classe_id'] }}">
                    <input type="hidden" name="date_debut" value="{{ $validatedData['date_debut'] }}">
                    <input type="hidden" name="date_fin" value="{{ $validatedData['date_fin'] }}">
                    <button type="submit" class="btn-acasi danger">
                        <i class="fas fa-file-pdf"></i>Télécharger PDF
                    </button>
                </form>
                <button onclick="window.print()" class="btn-acasi primary">
                    <i class="fas fa-print"></i>Imprimer
                </button>
                <a href="{{ route('esbtp.attendances.rapport-form') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Nouveau Rapport
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Présences</div>
                <div class="kpi-value" style="color: #10b981; font-size: 2rem; font-weight: bold;">{{ collect($statistiques)->sum('present') }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    <span>Étudiants présents</span>
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Absences</div>
                <div class="kpi-value" style="color: #ef4444; font-size: 2rem; font-weight: bold;">{{ collect($statistiques)->sum('absent') }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-times-circle"></i>
                    <span>Étudiants absents</span>
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Retards</div>
                <div class="kpi-value" style="color: #f59e0b; font-size: 2rem; font-weight: bold;">{{ collect($statistiques)->sum('retard') }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clock"></i>
                    <span>Étudiants en retard</span>
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Excusés</div>
                <div class="kpi-value" style="color: #3b82f6; font-size: 2rem; font-weight: bold;">{{ collect($statistiques)->sum('excuse') }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-file-alt"></i>
                    <span>Absences justifiées</span>
                </div>
            </div>
        </div>

        <!-- Graphique statistiques -->
        <div class="main-card no-print" style="margin-bottom: 2rem;">
            <div class="card-header-moderne">
                <div class="header-title">
                    <i class="fas fa-chart-pie"></i>
                    <span>Répartition Globale</span>
                </div>
            </div>
            <div class="card-body-moderne">
                <div class="chart-container" style="position: relative; height: 350px;">
                    <canvas id="presenceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tableau détaillé par étudiant -->
        <div class="main-card">
            <div class="card-header-moderne">
                <div class="header-title">
                    <i class="fas fa-users"></i>
                    <span>Détail par Étudiant</span>
                </div>
            </div>
            <div class="card-body-moderne" style="padding: 0;">
                <div class="table-responsive-moderne">
                    <table class="table-moderne attendance-table">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Étudiant</th>
                                <th class="text-center" style="width: 12%;">Présences</th>
                                <th class="text-center" style="width: 12%;">Absences</th>
                                <th class="text-center" style="width: 12%;">Retards</th>
                                <th class="text-center" style="width: 12%;">Excusés</th>
                                <th style="width: 17%;">Taux de Présence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statistiques as $stat)
                                <tr>
                                    <td>
                                        <div style="font-weight: 500; color: #111827;">{{ $stat['etudiant']->nom_complet }}</div>
                                        @if($stat['etudiant']->matricule)
                                            <div style="font-size: 0.875rem; color: #6b7280;">{{ $stat['etudiant']->matricule }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge present">{{ $stat['present'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge absent">{{ $stat['absent'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge late">{{ $stat['retard'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge excuse">{{ $stat['excuse'] }}</span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div class="progress-bar-custom" style="flex: 1;">
                                                <div class="progress-fill {{ $stat['taux_presence'] > 75 ? 'high' : ($stat['taux_presence'] > 50 ? 'medium' : 'low') }}"
                                                     style="width: {{ $stat['taux_presence'] }}%"></div>
                                            </div>
                                            <span style="font-weight: 500; color: #374151; min-width: 45px;">{{ $stat['taux_presence'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 3rem; color: #6b7280;">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        Aucune donnée disponible pour cette période
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Informations de période -->
        <div class="main-card" style="margin-top: 2rem;">
            <div class="card-header-moderne">
                <div class="header-title">
                    <i class="fas fa-info-circle"></i>
                    <span>Informations du Rapport</span>
                </div>
            </div>
            <div class="card-body-moderne">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-group">
                            <span class="info-label">Classe :</span>
                            <span class="info-value">{{ $classe->name }}</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Période :</span>
                            <span class="info-value">Du {{ \Carbon\Carbon::parse($validatedData['date_debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($validatedData['date_fin'])->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <span class="info-label">Nombre d'étudiants :</span>
                            <span class="info-value">{{ count($statistiques) }}</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Total d'enregistrements :</span>
                            <span class="info-value">{{ collect($statistiques)->sum('present') + collect($statistiques)->sum('absent') + collect($statistiques)->sum('retard') + collect($statistiques)->sum('excuse') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('presenceChart').getContext('2d');

        // Calculer les totaux
        const presents = {{ collect($statistiques)->sum('present') }};
        const absents = {{ collect($statistiques)->sum('absent') }};
        const retards = {{ collect($statistiques)->sum('retard') }};
        const excuses = {{ collect($statistiques)->sum('excuse') }};

        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Présents', 'Absents', 'Retards', 'Excusés'],
                datasets: [{
                    data: [presents, absents, retards, excuses],
                    backgroundColor: [
                        '#10b981',
                        '#ef4444',
                        '#f59e0b',
                        '#3b82f6'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14,
                                family: "'Inter', sans-serif"
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    title: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const total = presents + absents + retards + excuses;
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    });
</script>
@endpush
