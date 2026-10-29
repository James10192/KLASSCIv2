@extends('layouts.app')

@section('title', 'Abonnement à régulariser - KLASSCI')

@push('styles')
@include('esbtp.paywall-config.partials._styles')
@endpush

@section('content')
@include('esbtp.paywall-config.partials._ecole', [
    'titre' => 'Abonnement à régulariser',
    'intro' => 'Une limite ou l\'échéance de votre abonnement est atteinte.',
    'icone' => 'fa-chart-line',
])
@endsection
