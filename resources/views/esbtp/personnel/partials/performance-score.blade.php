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
@endphp

<style>
    .ps-score-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .ps-score-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .ps-score-ring { width:116px; height:116px; border-radius:50%; display:grid; place-items:center; background:conic-gradient(#0453cb calc(var(--score) * 1%), #e2e8f0 0); }
    .ps-score-inner { width:88px; height:88px; border-radius:50%; background:#fff; display:grid; place-items:center; color:#1e293b; font-weight:800; font-size:1.45rem; }
    .ps-score-meta { flex:1; min-width:220px; }
    .ps-score-title { font-size:1.05rem; font-weight:800; color:#1e293b; margin:0 0 4px; }
    .ps-score-sub { color:#64748b; font-size:.85rem; margin:0; }
    .ps-badge { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 12px; font-size:.76rem; font-weight:700; }
    .ps-badge.success { background:#dcfce7; color:#166534; }
    .ps-badge.primary { background:#dbeafe; color:#1d4ed8; }
    .ps-badge.warning { background:#fef3c7; color:#92400e; }
    .ps-badge.danger { background:#fee2e2; color:#991b1b; }
    .ps-badge.muted { background:#f1f5f9; color:#475569; }
    .ps-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px; }
    .ps-dim { border:1px solid #e2e8f0; border-radius:10px; padding:14px; background:#f8fafc; }
    .ps-dim-top { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; }
    .ps-dim-title { font-weight:700; color:#1e293b; font-size:.9rem; }
    .ps-dim-score { font-weight:800; color:#0453cb; }
    .ps-bar { height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin-bottom:10px; }
    .ps-bar span { display:block; height:100%; background:linear-gradient(90deg,#0453cb,#5e91de); border-radius:999px; }
    .ps-metrics { display:flex; flex-wrap:wrap; gap:6px; }
    .ps-metric { background:#fff; color:#64748b; border:1px solid #e2e8f0; border-radius:8px; padding:4px 8px; font-size:.72rem; }
    .ps-empty { border:1px dashed #cbd5e1; border-radius:10px; padding:14px; color:#64748b; background:#f8fafc; font-size:.85rem; }
</style>

<div class="ps-score-card">
    <div class="ps-score-head">
        <div class="ps-score-ring" style="--score: {{ (int)($scoreData['total_score'] ?? 0) }}">
            <div class="ps-score-inner">{{ (int)($scoreData['total_score'] ?? 0) }}%</div>
        </div>
        <div class="ps-score-meta">
            <h3 class="ps-score-title">Performance operationnelle</h3>
            <p class="ps-score-sub">
                {{ $scoreData['period_start'] ?? ($scoreData['period_start'] ?? 'Debut periode') }}
                -
                {{ $scoreData['period_end'] ?? ($scoreData['period_end'] ?? 'Fin periode') }}
                @if(!empty($scoreData['calculated_at']))
                    · calcule le {{ $scoreData['calculated_at'] }}
                @endif
            </p>
            <div style="margin-top:10px;">
                <span class="ps-badge {{ $levelClass }}">
                    <i class="fas fa-chart-line"></i>
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
        <div class="ps-empty">Aucune dimension applicable pour cette periode. Verifiez les permissions du role ou lancez un recalcul.</div>
    @else
        <div class="ps-grid">
            @foreach($breakdown as $dimension)
                <div class="ps-dim">
                    <div class="ps-dim-top">
                        <div class="ps-dim-title">{{ $dimension['label'] ?? $dimension['dimension'] ?? 'Dimension' }}</div>
                        <div class="ps-dim-score">{{ (int)($dimension['score'] ?? 0) }}%</div>
                    </div>
                    <div class="ps-bar"><span style="width: {{ (int)($dimension['score'] ?? 0) }}%"></span></div>
                    <div class="ps-metrics">
                        @foreach(($dimension['metrics'] ?? []) as $metric => $value)
                            <span class="ps-metric">{{ str_replace('_', ' ', $metric) }}: {{ is_array($value) ? count($value) : ($value ?? '---') }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
