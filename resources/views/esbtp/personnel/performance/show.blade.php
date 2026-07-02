@extends('layouts.app')

@section('title', 'Detail Performance - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .pd-page {
        --primary: #0453cb;
        --accent: #3b7ddb;
        --text: #1e293b;
        --muted: #64748b;
        --shadow-sm: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        background: #f4f7fb;
        min-height: 100vh;
        padding: 1.5rem;
    }
    .pd-wrap { max-width: 1180px; margin: 0 auto; }
    .pd-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
    }
    .pd-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .pd-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .pd-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        color: #fff;
        flex-shrink: 0;
        font-weight: 900;
    }
    .pd-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .pd-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .25rem 0 0; }
    .pd-btn {
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 10px;
        padding: .58rem 1rem;
        font-size: .82rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        transition: all .2s ease;
        min-height: 40px;
        text-decoration: none;
    }
    .pd-btn--glass { background: rgba(255,255,255,.15); color: #fff; }
    .pd-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; text-decoration: none; }
    .pd-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .pd-kpi {
        flex: 1;
        min-width: 150px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .pd-kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255,255,255,.12);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex-shrink: 0;
    }
    .pd-kpi-value { font-size: 1.25rem; font-weight: 800; color: #fff; line-height: 1.1; }
    .pd-kpi-label { font-size: .72rem; color: rgba(255,255,255,.68); margin-top: .2rem; text-transform: uppercase; font-weight: 700; }
    .pd-filterbar {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow-sm);
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: end;
    }
    .pd-filter-group { display: grid; gap: .4rem; min-width: 0; }
    .pd-filter-label {
        color: #64748b;
        font-size: .7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .pd-segments { display: inline-flex; gap: .25rem; padding: .25rem; border-radius: 12px; background: #f1f5f9; width: fit-content; max-width: 100%; flex-wrap: wrap; }
    .pd-segment {
        border: 0;
        min-height: 36px;
        border-radius: 9px;
        padding: .45rem .75rem;
        background: transparent;
        color: #475569;
        font-size: .78rem;
        font-weight: 800;
        cursor: pointer;
    }
    .pd-segment:hover { color: #0453cb; background: rgba(4,83,203,.07); }
    .pd-segment.active { background: #0453cb; color: #fff; box-shadow: 0 8px 18px rgba(4,83,203,.16); }
    .pd-filter-actions { display: flex; flex-wrap: wrap; align-items: end; justify-content: flex-end; gap: .75rem; }
    .pd-search {
        position: relative;
        min-width: 260px;
    }
    .pd-search i {
        position: absolute;
        left: .8rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: .8rem;
    }
    .pd-search input {
        width: 100%;
        min-height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        padding: .55rem .8rem .55rem 2rem;
        color: #1e293b;
        font-size: .84rem;
        font-weight: 700;
        background: #fff;
    }
    .pd-search input:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.1);
    }
    .pd-filter-status {
        display: inline-flex;
        align-items: center;
        min-height: 40px;
        padding: .55rem .75rem;
        border-radius: 11px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: .78rem;
        font-weight: 800;
        white-space: nowrap;
    }
    .pd-dim-hidden { display: none !important; }
    .pd-grid {
        display: grid;
        grid-template-columns: minmax(620px, 1fr) 340px;
        gap: 1rem;
        align-items: start;
    }
    .pd-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem;
        box-shadow: var(--shadow-sm);
    }
    .pd-section-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
    .pd-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .pd-section-title { margin: 0; color: #1e293b; font-size: 1rem; font-weight: 800; }
    .pd-section-sub { margin: .15rem 0 0; color: #64748b; font-size: .8rem; }
    .pd-list { display: grid; gap: .65rem; }
    .pd-list-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: .75rem;
    }
    .pd-list-title { color: #1e293b; font-weight: 800; font-size: .86rem; margin-bottom: .25rem; }
    .pd-list-sub { color: #64748b; font-size: .76rem; }
    .pd-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 1rem;
        color: #64748b;
        background: #f8fafc;
        font-size: .86rem;
    }
    @media (max-width: 1200px) {
        .pd-page { padding: 1rem; }
        .pd-hero { padding: 1.5rem; }
        .pd-grid { grid-template-columns: 1fr; }
        .pd-filterbar { grid-template-columns: 1fr; }
        .pd-filter-actions { justify-content: flex-start; }
    }
    @media (max-width: 576px) {
        .pd-page { padding: .75rem; }
        .pd-hero { padding: 1rem; border-radius: 14px; }
        .pd-btn { width: 100%; }
        .pd-kpi { min-width: 100%; }
        .pd-search { min-width: 100%; width: 100%; }
        .pd-filter-status { width: 100%; justify-content: center; }
    }
</style>
@endsection

@section('content')
@php
    $isSnapshot = $scoreData instanceof \App\Models\ESBTPPersonnelScoreSnapshot;
    $totalScore = $isSnapshot ? $scoreData->total_score : ($scoreData['total_score'] ?? 0);
    $level = $isSnapshot ? $scoreData->level : ($scoreData['level'] ?? 'insufficient_data');
    $levelLabel = config("personnel_scoring.levels.{$level}.label", $level);
    $roleName = $isSnapshot ? $scoreData->role_name : ($scoreData['role_name'] ?? $user->getRoleNames()->first());
    $periodStart = $isSnapshot ? optional($scoreData->period_start)->format('d/m/Y') : ($scoreData['period_start'] ?? null);
    $periodEnd = $isSnapshot ? optional($scoreData->period_end)->format('d/m/Y') : ($scoreData['period_end'] ?? null);
    $applicableCount = $isSnapshot ? $scoreData->applicable_dimensions_count : ($scoreData['applicable_dimensions_count'] ?? $breakdown->count());
    $excludedCount = $isSnapshot ? $scoreData->excluded_dimensions_count : ($scoreData['excluded_dimensions_count'] ?? $excludedDimensions->count());
@endphp

<div class="pd-page" data-pd-performance-page data-base-url="{{ route('esbtp.personnel.performance.show', $user) }}">
    <div class="pd-wrap">
        <div class="pd-hero">
            <div class="pd-hero-top">
                <div class="pd-hero-left">
                    <div class="pd-hero-icon">{{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}</div>
                    <div>
                        <h1>{{ $user->name }}</h1>
                        <p>{{ $roleName ?? 'sans_role' }} - Performance detaillee sur {{ config("personnel_scoring.periods.{$period}", $period) }}</p>
                    </div>
                </div>
                <a href="{{ route('esbtp.personnel.performance.index', ['period' => $period]) }}" class="pd-btn pd-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour performance
                </a>
            </div>

            <div class="pd-kpis">
                <div class="pd-kpi">
                    <div class="pd-kpi-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <div><div class="pd-kpi-value">{{ (int) $totalScore }}%</div><div class="pd-kpi-label">Score global</div></div>
                </div>
                <div class="pd-kpi">
                    <div class="pd-kpi-icon"><i class="fas fa-chart-line"></i></div>
                    <div><div class="pd-kpi-value">{{ $levelLabel }}</div><div class="pd-kpi-label">Niveau</div></div>
                </div>
                <div class="pd-kpi">
                    <div class="pd-kpi-icon"><i class="fas fa-layer-group"></i></div>
                    <div><div class="pd-kpi-value">{{ (int) $applicableCount }}</div><div class="pd-kpi-label">Dimensions evaluees</div></div>
                </div>
                <div class="pd-kpi">
                    <div class="pd-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div><div class="pd-kpi-value">{{ $periodStart }} - {{ $periodEnd }}</div><div class="pd-kpi-label">Periode</div></div>
                </div>
            </div>
        </div>

        <div class="pd-filterbar" aria-label="Filtres performance">
            <div class="pd-filter-group">
                <span class="pd-filter-label">Periode</span>
                <div class="pd-segments" role="group" aria-label="Changer la periode">
                    @foreach(['month' => 'Mois', 'quarter' => 'Trimestre', 'year' => 'Annee'] as $key => $label)
                        <button type="button" class="pd-segment {{ $period === $key ? 'active' : '' }}" data-pd-period="{{ $key }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="pd-filter-actions">
                <div class="pd-filter-group">
                    <span class="pd-filter-label">Dimensions</span>
                    <div class="pd-segments" role="group" aria-label="Filtrer les dimensions">
                        <button type="button" class="pd-segment active" data-pd-score-filter="all">Toutes</button>
                        <button type="button" class="pd-segment" data-pd-score-filter="active">Actives</button>
                        <button type="button" class="pd-segment" data-pd-score-filter="watch">A surveiller</button>
                        <button type="button" class="pd-segment" data-pd-score-filter="zero">Zero</button>
                    </div>
                </div>
                <div class="pd-filter-group">
                    <span class="pd-filter-label">Recherche</span>
                    <label class="pd-search">
                        <i class="fas fa-search"></i>
                        <input type="search" data-pd-search placeholder="Rechercher une dimension">
                    </label>
                </div>
                <span class="pd-filter-status" data-pd-filter-status>{{ $breakdown->count() }} dimension(s)</span>
            </div>
        </div>

        <div class="pd-grid">
            <div>
                @include('esbtp.personnel.partials.performance-score', ['performanceScore' => $scoreData, 'showDetailLink' => false])
            </div>

            <div class="pd-card">
                <div class="pd-section-header">
                    <div class="pd-section-icon"><i class="fas fa-tasks"></i></div>
                    <div>
                        <h2 class="pd-section-title">Perimetre de scoring</h2>
                        <p class="pd-section-sub">Dimensions applicables et hors perimetre.</p>
                    </div>
                </div>

                <div class="pd-list">
                    @forelse($breakdown as $dimension)
                        @php
                            $dimensionScore = max(0, min(100, (int)($dimension['score'] ?? 0)));
                            $dimensionLabel = $dimension['label'] ?? $dimension['dimension'] ?? 'Dimension';
                        @endphp
                        <div class="pd-list-item" data-score="{{ $dimensionScore }}" data-label="{{ \Illuminate\Support\Str::lower($dimensionLabel) }}">
                            <div class="pd-list-title">{{ $dimension['label'] ?? $dimension['dimension'] ?? 'Dimension' }}</div>
                            <div class="pd-list-sub">Score: {{ (int)($dimension['score'] ?? 0) }}% - Poids: {{ (int)($dimension['weight'] ?? 0) }}</div>
                        </div>
                    @empty
                        <div class="pd-empty">Aucune dimension applicable sur cette periode.</div>
                    @endforelse
                </div>

                @if($excludedCount > 0)
                    <div class="pd-section-header" style="margin-top:1.25rem;">
                        <div class="pd-section-icon"><i class="fas fa-ban"></i></div>
                        <div>
                            <h2 class="pd-section-title">Hors perimetre</h2>
                            <p class="pd-section-sub">{{ (int) $excludedCount }} dimension(s) exclue(s) par permissions.</p>
                        </div>
                    </div>
                    <div class="pd-list">
                        @forelse($excludedDimensions as $dimension)
                            <div class="pd-list-item">
                                <div class="pd-list-title">{{ $dimension['label'] ?? 'Dimension' }}</div>
                                <div class="pd-list-sub">{{ implode(', ', $dimension['permissions'] ?? []) }}</div>
                            </div>
                        @empty
                            <div class="pd-empty">Details hors perimetre disponibles au prochain recalcul direct.</div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('[data-pd-performance-page]');
    if (!page) return;

    const state = { mode: 'all', search: '' };

    function scoreMatches(score, mode) {
        if (mode === 'active') return score > 0;
        if (mode === 'watch') return score > 0 && score < 50;
        if (mode === 'zero') return score === 0;
        return true;
    }

    function applyDimensionFilters() {
        const rows = Array.from(document.querySelectorAll('.ps-dimension-row, .pd-list-item[data-score]'));
        let visible = 0;

        rows.forEach(function (row) {
            const score = parseInt(row.dataset.score || '0', 10);
            const label = (row.dataset.label || '').toLowerCase();
            const matches = scoreMatches(score, state.mode) && label.includes(state.search);
            row.classList.toggle('pd-dim-hidden', !matches);
            if (matches && row.classList.contains('ps-dimension-row')) visible += 1;
        });

        const status = document.querySelector('[data-pd-filter-status]');
        if (status) status.textContent = visible + ' dimension(s)';
    }

    function bindFilters(root) {
        root.querySelectorAll('[data-pd-score-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                root.querySelectorAll('[data-pd-score-filter]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.mode = button.dataset.pdScoreFilter || 'all';
                applyDimensionFilters();
            });
        });

        const search = root.querySelector('[data-pd-search]');
        if (search) {
            search.addEventListener('input', function () {
                state.search = search.value.trim().toLowerCase();
                applyDimensionFilters();
            });
        }

        root.querySelectorAll('[data-pd-period]').forEach(function (button) {
            button.addEventListener('click', function () {
                loadPeriod(button.dataset.pdPeriod, button);
            });
        });
    }

    async function loadPeriod(period, button) {
        if (!period || button.classList.contains('active')) return;
        const baseUrl = page.dataset.baseUrl;
        const url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('period', period);

        button.disabled = true;
        try {
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            });
            if (!response.ok) throw new Error('Erreur ' + response.status);
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            ['.pd-hero', '.pd-filterbar', '.pd-grid'].forEach(function (selector) {
                const current = document.querySelector(selector);
                const next = doc.querySelector(selector);
                if (current && next) current.replaceWith(next);
            });

            window.history.replaceState({}, '', url.toString());
            state.mode = 'all';
            state.search = '';
            bindFilters(document);
            applyDimensionFilters();
        } catch (error) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: error.message } }));
        } finally {
            button.disabled = false;
        }
    }

    bindFilters(document);
    applyDimensionFilters();
});
</script>
@endpush
