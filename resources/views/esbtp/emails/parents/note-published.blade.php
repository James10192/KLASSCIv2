@php
    $emailTitle = 'Nouvelle note disponible';
    $statutTon = 'info';
    $statutTexte = 'Nouvelle note';
    $service = 'Scolarité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la scolarité.";
    $noteTexte = number_format((float) $note, 2, ',', ' ');
    $moyenneClasseTexte = number_format((float) $moyenneClasse, 2, ',', ' ');
    $auDessus = (float) $note >= (float) $moyenneClasse;
    $rangTexte = ! empty($rang) && $rang !== 'N/A' ? $rang.(! empty($effectifClasse) && $effectifClasse !== 'N/A' ? ' / '.$effectifClasse : '') : null;
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader'){{ $studentName }} a obtenu {{ $noteTexte }}/{{ $bareme }} en {{ $matiere }} ({{ $typeEvaluation }} du {{ $dateEvaluation }}).
@endsection

@section('titre'){{ $studentName }} a obtenu {{ $noteTexte }}/{{ $bareme }} en {{ $matiere }}
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, la note de l'évaluation «&nbsp;{{ $typeEvaluation }}&nbsp;» du {{ $dateEvaluation }} vient d'être publiée.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Note obtenue',
    'valeur' => $noteTexte,
    'unite' => '/'.$bareme,
    'precision' => ($auDessus ? 'Au-dessus' : 'En dessous').' de la moyenne de la classe ('.$moyenneClasseTexte.'/'.$bareme.')',
    'tonPrecision' => $auDessus ? $emailSuccessText : $emailDangerColor,
])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Matière', $matiere],
    ['Évaluation', $typeEvaluation],
    ['Date', $dateEvaluation],
    ['Rang', $rangTexte],
], 'marge' => '8px 0 0'])
@if(! empty($appreciation))
@include('esbtp.emails.parents.partials.citation', ['titre' => 'Appréciation', 'texte' => $appreciation])
@endif
@include('esbtp.emails.partials.bouton', ['url' => $noteUrl, 'libelle' => 'Voir les détails', 'pleineLargeur' => true])
@endsection
