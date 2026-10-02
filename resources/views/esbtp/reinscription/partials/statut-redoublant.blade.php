{{-- Statut redoublant confirmé à la réinscription (namespace rsr-*).
     Proposé d'après le niveau de la classe choisie, comparé à celui de l'année
     quittée ; la personne garde ou change, et dit pourquoi si elle change. --}}
@can(\App\Domain\Inscriptions\StatutRedoublant::PERMISSION)
@php
    $_niveauParClasse = collect($classesParDecision ?? [])->flatten(1)
        ->mapWithKeys(fn ($c) => [(string) data_get($c, 'id') => data_get($c, 'niveau_etude_id')])
        ->all();
    // Le niveau de l'année d'avant, pour chaque année de destination : la même
    // règle que le serveur (une correction dans l'année en cours compte aussi).
    $_niveauAvant = \App\Domain\Inscriptions\StatutRedoublant::niveauxDeLAnneePrecedente(
        (int) $analyse['etudiant']->id, $anneeUniversitairesFutures ?? []
    );
    $_ancien = ['valeur' => old('redoublant'), 'motif' => old('redoublant_motif')];
@endphp
<div class="rsr-carte mb-lg"
     x-data="statutRedoublantReinscription()"
     data-niveaux='@json($_niveauParClasse)'
     data-niveau-avant='@json($_niveauAvant)'
     data-ancien='@json($_ancien)'
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
        <textarea id="redoublant_motif" name="redoublant_motif" class="rsr-motif" rows="2" maxlength="500" x-model="motif"
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
                niveauAvant: {},
                motif: '',
                motifMinimum: 10,
                valeur: '',
                propose: '',
                touche: false,
                classeChoisie: false,
                contradiction: '',
                aide: AIDE_SANS_CLASSE,
                init() {
                    try { this.niveaux = JSON.parse(this.$root.dataset.niveaux || '{}'); } catch (e) { this.niveaux = {}; }
                    try { this.niveauAvant = JSON.parse(this.$root.dataset.niveauAvant || '{}'); } catch (e) { this.niveauAvant = {}; }
                    // Retour d'erreur du serveur : on rend le choix et le motif saisis.
                    try {
                        const ancien = JSON.parse(this.$root.dataset.ancien || '{}');
                        if (ancien.valeur === '0' || ancien.valeur === '1') {
                            this.valeur = ancien.valeur;
                            this.touche = true;
                        }
                        this.motif = ancien.motif || '';
                    } catch (e) { /* rien à rendre */ }
                    this.motifMinimum = parseInt(this.$root.dataset.motifMinimum || '10', 10);
                    ['decision', 'annee_universitaire_id'].forEach((id) => {
                        const champ = document.getElementById(id);
                        if (champ) champ.addEventListener('change', () => this.suivreLaClasse());
                    });
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
                    const annee = (document.getElementById('annee_universitaire_id') || {}).value || '';
                    const niveauAvant = this.niveauAvant[annee] ?? null;
                    const redouble = niveau !== null && niveauAvant !== null && String(niveau) === String(niveauAvant);
                    this.propose = redouble ? '1' : '0';
                    this.aide = redouble
                        ? 'Proposé : oui. La classe choisie est du même niveau que l\'année d\'avant.'
                        : 'Proposé : non. La classe choisie n\'est pas du même niveau que l\'année d\'avant.';
                    if (!this.touche) {
                        this.valeur = this.propose;
                    }
                    // La décision et la classe peuvent se contredire : le logiciel le montre, la personne tranche.
                    const decision = (document.getElementById('decision') || {}).value || '';
                    this.contradiction = (decision === 'redoublement') !== redouble
                        ? (redouble
                            ? 'La classe choisie est du même niveau que l\'année d\'avant, mais la décision est « ' + decision + ' ».'
                            : 'La décision est « redoublement », mais la classe choisie est d\'un autre niveau.')
                        : '';
                },
            };
        };
    }
</script>
@endcan
