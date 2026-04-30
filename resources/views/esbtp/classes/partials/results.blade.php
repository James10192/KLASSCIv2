@if($classes->count() > 0)
    <div class="ci-grid" id="classes-grid">
        @include('esbtp.classes.partials.items', ['classes' => $classes])
    </div>

    <div id="load-more-container" class="ci-load-more-container">
        <button type="button"
                id="load-more-btn"
                class="ci-load-more-btn"
                style="display: {{ isset($hasMore) && $hasMore ? 'inline-flex' : 'none' }};"
                data-has-more="{{ isset($hasMore) ? ($hasMore ? 'true' : 'false') : 'false' }}">
            <i class="fas fa-angle-down"></i>Charger plus de classes
        </button>
        <div id="load-more-spinner" class="ci-load-more-spinner d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    </div>
@else
    <div class="ci-empty">
        <div class="ci-empty-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="ci-empty-title">Aucune classe trouvée</div>
        <div class="ci-empty-text">Essayez d'ajuster vos filtres ou créez votre première classe.</div>
        @if(auth()->user()->can('admin.access'))
            <button type="button" class="ci-btn--white" id="btn-open-create-modal-empty" style="background: var(--ci-primary); color: #fff;">
                <i class="fas fa-plus-circle"></i>Créer une classe
            </button>
        @endif
    </div>
@endif
