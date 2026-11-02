{{-- Alerte interne de prospection : identité KLASSCI (pas celle du prospect).
     Conserve tous les champs du formulaire public en texte échappé. --}}
@php
    $emailTitle = 'Nouvelle demande de démonstration';
    $statutTon = 'info';
    $statutTexte = 'Nouvelle demande';
    $service = 'Prospection · KLASSCI';
    $raison = "Message interne généré à partir du formulaire de demande de démonstration KLASSCI.";
@endphp
@extends('esbtp.emails.parents.recu', ['schoolName' => 'KLASSCI', 'schoolAddress' => null, 'schoolPhone' => null, 'schoolEmail' => null])

@section('preheader')Demande de démonstration reçue le {{ $date_demande }} de {{ $etablissement }}.
@endsection

@section('titre')Nouvelle demande de démonstration
@endsection

@section('content')
<p style="margin:0 0 14px;font-size:14px;color:#334155;">
    Une nouvelle demande de démonstration a été reçue le <strong>{{ $date_demande }}</strong>.
</p>
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Informations du contact'])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Nom complet', $nom],
    ['E-mail professionnel', $email],
    ['Téléphone', $telephone],
    ['Date de demande', $date_demande],
]])
@include('esbtp.emails.parents.partials.intertitre', ['texte' => "Établissement à contacter"])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ["Nom de l'établissement", $etablissement],
    ["Type d'établissement", $type_etablissement],
    ["Nombre d'étudiants", $nombre_etudiants],
]])
@if(!empty($message))
@include('esbtp.emails.parents.partials.citation', [
    'titre' => 'Message du prospect',
    'texte' => $message,
])
@endif
@include('esbtp.emails.parents.partials.intertitre', ['texte' => "Informations techniques"])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Adresse IP', $ip_address],
    ['Navigateur', $user_agent],
]])
@endsection
