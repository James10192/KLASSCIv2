{{-- Un coordinateur : rendu par la page et par la suite chargee au defilement. --}}
<div class="coordinateur-card" data-li-cle="{{ $coordinateur->id }}">
    <div class="status-badge {{ $coordinateur->is_active ? 'active' : 'inactive' }}">
        {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
    </div>
    
    <div class="row align-items-center">
        <div class="col-md-1">
            <div class="coordinateur-avatar">
                {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
            </div>
        </div>
        <div class="col-md-7">
            <div class="coordinateur-info">
                <h6>{{ $coordinateur->name }}</h6>
                <div class="coordinateur-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span>{{ $coordinateur->email }}</span>
                    </div>
                    @if($coordinateur->telephone)
                    <div class="meta-item">
                        <i class="fas fa-phone"></i>
                        <span>{{ $coordinateur->telephone }}</span>
                    </div>
                    @endif
                    @if($coordinateur->specialite)
                    <div class="meta-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span>{{ $coordinateur->specialite }}</span>
                    </div>
                    @endif
                </div>
                <div class="coordinateur-meta mt-2">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Créé le {{ $coordinateur->created_at->format('d/m/Y') }}</span>
                    </div>
                    @if($coordinateur->last_login_at)
                    <div class="meta-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Dernière connexion: {{ $coordinateur->last_login_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="actions-group">
                <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}" 
                   class="btn btn-sm btn-outline-info" title="Voir détails">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" 
                   class="btn btn-sm btn-outline-primary" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
                @if($coordinateur->id !== auth()->id())
                <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" 
                            class="btn btn-sm btn-outline-{{ $coordinateur->is_active ? 'warning' : 'success' }}" 
                            title="{{ $coordinateur->is_active ? 'Désactiver' : 'Activer' }}"
                            onclick="return confirm('Êtes-vous sûr de vouloir {{ $coordinateur->is_active ? 'désactiver' : 'activer' }} ce coordinateur ?')">
                        <i class="fas fa-{{ $coordinateur->is_active ? 'ban' : 'check' }}"></i>
                    </button>
                </form>
                <form action="{{ route('esbtp.coordinateurs.destroy', $coordinateur) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="btn btn-sm btn-outline-danger" 
                            title="Supprimer"
                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce coordinateur ? Cette action est irréversible.')">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
