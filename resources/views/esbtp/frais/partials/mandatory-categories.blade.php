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

        <!-- Configuration des montants par statut d'affectation -->
        <div class="form-group-moderne">
            <label class="form-label-moderne">
                <i class="fas fa-coins me-1"></i>Montants selon le statut d'affectation (FCFA)
            </label>
            <div style="font-size: var(--text-small); color: var(--text-muted); margin-bottom: var(--space-md);">
                <i class="fas fa-info-circle me-1"></i>
                Configurez des tarifs différents selon le statut d'affectation gouvernementale (MESRS)
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-md);">
                <!-- Étudiants Affectés -->
                <div class="card-moderne" style="border-left: 4px solid var(--success); padding: var(--space-md);">
                    <div style="display: flex; align-items: center; margin-bottom: var(--space-sm);">
                        <div style="width: 24px; height: 24px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: var(--space-sm);">
                            <i class="fas fa-check" style="color: white; font-size: 12px;"></i>
                        </div>
                        <div style="font-weight: 600; color: var(--success); font-size: var(--text-small);">
                            AFFECTÉS
                        </div>
                    </div>
                    <div style="position: relative; margin-bottom: var(--space-sm);">
                        <input type="number" 
                               id="amount_affecte_{{ $category->id }}" 
                               name="categories[{{ $category->id }}][amount_affecte]" 
                               class="form-input-moderne" 
                               value="{{ $existingConfig && $existingConfig->amount_affecte ? number_format($existingConfig->amount_affecte, 0, '', '') : '' }}"
                               min="0" 
                               step="1000"
                               placeholder="Montant affectés"
                               style="padding-right: 60px; font-size: var(--text-small);">
                        <div style="position: absolute; right: var(--space-sm); top: 50%; transform: translateY(-50%); font-size: 10px; color: var(--text-secondary); font-weight: 600;">
                            FCFA
                        </div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted);">
                        Subvention étatique possible
                    </div>
                </div>

                <!-- Étudiants Réaffectés -->
                <div class="card-moderne" style="border-left: 4px solid var(--warning); padding: var(--space-md);">
                    <div style="display: flex; align-items: center; margin-bottom: var(--space-sm);">
                        <div style="width: 24px; height: 24px; background: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: var(--space-sm);">
                            <i class="fas fa-sync-alt" style="color: white; font-size: 12px;"></i>
                        </div>
                        <div style="font-weight: 600; color: var(--warning); font-size: var(--text-small);">
                            RÉAFFECTÉS
                        </div>
                    </div>
                    <div style="position: relative; margin-bottom: var(--space-sm);">
                        <input type="number" 
                               id="amount_reaffecte_{{ $category->id }}" 
                               name="categories[{{ $category->id }}][amount_reaffecte]" 
                               class="form-input-moderne" 
                               value="{{ $existingConfig && $existingConfig->amount_reaffecte ? number_format($existingConfig->amount_reaffecte, 0, '', '') : '' }}"
                               min="0" 
                               step="1000"
                               placeholder="Montant réaffectés"
                               style="padding-right: 60px; font-size: var(--text-small);">
                        <div style="position: absolute; right: var(--space-sm); top: 50%; transform: translateY(-50%); font-size: 10px; color: var(--text-secondary); font-weight: 600;">
                            FCFA
                        </div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted);">
                        Subvention maintenue après réaffectation
                    </div>
                </div>

                <!-- Étudiants Non Affectés -->
                <div class="card-moderne" style="border-left: 4px solid var(--danger); padding: var(--space-md);">
                    <div style="display: flex; align-items: center; margin-bottom: var(--space-sm);">
                        <div style="width: 24px; height: 24px; background: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: var(--space-sm);">
                            <i class="fas fa-times" style="color: white; font-size: 12px;"></i>
                        </div>
                        <div style="font-weight: 600; color: var(--danger); font-size: var(--text-small);">
                            NON AFFECTÉS
                        </div>
                    </div>
                    <div style="position: relative; margin-bottom: var(--space-sm);">
                        <input type="number" 
                               id="amount_non_affecte_{{ $category->id }}" 
                               name="categories[{{ $category->id }}][amount_non_affecte]" 
                               class="form-input-moderne" 
                               value="{{ $existingConfig && $existingConfig->amount_non_affecte ? number_format($existingConfig->amount_non_affecte, 0, '', '') : '' }}"
                               min="0" 
                               step="1000"
                               placeholder="Montant non affectés"
                               style="padding-right: 60px; font-size: var(--text-small);">
                        <div style="position: absolute; right: var(--space-sm); top: 50%; transform: translateY(-50%); font-size: 10px; color: var(--text-secondary); font-weight: 600;">
                            FCFA
                        </div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted);">
                        Tarif complet sans subvention
                    </div>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div style="display: flex; gap: var(--space-sm); margin-top: var(--space-md);">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToAll({{ $category->id }}, 'amount_affecte')" title="Copier le montant Affectés sur tous">
                    <i class="fas fa-copy"></i> Copier Affectés
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="copyToAll({{ $category->id }}, 'amount_reaffecte')" title="Copier le montant Réaffectés sur tous">
                    <i class="fas fa-copy"></i> Copier Réaffectés
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="copyToAll({{ $category->id }}, 'amount_non_affecte')" title="Copier le montant Non Affectés sur tous">
                    <i class="fas fa-copy"></i> Copier Non Aff.
                </button>
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