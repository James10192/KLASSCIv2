@php
    $school = $school ?? \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
    $logo = \App\Helpers\SettingsHelper::resolveLogoBase64();
    $hdrBg = $pdfSettings['header_bg_color'] ?? $pdfSettings['primary_color'] ?? '#0453cb';
    $hdrText = $pdfSettings['header_text_on_bg'] ?? $pdfSettings['header_text_color'] ?? '#ffffff';
    $_pieces = collect($pieces ?? []);
@endphp
{{--
    La fiche à remplir à la main, en salle d'attente.

    Ce n'est PAS la fiche d'inscription sans ses valeurs. Les deux documents ne
    servent pas au même geste : l'une se relit et se signe, l'autre s'écrit au
    stylo puis se ressaisit au clavier. D'où trois différences qui ne se voient
    pas mais qui décident si le guichet gagne du temps ou en perd :

    1. L'ORDRE DES CHAMPS est celui de l'écran de saisie. La secrétaire recopie
       de haut en bas sans chercher, et sans sauter de champ.
    2. LES LIGNES SONT HAUTES. Une case calibrée pour du texte imprimé en 9
       points est illisible une fois remplie au stylo par un adolescent.
    3. NI CLASSE, NI NIVEAU, NI FILIÈRE. Les demander reviendrait à retenir
       l'élève pour lui demander où il veut aller, alors que le papier est fait
       pour être rempli sans personne en face. C'est l'école qui l'affectera
       ensuite, dans KLASSCI.

    N'y figurent pas non plus le matricule, la photo, le code QR ni la signature
    de l'administration : ils n'existent qu'APRÈS la saisie. Les pré-imprimer
    vides inviterait quelqu'un à les remplir à la main, et à inventer un
    matricule.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche à remplir</title>
    @include('pdf.partials.theme')
    <style>
        @page { margin: 8mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; margin: 0; }

        .pdf-banner { width: 100%; border-collapse: collapse; table-layout: fixed; -webkit-print-color-adjust: exact; }
        .pdf-banner-logo-cell { width: 16%; background-color: {{ $hdrBg }}; padding: 8px 6px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25); }
        .pdf-banner-logo-frame { display: inline-block; background: #fff; border-radius: 5px; padding: 4px; }
        .pdf-banner-logo { max-height: 36px; max-width: 72px; display: block; }
        .pdf-banner-info-cell { width: 84%; background-color: {{ $hdrBg }}; padding: 8px 12px; vertical-align: middle; }
        .pdf-school-name { font-size: 12px; font-weight: 700; color: {{ $hdrText }}; margin: 0 0 2px; }
        .pdf-school-meta { font-size: 7.5px; color: {{ $hdrText }}; opacity: 0.88; margin: 0 0 5px; line-height: 1.4; }
        .pdf-banner-divider { border-top: 1px solid rgba(255,255,255,0.35); padding-top: 4px; }
        .pdf-banner-title { font-size: 11px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.4px; margin: 0; }
        .pdf-banner-subtitle { font-size: 8px; color: {{ $hdrText }}; opacity: 0.88; margin: 2px 0 0; }

        .consigne {
            margin-top: 2.5mm; border: 0.5pt solid {{ $hdrBg }}; padding: 2mm 2.5mm;
            font-size: 8px; line-height: 1.5;
        }
        .consigne strong { color: {{ $hdrBg }}; }

        .sec { margin-top: 3mm; }
        .sec-titre {
            font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em;
            color: {{ $hdrBg }}; border-bottom: 0.6pt solid {{ $hdrBg }};
            padding-bottom: 0.8mm; margin-bottom: 1.5mm;
        }

        .grille { width: 100%; border-collapse: collapse; }
        /* 11 mm de haut : de quoi ecrire au stylo sans deborder. Une case de
           5 mm, suffisante pour du texte imprime, rend l'ecriture illisible. */
        .grille td { border: 0.4pt solid #94a3b8; padding: 1.2mm 2mm; vertical-align: top; height: 11mm; }
        /* Le theme raye une ligne sur deux de tout tableau. Sur une grille qu'on
           remplit au stylo, ce gris rend l'ecriture moins lisible et fait croire
           que la ligne grisee est « reservee ». On l'annule ici seulement. */
        .grille tr:nth-child(even) { background-color: transparent !important; }
        .lbl { font-size: 6.8px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; }
        .aide { font-size: 6px; color: #94a3b8; font-style: italic; }

        .cases { font-size: 9px; margin-top: 1mm; }
        .cases span { margin-right: 5mm; }

        .liste { width: 100%; border-collapse: collapse; }
        .liste th {
            font-size: 6.5px; text-transform: uppercase; letter-spacing: 0.04em;
            text-align: left; padding: 1mm 2mm; border-bottom: 0.5pt solid #94a3b8;
        }
        .liste td { font-size: 8.5px; padding: 1.6mm 2mm; border-bottom: 0.3pt solid #cbd5e1; }
        .case { font-size: 11px; }

        .reserve {
            margin-top: 3mm; border: 0.5pt dashed #94a3b8; padding: 2mm 2.5mm;
            font-size: 7.5px; color: #64748b;
        }
        .reserve-lignes { margin-top: 2mm; }
        .reserve-ligne { border-bottom: 0.4pt solid #cbd5e1; height: 7mm; }

        .signs { width: 100%; margin-top: 4mm; }
        .signs td { text-align: center; height: 15mm; vertical-align: bottom; font-size: 8px; }
        .sign-line { border-top: 0.4pt solid #1e293b; width: 78%; margin: 0 auto; padding-top: 1mm; }
    </style>
</head>
<body>
    <table class="pdf-banner">
        <tr>
            <td class="pdf-banner-logo-cell">
                @if($logo)
                    <span class="pdf-banner-logo-frame">
                        <img src="{{ $logo['data_uri'] }}" alt="logo" class="pdf-banner-logo">
                    </span>
                @endif
            </td>
            <td class="pdf-banner-info-cell">
                <div class="pdf-school-name">{{ $school['name'] ?? config('app.name') }}</div>
                <div class="pdf-school-meta">
                    @if(!empty($school['address'])){{ $school['address'] }}@endif
                    @if(!empty($school['city'])) · {{ $school['city'] }}@endif
                    @if(!empty($school['phone'])) · Tél : {{ $school['phone'] }}@endif
                    @if(!empty($school['email'])) · {{ $school['email'] }}@endif
                </div>
                <div class="pdf-banner-divider">
                    <div class="pdf-banner-title">FICHE DE RENSEIGNEMENTS À REMPLIR</div>
                    <div class="pdf-banner-subtitle">
                        À compléter par l'élève ou sa famille, puis à remettre au secrétariat
                        @if(!empty($annee)) · {{ $annee }}@endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="consigne">
        <strong>Écrivez en MAJUSCULES</strong>, une lettre par intervalle si possible, et au stylo bleu ou noir.
        Le cadre au bas de la page est réservé au secrétariat : n'y écrivez rien.
        <strong>Vous n'avez pas à indiquer votre classe</strong> — l'établissement vous y affectera après examen de votre dossier.
    </div>

    <div class="sec">
        <div class="sec-titre">1 · État civil</div>
        <table class="grille">
            <tr>
                <td style="width:50%"><div class="lbl">Nom</div></td>
                <td><div class="lbl">Prénoms</div></td>
            </tr>
            <tr>
                <td>
                    <div class="lbl">Sexe</div>
                    <div class="cases"><span>☐ Masculin</span><span>☐ Féminin</span></div>
                </td>
                <td><div class="lbl">Nationalité</div></td>
            </tr>
            <tr>
                <td>
                    <div class="lbl">Date de naissance</div>
                    <div class="aide">jour / mois / année</div>
                </td>
                <td><div class="lbl">Lieu de naissance</div></td>
            </tr>
        </table>
    </div>

    <div class="sec">
        <div class="sec-titre">2 · Coordonnées</div>
        <table class="grille">
            <tr>
                <td style="width:50%">
                    <div class="lbl">Téléphone</div>
                    <div class="aide">de l'élève, ou celui où le joindre</div>
                </td>
                <td><div class="lbl">Adresse électronique</div></td>
            </tr>
            <tr>
                <td><div class="lbl">Ville</div></td>
                <td><div class="lbl">Commune ou quartier</div></td>
            </tr>
            <tr>
                <td colspan="2"><div class="lbl">Adresse complète</div></td>
            </tr>
        </table>
    </div>

    <div class="sec">
        <div class="sec-titre">3 · Parents et tuteurs</div>
        <table class="grille">
            <tr>
                <td style="width:38%"><div class="lbl">Nom et prénoms du père, de la mère ou du tuteur</div></td>
                <td style="width:20%">
                    <div class="lbl">Lien</div>
                    <div class="aide">père, mère, tuteur…</div>
                </td>
                <td style="width:22%"><div class="lbl">Téléphone</div></td>
                <td><div class="lbl">Profession</div></td>
            </tr>
            <tr>
                <td><div class="lbl">Second parent ou tuteur</div></td>
                <td><div class="lbl">Lien</div></td>
                <td><div class="lbl">Téléphone</div></td>
                <td><div class="lbl">Profession</div></td>
            </tr>
        </table>
    </div>

    <div class="sec">
        <div class="sec-titre">4 · Scolarité antérieure</div>
        <table class="grille">
            <tr>
                <td style="width:50%"><div class="lbl">Dernier établissement fréquenté</div></td>
                <td><div class="lbl">Dernière classe suivie</div></td>
            </tr>
            <tr>
                <td>
                    <div class="lbl">Avez-vous été affecté par l'État ?</div>
                    <div class="cases"><span>☐ Affecté</span><span>☐ Réaffecté</span><span>☐ Non affecté</span></div>
                </td>
                <td>
                    <div class="lbl">Dernier diplôme obtenu, et année</div>
                </td>
            </tr>
        </table>
    </div>

    @if($_pieces->isNotEmpty())
    <div class="sec">
        <div class="sec-titre">5 · Pièces à joindre</div>
        <table class="liste">
            <thead>
                <tr>
                    <th style="width:8%"></th>
                    <th>Pièce demandée</th>
                    <th style="width:26%">Nombre d'exemplaires</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($_pieces as $piece)
                    <tr>
                        <td class="case">☐</td>
                        <td>
                            {{ $piece->libelle }}
                            @if($piece->is_obligatoire) <strong>*</strong>@endif
                            @if($piece->forme_attendue) <span class="aide">({{ $piece->forme_attendue->label() }})</span>@endif
                        </td>
                        <td>{{ $piece->exemplaires_par_inscription }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aide" style="margin-top:1mm">* Pièce obligatoire. Cochez ce que vous joignez à cette fiche.</div>
    </div>
    @endif

    <div class="reserve">
        <strong>Cadre réservé au secrétariat</strong> — ne rien écrire au-dessus de cette ligne.
        <div class="reserve-lignes">
            <div class="reserve-ligne"></div>
            <div class="reserve-ligne"></div>
        </div>
    </div>

    <table class="signs">
        <tr>
            <td><div class="sign-line">Date et signature de l'élève</div></td>
            <td><div class="sign-line">Date et signature du parent ou tuteur</div></td>
        </tr>
    </table>
</body>
</html>
