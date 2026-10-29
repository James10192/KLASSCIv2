@extends('esbtp.emails.layout', ['emailTitle' => $annonce->titre])
@section('preheader', $annonce->titre)
@section('subtitle', 'Communication officielle de votre établissement')
@section('content')
    <p style="margin:0 0 12px;font-size:12px;font-weight:700;color:{{ $emailPrimaryColor }};">ANNONCE AUX ÉTUDIANTS</p>
    <h2 style="margin:0 0 20px;font-size:22px;line-height:1.4;color:#0f172a;">{{ $annonce->titre }}</h2>
    <div class="instruction-box" style="white-space:pre-line;line-height:1.8;font-size:15px;">{{ $texte }}</div>
    <p style="font-size:14px;margin-top:20px;">Retrouvez cette annonce et les informations officielles dans votre espace KLASSCI.</p>
    @include('esbtp.emails.partials.bouton', ['url' => $lien, 'libelle' => 'Consulter mon espace KLASSCI', 'afficherLien' => false])
@endsection
