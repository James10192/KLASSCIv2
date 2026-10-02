@extends('layouts.app')

@section('title', 'Matricules - KLASSCI')

@push('styles')
<style>
.mcf { --mcf-primary:#0453cb; --mcf-primary-d:#033a8e; --mcf-dark:#0f172a; --mcf-text:#1e293b; --mcf-muted:#64748b; --mcf-border:#e2e8f0; --mcf-surface:#f8fafc; color:var(--mcf-text); max-width:1320px; margin:0 auto; padding:clamp(1rem,2.5vw,1.5rem) clamp(.75rem,2.5vw,1.5rem) 2rem; overflow-x:clip; }
.mcf *, .mcf *::before, .mcf *::after { box-sizing:border-box; }
.mcf-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:clamp(1.25rem,3vw,2rem) clamp(1rem,3vw,2.5rem) clamp(1.1rem,2.5vw,1.5rem); color:#fff; margin-bottom:1.25rem; box-shadow:0 8px 30px rgba(4,83,203,.18); }
.mcf-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; }
.mcf-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
.mcf-hero h1 { font-size:clamp(1.15rem,2.6vw,1.45rem); font-weight:700; color:#fff; margin:0; line-height:1.25; }
.mcf-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:.2rem 0 0; }
.mcf-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr)); gap:.75rem; margin-top:1.4rem; }
.mcf-kpi { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.85rem 1rem; min-width:0; }
.mcf-kpi-label { font-size:.7rem; letter-spacing:.04em; text-transform:uppercase; color:rgba(255,255,255,.7); font-weight:600; }
.mcf-kpi-value { font-size:clamp(1.05rem,2.4vw,1.35rem); font-weight:700; margin-top:.25rem; line-height:1.25; }
.mcf-kpi-sub { font-size:.74rem; color:rgba(255,255,255,.75); margin-top:.15rem; line-height:1.35; }
.mcf-nowrap { white-space:nowrap; }
.mcf-card { background:#fff; border:1px solid var(--mcf-border); border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04),0 1px 2px rgba(15,23,42,.06); padding:1.1rem; min-width:0; margin-bottom:1rem; }
.mcf-card-title { display:flex; align-items:center; gap:.6rem; margin-bottom:.9rem; }
.mcf-card-title h2 { font-size:.98rem; font-weight:700; color:var(--mcf-dark); margin:0; }
.mcf-card-title span { display:block; font-size:.76rem; color:var(--mcf-muted); }
.mcf-icon { width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
.mcf-fields { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr)); gap:.9rem 1rem; }
.mcf-field { display:flex; flex-direction:column; gap:.35rem; min-width:0; }
.mcf-field > label { font-size:.78rem; font-weight:600; color:var(--mcf-dark); }
.mcf-field .au-select { display:flex; width:100%; }
.mcf-field .au-select-trigger { width:100%; }
.mcf-note { display:flex; gap:.7rem; align-items:flex-start; margin-top:1rem; padding:.85rem 1rem; border-radius:12px; background:rgba(4,83,203,.04); border:1px solid rgba(4,83,203,.2); font-size:.84rem; line-height:1.45; }
.mcf-note i { color:var(--mcf-primary); margin-top:.15rem; flex-shrink:0; }
.mcf-note strong { color:var(--mcf-dark); }
.mcf-note--warning { background:rgba(245,158,11,.06); border-color:rgba(245,158,11,.35); }
.mcf-note--warning i { color:#b45309; }
.mcf-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:.8rem; }
.mcf-niveau { border:1px solid var(--mcf-border); border-radius:12px; padding:.9rem; background:var(--mcf-surface); min-width:0; }
.mcf-niveau-name { font-size:.88rem; font-weight:700; color:var(--mcf-dark); line-height:1.3; }
.mcf-examples { display:flex; flex-direction:column; gap:.35rem; margin-top:.6rem; }
.mcf-example { display:flex; align-items:center; gap:.45rem; font-size:.78rem; color:var(--mcf-muted); min-width:0; }
.mcf-example i { width:14px; color:var(--mcf-primary); flex-shrink:0; }
.mcf-example code { font-family:'Courier New',ui-monospace,monospace; font-size:.84rem; color:var(--mcf-primary-d); background:#fff; border:1px solid var(--mcf-border); border-radius:7px; padding:.15rem .45rem; overflow-wrap:anywhere; min-width:0; }
.mcf-niveau-meta { font-size:.74rem; color:var(--mcf-muted); margin-top:.55rem; line-height:1.35; }
.mcf-empty { text-align:center; padding:1.5rem 1rem; color:var(--mcf-muted); font-size:.86rem; }
.mcf-empty i { font-size:1.4rem; color:var(--mcf-primary); margin-bottom:.5rem; display:block; }
</style>
@endpush

@section('content')
@php
    $niveauxConfigures = $configurations->count();
    $niveauxActifs = $niveauxEtudes->count();
    $etablissementCourant = $etablissements->firstWhere('id', $currentEtablissementId);
    $optionsEtablissements = $etablissements->mapWithKeys(fn ($e) => [$e->id => $e->nom . ($e->ville ? ' (' . $e->ville . ')' : '')])->all();
@endphp
<div class="mcf">
    <div class="mcf-hero">
        <div class="mcf-hero-left">
            <div class="mcf-hero-icon"><i class="fas fa-id-card"></i></div>
            <div style="min-width:0">
                <h1>Matricules étudiants</h1>
                <p>Comment l'instance attribue un matricule à chaque nouvelle inscription</p>
            </div>
        </div>
        <div class="mcf-kpis">
            <div class="mcf-kpi">
                <div class="mcf-kpi-label">Mode</div>
                <div class="mcf-kpi-value" id="mcf-kpi-mode">{{ $matriculeMode === 'automatique' ? 'Automatique' : 'Manuel' }}</div>
                <div class="mcf-kpi-sub">{{ $matriculeMode === 'automatique' ? 'Généré à l\'inscription' : 'Saisi, contrôlé contre les doublons' }}</div>
            </div>
            <div class="mcf-kpi">
                <div class="mcf-kpi-label">Niveaux configurés</div>
                <div class="mcf-kpi-value"><span class="mcf-nowrap" id="mcf-kpi-niveaux">{{ $niveauxConfigures }}</span></div>
                <div class="mcf-kpi-sub">sur {{ $niveauxActifs }} niveau(x) d'études actif(s)</div>
            </div>
            <div class="mcf-kpi">
                <div class="mcf-kpi-label">Établissement</div>
                <div class="mcf-kpi-value" id="mcf-kpi-etablissement">{{ $etablissementCourant->nom ?? '—' }}</div>
                <div class="mcf-kpi-sub">{{ $etablissements->count() }} établissement(s) actif(s)</div>
            </div>
        </div>
    </div>

    <div class="mcf-card">
        <div class="mcf-card-title">
            <div class="mcf-icon"><i class="fas fa-sliders"></i></div>
            <div><h2>Réglages</h2><span>Appliqués aux nouvelles inscriptions</span></div>
        </div>
        <div class="mcf-fields">
            <div class="mcf-field">
                <label for="matriculeMode">Mode de génération</label>
                <x-au-select id="matriculeMode" :value="$matriculeMode" icon="fa-gears" :placeholder-is-first-option="false"
                    :options="['automatique' => 'Automatique', 'manuel' => 'Manuel (saisie vérifiée)']" />
            </div>
            <div class="mcf-field">
                <label for="currentEtablissement">Établissement</label>
                <x-au-select id="currentEtablissement" :value="(string) $currentEtablissementId" icon="fa-building-columns"
                    :searchable="count($optionsEtablissements) > 8" :placeholder-is-first-option="false" :options="$optionsEtablissements" />
            </div>
        </div>
        <div class="mcf-note">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong id="modeDescription">{{ $matriculeMode === 'automatique' ? 'Mode automatique' : 'Mode manuel' }}</strong><br>
                <span id="modeExplanation">
                    @if($matriculeMode === 'automatique')
                        Les matricules sont générés selon la nomenclature de chaque niveau d'études.
                    @else
                        Les matricules sont saisis à l'inscription, avec vérification automatique des doublons.
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="mcf-card" id="nomenclatureSection">
        <div class="mcf-card-title">
            <div class="mcf-icon"><i class="fas fa-eye"></i></div>
            <div><h2>Nomenclature actuelle</h2><span>Les réinscriptions gardent leur matricule</span></div>
        </div>
        <div id="currentNomenclature">
            @if($configurations->count() > 0)
                <div class="mcf-grid">
                    @foreach($configurations as $config)
                        <div class="mcf-niveau">
                            <div class="mcf-niveau-name">{{ $config->niveau_etude_name }}</div>
                            @if($config->exemples_generes)
                                <div class="mcf-examples">
                                    <div class="mcf-example"><i class="fas fa-mars"></i><code>{{ $config->exemples_generes['masculin'] }}</code></div>
                                    <div class="mcf-example"><i class="fas fa-venus"></i><code>{{ $config->exemples_generes['feminin'] }}</code></div>
                                </div>
                            @endif
                            <div class="mcf-niveau-meta">Année sur {{ $config->annee_format }} chiffres · numéro sur {{ $config->numero_digits }} chiffres · code {{ $config->etablissement_code }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mcf-empty">
                    <i class="fas fa-layer-group"></i>
                    Aucune nomenclature définie pour cet établissement.
                </div>
            @endif
        </div>
    </div>

    <div class="mcf-note mcf-note--warning" id="configurationAlert" style="display: {{ ($matriculeMode === 'automatique' && $configurations->count() === 0) ? 'flex' : 'none' }};">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>Nomenclature requise.</strong>
            En mode automatique, sans nomenclature, aucun matricule ne pourra être généré lors des nouvelles inscriptions.
            Passez en mode manuel ou définissez une nomenclature par niveau d'études.
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('partials._klassci_toast')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeSelect = document.getElementById('matriculeMode');
    const etablissementSelect = document.getElementById('currentEtablissement');
    if (!modeSelect || !etablissementSelect) { return; }

    const csrf = '{{ csrf_token() }}';
    const toast = (type, message) => (window.klassciToast ? window.klassciToast(type, message) : alert(message));
    const echapper = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    async function poster(url, corps) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(corps),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) { throw new Error(data.message || 'Une erreur est survenue'); }
        return data;
    }

    function decrireMode(mode) {
        const auto = mode === 'automatique';
        document.getElementById('modeDescription').textContent = auto ? 'Mode automatique' : 'Mode manuel';
        document.getElementById('modeExplanation').textContent = auto
            ? 'Les matricules sont générés selon la nomenclature de chaque niveau d\'études.'
            : 'Les matricules sont saisis à l\'inscription, avec vérification automatique des doublons.';
        document.getElementById('mcf-kpi-mode').textContent = auto ? 'Automatique' : 'Manuel';
    }

    modeSelect.addEventListener('change', async function () {
        const mode = this.value;
        try {
            await poster('{{ route("esbtp.matricule-config.change-mode") }}', { mode: mode });
            decrireMode(mode);
            rafraichirNomenclature();
            toast('success', mode === 'automatique' ? 'Mode automatique activé.' : 'Mode manuel activé.');
        } catch (e) {
            toast('error', e.message);
        }
    });

    etablissementSelect.addEventListener('change', async function () {
        try {
            const data = await poster('{{ route("esbtp.matricule-config.change-etablissement") }}', { etablissement_id: this.value });
            if (data.etablissement) { document.getElementById('mcf-kpi-etablissement').textContent = data.etablissement; }
            rafraichirNomenclature();
            toast('success', 'Nomenclature de ' + (data.etablissement || 'l\'établissement') + ' affichée.');
        } catch (e) {
            toast('error', e.message);
        }
    });

    async function rafraichirNomenclature() {
        const conteneur = document.getElementById('currentNomenclature');
        const alerte = document.getElementById('configurationAlert');
        let configurations = [];
        try {
            const data = await poster('{{ route("esbtp.matricule-config.get-configurations") }}', { etablissement_id: etablissementSelect.value });
            configurations = data.configurations || [];
        } catch (e) {
            conteneur.innerHTML = '<div class="mcf-empty"><i class="fas fa-plug-circle-xmark"></i>Nomenclature indisponible pour le moment.</div>';
            return;
        }

        document.getElementById('mcf-kpi-niveaux').textContent = configurations.length;

        if (configurations.length > 0) {
            conteneur.innerHTML = '<div class="mcf-grid">' + configurations.map((c) => {
                const ex = c.exemples_generes || {};
                return '<div class="mcf-niveau">'
                    + '<div class="mcf-niveau-name">' + echapper(c.niveau_etude_name) + '</div>'
                    + '<div class="mcf-examples">'
                    + '<div class="mcf-example"><i class="fas fa-mars"></i><code>' + echapper(ex.masculin) + '</code></div>'
                    + '<div class="mcf-example"><i class="fas fa-venus"></i><code>' + echapper(ex.feminin) + '</code></div>'
                    + '</div>'
                    + '<div class="mcf-niveau-meta">Année sur ' + echapper(c.annee_format) + ' chiffres · numéro sur ' + echapper(c.numero_digits) + ' chiffres · code ' + echapper(c.etablissement_code) + '</div>'
                    + '</div>';
            }).join('') + '</div>';
        } else {
            conteneur.innerHTML = '<div class="mcf-empty"><i class="fas fa-layer-group"></i>Aucune nomenclature définie pour cet établissement.</div>';
        }

        if (alerte) {
            alerte.style.display = (modeSelect.value === 'automatique' && configurations.length === 0) ? 'flex' : 'none';
        }
    }
});
</script>
@endpush
