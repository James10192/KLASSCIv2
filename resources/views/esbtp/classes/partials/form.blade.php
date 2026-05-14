{{--
    Partial formulaire reutilisable pour creation/edition de classe (LMD-aware).

    Parametres:
    - $isModal (bool) : true si utilise dans un modal, false sinon
    - $classe (ESBTPClasse|null) : instance de classe pour edition, null pour creation
    - $filieres (Collection) : liste des filieres (BTS et historique)
    - $niveaux (Collection) : liste des niveaux d'etudes (BTS + Licence/Master/Doctorat)
    - $annees (Collection) : liste des annees universitaires
    - $mentions (Collection) : ESBTPLMDMention with('domaine') — pour le picker LMD
    - $parcours (Collection) : ESBTPLMDParcours with('mention.domaine', 'filiere')

    Logique LMD-aware:
    - Le niveau d'etude determine systeme_academique (BTS|LMD) via ClasseManagementService
    - En mode BTS : selectionner une Filiere
    - En mode LMD : selectionner une Mention (sert semantiquement de Filiere via Option A)
      + optionnel Parcours (cascade depuis Mention). Si pas de Parcours = tronc commun mention.
--}}

@php
    $isEdit = isset($classe) && $classe->id;
    $formId = $isModal ? ($isEdit ? 'modal-edit-classe-form' : 'modal-create-classe-form') : ($isEdit ? 'edit-classe-form' : 'create-classe-form');
    $formAction = $isEdit ? route('esbtp.classes.update', ['classe' => $classe->id]) : route('esbtp.classes.store');

    // Map niveau types pour le JS (data-niveau-types attribute + window global pour retro-compat).
    $niveauTypes = $niveaux->mapWithKeys(fn($n) => [$n->id => $n->type])->toJson();

    // Defaults pour le partial (compatibilite avec controllers qui n'envoient pas encore les nouveaux props)
    $mentionsCollection = isset($mentions) ? $mentions : collect();
    $parcoursCollection = isset($parcours) ? $parcours : collect();

    // Mode initial: deduit de la classe (edit) ou du niveau pre-rempli (old())
    $initialMode = '';
    $initialMentionId = '';
    $initialParcoursId = '';
    $initialFiliereId = '';
    $initialDomaineName = '';

    if ($isEdit) {
        $initialMode = $classe->systeme_academique ?? '';
        $initialParcoursId = $classe->parcours_id ?? '';
        $initialFiliereId = $classe->filiere_id ?? '';
        // En mode LMD : si parcours present, en deduire la mention via parcours->mention_id
        if ($initialMode === 'LMD' && $initialParcoursId) {
            $p = $parcoursCollection->firstWhere('id', $initialParcoursId);
            if ($p) {
                $initialMentionId = $p->mention_id ?? '';
                $initialDomaineName = optional(optional($p->mention)->domaine)->name ?? '';
            }
        } elseif ($initialMode === 'LMD' && $initialFiliereId) {
            // Mention sans parcours : filiere_id est la mention (convention Option A)
            // mais ici filiere_id en BTS != mention en LMD donc on cherche differemment.
            // Convention Option A : filiere_id == mention_id semantiquement en LMD
            $m = $mentionsCollection->firstWhere('id', $initialFiliereId);
            if ($m) {
                $initialMentionId = $m->id;
                $initialDomaineName = optional($m->domaine)->name ?? '';
            }
        }
    }

    // Anciens inputs (en cas de validation echec) prennent la priorite
    $oldFiliereId = old('filiere_id', $initialFiliereId);
    $oldParcoursId = old('parcours_id', $initialParcoursId);
    $oldNiveauId = old('niveau_etude_id', $isEdit ? $classe->niveau_etude_id : '');

    // Le mode initial depend du niveau choisi (old/edit) — calcule cote PHP pour SSR correct
    $oldNiveauObj = $oldNiveauId ? $niveaux->firstWhere('id', (int) $oldNiveauId) : null;
    $renderedMode = '';
    if ($oldNiveauObj) {
        $renderedMode = in_array($oldNiveauObj->type, ['Licence', 'Master', 'Doctorat'], true) ? 'LMD' : 'BTS';
    } elseif ($isEdit) {
        $renderedMode = $classe->systeme_academique ?? '';
    }
@endphp

<form id="{{ $formId }}"
      action="{{ $formAction }}"
      method="POST"
      data-mode="{{ strtolower($renderedMode ?: 'unknown') }}"
      x-data="classeLmdForm()"
      x-init="init()">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if($isModal)
        <input type="hidden" name="is_ajax" value="1">
    @endif

    @if($isEdit && request()->has('return_url') && !$isModal)
        <input type="hidden" name="return_url" value="{{ request()->input('return_url') }}">
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 {{ $isModal ? '' : 'premium-glass' }} mb-4">
                <div class="card-header {{ $isModal ? 'bg-light' : 'bg-white' }} border-0 rounded-top-4">
                    <h6 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Informations de la classe
                    </h6>
                </div>
                <div class="card-body">
                    {{-- COMMUN : Nom + Code --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="{{ $formId }}_name" class="form-label">Nom de la classe <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="{{ $formId }}_name"
                                   name="name"
                                   value="{{ old('name', $isEdit ? $classe->name : '') }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if(!$isEdit)
                                <small class="form-text text-muted">Ex: Licence 1 Droit / 1ère année BTS Génie Civil</small>
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="{{ $formId }}_code" class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('code') is-invalid @enderror"
                                   id="{{ $formId }}_code"
                                   name="code"
                                   value="{{ old('code', $isEdit ? $classe->code : '') }}"
                                   required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if(!$isEdit)
                                <small class="form-text text-muted">Ex: L1-DRT ou 1BTS-GC-BAT</small>
                            @endif
                        </div>
                    </div>

                    {{-- COMMUN : Niveau + Annee --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="{{ $formId }}_niveau_etude_id" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                            <select class="form-select @error('niveau_etude_id') is-invalid @enderror"
                                    id="{{ $formId }}_niveau_etude_id"
                                    name="niveau_etude_id"
                                    data-niveau-types='{!! $niveauTypes !!}'
                                    @change="onNiveauChange($event.target.value)"
                                    required>
                                <option value="">Sélectionner un niveau</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}"
                                            {{ $oldNiveauId == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }} ({{ $niveau->type }} - Année {{ $niveau->year }})
                                    </option>
                                @endforeach
                            </select>
                            @error('niveau_etude_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Mode :
                                <span class="badge ms-1"
                                      :class="mode === 'LMD' ? 'bg-primary' : (mode === 'BTS' ? 'bg-secondary' : 'bg-light text-muted')"
                                      x-text="mode || '—'"></span>
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="{{ $formId }}_annee_universitaire_id" class="form-label">Année universitaire <span class="text-danger">*</span></label>
                            <select class="form-select @error('annee_universitaire_id') is-invalid @enderror"
                                    id="{{ $formId }}_annee_universitaire_id"
                                    name="annee_universitaire_id"
                                    required>
                                <option value="">Sélectionner une année</option>
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id }}"
                                            {{ old('annee_universitaire_id', $isEdit ? $classe->annee_universitaire_id : '') == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('annee_universitaire_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- BTS : Filiere classique.
                         Wrapped in <fieldset :disabled> so filiere_id BTS does NOT submit
                         when the form is in LMD mode (avoid name conflict with mention picker).
                         SSR safety : `disabled` attr also set server-side based on $renderedMode. --}}
                    <fieldset class="row" x-show="mode === 'BTS'" x-cloak x-transition.opacity
                              :disabled="mode !== 'BTS'"
                              {{ $renderedMode !== 'BTS' ? 'disabled' : '' }}
                              style="border:0; padding:0; margin:0;">
                        <div class="col-12 mb-2">
                            <div class="d-flex align-items-center gap-2 text-primary">
                                <i class="fas fa-stream"></i>
                                <strong class="small">Mode BTS</strong>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="{{ $formId }}_filiere_id_bts" class="form-label">Filière <span class="text-danger">*</span></label>
                            <select class="form-select @error('filiere_id') is-invalid @enderror"
                                    id="{{ $formId }}_filiere_id_bts"
                                    name="filiere_id"
                                    x-ref="filiereBts">
                                <option value="">Sélectionner une filière</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}"
                                            {{ $oldFiliereId == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}{{ $filiere->parent ? ' (Option de '.$filiere->parent->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('filiere_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </fieldset>

                    {{-- LMD : Mention + Domaine read-only + Parcours optionnel.
                         Wrapped in <fieldset :disabled> to prevent the picker hidden input
                         AND parcours_id from submitting when mode !== LMD.
                         SSR safety : `disabled` attr also set server-side based on $renderedMode. --}}
                    <fieldset class="row" x-show="mode === 'LMD'" x-cloak x-transition.opacity
                              :disabled="mode !== 'LMD'"
                              {{ $renderedMode !== 'LMD' ? 'disabled' : '' }}
                              style="border:0; padding:0; margin:0;">
                        <div class="col-12 mb-2">
                            <div class="d-flex align-items-center gap-2 text-primary">
                                <i class="fas fa-university"></i>
                                <strong class="small">Mode LMD activé</strong>
                            </div>
                        </div>

                        @if($mentionsCollection->isEmpty())
                            <div class="col-12 mb-3">
                                <div class="alert alert-warning d-flex align-items-start gap-2 mb-0">
                                    <i class="fas fa-exclamation-triangle mt-1"></i>
                                    <div>
                                        <strong>Aucune mention LMD configurée pour cette instance.</strong>
                                        <div class="small mt-1">Demandez à l'administration de créer au moins une mention dans <em>Configuration LMD &rarr; Mentions</em> avant de créer une classe de niveau Licence, Master ou Doctorat.</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mention <span class="text-danger">*</span></label>
                                <x-au-mention-picker
                                    :name="'filiere_id'"
                                    :value="$initialMentionId ?: $oldFiliereId"
                                    :mentions="$mentionsCollection"
                                    placeholder="Sélectionner une mention"
                                    x-ref="mentionPicker"
                                />
                                @error('filiere_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">La mention regroupe les parcours d'une même discipline (ex. Droit, Économie, SVT).</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Domaine</label>
                                <div class="form-control bg-light d-flex align-items-center"
                                     style="min-height: 38px;">
                                    <i class="fas fa-folder-open me-2 text-primary"></i>
                                    <span x-text="domaineName || '— Choisissez d\'abord une mention —'"
                                          :class="domaineName ? 'text-dark fw-semibold' : 'text-muted small'"></span>
                                </div>
                                <small class="form-text text-muted">Hérité automatiquement de la mention sélectionnée.</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="{{ $formId }}_parcours_id" class="form-label">
                                    Parcours
                                    <span class="text-muted small ms-2">(optionnel — vide = tronc commun mention)</span>
                                </label>
                                <select class="form-select @error('parcours_id') is-invalid @enderror"
                                        id="{{ $formId }}_parcours_id"
                                        name="parcours_id"
                                        x-ref="parcoursSelect"
                                        :disabled="mode !== 'LMD' || !mentionId">
                                    <option value="">— Aucun parcours (tronc commun mention) —</option>
                                    @foreach($parcoursCollection as $p)
                                        <option value="{{ $p->id }}"
                                                data-mention-id="{{ $p->mention_id }}"
                                                {{ $oldParcoursId == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}@if($p->code) ({{ $p->code }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('parcours_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Spécialisation au sein de la mention. Laisser vide pour une classe au niveau de la mention.</small>
                            </div>
                        @endif
                    </fieldset>

                    {{-- Etat "niveau pas encore choisi" --}}
                    <div class="row" x-show="!mode" x-cloak>
                        <div class="col-12 mb-3">
                            <div class="alert alert-info d-flex align-items-center gap-2 mb-0">
                                <i class="fas fa-info-circle"></i>
                                <span>Sélectionnez un niveau d'études pour voir les champs spécifiques (Filière en BTS, Mention/Parcours en LMD).</span>
                            </div>
                        </div>
                    </div>

                    {{-- COMMUN : Capacite + Active --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="{{ $formId }}_places_totales" class="form-label">Capacité maximale <span class="text-danger">*</span></label>
                            <input type="number"
                                   min="1"
                                   class="form-control @error('places_totales') is-invalid @enderror"
                                   id="{{ $formId }}_places_totales"
                                   name="places_totales"
                                   value="{{ old('places_totales', $isEdit ? $classe->places_totales : 30) }}"
                                   required>
                            @error('places_totales')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input @error('is_active') is-invalid @enderror"
                                       type="checkbox"
                                       id="{{ $formId }}_is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $isEdit ? $classe->is_active : 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $formId }}_is_active">
                                    Classe active
                                </label>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- COMMUN : Description --}}
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="{{ $formId }}_description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="{{ $formId }}_description"
                                      name="description"
                                      rows="3"
                                      placeholder="Description détaillée de la classe">{{ old('description', $isEdit ? $classe->description : '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$isModal)
        <div class="d-flex justify-content-end gap-3 mt-4">
            @if($isEdit)
                <a href="{{ request()->input('return_url', route('esbtp.classes.show', ['classe' => $classe->id])) }}"
                   class="btn btn-lg btn-secondary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2">
                    <i class="fas fa-save"></i> Mettre à jour la classe
                </button>
            @else
                <a href="{{ route('esbtp.student.classes.index') }}"
                   class="btn btn-lg btn-secondary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2">
                    <i class="fas fa-save"></i> Enregistrer la classe
                </button>
            @endif
        </div>
    @endif
</form>

{{--
    INLINE <script> (pas @push('scripts')) pour que la factory soit incluse dans la
    reponse AJAX standalone (modal load via fetch + injectHtmlWithScripts helper).

    @push sans @stack parent = drop silencieux du contenu en reponse AJAX. Voir rule
    .claude/rules/premium-selects.md section "AJAX-safe pattern". Idempotency guard
    `if typeof window.classeLmdForm === 'function'` empeche le double-register quand
    le partial est rendu plusieurs fois sur la meme page (create + edit modals
    coexistent dans classes.index).
--}}
<script>
(function () {
    if (typeof window.classeLmdForm === 'function') return;

    window.classeLmdForm = function () {
        return {
            mode: @json($renderedMode),
            mentionId: @json($initialMentionId),
            domaineName: @json($initialDomaineName),
            niveauTypes: {},
            previousMode: '',
            _mentionChangedHandler: null,

            init() {
                var sel = this.$el.querySelector('select[name="niveau_etude_id"]');
                if (sel) {
                    try { this.niveauTypes = JSON.parse(sel.getAttribute('data-niveau-types') || '{}'); }
                    catch (e) { this.niveauTypes = {}; }
                }
                this.previousMode = this.mode;

                // Listener cascade mention -> domaine + parcours
                // Stocker la reference pour cleanup dans destroy() (anti memory leak
                // quand modal est ouvert/ferme plusieurs fois — bug bonus identifie
                // par critic durant /plan-and-confirm depth=5).
                this._mentionChangedHandler = (ev) => {
                    this.mentionId = ev.detail.mentionId ? String(ev.detail.mentionId) : '';
                    this.domaineName = ev.detail.domaineName || '';
                    this.filterParcoursOptions();
                };
                window.addEventListener('mention:changed', this._mentionChangedHandler);

                this.$nextTick(() => this.filterParcoursOptions());
            },

            destroy() {
                // Alpine appelle destroy() automatiquement quand le composant est
                // retire du DOM (modal close + innerHTML replace). Cleanup obligatoire
                // sinon chaque re-open ajoute un handler supplementaire.
                if (this._mentionChangedHandler) {
                    window.removeEventListener('mention:changed', this._mentionChangedHandler);
                    this._mentionChangedHandler = null;
                }
            },

            onNiveauChange(niveauId) {
                var type = this.niveauTypes[niveauId] || '';
                var newMode = (type === 'Licence' || type === 'Master' || type === 'Doctorat') ? 'LMD' : (type ? 'BTS' : '');

                if (this.previousMode && newMode !== this.previousMode) {
                    this.resetCrossModeFields(newMode);
                }
                this.mode = newMode;
                this.previousMode = newMode;
            },

            resetCrossModeFields(newMode) {
                if (newMode === 'LMD') {
                    if (this.$refs.filiereBts) {
                        this.$refs.filiereBts.value = '';
                    }
                }
                if (newMode === 'BTS') {
                    this.mentionId = '';
                    this.domaineName = '';
                    if (this.$refs.parcoursSelect) {
                        this.$refs.parcoursSelect.value = '';
                    }
                    if (this.$refs.mentionPicker && this.$refs.mentionPicker._x_dataStack) {
                        var data = this.$refs.mentionPicker._x_dataStack[0];
                        if (data && typeof data.reset === 'function') data.reset();
                    }
                }
            },

            filterParcoursOptions() {
                if (!this.$refs.parcoursSelect) return;
                var sel = this.$refs.parcoursSelect;
                var currentMention = this.mentionId;

                Array.from(sel.options).forEach(function (opt) {
                    if (!opt.value) {
                        opt.hidden = false;
                        return;
                    }
                    var optMention = opt.getAttribute('data-mention-id');
                    var matches = currentMention && String(optMention) === String(currentMention);
                    opt.hidden = !matches;
                });

                var selectedOpt = sel.options[sel.selectedIndex];
                if (selectedOpt && selectedOpt.hidden) {
                    sel.value = '';
                }
            },
        };
    };
})();
</script>

@if(!$isModal)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof initClasseFormScripts === 'function') {
            initClasseFormScripts('{{ $formId }}');
        }
    });
</script>
@endif
