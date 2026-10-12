@extends('layouts.guest-error')
@section('title', 'KLASSCI — Trop de tentatives')
@section('content')
    @include('errors.partials.notice', [
        'code' => '429',
        'heading' => 'Trop de tentatives',
        'message' => 'Pour votre sécurité, les demandes sont limitées. Patientez quelques instants avant de réessayer. Aucun nouvel e-mail ne peut être demandé immédiatement.',
        'primaryUrl' => route('password.request'),
        'primaryLabel' => 'Revenir au formulaire',
    ])
@endsection
