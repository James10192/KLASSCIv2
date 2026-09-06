@php
    $doc = $snapshot['document'] ?? [];
    $etudiant = $snapshot['student'] ?? [];
    $portee = $snapshot['scope'] ?? [];
    $semestres = $snapshot['semesters'] ?? [];
    $totaux = $snapshot['totals'] ?? [];
    $ecole = $snapshot['institution'] ?? [];

    $sexe = $etudiant['sexe'] ?? null;
    $logo = \App\Helpers\SettingsHelper::resolveLogoBase64();

    $note = static fn ($v): string => $v === null ? '—' : number_format((float) $v, 2, ',', ' ');
    $texte = static fn ($v): string => trim((string) ($v ?? '')) !== '' ? (string) $v : '—';

    // La decision est accordee au genre : le modele officiel imprime « Admise »
    // pour une etudiante. L'accord se fait au rendu a partir du sexe GELE dans
    // l'instantane, jamais de la fiche courante.
    $decision = static fn (?string $brut) => $brut === null
        ? '—'
        : \App\Support\AccordGenre::accorderPhrase($brut === 'admis' ? 'Admis' : 'Ajourné', $sexe);

    $identite = trim(mb_strtoupper((string) ($etudiant['last_name'] ?? ''), 'UTF-8').' '.($etudiant['first_names'] ?? ''));
    $annee = $portee['year']['label'] ?? '';
    $niveau = mb_strtoupper((string) ($portee['level'] ?? ''), 'UTF-8');

    $creditsObtenus = $totaux['credits_earned'] ?? null;
    $creditsAttendus = $totaux['credits_expected'] ?? null;

    // La decision de l'annee : celle du dernier semestre delibere, faute d'une
    // decision annuelle propre dans l'instantane. Elle n'est imprimee que si
    // elle existe — un releve ne conclut pas a la place du jury.
    $decisionAnnee = collect($semestres)->pluck('decision')->filter()->last();

    $rangAnnuel = collect($semestres)->pluck('rank')->filter()->last();
    $effectif = collect($semestres)->pluck('headcount')->filter()->last();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé de notes</title>
    <style>
        @page { margin: 10mm 12mm; }
        /* Le modele du ministere est compose en serif. DejaVu Serif est la seule
           police a empattement embarquee par DomPDF qui porte nos accents. */
        body { font-family: "DejaVu Serif", serif; font-size: 9.5px; color: #000; margin: 0; }

        .entete { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: top; padding: 0; }
        .ministere { font-size: 10px; font-weight: bold; line-height: 1.35; text-transform: uppercase; }
        .republique { font-size: 10px; font-weight: bold; line-height: 1.35; text-align: right; text-transform: uppercase; }
        .devise { font-size: 9px; font-weight: normal; text-align: right; }

        .cadres { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        .cadre {
            border: 0.6pt solid #000; height: 16mm; text-align: center;
            vertical-align: middle; font-size: 8px; padding: 1.5mm;
        }
        .cadre-logo { width: 46mm; }
        .cadre-embleme { width: 40mm; }
        .cadre img { max-height: 13mm; max-width: 40mm; }

        .titre { text-align: center; margin-top: 5mm; }
        .titre h1 { font-size: 17px; font-weight: bold; margin: 0; letter-spacing: 0.5px; }
        .titre h2 { font-size: 12px; font-weight: bold; margin: 1.5mm 0 0; }
        .filet { text-align: center; font-size: 9px; letter-spacing: -0.5px; margin-top: 1mm; }

        .identite { width: 100%; border-collapse: collapse; margin-top: 3mm; font-size: 10px; }
        .identite td { padding: 0.9mm 0; vertical-align: top; }

        .notes { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        .notes th {
            border: 0.6pt solid #000; padding: 1.6mm 1mm; font-size: 9px; font-weight: bold;
            text-align: center; background-color: #e8e8e8;
        }
        .notes td { border: 0.6pt solid #000; padding: 1.4mm 1.6mm; font-size: 9px; vertical-align: middle; }
        .c { text-align: center; }

        /* Le libelle du semestre court verticalement dans la marge, comme sur le
           modele. DomPDF ne fait pas tourner un bloc : on empile les lettres, ce
           qui rend la meme chose a l'impression. */
        .marge {
            width: 7mm; text-align: center; font-weight: bold; font-size: 8.5px;
            background-color: #e8e8e8; letter-spacing: 0; line-height: 1.05;
        }
        .marge span { display: block; }

        .pied-sem td { font-weight: bold; font-style: italic; background-color: #f2f2f2; }
        .total-annee td { font-weight: bold; font-style: italic; background-color: #e8e8e8; }

        .cumul { margin-top: 3.5mm; font-size: 10px; }
        .cumul strong { font-size: 11px; }
        .decision-annee { font-size: 12px; font-weight: bold; margin-top: 1.5mm; }

        .bas { width: 100%; border-collapse: collapse; margin-top: 8mm; }
        .bas td { vertical-align: top; font-size: 10px; }
        .qr-cadre { border: 0.6pt solid #000; width: 30mm; height: 30mm; text-align: center; vertical-align: middle; font-size: 8px; }
        .qr-cadre img { width: 27mm; height: 27mm; }
        .signature { text-align: right; line-height: 2.4; }

        .mention-bas { border-top: 0.5pt solid #000; margin-top: 9mm; padding-top: 1.5mm;
                       text-align: center; font-size: 7.5px; letter-spacing: 0.3px; }
        .verif { font-size: 7.5px; text-align: center; margin-top: 1mm; font-family: "DejaVu Sans Mono", monospace; }
    </style>
</head>
<body>

<table class="entete">
    <tr>
        <td style="width:52%">
            <div class="ministere">
                Ministère de l'Enseignement Supérieur<br>et de la Recherche Scientifique
            </div>
        </td>
        <td style="width:48%">
            <div class="republique">République de Côte d'Ivoire</div>
            <div class="devise">Union – Discipline – Travail</div>
        </td>
    </tr>
</table>

<table class="cadres">
    <tr>
        <td class="cadre cadre-logo">
            @if($logo)
                <img src="{{ $logo['data_uri'] }}" alt="">
            @else
                {{ $texte($ecole['name'] ?? null) }}
            @endif
        </td>
        <td></td>
        <td class="cadre cadre-embleme">Emblème</td>
    </tr>
</table>

<div class="titre">
    <h1>RELEVÉ DE NOTES</h1>
    @if($niveau !== '')
        <h2>NIVEAU : {{ $niveau }}</h2>
    @endif
    <div class="filet">--------------------------------------------------------------</div>
</div>

<table class="identite">
    <tr>
        <td style="width:56%">Nom et prénom(s) : <strong>{{ $identite ?: '—' }}</strong></td>
        <td>Date de naissance : <strong>{{ $etudiant['birth_date'] ? \Carbon\Carbon::parse($etudiant['birth_date'])->format('d/m/Y') : '—' }}</strong></td>
    </tr>
    <tr>
        <td>
            Lieu de naissance : <strong>{{ $texte($etudiant['birth_place'] ?? null) }}</strong>
            &nbsp;&nbsp;&nbsp; Genre : <strong>{{ \App\Support\AccordGenre::libelle($sexe) ? mb_substr(\App\Support\AccordGenre::libelle($sexe), 0, 1) : '—' }}</strong>
        </td>
        <td>Matricule MESRS : <strong>{{ $texte($etudiant['matricule'] ?? null) }}</strong></td>
    </tr>
    <tr>
        <td>
            {{ \App\Support\AccordGenre::accorder('Redoublant', $sexe) }} :
            <strong>{{ ($etudiant['is_redoublant'] ?? null) === null ? '—' : (($etudiant['is_redoublant']) ? 'Oui' : 'Non') }}</strong>
            &nbsp;&nbsp;&nbsp; Domaine : <strong>{{ $texte($portee['domain'] ?? null) }}</strong>
        </td>
        <td>Mention : <strong>{{ $texte($portee['mention'] ?? null) }}</strong></td>
    </tr>
    <tr>
        <td colspan="2">Parcours : <strong>{{ $texte($portee['parcours']['label'] ?? null) }}</strong></td>
    </tr>
</table>

<table class="notes">
    <tr>
        <th style="width:7mm"></th>
        <th style="width:17mm">CODE UE</th>
        <th>Unité(s) d'Enseignement (UE)</th>
        <th style="width:16mm">Nbre de crédits</th>
        <th style="width:14mm">MOY. UE</th>
        <th style="width:22mm">MENTION</th>
        <th style="width:20mm">DÉCISION</th>
        <th style="width:20mm">ANNÉE</th>
    </tr>

    @forelse ($semestres as $sem)
        @php $unites = $sem['units'] ?? []; $nb = max(1, count($unites)); @endphp

        @foreach ($unites as $i => $ue)
            <tr>
                @if($i === 0)
                    <td class="marge" rowspan="{{ $nb }}">
                        @foreach (str_split('SEMESTRE '.$sem['semester']) as $lettre)
                            <span>{!! $lettre === ' ' ? '&nbsp;' : e($lettre) !!}</span>
                        @endforeach
                    </td>
                @endif
                <td>{{ $texte($ue['code'] ?? null) }}</td>
                <td>{{ $texte($ue['name'] ?? null) }}</td>
                <td class="c">{{ $ue['credits'] ?? '—' }}</td>
                <td class="c">{{ $note($ue['average'] ?? null) }}</td>
                <td class="c">{{ $texte($ue['mention'] ?? null) }}</td>
                <td class="c">{{ $decision($ue['decision'] ?? null) }}</td>
                <td class="c">{{ $annee }}</td>
            </tr>
        @endforeach

        @if ($unites === [])
            <tr>
                <td class="marge">
                    @foreach (str_split('SEMESTRE '.$sem['semester']) as $lettre)
                        <span>{!! $lettre === ' ' ? '&nbsp;' : e($lettre) !!}</span>
                    @endforeach
                </td>
                <td colspan="7" class="c" style="font-style:italic">Aucune unité d'enseignement délibérée pour ce semestre.</td>
            </tr>
        @endif

        <tr class="pied-sem">
            <td colspan="4" class="c">
                Moyenne Semestre {{ $sem['semester'] }} : {{ $note($sem['average'] ?? null) }} / 20
            </td>
            <td class="c">{{ ($sem['credits_earned'] ?? 0) }} / {{ ($sem['credits_expected'] ?? 0) }}</td>
            <td colspan="3"></td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="c" style="font-style:italic">Aucun semestre délibéré pour cette année.</td>
        </tr>
    @endforelse

    @if (count($semestres) > 1)
        <tr class="total-annee">
            <td colspan="5" class="c">
                Moyenne {{ collect($semestres)->map(fn ($s) => 'Semestre '.$s['semester'])->implode(' & ') }} :
                {{ $note($totaux['average'] ?? null) }} / 20
            </td>
            <td colspan="3" class="c">
                @if($rangAnnuel)
                    Rang : {{ $rangAnnuel }}@if($effectif) / {{ $effectif }}@endif
                @endif
            </td>
        </tr>
    @endif
</table>

<div class="cumul">
    Total des crédits cumulés pour l'année : <strong>{{ $creditsObtenus ?? '—' }} / {{ $creditsAttendus ?? '—' }}</strong>
</div>

@if($decisionAnnee)
    <div class="decision-annee">
        Décision : {{ $decision(strtolower(trim($decisionAnnee)) === 'admis' ? 'admis' : 'ajourne') }}
    </div>
@endif

<table class="bas">
    <tr>
        <td style="width:34mm">
            <table style="border-collapse:collapse"><tr>
                <td class="qr-cadre">
                    @php
                        $qr = app(\App\Support\CodeQr::class)->svg(
                            route('official-documents.verify.form').'?reference='.urlencode((string) ($doc['reference'] ?? '')),
                            150
                        );
                    @endphp
                    @if($qr)
                        <img src="{{ $qr }}" alt="">
                    @else
                        QR CODE
                    @endif
                </td>
            </tr></table>
        </td>
        <td class="signature">
            {{ $texte($ecole['city'] ?? null) }}, le {{ isset($snapshot['issuance']['issued_at']) ? \Carbon\Carbon::parse($snapshot['issuance']['issued_at'])->format('d/m/Y') : '—' }}
            <br><br>
            Signature et cachet
        </td>
    </tr>
</table>

<div class="mention-bas">
    Document délivré par {{ $texte($ecole['name'] ?? null) }} — référence {{ $texte($doc['reference'] ?? null) }}
</div>
<div class="verif">
    Vérifiable sur {{ config('app.url') }}/verifier-document-officiel — code : {{ $verificationCode }}
</div>

</body>
</html>
