@extends('layouts.app')

@section('title', 'Configuration pédagogique par groupe | KLASSCI')

@push('styles')
<style>
.pc-page{max-width:1260px;margin:0 auto;padding:1rem 1rem 3rem}
.pc-hero{background:linear-gradient(125deg,#073c88,#0453cb 65%,#5689e4);border-radius:18px;padding:1.6rem 1.9rem;color:#fff;box-shadow:0 8px 25px #0453cb27;margin-bottom:1rem}
.pc-hero h1{font-size:1.45rem;color:#fff;font-weight:800;margin:0 0 .45rem}
.pc-hero p{margin:0;color:#dceafe;font-size:.88rem}
.pc-card{background:#fff;border:1px solid #dee6f0;border-radius:14px;box-shadow:0 2px 10px #0f172a09;margin-bottom:1rem;overflow:hidden}
.pc-body{padding:1.3rem}
.pc-filters{display:grid;grid-template-columns:minmax(220px,2fr) minmax(180px,1fr) minmax(130px,1fr) auto;gap:.85rem;align-items:end}
.pc-field label{display:block;font-size:.75rem;text-transform:uppercase;letter-spacing:.025em;font-weight:800;color:#64748b;margin-bottom:.35rem}
.pc-field select,.pc-line input,.pc-line select{width:100%;border:1px solid #cbd5e1;background:#fff;border-radius:8px;padding:.58rem .65rem;font-size:.87rem;color:#1e293b}
.pc-btn{background:#0453cb;color:#fff;border:0;border-radius:9px;padding:.64rem 1rem;font-size:.84rem;font-weight:700;cursor:pointer;white-space:nowrap}
.pc-btn:disabled{opacity:.55;cursor:wait}
.pc-note{border-left:4px solid #0453cb;background:#eff6ff;color:#1d4c82;border-radius:9px;padding:.85rem 1rem;font-size:.84rem;margin-bottom:1rem}
.pc-heading{font-weight:800;color:#1e293b;font-size:1rem;margin:0 0 .85rem}
.pc-table{width:100%;border-collapse:collapse}
.pc-table th{text-align:left;padding:.7rem .75rem;font-size:.74rem;text-transform:uppercase;background:#f5f8fe;color:#64748b;white-space:nowrap}
.pc-table td{padding:.65rem .75rem;border-top:1px solid #ecf0f5;font-size:.87rem;vertical-align:middle}
.pc-table .pc-sub{font-size:.73rem;color:#64748b}
.pc-type{min-width:140px}.pc-coeff{width:115px}.pc-teacher{min-width:190px}
.pc-chip{font-size:.69rem;border-radius:99px;padding:.18rem .45rem;background:#edf2f7;color:#475569;font-weight:800}
.pc-chip--local{background:#dbeafe;color:#1d4ed8}
.pc-alert{padding:.75rem 1rem;border-radius:9px;font-size:.85rem;display:none;margin-top:.7rem}
.pc-alert.ok{display:block;background:#ecfdf5;color:#047857}.pc-alert.error{display:block;background:#fef2f2;color:#b91c1c}
.pc-ue{padding:1.15rem 1.3rem;border-top:1px solid #e5eaf1}
.pc-ue:first-of-type{border-top:0}.pc-ue-title{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;color:#1e293b;font-weight:800}
.pc-ue a{font-weight:700;color:#0453cb;text-decoration:none}
.pc-ecues{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.5rem;margin-top:.8rem}
.pc-ecue{border:1px solid #e3eaf3;background:#fafcff;border-radius:9px;padding:.68rem .8rem;font-size:.82rem;color:#334155}
@@media(max-width:840px){.pc-filters{grid-template-columns:1fr 1fr}.pc-table{min-width:680px}.pc-body{padding:1rem}}
@@media(max-width:540px){.pc-filters{grid-template-columns:1fr}.pc-hero{padding:1.3rem}}
</style>
@endpush

@section('content')
<div class="pc-page">
    <header class="pc-hero">
        <h1><i class="fas fa-layer-group" aria-hidden="true"></i> Configuration pédagogique par groupe</h1>
        <p>Choisissez une classe, une année universitaire et un semestre. La configuration BTS est propre à la classe ; les UE/ECUE LMD restent liées à leur maquette de parcours.</p>
    </header>

    <section class="pc-card">
        <div class="pc-body">
            <form method="GET" action="{{ route('esbtp.bulletins.configuration-groupe') }}" class="pc-filters">
                <div class="pc-field"><label for="pc-classe">Classe</label>
                    <x-au-select name="classe_id" id="pc-classe" label="Classe" :options="$classes->mapWithKeys(fn ($c) => [$c->id => $c->name.' · '.($c->systeme_academique ?? 'BTS')])->all()" :value="$classe?->id" :placeholder-is-first-option="false" :searchable="true" />
                </div>
                <div class="pc-field"><label for="pc-annee">Année universitaire</label>
                    <x-au-select name="annee_universitaire_id" id="pc-annee" label="Année universitaire" :options="$annees->pluck('name', 'id')->all()" :value="$annee?->id" :placeholder-is-first-option="false" />
                </div>
                <div class="pc-field"><label for="pc-periode">Semestre</label>
                    <x-au-select name="periode" id="pc-periode" label="Semestre" :options="$periodes" :value="$periode" :placeholder-is-first-option="false" />
                </div>
                <button class="pc-btn" type="submit"><i class="fas fa-filter" aria-hidden="true"></i> Afficher</button>
            </form>
        </div>
    </section>

    @if(!$classe || !$annee)
        <div class="pc-note">Aucune classe ou année n'est encore disponible.</div>
    @elseif(!$isLmd)
        <div class="pc-note"><strong>BTS · {{ $classe->name }} · {{ $annee->name }} · {{ $periodes[$periode] }}.</strong> Les coefficients saisis ci-dessous prévalent seulement pour cette classe. Les valeurs héritées restent disponibles tant qu'aucune surcharge locale n'est posée.</div>
        <section class="pc-card">
            <div class="pc-body">
                <h2 class="pc-heading">Matières, type, coefficient et enseignant</h2>
                <form id="pc-bts-form" action="{{ route('esbtp.bulletins.config-matieres.inline-save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $annee->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <div style="overflow-x:auto;">
                        <table class="pc-table">
                            <thead><tr><th>Matière</th><th>Type de formation</th><th>Coefficient</th><th>Enseignant</th></tr></thead>
                            <tbody>
                            @forelse($configurationBts['matieres'] as $matiere)
                                <tr class="pc-line">
                                    <td><strong>{{ $matiere['name'] }}</strong><div class="pc-sub">{{ $matiere['code'] ?? '' }}</div></td>
                                    <td><select class="pc-type" name="matiere_type[{{ $matiere['id'] }}]" aria-label="Type {{ $matiere['name'] }}">
                                        <option value="general" @selected(($matiere['existing_type'] ?? $matiere['suggested_type']) === 'general')>Générale</option>
                                        <option value="technique" @selected(($matiere['existing_type'] ?? $matiere['suggested_type']) === 'technique')>Technique</option>
                                        <option value="none" @selected(!$matiere['existing_type'] && !$matiere['suggested_type'])>Retirer le classement</option>
                                    </select></td>
                                    <td><input class="pc-coeff" type="number" step="0.01" min="0.1" name="coefficients[{{ $matiere['id'] }}]" value="{{ ($matiere['coefficient_source'] ?? '') === 'classe' ? $matiere['coefficient'] : '' }}" placeholder="{{ $matiere['coefficient'] === null ? 'À définir' : 'Hérité : '.$matiere['coefficient'] }}" aria-label="Coefficient propre à la classe pour {{ $matiere['name'] }}">
                                        <span class="pc-chip {{ ($matiere['coefficient_source'] ?? '') === 'classe' ? 'pc-chip--local' : '' }}">{{ ($matiere['coefficient_source'] ?? '') === 'classe' ? 'Classe' : 'Hérité' }}</span></td>
                                    <td><input class="pc-teacher" name="professeurs[{{ $matiere['id'] }}]" maxlength="255" value="{{ $matiere['professeur'] ?? '' }}" placeholder="Nom de l'enseignant" aria-label="Enseignant {{ $matiere['name'] }}"></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">Aucune matière BTS prévue à ce semestre ni évaluée historiquement.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-top:1rem;"><button class="pc-btn" type="submit" @disabled($configurationBts['matieres']->isEmpty())>Enregistrer la configuration</button></div>
                    <div id="pc-feedback" class="pc-alert" role="status" aria-live="polite"></div>
                </form>
            </div>
        </section>
    @else
        <div class="pc-note"><strong>LMD · {{ $classe->name }} · {{ $annee->name }} · {{ $periodes[$periode] }}.</strong> Les UE/ECUE, crédits et coefficients appartiennent à la maquette du parcours ; leur édition officielle est partagée par les classes de ce parcours, et les enseignants sont affectés dans la planification.</div>
        <section class="pc-card">
            <div class="pc-body" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <h2 class="pc-heading" style="margin:0;">Unités et éléments constitutifs</h2>
                @can('lmd.planning.view')<a href="{{ route('esbtp.lmd.planning.index', ['classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id, 'semestre' => substr($periode, 8)]) }}" class="pc-btn" style="text-decoration:none;">Enseignants et planification</a>@endcan
            </div>
            @forelse($unitesLmd as $ue)
                <div class="pc-ue">
                    <div class="pc-ue-title"><span>{{ $ue->code }} — {{ $ue->name }} <span class="pc-chip">{{ $ue->creditEffectif() }} crédits</span></span>
                        @can('lmd.structure.manage')<a href="{{ route('esbtp.lmd.ue.edit', $ue) }}">Configurer UE/ECUE <i class="fas fa-arrow-right" aria-hidden="true"></i></a>@endcan
                    </div>
                    <div class="pc-ecues">
                        @forelse($ue->getEcuesEffectifs($classe->parcours_id) as $ecue)
                            <div class="pc-ecue"><strong>{{ $ecue->code }} — {{ $ecue->name }}</strong>
                                <div class="pc-sub">Coeff. {{ $ecue->pivot?->coefficient_ecue ?? $ecue->coefficient_ecue ?? $ecue->coefficient ?? 1 }} · Crédit {{ $ecue->pivot?->credit_ecue ?? $ecue->credit_ecue ?? 0 }}</div>
                            </div>
                        @empty
                            <div class="pc-ecue">Aucun ECUE actif dans cette unité.</div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="pc-body">Aucune UE prévue pour cette classe et ce semestre.</div>
            @endforelse
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    let pendingContext = null;

    async function showContext(url, pushHistory = true) {
        if (pendingContext) pendingContext.abort();
        const controller = new AbortController();
        pendingContext = controller;
        const current = document.querySelector('.pc-page');
        current?.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal
            });
            if (!response.ok) throw new Error('Contexte indisponible');
            const html = await response.text();
            const next = new DOMParser().parseFromString(html, 'text/html').querySelector('.pc-page');
            if (!next) throw new Error('Réponse inattendue');
            const previous = document.querySelector('.pc-page');
            if (window.Alpine?.destroyTree) window.Alpine.destroyTree(previous);
            previous.replaceWith(next);
            if (window.Alpine?.initTree) window.Alpine.initTree(next);
            if (pushHistory) history.pushState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        } finally {
            if (pendingContext === controller) {
                pendingContext = null;
                document.querySelector('.pc-page')?.removeAttribute('aria-busy');
            }
        }
    }

    function contextUrl(form, changedClass = false) {
        const data = new FormData(form);
        if (changedClass) data.delete('periode');
        return form.action + '?' + new URLSearchParams(data).toString();
    }

    document.addEventListener('change', function (event) {
        const select = event.target;
        const filters = select.closest?.('form.pc-filters');
        if (!filters || !['classe_id', 'annee_universitaire_id', 'periode'].includes(select.name)) return;
        showContext(contextUrl(filters, select.name === 'classe_id'));
    });

    document.addEventListener('submit', async function (event) {
        const filters = event.target.closest?.('form.pc-filters');
        if (filters) {
            event.preventDefault();
            showContext(contextUrl(filters));
            return;
        }
        const form = event.target.closest?.('#pc-bts-form');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const feedback = form.querySelector('#pc-feedback');
        button.disabled = true;
        feedback.className = 'pc-alert';
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Cette configuration a été refusée.');
            }
            feedback.textContent = 'Configuration enregistrée pour la classe, l’année et le semestre sélectionnés.';
            feedback.className = 'pc-alert ok';
        } catch (error) {
            feedback.textContent = error.message || 'Enregistrement impossible. Réessayez.';
            feedback.className = 'pc-alert error';
        } finally {
            button.disabled = false;
        }
    });
    window.addEventListener('popstate', () => showContext(window.location.href, false));
})();
</script>
@endpush
