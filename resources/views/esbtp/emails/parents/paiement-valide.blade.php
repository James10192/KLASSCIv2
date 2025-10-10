@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Paiement Validé',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'ESBTP-yAKRO',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-success">
        <strong>Paiement validé avec succès!</strong><br>
        Le paiement de {{ $studentName }} a été validé par l'administration.
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Détails du paiement</h3>

    <table class="info-table">
        <tr>
            <th style="width: 40%;">Étudiant</th>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <th>Montant payé</th>
            <td><strong style="color: #28a745; font-size: 16px;">{{ number_format($montant, 0, ',', ' ') }} FCFA</strong></td>
        </tr>
        <tr>
            <th>Référence</th>
            <td>{{ $reference }}</td>
        </tr>
        <tr>
            <th>Numéro de reçu</th>
            <td><strong>{{ $numeroRecu }}</strong></td>
        </tr>
        <tr>
            <th>Mode de paiement</th>
            <td><span class="badge badge-info">{{ $modePaiement }}</span></td>
        </tr>
        <tr>
            <th>Date de paiement</th>
            <td>{{ $datePaiement }}</td>
        </tr>
        <tr>
            <th>Date de validation</th>
            <td>{{ $dateValidation }}</td>
        </tr>
        <tr>
            <th>Validé par</th>
            <td>{{ $validePar }}</td>
        </tr>
    </table>

    <h3 style="color: #007bff; margin-top: 30px;">Situation financière</h3>

    <div class="kpi-section">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value">{{ number_format($montantTotal, 0, ',', ' ') }} FCFA</div>
                <div class="kpi-label">Montant total</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: #28a745;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</div>
                <div class="kpi-label">Total payé</div>
            </div>
        </div>
    </div>

    <div class="kpi-section" style="margin-top: 10px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value" style="color: {{ $resteDu > 0 ? '#dc3545' : '#28a745' }};">
                    {{ number_format($resteDu, 0, ',', ' ') }} FCFA
                </div>
                <div class="kpi-label">Reste à payer</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: #17a2b8;">{{ $pourcentagePaye }}%</div>
                <div class="kpi-label">Progression</div>
            </div>
        </div>
    </div>

    @if($resteDu > 0)
    <div class="alert alert-warning">
        <strong>Frais restants</strong><br>
        Il reste encore <strong>{{ number_format($resteDu, 0, ',', ' ') }} FCFA</strong> à payer pour compléter les frais de scolarité.
    </div>
    @else
    <div class="alert alert-success">
        <strong>Félicitations!</strong><br>
        Tous les frais de scolarité ont été payés. Votre enfant est à jour.
    </div>
    @endif

    <div class="button-container">
        <a href="{{ $recuUrl }}" class="button">Télécharger le reçu</a>
    </div>

    <div class="divider"></div>

    <p style="color: #6c757d; font-size: 13px;">
        Conservez ce reçu comme preuve de paiement. Vous pouvez également le télécharger à tout moment depuis votre espace parent sur la plateforme.
    </p>
@endsection
