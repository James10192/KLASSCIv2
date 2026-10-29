{{-- Un message de l'etudiant et sa modale de lecture : rendus par la page et par la
     suite chargee au defilement. Les deux portent leur cle : un message deja affiche
     n'est repete ni en carte ni en modale. --}}
@php
    $isUrgent = (int) ($message->priorite ?? 0) === 2;
    $isMedium = (int) ($message->priorite ?? 0) === 1;
    $isUnread = !$message->is_read;
    $cardClasses = 'mm-card';
    if ($isUnread) { $cardClasses .= ' is-unread'; }
    if ($isUrgent) { $cardClasses .= ' is-urgent'; }

    $type = $message->type ?? 'general';
    $avatarClass = match($type) {
        'classe'   => 'mm-avatar--classe',
        'etudiant' => 'mm-avatar--etudiant',
        default    => '',
    };
    $avatarIcon = match($type) {
        'classe'   => 'fa-users',
        'etudiant' => 'fa-user',
        default    => 'fa-bullhorn',
    };
    $typeLabel = match($type) {
        'classe'   => 'Classe',
        'etudiant' => 'Personnel',
        default    => 'Général',
    };

    $prioLabel = $isUrgent ? 'Urgent' : ($isMedium ? 'Important' : 'Normal');
    $prioClass = $isUrgent ? 'mm-chip--prio-high' : ($isMedium ? 'mm-chip--prio-medium' : 'mm-chip--prio-normal');
    $prioIcon  = $isUrgent ? 'fa-exclamation-triangle' : ($isMedium ? 'fa-flag' : 'fa-circle-check');

    $relative = $message->created_at ? $message->created_at->locale('fr')->diffForHumans(['short' => true]) : '';
    $absolute = $message->created_at ? $message->created_at->format('d/m/Y à H:i') : '';
@endphp

<article data-li-cle="{{ $message->id }}" class="{{ $cardClasses }}"
         data-mm-id="{{ $message->id }}"
         data-mm-state="{{ $isUnread ? 'unread' : 'read' }}"
         data-mm-priority="{{ $isUrgent ? 'urgent' : ($isMedium ? 'medium' : 'normal') }}"
         role="button"
         tabindex="0"
         aria-label="Message {{ $message->titre }}, {{ $isUnread ? 'non lu' : 'lu' }}, priorité {{ $prioLabel }}"
         onclick="mmOpenMessage({{ $message->id }})"
         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();mmOpenMessage({{ $message->id }});}">
    <div class="mm-card-body">
        <div class="mm-avatar {{ $avatarClass }}" aria-hidden="true">
            <i class="fas {{ $avatarIcon }}"></i>
        </div>

        <div class="mm-content">
            <div class="mm-title-row">
                @if($isUnread)
                    <span class="mm-unread-dot" aria-label="Non lu" title="Non lu"></span>
                @endif
                <h3 class="mm-title">{{ $message->titre }}</h3>
            </div>

            <p class="mm-snippet">
                {{ Str::limit(strip_tags($message->contenu), 180) }}
            </p>

            <div class="mm-meta">
                <span class="mm-chip mm-chip--type-{{ $type }}">
                    <i class="fas {{ $avatarIcon }}" aria-hidden="true"></i>
                    {{ $typeLabel }}
                </span>
                <span class="mm-chip {{ $prioClass }}">
                    <i class="fas {{ $prioIcon }}" aria-hidden="true"></i>
                    {{ $prioLabel }}
                </span>
                <span class="mm-chip mm-chip--time" title="{{ $absolute }}">
                    <i class="far fa-clock" aria-hidden="true"></i>
                    {{ $relative }}
                </span>
            </div>
        </div>

        <div class="mm-card-actions" onclick="event.stopPropagation();">
            @if($isUnread)
            <button type="button"
                    class="mm-icon-btn mm-icon-btn--read"
                    data-mm-mark-read="{{ $message->id }}"
                    aria-label="Marquer comme lu"
                    title="Marquer comme lu">
                <i class="fas fa-check" aria-hidden="true"></i>
            </button>
            @endif
            <button type="button"
                    class="mm-icon-btn"
                    onclick="mmOpenMessage({{ $message->id }})"
                    aria-label="Lire le message"
                    title="Lire">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</article>

{{-- Modal détail message --}}
<div data-li-cle="{{ $message->id }}-modale" class="modal fade mm-modal"
     id="mmModal{{ $message->id }}"
     tabindex="-1"
     aria-labelledby="mmModalLabel{{ $message->id }}"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header {{ $isUrgent ? 'is-urgent' : '' }}">
                <div class="mm-modal-title-wrap">
                    <span class="mm-modal-eyebrow">
                        <i class="fas {{ $avatarIcon }}" aria-hidden="true"></i>
                        {{ $typeLabel }}
                        @if($isUrgent) , urgent @elseif($isMedium) , important @endif
                    </span>
                    <h5 class="modal-title" id="mmModalLabel{{ $message->id }}">
                        {{ $message->titre }}
                    </h5>
                </div>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
            </div>
            <div class="mm-modal-body modal-body">
                <div class="mm-info-grid">
                    <div class="mm-info-item">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <span><strong>Publié</strong><br>{{ $absolute }}</span>
                    </div>
                    @if($message->date_expiration)
                    <div class="mm-info-item">
                        <i class="fas fa-hourglass-end" aria-hidden="true"></i>
                        <span><strong>Expire</strong><br>{{ $message->date_expiration->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    <div class="mm-info-item">
                        <i class="fas fa-signal" aria-hidden="true"></i>
                        <span>
                            <strong>Priorité</strong><br>
                            <span class="mm-chip {{ $prioClass }}" style="margin-top:.15rem;">
                                <i class="fas {{ $prioIcon }}" aria-hidden="true"></i>
                                {{ $prioLabel }}
                            </span>
                        </span>
                    </div>
                </div>

                <div class="mm-modal-content">
                    {!! nl2br(e($message->contenu)) !!}
                </div>
            </div>
            <div class="mm-modal-footer">
                @if($isUnread)
                <button type="button"
                        class="btn-acasi primary"
                        data-mm-mark-read="{{ $message->id }}"
                        data-bs-dismiss="modal">
                    <i class="fas fa-check"></i>
                    Marquer comme lu
                </button>
                @endif
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
