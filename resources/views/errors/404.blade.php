@extends('layouts.app')

@section('title', \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI') . ' — Page non trouvée')

@section('page_title', 'Page non trouvée')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="display-1 fw-bold text-primary mb-3">404</div>
                    <h2 class="mb-4">Page non trouvée</h2>
                    <p class="mb-4 text-muted">
                        La page que vous recherchez n'existe pas ou a été déplacée.
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
