<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <title>Liste des étudiants</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 8px;
            color: #333;
            line-height: 1.3;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        .header-section {
            border-radius: 6px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            background: white;
            font-size: 9px;
        }

        .students-table th {
            background: #0453cb;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            font-size: 8.5px;
            padding: 6px 4px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .students-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
        }

        .students-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .student-number {
            background: #0453cb;
            color: white;
            padding: 2px 4px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 16px;
            display: inline-block;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 2px 3px;
            border-radius: 2px;
            font-size: 8px;
            color: #374151;
        }

        .student-info-cell {
            text-align: left !important;
            padding-left: 4px !important;
        }

        .student-name {
            font-weight: 600;
            font-size: 9px;
            color: #1f2937;
        }

        .genre-badge {
            background: #0453cb;
            color: white;
            padding: 2px 4px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 16px;
            display: inline-block;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .genre-badge.female {
            background: #e91e63;
        }

        .group-title {
            background: #0453cb;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 16px;
            margin-bottom: 4px;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .group-title:first-child {
            margin-top: 0;
        }

        .group-count {
            font-weight: 400;
            font-size: 10px;
            opacity: 0.85;
            margin-left: 8px;
        }

        .filters-section {
            background: #f0f4ff;
            border: 1px solid #c7d5f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 9px;
            color: #374151;
        }

        .filters-label {
            font-weight: 700;
            color: #0453cb;
            margin-right: 6px;
        }

        .summary-section {
            margin-top: 12px;
            display: table;
            width: 100%;
        }

        .summary-left, .summary-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 3px;
        }

        .summary-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px;
        }

        .summary-title {
            font-size: 10px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-row {
            display: table-row;
        }

        .summary-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 2px;
        }

        .summary-value {
            font-size: 11px;
            font-weight: bold;
            color: #0453cb;
        }

        .summary-label-text {
            font-size: 8px;
            color: #6b7280;
            margin-top: 1px;
        }

        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        .page-break {
            page-break-before: always;
        }

        @page {
            margin: 0.5cm;
            size: A4 landscape;
        }
    </style>
</head>
<body>
    @php
        $pdfCfg  = \App\Helpers\SettingsHelper::getPdfSettings();
        $hdrBg   = $pdfCfg['header_bg_color']  ?? $pdfCfg['primary_color'] ?? '#0453cb';
        $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
        $primary = $pdfCfg['primary_color']     ?? '#0453cb';

        // Chunk-aware defaults (backward compatible with non-chunked calls)
        $isFirstChunk = $isFirstChunk ?? true;
        $isLastChunk  = $isLastChunk ?? true;
        $rowOffset    = $rowOffset ?? 0;

        $allEtudiants = collect();
        foreach ($groups as $items) {
            foreach ($items as $item) {
                $allEtudiants->push($item['etudiant']);
            }
        }
    @endphp
    <div class="container">
        @if($isFirstChunk)
        {{-- Header --}}
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="18%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                        @php $_logo = \App\Helpers\SettingsHelper::resolveLogoBase64(); @endphp
                        @if($_logo)
                            <span style="display:inline-block; background:#fff; padding:5px; border-radius:5px; border:1px solid rgba(255,255,255,0.35);">
                                <img src="{{ $_logo['data_uri'] }}" style="max-height: 55px; max-width: 100px; display:block;" alt="Logo">
                            </span>
                        @else
                            <div style="font-size: 30px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                        @endif
                    </td>
                    <td width="82%" style="background-color: {{ $hdrBg }}; padding: 12px 16px; vertical-align: middle;">
                        <div style="font-size: 15px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                        @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                        <div style="font-size: 8.5px; color: {{ $hdrText }}; opacity: 0.85; margin-bottom: 8px;">
                            @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                            @if($etablissement['telephone'])
                                @if($etablissement['adresse']) &nbsp;|&nbsp; @endif
                                Tel: {{ $etablissement['telephone'] }}
                            @endif
                            @if($etablissement['email'])
                                @if($etablissement['adresse'] || $etablissement['telephone']) &nbsp;|&nbsp; @endif
                                Email: {{ $etablissement['email'] }}
                            @endif
                        </div>
                        @endif
                        <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                            <div style="font-size: 12px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px; margin-bottom: 5px;">
                                LISTE DES ÉTUDIANTS
                                @if($groupBy)
                                    — Groupé par {{ $groupBy === 'classe' ? 'Classe' : ($groupBy === 'filiere' ? 'Filière' : 'Niveau') }}
                                @endif
                            </div>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="33%" style="font-size: 9px; color: {{ $hdrText }};">
                                        <span style="opacity: 0.75;">Total :</span>
                                        <strong>{{ $totalEtudiants }} étudiant{{ $totalEtudiants > 1 ? 's' : '' }}</strong>
                                    </td>
                                    <td width="33%" style="font-size: 9px; color: {{ $hdrText }}; text-align: center;">
                                        <span style="opacity: 0.75;">Date :</span>
                                        <strong>{{ now()->format('d/m/Y') }}</strong>
                                    </td>
                                    <td width="34%" style="font-size: 9px; color: {{ $hdrText }}; text-align: right;">
                                        <span style="opacity: 0.75;">Groupes :</span>
                                        <strong>{{ $groups->count() }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- KPI Bar --}}
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
            <tr>
                <td width="25%" style="background-color: {{ $primary }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">TOTAL</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $totalEtudiants }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Étudiants</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">HOMMES</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $allEtudiants->whereIn('sexe', ['M', 'Masculin'])->count() }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Masculin</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25); -webkit-print-color-adjust: exact; color-adjust: exact;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">FEMMES</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $allEtudiants->whereIn('sexe', ['F', 'Féminin'])->count() }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Féminin</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 9px 8px; text-align: center; vertical-align: middle; -webkit-print-color-adjust: exact; color-adjust: exact;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">GROUPES</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $groups->count() }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">{{ $groupBy ? ucfirst($groupBy) . 's' : 'Total' }}</div>
                </td>
            </tr>
        </table>

        {{-- Active filters --}}
        @if(count($filterLabels) > 0)
        <div class="filters-section">
            <span class="filters-label">Filtres appliqués :</span>
            {{ implode(' | ', $filterLabels) }}
        </div>
        @endif
        @endif

        {{-- Groups --}}
        @foreach($groups as $groupName => $items)
            @if(!$loop->first && $groupBy)
                <div class="page-break"></div>
            @endif

            @if($groupBy)
                <div class="group-title" style="background-color: {{ $primary }};">
                    {{ $groupName }}
                    <span class="group-count">— {{ $items->count() }} étudiant{{ $items->count() > 1 ? 's' : '' }}</span>
                </div>
            @endif

            <table class="students-table">
                <thead>
                    <tr>
                        <th width="25">N°</th>
                        <th width="60">Matricule</th>
                        <th>Nom et Prénoms</th>
                        <th width="30">Genre</th>
                        <th width="60">Date naiss.</th>
                        <th width="70">Téléphone</th>
                        @if(!$groupBy || $groupBy !== 'classe')
                        <th>Classe</th>
                        @endif
                        @if(!$groupBy || $groupBy !== 'filiere')
                        <th>Filière</th>
                        @endif
                        @if(!$groupBy || $groupBy !== 'niveau')
                        <th>Niveau</th>
                        @endif
                        <th width="80">Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        @php
                            $etudiant = $item['etudiant'];
                            $inscription = $item['inscription'];
                        @endphp
                        <tr>
                            <td><span class="student-number">{{ $rowOffset + $index + 1 }}</span></td>
                            <td><span class="student-matricule">{{ $etudiant->matricule ?? 'N/A' }}</span></td>
                            <td class="student-info-cell">
                                @php $accProfile = auth()->user()?->can('students.accessibility.export') ? $etudiant->accessibilityProfile : null; @endphp
                                <div class="student-name">
                                    {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                    @if($accProfile)
                                        <x-pdf-accessibility-pill :summary="$accProfile->summaryBadge()" />
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="genre-badge {{ in_array($etudiant->sexe, ['F', 'Féminin']) ? 'female' : '' }}">{{ $etudiant->sexe ?? '?' }}</span>
                            </td>
                            <td>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'N/A' }}</td>
                            <td>{{ $etudiant->telephone ?? 'N/A' }}</td>
                            @if(!$groupBy || $groupBy !== 'classe')
                            <td>{{ $inscription->classe->name ?? 'N/A' }}</td>
                            @endif
                            @if(!$groupBy || $groupBy !== 'filiere')
                            <td>{{ $inscription->filiere->name ?? 'N/A' }}</td>
                            @endif
                            @if(!$groupBy || $groupBy !== 'niveau')
                            <td>{{ $inscription->niveau->name ?? 'N/A' }}</td>
                            @endif
                            <td style="font-size: 8px;">{{ $etudiant->email_personnel ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

        @if($isLastChunk)
        {{-- Footer summary --}}
        <div class="summary-section">
            <div class="summary-left">
                <div class="summary-card">
                    <div class="summary-title">Résumé statistique</div>
                    <div class="summary-grid">
                        <div class="summary-row">
                            <div class="summary-cell">
                                <div class="summary-value">{{ $totalEtudiants }}</div>
                                <div class="summary-label-text">Total</div>
                            </div>
                            <div class="summary-cell">
                                <div class="summary-value">{{ $allEtudiants->whereIn('sexe', ['M', 'Masculin'])->count() }}</div>
                                <div class="summary-label-text">Hommes</div>
                            </div>
                            <div class="summary-cell">
                                <div class="summary-value">{{ $allEtudiants->whereIn('sexe', ['F', 'Féminin'])->count() }}</div>
                                <div class="summary-label-text">Femmes</div>
                            </div>
                            <div class="summary-cell">
                                <div class="summary-value">{{ $groups->count() }}</div>
                                <div class="summary-label-text">Groupes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="summary-right">
                @php $pdfCfg = $pdfCfg ?? \App\Helpers\SettingsHelper::getPdfSettings(); @endphp
                <div class="summary-card" style="margin-left: 3px;">
                    <div class="summary-title">Informations document</div>
                    <div style="margin-bottom: 5px;">
                        <div style="font-size: 8px; color: #6b7280;">Généré le :</div>
                        <div style="font-size: 9px; font-weight: 600; color: #374151;">{{ now()->format('d/m/Y à H:i') }}</div>
                    </div>
                    @if(($pdfCfg['show_generator_name'] ?? true) && auth()->check())
                        <div style="margin-bottom: 5px;">
                            <div style="font-size: 8px; color: #6b7280;">Par :</div>
                            <div style="font-size: 9px; font-weight: 600; color: #374151;">{{ auth()->user()->name }}</div>
                        </div>
                    @endif
                    <div>
                        <div style="font-size: 8px; color: #6b7280;">Établissement :</div>
                        <div style="font-size: 9px; font-weight: 600; color: #374151;">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="generation-info">
            <strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong><br>
            {{ $etablissement['nom'] ?? 'KLASSCI' }} — Système de Gestion des Inscriptions
        </div>
        @endif
    </div>
</body>
</html>
