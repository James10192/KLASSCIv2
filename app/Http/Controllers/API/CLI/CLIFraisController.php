<?php

namespace App\Http\Controllers\API\CLI;

use App\Exceptions\AllocationIncoherenteException;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\Setting;
use App\Services\TenantScolariteSettings;
use App\Services\Frais\CorrectionMontantSouscriptions;
use App\Services\Frais\OrdreDesCategoriesFrais;
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
    public function bareme(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $categories = ESBTPFraisCategory::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'is_mandatory', 'default_amount', 'is_active', 'accepts_in_kind']);

        $configurations = ESBTPFraisConfiguration::query()
            ->with(['fraisCategory:id,name,code', 'filiere:id,name', 'niveau:id,name'])
            ->where('is_active', true)
            ->get()
            ->map(fn (ESBTPFraisConfiguration $c) => [
                'configuration_id' => $c->id,
                'categorie_id' => $c->frais_category_id,
                'categorie' => $c->fraisCategory->name ?? null,
                'filiere_id' => $c->filiere_id,
                'filiere' => $c->filiere->name ?? null,
                'niveau_id' => $c->niveau_id,
                'niveau' => $c->niveau->name ?? null,
                'amount' => (float) $c->amount,
                'amount_affecte' => $c->amount_affecte !== null ? (float) $c->amount_affecte : null,
            ]);

        $souscriptions = ESBTPFraisSubscription::query()
            ->selectRaw('frais_category_id, amount, COUNT(*) as total')
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->groupBy('frais_category_id', 'amount')
            ->orderBy('frais_category_id')
            ->orderByDesc('amount')
            ->get()
            ->map(function ($ligne) use ($categories) {
                $cat = $categories->firstWhere('id', (int) $ligne->frais_category_id);

                return [
                    'categorie_id' => (int) $ligne->frais_category_id,
                    'categorie' => $cat->name ?? null,
                    'amount' => (float) $ligne->amount,
                    'souscriptions' => (int) $ligne->total,
                ];
            });

        $focus = $configurations
            ->filter(fn (array $l) => in_array($l['amount'], [40000.0, 60000.0], true))
            ->values();

        if ($focus->isEmpty()) {
            $focus = $souscriptions
                ->filter(fn (array $l) => in_array($l['amount'], [40000.0, 60000.0], true))
                ->values();
        }

        return $this->successResponse([
            'categories' => $categories,
            'configurations' => $configurations,
            'souscriptions' => $souscriptions,
            'montants_40k_60k' => $focus,
        ], 'Barème frais');
    }

    public function appliquerTenueNouveaux(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'category_id' => ['required', 'integer'],
            'niveau_source_id' => ['required', 'integer'],
            'niveau_cible_id' => ['required', 'integer'],
            'reduction' => ['nullable', 'numeric', 'min:0'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $categorie = ESBTPFraisCategory::query()->find($valide['category_id']);
        if (! $categorie) {
            return $this->errorResponse('Categorie introuvable.', [], 404);
        }

        $reduction = (float) ($valide['reduction'] ?? 10000);
        $appliquer = (bool) ($valide['apply'] ?? false);
        $sourceId = (int) $valide['niveau_source_id'];
        $cibleId = (int) $valide['niveau_cible_id'];

        $sources = ESBTPFraisConfiguration::query()
            ->with('filiere:id,name')
            ->where('frais_category_id', $categorie->id)
            ->where('niveau_id', $sourceId)
            ->where('is_active', true)
            ->get();

        $lignes = [];

        foreach ($sources as $source) {
            $plein = (float) $source->amount;
            if ($plein <= 0) {
                continue;
            }

            $cible = ESBTPFraisConfiguration::query()
                ->where('frais_category_id', $categorie->id)
                ->where('filiere_id', $source->filiere_id)
                ->where('niveau_id', $cibleId)
                ->where('is_active', true)
                ->first();

            if (! $cible) {
                continue;
            }

            $nouveauMontant = max(0, $plein - $reduction);
            $lignes[] = [
                'filiere' => $source->filiere->name ?? null,
                'filiere_id' => $source->filiere_id,
                'configuration_id' => $cible->id,
                'avant' => (float) $cible->amount,
                'apres' => $nouveauMontant,
                'plein_1a' => $plein,
            ];

            if ($appliquer) {
                $cible->update([
                    'amount' => $nouveauMontant,
                    'amount_affecte' => $nouveauMontant,
                ]);
            }
        }

        $audienceAvant = $categorie->audience ?? ESBTPFraisCategory::AUDIENCE_TOUS;
        if ($appliquer) {
            $categorie->update(['audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX]);
            Setting::firstOrCreate(
                ['key' => TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT],
                [
                    'value' => '1',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Demande a l agent de confirmer si l etudiant est nouveau ou deja passe par l etablissement.',
                    'is_required' => false,
                    'default_value' => '0',
                ]
            );
            Setting::query()
                ->where('key', TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT)
                ->update(['value' => '1']);
            if (method_exists(Setting::class, 'clearCache')) {
                Setting::clearCache();
            }
        }

        return $this->successResponse([
            'categorie' => $categorie->name,
            'audience_avant' => $audienceAvant,
            'audience_apres' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
            'reduction' => $reduction,
            'lignes' => $lignes,
            'applique' => $appliquer,
        ], $appliquer
            ? sprintf('%d barème(s) 2e année mis à jour. Audience = nouveaux.', count($lignes))
            : sprintf('%d barème(s) 2e année changeraient. Rien n\'a été écrit.', count($lignes)));
    }

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
            // Repart de zero. Necessaire apres un changement d'ordre de service :
            // sans lui, les versements deja repartis sont ignores et la nouvelle
            // priorite ne s'appliquerait qu'aux paiements futurs.
            'reset' => ['nullable', 'boolean'],
        ]);

        $reinitialiser = (bool) ($valide['reset'] ?? false);
        $appliquer = (bool) ($valide['apply'] ?? false);

        try {
            $resultat = $repartition->executer(
                $appliquer,
                $valide['inscription_id'] ?? null,
                $valide['annee_id'] ?? null,
                $reinitialiser,
            );
        } catch (AllocationIncoherenteException $e) {
            // Une repartition qui ne boucle pas ferait disparaitre la difference
            // des totaux par frais. On refuse d'ecrire et on dit lequel :
            // renvoyer un 500 opaque laisserait chercher.
            return $this->errorResponse($e->getMessage(), [], 422);
        }

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf(
                    '%d allocation(s) ecrite(s) sur %d versement(s), %d inscription(s)%s.',
                    $resultat['allocations'], $resultat['paiements'], $resultat['inscriptions'],
                    $reinitialiser ? sprintf(' — %d ancienne(s) allocation(s) effacee(s)', $resultat['effacees']) : ''
                )
                : sprintf(
                    "%d versement(s) a repartir sur %d inscription(s) — %d allocation(s)%s. Rien n'a ete ecrit.",
                    $resultat['paiements'], $resultat['inscriptions'], $resultat['allocations'],
                    $reinitialiser ? sprintf(', %d a effacer', $resultat['effacees']) : ''
                )
        );
    }

    /**
     * Range les categories de frais dans l'ordre voulu par l'ecole.
     *
     * Cet ordre n'est pas cosmetique : c'est celui dans lequel un versement
     * solde les frais. Une ecole qui veut que la tenue passe avant la scolarite
     * le dit ici — le code, lui, ne connait aucune priorite.
     *
     * Montre par defaut, n'ecrit que sur `apply`. Apres avoir applique, rejouer
     * `repartir-trop-percu` avec `reset` pour que les versements deja encaisses
     * suivent le nouvel ordre.
     */
    public function ordonnerCategories(Request $request, OrdreDesCategoriesFrais $ordre): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'integer'],
            'apply' => ['nullable', 'boolean'],
        ]);

        try {
            $resultat = $ordre->executer(
                array_map('intval', $valide['categories']),
                (bool) ($valide['apply'] ?? false),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        }

        return $this->successResponse(
            $resultat,
            $resultat['applique']
                ? sprintf('%d categorie(s) reordonnee(s).', $resultat['modifiees'])
                : sprintf("%d categorie(s) changeraient de rang. Rien n'a ete ecrit.", $resultat['modifiees'])
        );
    }
}
