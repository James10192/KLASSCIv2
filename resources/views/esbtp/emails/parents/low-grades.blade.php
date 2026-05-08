@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Alerte Performance Académique',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? \App\Helpers\SettingsHelper::get('school_address', ''),
    'schoolPhone' => $schoolPhone ?? \App\Helpers\SettingsHelper::get('school_phone', ''),
    'schoolEmail' => $schoolEmail ?? \App\Helpers\SettingsHelper::get('school_email', ''),
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-warning">
        <strong>Attention - Performance académique</strong><br>
        Le bulletin de {{ $studentName }} indique des résultats en dessous de la moyenne requise.
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Résultats du bulletin</h3>

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
            <th>Période</th>
            <td>{{ $periode }}</td>
        </tr>
        <tr>
            <th>Moyenne générale</th>
            <td><strong style="color: #dc3545; font-size: 16px;">{{ number_format($moyenneGenerale, 2) }}/20</strong></td>
        </tr>
        <tr>
            <th>Seuil de réussite</th>
            <td>10/20</td>
        </tr>
        <tr>
            <th>Rang de la classe</th>
            <td>{{ $rang }}/{{ $effectifClasse }}</td>
        </tr>
        @if(isset($decision) && $decision)
        <tr>
            <th>Décision</th>
            <td><span class="badge badge-danger">{{ $decision }}</span></td>
        </tr>
        @endif
    </table>

    @if(isset($matieresEnDifficulte) && count($matieresEnDifficulte) > 0)
    <h3 style="color: #dc3545; margin-top: 30px;">Matières en difficulté</h3>

    <table class="info-table">
        <thead>
            <tr>
                <th>Matière</th>
                <th style="text-align: center;">Moyenne</th>
                <th style="text-align: center;">Coefficient</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matieresEnDifficulte as $matiere)
            <tr>
                <td>{{ $matiere['nom'] }}</td>
                <td style="text-align: center;">
                    <strong style="color: #dc3545;">{{ number_format($matiere['moyenne'], 2) }}/20</strong>
                </td>
                <td style="text-align: center;">{{ $matiere['coefficient'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h3 style="color: #007bff; margin-top: 30px;">Recommandations</h3>

    <div style="background: #fff3cd; padding: 20px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #856404;">Actions suggérées</h4>
        <ul style="color: #856404; margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 10px;">
                <strong>Suivi régulier:</strong> Suivez de près le travail scolaire de votre enfant et vérifiez ses devoirs quotidiennement.
            </li>
            <li style="margin-bottom: 10px;">
                <strong>Communication:</strong> Discutez avec votre enfant pour identifier les difficultés rencontrées.
            </li>
            <li style="margin-bottom: 10px;">
                <strong>Soutien scolaire:</strong> Envisagez des cours de soutien dans les matières en difficulté.
            </li>
            <li style="margin-bottom: 10px;">
                <strong>Rencontre pédagogique:</strong> Prenez rendez-vous avec le coordinateur pédagogique pour discuter de la situation.
            </li>
            <li style="margin-bottom: 10px;">
                <strong>Assiduité:</strong> Assurez-vous que votre enfant assiste régulièrement aux cours (taux de présence actuel: {{ $tauxPresence ?? 'N/A' }}%).
            </li>
        </ul>
    </div>

    @if(isset($coursDisponibles) && $coursDisponibles)
    <div class="alert alert-info">
        <strong>Cours de soutien disponibles</strong><br>
        L'établissement propose des cours de soutien pour aider les étudiants en difficulté.
        Contactez l'administration pour plus d'informations.
    </div>
    @endif

    <div class="button-container">
        <a href="{{ $bulletinUrl }}" class="button" style="background: #dc3545;">Consulter le bulletin complet</a>
        <a href="{{ $contactUrl }}" class="button" style="background: #6c757d; margin-left: 10px;">Contacter le coordinateur</a>
    </div>

    <div class="divider"></div>

    <p style="color: #6c757d; font-size: 13px;">
        Nous restons à votre disposition pour vous accompagner dans le suivi scolaire de votre enfant.
        N'hésitez pas à nous contacter pour toute question ou préoccupation.
    </p>
@endsection
