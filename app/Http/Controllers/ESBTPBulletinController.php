<?php

namespace App\Http\Controllers;

use App\Support\ListeInfinie;
use App\Domain\Bulletins\FiltresBulletins;
use App\Http\Controllers\Concerns\ExporteBulletinsParTranches;
use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Domain\AcademicPilotage\Services\BulletinGenerationReadinessService;
use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
use App\Exceptions\BulletinConfigurationException;
use App\Exceptions\CoefficientMissingException;
use App\Helpers\SettingsHelper;
use App\Http\Requests\Bulletin\GenerateClasseBulletinsRequest;
use App\Models\ESBTPInscription;
use App\Http\Requests\Bulletin\StoreBulletinRequest;
use App\Http\Requests\Bulletin\UpdateBulletinRequest;
use App\Models\Classe;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\ESBTPResultatMatiere;
use App\Services\BulletinBulkPdfExporter;
use App\Services\BulletinService;
use App\Services\DocumentPrintGuard;
use App\Services\BtsBulletinPolicy;
use App\Services\ESBTP\BulletinConsistencyService;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PDF;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ESBTPBulletinController extends Controller
{
    use ExporteBulletinsParTranches;

    private array $coefficientCache = [];

    private array $classeCache = [];

    protected $absenceService;

    protected $bulletinService;

    protected $bulletinConsistencyService;

    protected BtsBulletinSubjectResolver $subjectResolver;

    protected BulletinSubjectOrder $subjectOrder;

    protected BulletinGenerationReadinessService $bulletinReadiness;

    protected BtsBulkBulletinGenerationService $bulkBulletinGeneration;

    public function __construct(
        ESBTPAbsenceService $absenceService,
        BulletinService $bulletinService,
        BulletinConsistencyService $bulletinConsistencyService,
        BtsBulletinSubjectResolver $subjectResolver,
        BulletinGenerationReadinessService $bulletinReadiness,
        BtsBulkBulletinGenerationService $bulkBulletinGeneration,
        BulletinSubjectOrder $subjectOrder
    ) {
        $this->absenceService = $absenceService;
        $this->bulletinService = $bulletinService;
        $this->bulletinConsistencyService = $bulletinConsistencyService;
        $this->subjectResolver = $subjectResolver;
        $this->bulletinReadiness = $bulletinReadiness;
        $this->bulkBulletinGeneration = $bulkBulletinGeneration;
        $this->subjectOrder = $subjectOrder;
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
     * @return Response
     */
    public function index(Request $request)
    {
        // Les choix offerts et la sélection normalisée viennent du même objet :
        // ce que la vue affiche est exactement ce qui filtre.
        $filtres = FiltresBulletins::depuis($request);
        $classes = $filtres->classes;
        $anneesUniversitaires = $filtres->annees;
        $periodes = $filtres->periodes();

        $classe_id = $filtres->classeId;
        $annee_id = $filtres->anneeId;
        $periode_id = $filtres->periode;
        $published = $filtres->publie;
        $search = $filtres->recherche;

        $query = ESBTPBulletin::with(['etudiant:id,matricule,nom,prenoms', 'classe:id,name', 'anneeUniversitaire:id,name']);
        $filtres->appliquerA($query);

        // L'identifiant departage une generation en masse, ecrite dans la meme
        // seconde : la liste se charge par tranches au defilement.
        $bulletins = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(20)->appends($request->query());

        if (ListeInfinie::demandee($request)) {
            return ListeInfinie::reponse(
                $bulletins,
                fn ($bulletin) => view('esbtp.bulletins.partials._ligne', compact('bulletin'))->render(),
            );
        }

        // Statistiques globales scoppées sur l'année universitaire active du filtre.
        $statsScope = ESBTPBulletin::query();
        if ($annee_id) {
            $statsScope->where('annee_universitaire_id', $annee_id);
        }
        $bulletinCounts = (clone $statsScope)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published, SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN periode = ? THEN 1 ELSE 0 END) as legacy_annuel', ['annuel'])
            ->first();
        $coveredStudents = (clone $statsScope)->distinct('etudiant_id')->count('etudiant_id');

        $stats = [
            'total' => (int) ($bulletinCounts->total ?? 0),
            'published' => (int) ($bulletinCounts->published ?? 0),
            'pending' => (int) ($bulletinCounts->pending ?? 0),
            'covered' => $coveredStudents,
            'legacy_annuel' => (int) ($bulletinCounts->legacy_annuel ?? 0),
        ];
        $stats['publish_pct'] = $stats['total'] > 0
            ? (int) round($stats['published'] / $stats['total'] * 100)
            : 0;

        // AJAX no-reload : si requête AJAX, renvoyer le partial table + stats en JSON.
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('esbtp.bulletins.partials._table', compact(
                    'bulletins', 'classe_id', 'periode_id', 'annee_id', 'published', 'search'
                ))->render(),
                'stats' => $stats,
                'count' => $bulletins->count(),
                'ids' => $bulletins->pluck('id')->all(),
            ]);
        }

        return view('esbtp.bulletins.index', compact(
            'bulletins',
            'classes',
            'anneesUniversitaires',
            'classe_id',
            'annee_id',
            'periodes',
            'periode_id',
            'published',
            'search',
            'stats'
        ));
    }

    /**
     * Affiche le formulaire de sélection d'étudiant pour créer un bulletin
     *
     * @return Response
     */
    public function create()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeActuelle = ESBTPAnneeUniversitaire::anneeCourante();

        return view('esbtp.bulletins.create', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Enregistre un nouveau bulletin
     *
     * @return Response
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
            $classe = ESBTPClasse::with(['matieres', 'filiere'])->findOrFail($request->classe_id);
            abort_if(
                ($classe->systeme_academique ?? '') === 'LMD',
                422,
                'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour générer des bulletins LMD.'
            );
            // Tronc commun (C10) : union [filière classe, filière TC parente] + fallback pivot.
            // Meme ordre que le PDF officiel : l'apercu et la configuration ne
            // doivent pas presenter les matieres autrement que le bulletin.
            $matieres = $this->subjectOrder->orderedSubjectsForClasse($classe);

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
                try {
                    $coefficient = $this->bulletinService->getCoefficientForCombination(
                        $matiere->id,
                        $classe->id,
                        $request->annee_universitaire_id,
                        $request->periode,
                        $request->etudiant_id
                    );
                } catch (\RuntimeException $e) {
                    if (! str_contains($e->getMessage(), 'Coefficient manquant')) {
                        throw $e;
                    }
                    Log::warning('Coef introuvable matiere TC, skip', ['matiere_id' => $matiere->id, 'classe_id' => $classe->id]);

                    continue;
                }

                // Le point d'entree unique, et non un `withTrashed()` recopie
                // ici : il porte l'explication de la cle unique sans
                // `deleted_at`, et c'est lui qu'on trouvera en cherchant
                // comment ecrire une ligne de bulletin.
                //
                // Passer par lui retire au passage l'ecriture de `commentaire`,
                // qui n'est une colonne d'AUCUNE des deux tables de resultats —
                // un nom invente, jamais migre. `$fillable` l'ecarte. La vraie
                // colonne s'appelle `appreciation`, et c'est la generation qui
                // la renseigne.
                ESBTPResultatMatiere::poserSurLeBulletin(
                    (int) $bulletin->id,
                    (int) $matiere->id,
                    [
                        'moyenne' => $moyenne,
                        'coefficient' => $coefficient,
                    ]
                );
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

            return redirect()->route('esbtp.bulletins.show', $bulletin)
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
     * @return Response
     */
    public function show(ESBTPBulletin $bulletin)
    {
        $this->authorize('view', $bulletin);

        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere', 'user']);

        // Recalculer les absences EN LIVE (saisie manuelle par matière + globale incluse)
        // via le MÊME wrapper que le PDF (calculerAbsencesDetailees) → cohérence garantie
        // entre la fiche et le PDF. Les colonnes esbtp_bulletins.absences_* peuvent être
        // figées à 0 tant qu'aucune (re)génération n'a tourné depuis la saisie manuelle.
        // Le wrapper absorbe ses propres erreurs (retourne 0), d'où le fallback colonne.
        $absences = $this->bulletinService->calculerAbsencesDetailees($bulletin);
        $absencesJustifiees = $absences['justifiees'] ?? $bulletin->absences_justifiees ?? 0;
        $absencesNonJustifiees = $absences['non_justifiees'] ?? $bulletin->absences_non_justifiees ?? 0;

        return view('esbtp.bulletins.show', compact('bulletin', 'absencesJustifiees', 'absencesNonJustifiees'));
    }

    /**
     * Affiche le formulaire de modification d'un bulletin.
     *
     * @return Response
     */
    public function edit(ESBTPBulletin $bulletin)
    {
        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere']);

        return view('esbtp.bulletins.edit', compact('bulletin'));
    }

    /**
     * Met à jour un bulletin spécifique.
     *
     * @return Response
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
            // Un seul chemin d'ecriture, celui du modele : il sait qu'une ligne
            // soft-deletee occupe la cle unique sans etre visible, et il la
            // ressuscite au lieu d'en creer une seconde. Le chargement prealable
            // de toutes les lignes et la bifurcation qui l'accompagnait ne
            // servaient qu'a rejouer ce que cette methode fait deja.
            //
            // `commentaire` a disparu avec : ce n'est une colonne d'AUCUNE des
            // deux tables de resultats. La colonne reelle est `appreciation`,
            // et la renseigner ici demanderait de reparer d'abord cet ecran —
            // voir la note de suivi sur `/esbtp/bulletins/{id}/edit`.
            foreach ($request->resultats as $resultatData) {
                $moyenne = $resultatData['moyenne'] !== null && $resultatData['moyenne'] !== ''
                    ? $resultatData['moyenne'] : null;

                ESBTPResultatMatiere::poserSurLeBulletin(
                    (int) $bulletin->id,
                    (int) $resultatData['matiere_id'],
                    [
                        'moyenne' => $moyenne,
                        'coefficient' => $resultatData['coefficient'],
                    ]
                );
            }

            // Recalculer la moyenne générale
            $this->bulletinService->calculerMoyenneGenerale($bulletin);

            DB::commit();

            return redirect()->route('esbtp.bulletins.show', $bulletin)
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
     * @return Response
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
     * Bulk : publier plusieurs bulletins (AJAX).
     */
    public function bulkPublish(Request $request)
    {
        $ids = $this->validateBulkIds($request);
        $count = ESBTPBulletin::whereIn('id', $ids)->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "$count bulletin(s) publié(s).",
            'count' => $count,
        ]);
    }

    /**
     * Bulk : régénérer plusieurs bulletins (recalcul moyennes via BulletinService).
     */
    public function bulkRegenerate(Request $request)
    {
        $ids = $this->validateBulkIds($request);
        $bulletins = ESBTPBulletin::whereIn('id', $ids)->get();
        $success = 0;
        $errors = [];

        foreach ($bulletins as $bulletin) {
            try {
                $this->assertBtsBulletinReady(
                    (int) $bulletin->etudiant_id,
                    (int) $bulletin->classe_id,
                    (int) $bulletin->annee_universitaire_id,
                    (string) $bulletin->periode,
                    $request
                );
                $this->bulletinConsistencyService->regenerateOfficialBulletin(
                    (int) $bulletin->etudiant_id,
                    (int) $bulletin->classe_id,
                    (int) $bulletin->annee_universitaire_id,
                    (string) $bulletin->periode
                );
                $success++;
            } catch (\Throwable $e) {
                $errors[] = "Bulletin #{$bulletin->id} : {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'message' => "$success bulletin(s) régénéré(s)".(count($errors) ? ' — '.count($errors).' erreur(s)' : '.'),
            'count' => $success,
            'errors' => $errors,
        ]);
    }

    /**
     * Bulk : supprimer plusieurs bulletins.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $this->validateBulkIds($request);
        $count = ESBTPBulletin::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "$count bulletin(s) supprimé(s).",
            'count' => $count,
        ]);
    }

    private function validateBulkIds(Request $request): array
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:esbtp_bulletins,id',
        ]);

        return $validated['ids'];
    }

    /**
     * Aperçu PDF du bulletin (Content-Disposition: inline) — ouvre dans une
     * nouvelle tab pour vérifier avant téléchargement.
     */
    public function previewPDF(ESBTPBulletin $bulletin)
    {
        // P-D : unification endpoints PDF. Si le snapshot officiel diverge de la
        // donnée live (notes ajoutées/modifiées depuis la dernière génération),
        // on redirige vers l'endpoint params-preview qui choisira automatiquement
        // entre snapshot et live selon BulletinConsistencyService.
        // Sinon, on rend directement le snapshot (rapide, pas de recomputation).
        if (
            $bulletin->etudiant_id
            && $bulletin->classe_id
            && $bulletin->annee_universitaire_id
            && $bulletin->periode
        ) {
            try {
                $consistency = $this->bulletinConsistencyService->getSnapshot(
                    (int) $bulletin->etudiant_id,
                    (int) $bulletin->classe_id,
                    (int) $bulletin->annee_universitaire_id,
                    (string) $bulletin->periode
                );
                if (! empty($consistency['has_divergence'])) {
                    return redirect()->route('esbtp.bulletins.pdf-params-preview', [
                        'etudiant_id' => $bulletin->etudiant_id,
                        'classe_id' => $bulletin->classe_id,
                        'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                        'periode' => $bulletin->periode,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('previewPDF: consistency check failed, fallback snapshot', [
                    'bulletin_id' => $bulletin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->genererPDF($bulletin, true);
    }

    /**
     * Génère un PDF du bulletin. Si $inline est true, le PDF est streamé
     * inline (preview), sinon téléchargé en attachment (download).
     *
     * @return Response
     */
    public function genererPDF(ESBTPBulletin $bulletin, bool $inline = false)
    {
        $this->authorize('download', $bulletin);

        app(DocumentPrintGuard::class)->assertPrintable(
            auth()->user(),
            'bulletin',
            (int) $bulletin->etudiant_id,
            (int) $bulletin->id
        );

        try {
            $pdf = $this->buildBulletinPdf($bulletin);

            $filename = 'bulletin_'.
                        ($bulletin->etudiant ? $bulletin->etudiant->matricule : 'unknown').'_'.
                        ($bulletin->classe ? $bulletin->classe->code : 'unknown').'_'.
                        $bulletin->periode.'_'.
                        ($bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire->display_name : 'unknown').'.pdf';

            // Stream inline (preview) ou télécharger selon le mode demandé
            return $inline ? $pdf->stream($filename) : $pdf->download($filename);
        } catch (BulletinConfigurationException $e) {
            // Bulletin non configuré : message clair + lien vers la configuration des
            // matières, plutôt qu'un « Undefined variable $note_assiduite » (500 technique).
            $context = $e->getContext();
            $configUrl = $context['configuration_url'] ?? route('esbtp.bulletins.config-matieres', array_filter([
                'classe_id' => $bulletin->classe_id,
                'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                'periode' => $bulletin->periode,
                'bulletin' => $bulletin->etudiant_id,
                'etudiant_id' => $bulletin->etudiant_id,
            ]));

            return redirect($configUrl)->with('warning',
                "Ce bulletin n'est pas encore configuré : configurez d'abord les matières "
                .'(types, coefficients et professeurs) avant de générer le PDF.');
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la génération du PDF du bulletin #'.$bulletin->id.': '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());

            return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: '.$e->getMessage());
        }
    }

    /**
     * Construit le PDF DomPDF d'un bulletin SANS le streamer/télécharger.
     * Extrait de genererPDF() afin d'être réutilisé à l'identique par l'export
     * groupé (exportBulkPdf) : chaque page du PDF fusionné est byte-identique au
     * téléchargement unitaire. Lève une exception en cas d'échec (le caller décide
     * du fallback).
     */
    public function buildBulletinPdf(ESBTPBulletin $bulletin, bool $persist = true): \Barryvdh\DomPDF\PDF
    {
        // $persist=false (export groupé) : on calcule et on met à jour l'objet en
        // mémoire pour un rendu identique, MAIS on n'écrit rien en base — un GET
        // d'export ne doit pas déclencher N écritures.
        try {
            Log::debug('Début de la génération du PDF pour le bulletin #'.$bulletin->id);

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

            // Ces trois methodes du modele font un save() inconditionnel : elles
            // ne peuvent donc tourner que sur le chemin persistant. Un bulletin
            // publie a deja ces valeurs, et le rendu en lecture seule recalcule
            // la moyenne en memoire plus bas.
            // Calculer la moyenne générale si pas déjà fait
            if ($persist && ! $bulletin->moyenne_generale) {
                try {
                    $bulletin->calculerMoyenneGenerale();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la moyenne générale: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
                    $bulletin->moyenne_generale = 0;
                }
            }

            // Calculer la mention si pas déjà fait
            if ($persist && ! $bulletin->mention) {
                try {
                    $bulletin->calculerMention();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la mention: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
                    $bulletin->mention = 'Non calculée';
                }
            }

            // Recalculer toujours le rang de toute la classe. Un rang déjà
            // égal à 1 n'est pas un signal de "déjà calculé".
            if ($persist) {
                try {
                    $this->bulletinService->calculerRangsPourClasse(
                        (int) $bulletin->classe_id,
                        (int) $bulletin->annee_universitaire_id,
                        (string) $bulletin->periode
                    );
                    $bulletin->refresh();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul du rang: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
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
                    Log::debug('Tentative de calcul des absences via le service pour le bulletin #'.$bulletin->id);

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
                    Log::debug('Calcul des absences via le service réussi: '.json_encode($absencesAttendance));
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul des absences via le service: '.$e->getMessage());
                    Log::error('Trace: '.$e->getTraceAsString());
                }
            }

            // Récupérer les vraies notes de l'étudiant depuis la table esbtp_notes
            try {
                Log::debug('Récupération des vraies notes pour l\'étudiant #'.$bulletin->etudiant_id);

                // Récupérer les notes de l'étudiant pour la période du bulletin.
                // BUG corrigé : avant aucun filtre periode → S1 et S2 mélangés sur le PDF S2.
                // La période vit sur evaluation.periode (semestre1 | semestre2 | annuel).
                $bulletinPeriode = $this->bulletinService->normalizePeriode($bulletin->periode ?? 'semestre1');
                $notesEtudiant = ESBTPNote::where('etudiant_id', $bulletin->etudiant_id)
                    ->where('classe_id', $bulletin->classe_id)
                    ->whereHas('evaluation', function ($q) use ($bulletinPeriode) {
                        if ($bulletinPeriode === 'annuel') {
                            return; // pas de filtre → toutes les périodes
                        }
                        $q->where('periode', $bulletinPeriode);
                    })
                    ->with(['matiere', 'evaluation'])
                    ->get();

                Log::debug('Notes trouvées: '.$notesEtudiant->count().' periode_attendue='.$bulletinPeriode);

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

                    // CE CALCUL EST INERTE AUJOURD'HUI, ET LE FILTRE RESTE QUAND MEME.
                    // Une revue l'a signale comme « sixieme calcul de moyenne, et le
                    // seul qui ecrive » : il relit bien `esbtp_notes` lui-meme, sans
                    // passer par `BulletinService`, et il ecrit sa moyenne plus bas
                    // (`$bulletin->save()`). MESURE : ce n'est pas une fuite.
                    // `array_replace($data, getOfficialBulletinTemplateDefaults(...))`
                    // remplace ensuite `resultatsGeneraux`, `resultatsTechniques` et
                    // `moyenneGlobale` par la projection du service, deja filtree ; et
                    // cette projection re-enregistre la bonne moyenne apres celle-ci.
                    // Le document imprime et la base portent donc la valeur juste,
                    // avec ou sans ce filtre — verifie en retirant le filtre : le test
                    // reste vert.
                    //
                    // Il est garde pour une seule raison : le jour ou l'ordre de
                    // `array_replace` change, ou qu'une cle disparait de la projection,
                    // ce calcul-ci reprend la main en silence. La vraie correction
                    // serait qu'il cesse d'exister et lise le service, comme l'apercu ;
                    // c'est un refactor a part, note dans
                    // `.claude/rules/lmd-ecue-leak-bts-picker.md`.
                    if ($matiere && ! CoherenceSystemeAcademique::matiereRetenue($matiere, $bulletin->classe, 'pdf bulletin/note')) {
                        continue;
                    }

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
                        // Fallback 1 si coefficient missing — évite que toute la boucle bail
                        // (catch ligne 641 met resultatsGeneraux/Techniques = empty → 0 matières)
                        try {
                            $coefficient = $this->bulletinService->getCoefficientForCombination(
                                $matiere->id,
                                $bulletin->classe_id,
                                $bulletin->annee_universitaire_id,
                                $bulletin->periode,
                                $bulletin->etudiant_id
                            );
                        } catch (\RuntimeException $e) {
                            if (! str_contains($e->getMessage(), 'Coefficient manquant')) {
                                throw $e;
                            }
                            $coefficient = 1;
                            Log::warning('Coefficient manquant pour matière #'.$matiere->id.' bulletin #'.$bulletin->id.' — fallback à 1');
                        }

                        Log::debug('Matière: '.$matiere->name.' - '.$notes->count().' notes');
                        Log::debug('Total pondéré: '.$totalPondere.', Total coefficients: '.$totalCoefficients);
                        Log::debug('Moyenne pondérée: '.round($moyenneMatiere, 2));

                        // Créer un objet résultat formaté pour le template
                        $resultatFormate = (object) [
                            'id' => $notes->first()->id,
                            'note' => round($moyenneMatiere, 2),
                            'moyenne' => round($moyenneMatiere, 2),
                            'appreciation' => $this->bulletinService->getAppreciation($moyenneMatiere),
                            'matiere' => $matiere,
                            'matiere_id' => $matiere->id,
                            'moyenne_matiere' => round($moyenneMatiere, 2),
                            'coefficient' => $coefficient,
                            'rang' => null, // À calculer si nécessaire
                            'evaluations_detail' => $evaluationsDetail,
                            'total_coefficients' => $totalCoefficients,
                        ];

                        // Type de formation : résolution canonique (ConfigMatiere → bulletin JSON → matiere globale)
                        $typeForm = $this->bulletinService->resolveMatiereTypeFormation(
                            $matiere->id,
                            (int) $bulletin->classe_id,
                            (string) ($bulletin->periode ?? 'semestre1'),
                            (int) $bulletin->annee_universitaire_id,
                            $bulletin
                        );
                        if ($typeForm === 'technologique_professionnelle') {
                            $totalTechnique += $moyenneMatiere * $coefficient;
                            $countTechnique += $coefficient;
                            $resultatsTechniques->push($resultatFormate);
                        } else {
                            $totalGeneral += $moyenneMatiere * $coefficient;
                            $countGeneral += $coefficient;
                            $resultatsGeneraux->push($resultatFormate);
                        }
                    }
                }

                // Calculer les moyennes correctement
                $moyenneGenerale = $countGeneral > 0 ? round($totalGeneral / $countGeneral, 2) : 0;
                $moyenneTechnique = $countTechnique > 0 ? round($totalTechnique / $countTechnique, 2) : 0;

                // Calculer la moyenne globale (général + technique)
                $moyenneGlobale = ($countGeneral + $countTechnique) > 0 ?
                    round(($totalGeneral + $totalTechnique) / ($countGeneral + $countTechnique), 2) : 0;

                Log::debug('Moyennes calculées - Général: '.$moyenneGenerale.', Technique: '.$moyenneTechnique.', Globale: '.$moyenneGlobale);

                // Mettre à jour le bulletin avec les moyennes calculées
                if (! $bulletin->moyenne_generale || $bulletin->moyenne_generale != $moyenneGlobale) {
                    $bulletin->moyenne_generale = $moyenneGlobale;
                    if ($persist) {
                        $bulletin->save();
                        Log::debug('Moyenne générale mise à jour: '.$moyenneGlobale);
                    }
                }

            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des notes: '.$e->getMessage());
                Log::error('Trace: '.$e->getTraceAsString());
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
                $moyenneGenerale = 0;
                $moyenneTechnique = 0;
                $moyenneGlobale = 0; // Évite 'Undefined variable $moyenneGlobale' au rendu du template
            }

            // Générer le PDF avec les configurations de l'école
            $config = $this->bulletinService->getPDFConfig();

            $settings = $config;

            $semesterWeights = $this->bulletinService->getSemesterWeights($bulletin->classe);
            $periodeCourante = $bulletin->periode;
            // Recompute note d'assiduité depuis les absences finales (était à 0 — initialisé ligne 499
            // mais jamais réassigné → moyenneAvecAssiduite = brute, le +0.13 du template venait de
            // note_assiduite injecté par genererDonneesBulletin via getOfficialBulletinTemplateDefaults).
            // Maintenant cohérent : moyenneAvecAssiduite = brute + bonus assiduité.
            $noteAssiduite = $this->bulletinService->resolveAttendanceNote(
                $bulletin->absences_justifiees ?? 0,
                $bulletin->absences_non_justifiees ?? 0
            );
            // FIX : $moyenneGenerale = moyenne enseignement général uniquement (= 7.00 dans le cas ADIE).
            // Pour "Moyenne 1er/2e Semestre" il faut $moyenneGlobale (= général + technique = 10.49).
            // Bug observé : 7.00 + 0.13 = 7.13 au lieu de 10.49 + 0.13 = 10.62.
            $moyenneAvecAssiduite = $moyenneGlobale + ($noteAssiduite ?? 0);
            $classeIdS1 = (int) $bulletin->classe_id;
            $classMap = app(\App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver::class)
                ->resolve((int) $bulletin->etudiant_id, (int) $bulletin->classe_id, (int) $bulletin->annee_universitaire_id);
            if (! empty($classMap['semestre1_classe_id'])) {
                $classeIdS1 = (int) $classMap['semestre1_classe_id'];
            }
            $moyenneSemestre1 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                $bulletin->etudiant_id,
                $classeIdS1,
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
            $effectifClasse = $this->bulletinService->getValidatedClassStudentCount(
                $bulletin->classe_id,
                $bulletin->annee_universitaire_id,
                (string) $bulletin->periode
            );
            $rangAnnuel = $this->bulletinService->calculerRangAnnuel(
                (int) $bulletin->etudiant_id,
                (int) $bulletin->classe_id,
                (int) $bulletin->annee_universitaire_id,
                $moyenneAnnuelle
            );
            $classe = $bulletin->classe;
            $levelYear = $classe?->niveau?->year ?? $classe?->niveauEtude?->year;
            $levelYear = is_numeric($levelYear) ? (int) $levelYear : null;
            $automaticCouncilDecision = null;
            if ($classe) {
                $settings = \App\Services\BtsBulletinPolicy::readSettings(
                    fn (string $key, string $default) => \App\Helpers\SettingsHelper::get($key, $default)
                );
                $source = \App\Services\BtsBulletinPolicy::councilAverageSource($levelYear, $settings);
                $decisionAverage = \App\Services\BtsBulletinPolicy::decisionAverage($source, $moyenneSemestre2, $moyenneAnnuelle);
                $automaticCouncilDecision = \App\Services\BtsBulletinPolicy::councilDecision(
                    (bool) $classe->isBTS(),
                    $levelYear,
                    $this->bulletinService->normalizePeriode((string) $bulletin->periode),
                    $decisionAverage,
                    $settings
                );
            }
            $decisionConseil = \App\Services\BtsBulletinPolicy::displayCouncilDecision(
                $automaticCouncilDecision,
                $bulletin->decision_conseil
            );
            $councilDecision = [
                'title' => $this->bulletinService->councilDecisionTitle(
                    $classe,
                    $this->bulletinService->normalizePeriode((string) $bulletin->periode)
                ),
                'text' => (string) ($decisionConseil ?? ''),
            ];

            if ((int) $bulletin->effectif_classe !== $effectifClasse) {
                $bulletin->forceFill(['effectif_classe' => $effectifClasse]);
                if ($persist) {
                    $bulletin->save();
                }
            }

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
                'rangAnnuel' => $rangAnnuel,
                'councilDecision' => $councilDecision,
                'effectif' => $effectifClasse,
                'appreciation' => $bulletin->mention,
                'decisionConseil' => $decisionConseil,
                'date_edition' => now()->format('d/m/Y'),
                'absencesJustifiees' => $bulletin->absences_justifiees,
                'absencesNonJustifiees' => $bulletin->absences_non_justifiees,
                'absences_justifiees' => $bulletin->absences_justifiees,
                'absences_non_justifiees' => $bulletin->absences_non_justifiees,
                'config' => $config,
                'settings' => $settings, // Ajouter tous les paramètres de configuration
            ];

            // La projection canonique du service contient les rangs par matière et les
            // professeurs résolus. Elle doit remplacer les valeurs transitoires du
            // renderer, sinon le PDF groupé affiche des rangs figés à 1.
            $data = array_replace($data, $this->getOfficialBulletinTemplateDefaults($bulletin, $persist));

            // Log des variables d'absences pour debugging
            Log::debug('Variables d\'absence pour le PDF dans genererPDF:', [
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
            if (! empty($data['etudiant']?->photo)) {
                $photo = $data['etudiant']->photo;
                $photoCandidates = [
                    storage_path('app/public/'.$photo),
                    storage_path('app/public/photos/etudiants/'.basename($photo)),
                    public_path('storage/'.$photo),
                    public_path('storage/photos/etudiants/'.basename($photo)),
                ];
                foreach ($photoCandidates as $photoPath) {
                    if (file_exists($photoPath)) {
                        $data['photoEtudiantBase64'] = $this->bulletinService->convertImageToJpegBase64($photoPath);
                        break;
                    }
                }
            }

            // Ce rendu EST un export PDF : sans ce drapeau, le gabarit n'emet pas
            // son bloc @page et DomPDF retombe sur ses marges par defaut (~27 mm),
            // ce qui rendait les marges reglees dans la configuration sans effet.
            $data['isPdfExport'] = true;

            Log::debug('Chargement de la vue PDF avec le template configurable pour le bulletin #'.$bulletin->id);
            $pdf = PDF::loadView($this->bulletinService->getBulletinTemplateView(), $data);

            // Configuration PDF avec format A4 et options optimisées
            $paperFormat = SettingsHelper::get('bulletin_paper_format', 'A4');
            $orientation = SettingsHelper::get('bulletin_orientation', 'portrait');
            $dpi = SettingsHelper::get('bulletin_dpi', '150');

            $pdf->setPaper(strtolower($paperFormat), $orientation);
            $pdf->setOptions([
                'dpi' => intval($dpi),
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false, // Pour éviter les problèmes de sécurité
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);

            Log::info('PDF généré avec succès pour le bulletin #'.$bulletin->id);

            return $pdf;
        } catch (BulletinConfigurationException $e) {
            // Bulletin non configuré : ce n'est pas une erreur technique. On relaie
            // sans bruit de log ; le caller (genererPDF) affichera un message clair.
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Erreur buildBulletinPdf bulletin #'.$bulletin->id.': '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());

            throw $e;
        }
    }

    /**
     * Ordonne l'export groupé selon le tri demandé.
     *
     * Le nom de colonne n'est jamais repris tel quel de la requête : seules
     * les valeurs de la liste blanche ci-dessous atteignent le SQL, et le sens
     * est réduit à « asc » ou « desc ». Retourne l'ordre effectivement appliqué.
     */
    protected function applyBulletinExportOrder($query, Request $request): string
    {
        $order = (string) $request->input('order', 'classe');
        $dirInput = strtolower((string) $request->input('dir', ''));
        $dir = in_array($dirInput, ['asc', 'desc'], true) ? $dirInput : null;

        switch ($order) {
            case 'nom':
                $query->leftJoin('esbtp_etudiants as e_ord', 'e_ord.id', '=', 'esbtp_bulletins.etudiant_id')
                    ->orderBy('e_ord.nom', $dir ?? 'asc')
                    ->orderBy('e_ord.prenoms', $dir ?? 'asc')
                    ->select('esbtp_bulletins.*');

                return 'Nom';

            case 'matricule':
                $query->leftJoin('esbtp_etudiants as e_ord', 'e_ord.id', '=', 'esbtp_bulletins.etudiant_id')
                    ->orderBy('e_ord.matricule', $dir ?? 'asc')
                    ->select('esbtp_bulletins.*');

                return 'Matricule';

            case 'moyenne':
                // Défaut décroissant (ordre de mérite). whereNotNull en amont.
                $query->orderBy('esbtp_bulletins.moyenne_generale', $dir ?? 'desc');

                return 'Moyenne';

            case 'rang':
                $query->orderByRaw('esbtp_bulletins.rang IS NULL, esbtp_bulletins.rang '.($dir ?? 'asc'));

                return 'Rang';

            case 'classe':
            default:
                $query->leftJoin('esbtp_classes as c_ord', 'c_ord.id', '=', 'esbtp_bulletins.classe_id')
                    ->leftJoin('esbtp_etudiants as e_ord', 'e_ord.id', '=', 'esbtp_bulletins.etudiant_id')
                    ->orderBy('c_ord.name', $dir ?? 'asc')
                    ->orderBy('e_ord.nom', $dir ?? 'asc')
                    ->select('esbtp_bulletins.*');

                return 'Classe';
        }
    }

    /**
     * Exporte en UN seul PDF tous les bulletins du jeu filtré courant, dans
     * l'ordre choisi. Snapshot-only (bulletins déjà générés uniquement) : aucun
     * recalcul n'est déclenché depuis ce GET. Borné par un plafond configurable
     * (bulletins_bulk_export_cap) pour protéger mémoire/temps d'exécution.
     *
     * Le plafond par defaut est de 6, mesure sur esbtp-yakro le 22/08/2026 :
     * sept bulletins prennent 32 secondes en telechargement et 26 en apercu,
     * pour une limite d execution de l hebergeur autour de 30. Au-dela, la
     * requete est tuee et l utilisateur ne voit rien d exploitable.
     *
     * C est le meme nombre que la tranche de generation, qui repond a la meme
     * contrainte. Exporter une classe entiere demandera un decoupage en
     * plusieurs requetes, comme la generation le fait deja.
     */
    private function bulkExportFilename(): string
    {
        return 'bulletins_'.now()->format('Ymd_His').'.pdf';
    }

    /**
     * Page de garde d'avertissement : listée en tête du PDF groupé lorsqu'au moins
     * un bulletin du filtre est ABSENT (non généré, ou échec de rendu). Retourne
     * null si tout le filtre est inclus (aucun avertissement nécessaire).
     *
     * @param  \Illuminate\Support\Collection  $ungenerated  bulletins non générés (moyenne NULL)
     * @param  array<int, array{id: int, message: string}>  $failed  bulletins générés mais non rendus
     */
    /**
     * Page de garde : ce qui manque au document, et sur quel périmètre.
     *
     * Le périmètre est figé à l'ouverture de l'export et transporté jusqu'ici :
     * l'assemblage ne porte plus que le jeton, et la garde annonçait « Toutes
     * les classes, toutes les périodes » sur un export borné à une classe et
     * un semestre.
     *
     * @param  array{annee: ?string, classe: ?string, periode: ?string}  $entete
     */
    protected function buildExportCoverPdf(\Illuminate\Support\Collection $ungenerated, array $failed, array $entete, int $includedCount): ?\Barryvdh\DomPDF\PDF
    {
        $failedIds = collect($failed)->pluck('id')->filter();
        $failedBulletins = $failedIds->isNotEmpty()
            ? ESBTPBulletin::with(['etudiant:id,matricule,nom,prenoms', 'classe:id,name'])
                ->whereIn('id', $failedIds)->get()
            : collect();

        // Rien d'absent → pas de page de garde.
        if ($ungenerated->isEmpty() && $failedBulletins->isEmpty()) {
            return null;
        }

        // Liste plafonnée pour éviter une page de garde démesurée (ex: 600+ absents).
        $coverListLimit = 60;

        $pdf = PDF::loadView('esbtp.bulletins.pdf-export-cover', [
            'included' => $includedCount,
            'ungenerated' => $ungenerated->take($coverListLimit),
            'ungeneratedTotal' => $ungenerated->count(),
            'failed' => $failedBulletins->take($coverListLimit),
            'failedTotal' => $failedBulletins->count(),
            'annee' => $entete['annee'] ?? null,
            'classe' => $entete['classe'] ?? null,
            'periode' => $entete['periode'] ?? null,
            'config' => $this->bulletinService->getPDFConfig(),
            // Couleurs configurées par le tenant, comme les pages de bulletin elles-mêmes.
            'pdfSettings' => SettingsHelper::getPdfSettings(),
            'logoBase64' => $this->bulletinService->prepareLogoBase64($this->bulletinService->getPDFConfig()['school_logo'] ?? null),
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Calcule les absences justifiées et non justifiées pour un bulletin.
     *
     * @param  ESBTPBulletin  $bulletin
     * @return array
     */
    // ///////////////////
    /**
     * Genere les bulletins pour une classe entiere via le contrat bulk BTS.
     *
     * @return Response
     */
    public function genererClasseBulletins(GenerateClasseBulletinsRequest $request)
    {
        $classe = ESBTPClasse::findOrFail($request->integer('classe_id'));

        abort_if(
            ($classe->systeme_academique ?? '') === 'LMD',
            422,
            'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour generer des bulletins LMD en masse.'
        );

        // Traitement par tranches : la generation coute O(N^2) et l'hebergement
        // coupe a 30 secondes. Le front envoie une tranche a la fois et affiche
        // la progression. Le recalcul des rangs porte sur la cohorte entiere a
        // chaque passe, l'etat final est donc identique a une passe unique.
        $studentIds = $request->filled('student_ids')
            ? array_map('intval', (array) $request->input('student_ids'))
            : null;

        $result = $this->bulkBulletinGeneration->generate(
            $classe,
            $request->integer('annee_universitaire_id'),
            (string) $request->input('periode'),
            $request->user(),
            $request->boolean('recalculer'),
            $request->input('incomplete_reason'),
            $studentIds
        );

        if ($request->expectsJson()) {
            return response()->json($result->toArray(), $result->statusCode());
        }

        $redirect = redirect()->route('esbtp.bulletins.index', [
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $request->integer('annee_universitaire_id'),
            'periode_id' => $this->bulletinService->normalizePeriode((string) $request->input('periode')),
        ]);

        if ($result->hasWrites() && $result->hasFailures()) {
            return $redirect
                ->with('warning', $result->message())
                ->with('bulk_bulletin_generation', $result->toArray());
        }

        if ($result->hasWrites()) {
            return $redirect
                ->with('success', $result->message())
                ->with('bulk_bulletin_generation', $result->toArray());
        }

        if ($result->hasFailures()) {
            return back()
                ->with('error', $result->message())
                ->with('bulk_bulletin_generation', $result->toArray())
                ->withInput();
        }

        return $redirect
            ->with('info', $result->message())
            ->with('bulk_bulletin_generation', $result->toArray());
    }

    /**
     * Supprime les moyennes enregistrees pour une matiere qui n'a plus aucune
     * note sur la periode, dans une classe.
     *
     * Le pre-controle les signale sans jamais les toucher : une moyenne saisie
     * a la main a exactement la meme apparence qu'une ligne laissee par une
     * evaluation deplacee d'un semestre a l'autre. La suppression est donc
     * demandee matiere par matiere, par quelqu'un qui sait laquelle est la
     * sienne, et jamais deduite.
     */
    public function supprimerMoyennesSansNote(Request $request)
    {
        $valide = $request->validate([
            'classe_id' => 'required|integer|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|string',
            'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
        ]);

        $periodes = $this->bulletinService->periodeAliases(
            $this->bulletinService->normalizePeriode($valide['periode'])
        );

        // La garde « plus aucune note » est REAPPLIQUEE sur la suppression
        // elle-meme, dans la meme requete SQL : lister puis supprimer par
        // identifiants laissait une fenetre entre les deux ou une note
        // reapparue rendait la ligne legitime -- elle doit survivre. Chaque
        // ligne detruite laisse sa valeur dans `audits` (modele Auditable,
        // evenement deleted, moyenne dans la whitelist).
        $requete = ESBTPResultat::query()
            ->sansNoteSurLaPeriode((int) $valide['classe_id'], (int) $valide['annee_universitaire_id'], $periodes)
            ->where('matiere_id', $valide['matiere_id']);

        $etudiants = (clone $requete)->pluck('etudiant_id')->all();
        $supprimees = 0;

        // Suppression modele par modele et non en masse : le delete de masse
        // contourne les evenements Eloquent, donc l'audit.
        foreach ((clone $requete)->get() as $ligne) {
            $supprimees += $ligne->delete() ? 1 : 0;
        }

        Log::warning('Moyennes sans note supprimees', [
            'classe_id' => (int) $valide['classe_id'],
            'annee_universitaire_id' => (int) $valide['annee_universitaire_id'],
            'periode' => $valide['periode'],
            'matiere_id' => (int) $valide['matiere_id'],
            'supprimees' => $supprimees,
            'etudiants' => $etudiants,
            'par' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'supprimees' => $supprimees,
            'message' => $supprimees > 0
                ? "$supprimees moyenne(s) supprimee(s)."
                : 'Aucune moyenne a supprimer : elles ont retrouve une note.',
        ]);
    }

    public function preflightClasseBulletins(GenerateClasseBulletinsRequest $request)
    {
        $classe = ESBTPClasse::findOrFail($request->integer('classe_id'));

        abort_if(
            ($classe->systeme_academique ?? '') === 'LMD',
            422,
            'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour les bulletins LMD.'
        );

        $preflight = $this->bulkBulletinGeneration->preflight(
            $classe,
            $request->integer('annee_universitaire_id'),
            (string) $request->input('periode'),
            $request->user(),
            $request->boolean('recalculer')
        );

        // `student_ids` vient du pre-controle lui-meme : c'est lui qui sait
        // qui sera traite. La liste etait ici la cohorte entiere, donc soixante-dix
        // identifiants quand le pre-controle en annonçait soixante-neuf
        // generables. Deux nombres pour la meme chose a l'ecran, et surtout des
        // tranches composees uniquement d'etudiants refuses : elles repondaient
        // 422 et arretaient la boucle du front.
        //
        // 6 et non 10 : mesure a 24,6 s pour 10 etudiants, trop pres de la
        // limite de 30 s pour tenir sous charge.
        $preflight['batch_size'] = 6;

        // Le constat de couverture etait calcule ici et joint a la reponse.
        // AUCUN ecran ne le lisait : le panneau de pre-controle
        // (`bulletins/partials/select-scripts.blade.php`) lit `ok`, `status`,
        // `students_count`, `student_ids`, `batch_size` et `message`, jamais
        // `couverture`. C'etait donc un calcul complet — matieres, cohorte,
        // notes de toute la classe — paye a chaque pre-controle pour personne.
        //
        // Le bandeau de couverture, lui, est deja present sur l'ecran de
        // selection des bulletins : il interroge sa propre adresse, avec son
        // cache. Si le panneau de pre-controle doit un jour montrer ce constat,
        // c'est de la qu'il faut le prendre.

        return response()->json([
            'ok' => $preflight['ok'],
            'preflight' => $preflight,
            'message' => $preflight['message'],
        ], $preflight['ok'] ? 200 : 422);
    }
    /**
     * Affiche la page de sélection pour les bulletins
     *
     * @return Response
     */
    public function select()
    {
        $classes = ESBTPClasse::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('systeme_academique')->orWhere('systeme_academique', '!=', 'LMD'))
            ->orderBy('name')
            ->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        // L'année par défaut : on prend l'année marquée is_current (l'année académique
        // en cours pour l'école), avec fallback sur is_active si jamais aucune is_current.
        $anneeActuelle = ESBTPAnneeUniversitaire::where('is_current', true)->first()
            ?? ESBTPAnneeUniversitaire::anneeCourante();

        return view('esbtp.bulletins.select', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Signe un bulletin par un responsable
     *
     * @param  string  $role
     * @return Response
     */
    public function signer(Request $request, ESBTPBulletin $bulletin, $role)
    {
        if (! in_array($role, ['directeur', 'responsable', 'parent'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Rôle de signature invalide.'], 422);
            }

            return back()->with('error', 'Rôle de signature invalide.');
        }

        try {
            $bulletin->signer($role);
            $bulletin->refresh();

            if ($request->expectsJson()) {
                $dateRaw = $bulletin->{'date_signature_'.$role} ?? null;

                return response()->json([
                    'success' => true,
                    'message' => 'Bulletin signé avec succès.',
                    'role' => $role,
                    'signed_at' => $dateRaw
                        ? \Illuminate\Support\Carbon::parse($dateRaw)->format('d/m/Y à H:i')
                        : now()->format('d/m/Y à H:i'),
                ]);
            }

            return back()->with('success', 'Bulletin signé avec succès.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Erreur lors de la signature : '.$e->getMessage()], 500);
            }

            return back()->with('error', 'Erreur lors de la signature: '.$e->getMessage());
        }
    }

    /**
     * Bascule l'état de publication d'un bulletin
     *
     * @return Response
     */
    public function togglePublication(Request $request, ESBTPBulletin $bulletin)
    {
        try {
            $wasPublished = $bulletin->is_published;
            $bulletin->is_published = ! $bulletin->is_published;
            $bulletin->save();

            // Si le bulletin vient d'être publié, notifier les parents
            if (! $wasPublished && $bulletin->is_published) {
                try {
                    $notificationService = app(NotificationService::class);
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

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'is_published' => (bool) $bulletin->is_published,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Erreur lors du changement de statut : '.$e->getMessage()], 500);
            }

            return back()->with('error', 'Erreur lors du changement de statut: '.$e->getMessage());
        }
    }

    /**
     * Affiche les bulletins en attente (non publiés ou non signés)
     *
     * @return Response
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
     * @return Response
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

            // Récupérer les matières de la classe (tronc commun-aware, C10) :
            // union [filière classe, filière TC parente] + fallback pivot.
            // Meme ordre que le PDF officiel : l'apercu et la configuration ne
            // doivent pas presenter les matieres autrement que le bulletin.
            $matieres = $this->subjectOrder->orderedSubjectsForClasse($classe);

            // Période prévisualisée (evaluation.periode : semestre1 | semestre2 | annuel).
            $periode = $this->bulletinService->normalizePeriode($request->periode ?? ($bulletin->periode ?? 'semestre1'));

            // Récupérer les évaluations pour ces matières, SCOPÉES à la période.
            $evaluations = ESBTPEvaluation::whereIn('matiere_id', $matieres->pluck('id'))
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->where('status', '!=', 'cancelled')
                ->when($periode !== 'annuel', fn ($q) => $q->where('periode', $periode))
                ->orderBy('titre')
                ->get();

            // Grouper les évaluations par matière
            $evaluationsParMatiere = $evaluations->groupBy('matiere_id');

            // Récupérer les notes de l'étudiant
            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
                ->whereIn('evaluation_id', $evaluations->pluck('id'))
                ->get()
                ->keyBy('evaluation_id');

            // FILTRE (aligné sur la génération) : une matière n'apparaît QUE si l'étudiant
            // a au moins une note dans cette matière POUR LA PÉRIODE. Sans ce filtre, une
            // matière de spécialité évaluée dans un autre semestre remontait sur le
            // bulletin tronc commun (régression réintroduite après le split du contrôleur).
            $matiereIdsAvecNotes = $evaluations
                ->whereIn('id', $notes->keys())
                ->pluck('matiere_id')
                ->unique();
            $matieres = $matieres->filter(fn ($m) => $matiereIdsAvecNotes->contains($m->id))->values();

            // Grouper les matières (filtrées) par filière
            $matieresByFiliere = $matieres->groupBy(function ($matiere) use ($classe) {
                return $classe->filiere->name ?? 'Non défini';
            });

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
            $resultats = ESBTPResultat::where('etudiant_id', $etudiant->id)
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
                // Décodage tolérant via l'unique helper canonical : config_matieres est
                // casté `json` (array), professeurs est une chaîne brute — json_decode(array)
                // lèverait un TypeError.
                $configMatieres = $this->bulletinService->decodeJsonToArray($bulletin->config_matieres);
                $professeurs = $this->bulletinService->decodeJsonToArray($bulletin->professeurs);
            }

            // Préparer le logo et configuration PDF
            $logoBase64 = null;
            $config = $this->bulletinService->getPDFConfig();
            $schoolInfo = [
                'name' => $config['school_name'] ?? '',
                'address' => $config['school_address'] ?? '',
                'phone' => $config['school_phone'] ?? '',
                'email' => $config['school_email'] ?? '',
                'city' => $config['school_city'] ?? '',
                'country' => $config['school_country'] ?? '',
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
            $totalEtudiants = $this->bulletinService->getValidatedClassStudentCount(
                (int) $classe->id,
                (int) $anneeUniversitaire->id,
                $periode
            );

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

        } catch (HttpExceptionInterface $e) {
            // Laisser passer les réponses HTTP volontaires (ex: garde LMD abort_if(422)).
            // Sinon le catch générique ci-dessous les convertirait en redirect 302.
            throw $e;
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
            $donnees = $inline
                ? $this->bulletinService->genererDonneesBulletinPreview(
                    $etudiant_id,
                    $classe_id,
                    $annee_universitaire_id,
                    $periode
                )
                : $this->bulletinService->genererDonneesBulletin(
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
            if (! empty($donnees['etudiant']?->photo)) {
                $photo = $donnees['etudiant']->photo;
                $photoCandidates = [
                    storage_path('app/public/'.$photo),
                    storage_path('app/public/photos/etudiants/'.basename($photo)),
                    public_path('storage/'.$photo),
                    public_path('storage/photos/etudiants/'.basename($photo)),
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
                        ($donnees['anneeUniversitaire']->display_name ?? 'unknown').'.pdf';

            return $inline ? $pdf->stream($filename) : $pdf->download($filename);

        } catch (CoefficientMissingException $e) {
            $context = $this->buildCoefficientIssueContext($e->getContext(), $request);
            $message = $this->formatCoefficientIssueMessage($context);

            if ($inline || $request->expectsJson()) {
                return $this->blockedBulletinPreviewResponse($request, $message, $context['config_url'] ?? null, $context);
            }

            return redirect()->back()
                ->with('error', $message)
                ->with('coefficient_missing_context', $context);
        } catch (BulletinConfigurationException $e) {
            $context = $e->getContext();

            return $this->blockedBulletinPreviewResponse(
                $request,
                $e->getMessage(),
                $context['configuration_url'] ?? null,
                $context
            );
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Coefficient manquant')) {
                return $this->blockedBulletinPreviewResponse(
                    $request,
                    $e->getMessage(),
                    route('esbtp.evaluations.index', ['open_coefficients' => 1]),
                    ['reason' => 'coefficients_missing']
                );
            }

            // Gestion des erreurs de configuration
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $request->classe_id,
                    'periode' => $request->periode ?? 'semestre1',
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'bulletin' => $request->etudiant_id ?? $request->bulletin,
                ]);

                return $this->blockedBulletinPreviewResponse($request, $e->getMessage(), $configMatieresUrl);
            }

            return back()->with('error', 'Erreur lors de la génération du PDF : '.$e->getMessage());
        }
    }

    /**
     * Vérifie les pré-requis avant génération du bulletin (AJAX).
     * Retourne les warnings éventuels (ex: bulletin de l'autre semestre non généré).
     */
    private function blockedBulletinPreviewResponse(
        Request $request,
        string $message,
        ?string $configurationUrl = null,
        array $context = [],
        int $status = 422
    ) {
        $payload = [
            'ok' => false,
            'message' => $message,
            'configuration_url' => $configurationUrl,
            'context' => $context,
            'back_url' => route('esbtp.bulletins.select'),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, $status);
        }

        if ($configurationUrl) {
            return redirect()->to($configurationUrl)->with('error', $message);
        }

        return response()->view('esbtp.bulletins.preview-blocked', $payload, $status);
    }

    public function checkBulletinPrerequisites(Request $request)
    {
        $classeId = $request->classe_id;
        $etudiantId = $request->etudiant_id ?? $request->bulletin;
        $periode = $request->periode ?? 'semestre1';
        $anneeId = $request->annee_universitaire_id;

        $warnings = [];

        $otherPeriode = $periode === 'semestre1' ? 'semestre2' : 'semestre1';
        $otherLabel = $otherPeriode === 'semestre1' ? 'Semestre 1' : 'Semestre 2';

        $otherBulletinExists = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('periode', $otherPeriode)
            ->where('moyenne_generale', '>', 0)
            ->exists();

        // Avertir seulement quand on génère S2 sans bulletin S1 officiel
        // (En S1, c'est normal que S2 n'existe pas encore)
        if (! $otherBulletinExists && $periode === 'semestre2') {
            $hasNotes = ESBTPNote::where('etudiant_id', $etudiantId)
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
            // 'annuel' ajouté : le bouton 'Régénérer le bulletin' sur la page result
            // detail peut être déclenché en mode annuel (S1+S2 combinés)
            'periode' => 'required|string|in:1,2,semestre1,semestre2,annuel',
            'incomplete_reason' => GenerateClasseBulletinsRequest::REGLE_MOTIF_INCOMPLET,
        ]);

        try {
            $this->assertBtsBulletinReady(
                (int) $validated['etudiant_id'],
                (int) $validated['classe_id'],
                (int) $validated['annee_universitaire_id'],
                (string) $validated['periode'],
                $request
            );
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
        } catch (AcademicPilotageException $e) {
            return $e->render($request);
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
     * Vérifie qu'un bulletin BTS peut être généré ou régénéré.
     */
    private function assertBtsBulletinReady(
        int $studentId,
        int $classId,
        int $academicYearId,
        string $period,
        Request $request,
    ): void {
        $this->bulletinReadiness->assertReady(
            'BTS',
            $studentId,
            $classId,
            $academicYearId,
            $period,
            $request->user(),
            $request->input('incomplete_reason'),
        );
    }

    private function bulletinReadinessErrorSummary(array $errors): string
    {
        $visible = array_slice($errors, 0, 3);
        $remaining = count($errors) - count($visible);
        $message = 'Génération bloquée par des données académiques incomplètes : '.implode(' | ', $visible);

        if ($remaining > 0) {
            $message .= " | {$remaining} autre(s) blocage(s).";
        }

        return $message;
    }

    /**
     * @deprecated Route stub, use genererBulletin() instead.
     *
     * @return Response
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
        $effectiveBtsSettings = BtsBulletinPolicy::effectiveSettings(
            $request->all(),
            fn (string $key, string $default) => SettingsHelper::get($key, $default)
        );
        $validator = Validator::make($effectiveBtsSettings, BtsBulletinPolicy::validationRules());
        $validator->after(function ($validator) use ($effectiveBtsSettings) {
            foreach (BtsBulletinPolicy::invalidWeightPairYears($effectiveBtsSettings) as $year) {
                $semester2Key = "bulletin_bts{$year}_semester2_weight";
                $validator->errors()->add(
                    $semester2Key,
                    "La pondération BTS {$year} doit garder au moins un semestre actif."
                );
            }
        });
        $validator->validate();

        try {
            // Les valeurs postees ne servaient pas au diagnostic : les CLES suffisent
            // a savoir ce que l ecran a envoye, sans recopier son contenu dans un
            // fichier conserve quatorze jours.
            \Log::info('Début de sauvegarde configuration', ['champs' => array_keys($request->all())]);

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
                'bulletin_appreciation_plain',
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
                'bulletin_margin_vertical',
                'bulletin_margin_horizontal',
                'bulletin_decision_min_height',
                'bulletin_signature_height',
                'bulletin_school_name_custom',
                'bulletin_republic_text',
                'bulletin_union_text',
                'bulletin_ministry_text',
                'bulletin_cycle_text',
                'bulletin_cycle_abbreviation',
                'bulletin_table_border_style',
                'bulletin_bts1_semester1_weight',
                'bulletin_bts1_semester2_weight',
                'bulletin_bts2_semester1_weight',
                'bulletin_bts2_semester2_weight',
                'bulletin_bts1_council_mode',
                'bulletin_bts1_council_average_source',
                'bulletin_bts1_council_threshold',
                'bulletin_bts1_council_below_text',
                'bulletin_bts1_council_at_or_above_text',
                'bulletin_bts2_council_mode',
                'bulletin_bts2_council_fixed_text',
                'bulletin_bts1_s1_council_title',
                'bulletin_style',
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

            // A partial POST (BTS weights/council only) must not treat missing
            // display checkboxes as unchecked. Flip a toggle only when the
            // submitted form actually carries that field or a sibling toggle.
            $submittedCheckboxFields = array_values(array_filter(
                $checkboxFields,
                fn (string $field) => $request->exists($field)
            ));
            $treatMissingCheckboxesAsOff = $request->boolean('bulletin_save_display');

            if ($treatMissingCheckboxesAsOff) {
                // Le formulaire annonce qu'il porte les cases d'affichage, mais il
                // n'en rend pas forcement la totalite. Balayer aveuglement mettait
                // a zero, en silence, les reglages sans controle dans l'UI (nom de
                // l'ecole, matricule...) sans aucun moyen de les reactiver.
                $renderedCheckboxes = array_filter(
                    $checkboxFields,
                    fn (string $field) => $request->exists($field) || $request->exists($field.'_present')
                );
                $sweep = $renderedCheckboxes !== [] ? $renderedCheckboxes : $checkboxFields;
                foreach ($sweep as $field) {
                    $bulletinSettings[$field] = $request->boolean($field) ? '1' : '0';
                }
                foreach (array_diff($checkboxFields, $sweep) as $field) {
                    unset($bulletinSettings[$field]);
                }
            } else {
                foreach ($submittedCheckboxFields as $field) {
                    $bulletinSettings[$field] = $request->boolean($field) ? '1' : '0';
                }
                foreach (array_diff($checkboxFields, $submittedCheckboxFields) as $field) {
                    unset($bulletinSettings[$field]);
                }
            }

            if (array_key_exists('bulletin_show_signatures', $bulletinSettings)) {
                $bulletinSettings['bulletin_show_signature'] = $bulletinSettings['bulletin_show_signatures'];
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

            $this->bulletinService->forgetPDFConfigCache();
            $savedSettings = $this->bulletinService->getPDFConfig();
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Configuration sauvegardée avec succès.',
                    'settings' => $savedSettings,
                ]);
            }

            return redirect()->back()->with('success', 'Configuration sauvegardée avec succès.');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la sauvegarde de la configuration: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la sauvegarde de la configuration: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la sauvegarde de la configuration: '.$e->getMessage());
        }
    }

    private function buildCoefficientIssueContext(array $context, Request $request): array
    {
        $classeId = $context['classe']['id'] ?? $request->input('classe_id');
        $matiereId = $context['matiere']['id'] ?? null;
        $etudiantId = $request->input('etudiant_id') ?? $request->input('bulletin');
        $anneeId = $request->input('annee_universitaire_id');
        $periode = $this->bulletinService->normalizePeriode((string) $request->input('periode', 'semestre1'));

        $resultatsConfigUrl = null;
        if ($etudiantId && $classeId && $anneeId) {
            $resultatsConfigUrl = route('esbtp.resultats.etudiant', ['etudiant' => $etudiantId]).'?'.http_build_query([
                'classe_id' => $classeId,
                'periode' => $periode,
                'annee_universitaire_id' => $anneeId,
                'open_coeff_modal' => 1,
            ]);
        }

        $context['config_url'] = $context['config_url']
            ?? $resultatsConfigUrl
            ?? route('esbtp.evaluations.index', ['open_coefficients' => 1]);

        if ($classeId) {
            $context['classe_matieres_url'] = $context['classe_matieres_url']
                ?? (Route::has('classes.matieres')
                    ? route('esbtp.classes.matieres', ['classe' => $classeId])
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
            'web_preview' => route('esbtp.resultats.etudiant.preview', ['etudiant' => $params['bulletin']]).'?'.http_build_query([
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

    private function getOfficialBulletinTemplateDefaults(ESBTPBulletin $bulletin, bool $persist): array
    {
        try {
            return $persist
                ? $this->bulletinService->genererDonneesBulletin(
                    $bulletin->etudiant_id,
                    $bulletin->classe_id,
                    $bulletin->annee_universitaire_id,
                    $bulletin->periode
                )
                : $this->bulletinService->genererDonneesBulletinPreview(
                    $bulletin->etudiant_id,
                    $bulletin->classe_id,
                    $bulletin->annee_universitaire_id,
                    $bulletin->periode
                );
        } catch (BulletinConfigurationException $e) {
            // Bulletin non configuré : ne PAS avaler l'exception. Sans les ~25 variables
            // du template (note_assiduite, pdfSettings...), le rendu planterait sur
            // « Undefined variable $note_assiduite ». On la laisse remonter pour que le
            // caller (genererPDF) affiche un message clair « configurez d'abord ce bulletin ».
            throw $e;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Convertit une image en JPEG base64 compatible DomPDF.
     * DomPDF ne supporte pas les PNG indexés (palette 8-bit) ni la transparence PNG.
     * Cette méthode utilise GD pour convertir l'image en truecolor JPEG.
     */
}
