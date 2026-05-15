{{-- Tab Suivi heures BTS pour emploi-temps/show (fallback non-LMD) --}}
{{-- Recoit : $classe, $planningMatiere, $kpiTaux --}}
@php
    $kpiTauxColor = $kpiTaux >= 70 ? '#10b981' : ($kpiTaux >= 30 ? '#f59e0b' : '#dc2626');
@endphp

<div class="sh-bts">
    {{-- KPIs --}}
    <div class="cs-planning-kpis">
        <div class="cs-planning-kpi" style="--kpi-color:#0453cb;">
            <div class="cs-planning-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="cs-planning-kpi-value">{{ number_format($planningMatiere['stats']['heures_planifiees'] ?? 0, 1) }}h</div>
            <div class="cs-planning-kpi-label">Planifiées</div>
        </div>
        <div class="cs-planning-kpi" style="--kpi-color:#10b981;">
            <div class="cs-planning-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="cs-planning-kpi-value">{{ number_format($planningMatiere['stats']['heures_realisees'] ?? 0, 1) }}h</div>
            <div class="cs-planning-kpi-label">Réalisées</div>
        </div>
        <div class="cs-planning-kpi" style="--kpi-color:#3b7ddb;">
            <div class="cs-planning-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <div class="cs-planning-kpi-value">{{ $planningMatiere['stats']['nb_seances'] ?? 0 }}</div>
            <div class="cs-planning-kpi-label">Séances</div>
        </div>
        <div class="cs-planning-kpi" style="--kpi-color:{{ $kpiTauxColor }};">
            <div class="cs-planning-kpi-icon"><i class="fas fa-chart-pie"></i></div>
            <div class="cs-planning-kpi-value" style="color:{{ $kpiTauxColor }};">{{ $kpiTaux }}%</div>
            <div class="cs-planning-kpi-label">Taux</div>
        </div>
    </div>

    {{-- Liste matieres avec progression --}}
    @if(!empty($planningMatiere['matieres']) && $planningMatiere['matieres']->isNotEmpty())
        <div class="sh-bts-list" style="margin-top:1.25rem;">
            @foreach($planningMatiere['matieres'] as $item)
                @php
                    $pct = min($item['pourcentage_realise'] ?? 0, 100);
                    $barColor = $pct >= 100 ? '#10b981' : ($pct >= 70 ? '#0453cb' : ($pct >= 30 ? '#f59e0b' : '#dc2626'));
                @endphp
                <div class="sh-bts-matiere-card">
                    <div class="sh-bts-matiere-header">
                        <div class="sh-bts-matiere-info">
                            <span class="sh-bts-matiere-name">{{ $item['matiere']->name ?? 'Matière inconnue' }}</span>
                            @if(!empty($item['matiere']->code))
                                <span class="sh-bts-matiere-code">{{ $item['matiere']->code }}</span>
                            @endif
                        </div>
                        <div class="sh-bts-matiere-hours">
                            <strong>{{ number_format($item['heures_realisees'], 1) }}h</strong>
                            <span class="sh-bts-matiere-hours-muted">/ {{ number_format($item['heures_planifiees'], 1) }}h</span>
                        </div>
                    </div>
                    <div class="sh-bts-bar-wrap">
                        <div class="sh-bts-bar" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                    </div>
                    <div class="sh-bts-matiere-footer">
                        <small>
                            <i class="fas fa-clock"></i>{{ number_format($item['heures_restantes'], 1) }}h restantes · {{ $item['nb_seances'] }} séances
                        </small>
                        <span class="sh-bts-pct-badge" style="background:rgba({{ $pct >= 70 ? '16,185,129' : ($pct >= 30 ? '245,158,11' : '220,38,38') }},.12);color:{{ $barColor }};border:1px solid rgba({{ $pct >= 70 ? '16,185,129' : ($pct >= 30 ? '245,158,11' : '220,38,38') }},.3);">{{ $pct }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="cs-empty" style="margin-top:1.5rem;">
            <div class="cs-empty-icon"><i class="fas fa-calendar-times"></i></div>
            <div class="cs-empty-title">Aucune donnée de suivi</div>
            <div class="cs-empty-text">Aucune planification ou séance trouvée pour cette classe.</div>
        </div>
    @endif
</div>

@push('styles')
<style>
.sh-bts-list { display: flex; flex-direction: column; gap: .65rem; }
.sh-bts-matiere-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; transition: all .15s; }
.sh-bts-matiere-card:hover { box-shadow: 0 4px 16px rgba(4,83,203,.06); border-color: rgba(4,83,203,.2); }
.sh-bts-matiere-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: .5rem; }
.sh-bts-matiere-info { display: flex; align-items: center; gap: .55rem; min-width: 0; flex: 1; }
.sh-bts-matiere-name { font-size: .92rem; font-weight: 700; color: #1e293b; }
.sh-bts-matiere-code { font-family: 'Courier New', monospace; font-size: .68rem; font-weight: 700; color: #0453cb; background: rgba(4,83,203,.08); padding: .15rem .45rem; border-radius: 4px; }
.sh-bts-matiere-hours { font-size: .85rem; color: #64748b; white-space: nowrap; }
.sh-bts-matiere-hours strong { font-size: 1.05rem; color: #0f172a; font-weight: 700; }
.sh-bts-matiere-hours-muted { color: #94a3b8; }
.sh-bts-bar-wrap { background: #e2e8f0; border-radius: 99px; height: 5px; overflow: hidden; }
.sh-bts-bar { height: 100%; border-radius: 99px; transition: width .3s ease; }
.sh-bts-matiere-footer { display: flex; align-items: center; justify-content: space-between; margin-top: .45rem; }
.sh-bts-matiere-footer small { color: #64748b; font-size: .72rem; display: inline-flex; align-items: center; gap: .35rem; }
.sh-bts-pct-badge { font-size: .7rem; font-weight: 700; padding: .15rem .5rem; border-radius: 5px; }
</style>
@endpush
