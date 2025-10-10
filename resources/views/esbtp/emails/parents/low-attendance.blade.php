@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Alerte Taux de Présence',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'ESBTP-yAKRO',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-danger">
        <strong>Alerte - Taux de présence faible</strong><br>
        Le taux de présence de {{ $studentName }} est en dessous du seuil recommandé.
    </div>

    <table class="info-table">
        <tr><th style="width: 40%;">Étudiant</th><td><strong>{{ $studentName }}</strong></td></tr>
        <tr><th>Classe</th><td>{{ $classe }}</td></tr>
        <tr><th>Période</th><td>{{ $periode }}</td></tr>
    </table>

    <div class="kpi-section" style="margin-top: 20px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value" style="color: #dc3545; font-size: 32px;">{{ $tauxPresence }}%</div>
                <div class="kpi-label">Taux de présence</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: #28a745;">80%</div>
                <div class="kpi-label">Seuil recommandé</div>
            </div>
        </div>
    </div>

    <div class="kpi-section" style="margin-top: 10px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value">{{ $totalAbsences }}h</div>
                <div class="kpi-label">Total absences</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: #dc3545;">{{ $absencesNonJustifiees }}h</div>
                <div class="kpi-label">Non justifiées</div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning" style="margin-top: 20px;">
        <strong>Impact sur les résultats</strong><br>
        Un taux de présence faible peut affecter négativement les résultats académiques et la note d'assiduité de votre enfant.
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Recommandations</h3>
    <ul style="color: #6c757d;">
        <li>Assurez-vous que votre enfant assiste régulièrement aux cours</li>
        <li>Justifiez les absences inévitables dans les 48h</li>
        <li>Contactez le coordinateur en cas de difficultés persistantes</li>
    </ul>

    <div class="button-container">
        <a href="{{ $absencesUrl }}" class="button">Voir les détails des absences</a>
    </div>
@endsection
