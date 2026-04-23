{{--
    Section "Saisie manuelle (heures par matière)" — tab Présences de la
    fiche étudiant.

    Variables attendues :
      $etudiantId       int
      $anneeId          int
      $isCurrentYear    bool
      $anneeLabel       string  (affiché hors année courante)

    La récupération se fait ici (plutôt que dans le controller-god) pour
    garder la vue autonome ; le coût est une requête par année affichée,
    bornée aux années où l'étudiant est inscrit.
--}}
@php
    $mhRows = \App\Models\ESBTPAttendanceManualHours::with('matiere:id,name')
        ->where('etudiant_id', $etudiantId)
        ->where('annee_universitaire_id', $anneeId)
        ->orderBy('matiere_id')
        ->orderBy('periode')
        ->get();

    $mhPerMatiere = $mhRows->filter(fn ($r) => $r->matiere_id !== null);
    $mhGlobalRows = $mhRows->filter(fn ($r) => $r->matiere_id === null);

    $fmtHours = fn ($h) => rtrim(rtrim(number_format((float) $h, 2, '.', ''), '0'), '.');

    $periodeLabel = fn ($p) => ucfirst(str_replace('semestre', 'Semestre ', $p));
@endphp

@if($mhRows->isNotEmpty())
<div class="mh-card" data-current="{{ $isCurrentYear ? '1' : '0' }}">
    <div class="mh-head">
        <div class="mh-head-title">
            <span class="mh-head-icon"><i class="fas fa-list-check"></i></span>
            <div>
                <div class="mh-head-label">Saisie manuelle (heures par matière)</div>
                @if(!$isCurrentYear)
                    <div class="mh-head-sub">Année {{ $anneeLabel ?? '—' }}</div>
                @endif
            </div>
        </div>
        <span class="mh-chip mh-chip--priority">
            <i class="fas fa-star"></i>Source prioritaire sur bulletin
        </span>
    </div>

    @if($isCurrentYear)
        {{-- KPIs : calculs directs sur la collection, pas de requête supplémentaire. --}}
        @php
            $kpiPresence = $mhRows->sum(fn ($r) => (float) $r->heures_presence);
            $kpiAbsJust = $mhRows->sum(fn ($r) => (float) $r->heures_absence_justifiees);
            $kpiAbsNonJust = $mhRows->sum(fn ($r) => (float) $r->heures_absence_non_justifiees);
            $kpiTotal = $kpiPresence + $kpiAbsJust + $kpiAbsNonJust;
            $kpiTauxAssiduite = $kpiTotal > 0 ? round($kpiPresence / $kpiTotal * 100) : null;
            $kpiNbMatieres = $mhPerMatiere->pluck('matiere_id')->unique()->count();
        @endphp
        <div class="mh-kpis">
            <div class="mh-kpi">
                <div class="mh-kpi-icon mh-kpi-icon--blue"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="mh-kpi-val">{{ $fmtHours($kpiTotal) }}<span class="mh-kpi-unit">h</span></div>
                    <div class="mh-kpi-lbl">Total heures saisies</div>
                </div>
            </div>
            <div class="mh-kpi">
                <div class="mh-kpi-icon mh-kpi-icon--blue"><i class="fas fa-book"></i></div>
                <div>
                    <div class="mh-kpi-val">{{ $kpiNbMatieres }}{{ $mhGlobalRows->isNotEmpty() ? ' + G' : '' }}</div>
                    <div class="mh-kpi-lbl">{{ $kpiNbMatieres > 1 ? 'Matières saisies' : 'Matière saisie' }}{{ $mhGlobalRows->isNotEmpty() ? ' (+ global)' : '' }}</div>
                </div>
            </div>
            <div class="mh-kpi">
                <div class="mh-kpi-icon mh-kpi-icon--{{ $kpiTauxAssiduite === null ? 'muted' : ($kpiTauxAssiduite >= 80 ? 'success' : ($kpiTauxAssiduite >= 60 ? 'warning' : 'danger')) }}">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <div class="mh-kpi-val">{{ $kpiTauxAssiduite !== null ? $kpiTauxAssiduite . '%' : '—' }}</div>
                    <div class="mh-kpi-lbl">Assiduité manuelle</div>
                </div>
            </div>
        </div>

        {{-- Graphe horizontal par matière — CSS flexbox pur (pas de Chart.js) --}}
        @if($mhPerMatiere->isNotEmpty())
            @php
                $matiereGroups = $mhPerMatiere->groupBy('matiere_id');
            @endphp
            <div class="mh-chart">
                <div class="mh-chart-legend">
                    <span class="mh-legend-item"><span class="mh-legend-dot mh-legend-dot--pres"></span>Présence</span>
                    <span class="mh-legend-item"><span class="mh-legend-dot mh-legend-dot--absj"></span>Abs. justifiée</span>
                    <span class="mh-legend-item"><span class="mh-legend-dot mh-legend-dot--absnj"></span>Abs. non justifiée</span>
                </div>
                @foreach($matiereGroups as $matiereId => $rows)
                    @php
                        $pres = $rows->sum(fn ($r) => (float) $r->heures_presence);
                        $absJ = $rows->sum(fn ($r) => (float) $r->heures_absence_justifiees);
                        $absNj = $rows->sum(fn ($r) => (float) $r->heures_absence_non_justifiees);
                        $total = $pres + $absJ + $absNj;
                        $pctPres = $total > 0 ? ($pres / $total) * 100 : 0;
                        $pctAbsJ = $total > 0 ? ($absJ / $total) * 100 : 0;
                        $pctAbsNj = $total > 0 ? ($absNj / $total) * 100 : 0;
                        $matiereName = optional($rows->first()->matiere)->name ?? '—';
                    @endphp
                    <div class="mh-chart-row">
                        <div class="mh-chart-name" title="{{ $matiereName }}">{{ $matiereName }}</div>
                        <div class="mh-chart-track">
                            @if($pctPres > 0)
                                <div class="mh-chart-seg mh-chart-seg--pres" style="width: {{ $pctPres }}%" title="Présence : {{ $fmtHours($pres) }}h"></div>
                            @endif
                            @if($pctAbsJ > 0)
                                <div class="mh-chart-seg mh-chart-seg--absj" style="width: {{ $pctAbsJ }}%" title="Abs. justifiée : {{ $fmtHours($absJ) }}h"></div>
                            @endif
                            @if($pctAbsNj > 0)
                                <div class="mh-chart-seg mh-chart-seg--absnj" style="width: {{ $pctAbsNj }}%" title="Abs. non justifiée : {{ $fmtHours($absNj) }}h"></div>
                            @endif
                        </div>
                        <div class="mh-chart-total">{{ $fmtHours($total) }}<span class="mh-chart-unit">h</span></div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Ligne globale (si scope 1 activé et qu'une ligne matiere_id NULL existe) --}}
        @foreach($mhGlobalRows as $g)
            <div class="mh-global">
                <div class="mh-global-head">
                    <span class="mh-chip mh-chip--global"><i class="fas fa-globe"></i>Global</span>
                    <span class="mh-global-periode">{{ $periodeLabel($g->periode) }}</span>
                </div>
                <div class="mh-global-grid">
                    <div class="mh-global-stat"><span class="mh-global-stat-lbl">Présence</span><span class="mh-global-stat-val mh-global-stat-val--pres">{{ $fmtHours($g->heures_presence) }}h</span></div>
                    <div class="mh-global-stat"><span class="mh-global-stat-lbl">Abs. just.</span><span class="mh-global-stat-val mh-global-stat-val--absj">{{ $fmtHours($g->heures_absence_justifiees) }}h</span></div>
                    <div class="mh-global-stat"><span class="mh-global-stat-lbl">Abs. non just.</span><span class="mh-global-stat-val mh-global-stat-val--absnj">{{ $fmtHours($g->heures_absence_non_justifiees) }}h</span></div>
                </div>
                @if(!empty($g->notes))
                    <div class="mh-global-note"><i class="fas fa-sticky-note"></i>{{ $g->notes }}</div>
                @endif
            </div>
        @endforeach

        {{-- Détail par matière × période (accordion Bootstrap 5 multi-open) --}}
        @if($mhPerMatiere->isNotEmpty())
            <details class="mh-details">
                <summary class="mh-details-summary">
                    <i class="fas fa-chevron-right mh-details-chevron"></i>
                    Voir le détail par matière et période ({{ $mhPerMatiere->count() }} ligne{{ $mhPerMatiere->count() > 1 ? 's' : '' }})
                </summary>
                <div class="mh-details-body">
                    <table class="mh-table">
                        <thead>
                            <tr>
                                <th class="mh-th">Matière</th>
                                <th class="mh-th">Période</th>
                                <th class="mh-th mh-th--num">Présence</th>
                                <th class="mh-th mh-th--num">Abs. just.</th>
                                <th class="mh-th mh-th--num">Abs. non just.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mhPerMatiere as $mh)
                                <tr>
                                    <td class="mh-td mh-td--name">{{ optional($mh->matiere)->name ?? '—' }}</td>
                                    <td class="mh-td mh-td--muted">{{ $periodeLabel($mh->periode) }}</td>
                                    <td class="mh-td mh-td--num mh-td--pres">{{ $fmtHours($mh->heures_presence) }}h</td>
                                    <td class="mh-td mh-td--num mh-td--absj">{{ $fmtHours($mh->heures_absence_justifiees) }}h</td>
                                    <td class="mh-td mh-td--num mh-td--absnj">{{ $fmtHours($mh->heures_absence_non_justifiees) }}h</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif

    @else
        {{-- Années précédentes : tableau simple (sobriété dans l'accordéon) --}}
        <div class="mh-table-wrap">
            <table class="mh-table">
                <thead>
                    <tr>
                        <th class="mh-th">Matière</th>
                        <th class="mh-th">Période</th>
                        <th class="mh-th mh-th--num">Présence</th>
                        <th class="mh-th mh-th--num">Abs. just.</th>
                        <th class="mh-th mh-th--num">Abs. non just.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mhRows as $mh)
                        <tr>
                            <td class="mh-td mh-td--name">
                                @if($mh->matiere_id === null)
                                    <span class="mh-chip mh-chip--global mh-chip--sm"><i class="fas fa-globe"></i>Global</span>
                                @else
                                    {{ optional($mh->matiere)->name ?? '—' }}
                                @endif
                            </td>
                            <td class="mh-td mh-td--muted">{{ $periodeLabel($mh->periode) }}</td>
                            <td class="mh-td mh-td--num mh-td--pres">{{ $fmtHours($mh->heures_presence) }}h</td>
                            <td class="mh-td mh-td--num mh-td--absj">{{ $fmtHours($mh->heures_absence_justifiees) }}h</td>
                            <td class="mh-td mh-td--num mh-td--absnj">{{ $fmtHours($mh->heures_absence_non_justifiees) }}h</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="mh-footnote">
        <i class="fas fa-info-circle"></i>
        Pour ces matières et périodes, le bulletin utilise ces heures manuelles au lieu des séances.
        @if($mhGlobalRows->isNotEmpty())
            La ligne <strong>globale</strong> s'ajoute au total étudiant sans être ventilée par matière.
        @endif
    </p>
</div>
@endif
