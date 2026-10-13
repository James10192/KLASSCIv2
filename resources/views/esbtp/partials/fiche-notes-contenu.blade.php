{{-- Données déjà filtrées par fiche, année et classe côté serveur. --}}
<div class="fng-head">
    <div>
        <span class="fng-eyebrow"><i class="fas fa-graduation-cap"></i> Pilotage pédagogique</span>
        <h3>Évaluations et notes</h3>
        <p>Consultez, corrigez ou ajoutez des notes sans quitter cette fiche.</p>
    </div>
    <span class="fng-pill">{{ $blocks->count() }} inscription(s)</span>
</div>
@if($blocks->isEmpty())
    <div class="fng-empty"><i class="fas fa-folder-open"></i> Aucune inscription académique sur cette fiche.</div>
@endif
@foreach($blocks as $block)
    @php
        $ficheInsc = $block['inscription'];
        $evaluationsFiche = $block['evaluations'];
        $canCreateEval = auth()->user()->can('evaluations.create');
        $canEditEval = auth()->user()->can('evaluations.edit');
        $canCreateNote = auth()->user()->can('notes.create');
        $canEditNote = auth()->user()->can('notes.edit');
        $canDeleteNote = auth()->user()->can('notes.delete');
    @endphp
    <section class="fng-block" data-fng-inscription="{{ $ficheInsc->id }}"
        data-student="{{ $student->id }}"
        data-save="{{ route('esbtp.notes.save-ajax') }}"
        data-create-evaluation="{{ route('esbtp.evaluations.store') }}"
        data-quick-base="{{ url('/esbtp/evaluations') }}">
        <div class="fng-block-head">
            <div>
                <strong>{{ $ficheInsc->classe?->name ?? 'Classe non précisée' }}</strong>
                <small>{{ $ficheInsc->anneeUniversitaire?->name ?? 'Année non précisée' }} · {{ ($ficheInsc->classe?->systeme_academique ?? '') === 'LMD' ? 'LMD' : 'BTS' }} · Inscription #{{ $ficheInsc->id }}</small>
            </div>
            <div class="fng-actions">
                @if($canCreateEval && $block['matieres']->isNotEmpty())
                    <button type="button" class="fng-btn fng-btn-primary" data-fng-new-eval><i class="fas fa-plus"></i> Évaluation</button>
                @endif
                @if(auth()->user()->can('bulletins.view'))
                    <a class="fng-btn" data-fng-document target="_blank" rel="noopener" href="{{ route('esbtp.bulletins.index', ['etudiant_id' => $student->id, 'annee_universitaire_id' => $ficheInsc->annee_universitaire_id]) }}"><i class="fas fa-file-alt"></i> Bulletins</a>
                @endif
                @foreach($block['bulletins'] as $bul)
                    <a class="fng-btn" title="Ouvrir le bulletin / relevé {{ $bul->periode }}" data-fng-document target="_blank" rel="noopener"
                        href="{{ route('esbtp.bulletins.preview-pdf', $bul->id) }}"><i class="fas fa-file-pdf"></i> {{ $bul->periode }}</a>
                @endforeach
                @if(($ficheInsc->classe?->systeme_academique ?? '') === 'LMD' && auth()->user()->can('lmd.releve.view'))
                    <a class="fng-btn" data-fng-document target="_blank" rel="noopener" href="{{ route('esbtp.lmd.releves.preview', ['etudiant' => $student->id, 'annee' => $ficheInsc->annee_universitaire_id]) }}"><i class="fas fa-file-pdf"></i> Relevé LMD</a>
                @endif
            </div>
        </div>
        <div class="fng-scroll">
            <table class="fng-table">
                <thead><tr><th>Évaluation / Matière</th><th>Période</th><th>Barème · Coef.</th><th>Note</th><th>Statut</th><th class="fng-right">Actions</th></tr></thead>
                <tbody>
                    @forelse($evaluationsFiche as $eval)
                        @php $studentNote = $eval->notes->first(); @endphp
                        <tr>
                            <td><strong>{{ $eval->titre }}</strong><small>{{ $eval->matiere?->name ?? 'Matière inconnue' }} · {{ $eval->date_evaluation?->format('d/m/Y') ?? 'Sans date' }}</small></td>
                            <td>{{ preg_replace('/^semestre/', 'S', (string) $eval->periode) }}</td>
                            <td>{{ number_format((float)$eval->bareme, 1, ',', ' ') }} · ×{{ number_format((float)$eval->coefficient, 1, ',', ' ') }}</td>
                            <td>
                                @if($studentNote)
                                    <span class="fng-score">{{ $studentNote->is_absent ? 'ABS' : number_format((float)$studentNote->note, 2, ',', ' ') }}</span>
                                @else
                                    <span class="fng-muted">Non saisie</span>
                                @endif
                            </td>
                            <td><span class="fng-status {{ $eval->is_published ? 'fng-status-ok' : '' }}">{{ $eval->is_published ? 'Active' : 'Brouillon' }}</span></td>
                            <td class="fng-right">
                                <div class="fng-actions">
                                    @if(($studentNote ? $canEditNote : $canCreateNote) && $eval->is_published)
                                        <button type="button" class="fng-icon-btn" title="{{ $studentNote ? 'Modifier la note' : 'Saisir une note' }}"
                                            data-fng-note data-evaluation="{{ $eval->id }}"
                                            data-note="{{ $studentNote?->note ?? '' }}" data-absent="{{ $studentNote?->is_absent ? 1 : 0 }}"
                                            data-comment="{{ $studentNote?->commentaire ?? '' }}"
                                            data-bareme="{{ $eval->bareme }}" data-title="{{ $eval->titre }}">
                                            <i class="fas fa-{{ $studentNote ? 'pen' : 'plus' }}"></i>
                                        </button>
                                    @endif
                                    @if($canEditEval)
                                        <button type="button" class="fng-icon-btn" title="{{ $eval->is_published ? 'Désactiver l’évaluation' : 'Activer l’évaluation' }}"
                                            data-fng-publish data-id="{{ $eval->id }}" data-published="{{ $eval->is_published ? 1 : 0 }}">
                                            <i class="fas fa-{{ $eval->is_published ? 'eye-slash' : 'eye' }}"></i>
                                        </button>
                                        <button type="button" class="fng-icon-btn" title="Modifier titre, barème et coefficient" data-fng-edit-eval
                                            data-id="{{ $eval->id }}" data-title="{{ $eval->titre }}" data-bareme="{{ $eval->bareme }}"
                                            data-coefficient="{{ $eval->coefficient }}"><i class="fas fa-sliders-h"></i></button>
                                    @endif
                                    @if($studentNote && $canDeleteNote)
                                        <button type="button" class="fng-icon-btn fng-danger" title="Supprimer cette note" data-fng-delete
                                            data-delete-url="{{ route('esbtp.fiche-notes.supprimer', ['inscription' => $ficheInsc->id, 'note' => $studentNote->id]) }}"
                                            data-title="{{ $eval->titre }}"><i class="fas fa-trash"></i></button>
                                    @endif
                                    <a class="fng-icon-btn" target="_blank" rel="noopener" title="Détails de l'évaluation" href="{{ route('esbtp.evaluations.show', $eval->id) }}"><i class="fas fa-arrow-up-right-from-square"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="fng-empty"><i class="fas fa-clipboard-list"></i> Aucune évaluation sur cette inscription.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($canCreateEval)
            <template data-fng-eval-fields>
                <label>Titre de l'évaluation <input required name="titre" maxlength="255" placeholder="Ex. Devoir de statistiques"></label>
                <div class="fng-grid">
                    <label>Type
                        <select required name="type" class="fng-select">@foreach($typesEvaluation as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                    </label>
                    <label>Semestre
                        <select required name="periode" class="fng-select">@foreach($block['periodes'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                    </label>
                </div>
                <label>Matière
                    <select required name="matiere_id" class="fng-select"><option value="">Choisir une matière</option>@foreach($block['matieres'] as $matiere)<option value="{{ $matiere->id }}">{{ $matiere->name }}</option>@endforeach</select>
                </label>
                <div class="fng-grid">
                    <label>Date <input required type="date" name="date_evaluation" value="{{ now()->toDateString() }}"></label>
                    <label>Début <input required type="time" name="heure_debut" value="08:00"></label>
                    <label>Fin <input required type="time" name="heure_fin" value="10:00"></label>
                </div>
                <div class="fng-grid">
                    <label>Barème <input required type="number" name="bareme" step="0.5" min="1" max="100" value="20"></label>
                    <label>Coefficient <input required type="number" name="coefficient" step="0.1" min="0.1" max="100" value="1"></label>
                </div>
                <input type="hidden" name="classe_id" value="{{ $ficheInsc->classe_id }}">
                <input type="hidden" name="annee_universitaire_id" value="{{ $ficheInsc->annee_universitaire_id }}">
                <input type="hidden" name="embed" value="1">
                <input type="hidden" name="is_published" value="1">
            </template>
        @endif
    </section>
@endforeach
