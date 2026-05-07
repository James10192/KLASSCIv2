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

    // ─────────────────────────────────────────────────────────────
    //  ZÉRO COULEUR HARDCODÉE : tout dérive des 4 settings PDF
    //  configurables dans /esbtp/settings → "Couleurs des documents
    //  PDF" (4 pickers, mapping vérifié en mai 2026).
    //
    //  Mapping UI → DB (attention, naming historique non aligné) :
    //    • Picker "Fond de l'en-tête établissement" → pdf_header_bg_color
    //    • Picker "Texte dans l'en-tête établissement" → pdf_header_text_color
    //    • Picker "Couleur d'accent — titres & soulignements"
    //                                              → pdf_PRIMARY_color  (!!)
    //    • Picker "Texte principal du corps du document" → pdf_text_color
    //
    //  L'ancienne clé `pdf_accent_color` existe en DB mais n'est
    //  liée à AUCUN picker UI : c'est un orphan. NE PAS l'utiliser
    //  ici sinon le PDF rend une couleur invisible côté UI tenant.
    // ─────────────────────────────────────────────────────────────
    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $cHeaderBg   = $pdfCfg['header_bg_color']   ?? '#0453cb';
    $cHeaderText = $pdfCfg['header_text_color'] ?? '#ffffff';
    $cAccent     = $pdfCfg['primary_color']     ?? '#0453cb'; // = picker "Accent" UI
    $cText       = $pdfCfg['text_color']        ?? '#1f2937';

    // Helper : "tint" une couleur hex en la mélangeant avec du blanc selon
    // un facteur alpha [0..1]. Retourne un hex composite OPAQUE équivalent à
    // ce qu'on verrait avec rgba(hex, alpha) sur un fond blanc.
    //
    // Choix volontaire vs rgba() :
    //   • DomPDF supporte rgba() depuis v0.8 mais a des bugs documentés sur
    //     certaines combinaisons (borders, table cells imbriquées, opacity
    //     sur background-color avec print-color-adjust).
    //   • PDF = papier blanc, jamais transparent : un hex composite donne
    //     EXACTEMENT le même rendu visuel sans dépendre du moteur.
    //   • Bonus : rendu plus rapide (pas de blending alpha à chaque pixel).
    //
    // Donc tout reste 100% drivé par les 4 settings tenant — on calcule juste
    // les nuances en hex au lieu de déléguer le blending à DomPDF.
    $tint = function (string $hex, float $alpha = 1.0): string {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) {
            $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
        }
        if (strlen($h) !== 6) return $hex;
        $a = max(0.0, min(1.0, $alpha));
        $mix = fn (int $c) => (int) round($c * $a + 255 * (1 - $a));
        return sprintf('#%02x%02x%02x',
            $mix(hexdec(substr($h, 0, 2))),
            $mix(hexdec(substr($h, 2, 2))),
            $mix(hexdec(substr($h, 4, 2))),
        );
    };

    // Nuances dérivées du text_color tenant (structure neutre)
    $cMuted   = $tint($cText, 0.55); // labels secondaires
    $cBorder  = $tint($cText, 0.12); // bordures table/cards
    $cBgSoft  = $tint($cText, 0.03); // fond carte / row alternée
    $cBgLight = $tint($cText, 0.06); // fond hover / instructions
    $cDashed  = $tint($cText, 0.25); // cellules vides en attente
    // Marqueur ABS : dérivé de accent_color (tinted bg + couleur pleine)
    $cDangerBg = $tint($cAccent, 0.10);
    $cDangerFg = $cAccent;

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
        /* ── KPI bandeau premium ──────────────────────────────────────
           Tous les KPIs utilisent header_bg_color (cohérent avec banner)
           pour une identité tenant unifiée. Le texte hérite header_text. */
        .nm-kpis {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin: 8px 0 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-kpi {
            background-color: {{ $cHeaderBg }};
            color: {{ $cHeaderText }};
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

        /* ── Table notes premium ─────────────────────────────────────
           Le thead utilise accent_color (= titres & soulignements selon
           le label des settings UI). Pas de "primary" caché. */
        .nm-notes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 9.5px;
            color: {{ $cText }};
        }
        .nm-notes-table thead th {
            background-color: {{ $cAccent }};
            color: {{ $cHeaderText }};
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 5px;
            text-align: center;
            border: 1px solid {{ $cAccent }};
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-notes-table tbody td {
            padding: 5px 5px;
            border: 1px solid {{ $cBorder }};
            vertical-align: middle;
        }
        .nm-notes-table tbody tr:nth-child(even) td {
            background-color: {{ $cBgSoft }};
        }
        .nm-num-badge {
            display: inline-block;
            min-width: 16px;
            padding: 2px 5px;
            border-radius: 3px;
            background: {{ $cBgLight }};
            color: {{ $cAccent }};
            font-weight: 700;
            font-size: 8.5px;
            text-align: center;
        }
        .nm-matricule {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5px;
            color: {{ $cMuted }};
            letter-spacing: 0.3px;
        }
        .nm-student-name {
            font-weight: 600;
            color: {{ $cText }};
            font-size: 9.5px;
        }
        .nm-student-genre {
            font-size: 7.5px;
            color: {{ $cMuted }};
            margin-top: 1px;
        }
        .nm-note-cell {
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            color: {{ $cAccent }};
        }
        .nm-note-empty {
            display: inline-block;
            min-width: 28px;
            min-height: 14px;
            border: 1px dashed {{ $cDashed }};
            border-radius: 3px;
        }
        .nm-abs-cell {
            text-align: center;
        }
        .nm-abs-tick {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid {{ $cMuted }};
            border-radius: 2px;
        }
        .nm-abs-marked {
            display: inline-block;
            padding: 2px 6px;
            background: {{ $cDangerBg }};
            color: {{ $cDangerFg }};
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-obs-cell {
            min-width: 90px;
            border-bottom: 1px dotted {{ $cDashed }};
        }

        /* ── Empty state ──────────────────────────────────────────── */
        .nm-empty {
            text-align: center;
            padding: 40px 20px;
            color: {{ $cMuted }};
            background: {{ $cBgSoft }};
            border: 1px dashed {{ $cDashed }};
            border-radius: 6px;
            margin-top: 20px;
        }
        .nm-empty-title {
            font-size: 12px;
            font-weight: 600;
            color: {{ $cAccent }};
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
            background: {{ $cBgSoft }};
            border: 1px solid {{ $cBorder }};
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 8.5px;
            vertical-align: top;
        }
        .nm-summary-title {
            color: {{ $cAccent }};
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid {{ $cBorder }};
        }
        .nm-summary-grid {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .nm-summary-grid td {
            padding: 4px 2px;
            font-size: 8.5px;
            color: {{ $cMuted }};
        }
        .nm-summary-num {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: {{ $cAccent }};
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
            color: {{ $cMuted }};
            width: 50%;
            font-weight: 600;
        }
        .nm-info-value {
            display: table-cell;
            color: {{ $cText }};
            text-align: right;
            font-weight: 600;
        }

        /* ── Instructions footer ─────────────────────────────────── */
        .nm-instructions {
            margin-top: 12px;
            padding: 6px 10px;
            background: {{ $cBgLight }};
            border-left: 3px solid {{ $cAccent }};
            font-size: 8px;
            color: {{ $cMuted }};
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .nm-instructions strong {
            color: {{ $cAccent }};
        }
    </style>

    {{-- ── KPIs bandeau premium — tous en header_bg_color (identité tenant) ──── --}}
    <table class="nm-kpis">
        <tr>
            <td class="nm-kpi" style="width: 25%;">
                <span class="nm-kpi-label">Étudiants</span>
                <span class="nm-kpi-value">{{ $totalEtudiants }}</span>
                <span class="nm-kpi-sub">à évaluer</span>
            </td>
            <td class="nm-kpi" style="width: 25%;">
                <span class="nm-kpi-label">Classe</span>
                <span class="nm-kpi-value small">{{ $classeName ?: '—' }}</span>
                <span class="nm-kpi-sub">{{ $evaluation->classe->filiere->name ?? 'Filière' }}</span>
            </td>
            <td class="nm-kpi" style="width: 25%;">
                <span class="nm-kpi-label">{{ $blank ? 'Évaluation' : 'Type' }}</span>
                <span class="nm-kpi-value small">{{ $blank ? 'à compléter' : (ucfirst($evaluation->type ?? '') ?: '—') }}</span>
                <span class="nm-kpi-sub">{{ $blank ? '' : (isset($evaluation->coefficient) ? 'Coef '.$evaluation->coefficient : '') }}</span>
            </td>
            <td class="nm-kpi" style="width: 25%;">
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
                            @php $accProfile = auth()->user()?->can('students.accessibility.export') ? $etudiant->accessibilityProfile : null; @endphp
                            <div class="nm-student-name">
                                {{ mb_strtoupper($etudiant->nom ?? '', 'UTF-8') }} {{ $etudiant->prenoms }}
                                @if($accProfile)
                                    <span style="display:inline-block; background:#0453cb; color:#fff; padding:1px 5px; border-radius:50px; font-size:7px; margin-left:3px; font-weight:600; -webkit-print-color-adjust:exact; color-adjust:exact;" title="{{ $accProfile->summaryBadge() }}">&#9881; A</span>
                                @endif
                            </div>
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
