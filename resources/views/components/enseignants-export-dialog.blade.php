@props([
    'previewUrl',
    'pdfUrl',
    'excelUrl',
    'annees' => collect(),
    'anneeId' => null,
    'classes' => collect(),
    'filieres' => collect(),
    'niveaux' => collect(),
    'matieres' => collect(),
    'parcours' => collect(),
    'ues' => collect(),
    'specializations' => collect(),
])

{{-- L'ancien export-menu générique reste intact sur les autres pages.
     Ce parcours spécifique a besoin d'une validation avant les trois actions. --}}
<div class="te-export-launcher"
     x-data="enseignantsExportDialog()"
     data-preview-url="{{ $previewUrl }}"
     data-pdf-url="{{ $pdfUrl }}"
     data-excel-url="{{ $excelUrl }}"
     data-classes='@json($classes)'
     data-filieres='@json($filieres)'
     data-niveaux='@json($niveaux)'
     data-matieres='@json($matieres)'
     data-parcours='@json($parcours)'
     data-ues='@json($ues)'
     data-structure="{{ json_encode(\App\Services\Enseignants\CompositionExportEnseignants::definitions(), JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
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
                            <label class="te-exp-choice" :class="{ 'te-exp-choice--selected': vue === 'specialisations' }">
                                <input type="radio" name="vue" value="specialisations" x-model="vue" @change="errors = {}">
                                <i class="fas fa-layer-group"></i>
                                <span><strong>Répertoire par spécialisation</strong><small>Enseignants regroupés par discipline + noms à classer</small></span>
                                <i class="fas fa-check-circle te-exp-checked"></i>
                            </label>
                            <label class="te-exp-choice" :class="{ 'te-exp-choice--selected': vue === 'liste' }">
                                <input type="radio" name="vue" value="liste" x-model="vue" @change="errors = {}">
                                <i class="fas fa-list"></i>
                                <span><strong>Liste épurée</strong><small>Juste les noms, regroupés par matière, classe, filière ou niveau</small></span>
                                <i class="fas fa-check-circle te-exp-checked"></i>
                            </label>
                            <label class="te-exp-choice" :class="{ 'te-exp-choice--selected': vue === 'matieres' }">
                                <input type="radio" name="vue" value="matieres" x-model="vue" @change="errors = {}">
                                <i class="fas fa-list-alt"></i>
                                <span><strong>Matières regroupées</strong><small>Par matière, tous les professeurs, classes et semestres</small></span>
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
                        <div class="te-exp-programs" role="group" aria-label="Systèmes académiques à exporter">
                            <span class="te-exp-label">Système académique <em>obligatoire</em></span>
                            <div class="te-exp-group-choices">
                                <label class="te-exp-group-option" :class="{ 'te-exp-group-option--selected': systemes.includes('BTS') }">
                                    <input type="checkbox" :checked="systemes.includes('BTS')"
                                           @change="changerSysteme('BTS', $event.target.checked)">
                                    BTS
                                </label>
                                <label class="te-exp-group-option" :class="{ 'te-exp-group-option--selected': systemes.includes('LMD') }">
                                    <input type="checkbox" :checked="systemes.includes('LMD')"
                                           @change="changerSysteme('LMD', $event.target.checked)">
                                    LMD — Licence / Master
                                </label>
                            </div>
                            <template x-for="systeme in systemes" :key="systeme">
                                <input type="hidden" name="systemes[]" :value="systeme">
                            </template>
                            <p class="te-exp-scope-help">BTS : matières des bulletins. LMD : ECUE de maquettes et enseignants de planification. Les matières homonymes restent indépendantes, même avec les deux systèmes.</p>
                            <p class="te-exp-error" x-show="errors.systemes" x-text="errors.systemes" role="alert" x-cloak></p>
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

                            <div class="te-exp-field">
                                <span class="te-exp-label">Périmètre pédagogique <span>facultatif</span></span>
                                <p class="te-exp-scope-help">Sélectionnez plusieurs classes, filières, niveaux ou matières ci-dessous. Les critères se croisent.</p>
                            </div>

                            <div class="te-exp-field">
                                <span class="te-exp-label">Semestre <span>facultatif</span></span>
                                <x-au-select
                                    name="semestre"
                                    label="Semestre du programme"
                                    icon="fa-calendar-week"
                                    placeholder="Tous les semestres"
                                    :searchable="true"
                                    :value="old('semestre', '')"
                                    :options="collect(range(1, 10))->mapWithKeys(fn ($n) => [(string) $n => 'S'.$n.($n > 2 ? ' — LMD' : ' — BTS ou LMD')])->all()" />
                                <p class="te-exp-scope-help" x-show="systemes.length === 1 && systemes[0] === 'BTS'" x-cloak>En BTS, seuls S1 et S2 sont applicables.</p>
                                <p class="te-exp-error" role="alert" x-show="errors.semestre" x-text="errors.semestre" x-cloak></p>
                            </div>
                        </div>
                        <div class="te-exp-multi-grid">
                            <x-enseignants-export-multi-picker kind="filieres" label="Filières" icon="fa-sitemap" />
                            <x-enseignants-export-multi-picker kind="niveaux" label="Niveaux d'études" icon="fa-graduation-cap" />
                            <x-enseignants-export-multi-picker kind="classes" label="Classes" icon="fa-chalkboard" />
                            <x-enseignants-export-multi-picker kind="matieres" label="Matières BTS / ECUE LMD" icon="fa-book" />
                            <div x-show="systemes.includes('LMD')" x-cloak>
                                <x-enseignants-export-multi-picker kind="parcours" label="Parcours LMD" icon="fa-route" />
                            </div>
                            <div x-show="systemes.includes('LMD')" x-cloak>
                                <x-enseignants-export-multi-picker kind="ues" label="Unités d'enseignement (UE)" icon="fa-layer-group" />
                            </div>
                        </div>
                        <p class="te-exp-scope-help">Dans un même critère, les choix s'additionnent. Entre critères, seules les affectations correspondant à toutes les sélections sont exportées. Sans sélection : toutes les classes et matières de l'année.</p>
                        <p class="te-exp-error" role="alert" x-show="errors.perimetre" x-text="errors.perimetre" x-cloak></p>
                        <p class="te-exp-tip" x-show="(vue === 'annuaire' || vue === 'specialisations') && hasPedagogicalFilters()" x-cloak>
                            <i class="fas fa-info-circle"></i>
                            Pour ces documents, seules les fiches associées par nom aux bulletins du périmètre choisi apparaissent ; les saisies non rapprochées restent distinctes.
                        </p>

                        <p class="te-exp-tip" x-show="vue === 'affectations'" x-cloak>
                            <i class="fas fa-info-circle"></i>
                            Pour le détail ligne à ligne, choisissez au moins une classe, filière, niveau ou matière. Pour un tableau condensé, sélectionnez « Matières regroupées ».
                        </p>

                        <p class="te-exp-tip" x-show="vue === 'matieres'" x-cloak>
                            <i class="fas fa-info-circle"></i>
                            Sans filtre de classe, les professeurs sont regroupés par matière sur toutes les classes de l'année. Les classes et semestres restent visibles pour chaque professeur.
                        </p>
                        <p class="te-exp-tip" x-show="vue === 'specialisations'" x-cloak>
                            <i class="fas fa-info-circle"></i>
                            Les spécialisations viennent des fiches KLASSCI. Les noms des bulletins non classables figurent en annexe sans rapprochement automatique.
                        </p>

                        <div class="te-exp-section-title te-exp-section-spaced">
                            <span class="te-exp-step">3</span> Composer le document
                        </div>
                        <div class="te-exp-compose">
                            <div>
                                <div class="te-exp-label">Colonnes à afficher <em>au moins une</em></div>
                                <p class="te-exp-scope-help">Décochez les informations inutiles. L'intitulé d'un groupe peut remplacer une colonne répétée.</p>
                                <div class="te-exp-column-grid" role="group" aria-label="Colonnes du document">
                                    <template x-for="[key, label] in Object.entries(schema[vue]?.colonnes || {})" :key="vue + '-' + key">
                                        <label class="te-exp-column-option" :class="{ 'te-exp-column-option--checked': selectedColumns.includes(key) }">
                                            <input type="checkbox" :checked="selectedColumns.includes(key)"
                                                   @change="changerColonne(key, $event.target.checked)">
                                            <span x-text="label"></span>
                                        </label>
                                    </template>
                                </div>
                                <p class="te-exp-error" x-show="errors.colonnes" x-text="errors.colonnes" role="alert" x-cloak></p>
                                <template x-for="colonne in selectedColumns" :key="colonne">
                                    <input type="hidden" name="colonnes[]" :value="colonne">
                                </template>
                            </div>
                            <div>
                                <div class="te-exp-label">Classement / rubriques</div>
                                <div class="te-exp-group-choices" role="radiogroup" aria-label="Regroupement du document">
                                    <template x-for="[key, label] in Object.entries(schema[vue]?.regroupements || {})" :key="vue + '-' + key">
                                        <label class="te-exp-group-option" :class="{ 'te-exp-group-option--selected': regroupement === key }">
                                            <input type="radio" name="regroupement" :value="key" x-model="regroupement" @change="choisirRegroupement(key)">
                                            <span x-text="label"></span>
                                        </label>
                                    </template>
                                </div>
                                <div class="te-exp-label" style="margin-top:.95rem">Orientation du PDF</div>
                                <div class="te-exp-group-choices" role="radiogroup" aria-label="Orientation du PDF">
                                    <label class="te-exp-group-option" :class="{ 'te-exp-group-option--selected': orientation === 'auto' }">
                                        <input type="radio" name="orientation" value="auto" x-model="orientation">
                                        Automatique
                                    </label>
                                    <label class="te-exp-group-option" :class="{ 'te-exp-group-option--selected': orientation === 'portrait' }">
                                        <input type="radio" name="orientation" value="portrait" x-model="orientation">
                                        Portrait
                                    </label>
                                    <label class="te-exp-group-option" :class="{ 'te-exp-group-option--selected': orientation === 'landscape' }">
                                        <input type="radio" name="orientation" value="landscape" x-model="orientation">
                                        Paysage
                                    </label>
                                </div>
                                <p class="te-exp-scope-help">Automatique privilégie le portrait et choisit le paysage si les colonnes sont trop larges. En portrait forcé, une sélection incompatible est refusée pour éviter les coupes.</p>
                            </div>
                        </div>

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
                        <p class="te-exp-footer-hint">PDF et Excel suivent le type de document sélectionné, avec les mêmes filtres.</p>
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
.te-exp-multi-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.8rem; margin-top:.85rem; align-items:start; }
 .te-exp-programs { padding:.85rem 1rem; border:1px solid #bfdbfe; background:#eff6ff; border-radius:12px; margin-bottom:.85rem; }
.te-exp-compose { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(0,1fr); gap:1rem; padding:.9rem; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; }
.te-exp-column-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.45rem; margin-top:.65rem; }
.te-exp-column-option { display:flex; gap:.5rem; align-items:center; border:1px solid #cbd5e1; border-radius:8px; padding:.5rem; cursor:pointer; font-size:.76rem; background:#fff; color:#334155; }
.te-exp-column-option--checked { background:#eff6ff; border-color:#0453cb; color:#1d4ed8; font-weight:700; }
.te-exp-column-option input,.te-exp-group-option input { accent-color:#0453cb; }
.te-exp-group-choices { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.6rem; }
.te-exp-group-option { display:inline-flex; align-items:center; gap:.3rem; padding:.42rem .55rem; border-radius:9px; background:#fff; border:1px solid #cbd5e1; font-size:.74rem; cursor:pointer; }
.te-exp-group-option--selected { border-color:#0453cb; background:#eff6ff; color:#1d4ed8; font-weight:700; }
.te-exp-scope-help { color:#64748b; font-size:.73rem; line-height:1.4; margin:.25rem 0 .65rem; }
.te-exp-multi-trigger { min-height:44px; display:flex; align-items:center; gap:.6rem; width:100%; background:#fff; border:1px solid #cbd5e1; border-radius:10px; padding:.65rem .8rem; color:#1e293b; font-weight:650; text-align:left; cursor:pointer; }
.te-exp-multi-trigger > i:first-child { color:#0453cb; }
.te-exp-multi-trigger:focus-visible { outline:3px solid #93c5fd; outline-offset:2px; }
.te-exp-multi-caret { margin-left:auto; color:#64748b; }
.te-exp-multi-panel { padding:.65rem; border:1px solid #cbd5e1; background:#f8fafc; border-radius:10px; margin-top:.4rem; }
.te-exp-multi-options { max-height:190px; overflow:auto; margin:.35rem 0; }
.te-exp-multi-option { display:flex; align-items:center; gap:.5rem; padding:.45rem; cursor:pointer; border-radius:7px; font-size:.78rem; color:#1e293b; }
.te-exp-multi-option:hover { background:#eff6ff; }
.te-exp-multi-option input { accent-color:#0453cb; flex-shrink:0; }
.te-exp-multi-summary { display:flex; flex-wrap:wrap; gap:.25rem; margin-top:.3rem; max-height:85px; overflow-y:auto; }
.te-exp-multi-tag { display:inline-flex; align-items:center; gap:.35rem; color:#033a8e; background:#eff6ff; border-radius:7px; padding:.25rem .35rem; font-size:.7rem; max-width:100%; overflow-wrap:anywhere; }
.te-exp-multi-tag button { border:0; background:transparent; color:#0453cb; padding:.1rem; cursor:pointer; }
.te-exp-multi-clear { display:inline-flex; background:none; border:0; color:#0453cb; font-size:.72rem; font-weight:700; cursor:pointer; padding:.4rem 0; }
.te-exp-multi-empty { margin:.5rem; color:#64748b; font-size:.75rem; }
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
    .te-exp-grid, .te-exp-multi-grid, .te-exp-compose { grid-template-columns:1fr; }
    .te-exp-column-grid { grid-template-columns:1fr 1fr; }
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
            selections: { classes: [], filieres: [], niveaux: [], matieres: [], parcours: [], ues: [] },
            systemes: ['BTS'],
            options: { classes: [], filieres: [], niveaux: [], matieres: [], parcours: [], ues: [] },
            schema: {},
            selectedColumns: [],
            regroupement: 'aucun',
            orientation: 'auto',
            errors: {},
            init() {
                this.launcher = this.$el;
                try { this.schema = JSON.parse(this.launcher.dataset.structure || '{}'); } catch (_) { this.schema = {}; }
                this.reinitialiserComposition();
                this.$watch('vue', () => { this.reinitialiserComposition(); this.errors = {}; });
                for (const kind of ['classes', 'filieres', 'niveaux', 'matieres', 'parcours', 'ues']) {
                    let rows = [];
                    try { rows = JSON.parse(this.launcher.dataset[kind] || '[]'); } catch (_) { rows = []; }
                    this.options[kind] = rows.map(item => ({
                        id: String(item.id),
                        label: String(item.name),
                        filiere_id: item.filiere_id == null ? null : String(item.filiere_id),
                        niveau_id: item.niveau_etude_id == null ? null : String(item.niveau_etude_id),
                        parcours_id: item.parcours_id == null ? null : String(item.parcours_id),
                        parent_id: item.parent_id == null ? null : String(item.parent_id),
                        systeme: item.systeme || item.systeme_academique || 'BTS',
                        systemes: item.systemes || [item.systeme || item.systeme_academique || 'BTS']
                    }));
                }
            },
            reinitialiserComposition() {
                const def = this.schema[this.vue];
                this.selectedColumns = def ? [...def.defaut] : ['nom'];
                if (this.systemes.includes('LMD') && this.vue === 'matieres') {
                    this.selectedColumns = ['nom', 'classes', 'semestres', 'total'];
                }
                if (this.systemes.includes('LMD') && this.vue === 'synthese') {
                    this.selectedColumns = ['classe', 'systeme', 'semestres', 'total', 'enseignants_saisis'];
                }
                this.regroupement = def?.regroupement || 'aucun';
            },
            choisirRegroupement(key) {
                this.regroupement = key;
                if (this.vue === 'matieres' && key === 'aucun' && !this.selectedColumns.includes('matiere')) {
                    this.selectedColumns = [...this.selectedColumns, 'matiere'];
                }
                this.errors = {};
            },
            changerColonne(key, checked) {
                this.selectedColumns = checked
                    ? [...new Set([...this.selectedColumns, key])]
                    : this.selectedColumns.filter(colonne => colonne !== key);
                this.errors = {};
            },
            changerSysteme(systeme, checked) {
                const updated = checked
                    ? [...new Set([...this.systemes, systeme])]
                    : this.systemes.filter(value => value !== systeme);
                if (!updated.length) {
                    this.errors = { systemes: 'Sélectionnez au moins BTS ou LMD.' };
                    this.$nextTick(() => this.systemes = [...this.systemes]);
                    return;
                }
                const anciensSystemes = [...this.systemes];
                this.systemes = updated;
                if (anciensSystemes.includes('LMD') !== updated.includes('LMD')) {
                    const defaut = this.schema[this.vue]?.defaut || [];
                    const versionLmd = this.vue === 'matieres'
                        ? ['nom', 'classes', 'semestres', 'total']
                        : this.vue === 'synthese'
                            ? ['classe', 'systeme', 'semestres', 'total', 'enseignants_saisis'] : [];
                    if (this.vue === 'matieres' || this.vue === 'synthese') {
                        if ([defaut, versionLmd].some(items => items.join('|') === this.selectedColumns.join('|'))) {
                            this.selectedColumns = updated.includes('LMD') ? [...versionLmd] : [...defaut];
                        }
                    }
                }
                for (const kind of ['classes', 'filieres', 'niveaux', 'matieres', 'parcours', 'ues']) {
                    this.selections[kind] = this.selections[kind].filter(id => {
                        const option = this.options[kind].find(item => item.id === id);
                        return option && this.optionCompatibleSysteme(option);
                    });
                }
                this.selections.classes = this.selections.classes.filter(id => {
                    const cl = this.options.classes.find(item => item.id === id);
                    return cl && this.classeCompatible(cl);
                });
                this.errors = {};
            },
            optionCompatibleSysteme(option) {
                return (option.systemes || [option.systeme || 'BTS'])
                    .some(systeme => this.systemes.includes(systeme));
            },
            hasPedagogicalFilters() {
                return Object.values(this.selections).some(values => values.length > 0);
            },
            optionLabel(kind, id) {
                return this.options[kind].find(o => o.id === String(id))?.label || String(id);
            },
            filiereIncluse(id) {
                if (!this.selections.filieres.length) return true;
                const filieres = this.options.filieres;
                const vus = new Set();
                let actuelle = id;
                while (actuelle && !vus.has(actuelle)) {
                    if (this.selections.filieres.includes(String(actuelle))) return true;
                    vus.add(actuelle);
                    actuelle = filieres.find(f => f.id === String(actuelle))?.parent_id ?? null;
                }
                return false;
            },
            classeCompatible(option) {
                return this.optionCompatibleSysteme(option)
                    && this.filiereIncluse(option.filiere_id)
                    && (!this.selections.niveaux.length || this.selections.niveaux.includes(option.niveau_id))
                    && (!this.selections.parcours.length || this.selections.parcours.includes(option.parcours_id));
            },
            optionsFor(kind, search) {
                const term = String(search || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
                return this.options[kind].filter(option =>
                    this.optionCompatibleSysteme(option) &&
                    (kind !== 'classes' || this.classeCompatible(option)) &&
                    (!term || option.label.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().includes(term))
                );
            },
            toggleSelection(kind, id, checked) {
                const value = String(id);
                this.selections[kind] = checked
                    ? [...new Set([...this.selections[kind], value])]
                    : this.selections[kind].filter(x => x !== value);
                if (kind === 'filieres' || kind === 'niveaux' || kind === 'parcours') {
                    this.selections.classes = this.selections.classes.filter(classeId => {
                        const option = this.options.classes.find(classe => classe.id === classeId);
                        return option && this.classeCompatible(option);
                    });
                }
                this.errors = {};
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
                if (!this.systemes.length) {
                    errors.systemes = 'Sélectionnez BTS, LMD ou les deux.';
                }
                const semestre = Number(params.get('semestre') || 0);
                if (this.systemes.length === 1 && this.systemes[0] === 'BTS' && semestre > 2) {
                    errors.semestre = 'Les semestres S3 à S10 sont réservés au LMD.';
                }
                if (!this.selectedColumns.length) {
                    errors.colonnes = "Choisissez au moins une colonne avant l'export.";
                }
                if (this.vue === 'matieres' && this.regroupement === 'aucun'
                    && !this.selectedColumns.includes('matiere')) {
                    errors.colonnes = "Sans rubriques par matière, affichez la colonne Matière.";
                }
                if (!params.get('annee_universitaire_id')) {
                    errors.annee_universitaire_id = "Choisissez une année universitaire.";
                }
                if (format !== 'excel' && params.get('vue') === 'affectations' && !this.hasPedagogicalFilters()) {
                    errors.perimetre = "Sélectionnez une classe, une filière, un niveau ou une matière pour le PDF détaillé.";
                }
                this.errors = errors;
                if (Object.keys(errors).length > 0) {
                    this.$nextTick(() => {
                        const cible = document.querySelector('.te-exp-field--invalid')
                            || document.querySelector('.te-exp-multi-trigger');
                        cible?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        (cible?.querySelector('.au-select-trigger') || cible)?.focus();
                    });
                    return;
                }

                const key = format === 'preview' ? 'previewUrl' : format === 'pdf' ? 'pdfUrl' : 'excelUrl';
                const base = this.launcher.dataset[key];
                if (!base) return;
                const url = new URL(base, window.location.origin);
                params.forEach((value, name) => { if (value !== '') url.searchParams.append(name, value); });
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
