@extends(request()->boolean('embed') ? 'layouts.embedded' : 'layouts.app')

@section('title', 'Nouvelle évaluation - KLASSCI')

@php
    $isEmbed = request()->boolean('embed');
    $isResubmit = old('titre') !== null;
    $publishedChecked = $isResubmit ? old('is_published') !== null : true;
    $classesOptions = $classes->mapWithKeys(fn($c) => [
        $c->id => $c->name . ' (' . ($c->filiere->name ?? '—') . ' · ' . ($c->niveau->name ?? '—') . ')',
    ])->all();
    $matieresOptions = $matieres->mapWithKeys(fn($m) => [
        $m->id => $m->nom ?? $m->name ?? 'Matière ' . $m->id,
    ])->all();
    $preClasse = !empty($classe_id) ? $classes->firstWhere('id', $classe_id) : null;
    $preMatiere = !empty($matiere_id) ? $matieres->firstWhere('id', $matiere_id) : null;
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        @unless($isEmbed)
        <div class="ec-hero">
            <div class="ec-hero-top">
                <div class="ec-hero-left">
                    <div class="ec-hero-icon"><i class="fas fa-plus-circle"></i></div>
                    <div>
                        <h1>Nouvelle évaluation</h1>
                        <p>Créer une évaluation pour une classe et une matière</p>
                    </div>
                </div>
                <div class="ec-hero-actions">
                    @if(!empty($anneeUniversitaire))
                        <span class="ec-chip">
                            <i class="far fa-calendar"></i>
                            {{ $anneeUniversitaire->name }}
                        </span>
                    @endif
                    @if(auth()->check() && !auth()->user()->can('identity.teach'))
                        <a href="{{ route('esbtp.evaluations.index') }}" class="ec-btn ec-btn--glass">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    @else
                        <a href="{{ route('teacher.dashboard') }}" class="ec-btn ec-btn--glass">
                            <i class="fas fa-arrow-left"></i> Tableau de bord
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endunless

        @if($errors->any())
            <div class="ec-alert ec-alert--error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Erreur de validation</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('esbtp.evaluations.store') }}"
              method="POST"
              id="evaluationCreateForm"
              data-matieres-json='@json($matieresJson)'
              data-load-matieres-url="{{ route('esbtp.evaluations.load-matieres') }}"
              data-coeff-check-url="{{ route('esbtp.evaluations.coefficients.check') }}">
            @csrf
            @if($isEmbed)
                <input type="hidden" name="embed" value="1">
                @if(!empty($classe_id))
                    <input type="hidden" name="classe_id" value="{{ $classe_id }}">
                @endif
                @if(!empty($matiere_id))
                    <input type="hidden" name="matiere_id" value="{{ $matiere_id }}">
                @endif
            @endif

            <div class="ec-sections">
                {{-- Section 1 : Informations générales --}}
                <div class="ec-card">
                    <div class="ec-card-header">
                        <div class="ec-section-icon"><i class="fas fa-info-circle"></i></div>
                        <div>
                            <h2 class="ec-card-title">Informations générales</h2>
                            <p class="ec-card-subtitle">Détails de base de l'évaluation</p>
                        </div>
                    </div>
                    <div class="ec-card-body">
                        <div class="ec-grid">
                            <div class="ec-field ec-field--wide">
                                <label for="titre" class="ec-label">Titre de l'évaluation <span class="ec-required">*</span></label>
                                <input type="text" class="ec-input @error('titre') ec-input--error @enderror"
                                       id="titre" name="titre" value="{{ old('titre') }}"
                                       placeholder="Ex : Examen final de mathématiques" required>
                                @error('titre')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label class="ec-label">Type d'évaluation <span class="ec-required">*</span></label>
                                <x-au-select
                                    name="type"
                                    id="type"
                                    icon="fa-tag"
                                    :value="old('type', '')"
                                    placeholder="Sélectionner un type"
                                    :options="$types"
                                    required />
                                @error('type')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label class="ec-label">Période <span class="ec-required">*</span></label>
                                <x-au-select
                                    name="periode"
                                    id="periode"
                                    icon="fa-calendar-alt"
                                    :value="old('periode', '')"
                                    placeholder="Sélectionner une période"
                                    :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2']"
                                    required />
                                @error('periode')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label for="date_evaluation" class="ec-label">Date d'évaluation <span class="ec-required">*</span></label>
                                <input type="date" class="ec-input @error('date_evaluation') ec-input--error @enderror"
                                       id="date_evaluation" name="date_evaluation" value="{{ old('date_evaluation') }}" required>
                                @error('date_evaluation')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label for="heure_debut" class="ec-label">Heure de début <span class="ec-required">*</span></label>
                                <input type="time" class="ec-input @error('heure_debut') ec-input--error @enderror"
                                       id="heure_debut" name="heure_debut"
                                       value="{{ old('heure_debut', '08:00') }}" required>
                                @error('heure_debut')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label for="heure_fin" class="ec-label">Heure de fin <span class="ec-required">*</span></label>
                                <input type="time" class="ec-input @error('heure_fin') ec-input--error @enderror"
                                       id="heure_fin" name="heure_fin"
                                       value="{{ old('heure_fin', '10:00') }}" required>
                                @error('heure_fin')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label for="duree_minutes" class="ec-label">Durée (minutes)</label>
                                <input type="number" class="ec-input @error('duree_minutes') ec-input--error @enderror"
                                       id="duree_minutes" name="duree_minutes" value="{{ old('duree_minutes') }}"
                                       min="15" max="720" placeholder="Calculée auto si vide">
                                @error('duree_minutes')<div class="ec-error">{{ $message }}</div>@enderror
                                <div class="ec-hint">Laissez vide pour calculer automatiquement à partir des horaires.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2 : Classe, Matière, Coefficient, Barème --}}
                <div class="ec-card">
                    <div class="ec-card-header">
                        <div class="ec-section-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <h2 class="ec-card-title">Classe et matière</h2>
                            <p class="ec-card-subtitle">Sélection de la classe et de la matière concernées</p>
                        </div>
                    </div>
                    <div class="ec-card-body">
                        <div class="ec-grid">
                            <div class="ec-field">
                                <label class="ec-label">Classe <span class="ec-required">*</span></label>
                                @if($isEmbed && $preClasse)
                                    <div class="ec-readonly">
                                        <i class="fas fa-users"></i>
                                        <span>{{ $preClasse->name }} ({{ $preClasse->filiere->name ?? '—' }} · {{ $preClasse->niveau->name ?? '—' }})</span>
                                        <span class="ec-readonly-tag">Pré-sélectionnée</span>
                                    </div>
                                @else
                                    <x-au-select
                                        name="classe_id"
                                        id="classe_id"
                                        icon="fa-users"
                                        :value="old('classe_id', $classe_id)"
                                        placeholder="Sélectionner une classe"
                                        :options="$classesOptions"
                                        :searchable="count($classesOptions) > 8"
                                        required />
                                @endif
                                @error('classe_id')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label class="ec-label">
                                    Matière <span class="ec-required">*</span>
                                    <span class="ec-loading" id="matiere-loading" style="display:none">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </label>
                                @if($isEmbed && $preMatiere)
                                    <div class="ec-readonly">
                                        <i class="fas fa-book"></i>
                                        <span>{{ $preMatiere->nom ?? $preMatiere->name }}</span>
                                        <span class="ec-readonly-tag">Pré-sélectionnée</span>
                                    </div>
                                @else
                                    <x-au-select
                                        name="matiere_id"
                                        id="matiere_id"
                                        icon="fa-book"
                                        :value="old('matiere_id', $matiere_id)"
                                        placeholder="Sélectionner une matière"
                                        :options="$matieresOptions"
                                        :searchable="count($matieresOptions) > 8"
                                        required />
                                @endif
                                @error('matiere_id')<div class="ec-error">{{ $message }}</div>@enderror

                                <div class="ec-info">
                                    <i class="fas fa-info-circle"></i>
                                    <span>
                                        @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.coordinate', 'identity.school_manager']))
                                            Pour rattacher une matière, allez sur
                                            <a href="{{ route('esbtp.matieres.index') }}" class="ec-link">Matières</a>
                                            puis cliquez sur <strong>Configurer les liaisons</strong>.
                                        @else
                                            Si une matière manque, signalez-le à la direction pour la rattacher à la classe.
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="ec-field">
                                <label for="coefficient" class="ec-label">Coefficient <span class="ec-required">*</span></label>
                                <div class="ec-input-group">
                                    <input type="number" class="ec-input @error('coefficient') ec-input--error @enderror"
                                           id="coefficient" name="coefficient" value="{{ old('coefficient', 1) }}"
                                           step="0.1" min="0.1" max="10" required>
                                    <button type="button" class="ec-icon-btn"
                                            id="btn-use-matiere-coefficient"
                                            title="Reprendre le coefficient de la matière">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div id="coeff-matiere-info" class="ec-info" style="display:none">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="coeff-matiere-info-text"></span>
                                </div>
                                <div class="ec-hint">Le coefficient de l'évaluation peut différer de celui de la matière (ex : quiz vs examen).</div>
                                @error('coefficient')<div class="ec-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="ec-field">
                                <label for="bareme" class="ec-label">Barème <span class="ec-required">*</span></label>
                                <input type="number" class="ec-input @error('bareme') ec-input--error @enderror"
                                       id="bareme" name="bareme" value="{{ old('bareme', 20) }}"
                                       step="0.1" min="1" required>
                                @error('bareme')<div class="ec-error">{{ $message }}</div>@enderror
                                <div class="ec-hint">Note maximale possible (par défaut 20).</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3 : Description et publication --}}
                <div class="ec-card">
                    <div class="ec-card-header">
                        <div class="ec-section-icon"><i class="fas fa-align-left"></i></div>
                        <div>
                            <h2 class="ec-card-title">Description et publication</h2>
                            <p class="ec-card-subtitle">Contenu et statut de l'évaluation</p>
                        </div>
                    </div>
                    <div class="ec-card-body">
                        <div class="ec-field ec-field--wide">
                            <label for="description" class="ec-label">Description (optionnelle)</label>
                            <textarea class="ec-input @error('description') ec-input--error @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="Décrivez le contenu, les chapitres couverts...">{{ old('description') }}</textarea>
                            @error('description')<div class="ec-error">{{ $message }}</div>@enderror
                        </div>

                        <label for="is_published" class="ec-switch">
                            <input type="checkbox" id="is_published" name="is_published" value="1"
                                   {{ $publishedChecked ? 'checked' : '' }}>
                            <span class="ec-switch-toggle"></span>
                            <span class="ec-switch-text">
                                <span class="ec-switch-title">Publier immédiatement</span>
                                <span class="ec-switch-desc">L'évaluation sera visible par les enseignants et permettra la saisie des notes. Décochez pour la garder en brouillon.</span>
                            </span>
                        </label>

                        <div class="ec-info ec-info--tip">
                            <i class="fas fa-lightbulb"></i>
                            <span>
                                <strong>Astuce :</strong> tant qu'elle n'est pas publiée, l'évaluation reste en brouillon (invisible aux étudiants).
                                Une fois publiée, son statut passe automatiquement à <strong>Planifiée</strong>, puis <strong>En cours</strong>, puis <strong>Terminée</strong> selon la date et la durée.
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Section 4 : Assignation enseignant (non-enseignants uniquement) --}}
                @if(auth()->check() && !auth()->user()->can('identity.teach'))
                <div class="ec-card">
                    <div class="ec-card-header">
                        <div class="ec-section-icon"><i class="fas fa-user-tie"></i></div>
                        <div>
                            <h2 class="ec-card-title">Assignation d'enseignant</h2>
                            <p class="ec-card-subtitle">Attribuer l'évaluation à un enseignant</p>
                        </div>
                    </div>
                    <div class="ec-card-body">
                        <div class="ec-grid">
                            <div class="ec-field">
                                <label class="ec-label">Enseignant de la plateforme</label>
                                <x-au-user-picker
                                    name="enseignant_id"
                                    :value="old('enseignant_id')"
                                    :users="$enseignants"
                                    placeholder="— Sélectionner un enseignant —" />
                                <div class="ec-hint">L'enseignant pourra saisir les notes directement.</div>
                            </div>

                            <div class="ec-field">
                                <label for="enseignant_externe_nom" class="ec-label">Enseignant externe</label>
                                <input type="text" class="ec-input"
                                       id="enseignant_externe_nom" name="enseignant_externe_nom"
                                       value="{{ old('enseignant_externe_nom') }}"
                                       placeholder="Nom complet de l'enseignant">
                                <div class="ec-hint">Si l'enseignant n'a pas de compte sur la plateforme.</div>
                            </div>
                        </div>

                        <label for="generer_lien_externe" class="ec-switch">
                            <input type="checkbox" id="generer_lien_externe" name="generer_lien_externe" value="1"
                                   {{ old('generer_lien_externe') ? 'checked' : '' }}>
                            <span class="ec-switch-toggle"></span>
                            <span class="ec-switch-text">
                                <span class="ec-switch-title">Générer un lien de saisie pour l'enseignant externe</span>
                                <span class="ec-switch-desc">Un lien temporaire (valable 30 jours) sera créé pour permettre la saisie des notes.</span>
                            </span>
                        </label>

                        <div class="ec-info ec-info--tip">
                            <i class="fas fa-lightbulb"></i>
                            <span>
                                <strong>Options :</strong> Enseignant plateforme → connexion + saisie · Enseignant externe → traçabilité du nom · Lien externe → envoi du lien pour saisie sans compte.
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="ec-actions">
                    <button type="reset" class="ec-btn ec-btn--ghost">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                    <button type="submit" class="ec-btn ec-btn--primary" id="evaluation-submit">
                        <i class="fas fa-save"></i> Enregistrer l'évaluation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal coefficient manquant (conservé pour rétro-compat) --}}
<div class="modal fade" id="coeffMissingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-triangle-exclamation me-2"></i>Coefficient manquant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="coeffMissingModalBody"></div>
        </div>
    </div>
</div>

{{-- Toast container --}}
<div class="ec-toast-container" id="ec-toast-container" aria-live="polite" aria-atomic="true"></div>
@endsection

@push('styles')
<style>
/* ============================================================
   ec-* — Namespace évaluations.create (premium KLASSCI)
   ============================================================ */
.ec-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.ec-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.ec-hero-left { display: flex; align-items: center; gap: 1rem; }
.ec-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.ec-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.ec-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: .15rem 0 0; }
.ec-hero-actions { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
.ec-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff; font-size: .8rem; font-weight: 500;
    padding: .4rem .75rem; border-radius: 10px;
}

.ec-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem 1rem; border-radius: 10px;
    font-size: .85rem; font-weight: 600;
    cursor: pointer; border: 1px solid transparent;
    transition: all .2s ease; text-decoration: none;
    line-height: 1.2;
}
.ec-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border-color: rgba(255,255,255,.2);
}
.ec-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
.ec-btn--primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
    padding: .65rem 1.25rem;
}
.ec-btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(4,83,203,.35);
    color: #fff;
}
.ec-btn--ghost {
    background: #fff; color: #475569;
    border-color: #e2e8f0;
    padding: .65rem 1.25rem;
}
.ec-btn--ghost:hover { background: #f8fafc; color: #0f172a; }

.ec-alert {
    display: flex; gap: .9rem; align-items: flex-start;
    padding: 1rem 1.15rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border-left: 4px solid;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.ec-alert i { font-size: 1.2rem; margin-top: .15rem; flex-shrink: 0; }
.ec-alert strong { display: block; margin-bottom: .35rem; }
.ec-alert ul { margin: 0; padding-left: 1.1rem; }
.ec-alert li { font-size: .85rem; line-height: 1.5; }
.ec-alert--error { background: #fef2f2; color: #991b1b; border-left-color: #dc2626; }
.ec-alert--error i { color: #dc2626; }

.ec-sections { display: grid; gap: 1.25rem; max-width: none; }

.ec-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: visible;
    transition: box-shadow .2s ease;
}
.ec-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.06), 0 2px 8px rgba(15,23,42,.04);
}
.ec-card-header {
    display: flex; align-items: center; gap: .85rem;
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}
.ec-section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
}
.ec-card-title { font-size: 1.02rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2; }
.ec-card-subtitle { font-size: .8rem; color: #64748b; margin: .15rem 0 0; }
.ec-card-body { padding: 1.25rem 1.5rem 1.5rem; }

.ec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem 1.25rem;
}
.ec-field { display: flex; flex-direction: column; min-width: 0; }
.ec-field--wide { grid-column: 1 / -1; }
.ec-label {
    font-size: .82rem; font-weight: 600; color: #1e293b;
    margin-bottom: .4rem;
    display: inline-flex; align-items: center; gap: .35rem;
}
.ec-required { color: #dc2626; font-weight: 700; }
.ec-loading { color: #0453cb; font-size: .78rem; margin-left: .35rem; }

.ec-input {
    padding: .55rem .85rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff; color: #1e293b;
    font-size: .88rem; line-height: 1.4;
    transition: border-color .15s, box-shadow .15s;
    font-family: inherit;
    width: 100%;
}
.ec-input:focus {
    outline: none; border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.ec-input--error { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.10); }
.ec-input:disabled, .ec-input[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
textarea.ec-input { resize: vertical; min-height: 90px; }

.ec-readonly {
    display: flex; align-items: center; gap: .55rem;
    padding: .55rem .85rem;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    background: #f8fafc;
    color: #475569; font-size: .88rem; font-weight: 500;
}
.ec-readonly i { color: #64748b; }
.ec-readonly span:first-of-type { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ec-readonly-tag {
    font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
    padding: .2rem .5rem; border-radius: 6px;
    background: #e0e7ff; color: #3730a3;
    flex-shrink: 0;
}

.ec-error {
    color: #dc2626; font-size: .78rem; margin-top: .3rem;
    display: flex; align-items: center; gap: .3rem;
}
.ec-error::before { content: "⚠"; font-weight: bold; }
.ec-hint { color: #64748b; font-size: .76rem; margin-top: .3rem; font-style: italic; }

.ec-input-group { display: flex; gap: .5rem; align-items: stretch; }
.ec-input-group .ec-input { flex: 1; }
.ec-icon-btn {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: 0 .85rem;
    color: #0453cb; cursor: pointer;
    transition: all .2s ease;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .82rem;
}
.ec-icon-btn:hover { background: #eff6ff; border-color: #0453cb; transform: translateY(-1px); }
.ec-icon-btn:disabled { background: #f8fafc; color: #cbd5e1; cursor: not-allowed; transform: none; border-color: #e2e8f0; }

.ec-info {
    display: flex; align-items: flex-start; gap: .5rem;
    background: #eff6ff;
    border-left: 3px solid #0453cb;
    padding: .65rem .85rem;
    border-radius: 8px;
    color: #1e293b;
    font-size: .8rem; line-height: 1.5;
    margin-top: .55rem;
}
.ec-info i { color: #0453cb; flex-shrink: 0; margin-top: .15rem; }
.ec-info--tip { background: #fffbeb; border-left-color: #f59e0b; margin-top: 1rem; }
.ec-info--tip i { color: #f59e0b; }
.ec-info--success { background: #ecfdf5; border-left-color: #10b981; }
.ec-info--success i { color: #10b981; }
.ec-info--warning { background: #fffbeb; border-left-color: #f59e0b; }
.ec-info--warning i { color: #f59e0b; }

.ec-link { color: #0453cb; font-weight: 600; text-decoration: underline; }
.ec-link:hover { color: #033a8e; }

.ec-switch {
    display: flex; align-items: flex-start; gap: .85rem;
    padding: 1rem 1.15rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    margin-top: 1rem;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.ec-switch:hover { border-color: #cbd5e1; background: #f1f5f9; }
.ec-switch input[type="checkbox"] {
    position: absolute; opacity: 0; pointer-events: none;
    width: 0; height: 0;
}
.ec-switch-toggle {
    width: 40px; height: 22px;
    background: #cbd5e1; border-radius: 999px;
    position: relative;
    transition: background .2s ease;
    flex-shrink: 0; margin-top: 2px;
}
.ec-switch-toggle::before {
    content: ''; position: absolute;
    top: 2px; left: 2px;
    width: 18px; height: 18px;
    background: #fff; border-radius: 50%;
    transition: transform .2s ease;
    box-shadow: 0 1px 3px rgba(15,23,42,.2);
}
.ec-switch input[type="checkbox"]:checked + .ec-switch-toggle {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
}
.ec-switch input[type="checkbox"]:checked + .ec-switch-toggle::before {
    transform: translateX(18px);
}
.ec-switch input[type="checkbox"]:focus-visible + .ec-switch-toggle {
    box-shadow: 0 0 0 3px rgba(4,83,203,.20);
}
.ec-switch-text {
    display: flex; flex-direction: column; gap: .15rem;
    flex: 1; min-width: 0;
}
.ec-switch-title { font-size: .9rem; font-weight: 600; color: #0f172a; }
.ec-switch-desc { color: #64748b; font-size: .78rem; line-height: 1.45; }

.ec-actions {
    display: flex; gap: .75rem;
    justify-content: flex-end;
    padding: 1rem 0;
    margin-top: .5rem;
}

.ec-toast-container {
    position: fixed;
    top: 20px; right: 20px;
    z-index: 2000;
    display: flex; flex-direction: column;
    gap: .5rem;
    pointer-events: none;
}
.ec-toast {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #0453cb;
    border-radius: 12px;
    padding: .85rem 1.1rem;
    box-shadow: 0 12px 40px rgba(15,23,42,.12), 0 4px 12px rgba(15,23,42,.06);
    display: flex; align-items: flex-start; gap: .75rem;
    max-width: 380px;
    pointer-events: auto;
    transition: opacity .25s ease, transform .25s ease;
}
.ec-toast--error { border-left-color: #dc2626; }
.ec-toast--error .ec-toast-icon { color: #dc2626; }
.ec-toast--warning { border-left-color: #f59e0b; }
.ec-toast--warning .ec-toast-icon { color: #f59e0b; }
.ec-toast--success { border-left-color: #10b981; }
.ec-toast--success .ec-toast-icon { color: #10b981; }
.ec-toast-icon { color: #0453cb; font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
.ec-toast-body { flex: 1; min-width: 0; }
.ec-toast-title { font-weight: 600; color: #0f172a; font-size: .88rem; margin-bottom: .15rem; }
.ec-toast-message { color: #475569; font-size: .82rem; line-height: 1.4; }
.ec-toast-close {
    background: transparent; border: none;
    color: #94a3b8; cursor: pointer; padding: 0;
    font-size: .85rem; flex-shrink: 0;
}
.ec-toast-close:hover { color: #0f172a; }

@media (max-width: 768px) {
    .ec-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .ec-hero h1 { font-size: 1.2rem; }
    .ec-card-header { padding: .85rem 1rem; }
    .ec-card-body { padding: 1rem 1rem 1.25rem; }
    .ec-grid { grid-template-columns: 1fr; }
    .ec-actions { flex-direction: column; }
    .ec-actions .ec-btn { width: 100%; justify-content: center; }
    .ec-toast-container { top: 10px; right: 10px; left: 10px; }
    .ec-toast { max-width: none; }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('evaluationCreateForm');
        if (!form) return;

        var matieresJson = [];
        try {
            matieresJson = JSON.parse(form.dataset.matieresJson || '[]');
        } catch (e) {
            matieresJson = [];
        }
        var loadMatieresUrl = form.dataset.loadMatieresUrl;
        var coeffCheckUrl = form.dataset.coeffCheckUrl;

        var classeSelect = document.getElementById('classe_id');
        var matiereSelect = document.getElementById('matiere_id');
        var coeffInput = document.getElementById('coefficient');
        var submitBtn = document.getElementById('evaluation-submit');
        var matiereLoading = document.getElementById('matiere-loading');
        var btnUseMatiereCoefficient = document.getElementById('btn-use-matiere-coefficient');
        var coeffInfoDiv = document.getElementById('coeff-matiere-info');
        var coeffInfoText = document.getElementById('coeff-matiere-info-text');
        var toastContainer = document.getElementById('ec-toast-container');

        function toast(opts) {
            opts = opts || {};
            if (!toastContainer) return;
            var type = opts.type || 'info';
            var icons = { error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', success: 'fa-check-circle', info: 'fa-info-circle' };
            var t = document.createElement('div');
            t.className = 'ec-toast ec-toast--' + type;
            t.innerHTML =
                '<i class="fas ' + icons[type] + ' ec-toast-icon"></i>' +
                '<div class="ec-toast-body">' +
                    (opts.title ? '<div class="ec-toast-title">' + opts.title + '</div>' : '') +
                    '<div class="ec-toast-message"></div>' +
                '</div>' +
                '<button type="button" class="ec-toast-close" aria-label="Fermer">' +
                    '<i class="fas fa-times"></i>' +
                '</button>';
            // Inject message safely via textContent (no XSS from server data)
            t.querySelector('.ec-toast-message').textContent = opts.message || '';
            toastContainer.appendChild(t);
            var close = function() {
                t.style.opacity = '0';
                t.style.transform = 'translateX(20px)';
                setTimeout(function() { t.remove(); }, 250);
            };
            t.querySelector('.ec-toast-close').addEventListener('click', close);
            setTimeout(close, opts.duration || 5000);
        }

        // Cascade classe → matières
        if (classeSelect && matiereSelect) {
            classeSelect.addEventListener('change', function() {
                var classeId = this.value;
                matiereSelect.innerHTML = '<option value="" data-placeholder="1">Sélectionner une matière</option>';
                matiereSelect.disabled = true;
                if (classeId) loadMatieres(classeId);
            });
            matiereSelect.addEventListener('change', checkCombinationCoefficient);
        }

        function loadMatieres(classeId) {
            if (matiereLoading) matiereLoading.style.display = 'inline-block';
            var url = loadMatieresUrl + '?classe_id=' + encodeURIComponent(classeId);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function(data) {
                    if (matiereLoading) matiereLoading.style.display = 'none';
                    if (!data.success) {
                        toast({ type: 'error', title: 'Erreur', message: data.message || 'Impossible de charger les matières.' });
                        matiereSelect.disabled = false;
                        return;
                    }
                    matiereSelect.innerHTML = data.options;
                    matiereSelect.disabled = false;
                    if (data.count === 0) {
                        matiereSelect.innerHTML = '<option value="" data-placeholder="1">Aucune matière disponible</option>';
                        toast({
                            type: 'warning',
                            title: 'Aucune matière',
                            message: 'La combinaison ' + (data.classe ? data.classe.filiere : '') + ' / ' + (data.classe ? data.classe.niveau : '') + " n'a aucune matière configurée."
                        });
                    } else if (matiereSelect.value) {
                        checkCombinationCoefficient();
                    }
                })
                .catch(function(err) {
                    if (matiereLoading) matiereLoading.style.display = 'none';
                    toast({ type: 'error', title: 'Erreur réseau', message: err.message || 'Impossible de charger les matières.' });
                    matiereSelect.disabled = false;
                });
        }

        function checkCombinationCoefficient() {
            if (!classeSelect || !matiereSelect) return;
            var classeId = classeSelect.value;
            var matiereId = matiereSelect.value;
            if (!classeId || !matiereId) {
                if (coeffInfoDiv) coeffInfoDiv.style.display = 'none';
                return;
            }
            fetch(coeffCheckUrl + '?classe_id=' + encodeURIComponent(classeId) + '&matiere_id=' + encodeURIComponent(matiereId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (coeffInput) coeffInput.value = data.coefficient;
                        if (coeffInfoDiv && coeffInfoText) {
                            coeffInfoDiv.className = 'ec-info ec-info--success';
                            coeffInfoDiv.style.display = 'flex';
                            coeffInfoText.textContent = 'Coefficient matière pré-rempli : ' + data.coefficient + ' (modifiable).';
                        }
                    } else {
                        if (coeffInput && (!coeffInput.value || coeffInput.value <= 0)) coeffInput.value = 1;
                        if (coeffInfoDiv && coeffInfoText) {
                            coeffInfoDiv.className = 'ec-info ec-info--warning';
                            coeffInfoDiv.style.display = 'flex';
                            // Build content safely
                            coeffInfoText.textContent = 'Aucun coefficient matière configuré. Saisissez-le manuellement.';
                            if (data.config_url) {
                                var link = document.createElement('a');
                                link.href = data.config_url;
                                link.className = 'ec-link';
                                link.textContent = ' Configurer';
                                coeffInfoText.appendChild(link);
                            }
                        }
                    }
                    if (submitBtn) submitBtn.disabled = false;
                })
                .catch(function() {
                    if (submitBtn) submitBtn.disabled = false;
                    if (coeffInfoDiv) coeffInfoDiv.style.display = 'none';
                });
        }

        // Sync coefficient depuis la matière
        if (btnUseMatiereCoefficient && coeffInput && matiereSelect) {
            btnUseMatiereCoefficient.addEventListener('click', function() {
                if (!matiereSelect.value || (classeSelect && !classeSelect.value)) {
                    toast({ type: 'warning', message: 'Sélectionnez d’abord une classe et une matière.' });
                    return;
                }
                var matiere = matieresJson.find(function(m) { return String(m.id) === String(matiereSelect.value); });
                if (matiere && matiere.coefficient) {
                    coeffInput.value = matiere.coefficient;
                    var originalHtml = btnUseMatiereCoefficient.innerHTML;
                    btnUseMatiereCoefficient.innerHTML = '<i class="fas fa-check"></i>';
                    btnUseMatiereCoefficient.style.color = '#10b981';
                    btnUseMatiereCoefficient.style.borderColor = '#10b981';
                    setTimeout(function() {
                        btnUseMatiereCoefficient.innerHTML = originalHtml;
                        btnUseMatiereCoefficient.style.color = '';
                        btnUseMatiereCoefficient.style.borderColor = '';
                    }, 1500);
                } else {
                    toast({ type: 'warning', message: 'Cette matière n’a pas de coefficient défini.' });
                }
            });

            matiereSelect.addEventListener('change', function() {
                var matiere = matieresJson.find(function(m) { return String(m.id) === String(matiereSelect.value); });
                if (matiere && matiere.coefficient) {
                    btnUseMatiereCoefficient.title = 'Reprendre le coefficient de la matière (' + matiere.coefficient + ')';
                    btnUseMatiereCoefficient.disabled = false;
                } else {
                    btnUseMatiereCoefficient.title = 'Aucun coefficient défini pour cette matière';
                    btnUseMatiereCoefficient.disabled = true;
                }
            });
        }
    });
})();
</script>
@endpush
