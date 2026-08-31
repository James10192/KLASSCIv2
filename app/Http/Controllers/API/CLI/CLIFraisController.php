<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Frais\CorrectionMontantSouscriptions;
use App\Services\Frais\RepartitionTropPercu;
use App\Services\Frais\SouscriptionsObligatoiresManquantes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Operations de frais pilotables a distance.
 *
 * Une correction de montant se decide en regardant les etudiants concernes, pas
 * en lancant une commande a l'aveugle. Cet endpoint MONTRE par defaut et
 * n'ecrit que si on le lui demande explicitement.
 */
class CLIFraisController extends BaseApiController
{
    /**
     * Montre tous les montants de souscription, et lesquels detonnent.
     *
     * Lecture seule : on ne corrige que ce qu'on a d'abord vu.
     */
    public function releverMontants(Request $request, CorrectionMontantSouscriptions $correction): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $releve = $correction->releverLesMontants();

        return $this->successResponse(
            $releve,
            sprintf(
                '%d montant(s) distinct(s), dont %d suspect(s).',
                count($releve['montants']),
                count($releve['suspects'])
            )
        );
    }

    /**
     * Cree les souscriptions obligatoires qui n'ont jamais ete posees.
     *
     * Ne touche a rien sans `apply`, et ne cree QUE ce qui manque : une
     * souscription existante porte une decision, on ne la revient pas.
     */
    public function souscriptionsManquantes(
        Request $request,
        SouscriptionsObligatoiresManquantes $rattrapage
    ): JsonResponse {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'annee_id' => ['nullable', 'integer'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $resultat = $rattrapage->executer(
            (bool) ($valide['apply'] ?? false),
            $valide['annee_id'] ?? null,
        );

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf('%d souscription(s) creee(s) sur %d inscription(s).', $resultat['total'], $resultat['inscriptions'])
                : sprintf("%d souscription(s) manquante(s) sur %d inscription(s). Rien n'a ete ecrit.", $resultat['total'], $resultat['inscriptions'])
        );
    }

    public function corrigerSouscriptions(
        Request $request,
        CorrectionMontantSouscriptions $correction
    ): JsonResponse {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'from' => ['required', 'numeric', 'min:0'],
            'to' => ['required', 'numeric', 'min:0'],
            'categorie_id' => ['nullable', 'integer'],
            'annee_id' => ['nullable', 'integer'],
            // `apply` doit etre demande. Le defaut ne touche a rien : on ne
            // corrige pas des montants sans les avoir regardes.
            'apply' => ['nullable', 'boolean'],
        ]);

        if ((float) $valide['from'] === (float) $valide['to']) {
            return $this->errorResponse('from et to sont identiques : il n\'y a rien a corriger.', [], 422);
        }

        $resultat = $correction->executer(
            (float) $valide['from'],
            (float) $valide['to'],
            (bool) ($valide['apply'] ?? false),
            $valide['categorie_id'] ?? null,
            $valide['annee_id'] ?? null,
        );

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf('%d souscription(s) corrigee(s).', $resultat['total'])
                : sprintf('%d souscription(s) concernee(s). Rien n\'a ete ecrit.', $resultat['total'])
        );
    }

    /**
     * Repartit les versements deja encaisses sur les frais qu'ils couvrent.
     *
     * Ne cree AUCUN paiement : dit seulement ou l'argent est alle. Un versement
     * qui depassait le frais designe voyait son excedent ecrete par le calcul du
     * restant — ni impute, ni signale. Cet endpoint le rend visible.
     *
     * Montre par defaut, n'ecrit que sur `apply`.
     */
    public function repartirTropPercu(Request $request, RepartitionTropPercu $repartition): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'inscription_id' => ['nullable', 'integer'],
            'annee_id' => ['nullable', 'integer'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $resultat = $repartition->executer(
            (bool) ($valide['apply'] ?? false),
            $valide['inscription_id'] ?? null,
            $valide['annee_id'] ?? null,
        );

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf(
                    '%d allocation(s) ecrite(s) sur %d versement(s), %d inscription(s).',
                    $resultat['allocations'], $resultat['paiements'], $resultat['inscriptions']
                )
                : sprintf(
                    "%d versement(s) a repartir sur %d inscription(s) — %d allocation(s). Rien n'a ete ecrit.",
                    $resultat['paiements'], $resultat['inscriptions'], $resultat['allocations']
                )
        );
    }
}
