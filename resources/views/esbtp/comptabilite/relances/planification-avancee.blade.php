@extends('layouts.app')

@section('title', 'Planification avancée des relances')

@section('content')
@php
    // Shell mobile : le formulaire de bureau reste dans .m-only-desktop ; l'écran
    // mobile est le pas-à-pas partagé (partials/_planification-mobile), qui pose
    // aussi la fabrique Alpine window.rlcPlanif et la configuration
    // window.rlpCfgPartage lues par le formulaire de bureau.
    $rlpShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $rlpEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $rlpEcoleNom = $rlpEcole['name'] ?: ($rlpEcole['acronym'] ?: config('app.name'));
    $rlpPeutLancer = auth()->user()?->can('comptabilite.relances.send') ?? false;

    // Options du sélecteur « niveaux » (bornes de la validation serveur : 1..5) ;
    // trois niveaux par défaut, comme les trois délais configurés.
    $rlpNiveauDefaut = 3;
    $rlpNiveauxOptions = [];
    foreach (range(1, 5) as $rlpN) {
        $rlpNiveauxOptions[$rlpN] = $rlpN . ($rlpN > 1 ? ' niveaux' : ' niveau');
    }
    $rlpSegmentationsBureau = [
        'auto'                => ['Automatique', 'Combine la dette et le retard en trois priorités. Recommandé.', 'fa-wand-magic-sparkles'],
        'niveau_retard'       => ['Par niveau de retard', 'Retard léger, moyen ou sévère.', 'fa-hourglass-half'],
        'montant_dette'       => ['Par montant de dette', 'Dette faible, moyenne ou élevée.', 'fa-coins'],
        'historique_paiement' => ['Par historique', 'Bon payeur, irrégulier, mauvais payeur.', 'fa-history'],
        'classe'              => ['Par classe', 'Un segment par classe.', 'fa-users'],
    ];
    $rlpCanauxBureau = [
        'email'    => ['E-mail',   'Tous niveaux',              'fa-envelope'],
        'sms'      => ['SMS',      'Privilégié dès le 2e niveau', 'fa-sms'],
        'courrier' => ['Courrier', 'Privilégié au 3e niveau',   'fa-file-pdf'],
    ];
@endphp

<div class="container-fluid rlp-page">

<div class="{{ $rlpShell ? 'm-only-desktop' : '' }}" x-data="rlcPlanif(window.rlpCfgPartage)">

    <div class="dashboard-header rlp-header">
        <div class="header-left">
            <h1><i class="fas fa-layer-group me-2"></i>Planification avancée des relances</h1>
            <p class="header-subtitle">Découpez la population en retard, choisissez les canaux, vérifiez qui est concerné, puis lancez · {{ $rlpEcoleNom }}</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.comptabilite.relances.config') }}" class="btn-acasi secondary">
                <i class="fas fa-sliders-h"></i>Configuration
            </a>
            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i>Retour
            </a>
        </div>
    </div>

    {{-- Terminé : le résultat remplace le formulaire, sans rechargement --}}
    <div class="rlp-done" x-show="etape === 4" x-cloak>
        <div class="rlp-done-icon"><i class="fas fa-check"></i></div>
        <h4>Planification lancée</h4>
        <p x-text="resultat"></p>
        <div class="rlp-done-acts">
            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="rlp-save"><i class="fas fa-bell"></i> Voir les relances</a>
            <button type="button" class="rlp-ghost" x-on:click="reset()">Nouvelle planification</button>
        </div>
    </div>

    <div class="row g-4" x-show="etape !== 4">

        <div class="col-lg-8">

            {{-- 1. Population --}}
            <div class="rlp-card">
                <div class="rlp-card-head">
                    <div class="rlp-card-num">1</div>
                    <div>
                        <h5 class="rlp-card-title">Population à relancer</h5>
                        <div class="rlp-card-sub">Le découpage détermine les segments et, pour chaque étudiant, la stratégie de canal.</div>
                    </div>
                </div>
                <div class="rlp-card-body">
                    <div class="rlp-seg-grid" role="radiogroup" aria-label="Découpage de la population">
                        @foreach($rlpSegmentationsBureau as $rlpKey => $rlpS)
                            <label class="rlp-seg" x-bind:class="form.segmentation === '{{ $rlpKey }}' ? 'rlp-seg--on' : ''">
                                <input type="radio" name="segmentation" value="{{ $rlpKey }}" x-model="form.segmentation" x-on:change="segments = null">
                                <span class="rlp-seg-icon"><i class="fas {{ $rlpS[2] }}"></i></span>
                                <span class="rlp-seg-text">
                                    <b>{{ $rlpS[0] }}</b>
                                    <span>{{ $rlpS[1] }}</span>
                                </span>
                                <span class="rlp-seg-check"><i class="fas fa-check"></i></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 2. Niveaux et canaux --}}
            <div class="rlp-card">
                <div class="rlp-card-head">
                    <div class="rlp-card-num">2</div>
                    <div>
                        <h5 class="rlp-card-title">Niveaux et canaux</h5>
                        <div class="rlp-card-sub">Un étudiant repart du niveau suivant sa dernière relance, jusqu'au maximum choisi.</div>
                    </div>
                </div>
                <div class="rlp-card-body">
                    <div class="rlp-field rlp-field--niv">
                        <label class="rlp-label">Nombre maximum de niveaux</label>
                        <x-au-select name="niveau_max"
                                     :value="$rlpNiveauDefaut"
                                     :options="$rlpNiveauxOptions"
                                     :placeholder-is-first-option="false"
                                     placeholder="Niveaux"
                                     icon="fa-layer-group"
                                     x-model.number="form.niveau_max" />
                    </div>

                    <label class="rlp-label">Canaux autorisés</label>
                    <div class="rlp-canaux">
                        @foreach($rlpCanauxBureau as $rlpKey => $rlpC)
                            <label class="rlp-canal" x-bind:class="form.types_relance.includes('{{ $rlpKey }}') ? 'rlp-canal--on' : ''">
                                <input type="checkbox" value="{{ $rlpKey }}" x-model="form.types_relance">
                                <span class="rlp-canal-box"><i class="fas fa-check"></i></span>
                                <span class="rlp-canal-text">
                                    <b><i class="fas {{ $rlpC[2] }}"></i> {{ $rlpC[0] }}</b>
                                    <span>{{ $rlpC[1] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="rlp-hint" x-show="form.types_relance.length === 0" x-cloak>Choisissez au moins un canal.</div>
                </div>
            </div>

            {{-- 3. Programmation --}}
            <div class="rlp-card">
                <div class="rlp-card-head">
                    <div class="rlp-card-num">3</div>
                    <div>
                        <h5 class="rlp-card-title">Programmation</h5>
                        <div class="rlp-card-sub">Tout de suite, ou à une date choisie.</div>
                    </div>
                </div>
                <div class="rlp-card-body rlp-exec">
                    <label class="rlp-radio" x-bind:class="form.execution_type === 'immediate' ? 'rlp-radio--on' : ''">
                        <input type="radio" name="execution_type" value="immediate" x-model="form.execution_type">
                        <span class="rlp-radio-dot"></span>
                        <span><b>Exécution immédiate</b><span>Les relances sont créées dès la confirmation.</span></span>
                    </label>
                    <label class="rlp-radio" x-bind:class="form.execution_type === 'programmee' ? 'rlp-radio--on' : ''">
                        <input type="radio" name="execution_type" value="programmee" x-model="form.execution_type">
                        <span class="rlp-radio-dot"></span>
                        <span><b>Exécution programmée</b><span>La planification part le jour choisi.</span></span>
                    </label>
                    <div class="rlp-field" x-show="form.execution_type === 'programmee'" x-cloak>
                        <label class="rlp-label" for="rlp-date">Date d'exécution</label>
                        <input type="date" class="rlp-input" id="rlp-date" x-model="form.date_execution" min="{{ now()->toDateString() }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Résumé + aperçu + lancement --}}
        <div class="col-lg-4">
            <div class="rlp-sticky">
                <div class="rlp-summary">
                    <div class="rlp-summary-title"><i class="fas fa-clipboard-list"></i> Résumé</div>
                    <dl>
                        <dt>Découpage</dt><dd x-text="libelleSegmentation"></dd>
                        <dt>Niveaux</dt><dd x-text="'jusqu\'au niveau ' + form.niveau_max"></dd>
                        <dt>Canaux</dt><dd x-text="libelleCanaux"></dd>
                        <dt>Exécution</dt><dd x-text="form.execution_type === 'immediate' ? 'Immédiate' : (form.date_execution ? 'Le ' + form.date_execution : 'Programmée · date à choisir')"></dd>
                    </dl>
                    <div class="rlp-summary-total">
                        <span>Étudiants concernés</span>
                        <b x-text="segments ? totalEtudiants : '—'"></b>
                    </div>
                    <div class="rlp-summary-sub" x-show="segments" x-cloak x-text="formatMoney(totalDette) + ' FCFA de dette cumulée'"></div>
                </div>

                <div class="rlp-card">
                    <div class="rlp-card-head rlp-card-head--tight">
                        <div class="rlp-card-num"><i class="fas fa-eye"></i></div>
                        <div>
                            <h5 class="rlp-card-title">Aperçu de la population</h5>
                            <div class="rlp-card-sub">Calculé sur les données réelles du moment.</div>
                        </div>
                    </div>
                    <div class="rlp-card-body">
                        <div class="rlp-alert rlp-alert--bad" x-show="erreur" x-cloak><i class="fas fa-times-circle"></i><div x-text="erreur"></div></div>

                        <button type="button" class="rlp-ghost rlp-ghost--full" x-bind:disabled="chargement" x-on:click="chargerApercu(false)">
                            <i class="fas" x-bind:class="chargement ? 'fa-spinner fa-spin' : 'fa-eye'"></i>
                            <span x-text="chargement ? 'Calcul…' : (segments ? 'Recalculer l\'aperçu' : 'Voir la population concernée')"></span>
                        </button>

                        <div class="rlp-skel" x-show="chargement" x-cloak><i></i><i></i><i></i></div>

                        <template x-if="!chargement && segments">
                            <div class="rlp-segments">
                                <div class="rlp-empty" x-show="totalEtudiants === 0">
                                    <i class="fas fa-check-circle"></i>
                                    <b>Personne à relancer</b>
                                    <span>Aucun étudiant ne remplit les critères de ce découpage aujourd'hui.</span>
                                </div>
                                <template x-for="s in segmentsListe" x-bind:key="s.key">
                                    <div class="rlp-segment" x-bind:class="s.nombre === 0 ? 'rlp-segment--vide' : ''">
                                        <div class="rlp-segment-count" x-text="s.nombre"></div>
                                        <div class="rlp-segment-text">
                                            <b x-text="s.label"></b>
                                            <span x-text="s.exemples.length ? s.exemples.join(', ') + (s.nombre > s.exemples.length ? '…' : '') : 'Aucun étudiant'"></span>
                                        </div>
                                        <div class="rlp-segment-amt" x-text="formatMoney(s.dette) + ' FCFA'"></div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                @if($rlpPeutLancer)
                    <button type="button" class="rlp-save rlp-save--full" x-bind:disabled="chargement || lancement || !formValide" x-on:click="ouvrirConfirmation()">
                        <i class="fas fa-rocket"></i> Lancer la planification
                    </button>
                @else
                    <p class="rlp-readonly"><i class="fas fa-lock"></i> Le lancement demande le droit d'envoyer des relances.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Confirmation (bureau) : chiffres réels de l'aperçu --}}
    <div class="rlp-overlay" x-show="confirmOpen" x-cloak x-transition.opacity x-on:keydown.escape.window="confirmOpen = false">
        <div class="rlp-overlay-scrim" x-on:click="confirmOpen = false"></div>
        <div class="rlp-dialog" role="dialog" aria-modal="true" aria-labelledby="rlp-dialog-title">
            <div class="rlp-dialog-head">
                <h5 id="rlp-dialog-title"><i class="fas fa-rocket"></i> Confirmer la planification</h5>
                <button type="button" class="rlp-dialog-close" x-on:click="confirmOpen = false" aria-label="Fermer"><i class="fas fa-times"></i></button>
            </div>
            <div class="rlp-dialog-body">
                <div class="rlp-confirm-total">
                    <b x-text="totalEtudiants"></b>
                    <span x-text="(totalEtudiants > 1 ? 'étudiants' : 'étudiant') + ' · ' + formatMoney(totalDette) + ' FCFA'"></span>
                </div>
                <dl class="rlp-confirm-dl">
                    <dt>Découpage</dt><dd x-text="libelleSegmentation"></dd>
                    <dt>Niveaux</dt><dd x-text="'jusqu\'au niveau ' + form.niveau_max"></dd>
                    <dt>Canaux</dt><dd x-text="libelleCanaux"></dd>
                    <dt>Exécution</dt><dd x-text="form.execution_type === 'immediate' ? 'Immédiate' : 'Le ' + form.date_execution"></dd>
                </dl>
                <div class="rlp-alert rlp-alert--warn" x-show="totalEtudiants === 0" x-cloak><i class="fas fa-exclamation-triangle"></i><div>Personne n'est concerné : la planification ne créera aucune relance.</div></div>
                <div class="rlp-alert rlp-alert--bad" x-show="erreur" x-cloak><i class="fas fa-times-circle"></i><div x-text="erreur"></div></div>
            </div>
            <div class="rlp-dialog-foot">
                <button type="button" class="rlp-ghost" x-on:click="confirmOpen = false">Annuler</button>
                <button type="button" class="rlp-save" x-bind:disabled="lancement || totalEtudiants === 0" x-on:click="lancer()">
                    <i class="fas" x-bind:class="lancement ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="lancement ? 'Lancement…' : 'Confirmer'"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="rlp-toast" x-show="toast" x-cloak x-transition x-bind:class="'rlp-toast--' + toastType">
        <i class="fas" x-bind:class="toastType === 'error' ? 'fa-times-circle' : 'fa-check-circle'"></i>
        <span x-text="toast"></span>
    </div>
</div>

@if($rlpShell)
{{-- ============================ ÉCRAN MOBILE (shell m-*) ============================ --}}
{{-- La barre d'onglets et la navbar mobile sont rendues par le layout. --}}
<div class="m-only-mobile m-screen rlpp-screen">
    <x-m.appbar title="Planification avancée"
                :sub="$rlpEcoleNom"
                :back="route('esbtp.comptabilite.relances.config')" />
    <div class="m-body">
        @include('esbtp.comptabilite.relances.partials._planification-mobile')
    </div>
</div>
@else
    {{-- Sans shell : seuls les styles/scripts partagés (fabrique + configuration) sont posés. --}}
    @include('esbtp.comptabilite.relances.partials._planification-mobile', ['rlpRendreDom' => false])
@endif

</div>
@endsection

@push('styles')
<style>
/* ── Planification avancée (bureau) — namespace rlp-* ─────────────────── */
.rlp-page { --rlp-primary: #0453cb; --rlp-accent: #3b7ddb; --rlp-soft: #5e91de; --rlp-text: #1e293b; --rlp-muted: #64748b; --rlp-subtle: #94a3b8; --rlp-border: #e2e8f0; --rlp-surface: #f8fafc; }
.rlp-header .header-actions { flex-wrap: wrap; }

.rlp-card { background: #fff; border: 1px solid var(--rlp-border); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); margin-bottom: 1.25rem; }
.rlp-card-head { display: flex; align-items: center; gap: .85rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #f1f5f9; }
.rlp-card-head--tight { padding: .9rem 1.2rem; }
.rlp-card-num { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--rlp-primary), var(--rlp-accent)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0; }
.rlp-card-title { margin: 0; font-size: 1rem; font-weight: 700; color: var(--rlp-text); }
.rlp-card-sub { font-size: .76rem; color: var(--rlp-muted); margin-top: .1rem; }
.rlp-card-body { padding: 1.4rem; }

.rlp-seg-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; }
.rlp-seg { position: relative; display: flex; align-items: flex-start; gap: .8rem; border: 1.5px solid var(--rlp-border); border-radius: 12px; padding: .9rem 1rem; cursor: pointer; background: #fff; transition: border-color .15s, box-shadow .15s; margin: 0; }
.rlp-seg:hover { border-color: rgba(4,83,203,.35); }
.rlp-seg--on { border-color: var(--rlp-primary); background: rgba(4,83,203,.04); box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
.rlp-seg input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
.rlp-seg-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(4,83,203,.08); color: var(--rlp-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .95rem; }
.rlp-seg--on .rlp-seg-icon { background: var(--rlp-primary); color: #fff; }
.rlp-seg-text { flex: 1; min-width: 0; }
.rlp-seg-text b { display: block; font-size: .88rem; font-weight: 700; color: var(--rlp-text); }
.rlp-seg-text span { display: block; font-size: .75rem; color: var(--rlp-muted); margin-top: .15rem; line-height: 1.4; }
.rlp-seg-check { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: .6rem; color: transparent; flex-shrink: 0; margin-top: .15rem; }
.rlp-seg--on .rlp-seg-check { background: var(--rlp-primary); border-color: var(--rlp-primary); color: #fff; }

.rlp-field { margin-bottom: 1rem; }
.rlp-field--niv { max-width: 320px; display: flex; flex-direction: column; }
.rlp-label { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #475569; margin-bottom: .4rem; }
.rlp-input { width: 100%; border: 1.5px solid #dde5f0; border-radius: 10px; padding: .6rem .85rem; font-size: .88rem; color: var(--rlp-text); }
.rlp-input:focus { outline: none; border-color: var(--rlp-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.rlp-hint { font-size: .75rem; color: #b45309; margin-top: .5rem; font-weight: 600; }

.rlp-canaux { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.rlp-canal { position: relative; display: flex; align-items: flex-start; gap: .7rem; border: 1.5px solid var(--rlp-border); border-radius: 12px; padding: .85rem .95rem; cursor: pointer; background: #fff; transition: border-color .15s; margin: 0; }
.rlp-canal:hover { border-color: rgba(4,83,203,.35); }
.rlp-canal--on { border-color: var(--rlp-primary); background: rgba(4,83,203,.04); }
.rlp-canal input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
.rlp-canal-box { width: 20px; height: 20px; border-radius: 6px; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; color: transparent; font-size: .6rem; flex-shrink: 0; margin-top: .1rem; }
.rlp-canal--on .rlp-canal-box { background: var(--rlp-primary); border-color: var(--rlp-primary); color: #fff; }
.rlp-canal-text b { display: block; font-size: .86rem; font-weight: 700; color: var(--rlp-text); }
.rlp-canal-text b i { color: var(--rlp-accent); margin-right: .25rem; }
.rlp-canal-text span { display: block; font-size: .73rem; color: var(--rlp-muted); margin-top: .1rem; }

.rlp-exec { display: grid; gap: .75rem; }
.rlp-radio { position: relative; display: flex; align-items: flex-start; gap: .75rem; border: 1.5px solid var(--rlp-border); border-radius: 12px; padding: .85rem 1rem; cursor: pointer; margin: 0; background: #fff; transition: border-color .15s; }
.rlp-radio--on { border-color: var(--rlp-primary); background: rgba(4,83,203,.04); }
.rlp-radio input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
.rlp-radio-dot { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cbd5e1; flex-shrink: 0; margin-top: .1rem; }
.rlp-radio--on .rlp-radio-dot { border: 6px solid var(--rlp-primary); }
.rlp-radio > span:last-child b { display: block; font-size: .86rem; font-weight: 700; color: var(--rlp-text); }
.rlp-radio > span:last-child span { display: block; font-size: .73rem; color: var(--rlp-muted); margin-top: .1rem; }

@media (min-width: 992px) { .rlp-sticky { position: sticky; top: 80px; } }
.rlp-summary { background: linear-gradient(135deg, #0a3d8f 0%, var(--rlp-primary) 55%, var(--rlp-accent) 100%); color: #fff; border-radius: 16px; padding: 1.3rem 1.4rem; margin-bottom: 1.25rem; box-shadow: 0 10px 30px rgba(4,83,203,.22); }
.rlp-summary-title { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.75); margin-bottom: .9rem; display: flex; align-items: center; gap: .45rem; }
.rlp-summary dl { display: grid; grid-template-columns: auto 1fr; gap: .45rem .9rem; margin: 0; font-size: .84rem; }
.rlp-summary dt { color: rgba(255,255,255,.7); font-weight: 600; }
.rlp-summary dd { margin: 0; font-weight: 700; text-align: right; }
.rlp-summary-total { display: flex; justify-content: space-between; align-items: baseline; border-top: 1px solid rgba(255,255,255,.2); margin-top: 1rem; padding-top: .9rem; font-size: .86rem; }
.rlp-summary-total b { font-size: 1.6rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.rlp-summary-sub { font-size: .76rem; color: rgba(255,255,255,.75); margin-top: .2rem; text-align: right; }

.rlp-ghost { border: 1px solid rgba(4,83,203,.25); background: #fff; color: var(--rlp-primary); font-weight: 600; font-size: .82rem; padding: .55rem 1rem; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: .45rem; transition: background .15s; }
.rlp-ghost:hover:not(:disabled) { background: rgba(4,83,203,.06); }
.rlp-ghost:disabled { opacity: .6; cursor: wait; }
.rlp-ghost--full { width: 100%; }
.rlp-save { border: 0; background: linear-gradient(135deg, var(--rlp-primary), var(--rlp-accent)); color: #fff; font-weight: 700; font-size: .88rem; padding: .75rem 1.4rem; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; box-shadow: 0 6px 18px rgba(4,83,203,.22); text-decoration: none; transition: box-shadow .15s, opacity .15s; }
.rlp-save:hover:not(:disabled) { color: #fff; box-shadow: 0 8px 24px rgba(4,83,203,.3); }
.rlp-save:disabled { opacity: .6; cursor: not-allowed; }
.rlp-save--full { width: 100%; }
.rlp-readonly { font-size: .78rem; color: var(--rlp-muted); background: var(--rlp-surface); border: 1px dashed var(--rlp-border); border-radius: 10px; padding: .65rem .9rem; margin: 0; }
.rlp-readonly i { color: var(--rlp-subtle); margin-right: .35rem; }

.rlp-skel { display: grid; gap: 8px; margin-top: 1rem; }
.rlp-skel i { display: block; height: 56px; border-radius: 12px; background: linear-gradient(90deg, #e9edf5 25%, #f4f6fb 50%, #e9edf5 75%); background-size: 200% 100%; animation: rlp-sh 1.2s infinite; }
@keyframes rlp-sh { from { background-position: 200% 0; } to { background-position: -200% 0; } }
.rlp-segments { display: grid; gap: .5rem; margin-top: 1rem; }
.rlp-segment { display: grid; grid-template-columns: 40px 1fr auto; gap: .75rem; align-items: center; border: 1px solid var(--rlp-border); border-radius: 12px; padding: .65rem .8rem; }
.rlp-segment--vide { opacity: .55; }
.rlp-segment-count { width: 40px; height: 40px; border-radius: 10px; background: rgba(4,83,203,.1); color: var(--rlp-primary); font-weight: 800; display: flex; align-items: center; justify-content: center; font-variant-numeric: tabular-nums; }
.rlp-segment-text { min-width: 0; }
.rlp-segment-text b { display: block; font-size: .84rem; font-weight: 700; color: var(--rlp-text); }
.rlp-segment-text span { display: block; font-size: .72rem; color: var(--rlp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rlp-segment-amt { font-size: .8rem; font-weight: 700; color: var(--rlp-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
.rlp-empty { display: grid; justify-items: center; gap: .3rem; text-align: center; padding: 1.2rem .5rem; color: var(--rlp-muted); font-size: .8rem; }
.rlp-empty i { font-size: 1.6rem; color: #0f6b4c; }
.rlp-empty b { color: var(--rlp-text); font-size: .9rem; }

.rlp-alert { display: flex; gap: .65rem; align-items: flex-start; border-radius: 12px; padding: .8rem 1rem; font-size: .82rem; margin-bottom: 1rem; line-height: 1.45; }
.rlp-alert i { margin-top: .15rem; flex-shrink: 0; }
.rlp-alert--warn { background: #fff8e6; border: 1px solid #f5d48a; color: #7a4b00; }
.rlp-alert--bad { background: #fdecea; border: 1px solid #f5b5ad; color: #a12016; }

.rlp-done { background: #fff; border: 1px solid var(--rlp-border); border-radius: 16px; padding: 2.5rem 1.5rem; text-align: center; display: grid; justify-items: center; gap: .6rem; max-width: 640px; margin: 0 auto 1.5rem; }
.rlp-done-icon { width: 64px; height: 64px; border-radius: 50%; background: #e6f6ef; color: #0f6b4c; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
.rlp-done h4 { margin: .25rem 0 0; font-weight: 800; color: var(--rlp-text); }
.rlp-done p { margin: 0; color: var(--rlp-muted); font-size: .9rem; }
.rlp-done-acts { display: flex; gap: .75rem; flex-wrap: wrap; justify-content: center; margin-top: .75rem; }

.rlp-overlay { position: fixed; inset: 0; z-index: 1085; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.rlp-overlay-scrim { position: absolute; inset: 0; background: rgba(15,23,42,.45); }
.rlp-dialog { position: relative; width: min(520px, 100%); background: #fff; border-radius: 18px; box-shadow: 0 30px 80px rgba(15,23,42,.35); overflow: hidden; }
.rlp-dialog-head { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.4rem; background: linear-gradient(135deg, #0a3d8f, var(--rlp-primary)); color: #fff; }
.rlp-dialog-head h5 { margin: 0; font-size: .98rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
.rlp-dialog-close { border: 0; background: rgba(255,255,255,.15); color: #fff; width: 34px; height: 34px; border-radius: 9px; cursor: pointer; }
.rlp-dialog-body { padding: 1.4rem; }
.rlp-dialog-foot { display: flex; justify-content: flex-end; gap: .6rem; padding: .85rem 1.4rem; border-top: 1px solid var(--rlp-border); }
.rlp-confirm-total { text-align: center; margin-bottom: 1.1rem; }
.rlp-confirm-total b { display: block; font-size: 2.4rem; font-weight: 800; color: var(--rlp-primary); line-height: 1; font-variant-numeric: tabular-nums; }
.rlp-confirm-total span { font-size: .84rem; color: var(--rlp-muted); font-weight: 600; }
.rlp-confirm-dl { display: grid; grid-template-columns: auto 1fr; gap: .45rem .9rem; margin: 0 0 1rem; font-size: .86rem; }
.rlp-confirm-dl dt { color: var(--rlp-muted); font-weight: 600; }
.rlp-confirm-dl dd { margin: 0; font-weight: 700; text-align: right; color: var(--rlp-text); }

.rlp-toast { position: fixed; top: 20px; right: 20px; z-index: 1300; min-width: 280px; max-width: 420px; background: #fff; border: 1.5px solid var(--rlp-border); border-radius: 14px; padding: .9rem 1.1rem; display: flex; gap: .65rem; align-items: flex-start; font-size: .84rem; font-weight: 600; color: var(--rlp-text); box-shadow: 0 10px 35px rgba(15,23,42,.14); }
.rlp-toast i { margin-top: .1rem; color: var(--rlp-primary); }
.rlp-toast--success { border-color: rgba(16,185,129,.35); }
.rlp-toast--success i { color: #047857; }
.rlp-toast--error { border-color: rgba(220,38,38,.35); }
.rlp-toast--error i { color: #b42318; }

@media (max-width: 767.98px) { .rlp-seg-grid, .rlp-canaux { grid-template-columns: 1fr; } .rlp-card-body { padding: 1rem; } }

/* ── Écran mobile — namespace rlpp-* ─────────────────────────────────── */
.rlpp-screen { font-family: var(--m-font); }
</style>
@endpush
