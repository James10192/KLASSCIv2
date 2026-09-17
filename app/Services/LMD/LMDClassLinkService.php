<?php

namespace App\Services\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\LMD\FiliereMiroirLmd;
use Illuminate\Support\Facades\DB;

/**
 * Rattache des classes LMD à un parcours : pose parcours_id, ancre la classe sur
 * la filière du parcours — créée à son nom si elle manque, voir FiliereMiroirLmd
 * — et force systeme_academique = 'LMD'. Le domaine et la mention en découlent
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

            // Ancrage resolu UNE fois, avant la boucle.
            //
            // `parcours->filiere_id` est nullable, et nulle pour les parcours
            // d'agronomie d'USAT. La recopier ecrivait NULL sur une colonne
            // NOT NULL : le save() echouait et faisait reculer toute la
            // transaction. On demande donc au parcours sa filiere d'ancrage.
            //
            // Rien en dry_run : une simulation ne cree pas de filiere.
            $ancrageId = $dryRun
                ? $parcours->filiere_id
                : app(FiliereMiroirLmd::class)->pourParcours($parcours)->id;

            foreach ($classes as $classe) {
                $type = $classe->niveauEtude->type ?? null;
                if (!in_array($type, ESBTPNiveauEtude::CYCLES_LMD, true)) {
                    $skipped[] = ['id' => $classe->id, 'name' => $classe->name,
                        'reason' => 'niveau non-LMD (' . ($type ?? 'inconnu') . ')'];
                    continue;
                }

                $before = ['parcours_id' => $classe->parcours_id, 'filiere_id' => $classe->filiere_id,
                    'systeme' => $classe->systeme_academique];

                if (!$dryRun) {
                    $classe->parcours_id = $parcours->id;
                    $classe->filiere_id = $ancrageId;
                    $classe->systeme_academique = 'LMD';
                    $classe->save();
                }

                $linked[] = ['id' => $classe->id, 'name' => $classe->name, 'before' => $before,
                    'after' => ['parcours_id' => $parcours->id, 'filiere_id' => $ancrageId, 'systeme' => 'LMD']];
            }

            return [$linked, $skipped];
        };

        [$linked, $skipped] = $dryRun ? $apply() : DB::transaction($apply);

        return [
            'dry_run' => $dryRun,
            'parcours' => ['code' => $parcours->code, 'name' => $parcours->name,
                'filiere_id' => $parcours->filiere_id],
            // Vrai quand le parcours n'a pas encore de filiere : l'application
            // reelle en creera une a son nom. Sans ce drapeau, le dry_run
            // annoncerait « filiere_id: null » sans dire ce qui va se passer.
            'reflet_a_creer' => $parcours->filiere_id === null,
            'linked' => $linked,
            'skipped' => $skipped,
            'totals' => ['linked' => count($linked), 'skipped' => count($skipped)],
        ];
    }
}
