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
    .cd-filtre-ref { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.18); color: #1e3a6e; border-radius: 12px; padding: .7rem 1rem; margin-bottom: 1rem; font-size: .86rem; }
    .cd-filtre-ref i { color: #0453cb; }
    .cd-filtre-ref a { margin-left: auto; font-weight: 600; color: #0453cb; }
    .cd-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .cd-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* La ligne entiere est un declencheur : elle doit le dire. Pas de
       transform au survol, un dropdown ou un modal ouvert par-dessus s'en
       trouverait mal place (piege maison des contextes d'empilement). */
    .cd-ligne { cursor: pointer; }
    .cd-ligne:hover td { background: #f8fafc; }

    .cd-dossier-grille { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.5rem; }
    @@media (max-width: 640px) { .cd-dossier-grille { grid-template-columns: 1fr; } }
    .cd-bloc-titre {
        font-size: .68rem; text-transform: uppercase; letter-spacing: .6px;
        color: #64748b; font-weight: 700; margin-bottom: .35rem;
    }
    .cd-champ { display: flex; justify-content: space-between; gap: 1rem; padding: .3rem 0; border-bottom: 1px solid #f1f5f9; }
    .cd-champ:last-child { border-bottom: 0; }
    .cd-champ-nom { color: #64748b; font-size: .8rem; }
    .cd-champ-valeur { color: #1e293b; font-size: .85rem; font-weight: 600; text-align: right; }
    .cd-bloc { grid-column: span 2; }
    @@media (max-width: 640px) { .cd-bloc { grid-column: span 1; } }

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
    .cd-transfert { display: inline-flex; align-items: center; gap: .3rem; margin-top: .2rem;
        padding: .1rem .45rem; border-radius: 5px; font-size: .7rem; font-weight: 700;
        background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.25); }
    .cd-transfert-de { font-size: .72rem; color: #475569; margin-top: .15rem; }
    .cd-actions { display: flex; gap: .4rem; justify-content: flex-end; }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    {{-- .dashboard-acasi est la coquille flex du gabarit (barre laterale + contenu).
         Sans .main-content, le hero et le tableau en devenaient deux COLONNES :
         le titre se retrouvait a gauche, la liste a cote. --}}
    <div class="main-content">
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
        <x-filtre-contact-non-verifie route="esbtp.candidatures.index" />
    </div>

    <x-flash-demandes />

    @if(($referenceActive ?? '') !== '')
        <div class="cd-filtre-ref">
            <i class="fas fa-filter"></i>
            <span>Dossier de référence <strong>{{ $referenceActive }}</strong></span>
            <a href="{{ route('esbtp.candidatures.index') }}">Voir toutes les candidatures</a>
        </div>
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
                            <th>@rang('parcours')</th>
                            <th>Reçue le</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidatures as $c)
                            @php
                                // Tout ce que le candidat a declare, rassemble pour le modal.
                                $_dossier = [
                                    'nom' => $c->nomComplet(),
                                    'naissance' => trim(($c->date_naissance?->format('d/m/Y') ?: '')
                                        .($c->lieu_naissance ? ' à '.$c->lieu_naissance : '')),
                                    'sexe' => $c->sexe ? ($c->sexe === 'F' ? 'Féminin' : 'Masculin') : '',
                                    'nationalite' => (string) $c->nationalite,
                                    'telephone' => \App\Domain\Notifications\PhoneFormatter::toReadable($c->telephone) ?: (string) $c->telephone,
                                    'email' => (string) $c->email,
                                    'residence' => collect([$c->commune, $c->ville])->filter()->join(', '),
                                    'voeu' => $c->voeu() !== '' ? $c->voeu() : '',
                                    'annee' => (string) $c->anneeUniversitaire?->name,
                                    'serie_bac' => (string) $c->serie_bac,
                                    'etablissement_origine' => (string) $c->etablissement_origine,
                                    'annee_bac' => (string) $c->annee_bac,
                                    'affectation' => $c->affectation_status
                                        ? (\App\Models\ESBTPCandidature::affectationsDeclarables()[$c->affectation_status] ?? $c->affectation_status)
                                        : '',
                                    'etablissement_sup_origine' => (string) $c->etablissement_sup_origine,
                                    'formation_origine' => (string) $c->formation_origine,
                                    'niveau_atteint_origine' => (string) $c->niveau_atteint_origine,
                                    'annee_derniere_inscription' => (string) $c->annee_derniere_inscription,
                                    'motif_transfert' => (string) $c->motif_transfert,
                                    'tuteur_nom' => (string) $c->tuteur_nom,
                                    'tuteur_lien' => (string) $c->tuteur_lien,
                                    'tuteur_telephone' => \App\Domain\Notifications\PhoneFormatter::toReadable($c->tuteur_telephone) ?: (string) $c->tuteur_telephone,
                                    'tuteur_profession' => (string) $c->tuteur_profession,
                                    'message' => (string) $c->message,
                                    'recue_le' => (string) $c->created_at?->format('d/m/Y à H:i'),
                                    'statut' => (string) $c->statut,
                                    'motif_rejet' => (string) $c->motif_rejet,
                                    'traitable' => $c->estTraitable(),
                                    'url_accepter' => route('esbtp.candidatures.accepter', $c),
                                    'id' => $c->id,
                                ];
                            @endphp
                            {{-- La ligne entiere ouvre le dossier : c'est le geste le plus
                                 rapide, et decider sur une ligne de tableau demandait sinon de
                                 deviner ce que le candidat avait declare. Le bouton « Voir »
                                 reste, pour qui cherche une cible explicite. --}}
                            <tr class="cd-ligne" data-dossier='@json($_dossier)'>
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
                                    {{-- Le transfert se voit AVANT d'ouvrir le dossier : c'est ce qui
                                         change l'instruction du dossier, et l'agent trie sur cette
                                         colonne. Le detail complet reste dans la fiche. --}}
                                    @if($c->est_transfert)
                                        <span class="cd-transfert"><i class="fas fa-right-left"></i> Transfert</span>
                                        @if($c->etablissement_sup_origine)
                                            <div class="cd-transfert-de">{{ $c->etablissement_sup_origine }}</div>
                                        @endif
                                    @endif
                                    @if($c->parcoursEstVide()) — @endif
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
                                    <x-demande-contact-badge :demande="$c" route="esbtp.candidatures.confirmer-contact" permission="inscriptions.candidatures.process" />
                                    @if($c->motif_rejet)
                                        <div class="cd-contact" style="margin-top:.25rem;">{{ $c->motif_rejet }}</div>
                                    @endif
                                </td>
                                <td>
                                    @can('inscriptions.candidatures.process')
                                        @if($c->estTraitable())
                                            <div class="cd-actions">
                                                <button type="button" class="btn-acasi secondary btn-sm cd-voir">
                                                    <i class="fas fa-eye"></i> Voir
                                                </button>
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
                                                <button type="button" class="btn-acasi secondary btn-sm cd-voir">
                                                    <i class="fas fa-eye"></i> Voir
                                                </button>
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
</div>

{{-- Le dossier complet, avant de decider.
     Une ligne de tableau ne tient pas ce que le candidat a declare : la serie du
     bac, l'etablissement d'origine, le tuteur et son lien, le message libre.
     Decider sans les avoir lus, c'est decider a l'aveugle — et une acceptation
     ne se reprend pas depuis cet ecran. --}}
<div class="modal fade" id="cdDossierModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="cdDossierNom"></h5>
                    <div class="cd-contact" id="cdDossierRecue"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="cd-dossier-grille" id="cdDossierCorps"></div>
            </div>
            <div class="modal-footer" id="cdDossierPied">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Fermer</button>
                @can('inscriptions.candidatures.process')
                    <form method="POST" id="cdDossierAccepter" style="display:none;">
                        @csrf
                        <button type="submit" class="btn-acasi primary">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                    </form>
                    <button type="button" class="btn-acasi warning" id="cdDossierRejeter" style="display:none;">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                @endcan
            </div>
        </div>
    </div>
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
                <div class="form-text">Si la famille a un rendez-vous à venir, son créneau est libéré pour une autre.</div>
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
// Le dossier complet, ouvert par la ligne entiere ou par le bouton « Voir ».
document.addEventListener('DOMContentLoaded', function () {
    var dossierEl = document.getElementById('cdDossierModal');

    if (dossierEl) {
        var corps = document.getElementById('cdDossierCorps');
        var titre = document.getElementById('cdDossierNom');
        var recue = document.getElementById('cdDossierRecue');
        var formAccepter = document.getElementById('cdDossierAccepter');
        var boutonRejeter = document.getElementById('cdDossierRejeter');

        // L'ordre de lecture d'un dossier : qui, comment le joindre, ce qu'il
        // demande, d'ou il vient, qui repond de lui, ce qu'il a ecrit.
        var BLOCS = [
            ['Identité', [['naissance', 'Naissance'], ['sexe', 'Sexe'], ['nationalite', 'Nationalité']]],
            ['Contact', [['telephone', 'Téléphone'], ['email', 'E-mail'], ['residence', 'Résidence']]],
            ['Vœu', [['voeu', 'Formation'], ['annee', 'Année visée']]],
            ['Scolarité antérieure', [['serie_bac', 'Série du bac'], ['annee_bac', 'Année du bac'], ['etablissement_origine', 'Établissement'], ['affectation', 'Affectation déclarée']]],
            // Bloc distinct de la scolarite anterieure, et non fondu dedans : le
            // bac dit ce que le candidat a OBTENU, ce bloc dit d'ou il ARRIVE.
            // Les melanger ferait lire un lycee et une universite sur la meme
            // liste, sans que rien ne dise laquelle il quitte. Les champs vides
            // ne sont pas rendus, donc le bloc disparait pour un bachelier.
            ['Transfert', [['etablissement_sup_origine', 'Établissement quitté'], ['formation_origine', 'Formation suivie'], ['niveau_atteint_origine', 'Dernier niveau validé'], ['annee_derniere_inscription', 'Dernière inscription'], ['motif_transfert', 'Motif']]],
            ['Tuteur', [['tuteur_nom', 'Nom'], ['tuteur_lien', 'Lien'], ['tuteur_telephone', 'Téléphone'], ['tuteur_profession', 'Profession']]],
        ];

        var ouvrir = function (dossier) {
            titre.textContent = dossier.nom;
            recue.textContent = dossier.recue_le ? 'Reçue le ' + dossier.recue_le : '';
            corps.innerHTML = '';

            BLOCS.forEach(function (bloc) {
                // Un bloc dont aucun champ n'est renseigne ne s'affiche pas :
                // une colonne de tirets ne dit rien que l'absence ne dise deja.
                var champs = bloc[1].filter(function (c) { return (dossier[c[0]] || '').trim() !== ''; });

                if (champs.length === 0) return;

                var div = document.createElement('div');
                var html = '<div class="cd-bloc-titre">' + bloc[0] + '</div>';

                champs.forEach(function (c) {
                    var valeur = document.createElement('span');
                    valeur.textContent = dossier[c[0]];
                    html += '<div class="cd-champ"><span class="cd-champ-nom">' + c[1]
                        + '</span><span class="cd-champ-valeur">' + valeur.innerHTML + '</span></div>';
                });

                div.innerHTML = html;
                corps.appendChild(div);
            });

            // Le message libre et le motif de rejet prennent toute la largeur :
            // ce sont des phrases, pas des valeurs.
            [['message', 'Message du candidat'], ['motif_rejet', 'Motif du rejet']].forEach(function (c) {
                if (!(dossier[c[0]] || '').trim()) return;

                var div = document.createElement('div');
                div.className = 'cd-bloc';
                var p = document.createElement('p');
                p.className = 'cd-champ-valeur';
                p.style.textAlign = 'left';
                p.style.fontWeight = '500';
                p.textContent = dossier[c[0]];
                div.innerHTML = '<div class="cd-bloc-titre">' + c[1] + '</div>';
                div.appendChild(p);
                corps.appendChild(div);
            });

            // Les decisions ne s'affichent que sur un dossier qui en attend une.
            if (formAccepter) {
                formAccepter.style.display = dossier.traitable ? '' : 'none';
                formAccepter.action = dossier.url_accepter;
            }

            if (boutonRejeter) {
                boutonRejeter.style.display = dossier.traitable ? '' : 'none';
                boutonRejeter.dataset.rejetId = dossier.id;
                boutonRejeter.dataset.rejetNom = dossier.nom;
            }

            new bootstrap.Modal(dossierEl).show();
        };

        var lire = function (element) {
            try {
                return JSON.parse(element.dataset.dossier);
            } catch (e) {
                return null;
            }
        };

        document.querySelectorAll('tr.cd-ligne').forEach(function (ligne) {
            ligne.addEventListener('click', function (ev) {
                // Un clic sur une action reste un clic sur cette action : sans
                // cette garde, accepter ouvrirait aussi le dossier par-dessus.
                if (ev.target.closest('a, button:not(.cd-voir), form, input, select, textarea')) return;

                var dossier = lire(ligne);
                if (dossier) ouvrir(dossier);
            });
        });

        document.querySelectorAll('.cd-voir').forEach(function (bouton) {
            bouton.addEventListener('click', function (ev) {
                ev.stopPropagation();

                var ligne = bouton.closest('tr.cd-ligne');
                var dossier = ligne ? lire(ligne) : null;
                if (dossier) ouvrir(dossier);
            });
        });
    }

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

            // Fermer le dossier d'abord : deux modals Bootstrap superposes
            // laissent derriere eux un voile que rien ne retire, et la page
            // reste inutilisable jusqu'au rechargement.
            var dossierOuvert = document.getElementById('cdDossierModal');
            var instance = dossierOuvert ? bootstrap.Modal.getInstance(dossierOuvert) : null;

            if (instance) {
                dossierOuvert.addEventListener('hidden.bs.modal', function ouvrirMotif() {
                    dossierOuvert.removeEventListener('hidden.bs.modal', ouvrirMotif);
                    new bootstrap.Modal(modalEl).show();
                });
                instance.hide();

                return;
            }

            new bootstrap.Modal(modalEl).show();
        });
    });
});
</script>
@endpush
