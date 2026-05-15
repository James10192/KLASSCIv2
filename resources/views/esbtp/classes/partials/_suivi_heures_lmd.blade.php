{{-- Partial LMD pour la tab "Suivi des heures" de classes.show --}}
{{-- Recoit : $classe, $planningMatiere, $lmdVolumeBudget, $lmdUesAvecEcues, $lmdSemestres, $periode, $kpiTaux --}}
@php
    $vbTotalsTab = ['cm'=>['p'=>0,'r'=>0],'td'=>['p'=>0,'r'=>0],'tp'=>['p'=>0,'r'=>0]];
    foreach ($lmdVolumeBudget as $budget) {
        foreach (['cm','td','tp'] as $k) {
            $vbTotalsTab[$k]['p'] += (float) ($budget[$k]['planifie'] ?? 0);
            $vbTotalsTab[$k]['r'] += (float) ($budget[$k]['realise'] ?? 0);
        }
    }
    $vbLabelsTab = ['cm'=>'Cours Magistral','td'=>'Travaux Dirigés','tp'=>'Travaux Pratiques'];
    $vbIconsTab  = ['cm'=>'fa-chalkboard-user','td'=>'fa-pen-ruler','tp'=>'fa-flask-vial'];

    $totalUe = $lmdUesAvecEcues->count();
    $totalEcues = $lmdUesAvecEcues->sum('nb_ecues');
    $totalCredits = $lmdUesAvecEcues->sum('total_credits');
    $totalPlanifieAll = $lmdUesAvecEcues->sum(fn ($u) => $u['totaux']['cm_p'] + $u['totaux']['td_p'] + $u['totaux']['tp_p']);
    $totalRealiseAll = $lmdUesAvecEcues->sum(fn ($u) => $u['totaux']['cm_r'] + $u['totaux']['td_r'] + $u['totaux']['tp_r']);

    $kpiTauxColor = $kpiTaux >= 70 ? '#10b981' : ($kpiTaux >= 30 ? '#f59e0b' : '#dc2626');
@endphp

<div class="sh-lmd" x-data="{ openIds: [] }">
    {{-- KPIs identiques au pattern existant --}}
    <div class="sh-planning-kpis">
        <div class="sh-planning-kpi" style="--kpi-color:#0453cb;">
            <div class="sh-planning-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="sh-planning-kpi-value">{{ number_format($planningMatiere['stats']['heures_planifiees'] ?? 0, 1) }}h</div>
            <div class="sh-planning-kpi-label">Planifiées</div>
        </div>
        <div class="sh-planning-kpi" style="--kpi-color:#10b981;">
            <div class="sh-planning-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="sh-planning-kpi-value">{{ number_format($planningMatiere['stats']['heures_realisees'] ?? 0, 1) }}h</div>
            <div class="sh-planning-kpi-label">Réalisées</div>
        </div>
        <div class="sh-planning-kpi" style="--kpi-color:#3b7ddb;">
            <div class="sh-planning-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <div class="sh-planning-kpi-value">{{ $planningMatiere['stats']['nb_seances'] ?? 0 }}</div>
            <div class="sh-planning-kpi-label">Séances</div>
        </div>
        <div class="sh-planning-kpi" style="--kpi-color:{{ $kpiTauxColor }};">
            <div class="sh-planning-kpi-icon"><i class="fas fa-chart-pie"></i></div>
            <div class="sh-planning-kpi-value" style="color:{{ $kpiTauxColor }};">{{ $kpiTaux }}%</div>
            <div class="sh-planning-kpi-label">Taux</div>
        </div>
    </div>

    {{-- Repartition par categorie pedagogique UEMOA --}}
    <div class="sh-pedago">
        <div class="sh-pedago-header">
            <div class="sh-pedago-title"><i class="fas fa-university"></i><strong>Répartition par catégorie pédagogique LMD (UEMOA)</strong></div>
            <a href="{{ route('esbtp.lmd.planning.index', array_filter(['parcours_id' => optional($classe->parcours)->id, 'niveau_id' => $classe->niveau_etude_id, 'semestre' => !empty($lmdSemestres) ? ($lmdSemestres[0] ?? null) : null])) }}" class="sh-pedago-link"><i class="fas fa-external-link-alt"></i> Maquette LMD complète</a>
        </div>
        <div class="sh-pedago-grid">
            @foreach(['cm','td','tp'] as $k)
                @php
                    $p = $vbTotalsTab[$k]['p']; $r = $vbTotalsTab[$k]['r'];
                    $pct = $p > 0 ? min(100, round($r / $p * 100)) : ($r > 0 ? 100 : 0);
                    $tone = $pct >= 100 ? '#10b981' : ($pct >= 70 ? '#f59e0b' : '#0453cb');
                @endphp
                <div class="sh-pedago-cell">
                    <div class="sh-pedago-cell-head">
                        <div class="sh-pedago-cell-label"><i class="fas {{ $vbIconsTab[$k] }}"></i>{{ $vbLabelsTab[$k] }}</div>
                        <span class="sh-pedago-cell-val">{{ rtrim(rtrim(number_format($r,1,',',''),'0'),',') ?: '0' }}h / {{ rtrim(rtrim(number_format($p,1,',',''),'0'),',') ?: '0' }}h</span>
                    </div>
                    <div class="sh-pedago-cell-bar-wrap">
                        <div class="sh-pedago-cell-bar" style="width:{{ $pct }}%;background:{{ $tone }};"></div>
                    </div>
                    <div class="sh-pedago-cell-pct" style="color:{{ $tone }};">{{ $pct }}%</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Detail par UE --}}
    @if($lmdUesAvecEcues->isEmpty())
        <div class="sh-warn">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                Aucune planification académique LMD configurée pour {{ optional($classe->parcours)->name ?: 'cette classe' }}.
                La maquette pédagogique se configure dans <a href="{{ route('esbtp.lmd.ue.index', array_filter(['parcours_id' => $classe->parcours_id, 'niveau_id' => $classe->niveau_etude_id, 'filiere_id' => $classe->filiere_id])) }}">Unités d'Enseignement</a>.
            </div>
        </div>
    @else
        <div class="sh-ue-section">
            <div class="sh-ue-section-header">
                <div>
                    <h4>Détail par Unité d'Enseignement</h4>
                    <p>{{ $totalUe }} UE · {{ $totalEcues }} ECUE · {{ $totalCredits }} crédits ECTS</p>
                </div>
                <div class="sh-ue-section-actions">
                    <button type="button" class="sh-ue-toggle-all" @click="openIds = openIds.length === {{ $totalUe }} ? [] : Array.from({length: {{ $totalUe }}}, (_, i) => i)">
                        <i class="fas fa-arrows-up-down"></i>
                        <span x-show="openIds.length !== {{ $totalUe }}">Tout déplier</span>
                        <span x-show="openIds.length === {{ $totalUe }}" x-cloak>Tout replier</span>
                    </button>
                </div>
            </div>
            @foreach($lmdUesAvecEcues as $ueIdx => $ueRow)
                @include('esbtp.classes.partials._suivi_heures_lmd_ue_card', ['ueRow' => $ueRow, 'ueIdx' => $ueIdx])
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
/* SUIVI HEURES LMD - namespace sh-* (self-contained, reusable on classes.show + emploi-temps/show) */
[x-cloak] { display: none !important; }

.sh-lmd { margin-top: 1rem; }

/* KPIs row (autonome, ex-sh-planning-kpi*) */
.sh-planning-kpis {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .75rem; margin-bottom: 1.25rem;
}
.sh-planning-kpi {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 12px; padding: .85rem 1rem;
    position: relative; overflow: hidden;
}
.sh-planning-kpi::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--kpi-color, #0453cb);
    border-radius: 12px 12px 0 0;
}
.sh-planning-kpi-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .88rem;
    background: color-mix(in srgb, var(--kpi-color, #0453cb) 12%, transparent);
    color: var(--kpi-color, #0453cb);
    margin-bottom: .35rem;
}
.sh-planning-kpi-value { font-size: 1.4rem; font-weight: 800; color: #1e293b; line-height: 1; }
.sh-planning-kpi-label { font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin-top: .25rem; }

/* Repartition pedagogique */
.sh-pedago { margin:1.25rem 0;padding:1rem 1.15rem;background:linear-gradient(135deg,rgba(4,83,203,.04),rgba(59,125,219,.06));border:1px solid rgba(4,83,203,.18);border-radius:14px; }
.sh-pedago-header { display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.85rem;flex-wrap:wrap; }
.sh-pedago-title { display:flex;align-items:center;gap:.6rem;color:#0f172a;font-size:.92rem; }
.sh-pedago-title i { color:#0453cb; }
.sh-pedago-link { font-size:.75rem;color:#0453cb;text-decoration:none;font-weight:600; }
.sh-pedago-link:hover { text-decoration:underline; }
.sh-pedago-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.85rem; }
.sh-pedago-cell { background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:.7rem .85rem; }
.sh-pedago-cell-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:.45rem; }
.sh-pedago-cell-label { display:flex;align-items:center;gap:.45rem;font-weight:700;color:#0f172a;font-size:.8rem; }
.sh-pedago-cell-label i { color:#0453cb;font-size:.78rem; }
.sh-pedago-cell-val { font-size:.7rem;color:#64748b;font-weight:600; }
.sh-pedago-cell-bar-wrap { background:#e2e8f0;border-radius:99px;height:6px;overflow:hidden; }
.sh-pedago-cell-bar { height:100%;border-radius:99px;transition:width .3s ease; }
.sh-pedago-cell-pct { margin-top:.35rem;font-size:.72rem;font-weight:700; }

/* Section detail UE */
.sh-ue-section { margin-top:1.25rem; }
.sh-ue-section-header { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.85rem; }
.sh-ue-section-header h4 { margin:0;font-size:.95rem;font-weight:700;color:#1e293b; }
.sh-ue-section-header p { margin:.15rem 0 0;font-size:.78rem;color:#64748b; }
.sh-ue-toggle-all { display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .85rem;background:rgba(4,83,203,.06);border:1px solid rgba(4,83,203,.18);border-radius:8px;color:#0453cb;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s; }
.sh-ue-toggle-all:hover { background:rgba(4,83,203,.12);border-color:rgba(4,83,203,.3); }

/* UE card */
.sh-ue-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:.65rem;overflow:hidden;transition:all .2s; }
.sh-ue-card:hover { box-shadow:0 4px 16px rgba(4,83,203,.06),0 1px 3px rgba(15,23,42,.04); }
.sh-ue-card--open { border-color:rgba(4,83,203,.3);box-shadow:0 4px 16px rgba(4,83,203,.06); }

.sh-ue-header { width:100%;display:flex;align-items:center;justify-content:space-between;gap:.85rem;padding:.85rem 1rem;background:none;border:0;text-align:left;cursor:pointer;transition:background .15s; }
.sh-ue-header:hover { background:rgba(4,83,203,.02); }
.sh-ue-header-left { display:flex;align-items:center;gap:.65rem;flex:1;min-width:0; }
.sh-ue-header-right { display:flex;align-items:center;gap:.55rem;flex-shrink:0; }

.sh-ue-caret { color:#94a3b8;font-size:.75rem;width:14px;text-align:center;transition:transform .2s ease; }
.sh-ue-caret--open { transform:rotate(90deg);color:#0453cb; }

.sh-ue-chip { display:inline-flex;align-items:center;gap:.3rem;padding:.22rem .55rem;border-radius:6px;font-size:.68rem;font-weight:700;letter-spacing:.3px;text-transform:uppercase;white-space:nowrap;flex-shrink:0; }
.sh-ue-chip--primary { background:rgba(4,83,203,.1);color:#0453cb;border:1px solid rgba(4,83,203,.25); }
.sh-ue-chip--accent { background:rgba(59,125,219,.1);color:#3b7ddb;border:1px solid rgba(59,125,219,.25); }
.sh-ue-chip--muted { background:rgba(94,145,222,.08);color:#5e91de;border:1px solid rgba(94,145,222,.2); }
.sh-ue-chip--orphan { background:rgba(245,158,11,.1);color:#b45309;border:1px solid rgba(245,158,11,.25); }

.sh-ue-title { display:flex;align-items:center;gap:.55rem;min-width:0;flex:1; }
.sh-ue-code { font-family:'Courier New',monospace;font-size:.72rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.08);padding:.15rem .45rem;border-radius:4px;flex-shrink:0; }
.sh-ue-name { font-size:.88rem;font-weight:600;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }

.sh-ue-credits { display:inline-flex;align-items:center;gap:.3rem;font-size:.72rem;font-weight:600;color:#0453cb;background:rgba(4,83,203,.06);padding:.25rem .55rem;border-radius:6px; }
.sh-ue-count { font-size:.7rem;color:#64748b;font-weight:500; }

/* UE totaux */
.sh-ue-totaux { padding:0 1rem .85rem; }
.sh-ue-bar-wrap { background:#e2e8f0;border-radius:99px;height:4px;overflow:hidden;margin-bottom:.55rem; }
.sh-ue-bar { height:100%;border-radius:99px;transition:width .3s ease; }
.sh-ue-totaux-row { display:flex;gap:.5rem;flex-wrap:wrap; }
.sh-ue-tot-chip { display:inline-flex;align-items:center;gap:.35rem;padding:.32rem .6rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;font-size:.72rem;color:#64748b; }
.sh-ue-tot-chip i { color:#0453cb;font-size:.72rem; }
.sh-ue-tot-chip strong { color:#0f172a;font-weight:700; }
.sh-ue-tot-chip--strong { background:rgba(4,83,203,.06);border-color:rgba(4,83,203,.18); }
.sh-ue-tot-chip--strong strong { color:#0453cb; }

/* Body table ECUE */
.sh-ue-body { padding:0 1rem 1rem;border-top:1px dashed #e2e8f0; }
.sh-ecue-table { width:100%;border-collapse:collapse;margin-top:.65rem; }
.sh-ecue-table thead th { padding:.55rem .65rem;font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;text-align:left; }
.sh-ecue-table thead th.sh-num { text-align:right; }
.sh-ecue-table tbody td { padding:.55rem .65rem;font-size:.82rem;color:#1e293b;border-bottom:1px solid #f1f5f9; }
.sh-ecue-table tbody tr:last-child td { border-bottom:none; }
.sh-ecue-table tbody tr:hover { background:rgba(4,83,203,.02); }
.sh-num { text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap; }
.sh-num strong { color:#0f172a;font-weight:700; }
.sh-num-strong strong { color:#0453cb; }
.sh-num-muted { color:#94a3b8;font-size:.72rem; }

.sh-ecue-code { display:inline-block;font-family:'Courier New',monospace;font-size:.7rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.06);padding:.12rem .4rem;border-radius:4px;margin-right:.5rem; }
.sh-ecue-name { font-weight:500;color:#1e293b; }

/* Warning state */
.sh-warn { display:flex;align-items:flex-start;gap:.65rem;padding:.85rem 1rem;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.25);border-radius:10px;color:#92400e;font-size:.85rem;margin-top:1rem; }
.sh-warn i { color:#f59e0b;font-size:1rem;margin-top:.1rem; }
.sh-warn a { color:#0453cb;font-weight:600; }

/* Responsive */
@media (max-width:768px) {
    .sh-hide-sm { display:none; }
    .sh-ue-name { font-size:.82rem; }
    .sh-ue-totaux-row { gap:.35rem; }
    .sh-ue-tot-chip { font-size:.68rem;padding:.25rem .45rem; }
    .sh-ecue-table thead th, .sh-ecue-table tbody td { padding:.4rem .45rem;font-size:.75rem; }
}
</style>
@endpush
