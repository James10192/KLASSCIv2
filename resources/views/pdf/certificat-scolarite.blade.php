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
            margin-top: 50px;
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
            margin-top: 40px;
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
    <div class="header">
        <div class="logo">
            <!-- Logo placeholder -->
            <div style="width: 100px; height: 100px; border: 2px solid #2d5016; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #2d5016; font-size: 24px;">
                ES<br>BTP
            </div>
        </div>
        
        <div class="school-info">
            <div class="school-name">École Spéciale</div>
            <div class="school-subtitle">Du Bâtiment et des Travaux Publics</div>
            
            <div class="school-details">
                <strong>SIÈGE SOCIAL :</strong> {{ $settings['address'] ?? 'BP 2541 YAMOUSSSOUKRO – QUARTIER N°ZUESSY – LOT N° 15' }}<br>
                <strong>TÉL :</strong> {{ $settings['phone'] ?? '30 64 59 93' }} - <strong>CEL :</strong> {{ $settings['mobile'] ?? '05 93 34 26 / 07 72 88 56' }}<br>
                <strong>SITE WEB :</strong> {{ $settings['website'] ?? 'www. esbtp-ci.net' }} - <strong>E-MAIL :</strong> {{ $settings['email'] ?? 'esbtp.yamoussoukro.abidjan@gmail.com' }}
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
        <p>Je soussigné(e) la Directrice des Etudes de l'Ecole Spéciale du Bâtiment et des Travaux publics (ESBTP) certifie que :</p>
    </div>
    
    <!-- Informations étudiant -->
    <div class="student-info">
        <p>{{ $etudiant->sexe === 'F' ? 'Mme, M., Mlle' : 'M.' }} <strong>{{ strtoupper($etudiant->nom) }} {{ strtoupper($etudiant->prenom) }}</strong></p>
        <p>Né(e) le <strong>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non renseigné' }}</strong> à <strong>{{ strtoupper($etudiant->lieu_naissance) }}</strong></p>
        <p>Sous le matricule : <strong>{{ $etudiant->numero_etudiant ?? 'Non attribué' }}</strong></p>
    </div>
    
    <p style="margin: 25px 0;">Est régulièrement inscrit(e) sur le registre des effectifs de l'année académique :</p>
    
    <!-- Tableau des inscriptions -->
    <table class="inscriptions-table">
        <thead>
            <tr>
                <th>Année scolaire</th>
                <th>Classe suivie</th>
                <th>Filière</th>
                <th>Moyenne/20</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscriptions as $inscription)
            <tr>
                <td>{{ $inscription->anneeUniversitaire->nom ?? 'Non renseigné' }}</td>
                <td>{{ $inscription->classe->nom ?? ($inscription->niveau->nom ?? 'Non renseigné') }}</td>
                <td>{{ strtoupper($inscription->filiere->nom ?? 'Non renseigné') }}</td>
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
