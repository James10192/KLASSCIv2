{{--
    Premium modal "Lier des UE au parcours" — namespace lpm-*
    Self-contained : CSS + Alpine factory + markup.

    Pattern : Alpine.data() registered in <script>, state via data-* + JSON.parse
    (Blade-safe — pas de {{ }} dans des object literals Alpine).

    Communication :
      - Reçoit  : @lpm:open.window={ detail: { parcoursId, parcoursName } }
      - Émet    : lpm:saved → écouté par lpPlanning pour fetchPartial()
--}}


@include('esbtp.lmd.planning._link_ue_modal_styles')
@include('esbtp.lmd.planning._link_ue_modal_scripts')


<div
    x-data="lpmModal"
    x-show="open"
    x-cloak
    @keydown.escape.window="open && !saving && close()"
    x-transition.opacity.duration.150ms
    class="lpm-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="lpm-title">

    <div
        @click.outside="open && !saving && close()"
        x-trap.inert.noscroll="open"
        x-transition:enter="lpm-card-enter"
        class="lpm-card">

        {{-- Header --}}
        <div class="lpm-header">
            <div class="lpm-header-left">
                <div class="lpm-header-icon"><i class="fas fa-link"></i></div>
                <div>
                    <h2 id="lpm-title">Lier des UE au parcours</h2>
                    <p x-text="parcoursName ? parcoursName : 'Sélectionnez les unités d\'enseignement à associer'"></p>
                </div>
            </div>
            <button type="button" class="lpm-close" @click="close()" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Toolbar --}}
        <div class="lpm-toolbar">
            <input
                type="text"
                class="lpm-search"
                placeholder="Rechercher une UE par nom ou code..."
                x-model="search"
                aria-label="Recherche UE">
            <div class="lpm-counter">
                <strong x-text="selectedCount"></strong> UE sélectionnée<span x-show="selectedCount > 1">s</span>
                <span x-show="rows.length > 0"> / <span x-text="rows.length"></span></span>
            </div>
        </div>

        {{-- Body --}}
        <div class="lpm-body">
            <template x-if="loading">
                <div class="lpm-loading">
                    <span class="lpm-spin"></span> Chargement des UE...
                </div>
            </template>

            <template x-if="!loading && rows.length === 0 && !error">
                <div class="lpm-empty">
                    <div class="lpm-empty-icon"><i class="fas fa-cubes"></i></div>
                    Aucune UE disponible. Créez-en d'abord via le module Unités d'enseignement.
                </div>
            </template>

            <template x-if="!loading && rows.length > 0">
                <table class="lpm-table">
                    <thead>
                        <tr>
                            <th class="lpm-cell-check"></th>
                            <th>Unité d'enseignement</th>
                            <th class="lpm-th-num">Semestre</th>
                            <th class="lpm-th-num">Optionnelle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in filteredRows" :key="row.id">
                            <tr :class="row.selected ? 'lpm-row-selected' : ''">
                                <td class="lpm-cell-check">
                                    <input type="checkbox" class="lpm-checkbox"
                                        x-model="row.selected"
                                        :aria-label="'Sélectionner ' + row.name">
                                </td>
                                <td>
                                    <template x-if="row.code">
                                        <span class="lpm-ue-code" x-text="row.code"></span>
                                    </template>
                                    <template x-if="!row.code">
                                        <span class="lpm-ue-code lpm-ue-code-virtual">virtuelle</span>
                                    </template>
                                    <span x-text="row.name"></span>
                                </td>
                                <td class="lpm-cell-sem" style="text-align:center;">
                                    <select class="lpm-mini-select"
                                        x-model.number="row.semestre"
                                        :disabled="!row.selected"
                                        :aria-label="'Semestre pour ' + row.name">
                                        <template x-for="n in 10" :key="n">
                                            <option :value="n" x-text="'S' + n"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="lpm-cell-opt" style="text-align:center;">
                                    <input type="checkbox" class="lpm-checkbox"
                                        x-model="row.is_optional"
                                        :disabled="!row.selected"
                                        :aria-label="'UE optionnelle: ' + row.name">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>

        {{-- Footer --}}
        <div class="lpm-footer">
            <div class="lpm-feedback" :class="error ? 'lpm-feedback-error' : ''">
                <template x-if="error">
                    <span><i class="fas fa-exclamation-triangle"></i> <span x-text="error"></span></span>
                </template>
                <template x-if="!error && !loading">
                    <span x-show="selectedCount > 0">Cliquez sur "Enregistrer" pour appliquer.</span>
                </template>
            </div>
            <div class="lpm-actions">
                <button type="button" class="lpm-btn lpm-btn-secondary" @click="close()" :disabled="saving">
                    Annuler
                </button>
                <button type="button" class="lpm-btn lpm-btn-primary" @click="submit()" :disabled="saving || loading">
                    <template x-if="saving"><span class="lpm-spin"></span></template>
                    <i x-show="!saving" class="fas fa-check"></i>
                    <span x-text="saving ? 'Enregistrement...' : 'Enregistrer'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
