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
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.attendance-table th,
.attendance-table td {
    border: 1px solid #e0e0e0;
    padding: 12px 8px;
    text-align: left;
}

.attendance-table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
}

.attendance-table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

.attendance-table tbody tr:hover {
    background-color: #e3f2fd;
}

.checkbox-cell {
    width: 40px;
    text-align: center;
    font-size: 16px;
}

.number-cell {
    width: 60px;
    text-align: center;
    font-weight: 600;
    color: #667eea;
}

@media print {
    .modern-header {
        background: #667eea !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }

    .attendance-table th {
        background: #667eea !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
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

        <!-- En-tête moderne -->
        <div class="modern-header text-center mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="header-content">
                @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                    <div class="logo-container mb-3">
                        <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}"
                             style="max-height: 80px; max-width: 200px; filter: brightness(0) invert(1);" alt="Logo">
                    </div>
                @endif

                <h1 style="font-size: 28px; margin-bottom: 5px; font-weight: 700; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">{{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }}</h1>

                @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                <div style="font-size: 14px; margin-bottom: 20px; opacity: 0.9;">
                    @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                    @if($etablissement['telephone'] && $etablissement['adresse']) | @endif
                    @if($etablissement['telephone'])Tel: {{ $etablissement['telephone'] }}@endif
                    @if($etablissement['email'] && ($etablissement['adresse'] || $etablissement['telephone'])) | @endif
                    @if($etablissement['email'])Email: {{ $etablissement['email'] }}@endif
                </div>
                @endif

                <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 10px; backdrop-filter: blur(10px);">
                    <h2 style="font-size: 24px; margin-bottom: 15px; font-weight: 600;">FEUILLE D'APPEL</h2>

                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 2rem; align-items: center; margin-bottom: 15px;">
                        <div style="text-align: left;">
                            <div style="font-size: 18px; margin-bottom: 5px;"><strong>Classe :</strong> <span style="background: rgba(255,255,255,0.3); padding: 0.3rem 0.8rem; border-radius: 20px;">{{ $classe->name }}</span></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 16px;"><strong>Date :</strong> <span style="border-bottom: 2px solid rgba(255,255,255,0.7); padding: 0.2rem 2rem;">_____________</span></div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; font-size: 14px;">
                        <div><strong>Filière :</strong><br><span style="background: rgba(255,255,255,0.3); padding: 0.2rem 0.6rem; border-radius: 15px;">{{ $classe->filiere->name ?? 'Non renseigné' }}</span></div>
                        <div><strong>Niveau :</strong><br><span style="background: rgba(255,255,255,0.3); padding: 0.2rem 0.6rem; border-radius: 15px;">{{ $classe->niveau->name ?? 'Non renseigné' }}</span></div>
                        <div><strong>Année :</strong><br><span style="background: rgba(255,255,255,0.3); padding: 0.2rem 0.6rem; border-radius: 15px;">{{ $anneeCourante->name ?? 'Non renseigné' }}</span></div>
                    </div>
                </div>
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
                            <td>{{ $etudiant->matricule ?? 'Non renseigne' }}</td>
                            <td>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
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
                    <p style="font-size: 18px; color: #666;">Aucun etudiant inscrit dans cette classe pour l'annee {{ $anneeCourante->name ?? 'courante' }}.</p>
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