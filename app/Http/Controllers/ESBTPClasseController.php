<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMatiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ESBTPClasseController extends Controller
{
    /**
     * Affiche la liste des classes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        // Récupérer l'année universitaire courante pour l'affichage
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y') . '-' . (date('Y') + 1);
        
        $classes = ESBTPClasse::with(['filiere', 'niveau', 'annee'])->get();

        // Different view rendering based on user role
        if ($user->hasRole('etudiant')) {
            // For students - read-only view
            return view('esbtp.classes.student_index', compact('classes', 'anneeAcademique', 'anneeCourante'));
        } else {
            // For admin and secretary - full functionality view
            return view('esbtp.classes.index', compact('classes', 'anneeAcademique', 'anneeCourante'));
        }
    }

    /**
     * Affiche le formulaire de création d'une nouvelle classe.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::where('is_active', true)->get();

        return view('esbtp.classes.create', compact('filieres', 'niveaux', 'annees'));
    }

    /**
     * Enregistre une nouvelle classe dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code',
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'places_totales' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Ajouter les champs de traçabilité
        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        // Créer la nouvelle classe
        $classe = ESBTPClasse::create($validatedData);

        // Récupérer les matières associées aux niveaux sélectionnés
        $matieres = ESBTPMatiere::whereHas('niveaux', function ($query) use ($request) {
            $query->where('esbtp_niveau_etudes.id', $request->niveau_etude_id);
        })->get();

        // Associer les matières à la classe avec leurs coefficients et heures par défaut
        foreach ($matieres as $matiere) {
            $classe->matieres()->attach($matiere->id, [
                'coefficient' => $matiere->coefficient_default,
                'total_heures' => $matiere->total_heures_default,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return redirect()->route('esbtp.classes.index')
            ->with('success', 'La classe a été créée avec succès.');
    }

    /**
     * Affiche les détails d'une classe spécifique.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPClasse $classe)
    {
        $user = Auth::user();
        
        // Récupérer l'année universitaire courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        // Charger les relations de base
        $classe->load(['filiere', 'niveau', 'annee', 'matieres', 'emploisDuTemps']);
        
        // Charger les étudiants et inscriptions FILTRÉS par année courante
        if ($anneeCourante) {
            $classe->load([
                'etudiants' => function ($query) use ($anneeCourante) {
                    $query->whereHas('inscriptions', function ($inscriptionQuery) use ($anneeCourante) {
                        $inscriptionQuery->where('annee_universitaire_id', $anneeCourante->id)
                                       ->where('status', 'active');
                    });
                },
                'inscriptions' => function ($query) use ($anneeCourante) {
                    $query->where('annee_universitaire_id', $anneeCourante->id)
                          ->where('status', 'active')
                          ->with('etudiant');
                }
            ]);
        } else {
            // Si aucune année courante définie, charger normalement (éviter les erreurs)
            $classe->load(['etudiants', 'inscriptions']);
        }

        // Préparer l'année académique pour l'affichage
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y') . '-' . (date('Y') + 1);

        // Different view rendering based on user role
        if ($user->hasRole('etudiant')) {
            // For students - read-only view
            return view('esbtp.classes.student_show', compact('classe', 'anneeCourante', 'anneeAcademique'));
        } else {
            // For admin and secretary - full functionality view
            return view('esbtp.classes.show', compact('classe', 'anneeCourante', 'anneeAcademique'));
        }
    }

    /**
     * Affiche le formulaire de modification d'une classe existante.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPClasse $classe)
    {
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::where('is_active', true)->get();

        return view('esbtp.classes.edit', compact('classe', 'filieres', 'niveaux', 'annees'));
    }

    /**
     * Met à jour la classe spécifiée dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPClasse $classe)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code,' . $classe->id,
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'places_totales' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Mettre à jour les champs de traçabilité
        $validatedData['updated_by'] = Auth::id();

        // Mettre à jour la classe
        $classe->update($validatedData);

        // Si le niveau a changé, mettre à jour les matières
        if ($classe->isDirty('niveau_etude_id')) {
            // Récupérer les matières associées au niveau sélectionné
            $matieres = ESBTPMatiere::whereHas('niveaux', function ($query) use ($request) {
                $query->where('esbtp_niveau_etudes.id', $request->niveau_etude_id);
            })->get();

            // Réinitialiser les matières associées à la classe
            $classe->matieres()->detach();

            // Associer les nouvelles matières à la classe
            foreach ($matieres as $matiere) {
                $classe->matieres()->attach($matiere->id, [
                    'coefficient' => $matiere->coefficient_default,
                    'total_heures' => $matiere->total_heures_default,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return redirect()->route('esbtp.classes.index')
            ->with('success', 'La classe a été mise à jour avec succès.');
    }

    /**
     * Supprime la classe spécifiée de la base de données.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPClasse $classe)
    {
        // Vérifier si des étudiants sont inscrits dans cette classe
        if ($classe->inscriptions()->count() > 0) {
            return redirect()->route('esbtp.classes.index')
                ->with('error', 'Impossible de supprimer cette classe car elle contient des étudiants inscrits.');
        }

        // Détacher toutes les matières
        $classe->matieres()->detach();

        // Supprimer la classe
        $classe->delete();

        return redirect()->route('esbtp.classes.index')
            ->with('success', 'La classe a été supprimée avec succès.');
    }

    /**
     * Affiche la page de gestion des matières associées à une classe.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function matieres(ESBTPClasse $classe)
    {
        $classe->load('matieres');
        $allMatieres = ESBTPMatiere::where('is_active', true)->get();

        return view('esbtp.classes.matieres', compact('classe', 'allMatieres'));
    }

    /**
     * Met à jour les matières et leurs coefficients pour une classe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function updateMatieres(Request $request, ESBTPClasse $classe)
    {
        // Valider les données du formulaire
        $request->validate([
            'matiere_ids' => 'nullable|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
            'coefficients' => 'nullable|array',
            'coefficients.*' => 'numeric|min:0',
            'heures' => 'nullable|array',
            'heures.*' => 'integer|min:0',
            'active' => 'nullable|array',
            'active.*' => 'boolean',
        ]);

        // Réinitialiser les matières existantes
        $classe->matieres()->detach();

        // Récupérer les IDs des matières sélectionnées
        $matiereIds = $request->input('matiere_ids', []);

        // Ajouter les matières sélectionnées avec leurs coefficients et heures
        foreach ($matiereIds as $matiereId) {
            $classe->matieres()->attach($matiereId, [
                'coefficient' => $request->input("coefficients.{$matiereId}", 1),
                'total_heures' => $request->input("heures.{$matiereId}", 0),
                'is_active' => $request->has("active.{$matiereId}") ? true : false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('esbtp.classes.show', ['classe' => $classe->id])
            ->with('success', 'Les matières ont été mises à jour avec succès.');
    }

    /**
     * Récupère les matières d'une classe pour l'API JavaScript.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function getMatieresForApi(ESBTPClasse $classe)
    {
        // Log pour debugging
        \Log::info('API matières appelée pour la classe ID: ' . $classe->id);
        \Log::info('Classe: ' . ($classe->nom ?? $classe->name ?? 'N/A') . ' (Filière: ' . ($classe->filiere->nom ?? $classe->filiere->name ?? 'N/A') . ', Niveau: ' . ($classe->niveau->nom ?? $classe->niveau->name ?? 'N/A') . ')');

        // Récupérer les matières de la classe
        $matieres = $classe->matieres()->where('esbtp_matieres.is_active', true)->get();
        \Log::info('Matières directement liées à la classe: ' . $matieres->count());

        // Si aucune matière n'est trouvée, essayer de récupérer les matières de la même filière et niveau
        if ($matieres->isEmpty()) {
            \Log::info('Aucune matière directement liée, recherche par filière et niveau...');
            // Récupérer des matières basées sur la filière et le niveau d'étude
            $matieres = \App\Models\ESBTPMatiere::where('is_active', true);

            if ($classe->filiere_id) {
                $matieres = $matieres->whereHas('filieres', function($q) use ($classe) {
                    $q->where('esbtp_filieres.id', $classe->filiere_id);
                });
                \Log::info('Filtrage par filière_id: ' . $classe->filiere_id);
            }

            if ($classe->niveau_etude_id) {
                $matieres = $matieres->whereHas('niveaux', function($q) use ($classe) {
                    $q->where('esbtp_niveaux_etudes.id', $classe->niveau_etude_id);
                });
                \Log::info('Filtrage par niveau_etude_id: ' . $classe->niveau_etude_id);
            }

            $matieres = $matieres->get();
            \Log::info('Matières trouvées par filière et niveau: ' . $matieres->count());
        }

        // Si toujours aucune matière, récupérer toutes les matières actives (pas de limite de 10)
        if ($matieres->isEmpty()) {
            \Log::info('Aucune matière trouvée par filière/niveau, récupération de toutes les matières actives...');
            $matieres = \App\Models\ESBTPMatiere::where('is_active', true)->get();
            \Log::info('Toutes les matières actives trouvées: ' . $matieres->count());
        }

        // Si toujours aucune matière, récupérer toutes les matières (même inactives)
        if ($matieres->isEmpty()) {
            \Log::info('Aucune matière active trouvée, récupération de toutes les matières...');
            $matieres = \App\Models\ESBTPMatiere::all();
            \Log::info('Toutes les matières trouvées: ' . $matieres->count());
        }

        // Formater les matières pour l'API JavaScript
        $formattedMatieres = $matieres->map(function ($matiere) {
            $formatted = [
                'id' => $matiere->id,
                'name' => $matiere->nom ?? $matiere->name ?? 'Matière ' . $matiere->id,
                'code' => $matiere->code ?? '',
                'coefficient' => $matiere->coefficient ?? 1
            ];
            \Log::info('Matière formatée: ' . json_encode($formatted));
            return $formatted;
        });

        \Log::info('Total matières renvoyées: ' . $formattedMatieres->count());
        return response()->json($formattedMatieres);
    }

    /**
     * Get subjects for a specific class.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatieres($id)
    {
        try {
            $classe = ESBTPClasse::findOrFail($id);
            $matieres = $classe->matieres()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            return response()->json($matieres);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des matières'], 500);
        }
    }

    /**
     * Récupère les détails d'une classe pour l'API JavaScript.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getClasseById($id)
    {
        try {
            $classe = ESBTPClasse::with(['filiere', 'niveau', 'anneeUniversitaire'])
                ->findOrFail($id);

            return response()->json($classe);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la classe: ' . $e->getMessage());
            return response()->json(['error' => 'Classe non trouvée'], 404);
        }
    }

    /**
     * Returns all active classes for API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexApi()
    {
        $classes = ESBTPClasse::with(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->get();
        return response()->json($classes);
    }

    /**
     * Récupère le nombre de places disponibles pour une classe.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailablePlaces($id)
    {
        try {
            \Log::info("Début de getAvailablePlaces pour la classe ID: {$id}");
            
            // Trouver l'année universitaire active
            $anneeActive = \App\Models\ESBTPAnneeUniversitaire::where('is_active', true)->first();
            if (!$anneeActive) {
                \Log::error("Aucune année universitaire active trouvée");
                return response()->json(['error' => 'Aucune année universitaire active.'], 400);
            }

            $classe = ESBTPClasse::find($id);

            if (!$classe) {
                \Log::error("Classe non trouvée pour l'ID: {$id}");
                return response()->json(['error' => 'Classe non trouvée.'], 404);
            }
            
            \Log::info("Classe trouvée: {$classe->name}");

            $capacity = $classe->places_totales ?? 0;
            \Log::info("Capacité (places_totales) lue: {$capacity}");

            // Compter seulement les inscriptions de l'année universitaire active
            $inscriptions_count = $classe->inscriptions()
                ->where('annee_universitaire_id', $anneeActive->id)
                ->where('status', '!=', 'annulée')
                ->count();
            \Log::info("Nombre d'inscriptions pour l'année active {$anneeActive->name}: {$inscriptions_count}");

            $availablePlaces = $capacity - $inscriptions_count;
            \Log::info("Calcul des places disponibles: {$capacity} - {$inscriptions_count} = {$availablePlaces}");

            $responseData = [
                'available_places' => $availablePlaces,
                'capacity' => $capacity,
                'inscriptions_count' => $inscriptions_count,
            ];

            \Log::info("Réponse JSON envoyée: " . json_encode($responseData));

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error("Erreur dans getAvailablePlaces pour la classe ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des données.'], 500);
        }
    }

    /**
     * Récupère les étudiants d'une classe pour l'API JavaScript.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function getEtudiants(ESBTPClasse $classe)
    {
        $etudiants = $classe->etudiants()
            ->select('id', 'nom', 'prenom', 'matricule')
            ->where('is_active', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return response()->json([
            'success' => true,
            'etudiants' => $etudiants
        ]);
    }
}
