{{-- Un compte secretaire : rendu par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $secretaire->id }}">
    <td>{{ $secretaire->last_name ?? $secretaire->name }}</td>
    <td>{{ $secretaire->first_name ?? '' }}</td>
    <td>{{ $secretaire->email }}</td>
    <td>{{ $secretaire->username }}</td>
    <td>{{ $secretaire->phone ?? 'Non renseigné' }}</td>
    <td>
        @if($secretaire->is_active)
            <span class="badge bg-success">Actif</span>
        @else
            <span class="badge bg-danger">Inactif</span>
        @endif
    </td>
    <td>{{ $secretaire->created_at->format('d/m/Y') }}</td>
    <td>
        <div class="btn-group" role="group">
            <a href="{{ route('esbtp.secretaires.show', $secretaire->id) }}" class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.secretaires.edit', $secretaire->id) }}" class="btn btn-sm btn-warning">
                <i class="fas fa-edit"></i>
            </a>
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $secretaire->id }}">
                <i class="fas fa-trash"></i>
            </button>
        </div>

        <!-- Modal de suppression -->
        <div class="modal fade" id="deleteModal{{ $secretaire->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $secretaire->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel{{ $secretaire->id }}">Confirmation de suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Êtes-vous sûr de vouloir supprimer le secrétaire <strong>{{ $secretaire->name }}</strong> ?
                        <p class="text-danger mt-2">Cette action est irréversible.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <form action="{{ route('esbtp.secretaires.destroy', $secretaire->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>
