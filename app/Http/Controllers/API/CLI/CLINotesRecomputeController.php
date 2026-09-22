<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Notes\PerimetreDeRecalcul;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Rejouer le calcul d'un agregat d'`esbtp_resultats` depuis les notes.
 *
 * Endpoint a lui seul, et pas une methode de plus dans `CLIMaintenanceController` :
 * ce fichier-la passait 1500 lignes avant ce chantier, et le grossir encore
 * contredit l'axe « no god code ». Le precedent est `ESBTPComptabiliteController`,
 * ramene a 204 lignes en quatre controleurs.
 */
class CLINotesRecomputeController extends BaseApiController
{
    /**
     * Plafond de couples (etudiant, matiere) par appel.
     *
     * Le recalcul tourne sur place, dans la requete HTTP : le plafond protege
     * le temps de reponse, pas la base. Une classe de 40 eleves sur 12 matieres
     * (480 couples) tient dessous ; au-dela, le perimetre n'a probablement pas
     * ete reflechi et se decoupe par matiere.
     *
     * **Il valait 600, sans raison derriere le chiffre.** Ce qui se compte, lui,
     * se lit dans `PerimetreDeRecalcul::recalculer()` : chaque couple coute une
     * lecture de moyenne, un `dispatchSync` (le job, ses requetes, et un
     * `touch()` de bulletin) puis une seconde lecture. A 600, cela fait de
     * l'ordre de 1200 lectures et 600 executions de job dans UNE requete HTTP.
     *
     * **Mesure depuis (septembre 2026)** : un couple coute ~17 ms et 22 requetes
     * en local, lineairement — 40 couples en 0,67 s. A 500, un appel prend donc
     * de l'ordre de 9 s en local, sous les 30 s au-dela desquelles le binaire
     * `klassci` abandonne. Ce qui reste NON mesure, et se lit comme tel : le
     * facteur de ralentissement de l'hebergement mutualise LWS. Le controle a
     * faire est un chronometrage sur une instance Elite.
     *
     * 500 et non 200 : le cas legitime que cet endpoint sert est le recalcul
     * d'une classe entiere, soit 40 eleves sur 12 matieres — 480 couples.
     * Descendre sous ce chiffre refuserait le geste normal et forcerait a
     * decouper ce qui n'a pas de raison de l'etre. 500 est la plus petite valeur
     * qui l'admette encore.
     */
    private const PLAFOND_RECOMPUTE = 500;

    /**
     * POST /api/cli/notes/recompute
     *
     * Rejoue `notes:recompute` sur un perimetre EXPLICITE. La commande artisan
     * existe depuis longtemps mais n'etait joignable que depuis un terminal du
     * serveur : quand un agregat d'`esbtp_resultats` divergeait des notes, il
     * n'y avait aucun moyen de le rafraichir a distance.
     *
     * ## Le perimetre est obligatoire, et c'est le point
     *
     * `classe_id`, `periode` et `annee_universitaire_id` sont requis. La
     * commande artisan, elle, accepte de tourner sans aucun filtre et balaie
     * alors l'ecole entiere. Un recalcul ECRASE `esbtp_resultats.moyenne` :
     * lache sans bornes sur une instance Elite, il effacerait d'un coup toutes
     * les moyennes saisies a la main par l'ecole. D'ou le refus de tourner a
     * l'aveugle, et le plafond.
     *
     * La selection des couples est partagee avec la commande artisan
     * ({@see PerimetreDeRecalcul}) : la premiere version la reimplementait, et
     * cette copie avait deja perdu le filtre `etudiant_id` en chemin.
     *
     * ## Ce que `dry_run` montre, et ce qu'il ne montre pas
     *
     * `dry_run` liste les couples vises avec leur moyenne enregistree. Il ne
     * PREDIT pas la valeur d'apres : la predire demanderait de reecrire la
     * selection des notes a cote de celle du job. L'execution reelle, elle,
     * rend `moyenne_avant` et `moyenne_apres` par couple.
     *
     * Body: { classe_id, periode, annee_universitaire_id, matiere_id?,
     *         etudiant_id?, dry_run? }
     */
    public function notesRecompute(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'classe_id' => 'required|integer|exists:esbtp_classes,id',
            // `annuel` est volontairement absent : une evaluation ne porte
            // jamais cette periode, donc le perimetre serait toujours vide et
            // l'appel rendrait un succes rassurant sans rien avoir recalcule.
            'periode' => 'required|string|in:semestre1,semestre2',
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'matiere_id' => 'nullable|integer|exists:esbtp_matieres,id',
            'etudiant_id' => 'nullable|integer|exists:esbtp_etudiants,id',
            'dry_run' => 'nullable|boolean',
        ]);

        $perimetre = PerimetreDeRecalcul::depuis($validated);
        $couples = $perimetre->couples();

        if ($couples->isEmpty()) {
            return $this->successResponse([
                'perimetre' => $validated,
                'couples' => [],
                'total' => 0,
            ], 'Aucune note ne correspond a ce perimetre : rien a recalculer.');
        }

        if ($couples->count() > self::PLAFOND_RECOMPUTE) {
            return $this->errorResponse(
                'Perimetre trop large : '.$couples->count().' couples (etudiant, matiere) pour un plafond de '
                .self::PLAFOND_RECOMPUTE.'. Ajoutez `matiere_id` ou `etudiant_id`.',
                [],
                422
            );
        }

        if ((bool) ($validated['dry_run'] ?? false)) {
            return $this->successResponse([
                'dry_run' => true,
                'perimetre' => $validated,
                'couples' => $couples->map(fn (array $c) => $c + [
                    'moyenne_enregistree' => $perimetre->moyenneEnregistree($c),
                ])->all(),
                'total' => $couples->count(),
            ], 'Aucune ecriture : '.$couples->count().' couple(s) seraient recalcules.');
        }

        $bilan = $perimetre->recalculer($couples, 'cli', $request->user()->id);
        $modifies = collect($bilan['lignes'])->where('change', true)->count();

        Log::warning('CLI: recalcul de resultats execute', [
            'perimetre' => $validated,
            'couples' => count($bilan['lignes']),
            'modifies' => $modifies,
            'echecs' => $bilan['echecs'],
            'laissees' => count($bilan['laissees']),
            'caller_user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        // `laissees` : les moyennes qu'il ne restait rien a moyenner pour
        // recalculer (aucune note, ou seulement des absences). Elles ne sont
        // jamais remises a zero — voir PerimetreDeRecalcul::recalculerUnCouple().
        return $this->successResponse([
            'perimetre' => $validated,
            'couples' => $bilan['lignes'],
            'total' => count($bilan['lignes']),
            'modifies' => $modifies,
            'echecs' => $bilan['echecs'],
            'laissees' => $bilan['laissees'],
        ], count($bilan['lignes']).' couple(s) recalcule(s), '.$modifies
            .' moyenne(s) modifiee(s), '.$bilan['echecs'].' echec(s), '
            .count($bilan['laissees']).' laissee(s) sans rien a moyenner.');
    }
}
