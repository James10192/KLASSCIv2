@extends('layouts.app')

@section('title', 'Profil étudiant à rattacher')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Votre profil étudiant n'est pas encore rattaché</div>

                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading">Bienvenue sur KLASSCI, {{ $user->name }}</h5>
                        <p class="mb-0">
                            Votre compte existe, mais il n'est encore relié à aucun dossier étudiant.
                            Tant que ce rattachement n'est pas fait, vos notes, votre emploi du temps
                            et votre situation financière ne peuvent pas s'afficher.
                        </p>
                    </div>

                    {{-- Un dossier etudiant est cree par l'ecole au moment de l'inscription :
                         matricule, filiere, niveau et classe sont des donnees dont l'ecole est
                         seule responsable. Il n'existe donc pas d'auto-inscription ici. --}}
                    <p>
                        Le dossier étudiant est créé par l'établissement lors de l'inscription.
                        Rapprochez-vous du service de scolarité en précisant l'adresse du compte
                        ci-dessous, afin qu'il rattache votre dossier.
                    </p>

                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Nom du compte</span>
                            <strong>{{ $user->name }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Adresse e-mail</span>
                            <strong>{{ $user->email ?? 'Non renseignée' }}</strong>
                        </li>
                        @if($user->username)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Identifiant</span>
                                <strong>{{ $user->username }}</strong>
                            </li>
                        @endif
                    </ul>

                    <a href="{{ route('logout') }}" class="btn btn-secondary"
                       onclick="event.preventDefault(); document.getElementById('etudiant-setup-logout').submit();">
                        Se déconnecter
                    </a>
                    <form id="etudiant-setup-logout" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
