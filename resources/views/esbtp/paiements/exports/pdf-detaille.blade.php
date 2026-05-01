<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <title>{{ $context['title'] ?? 'Tableau détaillé des paiements' }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4 {{ $showCreator ? 'landscape' : 'portrait' }};
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $showCreator ? '9px' : '10px' }};
            margin: 0;
            padding: 8px;
            color: #1f2937;
            line-height: 1.3;
            background: white;
        }
        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        /* ─── Header (aligné sur liste-complete-pdf : table 2 colonnes logo|infos)
              Lot 17e fix : on retire border-radius + overflow:hidden qui combinés
              avec 2 tables sœurs perturbent le rendu DomPDF — l'apparence reste
              homogène grâce au background uni du <td> et au border-top tonal. */
        .header-section {
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        /* ─── Filters bar ─── */
        .filters-bar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 8.5px;
        }
        .filters-bar .filter-item { display: inline-block; margin-right: 12px; }
        .filters-bar .filter-label {
            font-weight: 600; color: #475569;
            text-transform: uppercase; font-size: 7.5px;
            letter-spacing: 0.3px;
        }
        .filters-bar .filter-value { color: #0f172a; font-weight: 600; }

        /* ─── Table ─── */
        table.payments {
            width: 100%;
            border-collapse: collapse;
            font-size: {{ $showCreator ? '8.5px' : '9.5px' }};
            margin-top: 8px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
        }
        table.payments thead th {
            background: #0453cb;
            color: #fff;
            font-weight: 600;
            text-align: left;
            padding: 6px 5px;
            font-size: {{ $showCreator ? '8px' : '9px' }};
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-right: 1px solid rgba(255,255,255,.15);
        }
        table.payments thead th:last-child { border-right: 0; }
        table.payments tbody td {
            padding: 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        table.payments tbody tr:nth-child(even) td { background: #f8fafc; }

        .col-date { width: 9%; }
        .col-recu { width: 10%; font-family: 'Courier New', monospace; font-size: {{ $showCreator ? '8px' : '8.5px' }}; }
        .col-etudiant { width: {{ $showCreator ? '22%' : '26%' }}; }
        .col-classe { width: {{ $showCreator ? '12%' : '14%' }}; }
        .col-mode { width: 10%; }
        .col-montant { width: 11%; text-align: right; font-weight: 700; }
        .col-status { width: 9%; text-align: center; }
        .col-creator { width: 17%; }

        .matricule {
            font-family: 'Courier New', monospace;
            font-size: {{ $showCreator ? '7.5px' : '8.5px' }};
            color: #475569;
            display: block;
        }
        .nom {
            font-weight: 600;
            color: #1f2937;
            display: block;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 99px;
            font-size: 7px;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-valid { background: #16a34a; }
        .badge-pending { background: #f59e0b; }
        .badge-rejected { background: #dc2626; }
        .badge-default { background: #64748b; }

        /* ─── Footer total ─── */
        .totals {
            margin-top: 10px;
            background: #eff6ff;
            border-radius: 4px;
            padding: 8px 10px;
            border-left: 4px solid #0453cb;
        }
        .totals .label {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .totals .value {
            font-size: 12px;
            font-weight: 700;
            color: #0453cb;
        }
        .totals .row { display: table-row; }
        .totals .cell { display: table-cell; padding: 2px 0; }

        /* ─── Generation info (cohérence avec liste-complete-pdf) ─── */
        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
@php
    // Lot 17d — Header aligné sur le pattern liste-complete-pdf : on récupère
    // les infos établissement via SettingsHelper (fallback KLASSCI).
    $etablissement = [
        'nom' => \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI'),
        'adresse' => \App\Helpers\SettingsHelper::get('school_address', ''),
        'telephone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
        'email' => \App\Helpers\SettingsHelper::get('school_email', ''),
        'logo' => \App\Helpers\SettingsHelper::get('school_logo', ''),
    ];
    $pdfCfg  = \App\Helpers\SettingsHelper::getPdfSettings();
    $hdrBg   = $pdfCfg['header_bg_color']  ?? $pdfCfg['primary_color'] ?? '#0453cb';
    $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
    $primary = $pdfCfg['primary_color']     ?? '#0453cb';

    $statusBadge = function ($status) {
        $normalized = strtolower(trim($status ?? ''));
        return match ($normalized) {
            'validé', 'valide' => ['Validé', 'badge-valid'],
            'en_attente', 'en attente' => ['En attente', 'badge-pending'],
            'rejeté', 'rejete' => ['Rejeté', 'badge-rejected'],
            'annulé', 'annule' => ['Annulé', 'badge-default'],
            '' => ['—', 'badge-default'],
            default => [ucfirst($status), 'badge-default'],
        };
    };
    $formatMontant = fn ($m) => number_format((float) $m, 0, ',', ' ');
@endphp

<div class="container">
    {{-- Header Section — pattern liste-complete-pdf (2 colonnes : Logo | Infos école + titre document)
         Lot 17e fix DomPDF : la rangée méta (Lignes / Date / Total) a été extraite en table SŒUR
         pour éviter la table imbriquée dans un <td> parent qui crashe DomPDF (get_cellmap() on null). --}}
    <div class="header-section">
        {{-- Bandeau principal : Logo (gauche) + École/Titre (droite), sans table imbriquée --}}
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
            <tr>
                {{-- Colonne gauche : Logo --}}
                <td width="18%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                    @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                        <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}"
                             style="max-height: 55px; max-width: 100px; filter: brightness(0) invert(1);" alt="Logo">
                    @else
                        <div style="font-size: 30px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                    @endif
                </td>
                {{-- Colonne droite : Nom école + contact + titre document (zéro table imbriquée) --}}
                <td width="82%" style="background-color: {{ $hdrBg }}; padding: 12px 16px; vertical-align: middle;">
                    {{-- Nom établissement --}}
                    <div style="font-size: 15px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                    {{-- Adresse | Tél | Email --}}
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
                    {{-- Titre document (rangée méta extraite en dessous, hors du <td>) --}}
                    <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                        <div style="font-size: 12px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px;">
                            {{ strtoupper($context['title'] ?? 'TABLEAU DÉTAILLÉ DES PAIEMENTS') }}
                        </div>
                        @if(! empty($context['subtitle_creator']))
                            <div style="font-size: 8.5px; color: {{ $hdrText }}; opacity: 0.85; margin-top: 5px; font-style: italic;">
                                {{ $context['subtitle_creator'] }}
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- Méta-row (Lignes / Date / Total) — table SŒUR, plus imbriquée → DomPDF safe --}}
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-top: 0;">
            <tr>
                <td width="18%" style="background-color: {{ $hdrBg }}; padding: 6px 10px 10px; border-top: 1px solid rgba(255,255,255,0.25); border-right: 2px solid rgba(255,255,255,0.25);"></td>
                <td width="27%" style="background-color: {{ $hdrBg }}; padding: 6px 8px 10px 16px; font-size: 9px; color: {{ $hdrText }}; border-top: 1px solid rgba(255,255,255,0.18);">
                    <span style="color: {{ $hdrText }}; opacity: 0.75;">Lignes :</span>
                    <strong style="color: {{ $hdrText }};">{{ $count }}</strong>
                </td>
                <td width="28%" style="background-color: {{ $hdrBg }}; padding: 6px 8px 10px; font-size: 9px; color: {{ $hdrText }}; text-align: center; border-top: 1px solid rgba(255,255,255,0.18);">
                    <span style="color: {{ $hdrText }}; opacity: 0.75;">Date :</span>
                    <strong style="color: {{ $hdrText }};">{{ $dateGeneration->format('d/m/Y H:i') }}</strong>
                </td>
                <td width="27%" style="background-color: {{ $hdrBg }}; padding: 6px 16px 10px 8px; font-size: 9px; color: {{ $hdrText }}; text-align: right; border-top: 1px solid rgba(255,255,255,0.18);">
                    <span style="color: {{ $hdrText }}; opacity: 0.75;">Total :</span>
                    <strong style="color: {{ $hdrText }};">{{ $formatMontant($totalMontant) }} FCFA</strong>
                </td>
            </tr>
        </table>
    </div>

    {{-- Filters bar (résumé filtres appliqués) --}}
    @if(! empty($filtersSummary))
    <div class="filters-bar">
        @foreach($filtersSummary as $f)
            <span class="filter-item">
                <span class="filter-label">{{ $f['label'] }}:</span>
                <span class="filter-value">{{ $f['value'] }}</span>
            </span>
        @endforeach
    </div>
    @endif

    {{-- Table paiements --}}
    <table class="payments">
        <thead>
            <tr>
                <th class="col-date">Date</th>
                <th class="col-recu">N° Reçu</th>
                <th class="col-etudiant">Étudiant</th>
                <th class="col-classe">Classe</th>
                <th class="col-mode">Mode</th>
                <th class="col-montant">Montant</th>
                <th class="col-status">Statut</th>
                @if($showCreator)
                    <th class="col-creator">Encaissé par</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($paiements as $p)
                @php
                    [$statusLabel, $statusClass] = $statusBadge($p->status ?? $p->statut ?? '');
                    $etu = $p->etudiant;
                    $cls = optional($p->inscription)->classe;
                    $creator = $p->createdBy;
                @endphp
                <tr>
                    <td class="col-date">{{ $p->date_paiement ? $p->date_paiement->format('d/m/Y') : '—' }}</td>
                    <td class="col-recu">{{ $p->numero_recu ?? '—' }}</td>
                    <td class="col-etudiant">
                        @if($etu)
                            <span class="matricule">{{ $etu->matricule }}</span>
                            <span class="nom">{{ $etu->nom }} {{ $etu->prenoms }}</span>
                        @else
                            <span style="color:#94a3b8;">—</span>
                        @endif
                    </td>
                    <td class="col-classe">{{ $cls?->name ?? '—' }}</td>
                    <td class="col-mode">{{ $p->mode_paiement ?? '—' }}</td>
                    <td class="col-montant">{{ $formatMontant($p->montant) }}</td>
                    <td class="col-status">
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    @if($showCreator)
                        <td class="col-creator">{{ $creator?->name ?? '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showCreator ? 8 : 7 }}" style="text-align:center; padding:20px; color:#94a3b8;">
                        Aucun paiement à afficher.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer total (cohérent avec liste-complete-pdf) --}}
    <div class="totals">
        <div class="row">
            <div class="cell"><span class="label">Nombre de paiements :</span></div>
            <div class="cell" style="text-align:right;"><span class="value">{{ $count }}</span></div>
        </div>
        <div class="row">
            <div class="cell"><span class="label">Total encaissé :</span></div>
            <div class="cell" style="text-align:right;"><span class="value">{{ $formatMontant($totalMontant) }} FCFA</span></div>
        </div>
    </div>

    {{-- Generation info (signature document, identique à liste-complete-pdf) --}}
    <div class="generation-info">
        <strong>Document généré automatiquement le {{ $dateGeneration->format('d/m/Y à H:i') }}</strong>
        @if(auth()->check())
            par {{ auth()->user()->name }}
        @endif
        <br>
        {{ $etablissement['nom'] ?? 'KLASSCI' }} — Système de Gestion des Paiements
    </div>
</div>
</body>
</html>
