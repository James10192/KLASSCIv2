<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPInscription;

/**
 * Effectif BTS aligne sur l'historique de phases, pas seulement classe_id courant.
 *
 * S1 d'un etudiant oriente : compte dans la classe TC qui portait le semestre.
 * S2 apres changement de specialite : la phase active du semestre l'emporte.
 */
final class BtsClassCohortCounter
{
    /**
     * Cohortes deja resolues, indexees par « annee:semestre » puis par classe.
     * Portee requete : le service est resolu par le conteneur, l'instance vit
     * le temps de la requete HTTP ou de la commande.
     *
     * @var array<string, array<int, array<int, true>>>
     */
    private array $cohortCache = [];

    public function __construct(private BtsPhaseResolver $phaseResolver)
    {
    }

    public function count(int $classeId, int $anneeUniversitaireId, string $periode): int
    {
        return count($this->etudiantIds($classeId, $anneeUniversitaireId, $periode));
    }

    /**
     * @return list<int>
     */
    public function etudiantIds(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $semester = $this->semesterNumber($periode);

        // Le balayage porte volontairement sur toute l'annee : la classe de
        // rattachement d'un etudiant depend de ses phases tronc commun et ne
        // peut pas etre filtree en SQL. On memoise donc le resultat par
        // (annee, semestre) le temps de la requete : la generation en masse
        // appelait cette methode plusieurs fois par etudiant, rechargeant a
        // chaque fois plus de 2000 inscriptions avec cinq arbres de relations.
        $cacheKey = $anneeUniversitaireId.':'.$semester;

        if (! array_key_exists($cacheKey, $this->cohortCache)) {
            $inscriptions = ESBTPInscription::query()
                ->with([
                    'filiere',
                    'classe.filiere',
                    'phases.classe.filiere',
                    'inscriptionOrigine.classe.filiere',
                    'inscriptionSpecialisation.classe.filiere',
                ])
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->get();

            $parClasse = [];
            foreach ($inscriptions as $inscription) {
                $resolved = $this->resolveClasseId($inscription, $semester);
                if ($resolved !== null) {
                    $parClasse[$resolved][(int) $inscription->etudiant_id] = true;
                }
            }

            $this->cohortCache[$cacheKey] = $parClasse;
        }

        $ids = $this->cohortCache[$cacheKey][$classeId] ?? [];

        $sorted = array_keys($ids);
        sort($sorted);

        return $sorted;
    }

    private function resolveClasseId(ESBTPInscription $inscription, int $semester): ?int
    {
        $phase = $this->phaseResolver->resolveSemesterPhase($inscription, $semester);
        if (is_array($phase) && ! empty($phase['classe_id'])) {
            return (int) $phase['classe_id'];
        }

        return $inscription->classe_id ? (int) $inscription->classe_id : null;
    }

    private function semesterNumber(string $periode): int
    {
        return match ($periode) {
            '2', 'semestre2', 'annuel' => 2,
            default => 1,
        };
    }
}
