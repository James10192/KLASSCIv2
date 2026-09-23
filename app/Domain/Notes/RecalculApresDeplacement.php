<?php

namespace App\Domain\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Recalcule les agregats d'`esbtp_resultats` apres qu'une evaluation a change
 * de classe, de matiere ou de periode.
 *
 * ## Pourquoi cette classe existe
 *
 * Les endroits qui deplacent une evaluation propagent la colonne denormalisee
 * `esbtp_notes.matiere_id` (ou `.semestre`) par un `update()` de
 * **query builder** :
 *
 *     ESBTPNote::where('evaluation_id', $id)->update(['matiere_id' => $cible->id]);
 *
 * Un `update()` de query builder **n'emet aucun evenement Eloquent**. Donc
 * `ESBTPNoteObserver::saved()` ne tourne pas, `RecomputeStudentResultatJob`
 * n'est jamais dispatche, et les deux coordonnees restent figees sur leur
 * ancienne valeur : l'ancienne matiere garde une moyenne qu'aucune note ne
 * justifie plus, la nouvelle garde une moyenne qui ignore les notes arrivees.
 *
 * Ce n'est pas un defaut d'affichage. `BtsCurrentResultSnapshotService` et
 * `MoyennesDeLApercu` donnent la **preseance a la ligne enregistree** : quand
 * `esbtp_resultats` porte une valeur, elle ecrase celle calculee depuis les
 * notes. L'agregat perime gagne donc sur les notes, en silence.
 *
 * Mesure du 2026-09-20 sur esbtp-abidjan, apres un deplacement : eleve 149,
 * matiere 14, semestre2 — l'agregat disait **15**, les cinq notes (10, 20, 10,
 * 18, 20) disent **15,6**. Le 18 etait en base, au bon endroit, et avale.
 *
 * ## Le piege du zero
 *
 * Une coordonnee que le deplacement a videe n'a plus rien a moyenner, et le
 * job y ecrirait **0/20** sur une matiere que l'eleve n'a plus. Ce garde ne
 * vit pas ici : il est dans {@see PerimetreDeRecalcul::recalculerUnCouple()},
 * par ou passent aussi `notes:recompute` et l'endpoint de rattrapage. Il
 * n'existait d'abord qu'ici — et le rattrapage que cette classe conseille
 * reecrivait le 0/20 qu'elle venait de refuser.
 *
 * Une ligne ainsi laissee est **signalee, jamais touchee** : son sort est une
 * decision d'ecole (`.claude/rules/rien-en-dur.md`, « le cas particulier du
 * zero »). Le nettoyage deja livre la propose a la suppression depuis le
 * pre-controle de la generation des bulletins — sauf quand il y reste des
 * absences, cas qu'il ne voit pas (`reste = notes_non_comptees`).
 *
 * ## Synchrone, et pas sur la file
 *
 * Le job est execute **sur place** (`dispatchSync`), pas dispatche. Deux
 * raisons : l'operateur qui vient de deplacer une evaluation doit voir l'ecran
 * juste tout de suite, et surtout rien ne prouve qu'un worker tourne sur les
 * instances mutualisees — `config/queue.php` vaut `database` par defaut et
 * `app/Console/Kernel.php` ne planifie aucun `queue:work`. Dispatcher aurait
 * donne un correctif qui a l'air pose et ne s'execute jamais.
 *
 * Le volume est borne par construction : les eleves notes sur **une** seule
 * evaluation, au plus deux coordonnees chacun.
 *
 * ## Combien de deplaceurs, et lesquels — TROUVES A CE JOUR
 *
 * **Ce compte a ete faux trois fois, chaque fois publie comme definitif** : la
 * premiere livraison annoncait « les deux endroits » (les deux qui changent la
 * matiere), la deuxieme « quatre » (en ajoutant les deux qui changent la
 * periode), la troisieme « cinq » — et « cinq » ne satisfaisait pas la
 * definition que ce docbloc venait d'ecrire, laquelle parle de tout `update()`
 * de query builder sur ces colonnes, pas seulement de ceux qui deplacent une
 * evaluation.
 *
 * Alors disons ce que le tableau compte : **les endroits qui changent les
 * coordonnees d'une EVALUATION**. Ils sont sept, `MergeDuplicateEcue` n'est pas
 * branche ici, et la liste est celle des sites **trouves a ce jour** — pas celle
 * des sites existants (`klassci-debugging-discipline.md`, piege #14). Les deux
 * derniers de la liste ont ete trouves APRES que ce docbloc a dit « cinq » :
 * le devoir d'une seance, et l'annee donnee a une evaluation qui n'en avait pas.
 *
 * | deplaceur | ce qu'il change | branche sur cette classe |
 * |---|---|---|
 * | `ESBTPEvaluationController::update()` | matiere, classe, periode | oui |
 * | `CLIEvaluationMatiereController::evaluationChangeMatiere()` | matiere | oui |
 * | `CLIEvaluationDeplacementController::deplacer()` | periode, en lot | oui |
 * | `CLIEvaluationPeriodeController::repair()` | periode, en lot | oui |
 * | `AlignementDuDevoir` (seance de devoir modifiee) | matiere ; periode quand la date change | oui |
 * | `CheckEvaluationsAnnees` (`esbtp:check-evaluations-annees`) | annee, depuis nulle : arrivee seule | oui |
 * | `MergeDuplicateEcue` (sous `force`) | matiere, en masse | **non** |
 *
 * Et trois ecritures qui ne DEPLACENT rien mais font entrer ou sortir des
 * notes d'une moyenne — l'annulation les retire, la reactivation les remet :
 * `ESBTPEvaluationController::cancel()`, `restore()` (les boutons de la liste)
 * et `updateStatus()` (route sans ecran). Branchees par
 * {@see apresChangementDeStatut()}, puisque `pour()` compare deux coordonnees
 * et n'en voit ici qu'une.
 *
 * **Restent sans recalcul, trouves a ce jour** — et ce sont des ecritures qui
 * changent une moyenne sans deplacer ni exclure une note :
 * - la SUPPRESSION d'une evaluation encore brouillon ou planifiee qui porte
 *   deja des notes (`ESBTPEvaluationController::destroy()`,
 *   `ESBTPSeanceCoursController::destroy()`) ;
 * - le changement de BAREME ou de COEFFICIENT d'une evaluation notee
 *   (`ESBTPEvaluationController::update()` et `quickUpdate()`) : les deux
 *   entrent dans le calcul (`ESBTPNote::enChargeUtilePourLeCalcul()`).
 *
 * `MergeDuplicateEcue` (`app/Domain/LMD/Actions/MergeDuplicateEcue.php`) reparente
 * `esbtp_evaluations.matiere_id` ET `esbtp_notes.matiere_id` vers l'ECUE
 * canonique, puis met l'absorbee de cote (soft-delete). Il ne recalcule rien.
 * Il n'est pas corrige dans ce lot a dessein : c'est un autre domaine (la
 * reconciliation LMD, dont les agregats sont `esbtp_lmd_resultat_ecue`), il est
 * garde par un drapeau `force`, et sur une instance saine
 * `ESBTPEvaluation::booted()` refuse deja qu'une ECUE soit evaluee dans une
 * classe BTS — donc il ne devrait pas croiser `esbtp_resultats`. « Ne devrait
 * pas » n'est pas « ne peut pas » : sur une instance portant des lignes
 * heritees, il laisserait le meme agregat perime. C'est un chantier a lui, pas
 * une ligne a glisser ici.
 *
 * ## Les VOISINS : ils ecrivent les memes colonnes sans deplacer d'evaluation
 *
 * Ils ne sont pas dans le tableau — ils ne bougent aucune evaluation — mais ils
 * repondent a la definition d'en tete, et les ignorer ferait mentir ce docbloc :
 *
 * - `app/Console/Commands/Evaluations/SyncNotesScopeCommand.php` : `update()` de
 *   query builder sur `classe_id`, `matiere_id` ET `semestre` a la fois, sans
 *   aucun recalcul. Il **realigne** les notes sur leur evaluation apres coup —
 *   et c'est l'outil que ce docbloc recommande plus haut pour le menage. Un
 *   operateur qui le lance pour rattraper un deplacement ancien remet
 *   `esbtp_notes` d'aplomb et laisse `esbtp_resultats` perime : le defaut meme
 *   de ce chantier, par la porte du remede.
 * - `app/Console/Commands/SynchronizeNotesPeriodes.php` : passe par `save()`,
 *   donc par l'observer — mais celui-ci `dispatch()` sur la FILE, dont rien ne
 *   prouve qu'un ouvrier la consomme (voir plus bas).
 *
 * Aucun des deux n'est branche ici, a dessein : `sync-notes` tourne sans bornes
 * sur l'ecole entiere, et y ajouter un recalcul synchrone par note est
 * exactement ce que le plafond de cette classe cherche a eviter.
 *
 * ## Pourquoi pas un observer sur `ESBTPEvaluation`
 *
 * C'est la premiere question que pose un lecteur, et le depot s'est justement
 * ecrit la lecon inverse dans `lmd-ecue-leak-bts-picker.md` : « le garde est a
 * l'ECRITURE, pas en lecture », apres quatre passes de filtres semes chez les
 * lecteurs. Un `updated()` sur les quatre coordonnees couvrirait les chemins
 * Eloquent sans un seul appel a se rappeler.
 *
 * Trois raisons de ne pas l'avoir fait, et elles tiennent aux deux endpoints en
 * lot : ils ont besoin d'un plafond et d'un compte-rendu **agreges**, qu'un hook
 * ligne a ligne ne peut pas rendre ; leurs `save()` sont DANS une transaction
 * alors que le recalcul est volontairement hors transaction ; et
 * `MergeDuplicateEcue` passe par `DB::table()` brut, qu'aucun observer n'attrape. Un
 * observer reste souhaitable pour les chemins Eloquent — il n'est simplement
 * pas suffisant, et ce n'est pas le geste de ce lot.
 */
final class RecalculApresDeplacement
{
    /** Valeur ecrite dans `esbtp_resultats_recompute_log.source`. */
    public const SOURCE = 'deplacement';

    /**
     * Borne GLOBALE de notes recalculees dans une requete, tous perimetres
     * confondus.
     *
     * Le recalcul est hors transaction a dessein : les evaluations sont **deja
     * enregistrees**. Si la requete meurt sur le delai d'attente, on garde des
     * evaluations deplacees, des agregats rafraichis a moitie, et surtout
     * `perimetres_reportes` — tout l'objet de ce mecanisme — n'arrive JAMAIS,
     * puisque la reponse n'arrive pas. Le nombre de classes d'un lot n'etant
     * borne nulle part, seule une borne sur le total tient.
     *
     * **Les perimetres sont servis du plus leger au plus lourd.** Le budget se
     * consomme dans l'ordre : servi dans l'ordre d'arrivee, une classe lourde
     * placee en tete l'epuisait et faisait reporter toutes les classes legeres
     * derriere elle. Un plafond PAR CLASSE avait d'abord ete pose pour cela ;
     * egal a la borne globale, il ne servait plus a rien, et le retirer ne
     * faisait tomber aucun test. Le tri, lui, garantit la propriete.
     *
     * **La valeur : mesuree, et abaissee de 1200 a 400.** Un recalcul coute
     * **22 requetes et ~17 ms par eleve**, lineairement (mesure locale, MariaDB
     * sur la meme machine : 10 eleves 0,17 s, 40 eleves 0,67 s). Une note
     * deplacee declenche un ou deux recalculs : 17 a 34 ms par note. A 1200,
     * cela faisait **20 a 40 s en local** — au-dessus des 30 s au-dela
     * desquelles le binaire `klassci` abandonne la requete
     * (`feature-delivery-methodology.md`, phase 12). A 400 : 7 a 14 s en
     * local, soit une marge d'un facteur deux pour un hebergement plus lent —
     * facteur qui, lui, n'est pas mesure sur LWS.
     *
     * **Remesure apres l'ajout du garde** (diagnostic avant chaque recalcul) :
     * **11 requetes et ~8-9 ms par couple recalcule** (10 puis 50 couples,
     * meme machine). Un eleve deplace compte jusqu'a deux couples, d'ou les 22
     * requetes ci-dessus : le garde n'a pas deplace la borne.
     */
    public const PLAFOND_NOTES_PAR_APPEL = 400;

    /**
     * @param  array{classe_id?:int|null, matiere_id?:int|null, periode?:string|null, annee_universitaire_id?:int|null}  $avant
     *                                                                                                                           Coordonnees de l'evaluation AVANT le deplacement.
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    public static function pour(ESBTPEvaluation $evaluation, array $avant, ?int $declencheur = null): array
    {
        $memo = ['couples' => [], 'orphelins' => []];

        return self::pourAvecMemo($evaluation, $avant, $declencheur, $memo);
    }

    /**
     * Le deplacement fait depuis l'ecran des evaluations : repercute sur les
     * notes les colonnes denormalisees qui ont change (`classe_id`,
     * `matiere_id`, `semestre`), puis rafraichit les moyennes des deux cotes.
     *
     * Sorti de `ESBTPEvaluationController`, qui depassait deja 2000 lignes :
     * c'est une regle du domaine des notes, pas de l'ecran.
     *
     * La repercussion est un `update()` de QUERY BUILDER : il n'emet aucun
     * evenement, l'observateur ne tourne pas — d'ou l'appel a `pour()` qui
     * suit, sans lequel la moyenne d'avant l'emporterait sur les notes.
     *
     * @param  array{classe_id:int, matiere_id:int, periode:string}  $avant
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    public static function apresEnregistrement(ESBTPEvaluation $evaluation, array $avant, ?int $declencheur = null): array
    {
        if (! self::recopierSurLesNotes($evaluation, $avant)) {
            return ['recalculs_tentes' => 0, 'orphelins' => [], 'echecs' => 0];
        }

        return self::pour($evaluation, $avant + [
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ], $declencheur);
    }

    /**
     * La premiere moitie d'{@see apresEnregistrement()}, seule : l'ecriture,
     * sans le recalcul. Pour l'appelant qui doit ecrire DANS sa transaction et
     * recalculer APRES le commit (`AlignementDuDevoir`) — le recalcul reste
     * hors transaction, comme partout.
     *
     * @param  array{classe_id:int, matiere_id:int, periode:string}  $avant
     * @return bool vrai si une colonne a change
     */
    public static function recopierSurLesNotes(ESBTPEvaluation $evaluation, array $avant): bool
    {
        $colonnes = [];

        if ($evaluation->classe_id != $avant['classe_id']) {
            $colonnes['classe_id'] = $evaluation->classe_id;
        }
        if ($evaluation->matiere_id != $avant['matiere_id']) {
            $colonnes['matiere_id'] = $evaluation->matiere_id;
        }
        if ($evaluation->periode != $avant['periode']) {
            // L'encodage vit sur le modele (voir le hook `saving()`), pas ici.
            $colonnes['semestre'] = ESBTPNote::semestreDepuisLaPeriode((string) $evaluation->periode);
        }

        if ($colonnes === []) {
            return false;
        }

        $touchees = ESBTPNote::where('evaluation_id', $evaluation->id)->update($colonnes);

        Log::info('Notes propagées après modif évaluation', [
            'evaluation_id' => $evaluation->id,
            'changes' => $colonnes,
            'old' => $avant,
            'notes_affected' => $touchees,
        ]);

        return true;
    }

    /**
     * Annuler une évaluation retire ses notes de toute moyenne (le calcul
     * écarte `cancelled`), la réactiver les y remet. Rien ne bouge, et
     * pourtant la moyenne enregistrée change : `pour()`, qui compare deux
     * coordonnées, n'y voit rien — d'où cette seconde entrée.
     *
     * La coordonnée de l'évaluation est recalculée pour chaque élève noté,
     * par le même garde : une moyenne qui ne reposait que sur l'évaluation
     * annulée n'a plus rien à moyenner, elle est laissée et signalée, jamais
     * remise à zéro.
     *
     * Un changement de statut qui ne franchit pas l'annulation (brouillon →
     * planifiée, par exemple) ne coûte rien.
     *
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    public static function apresChangementDeStatut(ESBTPEvaluation $evaluation, ?string $statutAvant, ?int $declencheur = null): array
    {
        $annulee = ESBTPEvaluation::STATUS_CANCELLED;

        if (($statutAvant === $annulee) === ($evaluation->status === $annulee)) {
            return ['recalculs_tentes' => 0, 'orphelins' => [], 'echecs' => 0];
        }

        $memo = ['couples' => [], 'orphelins' => []];
        $bilan = self::recalculerPourLesEleves($evaluation, [self::coordonneeDe($evaluation)], $declencheur, $memo);

        if ($bilan['orphelins'] !== []) {
            Log::warning('Changement de statut d evaluation : moyennes sans rien a moyenner, laissees en place', [
                'evaluation_id' => $evaluation->id,
                'statut_avant' => $statutAvant,
                'statut' => $evaluation->status,
                'orphelins' => $bilan['orphelins'],
            ]);
        }

        return $bilan;
    }

    /**
     * Le memo evite de refaire deux fois le meme travail dans un lot.
     *
     * `pourUnLotDePeriodes()` appelle `pour()` **par evaluation**. Deux
     * evaluations de la meme coordonnee deplacees ensemble produisaient donc
     * deux fois le meme recalcul, et surtout signalaient DEUX FOIS la meme ligne
     * d'`esbtp_resultats` comme orpheline. Mesure : `agregats_orphelins` rendait
     * deux entrees de `resultat_id` identique pour une seule ligne en base, et
     * `recalculs_tentes` valait 2 pour un unique couple (eleve, coordonnee).
     *
     * Les deux comptent. Le doublon d'orphelins fait sur-compter a l'operateur
     * ce qu'il a a trancher — dans un chantier dont la these est qu'un compte
     * faux ferme l'enquete suivante. Et le recalcul redondant consomme le budget
     * global pour rien.
     *
     * Le memo porte sur le couple (eleve, coordonnee) et sur `resultat_id`, pas
     * sur l'evaluation : deux evaluations de la meme coordonnee peuvent noter
     * des eleves differents, et chacun doit etre traite.
     *
     * @param  array{couples:array<string,true>, orphelins:array<int,true>}  $memo
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    private static function pourAvecMemo(ESBTPEvaluation $evaluation, array $avant, ?int $declencheur, array &$memo): array
    {
        $apres = self::coordonneeDe($evaluation);

        if (self::memeCoordonnee($avant, $apres)) {
            return ['recalculs_tentes' => 0, 'orphelins' => [], 'echecs' => 0];
        }

        // Les deux cotes passent par le meme garde : l'arrivee peut elle aussi
        // ne recevoir que des absences, et une ligne qu'elle porterait deja ne
        // doit pas davantage y etre remise a zero.
        $bilan = self::recalculerPourLesEleves($evaluation, [$apres, $avant], $declencheur, $memo);

        if ($bilan['orphelins'] !== []) {
            Log::warning('Deplacement d evaluation : moyennes sans rien a moyenner, laissees en place', [
                'evaluation_id' => $evaluation->id,
                'avant' => $avant,
                'apres' => $apres,
                'orphelins' => $bilan['orphelins'],
                'remede' => 'chaque entree porte sa classe et sa periode (depart OU arrivee) : pre-controle de la '
                    .'generation des bulletins pour reste = aucune_note, « Modifier les moyennes » de l eleve sinon',
            ]);
        }

        return $bilan;
    }

    /** @return array{classe_id:mixed, matiere_id:mixed, periode:mixed, annee_universitaire_id:mixed} */
    private static function coordonneeDe(ESBTPEvaluation $evaluation): array
    {
        return [
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => $evaluation->matiere_id,
            'periode' => $evaluation->periode,
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ];
    }

    /**
     * Chaque élève noté sur l'évaluation, sur chacune des coordonnées données.
     * Une coordonnée incomplète (une année encore nulle, par exemple) est
     * sautée par {@see executerUneFois()} : elle ne porte aucune moyenne.
     *
     * @param  array<int, array<string,mixed>>  $coordonnees
     * @param  array{couples:array<string,true>, orphelins:array<int,true>}  $memo
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    private static function recalculerPourLesEleves(ESBTPEvaluation $evaluation, array $coordonnees, ?int $declencheur, array &$memo): array
    {
        // `recalculs_tentes` et non « recalcules » : le job rend la main sans
        // rien ecrire dans deux cas legitimes — aucune note et aucune ligne
        // existante, ou refus du garde de coherence BTS/LMD, qu'il attrape sans
        // relancer. Annoncer « N agregats recalcules » surestimait.
        $bilan = ['recalculs_tentes' => 0, 'orphelins' => [], 'echecs' => 0];

        $etudiantIds = ESBTPNote::where('evaluation_id', $evaluation->id)
            ->distinct()
            ->pluck('etudiant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($etudiantIds as $etudiantId) {
            foreach ($coordonnees as $coordonnee) {
                self::executerUneFois($etudiantId, $coordonnee, $declencheur, $bilan, $memo);
            }
        }

        return $bilan;
    }

    /**
     * Recalcule un couple (eleve, coordonnee) au plus une fois sur la duree
     * du lot, par {@see PerimetreDeRecalcul::recalculerUnCouple()}, et reporte
     * son issue dans le bilan. Voir le docbloc de `pourAvecMemo()`.
     *
     * La cle du memo porte la periode CANONIQUE : `'1'` et `'semestre1'`
     * designent la meme coordonnee, et la cle brute les recalculait deux fois.
     *
     * Un recalcul qui echoue ne defait pas le deplacement, qui reste acquis.
     *
     * @param  array<string,mixed>  $coordonnee
     * @param  array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}  $bilan
     * @param  array{couples:array<string,true>, orphelins:array<int,true>}  $memo
     */
    private static function executerUneFois(int $etudiantId, array $coordonnee, ?int $declencheur, array &$bilan, array &$memo): void
    {
        if (! self::coordonneeComplete($coordonnee)) {
            return;
        }

        $couple = [
            'etudiant_id' => $etudiantId,
            'classe_id' => (int) $coordonnee['classe_id'],
            'matiere_id' => (int) $coordonnee['matiere_id'],
            'annee_universitaire_id' => (int) $coordonnee['annee_universitaire_id'],
            'periode' => ESBTPEvaluation::periodeCanonique((string) $coordonnee['periode']),
        ];

        $cle = implode('|', $couple);

        if (isset($memo['couples'][$cle])) {
            return;
        }

        $memo['couples'][$cle] = true;

        $issue = PerimetreDeRecalcul::recalculerUnCouple($couple, self::SOURCE, $declencheur);

        if ($issue['statut'] === PerimetreDeRecalcul::RECALCULE) {
            $bilan['recalculs_tentes']++;
        } elseif ($issue['statut'] === PerimetreDeRecalcul::ECHEC) {
            $bilan['echecs']++;
        }

        $laissee = $issue['laissee'];

        if ($laissee !== null && ! isset($memo['orphelins'][$laissee['resultat_id']])) {
            $memo['orphelins'][$laissee['resultat_id']] = true;
            $bilan['orphelins'][] = $laissee;
        }
    }

    /** @param array<string,mixed> $coordonnee */
    private static function coordonneeComplete(array $coordonnee): bool
    {
        foreach (['classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'] as $cle) {
            if (empty($coordonnee[$cle])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string,mixed>  $avant
     * @param  array<string,mixed>  $apres
     */
    private static function memeCoordonnee(array $avant, array $apres): bool
    {
        foreach (['classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'] as $cle) {
            if (($avant[$cle] ?? null) != ($apres[$cle] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Meme correction, pour les deux endpoints qui deplacent des evaluations
     * d'une PERIODE a l'autre, en lot.
     *
     * `periode` est une coordonnee de la cle d'`esbtp_resultats` au meme titre
     * que `matiere_id` : un changement de semestre laisse donc exactement le
     * meme agregat perime des deux cotes.
     *
     * Une borne globale, servie du plus leger au plus lourd — voir
     * `PLAFOND_NOTES_PAR_APPEL`. Tout perimetre non traite part dans
     * `perimetres_reportes` avec sa raison et les parametres exacts a rejouer
     * sur `POST /api/cli/notes/recompute`.
     *
     * @param  array<int, array{evaluation: ESBTPEvaluation, periode_avant: string}>  $deplacements
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int, reporte:bool, perimetres_reportes:array<int,array<string,mixed>>}
     */
    public static function pourUnLotDePeriodes(array $deplacements, ?int $declencheur = null): array
    {
        $bilan = [
            'recalculs_tentes' => 0,
            'orphelins' => [],
            'echecs' => 0,
            'reporte' => false,
            'perimetres_reportes' => [],
        ];

        if ($deplacements === []) {
            return $bilan;
        }

        // Une seule requete pour tout le lot : le compte par evaluation est
        // ensuite reparti par perimetre en memoire.
        $notesParEvaluation = ESBTPNote::whereIn(
            'evaluation_id',
            array_map(static fn (array $d) => (int) $d['evaluation']->id, $deplacements)
        )
            ->selectRaw('evaluation_id, COUNT(*) as total')
            ->groupBy('evaluation_id')
            ->pluck('total', 'evaluation_id');

        $budget = self::PLAFOND_NOTES_PAR_APPEL;
        $memo = ['couples' => [], 'orphelins' => []];

        $perimetres = self::grouperParPerimetre($deplacements, $notesParEvaluation);
        uasort($perimetres, static fn (array $a, array $b) => $a['notes'] <=> $b['notes']);

        foreach ($perimetres as $perimetre) {
            if ($perimetre['notes'] > $budget) {
                self::reporterUnPerimetre(
                    $perimetre,
                    $perimetre['notes'] > self::PLAFOND_NOTES_PAR_APPEL ? 'perimetre_trop_lourd' : 'budget_de_la_requete_epuise',
                    $bilan
                );

                continue;
            }

            $budget -= $perimetre['notes'];

            foreach ($perimetre['deplacements'] as $deplacement) {
                $evaluation = $deplacement['evaluation'];

                $partiel = self::pourAvecMemo($evaluation, [
                    'classe_id' => $evaluation->classe_id,
                    'matiere_id' => $evaluation->matiere_id,
                    'periode' => $deplacement['periode_avant'],
                    'annee_universitaire_id' => $evaluation->annee_universitaire_id,
                ], $declencheur, $memo);

                $bilan['recalculs_tentes'] += $partiel['recalculs_tentes'];
                $bilan['echecs'] += $partiel['echecs'];
                $bilan['orphelins'] = array_merge($bilan['orphelins'], $partiel['orphelins']);
            }
        }

        return $bilan;
    }

    /**
     * Regroupe les deplacements sur ce que sait rejouer
     * `POST /api/cli/notes/recompute` : (classe, annee), avec les periodes
     * touchees des DEUX cotes et les matieres concernees.
     *
     * Les periodes sont rendues sous leur forme canonique `semestreN`, pas sous
     * la valeur brute d'avant : `esbtp_evaluations.periode` accepte aussi `'1'`
     * et `'2'` (voir `ESBTPEvaluation::aliasDePeriode()`), et l'endpoint de
     * rattrapage valide `in:semestre1,semestre2`. Publier la valeur brute
     * rendait un parametre que l'endpoint refuse en 422.
     *
     * @param  array<int, array{evaluation: ESBTPEvaluation, periode_avant: string}>  $deplacements
     * @param  Collection<int|string, int>  $notesParEvaluation
     * @return array<string, array<string, mixed>>
     */
    private static function grouperParPerimetre(array $deplacements, $notesParEvaluation): array
    {
        $perimetres = [];

        foreach ($deplacements as $deplacement) {
            $evaluation = $deplacement['evaluation'];
            $cle = ((int) $evaluation->classe_id).'|'.((int) $evaluation->annee_universitaire_id);

            if (! isset($perimetres[$cle])) {
                $perimetres[$cle] = [
                    'classe_id' => (int) $evaluation->classe_id,
                    'annee_universitaire_id' => (int) $evaluation->annee_universitaire_id,
                    'periodes' => [],
                    'matiere_ids' => [],
                    'notes' => 0,
                    'deplacements' => [],
                ];
            }

            foreach ([$deplacement['periode_avant'], $evaluation->periode] as $brute) {
                if (ESBTPEvaluation::numeroDeSemestre((string) $brute) === null) {
                    continue;
                }

                $canonique = ESBTPEvaluation::periodeCanonique((string) $brute);

                if (! in_array($canonique, $perimetres[$cle]['periodes'], true)) {
                    $perimetres[$cle]['periodes'][] = $canonique;
                }
            }

            if ($evaluation->matiere_id && ! in_array((int) $evaluation->matiere_id, $perimetres[$cle]['matiere_ids'], true)) {
                $perimetres[$cle]['matiere_ids'][] = (int) $evaluation->matiere_id;
            }

            $perimetres[$cle]['notes'] += (int) ($notesParEvaluation[$evaluation->id] ?? 0);
            $perimetres[$cle]['deplacements'][] = $deplacement;
        }

        return $perimetres;
    }

    /**
     * @param  array<string, mixed>  $perimetre
     * @param  array<string, mixed>  $bilan
     */
    private static function reporterUnPerimetre(array $perimetre, string $raison, array &$bilan): void
    {
        $evaluations = array_map(
            static fn (array $d) => (int) $d['evaluation']->id,
            $perimetre['deplacements']
        );

        $ligne = [
            'classe_id' => $perimetre['classe_id'],
            'annee_universitaire_id' => $perimetre['annee_universitaire_id'],
            'periodes' => $perimetre['periodes'],
            'matiere_ids' => $perimetre['matiere_ids'],
            'notes' => $perimetre['notes'],
            'raison' => $raison,
            'evaluations' => $evaluations,
        ];

        $bilan['reporte'] = true;
        $bilan['perimetres_reportes'][] = $ligne;

        Log::warning('Deplacement en lot : recalcul reporte pour un perimetre', $ligne + [
            'plafond_appel' => self::PLAFOND_NOTES_PAR_APPEL,
            'remede' => 'POST /api/cli/notes/recompute avec classe_id, annee_universitaire_id et chaque periode',
        ]);
    }

    /**
     * La phrase que les deux endpoints en lot ajoutent a leur message.
     *
     * Elle vit ici, a cote du plafond et de la forme de
     * `perimetres_reportes` qu'elle decrit. Elle etait dupliquee mot pour mot
     * dans les deux controleurs : deux copies d'un message qui cite un plafond,
     * c'est le prochain compte faux en germe.
     *
     * @param  array<string, mixed>  $recalcul
     */
    public static function motDeLaFin(array $recalcul): string
    {
        $fait = ' '.$recalcul['recalculs_tentes'].' recalcul(s) lance(s).';

        if (! $recalcul['reporte']) {
            return $fait;
        }

        return $fait.' ATTENTION : '.count($recalcul['perimetres_reportes'])
            .' perimetre(s) non recalcule(s) — budget de '
            .self::PLAFOND_NOTES_PAR_APPEL.' notes par requete epuise. Chaque ligne de'
            .' `perimetres_reportes` porte les parametres a rejouer sur'
            .' POST /api/cli/notes/recompute (classe_id, annee_universitaire_id,'
            .' et une fois par periode listee). PASSEZ `matiere_id`, une fois par'
            .' entree de `matiere_ids` : sans lui le rattrapage recalcule TOUTES'
            .' les matieres de la classe pour cette periode, et un recalcul'
            .' ECRASE la moyenne enregistree — y compris celle qu une personne a'
            .' saisie a la main sur une matiere qui n a pas bouge. Une moyenne'
            .' sans rien a moyenner, elle, n est jamais remise a zero : elle est'
            .' rendue dans `laissees`.';
    }
}
