@extends('layouts.app')

@section('title', 'Modifier l\'évaluation : ' . $evaluation->titre . ' - KLASSCI')

@php
    $classesOptions = $classes->mapWithKeys(fn($c) => [
        $c->id => $c->name . ' (' . ($c->filiere->name ?? '—') . ' · ' . ($c->niveau->name ?? '—') . ')',
    ])->all();
    $matieresOptions = $matieres->mapWithKeys(fn($m) => [
        $m->id => $m->nom ?? $m->name ?? 'Matière ' . $m->id,
    ])->all();
    $notesCount = $evaluation->notes->count();
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        {{-- Hero --}}
        <div class="ee-hero">
            <div class="ee-hero-top">
                <div class="ee-hero-left">
                    <div class="ee-hero-icon"><i class="fas fa-pen-to-square"></i></div>
                    <div>
                        <h1>Modifier l'évaluation</h1>
                        <p>{{ $evaluation->titre }}</p>
                    </div>
                </div>
                <div class="ee-hero-actions">
                    @php
                        $statusLabel = $evaluation->status_label;
                        $statusColor = match($evaluation->status) {
                            'draft' => 'rgba(255,255,255,.10)',
                            'scheduled' => 'rgba(59,130,246,.20)',
                            'in_progress' => 'rgba(245,158,11,.25)',
                            'completed' => 'rgba(16,185,129,.25)',
                            'cancelled' => 'rgba(239,68,68,.25)',
                            default => 'rgba(255,255,255,.10)',
                        };
                    @endphp
                    <span class="ee-chip" style="background:{{ $statusColor }};">
                        <i class="fas fa-circle" style="font-size:.5rem;"></i>
                        {{ $statusLabel }}
                    </span>
                    @if($notesCount > 0)
                        <span class="ee-chip">
                            <i class="fas fa-pen"></i>
                            {{ $notesCount }} note{{ $notesCount > 1 ? 's' : '' }} saisie{{ $notesCount > 1 ? 's' : '' }}
                        </span>
                    @endif
                    <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="ee-btn ee-btn--glass">
                        <i class="fas fa-eye"></i> Voir les détails
                    </a>
                    <a href="{{ route('esbtp.evaluations.index') }}" class="ee-btn ee-btn--glass">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- Erreurs --}}
        @if($errors->any())
            <div class="ee-alert ee-alert--error">
                <i class="fas fa-exclamation-circle"></i>
                <div class="ee-alert-body">
                    <div class="ee-alert-title">Erreur de validation</div>
                    <ul class="ee-alert-errors">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Warning évaluation verrouillée --}}
        @if(!$evaluation->isEditable() && auth()->user()->can('evaluations.edit_locked'))
            <div class="ee-alert ee-alert--warning">
                <i class="fas fa-unlock"></i>
                <div class="ee-alert-body">
                    <div class="ee-alert-title">Modification d'une évaluation verrouillée</div>
                    <p class="ee-alert-text">
                        Cette évaluation est en statut <span class="ee-tag">{{ $statusLabel }}</span>.
                        Accessible grâce à votre permission <code>evaluations.edit_locked</code>.
                        Modifiez avec prudence : les changements de barème, coefficient ou date peuvent impacter les notes déjà saisies.
                    </p>
                </div>
            </div>
        @endif

        {{-- Notes warning --}}
        @if($notesCount > 0)
            <div class="ee-alert ee-alert--info">
                <i class="fas fa-info-circle"></i>
                <div class="ee-alert-body">
                    <div class="ee-alert-title">{{ $notesCount }} note{{ $notesCount > 1 ? 's' : '' }} déjà saisie{{ $notesCount > 1 ? 's' : '' }}</div>
                    <p class="ee-alert-text">
                        Modifier le barème, le coefficient ou la matière peut affecter les calculs des moyennes et des bulletins.
                    </p>
                    <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="ee-alert-action">
                        <i class="fas fa-pen"></i> Saisir / réviser les notes
                    </a>
                </div>
            </div>
        @endif

        {{-- Fallback statique matières --}}
        <div id="matiere-data" data-matieres="{{ json_encode($matieres) }}" style="display: none;"></div>

        <form action="{{ route('esbtp.evaluations.update', $evaluation) }}"
              method="POST"
              id="evaluationEditForm">
            @csrf
            @method('PUT')

            <div class="ee-sections">
                {{-- Section 1 : Informations générales --}}
                <div class="ee-card">
                    <div class="ee-card-header">
                        <div class="ee-section-icon"><i class="fas fa-info-circle"></i></div>
                        <div>
                            <h2 class="ee-card-title">Informations générales</h2>
                            <p class="ee-card-subtitle">Titre, type, période et horaires</p>
                        </div>
                    </div>
                    <div class="ee-card-body">
                        <div class="ee-grid">
                            <div class="ee-field ee-field--wide">
                                <label for="titre" class="ee-label">Titre de l'évaluation <span class="ee-required">*</span></label>
                                <input type="text" class="ee-input @error('titre') ee-input--error @enderror"
                                       id="titre" name="titre" value="{{ old('titre', $evaluation->titre) }}"
                                       placeholder="Ex : Examen final de mathématiques" required>
                                @error('titre')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label class="ee-label">Type d'évaluation <span class="ee-required">*</span></label>
                                <x-au-select
                                    name="type"
                                    id="type"
                                    icon="fa-tag"
                                    :value="old('type', $evaluation->type)"
                                    placeholder="Sélectionner un type"
                                    :options="$types"
                                    required />
                                @error('type')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label class="ee-label">Période <span class="ee-required">*</span></label>
                                <x-au-select
                                    name="periode"
                                    id="periode"
                                    icon="fa-calendar-alt"
                                    :value="old('periode', $evaluation->periode)"
                                    placeholder="Sélectionner une période"
                                    :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2']"
                                    required />
                                @error('periode')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label for="date_evaluation" class="ee-label">Date d'évaluation <span class="ee-required">*</span></label>
                                <input type="date" class="ee-input @error('date_evaluation') ee-input--error @enderror"
                                       id="date_evaluation" name="date_evaluation"
                                       value="{{ old('date_evaluation', $evaluation->date_evaluation ? date('Y-m-d', strtotime($evaluation->date_evaluation)) : '') }}" required>
                                @error('date_evaluation')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label for="heure_debut" class="ee-label">Heure de début <span class="ee-required">*</span></label>
                                <input type="time" class="ee-input @error('heure_debut') ee-input--error @enderror"
                                       id="heure_debut" name="heure_debut"
                                       value="{{ old('heure_debut', $heureDebut) }}" required>
                                @error('heure_debut')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label for="heure_fin" class="ee-label">Heure de fin <span class="ee-required">*</span></label>
                                <input type="time" class="ee-input @error('heure_fin') ee-input--error @enderror"
                                       id="heure_fin" name="heure_fin"
                                       value="{{ old('heure_fin', $heureFin) }}" required>
                                @error('heure_fin')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label for="duree_minutes" class="ee-label">Durée (minutes)</label>
                                <input type="number" class="ee-input @error('duree_minutes') ee-input--error @enderror"
                                       id="duree_minutes" name="duree_minutes" value="{{ old('duree_minutes', $evaluation->duree_minutes) }}"
                                       min="15" max="720" placeholder="Calculée auto si vide">
                                @error('duree_minutes')<div class="ee-error">{{ $message }}</div>@enderror
                                <div class="ee-hint">Laissez vide pour recalculer automatiquement depuis les horaires.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2 : Classe, Matière, Coefficient, Barème --}}
                <div class="ee-card">
                    <div class="ee-card-header">
                        <div class="ee-section-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <h2 class="ee-card-title">Classe et matière</h2>
                            <p class="ee-card-subtitle">Cible et paramètres de notation</p>
                        </div>
                    </div>
                    <div class="ee-card-body">
                        <div class="ee-grid">
                            <div class="ee-field">
                                <label class="ee-label">Classe <span class="ee-required">*</span></label>
                                <x-au-select
                                    name="classe_id"
                                    id="classe_id"
                                    icon="fa-users"
                                    :value="old('classe_id', $evaluation->classe_id)"
                                    placeholder="Sélectionner une classe"
                                    :options="$classesOptions"
                                    :searchable="count($classesOptions) > 8"
                                    required />
                                @error('classe_id')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label class="ee-label">
                                    Matière <span class="ee-required">*</span>
                                    <span class="ee-loading" id="matiere-loading" style="display:none">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </label>
                                <x-au-select
                                    name="matiere_id"
                                    id="matiere_id"
                                    icon="fa-book"
                                    :value="old('matiere_id', $evaluation->matiere_id)"
                                    placeholder="Sélectionner une matière"
                                    :options="$matieresOptions"
                                    :searchable="count($matieresOptions) > 8"
                                    required />
                                @error('matiere_id')<div class="ee-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ee-field">
                                <label for="coefficient" class="ee-label">Coefficient <span class="ee-required">*</span></label>
                                <input type="number" class="ee-input @error('coefficient') ee-input--error @enderror"
                                       id="coefficient" name="coefficient" value="{{ old('coefficient', $evaluation->coefficient) }}"
                                       step="0.1" min="0.1" max="10" required>
                                @error('coefficient')<div class="ee-error">{{ $message }}</div>@enderror
                                <div class="ee-hint">Pondération de cette évaluation dans la moyenne.</div>
                            </div>

                            <div class="ee-field">
                                <label for="bareme" class="ee-label">Barème <span class="ee-required">*</span></label>
                                <input type="number" class="ee-input @error('bareme') ee-input--error @enderror"
                                       id="bareme" name="bareme" value="{{ old('bareme', $evaluation->bareme) }}"
                                       step="0.1" min="1" required>
                                @error('bareme')<div class="ee-error">{{ $message }}</div>@enderror
                                <div class="ee-hint">Note maximale possible (généralement 20).</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3 : Description et publication --}}
                <div class="ee-card">
                    <div class="ee-card-header">
                        <div class="ee-section-icon"><i class="fas fa-align-left"></i></div>
                        <div>
                            <h2 class="ee-card-title">Description et publication</h2>
                            <p class="ee-card-subtitle">Contenu et visibilité</p>
                        </div>
                    </div>
                    <div class="ee-card-body">
                        <div class="ee-field ee-field--wide">
                            <label for="description" class="ee-label">Description (optionnelle)</label>
                            <textarea class="ee-input @error('description') ee-input--error @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="Chapitres couverts, consignes...">{{ old('description', $evaluation->description) }}</textarea>
                            @error('description')<div class="ee-error">{{ $message }}</div>@enderror
                        </div>

                        <label for="is_published" class="ee-switch">
                            <input type="checkbox" id="is_published" name="is_published" value="1"
                                   {{ old('is_published', $evaluation->is_published) ? 'checked' : '' }}>
                            <span class="ee-switch-toggle"></span>
                            <span class="ee-switch-text">
                                <span class="ee-switch-title">Évaluation publiée</span>
                                <span class="ee-switch-desc">Visible par les enseignants ; permet la saisie des notes. Décochez pour repasser en brouillon.</span>
                            </span>
                        </label>

                        <div class="ee-info ee-info--tip">
                            <i class="fas fa-lightbulb"></i>
                            <span>
                                <strong>Workflow :</strong> publié → <strong>Planifiée</strong> → <strong>En cours</strong> (date + durée) → <strong>Terminée</strong>. Le statut évolue automatiquement.
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="ee-actions">
                    @can('evaluations.delete')
                        <button type="button" class="ee-btn ee-btn--danger" data-bs-toggle="modal" data-bs-target="#deleteEvaluationModal">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    @endcan
                    <div class="ee-actions-right">
                        <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="ee-btn ee-btn--ghost">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="ee-btn ee-btn--primary" id="evaluation-submit">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal suppression --}}
@can('evaluations.delete')
<div class="modal fade" id="deleteEvaluationModal" tabindex="-1" aria-labelledby="deleteEvaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 20px 60px rgba(15,23,42,.25);">
            <div class="modal-header" style="background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border-bottom:none;border-radius:14px 14px 0 0;">
                <h5 class="modal-title" id="deleteEvaluationModalLabel" style="font-weight:700;">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <p style="font-size:.95rem;color:#1e293b;margin-bottom:1rem;">
                    Êtes-vous sûr de vouloir supprimer l'évaluation <strong>{{ $evaluation->titre }}</strong> ?
                </p>
                @if($notesCount > 0)
                    <div class="ee-alert ee-alert--error" style="margin-bottom:0;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Attention</strong>
                            Cette évaluation a <strong>{{ $notesCount }}</strong> note{{ $notesCount > 1 ? 's' : '' }} associée{{ $notesCount > 1 ? 's' : '' }} qui seront aussi supprimée{{ $notesCount > 1 ? 's' : '' }}. Action irréversible.
                        </div>
                    </div>
                @else
                    <div class="ee-info">
                        <i class="fas fa-info-circle"></i>
                        <span>Aucune note saisie pour cette évaluation — suppression sécurisée.</span>
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:1rem 1.5rem;">
                <button type="button" class="ee-btn ee-btn--ghost" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <form action="{{ route('esbtp.evaluations.destroy', $evaluation) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ee-btn ee-btn--danger">
                        <i class="fas fa-trash"></i> Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('styles')
<style>
/* ============================================================
   ee-* — Namespace évaluations.edit (premium KLASSCI)
   Cohérent avec ec-* (create) pour expérience unifiée.
   ============================================================ */
.ee-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.ee-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.ee-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.ee-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.ee-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.2; }
.ee-hero p {
    color: rgba(255,255,255,.75); font-size: .88rem; margin: .2rem 0 0;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 600px;
}
.ee-hero-actions { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
.ee-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff; font-size: .78rem; font-weight: 500;
    padding: .35rem .7rem; border-radius: 999px;
}

.ee-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem 1rem; border-radius: 10px;
    font-size: .85rem; font-weight: 600;
    cursor: pointer; border: 1px solid transparent;
    transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
    text-decoration: none;
    line-height: 1.2;
}
.ee-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border-color: rgba(255,255,255,.2);
}
.ee-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
.ee-btn--primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
    padding: .65rem 1.25rem;
}
.ee-btn--primary:hover {
    box-shadow: 0 8px 20px rgba(4,83,203,.35);
    color: #fff;
}
.ee-btn--ghost {
    background: #fff; color: #475569;
    border-color: #e2e8f0;
    padding: .65rem 1.25rem;
}
.ee-btn--ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
.ee-btn--danger {
    background: #fff; color: #dc2626;
    border-color: #fecaca;
    padding: .65rem 1.25rem;
}
.ee-btn--danger:hover { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }
.modal-footer .ee-btn--danger {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(220,38,38,.25);
}
.modal-footer .ee-btn--danger:hover { color: #fff; box-shadow: 0 8px 20px rgba(220,38,38,.35); }

.ee-alert {
    display: flex; gap: .9rem; align-items: flex-start;
    padding: 1rem 1.15rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border-left: 4px solid;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.ee-alert > i { font-size: 1.15rem; margin-top: .15rem; flex-shrink: 0; }
.ee-alert-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .35rem; }
.ee-alert-title { font-size: .9rem; font-weight: 700; line-height: 1.3; }
.ee-alert-text { font-size: .82rem; line-height: 1.55; margin: 0; color: inherit; opacity: .95; }
.ee-alert-errors { margin: 0; padding-left: 1.15rem; display: flex; flex-direction: column; gap: .15rem; }
.ee-alert-errors li { font-size: .8rem; line-height: 1.4; }
.ee-alert--error { background: #fef2f2; color: #991b1b; border-left-color: #dc2626; }
.ee-alert--error > i { color: #dc2626; }
.ee-alert--error strong { color: #7f1d1d; }
.ee-alert--warning { background: #fffbeb; color: #92400e; border-left-color: #f59e0b; }
.ee-alert--warning > i { color: #f59e0b; }
.ee-alert--info { background: #eff6ff; color: #1e3a8a; border-left-color: #0453cb; }
.ee-alert--info > i { color: #0453cb; }

.ee-alert code {
    background: rgba(0,0,0,.06);
    padding: .1rem .4rem; border-radius: 4px;
    font-family: 'Consolas', 'Monaco', monospace; font-size: .76rem;
    font-weight: 500;
}

.ee-tag {
    display: inline-flex; align-items: center;
    padding: .1rem .5rem;
    border-radius: 999px;
    background: rgba(0,0,0,.08);
    font-size: .72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .3px;
    vertical-align: middle;
}

.ee-alert-action {
    display: inline-flex; align-items: center; gap: .4rem;
    margin-top: .25rem;
    padding: .45rem .85rem;
    border-radius: 8px;
    background: rgba(4,83,203,.08);
    border: 1px solid rgba(4,83,203,.18);
    color: #0453cb;
    font-size: .8rem; font-weight: 600;
    text-decoration: none;
    align-self: flex-start;
    transition: background-color .15s, border-color .15s;
}
.ee-alert-action:hover {
    background: rgba(4,83,203,.14);
    border-color: rgba(4,83,203,.28);
    color: #033a8e;
}

.ee-sections { display: grid; gap: 1.25rem; }

.ee-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: visible;
    transition: box-shadow .2s ease;
}
.ee-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.06), 0 2px 8px rgba(15,23,42,.04);
}
.ee-card-header {
    display: flex; align-items: center; gap: .85rem;
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}
.ee-section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
}
.ee-card-title { font-size: 1.02rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2; }
.ee-card-subtitle { font-size: .8rem; color: #64748b; margin: .15rem 0 0; }
.ee-card-body { padding: 1.25rem 1.5rem 1.5rem; }

.ee-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem 1.25rem;
}
.ee-field { display: flex; flex-direction: column; min-width: 0; }
.ee-field--wide { grid-column: 1 / -1; }
.ee-label {
    font-size: .82rem; font-weight: 600; color: #1e293b;
    margin-bottom: .4rem;
    display: inline-flex; align-items: center; gap: .35rem;
}
.ee-required { color: #dc2626; font-weight: 700; }
.ee-loading { color: #0453cb; font-size: .78rem; margin-left: .35rem; }

.ee-input {
    padding: .55rem .85rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff; color: #1e293b;
    font-size: .88rem; line-height: 1.4;
    transition: border-color .15s, box-shadow .15s;
    font-family: inherit;
    width: 100%;
}
.ee-input:focus {
    outline: none; border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.ee-input--error { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.10); }
.ee-input:disabled, .ee-input[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
textarea.ee-input { resize: vertical; min-height: 90px; }

.ee-error {
    color: #dc2626; font-size: .78rem; margin-top: .3rem;
    display: flex; align-items: center; gap: .3rem;
}
.ee-error::before { content: "⚠"; font-weight: bold; }
.ee-hint { color: #64748b; font-size: .76rem; margin-top: .3rem; font-style: italic; }

.ee-info {
    display: flex; align-items: flex-start; gap: .5rem;
    background: #eff6ff;
    border-left: 3px solid #0453cb;
    padding: .65rem .85rem;
    border-radius: 8px;
    color: #1e293b;
    font-size: .8rem; line-height: 1.5;
    margin-top: .55rem;
}
.ee-info i { color: #0453cb; flex-shrink: 0; margin-top: .15rem; }
.ee-info--tip { background: #fffbeb; border-left-color: #f59e0b; margin-top: 1rem; }
.ee-info--tip i { color: #f59e0b; }

.ee-switch {
    display: flex; align-items: flex-start; gap: .85rem;
    padding: 1rem 1.15rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    cursor: pointer;
    transition: background-color .15s, border-color .15s;
    margin-top: 1rem;
}
.ee-switch:hover { background: #f1f5f9; border-color: #cbd5e1; }
.ee-switch input[type="checkbox"] { display: none; }
.ee-switch-toggle {
    width: 42px; height: 22px;
    border-radius: 999px;
    background: #cbd5e1;
    position: relative;
    flex-shrink: 0;
    transition: background-color .2s;
    margin-top: .15rem;
}
.ee-switch-toggle::after {
    content: '';
    position: absolute;
    top: 2px; left: 2px;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
    transition: transform .2s;
}
.ee-switch input:checked ~ .ee-switch-toggle { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
.ee-switch input:checked ~ .ee-switch-toggle::after { transform: translateX(20px); }
.ee-switch-text { display: flex; flex-direction: column; gap: .15rem; flex: 1; min-width: 0; }
.ee-switch-title { font-size: .88rem; font-weight: 600; color: #0f172a; }
.ee-switch-desc { font-size: .78rem; color: #64748b; line-height: 1.4; }

.ee-actions {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 0;
    flex-wrap: wrap; gap: .8rem;
}
.ee-actions-right { display: flex; gap: .6rem; flex-wrap: wrap; }

@media (max-width: 768px) {
    .ee-hero { padding: 1.5rem 1.5rem 1.25rem; }
    .ee-hero h1 { font-size: 1.2rem; }
    .ee-card-body { padding: 1rem 1.15rem 1.25rem; }
    .ee-card-header { padding: .9rem 1.15rem; }
    .ee-actions { flex-direction: column-reverse; align-items: stretch; }
    .ee-actions-right { justify-content: stretch; }
    .ee-actions-right .ee-btn { flex: 1; justify-content: center; }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    // Cascade classe → matière via API
    const classeNative = document.querySelector('select[name="classe_id"]');
    const matiereNative = document.querySelector('select[name="matiere_id"]');
    const loading = document.getElementById('matiere-loading');
    const initialMatiereId = @json((string) old('matiere_id', $evaluation->matiere_id));
    const staticMatieres = JSON.parse(document.getElementById('matiere-data').getAttribute('data-matieres') || '[]');

    function setLoading(on) {
        if (!loading) return;
        loading.style.display = on ? 'inline' : 'none';
    }

    function rebuildMatiereOptions(matieres) {
        if (!matiereNative) return;
        const currentValue = String(initialMatiereId || '');
        const fragment = document.createDocumentFragment();
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Sélectionner une matière';
        fragment.appendChild(placeholder);
        (matieres || []).forEach(function(m) {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.nom || m.name || ('Matière ' + m.id);
            if (String(m.id) === currentValue) opt.selected = true;
            fragment.appendChild(opt);
        });
        matiereNative.innerHTML = '';
        matiereNative.appendChild(fragment);
        matiereNative.disabled = false;
        // Notify x-au-select wrapper to sync visual state
        matiereNative.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function loadMatieres(classeId) {
        if (!classeId) {
            rebuildMatiereOptions(staticMatieres);
            return;
        }
        setLoading(true);
        fetch('/esbtp/api/classes/' + classeId + '/matieres', {
            headers: { 'Accept': 'application/json' }
        })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function(data) {
                rebuildMatiereOptions(Array.isArray(data) && data.length > 0 ? data : staticMatieres);
            })
            .catch(function(e) {
                console.warn('[ee-edit] matieres API failed, fallback static:', e);
                rebuildMatiereOptions(staticMatieres);
            })
            .finally(function() { setLoading(false); });
    }

    if (classeNative) {
        classeNative.addEventListener('change', function() {
            loadMatieres(this.value);
        });
    }
})();
</script>
@endpush
