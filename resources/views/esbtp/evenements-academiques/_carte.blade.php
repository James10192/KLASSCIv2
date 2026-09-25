{{-- Un evenement academique : rendu par la page et par la suite chargee au defilement. --}}
<div class="evenement-card" data-event-id="{{ $evenement->id }}" data-li-cle="{{ $evenement->id }}">
    <div class="evenement-header">
        <div class="evenement-info">
            <div class="d-flex align-items-center me-3">
                <input type="checkbox" class="event-checkbox me-2" value="{{ $evenement->id }}" onchange="updateBulkActions()">
            </div>
            <div class="evenement-icon">
                <i class="fas fa-{{ $evenement->icone }} color-{{ $evenement->couleur }}"></i>
            </div>
            <div class="evenement-details">
                <h6 class="evenement-title">{{ $evenement->titre }}</h6>
                <p class="evenement-description">{{ Str::limit($evenement->description, 80) }}</p>
            </div>
        </div>
        <div class="evenement-actions">
            <a href="{{ route('esbtp.evenements-academiques.show', $evenement) }}" 
               class="btn-acasi secondary btn-sm" title="Voir">
                <i class="fas fa-eye"></i>
            </a>
            @if($evenement->isEditable())
                <a href="{{ route('esbtp.evenements-academiques.edit', $evenement) }}" 
                   class="btn-acasi primary btn-sm" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
            @endif
            <div class="dropdown">
                <button class="btn-acasi secondary btn-sm dropdown-toggle" 
                        type="button" data-toggle="dropdown" title="Plus d'actions">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu">
                    <form method="POST" action="{{ route('esbtp.evenements-academiques.duplicate', $evenement) }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-copy me-2"></i> Dupliquer
                        </button>
                    </form>
                    @if($evenement->isDeletable())
                        <div class="dropdown-divider"></div>
                        <form method="POST" 
                              action="{{ route('esbtp.evenements-academiques.destroy', $evenement) }}"
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-trash me-2"></i> Supprimer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="evenement-meta">
        <div class="meta-item">
            <i class="fas fa-calendar-day"></i>
            <span class="meta-value">{{ $evenement->date_formatee }}</span>
        </div>
        <div class="meta-item">
            <i class="fas fa-clock"></i>
            <span class="meta-value">{{ $evenement->duree }}</span>
        </div>
        <div class="meta-item">
            <span class="badge-moderne badge-{{ $evenement->couleur }}">
                {{ $evenement->type_libelle }}
            </span>
        </div>
        <div class="meta-item">
            @php
                $statusColor = match($evenement->statut) {
                    'planifie' => 'secondary',
                    'confirme' => 'success',
                    'annule' => 'danger',
                    'reporte' => 'warning',
                    'termine' => 'info',
                    default => 'secondary'
                };
            @endphp
            <span class="badge-moderne badge-{{ $statusColor }}">
                {{ $evenement->statut_libelle }}
            </span>
        </div>
        <div class="meta-item participants">
            <i class="fas fa-users"></i>
            <span class="meta-value">{{ $evenement->participants_formatted }}</span>
        </div>
    </div>
</div>
