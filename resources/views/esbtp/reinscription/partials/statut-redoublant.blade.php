{{-- Statut redoublant confirmé à la réinscription (namespace rsr-*).
     Proposé d'après le niveau de la classe choisie, comparé à celui de l'année
     quittée ; la personne garde ou change, et dit pourquoi si elle change. --}}
@can(\App\Domain\Inscriptions\StatutRedoublant::PERMISSION)
@php
    $_niveauParClasse = collect($classesParDecision ?? [])->flatten(1)
        ->mapWithKeys(fn ($c) => [(string) data_get($c, 'id') => data_get($c, 'niveau_etude_id')])
        ->all();
    $_niveauQuitte = $analyse['inscription']->niveau_id ?? null;
@endphp
<div class="rsr-carte mb-lg"
     x-data="statutRedoublantReinscription()"
     data-niveaux='@json($_niveauParClasse)'
     data-niveau-quitte="{{ $_niveauQuitte }}"
     data-motif-minimum="{{ \App\Domain\Inscriptions\StatutRedoublant::MOTIF_MINIMUM }}"
     x-effect="suivreLaClasse()">
    <div class="rsr-tete">
        <span class="rsr-icone"><i class="fas fa-redo-alt"></i></span>
        <div>
            <div class="rsr-titre">Redoublant en {{ $anneeDestinationName }} ?</div>
            <div class="rsr-aide" x-text="aide"></div>
        </div>
    </div>
    <div class="rsr-choix" role="radiogroup" aria-label="Statut redoublant">
        <label class="rsr-option" :class="{ 'rsr-option--actif': valeur === '1', 'rsr-option--inactif': !classeChoisie }">
            <input type="radio" name="redoublant" value="1" x-model="valeur" x-on:change="touche = true" :disabled="!classeChoisie">
            <span>Oui, il redouble</span>
        </label>
        <label class="rsr-option" :class="{ 'rsr-option--actif': valeur === '0', 'rsr-option--inactif': !classeChoisie }">
            <input type="radio" name="redoublant" value="0" x-model="valeur" x-on:change="touche = true" :disabled="!classeChoisie">
            <span>Non</span>
        </label>
    </div>
    <div class="rsr-alerte" x-show="contradiction" x-cloak>
        <i class="fas fa-exclamation-triangle"></i> <span x-text="contradiction"></span>
    </div>
    <div x-show="classeChoisie && valeur !== propose" x-cloak>
        <label class="rsr-note" for="redoublant_motif"><i class="fas fa-pen"></i> Vous changez la valeur proposée : dites pourquoi (obligatoire).</label>
        <textarea id="redoublant_motif" name="redoublant_motif" class="rsr-motif" rows="2" maxlength="500"
                  :required="classeChoisie && valeur !== propose" :minlength="motifMinimum"
                  :disabled="!(classeChoisie && valeur !== propose)"
                  placeholder="Exemple : redouble sur décision du conseil de classe de juin"></textarea>
    </div>
    @error('motif')
        <div class="rsr-alerte">{{ $message }}</div>
    @enderror
</div>

<style>
    .rsr-carte { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1rem 1.25rem; box-shadow:0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .rsr-tete { display:flex; align-items:center; gap:.75rem; margin-bottom:.85rem; }
    .rsr-icone { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .rsr-titre { font-weight:700; color:#1e293b; }
    .rsr-aide { font-size:.82rem; color:#64748b; }
    .rsr-choix { display:flex; gap:.6rem; flex-wrap:wrap; }
    .rsr-option { display:flex; align-items:center; gap:.5rem; padding:.55rem .95rem; border:1px solid #cbd5e1; border-radius:10px; cursor:pointer; font-weight:600; color:#334155; transition:all .2s ease; }
    .rsr-option input { accent-color:#0453cb; }
    .rsr-option--actif { border-color:#0453cb; background:rgba(4,83,203,.06); color:#0453cb; }
    .rsr-option--inactif { opacity:.55; cursor:not-allowed; }
    .rsr-note { display:block; margin-top:.75rem; font-size:.8rem; color:#0453cb; font-weight:600; }
    .rsr-motif { width:100%; margin-top:.35rem; border:1px solid #cbd5e1; border-radius:10px; padding:.55rem .75rem; font-size:.88rem; }
    .rsr-alerte { margin-top:.65rem; font-size:.82rem; color:#b45309; }
</style>

<script>
    if (typeof window.statutRedoublantReinscription !== 'function') {
        window.statutRedoublantReinscription = function () {
            const AIDE_SANS_CLASSE = 'Choisissez d\'abord la classe : la réponse sera proposée d\'après son niveau.';
            return {
                niveaux: {},
                niveauQuitte: null,
                motifMinimum: 10,
                valeur: '',
                propose: '',
                touche: false,
                classeChoisie: false,
                contradiction: '',
                aide: AIDE_SANS_CLASSE,
                init() {
                    try { this.niveaux = JSON.parse(this.$root.dataset.niveaux || '{}'); } catch (e) { this.niveaux = {}; }
                    this.niveauQuitte = this.$root.dataset.niveauQuitte || null;
                    this.motifMinimum = parseInt(this.$root.dataset.motifMinimum || '10', 10);
                    const decision = document.getElementById('decision');
                    if (decision) {
                        decision.addEventListener('change', () => this.suivreLaClasse());
                    }
                },
                suivreLaClasse() {
                    const autre = window.autreClasseSelector ? String(window.autreClasseSelector.selectedValue || '') : '';
                    const meme = window.nouvelleClasseSelector ? String(window.nouvelleClasseSelector.selectedValue || '') : '';
                    let niveau = null;
                    if (autre) {
                        const choixNiveau = document.getElementById('autre_niveau_id');
                        niveau = choixNiveau ? choixNiveau.value : null;
                    } else if (meme) {
                        niveau = this.niveaux[meme] ?? null;
                    }
                    this.classeChoisie = (autre || meme) !== '';
                    if (!this.classeChoisie) {
                        this.aide = AIDE_SANS_CLASSE;
                        this.contradiction = '';
                        return;
                    }
                    const redouble = niveau !== null && this.niveauQuitte !== null && String(niveau) === String(this.niveauQuitte);
                    this.propose = redouble ? '1' : '0';
                    this.aide = redouble
                        ? 'Proposé : oui. La classe choisie est du même niveau que cette année.'
                        : 'Proposé : non. La classe choisie n\'est pas du même niveau que cette année.';
                    if (!this.touche) {
                        this.valeur = this.propose;
                    }
                    // La décision et la classe peuvent se contredire : le logiciel le montre, la personne tranche.
                    const decision = (document.getElementById('decision') || {}).value || '';
                    this.contradiction = (decision === 'redoublement') !== redouble
                        ? (redouble
                            ? 'La classe choisie est du même niveau que cette année, mais la décision est « ' + decision + ' ».'
                            : 'La décision est « redoublement », mais la classe choisie est d\'un autre niveau.')
                        : '';
                },
            };
        };
    }
</script>
@endcan
