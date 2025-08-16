{{-- Vue partielle pour les catégories de frais obligatoires --}}
@foreach($categories as $category)
    @php
        // Chercher la configuration existante pour cette catégorie
        $existingConfig = $configurations->where('frais_category_id', $category->id)->first();
        $currentAmount = $existingConfig ? $existingConfig->amount : $category->default_amount;
    @endphp
    
    <div class="config-category-card mandatory animate-slide-up">
        <!-- Header de la catégorie -->
        <div style="display: flex; align-items: center; margin-bottom: var(--space-md);">
            <div style="width: 40px; height: 40px; background: var(--danger); border-radius: var(--radius-circle); display: flex; align-items: center; justify-content: center; margin-right: var(--space-md);">
                <i class="{{ $category->icon ?? 'fas fa-money-bill' }}" style="color: white; font-size: 16px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: var(--space-xs);">
                    {{ $category->name }}
                    <span class="badge danger" style="font-size: var(--text-small); margin-left: var(--space-sm);">Obligatoire</span>
                </div>
                <div style="font-size: var(--text-small); color: var(--text-secondary);">
                    {{ $category->description }}
                </div>
            </div>
        </div>

        <!-- Configuration du montant -->
        <div class="form-group-moderne">
            <label class="form-label-moderne" for="amount_{{ $category->id }}">
                <i class="fas fa-coins me-1"></i>Montant (FCFA)
            </label>
            <div style="position: relative;">
                <input type="number" 
                       id="amount_{{ $category->id }}" 
                       name="categories[{{ $category->id }}][amount]" 
                       class="form-input-moderne" 
                       value="{{ number_format($currentAmount, 0, '', '') }}"
                       min="0" 
                       step="1000"
                       placeholder="Saisir le montant"
                       style="padding-right: 80px;">
                <div style="position: absolute; right: var(--space-md); top: 50%; transform: translateY(-50%); font-size: var(--text-small); color: var(--text-secondary); font-weight: 600;">
                    FCFA
                </div>
            </div>
            <div style="font-size: var(--text-small); color: var(--text-muted); margin-top: var(--space-xs);">
                <i class="fas fa-info-circle me-1"></i>
                Montant par défaut: {{ number_format($category->default_amount, 0, '', ' ') }} FCFA
            </div>
        </div>

        <!-- Échéance de paiement -->
        <div class="form-group-moderne" style="margin-bottom: 0;">
            <label class="form-label-moderne" for="deadline_{{ $category->id }}">
                <i class="fas fa-calendar-alt me-1"></i>Échéance (jours)
            </label>
            <div style="display: grid; grid-template-columns: 1fr auto; gap: var(--space-sm); align-items: center;">
                <input type="number" 
                       id="deadline_{{ $category->id }}" 
                       name="categories[{{ $category->id }}][deadline_days]" 
                       class="form-input-moderne" 
                       value="{{ $existingConfig ? $existingConfig->payment_deadline_days : $category->payment_deadline_days }}"
                       min="1" 
                       max="365"
                       placeholder="Nombre de jours">
                <span style="font-size: var(--text-small); color: var(--text-secondary); white-space: nowrap;">
                    jours après inscription
                </span>
            </div>
        </div>

        <!-- Statut actuel -->
        @if($existingConfig)
            <div style="margin-top: var(--space-md); padding: var(--space-sm); background: rgba(16, 185, 129, 0.1); border-radius: var(--radius-small); border-left: 3px solid var(--success);">
                <div style="font-size: var(--text-small); color: var(--success); font-weight: 600;">
                    <i class="fas fa-check-circle me-1"></i>Configuré depuis le {{ $existingConfig->created_at->format('d/m/Y') }}
                </div>
            </div>
        @else
            <div style="margin-top: var(--space-md); padding: var(--space-sm); background: rgba(239, 68, 68, 0.1); border-radius: var(--radius-small); border-left: 3px solid var(--danger);">
                <div style="font-size: var(--text-small); color: var(--danger); font-weight: 600;">
                    <i class="fas fa-exclamation-triangle me-1"></i>Configuration requise
                </div>
            </div>
        @endif
    </div>
@endforeach

@if($categories->count() === 0)
    <div style="grid-column: span 2; text-align: center; padding: var(--space-xl);">
        <i class="fas fa-info-circle" style="font-size: 48px; color: var(--neutral); margin-bottom: var(--space-md);"></i>
        <h5 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Aucun frais obligatoire trouvé</h5>
        <p style="color: var(--text-muted); font-size: var(--text-small);">
            Vérifiez que vous avez des catégories obligatoires actives dans le système.
        </p>
    </div>
@endif