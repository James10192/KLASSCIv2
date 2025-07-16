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
        $this->middleware('permission:frais.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:frais.delete', ['only' => ['destroy']]);
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
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        // Filtres
        $filiereId = $request->get('filiere_id');
        $niveauId = $request->get('niveau_id');
        $anneeId = $request->get('annee_id');

        $rules = [];
        if ($filiereId && $niveauId) {
            $rules = ESBTPFraisRule::with(['fraisCategory', 'filiere', 'niveau', 'anneeUniversitaire'])
                ->where('filiere_id', $filiereId)
                ->where('niveau_id', $niveauId)
                ->when($anneeId, function ($query, $anneeId) {
                    return $query->where('annee_universitaire_id', $anneeId);
                })
                ->get();
        }

        return view('esbtp.frais.configure', compact(
            'categories',
            'filieres',
            'niveaux',
            'annees',
            'rules',
            'filiereId',
            'niveauId',
            'anneeId'
        ));
    }

    /**
     * Afficher les détails d'une catégorie de frais
     */
    public function show(ESBTPFraisCategory $fraisCategory)
    {
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
    public function edit(ESBTPFraisCategory $fraisCategory)
    {
        return view('esbtp.frais.edit', compact('fraisCategory'));
    }

    /**
     * Mettre à jour une catégorie de frais
     */
    public function update(Request $request, ESBTPFraisCategory $fraisCategory)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_frais_categories,code,' . $fraisCategory->id,
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

            $fraisCategory->update([
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
    public function destroy(ESBTPFraisCategory $fraisCategory)
    {
        try {
            DB::beginTransaction();

            // Vérifier s'il y a des paiements associés
            if ($fraisCategory->paiements()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer cette catégorie car elle contient des paiements associés.');
            }

            // Supprimer les règles associées
            $fraisCategory->rules()->delete();

            // Supprimer la catégorie
            $fraisCategory->delete();

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
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'rules' => 'required|array',
            'rules.*.frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'rules.*.amount' => 'required|numeric|min:0',
            'rules.*.payment_deadline_days' => 'required|integer|min:1|max:365',
            'rules.*.installments_allowed' => 'required|boolean',
            'rules.*.max_installments' => 'nullable|integer|min:1|max:12',
            'rules.*.min_installment_amount' => 'nullable|numeric|min:0',
            'rules.*.late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'rules.*.late_fee_amount' => 'nullable|numeric|min:0',
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
            $anneeId = $request->annee_universitaire_id;

            foreach ($request->rules as $ruleData) {
                ESBTPFraisRule::updateOrCreate(
                    [
                        'frais_category_id' => $ruleData['frais_category_id'],
                        'filiere_id' => $filiereId,
                        'niveau_id' => $niveauId,
                        'annee_universitaire_id' => $anneeId,
                    ],
                    [
                        'amount' => $ruleData['amount'],
                        'payment_deadline_days' => $ruleData['payment_deadline_days'],
                        'installments_allowed' => $ruleData['installments_allowed'],
                        'max_installments' => $ruleData['max_installments'] ?? 1,
                        'min_installment_amount' => $ruleData['min_installment_amount'],
                        'late_fee_percentage' => $ruleData['late_fee_percentage'] ?? 0,
                        'late_fee_amount' => $ruleData['late_fee_amount'] ?? 0,
                        'is_active' => true,
                        'effective_date' => now(),
                    ]
                );
            }

            DB::commit();

            return redirect()->back()
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
}