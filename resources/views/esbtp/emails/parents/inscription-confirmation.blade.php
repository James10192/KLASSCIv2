@php
    $emailTitle = 'Confirmation d\'Inscription';
@endphp

@extends('esbtp.emails.parents.layout')

@section('content')
    <div class="message-intro">
        <p><strong>Inscription réussie!</strong> Votre enfant {{ $studentName }} a été inscrit(e) avec succès{{ isset($anneeUniversitaire) && $anneeUniversitaire !== 'N/A' ? ' pour l\'année universitaire ' . $anneeUniversitaire : '' }}.</p>
    </div>

    <h3 style="color: #007bff; margin-top: 30px; margin-bottom: 15px; font-size: 18px;">Informations de l'inscription</h3>

    <table class="info-table">
        <tr>
            <th style="width: 40%;">Étudiant</th>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        @if(isset($matricule) && $matricule !== 'N/A')
        <tr>
            <th>Matricule</th>
            <td><span class="badge badge-info">{{ $matricule }}</span></td>
        </tr>
        @endif
        @if(isset($classe) && $classe !== 'N/A')
        <tr>
            <th>Classe</th>
            <td>{{ $classe }}</td>
        </tr>
        @endif
        @if(isset($filiere) && $filiere !== 'N/A')
        <tr>
            <th>Filière</th>
            <td>{{ $filiere }}</td>
        </tr>
        @endif
        @if(isset($niveauEtude) && $niveauEtude !== 'N/A')
        <tr>
            <th>Niveau d'étude</th>
            <td>{{ $niveauEtude }}</td>
        </tr>
        @endif
        @if(isset($anneeUniversitaire) && $anneeUniversitaire !== 'N/A')
        <tr>
            <th>Année universitaire</th>
            <td><strong>{{ $anneeUniversitaire }}</strong></td>
        </tr>
        @endif
        <tr>
            <th>Date d'inscription</th>
            <td>{{ $dateInscription }}</td>
        </tr>
    </table>

    <div class="instruction-box">
        <h3>Identifiants de connexion</h3>
        <p style="margin-bottom: 15px; color: #495057;">
            Vous pouvez désormais accéder à la plateforme {{ $schoolName ?? 'KLASSCI' }} pour suivre la scolarité de votre enfant, consulter les notes, les absences et effectuer les paiements.
        </p>

        <table class="info-table" style="margin: 15px 0;">
            <tr>
                <th style="width: 40%; background: #f8f9fa; color: #495057;">Nom d'utilisateur</th>
                <td><strong style="color: #007bff; font-size: 16px; font-family: 'Courier New', monospace;">{{ $username }}</strong></td>
            </tr>
            <tr>
                <th style="background: #f8f9fa; color: #495057;">Mot de passe</th>
                <td><strong style="color: #007bff; font-size: 16px; font-family: 'Courier New', monospace;">{{ $password }}</strong></td>
            </tr>
        </table>

        <div class="alert alert-warning" style="margin-top: 15px;">
            <strong>Important:</strong> Changez votre mot de passe lors de votre première connexion pour sécuriser votre compte.
        </div>
    </div>

    <div class="button-container">
        <a href="{{ $platformUrl }}" class="button">Accéder à la plateforme</a>
    </div>

    <h3 style="color: #007bff; margin-top: 35px; margin-bottom: 15px; font-size: 18px;">Que faire ensuite?</h3>

    <ol style="color: #495057; line-height: 2; font-size: 14px;">
        <li><strong>Connectez-vous</strong> à la plateforme avec vos identifiants</li>
        <li><strong>Changez votre mot de passe</strong> dans les paramètres de sécurité</li>
        <li><strong>Consultez le profil</strong> et les informations de scolarité de votre enfant</li>
        <li><strong>Vérifiez les paiements</strong> et effectuez les règlements si nécessaire</li>
    </ol>

    @if(isset($montantDu) && $montantDu > 0)
    <div class="kpi-section" style="margin-top: 30px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-title">Total à payer</div>
                <div class="kpi-value">{{ number_format($montantTotal, 0, ',', ' ') }}</div>
                <div class="kpi-desc">FCFA</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Déjà payé</div>
                <div class="kpi-value" style="color: #28a745;">{{ number_format($montantPaye, 0, ',', ' ') }}</div>
                <div class="kpi-desc">FCFA</div>
            </div>
        </div>
        <div class="kpi-row" style="margin-top: 8px;">
            <div class="kpi-card" style="border-radius: 8px; background: #fff3cd; border-color: #ffc107;">
                <div class="kpi-title" style="color: #856404;">Reste à payer</div>
                <div class="kpi-value" style="color: #dc3545; font-size: 32px;">{{ number_format($montantDu, 0, ',', ' ') }}</div>
                <div class="kpi-desc" style="color: #856404; font-weight: 600;">FCFA</div>
            </div>
        </div>
    </div>
    @endif

    <div class="divider"></div>

    <div style="background: #e7f3ff; border: 2px solid #007bff; border-radius: 8px; padding: 18px; margin-top: 25px; text-align: center;">
        <p style="margin: 0; color: #0056b3; font-size: 14px;">
            <strong>Besoin d'aide?</strong><br>
            Si vous avez des questions ou rencontrez des difficultés, contactez notre service administratif.
        </p>
    </div>
@endsection
