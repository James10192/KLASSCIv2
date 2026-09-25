{{--
    Le constat du tableau de bord pédagogique, rendu côté serveur puis injecté.
    Deux blocs : les indicateurs du bandeau et le corps de page.

    @param array  $d              ApercuDuPilotage::construire()
    @param object $annee
    @param string $periodeLabel
    @param int|null $classeFiltree
--}}
@php
    $k = $d['kpis'];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $pctRecues = $k['notes_attendues'] > 0 ? (int) floor(($k['notes_attendues'] - $k['notes_manquantes']) / $k['notes_attendues'] * 100) : null;
    $fenetre = $d['fenetre_presence'];
    $du = \Illuminate\Support\Carbon::parse($fenetre['debut'])->format('d/m');
    $au = \Illuminate\Support\Carbon::parse($fenetre['fin'])->format('d/m');

    // La file de travail se lit par PERSONNE : on appelle un enseignant une
    // fois pour toutes ses classes, pas une fois par matière.
    $parEnseignant = collect($d['relances'])
        ->groupBy(fn ($r) => $r['contact']['name'] ?? '—')
        ->map(fn ($lignes, $nom) => [
            'nom' => $nom === '—' ? null : $nom,
            'telephone' => $lignes->first()['contact']['phone'] ?? null,
            'manquantes' => $lignes->sum('manquantes'),
            'depuis' => $lignes->max('depuis_jours'),
            'lignes' => $lignes->values(),
        ])
        ->sortByDesc('depuis')
        ->values();

    $etats = [
        'complete' => ['Toutes reçues', 'pa-badge--ok'],
        'incomplete' => ['Incomplète', 'pa-badge--alerte'],
        'aucune_evaluation' => ['Rien d’évalué', 'pa-badge--neutre'],
        'evaluations_programmees' => ['Évaluations à venir', 'pa-badge--neutre'],
        'cohorte_vide' => ['Sans étudiant', 'pa-badge--neutre'],
        'referentiel_absent' => ['Maquette absente', 'pa-badge--alerte'],
        'aucune_matiere_ce_semestre' => ['Aucune matière ce semestre', 'pa-badge--neutre'],
    ];

    $_paTendance = [
        'labels' => $d['tendance']['labels'],
        'datasets' => [['label' => 'Présence (%)', 'data' => $d['tendance']['presence'], 'spanGaps' => false]],
    ];
    $_paTendanceOptions = ['scales' => ['y' => ['min' => 0, 'max' => 100]]];
    $_paNotes = [
        'labels' => $d['tendance']['labels'],
        'datasets' => [['label' => 'Notes saisies', 'data' => $d['tendance']['notes']]],
    ];
    $_paPayloadPresence = ['data' => $_paTendance, 'options' => $_paTendanceOptions];
    $_paPayloadNotes = ['data' => $_paNotes, 'options' => new \stdClass()];
    $_paPresenceVide = collect($d['tendance']['presence'])->filter(fn ($v) => $v !== null)->isEmpty();
    $_paNotesVide = collect($d['tendance']['notes'])->sum() === 0;
@endphp

<span data-pa-periode="{{ $periodeLabel }}" hidden></span>

<div data-pa-kpis>
    <button type="button" class="pa-kpi" @click="aller('pa-classes', 'incompletes')">
        <span class="pa-kpi-icone"><i class="fas fa-school-circle-check"></i></span>
        <span class="pa-kpi-texte">
            <span class="pa-kpi-valeur">{{ $k['classes_a_jour'] }}<small> / {{ $k['classes_mesurables'] }}</small></span>
            <span class="pa-kpi-libelle">classes avec toutes leurs notes</span>
        </span>
    </button>
    <button type="button" class="pa-kpi" @click="aller('pa-classes', 'incompletes')">
        <span class="pa-kpi-icone"><i class="fas fa-file-circle-question"></i></span>
        <span class="pa-kpi-texte">
            <span class="pa-kpi-valeur">{{ $fmt($k['notes_manquantes']) }}</span>
            <span class="pa-kpi-libelle">notes manquantes sur {{ $fmt($k['notes_attendues']) }} attendues{{ $pctRecues !== null ? " · {$pctRecues} % reçues" : '' }}</span>
        </span>
    </button>
    <button type="button" class="pa-kpi" @click="aller('pa-file')">
        <span class="pa-kpi-icone"><i class="fas fa-phone-volume"></i></span>
        <span class="pa-kpi-texte">
            <span class="pa-kpi-valeur">{{ $k['enseignants_a_relancer'] }}</span>
            <span class="pa-kpi-libelle">enseignant{{ $k['enseignants_a_relancer'] > 1 ? 's' : '' }} à relancer · {{ $d['relances_total'] }} matière{{ $d['relances_total'] > 1 ? 's' : '' }} en retard de plus de {{ $d['seuils']['relance_jours'] }} j</span>
        </span>
    </button>
    <button type="button" class="pa-kpi" @click="aller('pa-etudiants')">
        <span class="pa-kpi-icone"><i class="fas fa-user-check"></i></span>
        <span class="pa-kpi-texte">
            <span class="pa-kpi-valeur">{{ $k['presence'] !== null ? number_format($k['presence'], 1, ',', ' ').' %' : '—' }}</span>
            <span class="pa-kpi-libelle">{{ $k['appels'] > 0 ? 'de présence sur '.$fmt($k['appels']).' appels, du '.$du.' au '.$au : 'aucun appel fait sur la période' }}</span>
        </span>
    </button>
</div>

<div data-pa-corps>
    <div class="pa-grille">
        {{-- 1. Ce qui attend : les relances, par enseignant --}}
        <section class="pa-card" id="pa-file">
            <div class="pa-card-tete">
                <div class="pa-section-icone"><i class="fas fa-list-check"></i></div>
                <div>
                    <h2>À relancer</h2>
                    <p>Évaluations passées depuis plus de {{ $d['seuils']['relance_jours'] }} jours auxquelles il manque des notes.</p>
                </div>
            </div>

            @forelse($parEnseignant as $ens)
                <div class="pa-relance">
                    <div class="pa-relance-tete">
                        <div class="pa-avatar">{{ $ens['nom'] ? mb_strtoupper(mb_substr($ens['nom'], 0, 1, 'UTF-8'), 'UTF-8') : '?' }}</div>
                        <div class="pa-relance-qui">
                            <strong>{{ $ens['nom'] ?? 'Enseignant non renseigné' }}</strong>
                            <span>{{ $fmt($ens['manquantes']) }} notes manquantes · la plus ancienne évaluation date de {{ $ens['depuis'] }} jours</span>
                        </div>
                        @if($ens['telephone'])
                            <a class="pa-btn pa-btn--ghost pa-btn--compact" href="tel:{{ preg_replace('/[^0-9+]/', '', $ens['telephone']) }}"><i class="fas fa-phone"></i>Appeler</a>
                        @endif
                    </div>
                    <ul class="pa-relance-lignes">
                        @foreach($ens['lignes'] as $r)
                            <li>
                                <span class="pa-relance-quoi">{{ $r['classe'] }} · {{ $r['matiere'] }}</span>
                                <span class="pa-relance-combien">{{ $r['manquantes'] }} note{{ $r['manquantes'] > 1 ? 's' : '' }} · {{ $r['evaluations'] }} éval.</span>
                                <button type="button" class="pa-lien" @click="ouvrirClasse({{ $r['classe_id'] }}, @js($r['classe']))">Voir</button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="pa-vide">
                    <i class="fas fa-circle-check"></i>
                    <strong>Rien à relancer</strong>
                    <span>Aucune évaluation passée depuis plus de {{ $d['seuils']['relance_jours'] }} jours n’attend de notes. Vérifié à {{ now()->format('H:i') }}.</span>
                </div>
            @endforelse

            @if($d['relances_total'] > count($d['relances']))
                <p class="pa-note">{{ $d['relances_total'] - count($d['relances']) }} autre(s) matière(s) en retard dans le tableau des classes ci-dessous.</p>
            @endif
            @if($d['saisies_en_cours'] > 0)
                <p class="pa-note"><i class="fas fa-hourglass-half"></i>{{ $d['saisies_en_cours'] }} évaluation(s) récente(s) en cours de saisie, laissée(s) hors relance.</p>
            @endif
        </section>

        {{-- 2. La tendance, sur le périmètre de la personne connectée --}}
        <section class="pa-card">
            <div class="pa-card-tete">
                <div class="pa-section-icone"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h2>Au fil de l’année</h2>
                    <p>Présence aux appels et notes saisies, mois par mois. Un mois sans appel reste vide.</p>
                </div>
            </div>
            <div class="pa-mini-titre">Présence</div>
            @if($_paPresenceVide)
                <div class="pa-vide pa-vide--compact"><span>Aucun appel enregistré cette année.</span></div>
            @else
                <div class="pa-graphe"><canvas data-chart-type="line" data-chart-payload='@json($_paPayloadPresence)'></canvas></div>
            @endif
            <div class="pa-mini-titre">Notes saisies</div>
            @if($_paNotesVide)
                <div class="pa-vide pa-vide--compact"><span>Aucune note saisie cette année.</span></div>
            @else
                <div class="pa-graphe"><canvas data-chart-type="bar" data-chart-payload='@json($_paPayloadNotes)'></canvas></div>
            @endif
        </section>
    </div>

    {{-- 3. Les classes, la moins avancée d'abord --}}
    <section class="pa-card" id="pa-classes" x-data="{ filtre: 'toutes' }" @pa:filtre-classes.window="filtre = $event.detail">
        <div class="pa-card-tete pa-card-tete--actions">
            <div class="pa-section-icone"><i class="fas fa-school"></i></div>
            <div>
                <h2>Classes</h2>
                <p>Notes reçues sur les évaluations passées de la période, et présence aux appels. Cliquez une classe pour voir matière par matière ce qui manque.</p>
            </div>
            <div class="pa-segments" role="tablist">
                <button type="button" :class="{ 'is-active': filtre === 'toutes' }" @click="filtre = 'toutes'">Toutes <span>{{ count($d['classes']) }}</span></button>
                <button type="button" :class="{ 'is-active': filtre === 'incompletes' }" @click="filtre = 'incompletes'">Incomplètes <span>{{ collect($d['classes'])->where('etat', 'incomplete')->count() }}</span></button>
            </div>
        </div>

        @if(empty($d['classes']))
            <div class="pa-vide"><i class="fas fa-school"></i><strong>Aucune classe</strong><span>Aucune classe n’a d’inscrit actif sur cette année dans votre périmètre.</span></div>
        @else
            <div class="pa-table-wrap">
                <table class="pa-table">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Notes reçues</th>
                            <th class="pa-num">Manquantes</th>
                            <th class="pa-num">Évaluations sans note</th>
                            <th class="pa-num">Matières sans évaluation</th>
                            <th class="pa-num">Présence</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($d['classes'] as $c)
                            @php [$etatLibelle, $etatClasse] = $etats[$c['etat']] ?? ['Indisponible', 'pa-badge--neutre']; @endphp
                            <tr x-show="filtre === 'toutes' || @js($c['etat'] === 'incomplete')" @click="ouvrirClasse({{ $c['id'] }}, @js($c['nom']))" class="pa-ligne">
                                <td>
                                    <div class="pa-classe">
                                        <strong>{{ $c['nom'] }}</strong>
                                        <span><span class="pa-badge {{ $etatClasse }}">{{ $etatLibelle }}</span> {{ $c['systeme'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($c['pourcentage'] !== null)
                                        <div class="pa-jauge" title="{{ $c['recues'] }} sur {{ $c['attendues'] }}">
                                            <div class="pa-jauge-piste"><div class="pa-jauge-barre {{ $c['pourcentage'] < 50 ? 'is-bas' : '' }}" style="width: {{ $c['pourcentage'] }}%"></div></div>
                                            <span>{{ $c['pourcentage'] }} %</span>
                                        </div>
                                    @else
                                        <span class="pa-muet">{{ $c['message'] ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="pa-num">{{ $c['manquantes'] ?: '—' }}</td>
                                <td class="pa-num">{{ $c['evaluations_sans_note'] ?: '—' }}</td>
                                <td class="pa-num">{{ $c['matieres_sans_evaluation'] ? $c['matieres_sans_evaluation'].' / '.$c['matieres_total'] : '—' }}</td>
                                <td class="pa-num">{{ $c['presence'] !== null ? number_format($c['presence'], 1, ',', ' ').' %' : '—' }}</td>
                                <td class="pa-num"><span class="pa-lien">Détail <i class="fas fa-chevron-right"></i></span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- 4. Les étudiants qui ne viennent plus --}}
    <section class="pa-card" id="pa-etudiants">
        <div class="pa-card-tete">
            <div class="pa-section-icone"><i class="fas fa-user-clock"></i></div>
            <div>
                <h2>Étudiants sous {{ $d['seuils']['presence_min'] }} % de présence</h2>
                <p>Du {{ $du }} au {{ $au }}, au moins cinq appels. Le seuil se règle dans les paramètres de l’établissement.</p>
            </div>
        </div>
        @forelse($d['etudiants'] as $e)
            <div class="pa-etudiant">
                <div class="pa-avatar">{{ mb_strtoupper(mb_substr($e['nom'] ?: '?', 0, 1, 'UTF-8'), 'UTF-8') }}</div>
                <div class="pa-etudiant-qui">
                    <strong>{{ $e['nom'] }}</strong>
                    <span>{{ $e['classe'] }}{{ $e['matricule'] ? ' · '.$e['matricule'] : '' }}</span>
                </div>
                <div class="pa-etudiant-chiffres">
                    <strong>{{ number_format($e['taux'], 1, ',', ' ') }} %</strong>
                    <span>{{ $e['absences'] }} absence{{ $e['absences'] > 1 ? 's' : '' }} sur {{ $e['appels'] }} appels · {{ $e['non_justifiees'] }} non justifiée{{ $e['non_justifiees'] > 1 ? 's' : '' }}</span>
                </div>
                @can('students.view')
                    <a class="pa-btn pa-btn--ghost pa-btn--compact" href="{{ route('esbtp.etudiants.show', $e['id']) }}">Fiche</a>
                @endcan
            </div>
        @empty
            <div class="pa-vide">
                <i class="fas fa-circle-check"></i>
                <strong>Aucun étudiant sous le seuil</strong>
                <span>{{ $k['appels'] > 0 ? 'Tous les étudiants suivis sont présents à au moins '.$d['seuils']['presence_min'].' % des appels.' : 'Aucun appel n’a encore été fait sur cette période.' }}</span>
            </div>
        @endforelse
    </section>

    <p class="pa-pied">Calculé à {{ \Illuminate\Support\Carbon::parse($d['calcule_a'])->format('H:i') }}. Les notes se relisent au plus toutes les dix minutes, comme sur les écrans de saisie.</p>
</div>
