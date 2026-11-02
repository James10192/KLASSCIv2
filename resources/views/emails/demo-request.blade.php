{{-- Alerte interne de prospection : identité KLASSCI (pas celle du prospect).
     Conserve tous les champs du formulaire public en texte échappé. --}}
@php
    $emailTitle = 'Nouvelle demande de démonstration';
    $statutTon = 'info';
    $statutTexte = 'Nouvelle demande';
    $service = 'Prospection · KLASSCI';
    $raison = "Message interne généré à partir du formulaire de demande de démonstration KLASSCI.";
    $potentiel = match ($nombre_etudiants) {
        'Plus de 5 000' => 'Élevé',
        '1 000 - 5 000' => 'Moyen',
        default => 'Standard',
    };
    $sujetReponse = 'Re: Demande de démonstration KLASSCI - '.$etablissement;
    $texteReponse = "Bonjour ".$nom.",\n\nNous avons bien reçu votre demande de démonstration pour ".$etablissement.".\nJe vous propose un échange de 30 minutes pour découvrir KLASSCI. Êtes-vous disponible cette semaine ?\n\nCordialement,\nÉquipe KLASSCI";
    $lienReponse = 'mailto:'.$email.'?subject='.rawurlencode($sujetReponse).'&body='.rawurlencode($texteReponse);
    $telephoneComposable = preg_replace('/[^0-9+]/', '', (string) $telephone);
@endphp
@extends('esbtp.emails.parents.recu', ['schoolName' => 'KLASSCI', 'schoolAddress' => '', 'schoolPhone' => '', 'schoolEmail' => '', 'schoolLogoUrl' => '', 'emailPrimaryColor' => '#0453cb'])

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
    ['Potentiel commercial', $potentiel],
]])
@if(!empty($message) && $message !== 'Aucun message spécifique')
@include('esbtp.emails.parents.partials.citation', [
    'titre' => 'Message du prospect',
    'texte' => $message,
    'ton' => '#0453cb',
])
@endif
<p style="margin:22px 0 16px;font-size:13px;color:#64748b;">Prospect qualifié · réponse souhaitée sous 24 heures.</p>
<p style="margin:0 0 20px;font-size:14px;line-height:1.7;">
    <a href="{{ $lienReponse }}" style="color:#0453cb;text-decoration:underline;font-weight:700;">Répondre par e-mail</a>
    @if($telephoneComposable !== '')
        &nbsp;·&nbsp;
        <a href="tel:{{ $telephoneComposable }}" style="color:#0453cb;text-decoration:underline;font-weight:700;">Appeler maintenant</a>
    @endif
</p>
@include('esbtp.emails.parents.partials.intertitre', ['texte' => "Informations techniques"])
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Adresse IP', $ip_address],
    ['Navigateur', \Illuminate\Support\Str::limit($user_agent, 100)],
    ['Formulaire', 'Page d\'accueil KLASSCI'],
]])
@endsection
