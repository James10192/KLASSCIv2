@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Paiement en Attente',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-info">
        <strong>Paiement enregistré</strong><br>
        Votre paiement pour {{ $studentName }} a été enregistré et est en attente de validation.
    </div>

    <table class="info-table">
        <tr><th style="width: 40%;">Étudiant</th><td><strong>{{ $studentName }}</strong></td></tr>
        <tr><th>Montant</th><td><strong style="color: #007bff;">{{ number_format($montant, 0, ',', ' ') }} FCFA</strong></td></tr>
        <tr><th>Référence</th><td>{{ $reference }}</td></tr>
        <tr><th>Mode de paiement</th><td><span class="badge badge-info">{{ $modePaiement }}</span></td></tr>
        <tr><th>Date de soumission</th><td>{{ $dateSoumission }}</td></tr>
    </table>

    <div class="alert alert-warning" style="margin-top: 20px;">
        <strong>En attente de validation</strong><br>
        Votre paiement sera validé par l'administration sous 24-48h. Vous recevrez une notification une fois validé.
    </div>

    <div class="button-container">
        <a href="{{ $suiviUrl }}" class="button">Suivre mon paiement</a>
    </div>
@endsection
