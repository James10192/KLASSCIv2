<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPBulletin;

/**
 * Résout la classe-cohorte servant de base au calcul de rang/effectif d'un bulletin BTS.
 *
 * Pour un bulletin Semestre 1 d'un étudiant orienté (Tronc Commun → Spécialité), le rang
 * doit être calculé dans la cohorte de la classe Tronc Commun qui portait réellement les
 * notes du S1 — pas dans la classe de spécialité courante. Pour le S2 / l'annuel (ou tout
 * bulletin sans étudiant identifiable), on conserve la classe du bulletin.
 *
 * Collaborateur partagé entre ESBTPBulletin::calculerRang (génération controller) et
 * BulletinService::calculerRang (preview/regen) pour réconcilier les deux chemins.
 * BTS uniquement — LMD intouché. Stateless, ne dépend pas de BulletinService.
 */
class BtsBulletinCohortResolver
{
    public function __construct(private BtsAnnualClassMapResolver $classMapResolver)
    {
    }

    /**
     * Détermine la classe dont la cohorte sert de base au rang et à l'effectif.
     */
    public function resolveRankCohortClasseId(ESBTPBulletin $bulletin): int
    {
        $classeId = (int) $bulletin->classe_id;

        if ($this->normalizePeriode((string) $bulletin->periode) !== 'semestre1' || ! $bulletin->etudiant_id) {
            return $classeId;
        }

        $classMap = $this->classMapResolver->resolve(
            (int) $bulletin->etudiant_id,
            $classeId,
            (int) $bulletin->annee_universitaire_id
        );

        return (int) ($classMap['semestre1_classe_id'] ?? $classeId);
    }

    /**
     * Mapping périodes local (miroir de BulletinService::normalizePeriode) — pas
     * d'injection de BulletinService pour garder ce résolveur stateless.
     */
    private function normalizePeriode(string $periode): string
    {
        if ($periode === '1') {
            return 'semestre1';
        }
        if ($periode === '2') {
            return 'semestre2';
        }

        return $periode ?: 'semestre1';
    }
}
