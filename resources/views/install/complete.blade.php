@extends('install.layout')

@section('title', 'Finalisation')
@section('hero_title', 'UIC est prêt')
@section('hero_copy', 'Validez la fin de l’installation pour verrouiller l’assistant et ouvrir la connexion KLASSCI.')

@section('content')
<div class="grid-2">
    <section class="card card-pad">
        <h2 class="section-title">Installation terminée</h2>
        <p class="section-copy">Les éléments techniques nécessaires sont en place. Le mot de passe administrateur n’est pas réaffiché pour des raisons de sécurité.</p>

        <div class="check-list">
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-database"></i></span>
                <div><strong>Base configurée</strong><p class="hint">Connexion MySQL et fichier .env mis à jour.</p></div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-table"></i></span>
                <div><strong>Migrations appliquées</strong><p class="hint">Les tables KLASSCI ont été créées ou vérifiées.</p></div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-gears"></i></span>
                <div><strong>Setup exécuté</strong><p class="hint">Storage, permissions, settings et seeders critiques.</p></div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-user-shield"></i></span>
                <div>
                    <strong>Administrateur créé</strong>
                    <p class="hint">
                        Identifiant : <strong>{{ session('admin_username') }}</strong><br>
                        Email : <strong>{{ session('admin_email') }}</strong>
                    </p>
                </div>
            </div>
        </div>

        @if(session('esbtp_warning'))
            <div class="notice warning">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    <strong>Données métier à contrôler</strong>
                    <p class="hint">Certains seeders ou paramètres métier doivent être vérifiés depuis l’administration.</p>
                </div>
            </div>
        @endif

        <div class="btn-row">
            <a href="{{ route('install.admin') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Retour
            </a>
            <a href="{{ route('install.finalize.get') }}" class="btn btn-primary">
                Verrouiller et se connecter
                <i class="fas fa-lock"></i>
            </a>
        </div>
    </section>

    <aside class="card card-pad">
        <h2 class="section-title">Après connexion</h2>
        <div class="check-list">
            <div class="check-item">
                <span class="check-icon"><i class="fas fa-building-columns"></i></span>
                <div><strong>Paramètres établissement</strong><p class="hint">Complétez le nom officiel, logo, couleurs, documents et signatures.</p></div>
            </div>
            <div class="check-item">
                <span class="check-icon"><i class="fas fa-calendar-days"></i></span>
                <div><strong>Année universitaire</strong><p class="hint">Créez ou activez l’année de travail de l’UIC.</p></div>
            </div>
            <div class="check-item">
                <span class="check-icon"><i class="fas fa-layer-group"></i></span>
                <div><strong>Filières, niveaux, classes</strong><p class="hint">Structurez le référentiel avant inscriptions et notes.</p></div>
            </div>
            <div class="check-item">
                <span class="check-icon"><i class="fas fa-user-group"></i></span>
                <div><strong>Utilisateurs</strong><p class="hint">Ajoutez les secrétaires, enseignants, comptables et coordinateurs.</p></div>
            </div>
        </div>
    </aside>
</div>
@endsection
