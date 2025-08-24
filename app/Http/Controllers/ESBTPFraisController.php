<?php

namespace App\Http\Controllers;

use App\Models\ESBTPFraisCategory;
// use App\Models\ESBTPFraisRule; // Supprimé - remplacé par ESBTPFraisConfiguration
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
// use App\Models\ESBTPFraisVariant; // Supprimé - remplacé par ESBTPFraisOption
use App\Models\ESBTPOptionAssignment;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\FraisCalculationService;
use App\Services\FraisCacheService;
use App\Services\FraisManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ESBTPFraisController extends Controller
{
    protected $fraisCalculationService;
    protected $fraisCacheService;
    protected $fraisManagementService;

    public function __construct(
        FraisCalculationService $fraisCalculationService,
        FraisCacheService $fraisCacheService,
        FraisManagementService $fraisManagementService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:frais.view', ['only' => ['index', 'show']]);
        $this->middleware('permission:frais.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:frais.edit', ['only' => ['edit', 'update', 'toggleActive']]);
        $this->middleware('permission:frais.delete', ['only' => ['destroy', 'resetDefaults']]);
        $this->middleware('permission:frais.configure', ['only' => ['configure', 'updateConfiguration']]);
        
        $this->fraisCalculationService = $fraisCalculationService;
        $this->fraisCacheService = $fraisCacheService;
        $this->fraisManagementService = $fraisManagementService;
    }

    /**
     * Interface principale de gestion des frais
     */
    public function index()
    {
        // Utiliser le cache pour les catégories fréquemment accédées
        $categories = $this->fraisCacheService->getCategories();
        
        // Ajouter les statuts de configuration pour chaque catégorie
        $categories = $categories->map(function($category) {
            $status = $this->fraisManagementService->getConfigurationStatus($category);
            $category->configuration_status = $status;
            return $category;
        });
        
        // Grouper les catégories par type
        $categoriesByType = [
            'academic' => $categories->where('category_type', 'academic'),
            'service' => $categories->where('category_type', 'service'),
            'administrative' => $categories->where('category_type', 'administrative'),
        ];
        
        $stats = [
            'total_categories' => ESBTPFraisCategory::count(),
            'academic_categories' => ESBTPFraisCategory::academic()->count(),
            'service_categories' => ESBTPFraisCategory::service()->count(),
            'administrative_categories' => ESBTPFraisCategory::administrative()->count(),
            'mandatory_categories' => ESBTPFraisCategory::mandatory()->count(),
            'optional_categories' => ESBTPFraisCategory::optional()->count(),
            'active_categories' => ESBTPFraisCategory::active()->count(),
        ];

        return view('esbtp.frais.index', compact('categories', 'categoriesByType', 'stats'));
    }

    /**
     * Interface de configuration des frais par filière/niveau
     */
    public function configure(Request $request)
    {
        $categories = $this->fraisCacheService->getCategories();
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

                // Récupérer les configurations pour cette classe
                $configurations = ESBTPFraisConfiguration::with('fraisCategory')
                    ->where('filiere_id', $filiere->id)
                    ->where('niveau_id', $niveau->id)
                    ->active()
                    ->valid()
                    ->get();

                // Compter les frais optionnels assignés à cette classe via ESBTPOptionAssignment
                $optionnelsAssignes = ESBTPOptionAssignment::where(function($query) use ($filiere, $niveau) {
                    $query->where('filiere_id', $filiere->id)
                          ->where('niveau_id', $niveau->id);
                })->orWhere(function($query) use ($filiere, $niveau) {
                    // Assignations "tous les étudiants" (filiere_id ET niveau_id null)
                    $query->whereNull('filiere_id')
                          ->whereNull('niveau_id');
                })->orWhere(function($query) use ($filiere) {
                    // Assignations pour toute une filière (niveau_id null mais filiere_id défini)
                    $query->where('filiere_id', $filiere->id)
                          ->whereNull('niveau_id');
                })->orWhere(function($query) use ($niveau) {
                    // Assignations pour tout un niveau (filiere_id null mais niveau_id défini)
                    $query->whereNull('filiere_id')
                          ->where('niveau_id', $niveau->id);
                })->count();

                $classes->push((object) [
                    'filiere' => $filiere,
                    'niveau' => $niveau,
                    'name' => $filiere->name . ' - ' . $niveau->name,
                    'effectif' => $effectif,
                    'configurations' => $configurations,
                    'obligatoires_configures' => $configurations->filter(function($config) {
                        return $config->fraisCategory->is_mandatory;
                    })->count(),
                    'optionnels_configures' => $optionnelsAssignes,
                    'total_obligatoires' => $categories->where('is_mandatory', true)->count(),
                    'total_optionnels' => $categories->where('is_mandatory', false)->count(),
                ]);
            }
        }

        // Filtres pour la configuration sélectionnée
        $filiereId = $request->get('filiere_id');
        $niveauId = $request->get('niveau_id');
        $configurations = collect();
        
        if ($filiereId && $niveauId) {
            $configurations = ESBTPFraisConfiguration::with(['fraisCategory', 'options'])
                ->where('filiere_id', $filiereId)
                ->where('niveau_id', $niveauId)
                ->active()
                ->valid()
                ->get();
        }

        return view('esbtp.frais.configure', compact(
            'categories',
            'filieres',
            'niveaux',
            'classes',
            'configurations',
            'filiereId',
            'niveauId'
        ));
    }

    /**
     * Configuration des frais optionnels (transport, cantine, etc.)
     */
    public function optionalConfig(Request $request)
    {
        // Récupérer les catégories optionnelles avec leurs options ET les assignations
        $optionalCategories = ESBTPFraisCategory::with(['options.assignments.filiere', 'options.assignments.niveau'])
            ->where('is_mandatory', false)
            ->active()
            ->get();

        // Statistiques - chercher par nom plutôt que par type  
        $transportCategory = $optionalCategories->filter(function($cat) {
            return stripos($cat->name, 'transport') !== false;
        })->first();
        
        $cantineCategory = $optionalCategories->filter(function($cat) {
            return stripos($cat->name, 'cantine') !== false;
        })->first();
        
        $stats = [
            'total_optional' => $optionalCategories->count(),
            'transport_stops' => $transportCategory ? $transportCategory->options->count() : 0,
            'cantine_menus' => $cantineCategory ? $cantineCategory->options->count() : 0,
        ];

        return view('esbtp.frais.optional-config', compact('optionalCategories', 'stats'));
    }

    /**
     * Ajouter une nouvelle option/variant pour une catégorie
     */
    public function storeVariant(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:esbtp_frais_categories,id',
                'name' => 'required|string|max:255',
                'additional_amount' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:500',
            ]);

            // Vérifier que la catégorie existe
            $category = ESBTPFraisCategory::findOrFail($request->category_id);
            
            // Récupérer une configuration existante pour cette catégorie ou en créer une
            $configuration = ESBTPFraisConfiguration::where('frais_category_id', $request->category_id)
                ->first();
            
            if (!$configuration) {
                // Créer une configuration de base si elle n'existe pas
                $configuration = ESBTPFraisConfiguration::create([
                    'frais_category_id' => $request->category_id,
                    'filiere_id' => ESBTPFiliere::where('is_active', true)->first()->id ?? 1,
                    'niveau_id' => ESBTPNiveauEtude::where('is_active', true)->first()->id ?? 1,
                    'amount' => 0,
                    'deadline_days' => 30,
                    'is_active' => true,
                    'is_valid' => true,
                ]);
            }

            // Créer l'option
            ESBTPFraisOption::create([
                'configuration_id' => $configuration->id,
                'name' => $request->name,
                'description' => $request->description,
                'additional_amount' => $request->additional_amount,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => ESBTPFraisOption::where('configuration_id', $configuration->id)->max('sort_order') + 1,
            ]);

            return redirect()->route('esbtp.frais.optional-config')
                ->with('success', 'Option ajoutée avec succès !');

        } catch (\Exception $e) {
            return redirect()->route('esbtp.frais.optional-config')
                ->with('error', 'Erreur lors de l\'ajout de l\'option : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer une option/variant
     */
    public function destroyVariant(ESBTPFraisOption $variant)
    {
        try {
            $variant->delete();
            
            return redirect()->route('esbtp.frais.optional-config')
                ->with('success', 'Option supprimée avec succès !');
                
        } catch (\Exception $e) {
            return redirect()->route('esbtp.frais.optional-config')
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Sauvegarder l'assignation des frais optionnels aux filières/niveaux
     */
    public function saveAssignment(Request $request)
    {
        try {
            $categoryId = $request->category_id;
            $assignmentType = $request->assignment_type;
            
            // Récupérer la catégorie
            $category = ESBTPFraisCategory::findOrFail($categoryId);
            
            if ($assignmentType === 'all') {
                // Créer des configurations pour toutes les classes existantes
                $filieres = ESBTPFiliere::where('is_active', true)->get();
                $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
                
                foreach ($filieres as $filiere) {
                    foreach ($niveaux as $niveau) {
                        ESBTPFraisConfiguration::updateOrCreate(
                            [
                                'frais_category_id' => $categoryId,
                                'filiere_id' => $filiere->id,
                                'niveau_id' => $niveau->id,
                            ],
                            [
                                'amount' => 0, // Montant de base, sera défini via les options
                                'deadline_days' => 30,
                                'is_active' => true,
                                'is_valid' => true,
                            ]
                        );
                    }
                }
            } else {
                $filieres = $request->filieres ?? [];
                $niveaux = $request->niveaux ?? [];
                
                if ($assignmentType === 'filiere') {
                    // Assigner à toutes les combinaisons des filières sélectionnées avec tous les niveaux
                    $allNiveaux = ESBTPNiveauEtude::where('is_active', true)->get();
                    foreach ($filieres as $filiereId) {
                        foreach ($allNiveaux as $niveau) {
                            ESBTPFraisConfiguration::updateOrCreate(
                                [
                                    'frais_category_id' => $categoryId,
                                    'filiere_id' => $filiereId,
                                    'niveau_id' => $niveau->id,
                                ],
                                [
                                    'amount' => 0,
                                    'deadline_days' => 30,
                                    'is_active' => true,
                                    'is_valid' => true,
                                ]
                            );
                        }
                    }
                } elseif ($assignmentType === 'niveau') {
                    // Assigner à toutes les combinaisons des niveaux sélectionnés avec toutes les filières
                    $allFilieres = ESBTPFiliere::where('is_active', true)->get();
                    foreach ($niveaux as $niveauId) {
                        foreach ($allFilieres as $filiere) {
                            ESBTPFraisConfiguration::updateOrCreate(
                                [
                                    'frais_category_id' => $categoryId,
                                    'filiere_id' => $filiere->id,
                                    'niveau_id' => $niveauId,
                                ],
                                [
                                    'amount' => 0,
                                    'deadline_days' => 30,
                                    'is_active' => true,
                                    'is_valid' => true,
                                ]
                            );
                        }
                    }
                } elseif ($assignmentType === 'classe') {
                    // Assigner aux combinaisons spécifiques filière + niveau
                    foreach ($filieres as $filiereId) {
                        foreach ($niveaux as $niveauId) {
                            ESBTPFraisConfiguration::updateOrCreate(
                                [
                                    'frais_category_id' => $categoryId,
                                    'filiere_id' => $filiereId,
                                    'niveau_id' => $niveauId,
                                ],
                                [
                                    'amount' => 0,
                                    'deadline_days' => 30,
                                    'is_active' => true,
                                    'is_valid' => true,
                                ]
                            );
                        }
                    }
                }
            }
            
            return response()->json(['success' => true, 'message' => 'Assignation sauvegardée avec succès']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Afficher les détails d'une catégorie de frais
     */
    public function show(ESBTPFraisCategory $frai)
    {
        $fraisCategory = $frai;
        
        // Utiliser le nouveau service pour gérer les différents types de frais
        if ($fraisCategory->is_mandatory) {
            // Pour les frais obligatoires (par classe)
            $configurations = ESBTPFraisConfiguration::with(['filiere', 'niveau', 'options'])
                ->where('frais_category_id', $fraisCategory->id)
                ->active()
                ->valid()
                ->get();
            
            // Récupérer les options par classe
            $options = collect();
            if ($configurations->count() > 0) {
                $configurationIds = $configurations->pluck('id');
                $options = ESBTPFraisOption::classBased()
                    ->whereIn('configuration_id', $configurationIds)
                    ->active()
                    ->orderBy('sort_order')
                    ->get();
            }
            
            // Calculer les statistiques pour frais obligatoires
            $stats = [
                'total_configurations' => $configurations->count(),
                'active_configurations' => $configurations->where('is_active', true)->count(),
                'total_options' => $options->count(),
                'total_classes' => ESBTPFiliere::active()->count() * ESBTPNiveauEtude::active()->count(),
                'configured_classes' => $configurations->count(),
                'coverage_percentage' => ESBTPFiliere::active()->count() * ESBTPNiveauEtude::active()->count() > 0 
                    ? round(($configurations->count() / (ESBTPFiliere::active()->count() * ESBTPNiveauEtude::active()->count())) * 100, 1)
                    : 0
            ];
        } else {
            // Pour les services optionnels - utiliser EXACTEMENT la même logique qu'optional-config
            $configurations = collect(); // Pas de configurations par classe pour les services globaux
            
            // Charger la catégorie avec ses options comme dans optional-config
            $fraisCategoryWithOptions = ESBTPFraisCategory::with(['options.assignments.filiere', 'options.assignments.niveau'])
                ->where('id', $fraisCategory->id)
                ->first();
            
            $options = $fraisCategoryWithOptions ? $fraisCategoryWithOptions->options : collect();
            
            // Calculer les statistiques pour services globaux
            $status = $this->fraisManagementService->getConfigurationStatus($fraisCategory);
            $stats = [
                'total_configurations' => 0, // Pas de configurations par classe
                'active_configurations' => 0,
                'total_options' => $options->count(),
                'active_options' => $options->where('is_active', true)->count(),
                'default_options' => $options->where('is_default', true)->count(),
                'coverage_percentage' => 100, // Services globaux couvrent 100% (tous les étudiants)
                'configuration_status' => $status
            ];
        }

        return view('esbtp.frais.show', compact('fraisCategory', 'configurations', 'options', 'stats'));
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

            // Créer automatiquement une option "Standard" pour cette catégorie
            ESBTPFraisOption::create([
                'configuration_id' => null, // Option globale
                'name' => 'Standard',
                'description' => 'Option standard pour ' . $fraisCategory->name,
                'additional_amount' => 0,
                'is_default' => true,
                'is_active' => true,
                'available_from' => now(),
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

            // Supprimer les configurations associées
            $frai->configurations()->delete();

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
     * Configurer les frais pour une filière/niveau avec le nouveau système
     */
    public function updateConfiguration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'categories' => 'required|array',
            'categories.*.amount' => 'required|numeric|min:0',
            'categories.*.deadline_days' => 'required|integer|min:1|max:365',
            'categories.*.installments_allowed' => 'boolean',
            'categories.*.max_installments' => 'nullable|integer|min:1|max:12',
            'categories.*.early_payment_discount' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 400);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $filiereId = $request->filiere_id;
            $niveauId = $request->niveau_id;
            $userId = auth()->id();

            foreach ($request->categories as $categoryId => $categoryData) {
                // Vérifier si la catégorie existe
                $category = ESBTPFraisCategory::find($categoryId);
                if (!$category) {
                    continue;
                }

                // Créer ou mettre à jour la configuration unifiée
                ESBTPFraisConfiguration::updateOrCreate(
                    [
                        'frais_category_id' => $categoryId,
                        'filiere_id' => $filiereId,
                        'niveau_id' => $niveauId,
                        'annee_universitaire_id' => null,
                    ],
                    [
                        'amount' => $categoryData['amount'],
                        'payment_deadline_days' => $categoryData['deadline_days'],
                        'installments_allowed' => $categoryData['installments_allowed'] ?? false,
                        'max_installments' => $categoryData['max_installments'] ?? 1,
                        'early_payment_discount' => $categoryData['early_payment_discount'] ?? 0,
                        'is_active' => true,
                        'effective_date' => now(),
                        'created_by' => $userId,
                    ]
                );
            }

            // Invalider le cache après mise à jour
            $this->fraisCacheService->invalidateConfigurationCache($filiereId, $niveauId);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Configuration des frais mise à jour avec succès.'
                ]);
            }

            return redirect()->route('esbtp.frais.configure')
                ->with('success', 'Configuration des frais mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la configuration des frais: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la configuration des frais.'
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la configuration des frais.')
                ->withInput();
        }
    }

    /**
     * Activer/désactiver une catégorie de frais
     */
    public function toggleActive(ESBTPFraisCategory $frai)
    {
        try {
            $frai->update(['is_active' => !$frai->is_active]);
            
            $status = $frai->is_active ? 'activée' : 'désactivée';
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
     * API: Obtenir les catégories de frais pour une classe (pour le modal) - Version mise à jour
     */
    public function getCategories(Request $request)
    {
        try {
            $filiereId = $request->get('filiere_id');
            $niveauId = $request->get('niveau_id');
            $type = $request->get('type', 'all'); // mandatory, optional, ou all

            if (!$filiereId || !$niveauId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètres manquants: filiere_id et niveau_id requis'
                ], 400);
            }

            $categories = $this->fraisCacheService->getCategories();
            $configurations = $this->fraisCacheService->getConfigurations($filiereId, $niveauId);

            // Filtrer selon le type demandé
            if ($type === 'mandatory') {
                $categories = $categories->where('is_mandatory', true);
            } elseif ($type === 'optional') {
                $categories = $categories->where('is_mandatory', false);
            }

            // Générer le HTML selon le type
            if ($type === 'mandatory') {
                $html = view('esbtp.frais.partials.mandatory-categories', compact('categories', 'configurations', 'filiereId', 'niveauId'))->render();
            } elseif ($type === 'optional') {
                $html = view('esbtp.frais.partials.optional-categories', compact('categories', 'configurations', 'filiereId', 'niveauId'))->render();
            } else {
                $html = view('esbtp.frais.partials.categories-grid', compact('categories', 'configurations'))->render();
            }
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'count' => $categories->count(),
                'type' => $type
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des catégories: ' . $e->getMessage(), [
                'filiere_id' => $filiereId,
                'niveau_id' => $niveauId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des catégories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Détails des frais pour une classe avec le nouveau système
     */
    public function getClassDetails($filiereId, $niveauId)
    {
        try {
            $categories = $this->fraisCacheService->getCategories();
            $configurations = $this->fraisCacheService->getConfigurations($filiereId, $niveauId);

            $result = [];
            foreach ($categories as $category) {
                $configuration = $configurations->where('frais_category_id', $category->id)->first();
                $options = $configuration ? $configuration->options()->active()->get() : collect();
                
                $result[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'description' => $category->description,
                    'is_mandatory' => $category->is_mandatory,
                    'icon' => $category->icon,
                    'amount' => $configuration ? $configuration->amount : $category->default_amount,
                    'payment_deadline_days' => $configuration ? $configuration->payment_deadline_days : $category->payment_deadline_days,
                    'installments_allowed' => $configuration ? $configuration->allowsInstallments() : false,
                    'options' => $options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'name' => $option->name,
                            'description' => $option->description,
                            'additional_amount' => $option->additional_amount,
                            'is_default' => $option->is_default,
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
     * API: Créer une option de frais
     */
    public function storeOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'configuration_id' => 'nullable|exists:esbtp_frais_configurations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'additional_amount' => 'required|numeric|min:0',
            'is_default' => 'boolean',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after:available_from',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Données invalides', 'details' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            // Si cette option est définie comme défaut, désactiver les autres options par défaut
            if ($request->is_default && $request->configuration_id) {
                ESBTPFraisOption::where('configuration_id', $request->configuration_id)
                    ->update(['is_default' => false]);
            }

            $option = ESBTPFraisOption::create([
                'configuration_id' => $request->configuration_id,
                'name' => $request->name,
                'description' => $request->description,
                'additional_amount' => $request->additional_amount,
                'is_default' => $request->is_default ?? false,
                'is_active' => true,
                'available_from' => $request->available_from ?? now(),
                'available_to' => $request->available_to,
                'sort_order' => ESBTPFraisOption::where('configuration_id', $request->configuration_id)->max('sort_order') + 1,
            ]);

            // Invalider le cache
            $this->fraisCacheService->invalidateOptionsCache();

            DB::commit();

            return response()->json(['success' => true, 'option' => $option]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de l\'option: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la création de l\'option'], 500);
        }
    }

    /**
     * API: Supprimer une option de frais
     */
    public function destroyOption($optionId)
    {
        try {
            $option = ESBTPFraisOption::findOrFail($optionId);
            
            // Vérifier s'il y a des souscriptions liées
            if ($option->subscriptions()->exists()) {
                return response()->json(['error' => 'Impossible de supprimer une option qui a des souscriptions'], 400);
            }

            $option->delete();

            // Invalider le cache
            $this->fraisCacheService->invalidateOptionsCache();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'option: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la suppression de l\'option'], 500);
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

    /**
     * API: Récupérer les catégories de frais pour une inscription avec le nouveau système
     */
    public function getCategoriesForApi(Request $request)
    {
        try {
            $inscriptionId = $request->get('inscription_id');
            
            if (!$inscriptionId) {
                return response()->json(['error' => 'ID de l\'inscription requis'], 400);
            }

            // Récupérer l'inscription avec ses relations
            $inscription = \App\Models\ESBTPInscription::with(['filiere', 'niveauEtude', 'anneeUniversitaire'])
                ->find($inscriptionId);
            
            if (!$inscription) {
                return response()->json(['error' => 'Inscription non trouvée'], 404);
            }

            // Utiliser le cache pour les catégories
            $categories = $this->fraisCacheService->getCategories()
                ->map(function ($category) use ($inscription) {
                    // Chercher une configuration pour cette catégorie et cette inscription
                    $configuration = ESBTPFraisConfiguration::getApplicableConfiguration(
                        $category->id,
                        $inscription->filiere_id,
                        $inscription->niveau_id,
                        $inscription->annee_universitaire_id
                    );

                    // Calculer le montant avec le service
                    $calculationResult = $this->fraisCalculationService->calculateFeeForInscription(
                        $inscription,
                        $category
                    );

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description ?? 'Description non définie',
                        'type' => $category->category_type ?? 'academic',
                        'montant' => $calculationResult['final_amount'],
                        'base_amount' => $calculationResult['base_amount'],
                        'discounts' => $calculationResult['discounts'],
                        'is_mandatory' => $category->is_mandatory ?? true,
                        'installments_allowed' => $configuration ? $configuration->allowsInstallments() : false,
                        'max_installments' => $configuration ? $configuration->max_installments : 1,
                        'payment_deadline_days' => $configuration ? $configuration->payment_deadline_days : $category->payment_deadline_days,
                        'configured' => $configuration ? true : false,
                        'options' => $configuration ? $configuration->options()->active()->get()->map(function($option) {
                            return [
                                'id' => $option->id,
                                'name' => $option->name,
                                'description' => $option->description,
                                'additional_amount' => $option->additional_amount,
                                'is_default' => $option->is_default
                            ];
                        }) : []
                    ];
                });

            return response()->json($categories);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des catégories de frais: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur interne du serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer le montant d'une catégorie pour une inscription spécifique
     * Utilise le nouveau service de calcul
     */
    private function calculateAmountForInscription($category, $inscription, $options = [])
    {
        try {
            $result = $this->fraisCalculationService->calculateFeeForInscription(
                $inscription, 
                $category, 
                null, 
                $options
            );
            
            return $result['final_amount'] ?? $category->default_amount;
        } catch (\Exception $e) {
            Log::warning('Erreur lors du calcul des frais, utilisation du montant par défaut: ' . $e->getMessage());
            return $category->default_amount ?? 0;
        }
    }

    /**
     * Récupérer les assignations d'une option
     */
    public function getOptionAssignments($optionId)
    {
        try {
            $assignments = ESBTPOptionAssignment::with(['filiere', 'niveau'])
                ->forOption($optionId)
                ->active()
                ->get();

            $formattedAssignments = $assignments->map(function($assignment) {
                return [
                    'id' => $assignment->id,
                    'assignment_type' => $assignment->assignment_type,
                    'filiere_id' => $assignment->filiere_id,
                    'niveau_id' => $assignment->niveau_id,
                    'filiere_name' => $assignment->filiere ? $assignment->filiere->name : null,
                    'niveau_name' => $assignment->niveau ? $assignment->niveau->name : null,
                    'display_label' => $assignment->display_label
                ];
            });

            return response()->json([
                'success' => true,
                'assignments' => $formattedAssignments
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des assignations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des assignations'
            ], 500);
        }
    }

    /**
     * Sauvegarder les assignations d'une option
     */
    public function saveOptionAssignments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'option_id' => 'required|exists:esbtp_frais_options,id',
            'assignment_type' => 'required|in:all,filiere,niveau,classe',
            'filieres' => 'nullable|array',
            'filieres.*' => 'exists:esbtp_filieres,id',
            'niveaux' => 'nullable|array', 
            'niveaux.*' => 'exists:esbtp_niveau_etudes,id'
        ]);

        if ($validator->fails()) {
            Log::error('Validation des assignations échouée', [
                'request_data' => $request->all(),
                'errors' => $validator->errors()->toArray()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Données invalides: ' . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $optionId = $request->option_id;
            $assignmentType = $request->assignment_type;
            $filieres = $request->filieres ?? [];
            $niveaux = $request->niveaux ?? [];

            // Utiliser la méthode statique du modèle pour mettre à jour les assignations
            $assignments = ESBTPOptionAssignment::updateAssignmentsForOption(
                $optionId, 
                $assignmentType, 
                $filieres, 
                $niveaux
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignations sauvegardées avec succès',
                'assignments_count' => is_array($assignments) ? count($assignments) : 1
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la sauvegarde des assignations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde'
            ], 500);
        }
    }

    /**
     * Supprimer une assignation spécifique
     */
    public function removeAssignment($assignmentId)
    {
        try {
            $assignment = ESBTPOptionAssignment::findOrFail($assignmentId);
            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignation supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'assignation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Supprimer toutes les assignations d'une option
     */
    public function clearOptionAssignments($optionId)
    {
        try {
            $deletedCount = ESBTPOptionAssignment::forOption($optionId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Toutes les assignations ont été supprimées',
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des assignations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Mettre à jour une option/variant
     */
    public function updateVariant(Request $request, $variantId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'additional_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $variant = ESBTPFraisOption::findOrFail($variantId);
            
            $variant->update([
                'name' => $request->name,
                'description' => $request->description,
                'additional_amount' => $request->additional_amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Option modifiée avec succès',
                'variant' => $variant
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification de l\'option: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification'
            ], 500);
        }
    }

    /**
     * Mettre à jour une configuration directement (édition inline)
     */
    public function updateConfigurationInline(Request $request, $configId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_deadline_days' => 'required|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuration = ESBTPFraisConfiguration::findOrFail($configId);
            
            $configuration->update([
                'amount' => $request->amount,
                'payment_deadline_days' => $request->payment_deadline_days,
                'updated_by' => auth()->id()
            ]);

            // Invalider le cache
            $this->fraisCacheService->invalidateConfigurationCache(
                $configuration->filiere_id, 
                $configuration->niveau_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuration mise à jour avec succès',
                'configuration' => $configuration
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la configuration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Activer/désactiver une configuration
     */
    public function toggleConfigurationStatus(Request $request, $configId)
    {
        try {
            $configuration = ESBTPFraisConfiguration::findOrFail($configId);
            
            $configuration->update([
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id()
            ]);

            // Invalider le cache
            $this->fraisCacheService->invalidateConfigurationCache(
                $configuration->filiere_id, 
                $configuration->niveau_id
            );

            return response()->json([
                'success' => true,
                'message' => $configuration->is_active ? 'Configuration activée' : 'Configuration désactivée'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut de la configuration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    /**
     * Mettre à jour une option directement (édition inline)
     */
    public function updateOption(Request $request, $optionId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'additional_amount' => 'required|numeric|min:0',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $option = ESBTPFraisOption::findOrFail($optionId);
            
            // Si cette option devient la nouvelle option par défaut, désactiver les autres
            if ($request->boolean('is_default') && $option->configuration_id) {
                ESBTPFraisOption::where('configuration_id', $option->configuration_id)
                    ->where('id', '!=', $optionId)
                    ->update(['is_default' => false]);
            }
            
            $option->update([
                'name' => $request->name,
                'description' => $request->description,
                'additional_amount' => $request->additional_amount,
                'is_default' => $request->boolean('is_default')
            ]);

            DB::commit();

            // Invalider le cache des options
            $this->fraisCacheService->invalidateOptionsCache();

            return response()->json([
                'success' => true,
                'message' => 'Option mise à jour avec succès',
                'option' => $option
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de l\'option: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Activer/désactiver une option
     */
    public function toggleOptionStatus(Request $request, $optionId)
    {
        try {
            $option = ESBTPFraisOption::findOrFail($optionId);
            
            $option->update([
                'is_active' => $request->boolean('is_active')
            ]);

            // Invalider le cache
            $this->fraisCacheService->invalidateOptionsCache();

            return response()->json([
                'success' => true,
                'message' => $option->is_active ? 'Option activée' : 'Option désactivée'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut de l\'option: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    /**
     * Obtenir les options d'une configuration
     */
    public function getConfigurationOptions($configId)
    {
        try {
            $configuration = ESBTPFraisConfiguration::findOrFail($configId);
            $options = $configuration->options()->active()->orderBy('sort_order')->get();

            return response()->json([
                'success' => true,
                'options' => $options,
                'configuration' => $configuration
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des options: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des options'
            ], 500);
        }
    }

}