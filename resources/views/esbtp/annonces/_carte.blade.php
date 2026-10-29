{{-- Une annonce, carte de la grille (telephone) : rendue par la page et par la suite chargee au defilement. --}}
@php
    $canEdit = true;
    if ($annonce->isExpired()) {
        $canEdit = false;
    } elseif ($annonce->is_published) {
        $publishedAt = $annonce->date_publication && $annonce->date_publication > $annonce->created_at
            ? $annonce->date_publication
            : $annonce->created_at;
        $canEdit = $publishedAt->diffInMinutes(now()) <= 15;
    }
@endphp

<div data-li-cle="{{ $annonce->id }}" class="annonce-card {{ $annonce->isExpired() ? 'expired' : '' }}" data-title="{{ strtolower($annonce->titre) }}">
    <div class="annonce-card-header">
        <h3 class="annonce-card-title {{ $annonce->isExpired() ? 'expired' : '' }}">
            @if($annonce->priorite == 2)
                <i class="fas fa-exclamation-triangle text-danger"></i>
            @endif
            {{ $annonce->titre }}
            @if($annonce->isExpired())
                <i class="fas fa-clock text-danger" title="Annonce expirée"></i>
            @endif
        </h3>
    </div>

    <div class="annonce-card-badges">
        @if($annonce->isExpired())
            <span class="status-badge danger">
                <i class="fas fa-clock"></i> Expirée
            </span>
        @else
            <span class="status-badge {{ $annonce->is_published ? 'success' : 'warning' }}">
                {{ $annonce->is_published ? 'Publiée' : 'Brouillon' }}
            </span>
        @endif
        <span class="priority-badge priority-{{ $annonce->priorite }}">
            {{ $annonce->priorite == 2 ? 'Urgente' : ($annonce->priorite == 1 ? 'Importante' : 'Normale') }}
        </span>
        <span class="type-badge">
            {{ $annonce->type == 'general' ? 'Générale' : ucfirst($annonce->type) }}
        </span>
    </div>

    <div class="annonce-card-info">
        <div class="annonce-card-info-item">
            <div class="annonce-card-info-label">
                <i class="fas fa-calendar-plus"></i> Créée le
            </div>
            <div class="annonce-card-info-value">
                {{ $annonce->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
        <div class="annonce-card-info-item">
            <div class="annonce-card-info-label">
                <i class="fas fa-calendar-times"></i> Expire le
            </div>
            <div class="annonce-card-info-value">
                {{ $annonce->date_expiration ? \Carbon\Carbon::parse($annonce->date_expiration)->format('d/m/Y H:i') : '-' }}
            </div>
        </div>
    </div>

    <div class="annonce-card-actions">
        <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-action primary" title="Voir">
            <i class="fas fa-eye"></i>
        </a>
        @if($canEdit)
            <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="btn-action secondary" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
        @else
            <button class="btn-action secondary disabled" disabled title="{{ $annonce->isExpired() ? 'Modification impossible (annonce expirée)' : 'Modification impossible (plus de 15 minutes)' }}">
                <i class="fas fa-edit"></i>
            </button>
        @endif
        <button type="button" class="btn-action danger" onclick="deleteAnnonce({{ $annonce->id }})" title="Supprimer">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</div>
