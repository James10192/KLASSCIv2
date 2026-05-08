@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
<style>
    /* Styles du bulletin basés sur Layout pdf/Bulletin */
    body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        color: #000;
        font-size: 12px;
    }

    .bulletin-container {
        background-color: white;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        padding: 20px 30px;
        border-radius: 0;
        max-width: 1000px;
        margin: 0 auto;
        border: 2px solid #000;
    }

    /* En-tête */
    .bulletin-header {
        margin-bottom: 15px;
    }

    .republic-title {
        font-weight: bold;
        font-size: 14px;
    }

    .motto {
        font-style: italic;
        font-size: 12px;
    }

    .ministry-title {
        font-size: 12px;
    }

    .bulletin-title {
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
    }

    .edition-date, .cycle-info, .diploma-info, .diploma-code {
        font-size: 12px;
    }

    .academic-year {
        font-size: 12px;
        font-weight: bold;
        margin-top: 20px;
    }

    /* Logo ESBTP */
    .esbtp-logo {
        max-width: 150px;
        margin: 0 auto;
        text-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 120px;
    }

    .esbtp-logo img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
        border: none;
        box-shadow: none;
        object-fit: contain;
    }

    /* Informations de l'école */
    .school-info {
        margin-top: 10px;
    }

    .school-name {
        font-size: 14px;
        color: #0A6B31;
        font-weight: bold;
    }

    .school-contact {
        font-size: 10px;
        color: #333;
    }

    /* Tableaux d'informations de l'étudiant */
    .student-table {
        font-size: 12px;
        margin-bottom: 10px;
        border: 1px solid #000;
    }

    .student-table td {
        padding: 4px 8px;
        border: 1px solid #000;
    }

    .student-info-container {
        border: 2px solid #000;
    }

    /* Tableaux des notes */
    .table {
        font-size: 12px;
        border: 1px solid #000;
        margin-bottom: 10px;
    }

    .table th, .table td {
        padding: 4px 8px;
        border: 1px solid #000;
        vertical-align: middle;
    }

    .table-header th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .section-header {
        background-color: #000;
        color: white;
        font-weight: bold;
        text-align: center;
    }

    .total-row {
        background-color: #f2f2f2;
        font-weight: bold;
        border-bottom: 2px solid #000;
    }

    /* Résultats */
    .result-value {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .result-cadre {
        border: 1px solid #000;
        padding: 0 20px;
    }

    /* Décision et signature */
    .decision-section {
        margin-top: 30px;
        border-top: 1px solid #000;
        padding-top: 20px;
    }

    .decision-title, .signature-title {
        font-weight: bold;
        font-size: 12px;
        margin-bottom: 30px;
    }

    .decision-result {
        font-weight: bold;
        font-size: 16px;
        margin-top: 20px;
        font-style: italic;
    }

    .signature-box {
        height: 60px;
        width: 150px;
        margin: 0 auto;
        border-bottom: 1px solid #000;
    }

    /* Pied de page */
    .bulletin-footer {
        margin-top: 30px;
        font-size: 10px;
        text-align: center;
        border-top: 1px solid #000;
        padding-top: 5px;
    }

    /* Boutons d'action */
    .action-buttons {
        margin-bottom: 20px;
        text-align: center;
    }

    .action-buttons .btn {
        margin: 0 10px;
    }

    /* Impression */
    @media print {
        .action-buttons {
            display: none;
        }
        
        .bulletin-container {
            box-shadow: none;
            padding: 0;
            max-width: 100%;
            border: 1px solid #000;
        }
        
        body {
            width: 27.7cm;
            height: 29.7cm;
            margin: 0cm;
            padding: 0;
            background: white;
            color: black;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Boutons d'action -->
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <button type="button" class="btn btn-outline-success" onclick="previewPDF()" title="Aperçu PDF dans un nouvel onglet">
                    <i class="fas fa-eye"></i> Aperçu PDF
                </button>
                <button type="button" class="btn btn-success" onclick="downloadPDF()">
                    <i class="fas fa-download"></i> Télécharger le PDF
                </button>
                <button type="button" class="btn btn-info" onclick="previewPDF()" title="Imprimer le vrai PDF (s'ouvre dans un nouvel onglet)">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>

            <!-- Prévisualisation du bulletin -->
            <div class="bulletin-container" id="bulletin-preview">
                <!-- En-tête officiel -->
                <header class="bulletin-header">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="republic-title">RÉPUBLIQUE DE CÔTE D'IVOIRE</div>
                            <div class="motto">Union - Travail - Progrès</div>
                            <div class="ministry-title">MINISTÈRE DE L'ENSEIGNEMENT SUPÉRIEUR ET DE LA RECHERCHE SCIENTIFIQUE</div>
                        </div>
                        <div class="col-md-6 text-center">
                            <div class="esbtp-logo">
                                @if($logoBase64)
                                    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo {{ \App\Helpers\SettingsHelper::get('school_acronym', config('app.name')) }}">
                                @endif
                            </div>
                            <div class="school-info">
                                <div class="school-name">ÉCOLE SPÉCIALE DU BÂTIMENT ET DES TRAVAUX PUBLICS</div>
                                <div class="school-contact">Yamoussoukro - Tél: 27 30 64 95 15 - Email: esbtp@esbtp.ci</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <div class="bulletin-title">BULLETIN DE NOTES</div>
                            <div class="edition-date">Édition du {{ date('d/m/Y') }}</div>
                            <div class="cycle-info">Cycle: {{ $classe->niveau->nom ?? 'Non défini' }}</div>
                            <div class="diploma-info">Diplôme: {{ $classe->filiere->nom ?? 'Non défini' }}</div>
                            <div class="diploma-code">Code: {{ $classe->code ?? 'Non défini' }}</div>
                        </div>
                    </div>
                    <div class="academic-year text-center">
                        ANNÉE UNIVERSITAIRE {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}
                    </div>
                </header>

                <!-- Informations de l'étudiant -->
                <div class="student-info-container mb-3">
                    <table class="table student-table">
                        <tr>
                            <td class="fw-bold" style="width: 15%;">Nom et Prénoms:</td>
                            <td style="width: 35%;">{{ $etudiant->nom }} {{ $etudiant->prenom }}</td>
                            <td class="fw-bold" style="width: 15%;">Date de naissance:</td>
                            <td style="width: 35%;">{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non définie' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Lieu de naissance:</td>
                            <td>{{ $etudiant->lieu_naissance ?? 'Non défini' }}</td>
                            <td class="fw-bold">N° Matricule:</td>
                            <td>{{ $etudiant->matricule }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Département:</td>
                            <td>{{ $classe->filiere->nom ?? 'Non défini' }}</td>
                            <td class="fw-bold">Niveau:</td>
                            <td>{{ $classe->niveau->nom ?? 'Non défini' }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Tableau des notes -->
                <table class="table">
                    <thead class="table-header">
                        <tr>
                            <th style="width: 20%;">UNITÉS D'ENSEIGNEMENT</th>
                            <th style="width: 15%;">ÉVALUATIONS</th>
                            <th style="width: 10%;">COEFF</th>
                            <th style="width: 10%;">NOTE/20</th>
                            <th style="width: 10%;">MOYENNE/20</th>
                            <th style="width: 10%;">CRÉDITS</th>
                            <th style="width: 10%;">CRÉDITS ACQUIS</th>
                            <th style="width: 15%;">OBSERVATIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalCredits = 0; $totalCreditsAcquis = 0; $totalMoyenneGenerale = 0; $totalCoeff = 0; @endphp
                        
                        @foreach($matieresByFiliere as $filiere => $matieres)
                            <tr class="section-header">
                                <td colspan="8">{{ $filiere }}</td>
                            </tr>
                            
                            @foreach($matieres as $matiere)
                                @php
                                    $moyenneMatiere = $moyennesMatiere[$matiere->id] ?? 0;
                                    $credits = $matiere->credits ?? 0;
                                    $creditsAcquis = $moyenneMatiere >= 10 ? $credits : 0;
                                    
                                    $totalCredits += $credits;
                                    $totalCreditsAcquis += $creditsAcquis;
                                    $totalMoyenneGenerale += $moyenneMatiere * $credits;
                                    $totalCoeff += $credits;
                                @endphp
                                
                                @if(isset($evaluationsParMatiere[$matiere->id]) && count($evaluationsParMatiere[$matiere->id]) > 0)
                                    @foreach($evaluationsParMatiere[$matiere->id] as $index => $evaluation)
                                        <tr>
                                            @if($index === 0)
                                                <td rowspan="{{ count($evaluationsParMatiere[$matiere->id]) }}">{{ $matiere->name }}</td>
                                            @endif
                                            <td>{{ $evaluation->titre }}</td>
                                            <td class="text-center">{{ $evaluation->coefficient }}</td>
                                            <td class="text-center">{{ isset($notesParEvaluation[$evaluation->id]) ? number_format($notesParEvaluation[$evaluation->id], 2) : '-' }}</td>
                                            @if($index === 0)
                                                <td rowspan="{{ count($evaluationsParMatiere[$matiere->id]) }}" class="text-center result-value">{{ number_format($moyenneMatiere, 2) }}</td>
                                                <td rowspan="{{ count($evaluationsParMatiere[$matiere->id]) }}" class="text-center">{{ $credits }}</td>
                                                <td rowspan="{{ count($evaluationsParMatiere[$matiere->id]) }}" class="text-center">{{ $creditsAcquis }}</td>
                                                <td rowspan="{{ count($evaluationsParMatiere[$matiere->id]) }}" class="text-center">
                                                    @include('esbtp.bulletins.partials.appreciation', ['moyenne' => $moyenneMatiere])
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>{{ $matiere->name }}</td>
                                        <td>-</td>
                                        <td class="text-center">-</td>
                                        <td class="text-center">-</td>
                                        <td class="text-center result-value">{{ number_format($moyenneMatiere, 2) }}</td>
                                        <td class="text-center">{{ $credits }}</td>
                                        <td class="text-center">{{ $creditsAcquis }}</td>
                                        <td class="text-center">
                                            @include('esbtp.bulletins.partials.appreciation', ['moyenne' => $moyenneMatiere])
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endforeach

                        <!-- Ligne de total -->
                        <tr class="total-row">
                            <td colspan="5" class="text-center"><strong>TOTAL</strong></td>
                            <td class="text-center"><strong>{{ $totalCredits }}</strong></td>
                            <td class="text-center"><strong>{{ $totalCreditsAcquis }}</strong></td>
                            <td class="text-center">
                                @php $moyenneGenerale = $totalCoeff > 0 ? $totalMoyenneGenerale / $totalCoeff : 0; @endphp
                                <strong>{{ number_format($moyenneGenerale, 2) }}/20</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Résumé des résultats -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="result-cadre">
                            <table class="table">
                                <tr>
                                    <td class="fw-bold">Moyenne générale:</td>
                                    <td class="result-value">{{ number_format($moyenneGenerale, 2) }}/20</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Crédits acquis:</td>
                                    <td class="result-value">{{ $totalCreditsAcquis }}/{{ $totalCredits }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Rang:</td>
                                    <td class="result-value">-/{{ $totalEtudiants ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Mention:</td>
                                    <td class="result-value">
                                        @include('esbtp.bulletins.partials.appreciation', ['moyenne' => $moyenneGenerale])
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="decision-section">
                            <div class="decision-title">DÉCISION DU JURY:</div>
                            <div class="decision-result">
                                {{ $moyenneGenerale >= 10 ? 'ADMIS(E)' : 'AJOURNÉ(E)' }}
                            </div>
                            <div class="mt-4">
                                @php
                                    $directorTitle = \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
                                    $directorName = \App\Helpers\SettingsHelper::get('director_name', '');
                                @endphp
                                <div class="signature-title">Fait à Yamoussoukro, le {{ date('d/m/Y') }}</div>
                                <div class="text-center">
                                    <div>{{ $directorTitle }}</div>
                                    <div class="signature-box"></div>
                                    @if($directorName)
                                        <div class="mt-2 fw-bold">{{ $directorName }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observations -->
                <div class="mt-4">
                    <div class="fw-bold">OBSERVATIONS:</div>
                    <div style="min-height: 60px; border: 1px solid #000; padding: 10px;">
                        @if($moyenneGenerale >= 16)
                            Excellent travail. L'étudiant a fait preuve d'excellence tout au long du semestre.
                        @elseif($moyenneGenerale >= 14)
                            Très bon travail. L'étudiant a fait preuve de sérieux et de régularité.
                        @elseif($moyenneGenerale >= 12)
                            Bon travail. L'étudiant doit maintenir ses efforts.
                        @elseif($moyenneGenerale >= 10)
                            Travail satisfaisant. L'étudiant doit redoubler d'efforts.
                        @else
                            Travail insuffisant. L'étudiant doit considérablement améliorer ses résultats.
                        @endif
                    </div>
                </div>

                <!-- Pied de page -->
                <footer class="bulletin-footer">
                    <div>{{ \App\Helpers\SettingsHelper::get('school_name', config('app.name')) }}</div>
                    <div>{{ \App\Helpers\SettingsHelper::get('school_address', '') }}@if(\App\Helpers\SettingsHelper::get('school_phone')) - Tél: {{ \App\Helpers\SettingsHelper::get('school_phone') }}@endif@if(\App\Helpers\SettingsHelper::get('school_website')) - {{ \App\Helpers\SettingsHelper::get('school_website') }}@endif</div>
                </footer>
            </div>
        </div>
    </div>
</div>

<script>
function downloadPDF() {
    @if(isset($etudiant) && isset($classe) && isset($anneeUniversitaire))
        window.open("{{ route('esbtp.bulletins.pdf-params', [
            'bulletin' => $etudiant->id,
            'classe_id' => $classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $anneeUniversitaire->id
        ]) }}", '_blank');
    @else
        alert('Erreur: Impossible de générer le PDF. Données manquantes.');
    @endif
}
function previewPDF() {
    @if(isset($etudiant) && isset($classe) && isset($anneeUniversitaire))
        window.open("{{ route('esbtp.bulletins.pdf-params-preview', [
            'bulletin' => $etudiant->id,
            'classe_id' => $classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $anneeUniversitaire->id
        ]) }}", '_blank');
    @else
        alert('Erreur: Impossible d\'ouvrir l\'aperçu PDF. Données manquantes.');
    @endif
}
</script>
@endsection
