{{-- Liste des bulletins de paie. Reçoit $bulletins, $statutLabels. --}}
@php
    $fmt = fn($v) => number_format($v, 0, ',', ' ');
    $badge = [
        'brouillon' => ['bg' => 'rgba(100,116,139,.12)', 'color' => '#475569'],
        'valide'    => ['bg' => 'rgba(4,83,203,.1)',     'color' => '#0453cb'],
        'paye'      => ['bg' => 'rgba(16,185,129,.12)',  'color' => '#065f46'],
        'annule'    => ['bg' => 'rgba(220,38,38,.1)',    'color' => '#b91c1c'],
    ];
@endphp
@forelse($bulletins as $b)
    @php $st = $badge[$b->workflow_status] ?? $badge['brouillon']; @endphp
    <a href="{{ route('esbtp.comptabilite.salaires.show', $b->id) }}" class="pay-row">
        <div class="pay-row-avatar">{{ \Illuminate\Support\Str::substr($b->teacher->user->name ?? 'E', 0, 1) }}</div>
        <div class="pay-row-main">
            <div class="pay-row-name">{{ $b->teacher->user->name ?? 'Enseignant' }}</div>
            <div class="pay-row-meta">
                <span><i class="fas fa-hourglass-half"></i> {{ rtrim(rtrim(number_format($b->heures_total, 1, ',', ' '), '0'), ',') }}h</span>
                <span><i class="fas fa-coins"></i> Base {{ $fmt($b->salaire_base) }}</span>
                <span><i class="fas fa-circle-minus"></i> Retenues {{ $fmt($b->retenues) }}</span>
            </div>
        </div>
        <div class="pay-row-net">
            <div class="pay-row-net-val">{{ $fmt($b->net_a_payer) }}</div>
            <div class="pay-row-net-lbl">FCFA net</div>
        </div>
        <span class="pay-row-badge" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">
            {{ $statutLabels[$b->workflow_status] ?? ucfirst($b->workflow_status) }}
        </span>
    </a>
@empty
    <div class="pay-empty">
        <i class="fas fa-file-invoice-dollar"></i>
        <p>Aucun bulletin pour cette période.</p>
    </div>
@endforelse
