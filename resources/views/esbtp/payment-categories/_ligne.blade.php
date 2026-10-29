{{-- Une categorie de paiement : rendue par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $cat->id }}">
    <td>{{ $cat->name }}</td>
    <td><span class="badge bg-info text-dark px-3 py-2">{{ $cat->code }}</span></td>
    <td>{{ $cat->description }}</td>
    <td>
        @if($cat->is_active)
            <span class="badge bg-success px-3 py-2">Actif</span>
        @else
            <span class="badge bg-danger px-3 py-2">Inactif</span>
        @endif
    </td>
    <td>
        <a href="{{ route('esbtp.payment-categories.edit', $cat) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"><i class="fas fa-edit"></i></a>
        <form action="{{ route('esbtp.payment-categories.destroy', $cat) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer cette catégorie ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"><i class="fas fa-trash"></i></button>
        </form>
    </td>
</tr>
