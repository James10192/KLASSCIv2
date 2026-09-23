<?php

namespace App\Http\Controllers;

use App\Domain\EmploiTemps\AlignementDuDevoir;
use App\Domain\EmploiTemps\ConflitsDUnCreneau;
use App\Domain\EmploiTemps\DetectionDesConflits;
use App\Domain\EmploiTemps\JourDeLaSemaine;
use App\Enums\TypeSeance;
use App\Services\LMD\Tpe\TpePlanification;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ESBTPSeanceCoursController extends Controller
{
    /**
     * Affiche la liste des séances de cours.
     */
    public function index(Request $request)
    {
        try {
            $seancesCours = $this->listeFiltree($request)
                ->orderBy('jour')
                ->orderBy('heure_debut')
                ->paginate(25);

            // Récupérer tous les emplois du temps pour le filtre
            $emploisTemps = ESBTPEmploiTemps::with('classe')->orderBy('created_at', 'desc')->get();

            $enseignants = $this->enseignantsDuFiltre();

            // Statistiques par type de séance (single aggregated query, keys UPPERCASE per TypeSeance enum)
            $rawCounts = ESBTPSeanceCours::query()
                ->select('type_seance', DB::raw('COUNT(*) AS total'))
                ->groupBy('type_seance')
                ->pluck('total', 'type_seance')
                ->toArray();

            $statsCours = [];
            foreach (TypeSeance::cases() as $case) {
                $statsCours[$case->value] = (int) ($rawCounts[$case->value] ?? 0);
            }

            $statsJours = $this->comptesParJour();

            // Détecter les conflits potentiels
            $conflits = $this->detecterConflitsHoraire();

            return view('esbtp.seances-cours.index', compact(
                'seancesCours',
                'emploisTemps',
                'enseignants',
                'statsCours',
                'statsJours',
                'conflits'
            ));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage des séances de cours: '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());

            return back()->with('error', 'Une erreur est survenue lors du chargement des séances de cours: '.$e->getMessage());
        }
    }

    /**
     * La liste des séances, filtrée comme le demande la requête.
     *
     * Le filtre enseignant porte sur `teacher_id`, la colonne vivante. Il
     * portait sur la colonne texte `enseignant`, que rien n'écrit — absente de
     * `$fillable`, renseignée par aucun code du dépôt : il rendait donc
     * systématiquement zéro séance, et la liste paraissait vide dès qu'on
     * choisissait un enseignant.
     *
     * Le filtre jour interroge les DEUX écritures de la colonne. Le formulaire
     * envoie un entier, mais les séances saisies depuis l'emploi du temps
     * portent un libellé (« Lundi ») : un `where` sur l'entier les rate toutes.
     */
    private function listeFiltree(Request $request): Builder
    {
        $query = ESBTPSeanceCours::with(['emploiTemps.classe', 'matiere', 'teacher.user']);

        if ($emploiTempsId = $request->input('emploi_temps_id')) {
            $query->where('emploi_temps_id', $emploiTempsId);
        }

        if ($jourSemaine = $request->input('jour_semaine')) {
            $query->whereIn('jour', JourDeLaSemaine::ecrituresDe($jourSemaine));
        }

        if ($typeSeance = $request->input('type_seance')) {
            $query->where('type_seance', $typeSeance);
        }

        if ($enseignantId = $request->input('enseignant')) {
            $query->where('teacher_id', $enseignantId);
        }

        return $query;
    }

    /**
     * Le nombre de séances par jour, les deux écritures réunies.
     *
     * `groupBy('jour')` rend des clés BRUTES : une séance écrite « Lundi » sort
     * sous la clé `'Lundi'`, une autre du même lundi sous la clé `1`. Le panneau
     * « Répartition par jour » lisant six clés entières, toutes les séances
     * saisies depuis l'emploi du temps y comptaient pour zéro.
     *
     * @return array<int, int> indexé par l'écriture entière du jour, 1 à 6
     */
    private function comptesParJour(): array
    {
        $bruts = ESBTPSeanceCours::select('jour', DB::raw('count(*) as total'))
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $comptes = array_fill_keys(array_keys(JourDeLaSemaine::libelles()), 0);
        $illisibles = 0;

        foreach ($bruts as $ecriture => $total) {
            $numero = JourDeLaSemaine::numero($ecriture);

            if ($numero === null) {
                $illisibles += (int) $total;

                continue;
            }

            $comptes[$numero] += (int) $total;
        }

        if ($illisibles > 0) {
            // Ces séances ne sont comptées nulle part, et c'est le seul endroit
            // du dépôt qui peut s'en apercevoir. Les taire ferait un total qui
            // ne tombe jamais juste, sans qu'on sache pourquoi.
            Log::warning('Séances dont le jour est illisible, absentes de la répartition', [
                'nombre' => $illisibles,
            ]);
        }

        return $comptes;
    }

    /**
     * Les enseignants proposés au filtre.
     *
     * Des `ESBTPTeacher`, parce que c'est ce que `esbtp_seance_cours.teacher_id`
     * désigne. La liste portait des `User`, dont les identifiants ne
     * correspondent pas.
     *
     * Tous, y compris les désactivés — la liste portait un `is_active`, retiré
     * ici à dessein : elle filtre des séances DÉJÀ enregistrées, et masquer un
     * enseignant parti rendrait ses séances passées introuvables.
     *
     * Le tri se fait en mémoire : `name` est un accesseur qui lit le compte
     * lié, donc il n'existe pas en base.
     *
     * @return \Illuminate\Support\Collection<int, ESBTPTeacher>
     */
    private function enseignantsDuFiltre()
    {
        return ESBTPTeacher::with('user')->get()->sortBy('name')->values();
    }

    /**
     * Les conflits d'horaire du bandeau, délégués à `DetectionDesConflits`.
     *
     * Le raisonnement a quitté ce contrôleur : privé dans un fichier de plus de
     * mille lignes, il n'était prouvé que par ses commentaires. Il est désormais
     * rejouable sans base — voir `tests/Unit/Domain/EmploiTemps`.
     *
     * Ce qui reste ici est ce qui appartient au contrôleur : la requête, et les
     * relations à charger pour que la détection n'en déclenche aucune.
     */
    private function detecterConflitsHoraire(): array
    {
        $seances = ESBTPSeanceCours::with(['emploiTemps.classe', 'matiere', 'teacher.user'])
            ->where('is_active', true)
            ->get();

        return (new DetectionDesConflits)->depuis($seances);
    }

    /**
     * Affiche les détails d'une séance de cours.
     */
    public function show(ESBTPSeanceCours $seancesCour)
    {
        try {
            // Load relationships
            $seancesCour->load(['emploiTemps.classe', 'matiere', 'teacher.user', 'sessionReport']);

            return view('esbtp.seances-cours.show', compact('seancesCour'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la séance de cours: '.$e->getMessage());

            return back()->with('error', 'Une erreur est survenue lors du chargement de la séance de cours.');
        }
    }

    /**
     * Affiche le formulaire de création d'une nouvelle séance de cours.
     */
    public function create(Request $request)
    {
        // PR17.2 : si pas d'emploi_temps_id, afficher page picker EDT premium
        if (! $request->filled('emploi_temps_id')) {
            return $this->showEmploiTempsPicker($request);
        }

        try {
            // Validate required parameters - jour et heure_debut sont optionnels
            $request->validate([
                'emploi_temps_id' => 'required|exists:esbtp_emploi_temps,id',
                'jour' => 'nullable|integer|min:1|max:6',
                'heure_debut' => 'nullable|date_format:H:i',
            ]);

            // Récupérer l'emploi du temps
            $emploiTemps = ESBTPEmploiTemps::with('classe.filiere', 'classe.niveau', 'annee')
                ->findOrFail($request->emploi_temps_id);

            // Utiliser la même logique que dans l'emploi du temps pour récupérer les données de planification
            $emploiTempsController = new ESBTPEmploiTempsController;
            $reflection = new \ReflectionClass($emploiTempsController);
            $method = $reflection->getMethod('getPlanificationDataForClasse');
            $method->setAccessible(true);

            $planificationData = $method->invoke($emploiTempsController,
                $emploiTemps->classe,
                $emploiTemps->annee,
                $emploiTemps->semestre
            );

            // OVERRIDE LMD : pour classe LMD avec parcours, le pivot esbtp_matiere_filiere
            // utilise par getPlanificationDataForClasse() est VIDE donc 0 matières.
            // On override via le scope strict parcours.unitesEnseignement (Service SSOT).
            // PR1 chantier emploi-temps-lmd-unification : bascule vers buildForPlanning()
            // (sans volumeBudget car create form n'affiche pas les heures realisees).
            if (($emploiTemps->classe->systeme_academique ?? '') === 'LMD') {
                $planificationData = app(\App\Services\LMD\MatiereTreeBuilder::class)
                    ->buildForPlanning($planificationData, $emploiTemps->classe);
            }

            // Récupérer les matières configurées pour cette combinaison filière/niveau
            $matieres = $planificationData['matieres_planifiees'];

            // Récupérer les enseignants avec leurs disponibilités
            $teachers = collect();
            $availabilityData = [];

            foreach ($matieres as $matiere) {
                $enseignantsPourMatiere = $matiere['enseignants_selectables'] ?? collect();

                foreach ($enseignantsPourMatiere as $teacher) {
                    if ($teacher && ! $teachers->contains('id', $teacher->id)) {
                        $teachers->push($teacher->loadMissing(['user', 'availabilities']));
                    }
                }
            }

            $planning = app(\App\Services\TeacherPlanningService::class);
            foreach ($teachers as $teacher) {
                $baseAvailability = $planning->getAvailabilityMatrix($teacher)['availability'];

                // Ajouter les séances existantes comme créneaux occupés, sur la
                // fenêtre de CET emploi du temps et non sur celle d'aujourd'hui.
                $availabilityData[$teacher->id] = $this->addExistingSessionsToAvailability($baseAvailability, $teacher, null, $emploiTemps);
            }

            // Définir les types de séances disponibles
            $sessionTypes = [
                ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
            ];

            // Définir les jours de la semaine
            $joursSemaine = [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi',
            ];

            // Get default colors
            $defaultColors = ESBTPSeanceCours::DEFAULT_COLORS;

            $titres_academiques = [
                'M.' => 'Monsieur',
                'Mme' => 'Madame',
                'Mlle' => 'Mademoiselle',
                'Dr.' => 'Docteur',
                'Pr.' => 'Professeur'
            ];

            $grades_academiques = [
                'assistant' => 'Assistant',
                'maitre_assistant' => 'Maître Assistant',
                'maitre_conferences' => 'Maître de Conférences',
                'professeur' => 'Professeur'
            ];

            return view('esbtp.seances-cours.create', compact(
                'emploiTemps',
                'teachers',
                'matieres',
                'sessionTypes',
                'joursSemaine',
                'defaultColors',
                'request',
                'planificationData',
                'availabilityData',
                'titres_academiques',
                'grades_academiques'
            ));
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@create: '.$e->getMessage());

            return back()->with('error', 'Une erreur est survenue lors du chargement du formulaire.');
        }
    }

    /**
     * Ajouter les séances existantes du professeur comme créneaux occupés
     */
    private function addExistingSessionsToAvailability($baseAvailability, $teacher, $ignoreSessionId = null, ?ESBTPEmploiTemps $emploiTempsEdite = null)
    {
        [$debutJour, $finJour] = $this->fenetreDeLaGrille($emploiTempsEdite);

        // Résolu une fois, pas à chaque créneau de chaque séance.
        $debutJournee = app(\App\Services\Planning\PlageHoraireJournee::class)->debut();

        $existingSessions = $this->seancesOccupantLaFenetre($teacher, $debutJour, $finJour);

        // Les clés de la grille de disponibilité, dans l'ordre de `JourDeLaSemaine`
        // (rang 0 = lundi). La table de traduction français → anglais qui vivait
        // ici a été retirée : c'était une troisième source de vérité sur la
        // lecture du jour, dans le fichier même où la recopie de la deuxième
        // avait déjà fait compter « Répartition par jour » pour zéro.
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        foreach ($existingSessions as $session) {
            if ($ignoreSessionId && (int) $session->id === (int) $ignoreSessionId) {
                // Ne pas marquer la séance en cours de modification comme occupée
                continue;
            }

            // Les deux écritures de la colonne, par la lecture unique du domaine.
            $rang = JourDeLaSemaine::rang($session->jour);
            $dayKey = $rang === null ? null : ($days[$rang] ?? null);

            if (! $dayKey || ! isset($baseAvailability[$dayKey])) {
                continue;
            }

            // L'accesseur du modèle rend TOUJOURS un Carbon : le repli sur
            // `substr(..., 0, 2)` était mort, et il était faux — il aurait lu
            // « 20 » dans « 2026-09-15 08:00:00 », soit 20 h au lieu de 8 h.
            // Le garder aurait tendu le piège au prochain lecteur.
            $startHour = (int) $session->heure_debut->format('H');
            $endHour = (int) $session->heure_fin->format('H');

            // Marquer comme occupé tous les créneaux de cette séance
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $hourIndex = $hour - $debutJournee;
                if ($hourIndex >= 0 && $hourIndex < count($baseAvailability[$dayKey])) {
                    $baseAvailability[$dayKey][$hourIndex] = 'occupied';
                }
            }
        }

        return $baseAvailability;
    }

    /**
     * La fenêtre de dates sur laquelle la grille de disponibilité fait foi.
     *
     * La grille montrait les séances des emplois du temps en vigueur
     * AUJOURD'HUI. En préparant le planning du semestre suivant, elle déclarait
     * donc l'enseignant libre sur des créneaux qu'il a déjà — et
     * l'enregistrement, lui, refuse maintenant le conflit. La grille et le garde
     * auraient dit deux choses contraires.
     *
     * La bonne fenêtre n'est pas une date mais un CHEVAUCHEMENT : cette grille
     * est hebdomadaire et vaut pour toute la période de l'emploi du temps qu'on
     * édite. Une séance compte donc si la période de SON emploi du temps recoupe
     * celle-ci. Une fin non renseignée vaut « sans terme » des deux côtés.
     *
     * Sans emploi du temps de référence, on retombe sur « en vigueur
     * aujourd'hui », le comportement d'avant.
     *
     * Aucun appelant n'y tombe aujourd'hui, et ce sont les APPELANTS qui le
     * disent, pas le schéma : `create()` obtient son emploi du temps par
     * `findOrFail()`, et `edit()` sort en `back()` quand la relation est nulle.
     * Deux affirmations plus fortes ont figuré ici et étaient fausses —
     * « atteignable par la suppression douce » (la relation porte
     * `->withTrashed()`), puis « `emploi_temps_id` est NOT NULL depuis sa
     * migration » (une seconde migration le recrée `nullable()` si la colonne
     * manque, donc la nullité dépend de l'instance).
     *
     * Le repli reste écrit parce qu'un paramètre nullable finit par recevoir
     * `null`, et parce qu'il coûte une ligne. D'où la fenêtre `[aujourd'hui,
     * aujourd'hui]` et non `[aujourd'hui, ∞)` : laisser la borne droite ouverte
     * élargirait le repli aux emplois du temps À VENIR, que l'ancien
     * `date_debut <= today` excluait. Un repli rend le comportement d'avant, pas
     * un comportement voisin.
     *
     * @return array{0: string, 1: ?string} [début, fin] — fin `null` = sans terme
     */
    private function fenetreDeLaGrille(?ESBTPEmploiTemps $emploiTempsEdite): array
    {
        $debut = $emploiTempsEdite?->date_debut;
        $fin = $emploiTempsEdite?->date_fin;

        return [
            $debut ? Carbon::parse($debut)->toDateString() : now()->toDateString(),
            $fin
                ? Carbon::parse($fin)->toDateString()
                : ($emploiTempsEdite ? null : now()->toDateString()),
        ];
    }

    /**
     * Les séances de cet enseignant dont l'emploi du temps recoupe la fenêtre.
     */
    private function seancesOccupantLaFenetre($teacher, string $debutJour, ?string $finJour)
    {
        return ESBTPSeanceCours::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereHas('emploiTemps', function ($query) use ($debutJour, $finJour) {
                $query->where('is_active', true)
                    // L'autre finit après le début de la nôtre.
                    ->where(function ($sub) use ($debutJour) {
                        $sub->whereNull('date_fin')
                            ->orWhereDate('date_fin', '>=', $debutJour);
                    });

                // L'autre commence avant la fin de la nôtre. Une fin non
                // renseignée de notre côté vaut « sans terme » : aucune borne
                // droite à poser, tout début convient.
                if ($finJour !== null) {
                    $query->whereDate('date_debut', '<=', $finJour);
                }
            })
            ->get();
    }

    /**
     * Enregistre une nouvelle séance de cours.
     */
    public function store(Request $request)
    {
        try {
            $expectsJson = $request->expectsJson() || $request->boolean('embed');

            // Get the emploi du temps and its associated classe_id and annee_universitaire_id
            $emploiTemps = ESBTPEmploiTemps::findOrFail($request->emploi_temps_id);
            if (! $emploiTemps->classe_id) {
                throw new \Exception('Aucune classe n\'est associée à cet emploi du temps.');
            }
            if (! $emploiTemps->annee_universitaire_id) {
                throw new \Exception('Aucune année universitaire n\'est associée à cet emploi du temps.');
            }

            // Log des données reçues
            \Log::info('Création séance - Données reçues', ['champs' => array_keys($request->all())]);

            // ════════════════════════════════════════════════════════════════
            // LMD-aware : derive le `type` (creneau emploi-temps) depuis le
            // `type_seance` UEMOA si la classe est LMD. La directrice LMD ne
            // choisit qu'un seul selecteur (type_seance), le `type` est calcule.
            // TPE : interdit tant que tpe.mode = non_planifiable (défaut CI).
            // ════════════════════════════════════════════════════════════════
            $isLmdClasse = ($emploiTemps->classe->systeme_academique ?? '') === 'LMD';
            if ($isLmdClasse && $request->filled('type_seance')) {
                $typeSeanceEnum = \App\Enums\TypeSeance::tryFrom($request->input('type_seance'));
                if ($typeSeanceEnum) {
                    if ($typeSeanceEnum === \App\Enums\TypeSeance::TPE && ! TpePlanification::isPlanifiable()) {
                        throw ValidationException::withMessages([
                            'type_seance' => 'Le TPE n\'est pas planifiable en emploi du temps. C\'est une métadonnée de l\'ECUE configurée dans /esbtp/lmd/planning.',
                        ]);
                    }
                    // Derive le top-type (creneau) depuis la nature du sous-type :
                    // evaluation (Examen/Partiel/Rattrapage/Soutenance) -> homework, enseignement -> course.
                    // NB: on n'utilise PAS mapToType() ici car son contrat mappe les evaluations a null
                    //     (cf rule type-seance-enum-extension.md). La nature est lue via isEvaluation().
                    $request->merge(['type' => $typeSeanceEnum->isEvaluation() ? 'homework' : 'course']);
                }
            }

            // Validate the basic required fields first
            $baseValidator = Validator::make($request->all(), [
                'emploi_temps_id' => 'required|exists:esbtp_emploi_temps,id',
                'type' => 'required|in:course,homework,break,lunch',
                'type_seance' => ['nullable', \Illuminate\Validation\Rule::enum(\App\Enums\TypeSeance::class)],
                'jour' => 'required|integer|min:1|max:6',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            ]);

            // Champs additionnels selon le type
            $additionalRules = [];
            $additionalMessages = [];
            $optionalRules = [];

            if ($request->type === 'course' || $request->type === 'homework') {
                $additionalRules['matiere_id'] = 'required|exists:esbtp_matieres,id';
                $optionalRules['teacher_id'] = 'nullable|exists:esbtp_teachers,id';
                $optionalRules['salle'] = 'nullable|string|max:50';
            }
            if ($request->type === 'homework') {
                $optionalRules['homework_description'] = 'nullable|string|max:255';
                $optionalRules['homework_due_date'] = 'nullable|date';
            }
            // Pour break/lunch, on force matiere_id, salle, teacher_id à null
            if ($request->type === 'break' || $request->type === 'lunch') {
                $request->merge([
                    'matiere_id' => null,
                    'teacher_id' => null,
                    'salle' => null,
                    'homework_description' => null,
                    'homework_due_date' => null,
                ]);
            }

            $allRules = array_merge($baseValidator->getRules(), $additionalRules, $optionalRules);
            $allMessages = array_merge($baseValidator->getMessageBag()->getMessages(), $additionalMessages);

            // Validate all rules
            $validator = Validator::make($request->all(), $allRules, $allMessages);

            if ($validator->fails()) {
                \Log::warning('Erreur validation création séance', $validator->errors()->toArray());

                if ($expectsJson) {
                    return response()->json([
                        'message' => 'Validation échouée.',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Start transaction
            DB::beginTransaction();
            $createdSessions = [];
            $createdSeanceId = null;
            $creationSucceeded = false;

            try {
                // Les jours réellement écrits — établis UNE fois, et servant à la
                // fois au garde et à la boucle de création plus bas.
                //
                // Le garde ne portait que sur `$request->jour`. Or en récurrence
                // ce jour-là n'est PAS écrit : seuls les jours cochés le sont.
                // La vérification tombait donc sur un jour où rien n'est créé, et
                // les jours créés n'étaient jamais vérifiés — un « lundi, mercredi,
                // vendredi » posé sur trois créneaux occupés passait entier.
                $joursAEcrire = $this->joursAEcrire($request);

                $conflicts = [];
                foreach ($joursAEcrire as $jourAEcrire) {
                    foreach ($this->checkSchedulingConflicts($request, $jourAEcrire) as $message) {
                        // Le libellé d'un conflit ne nomme pas son jour ; sans
                        // ce dédoublonnage, une récurrence sur trois jours tous
                        // occupés par la même classe répéterait trois fois la
                        // même phrase.
                        $conflicts[$message] = $message;
                    }
                }
                $conflicts = array_values($conflicts);

                if (! empty($conflicts)) {
                    \Log::warning('Conflit horaire lors de la création de séance', $conflicts);
                    throw ValidationException::withMessages([
                        'conflicts' => $conflicts,
                    ]);
                }

                // Prepare data for creation
                $data = $validator->validated();
                $data['classe_id'] = $emploiTemps->classe_id;
                $data['annee_universitaire_id'] = $emploiTemps->annee_universitaire_id;

                // Couleur dynamique selon le type
                if (empty($data['color'])) {
                    $data['color'] = \App\Models\ESBTPSeanceCours::DEFAULT_COLORS[$data['type']] ?? '#000000';
                }

                if ($data['type'] === ESBTPSeanceCours::TYPE_HOMEWORK) {
                    $data['teacher_id'] = null;
                }

                // Log des données à enregistrer
                \Log::info('Création séance - Données enregistrées', $data);

                // Une seule boucle pour les deux cas : `joursAEcrire()` rend le
                // jour du formulaire quand il n'y a pas de récurrence. C'est la
                // MÊME liste que celle passée au garde plus haut, et c'est ce qui
                // empêche que l'un vérifie un jour que l'autre n'écrit pas.
                foreach ($joursAEcrire as $jourAEcrire) {
                    $dataForDay = $data;
                    $dataForDay['jour'] = $jourAEcrire;
                    // La MÊME formule que le garde. Elle portait le raccourci
                    // `date_debut + (jour - 1)` : sur un emploi du temps ouvert un
                    // mercredi, une récurrence du vendredi recevait une date
                    // tombant un dimanche.
                    $dataForDay['date_seance'] = $emploiTemps->dateDuJour($jourAEcrire);
                    $session = ESBTPSeanceCours::create($dataForDay);
                    $createdSessions[] = $session->id;
                    \Log::info('Séance créée', ['id' => $session->id, 'jour' => $jourAEcrire]);
                }

                // Création automatique d'évaluations :
                // - LMD : si le type_seance est une évaluation (Examen/Partiel/Rattrapage/Soutenance).
                //         CM/TD/TP/PROJET/AUTRE ne génèrent PAS d'évaluation.
                // - BTS legacy : si type === homework (comportement preserve, zero régression)
                $typeSeanceEnumForEval = \App\Enums\TypeSeance::tryFrom((string) $request->input('type_seance'));
                $shouldGenerateEvaluation = $isLmdClasse
                    ? ($typeSeanceEnumForEval?->isEvaluation() ?? false)
                    : ($request->type === 'homework');

                if ($shouldGenerateEvaluation) {
                    $createdEvaluations = [];
                    foreach ($createdSessions as $sessionId) {
                        $seance = ESBTPSeanceCours::with('matiere')->find($sessionId);

                        // Calculer la durée en minutes
                        $heureDebut = Carbon::parse($seance->heure_debut);
                        $heureFin = Carbon::parse($seance->heure_fin);
                        $dureeMinutes = $heureFin->diffInMinutes($heureDebut);

                        // Déterminer la période selon la date
                        $periode = 'semestre1'; // Par défaut
                        $dateSeance = Carbon::parse($seance->date_seance);
                        if ($dateSeance->month >= 1 && $dateSeance->month <= 6) {
                            $periode = 'semestre2';
                        }

                        $evaluationStartAt = AlignementDuDevoir::combiner($seance->date_seance, $seance->heure_debut);
                        $evaluationEndAt = AlignementDuDevoir::combiner($seance->date_seance, $seance->heure_fin);
                        if ($evaluationEndAt->lessThanOrEqualTo($evaluationStartAt)) {
                            $evaluationEndAt = $evaluationEndAt->addDay();
                        }
                        $dureeMinutes = $evaluationEndAt->diffInMinutes($evaluationStartAt);

                        $evaluationData = [
                            'titre' => $seance->homework_description ?: 'Devoir - '.($seance->matiere->name ?? 'Matière'),
                            'description' => $seance->homework_description,
                            'matiere_id' => $seance->matiere_id,
                            'classe_id' => $seance->classe_id,
                            'type' => 'devoir',
                            'date_evaluation' => $evaluationStartAt,
                            'coefficient' => 1.0,
                            'bareme' => 20.00,
                            'duree_minutes' => $dureeMinutes,
                            'periode' => $periode,
                            'annee_universitaire_id' => $seance->annee_universitaire_id,
                            'status' => 'draft',
                            'is_published' => false,
                            'notes_published' => false,
                            'created_by' => Auth::id(),
                            'enseignant_id' => $seance->type === ESBTPSeanceCours::TYPE_HOMEWORK ? null : $seance->teacher_id,
                        ];

                        $evaluation = ESBTPEvaluation::create($evaluationData);
                        $createdEvaluations[] = $evaluation->id;

                        if ($seance) {
                            $seance->homework_evaluation_id = $evaluation->id;
                            $seance->save();
                        }

                        \Log::info('Évaluation créée automatiquement', [
                            'evaluation_id' => $evaluation->id,
                            'seance_id' => $sessionId,
                            'date_evaluation' => $evaluationStartAt->toDateTimeString(),
                            'titre' => $evaluation->titre,
                        ]);
                    }

                    \Log::info('Évaluations automatiques créées pour séances homework', [
                        'evaluation_ids' => $createdEvaluations,
                        'session_ids' => $createdSessions,
                    ]);
                }

                DB::commit();
                \Log::info('Création séances terminée', ['ids' => $createdSessions]);
                $createdSeanceId = ! empty($createdSessions) ? end($createdSessions) : null;
                $creationSucceeded = true;

                $successMessage = 'Séance(s) ajoutée(s) avec succès.';
                if ($request->type === 'homework') {
                    $successMessage .= ' Les évaluations correspondantes ont été créées automatiquement.';
                }

                if ($expectsJson) {
                    return response()->json([
                        'success' => true,
                        'emploi_temps_id' => $request->emploi_temps_id,
                        'seance_id' => $createdSeanceId,
                        'message' => $successMessage,
                    ]);
                }

                return redirect()
                    ->route('esbtp.emploi-temps.show', $request->emploi_temps_id)
                    ->with('success', $successMessage);
            } catch (ValidationException $e) {
                DB::rollBack();
                \Log::error('Erreur validation transaction création séance', $e->errors());

                if ($expectsJson) {
                    return response()->json([
                        'message' => 'Validation échouée.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                return redirect()->back()
                    ->withErrors($e->errors())
                    ->withInput();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Erreur exception création séance', ['message' => $e->getMessage(), 'trace' => config('app.debug') ? $e->getTraceAsString() : null]);
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Erreur globale création séance', ['message' => $e->getMessage(), 'trace' => config('app.debug') ? $e->getTraceAsString() : null]);

            if ($creationSucceeded) {
                if ($expectsJson) {
                    return response()->json([
                        'success' => true,
                        'emploi_temps_id' => $request->emploi_temps_id,
                        'seance_id' => $createdSeanceId,
                        'message' => 'Séance ajoutée avec succès.',
                    ]);
                }

                return redirect()
                    ->route('esbtp.emploi-temps.show', $request->emploi_temps_id)
                    ->with('success', 'Séance ajoutée avec succès.');
            }

            if ($expectsJson) {
                return response()->json([
                    'message' => 'Une erreur est survenue lors de la création de la séance.',
                ], 500);
            }

            return back()->with('error', 'Une erreur est survenue lors de la création de la séance : '.$e->getMessage());
        }
    }

    /**
     * Les jours que `store()` va réellement écrire.
     *
     * Sans récurrence : le jour du formulaire. Avec récurrence : les jours
     * cochés, et eux seuls — le jour du formulaire n'est PAS créé dans ce cas.
     *
     * @return array<int, mixed>
     */
    private function joursAEcrire(Request $request): array
    {
        if (! $request->has('is_recurring')) {
            return [$request->jour];
        }

        $jours = $request->input('recurrence_days', []);

        if (is_string($jours)) {
            $jours = explode(',', $jours);
        }

        if (! is_array($jours) || $jours === []) {
            // Case cochée sans aucun jour : on retombe sur le jour du
            // formulaire, ce que faisait déjà la boucle de création.
            return [$request->jour];
        }

        return array_values($jours);
    }

    /**
     * Les conflits du créneau demandé, pour une séance qui n'existe pas encore.
     *
     * La recherche elle-même vit dans `ConflitsDUnCreneau`, parce qu'elle sert
     * aussi à `update()` ici et à `storeSession()` de l'autre contrôleur — dont
     * les champs de formulaire ne portent pas les mêmes noms. Ce qui reste ici
     * est la traduction de CE formulaire vers ces valeurs.
     *
     * Le jour est passé à part : en récurrence, un même formulaire en écrit
     * plusieurs, et chacun doit être vérifié.
     *
     * @return string[]
     */
    private function checkSchedulingConflicts(Request $request, mixed $jour)
    {
        $emploiTemps = ESBTPEmploiTemps::findOrFail($request->emploi_temps_id);

        $mobiliseUneRessource = ESBTPSeanceCours::mobiliseUneRessource($request->type);

        return (new ConflitsDUnCreneau($emploiTemps))->pourUneNouvelleSeance(
            $jour,
            $request->heure_debut,
            $request->heure_fin,
            $mobiliseUneRessource && $request->teacher_id ? (int) $request->teacher_id : null,
            $mobiliseUneRessource ? $request->salle : null,
        );
    }

    /**
     * Les règles de validation de la modification d'une séance.
     *
     * Extraites du corps de `update()`, qui franchissait le seuil de l'axe 6 :
     * ce bloc en occupait la moitié, et il ne décrit rien de ce que la méthode
     * FAIT — il décrit la forme du formulaire.
     *
     * `$type` est le type STOCKÉ de la séance, pas celui que le formulaire
     * renvoie : `update()` le réimpose avant de valider, parce qu'une séance ne
     * change pas de nature en cours de route. La règle de compatibilité
     * ci-dessous s'y adosse donc sans avoir à refuser un changement de type.
     *
     * @return array<string, mixed>
     */
    private function reglesDeModification(?string $type): array
    {
        $regles = [
            'type' => 'required|in:'.implode(',', [
                ESBTPSeanceCours::TYPE_COURSE,
                ESBTPSeanceCours::TYPE_HOMEWORK,
                ESBTPSeanceCours::TYPE_BREAK,
                ESBTPSeanceCours::TYPE_LUNCH,
            ]),
            'type_seance' => [
                'nullable',
                Rule::enum(TypeSeance::class),
                function (string $attribute, mixed $value, \Closure $fail) use ($type) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    // Une récréation ou un déjeuner ne porte pas de type de séance :
                    // `update()` l'écarte ensuite du jeu validé.
                    if (in_array($type, [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH], true)) {
                        return;
                    }
                    $enum = $value instanceof TypeSeance
                        ? $value
                        : TypeSeance::tryFrom((string) $value);
                    if ($enum === TypeSeance::TPE) {
                        if (! TpePlanification::isPlanifiable() || $type !== ESBTPSeanceCours::TYPE_COURSE) {
                            $fail('Le TPE n\'est pas planifiable en emploi du temps.');
                        }

                        return;
                    }
                    if ($enum && ! $enum->isCompatibleWithTopType($type)) {
                        $fail($enum->isEvaluation()
                            ? 'Une séance de type Cours ne peut pas être un examen. Recréez-la en Devoir.'
                            : 'Une séance de type Devoir doit rester une évaluation (Examen, Partiel…).');
                    }
                },
            ],
            'jour' => 'required|integer|min:1|max:6',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'is_recurring' => 'boolean',
            'recurrence_days' => 'nullable|array',
            'recurrence_days.*' => 'integer|min:1|max:6',
            'priority' => 'integer',
        ];

        if ($type === ESBTPSeanceCours::TYPE_COURSE) {
            return array_merge($regles, [
                'teacher_id' => 'required|exists:esbtp_teachers,id',
                'matiere_id' => 'required|exists:esbtp_matieres,id',
                'salle' => 'required|string|max:50',
            ]);
        }

        if ($type === ESBTPSeanceCours::TYPE_HOMEWORK) {
            return array_merge($regles, [
                'teacher_id' => 'nullable|exists:esbtp_teachers,id',
                'matiere_id' => 'required|exists:esbtp_matieres,id',
                'salle' => 'nullable|string|max:50',
                'homework_description' => 'required|string',
                'homework_due_date' => 'required|date|after:today',
            ]);
        }

        return $regles;
    }

    /**
     * Les conflits du créneau demandé, pour une séance qui existe déjà.
     *
     * Le pendant de `checkSchedulingConflicts()` pour `update()`. Il n'existait
     * pas : créer une séance sur un créneau occupé était refusé, y DÉPLACER une
     * séance existante passait sans un mot.
     *
     * `pourUneSeanceModifiee()` et non `pourUneNouvelleSeance()` : sans quoi la
     * séance entrerait en conflit avec sa propre ligne.
     *
     * @param  array<string, mixed>  $validated
     * @return string[]
     */
    private function conflitsDeLaModification(
        ESBTPSeanceCours $seance,
        ESBTPEmploiTemps $emploiTemps,
        array $validated,
    ): array {
        $mobiliseUneRessource = ESBTPSeanceCours::mobiliseUneRessource($validated['type'] ?? null);

        return (new ConflitsDUnCreneau($emploiTemps))->pourUneSeanceModifiee(
            $seance,
            $validated['jour'],
            $validated['heure_debut'],
            $validated['heure_fin'],
            $mobiliseUneRessource && ! empty($validated['teacher_id']) ? (int) $validated['teacher_id'] : null,
            $mobiliseUneRessource ? ($validated['salle'] ?? null) : null,
        );
    }

    /**
     * Afficher le formulaire de modification d'une séance de cours.
     */
    public function edit(ESBTPSeanceCours $seancesCour)
    {
        try {
            // Check if the session exists
            if (! $seancesCour->exists) {
                Log::error('Session not found when trying to edit', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id(),
                ]);

                return back()->with('error', 'La séance de cours n\'existe pas.');
            }

            // Check authorization
            if (! Auth::user()->can('edit', $seancesCour)) {
                Log::warning('Unauthorized attempt to edit session', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id(),
                ]);

                return back()->with('error', 'Vous n\'êtes pas autorisé à modifier cette séance.');
            }

            // Load the emploi du temps with error handling
            $seancesCour->loadMissing([
                'matiere',
                'teacher.user',
                'teacher.availabilities',
                'emploiTemps.classe.filiere',
                'emploiTemps.classe.niveau',
                'emploiTemps.annee',
            ]);

            $emploiTemps = $seancesCour->emploiTemps;
            if (! $emploiTemps) {
                Log::error('Associated emploi du temps not found', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id(),
                ]);

                return back()->with('error', 'L\'emploi du temps associé est introuvable.');
            }

            // Load required data with error handling
            try {
                $emploiTempsController = new ESBTPEmploiTempsController;
                $reflection = new \ReflectionClass($emploiTempsController);

                $planificationMethod = $reflection->getMethod('getPlanificationDataForClasse');
                $planificationMethod->setAccessible(true);

                $planificationData = $planificationMethod->invoke(
                    $emploiTempsController,
                    $emploiTemps->classe,
                    $emploiTemps->annee,
                    $emploiTemps->semestre
                );

                // PR3 chantier emploi-temps-lmd-unification : applique override LMD via service
                // canonical (SSOT). buildForPlanning() sans volumeBudget car edit form n'affiche
                // pas les KPIs heures realisees.
                if (($emploiTemps->classe->systeme_academique ?? '') === 'LMD') {
                    $planificationData = app(\App\Services\LMD\MatiereTreeBuilder::class)
                        ->buildForPlanning($planificationData, $emploiTemps->classe);
                }

                $planificationConfigured = $planificationData['planifications_configurees'] ?? false;
                $matieres = collect($planificationData['matieres_planifiees'] ?? []);

                $teachers = collect();
                foreach ($matieres as $matiere) {
                    $enseignantsPourMatiere = $matiere['enseignants_selectables'] ?? collect();
                    foreach ($enseignantsPourMatiere as $teacher) {
                        if ($teacher && ! $teachers->contains('id', $teacher->id)) {
                            $teachers->push($teacher->loadMissing(['user', 'availabilities']));
                        }
                    }
                }

                $sessionTeacher = $seancesCour->teacher()->with(['user', 'availabilities'])->first();
                if ($sessionTeacher && ! $teachers->contains('id', $sessionTeacher->id)) {
                    $teachers->push($sessionTeacher);
                }

                $teachers = $teachers->filter()->unique('id')->sortBy(function ($teacher) {
                    return $teacher->user->name ?? $teacher->matricule ?? '';
                })->values();

                $planning = app(\App\Services\TeacherPlanningService::class);
                $availabilityData = [];
                foreach ($teachers as $teacher) {
                    $baseAvailability = $planning->getAvailabilityMatrix($teacher)['availability'];
                    $availabilityData[$teacher->id] = $this->addExistingSessionsToAvailability($baseAvailability, $teacher, $seancesCour->id, $seancesCour->emploiTemps);
                }

                // Assurer la présence de la matière actuelle même si aucune planification n'est configurée
                if ($matieres->isEmpty() && $seancesCour->matiere) {
                    $planificationConfigured = true;

                    $enseignantsSelectables = collect();
                    if ($sessionTeacher) {
                        $enseignantsSelectables->push($sessionTeacher);
                    }

                    $matieres = collect([
                        [
                            'planification_id' => null,
                            'matiere' => $seancesCour->matiere,
                            'enseignant_principal' => null,
                            'enseignants_assignes' => $enseignantsSelectables,
                            'enseignants_selectables' => $enseignantsSelectables,
                            'enseignant_affiche' => optional($sessionTeacher)->user,
                            'volume_horaire_total' => '--',
                            'heures_restantes' => '--',
                            'pourcentage_utilise' => null,
                            'volume_horaire_cm' => null,
                            'volume_horaire_td' => null,
                            'volume_horaire_tp' => null,
                            'statut' => null,
                            'periode_debut' => null,
                            'periode_fin' => null,
                        ],
                    ]);
                }

                // Forcer la disponibilité des cartes matières lorsqu'on a des données (planification ou fallback)
                $planificationData['planifications_configurees'] = $planificationConfigured || $matieres->isNotEmpty();
                $planificationData['matieres_planifiees'] = $matieres->values();

                if (empty($planificationData['heures_totales'])) {
                    $planificationData['heures_totales'] = $matieres->count() ? '--' : 0;
                }
                if (empty($planificationData['heures_restantes'])) {
                    $planificationData['heures_restantes'] = $matieres->count() ? '--' : 0;
                }

                $sessionTypes = [
                    ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                    ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                    ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                    ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
                ];

                $joursSemaine = [
                    1 => 'Lundi',
                    2 => 'Mardi',
                    3 => 'Mercredi',
                    4 => 'Jeudi',
                    5 => 'Vendredi',
                    6 => 'Samedi',
                ];

                $defaultColors = ESBTPSeanceCours::DEFAULT_COLORS;
            } catch (\Exception $e) {
                Log::error('Error loading required data for edit form', [
                    'session_id' => $seancesCour->id,
                    'error' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ]);

                return back()->with('error', 'Erreur lors du chargement des données du formulaire.');
            }

            return view('esbtp.seances-cours.edit', compact(
                'seancesCour',
                'emploiTemps',
                'teachers',
                'matieres',
                'sessionTypes',
                'joursSemaine',
                'defaultColors',
                'planificationData',
                'availabilityData'
            ));

        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@edit', [
                'session_id' => $seancesCour->id ?? null,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors du chargement du formulaire de modification: '.$e->getMessage());
        }
    }

    /**
     * Mettre à jour une séance de cours existante.
     */
    public function update(Request $request, ESBTPSeanceCours $seancesCour)
    {
        try {
            // Une séance ne change pas de nature en cours de route : le type
            // stocké est réimposé avant validation, et c'est lui qui pilote les
            // règles conditionnelles.
            $request->merge(['type' => $seancesCour->type]);
            $topType = $seancesCour->type;

            $validated = $request->validate($this->reglesDeModification($topType));
            if (! array_key_exists('teacher_id', $validated)) {
                $validated['teacher_id'] = null;
            }
            if ($topType === ESBTPSeanceCours::TYPE_HOMEWORK) {
                $validated['teacher_id'] = null;
            }
            if (in_array($topType, [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH], true)) {
                unset($validated['type_seance']);
            }

            $emploiTemps = ESBTPEmploiTemps::findOrFail($seancesCour->emploi_temps_id);

            // La date suit le jour. Elle ne le suivait pas : déplacer une séance
            // du lundi au jeudi laissait `date_seance` sur le lundi, et c'est
            // cette colonne — non le `jour` — que lisent les heures enseignant,
            // la paie et l'émargement. La séance changeait de case à l'écran
            // sans changer de jour pour le reste de l'application.
            $validated['date_seance'] = $emploiTemps->dateDuJour($validated['jour']);

            $conflits = $this->conflitsDeLaModification($seancesCour, $emploiTemps, $validated);

            if (! empty($conflits)) {
                throw ValidationException::withMessages(['conflicts' => $conflits]);
            }

            // La séance et son devoir bougent ensemble ou pas du tout : un
            // échec d'écriture de l'alignement du devoir annule aussi la séance.
            $avertissement = DB::transaction(function () use ($seancesCour, $validated) {
                $seancesCour->update($validated);

                return $seancesCour->type === ESBTPSeanceCours::TYPE_HOMEWORK
                    ? app(AlignementDuDevoir::class)->aligner($seancesCour)
                    : null;
            });

            return redirect()
                ->route('esbtp.emploi-temps.show', $seancesCour->emploi_temps_id)
                ->with('success', 'Séance mise à jour avec succès.')
                ->with('warning', $avertissement);
        } catch (ValidationException $e) {
            // À relancer AVANT le filet large, qui la convertissait en « une
            // erreur est survenue » : l'utilisateur perdait le détail. Cela vaut
            // aussi pour le `$request->validate()` de cette méthode, dont les
            // messages de champ tombaient déjà dans ce filet.
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@update: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la séance.');
        }
    }

    /**
     * Supprimer une séance de cours.
     */
    public function destroy(ESBTPSeanceCours $seancesCour)
    {
        try {
            $emploiTempsId = $seancesCour->emploi_temps_id;
            if ($seancesCour->type === ESBTPSeanceCours::TYPE_HOMEWORK && $seancesCour->homeworkEvaluation) {
                $seancesCour->homeworkEvaluation->delete();
            }
            $seancesCour->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'emploi_temps_id' => $emploiTempsId,
                    'message' => 'Séance supprimée avec succès.',
                ]);
            }

            return redirect()
                ->route('esbtp.emploi-temps.show', $emploiTempsId)
                ->with('success', 'Séance supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@destroy: '.$e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la suppression de la séance.',
                ], 500);
            }

            return back()->with('error', 'Une erreur est survenue lors de la suppression de la séance.');
        }
    }

    /**
     * PR17.2 : Page premium picker EDT quand /esbtp/seances-cours/create est appelé
     * sans emploi_temps_id. Évite redirect silencieux vers /navbar/notifications.
     */
    private function showEmploiTempsPicker(Request $request)
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first()
            ?? ESBTPAnneeUniversitaire::orderByDesc('id')->first();

        $emploisTemps = ESBTPEmploiTemps::with(['classe.filiere', 'classe.niveau'])
            ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee->id))
            ->where('is_active', true)
            ->orderByDesc('is_current')
            ->orderByDesc('date_debut')
            ->get();

        return view('esbtp.seances-cours.picker-emploi-temps', compact('emploisTemps', 'annee'));
    }
}
