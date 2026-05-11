{{-- Action bar sticky bottom — apparait des qu'un ECUE est selectionne via
     checkbox row. Affiche compteur + actions (modifier / annuler).
     Alpine factory : lpbBar (definie dans _bulk_scripts). --}}
@can('lmd.planning.edit')
<div id="lpbBar"
     class="lpb-bar"
     x-data="lpbBar()"
     :class="{ 'lpb-bar--visible': count > 0 }"
     x-cloak>
    <div class="lpb-bar-count">
        <i class="fas fa-check-square"></i>
        <span x-text="count + ' ECUE selectionn' + (count > 1 ? 'es' : 'e')"></span>
    </div>
    <div class="lpb-bar-actions">
        <button type="button"
                class="lpb-bar-btn lpb-bar-btn--ghost"
                @click="clearSelection()">
            <i class="fas fa-times"></i> Annuler la selection
        </button>
        <button type="button"
                class="lpb-bar-btn lpb-bar-btn--white"
                @click="openModal()">
            <i class="fas fa-edit"></i> Modifier en lot
        </button>
    </div>
</div>
@endcan
