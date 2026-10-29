<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryMembre;
use App\Services\JuryDeliberationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CLILMDJuryE2EController extends BaseApiController
{
    public function prepare(Request $request, JuryDeliberationService $deliberation): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        if ($guard = $this->rejectUnsafeWriteTarget($request)) {
            return $guard;
        }

        $data = $request->validate([
            'confirm' => ['required', 'in:presentation-lmd-jury-e2e'],
            'classe_id' => ['nullable', 'integer', 'exists:esbtp_classes,id'],
            'semestre' => ['nullable', 'integer', 'between:1,10'],
            'students_limit' => ['nullable', 'integer', 'between:1,12'],
            'force_new' => ['nullable', 'boolean'],
            'apply_decisions' => ['nullable', 'boolean'],
        ]);

        try {
            $result = DB::transaction(function () use ($request, $data, $deliberation) {
                $actorId = $request->user()->id;
                $classe = $this->resolveClasse($data['classe_id'] ?? null);
                $annee = $this->resolveAnnee($classe);
                $semestre = (int) ($data['semestre'] ?? ($classe->getSemestresLMD()[0] ?? 1));
                $students = $this->studentsForClasse($classe, $annee, (int) ($data['students_limit'] ?? 3));

                if ($students->isEmpty()) {
                    throw new \RuntimeException('Aucun étudiant actif disponible pour préparer le jury LMD E2E.');
                }

                $this->ensureValidatedGradeSheet($classe, $annee, $semestre, $actorId);
                $this->ensureBulletins($students, $classe, $annee, $semestre, $actorId);
                $jury = $this->ensureJury($classe, $annee, $semestre, $actorId, (bool) ($data['force_new'] ?? false));
                $this->ensureSignedMembers($jury, $actorId);
                $created = (bool) ($data['apply_decisions'] ?? false)
                    ? $deliberation->appliquerDecisionsAuto($jury->fresh())
                    : 0;
                $readiness = $deliberation->verifierReadiness($jury->fresh());

                return [
                    'jury' => $jury->fresh(),
                    'created_decisions' => $created,
                    'readiness' => $readiness,
                    'students_count' => $students->count(),
                ];
            });
        } catch (\Throwable $exception) {
            report($exception);
            return $this->errorResponse('Préparation du jury LMD E2E impossible : '.$exception->getMessage(), [], 422);
        }

        return $this->successResponse($this->formatStatusPayload($result['jury'], $deliberation, [
            'created_decisions' => $result['created_decisions'],
            'students_count' => $result['students_count'],
            'readiness' => $result['readiness'],
        ]), 'Jury LMD E2E préparé.');
    }

    public function status(Request $request, ESBTPLMDJury $jury, JuryDeliberationService $deliberation): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read') && ! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        return $this->successResponse($this->formatStatusPayload($jury, $deliberation), 'Statut du jury LMD E2E récupéré.');
    }

    private function rejectUnsafeWriteTarget(Request $request): ?JsonResponse
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        $host = $request->getHost();
        $appUrl = (string) config('app.url');
        if (Str::contains($host, 'presentation.klassci.com') || Str::contains($appUrl, 'presentation.klassci.com')) {
            return null;
        }

        return $this->errorResponse('Préparation E2E autorisée uniquement sur presentation.klassci.com.', [], 403);
    }

    private function resolveClasse(?int $classeId): ESBTPClasse
    {
        if ($classeId) {
            return ESBTPClasse::query()->lmd()->whereKey($classeId)->firstOrFail();
        }

        $classIds = DB::table('esbtp_inscriptions')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->whereNotNull('classe_id')
            ->distinct()
            ->pluck('classe_id');

        return ESBTPClasse::query()
            ->lmd()
            ->whereNotNull('parcours_id')
            ->whereIn('id', $classIds)
            ->orderBy('id')
            ->firstOrFail();
    }

    private function resolveAnnee(ESBTPClasse $classe): ESBTPAnneeUniversitaire
    {
        if ($classe->annee_universitaire_id) {
            return ESBTPAnneeUniversitaire::query()->findOrFail($classe->annee_universitaire_id);
        }

        return ESBTPAnneeUniversitaire::query()->where('is_current', true)->firstOrFail();
    }

    private function studentsForClasse(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, int $limit)
    {
        $ids = DB::table('esbtp_inscriptions')
            ->whereNull('deleted_at')
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->orderBy('etudiant_id')
            ->limit($limit)
            ->pluck('etudiant_id');

        return ESBTPEtudiant::query()->whereIn('id', $ids)->orderBy('id')->get();
    }

    private function ensureValidatedGradeSheet(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, int $semestre, int $actorId): void
    {
        $matiereId = DB::table('esbtp_matieres')->whereNull('deleted_at')->orderBy('id')->value('id');
        if (! $matiereId) {
            throw new \RuntimeException('Aucune matière disponible pour préparer la feuille LMD E2E.');
        }

        $key = "lmd-e2e-jury-{$classe->id}-{$annee->id}-s{$semestre}";
        GradeSheet::query()->updateOrCreate(
            ['obligation_key' => $key],
            [
                'code' => Str::upper(Str::limit($key, 40, '')),
                'classe_id' => $classe->id,
                'matiere_id' => $matiereId,
                'annee_universitaire_id' => $annee->id,
                'academic_system' => 'LMD',
                'semester' => (string) $semestre,
                'evaluation_type' => 'e2e_lmd_jury',
                'entry_mode' => 'direct',
                'expected_at' => now(),
                'source' => 'cli',
                'observations' => 'Feuille validée pour test E2E LMD 360 sur présentation.',
                'metadata' => ['e2e' => 'lmd-360', 'prepared_at' => now()->toIso8601String()],
            ]
        )->forceFill([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => $actorId,
            'updated_by' => $actorId,
        ])->save();
    }

    private function ensureBulletins($students, ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, int $semestre, int $actorId): void
    {
        foreach ($students->values() as $index => $student) {
            ESBTPLMDBulletin::query()->updateOrCreate(
                [
                    'etudiant_id' => $student->id,
                    'classe_id' => $classe->id,
                    'annee_universitaire_id' => $annee->id,
                    'semestre' => $semestre,
                ],
                [
                    'parcours_id' => $classe->parcours_id,
                    'niveau' => optional($classe->niveau)->name,
                    'parcours_label' => optional($classe->parcours)->name,
                    'moyenne_generale' => 14.5 - ($index * 1.2),
                    'credits_capitalises' => $index === 2 ? 24 : 30,
                    'credits_totaux' => 30,
                    'rang' => $index + 1,
                    'effectif' => $students->count(),
                    'appreciation' => 'Bulletin préparé pour test E2E LMD 360.',
                    'is_published' => false,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]
            );
        }
    }

    private function ensureJury(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, int $semestre, int $actorId, bool $forceNew): ESBTPLMDJury
    {
        $baseLabel = "E2E LMD 360 {$classe->name} S{$semestre}";
        $jury = $forceNew ? null : ESBTPLMDJury::query()
            ->where('libelle', $baseLabel)
            ->whereNotIn('status', ['publie', 'archive'])
            ->first();

        $jury ??= new ESBTPLMDJury(['libelle' => $forceNew ? $baseLabel.' '.now()->format('YmdHis') : $baseLabel]);
        $jury->fill([
            'annee_universitaire_id' => $annee->id,
            'parcours_id' => $classe->parcours_id,
            'classe_id' => $classe->id,
            'semestre' => $semestre,
            'date_jury' => now()->toDateString(),
            'status' => 'en_cours',
            'observations' => 'Jury préparé par le harnais E2E LMD 360.',
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ])->save();

        return $jury;
    }

    private function ensureSignedMembers(ESBTPLMDJury $jury, int $actorId): void
    {
        $userIds = DB::table('users')->orderBy('id')->limit(2)->pluck('id');
        if ($userIds->count() < 2) {
            throw new \RuntimeException('Deux utilisateurs sont requis pour le quorum E2E.');
        }

        foreach ([['president', $actorId], ['assesseur', (int) $userIds->first(fn ($id) => (int) $id !== $actorId)]] as [$role, $userId]) {
            ESBTPLMDJuryMembre::query()->updateOrCreate(
                ['jury_id' => $jury->id, 'user_id' => $userId],
                [
                    'role' => $role,
                    'present' => true,
                    'signature_data' => json_encode(['checked' => true, 'source' => 'cli-e2e']),
                    'signature_at' => now(),
                    'signature_ip' => request()->ip(),
                    'signature_user_agent' => 'klassci-cli-e2e',
                ]
            );
        }
    }

    private function formatStatusPayload(ESBTPLMDJury $jury, JuryDeliberationService $deliberation, array $extra = []): array
    {
        $fresh = $jury->fresh(['classe', 'decisions', 'membres']);
        $documents = OfficialDocument::query()
            ->forSource(ESBTPLMDJury::class, $fresh->id)
            ->where('document_type', OfficialDocument::TYPE_LMD_JURY_PV)
            ->orderBy('version')
            ->get(['id', 'reference', 'version', 'status', 'checksum_sha256', 'issued_at']);

        return array_merge([
            'jury' => [
                'id' => $fresh->id,
                'libelle' => $fresh->libelle,
                'status' => $fresh->status,
                'classe' => $fresh->classe?->name,
                'semestre' => $fresh->semestre,
                'pv_numero' => $fresh->pv_numero,
                'pv_genere_at' => $fresh->pv_genere_at?->toIso8601String(),
                'publie_at' => $fresh->publie_at?->toIso8601String(),
            ],
            'counts' => [
                'members' => $fresh->membres->count(),
                'signed_members' => $fresh->membres->whereNotNull('signature_at')->count(),
                'decisions' => $fresh->decisions->count(),
            ],
            'readiness' => $deliberation->verifierReadiness($fresh),
            'documents' => $documents,
            'routes' => [
                'show' => url("/esbtp/lmd/jurys/{$fresh->id}"),
                'auto_decisions' => url("/esbtp/lmd/jurys/{$fresh->id}/decisions/auto"),
                'generate_pv' => url("/esbtp/lmd/jurys/{$fresh->id}/pv/generer"),
                'publish' => url("/esbtp/lmd/jurys/{$fresh->id}/publier"),
                'rectify' => url("/esbtp/lmd/jurys/{$fresh->id}/pv/rectifier"),
                'reconcile' => url("/esbtp/lmd/jurys/{$fresh->id}/pv/reconcilier"),
            ],
        ], $extra);
    }
}
