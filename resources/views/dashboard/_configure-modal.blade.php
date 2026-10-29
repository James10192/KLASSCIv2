{{--
    Modal "Configurer mon dashboard" (Lot 9)
    Reçoit :
    - $availableGrouped : Collection<string,Collection<widget>> (groupés par 'group')
    - $activeKeys       : list<string> des clés actuellement actives (dans l'ordre)
--}}
<div class="modal fade" id="dwConfigureModal" tabindex="-1" aria-labelledby="dwConfigureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0a3d8f, #0453cb); color: #fff; border-radius: 14px 14px 0 0; border-bottom: none;">
                <div style="display: flex; align-items: center; gap: .75rem;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="dwConfigureModalLabel" style="color:#fff;font-weight:700;margin:0;">Configurer mon dashboard</h5>
                        <p style="color:rgba(255,255,255,.7);font-size:.8rem;margin:0;">Choisissez les widgets visibles et leur ordre</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="dwConfigureForm" action="{{ route('dashboard.widgets.update') }}" method="POST">
                @csrf

                <div class="modal-body" style="padding: 1.5rem;">
                    @php
                        // Réordonner : widgets actifs (dans l'ordre $activeKeys), puis les autres
                        $allWidgets = $availableGrouped->flatten(1);
                        $orderedActive = collect($activeKeys)
                            ->map(fn ($k) => $allWidgets->firstWhere('key', $k))
                            ->filter();
                        $inactive = $allWidgets->reject(fn ($w) => in_array($w['key'], $activeKeys, true));
                        $orderedAll = $orderedActive->concat($inactive);
                    @endphp

                    @if ($availableGrouped->isEmpty())
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i>
                            Aucun widget disponible avec vos permissions actuelles.
                        </div>
                    @else
                        <p style="font-size:.85rem;color:#64748b;margin-bottom:1rem;">
                            <i class="fas fa-info-circle"></i>
                            Cochez les widgets que vous souhaitez voir, et utilisez les flèches pour les réorganiser.
                            Les widgets désactivés disparaissent automatiquement si vous perdez la permission associée.
                        </p>

                        <div class="dw-modal-group">
                            @foreach ($orderedAll as $widget)
                                @php
                                    $isActive = in_array($widget['key'], $activeKeys, true);
                                @endphp
                                <div class="dw-toggle-row {{ $isActive ? 'is-active' : '' }}" data-key="{{ $widget['key'] }}">
                                    <div style="display:flex;align-items:center;gap:.65rem;">
                                        <button type="button" class="dw-sort-btn btn btn-sm btn-light" data-direction="up" title="Monter" style="padding:.15rem .35rem;">
                                            <i class="fas fa-chevron-up"></i>
                                        </button>
                                        <button type="button" class="dw-sort-btn btn btn-sm btn-light" data-direction="down" title="Descendre" style="padding:.15rem .35rem;">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>

                                    <div class="dw-toggle-info">
                                        <div class="dw-toggle-info-title">
                                            <i class="fas {{ $widget['icon'] ?? 'fa-puzzle-piece' }}" style="color:#0453cb;margin-right:.35rem;"></i>
                                            {{ $widget['label'] }}
                                            <span class="badge" style="background:#eef4ff;color:#0453cb;font-size:.65rem;font-weight:500;margin-left:.35rem;">{{ $widget['group'] }}</span>
                                        </div>
                                        <div class="dw-toggle-info-desc">{{ $widget['description'] ?? '' }}</div>
                                    </div>

                                    <label class="dw-toggle-switch">
                                        <input type="checkbox" name="widgets[]" value="{{ $widget['key'] }}" {{ $isActive ? 'checked' : '' }}>
                                        <span class="dw-toggle-slider"></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
                    <button type="button" class="dw-btn" data-bs-dismiss="modal" style="background:#f1f5f9;color:#1e293b;">
                        Annuler
                    </button>
                    <button type="submit" class="dw-btn dw-btn--white" style="background:#0453cb;color:#fff;border-color:#0453cb;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
