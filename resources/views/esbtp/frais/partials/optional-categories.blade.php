{{-- Vue partielle pour les catégories de frais optionnels avec leurs options --}}
@foreach($categories as $category)
    @php
        // Chercher la configuration existante pour cette catégorie
        $existingConfig = $configurations->where('frais_category_id', $category->id)->first();
        
        // Récupérer les options disponibles pour cette catégorie
        // Pour l'instant, créer une collection vide car les options sont liées via configuration
        $options = collect();
        
        // Récupérer les options via les configurations de cette catégorie et cette classe
        $categoryConfigs = $configurations->where('frais_category_id', $category->id);
        foreach ($categoryConfigs as $config) {
            if (method_exists($config, 'options')) {
                $configOptions = $config->options()->active()->ordered()->get();
                $options = $options->merge($configOptions);
            }
        }
    @endphp
    
    <div class="config-category-card optional animate-slide-up" style="margin-bottom: var(--space-lg);">
        <!-- Header de la catégorie -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-lg);">
            <div style="display: flex; align-items: center;">
                <div style="width: 40px; height: 40px; background: var(--success); border-radius: var(--radius-circle); display: flex; align-items: center; justify-content: center; margin-right: var(--space-md);">
                    <i class="{{ $category->icon ?? 'fas fa-cogs' }}" style="color: white; font-size: 16px;"></i>
                </div>
                <div>
                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: var(--space-xs);">
                        {{ $category->name }}
                        <span class="badge success" style="font-size: var(--text-small); margin-left: var(--space-sm);">Optionnel</span>
                    </div>
                    <div style="font-size: var(--text-small); color: var(--text-secondary);">
                        {{ $category->description }}
                    </div>
                </div>
            </div>
            
            <!-- Bouton pour ajouter une option -->
            <button type="button" 
                    class="btn-acasi primary" 
                    style="font-size: var(--text-small); padding: var(--space-xs) var(--space-sm);"
                    onclick="addNewOption({{ $category->id }}, '{{ $category->name }}')">
                <i class="fas fa-plus"></i>
                Ajouter Option
            </button>
        </div>

        <!-- Description des types d'options selon la catégorie -->
        <div style="margin-bottom: var(--space-md); padding: var(--space-sm); background: rgba(6, 182, 212, 0.1); border-radius: var(--radius-small); border-left: 3px solid var(--accent-blue);">
            <div style="font-size: var(--text-small); color: var(--text-primary);">
                <i class="fas fa-lightbulb me-1" style="color: var(--accent-blue);"></i>
                @switch($category->code)
                    @case('TRANSPORT')
                        <strong>Transport :</strong> Configurez les différents arrêts de bus avec leurs tarifs spécifiques
                        @break
                    @case('CANTINE')
                        <strong>Cantine :</strong> Configurez les différents menus ou formules de restauration
                        @break
                    @case('DOCUMENTATION')
                        <strong>Documentation :</strong> Configurez les packs de documents par filière ou niveau
                        @break
                    @default
                        <strong>Options :</strong> Configurez les différentes variantes disponibles pour cette catégorie
                @endswitch
            </div>
        </div>

        <!-- Liste des options existantes -->
        <div style="margin-bottom: var(--space-md);">
            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-sm); font-size: var(--text-small);">
                <i class="fas fa-list me-1"></i>OPTIONS DISPONIBLES ({{ $options->count() }})
            </div>
            
            @if($options->count() > 0)
                <div id="options-list-{{ $category->id }}">
                    @foreach($options as $option)
                        <div class="config-option-item" data-option-id="{{ $option->id }}">
                            <div class="option-details">
                                <div class="option-name">{{ $option->name }}</div>
                                <div class="option-description">
                                    {{ $option->description }}
                                    @if($option->capacity_limit)
                                        • Limite: {{ $option->capacity_limit }} places
                                    @endif
                                    @if($option->requires_approval)
                                        • <span style="color: var(--warning);">Requiert approbation</span>
                                    @endif
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: var(--space-sm);">
                                <div class="option-price">{{ number_format($option->calculateFinalPrice(), 0, '', ' ') }} FCFA</div>
                                <div style="display: flex; gap: var(--space-xs);">
                                    <button type="button" 
                                            class="btn-table-action primary" 
                                            onclick="editOption({{ $option->id }})"
                                            title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn-table-action danger" 
                                            onclick="deleteOption({{ $option->id }})"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: var(--space-lg); border: 2px dashed #d1d5db; border-radius: var(--radius-small); background: var(--background);">
                    <i class="fas fa-plus-circle" style="font-size: 32px; color: var(--neutral); margin-bottom: var(--space-sm);"></i>
                    <div style="color: var(--text-secondary); font-weight: 500; margin-bottom: var(--space-xs);">
                        Aucune option configurée
                    </div>
                    <div style="font-size: var(--text-small); color: var(--text-muted);">
                        Cliquez sur "Ajouter Option" pour créer la première option
                    </div>
                </div>
            @endif
        </div>

        <!-- Bouton pour ajouter une option (répété en bas) -->
        @if($options->count() > 0)
            <button type="button" 
                    class="add-option-btn" 
                    onclick="addNewOption({{ $category->id }}, '{{ $category->name }}')">
                <i class="fas fa-plus me-2"></i>Ajouter une nouvelle option
            </button>
        @endif
    </div>
@endforeach

@if($categories->count() === 0)
    <div style="text-align: center; padding: var(--space-xl);">
        <i class="fas fa-cogs" style="font-size: 48px; color: var(--neutral); margin-bottom: var(--space-md);"></i>
        <h5 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Aucun frais optionnel trouvé</h5>
        <p style="color: var(--text-muted); font-size: var(--text-small);">
            Créez des catégories optionnelles comme Transport ou Cantine pour configurer leurs options.
        </p>
    </div>
@endif

<script>
// Fonctions JavaScript pour gérer les options
function addNewOption(categoryId, categoryName) {
    // TODO: Ouvrir un modal pour créer une nouvelle option
    debugLog('Ajouter option pour catégorie:', categoryId, categoryName);
    
    // Exemple de prompt simple en attendant le modal
    const optionName = prompt(`Nom de la nouvelle option pour ${categoryName}:`);
    if (optionName) {
        const optionPrice = prompt('Prix de cette option (en FCFA):');
        if (optionPrice && !isNaN(optionPrice)) {
            // TODO: Envoyer la requête AJAX pour créer l'option
            debugLog('Créer option:', optionName, optionPrice);
        }
    }
}

function editOption(optionId) {
    // TODO: Ouvrir un modal pour modifier l'option
    debugLog('Modifier option:', optionId);
}

function deleteOption(optionId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette option ?')) {
        // TODO: Envoyer la requête AJAX pour supprimer l'option
        debugLog('Supprimer option:', optionId);
    }
}
</script>