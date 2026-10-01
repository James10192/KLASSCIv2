@php
    $emailTitle = 'Rappel de paiement';
    $statutTon = 'alerte';
    $rappels = (int) ($historiqueRelances ?? 0);
    $ordinaux = [1 => 'premier', 2 => 'deuxième', 3 => 'troisième', 4 => 'quatrième', 5 => 'cinquième'];
    $statutTexte = 'Rappel'.($rappels > 0 ? ' · '.($ordinaux[$rappels] ?? $rappels.'e').' envoi' : '');
    $service = 'Comptabilité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la comptabilité.";
    $echeanceConnue = ! empty($echeance);
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader'){{ \App\Helpers\MontantFcfa::nombre($montantDu) }} FCFA restent à régler pour {{ $studentName }}@if($echeanceConnue) avant le {{ $echeance }}@endif.
@endsection

@section('titre')Il reste {{ \App\Helpers\MontantFcfa::html($montantDu) }} à régler pour {{ $studentName }}
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, les frais de scolarité {{ ! empty($anneeUniversitaire) && $anneeUniversitaire !== 'N/A' ? $anneeUniversitaire.' ' : '' }}ne sont pas encore soldés pour {{ $studentName }}.@if($echeanceConnue) Merci de régler le reste avant le <strong style="color:#0f172a;">{{ $echeance }}</strong>.@endif</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Reste à payer',
    'valeur' => \App\Helpers\MontantFcfa::nombre($montantDu),
    'unite' => 'FCFA',
    'precision' => $echeanceConnue ? 'Échéance le '.$echeance.(isset($joursRestants) ? ' · dans '.$joursRestants.' jour'.((int) $joursRestants > 1 ? 's' : '') : '') : null,
    'tonPrecision' => $emailWarningText,
    'progression' => $montantTotal > 0 ? $pourcentagePaye : null,
    'gauche' => new \Illuminate\Support\HtmlString(\App\Helpers\MontantFcfa::html($montantPaye).' payés'),
    'droite' => new \Illuminate\Support\HtmlString(e($pourcentagePaye).'&nbsp;% de '.\App\Helpers\MontantFcfa::html($montantTotal)),
])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Classe', $classe ?? null],
    ['Année', $anneeUniversitaire ?? null],
], 'marge' => '8px 0 0'])
@include('esbtp.emails.partials.bouton', ['url' => $paiementUrl, 'libelle' => 'Effectuer un paiement', 'pleineLargeur' => true])
<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Vous pouvez aussi payer à la caisse de l'établissement{{ ! empty($modesPaiement) ? ' ('.implode(', ', (array) $modesPaiement).')' : '' }}. Déjà réglé ou une difficulté&nbsp;? Contactez la comptabilité&nbsp;: la mise à jour peut prendre un jour ouvré.</p>
@endsection
