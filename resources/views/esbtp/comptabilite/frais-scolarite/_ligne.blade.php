{{-- Un frais de scolarite : rendu par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $frais->id }}">
    <td>{{ $frais->filiere->name }}</td>
    <td>{{ $frais->niveau->name }}</td>
    <td>{{ $frais->anneeUniversitaire->name }}</td>
    <td class="text-end">{{ number_format($frais->montant_total, 0, ',', ' ') }} FCFA</td>
    <td class="text-end">{{ number_format($frais->frais_inscription, 0, ',', ' ') }} FCFA</td>
    <td class="text-center">{{ $frais->nombre_echeances }}</td>
    <td>
        @if($frais->est_actif)
        <span class="badge bg-success">Actif</span>
        @else
        <span class="badge bg-danger">Inactif</span>
        @endif
    </td>
    <td class="text-nowrap">
        <div class="btn-group" role="group">
            <a href="{{ route('esbtp.comptabilite.frais-scolarite.show', $frais->id) }}" class="btn btn-sm btn-info" title="Détails">
                <i class="fas fa-eye"></i>
            </a>
            
            <a href="{{ route('esbtp.comptabilite.frais-scolarite.edit', $frais->id) }}" class="btn btn-sm btn-primary" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
            
            <form action="{{ route('esbtp.comptabilite.frais-scolarite.destroy', $frais->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette configuration de frais de scolarité?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
