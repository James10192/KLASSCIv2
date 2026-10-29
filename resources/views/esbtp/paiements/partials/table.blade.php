@php
    // Lot 13 — colonne "Encaissé par" visible UNIQUEMENT pour les users
    // qui voient TOUS les paiements (paiements.view).
    $showCreatorColumn = auth()->user()?->can('paiements.view') ?? false;
    $colspan = $showCreatorColumn ? 10 : 9;
    $totalRows = method_exists($paiements, 'total') ? $paiements->total() : $paiements->count();
@endphp
<div class="pi-table-card">
    <div class="pi-table-head">
        <div class="pi-table-title">
            <i class="fas fa-list"></i>
            <span>Liste des Paiements</span>
        </div>
        <div class="pi-table-count">
            <span id="pi-rows-shown">{{ $paiements->count() }}</span> / <span id="pi-rows-total">{{ $totalRows }}</span>
            <span class="pi-table-count-label">résultats</span>
        </div>
    </div>

    <div class="pi-table-wrap">
        <table class="pi-table">
            <thead>
                <tr>
                    <th class="pi-th-checkbox">
                        <input type="checkbox" id="select-all" class="form-check-input" title="Tout sélectionner">
                    </th>
                    <th>N° Reçu</th>
                    <th>Étudiant</th>
                    <th class="d-none d-md-table-cell">Catégorie</th>
                    <th>Date</th>
                    <th>Montant</th>
                    <th class="d-none d-md-table-cell">Mode</th>
                    @if($showCreatorColumn)
                        <th class="d-none d-lg-table-cell">Encaissé par</th>
                    @endif
                    <th class="pi-th-status">Statut</th>
                    <th class="pi-th-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="pi-tbody">
                @forelse($paiements as $paiement)
                    @include('esbtp.paiements.partials.ligne-paiement', ['paiement' => $paiement, 'showCreatorColumn' => $showCreatorColumn, 'renderPaiementModals' => false])
                @empty
                    <tr id="pi-empty-row">
                        <td colspan="{{ $colspan }}" class="pi-table-empty">
                            <i class="fas fa-inbox"></i>
                            <span>Aucun paiement trouvé</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modales sœurs du tableau, jamais enfants de <tbody> (HTML invalide).
         Le script de page les déplace ensuite vers l'hôte persistant, hors AJAX. --}}
    <div class="pi-modals-to-mount">
        @foreach($paiements as $paiement)
            @include('esbtp.paiements.partials.modals-paiement', ['paiement' => $paiement])
        @endforeach
    </div>

    {{-- Suite au defilement : le composant commun (contrat ListeInfinie). --}}
    <x-liste-infinie :paginateur="$paiements" cible="#pi-tbody" libelle="paiements"
                     :url="route('esbtp.paiements.index')" />
</div>
