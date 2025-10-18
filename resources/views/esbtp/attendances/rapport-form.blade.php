@extends('layouts.app')

@section('title', 'Générer un rapport de présence')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .main-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .main-card-header {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05));
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .main-card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .main-card-subtitle {
        color: #6b7280;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .main-card-body {
        padding: 2rem;
    }

    .form-label-modern {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        display: block;
    }

    .form-control-modern {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0.625rem 1rem;
        font-size: 0.9375rem;
        transition: all 0.2s;
    }

    .form-control-modern:focus {
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        outline: none;
    }

    .info-card {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        border-radius: 12px;
        padding: 1.5rem;
        color: white;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        margin-bottom: 0.75rem;
        backdrop-filter: blur(10px);
    }

    .info-icon {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .btn-modern {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9375rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
    }

    .btn-modern.primary {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        color: white;
    }

    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.4);
    }

    .btn-modern.secondary {
        background: #f3f4f6;
        color: #374151;
    }

    .btn-modern.secondary:hover {
        background: #e5e7eb;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .main-card-body {
            padding: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .main-card-header {
            padding: 1rem;
        }

        .main-card-body {
            padding: 1rem;
        }

        .main-card-title {
            font-size: 1.125rem;
        }

        .btn-modern {
            width: 100%;
            justify-content: center;
        }

        .info-card {
            margin-top: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .main-card-title i {
            font-size: 1rem;
        }

        .form-label-modern {
            font-size: 0.8125rem;
        }

        .form-control-modern {
            font-size: 0.875rem;
            padding: 0.5rem 0.875rem;
        }

        .info-item {
            font-size: 0.875rem;
            padding: 0.625rem;
        }

        .info-icon {
            width: 32px;
            height: 32px;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
        <!-- Header -->
        <div class="dashboard-header mb-4">
            <div class="header-left">
                <h1><i class="fas fa-file-chart me-2"></i>Rapport de présence</h1>
                <p class="header-subtitle">Générez des rapports détaillés de présence par classe et période</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux présences
                </a>
            </div>
        </div>

        <!-- Alertes -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Formulaire -->
            <div class="col-lg-8">
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-cog"></i>
                            Configuration du rapport
                        </div>
                        <div class="main-card-subtitle">
                            Sélectionnez les critères pour générer votre rapport
                        </div>
                    </div>
                    <div class="main-card-body">
                        <form action="{{ route('esbtp.attendances.rapport') }}" method="POST">
                            @csrf

                            <!-- Classe -->
                            <div class="mb-4">
                                <label for="classe_id" class="form-label-modern">
                                    <i class="fas fa-users text-primary me-1"></i>Classe
                                </label>
                                <select name="classe_id" id="classe_id" class="form-control-modern form-select" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Période -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="date_debut" class="form-label-modern">
                                        <i class="fas fa-calendar-plus text-success me-1"></i>Date de début
                                    </label>
                                    <input type="date"
                                           name="date_debut"
                                           id="date_debut"
                                           class="form-control-modern"
                                           required
                                           value="{{ date('Y-m-01') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_fin" class="form-label-modern">
                                        <i class="fas fa-calendar-check text-danger me-1"></i>Date de fin
                                    </label>
                                    <input type="date"
                                           name="date_fin"
                                           id="date_fin"
                                           class="form-control-modern"
                                           required
                                           value="{{ date('Y-m-t') }}">
                                </div>
                            </div>

                            <!-- Boutons -->
                            <div class="d-flex gap-3 flex-wrap">
                                <button type="submit" class="btn-modern primary">
                                    <i class="fas fa-file-chart"></i>
                                    Générer le rapport
                                </button>
                                <a href="{{ route('esbtp.attendances.index') }}" class="btn-modern secondary">
                                    <i class="fas fa-times"></i>
                                    Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Informations -->
            <div class="col-lg-4">
                <div class="info-card">
                    <h5 class="mb-4 d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        Informations
                    </h5>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Taux de présence</strong>
                            <div style="font-size: 0.875rem; opacity: 0.9;">Pour chaque étudiant</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Statistiques détaillées</strong>
                            <div style="font-size: 0.875rem; opacity: 0.9;">Présences, absences, retards</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Stats globales</strong>
                            <div style="font-size: 0.875rem; opacity: 0.9;">Par classe et période</div>
                        </div>
                    </div>

                    <hr style="border-color: rgba(255,255,255,0.2); margin: 1.5rem 0;">

                    <h6 class="mb-3">Actions disponibles</h6>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Export PDF</strong>
                            <div style="font-size: 0.875rem; opacity: 0.9;">Téléchargement du rapport</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Envoi par email</strong>
                            <div style="font-size: 0.875rem; opacity: 0.9;">Partage du rapport</div>
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
        const dateDebut = document.getElementById('date_debut');
        const dateFin = document.getElementById('date_fin');

        // Validation de la date de fin
        dateFin.addEventListener('change', function() {
            if (this.value < dateDebut.value) {
                alert('La date de fin doit être postérieure à la date de début.');
                this.value = dateDebut.value;
            }
        });

        // Validation de la date de début
        dateDebut.addEventListener('change', function() {
            if (dateFin.value < this.value) {
                dateFin.value = this.value;
            }
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>
@endsection
