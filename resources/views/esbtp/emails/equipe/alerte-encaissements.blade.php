@php
    $nbMois = count($ecarts);
    $emailTitle = $nbMois > 0 ? 'Encaissements en retard' : 'Encaissements à vérifier';
    $statutTon = $nbMois > 0 ? 'danger' : 'alerte';
    $statutTexte = $nbMois > 0 ? 'Écart de recouvrement' : 'À vérifier';
    $service = 'Comptabilité · suivi des encaissements';
    $raison = "Vous recevez ce message parce que vous suivez la comptabilité de l’établissement. Ce contrôle passe toutes les six heures ; un même écart n'est signalé qu'une fois par jour.";
    $totalEcart = max(0, $totalAttendu - $totalEncaisse);
    $part = $totalAttendu > 0 ? (int) round(100 * $totalEncaisse / $totalAttendu) : 0;
    $nbAutres = count($autres);
    // Le mois et ses deux montants à gauche, la part encaissée seule à droite :
    // trois montants sur une ligne débordaient sur téléphone.
    $_lignesMois = array_map(fn ($e) => [
        new \Illuminate\Support\HtmlString('<span style="color:#0f172a;font-weight:600;">'.e($e['mois']).'</span><div style="font-size:12px;color:#64748b;margin-top:2px;">'.\App\Helpers\MontantFcfa::nombre($e['encaisse']).' sur '.\App\Helpers\MontantFcfa::html($e['attendu']).'</div>'),
        new \Illuminate\Support\HtmlString('<span style="color:'.e($emailDangerColor).';white-space:nowrap;">'.e($e['part']).'&nbsp;% encaissé</span>'),
    ], $ecarts);
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')@if($nbMois > 0){{ \App\Helpers\MontantFcfa::nombre($totalEcart) }} FCFA attendus par les échéanciers ne sont pas encore encaissés.@else{{ $nbAutres }} point{{ $nbAutres > 1 ? 's' : '' }} à vérifier dans les encaissements.@endif
@endsection

@section('titre')@if($nbMois > 0)Les encaissements sont en retard sur {{ $nbMois }} mois @else{{ $nbAutres }} point{{ $nbAutres > 1 ? 's' : '' }} à vérifier dans les encaissements @endif
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour{{ $prenom !== '' ? ' '.$prenom : '' }}, @if($nbMois > 0)sur {{ $nbMois > 1 ? 'ces mois' : 'ce mois' }}, l'école a encaissé bien moins que ce que prévoient les échéanciers des élèves.@else le contrôle des encaissements a relevé des montants inhabituels.@endif</p>
@if($nbMois > 0)
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Reste à encaisser',
    'valeur' => \App\Helpers\MontantFcfa::nombre($totalEcart),
    'unite' => 'FCFA',
    'progression' => $part,
    'couleurBarre' => $emailDangerColor,
    'gauche' => new \Illuminate\Support\HtmlString(\App\Helpers\MontantFcfa::html($totalEncaisse).' encaissés'),
    'droite' => new \Illuminate\Support\HtmlString(e($part).'&nbsp;% de '.\App\Helpers\MontantFcfa::html($totalAttendu)),
])
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Part encaissée, mois par mois'])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => $_lignesMois])
@endif
@foreach($autres as $_signal)
@include('esbtp.emails.parents.partials.citation', ['titre' => $_signal['titre'], 'texte' => $_signal['texte'], 'ton' => $emailWarningColor])
@endforeach
@if($restants > 0)
<p style="margin:16px 0 0;font-size:13px;color:#64748b;">Et {{ $restants }} autre{{ $restants > 1 ? 's' : '' }} point{{ $restants > 1 ? 's' : '' }}, visible{{ $restants > 1 ? 's' : '' }} dans l'analyse.</p>
@endif
@if($lien)
@include('esbtp.emails.partials.bouton', ['url' => $lien, 'libelle' => "Ouvrir l'analyse des encaissements", 'pleineLargeur' => true])
@endif
@if($nbMois > 0)
<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Un écart sur un mois de vacances vient souvent d'une échéance placée trop tôt dans l'échéancier : vérifiez les dates avant de relancer les familles.</p>
@endif
@endsection
