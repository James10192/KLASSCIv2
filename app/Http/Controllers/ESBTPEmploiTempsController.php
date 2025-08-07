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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\ESBTPPDFService;

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
    public function index()
    {
        // No policy-based authorization
        $emploisTemps = ESBTPEmploiTemps::with(['classe', 'seances'])->get();

        // Ajout des filières pour le filtre
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();

        // Ajout des niveaux pour le filtre
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        // Ajout des années universitaires pour le filtre
        $annees = ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        // Statistiques
        $totalEmploisTemps = $emploisTemps->count();
        $emploisTempsActifs = $emploisTemps->where('is_active', true)->count();
        $totalSeances = ESBTPSeanceCours::count();

        // Emplois du temps de l'année en cours
        $emploisTempsAnneeEnCours = 0;
        if ($anneeEnCours) {
            // Trouver tous les emplois du temps associés à des classes de l'année en cours
            $classesAnneeEnCours = ESBTPClasse::where('annee_universitaire_id', $anneeEnCours->id)->pluck('id')->toArray();
            $emploisTempsAnneeEnCours = $emploisTemps->whereIn('classe_id', $classesAnneeEnCours)->count();
        }

        return view('esbtp.emploi-temps.index', compact(
            'emploisTemps', 'filieres', 'niveaux', 'annees',
            'totalEmploisTemps', 'emploisTempsActifs', 'totalSeances', 'emploisTempsAnneeEnCours'
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

        // Récupérer l'année universitaire active
        $anneeActive = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        // Initialiser les données de planification
        $planificationData = [];
        
        // Si on a une classe sélectionnée (via paramètres GET ou session)
        $classeSelectionnee = request('classe_id') ? ESBTPClasse::find(request('classe_id')) : null;
        
        // Utiliser l'année sélectionnée dans le formulaire ou l'année active par défaut
        $anneeSelectionnee = null;
        if (request('annee_universitaire_id')) {
            $anneeSelectionnee = ESBTPAnneeUniversitaire::find(request('annee_universitaire_id'));
        } elseif ($anneeActive) {
            $anneeSelectionnee = $anneeActive;
        }
        
        if ($classeSelectionnee && $anneeSelectionnee) {
            $semestre = request('semestre') ?: null;
            $planificationData = $this->getPlanificationDataForClasse($classeSelectionnee, $anneeSelectionnee, $semestre);
        }

        return view('esbtp.emploi-temps.create', compact(
            'classes', 'annees', 'semaineCourante', 'planificationData', 'classeSelectionnee', 'anneeActive', 'anneeSelectionnee'
        ));
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

        // Récupérer les planifications académiques pour cette classe
        $planifications = \App\Models\ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->when($semestre, function($query) use ($semestre) {
                $query->where('semestre', $semestre);
            })
            ->active()
            ->with(['matiere', 'enseignantPrincipal'])
            ->get();

        if ($planifications->isEmpty()) {
            // Aucune planification configurée
            $data['message_configuration'] = "Aucune planification académique n'a été configurée pour cette classe. Veuillez d'abord configurer la planification.";
            $data['lien_configuration'] = route('esbtp.planning-general.annuel', [
                'annee_id' => $annee->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_id,
                'semestre' => $semestre ?: 1
            ]);
            return $data;
        }

        $data['planifications_configurees'] = true;
        
        // Traiter chaque planification pour calculer les heures restantes
        $matieresPlanifiees = collect();
        $enseignantsIds = collect();
        
        foreach ($planifications as $planification) {
            // Utiliser les heures effectuées basées sur les émargements validés
            // Si le champ n'existe pas encore, utiliser l'ancienne méthode comme fallback
            $heuresUtilisees = $planification->heures_effectuees ?? 0;
            
            // Fallback : si pas d'heures effectuées, calculer à partir des séances
            if ($heuresUtilisees == 0) {
                $seances = \App\Models\ESBTPSeanceCours::where('matiere_id', $planification->matiere_id)
                    ->where('classe_id', $classe->id)
                    ->where('annee_universitaire_id', $annee->id)
                    ->when($semestre, function($query) use ($semestre) {
                        // Si on a un semestre spécifique, filtrer les séances par période
                        // Cette logique peut être adaptée selon votre implémentation des semestres
                    })
                    ->select('heure_debut', 'heure_fin')
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
            
            $matieresPlanifiees->push([
                'planification_id' => $planification->id,
                'matiere' => $planification->matiere,
                'enseignant_principal' => $planification->enseignantPrincipal,
                'volume_horaire_total' => $planification->volume_horaire_total,
                'heures_utilisees' => $heuresUtilisees,
                'heures_restantes' => max(0, $heuresRestantes),
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
        $data['heures_totales'] = $matieresPlanifiees->sum('volume_horaire_total');
        $data['heures_restantes'] = $matieresPlanifiees->sum('heures_restantes');

        // Récupérer les enseignants disponibles (tous les enseignants, mais marquer ceux qui sont assignés)
        $tousLesEnseignants = \App\Models\User::role('enseignant')
            ->where('is_active', true)
            ->with('profile')
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

        // Récupérer les heures de début et de fin pour l'affichage (créneaux d'une heure)
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
        foreach ($emploi_temp->seances as $seance) {
            $matiereName = $seance->matiere ? $seance->matiere->name : 'Non définie';
            if (!isset($matiereStats[$matiereName])) {
                $matiereStats[$matiereName] = 0;
            }
            $matiereStats[$matiereName]++;
        }

        // Renommer la variable pour la vue
        $emploiTemps = $emploi_temp;

        return view('esbtp.emploi-temps.show', compact(
            'emploiTemps', 'seances', 'seancesParJour',
            'heuresDebut', 'heuresFin', 'joursNoms',
            'matiereStats', 'timeSlots', 'days'
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
            'semestre' => 'required|string',
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
            ->with(['matiere', 'enseignant'])
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

        $matieres = ESBTPMatiere::whereHas('filieres', function($query) use ($emploi_temp) {
            $query->whereHas('classes', function($q) use ($emploi_temp) {
                $q->where('id', $emploi_temp->classe_id);
            });
        })->get();

        $enseignants = User::role('enseignant')->get();

        return view('esbtp.emploi-temps.add-session', compact('emploi_temp', 'matieres', 'enseignants'));
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

        return redirect()->route('esbtp.emploi-temps.show', $validated['emploi_temps_id'])
            ->with('success', 'Séance ajoutée avec succès.');
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

            // Retourner le PDF pour téléchargement
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du PDF de l\'emploi du temps', [
                'error' => $e->getMessage(),
                'emploi_temps_id' => $id
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la génération du PDF.');
        }
    }
}
