<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ordre des matieres sur le bulletin BTS.
 *
 * Trois sources, de la plus precise a la plus large :
 *  1. l'ordre propre au combo (filiere x niveau), colonne `ordre_bulletin` du
 *     pivot `esbtp_matiere_filiere_niveau` ;
 *  2. l'ordre general, colonne `esbtp_matieres.ordre_bulletin` ;
 *  3. rien : le rang reste indefini.
 *
 * Une classe de specialite compte deux combos (sa filiere et la filiere de
 * tronc commun parente). Quand les deux portent un rang propre, c'est le plus
 * petit qui gagne : la matiere garde la place que le tronc commun lui donnait.
 *
 * GARANTIE DE NON-REGRESSION : tant qu'aucun rang n'est defini pour la classe,
 * `sort()` rend la collection telle quelle. Aucun bulletin deja distribue ne
 * change d'ordre tant que l'ecole n'a pas pose un premier rang.
 *
 * Zero et null valent tous deux « non defini » : toutes les matieres BTS
 * existantes portent `ordre_bulletin = 0` (defaut de la migration de mars 2026).
 *
 * BTS uniquement. Le LMD ordonne ses UE/ECUE par ses propres pivots.
 *
 * @see .claude/rules/lmd-bts-bulletin-separation.md
 */
final class BulletinSubjectOrder
{
    /** Borne haute du stockage : `unsignedSmallInteger`. */
    public const RANG_MAX = 65535;

    /**
     * Rangs deja resolus, par classe.
     *
     * La generation d'une classe appelle `rankMapForClasse` une fois par
     * ETUDIANT, pour une carte qui ne depend que de la classe : sans cette
     * memoire, quarante bulletins declenchaient quarante fois les memes
     * requetes. L'instance vit le temps d'une requete HTTP (elle est injectee
     * dans BulletinService), donc la carte ne peut pas devenir perimee entre
     * deux etudiants d'un meme export.
     *
     * @var array<string, array<int, int|null>>
     */
    private array $memo = [];

    public function __construct(private readonly BtsBulletinSubjectResolver $resolver) {}

    /**
     * Rang effectif de chaque matiere de la classe.
     *
     * Deux requetes au total (les matieres, puis le pivot), jamais une par matiere.
     *
     * @return array<int, int|null> matiere_id => rang effectif (null = non defini)
     */
    public function rankMapForClasse(ESBTPClasse $classe, ?int $semestre = null): array
    {
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return [];
        }

        return $this->rankMapForFiliereNiveau(
            (int) $classe->filiere_id,
            (int) $classe->niveau_etude_id,
            $classe->filiere?->troncCommunUnionFiliereIds(),
            $semestre
        );
    }


    /**
     * Rang effectif des matieres d'un couple filiere x niveau.
     *
     * C'est LE calcul du rang, et le seul : l'ecran de maquette et le bulletin
     * l'appellent tous les deux. Les avoir calcules separement les faisait
     * diverger des qu'une classe de specialite heritait d'un rang pose sur son
     * tronc commun — l'ecran montrait un ordre, le PDF un autre, ce que ce lot
     * existe justement pour supprimer.
     *
     * @param  list<int>|null  $unionFiliereIds  combos a prendre en compte ; a defaut, la filiere seule
     * @return array<int, int|null>
     */
    public function rankMapForFiliereNiveau(int $filiereId, int $niveauId, ?array $unionFiliereIds = null, ?int $semestre = null): array
    {
        $combos = $unionFiliereIds ?: [$filiereId];
        sort($combos);
        $cle = implode(',', $combos).':'.$niveauId.':'.($semestre ?? '-');

        if (isset($this->memo[$cle])) {
            return $this->memo[$cle];
        }

        $lignes = ESBTPMatiereFilierNiveau::query()
            ->with('matiere:id,ordre_bulletin')
            ->whereIn('filiere_id', $combos)
            ->where('niveau_etude_id', $niveauId)
            ->get(['matiere_id', 'ordre_bulletin']);

        $carte = [];

        foreach ($lignes as $ligne) {
            $matiereId = (int) $ligne->matiere_id;
            $propre = self::rang($ligne->ordre_bulletin);

            // Le plus petit rang propre de l'union gagne : la matiere garde la
            // place que le tronc commun lui donnait.
            if ($propre !== null) {
                $carte[$matiereId] = isset($carte[$matiereId]) && $carte[$matiereId] !== null
                    ? min($carte[$matiereId], $propre)
                    : $propre;

                continue;
            }

            if (! array_key_exists($matiereId, $carte)) {
                $carte[$matiereId] = self::rang($ligne->matiere->ordre_bulletin ?? null);
            }
        }

        // Une place propre au semestre PRIME sur celle du pivot : c'est le seul
        // endroit ou une matiere enseignee aux deux semestres peut occuper deux
        // rangs differents. Sans ligne ici, rien ne change — une ecole qui n'a
        // jamais renseigne de place par semestre garde l'ordre qu'elle avait.
        if ($semestre !== null) {
            foreach ($this->placesDuSemestre($combos, $niveauId, $semestre) as $matiereId => $rang) {
                $carte[$matiereId] = $rang;
            }
        }

        return $this->memo[$cle] = $carte;
    }

    /**
     * Places propres a un semestre, par matiere.
     *
     * Comme pour le pivot, le plus petit rang de l'union l'emporte : une classe
     * de specialite garde la place que son tronc commun donnait a la matiere.
     *
     * @param  list<int>  $combos
     * @return array<int, int>
     */
    private function placesDuSemestre(array $combos, int $niveauId, int $semestre): array
    {
        $places = [];

        // Le code arrive avant la migration : la methode de deploiement est
        // pull, puis vidage des caches, puis migrate. Sans cette garde, tout
        // bulletin de semestre leve une erreur SQL dans cette fenetre, et le
        // controleur etudiant l'avale en silence en affichant << Non
        // disponible >>. Une instance qui n'a pas encore migre doit voir
        // l'ordre du pivot, pas un bulletin vide.
        if (! $this->tableDesPlacesExiste()) {
            return $places;
        }

        $lignes = ESBTPMaquettePlaceSemestre::query()
            ->whereIn('filiere_id', $combos)
            ->where('niveau_etude_id', $niveauId)
            ->where('semestre', $semestre)
            ->get(['matiere_id', 'ordre_bulletin']);

        foreach ($lignes as $ligne) {
            $rang = self::rang($ligne->ordre_bulletin);
            if ($rang === null) {
                continue;
            }

            $matiereId = (int) $ligne->matiere_id;
            $places[$matiereId] = isset($places[$matiereId]) ? min($places[$matiereId], $rang) : $rang;
        }

        return $places;
    }

    /**
     * La table des places par semestre est-elle deja migree ?
     *
     * Retenu par connexion, et seul un true est retenu : une absence doit
     * pouvoir etre reinterrogee, sinon la migration ne serait jamais vue
     * dans un processus long.
     */
    private function tableDesPlacesExiste(): bool
    {
        static $vue = [];

        $connexion = (new ESBTPMaquettePlaceSemestre())->getConnectionName() ?? DB::getDefaultConnection();
        $cle = $connexion.'|'.DB::connection($connexion)->getDatabaseName();

        if (($vue[$cle] ?? false) === true) {
            return true;
        }

        $existe = DB::connection($connexion)
            ->getSchemaBuilder()
            ->hasTable((new ESBTPMaquettePlaceSemestre())->getTable());

        // Un repli qui degrade l'affichage doit dire ce qu'il a rattrape.
        // Pendant le deploiement, cette absence dure quelques secondes et la
        // ligne est anodine. Si elle revient hors de cette fenetre — base
        // restauree a moitie, migration interrompue —, c'est la seule trace
        // qui expliquera pourquoi les bulletins de semestre sont repasses sur
        // l'ordre general. Une fois par processus, pas une fois par bulletin.
        if (! $existe && ! isset($vue[$cle])) {
            Log::warning('Ordre des bulletins : table des places par semestre absente, repli sur le referentiel general.', [
                'connexion' => $connexion,
                'table' => (new ESBTPMaquettePlaceSemestre())->getTable(),
            ]);
        }

        return $vue[$cle] = $existe;
    }

    /**
     * Oublie les rangs deja resolus.
     *
     * A appeler apres une ecriture de maquette dans le meme cycle de requete,
     * sinon la memoire rendrait l'etat d'avant.
     */
    public function oublierLeCache(): void
    {
        $this->memo = [];
    }

    /**
     * Trie une collection de lignes porteuses d'une matiere.
     *
     * Les cles sont preservees : `$resultatsParMatiere` reste indexe par
     * matiere_id, ce dont dependent ses consommateurs.
     *
     * @param  array<int, int|null>  $rankMap
     */
    public function sort(Collection $rows, array $rankMap): Collection
    {
        if ($rows->isEmpty() || ! $this->auMoinsUnRang($rankMap)) {
            return $rows;
        }

        return $rows->sortBy(function ($row, $key) use ($rankMap) {
            $matiereId = $this->matiereIdDe($row, $key);
            $rang = $matiereId !== null ? ($rankMap[$matiereId] ?? null) : null;

            // Rangs definis d'abord, puis le rang, puis le nom, puis l'identifiant :
            // deux matieres au meme rang gardent un ordre stable d'un etudiant a l'autre.
            return [
                $rang === null ? 1 : 0,
                $rang ?? 0,
                $this->nomDe($row),
                $matiereId ?? 0,
            ];
        });
    }

    /**
     * Matieres de la classe, dans l'ordre du bulletin.
     *
     * @return Collection<int, \App\Models\ESBTPMatiere>
     */
    public function orderedSubjectsForClasse(ESBTPClasse $classe): Collection
    {
        // Une seule resolution des matieres : `rankMapForClasse` lit le pivot,
        // il ne redemande pas la liste au resolver.
        return $this->sort(
            $this->resolver->subjectsForClasse($classe),
            $this->rankMapForClasse($classe)
        );
    }

    /**
     * Zero, null et hors bornes valent « non defini ».
     *
     * Publique et statique a dessein : l'ecran de maquette applique la meme
     * regle. La laisser privee obligeait a la recopier, et deux copies d'une
     * regle finissent toujours par diverger sans que rien n'echoue.
     */
    public static function rang(mixed $valeur): ?int
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        $rang = (int) $valeur;

        return ($rang >= 1 && $rang <= self::RANG_MAX) ? $rang : null;
    }

    /** @param  array<int, int|null>  $rankMap */
    private function auMoinsUnRang(array $rankMap): bool
    {
        foreach ($rankMap as $rang) {
            if ($rang !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Identifiant de matiere d'une ligne.
     *
     * Les lignes de bulletin portent `matiere_id` ; un modele `ESBTPMatiere`
     * porte son propre `id`. La cle du tableau sert de dernier recours.
     */
    private function matiereIdDe(mixed $row, mixed $key): ?int
    {
        foreach (['matiere_id', 'id'] as $attribut) {
            $valeur = is_object($row) ? ($row->{$attribut} ?? null) : ($row[$attribut] ?? null);

            if ($valeur !== null && $valeur !== '') {
                return (int) $valeur;
            }
        }

        return is_numeric($key) ? (int) $key : null;
    }

    private function nomDe(mixed $row): string
    {
        $nom = null;

        if (is_object($row)) {
            $nom = $row->matiere->name ?? $row->matiere->nom ?? $row->name ?? $row->nom ?? null;
        } elseif (is_array($row)) {
            $nom = $row['name'] ?? $row['nom'] ?? null;
        }

        return mb_strtolower((string) ($nom ?? ''), 'UTF-8');
    }
}
