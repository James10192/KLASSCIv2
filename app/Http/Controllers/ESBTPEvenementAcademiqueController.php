<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEvenementAcademique;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ESBTPEvenementAcademiqueController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:module.academique.access');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $type = $request->input('type');
        $statut = $request->input('statut');
        $search = $request->input('search');

        // Récupérer les années universitaires
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeSelectionnee = $anneeId ? ESBTPAnneeUniversitaire::find($anneeId) : 
                           ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Query de base
        $query = ESBTPEvenementAcademique::with(['anneeUniversitaire', 'createdBy'])
            ->active();

        // Filtres
        if ($anneeSelectionnee) {
            $query->forAnnee($anneeSelectionnee->id);
        }

        if ($type) {
            $query->ofType($type);
        }

        if ($statut) {
            $query->withStatus($statut);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('lieu', 'like', "%{$search}%");
            });
        }

        $evenements = $query->orderBy('date_debut', 'asc')->paginate(15);

        // Statistiques
        $stats = $this->calculerStatistiques($anneeSelectionnee);

        return view('esbtp.evenements-academiques.index', compact(
            'evenements', 'annees', 'anneeSelectionnee', 'type', 'statut', 'search', 'stats'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeSelectionnee = $anneeId ? ESBTPAnneeUniversitaire::find($anneeId) : 
                           ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Données pour les sélecteurs
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('year')->get();

        return view('esbtp.evenements-academiques.create', compact(
            'annees', 'anneeSelectionnee', 'filieres', 'niveaux'
        ));
    }

    /**
     * Show the form for creating a quick academic event (start/end of year).
     */
    public function createQuick($type, $anneeId)
    {
        // Vérifier que le type est valide
        if (!in_array($type, ['rentree', 'fermeture'])) {
            return redirect()->route('esbtp.evenements-academiques.index')
                ->with('error', 'Type d\'événement non valide pour la création rapide.');
        }

        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeSelectionnee = ESBTPAnneeUniversitaire::find($anneeId);
        
        if (!$anneeSelectionnee) {
            return redirect()->route('esbtp.evenements-academiques.index')
                ->with('error', 'Année universitaire non trouvée.');
        }

        // Données pour les sélecteurs
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('year')->get();

        // Pré-remplir les données selon le type
        $defaultData = $this->getQuickEventDefaults($type, $anneeSelectionnee);

        return view('esbtp.evenements-academiques.create', compact(
            'annees', 'anneeSelectionnee', 'filieres', 'niveaux', 'defaultData'
        ));
    }

    /**
     * Get default data for quick event creation
     */
    private function getQuickEventDefaults($type, $anneeUniversitaire)
    {
        $defaults = [
            'type' => $type,
            'annee_universitaire_id' => $anneeUniversitaire->id,
            'icone' => ESBTPEvenementAcademique::ICONES_TYPES[$type],
            'couleur' => ESBTPEvenementAcademique::COULEURS_TYPES[$type],
            'afficher_calendrier' => true,
            'afficher_timeline' => true,
            'notification_active' => true,
            'jours_notification' => 7,
        ];

        if ($type === 'rentree') {
            $defaults['titre'] = 'Rentrée académique ' . $anneeUniversitaire->name;
            $defaults['description'] = 'Début de l\'année académique ' . $anneeUniversitaire->name . '. Accueil des nouveaux étudiants et reprise des cours.';
            $defaults['date_debut'] = $anneeUniversitaire->start_date->format('Y-m-d');
            $defaults['lieu'] = 'Campus ESBTP';
            $defaults['heure_debut'] = '08:00';
            $defaults['notes'] = 'Prévoir l\'accueil des nouveaux étudiants et la distribution des emplois du temps.';
        } elseif ($type === 'fermeture') {
            $defaults['titre'] = 'Fin d\'année académique ' . $anneeUniversitaire->name;
            $defaults['description'] = 'Clôture de l\'année académique ' . $anneeUniversitaire->name . '. Fin des cours et début des vacances.';
            $defaults['date_debut'] = $anneeUniversitaire->end_date->format('Y-m-d');
            $defaults['lieu'] = 'Campus ESBTP';
            $defaults['heure_fin'] = '17:00';
            $defaults['notes'] = 'Bilan de l\'année académique et préparation de la prochaine rentrée.';
        }

        return $defaults;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'type' => 'required|in:' . implode(',', array_keys(ESBTPEvenementAcademique::TYPES)),
            'icone' => 'required|string|max:50',
            'couleur' => 'required|in:' . implode(',', array_keys(ESBTPEvenementAcademique::COULEURS)),
            'lieu' => 'nullable|string|max:255',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
            'participants' => 'nullable|array',
            'notes' => 'nullable|string',
            'notification_active' => 'boolean',
            'jours_notification' => 'integer|min:1|max:30',
            'afficher_calendrier' => 'boolean',
            'afficher_timeline' => 'boolean',
        ]);

        // Validation des dates cohérentes
        $validationErrors = $this->validateEventDates($request->all());
        if (!empty($validationErrors)) {
            return redirect()->back()
                ->withErrors($validationErrors)
                ->withInput();
        }

        $evenement = ESBTPEvenementAcademique::create([
            'annee_universitaire_id' => $request->annee_universitaire_id,
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'type' => $request->type,
            'icone' => $request->icone,
            'couleur' => $request->couleur,
            'lieu' => $request->lieu,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'participants' => $request->participants,
            'notes' => $request->notes,
            'notification_active' => $request->boolean('notification_active'),
            'jours_notification' => $request->jours_notification ?? 7,
            'afficher_calendrier' => $request->boolean('afficher_calendrier', true),
            'afficher_timeline' => $request->boolean('afficher_timeline', true),
            'statut' => 'planifie',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('esbtp.evenements-academiques.index', ['annee_id' => $request->annee_universitaire_id])
            ->with('success', 'Événement académique créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ESBTPEvenementAcademique $evenementAcademique)
    {
        $evenementAcademique->load(['anneeUniversitaire', 'createdBy', 'updatedBy']);

        return view('esbtp.evenements-academiques.show', compact('evenementAcademique'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ESBTPEvenementAcademique $evenementAcademique)
    {
        if (!$evenementAcademique->isEditable()) {
            return redirect()->route('esbtp.evenements-academiques.index')
                ->with('error', 'Cet événement ne peut plus être modifié.');
        }

        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('year')->get();

        return view('esbtp.evenements-academiques.edit', compact(
            'evenementAcademique', 'annees', 'filieres', 'niveaux'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ESBTPEvenementAcademique $evenementAcademique)
    {
        if (!$evenementAcademique->isEditable()) {
            return redirect()->route('esbtp.evenements-academiques.index')
                ->with('error', 'Cet événement ne peut plus être modifié.');
        }

        $request->validate([
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'type' => 'required|in:' . implode(',', array_keys(ESBTPEvenementAcademique::TYPES)),
            'icone' => 'required|string|max:50',
            'couleur' => 'required|in:' . implode(',', array_keys(ESBTPEvenementAcademique::COULEURS)),
            'lieu' => 'nullable|string|max:255',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
            'participants' => 'nullable|array',
            'notes' => 'nullable|string',
            'notification_active' => 'boolean',
            'jours_notification' => 'integer|min:1|max:30',
            'afficher_calendrier' => 'boolean',
            'afficher_timeline' => 'boolean',
            'statut' => 'required|in:' . implode(',', array_keys(ESBTPEvenementAcademique::STATUTS)),
        ]);

        // Validation des dates cohérentes
        $validationErrors = $this->validateEventDates($request->all(), $evenementAcademique->id);
        if (!empty($validationErrors)) {
            return redirect()->back()
                ->withErrors($validationErrors)
                ->withInput();
        }

        $evenementAcademique->update([
            'annee_universitaire_id' => $request->annee_universitaire_id,
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'type' => $request->type,
            'icone' => $request->icone,
            'couleur' => $request->couleur,
            'lieu' => $request->lieu,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'participants' => $request->participants,
            'notes' => $request->notes,
            'notification_active' => $request->boolean('notification_active'),
            'jours_notification' => $request->jours_notification ?? 7,
            'afficher_calendrier' => $request->boolean('afficher_calendrier', true),
            'afficher_timeline' => $request->boolean('afficher_timeline', true),
            'statut' => $request->statut,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('esbtp.evenements-academiques.index', ['annee_id' => $request->annee_universitaire_id])
            ->with('success', 'Événement académique modifié avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ESBTPEvenementAcademique $evenementAcademique)
    {
        if (!$evenementAcademique->isDeletable()) {
            return redirect()->route('esbtp.evenements-academiques.index')
                ->with('error', 'Cet événement ne peut pas être supprimé.');
        }

        $anneeId = $evenementAcademique->annee_universitaire_id;
        $evenementAcademique->delete();

        return redirect()->route('esbtp.evenements-academiques.index', ['annee_id' => $anneeId])
            ->with('success', 'Événement académique supprimé avec succès.');
    }

    /**
     * Dupliquer un événement pour l'année suivante
     */
    public function duplicate(ESBTPEvenementAcademique $evenementAcademique)
    {
        $anneeActuelle = $evenementAcademique->anneeUniversitaire;
        $anneeSuivante = ESBTPAnneeUniversitaire::where('start_date', '>', $anneeActuelle->start_date)
            ->orderBy('start_date', 'asc')
            ->first();

        if (!$anneeSuivante) {
            return redirect()->route('esbtp.evenements-academiques.index')
                ->with('error', 'Aucune année suivante trouvée pour la duplication.');
        }

        // Calculer les nouvelles dates
        $diffAnnees = $anneeSuivante->start_date - $anneeActuelle->start_date;
        $nouvelleDateDebut = Carbon::parse($evenementAcademique->date_debut)->addYears($diffAnnees);
        $nouvelleDateFin = $evenementAcademique->date_fin ? 
            Carbon::parse($evenementAcademique->date_fin)->addYears($diffAnnees) : null;

        $nouvelEvenement = ESBTPEvenementAcademique::create([
            'annee_universitaire_id' => $anneeSuivante->id,
            'titre' => $evenementAcademique->titre,
            'description' => $evenementAcademique->description,
            'date_debut' => $nouvelleDateDebut,
            'date_fin' => $nouvelleDateFin,
            'type' => $evenementAcademique->type,
            'icone' => $evenementAcademique->icone,
            'couleur' => $evenementAcademique->couleur,
            'lieu' => $evenementAcademique->lieu,
            'heure_debut' => $evenementAcademique->heure_debut,
            'heure_fin' => $evenementAcademique->heure_fin,
            'participants' => $evenementAcademique->participants,
            'notes' => $evenementAcademique->notes,
            'notification_active' => $evenementAcademique->notification_active,
            'jours_notification' => $evenementAcademique->jours_notification,
            'afficher_calendrier' => $evenementAcademique->afficher_calendrier,
            'afficher_timeline' => $evenementAcademique->afficher_timeline,
            'statut' => 'planifie',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('esbtp.evenements-academiques.index', ['annee_id' => $anneeSuivante->id])
            ->with('success', 'Événement dupliqué avec succès pour l\'année ' . $anneeSuivante->name);
    }

    /**
     * Changer le statut d'un événement
     */
    public function changeStatus(Request $request, ESBTPEvenementAcademique $evenementAcademique)
    {
        $request->validate([
            'statut' => 'required|in:' . implode(',', array_keys(ESBTPEvenementAcademique::STATUTS)),
        ]);

        $evenementAcademique->update([
            'statut' => $request->statut,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Statut modifié avec succès.');
    }

    /**
     * Actions en lot sur les événements
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,change_status',
            'events' => 'required|array|min:1',
            'events.*' => 'exists:esbtp_evenements_academiques,id',
            'status' => 'nullable|in:' . implode(',', array_keys(ESBTPEvenementAcademique::STATUTS)),
        ]);

        $events = ESBTPEvenementAcademique::whereIn('id', $request->events)->get();
        $count = 0;

        try {
            DB::beginTransaction();

            foreach ($events as $event) {
                if ($request->action === 'delete' && $event->isDeletable()) {
                    $event->delete();
                    $count++;
                } elseif ($request->action === 'change_status') {
                    $event->update([
                        'statut' => $request->status,
                        'updated_by' => Auth::id(),
                    ]);
                    $count++;
                }
            }

            DB::commit();

            $actionText = $request->action === 'delete' ? 'supprimés' : 'mis à jour';
            return redirect()->back()->with('success', "{$count} événement(s) {$actionText} avec succès.");

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors de l\'action en lot : ' . $e->getMessage());
        }
    }

    /**
     * API pour récupérer les événements d'une année
     */
    public function getEvents(Request $request)
    {
        $anneeId = $request->input('annee_id');
        $mois = $request->input('mois');

        $query = ESBTPEvenementAcademique::active()
            ->visibleCalendrier();

        if ($anneeId) {
            $query->forAnnee($anneeId);
        }

        if ($mois) {
            $query->whereMonth('date_debut', $mois);
        }

        $events = $query->orderBy('date_debut', 'asc')->get();

        return response()->json($events->map(function($event) {
            return [
                'id' => $event->id,
                'titre' => $event->titre,
                'description' => $event->description,
                'date' => $event->date_debut->format('d/m/Y'),
                'date_debut' => $event->date_debut->format('Y-m-d'),
                'date_fin' => $event->date_fin ? $event->date_fin->format('Y-m-d') : null,
                'type' => $event->type,
                'icone' => $event->icone,
                'couleur' => $event->couleur,
                'lieu' => $event->lieu,
                'statut' => $event->statut,
                'statut_libelle' => $event->statut_libelle,
                'duree' => $event->duree,
                'participants' => $event->participants_formatted,
            ];
        }));
    }

    /**
     * Valider la cohérence des dates d'un événement
     */
    private function validateEventDates($data, $eventId = null)
    {
        $errors = [];
        
        // Récupérer l'année universitaire
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($data['annee_universitaire_id']);
        if (!$anneeUniversitaire) {
            $errors['annee_universitaire_id'] = 'Année universitaire non trouvée.';
            return $errors;
        }

        $dateDebut = Carbon::parse($data['date_debut']);
        $dateFin = isset($data['date_fin']) && $data['date_fin'] ? Carbon::parse($data['date_fin']) : null;

        // 1. Vérifier que les dates sont dans la période de l'année universitaire
        if ($dateDebut->lt($anneeUniversitaire->start_date) || $dateDebut->gt($anneeUniversitaire->end_date)) {
            $errors['date_debut'] = 'La date de début doit être comprise entre ' . 
                $anneeUniversitaire->start_date->format('d/m/Y') . ' et ' . 
                $anneeUniversitaire->end_date->format('d/m/Y') . 
                ' (période de l\'année universitaire ' . $anneeUniversitaire->name . ').';
        }

        if ($dateFin && ($dateFin->lt($anneeUniversitaire->start_date) || $dateFin->gt($anneeUniversitaire->end_date))) {
            $errors['date_fin'] = 'La date de fin doit être comprise entre ' . 
                $anneeUniversitaire->start_date->format('d/m/Y') . ' et ' . 
                $anneeUniversitaire->end_date->format('d/m/Y') . 
                ' (période de l\'année universitaire ' . $anneeUniversitaire->name . ').';
        }

        // 2. Vérifier les conflits pour certains types d'événements
        if (in_array($data['type'], ['rentree', 'fermeture'])) {
            $query = ESBTPEvenementAcademique::where('annee_universitaire_id', $data['annee_universitaire_id'])
                ->where('type', $data['type']);
            
            if ($eventId) {
                $query->where('id', '!=', $eventId);
            }
            
            $existingEvent = $query->first();
            if ($existingEvent) {
                $typeLabel = ESBTPEvenementAcademique::TYPES[$data['type']];
                $errors['type'] = "Un événement de type \"{$typeLabel}\" existe déjà pour cette année universitaire.";
            }
        }

        // 3. Vérifier les chevauchements critiques (examens, cérémonies)
        if (in_array($data['type'], ['examens', 'ceremonie', 'soutenances'])) {
            $query = ESBTPEvenementAcademique::where('annee_universitaire_id', $data['annee_universitaire_id'])
                ->where('type', $data['type']);
            
            if ($eventId) {
                $query->where('id', '!=', $eventId);
            }

            // Vérifier les chevauchements de dates
            $query->where(function($q) use ($dateDebut, $dateFin) {
                $endDate = $dateFin ?: $dateDebut;
                $q->whereBetween('date_debut', [$dateDebut, $endDate])
                  ->orWhereBetween('date_fin', [$dateDebut, $endDate])
                  ->orWhere(function($subQ) use ($dateDebut, $endDate) {
                      $subQ->where('date_debut', '<=', $dateDebut)
                           ->where(function($dateQ) use ($endDate) {
                               $dateQ->where('date_fin', '>=', $endDate)
                                    ->orWhereNull('date_fin');
                           });
                  });
            });

            $conflictingEvent = $query->first();
            if ($conflictingEvent) {
                $typeLabel = ESBTPEvenementAcademique::TYPES[$data['type']];
                $errors['date_debut'] = "Les dates choisies entrent en conflit avec un autre événement de type \"{$typeLabel}\" : {$conflictingEvent->titre}.";
            }
        }

        // 4. Validation spécifique pour la rentrée/fermeture
        if ($data['type'] === 'rentree') {
            // La rentrée devrait être proche du début de l'année universitaire
            $diffDays = $dateDebut->diffInDays($anneeUniversitaire->start_date);
            if ($diffDays > 30) {
                $errors['date_debut'] = 'La date de rentrée devrait être proche du début de l\'année universitaire (' . 
                    $anneeUniversitaire->start_date->format('d/m/Y') . ').';
            }
        }

        if ($data['type'] === 'fermeture') {
            // La fermeture devrait être proche de la fin de l'année universitaire
            $diffDays = $dateDebut->diffInDays($anneeUniversitaire->end_date);
            if ($diffDays > 30) {
                $errors['date_debut'] = 'La date de fermeture devrait être proche de la fin de l\'année universitaire (' . 
                    $anneeUniversitaire->end_date->format('d/m/Y') . ').';
            }
        }

        return $errors;
    }

    /**
     * Calculer les statistiques
     */
    private function calculerStatistiques($anneeSelectionnee)
    {
        if (!$anneeSelectionnee) {
            return [
                'total_evenements' => 0,
                'evenements_confirmes' => 0,
                'evenements_a_venir' => 0,
                'evenements_en_cours' => 0,
                'par_type' => [],
                'par_mois' => [],
            ];
        }

        $evenements = ESBTPEvenementAcademique::forAnnee($anneeSelectionnee->id)->active()->get();

        return [
            'total_evenements' => $evenements->count(),
            'evenements_confirmes' => $evenements->where('statut', 'confirme')->count(),
            'evenements_a_venir' => $evenements->where('is_upcoming', true)->count(),
            'evenements_en_cours' => $evenements->where('is_current', true)->count(),
            'par_type' => $evenements->groupBy('type')->map->count(),
            'par_mois' => $evenements->groupBy(function($event) {
                return $event->date_debut->format('m');
            })->map->count(),
        ];
    }
}
