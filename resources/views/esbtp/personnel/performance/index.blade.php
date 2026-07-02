@extends('layouts.app')

@section('title', 'Performance Personnel - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .pp-page {
        --primary: #0453cb;
        --primary-d: #033a8e;
        --secondary: #5e91de;
        --accent: #3b7ddb;
        --dark: #0f172a;
        --text: #1e293b;
        --muted: #64748b;
        --surface: #f8fafc;
        --shadow-sm: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        --shadow-md: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
        --shadow-lg: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
        background: #f4f7fb;
        min-height: 100vh;
        padding: 1.5rem;
    }
    .pp-wrap { max-width: 1280px; margin: 0 auto; }
    .pp-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: var(--shadow-md);
    }
    .pp-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .pp-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .pp-hero-icon {
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
        flex-shrink: 0;
        color: #fff;
    }
    .pp-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .pp-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .25rem 0 0; }
    .pp-hero-actions { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .pp-period-field {
        width: 180px;
        position: relative;
        z-index: 20;
    }
    .pp-period-field .au-select,
    .pp-period-field .au-select-trigger { width: 100%; }
    .pp-period-field .au-select-trigger {
        background: rgba(255,255,255,.15);
        color: #fff;
        border-color: rgba(255,255,255,.2);
    }
    .pp-period-field .au-select-icon,
    .pp-period-field .au-select-caret,
    .pp-period-field .au-select-value,
    .pp-period-field .au-select-value--placeholder { color: #fff; }
    .pp-btn {
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
        cursor: pointer;
    }
    .pp-btn--glass { background: rgba(255,255,255,.15); color: #fff; }
    .pp-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
    .pp-btn--white { background: #fff; color: #0453cb; border-color: transparent; }
    .pp-btn--white:hover { background: #eff6ff; color: #033a8e; }
    .pp-btn:disabled { opacity: .65; cursor: wait; }
    .pp-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .pp-kpi {
        flex: 1;
        min-width: 140px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .pp-kpi-icon {
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
    .pp-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
    .pp-kpi-label { font-size: .72rem; color: rgba(255,255,255,.68); margin-top: .2rem; text-transform: uppercase; font-weight: 700; }
    .pp-alert {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-bottom: 1rem;
        border-radius: 12px;
        padding: .85rem 1rem;
        background: rgba(4,83,203,.08);
        border: 1px solid rgba(4,83,203,.16);
        color: #0453cb;
        font-weight: 700;
        box-shadow: var(--shadow-sm);
    }
    .pp-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
        overflow: visible;
    }
    .pp-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #edf2f7;
    }
    .pp-section-header { display: flex; align-items: center; gap: .75rem; }
    .pp-section-icon {
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
    .pp-section-title { margin: 0; color: #1e293b; font-size: 1rem; font-weight: 800; }
    .pp-section-sub { margin: .15rem 0 0; color: #64748b; font-size: .8rem; }
    .pp-table-wrap { overflow-x: auto; }
    .pp-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 780px; }
    .pp-table th {
        text-align: left;
        color: #64748b;
        font-size: .72rem;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
        padding: .85rem 1rem;
        background: #f8fafc;
        font-weight: 800;
    }
    .pp-table td {
        border-bottom: 1px solid #f1f5f9;
        padding: .9rem 1rem;
        color: #1e293b;
        vertical-align: middle;
    }
    .pp-table tbody tr { transition: background .2s ease; }
    .pp-table tbody tr:hover { background: rgba(4,83,203,.035); }
    .pp-person { display: flex; align-items: center; gap: .7rem; min-width: 0; }
    .pp-avatar {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(4,83,203,.12), rgba(94,145,222,.18));
        color: #0453cb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        flex-shrink: 0;
    }
    .pp-name { font-weight: 800; color: #1e293b; line-height: 1.25; }
    .pp-role {
        display: inline-flex;
        align-items: center;
        border-radius: 7px;
        padding: .28rem .6rem;
        background: rgba(4,83,203,.08);
        border: 1px solid rgba(4,83,203,.16);
        color: #0453cb;
        font-weight: 800;
        font-size: .74rem;
    }
    .pp-score { font-size: 1rem; font-weight: 900; color: #0453cb; white-space: nowrap; }
    .pp-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .35rem .7rem;
        font-size: .72rem;
        font-weight: 800;
        white-space: nowrap;
    }
    .pp-badge.excellent { background: rgba(16,185,129,.12); color: #047857; border: 1px solid rgba(16,185,129,.2); }
    .pp-badge.good { background: rgba(4,83,203,.1); color: #0453cb; border: 1px solid rgba(4,83,203,.18); }
    .pp-badge.watch { background: rgba(245,158,11,.14); color: #b45309; border: 1px solid rgba(245,158,11,.22); }
    .pp-badge.critical { background: rgba(220,38,38,.1); color: #b91c1c; border: 1px solid rgba(220,38,38,.18); }
    .pp-badge.insufficient_data { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .pp-empty {
        padding: 2rem;
        text-align: center;
        color: #64748b;
    }
    .pp-empty i {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(4,83,203,.08);
        color: #0453cb;
        margin-bottom: .75rem;
    }
    @media (max-width: 992px) {
        .pp-page { padding: 1rem; }
        .pp-hero { padding: 1.5rem; }
        .pp-hero-actions { width: 100%; }
        .pp-period-field { flex: 1; min-width: 180px; }
    }
    @media (max-width: 768px) {
        .pp-hero-left { align-items: flex-start; }
        .pp-hero h1 { font-size: 1.25rem; }
        .pp-card-head { align-items: flex-start; flex-direction: column; }
    }
    @media (max-width: 576px) {
        .pp-page { padding: .75rem; }
        .pp-hero { padding: 1rem; border-radius: 14px; }
        .pp-hero-actions, .pp-btn, .pp-period-field { width: 100%; }
        .pp-kpi { min-width: 100%; }
    }
</style>
@endsection

@section('content')
@php
    $initialRows = $scores->map(fn ($score) => [
        'id' => $score->id,
        'user_id' => $score->user_id,
        'name' => $score->user?->name,
        'role' => $score->role_name,
        'score' => $score->total_score,
        'level' => $score->level,
        'level_label' => config("personnel_scoring.levels.{$score->level}.label", $score->level),
        'dimensions' => $score->applicable_dimensions_count,
        'period' => $score->period_start?->format('d/m/Y').' - '.$score->period_end?->format('d/m/Y'),
    ])->values();
@endphp

<div class="pp-page"
     x-data="personnelPerformancePage({
        period: @js($period),
        summary: @js($summary),
        rows: @js($initialRows),
        dataUrl: @js(route('esbtp.personnel.performance.data')),
        recalculateUrl: @js(route('esbtp.personnel.performance.recalculate')),
        canRecalculate: @js(auth()->user()->can('performance.recalculate')),
        csrf: @js(csrf_token())
     })">
    <div class="pp-wrap">
        <div class="pp-hero">
            <div class="pp-hero-top">
                <div class="pp-hero-left">
                    <div class="pp-hero-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <h1>Performance personnel</h1>
                        <p>Scores automatiques bases sur les permissions effectives et les actions KLASSCI.</p>
                    </div>
                </div>
                <div class="pp-hero-actions">
                    <div class="pp-period-field">
                        <x-au-select
                            name="period"
                            :value="$period"
                            icon="fa-calendar-alt"
                            :placeholderIsFirstOption="false"
                            :options="config('personnel_scoring.periods')"
                            x-model="period"
                            @change="changePeriod($event.target.value)" />
                    </div>
                    @can('performance.recalculate')
                        <button type="button" class="pp-btn pp-btn--white" :disabled="loading || recalculating" @click="recalculate()">
                            <i class="fas fa-sync-alt" :class="{ 'fa-spin': recalculating }"></i>
                            <span x-text="recalculating ? 'Recalcul...' : 'Recalculer'"></span>
                        </button>
                    @endcan
                </div>
            </div>

            <div class="pp-kpis">
                <div class="pp-kpi">
                    <div class="pp-kpi-icon"><i class="fas fa-gauge-high"></i></div>
                    <div><div class="pp-kpi-value" x-text="summary.average + '%'"></div><div class="pp-kpi-label">Score moyen</div></div>
                </div>
                <div class="pp-kpi">
                    <div class="pp-kpi-icon"><i class="fas fa-users"></i></div>
                    <div><div class="pp-kpi-value" x-text="summary.count"></div><div class="pp-kpi-label">Personnels scores</div></div>
                </div>
                <div class="pp-kpi">
                    <div class="pp-kpi-icon"><i class="fas fa-star"></i></div>
                    <div><div class="pp-kpi-value" x-text="summary.excellent"></div><div class="pp-kpi-label">Excellent</div></div>
                </div>
                <div class="pp-kpi">
                    <div class="pp-kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <div><div class="pp-kpi-value" x-text="summary.watch"></div><div class="pp-kpi-label">Points d'attention</div></div>
                </div>
            </div>
        </div>

        <div class="pp-alert" x-show="message" x-cloak>
            <i class="fas fa-circle-check"></i>
            <span x-text="message"></span>
        </div>

        <div class="pp-card">
            <div class="pp-card-head">
                <div class="pp-section-header">
                    <div class="pp-section-icon"><i class="fas fa-list-check"></i></div>
                    <div>
                        <h2 class="pp-section-title">Scores par personnel</h2>
                        <p class="pp-section-sub">Perimetre recalcule selon les permissions disponibles pour chaque utilisateur.</p>
                    </div>
                </div>
                <span class="pp-badge insufficient_data" x-show="loading" x-cloak>
                    <i class="fas fa-circle-notch fa-spin"></i> Chargement
                </span>
            </div>

            <div class="pp-table-wrap" x-show="rows.length > 0">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Personnel</th>
                            <th>Role</th>
                            <th>Score</th>
                            <th>Niveau</th>
                            <th>Dimensions</th>
                            <th>Periode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in rows" :key="row.id">
                            <tr>
                                <td>
                                    <div class="pp-person">
                                        <div class="pp-avatar" x-text="initials(row.name)"></div>
                                        <div class="pp-name" x-text="row.name || ('Utilisateur #' + row.user_id)"></div>
                                    </div>
                                </td>
                                <td><span class="pp-role" x-text="row.role || 'sans_role'"></span></td>
                                <td><span class="pp-score" x-text="row.score + '%'"></span></td>
                                <td>
                                    <span class="pp-badge" :class="row.level || 'insufficient_data'">
                                        <i class="fas fa-chart-simple"></i>
                                        <span x-text="row.level_label || 'Donnees insuffisantes'"></span>
                                    </span>
                                </td>
                                <td x-text="row.dimensions"></td>
                                <td x-text="row.period"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="pp-empty" x-show="!loading && rows.length === 0" x-cloak>
                <i class="fas fa-chart-line"></i>
                <div>Aucun snapshot disponible pour cette periode.</div>
                @can('performance.recalculate')
                    <button type="button" class="pp-btn pp-btn--white" style="margin-top:1rem;border-color:#e2e8f0;" @click="recalculate()">
                        <i class="fas fa-sync-alt"></i> Initialiser les scores
                    </button>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function personnelPerformancePage(config) {
    return {
        period: config.period,
        summary: config.summary,
        rows: config.rows,
        loading: false,
        recalculating: false,
        message: '',
        initials(name) {
            return (name || '?')
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map(part => part[0])
                .join('')
                .toUpperCase();
        },
        async changePeriod(period) {
            this.period = period;
            const url = new URL(window.location.href);
            url.searchParams.set('period', period);
            window.history.pushState({}, '', url.toString());
            await this.refresh();
        },
        async refresh() {
            this.loading = true;
            this.message = '';
            try {
                const url = new URL(config.dataUrl, window.location.origin);
                url.searchParams.set('period', this.period);
                const response = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('Impossible de charger les scores.');
                }
                const payload = await response.json();
                this.summary = payload.summary || this.summary;
                this.rows = payload.data || [];
            } catch (error) {
                this.message = error.message;
            } finally {
                this.loading = false;
            }
        },
        async recalculate() {
            if (!config.canRecalculate) {
                return;
            }
            this.recalculating = true;
            this.message = '';
            try {
                const response = await fetch(config.recalculateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify({ period: this.period }),
                });
                if (!response.ok) {
                    throw new Error('Le recalcul a echoue.');
                }
                const payload = await response.json();
                this.summary = payload.summary || this.summary;
                this.rows = payload.data || this.rows;
                this.message = payload.message || 'Scores recalcules.';
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: this.message }
                }));
            } catch (error) {
                this.message = error.message;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'error', message: error.message }
                }));
            } finally {
                this.recalculating = false;
            }
        },
    };
}
</script>
@endpush
