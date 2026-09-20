{{--
    Une matière de la maquette : sa place sur le bulletin, son statut tronc
    commun / spécialité, et le semestre auquel elle est prévue.

    La place se règle au clavier (champ numérique) ou aux flèches. Une place
    héritée de l'ordre général s'affiche en gris : elle devient propre à la
    filière dès qu'on y touche.
--}}
<template x-for="(m, idx) in matieres" :key="m.matiere_id">
    <div class="mtc-row">
        <div class="mtc-row-main">
            <div class="mtc-rank">
                <input type="number" min="1" class="mtc-rank-input"
                    :class="m.ordre_source === 'general' ? 'mtc-rank-input--herite' : ''"
                    :value="m.ordre_effectif"
                    placeholder="—"
                    :aria-label="'Place de ' + m.name + ' sur le bulletin'"
                    @input="poserRang(m, $event.target.value)">
                <button type="button" class="mtc-rank-btn" :disabled="idx === 0"
                    :aria-label="'Monter ' + m.name"
                    @click="monter(idx)"><i class="fas fa-chevron-up"></i></button>
                <button type="button" class="mtc-rank-btn" :disabled="idx === matieres.length - 1"
                    :aria-label="'Descendre ' + m.name"
                    @click="descendre(idx)"><i class="fas fa-chevron-down"></i></button>
                <span class="mtc-rank-tag"
                    x-text="m.ordre_source === 'combo' ? 'Propre' : (m.ordre_source === 'general' ? 'Général' : '')"></span>
            </div>
            <span class="mtc-row-name" x-text="m.name"></span>
            <span class="mtc-row-code" x-show="m.code" x-text="m.code"></span>
            <span class="mtc-suggest" x-show="m.wasSuggested && m.classification === 'specialite'">suggéré</span>
        </div>

        <div class="mtc-seg">
            <button type="button" class="mtc-seg-btn"
                :class="m.semestre === 1 ? 'mtc-seg-btn--tc mtc-seg-btn--active' : ''"
                @click="setSemestre(m, 1)">S1</button>
            <button type="button" class="mtc-seg-btn"
                :class="m.semestre === 2 ? 'mtc-seg-btn--tc mtc-seg-btn--active' : ''"
                @click="setSemestre(m, 2)">S2</button>
            <button type="button" class="mtc-seg-btn"
                :class="m.semestre === null ? 'mtc-seg-btn--tc mtc-seg-btn--active' : ''"
                @click="setSemestre(m, null)">Les deux</button>
        </div>

        <div class="mtc-seg">
            <button type="button" class="mtc-seg-btn mtc-seg-btn--tc"
                :class="m.classification === 'tronc_commun' ? 'mtc-seg-btn--active' : ''"
                @click="setClass(m, 'tronc_commun')">Tronc commun</button>
            <button type="button" class="mtc-seg-btn mtc-seg-btn--spe"
                :class="m.classification === 'specialite' ? 'mtc-seg-btn--active' : ''"
                @click="setClass(m, 'specialite')">Spécialité</button>
        </div>

        {{-- Retirer de la maquette. Le retrait n'existait nulle part sur cet
             écran : il fallait passer par les liaisons de la matière, qui
             effaçaient les réglages de ses autres filières et niveaux. --}}
        <button type="button" class="mtc-retirer" :disabled="saving"
            :aria-label="'Retirer ' + m.name + ' de la maquette'"
            title="Retirer de la maquette"
            @click="retirerDeLaMaquette(m)">
            <i class="fas fa-xmark"></i>
        </button>
    </div>
</template>
