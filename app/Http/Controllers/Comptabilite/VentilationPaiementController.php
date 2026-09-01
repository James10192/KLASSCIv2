<?php

namespace App\Http\Controllers\Comptabilite;

use App\Exceptions\RepartitionRefuseeException;
use App\Http\Controllers\Concerns\VerrouilleLesPeriodesComptables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Paiement\ReventilerPaiementRequest;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPPaiement;
use App\Services\Frais\RepartitionDuVersement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Facades\Auditor;

/**
 * Corriger l'imputation d'un versement DEJA encaisse.
 *
 * Un caissier impute au mauvais frais, ou le bon frais n'existait pas encore
 * — c'est ce qui vient d'arriver sur ISLG avec la ramette et la chemise
 * cartonnee. Jusqu'ici il fallait annuler le versement et le ressaisir, ce qui
 * casse la numerotation des recus et laisse un trou dans la piste d'audit pour
 * une erreur qui n'a rien coute a personne.
 *
 * Cet ecran ne touche NI au montant, NI au numero de recu, NI a la date, NI au
 * mode : il ne reecrit que la repartition. Le reste du versement est le meme
 * fait comptable qu'avant — seule sa lecture change.
 *
 * Controleur separe de {@see \App\Http\Controllers\ESBTPPaiementController}, qui
 * depasse deja largement les seuils de `.claude/rules/no-god-code-compta.md`.
 * Les verrous de periode et de reconciliation sont partages via un trait plutot
 * que recopies : un verrou en double n'est plus un verrou.
 */
class VentilationPaiementController extends Controller
{
    use VerrouilleLesPeriodesComptables;

    public function __construct(private readonly RepartitionDuVersement $repartition)
    {
        $this->middleware('auth');
        $this->middleware('permission:paiements.reventiler');
    }

    /**
     * L'ecran de correction.
     */
    public function edit(ESBTPPaiement $paiement)
    {
        if ($refus = $this->refusDeCorriger($paiement)) {
            return redirect()
                ->route('esbtp.paiements.show', $paiement->id)
                ->with('error', $refus);
        }

        // Ce dont l'ecran a besoin pour son en-tete. Les allocations, elles,
        // sont relues par `lignes()` : les charger ici en plus donnerait deux
        // sources pour la meme information, dont une susceptible d'etre perimee.
        $paiement->load(['etudiant']);

        return view('esbtp.paiements.ventilation', [
            'paiement' => $paiement,
            'lignes' => $this->lignes($paiement),
        ]);
    }

    /**
     * Reecrit la repartition.
     */
    public function update(ReventilerPaiementRequest $request, ESBTPPaiement $paiement)
    {
        if ($refus = $this->refusDeCorriger($paiement)) {
            return $this->echec($request, $refus, 403);
        }

        $ancienne = $this->ventilationActuelle($paiement);

        // Calcule AVANT d'ouvrir la transaction : un refus metier n'a rien a
        // annuler, et n'a pas a faire un aller-retour en base.
        try {
            $nouvelle = $this->repartition->reventiler($paiement, $request->validated('repartition'));
        } catch (RepartitionRefuseeException $e) {
            return $this->echec($request, $e->getMessage(), 422);
        }

        if ($this->identiques($ancienne, $nouvelle)) {
            return $this->echec(
                $request,
                'Cette répartition est déjà celle du versement : rien à corriger.',
                422
            );
        }

        $this->appliquer($paiement, $ancienne, $nouvelle, $request->validated('motif'));

        $message = 'Répartition corrigée. Le montant et le numéro de reçu sont inchangés ; '
            .'un reçu réédité portera la mention de la rectification.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('esbtp.paiements.show', $paiement->id),
            ]);
        }

        return redirect()->route('esbtp.paiements.show', $paiement->id)->with('success', $message);
    }

    // ------------------------------------------------------------------

    /**
     * L'ecriture elle-meme : les lignes, la date de rectification, les traces.
     *
     * Le tout dans UNE transaction. Une ventilation remplacee sans sa date de
     * rectification donnerait un recu qui ne se declare pas corrige ; une date
     * sans les lignes annoncerait une correction qui n'a pas eu lieu.
     *
     * @param  array<int, float>  $ancienne
     * @param  array<int, float>  $nouvelle
     */
    private function appliquer(ESBTPPaiement $paiement, array $ancienne, array $nouvelle, string $motif): void
    {
        DB::transaction(function () use ($paiement, $nouvelle, $ancienne, $motif) {
            $this->repartition->remplacer($paiement, $nouvelle);

            // La date vit sur le versement pour que le recu puisse se declarer
            // rectifie sans aller lire le journal d'audit.
            $paiement->ventilation_rectifiee_le = now();
            $paiement->updated_by = auth()->id();
            $paiement->save();

            $this->tracer($paiement, $ancienne, $nouvelle, $motif);
        });

        // Journalise meme en cas de succes : une ecriture sur de l'argent deja
        // encaisse merite une trace hors de la base, consultable quand la base
        // est justement ce qu'on met en doute.
        Log::warning('Ventilation d\'un versement corrigée', [
            'paiement_id' => $paiement->id,
            'numero_recu' => $paiement->numero_recu,
            'ancienne' => $ancienne,
            'nouvelle' => $nouvelle,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Ce qui interdit de corriger ce versement, s'il y a lieu.
     */
    private function refusDeCorriger(ESBTPPaiement $paiement): ?string
    {
        // Un avoir n'est pas un encaissement : il REND du du au lieu d'en
        // eteindre. Le garde-fou de cet ecran compare chaque part au reste du
        // — une mesure qui n'a aucun sens pour une piece inverse, et qui
        // refuserait tout. Un avoir mal impute se corrige en emettant l'avoir
        // juste, pas en reecrivant celui-ci.
        if ($paiement->isAvoir()) {
            return 'Un avoir ne se reventile pas : sa répartition suit celle du versement '
                .'qu\'il annule. Émettez l\'avoir correct plutôt que de réécrire celui-ci.';
        }

        // Les totaux par frais ne comptent que les versements validés ou en
        // attente, et jamais les reliquats. Reventiler ailleurs écrirait des
        // lignes que personne ne lit — et poserait sur la fiche une mention de
        // rectification pour une correction sans effet.
        if (! in_array($paiement->status, ['validé', 'en_attente'], true)) {
            return 'Ce versement est '.($paiement->status ?: 'sans statut').' : il ne compte dans '
                .'aucun total par frais, sa répartition n\'a donc rien à corriger.';
        }

        if ($paiement->type_paiement === 'reliquat') {
            return 'Un report de reliquat ne se reventile pas : il n\'entre pas dans les totaux '
                .'par frais de l\'année en cours.';
        }

        if ($block = $this->assertPeriodNotLocked($paiement)) {
            return $block['message'];
        }

        if ($block = $this->assertReconciliationNotLocked($paiement)) {
            return $block['message'];
        }

        return null;
    }

    /**
     * Les frais a proposer, dans l'ordre de service de l'ecole.
     *
     * L'ordre vient de {@see RepartitionDuVersement::resteConnuParFrais()} — il
     * n'est pas recalcule ici. Deux familles s'ajoutent derriere, parce qu'elles
     * n'ont justement pas de reste connu et que ce serait pourtant une erreur de
     * les cacher : les frais dont l'ecole n'a pas encore fixe le tarif, et ceux
     * qui portent DEJA une part de ce versement (le frais mal choisi, souvent —
     * celui-la meme qu'on vient corriger).
     *
     * @return array<int, array{frais_category_id:int, name:string, reste:float|null, montant:float}>
     */
    private function lignes(ESBTPPaiement $paiement): array
    {
        $reste = $this->repartition->resteConnuParFrais(
            (int) $paiement->inscription_id,
            (int) $paiement->id
        );

        $actuelle = $this->ventilationActuelle($paiement);

        $ids = array_keys($reste);

        foreach (array_keys($actuelle) as $id) {
            if (! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        if ($paiement->frais_category_id && ! in_array((int) $paiement->frais_category_id, $ids, true)) {
            $ids[] = (int) $paiement->frais_category_id;
        }

        $noms = ESBTPFraisCategory::query()->whereIn('id', $ids)->pluck('name', 'id');

        $lignes = [];

        foreach ($ids as $id) {
            // Un frais supprime entre-temps : on n'invente pas de nom, on dit
            // lequel c'est. Cacher la ligne ferait disparaitre l'argent qu'elle
            // porte du total, et la correction ne ferait plus le compte.
            $lignes[] = [
                'frais_category_id' => $id,
                'name' => (string) ($noms[$id] ?? ('Frais #'.$id.' (supprimé)')),
                'reste' => array_key_exists($id, $reste) ? (float) $reste[$id] : null,
                'montant' => (float) ($actuelle[$id] ?? 0),
            ];
        }

        return $lignes;
    }

    /**
     * Ou va ce versement AUJOURD'HUI.
     *
     * Un versement sans allocation garde son comportement historique : sa
     * categorie unique fait foi et le montant entier lui revient. Le lire ainsi
     * permet de corriger aussi les versements anterieurs a la repartition, qui
     * sont precisement ceux que personne ne pouvait rectifier.
     *
     * @return array<int, float>
     */
    private function ventilationActuelle(ESBTPPaiement $paiement): array
    {
        $allocations = $paiement->allocations()->pluck('montant', 'frais_category_id');

        if ($allocations->isNotEmpty()) {
            return $allocations
                ->mapWithKeys(fn ($montant, $id) => [(int) $id => round((float) $montant, 2)])
                ->all();
        }

        if (! $paiement->frais_category_id) {
            return [];
        }

        return [(int) $paiement->frais_category_id => round((float) $paiement->montant, 2)];
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function identiques(array $a, array $b): bool
    {
        ksort($a);
        ksort($b);

        return array_map(fn ($v) => round($v, 2), $a) === array_map(fn ($v) => round($v, 2), $b);
    }

    /**
     * L'audit de synthese : une correction, une entree lisible.
     *
     * Les lignes d'allocation sont auditees chacune de leur cote
     * ({@see \App\Models\ESBTPPaiementAllocation}), ce qui donne la trace
     * exhaustive. Mais une suppression et deux creations ne racontent pas qu'il
     * s'agissait d'UNE correction, et ne portent nulle part le motif. Cet
     * evenement porte l'histoire ; les lignes portent la preuve.
     *
     * On appelle l'auditeur DIRECTEMENT plutot que de passer par l'evenement
     * `AuditCustom` prevu pour ca : dans owen-it/laravel-auditing 13, son
     * ecouteur declare `handle(Auditable $model)` alors que Laravel lui remet
     * l'evenement, et l'appel leve une TypeError. `Auditor::execute()` est
     * exactement ce que cet ecouteur ferait s'il recevait le bon argument.
     *
     * @param  array<int, float>  $ancienne
     * @param  array<int, float>  $nouvelle
     */
    private function tracer(ESBTPPaiement $paiement, array $ancienne, array $nouvelle, string $motif): void
    {
        $paiement->auditEvent = 'reventilation';
        $paiement->isCustomEvent = true;
        $paiement->auditCustomOld = ['ventilation' => $ancienne];
        $paiement->auditCustomNew = ['ventilation' => $nouvelle, 'motif' => $motif];

        try {
            Auditor::execute($paiement);
        } finally {
            // Sinon le versement reste marque « evenement custom » et le
            // prochain `save()` ordinaire ecrirait un audit `reventilation`
            // portant l'ancienne ventilation.
            $paiement->isCustomEvent = false;
            $paiement->auditCustomOld = null;
            $paiement->auditCustomNew = null;
        }
    }

    private function echec(\Illuminate\Http\Request $request, string $message, int $statut)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], $statut);
        }

        return redirect()->back()->withErrors(['repartition' => $message])->withInput();
    }
}
