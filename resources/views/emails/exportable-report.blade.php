@php
    $emailTitle = $reportTitle;
    $statutTon = 'info';
    $statutTexte = 'Rapport disponible';
    $service = 'Analytics · rapports';
    $raison = 'Message envoyé après votre demande de génération de rapport dans KLASSCI.';
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader')Votre rapport {{ $reportTitle }} est disponible en pièce jointe.
@endsection

@section('titre'){{ $reportTitle }}
@endsection

@section('content')
<p style="margin:0 0 16px;">Bonjour,</p>
<p style="margin:0 0 16px;">
    Vous trouverez en pièce jointe le rapport <strong>{{ $reportTitle }}</strong>
    @if($senderName) demandé par <strong>{{ $senderName }}</strong>@endif.
</p>
@if($reportSubtitle)
<p style="margin:0 0 16px;color:#64748b;">{{ $reportSubtitle }}</p>
@endif
@include('esbtp.emails.parents.partials.citation', [
    'titre' => 'Rapport en pièce jointe',
    'texte' => 'Document au format PDF, généré automatiquement par le moteur Analytics KLASSCI.',
    'ton' => $emailPrimaryColor
])
@endsection
