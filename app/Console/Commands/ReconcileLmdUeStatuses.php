<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatUE;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile statuses in existing UNPUBLISHED LMD snapshots without recomputing
 * grades or bypassing note-sheet readiness. Defaults to inspection only.
 */
final class ReconcileLmdUeStatuses extends Command
{
    protected $signature = 'lmd:reconcile-ue-statuses
        {--classe= : Required class ID}
        {--annee= : Required academic year ID}
        {--semestre= : Required LMD semester}
        {--apply : Persist proposed changes (default is dry-run)}';

    protected $description = 'Audit/reapply current APC/AQ/NAQ rules to existing unpublished LMD bulletin UE snapshots.';

    public function handle(LmdAcademicRuleProfile $rules, LMDBulletinService $service): int
    {
        $classe = filter_var($this->option('classe'), FILTER_VALIDATE_INT);
        $annee = filter_var($this->option('annee'), FILTER_VALIDATE_INT);
        $semestre = filter_var($this->option('semestre'), FILTER_VALIDATE_INT);
        if (!$classe || !$annee || !$semestre || $semestre < 1 || $semestre > 10) {
            $this->error('Renseignez --classe, --annee et --semestre (IDs positifs, semestre 1 à 10).');

            return self::FAILURE;
        }

        $scope = ESBTPLMDBulletin::query()
            ->where('classe_id', $classe)
            ->where('annee_universitaire_id', $annee)
            ->where('semestre', $semestre);
        $published = (clone $scope)->where('is_published', true)->count();
        if ($published > 0) {
            $this->error("Cohorte figée : {$published} bulletin(s) publié(s). Aucune écriture, même sur les autres bulletins.");

            return self::FAILURE;
        }

        $threshold = $rules->validationThreshold();
        $minimum = $rules->interUeCompensationMinimum();
        $enabled = $rules->interUeCompensationEnabled();
        $this->line(sprintf('Règles courantes : validation %.2f ; plancher APC %.2f ; compensation inter-UE %s.', $threshold, $minimum, $enabled ? 'oui' : 'non'));
        $this->line($this->option('apply') ? 'MODE APPLICATION' : 'MODE SIMULATION (aucune écriture)');

        $examined = 0;
        $changes = 0;
        $skipped = 0;
        foreach ((clone $scope)->with('resultatsUEs')->lazyById(100) as $bulletin) {
            $rows = $bulletin->resultatsUEs;
            if ($rows->isEmpty() || $bulletin->moyenne_generale === null || $rows->contains(fn ($ue) => $ue->moyenne === null)) {
                $skipped++;
                $this->warn("Bulletin #{$bulletin->id} ignoré : UE ou moyenne générale absente.");
                continue;
            }

            $expectedCredits = 0;
            $pending = [];
            foreach ($rows as $ue) {
                $avg = (float) $ue->moyenne;
                $status = $avg >= $threshold ? ESBTPLMDResultatUE::STATUT_AQ : (
                    $enabled && $avg >= $minimum && (float) $bulletin->moyenne_generale >= $threshold
                    ? ESBTPLMDResultatUE::STATUT_APC
                    : ESBTPLMDResultatUE::STATUT_NAQ
                );
                if ($status !== ESBTPLMDResultatUE::STATUT_NAQ) {
                    $expectedCredits += (int) $ue->credit;
                }
                if ($ue->statut !== $status) {
                    $pending[] = "#{$ue->id} {$ue->statut}->{$status}";
                }
            }

            $needsUpdate = $pending !== [] || (int) $bulletin->credits_capitalises !== $expectedCredits;
            $examined++;
            if (!$needsUpdate) {
                continue;
            }
            $changes++;
            $this->line("Bulletin #{$bulletin->id} : ".implode(', ', $pending)." ; crédits {$bulletin->credits_capitalises} -> {$expectedCredits}");

            if ($this->option('apply')) {
                DB::transaction(function () use ($bulletin, $service, $expectedCredits): void {
                    $locked = ESBTPLMDBulletin::query()->lockForUpdate()->findOrFail($bulletin->id);
                    if ($locked->is_published || ESBTPLMDBulletin::query()
                        ->where('classe_id', $locked->classe_id)
                        ->where('annee_universitaire_id', $locked->annee_universitaire_id)
                        ->where('semestre', $locked->semestre)
                        ->where('is_published', true)->exists()) {
                        throw new \RuntimeException("Bulletin publié pendant le traitement : #{$locked->id}");
                    }
                    $capitalises = $service->appliquerCompensation($locked->resultatsUEs()->get()->all(), (float) $locked->moyenne_generale);
                    if ($capitalises !== $expectedCredits) {
                        throw new \RuntimeException("Résultat concurrent divergent pour bulletin #{$locked->id}");
                    }
                    $locked->credits_capitalises = $capitalises;
                    $locked->save();
                });
            }
        }

        $this->info("{$examined} bulletins vérifiés, {$changes} à corriger, {$skipped} ignorés.");
        if (!$this->option('apply')) {
            $this->warn('Aucune écriture. Relancez avec --apply pour appliquer après contrôle.');
        }

        return self::SUCCESS;
    }
}
