{{--
    Le constat de l'activité du personnel, rendu côté serveur puis injecté.

    @param \App\Services\Personnel\FenetreDActivite $fenetre
    @param \Illuminate\Support\Collection $lignes
    @param array $synthese
    @param float|null $tauxPrecedent
    @param int $seuilRelance
--}}
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $taux = $synthese['seances_prevues'] > 0 ? round($synthese['seances_tenues'] / $synthese['seances_prevues'] * 100, 1) : null;
    $ecart = $taux !== null && $tauxPrecedent !== null ? round($taux - $tauxPrecedent, 1) : null;
    $attente = $synthese['paiements_en_attente'];

    // La file de travail : qui appeler, et pourquoi. Triée par volume en retard.
    $aRelancer = $lignes
        ->filter(fn ($l) => $l['seances_non_emargees'] > 0 || $l['evaluations_en_retard'] > 0)
        ->sortByDesc(fn ($l) => $l['evaluations_en_retard'] * 10 + $l['seances_non_emargees'])
        ->take(8)
        ->values();

    $ratio = function (int $fait, int $prevu) {
        return $prevu > 0 ? (int) floor($fait / $prevu * 100) : null;
    };
@endphp

<span data-ap-libelle="{{ $fenetre->libelle }}" hidden></span>

<div data-ap-kpis>
    <button type="button" class="ap-kpi" @click="aller('ap-liste')">
        <span class="ap-kpi-icone"><i class="fas fa-chalkboard-user"></i></span>
        <span class="ap-kpi-texte">
            <span class="ap-kpi-valeur">{{ $taux !== null ? number_format($taux, 1, ',', ' ').' %' : '—' }}</span>
            <span class="ap-kpi-libelle">des séances tenues · {{ $fmt($synthese['seances_tenues']) }} sur {{ $fmt($synthese['seances_prevues']) }} prévues</span>
            @if($ecart !== null)
                <span class="ap-kpi-repere">{{ $ecart >= 0 ? '▲ +' : '▼ ' }}{{ number_format($ecart, 1, ',', ' ') }} pt vs période précédente</span>
            @endif
        </span>
    </button>
    <button type="button" class="ap-kpi" @click="aller('ap-file')">
        <span class="ap-kpi-icone"><i class="fas fa-file-circle-question"></i></span>
        <span class="ap-kpi-texte">
            <span class="ap-kpi-valeur">{{ $synthese['enseignants_notes_en_retard'] }}</span>
            <span class="ap-kpi-libelle">enseignant{{ $synthese['enseignants_notes_en_retard'] > 1 ? 's' : '' }} avec des notes en retard · {{ $synthese['evaluations_en_retard'] }} évaluation{{ $synthese['evaluations_en_retard'] > 1 ? 's' : '' }} de plus de {{ $seuilRelance }} j</span>
        </span>
    </button>
    @can('paiements.view')
        <a class="ap-kpi" href="{{ route('esbtp.paiements.index', ['status' => 'en_attente']) }}">
    @else
        <div class="ap-kpi">
    @endcan
        <span class="ap-kpi-icone"><i class="fas fa-hourglass-half"></i></span>
        <span class="ap-kpi-texte">
            <span class="ap-kpi-valeur">{{ $attente['nombre'] }}</span>
            <span class="ap-kpi-libelle">paiement{{ $attente['nombre'] > 1 ? 's' : '' }} en attente de validation depuis plus de {{ $attente['jours'] }} j{{ $attente['nombre'] > 0 ? ' · '.$fmt($attente['montant']).' FCFA' : '' }}</span>
        </span>
    @can('paiements.view')
        </a>
    @else
        </div>
    @endcan
    <button type="button" class="ap-kpi" @click="aller('ap-liste')">
        <span class="ap-kpi-icone"><i class="fas fa-users"></i></span>
        <span class="ap-kpi-texte">
            <span class="ap-kpi-valeur">{{ $synthese['personnes'] }}</span>
            <span class="ap-kpi-libelle">personne{{ $synthese['personnes'] > 1 ? 's' : '' }} avec une activité ou des séances sur la période</span>
        </span>
    </button>
</div>

<div data-ap-corps>
    <section class="ap-card" id="ap-file">
        <div class="ap-card-tete">
            <div class="ap-section-icone"><i class="fas fa-list-check"></i></div>
            <div>
                <h2>À relancer</h2>
                <p>Séances passées sans émargement, et évaluations de plus de {{ $seuilRelance }} jours auxquelles il manque des notes.</p>
            </div>
        </div>
        @forelse($aRelancer as $l)
            <div class="ap-file-ligne">
                <div class="ap-avatar">{{ mb_strtoupper(mb_substr($l['nom'], 0, 1, 'UTF-8'), 'UTF-8') }}</div>
                <div class="ap-file-qui">
                    <strong>{{ $l['nom'] }}</strong>
                    @php
                        $_apMotifs = array_filter([
                            $l['seances_non_emargees'] > 0 ? $l['seances_non_emargees'].' séance(s) non émargée(s)' : null,
                            $l['evaluations_en_retard'] > 0 ? $l['evaluations_en_retard'].' évaluation(s) sans toutes leurs notes' : null,
                        ]);
                    @endphp
                    <span>{{ implode(' · ', $_apMotifs) }}
                    </span>
                </div>
                @if($l['telephone'])
                    <a class="ap-btn ap-btn--ghost ap-btn--compact" href="tel:{{ preg_replace('/[^0-9+]/', '', $l['telephone']) }}"><i class="fas fa-phone"></i>Appeler</a>
                @endif
                <a class="ap-btn ap-btn--ghost ap-btn--compact" href="{{ route('esbtp.personnel.performance.show', ['user' => $l['id'], 'periode' => $fenetre->periode]) }}">Détail</a>
            </div>
        @empty
            <div class="ap-vide"><i class="fas fa-circle-check"></i><strong>Rien à relancer</strong><span>Toutes les séances passées sont émargées et les notes sont rendues. Vérifié à {{ now()->format('H:i') }}.</span></div>
        @endforelse
    </section>

    <section class="ap-card" id="ap-liste" x-data="{ q: '' }" @ap:recherche.window="q = ($event.detail || '').toLowerCase()">
        <div class="ap-card-tete">
            <div class="ap-section-icone"><i class="fas fa-table-list"></i></div>
            <div>
                <h2>Personne par personne</h2>
                <p>Chaque colonne compare ce qui était prévu à ce qui a été fait. Un tiret : rien de prévu pour cette personne. Cliquez une ligne pour voir le détail.</p>
            </div>
        </div>
        @if($lignes->isEmpty())
            <div class="ap-vide"><i class="fas fa-users"></i><strong>Aucune activité</strong><span>Aucune séance, note, inscription ni paiement enregistré sur cette période.</span></div>
        @else
            <div class="ap-table-wrap">
                <table class="ap-table">
                    <thead>
                        <tr>
                            <th>Personne</th>
                            <th class="ap-num">Séances tenues</th>
                            <th class="ap-num">Retards</th>
                            <th class="ap-num">Notes rendues</th>
                            <th class="ap-num">Paiements saisis</th>
                            <th class="ap-num">Validés</th>
                            <th class="ap-num">En attente de validation</th>
                            <th class="ap-num">Inscriptions saisies</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lignes as $l)
                            @php
                                $pctSeances = $ratio($l['seances_tenues'], $l['seances_prevues']);
                                $pctNotes = $ratio($l['notes_recues'], $l['notes_attendues']);
                                $cherche = mb_strtolower($l['nom'].' '.$l['role'], 'UTF-8');
                                $lien = route('esbtp.personnel.performance.show', ['user' => $l['id'], 'periode' => $fenetre->periode]);
                            @endphp
                            <tr class="ap-ligne" x-show="!q || @js($cherche).includes(q)" @click="window.location = @js($lien)">
                                <td>
                                    <div class="ap-personne">
                                        <div class="ap-avatar">{{ mb_strtoupper(mb_substr($l['nom'], 0, 1, 'UTF-8'), 'UTF-8') }}</div>
                                        <div><strong>{{ $l['nom'] }}</strong><span>{{ $l['role'] ?: '—' }}</span></div>
                                    </div>
                                </td>
                                <td class="ap-num">
                                    @if($pctSeances !== null)
                                        <div class="ap-ratio" title="{{ $l['seances_tenues'] }} sur {{ $l['seances_prevues'] }}">
                                            <span>{{ $l['seances_tenues'] }} / {{ $l['seances_prevues'] }}</span>
                                            <div class="ap-ratio-piste"><div class="ap-ratio-barre {{ $pctSeances < 80 ? 'is-bas' : '' }}" style="width: {{ $pctSeances }}%"></div></div>
                                        </div>
                                    @else <span class="ap-muet">—</span> @endif
                                </td>
                                <td class="ap-num">{!! $l['seances_prevues'] > 0 ? e($l['retards']) : '<span class="ap-muet">—</span>' !!}</td>
                                <td class="ap-num">
                                    @if($pctNotes !== null)
                                        <div class="ap-ratio" title="{{ $l['notes_recues'] }} notes sur {{ $l['notes_attendues'] }} attendues, {{ $l['evaluations'] }} évaluation(s)">
                                            <span>{{ $pctNotes }} %</span>
                                            <div class="ap-ratio-piste"><div class="ap-ratio-barre {{ $pctNotes < 80 ? 'is-bas' : '' }}" style="width: {{ $pctNotes }}%"></div></div>
                                        </div>
                                    @else <span class="ap-muet">—</span> @endif
                                </td>
                                <td class="ap-num">{!! $l['paiements_saisis'] ? e($l['paiements_saisis']) : '<span class="ap-muet">—</span>' !!}</td>
                                <td class="ap-num">{!! $l['paiements_valides'] ? e($l['paiements_valides']) : '<span class="ap-muet">—</span>' !!}</td>
                                <td class="ap-num">{!! $l['paiements_en_attente'] ? '<span class="ap-alerte">'.e($l['paiements_en_attente']).'</span>' : '<span class="ap-muet">—</span>' !!}</td>
                                <td class="ap-num">{!! $l['inscriptions'] ? e($l['inscriptions']) : '<span class="ap-muet">—</span>' !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <p class="ap-note">{{ $fenetre->libelle }}, du {{ $fenetre->debut->format('d/m/Y') }} au {{ $fenetre->fin->format('d/m/Y') }}. Aucune note n'est attribuée aux personnes : les chiffres se vérifient un par un dans leur détail.</p>
</div>
