@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Tableau de bord') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <h4>Bienvenue, {{ $user->name }} !</h4>
                    <p>Vous êtes connecté à l'application KLASSCI.</p>
                    
                    <div class="alert alert-info mt-4">
                        <p><strong>Remarque :</strong> Votre profil utilisateur n'est pas encore associé à un rôle spécifique dans le système.</p>
                        <p>Veuillez contacter l'administrateur pour configurer correctement votre compte.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 