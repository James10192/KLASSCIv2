{{--
    Lot 8 — Picker de permissions (réutilisable dans create + edit modals).
    Namespace CSS : cr-* (custom-roles)

    Variables attendues :
    - $grantablePermissions : Collection groupée [groupName => Collection<['id','name','label','icon','group']>]
    - $assignedPermissions  : array<string> (noms canoniques) — permissions actuellement cochées (vide pour create)
    - $totalPermissions     : int (total de permissions accordables)
--}}
@php
    $assignedPermissions = $assignedPermissions ?? [];
    $totalPermissions = $grantablePermissions->flatten(1)->count();
@endphp

<div class="cr-picker">
    {{-- Toolbar du picker --}}
    <div class="cr-picker-toolbar">
        <div class="cr-picker-search">
            <i class="fas fa-search"></i>
            <input type="text" class="cr-picker-search-input" placeholder="Rechercher une permission..." data-cr-search>
        </div>
        <div class="cr-picker-counter">
            <span class="cr-picker-counter-value" data-cr-counter-checked>{{ count($assignedPermissions) }}</span>
            <span class="cr-picker-counter-sep">/</span>
            <span class="cr-picker-counter-total">{{ $totalPermissions }}</span>
            <span class="cr-picker-counter-label">permissions sélectionnées</span>
        </div>
        <div class="cr-picker-bulk">
            <button type="button" class="cr-picker-bulk-btn" data-cr-select-all>
                <i class="fas fa-check-double"></i> Tout cocher
            </button>
            <button type="button" class="cr-picker-bulk-btn" data-cr-clear-all>
                <i class="fas fa-eraser"></i> Tout vider
            </button>
        </div>
    </div>

    {{-- Liste des groupes (filterable) --}}
    @if($grantablePermissions->isEmpty())
        <div class="cr-picker-empty">
            <div class="cr-picker-empty-icon"><i class="fas fa-lock"></i></div>
            <h4>Aucune permission disponible</h4>
            <p>Vous ne pouvez accorder que des permissions que vous possédez déjà. Contactez un administrateur si besoin.</p>
        </div>
    @else
        <div class="cr-picker-groups">
            @foreach($grantablePermissions as $groupName => $perms)
                <div class="cr-picker-group" data-cr-group="{{ $groupName }}">
                    <div class="cr-picker-group-header">
                        <button type="button" class="cr-picker-group-toggle" aria-expanded="true">
                            <i class="fas fa-chevron-down cr-picker-group-chev"></i>
                            <span class="cr-picker-group-name">{{ $groupName }}</span>
                            <span class="cr-picker-group-count" data-cr-group-count>{{ $perms->whereIn('name', $assignedPermissions)->count() }}/{{ $perms->count() }}</span>
                        </button>
                        <button type="button" class="cr-picker-group-all" data-cr-group-all="{{ $groupName }}" title="Tout cocher dans ce groupe">
                            <i class="fas fa-check-square"></i>
                        </button>
                    </div>
                    <div class="cr-picker-group-body">
                        @foreach($perms as $perm)
                            <label class="cr-perm" data-cr-perm-label="{{ Str::lower($perm['label']) }} {{ $perm['name'] }}">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $perm['name'] }}"
                                       class="cr-perm-check"
                                       data-cr-perm
                                       data-cr-perm-group="{{ $groupName }}"
                                       @checked(in_array($perm['name'], $assignedPermissions, true))>
                                <span class="cr-perm-box"><i class="fas fa-check"></i></span>
                                <span class="cr-perm-icon"><i class="fas {{ $perm['icon'] }}"></i></span>
                                <span class="cr-perm-text">
                                    <span class="cr-perm-label">{{ $perm['label'] }}</span>
                                    <span class="cr-perm-name">{{ $perm['name'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
