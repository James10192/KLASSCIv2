@php
    $emailTitle = "Notification d'absence";
    $statutTon = 'alerte';
    $statutTexte = 'Absence';
    $service = 'Scolarité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la scolarité.";
    // Seuil de l'école (préférence de notification), 80 pour les données d'exemple.
    $seuilPresence = (int) ($seuilPresence ?? 80);
    $sousLeSeuil = $tauxPresence < $seuilPresence;
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader'){{ $studentName }} était absent(e) le {{ $date }} en {{ $matiere }}. Vous pouvez justifier cette absence en ligne.
@endsection

@section('titre'){{ $studentName }} était absent(e) le {{ $date }}
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, {{ $studentName }} a été marqué(e) absent(e) en {{ $matiere }}, de {{ $heureDebut }} à {{ $heureFin }}. Si l'absence est justifiée, vous pouvez transmettre un justificatif en ligne.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Taux de présence · '.$periodeStats,
    'valeur' => $tauxPresence,
    'unite' => '%',
    'precision' => $sousLeSeuil ? 'Sous le seuil recommandé de '.$seuilPresence.' %' : null,
    'tonPrecision' => $emailDangerColor,
    'progression' => $tauxPresence,
    'couleurBarre' => $sousLeSeuil ? $emailDangerColor : $emailSuccessColor,
    'gauche' => $absencesNonJustifiees.' non justifiée(s) · '.$absencesJustifiees.' justifiée(s)',
    'droite' => $totalAbsences.' absence(s) au total',
])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Classe', $classe ?? null],
    ['Date', $date],
    ['Horaire', $heureDebut.' – '.$heureFin],
    ['Matière', $matiere],
    ['Activité', $typeActivite ?? null],
    ['Commentaire', $commentaire ?? null],
], 'marge' => '8px 0 0'])
@if($absencesNonJustifiees >= 3)
<p style="margin:18px 0 0;font-size:14px;line-height:1.6;color:#334155;"><strong style="color:#0f172a;">{{ $absencesNonJustifiees }} absences non justifiées ce mois-ci.</strong> Les absences répétées peuvent peser sur les résultats et sur la note d'assiduité.</p>
@endif
@include('esbtp.emails.partials.bouton', ['url' => $justificationUrl, 'libelle' => 'Soumettre un justificatif', 'pleineLargeur' => true])
<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Un justificatif officiel est demandé (certificat médical, attestation…), dans les 48&nbsp;heures.</p>
@endsection
