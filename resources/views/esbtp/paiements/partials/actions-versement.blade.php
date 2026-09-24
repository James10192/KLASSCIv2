{{-- Actions sur un versement, là où il est listé hors de la liste des
     paiements (fiche d'inscription). Chaque bouton suit le droit ou l'état
     qui le permet ; la modale de suppression est incluse à côté. --}}
@php $avModal = 'supprimerVersement'.$paiement->id; @endphp
<div class="d-inline-flex gap-1 flex-nowrap">
    @can('view', $paiement)
        <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn btn-sm btn-outline-primary" title="Voir le versement">
            <i class="fas fa-eye"></i>
        </a>
    @endcan
    @can('cancelOwnRecent', $paiement)
        <form action="{{ route('esbtp.paiements.cancel-own', $paiement->id) }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="retour" value="{{ $retour }}">
            <button type="submit" class="btn btn-sm btn-outline-warning" title="Annuler ma saisie"
                    onclick="return confirm('Annuler ce versement que vous venez de saisir ?')">
                <i class="fas fa-undo"></i>
            </button>
        </form>
    @endcan
    @can('paiements.delete')
        <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer le versement (motif obligatoire)"
                data-bs-toggle="modal" data-bs-target="#{{ $avModal }}">
            <i class="fas fa-trash"></i>
        </button>
    @endcan
</div>
@include('esbtp.paiements.partials.supprimer-modal', ['paiement' => $paiement, 'modalId' => $avModal, 'retour' => $retour])
