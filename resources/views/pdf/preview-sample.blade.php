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
    :overrides="$overrides ?? []"
    signature-block="director">

    <style>
        .preview-kpi-row { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .preview-kpi-cell {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
            background: #f8fafc;
        }
        .preview-kpi-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 4px;
        }
        .preview-kpi-value {
            font-size: 14px;
            font-weight: 700;
            color: #0453cb;
        }
        .preview-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .preview-table th {
            background: #0453cb;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #033a8e;
        }
        .preview-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .preview-table tr:nth-child(even) td { background: #f8fafc; }
        .preview-table tfoot td {
            background: #eff6ff;
            font-weight: 700;
            color: #0453cb;
            border-top: 2px solid #0453cb;
            padding: 9px 10px;
        }
        .preview-section-title {
            font-size: 11px;
            font-weight: 700;
            color: #0453cb;
            margin: 14px 0 6px;
            text-transform: uppercase;
            letter-spacing: .5px;
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

    {{-- KPIs synthèse --}}
    <table class="preview-kpi-row">
        <tr>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Étudiants concernés</div>
                <div class="preview-kpi-value">42</div>
            </td>
            <td style="width: 1%;"></td>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Total attendu</div>
                <div class="preview-kpi-value">12 600 000 FCFA</div>
            </td>
            <td style="width: 1%;"></td>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Total payé</div>
                <div class="preview-kpi-value">8 740 000 FCFA</div>
            </td>
            <td style="width: 1%;"></td>
            <td class="preview-kpi-cell" style="width: 25%;">
                <div class="preview-kpi-label">Taux recouvrement</div>
                <div class="preview-kpi-value">69 %</div>
            </td>
        </tr>
    </table>

    <div class="preview-section-title">Détail par étudiant</div>

    <table class="preview-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Étudiant</th>
                <th>Matricule</th>
                <th style="text-align: right;">Attendu</th>
                <th style="text-align: right;">Payé</th>
                <th style="text-align: right;">Solde</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rows = [
                    ['Aïcha KOUAME',     'MESBTP24-0102', 300000, 300000, 0],
                    ['Bakary DIABATÉ',   'MESBTP24-0103', 300000, 200000, 100000],
                    ['Christian YAO',    'MESBTP24-0104', 300000, 150000, 150000],
                    ['Djeneba TRAORÉ',   'MESBTP24-0105', 300000, 300000, 0],
                    ['Élise NIAMBA',     'MESBTP24-0106', 300000, 0,      300000],
                    ['Fanta KONÉ',       'MESBTP24-0107', 300000, 250000, 50000],
                    ['Gérard KOFFI',     'MESBTP24-0108', 300000, 300000, 0],
                ];
            @endphp
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row[0] }}</td>
                    <td>{{ $row[1] }}</td>
                    <td style="text-align: right;">{{ number_format($row[2], 0, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format($row[3], 0, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format($row[4], 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total ({{ count($rows) }} étudiants)</td>
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
