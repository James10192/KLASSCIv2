@props([
    'previewUrl',
    'pdfUrl',
    'excelUrl',
    'annees' => collect(),
    'anneeId' => null,
    'classes' => collect(),
    'specializations' => collect(),
])

{{-- L'ancien export-menu générique reste intact sur les autres pages.
     Ce parcours spécifique a besoin d'une validation avant les trois actions. --}}
<div class="te-export-launcher"
     x-data="enseignantsExportDialog()"
     data-preview-url="{{ $previewUrl }}"
     data-pdf-url="{{ $pdfUrl }}"
     data-excel-url="{{ $excelUrl }}">
    <button type="button" class="te-hero-btn" data-export-trigger @click="ouvrir()"
            aria-haspopup="dialog" :aria-expanded="open.toString()">
        <i class="fas fa-download"></i> Exporter les enseignants
    </button>

    {{-- Alpine téléporte le dialogue sous body pour éviter le stacking-context du hero. --}}
    <template x-teleport="body">
        <div class="te-exp-overlay" x-show="open" x-cloak
             @click.self="fermer()" @keydown.escape.window="if (open) fermer()">
            <section class="te-exp-dialog" role="dialog" aria-modal="true"
                     aria-labelledby="te-exp-title" aria-describedby="te-exp-description"
                     tabindex="-1" data-export-dialog>
                <header class="te-exp-header">
                    <div class="te-exp-header-icon"><i class="fas fa-file-export"></i></div>
                    <div class="te-exp-heading">
                        <h2 id="te-exp-title">Préparer l'export des enseignants</h2>
                        <p id="te-exp-description">Choisissez les informations à produire, puis le format souhaité.</p>
                    </div>
                    <button type="button" class="te-exp-close" aria-label="Fermer la fenêtre"
                            @click="fermer()"><i class="fas fa-times"></i></button>
                </header>

                <form id="te-exp-form" class="te-exp-form" @submit.prevent
                      @change="champModifie($event)" novalidate>
                    <div class="te-exp-body">
                        <div class="te-exp-section-title">
                            <span class="te-exp-step">1</span> Quel document souhaitez-vous ?
                        </div>
                        <div class="te-exp-choices" role="radiogroup" aria-label="Type de document">
                            <label class="te-exp-choice" :class="{ 'te-exp-choice--selected': vue === 'synthese' }">
                                <input type="radio" name="vue" value="synthese" x-model="vue" @change="errors = {}">
                                <i class="fas fa-chart-bar"></i>
                                <span><strong>Synthèse</strong><small>Repères par classe et semestre</small></span>
                                <i class="fas fa-check-circle te-exp-checked"></i>
                            </label>
                            <label class="te-exp-choice" :class="{ 'te-exp-choice--selected': vue === 'annuaire' }">
                                <input type="radio" name="vue" value="annuaire" x-model="vue" @change="errors = {}">
                                <i class="fas fa-address-book"></i>
                                <span><strong>Annuaire officiel</strong><small>Fiches et contacts KLASSCI</small></span>
                                <i class="fas fa-check-circle te-exp-checked"></i>
                            </label>
                            <label class="te-exp-choice" :class="{ 'te-exp-choice--selected': vue === 'rapprochement' }">
                                <input type="radio" name="vue" value="rapprochement" x-model="vue" @change="errors = {}">
                                <i class="fas fa-user-check"></i>
                                <span><strong>Noms à rapprocher</strong><small>Saisies manuelles sur les bulletins</small></span>
                                <i class="fas fa-check-circle te-exp-checked"></i>
                            </label>
                            <label class="te-exp-choice" :class="{ 'te-exp-choice--selected': vue === 'affectations' }">
                                <input type="radio" name="vue" value="affectations" x-model="vue" @change="errors = {}">
                                <i class="fas fa-book-open"></i>
                                <span><strong>Matières et professeurs</strong><small>Détail d'une classe — S1 / S2</small></span>
                                <i class="fas fa-check-circle te-exp-checked"></i>
                            </label>
                        </div>

                        <div class="te-exp-section-title te-exp-section-spaced">
                            <span class="te-exp-step">2</span> Définir le périmètre
                        </div>
                        <div class="te-exp-grid">
                            <div class="te-exp-field" :class="{ 'te-exp-field--invalid': errors.annee_universitaire_id }">
                                <span class="te-exp-label">Année universitaire <em>obligatoire</em></span>
                                <x-au-select
                                    name="annee_universitaire_id"
                                    label="Année universitaire du document"
                                    icon="fa-calendar-alt"
                                    placeholder="Choisir l'année"
                                    :placeholder-is-first-option="false"
                                    :value="old('annee_universitaire_id', $anneeId)"
                                    :searchable="$annees->count() > 8"
                                    :options="$annees->mapWithKeys(fn ($annee) => [(string) $annee->id => $annee->display_name])->all()" />
                                <p class="te-exp-error" role="alert" x-show="errors.annee_universitaire_id" x-text="errors.annee_universitaire_id"></p>
                            </div>

                            <div class="te-exp-field" :class="{ 'te-exp-field--invalid': errors.classe_id }">
                                <span class="te-exp-label">Classe
                                    <em x-show="vue === 'affectations'" x-cloak>obligatoire pour le détail PDF</em>
                                    <span x-show="vue !== 'affectations'">facultative</span>
                                </span>
                                <x-au-select
                                    name="classe_id"
                                    label="Classe des bulletins"
                                    icon="fa-chalkboard"
                                    placeholder="Toutes les classes"
                                    :value="old('classe_id', '')"
                                    :searchable="$classes->count() > 8"
                                    :options="$classes->mapWithKeys(fn ($classe) => [(string) $classe->id => $classe->name])->all()" />
                                <p class="te-exp-error" role="alert" x-show="errors.classe_id" x-text="errors.classe_id"></p>
                            </div>

                            <div class="te-exp-field">
                                <span class="te-exp-label">Semestre <span>facultatif</span></span>
                                <x-au-select
                                    name="semestre"
                                    label="Semestre des bulletins"
                                    icon="fa-calendar-week"
                                    placeholder="S1 + S2"
                                    :value="old('semestre', '')"
                                    :options="['1' => 'Semestre 1', '2' => 'Semestre 2']" />
                            </div>
                        </div>
                        <p class="te-exp-tip" x-show="vue === 'affectations'" x-cloak>
                            <i class="fas fa-info-circle"></i>
                            Pour le PDF détaillé, sélectionnez une classe. Sans classe, la synthèse globale est disponible.
                        </p>

                        <button type="button" class="te-exp-more" @click="afficherAvance = !afficherAvance"
                                :aria-expanded="afficherAvance.toString()" aria-controls="te-exp-advanced">
                            <i class="fas fa-sliders-h"></i> Affiner les enseignants (facultatif)
                            <i class="fas fa-chevron-down" :class="{ 'te-exp-chevron--open': afficherAvance }"></i>
                        </button>
                        <div id="te-exp-advanced" class="te-exp-advanced" x-show="afficherAvance" x-cloak>
                            <div class="te-exp-grid">
                                <div class="te-exp-field">
                                    <label for="te-exp-search" class="te-exp-label">Nom ou email</label>
                                    <input id="te-exp-search" name="search" class="te-exp-input"
                                           type="text" maxlength="150"
                                           placeholder="Tous les enseignants" value="{{ request('search', '') }}">
                                </div>
                                <div class="te-exp-field">
                                    <span class="te-exp-label">Statut</span>
                                    <x-au-select
                                        name="status"
                                        label="Statut des enseignants exportés"
                                        icon="fa-user-check"
                                        placeholder="Tous les statuts"
                                        :value="request('status', '')"
                                        :options="['active' => 'Actifs', 'inactive' => 'Inactifs']" />
                                </div>
                                <div class="te-exp-field">
                                    <span class="te-exp-label">Spécialisation</span>
                                    <x-au-select
                                        name="specialization"
                                        label="Spécialisation des enseignants exportés"
                                        icon="fa-book-open"
                                        placeholder="Toutes les spécialisations"
                                        :value="request('specialization', '')"
                                        :searchable="$specializations->count() > 8"
                                        :options="$specializations->mapWithKeys(fn ($spec) => [(string) $spec => (string) $spec])->all()" />
                                </div>
                            </div>
                            <p class="te-exp-advanced-note">Les filtres sur le statut ou la spécialisation excluent les noms de bulletins dont ces informations sont inconnues.</p>
                        </div>
                    </div>

                    <footer class="te-exp-footer">
                        <p class="te-exp-footer-hint">Excel contient les données de l'annuaire avec les filtres sélectionnés.</p>
                        <div class="te-exp-actions">
                            <button type="button" class="te-exp-button te-exp-button--ghost" @click="fermer()">Annuler</button>
                            <button type="button" class="te-exp-button te-exp-button--outline" @click="lancer('preview')">
                                <i class="fas fa-eye"></i> Aperçu PDF
                            </button>
                            <button type="button" class="te-exp-button te-exp-button--outline" @click="lancer('pdf')">
                                <i class="fas fa-file-pdf"></i> Télécharger PDF
                            </button>
                            <button type="button" class="te-exp-button te-exp-button--primary" @click="lancer('excel')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </footer>
                </form>
            </section>
        </div>
    </template>
</div>

@once
@push('styles')
<style>
.te-export-launcher { display:inline-flex; }
/* La fenetre est teleportee sous body : ni hero, ni animation, ni overflow parent. */
.te-exp-overlay { position:fixed; inset:0; z-index:1090; background:rgba(15,23,42,.56); display:flex; align-items:center; justify-content:center; padding:1rem; }
.te-exp-dialog { position:relative; background:#fff; border-radius:18px; border:1px solid #e2e8f0; width:min(850px,100%); max-height:92vh; display:flex; flex-direction:column; box-shadow:0 24px 70px rgba(15,23,42,.28); color:#1e293b; }
.te-exp-header { display:flex; gap:1rem; align-items:center; padding:1.3rem 1.5rem; border-bottom:1px solid #e2e8f0; flex-shrink:0; }
.te-exp-header-icon { width:45px; height:45px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:12px; background:#eff6ff; color:#0453cb; font-size:1.1rem; }
.te-exp-heading { flex:1; min-width:0; }
.te-exp-heading h2 { color:#0f172a; font-size:1.15rem; font-weight:750; margin:0 0 .25rem; }
.te-exp-heading p { font-size:.81rem; color:#64748b; margin:0; }
.te-exp-close { display:grid; place-items:center; border:0; border-radius:9px; background:#f1f5f9; color:#475569; width:34px; height:34px; cursor:pointer; }
.te-exp-close:hover { background:#e2e8f0; }
.te-exp-form { display:flex; flex-direction:column; min-height:0; }
.te-exp-body { padding:1.25rem 1.5rem; overflow-y:auto; min-height:0; }
.te-exp-section-title { display:flex; gap:.55rem; align-items:center; color:#0f172a; font-size:.89rem; font-weight:750; margin-bottom:.8rem; }
.te-exp-section-spaced { margin-top:1.5rem; }
.te-exp-step { display:grid; place-items:center; border-radius:50%; color:#0453cb; background:#eff6ff; width:24px; height:24px; font-size:.75rem; }
.te-exp-choices { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; }
.te-exp-choice { cursor:pointer; display:flex; align-items:center; gap:.75rem; padding:.8rem .85rem; min-width:0; border-radius:12px; border:1px solid #e2e8f0; background:#fff; transition:border-color .15s, background .15s, box-shadow .15s; margin:0; }
.te-exp-choice:hover { border-color:#9bbaf0; background:#f8fbff; }
.te-exp-choice--selected { border-color:#0453cb; background:#eff6ff; box-shadow:0 0 0 1px #0453cb; }
.te-exp-choice:focus-within { outline:2px solid #0453cb; outline-offset:2px; }
.te-exp-choice > input { position:absolute; opacity:0; width:1px; height:1px; }
.te-exp-choice > i:first-of-type { flex-shrink:0; color:#0453cb; font-size:1rem; width:24px; text-align:center; }
.te-exp-choice > span { display:flex; flex-direction:column; min-width:0; flex:1; }
.te-exp-choice strong { color:#0f172a; font-size:.84rem; font-weight:700; }
.te-exp-choice small { font-size:.73rem; line-height:1.35; color:#64748b; margin-top:.15rem; }
.te-exp-choice .te-exp-checked { color:#0453cb; font-size:.9rem; visibility:hidden; }
.te-exp-choice--selected .te-exp-checked { visibility:visible; }
.te-exp-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.8rem; }
.te-exp-field { min-width:0; display:flex; flex-direction:column; gap:.35rem; border-radius:10px; }
.te-exp-label { font-size:.74rem; font-weight:700; color:#475569; }
.te-exp-label em { color:#0453cb; font-size:.68rem; font-style:normal; font-weight:650; }
.te-exp-label span { font-size:.68rem; color:#94a3b8; font-weight:500; }
.te-exp-field .au-select { display:flex; width:100%; flex:0 0 auto; }
.te-exp-field .au-select-trigger { width:100%; min-height:44px; }
.te-exp-field--invalid .au-select-trigger { border:2px solid #dc2626 !important; background:#fff5f5; }
.te-exp-error { font-size:.72rem; color:#b91c1c; font-weight:650; margin:.15rem 0 0; }
.te-exp-input { width:100%; border:1px solid #e2e8f0; border-radius:10px; height:44px; padding:.6rem .75rem; color:#1e293b; }
.te-exp-input:focus { outline:2px solid #bfdbfe; border-color:#0453cb; }
.te-exp-tip { display:flex; align-items:flex-start; gap:.5rem; background:#eff6ff; color:#1e40af; border-radius:9px; margin:.8rem 0 0; padding:.7rem .85rem; font-size:.76rem; line-height:1.4; }
.te-exp-more { display:flex; align-items:center; gap:.5rem; color:#0453cb; background:none; border:0; cursor:pointer; font-weight:700; font-size:.8rem; margin-top:1.1rem; padding:.2rem 0; }
.te-exp-more > i:last-child { margin-left:auto; transition:transform .2s; }
.te-exp-chevron--open { transform:rotate(180deg); }
.te-exp-advanced { padding:.95rem; border-radius:10px; background:#f8fafc; border:1px solid #e2e8f0; margin-top:.6rem; }
.te-exp-advanced-note { font-size:.7rem; line-height:1.45; color:#64748b; margin:.65rem 0 0; }
.te-exp-footer { display:flex; flex-direction:column; gap:.6rem; border-top:1px solid #e2e8f0; padding:1rem 1.5rem 1.25rem; background:#fff; border-radius:0 0 18px 18px; flex-shrink:0; }
.te-exp-footer-hint { font-size:.73rem; color:#64748b; margin:0; }
.te-exp-actions { display:flex; align-items:center; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
.te-exp-button { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; font-weight:700; font-size:.78rem; border:1px solid transparent; border-radius:9px; padding:.65rem .85rem; min-height:40px; cursor:pointer; }
.te-exp-button--ghost { background:none; color:#64748b; }
.te-exp-button--outline { background:#fff; color:#0453cb; border-color:#bfdbfe; }
.te-exp-button--outline:hover { background:#eff6ff; }
.te-exp-button--primary { background:#0453cb; color:white; }
.te-exp-button--primary:hover { background:#033a8e; }
.te-exp-button:focus-visible, .te-exp-close:focus-visible, .te-exp-more:focus-visible { outline:3px solid #93c5fd; outline-offset:2px; }
body.te-exp-body-locked { overflow:hidden; }
/* Le menu officiel x-au-select sait se teleporter au body si scroll/container bloque.
   Son z-index est 1100 : juste devant la modale 1090. */
@media(max-width:700px) {
    .te-exp-overlay { padding:.5rem; }
    .te-exp-dialog { max-height:97dvh; border-radius:14px; }
    .te-exp-header { padding:1rem; }
    .te-exp-body { padding:1rem; }
    .te-exp-footer { padding:.8rem 1rem 1rem; }
    .te-exp-choices { grid-template-columns:1fr; }
    .te-exp-grid { grid-template-columns:1fr; }
    .te-exp-actions { justify-content:stretch; }
    .te-exp-actions .te-exp-button { flex:1 1 46%; }
}
</style>
@endpush
@push('scripts')
<script>
if (typeof window.enseignantsExportDialog !== 'function') {
    window.enseignantsExportDialog = function () {
        return {
            open: false,
            vue: 'synthese',
            afficherAvance: {{ request()->hasAny(['search', 'status', 'specialization']) ? 'true' : 'false' }},
            errors: {},
            init() {
                this.launcher = this.$el;
            },
            ouvrir() {
                this.errors = {};
                this.open = true;
                document.body.classList.add('te-exp-body-locked');
                this.$nextTick(() => document.querySelector('[data-export-dialog]')?.focus());
            },
            fermer() {
                this.open = false;
                this.errors = {};
                document.body.classList.remove('te-exp-body-locked');
                this.$nextTick(() => this.launcher?.querySelector('[data-export-trigger]')?.focus());
            },
            destroy() {
                document.body.classList.remove('te-exp-body-locked');
            },
            champModifie(event) {
                if (event.target && event.target.name && this.errors[event.target.name]) {
                    const nouveau = { ...this.errors };
                    delete nouveau[event.target.name];
                    this.errors = nouveau;
                }
            },
            lancer(format) {
                const form = document.getElementById('te-exp-form');
                if (!form) return;
                const params = new URLSearchParams(new FormData(form));
                const errors = {};
                if (!params.get('annee_universitaire_id')) {
                    errors.annee_universitaire_id = "Choisissez une année universitaire.";
                }
                if (format !== 'excel' && params.get('vue') === 'affectations' && !params.get('classe_id')) {
                    errors.classe_id = "Sélectionnez une classe avant d'ouvrir le PDF détaillé.";
                }
                this.errors = errors;
                if (Object.keys(errors).length > 0) {
                    this.$nextTick(() => {
                        const cible = document.querySelector('.te-exp-field--invalid');
                        cible?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        cible?.querySelector('.au-select-trigger')?.focus();
                    });
                    return;
                }

                const key = format === 'preview' ? 'previewUrl' : format === 'pdf' ? 'pdfUrl' : 'excelUrl';
                const base = this.launcher.dataset[key];
                if (!base) return;
                const url = new URL(base, window.location.origin);
                params.forEach((value, name) => { if (value !== '') url.searchParams.set(name, value); });
                // L'action est declenchee directement par le clic, pour eviter le bloqueur de popups.
                if (format === 'preview') {
                    window.open(url.toString(), '_blank', 'noopener,noreferrer');
                } else {
                    const a = document.createElement('a');
                    a.href = url.toString();
                    a.download = '';
                    // download sur URL same-origin fonctionne sur les reponses attachment
                    // et garde l'ecran de gestion ouvert.
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
            },
        };
    };
}
</script>
@endpush
@endonce
