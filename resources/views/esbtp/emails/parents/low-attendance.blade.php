@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Alerte Taux de Présence',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? \App\Helpers\SettingsHelper::get('school_address', ''),
    'schoolPhone' => $schoolPhone ?? \App\Helpers\SettingsHelper::get('school_phone', ''),
    'schoolEmail' => $schoolEmail ?? \App\Helpers\SettingsHelper::get('school_email', ''),
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
                <div class="kpi-value" style="color: {{ $emailDangerColor }}; font-size: 32px;">{{ $tauxPresence }}%</div>
                <div class="kpi-label">Taux de présence</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: {{ $emailSuccessText }};">80%</div>
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
                <div class="kpi-value" style="color: {{ $emailDangerColor }};">{{ $absencesNonJustifiees }}h</div>
                <div class="kpi-label">Non justifiées</div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning" style="margin-top: 20px;">
        <strong>Impact sur les résultats</strong><br>
        Un taux de présence faible peut affecter négativement les résultats académiques et la note d'assiduité de votre enfant.
    </div>

    <h3 style="color: {{ $emailPrimaryColor }}; margin-top: 30px;">Recommandations</h3>
    <ul style="color: #6c757d;">
        <li>Assurez-vous que votre enfant assiste régulièrement aux cours</li>
        <li>Justifiez les absences inévitables dans les 48h</li>
        <li>Contactez le coordinateur en cas de difficultés persistantes</li>
    </ul>

    @include('esbtp.emails.partials.bouton', ['url' => $absencesUrl, 'libelle' => 'Voir les détails des absences'])
@endsection
