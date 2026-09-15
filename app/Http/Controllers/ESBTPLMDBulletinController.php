<?php

namespace App\Http\Controllers;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Services\BulletinGenerationReadinessService;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Services\DocumentPrintGuard;
use App\Services\LMD\Exceptions\MaquetteSansCompositionException;
use App\Services\LMD\LmdCreditWalletService;
use App\Services\LMDBulletinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ESBTPLMDBulletinController extends Controller
{
    protected LMDBulletinService $service;

    public function __construct(
        LMDBulletinService $service,
        private readonly BulletinGenerationReadinessService $bulletinReadiness,
        private readonly LmdCreditWalletService $creditWallet,
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:module.lmd.access');
        $this->service = $service;
    }

    /**
     * Liste des bulletins LMD avec filtres.
     */
    public function index(Request $request)
    {
        $query = ESBTPLMDBulletin::with(['etudiant', 'classe', 'anneeUniversitaire']);

        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        if ($request->filled('annee_universitaire_id')) {
            $query->where('annee_universitaire_id', $request->annee_universitaire_id);
        }

        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('etudiant', function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('prenoms', 'like', "%{$search}%");
            });
        }

        $bulletins = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->orderBy('name')
            ->get();

        $annees = ESBTPAnneeUniversitaire::orderByDesc('annee_debut')->get();

        return view('esbtp.lmd.bulletins.index', compact('bulletins', 'classes', 'annees'));
    }

    /**
     * Formulaire de selection classe + semestre pour generation.
     */
    public function select()
    {
        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->orderBy('name')
            ->get();

        $annees = ESBTPAnneeUniversitaire::orderByDesc('annee_debut')->get();

        return view('esbtp.lmd.bulletins.select', compact('classes', 'annees'));
    }

    /**
     * Generer le bulletin pour un etudiant individuel.
     */
    public function generer(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
            'incomplete_reason' => 'nullable|string|max:1000',
        ]);

        // Vérifier que le semestre correspond au niveau de la classe
        $classe = ESBTPClasse::findOrFail($request->classe_id);
        $semestresAutorises = $classe->getSemestresLMD();
        if (! in_array((int) $request->semestre, $semestresAutorises)) {
            return redirect()->back()->with('error',
                "Le semestre S{$request->semestre} ne correspond pas au niveau {$classe->niveau->name}. Semestres autorisés : S".implode(', S', $semestresAutorises).'.'
            );
        }

        if (! $this->isStudentInGenerationCohort(
            (int) $request->etudiant_id,
            (int) $request->classe_id,
            (int) $request->annee_universitaire_id
        )) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cet étudiant ne possède pas une inscription active valide pour cette classe et cette année universitaire.');
        }

        try {
            $this->assertLmdBulletinReady(
                (int) $request->etudiant_id,
                (int) $request->classe_id,
                (int) $request->annee_universitaire_id,
                (int) $request->semestre,
                $request
            );
        } catch (AcademicPilotageException $exception) {
            return $this->academicPilotageFailure($request, $exception);
        }

        try {
            $bulletin = $this->service->genererBulletinLMD(
                $request->etudiant_id,
                $request->classe_id,
                $request->annee_universitaire_id,
                $request->semestre
            );
        } catch (MaquetteSansCompositionException $exception) {
            // Le refus doit se voir. Rendre le bulletin tel quel afficherait
            // « généré avec succès » sur un calcul qui n'a rien recalculé.
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('esbtp.lmd.bulletins.show', $bulletin)
            ->with('success', 'Bulletin LMD généré avec succès.');
    }

    /**
     * Le premier etudiant de la cohorte dont le bulletin n'est pas pret, s'il y en a un.
     *
     * On s'arrete au premier : les donnees academiques manquantes sont presque
     * toujours les memes pour toute la classe, et enumerer vingt-cinq fois le
     * meme motif noierait celui qui compte.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $studentIds
     */
    private function premierBlocageDeLaCohorte(\Illuminate\Support\Collection $studentIds, Request $request)
    {
        foreach ($studentIds as $studentId) {
            try {
                $this->assertLmdBulletinReady(
                    (int) $studentId,
                    (int) $request->classe_id,
                    (int) $request->annee_universitaire_id,
                    (int) $request->semestre,
                    $request
                );
            } catch (AcademicPilotageException $exception) {
                return $this->academicPilotageFailure(
                    $request,
                    $exception,
                    "Bulletin LMD bloqué pour l'étudiant {$studentId} : {$exception->getMessage()}",
                );
            }
        }

        return null;
    }

    /**
     * Generer les bulletins pour toute une classe.
     */
    public function genererClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
            'incomplete_reason' => 'nullable|string|max:1000',
        ]);

        // Vérifier que le semestre correspond au niveau de la classe
        $classe = ESBTPClasse::findOrFail($request->classe_id);
        $semestresAutorises = $classe->getSemestresLMD();
        if (! in_array((int) $request->semestre, $semestresAutorises)) {
            return redirect()->back()->with('error',
                "Le semestre S{$request->semestre} ne correspond pas au niveau {$classe->niveau->name}. Semestres autorisés : S".implode(', S', $semestresAutorises).'.'
            );
        }

        $studentIds = $this->service->studentIdsForGenerationCohort(
            (int) $request->classe_id,
            (int) $request->annee_universitaire_id
        );

        if ($studentIds->isEmpty()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Aucun étudiant actif trouvé pour cette classe et cette année universitaire.');
        }

        if ($refus = $this->premierBlocageDeLaCohorte($studentIds, $request)) {
            return $refus;
        }

        try {
            $bulletins = $this->service->genererBulletinsClasse(
                $request->classe_id,
                $request->annee_universitaire_id,
                $request->semestre
            );
        } catch (MaquetteSansCompositionException $exception) {
            // La maquette est une propriété de la classe, pas de l'étudiant :
            // le refus vaut pour les vingt-cinq d'un coup. L'annoncer une fois
            // vaut mieux que vingt-cinq échecs comptés sans leur raison.
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        $count = count($bulletins);
        $expectedCount = $studentIds->count();

        if ($count < $expectedCount) {
            return redirect()
                ->route('esbtp.lmd.bulletins.index', [
                    'classe_id' => $request->classe_id,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'semestre' => $request->semestre,
                ])
                ->with('error', "{$count}/{$expectedCount} bulletin(s) LMD généré(s). Certains étudiants ont été bloqués pendant la génération.");
        }

        return redirect()
            ->route('esbtp.lmd.bulletins.index', [
                'classe_id' => $request->classe_id,
                'annee_universitaire_id' => $request->annee_universitaire_id,
                'semestre' => $request->semestre,
            ])
            ->with('success', "{$count} bulletin(s) LMD généré(s) avec succès.");
    }

    public function preflight(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|in:classe,etudiant',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
            'etudiant_id' => 'nullable|required_if:mode,etudiant|exists:esbtp_etudiants,id',
        ]);

        $classe = ESBTPClasse::findOrFail($validated['classe_id']);
        $semestre = (int) $validated['semestre'];
        $semestresAutorises = $classe->getSemestresLMD();
        if (! in_array($semestre, $semestresAutorises, true)) {
            $niveauName = $classe->niveau?->name ?? 'selectionne';

            return response()->json([
                'ok' => false,
                'ready' => false,
                'can_generate' => false,
                'message' => "Le semestre S{$semestre} ne correspond pas au niveau {$niveauName}.",
                'blocking_errors' => [[
                    'student_id' => null,
                    'message' => 'Semestre non autorisé pour cette classe.',
                    'issues' => [[
                        'code' => 'invalid_semester',
                        'message' => 'Semestre non autorisé pour cette classe.',
                        'severity' => 'blocking',
                    ]],
                ]],
            ], 422);
        }

        $studentIds = $validated['mode'] === 'classe'
            ? $this->service->studentIdsForGenerationCohort((int) $validated['classe_id'], (int) $validated['annee_universitaire_id'])
            : collect();

        if ($validated['mode'] === 'etudiant') {
            $etudiantId = (int) $validated['etudiant_id'];
            if (! $this->isStudentInGenerationCohort(
                $etudiantId,
                (int) $validated['classe_id'],
                (int) $validated['annee_universitaire_id']
            )) {
                $student = ESBTPEtudiant::select('id', 'matricule', 'nom', 'prenoms')->find($etudiantId);

                return response()->json([
                    'ok' => false,
                    'ready' => false,
                    'can_generate' => false,
                    'students_count' => 1,
                    'ready_count' => 0,
                    'blocked_count' => 1,
                    'message' => 'Cet étudiant ne possède pas une inscription active valide pour cette classe et cette année universitaire.',
                    'blocking_errors' => [[
                        'student_id' => $etudiantId,
                        'student_name' => $this->studentDisplayName($student, $etudiantId),
                        'student_matricule' => $student?->matricule,
                        'message' => 'Inscription active introuvable pour cette cohorte.',
                        'issues' => [[
                            'code' => 'missing_active_registration',
                            'message' => 'Inscription active introuvable pour cette cohorte.',
                            'severity' => 'blocking',
                        ]],
                    ]],
                ], 422);
            }

            $studentIds = collect([$etudiantId]);
        }

        $hasOverride = $request->user()?->can('bulletins.generate_incomplete') ?? false;
        $students = ESBTPEtudiant::select('id', 'matricule', 'nom', 'prenoms')
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');
        $blocking = [];
        $ready = 0;

        foreach ($studentIds as $studentId) {
            $inspection = $this->bulletinReadiness->inspect(
                'LMD',
                (int) $studentId,
                (int) $validated['classe_id'],
                (int) $validated['annee_universitaire_id'],
                'semestre'.$semestre
            );

            if ($inspection->ready) {
                $ready++;
                continue;
            }

            $student = $students->get((int) $studentId);

            $blocking[] = [
                'student_id' => (int) $studentId,
                'student_name' => $this->studentDisplayName($student, (int) $studentId),
                'student_matricule' => $student?->matricule,
                'message' => 'Dossier académique incomplet pour cet étudiant.',
                'requires_incomplete_reason' => $hasOverride,
                'issues' => $inspection->blockingIssues,
                'warnings' => $inspection->warnings,
                'coverage_pct' => $inspection->coveragePct,
                'evidence' => $inspection->evidence,
            ];
        }

        $total = $studentIds->count();
        $canGenerate = $total > 0 && ($blocking === [] || $hasOverride);

        return response()->json([
            'ok' => true,
            'ready' => $total > 0 && $blocking === [],
            'can_generate' => $canGenerate,
            'requires_incomplete_reason' => $blocking !== [] && $hasOverride,
            'students_count' => $total,
            'ready_count' => $ready,
            'blocked_count' => count($blocking),
            'blocking_errors' => $blocking,
            'message' => $this->preflightMessage($total, $ready, $blocking, $hasOverride),
        ]);
    }

    /**
     * Apercu du bulletin (preview HTML).
     */
    private function assertLmdBulletinReady(
        int $studentId,
        int $classId,
        int $academicYearId,
        int $semester,
        Request $request,
    ): void {
        $this->bulletinReadiness->assertReady(
            'LMD',
            $studentId,
            $classId,
            $academicYearId,
            'semestre'.$semester,
            $request->user(),
            $request->input('incomplete_reason'),
        );
    }

    private function academicPilotageFailure(
        Request $request,
        AcademicPilotageException $exception,
        ?string $message = null,
    ) {
        if ($request->expectsJson() || $request->ajax()) {
            return $exception->render($request);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $message ?: $exception->getMessage());
    }

    private function preflightMessage(int $total, int $ready, array $blocking, bool $hasOverride): string
    {
        if ($total === 0) {
            return 'Aucun étudiant actif trouvé pour cette classe et cette année universitaire.';
        }

        if ($blocking === []) {
            return "{$ready}/{$total} dossier(s) prêt(s). La génération peut démarrer.";
        }

        if ($hasOverride) {
            return count($blocking)." dossier(s) incomplet(s) ou non vérifié(s). Vérifiez les fiches dans Notes LMD. Si vous assumez un bulletin provisoire, renseignez un motif explicite pour continuer.";
        }

        return count($blocking)." dossier(s) incomplet(s) ou non vérifié(s). Ouvrez Notes LMD pour synchroniser, compléter et valider les fiches de notes avant génération.";
    }

    private function isStudentInGenerationCohort(int $studentId, int $classId, int $academicYearId): bool
    {
        return $this->service
            ->studentIdsForGenerationCohort($classId, $academicYearId)
            ->contains($studentId);
    }

    private function studentDisplayName(?ESBTPEtudiant $student, int $fallbackId): string
    {
        if (! $student) {
            return "Étudiant #{$fallbackId}";
        }

        $name = trim((string) $student->nom_complet);

        return $name !== '' ? $name : "Étudiant #{$fallbackId}";
    }

    public function show(ESBTPLMDBulletin $bulletin)
    {
        $data = $this->service->preparerDonneesBulletin($bulletin);

        return view('esbtp.lmd.bulletins.preview', $data);
    }

    /**
     * Télécharge le bulletin en PDF (Content-Disposition: attachment).
     */
    public function pdf(ESBTPLMDBulletin $bulletin)
    {
        app(DocumentPrintGuard::class)->assertPrintable(
            auth()->user(),
            'bulletin',
            (int) $bulletin->etudiant_id,
            (int) $bulletin->id
        );

        [$pdf, $filename] = $this->buildBulletinPdf($bulletin);

        return $pdf->download($filename);
    }

    /**
     * Aperçu PDF inline (Content-Disposition: inline) — ouvre dans une
     * nouvelle tab pour vérifier avant téléchargement.
     */
    public function pdfPreview(ESBTPLMDBulletin $bulletin)
    {
        app(DocumentPrintGuard::class)->assertPrintable(
            auth()->user(),
            'bulletin',
            (int) $bulletin->etudiant_id,
            (int) $bulletin->id
        );

        [$pdf, $filename] = $this->buildBulletinPdf($bulletin);

        return $pdf->stream($filename);
    }

    /**
     * Construction unifiée du PDF bulletin LMD. Retourne [PDF, filename].
     */
    private function buildBulletinPdf(ESBTPLMDBulletin $bulletin): array
    {
        $data = $this->service->preparerDonneesBulletin($bulletin);

        // Etablissement depuis SettingsHelper (methode centralisee)
        $schoolInfo = SettingsHelper::getSchoolInfo();
        $data['etablissement'] = [
            'nom' => $schoolInfo['name'],
            'adresse' => $schoolInfo['address'],
            'telephone' => $schoolInfo['phone'],
            'email' => $schoolInfo['email'],
            'logo' => $schoolInfo['logo'],
            'ville' => $schoolInfo['city'] ?: 'Abidjan',
            'directeur' => $schoolInfo['director_name'],
        ];

        // Couleurs PDF depuis SettingsHelper
        $data['pdfCfg'] = SettingsHelper::getPdfSettings();

        // Settings bulletin LMD (clés propres lmd_bulletin_*)
        $data['bulletinCfg'] = [
            'show_republic_info' => SettingsHelper::get('lmd_bulletin_show_republic_info', '1') == '1',
            'show_ministry_info' => SettingsHelper::get('lmd_bulletin_show_ministry_info', '1') == '1',
            'republic_text' => SettingsHelper::get('lmd_bulletin_republic_text', 'REPUBLIQUE DE COTE D\'IVOIRE'),
            'union_text' => SettingsHelper::get('lmd_bulletin_union_text', 'Union - Discipline - Travail'),
            'ministry_text' => SettingsHelper::get('lmd_bulletin_ministry_text', 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE'),
            'show_etablissement_box' => SettingsHelper::get('lmd_bulletin_show_etablissement_box', '1') == '1',
            'code_etablissement' => SettingsHelper::get('lmd_bulletin_code_etablissement', ''),
            'statut' => SettingsHelper::get('lmd_bulletin_statut', 'Privé'),
            'direction' => SettingsHelper::get('lmd_bulletin_direction', ''),
            'notice_text' => SettingsHelper::get('lmd_bulletin_notice_text', LMDBulletinService::NOTICE_DEFAUT),
            'bottom_text' => SettingsHelper::get('lmd_bulletin_bottom_text', 'Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.'),
        ];

        // Logo base64 pour DomPDF
        $data['logoBase64'] = $this->prepareLogoBase64($data['etablissement']['logo']);

        $pdf = Pdf::loadView('esbtp.lmd.bulletins.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
            ]);

        $matricule = $bulletin->etudiant->matricule ?? 'unknown';
        $semestre = $bulletin->semestre;
        $filename = "bulletin_lmd_{$matricule}_S{$semestre}.pdf";

        return [$pdf, $filename];
    }

    private function prepareLogoBase64(?string $logoPath): ?string
    {
        $paths = [
            $logoPath ? storage_path('app/public/'.$logoPath) : null,
            // Repli generique uniquement : jamais le logo d'un etablissement.
            public_path('images/LOGO-KLASSCI-PNG.png'),
        ];

        foreach (array_filter($paths) as $path) {
            if (file_exists($path)) {
                $mime = mime_content_type($path);
                $data = base64_encode(file_get_contents($path));

                return "data:{$mime};base64,{$data}";
            }
        }

        return null;
    }

    /**
     * Basculer la publication du bulletin.
     */
    public function togglePublication(ESBTPLMDBulletin $bulletin)
    {
        $bulletin->update([
            'is_published' => ! $bulletin->is_published,
        ]);
        $this->creditWallet->syncBulletin($bulletin->fresh());

        $status = $bulletin->is_published ? 'publié' : 'dépublié';

        return redirect()->back()->with('success', "Bulletin {$status} avec succès.");
    }

    /**
     * Supprimer un bulletin (soft delete) avec ses resultats.
     */
    public function destroy(ESBTPLMDBulletin $bulletin)
    {
        // Supprimer les resultats ECUEs lies
        $bulletin->resultatsECUEs()->delete();

        // Supprimer les resultats UEs lies
        $bulletin->resultatsUEs()->delete();

        // Supprimer le bulletin
        $bulletin->delete();

        return redirect()
            ->route('esbtp.lmd.bulletins.index')
            ->with('success', 'Bulletin LMD supprimé avec succès.');
    }
}
