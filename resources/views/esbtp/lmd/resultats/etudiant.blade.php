@extends('layouts.app')

@section('title', 'Résultats LMD - ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
<style>
    .lmd-page { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }
    .lmd-hero { background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); border-radius: 16px; padding: 1.5rem 2rem; color: #fff; margin-bottom: 1rem; }
    .lmd-hero-title { font-size: 1.35rem; font-weight: 800; }
    .lmd-hero-subtitle { opacity: .86; margin-top: .25rem; font-size: .9rem; }
    .lmd-hero-kpis, .lmd-filters { display: flex; gap: .75rem; flex-wrap: wrap; }
    .lmd-hero-kpis { margin-top: 1rem; }
    .lmd-hero-kpi { min-width: 130px; flex: 1; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); border-radius: 10px; padding: .7rem 1rem; }
    .lmd-hero-kpi-value { font-size: 1.35rem; font-weight: 800; }
    .lmd-hero-kpi-label { font-size: .74rem; opacity: .78; }
    .lmd-card { background: #fff; border: 1px solid #e8ecf1; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.04); margin-bottom: 1rem; overflow: hidden; }
    .lmd-card-body { padding: 1rem 1.25rem; }
    .lmd-field { min-width: 220px; flex: 1; }
    .lmd-label { display: block; font-size: .72rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: .3rem; }
    .lmd-field .au-select { display: flex; width: 100%; }
    .lmd-field .au-select-trigger { width: 100%; }
    .lmd-periods { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .lmd-period-btn { border: 1px solid #dce3ef; border-radius: 9px; background: #fff; color: #475569; padding: .55rem .85rem; font-size: .83rem; font-weight: 700; cursor: pointer; }
    .lmd-period-btn:hover, .lmd-period-btn:focus-visible { border-color: #0453cb; outline-color: #0453cb; }
    .lmd-period-btn.active { background: #0453cb; color: #fff; border-color: #0453cb; }
    .lmd-loading { position: sticky; top: .5rem; z-index: 10; text-align: center; padding: .6rem; color: #0453cb; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; margin-bottom: .75rem; font-weight: 700; }
    .lmd-error { padding: .7rem 1rem; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; margin-bottom: .75rem; font-size: .85rem; }
    .lmd-sem-result[hidden], .lmd-loading[hidden], .lmd-error[hidden] { display: none; }
    .lmd-semester-header { background: #f8fafc; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap; align-items: center; }
    .lmd-semester-title { font-weight: 800; color: #1e293b; }
    .lmd-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .22rem .6rem; font-size: .72rem; font-weight: 800; }
    .lmd-badge--success { background: #ecfdf5; color: #047857; }
    .lmd-badge--warning { background: #fffbeb; color: #b45309; }
    .lmd-badge--danger { background: #fef2f2; color: #b91c1c; }
    .lmd-badge--muted { background: #f1f5f9; color: #64748b; }
    .lmd-ue-table { width: 100%; border-collapse: collapse; }
    .lmd-ue-table th { background: #f1f5f9; padding: .55rem .75rem; font-size: .72rem; color: #475569; text-transform: uppercase; }
    .lmd-ue-table td { padding: .55rem .75rem; border-top: 1px solid #f1f5f9; font-size: .85rem; color: #334155; }
    .lmd-ue-row td { background: #fbfdff; font-weight: 800; }
    .lmd-ecue-row td:first-child { padding-left: 1.5rem; }
    .lmd-actions a { margin-left: .35rem; }
    .lmd-muted { color: #94a3b8; }
    @media (max-width: 768px) {
        .lmd-semester-header, .lmd-hero-kpis, .lmd-filters { flex-direction: column; align-items: stretch; }
    }
</style>
@endpush

@section('content')
<div class="lmd-page" id="lmd-results-page" data-annee-id="{{ $anneeId }}" data-inscription-id="{{ $inscriptionSelectionnee ?? '' }}" data-classe-id="{{ request('classe_id', '') }}">
    <div class="lmd-loading" role="status" aria-live="polite" hidden><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Actualisation des résultats…</div>
    <div class="lmd-error" role="alert" hidden>Impossible d'actualiser les résultats. Réessayez.</div>
    <div class="lmd-hero">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
            <div>
                <div class="lmd-hero-title"><i class="fas fa-user-graduate me-2"></i>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                <div class="lmd-hero-subtitle">Matricule : {{ $etudiant->matricule }}</div>
            </div>
            @can('students.view')
                <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $etudiant->id, 'tab' => 'academique', 'inscription_id' => $inscriptionSelectionnee]) }}"
                   class="btn-acasi secondary" style="background:#fff;color:#0453cb;">
                    <i class="fas fa-user-graduate" aria-hidden="true"></i> Fiche étudiant
                </a>
            @endcan
        </div>
        <div class="lmd-hero-kpis">
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $creditsCumules }}</div><div class="lmd-hero-kpi-label">Crédits validés (tous semestres)</div></div>
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $creditsTotauxCumules }}</div><div class="lmd-hero-kpi-label">Crédits totaux (tous semestres)</div></div>
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $creditsTotauxCumules > 0 ? round(($creditsCumules / $creditsTotauxCumules) * 100) : 0 }}%</div><div class="lmd-hero-kpi-label">Progression globale</div></div>
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $resultats->count() }}</div><div class="lmd-hero-kpi-label">Semestres de l'année</div></div>
        </div>
    </div>

    @php
        $anneesOptions = $annees->mapWithKeys(fn ($annee) => [(string) $annee->id => $annee->name ?? $annee->display_name ?? (string) $annee->id])->all();
    @endphp
    <form method="GET" id="lmd-results-filters" action="{{ route('esbtp.lmd.resultats.etudiant', $etudiant) }}" class="lmd-card">
        @if($inscriptionSelectionnee)
            <input type="hidden" name="inscription_id" value="{{ $inscriptionSelectionnee }}">
        @endif
        @if(request()->filled('classe_id'))
            <input type="hidden" name="classe_id" value="{{ (int) request('classe_id') }}">
        @endif
        <div class="lmd-card-body lmd-filters">
            <div class="lmd-field">
                <label class="lmd-label">Année universitaire</label>
                <x-au-select name="annee_universitaire_id" :value="$anneeId" :options="$anneesOptions"
                             :placeholder-is-first-option="false" icon="fa-calendar-alt" placeholder="Choisir une année" />
            </div>
        </div>
    </form>

    @if($semestresAutorises->isNotEmpty())
        <nav class="lmd-periods" aria-label="Filtrer les résultats par semestre">
            <button type="button" class="lmd-period-btn {{ $semestreSelectionne === null ? 'active' : '' }}"
                    data-lmd-sem="" aria-pressed="{{ $semestreSelectionne === null ? 'true' : 'false' }}">Tous les semestres</button>
            @foreach($semestresAutorises as $semestreAutorise)
                <button type="button" class="lmd-period-btn {{ $semestreSelectionne === $semestreAutorise ? 'active' : '' }}"
                        data-lmd-sem="{{ $semestreAutorise }}" aria-pressed="{{ $semestreSelectionne === $semestreAutorise ? 'true' : 'false' }}">S{{ $semestreAutorise }}</button>
            @endforeach
        </nav>
    @endif

    @forelse($resultats as $resultat)
        @php
            $bulletin = $resultat['bulletin'];
            $moyenne = $resultat['moyenne_generale'];
            $status = $resultat['status'];
            $statusLabel = match ($status) {
                'complete' => 'Complet',
                'configuration_missing' => 'Configuration manquante',
                default => 'Incomplet',
            };
            $statusClass = match ($status) {
                'complete' => 'lmd-badge--success',
                'configuration_missing' => 'lmd-badge--danger',
                default => 'lmd-badge--warning',
            };
        @endphp
        <div class="lmd-card lmd-sem-result" data-semestre="{{ $resultat['semestre'] }}" @if($semestreSelectionne !== null && (int) $resultat['semestre'] !== $semestreSelectionne) hidden @endif>
            <div class="lmd-semester-header">
                <div>
                    <span class="lmd-semester-title">{{ $resultat['classe']->name ?? '' }} - Semestre {{ $resultat['semestre'] }}</span>
                    <span class="lmd-muted" style="margin-left:.5rem;">{{ $resultat['classe']->niveau->name ?? '' }}</span>
                </div>
                <div class="lmd-actions" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                    <strong style="color:{{ $moyenne === null ? '#94a3b8' : ($moyenne >= 10 ? '#047857' : '#b91c1c') }};">{{ $moyenne !== null ? number_format($moyenne, 2) : '-' }}/20</strong>
                    <span class="lmd-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    <span class="lmd-badge lmd-badge--muted">{{ $resultat['credits_capitalises'] }}/{{ $resultat['credits_totaux'] }} crédits</span>
                    @if($bulletin)
                        <a href="{{ route('esbtp.lmd.bulletins.pdf-preview', $bulletin) }}" class="btn-acasi secondary" target="_blank">Aperçu</a>
                        <a href="{{ route('esbtp.lmd.bulletins.pdf', $bulletin) }}" class="btn-acasi primary">PDF</a>
                    @else
                        <span class="lmd-badge lmd-badge--muted">Bulletin a generer</span>
                    @endif
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="lmd-ue-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Intitule</th>
                            <th style="text-align:center;">Moy /20</th>
                            <th style="text-align:center;">Statut</th>
                            <th style="text-align:center;">Crédits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultat['resultats_ues'] as $resUE)
                            <tr class="lmd-ue-row">
                                <td>{{ $resUE['unite_enseignement']->code ?? '' }}</td>
                                <td>{{ $resUE['unite_enseignement']->name ?? '' }}</td>
                                <td style="text-align:center;">{{ $resUE['moyenne'] !== null ? number_format($resUE['moyenne'], 2) : '-' }}</td>
                                <td style="text-align:center;">{{ $resUE['statut'] }}</td>
                                <td style="text-align:center;">{{ $resUE['credit'] }}</td>
                            </tr>
                            @foreach($resUE['resultats_ecues'] as $resECUE)
                                <tr class="lmd-ecue-row">
                                    <td>{{ $resECUE['matiere']->code ?? '' }}</td>
                                    <td>{{ $resECUE['matiere']->name ?? '' }}</td>
                                    <td style="text-align:center;">{{ $resECUE['moyenne'] !== null ? number_format($resECUE['moyenne'], 2) : '-' }}</td>
                                    <td></td>
                                    <td style="text-align:center;">{{ $resECUE['credit'] ?: '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="lmd-card">
            <div class="lmd-card-body" style="text-align:center;padding:3rem 1rem;">
                <i class="fas fa-inbox fa-3x lmd-muted"></i>
                <p class="lmd-muted" style="margin-top:1rem;">Aucun résultat LMD live pour cet étudiant sur cette année universitaire.</p>
            </div>
        </div>
    @endforelse

    <a href="{{ route('esbtp.lmd.resultats.index', ['annee_universitaire_id' => $anneeId]) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour aux résultats LMD</a>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Le script reste en place lors du remplacement AJAX : écouteurs délégués
    // et état reconstitué depuis l'URL pour le retour précédent/suivant.
    var requestNumber = 0;
    var pendingRequest = null;

    function page() { return document.getElementById('lmd-results-page'); }

    function applySemesterFromUrl() {
        var root = page();
        if (!root) return;
        var selected = new URL(window.location.href).searchParams.get('semestre') || '';
        root.querySelectorAll('[data-lmd-sem]').forEach(function (button) {
            var active = button.dataset.lmdSem === selected;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        root.querySelectorAll('.lmd-sem-result').forEach(function (card) {
            card.hidden = selected !== '' && card.dataset.semestre !== selected;
        });
    }

    function loadContext(url, pushHistory) {
        var root = page();
        if (!root) return;
        var sequence = ++requestNumber;
        if (pendingRequest) pendingRequest.abort();
        pendingRequest = typeof AbortController === 'function' ? new AbortController() : null;
        var loading = root.querySelector('.lmd-loading');
        var error = root.querySelector('.lmd-error');
        if (loading) loading.hidden = false;
        if (error) error.hidden = true;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            signal: pendingRequest ? pendingRequest.signal : undefined
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.text();
        }).then(function (html) {
            if (sequence !== requestNumber) return;
            var documentHtml = new DOMParser().parseFromString(html, 'text/html');
            var incoming = documentHtml.getElementById('lmd-results-page');
            var current = page();
            if (!incoming || !current) throw new Error('Vue des résultats introuvable');
            current.replaceWith(incoming);
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(incoming);
            }
            if (pushHistory) history.pushState(null, '', url);
            applySemesterFromUrl();
        }).catch(function (exception) {
            if (sequence !== requestNumber || exception.name === 'AbortError') return;
            var current = page();
            var message = current && current.querySelector('.lmd-error');
            if (message) {
                message.textContent = 'Les résultats n’ont pas pu être actualisés (' + exception.message + '). Réessayez.';
                message.hidden = false;
            }
        }).finally(function () {
            if (sequence !== requestNumber) return;
            var current = page();
            var indicator = current && current.querySelector('.lmd-loading');
            if (indicator) indicator.hidden = true;
        });
    }

    function submitYearFilter(form) {
        var params = new URLSearchParams(new FormData(form));
        // Une autre année ne peut pas conserver l'inscription ou classe de
        // la fiche d'origine ; le serveur réélit les inscriptions de l'année.
        params.delete('inscription_id');
        params.delete('classe_id');
        params.delete('semestre');
        loadContext(form.action + '?' + params.toString(), true);
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('#lmd-results-page [data-lmd-sem]');
        if (!button) return;
        event.preventDefault();
        var uri = new URL(window.location.href);
        if (button.dataset.lmdSem) uri.searchParams.set('semestre', button.dataset.lmdSem);
        else uri.searchParams.delete('semestre');
        if (uri.href !== window.location.href) history.pushState(null, '', uri.href);
        applySemesterFromUrl();
    });

    document.addEventListener('change', function (event) {
        var field = event.target;
        if (field.matches('#lmd-results-filters select[name="annee_universitaire_id"]')) {
            submitYearFilter(field.form);
        }
    });

    document.addEventListener('submit', function (event) {
        if (event.target.id !== 'lmd-results-filters') return;
        event.preventDefault();
        submitYearFilter(event.target);
    });

    window.addEventListener('popstate', function () {
        var root = page();
        if (!root) return;
        var params = new URL(window.location.href).searchParams;
        if ((params.get('annee_universitaire_id') || '') === (root.dataset.anneeId || '')
            && (params.get('inscription_id') || '') === (root.dataset.inscriptionId || '')
            && (params.get('classe_id') || '') === (root.dataset.classeId || '')) {
            applySemesterFromUrl();
        } else {
            loadContext(window.location.href, false);
        }
    });
})();
</script>
@endpush
