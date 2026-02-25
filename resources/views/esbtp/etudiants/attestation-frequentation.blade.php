<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    @php
        $pdfSettings    = \App\Helpers\SettingsHelper::getPdfSettings();
        $pdfHeaderBg    = $pdfSettings['header_bg_color']   ?? '#0453cb';
        $pdfHeaderText  = $pdfSettings['header_text_color'] ?? '#ffffff';
        $pdfPrimary     = $pdfSettings['primary_color']     ?? $pdfHeaderBg;
        $pdfText        = $pdfSettings['text_color']        ?? '#1f2937';
        $pdfMuted       = '#6b7280';
        $pdfBorder      = '#e5e7eb';
    @endphp
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Attestation de Fréquentation - {{ $etudiant->matricule }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: "Helvetica", "Arial", sans-serif;
            font-size: 12px;
            line-height: 1.55;
            color: {{ $pdfText }};
            margin: 0;
            padding: 0;
            background: white;
        }

        .container {
            width: 100%;
            padding: 28px 30px;
        }

        .document-watermark {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.06;
            width: 55%;
            z-index: 0;
            text-align: center;
        }
        .document-watermark img { max-width: 100%; }
        .document-content { position: relative; z-index: 1; }

        /* En-tête établissement */
        .doc-header {
            background-color: {{ $pdfHeaderBg }};
            color: {{ $pdfHeaderText }};
            border-radius: 10px;
            padding: 18px 22px;
            text-align: center;
        }

        .doc-header-logo img {
            max-height: 48px;
            max-width: 90px;
            margin-bottom: 8px;
            filter: brightness(0) invert(1);
        }

        .doc-school-name {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: {{ $pdfHeaderText }};
            margin-bottom: 4px;
        }

        .doc-school-meta {
            font-size: 9px;
            opacity: 0.88;
            line-height: 1.5;
            color: {{ $pdfHeaderText }};
        }

        /* Séparateur */
        .doc-divider {
            height: 3px;
            background-color: {{ $pdfPrimary }};
            margin: 18px 0;
            border: none;
        }

        /* Titre document — underline uniquement */
        .doc-title-wrap {
            text-align: center;
            margin: 0 0 26px;
        }

        .doc-title {
            display: inline-block;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: {{ $pdfPrimary }};
            border-bottom: 3px solid {{ $pdfPrimary }};
            padding-bottom: 5px;
        }

        /* Corps */
        .doc-body {
            margin: 0 0 20px;
            line-height: 1.7;
            font-size: 12px;
            text-align: justify;
            color: {{ $pdfText }};
        }

        .doc-body p { margin-bottom: 10px; }

        /* Mots mis en valeur — couleur accent lisible sur fond blanc */
        .hl {
            font-weight: 700;
            color: {{ $pdfPrimary }};
            text-decoration: underline;
        }

        /* Bloc infos étudiant — fond très clair + bordure gauche colorée */
        .student-details {
            margin: 16px 0;
            padding: 12px 14px;
            background-color: #f8fafc;
            border-left: 4px solid {{ $pdfPrimary }};
            width: 100%;
        }

        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 7px;
        }

        .detail-label {
            display: table-cell;
            width: 35%;
            font-weight: 700;
            color: {{ $pdfPrimary }};
            vertical-align: top;
        }

        .detail-value {
            display: table-cell;
            width: 65%;
            color: {{ $pdfText }};
            vertical-align: top;
            word-break: break-word;
        }

        /* Options statut/boursier */
        .status-options {
            margin: 14px 0;
            font-style: italic;
            font-size: 11px;
            color: {{ $pdfText }};
        }

        /* Footer signature */
        .doc-footer {
            margin-top: 36px;
            width: 100%;
            overflow: hidden;
        }

        .doc-footer-date {
            float: left;
            width: 48%;
            font-style: italic;
            color: {{ $pdfMuted }};
            font-size: 11px;
            margin-top: 32px;
        }

        .doc-footer-sign {
            float: right;
            width: 48%;
            text-align: right;
            border-top: 2px solid {{ $pdfPrimary }};
            padding-top: 10px;
            min-height: 60px;
        }

        .sign-title {
            font-weight: 700;
            color: {{ $pdfPrimary }};
            font-size: 12px;
            margin-bottom: 6px;
        }

        .sign-name {
            color: {{ $pdfMuted }};
            font-style: italic;
            font-size: 10px;
            margin-top: 18px;
        }

        .sign-note {
            clear: both;
            text-align: center;
            font-size: 9px;
            font-style: italic;
            color: {{ $pdfMuted }};
            margin-top: 14px;
        }

        /* Note de bas de page */
        .doc-note {
            clear: both;
            margin-top: 28px;
            text-align: center;
            font-size: 9px;
            font-style: italic;
            color: {{ $pdfMuted }};
            border-top: 1px solid {{ $pdfBorder }};
            padding-top: 8px;
        }
    </style>
</head>
<body>
<div class="container">

    @php
        $logoPath   = \App\Helpers\SettingsHelper::get('school_logo');
        $logoBase64 = null;
        if ($logoPath) {
            foreach ([
                storage_path('app/public/' . $logoPath),
                public_path($logoPath),
            ] as $path) {
                if (file_exists($path)) {
                    $ext        = pathinfo($path, PATHINFO_EXTENSION);
                    $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($path));
                    break;
                }
            }
        }
    @endphp

    @if($logoBase64)
        <div class="document-watermark">
            <img src="{{ $logoBase64 }}" alt="">
        </div>
    @endif

    <div class="document-content">

        {{-- En-tête établissement --}}
        <div class="doc-header">
            @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                <div class="doc-header-logo"><img src="{{ $settings['logo_base64'] }}" alt="Logo"></div>
            @endif
            <div class="doc-school-name">{{ $settings['name'] ?? '' }}</div>
            @if($settings['address'] ?? null)
                <div class="doc-school-meta">{{ $settings['address'] }}</div>
            @endif
            @if(($settings['phone'] ?? null) || ($settings['email'] ?? null))
                <div class="doc-school-meta">
                    @if($settings['phone'] ?? null)Tél : {{ $settings['phone'] }}@endif
                    @if(($settings['phone'] ?? null) && ($settings['email'] ?? null)) – @endif
                    @if($settings['email'] ?? null)Email : {{ $settings['email'] }}@endif
                </div>
            @endif
        </div>

        {{-- Séparateur --}}
        <div class="doc-divider"></div>

        {{-- Titre --}}
        <div class="doc-title-wrap">
            <span class="doc-title">Attestation de Fréquentation</span>
        </div>

        {{-- Corps --}}
        <div class="doc-body">
            <p>Je soussigné(e), {{ $settings['director_title'] ?? '' }} de {{ $settings['name'] ?? '' }}, atteste que :</p>

            <p>
                {{ $etudiant->sexe === 'F' ? 'Mme / M. / Mlle' : 'M.' }}
                <span class="hl">{{ strtoupper($etudiant->nom) }} {{ strtoupper($etudiant->prenom) }}</span>
            </p>

            @if($etudiant->date_naissance)
            <p>
                Né(e) le <span class="hl">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                @if($etudiant->lieu_naissance)
                    à <span class="hl">{{ strtoupper($etudiant->lieu_naissance) }}</span>
                @endif
            </p>
            @endif

            <p>
                Est régulièrement inscrit(e) au titre de l'année scolaire
                <span class="hl">
                @php
                    $anneeText = $inscription->anneeUniversitaire->name
                        ?? $inscription->anneeUniversitaire->nom
                        ?? $inscription->anneeUniversitaire->libelle
                        ?? '';
                    echo preg_match('/(\d{4}-\d{4})/', $anneeText, $matches)
                        ? $matches[1] : $anneeText;
                @endphp
                </span>
            </p>

            {{-- Bloc infos étudiant — fond clair + bordure gauche colorée --}}
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
                <strong>Statut* :</strong> Affecté / Non affecté
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <strong>Boursier* :</strong> Oui / Non
            </div>

            <p>En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</p>
        </div>

        {{-- Footer signature --}}
        <div class="doc-footer">
            <div class="doc-footer-date">
                <p>Fait à {{ $settings['city'] ?? 'Yamoussoukro' }}, le {{ now()->format('d/m/Y') }}</p>
            </div>
            <div class="doc-footer-sign">
                <div class="sign-title">{{ $settings['director_title'] ?? '' }}</div>
                @if($settings['director_name'] ?? null)
                    <div class="sign-name">{{ $settings['director_name'] }}</div>
                @endif
            </div>
            <div style="clear:both;"></div>
        </div>

        <div class="sign-note">*Rayer la mention inutile</div>

        {{-- Note de bas de page --}}
        <div class="doc-note">
            Ce document est un certificat officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
        </div>

    </div>
</div>
</body>
</html>
