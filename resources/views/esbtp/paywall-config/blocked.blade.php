@extends('layouts.app')

@section('title', 'Accès bloqué - KLASSCI')

@push('styles')
@include('esbtp.paywall-config.partials._styles')
@endpush

@section('content')
@include('esbtp.paywall-config.partials._ecole', [
    'titre' => 'Accès bloqué',
    'intro' => 'L\'accès est suspendu : voici pourquoi, et comment le rétablir.',
    'icone' => 'fa-lock',
])
@endsection
