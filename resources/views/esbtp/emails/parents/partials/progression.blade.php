{{-- Barre de progression et ses deux légendes. `pct` (0-100), `couleurBarre`,
     `gauche`, `droite` (texte ou HtmlString), `fort` (légende de droite en gras).
     Le remplissage est porté par `background-image` (il garde sa couleur dans
     Gmail sombre), la piste par une couleur simple, que Gmail assombrit avec le
     fond. --}}
@php
    $_pct = max(0, min(100, (int) round((float) $pct)));
    $_barre = $couleurBarre ?? $emailPrimaryColor;
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:99px;overflow:hidden;"><tr>
@if($_pct > 0)<td width="{{ $_pct }}%" height="8" style="height:8px;background:{{ $_barre }};background-image:linear-gradient({{ $_barre }},{{ $_barre }});font-size:0;line-height:0;">&nbsp;</td>@endif
@if($_pct < 100)<td width="{{ 100 - $_pct }}%" height="8" style="height:8px;background:#dde3ec;font-size:0;line-height:0;">&nbsp;</td>@endif
</tr></table>
@if(! empty($gauche) || ! empty($droite))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;"><tr><td style="font-size:12px;color:#64748b;">{{ $gauche ?? '' }}</td><td align="right" style="font-size:12px;text-align:right;{{ ! empty($fort) ? 'font-weight:700;color:#0f172a;' : 'color:#64748b;' }}">{{ $droite ?? '' }}</td></tr></table>
@endif
