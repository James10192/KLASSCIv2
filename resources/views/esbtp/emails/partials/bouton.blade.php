{{-- Bouton d'action lisible partout (table + bgcolor : Outlook ignore les
     styles d'un lien seul), suivi du lien en clair pour qui ne peut pas cliquer.
     `background-image` et les enveloppes `gm-*` : garde contre le mode sombre
     des applications Gmail, expliquée dans `esbtp.emails.layout`. Le libellé est
     dans un span coloré : Gmail impose sa propre couleur de lien à un `<a>`.

     `couleur` (facultatif) : un fond qui porte un SENS, pas une décoration —
     l'alerte de résultats scolaires passe `$emailDangerColor`. Le texte y est
     alors blanc, seule couleur que la garde Gmail sait restituer, et la garde
     est posée quel que soit le réglage de l'en-tête de l'école.

     `pleineLargeur` (facultatif) : le bouton prend toute la largeur (cible
     tactile large, avis aux parents) ; l'adresse est recopiée en clair et en
     petit dessous, à copier. Sans lui, le rendu des autres courriels ne change pas. --}}
@php
    $fondBouton = $couleur ?? $emailPrimaryColor;
    $texteBouton = isset($couleur) ? '#ffffff' : $emailHeaderTextColor;
    $gardeBouton = isset($couleur) ? true : $emailGardeGmailSombre;
@endphp
@if($pleineLargeur ?? false)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px;">
    <tr>
        <td align="center" bgcolor="{{ $fondBouton }}" style="background:{{ $fondBouton }};background-image:linear-gradient({{ $fondBouton }},{{ $fondBouton }});border-radius:10px;">
            @if($gardeBouton)<div class="gm-ecran" style="border-radius:10px;"><div class="gm-diff" style="border-radius:10px;">@endif
            <a href="{{ $url }}" target="_blank" rel="noopener" style="display:block;padding:15px 20px;font-size:16px;font-weight:700;color:{{ $texteBouton }};text-decoration:none;text-align:center;"><span style="color:{{ $texteBouton }};">{{ $libelle }}</span></a>
            @if($gardeBouton)</div></div>@endif
        </td>
    </tr>
</table>
@if($afficherLien ?? true)
    <p style="margin:0 0 2px;font-size:12px;line-height:1.5;color:#64748b;">Le bouton ne s'ouvre pas&nbsp;? Copiez ce lien&nbsp;:</p>
    <p style="margin:0;font-size:12px;line-height:1.5;color:#64748b;word-break:break-all;">{{ $url }}</p>
@endif
@else
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 10px;">
    <tr>
        <td bgcolor="{{ $fondBouton }}" style="background:{{ $fondBouton }};background-image:linear-gradient({{ $fondBouton }},{{ $fondBouton }});border-radius:12px;box-shadow:0 6px 16px rgba(15,23,42,.14);">
            @if($gardeBouton)<div class="gm-ecran" style="border-radius:12px;"><div class="gm-diff" style="border-radius:12px;">@endif
            <a href="{{ $url }}" target="_blank" rel="noopener" style="display:inline-block;padding:15px 30px;font-size:15px;font-weight:700;color:{{ $texteBouton }};text-decoration:none;border-radius:12px;"><span style="color:{{ $texteBouton }};">{{ $libelle }}&nbsp;&rarr;</span></a>
            @if($gardeBouton)</div></div>@endif
        </td>
    </tr>
</table>
@if($afficherLien ?? true)
    <p style="margin:0 0 4px;font-size:12px;color:#64748b;">Le bouton ne s'ouvre pas ? Copiez ce lien dans votre navigateur :</p>
    <p style="margin:0;font-size:12px;word-break:break-all;"><a href="{{ $url }}" style="color:{{ $emailPrimaryColor }};">{{ $url }}</a></p>
@endif
@endif
