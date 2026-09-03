<x-pdf-document
    title="État financier par frais"
    subtitle="Ce que chaque étudiant a soldé, frais par frais"
    :filters="$filtersRecap"
    orientation="landscape">

    <style>
        .ef-table { width:100%; border-collapse: collapse; font-size: 8.5pt; }
        .ef-table th { background: #0453cb; color:#fff; padding: 6px 4px; text-align:left; font-weight:700; }
        .ef-table td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .ef-table tr:nth-child(even) td { background:#f8fafc; }
        .ef-num { text-align: right; font-family: 'DejaVu Sans', sans-serif; white-space: nowrap; }
        .ef-mat { font-family: monospace; font-size: 7.5pt; color:#475569; }
        .ef-chip { display:inline-block; padding: 2px 7px; border-radius: 9px; font-size: 7pt; font-weight:700; }
        .ef-solde { background:#dcfce7; color:#15803d; }
        .ef-partiel { background:#fef3c7; color:#92400e; }
        .ef-rien { background:#fee2e2; color:#991b1b; }
        .ef-nature { background:#dbeafe; color:#1d4ed8; }
        .ef-inconnu { background:#f3f4f6; color:#4b5563; }
        .ef-synthese { width:100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8.5pt; }
        .ef-synthese td { padding: 5px 7px; border: 1px solid #e2e8f0; }
        .ef-synthese .ef-label { color:#64748b; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .4px; }
        .ef-note { font-size: 7.5pt; color:#64748b; margin-bottom: 9px; font-style: italic; }
    </style>

    @php
        $classeChip = [
            'Soldé' => 'ef-solde',
            'Partiel' => 'ef-partiel',
            'Aucun paiement' => 'ef-rien',
            'Déposé en nature' => 'ef-nature',
            'Montant non défini' => 'ef-inconnu',
        ];
        $money = fn ($v) => number_format((float) $v, 0, ',', ' ');

        // isFirstChunk est toujours fourni par PdfParLots, meme en rendu
        // direct : la synthese et la note ne paraissent qu'en tete du document.
    @endphp

    @if($isFirstChunk)
    <table class="ef-synthese">
        <tr>
            <td class="ef-label">Lignes</td>
            <td><strong>{{ $totaux['lignes'] }}</strong></td>
            <td class="ef-label">Soldées</td>
            <td><strong>{{ $totaux['soldees'] }}</strong></td>
            <td class="ef-label">Partielles</td>
            <td><strong>{{ $totaux['partielles'] }}</strong></td>
            <td class="ef-label">Sans paiement</td>
            <td><strong>{{ $totaux['sans_paiement'] }}</strong></td>
        </tr>
        <tr>
            <td class="ef-label">Total dû</td>
            <td class="ef-num"><strong>{{ $money($totaux['du']) }} FCFA</strong></td>
            <td class="ef-label">Total payé</td>
            <td class="ef-num"><strong>{{ $money($totaux['paye']) }} FCFA</strong></td>
            <td class="ef-label">Reste à recouvrer</td>
            <td class="ef-num" colspan="3"><strong>{{ $money($totaux['reste']) }} FCFA</strong></td>
        </tr>
    </table>

    <div class="ef-note">
        Situation cumulée à la date d'édition. Les versements en attente de validation sont comptés,
        comme sur la situation financière de l'étudiant. Un versement réparti sur plusieurs frais est
        imputé à chacun d'eux selon sa ventilation. Tout avoir validé est déduit du frais qu'il
        annule, qu'il ait été remboursé en caisse ou conservé en crédit sur le compte de l'étudiant —
        ce second cas explique qu'un frais paraisse ici moins couvert que sur la liste des paiements,
        qui totalise les mouvements de caisse.
    </div>
    @endif

    @if($lignes->isEmpty())
        <p>Aucune ligne ne correspond à ces filtres.</p>
    @else
        <table class="ef-table">
            <thead>
                <tr>
                    <th style="width: 11%;">Matricule</th>
                    <th style="width: 22%;">Étudiant</th>
                    <th style="width: 15%;">Classe</th>
                    <th style="width: 17%;">Frais</th>
                    <th style="width: 11%;" class="ef-num">Dû</th>
                    <th style="width: 11%;" class="ef-num">Payé</th>
                    <th style="width: 11%;" class="ef-num">Reste</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td class="ef-mat">{{ $ligne['matricule'] }}</td>
                        <td>{{ $ligne['etudiant'] }}</td>
                        <td>{{ $ligne['classe'] }}</td>
                        <td>{{ $ligne['frais'] }}</td>
                        <td class="ef-num">{{ $ligne['statut'] === 'Montant non défini' ? '—' : $money($ligne['du']) }}</td>
                        <td class="ef-num">{{ $money($ligne['paye']) }}</td>
                        <td class="ef-num">{{ $ligne['statut'] === 'Montant non défini' ? '—' : $money($ligne['reste']) }}</td>
                        <td>
                            <span class="ef-chip {{ $classeChip[$ligne['statut']] ?? 'ef-inconnu' }}">{{ $ligne['statut'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-pdf-document>
