@php
    $emailTitle = 'Alerte taux de présence';
    $statutTon = 'danger';
    $statutTexte = 'Assiduité insuffisante';
    $service = 'Scolarité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la scolarité.";
    // Seuil de l'école (préférence de notification), 80 pour les données d'exemple.
    $seuilPresence = (int) ($seuilPresence ?? 80);
    // L'appelant réel transmet les données de l'avis d'absence : la période y
    // s'appelle `periodeStats`, et le lien `justificationUrl`.
    $periodeVue = $periode ?? $periodeStats ?? null;
    $lienAbsences = $absencesUrl ?? $justificationUrl ?? null;
    $tauxTexte = number_format((float) $tauxPresence, fmod((float) $tauxPresence, 1.0) == 0.0 ? 0 : 1, ',', ' ');
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')Taux de présence de {{ $tauxTexte }} % pour {{ $studentName }}, sous le seuil recommandé de {{ $seuilPresence }} %.
@endsection

@section('titre'){{ $studentName }} n'est présent(e) qu'à {{ $tauxTexte }}&nbsp;% des cours
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, ce taux de présence est en dessous du seuil recommandé de {{ $seuilPresence }}&nbsp;%. Un taux faible peut peser sur les résultats et sur la note d'assiduité.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Taux de présence',
    'valeur' => $tauxTexte,
    'unite' => '%',
    'precision' => 'Seuil recommandé : '.$seuilPresence.' %',
    'tonPrecision' => $emailDangerColor,
    'progression' => $tauxPresence,
    'couleurBarre' => $emailDangerColor,
    'gauche' => $totalAbsences.' absence(s) au total',
    'droite' => $absencesNonJustifiees.' non justifiée(s)',
])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Classe', $classe ?? null],
    ['Période', $periodeVue],
], 'marge' => '8px 0 0'])
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Ce que nous recommandons'])
<ul style="margin:0;padding-left:20px;font-size:14px;line-height:1.7;color:#334155;">
<li>Veillez à ce que votre enfant assiste régulièrement aux cours.</li>
<li>Justifiez les absences inévitables dans les 48&nbsp;heures.</li>
<li>Contactez le coordinateur en cas de difficultés persistantes.</li>
</ul>
@if($lienAbsences)
@include('esbtp.emails.partials.bouton', ['url' => $lienAbsences, 'libelle' => 'Voir les détails des absences', 'pleineLargeur' => true])
@endif
@endsection
