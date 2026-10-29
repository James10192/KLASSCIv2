@php
    $paiementsLies = $inscription->paiements
        ->sortByDesc(fn ($p) => $p->date_paiement ?? $p->created_at)
        ->values();

    $paiementsEnAttenteCount = $paiementsLies->where('status', 'en_attente')->count();
    $paiementsValidesCount = $paiementsLies->where('status', 'validé')->count();
    $paiementsRejetesCount = $paiementsLies->where('status', 'rejeté')->count();

    $statusUi = [
        'validé' => ['Validé', 'success', 'fa-check-circle'],
        'en_attente' => ['À valider', 'warning', 'fa-hourglass-half'],
        'rejeté' => ['Rejeté', 'danger', 'fa-times-circle'],
    ];
@endphp

<div class="is-card ips-payments-card" id="inscription-payments-section"
     data-inscription-id="{{ $inscription->id }}">
    <div class="is-card-body">
        <div class="is-section-header ips-payments-head">
            <div class="is-section-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <div class="is-section-title">Paiements liés à cette inscription</div>
                <div class="ips-payments-subtitle">
                    {{ $paiementsLies->count() }} versement{{ $paiementsLies->count() > 1 ? 's' : '' }}
                    @if($paiementsEnAttenteCount > 0)
                        · <strong>{{ $paiementsEnAttenteCount }} à valider</strong>
                    @endif
                </div>
            </div>

            <div class="ms-auto ips-payments-head-actions">
                <div class="dropdown pdf-dropdown">
                    <button class="btn btn-outline-success dropdown-toggle" type="button"
                            id="situationFinanciereDropdown" data-bs-toggle="dropdown"
                            aria-expanded="false" title="Situation Financière">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="d-none d-sm-inline">Situation Financière</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="situationFinanciereDropdown">
                        <li><a class="dropdown-item" href="{{ route('esbtp.inscriptions.situation-financiere.preview', $inscription) }}"><i class="fas fa-window-restore me-1"></i>Vue web</a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.inscriptions.situation-financiere.pdf-preview', $inscription) }}" target="_blank"><i class="fas fa-eye me-1"></i>Aperçu PDF</a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $inscription) }}"><i class="fas fa-download me-1"></i>Télécharger PDF</a></li>
                    </ul>
                </div>
            </div>
        </div>

        @if($paiementsLies->count() > 0)
            <div class="ips-filterbar" role="tablist" aria-label="Filtrer les paiements">
                <button type="button" class="ips-filter is-active" data-payment-filter="all">Tous <span>{{ $paiementsLies->count() }}</span></button>
                <button type="button" class="ips-filter" data-payment-filter="en_attente">À valider <span>{{ $paiementsEnAttenteCount }}</span></button>
                <button type="button" class="ips-filter" data-payment-filter="validé">Validés <span>{{ $paiementsValidesCount }}</span></button>
                <button type="button" class="ips-filter" data-payment-filter="rejeté">Rejetés <span>{{ $paiementsRejetesCount }}</span></button>
            </div>

            <div class="ips-table-wrap">
                <table class="ips-table">
                    <thead><tr><th>N° Reçu</th><th>Catégorie</th><th>Date</th><th>Montant</th><th class="d-none d-lg-table-cell">Mode</th><th>Statut</th><th class="ips-th-actions">Actions</th></tr></thead>
                    <tbody>
                    @foreach($paiementsLies as $paiement)
                        @php
                            $ventilation = $paiement->ventilation();
                            $noms = $ventilation->pluck('nom')->filter()->values();
                            $categorieLabel = $noms->count() > 1
                                ? $noms->take(2)->implode(', ').($noms->count() > 2 ? ' +'.($noms->count() - 2) : '')
                                : ($noms->first() ?: ($paiement->fraisCategory->name ?? $paiement->categorie->name ?? $paiement->motif ?? 'Non définie'));
                            $categoryIds = $ventilation->pluck('frais_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
                            if ($categoryIds->isEmpty() && $paiement->frais_category_id) $categoryIds = collect([(int) $paiement->frais_category_id]);
                            [$statusLabel, $statusTone, $statusIcon] = $statusUi[$paiement->status] ?? [ucfirst((string) $paiement->status), 'secondary', 'fa-circle'];
                            $datePaiement = $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement) : $paiement->created_at;
                        @endphp
                        <tr class="ips-payment-row ips-payment-row--{{ $statusTone }}"
                            data-paiement-id="{{ $paiement->id }}" data-payment-status="{{ $paiement->status }}"
                            data-category-ids=",{{ $categoryIds->implode(',') }}," data-reliquat-id="{{ $paiement->reliquat_detail_id ?? '' }}">
                            <td><div class="ips-receipt"><span>Reçu</span><strong>{{ $paiement->numero_recu ?: '—' }}</strong></div></td>
                            <td><div class="ips-category" title="{{ $categorieLabel }}"><i class="fas fa-layer-group"></i><span>{{ $categorieLabel }}</span></div></td>
                            <td><div class="ips-date"><i class="far fa-calendar-alt"></i>{{ $datePaiement?->format('d/m/Y') ?? '—' }}</div></td>
                            <td><strong class="ips-amount {{ $paiement->isAvoir() ? 'is-credit' : '' }}">{{ $paiement->isAvoir() ? '− ' : '' }}{{ number_format((float) $paiement->montant, 0, ',', ' ') }} <span>FCFA</span></strong></td>
                            <td class="d-none d-lg-table-cell"><span class="ips-mode"><i class="fas fa-wallet"></i>{{ $paiement->mode_paiement ?: '—' }}</span></td>
                            <td><span class="ips-status ips-status--{{ $statusTone }}"><i class="fas {{ $statusIcon }}"></i>{{ $statusLabel }}</span></td>
                            <td>
                                <div class="ips-actions" data-payment-actions="{{ $paiement->id }}">
                                    <div class="ips-actions-buttons">
                                        @can('view', $paiement)
                                            <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="ips-action" title="Voir le paiement" aria-label="Voir le paiement"><i class="fas fa-eye"></i></a>
                                        @endcan
                                        @if($paiement->status === 'validé' && ! $paiement->isAvoir())
                                            <a href="{{ route('esbtp.paiements.preview-pdf', $paiement->id) }}" target="_blank" rel="noopener" class="ips-action" title="Aperçu du reçu" aria-label="Aperçu du reçu"><i class="fas fa-file-pdf"></i></a>
                                        @endif
                                        @if($paiement->status === 'en_attente')
                                            @can('paiements.edit')
                                                <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}" class="ips-action ips-action--warning" title="Modifier" aria-label="Modifier"><i class="fas fa-pen"></i></a>
                                            @endcan
                                            @can('paiements.validate')
                                                <button type="button" class="ips-action ips-action--success js-paiement-valider-inline"
                                                        data-paiement-id="{{ $paiement->id }}" data-action-url="{{ route('esbtp.paiements.valider', $paiement->id) }}"
                                                        title="Valider maintenant" aria-label="Valider maintenant"><i class="fas fa-check"></i></button>
                                            @endcan
                                        @endif
                                    </div>
                                    <div class="ips-actions-spinner" aria-hidden="true"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ips-mobile-list">
                @foreach($paiementsLies as $paiement)
                    @php
                        $ventilation = $paiement->ventilation();
                        $noms = $ventilation->pluck('nom')->filter()->values();
                        $categorieLabel = $noms->count() > 1 ? $noms->take(2)->implode(', ').($noms->count() > 2 ? ' +'.($noms->count() - 2) : '') : ($noms->first() ?: ($paiement->fraisCategory->name ?? $paiement->categorie->name ?? $paiement->motif ?? 'Non définie'));
                        $categoryIds = $ventilation->pluck('frais_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
                        if ($categoryIds->isEmpty() && $paiement->frais_category_id) $categoryIds = collect([(int) $paiement->frais_category_id]);
                        [$statusLabel, $statusTone, $statusIcon] = $statusUi[$paiement->status] ?? [ucfirst((string) $paiement->status), 'secondary', 'fa-circle'];
                        $datePaiement = $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement) : $paiement->created_at;
                    @endphp
                    <article class="ips-payment-mobile ips-payment-mobile--{{ $statusTone }}"
                             data-paiement-id="{{ $paiement->id }}" data-payment-status="{{ $paiement->status }}"
                             data-category-ids=",{{ $categoryIds->implode(',') }}," data-reliquat-id="{{ $paiement->reliquat_detail_id ?? '' }}">
                        <div class="ips-mobile-top">
                            <div class="ips-receipt"><span>Reçu</span><strong>{{ $paiement->numero_recu ?: '—' }}</strong></div>
                            <span class="ips-status ips-status--{{ $statusTone }}"><i class="fas {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
                        </div>
                        <div class="ips-mobile-category">{{ $categorieLabel }}</div>
                        <div class="ips-mobile-grid">
                            <div><span>Date</span><strong>{{ $datePaiement?->format('d/m/Y') ?? '—' }}</strong></div>
                            <div><span>Mode</span><strong>{{ $paiement->mode_paiement ?: '—' }}</strong></div>
                        </div>
                        <div class="ips-mobile-bottom">
                            <strong class="ips-amount {{ $paiement->isAvoir() ? 'is-credit' : '' }}">{{ $paiement->isAvoir() ? '− ' : '' }}{{ number_format((float) $paiement->montant, 0, ',', ' ') }} <span>FCFA</span></strong>
                            <div class="ips-actions" data-payment-actions="{{ $paiement->id }}">
                                <div class="ips-actions-buttons">
                                    @can('view', $paiement)
                                        <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="ips-action" aria-label="Voir le paiement"><i class="fas fa-eye"></i></a>
                                    @endcan
                                    @if($paiement->status === 'en_attente')
                                        @can('paiements.validate')
                                            <button type="button" class="ips-action ips-action--success js-paiement-valider-inline"
                                                    data-paiement-id="{{ $paiement->id }}" data-action-url="{{ route('esbtp.paiements.valider', $paiement->id) }}"
                                                    aria-label="Valider maintenant"><i class="fas fa-check"></i></button>
                                        @endcan
                                    @elseif($paiement->status === 'validé' && ! $paiement->isAvoir())
                                        <a href="{{ route('esbtp.paiements.preview-pdf', $paiement->id) }}" target="_blank" rel="noopener" class="ips-action" aria-label="Aperçu du reçu"><i class="fas fa-file-pdf"></i></a>
                                    @endif
                                </div>
                                <div class="ips-actions-spinner" aria-hidden="true"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="is-empty-state">
                <div class="is-empty-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="is-empty-text">Aucun paiement enregistré</div>
                <div class="is-empty-sub">Les paiements apparaîtront ici une fois effectués.</div>
            </div>
        @endif
    </div>
</div>
