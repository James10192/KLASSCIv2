@extends('layouts.app')

@section('title', 'Candidatures en ligne - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .cd-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem;
    }
    .cd-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .cd-hero-left { display: flex; align-items: center; gap: 1rem; }
    .cd-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .cd-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .cd-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .cd-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .cd-kpi {
        flex: 1; min-width: 140px; background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem; text-decoration: none;
    }
    .cd-kpi--actif { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.4); }
    .cd-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .cd-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    .cd-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        overflow: hidden;
    }
    .cd-table { width: 100%; border-collapse: collapse; }
    .cd-table th {
        background: #f8fafc; text-align: left; padding: .75rem 1rem;
        font-size: .72rem; text-transform: uppercase; letter-spacing: .5px;
        color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0;
    }
    .cd-table td { padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .88rem; vertical-align: middle; }
    .cd-table tr:last-child td { border-bottom: none; }

    .cd-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .6rem; border-radius: 6px; font-size: .72rem; font-weight: 700; }
    .cd-badge--attente { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .cd-badge--acceptee { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .cd-badge--rejetee { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .cd-badge--convertie { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

    .cd-vide { padding: 3rem 1rem; text-align: center; color: #64748b; }
    .cd-vide i { font-size: 2.2rem; color: #cbd5e1; display: block; margin-bottom: .75rem; }
    .cd-contact { font-size: .78rem; color: #64748b; }
    /* Le tuteur est souvent le seul numero qui repond : il se distingue du
       contact du candidat sans pour autant crier. */
    .cd-tuteur { font-size: .78rem; color: #475569; margin-top: .3rem; }
    .cd-tuteur i { color: #94a3b8; margin-right: .25rem; }
    /* Declare par le candidat, pas verifie : le libelle porte la nuance, la
       couleur reste neutre pour ne pas se lire comme une decision de l'ecole. */
    .cd-affect { display: inline-block; margin-top: .2rem; padding: .1rem .45rem;
                 border-radius: 5px; background: #f1f5f9; color: #475569; font-size: .72rem; }
    .cd-affect em { font-style: normal; color: #94a3b8; }
    .cd-actions { display: flex; gap: .4rem; justify-content: flex-end; }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="cd-hero">
        <div class="cd-hero-top">
            <div class="cd-hero-left">
                <div class="cd-hero-icon"><i class="fas fa-address-card"></i></div>
                <div>
                    <h1>Candidatures en ligne</h1>
                    <p>Nouveaux étudiants ayant postulé depuis klassci.com. Rien n'est inscrit tant que vous n'avez pas décidé.</p>
                </div>
            </div>
        </div>
        <div class="cd-kpis">
            @php
                $_filtres = [
                    '' => ['Toutes', array_sum($compteurs->toArray())],
                    'en_attente' => ['En attente', $compteurs['en_attente'] ?? 0],
                    'acceptee' => ['Acceptées', $compteurs['acceptee'] ?? 0],
                    'rejetee' => ['Rejetées', $compteurs['rejetee'] ?? 0],
                ];
            @endphp
            @foreach($_filtres as $_cle => $_f)
                <a href="{{ route('esbtp.candidatures.index', array_filter(['statut' => $_cle])) }}"
                   class="cd-kpi {{ $statutActif === $_cle ? 'cd-kpi--actif' : '' }}">
                    <div>
                        <div class="cd-kpi-value">{{ $_f[1] }}</div>
                        <div class="cd-kpi-label">{{ $_f[0] }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="cd-card">
        @if($candidatures->isEmpty())
            <div class="cd-vide">
                <i class="fas fa-inbox"></i>
                Aucune candidature pour ce filtre.
                <div style="font-size:.8rem;margin-top:.4rem;">
                    Les candidatures arrivent ici dès qu'un nouvel étudiant postule depuis klassci.com.
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="cd-table">
                    <thead>
                        <tr>
                            <th>Candidat</th>
                            <th>Contact</th>
                            <th>Vœu</th>
                            <th>Parcours</th>
                            <th>Reçue le</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidatures as $c)
                            <tr>
                                <td>
                                    <strong>{{ $c->nomComplet() }}</strong>
                                    <div class="cd-contact">
                                        {{ $c->date_naissance?->format('d/m/Y') }}@if($c->lieu_naissance) à {{ $c->lieu_naissance }}@endif
                                        @if($c->sexe) &middot; {{ $c->sexe === 'F' ? 'Féminin' : 'Masculin' }} @endif
                                        @if($c->nationalite) &middot; {{ $c->nationalite }} @endif
                                    </div>
                                </td>
                                <td>
                                    {{-- Stocké en E.164 (« +2250707121234 »), parce que c'est la clé
                                         d'unicité du canal public. C'est un agent qui va le composer :
                                         on le lui rend lisible. --}}
                                    <div>{{ \App\Domain\Notifications\PhoneFormatter::toReadable($c->telephone) ?: $c->telephone }}</div>
                                    @if($c->email)
                                        <div class="cd-contact">{{ $c->email }}</div>
                                    @endif
                                    @if($c->ville || $c->commune)
                                        <div class="cd-contact">{{ collect([$c->commune, $c->ville])->filter()->join(', ') }}</div>
                                    @endif
                                    @if($c->tuteur_nom || $c->tuteur_telephone)
                                        <div class="cd-tuteur">
                                            <i class="fas fa-user-shield"></i>
                                            {{ $c->tuteur_nom ?: 'Tuteur' }}@if($c->tuteur_lien) ({{ $c->tuteur_lien }})@endif
                                            @if($c->tuteur_telephone) &middot; {{ $c->tuteur_telephone }} @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ $c->voeu() !== '' ? $c->voeu() : '—' }}
                                    <div class="cd-contact">{{ $c->anneeUniversitaire?->name }}</div>
                                </td>
                                <td class="cd-contact">
                                    @if($c->serie_bac) Série {{ $c->serie_bac }}<br> @endif
                                    @if($c->etablissement_origine) {{ $c->etablissement_origine }}<br> @endif
                                    @if($c->annee_bac) Bac {{ $c->annee_bac }}<br> @endif
                                    @if($c->affectation_status)
                                        <span class="cd-affect">{{-- Le libellé canonique, pas une reconstruction : `affectationsDeclarables()`
     existe pour que valeur et libellé voyagent ensemble. Écrit à la main, cette
     ligne rendait « Affecté » là où le portail public affiche « Affecté par
     l'État » — deux mots différents pour la même donnée, sur les deux écrans
     que la scolarité compare. --}}
{{ \App\Models\ESBTPCandidature::affectationsDeclarables()[$c->affectation_status] ?? $c->affectation_status }} <em>(déclaré)</em></span>
                                    @endif
                                    @if(! $c->serie_bac && ! $c->etablissement_origine && ! $c->annee_bac && ! $c->affectation_status) — @endif
                                </td>
                                <td class="cd-contact">{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    @php
                                        $_libelles = [
                                            'en_attente' => ['attente', 'En attente'],
                                            'acceptee' => ['acceptee', 'Acceptée'],
                                            'rejetee' => ['rejetee', 'Rejetée'],
                                            'convertie' => ['convertie', 'Inscrite'],
                                        ];
                                        $_b = $_libelles[$c->statut] ?? ['attente', $c->statut];
                                    @endphp
                                    <span class="cd-badge cd-badge--{{ $_b[0] }}">{{ $_b[1] }}</span>
                                    @if($c->motif_rejet)
                                        <div class="cd-contact" style="margin-top:.25rem;">{{ $c->motif_rejet }}</div>
                                    @endif
                                </td>
                                <td>
                                    @can('inscriptions.candidatures.process')
                                        @if($c->estTraitable())
                                            <div class="cd-actions">
                                                <form method="POST" action="{{ route('esbtp.candidatures.accepter', $c) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-acasi primary btn-sm">
                                                        <i class="fas fa-check"></i> Accepter
                                                    </button>
                                                </form>
                                                <button type="button" class="btn-acasi secondary btn-sm"
                                                        data-rejet-id="{{ $c->id }}"
                                                        data-rejet-nom="{{ $c->nomComplet() }}">
                                                    <i class="fas fa-times"></i> Rejeter
                                                </button>
                                            </div>
                                        @else
                                            <div class="cd-actions">
                                                {{-- La suite du parcours. Sans ce lien, la scolarité
                                                     retaperait à la main ce que le candidat a déjà
                                                     saisi. Ne pas compter les champs ici : le compte
                                                     a déjà vieilli une fois, et
                                                     PreRemplissageCandidature::valeurs() fait foi. --}}
                                                @if($c->statut === \App\Models\ESBTPCandidature::STATUT_ACCEPTEE)
                                                    @can('inscriptions.ouvrir-formulaire')
                                                        <a href="{{ route('esbtp.inscriptions.create', ['candidature' => $c->id]) }}"
                                                           class="btn-acasi primary btn-sm">
                                                            <i class="fas fa-user-plus"></i> Créer l'inscription
                                                        </a>
                                                    @endcan
                                                @endif
                                                <div class="cd-contact" style="text-align:right;">
                                                    {{ $c->traitePar?->name ? 'par '.$c->traitePar->name : '' }}
                                                    {{ $c->traite_at?->format('d/m/Y') }}
                                                </div>
                                            </div>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div style="margin-top:1rem;">{{ $candidatures->links() }}</div>
</div>

@can('inscriptions.candidatures.process')
{{-- Rejet : le motif est obligatoire, c'est ce qu'on dira a la famille qui rappelle. --}}
<div class="modal fade" id="cdRejetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="cdRejetForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Rejeter la candidature</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="cd-contact" id="cdRejetNom"></p>
                <label for="cd-motif" class="form-label">Motif du rejet</label>
                <textarea class="form-control" id="cd-motif" name="motif_rejet" rows="3" minlength="10" maxlength="1000" required
                          placeholder="Ce motif vous servira à répondre à la famille si elle rappelle."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn-acasi warning">Rejeter</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('cdRejetModal');
    if (!modalEl) return;

    var form = document.getElementById('cdRejetForm');
    var nom = document.getElementById('cdRejetNom');
    // Gabarit d'URL construit par le routeur : un chemin assemble a la main en
    // JavaScript casserait en silence le jour ou le prefixe change.
    var gabarit = @js(route('esbtp.candidatures.rejeter', ['candidature' => '__ID__']));

    document.querySelectorAll('[data-rejet-id]').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            form.action = gabarit.replace('__ID__', bouton.dataset.rejetId);
            nom.textContent = bouton.dataset.rejetNom;
            new bootstrap.Modal(modalEl).show();
        });
    });
});
</script>
@endpush
