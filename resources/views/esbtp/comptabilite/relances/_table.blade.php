{{-- Partial: table + pagination — retourné pour les requêtes AJAX --}}
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
            <tbody>
                @foreach ($paginated as $row)
                    @php
                        $etudiant  = optional($row->inscription->etudiant);
                        $classe    = optional($row->inscription->classe);
                        $filiere   = optional($classe->filiere);
                        $nom       = $etudiant->nom ?? '';
                        $prenoms   = $etudiant->prenoms ?? '';
                        $initiales = strtoupper(mb_substr($nom, 0, 1) . mb_substr($prenoms, 0, 1));
                        $nomComplet = trim("$nom $prenoms") ?: '(sans nom)';

                        $pbClass = match(true) {
                            $row->pourcentage >= 100 => 'full',
                            $row->pourcentage >= 50  => 'partial',
                            $row->pourcentage > 0    => 'low-pay',
                            default                   => 'none',
                        };
                    @endphp
                    <tr>
                        {{-- Étudiant --}}
                        <td>
                            <div class="stud-cell">
                                <div class="stud-avatar">{{ $initiales ?: '?' }}</div>
                                <div>
                                    <div class="stud-name">{{ $nomComplet }}</div>
                                    <div class="stud-matricule">{{ $etudiant->matricule ?? '—' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Classe --}}
                        <td>{{ $classe->name ?? '—' }}</td>

                        {{-- Filière --}}
                        <td>{{ $filiere->name ?? '—' }}</td>

                        {{-- Progression --}}
                        <td>
                            <div class="pbar-wrap">
                                <div class="pbar-track">
                                    <div class="pbar-fill {{ $pbClass }}"
                                         style="width:{{ max(4, $row->pourcentage) }}%"></div>
                                </div>
                                <span class="pbar-pct">{{ $row->pourcentage }}%</span>
                            </div>
                        </td>

                        {{-- Total dû --}}
                        <td class="amount-cell">
                            {{ number_format($row->totalDu, 0, ',', ' ') }}
                            <span class="amount-unit">FCFA</span>
                        </td>

                        {{-- Solde restant --}}
                        <td class="amount-cell amount-red">
                            {{ number_format($row->soldeRestant, 0, ',', ' ') }}
                            <span class="amount-unit">FCFA</span>
                        </td>

                        {{-- Situation --}}
                        <td>
                            <span class="rbadge {{ $row->risk }}">
                                @if($row->risk === 'critical')
                                    <i class="fas fa-ban" style="font-size:.65em;"></i>
                                @elseif($row->risk === 'high')
                                    <i class="fas fa-hourglass-half" style="font-size:.65em;"></i>
                                @elseif($row->risk === 'medium')
                                    <i class="fas fa-tasks" style="font-size:.65em;"></i>
                                @else
                                    <i class="fas fa-check" style="font-size:.65em;"></i>
                                @endif
                                {{ $row->riskLabel }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                <a href="{{ route('esbtp.comptabilite.relances.etudiant', $row->inscription->id) }}"
                                   class="act-btn primary" title="Voir fiche relance">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span class="d-none d-xl-inline">Fiche</span>
                                </a>
                                <a href="{{ route('esbtp.inscriptions.show', $row->inscription->id) }}"
                                   class="act-btn ghost" title="Voir inscription">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if ($paginated->hasPages())
        <div class="rel-pagination">
            <span class="page-info">
                {{ $paginated->firstItem() }}–{{ $paginated->lastItem() }}
                sur {{ $paginated->total() }} résultat(s)
            </span>
            <div>
                {{ $paginated->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
@endif
