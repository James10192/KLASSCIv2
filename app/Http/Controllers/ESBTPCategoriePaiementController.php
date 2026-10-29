<?php

namespace App\Http\Controllers;

use App\Models\ESBTPCategoriePaiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ESBTPCategoriePaiementController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Afficher la liste des catégories de paiement.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Récupérer toutes les catégories parentes (niveau supérieur)
        $categoriesParentes = ESBTPCategoriePaiement::parents()->ordre()->get();
        
        // Récupérer toutes les catégories (pour affichage complet)
        $categories = ESBTPCategoriePaiement::with('enfants', 'parent')->ordre()->get();
        
        return view('esbtp.comptabilite.categories-paiement.index', compact('categoriesParentes', 'categories'));
    }

    /**
     * Afficher le formulaire de création d'une catégorie.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Récupérer les catégories parentes potentielles
        $categoriesParentes = ESBTPCategoriePaiement::parents()->actif()->ordre()->get();
        
        return view('esbtp.comptabilite.categories-paiement.create', compact('categoriesParentes'));
    }

    /**
     * Enregistrer une nouvelle catégorie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:esbtp_categorie_paiements,code',
            'description' => 'nullable|string',
            'icone' => 'required|string|max:50',
            'couleur' => 'required|string|max:20',
            'est_obligatoire' => 'boolean',
            'parent_id' => 'nullable|exists:esbtp_categorie_paiements,id',
            'ordre' => 'nullable|integer|min:0',
        ]);
        
        // Générer automatiquement le slug si non fourni
        $validated['slug'] = Str::slug($request->nom);
        
        // Générer automatiquement le code si non fourni
        if (empty($validated['code'])) {
            $validated['code'] = Str::upper(Str::substr(Str::slug($request->nom, ''), 0, 10));
        }
        
        // Définir la valeur de est_obligatoire
        $validated['est_obligatoire'] = $request->has('est_obligatoire');
        
        // Définir la valeur de est_actif (toujours actif à la création)
        $validated['est_actif'] = true;
        
        try {
            // Créer la catégorie
            ESBTPCategoriePaiement::create($validated);
            
            return redirect()->route('esbtp.comptabilite.categories-paiement.index')
                ->with('success', 'Catégorie de paiement créée avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création d\'une catégorie de paiement: ' . $e->getMessage());
            
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de la catégorie');
        }
    }

    /**
     * Afficher les détails d'une catégorie spécifique.
     *
     * @param  \App\Models\ESBTPCategoriePaiement  $categorie
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPCategoriePaiement $categorie)
    {
        // Charger les relations
        $categorie->load('enfants', 'parent');
        
        // Récupérer les paiements associés à cette catégorie
        $paiements = $categorie->paiements()->latest()->paginate(10);
        
        return view('esbtp.comptabilite.categories-paiement.show', compact('categorie', 'paiements'));
    }

    /**
     * Afficher le formulaire de modification d'une catégorie.
     *
     * @param  \App\Models\ESBTPCategoriePaiement  $categorie
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPCategoriePaiement $categorie)
    {
        // Récupérer les catégories parentes potentielles (exclure la catégorie courante et ses enfants)
        $categoriesParentes = ESBTPCategoriePaiement::where('id', '!=', $categorie->id)
            ->whereNotIn('id', $categorie->enfants->pluck('id'))
            ->where(function($query) use ($categorie) {
                $query->whereNull('parent_id')
                      ->orWhere('parent_id', '!=', $categorie->id);
            })
            ->actif()
            ->ordre()
            ->get();
        
        return view('esbtp.comptabilite.categories-paiement.edit', compact('categorie', 'categoriesParentes'));
    }

    /**
     * Mettre à jour une catégorie spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPCategoriePaiement  $categorie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPCategoriePaiement $categorie)
    {
        // Valider les données du formulaire
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:esbtp_categorie_paiements,code,' . $categorie->id,
            'description' => 'nullable|string',
            'icone' => 'required|string|max:50',
            'couleur' => 'required|string|max:20',
            'est_obligatoire' => 'boolean',
            'est_actif' => 'boolean',
            'parent_id' => 'nullable|exists:esbtp_categorie_paiements,id',
            'ordre' => 'nullable|integer|min:0',
        ]);
        
        // Mettre à jour le slug
        $validated['slug'] = Str::slug($request->nom);
        
        // Mettre à jour le code si fourni
        if (empty($validated['code'])) {
            $validated['code'] = Str::upper(Str::substr(Str::slug($request->nom, ''), 0, 10));
        }
        
        // Gérer les checkboxes
        $validated['est_obligatoire'] = $request->has('est_obligatoire');
        $validated['est_actif'] = $request->has('est_actif');
        
        // Vérifier que la catégorie n'est pas définie comme son propre parent
        if ($validated['parent_id'] == $categorie->id) {
            return back()->withInput()->with('error', 'Une catégorie ne peut pas être son propre parent');
        }
        
        try {
            // Mettre à jour la catégorie
            $categorie->update($validated);
            
            return redirect()->route('esbtp.comptabilite.categories-paiement.index')
                ->with('success', 'Catégorie de paiement mise à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour d\'une catégorie de paiement: ' . $e->getMessage());
            
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de la catégorie');
        }
    }

    /**
     * Supprimer une catégorie spécifique.
     *
     * @param  \App\Models\ESBTPCategoriePaiement  $categorie
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPCategoriePaiement $categorie)
    {
        // Vérifier si la catégorie a des paiements associés
        if ($categorie->paiements()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer cette catégorie car elle est associée à des paiements');
        }
        
        // Vérifier si la catégorie a des sous-catégories
        if ($categorie->enfants()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer cette catégorie car elle a des sous-catégories');
        }
        
        try {
            // Supprimer la catégorie
            $categorie->delete();
            
            return redirect()->route('esbtp.comptabilite.categories-paiement.index')
                ->with('success', 'Catégorie de paiement supprimée avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression d\'une catégorie de paiement: ' . $e->getMessage());
            
            return back()->with('error', 'Une erreur est survenue lors de la suppression de la catégorie');
        }
    }

    /**
     * Mise à jour rapide du statut (actif/inactif).
     *
     * @param  \App\Models\ESBTPCategoriePaiement  $categorie
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(ESBTPCategoriePaiement $categorie)
    {
        $categorie->est_actif = !$categorie->est_actif;
        $categorie->save();
        
        return back()->with('success', 'Statut de la catégorie mis à jour avec succès');
    }
}
