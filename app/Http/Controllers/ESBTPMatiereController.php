<?php

namespace App\Http\Controllers;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Models\ESBTPClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ESBTPMatiereController extends Controller
{
    /**
     * Affiche la liste des matières.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $matieres = ESBTPMatiere::with(['uniteEnseignement', 'filieres', 'niveaux'])->get();

        return view('esbtp.matieres.index', compact('matieres'));
    }

    /**
     * Affiche le formulaire de création d'une nouvelle matière.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $filieres = ESBTPFiliere::all();
        $niveaux = ESBTPNiveauEtude::all();
        $unitesEnseignement = ESBTPUniteEnseignement::all();

        return view('esbtp.matieres.create', compact('filieres', 'niveaux', 'unitesEnseignement'));
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
            'type_formation' => 'required|in:generale,technologique_professionnelle',
            'couleur' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        // Ajouter l'identifiant de l'utilisateur courant
        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        // Créer la nouvelle matière
        $matiere = ESBTPMatiere::create($validatedData);

        // Attacher les filières si présentes
        if ($request->has('filiere_id')) {
            $matiere->filieres()->attach($request->filiere_id);
        }

        // Attacher les niveaux d'études si présents
        if ($request->has('niveau_etude_id')) {
            $matiere->niveaux()->attach($request->niveau_etude_id);
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
        $matiere->load(['uniteEnseignement', 'filieres', 'niveaux', 'createdBy', 'updatedBy']);

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
        // Charger les relations
        $matiere->load([
            'uniteEnseignement',
            'filieres',
            'niveaux',
            'seancesCours',
            'evaluations'
        ]);

        // Récupérer les données pour les listes déroulantes
        $filieres = ESBTPFiliere::all();
        $niveaux = ESBTPNiveauEtude::all();
        $unitesEnseignement = ESBTPUniteEnseignement::all();

        // Obtenir les IDs actuellement associés
        $filiereIds = $matiere->filieres->pluck('id')->toArray();
        $niveauIds = $matiere->niveaux->pluck('id')->toArray();

        return view('esbtp.matieres.edit', compact(
            'matiere',
            'filieres',
            'niveaux',
            'unitesEnseignement',
            'filiereIds',
            'niveauIds'
        ));
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
}
