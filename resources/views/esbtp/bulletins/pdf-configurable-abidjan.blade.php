<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    @php
        $pdfSettings   = \App\Helpers\SettingsHelper::getPdfSettings();
        $pdfHeaderBg   = $pdfSettings['header_bg_color']   ?? '#0453cb';
        $pdfHeaderText = $pdfSettings['header_text_color'] ?? '#ffffff';
        $pdfPrimary    = $pdfSettings['primary_color']     ?? $pdfHeaderBg;
        $pdfText       = $pdfSettings['text_color']        ?? '#1f2937';
        // Meme echelle typographique que le gabarit Yakro : la taille choisie
        // dans /esbtp/bulletins/configuration pilote tout le document.
        $typeScale     = \App\Services\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
        // Marges de page reglees dans /esbtp/bulletins/configuration. Les bornes
        // ecartent seulement les valeurs aberrantes : sous ~5 mm, certaines
        // imprimantes rognent encore.
        $marginVertical   = max(2, min(25, (int) ($settings['bulletin_margin_vertical'] ?? 5)));
        $marginHorizontal = max(2, min(25, (int) ($settings['bulletin_margin_horizontal'] ?? 5)));
        $decisionHeight   = max(30, min(200, (int) ($settings['bulletin_decision_min_height'] ?? 84)));
        $signatureHeight  = max(20, min(160, (int) ($settings['bulletin_signature_height'] ?? 44)));
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - {{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</title>
    <style>
        /* ── Base ─────────────────────────────────────────────── */
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $typeScale['body'] }}px;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111827;
            line-height: 1.35;
        }
        .container {
            width: 210mm;
            max-width: 794px;
            margin: 0 auto;
            background: #fff;
            padding: 10px 14px 14px;
        }

        /* ── En-tête institution ──────────────────────────────── */
        .top-entete {
            text-align: center;
            font-size: {{ $typeScale['info'] }}px;
            color: #374151;
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #d1d5db;
        }
        .top-entete .line-strong { font-weight: 700; }

        /* ── Header principal ─────────────────────────────────── */
        .header {
            width: 100%;
            margin-bottom: 6px;
            border: 1.5px solid {{ $pdfPrimary }};
            border-radius: 10px;
            overflow: hidden;
            background: #f9fafb;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
            word-wrap: break-word;
        }

        /* Colonne gauche : logo — largeur % explicite requise par DomPDF */
        .header-table td.header-logo-cell {
            width: 16%;
            padding: 10px 8px 10px 12px;
            vertical-align: middle;
            text-align: center;
            border-right: 1.5px solid #e5e7eb;
        }
        /* width fixe + height auto = ratio préservé ; max-height = protection logo portrait */
        .logo {
            width: 80px;
            height: auto;
            max-height: 80px;
            display: block;
            margin: 0 auto;
        }

        /* Colonne droite : infos école + titre bulletin — largeur % explicite requise par DomPDF */
        .header-table td.header-school-cell {
            width: 50%;
            padding: 10px 10px 10px 14px;
            vertical-align: middle;
        }
        /* Bloc titre a droite, separe par un filet vertical (pas d'encadre) */
        .header-table td.header-title-cell {
            width: 34%;
            padding: 10px 12px 10px 10px;
            vertical-align: middle;
            text-align: right;
            border-left: 1px solid #e5e7eb;
        }
        .school-name {
            font-weight: 700;
            font-size: {{ $typeScale['heading'] }}px;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }
        .school-contact {
            font-size: {{ $typeScale['meta'] }}px;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .bulletin-title {
            font-weight: 700;
            font-size: {{ $typeScale['title'] }}px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: {{ $pdfPrimary }};
            margin-bottom: 2px;
        }
        .bulletin-period {
            font-size: {{ $typeScale['info'] }}px;
            color: #374151;
            margin-bottom: 1px;
        }
        .academic-year {
            font-size: {{ $typeScale['info'] }}px;
            font-weight: 700;
            color: #111827;
        }

        /* Sections critiques : jamais coupees par un saut de page. */
        .student-info, .header, .results-container, .signature-container,
        .decision-container, tr.section-header, tr.summary-row,
        tr.subject-row { page-break-inside: avoid; }

        /* ── Fiche étudiant ───────────────────────────────────── */
        .student-info {
            width: 100%;
            margin-bottom: 6px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .student-info-table td {
            border: none;
            padding: 7px 8px;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* Colonne photo */
        .student-info-table td.student-photo-cell {
            width: 118px;
            min-width: 118px;
            text-align: center;
            vertical-align: middle;
            padding: 8px;
            background: #f8fafb;
            border-right: 1px solid #e5e7eb;
            display: table-cell;
        }
        .student-info-table td.student-photo-cell img {
            width: 90px;
            height: 90px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid {{ $pdfPrimary }};
            display: block;
            margin: 0 auto;
        }
        /* Date d'edition en bas de page */
        .edition-footer {
            margin-top: 10px;
            font-size: {{ $typeScale['label'] }}px;
            color: #6b7280;
            text-align: left;
        }
        .edition-authenticity {
            margin-top: 4px;
            font-size: {{ $typeScale['label'] }}px;
            color: #6b7280;
            text-align: center;
        }

        /* Colonnes infos */
        .info-group {
            width: 40%;
            vertical-align: top;
        }
        /* Libelle et valeur dans deux cellules d'une meme ligne : c'est le seul
           montage qui garantit leur alignement sous DomPDF. En inline-block,
           les deux boites se calaient sur des lignes de base differentes et le
           texte apparaissait decale. Largeur du libelle en em pour suivre la
           taille de police choisie par l'ecole. */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td {
            border: none;
            padding: 1px 0;
            vertical-align: top;
            font-size: {{ $typeScale['info'] }}px;
        }
        /* Selecteurs qualifies par td : `.info-table td` porte deja un padding,
           et une simple classe (0,1,0) perdrait contre lui (0,1,1). Sans cela la
           valeur venait coller les deux points du libelle. */
        .info-table td.info-label {
            font-weight: 700;
            width: 10.5em;
            white-space: nowrap;
            color: #374151;
            padding: 1px 10px 1px 0;
        }
        .info-table td.info-value { color: #111827; padding: 1px 0; }

        /* ── Tableau matières ─────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: {{ $typeScale['table'] }}px;
        }
        /* Interligne resserre : a police 14 le document doit tenir sur une
           seule page, sinon la signature part sur une deuxieme feuille vide. */
        th, td {
            border: 1px solid #d1d5db;
            padding: 2px 5px;
            text-align: left;
        }
        th {
            background: {{ $pdfPrimary }};
            font-weight: 700;
            text-align: center;
            font-size: {{ $typeScale['table_head'] }}px;
            color: #ffffff;
        }
        .center { text-align: center; }

        /* DomPDF n'applique pas background-color sur <tr> : il faut viser les
           <td> enfants. Sans cela le bandeau reste blanc et son texte, qui
           herite color:#fff, devient invisible (blanc sur blanc). */
        .section-header td {
            background-color: {{ $pdfPrimary }};
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            padding: 5px 8px;
            font-size: {{ $typeScale['table'] }}px;
        }
        .subject-row-even td { background-color: #f8fafb; }
        .summary-row td {
            background-color: #e5e7eb;
            font-weight: 700;
        }

        /* Absences */
        .absences-table { width: 100%; margin-bottom: 6px; }

        /* ── Résultats & Statistiques ─────────────────────────── */
        .results-container { width: 100%; margin-bottom: 6px; }
        .results-container-table { width: 100%; border-collapse: collapse; }
        .results-container-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .results-left { width: 50%; padding-right: 5px; }
        .results-right { width: 50%; padding-left: 5px; }

        .results-card, .stats-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .results-table, .stats-table {
            width: 100%;
            font-size: {{ $typeScale['table'] }}px;
            border-collapse: collapse;
            background: #fff;
        }
        .results-table th, .stats-table th {
            background: {{ $pdfPrimary }};
            color: #ffffff;
            padding: 5px 8px;
            font-size: {{ $typeScale['table_head'] }}px;
            border: none;
            text-align: left;
        }
        .results-table td, .stats-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #f3f4f6;
            border-left: none;
            border-right: none;
            border-top: none;
        }
        .results-table tr:last-child td, .stats-table tr:last-child td {
            border-bottom: none;
        }
        .result-value-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 2px 6px;
            min-width: 52px;
            display: inline-block;
            text-align: center;
            font-weight: 700;
            background: #f8fafb;
            font-size: {{ $typeScale['info'] }}px;
        }
        .appreciation-badge {
            display: inline-block;
            border-radius: 4px;
            padding: 2px 5px;
            font-size: {{ $typeScale['label'] }}px;
            font-weight: 700;
            white-space: nowrap;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            color: #475569;
        }
        .appreciation-badge--success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .appreciation-badge--primary { background: #eff6ff; color: #0453cb; border-color: #bfdbfe; }
        .appreciation-badge--warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .appreciation-badge--danger { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .appreciation-badge--neutral { background: #f8fafc; color: #475569; border-color: #d1d5db; }

        /* ── Mentions ─────────────────────────────────────────── */
        .mention-box {
            width: 100%;
            margin-bottom: 4px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: {{ $typeScale['table'] }}px;
            background: #fff;
            overflow: hidden;
        }
        .mention-table { width: 100%; border-collapse: collapse; }
        .mention-table td {
            padding: 4px 8px;
            border-bottom: none;
            border-left: none;
            border-right: none;
            border-top: none;
        }
        .mention-label { font-weight: 600; color: #111827; word-wrap: break-word; }
        .mention-value {
            width: 28px;
            text-align: right;
        }
        /* Distinctions : colonne gauche (3 cases) sous les resultats,
           colonne droite (2 cases) dans la zone vide sous les statistiques.
           Paddings alignes sur .results-left / .results-right (5px). */
        .mention-columns { border-collapse: collapse; margin-bottom: 4px; table-layout: fixed; }
        .mention-col { width: 50%; border: none; vertical-align: top; }
        .mention-col--left { padding: 0 5px 0 0; }
        .mention-col--right { padding: 0 0 0 5px; }

        /* ── Décision conseil ─────────────────────────────────── */
        .decision-container {
            margin: 6px 0;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 10px;
            min-height: {{ $decisionHeight }}px;
            background: #f9fafb;
        }
        .decision-title {
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: {{ $typeScale['table'] }}px;
            color: {{ $pdfPrimary }};
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        /* ── Signature ────────────────────────────────────────── */
        .signature-container {
            margin-top: 6px;
            text-align: right;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        /* Espace de signature sans trait : la barre au-dessus du nom du
           directeur des etudes est retiree sur le gabarit Abidjan. */
        .signature-line {
            width: 200px;
            height: {{ $signatureHeight }}px;
            margin-top: 4px;
        }

        /* ── Mode PDF export ──────────────────────────────────── */
        @if($isPdfExport ?? false)
        @page {
            size: A4 portrait;
            margin: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;
        }
        body.pdf-export {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        body.pdf-export .container {
            width: 100%;
            max-width: none;
            padding: 0;
            border: none;
        }
        @endif

        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .container { box-shadow: none; width: 100%; max-width: none; }
            .print-button, .pdf-toggle { display: none !important; }
        }
    </style>
</head>
<body @if($isPdfExport ?? false)class="pdf-export"@endif>
    <div class="container">
        @if(($showInscriptionWorkflowAlert ?? false) && !($isPdfExport ?? false))
            @include('esbtp.partials.inscription-workflow-alert', [
                'inscriptionWorkflowAlert' => $inscriptionWorkflowAlert ?? null,
                'redirectTo' => 'resultats_etudiant',
            ])
        @endif

        {{-- Entête ministère / république --}}
        @if(($settings['bulletin_show_header'] ?? '1') == '1')
            @if(($settings['bulletin_show_ministry_info'] ?? '1') == '1' || ($settings['bulletin_show_republic_info'] ?? '1') == '1')
                <div class="top-entete">
                    @if(($settings['bulletin_show_republic_info'] ?? '1') == '1')
                        <div class="line-strong">{{ $settings['bulletin_republic_text'] ?? 'République de Côte d\'Ivoire' }}</div>
                        <div>{{ $settings['bulletin_union_text'] ?? 'Union - Discipline - Travail' }}</div>
                    @endif
                    @if(($settings['bulletin_show_ministry_info'] ?? '1') == '1')
                        <div class="line-strong">{{ $settings['bulletin_ministry_text'] ?? "Ministère de l'Enseignement Supérieur et de la Recherche Scientifique" }}</div>
                    @endif
                </div>
            @endif

            @php
                $bulletin = $bulletin ?? null;
                $anneeAffichee = $bulletin?->anneeUniversitaire ?? ($anneeUniversitaire ?? null);
                $anneeLabel = $anneeAffichee?->display_name ?? null;
                if ($anneeLabel && str_starts_with($anneeLabel, 'Année #')) {
                    $anneeLabel = null;
                }
                if (! $anneeLabel) {
                    $anneeLabel = $anneeAffichee->name ?? null;
                }
                if (! $anneeLabel && $anneeAffichee && $anneeAffichee->start_date && $anneeAffichee->end_date) {
                    $anneeLabel = $anneeAffichee->start_date->format('Y').'-'.$anneeAffichee->end_date->format('Y');
                }
                if (! $anneeLabel && isset($anneeAffichee->annee_debut, $anneeAffichee->annee_fin)) {
                    $anneeLabel = $anneeAffichee->annee_debut.'-'.$anneeAffichee->annee_fin;
                }
                if (! $anneeLabel) {
                    $anneeLabel = $anneeAffichee ? ('Annee '.$anneeAffichee->id) : '';
                }
            @endphp

            {{-- Header : logo à gauche | infos école + titre à droite --}}
            <div class="header">
                <table class="header-table">
                    <tr>
                        <td class="header-logo-cell">
                            @if(($settings['bulletin_show_logo'] ?? '1') == '1' && isset($logoBase64) && $logoBase64)
                                <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                            @endif
                        </td>
                        <td class="header-school-cell">
                            @if(($settings['bulletin_show_school_info'] ?? '1') == '1')
                                <div class="school-name">
                                    {{ $settings['bulletin_school_name_custom'] ?: $settings['school_name'] }}
                                </div>
                                <div class="school-contact">
                                    {{ $settings['school_address'] }}
                                    @if($settings['school_phone'] ?? null) &bull; Tél : {{ $settings['school_phone'] }}@endif
                                    @if($settings['school_email'] ?? null) &bull; {{ $settings['school_email'] }}@endif
                                </div>
                            @endif
                        </td>
                        <td class="header-title-cell">
                            <div class="bulletin-title">Bulletin de Notes</div>
                            <div class="bulletin-period">
                                @if($periode == 'semestre1') Premier Semestre
                                @elseif($periode == 'semestre2') Deuxième Semestre
                                @else Annuel
                                @endif
                            </div>
                            <div class="academic-year">Année universitaire : {{ $anneeLabel }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Fiche étudiant --}}
        @php
            // Fallback photo : silhouette generique embarquee en base64 (DomPDF-safe),
            // a la place des initiales.
            $avatarFallbackPath = public_path('images/placeholders/student-avatar-fallback.png');
            $avatarFallbackBase64 = is_file($avatarFallbackPath)
                ? 'data:image/png;base64,'.base64_encode(file_get_contents($avatarFallbackPath))
                : null;
        @endphp
        <div class="student-info">
            <table class="student-info-table">
                <tr>
                    <td class="student-photo-cell">
                        @if(isset($photoEtudiantBase64) && $photoEtudiantBase64)
                            <img src="{{ $photoEtudiantBase64 }}" alt="Photo">
                        @elseif($avatarFallbackBase64)
                            <img src="{{ $avatarFallbackBase64 }}" alt="Avatar">
                        @endif
                    </td>
                    <td class="info-group">
                        <table class="info-table">
                        <tr>
                                <td class="info-label">Nom et Prénoms :</td>
                                <td class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms ?? $etudiant->prenom }}</td>
                            </tr>
                        @if(($settings['bulletin_show_birth_date'] ?? '1') == '1')
                            <tr>
                                <td class="info-label">Date de Naissance :</td>
                                <td class="info-value">{{ $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'Non renseignée' }}</td>
                            </tr>
                        @endif
                        <tr>
                                <td class="info-label">Lieu de Naissance :</td>
                                <td class="info-value">{{ $etudiant->lieu_naissance ?? 'Non renseigné' }}</td>
                            </tr>
                        <tr>
                                <td class="info-label">Genre :</td>
                                <td class="info-value">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                            </tr>
                        @if(($settings['bulletin_show_redoublant'] ?? '1') == '1')
                            <tr>
                                <td class="info-label">Redoublant :</td>
                                <td class="info-value">{{ ($inscription?->is_redoublant ?? false) ? 'Oui' : 'Non' }}</td>
                            </tr>
                        @endif
                        <tr>
                                <td class="info-label">Téléphone :</td>
                                <td class="info-value">{{ $etudiant->telephone ?? 'Non renseigné' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="info-group">
                        <table class="info-table">
                        @if(($settings['bulletin_show_matricule'] ?? '1') == '1')
                            <tr>
                                <td class="info-label">Matricule :</td>
                                <td class="info-value">{{ $etudiant->matricule }}</td>
                            </tr>
                        @endif
                        <tr>
                                <td class="info-label">Classe :</td>
                                <td class="info-value">{{ $classe->libelle ?? $classe->name }}</td>
                            </tr>
                        @if(!empty($isSpecialisation) && !empty($classeTroncCommun) && ($settings['tronc_commun_bulletin_show_origin'] ?? '1') == '1')
                        <tr>
                                <td class="info-label">Classe S1 (TC) :</td>
                                <td class="info-value">{{ $classeTroncCommun->libelle ?? $classeTroncCommun->name }}</td>
                            </tr>
                        @endif
                        <tr>
                                <td class="info-label">Année d'étude :</td>
                                <td class="info-value">{{ $classe->niveau->libelle ?? $classe->niveau->name ?? ($classe->annee ?? 'N/A') }}</td>
                            </tr>
                        <tr>
                                <td class="info-label">Filière :</td>
                                <td class="info-value">{{ $classe->filiere->name ?? 'N/A' }}</td>
                            </tr>
                        <tr>
                                <td class="info-label">Effectif :</td>
                                <td class="info-value">{{ $effectif }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Tableau des matieres --}}
        @if(($settings['bulletin_show_subjects_table'] ?? '1') == '1')
            @php
                $showSubjectAverage = ($settings['bulletin_show_subject_average'] ?? '1') == '1';
                $showCoefficient = ($settings['bulletin_show_coefficient'] ?? '1') == '1';
                $showWeightedAverage = ($settings['bulletin_show_weighted_average'] ?? '1') == '1';
                $showRankPerSubject = ($settings['bulletin_show_rank_per_subject'] ?? '1') == '1';
                $showAbsencesParMatiere = ($settings['bulletin_show_absences_par_matiere'] ?? '0') == '1'
                    && ($settings['bulletin_conduite_enabled'] ?? '0') == '1';
                $showTeachers = ($settings['bulletin_show_teachers'] ?? '1') == '1';
                $showAppreciations = ($settings['bulletin_show_appreciations'] ?? '1') == '1';
                $subjectColumnCount = 1
                    + ($showSubjectAverage ? 1 : 0)
                    + ($showCoefficient ? 1 : 0)
                    + ($showWeightedAverage ? 1 : 0)
                    + ($showRankPerSubject ? 1 : 0)
                    + ($showAbsencesParMatiere ? 1 : 0)
                    + ($showTeachers ? 1 : 0)
                    + ($showAppreciations ? 1 : 0);
            @endphp
            <table>
                <thead>
                    <tr>
                        <th>Mati&egrave;re</th>
                        @if($showSubjectAverage)<th>Moyenne M</th>@endif
                        @if($showCoefficient)<th>Coef C</th>@endif
                        @if($showWeightedAverage)<th>Moy Pond&eacute;r&eacute;e M&times;C</th>@endif
                        @if($showRankPerSubject)<th>Rang</th>@endif
                        @if($showAbsencesParMatiere)<th>Abs. (h)</th>@endif
                        @if($showTeachers)<th>Professeurs</th>@endif
                        @if($showAppreciations)<th>Appr&eacute;ciations</th>@endif
                    </tr>
                    @if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
                        <tr class="section-header">
                            <td colspan="{{ $subjectColumnCount }}">Enseignement G&eacute;n&eacute;ral</td>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
                        @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
                            @foreach($resultatsGeneraux as $resultat)
                                <tr class="subject-row{{ $loop->even ? ' subject-row-even' : '' }}">
                                    <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                                    @if($showSubjectAverage)<td class="center">{{ number_format($resultat->moyenne, 2) }}</td>@endif
                                    @if($showCoefficient)<td class="center">{{ $resultat->coefficient }}</td>@endif
                                    @if($showWeightedAverage)<td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>@endif
                                    @if($showRankPerSubject)<td class="center">{{ $resultat->rang ?: '-' }}</td>@endif
                                    @if($showAbsencesParMatiere)<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                                    @if($showTeachers)<td>{{ trim((string) ($professeurs[$resultat->matiere_id] ?? '')) ?: 'Non attribué' }}</td>@endif
                                    @if($showAppreciations)
                                        <td class="center">
                                            @include('esbtp.bulletins.partials.appreciation', [
                                                'moyenne' => $resultat->moyenne,
                                                'badgeClass' => 'appreciation-badge',
                                            ])
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ $subjectColumnCount }}" class="center">Aucune mati&egrave;re d'enseignement g&eacute;n&eacute;ral</td>
                            </tr>
                        @endif
                        @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                            <tr class="summary-row">
                                <td colspan="{{ max($subjectColumnCount - 1, 1) }}">Moyenne enseignement g&eacute;n&eacute;ral</td>
                                <td class="center">{{ number_format($moyenneGenerale, 2) }}</td>
                            </tr>
                        @endif
                    @endif

                    @if(($settings['bulletin_show_technical_subjects'] ?? '1') == '1')
                        <tr class="section-header">
                            <td colspan="{{ $subjectColumnCount }}">Enseignement Technique</td>
                        </tr>
                        @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
                            @foreach($resultatsTechniques as $resultat)
                                <tr class="subject-row{{ $loop->even ? ' subject-row-even' : '' }}">
                                    <td>{{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'N/A' }}</td>
                                    @if($showSubjectAverage)<td class="center">{{ number_format($resultat->moyenne, 2) }}</td>@endif
                                    @if($showCoefficient)<td class="center">{{ $resultat->coefficient }}</td>@endif
                                    @if($showWeightedAverage)<td class="center">{{ number_format($resultat->moyenne * $resultat->coefficient, 2) }}</td>@endif
                                    @if($showRankPerSubject)<td class="center">{{ $resultat->rang ?: '-' }}</td>@endif
                                    @if($showAbsencesParMatiere)<td class="center">{{ isset($absencesParMatiere[$resultat->matiere_id]) ? $absencesParMatiere[$resultat->matiere_id]['total_heures'] : 0 }}</td>@endif
                                    @if($showTeachers)<td>{{ trim((string) ($professeurs[$resultat->matiere_id] ?? '')) ?: 'Non attribué' }}</td>@endif
                                    @if($showAppreciations)
                                        <td class="center">
                                            @include('esbtp.bulletins.partials.appreciation', [
                                                'moyenne' => $resultat->moyenne,
                                                'badgeClass' => 'appreciation-badge',
                                            ])
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ $subjectColumnCount }}" class="center">Aucune mati&egrave;re d'enseignement technique</td>
                            </tr>
                        @endif
                        @if(($settings['bulletin_show_section_averages'] ?? '1') == '1')
                            <tr class="summary-row">
                                <td colspan="{{ max($subjectColumnCount - 1, 1) }}">Moyenne enseignement technique</td>
                                <td class="center">{{ number_format($moyenneTechnique, 2) }}</td>
                            </tr>
                        @endif
                    @endif
                </tbody>
            </table>
        @endif

        {{-- Absences --}}
        @if(($settings['bulletin_show_absences'] ?? '1') == '1')
            <table class="absences-table">
                <thead>
                    <tr class="section-header">
                        <td colspan="2">Nombre d'heures d'absence</td>
                    </tr>
                </thead>
                <tbody>
                    @if(($settings['bulletin_show_justified_absences'] ?? '1') == '1')
                        <tr>
                            <td>Absences justifiées</td>
                            <td class="center" style="width: 50%;">{{ isset($absencesJustifiees) ? $absencesJustifiees : (isset($absences_justifiees) ? $absences_justifiees : (isset($bulletin->absences_justifiees) ? $bulletin->absences_justifiees : '00')) }} Heure(s)</td>
                        </tr>
                    @endif
                    @if(($settings['bulletin_show_unjustified_absences'] ?? '1') == '1')
                        <tr>
                            <td>Absences non justifiées</td>
                            <td class="center">{{ isset($absencesNonJustifiees) ? $absencesNonJustifiees : (isset($absences_non_justifiees) ? $absences_non_justifiees : (isset($bulletin->absences_non_justifiees) ? $bulletin->absences_non_justifiees : '00')) }} Heure(s)</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

        {{-- Note de Conduite --}}
        @if(($settings['bulletin_conduite_enabled'] ?? '0') == '1' && isset($noteConduite))
            <table class="absences-table">
                <thead>
                    <tr class="section-header">
                        <td colspan="2">Note de Conduite</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total heures d'absences</td>
                        <td class="center" style="width: 50%;">{{ $totalHeuresAbsencesParMatiere ?? 0 }} Heure(s)</td>
                    </tr>
                    <tr>
                        <td>Note de conduite</td>
                        <td class="center"><strong>{{ number_format($noteConduite, 2) }} / 20</strong></td>
                    </tr>
                    @if(!empty($mentionConduite))
                    <tr>
                        <td>Mention conduite</td>
                        <td class="center"><strong style="color: #c0392b;">{{ $mentionConduite }}</strong></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        @endif

        {{-- Résultats & Statistiques --}}
        @if(($settings['bulletin_show_results_section'] ?? '1') == '1')
            <div class="results-container">
                <table class="results-container-table">
                    <tr>
                        <td class="results-left">
                            <div class="results-card">
                                <table class="results-table">
                                    <thead>
                                        <tr><th colspan="2">RÉSULTATS</th></tr>
                                    </thead>
                                    <tbody>
                                        @if(($settings['bulletin_show_raw_average'] ?? '1') == '1')
                                            <tr>
                                                <td>Moyenne Brute</td>
                                                <td class="center"><span class="result-value-box">{{ number_format($moyenneGlobale, 2) }}</span></td>
                                            </tr>
                                        @endif
                                        @if(($settings['bulletin_show_attendance_note'] ?? '1') == '1')
                                            <tr>
                                                <td>Note d'assiduité</td>
                                                <td class="center"><span class="result-value-box">{{ $note_assiduite > 0 ? '+'.number_format($note_assiduite, 2) : number_format($note_assiduite, 2) }}</span></td>
                                            </tr>
                                        @endif
                                        @if(($settings['bulletin_show_semester_average'] ?? '1') == '1')
                                            @if($periode == 'semestre1' || $periode == 'semestre2')
                                            <tr>
                                                <td>Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre</td>
                                                <td class="center"><span class="result-value-box">{{ number_format($moyenneAvecAssiduite, 2) }}</span></td>
                                            </tr>
                                            @endif
                                            @if($periode == 'semestre2')
                                                <tr>
                                                    <td>Moyenne Semestre 1</td>
                                                    <td class="center"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Moyenne Annuelle</td>
                                                    <td class="center"><span class="result-value-box">{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</span></td>
                                                </tr>
                                            @endif
                                            @if($periode == 'annuel')
                                                <tr>
                                                    <td>Moyenne Semestre 1</td>
                                                    <td class="center"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Moyenne Semestre 2</td>
                                                    <td class="center"><span class="result-value-box">{{ $moyenneSemestre2 !== null ? number_format($moyenneSemestre2, 2) : '-' }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Moyenne Annuelle</strong></td>
                                                    <td class="center"><span class="result-value-box"><strong>{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</strong></span></td>
                                                </tr>
                                            @endif
                                        @endif
                                        @if(($settings['bulletin_show_student_rank'] ?? '1') == '1')
                                            <tr>
                                                <td>{{ in_array($periode, ['semestre2', 'annuel'], true) ? 'Rang semestre 2' : 'Rang' }}</td>
                                                <td class="center"><span class="result-value-box">{{ $rang ?: '-' }}</span></td>
                                            </tr>
                                            @if(in_array($periode, ['semestre2', 'annuel'], true))
                                            <tr>
                                                <td>Rang annuel</td>
                                                <td class="center"><span class="result-value-box">{{ ($rangAnnuel ?? null) ?: '-' }}</span></td>
                                            </tr>
                                            @endif
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                        </td>

                        @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
                            <td class="results-right">
                                <div class="stats-card">
                                    <table class="stats-table">
                                        <thead>
                                            <tr><th colspan="2">STATISTIQUES — {{ $periode == 'semestre1' ? 'SEMESTRE 1' : ($periode == 'semestre2' ? 'SEMESTRE 2' : 'ANNUEL') }}</th></tr>
                                        </thead>
                                        <tbody>
                                            @if(($settings['bulletin_show_highest_average'] ?? '1') == '1')
                                                <tr><td>Plus forte moyenne</td><td class="center">{{ number_format($meilleure_moyenne, 2) }}</td></tr>
                                            @endif
                                            @if(($settings['bulletin_show_lowest_average'] ?? '1') == '1')
                                                <tr><td>Plus faible moyenne</td><td class="center">{{ number_format($plus_faible_moyenne, 2) }}</td></tr>
                                            @endif
                                            @if(($settings['bulletin_show_class_average'] ?? '1') == '1')
                                                <tr><td>Moyenne de la classe</td><td class="center">{{ number_format($moyenne_classe, 2) }}</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        @endif
                    </tr>
                </table>
            </div>

            {{-- Distinctions : 3 cases sous les resultats, 2 cases a droite dans
                 la zone vide sous les statistiques (disposition validee ecole). --}}
            @if(($settings['bulletin_show_mentions'] ?? '1') == '1')
                @php
                    $mentionItems = \App\Services\BulletinMentionResolver::resolveFromSettings(
                        isset($moyenneGlobale) ? (float) $moyenneGlobale : null,
                        isset($noteConduite) ? (float) $noteConduite : null
                    );
                @endphp
                @php $mentionSplit = (int) ceil(count($mentionItems) / 2); @endphp
                @if(count($mentionItems) > 0)
                    <table class="mention-columns">
                        <tr>
                            @foreach(['left' => array_slice($mentionItems, 0, $mentionSplit), 'right' => array_slice($mentionItems, $mentionSplit)] as $cote => $colonne)
                                <td class="mention-col mention-col--{{ $cote }}">
                                    @foreach($colonne as $item)
                                        <div class="mention-box"><table class="mention-table"><tr><td class="mention-label">{{ $item['label'] }}</td><td class="mention-value"><input type="checkbox" {{ $item['checked'] ? 'checked' : '' }}></td></tr></table></div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    </table>
                @endif
            @endif
        @endif

        @php
            $councilDecision = $councilDecision ?? ['title' => 'Décision du conseil de classe', 'text' => $appreciation ?? ''];
        @endphp
        {{-- Décision du conseil au-dessus de la signature --}}
        @if(($settings['bulletin_show_council_decision'] ?? '1') == '1')
            <div class="decision-container">
                <div class="decision-title">{{ $councilDecision['title'] ?? 'Décision du conseil de classe' }}</div>
                <div style="min-height: {{ max(20, $decisionHeight - 20) }}px; font-size: {{ $typeScale['decision'] }}px;">{{ $decisionConseil ?? $councilDecision['text'] ?? $bulletin->decision_conseil ?? '' }}</div>
            </div>
        @endif

        {{-- Signature --}}
        @if(($settings['bulletin_show_signature'] ?? '1') == '1' || ($settings['bulletin_show_director_signature'] ?? '1') == '1')
            @php
                $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
                $directorName  = $settings['director_name']  ?? \App\Helpers\SettingsHelper::get('director_name', '');
            @endphp
            <div class="signature-container">
                <div class="signature-box">
                    <div style="font-size: {{ $typeScale['signature'] }}px;">{{ $directorTitle }}</div>
                    <div class="signature-line"></div>
                    @if($directorName)
                        <div style="margin-top: 4px; font-weight: 700; font-size: {{ $typeScale['signature'] }}px;">{{ $directorName }}</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Date d'edition en bas de page (retiree de l'en-tete) --}}
        @include('esbtp.bulletins.partials.edition-footer')

    </div>
</body>
</html>
