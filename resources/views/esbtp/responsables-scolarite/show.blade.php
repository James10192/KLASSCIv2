@extends('layouts.app')

@section('title', $responsable->name.' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-show', [
    'routeBase' => 'esbtp.responsables-scolarite',
    'icon' => 'fa-user-check',
    'roleLabel' => 'Responsable scolarité',
    'model' => $responsable,
    'activite' => $activite ?? null,
])
@endsection
