{{-- Avancement par matière. Reçoit $stats. --}}
@php $subjects = collect($stats['subjects_stats'] ?? []); @endphp
@forelse($subjects as $s)
    @php
        $taux = $s['taux_completion'] ?? 0;
        $col = $taux >= 80 ? '#10b981' : ($taux >= 40 ? '#f59e0b' : '#0453cb');
    @endphp
    <div class="cad-subject">
        <div class="cad-subject-info">
            <div class="cad-subject-name">{{ $s['matiere_name'] ?? 'Matière' }}</div>
            <div class="cad-subject-meta">
                <span><i class="fas fa-calendar"></i> {{ $s['total_seances'] ?? 0 }} séance(s)</span>
                <span><i class="fas fa-signature"></i> {{ $s['emargements_effectues'] ?? 0 }}/{{ $s['emargements_possibles'] ?? 0 }} émarg.</span>
                <span><i class="fas fa-list-check"></i> {{ $s['appels_effectues'] ?? 0 }}/{{ $s['appels_possibles'] ?? 0 }} appels</span>
            </div>
        </div>
        <div class="cad-subject-prog">
            <div class="cad-subject-bar"><div class="cad-subject-bar-fill" style="width:{{ min(100,$taux) }}%;background:{{ $col }};"></div></div>
            <span class="cad-subject-pct" style="color:{{ $col }};">{{ $taux }}%</span>
        </div>
    </div>
@empty
    <div class="cad-empty"><i class="fas fa-book"></i><p>Aucune séance programmée ce jour.</p></div>
@endforelse
