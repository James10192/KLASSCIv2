<x-pdf-document
    title="Répertoire pédagogique"
    subtitle="Enseignants et affectations des bulletins BTS"
    :filters="$reportFilters ?? []"
    :orientation="$reportOrientation ?? 'landscape'">
    <style>
        .ens-intro { font-size:9px; line-height:1.55; margin:8px 0 12px; }
        .ens-section { margin:14px 0 7px; font-size:12px; font-weight:700; page-break-after:avoid; }
        .ens-subsection { margin:12px 0 6px; font-size:10px; font-weight:700; page-break-after:avoid; }
        .ens-hint { font-size:8.5px; padding:8px 10px; margin:8px 0 12px; background:#f1f5f9; border-left:3px solid #94a3b8; line-height:1.55; }
        .ens-table { table-layout:fixed; }
        .ens-table thead { display:table-header-group; }
        .ens-table tr { page-break-inside:avoid; }
        .ens-table td { vertical-align:top; padding:5px 6px; font-size:8.5px; overflow-wrap:break-word; word-wrap:break-word; }
        .ens-table th { font-size:7.5px; }
        .ens-table tr:nth-child(even) td { background:#f8fafc; }
        .ens-muted { font-size:7.5px; color:#64748b; }
        .ens-small { font-size:8px; }
        .ens-kpi-label { font-size:7px; }
        .ens-kpi-value { font-size:14px; }
        .ens-strong { font-weight:700; }
        .ens-spacer { margin-top:8px; }
    </style>

    @if($composition === null)
    <table class="pdf-kpi-table" style="page-break-inside:avoid;">
        <tr>
            <td class="pdf-kpi-cell" style="width:25%">
                <div class="pdf-kpi-label">Fiches KLASSCI identifiées</div>
                <div class="pdf-kpi-value">{{ $nombreFiches }}</div>
            </td>
            <td class="pdf-kpi-cell" style="width:25%">
                <div class="pdf-kpi-label">Libellés sans fiche reconnue</div>
                <div class="pdf-kpi-value">{{ $nombreSaisies }}</div>
            </td>
            <td class="pdf-kpi-cell" style="width:25%">
                <div class="pdf-kpi-label">Affectations de bulletin</div>
                <div class="pdf-kpi-value">{{ $affectationsTotal }}</div>
            </td>
            <td class="pdf-kpi-cell" style="width:25%">
                <div class="pdf-kpi-label">Classes avec des données</div>
                <div class="pdf-kpi-value">{{ $nombreClasses }}</div>
            </td>
        </tr>
    </table>

    <p class="ens-intro">
        <strong>Comment lire ce document :</strong>
        une fiche enregistrée est un enseignant présent dans l'annuaire KLASSCI.
        Un libellé saisi sur un bulletin peut être un nom abrégé, une civilité ou une variante
        d'une fiche existante : ce n'est pas la preuve d'une personne supplémentaire.
        Les chiffres suivent les filtres sélectionnés. L'annuaire des fiches n'est pas limité
        à l'année universitaire, contrairement aux données des bulletins.
    </p>

    @endif

    @if($composition !== null)
        <h2 class="ens-section">{{ $vue === 'liste' ? 'Liste épurée des professeurs' : 'Édition personnalisée' }}</h2>
        <p class="ens-small">
            {{ $composition['total'] }} ligne(s) après regroupement.
            Les noms issus des bulletins sont conservés tels que saisis : il ne s'agit pas d'identités fusionnées.
            Les informations de filtre et de périmètre figurent dans l'en-tête du document.
        </p>
        @forelse($composition['groupes'] as $groupe)
            @if($composition['regroupement'] !== 'aucun')
                <h3 class="ens-subsection">{{ $groupe['titre'] }} — {{ count($groupe['lignes']) }} ligne(s)</h3>
            @endif
            <table class="pdf-detail-table ens-table" style="width:100%">
                <thead>
                    <tr>
                        @foreach($composition['entetes'] as $titre)
                            <th>{{ $titre }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupe['lignes'] as $ligne)
                        <tr>
                            @foreach($composition['colonnes'] as $colonne)
                                <td @class(['ens-strong' => $colonne === 'nom'])>{{ $ligne[$colonne] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="ens-hint">Aucune donnée ne correspond à ce périmètre et aux filtres sélectionnés.</p>
        @endforelse
    @elseif($vue === 'synthese')
        <h2 class="ens-section">01 — Repères de pilotage</h2>
        <div class="ens-hint">
            Ce panorama sert à retrouver rapidement <strong>les classes ayant des données</strong>,
            les périodes disponibles et le volume de saisies. Les valeurs S1 et S2 correspondent
            à des affectations <strong>matière / professeur</strong>, pas à des heures enseignées.
            Un même nom peut figurer plusieurs fois dans différentes matières.
        </div>

        <h3 class="ens-subsection">Répartition par classe — année universitaire sélectionnée</h3>
        <table class="pdf-detail-table ens-table" style="width:100%">
            <thead>
                <tr>
                    <th style="width:8%">N°</th>
                    <th style="width:43%">Classe</th>
                    <th style="width:12%;text-align:center">S1</th>
                    <th style="width:12%;text-align:center">S2</th>
                    <th style="width:12%;text-align:center">Total</th>
                    <th style="width:13%;text-align:center">Noms saisis</th>
                </tr>
            </thead>
            <tbody>
                @forelse($repartition as $groupe)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="ens-strong">{{ $groupe['classe'] }}</td>
                        <td style="text-align:center">{{ $groupe['S1'] }}</td>
                        <td style="text-align:center">{{ $groupe['S2'] }}</td>
                        <td style="text-align:center" class="ens-strong">{{ $groupe['total'] }}</td>
                        <td style="text-align:center">{{ $groupe['enseignants_saisis'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Aucune affectation pour ces filtres.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2 class="ens-section">02 — Où trouver les informations ?</h2>
        <table class="pdf-detail-table ens-table">
            <thead><tr><th style="width:35%">Document PDF à sélectionner</th><th>Utilité pour le service des études</th></tr></thead>
            <tbody>
                <tr><td><strong>Annuaire officiel</strong></td><td>Consulter les fiches, matricules, spécialités, régimes et contacts exploitables des enseignants enregistrés.</td></tr>
                <tr><td><strong>Noms à rapprocher</strong></td><td>Identifier les noms libres utilisés dans les bulletins, leurs classes et les écritures potentiellement équivalentes, sans créer de compte ni fusionner de personnes.</td></tr>
                <tr><td><strong>Affectations par classe</strong></td><td>Après sélection d'une classe et éventuellement d'un semestre, imprimer la liste des matières et du professeur tel qu'indiqué sur le bulletin.</td></tr>
                <tr><td><strong>Matières regroupées</strong></td><td>Voir pour chaque matière les professeurs saisis sur les bulletins de toutes les classes, avec les classes et semestres concernés.</td></tr>
                <tr><td><strong>Répertoire par spécialisation</strong></td><td>Regrouper les fiches KLASSCI par discipline déclarée et isoler les noms sans spécialisation.</td></tr>
            </tbody>
        </table>
        <div class="ens-hint">Ce document est une synthèse. Il n'omet aucune classe dans les comptages, mais ne reproduit pas les {{ $affectationsTotal }} lignes de détail. Pour le détail, choisissez le document « Affectations par classe » dans les filtres d'export.</div>

    @elseif($vue === 'annuaire')
        <h2 class="ens-section">Annuaire — fiches enseignants KLASSCI</h2>
        <div class="ens-hint">Uniquement les fiches enregistrées dans KLASSCI. Les noms saisis seulement dans les bulletins ne figurent pas ici. Les adresses provisoires de type « NEANT » et les contacts non valables ne sont pas imprimés ; les données d'origine restent intactes.</div>
        <table class="pdf-detail-table ens-table">
            <thead>
                <tr>
                    <th style="width:5%">N°</th>
                    <th style="width:26%">Nom et prénoms</th>
                    <th style="width:15%">Matricule</th>
                    <th style="width:20%">Discipline / spécialité</th>
                    <th style="width:12%">Régime</th>
                    <th style="width:8%">Statut</th>
                    <th style="width:14%">Contact</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fiches as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="ens-strong">{{ $row['nom'] }}</td>
                        <td>{{ $row['matricule'] }}</td>
                        <td>{{ $row['specialisation'] }}</td>
                        <td>{{ $row['regime'] }}</td>
                        <td>{{ $row['statut'] }}</td>
                        <td>
                            {{ $row['telephone_affiche'] }}
                            @if($row['email_affiche'] !== '—')
                                <div class="ens-muted">{{ $row['email_affiche'] }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Aucune fiche enseignant trouvée pour ces filtres.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="ens-muted">Une spécialité correspond à la fiche enseignant, et non nécessairement à la matière enseignée cette année. La vue « Affectations par classe » fournit les matières exactes.</p>

    @elseif($vue === 'rapprochement')
        <h2 class="ens-section">Noms des bulletins à rapprocher</h2>
        <div class="ens-hint">Ces {{ $nombreSaisies }} libellés n'ont pas de correspondance exacte avec une fiche enseignant dans le périmètre des filtres. <strong>Ce ne sont pas {{ $nombreSaisies }} personnes supplémentaires confirmées.</strong> Vérifier l'identité auprès des responsables avant toute création ou modification de fiche.</div>

        @if(count($pistes) > 0)
            <h3 class="ens-subsection">Écritures possiblement équivalentes — vérification humaine requise</h3>
            <table class="pdf-detail-table ens-table">
                <thead><tr><th style="width:10%">N°</th><th>Variantes à comparer, sans rapprochement automatique</th></tr></thead>
                <tbody>
                    @foreach(array_slice($pistes, 0, 30) as $piste)
                        <tr><td>{{ $loop->iteration }}</td><td>{{ $piste }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            @if(count($pistes) > 30)
                <p class="ens-muted">{{ count($pistes) - 30 }} autres groupes non détaillés ici : utiliser l'export Excel pour traiter le répertoire complet.</p>
            @endif
        @endif

        <h3 class="ens-subsection">Répertoire des saisies, classé par initiale</h3>
        @forelse($saisiesGroupes as $initiale => $groupe)
            <h3 class="ens-subsection">{{ $initiale }} — {{ count($groupe) }} libellé(s)</h3>
            <table class="pdf-detail-table ens-table">
                <thead><tr>
                    <th style="width:7%">N°</th>
                    <th style="width:35%">Nom tel que saisi</th>
                    <th style="width:46%">Classe(s) repère(s)</th>
                    <th style="width:12%;text-align:center">Affectations</th>
                </tr></thead>
                <tbody>
                    @foreach($groupe as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="ens-strong">{{ $row['nom'] }}</td>
                            <td>
                                {{ \Illuminate\Support\Str::limit($row['classes'] ?: 'Non précisée', 130) }}
                                @if(mb_strlen($row['classes'] ?? '', 'UTF-8') > 130)
                                    <div class="ens-muted">Aperçu limité — {{ $row['classes_nombre'] }} classes au total. Liste intégrale dans Excel.</div>
                                @endif
                            </td>
                            <td style="text-align:center">{{ $row['affectations_nombre'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="ens-hint">Aucune saisie isolée : tous les noms trouvés ont une correspondance exacte avec une fiche enregistrée, sous réserve des filtres appliqués.</p>
        @endforelse


    @elseif($vue === 'specialisations')
        <h2 class="ens-section">Répertoire par spécialisation — fiches enseignants KLASSCI</h2>
        <div class="ens-hint">Les disciplines ci-dessous viennent des fiches des enseignants enregistrés. Une spécialisation n'est pas nécessairement la matière enseignée sur un bulletin. Seules les différences de casse et d'espacement sont regroupées ; aucune discipline voisine n'est fusionnée.</div>
        <table class="pdf-detail-table ens-table">
            <thead><tr><th>Spécialisation</th><th style="width:16%;text-align:center">Enseignants</th></tr></thead>
            <tbody>
                @forelse($specialisationsGroupes as $groupe)
                    <tr><td class="ens-strong">{{ $groupe['nom'] }}</td><td style="text-align:center">{{ $groupe['total'] }}</td></tr>
                @empty
                    <tr><td colspan="2">Aucune spécialisation renseignée.</td></tr>
                @endforelse
            </tbody>
        </table>
        @foreach($specialisationsGroupes as $groupe)
            <h3 class="ens-subsection">{{ $groupe['nom'] }} — {{ $groupe['total'] }} enseignant(s)</h3>
            <table class="pdf-detail-table ens-table">
                <thead><tr>
                    <th style="width:6%">N°</th>
                    <th style="width:27%">Nom et prénoms</th>
                    <th style="width:16%">Matricule</th>
                    <th style="width:13%">Régime</th>
                    <th style="width:10%">Statut</th>
                    <th>Contact valable</th>
                </tr></thead>
                <tbody>
                    @foreach($groupe['enseignants'] as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="ens-strong">{{ $row['nom'] }}</td>
                            <td>{{ $row['matricule'] }}</td>
                            <td>{{ $row['regime'] }}</td>
                            <td>{{ $row['statut'] }}</td>
                            <td>{{ $row['telephone_affiche'] }}
                                @if($row['email_affiche'] !== '—')
                                    <div class="ens-muted">{{ $row['email_affiche'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
        <h3 class="ens-subsection">À classer — {{ count($sansSpecialisation) }} libellé(s) sans spécialisation renseignée</h3>
        <div class="ens-hint">Cette annexe comprend les fiches sans spécialité et les noms saisis uniquement dans les bulletins. Ces libellés peuvent désigner des variantes d'une même personne : ne pas les fusionner ou attribuer une matière automatiquement.</div>
        <table class="pdf-detail-table ens-table">
            <thead><tr>
                <th style="width:7%">N°</th>
                <th style="width:40%">Nom ou libellé</th>
                <th style="width:16%">Matricule</th>
                <th>Origine</th>
            </tr></thead>
            <tbody>
                @forelse($sansSpecialisation as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="ens-strong">{{ $row['nom'] }}</td>
                        <td>{{ $row['matricule'] }}</td>
                        <td>{{ $row['origine'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucune entrée à classer.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif($vue === 'matieres')
        <h2 class="ens-section">Matières et professeurs — regroupement transversal</h2>
        <div class="ens-hint">Les matières et les noms viennent des configurations des bulletins BTS pour l'année sélectionnée. Regroupement sur toutes les classes si aucune classe n'est choisie. Chaque professeur est affiché une fois par matière avec le détail des classes et semestres ; les variantes de nom restent distinctes, aucune identité n'est supposée.</div>
        <h3 class="ens-subsection">Sommaire des matières — {{ $nombreMatieres }} matière(s)</h3>
        <table class="pdf-detail-table ens-table">
            <thead><tr><th style="width:58%">Matière du bulletin</th><th style="width:21%;text-align:center">Professeurs saisis</th><th style="width:21%;text-align:center">Affectations</th></tr></thead>
            <tbody>
                @forelse($matieresGroupes as $groupe)
                    <tr><td class="ens-strong">{{ $groupe['matiere'] }}</td><td style="text-align:center">{{ $groupe['nombreEnseignants'] }}</td><td style="text-align:center">{{ $groupe['total'] }}</td></tr>
                @empty
                    <tr><td colspan="3">Aucune matière avec professeur saisi pour ce périmètre.</td></tr>
                @endforelse
            </tbody>
        </table>
        @foreach($matieresGroupes as $groupe)
            <h3 class="ens-subsection">{{ $groupe['matiere'] }} — {{ $groupe['nombreEnseignants'] }} professeur(s), {{ $groupe['total'] }} affectation(s)</h3>
            <table class="pdf-detail-table ens-table">
                <thead><tr>
                    <th style="width:5%">N°</th>
                    <th style="width:27%">Professeur tel que saisi</th>
                    <th style="width:46%">Classe(s) et semestre(s)</th>
                    <th style="width:7%;text-align:center">S1</th>
                    <th style="width:7%;text-align:center">S2</th>
                    <th style="width:8%;text-align:center">Total</th>
                </tr></thead>
                <tbody>
                    @foreach($groupe['enseignants'] as $prof)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="ens-strong">{{ $prof['nom'] }}</td>
                            <td>{{ $prof['classes'] }}</td>
                            <td style="text-align:center">{{ $prof['S1'] }}</td>
                            <td style="text-align:center">{{ $prof['S2'] }}</td>
                            <td style="text-align:center" class="ens-strong">{{ $prof['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

    @elseif($vue === 'affectations')
        <h2 class="ens-section">Affectations des bulletins — détail par classe</h2>
        <div class="ens-hint">Les noms ci-dessous sont reproduits tels qu'ils ont été saisis dans les bulletins de la classe sélectionnée. S1 et S2 sont séparés pour faciliter les contrôles du service des études. Aucune association automatique à un compte n'est effectuée.</div>
        @foreach(['S1' => 'Semestre 1', 'S2' => 'Semestre 2'] as $periode => $nomPeriode)
            <h3 class="ens-subsection">{{ $nomPeriode }} — {{ count($affectationsSemestres[$periode]) }} affectation(s)</h3>
            <table class="pdf-detail-table ens-table">
                <thead>
                    <tr>
                        <th style="width:7%">N°</th>
                        <th style="width:25%">Classe</th>
                        <th style="width:36%">Matière</th>
                        <th style="width:32%">Professeur indiqué sur le bulletin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($affectationsSemestres[$periode] as $affectation)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $affectation['classe'] }}</td>
                            <td>{{ $affectation['matiere'] }}</td>
                            <td class="ens-strong">{{ $affectation['nom'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Aucune affectation renseignée pour ce semestre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
    @endif
</x-pdf-document>
