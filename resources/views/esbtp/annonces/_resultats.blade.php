{{-- La même première tranche et le même contrat d'infinite scroll sur chaque surface. --}}
<div class="ann-result-heading">
    <div><strong>{{ number_format($annonces->total(), 0, ',', ' ') }} annonce(s)</strong>
        <span>correspondant aux critères</span></div>
    <span class="ann-result-hint">Résultats filtrés sur toute la base.</span>
</div>
<div class="ann-table-wrap">
    <table class="modern-table" id="annoncesTable">
        <thead><tr>
            <th>Titre</th><th>Statut</th><th>Priorité</th><th>Public</th>
            <th>Créée le</th><th>Expiration</th><th class="actions-col">Actions</th>
        </tr></thead>
        <tbody id="ann-tbody">
            @forelse($annonces as $annonce)
                @include('esbtp.annonces._ligne')
            @empty
                <tr><td colspan="7"><div class="ann-empty">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>Aucune annonce trouvée</strong>
                    <span>Essayez d'élargir vos critères.</span>
                </div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="annonces-grid" id="annoncesGrid">
    @forelse($annonces as $annonce)
        @include('esbtp.annonces._carte')
    @empty
        <div class="ann-empty"><strong>Aucune annonce trouvée</strong>
            <span>Modifiez les filtres pour continuer.</span></div>
    @endforelse
</div>
<x-liste-infinie class="ann-bas-tableau" :paginateur="$annonces" cible="#ann-tbody" libelle="annonces" />
<x-liste-infinie class="ann-bas-grille" :paginateur="$annonces" cible="#annoncesGrid"
    libelle="annonces" :url="route('esbtp.annonces.index', ['vue' => 'grille'])" />

<noscript>
    <nav class="ann-nojs-pagination" aria-label="Pagination sans JavaScript">
        {{ $annonces->links() }}
    </nav>
</noscript>
