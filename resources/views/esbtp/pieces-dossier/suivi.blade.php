@extends('layouts.app')

@section('title', 'Suivi des dossiers — KLASSCI')

@push('styles')
<style>
    .sp-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.75rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .sp-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .sp-hero-left { display: flex; align-items: center; gap: 1rem; }
    .sp-hero-icon {
        width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center; font-size: 1.35rem; color: #fff;
    }
    .sp-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .sp-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

    .sp-btn {
        background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.2);
        border-radius: 10px; padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
    }
    .sp-btn:hover { background: rgba(255,255,255,.24); color: #fff; }

    .sp-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .sp-kpi {
        flex: 1; min-width: 150px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .9rem 1rem;
    }
    .sp-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .sp-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    .sp-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        padding: 1.1rem 1.25rem;
    }

    .sp-filtres { display: flex; gap: .6rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 1rem; }
    .sp-champ { display: flex; flex-direction: column; gap: .25rem; min-width: 160px; }
    .sp-champ label { font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
    .sp-champ input {
        border: 1px solid #e2e8f0; border-radius: 9px; padding: .48rem .7rem;
        font-size: .84rem; font-family: inherit; color: #1e293b;
    }
    .sp-champ input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10); }
    .sp-valider {
        background: #0453cb; color: #fff; border: 1px solid #0453cb; border-radius: 9px;
        padding: .5rem 1.1rem; font-size: .84rem; font-weight: 600; cursor: pointer; font-family: inherit;
    }

    .sp-table { width: 100%; border-collapse: collapse; }
    .sp-table thead th {
        font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px;
        text-align: left; padding: .55rem .7rem; border-bottom: 1px solid #e2e8f0; white-space: nowrap;
    }
    .sp-table tbody td { padding: .62rem .7rem; border-bottom: 1px solid #f1f5f9; font-size: .85rem; color: #1e293b; vertical-align: middle; }
    .sp-table tbody tr:hover { background: #f8fafc; }

    .sp-eleve { display: flex; align-items: center; gap: .6rem; }
    .sp-avatar {
        width: 34px; height: 34px; border-radius: 9px; object-fit: cover; flex-shrink: 0;
        background: #e2e8f0; display: flex; align-items: center; justify-content: center;
        color: #64748b; font-size: .72rem; font-weight: 700;
    }
    .sp-nom { font-weight: 600; }
    .sp-matricule { font-size: .72rem; color: #64748b; }

    .sp-etat { font-size: .68rem; font-weight: 700; padding: .18rem .5rem; border-radius: 6px; white-space: nowrap; }
    .sp-etat--ok { background: rgba(16,185,129,.10); color: #047857; }
    .sp-etat--manque { background: rgba(245,158,11,.12); color: #b45309; }
    .sp-etat--relire { background: rgba(4,83,203,.10); color: #0453cb; }

    .sp-manquantes { font-size: .76rem; color: #64748b; }
    .sp-lien { color: #0453cb; font-weight: 600; text-decoration: none; font-size: .8rem; white-space: nowrap; }
    .sp-lien:hover { text-decoration: underline; }

    .sp-vide { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .sp-vide-icone { font-size: 2.2rem; color: #cbd5e1; }
    .sp-vide-titre { font-size: 1rem; font-weight: 600; color: #1e293b; margin-top: .7rem; }
    .sp-vide-texte { font-size: .85rem; margin-top: .35rem; line-height: 1.6; }

    .sp-note { font-size: .74rem; color: #94a3b8; margin-top: .8rem; line-height: 1.5; }

    .sp-defilement { overflow-x: auto; }

    @@media (max-width: 768px) {
        .sp-hero { padding: 1.5rem 1.25rem; }
    }
</style>
@endpush

@section('content')
<div class="main-content">

    <div class="sp-hero">
        <div class="sp-hero-top">
            <div class="sp-hero-left">
                <div class="sp-hero-icon"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <h1>Suivi des dossiers</h1>
                    <p>Qui n'a pas rendu ses pièces, et lesquelles.</p>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @can('pieces_dossier.view')
                    <a href="{{ route('esbtp.pieces-dossier.index') }}" class="sp-btn">
                        <i class="fas fa-list-check"></i> Catalogue des pièces
                    </a>
                @endcan
            </div>
        </div>

        @if($configure)
        <div class="sp-kpis">
            <div class="sp-kpi">
                <div class="sp-kpi-value">{{ $kpis['total'] }}</div>
                <div class="sp-kpi-label">Inscriptions examinées</div>
            </div>
            <div class="sp-kpi">
                <div class="sp-kpi-value">{{ $kpis['complets'] }}</div>
                <div class="sp-kpi-label">Dossiers complets</div>
            </div>
            <div class="sp-kpi">
                <div class="sp-kpi-value">{{ $kpis['incomplets'] }}</div>
                <div class="sp-kpi-label">Dossiers incomplets</div>
            </div>
            <div class="sp-kpi">
                <div class="sp-kpi-value">{{ $kpis['pieces_manquantes'] }}</div>
                <div class="sp-kpi-label">Pièces à réclamer</div>
            </div>
            <div class="sp-kpi">
                <div class="sp-kpi-value">{{ $kpis['a_relire'] }}</div>
                <div class="sp-kpi-label">Pièces à relire</div>
            </div>
        </div>
        @endif
    </div>

    <div class="sp-card">

        @if(! $configure)
            <div class="sp-vide">
                <div class="sp-vide-icone"><i class="fas fa-folder-open"></i></div>
                <div class="sp-vide-titre">Aucune pièce n'est réclamée pour l'instant</div>
                <div class="sp-vide-texte">
                    Cet écran suit ce que l'école demande à ses étudiants. Il reste vide
                    tant que le catalogue des pièces n'est pas rempli.
                </div>
                @can('pieces_dossier.configure')
                    <a href="{{ route('esbtp.pieces-dossier.index') }}" class="sp-valider"
                       style="display:inline-block;margin-top:1rem;text-decoration:none;">
                        Configurer le catalogue
                    </a>
                @endcan
            </div>
        @elseif($tropVolumineux)
            <div class="sp-vide">
                <div class="sp-vide-icone"><i class="fas fa-filter"></i></div>
                <div class="sp-vide-titre">{{ number_format($nombre ?? 0, 0, ',', ' ') }} inscriptions à examiner</div>
                <div class="sp-vide-texte">
                    C'est trop pour un seul écran : l'état d'un dossier se calcule, il ne se lit pas.
                    Choisissez une classe, ou cherchez un étudiant.
                </div>
            </div>
        @else

            <form method="GET" class="sp-filtres">
                <div class="sp-champ" style="min-width:200px;">
                    <label for="recherche">Étudiant</label>
                    <input type="search" id="recherche" name="recherche"
                           value="{{ $filtres['recherche'] }}" placeholder="Nom, prénoms ou matricule">
                </div>

                <x-au-select name="classe_id" :value="$filtres['classe_id']" icon="fa-chalkboard"
                             placeholder="Toutes les classes" :searchable="true"
                             :options="$classes->pluck('name', 'id')->all()" />

                <x-au-select name="etat" :value="$filtres['etat']" icon="fa-filter"
                             placeholder="Tous les dossiers"
                             :options="['incomplet' => 'Incomplets seulement', 'complet' => 'Complets seulement', 'a_relire' => 'Avec pièces à relire']" />

                <x-au-select name="annee_id" :value="$filtres['annee_id']" icon="fa-calendar"
                             placeholder="Année"
                             :options="$annees->pluck('name', 'id')->all()" />

                <button type="submit" class="sp-valider"><i class="fas fa-search"></i> Filtrer</button>
            </form>

            @if($lignes->isEmpty())
                <div class="sp-vide">
                    <div class="sp-vide-icone"><i class="fas fa-check-circle"></i></div>
                    <div class="sp-vide-titre">Rien à afficher</div>
                    <div class="sp-vide-texte">
                        Aucune inscription ne correspond à ces filtres.
                    </div>
                </div>
            @else
                <div class="sp-defilement">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Classe</th>
                                <th>Dossier</th>
                                <th>Ce qui manque</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lignes as $ligne)
                                @php
                                    $inscription = $ligne['inscription'];
                                    $synthese = $ligne['synthese'];
                                    $etudiant = $inscription->etudiant;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="sp-eleve">
                                            @if($etudiant?->photo_url)
                                                <img src="{{ $etudiant->photo_url }}" alt="" class="sp-avatar">
                                            @else
                                                <span class="sp-avatar">{{ mb_strtoupper(mb_substr($etudiant->prenoms ?? 'E', 0, 1)) }}{{ mb_strtoupper(mb_substr($etudiant->nom ?? '', 0, 1)) }}</span>
                                            @endif
                                            <div>
                                                <div class="sp-nom">{{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}</div>
                                                <div class="sp-matricule">{{ $etudiant->matricule ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $inscription->classe->name ?? '—' }}</td>
                                    <td>
                                        @if($synthese['complet'])
                                            <span class="sp-etat sp-etat--ok">Complet</span>
                                        @else
                                            <span class="sp-etat sp-etat--manque">
                                                {{ $synthese['manquantes'] }} {{ $synthese['manquantes'] > 1 ? 'pièces' : 'pièce' }}
                                            </span>
                                        @endif
                                        @if($synthese['a_relire'] > 0)
                                            <span class="sp-etat sp-etat--relire">{{ $synthese['a_relire'] }} à relire</span>
                                        @endif
                                    </td>
                                    <td class="sp-manquantes">
                                        {{ $synthese['libelles_manquants'] ? implode(', ', $synthese['libelles_manquants']) : '—' }}
                                    </td>
                                    <td>
                                        {{-- Le lien mene la ou le geste se fait : la fiche d'inscription,
                                             pas un formulaire de plus. --}}
                                        <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}#dossier"
                                           class="sp-lien">Ouvrir le dossier &rarr;</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:1rem;">{{ $lignes->links() }}</div>
            @endif

            <div class="sp-note">
                Les compteurs portent sur toutes les inscriptions de l'année et de la classe choisies,
                pas seulement sur la page affichée. Une inscription annulée n'y figure pas :
                son dossier n'a plus à être complété.
            </div>
        @endif
    </div>
</div>
@endsection
