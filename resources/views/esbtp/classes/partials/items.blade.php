@foreach($classes as $classe)
    <div class="card-moderne resultat-card animate-slide-up" style="border-left: 4px solid {{ $classe->is_active ? 'var(--success)' : 'var(--neutral)' }};">
        <!-- En-tête classe -->
        <div style="display: flex; justify-content: between; align-items: start; margin-bottom: var(--space-md);">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; margin-bottom: var(--space-sm);">
                    <div style="width: 40px; height: 40px; background: {{ $classe->is_active ? 'var(--success)' : 'var(--neutral)' }}; border-radius: var(--radius-circle); display: flex; align-items: center; justify-content: center; margin-right: var(--space-sm);">
                        <i class="fas fa-graduation-cap" style="color: white; font-size: 16px;"></i>
                    </div>
                    <div>
                        <div class="font-bold color-primary" style="font-size: var(--text-normal);">{{ $classe->name }}</div>
                        <div style="font-size: var(--text-small); color: var(--text-secondary);">Code: {{ $classe->code }}</div>
                    </div>
                </div>

                <!-- Filière et niveau -->
                <div style="margin-bottom: var(--space-md);">
                    @if ($classe->filiere)
                        <div style="font-size: var(--text-small); color: var(--text-primary); margin-bottom: var(--space-xs);">
                            <i class="fas fa-layer-group me-1"></i><strong>{{ $classe->filiere->name }}</strong>
                            @if ($classe->filiere->parent)
                                <br><span style="color: var(--text-muted); margin-left: 16px;">Option de {{ $classe->filiere->parent->name }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($classe->niveau)
                        <div style="font-size: var(--text-small); color: var(--text-secondary);">
                            <i class="fas fa-level-up-alt me-1"></i>{{ $classe->niveau->name }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Statut -->
            <div>
                <span class="badge {{ $classe->is_active ? 'success' : 'danger' }}">
                    {{ $classe->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        <!-- Statistiques -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-md); margin-bottom: var(--space-md); padding: var(--space-sm); background: rgba(248, 250, 252, 0.5); border-radius: var(--radius-small);">
            <div class="text-center">
                <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Capacité</div>
                <div class="font-bold color-primary">{{ $classe->places_totales }}</div>
            </div>
            <div class="text-center">
                <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Inscrits</div>
                <div class="font-bold color-accent">{{ $classe->nombre_etudiants }}</div>
            </div>
            <div class="text-center">
                <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Disponibles</div>
                <div class="font-bold color-{{ $classe->places_disponibles > 0 ? 'success' : 'danger' }}">{{ $classe->places_disponibles }}</div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f3f4f6; padding-top: var(--space-md);">
            <div style="font-size: var(--text-small); color: var(--text-muted);">
                @if ($classe->annee)
                    <i class="fas fa-calendar me-1"></i>{{ $classe->annee->name }}
                @endif
            </div>
            <div style="display: flex; gap: var(--space-xs);">
                {{-- Lien "Voir détails" avec filtres préservés dans l'URL --}}
                <a href="{{ route('esbtp.classes.show', array_merge(['classe' => $classe->id], request()->query())) }}" class="btn-acasi secondary" style="padding: var(--space-xs);" title="Voir les détails">
                    <i class="fas fa-eye"></i>
                </a>

                @if(auth()->user()->hasRole('superAdmin'))
                {{-- Lien "Modifier" avec return_url vers index filtrée --}}
                <a href="{{ route('esbtp.classes.edit', array_merge(['classe' => $classe->id], ['return_url' => request()->fullUrl()])) }}" class="btn-acasi primary" style="padding: var(--space-xs);" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
                @endif

                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('coordinateur'))
                <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn-acasi secondary" style="padding: var(--space-xs);" title="Gérer les matières">
                    <i class="fas fa-book"></i>
                </a>
                @endif

                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('enseignant') || auth()->user()->hasRole('coordinateur'))
                <a href="{{ route('esbtp.classes.liste-appel', ['classe' => $classe->id]) }}" class="btn-acasi primary" style="padding: var(--space-xs);" title="Liste d'appel" target="_blank">
                    <i class="fas fa-clipboard-list"></i>
                </a>
                <a href="{{ route('esbtp.classes.liste-complete', ['classe' => $classe->id]) }}" class="btn-acasi secondary" style="padding: var(--space-xs);" title="Liste complète des étudiants" target="_blank">
                    <i class="fas fa-users"></i>
                </a>
                @endif

                @if(auth()->user()->hasRole('superAdmin'))
                    @if($classe->nombre_etudiants == 0)
                    <button type="button" class="btn-acasi danger" style="padding: var(--space-xs);" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $classe->id }}" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                    @else
                    <button type="button" class="btn-acasi secondary" style="padding: var(--space-xs); opacity: 0.5; cursor: not-allowed;" title="Suppression impossible - Classe avec historique d'inscriptions préservé" disabled>
                        <i class="fas fa-lock"></i>
                    </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de suppression -->
    @if(auth()->user()->hasRole('superAdmin') && $classe->nombre_etudiants == 0)
    <div class="modal fade" id="deleteModal{{ $classe->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $classe->id }}" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel{{ $classe->id }}">Archivage de la classe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir archiver la classe <strong>{{ $classe->name }}</strong> ?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> La classe sera archivée mais l'historique des inscriptions sera préservé pour les rapports et statistiques des années universitaires passées.
                    </div>
                    <p class="text-warning"><strong>Important:</strong> Cette classe ne sera plus visible dans la liste active mais restera accessible dans l'historique.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form action="{{ route('esbtp.classes.destroy', $classe->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive me-1"></i>Archiver la classe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
