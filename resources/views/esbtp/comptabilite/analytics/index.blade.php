@extends('layouts.app')

@section('title', 'Analytics Prédictifs')

@section('content')
@php
    $selectedAnnee = $context->anneeId ? $annees->firstWhere('id', $context->anneeId) : null;
    $selectedFiliere = $context->filiereId ? $filieres->firstWhere('id', $context->filiereId) : null;
    $selectedClasse = $context->classeId ? $classes->firstWhere('id', $context->classeId) : null;
    $criticalCount = collect($anomalies)->where('severity', \App\Domain\Analytics\DTOs\AnomalyAlert::SEVERITY_CRITICAL)->count();
    $warningCount = collect($anomalies)->where('severity', \App\Domain\Analytics\DTOs\AnomalyAlert::SEVERITY_WARNING)->count();
    $scopePills = array_filter([
        ['label' => 'Année', 'value' => $selectedAnnee?->name ?? 'Toutes'],
        ['label' => 'Filière', 'value' => $selectedFiliere?->name ?? 'Toutes'],
        ['label' => 'Classe', 'value' => $selectedClasse?->name ?? 'Toutes'],
    ]);
@endphp
<div class="container-fluid an-page" x-data="analyticsPage()">

    {{-- ============================ HERO ============================ --}}
    <div class="an-hero">
        <div class="an-hero-grid">
            <div class="an-hero-copy">
                <div class="an-hero-kicker">
                    <span class="an-hero-kicker-dot"></span>
                    Pilotage financier
                </div>
                <div class="an-hero-left">
                    <div class="an-hero-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <h1>Analytics Prédictifs</h1>
                        <p>Cash flow, risque de défaut et anomalies, dans une seule vue de décision.</p>
                    </div>
                </div>
                <div class="an-scope-pills">
                    @foreach($scopePills as $pill)
                        <div class="an-scope-pill">
                            <span>{{ $pill['label'] }}</span>
                            <strong>{{ $pill['value'] }}</strong>
                        </div>
                    @endforeach
                    <div class="an-scope-pill an-scope-pill--soft">
                        <span>Dernier calcul</span>
                        <strong>{{ $lastComputedAt ? $lastComputedAt->locale('fr')->diffForHumans() : 'Jamais' }}</strong>
                    </div>
                </div>
            </div>

            <div class="an-hero-panel">
                <div class="an-hero-panel-top">
                    <span class="an-hero-panel-title">Synthèse instantanée</span>
                    <span class="an-hero-panel-badge">Temps réel</span>
                </div>
                <div class="an-hero-primary">
                    <div class="an-hero-primary-value">
                        @if($cashFlow->isAvailable())
                            {{ number_format($cashFlow->value, 0, ',', ' ') }} <span>FCFA</span>
                        @else
                            N/D
                        @endif
                    </div>
                    <div class="an-hero-primary-label">Recettes prévues le mois prochain</div>
                    <div class="an-hero-primary-meta">
                        @if($cashFlow->confidenceInterval)
                            Intervalle 95% : {{ number_format($cashFlow->confidenceInterval->lower, 0, ',', ' ') }} à {{ number_format($cashFlow->confidenceInterval->upper, 0, ',', ' ') }} FCFA
                        @else
                            Prévision indicative
                        @endif
                    </div>
                </div>
                <div class="an-hero-stats">
                    <div class="an-hero-stat">
                        <div class="an-hero-stat-value">
                            @if($defaultRisk->isAvailable())
                                {{ (int) $defaultRisk->value }}
                            @else
                                N/D
                            @endif
                        </div>
                        <div class="an-hero-stat-label">Étudiants à haut risque</div>
                    </div>
                    <div class="an-hero-stat">
                        <div class="an-hero-stat-value">{{ $criticalCount }}</div>
                        <div class="an-hero-stat-label">Alertes critiques</div>
                    </div>
                    <div class="an-hero-stat">
                        <div class="an-hero-stat-value">{{ $warningCount }}</div>
                        <div class="an-hero-stat-label">Alertes secondaires</div>
                    </div>
                    <div class="an-hero-stat">
                        <div class="an-hero-stat-value">
                            @if($lastComputedAt)
                                {{ $lastComputedAt->locale('fr')->format('H:i') }}
                            @else
                                --
                            @endif
                        </div>
                        <div class="an-hero-stat-label">Dernier run</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="an-hero-bar">
            <div class="an-hero-actions">
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
            <div class="an-hero-links">
                <a href="#cash-flow">Cash flow</a>
                <a href="#risk">Risque</a>
                <a href="#anomalies">Anomalies</a>
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

    {{-- ============================ CASH FLOW ============================ --}}
    <div class="an-card an-section-panel mt-4" id="cash-flow">
        <div class="an-section-header">
            <div class="an-section-icon"><i class="fas fa-chart-area"></i></div>
            <div>
                <h2 class="an-section-title">Projection cash-flow — mois prochain</h2>
                <p class="an-section-sub">Modèle saisonnier (Holt-Winters) + régression linéaire sur 24 mois d'historique.</p>
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

    {{-- ============================ DEFAULT RISK ============================ --}}
    <div class="an-card an-section-panel mt-4" id="risk">
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
                <h3 class="an-subtitle mt-4">Top {{ count($topAtRisk) }} étudiants prioritaires</h3>
                <div class="table-responsive">
                    <table class="table table-modern an-risk-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Classe</th>
                                <th class="text-end">Solde restant</th>
                                <th class="text-center">Retard</th>
                                <th class="text-center">% payé</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Niveau</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topAtRisk as $student)
                                <tr>
                                    <td><strong>{{ $student['etudiant_nom'] }}</strong></td>
                                    <td>{{ $student['classe_nom'] }}</td>
                                    <td class="text-end">{{ number_format($student['solde_restant'], 0, ',', ' ') }} FCFA</td>
                                    <td class="text-center">
                                        @if($student['jours_retard'] > 0)
                                            <span class="an-chip an-chip--retard">{{ $student['jours_retard'] }} j</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($student['ratio_paye'] * 100, 0) }}%</td>
                                    <td class="text-center">{{ number_format($student['score'], 2) }}</td>
                                    <td class="text-center">
                                        <span class="an-level an-level--{{ $student['level'] }}">{{ ucfirst($student['level']) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="an-empty">
                <i class="fas fa-info-circle"></i>
                <p>{{ $defaultRisk->explanation[0] ?? 'Analyse de risque indisponible.' }}</p>
            </div>
        @endif
    </div>

    {{-- ============================ ANOMALIES ============================ --}}
    <div class="an-card an-section-panel mt-4" id="anomalies">
        <div class="an-section-header">
            <div class="an-section-icon"><i class="fas fa-radiation"></i></div>
            <div>
                <h2 class="an-section-title">Anomalies financières détectées</h2>
                <p class="an-section-sub">Z-score sur les flux mensuels + détection d'outliers sur les paiements des 30 derniers jours.</p>
            </div>
        </div>

        @if(empty($anomalies))
            <div class="an-empty an-empty--ok">
                <i class="fas fa-check-circle"></i>
                <p>Aucune anomalie détectée. Les flux financiers sont conformes aux tendances historiques.</p>
            </div>
        @else
            <div class="an-anomalies">
                @foreach($anomalies as $alert)
                    <div class="an-anomaly an-anomaly--{{ $alert->severity }}">
                        <div class="an-anomaly-icon">
                            @if($alert->severity === 'critical')
                                <i class="fas fa-exclamation-circle"></i>
                            @elseif($alert->severity === 'warning')
                                <i class="fas fa-exclamation-triangle"></i>
                            @else
                                <i class="fas fa-info-circle"></i>
                            @endif
                        </div>
                        <div class="an-anomaly-body">
                            <div class="an-anomaly-meta">
                                <span class="an-anomaly-type">{{ str_replace('_', ' ', $alert->type) }}</span>
                                <span class="an-anomaly-severity an-anomaly-severity--{{ $alert->severity }}">
                                    {{ strtoupper($alert->severity) }}
                                </span>
                            </div>
                            <div class="an-anomaly-message">{{ $alert->message }}</div>
                            <div class="an-anomaly-score">Score d'écart : {{ number_format($alert->score, 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
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

/* ===== Hero ===== */
.an-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
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
}
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
    font-size: 2rem; font-weight: 700; color: var(--an-primary);
    line-height: 1.1;
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

/* ===== Premium dashboard overrides ===== */
.an-page {
    padding: 1rem 0 1.5rem;
    position: relative;
    background:
        linear-gradient(180deg, rgba(4,83,203,.04), rgba(255,255,255,0) 220px),
        linear-gradient(135deg, rgba(15,23,42,.02), rgba(255,255,255,0) 55%);
}
.an-page::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image: linear-gradient(rgba(148,163,184,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,.08) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(180deg, rgba(0,0,0,.24), transparent 85%);
    opacity: .35;
}
.an-hero {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    color: #fff;
    background:
        linear-gradient(135deg, rgba(5,39,102,.96), rgba(4,83,203,.94) 46%, rgba(20,116,226,.92)),
        linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,0));
    box-shadow: 0 18px 40px rgba(4,83,203,.18);
    border: 1px solid rgba(255,255,255,.14);
}
.an-hero::after {
    content: '';
    position: absolute;
    inset: auto -15% -35% auto;
    width: 320px;
    height: 320px;
    background: linear-gradient(135deg, rgba(255,255,255,.15), rgba(255,255,255,0));
    transform: rotate(18deg);
    pointer-events: none;
    clip-path: polygon(0 0, 100% 0, 100% 100%);
}
.an-hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, .95fr);
    gap: 1rem;
    position: relative;
    z-index: 1;
}
.an-hero-copy {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 1rem;
}
.an-hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: rgba(255,255,255,.75);
}
.an-hero-kicker-dot {
    width: .45rem;
    height: .45rem;
    border-radius: 999px;
    background: #9be3ff;
    box-shadow: 0 0 0 6px rgba(155,227,255,.12);
}
.an-hero-left { display: flex; align-items: center; gap: 1rem; }
.an-hero-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.16);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.an-hero h1 { font-size: 1.55rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: 0; }
.an-hero p { color: rgba(255,255,255,.78); font-size: .92rem; margin: .25rem 0 0; }
.an-scope-pills { display: flex; flex-wrap: wrap; gap: .65rem; }
.an-scope-pill {
    min-width: 160px;
    padding: .75rem .9rem;
    border-radius: 14px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    backdrop-filter: blur(10px);
}
.an-scope-pill span {
    display: block;
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: rgba(255,255,255,.62);
}
.an-scope-pill strong {
    display: block;
    margin-top: .2rem;
    font-size: .9rem;
    font-weight: 700;
    color: #fff;
}
.an-scope-pill--soft { background: rgba(255,255,255,.06); }
.an-hero-panel {
    padding: 1rem;
    border-radius: 18px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.14);
    backdrop-filter: blur(12px);
}
.an-hero-panel-top {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    margin-bottom: .85rem;
}
.an-hero-panel-title {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: rgba(255,255,255,.72);
}
.an-hero-panel-badge {
    padding: .25rem .55rem;
    border-radius: 999px;
    background: rgba(255,255,255,.14);
    color: #fff;
    font-size: .68rem;
    font-weight: 700;
}
.an-hero-primary {
    padding: 1rem;
    border-radius: 16px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.12);
}
.an-hero-primary-value { font-size: 2rem; font-weight: 800; line-height: 1; color: #fff; }
.an-hero-primary-value span { font-size: .95rem; font-weight: 600; opacity: .75; }
.an-hero-primary-label { margin-top: .5rem; font-size: .84rem; font-weight: 600; color: rgba(255,255,255,.82); }
.an-hero-primary-meta { margin-top: .35rem; font-size: .76rem; color: rgba(255,255,255,.68); }
.an-hero-stats { margin-top: .85rem; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
.an-hero-stat {
    padding: .8rem .85rem;
    border-radius: 14px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.12);
}
.an-hero-stat-value { font-size: 1rem; font-weight: 800; color: #fff; line-height: 1.1; }
.an-hero-stat-label { margin-top: .18rem; font-size: .72rem; color: rgba(255,255,255,.68); }
.an-hero-bar {
    margin-top: 1rem;
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}
.an-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.an-hero-links { display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
.an-hero-links a {
    text-decoration: none;
    color: rgba(255,255,255,.8);
    font-size: .75rem;
    font-weight: 700;
    padding: .45rem .7rem;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.08);
}
.an-hero-links a:hover { color: #fff; background: rgba(255,255,255,.14); }
.an-card {
    background: rgba(255,255,255,.92);
    border: 1px solid rgba(226,232,240,.9);
    border-radius: 18px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 10px 24px rgba(15,23,42,.05);
    backdrop-filter: blur(8px);
}
.an-section-panel { position: relative; }
.an-section-panel::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    border-radius: 18px 0 0 18px;
    background: linear-gradient(180deg, var(--an-primary), var(--an-secondary));
    opacity: .9;
}
.an-section-title { font-size: 1.1rem; font-weight: 800; color: var(--an-dark); margin: 0; }
/* ===== Anomalies ===== */
.an-anomalies { display: flex; flex-direction: column; gap: .75rem; }
.an-anomaly {
    display: flex; gap: 1rem; padding: 1rem 1.25rem;
    border-radius: 12px; border: 1px solid var(--an-border);
    background: #fafbfc;
}
.an-anomaly--critical { border-color: rgba(220,38,38,.3); background: rgba(220,38,38,.03); }
.an-anomaly--warning { border-color: rgba(245,158,11,.3); background: rgba(245,158,11,.03); }
.an-anomaly--info { border-color: rgba(4,83,203,.2); background: rgba(4,83,203,.03); }

.an-anomaly-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.an-anomaly--critical .an-anomaly-icon { background: rgba(220,38,38,.15); color: var(--an-danger); }
.an-anomaly--warning .an-anomaly-icon { background: rgba(245,158,11,.15); color: var(--an-warning); }
.an-anomaly--info .an-anomaly-icon { background: rgba(4,83,203,.15); color: var(--an-primary); }

.an-anomaly-body { flex: 1; }
.an-anomaly-meta {
    display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem;
}
.an-anomaly-type {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; color: var(--an-muted);
}
.an-anomaly-severity {
    padding: .15rem .55rem; border-radius: 999px;
    font-size: .65rem; font-weight: 700; letter-spacing: .04em;
}
.an-anomaly-severity--critical { background: rgba(220,38,38,.15); color: var(--an-danger); }
.an-anomaly-severity--warning { background: rgba(245,158,11,.15); color: #b45309; }
.an-anomaly-severity--info { background: rgba(4,83,203,.15); color: var(--an-primary); }

.an-anomaly-message {
    font-size: .92rem; color: var(--an-text); margin-bottom: .35rem;
    line-height: 1.4;
}
.an-anomaly-score {
    font-size: .75rem; color: var(--an-muted); font-style: italic;
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
}
@media (max-width: 768px) {
    .an-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .an-card { padding: 1.25rem 1rem; }
    .an-risk-grid { grid-template-columns: repeat(2, 1fr); }
    .an-hero h1 { font-size: 1.2rem; }
    .an-kpi { min-width: 140px; }
}
</style>
@endpush
