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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ESBTPPlanningGeneralController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Interface de test pour planification académique
     */
    public function indexTest(Request $request)
    {
        // Utilise la même logique que index() mais force la vue de test
        $result = $this->index($request);
        $data = $result->getData();
        
        return view('esbtp.planning-general.index-test', $data);
    }

    /**
     * Interface principale de planification académique
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Récupérer l'année universitaire sélectionnée ou celle en cours
        $anneeId = $request->input('annee_id');
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');
        $semestre = $request->input('semestre', 1);
        
        if (!$anneeId) {
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
        }
        
        // Données de base
        $annees = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId);
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('year')->get();
        
        // Variables pour les vues
        $filiereSelectionnee = ESBTPFiliere::find($filiereId);
        $niveauSelectionne = ESBTPNiveauEtude::find($niveauId);
        
        // Récupérer les planifications existantes
        $planifications = collect();
        $matieres = collect();
        $enseignants = collect();
        
        if ($anneeId && $filiereId && $niveauId) {
            $planifications = ESBTPPlanificationAcademique::with(['matiere', 'enseignantPrincipal'])
                ->forAnnee($anneeId)
                ->forFiliere($filiereId)
                ->forNiveau($niveauId)
                ->forSemestre($semestre)
                ->orderBy('created_at', 'desc')
                ->get();
                
            // Matières disponibles pour cette filière/niveau
            $matieres = ESBTPMatiere::whereHas('classes', function($query) use ($filiereId, $niveauId) {
                $query->where('filiere_id', $filiereId)
                      ->where('niveau_etude_id', $niveauId);
            })->orderBy('name')->get();
            
            // Enseignants disponibles
            $enseignants = User::role('enseignant')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
        
        // Statistiques de planification
        $statistiques = $this->calculerStatistiquesPlanification($anneeId, $filiereId, $niveauId, $semestre);
        
        // Statistiques générales pour la vue index
        $stats = $this->calculerStatistiquesGenerales($anneeId);
        
        return view('esbtp.planning-general.index', compact(
            'annees', 'anneeSelectionnee', 'filieres', 'filiereSelectionnee',
            'niveaux', 'niveauSelectionne', 'matieres', 'enseignants',
            'planifications', 'semestre', 'statistiques', 'stats',
            'anneeId', 'filiereId', 'niveauId'
        ));
    }

    /**
     * Vue coordinateur - Vue d'ensemble avec options avancées
     */
    private function indexCoordinateur($anneeId, $annees, $anneeSelectionnee, $stats)
    {
        // Pour la vue d'ensemble, on utilise la vue index.blade.php
        // mais avec des données supplémentaires pour les coordinateurs
        
        // Répartition des heures par matière
        $repartitionMatieres = $this->calculerRepartitionMatieres($anneeId);
        
        // Emplois du temps par classe
        $emploisTempsClasses = $this->getEmploisTempsParClasse($anneeId);
        
        // Progression vs objectifs
        $progressionObjectifs = $this->calculerProgressionObjectifs($anneeId);
        
        // Classes avec conflits d'horaires
        $conflitsHoraires = $this->detecterConflitsHoraires($anneeId);

        return view('esbtp.planning-general.index', compact(
            'annees', 'anneeSelectionnee', 'stats', 'repartitionMatieres',
            'emploisTempsClasses', 'progressionObjectifs', 'conflitsHoraires'
        ));
    }

    /**
     * Vue enseignant - Planning personnel
     */
    private function indexEnseignant($anneeId, $annees, $anneeSelectionnee, $stats)
    {
        $user = Auth::user();
        
        // Séances de l'enseignant
        $seancesEnseignant = ESBTPSeanceCours::where('teacher_id', $user->id)
            ->whereHas('emploiTemps', function($query) use ($anneeId) {
                if ($anneeId) {
                    $query->where('annee_universitaire_id', $anneeId);
                }
            })
            ->with(['matiere', 'classe', 'emploiTemps'])
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get();
        
        // Grouper par semaine et jour
        $planningHebdomadaire = $this->grouperSeancesParSemaine($seancesEnseignant);
        
        // Charge horaire par matière
        $chargeHoraireMatiere = $this->calculerChargeHoraireEnseignant($user->id, $anneeId);

        return view('esbtp.planning-general.enseignant', compact(
            'annees', 'anneeSelectionnee', 'stats', 'seancesEnseignant',
            'planningHebdomadaire', 'chargeHoraireMatiere'
        ));
    }

    /**
     * Vue étudiant - Planning de classe
     */
    private function indexEtudiant($anneeId, $annees, $anneeSelectionnee, $stats)
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();
        
        if (!$etudiant) {
            return view('esbtp.planning-general.etudiant-no-profile', compact(
                'annees', 'anneeSelectionnee'
            ));
        }
        
        // Inscription active pour l'année sélectionnée
        $inscription = $etudiant->inscriptions()
            ->where('status', 'active')
            ->where('annee_universitaire_id', $anneeId)
            ->first();
        
        if (!$inscription) {
            return view('esbtp.planning-general.etudiant-no-inscription', compact(
                'annees', 'anneeSelectionnee', 'etudiant'
            ));
        }
        
        // Emploi du temps de la classe
        $emploiTemps = ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
            ->where('is_current', true)
            ->first();
        
        $seancesClasse = $emploiTemps ? $emploiTemps->seances()
            ->with(['matiere', 'enseignant'])
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get() : collect();
        
        // Planning hebdomadaire
        $planningHebdomadaire = $this->grouperSeancesParJour($seancesClasse);

        return view('esbtp.planning-general.etudiant', compact(
            'annees', 'anneeSelectionnee', 'stats', 'etudiant', 'inscription',
            'emploiTemps', 'seancesClasse', 'planningHebdomadaire'
        ));
    }

    /**
     * Vue annuelle - Calendrier complet de l'année
     */
    public function annuel(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId) ?? 
                            ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeSelectionnee) {
            return redirect()->route('esbtp.planning-general.index')
                ->with('error', 'Aucune année universitaire trouvée.');
        }

        // Calendrier mensuel de l'année
        $calendrierMensuel = $this->genererCalendrierAnnuel($anneeSelectionnee);
        
        // Événements académiques importants
        $evenementsAcademiques = $this->getEvenementsAcademiques($anneeSelectionnee);
        
        // Statistiques par mois
        $statistiquesMensuelles = $this->calculerStatistiquesMensuelles($anneeSelectionnee);

        return view('esbtp.planning-general.annuel', compact(
            'anneeSelectionnee', 'calendrierMensuel', 'evenementsAcademiques', 
            'statistiquesMensuelles'
        ));
    }

    /**
     * Répartition des heures par matière
     */
    public function repartitionMatieres(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $classeId = $request->input('classe_id');
        $periode = $request->input('periode', 'annee'); // semestre1, semestre2, ou annee
        
        $annees = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $classes = ESBTPClasse::with(['filiere', 'niveau'])->orderBy('name')->get();
        
        // Répartition globale ou par classe avec comparaison planifié vs réalisé
        if ($classeId) {
            $repartition = $this->calculerRepartitionMatieresClasse($classeId, $anneeId, $periode);
        } else {
            $repartition = $this->calculerRepartitionMatieres($anneeId, $periode);
        }
        
        // Debug: vérifier les données
        \Log::info('Repartition data:', [
            'count' => $repartition->count(),
            'anneeId' => $anneeId,
            'classeId' => $classeId,
            'periode' => $periode,
            'sample' => $repartition->take(2)->toArray()
        ]);
        
        // Comparaison avec les objectifs
        $objectifsComparaison = $this->comparerAvecObjectifs($repartition, $classeId, $anneeId);

        return view('esbtp.planning-general.repartition-matieres', compact(
            'annees', 'classes', 'repartition', 'objectifsComparaison', 'anneeId', 'classeId'
        ));
    }

    /**
     * Planning par coordinateur - Interface de gestion
     */
    public function coordinateur(Request $request)
    {
        if (!Auth::user()->hasRole(['coordinateur', 'superAdmin'])) {
            abort(403, 'Accès réservé aux coordinateurs.');
        }

        $anneeId = $request->input('annee_id');
        $mois = $request->input('mois', now()->month);
        
        $annees = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId) ?? 
                            ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Allocation horaire par module
        $allocationHoraire = $this->getAllocationHoraireModules($anneeId);
        
        // Programmation hebdomadaire
        $programmationHebdomadaire = $this->getProgrammationHebdomadaire($anneeId, $mois);
        
        // Codes d'émargement actifs
        $codesEmargement = $this->getCodesEmargementActifs();
        
        // Taux de présence par classe
        $tauxPresenceClasses = $this->calculerTauxPresenceClasses($anneeId);

        return view('esbtp.planning-general.coordinateur', compact(
            'annees', 'anneeSelectionnee', 'allocationHoraire', 'programmationHebdomadaire',
            'codesEmargement', 'tauxPresenceClasses', 'mois'
        ));
    }

    // ============ MÉTHODES PRIVÉES DE CALCUL ============

    /**
     * Calcule les statistiques générales
     */
    private function calculerStatistiquesGenerales($anneeId)
    {
        $query = ESBTPSeanceCours::query();
        
        if ($anneeId) {
            $query->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
            });
        }

        return [
            'total_seances' => $query->count(),
            'total_heures' => $query->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600')),
            'total_classes' => ESBTPClasse::whereHas('emploiTemps', function($q) use ($anneeId) {
                if ($anneeId) {
                    $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                }
            })->count(),
            'total_matieres' => ESBTPMatiere::whereHas('seancesCours', function($q) use ($anneeId) {
                if ($anneeId) {
                    $q->whereHas('emploiTemps', function($q2) use ($anneeId) {
                        $q2->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                    });
                }
            })->count(),
            'total_enseignants' => User::role('enseignant')->whereHas('seancesCours', function($q) use ($anneeId) {
                if ($anneeId) {
                    $q->whereHas('emploiTemps', function($q2) use ($anneeId) {
                        $q2->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                    });
                }
            })->count()
        ];
    }

    /**
     * Calcule la répartition des heures par matière
     */
    private function calculerRepartitionMatieres($anneeId, $periode = 'annee')
    {
        // Récupérer les heures réalisées par matière
        $query = ESBTPSeanceCours::with('matiere')
            ->select('matiere_id', DB::raw('COUNT(*) as nb_seances'), 
                    DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures'))
            ->groupBy('matiere_id');
        
        if ($anneeId) {
            $query->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
            });
        }

        $results = $query->get();
        
        // Récupérer les heures planifiées par matière selon la période
        $planificationsQuery = ESBTPPlanificationAcademique::with('matiere')
            ->select('matiere_id', DB::raw('SUM(volume_horaire_total) as heures_planifiees'))
            ->groupBy('matiere_id');
            
        if ($anneeId) {
            $planificationsQuery->where('annee_universitaire_id', $anneeId);
        }
        
        // Filtrer par semestre si spécifié
        if ($periode === 'semestre1') {
            $planificationsQuery->where('semestre', 1);
        } elseif ($periode === 'semestre2') {
            $planificationsQuery->where('semestre', 2);
        }
        
        $planifications = $planificationsQuery->get()->keyBy('matiere_id');
        
        // Calcul du total pour les pourcentages
        $totalHeures = $results->sum('total_heures');
        
        return $results->map(function($item) use ($totalHeures, $planifications, $periode) {
            $planification = $planifications->get($item->matiere_id);
            $heuresPlanifiees = $planification ? $planification->heures_planifiees : 0;
            $heuresRestantes = max(0, $heuresPlanifiees - $item->total_heures);
            
            return [
                'matiere' => $item->matiere,
                'nb_seances' => $item->nb_seances,
                'total_heures' => round($item->total_heures, 2),
                'heures_planifiees' => round($heuresPlanifiees, 2),
                'heures_restantes' => round($heuresRestantes, 2),
                'pourcentage_realise' => $heuresPlanifiees > 0 ? round(($item->total_heures / $heuresPlanifiees) * 100, 1) : 0,
                'pourcentage' => $totalHeures > 0 ? round(($item->total_heures / $totalHeures) * 100, 1) : 0,
                'est_configure' => $heuresPlanifiees > 0,
                'periode' => $periode
            ];
        });
    }

    /**
     * Groupe les séances par jour de la semaine
     */
    private function grouperSeancesParJour($seances)
    {
        $jours = [
            0 => 'Lundi', 1 => 'Mardi', 2 => 'Mercredi', 
            3 => 'Jeudi', 4 => 'Vendredi', 5 => 'Samedi'
        ];

        $planning = [];
        foreach ($jours as $numero => $nom) {
            $planning[$nom] = $seances->where('jour', $numero)->sortBy('heure_debut');
        }

        return $planning;
    }

    /**
     * Calcule la charge horaire par matière pour un enseignant
     */
    private function calculerChargeHoraireEnseignant($enseignantId, $anneeId)
    {
        $query = ESBTPSeanceCours::where('teacher_id', $enseignantId)
            ->with('matiere')
            ->select('matiere_id', DB::raw('COUNT(*) as nb_seances'),
                    DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures'))
            ->groupBy('matiere_id');

        if ($anneeId) {
            $query->whereHas('emploiTemps', function($q) use ($anneeId) {
                $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
            });
        }

        return $query->get();
    }

    /**
     * Génère le calendrier annuel par mois
     */
    private function genererCalendrierAnnuel($annee)
    {
        // Créer des dates complètes à partir des années
        $debut = Carbon::create($annee->annee_debut, 9, 1); // 1er septembre de l'année de début
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin de l'année de fin
        
        $calendrier = [];
        $moisCourant = $debut->copy()->startOfMonth();
        
        while ($moisCourant->lte($fin)) {
            $calendrier[] = [
                'mois' => $moisCourant->format('Y-m'),
                'nom' => $moisCourant->translatedFormat('F Y'),
                'semaines' => $this->genererSemainesMois($moisCourant)
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
                    'date' => $semaineActuelle->copy(),
                    'dans_mois' => $semaineActuelle->month === $mois->month,
                    'est_aujourd_hui' => $semaineActuelle->isToday()
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
    private function getEmploisTempsParClasse($anneeId) { 
        return collect(); 
    }
    
    private function calculerProgressionObjectifs($anneeId) { 
        return []; 
    }
    
    private function detecterConflitsHoraires($anneeId) { 
        return []; 
    }
    
    private function grouperSeancesParSemaine($seances) { 
        return []; 
    }
    
    private function calculerRepartitionMatieresClasse($classeId, $anneeId, $periode = 'annee') { 
        // Récupérer les informations de la classe pour filtrer les planifications
        $classe = ESBTPClasse::find($classeId);
        
        $query = ESBTPSeanceCours::with('matiere')
            ->whereHas('emploiTemps', function($q) use ($classeId, $anneeId) {
                $q->where('classe_id', $classeId);
                if ($anneeId) {
                    $q->where('esbtp_emploi_temps.annee_universitaire_id', $anneeId);
                }
            })
            ->select('matiere_id', DB::raw('COUNT(*) as nb_seances'), 
                    DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures'))
            ->groupBy('matiere_id');

        $results = $query->get();
        
        // Récupérer les heures planifiées pour cette classe selon la période
        $planificationsQuery = ESBTPPlanificationAcademique::with('matiere')
            ->select('matiere_id', DB::raw('SUM(volume_horaire_total) as heures_planifiees'))
            ->groupBy('matiere_id');
            
        if ($anneeId) {
            $planificationsQuery->where('annee_universitaire_id', $anneeId);
        }
        
        // Filtrer par classe (filière et niveau)
        if ($classe) {
            $planificationsQuery->where('filiere_id', $classe->filiere_id)
                              ->where('niveau_etude_id', $classe->niveau_id);
        }
        
        // Filtrer par semestre si spécifié
        if ($periode === 'semestre1') {
            $planificationsQuery->where('semestre', 1);
        } elseif ($periode === 'semestre2') {
            $planificationsQuery->where('semestre', 2);
        }
        
        $planifications = $planificationsQuery->get()->keyBy('matiere_id');
        
        // Calcul du total pour les pourcentages
        $totalHeures = $results->sum('total_heures');
        
        return $results->map(function($item) use ($totalHeures, $planifications, $periode) {
            $planification = $planifications->get($item->matiere_id);
            $heuresPlanifiees = $planification ? $planification->heures_planifiees : 0;
            $heuresRestantes = max(0, $heuresPlanifiees - $item->total_heures);
            
            return [
                'matiere' => $item->matiere,
                'nb_seances' => $item->nb_seances,
                'total_heures' => round($item->total_heures, 2),
                'heures_planifiees' => round($heuresPlanifiees, 2),
                'heures_restantes' => round($heuresRestantes, 2),
                'pourcentage_realise' => $heuresPlanifiees > 0 ? round(($item->total_heures / $heuresPlanifiees) * 100, 1) : 0,
                'pourcentage' => $totalHeures > 0 ? round(($item->total_heures / $totalHeures) * 100, 1) : 0,
                'est_configure' => $heuresPlanifiees > 0,
                'periode' => $periode
            ];
        });
    }
    
    private function comparerAvecObjectifs($repartition, $classeId, $anneeId) { 
        return []; 
    }
    
    private function getAllocationHoraireModules($anneeId) { 
        // Données de démonstration
        return [
            [
                'module' => 'Informatique Générale',
                'description' => 'Cours de base en informatique',
                'heures' => 120
            ],
            [
                'module' => 'Mathématiques',
                'description' => 'Mathématiques appliquées',
                'heures' => 90
            ],
            [
                'module' => 'Gestion de Projet',
                'description' => 'Méthodologies de gestion',
                'heures' => 60
            ]
        ];
    }
    
    private function getProgrammationHebdomadaire($anneeId, $mois) { 
        // Données de démonstration
        return [
            'lundi' => [
                [
                    'id' => 1,
                    'matiere' => 'Informatique',
                    'horaire' => '08:00-10:00',
                    'classe' => 'L3 Info'
                ]
            ],
            'mardi' => [
                [
                    'id' => 2,
                    'matiere' => 'Mathématiques',
                    'horaire' => '10:00-12:00',
                    'classe' => 'L2 Math'
                ]
            ]
        ];
    }
    
    private function getCodesEmargementActifs() { 
        // Données de démonstration
        return [
            [
                'id' => 1,
                'code' => 'AB12',
                'cours' => 'Informatique L3',
                'expire_dans' => '15 min',
                'expire' => false
            ],
            [
                'id' => 2,
                'code' => 'CD34',
                'cours' => 'Mathématiques L2',
                'expire_dans' => 'Expiré',
                'expire' => true
            ]
        ];
    }
    
    private function calculerTauxPresenceClasses($anneeId) { 
        // Données de démonstration
        return [
            [
                'nom' => 'L3 Informatique',
                'effectif' => 25,
                'taux' => 85
            ],
            [
                'nom' => 'L2 Mathématiques',
                'effectif' => 30,
                'taux' => 92
            ],
            [
                'nom' => 'L1 Gestion',
                'effectif' => 35,
                'taux' => 78
            ]
        ];
    }
    
    private function getEvenementsAcademiques($annee) { 
        // Récupérer les événements réels depuis la base de données
        if (class_exists('App\Models\ESBTPEvenementAcademique')) {
            $evenements = \App\Models\ESBTPEvenementAcademique::where('annee_universitaire_id', $annee->id)
                ->where('afficher_calendrier', true)
                ->where('is_active', true)
                ->orderBy('date_debut')
                ->get();
            
            return $evenements->map(function($evenement) {
                return [
                    'titre' => $evenement->titre,
                    'date' => $evenement->date_debut->format('d/m/Y'),
                    'description' => $evenement->description,
                    'icon' => $evenement->icone,
                    'type' => $evenement->type,
                    'couleur' => $evenement->couleur,
                    'statut' => $evenement->statut,
                    'lieu' => $evenement->lieu,
                    'heure_debut' => $evenement->heure_debut ? $evenement->heure_debut->format('H:i') : null,
                    'heure_fin' => $evenement->heure_fin ? $evenement->heure_fin->format('H:i') : null,
                    'date_fin' => $evenement->date_fin ? $evenement->date_fin->format('d/m/Y') : null
                ];
            })->toArray();
        }
        
        // Données de démonstration si le modèle n'existe pas
        $debut = Carbon::create($annee->annee_debut, 9, 1); // 1er septembre
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin
        
        return [
            [
                'titre' => 'Rentrée Académique',
                'date' => $debut->copy()->format('d/m/Y'),
                'description' => 'Ouverture officielle de l\'année académique - Toutes filières',
                'icon' => 'graduation-cap',
                'type' => 'rentree',
                'couleur' => 'success'
            ],
            [
                'titre' => 'Période d\'Orientation',
                'date' => $debut->copy()->addWeeks(2)->format('d/m/Y'),
                'description' => 'Séances d\'information pour nouveaux étudiants',
                'icon' => 'compass',
                'type' => 'orientation',
                'couleur' => 'info'
            ],
            [
                'titre' => 'Examens de 1er Semestre',
                'date' => Carbon::create($annee->annee_debut, 12, 15)->format('d/m/Y'),
                'description' => 'Évaluations semestrielles - Toutes classes',
                'icon' => 'file-alt',
                'type' => 'examens',
                'couleur' => 'warning'
            ],
            [
                'titre' => 'Vacances Semestrielles',
                'date' => Carbon::create($annee->annee_debut, 12, 22)->format('d/m/Y'),
                'description' => 'Période de vacances inter-semestrielle',
                'icon' => 'calendar-times',
                'type' => 'vacances',
                'couleur' => 'secondary'
            ],
            [
                'titre' => 'Reprise 2e Semestre',
                'date' => Carbon::create($annee->annee_fin, 1, 8)->format('d/m/Y'),
                'description' => 'Début du second semestre académique',
                'icon' => 'play-circle',
                'type' => 'reprise',
                'couleur' => 'success'
            ],
            [
                'titre' => 'Soutenances de Stages',
                'date' => Carbon::create($annee->annee_fin, 4, 15)->format('d/m/Y'),
                'description' => 'Présentations des stages professionnels - BTS2',
                'icon' => 'presentation',
                'type' => 'soutenances',
                'couleur' => 'primary'
            ],
            [
                'titre' => 'Examens Finaux',
                'date' => Carbon::create($annee->annee_fin, 5, 20)->format('d/m/Y'),
                'description' => 'Examens de fin d\'année - Toutes filières',
                'icon' => 'certificate',
                'type' => 'examens',
                'couleur' => 'danger'
            ],
            [
                'titre' => 'Cérémonie de Remise des Diplômes',
                'date' => Carbon::create($annee->annee_fin, 6, 20)->format('d/m/Y'),
                'description' => 'Cérémonie officielle de graduation',
                'icon' => 'trophy',
                'type' => 'ceremonie',
                'couleur' => 'primary'
            ],
            [
                'titre' => 'Fermeture Année Académique',
                'date' => $fin->copy()->format('d/m/Y'),
                'description' => 'Clôture officielle de l\'année académique',
                'icon' => 'flag-checkered',
                'type' => 'fermeture',
                'couleur' => 'dark'
            ]
        ];
    }
    
    private function calculerStatistiquesMensuelles($annee) { 
        // Calcul des statistiques mensuelles réelles
        $debut = Carbon::create($annee->annee_debut, 9, 1); // 1er septembre
        $fin = Carbon::create($annee->annee_fin, 6, 30); // 30 juin
        
        $statistiques = [];
        $moisCourant = $debut->copy()->startOfMonth();
        
        while ($moisCourant->lte($fin)) {
            // Compter les séances programmées pour ce mois
            $totalSeances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($annee) {
                $query->where('annee_universitaire_id', $annee->id);
            })
            ->whereMonth('created_at', $moisCourant->month)
            ->whereYear('created_at', $moisCourant->year)
            ->count();
            
            // Calculer les heures totales
            $totalHeures = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($annee) {
                $query->where('annee_universitaire_id', $annee->id);
            })
            ->whereMonth('created_at', $moisCourant->month)
            ->whereYear('created_at', $moisCourant->year)
            ->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600'));
            
            // Compter les planifications pour ce mois
            $totalPlanifications = ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
                ->whereMonth('created_at', $moisCourant->month)
                ->whereYear('created_at', $moisCourant->year)
                ->count();
            
            $statistiques[] = [
                'mois' => $moisCourant->translatedFormat('F Y'),
                'mois_court' => $moisCourant->translatedFormat('M'),
                'total_seances' => $totalSeances,
                'total_heures' => round($totalHeures, 1),
                'total_planifications' => $totalPlanifications,
                'date' => $moisCourant->copy()
            ];
            
            $moisCourant->addMonth();
        }
        
        return $statistiques;
    }

    /**
     * Calculer les statistiques de planification pour une filière/niveau/semestre
     */
    private function calculerStatistiquesPlanification($anneeId, $filiereId, $niveauId, $semestre)
    {
        if (!$anneeId || !$filiereId || !$niveauId) {
            return [
                'total_matieres_planifiees' => 0,
                'total_heures_planifiees' => 0,
                'total_enseignants_assignes' => 0,
                'repartition_types_cours' => ['cm' => 0, 'td' => 0, 'tp' => 0],
                'statuts_planification' => [],
                'taux_completion' => 0
            ];
        }

        $planifications = ESBTPPlanificationAcademique::forAnnee($anneeId)
            ->forFiliere($filiereId)
            ->forNiveau($niveauId)
            ->forSemestre($semestre)
            ->get();

        $totalMatieresDisponibles = ESBTPMatiere::whereHas('classes', function($query) use ($filiereId, $niveauId) {
            $query->where('filiere_id', $filiereId)
                  ->where('niveau_etude_id', $niveauId);
        })->count();

        $stats = [
            'total_matieres_planifiees' => $planifications->count(),
            'total_heures_planifiees' => $planifications->sum('volume_horaire_total'),
            'total_enseignants_assignes' => $planifications->whereNotNull('enseignant_principal_id')->pluck('enseignant_principal_id')->unique()->count(),
            'repartition_types_cours' => [
                'cm' => $planifications->sum('volume_horaire_cm'),
                'td' => $planifications->sum('volume_horaire_td'),
                'tp' => $planifications->sum('volume_horaire_tp')
            ],
            'statuts_planification' => $planifications->groupBy('statut')->map(function($items) {
                return $items->count();
            }),
            'taux_completion' => $totalMatieresDisponibles > 0 
                ? round(($planifications->count() / $totalMatieresDisponibles) * 100, 1)
                : 0
        ];

        return $stats;
    }

    /**
     * Créer ou mettre à jour une planification académique
     */
    public function storePlanification(Request $request)
    {
        $request->validate([
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'semestre' => 'required|integer|min:1|max:4',
            'volume_horaire_total' => 'required|integer|min:1|max:200',
            'volume_horaire_cm' => 'nullable|integer|min:0',
            'volume_horaire_td' => 'nullable|integer|min:0',
            'volume_horaire_tp' => 'nullable|integer|min:0',
            'coefficient' => 'nullable|numeric|min:0.5|max:10',
            'credits_ects' => 'nullable|integer|min:1|max:30',
            'enseignant_principal_id' => 'nullable|exists:users,id',
            'periode_debut' => 'nullable|date',
            'periode_fin' => 'nullable|date|after:periode_debut',
            'objectifs_pedagogiques' => 'nullable|string|max:1000',
            'prerequis' => 'nullable|string|max:500',
            'observations' => 'nullable|string|max:500'
        ]);

        // Vérifier que la somme des volumes horaires détaillés correspond au total
        $sommeDetaillee = ($request->volume_horaire_cm ?? 0) + 
                         ($request->volume_horaire_td ?? 0) + 
                         ($request->volume_horaire_tp ?? 0);
        
        if ($sommeDetaillee > 0 && $sommeDetaillee != $request->volume_horaire_total) {
            return back()->withErrors([
                'volume_horaire_total' => 'La somme des heures CM + TD + TP doit correspondre au volume horaire total'
            ]);
        }

        $planification = ESBTPPlanificationAcademique::updateOrCreate(
            [
                'annee_universitaire_id' => $request->annee_universitaire_id,
                'filiere_id' => $request->filiere_id,
                'niveau_etude_id' => $request->niveau_etude_id,
                'matiere_id' => $request->matiere_id,
                'semestre' => $request->semestre
            ],
            [
                'volume_horaire_total' => $request->volume_horaire_total,
                'volume_horaire_cm' => $request->volume_horaire_cm ?? 0,
                'volume_horaire_td' => $request->volume_horaire_td ?? 0,
                'volume_horaire_tp' => $request->volume_horaire_tp ?? 0,
                'coefficient' => $request->coefficient ?? 1,
                'credits_ects' => $request->credits_ects ?? 0,
                'enseignant_principal_id' => $request->enseignant_principal_id,
                'periode_debut' => $request->periode_debut,
                'periode_fin' => $request->periode_fin,
                'objectifs_pedagogiques' => $request->objectifs_pedagogiques,
                'prerequis' => $request->prerequis,
                'observations' => $request->observations,
                'statut' => ESBTPPlanificationAcademique::STATUT_PLANIFIE,
                'updated_by' => Auth::id()
            ]
        );

        if ($planification->wasRecentlyCreated) {
            $planification->update(['created_by' => Auth::id()]);
        }

        return redirect()->back()->with('success', 'Planification académique enregistrée avec succès');
    }

    /**
     * Supprimer une planification académique
     */
    public function destroyPlanification($id)
    {
        $planification = ESBTPPlanificationAcademique::findOrFail($id);
        
        // Vérifier que la planification peut être supprimée
        if (!$planification->isModifiable()) {
            return back()->withErrors(['error' => 'Cette planification ne peut plus être supprimée (statut: ' . $planification->statut . ')']);
        }

        $planification->delete();

        return redirect()->back()->with('success', 'Planification supprimée avec succès');
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
            return back()->withErrors(['error' => 'Erreurs de validation: ' . implode(', ', $erreurs)]);
        }

        $planification->update([
            'statut' => ESBTPPlanificationAcademique::STATUT_VALIDE,
            'updated_by' => Auth::id()
        ]);

        return redirect()->back()->with('success', 'Planification validée avec succès');
    }
}