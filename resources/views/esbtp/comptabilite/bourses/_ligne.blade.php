{{-- Une bourse : rendue par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $bourse->id }}">
    <td>{{ $bourse->id }}</td>
    <td>{{ $bourse->etudiant->nom_complet ?? $bourse->etudiant->user->name ?? 'N/A' }}</td>
    <td>{{ ucfirst($bourse->type_bourse) }}</td>
    <td>
        @if($bourse->montant)
            <span class="fw-bold text-primary">{{ number_format($bourse->montant, 0, ',', ' ') }} FCFA</span>
        @elseif($bourse->pourcentage)
            <span class="fw-bold text-info">{{ $bourse->pourcentage }}%</span>
        @else
            <span class="text-muted">N/A</span>
        @endif
    </td>
    <td>{{ $bourse->date_debut ? $bourse->date_debut->format('d/m/Y') : 'N/A' }}</td>
    <td>{{ $bourse->date_fin ? $bourse->date_fin->format('d/m/Y') : 'N/A' }}</td>
    <td>
        @if($bourse->statut == 'active')
            <span class="badge bg-success px-3 py-2">Active</span>
        @elseif($bourse->statut == 'suspendue')
            <span class="badge bg-warning px-3 py-2">Suspendue</span>
        @elseif($bourse->statut == 'terminée')
            <span class="badge bg-secondary px-3 py-2">Terminée</span>
        @else
            <span class="badge bg-info px-3 py-2">{{ $bourse->statut }}</span>
        @endif
    </td>
    <td>{{ $bourse->organisme_financeur ?? 'N/A' }}</td>
    <td class="text-nowrap">
        <div class="btn-group" role="group">
            <a href="{{ route('esbtp.comptabilite.bourses.show', $bourse->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Détails">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.comptabilite.bourses.edit', $bourse->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
            <form action="{{ route('esbtp.comptabilite.bourses.destroy', $bourse->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette bourse?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
