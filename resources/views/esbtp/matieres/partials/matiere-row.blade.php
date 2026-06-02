@php
    $filieres = $matiere->filieres ?? collect();
    $niveaux = $matiere->niveaux ?? collect();
    $liaisons = $matiere->liaisonsFilieresNiveaux ?? collect();
    $liaisonCount = $liaisons->count();
    $liaisonsVisible = $liaisons->take(3);
    $liaisonsExtra = max(0, $liaisonCount - 3);
@endphp

<tr data-matiere-id="{{ $matiere->id }}" class="position-relative">
    <td>
        <div class="form-check m-0">
            <input class="form-check-input matiere-checkbox" type="checkbox" id="matiere-{{ $matiere->id }}" value="{{ $matiere->id }}">
            <label class="form-check-label visually-hidden" for="matiere-{{ $matiere->id }}">
                Sélectionner {{ $matiere->name }}
            </label>
        </div>
    </td>
    <td>
        <span class="badge primary" style="font-family:'IBM Plex Mono',monospace;font-size:.72rem;letter-spacing:.04em;">{{ $matiere->code ?? '—' }}</span>
    </td>
    <td>
        <div class="font-semibold color-primary">{{ $matiere->name }}</div>
        @if($matiere->description)
            <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($matiere->description, 80) }}</small>
        @endif
        @if($liaisonCount > 0)
            <small class="text-muted">
                <i class="fas fa-link me-1"></i>{{ $liaisonCount }} combinaison(s)
            </small>
        @endif
    </td>
    <td>
        <span class="font-bold color-accent">{{ number_format($matiere->coefficient_default ?? $matiere->coefficient ?? 0, 2) }}</span>
    </td>
    <td>
        <span class="font-bold color-primary">{{ $matiere->total_heures_default ?? 0 }}h</span>
    </td>
    {{-- Colonne Liaisons : pills filière · niveau exactes --}}
    <td>
        @if($liaisonCount > 0)
            <div class="d-flex flex-wrap gap-1">
                @foreach($liaisonsVisible as $liaison)
                    @php
                        $fCode = $liaison->filiere->code ?? \Illuminate\Support\Str::limit($liaison->filiere->name ?? '?', 8);
                        $nCode = $liaison->niveauEtude->code ?? \Illuminate\Support\Str::limit($liaison->niveauEtude->name ?? '?', 4);
                        $tooltip = ($liaison->filiere->name ?? $fCode) . ' — ' . ($liaison->niveauEtude->name ?? $nCode);
                    @endphp
                    <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1"
                          style="background: linear-gradient(135deg,#e8f0fe 0%,#d2e3fc 100%); color:#1a56db; font-size:.72rem; font-weight:600; border:1px solid #c2d4f8; border-radius:999px;"
                          title="{{ $tooltip }}">
                        <span style="color:#1a56db;">{{ $fCode }}</span>
                        <span style="color:#94a3b8; font-weight:400;">·</span>
                        <span style="color:#0f3fa6;">{{ $nCode }}</span>
                    </span>
                @endforeach
                @if($liaisonsExtra > 0)
                    <span class="badge d-inline-flex align-items-center px-2 py-1"
                          style="background:#f1f5f9; color:#64748b; font-size:.72rem; border:1px solid #e2e8f0; border-radius:999px;"
                          title="{{ $liaisonCount }} combinaisons au total">
                        +{{ $liaisonsExtra }}
                    </span>
                @endif
            </div>
        @else
            <span class="badge bg-light text-muted border" style="border-color:#e2e8f0 !important; font-size:.72rem;">
                <i class="fas fa-unlink me-1 opacity-50"></i>Non configuré
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
        <div class="matiere-actions-wrapper" data-matiere-actions="{{ $matiere->id }}">
            <div class="matiere-actions-buttons mi-actions">
                {{-- Action primaire : Voir --}}
                <a href="{{ route('esbtp.matieres.show', $matiere->id) }}"
                   class="mi-action-primary"
                   title="Voir la fiche"
                   aria-label="Voir la fiche de {{ $matiere->name }}">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </a>

                {{-- Kebab menu BS5 native dropdown : Configurer / Modifier / Supprimer
                     data-bs-strategy=fixed → Popper détache du flow table-responsive (pas de clip)
                     data-bs-display=dynamic → flip auto vers le haut si pas assez d'espace en bas --}}
                <div class="dropdown">
                    <button type="button"
                            class="mi-action-kebab"
                            data-bs-toggle="dropdown"
                            data-bs-strategy="fixed"
                            data-bs-display="dynamic"
                            aria-expanded="false"
                            aria-label="Plus d'actions pour {{ $matiere->name }}"
                            title="Plus d'actions">
                        <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end mi-action-menu" style="z-index:1100;">
                        <li>
                            <button type="button"
                                    class="dropdown-item configure-matiere-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#configureModal"
                                    data-matiere-id="{{ $matiere->id }}"
                                    data-matiere-name="{{ $matiere->name }}">
                                <i class="fas fa-link me-2" aria-hidden="true"></i>
                                Configurer les liaisons
                            </button>
                        </li>
                        <li>
                            <a href="{{ route('esbtp.matieres.edit', $matiere->id) }}" class="dropdown-item">
                                <i class="fas fa-edit me-2" aria-hidden="true"></i>
                                Modifier
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button"
                                    class="dropdown-item text-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteModal{{ $matiere->id }}">
                                <i class="fas fa-trash me-2" aria-hidden="true"></i>
                                Supprimer
                            </button>
                        </li>
                    </ul>
                </div>
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
