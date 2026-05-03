@extends('layouts.app')

@section('title', 'Créer une annonce — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

{{-- Shared CSS partial — namespace ac-* (~600 lignes) extrait pour DRY avec edit.blade.php --}}
@include('esbtp.annonces._form-styles')
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- Header standard (rule premium-redesign : pages create utilisent dashboard-header) --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-bullhorn me-2"></i>Créer une annonce</h1>
                <p class="header-subtitle">Diffuser un message aux étudiants ciblés. Brouillon enregistré tant que vous n'envoyez pas.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-modern error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Erreur de validation</h4>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('esbtp.annonces.store') }}" method="POST" enctype="multipart/form-data" id="annonceForm">
            @csrf

            <div class="ac-grid">

                {{-- =================== COLONNE PRINCIPALE =================== --}}
                <div class="ac-main">

                    {{-- ===== Carte 1 : Message ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-pen-nib"></i></span>
                            <div>
                                <div class="ac-card-title">Composer le message</div>
                                <div class="ac-card-sub">Objet, contenu et pièce jointe optionnelle</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-field">
                                <label for="titre" class="ac-label">
                                    Objet de l'annonce <span class="ac-req">*</span>
                                </label>
                                <input type="text" id="titre" name="titre"
                                       class="ac-input @error('titre') is-invalid @enderror"
                                       value="{{ old('titre') }}"
                                       placeholder="Ex : Conseil pédagogique du 15 mai, Rentrée 2026..."
                                       maxlength="255" required>
                                <div class="ac-counter" data-counter-for="titre" data-max="255">
                                    Soyez clair et concis · <strong><span data-counter-current>0</span></strong>/255
                                </div>
                                @error('titre')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label for="contenu" class="ac-label">
                                    Corps du message <span class="ac-req">*</span>
                                </label>
                                <textarea id="contenu" name="contenu"
                                          class="ac-textarea @error('contenu') is-invalid @enderror"
                                          rows="8" required
                                          placeholder="Rédigez votre annonce. Soyez précis sur la date, le lieu et le public concerné.">{{ old('contenu') }}</textarea>
                                <div class="ac-counter" data-counter-for="contenu">
                                    <span><strong><span data-counter-current>0</span></strong> caractères</span>
                                    <span class="ac-help">Markdown léger non interprété — texte brut</span>
                                </div>
                                @error('contenu')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label for="piece_jointe" class="ac-label">Pièce jointe (optionnelle)</label>
                                <label class="ac-file" id="ac-file-zone" for="piece_jointe">
                                    <input type="file" id="piece_jointe" name="piece_jointe"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    <div class="ac-file-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="ac-file-label" id="ac-file-label">Cliquez ou déposez un fichier</div>
                                    <div class="ac-file-hint">PDF · Word · Excel · Image — 5 MB max</div>
                                    <div class="ac-file-pill" id="ac-file-pill">
                                        <i class="fas fa-paperclip"></i><span id="ac-file-name"></span>
                                    </div>
                                </label>
                                @error('piece_jointe')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- ===== Carte 2 : Ciblage ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-users"></i></span>
                            <div>
                                <div class="ac-card-title">Destinataires</div>
                                <div class="ac-card-sub">À qui envoyer cette annonce ?</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-field">
                                <span class="ac-label">Type de diffusion <span class="ac-req">*</span></span>
                                <div class="ac-audience">
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="general" required
                                               {{ old('type', 'general') === 'general' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-globe"></i></span>
                                            <span class="ac-audience-opt-name">Tous les étudiants</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Diffusion générale à l'ensemble de l'école.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="classe" required
                                               {{ old('type') === 'classe' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-chalkboard"></i></span>
                                            <span class="ac-audience-opt-name">Classes ciblées</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Une ou plusieurs classes spécifiques.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="etudiant" required
                                               {{ old('type') === 'etudiant' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-user-graduate"></i></span>
                                            <span class="ac-audience-opt-name">Étudiants nominatifs</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Sélection individuelle d'étudiants.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                </div>
                                @error('type')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            {{-- Picker Classes --}}
                            <div id="classes_picker" class="ac-picker">
                                <div class="ac-picker-row">
                                    <div class="ac-picker-meta">
                                        <span class="ac-picker-title">
                                            <i class="fas fa-chalkboard"></i> Classes destinataires
                                            <span class="ac-picker-count-badge ac-picker-count-badge--empty" id="classes_count_badge">0</span>
                                        </span>
                                        <div class="ac-picker-summary" id="classes_summary">Aucune classe sélectionnée</div>
                                    </div>
                                    <button type="button" class="ac-btn ac-btn-secondary"
                                            data-bs-toggle="modal" data-bs-target="#classesModal">
                                        <i class="fas fa-layer-group"></i>Choisir les classes
                                    </button>
                                </div>
                                @error('classes')<div class="ac-error mt-2">{{ $message }}</div>@enderror
                            </div>

                            {{-- Picker Étudiants --}}
                            <div id="etudiants_picker" class="ac-picker">
                                <div class="ac-picker-row">
                                    <div class="ac-picker-meta">
                                        <span class="ac-picker-title">
                                            <i class="fas fa-user-graduate"></i> Étudiants destinataires
                                            <span class="ac-picker-count-badge ac-picker-count-badge--empty" id="etudiants_count_badge">0</span>
                                        </span>
                                        <div class="ac-picker-summary" id="etudiants_summary">Aucun étudiant sélectionné</div>
                                    </div>
                                    <button type="button" class="ac-btn ac-btn-secondary"
                                            data-bs-toggle="modal" data-bs-target="#etudiantsModal">
                                        <i class="fas fa-user-check"></i>Choisir les étudiants
                                    </button>
                                </div>
                                @error('etudiants')<div class="ac-error mt-2">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                </div>

                {{-- =================== COLONNE LATÉRALE =================== --}}
                <aside class="ac-aside">

                    {{-- ===== Sidebar — Actions ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-paper-plane"></i></span>
                            <div>
                                <div class="ac-card-title">Actions</div>
                                <div class="ac-card-sub">Enregistrer ou diffuser maintenant</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-notice">
                                <i class="fas fa-info-circle"></i>
                                <div class="ac-notice-text">
                                    <strong>Mode brouillon par défaut.</strong>
                                    Votre annonce reste invisible aux étudiants tant que vous n'avez pas cliqué « Envoyer maintenant ».
                                </div>
                            </div>

                            <div class="ac-actions">
                                <button type="submit" name="action" value="publish" class="ac-btn ac-btn-primary">
                                    <i class="fas fa-paper-plane"></i>Envoyer maintenant
                                </button>
                                <button type="submit" name="action" value="save_draft"
                                        class="ac-btn ac-btn-secondary" id="saveDraftButton">
                                    <i class="fas fa-save"></i>Sauvegarder en brouillon
                                </button>
                                <button type="reset" class="ac-btn ac-btn-ghost">
                                    <i class="fas fa-undo"></i>Réinitialiser le formulaire
                                </button>
                            </div>

                            <div class="ac-actions-help">
                                <i class="fas fa-clock me-1"></i>
                                Pensez à sauvegarder si vous quittez la page sans envoyer — sinon vos modifications seront perdues.
                            </div>

                            <input type="hidden" name="is_published" value="0">

                        </div>
                    </div>

                    {{-- ===== Sidebar — Publication ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-cog"></i></span>
                            <div>
                                <div class="ac-card-title">Paramètres de publication</div>
                                <div class="ac-card-sub">Date d'expiration & priorité</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-field">
                                <label for="date_expiration" class="ac-label">
                                    Date d'expiration <span class="ac-req">*</span>
                                </label>
                                <input type="datetime-local" id="date_expiration" name="date_expiration"
                                       class="ac-input @error('date_expiration') is-invalid @enderror"
                                       value="{{ old('date_expiration', now()->addMonths(1)->format('Y-m-d\TH:i')) }}"
                                       required>
                                <div class="ac-help">L'annonce sera retirée des fils étudiants après cette date.</div>
                                @error('date_expiration')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label for="priorite" class="ac-label">Niveau d'urgence</label>
                                <x-au-select
                                    name="priorite"
                                    :value="old('priorite', '0')"
                                    icon="fa-flag"
                                    :options="[
                                        '0' => 'Normale — visible dans le fil',
                                        '1' => 'Importante — épinglée en haut',
                                        '2' => 'Urgente — notification renforcée',
                                    ]" />
                                @error('priorite')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- ===== Sidebar — Conseils ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-lightbulb"></i></span>
                            <div>
                                <div class="ac-card-title">Bonnes pratiques</div>
                                <div class="ac-card-sub">Pour une annonce efficace</div>
                            </div>
                        </div>
                        <div class="ac-card-body">
                            <div class="ac-tips">
                                <div class="ac-tip">
                                    <i class="fas fa-bullseye"></i>
                                    <div class="ac-tip-text"><strong>Ciblez précisément</strong> — préférez « Classes » ou « Étudiants » plutôt que « Tous » pour éviter le bruit.</div>
                                </div>
                                <div class="ac-tip">
                                    <i class="fas fa-clock"></i>
                                    <div class="ac-tip-text"><strong>Date d'expiration courte</strong> — une annonce visible 6 mois perd son caractère urgent.</div>
                                </div>
                                <div class="ac-tip">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div class="ac-tip-text"><strong>Urgence rare</strong> — réservez « Urgente » aux infos critiques (annulation cours, sécurité).</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </aside>
            </div>

            {{-- =================== MODAL CLASSES =================== --}}
            <div class="modal fade ac-modal" id="classesModal" tabindex="-1"
                 aria-labelledby="classesModalLabel" aria-hidden="true" data-bs-backdrop="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="ac-modal-titlewrap">
                                <span class="ac-modal-title" id="classesModalLabel">
                                    <i class="fas fa-chalkboard"></i> Sélectionner les classes
                                </span>
                                <span class="ac-modal-sub">Filtrez par filière et niveau, puis cochez les classes destinataires.</span>
                            </div>
                            <div class="ac-modal-actions">
                                <button type="button" class="ac-modal-btn-glass" id="select_all_classes">
                                    <i class="fas fa-check-double"></i>Tout sélectionner
                                </button>
                                <button type="button" class="ac-modal-btn-glass" id="clear_classes_selection">
                                    <i class="fas fa-eraser"></i>Vider
                                </button>
                                <button type="button" class="ac-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="modal-body">

                            <div class="ac-filters">
                                <select id="filiere_filter" aria-label="Filtrer par filière">
                                    <option value="">Toutes les filières</option>
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                    @endforeach
                                </select>
                                <select id="niveau_filter" aria-label="Filtrer par niveau">
                                    <option value="">Tous les niveaux</option>
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="ac-bulk-btn reset-filters" title="Réinitialiser les filtres">
                                    <i class="fas fa-rotate-left"></i>Réinitialiser
                                </button>
                            </div>

                            <div class="ac-bulk">
                                <div class="ac-bulk-info">
                                    <i class="fas fa-list"></i>
                                    <span><strong id="classes_total_visible">{{ $classes->count() }}</strong> classes disponibles</span>
                                </div>
                            </div>

                            <div class="ac-field">
                                <label for="classes" class="ac-label">
                                    Classes destinataires <span class="ac-req">*</span>
                                </label>
                                <select class="ac-multi-native @error('classes') is-invalid @enderror"
                                        id="classes" name="classes[]" multiple>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}"
                                                data-filiere="{{ $classe->filiere_id }}"
                                                data-niveau="{{ $classe->niveau_etude_id }}"
                                                data-current-count="{{ $classe->current_inscriptions_count ?? 0 }}"
                                                {{ (old('classes') && in_array($classe->id, old('classes'))) ? 'selected' : '' }}>
                                            {{ $classe->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('classes')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                        <div class="modal-footer">
                            <span class="ac-help"><i class="fas fa-info-circle me-1"></i>Vous pouvez sélectionner jusqu'à 20 classes.</span>
                            <button type="button" class="ac-btn ac-btn-primary" data-bs-dismiss="modal">
                                <i class="fas fa-check"></i>Terminer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =================== MODAL ÉTUDIANTS =================== --}}
            <div class="modal fade ac-modal" id="etudiantsModal" tabindex="-1"
                 aria-labelledby="etudiantsModalLabel" aria-hidden="true" data-bs-backdrop="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="ac-modal-titlewrap">
                                <span class="ac-modal-title" id="etudiantsModalLabel">
                                    <i class="fas fa-user-graduate"></i> Sélectionner les étudiants
                                </span>
                                <span class="ac-modal-sub">Filtrez par classe puis cochez les étudiants destinataires.</span>
                            </div>
                            <div class="ac-modal-actions">
                                <button type="button" class="ac-modal-btn-glass" id="select_all_etudiants">
                                    <i class="fas fa-check-double"></i>Tout sélectionner
                                </button>
                                <button type="button" class="ac-modal-btn-glass" id="clear_etudiants_selection">
                                    <i class="fas fa-eraser"></i>Vider
                                </button>
                                <button type="button" class="ac-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="modal-body">

                            <div class="ac-filters ac-filters--single">
                                <select id="classe_etudiant_filter" aria-label="Filtrer par classe">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="ac-bulk-btn reset-filters" title="Réinitialiser le filtre">
                                    <i class="fas fa-rotate-left"></i>Réinitialiser
                                </button>
                            </div>

                            <div class="ac-bulk">
                                <div class="ac-bulk-info">
                                    <i class="fas fa-list"></i>
                                    <span id="etudiants-info"><strong>{{ $etudiants->count() }}</strong> étudiant(s) disponible(s)</span>
                                </div>
                            </div>

                            <div class="ac-field">
                                <label for="etudiants" class="ac-label">
                                    Étudiants destinataires <span class="ac-req">*</span>
                                </label>
                                <select class="ac-multi-native @error('etudiants') is-invalid @enderror"
                                        id="etudiants" name="etudiants[]" multiple>
                                    @foreach($etudiants as $etudiant)
                                        <option value="{{ $etudiant->id }}"
                                                data-classe="{{ optional($etudiant->inscriptions->first())->classe_id }}"
                                                data-current-year="{{ ($etudiant->current_inscriptions_count ?? 0) > 0 ? 1 : 0 }}"
                                                {{ (old('etudiants') && in_array($etudiant->id, old('etudiants'))) ? 'selected' : '' }}>
                                            {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('etudiants')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                        </div>
                        <div class="modal-footer">
                            <span class="ac-help"><i class="fas fa-info-circle me-1"></i>Sélection multiple jusqu'à 50 étudiants.</span>
                            <button type="button" class="ac-btn ac-btn-primary" data-bs-dismiss="modal">
                                <i class="fas fa-check"></i>Terminer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =================== MODAL LEAVE DRAFT =================== --}}
            <div class="modal fade ac-modal" id="leaveDraftModal" tabindex="-1"
                 aria-labelledby="leaveDraftModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="ac-modal-titlewrap">
                                <span class="ac-modal-title" id="leaveDraftModalLabel">
                                    <i class="fas fa-exclamation-triangle"></i> Quitter sans enregistrer ?
                                </span>
                                <span class="ac-modal-sub">Vos modifications ne sont pas encore sauvegardées.</span>
                            </div>
                            <button type="button" class="ac-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0" style="font-size: .9rem; color: #475569; line-height: 1.5;">
                                Voulez-vous conserver cette annonce en brouillon ? Vous pourrez la retrouver dans la liste des annonces et la publier plus tard.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="ac-btn ac-btn-ghost" id="leaveDiscard">
                                Quitter sans sauver
                            </button>
                            <button type="button" class="ac-btn ac-btn-primary" id="leaveSaveDraft">
                                <i class="fas fa-save"></i>Sauvegarder le brouillon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
{{-- Shared JS partial — Choices.js init, snapshots, filters, bulk, validation, etc. --}}
@include('esbtp.annonces._form-scripts', ['debugTag' => 'annonces:create'])

{{-- Comportement spécifique create : leave-draft modal + beforeunload guard. --}}
<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('annonceForm');
        if (!form) return;

        let formDirty = false;
        let isSubmitting = false;
        let pendingNavigation = null;

        const setDirty = () => { if (!isSubmitting) formDirty = true; };

        document.querySelectorAll('input, textarea, select').forEach(el => {
            if (el.getAttribute('type') === 'hidden') return;
            el.addEventListener('input', setDirty);
            el.addEventListener('change', setDirty);
        });

        form.addEventListener('submit', () => { isSubmitting = true; });

        document.addEventListener('click', function (e) {
            const a = e.target.closest('a[href]');
            if (!a) return;
            const href = a.getAttribute('href');
            const target = a.getAttribute('target');
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || target === '_blank') return;
            if (formDirty && !isSubmitting) {
                e.preventDefault();
                pendingNavigation = href;
                const modalEl = document.getElementById('leaveDraftModal');
                if (modalEl && window.bootstrap) {
                    new bootstrap.Modal(modalEl).show();
                }
            }
        });

        window.addEventListener('beforeunload', function (e) {
            if (formDirty && !isSubmitting) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        document.getElementById('leaveSaveDraft')?.addEventListener('click', function () {
            isSubmitting = true;
            document.getElementById('saveDraftButton')?.click();
        });

        document.getElementById('leaveDiscard')?.addEventListener('click', function () {
            formDirty = false;
            const modalEl = document.getElementById('leaveDraftModal');
            const inst = window.bootstrap && bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
            if (pendingNavigation) window.location.href = pendingNavigation;
        });
    });
})();
</script>
@endpush
