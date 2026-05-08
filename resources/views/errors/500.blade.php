@extends('layouts.app')

@section('title', \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI') . ' — Erreur serveur')

@section('page_title', 'Erreur serveur')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="display-1 fw-bold text-secondary mb-3">500</div>
                    <h2 class="mb-4">Erreur serveur</h2>
                    <p class="mb-4 text-muted">
                        Une erreur s'est produite sur le serveur. Veuillez réessayer plus tard ou contacter l'administrateur si le problème persiste.
                    </p>
                    <div class="mt-4">
                        <a href="{{ url('/') }}" class="btn btn-primary">Retour à l'accueil</a>
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary ms-2">Tableau de bord</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
