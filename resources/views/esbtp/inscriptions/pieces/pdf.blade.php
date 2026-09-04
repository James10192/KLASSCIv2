{{-- Rapport des pieces manquantes : le document qu'on emporte pour constituer
     physiquement les dossiers a remettre aux ministeres. --}}
<x-pdf-document
    :title="$reportTitle ?? 'Pieces manquantes aux dossiers'"
    :subtitle="$reportSubtitle ?? null"
    :filters="$reportFilters ?? []"
    orientation="landscape">

    <style>
        .pm-kpis { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .pm-kpis td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 9px;
            text-align: center;
        }
        .pm-kpis .pm-kpi-value { font-size: 14px; font-weight: bold; color: #0453cb; }
        .pm-kpis .pm-kpi-label { color: #64748b; }

        .pm-section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0453cb;
            margin: 10px 0 4px;
        }

        .pm-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .pm-table th {
            background: #0453cb;
            color: #fff;
            padding: 5px 6px;
            text-align: left;
            font-weight: bold;
        }
        .pm-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .pm-table tr:nth-child(even) td { background: #f8fafc; }
        .pm-num { text-align: center; }
        .pm-obligatoire { color: #dc2626; font-weight: bold; }
        .pm-vide { padding: 14px; text-align: center; color: #64748b; font-size: 10px; }
    </style>

    @php
        $kpis = $kpis ?? [];
        $parPiece = $parPiece ?? [];
        $lignes = $lignes ?? [];
    @endphp

    <table class="pm-kpis">
        <tr>
            <td>
                <div class="pm-kpi-value">{{ $kpis['inscriptions_incompletes'] ?? 0 }}</div>
                <div class="pm-kpi-label">Dossiers incomplets</div>
            </td>
            <td>
                <div class="pm-kpi-value">{{ $kpis['pieces_manquantes'] ?? 0 }}</div>
                <div class="pm-kpi-label">Pieces manquantes</div>
            </td>
            <td>
                <div class="pm-kpi-value">{{ $kpis['exemplaires_manquants'] ?? 0 }}</div>
                <div class="pm-kpi-label">Exemplaires a reunir</div>
            </td>
            <td>
                <div class="pm-kpi-value">{{ $kpis['inscriptions_examinees'] ?? 0 }}</div>
                <div class="pm-kpi-label">Inscriptions examinees</div>
            </td>
        </tr>
    </table>

    <div class="pm-section-title">SYNTHESE PAR PIECE</div>
    <table class="pm-table">
        <thead>
            <tr>
                <th>Piece</th>
                <th style="width: 90px;">Obligatoire</th>
                <th style="width: 100px;">Etudiants</th>
                <th style="width: 110px;">Exemplaires</th>
            </tr>
        </thead>
        <tbody>
            @forelse($parPiece as $piece)
                <tr>
                    <td>{{ $piece['libelle'] }}</td>
                    <td class="pm-num">
                        @if($piece['obligatoire'])
                            <span class="pm-obligatoire">Oui</span>
                        @else
                            Non
                        @endif
                    </td>
                    <td class="pm-num">{{ $piece['etudiants'] }}</td>
                    <td class="pm-num">{{ $piece['exemplaires'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="pm-vide">Aucune piece manquante sur ce perimetre.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pm-section-title">DETAIL PAR ETUDIANT</div>
    <table class="pm-table">
        <thead>
            <tr>
                <th style="width: 90px;">Matricule</th>
                <th style="width: 150px;">Etudiant</th>
                <th style="width: 110px;">Classe</th>
                <th style="width: 90px;">Annee</th>
                <th>Pieces manquantes</th>
                <th style="width: 55px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['matricule'] ?: '—' }}</td>
                    <td>{{ $ligne['etudiant'] }}</td>
                    <td>{{ $ligne['classe'] ?: '—' }}</td>
                    <td>{{ $ligne['annee'] ?: '—' }}</td>
                    <td>
                        {{-- Une seule chaine construite en PHP : coller des directives Blade
                             les unes aux autres (@@endif@@if) laisse la seconde litterale. --}}
                        @php
                            $_morceaux = [];
                            foreach ($ligne['manquantes'] as $_m) {
                                $_texte = $_m['libelle'];
                                if ($_m['exemplaires_manquants'] > 1) {
                                    $_texte .= ' (' . $_m['exemplaires_manquants'] . ')';
                                }
                                if ($_m['obligatoire']) {
                                    $_texte .= ' *';
                                }
                                $_morceaux[] = $_texte;
                            }
                        @endphp
                        {{ implode(', ', $_morceaux) }}
                    </td>
                    <td class="pm-num">{{ $ligne['nb_manquantes'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="pm-vide">Aucun dossier incomplet sur ce perimetre.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-pdf-document>
