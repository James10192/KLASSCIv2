{{--
    Partial formulaire réutilisable pour création/édition de classe

    Paramètres:
    - $isModal (bool) : true si utilisé dans un modal, false sinon
    - $classe (ESBTPClasse|null) : instance de classe pour édition, null pour création
    - $filieres (Collection) : liste des filières
    - $niveaux (Collection) : liste des niveaux
    - $annees (Collection) : liste des années universitaires
--}}

@php
    $isEdit = isset($classe) && $classe->id;
    $formId = $isModal ? ($isEdit ? 'modal-edit-classe-form' : 'modal-create-classe-form') : ($isEdit ? 'edit-classe-form' : 'create-classe-form');
    $formAction = $isEdit ? route('esbtp.classes.update', ['classe' => $classe->id]) : route('esbtp.classes.store');
    $formMethod = $isEdit ? 'PUT' : 'POST';
    // Map niveau types pour le JS (data-niveau-types attribute + window global pour rétro-compat).
    // DOIT être défini AVANT la première utilisation dans le <select> ligne ~101.
    $niveauTypes = $niveaux->mapWithKeys(fn($n) => [$n->id => $n->type])->toJson();
@endphp

<form id="{{ $formId }}" action="{{ $formAction }}" method="POST">
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
                                <small class="form-text text-muted">Ex: 1ère année BTS Génie Civil Option Bâtiment</small>
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
                                <small class="form-text text-muted">Ex: 1BTS-GC-BAT</small>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="{{ $formId }}_filiere_id" class="form-label">Filière <span class="text-danger">*</span></label>
                            <select class="form-select @error('filiere_id') is-invalid @enderror"
                                    id="{{ $formId }}_filiere_id"
                                    name="filiere_id"
                                    required>
                                <option value="">Sélectionner une filière</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}"
                                            {{ old('filiere_id', $isEdit ? $classe->filiere_id : '') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }} {{ $filiere->parent ? '(Option de '.$filiere->parent->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('filiere_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="{{ $formId }}_niveau_etude_id" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                            <select class="form-select @error('niveau_etude_id') is-invalid @enderror"
                                    id="{{ $formId }}_niveau_etude_id"
                                    name="niveau_etude_id"
                                    data-niveau-types='{!! $niveauTypes !!}'
                                    required>
                                <option value="">Sélectionner un niveau</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}"
                                            {{ old('niveau_etude_id', $isEdit ? $classe->niveau_etude_id : '') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }} ({{ $niveau->type }} - Année {{ $niveau->year }})
                                    </option>
                                @endforeach
                            </select>
                            @error('niveau_etude_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
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

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Système académique</label>
                            <div id="{{ $formId }}_systeme_badge" class="form-control-plaintext">
                                @if($isEdit)
                                    <span class="badge {{ $classe->systeme_academique === 'LMD' ? 'bg-primary' : 'bg-secondary' }}" style="font-size: 0.85rem; padding: 0.4em 0.8em;">
                                        {{ $classe->systeme_academique ?? 'BTS' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary" style="font-size: 0.85rem; padding: 0.4em 0.8em;">—</span>
                                @endif
                            </div>
                            <small class="form-text text-muted">Déterminé automatiquement par le niveau d'études (Licence/Master/Doctorat → LMD, sinon BTS)</small>
                        </div>
                        <div class="col-md-4 mb-3" id="{{ $formId }}_parcours_group"
                             style="{{ ($isEdit && ($classe->systeme_academique ?? 'BTS') === 'LMD') ? '' : 'display:none;' }}">
                            <label for="{{ $formId }}_parcours_id" class="form-label">Parcours LMD</label>
                            <select class="form-select @error('parcours_id') is-invalid @enderror"
                                    id="{{ $formId }}_parcours_id"
                                    name="parcours_id">
                                <option value="">— Aucun parcours —</option>
                                @foreach(\App\Models\ESBTPLMDParcours::with('mention.domaine', 'filiere')->orderBy('name')->get() as $p)
                                    <option value="{{ $p->id }}"
                                            {{ old('parcours_id', $isEdit ? $classe->parcours_id : '') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                        @if($p->filiere) ({{ $p->filiere->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('parcours_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Associer à un parcours pour la hiérarchie Domaine/Mention/Parcours</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <!-- Vide pour l'alignement -->
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
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
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch {{ $isModal ? 'mt-4' : 'mt-4' }}">
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
                        <div class="col-md-4 mb-3">
                            <!-- Vide pour l'alignement -->
                        </div>
                    </div>
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
                <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
                    <i class="fas fa-save"></i> Mettre à jour la classe
                </button>
            @else
                <a href="{{ route('esbtp.student.classes.index') }}"
                   class="btn btn-lg btn-secondary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
                    <i class="fas fa-save"></i> Enregistrer la classe
                </button>
            @endif
        </div>
    @endif
</form>

{{-- JavaScript pour auto-détection système académique + toggle parcours LMD --}}
{{-- $niveauTypes est défini dans le @php du haut (utilisé aussi par data-niveau-types L101) --}}
<script>
(function() {
    window['niveauTypes_{{ str_replace('-', '_', $formId) }}'] = {!! $niveauTypes !!};

    var niveauSelect = document.getElementById('{{ $formId }}_niveau_etude_id');
    if (niveauSelect) {
        niveauSelect.addEventListener('change', function() {
            var type = (window['niveauTypes_{{ str_replace('-', '_', $formId) }}'] || {})[this.value] || '';
            var isLMD = (type === 'Licence' || type === 'Master' || type === 'Doctorat');
            var badge = document.getElementById('{{ $formId }}_systeme_badge');
            if (badge) {
                badge.innerHTML = '<span class="badge ' + (isLMD ? 'bg-primary' : 'bg-secondary') + '" style="font-size:0.85rem;padding:0.4em 0.8em;">' + (isLMD ? 'LMD' : (type ? 'BTS' : '—')) + '</span>';
            }
            var parcoursGroup = document.getElementById('{{ $formId }}_parcours_group');
            if (parcoursGroup) {
                parcoursGroup.style.display = isLMD ? '' : 'none';
                if (!isLMD) {
                    var sel = document.getElementById('{{ $formId }}_parcours_id');
                    if (sel) sel.value = '';
                }
            }
        });
    }
})();
</script>

{{-- JavaScript pour auto-génération code et Select2 (seulement si non-modal) --}}
@if(!$isModal)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initClasseFormScripts('{{ $formId }}');
    });
</script>
@endif
