<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPDepartment;
use App\Models\ESBTPLaboratory;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ESBTPEnseignantController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the teachers.
     */
    public function index(Request $request)
    {
        $query = ESBTPTeacher::with(['user', 'department', 'laboratory']);
        
        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        
        if ($request->filled('specialization')) {
            $query->where('specialization', 'like', '%' . $request->specialization . '%');
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        $teachers = $query->paginate(15);
        
        // Données pour les filtres
        $departments = ESBTPDepartment::where('is_active', true)->get();
        $specializations = ESBTPTeacher::distinct()->pluck('specialization')->filter();
        
        // Statistiques
        $stats = [
            'total' => ESBTPTeacher::count(),
            'active' => ESBTPTeacher::where('status', 'active')->count(),
            'inactive' => ESBTPTeacher::where('status', 'inactive')->count(),
            'permanent' => ESBTPTeacher::where('type_contrat', 'permanent')->count(),
            'temporary' => ESBTPTeacher::where('type_contrat', 'temporaire')->count(),
        ];
        
        return view('esbtp.enseignants.index', compact('teachers', 'departments', 'specializations', 'stats'));
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create()
    {
        $departments = ESBTPDepartment::where('is_active', true)->get();
        $laboratories = ESBTPLaboratory::where('is_active', true)->get();
        $matieres = ESBTPMatiere::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)->get();
        
        // Données pour les formulaires
        $titres_academiques = [
            'M.' => 'Monsieur',
            'Mme' => 'Madame',
            'Dr.' => 'Docteur',
            'Pr.' => 'Professeur'
        ];
        
        $grades_academiques = [
            'assistant' => 'Assistant',
            'maitre_assistant' => 'Maître Assistant',
            'maitre_conferences' => 'Maître de Conférences',
            'professeur' => 'Professeur'
        ];
        
        $types_contrat = [
            'permanent' => 'Permanent',
            'temporaire' => 'Temporaire',
            'vacataire' => 'Vacataire',
            'consultant' => 'Consultant'
        ];
        
        $statuts_emploi = [
            'temps_plein' => 'Temps Plein',
            'temps_partiel' => 'Temps Partiel',
            'vacations' => 'Vacations'
        ];
        
        $methodes_enseignement = [
            'cours_magistral' => 'Cours Magistral',
            'travaux_diriges' => 'Travaux Dirigés',
            'travaux_pratiques' => 'Travaux Pratiques',
            'projet' => 'Projet',
            'stage' => 'Stage',
            'apprentissage_actif' => 'Apprentissage Actif',
            'classe_inversee' => 'Classe Inversée'
        ];
        
        $outils_pedagogiques = [
            'tableau_blanc' => 'Tableau Blanc',
            'ordinateur' => 'Ordinateur',
            'projecteur' => 'Projecteur',
            'plateforme_lms' => 'Plateforme LMS',
            'outils_collaboration' => 'Outils de Collaboration',
            'simulation' => 'Simulation',
            'realite_virtuelle' => 'Réalité Virtuelle'
        ];
        
        return view('esbtp.enseignants.create', compact(
            'departments', 'laboratories', 'matieres', 'classes',
            'titres_academiques', 'grades_academiques', 'types_contrat', 
            'statuts_emploi', 'methodes_enseignement', 'outils_pedagogiques'
        ));
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Informations utilisateur - username et password automatiques
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            
            // Informations professionnelles
            'titre_academique' => 'nullable|string|max:10',
            'grade_academique' => 'nullable|string|max:50',
            'specialization' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'laboratory_id' => 'nullable|exists:laboratories,id',
            
            // Informations contractuelles
            'type_contrat' => 'required|in:permanent,temporaire,vacataire,consultant',
            'statut_emploi' => 'required|in:temps_plein,temps_partiel,vacations',
            'date_embauche' => 'required|date',
            'fin_contrat' => 'nullable|date|after:date_embauche',
            'taux_horaire' => 'nullable|numeric|min:0',
            
            // Expérience et qualifications
            'diplome_principal' => 'nullable|string|max:255',
            'universite_diplome' => 'nullable|string|max:255',
            'annee_diplome' => 'nullable|integer|min:1950|max:' . date('Y'),
            'annees_experience_enseignement' => 'nullable|integer|min:0',
            'annees_experience_professionnelle' => 'nullable|integer|min:0',
            
            // Préférences
            'charge_horaire_max_semaine' => 'nullable|integer|min:1|max:60',
            'accepte_enseignement_distance' => 'boolean',
            'accepte_cours_weekend' => 'boolean',
            'accepte_cours_soir' => 'boolean',
            
            // Autres informations
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'motivation' => 'nullable|string|max:1000',
            'objectifs_pedagogiques' => 'nullable|string|max:1000',
            
            // Fichiers
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        
        try {
            // Créer l'utilisateur avec username et password automatiques
            $user = $this->userService->createUserWithAutoCredentials([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ], 'enseignant');

            // Assigner le rôle enseignant
            $user->assignRole('enseignant');

            // Gérer les uploads de fichiers
            $cvPath = null;
            $photoPath = null;
            
            if ($request->hasFile('cv')) {
                $cvPath = $request->file('cv')->store('enseignants/cv', 'public');
            }
            
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('enseignants/photos', 'public');
            }

            // Créer le profil enseignant
            $teacher = ESBTPTeacher::create([
                'user_id' => $user->id,
                'matricule' => $this->generateMatricule(),
                'title' => $request->titre_academique,
                'specialization' => $request->specialization,
                'department_id' => $request->department_id,
                'laboratory_id' => $request->laboratory_id,
                'grade' => $request->grade_academique,
                'bio' => $request->bio,
                'website' => $request->website,
                'status' => 'active',
                'teaching_hours_due' => $request->charge_horaire_max_semaine ?? 40,
                'created_by' => auth()->id(),
            ]);

            // Créer le profil avancé si les tables existent
            if (Schema::hasTable('esbtp_enseignant_profiles')) {
                DB::table('esbtp_enseignant_profiles')->insert([
                    'user_id' => $user->id,
                    'matricule_enseignant' => $teacher->matricule,
                    'titre_academique' => $request->titre_academique,
                    'grade_academique' => $request->grade_academique,
                    'diplome_principal' => $request->diplome_principal,
                    'universite_diplome' => $request->universite_diplome,
                    'annee_diplome' => $request->annee_diplome,
                    'annees_experience_enseignement' => $request->annees_experience_enseignement ?? 0,
                    'annees_experience_professionnelle' => $request->annees_experience_professionnelle ?? 0,
                    'charge_horaire_max_semaine' => $request->charge_horaire_max_semaine ?? 40,
                    'type_contrat' => $request->type_contrat,
                    'statut_emploi' => $request->statut_emploi,
                    'date_embauche' => $request->date_embauche,
                    'fin_contrat' => $request->fin_contrat,
                    'taux_horaire' => $request->taux_horaire,
                    'accepte_enseignement_distance' => $request->boolean('accepte_enseignement_distance'),
                    'accepte_cours_weekend' => $request->boolean('accepte_cours_weekend'),
                    'accepte_cours_soir' => $request->boolean('accepte_cours_soir'),
                    'motivation' => $request->motivation,
                    'objectifs_pedagogiques' => $request->objectifs_pedagogiques,
                    'statut' => 'actif',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            
            // Obtenir les informations de connexion pour affichage
            $credentials = $this->userService->getCredentialsInfo(
                $user->username, 
                $this->userService->generateDefaultPassword()
            );
            
            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Enseignant créé avec succès')
                ->with('credentials', $credentials);
                
        } catch (\Exception $e) {
            DB::rollback();
            
            // Supprimer les fichiers uploadés en cas d'erreur
            if ($cvPath && Storage::disk('public')->exists($cvPath)) {
                Storage::disk('public')->delete($cvPath);
            }
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'enseignant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified teacher.
     */
    public function show(ESBTPTeacher $teacher)
    {
        $teacher->load(['user', 'department', 'laboratory', 'createdBy', 'updatedBy']);
        
        // Récupérer les informations additionnelles si elles existent
        $profileData = null;
        if (Schema::hasTable('esbtp_enseignant_profiles')) {
            $profileData = DB::table('esbtp_enseignant_profiles')
                ->where('user_id', $teacher->user_id)
                ->first();
        }
        
        return view('esbtp.enseignants.show', compact('teacher', 'profileData'));
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(ESBTPTeacher $teacher)
    {
        $teacher->load(['user', 'department', 'laboratory']);
        
        $departments = ESBTPDepartment::where('is_active', true)->get();
        $laboratories = ESBTPLaboratory::where('is_active', true)->get();
        $matieres = ESBTPMatiere::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)->get();
        
        // Récupérer les informations additionnelles si elles existent
        $profileData = null;
        if (Schema::hasTable('esbtp_enseignant_profiles')) {
            $profileData = DB::table('esbtp_enseignant_profiles')
                ->where('user_id', $teacher->user_id)
                ->first();
        }
        
        // Données pour les formulaires (même que dans create)
        $titres_academiques = [
            'M.' => 'Monsieur',
            'Mme' => 'Madame',
            'Dr.' => 'Docteur',
            'Pr.' => 'Professeur'
        ];
        
        $grades_academiques = [
            'assistant' => 'Assistant',
            'maitre_assistant' => 'Maître Assistant',
            'maitre_conferences' => 'Maître de Conférences',
            'professeur' => 'Professeur'
        ];
        
        $types_contrat = [
            'permanent' => 'Permanent',
            'temporaire' => 'Temporaire',
            'vacataire' => 'Vacataire',
            'consultant' => 'Consultant'
        ];
        
        $statuts_emploi = [
            'temps_plein' => 'Temps Plein',
            'temps_partiel' => 'Temps Partiel',
            'vacations' => 'Vacations'
        ];
        
        return view('esbtp.enseignants.edit', compact(
            'teacher', 'profileData', 'departments', 'laboratories', 'matieres', 'classes',
            'titres_academiques', 'grades_academiques', 'types_contrat', 'statuts_emploi'
        ));
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, ESBTPTeacher $teacher)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->user_id,
            'phone' => 'nullable|string|max:20',
            'specialization' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'laboratory_id' => 'nullable|exists:laboratories,id',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'status' => 'required|in:active,inactive',
            'teaching_hours_due' => 'nullable|integer|min:0|max:80',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        
        try {
            // Mettre à jour l'utilisateur
            $teacher->user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            // Mettre à jour le profil enseignant
            $teacher->update([
                'specialization' => $request->specialization,
                'department_id' => $request->department_id,
                'laboratory_id' => $request->laboratory_id,
                'bio' => $request->bio,
                'website' => $request->website,
                'status' => $request->status,
                'teaching_hours_due' => $request->teaching_hours_due,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();
            
            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Enseignant mis à jour avec succès');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(ESBTPTeacher $teacher)
    {
        try {
            DB::beginTransaction();
            
            // Supprimer les fichiers associés
            if ($teacher->cv_path && Storage::disk('public')->exists($teacher->cv_path)) {
                Storage::disk('public')->delete($teacher->cv_path);
            }
            if ($teacher->photo_path && Storage::disk('public')->exists($teacher->photo_path)) {
                Storage::disk('public')->delete($teacher->photo_path);
            }
            
            // Supprimer le profil étendu si il existe
            if (Schema::hasTable('esbtp_enseignant_profiles')) {
                DB::table('esbtp_enseignant_profiles')
                    ->where('user_id', $teacher->user_id)
                    ->delete();
            }
            
            // Supprimer l'enseignant
            $teacher->delete();
            
            DB::commit();
            
            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Enseignant supprimé avec succès');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique matricule for the teacher.
     */
    private function generateMatricule()
    {
        $year = date('Y');
        $lastTeacher = ESBTPTeacher::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastTeacher ? (int)substr($lastTeacher->matricule, -4) + 1 : 1;
        
        return sprintf('ENS-%s-%04d', $year, $sequence);
    }

    /**
     * Toggle teacher status.
     */
    public function toggleStatus(ESBTPTeacher $teacher)
    {
        $teacher->update([
            'status' => $teacher->status === 'active' ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);
        
        return redirect()->back()->with('success', 'Statut mis à jour avec succès');
    }

    /**
     * Afficher la page de gestion des matières d'un enseignant
     */
    public function matieres(ESBTPTeacher $teacher)
    {
        $this->authorize('edit_enseignants');
        
        // Récupérer toutes les matières disponibles
        $matieres = ESBTPMatiere::with(['niveauEtude', 'filieres'])
            ->orderBy('name')
            ->get();
        
        // Récupérer les matières actuellement assignées à l'enseignant
        $matieresAssignees = $teacher->user->matieres()->with(['niveauEtude', 'filieres'])->get();
        
        return view('esbtp.enseignants.matieres', compact('teacher', 'matieres', 'matieresAssignees'));
    }

    /**
     * Assigner/Désassigner des matières à un enseignant
     */
    public function assignMatieres(Request $request, ESBTPTeacher $teacher)
    {
        $this->authorize('edit_enseignants');
        
        $request->validate([
            'matieres' => 'array',
            'matieres.*' => 'exists:esbtp_matieres,id',
        ]);

        DB::beginTransaction();
        
        try {
            // Récupérer l'année universitaire actuelle
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_active', true)->first();
            
            if (!$anneeUniversitaire) {
                return redirect()->back()->with('error', 'Aucune année universitaire active trouvée.');
            }

            // Préparer les données pour la table pivot
            $matieresData = [];
            foreach ($request->matieres ?? [] as $matiereId) {
                $matieresData[$matiereId] = [
                    'annee_universitaire_id' => $anneeUniversitaire->id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Synchroniser les matières (supprime les anciennes et ajoute les nouvelles)
            $teacher->user->matieres()->syncWithoutDetaching($matieresData);
            
            DB::commit();
            
            return redirect()->back()->with('success', 'Matières assignées avec succès.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors de l\'assignation : ' . $e->getMessage());
        }
    }
}