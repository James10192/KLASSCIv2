<?php

namespace App\Services;

use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionEcheancierSnapshot;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Regenere les snapshots d'echeancier par lots, pour un perimetre d'inscriptions.
 *
 * Le calcul lui-meme n'est pas ici : chaque inscription passe par
 * EcheancierSnapshotService::refreshForInscription(), le seul ecrivain de la
 * table, celui que la fiche financiere d'un etudiant appelle deja. Ce service
 * ne fait que choisir les inscriptions, les parcourir par lots et compter.
 *
 * Idempotent : refreshForInscription() fait un updateOrCreate sur
 * inscription_id ; relancer deux fois donne le meme resultat, seule la colonne
 * « mises a jour » bouge.
 */
class EcheancierRecomputeService
{
    public const CHUNK_PAR_DEFAUT = 100;
    public const CHUNK_MAX = 500;
    public const ECHANTILLON_ERREURS_MAX = 5;

    public function __construct(private readonly EcheancierSnapshotService $snapshots) {}

    /**
     * Les inscriptions concernees : actives, dossier mene a son terme, de
     * l'annee demandee — les memes criteres que la couverture et le diagnostic,
     * sinon les chiffres ne se repondraient pas.
     *
     * Une inscription nommee explicitement passe outre le filtre d'annee.
     */
    public function requete(?int $anneeId, ?int $inscriptionId): Builder
    {
        return ESBTPInscription::query()
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->when($inscriptionId, fn ($q) => $q->whereKey($inscriptionId))
            ->when(! $inscriptionId && $anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId));
    }

    /**
     * @param  callable(int $traitees):void|null  $progression  Appele apres chaque lot.
     * @return array{
     *   dry_run: bool,
     *   perimetre: int,
     *   a_creer: int,
     *   a_mettre_a_jour: int,
     *   traitees: int,
     *   creees: int,
     *   mises_a_jour: int,
     *   sans_regle: int,
     *   erreurs: int,
     *   echantillon_erreurs: array<int, array{inscription_id:int, message:string}>
     * }
     */
    public function recalculer(
        ?int $anneeId,
        ?int $inscriptionId,
        bool $dryRun = true,
        int $chunk = self::CHUNK_PAR_DEFAUT,
        ?callable $progression = null,
    ): array {
        $chunk = max(1, min(self::CHUNK_MAX, $chunk));
        $base = $this->requete($anneeId, $inscriptionId);

        $perimetre = (clone $base)->count();
        $avecSnapshot = (clone $base)
            ->whereExists(fn ($q) => $q->selectRaw(1)
                ->from('esbtp_inscription_echeancier_snapshots as s')
                ->whereColumn('s.inscription_id', 'esbtp_inscriptions.id'))
            ->count();

        $rapport = [
            'dry_run' => $dryRun,
            'perimetre' => $perimetre,
            'a_creer' => max(0, $perimetre - $avecSnapshot),
            'a_mettre_a_jour' => $avecSnapshot,
            'traitees' => 0,
            'creees' => 0,
            'mises_a_jour' => 0,
            'sans_regle' => 0,
            'erreurs' => 0,
            'echantillon_erreurs' => [],
        ];

        if ($dryRun || $perimetre === 0) {
            return $rapport;
        }

        $base->chunkById($chunk, function ($inscriptions) use (&$rapport, $progression) {
            foreach ($inscriptions as $inscription) {
                $rapport['traitees']++;

                try {
                    $snapshot = $this->snapshots->refreshForInscription($inscription);
                } catch (Throwable $e) {
                    $rapport['erreurs']++;
                    if (count($rapport['echantillon_erreurs']) < self::ECHANTILLON_ERREURS_MAX) {
                        $rapport['echantillon_erreurs'][] = [
                            'inscription_id' => (int) $inscription->id,
                            'message' => $e->getMessage(),
                        ];
                    }
                    continue;
                }

                $snapshot->wasRecentlyCreated ? $rapport['creees']++ : $rapport['mises_a_jour']++;

                if ($this->sansRegle($snapshot)) {
                    $rapport['sans_regle']++;
                }
            }

            if ($progression) {
                $progression($rapport['traitees']);
            }
        });

        return $rapport;
    }

    /**
     * Un snapshot « sans regle » est projete entierement en mode degrade : aucun
     * de ses postes n'a trouve de regle d'echeancier, chacun tombe sur l'echeance
     * unique par defaut. C'est le chiffre qui dit si les regles configurees
     * s'appliquent reellement au parc.
     */
    private function sansRegle(ESBTPInscriptionEcheancierSnapshot $snapshot): bool
    {
        $items = (array) ($snapshot->payload['items'] ?? []);

        foreach ($items as $item) {
            if (! empty($item['rule_id'])) {
                return false;
            }
        }

        return true;
    }
}
