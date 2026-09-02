<?php

namespace App\Http\Controllers\API\CLI;

use App\Exceptions\AllocationIncoherenteException;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\Setting;
use App\Services\FraisConfigurationWriter;
use App\Services\TenantScolariteSettings;
use App\Services\Frais\CorrectionMontantSouscriptions;
use App\Services\Frais\OrdreDesCategoriesFrais;
use App\Services\Frais\RepartitionTropPercu;
use App\Services\Frais\SoldesParSouscription;
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

        // `audience` et `sort_order` decident QUI paie et dans quel ordre un
        // versement solde les frais. Les omettre ici rendait un bareme
        // inauditable a distance.
        $categories = ESBTPFraisCategory::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'is_mandatory', 'audience', 'sort_order', 'default_amount', 'is_active', 'accepts_in_kind']);

        // En LMD la portee est le parcours, pas la filiere : sans
        // `parcours_id`, toutes les lignes d'un tenant LMD se ressemblaient
        // (filiere nulle) et rien n'etait verifiable. Les montants par statut
        // d'affectation manquaient pour la meme raison.
        $configurations = ESBTPFraisConfiguration::query()
            ->with(['fraisCategory:id,name,code', 'filiere:id,name', 'niveau:id,name', 'parcours:id,name,code'])
            ->where('is_active', true)
            ->get()
            ->map(fn (ESBTPFraisConfiguration $c) => [
                'configuration_id' => $c->id,
                'categorie_id' => $c->frais_category_id,
                'categorie' => $c->fraisCategory->name ?? null,
                'systeme' => $c->systeme_academique,
                'filiere_id' => $c->filiere_id,
                'filiere' => $c->filiere->name ?? null,
                'parcours_id' => $c->parcours_id,
                'parcours' => $c->parcours->name ?? null,
                'niveau_id' => $c->niveau_id,
                'niveau' => $c->niveau->name ?? null,
                'annee_universitaire_id' => $c->annee_universitaire_id,
                'amount' => (float) $c->amount,
                'amount_affecte' => $c->amount_affecte !== null ? (float) $c->amount_affecte : null,
                'amount_reaffecte' => $c->amount_reaffecte !== null ? (float) $c->amount_reaffecte : null,
                'amount_non_affecte' => $c->amount_non_affecte !== null ? (float) $c->amount_non_affecte : null,
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

    public function soldesInscription(Request $request, SoldesParSouscription $soldes): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $valide = $request->validate([
            'inscription_id' => ['nullable', 'integer', 'exists:esbtp_inscriptions,id'],
        ]);

        $inscription = isset($valide['inscription_id'])
            ? ESBTPInscription::with(['etudiant', 'classe'])->find($valide['inscription_id'])
            : $this->echantillonSoldes();

        if (! $inscription) {
            return $this->errorResponse('Aucune inscription avec souscription active.', [], 404);
        }

        $souscriptions = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get()
            ->map(function (ESBTPFraisSubscription $sub) {
                $cat = $sub->fraisCategory;
                $catalogue = (float) ($cat->default_amount ?? 0);

                return [
                    'categorie_id' => (int) $sub->frais_category_id,
                    'categorie' => $cat->name ?? null,
                    'audience' => $cat->audience ?? ESBTPFraisCategory::AUDIENCE_TOUS,
                    'accepts_in_kind' => (bool) ($cat->accepts_in_kind ?? false),
                    'amount' => (float) $sub->amount,
                    'charged' => (float) $sub->chargedAmount(),
                    'satisfied_in_kind' => (bool) $sub->satisfied_in_kind,
                    'catalogue' => $catalogue,
                    'ecart_catalogue' => round((float) $sub->chargedAmount() - $catalogue, 2),
                ];
            });

        $computed = $soldes->pourInscription($inscription);

        return $this->successResponse([
            'inscription_id' => $inscription->id,
            'etudiant' => trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? '')),
            'matricule' => $inscription->etudiant->matricule ?? null,
            'classe' => $inscription->classe->name ?? null,
            'statut_etablissement' => $inscription->statut_etablissement,
            'confirmer_statut' => app(TenantScolariteSettings::class)->confirmerStatutEtablissement(),
            'souscriptions' => $souscriptions,
            'soldes' => [
                'total_due' => $computed['total_due'],
                'total_paid' => $computed['total_paid'],
                'total_remaining' => $computed['total_remaining'],
                'categories' => array_values($computed['categories']),
            ],
        ], 'Soldes par souscription');
    }

    private function echantillonSoldes(): ?ESBTPInscription
    {
        $sub = ESBTPFraisSubscription::query()
            ->where('is_active', true)
            ->where('amount', '>', 0)
            ->latest('id')
            ->first();

        return $sub
            ? ESBTPInscription::with(['etudiant', 'classe'])->find($sub->inscription_id)
            : null;
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
                ? sprintf('%d ajoutée(s), %d retirée(s) sur %d inscription(s).', $resultat['total_ajouter'], $resultat['total_retirer'], $resultat['inscriptions'])
                : sprintf("%d à ajouter, %d à retirer sur %d inscription(s). Rien n'a ete ecrit.", $resultat['total_ajouter'], $resultat['total_retirer'], $resultat['inscriptions'])
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

    public function poserBareme(Request $request, FraisConfigurationWriter $writer): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'apply' => ['nullable', 'boolean'],
            'confirmer_statut' => ['nullable', 'boolean'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.code' => ['required', 'string', 'max:50'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.is_mandatory' => ['nullable', 'boolean'],
            'categories.*.audience' => ['nullable', 'in:tous,nouveaux_etablissement,anciens_etablissement'],
            'categories.*.category_type' => ['nullable', 'in:academic,service,administrative'],
            'categories.*.default_amount' => ['nullable', 'numeric', 'min:0'],
            'configurations' => ['required', 'array', 'min:1'],
            'configurations.*.category_code' => ['required', 'string'],
            'configurations.*.systeme' => ['required', 'in:LMD,BTS'],
            'configurations.*.parcours_id' => ['nullable', 'integer'],
            'configurations.*.filiere_id' => ['nullable', 'integer'],
            'configurations.*.niveau_id' => ['required', 'integer'],
            'configurations.*.amount' => ['required', 'numeric', 'min:0'],
            'configurations.*.amount_affecte' => ['nullable', 'numeric', 'min:0'],
            'configurations.*.amount_reaffecte' => ['nullable', 'numeric', 'min:0'],
            'configurations.*.amount_non_affecte' => ['nullable', 'numeric', 'min:0'],
        ]);

        $appliquer = (bool) ($valide['apply'] ?? false);
        $codes = collect($valide['categories'])->keyBy(fn ($c) => strtoupper($c['code']));

        foreach ($valide['configurations'] as $ligne) {
            if (! $codes->has(strtoupper($ligne['category_code']))) {
                return $this->errorResponse('Categorie inconnue dans configurations: '.$ligne['category_code'], [], 422);
            }
        }

        if (! $appliquer) {
            return $this->successResponse([
                'categories' => count($valide['categories']),
                'configurations' => count($valide['configurations']),
                'applique' => false,
            ], sprintf(
                '%d categorie(s), %d configuration(s). Rien n\'a ete ecrit.',
                count($valide['categories']),
                count($valide['configurations'])
            ));
        }

        $auteur = (int) $request->user()->id;
        $parCode = [];

        foreach ($valide['categories'] as $i => $cat) {
            $code = strtoupper($cat['code']);
            $modele = ESBTPFraisCategory::query()->where('code', $code)->first();
            if (! $modele) {
                $modele = ESBTPFraisCategory::create([
                    'name' => $cat['name'],
                    'code' => $code,
                    'is_mandatory' => (bool) ($cat['is_mandatory'] ?? true),
                    'audience' => $cat['audience'] ?? ESBTPFraisCategory::AUDIENCE_TOUS,
                    'category_type' => $cat['category_type'] ?? 'academic',
                    'default_amount' => (float) ($cat['default_amount'] ?? 0),
                    'payment_deadline_days' => 30,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                    'accepts_in_kind' => false,
                ]);
                ESBTPFraisOption::create([
                    'configuration_id' => null,
                    'name' => 'Standard',
                    'description' => 'Option standard pour '.$modele->name,
                    'additional_amount' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'available_from' => now(),
                    'sort_order' => 1,
                ]);
            } else {
                $modele->fill([
                    'name' => $cat['name'],
                    'is_mandatory' => (bool) ($cat['is_mandatory'] ?? $modele->is_mandatory),
                    'audience' => $cat['audience'] ?? $modele->audience,
                    'category_type' => $cat['category_type'] ?? $modele->category_type,
                    'default_amount' => $cat['default_amount'] ?? $modele->default_amount,
                    'is_active' => true,
                ])->save();
            }
            $parCode[$code] = $modele;
        }

        $parPortee = [];
        foreach ($valide['configurations'] as $ligne) {
            $systeme = strtoupper($ligne['systeme']);
            $cle = implode('|', [
                $systeme,
                $ligne['parcours_id'] ?? '',
                $ligne['filiere_id'] ?? '',
                $ligne['niveau_id'],
            ]);
            $parPortee[$cle]['scope'] = [
                'systeme' => $systeme,
                'parcours_id' => $ligne['parcours_id'] ?? null,
                'filiere_id' => $ligne['filiere_id'] ?? null,
                'niveau_id' => $ligne['niveau_id'],
            ];
            $cat = $parCode[strtoupper($ligne['category_code'])];
            $parPortee[$cle]['categories'][$cat->id] = [
                'amount' => $ligne['amount'],
                'amount_affecte' => $ligne['amount_affecte'] ?? $ligne['amount'],
                'amount_reaffecte' => $ligne['amount_reaffecte'] ?? $ligne['amount'],
                'amount_non_affecte' => $ligne['amount_non_affecte'] ?? $ligne['amount'],
                'deadline_days' => 30,
            ];
        }

        $created = 0;
        $updated = 0;
        foreach ($parPortee as $bloc) {
            $resultat = $writer->persistCategories(
                $bloc['scope'],
                $bloc['categories'],
                'global',
                null,
                $auteur,
                'overwrite_all',
            );
            $created += $resultat['created'];
            $updated += $resultat['updated'];
        }

        if ($valide['confirmer_statut'] ?? false) {
            Setting::updateOrCreate(
                ['key' => TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT],
                [
                    'value' => '1',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'is_required' => false,
                ]
            );
            if (method_exists(Setting::class, 'clearCache')) {
                Setting::clearCache();
            }
        }

        return $this->successResponse([
            'categories' => count($parCode),
            'configurations_creees' => $created,
            'configurations_maj' => $updated,
            'applique' => true,
        ], sprintf('%d categorie(s), %d config(s) creees, %d maj.', count($parCode), $created, $updated));
    }
}
