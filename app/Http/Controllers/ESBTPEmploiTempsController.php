<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPPlanificationAcademique;
use App\Services\TimetableShortcutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\ESBTPPDFService;
use App\Helpers\SettingsHelper;
use Carbon\Carbon;

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
        $emploisTempsQuery = ESBTPEmploiTemps::with(['classe.filiere', 'classe.niveau', 'seances']);

        if ($anneeEnCours) {
            $emploisTempsQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }

        // Appliquer les filtres depuis l'URL
        // Filtrage par filière
        if ($request->filled('filiere_id')) {
            $emploisTempsQuery->whereHas('classe', function($q) use ($request) {
                $q->where('filiere_id', $request->filiere_id);
            });
        }

        // Filtrage par niveau
        if ($request->filled('niveau_id')) {
            $emploisTempsQuery->whereHas('classe', function($q) use ($request) {
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
            if (!$emploiTemps->date_debut || !$emploiTemps->date_fin) {
                return false;
            }
            $startDate = Carbon::parse($emploiTemps->date_debut);
            $endDate = Carbon::parse($emploiTemps->date_fin);
            return $today->between($startDate, $endDate);
        })->count();
        $totalSeances = ESBTPSeanceCours::count();

        // Emplois du temps de l'année en cours (déjà filtrés)
        $emploisTempsAnneeEnCours = $emploisTemps->count();

        // Passer l'année courante avec le bon nom pour la vue
        $anneeUniversitaireCourante = $anneeEnCours;

        $timetableShortcut = app(TimetableShortcutService::class)->getShortcutSummary($anneeEnCours);

        return view('esbtp.emploi-temps.index', compact(
            'emploisTemps', 'filieres', 'niveaux', 'classes', 'annees', 'anneeUniversitaireCourante',
            'totalEmploisTemps', 'emploisTempsActifs', 'totalSeances', 'emploisTempsAnneeEnCours', 'timetableShortcut'
        ));
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
            'lien_configuration' => null
        ];

        // NOUVELLE LOGIQUE : Récupérer uniquement les matières liées à cette combinaison filière/niveau
        $matieresLiees = ESBTPMatiere::where('is_active', true)
            ->whereHas('filieres', function($query) use ($classe) {
                $query->where('esbtp_filieres.id', $classe->filiere_id);
            })
            ->whereHas('niveaux', function($query) use ($classe) {
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
                ->when($semestre, function($query) use ($semestre) {
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
                ->with(['matiere', 'enseignantPrincipal', 'teachers.user', 'teachers.availabilities'])
                ->first();
            
            if ($planification) {
                $planifications->push($planification);
            }
        }

        if ($planifications->isEmpty()) {
            // Aucune planification configurée pour les matières liées
            $data['message_configuration'] = "Aucune planification académique n'a été configurée pour les matières de cette classe (" . $matieresLiees->count() . " matières disponibles). Veuillez d'abord configurer la planification.";
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
                'niveau_filter' => $classe->niveau_etude_id
            ]);
            return $data;
        }

        $data['planifications_configurees'] = true;
        
        // Traiter chaque planification pour calculer les heures restantes
        $matieresPlanifiees = collect();
        $enseignantsIds = collect();
        $teacherCache = [];
        $getTeacherModel = function ($userId) use (&$teacherCache) {
            if (!$userId) {
                return null;
            }

            if (!array_key_exists($userId, $teacherCache)) {
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
                    ->when($semestre, function($query) use ($semestre) {
                        // Si on a un semestre spécifique, filtrer les séances par période
                        // Cette logique peut être adaptée selon votre implémentation des semestres
                    })
                    // Left join pour obtenir l'attendance la plus récente
                    ->leftJoin('esbtp_teacher_attendances', function($join) {
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
                    ->where(function($query) {
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
                if ($principalTeacherModel && !$enseignantsSelectables->contains('id', $principalTeacherModel->id)) {
                    $enseignantsSelectables->push($principalTeacherModel);
                }
            }

            if (!empty($planification->enseignants_secondaires)) {
                foreach ($planification->enseignants_secondaires as $secondaryUserId) {
                    $secondaryTeacherModel = $getTeacherModel($secondaryUserId);
                    if ($secondaryTeacherModel && !$enseignantsSelectables->contains('id', $secondaryTeacherModel->id)) {
                        $enseignantsSelectables->push($secondaryTeacherModel);
                    }
                }
            }

            $enseignantsSelectables = $enseignantsSelectables->filter()->unique('id')->values();

            // Fonction helper pour formater les heures en XXhYY
            $formatHeures = function($heures) {
                $h = floor($heures);
                $m = round(($heures - $h) * 60);
                if ($m > 0) {
                    return $h . 'h' . ($m < 10 ? '0' : '') . $m;
                }
                return $h . 'h';
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
                'periode_fin' => $planification->periode_fin
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
        $formatHeuresTotal = function($heures) {
            $h = floor($heures);
            $m = round(($heures - $h) * 60);
            if ($m > 0) {
                return $h . 'h' . ($m < 10 ? '0' : '') . $m;
            }
            return $h . 'h';
        };

        $data['heures_totales'] = $heuresTotal;
        $data['heures_restantes'] = $heuresRestantesTotal;
        $data['heures_totales_formatted'] = $formatHeuresTotal($heuresTotal);
        $data['heures_restantes_formatted'] = $formatHeuresTotal($heuresRestantesTotal);

        // Récupérer les enseignants disponibles (tous les enseignants, mais marquer ceux qui sont assignés)
        $tousLesEnseignants = \App\Models\User::role('enseignant')
            ->where('is_active', true)
            ->with('enseignantProfile')
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
                'disponibilite' => $chargeActuelle < 500 ? 'Disponible' : ($chargeActuelle < 800 ? 'Chargé' : 'Surchargé')
            ]);
        }

        $data['enseignants_disponibles'] = $enseignantsDisponibles->sortBy([
            ['est_assigne_classe', 'desc'],
            ['charge_horaire_annuelle', 'asc']
        ]);

        return $data;
    }

    /**
     * Enregistre un nouvel emploi du temps.
     *
     * @param  \Illuminate\Http\Request  $request
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
                'date_fin' => 'La période de l\'emploi du temps ne doit pas dépasser 6 jours (du lundi au samedi).'
            ]);
        }

        // Créer l'emploi du temps
        $emploiTemps = new ESBTPEmploiTemps();
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
                    'is_current' => false
                ]);

            // S'assurer que le nouvel emploi du temps est bien actif et courant
            $emploiTemps->is_active = true;
            $emploiTemps->is_current = true;
            $emploiTemps->save();

            // Journaliser l'action
            \Log::info('Nouvel emploi du temps activé et défini comme courant', [
                'emploi_temps_id' => $emploiTemps->id,
                'classe_id' => $emploiTemps->classe_id,
                'user_id' => Auth::id()
            ]);
        }

        return redirect()->route('esbtp.emploi-temps.show', $emploiTemps->id)
            ->with('success', 'Emploi du temps créé avec succès.');
    }

    /**
     * Affiche un emploi du temps spécifique.
     *
     * @param  \App\Models\ESBTPEmploiTemps  $emploi_temp
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPEmploiTemps $emploi_temp)
    {
        // No policy-based authorization
        // Charger les séances pour cet emploi du temps
        $emploi_temp->load([
            'seances.matiere',
            'seances.teacher',
            'classe',
            'classe.filiere',
            'classe.niveau',
            'annee'
        ]);

        // Variable $seances pour la vue
        $seances = $emploi_temp->seances;

        // Grouper les séances par jour
        $seancesParJour = $emploi_temp->getSeancesParJour();

        // Noms des jours pour l'affichage
        $joursNoms = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];

        // Générer dynamiquement les créneaux horaires (pas de 15 minutes pour couvrir 08:30, 09:15, etc.)
        $timeSlots = $this->generateTimeSlots($seances);

        // Créer les variables $days pour la vue
        $days = array_keys($joursNoms);

        // Calcul des statistiques par matière
        $matiereStats = [];
        foreach ($emploi_temp->seances as $seance) {
            $matiereName = $seance->matiere ? $seance->matiere->name : 'Non définie';
            if (!isset($matiereStats[$matiereName])) {
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
        }

        // Renommer la variable pour la vue
        $emploiTemps = $emploi_temp;

        return view('esbtp.emploi-temps.show', compact(
            'emploiTemps', 'seances', 'seancesParJour',
            'joursNoms', 'matiereStats', 'timeSlots', 'days', 'planificationData'
        ));
    }

    /**
     * Affiche le formulaire d'édition d'un emploi du temps.
     *
     * @param  \App\Models\ESBTPEmploiTemps  $emploi_temp
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPEmploiTemps $emploi_temp)
    {
        \Log::info('Tentative d\'édition d\'emploi du temps', [
            'emploi_temps_id' => $emploi_temp->id,
            'user_id' => auth()->id(),
            'user_permissions' => auth()->user()->getAllPermissions()->pluck('name'),
            'user_roles' => auth()->user()->getRoleNames()
        ]);

        // No policy-based authorization
        $emploiTemps = $emploi_temp;

        // Ensure $emploiTemps is an object
        if (!is_object($emploiTemps)) {
            \Log::error('$emploiTemps is not an object', [
                'type' => gettype($emploiTemps),
                'value' => $emploiTemps
            ]);

            // Try to find the emploi_temp by ID if it's an integer
            if (is_numeric($emploiTemps)) {
                $emploiTemps = ESBTPEmploiTemps::find($emploiTemps);
                if (!$emploiTemps) {
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
            'annees_count' => $annees->count()
        ]);

        return view('esbtp.emploi-temps.edit', compact('emploiTemps', 'classes', 'annees'));
    }

    /**
     * Met à jour un emploi du temps.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPEmploiTemps  $emploi_temp
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
        $isBeingActivated = $request->has('is_active') && !$emploi_temp->is_active;
        $isBeingSetCurrent = $request->has('is_current') && !$emploi_temp->is_current;

        // Mettre à jour l'emploi du temps
        $emploi_temp->update($validated);

        // Si l'emploi du temps est activé ou défini comme courant, désactiver les autres
        if ($isBeingActivated || $isBeingSetCurrent) {
            // Désactiver tous les autres emplois du temps pour cette classe
            ESBTPEmploiTemps::where('id', '!=', $emploi_temp->id)
                ->where('classe_id', $emploi_temp->classe_id)
                ->update([
                    'is_active' => false,
                    'is_current' => false
                ]);

            // S'assurer que cet emploi du temps est bien actif et courant
            $emploi_temp->is_active = true;
            $emploi_temp->is_current = true;
            $emploi_temp->save();

            // Journaliser l'action
            \Log::info('Emploi du temps activé et défini comme courant', [
                'emploi_temps_id' => $emploi_temp->id,
                'classe_id' => $emploi_temp->classe_id,
                'user_id' => Auth::id()
            ]);
        }

        return redirect()->route('esbtp.emploi-temps.show', ['emploi_temp' => $emploi_temp->id])
            ->with('success', 'Emploi du temps mis à jour avec succès.');
    }

    /**
     * Supprime un emploi du temps.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPEmploiTemps  $emploi_temp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ESBTPEmploiTemps $emploi_temp)
    {
        // Vérifier si l'utilisateur a la permission de supprimer les emplois du temps
        if (!auth()->user()->can('delete_timetables')) {
            abort(403, 'Accès non autorisé. Permission de suppression requise.');
        }

        // Vérifier si l'emploi du temps a des séances associées
        $seancesCount = $emploi_temp->seances()->count();

        // Si l'emploi du temps a des séances associées et que la suppression forcée n'est pas demandée
        if ($seancesCount > 0 && !$request->has('force_delete')) {
            // Journaliser la tentative de suppression
            \Log::warning('Tentative de suppression d\'un emploi du temps avec des séances associées', [
                'emploi_temps_id' => $emploi_temp->id,
                'seances_count' => $seancesCount,
                'user_id' => auth()->id()
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
            'user_id' => auth()->id()
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

        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer l'inscription active de l'étudiant pour l'année en cours
        $inscription = $etudiant->inscriptions()
            ->where('status', 'active')
            ->whereHas('anneeUniversitaire', function($query) {
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
                'is_current' => $inscription->anneeUniversitaire->is_current
            ] : null
        ]);

        if (!$inscription) {
            return view('etudiants.emploi-temps', [
                'etudiant' => $etudiant,
                'emploiTemps' => null,
                'seances' => collect(),
                'inscription' => null
            ])->with('warning', 'Aucune inscription active trouvée pour l\'année en cours.');
        }

        // Récupérer l'emploi du temps actif pour la classe de l'étudiant
        $emploiTemps = ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
            ->where(function($query) {
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
                ->where(function($query) {
                    $query->where('is_active', true)
                          ->orWhere('is_current', true);
                })
                ->orderBy('created_at', 'desc')
                ->toSql(),
            'bindings' => ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
                ->where(function($query) {
                    $query->where('is_active', true)
                          ->orWhere('is_current', true);
                })
                ->orderBy('created_at', 'desc')
                ->getBindings(),
            'total_emplois_temps' => ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)->count(),
            'emplois_temps_actifs' => ESBTPEmploiTemps::where('classe_id', $inscription->classe_id)
                ->where(function($query) {
                    $query->where('is_active', true)
                          ->orWhere('is_current', true);
                })
                ->count()
        ]);

        if (!$emploiTemps) {
            return view('etudiants.emploi-temps', [
                'etudiant' => $etudiant,
                'emploiTemps' => null,
                'seances' => collect(),
                'inscription' => $inscription
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
            'seances' => $seances->map(function($seance) {
                return [
                    'id' => $seance->id,
                    'jour' => $seance->jour,
                    'heure_debut' => $seance->heure_debut,
                    'heure_fin' => $seance->heure_fin,
                    'matiere' => $seance->matiere ? $seance->matiere->name : null,
                    'enseignant' => $seance->enseignantName
                ];
            })->toArray()
        ]);

        // Grouper les séances par jour
        $seancesGroupees = $seances->groupBy('jour');

        \Log::info('Séances après groupement:', [
            'jours_avec_seances' => $seancesGroupees->keys()->toArray(),
            'nombre_seances_par_jour' => $seancesGroupees->map->count()->toArray()
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

        if (!$emploiTemps) {
            return response()->json(['message' => 'Aucun emploi du temps actuel trouvé pour cette classe.'], 404);
        }

        // No policy-based authorization
        return response()->json($emploiTemps->load('seances'));
    }

    /**
     * Affiche le formulaire pour ajouter une séance à un emploi du temps.
     *
     * @param ESBTPEmploiTemps $emploi_temp
     * @return \Illuminate\Http\Response
     */
    public function addSession(ESBTPEmploiTemps $emploi_temp)
    {
        $this->authorize('create', ESBTPSeanceCours::class);

        // NOUVELLE LOGIQUE : Utiliser la même approche que la planification générale
        // 1. Récupérer les matières réellement liées à cette combinaison filière/niveau
        $matieresLiees = ESBTPMatiere::where('is_active', true)
            ->whereHas('filieres', function($query) use ($emploi_temp) {
                $query->where('esbtp_filieres.id', $emploi_temp->classe->filiere_id);
            })
            ->whereHas('niveaux', function($query) use ($emploi_temp) {
                $query->where('esbtp_niveau_etudes.id', $emploi_temp->classe->niveau_etude_id);
            })
            ->with(['filieres', 'niveaux'])
            ->get();
        
        // 2. Pour chaque matière liée, récupérer sa planification académique
        $matieres = $matieresLiees->map(function ($matiere) use ($emploi_temp) {
            $planification = ESBTPPlanificationAcademique::where('annee_universitaire_id', $emploi_temp->annee_universitaire_id)
                ->where('filiere_id', $emploi_temp->classe->filiere_id)
                ->where('niveau_etude_id', $emploi_temp->classe->niveau_etude_id)
                ->where('matiere_id', $matiere->id)
                ->with('enseignantPrincipal')
                ->first();
            
            if ($planification) {
                $heuresEffectuees = $planification->heures_effectuees ?? 0;
                $volumeTotal = $planification->volume_horaire_total;
                $heuresRestantes = max(0, $volumeTotal - $heuresEffectuees);

                // Fonction helper pour formater les heures en XXh YYmin
                $formatHeures = function($heures) {
                    $h = floor($heures);
                    $m = round(($heures - $h) * 60);
                    if ($m > 0) {
                        return $h . 'h' . ($m < 10 ? '0' : '') . $m;
                    }
                    return $h . 'h';
                };

                $matiere->volume_info = [
                    'volume_total' => $volumeTotal,
                    'heures_effectuees' => $heuresEffectuees,
                    'heures_restantes' => $heuresRestantes,
                    'volume_total_formatted' => $formatHeures($volumeTotal),
                    'heures_effectuees_formatted' => $formatHeures($heuresEffectuees),
                    'heures_restantes_formatted' => $formatHeures($heuresRestantes),
                    'pourcentage_utilise' => $volumeTotal > 0 ? round(($heuresEffectuees / $volumeTotal) * 100, 1) : 0,
                    'est_complete' => $heuresRestantes <= 0,
                    'enseignant_principal' => $planification->enseignantPrincipal
                ];
            } else {
                // Matière liée mais pas encore configurée
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
                    'enseignant_principal' => null
                ];
            }
            
            return $matiere;
        });

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
     * @param Request $request
     * @param ESBTPEmploiTemps $emploi_temp
     * @return \Illuminate\Http\Response
     */
    public function storeSession(Request $request, ESBTPEmploiTemps $emploi_temp)
    {
        $this->authorize('create', ESBTPSeanceCours::class);

        $validated = $request->validate([
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'enseignant_id' => 'required|exists:users,id',
            'jour' => 'required|string|max:20',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'salle' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id'
        ]);

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
                        'heure_fin' => "Cette séance dépasserait le volume horaire total de la matière. Heures disponibles: " . 
                                      ($volumeTotal - $heuresEffectuees) . "h sur " . $volumeTotal . "h total."
                    ]);
            }
        }

        $seance = new \App\Models\ESBTPSeanceCours();
        $seance->emploi_temps_id = $validated['emploi_temps_id'];
        $seance->classe_id = $validated['classe_id'];
        $seance->matiere_id = $validated['matiere_id'];
        $seance->enseignant_id = $validated['enseignant_id'];
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
                'volume_total' => $planification->volume_horaire_total
            ]);
        }

        return redirect()->route('esbtp.emploi-temps.show', $validated['emploi_temps_id'])
            ->with('success', 'Séance ajoutée avec succès. ' . 
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
        $seancesParClasse = $seancesAujourdhui->groupBy(function($seance) {
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
        // Check if user is superAdmin
        if (!auth()->user()->hasRole('superAdmin')) {
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
                'is_current' => false
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
                        'is_current' => true
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
                ->with('error', 'Une erreur est survenue lors de l\'activation des emplois du temps : ' . $e->getMessage());
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
        // Check if user has permission
        if (!auth()->user()->hasRole('superAdmin') && !auth()->user()->hasRole('secretaire')) {
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
                ->with('error', 'Une erreur est survenue : ' . $e->getMessage());
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
        try {
            $emploiTemps = ESBTPEmploiTemps::with([
                'seances.matiere',
                'classe',
                'classe.filiere',
                'classe.niveau',
                'annee'
            ])->findOrFail($id);

            // Utiliser le service PDF pour générer le document
            $pdfService = app(ESBTPPDFService::class);
            $pdf = $pdfService->genererEmploiTempsPDF($emploiTemps);

            // Générer le nom du fichier
            $filename = 'emploi_temps_' . $emploiTemps->classe->name . '_' . now()->format('Y-m-d') . '.pdf';

            // Retourner le PDF pour téléchargement (Browsershot retourne le contenu binaire directement)
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du PDF de l\'emploi du temps', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'emploi_temps_id' => $id
            ]);

            // Pour debug - afficher l'erreur complète temporairement
            if (config('app.debug')) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
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
            'school_name' => SettingsHelper::get('school_name', 'École Spéciale du Bâtiment et des Travaux Publics'),
            'school_type' => SettingsHelper::get('school_type', 'Enseignement Supérieur Technique'),
            'school_authorization' => SettingsHelper::get('school_authorization_number', ''),
            'school_address' => SettingsHelper::get('school_address', ''),
            'school_phone' => SettingsHelper::get('school_phone', ''),
            'school_email' => SettingsHelper::get('school_email', ''),
            'school_website' => SettingsHelper::get('school_website', ''),
            'school_city' => SettingsHelper::get('school_city', 'Yamoussoukro'),
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
        $storagePath = storage_path('app/public/' . $logoPath);
        if (file_exists($storagePath)) {
            $logoType = pathinfo($storagePath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($storagePath);
            return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }

        // Priorité 2: Vérifier dans public/ (compatibilité)
        $publicPath = public_path($logoPath);
        if (file_exists($publicPath)) {
            $logoType = pathinfo($publicPath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($publicPath);
            return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }

        return null;
    }

    /**
     * Prévisualise l'emploi du temps avant génération PDF
     */
    public function previewEmploiTemps($id)
    {
        try {
            $emploiTemps = ESBTPEmploiTemps::with([
                'seances.matiere',
                'classe',
                'classe.filiere',
                'classe.niveau',
                'annee'
            ])->findOrFail($id);

            // Récupérer la configuration PDF
            $config = $this->getPDFConfig();
            $settings = $config; // Utiliser la même structure que les bulletins
            
            // Préparer le logo en base64
            $logoBase64 = $this->prepareLogoBase64($config['school_logo']);

            // Grouper les séances par jour
            $seancesParJour = $emploiTemps->getSeancesParJour();

            // Récupérer les heures de début et de fin pour l'affichage
            $heuresDebut = [];
            $heuresFin = [];
            for ($heure = 8; $heure < 18; $heure++) {
                $heuresDebut[] = sprintf('%02d:00', $heure);
                $heuresFin[] = sprintf('%02d:00', $heure + 1);
            }

            // Noms des jours pour l'affichage
            $joursNoms = [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi',
            ];

            // Créer les variables $timeSlots et $days pour la vue
            $timeSlots = $heuresDebut;
            $days = array_keys($joursNoms);

            // Calcul des statistiques par matière
            $matiereStats = [];
            foreach ($emploiTemps->seances as $seance) {
                $matiereName = $seance->matiere ? $seance->matiere->name : 'Non définie';
                if (!isset($matiereStats[$matiereName])) {
                    $matiereStats[$matiereName] = 0;
                }
                $matiereStats[$matiereName]++;
            }
            $matiereStats = collect($matiereStats)->sortDesc();

            $totalSeances = $emploiTemps->seances->count();
            $totalMinutes = $emploiTemps->seances->reduce(function ($carry, $seance) {
                $start = $seance->heure_debut instanceof Carbon
                    ? $seance->heure_debut->copy()
                    : ($seance->heure_debut ? Carbon::parse($seance->heure_debut) : null);
                $end = $seance->heure_fin instanceof Carbon
                    ? $seance->heure_fin->copy()
                    : ($seance->heure_fin ? Carbon::parse($seance->heure_fin) : null);

                if ($start && $end) {
                    if ($end->lessThanOrEqualTo($start)) {
                        $end = $end->addDay();
                    }
                    return $carry + $start->diffInMinutes($end);
                }

                return $carry;
            }, 0);
            $totalHours = $totalMinutes > 0 ? $totalMinutes / 60 : 0;
            $totalHoursFormatted = $totalHours > 0
                ? rtrim(rtrim(number_format($totalHours, 1, ',', ' '), '0'), ',') . ' h'
                : '0 h';

            $uniqueMatieres = $matiereStats->count();
            $uniqueTeachers = $emploiTemps->seances
                ->map(function ($seance) {
                    if ($seance->teacher_id) {
                        return 'teacher_' . $seance->teacher_id;
                    }
                    if (!empty($seance->enseignant)) {
                        return 'name_' . $seance->enseignant;
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->count();

            $daysCovered = $emploiTemps->seances->pluck('jour')->filter()->unique()->count();

            $sessionTypeLabels = [
                ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
            ];

            $sessionTypeColors = [
                ESBTPSeanceCours::TYPE_COURSE => ['bg' => '#0453cb', 'text' => '#ffffff'],
                ESBTPSeanceCours::TYPE_HOMEWORK => ['bg' => '#3ba54f', 'text' => '#ffffff'],
                ESBTPSeanceCours::TYPE_BREAK => ['bg' => '#f59e0b', 'text' => '#1f2937'],
                ESBTPSeanceCours::TYPE_LUNCH => ['bg' => '#0ea5e9', 'text' => '#ffffff'],
                'default' => ['bg' => '#5e91de', 'text' => '#ffffff'],
            ];

            $sessionTypeSwatches = [];
            foreach ($emploiTemps->seances as $seance) {
                $type = $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
                if (!isset($sessionTypeSwatches[$type])) {
                    $background = $seance->color ?: ($sessionTypeColors[$type]['bg'] ?? $sessionTypeColors['default']['bg']);
                    $sessionTypeSwatches[$type] = [
                        'bg' => $background,
                        'text' => $this->calculateTextColor($background, $sessionTypeColors[$type]['text'] ?? '#ffffff'),
                    ];
                }
            }
            foreach ($sessionTypeLabels as $type => $label) {
                if (!isset($sessionTypeSwatches[$type])) {
                    $sessionTypeSwatches[$type] = $sessionTypeColors[$type] ?? $sessionTypeColors['default'];
                }
            }

            $sessionTypeStats = $emploiTemps->seances
                ->groupBy(function ($seance) {
                    return $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
                })
                ->map
                ->count()
                ->toArray();

            $summaryStats = [
                [
                    'label' => 'Séances programmées',
                    'value' => $totalSeances,
                    'icon' => 'fa-calendar-check',
                    'description' => 'Total des séances de la semaine',
                ],
                [
                    'label' => 'Volume horaire',
                    'value' => $totalHoursFormatted,
                    'icon' => 'fa-hourglass-half',
                    'description' => 'Durée cumulée des séances',
                ],
                [
                    'label' => 'Matières couvertes',
                    'value' => $uniqueMatieres,
                    'icon' => 'fa-book-open',
                    'description' => 'Nombre de matières différentes',
                ],
                [
                    'label' => 'Intervenants mobilisés',
                    'value' => $uniqueTeachers,
                    'icon' => 'fa-user-tie',
                    'description' => 'Nombre d’enseignants uniques',
                ],
            ];

            $periodeAffichage = null;
            if ($emploiTemps->date_debut && $emploiTemps->date_fin) {
                $periodeAffichage = Carbon::parse($emploiTemps->date_debut)->locale('fr')->isoFormat('LL')
                    . ' → '
                    . Carbon::parse($emploiTemps->date_fin)->locale('fr')->isoFormat('LL');
            } elseif ($emploiTemps->annee && $emploiTemps->annee->name) {
                $periodeAffichage = $emploiTemps->annee->name;
            } elseif (!empty($emploiTemps->semestre)) {
                $periodeAffichage = 'Semestre ' . $emploiTemps->semestre;
            }

            $etablissementInfo = [
                'nom' => $config['school_name'],
                'adresse' => $config['school_address'],
                'telephone' => $config['school_phone'],
                'email' => $config['school_email'],
                'ville' => $config['school_city'],
                'pays' => $config['school_country'],
                'type' => $config['school_type'],
            ];

            return view('esbtp.emploi-temps.preview-pdf', [
                'emploiTemps' => $emploiTemps,
                'seances' => $emploiTemps->seances,
                'seancesParJour' => $seancesParJour,
                'heuresDebut' => $heuresDebut,
                'heuresFin' => $heuresFin,
                'joursNoms' => $joursNoms,
                'timeSlots' => $timeSlots,
                'days' => $days,
                'matiereStats' => $matiereStats,
                'logoBase64' => $logoBase64,
                'settings' => $settings,
                'summaryStats' => $summaryStats,
                'sessionTypeStats' => $sessionTypeStats,
                'sessionTypeLabels' => $sessionTypeLabels,
                'sessionTypeColors' => $sessionTypeColors,
                'sessionTypeSwatches' => $sessionTypeSwatches,
                'totalHoursFormatted' => $totalHoursFormatted,
                'totalSeances' => $totalSeances,
                'daysCovered' => $daysCovered,
                'periodeAffichage' => $periodeAffichage,
                'etablissement' => $etablissementInfo,
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la prévisualisation de l\'emploi du temps', [
                'error' => $e->getMessage(),
                'emploi_temps_id' => $id
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
            if (!$dayName) continue;
            
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
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
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

        if (!$anneeUniversitaire) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année universitaire active trouvée'
            ], 404);
        }

        // Query de base
        $query = ESBTPEmploiTemps::with([
            'classe.filiere',
            'classe.niveau',
            'annee'
        ])->where('annee_universitaire_id', $anneeUniversitaire->id);

        // Filtrage par filière
        if ($request->filled('filiere_id')) {
            $query->whereHas('classe', function($q) use ($request) {
                $q->where('filiere_id', $request->filiere_id);
            });
        }

        // Filtrage par niveau
        if ($request->filled('niveau_id')) {
            $query->whereHas('classe', function($q) use ($request) {
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
            'html_table' => view('esbtp.emploi-temps.partials.table-rows', compact('emploisTemps', 'timetableShortcut'))->render(),
            'count' => $emploisTemps->count()
        ]);
    }

    public function quickGenerate(Request $request, TimetableShortcutService $shortcutService)
    {
        $user = auth()->user();

        if (!$user || (! $user->hasRole('superAdmin') && ! $user->hasRole('secretaire') && ! $user->can('create_timetable'))) {
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
        if (!$anneeEnCours) {
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

        DB::beginTransaction();
        try {
            foreach ($selectedClasses as $classeId) {
                if (!isset($itemsByClass[$classeId])) {
                    $skippedCount++;
                    continue;
                }

                $item = $itemsByClass[$classeId];
                $classe = $item['class'];
                $targetStart = $item['target_start'];
                $targetEnd = $item['target_end'];
                $source = $item['source'];

                $mode = $validated['modes'][$classeId] ?? ($source ? 'duplicate' : 'empty');
                if ($mode === 'duplicate' && !$source) {
                    $mode = 'empty';
                    $fallbackCount++;
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
                    'titre' => $source?->titre ?? 'Emploi du temps - ' . $classe->name . ' (' . $periodeLabel . ')',
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
                    foreach ($source->seances as $seance) {
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

        return redirect()->back()->with('success', $message);
    }

}
