@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Nouvelle Note Disponible',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-info">
        <strong>Nouvelle note publiée</strong><br>
        Une nouvelle note a été publiée pour {{ $studentName }}.
    </div>

    <table class="info-table">
        <tr><th style="width: 40%;">Étudiant</th><td><strong>{{ $studentName }}</strong></td></tr>
        <tr><th>Matière</th><td>{{ $matiere }}</td></tr>
        <tr><th>Type d'évaluation</th><td><span class="badge badge-info">{{ $typeEvaluation }}</span></td></tr>
        <tr><th>Date de l'évaluation</th><td>{{ $dateEvaluation }}</td></tr>
    </table>

    <div class="kpi-section" style="margin-top: 20px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value" style="color: {{ $note >= 10 ? '#28a745' : '#dc3545' }}; font-size: 32px;">
                    {{ number_format($note, 2) }}/{{ $bareme }}
                </div>
                <div class="kpi-label">Note obtenue</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">{{ number_format($moyenneClasse, 2) }}/{{ $bareme }}</div>
                <div class="kpi-label">Moyenne de la classe</div>
            </div>
        </div>
    </div>

    @if(isset($rang) && $rang)
    <div class="kpi-section" style="margin-top: 10px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value">{{ $rang }}/{{ $effectifClasse }}</div>
                <div class="kpi-label">Rang</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: {{ $note >= $moyenneClasse ? '#28a745' : '#dc3545' }};">
                    {{ $note >= $moyenneClasse ? 'Au-dessus' : 'En-dessous' }}
                </div>
                <div class="kpi-label">Par rapport à la classe</div>
            </div>
        </div>
    </div>
    @endif

    @if(isset($appreciation) && $appreciation)
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #333;">Appréciation</h4>
        <p style="margin-bottom: 0; color: #6c757d;">{{ $appreciation }}</p>
    </div>
    @endif

    <div class="button-container">
        <a href="{{ $noteUrl }}" class="button">Voir les détails</a>
    </div>
@endsection
