<x-pdf-document
    title="Répertoire pédagogique"
    subtitle="Enseignants et affectations des bulletins BTS"
    :filters="$reportFilters ?? []"
    orientation="landscape">
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

    @if($vue === 'synthese')
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
