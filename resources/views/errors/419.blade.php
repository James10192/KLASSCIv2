@extends('layouts.guest-error')
@section('title', 'KLASSCI — Session expirée')
@section('content')
    @include('errors.partials.notice', [
        'code' => '419',
        'heading' => 'Votre session a expiré',
        'message' => 'Le formulaire est resté ouvert trop longtemps. Pour protéger votre compte, veuillez rouvrir le formulaire et réessayer.',
        'primaryUrl' => route('password.request'),
        'primaryLabel' => 'Reprendre la réinitialisation',
    ])
@endsection
