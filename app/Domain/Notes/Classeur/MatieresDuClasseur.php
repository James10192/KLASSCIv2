<?php

namespace App\Domain\Notes\Classeur;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Models\ESBTPClasse;
use App\Services\LMDBulletinService;
use Illuminate\Support\Collection;

/**
 * Les matières d'une fiche de notes Excel : celles que le bulletin de la
 * classe lit, pour ce semestre. LMD : les éléments (ECUE) de la maquette du
 * semestre ; BTS : les matières du bulletin de la classe.
 */
class MatieresDuClasseur
{
    public function __construct(private LMDBulletinService $lmd, private BtsBulletinSubjectResolver $bts) {}

    /** @return Collection<int, \App\Models\ESBTPMatiere> */
    public function pour(ESBTPClasse $classe, string $periode): Collection
    {
        if (($classe->systeme_academique ?? '') === 'LMD') {
            $semestre = (int) preg_replace('/\D/', '', $periode);

            return $this->lmd->getUEsForSemestre($classe, $semestre)
                ->flatMap(fn ($ue) => $ue->getEcuesEffectifs($classe->parcours_id))
                ->unique('id')->values();
        }

        return $this->bts->subjectsForClasse($classe)->sortBy('name')->values();
    }
}
