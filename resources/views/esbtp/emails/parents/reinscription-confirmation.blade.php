@php
    $emailTitle = 'Confirmation de réinscription';
    $statutTon = 'succes';
    $statutTexte = 'Réinscription confirmée';
    $service = 'Scolarité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}, à sa réinscription. Pour toute question, contactez l'administration.";
    $couleurDecision = match ($decision ?? null) {
        'passage' => $emailSuccessText,
        'redoublement' => $emailWarningText,
        default => '#0f172a',
    };
    $decisionVue = ! empty($decision)
        ? new \Illuminate\Support\HtmlString('<span style="color:'.$couleurDecision.';">'.e(ucfirst($decision)).'</span>')
        : null;
    $reliquat = (float) ($reliquatMontant ?? 0);
    $classeConnue = \App\Helpers\ValeurConnue::ou($classe ?? null);
    $anneeConnue = \App\Helpers\ValeurConnue::ou($anneeUniversitaire ?? null);
    $reinscritLe = \App\Helpers\ValeurConnue::ou($dateReinscription ?? null);
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader'){{ $studentName }} est réinscrit(e){{ $classeConnue ? ' en '.$classeConnue : '' }}{{ $anneeConnue ? ' pour '.$anneeConnue : '' }}.
@endsection

@section('titre'){{ $studentName }} est réinscrit(e){{ $classeConnue ? ' en '.$classeConnue : '' }}
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, la réinscription{{ $anneeConnue ? " pour l'année ".$anneeConnue : '' }} a été enregistrée{{ $reinscritLe ? ' le '.$reinscritLe : '' }}. Vous continuez à suivre sa scolarité avec vos identifiants habituels.</p>
@if($reliquat > 0)
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Reliquat reporté',
    'valeur' => \App\Helpers\MontantFcfa::nombre($reliquat),
    'unite' => 'FCFA',
    'precision' => 'Reporté sur cette nouvelle inscription.',
    'tonPrecision' => $emailWarningText,
])
@endif
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Matricule', $matricule ?? null],
    ['Nouvelle classe', $classe ?? null],
    ['Filière', $filiere ?? null],
    ["Niveau d'étude", $niveauEtude ?? null],
    ['Année', $anneeUniversitaire ?? null],
    ['Réinscrit(e) le', $dateReinscription ?? null],
    ['Décision', $decisionVue],
], 'marge' => $reliquat > 0 ? '8px 0 0' : '0'])
@include('esbtp.emails.partials.bouton', ['url' => $platformUrl, 'libelle' => 'Accéder à la plateforme', 'pleineLargeur' => true])
<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Pour toute question sur cette réinscription, contactez l'administration.</p>
@endsection
