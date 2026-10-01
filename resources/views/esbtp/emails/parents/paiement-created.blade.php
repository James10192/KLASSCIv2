@php
    $emailTitle = 'Paiement en attente de validation';
    $statutTon = 'info';
    $statutTexte = 'En attente de validation';
    $service = 'Comptabilité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la comptabilité.";
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')Votre paiement de {{ \App\Helpers\MontantFcfa::nombre($montant) }} FCFA pour {{ $studentName }} est enregistré : validation sous 24 à 48 h.
@endsection

@section('titre')Votre paiement de {{ \App\Helpers\MontantFcfa::html($montant) }} est enregistré
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, le paiement pour {{ $studentName }} a été enregistré le {{ $dateSoumission }}. L'administration le valide sous 24 à 48&nbsp;h&nbsp;; vous recevrez un message dès qu'il le sera.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Montant enregistré',
    'valeur' => \App\Helpers\MontantFcfa::nombre($montant),
    'unite' => 'FCFA',
    'lignes' => [
        ['Élève', $studentName],
        ['Mode', $modePaiement],
        ['Référence', $reference],
        ['Soumis le', $dateSoumission],
    ],
])
@include('esbtp.emails.partials.bouton', ['url' => $suiviUrl, 'libelle' => 'Suivre mon paiement', 'pleineLargeur' => true])
@endsection
