{{-- Une annonce, ligne du tableau (ordinateur) : rendue par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $annonce->id }}" class="{{ $annonce->isExpired() ? 'expired-row' : '' }}">
    <td>
        <div class="table-title">
            @if($annonce->priorite == 2)
                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
            @endif
            {{ $annonce->titre }}
            @if($annonce->isExpired())
                <i class="fas fa-clock text-danger ms-2" title="Annonce expirée"></i>
            @endif
        </div>
    </td>
    <td>
        @if($annonce->isExpired())
            <span class="status-badge danger">
                <i class="fas fa-clock me-1"></i>Expirée
            </span>
        @else
            <span class="status-badge {{ $annonce->is_published ? 'success' : 'warning' }}">
                {{ $annonce->is_published ? 'Publiée' : 'Brouillon' }}
            </span>
        @endif
    </td>
    <td>
        <span class="priority-badge priority-{{ $annonce->priorite }}">
            {{ $annonce->priorite == 2 ? 'Urgente' : ($annonce->priorite == 1 ? 'Importante' : 'Normale') }}
        </span>
    </td>
    <td>
        <span class="type-badge">
            {{ $annonce->type == 'general' ? 'Générale' : ucfirst($annonce->type) }}
        </span>
    </td>
    <td>{{ $annonce->created_at->format('d/m/Y H:i') }}</td>
    <td>{{ $annonce->date_expiration ? \Carbon\Carbon::parse($annonce->date_expiration)->format('d/m/Y H:i') : '-' }}</td>
    <td class="actions">
        <div class="action-buttons">
            <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-action primary" title="Voir">
                <i class="fas fa-eye"></i>
            </a>
            @php
                $canEdit = true;

                // Bloquer l'édition si l'annonce est expirée
                if ($annonce->isExpired()) {
                    $canEdit = false;
                } elseif ($annonce->is_published) {
                    // Règle des 15 minutes pour les annonces publiées
                    $publishedAt = $annonce->date_publication && $annonce->date_publication > $annonce->created_at
                        ? $annonce->date_publication
                        : $annonce->created_at;
                    $canEdit = $publishedAt->diffInMinutes(now()) <= 15;
                }
            @endphp

            @if($canEdit)
                <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="btn-action secondary" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
            @else
                <button class="btn-action secondary disabled" disabled title="{{ $annonce->isExpired() ? 'Modification impossible (annonce expirée)' : 'Modification impossible (plus de 15 minutes)' }}">
                    <i class="fas fa-edit text-muted"></i>
                </button>
            @endif
            <button type="button" class="btn-action danger" onclick="deleteAnnonce({{ $annonce->id }})" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>
</tr>
