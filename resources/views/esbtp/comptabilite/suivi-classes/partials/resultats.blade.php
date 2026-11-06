@php
    $sfcMontant = fn ($n) => number_format((float) $n, 0, ',', ' ');
    // Couleur sémantique du recouvrement : elle porte un statut, pas une décoration.
    $sfcTaux = fn ($t) => $t === null ? 'sfc-rate--none' : ($t >= 80 ? 'sfc-rate--ok' : ($t >= 40 ? 'sfc-rate--mid' : 'sfc-rate--low'));
    $sfcDetail = auth()->user()?->can('comptabilite.suivi_classes.view_details');
    $sfcCaisse = ! $rapport['filtered_category'];
@endphp

<section aria-labelledby="sfc-watch-title">
    <h2 class="sfc-section-title" id="sfc-watch-title"><i class="fas fa-bell"></i> À surveiller en premier</h2>
    @if(count($rapport['a_surveiller'] ?? []) > 0)
        <div class="sfc-watch">
            @foreach($rapport['a_surveiller'] as $l)
                <div class="sfc-watch-card">
                    <div class="sfc-watch-head">
                        <span class="sfc-watch-name" title="{{ $l['classe'] }}">{{ $l['classe'] }}</span>
                        <span class="sfc-watch-rate">{{ $l['taux'] === null ? '—' : number_format($l['taux'], 1, ',', ' ').' % recouvré' }}</span>
                    </div>
                    <div class="sfc-watch-amount">{{ $sfcMontant($l['echu']) }}<small>FCFA échus</small></div>
                    @if($sfcDetail)
                        <button type="button" class="sfc-link" style="align-self:flex-start" @click="openDetails({{ $l['classe_id'] }}, @js($l['classe']))">
                            <i class="fas fa-user-clock"></i> Voir les étudiants en retard
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="sfc-watch-ok"><i class="fas fa-circle-check"></i> Aucune tranche échue impayée dans ces classes.</div>
    @endif
</section>

<section class="sfc-panel" :class="loading && 'sfc-busy'" aria-labelledby="sfc-table-title">
    <div class="sfc-panel-head">
        <div>
            <h2 id="sfc-table-title">Classe par classe</h2>
            <p>Les totaux portent sur toutes les classes filtrées, pas seulement sur cette page.</p>
        </div>
        <span class="sfc-count">{{ $rapport['totaux']['classes'] }} classe(s)</span>
    </div>

    @if(count($rapport['classes']) === 0)
        <div class="sfc-empty"><i class="fas fa-folder-open"></i>Aucune classe ne correspond à ces filtres. Changez d’année ou effacez les filtres.</div>
    @else
        <div class="sfc-table-scroll">
            <table class="sfc-table">
                <thead>
                <tr>
                    <th>Classe</th>
                    <th class="sfc-num">Inscrits</th>
                    <th class="sfc-num">Attendu</th>
                    <th class="sfc-num">Réglé</th>
                    @if($sfcCaisse)<th class="sfc-num" title="Versements encaissés, avoirs déduits">Caisse nette</th>@endif
                    <th class="sfc-num">Reste</th>
                    <th class="sfc-num">Échu impayé</th>
                    <th>Recouvrement</th>
                    @if($sfcDetail)<th><span class="sfc-sr-only">Détail</span></th>@endif
                </tr>
                </thead>
                <tbody>
                @foreach($rapport['classes'] as $l)
                    <tr>
                        <td class="sfc-class"><strong>{{ $l['classe'] }}</strong><small>{{ $l['filiere'] }} · {{ $l['niveau'] }}</small></td>
                        <td class="sfc-num">{{ $l['effectif'] }}</td>
                        <td class="sfc-num">{{ $sfcMontant($l['attendu']) }}</td>
                        <td class="sfc-num">{{ $sfcMontant($l['regle']) }}</td>
                        @if($sfcCaisse)<td class="sfc-num">{{ $sfcMontant($l['encaisse']) }}</td>@endif
                        <td class="sfc-num">{{ $sfcMontant($l['restant']) }}</td>
                        <td class="sfc-num {{ $l['echu'] > 0 ? 'sfc-due' : 'sfc-zero' }}">{{ $sfcMontant($l['echu']) }}</td>
                        <td class="sfc-rate {{ $sfcTaux($l['taux']) }}">
                            <div class="sfc-rate-top"><span>{{ $l['taux'] === null ? 'Rien d’attendu' : number_format($l['taux'], 1, ',', ' ').' %' }}</span></div>
                            <div class="sfc-rate-bar" aria-hidden="true"><i style="width:{{ min(100, (float) ($l['taux'] ?? 0)) }}%"></i></div>
                        </td>
                        @if($sfcDetail)
                            <td><button type="button" class="sfc-link" @click="openDetails({{ $l['classe_id'] }}, @js($l['classe']))" aria-label="Voir les étudiants de {{ $l['classe'] }}"><i class="fas fa-users"></i> Étudiants</button></td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="sfc-cards-mobile">
            @foreach($rapport['classes'] as $l)
                <article class="sfc-mcard">
                    <div class="sfc-class"><strong>{{ $l['classe'] }}</strong><small>{{ $l['filiere'] }} · {{ $l['niveau'] }} · {{ $l['effectif'] }} inscrit(s)</small></div>
                    <div class="sfc-mcard-grid">
                        <div><span>Attendu</span><strong>{{ $sfcMontant($l['attendu']) }}</strong></div>
                        <div><span>Réglé</span><strong>{{ $sfcMontant($l['regle']) }}</strong></div>
                        <div><span>Reste</span><strong>{{ $sfcMontant($l['restant']) }}</strong></div>
                        <div><span>Échu impayé</span><strong class="{{ $l['echu'] > 0 ? 'sfc-due' : 'sfc-zero' }}">{{ $sfcMontant($l['echu']) }}</strong></div>
                    </div>
                    <div class="sfc-rate {{ $sfcTaux($l['taux']) }}">
                        <div class="sfc-rate-top"><span>Recouvrement</span><span>{{ $l['taux'] === null ? '—' : number_format($l['taux'], 1, ',', ' ').' %' }}</span></div>
                        <div class="sfc-rate-bar" aria-hidden="true"><i style="width:{{ min(100, (float) ($l['taux'] ?? 0)) }}%"></i></div>
                    </div>
                    @if($sfcDetail)
                        <button type="button" class="sfc-link" style="align-self:flex-start" @click="openDetails({{ $l['classe_id'] }}, @js($l['classe']))"><i class="fas fa-users"></i> Voir les étudiants</button>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    <div class="sfc-foot">
        <span>{{ $rapport['totaux']['effectif'] }} inscription(s) distincte(s)</span>
        @if($rapport['classes'] instanceof \Illuminate\Pagination\LengthAwarePaginator && $rapport['classes']->hasPages())
            <div class="sfc-pagination">{{ $rapport['classes']->links() }}</div>
        @endif
    </div>
</section>
