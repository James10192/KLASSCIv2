{{--
    Bandeau « matières prévues » : dit si les semestres de ce combo ont été
    validés, et propose de les reprendre du planning général.

    Tant que le combo n'est pas validé, rien ne change nulle part : le bulletin
    et la couverture des notes se comportent exactement comme avant.
--}}
<div class="mtc-maquette">
    <div class="mtc-maquette-txt">
        <template x-if="maquette.renseignee">
            <span>
                <strong>Matières prévues</strong> :
                <span x-text="maquette.semestre_1"></span> au semestre 1,
                <span x-text="maquette.semestre_2"></span> au semestre 2.
                <small>Une matière sans semestre est prévue aux deux.</small>
            </span>
        </template>
        <template x-if="!maquette.renseignee">
            <span>
                <strong>Semestres non renseignés</strong> pour cette filière et ce niveau.
                <small>Indiquez à quel semestre chaque matière est prévue. Cette saisie prépare le suivi des notes reçues et la composition du bulletin ; elle ne les modifie pas encore.</small>
            </span>
        </template>
    </div>

    <template x-if="planning">
        <button type="button" class="mtc-chip" @click="ouvrirApercuPlanning()" :disabled="saving">
            <i class="fas fa-diagram-project"></i>
            D'après le planning général :
            <span x-text="planning.lignes"></span> ligne(s) · voir
        </button>
    </template>
    <template x-if="!planning">
        <span class="mtc-chip mtc-chip--muted">
            <i class="fas fa-diagram-project"></i> Aucun planning général pour l'année en cours
        </span>
    </template>

    {{-- Ajouter une matière à la maquette. Cet écran ne savait que régler ce
         qui s'y trouvait déjà : compléter une maquette obligeait à en sortir,
         matière par matière, par la fiche de chacune. --}}
    <button type="button" class="mtc-chip" @click="ouvrirAjout()" :disabled="saving">
        <i class="fas fa-plus"></i> Ajouter une matière
    </button>

    <button type="button" class="mtc-btn" @click="save(true)" :disabled="saving">
        <i class="fas fa-check"></i> Valider les semestres
    </button>
</div>

{{-- Choix des matières à rattacher. Celles déjà dans la maquette restent
     visibles mais désactivées : voir qu'une matière y est déjà évite de la
     chercher ailleurs. --}}
<div class="mtc-modal" x-show="ajoutOuvert" x-cloak
     @keydown.escape.window="fermerAjout()"
     role="dialog" aria-modal="true" aria-labelledby="mtc-ajout-titre">
    <div class="mtc-modal-box" @click.outside="fermerAjout()">
        <div class="mtc-modal-head">
            <h2 id="mtc-ajout-titre">Ajouter des matières à la maquette</h2>
            <p>
                <span x-text="filiereName"></span> · la matière rejoint ce niveau ;
                son semestre et sa place se règlent ensuite sur la ligne.
            </p>
        </div>
        <div class="mtc-modal-body">
            <input type="search" class="mtc-ajout-recherche" placeholder="Rechercher une matière…"
                   aria-label="Rechercher une matière" x-model="ajoutRecherche">

            <template x-if="ajoutChargement">
                <div class="mtc-empty"><i class="fas fa-spinner fa-spin"></i>Chargement…</div>
            </template>

            <template x-if="!ajoutChargement && ajoutFiltrees().length === 0">
                <div class="mtc-empty"><i class="fas fa-inbox"></i>Aucune matière ne correspond.</div>
            </template>

            <template x-for="mat in ajoutFiltrees()" :key="mat.id">
                <label class="mtc-ajout-ligne" :class="mat.is_already_linked ? 'mtc-ajout-ligne--prise' : ''">
                    <input type="checkbox" :disabled="mat.is_already_linked"
                           :checked="ajoutSelection.includes(mat.id)"
                           @change="basculerAjout(mat.id)">
                    <span x-text="mat.name"></span>
                    <span class="mtc-row-code" x-show="mat.code" x-text="mat.code"></span>
                    <span class="mtc-ajout-prise" x-show="mat.is_already_linked">déjà dans la maquette</span>
                </label>
            </template>
        </div>
        <div class="mtc-modal-foot">
            <button type="button" class="mtc-mini" @click="fermerAjout()">Annuler</button>
            <button type="button" class="mtc-btn" @click="appliquerAjout()"
                    :disabled="saving || ajoutSelection.length === 0">
                <span x-show="!saving">
                    <i class="fas fa-plus"></i>
                    Ajouter <span x-text="ajoutSelection.length"></span> matière(s)
                </span>
                <span x-show="saving" x-cloak>Ajout…</span>
            </button>
        </div>
    </div>
</div>

{{-- Aperçu de l'import : montre ce qui changerait, avant d'écrire quoi que ce soit. --}}
<div class="mtc-modal" x-show="apercuOuvert" x-cloak
     @keydown.escape.window="fermerApercu()"
     role="dialog" aria-modal="true" aria-labelledby="mtc-apercu-titre">
    <div class="mtc-modal-box" @click.outside="fermerApercu()">
        <div class="mtc-modal-head">
            <h2 id="mtc-apercu-titre">Reprendre les semestres du planning général</h2>
            <p>
                <span x-text="apercu.changements"></span> matière(s) changeraient.
                Le planning est annuel, la maquette ne l'est pas : ce que vous appliquez ici vaut pour toutes les années.
            </p>
        </div>
        <div class="mtc-modal-body">
            <template x-if="apercu.lignes.length === 0">
                <div class="mtc-empty"><i class="fas fa-inbox"></i>Rien à reprendre.</div>
            </template>
            <template x-for="ligne in apercu.lignes" :key="ligne.matiere_id">
                <div class="mtc-diff-row">
                    <span x-text="ligne.name"></span>
                    <span :class="ligne.change ? 'mtc-diff-move' : 'mtc-diff-same'"
                          x-text="libelleSemestre(ligne.actuel) + ' → ' + libelleSemestre(ligne.propose)"></span>
                </div>
            </template>
            <template x-if="apercu.hors_maquette.length > 0">
                <div class="mtc-banner mtc-banner--info" style="margin-top:.8rem">
                    <i class="fas fa-info-circle"></i>
                    <span>
                        <span x-text="apercu.hors_maquette.length"></span> matière(s) du planning ne sont pas rattachées à cette filière et ce niveau.
                        Elles ne seront pas ajoutées : rattachez-les d'abord depuis la fiche matière.
                    </span>
                </div>
            </template>
        </div>
        <div class="mtc-modal-foot">
            <button type="button" class="mtc-mini" @click="fermerApercu()">Annuler</button>
            <button type="button" class="mtc-btn" @click="appliquerPlanning()" :disabled="saving">
                <span x-show="!saving"><i class="fas fa-download"></i> Appliquer</span>
                <span x-show="saving" x-cloak>Application…</span>
            </button>
        </div>
    </div>
</div>
