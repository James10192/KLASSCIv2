@php
    $emailTitle = 'Bulletin disponible';
    $statutTon = 'info';
    $statutTexte = 'Bulletin disponible';
    $service = 'Scolarité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la scolarité.";
    $moyenne = number_format((float) $moyenneGenerale, 2, ',', ' ');
    $reussite = (float) $moyenneGenerale >= 10;
    $rangTexte = ! empty($rang) && $rang !== 'N/A' ? $rang.(! empty($effectifClasse) && $effectifClasse !== 'N/A' ? ' / '.$effectifClasse : '') : null;
    $verdict = implode(' · ', array_filter([
        ! empty($mention) ? 'Mention '.$mention : null,
        ! empty($decision) ? $decision : null,
    ])) ?: ($reussite ? 'Au-dessus de la moyenne' : 'En dessous de la moyenne');
    $assiduite = isset($noteAssiduite) ? ($noteAssiduite >= 0 ? '+' : '').number_format((float) $noteAssiduite, 2, ',', ' ') : null;
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')Bulletin « {{ $periode }} » : {{ $studentName }} obtient {{ $moyenne }}/20{{ $rangTexte ? ', rang '.$rangTexte : '' }}.
@endsection

@section('titre')Nouveau bulletin pour {{ $studentName }}
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, le bulletin «&nbsp;{{ $periode }}&nbsp;»{{ ! empty($anneeUniversitaire) && $anneeUniversitaire !== 'N/A' ? ' ('.$anneeUniversitaire.')' : '' }} vient d'être publié.@if(! $reussite) La moyenne est en dessous de 10/20&nbsp;: nous vous encourageons à suivre de près son travail et à le soutenir.@endif</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Moyenne générale',
    'valeur' => $moyenne,
    'unite' => '/20',
    'precision' => $verdict,
    'tonPrecision' => $reussite ? $emailSuccessText : $emailWarningText,
])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Classe', $classe ?? null],
    ['Période', $periode ?? null],
    ['Rang', $rangTexte],
    ['Absences', isset($totalAbsences) ? $totalAbsences.' h' : null],
    ["Note d'assiduité", $assiduite],
], 'marge' => '8px 0 0'])
@if(! empty($appreciationGenerale))
@include('esbtp.emails.parents.partials.citation', ['titre' => 'Appréciation générale', 'texte' => $appreciationGenerale])
@endif
@if(! empty($requiresSignature))
@include('esbtp.emails.parents.partials.citation', ['titre' => 'Signature requise', 'texte' => "Téléchargez le bulletin, signez-le et retournez-le à l'administration.", 'ton' => $emailWarningColor])
@endif
@include('esbtp.emails.partials.bouton', ['url' => $bulletinUrl, 'libelle' => 'Télécharger le bulletin', 'pleineLargeur' => true])
<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Le détail des notes par matière est dans l'espace parent de la plateforme.</p>
@endsection
