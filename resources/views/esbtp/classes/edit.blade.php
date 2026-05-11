@extends('layouts.app')

@section('title', 'Modifier une classe - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Modifier la classe</h1>
                <p class="header-subtitle">{{ $classe->name }}</p>
            </div>
            <div class="header-actions">
                {{-- Lien "Voir les détails" avec filtres préservés --}}
                @php
                    $returnUrl = request()->input('return_url', route('esbtp.classes.show', ['classe' => $classe->id]));
                    $queryParams = request()->query();
                    unset($queryParams['return_url']);
                @endphp
                <a href="{{ route('esbtp.classes.show', array_merge(['classe' => $classe->id], $queryParams)) }}" class="btn-acasi info">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                {{-- Bouton "Retour" avec return_url --}}
                <a href="{{ $returnUrl }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
                @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-center glass-alert mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $niveauTypes = $niveaux->pluck('type', 'id')->all();
                    $parcoursOptions = $parcours->mapWithKeys(function ($p) {
                        return [$p->id => $p->label_complet ?: $p->name];
                    })->all();
                    $oldFiliere  = old('filiere_id',  $classe->filiere_id);
                    $oldNiveau   = old('niveau_etude_id', $classe->niveau_etude_id);
                    $oldParcours = old('parcours_id', $classe->parcours_id);
                @endphp

                <form action="{{ route('esbtp.classes.update', ['classe' => $classe->id]) }}" method="POST"
                      x-data="classeFormMode({
                          niveauTypes: @js($niveauTypes),
                          niveauId: @js((string)($oldNiveau ?? '')),
                          parcoursId: @js((string)($oldParcours ?? '')),
                      })">
                    @csrf
                    @method('PUT')

                    {{-- Input caché pour préserver l'URL de retour --}}
                    @if(request()->has('return_url'))
                        <input type="hidden" name="return_url" value="{{ request()->input('return_url') }}">
                    @endif
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-lg rounded-4 premium-glass mb-4">
                                <div class="card-header bg-white border-0 rounded-top-4">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-chalkboard-teacher me-2"></i> Informations de la classe
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Nom de la classe <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $classe->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $classe->code) }}" required>
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="niveau_etude_id" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                                            <select class="form-select @error('niveau_etude_id') is-invalid @enderror" id="niveau_etude_id" name="niveau_etude_id" required
                                                    @change="niveauId = $event.target.value; onModeChanged()">
                                                <option value="">Sélectionner un niveau</option>
                                                @foreach($niveaux as $niveau)
                                                    <option value="{{ $niveau->id }}" {{ $oldNiveau == $niveau->id ? 'selected' : '' }}>
                                                        {{ $niveau->name }} ({{ $niveau->type }} - Année {{ $niveau->year }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('niveau_etude_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                <span x-show="!systemeAcademique" x-cloak>Système académique : auto-déterminé après choix du niveau</span>
                                                <span x-show="systemeAcademique === 'BTS'" x-cloak><i class="fas fa-graduation-cap"></i> Système : <strong>BTS</strong></span>
                                                <span x-show="systemeAcademique === 'LMD'" x-cloak><i class="fas fa-university"></i> Système : <strong>LMD</strong> (UEMOA)</span>
                                            </small>
                                        </div>

                                        {{-- BTS mode : afficher Filière --}}
                                        <div class="col-md-4 mb-3" x-show="systemeAcademique !== 'LMD'" x-cloak>
                                            <label for="filiere_id" class="form-label">Filière <span class="text-danger">*</span></label>
                                            <select class="form-select @error('filiere_id') is-invalid @enderror" id="filiere_id" name="filiere_id"
                                                    :required="systemeAcademique !== 'LMD'" :disabled="systemeAcademique === 'LMD'">
                                                <option value="">Sélectionner une filière</option>
                                                @foreach($filieres as $filiere)
                                                    <option value="{{ $filiere->id }}" {{ $oldFiliere == $filiere->id ? 'selected' : '' }}>
                                                        {{ $filiere->name }} {{ $filiere->parent ? '(Option de '.$filiere->parent->name.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('filiere_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- LMD mode : afficher Parcours --}}
                                        <div class="col-md-4 mb-3" x-show="systemeAcademique === 'LMD'" x-cloak>
                                            <label for="parcours_id" class="form-label">Parcours LMD <span class="text-danger">*</span></label>
                                            <select class="form-select @error('parcours_id') is-invalid @enderror" id="parcours_id" name="parcours_id"
                                                    :required="systemeAcademique === 'LMD'" :disabled="systemeAcademique !== 'LMD'"
                                                    @change="parcoursId = $event.target.value">
                                                <option value="">Sélectionner un parcours</option>
                                                @foreach($parcoursOptions as $id => $label)
                                                    <option value="{{ $id }}" {{ $oldParcours == $id ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('parcours_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Hiérarchie UEMOA : Domaine &gt; Mention &gt; Parcours. La filière sera dérivée automatiquement.
                                            </small>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="annee_universitaire_id" class="form-label">Année universitaire <span class="text-danger">*</span></label>
                                            <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                                                <option value="">Sélectionner une année</option>
                                                @foreach($annees as $annee)
                                                    <option value="{{ $annee->id }}" {{ old('annee_universitaire_id', $classe->annee_universitaire_id) == $annee->id ? 'selected' : '' }}>
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
                                            <label for="places_totales" class="form-label">Capacité maximale <span class="text-danger">*</span></label>
                                            <input type="number" min="1" class="form-control @error('places_totales') is-invalid @enderror" id="places_totales" name="places_totales" value="{{ old('places_totales', $classe->places_totales) }}" required>
                                            @error('places_totales')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input @error('is_active') is-invalid @enderror" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $classe->is_active) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
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
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Description détaillée de la classe">{{ old('description', $classe->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        {{-- Bouton "Annuler" avec return_url --}}
                        <a href="{{ request()->input('return_url', route('esbtp.classes.show', ['classe' => $classe->id])) }}" class="btn btn-lg btn-secondary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
                            <i class="fas fa-save"></i> Mettre à jour la classe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Améliorer les sélecteurs avec select2 si disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $('#filiere_id, #parcours_id, #niveau_etude_id, #annee_universitaire_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionner une option',
                allowClear: true
            });

            // Quand Alpine change la value du select natif (BTS/LMD switch),
            // Select2 doit rafraîchir son affichage
            const reSelect2 = function (id) {
                const $el = $('#' + id);
                if ($el.length && $el.hasClass('select2-hidden-accessible')) {
                    $el.trigger('change.select2');
                }
            };

            // Sync Alpine -> Select2 quand le mode change
            $('#niveau_etude_id').on('change', function () {
                setTimeout(function () {
                    reSelect2('filiere_id');
                    reSelect2('parcours_id');
                }, 50);
            });
        }
    });

    // Alpine factory : détecte BTS vs LMD selon le type du niveau choisi
    window.classeFormMode = function (config) {
        return {
            niveauTypes: config.niveauTypes || {},
            niveauId: String(config.niveauId || ''),
            parcoursId: String(config.parcoursId || ''),
            lmdTypes: ['Licence', 'Master', 'Doctorat', 'Bachelor'],
            get systemeAcademique() {
                if (!this.niveauId) return null;
                const type = this.niveauTypes[this.niveauId] || this.niveauTypes[Number(this.niveauId)];
                if (!type) return null;
                return this.lmdTypes.includes(type) ? 'LMD' : 'BTS';
            },
            onModeChanged() {
                // Vider le select opposé pour éviter l'envoi de valeurs résiduelles
                if (this.systemeAcademique === 'LMD') {
                    const fil = document.getElementById('filiere_id');
                    if (fil) fil.value = '';
                } else if (this.systemeAcademique === 'BTS') {
                    const par = document.getElementById('parcours_id');
                    if (par) par.value = '';
                    this.parcoursId = '';
                }
            },
        };
    };
</script>
@endsection
