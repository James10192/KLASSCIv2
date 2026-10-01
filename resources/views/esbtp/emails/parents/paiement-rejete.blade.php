@php
    $emailTitle = 'Paiement rejeté';
    $statutTon = 'danger';
    $statutTexte = 'Paiement rejeté';
    $service = 'Comptabilité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la comptabilité.";
    $rejeteLe = \App\Helpers\ValeurConnue::ou($dateRejet ?? null);
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')Le paiement de {{ \App\Helpers\MontantFcfa::nombre($montant) }} FCFA pour {{ $studentName }} n'a pas été accepté. Motif : {{ $motifRejet }}
@endsection

@section('titre')Votre paiement de {{ \App\Helpers\MontantFcfa::html($montant) }} n'a pas été accepté
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, le paiement pour {{ $studentName }} a été rejeté par l'administration{{ $rejeteLe ? ' le '.$rejeteLe : '' }}. Le motif est indiqué ci-dessous.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Montant rejeté',
    'valeur' => \App\Helpers\MontantFcfa::nombre($montant),
    'unite' => 'FCFA',
    'lignes' => [
        ['Élève', $studentName],
        ['Référence', $reference],
        ['Soumis le', $dateSoumission],
        ['Rejeté le', $dateRejet],
    ],
])
@include('esbtp.emails.parents.partials.citation', ['titre' => 'Motif du rejet', 'texte' => $motifRejet, 'ton' => $emailDangerColor])
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Que faire maintenant ?'])
<ol style="margin:0;padding-left:20px;font-size:14px;line-height:1.7;color:#334155;">
<li>Lisez le motif ci-dessus.</li>
<li>Corrigez les informations ou réunissez les pièces manquantes.</li>
<li>Soumettez à nouveau le paiement sur la plateforme.</li>
</ol>
@include('esbtp.emails.partials.bouton', ['url' => $paiementUrl, 'libelle' => 'Soumettre un nouveau paiement', 'pleineLargeur' => true])
@endsection
