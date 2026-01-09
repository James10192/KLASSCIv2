@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Paiement Rejeté',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-danger">
        <strong>Paiement rejeté</strong><br>
        Votre paiement pour {{ $studentName }} a été rejeté par l'administration.
    </div>

    <table class="info-table">
        <tr><th style="width: 40%;">Étudiant</th><td><strong>{{ $studentName }}</strong></td></tr>
        <tr><th>Montant</th><td><strong>{{ number_format($montant, 0, ',', ' ') }} FCFA</strong></td></tr>
        <tr><th>Référence</th><td>{{ $reference }}</td></tr>
        <tr><th>Date de soumission</th><td>{{ $dateSoumission }}</td></tr>
        <tr><th>Date de rejet</th><td>{{ $dateRejet }}</td></tr>
    </table>

    <div style="background: #f8d7da; padding: 20px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #721c24;">Motif du rejet</h4>
        <p style="margin-bottom: 0; color: #721c24;">{{ $motifRejet }}</p>
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Que faire maintenant?</h3>
    <ol style="color: #6c757d;">
        <li>Vérifiez le motif du rejet ci-dessus</li>
        <li>Corrigez les informations ou fournissez les documents manquants</li>
        <li>Soumettez à nouveau votre paiement via la plateforme</li>
    </ol>

    <div class="button-container">
        <a href="{{ $paiementUrl }}" class="button">Soumettre un nouveau paiement</a>
    </div>
@endsection
