@php
    $scoreData = $performanceScore instanceof \App\Models\ESBTPPersonnelScoreSnapshot
        ? [
            'total_score' => $performanceScore->total_score,
            'level' => $performanceScore->level,
            'level_label' => config("personnel_scoring.levels.{$performanceScore->level}.label", $performanceScore->level),
            'level_class' => config("personnel_scoring.levels.{$performanceScore->level}.class", 'muted'),
            'period_start' => optional($performanceScore->period_start)->format('d/m/Y'),
            'period_end' => optional($performanceScore->period_end)->format('d/m/Y'),
            'applicable_dimensions_count' => $performanceScore->applicable_dimensions_count,
            'excluded_dimensions_count' => $performanceScore->excluded_dimensions_count,
            'breakdown' => $performanceScore->breakdown ?? [],
            'calculated_at' => optional($performanceScore->calculated_at)->format('d/m/Y H:i'),
        ]
        : ($performanceScore ?? []);

    $levelClass = $scoreData['level_class'] ?? 'muted';
    $breakdown = collect($scoreData['breakdown'] ?? []);
    $scoreValue = max(0, min(100, (int)($scoreData['total_score'] ?? 0)));
@endphp

@once
@push('styles')
<style>
    .ps-score-card {
        --primary: #0453cb;
        --accent: #3b7ddb;
        --text: #1e293b;
        --muted: #64748b;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    }
    .ps-score-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #edf2f7;
    }
    .ps-section-header { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .ps-section-icon {
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
    .ps-score-title { font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0; }
    .ps-score-sub { color: #64748b; font-size: .82rem; margin: .15rem 0 0; }
    .ps-score-main { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .ps-score-ring {
        width: 108px;
        height: 108px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: conic-gradient(#0453cb calc(var(--score) * 1%), #e2e8f0 0);
        box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    }
    .ps-score-inner {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #fff;
        display: grid;
        place-items: center;
        color: #1e293b;
        font-weight: 900;
        font-size: 1.35rem;
    }
    .ps-meta { display: flex; flex-direction: column; gap: .5rem; min-width: 220px; }
    .ps-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .35rem .75rem;
        font-size: .74rem;
        font-weight: 800;
        width: fit-content;
    }
    .ps-badge.success { background: rgba(16,185,129,.12); color: #047857; border: 1px solid rgba(16,185,129,.2); }
    .ps-badge.primary { background: rgba(4,83,203,.1); color: #0453cb; border: 1px solid rgba(4,83,203,.18); }
    .ps-badge.warning { background: rgba(245,158,11,.14); color: #b45309; border: 1px solid rgba(245,158,11,.22); }
    .ps-badge.danger { background: rgba(220,38,38,.1); color: #b91c1c; border: 1px solid rgba(220,38,38,.18); }
    .ps-badge.muted { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .ps-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: .75rem;
    }
    .ps-dim {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: .9rem;
        background: #f8fafc;
        transition: all .2s ease;
    }
    .ps-dim:hover {
        background: #fff;
        box-shadow: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
    }
    .ps-dim-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        margin-bottom: .65rem;
    }
    .ps-dim-title { font-weight: 800; color: #1e293b; font-size: .88rem; line-height: 1.25; }
    .ps-dim-score { font-weight: 900; color: #0453cb; white-space: nowrap; }
    .ps-bar {
        height: 8px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: .75rem;
    }
    .ps-bar span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #0453cb, #5e91de);
        border-radius: 999px;
    }
    .ps-metrics { display: flex; flex-wrap: wrap; gap: .4rem; }
    .ps-metric {
        background: #fff;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: .25rem .5rem;
        font-size: .72rem;
        font-weight: 700;
    }
    .ps-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 1rem;
        color: #64748b;
        background: #f8fafc;
        font-size: .86rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .ps-empty i { color: #0453cb; }
    @media (max-width: 576px) {
        .ps-score-card { padding: 1rem; }
        .ps-score-ring { width: 96px; height: 96px; }
        .ps-score-inner { width: 72px; height: 72px; font-size: 1.2rem; }
        .ps-meta { min-width: 100%; }
    }
</style>
@endpush
@endonce

<div class="ps-score-card">
    <div class="ps-score-head">
        <div class="ps-section-header">
            <div class="ps-section-icon"><i class="fas fa-chart-line"></i></div>
            <div>
                <h3 class="ps-score-title">Performance operationnelle</h3>
                <p class="ps-score-sub">
                    {{ $scoreData['period_start'] ?? 'Debut periode' }}
                    -
                    {{ $scoreData['period_end'] ?? 'Fin periode' }}
                    @if(!empty($scoreData['calculated_at']))
                        - calcule le {{ $scoreData['calculated_at'] }}
                    @endif
                </p>
            </div>
        </div>

        <div class="ps-score-main">
            <div class="ps-score-ring" style="--score: {{ $scoreValue }}">
                <div class="ps-score-inner">{{ $scoreValue }}%</div>
            </div>
            <div class="ps-meta">
                <span class="ps-badge {{ $levelClass }}">
                    <i class="fas fa-chart-simple"></i>
                    {{ $scoreData['level_label'] ?? 'Donnees insuffisantes' }}
                </span>
                <span class="ps-badge muted">
                    {{ (int)($scoreData['applicable_dimensions_count'] ?? $breakdown->count()) }} dimensions evaluees
                </span>
                @if(($scoreData['excluded_dimensions_count'] ?? 0) > 0)
                    <span class="ps-badge muted">{{ (int)$scoreData['excluded_dimensions_count'] }} hors perimetre</span>
                @endif
            </div>
        </div>
    </div>

    @if($breakdown->isEmpty())
        <div class="ps-empty">
            <i class="fas fa-circle-info"></i>
            <span>Aucune dimension applicable pour cette periode. Verifiez les permissions du role ou lancez un recalcul.</span>
        </div>
    @else
        <div class="ps-grid">
            @foreach($breakdown as $dimension)
                <div class="ps-dim">
                    <div class="ps-dim-top">
                        <div class="ps-dim-title">{{ $dimension['label'] ?? $dimension['dimension'] ?? 'Dimension' }}</div>
                        <div class="ps-dim-score">{{ (int)($dimension['score'] ?? 0) }}%</div>
                    </div>
                    <div class="ps-bar"><span style="width: {{ max(0, min(100, (int)($dimension['score'] ?? 0))) }}%"></span></div>
                    <div class="ps-metrics">
                        @forelse(($dimension['metrics'] ?? []) as $metric => $value)
                            <span class="ps-metric">{{ str_replace('_', ' ', $metric) }}: {{ is_array($value) ? count($value) : ($value ?? '---') }}</span>
                        @empty
                            <span class="ps-metric">Aucune metrique detaillee</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
