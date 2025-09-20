@extends('layouts.app')

@section('title', 'Liste complète - ' . $classe->name . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-users me-2"></i>Liste Complète des Étudiants</h1>
                <p class="header-subtitle">{{ $classe->name }} - {{ $anneeCourante->name ?? 'Année courante' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.classes.liste-complete.pdf', $classe->id) }}" class="btn-acasi danger">
                    <i class="fas fa-file-pdf"></i>Télécharger PDF
                </a>
                <a href="{{ route('esbtp.classes.liste-complete.excel', $classe->id) }}" class="btn-acasi success">
                    <i class="fas fa-file-excel"></i>Télécharger Excel
                </a>
                <button onclick="window.print()" class="btn-acasi primary">
                    <i class="fas fa-print"></i>Imprimer
                </button>
                <a href="{{ route('esbtp.classes.show', $classe->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Étudiants</div>
                <div class="kpi-value" style="color: #007bff; font-size: 2.5rem; font-weight: bold;">{{ $etudiants->count() }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    Inscrits dans la classe
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Filière</div>
                <div class="kpi-value" style="color: #007bff; font-size: 1.6rem; font-weight: bold;">{{ $classe->filiere->name ?? 'Non renseigné' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Spécialisation
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Niveau</div>
                <div class="kpi-value" style="color: #007bff; font-size: 1.6rem; font-weight: bold;">{{ $classe->niveau->name ?? 'Non renseigné' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-layer-group"></i>
                    Année d'études
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Répartition</div>
                <div class="kpi-value" style="color: #007bff; font-size: 1.5rem; font-weight: bold;">{{ $etudiants->where('genre', 'M')->count() }}H / {{ $etudiants->where('genre', 'F')->count() }}F</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-chart-pie"></i>
                    Hommes / Femmes
                </div>
            </div>
        </div>

        <!-- En-tête de l'établissement -->
        <div class="main-card">
            <div class="main-card-header" style="background: #007bff; color: white; border-radius: 15px 15px 0 0;">
                <div style="text-align: center; padding: 1rem;">
                    @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                        <div style="margin-bottom: 15px;">
                            <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}"
                                 style="max-height: 60px; max-width: 150px; filter: brightness(0) invert(1);" alt="Logo">
                        </div>
                    @endif

                    <h2 style="margin: 0 0 8px 0; font-size: 1.8rem; font-weight: 700;">{{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }}</h2>

                    @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 15px;">
                        @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                        @if($etablissement['telephone'] && $etablissement['adresse']) | @endif
                        @if($etablissement['telephone'])Tel: {{ $etablissement['telephone'] }}@endif
                        @if($etablissement['email'] && ($etablissement['adresse'] || $etablissement['telephone'])) | @endif
                        @if($etablissement['email'])Email: {{ $etablissement['email'] }}@endif
                    </div>
                    @endif

                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; backdrop-filter: blur(10px);">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.4rem; font-weight: 600;">LISTE COMPLÈTE DES ÉTUDIANTS</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.95rem;">
                            <div><strong>Classe:</strong> <span style="background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 12px;">{{ $classe->name }}</span></div>
                            <div><strong>Date:</strong> <span style="background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 12px;">{{ now()->format('d/m/Y') }}</span></div>
                            <div><strong>Code:</strong> <span style="background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 12px;">{{ $classe->code ?? 'N/A' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des étudiants -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste détaillée des étudiants
                </div>
                <div class="main-card-subtitle">{{ $etudiants->count() }} étudiant(s) inscrit(s) dans cette classe</div>
            </div>

            <div class="main-card-body">
                @if($etudiants->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="students-table">
                            <thead style="background: #007bff; color: white;">
                                <tr>
                                    <th width="60" class="text-center" style="color: white;">N°</th>
                                    <th style="color: white;">Matricule</th>
                                    <th style="color: white;">Nom et Prénoms</th>
                                    <th width="80" class="text-center" style="color: white;">Genre</th>
                                    <th style="color: white;">Date de naissance</th>
                                    <th style="color: white;">Téléphone</th>
                                    <th style="color: white;">Email</th>
                                    <th style="color: white;">Adresse</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($etudiants as $index => $etudiant)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge" style="background: #007bff; color: white;">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <code class="text-muted" style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">{{ $etudiant->matricule ?? 'Non renseigné' }}</code>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-placeholder me-2" style="width: 32px; height: 32px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                                                {{ substr($etudiant->nom, 0, 1) }}{{ substr($etudiant->prenoms, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                                                <small class="text-muted">Étudiant</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background: {{ $etudiant->genre == 'M' ? '#007bff' : '#e91e63' }}; color: white;">
                                            {{ $etudiant->genre == 'M' ? 'M' : 'F' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}
                                    </td>
                                    <td>
                                        @if($etudiant->telephone)
                                            <a href="tel:{{ $etudiant->telephone }}" class="text-decoration-none">
                                                <i class="fas fa-phone me-1"></i>{{ $etudiant->telephone }}
                                            </a>
                                        @else
                                            <span class="text-muted">Non renseigné</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($etudiant->email)
                                            <a href="mailto:{{ $etudiant->email }}" class="text-decoration-none">
                                                <i class="fas fa-envelope me-1"></i>{{ $etudiant->email }}
                                            </a>
                                        @else
                                            <span class="text-muted">Non renseigné</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $etudiant->adresse ?? 'Non renseigné' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Résumé et statistiques -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title" style="color: #007bff;">
                                        <i class="fas fa-chart-bar me-2"></i>Résumé statistique
                                    </h6>
                                    <div class="row text-center">
                                        <div class="col-3">
                                            <div class="fs-4 fw-bold" style="color: #007bff;">{{ $etudiants->count() }}</div>
                                            <small class="text-muted">Total</small>
                                        </div>
                                        <div class="col-3">
                                            <div class="fs-4 fw-bold" style="color: #007bff;">{{ $etudiants->where('genre', 'M')->count() }}</div>
                                            <small class="text-muted">Hommes</small>
                                        </div>
                                        <div class="col-3">
                                            <div class="fs-4 fw-bold" style="color: #e91e63;">{{ $etudiants->where('genre', 'F')->count() }}</div>
                                            <small class="text-muted">Femmes</small>
                                        </div>
                                        <div class="col-3">
                                            <div class="fs-4 fw-bold text-success">{{ ($classe->places_totales ?? 0) - $etudiants->count() }}</div>
                                            <small class="text-muted">Places libres</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title" style="color: #007bff;">
                                        <i class="fas fa-info-circle me-2"></i>Informations
                                    </h6>
                                    <div class="mb-2">
                                        <small class="text-muted">Document généré le :</small><br>
                                        <strong>{{ now()->format('d/m/Y à H:i') }}</strong>
                                    </div>
                                    <div>
                                        <small class="text-muted">Par :</small><br>
                                        <strong>{{ auth()->user()->name ?? 'Système' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun étudiant inscrit</h5>
                        <p class="text-muted">Aucun étudiant inscrit dans cette classe pour l'année {{ $anneeCourante->name ?? 'courante' }}.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Informations de génération -->
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Document généré le {{ now()->format('d/m/Y à H:i') }} par {{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }}
            </small>
        </div>
    </div>
</div>

<style>
@media print {
    .dashboard-header, .btn-acasi, .no-print {
        display: none !important;
    }

    .main-content {
        padding: 0 !important;
    }

    .main-card {
        box-shadow: none !important;
        border: none !important;
        margin-bottom: 20px !important;
    }

    .kpi-grid {
        display: none !important;
    }

    .table {
        font-size: 10px !important;
    }

    .table th,
    .table td {
        padding: 4px 3px !important;
    }

    .avatar-placeholder {
        display: none !important;
    }

    body {
        font-size: 11px !important;
    }

    .main-card-header[style*="background: #007bff"] {
        background: #007bff !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }

    .table thead {
        background: #007bff !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }

    .table thead th {
        color: white !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
}

.main-card-header[style*="background: #007bff"] {
    -webkit-print-color-adjust: exact;
    color-adjust: exact;
}
</style>
@endsection