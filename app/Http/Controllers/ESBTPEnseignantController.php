<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPDepartment;
use App\Models\ESBTPLaboratory;
use App\Models\ESBTPTeacherAvailability;
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
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            
            // Informations professionnelles
            'titre_academique' => 'nullable|string|max:10',
            'grade_academique' => 'nullable|string|max:50',
            'specialization' => 'required|string|max:255',
            'department_id' => 'required|exists:esbtp_departments,id',
            'laboratory_id' => 'nullable|exists:esbtp_laboratories,id',
            
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
                'email' => $request->email ?: null,
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
                ->with('credentials', $credentials)
                ->with('created_teacher_id', $teacher->id);
                
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
     * Check for duplicate teachers based on name and specialization.
     */
    public function duplicates(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
        ]);

        $name = $request->input('name');
        $specialization = $request->input('specialization');

        // Simple duplicate detection based on similar name and specialization
        $duplicates = ESBTPTeacher::with('user')
            ->whereHas('user', function($query) use ($name) {
                // Similar name detection (simple LIKE for now)
                $query->where('name', 'LIKE', '%' . $name . '%');
            })
            ->when($specialization, function($query) use ($specialization) {
                $query->where('specialization', 'LIKE', '%' . $specialization . '%');
            })
            ->limit(10)
            ->get()
            ->map(function($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->user->name ?? '',
                    'email' => $teacher->user->email ?? '',
                    'specialization' => $teacher->specialization,
                    'matricule' => $teacher->matricule,
                    'status' => $teacher->status,
                    'show_url' => route('esbtp.enseignants.show', $teacher->id),
                ];
            });

        return response()->json([
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * Display the specified teacher.
     */
    public function show(ESBTPTeacher $enseignant)
    {
        $enseignant->load(['user', 'department', 'laboratory', 'createdBy', 'updatedBy', 'availabilities']);
        
        // Récupérer les informations additionnelles si elles existent
        $profileData = null;
        if (Schema::hasTable('esbtp_enseignant_profiles')) {
            $profileData = DB::table('esbtp_enseignant_profiles')
                ->where('user_id', $enseignant->user_id)
                ->first();
        }
        
        // Préparer les données de disponibilité réelles
        $realAvailability = $this->prepareAvailabilityData($enseignant);
        
        // Passer $enseignant en tant que $teacher pour la compatibilité avec la vue
        $teacher = $enseignant;
        return view('esbtp.enseignants.show', compact('teacher', 'profileData', 'realAvailability'));
    }
    
    /**
     * Préparer les données de disponibilité pour l'affichage
     */
    private function prepareAvailabilityData($teacher)
    {
        
        // Utiliser des créneaux par heure comme la page EDIT pour cohérence
        $hours = range(8, 18); // 8h à 18h = 11 heures 
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Initialiser avec 'unavailable' par défaut
        $availability = [];
        foreach ($days as $day) {
            $availability[$day] = array_fill(0, count($hours), 'unavailable');
        }
        
        // Remplir avec les vraies données - traitement par heure
        foreach ($teacher->availabilities as $avail) {
            $dayName = $days[$avail->day_of_week] ?? null;
            
            // Parser l'heure de début et de fin
            if ($avail->start_time instanceof \Carbon\Carbon) {
                $startHour = $avail->start_time->hour;
                $endHour = $avail->end_time->hour;
            } elseif (is_string($avail->start_time)) {
                $startHour = (int) substr($avail->start_time, 0, 2);
                $endHour = (int) substr($avail->end_time, 0, 2);
            } else {
                $startHour = (int) substr((string) $avail->start_time, 0, 2);
                $endHour = (int) substr((string) $avail->end_time, 0, 2);
            }
            
            // DEBUG DETAILLE
            \Log::info("🔧 Processing availability: ID={$avail->id}, day={$avail->day_of_week} ($dayName), start={$avail->start_time} (hour=$startHour), end={$avail->end_time} (hour=$endHour), type={$avail->availability_type}");
            
            // Remplir toutes les heures entre start_time et end_time
            if ($dayName) {
                for ($hour = $startHour; $hour < $endHour; $hour++) {
                    $hourIndex = $hour - 8; // Index dans le tableau (8h = index 0)
                    if ($hourIndex >= 0 && $hourIndex < count($hours)) {
                        $availability[$dayName][$hourIndex] = $avail->availability_type;
                        \Log::info("  -> Set {$dayName}[{$hourIndex}] (hour {$hour}) = {$avail->availability_type}");
                    }
                }
            }
            
        }
        
        return $availability;
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(ESBTPTeacher $enseignant)
    {
        $enseignant->load(['user', 'department', 'laboratory', 'availabilities']);
        
        $departments = ESBTPDepartment::where('is_active', true)->get();
        $laboratories = ESBTPLaboratory::where('is_active', true)->get();
        $matieres = ESBTPMatiere::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)->get();
        
        // Récupérer les informations additionnelles si elles existent
        $profileData = null;
        if (Schema::hasTable('esbtp_enseignant_profiles')) {
            $profileData = DB::table('esbtp_enseignant_profiles')
                ->where('user_id', $enseignant->user_id)
                ->first();
        }
        
        // Données pour les formulaires (même que dans create)
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
        
        // Utiliser le même format que la page SHOW pour cohérence
        $availabilityData = $this->prepareAvailabilityData($enseignant);
        
        // Assigner pour compatibilité avec la vue
        $teacher = $enseignant;
        
        return view('esbtp.enseignants.edit', compact(
            'teacher', 'profileData', 'departments', 'laboratories', 'matieres', 'classes',
            'titres_academiques', 'grades_academiques', 'types_contrat', 'statuts_emploi', 'availabilityData'
        ));
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, ESBTPTeacher $enseignant)
    {
        // DEBUG: Voir ce qui arrive dans la request
        \Log::info('🔧 DEBUG UPDATE METHOD');
        \Log::info('Request data keys: ' . implode(', ', array_keys($request->all())));
        \Log::info('Request full data: ' . json_encode($request->all()));
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $enseignant->user_id,
            'phone' => 'nullable|string|max:20',
            'titre_academique' => 'nullable|string|max:10',
            'specialization' => 'required|string|max:255',
            'department_id' => 'required|exists:esbtp_departments,id',
            'laboratory_id' => 'nullable|exists:esbtp_laboratories,id',
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
            $enseignant->user->update([
                'name' => $request->name,
                'email' => $request->email ?: null,
                'phone' => $request->phone,
            ]);

            // Mettre à jour le profil enseignant
            $enseignant->update([
                'title' => $request->titre_academique,
                'specialization' => $request->specialization,
                'department_id' => $request->department_id,
                'laboratory_id' => $request->laboratory_id,
                'bio' => $request->bio,
                'website' => $request->website,
                'status' => $request->status,
                'teaching_hours_due' => $request->teaching_hours_due,
                'updated_by' => auth()->id(),
            ]);
            
            // Traiter les données de disponibilité si présentes
            if ($request->has('availability')) {
                \Log::info('🔧 Traitement des disponibilités');
                \Log::info('Availability data: ' . json_encode($request->availability));
                
                // Supprimer toutes les disponibilités existantes
                $enseignant->availabilities()->delete();
                
                // Recréer les disponibilités à partir des données du formulaire
                foreach ($request->availability as $key => $status) {
                    if ($status !== 'unavailable') {
                        // Parser la clé (format: "day_hour")
                        [$dayIndex, $hour] = explode('_', $key);
                        $dayIndex = (int) $dayIndex;
                        $hour = (int) $hour;
                        
                        // Créer le créneau avec heure de fin
                        $startTime = sprintf('%02d:00', $hour);
                        $endTime = sprintf('%02d:00', $hour + 1);
                        
                        ESBTPTeacherAvailability::create([
                            'teacher_id' => $enseignant->id,
                            'day_of_week' => $dayIndex,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'availability_type' => $status
                        ]);
                        
                        \Log::info("Created availability: day=$dayIndex, $startTime-$endTime, status=$status");
                    }
                }
                
                \Log::info('🔧 Disponibilités mises à jour');
            } else {
                \Log::info('🔧 Aucune donnée de disponibilité reçue');
            }

            DB::commit();
            
            // DEBUG FRONT : Préparer un message de debug détaillé pour l'utilisateur
            $debugMessage = '✅ ENSEIGNANT MIS À JOUR AVEC SUCCÈS\n\n';
            $debugMessage .= '🕒 Timestamp: ' . now()->format('Y-m-d H:i:s') . '\n\n';
            
            if ($request->has('availability')) {
                $debugMessage .= '📊 DÉTAILS DES DISPONIBILITÉS:\n';
                $debugMessage .= '- Total des créneaux reçus: ' . count($request->availability) . '\n';
                
                $statusCounts = ['available' => 0, 'preferred' => 0, 'unavailable' => 0];
                $savedCount = 0;
                $samples = [];
                
                foreach ($request->availability as $key => $status) {
                    $statusCounts[$status]++;
                    if ($status !== 'unavailable') {
                        $savedCount++;
                        if (count($samples) < 3) {
                            [$day, $hour] = explode('_', $key);
                            $dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
                            $samples[] = $dayNames[$day] . ' ' . sprintf('%02d:00', $hour) . ' = ' . $status;
                        }
                    }
                }
                
                $debugMessage .= '- Disponibles: ' . $statusCounts['available'] . '\n';
                $debugMessage .= '- Préférés: ' . $statusCounts['preferred'] . '\n';
                $debugMessage .= '- Indisponibles: ' . $statusCounts['unavailable'] . '\n';
                $debugMessage .= '- Sauvegardés en DB: ' . $savedCount . ' créneaux\n\n';
                
                if (!empty($samples)) {
                    $debugMessage .= '📝 EXEMPLES SAUVEGARDÉS:\n';
                    foreach ($samples as $sample) {
                        $debugMessage .= '  • ' . $sample . '\n';
                    }
                    if ($savedCount > 3) {
                        $debugMessage .= '  ... et ' . ($savedCount - 3) . ' autres\n';
                    }
                    $debugMessage .= '\n';
                }
                
                $debugMessage .= '🔧 FORMAT DES CLÉS REÇUES: day_hour (ex: 0_8 = Lundi 08h)\n';
                $debugMessage .= '💾 FORMAT SAUVEGARDÉ: start_time/end_time par heure\n\n';
            } else {
                $debugMessage .= '⚠️ AUCUNE DONNÉE DE DISPONIBILITÉ REÇUE\n\n';
            }
            
            $debugMessage .= '📋 AUTRES DONNÉES TRAITÉES:\n';
            $debugMessage .= '- Nom: ' . $request->name . '\n';
            $debugMessage .= '- Email: ' . $request->email . '\n';
            $debugMessage .= '- Spécialisation: ' . $request->specialization . '\n';
            if ($request->filled('password')) {
                $debugMessage .= '- Mot de passe: MODIFIÉ\n';
            }
            
            $debugMessage .= '\n🎯 RÉSULTAT: Modification terminée, vérifiez la page SHOW';
            
            // Sauvegarder le message de debug en session
            session(['debug_message' => $debugMessage]);
            
            // Rediriger vers une page de debug dédiée
            return redirect()->route('esbtp.enseignants.debug-result', ['enseignant' => $enseignant->id]);
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Afficher le résultat debug de la modification
     */
    public function debugResult(ESBTPTeacher $enseignant)
    {
        $debugMessage = session('debug_message', 'Aucun message de debug disponible');
        session()->forget('debug_message');
        
        return view('esbtp.enseignants.debug-result', [
            'enseignant' => $enseignant,
            'debugMessage' => $debugMessage
        ]);
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
    public function toggleStatus(Request $request, ESBTPTeacher $teacher)
    {
        $newStatus = $teacher->status === 'active' ? 'inactive' : 'active';

        $teacher->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        // Si c'est une requête AJAX, retourner du JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'new_status' => $newStatus
            ]);
        }

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

    /**
     * Mettre à jour les disponibilités de l'enseignant via AJAX
     */
    public function updateAvailability(Request $request, ESBTPTeacher $enseignant)
    {
        error_log("🔧 DEBUG: updateAvailability called at " . date('H:i:s'));
        
        try {
            // DEBUG : Voir ce qui arrive
            \Log::info('🔧 DEBUG updateAvailability METHOD');
            \Log::info('Request changes: ' . json_encode($request->changes));
            error_log("🔧 Changes received: " . json_encode($request->changes));
            
            $request->validate([
                'changes' => 'required|array',
                'changes.*.day' => 'required|integer|min:0|max:6',
                'changes.*.startTime' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
                'changes.*.endTime' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
                'changes.*.status' => 'required|in:available,preferred,unavailable'
            ]);

            DB::beginTransaction();

            // CORRIGÉ : Utiliser des créneaux par heure comme la page EDIT
            $hours = range(8, 18); // 8h à 18h = 11 heures

            // Traiter chaque changement
            foreach ($request->changes as $change) {
                $day = $change['day'];
                $startTime = $change['startTime'];
                $endTime = $change['endTime'];
                $status = $change['status'];

                \Log::info("Processing change: day={$day}, {$startTime}-{$endTime}, status={$status}");

                // Supprimer toutes les entrées qui se chevauchent avec le créneau sélectionné
                $clickedStart = (int) substr($startTime, 0, 2);
                $clickedEnd = (int) substr($endTime, 0, 2);
                
                \Log::info("Checking for existing entries to delete for teacher {$enseignant->id}, day {$day}, time {$startTime}-{$endTime}");
                
                $existingAvailabilities = ESBTPTeacherAvailability::where([
                    'teacher_id' => $enseignant->id,
                    'day_of_week' => $day
                ])->get();
                
                \Log::info("Found " . $existingAvailabilities->count() . " existing entries for this day");
                
                foreach ($existingAvailabilities as $existing) {
                    // CORRIGÉ: Parser correctement les heures depuis les timestamps
                    if ($existing->start_time instanceof \Carbon\Carbon) {
                        $existingStart = $existing->start_time->hour;
                        $existingEnd = $existing->end_time->hour;
                    } else {
                        // Extraire l'heure depuis la position 11 du timestamp "YYYY-MM-DD HH:MM:SS"
                        $existingStart = (int) substr($existing->start_time, 11, 2);
                        $existingEnd = (int) substr($existing->end_time, 11, 2);
                    }
                    
                    \Log::info("Existing entry ID={$existing->id}: {$existing->start_time}-{$existing->end_time} parsed as hours {$existingStart}-{$existingEnd}");
                    
                    // CORRIGÉ: Vérifier s'il y a chevauchement exact ou partiel
                    // Deux créneaux se chevauchent si l'un commence avant que l'autre ne finisse
                    $hasOverlap = ($clickedStart < $existingEnd) && ($clickedEnd > $existingStart);
                    
                    // AJOUTÉ: aussi supprimer si c'est exactement le même créneau
                    $isExactMatch = ($clickedStart == $existingStart) && ($clickedEnd == $existingEnd);
                    
                    if ($hasOverlap || $isExactMatch) {
                        \Log::info("Deleting existing availability ID={$existing->id}: {$existing->start_time}-{$existing->end_time} (overlaps/matches with {$startTime}-{$endTime})");
                        $existing->delete();
                    } else {
                        \Log::info("Keeping existing availability ID={$existing->id}: no overlap with {$startTime}-{$endTime}");
                    }
                }

                // Ajouter la nouvelle entrée seulement si ce n'est pas "unavailable"
                if ($status !== 'unavailable') {
                    ESBTPTeacherAvailability::create([
                        'teacher_id' => $enseignant->id,
                        'day_of_week' => $day,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'availability_type' => $status
                    ]);
                    
                    \Log::info("Created availability: {$startTime}-{$endTime} = {$status}");
                } else {
                    \Log::info("Skipping creation for unavailable status");
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Disponibilités mises à jour avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset teacher password to default (Bonjour@2025) and force change on first login
     */
    public function resetPassword(Request $request, ESBTPTeacher $enseignant)
    {
        try {
            if (!$enseignant->user_id) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet enseignant n\'a pas de compte utilisateur.'
                    ], 400);
                }
                return redirect()
                    ->back()
                    ->with('error', 'Cet enseignant n\'a pas de compte utilisateur.');
            }

            $user = User::find($enseignant->user_id);
            if (!$user) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Compte utilisateur introuvable.'
                    ], 404);
                }
                return redirect()
                    ->back()
                    ->with('error', 'Compte utilisateur introuvable.');
            }

            // Mot de passe par défaut
            $defaultPassword = 'Bonjour@2025';

            // Mettre à jour le mot de passe et forcer le changement à la première connexion
            $user->password = Hash::make($defaultPassword);
            $user->must_change_password = true; // Force le changement de mot de passe
            $user->save();

            // Log de l'action
            \Log::info('🔑 Password reset for teacher to default', [
                'teacher_id' => $enseignant->id,
                'user_id' => $enseignant->user_id,
                'teacher_name' => $user->name,
                'reset_by' => auth()->user()->name,
                'timestamp' => now(),
                'must_change_password' => true
            ]);

            // Retourner JSON si requête AJAX, sinon redirect
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mot de passe réinitialisé avec succès!',
                    'password' => $defaultPassword
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Mot de passe réinitialisé à Bonjour@2025 avec succès! L\'enseignant devra changer son mot de passe à la première connexion.')
                ->with('new_password', $defaultPassword);

        } catch (\Exception $e) {
            \Log::error('❌ Password reset failed', [
                'teacher_id' => $enseignant->id,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
        }
    }
}
