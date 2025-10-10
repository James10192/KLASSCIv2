@if($classes->count() > 0)
    <div class="resultats-grid" id="classes-grid" style="margin-top: var(--space-lg);">
        @include('esbtp.classes.partials.items', ['classes' => $classes])
    </div>

    <!-- Bouton Charger plus -->
    <div id="load-more-container" class="text-center" style="margin-top: var(--space-lg);">
        <button type="button" id="load-more-btn" class="btn-acasi primary" style="display: {{ isset($hasMore) && $hasMore ? 'inline-flex' : 'none' }};" data-has-more="{{ isset($hasMore) ? ($hasMore ? 'true' : 'false') : 'false' }}">
            <i class="fas fa-angle-down me-2"></i>Charger plus de classes
        </button>
        <div id="load-more-spinner" class="d-none" style="padding: var(--space-md);">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    </div>
@else
    <div class="text-center" style="padding: var(--space-xl); color: var(--text-secondary);">
        <i class="fas fa-graduation-cap" style="font-size: 48px; margin-bottom: var(--space-lg); color: var(--neutral);"></i>
        <h5 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Aucune classe trouvée</h5>
        <p style="color: var(--text-muted);">Commencez par créer votre première classe.</p>
        @if(auth()->user()->hasRole('superAdmin'))
            <a href="{{ route('esbtp.classes.create') }}" class="btn-acasi primary" style="margin-top: var(--space-md);">
                <i class="fas fa-plus-circle"></i>Créer une classe
            </a>
        @endif
    </div>
@endif
