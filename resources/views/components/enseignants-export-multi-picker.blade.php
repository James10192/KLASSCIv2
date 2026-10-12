@props([
    'kind',
    'label',
    'icon' => 'fa-filter',
])

{{-- Multi-select premium Alpine : aucun menu HTML natif visible, aucun plugin
     d'initialisation perdu apres le teleport du dialogue sous body. --}}
<div class="te-exp-field te-exp-multi" x-data="{ expanded: false, recherche: '' }"
     @keydown.escape.stop="expanded = false"
     data-multi-picker="{{ $kind }}">
    <span class="te-exp-label">{{ $label }} <span>facultatif — plusieurs choix</span></span>
    <button type="button" class="te-exp-multi-trigger"
            :aria-expanded="expanded.toString()"
            aria-controls="te-exp-options-{{ $kind }}"
            @click="expanded = !expanded">
        <i class="fas {{ $icon }}" aria-hidden="true"></i>
        <span x-text="selections.{{ $kind }}.length
            ? selections.{{ $kind }}.length + ' sélection(s)'
            : 'Toutes / tous'"></span>
        <i class="fas fa-chevron-down te-exp-multi-caret" aria-hidden="true"></i>
    </button>

    <div class="te-exp-multi-summary" x-show="selections.{{ $kind }}.length > 0" x-cloak>
        <template x-for="item in selections.{{ $kind }}" :key="item">
            <span class="te-exp-multi-tag">
                <span x-text="optionLabel('{{ $kind }}', item)"></span>
                <button type="button" :aria-label="'Retirer ' + optionLabel('{{ $kind }}', item)"
                        @click="toggleSelection('{{ $kind }}', item, false)">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </span>
        </template>
    </div>

    <div class="te-exp-multi-panel" id="te-exp-options-{{ $kind }}" x-show="expanded" x-cloak>
        <input type="search" class="te-exp-input" x-model="recherche"
               aria-label="Rechercher dans {{ $label }}"
               placeholder="Rechercher..." autocomplete="off">
        <div class="te-exp-multi-options">
            <template x-for="item in optionsFor('{{ $kind }}', recherche)" :key="item.id">
                <label class="te-exp-multi-option">
                    <input type="checkbox" :checked="selections.{{ $kind }}.includes(String(item.id))"
                           @change="toggleSelection('{{ $kind }}', item.id, $event.target.checked)">
                    <span x-text="item.label"></span>
                </label>
            </template>
            <p class="te-exp-multi-empty" x-show="optionsFor('{{ $kind }}', recherche).length === 0">Aucun résultat.</p>
        </div>
        <button type="button" class="te-exp-multi-clear" @click="selections.{{ $kind }} = []">
            Effacer cette sélection
        </button>
    </div>
    <template x-for="id in selections.{{ $kind }}" :key="id">
        <input type="hidden" name="{{ match($kind) { 'filieres' => 'filiere_ids', 'niveaux' => 'niveau_ids', 'classes' => 'classe_ids', 'parcours' => 'parcours_ids', 'ues' => 'ue_ids', default => 'matiere_refs' } }}[]" :value="id">
    </template>
</div>
