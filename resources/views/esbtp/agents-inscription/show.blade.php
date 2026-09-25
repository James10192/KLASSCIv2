@extends('layouts.app')

@section('title', $agent->name.' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@include('esbtp.partials.role-staff-show', [
    'routeBase' => 'esbtp.agents-inscription',
    'icon' => 'fa-user-plus',
    'roleLabel' => 'Agent d\'inscription',
    'model' => $agent,
    'activite' => $activite ?? null,
])
@endsection
