<?php

namespace App\Console\Commands;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPLMDJury;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recense les proces-verbaux de deliberation qui ont depasse leur duree de
 * retention legale.
 *
 * Le reglage `lmd_pv_retention_years` (5 ans, norme Cote d'Ivoire) existait
 * depuis l'origine mais n'etait consomme par aucune tache : la duree etait
 * annoncee, jamais mesuree. Cette commande la mesure.
 *
 * Elle RECENSE par defaut et ne touche a rien. L'archivage n'a lieu que si on
 * le demande explicitement (`--purger`), et il reste un archivage : le PV est
 * soft-delete, jamais supprime. Les documents officiels rattaches (table
 * `esbtp_official_documents`) ne sont jamais touches — le modele interdit lui
 * meme leur suppression, ils restent la preuve opposable.
 */
class LmdPvRetentionCommand extends Command
{
    protected $signature = 'lmd:pv-retention
                            {--annees= : Duree de retention en annees (defaut : reglage lmd_pv_retention_years)}
                            {--dry-run : Recensement seul — comportement par defaut, l\'option ne sert qu\'a l\'expliciter}
                            {--purger : Archive (soft-delete) les PV recenses, au lieu de seulement les lister}
                            {--force : N\'exige pas de confirmation avec --purger}
                            {--json : Sortie JSON}';

    protected $description = 'Recense les PV de deliberation qui ont depasse la duree de retention legale';

    public function handle(): int
    {
        $annees = $this->resolveRetentionYears();

        if ($annees === null) {
            return self::FAILURE;
        }

        $limite = Carbon::now()->subYears($annees)->startOfDay();
        $pvs = $this->pvsDepasses($limite);
        $purger = (bool) $this->option('purger') && ! (bool) $this->option('dry-run');

        if ((bool) $this->option('json')) {
            return $this->rendreJson($annees, $limite, $pvs, $purger);
        }

        $this->line(sprintf(
            'Retention : %d an(s). Sont recenses les PV generes avant le %s.',
            $annees,
            $limite->format('d/m/Y')
        ));

        if ($pvs->isEmpty()) {
            $this->info('Aucun PV n\'a depasse la duree de retention.');

            return self::SUCCESS;
        }

        $this->table(
            ['Numero', 'Annee', 'Statut', 'Genere le', 'Age (ans)', 'Documents officiels'],
            $pvs->map(fn (ESBTPLMDJury $jury): array => [
                $jury->pv_numero ?? ('Jury #'.$jury->id),
                $jury->anneeUniversitaire?->display_name ?? '—',
                $jury->status,
                optional($jury->pv_genere_at)->format('d/m/Y'),
                $this->ageEnAnnees($jury),
                $jury->official_documents_count,
            ])->all()
        );

        if (! $purger) {
            $this->info(sprintf(
                '%d PV depassent la retention. Recensement seul : rien n\'a ete modifie.',
                $pvs->count()
            ));
            $this->line('Pour les archiver (soft-delete, reversible) : --purger');

            return self::SUCCESS;
        }

        if (! $this->confirmerArchivage($pvs)) {
            $this->warn('Archivage annule. Rien n\'a ete modifie.');

            return self::SUCCESS;
        }

        $archives = $this->archiver($pvs);

        $this->info(sprintf(
            '%d PV archive(s) (soft-delete). Les documents officiels rattaches sont conserves.',
            $archives
        ));

        return self::SUCCESS;
    }

    /** Duree de retention demandee, ou null si la valeur est inexploitable. */
    private function resolveRetentionYears(): ?int
    {
        $option = $this->option('annees');
        $brut = $option !== null && $option !== ''
            ? $option
            : SettingsHelper::get('lmd_pv_retention_years', 5);

        if (! is_numeric($brut)) {
            $this->error('Duree de retention illisible : '.var_export($brut, true));

            return null;
        }

        $annees = (int) $brut;

        if ($annees < 1) {
            // Une retention nulle ou negative archiverait la totalite des PV,
            // y compris ceux de l'annee en cours. On refuse plutot que deviner.
            $this->error('La duree de retention doit valoir au moins 1 an (recue : '.$annees.').');

            return null;
        }

        return $annees;
    }

    /** @return Collection<int, ESBTPLMDJury> */
    private function pvsDepasses(Carbon $limite): Collection
    {
        return ESBTPLMDJury::query()
            ->whereNotNull('pv_genere_at')
            ->where('pv_genere_at', '<=', $limite)
            ->with('anneeUniversitaire')
            ->withCount('officialDocuments')
            ->orderBy('pv_genere_at')
            ->get();
    }

    private function ageEnAnnees(ESBTPLMDJury $jury): string
    {
        if (! $jury->pv_genere_at) {
            return '—';
        }

        return number_format($jury->pv_genere_at->floatDiffInYears(Carbon::now()), 1, ',', ' ');
    }

    /** @param Collection<int, ESBTPLMDJury> $pvs */
    private function confirmerArchivage(Collection $pvs): bool
    {
        if ((bool) $this->option('force') || ! $this->input->isInteractive()) {
            return (bool) $this->option('force');
        }

        return $this->confirm(sprintf(
            'Archiver (soft-delete) %d PV de deliberation ? Ils resteront restaurables.',
            $pvs->count()
        ), false);
    }

    /** @param Collection<int, ESBTPLMDJury> $pvs */
    private function archiver(Collection $pvs): int
    {
        return DB::transaction(function () use ($pvs): int {
            $archives = 0;

            foreach ($pvs as $jury) {
                $jury->delete(); // SoftDeletes : la ligne reste, restaurable.
                $archives++;

                Log::warning('[LMD] PV de deliberation archive pour depassement de retention', [
                    'jury_id' => $jury->id,
                    'pv_numero' => $jury->pv_numero,
                    'pv_genere_at' => optional($jury->pv_genere_at)->toIso8601String(),
                    'documents_officiels_conserves' => $jury->official_documents_count,
                ]);
            }

            return $archives;
        });
    }

    /** @param Collection<int, ESBTPLMDJury> $pvs */
    private function rendreJson(int $annees, Carbon $limite, Collection $pvs, bool $purger): int
    {
        if ($purger && ! $this->confirmerArchivage($pvs)) {
            $purger = false;
        }

        $archives = $purger ? $this->archiver($pvs) : 0;

        $this->line(json_encode([
            'retention_annees' => $annees,
            'genere_avant' => $limite->toDateString(),
            'mode' => $purger ? 'archivage' : 'recensement',
            'total' => $pvs->count(),
            'archives' => $archives,
            'pvs' => $pvs->map(fn (ESBTPLMDJury $jury): array => [
                'jury_id' => $jury->id,
                'pv_numero' => $jury->pv_numero,
                'annee' => $jury->anneeUniversitaire?->display_name,
                'statut' => $jury->status,
                'pv_genere_at' => optional($jury->pv_genere_at)->toIso8601String(),
                'documents_officiels' => $jury->official_documents_count,
            ])->values()->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
