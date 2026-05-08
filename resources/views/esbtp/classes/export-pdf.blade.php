@php
    $pdfCfg  = \App\Helpers\SettingsHelper::getPdfSettings();
    $hdrBg   = $pdfCfg['header_bg_color']   ?? $pdfCfg['primary_color'] ?? '#0453cb';
    $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
    $primary = $pdfCfg['primary_color']     ?? '#0453cb';

    // Fusion : settings passés par le controller (nom, adresse, …) pour l'établissement,
    // couleurs depuis les settings PDF globaux.
    $etablissement = [
        'nom'       => $settings['nom']       ?? 'KLASSCI',
        'adresse'   => $settings['adresse']   ?? '',
        'telephone' => $settings['telephone'] ?? '',
        'email'     => $settings['email']     ?? '',
        'logo'      => $settings['logo']      ?? '',
    ];

    // ────────────────────────────────────────────────────────────
    // Effectif canonique : année courante + status=active + workflow_step=etudiant_cree
    // Référence : ESBTPClasse::getNombreEtudiantsAttribute() (Models/ESBTPClasse.php:231)
    // ────────────────────────────────────────────────────────────
    $effectifs = [];
    $totalEffectif = 0;
    $totalCapacite = 0;

    foreach ($classes as $classe) {
        $count = $classe->inscriptions()
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->when($anneeCourante, fn($q) => $q->where('annee_universitaire_id', $anneeCourante->id))
            ->count();
        $effectifs[$classe->id] = $count;
        $totalEffectif += $count;
        $totalCapacite += $classe->places_totales ?? 0;
    }

    $totalClasses         = $classes->count();
    $classesActives       = $classes->where('is_active', true)->count();
    $tauxMoyenRemplissage = $totalCapacite > 0 ? round(($totalEffectif / $totalCapacite) * 100, 1) : 0;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des classes - {{ $settings['nom'] ?? 'Établissement' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Pattern strictement identique à bulletins/pdf-configurable.blade.php:342
           — seule combinaison vérifiée visible en prod dans ce projet. */
        @page {
            size: A4 landscape;
            margin: 15mm 12mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.35;
            background: #ffffff;
            padding: 0;
        }

        .container {
            background: #ffffff;
        }

        /* ============================================================
           HEADER — 2 colonnes (Logo | Infos école + Titre document)
           Copié du pattern de liste-complete-pdf
           ============================================================ */
        .header-section {
            border-radius: 6px;
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            overflow: hidden;
        }

        /* ============================================================
           KPI BAND — 4 cellules uniformes
           ============================================================ */
        .kpi-band {
            margin-bottom: 12px;
            border-radius: 6px;
            overflow: hidden;
        }

        /* ============================================================
           TABLE CLASSES — Premium monochrome bleu
           ============================================================ */
        .classes-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            font-size: 9px;
            margin-top: 6px;
        }

        .classes-table thead th {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 8.5px;
            padding: 7px 5px;
            text-align: center;
            border-right: 1px solid rgba(255, 255, 255, 0.18);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .classes-table thead th:last-child {
            border-right: none;
        }

        .classes-table tbody td {
            padding: 6px 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 9px;
            word-break: break-word;
        }

        .classes-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .classes-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Column widths — optimisés A4 landscape */
        .col-num    { width: 26px; text-align: center; }
        .col-name   { width: 140px; font-weight: 600; color: #0f172a; }
        .col-code   { width: 68px; text-align: center; font-family: 'Courier New', monospace; font-size: 8.5px; }
        .col-filie  { width: 140px; }
        .col-niv    { width: 95px; }
        .col-eff    { width: 65px; text-align: center; font-weight: 600; }
        .col-taux   { width: 58px; text-align: center; }
        .col-stat   { width: 60px; text-align: center; }

        /* Pastille numéro ligne */
        .row-number {
            display: inline-block;
            min-width: 18px;
            padding: 2px 5px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 8.5px;
            color: #ffffff;
        }

        .code-chip {
            display: inline-block;
            background: #f3f4f6;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 8.5px;
            color: #374151;
            letter-spacing: 0.2px;
        }

        .eff-value {
            font-weight: 700;
            color: #0f172a;
        }
        .eff-capacity {
            color: #64748b;
            font-weight: 500;
        }

        /* Badges sémantiques (statut + taux remplissage) */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            border: 1px solid transparent;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }
        .badge-muted {
            background: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0;
        }

        /* ============================================================
           FILTRES APPLIQUÉS — Card monochrome
           ============================================================ */
        .filters-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #0453cb;
            border-radius: 6px;
            padding: 9px 12px;
            margin-top: 12px;
        }
        .filters-title {
            font-size: 9px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 5px;
        }
        .filters-list {
            font-size: 9px;
            color: #334155;
            line-height: 1.7;
        }
        .filters-list strong {
            color: #0f172a;
        }

        /* ============================================================
           FOOTER — Résumé + Info génération
           ============================================================ */
        .footer-section {
            margin-top: 14px;
            display: table;
            width: 100%;
        }
        .footer-left,
        .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 3px;
        }
        .summary-card,
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 9px 11px;
        }
        .info-card { margin-left: 4px; }
        .summary-title {
            font-size: 9.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .summary-grid { display: table; width: 100%; }
        .summary-row  { display: table-row; }
        .summary-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 2px;
        }
        .summary-value {
            font-size: 12px;
            font-weight: 700;
            color: #0453cb;
        }
        .summary-label {
            font-size: 8px;
            color: #64748b;
            margin-top: 1px;
        }
        .info-field { margin-bottom: 5px; }
        .info-label {
            font-size: 8px;
            color: #64748b;
            margin-bottom: 1px;
        }
        .info-value {
            font-size: 9.5px;
            font-weight: 600;
            color: #1f2937;
        }

        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        .empty-state {
            text-align: center;
            padding: 24px 10px;
            color: #64748b;
            font-size: 10px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="container">
        {{-- ===================================================
             HEADER — Logo | Infos école + Titre document
             =================================================== --}}
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    {{-- Col 1 : Logo --}}
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

                    {{-- Col 2 : Infos + titre + meta --}}
                    <td width="82%" style="background-color: {{ $hdrBg }}; padding: 12px 16px; vertical-align: middle;">
                        <div style="font-size: 15px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">{{ $etablissement['nom'] }}</div>

                        @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                        <div style="font-size: 8.5px; color: {{ $hdrText }}; opacity: 0.85; margin-bottom: 8px;">
                            @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                            @if($etablissement['telephone'])
                                @if($etablissement['adresse']) &nbsp;|&nbsp; @endif
                                Tél: {{ $etablissement['telephone'] }}
                            @endif
                            @if($etablissement['email'])
                                @if($etablissement['adresse'] || $etablissement['telephone']) &nbsp;|&nbsp; @endif
                                Email: {{ $etablissement['email'] }}
                            @endif
                        </div>
                        @endif

                        <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                            <div style="font-size: 12px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px; margin-bottom: 5px;">LISTE COMPLÈTE DES CLASSES</div>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="40%" style="font-size: 9px; color: {{ $hdrText }};">
                                        <span style="color: {{ $hdrText }}; opacity: 0.75;">Année universitaire :</span>
                                        <strong style="color: {{ $hdrText }};">{{ $anneeCourante->name ?? 'Non définie' }}</strong>
                                    </td>
                                    <td width="30%" style="font-size: 9px; color: {{ $hdrText }}; text-align: center;">
                                        <span style="color: {{ $hdrText }}; opacity: 0.75;">Date :</span>
                                        <strong style="color: {{ $hdrText }};">{{ $dateExport->format('d/m/Y') }}</strong>
                                    </td>
                                    <td width="30%" style="font-size: 9px; color: {{ $hdrText }}; text-align: right;">
                                        <span style="color: {{ $hdrText }}; opacity: 0.75;">Total :</span>
                                        <strong style="color: {{ $hdrText }};">{{ $totalClasses }} classe{{ $totalClasses > 1 ? 's' : '' }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ===================================================
             KPI BAND — 4 cellules uniformes
             =================================================== --}}
        <table class="kpi-band" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">TOTAL CLASSES</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 3px;">{{ $totalClasses }}</div>
                    <div style="font-size: 7.5px; color: white; opacity: 0.7;">{{ $classesActives }} active{{ $classesActives > 1 ? 's' : '' }}</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">EFFECTIF TOTAL</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 3px;">{{ $totalEffectif }}</div>
                    <div style="font-size: 7.5px; color: white; opacity: 0.7;">étudiants inscrits</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">CAPACITÉ TOTALE</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 3px;">{{ $totalCapacite }}</div>
                    <div style="font-size: 7.5px; color: white; opacity: 0.7;">places disponibles</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">TAUX REMPLISSAGE</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 3px;">{{ $tauxMoyenRemplissage }}%</div>
                    <div style="font-size: 7.5px; color: white; opacity: 0.7;">moyenne établissement</div>
                </td>
            </tr>
        </table>

        {{-- ===================================================
             TABLE DES CLASSES
             =================================================== --}}
        @if($totalClasses > 0)
            <table class="classes-table">
                <thead>
                    <tr>
                        <th class="col-num"   style="background-color: {{ $hdrBg }}; color: {{ $hdrText }};">N°</th>
                        <th class="col-name"  style="background-color: {{ $hdrBg }}; color: {{ $hdrText }}; text-align: left;">Nom de la classe</th>
                        <th class="col-code"  style="background-color: {{ $hdrBg }}; color: {{ $hdrText }};">Code</th>
                        <th class="col-filie" style="background-color: {{ $hdrBg }}; color: {{ $hdrText }}; text-align: left;">Filière</th>
                        <th class="col-niv"   style="background-color: {{ $hdrBg }}; color: {{ $hdrText }}; text-align: left;">Niveau</th>
                        <th class="col-eff"   style="background-color: {{ $hdrBg }}; color: {{ $hdrText }};">Effectif</th>
                        <th class="col-taux"  style="background-color: {{ $hdrBg }}; color: {{ $hdrText }};">Taux</th>
                        <th class="col-stat"  style="background-color: {{ $hdrBg }}; color: {{ $hdrText }};">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($classes as $index => $classe)
                        @php
                            $effectifActuel = $effectifs[$classe->id] ?? 0;
                            $capaciteMax    = $classe->places_totales ?? 0;
                            $tauxRemplissage = $capaciteMax > 0 ? round(($effectifActuel / $capaciteMax) * 100, 1) : 0;

                            $tauxBadge = 'badge-success';
                            if ($capaciteMax == 0) {
                                $tauxBadge = 'badge-muted';
                            } elseif ($tauxRemplissage >= 100) {
                                $tauxBadge = 'badge-danger';
                            } elseif ($tauxRemplissage >= 80) {
                                $tauxBadge = 'badge-warning';
                            }
                        @endphp
                        <tr>
                            <td class="col-num">
                                <span class="row-number" style="background-color: {{ $primary }};">{{ $index + 1 }}</span>
                            </td>
                            <td class="col-name">{{ $classe->name ?? 'N/A' }}</td>
                            <td class="col-code">
                                <span class="code-chip">{{ $classe->code ?? 'N/A' }}</span>
                            </td>
                            <td class="col-filie">{{ $classe->filiere->name ?? 'N/A' }}</td>
                            <td class="col-niv">{{ $classe->niveau->name ?? 'N/A' }}</td>
                            <td class="col-eff">
                                <span class="eff-value">{{ $effectifActuel }}</span>
                                <span class="eff-capacity">/ {{ $capaciteMax }}</span>
                            </td>
                            <td class="col-taux">
                                <span class="badge {{ $tauxBadge }}">
                                    {{ $capaciteMax > 0 ? $tauxRemplissage . '%' : '—' }}
                                </span>
                            </td>
                            <td class="col-stat">
                                @if($classe->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ===================================================
                 FILTRES APPLIQUÉS (si au moins un)
                 =================================================== --}}
            @if(!empty(array_filter($filters)))
                <div class="filters-card">
                    <div class="filters-title">Filtres appliqués</div>
                    <div class="filters-list">
                        @if(!empty($filters['search']))
                            • Recherche : <strong>{{ $filters['search'] }}</strong><br>
                        @endif
                        @if(!empty($filters['filiere_id']))
                            @php $filiere = \App\Models\ESBTPFiliere::find($filters['filiere_id']); @endphp
                            @if($filiere)
                                • Filière : <strong>{{ $filiere->name }}</strong><br>
                            @endif
                        @endif
                        @if(!empty($filters['niveau_id']))
                            @php $niveau = \App\Models\ESBTPNiveauEtude::find($filters['niveau_id']); @endphp
                            @if($niveau)
                                • Niveau : <strong>{{ $niveau->name }}</strong><br>
                            @endif
                        @endif
                        @if(!empty($filters['statut']))
                            • Statut : <strong>{{ $filters['statut'] === 'active' ? 'Classes actives' : 'Classes inactives' }}</strong><br>
                        @endif
                        @if(!empty($filters['capacite']))
                            @php
                                $capaciteLabel = [
                                    'disponible' => 'Classes avec places disponibles',
                                    'pleine'     => 'Classes pleines',
                                ][$filters['capacite']] ?? $filters['capacite'];
                            @endphp
                            • Capacité : <strong>{{ $capaciteLabel }}</strong><br>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ===================================================
                 FOOTER — Résumé + info génération
                 =================================================== --}}
            <div class="footer-section">
                <div class="footer-left">
                    <div class="summary-card">
                        <div class="summary-title">Résumé statistique</div>
                        <div class="summary-grid">
                            <div class="summary-row">
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: {{ $primary }};">{{ $totalClasses }}</div>
                                    <div class="summary-label">Classes</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: {{ $primary }};">{{ $classesActives }}</div>
                                    <div class="summary-label">Actives</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: {{ $primary }};">{{ $totalEffectif }}</div>
                                    <div class="summary-label">Étudiants</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: {{ $primary }};">{{ max(0, $totalCapacite - $totalEffectif) }}</div>
                                    <div class="summary-label">Places libres</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-right">
                    @php $pdfCfg = $pdfCfg ?? \App\Helpers\SettingsHelper::getPdfSettings(); @endphp
                    <div class="info-card">
                        <div class="summary-title">Informations document</div>
                        <div class="info-field">
                            <div class="info-label">Document généré le :</div>
                            <div class="info-value">{{ $dateExport->format('d/m/Y à H:i') }}</div>
                        </div>
                        @if(($pdfCfg['show_generator_name'] ?? true) && auth()->check())
                            <div class="info-field">
                                <div class="info-label">Par :</div>
                                <div class="info-value">{{ auth()->user()->name }}</div>
                            </div>
                        @endif
                        <div class="info-field">
                            <div class="info-label">Établissement :</div>
                            <div class="info-value">{{ $etablissement['nom'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="empty-state">
                Aucune classe ne correspond aux critères sélectionnés pour l'année {{ $anneeCourante->name ?? 'courante' }}.
            </div>
        @endif

        {{-- ===================================================
             GENERATION INFO — Bas de page
             =================================================== --}}
        <div class="generation-info">
            <strong>Document généré automatiquement le {{ $dateExport->format('d/m/Y à H:i') }}</strong><br>
            {{ $etablissement['nom'] }} — Système de gestion des classes
        </div>
    </div>
</body>
</html>
