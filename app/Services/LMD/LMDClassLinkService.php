<?php

namespace App\Services\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDParcours;
use App\Services\ClasseManagementService;
use Illuminate\Support\Facades\DB;

/**
 * Rattache des classes LMD à un parcours : pose parcours_id, dérive
 * filiere_id = parcours.filiere_id (convention classe-lmd-filiere-as-mention),
 * et force systeme_academique = 'LMD'. Le domaine + la mention en découlent
 * via parcours -> mention -> domaine.
 *
 * Garde-fou : une classe dont le niveau n'est PAS LMD (Licence/Master/Doctorat)
 * est ignorée et rapportée (jamais modifiée). Idempotent. Dry-run par défaut.
 */
class LMDClassLinkService
{
    /**
     * @param  int[]  $classIds
     * @return array{dry_run:bool, parcours:?array, linked:array, skipped:array, totals:array}
     */
    public function link(string $parcoursCode, array $classIds, bool $dryRun = true): array
    {
        $parcours = ESBTPLMDParcours::where('code', $parcoursCode)->first();
        if (!$parcours) {
            return ['dry_run' => $dryRun, 'parcours' => null, 'linked' => [], 'skipped' => [],
                'totals' => ['linked' => 0, 'skipped' => 0]];
        }

        $apply = function () use ($parcours, $classIds, $dryRun) {
            $linked = [];
            $skipped = [];

            $classes = ESBTPClasse::with('niveauEtude:id,type')
                ->whereIn('id', $classIds)->get();

            foreach ($classes as $classe) {
                $type = $classe->niveauEtude->type ?? null;
                if (!in_array($type, ClasseManagementService::LMD_TYPES, true)) {
                    $skipped[] = ['id' => $classe->id, 'name' => $classe->name,
                        'reason' => 'niveau non-LMD (' . ($type ?? 'inconnu') . ')'];
                    continue;
                }

                $before = ['parcours_id' => $classe->parcours_id, 'filiere_id' => $classe->filiere_id,
                    'systeme' => $classe->systeme_academique];

                if (!$dryRun) {
                    $classe->parcours_id = $parcours->id;
                    $classe->filiere_id = $parcours->filiere_id;
                    $classe->systeme_academique = 'LMD';
                    $classe->save();
                }

                $linked[] = ['id' => $classe->id, 'name' => $classe->name, 'before' => $before,
                    'after' => ['parcours_id' => $parcours->id, 'filiere_id' => $parcours->filiere_id, 'systeme' => 'LMD']];
            }

            return [$linked, $skipped];
        };

        [$linked, $skipped] = $dryRun ? $apply() : DB::transaction($apply);

        return [
            'dry_run' => $dryRun,
            'parcours' => ['code' => $parcours->code, 'name' => $parcours->name,
                'filiere_id' => $parcours->filiere_id],
            'linked' => $linked,
            'skipped' => $skipped,
            'totals' => ['linked' => count($linked), 'skipped' => count($skipped)],
        ];
    }
}
