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

        // Colonnes configurables
        $showClasse  = $settings['show_classe']  ?? true;
        $showNiveau  = $settings['show_niveau']  ?? true;
        $showFiliere = $settings['show_filiere'] ?? true;
        $colCount    = 2 + ($showClasse ? 1 : 0) + ($showNiveau ? 1 : 0) + ($showFiliere ? 1 : 0);
    @endphp
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat de Scolarité - {{ $etudiant->matricule }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: "DejaVu Sans", "Helvetica", "Arial", sans-serif;
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
            position: fixed;
            top: 30%;
            left: 15%;
            width: 70%;
            opacity: 0.10;
            z-index: 0;
            text-align: center;
        }
        .document-watermark img { max-width: 100%; }
        .document-content { position: relative; z-index: 1; }

        /* ── En-tête établissement — table layout DomPDF-safe (pas de flexbox) ── */
        .doc-header-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            background-color: {{ $pdfHeaderBg }};
            margin-bottom: 0;
        }

        .header-logo-cell {
            width: 18%;
            vertical-align: middle;
            text-align: center;
            padding: 16px 8px 16px 14px;
        }

        .header-logo-cell img {
            width: 70px;
            height: auto;
            max-height: 70px;
            display: block;
            margin: 0 auto;
        }

        .header-info-cell {
            width: 82%;
            vertical-align: middle;
            padding: 16px 16px 16px 8px;
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

        /* ── Séparateur ── */
        .doc-divider {
            height: 3px;
            background-color: {{ $pdfPrimary }};
            margin: 18px 0;
            border: none;
        }

        /* ── Titre document ── */
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

        /* ── Corps ── */
        .doc-body {
            margin: 0 0 20px;
            line-height: 1.7;
            font-size: 12px;
            text-align: justify;
            color: {{ $pdfText }};
        }

        .doc-body p { margin-bottom: 10px; }

        .hl {
            font-weight: 700;
            color: {{ $pdfPrimary }};
            text-decoration: underline;
        }

        /* ── Tableau inscriptions ── */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 18px;
            font-size: 11px;
        }

        .doc-table th {
            background-color: {{ $pdfHeaderBg }};
            color: {{ $pdfHeaderText }};
            border: 1px solid {{ $pdfHeaderBg }};
            padding: 8px 7px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
        }

        .doc-table td {
            border: 1px solid {{ $pdfBorder }};
            padding: 7px;
            text-align: center;
            color: {{ $pdfText }};
        }

        .doc-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* ── Footer signature — float layout DomPDF-safe ── */
        .doc-footer {
            margin-top: 72px;
            width: 100%;
            overflow: hidden;
        }

        .doc-footer-date {
            float: left;
            width: 48%;
            font-style: italic;
            color: {{ $pdfMuted }};
            font-size: 11px;
            margin-top: 64px;
        }

        .doc-footer-sign {
            float: right;
            width: 48%;
            text-align: right;
            border-top: 2px solid {{ $pdfPrimary }};
            padding-top: 20px;
            min-height: 120px;
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

        /* ── Note de bas de page ── */
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

        {{-- ── En-tête établissement : table 2 colonnes DomPDF-safe ── --}}
        <table class="doc-header-table">
            <tr>
                {{-- Colonne logo (18%) --}}
                @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                <td class="header-logo-cell">
                    <img src="{{ $settings['logo_base64'] }}" alt="Logo">
                </td>
                @endif
                {{-- Colonne infos école (82% ou 100% si pas de logo) --}}
                <td class="header-info-cell"
                    @if(!isset($settings['show_logo']) || !$settings['show_logo'] || !isset($settings['logo_base64']))
                        style="width:100%; text-align:center;"
                    @endif
                >
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
                </td>
            </tr>
        </table>

        {{-- ── Séparateur ── --}}
        <div class="doc-divider"></div>

        {{-- ── Titre ── --}}
        <div class="doc-title-wrap">
            <span class="doc-title">Certificat de Scolarité</span>
        </div>

        {{-- ── Corps ── --}}
        <div class="doc-body">
            <p>Je soussigné(e), {{ $settings['director_title'] ?? '' }} de {{ $settings['name'] ?? '' }}, certifie que :</p>

            <p>L'étudiant(e) <span class="hl">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span></p>

            @if($etudiant->date_naissance)
            <p>
                Né(e) le <span class="hl">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                @if($etudiant->lieu_naissance)à <span class="hl">{{ $etudiant->lieu_naissance }}</span>@endif
            </p>
            @endif

            <p>Matricule : <span class="hl">{{ $etudiant->matricule }}</span></p>

            @php
                $hasSousReserve = $inscriptions->contains(fn($i) => $i->is_sous_reserve);
            @endphp
            @if($hasSousReserve)
            <p>Sera régulièrement inscrit(e) sur le registre des effectifs de l'année universitaire :</p>
            @else
            <p>Est régulièrement inscrit(e) sur le registre des effectifs de l'année universitaire :</p>
            @endif

            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Année universitaire</th>
                        @if($showClasse)<th>Classe suivie</th>@endif
                        @if($showNiveau)<th>Niveau d'étude</th>@endif
                        @if($showFiliere)<th>Filière</th>@endif
                        <th>Moyenne/20</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inscriptions as $inscription)
                    <tr>
                        <td>
                            @php
                                $rawAY = $inscription->anneeUniversitaire?->libelle
                                    ?? $inscription->anneeUniversitaire?->name ?? null;
                                $yearText = $rawAY
                                    ? (preg_match('/(\d{4}-\d{4})/', $rawAY, $m) ? $m[1] : $rawAY)
                                    : 'Non renseigné';
                                echo $yearText;
                            @endphp
                            @if($inscription->is_sous_reserve)
                                <br><small style="color: #d97706; font-weight: 600;">Sous réserve{{ $inscription->condition_reserve ? ' de son ' . $inscription->condition_reserve : '' }}</small>
                            @endif
                        </td>
                        @if($showClasse)<td>{{ $inscription->classe->name ?? 'Non renseigné' }}</td>@endif
                        @if($showNiveau)<td>{{ $inscription->niveauEtude->name ?? 'Non renseigné' }}</td>@endif
                        @if($showFiliere)<td>{{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}</td>@endif
                        <td>
                            {{ $inscription->moyenne_generale_calculee !== null ? number_format($inscription->moyenne_generale_calculee, 2) : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $colCount }}">Aucune inscription trouvée</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <p style="font-style:italic;">Suivant l'horaire du programme complet.</p>

            <p>Ce certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.</p>
        </div>

        {{-- ── Footer signature ── --}}
        <div class="doc-footer">
            <div class="doc-footer-date">
                <p>Fait à {{ $settings['city'] ?? 'Yamoussoukro' }}, le {{ now()->format('d/m/Y') }}</p>
            </div>
            <div class="doc-footer-sign">
                <div class="sign-title">{{ $settings['director_title'] ?? 'Le Directeur' }}</div>
                @if($settings['director_name'] ?? null)
                    <div class="sign-name">{{ $settings['director_name'] }}</div>
                @endif
            </div>
            <div style="clear:both;"></div>
        </div>

        {{-- ── Note de bas de page ── --}}
        <div class="doc-note">
            Ce certificat est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
        </div>

    </div>
</div>
</body>
</html>
