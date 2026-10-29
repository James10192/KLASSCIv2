{{--
    Lot 8 — Suggestions d'icônes Font Awesome partagées entre les 3 modals
    (création custom, édition custom, édition standard).

    Variables :
    - $icons : array<string> — liste des classes FA à proposer (par défaut, set commun)

    Toutes les icônes proposées DOIVENT figurer dans la whitelist
    `ESBTPCustomRoleController::ALLOWED_ICONS` sinon la validation serveur les rejettera.
--}}
@php
    $icons = $icons ?? [
        'fa-user-tag', 'fa-user-shield', 'fa-user-tie', 'fa-user-cog', 'fa-user-check',
        'fa-id-badge', 'fa-headset', 'fa-magnifying-glass', 'fa-key', 'fa-handshake',
    ];
@endphp
<div class="cr-icon-suggestions">
    @foreach($icons as $iconClass)
        <button type="button" class="cr-icon-chip" data-cr-icon-suggest="{{ $iconClass }}" title="{{ $iconClass }}">
            <i class="fas {{ $iconClass }}"></i>
        </button>
    @endforeach
</div>
