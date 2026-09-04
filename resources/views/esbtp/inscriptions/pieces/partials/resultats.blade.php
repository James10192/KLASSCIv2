@if($lignes->isEmpty())
    <div class="ii-empty">
        <div class="ii-empty-icon"><i class="fas fa-check-double"></i></div>
        <h4>Aucun dossier incomplet</h4>
        <p>Toutes les pièces attendues sont enregistrées pour ce périmètre.</p>
    </div>
@else
    <div class="ii-table-wrap">
        <table class="ii-table">
            <thead>
                <tr>
                    @can('inscriptions.pieces.relancer')
                        <th style="width:38px;">
                            <input type="checkbox" class="pm-check-all" aria-label="Tout sélectionner">
                        </th>
                    @endcan
                    <th style="width:110px;">Matricule</th>
                    <th>Étudiant</th>
                    <th style="width:150px;">Classe</th>
                    <th style="width:120px;">Année</th>
                    <th>Pièces manquantes</th>
                    <th style="width:70px;text-align:center;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        @can('inscriptions.pieces.relancer')
                            <td>
                                <input type="checkbox" class="pm-check" value="{{ $ligne['inscription_id'] }}"
                                       aria-label="Sélectionner {{ $ligne['etudiant'] }}">
                            </td>
                        @endcan
                        <td>{{ $ligne['matricule'] ?: '—' }}</td>
                        <td>
                            <a href="{{ route('esbtp.inscriptions.show', $ligne['inscription_id']) }}">
                                {{ $ligne['etudiant'] }}
                            </a>
                            @if(empty($ligne['email']))
                                {{-- Sans adresse, la relance par email ne partira pas : autant le dire ici. --}}
                                <i class="fas fa-envelope-circle-check" style="color:#94a3b8;margin-left:.3rem;"
                                   title="Aucune adresse email — relance impossible"></i>
                            @endif
                        </td>
                        <td>{{ $ligne['classe'] ?: '—' }}</td>
                        <td>{{ $ligne['annee'] ?: '—' }}</td>
                        <td>
                            <div class="pm-tags">
                                @foreach($ligne['manquantes'] as $manquante)
                                    <span class="pm-tag {{ $manquante['obligatoire'] ? 'pm-tag--req' : '' }}">
                                        {{ $manquante['libelle'] }}
                                        @if($manquante['exemplaires_manquants'] > 1)
                                            ×{{ $manquante['exemplaires_manquants'] }}
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td style="text-align:center;font-weight:700;color:#0453cb;">{{ $ligne['nb_manquantes'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($lignes->hasPages())
        <div class="ii-pagination">
            {{ $lignes->links() }}
        </div>
    @endif
@endif
