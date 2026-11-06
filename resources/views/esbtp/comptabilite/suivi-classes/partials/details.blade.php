@php
    $sfcMontant = fn ($n) => number_format((float) $n, 0, ',', ' ');
    // Chaque lien n'apparaît qu'à qui peut ouvrir la page visée.
    $sfcUser = auth()->user();
    $sfcFiche = $sfcUser?->can('students.view');
    $sfcInscription = $sfcUser?->can('inscriptions.view');
    $sfcPaiements = $sfcUser?->can('paiements.view');
@endphp
@if($ligne)
    <div class="sfc-d-kpis">
        <div class="sfc-d-kpi"><span>Inscrits</span><strong>{{ $ligne['effectif'] }}</strong></div>
        <div class="sfc-d-kpi"><span>Attendu</span><strong>{{ $sfcMontant($ligne['attendu']) }}</strong></div>
        <div class="sfc-d-kpi"><span>Réglé</span><strong>{{ $sfcMontant($ligne['regle']) }}</strong></div>
        <div class="sfc-d-kpi"><span>Échu impayé</span><strong class="{{ $ligne['echu'] > 0 ? 'sfc-due' : '' }}">{{ $sfcMontant($ligne['echu']) }}</strong></div>
    </div>

    @php
        // Les étudiants en retard d'abord, puis les plus gros restes.
        $sfcEtudiants = collect($ligne['etudiants'])->sortBy([['echu', 'desc'], ['restant', 'desc']])->values();
    @endphp
    <div class="sfc-table-scroll" style="display:block">
        <table class="sfc-table">
            <thead>
            <tr>
                <th>Étudiant</th>
                <th>Situation</th>
                <th class="sfc-num">Attendu</th>
                <th class="sfc-num">Réglé</th>
                <th class="sfc-num">Reste</th>
                <th class="sfc-num">Échu</th>
                @if($sfcFiche || $sfcInscription || $sfcPaiements)<th><span class="sfc-sr-only">Actions</span></th>@endif
            </tr>
            </thead>
            <tbody>
            @forelse($sfcEtudiants as $e)
                <tr>
                    <td class="sfc-class"><strong>{{ $e['nom'] }}</strong><small>{{ $e['matricule'] }}</small></td>
                    <td>
                        @if($e['restant'] <= 0)
                            <span class="sfc-badge sfc-badge--ok"><i class="fas fa-check"></i> Soldé</span>
                        @elseif($e['echu'] > 0)
                            <span class="sfc-badge sfc-badge--late"><i class="fas fa-clock"></i> En retard</span>
                        @else
                            <span class="sfc-badge sfc-badge--open">À jour</span>
                        @endif
                    </td>
                    <td class="sfc-num">{{ $sfcMontant($e['attendu']) }}</td>
                    <td class="sfc-num">{{ $sfcMontant($e['regle']) }}</td>
                    <td class="sfc-num">{{ $sfcMontant($e['restant']) }}</td>
                    <td class="sfc-num {{ $e['echu'] > 0 ? 'sfc-due' : 'sfc-zero' }}">{{ $sfcMontant($e['echu']) }}</td>
                    @if($sfcFiche || $sfcInscription || $sfcPaiements)
                        <td>
                            <div class="sfc-actions-row">
                                @if($sfcFiche && ($e['etudiant_id'] ?? 0) > 0)
                                    <a class="sfc-link" href="{{ route('esbtp.etudiants.show', $e['etudiant_id']) }}" title="Fiche de l’étudiant"><i class="fas fa-user"></i><span class="sfc-sr-only">Fiche de {{ $e['nom'] }}</span></a>
                                @endif
                                @if($sfcInscription)
                                    <a class="sfc-link" href="{{ route('esbtp.inscriptions.show', $e['inscription_id']) }}" title="Inscription"><i class="fas fa-id-card"></i><span class="sfc-sr-only">Inscription de {{ $e['nom'] }}</span></a>
                                @endif
                                @if($sfcPaiements && $e['matricule'] !== '—')
                                    <a class="sfc-link" href="{{ route('esbtp.paiements.index', ['search' => $e['matricule']]) }}" title="Paiements"><i class="fas fa-money-bill-wave"></i><span class="sfc-sr-only">Paiements de {{ $e['nom'] }}</span></a>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ ($sfcFiche || $sfcInscription || $sfcPaiements) ? 7 : 6 }}" class="sfc-empty"><i class="fas fa-user-slash"></i>Aucune inscription dans cette classe pour l’année choisie.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="sfc-empty"><i class="fas fa-circle-question"></i>Cette classe n’est pas dans les filtres choisis.</div>
@endif
