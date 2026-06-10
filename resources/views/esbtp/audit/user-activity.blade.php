@extends('layouts.app')

@section('title', 'Surveillance activité utilisateurs')

@php
    $eventLabels = [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
        'retrieved' => 'Consultation',
    ];
    $maxHourly = !empty($hourlyDistribution) ? max($hourlyDistribution) : 1;
    if ($maxHourly === 0) $maxHourly = 1;
@endphp

@section('content')
<div class="container-fluid au-page">

    {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
    <div class="au-hero au-hero--activity">
        <div class="au-hero-top">
            <div class="au-hero-left">
                <div class="au-hero-icon"><i class="fas fa-user-clock"></i></div>
                <div class="au-hero-info">
                    <h1>Surveillance activité utilisateurs</h1>
                    <p>
                        @if($selectedUser)
                            Activité de <strong>{{ $selectedUser->name }}</strong>
                        @else
                            Vue d'ensemble de l'activité de tous les utilisateurs
                        @endif
                        — du {{ $dateFrom->format('d/m/Y') }} au {{ $dateTo->format('d/m/Y') }}
                    </p>
                </div>
            </div>
            <div class="au-hero-actions">
                <a href="{{ route('esbtp.audit.index') }}" class="au-btn au-btn--glass">
                    <i class="fas fa-arrow-left"></i> Journal d'audit
                </a>
                @if($selectedUser)
                    <a href="{{ route('esbtp.audit.user-activity') }}" class="au-btn au-btn--white">
                        <i class="fas fa-times"></i> Vue globale
                    </a>
                @endif
            </div>
        </div>

        <div class="au-kpis">
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-bolt"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($stats['total_actions']) }}</div>
                    <div class="au-kpi-label">Actions totales</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($stats['unique_users']) }}</div>
                    <div class="au-kpi-label">Utilisateurs actifs</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-network-wired"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($stats['unique_ips']) }}</div>
                    <div class="au-kpi-label">Adresses IP uniques</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="far fa-clock"></i></div>
                <div>
                    <div class="au-kpi-value">{{ $stats['peak_hour'] }}</div>
                    <div class="au-kpi-label">Heure de pointe</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ FILTRES ═══════════════════════════════ --}}
    <div class="au-filters">
        <form action="{{ route('esbtp.audit.user-activity') }}" method="GET" class="au-filters-row">
            <x-au-user-picker
                class="au-filter-grow"
                name="user_id"
                :value="$selectedUser?->id"
                :users="$users"
                placeholder="— Tous les utilisateurs —"
                :submit-on-change="true" />
            <div class="au-filter-field">
                <label><i class="fas fa-calendar"></i></label>
                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}">
            </div>
            <div class="au-filter-field">
                <label><i class="fas fa-calendar"></i></label>
                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}">
            </div>
            <button type="submit" class="au-btn au-btn--primary">
                <i class="fas fa-filter"></i> Filtrer
            </button>
        </form>
    </div>

    <div class="au-grid-2col">

        {{-- ═══════════════════════════════ HEURES DE POINTE ═══════════════════════════════ --}}
        <div class="au-card">
            <div class="au-card-header">
                <div class="au-card-title"><i class="fas fa-chart-bar"></i> Heures de pointe</div>
                <span class="au-card-meta">Distribution sur 24h</span>
            </div>
            <div class="au-card-body">
                <div class="au-hours-grid">
                    @for($h = 0; $h < 24; $h++)
                        @php
                            $val = $hourlyDistribution[$h] ?? 0;
                            $pct = $maxHourly > 0 ? round(($val / $maxHourly) * 100) : 0;
                            $isPeak = ($h == array_search(max($hourlyDistribution), $hourlyDistribution));
                        @endphp
                        <div class="au-hour-bar {{ $isPeak ? 'au-hour-bar--peak' : '' }}" title="{{ sprintf('%02dh', $h) }} : {{ $val }} actions">
                            <div class="au-hour-bar-fill" style="height: {{ $pct }}%;"></div>
                            <div class="au-hour-bar-label">{{ sprintf('%02d', $h) }}</div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════ TOP MODÈLES ═══════════════════════════════ --}}
        <div class="au-card">
            <div class="au-card-header">
                <div class="au-card-title"><i class="fas fa-cubes"></i> Top entités touchées</div>
                <span class="au-card-meta">5 plus fréquentes</span>
            </div>
            <div class="au-card-body">
                @if($topModels->isEmpty())
                    <div class="au-empty">Aucune activité sur la période</div>
                @else
                    <ul class="au-rank-list">
                        @foreach($topModels as $i => $m)
                            <li class="au-rank-item">
                                <span class="au-rank-num">{{ $i + 1 }}</span>
                                <span class="au-rank-label">{{ $m['label'] }}</span>
                                <span class="au-rank-count">{{ number_format($m['count']) }}</span>
                                <div class="au-rank-track">
                                    <div class="au-rank-fill" style="width: {{ ($m['count'] / max($topModels[0]['count'], 1)) * 100 }}%;"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════════════ TOP IPs ═══════════════════════════════ --}}
        <div class="au-card">
            <div class="au-card-header">
                <div class="au-card-title"><i class="fas fa-globe"></i> Top adresses IP</div>
                <span class="au-card-meta">Origines les plus fréquentes</span>
            </div>
            <div class="au-card-body">
                @if($topIps->isEmpty())
                    <div class="au-empty">Aucune IP enregistrée</div>
                @else
                    <ul class="au-rank-list au-rank-list--mono">
                        @foreach($topIps as $i => $ip)
                            <li class="au-rank-item">
                                <span class="au-rank-num">{{ $i + 1 }}</span>
                                <span class="au-rank-label au-rank-label--mono">{{ $ip->ip_address }}</span>
                                <span class="au-rank-count">{{ number_format($ip->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════════════ ALERTES SUSPECTES ═══════════════════════════════ --}}
        @php
            $suspicious = $stats['suspicious_activities'] ?? 0;
            $suspiciousCount = is_array($suspicious) ? count($suspicious) : (int) $suspicious;
        @endphp
        <div class="au-card">
            <div class="au-card-header">
                <div class="au-card-title"><i class="fas fa-exclamation-triangle"></i> Activités suspectes</div>
                <span class="au-card-meta">{{ $suspiciousCount }} détectée(s)</span>
            </div>
            <div class="au-card-body">
                @if($suspiciousCount === 0)
                    <div class="au-empty au-empty--success">
                        <i class="fas fa-check-circle"></i> Aucune activité suspecte détectée
                    </div>
                @elseif(is_array($suspicious))
                    <ul class="au-suspicious-list">
                        @foreach($suspicious as $sus)
                            <li class="au-suspicious-item">
                                <i class="fas fa-flag text-danger"></i>
                                <span>{{ is_array($sus) ? ($sus['description'] ?? json_encode($sus)) : $sus }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="au-suspicious-summary">
                        <i class="fas fa-flag text-warning"></i>
                        <strong>{{ $suspiciousCount }}</strong> action(s) hors heures bureau (avant 08h, après 18h) ou suppressions/restaurations sur la période.
                        <a href="{{ route('esbtp.audit.index') }}?event=deleted" class="au-suspicious-link">Voir le détail →</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ TIMELINE DÉTAILLÉE ═══════════════════════════════ --}}
    <div class="au-card">
        <div class="au-card-header">
            <div class="au-card-title">
                <i class="fas fa-history"></i> Chronologie d'activité
                @if($selectedUser)
                    de <strong>{{ $selectedUser->name }}</strong>
                @endif
            </div>
            <span class="au-card-meta">{{ $activities->total() }} action(s)</span>
        </div>
        <div class="au-card-body">
            @if($activities->isEmpty())
                <div class="au-empty"><i class="fas fa-inbox"></i> Aucune action sur la période</div>
            @else
                <div class="au-timeline" x-data="{ openIds: [] }">
                    @php $lastDay = null; @endphp
                    @foreach($activities as $audit)
                        @php
                            $day = $audit->created_at->format('Y-m-d');
                            $event = $audit->event;
                            $modelLabel = \App\Helpers\EntityLabelHelper::for($audit->auditable_type);
                            $rowLinks = $entityLinksMap[$audit->id] ?? [];
                            $rowLinksCount = count($rowLinks);
                        @endphp
                        @if($day !== $lastDay)
                            <div class="au-timeline-day">
                                <i class="far fa-calendar"></i>
                                {{ $audit->created_at->translatedFormat('l j F Y') }}
                            </div>
                            @php $lastDay = $day; @endphp
                        @endif
                        <div class="au-timeline-item au-timeline-item--{{ $event }}">
                            <div class="au-timeline-time">{{ $audit->created_at->format('H:i:s') }}</div>
                            <div class="au-timeline-dot"></div>
                            <div class="au-timeline-content">
                                <div class="au-timeline-meta">
                                    @if(!$selectedUser)
                                        <strong>{{ $audit->user?->name ?? 'Système' }}</strong>
                                    @endif
                                    <span class="au-chip au-chip--{{ $event }}">{{ $eventLabels[$event] ?? $event }}</span>
                                    <span class="au-chip au-chip--neutral">{{ $modelLabel }} #{{ $audit->auditable_id }}</span>
                                    @if($audit->ip_address)
                                        <span class="au-timeline-ip"><i class="fas fa-network-wired"></i> {{ $audit->ip_address }}</span>
                                    @endif
                                </div>
                                <div class="au-timeline-actions-row">
                                    @if($rowLinksCount > 0)
                                        <button type="button" class="au-links-pill au-links-pill--sm"
                                                @click="openIds.includes({{ $audit->id }}) ? openIds = openIds.filter(i => i !== {{ $audit->id }}) : openIds.push({{ $audit->id }})">
                                            <i class="fas fa-project-diagram"></i>
                                            <span x-show="!openIds.includes({{ $audit->id }})">{{ $rowLinksCount }} lien{{ $rowLinksCount > 1 ? 's' : '' }}</span>
                                            <span x-show="openIds.includes({{ $audit->id }})" x-cloak>Replier</span>
                                            <i class="fas fa-chevron-down au-toggle-caret" :class="openIds.includes({{ $audit->id }}) ? 'au-toggle-caret--open' : ''"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('esbtp.audit.show', $audit->id) }}" class="au-timeline-link">
                                        Détail <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                                @if($rowLinksCount > 0)
                                    <div class="au-timeline-links" x-show="openIds.includes({{ $audit->id }})" x-cloak x-transition.opacity>
                                        <x-audit-links :links="$rowLinks" :compact="true" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="au-pagination">
                    {{ $activities->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
@include('esbtp.audit._styles')

/* Page-specific styles for user-activity */
.au-hero--activity { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #6366f1 100%); }

/* Cards & grid */
.au-grid-2col { display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.25rem; }
@media (max-width: 992px) { .au-grid-2col { grid-template-columns: 1fr; } }
.au-card { margin-bottom:1.25rem; }
.au-card-meta { font-size:.78rem; color:#64748b; }

/* Heures de pointe — bar chart vertical 24 colonnes */
.au-hours-grid { display:grid; grid-template-columns: repeat(24, 1fr); gap:3px; height: 160px; align-items:end; }
.au-hour-bar { height:100%; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; position:relative; }
.au-hour-bar-fill { width:100%; background: linear-gradient(180deg, #3b7ddb, #0453cb); border-radius: 3px 3px 0 0; min-height:3px; transition: height .3s ease; }
.au-hour-bar--peak .au-hour-bar-fill { background: linear-gradient(180deg, #f59e0b, #ea580c); }
.au-hour-bar-label { font-size:.62rem; color:#64748b; margin-top:.3rem; }

/* Rank list */
.au-rank-list { list-style:none; padding:0; margin:0; }
.au-rank-item { display:grid; grid-template-columns: auto 1fr auto; align-items:center; gap:.75rem; padding:.6rem 0; border-bottom:1px solid #f1f5f9; position:relative; }
.au-rank-item:last-child { border-bottom:none; }
.au-rank-num { width:28px;height:28px;border-radius:8px; background:#eff6ff; color:#0453cb; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.8rem; }
.au-rank-label { font-size:.88rem; color:#1e293b; font-weight:500; }
.au-rank-label--mono { font-family: 'SFMono-Regular', Consolas, monospace; }
.au-rank-count { font-weight:700; color:#0453cb; font-size:.95rem; }
.au-rank-track { grid-column: 2 / 3; height:3px; background:#f1f5f9; border-radius:2px; margin-top:.25rem; overflow:hidden; }
.au-rank-fill { height:100%; background: linear-gradient(90deg, #3b7ddb, #0453cb); border-radius:2px; }

/* Suspicious */
.au-empty--success { background:#ecfdf5; color:#065f46; }
.au-empty--success i { opacity:1; }
.au-suspicious-list { list-style:none; padding:0; margin:0; }
.au-suspicious-item { display:flex; align-items:center; gap:.75rem; padding:.65rem .85rem; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; margin-bottom:.5rem; color:#991b1b; font-size:.88rem; }
.au-suspicious-summary { background:#fefce8; border:1px solid #fde68a; border-radius:10px; padding:.85rem 1rem; color:#92400e; font-size:.9rem; display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; }
.au-suspicious-summary strong { color:#b45309; }
.au-suspicious-link { margin-left:auto; color:#b45309; font-weight:600; text-decoration:none; }
.au-suspicious-link:hover { color:#78350f; text-decoration:underline; }

/* Timeline (variant grid pour activity) */
.au-timeline { position:relative; padding-left: .5rem; }
.au-timeline-day { display:flex; align-items:center; gap:.5rem; margin: 1rem 0 .75rem; padding-bottom:.5rem; border-bottom: 2px solid #e2e8f0; color:#0f172a; font-weight:600; font-size:.85rem; text-transform: capitalize; }
.au-timeline-day i { color:#0453cb; }
.au-timeline-item { display:grid; grid-template-columns: 80px 16px 1fr; gap:1rem; padding: .6rem 0; align-items:start; border-bottom: none; }
.au-timeline-time { font-size:.78rem; color:#64748b; font-family: monospace; padding-top:.15rem; }
.au-timeline-dot { width:12px; height:12px; border-radius:50%; background:#cbd5e1; margin-top:.4rem; border: 2px solid #fff; box-shadow: 0 0 0 2px #cbd5e1; }
.au-timeline-item--created .au-timeline-dot { background:#10b981; box-shadow: 0 0 0 2px #10b981; }
.au-timeline-item--updated .au-timeline-dot { background:#3b82f6; box-shadow: 0 0 0 2px #3b82f6; }
.au-timeline-item--deleted .au-timeline-dot { background:#ef4444; box-shadow: 0 0 0 2px #ef4444; }
.au-timeline-item--restored .au-timeline-dot { background:#f59e0b; box-shadow: 0 0 0 2px #f59e0b; }
.au-timeline-content { padding-bottom:.5rem; border-bottom:1px solid #f8fafc; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
.au-timeline-meta { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; font-size:.85rem; color:#475569; }
.au-timeline-ip { font-family: monospace; font-size:.75rem; color:#64748b; }
.au-timeline-link { font-size:.78rem; color:#0453cb; font-weight:600; text-decoration:none; padding:.25rem .65rem; border-radius:6px; background:#eff6ff; }
.au-timeline-link:hover { background:#dbeafe; color:#033a8e; }

@media (max-width: 768px) {
    .au-timeline-item { grid-template-columns: 60px 16px 1fr; gap: .65rem; }
    .au-hours-grid { gap: 2px; height: 120px; }
    .au-hour-bar-label { font-size: .55rem; }
}
</style>
@endpush

@endsection
