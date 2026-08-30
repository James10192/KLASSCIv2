<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche d'inscription</title>
    @include('pdf.partials.theme')
    <style>
        @page { margin: 10mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .copy { height: 128mm; padding: 4mm 0 2mm; }
        .copy + .copy { border-top: 1px dashed #64748b; padding-top: 6mm; }
        .cut { text-align: center; font-size: 8px; color: #64748b; letter-spacing: 0.12em; text-transform: uppercase; margin: 2mm 0; }
        .hdr { width: 100%; margin-bottom: 4mm; }
        .hdr td { vertical-align: middle; }
        .school { font-size: 13px; font-weight: bold; color: #0453cb; }
        .doc-title { font-size: 12px; font-weight: bold; text-align: center; margin: 3mm 0; text-transform: uppercase; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { border: 0.4pt solid #cbd5e1; padding: 2.5mm 3mm; width: 50%; }
        .lbl { font-size: 7px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .val { font-size: 10px; font-weight: bold; }
        .rights { margin-top: 4mm; border: 0.4pt solid #0453cb; padding: 3mm; }
        .signs { width: 100%; margin-top: 6mm; }
        .signs td { width: 50%; text-align: center; height: 18mm; vertical-align: bottom; font-size: 9px; }
        .sign-line { border-top: 0.4pt solid #1e293b; width: 70%; margin: 0 auto; padding-top: 1mm; }
        .copy-tag { font-size: 8px; color: #0453cb; font-weight: bold; }
    </style>
</head>
<body>
@php
    $e = $inscription->etudiant;
    $copies = [
        'Exemplaire étudiant — à conserver',
        'Exemplaire administration — à archiver',
    ];
@endphp
@foreach ($copies as $tag)
<div class="copy">
    <table class="hdr">
        <tr>
            <td style="width:70%">
                <div class="school">{{ $school['name'] ?? $school['nom'] ?? config('app.name') }}</div>
                <div class="lbl">{{ $school['city'] ?? $school['ville'] ?? '' }} · {{ $inscription->anneeUniversitaire->name ?? '' }}</div>
            </td>
            <td style="width:30%; text-align:right" class="copy-tag">{{ $tag }}</td>
        </tr>
    </table>
    <div class="doc-title">Fiche d'inscription</div>
    <table class="meta">
        <tr>
            <td><div class="lbl">Nom</div><div class="val">{{ $e->nom }}</div></td>
            <td><div class="lbl">Prénoms</div><div class="val">{{ $e->prenoms }}</div></td>
        </tr>
        <tr>
            <td><div class="lbl">Matricule</div><div class="val">{{ $e->matricule ?? '—' }}</div></td>
            <td><div class="lbl">Sexe / Nationalité</div><div class="val">{{ $e->sexe ?? '—' }} · {{ $e->nationalite ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="lbl">Date et lieu de naissance</div><div class="val">{{ optional($e->date_naissance)->format('d/m/Y') ?? '—' }} · {{ $e->lieu_naissance ?? '—' }}</div></td>
            <td><div class="lbl">Classe</div><div class="val">{{ $inscription->classe->name ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="lbl">Filière / Parcours</div><div class="val">{{ $inscription->filiere->name ?? $inscription->classe->filiere->name ?? '—' }}</div></td>
            <td><div class="lbl">Niveau</div><div class="val">{{ $inscription->niveau->name ?? $inscription->classe->niveau->name ?? '—' }}</div></td>
        </tr>
    </table>
    <div class="rights">
        <div class="lbl">Droits à l'image</div>
        <p style="margin:2mm 0 0">J'autorise l'établissement à utiliser mon image (photo, vidéo) dans le cadre de la communication interne et institutionnelle, sauf opposition écrite.</p>
        <p style="margin:2mm 0 0">☐ J'accepte &nbsp;&nbsp;&nbsp; ☐ Je refuse</p>
    </div>
    <table class="signs">
        <tr>
            <td><div class="sign-line">Signature de l'étudiant</div></td>
            <td><div class="sign-line">Cachet et signature de l'administration</div></td>
        </tr>
    </table>
</div>
@if (! $loop->last)
    <div class="cut">✂ Couper ici — un exemplaire par partie</div>
@endif
@endforeach
</body>
</html>
