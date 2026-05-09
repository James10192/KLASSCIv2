@extends('layouts.app')

@section('title', 'Analytics Prédictifs')

@section('content')
<div class="container-fluid an-page" x-data="analyticsPage()">

    {{-- ============================ HERO ============================ --}}
    <div class="an-hero">
        <div class="an-hero-top">
            <div class="an-hero-left">
                <div class="an-hero-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h1>Analytics Prédictifs</h1>
                    <p>Cash flow, risque de défaut et anomalies — calculs quotidiens automatiques.</p>
                </div>
            </div>
            <div class="an-hero-right">
                @can('comptabilite.recouvrement.access')
                    <a href="{{ route('esbtp.comptabilite.recouvrement.index') }}" class="an-btn an-btn--glass">
                        <i class="fas fa-hand-holding-usd"></i> Recouvrement
                    </a>
                @endcan
                @can('comptabilite.analytics.run_now')
                    <button type="button"
                            @click="recalculer()"
                            :disabled="recalcul.loading"
                            class="an-btn an-btn--glass">
                        <i class="fas fa-sync-alt" :class="recalcul.loading ? 'fa-spin' : ''"></i>
                        <span x-text="recalcul.loading ? 'Lancement…' : 'Recalculer'"></span>
                    </button>
                @endcan
                <x-export-modal
                    :preview-url="route('esbtp.comptabilite.analytics.preview-pdf')"
                    :pdf-url="route('esbtp.comptabilite.analytics.export-pdf')"
                    :excel-url="route('esbtp.comptabilite.analytics.export-excel')"
                    button-class="an-btn an-btn--glass" />
                @can('comptabilite.analytics.configure')
                    <a href="{{ route('esbtp.comptabilite.analytics.settings') }}" class="an-btn an-btn--glass">
                        <i class="fas fa-sliders-h"></i> Paramètres
                    </a>
                @endcan
            </div>
        </div>

        <div class="an-kpis">
            <div class="an-kpi">
                <div class="an-kpi-icon"><i class="fas fa-coins"></i></div>
                <div>
                    <div class="an-kpi-value">
                        @if($cashFlow->isAvailable())
                            {{ number_format($cashFlow->value, 0, ',', ' ') }} <span class="an-kpi-unit">FCFA</span>
                        @else
                            N/D
                        @endif
                    </div>
                    <div class="an-kpi-label">Recettes prévues le mois prochain</div>
                </div>
            </div>

            <div class="an-kpi">
                <div class="an-kpi-icon"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="an-kpi-value">
                        @if($defaultRisk->isAvailable())
                            {{ (int) $defaultRisk->value }}
                        @else
                            N/D
                        @endif
                    </div>
                    <div class="an-kpi-label">Étudiants à haut risque de défaut</div>
                </div>
            </div>

            @php
                $criticalCount = collect($anomalies)->where('severity', \App\Domain\Analytics\DTOs\AnomalyAlert::SEVERITY_CRITICAL)->count();
                $warningCount = collect($anomalies)->where('severity', \App\Domain\Analytics\DTOs\AnomalyAlert::SEVERITY_WARNING)->count();
            @endphp
            <div class="an-kpi">
                <div class="an-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="an-kpi-value">{{ $criticalCount }} <span class="an-kpi-unit">critiques</span></div>
                    <div class="an-kpi-label">{{ $warningCount }} alertes secondaires détectées</div>
                </div>
            </div>

            <div class="an-kpi">
                <div class="an-kpi-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="an-kpi-value">
                        @if($lastComputedAt)
                            {{ $lastComputedAt->locale('fr')->diffForHumans() }}
                        @else
                            Jamais
                        @endif
                    </div>
                    <div class="an-kpi-label">Dernier calcul automatique</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ FLASH ============================ --}}
    @if(session('success'))
        <div class="alert alert-success an-alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger an-alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    @if(($echeancierMode ?? 'configured') === 'fallback' && !empty($echeancierNote))
        <div class="an-fallback-banner">
            <div class="an-fallback-banner-icon"><i class="fas fa-info-circle"></i></div>
            <div class="an-fallback-banner-body">
                <strong>Mode dégradé actif</strong>
                <p>{{ $echeancierNote }}</p>
            </div>
            @can('comptabilite.frais.configure')
                <a href="{{ route('esbtp.comptabilite.echeanciers.index') }}" class="an-fallback-banner-cta">
                    <i class="fas fa-sliders-h"></i> Configurer les règles
                </a>
            @endcan
        </div>
    @endif

    {{-- Bandeaux qualité données : signalent saturation, auto-calibration, jamais-calculé --}}
    @php
        $riskHautPct = 0.0;
        if ($defaultRisk->isAvailable()) {
            $bk = $defaultRisk->metadata['buckets'] ?? [];
            $tot = $defaultRisk->metadata['total_actifs'] ?? 0;
            $riskHautPct = $tot > 0 ? round(($bk['haut'] ?? 0) / $tot * 100, 1) : 0;
        }
        $riskSaturated = $riskHautPct >= 70.0;
        $autoCalibrated = (bool) ($defaultRisk->metadata['auto_calibrated'] ?? false);
        $neverComputed = !$lastComputedAt;
    @endphp

    @if($riskSaturated && !$autoCalibrated)
        <div class="an-quality-banner an-quality-banner--warn">
            <div class="an-quality-banner-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="an-quality-banner-body">
                <strong>Saturation détectée — {{ $riskHautPct }} % à haut risque</strong>
                <p>Quand quasiment toute la cohorte sature un bucket, le score de risque perd son pouvoir discriminant. Active l'auto-calibration pour que le seuil s'adapte dynamiquement, ou vérifie la complétude de tes règles d'échéancier.</p>
            </div>
            @can('comptabilite.analytics.configure')
                <a href="{{ route('esbtp.comptabilite.analytics.settings') }}" class="an-quality-banner-cta">
                    <i class="fas fa-sliders-h"></i> Calibration
                </a>
            @endcan
        </div>
    @endif

    @if($autoCalibrated)
        <div class="an-quality-banner an-quality-banner--info">
            <div class="an-quality-banner-icon"><i class="fas fa-magic"></i></div>
            <div class="an-quality-banner-body">
                <strong>Auto-calibration appliquée</strong>
                <p>Le seuil "haut risque" a été élevé automatiquement (cohorte saturée). Le Top {{ count($defaultRisk->metadata['top_at_risk'] ?? []) }} reste les plus prioritaires en valeur d'exposition.</p>
            </div>
        </div>
    @endif

    @if($neverComputed)
        <div class="an-quality-banner an-quality-banner--info">
            <div class="an-quality-banner-icon"><i class="fas fa-clock"></i></div>
            <div class="an-quality-banner-body">
                <strong>Calcul automatique jamais lancé</strong>
                <p>Le scheduler ne semble pas avoir tourné. Lance un recalcul manuel pour initier l'historique de précision.</p>
            </div>
            @can('comptabilite.analytics.run_now')
                <button type="button" @click="recalculer()" :disabled="recalcul.loading" class="an-quality-banner-cta">
                    <i class="fas fa-sync-alt" :class="recalcul.loading ? 'fa-spin' : ''"></i>
                    <span x-text="recalcul.loading ? 'Lancement…' : 'Lancer maintenant'"></span>
                </button>
            @endcan
        </div>
    @endif

    {{-- ============================ CASH FLOW ============================ --}}
    <div class="an-card mt-4">
        <div class="an-section-header">
            <div class="an-section-icon"><i class="fas fa-chart-area"></i></div>
            <div>
                <h2 class="an-section-title">Projection cash-flow — mois prochain</h2>
                <p class="an-section-sub">Tranches restantes des échéanciers actifs combinées à la saisonnalité des encaissements (jusqu'à 24 mois d'historique).</p>
            </div>
        </div>

        @if(($cashFlowAccuracy['label'] ?? null) !== null)
            @php $accLabel = $cashFlowAccuracy['label']; @endphp
            <div class="an-accuracy an-accuracy--{{ $accLabel }}">
                <i class="fas fa-shield-alt"></i>
                <span>
                    <strong>Précision du modèle :
                    @if($accLabel === 'excellente') Excellente
                    @elseif($accLabel === 'bonne') Bonne
                    @else À surveiller
                    @endif
                    </strong>
                    — évaluée sur les 6 derniers mois de prédictions vs paiements réellement encaissés.
                </span>
            </div>
        @endif

        @if($cashFlow->isAvailable())
            <div class="an-cf">
                <div class="an-cf-main">
                    <div class="an-cf-value">
                        {{ number_format($cashFlow->value, 0, ',', ' ') }}
                        <span class="an-cf-unit">FCFA</span>
                    </div>
                    @if($cashFlow->confidenceInterval)
                        <div class="an-cf-range">
                            <span class="an-cf-range-label">Intervalle 95% :</span>
                            de <strong>{{ number_format($cashFlow->confidenceInterval->lower, 0, ',', ' ') }}</strong>
                            à <strong>{{ number_format($cashFlow->confidenceInterval->upper, 0, ',', ' ') }}</strong> FCFA
                        </div>
                    @endif
                    @if($cashFlow->targetDate)
                        <div class="an-cf-target">
                            Cible : {{ ucfirst(\Carbon\Carbon::parse($cashFlow->targetDate)->locale('fr')->translatedFormat('F Y')) }}
                        </div>
                    @endif
                    <div class="an-cf-confidence">
                        <span class="an-conf an-conf--{{ $cashFlow->confidenceLabel }}">
                            <i class="fas fa-shield-alt"></i>
                            @if($cashFlow->confidenceLabel === 'tres_fiable') Très fiable
                            @elseif($cashFlow->confidenceLabel === 'fiable') Fiable
                            @else Indicatif
                            @endif
                        </span>
                    </div>
                </div>
                <div class="an-cf-reasons">
                    <div class="an-reasons-title">Pourquoi cette estimation</div>
                    <ul class="an-reasons-list">
                        @foreach($cashFlow->explanation as $reason)
                            <li><i class="fas fa-circle"></i> {{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <div class="an-empty">
                <i class="fas fa-info-circle"></i>
                <p>{{ $cashFlow->explanation[0] ?? 'Prévision indisponible.' }}</p>
            </div>
        @endif
    </div>

    {{-- ============================ RECOUVREMENT GAP ============================ --}}
    @php
        $gapBuckets = $recouvrementGaps ?? [];
        $totalExpected = collect($gapBuckets)->sum('expected');
        $totalPaid = collect($gapBuckets)->sum('paid');
        $totalGap = max(0.0, $totalExpected - $totalPaid);
        $globalRate = $totalExpected > 0 ? round($totalPaid / $totalExpected * 100, 1) : null;
        $maxExpected = collect($gapBuckets)->max('expected') ?: 1;
        $analyticsSettings = \App\Helpers\SettingsHelper::getAnalyticsSettings();
        $gapWarningPct = (float) ($analyticsSettings['anomaly']['recouvrement_gap_warning_pct'] ?? \App\Domain\Analytics\Detectors\AnomalyDetector::DEFAULT_RECOUVREMENT_GAP_WARNING_PCT) / 100;
        $gapCriticalPct = (float) ($analyticsSettings['anomaly']['recouvrement_gap_critical_pct'] ?? \App\Domain\Analytics\Detectors\AnomalyDetector::DEFAULT_RECOUVREMENT_GAP_CRITICAL_PCT) / 100;
    @endphp
    <div class="an-card mt-4">
        <div class="an-section-header">
            <div class="an-section-icon"><i class="fas fa-balance-scale"></i></div>
            <div>
                <h2 class="an-section-title">Recouvrement mois par mois — attendu vs encaissé</h2>
                <p class="an-section-sub">Sur les 6 derniers mois clos, ce que les échéanciers prévoyaient comparé à ce qui est effectivement rentré.</p>
            </div>
        </div>

        @if(empty($gapBuckets))
            <div class="an-empty">
                <i class="fas fa-balance-scale-left"></i>
                <p>Aucun montant attendu via les échéanciers sur les 6 derniers mois clos. Configurez des règles d'échéance ou attendez la prochaine échéance.</p>
            </div>
        @else
            <div class="an-gap-summary">
                <div class="an-gap-summary-item">
                    <div class="an-gap-summary-label">Attendu cumulé</div>
                    <div class="an-gap-summary-value">{{ number_format($totalExpected, 0, ',', ' ') }} <span class="an-gap-summary-unit">FCFA</span></div>
                </div>
                <div class="an-gap-summary-item">
                    <div class="an-gap-summary-label">Encaissé cumulé</div>
                    <div class="an-gap-summary-value">{{ number_format($totalPaid, 0, ',', ' ') }} <span class="an-gap-summary-unit">FCFA</span></div>
                </div>
                <div class="an-gap-summary-item">
                    <div class="an-gap-summary-label">Écart restant</div>
                    <div class="an-gap-summary-value an-gap-summary-value--gap">{{ number_format($totalGap, 0, ',', ' ') }} <span class="an-gap-summary-unit">FCFA</span></div>
                </div>
                <div class="an-gap-summary-item">
                    <div class="an-gap-summary-label">Taux de recouvrement</div>
                    <div class="an-gap-summary-value">{{ $globalRate !== null ? number_format($globalRate, 1, ',', ' ') . ' %' : '—' }}</div>
                </div>
            </div>

            @php
                $compactAmount = function (float $amount): string {
                    if ($amount >= 1_000_000_000) return number_format($amount / 1_000_000_000, 2, ',', ' ') . ' G';
                    if ($amount >= 1_000_000) return number_format($amount / 1_000_000, 1, ',', ' ') . ' M';
                    if ($amount >= 1_000) return number_format($amount / 1_000, 0, ',', ' ') . ' k';
                    return number_format($amount, 0, ',', ' ');
                };

                // "Pourquoi ce pic ?" — détecte un mois > 60% de l'attendu cumulé et propose
                // une lecture rapide pour éviter le faux signal "tous les autres mois sont morts".
                $peakInsight = null;
                if ($totalExpected > 0 && !empty($gapBuckets)) {
                    $peakKey = collect($gapBuckets)->sortByDesc('expected')->keys()->first();
                    $peakExpected = (float) ($gapBuckets[$peakKey]['expected'] ?? 0);
                    $peakShare = $peakExpected > 0 ? round(($peakExpected / $totalExpected) * 100, 1) : 0;
                    if ($peakShare >= 60) {
                        [$peakYear, $peakMonth] = array_map('intval', explode('-', $peakKey));
                        $peakDate = \Carbon\Carbon::createFromDate($peakYear, $peakMonth, 1);
                        $peakInsight = [
                            'month_label' => ucfirst($peakDate->locale('fr')->translatedFormat('F Y')),
                            'expected'    => $peakExpected,
                            'share_pct'   => $peakShare,
                        ];
                    }
                }
            @endphp

            @if($peakInsight)
                <div class="an-peak-insight">
                    <div class="an-peak-insight-icon"><i class="fas fa-lightbulb"></i></div>
                    <div class="an-peak-insight-body">
                        <strong>Pic concentré sur {{ $peakInsight['month_label'] }}</strong>
                        <p>
                            {{ number_format($peakInsight['expected'], 0, ',', ' ') }} FCFA attendus ce mois-là, soit <strong>{{ $peakInsight['share_pct'] }} %</strong> de l'attendu cumulé sur la fenêtre.
                            Souvent dû à des inscriptions concentrées dans le temps + une règle d'échéancier qui fait tomber la même tranche pour tout le monde au même moment.
                            Si tes inscriptions sont bien étalées sur l'année, vérifie tes règles d'échéancier (tranches en pourcentage avec délais variés).
                        </p>
                    </div>
                </div>
            @endif
            <div class="an-gap-rows">
                @foreach($gapBuckets as $monthKey => $bucket)
                    @php
                        [$year, $month] = array_map('intval', explode('-', $monthKey));
                        $monthDate = \Carbon\Carbon::createFromDate($year, $month, 1);
                        $monthShort = ucfirst($monthDate->locale('fr')->translatedFormat('M Y'));
                        $monthFull = ucfirst($monthDate->locale('fr')->translatedFormat('F Y'));
                        $paidPct = $bucket['expected'] > 0 ? min(100, ($bucket['paid'] / $bucket['expected']) * 100) : 0;
                        $gapRatio = $bucket['gap_ratio'];
                        $tone = $gapRatio >= $gapCriticalPct ? 'critical' : ($gapRatio >= $gapWarningPct ? 'warning' : 'ok');
                    @endphp
                    <div class="an-gap-row an-gap-row--{{ $tone }}" title="{{ $monthFull }} — Attendu : {{ number_format($bucket['expected'], 0, ',', ' ') }} FCFA · Reçu : {{ number_format($bucket['paid'], 0, ',', ' ') }} FCFA">
                        <div class="an-gap-row-month">{{ $monthShort }}</div>
                        <div class="an-gap-row-track">
                            <div class="an-gap-row-fill" style="width: {{ $paidPct }}%;"></div>
                            <span class="an-gap-row-fill-label">{{ number_format($paidPct, 0) }}%</span>
                        </div>
                        <div class="an-gap-row-amounts">
                            <span class="an-gap-row-amounts-paid">{{ $compactAmount($bucket['paid']) }}</span>
                            <span class="an-gap-row-amounts-sep">/</span>
                            <span class="an-gap-row-amounts-expected">{{ $compactAmount($bucket['expected']) }}</span>
                            <span class="an-gap-row-amounts-unit">FCFA</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="an-gap-legend">
                <span class="an-gap-legend-item"><span class="an-gap-legend-dot an-gap-legend-dot--ok"></span> Sain (&lt; {{ (int) ($gapWarningPct * 100) }}% d'écart)</span>
                <span class="an-gap-legend-item"><span class="an-gap-legend-dot an-gap-legend-dot--warning"></span> Surveillance (≥ {{ (int) ($gapWarningPct * 100) }}% d'écart)</span>
                <span class="an-gap-legend-item"><span class="an-gap-legend-dot an-gap-legend-dot--critical"></span> Critique (≥ {{ (int) ($gapCriticalPct * 100) }}% d'écart)</span>
                <span class="an-gap-legend-spacer"></span>
                <a href="{{ route('esbtp.comptabilite.analytics.settings') }}#anomaly" class="an-gap-legend-link"><i class="fas fa-sliders-h"></i> Ajuster les seuils</a>
            </div>
        @endif
    </div>

    {{-- ============================ DEFAULT RISK ============================ --}}
    <div class="an-card mt-4">
        <div class="an-section-header">
            <div class="an-section-icon"><i class="fas fa-user-shield"></i></div>
            <div>
                <h2 class="an-section-title">Risque de défaut de paiement</h2>
                <p class="an-section-sub">Score logistique multi-critères : solde restant, jours de retard, engagement, montant attendu.</p>
            </div>
        </div>

        @if($defaultRisk->isAvailable())
            @php
                $buckets = $defaultRisk->metadata['buckets'] ?? ['haut' => 0, 'moyen' => 0, 'bas' => 0];
                $totalActifs = $defaultRisk->metadata['total_actifs'] ?? 0;
                $tauxRisque = $defaultRisk->metadata['taux_risque_pct'] ?? 0;
                $totalSoldeHaut = $defaultRisk->metadata['total_solde_haut_risque'] ?? 0;
                $topAtRisk = $defaultRisk->metadata['top_at_risk'] ?? [];
            @endphp

            <div class="an-risk-grid">
                <div class="an-risk-bucket an-risk-bucket--haut">
                    <div class="an-risk-bucket-value">{{ $buckets['haut'] }}</div>
                    <div class="an-risk-bucket-label">Haut risque</div>
                </div>
                <div class="an-risk-bucket an-risk-bucket--moyen">
                    <div class="an-risk-bucket-value">{{ $buckets['moyen'] }}</div>
                    <div class="an-risk-bucket-label">Surveillance</div>
                </div>
                <div class="an-risk-bucket an-risk-bucket--bas">
                    <div class="an-risk-bucket-value">{{ $buckets['bas'] }}</div>
                    <div class="an-risk-bucket-label">À jour ou faible risque</div>
                </div>
                <div class="an-risk-bucket an-risk-bucket--total">
                    <div class="an-risk-bucket-value">{{ $totalActifs }}</div>
                    <div class="an-risk-bucket-label">Étudiants actifs analysés</div>
                </div>
            </div>

            <div class="an-risk-summary">
                <div class="an-risk-stat">
                    <div class="an-risk-stat-label">Taux de risque cumulé</div>
                    <div class="an-risk-stat-value">{{ number_format($tauxRisque, 1, ',', ' ') }}%</div>
                </div>
                <div class="an-risk-stat">
                    <div class="an-risk-stat-label">Exposition haut risque</div>
                    <div class="an-risk-stat-value">{{ number_format($totalSoldeHaut, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>

            <div class="an-reasons mt-3">
                @foreach($defaultRisk->explanation as $reason)
                    <div class="an-reason-line"><i class="fas fa-info-circle"></i> {{ $reason }}</div>
                @endforeach
            </div>

            @if(!empty($topAtRisk))
                @php
                    // Classes uniques pour le filtre Top 50
                    $topClasses = collect($topAtRisk)->pluck('classe_nom')->unique()->filter()->values();
                @endphp

                <div class="d-flex justify-content-between align-items-center mt-4 mb-2 flex-wrap gap-2">
                    <h3 class="an-subtitle mb-0">Top {{ count($topAtRisk) }} étudiants prioritaires</h3>
                    <div class="an-top-controls" x-data="{ classFilter: '', sortKey: 'score', sortDir: 'desc' }">
                        <select class="form-select form-select-sm an-top-select" x-model="classFilter" @change="$dispatch('top-filter', { classe: classFilter })">
                            <option value="">Toutes classes</option>
                            @foreach($topClasses as $cn)
                                <option value="{{ $cn }}">{{ $cn }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive" x-data="topRiskTable()">
                    <table class="table table-modern an-risk-table">
                        <thead>
                            <tr>
                                <th @click="setSort('etudiant_nom')" style="cursor:pointer;">Étudiant <i class="fas fa-sort an-sort-icon"></i></th>
                                <th @click="setSort('classe_nom')" style="cursor:pointer;">Classe <i class="fas fa-sort an-sort-icon"></i></th>
                                <th class="text-end" @click="setSort('solde_restant')" style="cursor:pointer;">Solde restant <i class="fas fa-sort an-sort-icon"></i></th>
                                <th class="text-center" @click="setSort('jours_retard')" style="cursor:pointer;">Retard <i class="fas fa-sort an-sort-icon"></i></th>
                                <th class="text-center" @click="setSort('ratio_paye')" style="cursor:pointer;">% payé <i class="fas fa-sort an-sort-icon"></i></th>
                                <th class="text-center" @click="setSort('score')" style="cursor:pointer;">Score <i class="fas fa-sort an-sort-icon"></i></th>
                                <th class="text-center">Niveau</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="student in displayed" :key="student.inscription_id">
                                <tr>
                                    <td><strong x-text="student.etudiant_nom"></strong></td>
                                    <td x-text="student.classe_nom"></td>
                                    <td class="text-end" x-text="formatFcfa(student.solde_restant)"></td>
                                    <td class="text-center">
                                        <template x-if="student.jours_retard > 0">
                                            <span class="an-chip an-chip--retard" x-text="student.jours_retard + ' j'"></span>
                                        </template>
                                        <template x-if="!student.jours_retard">
                                            <span class="text-muted">—</span>
                                        </template>
                                    </td>
                                    <td class="text-center" x-text="Math.round(student.ratio_paye * 100) + '%'"></td>
                                    <td class="text-center" x-text="(+student.score).toFixed(2)"></td>
                                    <td class="text-center">
                                        <span class="an-level" :class="'an-level--' + student.level" x-text="student.level.charAt(0).toUpperCase() + student.level.slice(1)"></span>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="displayed.length === 0">
                                <tr><td colspan="7" class="text-center text-muted py-4">Aucun étudiant ne correspond au filtre.</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <script>
                window.topRiskTable = function() {
                    return {
                        rows: @json($topAtRisk),
                        classFilter: '',
                        sortKey: 'score',
                        sortDir: 'desc',
                        init() {
                            // Restore from localStorage si présent
                            try {
                                const saved = JSON.parse(localStorage.getItem('an_top_state') || '{}');
                                if (saved.classFilter !== undefined) this.classFilter = saved.classFilter;
                                if (saved.sortKey) this.sortKey = saved.sortKey;
                                if (saved.sortDir) this.sortDir = saved.sortDir;
                            } catch(e) {}
                            // Listen to external filter
                            window.addEventListener('top-filter', e => {
                                this.classFilter = e.detail.classe || '';
                                this.persist();
                            });
                        },
                        persist() {
                            try {
                                localStorage.setItem('an_top_state', JSON.stringify({
                                    classFilter: this.classFilter, sortKey: this.sortKey, sortDir: this.sortDir
                                }));
                            } catch(e) {}
                        },
                        setSort(key) {
                            if (this.sortKey === key) {
                                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                            } else {
                                this.sortKey = key;
                                this.sortDir = 'desc';
                            }
                            this.persist();
                        },
                        formatFcfa(n) {
                            return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA';
                        },
                        get displayed() {
                            let rows = this.rows;
                            if (this.classFilter) {
                                rows = rows.filter(r => r.classe_nom === this.classFilter);
                            }
                            const dir = this.sortDir === 'asc' ? 1 : -1;
                            return [...rows].sort((a, b) => {
                                const av = a[this.sortKey], bv = b[this.sortKey];
                                if (typeof av === 'number') return (av - bv) * dir;
                                return String(av || '').localeCompare(String(bv || '')) * dir;
                            });
                        },
                    };
                };
                </script>
            @endif
        @else
            <div class="an-empty">
                <i class="fas fa-info-circle"></i>
                <p>{{ $defaultRisk->explanation[0] ?? 'Analyse de risque indisponible.' }}</p>
            </div>
        @endif
    </div>

    {{-- ============================ ANOMALIES ============================ --}}
    @php
        // Métadonnées d'affichage par type d'anomalie. Tout est piloté par
        // ces tables de correspondance pour éviter d'éparpiller les match()
        // dans la vue.
        $typeLabels = [
            'payment_outlier'  => 'Paiement aberrant',
            'recouvrement_gap' => 'Écart de recouvrement',
            'revenue_spike'    => 'Pic de recettes',
            'revenue_drop'     => 'Chute de recettes',
        ];
        $groupOf = fn ($type) => match ($type) {
            'payment_outlier'                => 'paiements',
            'recouvrement_gap'               => 'recouvrement',
            'revenue_spike', 'revenue_drop'  => 'revenus',
            default                           => 'autres',
        };
        $groupMeta = [
            'paiements'    => ['label' => 'Paiements aberrants',      'icon' => 'fa-coins',              'sub' => 'Outliers sur les paiements des 30 derniers jours'],
            'recouvrement' => ['label' => 'Écarts de recouvrement',   'icon' => 'fa-balance-scale-left', 'sub' => 'Mois clos avec un manque-à-gagner significatif'],
            'revenus'      => ['label' => 'Variations mensuelles',    'icon' => 'fa-chart-line',         'sub' => 'Pics ou chutes inhabituels des encaissements (Z-score)'],
        ];
        $monetaryImpact = fn ($alert) => match ($alert->type) {
            'payment_outlier'                => (float) ($alert->context['montant'] ?? 0),
            'recouvrement_gap'               => (float) ($alert->context['gap'] ?? 0),
            'revenue_spike', 'revenue_drop'  => abs(((float) ($alert->context['value'] ?? 0)) - ((float) ($alert->context['mean'] ?? 0))),
            default                           => 0.0,
        };
        $scoreLabel = fn ($alert) => match ($alert->type) {
            'payment_outlier'                => number_format($alert->score, 1, ',', ' ') . '× la moyenne',
            'recouvrement_gap'               => number_format($alert->score * 100, 0) . '% d\'écart',
            'revenue_spike', 'revenue_drop'  => 'Z = ' . number_format($alert->score, 1, ',', ' ') . 'σ',
            default                           => number_format($alert->score, 2),
        };
        $dateLabel = function ($alert) {
            $ctx = $alert->context;
            if (!empty($ctx['date_paiement'])) {
                return ucfirst(\Carbon\Carbon::parse($ctx['date_paiement'])->locale('fr')->translatedFormat('d M Y'));
            }
            if (!empty($ctx['year']) && !empty($ctx['month'])) {
                return ucfirst(\Carbon\Carbon::createFromDate((int) $ctx['year'], (int) $ctx['month'], 1)->locale('fr')->translatedFormat('F Y'));
            }
            return null;
        };
        $actionUrl = function ($alert) {
            if ($alert->type === 'payment_outlier' && !empty($alert->context['paiement_id']) && \Illuminate\Support\Facades\Route::has('esbtp.paiements.show')) {
                return route('esbtp.paiements.show', ['paiement' => $alert->context['paiement_id']]);
            }
            if ($alert->type === 'recouvrement_gap' && \Illuminate\Support\Facades\Route::has('esbtp.comptabilite.recouvrement.index')) {
                return route('esbtp.comptabilite.recouvrement.index');
            }
            return null;
        };
        $actionLabel = fn ($alert) => match ($alert->type) {
            'payment_outlier'  => 'Voir le paiement',
            'recouvrement_gap' => 'Voir le recouvrement',
            default            => 'Voir le détail',
        };

        $anomCollection  = collect($anomalies);
        $anomTotalCount  = $anomCollection->count();
        $anomCriticals   = $anomCollection->where('severity', 'critical')->count();
        $anomWarnings    = $anomCollection->where('severity', 'warning')->count();
        $alertsByGroup   = $anomCollection->groupBy(fn ($a) => $groupOf($a->type));
        $orderedGroups   = ['paiements', 'recouvrement', 'revenus'];
        $groupStats      = [];
        foreach ($orderedGroups as $g) {
            $items = $alertsByGroup->get($g, collect());
            $groupStats[$g] = [
                'items'    => $items,
                'count'    => $items->count(),
                'critical' => $items->where('severity', 'critical')->count(),
                'warning'  => $items->where('severity', 'warning')->count(),
                'impact'   => (float) $items->sum(fn ($a) => $monetaryImpact($a)),
            ];
        }
    @endphp
    <div class="an-card mt-4" x-data="{ filterSev: 'all', filterGroup: 'all' }">
        <div class="an-section-header">
            <div class="an-section-icon"><i class="fas fa-radiation"></i></div>
            <div>
                <h2 class="an-section-title">Anomalies financières détectées</h2>
                <p class="an-section-sub">Écarts entre attendu et encaissé via les échéanciers, Z-score sur les flux mensuels, et outliers sur les paiements des 30 derniers jours.</p>
            </div>
        </div>

        @if($anomTotalCount === 0)
            <div class="an-empty an-empty--ok">
                <i class="fas fa-check-circle"></i>
                <p>Aucune anomalie détectée. Les flux financiers sont conformes aux tendances historiques.</p>
            </div>
        @else
            {{-- Bandeau résumé : 3 cartes-groupes cliquables (servent de raccourci filtre) --}}
            <div class="an-anom-summary">
                @foreach($orderedGroups as $g)
                    @php $s = $groupStats[$g]; @endphp
                    <button type="button"
                            class="an-anom-summary-card an-anom-summary-card--{{ $g }}"
                            :class="{ 'is-active': filterGroup === '{{ $g }}' }"
                            @click="filterGroup = (filterGroup === '{{ $g }}' ? 'all' : '{{ $g }}')">
                        <div class="an-anom-summary-icon"><i class="fas {{ $groupMeta[$g]['icon'] }}"></i></div>
                        <div class="an-anom-summary-body">
                            <div class="an-anom-summary-count">
                                <strong>{{ $s['count'] }}</strong>
                                <span class="an-anom-summary-label">{{ $groupMeta[$g]['label'] }}</span>
                            </div>
                            @if($s['count'] > 0)
                                <div class="an-anom-summary-detail">
                                    {{ $s['critical'] }} critiques · {{ $s['warning'] }} warnings · {{ number_format($s['impact'], 0, ',', ' ') }} FCFA
                                </div>
                            @else
                                <div class="an-anom-summary-detail an-anom-summary-detail--ok">
                                    Conforme
                                </div>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            {{-- Chips filtres sévérité --}}
            <div class="an-anom-filters">
                <button type="button" class="an-anom-chip" :class="{ 'is-active': filterSev === 'all' }" @click="filterSev = 'all'">
                    Toutes <span class="an-anom-chip-count">{{ $anomTotalCount }}</span>
                </button>
                <button type="button" class="an-anom-chip an-anom-chip--critical" :class="{ 'is-active': filterSev === 'critical' }" @click="filterSev = 'critical'">
                    Critiques <span class="an-anom-chip-count">{{ $anomCriticals }}</span>
                </button>
                <button type="button" class="an-anom-chip an-anom-chip--warning" :class="{ 'is-active': filterSev === 'warning' }" @click="filterSev = 'warning'">
                    Avertissements <span class="an-anom-chip-count">{{ $anomWarnings }}</span>
                </button>
                <button type="button" class="an-anom-chip an-anom-chip--reset" x-show="filterGroup !== 'all' || filterSev !== 'all'" @click="filterGroup = 'all'; filterSev = 'all'">
                    <i class="fas fa-times"></i> Réinitialiser
                </button>
            </div>

            {{-- Groupes --}}
            @foreach($orderedGroups as $g)
                @php $s = $groupStats[$g]; @endphp
                <div class="an-anom-group an-anom-group--{{ $g }}" x-show="filterGroup === 'all' || filterGroup === '{{ $g }}'">
                    <div class="an-anom-group-header">
                        <div class="an-anom-group-title">
                            <i class="fas {{ $groupMeta[$g]['icon'] }}"></i>
                            <span>{{ $groupMeta[$g]['label'] }}</span>
                            <span class="an-anom-group-count">{{ $s['count'] }}</span>
                        </div>
                        @if($s['count'] > 0)
                            <div class="an-anom-group-meta">
                                <span class="an-anom-group-impact">{{ number_format($s['impact'], 0, ',', ' ') }} FCFA</span>
                                <span class="an-anom-group-sub">{{ $groupMeta[$g]['sub'] }}</span>
                            </div>
                        @endif
                    </div>

                    @if($s['count'] === 0)
                        <div class="an-anom-group-ok">
                            <i class="fas fa-check-circle"></i>
                            <span>Aucune anomalie détectée — situation conforme.</span>
                        </div>
                    @else
                        <div class="an-anom-list">
                            @foreach($s['items'] as $alert)
                                @php
                                    $url   = $actionUrl($alert);
                                    $date  = $dateLabel($alert);
                                @endphp
                                <div class="an-anom-item an-anom-item--{{ $alert->severity }}"
                                     x-show="filterSev === 'all' || filterSev === '{{ $alert->severity }}'">
                                    <div class="an-anom-item-meta">
                                        <span class="an-anom-dot an-anom-dot--{{ $alert->severity }}"></span>
                                        <span class="an-anom-type">{{ $typeLabels[$alert->type] ?? $alert->type }}</span>
                                        @if($date)
                                            <span class="an-anom-date"><i class="far fa-calendar"></i> {{ $date }}</span>
                                        @endif
                                        <span class="an-anom-score">{{ $scoreLabel($alert) }}</span>
                                        <span class="an-anom-sev an-anom-sev--{{ $alert->severity }}">{{ strtoupper($alert->severity) }}</span>
                                    </div>
                                    <div class="an-anom-item-body">
                                        <span class="an-anom-msg">{{ $alert->message }}</span>
                                        @if($url)
                                            <a href="{{ $url }}" class="an-anom-link">
                                                <i class="fas fa-arrow-right"></i> {{ $actionLabel($alert) }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    {{-- Toast feedback (AJAX no-refresh) --}}
    <div class="an-toast" x-show="toast.message" x-transition :class="'an-toast--' + (toast.type || 'info')">
        <i class="fas" :class="toast.type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
function analyticsPage() {
    return {
        recalcul: { loading: false },
        toast: { message: null, type: 'info' },

        async recalculer() {
            this.recalcul.loading = true;
            try {
                const response = await fetch('{{ route("esbtp.comptabilite.analytics.run-now") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await response.json();
                this.showToast(data.message || (data.success ? 'Recalcul lancé' : 'Erreur'), data.success ? 'success' : 'error');
            } catch (e) {
                this.showToast('Erreur réseau lors du lancement', 'error');
            } finally {
                this.recalcul.loading = false;
            }
        },

        showToast(message, type = 'info') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = { message: null, type: 'info' }; }, 4000);
        },
    };
}
</script>
@endsection

@push('styles')
<style>
:root {
    --an-primary: #0453cb;
    --an-primary-d: #033a8e;
    --an-secondary: #5e91de;
    --an-dark: #0f172a;
    --an-text: #1e293b;
    --an-muted: #64748b;
    --an-border: #e2e8f0;
    --an-success: #10b981;
    --an-warning: #f59e0b;
    --an-danger: #dc2626;
}

.an-page { padding: 1rem 0; }

/* ===== Hero =====
   /!\ Pattern KLASSCI : NE PAS mettre overflow:hidden + position:relative ici.
   Le hero contient un dropdown export (composant x-export-modal) qui s'ouvre
   vers le bas et doit pouvoir deborder. Voir .claude/rules/css-stacking-pitfalls.md
   (ex-bug analytics fixe 2026-05-09). Pour les decorations radiales,
   utiliser un .an-hero-deco enfant absolute avec son propre overflow:hidden. */
.an-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
    animation: an-hero-fade .5s ease-out;
}
@keyframes an-hero-fade {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}
.an-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.an-hero-left { display: flex; align-items: center; gap: 1rem; }
.an-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.an-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.an-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: 0; }
.an-hero-right { display: flex; gap: .5rem; flex-wrap: wrap; }

.an-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .2s ease;
}
.an-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.2);
}
.an-btn--glass:hover {
    background: rgba(255,255,255,.25); color: #fff;
    transform: translateY(-1px);
}

/* KPIs in hero */
.an-kpis {
    display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
}
.an-kpi {
    flex: 1; min-width: 180px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
    transition: background .2s ease, border-color .2s ease, transform .2s ease;
}
.an-kpi:hover {
    background: rgba(255,255,255,.16);
    border-color: rgba(255,255,255,.25);
    transform: translateY(-1px);
}
/* Désactive le transform hover des KPIs quand un dropdown export est ouvert
   ailleurs sur la page : sinon le transform crée un stacking context qui
   passe au-dessus du menu dropdown et bloque les clics. Pattern KLASSCI
   documenté dans .claude/rules/css-stacking-pitfalls.md. */
body:has(.export-menu:not([style*="display: none"])) .an-kpi:hover { transform: none; }
.an-kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #fff; flex-shrink: 0;
}
.an-kpi-value { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1.1; }
.an-kpi-unit { font-size: .68rem; font-weight: 500; opacity: .65; }
.an-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

/* ===== Card ===== */
.an-card {
    background: #fff;
    border: 1px solid var(--an-border);
    border-radius: 14px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    transition: box-shadow .25s ease, transform .25s ease, border-color .25s ease;
}
.an-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.07), 0 2px 8px rgba(15,23,42,.04);
    border-color: rgba(4,83,203,.12);
}

.an-section-header {
    display: flex; align-items: center; gap: .85rem;
    margin-bottom: 1.25rem;
}
.an-section-icon {
    width: 42px; height: 42px; border-radius: 11px;
    background: linear-gradient(135deg, var(--an-primary), var(--an-secondary));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1rem; flex-shrink: 0;
}
.an-section-title { font-size: 1.1rem; font-weight: 700; color: var(--an-dark); margin: 0; }
.an-section-sub { font-size: .82rem; color: var(--an-muted); margin: .15rem 0 0; }

.an-subtitle {
    font-size: .95rem; font-weight: 600; color: var(--an-text);
    margin: 0 0 .75rem;
}

/* ===== Cash Flow card ===== */
.an-cf {
    display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
}
.an-cf-main {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(94,145,222,.04));
    border-radius: 12px;
    border: 1px solid rgba(4,83,203,.1);
}
.an-cf-value {
    font-size: 2.35rem; font-weight: 800; color: var(--an-primary);
    line-height: 1.05; letter-spacing: -.02em;
}
.an-cf-unit { font-size: .9rem; font-weight: 500; color: var(--an-muted); }
.an-cf-range { margin-top: .75rem; font-size: .82rem; color: var(--an-text); }
.an-cf-range-label { color: var(--an-muted); }
.an-cf-target { margin-top: .5rem; font-size: .82rem; color: var(--an-muted); font-style: italic; }
.an-cf-confidence { margin-top: 1rem; }

.an-conf {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .35rem .75rem; border-radius: 999px;
    font-size: .75rem; font-weight: 600;
}
.an-conf--tres_fiable { background: rgba(16,185,129,.12); color: #047857; }
.an-conf--fiable { background: rgba(4,83,203,.1); color: var(--an-primary); }
.an-conf--indicatif { background: rgba(245,158,11,.12); color: #b45309; }

.an-cf-reasons {
    background: #fafbfc; padding: 1rem 1.25rem;
    border-radius: 12px; border: 1px solid var(--an-border);
}
.an-reasons-title {
    font-size: .78rem; font-weight: 700; text-transform: uppercase;
    color: var(--an-muted); letter-spacing: .04em; margin-bottom: .65rem;
}
.an-reasons-list { list-style: none; padding: 0; margin: 0; }
.an-reasons-list li {
    display: flex; align-items: flex-start; gap: .5rem;
    padding: .35rem 0; font-size: .85rem; color: var(--an-text);
    line-height: 1.4;
}
.an-reasons-list li i {
    color: var(--an-primary); font-size: .35rem; margin-top: .55rem; flex-shrink: 0;
}

.an-reasons { display: flex; flex-direction: column; gap: .35rem; }
.an-reason-line {
    font-size: .85rem; color: var(--an-text);
    display: flex; align-items: center; gap: .5rem;
}
.an-reason-line i { color: var(--an-primary); font-size: .8rem; }

/* ===== Risk grid ===== */
.an-risk-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;
    margin-bottom: 1.25rem;
}
.an-risk-bucket {
    padding: 1.25rem 1rem; text-align: center;
    border-radius: 12px; border: 1px solid var(--an-border);
    background: #fafbfc;
}
.an-risk-bucket-value {
    font-size: 1.85rem; font-weight: 700; color: var(--an-dark); line-height: 1;
}
.an-risk-bucket-label {
    font-size: .75rem; color: var(--an-muted);
    margin-top: .35rem; text-transform: uppercase; letter-spacing: .03em;
}
.an-risk-bucket--haut { border-color: rgba(220,38,38,.25); background: rgba(220,38,38,.04); }
.an-risk-bucket--haut .an-risk-bucket-value { color: var(--an-danger); }
.an-risk-bucket--moyen { border-color: rgba(245,158,11,.25); background: rgba(245,158,11,.04); }
.an-risk-bucket--moyen .an-risk-bucket-value { color: var(--an-warning); }
.an-risk-bucket--bas { border-color: rgba(16,185,129,.25); background: rgba(16,185,129,.04); }
.an-risk-bucket--bas .an-risk-bucket-value { color: var(--an-success); }
.an-risk-bucket--total { border-color: rgba(4,83,203,.25); background: rgba(4,83,203,.04); }
.an-risk-bucket--total .an-risk-bucket-value { color: var(--an-primary); }

.an-risk-summary {
    display: flex; gap: 1rem; flex-wrap: wrap;
    padding: 1rem 1.25rem; background: #fafbfc;
    border-radius: 12px; border: 1px solid var(--an-border);
}
.an-risk-stat { flex: 1; min-width: 200px; }
.an-risk-stat-label {
    font-size: .75rem; color: var(--an-muted);
    text-transform: uppercase; letter-spacing: .03em;
}
.an-risk-stat-value {
    font-size: 1.15rem; font-weight: 700; color: var(--an-dark); margin-top: .15rem;
}

.an-top-controls { display: flex; gap: .5rem; align-items: center; }
.an-top-select {
    border-radius: 8px; border: 1px solid #e2e8f0; font-size: .82rem;
    padding: .35rem .65rem; min-width: 180px;
}
.an-sort-icon { font-size: .65rem; color: #94a3b8; margin-left: .25rem; opacity: .7; }
.an-risk-table thead th:hover .an-sort-icon { color: #0453cb; opacity: 1; }
.an-risk-table { font-size: .88rem; margin-top: .5rem; }
.an-risk-table thead th {
    background: #fafbfc; font-weight: 600; color: var(--an-text);
    border-bottom: 2px solid var(--an-border); padding: .75rem;
}
.an-risk-table tbody td { padding: .75rem; vertical-align: middle; }
.an-risk-table tbody tr:hover { background: rgba(4,83,203,.02); }

.an-chip {
    display: inline-block; padding: .25rem .6rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600;
}
.an-chip--retard { background: rgba(245,158,11,.12); color: #b45309; }

.an-level {
    display: inline-block; padding: .25rem .65rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600;
}
.an-level--haut { background: rgba(220,38,38,.12); color: var(--an-danger); }
.an-level--moyen { background: rgba(245,158,11,.12); color: #b45309; }
.an-level--bas { background: rgba(16,185,129,.12); color: #047857; }

/* ===== Fallback banner (mode dégradé) ===== */
.an-fallback-banner {
    display: flex; align-items: center; gap: 1rem;
    padding: .9rem 1.25rem; margin-bottom: 1rem;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(245,158,11,.04));
    border: 1px solid rgba(245,158,11,.25);
}
.an-fallback-banner-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(245,158,11,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; color: #b45309; flex-shrink: 0;
}
.an-fallback-banner-body { flex: 1; min-width: 0; }
.an-fallback-banner-body strong {
    display: block; color: #92400e; font-size: .92rem; font-weight: 700;
    margin-bottom: .15rem;
}
.an-fallback-banner-body p {
    margin: 0; font-size: .82rem; color: #78350f;
    line-height: 1.4;
}
.an-fallback-banner-cta {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1rem; border-radius: 10px;
    background: #b45309; color: #fff; font-size: .8rem; font-weight: 600;
    text-decoration: none; flex-shrink: 0;
    transition: background .15s ease, transform .15s ease;
}
.an-fallback-banner-cta:hover { background: #92400e; color: #fff; transform: translateY(-1px); }

/* ===== Quality banners (saturation risque, never-computed, auto-calibration) ===== */
.an-quality-banner {
    display: flex; align-items: center; gap: 1rem;
    padding: .9rem 1.25rem; margin-bottom: 1rem;
    border-radius: 12px;
}
.an-quality-banner--warn {
    background: linear-gradient(135deg, rgba(220,38,38,.07), rgba(220,38,38,.03));
    border: 1px solid rgba(220,38,38,.22);
}
.an-quality-banner--warn .an-quality-banner-icon { background: rgba(220,38,38,.14); color: #b91c1c; }
.an-quality-banner--warn .an-quality-banner-body strong { color: #991b1b; }
.an-quality-banner--warn .an-quality-banner-body p { color: #7f1d1d; }
.an-quality-banner--warn .an-quality-banner-cta { background: #b91c1c; }
.an-quality-banner--warn .an-quality-banner-cta:hover { background: #991b1b; }

.an-quality-banner--info {
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(94,145,222,.04));
    border: 1px solid rgba(4,83,203,.18);
}
.an-quality-banner--info .an-quality-banner-icon { background: rgba(4,83,203,.12); color: #0453cb; }
.an-quality-banner--info .an-quality-banner-body strong { color: #033a8e; }
.an-quality-banner--info .an-quality-banner-body p { color: #1e3a8a; }
.an-quality-banner--info .an-quality-banner-cta { background: #0453cb; }
.an-quality-banner--info .an-quality-banner-cta:hover { background: #033a8e; }

.an-quality-banner-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.an-quality-banner-body { flex: 1; min-width: 0; }
.an-quality-banner-body strong {
    display: block; font-size: .92rem; font-weight: 700; margin-bottom: .15rem;
}
.an-quality-banner-body p { margin: 0; font-size: .82rem; line-height: 1.4; }
.an-quality-banner-cta {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1rem; border-radius: 10px;
    color: #fff; font-size: .8rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer; flex-shrink: 0;
    transition: background .15s ease, transform .15s ease;
}
.an-quality-banner-cta:hover { color: #fff; transform: translateY(-1px); }

/* ===== Peak insight (Pourquoi ce pic ?) ===== */
.an-peak-insight {
    display: flex; align-items: flex-start; gap: 1rem;
    padding: .9rem 1.25rem; margin: 1rem 0;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(252,211,77,.04));
    border: 1px dashed rgba(245,158,11,.3);
}
.an-peak-insight-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(245,158,11,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #b45309; flex-shrink: 0;
}
.an-peak-insight-body { flex: 1; min-width: 0; }
.an-peak-insight-body strong {
    display: block; color: #92400e; font-size: .9rem; font-weight: 700; margin-bottom: .15rem;
}
.an-peak-insight-body p {
    margin: 0; font-size: .82rem; color: #78350f; line-height: 1.5;
}

/* ===== Recouvrement Gap (attendu vs encaissé) ===== */
.an-gap-summary {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem;
    margin-bottom: 1.5rem;
}
.an-gap-summary-item {
    padding: .9rem 1.1rem; border-radius: 12px;
    background: #fafbfc; border: 1px solid var(--an-border);
}
.an-gap-summary-label {
    font-size: .72rem; color: var(--an-muted);
    text-transform: uppercase; letter-spacing: .04em;
}
.an-gap-summary-value {
    font-size: 1.15rem; font-weight: 700; color: var(--an-dark);
    margin-top: .2rem; line-height: 1.1;
}
.an-gap-summary-value--gap { color: var(--an-danger); }
.an-gap-summary-unit { font-size: .7rem; font-weight: 500; color: var(--an-muted); }

.an-gap-rows {
    display: flex; flex-direction: column; gap: .65rem;
    padding: 1rem 1.25rem; background: #fafbfc;
    border-radius: 12px; border: 1px solid var(--an-border);
}
.an-gap-row {
    display: grid;
    grid-template-columns: 110px 1fr 200px;
    align-items: center; gap: 1rem;
    padding: .25rem 0;
    transition: transform .2s ease;
}
.an-gap-row:hover { transform: translateX(2px); }

.an-gap-row-month {
    font-size: .85rem; font-weight: 600; color: var(--an-text);
    text-transform: capitalize; letter-spacing: -.01em;
    white-space: nowrap;
}

.an-gap-row-track {
    position: relative;
    height: 28px;
    background: rgba(4,83,203,.06);
    border: 1px solid rgba(4,83,203,.12);
    border-radius: 8px;
    overflow: hidden;
}
.an-gap-row-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--an-secondary), var(--an-primary));
    border-radius: 7px 0 0 7px;
    transition: width .6s cubic-bezier(.4,0,.2,1);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
    position: relative;
}
.an-gap-row-fill-label {
    position: absolute;
    top: 50%; left: .75rem;
    transform: translateY(-50%);
    font-size: .8rem; font-weight: 700; color: var(--an-text);
    letter-spacing: -.01em;
    pointer-events: none;
    text-shadow: 0 1px 0 rgba(255,255,255,.6);
}

.an-gap-row--warning .an-gap-row-track {
    background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.25);
}
.an-gap-row--warning .an-gap-row-fill {
    background: linear-gradient(90deg, #fbbf24, #d97706);
}
.an-gap-row--warning .an-gap-row-fill-label { color: #78350f; }

.an-gap-row--critical .an-gap-row-track {
    background: rgba(220,38,38,.08); border-color: rgba(220,38,38,.3);
}
.an-gap-row--critical .an-gap-row-fill {
    background: linear-gradient(90deg, #f87171, var(--an-danger));
}
.an-gap-row--critical .an-gap-row-fill-label { color: #7f1d1d; }

.an-gap-row-amounts {
    font-size: .8rem;
    color: var(--an-muted);
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.an-gap-row-amounts-paid { color: var(--an-text); font-weight: 700; }
.an-gap-row--warning .an-gap-row-amounts-paid { color: #b45309; }
.an-gap-row--critical .an-gap-row-amounts-paid { color: var(--an-danger); }
.an-gap-row-amounts-sep { margin: 0 .2rem; opacity: .5; }
.an-gap-row-amounts-expected { font-weight: 600; color: var(--an-text); }
.an-gap-row-amounts-unit { margin-left: .25rem; font-size: .7rem; opacity: .65; }

.an-gap-legend {
    display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
    margin-top: 1rem; padding: .65rem 1rem;
    border-top: 1px dashed var(--an-border);
    font-size: .78rem; color: var(--an-muted);
}
.an-gap-legend-item { display: inline-flex; align-items: center; gap: .4rem; }
.an-gap-legend-dot {
    width: 10px; height: 10px; border-radius: 3px;
    display: inline-block;
}
.an-gap-legend-dot--ok { background: linear-gradient(180deg, var(--an-secondary), var(--an-primary)); }
.an-gap-legend-dot--warning { background: linear-gradient(180deg, #fbbf24, #d97706); }
.an-gap-legend-dot--critical { background: linear-gradient(180deg, #f87171, var(--an-danger)); }
.an-gap-legend-spacer { flex: 1; min-width: 0; }
.an-gap-legend-link {
    display: inline-flex; align-items: center; gap: .35rem;
    color: var(--an-primary); font-weight: 600;
    text-decoration: none; transition: color .15s;
}
.an-gap-legend-link:hover { color: var(--an-primary-d); }

/* ===== Anomalies (regroupées par type, filtrables) ===== */

/* Summary : 3 cartes de groupe en haut, cliquables = raccourci filtre */
.an-anom-summary {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem;
    margin-bottom: 1.25rem;
}
.an-anom-summary-card {
    display: flex; align-items: center; gap: .85rem;
    padding: .9rem 1.1rem; border-radius: 12px;
    background: #fafbfc; border: 1px solid var(--an-border);
    text-align: left; cursor: pointer; font-family: inherit;
    transition: all .2s ease;
}
.an-anom-summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(15,23,42,.06);
}
.an-anom-summary-card.is-active {
    border-color: var(--an-primary);
    background: linear-gradient(135deg, rgba(4,83,203,.05), rgba(94,145,222,.03));
    box-shadow: 0 4px 16px rgba(4,83,203,.12);
}
.an-anom-summary-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
    background: rgba(4,83,203,.08); color: var(--an-primary);
}
.an-anom-summary-card--paiements .an-anom-summary-icon { background: rgba(4,83,203,.1); color: var(--an-primary); }
.an-anom-summary-card--recouvrement .an-anom-summary-icon { background: rgba(245,158,11,.12); color: #b45309; }
.an-anom-summary-card--revenus .an-anom-summary-icon { background: rgba(16,185,129,.1); color: #047857; }
.an-anom-summary-body { flex: 1; min-width: 0; }
.an-anom-summary-count strong {
    font-size: 1.4rem; font-weight: 800; color: var(--an-dark);
    line-height: 1; margin-right: .35rem;
}
.an-anom-summary-label {
    font-size: .82rem; font-weight: 600; color: var(--an-text);
}
.an-anom-summary-detail {
    font-size: .72rem; color: var(--an-muted); margin-top: .25rem;
    font-variant-numeric: tabular-nums;
}
.an-anom-summary-detail--ok { color: var(--an-success); font-weight: 600; }

/* Filter chips (sévérité) */
.an-anom-filters {
    display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem;
}
.an-anom-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .4rem .85rem; border-radius: 999px;
    background: #f1f5f9; border: 1px solid transparent;
    font-size: .78rem; font-weight: 600; color: var(--an-text);
    cursor: pointer; transition: all .15s ease;
    font-family: inherit;
}
.an-anom-chip:hover { background: #e2e8f0; }
.an-anom-chip.is-active {
    background: var(--an-primary); color: #fff;
}
.an-anom-chip--critical.is-active { background: var(--an-danger); }
.an-anom-chip--warning.is-active { background: #d97706; }
.an-anom-chip-count {
    background: rgba(255,255,255,.25);
    padding: .05rem .4rem; border-radius: 999px;
    font-size: .7rem; font-weight: 700;
}
.an-anom-chip:not(.is-active) .an-anom-chip-count {
    background: rgba(15,23,42,.08); color: var(--an-muted);
}
.an-anom-chip--reset {
    background: transparent; border-color: var(--an-border);
    color: var(--an-muted);
}
.an-anom-chip--reset:hover { background: #fef2f2; color: var(--an-danger); border-color: rgba(220,38,38,.3); }

/* Groupe : header + liste */
.an-anom-group {
    margin-top: 1.25rem; padding-top: 1.25rem;
    border-top: 1px dashed var(--an-border);
}
.an-anom-group:first-of-type { border-top: none; padding-top: 0; margin-top: 0; }

.an-anom-group-header {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: .65rem; margin-bottom: .85rem;
}
.an-anom-group-title {
    display: inline-flex; align-items: center; gap: .55rem;
    font-size: .95rem; font-weight: 700; color: var(--an-dark);
}
.an-anom-group-title i { color: var(--an-primary); }
.an-anom-group--recouvrement .an-anom-group-title i { color: #b45309; }
.an-anom-group--revenus .an-anom-group-title i { color: #047857; }
.an-anom-group-count {
    background: rgba(15,23,42,.06); color: var(--an-muted);
    padding: .15rem .55rem; border-radius: 999px;
    font-size: .72rem; font-weight: 700;
}
.an-anom-group-meta {
    text-align: right; font-size: .75rem; color: var(--an-muted);
    display: flex; flex-direction: column; gap: .15rem;
}
.an-anom-group-impact {
    color: var(--an-text); font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.an-anom-group-sub { font-style: italic; }

.an-anom-group-ok {
    display: flex; align-items: center; gap: .65rem;
    padding: .85rem 1rem; border-radius: 10px;
    background: rgba(16,185,129,.06);
    border: 1px solid rgba(16,185,129,.2);
    font-size: .85rem; color: #047857;
}
.an-anom-group-ok i { color: var(--an-success); font-size: 1rem; }

/* Items : layout 2-lignes compact */
.an-anom-list { display: flex; flex-direction: column; gap: .5rem; }
.an-anom-item {
    padding: .75rem 1rem; border-radius: 10px;
    border: 1px solid var(--an-border); background: #fff;
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
.an-anom-item:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(15,23,42,.05);
}
.an-anom-item--critical { border-left: 3px solid var(--an-danger); }
.an-anom-item--warning  { border-left: 3px solid #d97706; }
.an-anom-item--info     { border-left: 3px solid var(--an-primary); }

.an-anom-item-meta {
    display: flex; flex-wrap: wrap; align-items: center; gap: .5rem;
    margin-bottom: .35rem;
}
.an-anom-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0;
}
.an-anom-dot--critical { background: var(--an-danger); }
.an-anom-dot--warning  { background: #d97706; }
.an-anom-dot--info     { background: var(--an-primary); }
.an-anom-type {
    font-size: .8rem; font-weight: 700; color: var(--an-text);
}
.an-anom-date {
    display: inline-flex; align-items: center; gap: .25rem;
    font-size: .72rem; color: var(--an-muted);
    font-variant-numeric: tabular-nums;
}
.an-anom-date i { font-size: .68rem; }
.an-anom-score {
    font-size: .72rem; font-weight: 700;
    background: rgba(15,23,42,.06); color: var(--an-text);
    padding: .15rem .55rem; border-radius: 999px;
    font-variant-numeric: tabular-nums;
}
.an-anom-item--critical .an-anom-score { background: rgba(220,38,38,.1); color: var(--an-danger); }
.an-anom-item--warning  .an-anom-score { background: rgba(217,119,6,.12);  color: #b45309; }
.an-anom-sev {
    margin-left: auto;
    font-size: .62rem; font-weight: 700; letter-spacing: .04em;
    padding: .15rem .5rem; border-radius: 4px;
}
.an-anom-sev--critical { background: var(--an-danger); color: #fff; }
.an-anom-sev--warning  { background: #d97706; color: #fff; }
.an-anom-sev--info     { background: var(--an-primary); color: #fff; }

.an-anom-item-body {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
}
.an-anom-msg {
    flex: 1; min-width: 250px;
    font-size: .85rem; color: var(--an-text); line-height: 1.4;
}
.an-anom-link {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .76rem; font-weight: 600; color: var(--an-primary);
    text-decoration: none; flex-shrink: 0;
    padding: .3rem .65rem; border-radius: 8px;
    background: rgba(4,83,203,.08);
    transition: background .15s ease, transform .15s ease;
}
.an-anom-link:hover {
    background: rgba(4,83,203,.16); color: var(--an-primary-d);
    transform: translateX(2px);
}

/* ===== Accuracy banner ===== */
.an-accuracy {
    display: flex; align-items: center; gap: .65rem;
    padding: .75rem 1rem; border-radius: 10px;
    font-size: .85rem; margin-bottom: 1rem;
    border: 1px solid;
}
.an-accuracy i { font-size: 1rem; flex-shrink: 0; }
.an-accuracy strong { font-weight: 700; }
.an-accuracy--excellente { background: rgba(16,185,129,.06); border-color: rgba(16,185,129,.2); color: #047857; }
.an-accuracy--excellente i { color: var(--an-success); }
.an-accuracy--bonne { background: rgba(4,83,203,.05); border-color: rgba(4,83,203,.2); color: var(--an-primary); }
.an-accuracy--bonne i { color: var(--an-primary); }
.an-accuracy--a_surveiller { background: rgba(245,158,11,.06); border-color: rgba(245,158,11,.25); color: #b45309; }
.an-accuracy--a_surveiller i { color: var(--an-warning); }

/* ===== Empty / Alerts ===== */
.an-empty {
    text-align: center; padding: 2.5rem 1rem;
    color: var(--an-muted);
}
.an-empty i { font-size: 2rem; margin-bottom: .75rem; color: var(--an-secondary); }
.an-empty--ok i { color: var(--an-success); }
.an-empty p { margin: 0; font-size: .9rem; }

.an-alert {
    border-radius: 12px; border: none; margin-bottom: 1rem;
}

.an-toast {
    position: fixed; bottom: 24px; right: 24px;
    padding: .85rem 1.25rem; border-radius: 12px;
    background: #fff; box-shadow: 0 8px 30px rgba(15,23,42,.15);
    border: 1px solid var(--an-border);
    display: flex; align-items: center; gap: .65rem;
    font-size: .9rem; z-index: 1000; max-width: 400px;
}
.an-toast--success { border-color: rgba(16,185,129,.3); color: #047857; }
.an-toast--success i { color: var(--an-success); }
.an-toast--error { border-color: rgba(220,38,38,.3); color: var(--an-danger); }
.an-toast--error i { color: var(--an-danger); }
.an-toast--info i { color: var(--an-primary); }
[x-cloak] { display: none !important; }

/* ===== Responsive ===== */
@media (max-width: 992px) {
    .an-cf, .an-risk-grid { grid-template-columns: 1fr; }
    .an-gap-summary { grid-template-columns: repeat(2, 1fr); }
    .an-anom-summary { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .an-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .an-card { padding: 1.25rem 1rem; }
    .an-fallback-banner { flex-direction: column; align-items: flex-start; gap: .75rem; }
    .an-fallback-banner-cta { width: 100%; justify-content: center; }
    .an-risk-grid { grid-template-columns: repeat(2, 1fr); }
    .an-hero h1 { font-size: 1.2rem; }
    .an-kpi { min-width: 140px; }
    .an-gap-summary { grid-template-columns: 1fr; }
    .an-anom-summary { grid-template-columns: 1fr; }
    .an-anom-summary-card { padding: .75rem .85rem; }
    .an-anom-summary-count strong { font-size: 1.15rem; }
    .an-anom-group-header { flex-direction: column; align-items: flex-start; }
    .an-anom-group-meta { text-align: left; }
    .an-anom-item-meta { gap: .35rem; }
    .an-anom-sev { margin-left: 0; }
    .an-anom-msg { min-width: 100%; }
    .an-anom-link { width: 100%; justify-content: center; }
    .an-gap-rows { padding: .85rem .75rem; gap: .85rem; }
    .an-gap-row {
        grid-template-columns: 1fr;
        gap: .35rem;
    }
    .an-gap-row-month {
        font-size: .9rem;
        display: flex; align-items: baseline; justify-content: space-between;
    }
    .an-gap-row-track { height: 24px; }
    .an-gap-row-fill-label { font-size: .75rem; left: .5rem; }
    .an-gap-row-amounts { font-size: .75rem; text-align: left; }
    .an-gap-legend { font-size: .72rem; gap: .65rem; }
}
</style>
@endpush
