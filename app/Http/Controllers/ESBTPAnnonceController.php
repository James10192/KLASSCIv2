<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Carbon\Carbon;
use App\Notifications\ESBTPNotification;
use App\Services\NotificationService;

class ESBTPAnnonceController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Affiche la liste des annonces.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $annonces = ESBTPAnnonce::with(['classes', 'etudiants', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Préparation des statistiques
        $stats = [
            'total' => ESBTPAnnonce::count(),
            'published' => ESBTPAnnonce::where('is_published', true)->count(),
            'pending' => ESBTPAnnonce::where('is_published', false)->count(),
            'urgent' => ESBTPAnnonce::where('priorite', 2)->count()
        ];

        return view('esbtp.annonces.index', compact('annonces', 'stats'));
    }

    /**
     * Affiche le formulaire de création d'une annonce.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $etudiants = ESBTPEtudiant::with('classe')
            ->whereHas('classe')  // Exclure les étudiants sans classe affectée
            ->distinct()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get();

        return view('esbtp.annonces.create', compact('classes', 'etudiants', 'filieres', 'niveaux'));
    }

    /**
     * Enregistre une nouvelle annonce.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'date_publication' => 'nullable|date',
            'date_expiration' => 'required|date|after_or_equal:date_publication',
            'type' => 'required|in:general,classe,etudiant',
            'priorite' => 'required|in:0,1,2',
            'classes' => 'required_if:type,classe|array',
            'etudiants' => 'required_if:type,etudiant|array',
            'piece_jointe' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
        ], [
            'titre.required' => 'Le titre est obligatoire',
            'contenu.required' => 'Le contenu est obligatoire',
            'date_expiration.required' => 'La date d\'expiration est obligatoire',
            'date_expiration.after_or_equal' => 'La date d\'expiration doit être postérieure ou égale à la date de publication',
            'priorite.required' => 'La priorité est obligatoire',
            'classes.required_if' => 'Veuillez sélectionner au moins une classe',
            'etudiants.required_if' => 'Veuillez sélectionner au moins un étudiant',
            'piece_jointe.mimes' => 'Le fichier doit être au format PDF, Word, Excel ou image (JPG, PNG)',
            'piece_jointe.max' => 'Le fichier ne doit pas dépasser 5 MB',
        ]);

        DB::beginTransaction();
        try {
            $annonce = new ESBTPAnnonce();
            $annonce->titre = $request->titre;
            $annonce->contenu = $request->contenu;
            $annonce->date_publication = $request->date_publication ?? now();
            $annonce->date_expiration = $request->date_expiration;
            $annonce->type = $request->type;
            $annonce->priorite = $request->priorite;
            $annonce->is_published = $request->get('is_published') == '1';
            $annonce->created_by = Auth::id();

            // Handle file upload
            if ($request->hasFile('piece_jointe')) {
                $file = $request->file('piece_jointe');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('annonces', $filename, 'public');
                $annonce->piece_jointe = $path;
            }

            $annonce->save();

            // Attacher les classes ou les étudiants selon le type
            if ($request->type == 'classe' && $request->has('classes')) {
                $annonce->classes()->attach($request->classes);
            } elseif ($request->type == 'etudiant' && $request->has('etudiants')) {
                $annonce->etudiants()->attach($request->etudiants);
            }

            // Envoyer des notifications si l'annonce est publiée
            if ($annonce->is_published && $annonce->date_publication <= now()) {
                $this->sendAnnonceNotification($annonce);
            }

            DB::commit();
            return redirect()->route('esbtp.annonces.index')
                ->with('success', 'L\'annonce a été créée avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'annonce: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche une annonce spécifique.
     *
     * @param  \App\Models\ESBTPAnnonce  $annonce
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPAnnonce $annonce)
    {
        $annonce->load(['classes', 'etudiants', 'user']);
        return view('esbtp.annonces.show', compact('annonce'));
    }

    /**
     * Vérifie si une annonce peut encore être modifiée (< 15 min après publication)
     */
    private function canEditAnnonce($annonce)
    {
        if (!$annonce->is_published) {
            return true; // Brouillons toujours modifiables
        }

        $publishedAt = $annonce->created_at;
        if ($annonce->date_publication && $annonce->date_publication > $annonce->created_at) {
            $publishedAt = $annonce->date_publication;
        }

        return $publishedAt->diffInMinutes(now()) <= 15;
    }

    /**
     * Affiche le formulaire de modification d'une annonce.
     *
     * @param  \App\Models\ESBTPAnnonce  $annonce
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPAnnonce $annonce)
    {
        // Vérification règle 15 minutes
        if (!$this->canEditAnnonce($annonce)) {
            $publishedAt = $annonce->created_at;
            if ($annonce->date_publication && $annonce->date_publication > $annonce->created_at) {
                $publishedAt = $annonce->date_publication;
            }
            $minutesElapsed = $publishedAt->diffInMinutes(now());

            return redirect()->route('esbtp.annonces.show', $annonce)
                ->with('error', "Cette annonce ne peut plus être modifiée (publiée il y a {$minutesElapsed} minutes). Vous pouvez la supprimer et en créer une nouvelle.");
        }

        // Chargement des données avec même structure que create
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $etudiants = ESBTPEtudiant::with('classe')
            ->whereHas('classe')
            ->distinct()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get();

        return view('esbtp.annonces.edit', compact('annonce', 'classes', 'etudiants', 'filieres', 'niveaux'));
    }

    /**
     * Met à jour une annonce spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPAnnonce  $annonce
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPAnnonce $annonce)
    {
        // Double vérification règle 15 minutes
        if (!$this->canEditAnnonce($annonce)) {
            return redirect()->route('esbtp.annonces.show', $annonce)
                ->with('error', 'Cette annonce ne peut plus être modifiée.');
        }
        $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'date_publication' => 'nullable|date',
            'date_expiration' => 'required|date|after_or_equal:date_publication',
            'type' => 'required|in:general,classe,etudiant',
            'priorite' => 'required|in:0,1,2',
            'classes' => 'required_if:type,classe|array',
            'etudiants' => 'required_if:type,etudiant|array',
            'piece_jointe' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
        ], [
            'titre.required' => 'Le titre est obligatoire',
            'contenu.required' => 'Le contenu est obligatoire',
            'date_expiration.required' => 'La date d\'expiration est obligatoire',
            'date_expiration.after_or_equal' => 'La date d\'expiration doit être postérieure ou égale à la date de publication',
            'priorite.required' => 'La priorité est obligatoire',
            'classes.required_if' => 'Veuillez sélectionner au moins une classe',
            'etudiants.required_if' => 'Veuillez sélectionner au moins un étudiant',
            'piece_jointe.mimes' => 'Le fichier doit être au format PDF, Word, Excel ou image (JPG, PNG)',
            'piece_jointe.max' => 'Le fichier ne doit pas dépasser 5 MB',
        ]);

        DB::beginTransaction();
        try {
            $wasPublished = $annonce->is_published;

            $annonce->titre = $request->titre;
            $annonce->contenu = $request->contenu;
            $annonce->date_publication = $request->date_publication ?? $annonce->date_publication ?? now();
            $annonce->date_expiration = $request->date_expiration;
            $annonce->type = $request->type;
            $annonce->priorite = $request->priorite;
            $annonce->is_published = $request->get('is_published') == '1';
            $annonce->updated_by = Auth::id();

            // Handle file upload
            if ($request->hasFile('piece_jointe')) {
                // Delete old file if exists
                if ($annonce->piece_jointe && \Storage::disk('public')->exists($annonce->piece_jointe)) {
                    \Storage::disk('public')->delete($annonce->piece_jointe);
                }
                
                // Upload new file
                $file = $request->file('piece_jointe');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('annonces', $filename, 'public');
                $annonce->piece_jointe = $path;
            }

            $annonce->save();

            // Mettre à jour les associations
            $annonce->classes()->detach();
            $annonce->etudiants()->detach();

            if ($request->type == 'classe' && $request->has('classes')) {
                $annonce->classes()->attach($request->classes);
            } elseif ($request->type == 'etudiant' && $request->has('etudiants')) {
                $annonce->etudiants()->attach($request->etudiants);
            }

            // Envoyer des notifications si l'annonce devient publiée et est prévue pour maintenant ou le passé
            if ($annonce->is_published && !$wasPublished && $annonce->date_publication <= now()) {
                $this->sendAnnonceNotification($annonce);
            }

            DB::commit();
            return redirect()->route('esbtp.annonces.index')
                ->with('success', 'L\'annonce a été mise à jour avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'annonce: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime une annonce spécifique.
     *
     * @param  \App\Models\ESBTPAnnonce  $annonce
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPAnnonce $annonce)
    {
        try {
            // Détacher d'abord toutes les relations
            $annonce->classes()->detach();
            $annonce->etudiants()->detach();

            // Supprimer le fichier associé s'il existe
            if ($annonce->piece_jointe && \Storage::disk('public')->exists($annonce->piece_jointe)) {
                \Storage::disk('public')->delete($annonce->piece_jointe);
            }

            // Puis supprimer l'annonce
            $annonce->delete();

            return redirect()->route('esbtp.annonces.index')
                ->with('success', 'L\'annonce a été supprimée avec succès');
        } catch (\Exception $e) {
            return redirect()->route('esbtp.annonces.index')
                ->with('error', 'Une erreur est survenue lors de la suppression de l\'annonce: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les messages pour un étudiant
     *
     * @return \Illuminate\Http\Response
     */
    public function studentMessages()
    {
        // Récupérer l'étudiant connecté
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->firstOrFail();

        // Récupérer la classe active de l'étudiant
        $classeActive = $etudiant->classe_active;
        $classeId = $classeActive ? $classeActive->id : null;

        // Récupérer tous les messages pertinents pour l'étudiant
        $query = ESBTPAnnonce::where('is_published', true)
            ->where('date_publication', '<=', now())
            ->where(function($q) use ($classeId, $etudiant) {
                // Messages généraux
                $q->where('type', 'general');

                // Messages pour la classe de l'étudiant (si disponible)
                if ($classeId) {
                    $q->orWhere(function($sq) use ($classeId) {
                        $sq->where('type', 'classe')
                           ->whereHas('classes', function($cq) use ($classeId) {
                                $cq->where('esbtp_classes.id', $classeId);
                           });
                    });
                }

                // Messages spécifiques à l'étudiant
                $q->orWhere(function($sq) use ($etudiant) {
                    $sq->where('type', 'etudiant')
                       ->whereHas('etudiants', function($eq) use ($etudiant) {
                            $eq->where('esbtp_etudiants.id', $etudiant->id);
                       });
                });
            })
            ->where(function($q) {
                // Seulement les messages non expirés ou sans date d'expiration
                $q->whereNull('date_expiration')
                  ->orWhere('date_expiration', '>=', now());
            });

        // Tri par priorité (descendant) puis par date (plus récent d'abord)
        $messages = $query->orderBy('priorite', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(10);

        // Pour chaque message, déterminer s'il a été lu par l'étudiant
        foreach ($messages as $message) {
            if ($message->type == 'etudiant') {
                $pivot = $message->etudiants()->wherePivot('etudiant_id', $etudiant->id)->first();
                if ($pivot) {
                    $message->is_read = $pivot->pivot->is_read;
                    $message->read_at = $pivot->pivot->read_at;
                } else {
                    $message->is_read = false;
                    $message->read_at = null;
                }
            } else {
                // Pour les messages généraux et de classe, vérifier dans la table pivot
                $readStatus = DB::table('esbtp_annonce_lectures')
                    ->where('annonce_id', $message->id)
                    ->where('etudiant_id', $etudiant->id)
                    ->first();

                $message->is_read = $readStatus ? true : false;
                $message->read_at = $readStatus ? $readStatus->read_at : null;
            }
        }

        // Statistiques des messages
        $stats = [
            'total' => $messages->total(),
            'unread' => $query->whereDoesntHave('lectures', function($q) use ($etudiant) {
                $q->where('etudiant_id', $etudiant->id);
            })->count(),
            'urgent' => $query->where('priorite', 2)->count()
        ];

        return view('esbtp.annonces.student-messages', compact('messages', 'stats', 'etudiant'));
    }

    /**
     * Marque un message comme lu par l'étudiant connecté
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->firstOrFail();
        $annonce = ESBTPAnnonce::findOrFail($id);

        try {
            DB::beginTransaction();

            if ($annonce->type == 'etudiant') {
                // Pour les messages spécifiques aux étudiants
                $annonce->marquerCommeLue($etudiant->id);
            } else {
                // Pour les messages généraux et de classe
                // Vérifier si une entrée existe déjà
                $exists = DB::table('esbtp_annonce_lectures')
                    ->where('annonce_id', $id)
                    ->where('etudiant_id', $etudiant->id)
                    ->exists();

                if (!$exists) {
                    // Créer une nouvelle entrée
                    DB::table('esbtp_annonce_lectures')->insert([
                        'annonce_id' => $id,
                        'etudiant_id' => $etudiant->id,
                        'read_at' => Carbon::now(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
            }

            DB::commit();

            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->back()->with('success', 'Message marqué comme lu.');
        } catch (\Exception $e) {
            DB::rollBack();

            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }

    /**
     * Marque tous les messages comme lus par l'étudiant connecté
     *
     * @return \Illuminate\Http\Response
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            // Récupérer tous les messages non lus de l'étudiant
            $query = ESBTPAnnonce::where('is_published', true)
                ->where('date_publication', '<=', now())
                ->where(function($q) use ($etudiant) {
                    // Messages généraux
                    $q->where('type', 'general');

                    // Messages pour la classe de l'étudiant
                    if ($etudiant->classe_active) {
                        $q->orWhere(function($sq) use ($etudiant) {
                            $sq->where('type', 'classe')
                               ->whereHas('classes', function($cq) use ($etudiant) {
                                    $cq->where('esbtp_classes.id', $etudiant->classe_active->id);
                               });
                        });
                    }

                    // Messages spécifiques à l'étudiant
                    $q->orWhere(function($sq) use ($etudiant) {
                        $sq->where('type', 'etudiant')
                           ->whereHas('etudiants', function($eq) use ($etudiant) {
                                $eq->where('esbtp_etudiants.id', $etudiant->id);
                           });
                    });
                })
                ->whereDoesntHave('lectures', function($q) use ($etudiant) {
                    $q->where('etudiant_id', $etudiant->id);
                });

            $messages = $query->get();

            // Marquer tous les messages comme lus
            foreach ($messages as $message) {
                if ($message->type == 'etudiant') {
                    $message->marquerCommeLue($etudiant->id);
                } else {
                    // Pour les messages généraux et de classe
                    DB::table('esbtp_annonce_lectures')->insert([
                        'annonce_id' => $message->id,
                        'etudiant_id' => $etudiant->id,
                        'read_at' => Carbon::now(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
            }

            DB::commit();

            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->back()->with('success', 'Tous les messages ont été marqués comme lus.');
        } catch (\Exception $e) {
            DB::rollBack();

            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }

    /**
     * Envoie des notifications aux étudiants concernés par une annonce
     *
     * @param ESBTPAnnonce $annonce
     * @return void
     */
    private function sendAnnonceNotification(ESBTPAnnonce $annonce)
    {
        // Utiliser le service de notifications centralisé avec l'expéditeur
        $this->notificationService->notifyNewAnnouncement($annonce, Auth::user());
    }
}
