{{-- Un texte rapporté (motif d'un rejet, appréciation, consigne) : un filet de
     couleur à gauche, pas un encadré d'alerte. `titre`, `texte`, `ton` (couleur
     du filet, la couleur de l'école par défaut). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 4px;"><tr><td style="border-left:3px solid {{ $ton ?? $emailPrimaryColor }};padding:2px 0 2px 14px;">
<div style="font-size:13px;color:#64748b;">{{ $titre }}</div>
<div style="font-size:15px;line-height:1.55;color:#0f172a;margin-top:2px;">{{ $texte }}</div>
</td></tr></table>
