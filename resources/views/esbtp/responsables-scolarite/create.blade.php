@extends('layouts.app')

@section('title', 'Nouveau responsable scolarité - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-form', [
    'routeBase' => 'esbtp.responsables-scolarite',
    'icon' => 'fa-user-check',
    'heading' => 'Nouveau responsable scolarité',
    'subtitle' => 'Compte d\'encadrement de la scolarité : approbations, fenêtres de notes et validation des inscriptions.',
    'model' => null,
    'scope' => [
        ['allowed' => true, 'text' => 'Approuver certificats, attestations et bulletins avant impression'],
        ['allowed' => true, 'text' => 'Ouvrir et fermer les fenêtres de saisie des notes'],
        ['allowed' => true, 'text' => 'Valider les inscriptions'],
        ['allowed' => false, 'text' => 'Aucun accès à la comptabilité ni aux paramètres'],
    ],
])
@endsection
