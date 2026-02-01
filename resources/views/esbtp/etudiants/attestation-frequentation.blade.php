<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    @php
        $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
        $pdfHeaderBg = $pdfSettings['header_bg_color'] ?? '#0453cb';
        $pdfHeaderText = $pdfSettings['header_text_color'] ?? '#ffffff';
        $pdfText = $pdfSettings['text_color'] ?? '#1f2937';
    @endphp
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Attestation de Fréquentation - {{ $etudiant->matricule }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: white;
        }
        
        .container {
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
        }

        .document-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            width: 60%;
            z-index: 0;
            text-align: center;
        }

        .document-watermark img {
            max-width: 100%;
        }

        .document-content {
            position: relative;
            z-index: 1;
        }
        
        /* En-tête moderne */
        .certificat-header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
        }
        
        .certificat-logo {
            max-width: 80px;
            margin-bottom: 10px;
        }
        
        .certificat-school-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 6px;
            text-transform: uppercase;
            color: #1e40af;
        }
        
        .certificat-address {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 3px;
        }
        
        /* Séparateur décoratif - Compatible DomPDF */
        .certificat-divider {
            height: 4px;
            background-color: #1e40af;
            margin: 15px 0;
            border: none;
        }
        
        /* Titre du certificat - Compatible DomPDF */
        .certificat-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            border: 3px double #1e40af;
            padding: 12px;
            margin: 25px auto;
            max-width: 85%;
            background-color: #f8fafc;
            text-transform: uppercase;
            color: #1e40af;
        }
        
        /* Contenu principal */
        .certificat-content {
            margin: 25px 0;
            line-height: 1.7;
            font-size: 12px;
            text-align: justify;
        }
        
        .certificat-content p {
            margin-bottom: 12px;
        }
        
        .certificat-highlight {
            font-weight: bold;
            color: #1e40af;
            text-decoration: underline;
        }

        /* Informations étudiant en bloc */
        .student-info {
            margin: 20px 0;
            line-height: 1.8;
        }

        .student-details {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8fafc;
            border-left: 4px solid #1e40af;
        }

        .detail-row {
            margin-bottom: 8px;
            display: table;
            width: 100%;
        }

        .detail-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            vertical-align: top;
        }

        .detail-value {
            display: table-cell;
            width: 70%;
            vertical-align: top;
        }

        .status-options {
            margin: 15px 0;
            font-style: italic;
            font-size: 11px;
        }
        
        /* Footer avec signature - Compatible DomPDF */
        .certificat-footer {
            margin-top: 40px;
            width: 100%;
            overflow: hidden;
        }
        
        .certificat-date {
            float: left;
            width: 48%;
            text-align: left;
            font-style: italic;
            color: #64748b;
            font-size: 11px;
            margin-top: 30px;
        }
        
        .certificat-signature {
            float: right;
            width: 48%;
            text-align: right;
            border-top: 2px solid #1e40af;
            padding-top: 10px;
            min-height: 60px;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #1e40af;
            font-size: 12px;
        }
        
        .signature-name {
            color: #64748b;
            font-style: italic;
            font-size: 10px;
            margin-top: 20px;
        }

        .signature-note {
            font-size: 9px;
            font-style: italic;
            color: #64748b;
            margin-top: 10px;
            text-align: center;
        }
        
        /* Note de bas de page modernisée */
        .certificat-note {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            font-style: italic;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            clear: both;
        }

        /* Fix overflow in PDF layout */
        .container {
            max-width: 100%;
            box-sizing: border-box;
        }

        .certificat-document {
            max-width: 100%;
            box-sizing: border-box;
        }

        .student-details {
            width: 100%;
            box-sizing: border-box;
        }

        .detail-label {
            width: 35%;
        }

        .detail-value {
            width: 65%;
            word-break: break-word;
        }

        /* Modern administrative look (overrides) */
        body {
            font-family: "Helvetica", "Arial", sans-serif;
            line-height: 1.55;
        }

        .container {
            max-width: 780px;
            padding: 28px 30px;
        }

        .certificat-header {
            background: {{ $pdfHeaderBg }};
            color: {{ $pdfHeaderText }};
            border-radius: 12px;
            padding: 18px 20px;
            border-bottom: none;
        }

        .certificat-school-name,
        .certificat-address {
            color: {{ $pdfHeaderText }};
        }

        .certificat-logo {
            max-width: 70px;
            margin-bottom: 8px;
        }

        .certificat-divider {
            height: 2px;
            background: {{ $pdfHeaderText }};
            margin: 18px 0;
        }

        .certificat-title {
            background: {{ $pdfHeaderBg }};
            color: {{ $pdfHeaderText }};
            border-color: {{ $pdfHeaderText }};
            border-radius: 12px;
            letter-spacing: 0.5px;
            box-shadow: none;
            padding: 12px 16px;
            font-size: 22px;
        }

        .certificat-content {
            font-size: 12px;
            color: {{ $pdfText }};
        }

        .certificat-highlight {
            color: {{ $pdfHeaderText }};
        }

        .student-details {
            background-color: {{ $pdfHeaderBg }};
            border-left: 4px solid {{ $pdfHeaderText }};
        }

        .detail-label {
            color: {{ $pdfHeaderText }};
        }

        .detail-value {
            color: {{ $pdfText }};
        }

        .certificat-footer {
            margin-top: 36px;
        }

        .certificat-signature {
            border-top: 2px solid {{ $pdfHeaderText }};
            color: {{ $pdfHeaderText }};
        }

        .signature-title,
        .signature-name {
            color: {{ $pdfHeaderText }};
        }

        .certificat-note {
            color: {{ $pdfText }};
            border-top: 1px solid {{ $pdfHeaderText }};
        }

        /* Final overrides for PDF rendering */
        .container,
        .certificat-document {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .certificat-header,
        .certificat-school-name,
        .certificat-address,
        .certificat-title,
        .certificat-highlight,
        .signature-title,
        .signature-name,
        .detail-label {
            color: {{ $pdfHeaderText }} !important;
        }

        .certificat-title {
            background-color: {{ $pdfHeaderBg }} !important;
            border-color: {{ $pdfHeaderText }} !important;
        }

        .certificat-divider {
            background-color: {{ $pdfHeaderText }} !important;
        }

        .detail-value {
            color: {{ $pdfText }} !important;
        }

        .student-details {
            background-color: {{ $pdfHeaderBg }} !important;
            border-left-color: {{ $pdfHeaderText }} !important;
        }

        .certificat-document {
            --primary: {{ $pdfHeaderText }};
            --text-secondary: {{ $pdfText }};
            --text: {{ $pdfText }};
        }

        .certificat-document {
            color: {{ $pdfText }};
        }

        .certificat-header,
        .certificat-school-name,
        .certificat-address {
            color: {{ $pdfHeaderText }} !important;
        }

        .certificat-divider {
            background-color: {{ $pdfHeaderText }} !important;
        }

        .certificat-title {
            background-color: {{ $pdfHeaderBg }} !important;
            color: {{ $pdfHeaderText }} !important;
            border-color: {{ $pdfHeaderText }} !important;
        }

        .certificat-highlight,
        .signature-title,
        .certificat-signature {
            color: {{ $pdfHeaderText }} !important;
            border-color: {{ $pdfHeaderText }} !important;
        }

        .detail-label {
            color: {{ $pdfHeaderText }} !important;
        }

        .detail-value {
            color: {{ $pdfText }} !important;
        }

        .student-details {
            background-color: {{ $pdfHeaderBg }} !important;
            border-left-color: {{ $pdfHeaderText }} !important;
        }

        .signature-name {
            color: {{ $pdfHeaderText }} !important;
        }
    </style>
</head>
<body>
    <div class="container">
        @php
            $logoPath = \App\Helpers\SettingsHelper::get('school_logo');
            $logoBase64 = null;
            if ($logoPath) {
                $paths = [
                    storage_path('app/public/' . $logoPath),
                    public_path($logoPath),
                    public_path('images/LOGO-KLASSCI-PNG.png'),
                ];

                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        $imageData = file_get_contents($path);
                        $extension = pathinfo($path, PATHINFO_EXTENSION);
                        $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($imageData);
                        break;
                    }
                }
            }
        @endphp

        @if($logoBase64)
            <div class="document-watermark">
                <img src="{{ $logoBase64 }}" alt="Filigrane logo">
            </div>
        @endif

        <div class="document-content">
        <!-- En-tête -->
        <div class="certificat-header">
            @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                <img src="{{ $settings['logo_base64'] }}" alt="Logo École" class="certificat-logo">
            @endif
            
            <div class="certificat-school-name">{{ $settings['name'] ?? '' }}</div>
            
            @if($settings['address'] ?? null)
                <div class="certificat-address">{{ $settings['address'] }}</div>
            @endif
            @if(($settings['phone'] ?? null) || ($settings['email'] ?? null))
                <div class="certificat-address">
                    @if($settings['phone'] ?? null)Tél: {{ $settings['phone'] }}@endif
                    @if(($settings['phone'] ?? null) && ($settings['email'] ?? null)) - @endif
                    @if($settings['email'] ?? null)Email: {{ $settings['email'] }}@endif
                </div>
            @endif
        </div>

        <!-- Séparateur décoratif -->
        <div class="certificat-divider"></div>

        <!-- Titre du certificat -->
        <div class="certificat-title">
            Attestation de Fréquentation
        </div>

        <!-- Contenu principal -->
        <div class="certificat-content">
            <p>
                Je soussigné(e), {{ $settings['director_title'] ?? '' }} de {{ $settings['name'] ?? '' }}, atteste que :
            </p>

            <div class="student-info">
                <p>
                    {{ $etudiant->sexe === 'F' ? 'Mme, M., Mlle' : 'M.' }} <span class="certificat-highlight">{{ strtoupper($etudiant->nom) }} {{ strtoupper($etudiant->prenom) }}</span>
                </p>

                @if($etudiant->date_naissance)
                <p>
                    Né(e) le <span class="certificat-highlight">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                    @if($etudiant->lieu_naissance) 
                        à <span class="certificat-highlight">{{ strtoupper($etudiant->lieu_naissance) }}</span>
                    @endif
                </p>
                @endif
            </div>

            <p>
                Est régulièrement inscrit(e) au titre de l'année scolaire <span class="certificat-highlight">
                @php
                    $anneeText = $inscription->anneeUniversitaire->nom ?? $inscription->anneeUniversitaire->libelle ?? '2024-2025';
                    if (preg_match('/(\d{4}-\d{4})/', $anneeText, $matches)) {
                        echo $matches[1];
                    } else {
                        echo $anneeText;
                    }
                @endphp
                </span>
            </p>

            <div class="student-details">
                <div class="detail-row">
                    <div class="detail-label">En classe de :</div>
                    <div class="detail-value">{{ $inscription->classe->name ?? ($inscription->niveauEtude->name ?? 'Non renseigné') }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Filière :</div>
                    <div class="detail-value">{{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Sous le numéro Matricule :</div>
                    <div class="detail-value">{{ $etudiant->numero_etudiant ?? $etudiant->matricule }}</div>
                </div>
            </div>

            <div class="status-options">
                <p><strong>Statut* :</strong> Affecté / Non affecté &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Boursier* :</strong> Oui / Non</p>
            </div>

            <p>
                En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.
            </p>
        </div>

        <!-- Footer avec signature -->
        <div class="certificat-footer">
            <div class="certificat-date">
                <p>Fait à {{ $settings['city'] ?? 'Yamoussoukro' }}, le {{ now()->format('d/m/Y') }}</p>
            </div>

            <div class="certificat-signature">
                <div class="signature-title">{{ $settings['director_title'] ?? '' }}</div>
                @if($settings['director_name'] ?? null)
                    <div class="signature-name">{{ $settings['director_name'] }}</div>
                @endif
            </div>
            
            <!-- Clearfix pour le footer -->
            <div style="clear: both;"></div>
        </div>

        <div class="signature-note">
            *Rayer la mention inutile
        </div>

        <!-- Note de bas de page -->
        <div class="certificat-note">
            Ce document est un certificat officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
        </div>
        </div>
    </div>
</body>
</html>
