<?php

namespace App\Http\Controllers;

use App\Domain\LMD\Actions\MergeDuplicateEcue;
use App\Domain\LMD\Actions\MergeDuplicateUe;
use App\Domain\LMD\Actions\RetirerMoyennesEnCollision;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Services\LMD\DuplicateReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réconciliation des doublons UE/ECUE LMD.
 *
 * Controller mince : valide, délègue au service de détection et aux Actions de
 * fusion, retourne du JSON (AJAX no-reload). Aucune logique métier ici.
 */
class ESBTPLMDReconciliationController extends Controller
{
    public function __construct(
        private readonly DuplicateReconciliationService $detector,
    ) {
    }

    /**
     * Page premium (hero + groupes de doublons).
     */
    public function index(Request $request)
    {
        $this->authorize('lmd.reconciliation.manage');

        $mentions = ESBTPLMDMention::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $parcours = ESBTPLMDParcours::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'mention_id']);

        return view('esbtp.lmd.reconciliation.index', compact('mentions', 'parcours'));
    }

    /**
     * Détecte les groupes de doublons (UE + ECUE) selon le scope/seuil.
     */
    public function detect(Request $request): JsonResponse
    {
        $this->authorize('lmd.reconciliation.manage');

        $validated = $request->validate([
            'mention_id' => ['nullable', 'integer', 'exists:esbtp_lmd_mentions,id'],
            'parcours_id' => ['nullable', 'integer', 'exists:esbtp_lmd_parcours,id'],
            'threshold' => ['nullable', 'numeric', 'min:50', 'max:100'],
            'same_level_only' => ['nullable', 'boolean'],
        ]);

        $options = [
            'mention_id' => $validated['mention_id'] ?? null,
            'parcours_id' => $validated['parcours_id'] ?? null,
            'threshold' => $validated['threshold'] ?? DuplicateReconciliationService::DEFAULT_SIMILARITY_THRESHOLD,
            'same_level_only' => $request->boolean('same_level_only', true),
        ];

        $ueGroups = $this->detector->detectDuplicateUes($options);
        $ecueGroups = $this->detector->detectDuplicateEcues($options);

        $parcoursTouched = collect($ueGroups)
            ->flatMap(fn ($g) => collect($g['candidates'])->flatMap(fn ($c) => collect($c['parcours'] ?? [])->pluck('id')))
            ->unique()
            ->count();

        return response()->json([
            'success' => true,
            'ue_groups' => $ueGroups,
            'ecue_groups' => $ecueGroups,
            'kpis' => [
                'ue_duplicate_groups' => count($ueGroups),
                'ecue_duplicate_groups' => count($ecueGroups),
                'parcours_concerned' => $parcoursTouched,
            ],
        ]);
    }

    /**
     * Fusionne un groupe (UE ou ECUE) vers une entité canonique.
     * `dry_run=true` par défaut → aperçu d'impact sans commit.
     */
    public function merge(Request $request, MergeDuplicateUe $mergeUe, MergeDuplicateEcue $mergeEcue): JsonResponse
    {
        $this->authorize('lmd.reconciliation.manage');

        $validated = $request->validate([
            'type' => ['required', Rule::in(['ue', 'ecue'])],
            'canonical_id' => ['required', 'integer'],
            'absorbed_ids' => ['required', 'array', 'min:1'],
            'absorbed_ids.*' => ['integer', 'different:canonical_id'],
            'dry_run' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
        ]);

        $options = [
            'dry_run' => $request->boolean('dry_run', true),
            'force' => $request->boolean('force', false),
        ];

        $report = $validated['type'] === 'ue'
            ? $mergeUe->execute((int) $validated['canonical_id'], $validated['absorbed_ids'], $options)
            : $mergeEcue->execute((int) $validated['canonical_id'], $validated['absorbed_ids'], $options);

        // Seules les collisions que CETTE fusion a produites pourront être
        // réglées par retirerMoyennesEnCollision() : la liste reste côté serveur.
        $conflits = array_column($report['moyennes_enregistrees']['conflits'] ?? [], 'id');
        if ($conflits !== []) {
            $request->session()->put(self::cleDesConflits((int) $validated['canonical_id']), [
                'ids' => $conflits,
                'expire' => now()->addHours(2)->getTimestamp(),
            ]);
        }

        return response()->json($report, ($report['success'] ?? false) ? 200 : 422);
    }

    /**
     * Règle les moyennes enregistrées qu'une fusion d'ECUE a laissées en
     * collision : voir {@see RetirerMoyennesEnCollision}.
     */
    public function retirerMoyennesEnCollision(Request $request, RetirerMoyennesEnCollision $action): JsonResponse
    {
        $this->authorize('lmd.reconciliation.manage');

        $validated = $request->validate([
            'canonical_id' => ['required', 'integer'],
            'resultat_ids' => ['required', 'array', 'min:1', 'max:500'],
            'resultat_ids.*' => ['integer'],
        ]);

        $cle = self::cleDesConflits((int) $validated['canonical_id']);
        $garde = $request->session()->get($cle);
        $autorises = ($garde['expire'] ?? 0) >= now()->getTimestamp() ? ($garde['ids'] ?? []) : [];

        $rapport = $action->execute((int) $validated['canonical_id'], $validated['resultat_ids'], $autorises, optional($request->user())->id);

        // Une ligne réglée ne l'est qu'une fois ; les refusées restent réglables.
        $restantes = array_values(array_diff($autorises, $this->reglees($validated['resultat_ids'], $rapport)));
        $restantes === []
            ? $request->session()->forget($cle)
            : $request->session()->put($cle, ['ids' => $restantes] + $garde);

        return response()->json(['success' => $rapport['retirees'] > 0] + $rapport, $rapport['retirees'] > 0 ? 200 : 422);
    }

    private static function cleDesConflits(int $canonicalId): string
    {
        return 'lmd.fusion_ecue.conflits.'.$canonicalId;
    }

    /** @return list<int> */
    private function reglees(array $demandees, array $rapport): array
    {
        $refusees = array_column($rapport['refusees'], 'id');

        return array_values(array_diff(array_map('intval', $demandees), $refusees));
    }
}
