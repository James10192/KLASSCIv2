@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Confirmation de Réinscription',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? \App\Helpers\SettingsHelper::get('school_address', ''),
    'schoolPhone' => $schoolPhone ?? \App\Helpers\SettingsHelper::get('school_phone', ''),
    'schoolEmail' => $schoolEmail ?? \App\Helpers\SettingsHelper::get('school_email', ''),
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-success">
        <strong>Réinscription réussie!</strong><br>
        Votre enfant {{ $studentName }} a été réinscrit(e) avec succès pour l'année universitaire {{ $anneeUniversitaire }}.
    </div>

    <h3 style="color: {{ $emailPrimaryColor }}; margin-top: 30px;">Informations de la réinscription</h3>

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
                @php
                    [$fondDecision, $texteDecision] = match ($decision) {
                        'passage' => [$emailSuccessText, '#ffffff'],
                        'redoublement' => [$emailWarningColor, '#78350f'],
                        default => [$emailPrimaryColor, $emailHeaderTextColor],
                    };
                @endphp
                <span style="padding: 5px 10px; border-radius: 3px; background: {{ $fondDecision }}; color: {{ $texteDecision }}; font-weight: 600;">
                    {{ ucfirst($decision) }}
                </span>
            </td>
        </tr>
    </table>

    <h3 style="color: {{ $emailPrimaryColor }}; margin-top: 30px;">Accès à la plateforme {{ \App\Helpers\SettingsHelper::get('school_acronym', config('app.name')) }}</h3>

    <p class="message">
        Vous pouvez continuer à suivre la scolarité de votre enfant sur la plateforme en ligne avec vos identifiants habituels.
    </p>

    @include('esbtp.emails.partials.bouton', ['url' => $platformUrl, 'libelle' => 'Accéder à la plateforme'])

    @if(isset($reliquatMontant) && $reliquatMontant > 0)
    <div style="background: #fff3cd; padding: 20px; border-radius: 5px; border-left: 4px solid {{ $emailWarningColor }}; margin: 20px 0;">
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
