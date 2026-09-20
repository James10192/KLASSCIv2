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
     * Un bulletin porte une trentaine de notes par matiere ; sans ce memo, une
     * seule ECUE remplirait le journal de trente lignes identiques et le
     * rendrait illisible au moment ou il sert.
     *
     * LES DEUX METHODES LE PARTAGENT, MAIS PAS AVEC LA MEME CLE, et c'est ce
     * qui borne l'ensemble :
     *
     * - `matiereRetenue()`   -> "classeId:matiereId:provenance"
     * - `coherenceNonVerifiable()` -> "nv:provenance:" + la PORTEE que
     *   l'appelant declare, jamais son contexte entier.
     *
     * La distinction n'est pas cosmetique. Une premiere version construisait la
     * seconde cle sur tout le contexte, or quatre appelants y passent un
     * identifiant de LIGNE (`resultat_id`, `note_id`) : la cle devenait unique
     * a chaque tour, donc le memo ne dedupliquait rien et grossissait en
     * O(lignes lues). Une colonne orpheline sur une classe de soixante rendait
     * soixante `Log::warning` par generation — exactement le bruit que ce memo
     * existe pour eviter — et rien ne purgeait la table dans un `queue:work`
     * qui ne redemarre pas.
     *
     * Borne desormais par le nombre de portees reellement incoherentes, soit
     * une poignee de lignes par instance.
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
     * DEUX ARGUMENTS, ET C'EST DELIBERE. `$contexte` est ce qui part au
     * journal : il doit etre aussi precis que possible, identifiant de ligne
     * compris, sinon l'operateur ne retrouve pas l'enregistrement fautif.
     * `$portee` nomme les cles de ce contexte qui decident du dedoublonnage —
     * c'est-a-dire la granularite a laquelle on accepte de le redire.
     *
     * Les confondre est le defaut que cette signature corrige : construire la
     * cle sur le contexte entier rendait le memo inoperant chez les quatre
     * appelants qui passent un `resultat_id` ou un `note_id`, puisque chaque
     * ligne fabriquait sa propre cle. Le journal se remplissait, et le memo
     * avec — sans purge, dans un worker de file.
     *
     * Une cle de `$portee` absente du contexte ne leve pas — ce chemin sert
     * pendant la generation d'un bulletin sur huit instances, et une faute de
     * frappe ne doit pas casser l'impression. Elle ne compte pas non plus pour
     * nulle : ce serait confondre sous une cle vide des contextes differents,
     * donc SE TAIRE au lieu de devenir bavard. La portee incalculable retombe
     * sur le contexte entier, c'est-a-dire sur l'absence de dedoublonnage. Le
     * defaut penche du cote du bruit, jamais du silence.
     *
     * @param  array<string, scalar|null>  $contexte  ce qui part au journal
     * @param  list<string>  $portee  les cles de `$contexte` qui dedupliquent
     */
    public static function coherenceNonVerifiable(
        string $provenance,
        array $contexte,
        array $portee
    ): void {
        $manquantes = array_diff($portee, array_keys($contexte));

        // PORTEE INCALCULABLE -> ON NE DEDUPLIQUE PAS. Une premiere version
        // remplacait la cle manquante par une chaine vide : deux contextes
        // differents obtenaient alors la MEME cle, et le journal se taisait au
        // lieu de devenir bavard. C'est le mauvais sens — on perdait du signal
        // sur une faute de frappe, sans rien qui le dise. Retomber sur le
        // contexte entier rend le volume d'avant, donc visible.
        $cle = $manquantes === []
            ? 'nv:' . $provenance . ':' . implode(',', array_map(
                static fn (string $nom) => $nom . '=' . (string) ($contexte[$nom] ?? ''),
                $portee,
            ))
            : 'nv!' . $provenance . ':' . implode(',', array_map(
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
