{{-- Selecteur de classe des fenetres « inscrire » et « reinscrire ». Clone
     documente de x-au-select (premium-selects.md) : il affiche les places de
     chaque classe, ce que le composant generique ne sait pas faire. Une classe
     pleine reste visible mais ne se choisit pas : le flux canonique la
     refuserait. Params : $modele (chemin Alpine de la valeur), $liste (expression Alpine). --}}
<div class="dmi-picker" :class="picker.ouvert ? 'is-ouvert' : ''" x-on:click.outside="picker.ouvert = false" x-on:keydown.escape.stop="picker.ouvert = false">
    <button type="button" class="dmi-picker-bouton" x-on:click="picker.ouvert = !picker.ouvert; picker.q = ''; $nextTick(() => $el.closest('.dmi-picker').querySelector('input').focus())" :aria-expanded="picker.ouvert" aria-haspopup="listbox">
        <span x-text="classeChoisie({{ $liste }}, {{ $modele }})?.nom || 'Choisir une classe'"></span>
        <span class="dmi-jauge" x-show="classeChoisie({{ $liste }}, {{ $modele }})" :class="jaugeTon(classeChoisie({{ $liste }}, {{ $modele }}))">
            <span class="dmi-jauge-barre"><span :style="'width:' + jaugePct(classeChoisie({{ $liste }}, {{ $modele }})) + '%'"></span></span>
            <span x-text="placesTexte(classeChoisie({{ $liste }}, {{ $modele }}))"></span>
        </span>
        <i class="fas fa-chevron-down" style="color:#64748b;font-size:.75rem" aria-hidden="true"></i>
    </button>
    <div class="dmi-picker-menu" x-show="picker.ouvert" x-cloak role="listbox">
        <input type="search" x-model="picker.q" placeholder="Filtrer les classes…" aria-label="Filtrer les classes">
        <div class="dmi-picker-liste">
            <template x-for="c in filtrerClasses({{ $liste }})" :key="c.id">
                <button type="button" class="dmi-picker-option" role="option" :aria-selected="{{ $modele }} === c.id" :disabled="c.places_totales > 0 && c.places_libres <= 0"
                        x-on:click="{{ $modele }} = c.id; picker.ouvert = false; $dispatch('dmi-classe')">
                    <span style="min-width:0"><strong><span x-text="c.nom"></span><span class="dmi-voeu" x-show="c.voeu">Vœu</span></strong><small x-text="c.detail"></small></span>
                    <span class="dmi-jauge" :class="jaugeTon(c)"><span class="dmi-jauge-barre"><span :style="'width:' + jaugePct(c) + '%'"></span></span><span x-text="placesTexte(c)"></span></span>
                </button>
            </template>
            <p class="dmi-p-note" style="padding:.5rem" x-show="!filtrerClasses({{ $liste }}).length">Aucune classe ne correspond.</p>
        </div>
    </div>
</div>
