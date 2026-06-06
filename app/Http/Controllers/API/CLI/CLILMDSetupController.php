<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPUniteEnseignement;
use App\Console\Commands\LMDImportEnseignantsCommand;
use App\Services\LMD\LMDCleanupService;
use App\Services\LMD\LMDEnseignantsImporter;
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

    /**
     * POST /api/cli/lmd/cleanup
     *
     * Soft-delete les UE / ECUE / planifications d'un ou plusieurs parcours LMD
     * pour permettre un ré-import de maquette propre (sans codes en doublon).
     * Idempotent. Les UE dont une ECUE porte déjà des évaluations sont protégées.
     *
     * Body : { "parcours": ["TIR","BU"], "dry_run": true }
     * Défaut SAFE : dry_run = true (aucun commit DB).
     *
     * Permission token : cli:admin.
     */
    public function cleanup(Request $request, LMDCleanupService $service): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'parcours' => 'required|array|min:1',
            'parcours.*' => 'required|string|max:50',
            'dry_run' => 'sometimes|boolean',
        ]);

        $dryRun = $request->boolean('dry_run', true);
        $result = $service->cleanupParcours($validated['parcours'], $dryRun);
        $t = $result['totals'];

        $message = $dryRun
            ? sprintf(
                'Dry-run : %d UE / %d ECUE / %d planif supprimables, %d UE protégée(s) par des évaluations (aucun commit DB)',
                $t['ues'], $t['ecues'], $t['planifs'], $t['ues_blocked']
            )
            : sprintf(
                'Nettoyage terminé : %d UE, %d ECUE, %d planif supprimés ; %d UE protégée(s)',
                $t['ues'], $t['ecues'], $t['planifs'], $t['ues_blocked']
            );

        return $this->successResponse($result, $message);
    }

    /**
     * POST /api/cli/lmd/import-enseignants
     *
     * Bulk-import enseignants UEMOA (assignation Users + planifications) depuis
     * les JSONs présents dans database/seeds-data/lmd-enseignants/.
     *
     * Body :
     *   {
     *     "filiere": "droit|lettres-modernes|svt|economie|all",
     *     "dry_run": true,                  // défaut true (sécurité)
     *     "include_inferred": false         // défaut false
     *   }
     *
     * Permission token : cli:admin (équivalent à `lmd.planning.edit` web).
     *
     * Retour : stats agrégées par fichier traité + total cumulé. Warnings
     * inclus (premiers 50 max) pour debug + audit MdP temporaires.
     */
    public function importEnseignants(Request $request, LMDEnseignantsImporter $importer = null): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'filiere' => 'required|string|in:droit,lettres-modernes,svt,economie,all',
            'dry_run' => 'sometimes|boolean',
            'include_inferred' => 'sometimes|boolean',
        ]);

        $dryRun = $request->boolean('dry_run', true); // défaut SAFE : dry-run
        $includeInferred = $request->boolean('include_inferred', false);

        // Résolution des fichiers via la source unique de vérité (Command)
        $filiere = $validated['filiere'];
        $files = $filiere === 'all'
            ? array_values(LMDImportEnseignantsCommand::FILIERE_FILES)
            : [LMDImportEnseignantsCommand::FILIERE_FILES[$filiere] ?? null];
        $files = array_filter($files);

        if (empty($files)) {
            return $this->errorResponse("Filière invalide : {$filiere}", [], 422);
        }

        // Le service est instancié manuellement car il prend des args constructeur
        // (DI auto ne fonctionne pas avec readonly bool dryRun par tenant).
        $service = new LMDEnseignantsImporter($dryRun, $includeInferred);

        $perFile = [];
        $totalStats = [
            'users_created' => 0,
            'users_matched' => 0,
            'ecues_assigned' => 0,
            'ecues_not_found' => 0,
            'ues_assigned_responsable' => 0,
            'ues_not_found' => 0,
            'warnings' => [],
        ];

        foreach ($files as $file) {
            $path = database_path("seeds-data/lmd-enseignants/{$file}");
            if (!is_file($path)) {
                return $this->errorResponse("JSON introuvable : {$file}", ['path' => $path], 500);
            }

            try {
                $service->resetStats();
                $stats = $service->importFile($path);
            } catch (\Throwable $e) {
                Log::error('CLI lmd:import-enseignants failed', [
                    'file' => $file,
                    'exception' => $e->getMessage(),
                ]);
                return $this->errorResponse("Erreur import {$file} : " . $e->getMessage(), [], 500);
            }

            $perFile[$file] = $stats;
            foreach (['users_created', 'users_matched', 'ecues_assigned', 'ecues_not_found', 'ues_assigned_responsable', 'ues_not_found'] as $k) {
                $totalStats[$k] += $stats[$k];
            }
            $totalStats['warnings'] = array_merge($totalStats['warnings'], $stats['warnings']);
        }

        $message = $dryRun
            ? "Dry-run : {$totalStats['users_created']} users créables, {$totalStats['ecues_assigned']} ECUEs assignables (aucun commit DB)"
            : "Import terminé : {$totalStats['users_created']} users créés, {$totalStats['ecues_assigned']} ECUEs assignés";

        // Limite warnings à 50 pour ne pas faire exploser le payload (les MdP
        // temporaires complets restent en logs côté serveur).
        $totalStats['warnings_truncated'] = count($totalStats['warnings']) > 50;
        $totalStats['warnings'] = array_slice($totalStats['warnings'], 0, 50);

        return $this->successResponse([
            'dry_run' => $dryRun,
            'include_inferred' => $includeInferred,
            'filiere' => $filiere,
            'per_file' => $perFile,
            'total' => $totalStats,
        ], $message);
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
