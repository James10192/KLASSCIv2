@extends('layouts.app')

@section('title', 'Affectation Tronc Commun / Spécialité - KLASSCI')

@php
    $filiereOptions = $filieres->mapWithKeys(fn ($f) => [$f->id => $f->name . ($f->is_tronc_commun ? ' (Tronc commun)' : '')])->all();
    $niveauOptions = $niveaux->mapWithKeys(fn ($n) => [$n->id => $n->name])->all();
    $niveauSearchable = count($niveauOptions) > 8;
@endphp

@push('styles')
<style>
    .mtc-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .mtc-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .mtc-hero-left { display: flex; align-items: center; gap: 1rem; }
    .mtc-hero-icon {
        width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff; flex-shrink: 0;
    }
    .mtc-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .mtc-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .15rem 0 0; }
    .mtc-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .mtc-kpi { flex: 1; min-width: 120px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .8rem 1rem; }
    .mtc-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .mtc-kpi-label { font-size: .68rem; color: rgba(255,255,255,.68); margin-top: .1rem; text-transform: uppercase; letter-spacing: .04em; }

    .mtc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.5rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04); margin-bottom: 1.25rem; }
    .mtc-filters { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
    .mtc-field { display: flex; flex-direction: column; gap: .35rem; min-width: 240px; flex: 1; }
    .mtc-field label { font-size: .74rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }

    .mtc-banner { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem; border-radius: 10px;
        font-size: .84rem; margin-bottom: 1rem; }
    .mtc-banner--tc { background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.18); color: #0453cb; }
    .mtc-banner--info { background: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; }

    .mtc-bulk { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; align-items: center; }
    .mtc-bulk .mtc-bulk-lbl { font-size: .78rem; color: #64748b; margin-right: .25rem; }
    .mtc-mini { padding: .4rem .8rem; border-radius: 8px; font-size: .78rem; font-weight: 600; cursor: pointer;
        border: 1px solid #d7e0ec; background: #fff; color: #475569; transition: all .15s; }
    .mtc-mini:hover:not(:disabled) { background: rgba(4,83,203,.06); color: #0453cb; border-color: #b9cdec; }
    .mtc-mini:disabled { opacity: .55; cursor: wait; }

    .mtc-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .7rem .9rem;
        border: 1px solid #eef2f7; border-radius: 10px; margin-bottom: .5rem; background: #fff; }
    .mtc-row:hover { border-color: #d7e0ec; }
    .mtc-row-main { display: flex; align-items: center; gap: .7rem; min-width: 0; }
    .mtc-row-name { font-weight: 600; color: #1e293b; font-size: .92rem; }
    .mtc-row-code { font-size: .72rem; color: #94a3b8; font-family: 'Courier New', monospace; background: #f1f5f9;
        padding: .1rem .4rem; border-radius: 5px; }
    .mtc-suggest { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
        color: #a5670a; background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.28);
        padding: .1rem .45rem; border-radius: 5px; }

    /* Choix des matières à rattacher */
    .mtc-ajout-recherche { width: 100%; padding: .55rem .8rem; border: 1px solid #d7e0ec; border-radius: 9px;
        font-size: .86rem; color: #1e293b; margin-bottom: .75rem; }
    .mtc-ajout-recherche:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
    .mtc-ajout-ligne { display: flex; align-items: center; gap: .6rem; padding: .5rem .65rem; border-radius: 8px;
        cursor: pointer; font-size: .88rem; color: #1e293b; }
    .mtc-ajout-ligne:hover { background: rgba(4,83,203,.05); }
    .mtc-ajout-ligne--prise { color: #94a3b8; cursor: not-allowed; }
    .mtc-ajout-ligne--prise:hover { background: transparent; }
    .mtc-ajout-prise { margin-left: auto; font-size: .7rem; color: #64748b; }

    /* Retrait d'une matière de la maquette. Rouge assumé : l'action est
       destructive, et c'est la convention universelle — la palette monochrome
       ne vaut que pour le décor. */
    .mtc-retirer { flex-shrink: 0; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0;
        background: #fff; color: #94a3b8; font-size: .78rem; cursor: pointer; transition: all .15s ease;
        display: inline-flex; align-items: center; justify-content: center; }
    .mtc-retirer:hover:not(:disabled) { border-color: rgba(220,38,38,.35); background: rgba(220,38,38,.06); color: #dc2626; }
    .mtc-retirer:disabled { opacity: .45; cursor: not-allowed; }

    /* Toggle segmenté TC / Spé — état actif via :class, jamais :style inline */
    .mtc-seg { display: inline-flex; border: 1px solid #d7e0ec; border-radius: 9px; overflow: hidden; flex-shrink: 0; }
    .mtc-seg-btn { padding: .4rem .8rem; font-size: .78rem; font-weight: 600; cursor: pointer; border: none;
        background: #fff; color: #64748b; transition: background .15s, color .15s; white-space: nowrap; }
    .mtc-seg-btn + .mtc-seg-btn { border-left: 1px solid #d7e0ec; }
    .mtc-seg-btn:hover:not(.mtc-seg-btn--active) { background: rgba(4,83,203,.05); color: #0453cb; }
    .mtc-seg-btn--tc.mtc-seg-btn--active { background: #0453cb; color: #fff; }
    .mtc-seg-btn--spe.mtc-seg-btn--active { background: #334155; color: #fff; }

    /* Place sur le bulletin : numéro + deux flèches. Cibles tactiles 44px. */
    .mtc-rank { display: inline-flex; align-items: center; gap: .3rem; flex-shrink: 0; }
    .mtc-rank-input { width: 56px; height: 44px; text-align: center; font-size: .86rem; font-weight: 700;
        color: #0453cb; border: 1px solid #d7e0ec; border-radius: 9px; background: #fff; }
    .mtc-rank-input::placeholder { color: #cbd5e1; font-weight: 400; }
    .mtc-rank-input--herite { color: #94a3b8; font-weight: 500; background: #f8fafc; }
    .mtc-rank-btn { width: 44px; height: 44px; border: 1px solid #d7e0ec; border-radius: 9px; background: #fff;
        color: #64748b; cursor: pointer; font-size: .72rem; transition: background .15s, color .15s; }
    .mtc-rank-btn:hover:not(:disabled) { background: rgba(4,83,203,.06); color: #0453cb; }
    .mtc-rank-btn:disabled { opacity: .4; cursor: not-allowed; }
    .mtc-rank-tag { font-size: .64rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em;
        font-weight: 700; min-width: 62px; }

    /* Bandeau maquette : ce qui est prévu, et d'où ça viendrait */
    .mtc-maquette { display: flex; align-items: center; flex-wrap: wrap; gap: .75rem; padding: .8rem 1rem;
        border: 1px solid rgba(4,83,203,.18); border-radius: 11px; margin-bottom: .9rem;
        background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06)); }
    .mtc-maquette-txt { flex: 1; min-width: 220px; font-size: .84rem; color: #1e293b; }
    .mtc-maquette-txt small { display: block; color: #64748b; font-size: .76rem; margin-top: .15rem; }
    .mtc-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .7rem; border-radius: 8px;
        font-size: .76rem; font-weight: 600; background: rgba(4,83,203,.09); color: #0453cb;
        border: 1px solid rgba(4,83,203,.22); cursor: pointer; }
    .mtc-chip:hover { background: rgba(4,83,203,.16); }
    .mtc-chip--muted { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; cursor: default; }

    /* Aperçu de l'import : ce qui changerait, avant d'écrire quoi que ce soit */
    .mtc-modal { position: fixed; inset: 0; z-index: 2500; display: flex; align-items: center;
        justify-content: center; background: rgba(15,23,42,.45); padding: 1rem; }
    .mtc-modal-box { background: #fff; border-radius: 14px; width: 100%; max-width: 640px; max-height: 82vh;
        display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(15,23,42,.28); }
    .mtc-modal-head { padding: 1rem 1.2rem; border-bottom: 1px solid #eef2f7; }
    .mtc-modal-head h2 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
    .mtc-modal-head p { font-size: .8rem; color: #64748b; margin: .25rem 0 0; }
    .mtc-modal-body { padding: .8rem 1.2rem; overflow-y: auto; }
    .mtc-modal-foot { padding: .9rem 1.2rem; border-top: 1px solid #eef2f7; display: flex; justify-content: flex-end; gap: .6rem; }
    .mtc-diff-row { display: flex; align-items: center; justify-content: space-between; gap: .8rem;
        padding: .5rem .2rem; border-bottom: 1px solid #f5f7fa; font-size: .84rem; }
    .mtc-diff-row:last-child { border-bottom: none; }
    .mtc-diff-move { color: #0453cb; font-weight: 600; white-space: nowrap; }
    .mtc-diff-same { color: #94a3b8; white-space: nowrap; }

    .mtc-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    .mtc-empty i { font-size: 2rem; opacity: .5; margin-bottom: .5rem; display: block; }

    .mtc-savebar { display: flex; justify-content: flex-end; gap: .75rem; align-items: center; margin-top: 1rem; }
    .mtc-btn { padding: .6rem 1.3rem; border-radius: 10px; font-size: .86rem; font-weight: 600; cursor: pointer;
        border: none; background: #0453cb; color: #fff; transition: background .15s; }
    .mtc-btn:hover:not(:disabled) { background: #033a8e; }
    .mtc-btn:disabled { opacity: .6; cursor: wait; }

    /* Toast premium local */
    .mtc-toast-host { position: fixed; top: 1.1rem; right: 1.1rem; z-index: 3000; display: flex; flex-direction: column; gap: .5rem; }
    .mtc-toast { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem; border-radius: 10px; font-size: .85rem;
        font-weight: 600; color: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.18); opacity: 0; transform: translateX(20px);
        transition: opacity .25s, transform .25s; max-width: 360px; }
    .mtc-toast--in { opacity: 1; transform: translateX(0); }
    .mtc-toast--success { background: #0d9f74; }
    .mtc-toast--error { background: #dc2626; }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="matiereClassification()" x-init="init()" @change="onNativeChange($event)">
    <div class="mtc-hero">
        <div class="mtc-hero-top">
            <div class="mtc-hero-left">
                <div class="mtc-hero-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <h1>Affectation Tronc Commun / Spécialité</h1>
                    <p>Marquez, par filière et niveau, les matières du tronc commun et celles de spécialité. Le bulletin de tronc commun n'affichera que les matières TC.</p>
                </div>
            </div>
            <a href="{{ route('esbtp.matieres.index') }}" class="mtc-mini"><i class="fas fa-arrow-left"></i> Matières</a>
        </div>
        <div class="mtc-kpis" x-show="loaded" x-cloak>
            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.total"></div><div class="mtc-kpi-label">Matières</div></div>
            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.tronc_commun"></div><div class="mtc-kpi-label">Tronc commun</div></div>
            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.specialite"></div><div class="mtc-kpi-label">Spécialité</div></div>
            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.non_classe"></div><div class="mtc-kpi-label">Non classé</div></div>
        </div>
    </div>

    <div class="mtc-card">
        <div class="mtc-filters">
            <div class="mtc-field">
                <label>Filière</label>
                <x-au-select name="filiere_id" icon="fa-sitemap" placeholder="Choisir une filière"
                    :searchable="true" :options="$filiereOptions" />
            </div>
            <div class="mtc-field">
                <label>Niveau d'étude</label>
                <x-au-select name="niveau_id" icon="fa-graduation-cap" placeholder="Choisir un niveau"
                    :searchable="$niveauSearchable" :options="$niveauOptions" />
            </div>
        </div>
    </div>

    {{-- Chargement --}}
    <div class="mtc-card" x-show="loading" x-cloak>
        <div class="mtc-empty"><i class="fas fa-spinner fa-spin"></i>Chargement des matières…</div>
    </div>

    {{-- Résultat --}}
    <div class="mtc-card" x-show="loaded && !loading" x-cloak>
        <template x-if="isTroncCommun">
            <div class="mtc-banner mtc-banner--tc"><i class="fas fa-info-circle"></i>
                <span>Filière de tronc commun. Les matières marquées « Spécialité » ci-dessous seront exclues du bulletin de tronc commun.</span>
            </div>
        </template>
        <template x-if="!isTroncCommun">
            <div class="mtc-banner mtc-banner--info"><i class="fas fa-info-circle"></i>
                <span>Filière de spécialité. La classification sert surtout à cadrer l'héritage tronc commun ; les matières de spécialité restent au bulletin de spécialité.</span>
            </div>
        </template>

        <template x-if="matieres.length > 0">
            <div>
                @include('esbtp.matieres.partials._classification-maquette')

                <div class="mtc-bulk">
                    <span class="mtc-bulk-lbl">Tout marquer :</span>
                    <button type="button" class="mtc-mini" @click="bulk('tronc_commun')" :disabled="saving">Tronc commun</button>
                    <button type="button" class="mtc-mini" @click="bulk('specialite')" :disabled="saving">Spécialité</button>
                    <button type="button" class="mtc-mini" @click="bulk(null)" :disabled="saving">Effacer</button>
                    <template x-if="hasSuggestions">
                        <button type="button" class="mtc-mini" @click="applySuggestions()" :disabled="saving">
                            <i class="fas fa-wand-magic-sparkles"></i> Appliquer les suggestions
                        </button>
                    </template>
                </div>

                @include('esbtp.matieres.partials._classification-row')

                <div class="mtc-savebar">
                    <button type="button" class="mtc-mini" @click="resetOrdre()" :disabled="saving"
                        title="Efface les places propres à cette filière : les matières retrouvent l'ordre général.">
                        <i class="fas fa-rotate-left"></i> Revenir à l'ordre général
                    </button>
                    <button type="button" class="mtc-mini" @click="promouvoirOrdreGeneral()" :disabled="saving"
                        title="Fait de l'ordre affiché l'ordre général, repris par les filières qui n'ont pas le leur.">
                        <i class="fas fa-arrow-up-from-bracket"></i> Faire de cet ordre l'ordre général
                    </button>
                    <button type="button" class="mtc-btn" @click="save()" :disabled="saving">
                        <span x-show="!saving"><i class="fas fa-save"></i> Enregistrer</span>
                        <span x-show="saving" x-cloak>Enregistrement…</span>
                    </button>
                </div>
            </div>
        </template>

        <template x-if="matieres.length === 0">
            <div class="mtc-empty"><i class="fas fa-inbox"></i>Aucune matière BTS rattachée à ce combo (filière, niveau).</div>
        </template>
    </div>

    {{-- État initial --}}
    <div class="mtc-card" x-show="!loaded && !loading" x-cloak>
        <div class="mtc-empty"><i class="fas fa-hand-pointer"></i>Choisissez une filière et un niveau pour afficher les matières à classer.</div>
    </div>
</div>
@endsection

@push('scripts')
@include('esbtp.matieres.partials._classification-script')
@endpush
