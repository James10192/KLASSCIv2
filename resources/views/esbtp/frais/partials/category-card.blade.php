{{-- Composant réutilisable pour afficher une carte de catégorie de frais --}}
<div class="col-md-6 mb-4">
    <div class="card-moderne">
        <div class="p-md">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    @if($category->icon)
                        <i class="{{ $category->icon }} me-2" style="color: var(--{{ $category->color ?? 'primary' }});"></i>
                    @endif
                    <h6 class="mb-0 font-semibold d-inline">{{ $category->name }}</h6>
                    <p class="text-muted mb-0 mt-1">{{ $category->description }}</p>
                </div>
                <div class="text-end">
                    <div class="badge bg-{{ $category->is_mandatory ? 'danger' : 'warning' }}">
                        {{ $category->is_mandatory ? 'Obligatoire' : 'Optionnel' }}
                    </div>
                    @if($category->category_type)
                        <div class="badge bg-info mt-1">
                            {{ ucfirst($category->category_type) }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-sm-6">
                    <strong>Montant par défaut:</strong><br>
                    <span class="text-primary font-semibold">{{ number_format($category->default_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="col-sm-6">
                    <strong>Délai paiement:</strong><br>
                    <span class="text-secondary">{{ $category->payment_deadline_days }} jours</span>
                </div>
            </div>

            {{-- Affichage des variants si la catégorie en a --}}
            @if($category->variants()->exists())
                <div class="mb-3">
                    <strong>Variants disponibles:</strong>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @foreach($category->variants()->take(3) as $variant)
                            <div class="badge bg-light border text-dark">
                                <strong>{{ $variant->name }}</strong>: {{ number_format($variant->amount, 0, ',', ' ') }} FCFA
                                @if($variant->is_default)
                                    <span class="text-success">★</span>
                                @endif
                            </div>
                        @endforeach
                        @if($category->variants()->count() > 3)
                            <span class="badge bg-info">+{{ $category->variants()->count() - 3 }} autres</span>
                        @endif
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center">
                <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $category->is_active ? 'Actif' : 'Inactif' }}
                </span>
                <div>
                    <a href="{{ route('esbtp.frais.show', $category) }}" class="btn btn-sm btn-info me-1">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('esbtp.frais.edit', $category) }}" class="btn btn-sm btn-primary me-1">
                        <i class="fas fa-edit"></i>
                    </a>
                    
                    @if($category->category_type === 'academic')
                        {{-- Pour les frais académiques : lien vers la configuration par classe --}}
                        <a href="{{ route('esbtp.frais.configure') }}" class="btn btn-sm btn-outline-success" title="Configurer par classe">
                            <i class="fas fa-cogs"></i>
                        </a>
                    @else
                        {{-- Pour les services et administratifs : gestion des variants --}}
                        @if($category->variants()->exists())
                            <button class="btn btn-sm btn-outline-info me-1" 
                                    onclick="showCategoryVariants({{ $category->id }}, '{{ $category->name }}')"
                                    title="Voir tous les variants">
                                <i class="fas fa-list"></i>
                            </button>
                        @endif
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="addVariant({{ $category->id }}, '{{ $category->name }}')"
                                title="Ajouter un variant">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>