<x-pdf-document
    :title="$reportTitle ?? 'Suivi accessibilité étudiants'"
    :subtitle="$reportSubtitle ?? 'Aménagements et adaptations pédagogiques'"
    :filters="$reportFilters ?? []"
    orientation="landscape">

    <style>
        .acc-kpis-table { width: 100%; margin: 8px 0 14px; border-collapse: separate; border-spacing: 4px 0; }
        .acc-kpi {
            background: #0453cb; color: #fff;
            padding: 8px 10px; text-align: center;
            border-radius: 4px;
            -webkit-print-color-adjust: exact; color-adjust: exact;
        }
        .acc-kpi-value { font-size: 16px; font-weight: 700; }
        .acc-kpi-label { font-size: 8px; opacity: .85; text-transform: uppercase; letter-spacing: .04em; }

        .acc-table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        .acc-table th {
            background: #0453cb; color: #fff;
            padding: 6px 5px; text-align: left;
            font-size: 8px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .04em;
            -webkit-print-color-adjust: exact; color-adjust: exact;
        }
        .acc-table td { padding: 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .acc-table tbody tr:nth-child(even) { background: #f8fafc; }

        .acc-mat { font-family: 'Courier New', monospace; font-size: 7.5px; color: #475569; }
        .acc-name { font-weight: 600; color: #1e293b; font-size: 8.5px; }

        .acc-pill {
            display: inline-block;
            padding: 1.5px 6px;
            border-radius: 50px;
            font-size: 7px;
            font-weight: 600;
            margin: 1px 1px 1px 0;
            -webkit-print-color-adjust: exact; color-adjust: exact;
        }
        .acc-pill--cat { background: #eff6ff; color: #0453cb; }
        .acc-pill--acc { background: #f0fdf4; color: #065f46; }
        .acc-pill--ttp { background: #dbeafe; color: #1e40af; font-weight: 700; }
        .acc-pill--rec { background: #fef3c7; color: #78350f; }

        .acc-empty {
            text-align: center; color: #94a3b8;
            padding: 30px;
        }

        .acc-section-title {
            font-size: 10px; font-weight: 700;
            color: #0453cb; text-transform: uppercase;
            letter-spacing: .05em;
            margin: 14px 0 6px;
            border-bottom: 2px solid #0453cb;
            padding-bottom: 3px;
        }
    </style>

    {{-- KPIs --}}
    <table class="acc-kpis-table">
        <tr>
            <td class="acc-kpi" style="width:25%;">
                <div class="acc-kpi-value">{{ $kpis['total'] ?? 0 }}</div>
                <div class="acc-kpi-label">Étudiants suivis</div>
            </td>
            <td class="acc-kpi" style="width:25%;">
                <div class="acc-kpi-value">{{ $kpis['tiers_temps'] ?? 0 }}</div>
                <div class="acc-kpi-label">Tiers-temps actif</div>
            </td>
            <td class="acc-kpi" style="width:25%;">
                <div class="acc-kpi-value">{{ $kpis['assistant'] ?? 0 }}</div>
                <div class="acc-kpi-label">Assistant requis</div>
            </td>
            <td class="acc-kpi" style="width:25%;">
                <div class="acc-kpi-value">{{ $kpis['recognition'] ?? 0 }}</div>
                <div class="acc-kpi-label">Reconnaissance officielle</div>
            </td>
        </tr>
    </table>

    @if($rows->isEmpty())
        <div class="acc-empty">
            <p>Aucun étudiant ne correspond aux filtres sélectionnés.</p>
        </div>
    @else
        <div class="acc-section-title">Cohorte ({{ $rows->count() }} étudiant{{ $rows->count() > 1 ? 's' : '' }})</div>

        <table class="acc-table">
            <thead>
                <tr>
                    <th style="width:4%;">N°</th>
                    <th style="width:9%;">Matricule</th>
                    <th style="width:18%;">Étudiant</th>
                    <th style="width:12%;">Classe</th>
                    <th style="width:18%;">Catégories</th>
                    <th style="width:25%;">Aménagements</th>
                    <th style="width:8%;">Tiers-tps</th>
                    <th style="width:6%;">Reconn.</th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $i => $row)
                @php $e = $row['etudiant']; $p = $row['profile']; $insc = $row['inscription']; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><span class="acc-mat">{{ $e->matricule ?? '—' }}</span></td>
                    <td>
                        <div class="acc-name">{{ mb_strtoupper($e->nom ?? '', 'UTF-8') }} {{ $e->prenoms }}</div>
                        @if($p->short_description)
                            <div style="font-size:7px;color:#64748b;margin-top:1px;">{{ $p->short_description }}</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ $insc?->classe?->name ?? '—' }}</div>
                        <div style="font-size:7px;color:#64748b;">{{ $insc?->filiere?->name ?? '' }}</div>
                    </td>
                    <td>
                        @foreach($p->categoryLabels() as $catLabel)
                            <span class="acc-pill acc-pill--cat">{{ $catLabel }}</span>
                        @endforeach
                    </td>
                    <td>
                        @foreach($p->accommodationLabels() as $accLabel)
                            <span class="acc-pill acc-pill--acc">{{ $accLabel }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if($p->requires_third_time)
                            <span class="acc-pill acc-pill--ttp">{{ $p->third_time_percentage }}%</span>
                        @else
                            —
                        @endif
                        @if($p->assistant_required)
                            <span class="acc-pill acc-pill--ttp">Assist.</span>
                        @endif
                    </td>
                    <td>
                        @if($p->has_official_recognition)
                            <span class="acc-pill acc-pill--rec">Oui</span>
                        @else
                            <span style="color:#94a3b8;">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        @if($includeFullDescription)
            @php $rowsWithFullDesc = $rows->filter(fn ($r) => $r['profile']->full_description || $r['profile']->accommodations_notes); @endphp
            @if($rowsWithFullDesc->isNotEmpty())
                <div class="acc-section-title">Détail médical (annexe — distribution restreinte)</div>
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th style="width:9%;">Matricule</th>
                            <th style="width:25%;">Étudiant</th>
                            <th style="width:33%;">Description médicale</th>
                            <th style="width:33%;">Notes aménagements</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rowsWithFullDesc as $row)
                        @php $e = $row['etudiant']; $p = $row['profile']; @endphp
                        <tr>
                            <td><span class="acc-mat">{{ $e->matricule ?? '—' }}</span></td>
                            <td><div class="acc-name">{{ mb_strtoupper($e->nom ?? '', 'UTF-8') }} {{ $e->prenoms }}</div></td>
                            <td style="font-size:8px;white-space:pre-wrap;">{{ $p->full_description ?? '—' }}</td>
                            <td style="font-size:8px;white-space:pre-wrap;">{{ $p->accommodations_notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    @endif

</x-pdf-document>
