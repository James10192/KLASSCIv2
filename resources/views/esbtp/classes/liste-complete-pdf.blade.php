@php
    /**
     * Liste complète classe PDF — migré vers <x-pdf-document> (mai 2026).
     * Le composant gère : logo + banner école, title/subtitle, filtres
     * bandeau, footer paginé, watermark, et le respect des settings tenant.
     *
     * Variables attendues : $classe, $etudiants, $anneeCourante, $etablissement
     */
    $pdfCfg    = \App\Helpers\SettingsHelper::getPdfSettings();
    $primary   = $pdfCfg['primary_color']     ?? '#0453cb';
    $hdrBg     = $pdfCfg['header_bg_color']   ?? $primary;
    $hdrText   = $pdfCfg['header_text_color'] ?? '#ffffff';
    $textColor = $pdfCfg['text_color']        ?? '#1f2937';

    $filtersBandeau = array_filter([
        'Filière' => $classe->filiere->name ?? null,
        'Niveau'  => $classe->niveau->name ?? null,
        'Année'   => $anneeCourante->name ?? null,
    ]);

    $accStudents = auth()->user()?->can('students.accessibility.export')
        ? $etudiants->filter(fn ($e) => $e->accessibilityProfile)
        : collect();

    $homCount = $etudiants->where('genre', 'M')->count();
    $femCount = $etudiants->where('genre', 'F')->count();
@endphp

<x-pdf-document
    title="Liste complète"
    :subtitle="$classe->name"
    :filters="$filtersBandeau"
    orientation="landscape">

    <style>
        /* ── KPI strip ─────────────────────────────────────────── */
        .lc-kpis {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            margin: 6px 0 10px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .lc-kpi {
            background-color: {{ $hdrBg }};
            color: {{ $hdrText }};
            border-radius: 5px;
            padding: 7px 9px;
            text-align: center;
            vertical-align: middle;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .lc-kpi-label {
            display: block;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            opacity: 0.85;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .lc-kpi-value {
            display: block;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.1;
        }
        .lc-kpi-value.small { font-size: 10px; line-height: 1.2; }
        .lc-kpi-sub {
            display: block;
            font-size: 7px;
            opacity: 0.8;
            margin-top: 1px;
        }

        /* ── Students table ───────────────────────────────────── */
        .lc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 4px;
        }
        .lc-table thead th {
            background-color: {{ $primary }};
            color: {{ $hdrText }};
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 8.5px;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid {{ $primary }};
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .lc-table tbody td {
            padding: 4px 5px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }
        .lc-table tbody tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        .lc-num {
            display: inline-block;
            background: {{ $primary }};
            color: #fff;
            padding: 1px 5px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .lc-mat {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 8px;
            color: #374151;
        }
        .lc-name-cell { text-align: left !important; padding-left: 6px !important; }
        .lc-name {
            font-weight: 600;
            font-size: 9px;
            color: #1f2937;
            line-height: 1.25;
        }
        .lc-genre {
            display: inline-block;
            color: #fff;
            padding: 1px 5px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .lc-genre--m { background: {{ $primary }}; }
        .lc-genre--f { background: #e91e63; }

        /* ── Bloc Aménagements à respecter ─────────────────── */
        .lc-acc-block {
            margin-top: 8px;
            padding: 6px 9px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 3px solid {{ $primary }};
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .lc-acc-title {
            font-size: 8.5px;
            font-weight: 700;
            color: {{ $primary }};
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .lc-acc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .lc-acc-table td {
            padding: 2px 5px;
            vertical-align: top;
        }
        .lc-acc-name { color: #1e293b; font-weight: 700; width: 25%; }
        .lc-acc-detail { color: #475569; }
        .lc-acc-key { color: {{ $primary }}; font-weight: 700; }

        /* ── Résumé statistique ───────────────────────────── */
        .lc-summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .lc-summary td {
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 9px 12px;
            vertical-align: top;
            text-align: center;
        }
        .lc-summary-title {
            font-size: 9.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 5px;
        }
        .lc-summary-grid {
            display: table;
            width: 100%;
        }
        .lc-summary-cell {
            display: table-cell;
            text-align: center;
            padding: 0 4px;
        }
        .lc-summary-value {
            font-size: 14px;
            font-weight: 700;
            color: {{ $primary }};
            line-height: 1.1;
        }
        .lc-summary-label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>

    {{-- ═══ KPI strip ═══ --}}
    <table class="lc-kpis">
        <tr>
            <td class="lc-kpi" style="width: 25%;">
                <span class="lc-kpi-label">Total</span>
                <span class="lc-kpi-value">{{ $etudiants->count() }}</span>
                <span class="lc-kpi-sub">Étudiants</span>
            </td>
            <td class="lc-kpi" style="width: 25%;">
                <span class="lc-kpi-label">Hommes</span>
                <span class="lc-kpi-value">{{ $homCount }}</span>
                <span class="lc-kpi-sub">Masculin</span>
            </td>
            <td class="lc-kpi" style="width: 25%;">
                <span class="lc-kpi-label">Femmes</span>
                <span class="lc-kpi-value">{{ $femCount }}</span>
                <span class="lc-kpi-sub">Féminin</span>
            </td>
            <td class="lc-kpi" style="width: 25%;">
                <span class="lc-kpi-label">Année</span>
                <span class="lc-kpi-value small">{{ $anneeCourante->name ?? 'Courante' }}</span>
                <span class="lc-kpi-sub">Universitaire</span>
            </td>
        </tr>
    </table>

    {{-- ═══ Students table ═══ --}}
    @if($etudiants->count() > 0)
        <table class="lc-table">
            <thead>
                <tr>
                    <th style="width: 4%;">N°</th>
                    <th style="width: 10%;">Matricule</th>
                    <th>Nom et Prénoms</th>
                    <th style="width: 5%;">Genre</th>
                    <th style="width: 9%;">Date naiss.</th>
                    <th style="width: 11%;">Téléphone</th>
                    <th style="width: 18%;">Email</th>
                    <th>Adresse</th>
                </tr>
            </thead>
            <tbody>
                @foreach($etudiants as $index => $etudiant)
                    @php $accProfile = auth()->user()?->can('students.accessibility.export') ? $etudiant->accessibilityProfile : null; @endphp
                    <tr>
                        <td><span class="lc-num">{{ $index + 1 }}</span></td>
                        <td><span class="lc-mat">{{ $etudiant->matricule ?? 'N/A' }}</span></td>
                        <td class="lc-name-cell">
                            <div class="lc-name">
                                {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                @if($accProfile)
                                    <x-pdf-accessibility-pill :summary="$accProfile->summaryBadge()" />
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="lc-genre {{ ($etudiant->genre ?? '') === 'F' ? 'lc-genre--f' : 'lc-genre--m' }}">
                                {{ $etudiant->genre ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}</td>
                        <td>{{ $etudiant->telephone ?? 'Non renseigné' }}</td>
                        <td style="font-size: 8px;">{{ $etudiant->email ?? 'Non renseigné' }}</td>
                        <td style="font-size: 8px;">{{ $etudiant->adresse ?? 'Non renseigné' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ═══ Bloc Aménagements à respecter ═══ --}}
        @if($accStudents->isNotEmpty())
            <div class="lc-acc-block">
                <div class="lc-acc-title">
                    <x-pdf-accessibility-pill :summary="''" :size="8" /> Aménagements à respecter ({{ $accStudents->count() }})
                </div>
                <table class="lc-acc-table">
                    @foreach($accStudents as $accE)
                        @php $p = $accE->accessibilityProfile; @endphp
                        <tr>
                            <td class="lc-acc-name">{{ $accE->nom }} {{ $accE->prenoms }}</td>
                            <td class="lc-acc-detail">
                                @if($p->requires_third_time) <span class="lc-acc-key">Tiers-temps {{ $p->third_time_percentage }}%</span> · @endif
                                @if($p->assistant_required) <span class="lc-acc-key">Assistant requis</span> · @endif
                                {{ implode(' · ', $p->accommodationLabels()) }}
                                @if($p->short_description) — <em>{{ $p->short_description }}</em> @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        {{-- ═══ Résumé statistique ═══ --}}
        <table class="lc-summary">
            <tr>
                <td>
                    <div class="lc-summary-title">Résumé statistique</div>
                    <div class="lc-summary-grid">
                        <div class="lc-summary-cell">
                            <div class="lc-summary-value">{{ $etudiants->count() }}</div>
                            <div class="lc-summary-label">Total</div>
                        </div>
                        <div class="lc-summary-cell">
                            <div class="lc-summary-value">{{ $homCount }}</div>
                            <div class="lc-summary-label">Hommes</div>
                        </div>
                        <div class="lc-summary-cell">
                            <div class="lc-summary-value">{{ $femCount }}</div>
                            <div class="lc-summary-label">Femmes</div>
                        </div>
                        <div class="lc-summary-cell">
                            <div class="lc-summary-value">{{ $accStudents->count() }}</div>
                            <div class="lc-summary-label">Avec aménagements</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    @else
        <div style="text-align: center; padding: 30px 10px; color: #6b7280; font-size: 10px;">
            <p style="font-size: 16px; color: #d1d5db; margin-bottom: 6px;">Aucun étudiant</p>
            <p>Aucun étudiant inscrit dans cette classe pour l'année {{ $anneeCourante->name ?? 'courante' }}.</p>
        </div>
    @endif
</x-pdf-document>
