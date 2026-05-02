@props([
    'model',
    'limit' => 20,
    'showLinkToFull' => true,
    'compact' => false,
])

@can('security.audit.view')
@php
    $audits = app(\App\Services\AuditService::class)->historyFor($model, $limit);
    $eventLabels = [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
        'retrieved' => 'Consultation',
    ];
    $eventIcons = [
        'created' => 'fa-plus-circle',
        'updated' => 'fa-pen',
        'deleted' => 'fa-trash',
        'restored' => 'fa-undo',
        'retrieved' => 'fa-eye',
    ];
    $auditService = app(\App\Services\AuditService::class);
@endphp

<div class="au-eh {{ $compact ? 'au-eh--compact' : '' }}">
    <div class="au-eh__header">
        <h3 class="au-eh__title">
            <i class="fas fa-history"></i>
            Historique
            <span class="au-eh__count">{{ $audits->count() }}</span>
        </h3>
        @if($showLinkToFull && $audits->count() > 0)
            <a href="{{ route('esbtp.audit.index') }}?model_type={{ urlencode(get_class($model)) }}&search={{ $model->getKey() }}"
               class="au-eh__link">
                Voir tout <i class="fas fa-arrow-right"></i>
            </a>
        @endif
    </div>

    @if($audits->isEmpty())
        <div class="au-eh__empty">
            <i class="fas fa-clock"></i>
            <span>Aucun événement enregistré pour cette entité.</span>
        </div>
    @else
        <ul class="au-eh__timeline">
            @foreach($audits as $audit)
                @php
                    $event = $audit->event;
                    $diff = $event === 'updated' ? $auditService->formatDiff($audit) : [];
                    $risk = $auditService->riskLevel($audit);
                @endphp
                <li class="au-eh__item au-eh__item--{{ $event }}">
                    <div class="au-eh__icon">
                        <i class="fas {{ $eventIcons[$event] ?? 'fa-circle' }}"></i>
                    </div>
                    <div class="au-eh__body">
                        <div class="au-eh__meta">
                            <span class="au-eh__chip au-eh__chip--{{ $event }}">
                                {{ $eventLabels[$event] ?? mb_strtoupper($event, 'UTF-8') }}
                            </span>
                            <span class="au-eh__user">
                                par <strong>{{ $audit->user?->name ?? 'Système' }}</strong>
                            </span>
                            <span class="au-eh__time" title="{{ $audit->created_at->format('d/m/Y H:i:s') }}">
                                {{ $audit->created_at->diffForHumans() }}
                            </span>
                        </div>

                        @if($event === 'updated' && !empty($diff))
                            <div class="au-eh__changes">
                                @foreach(array_slice($diff, 0, 3, true) as $field => $change)
                                    <div class="au-eh__change">
                                        <span class="au-eh__field">{{ $field }}</span>
                                        <span class="au-eh__old">{{ \Illuminate\Support\Str::limit((string)($change['old'] ?? '—'), 40) }}</span>
                                        <i class="fas fa-arrow-right au-eh__arrow"></i>
                                        <span class="au-eh__new">{{ \Illuminate\Support\Str::limit((string)($change['new'] ?? '—'), 40) }}</span>
                                    </div>
                                @endforeach
                                @if(count($diff) > 3)
                                    <div class="au-eh__more">+{{ count($diff) - 3 }} autre(s) champ(s)</div>
                                @endif
                            </div>
                        @endif

                        <div class="au-eh__footer">
                            @if($audit->ip_address)
                                <span class="au-eh__ip"><i class="fas fa-network-wired"></i> {{ $audit->ip_address }}</span>
                            @endif
                            <a href="{{ route('esbtp.audit.show', $audit->id) }}" class="au-eh__detail">
                                Détail complet <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

@once
@push('styles')
<style>
.au-eh {
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.au-eh--compact { padding:.85rem 1rem; }

.au-eh__header {
    display:flex; align-items:center; justify-content:space-between;
    padding-bottom:.85rem; border-bottom:1px solid #f1f5f9; margin-bottom:.85rem;
}
.au-eh__title {
    font-size:.95rem; font-weight:600; color:#0f172a;
    margin:0; display:flex; align-items:center; gap:.5rem;
}
.au-eh__title i { color:#0453cb; }
.au-eh__count {
    background:#eff6ff; color:#0453cb;
    padding:.1rem .55rem; border-radius:999px;
    font-size:.72rem; font-weight:700;
}
.au-eh__link {
    font-size:.78rem; font-weight:600; color:#0453cb;
    text-decoration:none; display:inline-flex; align-items:center; gap:.3rem;
}
.au-eh__link:hover { color:#033a8e; }

.au-eh__empty {
    text-align:center; padding: 1.5rem 1rem;
    color:#64748b; font-size:.88rem;
    display:flex; flex-direction:column; align-items:center; gap:.5rem;
}
.au-eh__empty i { font-size:1.6rem; opacity:.4; }

.au-eh__timeline { list-style:none; padding:0; margin:0; }
.au-eh__item {
    display:flex; gap:.85rem;
    padding:.85rem 0;
    border-bottom:1px solid #f8fafc;
    position:relative;
}
.au-eh__item:last-child { border-bottom:none; padding-bottom:0; }
.au-eh__item:first-child { padding-top:0; }

.au-eh__icon {
    width:34px; height:34px; border-radius:10px;
    flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.85rem; color:#fff;
}
.au-eh__item--created .au-eh__icon { background: linear-gradient(135deg, #10b981, #059669); }
.au-eh__item--updated .au-eh__icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.au-eh__item--deleted .au-eh__icon { background: linear-gradient(135deg, #ef4444, #b91c1c); }
.au-eh__item--restored .au-eh__icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
.au-eh__item--retrieved .au-eh__icon { background: linear-gradient(135deg, #6b7280, #4b5563); }

.au-eh__body { flex:1; min-width:0; }

.au-eh__meta {
    display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
    font-size:.82rem; color:#475569;
    margin-bottom:.35rem;
}
.au-eh__user strong { color:#0f172a; }
.au-eh__time { color:#94a3b8; font-size:.75rem; }

.au-eh__chip {
    padding:.15rem .55rem; border-radius:999px;
    font-size:.7rem; font-weight:600;
    background:#f1f5f9; color:#475569;
}
.au-eh__chip--created { background:#dcfce7; color:#15803d; }
.au-eh__chip--updated { background:#dbeafe; color:#1d4ed8; }
.au-eh__chip--deleted { background:#fee2e2; color:#991b1b; }
.au-eh__chip--restored { background:#fef3c7; color:#92400e; }
.au-eh__chip--retrieved { background:#f3f4f6; color:#4b5563; }

.au-eh__changes {
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:8px; padding:.5rem .75rem;
    margin: .35rem 0;
}
.au-eh__change {
    display:flex; align-items:center; gap:.4rem;
    font-size:.78rem; padding:.15rem 0; flex-wrap:wrap;
}
.au-eh__field { font-weight:600; color:#0f172a; min-width:80px; }
.au-eh__old { color:#dc2626; text-decoration: line-through; opacity:.85; }
.au-eh__new { color:#15803d; font-weight:500; }
.au-eh__arrow { color:#94a3b8; font-size:.65rem; }
.au-eh__more {
    font-size:.72rem; color:#64748b; font-style:italic; margin-top:.25rem;
}

.au-eh__footer {
    display:flex; align-items:center; justify-content:space-between;
    margin-top:.35rem; flex-wrap:wrap; gap:.5rem;
}
.au-eh__ip {
    font-family: monospace; font-size:.72rem; color:#64748b;
    display:inline-flex; align-items:center; gap:.3rem;
}
.au-eh__detail {
    font-size:.75rem; color:#0453cb; text-decoration:none; font-weight:500;
}
.au-eh__detail:hover { color:#033a8e; text-decoration:underline; }

@media (max-width: 576px) {
    .au-eh { padding:.85rem 1rem; }
    .au-eh__item { gap:.6rem; padding:.7rem 0; }
    .au-eh__icon { width:30px; height:30px; font-size:.78rem; }
    .au-eh__change { font-size:.72rem; }
}
</style>
@endpush
@endonce
@endcan
