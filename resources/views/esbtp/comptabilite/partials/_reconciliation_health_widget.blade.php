{{-- PR6 Widget santé Réconciliation — gated par permission view --}}
@can('comptabilite.reconciliation.view')
@php
    $recMetrics = app(\App\Domain\Comptabilite\Reconciliation\Services\ReconciliationMetricsService::class)->snapshot();
@endphp

@push('styles')
<style>
    .rec-widget {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .rec-widget-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: .85rem; flex-wrap: wrap; gap: .5rem;
    }
    .rec-widget-title {
        display: flex; align-items: center; gap: .6rem;
        font-size: 1.02rem; font-weight: 700; color: #1e293b; margin: 0;
    }
    .rec-widget-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: .9rem;
    }
    .rec-widget-link {
        font-size: .78rem; color: #0453cb; text-decoration: none; font-weight: 600;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .rec-widget-link:hover { text-decoration: underline; color: #033a8e; }
    .rec-widget-health {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .7rem; border-radius: 999px;
        font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
    }
    .rec-widget-health.ok { background: rgba(16,185,129,.1); color: #047857; }
    .rec-widget-health.warning { background: rgba(245,158,11,.1); color: #b45309; }
    .rec-widget-health.degraded { background: rgba(220,38,38,.1); color: #b91c1c; }
    .rec-widget-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: .65rem;
    }
    .rec-widget-tile {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .75rem .9rem;
    }
    .rec-widget-tile-label {
        font-size: .65rem; text-transform: uppercase; color: #64748b;
        letter-spacing: .3px; font-weight: 600;
    }
    .rec-widget-tile-value {
        font-size: 1.4rem; font-weight: 700; color: #1e293b; margin-top: .1rem;
        line-height: 1.1; font-variant-numeric: tabular-nums;
    }
    .rec-widget-tile-sub { font-size: .72rem; color: #64748b; margin-top: .1rem; }
    .rec-widget-tile.alert .rec-widget-tile-value { color: #b91c1c; }
    .rec-widget-tile.warn .rec-widget-tile-value { color: #b45309; }

    .rec-widget-banner {
        display: flex; align-items: center; gap: .75rem;
        padding: .85rem 1rem;
        background: linear-gradient(135deg, rgba(220,38,38,.08), rgba(220,38,38,.04));
        border: 1px solid rgba(220,38,38,.25);
        border-left: 4px solid #dc2626;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-size: .85rem; color: #b91c1c;
    }
    .rec-widget-banner i { font-size: 1.1rem; color: #dc2626; flex-shrink: 0; }
    .rec-widget-banner-msg { flex: 1; }
    .rec-widget-banner-msg strong { color: #7f1d1d; }
    .rec-widget-banner-action {
        background: #dc2626; color: #fff; padding: .4rem .85rem;
        border-radius: 7px; font-size: .78rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .3rem;
    }
    .rec-widget-banner-action:hover { background: #b91c1c; color: #fff; }
</style>
@endpush

<div class="rec-widget">
    @if($recMetrics['overdue_draft_count'] > 0)
        <div class="rec-widget-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="rec-widget-banner-msg">
                <strong>{{ $recMetrics['overdue_draft_count'] }} session(s) overdue</strong> — ouverte(s) depuis plus de {{ $recMetrics['overdue_threshold_days'] }} jour(s) sans clôture. À traiter pour respecter le cycle OHADA.
            </div>
            <a href="{{ route('esbtp.comptabilite.reconciliation.index', ['status' => 'draft']) }}" class="rec-widget-banner-action">
                <i class="fas fa-arrow-right"></i> Traiter
            </a>
        </div>
    @endif
    <div class="rec-widget-header">
        <h3 class="rec-widget-title">
            <span class="rec-widget-icon"><i class="fas fa-balance-scale"></i></span>
            Santé Réconciliation Caisse
        </h3>
        <div style="display:flex;align-items:center;gap:.65rem;">
            <span class="rec-widget-health {{ $recMetrics['health_status'] }}">
                <i class="fas {{ $recMetrics['health_status'] === 'ok' ? 'fa-check-circle' : ($recMetrics['health_status'] === 'warning' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle') }}"></i>
                {{ ['ok' => 'OK', 'warning' => 'Vigilance', 'degraded' => 'Action requise'][$recMetrics['health_status']] }}
            </span>
            <a href="{{ route('esbtp.comptabilite.reconciliation.index') }}" class="rec-widget-link">
                Voir <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="rec-widget-grid">
        <div class="rec-widget-tile {{ $recMetrics['overdue_draft_count'] > 0 ? 'alert' : '' }}">
            <div class="rec-widget-tile-label">Sessions overdue</div>
            <div class="rec-widget-tile-value">{{ $recMetrics['overdue_draft_count'] }}</div>
            <div class="rec-widget-tile-sub">> {{ $recMetrics['overdue_threshold_days'] }} jour(s) sans clôture</div>
        </div>
        <div class="rec-widget-tile">
            <div class="rec-widget-tile-label">Brouillons / En revue</div>
            <div class="rec-widget-tile-value">{{ $recMetrics['sessions_by_status']['draft'] + $recMetrics['sessions_by_status']['review'] }}</div>
            <div class="rec-widget-tile-sub">{{ $recMetrics['sessions_by_status']['draft'] }} brouillon(s) · {{ $recMetrics['sessions_by_status']['review'] }} en revue</div>
        </div>
        <div class="rec-widget-tile {{ ($recMetrics['days_since_last_close'] ?? 0) > 7 ? 'warn' : '' }}">
            <div class="rec-widget-tile-label">Dernière clôture</div>
            <div class="rec-widget-tile-value">
                @if($recMetrics['days_since_last_close'] !== null)
                    {{ (int) $recMetrics['days_since_last_close'] }}j
                @else
                    —
                @endif
            </div>
            <div class="rec-widget-tile-sub">{{ $recMetrics['last_close_code'] ?? 'Aucune' }}</div>
        </div>
        <div class="rec-widget-tile">
            <div class="rec-widget-tile-label">Sessions sans écart (90j)</div>
            <div class="rec-widget-tile-value">
                @if($recMetrics['pct_sessions_no_ecart_90d'] !== null)
                    {{ $recMetrics['pct_sessions_no_ecart_90d'] }}%
                @else
                    —
                @endif
            </div>
            <div class="rec-widget-tile-sub">Cible : 100%</div>
        </div>
    </div>
</div>
@endcan
