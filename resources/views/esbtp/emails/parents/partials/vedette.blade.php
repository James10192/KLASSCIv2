{{-- Le chiffre en vedette d'un avis, dans un encadré gris très clair.
     Tous les paramètres sont facultatifs sauf `libelle` :
     - `valeur`, `unite`       : le chiffre (« 150 000 ») et son unité en petit ;
     - `precision`, `tonPrecision` (couleur) : une ligne sous le chiffre ;
     - `progression` (0-100), `couleurBarre`, `gauche`, `droite` : la barre ;
     - `lignes`                : détails libellé / valeur dans l'encadré. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f7fb;border-radius:12px;"><tr><td style="padding:20px 20px {{ empty($lignes) ? '18px' : '6px' }};">
<div style="font-size:13px;color:#64748b;">{{ $libelle }}</div>
@if(isset($valeur) && $valeur !== '')
<div class="kh" style="font-size:34px;line-height:1.15;font-weight:800;color:#0f172a;letter-spacing:-.5px;white-space:nowrap;margin:2px 0 4px;">{{ $valeur }}@if(! empty($unite))<span style="font-size:16px;font-weight:600;color:#64748b;">&nbsp;{{ $unite }}</span>@endif</div>
@endif
@if(! empty($precision))
<div style="font-size:13px;font-weight:600;color:{{ $tonPrecision ?? '#64748b' }};margin-bottom:{{ isset($progression) ? '16px' : '4px' }};">{{ $precision }}</div>
@elseif(isset($progression))
<div style="height:10px;"></div>
@endif
@if(isset($progression))
@include('esbtp.emails.parents.partials.progression', ['pct' => $progression])
@endif
@if(! empty($lignes))
@include('esbtp.emails.parents.partials.lignes', ['lignes' => $lignes, 'marge' => '6px 0 0'])
@endif
</td></tr></table>
