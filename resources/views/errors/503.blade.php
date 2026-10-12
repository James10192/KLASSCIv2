@extends('layouts.guest-error')
@section('title', 'KLASSCI — Service momentanément indisponible')
@section('content')
    @include('errors.partials.notice', [
        'code' => '503',
        'heading' => 'Une courte interruption',
        'message' => 'Le service est temporairement indisponible. Vos informations ne sont pas perdues. Veuillez réessayer un peu plus tard.',
        'primaryUrl' => route('login'),
        'primaryLabel' => 'Retourner à KLASSCI',
    ])
@endsection
