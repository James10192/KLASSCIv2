<?php

namespace App\Http\Controllers;

use App\Exceptions\CoefficientMissingException;
use App\Helpers\SettingsHelper;
use App\Models\Classe;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\ESBTP\BulletinConsistencyService;
use App\Services\ESBTP\ESBTPAbsenceService;
use Carbon\Carbon;
use App\Http\Requests\Bulletin\BulkUpdateMoyennesRequest;
use App\Http\Requests\Bulletin\GenerateClasseBulletinsRequest;
use App\Http\Requests\Bulletin\StoreBulletinRequest;
use App\Http\Requests\Bulletin\UpdateBulletinRequest;
use App\Http\Requests\Bulletin\UpdateMoyennesRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PDF;

class ESBTPBulletinController extends Controller
{
    private array $coefficientCache = [];

    private array $classeCache = [];

    protected $absenceService;

    protected $bulletinService;

    protected $bulletinConsistencyService;

    public function __construct(
        ESBTPAbsenceService $absenceService,
        \App\Services\BulletinService $bulletinService,
        BulletinConsistencyService $bulletinConsistencyService
    )
    {
        $this->absenceService = $absenceService;
        $this->bulletinService = $bulletinService;
        $this->bulletinConsistencyService = $bulletinConsistencyService;
    }

    /**
     * Récupère les configurations depuis les settings pour les PDF
     */
    /**
     * Prépare le logo en base64 pour l'intégration dans le PDF
     */
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
            (object) ['id' => 'semestre1', 'nom' => 'Premier Semestre', 'annee_scolaire' => date('Y').'-'.(date('Y') + 1)],
            (object) ['id' => 'semestre2', 'nom' => 'Deuxième Semestre', 'annee_scolaire' => date('Y').'-'.(date('Y') + 1)],
            (object) ['id' => 'annuel', 'nom' => 'Annuel', 'annee_scolaire' => date('Y').'-'.(date('Y') + 1)],
        ];

        // Statistiques pour les widgets (une seule requête)
        $bulletinCounts = ESBTPBulletin::selectRaw('COUNT(*) as total, SUM(is_published = 1) as published, SUM(is_published = 0) as pending')->first();
        $stats = [
            'total'     => (int) ($bulletinCounts->total ?? 0),
            'published' => (int) ($bulletinCounts->published ?? 0),
            'pending'   => (int) ($bulletinCounts->pending ?? 0),
            'periodes'  => count($periodes),
        ];

        // Valeurs par défaut filtre
        $classe_id = $request->input('classe_id');
        $annee_id = $request->input('annee_universitaire_id',
            $anneesUniversitaires->firstWhere('is_active', true)?->id);
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
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBulletinRequest $request)
    {

        DB::beginTransaction();
        try {
            // Vérifier si l'étudiant est bien inscrit dans cette classe pour cette année
            $etudiantInscrit = ESBTPEtudiant::findOrFail($request->etudiant_id)
                ->inscriptions()
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->exists();

            if (! $etudiantInscrit) {
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
            $bulletin = new ESBTPBulletin;
            $bulletin->etudiant_id = $request->etudiant_id;
            $bulletin->classe_id = $request->classe_id;
            $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
            $bulletin->periode = $request->periode;
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->user_id = Auth::id();
            $bulletin->save();

            // PR7 chantier emploi-temps-lmd-unification : GUARD bulletin BTS vs LMD.
            // ESBTPBulletinController est BTS-only. Les classes LMD doivent passer par
            // ESBTPLMDBulletinController + LMDBulletinService (architecture separee).
            // Rule .claude/rules/lmd-bts-bulletin-separation.md
            $classe = ESBTPClasse::with('matieres')->findOrFail($request->classe_id);
            abort_if(
                ($classe->systeme_academique ?? '') === 'LMD',
                422,
                'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour générer des bulletins LMD.'
            );
            $matieres = $classe->matieres;

            // Précharger toutes les évaluations pour cette classe et période
            $allEvaluations = ESBTPEvaluation::where('classe_id', $classe->id)
                ->where('periode', $request->periode)
                ->get()
                ->groupBy('matiere_id');

            // Pour chaque matière, calculer la moyenne et créer un résultat
            foreach ($matieres as $matiere) {
                // Récupérer les évaluations de cette matière depuis le cache
                $evaluations = $allEvaluations->get($matiere->id, collect());

                Log::info('Récupération des évaluations', [
                    'matiere_id' => $matiere->id,
                    'nombre_evaluations' => $evaluations->count(),
                    'classe_id' => $classe->id,
                    'periode' => $request->periode,
                ]);

                if (! $evaluations || $evaluations->isEmpty()) {
                    continue; // Passer à la matière suivante s'il n'y a pas d'évaluations
                }

                // Récupérer les notes de l'étudiant pour ces évaluations
                $notes = ESBTPNote::whereIn('evaluation_id', $evaluations->pluck('id'))
                    ->where('etudiant_id', $request->etudiant_id)
                    ->get();

                if (! $notes || $notes->isEmpty()) {
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
                $coefficient = $this->bulletinService->getCoefficientForCombination(
                    $matiere->id,
                    $classe->id,
                    $request->annee_universitaire_id
                );

                // Créer le résultat pour cette matière
                $resultat = new ESBTPResultatMatiere;
                $resultat->bulletin_id = $bulletin->id;
                $resultat->matiere_id = $matiere->id;
                $resultat->moyenne = $moyenne;
                $resultat->coefficient = $coefficient;
                $resultat->commentaire = null;
                $resultat->save();
            }

            // Calculer et mettre à jour la moyenne générale du bulletin
            $this->bulletinService->calculerMoyenneGenerale($bulletin);

            // Déterminer la période pour le calcul des absences
            // Par exemple: utiliser la date de début et de fin du semestre
            $anneeUniversitaire = ESBTPAnneeUniversitaire::find($request->annee_universitaire_id);
            if ($anneeUniversitaire) {
                // Exemple: si periode = 'S1' (1er semestre)
                if ($request->periode == 'S1') {
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = Carbon::parse($dateDebut)->addMonths(4)->format('Y-m-d'); // Environ 4 mois pour un semestre
                } elseif ($request->periode == 'S2') {
                    $dateDebut = Carbon::parse($anneeUniversitaire->date_debut)->addMonths(4)->format('Y-m-d');
                    $dateFin = $anneeUniversitaire->date_fin;
                } else {
                    // Pour les périodes différentes ou périodes trimestrielles
                    // Adapter la logique selon vos besoins
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = $anneeUniversitaire->date_fin;
                }

                // Calculer les absences pour la période du bulletin
                // (priorité à la saisie manuelle par matière si présente)
                $donneeAbsences = $this->absenceService->calculerDetailAbsences(
                    $request->etudiant_id,
                    $request->classe_id,
                    $dateDebut,
                    $dateFin,
                    $anneeUniversitaire->id,
                    $request->periode
                );

                // Intégrer les absences au bulletin
                $bulletin = $this->bulletinService->integrerAbsencesAuBulletin($bulletin, $donneeAbsences);
            }

            DB::commit();

            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a été créé avec succès');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création du bulletin: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Calcule et met à jour la moyenne générale d'un bulletin
     */
    /**
     * Calcule et met à jour le rang de l'étudiant dans sa classe
     */
    /**
     * Affiche un bulletin spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPBulletin $bulletin)
    {
        $this->authorize('view', $bulletin);

        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere', 'user']);

        return view('esbtp.bulletins.show', compact('bulletin'));
    }

    /**
     * Affiche le formulaire de modification d'un bulletin.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBulletinRequest $request, ESBTPBulletin $bulletin)
    {

        DB::beginTransaction();
        try {
            // Mettre à jour les informations du bulletin
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->save();

            // Mettre à jour les résultats par matière
            $existingResultats = ESBTPResultatMatiere::where('bulletin_id', $bulletin->id)
                ->get()->keyBy('matiere_id');

            foreach ($request->resultats as $resultatData) {
                $matiereId = $resultatData['matiere_id'];
                $moyenne = $resultatData['moyenne'] !== null && $resultatData['moyenne'] !== ''
                    ? $resultatData['moyenne'] : null;

                $resultat = $existingResultats->get($matiereId);

                if ($resultat) {
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                } else {
                    $resultat = new ESBTPResultatMatiere;
                    $resultat->bulletin_id = $bulletin->id;
                    $resultat->matiere_id = $matiereId;
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                }
            }

            // Recalculer la moyenne générale
            $this->bulletinService->calculerMoyenneGenerale($bulletin);

            DB::commit();

            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a été mis à jour avec succès');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du bulletin: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime un bulletin spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPBulletin $bulletin)
    {
        try {
            $bulletin->delete();

            return redirect()->route('esbtp.bulletins.index')->with('success', 'Bulletin supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }

    /**
     * Aperçu PDF du bulletin (Content-Disposition: inline) — ouvre dans une
     * nouvelle tab pour vérifier avant téléchargement.
     */
    public function previewPDF(ESBTPBulletin $bulletin)
    {
        return $this->genererPDF($bulletin, true);
    }

    /**
     * Génère un PDF du bulletin. Si $inline est true, le PDF est streamé
     * inline (preview), sinon téléchargé en attachment (download).
     *
     * @return \Illuminate\Http\Response
     */
    public function genererPDF(ESBTPBulletin $bulletin, bool $inline = false)
    {
        $this->authorize('download', $bulletin);

        try {
            Log::info('Début de la génération du PDF pour le bulletin #'.$bulletin->id);

            // Charger toutes les relations nécessaires avec eager loading, y compris les relations imbriquées
            $bulletin->load([
                'etudiant',
                'classe.niveauEtude',
                'classe.filiere',
                'anneeUniversitaire',
                'resultats.matiere',
                'user',
            ]);

            // Vérifier que les relations essentielles sont chargées
            if (! $bulletin->etudiant) {
                Log::error('Relation etudiant manquante pour le bulletin #'.$bulletin->id);
                throw new \Exception("L'étudiant associé à ce bulletin n'a pas été trouvé. Veuillez vérifier que l'étudiant existe et est correctement associé au bulletin.");
            }

            if (! $bulletin->classe) {
                Log::error('Relation classe manquante pour le bulletin #'.$bulletin->id);
                throw new \Exception("La classe associée à ce bulletin n'a pas été trouvée. Veuillez vérifier que la classe existe et est correctement associée au bulletin.");
            }

            if (! $bulletin->anneeUniversitaire) {
                Log::error('Relation anneeUniversitaire manquante pour le bulletin #'.$bulletin->id);
                throw new \Exception("L'année universitaire associée à ce bulletin n'a pas été trouvée. Veuillez vérifier que l'année universitaire existe et est correctement associée au bulletin.");
            }

            // Calculer la moyenne générale si pas déjà fait
            if (! $bulletin->moyenne_generale) {
                try {
                    $bulletin->calculerMoyenneGenerale();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la moyenne générale: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
                    $bulletin->moyenne_generale = 0;
                }
            }

            // Calculer la mention si pas déjà fait
            if (! $bulletin->mention) {
                try {
                    $bulletin->calculerMention();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la mention: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
                    $bulletin->mention = 'Non calculée';
                }
            }

            // Calculer le rang si pas déjà fait
            if (! $bulletin->rang) {
                try {
                    $bulletin->calculerRang();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul du rang: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
                    $bulletin->rang = 0;
                }
            }

            // Initialiser la note d'assiduité (calculée plus bas si absences disponibles)
            $noteAssiduite = 0;

            // Calculer les absences justifiées et non justifiées
            try {
                $absences = $this->bulletinService->calculerAbsencesDetailees($bulletin);
                $bulletin->absences_justifiees = $absences['justifiees'];
                $bulletin->absences_non_justifiees = $absences['non_justifiees'];
                $bulletin->total_absences = $absences['total'];
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des absences: '.$e->getMessage());
                Log::error('Trace: '.$e->getTraceAsString());
                $bulletin->absences_justifiees = 0;
                $bulletin->absences_non_justifiees = 0;
                $bulletin->total_absences = 0;
            }

            // Si les absences sont toujours à zéro, essayer la méthode basée sur l'attendance
            if ($bulletin->absences_justifiees == 0 && $bulletin->absences_non_justifiees == 0) {
                try {
                    Log::info('Tentative de calcul des absences via le service pour le bulletin #'.$bulletin->id);

                    $absencesAttendance = $this->absenceService->calculerDetailAbsences(
                        $bulletin->etudiant_id,
                        $bulletin->classe_id,
                        $bulletin->anneeUniversitaire->date_debut,
                        $bulletin->anneeUniversitaire->date_fin,
                        $bulletin->annee_universitaire_id,
                        $bulletin->periode
                    );

                    $bulletin->absences_justifiees = $absencesAttendance['justifiees'];
                    $bulletin->absences_non_justifiees = $absencesAttendance['non_justifiees'];
                    $bulletin->total_absences = $absencesAttendance['total'];
                    Log::info('Calcul des absences via le service réussi: '.json_encode($absencesAttendance));
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul des absences via le service: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
                }
            }

            // Récupérer les vraies notes de l'étudiant depuis la table esbtp_notes
            try {
                Log::info('Récupération des vraies notes pour l\'étudiant #'.$bulletin->etudiant_id);

                // Récupérer toutes les notes de l'étudiant avec les matières et évaluations
                $notesEtudiant = ESBTPNote::where('etudiant_id', $bulletin->etudiant_id)
                    ->where('classe_id', $bulletin->classe_id)
                    ->with(['matiere', 'evaluation'])
                    ->get();

                Log::info('Notes trouvées: '.$notesEtudiant->count());

                // Grouper les notes par matière et calculer les moyennes par matière
                $notesByMatiere = $notesEtudiant->groupBy('matiere_id');
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
                $totalGeneral = 0;
                $totalTechnique = 0;
                $countGeneral = 0;
                $countTechnique = 0;

                foreach ($notesByMatiere as $matiereId => $notes) {
                    $matiere = $notes->first()->matiere; // already eager-loaded via ->with(['matiere', 'evaluation'])

                    if ($matiere && $notes->count() > 0) {
                        // Calculer la moyenne pondérée de la matière avec les coefficients des évaluations
                        $totalPondere = 0;
                        $totalCoefficients = 0;
                        $evaluationsDetail = [];

                        foreach ($notes as $note) {
                            // Récupérer le coefficient de l'évaluation (already eager-loaded)
                            $coefficientEval = $note->evaluation?->coefficient ?? 1;

                            $totalPondere += $note->note * $coefficientEval;
                            $totalCoefficients += $coefficientEval;

                            $evaluationsDetail[] = [
                                'note' => $note->note,
                                'coefficient' => $coefficientEval,
                                'pondere' => $note->note * $coefficientEval,
                                'type' => $note->type_evaluation,
                                'evaluation_id' => $note->evaluation_id,
                            ];
                        }

                        // Calculer la moyenne pondérée
                        $moyenneMatiere = $totalCoefficients > 0 ? $totalPondere / $totalCoefficients : 0;
                        $coefficient = $this->bulletinService->getCoefficientForCombination(
                            $matiere->id,
                            $bulletin->classe_id,
                            $bulletin->annee_universitaire_id
                        );

                        Log::info('Matière: '.$matiere->name.' - '.$notes->count().' notes');
                        Log::info('Total pondéré: '.$totalPondere.', Total coefficients: '.$totalCoefficients);
                        Log::info('Moyenne pondérée: '.round($moyenneMatiere, 2));

                        // Créer un objet résultat formaté pour le template
                        $resultatFormate = (object) [
                            'id' => $notes->first()->id,
                            'note' => round($moyenneMatiere, 2),
                            'moyenne' => round($moyenneMatiere, 2),
                            'matiere' => $matiere,
                            'matiere_id' => $matiere->id,
                            'moyenne_matiere' => round($moyenneMatiere, 2),
                            'coefficient' => $coefficient,
                            'rang' => 1, // À calculer si nécessaire
                            'evaluations_detail' => $evaluationsDetail,
                            'total_coefficients' => $totalCoefficients,
                        ];

                        if ($matiere->type_formation === 'generale') {
                            $totalGeneral += $moyenneMatiere * $coefficient;
                            $countGeneral += $coefficient;
                            $resultatsGeneraux->push($resultatFormate);
                        } elseif (in_array($matiere->type_formation, ['technique', 'technologique_professionnelle'])) {
                            $totalTechnique += $moyenneMatiere * $coefficient;
                            $countTechnique += $coefficient;
                            $resultatsTechniques->push($resultatFormate);
                        }
                    }
                }

                // Calculer les moyennes correctement
                $moyenneGenerale = $countGeneral > 0 ? round($totalGeneral / $countGeneral, 2) : 0;
                $moyenneTechnique = $countTechnique > 0 ? round($totalTechnique / $countTechnique, 2) : 0;

                // Calculer la moyenne globale (général + technique)
                $moyenneGlobale = ($countGeneral + $countTechnique) > 0 ?
                    round(($totalGeneral + $totalTechnique) / ($countGeneral + $countTechnique), 2) : 0;

                Log::info('Moyennes calculées - Général: '.$moyenneGenerale.', Technique: '.$moyenneTechnique.', Globale: '.$moyenneGlobale);

                // Mettre à jour le bulletin avec les moyennes calculées
                if (! $bulletin->moyenne_generale || $bulletin->moyenne_generale != $moyenneGlobale) {
                    $bulletin->moyenne_generale = $moyenneGlobale;
                    $bulletin->save();
                    Log::info('Moyenne générale mise à jour: '.$moyenneGlobale);
                }

            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des notes: '.$e->getMessage());
                Log::error('Trace: '.$e->getTraceAsString());
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
                $moyenneGenerale = 0;
                $moyenneTechnique = 0;
            }

            // Générer le PDF avec les configurations de l'école
            $config = $this->bulletinService->getPDFConfig();

            $settings = $config;

            $semesterWeights = $this->bulletinService->getSemesterWeights();
            $periodeCourante = $bulletin->periode;
            $moyenneAvecAssiduite = $moyenneGenerale + ($noteAssiduite ?? 0);
            $moyenneSemestre1 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->annee_universitaire_id,
                'semestre1',
                $periodeCourante,
                $moyenneAvecAssiduite
            );
            $moyenneSemestre2 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->annee_universitaire_id,
                'semestre2',
                $periodeCourante,
                $moyenneAvecAssiduite
            );
            $moyenneAnnuelle = $this->bulletinService->calculateAnnualAverage($moyenneSemestre1, $moyenneSemestre2, $semesterWeights);

            $data = [
                'bulletin' => $bulletin,
                'etudiant' => $bulletin->etudiant, // Ajout explicite pour le template
                'classe' => $bulletin->classe,
                'anneeUniversitaire' => $bulletin->anneeUniversitaire,
                'periode' => $bulletin->periode,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneTechnique' => $moyenneTechnique,
                'moyenneAvecAssiduite' => $moyenneAvecAssiduite,
                'moyenneGlobale' => $moyenneGlobale, // Moyenne globale calculée
                'moyenneSemestre1' => $moyenneSemestre1,
                'moyenneSemestre2' => $moyenneSemestre2,
                'moyenneAnnuelle' => $moyenneAnnuelle,
                'semesterWeights' => $semesterWeights,
                'noteAssiduite' => $noteAssiduite,
                'rang' => $bulletin->rang,
                'effectif' => $bulletin->effectif_classe,
                'appreciation' => $bulletin->mention,
                'date_edition' => now()->format('d/m/Y'),
                'absencesJustifiees' => $bulletin->absences_justifiees,
                'absencesNonJustifiees' => $bulletin->absences_non_justifiees,
                'absences_justifiees' => $bulletin->absences_justifiees,
                'absences_non_justifiees' => $bulletin->absences_non_justifiees,
                'config' => $config,
                'settings' => $settings, // Ajouter tous les paramètres de configuration
            ];

            $data += $this->getOfficialBulletinTemplateDefaults($bulletin);

            // Log des variables d'absences pour debugging
            Log::info('Variables d\'absence pour le PDF dans genererPDF:', [
                'bulletin_absences_justifiees' => $bulletin->absences_justifiees ?? 'Non défini',
                'bulletin_absences_non_justifiees' => $bulletin->absences_non_justifiees ?? 'Non défini',
                'data_absencesJustifiees' => $data['absencesJustifiees'] ?? 'Non défini',
                'data_absencesNonJustifiees' => $data['absencesNonJustifiees'] ?? 'Non défini',
                'data_absences_justifiees' => $data['absences_justifiees'] ?? 'Non défini',
                'data_absences_non_justifiees' => $data['absences_non_justifiees'] ?? 'Non défini',
            ]);

            // Préparer le logo en base64
            $data['logoBase64'] = $this->bulletinService->prepareLogoBase64($config['school_logo']);

            // Préparer la photo étudiant en base64 pour le PDF (conversion JPEG pour DomPDF)
            $data['photoEtudiantBase64'] = null;
            if (!empty($data['etudiant']?->photo)) {
                $photo = $data['etudiant']->photo;
                $photoCandidates = [
                    storage_path('app/public/' . $photo),
                    storage_path('app/public/photos/etudiants/' . basename($photo)),
                    public_path('storage/' . $photo),
                    public_path('storage/photos/etudiants/' . basename($photo)),
                ];
                foreach ($photoCandidates as $photoPath) {
                    if (file_exists($photoPath)) {
                        $data['photoEtudiantBase64'] = $this->bulletinService->convertImageToJpegBase64($photoPath);
                        break;
                    }
                }
            }

            try {
                Log::info('Chargement de la vue PDF avec le template configurable pour le bulletin #'.$bulletin->id);
                $pdf = PDF::loadView($this->bulletinService->getBulletinTemplateView(), $data);

                // Configuration PDF avec format A4 et options optimisées
                $paperFormat = \App\Helpers\SettingsHelper::get('bulletin_paper_format', 'A4');
                $orientation = \App\Helpers\SettingsHelper::get('bulletin_orientation', 'portrait');
                $dpi = \App\Helpers\SettingsHelper::get('bulletin_dpi', '150');

                $pdf->setPaper(strtolower($paperFormat), $orientation);
                $pdf->setOptions([
                    'dpi' => intval($dpi),
                    'defaultFont' => 'sans-serif',
                    'isRemoteEnabled' => false, // Pour éviter les problèmes de sécurité
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => false,
                ]);

                // Nom du fichier PDF
                $filename = 'bulletin_'.
                            ($bulletin->etudiant ? $bulletin->etudiant->matricule : 'unknown').'_'.
                            ($bulletin->classe ? $bulletin->classe->code : 'unknown').'_'.
                            $bulletin->periode.'_'.
                            ($bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire->libelle : 'unknown').'.pdf';

                Log::info('PDF généré avec succès pour le bulletin #'.$bulletin->id);

                // Stream inline (preview) ou télécharger selon le mode demandé
                return $inline ? $pdf->stream($filename) : $pdf->download($filename);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la génération du PDF: '.$e->getMessage());
                Log::error('Trace: '.$e->getTraceAsString());

                // Enregistrer des informations supplémentaires pour le débogage
                Log::error('Données du bulletin: '.json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));

                return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la préparation des données pour le PDF: '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());

            // Enregistrer des informations supplémentaires pour le débogage
            if (isset($bulletin)) {
                Log::error('Données du bulletin: '.json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));
            }

            return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: '.$e->getMessage());
        }
    }

    /**
     * Calcule les absences justifiées et non justifiées pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return array
     */
    // ///////////////////
    /**
     * Génère les bulletins pour une classe entière.
     *
     * @return \Illuminate\Http\Response
     */
    public function genererClasseBulletins(GenerateClasseBulletinsRequest $request)
    {

        try {
            Log::info('Début de la génération des bulletins', $request->all());
            $classe = ESBTPClasse::findOrFail($request->classe_id);

            // PR7 chantier emploi-temps-lmd-unification : GUARD bulletin BTS vs LMD (bulk generation).
            // Rule .claude/rules/lmd-bts-bulletin-separation.md
            abort_if(
                ($classe->systeme_academique ?? '') === 'LMD',
                422,
                'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour générer des bulletins LMD en masse.'
            );

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

                Log::info('Nombre d\'étudiants trouvés: '.$etudiants->count());

                if ($etudiants->isEmpty()) {
                    Log::warning('Aucun étudiant trouvé pour la classe '.$classe->name);

                    return redirect()->route('esbtp.bulletins.index')
                        ->with('warning', 'Aucun étudiant trouvé pour la classe sélectionnée.');
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des étudiants: '.$e->getMessage());
                Log::error('SQL: '.$e->getTraceAsString());
                throw $e;
            }

            $bulletinsGeneres = 0;

            foreach ($etudiants as $etudiant) {
                Log::info('Traitement de l\'étudiant: '.$etudiant->id.' - '.$etudiant->nom.' '.$etudiant->prenoms);
                // Vérifier si un bulletin existe déjà pour cet étudiant
                try {
                    $bulletinExistant = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                        ->where('classe_id', $request->classe_id)
                        ->where('annee_universitaire_id', $request->annee_universitaire_id)
                        ->where('periode', $request->periode)
                        ->exists();

                    if ($bulletinExistant) {
                        Log::info('Bulletin existant pour l\'étudiant: '.$etudiant->id);

                        continue; // Passer à l'étudiant suivant
                    }
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la vérification du bulletin existant: '.$e->getMessage());
                    Log::error('SQL: '.$e->getTraceAsString());
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
                    $bulletin = new ESBTPBulletin;
                    $bulletin->etudiant_id = $etudiant->id;
                    $bulletin->classe_id = $request->classe_id;
                    $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
                    $bulletin->periode = $request->periode;
                    $bulletin->appreciation_generale = null;
                    $bulletin->decision_conseil = null;
                    $bulletin->user_id = Auth::id();
                    $bulletin->save();
                    Log::info('Bulletin créé: '.$bulletin->id);

                    // Récupérer toutes les matières de la classe
                    $matieres = $classe->matieres;
                    Log::info('Nombre de matières trouvées: '.$matieres->count());

                    // Pour chaque matière, calculer la moyenne et créer un résultat
                    foreach ($matieres as $matiere) {
                        Log::info('Traitement de la matière: '.$matiere->id.' - '.($matiere->nom ?? $matiere->name ?? 'Nom inconnu'));

                        // Vérifier si la matière est valide
                        if (! $matiere || ! $matiere->id) {
                            Log::warning('Matière invalide trouvée');

                            continue;
                        }

                        // Récupérer toutes les évaluations de cette matière pour cette classe
                        try {
                            $evaluations = $matiere->evaluations()
                                ->where('classe_id', $classe->id)
                                ->where('periode', $request->periode)
                                ->get();

                            Log::info('Nombre d\'évaluations trouvées: '.$evaluations->count(), [
                                'matiere_id' => $matiere->id,
                                'classe_id' => $classe->id,
                                'periode' => $request->periode,
                            ]);

                            if (! $evaluations || $evaluations->isEmpty()) {
                                Log::info('Pas d\'évaluations pour la matière et la période: '.$matiere->id, [
                                    'periode' => $request->periode,
                                ]);

                                // Créer un résultat vide pour cette matière
                                try {
                                    // Récupérer le coefficient de la matière pour cette classe
                                    $coefficient = $this->bulletinService->getCoefficientForCombination(
                                        $matiere->id,
                                        $classe->id,
                                        $request->annee_universitaire_id
                                    );

                                    $resultat = new ESBTPResultatMatiere;
                                    $resultat->bulletin_id = $bulletin->id;
                                    $resultat->matiere_id = $matiere->id;
                                    $resultat->moyenne = null; // Pas de moyenne car pas d'évaluations
                                    $resultat->coefficient = $coefficient;
                                    $resultat->commentaire = null;
                                    $resultat->save();
                                    Log::info('Résultat vide créé pour la matière: '.$matiere->id);
                                } catch (\Exception $e) {
                                    Log::error('Erreur lors de la création du résultat vide: '.$e->getMessage());
                                }

                                continue; // Passer à la matière suivante s'il n'y a pas d'évaluations
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération des évaluations: '.$e->getMessage());
                            Log::error('SQL: '.$e->getTraceAsString());

                            continue; // Passer à la matière suivante en cas d'erreur
                        }

                        // Récupérer les notes de l'étudiant pour ces évaluations
                        try {
                            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
                                ->where('classe_id', $request->classe_id)
                                ->whereHas('evaluation', function ($query) use ($request) {
                                    $query->where('annee_universitaire_id', $request->annee_universitaire_id);
                                    if ($request->periode != 'annuel') {
                                        $query->where('periode', $request->periode);
                                    }
                                })
                                ->get();

                            Log::info('Nombre de notes trouvées: '.$notes->count());

                            if (! $notes || $notes->isEmpty()) {
                                Log::info('Pas de notes pour l\'étudiant: '.$etudiant->id.' dans la matière: '.$matiere->id);

                                continue; // Passer à la matière suivante s'il n'y a pas de notes
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération des notes: '.$e->getMessage());
                            Log::error('SQL: '.$e->getTraceAsString());
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
                        $coefficient = $this->bulletinService->getCoefficientForCombination(
                            $matiere->id,
                            $classe->id,
                            $request->annee_universitaire_id
                        );

                        // Créer le résultat pour cette matière
                        try {
                            $resultat = new ESBTPResultatMatiere;
                            $resultat->bulletin_id = $bulletin->id;
                            $resultat->matiere_id = $matiere->id;
                            $resultat->moyenne = $moyenne;
                            $resultat->coefficient = $coefficient;
                            $resultat->commentaire = null;
                            $resultat->save();
                            Log::info('Résultat créé pour la matière: '.$matiere->id.' avec moyenne: '.$moyenne);
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la création du résultat: '.$e->getMessage());
                            Log::error('SQL: '.$e->getTraceAsString());
                            throw $e;
                        }
                    }

                    // Calculer et mettre à jour la moyenne générale du bulletin
                    try {
                        Log::info('Calcul de la moyenne générale pour le bulletin: '.$bulletin->id);
                        $this->bulletinService->calculerMoyenneGenerale($bulletin);
                    } catch (\Exception $e) {
                        Log::error('Erreur lors du calcul de la moyenne générale: '.$e->getMessage());
                        Log::error('SQL: '.$e->getTraceAsString());
                        throw $e;
                    }

                    DB::commit();
                    $bulletinsGeneres++;
                    Log::info('Bulletin généré avec succès pour l\'étudiant: '.$etudiant->id);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur lors de la génération du bulletin pour l\'étudiant: '.$etudiant->id.' - '.$e->getMessage());
                    Log::error('SQL: '.$e->getTraceAsString());
                    // Continuer avec l'étudiant suivant
                }
            }

            if ($bulletinsGeneres > 0) {
                Log::info('Bulletins générés avec succès: '.$bulletinsGeneres);

                return redirect()->route('esbtp.bulletins.index')
                    ->with('success', $bulletinsGeneres.' bulletins ont été générés avec succès');
            } else {
                Log::info('Aucun bulletin généré');

                return redirect()->route('esbtp.bulletins.index')
                    ->with('info', 'Aucun nouveau bulletin n\'a été généré. Tous les bulletins existent déjà ou il n\'y a pas de données suffisantes.');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des bulletins: '.$e->getMessage());
            Log::error('SQL: '.$e->getTraceAsString());

            return redirect()->route('esbtp.bulletins.index')
                ->with('error', 'Une erreur est survenue lors de la génération des bulletins: '.$e->getMessage());
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
     * Signe un bulletin par un responsable
     *
     * @param  string  $role
     * @return \Illuminate\Http\Response
     */
    public function signer(ESBTPBulletin $bulletin, $role)
    {
        if (! in_array($role, ['directeur', 'responsable', 'parent'])) {
            return back()->with('error', 'Rôle de signature invalide.');
        }

        try {
            $bulletin->signer($role);

            return back()->with('success', 'Bulletin signé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la signature: '.$e->getMessage());
        }
    }

    /**
     * Bascule l'état de publication d'un bulletin
     *
     * @return \Illuminate\Http\Response
     */
    public function togglePublication(ESBTPBulletin $bulletin)
    {
        try {
            $wasPublished = $bulletin->is_published;
            $bulletin->is_published = ! $bulletin->is_published;
            $bulletin->save();

            // Si le bulletin vient d'être publié, notifier les parents
            if (! $wasPublished && $bulletin->is_published) {
                try {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->notifyParentsBulletinPublished($bulletin);

                    // Vérifier si l'étudiant a des notes faibles et envoyer une alerte si nécessaire
                    $notificationService->notifyParentsLowGrades($bulletin);
                } catch (\Exception $e) {
                    \Log::error('Erreur envoi notification bulletin aux parents: '.$e->getMessage());
                }
            }

            $message = $bulletin->is_published
                ? 'Le bulletin a été publié avec succès.'
                : 'Le bulletin a été dépublié avec succès.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du changement de statut: '.$e->getMessage());
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
            ->orWhere(function ($query) {
                $query->where('signature_responsable', false)
                    ->orWhere('signature_directeur', false);
            })
            ->with(['etudiant', 'classe', 'anneeUniversitaire'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Statistiques
        $totalPending = ESBTPBulletin::where('is_published', false)->count();
        $totalNonSigned = ESBTPBulletin::where('is_published', true)
            ->where(function ($query) {
                $query->where('signature_responsable', false)
                    ->orWhere('signature_directeur', false);
            })->count();

        return view('esbtp.bulletins.pending', compact('bulletins', 'totalPending', 'totalNonSigned'));
    }












    /**
     * Génère un PDF à partir des paramètres fournis (étudiant, classe, période, année universitaire)
     * sans nécessiter un bulletin existant.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Prévisualise le bulletin avant génération PDF
     */
    public function previewBulletin(Request $request)
    {
        try {
            // Validation des paramètres
            $validator = Validator::make($request->all(), [
                'etudiant' => 'required|exists:esbtp_etudiants,id',
                'classe' => 'required|exists:esbtp_classes,id',
                'annee' => 'required|exists:esbtp_annee_universitaires,id',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Récupérer les données
            $etudiant = ESBTPEtudiant::findOrFail($request->etudiant);
            $classe = ESBTPClasse::with(['filiere', 'niveauEtude'])->findOrFail($request->classe);

            // PR7 chantier emploi-temps-lmd-unification : GUARD bulletin BTS vs LMD (preview).
            // Rule .claude/rules/lmd-bts-bulletin-separation.md
            abort_if(
                ($classe->systeme_academique ?? '') === 'LMD',
                422,
                'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour les bulletins LMD.'
            );

            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($request->annee);

            // Essayer de récupérer un bulletin existant pour avoir les configurations
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->first();

            // Récupérer les matières de la classe via la relation pivot
            $matieres = $classe->matieres()
                ->where('esbtp_matieres.is_active', true)
                ->orderBy('esbtp_matieres.name')
                ->get();

            // Grouper les matières par filière
            $matieresByFiliere = $matieres->groupBy(function ($matiere) use ($classe) {
                return $classe->filiere->name ?? 'Non défini';
            });

            // Récupérer les évaluations pour ces matières
            $evaluations = ESBTPEvaluation::whereIn('matiere_id', $matieres->pluck('id'))
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->where('status', '!=', 'cancelled')
                ->orderBy('titre')
                ->get();

            // Grouper les évaluations par matière
            $evaluationsParMatiere = $evaluations->groupBy('matiere_id');

            // Récupérer les notes de l'étudiant
            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
                ->whereIn('evaluation_id', $evaluations->pluck('id'))
                ->get()
                ->keyBy('evaluation_id');

            // Convertir en tableau simple pour la vue
            $notesParEvaluation = [];
            foreach ($notes as $evaluationId => $note) {
                $notesParEvaluation[$evaluationId] = $note->note;
            }

            // Calculer les moyennes par matière avec pondération (automatiques)
            $moyennesAutomatiques = [];
            foreach ($matieres as $matiere) {
                if (isset($evaluationsParMatiere[$matiere->id])) {
                    $evaluationsMatiere = $evaluationsParMatiere[$matiere->id];
                    $totalPoints = 0;
                    $totalCoeffs = 0;

                    foreach ($evaluationsMatiere as $evaluation) {
                        if (isset($notesParEvaluation[$evaluation->id])) {
                            $totalPoints += $notesParEvaluation[$evaluation->id] * $evaluation->coefficient;
                            $totalCoeffs += $evaluation->coefficient;
                        }
                    }

                    $moyennesAutomatiques[$matiere->id] = $totalCoeffs > 0 ? $totalPoints / $totalCoeffs : 0;
                } else {
                    $moyennesAutomatiques[$matiere->id] = 0;
                }
            }

            // NOUVELLE LOGIQUE: Intégrer les moyennes manuelles (priorité Manuel l'emporte)
            $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->with('matiere')
                ->get();

            // Commencer avec les moyennes automatiques
            $moyennesMatiere = $moyennesAutomatiques;

            // Écraser avec les moyennes manuelles (elles l'emportent toujours)
            foreach ($resultats as $resultat) {
                if ($resultat->matiere) {
                    $moyennesMatiere[$resultat->matiere_id] = $resultat->moyenne;
                }
            }

            // Récupérer les configurations si le bulletin existe
            $configMatieres = [];
            $professeurs = [];
            if ($bulletin) {
                $configMatieres = json_decode($bulletin->config_matieres, true) ?: [];
                $professeurs = json_decode($bulletin->professeurs, true) ?: [];
            }

            // Préparer le logo et configuration PDF
            $logoBase64 = null;
            $config = $this->bulletinService->getPDFConfig();
            $schoolInfo = [
                'name' => $config['school_name'],
                'address' => $config['school_address'],
                'phone' => $config['school_phone'],
                'email' => $config['school_email'],
                'city' => $config['school_city'],
                'country' => $config['school_country'],
                'logo' => null,
            ];
            if (! empty($config['school_logo'])) {
                $logoPath = $config['school_logo'];
                $fullPath = public_path($logoPath);

                if (file_exists($fullPath)) {
                    $logoType = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $logoData = file_get_contents($fullPath);
                    $logoBase64 = base64_encode($logoData);
                    $schoolInfo['logo'] = $config['school_logo'];
                }
            }

            // Statistiques de la classe
            $totalEtudiants = ESBTPEtudiant::where('classe_id', $classe->id)->count();

            return view($this->bulletinService->getBulletinPreviewView(), compact(
                'etudiant',
                'classe',
                'anneeUniversitaire',
                'matieres',
                'matieresByFiliere',
                'evaluationsParMatiere',
                'notesParEvaluation',
                'moyennesMatiere',
                'configMatieres',
                'professeurs',
                'bulletin',
                'config',
                'schoolInfo',
                'logoBase64',
                'totalEtudiants'
            ));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la prévisualisation du bulletin: '.$e->getMessage());

            return redirect()->back()->with('error', 'Erreur lors de la prévisualisation du bulletin.');
        }
    }



    /**
     * Aperçu PDF inline du bulletin via params (Content-Disposition: inline).
     */
    public function previewPDFParParamsUnified(Request $request)
    {
        return $this->genererPDFParParamsUnified($request, true);
    }

    /**
     * Génère le PDF du bulletin via params. Si $inline est true, retourne en
     * inline (preview), sinon en attachment (download).
     */
    public function genererPDFParParamsUnified(Request $request, bool $inline = false)
    {
        try {
            // Vérifier que l'utilisateur est autorisé
            if (! Auth::check() || ! Auth::user()->can('bulletins.configure')) {
                abort(403, 'Accès non autorisé. Vous n\'avez pas la permission de configurer les bulletins.');
            }

            // Récupérer les paramètres
            $classe_id = $request->classe_id;
            $etudiant_id = $request->etudiant_id ?? $request->bulletin;
            $periode = $request->periode ?? 'semestre1';
            $annee_universitaire_id = $request->annee_universitaire_id;
            $forceOfficial = $request->boolean('use_official');

            $consistency = null;
            if ($classe_id && $etudiant_id && $annee_universitaire_id) {
                $consistency = $this->bulletinConsistencyService->getSnapshot(
                    (int) $etudiant_id,
                    (int) $classe_id,
                    (int) $annee_universitaire_id,
                    (string) $periode
                );
            }

            if (($forceOfficial || ($consistency['official_bulletin_exists'] ?? false) && ! ($consistency['has_divergence'] ?? false))
                && ! empty($consistency['official_bulletin_id'])) {
                $officialBulletin = ESBTPBulletin::findOrFail($consistency['official_bulletin_id']);

                return $this->genererPDF($officialBulletin, $inline);
            }

            // Utiliser le BulletinService unifié pour générer les données
            $donnees = $this->bulletinService->genererDonneesBulletin(
                $etudiant_id,
                $classe_id,
                $annee_universitaire_id,
                $periode
            );

            // Ajouter le logo pour le PDF
            $config = $this->bulletinService->getPDFConfig();
            $logoBase64 = $this->bulletinService->prepareLogoBase64($config['school_logo']);
            $donnees['logoBase64'] = $logoBase64;

            // Préparer la photo étudiant en base64 pour le PDF (conversion JPEG pour DomPDF)
            $donnees['photoEtudiantBase64'] = null;
            if (!empty($donnees['etudiant']?->photo)) {
                $photo = $donnees['etudiant']->photo;
                $photoCandidates = [
                    storage_path('app/public/' . $photo),
                    storage_path('app/public/photos/etudiants/' . basename($photo)),
                    public_path('storage/' . $photo),
                    public_path('storage/photos/etudiants/' . basename($photo)),
                ];
                foreach ($photoCandidates as $photoPath) {
                    if (file_exists($photoPath)) {
                        $donnees['photoEtudiantBase64'] = $this->bulletinService->convertImageToJpegBase64($photoPath);
                        break;
                    }
                }
            }

            // Indiquer que c'est un export PDF pour cacher les éléments web
            $donnees['isPdfExport'] = true;

            // Générer le PDF avec le template unifié
            $pdf = PDF::loadView($this->bulletinService->getBulletinTemplateView(), $donnees);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isPhpEnabled' => true,
                'chroot' => public_path(),
            ]);

            // Nom du fichier PDF
            $filename = 'bulletin_'.
                        ($donnees['etudiant']->matricule ?? 'unknown').'_'.
                        ($donnees['classe']->code ?? 'unknown').'_'.
                        $periode.'_'.
                        ($donnees['anneeUniversitaire']->libelle ?? 'unknown').'.pdf';

            return $inline ? $pdf->stream($filename) : $pdf->download($filename);

        } catch (CoefficientMissingException $e) {
            $context = $this->buildCoefficientIssueContext($e->getContext(), $request);
            $message = $this->formatCoefficientIssueMessage($context);

            return redirect()->back()
                ->with('error', $message)
                ->with('coefficient_missing_context', $context);
        } catch (\Exception $e) {
            // Gestion des erreurs de configuration
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $request->classe_id,
                    'periode' => $request->periode ?? 'semestre1',
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'bulletin' => $request->etudiant_id ?? $request->bulletin,
                ]);

                return redirect($configMatieresUrl)->with('error', $e->getMessage());
            }

            return back()->with('error', 'Erreur lors de la génération du PDF : '.$e->getMessage());
        }
    }









    /**
     * Vérifie les pré-requis avant génération du bulletin (AJAX).
     * Retourne les warnings éventuels (ex: bulletin de l'autre semestre non généré).
     */
    public function checkBulletinPrerequisites(Request $request)
    {
        $classeId = $request->classe_id;
        $etudiantId = $request->etudiant_id ?? $request->bulletin;
        $periode = $request->periode ?? 'semestre1';
        $anneeId = $request->annee_universitaire_id;

        $warnings = [];

        $otherPeriode = $periode === 'semestre1' ? 'semestre2' : 'semestre1';
        $otherLabel = $otherPeriode === 'semestre1' ? 'Semestre 1' : 'Semestre 2';

        $otherBulletinExists = \App\Models\ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('periode', $otherPeriode)
            ->where('moyenne_generale', '>', 0)
            ->exists();

        // Avertir seulement quand on génère S2 sans bulletin S1 officiel
        // (En S1, c'est normal que S2 n'existe pas encore)
        if (! $otherBulletinExists && $periode === 'semestre2') {
            $hasNotes = \App\Models\ESBTPNote::where('etudiant_id', $etudiantId)
                ->whereHas('evaluation', function ($q) use ($otherPeriode, $anneeId) {
                    $q->where('annee_universitaire_id', $anneeId)
                        ->where('periode', $otherPeriode);
                })
                ->exists();

            if ($hasNotes) {
                $warnings[] = [
                    'type' => 'warning',
                    'title' => "Bulletin {$otherLabel} non généré",
                    'message' => "Le bulletin du {$otherLabel} n'a pas encore été généré officiellement. La moyenne sera calculée à la volée à partir des notes existantes. Pour un résultat officiel, générez d'abord le bulletin du {$otherLabel}.",
                ];
            } else {
                $warnings[] = [
                    'type' => 'info',
                    'title' => "Pas de notes pour le {$otherLabel}",
                    'message' => "Aucune note n'a été saisie pour le {$otherLabel}. La moyenne annuelle ne pourra pas être calculée.",
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'warnings' => $warnings,
        ]);
    }

    public function checkBulletinConsistency(Request $request)
    {
        $classeId = (int) $request->input('classe_id');
        $etudiantId = (int) ($request->input('etudiant_id') ?? $request->input('bulletin'));
        $periode = (string) ($request->input('periode') ?? 'semestre1');
        $anneeId = (int) $request->input('annee_universitaire_id');
        $action = (string) $request->input('action', 'download_pdf');

        $consistency = $this->bulletinConsistencyService->getSnapshot($etudiantId, $classeId, $anneeId, $periode);
        $warnings = [];

        if ($consistency['official_bulletin_exists'] && $consistency['has_divergence']) {
            $warnings[] = [
                'type' => 'warning',
                'title' => 'Bulletin officiel obsolète',
                'message' => 'Les notes actuelles ne correspondent plus au bulletin officiel enregistré.',
            ];
        }

        $resolvedUrl = $this->resolveConsistencyActionUrl($action, $consistency, [
            'bulletin' => $etudiantId,
            'classe_id' => $classeId,
            'periode' => $periode,
            'annee_universitaire_id' => $anneeId,
        ]);

        return response()->json([
            'ok' => true,
            'warnings' => $warnings,
            'consistency' => $consistency,
            'message' => $consistency['user_message'],
            'resolved_url' => $resolvedUrl['resolved_url'],
            'official_url' => $resolvedUrl['official_url'],
            'current_url' => $resolvedUrl['current_url'],
            'regenerate_url' => route('esbtp.bulletins.regenerate'),
            'can_regenerate' => Auth::user()?->can('bulletins.edit') ?? false,
        ]);
    }

    public function regenerateOfficialBulletin(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->can('bulletins.edit'), 403);

        $validated = $request->validate([
            'classe_id' => 'required|integer|exists:esbtp_classes,id',
            'etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|string|in:1,2,semestre1,semestre2',
        ]);

        try {
            $consistency = $this->bulletinConsistencyService->regenerateOfficialBulletin(
                (int) $validated['etudiant_id'],
                (int) $validated['classe_id'],
                (int) $validated['annee_universitaire_id'],
                (string) $validated['periode']
            );

            return response()->json([
                'ok' => true,
                'message' => 'Le bulletin officiel a été régénéré avec les données courantes.',
                'consistency' => $consistency,
            ]);
        } catch (CoefficientMissingException $e) {
            $context = $this->buildCoefficientIssueContext($e->getContext(), $request);

            return response()->json([
                'ok' => false,
                'message' => $this->formatCoefficientIssueMessage($context),
                'redirect_url' => $context['config_url'] ?? null,
                'context' => $context,
            ], 422);
        } catch (\Exception $e) {
            $redirectUrl = null;
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                $redirectUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $validated['classe_id'],
                    'periode' => $validated['periode'],
                    'annee_universitaire_id' => $validated['annee_universitaire_id'],
                    'bulletin' => $validated['etudiant_id'],
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'redirect_url' => $redirectUrl,
            ], 422);
        }
    }

    /**
     * @deprecated Route stub — use genererBulletin() instead.
     * @return \Illuminate\Http\Response
     */
    public function generateBulletin(Request $request)
    {
        // This method was a stub that created empty bulletin records.
        // The real implementation is in genererBulletin().
        abort(501, 'Not implemented — use the bulletin generation form.');
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
     * @param  ESBTPBulletin  $bulletin  Le bulletin à mettre à jour
     * @param  array  $donneeAbsences  Les données d'absences calculées
     * @return ESBTPBulletin Le bulletin mis à jour
     */
    /**
     * Calcule les statistiques réelles de la classe
     */
    /**
     * Calculer la moyenne globale d'un étudiant (utilisé pour les statistiques)
     */
    /**
     * Affiche la page de configuration des bulletins
     */
    public function configuration()
    {
        $settings = $this->bulletinService->getPDFConfig();

        return view('esbtp.bulletins.configuration', compact('settings'));
    }

    /**
     * Sauvegarde la configuration des bulletins
     */
    public function saveConfiguration(Request $request)
    {
        try {
            \Log::info('Début de sauvegarde configuration', ['data' => $request->all()]);

            // Liste des paramètres checkbox (qui doivent être gérés différemment)
            $checkboxFields = [
                'bulletin_show_logo',
                'bulletin_show_header',
                'bulletin_show_republic_info',
                'bulletin_show_ministry_info',
                'bulletin_show_school_info',
                'bulletin_show_edition_date',
                'bulletin_show_cycle_info',
                'bulletin_show_student_info',
                'bulletin_show_matricule',
                'bulletin_show_birth_date',
                'bulletin_show_redoublant',
                'bulletin_show_class_info',
                'bulletin_show_effectif',
                'bulletin_show_subjects_table',
                'bulletin_show_subject_average',
                'bulletin_show_coefficient',
                'bulletin_show_teachers',
                'bulletin_show_appreciations',
                'bulletin_show_general_average',
                'bulletin_show_technical_average',
                'bulletin_show_global_average',
                'bulletin_show_class_rank',
                'bulletin_show_class_size',
                'bulletin_show_attendance',
                'bulletin_show_attendance_note',
                'bulletin_show_highest_average',
                'bulletin_show_lowest_average',
                'bulletin_show_class_average',
                'bulletin_show_council_decision',
                'bulletin_show_signatures',
                'bulletin_show_director_signature',
            ];

            // Checkboxes LMD
            $lmdCheckboxFields = [
                'lmd_bulletin_show_republic_info',
                'lmd_bulletin_show_ministry_info',
                'lmd_bulletin_show_etablissement_box',
                'lmd_bulletin_show_domaine',
                'lmd_bulletin_show_mention',
                'lmd_bulletin_show_specialite',
                'lmd_bulletin_show_parcours',
            ];

            $checkboxFields = array_merge($checkboxFields, $lmdCheckboxFields);

            // Liste de tous les paramètres de bulletin (BTS + LMD)
            $allBulletinFields = array_merge($checkboxFields, [
                'bulletin_font_size',
                'bulletin_school_name_custom',
                'bulletin_republic_text',
                'bulletin_union_text',
                'bulletin_ministry_text',
                'bulletin_cycle_text',
                'bulletin_cycle_abbreviation',
                'bulletin_table_border_style',
                // LMD text fields
                'lmd_bulletin_republic_text',
                'lmd_bulletin_union_text',
                'lmd_bulletin_ministry_text',
                'lmd_bulletin_code_etablissement',
                'lmd_bulletin_statut',
                'lmd_bulletin_direction',
                'lmd_bulletin_label_domaine',
                'lmd_bulletin_label_mention',
                'lmd_bulletin_label_specialite',
                'lmd_bulletin_label_parcours',
                'lmd_bulletin_notice_text',
                'lmd_bulletin_bottom_text',
            ]);

            // Récupérer tous les paramètres de bulletin avec gestion des checkboxes
            $bulletinSettings = $request->only($allBulletinFields);

            // Gérer les checkboxes décochées (les définir à '0' si non présentes)
            foreach ($checkboxFields as $field) {
                $bulletinSettings[$field] = $request->has($field) ? '1' : '0';
            }

            \Log::info('Paramètres bulletin après traitement checkboxes', ['settings' => $bulletinSettings]);

            // Récupérer les paramètres d'établissement
            $establishmentSettings = $request->only([
                'school_name',
                'school_address',
                'school_phone',
                'school_email',
                'school_website',
                'school_country',
                'director_name',
                'director_title',
            ]);

            // Sauvegarder les paramètres de bulletin
            foreach ($bulletinSettings as $key => $value) {
                SettingsHelper::setOrCreate($key, $value ?? '', 'bulletin');
            }

            // Sauvegarder les paramètres d'établissement avec préfixe
            foreach ($establishmentSettings as $key => $value) {
                SettingsHelper::setOrCreate("establishment.{$key}", $value ?? '', 'establishment');
                SettingsHelper::setOrCreate($key, $value ?? '', 'establishment');
            }

            return redirect()->back()->with('success', 'Configuration sauvegardée avec succès.');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la sauvegarde de la configuration: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()->back()->with('error', 'Erreur lors de la sauvegarde de la configuration: '.$e->getMessage());
        }
    }



    /**
     * Calculer les moyennes automatiques depuis les évaluations pour un étudiant
     * Logique identique à previewMoyennes() mais pour un seul étudiant
     *
     * @param  int  $etudiantId
     * @param  int  $classeId
     * @param  string|null  $periode
     * @param  int  $anneeUniversitaireId
     * @param  \Illuminate\Support\Collection  $matieres
     * @return array
     */
    private function buildCoefficientIssueContext(array $context, Request $request): array
    {
        $classeId = $context['classe']['id'] ?? $request->input('classe_id');
        $matiereId = $context['matiere']['id'] ?? null;

        $context['config_url'] = $context['config_url']
            ?? route('esbtp.evaluations.index', ['open_coefficients' => 1]);

        if ($classeId) {
            $context['classe_matieres_url'] = $context['classe_matieres_url']
                ?? (\Illuminate\Support\Facades\Route::has('classes.matieres')
                    ? route('classes.matieres', ['classe' => $classeId])
                    : route('esbtp.evaluations.index', ['open_coefficients' => 1]));
        }

        $query = array_filter([
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
        ], fn ($value) => ! is_null($value) && $value !== '');

        if (! empty($query)) {
            $context['evaluations_url'] = $context['evaluations_url']
                ?? route('esbtp.evaluations.index', $query);
        }

        return $context;
    }

    private function formatCoefficientIssueMessage(array $context): string
    {
        $reason = $context['reason'] ?? 'coefficient_missing';
        $matiereName = $context['matiere']['name'] ?? 'la matière sélectionnée';
        $classeName = $context['classe']['name'] ?? 'la classe';
        $filiereName = $context['classe']['filiere_name'] ?? 'la filière';
        $niveauName = $context['classe']['niveau_name'] ?? 'le niveau';

        if ($reason === 'matiere_hors_combinaison') {
            return "La matière {$matiereName} n'est pas rattachée à la combinaison {$filiereName} / {$niveauName} de {$classeName}.";
        }

        if ($reason === 'matiere_introuvable') {
            return 'Matière introuvable. Veuillez vérifier la configuration des évaluations.';
        }

        return "Coefficient manquant pour {$matiereName}. Configurez les coefficients avant de continuer.";
    }

    private function resolveConsistencyActionUrl(string $action, array $consistency, array $params): array
    {
        $currentUrl = match ($action) {
            'preview_pdf' => route('esbtp.bulletins.pdf-params-preview', $params),
            'web_preview' => route('esbtp.resultats.etudiant.preview', ['etudiant' => $params['bulletin']]) . '?' . http_build_query([
                'classe_id' => $params['classe_id'],
                'annee_universitaire_id' => $params['annee_universitaire_id'],
                'periode' => $params['periode'],
            ]),
            default => route('esbtp.bulletins.pdf-params', $params),
        };

        $officialUrl = null;
        if (! empty($consistency['official_bulletin_id'])) {
            $officialUrl = match ($action) {
                'preview_pdf' => route('esbtp.bulletins.preview-pdf', $consistency['official_bulletin_id']),
                default => route('esbtp.bulletins.download', $consistency['official_bulletin_id']),
            };
        }

        $resolvedUrl = $currentUrl;
        if (($consistency['official_bulletin_exists'] ?? false)
            && ! ($consistency['has_divergence'] ?? false)
            && $officialUrl) {
            $resolvedUrl = $officialUrl;
        }

        return [
            'resolved_url' => $resolvedUrl,
            'official_url' => $officialUrl,
            'current_url' => $currentUrl,
        ];
    }

    private function getOfficialBulletinTemplateDefaults(ESBTPBulletin $bulletin): array
    {
        try {
            return $this->bulletinService->genererDonneesBulletin(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->annee_universitaire_id,
                $bulletin->periode
            );
        } catch (\Throwable $e) {
            Log::warning('Fallback defaults unavailable for official bulletin template', [
                'bulletin_id' => $bulletin->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Convertit une image en JPEG base64 compatible DomPDF.
     * DomPDF ne supporte pas les PNG indexés (palette 8-bit) ni la transparence PNG.
     * Cette méthode utilise GD pour convertir l'image en truecolor JPEG.
     */
}
