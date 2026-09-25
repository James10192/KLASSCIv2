@extends('layouts.app')

@section('title', $service->name.' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-show', [
    'routeBase' => 'esbtp.services-scolarite',
    'icon' => 'fa-print',
    'roleLabel' => 'Service scolarité',
    'model' => $service,
    'activite' => $activite ?? null,
])
@endsection
