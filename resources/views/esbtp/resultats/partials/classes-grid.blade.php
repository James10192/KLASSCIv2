@if($classes->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Aucune classe trouvée</h5>
        <p class="text-muted">Modifiez les filtres ou ajoutez une nouvelle classe.</p>
    </div>
@else
    <div class="row g-4" id="classes-container">
        @foreach($classes as $classe)
            <div class="col-xl-3 col-lg-4 col-md-6 class-card-wrapper"
                 data-name="{{ mb_strtolower($classe->name . ' ' . ($classe->filiere->name ?? '') . ' ' . ($classe->niveau->name ?? '')) }}">
                <div class="card card-moderne class-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-bubble me-3">
                                <i class="fas fa-school"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">{{ $classe->name }}</h5>
                                <span class="badge badge-light-muted mt-2">
                                    {{ $classe->anneeUniversitaire->name ?? ($classe->anneeUniversitaire->annee_debut ?? '') . (isset($classe->anneeUniversitaire->annee_fin) ? '-' . $classe->anneeUniversitaire->annee_fin : '') }}
                                </span>
                            </div>
                        </div>
                        <div class="text-muted small mb-3">
                            <div class="mb-1">
                                <i class="fas fa-layer-group text-primary me-2"></i>{{ $classe->filiere->name ?? 'Filière non définie' }}
                            </div>
                            <div class="mb-1">
                                <i class="fas fa-graduation-cap text-primary me-2"></i>{{ $classe->niveau->name ?? 'Niveau non défini' }}
                            </div>
                            <div>
                                <i class="fas fa-users text-primary me-2"></i>{{ $classe->actifs_count }} étudiant{{ $classe->actifs_count > 1 ? 's' : '' }} actifs
                            </div>
                        </div>
                        <div class="mt-auto d-flex gap-2">
                            <a href="{{ route('esbtp.resultats.index', ['classe_id' => $classe->id, 'annee_universitaire_id' => $classe->annee_universitaire_id]) }}"
                               class="btn-acasi primary flex-grow-1">
                                <i class="fas fa-chart-line"></i>Résultats
                            </a>
                            <a href="{{ route('esbtp.resultats.classe', $classe->id) }}" class="btn-acasi secondary" title="Vue détaillée">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
