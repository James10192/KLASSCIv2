{{-- Détails en lignes libellé / valeur, filets fins, valeur à droite.
     `lignes` : liste de [libellé, valeur]. Une valeur vide, nulle ou « N/A »
     (repli des appelants quand la donnée manque) retire sa ligne : l'avis
     s'en passe plutôt que d'afficher un trou. Une valeur `HtmlString` (montant,
     identifiant) est rendue telle quelle ; le reste est échappé. --}}
@php
    $_lignesVues = array_values(array_filter($lignes, function ($ligne) {
        $valeur = $ligne[1] ?? null;

        return $valeur instanceof \Illuminate\Support\HtmlString || \App\Helpers\ValeurConnue::ou($valeur) !== null;
    }));
@endphp
@if($_lignesVues !== [])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"@if(! empty($marge)) style="margin:{{ $marge }};"@endif>
@foreach($_lignesVues as [$_libelle, $_valeur])
<tr><td style="padding:10px 12px 10px 0;font-size:14px;color:#64748b;{{ $loop->first ? '' : 'border-top:1px solid #e6ebf2;' }}">{{ $_libelle }}</td><td align="right" style="padding:10px 0;font-size:14px;font-weight:600;color:#0f172a;text-align:right;{{ $loop->first ? '' : 'border-top:1px solid #e6ebf2;' }}">{{ $_valeur }}</td></tr>
@endforeach
</table>
@endif
