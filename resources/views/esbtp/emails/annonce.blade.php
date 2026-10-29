@extends('esbtp.emails.layout', ['emailTitle' => 'Annonce - '.$annonce->titre])
@section('preheader', \Illuminate\Support\Str::limit(trim(strip_tags($annonce->contenu)), 130))
@section('content')
<p style="margin:0 0 10px;font-size:13px;color:#64748b;font-weight:700;text-transform:uppercase">Communication officielle</p>
<h1 style="margin:0 0 20px;font-size:23px;line-height:1.35;color:#0f172a">{{ $annonce->titre }}</h1>
<div style="padding:20px;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0;color:#1e293b;font-size:15px;line-height:1.8;white-space:pre-line;overflow-wrap:break-word">{{ trim($annonce->contenu) }}</div>
@include('esbtp.emails.partials.bouton', ['url' => route('esbtp.mes-annonces.index'), 'libelle' => 'Consulter mes annonces dans KLASSCI', 'afficherLien' => false])
<p style="margin:18px 0 0;color:#64748b;font-size:12px">Les éventuelles pièces jointes sont disponibles dans votre espace KLASSCI.</p>
@endsection
