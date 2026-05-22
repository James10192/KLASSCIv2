<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAvailability;
use App\Models\User;
use App\Services\ESBTPPDFService;
use App\Services\TimetableShortcutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ESBTPEmploiTempsController extends Controller
{
    // Constructor without authorizeResource
    public function __construct()
    {
        // No policy-based authorization
    }

    /**
     * Affiche la liste des emplois du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // No policy-based authorization
        // Récupérer l'année universitaire courante d'abord
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Filtrer les emplois du temps par année courante
        $emploisTempsQuery = ESBTPEmploiTemps::with([
            'classe.filiere',
            'classe.niveau',
            'seances:id,emploi_temps_id,heure_debut,heure_fin',
            'updatedBy:id,name',
        ])->withCount('seances');

        if ($anneeEnCours) {
            $emploisTempsQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }

        // Appliquer les filtres depuis l'URL
        // Filtrage par filière
        if ($request->filled('filiere_id')) {
            $emploisTempsQuery->whereHas('classe', function ($q) use ($request) {
                $q->where('filiere_id', $request->filiere_id);
            });
        }

        // Filtrage par niveau
        if ($request->filled('niveau_id')) {
            $emploisTempsQuery->whereHas('classe', function ($q) use ($request) {
                $q->where('niveau_etude_id', $request->niveau_id);
            });
        }

        // Filtrage par classe
        if ($request->filled('classe_id')) {
            $emploisTempsQuery->where('classe_id', $request->classe_id);
        }

        // Filtrage par période automatique (basée sur les dates)
        if ($request->filled('period_status')) {
            $today = Carbon::today();
            if ($request->period_status === 'current') {
                $emploisTempsQuery->whereDate('date_debut', '<=', $today)
                    ->whereDate('date_fin', '>=', $today);
            } elseif ($request->period_status === 'upcoming') {
                $emploisTempsQuery->whereDate('date_debut', '>', $today);
            } elseif ($request->period_status === 'expired') {
                $emploisTempsQuery->whereDate('date_fin', '<', $today);
            }
        }

        // Filtrage par emploi du temps courant
        if ($request->filled('is_current')) {
            if ($request->is_current === '1' || $request->is_current === 'true') {
                $emploisTempsQuery->where('is_current', true);
            } elseif ($request->is_current === '0' || $request->is_current === 'false') {
                $emploisTempsQuery->where('is_current', false);
            }
        }

        // Filtrage par semaine (plage de dates au format "date_debut|date_fin")
        if ($request->filled('semaine')) {
            $dates = explode('|', $request->semaine);
            if (count($dates) === 2) {
                $emploisTempsQuery->where('date_debut', $dates[0])
                    ->where('date_fin', $dates[1]);
            }
        }

        $emploisTemps = $emploisTempsQuery->orderBy('date_debut', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Ajout des filières pour le filtre
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();

        // Ajout des niveaux pour le filtre
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        // Ajout des classes pour le filtre
        $classes = ESBTPClasse::with(['filiere', 'niveau'])->orderBy('name')->get();

        // Ajout des années universitaires pour le filtre
        $annees = ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        // Statistiques
        $totalEmploisTemps = $emploisTemps->count();
        $today = Carbon::today();
        $emploisTempsActifs = $emploisTemps->filter(function ($emploiTemps) use ($today) {
            if (! $emploiTemps->date_debut || ! $emploiTemps->date_fin) {
                return false;
            }
            $startDate = Carbon::parse($emploiTemps->date_debut);
            $endDate = Carbon::parse($emploiTemps->date_fin);

            return $today->between($startDate, $endDate);
        });
        $emploisTempsActifsCount = $emploisTempsActifs->count();

        // Séances de l'année courante uniquement (bug précédent : count app-wide)
        $totalSeances = ESBTPSeanceCours::whereHas('emploiTemps', function ($q) use ($anneeEnCours) {
            if ($anneeEnCours) {
                $q->where('annee_universitaire_id', $anneeEnCours->id);
            }
        })->count();

        // Emplois du temps de l'année en cours (déjà filtrés)
        $emploisTempsAnneeEnCours = $emploisTemps->count();

        // Compteurs pour les chips filtrantes (redesign v2)
        $edtExpiresCount = $emploisTemps->filter(function ($et) use ($today) {
            return $et->date_fin && Carbon::parse($et->date_fin)->lt($today);
        })->count();
        $totalClassesTenant = ESBTPClasse::where('is_active', true)->count();
        $classesAvecEdtActifIds = $emploisTempsActifs->pluck('classe_id')->unique();
        $classesSansEdtCount = max(0, $totalClassesTenant - $classesAvecEdtActifIds->count());

        // Passer l'année courante avec le bon nom pour la vue
        $anneeUniversitaireCourante = $anneeEnCours;

        // Statistiques par semaines — indépendantes des filtres (calculées sur l'année courante)
        $semainesStats = $this->buildSemainesStats($anneeEnCours, $request->input('semaine'));
        $semaines = $semainesStats['semaines'];
        $totalSemaines = $semainesStats['totalSemaines'];
        $totalClassesPlanifiees = $semainesStats['totalClassesPlanifiees'];
        $semaineCouranteValue = $semainesStats['semaineCouranteValue'];
        $previousWeekValue = $semainesStats['previousWeekValue'];
        $previousWeekPlanningCount = $semainesStats['previousWeekPlanningCount'];

        $timetableShortcut = app(TimetableShortcutService::class)->getShortcutSummary($anneeEnCours);

        return view('esbtp.emploi-temps.index', compact(
            'emploisTemps', 'filieres', 'niveaux', 'classes', 'annees', 'anneeUniversitaireCourante',
            'totalEmploisTemps', 'emploisTempsActifsCount', 'totalSeances', 'emploisTempsAnneeEnCours', 'timetableShortcut',
            'emploisTempsActifs', 'totalSemaines', 'totalClassesPlanifiees', 'semaines', 'semaineCouranteValue',
            'edtExpiresCount', 'totalClassesTenant', 'classesSansEdtCount',
            'previousWeekValue', 'previousWeekPlanningCount'
        ));
    }

    /**
     * Construit les statistiques par semaine pour une année universitaire donnée.
     * Retourne : totalSemaines, totalClassesPlanifiees, semaines[], semaineCouranteValue,
     * previousWeekValue, previousWeekPlanningCount.
     *
     * @param  string|null  $requestedWeek  Format "Y-m-d|Y-m-d" — la semaine actuellement
     *                                      sélectionnée par l'utilisateur (paramètre ?semaine=).
     *                                      Si elle est vide (aucun planning), on calcule la semaine
     *                                      précédente pour proposer l'action "Dupliquer".
     */
    private function buildSemainesStats(?ESBTPAnneeUniversitaire $annee, ?string $requestedWeek = null): array
    {
        $query = ESBTPEmploiTemps::query()
            ->whereNotNull('date_debut')
            ->whereNotNull('date_fin');

        if ($annee) {
            $query->where('annee_universitaire_id', $annee->id);
        }

        $rows = $query->select('date_debut', 'date_fin', 'classe_id')->get();

        $today = Carbon::today();
        $byRange = [];
        $classesIds = [];

        foreach ($rows as $row) {
            $key = $row->date_debut.'|'.$row->date_fin;
            if (! isset($byRange[$key])) {
                $start = Carbon::parse($row->date_debut);
                $end = Carbon::parse($row->date_fin);
                $status = $today->between($start, $end)
                    ? 'current'
                    : ($end->lt($today) ? 'past' : 'upcoming');
                $byRange[$key] = [
                    'value' => $key,
                    'start' => $start,
                    'end' => $end,
                    'label_short' => $start->isoFormat('DD MMM').' → '.$end->isoFormat('DD MMM'),
                    'label_long' => $start->isoFormat('DD MMM YYYY').' → '.$end->isoFormat('DD MMM YYYY'),
                    'month_key' => $start->isoFormat('MMMM YYYY'),
                    'count' => 0,
                    'status' => $status,
                ];
            }
            $byRange[$key]['count']++;
            $classesIds[$row->classe_id] = true;
        }

        $semaines = collect(array_values($byRange))
            ->sortBy(fn ($s) => $s['start']->timestamp)
            ->values();

        $semaineCourante = $semaines->firstWhere('status', 'current');
        $semaineCouranteValue = $semaineCourante['value'] ?? null;

        // Détection "semaine précédente" pour l'empty-state "dupliquer".
        // Si l'utilisateur a sélectionné une semaine avec ?semaine=X|Y, et que cette semaine
        // n'existe pas dans $byRange (aucun planning), on cherche la plus récente semaine
        // antérieure à la date de début demandée qui contient au moins un planning.
        $previousWeekValue = null;
        $previousWeekPlanningCount = 0;

        if ($requestedWeek && ! isset($byRange[$requestedWeek])) {
            $dates = explode('|', $requestedWeek);
            if (count($dates) === 2) {
                try {
                    $requestedStart = Carbon::parse($dates[0]);
                    $candidate = $semaines
                        ->filter(fn ($s) => $s['start']->lt($requestedStart) && $s['count'] > 0)
                        ->sortByDesc(fn ($s) => $s['start']->timestamp)
                        ->first();
                    if ($candidate) {
                        $previousWeekValue = $candidate['value'];
                        $previousWeekPlanningCount = $candidate['count'];
                    }
                } catch (\Throwable $e) {
                    // Format invalide, pas de détection
                }
            }
        }

        return [
            'totalSemaines' => $semaines->count(),
            'totalClassesPlanifiees' => count($classesIds),
            'semaines' => $semaines,
            'semaineCouranteValue' => $semaineCouranteValue,
            'previousWeekValue' => $previousWeekValue,
            'previousWeekPlanningCount' => $previousWeekPlanningCount,
        ];
    }

    /**
     * Affiche le formulaire de création d'un emploi du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Récupérer les classes avec relations pour optimiser
        $classes = ESBTPClasse::with(['filiere', 'niveau', 'anneeUniversitaire'])
            ->orderBy('name')
            ->get();

        // Récupérer les années universitaires
        $annees = ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        // Générer les dates de la semaine courante
        $semaineCourante = ESBTPEmploiTemps::genererSemaineCourante();

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', 1)->first();
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : 'Aucune année active';

        // Initialiser les données de planification
        $planificationData = [];

        // Si on a une classe sélectionnée (via paramètres GET ou session)
        $classeSelectionnee = request('classe_id') ? ESBTPClasse::find(request('classe_id')) : null;

        // Utiliser seulement l'année courante (pas de choix possible)
        $anneeSelectionnee = $anneeCourante;

        if ($classeSelectionnee && $anneeSelectionnee) {
            $semestre = request('semestre') ?: null;
            $planificationData = $this->getPlanificationDataForClasse($classeSelectionnee, $anneeSelectionnee, $semestre);
        }

        return view('esbtp.emploi-temps.create', compact(
            'classes', 'annees', 'semaineCourante', 'planificationData', 'classeSelectionnee', 'anneeCourante', 'anneeSelectionnee', 'anneeAcademique'
        ));
    }

    /**
     * Génère une liste de créneaux horaires à partir des séances existantes.
     */
    private function generateTimeSlots($seances, int $intervalMinutes = 60, string $defaultStart = '07:00', string $defaultEnd = '18:00'): array
    {
        $intervalMinutes = max(1, $intervalMinutes);

        $convertToMinutes = function ($value) {
            if ($value instanceof Carbon) {
                return ((int) $value->format('H')) * 60 + (int) $value->format('i');
            }

            if (is_string($value) && strlen($value) >= 4) {
                [$hour, $minute] = array_pad(explode(':', substr($value, 0, 5)), 2, 0);
                if (is_numeric($hour) && is_numeric($minute)) {
                    return ((int) $hour) * 60 + (int) $minute;
                }
            }

            return null;
        };

        $startMinutes = $convertToMinutes($defaultStart);
        $endMinutes = $convertToMinutes($defaultEnd);

        if ($startMinutes === null) {
            $startMinutes = 8 * 60;
        }

        if ($endMinutes === null) {
            $endMinutes = 18 * 60;
        }

        $seancesCollection = $seances instanceof \Illuminate\Support\Collection
            ? $seances
            : collect($seances ?: []);

        foreach ($seancesCollection as $seance) {
            $start = $convertToMinutes($seance->heure_debut ?? null);
            $end = $convertToMinutes($seance->heure_fin ?? null);

            if ($start !== null) {
                $startMinutes = min($startMinutes, (int) floor($start / $intervalMinutes) * $intervalMinutes);
            }

            if ($end !== null) {
                $endMinutes = max($endMinutes, (int) ceil($end / $intervalMinutes) * $intervalMinutes);
            }
        }

        if ($endMinutes <= $startMinutes) {
            $endMinutes = $startMinutes + $intervalMinutes;
        }

        $startMinutes = intdiv($startMinutes, $intervalMinutes) * $intervalMinutes;
        $endMinutes = (int) ceil($endMinutes / $intervalMinutes) * $intervalMinutes;

        $slots = [];
        for ($minute = $startMinutes; $minute < $endMinutes; $minute += $intervalMinutes) {
            $hours = intdiv($minute, 60);
            $minutes = $minute % 60;
            $slots[] = sprintf('%02d:%02d', $hours, $minutes);
        }

        return array_values(array_unique($slots));
    }

    /**
     * Récupérer les données de planification pour une classe donnée
     */
    private function getPlanificationDataForClasse($classe, $annee, $semestre = null)
    {
        $data = [
            'classe' => $classe,
            'annee' => $annee,
            'planifications_configurees' => false,
            'matieres_planifiees' => collect(),
            'enseignants_disponibles' => collect(),
            'heures_totales' => 0,
            'heures_restantes' => 0,
            'message_configuration' => null,
            'lien_configuration' => null,
        ];

        // NOUVELLE LOGIQUE : Récupérer uniquement les matières liées à cette combinaison filière/niveau
        $matieresLiees = ESBTPMatiere::where('is_active', true)
            ->whereHas('filieres', function ($query) use ($classe) {
                $query->where('esbtp_filieres.id', $classe->filiere_id);
            })
            ->whereHas('niveaux', function ($query) use ($classe) {
                $query->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
            })
            ->get();

        // Récupérer les planifications pour ces matières liées uniquement
        $planifications = collect();
        foreach ($matieresLiees as $matiere) {
            $planification = \App\Models\ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->where('matiere_id', $matiere->id)
                ->when($semestre, function ($query) use ($semestre) {
                    // Convertir le semestre string en integer si nécessaire
                    if (is_string($semestre)) {
                        if (strpos($semestre, 'Semestre 1') !== false) {
                            $semestreInt = 1;
                        } elseif (strpos($semestre, 'Semestre 2') !== false) {
                            $semestreInt = 2;
                        } else {
                            $semestreInt = null; // Année complète - ne pas filtrer
                        }
                    } else {
                        $semestreInt = $semestre;
                    }

                    if ($semestreInt) {
                        $query->where('semestre', $semestreInt);
                    }
                })
                ->active()
                ->with(['matiere.uniteEnseignement', 'enseignantPrincipal', 'teachers.user', 'teachers.availabilities'])
                ->first();

            if ($planification) {
                $planifications->push($planification);
            }
        }

        if ($planifications->isEmpty()) {
            // Aucune planification configurée pour les matières liées
            $data['message_configuration'] = "Aucune planification académique n'a été configurée pour les matières de cette classe (".$matieresLiees->count()." matières disponibles). Veuillez d'abord configurer la planification.";
            // Convertir le semestre string en integer pour l'URL
            $semestreUrl = 1; // Par défaut
            if ($semestre) {
                if (is_string($semestre)) {
                    if (strpos($semestre, 'Semestre 1') !== false) {
                        $semestreUrl = 1;
                    } elseif (strpos($semestre, 'Semestre 2') !== false) {
                        $semestreUrl = 2;
                    }
                } else {
                    $semestreUrl = $semestre;
                }
            }

            $data['lien_configuration'] = route('esbtp.planning-general.index', [
                'annee_id' => $annee->id,
                'filiere_filter' => $classe->filiere_id,
                'niveau_filter' => $classe->niveau_etude_id,
            ]);

            return $data;
        }

        $data['planifications_configurees'] = true;

        // Traiter chaque planification pour calculer les heures restantes
        $matieresPlanifiees = collect();
        $enseignantsIds = collect();
        $teacherCache = [];
        $getTeacherModel = function ($userId) use (&$teacherCache) {
            if (! $userId) {
                return null;
            }

            if (! array_key_exists($userId, $teacherCache)) {
                $teacherCache[$userId] = ESBTPTeacher::with(['user', 'availabilities'])
                    ->where('user_id', $userId)
                    ->first();
            }

            return $teacherCache[$userId];
        };

        foreach ($planifications as $planification) {
            // Utiliser les heures effectuées basées sur les émargements validés
            // Si le champ n'existe pas encore, utiliser l'ancienne méthode comme fallback
            $heuresUtilisees = $planification->heures_effectuees ?? 0;

            // Fallback : si pas d'heures effectuées, calculer à partir des séances
            // IMPORTANT: Exclure les séances où l'enseignant est marqué ABSENT
            if ($heuresUtilisees == 0) {
                // Utiliser une sous-requête pour obtenir l'attendance la plus récente par séance
                $seances = \App\Models\ESBTPSeanceCours::where('esbtp_seance_cours.matiere_id', $planification->matiere_id)
                    ->where('esbtp_seance_cours.type', ESBTPSeanceCours::TYPE_COURSE)
                    ->where('classe_id', $classe->id)
                    ->where('annee_universitaire_id', $annee->id)
                    ->when($semestre, function ($query) {
                        // Si on a un semestre spécifique, filtrer les séances par période
                        // Cette logique peut être adaptée selon votre implémentation des semestres
                    })
                    // Left join pour obtenir l'attendance la plus récente
                    ->leftJoin('esbtp_teacher_attendances', function ($join) {
                        $join->on('esbtp_teacher_attendances.course_id', '=', 'esbtp_seance_cours.id')
                            ->where('esbtp_teacher_attendances.type', '=', 'start')
                             // Sous-requête pour obtenir uniquement l'attendance la plus récente
                            ->whereRaw('esbtp_teacher_attendances.id = (
                                 SELECT ta.id FROM esbtp_teacher_attendances ta
                                 WHERE ta.course_id = esbtp_seance_cours.id
                                   AND ta.type = "start"
                                 ORDER BY CASE
                                     WHEN DATE(ta.date) = CURDATE() THEN 1
                                     WHEN DATE(ta.date) = DATE(esbtp_seance_cours.date_seance) THEN 2
                                     ELSE 3
                                 END, ta.created_at DESC
                                 LIMIT 1
                             )');
                    })
                    // Exclure les séances où l'enseignant est absent
                    ->where(function ($query) {
                        $query->whereNull('esbtp_teacher_attendances.status')
                            ->orWhere('esbtp_teacher_attendances.status', '!=', 'absent');
                    })
                    ->select('esbtp_seance_cours.heure_debut', 'esbtp_seance_cours.heure_fin')
                    ->get();

                foreach ($seances as $seance) {
                    if ($seance->heure_debut && $seance->heure_fin) {
                        $debut = \Carbon\Carbon::parse($seance->heure_debut);
                        $fin = \Carbon\Carbon::parse($seance->heure_fin);
                        $heuresUtilisees += $fin->diffInMinutes($debut) / 60; // Convertir en heures
                    }
                }
            }

            $heuresRestantes = $planification->volume_horaire_total - $heuresUtilisees;

            // Déterminer l'enseignant à afficher (priorité : assignations > principal)
            $enseignantAffiche = null;
            if ($planification->teachers && $planification->teachers->count() > 0) {
                // Utiliser le premier enseignant assigné via la table de liaison
                $firstTeacher = $planification->teachers->first();
                $enseignantAffiche = $firstTeacher->user ?? null;
            } elseif ($planification->enseignantPrincipal) {
                // Fallback sur l'enseignant principal
                $enseignantAffiche = $planification->enseignantPrincipal;
            }

            $enseignantsSelectables = collect();
            if ($planification->teachers && $planification->teachers->count() > 0) {
                $planification->teachers->each(function ($teacher) {
                    $teacher?->loadMissing(['user', 'availabilities']);
                });
                $enseignantsSelectables = $planification->teachers->filter()->values();
            }

            if ($planification->enseignantPrincipal) {
                $principalTeacherModel = $getTeacherModel($planification->enseignantPrincipal->id);
                if ($principalTeacherModel && ! $enseignantsSelectables->contains('id', $principalTeacherModel->id)) {
                    $enseignantsSelectables->push($principalTeacherModel);
                }
            }

            if (! empty($planification->enseignants_secondaires)) {
                foreach ($planification->enseignants_secondaires as $secondaryUserId) {
                    $secondaryTeacherModel = $getTeacherModel($secondaryUserId);
                    if ($secondaryTeacherModel && ! $enseignantsSelectables->contains('id', $secondaryTeacherModel->id)) {
                        $enseignantsSelectables->push($secondaryTeacherModel);
                    }
                }
            }

            $enseignantsSelectables = $enseignantsSelectables->filter()->unique('id')->values();

            // Fonction helper pour formater les heures en XXhYY
            $formatHeures = function ($heures) {
                $h = floor($heures);
                $m = round(($heures - $h) * 60);
                if ($m > 0) {
                    return $h.'h'.($m < 10 ? '0' : '').$m;
                }

                return $h.'h';
            };

            $matieresPlanifiees->push([
                'planification_id' => $planification->id,
                'matiere' => $planification->matiere,
                'enseignant_principal' => $planification->enseignantPrincipal,
                'enseignants_assignes' => $planification->teachers,
                'enseignants_selectables' => $enseignantsSelectables,
                'enseignant_affiche' => $enseignantAffiche,
                'volume_horaire_total' => $planification->volume_horaire_total,
                'heures_utilisees' => $heuresUtilisees,
                'heures_restantes' => max(0, $heuresRestantes),
                'volume_horaire_total_formatted' => $formatHeures($planification->volume_horaire_total),
                'heures_utilisees_formatted' => $formatHeures($heuresUtilisees),
                'heures_restantes_formatted' => $formatHeures(max(0, $heuresRestantes)),
                'pourcentage_utilise' => $planification->volume_horaire_total > 0
                    ? round(($heuresUtilisees / $planification->volume_horaire_total) * 100, 1)
                    : 0,
                'volume_horaire_cm' => $planification->volume_horaire_cm,
                'volume_horaire_td' => $planification->volume_horaire_td,
                'volume_horaire_tp' => $planification->volume_horaire_tp,
                'statut' => $planification->statut,
                'periode_debut' => $planification->periode_debut,
                'periode_fin' => $planification->periode_fin,
            ]);

            // Collecter les enseignants
            if ($planification->enseignant_principal_id) {
                $enseignantsIds->push($planification->enseignant_principal_id);
            }

            // Ajouter les enseignants secondaires s'ils existent
            if ($planification->enseignants_secondaires) {
                foreach ($planification->enseignants_secondaires as $enseignantId) {
                    $enseignantsIds->push($enseignantId);
                }
            }
        }

        $data['matieres_planifiees'] = $matieresPlanifiees;

        $heuresTotal = $matieresPlanifiees->sum('volume_horaire_total');
        $heuresRestantesTotal = $matieresPlanifiees->sum('heures_restantes');

        // Fonction helper pour formater les heures en XXhYY
        $formatHeuresTotal = function ($heures) {
            $h = floor($heures);
            $m = round(($heures - $h) * 60);
            if ($m > 0) {
                return $h.'h'.($m < 10 ? '0' : '').$m;
            }

            return $h.'h';
        };

        $data['heures_totales'] = $heuresTotal;
        $data['heures_restantes'] = $heuresRestantesTotal;
        $data['heures_totales_formatted'] = $formatHeuresTotal($heuresTotal);
        $data['heures_restantes_formatted'] = $formatHeuresTotal($heuresRestantesTotal);

        // Récupérer les enseignants disponibles (tous les enseignants, mais marquer ceux qui sont assignés)
        $tousLesEnseignants = \App\Models\User::role('enseignant')
            ->where('is_active', true)
            ->with('teacherProfile')
            ->get();

        $enseignantsDisponibles = collect();
        foreach ($tousLesEnseignants as $enseignant) {
            $estAssigne = $enseignantsIds->contains($enseignant->id);

            // Calculer la charge de travail actuelle de l'enseignant
            $chargeActuelle = \App\Models\ESBTPPlanificationAcademique::where('enseignant_principal_id', $enseignant->id)
                ->where('annee_universitaire_id', $annee->id)
                ->sum('volume_horaire_total');

            $enseignantsDisponibles->push([
                'enseignant' => $enseignant,
                'est_assigne_classe' => $estAssigne,
                'charge_horaire_annuelle' => $chargeActuelle,
                'disponibilite' => $chargeActuelle < 500 ? 'Disponible' : ($chargeActuelle < 800 ? 'Chargé' : 'Surchargé'),
            ]);
        }

        $data['enseignants_disponibles'] = $enseignantsDisponibles->sortBy([
            ['est_assigne_classe', 'desc'],
            ['charge_horaire_annuelle', 'asc'],
        ]);

        return $data;
    }

    private function buildEmploiTempsViewData(ESBTPEmploiTemps $emploiTemps): array
    {
        $emploiTemps->load([
            'seances.matiere',
            'seances.teacher',
            'classe',
            'classe.filiere',
            'classe.niveau',
            'annee',
        ]);

        $seances = $emploiTemps->seances;

        $joursNoms = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];

        $timeSlots = $this->generateTimeSlots($seances);
        $days = array_keys($joursNoms);

        $matiereStats = [];
        foreach ($emploiTemps->seances as $seance) {
            $matiereName = $seance->matiere ? $seance->matiere->name : 'Non définie';
            if (! isset($matiereStats[$matiereName])) {
                $matiereStats[$matiereName] = 0;
            }
            $matiereStats[$matiereName]++;
        }

        $planificationData = [];
        if ($emploiTemps->classe && $emploiTemps->annee) {
            $planificationData = $this->getPlanificationDataForClasse(
                $emploiTemps->classe,
                $emploiTemps->annee,
                $emploiTemps->semestre
            );

            // PR2 chantier emploi-temps-lmd-unification : applique override LMD via service
            // canonical MatiereTreeBuilder. buildForPlanning() sans volumeBudget car bulk-edit
            // et sections AJAX n'affichent pas les KPIs "heures restantes" (uniquement la grille).
            // Sans cette ligne, le bulkEdit affichait "Planification non configuree" pour classes LMD.
            if (($emploiTemps->classe->systeme_academique ?? '') === 'LMD') {
                $planificationData = app(\App\Services\LMD\MatiereTreeBuilder::class)
                    ->buildForPlanning($planificationData, $emploiTemps->classe);
            }
        }

        // Variables tab "Suivi des heures" (LMD/BTS) via méthode DRY
        $suiviData = $this->buildSuiviHeuresData($emploiTemps);

        return array_merge([
            'emploiTemps' => $emploiTemps,
            'seances' => $seances,
            'joursNoms' => $joursNoms,
            'timeSlots' => $timeSlots,
            'days' => $days,
            'matiereStats' => $matiereStats,
            'planificationData' => $planificationData,
        ], $suiviData);
    }

    /**
     * Enregistre un nouvel emploi du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|string|max:50',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        // Vérifier que la période ne dépasse pas 5 jours
        $dateDebut = \Carbon\Carbon::parse($validated['date_debut']);
        $dateFin = \Carbon\Carbon::parse($validated['date_fin']);
        $diffJours = $dateDebut->diffInDays($dateFin);

        if ($diffJours > 5) {
            return back()->withInput()->withErrors([
                'date_fin' => 'La période de l\'emploi du temps ne doit pas dépasser 6 jours (du lundi au samedi).',
            ]);
        }

        // Créer l'emploi du temps
        $emploiTemps = new ESBTPEmploiTemps;
        $emploiTemps->titre = $validated['titre'];
        $emploiTemps->classe_id = $validated['classe_id'];
        $emploiTemps->annee_universitaire_id = $validated['annee_universitaire_id'];
        $emploiTemps->semestre = $validated['semestre'];
        $emploiTemps->date_debut = $validated['date_debut'];
        $emploiTemps->date_fin = $validated['date_fin'];
        $emploiTemps->created_by = Auth::id();
        $emploiTemps->is_active = $request->has('is_active');
        $emploiTemps->is_current = $request->has('is_current');

        // Sauvegarder l'emploi du temps
        $emploiTemps->save();

        // Si l'emploi du temps est marqué comme actif ou courant, désactiver les autres pour cette classe
        if ($emploiTemps->is_active || $emploiTemps->is_current) {
            // Désactiver tous les autres emplois du temps pour cette classe
            ESBTPEmploiTemps::where('id', '!=', $emploiTemps->id)
                ->where('classe_id', $emploiTemps->classe_id)
                ->update([
                    'is_active' => false,
                    'is_current' => false,
                ]);

            // S'assurer que le nouvel emploi du temps est bien actif et courant
            $emploiTemps->is_active = true;
            $emploiTemps->is_current = true;
            $emploiTemps->save();

            // Journaliser l'action
            \Log::info('Nouvel emploi du temps activé et défini comme courant', [
                'emploi_temps_id' => $emploiTemps->id,
                'classe_id' => $emploiTemps->classe_id,
                'user_id' => Auth::id(),
            ]);
        }

        return redirect()->route('esbtp.emploi-temps.show', $emploiTemps->id)
            ->with('success', 'Emploi du temps créé avec succès.');
    }

    /**
     * Affiche un emploi du temps spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, ESBTPEmploiTemps $emploi_temp)
    {
        // No policy-based authorization
        // Charger les séances pour cet emploi du temps
        $emploi_temp->load([
            'seances.matiere',
            'seances.teacher',
            'classe',
            'classe.filiere',
            'classe.niveau',
            'annee',
        ]);

        // Variable $seances pour la vue
        $seances = $emploi_temp->seances;

        // Grouper les séances par jour
        $seancesParJour = $emploi_temp->getSeancesParJour();

        // Setting configurable : afficher le dimanche ou non (default false)
        $showSunday = (bool) \App\Models\ESBTPSystemSetting::getValue('emploi_temps.show_sunday', false);

        // Noms des jours pour l'affichage
        $joursNoms = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];
        if ($showSunday) {
            $joursNoms[7] = 'Dimanche';
        }

        // Générer dynamiquement les créneaux horaires (pas de 15 minutes pour couvrir 08:30, 09:15, etc.)
        $timeSlots = $this->generateTimeSlots($seances);

        // Créer les variables $days pour la vue
        $days = array_keys($joursNoms);

        // Calcul des statistiques par matière
        $matiereStats = [];
        foreach ($emploi_temp->seances as $seance) {
            $matiereName = $seance->matiere ? $seance->matiere->name : 'Non définie';
            if (! isset($matiereStats[$matiereName])) {
                $matiereStats[$matiereName] = 0;
            }
            $matiereStats[$matiereName]++;
        }

        // Ajouter les données de planification académique
        $planificationData = [];
        if ($emploi_temp->classe && $emploi_temp->annee) {
            $planificationData = $this->getPlanificationDataForClasse(
                $emploi_temp->classe,
                $emploi_temp->annee,
                $emploi_temp->semestre
            );

            // OVERRIDE LMD : si la classe est LMD avec parcours, utiliser le scope strict
            // parcours.unitesEnseignement (pattern Planning LMD) au lieu du planning general
            // (qui retourne 0 matieres car le pivot esbtp_classe_matiere est vide en LMD).
            // PR1 chantier emploi-temps-lmd-unification : bascule vers MatiereTreeBuilder
            // canonical (SSOT). buildWithVolumeBudget() calcule les heures realisees CM/TD/TP
            // via VolumeBudgetService pour les KPIs hero "heures restantes / % realise".
            if (($emploi_temp->classe->systeme_academique ?? '') === 'LMD') {
                $planificationData = app(\App\Services\LMD\MatiereTreeBuilder::class)
                    ->buildWithVolumeBudget($planificationData, $emploi_temp->classe, $emploi_temp->annee);
            }
        }

        // KPIs pour le hero premium
        $totalSeances = $emploi_temp->seances->count();
        $totalHeuresPlanifiees = $planificationData['heures_totales'] ?? 0;
        $heuresRestantes = $planificationData['heures_restantes'] ?? 0;
        $pourcentageRestant = $totalHeuresPlanifiees > 0
            ? round(($heuresRestantes / $totalHeuresPlanifiees) * 100)
            : 0;
        $enseignantsIds = $emploi_temp->seances
            ->pluck('teacher_id')
            ->filter()
            ->unique()
            ->count();

        $heroKpis = [
            'total_seances' => $totalSeances,
            'heures_planifiees' => $totalHeuresPlanifiees,
            'pourcentage_restant' => $pourcentageRestant,
            'enseignants' => $enseignantsIds,
        ];

        // Renommer la variable pour la vue
        $emploiTemps = $emploi_temp;

        // Variables pour le tab "Suivi des heures" (LMD/BTS)
        // Le toggle Semestre 1/2/Année est lu depuis ?periode= (cohérent avec classes.show).
        // Pour LMD : 'semestre1'/'semestre2' sont mappés sur les vrais semestres du niveau
        // (ex L2 → S3+S4) côté buildSuiviHeuresData via lmdSemestres.
        $suiviPeriode = $request->input('periode', 'annee');
        if (!in_array($suiviPeriode, ['annee', 'semestre1', 'semestre2'], true)) {
            $suiviPeriode = 'annee';
        }
        $suiviData = $this->buildSuiviHeuresData($emploiTemps, $suiviPeriode);
        $classe = $suiviData['classe'];
        $planningMatiere = $suiviData['planningMatiere'];
        $kpiTaux = $suiviData['kpiTaux'];
        $periode = $suiviData['periode'];
        $lmdVolumeBudget = $suiviData['lmdVolumeBudget'];
        $lmdMatieres = $suiviData['lmdMatieres'];
        $lmdSemestres = $suiviData['lmdSemestres'];
        $lmdUesAvecEcues = $suiviData['lmdUesAvecEcues'];

        return view('esbtp.emploi-temps.show', compact(
            'emploiTemps', 'seances', 'seancesParJour',
            'joursNoms', 'matiereStats', 'timeSlots', 'days', 'planificationData',
            'heroKpis', 'showSunday',
            // Tab Suivi heures
            'classe', 'planningMatiere', 'kpiTaux', 'periode',
            'lmdVolumeBudget', 'lmdMatieres', 'lmdSemestres', 'lmdUesAvecEcues'
        ));
    }

    /**
     * GET /esbtp/emploi-temps/{id}/suivi-heures-partial?periode=X
     * Retourne UNIQUEMENT le HTML du partial Suivi heures LMD (sans le layout)
     * pour permettre un toggle Semestre/Année sans full page reload.
     */
    public function suiviHeuresPartial(Request $request, ESBTPEmploiTemps $emploi_temp)
    {
        $emploiTemps = $emploi_temp;

        $suiviPeriode = $request->input('periode', 'annee');
        if (!in_array($suiviPeriode, ['annee', 'semestre1', 'semestre2'], true)) {
            $suiviPeriode = 'annee';
        }

        $suiviData = $this->buildSuiviHeuresData($emploiTemps, $suiviPeriode);
        $classe = $suiviData['classe'];
        $planningMatiere = $suiviData['planningMatiere'];
        $kpiTaux = $suiviData['kpiTaux'];
        $periode = $suiviData['periode'];
        $lmdVolumeBudget = $suiviData['lmdVolumeBudget'];
        $lmdMatieres = $suiviData['lmdMatieres'];
        $lmdSemestres = $suiviData['lmdSemestres'];
        $lmdUesAvecEcues = $suiviData['lmdUesAvecEcues'];

        return view('esbtp.classes.partials._suivi_heures_lmd', compact(
            'classe', 'planningMatiere', 'kpiTaux', 'periode',
            'lmdVolumeBudget', 'lmdMatieres', 'lmdSemestres', 'lmdUesAvecEcues'
        ))->render();
    }

    /**
     * Construit les variables LMD/BTS pour le tab "Suivi des heures" de emploi-temps/show.
     * Utilise par show() et buildEmploiTempsViewData() (DRY).
     *
     * @param  string  $periode  'annee' | 'semestre1' | 'semestre2'.
     *                           Pour LMD : 'semestre1'/'semestre2' sont mappés vers
     *                           les vrais semestres du niveau (ex L2 → S3+S4).
     */
    private function buildSuiviHeuresData(ESBTPEmploiTemps $emploiTemps, string $periode = 'annee'): array
    {
        $classe = $emploiTemps->classe;
        $anneeCourante = $emploiTemps->annee;
        $isLmd = ($classe->systeme_academique ?? '') === 'LMD';

        // Mapping niveau-aware : L1 LMD → [1,2], L2 → [3,4], L3 → [5,6], M1 → [7,8], M2 → [9,10].
        // Calculé en amont pour pouvoir le passer à ClassPlanningService (KPI Heures planifiées
        // doit aussi filtrer par semestre quand periode='semestre1'/'semestre2').
        $lmdSemestres = [];
        if ($isLmd) {
            $niveauType = optional($classe->niveau)->type ?? '';
            $niveauYear = (int) (optional($classe->niveau)->year ?? 1);
            $baseSem = 0;
            if ($niveauType === 'Licence') {
                $baseSem = ($niveauYear - 1) * 2;
            } elseif ($niveauType === 'Master') {
                $baseSem = 6 + ($niveauYear - 1) * 2;
            } elseif ($niveauType === 'Doctorat') {
                $baseSem = 10 + ($niveauYear - 1) * 2;
            }
            $lmdSemestres = [$baseSem + 1, $baseSem + 2];
        }

        $planningMatiere = ['stats' => ['heures_planifiees' => 0, 'heures_realisees' => 0, 'nb_seances' => 0, 'taux_realisation' => 0], 'matieres' => collect()];
        if ($classe && $anneeCourante) {
            try {
                $planningService = app(\App\Services\ClassPlanningService::class);
                $planningMatiere = $planningService->buildPlanningMatierePourClasse(
                    $classe,
                    $anneeCourante,
                    $periode,
                    !empty($lmdSemestres) ? $lmdSemestres : null,
                );
            } catch (\Throwable $e) {
                \Log::warning('ClassPlanningService failed on emploi-temps.show: '.$e->getMessage());
            }
        }
        $kpiTaux = $planningMatiere['stats']['taux_realisation'] ?? 0;

        $lmdVolumeBudget = [];
        $lmdMatieres = collect();
        $lmdUesAvecEcues = collect();

        if ($isLmd && $anneeCourante) {
            // Semestres effectivement chargés selon periode :
            //  - 'annee'      → tous les semestres du niveau (ex L2 → [3,4])
            //  - 'semestre1'  → 1er semestre du niveau (ex L2 → [3])
            //  - 'semestre2'  → 2e semestre du niveau (ex L2 → [4])
            $semestresToLoad = match ($periode) {
                'semestre1' => isset($lmdSemestres[0]) ? [$lmdSemestres[0]] : [],
                'semestre2' => isset($lmdSemestres[1]) ? [$lmdSemestres[1]] : [],
                default => $lmdSemestres,
            };

            try {
                $volumeBudgetService = app(\App\Services\VolumeBudgetService::class);
                foreach ($semestresToLoad as $sem) {
                    $semBudget = $volumeBudgetService->forClasse($classe, $classe->niveau_etude_id, $sem, $anneeCourante->id);
                    foreach ($semBudget as $matiereId => $budget) {
                        if (! isset($lmdVolumeBudget[$matiereId])) {
                            $lmdVolumeBudget[$matiereId] = $budget;
                        } else {
                            foreach (['cm', 'td', 'tp'] as $k) {
                                $lmdVolumeBudget[$matiereId][$k]['planifie'] = ($lmdVolumeBudget[$matiereId][$k]['planifie'] ?? 0) + ($budget[$k]['planifie'] ?? 0);
                                $lmdVolumeBudget[$matiereId][$k]['realise']  = ($lmdVolumeBudget[$matiereId][$k]['realise']  ?? 0) + ($budget[$k]['realise']  ?? 0);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('VolumeBudgetService failed on emploi-temps.show: '.$e->getMessage());
            }

            try {
                $lmdMatieres = app(\App\Services\LMD\MatiereTreeBuilder::class)->loadLmdMatieresForClasse($classe);
            } catch (\Throwable $e) {
                \Log::warning('LMD matieres loader failed on emploi-temps.show: '.$e->getMessage());
            }

            if ($lmdMatieres->isNotEmpty()) {
                $lmdUesAvecEcues = app(\App\Services\LMD\MatiereTreeBuilder::class)
                    ->forClasse($lmdMatieres, $lmdVolumeBudget);
            }
        }

        return [
            'classe' => $classe,
            'planningMatiere' => $planningMatiere,
            'kpiTaux' => $kpiTaux,
            'periode' => $periode,
            'lmdVolumeBudget' => $lmdVolumeBudget,
            'lmdMatieres' => $lmdMatieres,
            'lmdSemestres' => $lmdSemestres,
            'lmdUesAvecEcues' => $lmdUesAvecEcues,
        ];
    }

    // PR2 chantier emploi-temps-lmd-unification (2026-05-22) :
    // Méthode privée `overridePlanificationForLmd()` SUPPRIMÉE.
    // Logique consolidée dans App\Services\LMD\MatiereTreeBuilder::buildWithVolumeBudget().
    // Tous les callers (show, buildEmploiTempsViewData, addSession en PR3) utilisent désormais
    // le service via l'API canonique (rule lmd-bts-matieres-single-source.md).
    // Strangler fig pattern complété — voir memory/feedback_strangler_fig_refactor.md

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($ids)) {
            return redirect()->route('esbtp.emploi-temps.index')
                ->with('error', 'Sélectionnez au moins un emploi du temps actif.');
        }

        $emploisTemps = ESBTPEmploiTemps::whereIn('id', $ids)
            ->orderBy('date_debut', 'desc')
            ->get();

        $emploiTempsData = $emploisTemps->map(function ($emploiTemps) {
            return $this->buildEmploiTempsViewData($emploiTemps);
        });

        return view('esbtp.emploi-temps.bulk-edit', compact('emploisTemps', 'emploiTempsData'));
    }

    public function sections(ESBTPEmploiTemps $emploi_temp)
    {
        $data = $this->buildEmploiTempsViewData($emploi_temp);

        $html = view('esbtp.emploi-temps.partials.bulk-block', $data)->render();

        return response()->json([
            'emploi_temps_id' => $emploi_temp->id,
            'html' => $html,
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un emploi du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPEmploiTemps $emploi_temp)
    {
        \Log::info('Tentative d\'édition d\'emploi du temps', [
            'emploi_temps_id' => $emploi_temp->id,
            'user_id' => auth()->id(),
            'user_permissions' => auth()->user()->getAllPermissions()->pluck('name'),
            'user_roles' => auth()->user()->getRoleNames(),
        ]);

        // No policy-based authorization
        $emploiTemps = $emploi_temp;

        // Ensure $emploiTemps is an object
        if (! is_object($emploiTemps)) {
            \Log::error('$emploiTemps is not an object', [
                'type' => gettype($emploiTemps),
                'value' => $emploiTemps,
            ]);

            // Try to find the emploi_temp by ID if it's an integer
            if (is_numeric($emploiTemps)) {
                $emploiTemps = ESBTPEmploiTemps::find($emploiTemps);
                if (! $emploiTemps) {
                    abort(404, 'Emploi du temps non trouvé');
                }
            } else {
                abort(404, 'Emploi du temps non trouvé');
            }
        }

        $classes = ESBTPClasse::all();
        $annees = ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        // Log the variables being passed to the view
        \Log::info('Variables passed to edit view', [
            'emploiTemps' => $emploiTemps,
            'classes_count' => $classes->count(),
            'annees_count' => $annees->count(),
        ]);

        return view('esbtp.emploi-temps.edit', compact('emploiTemps', 'classes', 'annees'));
    }

    /**
     * Met à jour un emploi du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPEmploiTemps $emploi_temp)
    {
        // No policy-based authorization
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'semestre' => 'required|in:Semestre 1,Semestre 2',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        $validated['updated_by'] = Auth::id();
        $validated['is_active'] = $request->has('is_active');
        $validated['is_current'] = $request->has('is_current');

        // Vérifier si l'emploi du temps est activé ou défini comme courant
        $isBeingActivated = $request->has('is_active') && ! $emploi_temp->is_active;
        $isBeingSetCurrent = $request->has('is_current') && ! $emploi_temp->is_current;

        // Mettre à jour l'emploi du temps
        $emploi_temp->update($validated);

        // Si l'emploi du temps est activé ou défini comme courant, désactiver les autres
        if ($isBeingActivated || $isBeingSetCurrent) {
            // Désactiver tous les autres emplois du temps pour cette classe
            ESBTPEmploiTemps::where('id', '!=', $emploi_temp->id)
                ->where('classe_id', $emploi_temp->classe_id)
                ->update([
                    'is_active' => false,
                    'is_current' => false,
                ]);

            // S'assurer que cet emploi du temps est bien actif et courant
            $emploi_temp->is_active = true;
            $emploi_temp->is_current = true;
            $emploi_temp->save();

            // Journaliser l'action
            \Log::info('Emploi du temps activé et défini comme courant', [
                'emploi_temps_id' => $emploi_temp->id,
                'classe_id' => $emploi_temp->classe_id,
                'user_id' => Auth::id(),
            ]);
        }

        return redirect()->route('esbtp.emploi-temps.show', ['emploi_temp' => $emploi_temp->id])
            ->with('success', 'Emploi du temps mis à jour avec succès.');
    }

    /**
     * Supprime un emploi du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ESBTPEmploiTemps $emploi_temp)
    {
        // Vérifier si l'utilisateur a la permission de supprimer les emplois du temps
        if (! auth()->user()->can('timetables.delete')) {
            abort(403, 'Accès non autorisé. Permission de suppression requise.');
        }

        // Vérifier si l'emploi du temps a des séances associées
        $seancesCount = $emploi_temp->seances()->count();

        // Si l'emploi du temps a des séances associées et que la suppression forcée n'est pas demandée
        if ($seancesCount > 0 && ! $request->has('force_delete')) {
            // Journaliser la tentative de suppression
            \Log::warning('Tentative de suppression d\'un emploi du temps avec des séances associées', [
                'emploi_temps_id' => $emploi_temp->id,
                'seances_count' => $seancesCount,
                'user_id' => auth()->id(),
            ]);

            // Rediriger avec un message d'avertissement et un paramètre pour confirmer la suppression forcée
            return redirect()->route('esbtp.emploi-temps.show', $emploi_temp)
                ->with('warning', "Cet emploi du temps a {$seancesCount} séance(s) de cours associée(s). La suppression entraînera également la suppression de ces séances. Veuillez confirmer cette action.")
                ->with('show_force_delete', true);
        }

        // Journaliser la suppression
        \Log::info('Suppression de l\'emploi du temps', [
            'emploi_temps_id' => $emploi_temp->id,
            'force_delete' => $request->has('force_delete'),
            'user_id' => auth()->id(),
        ]);

        // Supprimer l'emploi du temps (les séances associées seront supprimées par l'événement de modèle)
        $emploi_temp->delete();

        return redirect()->route('esbtp.emploi-temps.index')
            ->with('success', 'Emploi du temps supprimé avec succès.');
    }

    /**
     * Affiche l'emploi du temps de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function studentTimetable()
    {
        $user = Auth::user();
        \Log::info('Utilisateur connecté:', ['user_id' => $user->id]);

        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();
        \Log::info('Étudiant trouvé:', ['etudiant_id' => $etudiant ? $etudiant->id : null]);

        if (! $etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer l'inscription active de l'étudiant pour l'année en cours
        $inscription = $etudiant->inscriptions()
            ->where('status', 'active')
            ->whereHas('anneeUniversitaire', function ($query) {
                $query->where('is_current', true);
            })
            ->first();
        \Log::info('Inscription trouvée:', [
            'inscription_id' => $inscription ? $inscription->id : null,
            'classe_id' => $inscription ? $inscription->classe_id : null,
            'status' => $inscription ? $inscription->status : null,
            'annee_universitaire' => $inscription && $inscription->anneeUniversitaire ? [
                'id' => $inscription->anneeUniversitaire->id,
                'name' => $inscription->anneeUniversitaire->name,
                'is_current' => $inscription->anneeUniversitaire->is_current,
            ] : null,
        ]);

        if (! $inscription) {
            return view('etudiants.emploi-temps', [
                'etudiant' => $etudiant,
                'emploiTemps' => null,
                'seances' => collect(),
                'inscription' => null,
            ])->with('warning', 'Aucune inscription active trouvée pour l\'année en cours.');
        }

        // Récupérer l'emploi du temps actif pour la classe de l'étudiant
        $emploiTemps = ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('is_current', true);
            })
            ->orderBy('created_at', 'desc')
            ->first();
        \Log::info('Emploi du temps trouvé:', [
            'emploi_temps_id' => $emploiTemps ? $emploiTemps->id : null,
            'classe_id' => $emploiTemps ? $emploiTemps->classe_id : null,
            'is_active' => $emploiTemps ? $emploiTemps->is_active : null,
            'is_current' => $emploiTemps ? $emploiTemps->is_current : null,
            'sql' => ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
                ->where(function ($query) {
                    $query->where('is_active', true)
                        ->orWhere('is_current', true);
                })
                ->orderBy('created_at', 'desc')
                ->toSql(),
            'bindings' => ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
                ->where(function ($query) {
                    $query->where('is_active', true)
                        ->orWhere('is_current', true);
                })
                ->orderBy('created_at', 'desc')
                ->getBindings(),
            'total_emplois_temps' => ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)->count(),
            'emplois_temps_actifs' => ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
                ->where(function ($query) {
                    $query->where('is_active', true)
                        ->orWhere('is_current', true);
                })
                ->count(),
        ]);

        if (! $emploiTemps) {
            return view('etudiants.emploi-temps', [
                'etudiant' => $etudiant,
                'emploiTemps' => null,
                'seances' => collect(),
                'inscription' => $inscription,
            ])->with('warning', 'Aucun emploi du temps n\'est actuellement disponible pour votre classe.');
        }

        // Charger les séances avec leurs relations
        $seances = $emploiTemps->seances()
            ->with(['matiere', 'teacher.user'])
            ->where('is_active', true)
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get();

        \Log::info('Séances trouvées avant groupement:', [
            'nombre_seances' => $seances->count(),
            'seances' => $seances->map(function ($seance) {
                return [
                    'id' => $seance->id,
                    'jour' => $seance->jour,
                    'heure_debut' => $seance->heure_debut,
                    'heure_fin' => $seance->heure_fin,
                    'matiere' => $seance->matiere ? $seance->matiere->name : null,
                    'enseignant' => $seance->enseignantName,
                ];
            })->toArray(),
        ]);

        // Grouper les séances par jour
        $seancesGroupees = $seances->groupBy('jour');

        \Log::info('Séances après groupement:', [
            'jours_avec_seances' => $seancesGroupees->keys()->toArray(),
            'nombre_seances_par_jour' => $seancesGroupees->map->count()->toArray(),
        ]);

        return view('etudiants.emploi-temps', compact('etudiant', 'emploiTemps', 'inscription', 'seancesGroupees'));
    }

    public function setAsCurrent($id)
    {
        $emploiTemps = ESBTPEmploiTemps::findOrFail($id);
        // No policy-based authorization

        ESBTPEmploiTemps::setAsCurrent($id);

        return redirect()->back()->with('success', 'Emploi du temps défini comme actuel.');
    }

    public function getCurrentForClass($classeId)
    {
        $emploiTemps = ESBTPEmploiTemps::where('classe_id', $classeId)
            ->where('is_current', true)
            ->first();

        if (! $emploiTemps) {
            return response()->json(['message' => 'Aucun emploi du temps actuel trouvé pour cette classe.'], 404);
        }

        // No policy-based authorization
        return response()->json($emploiTemps->load('seances'));
    }

    /**
     * Affiche le formulaire pour ajouter une séance à un emploi du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function addSession(ESBTPEmploiTemps $emploi_temp)
    {
        $this->authorize('create', ESBTPSeanceCours::class);

        // PR3 chantier emploi-temps-lmd-unification (2026-05-22) :
        // REDIRECTION vers seances-cours/create — l'ancienne vue add-session.blade.php
        // utilisait <select> natif (viole rule premium-selects) et logique BTS-only
        // (whereHas('filieres') ne fonctionnait pas pour LMD).
        //
        // La route /emploi-temps/{id}/add-session reste fonctionnelle mais redirige
        // vers /seances-cours/create?emploi_temps_id=X qui supporte BTS+LMD de maniere
        // canonique via MatiereTreeBuilder + UI premium namespace sce-*.
        //
        // Pour reactiver l'ancien partial premium : restaurer la logique ci-dessous
        // (avec override MatiereTreeBuilder applique).
        if (request()->boolean('use_legacy_partial')) {
            return $this->addSessionLegacyPartial($emploi_temp);
        }

        return redirect()->route('esbtp.seances-cours.create', [
            'emploi_temps_id' => $emploi_temp->id,
            'jour' => request('jour'),
            'heure_debut' => request('heure_debut'),
        ]);
    }

    /**
     * Ancien comportement `addSession()` preserve en methode privee pour rollback rapide
     * si necessaire. Use ?use_legacy_partial=1 sur l'URL pour la reactiver.
     *
     * PR3 chantier emploi-temps-lmd-unification : refactor LMD-aware via MatiereTreeBuilder.
     * Garde le partial add-session.blade.php fonctionnel (rule strangler fig).
     */
    private function addSessionLegacyPartial(ESBTPEmploiTemps $emploi_temp)
    {
        // PR3 fix : utilise MatiereTreeBuilder canonical (SSOT) au lieu de whereHas('filieres')
        // BTS-only qui retournait 0 matieres pour LMD.
        $planificationData = [];
        if ($emploi_temp->classe && $emploi_temp->annee) {
            $planificationData = $this->getPlanificationDataForClasse(
                $emploi_temp->classe,
                $emploi_temp->annee,
                $emploi_temp->semestre
            );

            if (($emploi_temp->classe->systeme_academique ?? '') === 'LMD') {
                $planificationData = app(\App\Services\LMD\MatiereTreeBuilder::class)
                    ->buildForPlanning($planificationData, $emploi_temp->classe);
            }
        }

        $matieresPlanifiees = $planificationData['matieres_planifiees'] ?? collect();

        // Extraire les ESBTPMatiere depuis les matieresPlanifiees (compat avec view legacy).
        // PR3 : utilise la structure $row['matiere'] retournee par MatiereTreeBuilder.
        $matieres = collect($matieresPlanifiees)->map(function ($row) use ($emploi_temp) {
            $matiere = is_array($row) ? ($row['matiere'] ?? null) : $row;
            if (!$matiere) {
                return null;
            }

            $planification = ESBTPPlanificationAcademique::where('annee_universitaire_id', $emploi_temp->annee_universitaire_id)
                ->where('filiere_id', $emploi_temp->classe->filiere_id)
                ->where('niveau_etude_id', $emploi_temp->classe->niveau_etude_id)
                ->where('matiere_id', $matiere->id)
                ->with('enseignantPrincipal')
                ->first();

            $formatHeures = function ($heures) {
                $h = floor($heures);
                $m = round(($heures - $h) * 60);
                return ($m > 0) ? $h.'h'.($m < 10 ? '0' : '').$m : $h.'h';
            };

            if ($planification) {
                $heuresEffectuees = $planification->heures_effectuees ?? 0;
                $volumeTotal = $planification->volume_horaire_total;
                $heuresRestantes = max(0, $volumeTotal - $heuresEffectuees);

                $matiere->volume_info = [
                    'volume_total' => $volumeTotal,
                    'heures_effectuees' => $heuresEffectuees,
                    'heures_restantes' => $heuresRestantes,
                    'volume_total_formatted' => $formatHeures($volumeTotal),
                    'heures_effectuees_formatted' => $formatHeures($heuresEffectuees),
                    'heures_restantes_formatted' => $formatHeures($heuresRestantes),
                    'pourcentage_utilise' => $volumeTotal > 0 ? round(($heuresEffectuees / $volumeTotal) * 100, 1) : 0,
                    'est_complete' => $heuresRestantes <= 0,
                    'enseignant_principal' => $planification->enseignantPrincipal,
                ];
            } else {
                $matiere->volume_info = [
                    'volume_total' => 0,
                    'heures_effectuees' => 0,
                    'heures_restantes' => 0,
                    'volume_total_formatted' => '0h',
                    'heures_effectuees_formatted' => '0h',
                    'heures_restantes_formatted' => '0h',
                    'pourcentage_utilise' => 0,
                    'est_complete' => false,
                    'non_configuree' => true,
                    'enseignant_principal' => null,
                ];
            }

            return $matiere;
        })->filter()->values();

        // Récupérer les enseignants avec leurs disponibilités
        $enseignants = ESBTPTeacher::with(['user', 'availabilities'])->get();

        // Préparer les données de disponibilités pour chaque enseignant
        $enseignantsAvecDisponibilites = $enseignants->map(function ($enseignant) {
            $enseignant->availability_data = $this->prepareAvailabilityData($enseignant);

            return $enseignant;
        });

        return view('esbtp.emploi-temps.add-session', compact('emploi_temp', 'matieres', 'enseignantsAvecDisponibilites'));
    }

    /**
     * Enregistre une nouvelle séance pour un emploi du temps.
     *
     * @return \Illuminate\Http\Response
     */
    public function storeSession(\App\Http\Requests\StoreSeanceCoursRequest $request, ESBTPEmploiTemps $emploi_temp)
    {
        $validated = $request->validated();

        $validated['emploi_temps_id'] = $emploi_temp->id;

        // Calculer la durée de la séance en heures
        $heureDebut = \Carbon\Carbon::parse($validated['heure_debut']);
        $heureFin = \Carbon\Carbon::parse($validated['heure_fin']);
        $dureeSeance = $heureFin->diffInMinutes($heureDebut) / 60; // Convertir en heures

        // Vérifier s'il existe une planification académique pour cette matière
        $planification = ESBTPPlanificationAcademique::where('annee_universitaire_id', $validated['annee_universitaire_id'])
            ->where('filiere_id', $emploi_temp->classe->filiere_id)
            ->where('niveau_etude_id', $emploi_temp->classe->niveau_etude_id)
            ->where('matiere_id', $validated['matiere_id'])
            ->first();

        // Vérifier si le volume horaire n'est pas dépassé
        if ($planification) {
            $heuresEffectuees = $planification->heures_effectuees ?? 0;
            $volumeTotal = $planification->volume_horaire_total;

            if (($heuresEffectuees + $dureeSeance) > $volumeTotal) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'heure_fin' => 'Cette séance dépasserait le volume horaire total de la matière. Heures disponibles: '.
                                      ($volumeTotal - $heuresEffectuees).'h sur '.$volumeTotal.'h total.',
                    ]);
            }
        }

        $seance = new \App\Models\ESBTPSeanceCours;
        $seance->emploi_temps_id = $validated['emploi_temps_id'];
        $seance->classe_id = $validated['classe_id'];
        $seance->matiere_id = $validated['matiere_id'];
        $seance->enseignant_id = $validated['enseignant_id'];
        $seance->type_seance = $validated['type_seance'];
        $seance->jour = $validated['jour'];
        $seance->heure_debut = $validated['heure_debut'];
        $seance->heure_fin = $validated['heure_fin'];
        $seance->salle = $validated['salle'] ?? null;
        $seance->description = $validated['description'] ?? null;
        $seance->annee_universitaire_id = $validated['annee_universitaire_id'];
        $seance->is_active = true;
        $seance->save();

        // Mettre à jour les heures effectuées dans la planification académique
        if ($planification) {
            $planification->heures_effectuees = ($planification->heures_effectuees ?? 0) + $dureeSeance;
            $planification->derniere_mise_a_jour_heures = now();
            $planification->save();

            \Log::info('Volume horaire mis à jour', [
                'planification_id' => $planification->id,
                'matiere_id' => $validated['matiere_id'],
                'duree_seance' => $dureeSeance,
                'heures_effectuees_avant' => $planification->heures_effectuees - $dureeSeance,
                'heures_effectuees_apres' => $planification->heures_effectuees,
                'volume_total' => $planification->volume_horaire_total,
            ]);
        }

        return redirect()->route('esbtp.emploi-temps.show', $validated['emploi_temps_id'])
            ->with('success', 'Séance ajoutée avec succès. '.
                   ($planification ? "Volume horaire mis à jour: {$planification->heures_effectuees}h/{$planification->volume_horaire_total}h" : ''));
    }

    /**
     * Affiche les emplois du temps pour la journée en cours.
     *
     * @return \Illuminate\Http\Response
     */
    public function today()
    {
        // Récupérer le jour de la semaine actuel (0 = Lundi, 1 = Mardi, etc.)
        $jourActuel = now()->dayOfWeekIso - 1; // dayOfWeekIso retourne 1 pour lundi, 2 pour mardi, etc.

        // Récupérer la date actuelle
        $dateActuelle = now()->format('Y-m-d');

        // Récupérer les emplois du temps actifs
        $emploisTempsActifs = ESBTPEmploiTemps::where('is_active', true)
            ->where('date_debut', '<=', $dateActuelle)
            ->where('date_fin', '>=', $dateActuelle)
            ->with(['classe', 'classe.filiere', 'classe.niveau'])
            ->get();

        // Récupérer les IDs des emplois du temps actifs
        $emploisTempsIds = $emploisTempsActifs->pluck('id')->toArray();

        // Récupérer les séances de cours pour aujourd'hui
        $seancesAujourdhui = ESBTPSeanceCours::whereIn('emploi_temps_id', $emploisTempsIds)
            ->where('jour', $jourActuel)
            ->with(['matiere', 'enseignant', 'emploiTemps.classe'])
            ->orderBy('heure_debut')
            ->get();

        // Grouper les séances par classe
        $seancesParClasse = $seancesAujourdhui->groupBy(function ($seance) {
            return $seance->emploiTemps->classe->name ?? 'Non définie';
        });

        // Statistiques
        $totalSeancesAujourdhui = $seancesAujourdhui->count();
        $totalClassesAujourdhui = $seancesParClasse->count();

        // Noms des jours pour l'affichage
        $joursNoms = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];

        // Jour actuel en texte
        $jourActuelTexte = $joursNoms[$jourActuel] ?? 'Jour inconnu';

        return view('esbtp.emploi-temps.today', compact(
            'seancesAujourdhui',
            'seancesParClasse',
            'totalSeancesAujourdhui',
            'totalClassesAujourdhui',
            'jourActuelTexte',
            'dateActuelle'
        ));
    }

    /**
     * Activate all timetables.
     *
     * @return \Illuminate\Http\Response
     */
    public function activateAll()
    {
        if (! auth()->user()->can('timetables.edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Get counts before update
            $totalTimetables = ESBTPEmploiTemps::count();
            $activeTimetables = ESBTPEmploiTemps::where('is_active', true)->count();
            $currentTimetables = ESBTPEmploiTemps::where('is_current', true)->count();

            // First, set all timetables to inactive and not current
            DB::table('esbtp_emploi_temps')->update([
                'is_active' => false,
                'is_current' => false,
            ]);

            // For each class, find the most recent timetable and set it as active and current
            $classes = ESBTPClasse::all();
            foreach ($classes as $classe) {
                // Find the most recent timetable for this class
                $mostRecentTimetable = ESBTPEmploiTemps::where('classe_id', $classe->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($mostRecentTimetable) {
                    // Set the most recent one to active and current
                    $mostRecentTimetable->update([
                        'is_active' => true,
                        'is_current' => true,
                    ]);
                }
            }

            // Log the action
            \Log::info('Activated most recent timetables for each class', [
                'user_id' => auth()->id(),
                'total_timetables' => $totalTimetables,
                'active_timetables_before' => $activeTimetables,
                'current_timetables_before' => $currentTimetables,
                'active_timetables_after' => ESBTPEmploiTemps::where('is_active', true)->count(),
                'current_timetables_after' => ESBTPEmploiTemps::where('is_current', true)->count(),
            ]);

            return redirect()->route('esbtp.emploi-temps.index')
                ->with('success', 'Les emplois du temps les plus récents pour chaque classe ont été activés avec succès.');
        } catch (\Exception $e) {
            return redirect()->route('esbtp.emploi-temps.index')
                ->with('error', 'Une erreur est survenue lors de l\'activation des emplois du temps : '.$e->getMessage());
        }
    }

    /**
     * Set a timetable as current.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function setCurrent($id)
    {
        if (! auth()->user()->can('timetables.edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $emploiTemps = ESBTPEmploiTemps::findOrFail($id);

            // Use the model's setAsCurrent method
            $result = ESBTPEmploiTemps::setAsCurrent($id);

            if ($result) {
                // Log the action
                \Log::info('Set timetable as current', [
                    'user_id' => auth()->id(),
                    'timetable_id' => $id,
                    'classe_id' => $emploiTemps->classe_id,
                    'classe_name' => $emploiTemps->classe->name ?? 'Unknown',
                ]);

                return redirect()->back()
                    ->with('success', 'L\'emploi du temps a été défini comme courant avec succès.');
            } else {
                return redirect()->back()
                    ->with('error', 'Une erreur est survenue lors de la définition de l\'emploi du temps comme courant.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Une erreur est survenue : '.$e->getMessage());
        }
    }

    /**
     * Générer un PDF de l'emploi du temps.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePdf($id)
    {
        return $this->respondWithEmploiTempsPdf($id, 'attachment');
    }

    /**
     * Aperçu inline du PDF de l'emploi du temps (Content-Disposition: inline).
     */
    public function previewPdf($id)
    {
        return $this->respondWithEmploiTempsPdf($id, 'inline');
    }

    /**
     * Construit la response PDF emploi du temps avec disposition donnée.
     */
    private function respondWithEmploiTempsPdf($id, string $disposition)
    {
        try {
            $emploiTemps = ESBTPEmploiTemps::with([
                'seances.matiere',
                'classe',
                'classe.filiere',
                'classe.niveau',
                'annee',
            ])->findOrFail($id);

            $pdfService = app(ESBTPPDFService::class);
            $pdf = $pdfService->genererEmploiTempsPDF($emploiTemps);

            $filename = 'emploi_temps_'.$emploiTemps->classe->name.'_'.now()->format('Y-m-d').'.pdf';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du PDF de l\'emploi du temps', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                'emploi_temps_id' => $id,
            ]);

            // Pour debug - afficher l'erreur complète temporairement
            if (config('app.debug')) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la génération du PDF.');
        }
    }

    /**
     * Récupère les configurations depuis les settings pour les PDF
     */
    private function getPDFConfig()
    {
        return [
            // Informations de l'établissement
            'school_name' => SettingsHelper::get('school_name', config('app.name', 'KLASSCI')),
            'school_type' => SettingsHelper::get('school_type', ''),
            'school_authorization' => SettingsHelper::get('school_authorization_number', ''),
            'school_address' => SettingsHelper::get('school_address', ''),
            'school_phone' => SettingsHelper::get('school_phone', ''),
            'school_email' => SettingsHelper::get('school_email', ''),
            'school_website' => SettingsHelper::get('school_website', ''),
            'school_city' => SettingsHelper::get('school_city', ''),
            'school_country' => SettingsHelper::get('school_country', 'Côte d\'Ivoire'),
            'director_name' => SettingsHelper::get('director_name', ''),
            'director_title' => SettingsHelper::get('director_title', 'Directeur'),
            'school_logo' => SettingsHelper::get('school_logo', ''),

            // Configuration PDF
            'pdf_margin_top' => SettingsHelper::get('pdf.margin_top', 15),
            'pdf_margin_bottom' => SettingsHelper::get('pdf.margin_bottom', 15),
            'pdf_margin_left' => SettingsHelper::get('pdf.margin_left', 10),
            'pdf_margin_right' => SettingsHelper::get('pdf.margin_right', 10),

            // Configuration spécifique emploi du temps
            'timetable_show_logo' => SettingsHelper::get('timetable_show_logo', '1'),
            'timetable_show_header' => SettingsHelper::get('timetable_show_header', '1'),
            'timetable_show_stats' => SettingsHelper::get('timetable_show_stats', '1'),
        ];
    }

    /**
     * Prépare le logo en base64 pour l'inclusion dans les PDF
     */
    private function prepareLogoBase64($logoPath)
    {
        if (empty($logoPath)) {
            return null;
        }

        // Priorité 1: Vérifier dans storage/app/public/ (logos uploadés)
        $storagePath = storage_path('app/public/'.$logoPath);
        if (file_exists($storagePath)) {
            $logoType = pathinfo($storagePath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($storagePath);

            return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
        }

        // Priorité 2: Vérifier dans public/ (compatibilité)
        $publicPath = public_path($logoPath);
        if (file_exists($publicPath)) {
            $logoType = pathinfo($publicPath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($publicPath);

            return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
        }

        return null;
    }

    /**
     * Prévisualise l'emploi du temps en affichant le PDF réel inline
     * (même rendu que le téléchargement, ouvert dans le viewer natif du navigateur).
     */
    public function previewEmploiTemps($id)
    {
        try {
            $emploiTemps = ESBTPEmploiTemps::with([
                'seances.matiere',
                'classe',
                'classe.filiere',
                'classe.niveau',
                'annee',
            ])->findOrFail($id);

            // Générer le vrai PDF (même pipeline que le téléchargement)
            $pdfService = app(ESBTPPDFService::class);
            $pdf = $pdfService->genererEmploiTempsPDF($emploiTemps);

            $filename = 'apercu_emploi_temps_'.($emploiTemps->classe->name ?? 'classe').'_'.now()->format('Y-m-d').'.pdf';

            // Afficher inline dans le viewer PDF du navigateur (pas de téléchargement forcé)
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la prévisualisation de l\'emploi du temps', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'emploi_temps_id' => $id,
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la prévisualisation.');
        }
    }

    /**
     * Prépare les données de disponibilité d'un enseignant pour l'affichage
     * Format standardisé: $availability[$day][$hourIndex]
     */
    private function prepareAvailabilityData($teacher)
    {
        // Définition des créneaux horaires (8h-18h = 11 créneaux d'1h)
        $hours = range(8, 18);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']; // Pas de dimanche

        // Initialiser toutes les cases comme indisponibles
        $availability = [];
        foreach ($days as $day) {
            $availability[$day] = array_fill(0, count($hours), 'unavailable');
        }

        // Traiter les disponibilités enregistrées
        foreach ($teacher->availabilities as $avail) {
            // Mapping jour de semaine (0=Lundi, 1=Mardi, etc.)
            $dayName = $days[$avail->day_of_week] ?? null;
            if (! $dayName) {
                continue;
            }

            // Parser les heures depuis les timestamps
            if ($avail->start_time instanceof \Carbon\Carbon) {
                $startHour = $avail->start_time->hour;
                $endHour = $avail->end_time->hour;
            } else {
                // Format TIME ou string
                $startHour = (int) substr($avail->start_time, 11, 2); // Position 11-12 pour heure
                $endHour = (int) substr($avail->end_time, 11, 2);
            }

            // Décomposer les créneaux multi-heures en créneaux d'1h
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $hourIndex = $hour - 8; // 8h = index 0
                if ($hourIndex >= 0 && $hourIndex < count($hours)) {
                    $availability[$dayName][$hourIndex] = $avail->availability_type;
                }
            }
        }

        return $availability;
    }

    private function calculateTextColor(string $hex, string $fallback = '#ffffff'): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return $fallback;
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;

        return $luminance > 0.6 ? '#0f172a' : '#ffffff';
    }

    /**
     * Refresh emplois du temps avec filtres AJAX (sans reload de page)
     */
    public function refresh(Request $request)
    {
        \Log::info('🔄 Refresh emplois temps AJAX', $request->all());

        // Récupérer l'année universitaire courante
        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $anneeUniversitaire) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année universitaire active trouvée',
            ], 404);
        }

        // Query de base
        $query = ESBTPEmploiTemps::with([
            'classe.filiere',
            'classe.niveau',
            'annee',
        ])->where('annee_universitaire_id', $anneeUniversitaire->id);

        // Filtrage par filière
        if ($request->filled('filiere_id')) {
            $query->whereHas('classe', function ($q) use ($request) {
                $q->where('filiere_id', $request->filiere_id);
            });
        }

        // Filtrage par niveau
        if ($request->filled('niveau_id')) {
            $query->whereHas('classe', function ($q) use ($request) {
                $q->where('niveau_etude_id', $request->niveau_id);
            });
        }

        // Filtrage par classe
        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        // Filtrage par période automatique (basée sur les dates)
        if ($request->filled('period_status')) {
            $today = Carbon::today();
            if ($request->period_status === 'current') {
                $query->whereDate('date_debut', '<=', $today)
                    ->whereDate('date_fin', '>=', $today);
            } elseif ($request->period_status === 'upcoming') {
                $query->whereDate('date_debut', '>', $today);
            } elseif ($request->period_status === 'expired') {
                $query->whereDate('date_fin', '<', $today);
            }
        }

        // Filtrage par emploi du temps courant
        if ($request->filled('is_current')) {
            if ($request->is_current === '1' || $request->is_current === 'true') {
                $query->where('is_current', true);
            } elseif ($request->is_current === '0' || $request->is_current === 'false') {
                $query->where('is_current', false);
            }
        }

        // Filtrage par semaine (plage de dates au format "date_debut|date_fin")
        if ($request->filled('semaine')) {
            $dates = explode('|', $request->semaine);
            if (count($dates) === 2) {
                $query->where('date_debut', $dates[0])
                    ->where('date_fin', $dates[1]);
            }
        }

        // Récupérer les résultats
        $emploisTemps = $query->orderBy('date_debut', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('✅ Résultats trouvés', ['count' => $emploisTemps->count()]);

        // Render les partiels
        $timetableShortcut = app(TimetableShortcutService::class)->getShortcutSummary($anneeUniversitaire);

        return response()->json([
            'success' => true,
            'html_cards' => view('esbtp.emploi-temps.partials.cards', compact('emploisTemps', 'timetableShortcut'))->render(),
            'html_compact' => view('esbtp.emploi-temps.partials.cards-compact', compact('emploisTemps'))->render(),
            'html_table' => view('esbtp.emploi-temps.partials.table-rows', compact('emploisTemps', 'timetableShortcut'))->render(),
            'count' => $emploisTemps->count(),
        ]);
    }

    public function quickGenerate(Request $request, TimetableShortcutService $shortcutService)
    {
        $user = auth()->user();

        if (! $user || (! $user->can('timetables.create'))) {
            abort(403);
        }

        $validated = $request->validate([
            'classes' => 'required|array|min:1',
            'classes.*' => 'integer',
            'semestre' => 'required|string',
            'modes' => 'array',
            'modes.*' => 'in:empty,duplicate',
        ]);

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return redirect()->back()->with('error', "Aucune année universitaire active n'est définie.");
        }

        $items = $shortcutService->getClassesNeedingTimetables($anneeEnCours);
        if (empty($items)) {
            return redirect()->back()->with('info', "Aucune classe n'a besoin d'un nouvel emploi du temps.");
        }

        $itemsByClass = collect($items)->keyBy(function ($item) {
            return $item['class']->id;
        })->all();
        $selectedClasses = array_map('intval', $validated['classes']);

        $createdCount = 0;
        $skippedCount = 0;
        $fallbackCount = 0;
        $availabilitySkipCount = 0;

        DB::beginTransaction();
        try {
            foreach ($selectedClasses as $classeId) {
                if (! isset($itemsByClass[$classeId])) {
                    $skippedCount++;

                    continue;
                }

                $item = $itemsByClass[$classeId];
                $classe = $item['class'];
                $targetStart = $item['target_start'];
                $targetEnd = $item['target_end'];
                $source = $item['source'];

                $mode = $validated['modes'][$classeId] ?? ($source ? 'duplicate' : 'empty');
                if ($mode === 'duplicate' && ! $source) {
                    $mode = 'empty';
                    $fallbackCount++;
                }

                $availabilityConflicts = [];
                if ($mode === 'duplicate' && $source) {
                    $source->loadMissing(['seances.teacher.availabilities', 'seances.matiere']);
                    $availabilityConflicts = $this->collectAvailabilityConflicts($source->seances, $targetStart);
                }

                $alreadyExists = ESBTPEmploiTemps::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('classe_id', $classe->id)
                    ->whereDate('date_debut', $targetStart->toDateString())
                    ->whereDate('date_fin', $targetEnd->toDateString())
                    ->exists();

                if ($alreadyExists) {
                    $skippedCount++;

                    continue;
                }

                $isActive = $targetStart->lte(Carbon::today()) && $targetEnd->gte(Carbon::today());

                $periodeLabel = sprintf(
                    'Semaine %s-%s',
                    $targetStart->format('d/m'),
                    $targetEnd->format('d/m')
                );

                $emploiTemps = ESBTPEmploiTemps::create([
                    'titre' => 'Emploi du temps - '.$classe->name.' ('.$periodeLabel.')',
                    'classe_id' => $classe->id,
                    'annee_universitaire_id' => $anneeEnCours->id,
                    'semestre' => $validated['semestre'],
                    'date_debut' => $targetStart->toDateString(),
                    'date_fin' => $targetEnd->toDateString(),
                    'is_active' => $isActive,
                    'is_current' => false,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                if ($mode === 'duplicate' && $source) {
                    $conflictIds = collect($availabilityConflicts)->pluck('seance_id')->filter()->all();

                    foreach ($source->seances as $seance) {
                        if (in_array($seance->id, $conflictIds, true)) {
                            $availabilitySkipCount++;
                            continue;
                        }
                        $newSeance = $seance->replicate();
                        $newSeance->emploi_temps_id = $emploiTemps->id;
                        $newSeance->classe_id = $classe->id;
                        $newSeance->annee_universitaire_id = $anneeEnCours->id;
                        $newSeance->homework_evaluation_id = null;
                        $newSeance->is_active = $isActive;

                        $dayOffset = is_numeric($seance->jour) ? ((int) $seance->jour - 1) : 0;
                        $newDateSeance = $targetStart->copy()->addDays($dayOffset);
                        $newSeance->date_seance = $newDateSeance->toDateString();

                        if ($seance->homework_due_date && $seance->date_seance) {
                            $deltaDays = Carbon::parse($seance->date_seance)->diffInDays($newDateSeance, false);
                            $newSeance->homework_due_date = Carbon::parse($seance->homework_due_date)->addDays($deltaDays)->toDateString();
                        }

                        $newSeance->save();
                    }
                }

                $createdCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Erreur génération rapide emplois du temps', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', "Erreur lors de la génération rapide: {$e->getMessage()}");
        }

        $message = "Génération terminée: {$createdCount} emploi(s) du temps créé(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} déjà existant(s) ont été ignoré(s).";
        }
        if ($fallbackCount > 0) {
            $message .= " {$fallbackCount} classe(s) ont été créées en mode vide (aucun emploi du temps à dupliquer).";
        }
        if ($availabilitySkipCount > 0) {
            $message .= " {$availabilitySkipCount} séance(s) ont été ignorée(s) (enseignants indisponibles ou déjà occupés).";
        }

        return redirect()->back()->with('success', $message);
    }

    public function quickGeneratePreview(Request $request, TimetableShortcutService $shortcutService)
    {
        $user = auth()->user();
        if (! $user || (! $user->can('timetables.create'))) {
            abort(403);
        }

        $validated = $request->validate([
            'classes' => 'required|array|min:1',
            'classes.*' => 'integer',
            'semestre' => 'required|string',
            'modes' => 'array',
            'modes.*' => 'in:empty,duplicate',
        ]);

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return response()->json([
                'success' => false,
                'message' => "Aucune année universitaire active n'est définie.",
            ], 422);
        }

        $items = $shortcutService->getClassesNeedingTimetables($anneeEnCours);
        if (empty($items)) {
            return response()->json([
                'success' => true,
                'conflicts' => [],
                'total_conflicts' => 0,
            ]);
        }

        $itemsByClass = collect($items)->keyBy(function ($item) {
            return $item['class']->id;
        })->all();

        $selectedClasses = array_map('intval', $validated['classes']);
        $conflictsPayload = [];
        $totalConflicts = 0;

        foreach ($selectedClasses as $classeId) {
            if (! isset($itemsByClass[$classeId])) {
                continue;
            }

            $item = $itemsByClass[$classeId];
            $classe = $item['class'];
            $targetStart = $item['target_start'];
            $source = $item['source'];

            $mode = $validated['modes'][$classeId] ?? ($source ? 'duplicate' : 'empty');
            if ($mode !== 'duplicate' || ! $source) {
                continue;
            }

            $source->loadMissing(['seances.teacher.availabilities', 'seances.matiere']);
            $conflicts = $this->collectAvailabilityConflicts($source->seances, $targetStart);
            if (! empty($conflicts)) {
                $conflictsPayload[] = [
                    'class_id' => $classe->id,
                    'class_name' => $classe->name,
                    'items' => $conflicts,
                ];
                $totalConflicts += count($conflicts);
            }
        }

        return response()->json([
            'success' => true,
            'conflicts' => $conflictsPayload,
            'total_conflicts' => $totalConflicts,
        ]);
    }

    /**
     * Duplique tous les emplois du temps d'une semaine source vers une semaine cible.
     * Utilisé par l'empty-state "La semaine précédente avait X plannings. Dupliquer →".
     *
     * Format des paramètres : "YYYY-MM-DD|YYYY-MM-DD" (date_debut|date_fin).
     * Les conflits enseignants sont détectés via collectAvailabilityConflicts() ; les
     * séances en conflit sont simplement ignorées (l'EDT est créé sans ces séances).
     * Les classes qui ont déjà un EDT pour la semaine cible sont sautées (idempotence).
     */
    public function duplicateWeek(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! $user->can('timetables.create')) {
            abort(403);
        }

        $validated = $request->validate([
            // Format accepté : "YYYY-MM-DD|YYYY-MM-DD" ou "YYYY-MM-DD HH:MM:SS|YYYY-MM-DD HH:MM:SS"
            // (les pills du rail incluent le time, le format court reste supporté pour les liens).
            'source_semaine' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?\|\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/'],
            'target_semaine' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?\|\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', 'different:source_semaine'],
        ]);

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return redirect()->back()->with('error', "Aucune année universitaire active n'est définie.");
        }

        [$sourceStart, $sourceEnd] = array_map('trim', explode('|', $validated['source_semaine']));
        [$targetStart, $targetEnd] = array_map('trim', explode('|', $validated['target_semaine']));

        try {
            $targetStartCarbon = Carbon::parse($targetStart);
            $targetEndCarbon = Carbon::parse($targetEnd);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Dates cibles invalides.');
        }

        $sources = ESBTPEmploiTemps::with(['seances.teacher.availabilities', 'seances.matiere'])
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->whereDate('date_debut', $sourceStart)
            ->whereDate('date_fin', $sourceEnd)
            ->get();

        if ($sources->isEmpty()) {
            return redirect()->back()->with('info', "Aucun emploi du temps trouvé pour la semaine source.");
        }

        $createdCount = 0;
        $skippedCount = 0;
        $availabilitySkipCount = 0;
        $today = Carbon::today();
        $isActiveTarget = $targetStartCarbon->lte($today) && $targetEndCarbon->gte($today);

        DB::beginTransaction();
        try {
            foreach ($sources as $source) {
                $alreadyExists = ESBTPEmploiTemps::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('classe_id', $source->classe_id)
                    ->whereDate('date_debut', $targetStartCarbon->toDateString())
                    ->whereDate('date_fin', $targetEndCarbon->toDateString())
                    ->exists();

                if ($alreadyExists) {
                    $skippedCount++;
                    continue;
                }

                $availabilityConflicts = $this->collectAvailabilityConflicts($source->seances, $targetStartCarbon);
                $conflictIds = collect($availabilityConflicts)->pluck('seance_id')->filter()->all();

                $periodeLabel = sprintf(
                    'Semaine %s-%s',
                    $targetStartCarbon->format('d/m'),
                    $targetEndCarbon->format('d/m')
                );

                $classeName = $source->classe->name ?? 'Classe';
                $newEmploiTemps = ESBTPEmploiTemps::create([
                    'titre' => "Emploi du temps - {$classeName} ({$periodeLabel})",
                    'classe_id' => $source->classe_id,
                    'annee_universitaire_id' => $anneeEnCours->id,
                    'semestre' => $source->semestre,
                    'date_debut' => $targetStartCarbon->toDateString(),
                    'date_fin' => $targetEndCarbon->toDateString(),
                    'is_active' => $isActiveTarget,
                    'is_current' => false,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                foreach ($source->seances as $seance) {
                    if (in_array($seance->id, $conflictIds, true)) {
                        $availabilitySkipCount++;
                        continue;
                    }
                    $newSeance = $seance->replicate();
                    $newSeance->emploi_temps_id = $newEmploiTemps->id;
                    $newSeance->classe_id = $source->classe_id;
                    $newSeance->annee_universitaire_id = $anneeEnCours->id;
                    $newSeance->homework_evaluation_id = null;
                    $newSeance->is_active = $isActiveTarget;

                    $dayOffset = is_numeric($seance->jour) ? ((int) $seance->jour - 1) : 0;
                    $newDateSeance = $targetStartCarbon->copy()->addDays($dayOffset);
                    $newSeance->date_seance = $newDateSeance->toDateString();

                    if ($seance->homework_due_date && $seance->date_seance) {
                        $deltaDays = Carbon::parse($seance->date_seance)->diffInDays($newDateSeance, false);
                        $newSeance->homework_due_date = Carbon::parse($seance->homework_due_date)->addDays($deltaDays)->toDateString();
                    }

                    $newSeance->save();
                }

                $createdCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Erreur duplication semaine', [
                'source' => $validated['source_semaine'],
                'target' => $validated['target_semaine'],
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', "Erreur lors de la duplication : {$e->getMessage()}");
        }

        if ($createdCount === 0) {
            return redirect()
                ->to(route('esbtp.emploi-temps.index', ['semaine' => $validated['target_semaine']]))
                ->with('info', "Aucun nouvel emploi du temps créé ({$skippedCount} classe(s) déjà présente(s) sur la semaine cible).");
        }

        $message = "{$createdCount} emploi(s) du temps dupliqué(s) vers la semaine cible.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} classe(s) ignorée(s) (EDT déjà présent).";
        }
        if ($availabilitySkipCount > 0) {
            $message .= " {$availabilitySkipCount} séance(s) omise(s) (conflits enseignant).";
        }

        return redirect()
            ->to(route('esbtp.emploi-temps.index', ['semaine' => $validated['target_semaine']]))
            ->with('success', $message);
    }

    private function collectAvailabilityConflicts($seances, Carbon $targetStart): array
    {
        $conflicts = [];

        foreach ($seances as $seance) {
            if (! $seance->teacher_id) {
                continue;
            }

            $check = $this->checkTeacherAvailabilityForSeance($seance, $targetStart);
            if (! $check['ok']) {
                $jourLabel = $this->resolveSeanceDayLabel($seance->jour);
                $conflicts[] = [
                    'seance_id' => $seance->id,
                    'matiere' => $seance->matiere->name ?? 'Matière',
                    'enseignant' => $seance->teacher->name ?? $seance->teacher?->user?->name ?? 'Enseignant',
                    'jour' => $jourLabel,
                    'heure_debut' => $this->normalizeTime($seance->heure_debut),
                    'heure_fin' => $this->normalizeTime($seance->heure_fin),
                    'reason' => $check['reason'] ?? 'conflict',
                    'message' => $check['message'] ?? 'Indisponible',
                ];
            }
        }

        return $conflicts;
    }

    private function checkTeacherAvailabilityForSeance(ESBTPSeanceCours $seance, Carbon $targetStart): array
    {
        $teacher = $seance->teacher;
        if (! $teacher) {
            return ['ok' => true];
        }

        $dayIndex = $this->resolveSeanceDayIndex($seance->jour);
        if ($dayIndex === null) {
            return ['ok' => true];
        }

        $targetDate = $targetStart->copy()->addDays($dayIndex)->toDateString();
        $startTime = $this->normalizeTime($seance->heure_debut);
        $endTime = $this->normalizeTime($seance->heure_fin);

        if ($startTime && $endTime) {
            $hasAvailability = $teacher->availabilities->isNotEmpty();
            if ($hasAvailability) {
                $available = $teacher->availabilities
                    ->filter(function (ESBTPTeacherAvailability $availability) use ($dayIndex) {
                        return (int) $availability->day_of_week === (int) $dayIndex
                            && in_array($availability->availability_type, ['available', 'preferred'], true);
                    })
                    ->first(function (ESBTPTeacherAvailability $availability) use ($startTime, $endTime) {
                        $availableStart = $this->normalizeTime($availability->start_time);
                        $availableEnd = $this->normalizeTime($availability->end_time);
                        if (! $availableStart || ! $availableEnd) {
                            return false;
                        }

                        return $availableStart <= $startTime && $availableEnd >= $endTime;
                    });

                if (! $available) {
                    return [
                        'ok' => false,
                        'reason' => 'unavailable',
                        'message' => "{$teacher->name} indisponible",
                    ];
                }
            }

            $conflictExists = ESBTPSeanceCours::where('teacher_id', $teacher->id)
                ->whereDate('date_seance', $targetDate)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('heure_debut', '<', $endTime)
                        ->where('heure_fin', '>', $startTime);
                })
                ->exists();

            if ($conflictExists) {
                return [
                    'ok' => false,
                    'reason' => 'occupied',
                    'message' => "{$teacher->name} occupé",
                ];
            }
        }

        return ['ok' => true];
    }

    private function resolveSeanceDayIndex($jour): ?int
    {
        $days = [
            1 => 0,
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            6 => 5,
        ];

        if (is_numeric($jour)) {
            return $days[(int) $jour] ?? null;
        }

        if (is_string($jour)) {
            $normalized = strtolower(trim($jour));
            $map = [
                'lundi' => 0,
                'mardi' => 1,
                'mercredi' => 2,
                'jeudi' => 3,
                'vendredi' => 4,
                'samedi' => 5,
                'monday' => 0,
                'tuesday' => 1,
                'wednesday' => 2,
                'thursday' => 3,
                'friday' => 4,
                'saturday' => 5,
            ];

            return $map[$normalized] ?? null;
        }

        return null;
    }

    private function resolveSeanceDayLabel($jour): string
    {
        $labels = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];

        if (is_numeric($jour)) {
            return $labels[(int) $jour] ?? 'Jour inconnu';
        }

        $normalized = strtolower(trim((string) $jour));
        $map = [
            'lundi' => 'Lundi',
            'monday' => 'Lundi',
            'mardi' => 'Mardi',
            'tuesday' => 'Mardi',
            'mercredi' => 'Mercredi',
            'wednesday' => 'Mercredi',
            'jeudi' => 'Jeudi',
            'thursday' => 'Jeudi',
            'vendredi' => 'Vendredi',
            'friday' => 'Vendredi',
            'samedi' => 'Samedi',
            'saturday' => 'Samedi',
        ];

        return $map[$normalized] ?? 'Jour inconnu';
    }

    private function normalizeTime($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        if (is_string($value)) {
            return substr($value, 0, 5);
        }

        return null;
    }
}
