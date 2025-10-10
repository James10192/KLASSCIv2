@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Rappel de Paiement',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'ESBTP-yAKRO',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-warning">
        <strong>Rappel de paiement</strong><br>
        Ce message vous rappelle qu'un montant reste dû pour les frais de scolarité de {{ $studentName }}.
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Situation financière</h3>

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
            <th>Année universitaire</th>
            <td>{{ $anneeUniversitaire }}</td>
        </tr>
    </table>

    <div class="kpi-section" style="margin-top: 20px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value">{{ number_format($montantTotal, 0, ',', ' ') }} FCFA</div>
                <div class="kpi-label">Montant total</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: #28a745;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</div>
                <div class="kpi-label">Montant payé</div>
            </div>
        </div>
    </div>

    <div class="kpi-section" style="margin-top: 10px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value" style="color: #dc3545; font-size: 28px;">
                    {{ number_format($montantDu, 0, ',', ' ') }} FCFA
                </div>
                <div class="kpi-label"><strong>Reste à payer</strong></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: #17a2b8;">{{ $pourcentagePaye }}%</div>
                <div class="kpi-label">Progression</div>
            </div>
        </div>
    </div>

    @if(isset($echeance) && $echeance)
    <div class="alert alert-danger">
        <strong>Échéance de paiement</strong><br>
        Date limite: <strong>{{ $echeance }}</strong><br>
        Jours restants: <strong>{{ $joursRestants }}</strong>
    </div>
    @endif

    @if(isset($historiqueRelances) && $historiqueRelances > 0)
    <p style="color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px;">
        Ceci est le <strong>{{ $historiqueRelances }}{{ $historiqueRelances == 1 ? 'er' : 'ème' }} rappel</strong> concernant ce paiement.
    </p>
    @endif

    <h3 style="color: #007bff; margin-top: 30px;">Effectuer un paiement</h3>

    <p class="message">
        Vous pouvez effectuer votre paiement directement via la plateforme en ligne ou vous rendre à l'administration de l'établissement.
    </p>

    <div class="button-container">
        <a href="{{ $paiementUrl }}" class="button">Effectuer un paiement</a>
    </div>

    @if(isset($modesP aiement) && count($modesPaiement) > 0)
    <h3 style="color: #007bff; margin-top: 30px;">Modes de paiement acceptés</h3>

    <ul style="color: #6c757d;">
        @foreach($modesPaiement as $mode)
        <li>{{ $mode }}</li>
        @endforeach
    </ul>
    @endif

    <div class="divider"></div>

    <p style="color: #6c757d; font-size: 13px;">
        Si vous avez déjà effectué ce paiement ou si vous rencontrez des difficultés, veuillez contacter notre service comptabilité.
    </p>
@endsection
