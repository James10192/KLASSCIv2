@extends('layouts.app')

@section('title', 'Diagnostic des accès')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="main-card" style="margin-bottom: 1.5rem;">
            <h1 style="margin:0 0 .5rem; color:#1e293b; font-size:1.4rem;">Pourquoi quelqu'un n'a pas accès</h1>
            <p style="margin:0; color:#64748b;">Cherchez une personne, choisissez le droit en français. Aucun bouton pour voir l'écran à sa place.</p>
        </div>

        <div class="main-card" style="margin-bottom: 1.5rem;">
            <form method="get" action="{{ route('esbtp.diagnostic-acces.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Personne</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Nom, identifiant ou e-mail" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-acasi primary">Rechercher</button>
                </div>
            </form>
        </div>

        @if($personnes->isEmpty() && $q !== '')
            <div class="main-card">Aucun compte ne correspond.</div>
        @endif

        @if($personnes->isNotEmpty() && ! $cible)
            <div class="main-card">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Personne</th>
                            <th>Identifiant</th>
                            <th>État</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($personnes as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->username }}</td>
                                <td>{{ $p->is_active ? 'Actif' : 'Désactivé' }}</td>
                                <td>
                                    <a href="{{ route('esbtp.diagnostic-acces.show', ['user' => $p->id, 'permission' => 'notes.view']) }}" class="btn-acasi secondary">Diagnostiquer</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($cible)
            <div class="main-card" style="margin-bottom: 1.5rem;">
                <h2 style="font-size:1.1rem; margin-top:0;">{{ $cible->name }}</h2>
                <p style="color:#64748b;">{{ $cible->is_active ? 'Compte actif' : 'Compte désactivé' }}</p>
                <form method="get" action="{{ route('esbtp.diagnostic-acces.show', $cible) }}" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Droit à vérifier</label>
                        <select name="permission" class="form-control">
                            @foreach($droits as $droit)
                                <option value="{{ $droit['name'] }}" @selected($permission === $droit['name'])>{{ $droit['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn-acasi primary">Vérifier</button>
                    </div>
                </form>

                @if($verdict)
                    <div class="stat-card" style="margin-top:1.5rem; border-left:4px solid {{ $verdict->autorise ? '#10b981' : '#0453cb' }}; padding:1rem;">
                        <strong>{{ $verdict->autorise ? 'Accès possible' : 'Accès refusé' }}</strong>
                        <p style="margin:.5rem 0 0;">{{ $verdict->motif }}</p>
                    </div>
                @endif
            </div>
        @endif

        <div class="main-card">
            <h2 style="font-size:1.1rem; margin-top:0;">D'où viennent les règles</h2>
            @foreach($reglages as $reglage)
                <div style="padding:.75rem 0; border-bottom:1px solid #e2e8f0;">
                    <div style="font-weight:600; color:#1e293b;">{{ $reglage['libelle'] }}</div>
                    <div style="color:#0453cb;">{{ $reglage['valeur'] }}</div>
                    <div style="color:#64748b; font-size:.9rem;">{{ $reglage['niveau'] }}{{ $reglage['heritee'] ? ' · valeur par défaut du produit' : '' }}</div>
                    <p style="margin:.35rem 0 0; color:#64748b;">{{ $reglage['phrase'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
