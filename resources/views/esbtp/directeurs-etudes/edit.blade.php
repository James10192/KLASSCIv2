@extends('layouts.app')

@section('title', 'Modifier — '.$directeur->name.' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-form', [
    'routeBase' => 'esbtp.directeurs-etudes',
    'icon' => 'fa-graduation-cap',
    'heading' => 'Nouveau directeur des études',
    'subtitle' => 'Compte de pilotage pédagogique, sans accès à la finance ni aux paramètres système.',
    'model' => $directeur,
    'scope' => [
        ['allowed' => true, 'text' => 'Consulter les emplois du temps, le planning et les résultats'],
        ['allowed' => true, 'text' => 'Ouvrir les rapports de rentrée, de trimestre et annuel'],
        ['allowed' => false, 'text' => 'Aucun accès à la caisse, à la comptabilité ni aux frais'],
        ['allowed' => false, 'text' => 'Ne peut pas modifier les paramètres du système'],
    ],
])
@endsection
