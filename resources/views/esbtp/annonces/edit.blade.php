@extends('layouts.app')

@section('title', 'Modifier l\'annonce — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

{{-- Shared CSS partial — namespace ac-* (~600 lignes) extrait pour DRY avec create.blade.php --}}
@include('esbtp.annonces._form-styles')

{{-- Styles spécifiques edit : preview pièce existante, status pills, boutons danger/success, notice warning, modal danger --}}
<style>
/* ----- Existing attachment preview row (above the dropzone) ----- */
.ac-existing-file {
    display: flex; align-items: center; gap: .75rem;
    padding: .65rem .85rem;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    background: #eff6ff;
    margin-bottom: .5rem;
}
.ac-existing-file-icon {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: #fff;
    color: #0453cb;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    border: 1px solid #bfdbfe;
}
.ac-existing-file-meta { flex: 1; min-width: 0; }
.ac-existing-file-name {
    font-size: .82rem; font-weight: 600; color: #0f172a;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ac-existing-file-sub { font-size: .72rem; color: #1e40af; }
.ac-existing-file-actions { display: inline-flex; gap: .35rem; flex-shrink: 0; }

/* ----- Notice warning ----- */
.ac-notice--warn {
    background: linear-gradient(180deg, #fffbeb, #fef3c7);
    border-color: #fcd34d;
    color: #92400e;
}
.ac-notice--warn i { color: #d97706; }

/* ----- Status pill (en haut du formulaire) ----- */
.ac-status-row {
    display: flex; align-items: center; gap: .5rem;
    flex-wrap: wrap;
    margin-bottom: .5rem;
}
.ac-status-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .3rem .7rem;
    border-radius: 999px;
    font-size: .75rem; font-weight: 600;
    border: 1px solid transparent;
}
.ac-status-pill--draft {
    background: #f1f5f9;
    color: #475569;
    border-color: #e2e8f0;
}
.ac-status-pill--published {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.ac-status-pill--expired,
.ac-status-pill--urgent {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.ac-status-pill--important {
    background: #fffbeb;
    color: #92400e;
    border-color: #fcd34d;
}

/* ----- Boutons danger / success spécifiques edit ----- */
.ac-btn-danger {
    background: #fff;
    border-color: #fecaca;
    color: #b91c1c;
}
.ac-btn-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #991b1b;
}
.ac-btn-success {
    background: linear-gradient(135deg, #047857, #10b981);
    color: #fff;
    box-shadow: 0 6px 18px rgba(16,185,129,.25);
}
.ac-btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(16,185,129,.32);
    color: #fff;
}

/* ----- Modal danger (delete) ----- */
.ac-modal--danger .modal-header {
    background: linear-gradient(135deg, #7f1d1d, #b91c1c 60%, #dc2626);
}
</style>
@endsection

@php
    // Helpers d'état pour la vue
    $isPublished = (bool) $annonce->is_published;
    $isExpired   = $annonce->isExpired();
    $oldType     = old('type', $annonce->type ?? 'general');
    $oldClasses  = old('classes', $annonce->classes->pluck('id')->map(fn ($id) => (string) $id)->all());
    $oldEtuds    = old('etudiants', $annonce->etudiants->pluck('id')->map(fn ($id) => (string) $id)->all());
    $existingFile = $annonce->piece_jointe ?? null;
    $existingFileName = $existingFile ? basename($existingFile) : null;
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- Header standard (rule premium-redesign : pages edit utilisent dashboard-header) --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-pen-to-square me-2"></i>Modifier l'annonce</h1>
                <p class="header-subtitle">Mettez à jour le contenu, le ciblage ou les paramètres de cette annonce.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-acasi secondary">
                    <i class="fas fa-eye"></i>Voir l'annonce
                </a>
                <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        {{-- Bandeau statut courant --}}
        <div class="ac-status-row">
            @if($isExpired)
                <span class="ac-status-pill ac-status-pill--expired">
                    <i class="fas fa-hourglass-end"></i>Annonce expirée
                </span>
            @elseif($isPublished)
                <span class="ac-status-pill ac-status-pill--published">
                    <i class="fas fa-check-circle"></i>Publiée
                </span>
            @else
                <span class="ac-status-pill ac-status-pill--draft">
                    <i class="fas fa-file-lines"></i>Brouillon
                </span>
            @endif

            @if((int) $annonce->priorite === 2)
                <span class="ac-status-pill ac-status-pill--urgent">
                    <i class="fas fa-bolt"></i>Priorité urgente
                </span>
            @elseif((int) $annonce->priorite === 1)
                <span class="ac-status-pill ac-status-pill--important">
                    <i class="fas fa-thumbtack"></i>Importante
                </span>
            @endif

            @if($annonce->date_publication)
                <span class="ac-status-pill ac-status-pill--draft" style="background:#fff;border-color:#e2e8f0;">
                    <i class="fas fa-calendar-day"></i>
                    Créée le {{ $annonce->created_at?->format('d/m/Y') }}
                </span>
            @endif
        </div>

        @if($isExpired)
            <div class="ac-notice ac-notice--warn" style="margin-bottom:1rem;">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="ac-notice-text">
                    <strong>Cette annonce est expirée.</strong>
                    Elle n'est plus visible par les étudiants. Vous pouvez la consulter ou la supprimer, mais plus la modifier.
                </div>
            </div>
        @endif

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

        @if(session('error'))
            <div class="alert-modern error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <form action="{{ route('esbtp.annonces.update', $annonce) }}" method="POST"
              enctype="multipart/form-data" id="annonceForm">
            @csrf
            @method('PUT')

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
                                       value="{{ old('titre', $annonce->titre) }}"
                                       placeholder="Ex : Conseil pédagogique du 15 mai, Rentrée 2026..."
                                       maxlength="255" required>
                                <div class="ac-counter" data-counter-for="titre" data-max="255">
                                    Soyez clair et concis &middot; <strong><span data-counter-current>0</span></strong>/255
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
                                          placeholder="Rédigez votre annonce. Soyez précis sur la date, le lieu et le public concerné.">{{ old('contenu', $annonce->contenu) }}</textarea>
                                <div class="ac-counter" data-counter-for="contenu">
                                    <span><strong><span data-counter-current>0</span></strong> caractères</span>
                                    <span class="ac-help">Markdown léger non interprété &mdash; texte brut</span>
                                </div>
                                @error('contenu')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label class="ac-label">Pièce jointe (optionnelle)</label>

                                @if($existingFile)
                                    <div class="ac-existing-file" id="ac-existing-file">
                                        <div class="ac-existing-file-icon"><i class="fas fa-paperclip"></i></div>
                                        <div class="ac-existing-file-meta">
                                            <div class="ac-existing-file-name" title="{{ $existingFileName }}">{{ $existingFileName }}</div>
                                            <div class="ac-existing-file-sub">Fichier actuellement attaché à l'annonce</div>
                                        </div>
                                        <div class="ac-existing-file-actions">
                                            <a href="{{ \Storage::disk('public')->url($existingFile) }}"
                                               target="_blank" rel="noopener"
                                               class="ac-btn ac-btn-secondary"
                                               style="padding:.4rem .65rem; font-size:.78rem;">
                                                <i class="fas fa-external-link-alt"></i>Ouvrir
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                <label class="ac-file" id="ac-file-zone" for="piece_jointe">
                                    <input type="file" id="piece_jointe" name="piece_jointe"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    <div class="ac-file-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="ac-file-label" id="ac-file-label">
                                        @if($existingFile)
                                            Cliquez pour remplacer le fichier
                                        @else
                                            Cliquez ou déposez un fichier
                                        @endif
                                    </div>
                                    <div class="ac-file-hint">PDF &middot; Word &middot; Excel &middot; Image &mdash; 5 MB max</div>
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
                                               {{ $oldType === 'general' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-globe"></i></span>
                                            <span class="ac-audience-opt-name">Tous les étudiants</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Diffusion générale à l'ensemble de l'école.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="classe" required
                                               {{ $oldType === 'classe' ? 'checked' : '' }}>
                                        <span class="ac-audience-opt-head">
                                            <span class="ac-audience-opt-icon"><i class="fas fa-chalkboard"></i></span>
                                            <span class="ac-audience-opt-name">Classes ciblées</span>
                                        </span>
                                        <span class="ac-audience-opt-desc">Une ou plusieurs classes spécifiques.</span>
                                        <span class="ac-audience-opt-tick"><i class="fas fa-check"></i></span>
                                    </label>
                                    <label class="ac-audience-opt">
                                        <input type="radio" name="type" value="etudiant" required
                                               {{ $oldType === 'etudiant' ? 'checked' : '' }}>
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
                                <div class="ac-card-sub">Mettre à jour ou publier</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            @if($isPublished)
                                <div class="ac-notice">
                                    <i class="fas fa-check-circle"></i>
                                    <div class="ac-notice-text">
                                        <strong>Annonce déjà publiée.</strong>
                                        Vos modifications seront visibles immédiatement par les étudiants ciblés.
                                    </div>
                                </div>
                            @else
                                <div class="ac-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <div class="ac-notice-text">
                                        <strong>Annonce en brouillon.</strong>
                                        Elle reste invisible aux étudiants tant que vous ne cliquez pas
                                        sur &laquo;&nbsp;Publier maintenant&nbsp;&raquo;.
                                    </div>
                                </div>
                            @endif

                            <div class="ac-actions">
                                <button type="submit" name="action" value="update" class="ac-btn ac-btn-primary">
                                    <i class="fas fa-save"></i>
                                    @if($isPublished)
                                        Enregistrer les modifications
                                    @else
                                        Mettre à jour le brouillon
                                    @endif
                                </button>

                                @if(!$isPublished)
                                    <button type="submit" name="action" value="publish" class="ac-btn ac-btn-success">
                                        <i class="fas fa-paper-plane"></i>Publier maintenant
                                    </button>
                                @endif

                                <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="ac-btn ac-btn-ghost">
                                    <i class="fas fa-times"></i>Annuler les modifications
                                </a>
                            </div>

                            <div class="ac-actions-help">
                                <i class="fas fa-clock me-1"></i>
                                Les annonces publiées peuvent encore être modifiées dans les 15 minutes qui suivent leur publication.
                            </div>

                        </div>
                    </div>

                    {{-- ===== Sidebar — Publication ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon"><i class="fas fa-cog"></i></span>
                            <div>
                                <div class="ac-card-title">Paramètres de publication</div>
                                <div class="ac-card-sub">Date d'expiration &amp; priorité</div>
                            </div>
                        </div>
                        <div class="ac-card-body">

                            <div class="ac-field">
                                <label for="date_expiration" class="ac-label">
                                    Date d'expiration <span class="ac-req">*</span>
                                </label>
                                <input type="datetime-local" id="date_expiration" name="date_expiration"
                                       class="ac-input @error('date_expiration') is-invalid @enderror"
                                       value="{{ old('date_expiration', $annonce->date_expiration?->format('Y-m-d\TH:i')) }}"
                                       required>
                                <div class="ac-help">L'annonce sera retirée des fils étudiants après cette date.</div>
                                @error('date_expiration')<div class="ac-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ac-field">
                                <label for="priorite" class="ac-label">Niveau d'urgence</label>
                                <x-au-select
                                    name="priorite"
                                    :value="(string) old('priorite', $annonce->priorite ?? '0')"
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

                    {{-- ===== Sidebar — Zone de danger ===== --}}
                    <div class="ac-card">
                        <div class="ac-card-head">
                            <span class="ac-card-icon" style="background:linear-gradient(135deg,#b91c1c,#dc2626);box-shadow:0 4px 12px rgba(220,38,38,.25);">
                                <i class="fas fa-triangle-exclamation"></i>
                            </span>
                            <div>
                                <div class="ac-card-title">Zone sensible</div>
                                <div class="ac-card-sub">Suppression définitive</div>
                            </div>
                        </div>
                        <div class="ac-card-body">
                            <p style="margin:0; font-size:.78rem; color:#475569; line-height:1.5;">
                                Supprimer cette annonce retire également tous les liens avec les destinataires
                                et les statuts de lecture. Cette action est irréversible.
                            </p>
                            <button type="button" class="ac-btn ac-btn-danger" id="ac-open-delete">
                                <i class="fas fa-trash"></i>Supprimer cette annonce
                            </button>
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
                                        @php
                                            $isSelected = in_array((string) $classe->id, array_map('strval', (array) $oldClasses), true);
                                        @endphp
                                        <option value="{{ $classe->id }}"
                                                data-filiere="{{ $classe->filiere_id }}"
                                                data-niveau="{{ $classe->niveau_etude_id }}"
                                                data-current-count="{{ $classe->current_inscriptions_count ?? 0 }}"
                                                {{ $isSelected ? 'selected' : '' }}>
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
                                        @php
                                            $isSelected = in_array((string) $etudiant->id, array_map('strval', (array) $oldEtuds), true);
                                            $currentInscriptionId = optional($etudiant->inscriptions->first())->classe_id;
                                        @endphp
                                        <option value="{{ $etudiant->id }}"
                                                data-classe="{{ $currentInscriptionId }}"
                                                data-current-year="1"
                                                {{ $isSelected ? 'selected' : '' }}>
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

        </form>

        {{-- Form delete (en-dehors du form principal pour éviter les nested forms) --}}
        <form action="{{ route('esbtp.annonces.destroy', $annonce) }}" method="POST" id="deleteAnnonceForm" style="display:none;">
            @csrf
            @method('DELETE')
        </form>

        {{-- =================== MODAL DELETE =================== --}}
        <div class="modal fade ac-modal ac-modal--danger" id="deleteAnnonceModal" tabindex="-1"
             aria-labelledby="deleteAnnonceModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="ac-modal-titlewrap">
                            <span class="ac-modal-title" id="deleteAnnonceModalLabel">
                                <i class="fas fa-triangle-exclamation"></i> Supprimer cette annonce ?
                            </span>
                            <span class="ac-modal-sub">Action irréversible — toutes les relations seront détachées.</span>
                        </div>
                        <button type="button" class="ac-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0 0 .75rem 0; font-size:.88rem; color:#1e293b; line-height:1.5;">
                            Vous êtes sur le point de supprimer définitivement&nbsp;:
                        </p>
                        <div style="padding:.75rem 1rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:1rem;">
                            <div style="font-size:.72rem; color:#64748b; text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Annonce</div>
                            <div style="font-size:.95rem; font-weight:600; color:#0f172a; margin-top:.2rem;">{{ $annonce->titre }}</div>
                        </div>
                        <p style="margin:0; font-size:.8rem; color:#b91c1c; line-height:1.45;">
                            <i class="fas fa-info-circle me-1"></i>
                            Les étudiants ne verront plus cette annonce. Les statistiques de lecture seront perdues.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="ac-btn ac-btn-ghost" data-bs-dismiss="modal">
                            Annuler
                        </button>
                        <button type="button" class="ac-btn ac-btn-danger" id="ac-confirm-delete"
                                style="background:linear-gradient(135deg,#b91c1c,#dc2626);color:#fff;border-color:transparent;box-shadow:0 6px 18px rgba(220,38,38,.25);">
                            <i class="fas fa-trash"></i>Supprimer définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
{{-- Shared JS partial — Choices.js init, snapshots, filters, bulk, validation, etc. --}}
@include('esbtp.annonces._form-scripts', ['debugTag' => 'annonces:edit'])

{{-- Comportement spécifique edit : modal delete premium (pas window.confirm). --}}
<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const deleteBtn = document.getElementById('ac-open-delete');
        const deleteModalEl = document.getElementById('deleteAnnonceModal');
        const confirmDeleteBtn = document.getElementById('ac-confirm-delete');
        const deleteForm = document.getElementById('deleteAnnonceForm');

        deleteBtn?.addEventListener('click', () => {
            if (deleteModalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(deleteModalEl).show();
            }
        });
        confirmDeleteBtn?.addEventListener('click', () => {
            if (deleteForm) deleteForm.submit();
        });
    });
})();
</script>
@endpush
