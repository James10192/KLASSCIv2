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
    <title>Certificat de Scolarité - {{ $etudiant->matricule }}</title>
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

        .certificat-document {
            --primary: {{ $pdfHeaderText }};
            --text-secondary: {{ $pdfText }};
            --text: {{ $pdfText }};
        }

        .certificat-content table {
            width: 100%;
            table-layout: fixed;
        }

        .certificat-content table th,
        .certificat-content table td {
            word-wrap: break-word;
            overflow-wrap: break-word;
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

        .certificat-content table thead th {
            background-color: {{ $pdfHeaderBg }} !important;
            color: {{ $pdfHeaderText }} !important;
            border-color: {{ $pdfHeaderText }} !important;
        }

        .certificat-content table td {
            color: {{ $pdfText }} !important;
            background: transparent !important;
            border-color: {{ $pdfHeaderText }} !important;
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
        .signature-name {
            color: {{ $pdfHeaderText }} !important;
        }

        .certificat-title {
            background-color: {{ $pdfHeaderBg }} !important;
            border-color: {{ $pdfHeaderText }} !important;
        }

        .certificat-divider {
            background-color: {{ $pdfHeaderText }} !important;
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
            
            <div class="certificat-school-name">{{ $settings['name'] ?? 'École Spéciale du Bâtiment et des Travaux Publics' }}</div>
            
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
            Certificat de Scolarité
        </div>

        <!-- Contenu principal -->
        <div class="certificat-content">
            <p>
                Je soussigné(e), {{ $settings['director_title'] ?? 'Le Directeur' }} de {{ $settings['name'] ?? 'l\'École Spéciale du Bâtiment et des Travaux Publics' }}, certifie que :
            </p>

            <p>
                L'étudiant(e) <span class="certificat-highlight">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span>
            </p>

            @if($etudiant->date_naissance)
            <p>
                Né(e) le <span class="certificat-highlight">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                @if($etudiant->lieu_naissance) 
                    à <span class="certificat-highlight">{{ $etudiant->lieu_naissance }}</span>
                @endif
            </p>
            @endif

            <p>
                Matricule : <span class="certificat-highlight">{{ $etudiant->matricule }}</span>
            </p>

            <p>
                Est régulièrement inscrit(e) sur le registre des effectifs de l'année académique :
            </p>

            <!-- Tableau des inscriptions -->
            <div style="margin: 15px 0;">
                <table style="width: 100%; border-collapse: collapse; border: 2px solid #1e40af; font-size: 11px;">
                    <thead>
                        <tr style="background-color: #f8fafc;">
                            <th style="border: 1px solid #1e40af; padding: 8px; text-align: center; font-weight: bold;">Année scolaire</th>
                            <th style="border: 1px solid #1e40af; padding: 8px; text-align: center; font-weight: bold;">Classe suivie</th>
                            <th style="border: 1px solid #1e40af; padding: 8px; text-align: center; font-weight: bold;">Niveau d'étude</th>
                            <th style="border: 1px solid #1e40af; padding: 8px; text-align: center; font-weight: bold;">Filière</th>
                            <th style="border: 1px solid #1e40af; padding: 8px; text-align: center; font-weight: bold;">Moyenne/20</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inscriptions as $inscription)
                        <tr>
                            <td style="border: 1px solid #1e40af; padding: 8px; text-align: center;">
                                @php
                                    $rawAcademicYear = $inscription->anneeUniversitaire?->libelle
                                        ?? $inscription->anneeUniversitaire?->name
                                        ?? null;
                                    $displayAcademicYear = $rawAcademicYear
                                        ? (preg_match('/(\d{4}-\d{4})/', $rawAcademicYear, $matches) ? $matches[1] : $rawAcademicYear)
                                        : 'Non renseigné';
                                @endphp
                                {{ $displayAcademicYear }}
                            </td>
                            <td style="border: 1px solid #1e40af; padding: 8px; text-align: center;">
                                {{ $inscription->classe->name ?? 'Non renseigné' }}
                            </td>
                            <td style="border: 1px solid #1e40af; padding: 8px; text-align: center;">
                                {{ $inscription->niveauEtude->name ?? 'Non renseigné' }}
                            </td>
                            <td style="border: 1px solid #1e40af; padding: 8px; text-align: center;">
                                {{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}
                            </td>
                            <td style="border: 1px solid #1e40af; padding: 8px; text-align: center;">
                                @if($inscription->moyenne_generale)
                                    {{ number_format($inscription->moyenne_generale, 2) }}
                                @else
                                    <!-- Moyenne vide pour l'année en cours -->
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="border: 1px solid #1e40af; padding: 8px; text-align: center;">Aucune inscription trouvée</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p style="font-style: italic; margin: 12px 0;">
                Suivant l'horaire du programme complet.
            </p>

            <p>
                Ce certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.
            </p>
        </div>

        <!-- Footer avec signature -->
        <div class="certificat-footer">
            <div class="certificat-date">
                <p>Fait à {{ $settings['city'] ?? 'Yamoussoukro' }}, le 13/09/2025</p>
            </div>

            <div class="certificat-signature">
                <div class="signature-title">{{ $settings['director_title'] ?? 'Le Directeur' }}</div>
                @if($settings['director_name'] ?? null)
                    <div class="signature-name">{{ $settings['director_name'] }}</div>
                @endif
            </div>
            
            <!-- Clearfix pour le footer -->
            <div style="clear: both;"></div>
        </div>

        <!-- Note de bas de page -->
        <div class="certificat-note">
            Ce certificat est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
        </div>
        </div>
    </div>
</body>
</html>
