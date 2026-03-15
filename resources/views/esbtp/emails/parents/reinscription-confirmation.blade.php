@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Confirmation de Réinscription',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-success">
        <strong>Réinscription réussie!</strong><br>
        Votre enfant {{ $studentName }} a été réinscrit(e) avec succès pour l'année universitaire {{ $anneeUniversitaire }}.
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Informations de la réinscription</h3>

    <table class="info-table">
        <tr>
            <th style="width: 40%;">Étudiant</th>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <th>Matricule</th>
            <td>{{ $matricule }}</td>
        </tr>
        <tr>
            <th>Nouvelle classe</th>
            <td>{{ $classe }}</td>
        </tr>
        <tr>
            <th>Filière</th>
            <td>{{ $filiere }}</td>
        </tr>
        <tr>
            <th>Niveau d'étude</th>
            <td>{{ $niveauEtude }}</td>
        </tr>
        <tr>
            <th>Année universitaire</th>
            <td>{{ $anneeUniversitaire }}</td>
        </tr>
        <tr>
            <th>Date de réinscription</th>
            <td>{{ $dateReinscription }}</td>
        </tr>
        <tr>
            <th>Décision</th>
            <td>
                <span style="padding: 5px 10px; border-radius: 3px; background:
                    @if($decision === 'passage') #28a745
                    @elseif($decision === 'redoublement') #ffc107
                    @else #17a2b8
                    @endif;
                    color: white; font-weight: 600;">
                    {{ ucfirst($decision) }}
                </span>
            </td>
        </tr>
    </table>

    <h3 style="color: #007bff; margin-top: 30px;">Accès à la plateforme {{ \App\Helpers\SettingsHelper::get('school_acronym', config('app.name')) }}</h3>

    <p class="message">
        Vous pouvez continuer à suivre la scolarité de votre enfant sur la plateforme en ligne avec vos identifiants habituels.
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $platformUrl }}"
           style="display: inline-block; padding: 15px 40px; background: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 16px;">
            Accéder à la plateforme
        </a>
    </div>

    @if(isset($reliquatMontant) && $reliquatMontant > 0)
    <div style="background: #fff3cd; padding: 20px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #856404;">Information reliquat</h4>
        <p style="margin: 10px 0; color: #856404;">
            Un reliquat de <strong>{{ number_format($reliquatMontant, 0, ',', ' ') }} FCFA</strong>
            a été reporté sur cette nouvelle inscription.
        </p>
    </div>
    @endif

    <div class="message" style="margin-top: 30px;">
        <p style="margin: 0;">
            Pour toute question concernant cette réinscription, n'hésitez pas à nous contacter.
        </p>
    </div>

    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
        <p style="margin: 0; color: #6c757d; font-size: 14px;">
            <strong>Rappel :</strong> Utilisez vos identifiants de connexion habituels pour accéder à votre espace parent.
        </p>
    </div>
@endsection
