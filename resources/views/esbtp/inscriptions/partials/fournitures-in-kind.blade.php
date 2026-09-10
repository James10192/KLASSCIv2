@can('inscriptions.in_kind.mark')
    @php
        $fournitures = collect($feeCategoriesWithRules ?? [])
            ->filter(fn ($item) => !empty($item['category']->accepts_in_kind) || !empty($item['satisfied_in_kind']));
    @endphp
    @if($fournitures->isNotEmpty())
        <div class="is-card">
            <div class="is-card-body">
                <div class="is-section-header">
                    <div class="is-section-icon"><i class="fas fa-box"></i></div>
                    <div class="is-section-title">Fournitures à déposer</div>
                </div>
                @foreach($fournitures as $item)
                    <div class="is-info-row" style="flex-direction:row;align-items:center;justify-content:space-between;gap:12px;">
                        <span class="is-info-val">{{ $item['category']->name }}</span>
                        @if(!empty($item['satisfied_in_kind']))
                            <span style="display:flex;align-items:center;gap:8px;">
                                <span class="is-badge success"><i class="fas fa-check"></i> Déposé</span>
                                {{-- Le retour en arriere. Sans lui, une case cochee par erreur
                                     laissait le frais a zero pour toujours, et la caisse ne
                                     pouvait plus l'encaisser. Il disparait des qu'un paiement
                                     valide existe : la situation comptable est alors etablie. --}}
                                @if(!empty($item['can_unmark_in_kind']))
                                    <form method="POST"
                                          action="{{ route('esbtp.inscriptions.in-kind-deposits.destroy', [$inscription, $item['category']]) }}"
                                          onsubmit="return confirm('Annuler le dépôt en nature de « {{ $item['category']->name }} » ? Ce frais redeviendra dû et encaissable.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-acasi secondary btn-sm" title="Ce frais redeviendra dû">
                                            <i class="fas fa-rotate-left"></i> Annuler le dépôt
                                        </button>
                                    </form>
                                @endif
                            </span>
                        @elseif(!empty($item['can_mark_in_kind']))
                            <form method="POST" action="{{ route('esbtp.inscriptions.in-kind-deposits.store', [$inscription, $item['category']]) }}">
                                @csrf
                                <button type="submit" class="btn-acasi primary btn-sm">Marquer déposé</button>
                            </form>
                        @elseif(!empty($item['paiement_bloquant']))
                            @php $bloquant = $item['paiement_bloquant']; @endphp
                            {{-- Un versement validé existe sur ce frais : il a été encaissé,
                                 pas déposé. Tant qu'il est là, marquer « déposé » est refusé,
                                 sinon le même frais serait réglé deux fois. On le dit, et on
                                 offre le geste qui débloque à qui en a le droit. --}}
                            <span style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                                <span class="is-badge warning" title="Reçu {{ $bloquant->numero_recu }} du {{ optional($bloquant->date_paiement)->format('d/m/Y') }}">
                                    <i class="fas fa-receipt"></i> Encaissé (reçu {{ $bloquant->numero_recu }})
                                </span>
                                @can('paiements.delete')
                                    <button type="button" class="btn-acasi secondary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#modalSupprimerVersement{{ $bloquant->id }}"
                                            title="Retirer ce versement pour pouvoir marquer le dépôt">
                                        <i class="fas fa-trash"></i> Supprimer le versement
                                    </button>
                                @endcan
                            </span>
                        @else
                            <span class="is-badge secondary">Non déposé</span>
                        @endif
                    </div>
                @endforeach
                @if($fournitures->contains(fn ($item) => !empty($item['paiement_bloquant']) && empty($item['satisfied_in_kind'])))
                    <p style="margin:10px 0 0;font-size:.78rem;color:#64748b;">
                        Un frais encaissé ne peut pas être marqué déposé : le versement doit d'abord être supprimé, avec un motif.
                    </p>
                @endif
            </div>
        </div>
    @endif
@endcan

@can('paiements.delete')
    @foreach(collect($feeCategoriesWithRules ?? [])->filter(fn ($item) => !empty($item['paiement_bloquant']) && empty($item['satisfied_in_kind'])) as $item)
        @php $bloquant = $item['paiement_bloquant']; @endphp
        <div class="modal fade" id="modalSupprimerVersement{{ $bloquant->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:15px; border:none; box-shadow:0 10px 40px rgba(0,0,0,.2);">
                    <div class="modal-header" style="background:linear-gradient(135deg, #ef4444, #dc2626); color:#fff; border-radius:15px 15px 0 0; padding:1.25rem 1.5rem; border:none;">
                        <h5 class="modal-title fw-bold"><i class="fas fa-trash me-2"></i>Supprimer le versement — {{ $item['category']->name }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('esbtp.paiements.destroy', $bloquant->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="retour" value="{{ request()->getRequestUri() }}">
                        <div class="modal-body" style="padding:1.5rem;">
                            <div style="background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px; padding:12px 16px; margin-bottom:16px; color:#991b1b; font-size:.88rem;">
                                <strong>Le reçu {{ $bloquant->numero_recu }} du {{ optional($bloquant->date_paiement)->format('d/m/Y') }} ne sera plus valable.</strong>
                                Le frais « {{ $item['category']->name }} » redeviendra dû, et pourra alors être marqué déposé. Votre nom, la date et le motif restent lisibles au journal d'audit.
                            </div>
                            <div x-data="{ count: 0 }">
                                <label for="motif_suppression_{{ $bloquant->id }}" class="form-label fw-semibold" style="font-size:.88rem;">
                                    Motif de la suppression <span class="text-danger">*</span>
                                    <span style="font-weight:400; color:#64748b; font-size:.78rem;">(min. 10 caractères)</span>
                                </label>
                                <textarea name="motif" id="motif_suppression_{{ $bloquant->id }}" rows="4" class="form-control" required
                                          minlength="10" maxlength="500"
                                          placeholder="Ex : Encaissé par erreur, le paquet de rames a été déposé en nature."
                                          x-on:input="count = $event.target.value.length"
                                          style="border:2px solid #dee2e6; border-radius:10px; resize:none;"></textarea>
                                <div style="display:flex; justify-content:flex-end; margin-top:6px; font-size:.74rem; color:#94a3b8;">
                                    <span x-text="count + ' / 500'" :style="count < 10 ? 'color:#dc2626;font-weight:600' : ''">0 / 500</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="background:#f8f9fa; border-radius:0 0 15px 15px; padding:1rem 1.5rem; border:none;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px; font-weight:600;">
                                <i class="fas fa-times me-1"></i>Annuler
                            </button>
                            <button type="submit" class="btn btn-danger" style="border-radius:8px; font-weight:600;">
                                <i class="fas fa-trash me-1"></i>Supprimer le versement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endcan
