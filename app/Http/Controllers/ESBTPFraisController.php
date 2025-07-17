<?php

namespace App\Http\Controllers;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisRule;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ESBTPFraisController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:frais.view', ['only' => ['index', 'show']]);
        $this->middleware('permission:frais.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:frais.edit', ['only' => ['edit', 'update', 'toggleActive']]);
        $this->middleware('permission:frais.delete', ['only' => ['destroy', 'resetDefaults']]);
        $this->middleware('permission:frais.configure', ['only' => ['configure', 'updateConfiguration']]);
    }

    /**
     * Interface principale de gestion des frais
     */
    public function index()
    {
        $categories = ESBTPFraisCategory::active()->ordered()->get();
        $stats = [
            'total_categories' => ESBTPFraisCategory::count(),
            'mandatory_categories' => ESBTPFraisCategory::mandatory()->count(),
            'optional_categories' => ESBTPFraisCategory::optional()->count(),
            'active_categories' => ESBTPFraisCategory::active()->count(),
        ];

        return view('esbtp.frais.index', compact('categories', 'stats'));
    }

    /**
     * Interface de configuration des frais par filière/niveau
     */
    public function configure(Request $request)
    {
        $categories = ESBTPFraisCategory::active()->ordered()->get();
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();

        // Créer la liste des classes (combinaisons filière + niveau)
        $classes = collect();
        foreach ($filieres as $filiere) {
            foreach ($niveaux as $niveau) {
                // Compter les étudiants inscrits pour cette classe
                $effectif = \DB::table('esbtp_inscriptions')
                    ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
                    ->where('esbtp_inscriptions.filiere_id', $filiere->id)
                    ->where('esbtp_inscriptions.niveau_id', $niveau->id)
                    ->where('esbtp_inscriptions.status', 'active')
                    ->count();

                // Récupérer les frais configurés pour cette classe
                $fraisConfigures = ESBTPFraisRule::with('fraisCategory')
                    ->where('filiere_id', $filiere->id)
                    ->where('niveau_id', $niveau->id)
                    ->get();

                $classes->push((object) [
                    'filiere' => $filiere,
                    'niveau' => $niveau,
                    'name' => $filiere->name . ' - ' . $niveau->name,
                    'effectif' => $effectif,
                    'frais_configures' => $fraisConfigures,
                    'obligatoires_configures' => $fraisConfigures->filter(function($rule) {
                        return $rule->fraisCategory->is_mandatory;
                    })->count(),
                    'optionnels_configures' => $fraisConfigures->filter(function($rule) {
                        return !$rule->fraisCategory->is_mandatory;
                    })->count(),
                    'total_obligatoires' => $categories->where('is_mandatory', true)->count(),
                    'total_optionnels' => $categories->where('is_mandatory', false)->count(),
                ]);
            }
        }

        // Filtres pour la configuration sélectionnée
        $filiereId = $request->get('filiere_id');
        $niveauId = $request->get('niveau_id');
        $rules = collect();
        
        if ($filiereId && $niveauId) {
            $rules = ESBTPFraisRule::with(['fraisCategory'])
                ->where('filiere_id', $filiereId)
                ->where('niveau_id', $niveauId)
                ->get();
        }

        return view('esbtp.frais.configure', compact(
            'categories',
            'filieres',
            'niveaux',
            'classes',
            'rules',
            'filiereId',
            'niveauId'
        ));
    }

    /**
     * Afficher les détails d'une catégorie de frais
     */
    public function show(ESBTPFraisCategory $frai)
    {
        $fraisCategory = $frai;
        $fraisCategory->load(['rules.filiere', 'rules.niveau', 'rules.anneeUniversitaire']);
        
        $stats = [
            'total_rules' => $fraisCategory->rules->count(),
            'active_rules' => $fraisCategory->rules->where('is_active', true)->count(),
            'total_paiements' => $fraisCategory->paiements->count(),
            'total_amount' => $fraisCategory->paiements->where('status', 'validated')->sum('montant'),
        ];

        return view('esbtp.frais.show', compact('fraisCategory', 'stats'));
    }

    /**
     * Formulaire de création d'une catégorie de frais
     */
    public function create()
    {
        return view('esbtp.frais.create');
    }

    /**
     * Enregistrer une nouvelle catégorie de frais
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_frais_categories,code',
            'description' => 'nullable|string',
            'is_mandatory' => 'required|boolean',
            'default_amount' => 'required|numeric|min:0',
            'payment_deadline_days' => 'required|integer|min:1|max:365',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $fraisCategory = ESBTPFraisCategory::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'is_mandatory' => $request->is_mandatory,
                'is_active' => true,
                'sort_order' => ESBTPFraisCategory::max('sort_order') + 1,
                'default_amount' => $request->default_amount,
                'payment_deadline_days' => $request->payment_deadline_days,
                'icon' => $request->icon,
                'color' => $request->color,
            ]);

            // Créer automatiquement un variant "Standard" pour cette catégorie
            \App\Models\ESBTPFraisVariant::create([
                'frais_category_id' => $fraisCategory->id,
                'name' => 'Standard',
                'description' => 'Option standard pour ' . $fraisCategory->name,
                'amount' => $request->default_amount,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]);

            DB::commit();

            return redirect()->route('esbtp.frais.index')
                ->with('success', 'Catégorie de frais créée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la catégorie de frais: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la catégorie de frais.')
                ->withInput();
        }
    }

    /**
     * Formulaire d'édition d'une catégorie de frais
     */
    public function edit(ESBTPFraisCategory $frai)
    {
        return view('esbtp.frais.edit', ['fraisCategory' => $frai]);
    }

    /**
     * Mettre à jour une catégorie de frais
     */
    public function update(Request $request, ESBTPFraisCategory $frai)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_frais_categories,code,' . $frai->id,
            'description' => 'nullable|string',
            'is_mandatory' => 'required|boolean',
            'default_amount' => 'required|numeric|min:0',
            'payment_deadline_days' => 'required|integer|min:1|max:365',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $frai->update([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'is_mandatory' => $request->is_mandatory,
                'default_amount' => $request->default_amount,
                'payment_deadline_days' => $request->payment_deadline_days,
                'icon' => $request->icon,
                'color' => $request->color,
            ]);

            DB::commit();

            return redirect()->route('esbtp.frais.index')
                ->with('success', 'Catégorie de frais mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la catégorie de frais: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la catégorie de frais.')
                ->withInput();
        }
    }

    /**
     * Supprimer une catégorie de frais
     */
    public function destroy(ESBTPFraisCategory $frai)
    {
        try {
            DB::beginTransaction();

            // Vérifier s'il y a des paiements associés
            if ($frai->paiements()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer cette catégorie car elle contient des paiements associés.');
            }

            // Supprimer les règles associées
            $frai->rules()->delete();

            // Supprimer la catégorie
            $frai->delete();

            DB::commit();

            return redirect()->route('esbtp.frais.index')
                ->with('success', 'Catégorie de frais supprimée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de la catégorie de frais: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la catégorie de frais.');
        }
    }

    /**
     * Configurer les règles pour une filière/niveau
     */
    public function updateConfiguration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'categories' => 'required|array',
            'categories.*.amount' => 'required|numeric|min:0',
            'categories.*.deadline_days' => 'required|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $filiereId = $request->filiere_id;
            $niveauId = $request->niveau_id;

            foreach ($request->categories as $categoryId => $categoryData) {
                // Vérifier si la catégorie existe
                $category = ESBTPFraisCategory::find($categoryId);
                if (!$category) {
                    continue;
                }

                // Créer ou mettre à jour la règle
                ESBTPFraisRule::updateOrCreate(
                    [
                        'frais_category_id' => $categoryId,
                        'filiere_id' => $filiereId,
                        'niveau_id' => $niveauId,
                        'annee_universitaire_id' => null, // Plus de dépendance à l'année
                    ],
                    [
                        'amount' => $categoryData['amount'],
                        'payment_deadline_days' => $categoryData['deadline_days'],
                        'installments_allowed' => false, // Par défaut
                        'max_installments' => 1,
                        'min_installment_amount' => null,
                        'late_fee_percentage' => 0,
                        'late_fee_amount' => 0,
                        'is_active' => true,
                        'effective_date' => now(),
                    ]
                );
            }

            DB::commit();

            return redirect()->route('esbtp.frais.configure')
                ->with('success', 'Configuration des frais mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la configuration des frais: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la configuration des frais.')
                ->withInput();
        }
    }

    /**
     * Activer/désactiver une catégorie de frais
     */
    public function toggleActive(ESBTPFraisCategory $fraisCategory)
    {
        try {
            $fraisCategory->update(['is_active' => !$fraisCategory->is_active]);
            
            $status = $fraisCategory->is_active ? 'activée' : 'désactivée';
            return redirect()->back()
                ->with('success', "Catégorie de frais {$status} avec succès.");

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut de la catégorie: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors du changement de statut.');
        }
    }

    /**
     * Réinitialiser les catégories par défaut
     */
    public function resetDefaults()
    {
        try {
            DB::beginTransaction();

            // Supprimer toutes les catégories existantes
            ESBTPFraisCategory::truncate();

            // Recréer les catégories par défaut
            $seeder = new \Database\Seeders\ESBTPFraisCategorySeeder();
            $seeder->run();

            DB::commit();

            return redirect()->route('esbtp.frais.index')
                ->with('success', 'Catégories de frais réinitialisées avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la réinitialisation des catégories: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la réinitialisation des catégories.');
        }
    }

    /**
     * API: Détails des frais pour une classe
     */
    public function getClassDetails($filiereId, $niveauId)
    {
        try {
            $categories = ESBTPFraisCategory::active()->ordered()->get();
            $rules = ESBTPFraisRule::with(['fraisCategory'])
                ->where('filiere_id', $filiereId)
                ->where('niveau_id', $niveauId)
                ->get();

            $result = [];
            foreach ($categories as $category) {
                $rule = $rules->where('frais_category_id', $category->id)->first();
                $variants = $category->activeVariants()->get();
                
                $result[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'description' => $category->description,
                    'is_mandatory' => $category->is_mandatory,
                    'icon' => $category->icon,
                    'amount' => $rule ? $rule->amount : $category->default_amount,
                    'variants' => $variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'description' => $variant->description,
                            'amount' => $variant->amount,
                            'is_default' => $variant->is_default,
                        ];
                    })
                ];
            }

            return response()->json(['categories' => $result]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails de classe: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors du chargement des détails'], 500);
        }
    }

    /**
     * API: Variants d'une catégorie
     */
    public function getCategoryVariants($categoryId)
    {
        try {
            $category = ESBTPFraisCategory::findOrFail($categoryId);
            $variants = $category->variants()->ordered()->get();

            return response()->json([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'variants' => $variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'description' => $variant->description,
                        'amount' => $variant->amount,
                        'is_default' => $variant->is_default,
                        'is_active' => $variant->is_active,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des variants: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors du chargement des variants'], 500);
        }
    }

    /**
     * API: Tous les variants
     */
    public function getAllVariants()
    {
        try {
            $categories = ESBTPFraisCategory::active()->ordered()->with(['variants' => function ($query) {
                $query->active()->ordered();
            }])->get();

            $result = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'is_mandatory' => $category->is_mandatory,
                    'icon' => $category->icon,
                    'variants' => $category->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'description' => $variant->description,
                            'amount' => $variant->amount,
                            'is_default' => $variant->is_default,
                            'is_active' => $variant->is_active,
                        ];
                    })
                ];
            });

            return response()->json(['categories' => $result]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de tous les variants: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors du chargement des variants'], 500);
        }
    }

    /**
     * API: Créer un variant
     */
    public function storeVariant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:esbtp_frais_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Données invalides', 'details' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            // Si ce variant est défini comme défaut, désactiver les autres variants par défaut
            if ($request->is_default) {
                ESBTPFraisVariant::where('frais_category_id', $request->category_id)
                    ->update(['is_default' => false]);
            }

            $variant = ESBTPFraisVariant::create([
                'frais_category_id' => $request->category_id,
                'name' => $request->name,
                'description' => $request->description,
                'amount' => $request->amount,
                'is_default' => $request->is_default ?? false,
                'is_active' => true,
                'sort_order' => ESBTPFraisVariant::where('frais_category_id', $request->category_id)->max('sort_order') + 1,
            ]);

            DB::commit();

            return response()->json(['success' => true, 'variant' => $variant]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du variant: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la création du variant'], 500);
        }
    }

    /**
     * API: Supprimer un variant
     */
    public function destroyVariant($variantId)
    {
        try {
            $variant = ESBTPFraisVariant::findOrFail($variantId);
            
            // Vérifier s'il y a des souscriptions liées
            if ($variant->subscriptions()->exists()) {
                return response()->json(['error' => 'Impossible de supprimer un variant qui a des souscriptions'], 400);
            }

            $variant->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du variant: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la suppression du variant'], 500);
        }
    }

    /**
     * API: Obtenir les étudiants en retard de paiement pour une catégorie
     */
    public function getStudentsWithOverduePayments($categoryId)
    {
        try {
            $category = ESBTPFraisCategory::findOrFail($categoryId);
            
            // Récupérer les étudiants avec des frais impayés pour cette catégorie
            $studentsWithOverdue = DB::table('esbtp_inscriptions')
                ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
                ->leftJoin('esbtp_paiements', function($join) use ($categoryId) {
                    $join->on('esbtp_paiements.inscription_id', '=', 'esbtp_inscriptions.id')
                         ->where('esbtp_paiements.frais_category_id', '=', $categoryId)
                         ->where('esbtp_paiements.status', '=', 'validé');
                })
                ->leftJoin('esbtp_frais_rules', function($join) use ($categoryId) {
                    $join->on('esbtp_frais_rules.filiere_id', '=', 'esbtp_inscriptions.filiere_id')
                         ->on('esbtp_frais_rules.niveau_id', '=', 'esbtp_inscriptions.niveau_id')
                         ->where('esbtp_frais_rules.frais_category_id', '=', $categoryId);
                })
                ->where('esbtp_inscriptions.status', 'active')
                ->whereNull('esbtp_paiements.id') // Pas de paiement validé
                ->whereNotNull('esbtp_frais_rules.id') // Frais configuré pour cette classe
                ->where('esbtp_frais_rules.payment_deadline_days', '<', 
                    DB::raw('DATEDIFF(NOW(), esbtp_inscriptions.created_at)'))
                ->select([
                    'esbtp_etudiants.*',
                    'esbtp_inscriptions.id as inscription_id',
                    'esbtp_frais_rules.amount',
                    'esbtp_frais_rules.payment_deadline_days',
                    DB::raw('DATEDIFF(NOW(), esbtp_inscriptions.created_at) as jours_retard')
                ])
                ->get();

            return response()->json([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'students' => $studentsWithOverdue,
                'count' => $studentsWithOverdue->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des étudiants en retard: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors du chargement des données'], 500);
        }
    }

    /**
     * API: Planifier des relances automatiques pour une catégorie de frais
     */
    public function scheduleAutomaticReminders(Request $request, $categoryId)
    {
        $validator = Validator::make($request->all(), [
            'niveau' => 'required|integer|min:1|max:3',
            'type' => 'required|in:email,sms,courrier,appel',
            'delai_jours' => 'required|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Données invalides', 'details' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            $category = ESBTPFraisCategory::findOrFail($categoryId);
            
            // Récupérer les étudiants en retard
            $studentsWithOverdue = $this->getStudentsWithOverduePayments($categoryId)->getData();
            
            if (empty($studentsWithOverdue->students)) {
                return response()->json(['message' => 'Aucun étudiant en retard trouvé'], 200);
            }

            $relancesCreated = 0;
            foreach ($studentsWithOverdue->students as $student) {
                // Vérifier s'il n'y a pas déjà une relance récente
                $existingRelance = \App\Models\ESBTPRelance::where('etudiant_id', $student->id)
                    ->where('niveau', $request->niveau)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->first();

                if (!$existingRelance) {
                    \App\Models\ESBTPRelance::create([
                        'etudiant_id' => $student->id,
                        'type' => $request->type,
                        'niveau' => $request->niveau,
                        'contenu_message' => $this->generateReminderMessage($category, $student, $request->niveau),
                        'date_envoi' => now()->addDays($request->delai_jours),
                        'statut' => 'planifiee',
                    ]);
                    $relancesCreated++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$relancesCreated} relance(s) planifiée(s) avec succès",
                'relances_created' => $relancesCreated
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la planification des relances: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la planification des relances'], 500);
        }
    }

    /**
     * Générer le message de relance personnalisé
     */
    private function generateReminderMessage($category, $student, $niveau)
    {
        $messages = [
            1 => "Cher(e) {$student->prenom} {$student->nom}, nous vous rappelons que les frais de {$category->name} sont en attente de paiement. Merci de régulariser votre situation dans les plus brefs délais.",
            2 => "Cher(e) {$student->prenom} {$student->nom}, votre paiement des frais de {$category->name} est toujours en attente. Merci de contacter l'administration pour régulariser votre situation.",
            3 => "Cher(e) {$student->prenom} {$student->nom}, ceci est un dernier rappel concernant le paiement des frais de {$category->name}. Votre inscription pourrait être suspendue en cas de non-paiement."
        ];

        return $messages[$niveau] ?? $messages[1];
    }
}