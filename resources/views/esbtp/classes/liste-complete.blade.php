@extends('layouts.app')

@section('title', 'Liste complète - ' . $classe->name . ' - ESBTP-yAKRO')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.export-buttons {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
}

@media print {
    .export-buttons, .no-print {
        display: none !important;
    }
    body {
        padding: 0;
        margin: 0;
    }
}

.students-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 12px;
}

.students-table th,
.students-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.students-table th {
    background-color: #f5f5f5;
    font-weight: bold;
    position: sticky;
    top: 0;
}

.students-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.students-table tr:hover {
    background-color: #f0f0f0;
}

.number-cell {
    width: 40px;
    text-align: center;
}

.text-center {
    text-align: center;
}

.summary-stats {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: var(--primary);
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9em;
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Boutons d'export -->
        <div class="export-buttons no-print">
            <a href="{{ route('esbtp.classes.liste-complete.pdf', $classe->id) }}" class="btn-acasi danger" title="Télécharger PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('esbtp.classes.liste-complete.excel', $classe->id) }}" class="btn-acasi success ml-2" title="Télécharger Excel">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <button onclick="window.print()" class="btn-acasi primary ml-2" title="Imprimer">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>

        <!-- En-tête -->
        <div class="text-center mb-4">
            <h1 style="font-size: 28px; margin-bottom: 10px;">ESBTP-yAKRO</h1>
            <h2 style="font-size: 22px; margin-bottom: 20px;">LISTE COMPLÈTE DES ÉTUDIANTS</h2>

            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 16px;">
                <div><strong>Classe :</strong> {{ $classe->name }}</div>
                <div><strong>Code :</strong> {{ $classe->code }}</div>
                <div><strong>Date d'impression :</strong> {{ date('d/m/Y H:i') }}</div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px;">
                <div><strong>Filière :</strong> {{ $classe->filiere->name ?? 'N/A' }}</div>
                <div><strong>Niveau :</strong> {{ $classe->niveau->name ?? 'N/A' }}</div>
                <div><strong>Année :</strong> {{ $anneeCourante->name ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Statistiques résumées -->
        <div class="summary-stats no-print">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">{{ $etudiants->count() }}</div>
                    <div class="stat-label">Total étudiants</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ $etudiants->where('sexe', 'M')->count() }}</div>
                    <div class="stat-label">Hommes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ $etudiants->where('sexe', 'F')->count() }}</div>
                    <div class="stat-label">Femmes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ $classe->places_totales ?? 'N/A' }}</div>
                    <div class="stat-label">Places totales</div>
                </div>
            </div>
        </div>

        <!-- Liste détaillée des étudiants -->
        <div>
            @if($etudiants->count() > 0)
                <table class="students-table">
                    <thead>
                        <tr>
                            <th class="number-cell">N°</th>
                            <th>Matricule</th>
                            <th>Nom et Prénoms</th>
                            <th>Sexe</th>
                            <th>Date de naissance</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Parent/Tuteur</th>
                            <th>Tél. Parent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($etudiants as $index => $etudiant)
                        <tr>
                            <td class="number-cell">{{ $index + 1 }}</td>
                            <td>{{ $etudiant->matricule ?? 'N/A' }}</td>
                            <td><strong>{{ $etudiant->nom }} {{ $etudiant->prenom }}</strong></td>
                            <td class="text-center">{{ $etudiant->sexe ?? 'N/A' }}</td>
                            <td>{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                            <td>{{ $etudiant->telephone ?? 'N/A' }}</td>
                            <td style="font-size: 11px;">{{ $etudiant->email ?? 'N/A' }}</td>
                            <td style="font-size: 11px;">{{ $etudiant->adresse ?? 'N/A' }}</td>
                            <td>{{ $etudiant->parent ? $etudiant->parent->nom . ' ' . $etudiant->parent->prenom : 'N/A' }}</td>
                            <td>{{ $etudiant->parent ? $etudiant->parent->telephone : 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Résumé en bas de page -->
                <div style="margin-top: 30px; display: flex; justify-content: space-between; page-break-inside: avoid;">
                    <div>
                        <h4>Résumé statistique :</h4>
                        <p><strong>Total étudiants :</strong> {{ $etudiants->count() }}</p>
                        <p><strong>Hommes :</strong> {{ $etudiants->where('sexe', 'M')->count() }} ({{ $etudiants->count() > 0 ? round(($etudiants->where('sexe', 'M')->count() / $etudiants->count()) * 100, 1) : 0 }}%)</p>
                        <p><strong>Femmes :</strong> {{ $etudiants->where('sexe', 'F')->count() }} ({{ $etudiants->count() > 0 ? round(($etudiants->where('sexe', 'F')->count() / $etudiants->count()) * 100, 1) : 0 }}%)</p>
                        <p><strong>Places disponibles :</strong> {{ ($classe->places_totales ?? 0) - $etudiants->count() }}</p>
                    </div>
                    <div style="text-align: right;">
                        <p><strong>Document généré le :</strong> {{ date('d/m/Y à H:i') }}</p>
                        <p><strong>Par :</strong> {{ auth()->user()->name }}</p>
                    </div>
                </div>
            @else
                <div class="text-center" style="padding: 40px;">
                    <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Aucun étudiant inscrit</h3>
                    <p style="color: #666;">Aucun étudiant n'est inscrit dans cette classe pour l'année {{ $anneeCourante->name ?? 'courante' }}.</p>
                </div>
            @endif
        </div>

        <!-- Navigation -->
        <div class="no-print mt-4 text-center">
            <a href="{{ route('esbtp.classes.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i> Retour aux classes
            </a>
        </div>
    </div>
</div>
@endsection