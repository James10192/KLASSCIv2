{{-- Catégories de frais obligatoires — rendu dans modal #configurationModal (chargé via AJAX) --}}
@foreach($categories as $category)
    @php
        $existingConfig = $configurations->where('frais_category_id', $category->id)->first();
        $isConfigured = (bool) $existingConfig;
        $echeancierUrl = $existingConfig
            ? route('esbtp.comptabilite.echeanciers.index', [
                'scope_type' => 'configuration',
                'scope_id' => $existingConfig->id,
                'affectation_status' => 'all',
            ])
            : route('esbtp.comptabilite.echeanciers.index', [
                'filiere_id' => $filiereId,
                'niveau_id' => $niveauId,
                'frais_category_id' => $category->id,
                'affectation_status' => 'all',
            ]);
    @endphp

    <div class="fc-cat-row">
        {{-- Header catégorie --}}
        <div class="fc-cat-head">
            <div class="fc-cat-icon">
                <i class="{{ $category->icon ?? 'fas fa-money-bill' }}"></i>
            </div>
            <div class="fc-cat-meta">
                <div class="fc-cat-name">
                    {{ $category->name }}
                    <span class="fc-cat-pill"><i class="fas fa-asterisk"></i>Obligatoire</span>
                </div>
                @if($category->description)
                    <div class="fc-cat-desc">{{ $category->description }}</div>
                @endif
                <div style="margin-top:.45rem;">
                    <a href="{{ $echeancierUrl }}" target="_blank" class="fc-copy-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                        <i class="fas fa-calendar-check"></i>
                        Gérer échéancier
                    </a>
                </div>
            </div>
        </div>

        {{-- Section Montants par statut --}}
        <div class="fc-cat-section">
            <div class="fc-cat-section-label">
                <i class="fas fa-coins"></i>
                Montants selon le statut d'affectation (FCFA)
            </div>
            <div class="fc-cat-section-hint">
                <i class="fas fa-info-circle"></i>
                Configurez des tarifs différents selon le statut d'affectation gouvernementale (MESRS)
            </div>

            <div class="fc-statuts">
                {{-- Affectés --}}
                <div class="fc-statut is-affecte">
                    <div class="fc-statut-head">
                        <span class="fc-statut-dot"><i class="fas fa-check"></i></span>
                        <span class="fc-statut-label">Affectés</span>
                    </div>
                    <div class="fc-input-wrap">
                        <input type="number"
                               id="amount_affecte_{{ $category->id }}"
                               name="categories[{{ $category->id }}][amount_affecte]"
                               class="fc-input"
                               value="{{ $existingConfig && $existingConfig->amount_affecte ? number_format($existingConfig->amount_affecte, 0, '', '') : '' }}"
                               min="0" step="1000"
                               placeholder="0">
                        <span class="fc-input-suffix">FCFA</span>
                    </div>
                    <div class="fc-statut-hint">Subvention étatique possible</div>
                </div>

                {{-- Réaffectés --}}
                <div class="fc-statut is-reaffecte">
                    <div class="fc-statut-head">
                        <span class="fc-statut-dot"><i class="fas fa-sync-alt"></i></span>
                        <span class="fc-statut-label">Réaffectés</span>
                    </div>
                    <div class="fc-input-wrap">
                        <input type="number"
                               id="amount_reaffecte_{{ $category->id }}"
                               name="categories[{{ $category->id }}][amount_reaffecte]"
                               class="fc-input"
                               value="{{ $existingConfig && $existingConfig->amount_reaffecte ? number_format($existingConfig->amount_reaffecte, 0, '', '') : '' }}"
                               min="0" step="1000"
                               placeholder="0">
                        <span class="fc-input-suffix">FCFA</span>
                    </div>
                    <div class="fc-statut-hint">Subvention maintenue après réaffectation</div>
                </div>

                {{-- Non affectés --}}
                <div class="fc-statut is-non-affecte">
                    <div class="fc-statut-head">
                        <span class="fc-statut-dot"><i class="fas fa-times"></i></span>
                        <span class="fc-statut-label">Non affectés</span>
                    </div>
                    <div class="fc-input-wrap">
                        <input type="number"
                               id="amount_non_affecte_{{ $category->id }}"
                               name="categories[{{ $category->id }}][amount_non_affecte]"
                               class="fc-input"
                               value="{{ $existingConfig && $existingConfig->amount_non_affecte ? number_format($existingConfig->amount_non_affecte, 0, '', '') : '' }}"
                               min="0" step="1000"
                               placeholder="0">
                        <span class="fc-input-suffix">FCFA</span>
                    </div>
                    <div class="fc-statut-hint">Tarif complet sans subvention</div>
                </div>
            </div>

            {{-- Actions rapides copier --}}
            <div class="fc-copy-actions">
                <button type="button" class="fc-copy-btn" onclick="copyToAll({{ $category->id }}, 'amount_affecte')" title="Copier le montant Affectés sur les autres statuts">
                    <i class="fas fa-copy"></i>Copier Affectés
                </button>
                <button type="button" class="fc-copy-btn" onclick="copyToAll({{ $category->id }}, 'amount_reaffecte')" title="Copier le montant Réaffectés sur les autres statuts">
                    <i class="fas fa-copy"></i>Copier Réaffectés
                </button>
                <button type="button" class="fc-copy-btn" onclick="copyToAll({{ $category->id }}, 'amount_non_affecte')" title="Copier le montant Non Affectés sur les autres statuts">
                    <i class="fas fa-copy"></i>Copier Non Aff.
                </button>
            </div>
        </div>

        {{-- Section Échéance --}}
        <div class="fc-cat-section">
            <label class="fc-cat-section-label" for="deadline_{{ $category->id }}">
                <i class="fas fa-calendar-alt"></i>
                Échéance (jours après inscription)
            </label>
            <div class="fc-deadline">
                <div class="fc-input-wrap" style="margin-bottom: 0;">
                    <input type="number"
                           id="deadline_{{ $category->id }}"
                           name="categories[{{ $category->id }}][deadline_days]"
                           class="fc-input"
                           style="padding-right: .65rem;"
                           value="{{ $existingConfig ? $existingConfig->payment_deadline_days : $category->payment_deadline_days }}"
                           min="1" max="365"
                           placeholder="{{ $category->payment_deadline_days ?? 30 }}">
                </div>
                <span class="fc-deadline-suffix">jours</span>
            </div>
            <div class="fc-cat-section-hint" style="margin-top: .4rem; margin-bottom: 0;">
                <i class="fas fa-info-circle"></i>
                Défaut catégorie : {{ $category->payment_deadline_days ?? 30 }} jours. Laissez vide pour utiliser le défaut.
            </div>
        </div>

        {{-- Statut --}}
        @if($isConfigured)
            <div class="fc-cat-status is-done">
                <i class="fas fa-check-circle"></i>
                Configuré depuis le {{ $existingConfig->created_at->format('d/m/Y') }}
            </div>
        @else
            <div class="fc-cat-status is-todo">
                <i class="fas fa-exclamation-triangle"></i>
                Configuration requise
            </div>
        @endif
    </div>
@endforeach

@if($categories->count() === 0)
    <div class="fc-empty">
        <i class="fas fa-inbox"></i>
        <p><strong>Aucun frais obligatoire trouvé.</strong><br>
        Vérifiez que vous avez des catégories obligatoires actives dans le système.</p>
    </div>
@endif
