@extends('esbtp.emails.layout', ['emailTitle' => $annonce->titre])

@section('preheader', \Illuminate\Support\Str::limit($annonce->titre, 120))

@section('content')
    <p style="margin:0 0 16px;font-size:16px;font-weight:600;color:#0f172a;">
        Communication de votre établissement
    </p>
    @if($annonce->priorite == 2)
        <p style="color:#9a3412;font-size:13px;font-weight:700;margin:0 0 14px;">INFORMATION IMPORTANTE</p>
    @endif
    <h1 style="font-size:22px;line-height:1.35;color:#0f172a;margin:0 0 20px;">
        {{ $annonce->titre }}
    </h1>
    <div style="font-size:15px;line-height:1.75;color:#334155;white-space:pre-line;overflow-wrap:anywhere;">{{ trim($annonce->contenu) }}</div>
    @include('esbtp.emails.partials.bouton', [
        'url' => $lien,
        'libelle' => 'Consulter mon espace KLASSCI',
        'afficherLien' => false,
    ])
    <p style="font-size:12px;line-height:1.6;color:#64748b;margin:20px 0 0;">
        Message envoyé depuis KLASSCI pour votre établissement.
        Les pièces jointes restent accessibles depuis votre espace sécurisé.
    </p>
@endsection
