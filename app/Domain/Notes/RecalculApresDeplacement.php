<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
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
 * ## Le piege du zero, et pourquoi l'ancienne coordonnee est traitee a part
 *
 * `NoteCalculationService::studentMatiereAverage([])` rend **0.0**, pas `null`.
 * Rejouer le recalcul sur une coordonnee que le deplacement a videe de toutes
 * ses notes n'effacerait donc pas la ligne : il y **ecrirait un 0/20**, sur une
 * matiere que l'eleve n'a plus. C'est strictement pire que la valeur perimee.
 *
 * D'ou la regle : l'ancienne coordonnee n'est recalculee que s'il y reste au
 * moins une note. Sinon la ligne est **signalee, jamais touchee** — le sort
 * d'un agregat orphelin est une decision d'ecole, pas de code
 * (`.claude/rules/rien-en-dur.md`, « le cas particulier du zero »). Le menage
 * se fait sciemment, avec `evaluations:sync-notes --clean-resultats` borne.
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
 * coordonnees d'une EVALUATION**. Ils sont cinq, le cinquieme n'est pas branche
 * ici, et la liste est celle des sites **trouves a ce jour** — pas celle des
 * sites existants (`klassci-debugging-discipline.md`, piege #14) :
 *
 * | deplaceur | ce qu'il change | branche sur cette classe |
 * |---|---|---|
 * | `ESBTPEvaluationController::update()` | matiere, classe, periode | oui |
 * | `CLIMaintenanceController::evaluationChangeMatiere()` | matiere | oui |
 * | `CLIEvaluationDeplacementController::deplacer()` | periode, en lot | oui |
 * | `CLIEvaluationPeriodeController::repair()` | periode, en lot | oui |
 * | `MergeDuplicateEcue` (sous `force`) | matiere, en masse | **non** |
 *
 * Le cinquieme, `app/Domain/LMD/Actions/MergeDuplicateEcue.php`, reparente
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
 * exactement ce que les deux plafonds de cette classe cherchent a eviter.
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
 * alors que le recalcul est volontairement hors transaction ; et le cinquieme
 * deplaceur passe par `DB::table()` brut, qu'aucun observer n'attrape. Un
 * observer reste souhaitable pour les chemins Eloquent — il n'est simplement
 * pas suffisant, et ce n'est pas le geste de ce lot.
 */
final class RecalculApresDeplacement
{
    /** Valeur ecrite dans `esbtp_resultats_recompute_log.source`. */
    public const SOURCE = 'deplacement';

    /**
     * Plafond de notes recalculees d'un coup, **par classe et par annee**.
     *
     * Le plafond porte sur la classe et non sur le lot : sur le lot, un appel
     * touchant cinq classes dont une seule est lourde ne recalculait **aucune**
     * des quatre autres.
     */
    public const PLAFOND_NOTES_PAR_CLASSE = 400;

    /**
     * Borne GLOBALE de notes recalculees dans une requete, tous perimetres
     * confondus.
     *
     * **Elle a manque, et son absence etait pire que le plafond trop strict
     * qu'elle a remplace.** Le plafond par classe seul ne borne rien du tout :
     * le nombre de classes n'est limite nulle part — `deplacer()` accepte 200
     * evaluations reparties sur autant de classes, et `detecter()` n'a aucun
     * `LIMIT`. Vingt classes a 399 notes passent chacune sous le plafond et font
     * huit mille notes recalculees sur place, dans une seule requete HTTP.
     *
     * Et le mode d'echec est le plus mauvais des deux. Le recalcul est hors
     * transaction a dessein : les evaluations sont **deja enregistrees**. Si la
     * requete meurt sur le delai d'attente, on garde des evaluations deplacees,
     * des agregats rafraichis a moitie, aucune trace desquels — et surtout
     * `perimetres_reportes`, qui est tout l'objet de ce mecanisme, n'arrive
     * JAMAIS, puisque la reponse n'arrive pas. Le plafond par lot, lui, refusait
     * proprement et le disait.
     *
     * Les deux bornes cohabitent donc : la classe legere est traitee tant que le
     * budget global le permet, et tout ce qui reste bascule dans
     * `perimetres_reportes` avec sa raison.
     */
    public const PLAFOND_NOTES_PAR_APPEL = 1200;

    /**
     * @param  array{classe_id?:int|null, matiere_id?:int|null, periode?:string|null, annee_universitaire_id?:int|null}  $avant
     *                                                                                                                          Coordonnees de l'evaluation AVANT le deplacement.
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    public static function pour(ESBTPEvaluation $evaluation, array $avant, ?int $declencheur = null): array
    {
        $memo = ['couples' => [], 'orphelins' => []];

        return self::pourAvecMemo($evaluation, $avant, $declencheur, $memo);
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
        $apres = [
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => $evaluation->matiere_id,
            'periode' => $evaluation->periode,
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ];

        // `recalculs_tentes` et non « recalcules » : le job rend la main sans
        // rien ecrire dans deux cas legitimes — aucune note et aucune ligne
        // existante, ou refus du garde de coherence BTS/LMD, qu'il attrape sans
        // relancer. Annoncer « N agregats recalcules » surestimait.
        $bilan = ['recalculs_tentes' => 0, 'orphelins' => [], 'echecs' => 0];

        if (self::memeCoordonnee($avant, $apres)) {
            return $bilan;
        }

        $etudiantIds = ESBTPNote::where('evaluation_id', $evaluation->id)
            ->distinct()
            ->pluck('etudiant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($etudiantIds === []) {
            return $bilan;
        }

        foreach ($etudiantIds as $etudiantId) {
            // Nouvelle coordonnee : les notes viennent d'y arriver, donc la
            // ligne ne sera pas creee a partir de rien. Ce n'est PAS une
            // garantie de valeur non nulle : si toutes les notes deplacees sont
            // des absences, `studentMatiereAverage()` les ecarte et rend 0 —
            // comme partout ailleurs dans le recalcul. Le piege qu'on evite ici
            // est l'autre, celui du cote qu'on quitte : y rejouer le calcul sur
            // ZERO note ecrirait un 0/20 sur une matiere que l'eleve n'a plus.
            if (self::executerUneFois($etudiantId, $apres, $declencheur, $bilan, $memo)) {
                $bilan['recalculs_tentes']++;
            }

            // Ancienne coordonnee : recalcul seulement s'il y reste des notes.
            if (! self::coordonneeComplete($avant)) {
                continue;
            }

            if (self::porteEncoreDesNotes($etudiantId, $avant)) {
                if (self::executerUneFois($etudiantId, $avant, $declencheur, $bilan, $memo)) {
                    $bilan['recalculs_tentes']++;
                }

                continue;
            }

            $orphelin = self::agregatOrphelin($etudiantId, $avant);

            if ($orphelin !== null && ! isset($memo['orphelins'][$orphelin['resultat_id']])) {
                $memo['orphelins'][$orphelin['resultat_id']] = true;
                $bilan['orphelins'][] = $orphelin;
            }
        }

        if ($bilan['orphelins'] !== []) {
            Log::warning('Deplacement d evaluation : agregats desormais sans note, laisses en place', [
                'evaluation_id' => $evaluation->id,
                'avant' => $avant,
                'apres' => $apres,
                'orphelins' => $bilan['orphelins'],
                'remede' => 'evaluations:sync-notes --clean-resultats, borne sur la classe et la periode',
            ]);
        }

        return $bilan;
    }

    /**
     * `executer()`, mais au plus une fois par couple (eleve, coordonnee) sur la
     * duree du lot. Voir le docbloc de `pourAvecMemo()`.
     *
     * @param  array<string,mixed>  $coordonnee
     * @param  array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}  $bilan
     * @param  array{couples:array<string,true>, orphelins:array<int,true>}  $memo
     */
    private static function executerUneFois(int $etudiantId, array $coordonnee, ?int $declencheur, array &$bilan, array &$memo): bool
    {
        if (! self::coordonneeComplete($coordonnee)) {
            return false;
        }

        $cle = implode('|', [
            $etudiantId,
            $coordonnee['classe_id'],
            $coordonnee['matiere_id'],
            $coordonnee['annee_universitaire_id'],
            $coordonnee['periode'],
        ]);

        if (isset($memo['couples'][$cle])) {
            return false;
        }

        $memo['couples'][$cle] = true;

        return self::executer($etudiantId, $coordonnee, $declencheur, $bilan);
    }

    /**
     * @param  array<string,mixed>  $coordonnee
     * @param  array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}  $bilan
     */
    private static function executer(int $etudiantId, array $coordonnee, ?int $declencheur, array &$bilan): bool
    {
        if (! self::coordonneeComplete($coordonnee)) {
            return false;
        }

        try {
            RecomputeStudentResultatJob::dispatchSync(
                etudiantId: $etudiantId,
                classeId: (int) $coordonnee['classe_id'],
                matiereId: (int) $coordonnee['matiere_id'],
                anneeUniversitaireId: (int) $coordonnee['annee_universitaire_id'],
                periode: (string) $coordonnee['periode'],
                source: self::SOURCE,
                triggeredBy: $declencheur,
            );

            return true;
        } catch (\Throwable $e) {
            // Le deplacement, lui, a reussi et reste acquis : un recalcul qui
            // echoue ne doit pas le defaire ni faire echouer la requete.
            $bilan['echecs']++;

            Log::error('Deplacement d evaluation : recalcul en echec', [
                'etudiant_id' => $etudiantId,
                'coordonnee' => $coordonnee,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @param array<string,mixed> $coordonnee */
    private static function porteEncoreDesNotes(int $etudiantId, array $coordonnee): bool
    {
        return ESBTPNote::query()
            ->where('etudiant_id', $etudiantId)
            ->whereHas('evaluation', function ($q) use ($coordonnee) {
                $q->where('classe_id', $coordonnee['classe_id'])
                    ->where('matiere_id', $coordonnee['matiere_id'])
                    ->where('annee_universitaire_id', $coordonnee['annee_universitaire_id'])
                    ->where('periode', $coordonnee['periode'])
                    ->where('status', '!=', 'cancelled');
            })
            ->exists();
    }

    /**
     * @param  array<string,mixed>  $coordonnee
     * @return array<string,mixed>|null
     */
    private static function agregatOrphelin(int $etudiantId, array $coordonnee): ?array
    {
        $ligne = ESBTPResultat::query()
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $coordonnee['classe_id'])
            ->where('matiere_id', $coordonnee['matiere_id'])
            ->where('annee_universitaire_id', $coordonnee['annee_universitaire_id'])
            ->where('periode', $coordonnee['periode'])
            ->first();

        if (! $ligne) {
            return null;
        }

        return [
            'resultat_id' => (int) $ligne->id,
            'etudiant_id' => $etudiantId,
            'matiere_id' => (int) $coordonnee['matiere_id'],
            'periode' => (string) $coordonnee['periode'],
            'moyenne' => $ligne->moyenne !== null ? (float) $ligne->moyenne : null,
        ];
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
     * Deux bornes, et il faut les deux — voir `PLAFOND_NOTES_PAR_CLASSE` et
     * `PLAFOND_NOTES_PAR_APPEL`. Tout perimetre non traite part dans
     * `perimetres_reportes` avec sa raison et les parametres exacts a rejouer
     * sur `POST /api/cli/notes/recompute`.
     *
     * @param  array<int, array{evaluation: ESBTPEvaluation, periode_avant: string}>  $deplacements
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int, reporte:bool, notes:int, perimetres_reportes:array<int,array<string,mixed>>}
     */
    public static function pourUnLotDePeriodes(array $deplacements, ?int $declencheur = null): array
    {
        $bilan = [
            'recalculs_tentes' => 0,
            'orphelins' => [],
            'echecs' => 0,
            'reporte' => false,
            'notes' => 0,
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

        $bilan['notes'] = (int) $notesParEvaluation->sum();

        $budget = self::PLAFOND_NOTES_PAR_APPEL;
        $memo = ['couples' => [], 'orphelins' => []];

        foreach (self::grouperParPerimetre($deplacements, $notesParEvaluation) as $perimetre) {
            if ($perimetre['notes'] > self::PLAFOND_NOTES_PAR_CLASSE) {
                self::reporterUnPerimetre($perimetre, 'plafond_classe', $bilan);

                continue;
            }

            if ($perimetre['notes'] > $budget) {
                self::reporterUnPerimetre($perimetre, 'budget_de_la_requete_epuise', $bilan);

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
     * @param  \Illuminate\Support\Collection<int|string, int>  $notesParEvaluation
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
                $numero = ESBTPEvaluation::numeroDeSemestre((string) $brute);

                if ($numero === null) {
                    continue;
                }

                $canonique = 'semestre'.$numero;

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
            'plafond_classe' => self::PLAFOND_NOTES_PAR_CLASSE,
            'plafond_appel' => self::PLAFOND_NOTES_PAR_APPEL,
            'remede' => 'POST /api/cli/notes/recompute avec classe_id, annee_universitaire_id et chaque periode',
        ]);
    }

    /**
     * La phrase que les deux endpoints en lot ajoutent a leur message.
     *
     * Elle vit ici, a cote des deux plafonds et de la forme de
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
            .' perimetre(s) non recalcule(s) — plafond de '
            .self::PLAFOND_NOTES_PAR_CLASSE.' notes par classe, ou budget de '
            .self::PLAFOND_NOTES_PAR_APPEL.' notes par requete epuise. Chaque ligne de'
            .' `perimetres_reportes` porte les parametres a rejouer sur'
            .' POST /api/cli/notes/recompute (classe_id, annee_universitaire_id,'
            .' et une fois par periode listee ; `matiere_ids` sert a decouper si'
            .' le rattrapage bute a son tour sur son plafond de couples).';
    }
}
