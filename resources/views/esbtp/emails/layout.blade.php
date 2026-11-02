{{--
    Gabarit principal des courriels KLASSCI.
    Le courrier « Encaissements en retard » utilise déjà le socle
    esbtp.emails.parents.recu. Les autres notifications métier bénéficient
    maintenant de la même identité, tout en conservant leurs sections
    `preheader`, `subtitle`, `content` et `mention`.
    Ancien rendu disponible : esbtp.emails.layout-legacy.
    Aucun transport, contenu utilisateur ni destinataire n'est changé ici.
--}}
@php
    $statutTon = $statutTon ?? 'info';
    $statutTexte = $statutTexte ?? null;
    $service = $service ?? null;
    $raison = $raison ?? 'Communication de votre établissement.';
@endphp
@extends('esbtp.emails.parents.recu')

@section('titre')
{{ $emailTitle ?? $title ?? $schoolName ?? 'KLASSCI' }}
@endsection
