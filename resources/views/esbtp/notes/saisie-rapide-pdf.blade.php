@php
    $blank = $isBlank ?? false;
    $matiereName = $blank ? null : ($evaluation->matiere->name ?? null);
    $classeName = $evaluation->classe->name ?? ($evaluation->classe ?? null);
    $titreEval = $blank ? null : ($evaluation->titre ?? null);

    $subtitle = $blank
        ? trim('Classe ' . ($classeName ?? '—'))
        : trim(($titreEval ? '« '.$titreEval.' » — ' : '').($matiereName ?? '').($classeName ? ' · '.$classeName : ''));

    $filters = array_filter([
        'Classe'      => $classeName ?: null,
        'Filière'     => $evaluation->classe->filiere->name ?? null,
        'Matière'     => $matiereName,
        'Évaluation'  => $titreEval,
        'Type'        => $blank ? null : (ucfirst($evaluation->type ?? '') ?: null),
        'Barème'      => $blank ? null : (isset($evaluation->bareme) && $evaluation->bareme !== '' ? '/ '.$evaluation->bareme : null),
        'Coefficient' => $blank ? null : (isset($evaluation->coefficient) && $evaluation->coefficient !== '' ? (string) $evaluation->coefficient : null),
        'Période'     => $blank ? null : (ucfirst(str_replace('semestre', 'Semestre ', $evaluation->periode ?? '')) ?: null),
        'Année'       => $anneeCourante->name ?? null,
        'Date évaluation' => $blank ? null : (isset($evaluation->date_evaluation) && $evaluation->date_evaluation
            ? (is_object($evaluation->date_evaluation) ? $evaluation->date_evaluation->format('d/m/Y') : $evaluation->date_evaluation)
            : null),
    ], fn ($v) => $v !== null && $v !== '');

    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $primary = $pdfCfg['primary_color'] ?? '#0453cb';
    $secondary = $pdfCfg['secondary_color'] ?? '#64748b';
    $accent = $pdfCfg['accent_color'] ?? '#3b7ddb';

    // Stats absences pré-remplies (mode évaluation existante)
    $totalEtudiants = $etudiants->count();
    $notesSaisies = $blank ? 0 : $notesByEtudiant->filter(fn ($n) => $n && !$n->is_absent)->count();
    $absents = $blank ? 0 : $notesByEtudiant->filter(fn ($n) => $n && $n->is_absent)->count();
    $vierges = $totalEtudiants - $notesSaisies - $absents;
@endphp

<x-pdf-document
    :title="$blank ? 'Feuille de saisie des notes' : 'Feuille de saisie — ' . ($titreEval ?: 'Évaluation')"
    :subtitle="$subtitle ?: null"
    :filters="$filters"
    orientation="portrait">

    <style>
        /* ── KPI bandeau premium (cohérent avec liste-complete-pdf) ─── */
        .nm-kpis {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin: 8px 0 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-kpi {
            background-color: {{ $primary }};
            color: #fff;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
            vertical-align: middle;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-kpi-label {
            display: block;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .nm-kpi-value {
            display: block;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.1;
        }
        .nm-kpi-value.small {
            font-size: 11px;
            line-height: 1.25;
        }
        .nm-kpi-sub {
            display: block;
            font-size: 7.5px;
            opacity: 0.8;
            margin-top: 1px;
        }

        /* ── Table notes premium ───────────────────────────────────── */
        .nm-notes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 9.5px;
        }
        .nm-notes-table thead th {
            background-color: {{ $primary }};
            color: #fff;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 5px;
            text-align: center;
            border: 1px solid {{ $primary }};
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-notes-table tbody td {
            padding: 5px 5px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .nm-notes-table tbody tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        .nm-num-badge {
            display: inline-block;
            min-width: 16px;
            padding: 2px 5px;
            border-radius: 3px;
            background: #eef4ff;
            color: {{ $primary }};
            font-weight: 700;
            font-size: 8.5px;
            text-align: center;
        }
        .nm-matricule {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5px;
            color: {{ $secondary }};
            letter-spacing: 0.3px;
        }
        .nm-student-name {
            font-weight: 600;
            color: #111827;
            font-size: 9.5px;
        }
        .nm-student-genre {
            font-size: 7.5px;
            color: {{ $secondary }};
            margin-top: 1px;
        }
        .nm-note-cell {
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            color: {{ $primary }};
        }
        .nm-note-empty {
            display: inline-block;
            min-width: 28px;
            min-height: 14px;
            border: 1px dashed #cbd5e1;
            border-radius: 3px;
        }
        .nm-abs-cell {
            text-align: center;
        }
        .nm-abs-tick {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #94a3b8;
            border-radius: 2px;
        }
        .nm-abs-marked {
            display: inline-block;
            padding: 2px 6px;
            background: #fef2f2;
            color: #b91c1c;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-obs-cell {
            min-width: 90px;
            border-bottom: 1px dotted #cbd5e1;
        }

        /* ── Empty state ──────────────────────────────────────────── */
        .nm-empty {
            text-align: center;
            padding: 40px 20px;
            color: {{ $secondary }};
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
            margin-top: 20px;
        }
        .nm-empty-title {
            font-size: 12px;
            font-weight: 600;
            color: {{ $primary }};
            margin-bottom: 4px;
        }

        /* ── Footer summary 2-col ─────────────────────────────────── */
        .nm-summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .nm-summary-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 8.5px;
            vertical-align: top;
        }
        .nm-summary-title {
            color: {{ $primary }};
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        .nm-summary-grid {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .nm-summary-grid td {
            padding: 4px 2px;
            font-size: 8.5px;
            color: {{ $secondary }};
        }
        .nm-summary-num {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: {{ $primary }};
            line-height: 1.1;
        }
        .nm-info-row {
            display: table;
            width: 100%;
            margin: 3px 0;
            font-size: 8.5px;
        }
        .nm-info-label {
            display: table-cell;
            color: {{ $secondary }};
            width: 50%;
            font-weight: 600;
        }
        .nm-info-value {
            display: table-cell;
            color: #111827;
            text-align: right;
            font-weight: 600;
        }

        /* ── Instructions footer ─────────────────────────────────── */
        .nm-instructions {
            margin-top: 12px;
            padding: 6px 10px;
            background: #eef4ff;
            border-left: 3px solid {{ $primary }};
            font-size: 8px;
            color: {{ $secondary }};
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-instructions strong {
            color: {{ $primary }};
        }
    </style>

    {{-- ── KPIs bandeau premium ─────────────────────────────────── --}}
    <table class="nm-kpis">
        <tr>
            <td class="nm-kpi" style="width: 25%;">
                <span class="nm-kpi-label">Étudiants</span>
                <span class="nm-kpi-value">{{ $totalEtudiants }}</span>
                <span class="nm-kpi-sub">à évaluer</span>
            </td>
            <td class="nm-kpi" style="width: 25%; background-color: {{ $accent }};">
                <span class="nm-kpi-label">Classe</span>
                <span class="nm-kpi-value small">{{ $classeName ?: '—' }}</span>
                <span class="nm-kpi-sub">{{ $evaluation->classe->filiere->name ?? 'Filière' }}</span>
            </td>
            <td class="nm-kpi" style="width: 25%; background-color: {{ $blank ? $secondary : $primary }};">
                <span class="nm-kpi-label">{{ $blank ? 'Évaluation' : 'Type' }}</span>
                <span class="nm-kpi-value small">{{ $blank ? 'à compléter' : (ucfirst($evaluation->type ?? '') ?: '—') }}</span>
                <span class="nm-kpi-sub">{{ $blank ? '' : (isset($evaluation->coefficient) ? 'Coef '.$evaluation->coefficient : '') }}</span>
            </td>
            <td class="nm-kpi" style="width: 25%; background-color: {{ $accent }};">
                <span class="nm-kpi-label">Barème</span>
                <span class="nm-kpi-value">/ {{ $blank ? '____' : ($evaluation->bareme ?? '20') }}</span>
                <span class="nm-kpi-sub">{{ $anneeCourante->name ?? 'Année courante' }}</span>
            </td>
        </tr>
    </table>

    {{-- ── Tableau étudiants/notes ─────────────────────────────── --}}
    @if($etudiants->count() > 0)
        <table class="nm-notes-table">
            <thead>
                <tr>
                    <th style="width: 6%;">N°</th>
                    <th style="width: 14%;">Matricule</th>
                    <th style="text-align: left;">Nom et Prénoms</th>
                    <th style="width: 12%;">Note{{ $blank ? '' : ' / '.($evaluation->bareme ?? '20') }}</th>
                    <th style="width: 8%;">Abs.</th>
                    <th style="width: 26%;">Observations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($etudiants as $index => $etudiant)
                    @php
                        $note = !$blank ? ($notesByEtudiant[$etudiant->id] ?? null) : null;
                        $hasNote = $note && !$note->is_absent;
                        $isAbsent = $note && $note->is_absent;
                    @endphp
                    <tr>
                        <td style="text-align: center;">
                            <span class="nm-num-badge">{{ $index + 1 }}</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="nm-matricule">{{ $etudiant->matricule ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="nm-student-name">{{ mb_strtoupper($etudiant->nom ?? '', 'UTF-8') }} {{ $etudiant->prenoms }}</div>
                            <div class="nm-student-genre">{{ ($etudiant->genre ?? '') === 'M' ? 'Masculin' : (($etudiant->genre ?? '') === 'F' ? 'Féminin' : '') }}</div>
                        </td>
                        <td class="nm-note-cell">
                            @if($hasNote)
                                {{ rtrim(rtrim(number_format((float) $note->note, 2, ',', ''), '0'), ',') }}
                            @else
                                <span class="nm-note-empty"></span>
                            @endif
                        </td>
                        <td class="nm-abs-cell">
                            @if($isAbsent)
                                <span class="nm-abs-marked">ABS</span>
                            @else
                                <span class="nm-abs-tick"></span>
                            @endif
                        </td>
                        <td class="nm-obs-cell">
                            {{ $note->commentaire ?? '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ── Résumé en 2 cards ─────────────────────────────── --}}
        <table class="nm-summary">
            <tr>
                <td class="nm-summary-card" style="width: 50%;">
                    <div class="nm-summary-title">Résumé des notes</div>
                    <table class="nm-summary-grid">
                        <tr>
                            <td>
                                <span class="nm-summary-num">{{ $totalEtudiants }}</span>
                                Total
                            </td>
                            <td>
                                <span class="nm-summary-num">{{ $blank ? '___' : $notesSaisies }}</span>
                                Saisies
                            </td>
                            <td>
                                <span class="nm-summary-num">{{ $blank ? '___' : $absents }}</span>
                                Absents
                            </td>
                            <td>
                                <span class="nm-summary-num">{{ $blank ? '___' : $vierges }}</span>
                                Vierges
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="nm-summary-card" style="width: 50%;">
                    <div class="nm-summary-title">Informations document</div>
                    <div class="nm-info-row">
                        <span class="nm-info-label">Date évaluation</span>
                        <span class="nm-info-value">
                            {{ $blank ? '________' : (isset($evaluation->date_evaluation) && $evaluation->date_evaluation
                                ? (is_object($evaluation->date_evaluation) ? $evaluation->date_evaluation->format('d/m/Y') : $evaluation->date_evaluation)
                                : 'Non renseignée') }}
                        </span>
                    </div>
                    <div class="nm-info-row">
                        <span class="nm-info-label">Coefficient</span>
                        <span class="nm-info-value">{{ $blank ? '____' : ($evaluation->coefficient ?? '—') }}</span>
                    </div>
                    <div class="nm-info-row">
                        <span class="nm-info-label">Durée</span>
                        <span class="nm-info-value">{{ $blank ? '____' : (isset($evaluation->duree_minutes) && $evaluation->duree_minutes ? $evaluation->duree_minutes.' min' : 'Non renseignée') }}</span>
                    </div>
                    <div class="nm-info-row">
                        <span class="nm-info-label">Année universitaire</span>
                        <span class="nm-info-value">{{ $anneeCourante->name ?? 'Courante' }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="nm-instructions">
            <strong>Instructions :</strong>
            Renseigner la note dans la case prévue (ou cocher la case <em>Abs.</em> si l'étudiant était absent).
            Les notes décimales sont autorisées (utiliser la virgule). Toute observation ou justification d'absence
            doit être indiquée dans la dernière colonne.
        </div>
    @else
        <div class="nm-empty">
            <div class="nm-empty-title">Aucun étudiant inscrit</div>
            <div>
                La classe <strong>{{ $classeName ?: 'sélectionnée' }}</strong> ne contient aucun étudiant inscrit
                actif pour l'année universitaire {{ $anneeCourante->name ?? 'courante' }}.
            </div>
        </div>
    @endif

</x-pdf-document>
