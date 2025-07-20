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
