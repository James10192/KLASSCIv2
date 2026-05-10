<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Bulk-setup endpoints for LMD hierarchy provisioning via klassci-cli.
 *
 * Allows provisioning a new instance with Domaine + Mention + Parcours (+ optional
 * Filiere) in one transactional call. Designed for bootstrapping LMD instances
 * (e.g. ephrata, future Hetec/Rostan) without UI clicks.
 */
class CLILMDSetupController extends BaseApiController
{
    /**
     * POST /api/cli/lmd/setup
     *
     * Body (JSON):
     *   {
     *     "domaine":  {"name": "Sciences et Technologie", "code": "ST", "description": "..."},
     *     "mention":  {"name": "Agroforesterie - SVT", "code": "SVT-MENT"},
     *     "parcours": {"name": "Sciences de la Vie et de la Terre", "code": "SVT-PARC", "credits_licence": 180},
     *     "filiere":  {"name": "Sciences de la Vie et de la Terre", "code": "SVT"}  // optional
     *   }
     *
     * Cascades creation in transaction. Re-uses existing entities (matching name+code)
     * if found — idempotent for repeated calls with same payload.
     */
    public function setup(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'domaine.name' => 'required|string|max:255',
            'domaine.code' => 'nullable|string|max:50',
            'domaine.description' => 'nullable|string|max:1000',
            'mention.name' => 'required|string|max:255',
            'mention.code' => 'nullable|string|max:50',
            'parcours.name' => 'required|string|max:255',
            'parcours.code' => 'nullable|string|max:50',
            'parcours.credits_licence' => 'nullable|integer|min:30|max:360',
            'parcours.credits_master' => 'nullable|integer|min:30|max:240',
            'filiere.name' => 'nullable|string|max:255',
            'filiere.code' => 'nullable|string|max:50',
        ]);

        try {
            $result = DB::transaction(function () use ($validated) {
                $userId = optional($this->resolveActor())->id;

                $domaine = $this->upsertDomaine($validated['domaine'], $userId);
                $mention = $this->upsertMention($validated['mention'], $domaine->id, $userId);
                $filiere = isset($validated['filiere'])
                    ? $this->upsertFiliere($validated['filiere'], $userId)
                    : null;
                $parcours = $this->upsertParcours(
                    $validated['parcours'],
                    $mention->id,
                    $filiere?->id,
                    $userId
                );

                return compact('domaine', 'mention', 'filiere', 'parcours');
            });

            return $this->successResponse([
                'domaine' => $this->formatModel($result['domaine']),
                'mention' => $this->formatModel($result['mention']),
                'filiere' => $result['filiere'] ? $this->formatModel($result['filiere']) : null,
                'parcours' => $this->formatModel($result['parcours']),
            ], 'LMD hierarchy created/updated');
        } catch (\Throwable $e) {
            Log::error('CLI: lmd setup failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Setup failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/cli/lmd/tree
     *
     * Returns the full Domaine -> Mention -> Parcours hierarchy of the instance.
     */
    public function tree(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $domaines = ESBTPLMDDomaine::with(['mentions.parcours.filiere'])
            ->orderBy('name')
            ->get()
            ->map(fn (ESBTPLMDDomaine $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'mentions' => $d->mentions->map(fn (ESBTPLMDMention $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'code' => $m->code,
                    'parcours' => $m->parcours->map(fn (ESBTPLMDParcours $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'filiere' => $p->filiere?->only(['id', 'name', 'code']),
                        'credits_licence' => $p->credits_licence,
                        'credits_master' => $p->credits_master,
                    ])->values(),
                ])->values(),
            ]);

        return $this->successResponse([
            'domaines' => $domaines,
            'totals' => [
                'domaines' => $domaines->count(),
                'mentions' => $domaines->sum(fn ($d) => count($d['mentions'])),
                'parcours' => $domaines->sum(fn ($d) => collect($d['mentions'])->sum(fn ($m) => count($m['parcours']))),
            ],
        ], 'LMD hierarchy retrieved');
    }

    private function upsertDomaine(array $data, ?int $userId): ESBTPLMDDomaine
    {
        $code = $data['code'] ?? Str::upper(Str::slug($data['name'], ''));
        return ESBTPLMDDomaine::updateOrCreate(
            ['code' => $code],
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function upsertMention(array $data, int $domaineId, ?int $userId): ESBTPLMDMention
    {
        $code = $data['code'] ?? Str::upper(Str::slug($data['name'], ''));
        return ESBTPLMDMention::updateOrCreate(
            ['code' => $code, 'domaine_id' => $domaineId],
            [
                'name' => $data['name'],
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function upsertFiliere(array $data, ?int $userId): ESBTPFiliere
    {
        $code = $data['code'] ?? Str::upper(Str::slug($data['name'], ''));
        return ESBTPFiliere::updateOrCreate(
            ['code' => $code],
            [
                'name' => $data['name'],
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function upsertParcours(array $data, int $mentionId, ?int $filiereId, ?int $userId): ESBTPLMDParcours
    {
        $code = $data['code'] ?? Str::upper(Str::slug($data['name'], ''));
        return ESBTPLMDParcours::updateOrCreate(
            ['code' => $code, 'mention_id' => $mentionId],
            [
                'name' => $data['name'],
                'filiere_id' => $filiereId,
                'credits_licence' => $data['credits_licence'] ?? 180,
                'credits_master' => $data['credits_master'] ?? 120,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function resolveActor(): ?\App\Models\User
    {
        return request()->user();
    }

    private function formatModel($model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'code' => $model->code,
            'created' => $model->wasRecentlyCreated,
        ];
    }
}
