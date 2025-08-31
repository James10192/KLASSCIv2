<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ESBTPParent;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPResultat;
use App\Models\User;
use App\Models\Classe;
use PDF;
use App\Models\ESBTPFiliere;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use App\Models\ESBTPConfigMatiere;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\ESBTPCategorie;
use App\Models\ESBTPCertificat;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPCycle;
use Carbon\Carbon;
use App\Services\ESBTP\ESBTPAbsenceService;

class ESBTPBulletinController extends Controller
{
    protected $absenceService;

    public function __construct(ESBTPAbsenceService $absenceService)
    {
        $this->absenceService = $absenceService;
    }

    /**
     * Affiche la liste des bulletins avec filtre par année et classe
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Périodes disponibles (définir les périodes pour la vue)
        $periodes = [
            (object)['id' => 'semestre1', 'nom' => 'Premier Semestre', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)],
            (object)['id' => 'semestre2', 'nom' => 'Deuxième Semestre', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)],
            (object)['id' => 'annuel', 'nom' => 'Annuel', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)]
        ];

        // Statistiques pour les widgets
        $stats = [
            'total' => ESBTPBulletin::count(),
            'published' => ESBTPBulletin::where('is_published', true)->count(),
            'pending' => ESBTPBulletin::where('is_published', false)->count(),
            'periodes' => count($periodes)
        ];

        // Valeurs par défaut filtre
        $classe_id = $request->input('classe_id');
        $annee_id = $request->input('annee_universitaire_id',
            ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null);
        $periode_id = $request->input('periode_id');

        $query = ESBTPBulletin::with(['etudiant', 'classe', 'anneeUniversitaire']);

        // Application des filtres
        if ($classe_id) {
            $query->where('classe_id', $classe_id);
        }

        if ($annee_id) {
            $query->where('annee_universitaire_id', $annee_id);
        }

        if ($periode_id) {
            $query->where('periode', $periode_id);
        }

        // Utiliser paginate() au lieu de get() pour permettre l'utilisation de appends()
        $bulletins = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('esbtp.bulletins.index', compact(
            'bulletins',
            'classes',
            'anneesUniversitaires',
            'classe_id',
            'annee_id',
            'periodes',
            'periode_id',
            'stats'
        ));
    }

    /**
     * Affiche le formulaire de sélection d'étudiant pour créer un bulletin
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeActuelle = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        return view('esbtp.bulletins.create', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Enregistre un nouveau bulletin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
            'appreciation_generale' => 'nullable|string',
            'decision_conseil' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'L\'étudiant est obligatoire',
            'classe_id.required' => 'La classe est obligatoire',
            'annee_universitaire_id.required' => 'L\'année universitaire est obligatoire',
            'periode.required' => 'La période est obligatoire',
        ]);

        DB::beginTransaction();
        try {
            // Vérifier si l'étudiant est bien inscrit dans cette classe pour cette année
            $etudiantInscrit = ESBTPEtudiant::findOrFail($request->etudiant_id)
                ->inscriptions()
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->exists();

            if (!$etudiantInscrit) {
                return redirect()->back()
                    ->with('error', 'L\'étudiant n\'est pas inscrit dans cette classe pour cette année universitaire')
                    ->withInput();
            }

            // Vérifier s'il existe déjà un bulletin pour cet étudiant, cette classe, cette année et cette période
            $bulletinExistant = ESBTPBulletin::where('etudiant_id', $request->etudiant_id)
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->where('periode', $request->periode)
                ->exists();

            if ($bulletinExistant) {
                return redirect()->back()
                    ->with('error', 'Un bulletin existe déjà pour cet étudiant pour cette période')
                    ->withInput();
            }

            // Créer le bulletin
            $bulletin = new ESBTPBulletin();
            $bulletin->etudiant_id = $request->etudiant_id;
            $bulletin->classe_id = $request->classe_id;
            $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
            $bulletin->periode = $request->periode;
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->user_id = Auth::id();
            $bulletin->save();

            // Récupérer toutes les matières de la classe
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $matieres = $classe->matieres;

            // Pour chaque matière, calculer la moyenne et créer un résultat
            foreach ($matieres as $matiere) {
                // Récupérer toutes les évaluations de cette matière pour cette classe
                $evaluations = $matiere ? $matiere->evaluations()
                    ->where('classe_id', $classe->id)
                    ->where('periode', $request->periode)
                    ->get() : collect();

                Log::info('Récupération des évaluations', [
                    'matiere_id' => $matiere->id,
                    'nombre_evaluations' => $evaluations->count(),
                    'classe_id' => $classe->id,
                    'periode' => $request->periode
                ]);

                if (!$evaluations || $evaluations->isEmpty()) {
                    continue; // Passer à la matière suivante s'il n'y a pas d'évaluations
                }

                // Récupérer les notes de l'étudiant pour ces évaluations
                $notes = ESBTPNote::whereIn('evaluation_id', $evaluations->pluck('id'))
                    ->where('etudiant_id', $request->etudiant_id)
                    ->get();

                if (!$notes || $notes->isEmpty()) {
                    continue; // Passer à la matière suivante s'il n'y a pas de notes
                }

                // Calculer la moyenne
                $sommeNotes = 0;
                $sommeCoefficients = 0;

                foreach ($notes as $note) {
                    $evaluation = $evaluations->where('id', $note->evaluation_id)->first();
                    $sommeNotes += ($note->valeur / $evaluation->bareme) * 20 * $evaluation->coefficient;
                    $sommeCoefficients += $evaluation->coefficient;
                }

                $moyenne = $sommeCoefficients > 0 ? $sommeNotes / $sommeCoefficients : null;

                // Récupérer le coefficient de la matière pour cette classe
                $pivotData = $classe->matieres()->where('matiere_id', $matiere->id)->first()->pivot;
                $coefficient = $pivotData->coefficient ?? 1;

                // Créer le résultat pour cette matière
                $resultat = new ESBTPResultatMatiere();
                $resultat->bulletin_id = $bulletin->id;
                $resultat->matiere_id = $matiere->id;
                $resultat->moyenne = $moyenne;
                $resultat->coefficient = $coefficient;
                $resultat->commentaire = null;
                $resultat->save();
            }

            // Calculer et mettre à jour la moyenne générale du bulletin
            $this->calculerMoyenneGenerale($bulletin);

            // Déterminer la période pour le calcul des absences
            // Par exemple: utiliser la date de début et de fin du semestre
            $anneeUniversitaire = ESBTPAnneeUniversitaire::find($request->annee_universitaire_id);
            if ($anneeUniversitaire) {
                // Exemple: si periode = 'S1' (1er semestre)
                if ($request->periode == 'S1') {
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = Carbon::parse($dateDebut)->addMonths(4)->format('Y-m-d'); // Environ 4 mois pour un semestre
                } else if ($request->periode == 'S2') {
                    $dateDebut = Carbon::parse($anneeUniversitaire->date_debut)->addMonths(4)->format('Y-m-d');
                    $dateFin = $anneeUniversitaire->date_fin;
                } else {
                    // Pour les périodes différentes ou périodes trimestrielles
                    // Adapter la logique selon vos besoins
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = $anneeUniversitaire->date_fin;
                }

                // Calculer les absences pour la période du bulletin
                $donneeAbsences = $this->calculerAbsencesPourBulletin(
                    $request->etudiant_id,
                    $request->classe_id,
                    $dateDebut,
                    $dateFin
                );

                // Intégrer les absences au bulletin
                $bulletin = $this->integrerAbsencesAuBulletin($bulletin, $donneeAbsences);
            }

            DB::commit();
            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a été créé avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création du bulletin: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Calcule et met à jour la moyenne générale d'un bulletin
     */
    private function calculerMoyenneGenerale(ESBTPBulletin $bulletin)
    {
        Log::info('Calcul de la moyenne générale pour le bulletin ' . $bulletin->id);

        try {
            $resultats = $bulletin->resultats;
            Log::info('Nombre de résultats trouvés: ' . $resultats->count());

            if ($resultats->isEmpty()) {
                Log::info('Aucun résultat trouvé pour le bulletin ' . $bulletin->id);
                $bulletin->moyenne_generale = null;
                $bulletin->save();
                return;
            }

            $sommePoints = 0;
            $sommeCoefficients = 0;

            foreach ($resultats as $resultat) {
                if ($resultat->moyenne !== null) {
                    Log::info('Résultat pour matière ' . $resultat->matiere_id . ': moyenne=' . $resultat->moyenne . ', coefficient=' . $resultat->coefficient);
                    $sommePoints += $resultat->moyenne * $resultat->coefficient;
                    $sommeCoefficients += $resultat->coefficient;
                } else {
                    Log::info('Résultat ignoré pour matière ' . $resultat->matiere_id . ' (moyenne null)');
                }
            }

            Log::info('Somme des points: ' . $sommePoints . ', Somme des coefficients: ' . $sommeCoefficients);
            $moyenneGenerale = $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : null;
            Log::info('Moyenne générale calculée: ' . $moyenneGenerale);

            $bulletin->moyenne_generale = $moyenneGenerale;
            $bulletin->save();
            Log::info('Moyenne générale enregistrée pour le bulletin ' . $bulletin->id);

            // Calculer le rang si la moyenne a changé
            $this->calculerRang($bulletin);
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul de la moyenne générale: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Calcule et met à jour le rang de l'étudiant dans sa classe
     */
    private function calculerRang($bulletin)
    {
        // Récupérer tous les bulletins de la même classe pour la même période
        $bulletins = ESBTPBulletin::where('classe_id', $bulletin->classe_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->where('periode', $bulletin->periode)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        // Mettre à jour l'effectif de la classe
        $bulletin->effectif_classe = $bulletins->count();

        // Trouver le rang de l'étudiant
        foreach ($bulletins as $index => $b) {
            if ($b->id === $bulletin->id) {
                $bulletin->rang = $index + 1;
                break;
            }
        }

        $bulletin->save();
    }

    /**
     * Affiche un bulletin spécifique.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPBulletin $bulletin)
    {
        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere', 'user']);
        return view('esbtp.bulletins.show', compact('bulletin'));
    }

    /**
     * Affiche le formulaire de modification d'un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPBulletin $bulletin)
    {
        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere']);
        return view('esbtp.bulletins.edit', compact('bulletin'));
    }

    /**
     * Met à jour un bulletin spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPBulletin $bulletin)
    {
        $request->validate([
            'resultats' => 'required|array',
            'resultats.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne' => 'nullable|numeric|min:0|max:20',
            'resultats.*.coefficient' => 'required|numeric|min:0',
            'resultats.*.commentaire' => 'nullable|string',
            'appreciation_generale' => 'nullable|string',
            'decision_conseil' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Mettre à jour les informations du bulletin
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->save();

            // Mettre à jour les résultats par matière
            foreach ($request->resultats as $resultatData) {
                $matiereId = $resultatData['matiere_id'];
                $moyenne = $resultatData['moyenne'] !== null && $resultatData['moyenne'] !== ''
                    ? $resultatData['moyenne'] : null;

                $resultat = ESBTPResultatMatiere::where('bulletin_id', $bulletin->id)
                    ->where('matiere_id', $matiereId)
                    ->first();

                if ($resultat) {
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                } else {
                    $resultat = new ESBTPResultatMatiere();
                    $resultat->bulletin_id = $bulletin->id;
                    $resultat->matiere_id = $matiereId;
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                }
            }

            // Recalculer la moyenne générale
            $this->calculerMoyenneGenerale($bulletin);

            DB::commit();
            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a été mis à jour avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du bulletin: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime un bulletin spécifique.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPBulletin $bulletin)
    {
        try {
            $bulletin->delete();
            return redirect()->route('esbtp.bulletins.index')->with('success', 'Bulletin supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Génère un PDF du bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function genererPDF(ESBTPBulletin $bulletin)
    {
        try {
            Log::info('Début de la génération du PDF pour le bulletin #' . $bulletin->id);

            // Charger toutes les relations nécessaires avec eager loading, y compris les relations imbriquées
            $bulletin->load([
                'etudiant',
                'classe.niveauEtude',
                'classe.filiere',
                'anneeUniversitaire',
                'resultats.matiere',
                'user'
            ]);

            // Vérifier que les relations essentielles sont chargées
            if (!$bulletin->etudiant) {
                Log::error('Relation etudiant manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("L'étudiant associé à ce bulletin n'a pas été trouvé. Veuillez vérifier que l'étudiant existe et est correctement associé au bulletin.");
            }

            if (!$bulletin->classe) {
                Log::error('Relation classe manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("La classe associée à ce bulletin n'a pas été trouvée. Veuillez vérifier que la classe existe et est correctement associée au bulletin.");
            }

            if (!$bulletin->anneeUniversitaire) {
                Log::error('Relation anneeUniversitaire manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("L'année universitaire associée à ce bulletin n'a pas été trouvée. Veuillez vérifier que l'année universitaire existe et est correctement associée au bulletin.");
            }

            // Calculer la moyenne générale si pas déjà fait
            if (!$bulletin->moyenne_generale) {
                try {
                    $bulletin->calculerMoyenneGenerale();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la moyenne générale: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->moyenne_generale = 0;
                }
            }

            // Calculer la mention si pas déjà fait
            if (!$bulletin->mention) {
                try {
                    $bulletin->calculerMention();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la mention: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->mention = 'Non calculée';
                }
            }

            // Calculer le rang si pas déjà fait
            if (!$bulletin->rang) {
                try {
                    $bulletin->calculerRang();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul du rang: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->rang = 0;
                }
            }

            // Calculer les absences justifiées et non justifiées
            try {
                $absences = $this->calculerAbsencesDetailees($bulletin);
                $bulletin->absences_justifiees = $absences['justifiees'];
                $bulletin->absences_non_justifiees = $absences['non_justifiees'];
                $bulletin->total_absences = $absences['total'];
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des absences: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $bulletin->absences_justifiees = 0;
                $bulletin->absences_non_justifiees = 0;
                $bulletin->total_absences = 0;
            }

            // Si les absences sont toujours à zéro, essayer la méthode basée sur l'attendance
            if ($bulletin->absences_justifiees == 0 && $bulletin->absences_non_justifiees == 0) {
                try {
                    Log::info('Tentative de calcul des absences via le service pour le bulletin #' . $bulletin->id);

                    $absencesAttendance = $this->absenceService->calculerDetailAbsences(
                        $bulletin->etudiant_id,
                        $bulletin->classe_id,
                        $bulletin->anneeUniversitaire->date_debut,
                        $bulletin->anneeUniversitaire->date_fin
                    );

                    $bulletin->absences_justifiees = $absencesAttendance['justifiees'];
                    $bulletin->absences_non_justifiees = $absencesAttendance['non_justifiees'];
                    $bulletin->total_absences = $absencesAttendance['total'];
                    Log::info('Calcul des absences via le service réussi: ' . json_encode($absencesAttendance));
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul des absences via le service: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                }
            }

            // Grouper les résultats par type d'enseignement (général ou technique)
            try {
                // S'assurer que les résultats sont chargés
                if ($bulletin->resultats->isEmpty()) {
                    Log::warning('Aucun résultat trouvé pour le bulletin #' . $bulletin->id);
                }

                // Vérifier que chaque résultat a une matière associée
                foreach ($bulletin->resultats as $resultat) {
                    if (!$resultat->matiere) {
                        Log::warning('Résultat #' . $resultat->id . ' sans matière associée pour le bulletin #' . $bulletin->id);
                    }
                }

                $resultatsGeneraux = $bulletin->resultats->filter(function($resultat) {
                    return $resultat->matiere && $resultat->matiere->type_formation == 'generale';
                });

                $resultatsTechniques = $bulletin->resultats->filter(function($resultat) {
                    return $resultat->matiere && $resultat->matiere->type_formation == 'technologique_professionnelle';
                });

                // Vérifier si des résultats ont été trouvés après filtrage
                if ($resultatsGeneraux->isEmpty() && $resultatsTechniques->isEmpty()) {
                    Log::warning('Aucun résultat trouvé après filtrage par type de formation pour le bulletin #' . $bulletin->id);
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors du filtrage des résultats: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
            }

            // Calculer les moyennes par type d'enseignement
            try {
                $moyenneGenerale = $bulletin->calculerMoyenneParType('generale');
                $moyenneTechnique = $bulletin->calculerMoyenneParType('technologique_professionnelle');
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des moyennes par type: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $moyenneGenerale = 0;
                $moyenneTechnique = 0;
            }

            // Générer le PDF avec les configurations de l'école
            $data = [
                'bulletin' => $bulletin,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneTechnique' => $moyenneTechnique,
                'absencesJustifiees' => $bulletin->absences_justifiees,
                'absencesNonJustifiees' => $bulletin->absences_non_justifiees,
                'absences_justifiees' => $bulletin->absences_justifiees,
                'absences_non_justifiees' => $bulletin->absences_non_justifiees,
                'config' => [
                    'school_name' => config('school.name', 'École Spéciale du Bâtiment et des Travaux Publics'),
                    'school_type' => config('school.type', 'Enseignement Supérieur Technique'),
                    'school_authorization' => config('school.authorization', ''),
                    'school_address' => config('school.address', 'BP 2541 Yamoussoukro'),
                    'school_phone' => config('school.phone', 'Tél/Fax: 30 64 39 93 - Cel: 05 93 34 26 : 07 72 88 56'),
                    'school_email' => config('school.email', 'esbtp@aviso.ci'),
                    'school_logo' => config('school.logo', 'images/esbtp_logo.png'),
                ]
            ];

            // Log des variables d'absences pour debugging
            Log::info('Variables d\'absence pour le PDF dans genererPDF:', [
                'bulletin_absences_justifiees' => $bulletin->absences_justifiees ?? 'Non défini',
                'bulletin_absences_non_justifiees' => $bulletin->absences_non_justifiees ?? 'Non défini',
                'data_absencesJustifiees' => $data['absencesJustifiees'] ?? 'Non défini',
                'data_absencesNonJustifiees' => $data['absencesNonJustifiees'] ?? 'Non défini',
                'data_absences_justifiees' => $data['absences_justifiees'] ?? 'Non défini',
                'data_absences_non_justifiees' => $data['absences_non_justifiees'] ?? 'Non défini',
            ]);

            // Debugging des chemins pour le logo
            Log::info('Chemin du logo configuré: ' . $data['config']['school_logo']);
            Log::info('Chemin absolu du logo: ' . public_path($data['config']['school_logo']));
            Log::info('Le fichier existe: ' . (file_exists(public_path($data['config']['school_logo'])) ? 'Oui' : 'Non'));

            // Conversion du logo en base64 pour DomPDF
            $logoPath = public_path($data['config']['school_logo']);
            if (file_exists($logoPath)) {
                $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
                $data['logoBase64'] = $logoBase64;
                Log::info('Logo converti en base64');
            } else {
                $data['logoBase64'] = null;
                Log::warning('Logo non trouvé: ' . $logoPath);
            }

            try {
                Log::info('Chargement de la vue PDF pour le bulletin #' . $bulletin->id);
                $pdf = PDF::loadView('esbtp.bulletins.pdf', $data);
                $pdf->setPaper('a4', 'portrait');
                $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

                // Nom du fichier PDF
                $filename = 'bulletin_' .
                            ($bulletin->etudiant ? $bulletin->etudiant->matricule : 'unknown') . '_' .
                            ($bulletin->classe ? $bulletin->classe->code : 'unknown') . '_' .
                            $bulletin->periode . '_' .
                            ($bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire->libelle : 'unknown') . '.pdf';

                Log::info('PDF généré avec succès pour le bulletin #' . $bulletin->id);
                // Télécharger le PDF
                return $pdf->download($filename);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la génération du PDF: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());

                // Enregistrer des informations supplémentaires pour le débogage
                Log::error('Données du bulletin: ' . json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));

                return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la préparation des données pour le PDF: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            // Enregistrer des informations supplémentaires pour le débogage
            if (isset($bulletin)) {
                Log::error('Données du bulletin: ' . json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));
            }

            return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Calcule les absences justifiées et non justifiées pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return array
     */
    private function calculerAbsencesDetailees($bulletin)
    {
        try {
            \Log::info('Début du calcul des absences détaillées pour le bulletin #' . $bulletin->id);

            // Vérifier que les relations nécessaires sont chargées
            if (!$bulletin->etudiant || !$bulletin->classe || !$bulletin->anneeUniversitaire) {
                \Log::error('Relations essentielles manquantes pour le calcul des absences du bulletin #' . $bulletin->id);
                throw new \Exception("Données incomplètes pour calculer les absences. Veuillez vérifier que l'étudiant, la classe et l'année universitaire sont correctement définis.");
            }

            // Vérifier que les dates de l'année universitaire sont définies
            if (!$bulletin->anneeUniversitaire->date_debut || !$bulletin->anneeUniversitaire->date_fin) {
                \Log::error('Dates de l\'année universitaire non définies pour le bulletin #' . $bulletin->id);
                throw new \Exception("Les dates de début et de fin de l'année universitaire ne sont pas définies.");
            }

            // Utiliser le service d'absences pour calculer les absences
            $absences = $this->absenceService->calculerDetailAbsences(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->anneeUniversitaire->date_debut,
                $bulletin->anneeUniversitaire->date_fin
            );

            \Log::info('Absences détaillées calculées avec succès pour le bulletin #' . $bulletin->id, $absences);

            return $absences;

            } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul des absences détaillées: ' . $e->getMessage(), [
                'bulletin_id' => $bulletin->id,
                'etudiant_id' => $bulletin->etudiant_id ?? 'non défini',
                'classe_id' => $bulletin->classe_id ?? 'non défini',
                'trace' => $e->getTraceAsString()
            ]);

            // Retourner des valeurs par défaut en cas d'erreur
            return [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0,
                'detail' => [
                    'justifiees' => [],
                    'non_justifiees' => []
                ]
            ];
        }
    }
/////////////////////
    /**
     * Calcule le total des heures d'absence pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return int
     */
    private function calculerTotalAbsences($bulletin)
    {
        \Log::info('Calcul du total des absences pour le bulletin #' . $bulletin->id);

        try {
            // Utiliser le service d'absences pour calculer les absences
            $absences = $this->absenceService->calculerDetailAbsences(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->anneeUniversitaire->date_debut,
                $bulletin->anneeUniversitaire->date_fin
            );

            \Log::info('Total des absences calculé: ' . $absences['total'] . ' heures');

            return $absences['total'];
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul du total des absences: ' . $e->getMessage(), [
                'bulletin_id' => $bulletin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return 0;
        }
    }

    /**
     * Génère les bulletins pour une classe entière.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function genererClasseBulletins(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
        ]);

        try {
            Log::info('Début de la génération des bulletins', $request->all());
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($request->annee_universitaire_id);

            // Récupérer tous les étudiants inscrits dans cette classe pour cette année
            try {
                Log::info('Récupération des étudiants inscrits');

                // Utiliser une requête directe à la place de la relation 'inscriptions'
                $etudiantIds = DB::table('esbtp_inscriptions')
                    ->where('classe_id', $request->classe_id)
                    ->where('annee_universitaire_id', $request->annee_universitaire_id)
                    ->where('status', 'active')
                    ->pluck('etudiant_id');

                $etudiants = ESBTPEtudiant::whereIn('id', $etudiantIds)->get();

                // Si aucun étudiant n'est trouvé par cette méthode, essayer de récupérer tous les étudiants de la classe
                if ($etudiants->isEmpty()) {
                    Log::info('Aucun étudiant trouvé via les inscriptions, recherche alternative');
                    $etudiants = ESBTPEtudiant::where('classe_id', $request->classe_id)->get();
                }

                Log::info('Nombre d\'étudiants trouvés: ' . $etudiants->count());

                if ($etudiants->isEmpty()) {
                    Log::warning('Aucun étudiant trouvé pour la classe ' . $classe->name);
                    return redirect()->route('esbtp.bulletins.index')
                        ->with('warning', 'Aucun étudiant trouvé pour la classe sélectionnée.');
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des étudiants: ' . $e->getMessage());
                Log::error('SQL: ' . $e->getTraceAsString());
                throw $e;
            }

            $bulletinsGeneres = 0;

            foreach ($etudiants as $etudiant) {
                Log::info('Traitement de l\'étudiant: ' . $etudiant->id . ' - ' . $etudiant->nom . ' ' . $etudiant->prenoms);
                // Vérifier si un bulletin existe déjà pour cet étudiant
                try {
                    $bulletinExistant = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                        ->where('classe_id', $request->classe_id)
                        ->where('annee_universitaire_id', $request->annee_universitaire_id)
                        ->where('periode', $request->periode)
                        ->exists();

                    if ($bulletinExistant) {
                        Log::info('Bulletin existant pour l\'étudiant: ' . $etudiant->id);
                        continue; // Passer à l'étudiant suivant
                    }
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la vérification du bulletin existant: ' . $e->getMessage());
                    Log::error('SQL: ' . $e->getTraceAsString());
                    throw $e;
                }

                // Créer une requête simulée pour réutiliser la méthode store
                $bulletinRequest = new Request([
                    'etudiant_id' => $etudiant->id,
                    'classe_id' => $request->classe_id,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'periode' => $request->periode,
                    'appreciation_generale' => null,
                    'decision_conseil' => null,
                ]);

                // Appeler la méthode store mais sans rediriger
                try {
                    DB::beginTransaction();

                    // Créer le bulletin
                    $bulletin = new ESBTPBulletin();
                    $bulletin->etudiant_id = $etudiant->id;
                    $bulletin->classe_id = $request->classe_id;
                    $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
                    $bulletin->periode = $request->periode;
                    $bulletin->appreciation_generale = null;
                    $bulletin->decision_conseil = null;
                    $bulletin->user_id = Auth::id();
                    $bulletin->save();
                    Log::info('Bulletin créé: ' . $bulletin->id);

                    // Récupérer toutes les matières de la classe
                    $matieres = $classe->matieres;
                    Log::info('Nombre de matières trouvées: ' . $matieres->count());

                    // Pour chaque matière, calculer la moyenne et créer un résultat
                    foreach ($matieres as $matiere) {
                        Log::info('Traitement de la matière: ' . $matiere->id . ' - ' . ($matiere->nom ?? $matiere->name ?? 'Nom inconnu'));

                        // Vérifier si la matière est valide
                        if (!$matiere || !$matiere->id) {
                            Log::warning('Matière invalide trouvée');
                            continue;
                        }

                        // Récupérer toutes les évaluations de cette matière pour cette classe
                        try {
                            $evaluations = $matiere->evaluations()
                                ->where('classe_id', $classe->id)
                                ->where('periode', $request->periode)
                                ->get();

                            Log::info('Nombre d\'évaluations trouvées: ' . $evaluations->count(), [
                                'matiere_id' => $matiere->id,
                                'classe_id' => $classe->id,
                                'periode' => $request->periode
                            ]);

                            if (!$evaluations || $evaluations->isEmpty()) {
                                Log::info('Pas d\'évaluations pour la matière et la période: ' . $matiere->id, [
                                    'periode' => $request->periode
                                ]);

                                // Créer un résultat vide pour cette matière
                                try {
                                    // Récupérer le coefficient de la matière pour cette classe
                                    $coefficient = 1; // Valeur par défaut
                                    try {
                                        $pivot = DB::table('esbtp_classe_matiere')
                                            ->where('classe_id', $classe->id)
                                            ->where('matiere_id', $matiere->id)
                                            ->first();

                                        if ($pivot && isset($pivot->coefficient)) {
                                            $coefficient = $pivot->coefficient;
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Erreur lors de la récupération du coefficient: ' . $e->getMessage());
                                    }

                                    $resultat = new ESBTPResultatMatiere();
                                    $resultat->bulletin_id = $bulletin->id;
                                    $resultat->matiere_id = $matiere->id;
                                    $resultat->moyenne = null; // Pas de moyenne car pas d'évaluations
                                    $resultat->coefficient = $coefficient;
                                    $resultat->commentaire = null;
                                    $resultat->save();
                                    Log::info('Résultat vide créé pour la matière: ' . $matiere->id);
                                } catch (\Exception $e) {
                                    Log::error('Erreur lors de la création du résultat vide: ' . $e->getMessage());
                                }

                                continue; // Passer à la matière suivante s'il n'y a pas d'évaluations
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            continue; // Passer à la matière suivante en cas d'erreur
                        }

                        // Récupérer les notes de l'étudiant pour ces évaluations
                        try {
                            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
                                ->where('classe_id', $request->classe_id)
                                ->whereHas('evaluation', function($query) use ($request) {
                                    $query->where('annee_universitaire_id', $request->annee_universitaire_id);
                                    if ($request->periode != 'annuel') {
                                        $query->where('periode', $request->periode);
                                    }
                                })
                                ->get();

                            Log::info('Nombre de notes trouvées: ' . $notes->count());

                            if (!$notes || $notes->isEmpty()) {
                                Log::info('Pas de notes pour l\'étudiant: ' . $etudiant->id . ' dans la matière: ' . $matiere->id);
                                continue; // Passer à la matière suivante s'il n'y a pas de notes
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération des notes: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            throw $e;
                        }

                        // Calculer la moyenne
                        $sommeNotes = 0;
                        $sommeCoefficients = 0;

                        foreach ($notes as $note) {
                            $evaluation = $notes->where('evaluation_id', $note->evaluation_id)->first();
                            $sommeNotes += ($note->valeur / $evaluation->bareme) * 20 * $evaluation->coefficient;
                            $sommeCoefficients += $evaluation->coefficient;
                        }

                        $moyenne = $sommeCoefficients > 0 ? $sommeNotes / $sommeCoefficients : null;

                        // Récupérer le coefficient de la matière pour cette classe
                        try {
                            $pivotData = $classe->matieres()->where('matiere_id', $matiere->id)->first()->pivot;
                            $coefficient = $pivotData->coefficient ?? 1;
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération du coefficient: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            $coefficient = 1; // Valeur par défaut en cas d'erreur
                        }

                        // Créer le résultat pour cette matière
                        try {
                            $resultat = new ESBTPResultatMatiere();
                            $resultat->bulletin_id = $bulletin->id;
                            $resultat->matiere_id = $matiere->id;
                            $resultat->moyenne = $moyenne;
                            $resultat->coefficient = $coefficient;
                            $resultat->commentaire = null;
                            $resultat->save();
                            Log::info('Résultat créé pour la matière: ' . $matiere->id . ' avec moyenne: ' . $moyenne);
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la création du résultat: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            throw $e;
                        }
                    }

                    // Calculer et mettre à jour la moyenne générale du bulletin
                    try {
                        Log::info('Calcul de la moyenne générale pour le bulletin: ' . $bulletin->id);
                        $this->calculerMoyenneGenerale($bulletin);
                    } catch (\Exception $e) {
                        Log::error('Erreur lors du calcul de la moyenne générale: ' . $e->getMessage());
                        Log::error('SQL: ' . $e->getTraceAsString());
                        throw $e;
                    }

                    DB::commit();
                    $bulletinsGeneres++;
                    Log::info('Bulletin généré avec succès pour l\'étudiant: ' . $etudiant->id);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur lors de la génération du bulletin pour l\'étudiant: ' . $etudiant->id . ' - ' . $e->getMessage());
                    Log::error('SQL: ' . $e->getTraceAsString());
                    // Continuer avec l'étudiant suivant
                }
            }

            if ($bulletinsGeneres > 0) {
                Log::info('Bulletins générés avec succès: ' . $bulletinsGeneres);
                return redirect()->route('esbtp.bulletins.index')
                    ->with('success', $bulletinsGeneres . ' bulletins ont été générés avec succès');
            } else {
                Log::info('Aucun bulletin généré');
                return redirect()->route('esbtp.bulletins.index')
                    ->with('info', 'Aucun nouveau bulletin n\'a été généré. Tous les bulletins existent déjà ou il n\'y a pas de données suffisantes.');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des bulletins: ' . $e->getMessage());
            Log::error('SQL: ' . $e->getTraceAsString());

            return redirect()->route('esbtp.bulletins.index')
                ->with('error', 'Une erreur est survenue lors de la génération des bulletins: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page de sélection pour les bulletins
     *
     * @return \Illuminate\Http\Response
     */
    public function select()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeActuelle = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        return view('esbtp.bulletins.select', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Affiche les résultats des étudiants
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resultats(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'semestre' => 'nullable|in:1,2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $classe_id = $request->classe_id;
        $semestre = $request->semestre;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses') ? $request->include_all_statuses : true; // Par défaut, inclure tous les statuts
        $periode = $semestre; // Map semestre to periode for view compatibility

        Log::info('Resultats method called with params', [
            'classe_id' => $classe_id,
            'semestre' => $semestre,
            'annee_universitaire_id' => $annee_universitaire_id,
            'include_all_statuses' => $include_all_statuses
        ]);

        // If classe_id is provided, get the corresponding academic year
        if ($classe_id && !$annee_universitaire_id) {
            $classe = ESBTPClasse::find($classe_id);
            if ($classe && $classe->annee_universitaire_id) {
                $annee_universitaire_id = $classe->annee_universitaire_id;
            }
        }

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
        }

        // For view compatibility
        $annee_id = $annee_universitaire_id;

        // Get annee object for view display
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // Always load all active classes with relationships, regardless of filters
        $classes = ESBTPClasse::with(['filiere', 'niveau'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $periodes = ['1' => 'Semestre 1', '2' => 'Semestre 2'];
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Get selected classe information
        $classeObj = null;
        $classe = null;
        if ($classe_id) {
            $classeObj = ESBTPClasse::with('filiere')->find($classe_id);
            $classe = $classeObj; // Alias for view compatibility
        }

        // Get students and notes
        $etudiants = []; // Renamed from $students for view compatibility
        $notes = [];
        $moyennes = []; // For storing student averages
        $rangs = []; // For storing student ranks
        $bulletins = []; // For storing student bulletins

        if ($classe_id) {
            // Get students through inscriptions for the selected class and year
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                // CORRECTION : Inversion de la logique du filtre
                if (!$include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions.classe.filiere', 'inscriptions.classe.niveau'])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            Log::info('Étudiants récupérés pour la classe', [
                'classe_id' => $classe_id,
                'annee_universitaire_id' => $annee_universitaire_id,
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, also get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spécifié, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                Log::info('Notes récupérées pour les étudiants', [
                    'etudiants_count' => $etudiants->count(),
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les périodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins
                $this->getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, $bulletins);
            }
        } else if ($annee_universitaire_id) {
            // If no class selected but academic year is set, get all students enrolled in that year
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($annee_universitaire_id, $include_all_statuses) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);

                // Inverser la condition pour inclure tous les statuts par défaut
                if ($include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions' => function ($query) use ($annee_universitaire_id) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);
            }])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            \Log::info('Étudiants récupérés par année', [
                'annee_universitaire_id' => $annee_universitaire_id,
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->whereHas('evaluation', function ($query) use ($annee_universitaire_id) {
                        $query->where('annee_universitaire_id', $annee_universitaire_id);
                    })
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spécifié, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                \Log::info('Notes récupérées par année', [
                    'annee_id' => $annee_universitaire_id,
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les périodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins - we don't have a specific class so use individual student inscriptions
                $this->getStudentBulletins($etudiants, null, $annee_universitaire_id, $semestre, $bulletins);
            }
        } else {
            // If no filters are applied, get all active students
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($include_all_statuses) {
                // Ne filtrer sur le statut 'active' que si include_all_statuses est false
                if (!$include_all_statuses) {
                $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions'])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            \Log::info('Étudiants récupérés sans filtres', [
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spécifié, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                \Log::info('Notes récupérées sans filtres', [
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les périodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins - we don't have a specific class so use individual student inscriptions
                $this->getStudentBulletins($etudiants, null, $annee_universitaire_id, $semestre, $bulletins);
            }
        }

        return view('esbtp.resultats.index', compact(
            'classes',
            'periodes',
            'annees_universitaires',
            'classe_id',
            'classeObj',
            'classe',
            'semestre',
            'periode',
            'annee_universitaire_id',
            'annee_id',
            'anneeUniversitaire',
            'etudiants',
            'notes',
            'moyennes',
            'rangs',
            'bulletins'
        ));
    }

    /**
     * Helper method to calculate student statistics (averages and ranks)
     */
    private function calculateStudentStats($etudiants, $notes, &$moyennes, &$rangs)
    {
        // Group notes by student and matière
        $notesByStudentMatiere = [];
        $nonNumericNotes = 0;
        $totalNotesProcessed = 0;

        \Log::info('Début du calcul des moyennes pour ' . count($etudiants) . ' étudiants avec ' . count($notes) . ' notes');

        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                \Log::warning('Note ignorée: absence d\'évaluation ou de matière', [
                    'note_id' => $note->id,
                    'etudiant_id' => $note->etudiant_id
                ]);
                continue; // Skip notes without evaluations or matières
            }

            $etudiantId = $note->etudiant_id;
            $matiereId = $note->evaluation->matiere_id;
            $totalNotesProcessed++;

            if (!isset($notesByStudentMatiere[$etudiantId])) {
                $notesByStudentMatiere[$etudiantId] = [];
            }

            if (!isset($notesByStudentMatiere[$etudiantId][$matiereId])) {
                $notesByStudentMatiere[$etudiantId][$matiereId] = [
                    'notes' => [],
                    'sum' => 0,
                    'coeffSum' => 0
                ];
            }

            // Add note to collection
            $notesByStudentMatiere[$etudiantId][$matiereId]['notes'][] = $note;

            // Calculate weighted note if evaluation has valid bareme
            if ($note->evaluation && $note->evaluation->bareme > 0) {
                // Utiliser note OU valeur (où que la note soit stockée)
                $noteValue = is_numeric($note->note) ? $note->note : $note->valeur;

                // Ajouter une gestion spéciale pour les notes "Absent" ou non numériques
                if ($noteValue === "Absent" || !is_numeric($noteValue)) {
                    // Comptabiliser une absence comme un zéro
                    $normalized = 0;
                    $nonNumericNotes++;
                    \Log::info('Note non numérique détectée', [
                        'etudiant_id' => $etudiantId,
                        'matiere_id' => $matiereId,
                        'note' => $note->note,
                        'valeur' => $note->valeur,
                        'noteValue' => $noteValue,
                        'evaluation_id' => $note->evaluation->id
                    ]);
                } else {
                    $normalized = ($noteValue / $note->evaluation->bareme) * 20;
                    \Log::debug('Note calculée', [
                        'etudiant_id' => $etudiantId,
                        'matiere_id' => $matiereId,
                        'noteValue' => $noteValue,
                        'bareme' => $note->evaluation->bareme,
                        'normalized' => $normalized
                    ]);
                }
                $coefficient = $note->evaluation->coefficient ?? 1;
                $notesByStudentMatiere[$etudiantId][$matiereId]['sum'] += $normalized * $coefficient;
                $notesByStudentMatiere[$etudiantId][$matiereId]['coeffSum'] += $coefficient;
            }
        }

        \Log::info('Notes traitées: ' . $totalNotesProcessed . ' sur ' . count($notes) . ' notes totales');
        \Log::info('Étudiants avec des notes: ' . count($notesByStudentMatiere) . ' sur ' . count($etudiants) . ' étudiants totaux');

        // Calculate average for each student
        foreach ($etudiants as $etudiant) {
            if (!isset($notesByStudentMatiere[$etudiant->id])) {
                \Log::warning('Aucune note trouvée pour l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' (ID: ' . $etudiant->id . ')');
                continue; // Skip if student has no notes
            }

            $totalSum = 0;
            $totalCoeff = 0;
            $matiereCount = 0;

            // Calculate average for each matière, then the overall average
            foreach ($notesByStudentMatiere[$etudiant->id] as $matiereId => $matiereData) {
                $matiereCount++;
                if ($matiereData['coeffSum'] > 0) {
                    $matiereAverage = $matiereData['sum'] / $matiereData['coeffSum'];
                    \Log::debug('Moyenne par matière', [
                        'etudiant_id' => $etudiant->id,
                        'matiere_id' => $matiereId,
                        'sum' => $matiereData['sum'],
                        'coeffSum' => $matiereData['coeffSum'],
                        'average' => $matiereAverage
                    ]);

                    // For overall average, we treat each matière equally for now
                    // You might want to adjust this to use matière coefficients if available
                    $matCoeff = 1; // Default coefficient for matière
                    $totalSum += $matiereAverage * $matCoeff;
                    $totalCoeff += $matCoeff;
                } else {
                    \Log::warning('Matière sans coefficient pour l\'étudiant ' . $etudiant->id, [
                        'matiere_id' => $matiereId,
                        'notes_count' => count($matiereData['notes'])
                    ]);
                }
            }

            if ($totalCoeff > 0) {
                $moyennes[$etudiant->id] = $totalSum / $totalCoeff;
                \Log::info('Moyenne calculée pour l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms, [
                    'etudiant_id' => $etudiant->id,
                    'moyenne' => $moyennes[$etudiant->id],
                    'matieres_count' => $matiereCount
                ]);
            } else {
                \Log::warning('Impossible de calculer la moyenne pour l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms, [
                    'etudiant_id' => $etudiant->id,
                    'totalCoeff' => $totalCoeff,
                    'matieres_count' => $matiereCount
                ]);
            }
        }

        // Sort by average to calculate ranks
        arsort($moyennes);
        $rank = 1;
        foreach (array_keys($moyennes) as $etudiantId) {
            $rangs[$etudiantId] = $rank++;
        }

        // Log the calculated averages for debugging
        \Log::info('Calcul des moyennes terminé:', [
            'moyennes_count' => count($moyennes),
            'rangs_count' => count($rangs),
            'non_numeric_notes' => $nonNumericNotes,
            'total_notes_processed' => $totalNotesProcessed
        ]);
    }

    /**
     * Helper method to get bulletins for students
     */
    private function getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, &$bulletins)
    {
        $periodeMap = [
            '1' => 'semestre1',
            '2' => 'semestre2',
        ];

        // Si le semestre est spécifié, on récupère seulement ce semestre
        // Sinon, on récupère tous les semestres
        $periodes = [];
        if ($semestre && isset($periodeMap[$semestre])) {
            $periodes[] = $periodeMap[$semestre];
        } else {
            // Si aucun semestre n'est spécifié, on récupère tous les semestres
            $periodes = array_values($periodeMap);
        }

        \Log::info('Récupération des bulletins pour ' . count($etudiants) . ' étudiants', [
            'annee_universitaire_id' => $annee_universitaire_id,
            'semestre' => $semestre,
            'periodes' => $periodes
        ]);

        foreach ($etudiants as $etudiant) {
            // If no specific class is provided, get the student's class from inscriptions
            $studentClasseId = $classe_id;
            if (!$studentClasseId) {
                $inscription = $etudiant->inscriptions
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('status', 'active')
                    ->first();
                $studentClasseId = $inscription ? $inscription->classe_id : null;
            }

            if ($studentClasseId && $annee_universitaire_id && !empty($periodes)) {
                $query = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                    ->where('classe_id', $studentClasseId)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                // Si on a des périodes spécifiques, on les utilise
                // Sinon, on récupère tous les bulletins pour cet étudiant dans cette classe et cette année
                if (count($periodes) == 1) {
                    $query->where('periode', $periodes[0]);
                } else {
                    $query->whereIn('periode', $periodes);
                }

                $bulletin = $query->first();

                if ($bulletin) {
                    $bulletins[$etudiant->id] = $bulletin->id;
                    \Log::debug('Bulletin trouvé pour étudiant', [
                        'etudiant_id' => $etudiant->id,
                        'bulletin_id' => $bulletin->id,
                        'classe_id' => $studentClasseId,
                        'periode' => $bulletin->periode
                    ]);
                } else {
                    \Log::warning('Aucun bulletin trouvé pour étudiant', [
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $studentClasseId,
                        'periodes' => $periodes
                    ]);
                }
            } else {
                \Log::warning('Données insuffisantes pour récupérer le bulletin', [
                    'etudiant_id' => $etudiant->id,
                    'studentClasseId' => $studentClasseId,
                    'annee_universitaire_id' => $annee_universitaire_id,
                    'periodes' => $periodes
                ]);
            }
        }
    }

    /**
     * Affiche le bulletin de l'étudiant connecté.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function monBulletin(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Récupérer l'étudiant associé à l'utilisateur
        $etudiant = $user->etudiant;

        if (!$etudiant) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre compte utilisateur n\'est pas associé à un étudiant.');
        }

        // Récupérer les paramètres de filtre
        $anneeId = $request->input('annee_universitaire_id',
            ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null);
        $periode = $request->input('periode');

        // Récupérer l'inscription active de l'étudiant
        $inscription = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', 'active')
            ->first();

        if (!$inscription) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'êtes pas inscrit pour l\'année universitaire sélectionnée.');
        }

        // Récupérer la classe de l'étudiant
        $classe = $inscription->classe;

        if (!$classe) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre inscription n\'est associée à aucune classe.');
        }

        // Récupérer le bulletin de l'étudiant
        $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeId);

        if ($periode) {
            $bulletin = $bulletin->where('periode', $periode);
        }

        $bulletin = $bulletin->first();

        // Si le bulletin n'existe pas encore, on affiche un message
        if (!$bulletin) {
            // Récupérer toutes les années universitaires pour le filtre
            $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

            return view('esbtp.bulletin.mon-bulletin', compact(
                'etudiant',
                'classe',
                'anneeId',
                'periode',
                'anneesUniversitaires'
            ))->with('warning', 'Le bulletin n\'est pas encore disponible pour la période sélectionnée.');
        }

        // Récupérer les détails du bulletin
        $detailsBulletin = ESBTPBulletinDetail::where('bulletin_id', $bulletin->id)
            ->with(['matiere'])
            ->get();

        // Regrouper les détails par UE si nécessaire
        $detailsParUE = [];

        foreach ($detailsBulletin as $detail) {
            $ueId = $detail->matiere->ue_id ?? 'sans_ue';
            if (!isset($detailsParUE[$ueId])) {
                $detailsParUE[$ueId] = [
                    'ue' => $detail->matiere->ue ?? null,
                    'details' => []
                ];
            }
            $detailsParUE[$ueId]['details'][] = $detail;
        }

        // Calculer les statistiques globales
        $moyenneGenerale = $bulletin->moyenne_generale;
        $rangGeneral = $bulletin->rang;
        $effectifClasse = $bulletin->effectif_classe;
        $creditsTotaux = $detailsBulletin->sum('credits_valides');
        $decisionConseil = $bulletin->decision_conseil;

        // Récupérer toutes les années universitaires pour le filtre
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        return view('esbtp.bulletin.mon-bulletin', compact(
            'etudiant',
            'classe',
            'bulletin',
            'detailsBulletin',
            'detailsParUE',
            'moyenneGenerale',
            'rangGeneral',
            'effectifClasse',
            'creditsTotaux',
            'decisionConseil',
            'anneeId',
            'periode',
            'anneesUniversitaires'
        ));
    }

    /**
     * Affiche les bulletins de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function studentBulletins()
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer les bulletins avec les relations nécessaires
        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->with(['classe', 'anneeUniversitaire'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Pour chaque bulletin, récupérer les résultats correspondants
        foreach ($bulletins as $bulletin) {
            // Récupérer les résultats de l'étudiant pour cette période
            $resultats = ESBTPResultat::where([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $bulletin->classe_id,
                'periode' => $bulletin->periode,
                'annee_universitaire_id' => $bulletin->annee_universitaire_id
            ])->get();

            // Calculer la moyenne générale
            $sommePoints = 0;
            $sommeCoefficients = 0;

            foreach ($resultats as $resultat) {
                $sommePoints += $resultat->moyenne * $resultat->coefficient;
                $sommeCoefficients += $resultat->coefficient;
            }

            $moyenneGenerale = $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : 0;
            $bulletin->moyenne_generale = round($moyenneGenerale, 2);

            // Calculer le rang de l'étudiant
            $autresResultats = ESBTPResultat::where([
                'classe_id' => $bulletin->classe_id,
                'periode' => $bulletin->periode,
                'annee_universitaire_id' => $bulletin->annee_universitaire_id
            ])
            ->select('etudiant_id')
            ->selectRaw('SUM(moyenne * coefficient) / SUM(coefficient) as moyenne_generale')
            ->groupBy('etudiant_id')
            ->orderByDesc('moyenne_generale')
            ->get();

            $rang = 1;
            foreach ($autresResultats as $autre) {
                if ($autre->etudiant_id == $etudiant->id) {
                    $bulletin->rang = $rang;
                    break;
                }
                $rang++;
            }
            $bulletin->effectif_classe = $autresResultats->count();

            // Déterminer la mention
            if ($moyenneGenerale >= 16) {
                $bulletin->mention = 'Très Bien';
            } elseif ($moyenneGenerale >= 14) {
                $bulletin->mention = 'Bien';
            } elseif ($moyenneGenerale >= 12) {
                $bulletin->mention = 'Assez Bien';
            } elseif ($moyenneGenerale >= 10) {
                $bulletin->mention = 'Passable';
            } else {
                $bulletin->mention = 'Insuffisant';
            }
        }

        return view('etudiants.bulletins', compact('bulletins', 'etudiant'));
    }

    /**
     * Signe un bulletin par un responsable
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @param  string  $role
     * @return \Illuminate\Http\Response
     */
    public function signer(ESBTPBulletin $bulletin, $role)
    {
        if (!in_array($role, ['directeur', 'responsable', 'parent'])) {
            return back()->with('error', 'Rôle de signature invalide.');
        }

        try {
            $bulletin->signer($role);
            return back()->with('success', 'Bulletin signé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la signature: ' . $e->getMessage());
        }
    }

    /**
     * Bascule l'état de publication d'un bulletin
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function togglePublication(ESBTPBulletin $bulletin)
    {
        try {
            $bulletin->is_published = !$bulletin->is_published;
            $bulletin->save();

            $message = $bulletin->is_published
                ? 'Le bulletin a été publié avec succès.'
                : 'Le bulletin a été dépublié avec succès.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les bulletins en attente (non publiés ou non signés)
     *
     * @return \Illuminate\Http\Response
     */
    public function pending()
    {
        // Récupérer les bulletins qui ne sont pas publiés ou qui n'ont pas toutes les signatures
        $bulletins = ESBTPBulletin::where('is_published', false)
            ->orWhere(function($query) {
                $query->where('signature_responsable', false)
                      ->orWhere('signature_directeur', false);
            })
            ->with(['etudiant', 'classe', 'anneeUniversitaire'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Statistiques
        $totalPending = ESBTPBulletin::where('is_published', false)->count();
        $totalNonSigned = ESBTPBulletin::where('is_published', true)
            ->where(function($query) {
                $query->where('signature_responsable', false)
                      ->orWhere('signature_directeur', false);
            })->count();

        return view('esbtp.bulletins.pending', compact('bulletins', 'totalPending', 'totalNonSigned'));
    }

    /**
     * Affiche les résultats des étudiants d'une classe spécifique
     *
     * @param ESBTPClasse $classe
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function resultatClasse(Request $request, $id)
    {
        $this->validate($request, [
            'semestre' => 'nullable|in:1,2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $semestre = $request->semestre;
        $periode = $semestre; // Map semestre to periode for view compatibility
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses');

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
                $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
        }

        $classe_id = $id;
        $classe = ESBTPClasse::with(['matieres' => function($query) {
            $query->withPivot('coefficient');
        }])->findOrFail($classe_id);

        // Get students through inscriptions for the selected class and year
        $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
            $query->where('classe_id', $classe_id)
                ->where('annee_universitaire_id', $annee_universitaire_id);

            // Inverser la condition pour inclure tous les statuts par défaut
            if ($include_all_statuses) {
                $query->where('status', 'active');
            }
        })
        ->with(['user']);

        $students = $studentsQuery->get();

        \Log::info('Classe Results Query', [
            'classe_id' => $classe_id,
            'semestre' => $semestre,
            'annee_universitaire_id' => $annee_universitaire_id,
            'include_all_statuses' => $include_all_statuses,
            'students_count' => $students->count()
        ]);

        // Get all notes for these students
        $notes = [];
        if ($students->count() > 0) {
            $student_ids = $students->pluck('id')->toArray();

            // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
            $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

            // Si un semestre est spécifié, filtrer par ce semestre
            if ($semestre) {
                $notesQuery->where(function ($q) use ($semestre) {
                        $q->where('semestre', $semestre)
                            ->whereHas('evaluation', function ($query) use ($semestre) {
                                $query->where('periode', 'semestre'.$semestre);
                            });
                    });
            }

            $notes = $notesQuery->get();

            \Log::info('Notes récupérées pour la classe', [
                'classe_id' => $classe_id,
                'notes_count' => $notes->count(),
                'semestre' => $semestre ? $semestre : 'Toutes les périodes'
            ]);
        }

        // Changed from associative array to array of objects for view compatibility
        $periodes = [
            (object)['id' => '1', 'nom' => 'Semestre 1'],
            (object)['id' => '2', 'nom' => 'Semestre 2']
        ];

        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Group notes by student and then by matière
        $notesByStudentMatiere = [];

        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                continue; // Skip notes without evaluation or matière
            }

            $etudiantId = $note->etudiant_id;
            $matiereId = $note->evaluation->matiere_id;

            if (!isset($notesByStudentMatiere[$etudiantId])) {
                $notesByStudentMatiere[$etudiantId] = [];
            }

            if (!isset($notesByStudentMatiere[$etudiantId][$matiereId])) {
                $notesByStudentMatiere[$etudiantId][$matiereId] = [
                    'sum' => 0,
                    'coeffSum' => 0,
                    'matiere' => $note->evaluation->matiere
                ];
            }

            // CORRECTION : Ajout du coefficient de l'évaluation
            $evaluationCoefficient = $note->evaluation->coefficient ?? 1;
            $normalized = ($note->valeur / $note->evaluation->bareme) * 20;

            $notesByStudentMatiere[$etudiantId][$matiereId]['sum'] += $normalized * $evaluationCoefficient;
            $notesByStudentMatiere[$etudiantId][$matiereId]['coeffSum'] += $evaluationCoefficient;
        }

        // Calculate averages for each student and matière
        $resultats = [];

        foreach ($students as $student) {
            $moyenne = 0;
            $totalPoints = 0;
            $totalCoefficients = 0;

            if (isset($notesByStudentMatiere[$student->id])) {
                $studentMatieres = $notesByStudentMatiere[$student->id];

                foreach ($studentMatieres as $matiereId => $matiereData) {
                    if ($matiereData['coeffSum'] > 0) {
                        // Calcul de la moyenne de la matière
                        $matiereMoyenne = $matiereData['sum'] / $matiereData['coeffSum'];

                        // Récupération du coefficient de la matière dans la classe
                        $matiereClasse = $classe->matieres()->where('matiere_id', $matiereId)->first();
                        $matiereCoefficient = $matiereClasse ? $matiereClasse->pivot->coefficient : 1;

                        // Application du coefficient de la matière
                        $totalPoints += $matiereMoyenne * $matiereCoefficient;
                        $totalCoefficients += $matiereCoefficient;
                    }
                }

                if ($totalCoefficients > 0) {
                    $moyenne = $totalPoints / $totalCoefficients;
                }
            }

            $resultats[] = [
                'etudiant' => $student,
                'moyenne' => $moyenne,
                'total_coefficients' => $totalCoefficients,
                'notes_count' => $notes->where('etudiant_id', $student->id)->count()
            ];
        }

        // Sort resultats by moyenne in descending order
        usort($resultats, function ($a, $b) {
            return $b['moyenne'] <=> $a['moyenne'];
        });

        // Define annee_id for view consistency
        $annee_id = $annee_universitaire_id;

        return view('esbtp.resultats.classe', compact(
            'classe',
            'students',
            'notes',
            'semestre',
            'periode',
            'periodes',
            'annee_universitaire_id',
            'annee_id',
            'annees_universitaires',
            'resultats',
            'include_all_statuses' // Ajouter cette ligne
        ));
    }

    /**
     * Affiche les résultats détaillés d'un étudiant spécifique
     *
     * @param ESBTPEtudiant $etudiant
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function resultatEtudiant(Request $request, $id)
    {
        $this->validate($request, [
            'semestre' => 'nullable|in:1,2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $semestre = $request->semestre;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // CORRECTION: Conversion du format du semestre pour compatibilité avec le format attendu
        // pour la génération de PDF (convertir '1' en 'semestre1')
        $periode = $semestre ? 'semestre' . $semestre : 'semestre1';

        \Log::debug('Valeurs des variables pour la génération de PDF:', [
            'semestre' => $semestre,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        $include_all_statuses = $request->has('include_all_statuses');

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
        }

        // For view compatibility
        $annee_id = $annee_universitaire_id;

        $etudiant = ESBTPEtudiant::with('user')->findOrFail($id);

        // Get inscription for the student in the specified academic year
        $inscriptionQuery = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $annee_universitaire_id);

        if (!$include_all_statuses) {
            $inscriptionQuery->where('status', 'active');
        }

        $inscription = $inscriptionQuery->first();

        $classe_id = $inscription->classe_id ?? $request->classe_id ?? null;
        $classe = $classe_id ? ESBTPClasse::with('filiere')->find($classe_id) : null;
        // Get all active classes for the filter dropdown
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $periodes = [
            (object)['id' => '1', 'nom' => 'Semestre 1'],
            (object)['id' => '2', 'nom' => 'Semestre 2']
        ];

        // Get notes for the student
        $notesQuery = ESBTPNote::where('etudiant_id', $id)
            ->with(['evaluation', 'evaluation.matiere']);

        // Si un semestre est spécifié, filtrer par ce semestre
        if ($semestre) {
            $notesQuery->where(function ($q) use ($semestre) {
                    $q->where('semestre', $semestre)
                        ->whereHas('evaluation', function ($query) use ($semestre) {
                            $query->where('periode', 'semestre'.$semestre);
                        });
                });
        }

        $notes = $notesQuery->get();

        \Log::info('Student Result Notes', [
            'student_id' => $id,
            'semestre' => $semestre ? $semestre : 'Toutes les périodes',
            'include_all_statuses' => $include_all_statuses,
            'notes_count' => $notes->count()
        ]);

        // Group notes by matière (subject)
        $notesByMatiere = [];
        $totalPoints = 0;
        $totalCoefficients = 0;
        $nonNumericNotes = 0;

        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                \Log::warning('Note without evaluation or matière', ['note_id' => $note->id]);
                continue; // Skip notes without evaluation or matière
            }

            // CORRECTION: Prioriser l'ID de matière stocké directement sur la note si disponible
            $matiere_id = $note->matiere_id;
            if (!$matiere_id && $note->evaluation && $note->evaluation->matiere) {
            $matiere_id = $note->evaluation->matiere->id;
            }

            // Skip if we still can't determine the matiere_id
            if (!$matiere_id) {
                \Log::warning('Cannot determine matiere_id for note', ['note_id' => $note->id]);
                continue;
            }

            // Récupérer la matière directement depuis la base de données pour éviter toute confusion
            $matiere = \App\Models\ESBTPMatiere::find($matiere_id);
            if (!$matiere) {
                \Log::warning("Matiere with ID {$matiere_id} not found for note ID {$note->id} - skipping note");
                continue;
            }

            // Initialize if this is the first note for this matière
            if (!isset($notesByMatiere[$matiere_id])) {
                $notesByMatiere[$matiere_id] = [
                    'matiere' => $matiere, // Use the freshly retrieved matiere
                    'notes' => [],
                    'calculations' => [], // Add storage for calculations
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0
                ];
                \Log::debug("Initialized new entry in notesByMatiere for matiere {$matiere->name} (ID: {$matiere->id})");
            }

            // CORRECTION AMÉLIORÉE: Vérification supplémentaire pour s'assurer que nous traitons la bonne note
            \Log::debug("Note {$note->id} VALUE CHECK: note field = {$note->note}, valeur field = {$note->valeur}");

            // Only use notes with evaluations that have a valid bareme
            if ($note->evaluation->bareme > 0) {
                // CORRECTION AMÉLIORÉE: Accès direct aux valeurs numériques pour éviter tout problème de
                // conversion ou de référence. Utiliser la fonction floatval pour s'assurer que nous avons une valeur numérique.
                $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;

                if ($noteValue === "Absent" || !is_numeric($noteValue)) {
                    $normalized = 0;
                    $nonNumericNotes++;
                } else {
                    $normalized = ($noteValue / $bareme) * 20;
                }

                $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;
                $ponderation = $normalized * $coefficient;

                \Log::debug("CALCULATION for note {$note->id}: noteValue={$noteValue}, coefficient={$coefficient}, bareme={$bareme} => ponderation={$ponderation}");

                // CORRECTION AMÉLIORÉE: Ajouter explicitement les valeurs aux tableaux en utilisant des structures claires
                // Cela évite tout problème de référence ou de partage d'objets en mémoire
                $noteRef = [
                    'id' => $note->id,
                    'value' => $noteValue,
                    'coefficient' => $coefficient,
                    'ponderation' => $ponderation,
                    'normalized' => $normalized
                ];

                // Store both the calculation structure AND the original note object to maintain view compatibility
                $notesByMatiere[$matiere_id]['notes'][] = $note; // Keep the full note object for the view
                $notesByMatiere[$matiere_id]['calculations'][] = $noteRef; // Store calculations separately
                $notesByMatiere[$matiere_id]['total_points'] += $ponderation;
                $notesByMatiere[$matiere_id]['total_coefficients'] += $coefficient;
            }
        }

        // Calculate average for each matière and overall weighted average
        $moyenneGenerale = 0;
        $countValidMatieres = 0;

        foreach ($notesByMatiere as $matiere_id => &$matiereData) {
            if ($matiereData['total_coefficients'] > 0) {
                $matiereData['moyenne'] = $matiereData['total_points'] / $matiereData['total_coefficients'];

                // For overall average, we treat each matière equally
                // You might want to adjust this to use matière coefficients
                $moyenneGenerale += $matiereData['moyenne'];
                $countValidMatieres++;
            }
        }

        // Calculate the overall moyenne générale
        $moyenneGenerale = $countValidMatieres > 0 ? $moyenneGenerale / $countValidMatieres : 0;

        \Log::info('Student Result Calculations', [
            'student_id' => $id,
            'matieres_count' => count($notesByMatiere),
            'moyenne_generale' => $moyenneGenerale,
            'non_numeric_notes' => $nonNumericNotes
        ]);

        return view('esbtp.resultats.etudiant', compact(
            'etudiant',
            'classe',
            'notes',
            'notesByMatiere',
            'moyenneGenerale',
            'semestre',
            'periode',
            'annee_universitaire_id',
            'annee_id',
            'classes',
            'anneesUniversitaires',
            'periodes'
        ));
    }

    /**
     * Génère un PDF à partir des paramètres fournis (étudiant, classe, période, année universitaire)
     * sans nécessiter un bulletin existant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function genererPDFParParams(Request $request)
    {
        try {
            // Vérifier que l'utilisateur est autorisé - Restreindre aux superAdmin uniquement
            if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
            }

            // Récupérer les paramètres
            $classe_id = $request->classe_id;
            // Récupérer etudiant_id soit depuis etudiant_id, soit depuis bulletin
            $etudiant_id = $request->etudiant_id ?? $request->bulletin;
            $periode = $request->periode;
            $annee_universitaire_id = $request->annee_universitaire_id;

            // Journaliser les paramètres pour le débogage
            \Log::info('Paramètres reçus pour genererPDFParParams:', [
                'classe_id' => $classe_id,
                'etudiant_id' => $etudiant_id,
                'bulletin' => $request->bulletin,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Vérifier l'existence de l'étudiant
            $etudiant = ESBTPEtudiant::find($etudiant_id);
            if (!$etudiant) {
                return back()->with('error', 'L\'étudiant spécifié n\'existe pas.');
            }

            // VÉRIFICATION 1: Vérifier si des moyennes dans esbtp_resultats sont nulles
            $resultatsNulls = ESBTPResultat::where([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->whereNull('moyenne')->exists();

            if ($resultatsNulls) {
                return back()->with('error', 'Certaines moyennes ne sont pas encore saisies. Veuillez d\'abord saisir toutes les moyennes.');
            }

            // Rechercher le bulletin existant
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            // VÉRIFICATION 2: Vérifier la configuration des matières
            $configMatieresExiste = ESBTPConfigMatiere::where([
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->exists();

            if (!$configMatieresExiste) {
                // Rediriger vers la configuration des matières
                $url = "/esbtp-special/bulletins/config-matieres?" . http_build_query([
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return redirect()->to($url)->with('error', 'La configuration des matières n\'a pas été effectuée. Veuillez d\'abord configurer les matières.');
            }

            // VÉRIFICATION 3: Vérifier l'existence des professeurs pour le bulletin
            $professeursManquants = false;

            if ($bulletin) {
                // Si le bulletin existe, vérifier que la colonne 'professeurs' n'est pas null
                if ($bulletin->professeurs === null) {
                    $professeursManquants = true;
                }
            } else {
                // Si le bulletin n'existe pas, considérer les professeurs comme manquants
                $professeursManquants = true;
            }

            if ($professeursManquants) {
                // Rediriger vers l'édition des professeurs
                $url = "/esbtp-special/bulletins/edit-professeurs?" . http_build_query([
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return redirect()->to($url)->with('error', 'Les professeurs n\'ont pas été assignés. Veuillez d\'abord assigner les professeurs.');
            }

            // Si le bulletin n'existe pas, créer un objet temporaire
            if (!$bulletin) {
                $bulletin = new \stdClass();
                $bulletin->etudiant_id = $etudiant_id;
                $bulletin->classe_id = $classe_id;
                $bulletin->periode = $periode;
                $bulletin->annee_universitaire_id = $annee_universitaire_id;
                $bulletin->rang = 'N/A'; // Initialiser avec une valeur par défaut
                $bulletin->appreciation = 'N/A'; // Initialiser avec une valeur par défaut
            }

            // Récupérer les entités liées
            $classe = ESBTPClasse::findOrFail($classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($annee_universitaire_id);

            // Assigner les entités au bulletin (si c'est un objet stdClass)
            if (!isset($bulletin->id)) {
                $bulletin->etudiant = $etudiant;
                $bulletin->classe = $classe;
                $bulletin->anneeUniversitaire = $anneeUniversitaire;
            }

            // Encoder le logo en base64 pour l'intégrer dans le PDF
            $logoPath = public_path('images/esbtp_logo.png');
            $logoBase64 = null;
            if (file_exists($logoPath)) {
                $logoContent = file_get_contents($logoPath);
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);
                \Log::info('Logo chargé avec succès depuis: ' . $logoPath);
            } else {
                \Log::warning('Logo non trouvé au chemin: ' . $logoPath . '. Vérifiez que le fichier existe.');
                // Essayer avec d'autres noms de fichiers possibles
                $alternativePaths = [
                    'images/logo.jpeg',
                    'images/esbtp_logo_white.png'
                ];

                foreach ($alternativePaths as $altPath) {
                    $fullPath = public_path($altPath);
                    if (file_exists($fullPath)) {
                        $logoContent = file_get_contents($fullPath);
                        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                        $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode($logoContent);
                        \Log::info('Logo alternatif chargé avec succès depuis: ' . $fullPath);
                        break;
                    }
                }
            }

            // Récupérer les résultats pour l'étudiant
            $resultats = ESBTPResultat::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->with('matiere')
                ->get();

            if ($resultats->isEmpty()) {
                return back()->with('error', 'Aucun résultat trouvé pour cet étudiant dans cette période.');
            }

            // Le reste du code reste inchangé
            // Séparer les résultats par type de matière (généraux et techniques)
            $resultatsGeneraux = collect();
            $resultatsTechniques = collect();

            foreach ($resultats as $resultat) {
                // Vérification et journalisation des données matière
                if ($resultat->matiere) {
                    \Log::info('Matière trouvée pour le résultat #' . $resultat->id, [
                        'matiere_id' => $resultat->matiere_id,
                        'matiere_nom' => $resultat->matiere->nom ?? 'Non défini',
                        'matiere_name' => $resultat->matiere->name ?? 'Non défini',
                        'type' => $resultat->matiere->type ?? 'Non défini'
                    ]);
                } else {
                    \Log::warning('Matière non trouvée pour le résultat #' . $resultat->id, [
                        'matiere_id' => $resultat->matiere_id
                    ]);
                }

                // S'assurer que chaque résultat a une matière valide avec un nom
                if (!$resultat->matiere) {
                    $resultat->matiere = ESBTPMatiere::find($resultat->matiere_id);
                    if (!$resultat->matiere) {
                        \Log::error('Impossible de récupérer la matière #' . $resultat->matiere_id . ' pour le résultat #' . $resultat->id);
                    }
                }

                // Classification améliorée - vérifier d'abord la configuration des matières
                $configMatiere = ESBTPConfigMatiere::where([
                    'matiere_id' => $resultat->matiere_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->first();

                $type = null;
                if ($configMatiere) {
                    $config_data = json_decode($configMatiere->config, true) ?? [];
                    $type = $config_data['type'] ?? null;
                }

                // Utiliser le type de la configuration ou les propriétés de la matière comme fallback
                if ($type === 'general') {
                    $resultatsGeneraux->push($resultat);
                } elseif ($type === 'technique') {
                    $resultatsTechniques->push($resultat);
                } elseif ($resultat->matiere && ($resultat->matiere->type == 'general' || (isset($resultat->matiere->type_formation) && $resultat->matiere->type_formation == 'generale'))) {
                    $resultatsGeneraux->push($resultat);
                } elseif ($resultat->matiere) {
                    $resultatsTechniques->push($resultat);
                }
            }

            // Calculer les moyennes
            $moyenneGeneraux = $resultatsGeneraux->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultatsGeneraux);
            $moyenneTechnique = $resultatsTechniques->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultatsTechniques);
            $moyenneGenerale = $resultats->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultats);

            // Calcul des rangs pour les résultats généraux
            if (!$resultatsGeneraux->isEmpty()) {
                // Trier par moyenne (décroissant)
                $resultatsGeneraux = $resultatsGeneraux->sortByDesc('moyenne')->values();

                // Assigner les rangs
                $previousMoyenne = null;
                $previousRank = 0;
                $sameRankCount = 0;

                foreach ($resultatsGeneraux as $index => $resultat) {
                    if ($previousMoyenne !== null && $resultat->moyenne == $previousMoyenne) {
                        // Même moyenne, même rang
                        $resultat->rang = $previousRank;
                        $sameRankCount++;
                    } else {
                        // Moyenne différente, nouveau rang
                        $resultat->rang = $index + 1;
                        $previousRank = $resultat->rang;
                        $previousMoyenne = $resultat->moyenne;
                        $sameRankCount = 0;
                    }
                }
            }

            // Calcul des rangs pour les résultats techniques
            if (!$resultatsTechniques->isEmpty()) {
                // Trier par moyenne (décroissant)
                $resultatsTechniques = $resultatsTechniques->sortByDesc('moyenne')->values();

                // Assigner les rangs
                $previousMoyenne = null;
                $previousRank = 0;
                $sameRankCount = 0;

                foreach ($resultatsTechniques as $index => $resultat) {
                    if ($previousMoyenne !== null && $resultat->moyenne == $previousMoyenne) {
                        // Même moyenne, même rang
                        $resultat->rang = $previousRank;
                        $sameRankCount++;
                    } else {
                        // Moyenne différente, nouveau rang
                        $resultat->rang = $index + 1;
                        $previousRank = $resultat->rang;
                        $previousMoyenne = $resultat->moyenne;
                        $sameRankCount = 0;
                    }
                }
            }

            // Liste des professeurs par matière (enrichie avec plus de matières possibles)
            $professeursMatiere = [
                // Matières d'enseignement général
                'Anglais' => 'M.FOFANA Lassina',
                'Gestion' => 'M.YAO YAOBLE',
                'Informatique' => 'Mme MANDOUA Nadège',
                'Mathématiques' => 'M.BONE Oussama',
                'Physique' => 'M.KOFFI Bruno',
                'Technique d\'Expression Française' => 'M.DJE Charles',
                'Communication' => 'M.KOUADIO Paul',
                'Économie' => 'Mme KONAN Sarah',
                'Droit' => 'M.KOUAME Jean',

                // Matières techniques/professionnelles
                'Aménagement foncier cadastre' => 'M.ASSALE Arsène',
                'Calculs Topo' => 'M.YAO Niamba',
                'CAO-DAO' => 'M.KIGNELMAN Christian',
                'Géodésie' => 'M.AKA Bleh',
                'Topométrie appliquée au génie civil' => 'M.ATTA Atta',
                'Topométrie générale' => 'M.KOUASSI Jean',
                'Photogrammétrie Analogique' => 'M.ANE Jean',
                'Traitement de données/Télédétection' => 'M.TRAORE Salim',
                'Dessin technique' => 'M.DIALLO Amadou',
                'Génie civil' => 'M.BAKAYOKO Ibrahim',
                'Résistance des matériaux' => 'M.TOURE Karim',
                'Béton armé' => 'Mme DIALLO Fatoumata',
                'Construction métallique' => 'M.CISSE Mohamed',
                'Mécanique des sols' => 'M.DIABATE Moussa',
                'Hydraulique' => 'M.TANOH Georges',
                'Routes et VRD' => 'M.KONE Adama',
                'Mathématiques appliquées' => 'M.COULIBALY Ali',
                'Physique appliquée' => 'Mme SYLLA Aminata',
                'Structures' => 'M.FOFANA Omar',
                'Matériaux de construction' => 'Mme BAH Mariam',
                'Architecture' => 'M.DOUMBIA Souleymane',
                'Gestion de projet' => 'M.CAMARA Issiaka',
                'BTP et environnement' => 'Mme KEITA Aissata'
            ];

            // Récupérer les professeurs du bulletin s'ils existent
            $professeursBulletin = [];
            if (isset($bulletin->id) && $bulletin->professeurs) {
                $professeursBulletin = json_decode($bulletin->professeurs, true) ?: [];
            }

            // Ajouter les professeurs aux résultats et les valeurs par défaut pour rang et appréciation
            $resultats->each(function($resultat) use ($professeursMatiere, $professeursBulletin) {
                // Vérification des propriétés matière
                if ($resultat->matiere) {
                    $nomMatiere = $resultat->matiere->nom ?? $resultat->matiere->name ?? '';

                    // Ajouter le professeur en priorité depuis le bulletin
                    if (isset($professeursBulletin[$resultat->matiere_id])) {
                        $resultat->professeur = $professeursBulletin[$resultat->matiere_id];
                    } else {
                        $resultat->professeur = $professeursMatiere[$nomMatiere] ?? 'N/A';
                    }
                } else {
                    $resultat->professeur = 'N/A';
                }

                // Ajouter le rang s'il n'existe pas
                if (!isset($resultat->rang)) {
                    $resultat->rang = 'N/A';
                }

                // Ajouter l'appréciation si elle n'existe pas
                if (!isset($resultat->appreciation)) {
                    // Déterminer l'appréciation en fonction de la moyenne
                    if ($resultat->moyenne >= 16) {
                        $resultat->appreciation = 'Excellent';
                    } elseif ($resultat->moyenne >= 14) {
                        $resultat->appreciation = 'Très Bien';
                    } elseif ($resultat->moyenne >= 12) {
                        $resultat->appreciation = 'Bien';
                    } elseif ($resultat->moyenne >= 10) {
                        $resultat->appreciation = 'Assez Bien';
                    } elseif ($resultat->moyenne >= 8) {
                        $resultat->appreciation = 'Passable';
                    } else {
                        $resultat->appreciation = 'Insuffisant';
                    }
                }
            });

            // Calcul des statistiques de classe
            $etudiantsClasse = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($classe_id, $annee_universitaire_id) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('status', 'active');
            })->get();

            // Initialiser les variables de statistiques
            $plusForteMoyenne = 0;
            $plusFaibleMoyenne = 20;
            $sommeMoyennes = 0;
            $effectifClasse = count($etudiantsClasse);

            // Calculer les statistiques si des étudiants sont inscrits
            if ($effectifClasse > 0) {
                foreach ($etudiantsClasse as $etud) {
                    $moyenneEtud = $this->calculerMoyenneEtudiant($etud->id, $classe_id, $periode, $annee_universitaire_id);

                    // Ignorer les moyennes nulles ou négatives pour les statistiques
                    if ($moyenneEtud > 0) {
                        if ($moyenneEtud > $plusForteMoyenne) {
                            $plusForteMoyenne = $moyenneEtud;
                        }

                        if ($moyenneEtud < $plusFaibleMoyenne) {
                            $plusFaibleMoyenne = $moyenneEtud;
                        }

                        $sommeMoyennes += $moyenneEtud;
                    }
                }

                // Créer un tableau avec les moyennes des étudiants
                $moyennesClasse = [];

                foreach ($etudiantsClasse as $etud) {
                    $moyenne = $this->calculerMoyenneEtudiant($etud->id, $classe_id, $periode, $annee_universitaire_id);
                    if ($moyenne > 0) {
                        $moyennesClasse[$etud->id] = $moyenne;
                    }
                }

                // Trier les moyennes dans l’ordre décroissant
                arsort($moyennesClasse);

                function formatRangAvecSuffix($rang){
                    if ($rang == 1) {
                        return '1er';
                    }
                    return $rang . 'ème';
                }
                // Déterminer le rang de l’étudiant courant
                $rang = 'N/A';
                $position = 1;

                foreach ($moyennesClasse as $id => $moyenne) {
                    if ($id == $etudiant_id) {
                        $rang = formatRangAvecSuffix($position);
                        break;
                    }
                    $position++;
                }



                // S'assurer que nous avons au moins un étudiant avec une moyenne valide
                if ($sommeMoyennes > 0) {
                    $moyenneClasse = $sommeMoyennes / $effectifClasse;
                } else {
                    $moyenneClasse = 0;
                    $plusFaibleMoyenne = 0;
                }
            } else {
                $plusForteMoyenne = $moyenneGenerale;
                $plusFaibleMoyenne = $moyenneGenerale;
                $moyenneClasse = $moyenneGenerale;
            }

            // Si aucun étudiant n'a de moyenne valide
            if ($plusFaibleMoyenne == 20 && $plusForteMoyenne == 0) {
                $plusFaibleMoyenne = 0;
            }

            // Calculer les absences depuis les enregistrements d'attendance
            $dateDebut = $anneeUniversitaire->date_debut;
            $dateFin = $anneeUniversitaire->date_fin;

            // Utilisation du service d'absences pour calculer les absences
            \Log::info("Calcul des absences pour l'étudiant ID: {$etudiant_id}, classe ID: {$classe_id}, période: du {$dateDebut} au {$dateFin}");
            $absences = $this->absenceService->calculerDetailAbsences($etudiant_id, $classe_id, $dateDebut, $dateFin);
            $absencesJustifiees = $absences['justifiees'];
            $absencesNonJustifiees = $absences['non_justifiees'];

            // Log pour le debugging
            \Log::info("Résultats du calcul des absences:", [
                'absencesJustifiees' => $absencesJustifiees,
                'absencesNonJustifiees' => $absencesNonJustifiees,
                'total' => $absencesJustifiees + $absencesNonJustifiees
            ]);

            // Note d'assiduité (peut être ajustée selon vos règles)
            $noteAssiduite = $this->calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees);

            // Préparation des données pour la vue
            $data = [
                'bulletin' => $bulletin,
                'etudiant' => $etudiant,
                'classe' => $classe,
                'anneeUniversitaire' => $anneeUniversitaire,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGeneraux' => $moyenneGeneraux,
                'moyenneTechnique' => $moyenneTechnique,
                'moyenneGenerale' => $moyenneGenerale,
                'absencesJustifiees' => $absencesJustifiees,
                'absencesNonJustifiees' => $absencesNonJustifiees,
                'noteAssiduite' => $noteAssiduite, // Utiliser la valeur calculée au lieu d'une chaîne vide
                'moyenneSemestre1' =>number_format($moyenneGenerale, 2), // À implémenter si nécessaire
                'plusForteMoyenne' => $bulletin->plus_forte_moyenne ?? number_format($plusForteMoyenne, 2),
                'plusFaibleMoyenne' => $bulletin->plus_faible_moyenne ?? number_format($plusFaibleMoyenne, 2),
                'moyenneClasse' => $bulletin->moyenne_classe ?? number_format($moyenneClasse, 2),
                'effectifClasse' => $effectifClasse,
                'logoBase64' => $logoBase64,
                'studentRang' => $rang
            ];

            // Journaliser les données de debug avant génération du PDF
            \Log::info('Données préparées pour la génération du PDF:', [
                'nb_resultats_generaux' => $resultatsGeneraux->count(),
                'nb_resultats_techniques' => $resultatsTechniques->count(),
                'matiere_names_general' => $resultatsGeneraux->pluck('matiere.nom')->toArray(),
                'matiere_names_technique' => $resultatsTechniques->pluck('matiere.nom')->toArray(),
                'professeurs_general' => $resultatsGeneraux->pluck('professeur')->toArray(),
                'professeurs_technique' => $resultatsTechniques->pluck('professeur')->toArray(),
                'ranks_general' => $resultatsGeneraux->pluck('rang')->toArray(),
                'ranks_technique' => $resultatsTechniques->pluck('rang')->toArray(),
                'absences_justifiees' => $absencesJustifiees,
                'absences_non_justifiees' => $absencesNonJustifiees,
            ]);

            // S'assurer que les variables d'absence sont bien définies
            $data['absences_justifiees'] = $absencesJustifiees;
            $data['absences_non_justifiees'] = $absencesNonJustifiees;

            // Log supplémentaire pour vérifier que les variables sont bien définies
            \Log::info('Variables d\'absence définies dans $data:', [
                'absencesJustifiees' => $data['absencesJustifiees'] ?? 'Non défini',
                'absencesNonJustifiees' => $data['absencesNonJustifiees'] ?? 'Non défini',
                'absences_justifiees' => $data['absences_justifiees'] ?? 'Non défini',
                'absences_non_justifiees' => $data['absences_non_justifiees'] ?? 'Non défini'
            ]);

            // Générer le PDF
            $pdf = PDF::loadView('esbtp.bulletins.pdf', $data);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

            // Nom du fichier PDF
            $filename = 'bulletin_' .
                        ($etudiant ? $etudiant->matricule : 'unknown') . '_' .
                        ($classe ? $classe->code : 'unknown') . '_' .
                        $periode . '_' .
                        ($anneeUniversitaire ? $anneeUniversitaire->libelle : 'unknown') . '.pdf';

            // Télécharger le PDF
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de la génération du PDF par paramètres: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Détermine la mention en fonction de la moyenne
     *
     * @param float $moyenne
     * @return string
     */
    private function getMention($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Félicitation';
        } elseif ($moyenne >= 14) {
            return 'Tableau d\'honneur';
        } elseif ($moyenne >= 12) {
            return 'Encouragement';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } elseif ($moyenne >= 8) {
            return 'Avertissement (Travail)';
        } else {
            return 'Blâme (Conduite)';
        }
    }

    /**
     * Calcule une moyenne pondérée pour un ensemble de résultats
     *
     * @param \Illuminate\Support\Collection $resultats
     * @return float
     */
    private function calculerMoyennePonderee($resultats)
    {
        $sommeNotes = 0;
        $sommeCoefficients = 0;
        $request = request();
                    // Récupérer les paramètres
                    $classe_id = $request->classe_id;
                    // Récupérer etudiant_id soit depuis etudiant_id, soit depuis bulletin
                    $etudiant_id = $request->etudiant_id ?? $request->bulletin;
                    $periode = $request->periode;
                    $annee_universitaire_id = $request->annee_universitaire_id;
                    $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($annee_universitaire_id);
                                // Calculer les absences depuis les enregistrements d'attendance
            $dateDebut = $anneeUniversitaire->date_debut;
            $dateFin = $anneeUniversitaire->date_fin;
            
        //Utilisation du service d'absences pour calculer les absences
        $absences = $this->absenceService->calculerDetailAbsences($etudiant_id, $classe_id, $dateDebut, $dateFin);
        $absencesJustifiees = $absences['justifiees'];
        $absencesNonJustifiees = $absences['non_justifiees'];

        foreach ($resultats as $resultat) {
            $sommeNotes += $resultat->moyenne * $resultat->coefficient;
            $sommeCoefficients += $resultat->coefficient;
        }

        if ($sommeCoefficients > 0) {
            return ($sommeNotes / $sommeCoefficients) +$this->calculerNoteAssiduite($absencesJustifiees,$absencesNonJustifiees);
        }

        return 0;
    }

    /**
     * Calcule la note d'assiduité en fonction des absences
     *
     * @param int $absencesJustifiees
     * @param int $absencesNonJustifiees
     * @return float
     */
    private function calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees)
    {

        // Chaque heure d'absence non justifiée pénalise plus que les justifiées
        //$totalPenalite = ($absencesJustifiees * 0.1) + ($absencesNonJustifiees * 0.5);
         switch (true) {
                    case $absencesNonJustifiees == 0:
                        return 0.13;
                        break;
                    case $absencesNonJustifiees == 1:
                        return 0;
                        break;
                    case $absencesNonJustifiees == 2:
                        return -0.13;
                        break;
                    case $absencesNonJustifiees == 3:
                        return -0.39;
                        break;
                    case $absencesNonJustifiees == 4:
                        return -0.39;
                        break;
                    case $absencesNonJustifiees >= 5: // 5 ou plus
                        return -0.5;
                }

        // La note de base est 20, on soustrait les pénalités
        // $note = 20 + $totalPenalite;

        // // La note ne peut pas être négative
        // if ($note < 0) $note = 0;

        // //Une note ne peut pas être supérieur à 20
        // if ($note > 20 ) $note = 20;

        // return number_format($note,2);

    }

    /**
     * Calcule la moyenne générale d'un étudiant pour une classe, période et année universitaire données
     *
     * @param int $etudiant_id
     * @param int $classe_id
     * @param string $periode
     * @param int $annee_universitaire_id
     * @return float
     */
    private function calculerMoyenneEtudiant($etudiant_id, $classe_id, $periode, $annee_universitaire_id)
    {
        // Récupérer les résultats de l'étudiant pour les paramètres spécifiés
        $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant_id)
            ->where('classe_id', $classe_id)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $annee_universitaire_id)
            ->get();

        // Si aucun résultat n'est trouvé, retourner 0
        if ($resultats->isEmpty()) {
            return 0;
        }

        // Calculer la moyenne pondérée en utilisant la méthode existante
        return $this->calculerMoyennePonderee($resultats);
    }

    /**
     * Prévisualise les moyennes d'un étudiant pour une classe, période et année universitaire données
     * Permet de modifier les moyennes avant génération du bulletin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function previewMoyennes(Request $request)
    {
        // Vérifier les permissions et les rôles
        if (!auth()->user()->hasRole('superAdmin') && !auth()->user()->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les permissions nécessaires pour modifier les moyennes.');
        }

        // Valider les paramètres avec une validation plus stricte pour la période
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
        ]);

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Si la période est vide, utiliser semestre1 comme valeur par défaut
        if (empty($periode)) {
            $periode = 'semestre1';
        }

        // Normaliser la période si nécessaire
        if (!in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
            // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
            $periodePourBDD = 'semestre1';
            // Mais conserver la période originale pour l'affichage
        } else {
            $periodePourBDD = $periode;
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
        $classe = \App\Models\ESBTPClasse::with('matieres')->findOrFail($classeId);
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

        // MODIFIÉ: Récupérer les notes de l'étudiant avec une requête plus flexible, similaire à resultatEtudiant
        // Récupérer toutes les notes de l'étudiant d'abord
        $notesQuery = \App\Models\ESBTPNote::where('etudiant_id', $etudiantId)
            ->with(['evaluation.matiere', 'matiere']);

        // Filtrer par période (semestre)
        $notesQuery->where(function ($q) use ($periodePourBDD) {
            $q->where('semestre', $periodePourBDD)
              ->orWhereHas('evaluation', function ($query) use ($periodePourBDD) {
                    $query->where('periode', $periodePourBDD);
                });
        });

        // MODIFIÉ: Utilisation du scope byClasse pour filtrer les notes par classe
        // Cela limite les notes aux évaluations de la classe spécifique demandée
        $notesQuery->byClasse($classeId);

        // MODIFIÉ: Filtrage par année universitaire pour inclure aussi l'année précédente
        // Utiliser le scope byAnneeUniversitaireWithPrevious qui permet de récupérer les notes
        // des évaluations de l'année courante (anneeUniversitaireId) ET de l'année précédente (anneeUniversitaireId-1)
        $notesQuery->byAnneeUniversitaireWithPrevious($anneeUniversitaireId);

        // Log pour le débogage - voir quelles notes sont récupérées
        \Log::debug("Notes query for student {$etudiantId}, class {$classeId}, period {$periodePourBDD}, year {$anneeUniversitaireId}");

        $notes = $notesQuery->get();

        // Log des notes récupérées
        foreach ($notes as $note) {
            \Log::debug("Note ID: {$note->id}, Value: {$note->note}, Evaluation ID: {$note->evaluation_id}, Evaluation Year: {$note->evaluation->annee_universitaire_id}, Matiere ID: {$note->evaluation->matiere_id}");
        }

        // Si aucune note n'est trouvée, vérifier s'il existe des notes dans l'année précédente uniquement
        if ($notes->isEmpty()) {
            \Log::debug("No notes found for current criteria. Checking previous year explicitly.");
            $prevYearId = $anneeUniversitaireId - 1;

            $prevNotesQuery = \App\Models\ESBTPNote::query()
        ->where('etudiant_id', $etudiantId)
                ->withValidEvaluation()
                ->whereHas('evaluation', function($query) use ($periodePourBDD, $classeId, $prevYearId) {
                    $query->where('classe_id', $classeId);
                    if ($periodePourBDD != 'annuel') {
                        $query->where('periode', $periodePourBDD);
                    }
                    $query->where('annee_universitaire_id', $prevYearId);
                });

            $prevNotes = $prevNotesQuery->get();

            if ($prevNotes->isNotEmpty()) {
                \Log::debug("Found notes in previous year {$prevYearId}");
                $notes = $prevNotes;
            }
        }

        // Organiser les notes par matière
        $notesByMatiere = [];
        foreach ($notes as $note) {
            if (!$note->evaluation) {
                \Log::debug("Skipping note ID {$note->id} - no evaluation");
                continue;
            }
            $matiere = $note->evaluation->matiere;
            if (!$matiere) {
                \Log::debug("Skipping note ID {$note->id} - no matiere for evaluation {$note->evaluation_id}");
                continue;
            }

            $matiereId = $matiere->id;
            if (!isset($notesByMatiere[$matiereId])) {
                $notesByMatiere[$matiereId] = [
                    'matiere' => $matiere,
                    'notes' => [],
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0
                ];
            }

            $notesByMatiere[$matiereId]['notes'][] = $note;
        }

        // Récupérer les résultats existants pour cet étudiant
        $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('periode', $periodePourBDD)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->with('matiere')
            ->get();

        // Préparer les données des résultats pour l'affichage et l'édition
        $resultatsData = [];
        foreach ($resultats as $resultat) {
            // Vérifier si la relation matiere existe
            if (!$resultat->matiere) {
                // Si la relation n'existe pas, essayer de récupérer la matière directement
                $matiere = \App\Models\ESBTPMatiere::find($resultat->matiere_id);

                // Si la matière n'existe toujours pas, ignorer ce résultat
                if (!$matiere) {
                    continue;
                }
            } else {
                $matiere = $resultat->matiere;
            }

            $resultatsData[$resultat->matiere_id] = [
                'id' => $resultat->id,
                'matiere' => $matiere,
                'moyenne' => $resultat->moyenne,
                'coefficient' => $resultat->coefficient,
                'rang' => $resultat->rang,
                'appreciation' => $resultat->appreciation
            ];
        }

        // Si des moyennes calculées n'ont pas de résultat correspondant, les ajouter
        foreach ($notesByMatiere as $matiereId => $matiereData) {
            if (!isset($resultatsData[$matiereId])) {
                // CORRECTION AMÉLIORÉE: Récupérer systématiquement l'objet matière directement
                // depuis la base de données en utilisant l'ID
                $matiere = \App\Models\ESBTPMatiere::find($matiereId);

                if (!$matiere) {
                    \Log::warning("Matiere with ID {$matiereId} not found when adding calculated averages - skipping");
                    continue; // Ignorer cette entrée si la matière n'existe pas
                }

                $resultatsData[$matiereId] = [
                    'id' => null,
                    'matiere' => $matiere, // Utiliser l'objet matière fraîchement récupéré
                    'moyenne' => $matiereData['moyenne'],
                    'coefficient' => $matiereData['total_coefficients'],
                    'rang' => null,
                    'appreciation' => null
                ];
            }
        }

        // Calculer la moyenne pour chaque matière
        foreach ($notesByMatiere as $matiereId => &$matiereData) {
            $totalPoints = 0;
            $totalCoefficients = 0;

            foreach ($matiereData['notes'] as $note) {
                if ($note->evaluation && $note->evaluation->bareme > 0) {
                    $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                    $bareme = floatval($note->evaluation->bareme);
                    $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;

                    $normalized = ($noteValue / $bareme) * 20;
                    $totalPoints += $normalized * $coefficient;
                    $totalCoefficients += $coefficient;
                }
            }

            $matiereData['total_points'] = $totalPoints;
            $matiereData['total_coefficients'] = $totalCoefficients;
            $matiereData['moyenne'] = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;

        }

        // Afficher la vue de prévisualisation des moyennes
        return view('esbtp.resultats.moyennes-preview', compact(
            'etudiant',
            'classe',
            'periode',
            'anneeUniversitaire',
            'notesByMatiere',
            'resultatsData'
        ));
    }

    /**
     * Met à jour les moyennes des étudiants
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateMoyennes(Request $request)
    {
        // Vérifier les permissions et les rôles
        if (!auth()->user()->hasRole('superAdmin') && !auth()->user()->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les permissions nécessaires pour modifier les moyennes.');
        }

        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'periode' => 'required',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'resultats' => 'required|array',
            'resultats.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne' => 'required|numeric|min:0|max:20',
            'resultats.*.coefficient' => 'required|numeric|min:0',
            'resultats.*.appreciation' => 'nullable|string|max:255'
        ]);

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Normaliser la période si nécessaire
        if (!in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
            // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
            $periodePourBDD = 'semestre1';
        } else {
            $periodePourBDD = $periode;
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
        $classe = \App\Models\ESBTPClasse::findOrFail($classeId);
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

        // Traiter chaque résultat
        foreach ($request->resultats as $resultatData) {
            $matiereId = $resultatData['matiere_id'];
            $moyenne = $resultatData['moyenne'];
            $coefficient = $resultatData['coefficient'];
            $appreciation = $resultatData['appreciation'] ?? null;
            $resultatId = $resultatData['id'] ?? null;

            // Si un ID de résultat est fourni, mettre à jour le résultat existant
            if ($resultatId) {
                $resultat = \App\Models\ESBTPResultat::find($resultatId);
                if ($resultat) {
                    $resultat->update([
                        'moyenne' => $moyenne,
                        'coefficient' => $coefficient,
                        'appreciation' => $appreciation
                    ]);
                    continue;
                }
            }

            // Sinon, créer un nouveau résultat
            \App\Models\ESBTPResultat::create([
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'matiere_id' => $matiereId,
                'periode' => $periodePourBDD,
                'annee_universitaire_id' => $anneeUniversitaireId,
                'moyenne' => $moyenne,
                'coefficient' => $coefficient,
                'appreciation' => $appreciation
            ]);
        }

        // Rediriger vers la page des résultats de l'étudiant
        return redirect()->route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiantId,
            'classe_id' => $classeId,
            'periode' => $periode, // Utiliser la période originale pour la redirection
            'annee_universitaire_id' => $anneeUniversitaireId
        ])->with('success', 'Les moyennes ont été mises à jour avec succès.');
    }

    /**
     * Affiche le formulaire de configuration des types de matières
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function configMatieresTypeFormation(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
        }

        // Récupérer les paramètres
        $etudiant_id = $request->etudiant_id ?? $request->bulletin;
        $classe_id = $request->classe_id;
        $periode = $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour configMatieresTypeFormation:', [
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) {
            return back()->with('error', 'Paramètres manquants pour la configuration des matières.');
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = ESBTPEtudiant::find($etudiant_id);
        $classe = ESBTPClasse::with(['filiere', 'niveau'])->find($classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // S'assurer que $classe est un objet, pas un tableau
        if (is_array($classe)) {
            // Si $classe est un tableau, le convertir en objet ESBTPClasse
            $classeObj = ESBTPClasse::with(['filiere', 'niveau'])->find($classe_id);
            if (!$classeObj) {
                return back()->with('error', 'Classe introuvable.');
            }
            $classe = $classeObj;
        }

        if (!$etudiant || !$classe || !$anneeUniversitaire) {
            return back()->with('error', 'Données introuvables.');
        }

        // Initialiser une collection vide pour les matières
        $matieres = collect();

        // APPROCHE 1: Récupérer les matières basées sur les résultats de l'étudiant
        try {
            // Récupérer les résultats de l'étudiant pour la classe, période et année universitaire
            $resultats = ESBTPResultat::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->get();

            if ($resultats->isNotEmpty()) {
                // Extraire les IDs des matières directement des résultats
                $matieresIds = $resultats->pluck('matiere_id')->unique()->toArray();
                if (!empty($matieresIds)) {
                    $matieres = ESBTPMatiere::whereIn('id', $matieresIds)->get();
                    \Log::info('Matières récupérées depuis les résultats de l\'étudiant', [
                        'count' => $matieres->count(),
                        'matiere_ids' => $matieresIds
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des matières via les résultats de l\'étudiant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // APPROCHE 2: Si aucune matière n'est trouvée, récupérer les matières de la classe
        if ($matieres->isEmpty()) {
            try {
                $matieres = $classe->matieres;
                \Log::info('Matières récupérées depuis la classe', [
                    'count' => $matieres->count()
                ]);

                if ($matieres->isEmpty()) {
                    return back()->with('error', 'Aucune matière trouvée pour cette classe.');
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la récupération des matières depuis la classe', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return back()->with('error', 'Erreur lors de la récupération des matières.');
            }
        }

        // Récupérer les configurations existantes
        $configsMatieres = ESBTPConfigMatiere::withTrashed()->where([
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ])->get()->keyBy('matiere_id');

        // Initialisation des catégories de matières
        $general = [];
        $technique = [];

        // Parcourir les matières pour les classer
        foreach ($matieres as $matiere) {
            $config = $configsMatieres->get($matiere->id);

            // Si une configuration existe pour cette matière
            if ($config && isset($config->config) && is_string($config->config)) {
                $configData = json_decode($config->config, true);
                // Utiliser la clé 'type' au lieu de 'type_formation'
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;

                if ($typeFormation === 'general' || $typeFormation === 'generale') {
                    $general[] = $matiere->id;
                } elseif ($typeFormation === 'technique' || $typeFormation === 'technologique_professionnelle') {
                    $technique[] = $matiere->id;
                }
            } else {
                // Classification automatique basée sur le nom
                $nomMatiere = strtolower($matiere->nom ?? $matiere->name ?? '');

                if (
                    str_contains($nomMatiere, 'math') ||
                    str_contains($nomMatiere, 'anglais') ||
                    str_contains($nomMatiere, 'français') ||
                    str_contains($nomMatiere, 'francais') ||
                    str_contains($nomMatiere, 'communication')
                ) {
                    $general[] = $matiere->id;
                } else {
                    $technique[] = $matiere->id;
                }
            }
        }

        // Préparer les données pour la vue
        $matieresData = [];
        foreach ($matieres as $matiere) {
            $config = $configsMatieres->get($matiere->id);
            $typeFormation = null;
            if ($config && isset($config->config) && is_string($config->config)) {
                $configData = json_decode($config->config, true);
                // Utiliser la clé 'type' au lieu de 'type_formation'
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;
            }

            // Transformer en objet stdClass au lieu d'un tableau associatif
            $matiereObj = new \stdClass();
            $matiereObj->id = $matiere->id;
            $matiereObj->nom = $matiere->nom ?? $matiere->name ?? '';
            $matiereObj->name = $matiere->name ?? $matiere->nom ?? '';
            $matiereObj->type_formation = $typeFormation;

            $matieresData[] = $matiereObj;
        }

        // Correction du chemin de la vue
        return view('esbtp.bulletins.config-matieres', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'anneeUniversitaire' => $anneeUniversitaire,
            'periode' => $periode,
            'matieres' => $matieresData,
            'general' => $general,
            'technique' => $technique,
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);
    }

    /**
     * Enregistre la configuration des types de matières
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function saveConfigMatieresTypeFormation(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
        }

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour saveConfigMatieresTypeFormation:', [
            'request' => $request->all()
        ]);

        // Valider les données reçues
        $request->validate([
            'etudiant_id' => 'required',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'periode' => 'required',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'matiere_type' => 'required|array',
        ]);

        // Récupérer les paramètres
        $etudiant_id = $request->etudiant_id;
        $classe_id = $request->classe_id;
        $periode = $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $matiere_types = $request->matiere_type;

        try {
            DB::beginTransaction();

            // Supprimer les configurations existantes qui ne sont plus dans la liste envoyée
            // Récupérer toutes les matières configurées précédemment pour cette classe/période/année
            $existingConfigs = ESBTPConfigMatiere::withTrashed()
                ->where([
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])
                ->pluck('matiere_id')
                ->toArray();

            // Trouver les matières qui ne sont plus dans la nouvelle configuration
            $removedMatieres = array_diff(
                $existingConfigs,
                array_keys(array_filter($matiere_types, function($type) { return $type !== 'none'; }))
            );

            // Supprimer définitivement ces configurations
            if (!empty($removedMatieres)) {
                ESBTPConfigMatiere::withTrashed()
                    ->where([
                        'classe_id' => $classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id
                    ])
                    ->whereIn('matiere_id', $removedMatieres)
                    ->forceDelete();
            }

            // Initialiser les tableaux pour stocker les matières par type de formation
            $matieresGenerales = [];
            $matieresTechniques = [];

            // Organiser les matières par type
            foreach ($matiere_types as $matiere_id => $type) {
                if ($type == 'general') {
                    $matieresGenerales[] = (int)$matiere_id;
                    // Utiliser le même type que dans le formulaire pour la cohérence
                    $type_value = 'general';
                } elseif ($type == 'technique') {
                    $matieresTechniques[] = (int)$matiere_id;
                    // Utiliser le même type que dans le formulaire pour la cohérence
                    $type_value = 'technique';
                } else {
                    // Si "none", ignorer cette matière
                    continue;
                }

                // Utiliser updateOrCreate au lieu de delete puis create
                ESBTPConfigMatiere::withTrashed()->updateOrCreate(
                    [
                        'matiere_id' => $matiere_id,
                        'classe_id' => $classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id
                    ],
                    [
                        'config' => json_encode(['type' => $type_value]),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'deleted_at' => null // Restaurer l'enregistrement s'il était soft-deleted
                    ]
                );
            }

            // Récupérer ou créer le bulletin pour cet étudiant
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Préparer la configuration des matières pour le bulletin
            $configMatieres = [
                'generales' => $matieresGenerales,
                'techniques' => $matieresTechniques
            ];

            // Sauvegarder la configuration dans le bulletin
            $bulletin->config_matieres = json_encode($configMatieres);
            $bulletin->save();

            \Log::info('Configuration des matières sauvegardée dans le bulletin', [
                'bulletin_id' => $bulletin->id,
                'config_matieres' => $bulletin->config_matieres,
                'matieres_generales' => count($matieresGenerales),
                'matieres_techniques' => count($matieresTechniques)
            ]);

            DB::commit();

            // Déterminer l'action suivante
            $action = $request->action ?? 'save';

            if ($action === 'edit_professeurs' || $action === 'save_and_edit_profs') {
                // Rediriger vers l'édition des professeurs
                $url = "/esbtp-special/bulletins/edit-professeurs?" . http_build_query([
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return redirect()->to($url)->with('success', 'Configuration des matières enregistrée avec succès.');
            } else if ($action === 'return_results' || $action === 'save_and_return') {
                // Rediriger vers les résultats de l'étudiant
                $url = "/esbtp/resultats/etudiant/{$etudiant_id}?" . http_build_query([
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return redirect()->to($url)->with('success', 'Configuration des matières enregistrée avec succès.');
            } else {
                // Rester sur la même page
                return back()->with('success', 'Configuration des matières enregistrée avec succès.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la sauvegarde de la configuration des matières : ' . $e->getMessage());
            \Log::error('Trace : ' . $e->getTraceAsString());
            return back()->with('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }
    }

    /**
     * Affiche le formulaire d'édition des professeurs
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function editProfesseurs(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
        }

        // Récupérer les paramètres
        $etudiant_id = $request->etudiant_id ?? $request->bulletin;
        $classe_id = $request->classe_id;
        $periode = $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour editProfesseurs:', [
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) {
            return back()->with('error', 'Paramètres manquants pour l\'édition des professeurs.');
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = ESBTPEtudiant::find($etudiant_id);
        $classe = ESBTPClasse::find($classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        if (!$etudiant || !$classe || !$anneeUniversitaire) {
            return back()->with('error', 'Données introuvables.');
        }

        // Vérifier si la configuration des matières a été faite
        $configsMatieres = ESBTPConfigMatiere::where([
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ])->get();

        if ($configsMatieres->isEmpty()) {
            // Rediriger vers la configuration des matières
            $url = "/esbtp-special/bulletins/config-matieres?" . http_build_query([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);
            return redirect()->to($url)->with('error', 'Vous devez d\'abord configurer les types de matières.');
        }

        // Récupérer le bulletin s'il existe
        $bulletin = ESBTPBulletin::where([
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ])->first();

        // Récupérer les matières avec leur type de formation
        $matieres = [];
        foreach ($configsMatieres as $config) {
            if ($config->matiere) {
                // Récupérer le type depuis le config en décodant le JSON et en cherchant la clé 'type'
                $config_data = json_decode($config->config, true) ?? [];
                $typeFormation = $config_data['type'] ?? null;

                // Journaliser pour le débogage
                \Log::debug('Config matière trouvée:', [
                    'matiere_id' => $config->matiere_id,
                    'matiere_nom' => $config->matiere->nom ?? 'Non défini',
                    'config_raw' => $config->config,
                    'config_decoded' => $config_data,
                    'type_formation' => $typeFormation
                ]);

                // Récupérer le nom du professeur pour cette matière
                $professeurNom = '';
                if ($bulletin && $bulletin->professeurs) {
                    $professeurs = json_decode($bulletin->professeurs, true);
                    $professeurNom = $professeurs[$config->matiere_id] ?? '';
                }

                // Récupérer le nom de la matière avec vérification
                $matiereName = 'Matière non identifiée';
                if ($config->matiere) {
                    $matiereName = $config->matiere->nom ?? $config->matiere->name ?? 'Matière #' . $config->matiere_id;
                }

                // Journaliser pour vérifier le nom de la matière
                \Log::info('Matière ajoutée:', [
                    'id' => $config->matiere_id,
                    'nom_recupere' => $matiereName,
                    'matiere_object' => $config->matiere ? 'Existe' : 'Null',
                    'matiere_nom_property' => $config->matiere ? ($config->matiere->nom ?? 'Non défini') : 'N/A',
                    'matiere_name_property' => $config->matiere ? ($config->matiere->name ?? 'Non défini') : 'N/A'
                ]);

                $matieres[] = [
                    'id' => $config->matiere_id,
                    'nom' => $matiereName,
                    'type_formation' => $typeFormation,
                    'professeur_nom' => $professeurNom
                ];
            }
        }

        // Journaliser les matières trouvées
        \Log::info('Matières trouvées pour editProfesseurs:', [
            'nombre_matieres' => count($matieres),
            'matieres' => $matieres
        ]);

        // Grouper les matières par type de formation
        $matieresGenerales = array_filter($matieres, function($matiere) {
            return $matiere['type_formation'] === 'general';
        });

        $matieresProf = array_filter($matieres, function($matiere) {
            return $matiere['type_formation'] === 'technique';
        });

        // Journaliser les résultats du filtrage
        \Log::info('Résultats du filtrage des matières:', [
            'matieres_generales' => count($matieresGenerales),
            'matieres_techniques' => count($matieresProf)
        ]);

        // Récupérer les professeurs du bulletin
        $professeurs = [];
        if ($bulletin && $bulletin->professeurs) {
            $professeurs = json_decode($bulletin->professeurs, true) ?: [];
        }

        // Transformer les matières en objets compatibles avec la vue
        $resultatsGeneraux = collect($matieresGenerales)->map(function ($item) {
            // Vérifier et journaliser chaque élément
            \Log::debug('Transformation matière générale:', [
                'id' => $item['id'],
                'nom' => $item['nom']
            ]);

            return (object) [
                'matiere_id' => $item['id'],
                'matiere' => (object) [
                    'nom' => $item['nom'],
                    'name' => $item['nom']  // Adding both for compatibility
                ]
            ];
        });

        $resultatsTechniques = collect($matieresProf)->map(function ($item) {
            // Vérifier et journaliser chaque élément
            \Log::debug('Transformation matière technique:', [
                'id' => $item['id'],
                'nom' => $item['nom']
            ]);

            return (object) [
                'matiere_id' => $item['id'],
                'matiere' => (object) [
                    'nom' => $item['nom'],
                    'name' => $item['nom']  // Adding both for compatibility
                ]
            ];
        });

        return view('esbtp.bulletins.edit-professeurs', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'anneeUniversitaire' => $anneeUniversitaire,
            'periode' => $periode,
            'resultatsGeneraux' => $resultatsGeneraux,
            'resultatsTechniques' => $resultatsTechniques,
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'annee_universitaire_id' => $annee_universitaire_id,
            'professeurs' => $professeurs
        ]);
    }

    /**
     * Sauvegarde les professeurs assignés aux matières pour un bulletin
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveProfesseurs(Request $request)
    {
        try {
            // Log au début de la méthode
            Log::info('🔍 Début de saveProfesseurs', [
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->roles
            ]);

            // Valider les données d'entrée
            $validated = $request->validate([
                'professeurs' => 'sometimes|array',
                'etudiant_id' => 'required|exists:esbtp_etudiants,id',
                'classe_id' => 'required|exists:esbtp_classes,id',
                'periode' => 'required|in:semestre1,semestre2,annuel',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            ]);

            $etudiant_id = $request->input('etudiant_id');
            $classe_id = $request->input('classe_id');
            $periode = $request->input('periode');
            $annee_universitaire_id = $request->input('annee_universitaire_id');

            $professeurs = [];
            if ($request->has('professeurs') && is_array($request->input('professeurs'))) {
                $professeurs = $request->input('professeurs');
            }

            // Récupérer le bulletin existant ou en créer un nouveau
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Si le bulletin n'existe pas encore, initialiser les propriétés de base
            if (!$bulletin->exists) {
                $bulletin->created_by = Auth::id();
                $bulletin->save();
            }

            // Mettre à jour le bulletin avec les données des professeurs
            $bulletin->professeurs = json_encode($professeurs);
            $bulletin->updated_by = Auth::id();
            $bulletin->save();

            Log::info('✅ Bulletin mis à jour avec succès', ['bulletin_id' => $bulletin->id, 'professeurs' => $professeurs]);

            // Vérifier quelle action a été choisie via le bouton submit
            $action = $request->input('action', '');

            // Préparer les paramètres communs pour les redirections
            $queryParams = [
                    'bulletin' => $etudiant_id,
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
            ];

            // Redirection en fonction de l'action choisie
            if ($action === 'save_and_back') {
                return redirect()->route('esbtp.resultats.etudiant', [
                    'etudiant' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('success', 'Les noms des professeurs ont été enregistrés avec succès.');
            } elseif ($action === 'generate') {
                // Stocker tous les paramètres nécessaires dans la session
                session(['etudiant_id' => $etudiant_id]);
                session(['classe_id' => $classe_id]);
                session(['periode' => $periode]);
                session(['annee_universitaire_id' => $annee_universitaire_id]);
                session(['params' => $queryParams]);

                // Redirection vers la route de génération du bulletin avec l'ID de l'étudiant
                // comme paramètre principal et le reste dans la session
                return redirect()->route('esbtp.bulletins.generate', [
                    'etudiant_id' => $etudiant_id
                ])->with('success', 'Les noms des professeurs ont été enregistrés. Génération du bulletin en cours...');
            }

            // Redirection par défaut vers la page des résultats de l'étudiant
            return redirect()->route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->with('success', 'Les noms des professeurs ont été enregistrés avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde des professeurs: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * Cette méthode a été remplacée par le service ESBTPAbsenceService.
     * Voir la méthode calculerDetailAbsences dans ce service.
     * @deprecated
     */
    private function calculerAbsencesAttendance($etudiant_id, $classe_id, $date_debut, $date_fin)
    {
        \Log::warning("La méthode obsolète calculerAbsencesAttendance a été appelée. Utiliser le service ESBTPAbsenceService à la place.");
        return $this->absenceService->calculerDetailAbsences($etudiant_id, $classe_id, $date_debut, $date_fin);
    }

    /**
     * Cette méthode a été remplacée par le service ESBTPAbsenceService.
     * Voir la méthode calculerDetailAbsences dans ce service.
     * @deprecated
     */
    private function calculerAbsencesPourBulletin($etudiantId, $classeId, $dateDebut, $dateFin)
    {
        \Log::warning("La méthode obsolète calculerAbsencesPourBulletin a été appelée. Utiliser le service ESBTPAbsenceService à la place.");
        return $this->absenceService->calculerDetailAbsences($etudiantId, $classeId, $dateDebut, $dateFin);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function generateBulletin(Request $request)
    {
        // ... existing code ...

        // Code existant pour créer le bulletin
        $bulletin = ESBTPBulletin::create([
            // Champs existants...
        ]);

        // Déterminer la période pour le calcul des absences
        // Par exemple: utiliser la date de début et de fin du semestre
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($request->annee_universitaire_id);
        if ($anneeUniversitaire) {
            // Exemple: si periode = 'S1' (1er semestre)
            if ($request->periode == 'S1') {
                $dateDebut = $anneeUniversitaire->date_debut;
                $dateFin = Carbon::parse($dateDebut)->addMonths(4)->format('Y-m-d'); // Environ 4 mois pour un semestre
            } else if ($request->periode == 'S2') {
                $dateDebut = Carbon::parse($anneeUniversitaire->date_debut)->addMonths(4)->format('Y-m-d');
                $dateFin = $anneeUniversitaire->date_fin;
            } else {
                // Pour les périodes différentes ou périodes trimestrielles
                // Adapter la logique selon vos besoins
                $dateDebut = $anneeUniversitaire->date_debut;
                $dateFin = $anneeUniversitaire->date_fin;
            }

            \Log::info("Génération de bulletin - Étudiant ID: {$request->etudiant_id}, Classe ID: {$request->classe_id}, Période: du {$dateDebut} au {$dateFin}");

            // Calculer les absences pour la période du bulletin en utilisant le service
            $donneeAbsences = $this->absenceService->calculerDetailAbsences(
                $request->etudiant_id,
                $request->classe_id,
                $dateDebut,
                $dateFin
            );

            \Log::info("Absences calculées:", $donneeAbsences);

            // Intégrer les absences au bulletin
            $bulletin = $this->integrerAbsencesAuBulletin($bulletin, $donneeAbsences);
        }

        // Suite du code existant...

        return redirect()->route('bulletins.show', $bulletin->id)
            ->with('success', 'Bulletin créé avec succès.');
    }

    /**
     * Génère le bulletin pour un étudiant
     */
    public function genererBulletin(Request $request, $etudiantId)
    {
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);

        // Calculer les absences en utilisant le service
        $absences = $this->absenceService->calculerDetailAbsences(
            $etudiantId,
            $etudiant->classe_id
        );

        // ... rest of the bulletin generation code ...

        return view('esbtp.bulletins.show', [
            'etudiant' => $etudiant,
            'absences' => $absences,
            // ... other data ...
        ]);
    }

    /**
     * Intègre les données d'absences dans le bulletin
     *
     * @param ESBTPBulletin $bulletin Le bulletin à mettre à jour
     * @param array $donneeAbsences Les données d'absences calculées
     * @return ESBTPBulletin Le bulletin mis à jour
     */
    private function integrerAbsencesAuBulletin($bulletin, $donneeAbsences)
    {
        \Log::info("Intégration des absences au bulletin ID: " . $bulletin->id, $donneeAbsences);

        // Mettre à jour les champs d'absences du bulletin
        $bulletin->absences_justifiees = $donneeAbsences['justifiees'];
        $bulletin->absences_non_justifiees = $donneeAbsences['non_justifiees'];
        $bulletin->total_absences = $donneeAbsences['total'];

        // Calculer et définir la note d'assiduité
        $bulletin->note_assiduite = $this->calculerNoteAssiduite(
            $donneeAbsences['justifiees'],
            $donneeAbsences['non_justifiees']
        );

        $bulletin->save();

        \Log::info("Absences intégrées avec succès au bulletin ID: " . $bulletin->id);

        return $bulletin;
    }

}
// private function calculerNoteAssiduite($justifiees, $nonJustifiees)
// {
//     $totalAbsences = $justifiees + $nonJustifiees;

//     switch ($totalAbsences) {
//         case 0:
//             return 0.13;
//         case 1:
//             return 0;
//         case 2:
//             return -0.13;
//         case 3:
//         case 4:
//             return -0.39;
//         default: // 5 ou plus
//             return -0.5;
//     }
// }
// private function calculerNoteAssiduite($justifiees, $nonJustifiees)
//             {
//                 $totalAbsences = $justifiees + $nonJustifiees;
            
//                 if ($totalAbsences == 0) {
//                     return 0.13;
//                 } elseif ($totalAbsences == 1) {
//                     return 0;
//                 } elseif ($totalAbsences == 2) {
//                     return -0.13;
//                 } elseif ($totalAbsences == 3 || $totalAbsences == 4) {
//                     return -0.39;
//                 } else { // 5 ou plus
//                     return -0.5;
//                 }
//             }


