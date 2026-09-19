<?php

declare(strict_types=1);

namespace App\Domain\Academique;

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
