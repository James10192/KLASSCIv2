@extends('layouts.app')

@section('title', 'Évaluation : ' . $evaluation->titre . ' - KLASSCI')

@php
    use Carbon\Carbon;

    $startAt = $evaluation->date_evaluation ? Carbon::parse($evaluation->date_evaluation) : null;
    $endAt = null;
    if ($startAt) {
        $duration = $evaluation->duree_minutes ?? 0;
        $endAt = $startAt->copy()->addMinutes($duration > 0 ? $duration : 120);
    }
    $durationMinutes = (int) ($evaluation->duree_minutes ?? 0);
    $durationLabel = null;
    if ($durationMinutes > 0) {
        $hours = intdiv($durationMinutes, 60);
        $minutes = $durationMinutes % 60;
        if ($hours > 0) {
            $durationLabel = $minutes > 0
                ? $hours . 'H' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT)
                : $hours . 'H';
        } else {
            $durationLabel = $minutes . ' mn';
        }
    }
    $classe = $evaluation->classe;
    $matiere = $evaluation->matiere;
    $enseignant = $evaluation->enseignant;
    $enseignantNom = $enseignant
        ? ($enseignant->name ?? $enseignant->full_name ?? $enseignant->email)
        : ($evaluation->enseignant_externe_nom ?: 'Non défini');

    $typeIcons = [
        'examen' => 'fa-file-alt',
        'devoir' => 'fa-pencil-alt',
        'tp' => 'fa-flask',
        'projet' => 'fa-project-diagram',
        'controle' => 'fa-tasks',
        'rattrapage' => 'fa-redo',
        'quiz' => 'fa-question-circle',
        'oral' => 'fa-comments',
        'cc' => 'fa-clipboard-check',
    ];
    $typeIcon = $typeIcons[$evaluation->type] ?? 'fa-file-alt';

    $evaluationDateFuture = $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture();
    $notesDisabled = !$evaluation->is_published || $evaluationDateFuture;
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        {{-- Hero gradient avec les 4 KPIs intégrés (pattern planning-header) --}}
        <div class="ev-hero">
            <div class="ev-hero-top">
                <div class="ev-hero-left">
                    <div class="ev-hero-icon"><i class="fas {{ $typeIcon }}"></i></div>
                    <div class="ev-hero-titles">
                        <h1>{{ $evaluation->titre }}</h1>
                        <p>
                            <i class="fas fa-book me-1"></i>{{ $matiere->name ?? $matiere->nom ?? 'Matière non définie' }}
                            <span class="ev-hero-sep">·</span>
                            <i class="fas fa-users me-1"></i>{{ $classe->name ?? $classe->libelle ?? 'Classe non définie' }}
                        </p>
                    </div>
                </div>
                <div class="ev-hero-actions">
                    <span class="ev-chip">
                        <i class="fas fa-tag"></i>
                        {{ ucfirst($evaluation->type) }}
                    </span>
                    @if($evaluation->is_published)
                        <span class="ev-chip ev-chip--success">
                            <i class="fas fa-check-circle"></i> Publiée
                        </span>
                    @else
                        <span class="ev-chip ev-chip--muted">
                            <i class="fas fa-eye-slash"></i> Brouillon
                        </span>
                    @endif
                    <a href="{{ route('esbtp.evaluations.index') }}" class="ev-btn ev-btn--glass">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    @if($evaluation->isEditable())
                        <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="ev-btn ev-btn--white">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                    @endif
                </div>
            </div>

            <div class="ev-kpis">
                <div class="ev-kpi">
                    <div class="ev-kpi-icon"><i class="far fa-calendar-alt"></i></div>
                    <div class="ev-kpi-body">
                        <div class="ev-kpi-label">Début</div>
                        <div class="ev-kpi-value">{{ $startAt?->format('d/m/Y') ?? '—' }}</div>
                        <div class="ev-kpi-sub"><i class="far fa-clock me-1"></i>{{ $startAt?->format('H:i') ?? '--:--' }}</div>
                    </div>
                </div>

                <div class="ev-kpi">
                    <div class="ev-kpi-icon"><i class="far fa-calendar-check"></i></div>
                    <div class="ev-kpi-body">
                        <div class="ev-kpi-label">Fin estimée</div>
                        <div class="ev-kpi-value">{{ $endAt?->format('d/m/Y') ?? '—' }}</div>
                        <div class="ev-kpi-sub"><i class="far fa-clock me-1"></i>{{ $endAt?->format('H:i') ?? '--:--' }}</div>
                    </div>
                </div>

                <div class="ev-kpi">
                    <div class="ev-kpi-icon"><i class="fas fa-stopwatch"></i></div>
                    <div class="ev-kpi-body">
                        <div class="ev-kpi-label">Durée</div>
                        <div class="ev-kpi-value">{{ $durationLabel ?? '—' }}</div>
                        <div class="ev-kpi-sub">{{ $durationMinutes > 0 ? $durationMinutes . ' minutes' : 'Non définie' }}</div>
                    </div>
                </div>

                <div class="ev-kpi">
                    <div class="ev-kpi-icon"><i class="fas fa-scale-balanced"></i></div>
                    <div class="ev-kpi-body">
                        <div class="ev-kpi-label">Coefficient / Barème</div>
                        <div class="ev-kpi-value">
                            {{ rtrim(rtrim(number_format($evaluation->coefficient, 1, '.', ''), '0'), '.') }}
                            <span class="ev-kpi-divider">/</span>
                            {{ rtrim(rtrim(number_format($evaluation->bareme, 1, '.', ''), '0'), '.') }}
                        </div>
                        <div class="ev-kpi-sub">Pondération</div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="ev-alert ev-alert--success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="ev-alert ev-alert--error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="ev-layout">
            {{-- Colonne principale --}}
            <div class="ev-main">
                {{-- Informations générales --}}
                <div class="ev-card">
                    <div class="ev-card-header">
                        <div class="ev-section-icon"><i class="fas fa-info-circle"></i></div>
                        <h2 class="ev-card-title">Informations générales</h2>
                    </div>
                    <div class="ev-card-body">
                        <div class="ev-info-grid">
                            <div class="ev-info-item">
                                <span class="ev-info-label">Type</span>
                                <span class="ev-info-value">
                                    <i class="fas {{ $typeIcon }} ev-info-icon"></i>
                                    {{ ucfirst($evaluation->type) }}
                                </span>
                            </div>

                            <div class="ev-info-item">
                                <span class="ev-info-label">Classe</span>
                                <span class="ev-info-value">
                                    <i class="fas fa-users ev-info-icon"></i>
                                    {{ $classe->name ?? $classe->libelle ?? '—' }}
                                </span>
                            </div>

                            <div class="ev-info-item">
                                <span class="ev-info-label">Matière</span>
                                <span class="ev-info-value">
                                    <i class="fas fa-book ev-info-icon"></i>
                                    {{ $matiere->name ?? $matiere->nom ?? '—' }}
                                </span>
                            </div>

                            <div class="ev-info-item">
                                <span class="ev-info-label">Enseignant</span>
                                <span class="ev-info-value">
                                    <i class="fas fa-chalkboard-teacher ev-info-icon"></i>
                                    {{ $enseignantNom }}
                                </span>
                            </div>

                            <div class="ev-info-item">
                                <span class="ev-info-label">Publication</span>
                                <span class="ev-info-value">
                                    @if($evaluation->is_published)
                                        <span class="ev-badge ev-badge--success"><i class="fas fa-check-circle"></i> Publiée</span>
                                    @else
                                        <span class="ev-badge ev-badge--muted"><i class="fas fa-eye-slash"></i> Non publiée</span>
                                    @endif
                                </span>
                            </div>

                            <div class="ev-info-item">
                                <span class="ev-info-label">Notes</span>
                                <span class="ev-info-value">
                                    @if($evaluation->notes_published)
                                        <span class="ev-badge ev-badge--success"><i class="fas fa-check-circle"></i> Visibles</span>
                                    @else
                                        <span class="ev-badge ev-badge--muted"><i class="fas fa-eye-slash"></i> Masquées</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description (conditionnelle) --}}
                @if($evaluation->description)
                    <div class="ev-card">
                        <div class="ev-card-header">
                            <div class="ev-section-icon"><i class="fas fa-align-left"></i></div>
                            <h2 class="ev-card-title">Description</h2>
                        </div>
                        <div class="ev-card-body">
                            <p class="ev-description">{{ $evaluation->description }}</p>
                        </div>
                    </div>
                @endif

                {{-- Notes des étudiants --}}
                <div class="ev-card">
                    <div class="ev-card-header">
                        <div class="ev-section-icon"><i class="fas fa-list-alt"></i></div>
                        <h2 class="ev-card-title">Notes des étudiants</h2>
                        @if($notesAnneeCourante->isNotEmpty())
                            <span class="ev-count">{{ $notesAnneeCourante->count() }}</span>
                        @endif
                    </div>
                    <div class="ev-card-body ev-card-body--flush">
                        @if($notesAnneeCourante->isNotEmpty())
                            <div class="ev-table-wrap">
                                <table class="ev-table">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th class="ev-th-center">Note</th>
                                            <th class="ev-th-center">Sur</th>
                                            <th class="ev-th-center">Coefficient</th>
                                            <th class="ev-th-center">Note finale</th>
                                            <th class="ev-th-center">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($notesAnneeCourante as $note)
                                            @php
                                                $percentage = $evaluation->bareme > 0
                                                    ? ($note->note / $evaluation->bareme) * 100
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td class="ev-td-name">{{ $note->etudiant->nom_complet }}</td>
                                                <td class="ev-td-center">{{ number_format($note->note, 2) }}</td>
                                                <td class="ev-td-center ev-td-muted">{{ number_format($evaluation->bareme, 2) }}</td>
                                                <td class="ev-td-center ev-td-muted">{{ number_format($evaluation->coefficient, 2) }}</td>
                                                <td class="ev-td-center ev-td-bold">
                                                    {{ number_format($note->note * $evaluation->coefficient, 2) }}
                                                </td>
                                                <td class="ev-td-center">
                                                    @if($note->is_absent)
                                                        <span class="ev-badge ev-badge--muted">Absent</span>
                                                    @elseif($percentage >= 60)
                                                        <span class="ev-badge ev-badge--success">Réussi</span>
                                                    @elseif($percentage >= 40)
                                                        <span class="ev-badge ev-badge--warning">Moyen</span>
                                                    @else
                                                        <span class="ev-badge ev-badge--danger">Échec</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="ev-empty">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Aucune note n'a encore été saisie pour cette évaluation.</p>
                                @unless($notesDisabled)
                                    <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="ev-btn ev-btn--primary">
                                        <i class="fas fa-pen-to-square"></i> Saisir les notes
                                    </a>
                                @endunless
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Colonne latérale --}}
            <div class="ev-sidebar">
                <div class="ev-card">
                    <div class="ev-card-header">
                        <div class="ev-section-icon"><i class="fas fa-bolt"></i></div>
                        <h2 class="ev-card-title">Actions rapides</h2>
                    </div>
                    <div class="ev-card-body ev-card-body--stack">
                        <a href="{{ $notesDisabled ? '#' : route('esbtp.notes.saisie-rapide', $evaluation) }}"
                           class="ev-btn ev-btn--primary ev-btn--block {{ $notesDisabled ? 'ev-btn--disabled' : '' }}"
                           @if($notesDisabled) aria-disabled="true" tabindex="-1" title="{{ $evaluationDateFuture ? 'Disponible après la date d\'évaluation' : 'Publiez l\'évaluation pour saisir les notes' }}" @endif>
                            <i class="fas fa-pen-to-square"></i> Gérer les notes
                        </a>

                        @if($evaluation->isEditable())
                            <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="ev-btn ev-btn--ghost ev-btn--block">
                                <i class="fas fa-edit"></i> Modifier l'évaluation
                            </a>
                        @endif

                        <form action="{{ route('esbtp.evaluations.toggle-published', $evaluation) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="ev-btn ev-btn--block {{ $evaluation->is_published ? 'ev-btn--ghost' : 'ev-btn--success' }}">
                                <i class="fas {{ $evaluation->is_published ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                {{ $evaluation->is_published ? 'Masquer l\'évaluation' : 'Publier l\'évaluation' }}
                            </button>
                        </form>

                        <form action="{{ route('esbtp.evaluations.toggle-notes-published', $evaluation) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="ev-btn ev-btn--block {{ $evaluation->notes_published ? 'ev-btn--ghost' : 'ev-btn--success' }}"
                                    {{ !$evaluation->canPublishNotes() && !$evaluation->notes_published ? 'disabled' : '' }}>
                                <i class="fas {{ $evaluation->notes_published ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                {{ $evaluation->notes_published ? 'Masquer les notes' : 'Publier les notes' }}
                            </button>
                        </form>

                        <x-pdf-actions
                            :preview-url="route('esbtp.evaluations.pdf-preview', $evaluation)"
                            :download-url="route('esbtp.evaluations.pdf', $evaluation)"
                            label="Évaluation"
                            buttonClass="ev-btn ev-btn--ghost ev-btn--block" />

                        @if($evaluation->isDeletable())
                            <button type="button" class="ev-btn ev-btn--danger ev-btn--block" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="fas fa-trash-alt"></i> Supprimer l'évaluation
                            </button>
                        @endif
                    </div>
                </div>

                @if($notesAnneeCourante->isNotEmpty())
                    <div class="ev-card">
                        <div class="ev-card-header">
                            <div class="ev-section-icon"><i class="fas fa-chart-bar"></i></div>
                            <h2 class="ev-card-title">Statistiques</h2>
                        </div>
                        <div class="ev-card-body">
                            <div class="ev-stat-hero">
                                <span class="ev-stat-label">Moyenne de classe</span>
                                <span class="ev-stat-value">{{ number_format($notesAnneeCourante->avg('note'), 2) }} / {{ rtrim(rtrim(number_format($evaluation->bareme, 1, '.', ''), '0'), '.') }}</span>
                            </div>

                            <div class="ev-stat-row">
                                <div class="ev-stat-card">
                                    <span class="ev-stat-card-label">Note max</span>
                                    <span class="ev-stat-card-value ev-stat-card-value--success">{{ number_format($notesAnneeCourante->max('note'), 2) }}</span>
                                </div>
                                <div class="ev-stat-card">
                                    <span class="ev-stat-card-label">Note min</span>
                                    <span class="ev-stat-card-value ev-stat-card-value--danger">{{ number_format($notesAnneeCourante->min('note'), 2) }}</span>
                                </div>
                            </div>

                            <div class="ev-distribution">
                                <h6 class="ev-distribution-title">Répartition des notes</h6>
                                @php
                                    $ranges = [
                                        ['min' => 0, 'max' => 5, 'class' => 'ev-progress-bar--danger'],
                                        ['min' => 5, 'max' => 10, 'class' => 'ev-progress-bar--warning'],
                                        ['min' => 10, 'max' => 15, 'class' => 'ev-progress-bar--info'],
                                        ['min' => 15, 'max' => 21, 'class' => 'ev-progress-bar--success'],
                                    ];
                                    $total = $notesAnneeCourante->count();
                                @endphp
                                <div class="ev-progress">
                                    @foreach($ranges as $range)
                                        @php
                                            $count = $notesAnneeCourante->filter(function ($note) use ($range) {
                                                return $note->note >= $range['min'] && $note->note < $range['max'];
                                            })->count();
                                            $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                        @endphp
                                        @if($percentage > 0)
                                            <div class="ev-progress-bar {{ $range['class'] }}"
                                                 style="width: {{ $percentage }}%"
                                                 title="{{ $count }} note(s) entre {{ $range['min'] }} et {{ $range['max'] }}"></div>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="ev-distribution-scale">
                                    <span>0</span><span>5</span><span>10</span><span>15</span><span>20</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal suppression --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ev-modal">
            <div class="modal-header ev-modal-header">
                <h5 class="modal-title"><i class="fas fa-triangle-exclamation me-2"></i> Confirmer la suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'évaluation <strong>« {{ $evaluation->titre }} »</strong> ?</p>
                <div class="ev-alert ev-alert--error" style="margin: 0">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><strong>Attention :</strong> cette action est irréversible. Toutes les notes saisies seront également supprimées.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="ev-btn ev-btn--ghost" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <form action="{{ route('esbtp.evaluations.destroy', $evaluation) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ev-btn ev-btn--danger">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ============================================================
   ev-* — Namespace évaluations.show (premium KLASSCI)
   ============================================================ */
.ev-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.ev-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.ev-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.ev-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.ev-hero-titles { min-width: 0; }
.ev-hero h1 {
    font-size: 1.45rem; font-weight: 700; color: #fff;
    margin: 0; line-height: 1.25;
}
.ev-hero p {
    color: rgba(255,255,255,.78); font-size: .88rem; margin: .25rem 0 0;
    display: flex; align-items: center; flex-wrap: wrap; gap: .4rem;
}
.ev-hero-sep { opacity: .5; }

.ev-hero-actions {
    display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
}

.ev-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff; font-size: .78rem; font-weight: 600;
    padding: .35rem .7rem; border-radius: 999px;
    text-transform: capitalize;
}
.ev-chip i { font-size: .72rem; }
.ev-chip--success { background: rgba(16,185,129,.2); border-color: rgba(16,185,129,.4); }
.ev-chip--muted { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15); opacity: .85; }

.ev-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    padding: .55rem 1rem; border-radius: 10px;
    font-size: .85rem; font-weight: 600;
    cursor: pointer; border: 1px solid transparent;
    transition: all .2s ease; text-decoration: none;
    line-height: 1.2;
    background: transparent;
}
.ev-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border-color: rgba(255,255,255,.2);
}
.ev-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
.ev-btn--white {
    background: #fff; color: #0453cb;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(15,23,42,.10);
}
.ev-btn--white:hover { color: #033a8e; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(15,23,42,.15); }
.ev-btn--primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}
.ev-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(4,83,203,.35); color: #fff; }
.ev-btn--ghost {
    background: #fff; color: #475569;
    border-color: #e2e8f0;
}
.ev-btn--ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
.ev-btn--success {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(16,185,129,.25);
}
.ev-btn--success:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(16,185,129,.35); color: #fff; }
.ev-btn--danger {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(220,38,38,.25);
}
.ev-btn--danger:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(220,38,38,.35); color: #fff; }
.ev-btn--block { width: 100%; }
.ev-btn--disabled, .ev-btn:disabled {
    opacity: .55; cursor: not-allowed; pointer-events: none;
    transform: none; box-shadow: none;
}

.ev-kpis {
    display: flex; gap: .75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.ev-kpi {
    flex: 1; min-width: 160px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .85rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.ev-kpi-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
}
.ev-kpi-body { flex: 1; min-width: 0; }
.ev-kpi-label { font-size: .68rem; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
.ev-kpi-value { font-size: 1.15rem; font-weight: 700; color: #fff; line-height: 1.2; margin-top: .15rem; }
.ev-kpi-divider { color: rgba(255,255,255,.4); margin: 0 .15rem; font-weight: 400; }
.ev-kpi-sub { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

.ev-alert {
    display: flex; align-items: center; gap: .75rem;
    padding: .85rem 1.1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border-left: 4px solid;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    font-size: .85rem; line-height: 1.5;
}
.ev-alert i:first-child { font-size: 1.1rem; flex-shrink: 0; }
.ev-alert--success { background: #ecfdf5; color: #065f46; border-left-color: #10b981; }
.ev-alert--success i:first-child { color: #10b981; }
.ev-alert--error { background: #fef2f2; color: #991b1b; border-left-color: #dc2626; }
.ev-alert--error i:first-child { color: #dc2626; }

.ev-layout {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
    gap: 1.25rem;
}
.ev-main, .ev-sidebar { display: grid; gap: 1.25rem; align-content: start; }

.ev-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    transition: box-shadow .2s ease;
    overflow: hidden;
}
.ev-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.06), 0 2px 8px rgba(15,23,42,.04);
}
.ev-card-header {
    display: flex; align-items: center; gap: .85rem;
    padding: 1.05rem 1.4rem;
    border-bottom: 1px solid #f1f5f9;
}
.ev-section-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .92rem; flex-shrink: 0;
}
.ev-card-title { font-size: 1.02rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2; flex: 1; }
.ev-count {
    background: #eff6ff; color: #0453cb;
    font-size: .78rem; font-weight: 700;
    padding: .25rem .65rem; border-radius: 999px;
}
.ev-card-body { padding: 1.2rem 1.4rem 1.35rem; }
.ev-card-body--flush { padding: 0; }
.ev-card-body--stack { display: grid; gap: .6rem; }

.ev-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem 1.25rem;
}
.ev-info-item { display: flex; flex-direction: column; gap: .3rem; min-width: 0; }
.ev-info-label {
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; color: #64748b;
    letter-spacing: .5px;
}
.ev-info-value {
    font-size: .95rem; font-weight: 600; color: #0f172a;
    display: inline-flex; align-items: center; gap: .45rem;
    line-height: 1.4;
}
.ev-info-icon { color: #0453cb; font-size: .85rem; }

.ev-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .72rem; font-weight: 600;
    padding: .25rem .6rem; border-radius: 999px;
    border: 1px solid;
}
.ev-badge--success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.ev-badge--muted { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.ev-badge--warning { background: #fffbeb; color: #92400e; border-color: #fcd34d; }
.ev-badge--danger { background: #fef2f2; color: #991b1b; border-color: #fca5a5; }
.ev-badge i { font-size: .68rem; }

.ev-description { margin: 0; color: #1e293b; font-size: .92rem; line-height: 1.6; white-space: pre-wrap; }

.ev-table-wrap { overflow-x: auto; }
.ev-table {
    width: 100%; border-collapse: collapse;
    font-size: .88rem;
}
.ev-table thead {
    background: #f8fafc;
}
.ev-table thead th {
    padding: .7rem 1rem;
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; color: #475569;
    letter-spacing: .5px; text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.ev-th-center { text-align: center !important; }
.ev-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.ev-table tbody tr:last-child { border-bottom: none; }
.ev-table tbody tr:hover { background: #f8fafc; }
.ev-table tbody td { padding: .75rem 1rem; color: #1e293b; }
.ev-td-name { font-weight: 600; }
.ev-td-center { text-align: center; }
.ev-td-muted { color: #64748b; }
.ev-td-bold { font-weight: 700; color: #0453cb; }

.ev-empty {
    padding: 2.5rem 1.5rem;
    text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: .85rem;
    color: #64748b;
}
.ev-empty i { font-size: 2rem; color: #cbd5e1; }
.ev-empty p { margin: 0; font-size: .88rem; }

.ev-stat-hero {
    text-align: center; padding: 1rem;
    background: linear-gradient(135deg, #eff6ff, #f0f9ff);
    border: 1px solid #dbeafe;
    border-radius: 12px;
    margin-bottom: 1rem;
    display: flex; flex-direction: column; gap: .3rem;
}
.ev-stat-label {
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; color: #64748b;
    letter-spacing: .5px;
}
.ev-stat-value {
    font-size: 1.6rem; font-weight: 800;
    color: #0453cb;
    font-feature-settings: 'tnum' 1;
}
.ev-stat-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .65rem;
    margin-bottom: 1.1rem;
}
.ev-stat-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .65rem .85rem;
    display: flex; flex-direction: column; gap: .15rem;
}
.ev-stat-card-label {
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; color: #64748b;
    letter-spacing: .5px;
}
.ev-stat-card-value { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
.ev-stat-card-value--success { color: #10b981; }
.ev-stat-card-value--danger { color: #dc2626; }

.ev-distribution-title {
    font-size: .78rem; font-weight: 700;
    text-transform: uppercase; color: #64748b;
    letter-spacing: .5px;
    margin: 0 0 .55rem;
}
.ev-progress {
    display: flex;
    height: 12px;
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
    box-shadow: inset 0 1px 2px rgba(15,23,42,.06);
}
.ev-progress-bar { height: 100%; transition: width .3s ease; }
.ev-progress-bar--danger { background: linear-gradient(90deg, #dc2626, #ef4444); }
.ev-progress-bar--warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.ev-progress-bar--info { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.ev-progress-bar--success { background: linear-gradient(90deg, #10b981, #34d399); }
.ev-distribution-scale {
    display: flex; justify-content: space-between;
    margin-top: .35rem;
    font-size: .7rem; color: #94a3b8;
}

/* Modal premium */
.ev-modal { border: none; border-radius: 14px; overflow: hidden; box-shadow: 0 20px 60px rgba(15,23,42,.2); }
.ev-modal-header {
    background: linear-gradient(135deg, #991b1b, #dc2626);
    color: #fff; border: none; padding: 1rem 1.25rem;
}
.ev-modal-header .modal-title { font-size: 1rem; font-weight: 700; color: #fff; }
.ev-modal .modal-body { padding: 1.25rem; color: #1e293b; }
.ev-modal .modal-body p { font-size: .92rem; line-height: 1.5; }
.ev-modal .modal-footer {
    border: none; padding: 0 1.25rem 1.25rem;
    gap: .5rem;
    display: flex; justify-content: flex-end;
}
.ev-modal .modal-footer form { margin: 0; }

@media (max-width: 1100px) {
    .ev-layout { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .ev-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .ev-hero h1 { font-size: 1.2rem; }
    .ev-hero-top { flex-direction: column; align-items: flex-start; }
    .ev-hero-actions { width: 100%; }
    .ev-card-header { padding: .85rem 1rem; }
    .ev-card-body { padding: 1rem 1rem 1.25rem; }
    .ev-info-grid { grid-template-columns: 1fr; }
    .ev-kpis { gap: .55rem; }
    .ev-kpi { min-width: 140px; }
}
</style>
@endpush
