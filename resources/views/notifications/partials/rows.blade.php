{{-- Lignes de /notifications, regroupees par date. Rendu a la fois par la page
     et par le fragment AJAX (« Charger plus », filtres) : aucun Alpine ici, les
     clics sont delegues au conteneur de la page. --}}
@php $ntfGroupeCourant = $previousGroup ?? null; @endphp
@foreach($notifications as $notification)
    @if($notification->display_group !== $ntfGroupeCourant)
        @php $ntfGroupeCourant = $notification->display_group; @endphp
        <h3 class="ntf-group" data-ntf-group="{{ $ntfGroupeCourant }}">
            {{ \App\Services\Notifications\NotificationPresenter::GROUPES[$ntfGroupeCourant] }}
        </h3>
    @endif
    @php
        $ntfNonLue = ! $notification->is_read;
        $ntfType = $notification->display_type;
        $ntfAction = $notification->display_action;
    @endphp
    <article class="ntf-row ntf-row--{{ $ntfType['key'] }} {{ $ntfNonLue ? 'is-unread' : '' }}"
             id="notification-{{ $notification->id }}"
             data-ntf-id="{{ $notification->id }}"
             data-ntf-unread="{{ $ntfNonLue ? '1' : '0' }}"
             data-ntf-url="{{ $ntfAction['url'] ?? '' }}">
        <span class="ntf-icon" title="{{ $ntfType['label'] }}">
            <i class="fas {{ $ntfType['icon'] }}" aria-hidden="true"></i>
        </span>

        <div class="ntf-body">
            <div class="ntf-title-line">
                <h4 class="ntf-title">{{ $notification->title ?: 'Notification' }}</h4>
                @if($ntfNonLue)
                    <span class="ntf-dot" data-ntf-dot><span class="visually-hidden">Non lue</span></span>
                @endif
            </div>

            @if(filled($notification->display_primary))
                <p class="ntf-excerpt">{{ \Illuminate\Support\Str::limit($notification->display_primary, 220) }}</p>
            @endif

            @if(! empty($notification->display_labels))
                <div class="ntf-pills">
                    @foreach($notification->display_labels as $pill)
                        <span class="ntf-pill ntf-pill--{{ $pill['tone'] }}">
                            <i class="fas {{ $pill['icon'] }}" aria-hidden="true"></i>{{ $pill['key'] }} : {{ $pill['value'] }}
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="ntf-meta">
                <time datetime="{{ optional($notification->created_at)->toIso8601String() }}"
                      title="{{ optional($notification->created_at)->translatedFormat('l d F Y à H:i') }}">
                    {{ optional($notification->created_at)->diffForHumans() }}
                </time>
                @if($notification->sender)
                    <span>· Par {{ $notification->sender->name }}</span>
                @endif
            </div>
        </div>

        <div class="ntf-actions">
            @if($ntfAction)
                <a href="{{ $ntfAction['url'] }}" class="ntf-btn ntf-btn--primary" data-ntf-open>
                    <i class="fas {{ $ntfAction['icon'] }}" aria-hidden="true"></i><span>{{ $ntfAction['label'] }}</span>
                </a>
            @elseif($ntfNonLue)
                <button type="button" class="ntf-btn" data-ntf-read>
                    <i class="fas fa-check" aria-hidden="true"></i><span>Marquer comme lue</span>
                </button>
            @endif
            <button type="button" class="ntf-icon-btn" data-ntf-delete
                    aria-label="Supprimer cette notification" title="Supprimer">
                <i class="fas fa-trash-can" aria-hidden="true"></i>
                <span class="ntf-confirm-label">Supprimer ?</span>
            </button>
        </div>
    </article>
@endforeach
