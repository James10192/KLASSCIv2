<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <title>{{ $context['title'] ?? 'Tableau détaillé des paiements' }}</title>
    @php
        // Settings PDF chargés UNE fois — réutilisés dans <style> ET dans le body
        // (header colors, signature, watermark, etc.). Évite un double appel à
        // SettingsHelper::getPdfSettings() (qui hit le cache mais reste 2 lookups).
        $pdfCfg          = \App\Helpers\SettingsHelper::getPdfSettings();
        $signatureHeight = (int) ($pdfCfg['signature_height'] ?? 80);
    @endphp
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
        /* NB : pas de text-transform:uppercase — DomPDF mangles les accents (rule exports-pdf-excel.md).
           Les libellés (Période, Étudiant, …) restent en casse naturelle pour préserver les accents. */
        .filters-bar .filter-label {
            font-weight: 600; color: #475569;
            font-size: 7.5px;
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
        }
        /* Headers thead : casse pré-mise en majuscules en PHP via mb_strtoupper (UTF-8 safe).
           text-transform retiré pour préserver É → É (sinon DomPDF affiche é dégradé). */
        table.payments thead th {
            background: #0453cb;
            color: #fff;
            font-weight: 600;
            text-align: left;
            padding: 6px 5px;
            font-size: {{ $showCreator ? '8px' : '9px' }};
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

        /* Badge libellés (VALIDÉ, EN ATTENTE, REJETÉ) — pré-majuscules via mb_strtoupper en PHP.
           text-transform retiré pour conserver les accents (É) sur DomPDF. */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 99px;
            font-size: 7px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.3px;
        }
        .badge-valid { background: #16a34a; }
        .badge-pending { background: #f59e0b; }
        .badge-rejected { background: #dc2626; }
        .badge-default { background: #64748b; }

        /* ─── Footer total (vraie table — display:table-row/cell sur div crashe DomPDF) ─── */
        table.totals {
            width: 100%;
            margin-top: 10px;
            background: #eff6ff;
            border-left: 4px solid #0453cb;
            border-collapse: collapse;
        }
        table.totals td {
            padding: 4px 10px;
            background: #eff6ff;
        }
        /* Totaux : libellés pré-majuscules en HTML — text-transform corrompt les accents DomPDF. */
        table.totals .label {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            letter-spacing: 0.4px;
        }
        table.totals .value {
            font-size: 12px;
            font-weight: 700;
            color: #0453cb;
        }

        /* ─── Signature & cachet (emplacement spacieux, configurable via pdf_signature_height) ─── */
        .signature-section {
            margin-top: 16px;
            display: table;
            width: 100%;
        }
        .signature-cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 6px;
        }
        .signature-box {
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            min-height: {{ max(80, (int) $signatureHeight) }}px;
            padding: 8px 10px;
            background: #ffffff;
        }
        /* Signature labels : pré-majuscules en HTML pour préserver Comptabilité (accent é). */
        .signature-label {
            font-size: 8px;
            color: #64748b;
            letter-spacing: 0.4px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .signature-name {
            font-size: 9px;
            font-weight: 600;
            color: #1f2937;
            margin-top: 4px;
        }
        .signature-img {
            max-height: {{ max(40, (int) $signatureHeight - 20) }}px;
            max-width: 200px;
            display: block;
            margin: 4px auto;
        }

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
    // $pdfCfg / $signatureHeight déjà chargés au-dessus du <style> — on ne les re-fetch pas.
    $etablissement = [
        'nom' => \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI'),
        'adresse' => \App\Helpers\SettingsHelper::get('school_address', ''),
        'telephone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
        'email' => \App\Helpers\SettingsHelper::get('school_email', ''),
        'logo' => \App\Helpers\SettingsHelper::get('school_logo', ''),
    ];
    $hdrBg         = $pdfCfg['header_bg_color']  ?? $pdfCfg['primary_color'] ?? '#0453cb';
    $hdrText       = $pdfCfg['header_text_color'] ?? '#ffffff';
    $primary       = $pdfCfg['primary_color']     ?? '#0453cb';
    $secondary     = $pdfCfg['secondary_color']   ?? '#64748b';
    $showGenerator = $pdfCfg['show_generator_name'] ?? true;
    $directorName  = \App\Helpers\SettingsHelper::get('director_name', '');
    $directorTitle = \App\Helpers\SettingsHelper::get('director_title', 'Directeur Général');

    // Labels pré-majuscules via mb_strtoupper (UTF-8 safe — sinon "VALIDé" sur DomPDF avec
    // text-transform CSS, voir rule exports-pdf-excel.md). On retire text-transform du badge.
    $statusBadge = function ($status) {
        $normalized = mb_strtolower(trim($status ?? ''), 'UTF-8');
        return match ($normalized) {
            'validé', 'valide' => ['VALIDÉ', 'badge-valid'],
            'en_attente', 'en attente' => ['EN ATTENTE', 'badge-pending'],
            'rejeté', 'rejete' => ['REJETÉ', 'badge-rejected'],
            'annulé', 'annule' => ['ANNULÉ', 'badge-default'],
            '' => ['—', 'badge-default'],
            default => [mb_strtoupper($status, 'UTF-8'), 'badge-default'],
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
                            {{ mb_strtoupper($context['title'] ?? 'Tableau détaillé des paiements', 'UTF-8') }}
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
            {{-- mb_strtoupper(..., 'UTF-8') sur les libellés français préserve les accents
                 (Étudiant, Reçu, Encaissé) — text-transform CSS les corrompt sur DomPDF. --}}
            <tr>
                <th class="col-date">{{ mb_strtoupper('Date', 'UTF-8') }}</th>
                <th class="col-recu">{{ mb_strtoupper('N° Reçu', 'UTF-8') }}</th>
                <th class="col-etudiant">{{ mb_strtoupper('Étudiant', 'UTF-8') }}</th>
                <th class="col-classe">{{ mb_strtoupper('Classe', 'UTF-8') }}</th>
                <th class="col-mode">{{ mb_strtoupper('Mode', 'UTF-8') }}</th>
                <th class="col-montant">{{ mb_strtoupper('Montant', 'UTF-8') }}</th>
                <th class="col-status">{{ mb_strtoupper('Statut', 'UTF-8') }}</th>
                @if($showCreator)
                    <th class="col-creator">{{ mb_strtoupper('Encaissé par', 'UTF-8') }}</th>
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

    {{-- Footer total (vraie <table> — pas de div avec display:table-* qui crashe DomPDF) --}}
    <table class="totals" cellspacing="0" cellpadding="0">
        <tr>
            <td><span class="label">{{ mb_strtoupper('Nombre de paiements', 'UTF-8') }} :</span></td>
            <td style="text-align:right;"><span class="value">{{ $count }}</span></td>
        </tr>
        <tr>
            <td><span class="label">{{ mb_strtoupper('Total encaissé', 'UTF-8') }} :</span></td>
            <td style="text-align:right;"><span class="value">{{ $formatMontant($totalMontant) }} FCFA</span></td>
        </tr>
    </table>

    {{-- Signature & cachet (emplacement spacieux, configurable via pdf_signature_height) --}}
    <div class="signature-section">
        <div class="signature-cell">
            <div class="signature-box">
                <div class="signature-label">{{ mb_strtoupper('Signature & Cachet', 'UTF-8') }}</div>
                @if(!empty($pdfCfg['signature_director']) && file_exists(storage_path('app/public/' . $pdfCfg['signature_director'])))
                    <img class="signature-img"
                         src="data:image/{{ pathinfo($pdfCfg['signature_director'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $pdfCfg['signature_director']))) }}"
                         alt="Signature directeur">
                @endif
                @if($directorName)
                    <div class="signature-name">{{ $directorName }}</div>
                    <div style="font-size: 8px; color: {{ $secondary }};">{{ $directorTitle }}</div>
                @endif
            </div>
        </div>
        <div class="signature-cell">
            <div class="signature-box">
                <div class="signature-label">{{ mb_strtoupper('Visa Comptabilité', 'UTF-8') }}</div>
            </div>
        </div>
    </div>

    {{-- Generation info (signature document, identique à liste-complete-pdf, respecte pdf_show_generator_name) --}}
    <div class="generation-info">
        <strong>Document généré automatiquement le {{ $dateGeneration->format('d/m/Y à H:i') }}</strong>
        @if($showGenerator && auth()->check())
            par {{ auth()->user()->name }}
        @endif
        <br>
        {{ $etablissement['nom'] ?? 'KLASSCI' }} — Système de Gestion des Paiements
    </div>
</div>
</body>
</html>
