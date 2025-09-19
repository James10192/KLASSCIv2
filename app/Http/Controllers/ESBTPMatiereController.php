<?php

namespace App\Http\Controllers;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ESBTPMatiereController extends Controller
{
    /**
     * Affiche la liste des matières.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ESBTPMatiere::with(['filieres', 'niveaux']);

        // Filtrer par filière
        if ($request->filled('filiere_filter')) {
            $query->whereHas('filieres', function($q) use ($request) {
                $q->where('esbtp_filieres.id', $request->filiere_filter);
            });
        }

        // Filtrer par niveau
        if ($request->filled('niveau_filter')) {
            $query->whereHas('niveaux', function($q) use ($request) {
                $q->where('esbtp_niveau_etudes.id', $request->niveau_filter);
            });
        }

        // Filtrer par statut
        if ($request->filled('statut_filter')) {
            $query->where('is_active', $request->statut_filter == '1');
        }

        $matieres = $query->get();

        return view('esbtp.matieres.index', compact('matieres'));
    }

    /**
     * Affiche le formulaire de création d'une nouvelle matière.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', ESBTPMatiere::class);

        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveauxEtudes = ESBTPNiveauEtude::all();
        $unitesEnseignement = collect(); // Collection vide temporaire

        // Récupérer les paramètres de pré-sélection depuis l'URL
        $preselectedFiliereId = $request->get('filiere_id');
        $preselectedNiveauId = $request->get('niveau_id');

        return view('esbtp.matieres.create', compact(
            'filieres', 
            'niveauxEtudes', 
            'unitesEnseignement',
            'preselectedFiliereId',
            'preselectedNiveauId'
        ));
    }

    /**
     * Enregistre une nouvelle matière dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_matieres,code',
            'description' => 'nullable|string',
            'coefficient' => 'required|numeric|min:0',
            'heures_cm' => 'required|integer|min:0',
            'heures_td' => 'required|integer|min:0',
            'heures_tp' => 'required|integer|min:0',
            'heures_stage' => 'required|integer|min:0',
            'heures_perso' => 'required|integer|min:0',
            'niveau_etude_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'filieres' => 'nullable|array',
            'filieres.*' => 'exists:esbtp_filieres,id',
            'niveaux' => 'nullable|array',
            'niveaux.*' => 'exists:esbtp_niveau_etudes,id',
            'type_formation' => 'required|in:generale,technologique_professionnelle',
            'couleur' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        // Ajouter l'identifiant de l'utilisateur courant
        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        // Créer la nouvelle matière
        $matiere = ESBTPMatiere::create($validatedData);

        // Gérer les liaisons multiple ou simples
        $filiereIds = [];
        $niveauIds = [];
        
        // Priorité à la multi-sélection si elle existe
        if ($request->has('filieres') && is_array($request->filieres)) {
            $filiereIds = $request->filieres;
        } elseif ($request->has('filiere_id') && $request->filiere_id) {
            $filiereIds = [$request->filiere_id];
        }
        
        if ($request->has('niveaux') && is_array($request->niveaux)) {
            $niveauIds = $request->niveaux;
        } elseif ($request->has('niveau_etude_id') && $request->niveau_etude_id) {
            $niveauIds = [$request->niveau_etude_id];
        }

        // Attacher les filières
        if (!empty($filiereIds)) {
            $matiere->filieres()->attach($filiereIds);
        }

        // Attacher les niveaux d'études
        if (!empty($niveauIds)) {
            $matiere->niveaux()->attach($niveauIds);
        }

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.matieres.index')
            ->with('success', 'La matière a été créée avec succès.');
    }

    /**
     * Affiche les détails d'une matière spécifique.
     *
     * @param  \App\Models\ESBTPMatiere  $matiere
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPMatiere $matiere)
    {
        // Charger les relations
        $matiere->load(['filieres', 'niveaux', 'createdBy', 'updatedBy']);

        return view('esbtp.matieres.show', compact('matiere'));
    }

    /**
     * Affiche le formulaire de modification d'une matière.
     *
     * @param  \App\Models\ESBTPMatiere  $matiere
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPMatiere $matiere)
    {
        // $this->authorize('update', $matiere); // Temporairement désactivé pour test

        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveauxEtudes = ESBTPNiveauEtude::all();
        $unitesEnseignement = collect(); // Collection vide temporaire

        return view('esbtp.matieres.edit', compact('matiere', 'filieres', 'niveauxEtudes', 'unitesEnseignement'));
    }

    /**
     * Met à jour la matière spécifiée dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPMatiere  $matiere
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPMatiere $matiere)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_matieres,code,' . $matiere->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'coefficient' => 'required|numeric|min:0',
            'heures_cm' => 'required|integer|min:0',
            'heures_td' => 'required|integer|min:0',
            'heures_tp' => 'required|integer|min:0',
            'heures_stage' => 'required|integer|min:0',
            'heures_perso' => 'required|integer|min:0',
            'niveau_etude_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'type_formation' => 'nullable|in:generale,technologique_professionnelle',
            'couleur' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        // Ajouter l'identifiant de l'utilisateur courant
        $validatedData['updated_by'] = Auth::id();

        // Mettre à jour la matière
        $matiere->update($validatedData);

        // Synchroniser les filières
        if ($request->has('filiere_id')) {
            $matiere->filieres()->sync($request->filiere_id);
        } else {
            $matiere->filieres()->detach();
        }

        // Synchroniser les niveaux d'études
        if ($request->has('niveau_etude_id')) {
            $matiere->niveaux()->sync($request->niveau_etude_id);
        } else {
            $matiere->niveaux()->detach();
        }

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.matieres.index')
            ->with('success', 'La matière a été mise à jour avec succès.');
    }

    /**
     * Supprime la matière spécifiée de la base de données.
     *
     * @param  \App\Models\ESBTPMatiere  $matiere
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPMatiere $matiere)
    {
        // Détacher toutes les relations
        $matiere->filieres()->detach();
        $matiere->niveaux()->detach();
        $matiere->classes()->detach();
        $matiere->enseignants()->detach();

        // Supprimer la matière
        $matiere->delete();

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.matieres.index')
            ->with('success', 'La matière a été supprimée avec succès.');
    }

    /**
     * Affiche le formulaire pour attacher des matières à une classe
     *
     * @return \Illuminate\Http\Response
     */
    public function showAttachForm()
    {
        return view('esbtp.matieres.attach-to-classe');
    }

    /**
     * Associe des matières à une classe spécifique (méthode utilitaire)
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function attachToClasse(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matieres' => 'required|array',
            'matieres.*' => 'exists:esbtp_matieres,id',
        ]);

        $classe = \App\Models\ESBTPClasse::findOrFail($validated['classe_id']);

        // Préparation des données pour l'attachement
        $matieresData = [];
        foreach ($validated['matieres'] as $matiereId) {
            $matiere = \App\Models\ESBTPMatiere::findOrFail($matiereId);
            $matieresData[$matiereId] = [
                'coefficient' => $matiere->coefficient_default ?? 1.0,
                'total_heures' => $matiere->total_heures_default ?? 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Attacher les matières à la classe
        $classe->matieres()->attach($matieresData);

        return redirect()->route('esbtp.classes.matieres', ['classe' => $classe->id])
            ->with('success', count($matieresData) . ' matière(s) ajoutée(s) à la classe avec succès.');
    }

    /**
     * Renvoie la liste des matières au format JSON pour les appels AJAX
     *
     * @return \Illuminate\Http\Response
     */
    public function getMatieresJson()
    {
        try {
            \Log::info('Méthode getMatieresJson appelée');

            // Log whether the model exists and is accessible
            try {
                $matieresCount = \App\Models\ESBTPMatiere::count();
                \Log::info('Test de connexion à la table des matières réussi. Nombre total de matières (toutes): ' . $matieresCount);
            } catch (\Exception $dbEx) {
                \Log::error('Erreur lors de l\'accès à la table des matières: ' . $dbEx->getMessage());
            }

            // Vérifier si la colonne is_active existe
            $hasIsActiveColumn = Schema::hasColumn('esbtp_matieres', 'is_active');

            // Construire la requête en fonction de la disponibilité de la colonne
            $query = \App\Models\ESBTPMatiere::query();
            if ($hasIsActiveColumn) {
                $query->where('is_active', true);
            }

            $matieres = $query->select('id', 'name', 'code', 'coefficient')
                ->orderBy('name')
                ->get();

            \Log::info('Nombre de matières trouvées: ' . $matieres->count());

            if ($matieres->isEmpty()) {
                \Log::warning('Aucune matière active trouvée');
                return response()->json([]);
            }

            $formatted = $matieres->map(function ($matiere) {
                return [
                    'id' => $matiere->id,
                    'name' => $matiere->name ?? $matiere->nom ?? 'Matière ' . $matiere->id,
                    'code' => $matiere->code ?? '',
                    'coefficient' => $matiere->coefficient ?? 1
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            \Log::error('Erreur dans getMatieresJson: ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des matières'], 500);
        }
    }

    /**
     * Renvoie toutes les matières actives en format JSON
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllMatieresJson()
    {
        $matieres = \App\Models\ESBTPMatiere::where('is_active', true)->get();

        $formattedMatieres = $matieres->map(function ($matiere) {
            return [
                'id' => $matiere->id,
                'name' => $matiere->name ?? $matiere->nom ?? 'Matière ' . $matiere->id,
                'code' => $matiere->code ?? '',
                'coefficient' => $matiere->coefficient ?? 1
            ];
        });

        return response()->json($formattedMatieres);
    }

    /**
     * Supprime plusieurs matières en masse.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        // Valider les données
        $request->validate([
            'matieres' => 'required|array',
            'matieres.*' => 'exists:esbtp_matieres,id'
        ]);

        $count = 0;

        // Supprimer chaque matière
        foreach ($request->matieres as $id) {
            $matiere = ESBTPMatiere::find($id);

            if ($matiere) {
                // Vérifier si la matière peut être supprimée (pas de dépendances)
                $canDelete = true;

                // Ajouter ici des vérifications supplémentaires si nécessaire
                // Par exemple, vérifier si la matière est utilisée dans des emplois du temps, des évaluations, etc.

                if ($canDelete) {
                    $matiere->delete();
                    $count++;
                }
            }
        }

        if ($count > 0) {
            return redirect()->route('esbtp.matieres.index')
                ->with('success', $count . ' matière(s) supprimée(s) avec succès.');
        } else {
            return redirect()->route('esbtp.matieres.index')
                ->with('error', 'Aucune matière n\'a pu être supprimée. Vérifiez qu\'elles ne sont pas utilisées ailleurs.');
        }
    }

    /**
     * Affiche l'interface d'attachement des matières aux classes.
     *
     * @return \Illuminate\Http\Response
     */
    public function attachToClasses(Request $request)
    {
        $selectedMatieres = collect();
        if ($request->has('matieres')) {
            $matiereIds = explode(',', $request->matieres);
            $selectedMatieres = ESBTPMatiere::whereIn('id', $matiereIds)->get();
        }

        $matieres = ESBTPMatiere::with(['filieres', 'niveaux'])->get();
        $classes = ESBTPClasse::with(['filiere', 'niveau'])->get();
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();

        return view('esbtp.matieres.attach-to-classes', compact('matieres', 'classes', 'filieres', 'niveaux', 'selectedMatieres'));
    }

    /**
     * Attache les matières sélectionnées aux classes sélectionnées.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processAttachToClasses(Request $request)
    {
        $request->validate([
            'matiere_ids' => 'required|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
            'classe_ids' => 'required|array',
            'classe_ids.*' => 'exists:esbtp_classes,id',
            'coefficient' => 'required|numeric|min:0',
            'total_heures' => 'required|integer|min:0',
        ]);

        $matiereIds = $request->matiere_ids;
        $classeIds = $request->classe_ids;
        $coefficient = $request->coefficient;
        $totalHeures = $request->total_heures;

        foreach ($classeIds as $classeId) {
            $classe = ESBTPClasse::find($classeId);
            foreach ($matiereIds as $matiereId) {
                $classe->matieres()->syncWithoutDetaching([
                    $matiereId => [
                        'coefficient' => $coefficient,
                        'total_heures' => $totalHeures,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                ]);
            }
        }

        return redirect()->back()->with('success', 'Les matières ont été attachées aux classes avec succès.');
    }

    /**
     * Récupère les liaisons existantes d'une matière (filières et niveaux).
     *
     * @param  \App\Models\ESBTPMatiere  $matiere
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLiaisons(ESBTPMatiere $matiere)
    {
        try {
            $matiere->load(['filieres', 'niveaux']);
            
            $filieres = $matiere->filieres->pluck('id')->toArray();
            $niveaux = $matiere->niveaux->pluck('id')->toArray();
            
            return response()->json([
                'success' => true,
                'filieres' => $filieres,
                'niveaux' => $niveaux
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des liaisons: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des liaisons'
            ], 500);
        }
    }

    /**
     * Met à jour les liaisons d'une matière avec les filières et niveaux sélectionnés.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPMatiere  $matiere
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLiaisons(Request $request, ESBTPMatiere $matiere)
    {
        try {
            $validated = $request->validate([
                'filieres' => 'array', // Permettre les tableaux vides pour supprimer toutes les liaisons
                'filieres.*' => 'exists:esbtp_filieres,id',
                'niveaux' => 'array', // Permettre les tableaux vides pour supprimer toutes les liaisons
                'niveaux.*' => 'exists:esbtp_niveau_etudes,id',
            ]);

            // Synchroniser les filières (les tableaux vides suppriment toutes les liaisons)
            $filieres = $validated['filieres'] ?? [];
            $matiere->filieres()->sync($filieres);

            // Synchroniser les niveaux (les tableaux vides suppriment toutes les liaisons)
            $niveaux = $validated['niveaux'] ?? [];
            $matiere->niveaux()->sync($niveaux);
            
            // Mettre à jour les champs directs pour compatibilité (optionnel)
            if (count($filieres) == 1 && count($niveaux) == 1) {
                $matiere->update([
                    'filiere_id' => $filieres[0],
                    'niveau_etude_id' => $niveaux[0],
                    'updated_by' => Auth::id()
                ]);
            } elseif (count($filieres) == 0 || count($niveaux) == 0) {
                // Supprimer les références directes si aucune liaison
                $matiere->update([
                    'filiere_id' => null,
                    'niveau_etude_id' => null,
                    'updated_by' => Auth::id()
                ]);
            }

            $totalCombinations = count($filieres) * count($niveaux);
            
            $message = $totalCombinations > 0
                ? "Liaisons mises à jour avec succès ! {$totalCombinations} combinaison(s) configurée(s)."
                : "Liaisons mises à jour avec succès ! Toutes les liaisons ont été supprimées.";

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour des liaisons: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde des liaisons'
            ], 500);
        }
    }

    /**
     * Récupère les statistiques de liaisons pour une matière.
     *
     * @param  \App\Models\ESBTPMatiere  $matiere
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistiquesLiaisons(ESBTPMatiere $matiere)
    {
        try {
            $matiere->load(['filieres', 'niveaux', 'classes']);
            
            $stats = [
                'filieres_count' => $matiere->filieres->count(),
                'niveaux_count' => $matiere->niveaux->count(),
                'classes_count' => $matiere->classes->count(),
                'combinations_count' => $matiere->filieres->count() * $matiere->niveaux->count(),
                'filieres_names' => $matiere->filieres->pluck('name')->toArray(),
                'niveaux_names' => $matiere->niveaux->pluck('name')->toArray(),
            ];
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Récupère TOUTES les matières actives pour les assigner à une combinaison.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableForCombination(Request $request)
    {
        try {
            $filiereId = $request->get('filiere_id');
            $niveauId = $request->get('niveau_id');
            
            if (!$filiereId || !$niveauId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les IDs filière et niveau sont requis'
                ], 400);
            }
            
            // Récupérer TOUTES les matières actives avec leur statut de liaison
            $matieres = ESBTPMatiere::where('is_active', true)
                ->select('id', 'name', 'code', 'description', 'coefficient', 'heures_cm', 'heures_td', 'heures_tp')
                ->orderBy('name')
                ->get()
                ->map(function($matiere) use ($filiereId, $niveauId) {
                    // Calculer le total des heures
                    $matiere->total_heures = $matiere->heures_cm + $matiere->heures_td + $matiere->heures_tp;
                    
                    // Vérifier si la matière est déjà liée à cette combinaison
                    $isLinkedToFiliere = $matiere->filieres()->where('esbtp_filieres.id', $filiereId)->exists();
                    $isLinkedToNiveau = $matiere->niveaux()->where('esbtp_niveau_etudes.id', $niveauId)->exists();
                    
                    $matiere->is_already_linked = $isLinkedToFiliere && $isLinkedToNiveau;
                    
                    return $matiere;
                });
            
            return response()->json([
                'success' => true,
                'matieres' => $matieres
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des matières: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des matières'
            ], 500);
        }
    }

    /**
     * Ajoute des matières à une ou plusieurs combinaisons filière/niveau.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCombination(Request $request)
    {
        $request->validate([
            'matiere_ids' => 'required|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
            'filiere_ids' => 'required|array',
            'filiere_ids.*' => 'exists:esbtp_filieres,id',
            'niveau_ids' => 'required|array',
            'niveau_ids.*' => 'exists:esbtp_niveau_etudes,id',
        ]);

        try {
            $matiereIds = $request->matiere_ids;
            $filiereIds = $request->filiere_ids;
            $niveauIds = $request->niveau_ids;
            
            $addedCount = 0;
            
            foreach ($matiereIds as $matiereId) {
                $matiere = ESBTPMatiere::find($matiereId);
                
                if ($matiere) {
                    // Ajouter les liaisons avec les filières
                    foreach ($filiereIds as $filiereId) {
                        $matiere->filieres()->syncWithoutDetaching([$filiereId]);
                    }
                    
                    // Ajouter les liaisons avec les niveaux
                    foreach ($niveauIds as $niveauId) {
                        $matiere->niveaux()->syncWithoutDetaching([$niveauId]);
                    }
                    
                    $addedCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "{$addedCount} matière(s) ajoutée(s) avec succès aux combinaisons sélectionnées."
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'ajout des matières: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des matières'
            ], 500);
        }
    }

    /**
     * API pour récupérer la liste des matières
     * Utilisée pour les dropdowns et sélections AJAX
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiList(Request $request)
    {
        try {
            $query = ESBTPMatiere::where('is_active', true);

            // Filtrer par terme de recherche si fourni
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filtrer par filière si fournie
            if ($request->has('filiere_id') && $request->filiere_id) {
                $query->whereHas('filieres', function($q) use ($request) {
                    $q->where('esbtp_filieres.id', $request->filiere_id);
                });
            }

            // Filtrer par niveau si fourni
            if ($request->has('niveau_id') && $request->niveau_id) {
                $query->whereHas('niveaux', function($q) use ($request) {
                    $q->where('esbtp_niveau_etudes.id', $request->niveau_id);
                });
            }

            $matieres = $query->select('id', 'name', 'code', 'description', 'coefficient')
                             ->orderBy('name')
                             ->get();

            return response()->json($matieres);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des matières via API: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Erreur lors du chargement des matières'
            ], 500);
        }
    }
}
