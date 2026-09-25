{{-- Un bulletin en attente : rendu par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $bulletin->id }}">
    <td>{{ $bulletin->id }}</td>
    <td>
        <div>{{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</div>
        <small class="text-muted">{{ $bulletin->etudiant->matricule }}</small>
    </td>
    <td>{{ $bulletin->classe->name }}</td>
    <td>
        @if($bulletin->periode == 'semestre1')
            Premier Semestre
        @elseif($bulletin->periode == 'semestre2')
            Deuxième Semestre
        @elseif($bulletin->periode == 'annuel')
            Annuel
        @else
            {{ $bulletin->periode }}
        @endif
    </td>
    <td>
        @if($bulletin->moyenne_generale !== null)
            <span class="badge {{ $bulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }}">
                {{ number_format($bulletin->moyenne_generale, 2) }}
            </span>
        @else
            <span class="badge bg-secondary">Non calculée</span>
        @endif
    </td>
    <td>
        @if($bulletin->is_published)
            <span class="badge bg-success">Publié</span>
        @else
            <span class="badge bg-warning">Non publié</span>
        @endif
    </td>
    <td>
        <div class="d-flex gap-1">
            <span class="badge {{ $bulletin->signature_directeur ? 'bg-success' : 'bg-danger' }}" title="Directeur">
                <i class="fas {{ $bulletin->signature_directeur ? 'fa-check' : 'fa-times' }}"></i> Dir
            </span>
            <span class="badge {{ $bulletin->signature_responsable ? 'bg-success' : 'bg-danger' }}" title="Responsable">
                <i class="fas {{ $bulletin->signature_responsable ? 'fa-check' : 'fa-times' }}"></i> Resp
            </span>
            <span class="badge {{ $bulletin->signature_parent ? 'bg-success' : 'bg-danger' }}" title="Parent">
                <i class="fas {{ $bulletin->signature_parent ? 'fa-check' : 'fa-times' }}"></i> Par
            </span>
        </div>
    </td>
    <td>
        <div class="btn-group" role="group">
            <a href="{{ route('esbtp.bulletins.show', $bulletin) }}" class="btn btn-sm btn-info" title="Voir">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.bulletins.edit', $bulletin) }}" class="btn btn-sm btn-warning" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
            @php $_pdfParams = ['bulletin' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id]; @endphp
            <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_pdfParams) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Aperçu PDF">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.bulletins.pdf-params', $_pdfParams) }}" class="btn btn-sm btn-secondary" target="_blank" title="Télécharger PDF">
                <i class="fas fa-file-pdf"></i>
            </a>

            @if(!$bulletin->is_published)
            <form action="{{ route('esbtp.bulletins.toggle-publication', $bulletin) }}" method="POST" class="d-inline">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-sm btn-success" title="Publier">
                    <i class="fas fa-check-circle"></i>
                </button>
            </form>
            @endif

            @if(!$bulletin->signature_responsable)
            <form action="{{ route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'responsable']) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary" title="Signer (Responsable)">
                    <i class="fas fa-signature"></i> R
                </button>
            </form>
            @endif

            @if(!$bulletin->signature_directeur)
            <form action="{{ route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'directeur']) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary" title="Signer (Directeur)">
                    <i class="fas fa-signature"></i> D
                </button>
            </form>
            @endif
        </div>
    </td>
</tr>
