<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPTeacher;
use App\Services\PlanningConfigurationService;
use App\Services\PlanningStatisticsService;
use App\Http\Requests\Planning\StorePlanificationRequest;
use App\Http\Requests\Planning\GenerateCodeEmargementRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ESBTPPlanningGeneralController extends Controller
{
    protected $planningConfigService;
    protected $planningStatsService;

    public function __construct(
        PlanningConfigurationService $planningConfigService,
        PlanningStatisticsService $planningStatsService,
    ) {
        $this->middleware("auth");
        $this->middleware('permission:module.emploi_temps.access');
        $this->planningConfigService = $planningConfigService;
        $this->planningStatsService = $planningStatsService;
    }

    /**
     * Interface de test pour planification académique
     */
    public function indexTest(Request $request)
    {
        // Utilise la même logique que index() mais force la vue de test
        $result = $this->index($request);
        $data = $result->getData();

        return view("esbtp.planning-general.index-test", $data);
    }

    /**
     * Interface principale de planification académique
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Récupérer l'année universitaire sélectionnée ou celle en cours
        $anneeId = $request->input("annee_id");

        if (!$anneeId) {
            $anneeEnCours = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();
            $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
        }

        // Données de base
        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId);

        // Statistiques générales pour la vue index
        $stats = $this->planningStatsService->calculerStatistiquesGenerales($anneeId);

        // Récupérer les filtres
        $filiereFilter = $request->input("filiere_filter");
        $niveauFilter = $request->input("niveau_filter");

        // Récupérer toutes les combinaisons filière/niveau avec leurs matières
        $combinaisons = $this->getCombinaisonsAvecMatieres(
            $anneeId,
            $filiereFilter,
            $niveauFilter,
        );

        return view(
            "esbtp.planning-general.index",
            compact("annees", "anneeSelectionnee", "stats", "combinaisons"),
        );
    }

    /**
     * Récupérer toutes les combinaisons filière/niveau avec leurs statistiques de configuration
     */
    private function getCombinaisonsAvecMatieres(
        $anneeId,
        $filiereFilter = null,
        $niveauFilter = null,
    ) {
        // Récupérer les combinaisons avec filtres optionnels
        $filieres = ESBTPFiliere::where("is_active", true);
        if ($filiereFilter) {
            $filieres->where("id", $filiereFilter);
        }
        $filieres = $filieres->orderBy("name")->get();

        $niveaux = ESBTPNiveauEtude::where("is_active", true);
        if ($niveauFilter) {
            $niveaux->where("id", $niveauFilter);
        }
        $niveaux = $niveaux->orderBy("year")->get();

        $combinaisons = [];

        foreach ($filieres as $filiere) {
            foreach ($niveaux as $niveau) {
                // Compter les planifications pour cette combinaison
                $planifications = ESBTPPlanificationAcademique::where(
                    "filiere_id",
                    $filiere->id,
                )->where("niveau_etude_id", $niveau->id);

                if ($anneeId) {
                    $planifications->where("annee_universitaire_id", $anneeId);
                }

                $planifications = $planifications->with("matiere")->get();

                // Pré-charger les matière IDs liées à cette combinaison (1 requête au lieu de N)
                $linkedMatiereIds = \App\Models\ESBTPMatiereFilierNiveau::matiereIdsForCombo($filiere->id, $niveau->id);

                $planificationsValides = $planifications->filter(function ($planification) use ($linkedMatiereIds) {
                    return $planification->matiere && $linkedMatiereIds->contains($planification->matiere->id);
                });

                $matieresLieesALaCombinaisonCount = \App\Models\ESBTPMatiereFilierNiveau::activeMatiereCountForCombo($filiere->id, $niveau->id);

                // Calculer les statistiques
                $totalMatieres = $matieresLieesALaCombinaisonCount; // Toutes les matières liées à cette combinaison
                $totalHeures = $planificationsValides->sum(
                    "volume_horaire_total",
                );
                $matieresConfigurees = $planificationsValides
                    ->where("volume_horaire_total", ">", 0)
                    ->count(); // Matières liées ET configurées
                $planificationsS1 = $planificationsValides->where(
                    "semestre",
                    1,
                );
                $planificationsS2 = $planificationsValides->where(
                    "semestre",
                    2,
                );
                $matieresConfigureesS1 = $planificationsS1
                    ->where("volume_horaire_total", ">", 0)
                    ->count();
                $matieresConfigureesS2 = $planificationsS2
                    ->where("volume_horaire_total", ">", 0)
                    ->count();
                $totalHeuresS1 = $planificationsS1->sum("volume_horaire_total");
                $totalHeuresS2 = $planificationsS2->sum("volume_horaire_total");

                // Déterminer le statut
                $statusClass = "";
                $statusIcon = "";
                $statusText = "";

                if ($totalMatieres == 0) {
                    $statusClass = "not-configured";
                    $statusIcon = "fa-plus-circle";
                    $statusText = "Non configuré";
                } elseif ($matieresConfigurees == $totalMatieres) {
                    $statusClass = "configured";
                    $statusIcon = "fa-check-circle";
                    $statusText = "Complet";
                } else {
                    $statusClass = "partial";
                    $statusIcon = "fa-exclamation-triangle";
                    $statusText = "Partiel";
                }

                $isTc = $filiere->isTroncCommun();
                $combinaisons[] = [
                    "filiere" => $filiere,
                    "niveau" => $niveau,
                    "name" => ($isTc ? '[TC] ' : '') . $filiere->name . " - " . $niveau->name,
                    "is_tronc_commun" => $isTc,
                    "total_matieres" => $totalMatieres,
                    "total_heures" => $totalHeures,
                    "matieres_configurees" => $matieresConfigurees,
                    "matieres_configurees_s1" => $matieresConfigureesS1,
                    "matieres_configurees_s2" => $matieresConfigureesS2,
                    "total_heures_s1" => $totalHeuresS1,
                    "total_heures_s2" => $totalHeuresS2,
                    "status_class" => $statusClass,
                    "status_icon" => $statusIcon,
                    "status_text" => $statusText,
                    "planifications" => $planificationsValides,
                ];
            }
        }

        return collect($combinaisons)->sortBy("name");
    }

    /**
     * Vue coordinateur - Vue d'ensemble avec options avancées
     */
    private function indexCoordinateur(
        $anneeId,
        $annees,
        $anneeSelectionnee,
        $stats,
    ) {
        // Pour la vue d'ensemble, on utilise la vue index.blade.php
        // mais avec des données supplémentaires pour les coordinateurs

        // Répartition des heures par matière
        $repartitionMatieres = $this->planningStatsService->calculerRepartitionMatieres($anneeId);

        // Emplois du temps par classe
        $emploisTempsClasses = $this->getEmploisTempsParClasse($anneeId);

        // Progression vs objectifs
        $progressionObjectifs = $this->calculerProgressionObjectifs($anneeId);

        // Classes avec conflits d'horaires
        $conflitsHoraires = $this->detecterConflitsHoraires($anneeId);

        return view(
            "esbtp.planning-general.index",
            compact(
                "annees",
                "anneeSelectionnee",
                "stats",
                "repartitionMatieres",
                "emploisTempsClasses",
                "progressionObjectifs",
                "conflitsHoraires",
            ),
        );
    }

    /**
     * Vue enseignant - Planning personnel
     */
    private function indexEnseignant(
        $anneeId,
        $annees,
        $anneeSelectionnee,
        $stats,
    ) {
        $user = Auth::user();

        // Séances de l'enseignant
        $seancesEnseignant = ESBTPSeanceCours::where("teacher_id", $user->id)
            ->where("type", ESBTPSeanceCours::TYPE_COURSE)
            ->whereHas("emploiTemps", function ($query) use ($anneeId) {
                if ($anneeId) {
                    $query->where("annee_universitaire_id", $anneeId);
                }
            })
            ->with(["matiere", "classe", "emploiTemps"])
            ->orderBy("jour")
            ->orderBy("heure_debut")
            ->get();

        // Grouper par semaine et jour
        $planningHebdomadaire = $this->grouperSeancesParSemaine(
            $seancesEnseignant,
        );

        // Charge horaire par matière
        $chargeHoraireMatiere = $this->planningStatsService->calculerChargeHoraireEnseignant(
            $user->id,
            $anneeId,
        );

        return view(
            "esbtp.planning-general.enseignant",
            compact(
                "annees",
                "anneeSelectionnee",
                "stats",
                "seancesEnseignant",
                "planningHebdomadaire",
                "chargeHoraireMatiere",
            ),
        );
    }

    /**
     * Vue étudiant - Planning de classe
     */
    private function indexEtudiant(
        $anneeId,
        $annees,
        $anneeSelectionnee,
        $stats,
    ) {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where("user_id", $user->id)->first();

        if (!$etudiant) {
            return view(
                "esbtp.planning-general.etudiant-no-profile",
                compact("annees", "anneeSelectionnee"),
            );
        }

        // Inscription active pour l'année sélectionnée
        $inscription = $etudiant
            ->inscriptions()
            ->where("status", "active")
            ->where("annee_universitaire_id", $anneeId)
            ->first();

        if (!$inscription) {
            return view(
                "esbtp.planning-general.etudiant-no-inscription",
                compact("annees", "anneeSelectionnee", "etudiant"),
            );
        }

        // Emploi du temps de la classe
        $emploiTemps = ESBTPEmploiTemps::where(
            "classe_id",
            $inscription->classe_id,
        )
            ->where("is_current", true)
            ->first();

        $seancesClasse = $emploiTemps
            ? $emploiTemps
                ->seances()
                ->with(["matiere", "enseignant"])
                ->orderBy("jour")
                ->orderBy("heure_debut")
                ->get()
            : collect();

        // Planning hebdomadaire
        $planningHebdomadaire = $this->grouperSeancesParJour($seancesClasse);

        return view(
            "esbtp.planning-general.etudiant",
            compact(
                "annees",
                "anneeSelectionnee",
                "stats",
                "etudiant",
                "inscription",
                "emploiTemps",
                "seancesClasse",
                "planningHebdomadaire",
            ),
        );
    }

    /**
     * Vue annuelle - Calendrier complet de l'année
     */
    public function annuel(Request $request)
    {
        $anneeId = $request->input("annee_id");
        $anneeSelectionnee =
            ESBTPAnneeUniversitaire::find($anneeId) ??
            ESBTPAnneeUniversitaire::where("is_current", true)->first();

        if (!$anneeSelectionnee) {
            return redirect()
                ->route("esbtp.planning-general.index")
                ->with("error", "Aucune année universitaire trouvée.");
        }

        // Calendrier mensuel de l'année
        $calendrierMensuel = $this->genererCalendrierAnnuel($anneeSelectionnee);

        // Événements académiques importants
        $evenementsAcademiques = $this->getEvenementsAcademiques(
            $anneeSelectionnee,
        );

        // Statistiques par mois
        $statistiquesMensuelles = $this->calculerStatistiquesMensuelles(
            $anneeSelectionnee,
        );

        // Toutes les années pour le sélecteur
        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();

        // Événements Eloquent pour la table CRUD
        $evenementsModels = \App\Models\ESBTPEvenementAcademique::where('annee_universitaire_id', $anneeSelectionnee->id)
            ->orderBy('date_debut', 'asc')
            ->get();

        // Stats globales pour le hero header
        $stats = $this->planningStatsService->calculerStatistiquesGenerales($anneeSelectionnee->id);

        return view(
            "esbtp.planning-general.annuel",
            compact(
                "anneeSelectionnee",
                "calendrierMensuel",
                "evenementsAcademiques",
                "statistiquesMensuelles",
                "annees",
                "evenementsModels",
                "stats",
            ),
        );
    }

    /**
     * Répartition des heures par matière
     */
    public function repartitionMatieres(Request $request)
    {
        $anneeId = $request->input("annee_id");
        $classeId = $request->input("classe_id");
        $filiereId = $request->input("filiere_id");
        $niveauId = $request->input("niveau_id");
        $search = $request->input("search");
        $periode = $request->input("periode", "annee"); // semestre1, semestre2, ou annee

        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();

        // Gérer la sélection d'année
        if (empty($anneeId) || $anneeId === "all") {
            $anneeSelectionnee = ESBTPAnneeUniversitaire::where(
                "is_current",
                true,
            )->first();
            if (!$anneeSelectionnee && $annees->count() > 0) {
                $anneeSelectionnee = $annees->first();
            }
            $anneeIdPourCalcul = null;
        } else {
            $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId);
            $anneeIdPourCalcul = $anneeId;
        }

        // Toutes les classes (pour les selects de filtres)
        $classes = ESBTPClasse::with(["filiere", "niveau"])
            ->orderBy("name")
            ->get();

        // Filières et niveaux pour les selects de filtrage
        $filieres = ESBTPFiliere::orderBy("name")->get();
        $niveaux = ESBTPNiveauEtude::orderBy("name")->get();

        // Construire la liste des classes filtrées
        $filteredClassIds = null;
        if ($classeId || $filiereId || $niveauId || $search) {
            $classFilterQuery = ESBTPClasse::query();
            if ($classeId) {
                $classFilterQuery->where('id', $classeId);
            }
            if ($filiereId) {
                $classFilterQuery->where('filiere_id', $filiereId);
            }
            if ($niveauId) {
                $classFilterQuery->where('niveau_etude_id', $niveauId);
            }
            if ($search) {
                $classFilterQuery->where('name', 'like', '%' . $search . '%');
            }
            $filteredClassIds = $classFilterQuery->pluck('id')->toArray();
        }

        $repartition = $this->calculerRepartitionMatieresParClasse(
            $anneeIdPourCalcul,
            $periode,
            $filteredClassIds,
        );
        $statsRepartition = $this->calculerStatsRepartitionParClasse(
            $repartition,
        );
        $chartData = $this->buildChartDataParClasse($repartition);

        // Comparaison avec les objectifs
        $objectifsComparaison = $this->comparerAvecObjectifs(
            $repartition,
            $classeId,
            $anneeIdPourCalcul,
        );

        $stats = $this->planningStatsService->calculerStatistiquesGenerales($anneeSelectionnee?->id);

        return view(
            "esbtp.planning-general.repartition-matieres",
            compact(
                "annees",
                "anneeSelectionnee",
                "classes",
                "filieres",
                "niveaux",
                "repartition",
                "objectifsComparaison",
                "anneeId",
                "classeId",
                "filiereId",
                "niveauId",
                "search",
                "statsRepartition",
                "chartData",
                "stats",
            ),
        );
    }

    /**
     * Planning par coordinateur - Interface de gestion
     */
    public function coordinateur(Request $request)
    {
        if (!Auth::user()->hasAnyPermission(["admin.access", "identity.coordinate"])) {
            abort(403, "Accès réservé aux coordinateurs.");
        }

        $anneeId = $request->input("annee_id");
        $mois = $request->input("mois", now()->month);

        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();
        $anneeSelectionnee =
            ESBTPAnneeUniversitaire::find($anneeId) ??
            ESBTPAnneeUniversitaire::where("is_current", true)->first();

        // Allocation horaire par module
        $allocationHoraire = $this->getAllocationHoraireModules($anneeId);

        // Programmation hebdomadaire
        $programmationHebdomadaire = $this->getProgrammationHebdomadaire(
            $anneeId,
            $mois,
        );

        // Codes d'émargement actifs
        $codesEmargement = $this->getCodesEmargementActifs();

        // Taux de présence par classe
        $tauxPresenceClasses = $this->calculerTauxPresenceClasses($anneeId);

        $stats = $this->planningStatsService->calculerStatistiquesGenerales($anneeSelectionnee?->id);

        return view(
            "esbtp.planning-general.coordinateur",
            compact(
                "annees",
                "anneeSelectionnee",
                "allocationHoraire",
                "programmationHebdomadaire",
                "codesEmargement",
                "tauxPresenceClasses",
                "mois",
                "stats",
            ),
        );
    }

    // ============ MÉTHODES PRIVÉES DE CALCUL ============

    /**
     * Groupe les séances par jour de la semaine
     */
    private function grouperSeancesParJour($seances)
    {
        $jours = [
            0 => "Lundi",
            1 => "Mardi",
            2 => "Mercredi",
            3 => "Jeudi",
            4 => "Vendredi",
            5 => "Samedi",
        ];

        $planning = [];
        foreach ($jours as $numero => $nom) {
            $planning[$nom] = $seances
                ->where("jour", $numero)
                ->sortBy("heure_debut");
        }

        return $planning;
    }

    /**
     * Génère le calendrier annuel par mois
     */
    private function genererCalendrierAnnuel($annee)
    {
        // Créer des dates complètes à partir des années
        $debut = Carbon::create($annee->start_date, 9, 1); // 1er septembre de l'année de début
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin de l'année de fin

        $calendrier = [];
        $moisCourant = $debut->copy()->startOfMonth();

        while ($moisCourant->lte($fin)) {
            $calendrier[] = [
                "mois" => $moisCourant->format("Y-m"),
                "nom" => $moisCourant->translatedFormat("F Y"),
                "semaines" => $this->genererSemainesMois($moisCourant),
            ];

            $moisCourant->addMonth();
        }

        return $calendrier;
    }

    /**
     * Génère les semaines d'un mois
     */
    private function genererSemainesMois($mois)
    {
        $debut = $mois->copy()->startOfMonth()->startOfWeek();
        $fin = $mois->copy()->endOfMonth()->endOfWeek();

        $semaines = [];
        $semaineActuelle = $debut->copy();

        while ($semaineActuelle->lte($fin)) {
            $jours = [];
            for ($i = 0; $i < 7; $i++) {
                $jours[] = [
                    "date" => $semaineActuelle->copy(),
                    "dans_mois" => $semaineActuelle->month === $mois->month,
                    "est_aujourd_hui" => $semaineActuelle->isToday(),
                ];
                $semaineActuelle->addDay();
            }
            $semaines[] = $jours;
        }

        return $semaines;
    }

    /**
     * Méthodes placeholder pour les fonctionnalités avancées
     */
    private function getEmploisTempsParClasse($anneeId)
    {
        return collect();
    }

    private function calculerProgressionObjectifs($anneeId)
    {
        return [];
    }

    private function detecterConflitsHoraires($anneeId)
    {
        return [];
    }

    private function grouperSeancesParSemaine($seances)
    {
        return [];
    }

    /**
     * Interface d'émargement intégrée au planning général
     */
    public function emargement(Request $request)
    {
        $anneeId = $request->input("annee_id");
        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();
        $anneeSelectionnee = $anneeId
            ? ESBTPAnneeUniversitaire::find($anneeId)
            : ESBTPAnneeUniversitaire::where("is_current", true)->first();

        // Codes actifs (peut y en avoir plusieurs maintenant)
        $activeCodes = ESBTPDailyCode::with(["seance.matiere", "seance.classe"])
            ->where("status", "active")
            ->where("valid_until", ">", now())
            ->orderBy("created_at", "desc")
            ->get();

        // Code actif principal (pour compatibilité avec la vue existante)
        $activeCode = $activeCodes->first();

        // Codes récents
        $recentCodes = ESBTPDailyCode::with("generator")
            ->orderBy("created_at", "desc")
            ->take(10)
            ->get();

        // Récupérer les séances à venir (aujourd'hui et dans les 3 prochains jours)
        $seancesAVenir = collect();
        if ($anneeSelectionnee) {
            $today = now();
            $todayDayOfWeek = $today->dayOfWeek === 0 ? 7 : $today->dayOfWeek; // Dimanche = 7, Lundi = 1

            $seancesAVenir = ESBTPSeanceCours::with([
                "matiere",
                "classe",
                "teacher",
                "emploiTemps",
            ])
                ->whereHas("emploiTemps", function ($query) use (
                    $anneeSelectionnee,
                ) {
                    $query
                        ->where(
                            "annee_universitaire_id",
                            $anneeSelectionnee->id,
                        )
                        ->where(function ($subQuery) {
                            $subQuery
                                ->where("is_active", true)
                                ->orWhere("is_current", true);
                        });
                })
                ->where("is_active", true)
                ->where(function ($query) use ($today, $todayDayOfWeek) {
                    // Séances avec date précise (aujourd'hui et 3 prochains jours)
                    $query
                        ->whereBetween("date_seance", [
                            $today->format("Y-m-d"),
                            $today->copy()->addDays(3)->format("Y-m-d"),
                        ])
                        // Ou séances récurrentes pour aujourd'hui et prochains jours
                        ->orWhere(function ($subQuery) use ($todayDayOfWeek) {
                            $subQuery
                                ->whereNull("date_seance")
                                ->where("is_recurring", true)
                                ->where(function ($dayQuery) use (
                                    $todayDayOfWeek,
                                ) {
                                    // Aujourd'hui et les 3 prochains jours de la semaine
                                    for ($i = 0; $i < 4; $i++) {
                                        $day =
                                            (($todayDayOfWeek + $i - 1) % 7) +
                                            1;
                                        if ($day > 6) {
                                            $day = $day - 6;
                                        } // Samedi max
                                        $dayQuery->orWhere("jour", $day);
                                    }
                                });
                        })
                        // Ou séances d'aujourd'hui (récurrentes sans date précise)
                        ->orWhere(function ($subQuery) use ($todayDayOfWeek) {
                            $subQuery
                                ->where("jour", $todayDayOfWeek)
                                ->whereNull("date_seance");
                        });
                })
                ->whereIn("type", ["course", "td", "tp", "cm"]) // Types de cours (pas pauses)
                ->orderByRaw(
                    "CASE WHEN date_seance IS NOT NULL THEN date_seance ELSE CURDATE() END",
                )
                ->orderBy("heure_debut")
                ->take(10)
                ->get();
        }

        // Statistiques des émargements
        $stats = $this->planningStatsService->calculerStatsEmargement($anneeSelectionnee);

        // Stats globales pour le hero header
        $heroStats = $this->planningStatsService->calculerStatistiquesGenerales($anneeSelectionnee?->id);

        return view(
            "esbtp.planning-general.emargement",
            compact(
                "annees",
                "anneeSelectionnee",
                "activeCode",
                "activeCodes",
                "recentCodes",
                "stats",
                "seancesAVenir",
                "heroStats",
            ),
        );
    }

    /**
     * Génère un nouveau code d'émargement depuis l'interface planning
     */
    public function genererCodeEmargement(GenerateCodeEmargementRequest $request)
    {
        try {
            $seanceId = $request->input("seance_id");

            // Logique d'invalidation intelligente
            if ($seanceId) {
                // Si un code est créé pour une séance spécifique, invalider seulement les codes pour cette même séance
                ESBTPDailyCode::where("status", "active")
                    ->where("seance_id", $seanceId)
                    ->update(["status" => "expired"]);
            } else {
                // Si un code générique est créé, invalider seulement les codes génériques (sans seance_id)
                ESBTPDailyCode::where("status", "active")
                    ->whereNull("seance_id")
                    ->update(["status" => "expired"]);
            }

            // Calculer les dates d'activation et d'expiration
            $activation = $request->input("activation", "immediate");
            $duree = (int) $request->input("duree", 2);

            $validFrom =
                $activation === "immediate"
                    ? now()
                    : now()->addHours((int) $activation);
            $validUntil = $validFrom->copy()->addHours($duree);

            // Générer le code
            $codeData = [
                "code" => ESBTPDailyCode::generateCode(),
                "valid_from" => $validFrom,
                "valid_until" => $validUntil,
                "is_active" => $activation === "immediate",
                "status" =>
                    $activation === "immediate" ? "active" : "scheduled",
                "created_by" => auth()->id(),
                "description" => $request->input("description"),
                "type" => $request->input("type"),
            ];

            // Ajouter l'ID de la séance si fourni
            if ($request->filled("seance_id")) {
                $codeData["seance_id"] = $request->input("seance_id");
            }

            $code = ESBTPDailyCode::create($codeData);

            $message = "Nouveau code généré avec succès : " . $code->code;

            if ($activation !== "immediate") {
                $heuresActivation = (int) $activation;
                $message .= " (activation dans {$heuresActivation}h)";
            }

            $message .= " - Valide pendant {$duree}h";

            return redirect()
                ->route("esbtp.planning-general.emargement", [
                    "annee_id" => $request->input("annee_id"),
                ])
                ->with("success", $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la génération du code : " .
                        $e->getMessage(),
                );
        }
    }

    /**
     * Calcule la répartition des matières par classe
     */
    private function calculerRepartitionMatieresParClasse(
        $anneeId,
        $periode = "annee",
        $classeIds = null,
    ) {
        $classesQuery = ESBTPClasse::with(["filiere", "niveau"])->orderBy(
            "name",
        );
        if ($classeIds !== null) {
            if (is_array($classeIds)) {
                $classesQuery->whereIn("id", $classeIds);
            } else {
                $classesQuery->where("id", $classeIds);
            }
        }
        $classes = $classesQuery->get();

        if ($classes->isEmpty()) {
            return collect();
        }

        $classIds = $classes->pluck("id")->values();

        $planificationsQuery = ESBTPPlanificationAcademique::with(["matiere"])
            ->select(
                "matiere_id",
                "filiere_id",
                "niveau_etude_id",
                DB::raw("SUM(volume_horaire_total) as heures_planifiees"),
            )
            ->groupBy("matiere_id", "filiere_id", "niveau_etude_id");

        if ($anneeId) {
            $planificationsQuery->where("annee_universitaire_id", $anneeId);
        }

        if ($periode === "semestre1") {
            $planificationsQuery->where(function ($query) {
                $query->where("semestre", 1)->orWhereNull("semestre");
            });
        } elseif ($periode === "semestre2") {
            $planificationsQuery->where(function ($query) {
                $query->where("semestre", 2)->orWhereNull("semestre");
            });
        }

        $planifications = $planificationsQuery->get();
        $planificationsByCombo = $planifications->groupBy(function (
            $planification,
        ) {
            return $planification->filiere_id .
                "_" .
                $planification->niveau_etude_id;
        });

        $seancesQuery = ESBTPSeanceCours::query()
            ->join(
                "esbtp_emploi_temps",
                "esbtp_seance_cours.emploi_temps_id",
                "=",
                "esbtp_emploi_temps.id",
            )
            ->leftJoin(
                DB::raw('(
                SELECT ta1.course_id, ta1.status
                FROM esbtp_teacher_attendances ta1
                INNER JOIN (
                    SELECT course_id,
                           MAX(CASE
                               WHEN DATE(date) = CURDATE() THEN CONCAT("1_", created_at)
                               WHEN DATE(date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = course_id) THEN CONCAT("2_", created_at)
                               ELSE CONCAT("3_", created_at)
                           END) as max_priority
                    FROM esbtp_teacher_attendances
                    WHERE type = "start"
                    GROUP BY course_id
                ) ta2 ON ta1.course_id = ta2.course_id
                     AND CONCAT(
                         CASE
                             WHEN DATE(ta1.date) = CURDATE() THEN "1_"
                             WHEN DATE(ta1.date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = ta1.course_id) THEN "2_"
                             ELSE "3_"
                         END, ta1.created_at
                     ) = ta2.max_priority
                WHERE ta1.type = "start"
            ) as latest_attendance'),
                "latest_attendance.course_id",
                "=",
                "esbtp_seance_cours.id",
            )
            ->where(function ($query) {
                $query
                    ->whereNull("latest_attendance.status")
                    ->orWhere("latest_attendance.status", "!=", "absent");
            })
            ->whereIn("esbtp_seance_cours.classe_id", $classIds)
            ->select(
                "esbtp_seance_cours.matiere_id",
                "esbtp_seance_cours.classe_id",
                "esbtp_seance_cours.teacher_id",
                DB::raw("COUNT(DISTINCT esbtp_seance_cours.id) as nb_seances"),
                DB::raw(
                    "SUM(TIME_TO_SEC(TIMEDIFF(esbtp_seance_cours.heure_fin, esbtp_seance_cours.heure_debut))/3600) as total_heures",
                ),
            )
            ->groupBy(
                "esbtp_seance_cours.matiere_id",
                "esbtp_seance_cours.classe_id",
                "esbtp_seance_cours.teacher_id",
            )
            ->whereRaw("(
                esbtp_seance_cours.date_seance < CURDATE()
                OR (
                    esbtp_seance_cours.date_seance = CURDATE()
                    AND TIME(esbtp_seance_cours.heure_fin) <= TIME(NOW())
                )
            )");

        if ($anneeId) {
            $seancesQuery->where(
                "esbtp_emploi_temps.annee_universitaire_id",
                $anneeId,
            );
        }

        if ($periode === "semestre1") {
            $seancesQuery->whereIn("esbtp_emploi_temps.semestre", [
                "1",
                1,
                "S1",
                "Semestre 1",
                "semestre1",
                "SEMESTRE 1",
                "Semestre1",
                "s1",
            ]);
        } elseif ($periode === "semestre2") {
            $seancesQuery->whereIn("esbtp_emploi_temps.semestre", [
                "2",
                2,
                "S2",
                "Semestre 2",
                "semestre2",
                "SEMESTRE 2",
                "Semestre2",
                "s2",
            ]);
        }

        $seancesRealisees = $seancesQuery->get();

        $teacherIds = $seancesRealisees
            ->pluck("teacher_id")
            ->filter()
            ->unique();
        $teachers = ESBTPTeacher::with("user")
            ->whereIn("id", $teacherIds)
            ->get()
            ->keyBy("id");

        $matiereIds = $planifications
            ->pluck("matiere_id")
            ->merge($seancesRealisees->pluck("matiere_id"))
            ->filter()
            ->unique();
        $matieres = ESBTPMatiere::whereIn("id", $matiereIds)
            ->get()
            ->keyBy("id");

        return $classes->map(function ($classe) use (
            $planificationsByCombo,
            $seancesRealisees,
            $teachers,
            $matieres,
            $periode,
        ) {
            $comboKey = $classe->filiere_id . "_" . $classe->niveau_etude_id;
            $planificationsCombo = $planificationsByCombo
                ->get($comboKey, collect())
                ->keyBy("matiere_id");
            $seancesClasse = $seancesRealisees->where("classe_id", $classe->id);

            $matiereIdsClasse = $planificationsCombo
                ->keys()
                ->merge($seancesClasse->pluck("matiere_id"))
                ->filter()
                ->unique();

            $matieresData = $matiereIdsClasse
                ->map(function ($matiereId) use (
                    $planificationsCombo,
                    $seancesClasse,
                    $teachers,
                    $matieres,
                    $periode,
                ) {
                    $planification = $planificationsCombo->get($matiereId);
                    $heuresPlanifiees = $planification
                        ? (float) $planification->heures_planifiees
                        : 0;

                    $seancesMatiere = $seancesClasse->where(
                        "matiere_id",
                        $matiereId,
                    );
                    $totalHeures = (float) $seancesMatiere->sum("total_heures");
                    $nbSeances = (int) $seancesMatiere->sum("nb_seances");

                    $enseignants = $seancesMatiere
                        ->groupBy("teacher_id")
                        ->map(function ($items, $teacherId) use ($teachers) {
                            $teacher = $teachers->get($teacherId);
                            if (!$teacher) {
                                return null;
                            }

                            $teacherName = trim(
                                (string) ($teacher->title
                                    ? $teacher->title . " "
                                    : "") .
                                    ($teacher->name ?? ""),
                            );

                            return [
                                "id" => $teacher->id,
                                "name" => $teacherName ?: "Enseignant",
                                "heures_realisees" => round(
                                    (float) $items->sum("total_heures"),
                                    2,
                                ),
                                "nb_seances" => (int) $items->sum("nb_seances"),
                            ];
                        })
                        ->filter()
                        ->values();

                    $heuresRestantes = max(0, $heuresPlanifiees - $totalHeures);

                    return [
                        "matiere" => $matieres->get($matiereId),
                        "nb_seances" => $nbSeances,
                        "heures_realisees" => round($totalHeures, 2),
                        "heures_planifiees" => round($heuresPlanifiees, 2),
                        "heures_restantes" => round($heuresRestantes, 2),
                        "pourcentage_realise" =>
                            $heuresPlanifiees > 0
                                ? round(
                                    ($totalHeures / $heuresPlanifiees) * 100,
                                    1,
                                )
                                : 0,
                        "est_configure" => $heuresPlanifiees > 0,
                        "periode" => $periode,
                        "enseignants" => $enseignants,
                    ];
                })
                ->filter()
                ->sortBy(function ($item) {
                    return $item["matiere"]->name ?? "";
                })
                ->values();

            $totalPlanifiees = $matieresData->sum("heures_planifiees");
            $totalRealisees = $matieresData->sum("heures_realisees");
            $totalSeances = $matieresData->sum("nb_seances");
            $taux =
                $totalPlanifiees > 0
                    ? round(($totalRealisees / $totalPlanifiees) * 100, 1)
                    : 0;

            $matieresData = $matieresData
                ->map(function ($item) use ($totalRealisees) {
                    $item["pourcentage"] =
                        $totalRealisees > 0
                            ? round(
                                ($item["heures_realisees"] / $totalRealisees) *
                                    100,
                                1,
                            )
                            : 0;
                    return $item;
                })
                ->values();

            return [
                "classe" => $classe,
                "matieres" => $matieresData,
                "stats" => [
                    "matieres_count" => $matieresData->count(),
                    "heures_planifiees_total" => round($totalPlanifiees, 2),
                    "heures_realisees_total" => round($totalRealisees, 2),
                    "nb_seances_total" => (int) $totalSeances,
                    "taux_realisation" => $taux,
                ],
            ];
        });
    }

    private function calculerStatsRepartitionParClasse($repartition)
    {
        $totalClasses = $repartition->count();
        $totalMatieres = $repartition->sum(function ($item) {
            return $item["stats"]["matieres_count"] ?? 0;
        });
        $totalHeuresPlanifiees = $repartition->sum(function ($item) {
            return $item["stats"]["heures_planifiees_total"] ?? 0;
        });
        $totalHeuresRealisees = $repartition->sum(function ($item) {
            return $item["stats"]["heures_realisees_total"] ?? 0;
        });
        $totalSeances = $repartition->sum(function ($item) {
            return $item["stats"]["nb_seances_total"] ?? 0;
        });
        $tauxGlobal =
            $totalHeuresPlanifiees > 0
                ? round(
                    ($totalHeuresRealisees / $totalHeuresPlanifiees) * 100,
                    1,
                )
                : 0;

        return [
            "classes" => $totalClasses,
            "matieres" => $totalMatieres,
            "heures_planifiees" => round($totalHeuresPlanifiees, 1),
            "heures_realisees" => round($totalHeuresRealisees, 1),
            "seances" => (int) $totalSeances,
            "taux_realisation" => $tauxGlobal,
        ];
    }

    private function buildChartDataParClasse($repartition)
    {
        $labels = $repartition
            ->map(function ($item) {
                return $item["classe"]->name ?? "Classe";
            })
            ->values();

        $planifiees = $repartition
            ->map(function ($item) {
                return (float) ($item["stats"]["heures_planifiees_total"] ?? 0);
            })
            ->values();

        $realisees = $repartition
            ->map(function ($item) {
                return (float) ($item["stats"]["heures_realisees_total"] ?? 0);
            })
            ->values();

        return [
            "labels" => $labels,
            "planifiees" => $planifiees,
            "realisees" => $realisees,
        ];
    }

    private function comparerAvecObjectifs($repartition, $classeId, $anneeId)
    {
        return [];
    }

    private function getAllocationHoraireModules($anneeId)
    {
        if (!$anneeId) {
            return [];
        }

        // Récupérer les vraies données des planifications académiques
        $allocations = ESBTPPlanificationAcademique::with(["matiere"])
            ->where("annee_universitaire_id", $anneeId)
            ->select(
                "matiere_id",
                DB::raw("SUM(volume_horaire_total) as total_heures"),
            )
            ->groupBy("matiere_id")
            ->get();

        return $allocations
            ->map(function ($allocation) {
                return [
                    "module" => $allocation->matiere
                        ? $allocation->matiere->name
                        : "Matière inconnue",
                    "description" => $allocation->matiere
                        ? $allocation->matiere->description
                        : "Description non disponible",
                    "heures" => intval($allocation->total_heures ?? 0),
                ];
            })
            ->sortByDesc("heures")
            ->values()
            ->toArray();
    }

    private function getProgrammationHebdomadaire($anneeId, $mois)
    {
        if (!$anneeId) {
            return [];
        }

        // Récupérer les séances de cours pour l'année et le mois sélectionnés
        $seances = ESBTPSeanceCours::with(["matiere", "classe", "enseignant"])
            ->whereHas("emploiTemps", function ($q) use ($anneeId) {
                $q->where("annee_universitaire_id", $anneeId);
            })
            ->whereMonth("created_at", $mois)
            ->orderBy("jour")
            ->orderBy("heure_debut")
            ->get();

        // Grouper par jour de la semaine
        $jours = [
            "lundi",
            "mardi",
            "mercredi",
            "jeudi",
            "vendredi",
            "samedi",
            "dimanche",
        ];
        $programmation = [];

        foreach ($jours as $jour) {
            $programmation[$jour] = $seances
                ->where("jour", ucfirst($jour))
                ->map(function ($seance) {
                    return [
                        "id" => $seance->id,
                        "matiere" => $seance->matiere
                            ? $seance->matiere->name
                            : "Matière inconnue",
                        "horaire" =>
                            $seance->heure_debut . "-" . $seance->heure_fin,
                        "classe" => $seance->classe
                            ? $seance->classe->name
                            : "Classe inconnue",
                    ];
                })
                ->values()
                ->toArray();
        }

        return $programmation;
    }

    private function getCodesEmargementActifs()
    {
        if (!class_exists("App\Models\ESBTPDailyCode")) {
            return [];
        }

        // Récupérer les codes d'émargement actifs ou récents
        $codes = \App\Models\ESBTPDailyCode::whereDate(
            "created_at",
            ">=",
            Carbon::today()->subDays(1),
        )
            ->orderBy("created_at", "desc")
            ->limit(10)
            ->get();

        return $codes
            ->map(function ($code) {
                $expireTime =
                    $code->valid_until ??
                    Carbon::parse($code->created_at)->addMinutes(30);
                $now = Carbon::now();
                $expire = $now->greaterThan($expireTime);

                // Essayer de trouver les émargements associés pour identifier le cours
                $coursInfo = "Cours général";
                if (class_exists("App\Models\ESBTPTeacherAttendance")) {
                    $attendance = \App\Models\ESBTPTeacherAttendance::where(
                        "daily_code_id",
                        $code->id,
                    )
                        ->with(["course.matiere", "course.classe"])
                        ->first();

                    if ($attendance && $attendance->course) {
                        $matiere = $attendance->course->matiere
                            ? $attendance->course->matiere->name
                            : "Matière inconnue";
                        $classe = $attendance->course->classe
                            ? $attendance->course->classe->name
                            : "Classe inconnue";
                        $coursInfo = $matiere . " - " . $classe;
                    }
                }

                return [
                    "id" => $code->id,
                    "code" => $code->code,
                    "cours" => $coursInfo,
                    "expire_dans" => $expire
                        ? "Expiré"
                        : $expireTime->diffForHumans($now),
                    "expire" => $expire,
                ];
            })
            ->toArray();
    }

    private function calculerTauxPresenceClasses($anneeId)
    {
        if (!$anneeId) {
            return [];
        }

        // Récupérer les classes avec leurs taux de présence réels
        $classes = ESBTPClasse::with(["etudiants"])
            ->whereHas("emploiTemps", function ($q) use ($anneeId) {
                $q->where("annee_universitaire_id", $anneeId);
            })
            ->get();

        return $classes
            ->map(function ($classe) {
                $effectif = $classe->etudiants->count();

                if ($effectif == 0) {
                    return [
                        "nom" => $classe->name,
                        "effectif" => 0,
                        "taux" => 0,
                    ];
                }

                // Calculer le taux de présence moyen sur les 30 derniers jours
                if (class_exists("App\Models\ESBTPAttendance")) {
                    $presences = \App\Models\ESBTPAttendance::whereHas(
                        "seanceCours",
                        function ($q) use ($classe) {
                            $q->where("classe_id", $classe->id);
                        },
                    )
                        ->whereDate("date", ">=", Carbon::today()->subDays(30))
                        ->get();

                    $totalPresences = $presences
                        ->where("statut", "present")
                        ->count();
                    $totalSeances = $presences->count();

                    $taux =
                        $totalSeances > 0
                            ? round(($totalPresences / $totalSeances) * 100, 1)
                            : 0;
                } else {
                    // Taux simulé basé sur l'ID de la classe pour cohérence
                    $taux = 70 + ($classe->id % 25);
                }

                return [
                    "nom" => $classe->name,
                    "effectif" => $effectif,
                    "taux" => $taux,
                ];
            })
            ->sortByDesc("taux")
            ->values()
            ->toArray();
    }

    private function getEvenementsAcademiques($annee)
    {
        // Récupérer les événements réels depuis la base de données
        if (class_exists("App\Models\ESBTPEvenementAcademique")) {
            $evenements = \App\Models\ESBTPEvenementAcademique::where(
                "annee_universitaire_id",
                $annee->id,
            )
                ->where("afficher_calendrier", true)
                ->where("is_active", true)
                ->orderBy("date_debut")
                ->get();

            return $evenements
                ->map(function ($evenement) {
                    return [
                        "titre" => $evenement->titre,
                        "date" => $evenement->date_debut->format("d/m/Y"),
                        "description" => $evenement->description,
                        "icon" => $evenement->icone,
                        "type" => $evenement->type,
                        "couleur" => $evenement->couleur,
                        "statut" => $evenement->statut,
                        "lieu" => $evenement->lieu,
                        "heure_debut" => $evenement->heure_debut
                            ? $evenement->heure_debut->format("H:i")
                            : null,
                        "heure_fin" => $evenement->heure_fin
                            ? $evenement->heure_fin->format("H:i")
                            : null,
                        "date_fin" => $evenement->date_fin
                            ? $evenement->date_fin->format("d/m/Y")
                            : null,
                    ];
                })
                ->toArray();
        }

        // Données de démonstration si le modèle n'existe pas
        $debut = Carbon::create($annee->start_date, 9, 1); // 1er septembre
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin

        return [
            [
                "titre" => "Rentrée Académique",
                "date" => $debut->copy()->format("d/m/Y"),
                "description" =>
                    'Ouverture officielle de l\'année académique - Toutes filières',
                "icon" => "graduation-cap",
                "type" => "rentree",
                "couleur" => "success",
            ],
            [
                "titre" => 'Période d\'Orientation',
                "date" => $debut->copy()->addWeeks(2)->format("d/m/Y"),
                "description" =>
                    'Séances d\'information pour nouveaux étudiants',
                "icon" => "compass",
                "type" => "orientation",
                "couleur" => "info",
            ],
            [
                "titre" => "Examens de 1er Semestre",
                "date" => Carbon::create($annee->start_date, 12, 15)->format(
                    "d/m/Y",
                ),
                "description" => "Évaluations semestrielles - Toutes classes",
                "icon" => "file-alt",
                "type" => "examens",
                "couleur" => "warning",
            ],
            [
                "titre" => "Vacances Semestrielles",
                "date" => Carbon::create($annee->start_date, 12, 22)->format(
                    "d/m/Y",
                ),
                "description" => "Période de vacances inter-semestrielle",
                "icon" => "calendar-times",
                "type" => "vacances",
                "couleur" => "secondary",
            ],
            [
                "titre" => "Reprise 2e Semestre",
                "date" => Carbon::create($annee->annee_fin, 1, 8)->format(
                    "d/m/Y",
                ),
                "description" => "Début du second semestre académique",
                "icon" => "play-circle",
                "type" => "reprise",
                "couleur" => "success",
            ],
            [
                "titre" => "Soutenances de Stages",
                "date" => Carbon::create($annee->annee_fin, 4, 15)->format(
                    "d/m/Y",
                ),
                "description" =>
                    "Présentations des stages professionnels - BTS2",
                "icon" => "presentation",
                "type" => "soutenances",
                "couleur" => "primary",
            ],
            [
                "titre" => "Examens Finaux",
                "date" => Carbon::create($annee->annee_fin, 5, 20)->format(
                    "d/m/Y",
                ),
                "description" => 'Examens de fin d\'année - Toutes filières',
                "icon" => "certificate",
                "type" => "examens",
                "couleur" => "danger",
            ],
            [
                "titre" => "Cérémonie de Remise des Diplômes",
                "date" => Carbon::create($annee->annee_fin, 6, 20)->format(
                    "d/m/Y",
                ),
                "description" => "Cérémonie officielle de graduation",
                "icon" => "trophy",
                "type" => "ceremonie",
                "couleur" => "primary",
            ],
            [
                "titre" => "Fermeture Année Académique",
                "date" => $fin->copy()->format("d/m/Y"),
                "description" => 'Clôture officielle de l\'année académique',
                "icon" => "flag-checkered",
                "type" => "fermeture",
                "couleur" => "dark",
            ],
        ];
    }

    private function calculerStatistiquesMensuelles($annee)
    {
        // Calcul des statistiques mensuelles réelles
        $debut = Carbon::create($annee->start_date, 9, 1); // 1er septembre
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin

        $statistiques = [];
        $moisCourant = $debut->copy()->startOfMonth();

        while ($moisCourant->lte($fin)) {
            // Compter les séances programmées pour ce mois
            $totalSeances = ESBTPSeanceCours::whereHas("emploiTemps", function (
                $query,
            ) use ($annee) {
                $query->where("annee_universitaire_id", $annee->id);
            })
                ->where("type", ESBTPSeanceCours::TYPE_COURSE)
                ->whereMonth("created_at", $moisCourant->month)
                ->whereYear("created_at", $moisCourant->year)
                ->count();

            // Calculer les heures totales
            $totalHeures = ESBTPSeanceCours::whereHas("emploiTemps", function (
                $query,
            ) use ($annee) {
                $query->where("annee_universitaire_id", $annee->id);
            })
                ->where("type", ESBTPSeanceCours::TYPE_COURSE)
                ->whereMonth("created_at", $moisCourant->month)
                ->whereYear("created_at", $moisCourant->year)
                ->sum(
                    DB::raw(
                        "TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600",
                    ),
                );

            // Compter les planifications pour ce mois
            $totalPlanifications = ESBTPPlanificationAcademique::where(
                "annee_universitaire_id",
                $annee->id,
            )
                ->whereMonth("created_at", $moisCourant->month)
                ->whereYear("created_at", $moisCourant->year)
                ->count();

            $statistiques[] = [
                "mois" => $moisCourant->translatedFormat("F Y"),
                "mois_court" => $moisCourant->translatedFormat("M"),
                "total_seances" => $totalSeances,
                "total_heures" => round($totalHeures, 1),
                "total_planifications" => $totalPlanifications,
                "date" => $moisCourant->copy(),
            ];

            $moisCourant->addMonth();
        }

        return $statistiques;
    }

    /**
     * Calculer les statistiques de planification pour une filière/niveau/semestre
     */
    private function calculerStatistiquesPlanification(
        $anneeId,
        $filiereId,
        $niveauId,
        $semestre,
    ) {
        if (!$anneeId || !$filiereId || !$niveauId) {
            return [
                "total_matieres_planifiees" => 0,
                "total_heures_planifiees" => 0,
                "total_enseignants_assignes" => 0,
                "repartition_types_cours" => ["cm" => 0, "td" => 0, "tp" => 0],
                "statuts_planification" => [],
                "taux_completion" => 0,
            ];
        }

        $planifications = ESBTPPlanificationAcademique::forAnnee($anneeId)
            ->forFiliere($filiereId)
            ->forNiveau($niveauId)
            ->forSemestre($semestre)
            ->get();

        $totalMatieresDisponibles = ESBTPMatiere::whereHas("classes", function (
            $query,
        ) use ($filiereId, $niveauId) {
            $query
                ->where("filiere_id", $filiereId)
                ->where("niveau_etude_id", $niveauId);
        })->count();

        $stats = [
            "total_matieres_planifiees" => $planifications->count(),
            "total_heures_planifiees" => $planifications->sum(
                "volume_horaire_total",
            ),
            "total_enseignants_assignes" => $planifications
                ->whereNotNull("enseignant_principal_id")
                ->pluck("enseignant_principal_id")
                ->unique()
                ->count(),
            "repartition_types_cours" => [
                "cm" => $planifications->sum("volume_horaire_cm"),
                "td" => $planifications->sum("volume_horaire_td"),
                "tp" => $planifications->sum("volume_horaire_tp"),
            ],
            "statuts_planification" => $planifications
                ->groupBy("statut")
                ->map(function ($items) {
                    return $items->count();
                }),
            "taux_completion" =>
                $totalMatieresDisponibles > 0
                    ? round(
                        ($planifications->count() / $totalMatieresDisponibles) *
                            100,
                        1,
                    )
                    : 0,
        ];

        return $stats;
    }

    /**
     * Créer ou mettre à jour une planification académique
     */
    public function storePlanification(StorePlanificationRequest $request)
    {
        // Vérifier que la somme des volumes horaires détaillés correspond au total
        $sommeDetaillee =
            ($request->volume_horaire_cm ?? 0) +
            ($request->volume_horaire_td ?? 0) +
            ($request->volume_horaire_tp ?? 0);

        if (
            $sommeDetaillee > 0 &&
            $sommeDetaillee != $request->volume_horaire_total
        ) {
            return back()->withErrors([
                "volume_horaire_total" =>
                    "La somme des heures CM + TD + TP doit correspondre au volume horaire total",
            ]);
        }

        $planification = ESBTPPlanificationAcademique::updateOrCreate(
            [
                "annee_universitaire_id" => $request->annee_universitaire_id,
                "filiere_id" => $request->filiere_id,
                "niveau_etude_id" => $request->niveau_etude_id,
                "matiere_id" => $request->matiere_id,
                "semestre" => $request->semestre,
            ],
            [
                "volume_horaire_total" => $request->volume_horaire_total,
                "volume_horaire_cm" => $request->volume_horaire_cm ?? 0,
                "volume_horaire_td" => $request->volume_horaire_td ?? 0,
                "volume_horaire_tp" => $request->volume_horaire_tp ?? 0,
                "coefficient" => $request->coefficient ?? 1,
                "credits_ects" => $request->credits_ects ?? 0,
                "enseignant_principal_id" => $request->enseignant_principal_id,
                "periode_debut" => $request->periode_debut,
                "periode_fin" => $request->periode_fin,
                "objectifs_pedagogiques" => $request->objectifs_pedagogiques,
                "prerequis" => $request->prerequis,
                "observations" => $request->observations,
                "statut" => ESBTPPlanificationAcademique::STATUT_PLANIFIE,
                "updated_by" => Auth::id(),
            ],
        );

        if ($planification->wasRecentlyCreated) {
            $planification->update(["created_by" => Auth::id()]);
        }

        return redirect()
            ->back()
            ->with(
                "success",
                "Planification académique enregistrée avec succès",
            );
    }

    /**
     * Supprimer une planification académique
     */
    public function destroyPlanification($id)
    {
        $planification = ESBTPPlanificationAcademique::findOrFail($id);

        // Vérifier que la planification peut être supprimée
        if (!$planification->isModifiable()) {
            return back()->withErrors([
                "error" =>
                    "Cette planification ne peut plus être supprimée (statut: " .
                    $planification->statut .
                    ")",
            ]);
        }

        $planification->delete();

        return redirect()
            ->back()
            ->with("success", "Planification supprimée avec succès");
    }

    /**
     * Valider une planification académique
     */
    public function validerPlanification($id)
    {
        $planification = ESBTPPlanificationAcademique::findOrFail($id);

        // Valider la cohérence
        $erreurs = $planification->validerCoherence();
        if (!empty($erreurs)) {
            return back()->withErrors([
                "error" => "Erreurs de validation: " . implode(", ", $erreurs),
            ]);
        }

        $planification->update([
            "statut" => ESBTPPlanificationAcademique::STATUT_VALIDE,
            "updated_by" => Auth::id(),
        ]);

        return redirect()
            ->back()
            ->with("success", "Planification validée avec succès");
    }

    /**
     * Interface admin pour voir l'impact des émargements sur la progression des planifications
     */
    public function impactEmargements(Request $request)
    {
        // Vérifier les permissions
        if (
            !Auth::user()->hasAnyPermission([
                "admin.access",
                "identity.coordinate",
            ]) && !Auth::user()->hasRole("directeurEtudes")
        ) {
            abort(403, "Accès réservé aux administrateurs et coordinateurs.");
        }

        $anneeId = $request->input("annee_id");
        $filiereId = $request->input("filiere_id");
        $niveauId = $request->input("niveau_id");
        $periodeDebut = $request->input("periode_debut");
        $periodeFin = $request->input("periode_fin");

        // Données de base
        $annees = ESBTPAnneeUniversitaire::orderBy("start_date", "desc")->get();
        $anneeSelectionnee =
            ESBTPAnneeUniversitaire::find($anneeId) ??
            ESBTPAnneeUniversitaire::where("is_current", true)->first();
        $filieres = ESBTPFiliere::where("is_active", true)
            ->orderBy("name")
            ->get();
        $niveaux = ESBTPNiveauEtude::where("is_active", true)
            ->orderBy("year")
            ->get();

        if ($anneeSelectionnee) {
            $anneeId = $anneeSelectionnee->id;
        }

        // Récupérer les données d'impact des émargements
        $impactData = $this->planningStatsService->calculerImpactEmargements(
            $anneeId,
            $filiereId,
            $niveauId,
            $periodeDebut,
            $periodeFin,
        );

        // Statistiques générales d'émargement
        $statistiquesEmargement = $this->calculerStatistiquesEmargement(
            $anneeId,
            $filiereId,
            $niveauId,
            $periodeDebut,
            $periodeFin,
        );

        // Progression par matière avec émargements
        $progressionMatieres = $this->calculerProgressionAvecEmargements(
            $anneeId,
            $filiereId,
            $niveauId,
        );

        // Enseignants avec taux d'émargement
        $enseignantsEmargement = $this->calculerTauxEmargementEnseignants(
            $anneeId,
            $filiereId,
            $niveauId,
        );

        return view(
            "esbtp.planning-general.impact-emargements",
            compact(
                "annees",
                "anneeSelectionnee",
                "filieres",
                "niveaux",
                "impactData",
                "statistiquesEmargement",
                "progressionMatieres",
                "enseignantsEmargement",
                "anneeId",
                "filiereId",
                "niveauId",
                "periodeDebut",
                "periodeFin",
            ),
        );
    }

    /**
     * Récupérer les émargements validés pour une planification
     */
    private function getEmargementsValidesParPlanification(
        $planification,
        $periodeDebut = null,
        $periodeFin = null,
    ) {
        $query = \App\Models\ESBTPTeacherAttendance::with("course")
            ->where("status", "validated")
            ->whereHas("course", function ($q) use ($planification) {
                $q->where("matiere_id", $planification->matiere_id)->where(
                    "teacher_id",
                    $planification->enseignant_principal_id,
                );
            });

        if ($periodeDebut) {
            $query->where("date", ">=", $periodeDebut);
        }
        if ($periodeFin) {
            $query->where("date", "<=", $periodeFin);
        }

        return $query->orderBy("date", "desc")->get();
    }

    /**
     * Calculer les statistiques générales d'émargement
     */
    private function calculerStatistiquesEmargement(
        $anneeId,
        $filiereId = null,
        $niveauId = null,
        $periodeDebut = null,
        $periodeFin = null,
    ) {
        $queryBase = \App\Models\ESBTPTeacherAttendance::query();

        // Filtrer par année via les séances
        $queryBase->whereHas("seance.emploiTemps", function ($q) use (
            $anneeId,
        ) {
            $q->where("annee_universitaire_id", $anneeId);
        });

        // Filtrer par filière/niveau si spécifié
        if ($filiereId || $niveauId) {
            $queryBase->whereHas("course.classe", function ($q) use (
                $filiereId,
                $niveauId,
            ) {
                if ($filiereId) {
                    $q->where("filiere_id", $filiereId);
                }
                if ($niveauId) {
                    $q->where("niveau_etude_id", $niveauId);
                }
            });
        }

        // Filtrer par période
        if ($periodeDebut) {
            $queryBase->where("date", ">=", $periodeDebut);
        }
        if ($periodeFin) {
            $queryBase->where("date", "<=", $periodeFin);
        }

        return [
            "total_emargements" => $queryBase->count(),
            "emargements_valides" => $queryBase
                ->where("status", "validated")
                ->count(),
            "emargements_pending" => $queryBase
                ->where("status", "pending")
                ->count(),
            "emargements_expires" => $queryBase
                ->where("status", "expired")
                ->count(),
            "taux_validation" =>
                $queryBase->count() > 0
                    ? round(
                        ($queryBase->where("status", "validated")->count() /
                            $queryBase->count()) *
                            100,
                        1,
                    )
                    : 0,
            "heures_totales_emargees" => $this->calculerHeuresTotalesEmargees(
                $queryBase,
            ),
            "derniere_mise_a_jour" => $queryBase
                ->where("status", "validated")
                ->max("validated_at"),
        ];
    }

    /**
     * Calculer la progression par matière avec émargements
     */
    private function calculerProgressionAvecEmargements(
        $anneeId,
        $filiereId = null,
        $niveauId = null,
    ) {
        $query = ESBTPPlanificationAcademique::with([
            "matiere",
            "enseignantPrincipal",
        ])->where("annee_universitaire_id", $anneeId);

        if ($filiereId) {
            $query->where("filiere_id", $filiereId);
        }
        if ($niveauId) {
            $query->where("niveau_etude_id", $niveauId);
        }

        $planifications = $query->get();

        return $planifications
            ->groupBy("matiere_id")
            ->map(function ($planificationsByMatiere) {
                $matiere = $planificationsByMatiere->first()->matiere;
                $totalPlanifie = $planificationsByMatiere->sum(
                    "volume_horaire_total",
                );
                $totalEffectue = $planificationsByMatiere->sum(
                    "heures_effectuees",
                );

                // Calculer heures via émargements
                $totalEmargement = 0;
                foreach ($planificationsByMatiere as $planif) {
                    $emargements = $this->getEmargementsValidesParPlanification(
                        $planif,
                    );
                    $totalEmargement += $emargements->sum(function (
                        $emargement,
                    ) {
                        if ($emargement->seance) {
                            return Carbon::parse(
                                $emargement->seance->heure_fin,
                            )->diffInMinutes(
                                Carbon::parse($emargement->seance->heure_debut),
                            ) / 60;
                        }
                        return 0;
                    });
                }

                return [
                    "matiere" => $matiere,
                    "heures_planifiees" => $totalPlanifie,
                    "heures_effectuees" => $totalEffectue,
                    "heures_emargement" => round($totalEmargement, 2),
                    "taux_progression_base" =>
                        $totalPlanifie > 0
                            ? round(($totalEffectue / $totalPlanifie) * 100, 1)
                            : 0,
                    "taux_progression_emargement" =>
                        $totalPlanifie > 0
                            ? round(
                                ($totalEmargement / $totalPlanifie) * 100,
                                1,
                            )
                            : 0,
                    "nb_planifications" => $planificationsByMatiere->count(),
                ];
            })
            ->sortByDesc("heures_emargement");
    }

    /**
     * Calculer le taux d'émargement des enseignants
     */
    private function calculerTauxEmargementEnseignants(
        $anneeId,
        $filiereId = null,
        $niveauId = null,
    ) {
        $query = User::role("enseignant")->whereHas(
            "seancesCours.emploiTemps",
            function ($q) use ($anneeId) {
                $q->where("annee_universitaire_id", $anneeId);
            },
        );

        if ($filiereId || $niveauId) {
            $query->whereHas("seancesCours.classe", function ($q) use (
                $filiereId,
                $niveauId,
            ) {
                if ($filiereId) {
                    $q->where("filiere_id", $filiereId);
                }
                if ($niveauId) {
                    $q->where("niveau_etude_id", $niveauId);
                }
            });
        }

        $enseignants = $query
            ->with(["seancesCours", "teacherAttendances"])
            ->get();

        return $enseignants
            ->map(function ($enseignant) use ($anneeId) {
                $seancesTotales = $enseignant
                    ->seancesCours()
                    ->whereHas("emploiTemps", function ($q) use ($anneeId) {
                        $q->where("annee_universitaire_id", $anneeId);
                    })
                    ->count();

                $emargementsValides = $enseignant
                    ->teacherAttendances()
                    ->where("status", "validated")
                    ->whereHas("seance.emploiTemps", function ($q) use (
                        $anneeId,
                    ) {
                        $q->where("annee_universitaire_id", $anneeId);
                    })
                    ->count();

                $tauxEmargement =
                    $seancesTotales > 0
                        ? round(
                            ($emargementsValides / $seancesTotales) * 100,
                            1,
                        )
                        : 0;

                return [
                    "enseignant" => $enseignant,
                    "seances_totales" => $seancesTotales,
                    "emargements_valides" => $emargementsValides,
                    "taux_emargement" => $tauxEmargement,
                    "dernier_emargement" => $enseignant
                        ->teacherAttendances()
                        ->where("status", "validated")
                        ->latest("validated_at")
                        ->first(),
                ];
            })
            ->sortByDesc("taux_emargement");
    }

    /**
     * Calculer les heures totales émargées
     */
    private function calculerHeuresTotalesEmargees($query)
    {
        $emargements = $query
            ->where("status", "validated")
            ->with("course")
            ->get();

        return $emargements->sum(function ($emargement) {
            if ($emargement->course) {
                return Carbon::parse(
                    $emargement->course->heure_fin,
                )->diffInMinutes(
                    Carbon::parse($emargement->course->heure_debut),
                ) / 60;
            }
            return 0;
        });
    }

}
