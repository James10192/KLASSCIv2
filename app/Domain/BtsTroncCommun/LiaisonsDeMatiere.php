<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Support\Facades\DB;

/**
 * Le rattachement d'une matiere a un couple (filiere, niveau), en UN seul
 * endroit qui ecrit les trois tables a la fois.
 *
 * TROIS TABLES, DEUX VERITES, UN ECART PERMANENT. Le rattachement vit
 * aujourd'hui a la fois dans `esbtp_matiere_filiere_niveau` (le pivot
 * canonique, au grain filiere x niveau) et dans les deux pivots plats
 * `esbtp_matiere_filiere` et `esbtp_matiere_niveau`, qui sont deux listes
 * independantes. Le produit des deux listes n'est pas le pivot canonique :
 * une matiere en filieres [A, B] et en niveaux [1, 2] parait rattachee a
 * quatre couples alors que le pivot n'en porte peut-etre qu'un. C'est ce que
 * `CLIMatiereController::diagnoseLiaisons` appelle des combinaisons fantomes.
 *
 * L'ecart se voit a l'ecran : le bulletin se compose sur le pivot canonique
 * (`BtsBulletinSubjectResolver`), pendant que l'ecran de configuration des
 * matieres du bulletin et l'onglet Matieres d'une classe lisent les pivots
 * plats. Retirer une matiere d'un cote ne la retire pas de l'autre.
 *
 * CE SERVICE FAIT DES PIVOTS PLATS UNE PROJECTION DU PIVOT CANONIQUE. Apres
 * toute ecriture sur une matiere, ses filieres et ses niveaux sont recalcules
 * depuis ses lignes canoniques. La projection n'est jamais globale : elle ne
 * porte que sur la matiere qu'on vient de toucher, pour qu'aucune ecriture ne
 * remue des donnees que personne n'a demande a changer. La reconciliation de
 * l'existant est un geste separe et explicite.
 *
 * BTS uniquement. Le LMD tient ses matieres par parcours -> UE -> ECUE et
 * n'utilise aucun de ces trois pivots.
 *
 * @see .claude/rules/lmd-bts-matieres-single-source.md
 */
final class LiaisonsDeMatiere
{
    /**
     * Rattache une matiere a un couple, sans toucher a ses autres couples.
     *
     * Rend la ligne canonique, creee ou deja la.
     */
    public function poser(int $matiereId, int $filiereId, int $niveauId): ESBTPMatiereFilierNiveau
    {
        return DB::transaction(function () use ($matiereId, $filiereId, $niveauId) {
            $ligne = ESBTPMatiereFilierNiveau::firstOrCreate([
                'matiere_id' => $matiereId,
                'filiere_id' => $filiereId,
                'niveau_etude_id' => $niveauId,
            ]);

            $this->projeterLesPivotsPlats($matiereId);

            return $ligne;
        });
    }

    /**
     * Detache une matiere d'un couple : la ligne canonique, ses places par
     * semestre, et ce que les pivots plats en gardaient.
     *
     * Rend ce qui a ete supprime, pour que l'appelant puisse le rapporter.
     *
     * @return array{canonique: int, places_semestre: int, filiere_detachee: bool, niveau_detache: bool}
     */
    public function retirer(int $matiereId, int $filiereId, int $niveauId): array
    {
        return DB::transaction(function () use ($matiereId, $filiereId, $niveauId) {
            $canonique = ESBTPMatiereFilierNiveau::query()
                ->where('matiere_id', $matiereId)
                ->where('filiere_id', $filiereId)
                ->where('niveau_etude_id', $niveauId)
                ->delete();

            // La place au bulletin d'un couple qui n'existe plus n'a plus de
            // sens, et sa ligne resterait a jamais : rien ne la relit.
            $places = ESBTPMaquettePlaceSemestre::query()
                ->where('matiere_id', $matiereId)
                ->where('filiere_id', $filiereId)
                ->where('niveau_etude_id', $niveauId)
                ->delete();

            $avant = $this->pivotsPlats($matiereId);
            // `true` : on SAIT que cette matiere etait au pivot canonique, on
            // vient d'y supprimer sa ligne. Le garde-fou des matieres
            // historiques ne s'applique donc pas, et retirer la derniere
            // liaison d'une matiere doit bien vider ses pivots plats.
            $this->projeterLesPivotsPlats($matiereId, true);
            $apres = $this->pivotsPlats($matiereId);

            return [
                'canonique' => (int) $canonique,
                'places_semestre' => (int) $places,
                'filiere_detachee' => in_array($filiereId, $avant['filieres'], true)
                    && ! in_array($filiereId, $apres['filieres'], true),
                'niveau_detache' => in_array($niveauId, $avant['niveaux'], true)
                    && ! in_array($niveauId, $apres['niveaux'], true),
            ];
        });
    }

    /**
     * Recalcule les pivots plats d'une matiere depuis ses lignes canoniques.
     *
     * Une filiere reste attachee tant qu'une ligne canonique la cite, meme via
     * un autre niveau ; idem pour un niveau. C'est ce qui evite qu'un retrait
     * sur un couple n'efface la matiere d'un couple voisin.
     *
     * Ne fait rien si la matiere n'a aucune ligne canonique ET des pivots
     * plats deja remplis : ce cas est celui des matieres historiques, jamais
     * passees par le pivot canonique. Les vider ici les ferait disparaitre de
     * l'ecran de configuration du bulletin sans que personne l'ait demande.
     *
     * `$forcerMemeSiAucuneLigne` leve ce garde-fou, et seul un appelant qui
     * vient de supprimer une ligne canonique de cette matiere a le droit de le
     * poser : lui sait qu'elle y etait.
     */
    public function projeterLesPivotsPlats(int $matiereId, bool $forcerMemeSiAucuneLigne = false): void
    {
        $matiere = ESBTPMatiere::find($matiereId);
        if (! $matiere) {
            return;
        }

        $lignes = ESBTPMatiereFilierNiveau::query()
            ->where('matiere_id', $matiereId)
            ->get(['filiere_id', 'niveau_etude_id']);

        if ($lignes->isEmpty() && ! $forcerMemeSiAucuneLigne) {
            $plats = $this->pivotsPlats($matiereId);
            if ($plats['filieres'] !== [] || $plats['niveaux'] !== []) {
                // Matiere historique, hors pivot canonique : on n'y touche pas.
                return;
            }
        }

        $matiere->filieres()->sync($lignes->pluck('filiere_id')->unique()->values()->all());
        $matiere->niveaux()->sync($lignes->pluck('niveau_etude_id')->unique()->values()->all());
    }

    /**
     * Ce que les pivots plats portent aujourd'hui pour cette matiere.
     *
     * @return array{filieres: list<int>, niveaux: list<int>}
     */
    public function pivotsPlats(int $matiereId): array
    {
        return [
            'filieres' => DB::table('esbtp_matiere_filiere')
                ->where('matiere_id', $matiereId)
                ->pluck('filiere_id')
                ->map(static fn ($id) => (int) $id)
                ->values()
                ->all(),
            'niveaux' => DB::table('esbtp_matiere_niveau')
                ->where('matiere_id', $matiereId)
                ->pluck('niveau_etude_id')
                ->map(static fn ($id) => (int) $id)
                ->values()
                ->all(),
        ];
    }
}
