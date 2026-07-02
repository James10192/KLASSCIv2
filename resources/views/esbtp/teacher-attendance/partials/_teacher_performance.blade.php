@php
    $score = $performanceScore ?? null;
    $level = $score?->level ?? 'insufficient_data';
    $levelClass = config("personnel_scoring.levels.{$level}.class", 'muted');
    $levelLabel = config("personnel_scoring.levels.{$level}.label", 'Donnees insuffisantes');
    $breakdown = collect($score?->breakdown ?? [])->take(3);
    $detailUrl = $score?->user_id
        ? route('esbtp.personnel.performance.show', [
            'user' => $score->user_id,
            'period' => $score->period_type ?? request('period', 'month'),
        ])
        : null;
@endphp

<div class="tdr-score-card">
    <div class="tdr-score-head">
        <div class="tdr-score-head-main">
            <div class="tdr-score-icon"><i class="fas fa-chart-line"></i></div>
            <div>
                <div class="tdr-score-title">Performance KLASSCI</div>
                <div class="tdr-score-sub">Score automatique base sur les permissions et activites</div>
            </div>
        </div>
        @can('performance.view_all')
            @if($detailUrl)
                <a href="{{ $detailUrl }}" class="tdr-score-link" aria-label="Voir le detail performance">
                    <i class="fas fa-external-link-alt"></i>
                    Voir detail
                </a>
            @endif
        @endcan
    </div>

    @if(! $score)
        <div class="tdr-score-empty">
            <i class="fas fa-circle-info"></i>
            <span>Aucun score disponible sur cette periode. Lancez un recalcul depuis le module Performance.</span>
        </div>
    @else
        <div class="tdr-score-main">
            <div class="tdr-score-value">
                <strong>{{ (int) $score->total_score }}%</strong>
                <span>Score global</span>
            </div>
            <div class="tdr-score-meta">
                <span class="tdr-score-badge {{ $levelClass }}"><i class="fas fa-award"></i>{{ $levelLabel }}</span>
                <span class="tdr-score-chip"><i class="fas fa-tasks"></i>{{ (int) $score->applicable_dimensions_count }} dimensions</span>
                <span class="tdr-score-chip"><i class="fas fa-ban"></i>{{ (int) $score->excluded_dimensions_count }} hors perimetre</span>
            </div>
        </div>

        <div class="tdr-score-dims">
            @forelse($breakdown as $dimension)
                @php $dimensionScore = max(0, min(100, (int)($dimension['score'] ?? 0))); @endphp
                <div class="tdr-score-dim">
                    <div class="tdr-score-dim-top">
                        <span>{{ $dimension['label'] ?? $dimension['dimension'] ?? 'Dimension' }}</span>
                        <b>{{ $dimensionScore }}%</b>
                    </div>
                    <div class="tdr-score-bar"><span style="width: {{ $dimensionScore }}%"></span></div>
                </div>
            @empty
                <div class="tdr-score-empty compact">Details disponibles au prochain recalcul.</div>
            @endforelse
        </div>
    @endif
</div>
