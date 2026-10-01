{{-- Gabarit « Reçu » des onze avis aux parents : page blanche, liseré de la
     couleur de l'école, une étiquette de statut, une phrase-titre, un chiffre en
     vedette, des lignes libellé / valeur, une seule action, un pied utile.

     Distinct de `esbtp.emails.layout` (support, vérification de contact,
     activation d'admission, convocation) : ces courriels gardent leur en-tête
     de couleur, et rien de ce qui suit ne les touche.

     Le modèle enfant pose, dans un bloc PHP placé AVANT sa ligne d'héritage :
     - `$emailTitle`   : titre de l'onglet et du courriel ;
     - `$statutTon`    : succes | alerte | danger | info (couleur de sens) ;
     - `$statutTexte`  : l'étiquette, courte, sans capitales (le style les met) ;
     - `$service`      : la ligne de service sous le nom de l'école ;
     - `$raison`       : (facultatif) pourquoi ce message arrive.
     Et les sections `preheader`, `titre` (la phrase-titre) et `content`.

     Tout est en style en ligne : plusieurs messageries ignorent le bloc
     <style>. Le bloc ne porte que la garde contre le mode sombre de Gmail,
     expliquée dans `esbtp.emails.layout`, et l'adaptation au téléphone. Aucun
     texte blanc n'est posé sur une couleur ici, sauf dans le bouton commun, qui
     porte sa propre garde. --}}
@php
    // Pièce intégrée par SMTP, URL publique par MailPulse : voir SourceDuLogo.
    $logoSrc = \App\Mail\Support\SourceDuLogo::pour($message ?? null, $schoolLogoPath ?? null, $schoolLogoUrl ?? null);
    $initiale = mb_strtoupper(mb_substr(trim((string) ($schoolName ?? 'K')), 0, 1, 'UTF-8'), 'UTF-8');
    [$pointStatut, $texteStatut] = match ($statutTon ?? 'info') {
        'succes' => [$emailSuccessColor, $emailSuccessText],
        'alerte' => [$emailWarningColor, $emailWarningText],
        'danger' => [$emailDangerColor, $emailDangerColor],
        default => [$emailPrimaryColor, $emailPrimaryDark],
    };
    $telephone = preg_replace('/[^0-9+]/', '', (string) ($schoolPhone ?? ''));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light">
<title>{{ $emailTitle ?? $schoolName }}</title>
<style>
u + .body .gm-ecran { background: #000; mix-blend-mode: screen; }
u + .body .gm-diff { background: #000; mix-blend-mode: difference; }
@media only screen and (max-width: 620px) {
.kw { padding: 0 !important; }
.kc { border-radius: 0 !important; }
.kp { padding-left: 22px !important; padding-right: 22px !important; }
.kh { font-size: 30px !important; }
}
</style>
</head>
<body class="body" style="margin:0;padding:0;background:#eef2f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#334155;-webkit-text-size-adjust:100%;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f7;">
<tr><td class="kw" align="center" style="padding:24px 12px;">
<table role="presentation" class="kc" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;">
<tr><td style="height:4px;font-size:0;line-height:0;background:{{ $emailPrimaryColor }};background-image:linear-gradient({{ $emailPrimaryColor }},{{ $emailPrimaryColor }});">&nbsp;</td></tr>
<tr><td class="kp" style="padding:24px 36px 0;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="40" valign="middle"><div style="width:40px;height:40px;border-radius:10px;background:#ffffff;background-image:linear-gradient(#ffffff,#ffffff);border:1px solid #e6ebf2;text-align:center;line-height:40px;font-size:19px;font-weight:800;color:{{ $emailPrimaryColor }};">@if($logoSrc)<img src="{{ $logoSrc }}" alt="" width="32" height="32" style="width:32px;height:32px;object-fit:contain;vertical-align:middle;border:0;">@else{{ $initiale }}@endif</div></td>
<td valign="middle" style="padding-left:12px;font-size:15px;font-weight:700;color:#0f172a;line-height:1.3;">{{ $schoolName }}@if(! empty($service))<div style="font-size:12px;font-weight:400;color:#64748b;">{{ $service }}</div>@endif</td>
</tr></table>
</td></tr>
<tr><td class="kp" style="padding:28px 36px 8px;font-size:15px;line-height:1.6;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td style="padding-right:8px;font-size:0;line-height:0;"><div style="width:8px;height:8px;border-radius:8px;background:{{ $pointStatut }};background-image:linear-gradient({{ $pointStatut }},{{ $pointStatut }});"></div></td>
<td style="font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:{{ $texteStatut }};">{{ $statutTexte ?? '' }}</td>
</tr></table>
<h1 style="margin:12px 0 10px;font-size:22px;line-height:1.3;font-weight:700;color:#0f172a;">@yield('titre')</h1>
@yield('content')
</td></tr>
<tr><td class="kp" style="padding:8px 36px 28px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e6ebf2;"><tr><td style="padding-top:20px;font-size:13px;line-height:1.6;color:#64748b;">
<strong style="color:#0f172a;">{{ $schoolName }}</strong>
@if(! empty($schoolAddress))<br>{{ $schoolAddress }}@endif
@if($telephone !== '' || ! empty($schoolEmail))<br>@endif
@if($telephone !== '')<a href="tel:{{ $telephone }}" style="color:{{ $emailPrimaryColor }};text-decoration:none;white-space:nowrap;">{{ $schoolPhone }}</a>@endif
@if($telephone !== '' && ! empty($schoolEmail)) &middot; @endif
@if(! empty($schoolEmail))<a href="mailto:{{ $schoolEmail }}" style="color:{{ $emailPrimaryColor }};text-decoration:none;">{{ $schoolEmail }}</a>@endif
<div style="margin-top:12px;font-size:12px;color:#64748b;">{{ $raison ?? "Message automatique envoyé au contact parent. Pour toute question, contactez l'établissement." }} Merci de ne pas répondre à ce courriel.</div>
</td></tr></table>
</td></tr>
</table>
<div style="font-size:11px;color:#5b6b80;margin-top:14px;">Envoyé avec KLASSCI</div>
</td></tr>
</table>
</body>
</html>
