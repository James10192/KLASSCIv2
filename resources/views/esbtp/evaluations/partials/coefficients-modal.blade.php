{{--
    Partial : modal coefficients (chargé en AJAX depuis evaluations/index)
    Variables attendues : $cards (Collection), $anneeUniversitaire (model|null)
--}}
<div class="coeff-modal-header">
    <div>
        <h5 class="mb-1"><i class="fas fa-sliders-h me-2"></i>Coefficients par combinaison</h5>
        <div class="text-muted small">
            <i class="fas fa-calendar-alt me-1"></i>Année universitaire courante
        </div>
    </div>
    <span class="badge bg-primary rounded-pill px-3 py-2">
        <i class="fas fa-layer-group me-1"></i>{{ $cards->count() }} combinaison{{ $cards->count() > 1 ? 's' : '' }}
    </span>
</div>

<div class="coeff-modal-intro">
    <div class="intro-icon">
        <i class="fas fa-lightbulb"></i>
    </div>
    <div>
        <div class="intro-title">Ces coefficients sont utilisés pour les bulletins</div>
        <div class="intro-text">Complétez chaque combinaison filière / niveau pour éviter les blocages lors des previews et PDFs.</div>
    </div>
</div>

@if($cards->isEmpty())
    <div class="coeff-empty-state">
        <i class="fas fa-inbox fa-2x mb-2 text-muted"></i>
        <div class="fw-semibold">Aucune combinaison filière/niveau trouvée.</div>
        <div class="text-muted small mt-1">Créez d'abord des classes avec une filière et un niveau associés.</div>
    </div>
@else
    <div class="coeff-cards">
        @foreach($cards as $cardIndex => $card)
            @php
                $statusClass = match($card['status']) {
                    'complete' => 'status-complete',
                    'partial'  => 'status-partial',
                    'missing'  => 'status-missing',
                    default    => 'status-empty',
                };
                $statusLabel = match($card['status']) {
                    'complete' => 'Complet',
                    'partial'  => 'Incomplet',
                    'missing'  => 'Non configuré',
                    'empty'    => 'Sans matières',
                    default    => 'Inconnu',
                };
                $statusIcon = match($card['status']) {
                    'complete' => 'fas fa-check-circle',
                    'partial'  => 'fas fa-exclamation-circle',
                    'missing'  => 'fas fa-times-circle',
                    default    => 'fas fa-minus-circle',
                };
            @endphp
            <form class="coeff-card {{ $statusClass }}-card"
                  data-filiere-id="{{ $card['filiere']->id }}"
                  data-niveau-id="{{ $card['niveau']->id }}">

                {{-- En-tête card --}}
                <div class="coeff-card-header">
                    <div class="coeff-card-title">
                        <div class="coeff-ordinal">{{ $cardIndex + 1 }}</div>
                        <div class="coeff-combo-info">
                            <div class="coeff-combo">
                                <span class="badge bg-primary">{{ $card['filiere']->name }}</span>
                                <span class="badge bg-secondary">{{ $card['niveau']->name }}</span>
                            </div>
                            <div class="coeff-progress-text">
                                <i class="fas fa-tasks me-1"></i>
                                {{ $card['configured'] }}/{{ $card['total'] }} matière{{ $card['total'] > 1 ? 's' : '' }} configurée{{ $card['configured'] > 1 ? 's' : '' }}
                            </div>
                        </div>
                    </div>
                    <span class="coeff-status {{ $statusClass }}">
                        <i class="{{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                    </span>
                </div>

                {{-- Barre de progression --}}
                @if($card['total'] > 0)
                    @php $pct = round(($card['configured'] / $card['total']) * 100); @endphp
                    <div class="coeff-progress-bar-wrap">
                        <div class="coeff-progress-bar" style="width: {{ $pct }}%"
                             data-status="{{ $card['status'] }}"></div>
                    </div>
                @endif

                {{-- Corps card --}}
                <div class="coeff-card-body">
                    @if($card['total'] === 0)
                        <div class="coeff-alert coeff-alert-empty">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucune matière liée à cette combinaison. Ajoutez d'abord les matières dans la classe.
                        </div>
                    @else
                        @if(in_array($card['status'], ['missing', 'partial'], true))
                            <div class="coeff-alert coeff-alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Complétez les coefficients manquants pour activer la génération des bulletins.
                            </div>
                        @endif

                        <div class="coeff-grid">
                            @foreach($card['matieres'] as $matiere)
                                @php $hasValue = !is_null($matiere['coefficient']) && $matiere['coefficient'] !== ''; @endphp
                                <div class="coeff-row {{ $hasValue ? 'has-value' : 'missing-value' }}">
                                    <div class="coeff-matiere-info">
                                        <div class="coeff-matiere-name" title="{{ $matiere['name'] }}">
                                            {{ $matiere['name'] }}
                                        </div>
                                        @if(!empty($matiere['code']))
                                            <div class="coeff-matiere-code">{{ $matiere['code'] }}</div>
                                        @endif
                                    </div>
                                    <div class="coeff-input-wrap">
                                        @if($hasValue)
                                            <span class="coeff-current-badge">{{ $matiere['coefficient'] }}</span>
                                        @endif
                                        <input type="number"
                                               name="coefficients[{{ $matiere['id'] }}]"
                                               value="{{ $matiere['coefficient'] }}"
                                               step="0.1"
                                               min="0.1"
                                               placeholder="—"
                                               class="form-control coeff-input"
                                               aria-label="Coefficient {{ $matiere['name'] }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Pied card --}}
                @if($card['total'] > 0)
                    <div class="coeff-card-footer">
                        <div class="coeff-save-feedback d-none">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            <span class="text-success small fw-semibold">Enregistré</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm coeff-save-btn">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                @endif
            </form>
        @endforeach
    </div>
@endif
