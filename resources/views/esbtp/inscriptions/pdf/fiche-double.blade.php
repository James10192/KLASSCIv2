@php
    $school = $school ?? \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
    $logo = \App\Helpers\SettingsHelper::resolveLogoBase64();
    $hdrBg = $pdfSettings['header_bg_color'] ?? $pdfSettings['primary_color'] ?? '#0453cb';
    $hdrText = $pdfSettings['header_text_on_bg'] ?? $pdfSettings['header_text_color'] ?? '#ffffff';
    $e = $inscription->etudiant;
    $sexe = $e->sexe ?? null;
    $copies = [
        'Exemplaire étudiant — à conserver',
        'Exemplaire administration — à archiver',
    ];
    $annee = $inscription->anneeUniversitaire->name
        ?? $inscription->anneeUniversitaire->display_name
        ?? '';

    // Le parcours LMD si la classe en a un, la filiere sinon. La fiche numerique
    // montre l'arbre complet Domaine > Mention > Parcours ; sur le papier une
    // seule ligne suffit, mais elle doit dire la meme chose.
    $_parcours = $inscription->classe->parcours ?? null;
    $_filiereLisible = $_parcours?->name
        ?? $inscription->filiere->name
        ?? $inscription->classe->filiere->name
        ?? null;
    $_mention = $_parcours?->mention?->name;
    $_domaine = $_parcours?->mention?->domaine?->name;

    $_type = $inscription->type_inscription === 'reinscription' ? 'Réinscription' : 'Inscription';
    $_affectation = $inscription->affectation_status
        ? $inscription->affectationStatusLabel()
        : null;

    $_parents = $e?->parents ?? collect();
    $_pieces = collect($pieces ?? []);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'inscription</title>
    @include('pdf.partials.theme')
    <style>
        @page { margin: 8mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; margin: 0; }

        /* UNE PAGE PLEINE PAR EXEMPLAIRE.
           L'ancienne fiche tenait deux exemplaires sur une A4 avec un « couper
           ici » au milieu. Avec l'etat civil complet, les coordonnees, les
           parents et les pieces du dossier, la demi-page ne suffit plus : on
           garde « un exemplaire par partie », on perd le ciseau. */
        .copy { page-break-after: always; }
        .copy:last-child { page-break-after: auto; }

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

        .sec { margin-top: 2.5mm; }
        .sec-titre {
            font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em;
            color: {{ $hdrBg }}; border-bottom: 0.6pt solid {{ $hdrBg }};
            padding-bottom: 0.8mm; margin-bottom: 1.2mm;
        }

        .grille { width: 100%; border-collapse: collapse; }
        .grille td { border: 0.4pt solid #cbd5e1; padding: 1.4mm 2mm; vertical-align: top; }
        /* Le theme raye une ligne sur deux de tout tableau. Sur une grille qu'on
           remplit au stylo, ce gris rend l'ecriture moins lisible et fait croire
           que la ligne grisee est « reservee ». On l'annule ici seulement. */
        .grille tr:nth-child(even) { background-color: transparent !important; }
        .lbl { font-size: 6.2px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .val { font-size: 9px; font-weight: bold; margin-top: 0.6mm; }

        .photo-cell { width: 26mm; text-align: center; vertical-align: middle; padding: 1.5mm; }
        .photo-img { max-width: 23mm; max-height: 30mm; }
        /* Sans photo au dossier, on imprime un cadre a coller plutot que du vide :
           la fiche part au guichet pour etre signee, et la photo s'y agrafe. */
        .photo-vide { border: 0.5pt dashed #94a3b8; height: 29mm; }
        .photo-vide-txt { font-size: 6.5px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; padding-top: 12mm; }

        .liste { width: 100%; border-collapse: collapse; }
        .liste th {
            font-size: 6.2px; text-transform: uppercase; letter-spacing: 0.04em;
            text-align: left; padding: 1mm 2mm; border-bottom: 0.5pt solid #cbd5e1;
        }
        .liste td { font-size: 8.5px; padding: 1.2mm 2mm; border-bottom: 0.3pt solid #e2e8f0; }
        .vide { font-size: 7.5px; color: #94a3b8; font-style: italic; padding: 1.5mm 2mm; }

        .case { font-size: 10px; }

        .rights { margin-top: 2.5mm; border: 0.5pt solid {{ $hdrBg }}; padding: 2mm; }
        .signs { width: 100%; margin-top: 4mm; }
        .signs td { text-align: center; height: 13mm; vertical-align: bottom; font-size: 7.5px; }
        .sign-line { border-top: 0.4pt solid #1e293b; width: 74%; margin: 0 auto; padding-top: 1mm; }
        .signs .qr-cell { width: 24mm; text-align: center; vertical-align: bottom; }
        .qr-img { width: 19mm; height: 19mm; }
        .qr-txt { font-size: 5.5px; color: #94a3b8; letter-spacing: 0.04em; margin-top: 0.8mm; }
    </style>
</head>
<body>
@foreach ($copies as $tag)
<div class="copy">
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
                    <div class="pdf-banner-title">FICHE D'INSCRIPTION</div>
                    <div class="pdf-banner-subtitle">{{ $tag }}@if($annee) · {{ $annee }}@endif</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="sec">
        <div class="sec-titre">État civil</div>
        <table class="grille">
            <tr>
                <td><div class="lbl">Nom</div><div class="val">{{ $e->nom }}</div></td>
                <td><div class="lbl">Prénoms</div><div class="val">{{ $e->prenoms }}</div></td>
                <td class="photo-cell" rowspan="3">
                    @if(!empty($photo))
                        <img src="{{ $photo }}" alt="" class="photo-img">
                    @else
                        <div class="photo-vide"><div class="photo-vide-txt">Photo</div></div>
                    @endif
                </td>
            </tr>
            <tr>
                <td><div class="lbl">Matricule</div><div class="val">{{ $e->matricule ?? '—' }}</div></td>
                <td><div class="lbl">Sexe · Nationalité</div><div class="val">{{ \App\Support\AccordGenre::libelle($sexe) ?? '—' }} · {{ $e->nationalite ?? '—' }}</div></td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="lbl">{{ \App\Support\AccordGenre::accorderPhrase('Né le', $sexe) }} · à</div>
                    <div class="val">{{ optional($e->date_naissance)->format('d/m/Y') ?? '—' }} · {{ $e->lieu_naissance ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="sec">
        <div class="sec-titre">Coordonnées</div>
        <table class="grille">
            <tr>
                <td style="width:34%"><div class="lbl">Téléphone</div><div class="val">{{ $e->telephone ?? '—' }}</div></td>
                <td colspan="2"><div class="lbl">Email</div><div class="val">{{ $e->email_personnel ?? '—' }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">Ville</div><div class="val">{{ $e->ville ?? '—' }}</div></td>
                <td style="width:33%"><div class="lbl">Commune</div><div class="val">{{ $e->commune ?? '—' }}</div></td>
                <td><div class="lbl">Adresse</div><div class="val">{{ $e->adresse ?? '—' }}</div></td>
            </tr>
        </table>
    </div>

    <div class="sec">
        <div class="sec-titre">Scolarité</div>
        <table class="grille">
            @if($_domaine || $_mention)
            <tr>
                <td colspan="3">
                    <div class="lbl">Domaine · Mention · Parcours</div>
                    <div class="val">{{ collect([$_domaine, $_mention, $_parcours?->name])->filter()->implode(' › ') }}</div>
                </td>
            </tr>
            @endif
            <tr>
                <td style="width:40%"><div class="lbl">{{ $_parcours ? app(\App\Services\LMD\VocabulaireStructure::class)->rang('parcours') : 'Filière' }}</div><div class="val">{{ $_filiereLisible ?? '—' }}</div></td>
                <td style="width:30%"><div class="lbl">Niveau</div><div class="val">{{ $inscription->niveau->name ?? $inscription->classe->niveau->name ?? '—' }}</div></td>
                <td><div class="lbl">Classe</div><div class="val">{{ $inscription->classe->name ?? '—' }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">Année universitaire</div><div class="val">{{ $annee ?: '—' }}</div></td>
                <td><div class="lbl">Type</div><div class="val">{{ $_type }}@if($inscription->est_transfert) · Transfert @endif</div></td>
                <td><div class="lbl">Statut d'affectation</div><div class="val">{{ $_affectation ?? 'Non renseigné' }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">Date d'inscription</div><div class="val">{{ optional($inscription->date_inscription)->format('d/m/Y') ?? '—' }}</div></td>
                <td colspan="2"><div class="lbl">Établissement d'origine</div><div class="val">{{ $inscription->etablissement_origine ?? '—' }}</div></td>
            </tr>
        </table>
    </div>

    <div class="sec">
        <div class="sec-titre">Parents et tuteurs</div>
        <table class="liste">
            <thead>
                <tr>
                    <th style="width:34%">Nom et prénoms</th>
                    <th style="width:18%">Lien</th>
                    <th style="width:22%">Téléphone</th>
                    <th>Profession</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($_parents as $parent)
                    <tr>
                        <td>
                            {{ $parent->nom }} {{ $parent->prenoms }}
                            @if($parent->pivot->is_tuteur ?? false) <strong>(tuteur)</strong>@endif
                        </td>
                        <td>{{ $parent->pivot->relation ?? '—' }}</td>
                        <td>{{ $parent->telephone ?? '—' }}</td>
                        <td>{{ $parent->profession ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="vide">Aucun parent ni tuteur renseigné.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($_pieces->isNotEmpty())
    <div class="sec">
        <div class="sec-titre">Pièces du dossier</div>
        <table class="liste">
            <thead>
                <tr>
                    <th style="width:8%"></th>
                    <th>Pièce</th>
                    <th style="width:22%">Attendu</th>
                    <th style="width:22%">État</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($_pieces as $ligne)
                    <tr>
                        {{-- La case reflete l'etat du dossier au moment de l'impression.
                             Elle sert aussi de case a cocher a la main quand la fiche
                             repart au guichet avec une piece manquante. --}}
                        <td class="case">{{ $ligne['satisfaite'] ? '☑' : '☐' }}</td>
                        <td>
                            {{ $ligne['piece']->libelle }}
                            @if($ligne['piece']->is_obligatoire) <strong>*</strong>@endif
                        </td>
                        <td>
                            {{ $ligne['requis'] }} {{ $ligne['requis'] > 1 ? 'exemplaires' : 'exemplaire' }}
                            @if($ligne['annuelle']) · chaque année @endif
                        </td>
                        <td>
                            @if($ligne['non_applicable'])
                                Ne s'applique pas
                            @else
                                {{ $ligne['etat']->label() }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="vide">* Pièce obligatoire. Le dossier n'est complet qu'une fois toutes les pièces obligatoires remises.</div>
    </div>
    @endif

    <div class="rights">
        <div class="lbl">Droits à l'image</div>
        <p style="margin:1.2mm 0 0">J'autorise l'établissement à utiliser mon image (photo, vidéo) dans le cadre de la communication interne et institutionnelle, sauf opposition écrite.</p>
        <p style="margin:1.2mm 0 0">☐ J'accepte &nbsp;&nbsp;&nbsp; ☐ Je refuse</p>
    </div>

    <table class="signs">
        <tr>
            <td><div class="sign-line">Signature de {{ \App\Support\AccordGenre::accorderPhrase("l'étudiant", $sexe) }}</div></td>
            <td><div class="sign-line">Signature du parent ou tuteur</div></td>
            <td><div class="sign-line">Cachet et signature de l'administration</div></td>
            @if(!empty($qr))
                <td class="qr-cell">
                    <img src="{{ $qr }}" alt="" class="qr-img">
                    <div class="qr-txt">Scanner pour ouvrir le dossier</div>
                </td>
            @endif
        </tr>
    </table>
</div>
@endforeach
</body>
</html>
