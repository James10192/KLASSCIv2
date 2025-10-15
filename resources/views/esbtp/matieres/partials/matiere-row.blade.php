@php
    $filieres = $matiere->filieres ?? collect();
    $niveaux = $matiere->niveaux ?? collect();
@endphp

<tr data-matiere-id="{{ $matiere->id }}" class="position-relative">
    <td>
        <div class="form-check">
            <input class="form-check-input matiere-checkbox" type="checkbox" id="matiere-{{ $matiere->id }}" value="{{ $matiere->id }}">
            <label class="form-check-label" for="matiere-{{ $matiere->id }}"></label>
        </div>
    </td>
    <td>
        <span class="badge primary">{{ $matiere->code ?? '—' }}</span>
    </td>
    <td>
        <div class="font-semibold color-primary">{{ $matiere->name }}</div>
        @if($matiere->description)
            <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($matiere->description, 80) }}</small>
        @endif
        @if($filieres->count() && $niveaux->count())
            <small class="text-muted">
                <i class="fas fa-link me-1"></i>{{ $filieres->count() * $niveaux->count() }} combinaison(s)
            </small>
        @endif
    </td>
    <td>
        <span class="font-bold color-accent">{{ number_format($matiere->coefficient_default ?? $matiere->coefficient ?? 0, 2) }}</span>
    </td>
    <td>
        <span class="font-bold color-primary">{{ $matiere->total_heures_default ?? 0 }}h</span>
    </td>
    <td>
        @if($filieres->isNotEmpty())
            <div class="d-flex flex-wrap gap-1">
                @foreach($filieres->take(3) as $filiere)
                    <span class="badge bg-primary text-white" title="{{ $filiere->name }}">
                        {{ $filiere->code ?? \Illuminate\Support\Str::limit($filiere->name, 8) }}
                    </span>
                @endforeach
                @if($filieres->count() > 3)
                    <span class="badge bg-info text-white" title="{{ $filieres->count() }} filières au total">
                        +{{ $filieres->count() - 3 }}
                    </span>
                @endif
            </div>
        @else
            <span class="badge bg-secondary">
                <i class="fas fa-minus me-1"></i>Aucune
            </span>
        @endif
    </td>
    <td>
        @if($niveaux->isNotEmpty())
            <div class="d-flex flex-wrap gap-1">
                @foreach($niveaux->take(3) as $niveau)
                    <span class="badge bg-info text-white" title="{{ $niveau->name }}">
                        {{ $niveau->code ?? \Illuminate\Support\Str::limit($niveau->name, 8) }}
                    </span>
                @endforeach
                @if($niveaux->count() > 3)
                    <span class="badge bg-warning text-dark" title="{{ $niveaux->count() }} niveaux au total">
                        +{{ $niveaux->count() - 3 }}
                    </span>
                @endif
            </div>
        @else
            <span class="badge bg-secondary">
                <i class="fas fa-minus me-1"></i>Aucun
            </span>
        @endif
    </td>
    <td>
        @if($matiere->is_active)
            <span class="badge success">
                <i class="fas fa-check-circle me-1"></i>Actif
            </span>
        @else
            <span class="badge danger">
                <i class="fas fa-times-circle me-1"></i>Inactif
            </span>
        @endif
    </td>
    <td>
        <div class="matiere-actions-wrapper d-inline-flex align-items-center gap-2" data-matiere-actions="{{ $matiere->id }}">
            <div class="d-flex gap-1 matiere-actions-buttons">
                <a href="{{ route('esbtp.matieres.show', $matiere->id) }}"
                   class="btn btn-sm btn-outline-info"
                   title="Voir">
                    <i class="fas fa-eye"></i>
                </a>
                <button type="button"
                        class="btn btn-sm btn-outline-success configure-matiere-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#configureModal"
                        data-matiere-id="{{ $matiere->id }}"
                        data-matiere-name="{{ $matiere->name }}"
                        title="Configurer les liaisons">
                    <i class="fas fa-link"></i>
                </button>
                <a href="{{ route('esbtp.matieres.edit', $matiere->id) }}"
                   class="btn btn-sm btn-outline-warning"
                   title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
                <button type="button"
                        class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteModal{{ $matiere->id }}"
                        title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="matiere-actions-spinner" aria-hidden="true">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteModal{{ $matiere->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $matiere->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel{{ $matiere->id }}">Confirmation de suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer la matière <strong>{{ $matiere->name }}</strong> ?</p>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Cette action est irréversible.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <form action="{{ route('esbtp.matieres.destroy', $matiere->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>
