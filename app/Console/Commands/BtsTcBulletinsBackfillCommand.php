<?php

namespace App\Console\Commands;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Services\BulletinService;
use App\Services\ESBTP\BulletinConsistencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill IDEMPOTENT des bulletins annuels BTS Tronc Commun → Spécialité.
 *
 * Cible les inscriptions du modèle phases (TC orienté) dont le bulletin annuel
 * officiel persisté est OBSOLÈTE par rapport au recalcul courant (introduit par
 * le class-map / coefficient fallback / rang TC-aware des commits précédents).
 *
 * BTS uniquement (LMD intouché). DESIGN + CODE only : cette commande n'est
 * JAMAIS lancée en prod par ce workflow. Garde-fou tenant strict + dry-run par
 * défaut côté runbook.
 *
 * Détecteur : compare la moyenne_generale PERSISTÉE du bulletin annuel avec la
 * moyenne RECALCULÉE (genererDonneesBulletin). On N'utilise PAS getSnapshot avec
 * période 'annuel' car BulletinConsistencyService force $officialBulletin=null en
 * annuel → ce détecteur serait un NO-OP.
 */
class BtsTcBulletinsBackfillCommand extends Command
{
    protected $signature = 'bts:tc-bulletins-backfill
        {tenant : Code du tenant (doit matcher TENANT_CODE de l\'instance)}
        {--annee= : ID de l\'année universitaire à backfiller}
        {--dry-run : N\'écrit rien, rapporte seulement les bulletins à régénérer}
        {--limit= : Limite le nombre d\'inscriptions traitées}
        {--periode=annuel : Période ciblée (annuel uniquement supporté)}';

    protected $description = 'Backfill idempotent des bulletins annuels BTS Tronc Commun (code only, aucune exécution prod).';

    private const EPSILON = 0.01;

    public function handle(
        BulletinService $bulletinService,
        BulletinConsistencyService $consistencyService
    ): int {
        $tenant = (string) $this->argument('tenant');
        $expectedTenant = (string) (config('app.tenant_code') ?? env('TENANT_CODE'));

        // GARDE-FOU tenant : refuse toute exécution sur une instance non concordante.
        if ($tenant !== $expectedTenant) {
            $this->error(sprintf(
                'Tenant "%s" ne correspond pas à l\'instance courante "%s". Abandon.',
                $tenant,
                $expectedTenant
            ));

            return self::FAILURE;
        }

        $periode = (string) $this->option('periode');
        if ($periode !== 'annuel') {
            $this->error('Seule la période "annuel" est supportée par ce backfill.');

            return self::FAILURE;
        }

        $anneeId = $this->option('annee') !== null ? (int) $this->option('annee') : null;
        if (! $anneeId) {
            $this->error('L\'option --annee est obligatoire (ID année universitaire).');

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $counters = [
            'scanned' => 0,
            'eligible' => 0,
            'skipped_no_official' => 0,
            'skipped_recompute_error' => 0,
            'aligned' => 0,
            'affected' => 0,
            'would_fix' => 0,
            'fixed' => 0,
            'errors' => 0,
        ];

        /** @var array<int, array<string, mixed>> $details */
        $details = [];

        // Cibler UNIQUEMENT les inscriptions modèle phases (TC orienté) :
        // - sans inscription_origine_id (pas le modèle legacy double-inscription)
        // - de l'année visée
        // - ayant au moins une phase
        // - dont la classe pointe vers une filière de spécialité (parent_id NOT NULL)
        $query = ESBTPInscription::query()
            ->whereNull('inscription_origine_id')
            ->where('annee_universitaire_id', $anneeId)
            ->whereHas('phases')
            ->whereHas('classe.filiere', function ($q) {
                $q->whereNotNull('parent_id');
            })
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $processInscription = function (ESBTPInscription $inscription) use (
            $bulletinService,
            $consistencyService,
            $periode,
            $anneeId,
            $isDryRun,
            &$counters,
            &$details
        ): void {
            $counters['scanned']++;

            $etudiantId = (int) $inscription->etudiant_id;
            $classeId = (int) $inscription->classe_id;

            // DÉTECTEUR : lire le bulletin annuel officiel PERSISTÉ direct (pas getSnapshot).
            $officialBulletin = ESBTPBulletin::where('etudiant_id', $etudiantId)
                ->where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeId)
                ->where('periode', $periode)
                ->latest('updated_at')
                ->first();

            if (! $officialBulletin || $officialBulletin->moyenne_generale === null) {
                $counters['skipped_no_official']++;
                $details[] = [
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'status' => 'skipped_no_official',
                ];

                return;
            }

            $counters['eligible']++;
            $persisted = round((float) $officialBulletin->moyenne_generale, 2);

            // Recalculer en LECTURE SEULE : genererDonneesBulletin persiste, donc on
            // enveloppe dans une transaction rollback pour ne RIEN écrire ici.
            $recomputed = null;
            try {
                DB::beginTransaction();
                $donnees = $bulletinService->genererDonneesBulletin(
                    $etudiantId,
                    $classeId,
                    $anneeId,
                    $periode
                );
                $recomputed = isset($donnees['moyenneGlobale']) && $donnees['moyenneGlobale'] !== null
                    ? round((float) $donnees['moyenneGlobale'], 2)
                    : null;
                DB::rollBack();
            } catch (\Throwable $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                $counters['skipped_recompute_error']++;
                $details[] = [
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'status' => 'skipped_recompute_error',
                    'message' => $e->getMessage(),
                ];

                return;
            }

            if ($recomputed === null) {
                $counters['skipped_recompute_error']++;
                $details[] = [
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'status' => 'skipped_recompute_error',
                    'message' => 'Moyenne recalculée indisponible.',
                ];

                return;
            }

            $delta = round($recomputed - $persisted, 2);
            $isAffected = abs($delta) >= self::EPSILON;

            if (! $isAffected) {
                $counters['aligned']++;

                return;
            }

            $counters['affected']++;
            $detail = [
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'persisted' => $persisted,
                'recomputed' => $recomputed,
                'delta' => $delta,
            ];

            if ($isDryRun) {
                $counters['would_fix']++;
                $detail['status'] = 'would_fix';
                Log::info('[bts-tc-backfill] would_fix bulletin annuel obsolète', $detail);
                $details[] = $detail;

                return;
            }

            try {
                DB::transaction(function () use ($consistencyService, $etudiantId, $classeId, $anneeId, $periode) {
                    $consistencyService->regenerateOfficialBulletin(
                        $etudiantId,
                        $classeId,
                        $anneeId,
                        $periode
                    );
                });
                $counters['fixed']++;
                $detail['status'] = 'fixed';
                Log::info('[bts-tc-backfill] bulletin annuel régénéré', $detail);
            } catch (\Throwable $e) {
                $counters['errors']++;
                $detail['status'] = 'error';
                $detail['message'] = $e->getMessage();
                Log::error('[bts-tc-backfill] échec régénération bulletin annuel', $detail);
            }

            $details[] = $detail;
        };

        $query->chunkById(200, function ($inscriptions) use ($processInscription) {
            foreach ($inscriptions as $inscription) {
                $processInscription($inscription);
            }
        });

        $this->renderReport($tenant, $anneeId, $isDryRun, $counters);

        $reportPath = $this->writeJsonReport($tenant, $anneeId, $isDryRun, $counters, $details);
        $this->line('Rapport JSON : ' . $reportPath);

        return $counters['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, int>  $counters
     */
    private function renderReport(string $tenant, int $anneeId, bool $isDryRun, array $counters): void
    {
        $this->info(sprintf(
            'Backfill bulletins annuels BTS TC — tenant=%s année=%d mode=%s',
            $tenant,
            $anneeId,
            $isDryRun ? 'DRY-RUN' : 'RUN'
        ));

        $rows = [];
        foreach ($counters as $key => $value) {
            $rows[] = [$key, $value];
        }

        $this->table(['Compteur', 'Valeur'], $rows);
    }

    /**
     * @param  array<string, int>  $counters
     * @param  array<int, array<string, mixed>>  $details
     */
    private function writeJsonReport(
        string $tenant,
        int $anneeId,
        bool $isDryRun,
        array $counters,
        array $details
    ): string {
        $timestamp = now()->format('Ymd_His');
        $relativePath = sprintf('backfill/bts-tc-%s-%d-%s.json', $tenant, $anneeId, $timestamp);

        $payload = [
            'command' => 'bts:tc-bulletins-backfill',
            'tenant' => $tenant,
            'annee_universitaire_id' => $anneeId,
            'periode' => 'annuel',
            'mode' => $isDryRun ? 'dry-run' : 'run',
            'generated_at' => now()->toIso8601String(),
            'counters' => $counters,
            'details' => $details,
        ];

        Storage::disk('local')->put(
            $relativePath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return storage_path('app/' . $relativePath);
    }
}
