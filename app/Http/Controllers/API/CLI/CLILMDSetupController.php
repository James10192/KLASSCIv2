<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\LMDImportService;
use App\Services\LMD\ParcoursUeSyncService;
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

    /**
     * GET /api/cli/lmd/niveaux-debug
     *
     * TEMPORARY diagnostic endpoint to investigate the "Licence 3" dropdown bug.
     * Returns raw ESBTPNiveauEtude rows incl. soft-deleted, with byte-level hex
     * dumps of the `name` field to expose any invisible/control characters.
     *
     * REMOVE THIS ENDPOINT AFTER DIAGNOSIS.
     */
    public function niveauxDebug(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $niveaux = ESBTPNiveauEtude::withTrashed()
            ->orderBy('type')
            ->orderBy('year')
            ->orderBy('id')
            ->get()
            ->map(function (ESBTPNiveauEtude $n) {
                $name = (string) $n->name;
                $libelle = (string) ($n->libelle ?? '');

                return [
                    'id' => $n->id,
                    'name' => $name,
                    'name_bytes_hex' => bin2hex($name),
                    'name_len_chars' => mb_strlen($name),
                    'name_len_bytes' => strlen($name),
                    'libelle' => $libelle,
                    'libelle_bytes_hex' => bin2hex($libelle),
                    'code' => $n->code,
                    'type' => $n->type,
                    'year' => $n->year,
                    'description' => $n->description,
                    'is_active' => (bool) $n->is_active,
                    'deleted_at' => $n->deleted_at?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                    'updated_at' => $n->updated_at?->toIso8601String(),
                ];
            });

        return $this->successResponse([
            'niveaux' => $niveaux,
            'total' => $niveaux->count(),
        ], 'Niveaux debug retrieved');
    }

    /**
     * POST /api/cli/lmd/parcours/{parcours}/link-ues
     *
     * Body (JSON):
     *   {
     *     "ues": [
     *       {"id": 12, "semestres": [1], "is_optional": false, "ordre": 0},
     *       {"id": 13, "semestres": [1, 2], "is_optional": true}
     *     ],
     *     "mode": "append"  // optional, default "append" (CLI safe). Use "sync" to detach missing.
     *   }
     *
     * Resolves UEs by id. Idempotent (re-runs are no-ops if pivot data unchanged).
     * Default mode is APPEND for safety — automation never silently detaches existing links.
     */
    public function linkUes(Request $request, ESBTPLMDParcours $parcours, ParcoursUeSyncService $sync): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'ues' => 'required|array|min:1',
            'ues.*.id' => 'required|integer|exists:esbtp_unites_enseignement,id',
            'ues.*.semestres' => 'required|array|min:1',
            'ues.*.semestres.*' => 'integer|between:1,10',
            'ues.*.is_optional' => 'sometimes|boolean',
            'ues.*.ordre' => 'sometimes|integer|min:0|max:65535',
            'mode' => 'sometimes|in:append,sync',
        ]);

        $detachMissing = ($validated['mode'] ?? 'append') === 'sync';
        $stats = $sync->sync($parcours, $validated['ues'], $detachMissing);

        return $this->successResponse([
            'parcours' => $parcours->only(['id', 'name', 'code']),
            'mode' => $detachMissing ? 'sync' : 'append',
            'stats' => $stats,
        ], sprintf(
            '%d ajout(s), %d màj, %d suppr, %d inchangé(s)',
            $stats['attached'],
            $stats['updated'],
            $stats['detached'],
            $stats['unchanged'],
        ));
    }

    /**
     * POST /api/cli/lmd/import
     *
     * Bulk import a full LMD maquette (Domaine + Mention + Parcours + Filière + UEs +
     * ECUEs + Planifications) from a JSON spec. Idempotent — re-runs upsert silently.
     *
     * Body: see LMDImportService::import() docblock for the spec schema.
     * Returns: { domaine, mention, parcours, filiere, niveaux, stats: {ues_attached, ecues_updated, ...} }
     */
    public function import(Request $request, LMDImportService $importer): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'domaine.name' => 'required|string|max:255',
            'domaine.code' => 'nullable|string|max:50',
            'mention.name' => 'required|string|max:255',
            'mention.code' => 'nullable|string|max:50',
            'parcours.name' => 'required|string|max:255',
            'parcours.code' => 'nullable|string|max:50',
            'parcours.credits_licence' => 'nullable|integer|min:0|max:600',
            'parcours.credits_master' => 'nullable|integer|min:0|max:600',
            'filiere.name' => 'nullable|string|max:255',
            'filiere.code' => 'nullable|string|max:50',
            'niveaux' => 'required|array|min:1',
            'niveaux.*.name' => 'required|string|max:50',
            'niveaux.*.year' => 'required|integer|between:1,8',
            'ues' => 'required|array|min:1',
            'ues.*.code' => 'nullable|string|max:50',
            'ues.*.name' => 'required|string|max:255',
            'ues.*.type_ue' => 'required|string',
            'ues.*.credit' => 'required|integer|min:0|max:60',
            'ues.*.niveau_year' => 'required|integer|between:1,8',
            'ues.*.semestre' => 'required|integer|between:1,10',
            'ues.*.is_optional' => 'sometimes|boolean',
            'ues.*.ordre' => 'sometimes|integer|min:0|max:65535',
            'ues.*.ecues' => 'required|array|min:1',
            'ues.*.ecues.*.code' => 'nullable|string|max:50',
            'ues.*.ecues.*.name' => 'required|string|max:255',
            'ues.*.ecues.*.credit_ecue' => 'required|integer|min:0|max:60',
            'ues.*.ecues.*.cm' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.td' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.tp' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.projet' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.tpe' => 'sometimes|integer|min:0|max:1000',
        ]);

        $result = $importer->import($validated, $request->user()->id);

        return $this->successResponse($result, sprintf(
            'Maquette importée : %d UE (+%d/~%d), %d ECUE (+%d/~%d), %d planif (+%d/~%d)',
            $result['stats']['ues_attached'] + $result['stats']['ues_updated'],
            $result['stats']['ues_attached'],
            $result['stats']['ues_updated'],
            $result['stats']['ecues_attached'] + $result['stats']['ecues_updated'],
            $result['stats']['ecues_attached'],
            $result['stats']['ecues_updated'],
            $result['stats']['planifs_attached'] + $result['stats']['planifs_updated'],
            $result['stats']['planifs_attached'],
            $result['stats']['planifs_updated'],
        ));
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
