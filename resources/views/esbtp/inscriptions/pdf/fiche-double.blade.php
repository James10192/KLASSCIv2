@php
    $school = $school ?? \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
    $logo = \App\Helpers\SettingsHelper::resolveLogoBase64();
    $hdrBg = $pdfSettings['header_bg_color'] ?? $pdfSettings['primary_color'] ?? '#0453cb';
    $hdrText = $pdfSettings['header_text_on_bg'] ?? $pdfSettings['header_text_color'] ?? '#ffffff';
    $e = $inscription->etudiant;
    $copies = [
        'Exemplaire étudiant — à conserver',
        'Exemplaire administration — à archiver',
    ];
    $annee = $inscription->anneeUniversitaire->name
        ?? $inscription->anneeUniversitaire->display_name
        ?? '';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'inscription</title>
    @include('pdf.partials.theme')
    <style>
        @page { margin: 8mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; margin: 0; }
        .copy { height: 132mm; page-break-inside: avoid; }
        .cut { text-align: center; font-size: 7.5px; color: #64748b; letter-spacing: 0.14em; text-transform: uppercase; margin: 1.5mm 0; border-top: 0.6pt dashed #94a3b8; padding-top: 1.5mm; }
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
        .meta { width: 100%; border-collapse: collapse; margin-top: 3mm; }
        .meta td { border: 0.4pt solid #cbd5e1; padding: 2mm 2.5mm; vertical-align: top; }
        /* La cellule photo traverse les quatre rangees : elle tient la hauteur du
           bloc d'identite, ce qui donne un portrait a peu pres 25 x 32 mm, le
           format d'identite habituel. */
        .photo-cell { width: 26mm; text-align: center; vertical-align: middle; padding: 1.5mm; }
        .photo-img { max-width: 23mm; max-height: 30mm; }
        /* Sans photo au dossier, on imprime un cadre a coller plutot que du vide :
           la fiche part au guichet pour etre signee, et la photo s'y agrafe. */
        .photo-vide { border: 0.5pt dashed #94a3b8; height: 29mm; }
        .photo-vide-txt { font-size: 6.5px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; padding-top: 12mm; }
        /* Le code QR se loge dans la troisieme cellule du bloc signature : il
           accompagne le geste — la fiche revient signee, on la scanne pour
           retrouver le dossier. */
        .signs .qr-cell { width: 26mm; text-align: center; vertical-align: bottom; }
        .qr-img { width: 20mm; height: 20mm; }
        .qr-txt { font-size: 5.5px; color: #94a3b8; letter-spacing: 0.04em; margin-top: 0.8mm; }
        .lbl { font-size: 6.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .val { font-size: 9.5px; font-weight: bold; margin-top: 1px; }
        .rights { margin-top: 3mm; border: 0.5pt solid {{ $hdrBg }}; padding: 2.5mm; }
        .signs { width: 100%; margin-top: 5mm; }
        .signs td { text-align: center; height: 14mm; vertical-align: bottom; font-size: 8px; }
        .sign-line { border-top: 0.4pt solid #1e293b; width: 72%; margin: 0 auto; padding-top: 1mm; }
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

    <table class="meta">
        <tr>
            <td><div class="lbl">Nom</div><div class="val">{{ $e->nom }}</div></td>
            <td><div class="lbl">Prénoms</div><div class="val">{{ $e->prenoms }}</div></td>
            <td class="photo-cell" rowspan="4">
                @if(!empty($photo))
                    <img src="{{ $photo }}" alt="" class="photo-img">
                @else
                    <div class="photo-vide"><div class="photo-vide-txt">Photo</div></div>
                @endif
            </td>
        </tr>
        <tr>
            <td><div class="lbl">Matricule</div><div class="val">{{ $e->matricule ?? '—' }}</div></td>
            <td><div class="lbl">Sexe / Nationalité</div><div class="val">{{ \App\Support\AccordGenre::libelle($e->sexe) ?? '—' }} · {{ $e->nationalite ?? '—' }}</div></td>
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
        <p style="margin:1.5mm 0 0">J'autorise l'établissement à utiliser mon image (photo, vidéo) dans le cadre de la communication interne et institutionnelle, sauf opposition écrite.</p>
        <p style="margin:1.5mm 0 0">☐ J'accepte &nbsp;&nbsp;&nbsp; ☐ Je refuse</p>
    </div>
    <table class="signs">
        <tr>
            <td><div class="sign-line">Signature de l'étudiant</div></td>
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
@if (! $loop->last)
    <div class="cut">✂ Couper ici — un exemplaire par partie</div>
@endif
@endforeach
</body>
</html>
