<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPInscription;

/**
 * Résolveur stateless du class-map annuel BTS (Tronc Commun → Spécialité).
 *
 * Pour un (étudiant, année, classe demandée), détermine quelle classe porte les
 * notes du Semestre 1 et du Semestre 2 — en supportant à la fois le modèle phases
 * (ESBTPInscriptionPhase) et le modèle legacy double-inscription
 * (inscription_origine_id + type_changement).
 *
 * Extrait verbatim de BtsCurrentResultSnapshotService::resolveAnnualClassMap pour
 * être partagé par le snapshot ET BulletinService (BTS uniquement, LMD intouché).
 */
class BtsAnnualClassMapResolver
{
    /**
     * Resultats deja calcules, indexes par « etudiant:classe:annee ».
     * Portee requete, comme l'instance du service.
     *
     * @var array<string, array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}>
     */
    private array $resolveCache = [];

    public function __construct(private BtsPhaseResolver $btsPhaseResolver)
    {
    }

    /**
     * @return array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}
     */
    public function resolve(int $etudiantId, int $requestedClasseId, int $anneeUniversitaireId): array
    {
        // Memoisation par triplet d'arguments, portee requete. La generation en
        // masse appelait ce resolveur plusieurs fois par etudiant et par
        // etudiant de la cohorte, soit un cout quadratique pour un resultat
        // strictement identique a arguments egaux.
        $cacheKey = $etudiantId.':'.$requestedClasseId.':'.$anneeUniversitaireId;

        if (array_key_exists($cacheKey, $this->resolveCache)) {
            return $this->resolveCache[$cacheKey];
        }

        return $this->resolveCache[$cacheKey] = $this->resolveUncached(
            $etudiantId,
            $requestedClasseId,
            $anneeUniversitaireId
        );
    }

    /**
     * @return array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}
     */
    private function resolveUncached(int $etudiantId, int $requestedClasseId, int $anneeUniversitaireId): array
    {
        $inscription = ESBTPInscription::query()
            ->with([
                'filiere',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ])
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->orderByRaw('CASE WHEN classe_id = ? THEN 0 ELSE 1 END', [$requestedClasseId])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('date_inscription')
            ->orderByDesc('id')
            ->first();

        if (! $inscription) {
            return [
                'inscription_id' => null,
                'source_model' => 'phase_based',
                'semestre1_classe_id' => $requestedClasseId,
                'semestre2_classe_id' => $requestedClasseId,
            ];
        }

        $journey = $this->btsPhaseResolver->buildJourney($inscription);
        $semestre1Phase = $this->btsPhaseResolver->resolveSemesterPhase($inscription, 1);
        $semestre2Phase = $this->btsPhaseResolver->resolveSemesterPhase($inscription, 2);

        return [
            'inscription_id' => $inscription->id,
            'source_model' => $journey['source_model'] ?? 'phase_based',
            'semestre1_classe_id' => $semestre1Phase['classe_id'] ?? $inscription->classe_id,
            'semestre2_classe_id' => $semestre2Phase['classe_id'] ?? $inscription->classe_id,
        ];
    }
}
