@foreach($categories as $category)
    <div class="card-moderne resultat-card animate-slide-up" 
         style="border: 2px solid {{ $category->is_mandatory ? 'var(--danger)' : 'var(--accent-blue)' }};">
        
        <!-- En-tête catégorie -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
            <div style="display: flex; align-items: center;">
                @if($category->icon)
                    <i class="{{ $category->icon }}" style="color: {{ $category->is_mandatory ? 'var(--danger)' : 'var(--accent-blue)' }}; margin-right: var(--space-sm); font-size: 18px;"></i>
                @endif
                <div class="font-bold" style="color: var(--text-primary);">{{ $category->name }}</div>
            </div>
            <span class="badge {{ $category->is_mandatory ? 'danger' : 'primary' }}">
                {{ $category->is_mandatory ? 'Obligatoire' : 'Optionnel' }}
            </span>
        </div>

        @php
            $existingRule = $rules->where('frais_category_id', $category->id)->first();
        @endphp
        
        <!-- Champs de configuration -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-bottom: var(--space-md);">
            <div>
                <label style="font-size: var(--text-small); font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs); display: block;">
                    Montant (FCFA)
                </label>
                <input type="number" 
                       style="width: 100%; padding: var(--space-sm); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal);"
                       name="categories[{{ $category->id }}][amount]" 
                       value="{{ $existingRule->amount ?? $category->default_amount }}" 
                       min="0" 
                       step="0.01"
                       {{ $category->is_mandatory ? 'required' : '' }}>
            </div>
            <div>
                <label style="font-size: var(--text-small); font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs); display: block;">
                    Échéance (jours)
                </label>
                <input type="number" 
                       style="width: 100%; padding: var(--space-sm); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal);"
                       name="categories[{{ $category->id }}][deadline_days]" 
                       value="{{ $existingRule->payment_deadline_days ?? $category->payment_deadline_days }}" 
                       min="1" 
                       max="365"
                       {{ $category->is_mandatory ? 'required' : '' }}>
            </div>
        </div>
        
        @if($category->description)
            <div style="padding: var(--space-sm); background: rgba(107, 114, 128, 0.1); border-radius: var(--radius-small); margin-top: var(--space-sm);">
                <div style="font-size: var(--text-small); color: var(--text-secondary);">{{ $category->description }}</div>
            </div>
        @endif
    </div>
@endforeach