{{-- Données déjà filtrées par fiche, année et classe côté serveur. --}}
{{-- Le titre est porté par la ligne intégrée à la fiche. Pas de deuxième hero. --}}
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
        $canOpenEvaluation = \App\Support\PorteDeRoute::ouverte('esbtp.evaluations.show', auth()->user());
        $hasWritableNotes = $evaluationsFiche->contains(
            fn ($eval) => $eval->is_published && ($eval->notes->isNotEmpty() ? $canEditNote : $canCreateNote)
        );
    @endphp
    <section class="fng-block" data-fng-inscription="{{ $ficheInsc->id }}"
        data-student="{{ $student->id }}"
        data-save="{{ route('esbtp.notes.save-ajax') }}"
        data-save-bulk="{{ route('esbtp.notes.save-ajax-bulk') }}"
        data-fng-bulk-limit="{{ \App\Http\Requests\Notes\StoreBulkNotesRequest::MAX_NOTES_PER_BATCH }}"
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
                @if(!empty($allowBulk) && $hasWritableNotes)
                    <button type="button" class="fng-btn fng-btn-primary" data-fng-bulk-start><i class="fas fa-table-cells" aria-hidden="true"></i> Saisie multiple</button>
                @endif
                @if(\App\Support\PorteDeRoute::ouverte('esbtp.bulletins.index', auth()->user()))
                    <a class="fng-btn" data-fng-document target="_blank" rel="noopener" href="{{ route('esbtp.bulletins.index', ['etudiant_id' => $student->id, 'annee_universitaire_id' => $ficheInsc->annee_universitaire_id]) }}"><i class="fas fa-file-alt"></i> Bulletins</a>
                @endif
                @if(\App\Support\PorteDeRoute::ouverte('esbtp.bulletins.preview-pdf', auth()->user()))
                @foreach($block['bulletins'] as $bul)
                    <a class="fng-btn" title="Ouvrir le bulletin / relevé {{ $bul->periode }}" data-fng-document target="_blank" rel="noopener"
                        href="{{ route('esbtp.bulletins.preview-pdf', $bul->id) }}"><i class="fas fa-file-pdf"></i> {{ $bul->periode }}</a>
                @endforeach
                @endif
                @if(($ficheInsc->classe?->systeme_academique ?? '') === 'LMD')
                    @can('lmd.releve.view')
                        @if(\App\Support\PorteDeRoute::ouverte('esbtp.lmd.releves.status', auth()->user())
                            && \Illuminate\Support\Facades\Route::has('esbtp.lmd.releves.issue')
                            && \App\Support\PorteDeRoute::ouverte('esbtp.lmd.releves.preview', auth()->user()))
                        <button type="button" class="fng-btn" data-fng-lmd-releve
                            data-status-url="{{ route('esbtp.lmd.releves.status', ['etudiant' => $student->id, 'annee' => $ficheInsc->annee_universitaire_id]) }}"
                            data-issue-url="{{ route('esbtp.lmd.releves.issue', ['etudiant' => $student->id, 'annee' => $ficheInsc->annee_universitaire_id]) }}"
                            data-can-issue="{{ auth()->user()->can('lmd.releve.issue') && \App\Support\PorteDeRoute::ouverte('esbtp.lmd.releves.issue', auth()->user()) ? '1' : '0' }}">
                            <i class="fas fa-file-pdf"></i> Relevé LMD
                        </button>
                        <a hidden data-fng-lmd-preview data-fng-document target="_blank" rel="noopener"
                            href="{{ route('esbtp.lmd.releves.preview', ['etudiant' => $student->id, 'annee' => $ficheInsc->annee_universitaire_id]) }}">Relevé LMD</a>
                        @endif
                    @endcan
                    @if(\App\Support\PorteDeRoute::ouverte('esbtp.lmd.jurys.index', auth()->user()))
                        <a class="fng-btn" target="_blank" rel="noopener"
                            href="{{ route('esbtp.lmd.jurys.index', ['classe_id' => $ficheInsc->classe_id, 'annee_universitaire_id' => $ficheInsc->annee_universitaire_id]) }}"
                            title="Le PV est un document collectif de jury"><i class="fas fa-users"></i> PV du jury</a>
                    @endif
                @else
                    @can('bts.releve.view')
                    @if(\Illuminate\Support\Facades\Route::has('esbtp.bts.releves.preview') && \App\Support\PorteDeRoute::ouverte('esbtp.bts.releves.preview', auth()->user()))
                    <a class="fng-btn" data-fng-document target="_blank" rel="noopener"
                        href="{{ route('esbtp.bts.releves.preview', ['etudiant' => $student->id, 'annee' => $ficheInsc->annee_universitaire_id]) }}"
                        title="Relevé BTS : provisoire depuis les notes si aucun bulletin n'est publié ; sinon interne publié, distinct du relevé national"><i class="fas fa-file-pdf"></i> Relevé BTS</a>
                    @endif
                    @endcan
                    @can('bts.releve.export')
                    @if(\Illuminate\Support\Facades\Route::has('esbtp.bts.releves.download') && \App\Support\PorteDeRoute::ouverte('esbtp.bts.releves.download', auth()->user()))
                    <a class="fng-btn" target="_blank" rel="noopener"
                        href="{{ route('esbtp.bts.releves.download', ['etudiant' => $student->id, 'annee' => $ficheInsc->annee_universitaire_id]) }}"
                        title="Télécharger le relevé BTS : provisoire avant publication, interne publié ensuite"><i class="fas fa-download"></i> PDF</a>
                    @endif
                    @endcan
                @endif
            </div>
        </div>
        @if($evaluationsFiche->isNotEmpty())
            <div class="fng-list-toolbar" aria-label="Organisation des évaluations">
                <div class="fng-view-switch" role="group" aria-label="Mode d'affichage">
                    <button type="button" class="fng-view-btn" data-fng-view="matiere" aria-pressed="true">
                        <i class="fas fa-layer-group" aria-hidden="true"></i> Par matière
                    </button>
                    <button type="button" class="fng-view-btn" data-fng-view="periode" aria-pressed="false">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i> Par semestre
                    </button>
                    <button type="button" class="fng-view-btn" data-fng-view="liste" aria-pressed="false">
                        <i class="fas fa-list" aria-hidden="true"></i> Liste
                    </button>
                </div>
                <label class="fng-search-field">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" data-fng-search placeholder="Matière ou évaluation…" autocomplete="off"
                           aria-label="Filtrer les évaluations par matière ou titre">
                </label>
                <span class="fng-list-count" data-fng-visible-count aria-live="polite"></span>
            </div>
        @endif
        <div class="fng-scroll">
            <table class="fng-table">
                <thead><tr><th>Matière / Évaluation</th><th>Période</th><th>Barème · Coef.</th><th>Note</th><th>Statut</th><th class="fng-right">Actions</th></tr></thead>
                <tbody>
                    @forelse($evaluationsFiche as $eval)
                        @php
                            $studentNote = $eval->notes->first();
                            $canWriteFngNote = $eval->is_published && ($studentNote ? $canEditNote : $canCreateNote);
                        @endphp
                        <tr data-fng-eval-row
                            data-fng-order="{{ $loop->index }}"
                            data-fng-matiere-id="{{ $eval->matiere_id ?? 'inconnue' }}"
                            data-fng-matiere="{{ $eval->matiere?->name ?? 'Matière inconnue' }}"
                            data-fng-titre="{{ $eval->titre }}"
                            data-fng-periode="{{ $eval->periode }}">
                            <td class="fng-eval-identite">
                                <span class="fng-row-matiere">{{ $eval->matiere?->name ?? 'Matière inconnue' }}</span>
                                <strong class="fng-row-evaluation">{{ $eval->titre }}</strong>
                                <small>{{ $eval->date_evaluation?->format('d/m/Y') ?? 'Sans date' }}</small>
                            </td>
                            <td>{{ preg_replace('/^semestre/', 'S', (string) $eval->periode) }}</td>
                            <td>{{ number_format((float)$eval->bareme, 1, ',', ' ') }} · ×{{ number_format((float)$eval->coefficient, 1, ',', ' ') }}</td>
                            <td data-fng-score-cell>
                                <span data-fng-display-score class="{{ $studentNote ? 'fng-score' : 'fng-muted' }}">{{ $studentNote ? ($studentNote->is_absent ? 'ABS' : number_format((float)$studentNote->note, 2, ',', ' ')) : 'Non saisie' }}</span>
                                @if(!empty($allowBulk) && $canWriteFngNote)
                                    <div class="fng-inline-editor" data-fng-inline-editor hidden>
                                        <div class="fng-inline-main">
                                            <label class="fng-inline-note-label">
                                                <span class="visually-hidden">Note de {{ $eval->titre }} sur {{ $eval->bareme }}</span>
                                                <input type="text" inputmode="decimal" autocomplete="off" data-fng-inline-note
                                                    aria-label="Note de {{ $eval->titre }} sur {{ $eval->bareme }}"
                                                    value="{{ ($studentNote && !$studentNote->is_absent) ? $studentNote->note : '' }}"
                                                    {{ $studentNote?->is_absent ? 'disabled' : '' }}>
                                            </label>
                                            <label class="fng-inline-absent"><input type="checkbox" data-fng-inline-absent {{ $studentNote?->is_absent ? 'checked' : '' }}> ABS</label>
                                        </div>
                                        <details class="fng-inline-details"><summary>Commentaire</summary>
                                            <textarea rows="2" maxlength="500" data-fng-inline-comment aria-label="Commentaire pour {{ $eval->titre }}">{{ $studentNote?->commentaire ?? '' }}</textarea>
                                        </details>
                                        <small class="fng-inline-error" data-fng-inline-error role="alert" hidden></small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="fng-status {{ $studentNote ? ($studentNote->isSubmitted() ? 'fng-status-submitted' : 'fng-status-draft') : 'fng-status-missing' }}" data-fng-note-status-label>
                                    {{ $studentNote ? ($studentNote->isSubmitted() ? 'Validée' : 'Brouillon (non validée)') : 'Non saisie' }}
                                </span>
                                <small class="fng-evaluation-status">Évaluation {{ $eval->is_published ? 'active' : 'non publiée' }}</small>
                            </td>
                            <td class="fng-right">
                                <div class="fng-actions">
                                    @if($canWriteFngNote)
                                        <button type="button" class="fng-icon-btn" title="{{ $studentNote ? 'Modifier la note' : 'Saisir une note' }}"
                                            data-fng-note data-evaluation="{{ $eval->id }}"
                                            data-note="{{ $studentNote?->note ?? '' }}" data-absent="{{ $studentNote?->is_absent ? 1 : 0 }}"
                                            data-comment="{{ $studentNote?->commentaire ?? '' }}" data-existing="{{ $studentNote ? 1 : 0 }}"
                                            data-submitted="{{ $studentNote?->isSubmitted() ? 1 : 0 }}"
                                            data-bareme="{{ $eval->bareme }}" data-title="{{ $eval->titre }}" data-matiere="{{ $eval->matiere?->name ?? 'Matière inconnue' }}">
                                            <i class="fas fa-{{ $studentNote ? 'pen' : 'plus' }}"></i>
                                        </button>
                                    @endif
                                    @if($canEditEval)
                                        <button type="button" class="fng-icon-btn" title="{{ $eval->is_published ? 'Désactiver l’évaluation' : 'Activer l’évaluation' }}"
                                            data-fng-publish data-id="{{ $eval->id }}" data-published="{{ $eval->is_published ? 1 : 0 }}">
                                            <i class="fas fa-{{ $eval->is_published ? 'eye-slash' : 'eye' }}"></i>
                                        </button>
                                        <button type="button" class="fng-icon-btn" title="Modifier titre, barème et coefficient" data-fng-edit-eval
                                            data-id="{{ $eval->id }}" data-title="{{ $eval->titre }}" data-matiere="{{ $eval->matiere?->name ?? 'Matière inconnue' }}" data-bareme="{{ $eval->bareme }}"
                                            data-coefficient="{{ $eval->coefficient }}"><i class="fas fa-sliders-h"></i></button>
                                    @endif
                                    @if($studentNote && $canDeleteNote)
                                        <button type="button" class="fng-icon-btn fng-danger" title="Supprimer cette note" data-fng-delete
                                            data-delete-url="{{ route('esbtp.fiche-notes.supprimer', ['inscription' => $ficheInsc->id, 'note' => $studentNote->id]) }}"
                                            data-title="{{ $eval->titre }}" data-matiere="{{ $eval->matiere?->name ?? 'Matière inconnue' }}"><i class="fas fa-trash"></i></button>
                                    @endif
                                    @if($canOpenEvaluation)
                                        <a class="fng-icon-btn" target="_blank" rel="noopener" title="Détails de l'évaluation" href="{{ route('esbtp.evaluations.show', $eval->id) }}"><i class="fas fa-arrow-up-right-from-square"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="fng-empty"><i class="fas fa-clipboard-list"></i> Aucune évaluation sur cette inscription.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(!empty($allowBulk) && $hasWritableNotes)
            <div class="fng-bulk-bar" data-fng-bulk-bar hidden>
                <div class="fng-bulk-message" role="status" aria-live="polite">
                    <strong data-fng-bulk-count>0 modification en attente</strong>
                    <small data-fng-bulk-feedback>La sauvegarde ne valide pas officiellement les notes.</small>
                </div>
                <div class="fng-bulk-buttons">
                    <button type="button" class="fng-btn" data-fng-bulk-cancel>Terminer / Annuler</button>
                    <button type="button" class="fng-btn fng-btn-primary" data-fng-bulk-save disabled><i class="fas fa-save" aria-hidden="true"></i> Enregistrer les modifications</button>
                </div>
            </div>
        @endif
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
