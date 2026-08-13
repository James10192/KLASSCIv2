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

    /* Toggle segmenté TC / Spé — état actif via :class, jamais :style inline */
    .mtc-seg { display: inline-flex; border: 1px solid #d7e0ec; border-radius: 9px; overflow: hidden; flex-shrink: 0; }
    .mtc-seg-btn { padding: .4rem .8rem; font-size: .78rem; font-weight: 600; cursor: pointer; border: none;
        background: #fff; color: #64748b; transition: background .15s, color .15s; white-space: nowrap; }
    .mtc-seg-btn + .mtc-seg-btn { border-left: 1px solid #d7e0ec; }
    .mtc-seg-btn:hover:not(.mtc-seg-btn--active) { background: rgba(4,83,203,.05); color: #0453cb; }
    .mtc-seg-btn--tc.mtc-seg-btn--active { background: #0453cb; color: #fff; }
    .mtc-seg-btn--spe.mtc-seg-btn--active { background: #334155; color: #fff; }

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

                <template x-for="m in matieres" :key="m.matiere_id">
                    <div class="mtc-row">
                        <div class="mtc-row-main">
                            <span class="mtc-row-name" x-text="m.name"></span>
                            <span class="mtc-row-code" x-show="m.code" x-text="m.code"></span>
                            <span class="mtc-suggest" x-show="m.wasSuggested && m.classification === 'specialite'">suggéré</span>
                        </div>
                        <div class="mtc-seg">
                            <button type="button" class="mtc-seg-btn mtc-seg-btn--tc"
                                :class="m.classification === 'tronc_commun' ? 'mtc-seg-btn--active' : ''"
                                @click="setClass(m, 'tronc_commun')">Tronc commun</button>
                            <button type="button" class="mtc-seg-btn mtc-seg-btn--spe"
                                :class="m.classification === 'specialite' ? 'mtc-seg-btn--active' : ''"
                                @click="setClass(m, 'specialite')">Spécialité</button>
                        </div>
                    </div>
                </template>

                <div class="mtc-savebar">
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
<script>
function matiereClassification() {
    return {
        filiereId: '',
        niveauId: '',
        loading: false,
        saving: false,
        loaded: false,
        isTroncCommun: false,
        filiereName: '',
        matieres: [],
        kpis: { total: 0, tronc_commun: 0, specialite: 0, non_classe: 0 },
        get hasSuggestions() { return this.matieres.some(m => m.suggested); },

        init() {
            this.$watch('filiereId', () => this.tryLoad());
            this.$watch('niveauId', () => this.tryLoad());
        },

        // Le composant au-select évalue son x-model dans son propre scope : on capte
        // plutôt l'evenement change du select natif qui bulle jusqu'a cette racine.
        onNativeChange(e) {
            const t = e && e.target;
            if (!t || !t.name) return;
            if (t.name === 'filiere_id') this.filiereId = t.value;
            if (t.name === 'niveau_id') this.niveauId = t.value;
        },

        tryLoad() {
            if (this.filiereId && this.niveauId) this.loadCombo();
        },

        notify(message, type) {
            let host = document.getElementById('mtc-toast-host');
            if (!host) {
                host = document.createElement('div');
                host.id = 'mtc-toast-host';
                host.className = 'mtc-toast-host';
                document.body.appendChild(host);
            }
            const el = document.createElement('div');
            el.className = 'mtc-toast mtc-toast--' + (type === 'error' ? 'error' : 'success');
            el.innerHTML = '<i class="fas ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span></span>';
            el.querySelector('span').textContent = message;
            host.appendChild(el);
            requestAnimationFrame(() => el.classList.add('mtc-toast--in'));
            setTimeout(() => {
                el.classList.remove('mtc-toast--in');
                setTimeout(() => el.remove(), 250);
            }, 3200);
        },

        async loadCombo() {
            this.loading = true;
            this.loaded = false;
            try {
                const url = "{{ route('esbtp.matieres.classification.combo') }}"
                    + "?filiere_id=" + encodeURIComponent(this.filiereId)
                    + "&niveau_id=" + encodeURIComponent(this.niveauId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Chargement impossible.');
                const data = await res.json();
                this.isTroncCommun = !!data.is_tronc_commun;
                this.filiereName = data.filiere || '';
                this.matieres = (data.matieres || []).map(m => ({
                    ...m,
                    wasSuggested: (m.classification === null && m.suggested != null),
                    classification: m.classification ?? (m.suggested ?? null),
                }));
                this.recomputeKpis();
                this.loaded = true;
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        setClass(m, val) {
            m.classification = (m.classification === val) ? null : val;
            this.recomputeKpis();
        },

        bulk(val) {
            this.matieres.forEach(m => { m.classification = val; });
            this.recomputeKpis();
        },

        applySuggestions() {
            this.matieres.forEach(m => { if (m.suggested) m.classification = m.suggested; });
            this.recomputeKpis();
        },

        recomputeKpis() {
            this.kpis = {
                total: this.matieres.length,
                tronc_commun: this.matieres.filter(m => m.classification === 'tronc_commun').length,
                specialite: this.matieres.filter(m => m.classification === 'specialite').length,
                non_classe: this.matieres.filter(m => !m.classification).length,
            };
        },

        async save() {
            this.saving = true;
            try {
                const res = await fetch("{{ route('esbtp.matieres.classification.save') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        filiere_id: this.filiereId,
                        niveau_id: this.niveauId,
                        classifications: this.matieres.map(m => ({ matiere_id: m.matiere_id, classification: m.classification })),
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Erreur lors de l\'enregistrement.');
                this.matieres.forEach(m => { m.wasSuggested = false; });
                this.notify(data.message, 'success');
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
