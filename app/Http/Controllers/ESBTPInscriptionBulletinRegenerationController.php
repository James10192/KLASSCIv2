<?php

namespace App\Http\Controllers;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Domain\AcademicPilotage\Services\BulletinGenerationReadinessService;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Services\ESBTP\BtsStudentAcademicRecords;
use App\Services\ESBTP\BulletinConsistencyService;
use App\Services\LMD\Exceptions\MaquetteSansCompositionException;
use App\Services\LMDBulletinService;
use Illuminate\Http\Request;

/** Entrée contextualisée par inscription ; tous les calculs restent dans les moteurs officiels. */
class ESBTPInscriptionBulletinRegenerationController extends Controller
{
    public function __construct(
        private readonly BtsStudentAcademicRecords $records,
        private readonly BulletinGenerationReadinessService $readiness,
        private readonly BulletinConsistencyService $consistency,
        private readonly BtsBulkBulletinGenerationService $btsBulk,
        private readonly LMDBulletinService $lmdBulletins,
    ) {}

    public function __invoke(Request $request, ESBTPInscription $inscription)
    {
        $data = $request->validate([
            'periode' => ['required', 'string', 'max:20'],
            'incomplete_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $inscription->loadMissing('classe');
        abort_unless($inscription->etudiant_id && $inscription->annee_universitaire_id && $inscription->classe, 422);

        $lmd = $inscription->classe->systeme_academique === 'LMD';
        abort_unless($request->user()?->can('students.view'), 403);
        abort_unless($lmd
            ? ($request->user()->can('module.lmd.access') && $request->user()->can('lmd.bulletins.generate'))
            : ($request->user()->can('bulletins.edit') || $request->user()->can('bulletins.generate')), 403);

        $periode = (string) $data['periode'];
        $etudiantId = (int) $inscription->etudiant_id;
        $anneeId = (int) $inscription->annee_universitaire_id;

        if ($lmd) {
            $semestres = $inscription->classe->getSemestresLMD();
            abort_unless(ctype_digit($periode) && in_array((int) $periode, $semestres, true), 422);
            $classeId = (int) $inscription->classe_id;
            $semestre = (int) $periode;
            $existing = ESBTPLMDBulletin::query()
                ->where('etudiant_id', $etudiantId)->where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeId)->where('semestre', $semestre)->first();
            if ($existing?->is_published) {
                return response()->json(['ok' => false, 'message' => 'Ce bulletin LMD est publié et verrouillé.'], 409);
            }
            if (! $existing && ($inscription->status !== 'active' || $inscription->workflow_step !== 'etudiant_cree')) {
                return response()->json(['ok' => false, 'message' => 'La première génération exige une inscription active et validée.'], 422);
            }

            try {
                $this->readiness->assertReady('LMD', $etudiantId, $classeId, $anneeId, 'semestre'.$semestre, $request->user(), $data['incomplete_reason'] ?? null);
                $bulletin = $this->lmdBulletins->genererBulletinLMD($etudiantId, $classeId, $anneeId, $semestre);
            } catch (AcademicPilotageException $e) {
                return $e->render($request);
            } catch (MaquetteSansCompositionException $e) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            } catch (\RuntimeException $e) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 409);
            }

            return response()->json([
                'ok' => true,
                'message' => $existing ? 'Bulletin LMD recalculé.' : 'Bulletin LMD généré.',
                'bulletin_url' => $request->user()->can('lmd.bulletins.view')
                    ? route('esbtp.lmd.bulletins.show', $bulletin) : null,
            ]);
        }

        abort_unless(in_array($periode, ['semestre1', 'semestre2'], true), 422);
        $map = $this->records->classMap($inscription);
        $classeId = (int) $map[$periode.'_classe_id'];
        $aliases = $periode === 'semestre1' ? ['1', 'semestre1'] : ['2', 'semestre2'];
        $existing = ESBTPBulletin::query()
            ->where('etudiant_id', $etudiantId)->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)->whereIn('periode', $aliases)->first();
        abort_unless($request->user()->can($existing ? 'bulletins.edit' : 'bulletins.generate'), 403);

        try {
            if ($existing) {
                $this->readiness->assertReady('BTS', $etudiantId, $classeId, $anneeId, $periode, $request->user(), $data['incomplete_reason'] ?? null);
                $this->consistency->regenerateOfficialBulletin($etudiantId, $classeId, $anneeId, $periode);
                $message = 'Bulletin BTS recalculé.';
            } else {
                $result = $this->btsBulk->generate($inscription->classe->newQuery()->findOrFail($classeId), $anneeId, $periode, $request->user(), false, $data['incomplete_reason'] ?? null, [$etudiantId]);
                if (! $result->hasWrites()) {
                    return response()->json(['ok' => false, 'message' => $result->message(), 'blocking_errors' => $result->blockingErrors, 'errors' => $result->errors], 422);
                }
                $message = 'Bulletin BTS généré.';
            }
        } catch (AcademicPilotageException $e) {
            return $e->render($request);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'message' => $message]);
    }
}
