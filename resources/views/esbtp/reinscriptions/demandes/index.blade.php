@extends('layouts.app')

@section('title', 'Demandes de réinscription - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .rd-filtre-ref { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.18); color: #1e3a6e; border-radius: 12px; padding: .7rem 1rem; margin-bottom: 1rem; font-size: .86rem; }
    .rd-filtre-ref i { color: #0453cb; }
    .rd-filtre-ref a { margin-left: auto; font-weight: 600; color: #0453cb; }
    .rd-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
    }
    .rd-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .rd-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rd-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .rd-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .rd-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .rd-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .rd-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem;
        text-decoration: none;
    }
    .rd-kpi--actif { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.4); }
    .rd-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .rd-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    .rd-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        overflow: hidden;
    }
    .rd-table { width: 100%; border-collapse: collapse; }
    .rd-table th {
        background: #f8fafc; text-align: left; padding: .75rem 1rem;
        font-size: .72rem; text-transform: uppercase; letter-spacing: .5px;
        color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0;
    }
    .rd-table td { padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .88rem; vertical-align: middle; }
    .rd-table tr:last-child td { border-bottom: none; }

    .rd-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .6rem; border-radius: 6px;
        font-size: .72rem; font-weight: 700;
    }
    .rd-badge--attente { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .rd-badge--convertie { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .rd-badge--rejetee { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

    .rd-eleve { font-weight: 700; color: #1e293b; }
    .rd-matricule { font-size: .75rem; color: #64748b; font-family: 'Courier New', monospace; }

    .rd-empty { padding: 3rem 1rem; text-align: center; color: #64748b; }
    .rd-empty i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: .75rem; display: block; }

    .rd-btn {
        border: none; border-radius: 8px; padding: .45rem .85rem;
        font-size: .78rem; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: .35rem;
    }
    .rd-btn--convertir { background: #0453cb; color: #fff; }
    .rd-btn--convertir:hover { background: #033a8e; }
    .rd-btn--rejeter { background: #fff; color: #b91c1c; border: 1px solid #fecaca; }
    .rd-btn--rejeter:hover { background: #fef2f2; }
    .rd-btn:disabled { opacity: .55; cursor: not-allowed; }

    .rd-modal-backdrop {
        position: fixed; inset: 0; background: rgba(15,23,42,.45);
        display: flex; align-items: center; justify-content: center; z-index: 1080; padding: 1rem;
    }
    .rd-modal {
        background: #fff; border-radius: 14px; width: 100%; max-width: 480px;
        box-shadow: 0 20px 60px rgba(15,23,42,.25); overflow: hidden;
    }
    .rd-modal-head { padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #1e293b; }
    .rd-modal-body { padding: 1.25rem; }
    /* Le picker premium est en inline-flex : hors d'un parent flex il se
       reduit a la largeur de son contenu, et son menu devient illisible pour
       les noms de classe longs. Cf. .claude/rules/premium-selects.md */
    .rd-au-full { display: flex !important; width: 100%; margin-bottom: .35rem; }
    .rd-au-full .au-select-trigger { width: 100%; }
    .rd-modal-foot { padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: .5rem; }
    .rd-label { display: block; font-size: .78rem; font-weight: 700; color: #374151; margin-bottom: .35rem; }
    .rd-input, .rd-textarea {
        width: 100%; border: 1px solid #d1d5db; border-radius: 8px;
        padding: .55rem .7rem; font-size: .85rem; margin-bottom: .85rem;
    }
    .rd-textarea { min-height: 90px; resize: vertical; }
    .rd-hint { font-size: .75rem; color: #64748b; margin-top: -.6rem; margin-bottom: .85rem; }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" x-data="corbeilleDemandes()">

        <div class="rd-hero">
            <div class="rd-hero-top">
                <div class="rd-hero-left">
                    <div class="rd-hero-icon"><i class="fas fa-inbox"></i></div>
                    <div>
                        <h1>Demandes de réinscription</h1>
                        <p>Déposées en ligne par les étudiants. Une demande ne devient une inscription qu'une fois convertie ici.</p>
                    </div>
                </div>
            </div>
            <div class="rd-kpis">
                @php
                    $onglets = [
                        '' => ['Toutes', array_sum($compteurs->toArray())],
                        'en_attente' => ['En attente', $compteurs['en_attente'] ?? 0],
                        'convertie' => ['Réinscrits', $compteurs['convertie'] ?? 0],
                        'rejetee' => ['Rejetées', $compteurs['rejetee'] ?? 0],
                    ];
                @endphp
                @foreach($onglets as $cle => [$libelle, $total])
                    <a href="{{ route('esbtp.reinscription-demandes.index', $cle === '' ? [] : ['statut' => $cle]) }}"
                       class="rd-kpi {{ $statutActif === $cle ? 'rd-kpi--actif' : '' }}">
                        <div>
                            <div class="rd-kpi-value">{{ $total }}</div>
                            <div class="rd-kpi-label">{{ $libelle }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <x-flash-demandes />

        @if(($referenceActive ?? '') !== '')
            <div class="rd-filtre-ref">
                <i class="fas fa-filter"></i>
                <span>Demande de référence <strong>{{ $referenceActive }}</strong></span>
                <a href="{{ route('esbtp.reinscription-demandes.index') }}">Voir toutes les demandes</a>
            </div>
        @endif

        <div class="rd-card">
            @if($demandes->isEmpty())
                <div class="rd-empty">
                    <i class="fas fa-inbox"></i>
                    <div><strong>Aucune demande {{ $statutActif !== '' ? 'dans ce statut' : '' }}</strong></div>
                    <div style="font-size:.85rem;margin-top:.35rem;">
                        Les demandes déposées depuis klassci.com apparaîtront ici.
                    </div>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="rd-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Classe précédente</th>
                                <th>Année visée</th>
                                <th>Déposée le</th>
                                <th>Statut</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($demandes as $demande)
                            <tr>
                                <td>
                                    <div class="rd-eleve">{{ $demande->etudiant?->nom }} {{ $demande->etudiant?->prenoms }}</div>
                                    <div class="rd-matricule">{{ $demande->etudiant?->matricule }}</div>
                                </td>
                                <td>{{ $demande->classeSouhaitee?->name ?? '—' }}</td>
                                <td>{{ $demande->anneeUniversitaire?->name ?? '—' }}</td>
                                <td>{{ $demande->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    @php
                                        $ton = match($demande->statut) {
                                            'convertie' => 'convertie',
                                            'rejetee' => 'rejetee',
                                            default => 'attente',
                                        };
                                    @endphp
                                    <span class="rd-badge rd-badge--{{ $ton }}">{{ $demande->libelleStatut() }}</span>
                                    <x-demande-contact-badge :demande="$demande" route="esbtp.reinscription-demandes.confirmer-contact" permission="reinscriptions.demandes.process" />
                                    @if($demande->traitePar)
                                        <div style="font-size:.72rem;color:#64748b;margin-top:.2rem;">
                                            par {{ $demande->traitePar->name }}
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    @if($demande->estTraitable())
                                        @can('reinscriptions.demandes.process')
                                            <button type="button" class="rd-btn rd-btn--convertir"
                                                    @click="ouvrirConversion(@js(route('esbtp.reinscription-demandes.convertir', $demande)), @js(trim($demande->etudiant?->nom.' '.$demande->etudiant?->prenoms)), {{ $demande->classe_souhaitee_id ?? 'null' }})">
                                                <i class="fas fa-user-check"></i>Convertir
                                            </button>
                                            <button type="button" class="rd-btn rd-btn--rejeter"
                                                    @click="ouvrirRejet(@js(route('esbtp.reinscription-demandes.rejeter', $demande)))">
                                                Rejeter
                                            </button>
                                        @endcan
                                    @elseif($demande->motif_rejet)
                                        <span style="font-size:.78rem;color:#64748b;" title="{{ $demande->motif_rejet }}">
                                            {{ \Illuminate\Support\Str::limit($demande->motif_rejet, 40) }}
                                        </span>
                                    @else
                                        <span style="font-size:.78rem;color:#94a3b8;">Traitée</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:1rem;">{{ $demandes->links() }}</div>
            @endif
        </div>

        {{-- Conversion : la scolarite choisit la classe, l'etudiant ne l'a jamais choisie --}}
        <div class="rd-modal-backdrop" x-show="conversionOuverte" x-cloak @keydown.escape.window="conversionOuverte = false">
            <div class="rd-modal" @click.outside="conversionOuverte = false">
                {{-- EXCEPTION ajax-no-reload-premium : la conversion cree une inscription
                     et deplace la demande d un onglet a l autre, en changeant du meme
                     coup les compteurs de filtre et le badge de la barre laterale. Un
                     rechargement est ici plus honnete qu une resynchronisation partielle
                     de trois zones distinctes. --}}
                <form :action="urlConversion" method="POST" x-ref="formConversion">
                    @csrf
                    <div class="rd-modal-head">Réinscrire <span x-text="nomEleve"></span></div>
                    <div class="rd-modal-body">
                        <label class="rd-label">Classe d'affectation</label>
                        <x-au-select class="rd-au-full" name="classe_id" icon="fa-chalkboard"
                                     placeholder="Choisir une classe…" :searchable="true"
                                     :options="$classes->pluck('name', 'id')" x-model="classeChoisie" />
                        <div class="rd-hint">La classe de l'an dernier est présélectionnée. C'est vous qui décidez de l'affectation.</div>

                        <label class="rd-label">Décision</label>
                        <x-au-select class="rd-au-full" name="decision" icon="fa-route"
                                     :placeholder-is-first-option="false" value="passage"
                                     :options="[
                                        'passage' => 'Passage en année supérieure',
                                        'redoublement' => 'Redoublement',
                                        'rattrapage' => 'Rattrapage',
                                     ]" />

                        <label class="rd-label" for="rd-obs">Observations (facultatif)</label>
                        <textarea class="rd-textarea" id="rd-obs" name="observations" maxlength="1000"></textarea>
                    </div>
                    <div class="rd-modal-foot">
                        <button type="button" class="rd-btn" @click="conversionOuverte = false">Annuler</button>
                        <button type="submit" class="rd-btn rd-btn--convertir" :disabled="!classeChoisie">
                            <i class="fas fa-user-check"></i>Réinscrire
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Rejet : motif obligatoire, c'est ce qu'on dira a la famille qui appellera --}}
        <div class="rd-modal-backdrop" x-show="rejetOuvert" x-cloak @keydown.escape.window="rejetOuvert = false">
            <div class="rd-modal" @click.outside="rejetOuvert = false">
                {{-- EXCEPTION ajax-no-reload-premium : meme raison que la conversion. --}}
                <form :action="urlRejet" method="POST">
                    @csrf
                    <div class="rd-modal-head">Rejeter la demande</div>
                    <div class="rd-modal-body">
                        <label class="rd-label" for="rd-motif">Motif du rejet</label>
                        <textarea class="rd-textarea" id="rd-motif" name="motif_rejet"
                                  minlength="10" maxlength="1000" required x-model="motif"></textarea>
                        <div class="rd-hint">Ce motif reste interne. Il vous servira si la famille appelle.</div>
                    </div>
                    <div class="rd-modal-foot">
                        <button type="button" class="rd-btn" @click="rejetOuvert = false">Annuler</button>
                        <button type="submit" class="rd-btn rd-btn--rejeter" :disabled="motif.trim().length < 10">
                            Rejeter
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
if (typeof window.corbeilleDemandes !== 'function') {
    window.corbeilleDemandes = function () {
        return {
            conversionOuverte: false,
            rejetOuvert: false,
            urlConversion: '',
            urlRejet: '',
            nomEleve: '',
            classeChoisie: '',
            motif: '',
            ouvrirConversion(url, nom, classeId) {
                this.urlConversion = url;
                this.nomEleve = nom;
                this.classeChoisie = classeId ? String(classeId) : '';
                this.conversionOuverte = true;
                // x-model ecrit `.value` par programmation, ce qui n'emet aucun
                // evenement. Le picker premium, lui, se synchronise sur
                // `change` : sans ce reveil il resterait sur « Choisir une
                // classe… » alors que le formulaire poste bel et bien la classe
                // de l'an dernier. L'agent validerait une affectation qu'il n'a
                // jamais vue, sur l'ecran fait pour la lui faire choisir.
                this.$nextTick(() => {
                    this.$refs.formConversion
                        ?.querySelector('.au-select-native')
                        ?.dispatchEvent(new Event('change', { bubbles: true }));
                });
            },
            ouvrirRejet(url) {
                this.urlRejet = url;
                this.motif = '';
                this.rejetOuvert = true;
            },
        };
    };
}
</script>
@endpush
