@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Notification d\'Absence',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-warning">
        <strong>Absence enregistrée</strong><br>
        Votre enfant {{ $studentName }} a été marqué(e) absent(e) en cours.
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Détails de l'absence</h3>

    <table class="info-table">
        <tr>
            <th style="width: 40%;">Étudiant</th>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <th>Classe</th>
            <td>{{ $classe }}</td>
        </tr>
        <tr>
            <th>Date</th>
            <td><strong style="color: #dc3545;">{{ $date }}</strong></td>
        </tr>
        <tr>
            <th>Heure</th>
            <td>{{ $heureDebut }} - {{ $heureFin }}</td>
        </tr>
        <tr>
            <th>Matière</th>
            <td>{{ $matiere }}</td>
        </tr>
        <tr>
            <th>Type d'activité</th>
            <td><span class="badge badge-info">{{ $typeActivite }}</span></td>
        </tr>
        @if(isset($commentaire) && $commentaire)
        <tr>
            <th>Commentaire</th>
            <td>{{ $commentaire }}</td>
        </tr>
        @endif
    </table>

    <h3 style="color: #007bff; margin-top: 30px;">Statistiques des absences ({{ $periodeStats }})</h3>

    <div class="kpi-section">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value" style="color: #ffc107;">{{ $absencesJustifiees }}</div>
                <div class="kpi-label">Absences justifiées</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: #dc3545;">{{ $absencesNonJustifiees }}</div>
                <div class="kpi-label">Absences non justifiées</div>
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
                <div class="kpi-value" style="color: {{ $tauxPresence >= 80 ? '#28a745' : '#dc3545' }};">
                    {{ $tauxPresence }}%
                </div>
                <div class="kpi-label">Taux de présence</div>
            </div>
        </div>
    </div>

    @if($absencesNonJustifiees >= 3)
    <div class="alert alert-danger">
        <strong>Attention!</strong><br>
        Votre enfant cumule <strong>{{ $absencesNonJustifiees }} absences non justifiées</strong> ce mois.
        Les absences répétées peuvent impacter les résultats académiques et la note d'assiduité.
    </div>
    @endif

    @if($tauxPresence < 80)
    <div class="alert alert-warning">
        <strong>Taux de présence faible</strong><br>
        Le taux de présence de votre enfant est de {{ $tauxPresence }}%, inférieur au seuil recommandé de 80%.
        Nous vous encourageons à suivre de près l'assiduité de votre enfant.
    </div>
    @endif

    <h3 style="color: #007bff; margin-top: 30px;">Justifier cette absence</h3>

    <p class="message">
        Si cette absence est justifiée (maladie, raison familiale, etc.), vous pouvez soumettre un justificatif via la plateforme.
    </p>

    <div class="button-container">
        <a href="{{ $justificationUrl }}" class="button">Soumettre un justificatif</a>
    </div>

    <div class="divider"></div>

    <p style="color: #6c757d; font-size: 13px;">
        Les absences justifiées nécessitent un document officiel (certificat médical, attestation, etc.).
        Les justificatifs doivent être soumis dans un délai de 48 heures.
    </p>
@endsection
