<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat de Scolarité</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('/storage/fonts/DejaVuSans.ttf');
            font-weight: normal;
        }
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('/storage/fonts/DejaVuSans-Bold.ttf');
            font-weight: bold;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }
        
        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
        }
        
        .logo {
            width: 120px;
            margin-right: 20px;
        }
        
        .school-info {
            flex: 1;
            text-align: center;
        }
        
        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #2d5016;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .school-subtitle {
            font-size: 14px;
            font-weight: bold;
            color: #2d5016;
            margin-bottom: 10px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        
        .school-details {
            font-size: 9px;
            line-height: 1.2;
            color: #000;
        }
        
        .divider {
            border-bottom: 2px solid #000;
            margin: 15px 0;
        }
        
        .certificate-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 3px solid #000;
            border-radius: 15px;
            padding: 15px;
            margin: 30px auto 40px;
            max-width: 500px;
            background-color: #f8f8f8;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .content {
            margin: 30px 0;
            text-align: justify;
            line-height: 1.6;
        }
        
        .student-info {
            margin: 25px 0;
            line-height: 1.8;
        }
        
        .student-info strong {
            font-weight: bold;
        }
        
        .inscriptions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            border: 2px solid #000;
        }
        
        .inscriptions-table th,
        .inscriptions-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        
        .inscriptions-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
        }
        
        .inscriptions-table td {
            font-size: 11px;
        }
        
        .program-info {
            margin: 20px 0;
            font-style: italic;
        }
        
        .appreciations {
            margin: 25px 0;
            line-height: 1.8;
        }
        
        .footer {
            margin-top: 100px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .date {
            text-align: left;
        }

        .signature {
            text-align: right;
        }

        .signature-name {
            margin-top: 80px;
            font-weight: bold;
        }
        
        .legal-notice {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            font-size: 8px;
            text-align: justify;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    @php
        $_certLogo = \App\Helpers\SettingsHelper::resolveLogoBase64();
        $_certAcronym = \App\Helpers\SettingsHelper::get('school_acronym', config('app.name', 'KLASSCI'));
    @endphp
    <div class="header">
        <div class="logo">
            @if($_certLogo)
                <img src="{{ $_certLogo['data_uri'] }}" alt="Logo" style="width: 100px; height: 100px; object-fit: contain;">
            @else
                <div style="width: 100px; height: 100px; border: 2px solid #2d5016; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #2d5016; font-size: 18px;">
                    {{ $_certAcronym }}
                </div>
            @endif
        </div>

        <div class="school-info">
            <div class="school-name">{{ $settings['school_name'] ?? \App\Helpers\SettingsHelper::get('school_name', config('app.name', 'KLASSCI')) }}</div>

            <div class="school-details">
                @if(($settings['address'] ?? null) || \App\Helpers\SettingsHelper::get('school_address'))
                    <strong>SIÈGE SOCIAL :</strong> {{ $settings['address'] ?? \App\Helpers\SettingsHelper::get('school_address', '') }}<br>
                @endif
                @if(($settings['phone'] ?? null) || ($settings['mobile'] ?? null))
                    @if($settings['phone'] ?? null)<strong>TÉL :</strong> {{ $settings['phone'] }}@endif
                    @if(($settings['phone'] ?? null) && ($settings['mobile'] ?? null)) - @endif
                    @if($settings['mobile'] ?? null)<strong>CEL :</strong> {{ $settings['mobile'] }}@endif
                    <br>
                @endif
                @if(($settings['website'] ?? null) || ($settings['email'] ?? null))
                    @if($settings['website'] ?? null)<strong>SITE WEB :</strong> {{ $settings['website'] }}@endif
                    @if(($settings['website'] ?? null) && ($settings['email'] ?? null)) - @endif
                    @if($settings['email'] ?? null)<strong>E-MAIL :</strong> {{ $settings['email'] }}@endif
                @endif
            </div>
        </div>
    </div>
    
    <div class="divider"></div>
    
    <!-- Titre -->
    <div class="certificate-title">
        Certificat de Scolarité
    </div>
    
    <!-- Contenu principal -->
    <div class="content">
        <p>Je soussigné(e) le/la {{ \App\Helpers\SettingsHelper::get('director_title', 'Directeur(trice) des Etudes') }} de {{ \App\Helpers\SettingsHelper::get('school_name', config('app.name')) }} certifie que :</p>
    </div>
    
    <!-- Informations étudiant -->
    <div class="student-info">
        <p>{{ $etudiant->sexe === 'F' ? 'Mme, M., Mlle' : 'M.' }} <strong>{{ mb_strtoupper($etudiant->nom ?? '', 'UTF-8') }} {{ mb_strtoupper($etudiant->prenom ?? '', 'UTF-8') }}</strong></p>
        <p>Né(e) le <strong>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non renseigné' }}</strong> à <strong>{{ mb_strtoupper($etudiant->lieu_naissance ?? '', 'UTF-8') }}</strong></p>
        <p>Sous le matricule : <strong>{{ $etudiant->numero_etudiant ?? 'Non attribué' }}</strong></p>
    </div>
    
    <p style="margin: 25px 0;">Est régulièrement inscrit(e) sur le registre des effectifs de l'année universitaire :</p>

    <!-- Tableau des inscriptions -->
    <table class="inscriptions-table">
        <thead>
            <tr>
                <th>Année universitaire</th>
                <th>Classe suivie</th>
                <th>Filière</th>
                <th>Moyenne/20</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscriptions as $inscription)
            <tr>
                <td>
                    {{ $inscription->anneeUniversitaire->nom ?? 'Non renseigné' }}
                    @if($inscription->is_sous_reserve)
                        <br><small style="color: #d97706; font-weight: bold;">Sous réserve{{ $inscription->condition_reserve ? ' de son ' . $inscription->condition_reserve : '' }}</small>
                    @endif
                </td>
                <td>{{ $inscription->classe->nom ?? ($inscription->niveau->nom ?? 'Non renseigné') }}</td>
                <td>{{ mb_strtoupper($inscription->filiere->nom ?? 'Non renseigné', 'UTF-8') }}</td>
                <td>
                    @if($inscription->moyenne_generale)
                        {{ number_format($inscription->moyenne_generale, 2) }}
                    @else
                        <!-- Moyenne vide pour l'année en cours -->
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4">Aucune inscription trouvée</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <!-- Information programme -->
    <div class="program-info">
        <p>Suivant l'horaire du programme complet.</p>
    </div>
    
    <!-- Appréciations -->
    <div class="appreciations">
        <p><strong>Appréciations générales :</strong></p>
        <p>Travail : <em>PASSABLE</em></p>
        <p>Conduite : <em>BONNE</em></p>
    </div>
    
    <div style="margin: 30px 0;">
        <p>En foi de quoi, le présent certificat lui est délivré pour servir et valoir ce que de droit.</p>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <div class="date">
            <p>Fait à {{ $settings['city'] ?? 'Yamoussoukro' }}, le {{ now()->locale('fr')->translatedFormat('j F Y') }}</p>
        </div>
        
        <div class="signature">
            <p>{{ $settings['director_title'] ?? 'La Directrice des Etudes' }}</p>
            <div class="signature-name">
                {{ $settings['director_name'] ?? 'MANGOUA Nadège' }}
            </div>
        </div>
    </div>
    
    <!-- Mention légale -->
    <div class="legal-notice">
        <strong>NB :</strong> les ratures, grattages, surcharges ou omissions conduisent à la nullité du présent certificat. Toute falsification sera punie par les peines prévues par la loi
    </div>
</body>
</html>
