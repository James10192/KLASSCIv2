<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Domain\Dispenses\DispenseLookup;
use App\Domain\Dispenses\Models\ESBTPDispense;
use App\Models\ESBTPClasse;
use App\Models\ESBTPResultatMatiere;
use Illuminate\Support\Collection;

/**
 * La liste definitive des lignes d'un bulletin, et l'etat de chacune.
 *
 * Avant ce composant, une matiere sans note n'apparaissait pas : le bulletin
 * d'un etudiant ne se comparait pas a celui de son voisin, et un professeur qui
 * n'avait pas rendu ses notes ne laissait aucune trace. Le document ne disait
 * pas ce qui manquait — il ne disait rien du tout.
 *
 * Ici, quand la maquette est renseignee, TOUTES les matieres qu'elle prevoit au
 * semestre figurent au bulletin, celles sans note portant le symbole de trou.
 * Et une matiere dont l'etudiant est dispense le dit, avec son motif.
 *
 * Deux garde-fous qui expliquent la forme du code :
 *
 * 1. **Rien ne disparait.** Une matiere reellement notee mais absente de la
 *    maquette reste au bulletin. La maquette ajoute des lignes, elle n'en
 *    retire jamais — une saisie de referentiel incomplete ne doit pas effacer
 *    un travail evalue.
 * 2. **Sans maquette, rien ne change.** Tant qu'elle n'est pas appliquee, le
 *    bulletin garde exactement les lignes d'avant. C'est la condition pour que
 *    ce lot soit sans effet sur les ecoles qui n'ont rien saisi.
 */
final class BulletinSubjectRowsCompleter
{
    public function __construct(
        private readonly BtsMaquette $maquette,
        private readonly BulletinSubjectOrder $ordre,
        private readonly DispenseLookup $dispenses,
    ) {}

    /**
     * @param  Collection<int, object>  $lignes  indexees par matiere_id
     * @param  int|null  $semestre  1, 2, ou null pour l'annuel
     * @param  callable(int): array{coefficient: int|float, type_formation: string|null}  $decorer
     * @return Collection<int, object>
     */
    public function completer(
        Collection $lignes,
        ESBTPClasse $classe,
        int $etudiantId,
        int $anneeId,
        ?int $semestre,
        callable $decorer,
    ): Collection {
        $lignes = $lignes->map(fn ($ligne) => $this->marquerNotee($ligne));

        $lignes = $this->ajouterLesMatieresPrevues($lignes, $classe, $semestre, $decorer);

        return $this->appliquerLesDispenses($lignes, $etudiantId, $anneeId, $semestre);
    }

    /** « semestre1 » → 1, « semestre2 » → 2, « annuel » et le reste → null. */
    public static function semestreDe(?string $periode): ?int
    {
        return match ($periode) {
            'semestre1' => 1,
            'semestre2' => 2,
            default => null,
        };
    }

    /**
     * Les matieres que la maquette prevoit et qui manquent au bulletin.
     *
     * Ne fait rien quand la maquette n'est pas appliquee, ni sur l'annuel : un
     * bulletin annuel agrege deux semestres, et « prevue au semestre » n'y a pas
     * de sens unique — le completer par une union produirait des trous que
     * personne n'a demandes.
     *
     * @param  Collection<int, object>  $lignes
     * @return Collection<int, object>
     */
    private function ajouterLesMatieresPrevues(
        Collection $lignes,
        ESBTPClasse $classe,
        ?int $semestre,
        callable $decorer,
    ): Collection {
        if ($semestre === null || ! $this->maquette->estAppliquee($classe)) {
            return $lignes;
        }

        $prevues = $this->maquette->subjectsForClasseAndSemestre($classe, $semestre);

        foreach ($prevues as $matiere) {
            $matiereId = (int) $matiere->id;

            if ($lignes->has($matiereId)) {
                continue;
            }

            $decor = $decorer($matiereId);

            $lignes->put($matiereId, (object) [
                'id' => $matiereId,
                'matiere_id' => $matiereId,
                'matiere' => $matiere,
                'notes' => [],
                'moyenne' => null,
                'statut' => ESBTPResultatMatiere::STATUT_NON_NOTE,
                'motif_dispense' => null,
                'dispense_id' => null,
                'coefficient' => $decor['coefficient'] ?? 1,
                'rang' => '-',
                'appreciation' => '',
                'type_formation' => $decor['type_formation'] ?? null,
            ]);
        }

        return $this->ordre->sort($lignes, $this->ordre->rankMapForClasse($classe));
    }

    /**
     * Une matiere dispensee ne porte plus de moyenne, meme si des notes existent.
     *
     * C'est le sens meme d'une dispense : la matiere ne compte pas pour cet
     * etudiant. Garder la moyenne la ferait entrer dans le calcul, et la
     * dispense n'aurait aucun effet. Les notes elles-memes ne sont pas touchees
     * en base — seule la photographie du bulletin les ecarte, et une revocation
     * les fait revenir a la generation suivante.
     *
     * @param  Collection<int, object>  $lignes
     * @return Collection<int, object>
     */
    private function appliquerLesDispenses(
        Collection $lignes,
        int $etudiantId,
        int $anneeId,
        ?int $semestre,
    ): Collection {
        $actives = $this->dispenses->pourEtudiantEtSemestre($etudiantId, $anneeId, $semestre);

        if ($actives === []) {
            return $lignes;
        }

        return $lignes->map(function ($ligne) use ($actives) {
            $matiereId = (int) ($ligne->matiere_id ?? 0);
            $dispense = $actives[$matiereId] ?? null;

            if (! $dispense instanceof ESBTPDispense) {
                return $ligne;
            }

            $ligne->moyenne = null;
            $ligne->statut = ESBTPResultatMatiere::STATUT_DISPENSE;
            $ligne->motif_dispense = $dispense->motif;
            $ligne->dispense_id = (int) $dispense->id;
            $ligne->appreciation = '';
            $ligne->rang = '-';

            return $ligne;
        });
    }

    /** Toute ligne deja presente porte une note, sauf a ce qu'on la contredise ensuite. */
    private function marquerNotee(object $ligne): object
    {
        $ligne->statut ??= ESBTPResultatMatiere::STATUT_NOTE;
        $ligne->motif_dispense ??= null;
        $ligne->dispense_id ??= null;

        return $ligne;
    }
}
