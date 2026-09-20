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
 * ## Combien de deplaceurs, et lesquels
 *
 * **Ce compte a ete faux deux fois, chaque fois publie comme definitif** : la
 * premiere livraison annoncait « les deux endroits » (les deux qui changent la
 * matiere), la deuxieme « quatre » (en ajoutant les deux qui changent la
 * periode). Il y en a **cinq**, et le cinquieme n'est pas branche ici :
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
 */
final class RecalculApresDeplacement
{
    /** Valeur ecrite dans `esbtp_resultats_recompute_log.source`. */
    public const SOURCE = 'deplacement';

    /**
     * @param  array{classe_id?:int|null, matiere_id?:int|null, periode?:string|null, annee_universitaire_id?:int|null}  $avant
     *                                                                                                                          Coordonnees de l'evaluation AVANT le deplacement.
     * @return array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}
     */
    public static function pour(ESBTPEvaluation $evaluation, array $avant, ?int $declencheur = null): array
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
            if (self::executer($etudiantId, $apres, $declencheur, $bilan)) {
                $bilan['recalculs_tentes']++;
            }

            // Ancienne coordonnee : recalcul seulement s'il y reste des notes.
            if (! self::coordonneeComplete($avant)) {
                continue;
            }

            if (self::porteEncoreDesNotes($etudiantId, $avant)) {
                if (self::executer($etudiantId, $avant, $declencheur, $bilan)) {
                    $bilan['recalculs_tentes']++;
                }

                continue;
            }

            if ($orphelin = self::agregatOrphelin($etudiantId, $avant)) {
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
     * Plafond de notes recalculees d'un coup, **par classe et par annee**.
     * Au-dela, le recalcul de CE perimetre-la n'est pas lance : il est rendu a
     * l'appelant, qui le rejoue par `POST /api/cli/notes/recompute`. Le job
     * tourne sur place ; un lot de 200 evaluations sur une classe pleine ferait
     * plusieurs milliers de requetes dans une seule requete HTTP, sur de
     * l'hebergement mutualise.
     *
     * Le plafond porte sur la classe, PAS sur le lot, et la difference n'est pas
     * cosmetique. Sur le lot, un appel touchant cinq classes dont une seule est
     * lourde ne recalculait **aucune** des quatre autres. Pire :
     * `CLIEvaluationPeriodeController::detecter()` ne borne pas sa selection —
     * sur une instance a plus de 2000 inscriptions, le plafond de lot etait
     * franchi a tous les coups, donc rien n'etait jamais recalcule et le
     * correctif se reduisait a un message.
     */
    public const PLAFOND_NOTES_PAR_CLASSE = 400;

    /**
     * Meme correction, pour les deux endpoints qui deplacent des evaluations
     * d'une PERIODE a l'autre, en lot.
     *
     * `periode` est une coordonnee de la cle d'`esbtp_resultats` au meme titre
     * que `matiere_id` : un changement de semestre laisse donc exactement le
     * meme agregat perime des deux cotes. Ces deux chemins ont ete manques a la
     * premiere passe — le correctif annoncait « les deux endroits » alors qu'il
     * y en a cinq (voir l'inventaire en tete de classe).
     *
     * `perimetres_reportes` est la partie qui compte pour l'operateur : chaque
     * ligne porte exactement les parametres qu'attend
     * `POST /api/cli/notes/recompute` — `classe_id`, `annee_universitaire_id` et
     * les periodes a rejouer. Sans elle, le message disait quoi refaire sans
     * donner de quoi le refaire.
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

        // Une seule requete pour tout le lot, comme avant : le compte par
        // evaluation est ensuite reparti par perimetre en memoire.
        $notesParEvaluation = ESBTPNote::whereIn(
            'evaluation_id',
            array_map(static fn (array $d) => (int) $d['evaluation']->id, $deplacements)
        )
            ->selectRaw('evaluation_id, COUNT(*) as total')
            ->groupBy('evaluation_id')
            ->pluck('total', 'evaluation_id');

        $bilan['notes'] = (int) $notesParEvaluation->sum();

        // Le perimetre que sait rejouer `/api/cli/notes/recompute` est
        // (classe, annee, periode). On groupe donc sur le couple classe+annee,
        // et on retient les periodes touchees des DEUX cotes du deplacement.
        $perimetres = [];

        foreach ($deplacements as $deplacement) {
            $evaluation = $deplacement['evaluation'];
            $cle = ((int) $evaluation->classe_id).'|'.((int) $evaluation->annee_universitaire_id);

            if (! isset($perimetres[$cle])) {
                $perimetres[$cle] = [
                    'classe_id' => (int) $evaluation->classe_id,
                    'annee_universitaire_id' => (int) $evaluation->annee_universitaire_id,
                    'periodes' => [],
                    'notes' => 0,
                    'deplacements' => [],
                ];
            }

            foreach ([$deplacement['periode_avant'], $evaluation->periode] as $periode) {
                if ($periode !== null && $periode !== '' && ! in_array($periode, $perimetres[$cle]['periodes'], true)) {
                    $perimetres[$cle]['periodes'][] = (string) $periode;
                }
            }

            $perimetres[$cle]['notes'] += (int) ($notesParEvaluation[$evaluation->id] ?? 0);
            $perimetres[$cle]['deplacements'][] = $deplacement;
        }

        foreach ($perimetres as $perimetre) {
            if ($perimetre['notes'] > self::PLAFOND_NOTES_PAR_CLASSE) {
                $bilan['reporte'] = true;
                $bilan['perimetres_reportes'][] = [
                    'classe_id' => $perimetre['classe_id'],
                    'annee_universitaire_id' => $perimetre['annee_universitaire_id'],
                    'periodes' => $perimetre['periodes'],
                    'notes' => $perimetre['notes'],
                    'evaluations' => array_map(
                        static fn (array $d) => (int) $d['evaluation']->id,
                        $perimetre['deplacements']
                    ),
                ];

                Log::warning('Deplacement en lot : recalcul reporte pour une classe, perimetre trop grand', [
                    'classe_id' => $perimetre['classe_id'],
                    'annee_universitaire_id' => $perimetre['annee_universitaire_id'],
                    'periodes' => $perimetre['periodes'],
                    'notes' => $perimetre['notes'],
                    'plafond' => self::PLAFOND_NOTES_PAR_CLASSE,
                    'evaluations' => array_map(
                        static fn (array $d) => (int) $d['evaluation']->id,
                        $perimetre['deplacements']
                    ),
                    'remede' => 'POST /api/cli/notes/recompute avec classe_id, annee_universitaire_id et chaque periode',
                ]);

                continue;
            }

            foreach ($perimetre['deplacements'] as $deplacement) {
                $evaluation = $deplacement['evaluation'];

                $partiel = self::pour($evaluation, [
                    'classe_id' => $evaluation->classe_id,
                    'matiere_id' => $evaluation->matiere_id,
                    'periode' => $deplacement['periode_avant'],
                    'annee_universitaire_id' => $evaluation->annee_universitaire_id,
                ], $declencheur);

                $bilan['recalculs_tentes'] += $partiel['recalculs_tentes'];
                $bilan['echecs'] += $partiel['echecs'];
                $bilan['orphelins'] = array_merge($bilan['orphelins'], $partiel['orphelins']);
            }
        }

        return $bilan;
    }
}
