@php
    $emailTitle = $annonce->titre;
    $statutTon = $annonce->priorite === 2 ? 'alerte' : 'info';
    $statutTexte = $annonce->priorite === 2 ? 'Communiqué important' : 'Annonce aux étudiants';
    $service = 'Direction des études';
    $raison = 'Ce communiqué vous est adressé via KLASSCI, conformément au public sélectionné par votre établissement.';
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader'){{ \Illuminate\Support\Str::limit($corpsAnnonce, 140) }}@endsection
@section('titre'){{ $annonce->titre }}@endsection

@section('content')
    <div style="margin:16px 0 20px;padding:20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;line-height:1.75;color:#334155;font-size:15px;white-space:pre-line;">{{ $corpsAnnonce }}</div>
    @if($annonce->date_expiration)
        <p style="margin:0 0 12px;font-size:13px;color:#64748b;">Disponible dans KLASSCI jusqu'au {{ $annonce->date_expiration->format('d/m/Y') }}.</p>
    @endif
    @include('esbtp.emails.partials.bouton', ['url' => $urlAnnonce, 'libelle' => "Consulter l'annonce dans KLASSCI", 'pleineLargeur' => true])
@endsection
