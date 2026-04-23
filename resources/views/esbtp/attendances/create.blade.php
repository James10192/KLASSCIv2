@extends('layouts.app')

@section('title', 'Marquer les présences')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ═══════════ Premium Hero ═══════════ --}}
        <div class="at-hero">
            <div class="at-hero-top">
                <div class="at-hero-left">
                    <div class="at-hero-icon"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <h1>Marquer les présences</h1>
                        <p>Enregistrement des présences étudiantes par séance ou en saisie manuelle</p>
                    </div>
                </div>
                <div class="at-hero-actions">
                    <button type="button" class="at-btn at-btn--glass" data-bs-toggle="modal" data-bs-target="#atHelpModal">
                        <i class="fas fa-circle-question"></i>Aide
                    </button>
                    <a href="{{ route('esbtp.attendances.index') }}" class="at-btn at-btn--white">
                        <i class="fas fa-arrow-left"></i>Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($messageErreur))
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ $messageErreur }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="at-card">
            <div class="at-card-body">

                    {{-- Debug visible uniquement en ?debug=1 --}}
                    @if(config('app.debug') && request()->has('debug') && isset($debug))
                        <div class="alert alert-secondary mb-3">
                            <h6 class="mb-2"><i class="fas fa-bug me-2"></i>Debug</h6>
                            <pre class="mb-0" style="font-size:.75rem">{{ json_encode($debug, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif

                    {{-- Modal d'aide (opt-in via bouton Aide du hero) --}}
                    <div class="modal fade" id="atHelpModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Comment marquer les présences</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <ol class="mb-0" style="padding-left: 1.2rem">
                                        <li class="mb-2"><strong>Choisissez une classe</strong> dans la liste déroulante.</li>
                                        <li class="mb-2"><strong>Sélectionnez une séance</strong> parmi celles disponibles pour cette classe.</li>
                                        <li class="mb-2">La <strong>date</strong> se calcule automatiquement à partir de la séance.</li>
                                        <li class="mb-2">Marquez chaque étudiant puis <strong>Enregistrez</strong>.</li>
                                    </ol>
                                    <hr class="my-3">
                                    <p class="mb-0 text-muted" style="font-size:.85rem">
                                        <i class="fas fa-lightbulb me-1 text-warning"></i>
                                        Vous pouvez aussi basculer sur l'onglet <strong>Saisie manuelle (heures)</strong> pour entrer directement le total d'heures de présence/absence par matière pour un semestre.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sélection de la classe et de la séance -->
                    <div class="mb-4">
                        @php
                            $classesForJs = ($classes ?? collect())->map(function ($c) {
                                return [
                                    'id' => $c->id,
                                    'name' => $c->name,
                                    'filiere_id' => $c->filiere_id,
                                    'niveau_etude_id' => $c->niveau_etude_id,
                                ];
                            })->values();
                        @endphp
                        <form id="selectionForm" method="GET" action="{{ route('esbtp.attendances.create') }}" class="row g-3">
                            <div class="col-md-4"
                                 data-classes='@json($classesForJs)'
                                 x-data="classeFilter({
                                     classes: JSON.parse($el.dataset.classes || '[]'),
                                     initialClasseId: {{ (int) request('classe_id', 0) }}
                                 })">
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <select x-model="filiereId" class="form-control form-control-sm" aria-label="Filtrer par filière">
                                            <option value="">Toutes les filières</option>
                                            @foreach($filieres ?? [] as $f)
                                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select x-model="niveauId" class="form-control form-control-sm" aria-label="Filtrer par niveau d'études">
                                            <option value="">Tous les niveaux</option>
                                            @foreach($niveauxEtudes ?? [] as $n)
                                                <option value="{{ $n->id }}">{{ $n->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <input type="text"
                                       x-model.debounce.150ms="search"
                                       class="form-control form-control-sm mb-2"
                                       placeholder="Rechercher une classe..."
                                       aria-label="Rechercher une classe">

                                <label for="classe_id" class="form-label">Classe</label>
                                <select name="classe_id" id="classe_id" class="form-control" required x-model="selectedId">
                                    <option value="">Sélectionner une classe</option>
                                    <template x-for="c in filtered" :key="c.id">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </select>
                                <small class="form-text text-muted d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-info-circle"></i>
                                        <span x-text="filtered.length"></span> / <span x-text="classes.length"></span> classe(s) affichée(s)
                                    </span>
                                    <a href="#" x-show="filiereId || niveauId || search"
                                       @click.prevent="reset()"
                                       class="text-decoration-none">
                                        <i class="fas fa-rotate-left"></i> Réinitialiser
                                    </a>
                                </small>
                            </div>

                            @if(isset($classeSelectionnee) && $classeSelectionnee)
                                <div class="col-md-4" id="seance-select-container">
                                    <label for="seance_id" class="form-label">Séance de cours</label>
                                    <select name="seance_id" id="seance_id" class="form-control" required>
                                        <option value="">Sélectionner une séance</option>
                                        @foreach($seances as $seance)
                                            <option value="{{ $seance->id }}" {{ request('seance_id') == $seance->id ? 'selected' : '' }}
                                                data-date="{{ $seance->date_calculee }}"
                                                data-jour="{{ $seance->jour_nom }}">
                                                {{ $seance->matiere->name ?? 'Matière inconnue' }} - {{ $seance->heure_debut->format('H:i') }} à {{ $seance->heure_fin->format('H:i') }} ({{ $seance->jour_nom }})
                                                @if($seance->date_calculee)
                                                    - {{ \Carbon\Carbon::parse($seance->date_calculee)->format('d/m/Y') }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($seances->isEmpty())
                                        <small class="form-text text-danger">
                                            <i class="fas fa-exclamation-circle"></i> Aucune séance disponible pour cette classe. Vérifiez que l'emploi du temps est actif.
                                        </small>
                                    @else
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Sélectionnez une séance pour voir les étudiants.
                                        </small>
                                    @endif
                                </div>
                            @endif

                            @if(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee)
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" name="date" id="date" class="form-control" required value="{{ $dateSeance ?? request('date', date('Y-m-d')) }}" {{ $dateSeance ? 'readonly' : '' }}>
                                    @if($dateSeance)
                                        <small class="form-text text-info">
                                            <i class="fas fa-info-circle"></i> Cette date est automatiquement calculée en fonction du jour de la séance et de la période de l'emploi du temps.
                                        </small>
                                    @endif
                                </div>
                            @endif
                        </form>
                    </div>

                    <div class="att-tabs-wrap"
                         data-initial-matieres='@json($matieresClasse ?? collect())'
                         x-data="manualHoursTab({
                             initialClasseId: {{ (int) request('classe_id', 0) }},
                             anneeId: {{ (int) (optional($anneeUniversitaireCourante ?? null)->id ?? 0) }},
                             initialMatieres: JSON.parse($el.dataset.initialMatieres || '[]'),
                             globalEnabled: {{ (bool) \App\Helpers\SettingsHelper::get('attendance_manual_hours_global_enabled', false) ? 'true' : 'false' }}
                         })"
                         x-show="hasClasse"
                         x-cloak
                         @attendance:classe-changed.window="onClasseChanged($event.detail)">
                        <nav class="att-tabs" role="tablist">
                            <button type="button"
                                    class="att-tab"
                                    :class="{ 'att-tab--active': activeTab === 'seances' }"
                                    @click="activeTab = 'seances'"
                                    role="tab">
                                <i class="fas fa-calendar-check"></i>Saisie par séance
                            </button>
                            <button type="button"
                                    class="att-tab"
                                    :class="{ 'att-tab--active': activeTab === 'manual' }"
                                    @click="activeTab = 'manual'"
                                    role="tab">
                                <i class="fas fa-list-check"></i>Saisie manuelle (heures)
                            </button>
                        </nav>

                        <div x-show="activeTab === 'seances'" role="tabpanel">

                    @php
                        $hasStudentsRows = request()->filled('seance_id') && isset($etudiants) && $etudiants->count() > 0;
                        $hideForm = !$hasStudentsRows;
                    @endphp

                    <!-- Formulaire de saisie des présences (toujours présent pour AJAX) -->
                    <form action="{{ route('esbtp.attendances.store') }}" method="POST" id="attendanceForm" style="{{ $hideForm ? 'display:none;' : '' }}">
                        @csrf
                        <input type="hidden" name="seance_cours_id" id="hidden_seance_id" value="{{ request('seance_id') }}">
                        <input type="hidden" name="date" id="hidden_date" value="{{ $dateSeance ?? request('date') }}">

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Statut</th>
                                        <th>Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody id="students-table-body">
                                    @if($hasStudentsRows)
                                        @foreach($etudiants as $etudiant)
                                            @php
                                                $attendance = $existingAttendances[$etudiant->id] ?? null;
                                                $statut = $attendance ? $attendance->statut : 'present';
                                                $statutOriginal = $statut;
                                                if ($statut === 'late') { $statut = 'retard'; }
                                                $commentaire = $attendance ? $attendance->commentaire : '';
                                            @endphp
                                            @php
                                                $initials = strtoupper(substr($etudiant->nom ?? '', 0, 1) . substr($etudiant->prenoms ?? '', 0, 1));
                                                $avatarHue = hexdec(substr(md5($etudiant->nom_complet ?? (string) $etudiant->id), 0, 4)) % 360;
                                            @endphp
                                            <tr data-etudiant-id="{{ $etudiant->id }}" data-debug-statut="{{ $statut }}" data-debug-statut-original="{{ $statutOriginal }}">
                                                <td>
                                                    <div class="at-etu">
                                                        <span class="at-etu-avatar" @if($etudiant->photo_url) style="background:transparent;padding:0;overflow:hidden;" @else style="background: hsl({{ $avatarHue }}, 55%, 92%); color: hsl({{ $avatarHue }}, 50%, 35%);" @endif>
                                                            @if($etudiant->photo_url)
                                                                <img src="{{ $etudiant->photo_url }}" alt="{{ $etudiant->nom_complet }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null;this.parentElement.style.background='hsl({{ $avatarHue }}, 55%, 92%)';this.parentElement.style.color='hsl({{ $avatarHue }}, 50%, 35%)';this.outerHTML='{{ $initials ?: '?' }}';">
                                                            @else
                                                                {{ $initials ?: '?' }}
                                                            @endif
                                                        </span>
                                                        <span class="at-etu-name">{{ trim(mb_strtoupper($etudiant->nom ?? '', 'UTF-8').' '.($etudiant->prenoms ?? '')) }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="present_{{ $etudiant->id }}" value="present" {{ $statut === 'present' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-success" for="present_{{ $etudiant->id }}">Présent</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="absent_{{ $etudiant->id }}" value="absent" {{ $statut === 'absent' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-danger" for="absent_{{ $etudiant->id }}">Absent</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="retard_{{ $etudiant->id }}" value="retard" {{ $statut === 'retard' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-warning" for="retard_{{ $etudiant->id }}">Retard</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="excuse_{{ $etudiant->id }}" value="excuse" {{ $statut === 'excuse' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-info" for="excuse_{{ $etudiant->id }}">Excusé</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" name="commentaires[{{ $etudiant->id }}]" class="form-control" placeholder="Commentaire (optionnel)" value="{{ $commentaire }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 submit-buttons" style="{{ $hideForm ? 'display:none;' : '' }}">
                            <button type="submit" class="btn btn-gradient-primary">
                                <i class="mdi mdi-content-save"></i> Enregistrer les présences
                            </button>
                            <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">Annuler</a>
                        </div>
                    </form>

                    <div class="mt-4 quick-action-buttons" style="{{ $hideForm ? 'display:none;' : '' }}">
                        <button type="button" class="btn btn-success btn-sm" onclick="marquerTous('present')">
                            <i class="mdi mdi-check-all"></i> Tous présents
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="marquerTous('absent')">
                            <i class="mdi mdi-close-all"></i> Tous absents
                        </button>
                    </div>

                    {{-- placeholder : les alertes sont rendues en dehors de .att-tabs-wrap ci-dessous --}}

                            </div>

                            <div x-show="activeTab === 'manual'" role="tabpanel" class="att-manual-wrap">
                                <template x-if="matieres.length === 0">
                                    <div class="amh-alert amh-alert--warning">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        <div>
                                            <strong>Aucune matière n'est attachée à cette classe.</strong>
                                            Vous devez d'abord associer des matières à la classe dans
                                            <a href="{{ route('esbtp.classes.index') }}" style="color:inherit;text-decoration:underline;">Gestion des classes</a>
                                            avant de pouvoir saisir les heures manuelles.
                                        </div>
                                    </div>
                                </template>

                                <template x-if="matieres.length > 0">
                                    <div>
                                        <div class="amh-selector-bar">
                                            <div class="amh-selector-field">
                                                <label for="amh-matiere-select">Matière</label>
                                                <select id="amh-matiere-select" x-model="matiereId" class="form-control" x-ref="matiereSelect"
                                                        :disabled="modeGlobal">
                                                    <option value="">— Sélectionner une matière —</option>
                                                    @if(isset($matieresClasse))
                                                        @foreach($matieresClasse as $m)
                                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="amh-selector-field">
                                                <label for="amh-periode-select">Période</label>
                                                <select id="amh-periode-select" x-model="periode" class="form-control">
                                                    <option value="semestre1">Semestre 1</option>
                                                    <option value="semestre2">Semestre 2</option>
                                                    <option value="annuel">Annuel</option>
                                                </select>
                                            </div>
                                            <button type="button"
                                                    class="amh-btn amh-btn--primary"
                                                    @click="loadGrid()"
                                                    :disabled="(!modeGlobal && !matiereId) || loading">
                                                <i class="fas fa-rotate" :class="{ 'fa-spin': loading }"></i>
                                                <span x-text="loading ? 'Chargement...' : 'Charger la grille'"></span>
                                            </button>
                                        </div>
                                        <label class="amh-global-toggle" x-show="globalEnabled" x-cloak>
                                            <input type="checkbox" x-model="modeGlobal" @change="onModeGlobalChanged()">
                                            <span class="amh-global-toggle__slider"></span>
                                            <span class="amh-global-toggle__text">
                                                <strong>Mode global (sans matière)</strong>
                                                <small>Saisir les heures d'absence pour toute la période, sans associer à une matière spécifique.</small>
                                            </span>
                                        </label>
                                    </div>
                                </template>

                                <div id="amh-container" x-ref="container" x-show="matieres.length > 0">
                                    <div class="amh-empty" x-show="!html && !loading">
                                        <i class="fas fa-hand-pointer"></i>
                                        <div>Sélectionnez une matière et une période, puis cliquez sur <strong>Charger la grille</strong>.</div>
                                    </div>
                                    <div class="amh-loading" x-show="loading">
                                        <i class="fas fa-spinner fa-spin"></i>Chargement...
                                    </div>
                                    <div x-html="html" x-show="html && !loading"></div>
                                </div>
                            </div>
                    </div>

                    {{-- Alertes contextuelles (Alpine-réactives) — en dehors des onglets pour être visibles même sans classe --}}
                    <div x-data="{
                            hasClasse: {{ (isset($classeSelectionnee) && $classeSelectionnee) ? 'true' : 'false' }},
                            hasSeance: {{ request()->filled('seance_id') ? 'true' : 'false' }},
                            hasStudents: {{ (isset($etudiants) && $etudiants->count() > 0) ? 'true' : 'false' }}
                         }"
                         @attendance:classe-changed.window="hasClasse = !!$event.detail.classeId; hasSeance = false; hasStudents = false;"
                         @attendance:seance-loaded.window="hasSeance = true; hasStudents = ($event.detail.nbEtudiants ?? 0) > 0;">
                        <div class="alert alert-warning" x-show="hasClasse && hasSeance && !hasStudents" x-cloak>
                            <i class="fas fa-triangle-exclamation me-2"></i>Aucun étudiant n'est inscrit dans cette classe pour l'année en cours.
                        </div>
                        <div class="alert alert-info" x-show="hasClasse && !hasSeance" x-cloak>
                            <i class="fas fa-info-circle me-2"></i>Veuillez sélectionner une séance pour voir les étudiants et marquer les présences.
                        </div>
                        <div class="alert alert-info" x-show="!hasClasse" x-cloak>
                            <i class="fas fa-hand-pointer me-2"></i>Veuillez d'abord sélectionner une classe pour commencer.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
    /* Animation "travelling light" pour le refresh des lignes */
    .student-row-highlight {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        background: linear-gradient(90deg,
            rgba(40, 167, 69, 0) 0%,
            rgba(40, 167, 69, 0.75) 50%,
            rgba(40, 167, 69, 0) 100%);
        transform: translateX(-65%) skewX(-12deg);
        opacity: 0;
    }

    .student-row-highlight.animate {
        animation: student-row-highlight-move 3.2s ease-out forwards;
    }

    @keyframes student-row-highlight-move {
        0% { opacity: 0; transform: translateX(-65%) skewX(-12deg); }
        18% { opacity: 0.92; }
        55% { opacity: 0.7; }
        100% { opacity: 0; transform: translateX(115%) skewX(-12deg); }
    }

    tbody tr {
        position: relative;
    }

    /* Loading spinner */
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-left: 8px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* ═══════════════════════════════════════════════════════════
       Onglets attendance (seances | manuel)
       ═══════════════════════════════════════════════════════════ */
    [x-cloak] { display: none !important; }

    .att-tabs-wrap {
        margin-top: .25rem;
    }

    .att-tabs {
        display: inline-flex;
        gap: .25rem;
        padding: .3rem;
        background: #f1f5f9;
        border-radius: 12px;
        margin-bottom: 1.25rem;
    }

    .att-tab {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: transparent;
        border: 0;
        color: #64748b;
        font-weight: 600;
        font-size: .82rem;
        padding: .55rem 1rem;
        border-radius: 9px;
        cursor: pointer;
        transition: all .2s ease;
    }
    .att-tab:hover { color: #0453cb; }
    .att-tab--active {
        background: #fff;
        color: #0453cb;
        box-shadow: 0 1px 2px rgba(15,23,42,.06);
    }

    /* ═══════════════════════════════════════════════════════════
       Panneau Saisie manuelle (namespace amh-*)
       ═══════════════════════════════════════════════════════════ */
    .amh-selector-bar {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
        padding: 1rem 1.25rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 1.25rem;
    }
    .amh-selector-field {
        flex: 1 1 180px;
        min-width: 180px;
    }
    .amh-selector-field label {
        display: block;
        font-size: .72rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .35rem;
    }

    .amh-global-toggle {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .85rem 1.1rem;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 10px;
        margin-bottom: 1.25rem;
        cursor: pointer;
        transition: all .2s ease;
    }
    .amh-global-toggle:hover { background: #e0f2fe; }
    .amh-global-toggle input[type="checkbox"] {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        margin-top: 2px;
        accent-color: #0453cb;
        cursor: pointer;
    }
    .amh-global-toggle__slider { display: none; }
    .amh-global-toggle__text {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: .15rem;
        line-height: 1.35;
    }
    .amh-global-toggle__text strong {
        font-size: .88rem;
        color: #0c4a6e;
        font-weight: 600;
    }
    .amh-global-toggle__text small {
        font-size: .78rem;
        color: #0369a1;
    }

    .amh-alert--info {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }
    .amh-alert--info i { color: #2563eb; }

    .amh-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .6rem 1.1rem;
        border-radius: 10px;
        border: 1px solid transparent;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
    }
    .amh-btn--primary {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
    }
    .amh-btn--primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(4,83,203,.25);
    }
    .amh-btn--primary:disabled {
        opacity: .55;
        cursor: not-allowed;
    }
    .amh-btn--danger {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fecaca;
        padding: .4rem .55rem;
    }
    .amh-btn--danger:hover { background: #fca5a5; color: #7f1d1d; }

    .amh-empty, .amh-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .75rem;
        padding: 2.5rem 1.5rem;
        color: #64748b;
        text-align: center;
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
    }
    .amh-empty i, .amh-loading i { font-size: 1.8rem; color: #94a3b8; }

    .amh-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.25rem 1.25rem 1.5rem;
    }
    .amh-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 1rem;
    }
    .amh-header__eyebrow {
        font-size: .72rem;
        font-weight: 600;
        color: #0453cb;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .amh-header__title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: .2rem;
    }
    .amh-header__meta {
        font-size: .82rem;
        color: #64748b;
        margin-top: .4rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .35rem;
    }

    .amh-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .7rem;
        font-weight: 600;
        padding: .18rem .55rem;
        border-radius: 999px;
    }
    .amh-chip--green { background: #d1fae5; color: #065f46; }
    .amh-chip--blue  { background: #dbeafe; color: #1e40af; }

    /* État par ligne (badge dynamique piloté par data-state sur tr) */
    .amh-state-chip {
        display: none;
        align-items: center;
        gap: .3rem;
        font-size: .65rem;
        font-weight: 600;
        padding: .18rem .55rem;
        border-radius: 999px;
        margin-left: auto;
    }
    .amh-state-chip--saved    { background: #d1fae5; color: #065f46; }
    .amh-state-chip--modified { background: #fef3c7; color: #92400e; }
    .amh-state-chip--empty    { background: #f1f5f9; color: #64748b; }
    .amh-row[data-state="saved"]    .amh-state-chip--saved    { display: inline-flex; }
    .amh-row[data-state="modified"] .amh-state-chip--modified { display: inline-flex; }
    .amh-row[data-state="empty"]    .amh-state-chip--empty    { display: inline-flex; }

    /* Bordure gauche colorée selon état */
    .amh-row { border-left: 3px solid transparent; transition: border-color .15s ease, background .15s ease; }
    .amh-row[data-state="saved"]    { border-left-color: #10b981; }
    .amh-row[data-state="modified"] { border-left-color: #f59e0b; background: #fffbeb33; }
    .amh-row[data-state="empty"]    { border-left-color: transparent; }

    /* Actions par ligne */
    .amh-row-actions { display: inline-flex; gap: .35rem; }
    .amh-btn--ghost,
    .amh-btn--ghost-sm {
        background: transparent;
        border: 1px solid #e2e8f0;
        color: #475569;
        transition: all .15s ease;
    }
    .amh-btn--ghost:hover:not(:disabled),
    .amh-btn--ghost-sm:hover:not(:disabled) {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .amh-btn--ghost:disabled,
    .amh-btn--ghost-sm:disabled {
        opacity: .4;
        cursor: not-allowed;
    }
    .amh-btn--ghost-sm {
        padding: .35rem .5rem;
        font-size: .75rem;
    }

    .amh-header__actions { flex-shrink: 0; }

    .amh-alert {
        display: flex;
        gap: .75rem;
        padding: .85rem 1rem;
        border-radius: 10px;
        font-size: .85rem;
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    .amh-alert i { flex-shrink: 0; margin-top: .15rem; font-size: 1rem; }
    .amh-alert--warning { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }
    .amh-alert--muted   { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

    .amh-table-wrap {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    .amh-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .88rem;
    }
    .amh-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        text-align: left;
        padding: .7rem .9rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .amh-table tbody td {
        padding: .55rem .9rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .amh-table tbody tr:last-child td { border-bottom: 0; }
    .amh-table tbody tr:hover { background: #f8fafc; }
    .amh-col-name   { min-width: 200px; }
    .amh-col-hours  { width: 120px; }
    .amh-col-total  { width: 100px; text-align: right; white-space: nowrap; }
    .amh-col-note   { min-width: 180px; }
    .amh-col-actions{ width: 90px; text-align: right; }

    .amh-row-total {
        font-weight: 700;
        font-size: .9rem;
        color: #0f172a;
    }
    .amh-row-total-unit {
        font-size: .72rem;
        color: #94a3b8;
        margin-left: .15rem;
    }
    .amh-row[data-total-match="ok"] .amh-row-total {
        color: #065f46;
    }
    .amh-row[data-total-match="ok"] .amh-col-total::after {
        content: " ✓";
        color: #10b981;
        font-size: .75rem;
    }
    .amh-row[data-total-match="over"] .amh-row-total {
        color: #b91c1c;
    }
    .amh-row[data-total-match="over"] .amh-col-total::after {
        content: " ⚠";
        color: #ef4444;
        font-size: .75rem;
    }
    .amh-row[data-total-match="under"] .amh-row-total {
        color: #b45309;
    }
    .amh-row[data-total-match="under"] .amh-col-total::after {
        content: " ⚠";
        color: #f59e0b;
        font-size: .75rem;
    }

    .amh-chip--muted { background: #f1f5f9; color: #64748b; }

    .amh-etu { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .amh-etu__name { font-weight: 600; color: #0f172a; }

    .amh-input {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: .45rem .6rem;
        font-size: .85rem;
        transition: border .15s;
    }
    .amh-input:focus { border-color: #0453cb; outline: none; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }

    .amh-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    .amh-hint {
        color: #64748b;
        font-size: .78rem;
    }

    @media (max-width: 768px) {
        .amh-table thead { display: none; }
        .amh-table tbody tr {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: .5rem;
            padding: .5rem;
        }
        .amh-table tbody td {
            display: flex;
            justify-content: space-between;
            padding: .4rem .5rem;
            border-bottom: 0;
        }
        .amh-table tbody td:before {
            content: attr(data-label);
            color: #64748b;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    }

    /* ═══════════════════════════════════════════════════════════
       at-* : Premium redesign page Marquer les présences
       ═══════════════════════════════════════════════════════════ */
    .at-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 1.75rem 2rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 10px 30px rgba(4, 83, 203, .12);
    }
    .at-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .at-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .at-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255, 255, 255, .12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, .15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
        color: #fff;
    }
    .at-hero h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
        letter-spacing: -.01em;
    }
    .at-hero p {
        color: rgba(255, 255, 255, .75);
        font-size: .88rem;
        margin: .2rem 0 0;
    }
    .at-hero-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .at-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .55rem 1rem;
        border-radius: 10px;
        font-size: .82rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .18s ease;
        text-decoration: none;
    }
    .at-btn--glass {
        background: rgba(255, 255, 255, .15);
        color: #fff;
        border-color: rgba(255, 255, 255, .2);
    }
    .at-btn--glass:hover { background: rgba(255, 255, 255, .25); color: #fff; }
    .at-btn--white {
        background: #fff;
        color: #0453cb;
    }
    .at-btn--white:hover { background: #f8fafc; color: #033a8e; transform: translateY(-1px); }

    /* Card principale */
    .at-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .at-card-body { padding: 1.5rem; }

    /* Toolbar de sélection classe/séance/date */
    #selectionForm.row {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin: 0 0 1.25rem 0;
    }
    #selectionForm label.form-label {
        font-size: .72rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .4rem;
    }
    #selectionForm .form-control {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        padding: .55rem .75rem;
        font-size: .9rem;
    }
    #selectionForm .form-control:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .1);
    }
    #selectionForm .form-text {
        font-size: .72rem;
        margin-top: .35rem;
    }

    /* Table étudiants premium */
    #attendanceForm .table-responsive {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    #attendanceForm .table {
        margin: 0;
        font-size: .88rem;
    }
    #attendanceForm .table thead th {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475569;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        border: 0;
        border-bottom: 1px solid #e2e8f0;
        padding: .85rem 1rem;
    }
    #attendanceForm .table tbody tr {
        transition: background .15s ease;
    }
    #attendanceForm .table tbody tr:hover { background: #f8fafc; }
    #attendanceForm .table tbody td {
        padding: .75rem 1rem;
        border-color: #f1f5f9;
        vertical-align: middle;
    }
    #attendanceForm .table tbody td:first-child {
        font-weight: 600;
        color: #0f172a;
        position: relative;
    }
    #attendanceForm .table tbody td:first-child::before {
        content: attr(data-initial);
        display: none;
    }

    /* Avatar étudiant avec initiales */
    .at-etu {
        display: flex;
        align-items: center;
        gap: .65rem;
        flex-wrap: wrap;
    }
    .at-etu-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .02em;
        flex-shrink: 0;
    }
    .at-etu-name {
        color: #0f172a;
        font-weight: 600;
    }
    .at-etu-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .65rem;
        font-weight: 600;
        padding: .15rem .5rem;
        border-radius: 999px;
        margin-left: auto;
    }
    .at-etu-badge--edit {
        background: #fef3c7;
        color: #92400e;
    }

    /* Radio buttons premium (pill toggles) */
    #attendanceForm .form-check-inline {
        margin-right: .35rem;
        margin-bottom: .15rem;
    }
    #attendanceForm .form-check-input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    #attendanceForm .form-check-label {
        display: inline-flex;
        align-items: center;
        padding: .35rem .7rem;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #64748b !important;
        cursor: pointer;
        transition: all .15s ease;
        border: 1px solid transparent;
        user-select: none;
    }
    #attendanceForm .form-check-label:hover {
        background: #e2e8f0;
    }
    #attendanceForm .form-check-input[type="radio"]:checked + .form-check-label.text-success {
        background: #d1fae5;
        color: #065f46 !important;
        border-color: #10b981;
    }
    #attendanceForm .form-check-input[type="radio"]:checked + .form-check-label.text-danger {
        background: #fee2e2;
        color: #991b1b !important;
        border-color: #ef4444;
    }
    #attendanceForm .form-check-input[type="radio"]:checked + .form-check-label.text-warning {
        background: #fef3c7;
        color: #92400e !important;
        border-color: #f59e0b;
    }
    #attendanceForm .form-check-input[type="radio"]:checked + .form-check-label.text-info {
        background: #dbeafe;
        color: #1e40af !important;
        border-color: #3b82f6;
    }
    /* Focus ring accessibility */
    #attendanceForm .form-check-input[type="radio"]:focus + .form-check-label {
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .15);
    }

    /* Commentaire input */
    #attendanceForm input[name^="commentaires"] {
        border: 1px solid transparent;
        background: #f8fafc;
        border-radius: 8px;
        padding: .45rem .7rem;
        font-size: .85rem;
        width: 100%;
        transition: all .15s ease;
    }
    #attendanceForm input[name^="commentaires"]:focus {
        background: #fff;
        border-color: #cbd5e1;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .08);
    }

    /* Boutons d'action */
    #attendanceForm .submit-buttons, .quick-action-buttons {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: center;
    }
    #attendanceForm .btn-gradient-primary {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        border: 0;
        padding: .6rem 1.25rem;
        border-radius: 10px;
        font-size: .88rem;
        font-weight: 600;
        transition: all .2s ease;
    }
    #attendanceForm .btn-gradient-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(4, 83, 203, .25);
        color: #fff;
    }
    .quick-action-buttons .btn {
        border-radius: 9px;
        padding: .45rem .9rem;
        font-size: .8rem;
        font-weight: 600;
    }
    .quick-action-buttons .btn-success {
        background: #10b981;
        border-color: #10b981;
    }
    .quick-action-buttons .btn-success:hover { background: #059669; border-color: #059669; }
    .quick-action-buttons .btn-danger {
        background: #ef4444;
        border-color: #ef4444;
    }
    .quick-action-buttons .btn-danger:hover { background: #dc2626; border-color: #dc2626; }

    /* Alertes premium */
    .at-card .alert {
        border-radius: 10px;
        border-width: 1px;
        padding: .85rem 1rem;
        font-size: .85rem;
    }
    .at-card .alert-info {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }
    .at-card .alert-warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }

    /* Masque "Aucun étudiant" avant sélection */
    #attendanceForm[style*="display:none"] + .quick-action-buttons { display: none !important; }

    /* Mobile */
    @media (max-width: 768px) {
        .at-hero { padding: 1.25rem; }
        .at-hero h1 { font-size: 1.2rem; }
        .at-hero-top { flex-direction: column; align-items: flex-start; }
        .at-hero-actions { width: 100%; }
        #attendanceForm .table tbody td {
            padding: .55rem .6rem;
        }
        #attendanceForm .form-check-label {
            padding: .3rem .5rem;
            font-size: .72rem;
        }
    }
</style>

<script>
    // Fonction simple pour marquer tous les étudiants avec un statut spécifique
    function marquerTous(statut) {
        debugLog('Marquer tous comme ' + statut);
        var radios = document.querySelectorAll('input[type="radio"][value="' + statut + '"]');
        debugLog('Nombre de boutons radio trouvés: ' + radios.length);
        for (var i = 0; i < radios.length; i++) {
            radios[i].checked = true;
        }
    }

    // Animation "travelling light"
    function triggerStudentRowHighlight(row) {
        const highlight = document.createElement('div');
        highlight.className = 'student-row-highlight';
        row.appendChild(highlight);
        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });
        setTimeout(() => {
            highlight.remove();
        }, 3200);
    }

    // Charger les séances via AJAX quand la classe change
    function loadSeances(classeId) {
        debugLog('📡 [AJAX] Chargement séances pour classe:', classeId);

        const seanceSelect = document.getElementById('seance_id');
        const seanceContainer = document.getElementById('seance-select-container');

        // Afficher un loader sur le label classe
        const classeLabel = document.querySelector('label[for="classe_id"]');
        let spinner = null;
        if (classeLabel) {
            spinner = document.createElement('span');
            spinner.className = 'loading-spinner';
            classeLabel.appendChild(spinner);
        } else {
            debugWarn('⚠️ Label classe introuvable pour spinner');
        }

        const url = '{{ route("esbtp.attendances.load-seances") }}?classe_id=' + classeId;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (spinner) spinner.remove();

            if (data.success) {
                debugLog('✅ [AJAX] Séances reçues:', data.nbSeances);

                // Créer le conteneur de séance s'il n'existe pas
                if (!seanceContainer) {
                    const formRow = document.getElementById('selectionForm');
                    if (!formRow) {
                        debugError('❌ Formulaire introuvable #selectionForm');
                        alert('Erreur: impossible de trouver le formulaire de sélection');
                        return;
                    }
                    const newContainer = document.createElement('div');
                    newContainer.className = 'col-md-4';
                    newContainer.id = 'seance-select-container';
                    newContainer.innerHTML = `
                        <label for="seance_id" class="form-label">Séance de cours</label>
                        <select name="seance_id" id="seance_id" class="form-control" required>
                            ${data.options}
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Sélectionnez une séance pour voir les étudiants.
                        </small>
                    `;
                    formRow.appendChild(newContainer);
                } else {
                    // Remplacer les options du select existant
                    if (seanceSelect) {
                        seanceSelect.innerHTML = data.options;
                    }
                }

                // Cacher le formulaire d'attendances et le champ date jusqu'à sélection d'une séance
                const attendanceForm = document.getElementById('attendanceForm');
                if (attendanceForm) {
                    attendanceForm.style.display = 'none';
                }
                const tbody = document.getElementById('students-table-body');
                if (tbody) {
                    tbody.innerHTML = '';
                }

                // Supprimer le champ date s'il existe
                const dateInput = document.getElementById('date');
                if (dateInput) {
                    const dateContainer = dateInput.closest('.col-md-4');
                    if (dateContainer) {
                        dateContainer.remove();
                    }
                }

                // Mettre à jour l'URL
                const newUrl = '{{ route("esbtp.attendances.create") }}?classe_id=' + classeId;
                history.pushState({}, '', newUrl);

                // Informer Alpine : la classe a changé, les onglets peuvent apparaître avec les matières
                window.dispatchEvent(new CustomEvent('attendance:classe-changed', {
                    detail: {
                        classeId: parseInt(classeId, 10),
                        matieres: data.matieres || [],
                    }
                }));

                debugLog('✅ Séances chargées, attendez sélection d\'une séance');
            } else {
                debugError('❌ Erreur:', data.message);
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            if (spinner) spinner.remove();
            debugError('❌ Erreur AJAX:', error);
            alert('Une erreur est survenue lors du chargement des séances: ' + error.message);
        });
    }

    // Charger les étudiants via AJAX
    function loadStudents(classeId, seanceId) {
        const tableBody = document.getElementById('students-table-body');
        if (!tableBody) return;

        // Afficher un loader
        const label = document.querySelector('label[for="seance_id"]');
        const existingSpinner = label.querySelector('.loading-spinner');
        if (!existingSpinner) {
            const spinner = document.createElement('span');
            spinner.className = 'loading-spinner';
            label.appendChild(spinner);
        }

        const url = '{{ route("esbtp.attendances.load-students") }}?classe_id=' + classeId + '&seance_id=' + seanceId;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Retirer le spinner
            const spinner = label.querySelector('.loading-spinner');
            if (spinner) spinner.remove();

            if (data.success) {
                debugLog('✅ [AJAX] Données reçues:', data);

                // Remplacer le contenu du tbody
                tableBody.innerHTML = data.html;

                // Mettre à jour les inputs hidden
                document.getElementById('hidden_seance_id').value = seanceId;
                document.getElementById('hidden_date').value = data.dateSeance;

                // IMPORTANT: Créer/Mettre à jour le champ date visible dans le formulaire de sélection
                let dateInput = document.getElementById('date');
                if (!dateInput) {
                    const dateContainer = document.createElement('div');
                    dateContainer.className = 'col-md-4';
                    dateContainer.innerHTML = `
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" required readonly value="${data.dateSeance}">
                        <small class="form-text text-info">
                            <i class="fas fa-info-circle"></i> Cette date est automatiquement calculée en fonction du jour de la séance.
                        </small>
                    `;
                    document.getElementById('selectionForm').appendChild(dateContainer);
                } else {
                    dateInput.value = data.dateSeance;
                }

                // Afficher le formulaire
                const attendanceForm = document.getElementById('attendanceForm');
                attendanceForm.style.display = '';

                // Vérifier si les boutons d'action existent, sinon les créer
                let submitButtons = attendanceForm.querySelector('.submit-buttons');
                if (!submitButtons) {
                    const tableContainer = attendanceForm.querySelector('.table-responsive');
                    submitButtons = document.createElement('div');
                    submitButtons.className = 'mt-4 submit-buttons';
                    submitButtons.innerHTML = `
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Enregistrer les présences
                        </button>
                        <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">Annuler</a>
                    `;
                    tableContainer.insertAdjacentElement('afterend', submitButtons);
                }

                // Vérifier si les boutons "marquer tous" existent, sinon les créer
                let quickButtons = attendanceForm.querySelector('.quick-action-buttons');
                if (!quickButtons) {
                    const submitButtonsContainer = attendanceForm.querySelector('.submit-buttons');
                    quickButtons = document.createElement('div');
                    quickButtons.className = 'mt-3 quick-action-buttons';
                    quickButtons.innerHTML = `
                        <button type="button" class="btn btn-success btn-sm" onclick="marquerTous('present')">
                            <i class="mdi mdi-check-all"></i> Tous présents
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="marquerTous('absent')">
                            <i class="mdi mdi-close-all"></i> Tous absents
                        </button>
                    `;
                    submitButtonsContainer.insertAdjacentElement('afterend', quickButtons);
                }

                // Informer Alpine : séance chargée avec N étudiants
                window.dispatchEvent(new CustomEvent('attendance:seance-loaded', {
                    detail: { nbEtudiants: data.nbEtudiants || 0 }
                }));

                // Animer toutes les lignes
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    setTimeout(() => {
                        triggerStudentRowHighlight(row);
                    }, index * 100);
                });

                debugLog('✅ Étudiants chargés:', data.nbEtudiants, 'Mode:', data.mode);
            } else {
                // Informer Alpine : séance chargée mais 0 étudiant
                window.dispatchEvent(new CustomEvent('attendance:seance-loaded', {
                    detail: { nbEtudiants: 0 }
                }));
                debugError('❌ Erreur:', data.message);
            }
        })
        .catch(error => {
            const spinner = label.querySelector('.loading-spinner');
            if (spinner) spinner.remove();
            debugError('❌ Erreur AJAX:', error);
            alert('Une erreur est survenue lors du chargement des étudiants.');
        });
    }

    // PATTERN EXACT DE PAIEMENTS.INDEX - jQuery + .off().on()
    $(document).ready(function() {
        debugLog('🚀 [ATTENDANCES] Script jQuery chargé');

        // 1. AJAX quand classe change — pas de reload, Alpine pilote les onglets
        $('#classe_id').off('change').on('change', function(e) {
            debugLog('🔵 [ATTENDANCES] Classe changée:', $(this).val());

            e.preventDefault();
            e.stopImmediatePropagation();

            const classeId = $(this).val();

            if (classeId) {
                loadSeances(classeId);
            } else {
                const seanceContainer = document.getElementById('seance-select-container');
                if (seanceContainer) seanceContainer.remove();
                const form = document.getElementById('attendanceForm');
                if (form) form.style.display = 'none';
                const tbody = document.getElementById('students-table-body');
                if (tbody) tbody.innerHTML = '';
                // Dispatch Alpine event: classe unset
                window.dispatchEvent(new CustomEvent('attendance:classe-changed', {
                    detail: { classeId: null, matieres: [] }
                }));
            }

            return false;
        });

        // 2. AJAX quand séance change — pas de reload, injection directe
        $(document).off('change', '#seance_id').on('change', '#seance_id', function(e) {
            debugLog('🔵 [ATTENDANCES] Séance changée:', $(this).val());

            e.preventDefault();
            e.stopImmediatePropagation();

            const classeId = $('#classe_id').val();
            const seanceId = $(this).val();

            if (classeId && seanceId) {
                const newUrl = '{{ route("esbtp.attendances.create") }}?classe_id=' + classeId + '&seance_id=' + seanceId;
                history.pushState({}, '', newUrl);
                loadStudents(classeId, seanceId);
            }

            return false;
        });

        // 3. Intercepter la soumission du formulaire pour save AJAX + refresh badge
        $(document).off('submit', '#attendanceForm').on('submit', '#attendanceForm', function(e) {
            e.preventDefault();
            debugLog('🔵 [ATTENDANCES] Soumission formulaire interceptée');

            const form = $(this);
            const formData = new FormData(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();

            // Désactiver le bouton et afficher un loader
            submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Enregistrement...');

            fetch(form.attr('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => {
                // Si redirection = succès
                if (response.redirected || response.ok) {
                    debugLog('✅ [ATTENDANCES] Présences enregistrées avec succès');

                    // Afficher un message de succès
                    const alertContainer = document.querySelector('.at-card-body');
                    const existingSuccess = alertContainer.querySelector('.alert-success');
                    if (existingSuccess) existingSuccess.remove();

                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success alert-dismissible fade show mb-4';
                    successAlert.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>Les présences ont été enregistrées avec succès.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    alertContainer.insertBefore(successAlert, alertContainer.firstChild);

                    // Recharger les séances pour mettre à jour le badge
                    const classeId = $('#classe_id').val();
                    const seanceId = $('#seance_id').val();

                    if (classeId && seanceId) {
                        debugLog('🔄 [ATTENDANCES] Rechargement séances pour MAJ badge...');

                        // Recharger les séances
                        const url = '{{ route("esbtp.attendances.load-seances") }}?classe_id=' + classeId;
                        fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Mettre à jour les options du select séance
                                const seanceSelect = document.getElementById('seance_id');
                                if (seanceSelect) {
                                    seanceSelect.innerHTML = data.options;
                                    seanceSelect.value = seanceId; // Resélectionner la séance
                                }

                                // Recharger les étudiants pour afficher les badges "Modification"
                                debugLog('🔄 [ATTENDANCES] Rechargement étudiants...');
                                loadStudents(classeId, seanceId);
                            }
                        });
                    }

                    // Réactiver le bouton
                    submitBtn.prop('disabled', false).html(originalText);

                    // Scroll vers le haut pour voir le message
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data && !data.success) {
                    debugError('❌ [ATTENDANCES] Erreur:', data.message || data.errors);
                    alert('Erreur: ' + (data.message || JSON.stringify(data.errors)));
                    submitBtn.prop('disabled', false).html(originalText);
                }
            })
            .catch(error => {
                debugError('❌ [ATTENDANCES] Erreur AJAX:', error);
                alert('Une erreur est survenue lors de l\'enregistrement.');
                submitBtn.prop('disabled', false).html(originalText);
            });

            return false;
        });

        debugLog('✅ [ATTENDANCES] Event delegation configuré - ZERO RELOAD MODE');
    });

    /* ═══════════════════════════════════════════════════════════
       Filtre de classe : recherche texte + filtres filière / niveau
       ═══════════════════════════════════════════════════════════ */
    function classeFilter(config) {
        return {
            classes: Array.isArray(config.classes) ? config.classes : [],
            filiereId: '',
            niveauId: '',
            search: '',
            selectedId: String(config.initialClasseId || ''),
            init() {
                this.$watch('filtered', (list) => {
                    if (!this.selectedId) return;
                    const stillThere = list.some(c => String(c.id) === String(this.selectedId));
                    if (!stillThere) this.selectedId = '';
                });
            },
            get filtered() {
                const term = this.search.trim().toLowerCase();
                const f = this.filiereId ? parseInt(this.filiereId, 10) : null;
                const n = this.niveauId ? parseInt(this.niveauId, 10) : null;
                return this.classes.filter(c =>
                    (!f || parseInt(c.filiere_id, 10) === f) &&
                    (!n || parseInt(c.niveau_etude_id, 10) === n) &&
                    (!term || (c.name || '').toLowerCase().includes(term))
                );
            },
            reset() {
                this.filiereId = '';
                this.niveauId = '';
                this.search = '';
            }
        };
    }

    /* ═══════════════════════════════════════════════════════════
       Onglet Saisie manuelle des heures (Alpine component)
       ═══════════════════════════════════════════════════════════ */
    function manualHoursTab(config) {
        return {
            activeTab: 'seances',
            classeId: config.initialClasseId || 0,
            anneeId: config.anneeId,
            matieres: Array.isArray(config.initialMatieres) ? config.initialMatieres : [],
            matiereId: '',
            periode: 'semestre1',
            loading: false,
            html: '',
            globalEnabled: !!config.globalEnabled,
            modeGlobal: false,

            get hasClasse() {
                return !!this.classeId;
            },

            onModeGlobalChanged() {
                this.html = '';
                if (this.modeGlobal) {
                    this.matiereId = '';
                }
            },

            onClasseChanged(detail) {
                this.classeId = detail.classeId || 0;
                this.matieres = Array.isArray(detail.matieres) ? detail.matieres : [];
                this.matiereId = '';
                this.modeGlobal = false;
                this.html = '';
                // Reconstruire les <option> du select matière (évite x-for dans <select>)
                this.$nextTick(() => {
                    const sel = this.$refs.matiereSelect;
                    if (!sel) return;
                    sel.innerHTML = '<option value="">— Sélectionner une matière —</option>'
                        + this.matieres.map(m => `<option value="${m.id}">${this.escapeHtml(m.name)}</option>`).join('');
                });
            },

            escapeHtml(str) {
                return String(str ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[c]));
            },

            async loadGrid() {
                if (!this.modeGlobal && !this.matiereId) return;
                this.loading = true;
                this.html = '';
                try {
                    const url = new URL('{{ route('esbtp.attendances.manual.load') }}', window.location.origin);
                    url.searchParams.set('classe_id', this.classeId);
                    if (this.modeGlobal) {
                        url.searchParams.set('mode', 'global');
                    } else {
                        url.searchParams.set('matiere_id', this.matiereId);
                    }
                    url.searchParams.set('periode', this.periode);
                    if (this.anneeId) url.searchParams.set('annee_universitaire_id', this.anneeId);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) {
                        throw new Error('HTTP '+res.status);
                    }
                    const data = await res.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Erreur inconnue');
                    }
                    this.html = data.html;
                    this.$nextTick(() => this.bindHandlers());
                } catch (e) {
                    this.html = '<div class="amh-alert amh-alert--warning"><i class="fas fa-triangle-exclamation"></i><div>Impossible de charger la grille: '+e.message+'</div></div>';
                } finally {
                    this.loading = false;
                }
            },

            bindHandlers() {
                const form = document.getElementById('amh-form');
                if (form) {
                    form.addEventListener('submit', (e) => this.handleSubmit(e));
                }
                document.querySelectorAll('.amh-delete-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => this.handleDelete(e));
                });

                // Inputs : tracker les modifications pour mettre à jour l'état de la ligne
                document.querySelectorAll('.amh-row .amh-input').forEach(input => {
                    input.addEventListener('input', (e) => {
                        const row = e.target.closest('.amh-row');
                        if (row) this.refreshRowState(row);
                    });
                });

                // Reset par ligne
                document.querySelectorAll('.amh-reset-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => this.resetRow(e));
                });

                // Reset global
                const resetAll = document.getElementById('amh-reset-all');
                if (resetAll) {
                    resetAll.addEventListener('click', () => this.resetAll());
                }

                // Init initial state for each row (pour attraper le rendu initial)
                document.querySelectorAll('.amh-row').forEach(row => this.refreshRowState(row));
            },

            refreshRowState(row) {
                const hasSavedId = !!row.dataset.rowId;
                const origPres  = row.dataset.origPres  ?? '';
                const origAbsJ  = row.dataset.origAbsJ  ?? '';
                const origAbsNj = row.dataset.origAbsNj ?? '';
                const origNotes = row.dataset.origNotes ?? '';

                const pres  = row.querySelector('.amh-input--pres')?.value  ?? '';
                const absJ  = row.querySelector('.amh-input--abs-j')?.value ?? '';
                const absNj = row.querySelector('.amh-input--abs-nj')?.value ?? '';
                const notes = row.querySelector('.amh-input--note')?.value ?? '';

                const normalized = v => (v === '' || v === null || v === undefined) ? '' : String(parseFloat(v) || 0);
                const dirty =
                    normalized(pres) !== normalized(origPres) ||
                    normalized(absJ) !== normalized(origAbsJ) ||
                    normalized(absNj) !== normalized(origAbsNj) ||
                    (notes ?? '') !== (origNotes ?? '');

                const presN  = parseFloat(pres)  || 0;
                const absJN  = parseFloat(absJ)  || 0;
                const absNjN = parseFloat(absNj) || 0;
                const total  = presN + absJN + absNjN;

                const isEmpty = total === 0 && !notes;

                let state;
                if (hasSavedId) {
                    state = dirty ? 'modified' : 'saved';
                } else {
                    state = isEmpty ? 'empty' : 'modified';
                }

                row.dataset.state = state;

                // Total saisi + match/mismatch avec volume prévu
                const panel = row.closest('.amh-panel');
                const expected = parseFloat(panel?.dataset.volumeTotal || '0') || 0;
                const totalEl = row.querySelector('[data-row-total]');
                if (totalEl) {
                    totalEl.textContent = this.formatHours(total);
                    let match = 'na';
                    if (expected > 0 && !isEmpty) {
                        const diff = Math.abs(total - expected);
                        match = diff < 0.001 ? 'ok' : (total > expected ? 'over' : 'under');
                    }
                    row.dataset.totalMatch = match;
                    const title = expected > 0
                        ? `Volume prévu : ${this.formatHours(expected)}h — saisi : ${this.formatHours(total)}h` + (match === 'over' ? ` (dépasse de ${this.formatHours(total - expected)}h)` : match === 'under' ? ` (manque ${this.formatHours(expected - total)}h)` : '')
                        : 'Aucun volume horaire prévu défini';
                    totalEl.parentElement.setAttribute('title', title);
                }

                const resetBtn = row.querySelector('.amh-reset-btn');
                if (resetBtn) {
                    resetBtn.disabled = !dirty && !(hasSavedId === false && !isEmpty);
                }
            },

            formatHours(h) {
                if (h === 0) return '0';
                const s = (Math.round(h * 100) / 100).toString();
                return s;
            },

            resetRow(e) {
                const row = e.currentTarget.closest('.amh-row');
                if (!row) return;
                const pres  = row.querySelector('.amh-input--pres');
                const absJ  = row.querySelector('.amh-input--abs-j');
                const absNj = row.querySelector('.amh-input--abs-nj');
                const notes = row.querySelector('.amh-input--note');
                if (pres)  pres.value  = row.dataset.origPres  ?? '';
                if (absJ)  absJ.value  = row.dataset.origAbsJ  ?? '';
                if (absNj) absNj.value = row.dataset.origAbsNj ?? '';
                if (notes) notes.value = row.dataset.origNotes ?? '';
                this.refreshRowState(row);
            },

            resetAll() {
                const dirtyRows = document.querySelectorAll('.amh-row[data-state="modified"]');
                if (dirtyRows.length === 0) {
                    this.showToast('Aucune modification à annuler', 'success');
                    return;
                }
                if (!confirm(`Annuler les modifications non enregistrées pour ${dirtyRows.length} ligne${dirtyRows.length > 1 ? 's' : ''} ?`)) return;
                document.querySelectorAll('.amh-row').forEach(row => {
                    const pres  = row.querySelector('.amh-input--pres');
                    const absJ  = row.querySelector('.amh-input--abs-j');
                    const absNj = row.querySelector('.amh-input--abs-nj');
                    const notes = row.querySelector('.amh-input--note');
                    if (pres)  pres.value  = row.dataset.origPres  ?? '';
                    if (absJ)  absJ.value  = row.dataset.origAbsJ  ?? '';
                    if (absNj) absNj.value = row.dataset.origAbsNj ?? '';
                    if (notes) notes.value = row.dataset.origNotes ?? '';
                    this.refreshRowState(row);
                });
                this.showToast('Modifications annulées', 'success');
            },

            async handleSubmit(e) {
                e.preventDefault();
                const form = e.target;

                // Contrôle de cohérence : avertir si des lignes ne matchent pas le volume horaire prévu
                const panel = form.closest('.amh-panel');
                const expected = parseFloat(panel?.dataset.volumeTotal || '0') || 0;
                if (expected > 0) {
                    const mismatched = Array.from(form.querySelectorAll('.amh-row'))
                        .filter(r => r.dataset.state !== 'empty' && (r.dataset.totalMatch === 'over' || r.dataset.totalMatch === 'under'));
                    if (mismatched.length > 0) {
                        const ok = confirm(
                            `${mismatched.length} ligne${mismatched.length > 1 ? 's' : ''} ne correspond${mismatched.length > 1 ? 'ent' : ''} pas au volume horaire prévu (${this.formatHours(expected)}h).\n\n` +
                            `Continuer l'enregistrement ?`
                        );
                        if (!ok) return;
                    }
                }

                const submitBtn = form.querySelector('#amh-submit');
                const originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Enregistrement...';

                const formData = new FormData(form);

                try {
                    const res = await fetch('{{ route('esbtp.attendances.manual.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                || form.querySelector('input[name="_token"]')?.value
                        },
                        body: formData
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        const err = data.errors
                            ? Object.values(data.errors).flat().join('\n')
                            : (data.message || 'Erreur inconnue');
                        throw new Error(err);
                    }
                    this.showToast(data.message || 'Enregistré avec succès', 'success');
                    await this.loadGrid();
                } catch (err) {
                    this.showToast('Erreur: '+err.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            },

            async handleDelete(e) {
                const btn = e.currentTarget;
                const rowId = btn.dataset.rowId;
                if (!rowId) return;
                if (!confirm('Supprimer cette saisie manuelle ? Le bulletin utilisera à nouveau les séances pour cette matière.')) return;

                try {
                    const res = await fetch(`/esbtp/attendances/manual/${rowId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Erreur');
                    }
                    this.showToast(data.message || 'Supprimé', 'success');
                    await this.loadGrid();
                } catch (err) {
                    this.showToast('Erreur: '+err.message, 'error');
                }
            },

            showToast(msg, type) {
                const t = document.createElement('div');
                t.style.cssText = 'position:fixed;top:1rem;right:1rem;padding:.8rem 1.2rem;border-radius:10px;color:#fff;font-weight:600;font-size:.85rem;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.15);'
                    + (type === 'error' ? 'background:#dc2626;' : 'background:#10b981;');
                t.textContent = msg;
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 3500);
            }
        };
    }
</script>
@endpush
