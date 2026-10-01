@php
    $emailTitle = 'Paiement validé';
    $statutTon = 'succes';
    $statutTexte = 'Paiement validé';
    $service = 'Comptabilité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la comptabilité.";
    $solde = (float) $resteDu <= 0;
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')Paiement de {{ \App\Helpers\MontantFcfa::nombre($montant) }} FCFA validé pour {{ $studentName }}, reçu {{ $numeroRecu }}.
@endsection

@section('titre')Nous avons bien reçu {{ \App\Helpers\MontantFcfa::html($montant) }}
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, le paiement pour {{ $studentName }} a été validé le {{ $dateValidation }}. Merci.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Montant reçu',
    'valeur' => \App\Helpers\MontantFcfa::nombre($montant),
    'unite' => 'FCFA',
    'lignes' => [
        ['Reçu n°', $numeroRecu],
        ['Mode', $modePaiement],
        ['Payé le', $datePaiement],
        ['Référence', $reference],
        ['Validé par', $validePar ?? null],
    ],
])
@if($montantTotal > 0)
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Situation des frais de scolarité'])
@include('esbtp.emails.parents.partials.progression', [
    'pct' => $pourcentagePaye,
    'couleurBarre' => $emailSuccessColor,
    'gauche' => new \Illuminate\Support\HtmlString(\App\Helpers\MontantFcfa::nombre($montantPaye).' sur '.\App\Helpers\MontantFcfa::html($montantTotal)),
    'droite' => $solde ? 'Tout est réglé' : new \Illuminate\Support\HtmlString('Reste '.\App\Helpers\MontantFcfa::html($resteDu)),
    'fort' => true,
])
@endif
@include('esbtp.emails.partials.bouton', ['url' => $recuUrl, 'libelle' => 'Télécharger le reçu', 'pleineLargeur' => true])
<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Conservez ce reçu comme preuve de paiement. Il reste disponible à tout moment dans l'espace parent.</p>
@endsection
