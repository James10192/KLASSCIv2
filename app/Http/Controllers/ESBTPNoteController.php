<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\NotificationService;
use Carbon\Carbon;

class ESBTPNoteController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Affiche la liste des notes avec filtre par classe et matière
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Initialize query with proper eager loading
        $query = ESBTPNote::whereHas('evaluation')  // Only fetch notes with valid evaluations
            ->with([
                'evaluation.matiere',
                'evaluation.classe',
                'etudiant',
                'createdBy'
            ]);

        // Apply filters
        if ($request->filled('classe_id')) {
            $query->whereHas('evaluation', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }

        if ($request->filled('matiere_id')) {
            $query->whereHas('evaluation', function($q) use ($request) {
                $q->whereHas('matiere', function($mq) use ($request) {
                    $mq->where('id', $request->matiere_id);
                });
            });
        }

        // Get the paginated results
        $notes = $query->latest()->paginate(50);

        // Get filter options
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $matieres = ESBTPMatiere::orderBy('name')->get();

        return view('esbtp.notes.index', compact('notes', 'classes', 'matieres'));
    }

    /**
     * Affiche le formulaire de création d'une note.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $evaluations = ESBTPEvaluation::with(['classe', 'matiere'])
            ->orderBy('date_evaluation', 'desc')
            ->get();
        $etudiants = ESBTPEtudiant::orderBy('nom')->get();

        // Ajouter un message flash pour tester
        session()->flash('info', 'Formulaire de création de note chargé. Veuillez remplir tous les champs requis.');

        return view('esbtp.notes.create', compact('evaluations', 'etudiants'));
    }

    /**
     * Enregistre une nouvelle note.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'evaluation_id' => 'required|exists:esbtp_evaluations,id',
            'note' => 'required_unless:is_absent,on|numeric|min:0',
            'is_absent' => 'nullable|in:on,1,true',
            'commentaire' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'L\'étudiant est obligatoire',
            'evaluation_id.required' => 'L\'évaluation est obligatoire',
            'note.required_unless' => 'La note est obligatoire si l\'étudiant n\'est pas absent',
            'note.numeric' => 'La note doit être un nombre',
            'note.min' => 'La note doit être positive',
            'is_absent.in' => 'Le statut d\'absence n\'est pas valide',
        ]);

        try {
            // Débogage : Log du début du try
            \Log::info('Début du traitement de la note après validation');

            // Vérifier si l'étudiant a déjà une note pour cette évaluation
            $existingNote = ESBTPNote::where('etudiant_id', $request->etudiant_id)
                ->where('evaluation_id', $request->evaluation_id)
                ->first();

            if ($existingNote) {
                return redirect()->back()
                    ->with('error', 'Cet étudiant a déjà une note pour cette évaluation.')
                    ->withInput();
            }

            // Récupérer l'évaluation pour obtenir le barème et la classe
            $evaluation = ESBTPEvaluation::findOrFail($request->evaluation_id);

            // Récupérer la classe associée à l'évaluation
            $classe_id = $evaluation->classe_id;

            // Récupérer la période de l'évaluation
            $semestre = $evaluation->periode;

            // Convertir is_absent en booléen
            $isAbsent = $request->has('is_absent') && in_array($request->is_absent, ['on', '1', 'true', true]);

            // Créer la note
            $note = new ESBTPNote();
            $note->etudiant_id = $request->etudiant_id;
            $note->evaluation_id = $request->evaluation_id;
            $note->classe_id = $classe_id; // Utiliser la classe de l'évaluation
            $note->matiere_id = $evaluation->matiere_id; // Ajouter le matiere_id de l'évaluation
            $note->semestre = $semestre; // Utiliser la période de l'évaluation
            $note->note = $isAbsent ? 0 : $request->note;
            $note->is_absent = $isAbsent ? 1 : 0;
            $note->commentaire = $request->commentaire;
            $note->created_by = Auth::id();
            $note->type_evaluation = $evaluation->type; // Ajouter le type d'évaluation
            $note->annee_universitaire = $evaluation->anneeUniversitaire ? $evaluation->anneeUniversitaire->name : 'N/A'; // Ajouter l'année universitaire
            $note->save();

            // Envoyer une notification d'absence si l'étudiant est marqué absent
            if ($note->is_absent) {
                $this->sendAbsenceNotificationForNote($note, $evaluation);
            }

            // Débogage : Log des détails de la note créée
            \Log::info('Note créée', [
                'id' => $note->id,
                'etudiant_id' => $note->etudiant_id,
                'evaluation_id' => $note->evaluation_id,
                'note' => $note->note,
                'is_absent' => $note->is_absent,
                'classe_id' => $note->classe_id,
                'semestre' => $note->semestre
            ]);

            return redirect()->route('esbtp.notes.index')
                ->with('success', 'Note créée avec succès.');
        } catch (\Exception $e) {
            // Débogage : Log de l'erreur
            \Log::error('Erreur lors de la création de la note : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la note : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche une note spécifique.
     *
     * @param  \App\Models\ESBTPNote  $note
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPNote $note)
    {
        $note->load(['evaluation.matiere', 'evaluation.classe', 'etudiant', 'createdBy', 'updatedBy']);
        return view('esbtp.notes.show', compact('note'));
    }

    /**
     * Affiche le formulaire de modification d'une note.
     *
     * @param  \App\Models\ESBTPNote  $note
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPNote $note)
    {
        // Vérifier si le coordinateur a le droit de modifier les notes existantes
        $user = Auth::user();
        if ($user->hasRole('coordinateur')) {
            return redirect()->back()
                ->with('error', 'Vous ne pouvez pas modifier les notes déjà enregistrées. Vous pouvez seulement ajouter des notes si aucune n\'existe encore.');
        }

        $note->load(['evaluation.matiere', 'evaluation.classe', 'etudiant']);
        return view('esbtp.notes.edit', compact('note'));
    }

    /**
     * Met à jour une note spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPNote  $note
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPNote $note)
    {
        // Vérifier si le coordinateur a le droit de modifier les notes existantes
        $user = Auth::user();
        if ($user->hasRole('coordinateur')) {
            return redirect()->back()
                ->with('error', 'Vous ne pouvez pas modifier les notes déjà enregistrées. Vous pouvez seulement ajouter des notes si aucune n\'existe encore.');
        }

        $request->validate([
            'note' => 'required_unless:is_absent,on|numeric|min:0',
            'is_absent' => 'nullable|in:on,1,true',
            'commentaire' => 'nullable|string',
        ]);

        try {
            // Récupérer l'évaluation associée à cette note
            $evaluation = $note->evaluation;

            if (!$evaluation) {
                return redirect()->back()
                    ->with('error', 'Évaluation introuvable pour cette note.')
                    ->withInput();
            }

            // Convertir is_absent en booléen
            $isAbsent = $request->has('is_absent') && in_array($request->is_absent, ['on', '1', 'true', true]);

            // Synchroniser le semestre avec la période de l'évaluation
            $note->semestre = $evaluation->periode;

            // Mettre à jour les autres champs
            $note->note = $isAbsent ? 0 : $request->note;
            $note->is_absent = $isAbsent ? 1 : 0;
            $note->commentaire = $request->commentaire;
            $note->updated_by = Auth::id();
            $note->save();

            // Débogage : Log des détails de la note mise à jour
            \Log::info('Note mise à jour', [
                'id' => $note->id,
                'etudiant_id' => $note->etudiant_id,
                'evaluation_id' => $note->evaluation_id,
                'note' => $note->note,
                'is_absent' => $note->is_absent,
                'semestre' => $note->semestre
            ]);

            return redirect()->route('esbtp.notes.index')
                ->with('success', 'Note mise à jour avec succès.');
        } catch (\Exception $e) {
            // Débogage : Log de l'erreur
            \Log::error('Erreur lors de la mise à jour de la note : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la note : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime une note spécifique.
     *
     * @param  \App\Models\ESBTPNote  $note
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPNote $note)
    {
        try {
            $note->delete();
            return redirect()->route('esbtp.notes.index')->with('success', 'Note supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression de la note: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page de saisie rapide des notes pour une évaluation.
     *
     * @param ESBTPEvaluation $evaluation
     * @return \Illuminate\Http\Response
     */
    public function saisieRapide(ESBTPEvaluation $evaluation)
    {
        $evaluation->load(['classe', 'matiere', 'notes.etudiant']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer uniquement les étudiants avec inscriptions actives sur l'année courante
        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($evaluation, $anneeCourante) {
                $query->where('classe_id', $evaluation->classe_id)
                      ->where('status', 'active');
                if ($anneeCourante) {
                    $query->where('annee_universitaire_id', $anneeCourante->id);
                }
            })
            ->with(['notes' => function($query) use ($evaluation) {
                $query->where('evaluation_id', $evaluation->id);
            }])
            ->orderBy('nom')
            ->get();

        // Récupérer uniquement les notes des étudiants de l'année courante pour cette évaluation
        $etudiantsIds = $etudiants->pluck('id');
        $notes = $evaluation->notes->whereIn('etudiant_id', $etudiantsIds);

        return view('esbtp.notes.saisie-rapide', compact('evaluation', 'etudiants', 'notes'));
    }

    /**
     * Génère un PDF pour la saisie rapide des notes.
     *
     * @param ESBTPEvaluation $evaluation
     * @return \Illuminate\Http\Response
     */
    public function saisieRapidePDF(ESBTPEvaluation $evaluation)
    {
        $evaluation->load(['classe', 'matiere']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer uniquement les étudiants avec inscriptions actives sur l'année courante
        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($evaluation, $anneeCourante) {
                $query->where('classe_id', $evaluation->classe_id)
                      ->where('status', 'active');
                if ($anneeCourante) {
                    $query->where('annee_universitaire_id', $anneeCourante->id);
                }
            })
            ->orderBy('nom')
            ->get();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => \App\Models\Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => \App\Models\Setting::get('school_address', ''),
            'telephone' => \App\Models\Setting::get('school_phone', ''),
            'email' => \App\Models\Setting::get('school_email', ''),
            'logo' => \App\Models\Setting::get('school_logo', '')
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.notes.saisie-rapide-pdf', compact('evaluation', 'etudiants', 'anneeCourante', 'etablissement'));

        $filename = 'saisie-notes-' . \Illuminate\Support\Str::slug($evaluation->titre) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Enregistre les notes saisies en masse pour une évaluation.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function enregistrerSaisieRapide(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:esbtp_evaluations,id',
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'notes.*.valeur' => 'required_without:notes.*.absent|nullable|numeric|min:0',
            'notes.*.commentaire' => 'nullable|string',
            'notes.*.absent' => 'nullable|boolean',
        ], [
            'notes.*.valeur.required_without' => 'La valeur de la note est obligatoire si l\'étudiant n\'est pas absent',
            'notes.*.valeur.numeric' => 'La valeur doit être un nombre',
            'notes.*.valeur.min' => 'La valeur doit être positive',
        ]);

        $evaluation = ESBTPEvaluation::findOrFail($request->evaluation_id);

        // Vérifier si le coordinateur a le droit de modifier les notes existantes
        $user = Auth::user();
        if ($user->hasRole('coordinateur')) {
            // Vérifier s'il y a déjà des notes pour cette évaluation
            $existingNotesCount = ESBTPNote::where('evaluation_id', $evaluation->id)->count();

            if ($existingNotesCount > 0) {
                return redirect()->back()
                    ->with('error', 'Vous ne pouvez pas modifier les notes déjà enregistrées. Vous pouvez seulement ajouter des notes si aucune n\'existe encore.')
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            foreach ($request->notes as $noteData) {
                // Vérifier si nous avons une valeur de note ou si l'étudiant est marqué comme absent
                $hasValue = isset($noteData['valeur']) && $noteData['valeur'] !== null && $noteData['valeur'] !== '';
                $isAbsent = isset($noteData['absent']) && $noteData['absent'] == '1';

                // Ignorer les entrées sans valeur et non marquées comme absentes
                if (!$hasValue && !$isAbsent) {
                    continue;
                }

                $etudiantId = $noteData['etudiant_id'];

                // Vérifier si l'étudiant a déjà une note pour cette évaluation
                $note = ESBTPNote::where('evaluation_id', $evaluation->id)
                    ->where('etudiant_id', $etudiantId)
                    ->first();

                if ($note) {
                    // Mise à jour de la note existante
                    $wasAbsent = $note->is_absent;
                    $note->note = $isAbsent ? 0 : $noteData['valeur'];
                    $note->is_absent = $isAbsent;
                    $note->commentaire = $noteData['commentaire'] ?? null;
                    $note->updated_by = Auth::id();

                    // S'assurer que tous les champs requis sont définis
                    if (!$note->matiere_id) {
                        $note->matiere_id = $evaluation->matiere_id;
                    }
                    if (!$note->classe_id) {
                        $note->classe_id = $evaluation->classe_id;
                    }
                    if (!$note->semestre) {
                        $note->semestre = $evaluation->periode;
                    }
                    if (!$note->annee_universitaire) {
                        $note->annee_universitaire = $evaluation->anneeUniversitaire ? $evaluation->anneeUniversitaire->name : 'N/A';
                    }
                    if (!$note->type_evaluation) {
                        $note->type_evaluation = $evaluation->type;
                    }

                    $note->save();

                    // Envoyer une notification d'absence si l'étudiant vient d'être marqué absent
                    if ($isAbsent && !$wasAbsent) {
                        $this->sendAbsenceNotificationForNote($note, $evaluation);
                    }
                } else {
                    // Création d'une nouvelle note
                    $note = new ESBTPNote();
                    $note->evaluation_id = $evaluation->id;
                    $note->etudiant_id = $etudiantId;
                    $note->matiere_id = $evaluation->matiere_id;
                    $note->classe_id = $evaluation->classe_id;
                    $note->semestre = $evaluation->periode;
                    $note->annee_universitaire = $evaluation->anneeUniversitaire ? $evaluation->anneeUniversitaire->name : 'N/A';
                    $note->note = $isAbsent ? 0 : $noteData['valeur'];
                    $note->type_evaluation = $evaluation->type;
                    $note->is_absent = $isAbsent;
                    $note->commentaire = $noteData['commentaire'] ?? null;
                    $note->created_by = Auth::id();
                    $note->save();

                    // Envoyer une notification d'absence si l'étudiant est marqué absent
                    if ($isAbsent) {
                        $this->sendAbsenceNotificationForNote($note, $evaluation);
                    }
                }
            }

            DB::commit();
            return redirect()->route('esbtp.evaluations.show', $evaluation)
                ->with('success', 'Les notes ont été enregistrées avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement des notes: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les notes de l'étudiant connecté.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function studentGrades(Request $request)
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
            ->with(['evaluation', 'matiere'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('esbtp.etudiants.notes', compact('notes', 'etudiant'));
    }

    /**
     * Affiche le formulaire de saisie rapide des notes.
     *
     * @return \Illuminate\Http\Response
     */
    public function saisieRapideForm()
    {
        return view('esbtp.notes.saisie-rapide-form');
    }

    /**
     * Envoie une notification d'absence à un étudiant lors de la saisie des notes
     *
     * @param ESBTPNote $note
     * @param ESBTPEvaluation $evaluation
     * @return void
     */
    private function sendAbsenceNotificationForNote(ESBTPNote $note, ESBTPEvaluation $evaluation)
    {
        try {
            // Charger l'étudiant avec sa relation user
            $etudiant = ESBTPEtudiant::with('user')->find($note->etudiant_id);

            // S'assurer que l'étudiant existe et a un compte utilisateur
            if (!$etudiant || !$etudiant->user) {
                \Log::warning("Impossible d'envoyer la notification d'absence pour la note: étudiant ou utilisateur non trouvé", [
                    'etudiant_id' => $note->etudiant_id,
                    'note_id' => $note->id
                ]);
                return;
            }

            // Charger la matière associée à l'évaluation
            $matiere = $evaluation->matiere;
            $matiereName = $matiere ? $matiere->name : 'Matière non définie';

            // Formater la date et l'heure
            $dateEvaluation = $evaluation->date_evaluation ? \Carbon\Carbon::parse($evaluation->date_evaluation) : \Carbon\Carbon::now();
            $jourSemaine = $dateEvaluation->locale('fr')->dayName;
            $dateFormatee = $dateEvaluation->format('d/m/Y');
            $heureFormatee = $evaluation->heure_debut ? $evaluation->heure_debut : 'Heure non définie';

            // Déterminer le type d'activité
            $typeActivite = 'Évaluation';
            $typeEvaluation = ucfirst($evaluation->type ?? 'évaluation');

            // Créer un message détaillé
            $messageDetail = sprintf(
                "Absence lors d'une %s (%s)\n" .
                "Matière: %s\n" .
                "Date: %s (%s)\n" .
                "Heure: %s\n" .
                "Titre: %s",
                strtolower($typeActivite),
                $typeEvaluation,
                $matiereName,
                $dateFormatee,
                ucfirst($jourSemaine),
                $heureFormatee,
                $evaluation->titre ?? 'Sans titre'
            );

            // Créer une entrée d'absence temporaire pour la notification avec informations enrichies
            $absence = new \App\Models\ESBTPAttendance();
            $absence->date = $dateEvaluation;
            $absence->etudiant_id = $note->etudiant_id;
            $absence->statut = 'absent';
            $absence->commentaire = $messageDetail;
            $absence->matiere_id = $evaluation->matiere_id;
            $absence->type_activite = 'evaluation';
            $absence->heure_debut = $evaluation->heure_debut;
            $absence->heure_fin = $evaluation->heure_fin;

            // Utiliser le service de notifications
            $this->notificationService->notifyNewAbsence($absence, $etudiant);

            \Log::info("Notification d'absence enrichie envoyée pour la note", [
                'etudiant_id' => $note->etudiant_id,
                'note_id' => $note->id,
                'evaluation_id' => $evaluation->id,
                'matiere' => $matiereName,
                'date' => $dateFormatee,
                'jour' => $jourSemaine,
                'heure' => $heureFormatee,
                'type' => $typeEvaluation
            ]);

        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de la notification d'absence pour la note", [
                'etudiant_id' => $note->etudiant_id,
                'note_id' => $note->id,
                'evaluation_id' => $evaluation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
