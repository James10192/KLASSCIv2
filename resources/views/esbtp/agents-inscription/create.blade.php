@extends('layouts.app')

@section('title', 'Nouvel agent d\'inscription - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-form', [
    'routeBase' => 'esbtp.agents-inscription',
    'icon' => 'fa-user-plus',
    'heading' => 'Nouvel agent d\'inscription',
    'subtitle' => 'Compte dédié aux inscriptions, sans aucun accès à la finance, aux notes ni au système.',
    'model' => null,
    'scope' => [
        ['allowed' => true, 'text' => 'Créer, éditer et valider les dossiers d\'inscription'],
        ['allowed' => true, 'text' => 'Consulter les étudiants, les classes et les filières'],
        ['allowed' => false, 'text' => 'Aucun montant, solde ni reçu financier n\'est affiché'],
        ['allowed' => false, 'text' => 'Pas d\'accès aux notes, au personnel ni aux paramètres'],
    ],
])
@endsection
