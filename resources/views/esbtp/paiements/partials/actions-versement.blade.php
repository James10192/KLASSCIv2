{{-- Actions sur un versement, là où il est listé hors de la liste des
     paiements (fiche d'inscription, accueil caisse et comptable). Chaque bouton
     suit le droit ou l'état qui le permet ; les modales sont incluses à côté.

     Deux gestes à ne pas confondre :
     - « Annuler le versement » émet un AVOIR : le versement reste visible,
       compensé par une contre-écriture numérotée. C'est l'annulation.
     - « Supprimer » le retire des listes (corbeille), motif obligatoire. --}}
@php
    $avSuffixe = $avSuffixe ?? '';
    $avSupprimer = 'supprimerVersement'.$paiement->id.$avSuffixe;
    $avAnnuler = 'annulerVersement'.$paiement->id.$avSuffixe;
    $avPeutAnnuler = ! $paiement->isAvoir() && $paiement->status === 'validé' && $paiement->avoir_disponible > 0;
    // Un versement déjà compensé par un avoir ne se supprime pas (le serveur
    // le refuse) : le bouton ne s'affiche pas plutôt que d'échouer.
    $avCompense = ! $paiement->isAvoir() && $paiement->status === 'validé' && $paiement->avoir_disponible < (float) $paiement->montant;
@endphp
<div class="d-inline-flex gap-1 flex-nowrap">
    @can('view', $paiement)
        <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn btn-sm btn-outline-primary" title="Voir le versement">
            <i class="fas fa-eye"></i>
        </a>
        @if(! $paiement->isAvoir())
            <a href="{{ route('esbtp.paiements.preview-pdf', $paiement->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="Reçu (aperçu PDF)">
                <i class="fas fa-receipt"></i>
            </a>
        @endif
    @endcan
    @can('cancelOwnRecent', $paiement)
        <form action="{{ route('esbtp.paiements.cancel-own', $paiement->id) }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="retour" value="{{ $retour }}">
            <button type="submit" class="btn btn-sm btn-outline-warning" title="Annuler ma saisie (dans les minutes qui suivent)"
                    onclick="return confirm('Annuler ce versement que vous venez de saisir ?')">
                <i class="fas fa-undo"></i>
            </button>
        </form>
    @endcan
    @can('paiements.avoir')
        @if($avPeutAnnuler)
            <button type="button" class="btn btn-sm btn-outline-primary" title="Annuler le versement (avoir : le versement reste visible)"
                    data-bs-toggle="modal" data-bs-target="#{{ $avAnnuler }}">
                <i class="fas fa-rotate-left"></i>
            </button>
        @endif
    @endcan
    @can('paiements.delete')
        @unless($avCompense)
        <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer le versement (motif obligatoire)"
                data-bs-toggle="modal" data-bs-target="#{{ $avSupprimer }}">
            <i class="fas fa-trash"></i>
        </button>
        @endunless
    @endcan
</div>
@include('esbtp.paiements.partials.supprimer-modal', ['paiement' => $paiement, 'modalId' => $avSupprimer, 'retour' => $retour])
@include('esbtp.paiements.partials.avoir-modal', ['paiement' => $paiement, 'modalId' => $avAnnuler, 'retour' => $retour])
