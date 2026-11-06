@extends('layouts.app')

@section('title', 'Situation financière des classes')

@push('styles')
<style>
.sf-page { max-width:1500px; margin:0 auto; padding:1.4rem; display:flex; flex-direction:column; gap:1.1rem; }
.sf-hero { background:linear-gradient(125deg,#0a3d8f,#0453cb 55%,#3b7ddb); border-radius:20px; padding:1.5rem 1.7rem; color:white; display:flex; gap:1rem; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; }
.sf-hero h1 { font-size:clamp(1.25rem,2.5vw,1.8rem); color:white; margin:.2rem 0 .5rem; font-weight:800; }
.sf-hero p { color:#dfebff; font-size:.88rem; margin:0; max-width:650px; }
.sf-crumb { color:#bed6ff; font-size:.78rem; }
.sf-crumb a { color:#fff; }
.sf-hero-actions {display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;}
.sf-hero .export-menu {z-index:60;}
.sf-hero .sf-btn { color:#0453cb; background:white; border-color:white; }
.sf-btn {display:inline-flex;gap:.55rem;align-items:center;justify-content:center;padding:.64rem .95rem;border:1px solid #c7d8ef;border-radius:11px;background:white;color:#0453cb;font-weight:700;font-size:.84rem;cursor:pointer;text-decoration:none;white-space:nowrap;}
.sf-btn:hover {box-shadow:0 5px 15px #003b8520;}
.sf-btn:focus-visible,.sf-details-btn:focus-visible{outline:3px solid #9fbef3;outline-offset:2px;}
.sf-btn:disabled {opacity:.55;cursor:not-allowed;}
.sf-cards {display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.9rem;}
.sf-card {background:white;border:1px solid #e2e8f0;border-radius:16px;padding:1.05rem 1.1rem;min-width:0;}
.sf-card-label {font-size:.75rem;color:#64748b;font-weight:700;letter-spacing:.03em;}
.sf-card-value {font-size:clamp(1.15rem,1.9vw,1.65rem);font-weight:800;color:#0f172a;margin:.35rem 0 .25rem;overflow-wrap:anywhere;font-variant-numeric:tabular-nums;}
.sf-card-note {font-size:.74rem;color:#64748b;}
.sf-card--attention .sf-card-value {color:#b45309;}
.sf-filters {background:#fff;padding:1.1rem;border:1px solid #e2e8f0;border-radius:16px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.9rem;align-items:end;}
.sf-filter{min-width:0;display:flex;flex-direction:column;gap:.35rem;}
.sf-filter label{font-size:.75rem;color:#475569;font-weight:700;}
.sf-filter .au-select {width:100%;max-width:100%;display:flex;}
.sf-filter input {width:100%;min-width:0;padding:.68rem .7rem;border:1px solid #dbe5f3;border-radius:10px;font:inherit;font-size:.84rem;color:#1e293b;}
.sf-filter input:focus {outline:2px solid #bfdbfe;border-color:#0453cb;}
.sf-filter-buttons{display:flex;gap:.5rem;flex-wrap:wrap;align-self:end;}
.sf-panel {background:white;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;}
.sf-panel-heading {padding:1.15rem 1.3rem;display:flex;gap:1rem;align-items:center;justify-content:space-between;flex-wrap:wrap;border-bottom:1px solid #e2e8f0;}
.sf-panel-heading h2 {margin:0;font-weight:800;font-size:1.07rem;color:#172338;}
.sf-panel-heading p{font-size:.78rem;color:#64748b;margin:.3rem 0 0;}
.sf-table-scroll {overflow-x:auto;max-width:100%;}
.sf-table {border-collapse:collapse;width:100%;font-size:.83rem;}
.sf-table thead th {background:#f2f6fc;color:#526682;letter-spacing:.02em;white-space:nowrap;text-align:left;font-size:.72rem;font-weight:800;padding:.9rem .75rem;border-bottom:1px solid #dce7f5;}
.sf-table td {padding:.9rem .75rem;border-bottom:1px solid #eef2f7;color:#334155;vertical-align:middle;}
.sf-table tbody tr:hover {background:#f8fbff;}
.sf-table td strong {display:block;color:#0f172a;font-weight:750;}
.sf-table td small {display:block;color:#64748b;font-size:.73rem;margin-top:.2rem;}
.sf-table td.sf-money,.sf-table th.sf-money {text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;}
.sf-money.sf-strong {color:#9a3412;font-weight:800;}
.sf-table .sf-empty {text-align:center;padding:2rem;color:#64748b;white-space:normal;}
.sf-figure {font-variant-numeric:tabular-nums;font-weight:700;}
.sf-progress-cell {min-width:112px;}
.sf-progress-label {font-size:.77rem;font-weight:750;text-align:right;color:#0453cb;}
.sf-progress {height:6px;border-radius:99px;background:#e9f0f8;margin-top:.35rem;overflow:hidden;}
.sf-progress i {display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#0453cb,#3b7ddb);}
.sf-details-btn {border:1px solid #cfe0f7;background:#f2f7ff;color:#0453cb;border-radius:9px;cursor:pointer;padding:.46rem .65rem;font-size:.76rem;font-weight:750;white-space:nowrap;}
.sf-table-footer {padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;font-size:.8rem;color:#64748b;}
.sf-pagination nav{display:flex;max-width:100%;overflow-x:auto;}
.sf-hint {border:1px solid #d7e5f8;border-radius:12px;padding:.85rem 1rem;background:#f2f7fe;color:#31577c;font-size:.79rem;line-height:1.6;}
.sf-loading {opacity:.5;pointer-events:none;}
.sf-modal-backdrop {position:fixed;inset:0;background:#0a193caa;z-index:1070;display:flex;justify-content:center;align-items:center;padding:1rem;}
.sf-modal {background:white;border-radius:18px;width:min(1120px,100%);max-height:88vh;display:flex;flex-direction:column;box-shadow:0 22px 80px #07142d40;overflow:hidden;}
.sf-modal-header {display:flex;gap:1rem;justify-content:space-between;align-items:center;padding:1.1rem 1.3rem;border-bottom:1px solid #e2e8f0;}
.sf-modal-header h2 {margin:0;font-size:1.1rem;color:#0f172a;}
.sf-modal-body{padding:1rem;overflow:auto;}
.sf-details-summary{display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap;font-size:.85rem;color:#64748b;padding:.5rem 0 1rem;}
.sf-details-summary strong{color:#0453cb;}
.sf-sr-only {position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}
@media(max-width:1150px){.sf-cards{grid-template-columns:repeat(2,minmax(0,1fr));}.sf-filters{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:620px){.sf-page{padding:.7rem;gap:.7rem;}.sf-hero{padding:1.1rem;border-radius:15px;}.sf-cards{gap:.5rem;}.sf-card{padding:.8rem;}.sf-card-value{font-size:1.14rem;}.sf-filters{padding:.8rem;gap:.65rem;grid-template-columns:1fr;}.sf-filter-buttons .sf-btn{flex:1;}.sf-panel-heading{padding:.9rem;}.sf-table{font-size:.78rem;}.sf-modal{max-height:94vh;}.sf-card-label{font-size:.69rem;}}
</style>
@endpush

@section('content')
@php
    $sfOptions = fn ($items, $labelKey = 'name') => ['' => 'Toutes / tous'] + $items->mapWithKeys(fn ($item) => [(string)$item->id => (string)$item->{$labelKey}])->all();
    $sfConfig = [
        'filters' => [
            'annee' => (string)($filtres['annee'] ?? ''),
            'filiere' => (string)($filtres['filiere'] ?? ''),
            'niveau' => (string)($filtres['niveau'] ?? ''),
            'classe' => (string)($filtres['classe'] ?? ''),
            'categorie' => (string)($filtres['categorie'] ?? ''),
            'systeme' => (string)($filtres['systeme'] ?? ''),
            'search' => (string)($filtres['search'] ?? ''),
        ],
        'totaux' => $rapport['totaux'],
        'dataUrl' => route('esbtp.comptabilite.suivi-classes.data'),
        'detailsPrefix' => route('esbtp.comptabilite.suivi-classes.index'),
    ];
@endphp
<div class="sf-page" x-data="suiviClasses({{ \Illuminate\Support\Js::from($sfConfig) }})">
    <header class="sf-hero">
        <div>
            <div class="sf-crumb"><a href="{{ route('esbtp.comptabilite.dashboard') }}">Analyse financière</a> · État des classes</div>
            <h1><i class="fas fa-chart-column"></i> Situation financière des classes</h1>
            <p>Vue consolidée des engagements et des règlements par classe. Montants issus des échéanciers et paiements validés, avec historique annuel distinct.</p>
        </div>
        <div class="sf-hero-actions">
            @can('comptabilite.suivi_classes.export')
            <x-export-modal
                :preview-url="route('esbtp.comptabilite.suivi-classes.pdf-preview')"
                :pdf-url="route('esbtp.comptabilite.suivi-classes.pdf')"
                :excel-url="route('esbtp.comptabilite.suivi-classes.excel')"
                button-class="sf-btn" />
            @endcan
        </div>
    </header>

    <section class="sf-cards" aria-label="Indicateurs financiers">
        <div class="sf-card"><div class="sf-card-label">Montant attendu</div><div class="sf-card-value" x-text="money(totaux.attendu)">{{ number_format($rapport['totaux']['attendu'],0,',',' ') }}</div><div class="sf-card-note">Charges annuelles de scolarité</div></div>
        <div class="sf-card"><div class="sf-card-label">Règlements imputés</div><div class="sf-card-value" x-text="money(totaux.regle)">{{ number_format($rapport['totaux']['regle'],0,',',' ') }}</div><div class="sf-card-note" x-text="'Taux : '+percent(totaux.taux)"></div></div>
        <div class="sf-card sf-card--attention"><div class="sf-card-label">Reste à payer</div><div class="sf-card-value" x-text="money(totaux.restant)">{{ number_format($rapport['totaux']['restant'],0,',',' ') }}</div><div class="sf-card-note">Dette non soldée</div></div>
        <div class="sf-card"><div class="sf-card-label">Échu impayé</div><div class="sf-card-value" x-text="money(totaux.echu)">{{ number_format($rapport['totaux']['echu'],0,',',' ') }}</div><div class="sf-card-note">Tranches en retard au {{ now()->format('d/m/Y') }}</div></div>
    </section>

    <form class="sf-filters" @submit.prevent="refresh(1)" aria-label="Filtrer les classes">
        <div class="sf-filter"><label>Année universitaire</label><x-au-select name="annee" x-model="filters.annee" icon="fa-calendar" :options="$sfOptions($annees)" :value="$filtres['annee'] ?? ''" :searchable="true" /></div>
        <div class="sf-filter"><label>Filière / mention</label><x-au-select name="filiere" x-model="filters.filiere" icon="fa-layer-group" :options="$sfOptions($filieres)" :value="$filtres['filiere'] ?? ''" :searchable="true" /></div>
        <div class="sf-filter"><label>Niveau</label><x-au-select name="niveau" x-model="filters.niveau" icon="fa-graduation-cap" :options="$sfOptions($niveaux)" :value="$filtres['niveau'] ?? ''" :searchable="true" /></div>
        <div class="sf-filter"><label>Classe</label><x-au-select name="classe" x-model="filters.classe" icon="fa-users" :options="$sfOptions($classesOptions)" :value="$filtres['classe'] ?? ''" :searchable="true" /></div>
        <div class="sf-filter"><label>Système académique</label><x-au-select name="systeme" x-model="filters.systeme" icon="fa-university" :options="['' => 'Tous les systèmes'] + $systemes->mapWithKeys(fn($s) => [$s => $s])->all()" :value="$filtres['systeme'] ?? ''" /></div>
        <div class="sf-filter"><label>Catégorie de frais</label><x-au-select name="categorie" x-model="filters.categorie" icon="fa-list" :options="$sfOptions($categories,'nom')" :value="$filtres['categorie'] ?? ''" :searchable="true" /></div>
        <div class="sf-filter"><label for="sf-search">Rechercher une classe</label><input id="sf-search" type="search" x-model="filters.search" placeholder="Nom de classe…" maxlength="100" /></div>
        <div class="sf-filter-buttons">
            <button type="submit" class="sf-btn" :disabled="loading"><i class="fas fa-filter"></i> Appliquer</button>
            <button type="button" class="sf-btn" @click="reset()" :disabled="loading">Effacer</button>
        </div>
    </form>

    <p class="sf-hint">
        <i class="fas fa-circle-info"></i>
        « Réglé » correspond aux versements imputés aux frais et tient compte des avoirs. Le « reste » provient des échéanciers. 
        <span x-show="categorieFiltre">Une catégorie de frais est filtrée : les chiffres de caisse ne sont pas ventilés artificiellement par catégorie.</span>
        <span x-show="!categorieFiltre">Le détail des flux de caisse et l'écart avec les imputations figurent dans l'export Excel.</span>
    </p>

    <section class="sf-panel" :class="loading && 'sf-loading'">
        <div class="sf-panel-heading">
            <div><h2>Répartition par classe</h2><p>La somme porte sur toutes les classes filtrées, et non seulement sur la page affichée.</p></div>
            <span x-text="totaux.classes+' classes · '+totaux.effectif+' inscriptions distinctes'"></span>
        </div>
        <div x-ref="results" @click="handlePagination($event)">
            @include('esbtp.comptabilite.suivi-classes.partials.resultats', ['rapport' => $rapport])
        </div>
    </section>

    @can('comptabilite.suivi_classes.view_details')
    <div x-show="detailOpen" x-cloak class="sf-modal-backdrop" @keydown.escape.window="detailOpen=false" @click.self="detailOpen=false">
        <div role="dialog" aria-modal="true" aria-labelledby="sf-detail-title" class="sf-modal">
            <div class="sf-modal-header"><h2 id="sf-detail-title">Situation des étudiants de la classe</h2><button type="button" class="sf-btn" @click="detailOpen=false" aria-label="Fermer">Fermer <i class="fas fa-times"></i></button></div>
            <div class="sf-modal-body">
                <p x-show="detailLoading" role="status">Chargement des inscriptions…</p>
                <div x-ref="detailBody"></div>
            </div>
        </div>
    </div>
    @endcan
    <div class="sf-sr-only" aria-live="polite" x-text="message"></div>
</div>
@endsection

@push('scripts')
<script>
window.suiviClasses = function(config) {
    return {
        filters: {...config.filters},
        initialFilters: {...config.filters},
        totaux: config.totaux,
        categorieFiltre: Boolean(config.filters.categorie),
        loading: false, detailOpen: false, detailLoading: false, message: '',
        money(n) { return new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0}).format(Number(n||0)); },
        percent(n) { return n === null || n === undefined ? '—' : Number(n).toFixed(1).replace('.', ',')+' %'; },
        init() {
            window.exportFilters = () => ({...this.filters});
        },
        params(page=1) {
            const qs = new URLSearchParams();
            for (const [key,value] of Object.entries(this.filters)) if (value !== '' && value !== null) qs.set(key,value);
            if (page > 1) qs.set('page',page);
            return qs;
        },
        async refresh(page=1) {
            if (this.loading) return;
            this.loading=true; this.message='';
            try {
                const url=config.dataUrl+'?'+this.params(page).toString();
                const response=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                if(!response.ok) throw new Error(response.status === 403 ? 'Accès non autorisé.' : 'Impossible de charger le rapport.');
                const data=await response.json();
                this.$refs.results.innerHTML=data.html;
                if(window.Alpine && typeof Alpine.initTree === 'function') Alpine.initTree(this.$refs.results);
                this.totaux=data.totaux; this.categorieFiltre=data.categorie;
                history.replaceState(null,'',location.pathname+'?'+this.params(page).toString());
            } catch(e) { this.message=e.message; alert(e.message); }
            finally { this.loading=false; }
        },
        reset() { this.filters={...this.initialFilters,fil iere:''}; },
        handlePagination(e) {
            const a=e.target.closest('a[href]');
            if(!a || !a.closest('.sf-pagination')) return;
            e.preventDefault();
            const href=new URL(a.href, location.href);
            this.refresh(Math.max(1,Number(href.searchParams.get('page')||1)));
        },
        async openDetails(id) {
            this.detailOpen=true; this.detailLoading=true; this.$refs.detailBody.innerHTML='';
            try {
                const response=await fetch(config.detailsPrefix+'/'+id+'/details?'+this.params().toString(),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                if(!response.ok) throw new Error(response.status===403?'Accès aux dossiers non autorisé.':'Détail indisponible.');
                this.$refs.detailBody.innerHTML=(await response.json()).html;
            } catch(e) { this.$refs.detailBody.textContent=e.message; }
            finally { this.detailLoading=false; }
        }
    };
};
</script>
@endpush
