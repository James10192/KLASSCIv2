@php
    $emailTitle = 'Alerte performance académique';
    $statutTon = 'danger';
    $statutTexte = 'Résultats insuffisants';
    $service = 'Scolarité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}. Pour toute question, contactez la scolarité.";
    $moyenne = number_format((float) $moyenneGenerale, 2, ',', ' ');
    $rangTexte = ! empty($rang) && $rang !== 'N/A' ? $rang.(! empty($effectifClasse) && $effectifClasse !== 'N/A' ? ' / '.$effectifClasse : '') : null;
    // L'appelant réel nomme la matière `matiere`, l'exemple `nom` : les deux sont lus.
    $matieres = collect($matieresEnDifficulte ?? [])->map(fn ($m) => [
        \App\Helpers\ValeurConnue::ou($m['nom'] ?? $m['matiere'] ?? null) ?? 'Matière non précisée',
        number_format((float) ($m['moyenne'] ?? 0), 2, ',', ' ').'/20'.(isset($m['coefficient']) ? ' · coef. '.$m['coefficient'] : ''),
    ])->all();
    $periodeConnue = \App\Helpers\ValeurConnue::ou($periode ?? null);
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')La moyenne de {{ $studentName }} est de {{ $moyenne }}/20{{ $periodeConnue ? ' pour '.$periodeConnue : '' }}. Voici les matières à travailler.
@endsection

@section('titre')La moyenne de {{ $studentName }} est de {{ $moyenne }}/20
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, le bulletin{!! $periodeConnue ? ' «&nbsp;'.e($periodeConnue).'&nbsp;»' : '' !!} indique des résultats en dessous de la moyenne requise. Un suivi rapproché peut aider {{ $studentName }} à remonter.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Moyenne générale',
    'valeur' => $moyenne,
    'unite' => '/20',
    'precision' => 'Seuil de réussite : 10/20',
    'tonPrecision' => $emailDangerColor,
])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Classe', $classe ?? null],
    ['Période', $periode ?? null],
    ['Rang', $rangTexte],
    ['Décision', $decision ?? null],
], 'marge' => '8px 0 0'])
@if($matieres !== [])
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Matières en difficulté'])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => $matieres])
@endif
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Ce que nous suggérons'])
<ul style="margin:0;padding-left:20px;font-size:14px;line-height:1.7;color:#334155;">
<li>Suivez de près son travail et vérifiez ses devoirs chaque jour.</li>
<li>Parlez avec votre enfant pour identifier ses difficultés.</li>
<li>Envisagez des cours de soutien dans les matières en difficulté.</li>
<li>Prenez rendez-vous avec le coordinateur pédagogique.</li>
<li>Veillez à son assiduité{{ isset($tauxPresence) ? ' (taux de présence actuel : '.$tauxPresence.' %)' : '' }}.</li>
</ul>
@if(! empty($coursDisponibles))
<p style="margin:14px 0 0;font-size:14px;line-height:1.6;color:#334155;">L'établissement propose des cours de soutien&nbsp;: renseignez-vous auprès de l'administration.</p>
@endif
{{-- Rouge : une alerte de résultats, pas une décoration. Le second geste
     reste un lien simple, un deuxième bouton plein lui disputerait l'œil. --}}
@include('esbtp.emails.partials.bouton', ['url' => $bulletinUrl, 'libelle' => 'Consulter le bulletin complet', 'couleur' => $emailDangerColor, 'pleineLargeur' => true])
<p style="margin:18px 0 0;font-size:14px;color:#475569;">Besoin d'en parler&nbsp;? <a href="{{ $contactUrl }}" style="color:{{ $emailPrimaryColor }};font-weight:600;">Contacter le coordinateur</a></p>
@endsection
