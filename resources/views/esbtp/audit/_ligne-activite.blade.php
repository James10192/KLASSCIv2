{{-- Une action de la chronologie d'activite, precedee de son jour quand il change :
     rendue par la page et par la suite chargee au defilement.
     Attend $audit, $afficherJour, $selectedUser, $entityLinksMap. --}}
@php
    $eventLabels ??= ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression', 'restored' => 'Restauration', 'retrieved' => 'Consultation'];
    $day = $audit->created_at->format('Y-m-d');
    $event = $audit->event;
    $modelLabel = \App\Helpers\EntityLabelHelper::for($audit->auditable_type);
    $rowLinks = $entityLinksMap[$audit->id] ?? [];
    $rowLinksCount = count($rowLinks);
@endphp
{{-- Le jour porte sa propre cle : ouvrir une tranche sur un jour deja
     affiche ne le repete pas, le dedoublonnage du defilement l'ecarte. --}}
@if($afficherJour)
    <div class="au-timeline-day" data-li-cle="jour-{{ $day }}">
        <i class="far fa-calendar"></i>
        {{ $audit->created_at->translatedFormat('l j F Y') }}
    </div>
@endif
<div class="au-timeline-item au-timeline-item--{{ $event }}" data-li-cle="{{ $audit->id }}">
    <div class="au-timeline-time">{{ $audit->created_at->format('H:i:s') }}</div>
    <div class="au-timeline-dot"></div>
    <div class="au-timeline-content">
        <div class="au-timeline-meta">
            @if(!$selectedUser)
                <strong>{{ $audit->user?->name ?? 'Système' }}</strong>
            @endif
            <span class="au-chip au-chip--{{ $event }}">{{ $eventLabels[$event] ?? $event }}</span>
            <span class="au-chip au-chip--neutral">{{ $modelLabel }} #{{ $audit->auditable_id }}</span>
            @if($audit->ip_address)
                <span class="au-timeline-ip"><i class="fas fa-network-wired"></i> {{ $audit->ip_address }}</span>
            @endif
        </div>
        <div class="au-timeline-actions-row">
            @if($rowLinksCount > 0)
                <button type="button" class="au-links-pill au-links-pill--sm"
                        @click="openIds.includes({{ $audit->id }}) ? openIds = openIds.filter(i => i !== {{ $audit->id }}) : openIds.push({{ $audit->id }})">
                    <i class="fas fa-project-diagram"></i>
                    <span x-show="!openIds.includes({{ $audit->id }})">{{ $rowLinksCount }} lien{{ $rowLinksCount > 1 ? 's' : '' }}</span>
                    <span x-show="openIds.includes({{ $audit->id }})" x-cloak>Replier</span>
                    <i class="fas fa-chevron-down au-toggle-caret" :class="openIds.includes({{ $audit->id }}) ? 'au-toggle-caret--open' : ''"></i>
                </button>
            @endif
            <a href="{{ route('esbtp.audit.show', $audit->id) }}" class="au-timeline-link">
                Détail <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        @if($rowLinksCount > 0)
            <div class="au-timeline-links" x-show="openIds.includes({{ $audit->id }})" x-cloak x-transition.opacity>
                <x-audit-links :links="$rowLinks" :compact="true" />
            </div>
        @endif
    </div>
</div>
