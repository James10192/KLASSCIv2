<!DOCTYPE html>
<html lang="fr">
<head>
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
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="certificat-header">
            @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                <img src="{{ $settings['logo_base64'] }}" alt="Logo École" class="certificat-logo">
            @endif
            
            <div class="certificat-school-name">{{ $settings['school_name'] ?? 'École Spéciale du Bâtiment et des Travaux Publics' }}</div>
            
            @if($settings['school_address'] ?? null)
                <div class="certificat-address">{{ $settings['school_address'] }}</div>
            @endif
            @if(($settings['school_phone'] ?? null) || ($settings['school_email'] ?? null))
                <div class="certificat-address">
                    @if($settings['school_phone'] ?? null)Tél: {{ $settings['school_phone'] }}@endif
                    @if(($settings['school_phone'] ?? null) && ($settings['school_email'] ?? null)) - @endif
                    @if($settings['school_email'] ?? null)Email: {{ $settings['school_email'] }}@endif
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
                Je soussigné(e), {{ $settings['director_title'] ?? 'Le Directeur' }} de {{ $settings['school_name'] ?? 'l\'École Spéciale du Bâtiment et des Travaux Publics' }}, certifie que :
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
                Est régulièrement inscrit(e) en 
                <span class="certificat-highlight">{{ $inscription->niveauEtude ? $inscription->niveauEtude->name : 'N/A' }}</span> 
                de la filière 
                <span class="certificat-highlight">{{ $inscription->filiere ? $inscription->filiere->name : 'N/A' }}</span> 
                pour l'année universitaire 
                <span class="certificat-highlight">{{ $inscription->anneeUniversitaire ? ($inscription->anneeUniversitaire->libelle ?: $inscription->anneeUniversitaire->name) : 'N/A' }}</span>.
            </p>

            @if($inscription->classe)
            <p>
                Classe : <span class="certificat-highlight">{{ $inscription->classe->name }}</span>
            </p>
            @endif

            <p>
                Ce certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.
            </p>
        </div>

        <!-- Footer avec signature -->
        <div class="certificat-footer">
            <div class="certificat-date">
                <p>Fait à {{ $settings['school_city'] ?? 'Yamoussoukro' }}, le {{ now()->format('d/m/Y') }}</p>
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
</body>
</html>
