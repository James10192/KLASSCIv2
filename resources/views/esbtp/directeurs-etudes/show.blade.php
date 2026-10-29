@extends('layouts.app')

@section('title', $directeur->name.' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-show', [
    'routeBase' => 'esbtp.directeurs-etudes',
    'icon' => 'fa-graduation-cap',
    'roleLabel' => 'Directeur des études',
    'model' => $directeur,
    'activite' => $activite ?? null,
])
@endsection
