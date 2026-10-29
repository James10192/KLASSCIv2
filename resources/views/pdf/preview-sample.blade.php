{{--
    Aperçu PDF — démontre le rendu d'un export type avec les overrides en cours
    de configuration. Le contenu est fictif mais représentatif d'un rapport
    KLASSCI réel (KPIs en haut, tableau de données, totaux en bas).

    Phase 9 — Customisation PDF tenant.
--}}
<x-pdf-document
    title="Aperçu — Rapport de paiements"
    subtitle="Document de démonstration · Rendu avec vos paramètres actuels"
    :filters="[
        'Filière' => 'BTS Comptabilité',
        'Niveau' => 'BTS 1',
        'Période' => 'Avril ' . now()->year,
        'Catégorie' => 'Scolarité annuelle',
    ]"
    orientation="portrait"
    :overrides="$overrides ?? []">

    @php
        $previewPdf = array_merge(\App\Helpers\SettingsHelper::getPdfSettings(), $overrides ?? []);
        $previewPrimary = $previewPdf['primary_color'] ?? '#0453cb';
        $previewText = $previewPdf['text_color'] ?? '#1f2937';
    @endphp
    <style>
        /* KPI bar pattern liste-complete-pdf : fond primary plein, valeurs blanches */
        .preview-kpi-row { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .preview-kpi-cell {
            background-color: {{ $previewPrimary }};
            padding: 10px 8px;
            text-align: center;
            vertical-align: middle;
            border-right: 1px solid rgba(255,255,255,0.25);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .preview-kpi-cell:last-child { border-right: 0; }
        .preview-kpi-label {
            font-size: 7.5px;
            font-weight: 600;
            color: #fff;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .preview-kpi-value {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 3px;
        }
        .preview-kpi-sublabel {
            font-size: 7px;
            color: #fff;
            opacity: 0.65;
        }

        /* Table pattern liste-complete-pdf : badges student-number + matricule mono */
        .preview-table { width: 100%; border-collapse: collapse; font-size: 9px; background: white; }
        .preview-table th {
            background: {{ $previewPrimary }};
            color: #fff;
            padding: 7px 6px;
            text-align: center;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .preview-table td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            color: {{ $previewText }};
        }
        .preview-table tbody tr:nth-child(even) td { background-color: #f8f9fa; }
        .preview-table tfoot td {
            background: #eff6ff;
            font-weight: 700;
            color: {{ $previewPrimary }};
            border-top: 2px solid {{ $previewPrimary }};
            padding: 9px 6px;
        }
        .preview-num-badge {
            background: {{ $previewPrimary }};
            color: #fff;
            padding: 2px 6px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 9px;
            display: inline-block;
            min-width: 18px;
        }
        .preview-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 2px 5px;
            border-radius: 2px;
            font-size: 8.5px;
            color: #374151;
            display: inline-block;
        }
        .preview-name { font-weight: 600; color: #1f2937; text-align: left; }
        .preview-section-title {
            font-size: 11px;
            font-weight: 700;
            color: {{ $previewPrimary }};
            margin: 14px 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .preview-note {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            padding: 8px 12px;
            font-size: 9px;
            color: #78350f;
            margin-top: 14px;
            border-radius: 2px;
        }
    </style>

    {{-- KPIs synthèse — pattern liste-complete-pdf : fond bleu plein, valeurs blanches --}}
    <table class="preview-kpi-row">
        <tr>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Étudiants</div>
                <div class="preview-kpi-value">42</div>
                <div class="preview-kpi-sublabel">Concernés</div>
            </td>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Attendu</div>
                <div class="preview-kpi-value">12 600 000</div>
                <div class="preview-kpi-sublabel">FCFA</div>
            </td>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Payé</div>
                <div class="preview-kpi-value">8 740 000</div>
                <div class="preview-kpi-sublabel">FCFA</div>
            </td>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Recouvrement</div>
                <div class="preview-kpi-value">69 %</div>
                <div class="preview-kpi-sublabel">Taux global</div>
            </td>
        </tr>
    </table>

    <div class="preview-section-title">Détail par étudiant</div>

    <table class="preview-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 90px;">Matricule</th>
                <th>Nom &amp; prénoms</th>
                <th style="width: 80px;">Attendu</th>
                <th style="width: 80px;">Payé</th>
                <th style="width: 80px;">Solde</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rows = [
                    ['Aïcha KOUAME',     'MAT24-0102', 300000, 300000, 0],
                    ['Bakary DIABATÉ',   'MAT24-0103', 300000, 200000, 100000],
                    ['Christian YAO',    'MAT24-0104', 300000, 150000, 150000],
                    ['Djeneba TRAORÉ',   'MAT24-0105', 300000, 300000, 0],
                    ['Élise NIAMBA',     'MAT24-0106', 300000, 0,      300000],
                    ['Fanta KONÉ',       'MAT24-0107', 300000, 250000, 50000],
                    ['Gérard KOFFI',     'MAT24-0108', 300000, 300000, 0],
                ];
            @endphp
            @foreach($rows as $i => $row)
                <tr>
                    <td><span class="preview-num-badge">{{ $i + 1 }}</span></td>
                    <td><span class="preview-matricule">{{ $row[1] }}</span></td>
                    <td class="preview-name">{{ $row[0] }}</td>
                    <td style="text-align: right;">{{ number_format($row[2], 0, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format($row[3], 0, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format($row[4], 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: left; padding-left: 10px;">Total ({{ count($rows) }} étudiants)</td>
                <td style="text-align: right;">{{ number_format(array_sum(array_column($rows, 2)), 0, ',', ' ') }}</td>
                <td style="text-align: right;">{{ number_format(array_sum(array_column($rows, 3)), 0, ',', ' ') }}</td>
                <td style="text-align: right;">{{ number_format(array_sum(array_column($rows, 4)), 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="preview-note">
        Ceci est un document d'aperçu généré avec vos paramètres en cours d'édition.
        Aucune donnée réelle n'apparaît ci-dessus. Une fois vos paramètres enregistrés,
        tous vos exports PDF (recouvrement, analytics, bulletins, certificats…)
        utiliseront ce rendu.
    </div>
</x-pdf-document>
