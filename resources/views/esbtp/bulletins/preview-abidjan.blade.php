@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f8fafc;
        color: #111827;
        font-size: 12px;
    }
    .bulletin-container {
        background-color: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        padding: 20px 24px;
        border-radius: 16px;
        max-width: 1000px;
        margin: 0 auto;
        border: 1px solid #e5e7eb;
    }
    .action-buttons {
        margin-bottom: 20px;
        text-align: center;
    }
    .action-buttons .btn {
        margin: 0 10px;
    }
    .top-entete {
        text-align: center;
        font-size: 11px;
        color: #1f2937;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 6px;
        margin-bottom: 12px;
    }
    .header-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 14px;
    }
    .header-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .header-table td {
        border: none;
        padding: 6px;
        vertical-align: middle;
    }
    .header-logo {
        width: 33%;
    }
    .header-info {
        width: 67%;
        text-align: right;
    }
    .header-logo img {
        width: 90px;
        height: 90px;
        object-fit: contain;
        border-radius: 10px;
    }
    .school-name {
        font-weight: bold;
        font-size: 14px;
        color: #0f5132;
        text-transform: uppercase;
    }
    .school-contact {
        font-size: 10px;
        color: #4b5563;
    }
    .bulletin-title {
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
        margin-top: 6px;
    }
    .bulletin-period {
        font-size: 10px;
        color: #1f2937;
        margin-top: 2px;
    }
    .academic-year {
        font-size: 10px;
        font-weight: bold;
        color: #1f2937;
        margin-top: 6px;
    }
    .student-info-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 12px;
        background: #ffffff;
    }
    .student-info-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .student-info-table td {
        border: none;
        padding: 6px;
        vertical-align: top;
    }
    .student-photo {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 2px solid #0f5132;
        object-fit: cover;
        display: block;
        margin: 0 auto;
    }
    .student-avatar {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 2px solid #0f5132;
        background: #eef2f7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 28px;
        color: #475569;
        margin: 0 auto;
    }
    .student-matricule {
        text-align: center;
        font-weight: bold;
        font-size: 10px;
        margin-top: 6px;
    }
    .table {
        font-size: 12px;
        border: 1px solid #d1d5db;
        margin-bottom: 10px;
    }
    .table th, .table td {
        padding: 4px 8px;
        border: 1px solid #d1d5db;
        vertical-align: middle;
    }
    .table-header th {
        background-color: #f3f4f6;
        font-weight: bold;
    }
    .section-header {
        background-color: #0f5132;
        color: white;
        font-weight: bold;
        text-align: center;
    }
    .total-row {
        background-color: #f3f4f6;
        font-weight: bold;
        border-bottom: 2px solid #d1d5db;
    }
    .result-value {
        background-color: #f3f4f6;
        font-weight: bold;
        text-align: center;
    }
    .result-cadre {
        border: 1px solid #d1d5db;
        padding: 0 20px;
        border-radius: 10px;
        background: #ffffff;
    }
    .decision-section {
        margin-top: 30px;
        border-top: 1px solid #d1d5db;
        padding-top: 20px;
    }
    .decision-title, .signature-title {
        font-weight: bold;
        font-size: 12px;
        margin-bottom: 20px;
    }
    .decision-result {
        font-weight: bold;
        font-size: 16px;
        margin-top: 20px;
        font-style: italic;
    }
    .signature-box {
        height: 60px;
        width: 180px;
        margin: 0 auto;
        border-bottom: 1px solid #111827;
    }
    .bulletin-footer {
        margin-top: 30px;
        font-size: 10px;
        text-align: center;
        border-top: 1px solid #e5e7eb;
        padding-top: 5px;
        color: #6b7280;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <button type="button" class="btn btn-success" onclick="downloadPDF()">
                    <i class="fas fa-download"></i> Telecharger le PDF
                </button>
                <button type="button" class="btn btn-info" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>

            <div class="bulletin-container" id="bulletin-preview">
                <div class="top-entete">
                    <div><strong>{{ \App\Helpers\SettingsHelper::get('bulletin_ministry_text', "Ministere de l'Enseignement Superieur") }}</strong></div>
                    <div>{{ \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union - Travail - Progres') }}</div>
                </div>

                @php
                    $anneeAffichee = $bulletin && $bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire : $anneeUniversitaire;
                @endphp
                <div class="header-card">
                    <table class="header-table">
                        <tr>
                            <td class="header-logo">
                                @if($logoBase64)
                                    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                                @endif
                            </td>
                            <td class="header-info">
                                <div class="school-name">{{ $config['school_name'] ?? 'E.S.B.T.P' }}</div>
                                <div class="school-contact">
                                    {{ $config['school_address'] ?? '' }} • Tel: {{ $config['school_phone'] ?? '' }} • {{ $config['school_email'] ?? '' }}
                                </div>
                                <div class="bulletin-title">BULLETIN DE NOTES</div>
                                <div class="bulletin-period">Edition du {{ date('d/m/Y') }}</div>
                                <div class="academic-year">ANNEE UNIVERSITAIRE {{ $anneeAffichee->annee_debut }}-{{ $anneeAffichee->annee_fin }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                @php
                    $prenom = $etudiant->prenoms ?? $etudiant->prenom ?? '';
                    $initials = strtoupper(substr($etudiant->nom ?? 'E', 0, 1) . substr($prenom ?: 'T', 0, 1));
                    $photoUrl = $etudiant->photo_url;
                @endphp
                <div class="student-info-card">
                    <table class="student-info-table">
                        <tr>
                            <td style="width: 140px; text-align: center;">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}" class="student-photo" alt="Photo">
                                @else
                                    <div class="student-avatar">{{ $initials }}</div>
                                @endif
                                <div class="student-matricule">{{ $etudiant->matricule }}</div>
                            </td>
                            <td>
                                <div><strong>Nom et Prenoms:</strong> {{ $etudiant->nom }} {{ $etudiant->prenom }}</div>
                                <div><strong>Date de naissance:</strong> {{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non definie' }}</div>
                                <div><strong>Lieu de naissance:</strong> {{ $etudiant->lieu_naissance ?? 'Non defini' }}</div>
                            </td>
                            <td>
                                <div><strong>N° Matricule:</strong> {{ $etudiant->matricule }}</div>
                                <div><strong>Departement:</strong> {{ $classe->filiere->nom ?? 'Non defini' }}</div>
                                <div><strong>Niveau:</strong> {{ $classe->niveau->nom ?? 'Non defini' }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="table">
                    <thead class="table-header">
                        <tr>
                            <th style="width: 20%;">UNITES D'ENSEIGNEMENT</th>
                            <th style="width: 15%;">EVALUATIONS</th>
                            <th style="width: 10%;">COEFF</th>
                            <th style="width: 10%;">NOTE/20</th>
                            <th style="width: 10%;">MOYENNE/20</th>
                            <th style="width: 10%;">CREDITS</th>
                            <th style="width: 10%;">CREDITS ACQUIS</th>
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

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="result-cadre">
                            <table class="table">
                                <tr>
                                    <td class="fw-bold">Moyenne generale:</td>
                                    <td class="result-value">{{ number_format($moyenneGenerale, 2) }}/20</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Credits acquis:</td>
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
                        @php
                            $directorTitle = \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
                            $directorName = \App\Helpers\SettingsHelper::get('director_name', '');
                        @endphp
                        <div class="decision-section">
                            <div class="decision-title">DECISION DU JURY:</div>
                            <div class="decision-result">
                                {{ $moyenneGenerale >= 10 ? 'ADMIS(E)' : 'AJOURNE(E)' }}
                            </div>
                            <div class="mt-4">
                                <div class="signature-title">Fait a {{ $config['school_city'] ?? 'Abidjan' }}, le {{ date('d/m/Y') }}</div>
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

                <div class="mt-4">
                    <div class="fw-bold">OBSERVATIONS:</div>
                    <div style="min-height: 60px; border: 1px solid #d1d5db; padding: 10px; border-radius: 10px; background: #f9fafb;">
                        @if($moyenneGenerale >= 16)
                            Excellent travail. L'etudiant a fait preuve d'excellence tout au long du semestre.
                        @elseif($moyenneGenerale >= 14)
                            Tres bon travail. L'etudiant a fait preuve de serieux et de regularite.
                        @elseif($moyenneGenerale >= 12)
                            Bon travail. L'etudiant doit maintenir ses efforts.
                        @elseif($moyenneGenerale >= 10)
                            Travail satisfaisant. L'etudiant doit redoubler d'efforts.
                        @else
                            Travail insuffisant. L'etudiant doit considerablement ameliorer ses resultats.
                        @endif
                    </div>
                </div>

                <footer class="bulletin-footer">
                    <div>{{ $config['school_name'] ?? 'E.S.B.T.P' }} - {{ $config['school_city'] ?? 'Abidjan' }}, {{ $config['school_country'] ?? "Cote d'Ivoire" }}</div>
                    <div>{{ $config['school_address'] ?? '' }} - Tel: {{ $config['school_phone'] ?? '' }}</div>
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
        alert('Erreur: Impossible de generer le PDF. Donnees manquantes.');
    @endif
}
</script>
@endsection
