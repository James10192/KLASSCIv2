@php
    $scoreData = $performanceScore instanceof \App\Models\ESBTPPersonnelScoreSnapshot
        ? [
            'user_id' => $performanceScore->user_id,
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
    $applicableCount = (int)($scoreData['applicable_dimensions_count'] ?? $breakdown->count());
    $excludedCount = (int)($scoreData['excluded_dimensions_count'] ?? 0);
    $detailUrl = !empty($scoreData['user_id']) ? route('esbtp.personnel.performance.show', $scoreData['user_id']) : null;
@endphp

@once
@push('styles')
<style>
    .ps-performance-tab {
        --ps-primary: #0453cb;
        --ps-primary-soft: #eaf2ff;
        --ps-accent: #5e91de;
        --ps-text: #1e293b;
        --ps-muted: #64748b;
        --ps-border: #e2e8f0;
        --ps-surface: #ffffff;
        --ps-surface-soft: #f8fafc;
        display: grid;
        gap: 16px;
    }

    .ps-hero {
        position: relative;
        overflow: hidden;
        min-height: 168px;
        border-radius: 18px;
        padding: 24px;
        color: #ffffff;
        background:
            linear-gradient(135deg, rgba(4, 83, 203, .98) 0%, rgba(38, 112, 215, .96) 52%, rgba(94, 145, 222, .94) 100%);
        box-shadow: 0 18px 45px rgba(4, 83, 203, .18);
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 20px;
    }

    .ps-hero-main,
    .ps-score-pill {
        position: relative;
        z-index: 1;
    }

    .ps-title-row {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        margin-bottom: 12px;
    }

    .ps-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .26);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .ps-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: 0;
    }

    .ps-subtitle {
        margin: 3px 0 0;
        color: rgba(255, 255, 255, .78);
        font-size: .84rem;
        line-height: 1.45;
    }

    .ps-meta-line {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 16px;
    }

    .ps-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .22);
        color: #ffffff;
        font-size: .78rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .ps-score-pill {
        width: 148px;
        min-height: 148px;
        border-radius: 22px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .24);
        display: grid;
        place-items: center;
        text-align: center;
        backdrop-filter: blur(8px);
    }

    .ps-score-pill strong {
        display: block;
        font-size: 2.55rem;
        font-weight: 900;
        line-height: .95;
        letter-spacing: 0;
    }

    .ps-score-pill span {
        display: block;
        margin-top: 8px;
        font-size: .76rem;
        font-weight: 800;
        color: rgba(255, 255, 255, .82);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .ps-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .ps-kpi {
        min-height: 94px;
        padding: 16px;
        border: 1px solid var(--ps-border);
        border-radius: 14px;
        background: var(--ps-surface);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ps-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ps-primary);
        background: var(--ps-primary-soft);
        flex-shrink: 0;
    }

    .ps-kpi-value {
        color: var(--ps-text);
        font-size: 1.05rem;
        font-weight: 900;
        line-height: 1.1;
    }

    .ps-kpi-label {
        margin-top: 4px;
        color: var(--ps-muted);
        font-size: .73rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .ps-level-badge {
        display: inline-flex;
        max-width: 100%;
        align-items: center;
        gap: 7px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: .78rem;
        font-weight: 900;
        line-height: 1.2;
    }

    .ps-level-badge.success { background: rgba(16, 185, 129, .12); color: #047857; border: 1px solid rgba(16, 185, 129, .22); }
    .ps-level-badge.primary { background: rgba(4, 83, 203, .10); color: #0453cb; border: 1px solid rgba(4, 83, 203, .18); }
    .ps-level-badge.warning { background: rgba(217, 119, 6, .12); color: #92400e; border: 1px solid rgba(217, 119, 6, .22); }
    .ps-level-badge.danger { background: rgba(220, 38, 38, .10); color: #b91c1c; border: 1px solid rgba(220, 38, 38, .18); }
    .ps-level-badge.muted { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    .ps-section {
        border: 1px solid var(--ps-border);
        border-radius: 16px;
        background: var(--ps-surface);
        overflow: hidden;
    }

    .ps-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--ps-border);
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .ps-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--ps-text);
        font-size: .98rem;
        font-weight: 900;
    }

    .ps-section-title i {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: linear-gradient(135deg, var(--ps-primary), var(--ps-accent));
        font-size: .78rem;
    }

    .ps-detail-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 36px;
        padding: 8px 12px;
        border-radius: 10px;
        background: var(--ps-primary);
        color: #ffffff;
        font-size: .78rem;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 8px 18px rgba(4, 83, 203, .16);
    }

    .ps-detail-link:hover {
        color: #ffffff;
        background: #0346ad;
        text-decoration: none;
    }

    .ps-dimension-list {
        display: grid;
        gap: 0;
    }

    .ps-dimension-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(150px, 220px);
        gap: 18px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--ps-border);
        align-items: center;
    }

    .ps-dimension-row:last-child {
        border-bottom: none;
    }

    .ps-dimension-title {
        color: var(--ps-text);
        font-size: .9rem;
        font-weight: 900;
        line-height: 1.3;
    }

    .ps-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }

    .ps-metric {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 5px 9px;
        border-radius: 8px;
        background: var(--ps-surface-soft);
        border: 1px solid var(--ps-border);
        color: var(--ps-muted);
        font-size: .72rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .ps-score-bar-wrap {
        display: grid;
        gap: 9px;
    }

    .ps-score-bar-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        color: var(--ps-text);
        font-size: .78rem;
        font-weight: 900;
    }

    .ps-score-bar {
        height: 10px;
        border-radius: 999px;
        overflow: hidden;
        background: #e2e8f0;
    }

    .ps-score-bar span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--ps-primary), var(--ps-accent));
    }

    .ps-empty {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        color: var(--ps-muted);
        font-size: .88rem;
        font-weight: 700;
    }

    .ps-empty i {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ps-primary);
        background: var(--ps-primary-soft);
        flex-shrink: 0;
    }

    @media (max-width: 992px) {
        .ps-hero,
        .ps-dimension-row {
            grid-template-columns: 1fr;
        }

        .ps-score-pill {
            width: 100%;
            min-height: 104px;
            justify-items: start;
            padding: 18px;
            text-align: left;
        }

        .ps-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 576px) {
        .ps-hero {
            border-radius: 14px;
            padding: 18px;
        }

        .ps-kpi-grid {
            grid-template-columns: 1fr;
        }

        .ps-kpi,
        .ps-section-head,
        .ps-dimension-row {
            padding: 14px;
        }

        .ps-section-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .ps-detail-link {
            width: 100%;
        }
    }
</style>
@endpush
@endonce

<div class="ps-performance-tab">
    <section class="ps-hero" aria-label="Resume performance">
        <div class="ps-hero-main">
            <div class="ps-title-row">
                <div class="ps-title-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h3 class="ps-title">Performance operationnelle</h3>
                    <p class="ps-subtitle">
                        {{ $scoreData['period_start'] ?? 'Debut periode' }}
                        -
                        {{ $scoreData['period_end'] ?? 'Fin periode' }}
                        @if(!empty($scoreData['calculated_at']))
                            - calcule le {{ $scoreData['calculated_at'] }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="ps-meta-line">
                <span class="ps-hero-badge"><i class="fas fa-layer-group"></i>{{ $applicableCount }} dimensions evaluees</span>
                <span class="ps-hero-badge"><i class="fas fa-ban"></i>{{ $excludedCount }} hors perimetre</span>
                <span class="ps-hero-badge"><i class="fas fa-shield-alt"></i>Base permissions</span>
            </div>
        </div>

        <div class="ps-score-pill">
            <div>
                <strong>{{ $scoreValue }}%</strong>
                <span>score global</span>
            </div>
        </div>
    </section>

    <div class="ps-kpi-grid" aria-label="Indicateurs performance">
        <div class="ps-kpi">
            <div class="ps-kpi-icon"><i class="fas fa-tachometer-alt"></i></div>
            <div>
                <div class="ps-kpi-value">{{ $scoreValue }}%</div>
                <div class="ps-kpi-label">Score global</div>
            </div>
        </div>
        <div class="ps-kpi">
            <div class="ps-kpi-icon"><i class="fas fa-award"></i></div>
            <div>
                <div class="ps-kpi-value">
                    <span class="ps-level-badge {{ $levelClass }}">
                        <i class="fas fa-chart-line"></i>
                        {{ $scoreData['level_label'] ?? 'Donnees insuffisantes' }}
                    </span>
                </div>
                <div class="ps-kpi-label">Niveau</div>
            </div>
        </div>
        <div class="ps-kpi">
            <div class="ps-kpi-icon"><i class="fas fa-tasks"></i></div>
            <div>
                <div class="ps-kpi-value">{{ $applicableCount }}</div>
                <div class="ps-kpi-label">Dimensions actives</div>
            </div>
        </div>
        <div class="ps-kpi">
            <div class="ps-kpi-icon"><i class="fas fa-ban"></i></div>
            <div>
                <div class="ps-kpi-value">{{ $excludedCount }}</div>
                <div class="ps-kpi-label">Hors perimetre</div>
            </div>
        </div>
    </div>

    @if($breakdown->isEmpty())
        <div class="ps-empty">
            <i class="fas fa-circle-info"></i>
            <span>Aucune dimension applicable pour cette periode. Verifiez les permissions du role ou lancez un recalcul.</span>
        </div>
    @else
        <section class="ps-section" aria-label="Dimensions evaluees">
            <div class="ps-section-head">
                <div class="ps-section-title">
                    <i class="fas fa-chart-bar"></i>
                    Dimensions evaluees
                </div>

                @can('performance.view_all')
                    @if($detailUrl)
                        <a href="{{ $detailUrl }}" class="ps-detail-link">
                            <i class="fas fa-external-link-alt"></i>
                            Detail complet
                        </a>
                    @endif
                @endcan
            </div>

            <div class="ps-dimension-list">
                @foreach($breakdown as $dimension)
                    @php
                        $dimensionScore = max(0, min(100, (int)($dimension['score'] ?? 0)));
                    @endphp
                    <article class="ps-dimension-row">
                        <div>
                            <div class="ps-dimension-title">{{ $dimension['label'] ?? $dimension['dimension'] ?? 'Dimension' }}</div>
                            <div class="ps-metrics">
                                @forelse(($dimension['metrics'] ?? []) as $metric => $value)
                                    <span class="ps-metric">
                                        {{ str_replace('_', ' ', $metric) }}:
                                        {{ is_array($value) ? count($value) : ($value ?? '---') }}
                                    </span>
                                @empty
                                    <span class="ps-metric">Aucune metrique detaillee</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="ps-score-bar-wrap">
                            <div class="ps-score-bar-top">
                                <span>Score dimension</span>
                                <strong>{{ $dimensionScore }}%</strong>
                            </div>
                            <div class="ps-score-bar"><span style="width: {{ $dimensionScore }}%"></span></div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
