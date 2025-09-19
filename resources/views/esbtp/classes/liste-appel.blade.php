@extends('layouts.app')

@section('title', 'Liste d\'appel - ' . $classe->name . ' - ESBTP-yAKRO')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.print-button {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
}

@media print {
    .print-button, .no-print {
        display: none !important;
    }
    body {
        padding: 0;
        margin: 0;
    }
}

.attendance-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.attendance-table th,
.attendance-table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
}

.attendance-table th {
    background-color: #f5f5f5;
    font-weight: bold;
}

.checkbox-cell {
    width: 30px;
    text-align: center;
}

.number-cell {
    width: 50px;
    text-align: center;
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Boutons d'action -->
        <div class="print-button no-print">
            <a href="{{ route('esbtp.classes.liste-appel.pdf', $classe->id) }}" class="btn-acasi danger" title="Télécharger PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <button onclick="window.print()" class="btn-acasi primary ml-2" title="Imprimer">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>

        <!-- En-tête -->
        <div class="text-center mb-4">
            <h1 style="font-size: 24px; margin-bottom: 10px;">ESBTP-yAKRO</h1>
            <h2 style="font-size: 20px; margin-bottom: 20px;">FEUILLE D'APPEL</h2>

            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 16px;">
                <div><strong>Classe :</strong> {{ $classe->name }}</div>
                <div><strong>Date :</strong> _______________</div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px;">
                <div><strong>Filière :</strong> {{ $classe->filiere->name ?? 'N/A' }}</div>
                <div><strong>Niveau :</strong> {{ $classe->niveau->name ?? 'N/A' }}</div>
                <div><strong>Année :</strong> {{ $anneeCourante->name ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Liste des étudiants -->
        <div>
            @if($etudiants->count() > 0)
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th class="number-cell">N°</th>
                            <th>Matricule</th>
                            <th>Nom et Prénoms</th>
                            <th class="checkbox-cell">Présent</th>
                            <th class="checkbox-cell">Absent</th>
                            <th style="width: 150px;">Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($etudiants as $index => $etudiant)
                        <tr>
                            <td class="number-cell">{{ $index + 1 }}</td>
                            <td>{{ $etudiant->matricule ?? 'N/A' }}</td>
                            <td>{{ $etudiant->nom }} {{ $etudiant->prenom }}</td>
                            <td class="checkbox-cell">☐</td>
                            <td class="checkbox-cell">☐</td>
                            <td></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Résumé -->
                <div style="margin-top: 30px; display: flex; justify-content: space-between;">
                    <div>
                        <p><strong>Total étudiants :</strong> {{ $etudiants->count() }}</p>
                        <p><strong>Présents :</strong> _____ / {{ $etudiants->count() }}</p>
                        <p><strong>Absents :</strong> _____ / {{ $etudiants->count() }}</p>
                    </div>
                    <div style="text-align: right;">
                        <p><strong>Enseignant :</strong> _____________________</p>
                        <p><strong>Signature :</strong></p>
                        <div style="height: 50px; border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                    </div>
                </div>
            @else
                <div class="text-center" style="padding: 40px;">
                    <p style="font-size: 18px; color: #666;">Aucun étudiant inscrit dans cette classe pour l'année {{ $anneeCourante->name ?? 'courante' }}.</p>
                </div>
            @endif
        </div>

        <!-- Pied de page -->
        <div class="no-print mt-4 text-center">
            <a href="{{ route('esbtp.classes.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i> Retour aux classes
            </a>
        </div>
    </div>
</div>
@endsection