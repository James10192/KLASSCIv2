<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTPEnseignantProfile;
use App\Models\ESBTPEnseignantDisponibilite;
use App\Models\ESBTPEnseignantAffectation;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ESBTPEnseignantProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Afficher la liste des enseignants
     */
    public function index(Request $request)
    {
        $filtres = [
            'statut' => $request->input('statut'),
            'type_contrat' => $request->input('type_contrat'),
            'grade_academique' => $request->input('grade_academique'),
            'specialite' => $request->input('specialite'),
            'profil_valide' => $request->input('profil_valide')
        ];

        $query = ESBTPEnseignantProfile::with(['user', 'validateur'])
                    ->when($filtres['statut'], function($q, $statut) {
                        return $q->where('statut', $statut);
                    })
                    ->when($filtres['type_contrat'], function($q, $type) {
                        return $q->where('type_contrat', $type);
                    })
                    ->when($filtres['grade_academique'], function($q, $grade) {
                        return $q->where('grade_academique', $grade);
                    })
                    ->when($filtres['profil_valide'] !== null, function($q) use ($filtres) {
                        return $q->where('profil_valide', $filtres['profil_valide']);
                    })
                    ->when($filtres['specialite'], function($q, $specialite) {
                        return $q->where('specialites', 'LIKE', '%' . $specialite . '%');
                    });

        $enseignants = $query->orderBy('created_at', 'desc')->paginate(15);

        $statistiques = [
            'total' => ESBTPEnseignantProfile::count(),
            'actifs' => ESBTPEnseignantProfile::where('statut', 'actif')->count(),
            'valides' => ESBTPEnseignantProfile::where('profil_valide', true)->count(),
            'charge_moyenne' => ESBTPEnseignantProfile::avg('charge_horaire_actuelle'),
            'taux_assiduite_moyen' => ESBTPEnseignantProfile::avg('taux_assiduite')
        ];

        // Options pour les filtres
        $grades = ESBTPEnseignantProfile::distinct('grade_academique')
                   ->whereNotNull('grade_academique')
                   ->pluck('grade_academique');
        
        $specialites = ESBTPEnseignantProfile::whereNotNull('specialites')
                        ->get()
                        ->flatMap(function($profile) {
                            return $profile->specialites ?? [];
                        })
                        ->unique()
                        ->values();

        return view('esbtp.enseignants.index', compact(
            'enseignants', 'statistiques', 'filtres', 'grades', 'specialites'
        ));
    }

    /**
     * Afficher le formulaire de création d'un profil enseignant
     */
    public function create()
    {
        // Utilisateurs avec le rôle enseignant qui n'ont pas encore de profil
        $users = User::role('enseignant')
                    ->whereDoesntHave('enseignantProfile')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();

        return view('esbtp.enseignants.create', compact('users'));
    }

    /**
     * Enregistrer un nouveau profil enseignant
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:esbtp_enseignant_profiles,user_id',
            'matricule_enseignant' => 'nullable|string|unique:esbtp_enseignant_profiles,matricule_enseignant',
            'titre_academique' => 'nullable|string|max:50',
            'grade_academique' => 'nullable|string|max:100',
            'diplome_principal' => 'required|string|max:200',
            'universite_diplome' => 'nullable|string|max:200',
            'annee_diplome' => 'nullable|integer|min:1950|max:' . (date('Y') + 10),
            'specialites' => 'nullable|array',
            'competences_techniques' => 'nullable|array',
            'certifications' => 'nullable|array',
            'langues' => 'nullable|array',
            'annees_experience_enseignement' => 'required|integer|min:0|max:50',
            'annees_experience_professionnelle' => 'required|integer|min:0|max:50',
            'charge_horaire_max_semaine' => 'required|integer|min:1|max:60',
            'type_contrat' => 'required|in:permanent,temporaire,vacataire,consultant',
            'statut_emploi' => 'required|in:temps_plein,temps_partiel,vacations',
            'taux_horaire' => 'nullable|numeric|min:0',
            'date_embauche' => 'nullable|date',
            'fin_contrat' => 'nullable|date|after:date_embauche',
            'accepte_enseignement_distance' => 'boolean',
            'accepte_cours_weekend' => 'boolean',
            'accepte_cours_soir' => 'boolean',
            'motivation' => 'nullable|string|max:1000',
            'objectifs_pedagogiques' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by'] = Auth::id();
            $data['statut'] = 'actif';
            $data['profil_valide'] = false;

            $profile = ESBTPEnseignantProfile::create($data);

            // Créer les disponibilités par défaut
            ESBTPEnseignantDisponibilite::creerDisponibilitesParDefaut($profile->id);

            DB::commit();

            return redirect()->route('esbtp.enseignants.show', $profile->id)
                           ->with('success', 'Profil enseignant créé avec succès');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la création: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Afficher le profil d'un enseignant
     */
    public function show($id)
    {
        $profile = ESBTPEnseignantProfile::with([
            'user', 'validateur', 'createdBy', 'updatedBy',
            'disponibilites' => function($query) {
                $query->actif()->orderBy('jour_semaine')->orderBy('heure_debut');
            },
            'affectations' => function($query) {
                $query->with(['planification.matiere', 'matiere', 'classe'])
                      ->orderBy('date_debut', 'desc');
            }
        ])->findOrFail($id);

        // Statistiques de l'enseignant
        $statistiques = [
            'charge_horaire_pourcentage' => $profile->taux_charge,
            'heures_disponibles' => $profile->heures_disponibles,
            'nombre_affectations_actives' => $profile->affectationsActives()->count(),
            'nombre_affectations_total' => $profile->affectations()->count(),
            'anciennete' => $profile->anciennete,
            'besoin_formation' => $profile->aBesoinFormation(),
            'profil_complet' => $profile->isProfilComplet(),
            'statut_evaluation' => $profile->statut_evaluation
        ];

        // Planifications compatibles disponibles
        $planificationsCompatibles = ESBTPPlanificationAcademique::with(['matiere', 'filiere', 'niveauEtude'])
            ->where('statut', 'planifie')
            ->whereNull('enseignant_principal_id')
            ->get()
            ->filter(function($planification) use ($profile) {
                return $profile->estCompatibleAvecPlanification($planification);
            });

        return view('esbtp.enseignants.show', compact(
            'profile', 'statistiques', 'planificationsCompatibles'
        ));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit($id)
    {
        $profile = ESBTPEnseignantProfile::with('user')->findOrFail($id);
        
        return view('esbtp.enseignants.edit', compact('profile'));
    }

    /**
     * Mettre à jour le profil
     */
    public function update(Request $request, $id)
    {
        $profile = ESBTPEnseignantProfile::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'matricule_enseignant' => 'nullable|string|unique:esbtp_enseignant_profiles,matricule_enseignant,' . $id,
            'titre_academique' => 'nullable|string|max:50',
            'grade_academique' => 'nullable|string|max:100',
            'diplome_principal' => 'required|string|max:200',
            'universite_diplome' => 'nullable|string|max:200',
            'annee_diplome' => 'nullable|integer|min:1950|max:' . (date('Y') + 10),
            'specialites' => 'nullable|array',
            'competences_techniques' => 'nullable|array',
            'certifications' => 'nullable|array',
            'langues' => 'nullable|array',
            'annees_experience_enseignement' => 'required|integer|min:0|max:50',
            'annees_experience_professionnelle' => 'required|integer|min:0|max:50',
            'charge_horaire_max_semaine' => 'required|integer|min:1|max:60',
            'type_contrat' => 'required|in:permanent,temporaire,vacataire,consultant',
            'statut_emploi' => 'required|in:temps_plein,temps_partiel,vacations',
            'taux_horaire' => 'nullable|numeric|min:0',
            'date_embauche' => 'nullable|date',
            'fin_contrat' => 'nullable|date|after:date_embauche',
            'accepte_enseignement_distance' => 'boolean',
            'accepte_cours_weekend' => 'boolean',
            'accepte_cours_soir' => 'boolean',
            'motivation' => 'nullable|string|max:1000',
            'objectifs_pedagogiques' => 'nullable|string|max:1000',
            'observations_rh' => 'nullable|string|max:1000',
            'notes_direction' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->all();
            $data['updated_by'] = Auth::id();

            // Ajouter à l'historique des modifications
            $historique = $profile->historique_modifications ?? [];
            $historique[] = [
                'date' => now()->toISOString(),
                'utilisateur' => Auth::user()->name,
                'action' => 'Modification du profil',
                'details' => 'Profil mis à jour'
            ];
            $data['historique_modifications'] = $historique;

            $profile->update($data);

            return redirect()->route('esbtp.enseignants.show', $profile->id)
                           ->with('success', 'Profil mis à jour avec succès');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Valider un profil enseignant
     */
    public function valider($id)
    {
        $profile = ESBTPEnseignantProfile::findOrFail($id);

        if (!$profile->isProfilComplet()) {
            return back()->withErrors(['error' => 'Le profil n\'est pas complet. Veuillez remplir tous les champs obligatoires.']);
        }

        $profile->update([
            'profil_valide' => true,
            'valide_par' => Auth::id(),
            'date_validation' => now(),
            'updated_by' => Auth::id()
        ]);

        // Ajouter à l'historique
        $historique = $profile->historique_modifications ?? [];
        $historique[] = [
            'date' => now()->toISOString(),
            'utilisateur' => Auth::user()->name,
            'action' => 'Validation du profil',
            'details' => 'Profil validé par ' . Auth::user()->name
        ];
        $profile->update(['historique_modifications' => $historique]);

        return back()->with('success', 'Profil validé avec succès');
    }

    /**
     * Affecter un enseignant à une planification
     */
    public function affecter(Request $request, $id)
    {
        $profile = ESBTPEnseignantProfile::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'planification_id' => 'required|exists:esbtp_planifications_academiques,id',
            'type_affectation' => 'required|in:principal,secondaire,remplacant,temporaire',
            'heures_affectees' => 'required|integer|min:1',
            'type_cours' => 'required|in:cm,td,tp,stage,projet',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $planification = ESBTPPlanificationAcademique::findOrFail($request->planification_id);

        // Vérifier la compatibilité
        if (!$profile->estCompatibleAvecPlanification($planification)) {
            return back()->withErrors(['error' => 'L\'enseignant n\'est pas compatible avec cette planification']);
        }

        // Vérifier la charge horaire
        if (!$profile->peutPrendreHeuresSupplementaires($request->heures_affectees)) {
            return back()->withErrors(['error' => 'L\'enseignant n\'a pas assez d\'heures disponibles']);
        }

        try {
            DB::beginTransaction();

            // Créer l'affectation
            $affectation = ESBTPEnseignantAffectation::create([
                'enseignant_profile_id' => $profile->id,
                'planification_id' => $planification->id,
                'matiere_id' => $planification->matiere_id,
                'type_affectation' => $request->type_affectation,
                'heures_affectees' => $request->heures_affectees,
                'type_cours' => $request->type_cours,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'statut' => 'active',
                'affecte_par' => Auth::id(),
                'date_affectation' => now()
            ]);

            // Mettre à jour la planification si c'est l'enseignant principal
            if ($request->type_affectation === 'principal') {
                $planification->update(['enseignant_principal_id' => $profile->user_id]);
            }

            // Mettre à jour la charge horaire
            $profile->mettreAJourChargeHoraire();

            DB::commit();

            return back()->with('success', 'Enseignant affecté avec succès');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de l\'affectation: ' . $e->getMessage()]);
        }
    }

    /**
     * Gérer les disponibilités d'un enseignant
     */
    public function disponibilites($id)
    {
        $profile = ESBTPEnseignantProfile::with([
            'user',
            'disponibilites' => function($query) {
                $query->actif()->orderBy('jour_semaine')->orderBy('heure_debut');
            }
        ])->findOrFail($id);

        return view('esbtp.enseignants.disponibilites', compact('profile'));
    }

    /**
     * Tableau de bord des enseignants (statistiques globales)
     */
    public function dashboard()
    {
        $statistiques = [
            'total_enseignants' => ESBTPEnseignantProfile::count(),
            'enseignants_actifs' => ESBTPEnseignantProfile::actif()->count(),
            'enseignants_valides' => ESBTPEnseignantProfile::valide()->count(),
            'charge_moyenne' => round(ESBTPEnseignantProfile::avg('charge_horaire_actuelle'), 2),
            'taux_assiduite_moyen' => round(ESBTPEnseignantProfile::avg('taux_assiduite'), 2),
            'enseignants_surcharge' => ESBTPEnseignantProfile::whereRaw('charge_horaire_actuelle > charge_horaire_max_semaine * 0.9')->count(),
            'enseignants_formation_necessaire' => ESBTPEnseignantProfile::whereNull('derniere_formation')
                ->orWhere('derniere_formation', '<', now()->subYears(2))
                ->count()
        ];

        // Répartition par type de contrat
        $repartitionContrats = ESBTPEnseignantProfile::select('type_contrat', DB::raw('count(*) as total'))
            ->groupBy('type_contrat')
            ->get();

        // Répartition par grade académique
        $repartitionGrades = ESBTPEnseignantProfile::select('grade_academique', DB::raw('count(*) as total'))
            ->whereNotNull('grade_academique')
            ->groupBy('grade_academique')
            ->get();

        return view('esbtp.enseignants.dashboard', compact(
            'statistiques', 'repartitionContrats', 'repartitionGrades'
        ));
    }
}