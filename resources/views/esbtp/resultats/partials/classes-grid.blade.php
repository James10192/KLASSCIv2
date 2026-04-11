@if($classes->isEmpty())
    <div class="sr-empty">
        <i class="fas fa-school"></i>
        <h3>Aucune classe trouvée</h3>
        <p>Modifiez les filtres ou ajoutez une nouvelle classe.</p>
    </div>
@else
    <div class="sr-cls-grid" id="classes-container">
        @foreach($classes as $classe)
            <div class="sr-cls-card class-card-wrapper"
                 data-name="{{ mb_strtolower($classe->name . ' ' . ($classe->filiere->name ?? '') . ' ' . ($classe->niveau->name ?? '')) }}">
                <div class="sr-cls-card-top">
                    <div class="sr-cls-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="sr-cls-info">
                        <h4 class="sr-cls-name">{{ $classe->name }}</h4>
                        <div class="sr-cls-badges">
                            @if($classe->filiere)
                                <span class="sr-cls-badge sr-cls-badge--filiere">{{ $classe->filiere->name }}</span>
                            @endif
                            @if($classe->niveau)
                                <span class="sr-cls-badge sr-cls-badge--niveau">{{ $classe->niveau->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="sr-cls-meta">
                    <div class="sr-cls-meta-item">
                        <i class="fas fa-users"></i>
                        <span><strong>{{ $classe->actifs_count }}</strong> étudiant{{ $classe->actifs_count > 1 ? 's' : '' }}</span>
                    </div>
                </div>
                <div class="sr-cls-actions">
                    <a href="{{ route('esbtp.resultats.classe', $classe->id) }}" class="sr-cls-btn sr-cls-btn--primary">
                        <i class="fas fa-chart-bar"></i>Résultats
                    </a>
                    @can('access_admin')
                        <a href="{{ route('esbtp.resultats.classe.edit', $classe->id) }}" class="sr-cls-btn sr-cls-btn--secondary">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>
@endif
