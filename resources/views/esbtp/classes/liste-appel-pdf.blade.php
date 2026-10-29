@php
    /**
     * Liste d'appel PDF — migré vers <x-pdf-document> (mai 2026).
     * Le composant gère : logo + banner école, title/subtitle, filtres
     * bandeau, footer paginé, signature directeur, watermark, et le
     * respect des settings tenant (pdf_show_generator_name, pdf_watermark,
     * pdf_logo_size, etc.).
     *
     * Variables attendues (passées par ESBTPClasseController::listeAppelPDF) :
     *   $classe, $etudiants, $anneeCourante, $etablissement
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
@endphp

<x-pdf-document
    title="Feuille d'appel"
    :subtitle="$classe->name"
    :filters="$filtersBandeau"
    orientation="landscape"
    signatureBlock="director">

    <style>
        /* ── KPI strip ─────────────────────────────────────────── */
        .la-kpis {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            margin: 6px 0 10px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .la-kpi {
            background-color: {{ $hdrBg }};
            color: {{ $hdrText }};
            border-radius: 5px;
            padding: 7px 9px;
            text-align: center;
            vertical-align: middle;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .la-kpi-label {
            display: block;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            opacity: 0.85;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .la-kpi-value {
            display: block;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.1;
        }
        .la-kpi-value.small { font-size: 10px; line-height: 1.2; }
        .la-kpi-sub {
            display: block;
            font-size: 7px;
            opacity: 0.8;
            margin-top: 1px;
        }

        /* ── Class info row (Classe / Date / Enseignant) ─────── */
        .la-class-info {
            width: 100%;
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            margin-bottom: 8px;
            border-collapse: separate;
        }
        .la-class-info td {
            padding: 7px 12px;
            font-size: 9.5px;
            color: {{ $textColor }};
            vertical-align: middle;
        }
        .la-class-info-label {
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 7.5px;
        }
        .la-class-info-value {
            font-weight: 700;
            color: {{ $primary }};
            font-size: 11px;
        }
        .la-class-info-line {
            border-bottom: 1.5px dashed #94a3b8;
            display: inline-block;
            min-width: 120px;
            padding: 1px 4px;
        }

        /* ── Attendance table ─────────────────────────────────── */
        .la-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 4px;
        }
        .la-table thead th {
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
        .la-table tbody td {
            padding: 4px 4px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }
        .la-table tbody tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        .la-num {
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
        .la-mat {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 8px;
            color: #374151;
        }
        .la-name-cell { text-align: left !important; padding-left: 6px !important; }
        .la-name {
            font-weight: 600;
            font-size: 9.5px;
            color: #1f2937;
            line-height: 1.25;
        }
        .la-gender {
            font-size: 7.5px;
            color: #6b7280;
            margin-top: 1px;
        }
        .la-checkbox {
            width: 11px;
            height: 11px;
            border: 1.5px solid {{ $primary }};
            border-radius: 2px;
            display: inline-block;
            background: white;
        }

        /* ── Bloc Aménagements à respecter ─────────────────── */
        .la-acc-block {
            margin-top: 6px;
            padding: 6px 9px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 3px solid {{ $primary }};
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .la-acc-title {
            font-size: 8.5px;
            font-weight: 700;
            color: {{ $primary }};
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .la-acc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .la-acc-table td {
            padding: 2px 5px;
            vertical-align: top;
        }
        .la-acc-name {
            color: #1e293b;
            font-weight: 700;
            width: 25%;
        }
        .la-acc-detail { color: #475569; }
        .la-acc-key { color: {{ $primary }}; font-weight: 700; }

        /* ── Résumé présences ─────────────────────────────── */
        .la-summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .la-summary td {
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 9px 12px;
            vertical-align: top;
            text-align: center;
        }
        .la-summary-title {
            font-size: 9.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 5px;
        }
        .la-summary-grid {
            display: table;
            width: 100%;
        }
        .la-summary-cell {
            display: table-cell;
            text-align: center;
            padding: 0 4px;
        }
        .la-summary-value {
            font-size: 14px;
            font-weight: 700;
            color: {{ $primary }};
            line-height: 1.1;
        }
        .la-summary-label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>

    {{-- ═══ KPI strip ═══ --}}
    <table class="la-kpis">
        <tr>
            <td class="la-kpi" style="width: 25%;">
                <span class="la-kpi-label">Total</span>
                <span class="la-kpi-value">{{ $etudiants->count() }}</span>
                <span class="la-kpi-sub">Étudiants</span>
            </td>
            <td class="la-kpi" style="width: 25%;">
                <span class="la-kpi-label">Filière</span>
                <span class="la-kpi-value small">{{ $classe->filiere->name ?? 'N/A' }}</span>
                <span class="la-kpi-sub">Spécialisation</span>
            </td>
            <td class="la-kpi" style="width: 25%;">
                <span class="la-kpi-label">Niveau</span>
                <span class="la-kpi-value small">{{ $classe->niveau->name ?? 'N/A' }}</span>
                <span class="la-kpi-sub">Année d'études</span>
            </td>
            <td class="la-kpi" style="width: 25%;">
                <span class="la-kpi-label">Année</span>
                <span class="la-kpi-value small">{{ $anneeCourante->name ?? 'Courante' }}</span>
                <span class="la-kpi-sub">Universitaire</span>
            </td>
        </tr>
    </table>

    {{-- ═══ Class info row : Classe + Date + Enseignant ═══ --}}
    <table class="la-class-info">
        <tr>
            <td style="width: 33%;">
                <span class="la-class-info-label">Classe</span><br>
                <span class="la-class-info-value">{{ $classe->name }}</span>
            </td>
            <td style="width: 33%;">
                <span class="la-class-info-label">Date</span><br>
                <span class="la-class-info-line">&nbsp;</span>
            </td>
            <td style="width: 34%;">
                <span class="la-class-info-label">Enseignant</span><br>
                <span class="la-class-info-line">&nbsp;</span>
            </td>
        </tr>
    </table>

    {{-- ═══ Attendance table ═══ --}}
    @if($etudiants->count() > 0)
        <table class="la-table">
            <thead>
                <tr>
                    <th style="width: 4%;">N°</th>
                    <th style="width: 12%;">Matricule</th>
                    <th>Nom et Prénoms</th>
                    <th style="width: 7%;">Présent</th>
                    <th style="width: 7%;">Absent</th>
                    <th style="width: 22%;">Observations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($etudiants as $index => $etudiant)
                    @php $accProfile = auth()->user()?->can('students.accessibility.export') ? $etudiant->accessibilityProfile : null; @endphp
                    <tr>
                        <td><span class="la-num">{{ $index + 1 }}</span></td>
                        <td><span class="la-mat">{{ $etudiant->matricule ?? 'N/A' }}</span></td>
                        <td class="la-name-cell">
                            <div class="la-name">
                                {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                @if($accProfile)
                                    <x-pdf-accessibility-pill :summary="$accProfile->summaryBadge()" />
                                @endif
                            </div>
                            <div class="la-gender">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</div>
                        </td>
                        <td><div class="la-checkbox"></div></td>
                        <td><div class="la-checkbox"></div></td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ═══ Bloc Aménagements à respecter (si profils accessibility) ═══ --}}
        @if($accStudents->isNotEmpty())
            <div class="la-acc-block">
                <div class="la-acc-title">
                    <x-pdf-accessibility-pill :summary="''" :size="8" /> Aménagements à respecter ({{ $accStudents->count() }})
                </div>
                <table class="la-acc-table">
                    @foreach($accStudents as $accE)
                        @php $p = $accE->accessibilityProfile; @endphp
                        <tr>
                            <td class="la-acc-name">{{ $accE->nom }} {{ $accE->prenoms }}</td>
                            <td class="la-acc-detail">
                                @if($p->requires_third_time) <span class="la-acc-key">Tiers-temps {{ $p->third_time_percentage }}%</span> · @endif
                                @if($p->assistant_required) <span class="la-acc-key">Assistant requis</span> · @endif
                                {{ implode(' · ', $p->accommodationLabels()) }}
                                @if($p->short_description) — <em>{{ $p->short_description }}</em> @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        {{-- ═══ Résumé des présences ═══ --}}
        <table class="la-summary">
            <tr>
                <td>
                    <div class="la-summary-title">Résumé des présences</div>
                    <div class="la-summary-grid">
                        <div class="la-summary-cell">
                            <div class="la-summary-value">{{ $etudiants->count() }}</div>
                            <div class="la-summary-label">Total</div>
                        </div>
                        <div class="la-summary-cell">
                            <div class="la-summary-value">___</div>
                            <div class="la-summary-label">Présents</div>
                        </div>
                        <div class="la-summary-cell">
                            <div class="la-summary-value">___</div>
                            <div class="la-summary-label">Absents</div>
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
