{{-- Partial : la table et son bas de liste — rendu par la page et par chaque filtrage AJAX --}}
@if ($paginated->isEmpty())
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
        <h5>Aucun impayé trouvé</h5>
        <p>Tous les étudiants correspondant à vos filtres sont à jour ou aucun résultat pour cette recherche.</p>
    </div>
@else
    <div style="overflow-x:auto;">
        <table class="rel-table">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Classe</th>
                    <th>Filière</th>
                    <th>Progression</th>
                    <th>Total dû</th>
                    <th>Solde restant</th>
                    <th>Situation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="rel-tbody">
                @foreach ($paginated as $row)
                    @include('esbtp.comptabilite.relances._ligne')
                @endforeach
            </tbody>
        </table>
    </div>

    <x-liste-infinie :paginateur="$paginated" cible="#rel-tbody" libelle="étudiants"
                     :url="route('esbtp.comptabilite.relances.index')" />
@endif
