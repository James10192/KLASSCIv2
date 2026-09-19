<?php

declare(strict_types=1);

namespace App\Domain\Academique;

use Illuminate\Database\Eloquent\Builder;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Facades\Log;

/**
 * Une matiere appartient-elle au systeme academique de la classe ?
 *
 * `esbtp_matieres` est partagee par le BTS et le LMD : une matiere portant une
 * `unite_enseignement_id` est un element constitutif (ECUE) du LMD, les autres
 * sont des matieres BTS. Une classe BTS attend les secondes, une classe LMD les
 * premieres. L'inverse trahit un selecteur qui a propose la mauvaise liste.
 *
 * POURQUOI CE PREDICAT VIT ICI, ET NON DANS CHAQUE APPELANT. Le chantier de
 * septembre 2026 a d'abord seme douze filtres `btsOnly()` chez les lecteurs, sur
 * neuf fichiers, en quatre passes de revue — et chaque passe trouvait une porte
 * que la precedente avait manquee. La lecon retenue : une seule phrase, un seul
 * endroit, et les appelants la citent.
 *
 * CE QUE LE PREDICAT NE DIT PAS. Il ne dit pas si la ligne est *voulue* : une
 * evaluation heritee, anterieure au garde du 20 aout 2026, est incoherente sans
 * etre illegitime — quelqu'un a saisi ces notes. D'ou la regle de conduite qui
 * accompagne chaque usage : **a l'ecriture on refuse, a la lecture on ecarte en
 * le journalisant**. Ecarter en silence est le defaut que ce chantier reproche
 * par ailleurs a la fuite qu'il corrige (piege #12 de
 * `klassci-debugging-discipline.md` : un rattrapage muet ne se cherche meme pas).
 *
 * LE NUL N'EST PAS « LMD ». `esbtp_classes.systeme_academique` est nullable, et
 * les classes BTS historiques l'ont nul. On compare donc a `'LMD'`, jamais a
 * `'BTS'` : sans cela une classe BTS ancienne basculerait cote LMD et le
 * predicat s'inverserait pour elle.
 */
final class CoherenceSystemeAcademique
{
    /** Valeur de `esbtp_classes.systeme_academique` qui designe le LMD. */
    public const LMD = 'LMD';

    public static function classeEstLmd(?string $systemeAcademique): bool
    {
        return ($systemeAcademique ?? '') === self::LMD;
    }

    public static function matiereEstEcue(?int $uniteEnseignementId): bool
    {
        return $uniteEnseignementId !== null;
    }

    /**
     * La matiere a-t-elle sa place dans cette classe ?
     *
     * Vrai quand les deux sont du meme cote : classe LMD avec ECUE, ou classe
     * BTS avec matiere BTS.
     */
    public static function estCoherente(?string $systemeAcademique, ?int $uniteEnseignementId): bool
    {
        return self::classeEstLmd($systemeAcademique) === self::matiereEstEcue($uniteEnseignementId);
    }



    /**
     * Restreint une requete aux lignes INCOHERENTES, pour les recenser.
     *
     * Le meme `where()` imbrique etait ecrit trois fois : deux en SQL dans
     * `CLIMaintenanceController` (evaluations, puis moyennes manuelles), une en
     * PHP dans `evaluationChangeMatiere()`. La classe qui porte le predicat
     * canonique en ajoutait donc elle-meme une quatrieme copie le jour de sa
     * creation — exactement ce que son propre docblock dit avoir appris.
     *
     * Les deux tables sont passees en parametre parce que seule la table des
     * lignes change (`esbtp_evaluations` ou `esbtp_resultats`) : la jointure
     * sur les classes et les matieres, elle, est la meme.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public static function contraindreLIncoherence(
        Builder $query,
        string $tableClasses = 'esbtp_classes',
        string $tableMatieres = 'esbtp_matieres'
    ): Builder {
        return $query->where(function ($q) use ($tableClasses, $tableMatieres) {
            // Classe BTS portant une ECUE. `!= LMD` ET `IS NULL`, jamais
            // `= 'BTS'` : la colonne est nullable et les classes BTS
            // historiques l'ont nulle.
            $q->where(function ($bts) use ($tableClasses, $tableMatieres) {
                $bts->where(function ($sys) use ($tableClasses) {
                    $sys->where($tableClasses.'.systeme_academique', '!=', self::LMD)
                        ->orWhereNull($tableClasses.'.systeme_academique');
                })->whereNotNull($tableMatieres.'.unite_enseignement_id');
            })
            // Classe LMD portant une matiere BTS.
            ->orWhere(function ($lmd) use ($tableClasses, $tableMatieres) {
                $lmd->where($tableClasses.'.systeme_academique', self::LMD)
                    ->whereNull($tableMatieres.'.unite_enseignement_id');
            });
        });
    }

    /**
     * Memo des ecarts deja journalises, par processus.
     *
     * Cle : "classeId:matiereId:provenance". Un bulletin porte une trentaine de
     * notes par matiere ; sans ce memo, une seule ECUE remplirait le journal de
     * trente lignes identiques et le rendrait illisible au moment ou il sert.
     * Borne par le nombre de couples (classe, matiere) reellement incoherents,
     * soit une poignee de lignes par instance — pas de fuite memoire a craindre
     * dans un worker de file d'attente.
     *
     * @var array<string, true>
     */
    private static array $ecartsJournalises = [];

    /**
     * La matiere a-t-elle sa place dans cette classe ? Sinon, le dire au journal.
     *
     * ON ECARTE, ON NE REFUSE PAS, et c'est la difference avec les deux gardes
     * d'ecriture (`ESBTPEvaluation`, `ESBTPResultat`). Une ligne incoherente
     * anterieure a ces gardes n'est pas illegitime : quelqu'un a saisi ces
     * notes, elles sont mal rangees. Refuser de generer le bulletin punirait
     * l'eleve pour une erreur de saisie qui n'est pas la sienne.
     *
     * Mais l'ecarter en SILENCE est le defaut que ce depot a deja paye cher
     * (piege #12 de `klassci-debugging-discipline.md` : un rattrapage muet ne se
     * cherche meme pas). Une moyenne qui bouge sans explication est pire qu'une
     * moyenne fausse : on ne sait meme pas qu'il faut chercher.
     */
    public static function matiereRetenue(
        ESBTPMatiere $matiere,
        ESBTPClasse $classe,
        string $provenance
    ): bool {
        if (self::estCoherente($classe->systeme_academique, $matiere->unite_enseignement_id)) {
            return true;
        }

        $cle = $classe->id . ':' . $matiere->id . ':' . $provenance;

        if (! isset(self::$ecartsJournalises[$cle])) {
            self::$ecartsJournalises[$cle] = true;

            Log::warning('Matiere ecartee : etrangere au systeme academique de la classe.', [
                'classe_id' => $classe->id,
                'classe' => $classe->name,
                'systeme_academique' => $classe->systeme_academique,
                'matiere_id' => $matiere->id,
                'matiere' => $matiere->name,
                'unite_enseignement_id' => $matiere->unite_enseignement_id,
                'provenance' => $provenance,
            ]);
        }

        return false;
    }


    /**
     * « Je ne peux pas verifier la coherence ici, et je le dis une fois. »
     *
     * La jumelle de `matiereRetenue()`, pour le cas ou le rapprochement est
     * IMPOSSIBLE : classe introuvable, note sans matiere resolvable, ligne
     * enregistree sans classe de reference. On ne peut ni retenir ni ecarter,
     * donc on laisse passer — et on le dit, sinon personne ne saura jamais
     * qu'une moyenne a ete calculee sans garde.
     *
     * CENTRALISEE POUR LA MEME RAISON QUE LE PREDICAT. Cette phrase etait
     * recopiee a la main dans huit methodes, sous huit libelles differents,
     * dont cinq gardees par leur propre memo booleen local. Un operateur qui
     * cherchait ce cas dans les journaux devait connaitre les huit
     * formulations, et chaque nouvel appelant en inventait une neuvieme.
     * La lecon du chantier, ecrite en tete de cette classe, vaut ici aussi :
     * une seule phrase, un seul endroit, et les appelants la citent.
     *
     * Le memo est celui de `matiereRetenue()`, volontairement : les deux cas
     * surviennent dans les memes boucles, sur les memes lots, et se bornent
     * donc de la meme facon. La cle est construite sur la provenance et le
     * contexte, faute de couple (classe, matiere) — c'est precisement ce qui
     * manque quand cette methode est appelee.
     *
     * @param  array<string, scalar|null>  $contexte
     */
    public static function coherenceNonVerifiable(string $provenance, array $contexte): void
    {
        $cle = 'nv:' . $provenance . ':' . implode(',', array_map(
            static fn ($v) => (string) $v,
            $contexte,
        ));

        if (isset(self::$ecartsJournalises[$cle])) {
            return;
        }

        self::$ecartsJournalises[$cle] = true;

        Log::warning(
            'Coherence non verifiable : rapprochement impossible, la ligne est conservee.',
            $contexte + ['provenance' => $provenance],
        );
    }

    /** Vide le memo — reservee aux tests, qui rejouent le meme couple. */
    public static function oublierLesEcartsJournalises(): void
    {
        self::$ecartsJournalises = [];
    }

    /** Message rendu a l'utilisateur quand une ecriture est refusee. */
    public static function messageDeRefus(
        ?string $systemeAcademique,
        string $nomDeLaClasse,
        string $nomDeLaMatiere
    ): string {
        return self::classeEstLmd($systemeAcademique)
            ? "La classe « {$nomDeLaClasse} » est en LMD : elle attend une ECUE, or « {$nomDeLaMatiere} » est une matière BTS."
            : "La classe « {$nomDeLaClasse} » est en BTS : elle attend une matière BTS, or « {$nomDeLaMatiere} » est une ECUE du LMD.";
    }
}
