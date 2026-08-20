@extends('layouts.app')

@section('title', 'Modifier — '.$service->name.' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-form', [
    'routeBase' => 'esbtp.services-scolarite',
    'icon' => 'fa-print',
    'heading' => 'Nouveau service scolarité',
    'subtitle' => 'Compte d\'exécution : impression des documents approuvés et saisie des notes pendant les fenêtres ouvertes.',
    'model' => $service,
    'scope' => [
        ['allowed' => true, 'text' => 'Imprimer les documents déjà approuvés par le responsable'],
        ['allowed' => true, 'text' => 'Saisir les notes uniquement pendant une fenêtre ouverte'],
        ['allowed' => true, 'text' => 'Consulter la liste des étudiants'],
        ['allowed' => false, 'text' => 'Ne peut ni créer une inscription ni approuver un document'],
    ],
])
@endsection
