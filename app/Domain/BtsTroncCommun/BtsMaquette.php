<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Support\Collection;

/**
 * Maquette BTS : quelles matieres sont prevues, et a quel semestre.
 *
 * La maquette vit sur le pivot `esbtp_matiere_filiere_niveau`, au grain
 * (matiere, filiere, niveau). Elle est SANS annee : c'est un referentiel de
 * cursus, pas une planification. Le planning general (`esbtp_planifications_
 * academiques`, qui est annuel) peut l'alimenter par un import explicite.
 *
 * CE QUI LA CONSOMME — c'est branche, contrairement a ce que ce bloc a
 * longtemps annonce. Outre l'ecran de maquette (`isRenseignee`), la
 * composition du bulletin passe par `BulletinSubjectRowsCompleter`, appele par
 * `BulletinService` avec le semestre de la periode, et la couverture des notes
 * par `ExpectedSubjectsResolver`. Renseigner un semestre CHANGE donc le
 * bulletin — mais seulement une fois les semestres valides, l'etat ETAT_COMPLET
 * ci-dessous etant ce qui ouvre la vanne.
 *
 * TROIS ETATS, ET NON DEUX. Un combo n'est « renseigne » que lorsque quelqu'un
 * a valide ses semestres : c'est la colonne `semestre_renseigne` qui le dit, pas
 * la presence d'un `semestre` non nul. Sans cette colonne, une maquette dont
 * toutes les matieres sont prevues aux deux semestres (donc `semestre = null`
 * partout) serait indiscernable d'une maquette jamais remplie, et repasser une
 * matiere de « semestre 1 » a « les deux » desactiverait la maquette entiere.
 *
 * A l'echelle d'une classe, l'etat est celui de TOUS ses combos : une classe de
 * specialite en compte deux (sa filiere, et la filiere de tronc commun parente).
 * Tant qu'un seul des deux reste indefini, l'etat est PARTIEL et la maquette
 * n'est pas appliquee : une matiere du combo non renseigne serait sinon
 * silencieusement absente du bulletin. On preserve alors le comportement
 * historique et on expose l'incertitude a l'appelant.
 *
 * BTS uniquement. Le LMD tient ses matieres par parcours -> UE -> ECUE.
 *
 * @see .claude/rules/lmd-bts-matieres-single-source.md
 */
final class BtsMaquette
{
    public const ETAT_AUCUN = 'aucun';

    public const ETAT_PARTIEL = 'partiel';

    public const ETAT_COMPLET = 'complet';

    /** @var array<string, array<int, array{semestres: list<int|null>}>> */
    private array $memo = [];

    public function __construct(
        private readonly BtsBulletinSubjectResolver $resolver,
        private readonly BulletinSubjectOrder $ordre,
    ) {}

    /** Un combo est renseigne des qu'une de ses lignes a ete validee. */
    public function isRenseignee(int $filiereId, int $niveauId): bool
    {
        return ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->where('semestre_renseigne', true)
            ->exists();
    }

    /**
     * Etat de la maquette pour une classe : aucun, partiel ou complet.
     *
     * Seul COMPLET autorise a composer un bulletin ou une couverture sur la
     * maquette.
     */
    public function etatPourClasse(ESBTPClasse $classe): string
    {
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return self::ETAT_AUCUN;
        }

        $combos = $this->comboFiliereIds($classe);
        $niveauId = (int) $classe->niveau_etude_id;

        $renseignes = 0;
        foreach ($combos as $filiereId) {
            if ($this->isRenseignee((int) $filiereId, $niveauId)) {
                $renseignes++;
            }
        }

        if ($renseignes === 0) {
            return self::ETAT_AUCUN;
        }

        return $renseignes === count($combos) ? self::ETAT_COMPLET : self::ETAT_PARTIEL;
    }

    public function estAppliquee(ESBTPClasse $classe): bool
    {
        return $this->etatPourClasse($classe) === self::ETAT_COMPLET;
    }

    /**
     * Matieres prevues pour la classe a ce semestre, dans l'ordre du bulletin.
     *
     * Union sur les combos : une matiere est prevue au semestre demande des
     * qu'un combo la prevoit — soit explicitement, soit parce qu'elle est
     * prevue aux deux semestres. Deux combos qui se contredisent additionnent
     * donc leurs semestres au lieu de s'annuler : une matiere reellement notee
     * ne peut pas disparaitre du bulletin a cause d'une saisie divergente.
     *
     * Si la maquette n'est pas appliquee, rend la liste complete du resolver :
     * exactement le comportement d'avant.
     *
     * @return Collection<int, \App\Models\ESBTPMatiere>
     */
    public function subjectsForClasseAndSemestre(ESBTPClasse $classe, int $semestre): Collection
    {
        $matieres = $this->ordre->orderedSubjectsForClasse($classe);

        if (! $this->estAppliquee($classe)) {
            return $matieres;
        }

        $semestresParMatiere = $this->semestresParMatiere($classe);

        return $matieres->filter(function ($matiere) use ($semestresParMatiere, $semestre) {
            return $this->prevueAu($semestresParMatiere[(int) $matiere->id] ?? null, $semestre);
        });
    }

    /**
     * Cette matiere est-elle prevue a ce semestre pour cette classe ?
     *
     * `null` quand la maquette n'est pas appliquee : l'appelant ne doit alors
     * rien conclure, surtout pas que la matiere en est exclue.
     */
    public function isPrevue(ESBTPClasse $classe, int $matiereId, int $semestre): ?bool
    {
        if (! $this->estAppliquee($classe)) {
            return null;
        }

        $semestresParMatiere = $this->semestresParMatiere($classe);

        if (! array_key_exists($matiereId, $semestresParMatiere)) {
            return false;
        }

        return $this->prevueAu($semestresParMatiere[$matiereId], $semestre);
    }

    /**
     * Semestres declares par matiere, tous combos confondus.
     *
     * `null` dans la liste = prevue aux deux semestres.
     *
     * @return array<int, list<int|null>>
     */
    private function semestresParMatiere(ESBTPClasse $classe): array
    {
        $cle = $classe->id.':'.$classe->filiere_id.':'.$classe->niveau_etude_id;

        if (isset($this->memo[$cle])) {
            return $this->memo[$cle];
        }

        $lignes = ESBTPMatiereFilierNiveau::query()
            ->whereIn('filiere_id', $this->comboFiliereIds($classe))
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->get(['matiere_id', 'semestre', 'semestre_renseigne']);

        $carte = [];

        foreach ($lignes as $ligne) {
            $matiereId = (int) $ligne->matiere_id;
            // Une ligne jamais validee vaut « les deux semestres » : une matiere
            // ajoutee au combo apres la validation reste visible partout.
            $semestre = $ligne->semestre_renseigne ? $ligne->semestre : null;
            $carte[$matiereId][] = $semestre === null ? null : (int) $semestre;
        }

        return $this->memo[$cle] = $carte;
    }

    /** @param  list<int|null>|null  $semestresDeclares */
    private function prevueAu(?array $semestresDeclares, int $semestre): bool
    {
        // Aucune ligne de pivot : la matiere vient du repli historique
        // (pivot `esbtp_classe_matiere`). On ne la retire pas.
        if ($semestresDeclares === null || $semestresDeclares === []) {
            return true;
        }

        foreach ($semestresDeclares as $declare) {
            if ($declare === null || $declare === $semestre) {
                return true;
            }
        }

        return false;
    }

    /** @return list<int> */
    private function comboFiliereIds(ESBTPClasse $classe): array
    {
        return $classe->filiere
            ? $classe->filiere->troncCommunUnionFiliereIds()
            : [(int) $classe->filiere_id];
    }
}
