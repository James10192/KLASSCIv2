<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cree les souscriptions obligatoires qui n'ont jamais ete posees.
 *
 * Le formulaire d'inscription n'envoyait aucun montant pour les frais acceptant
 * un depot en nature : il ne transmettait que la case « depose ». Or le
 * controleur passe son chemin des que le montant est nul. Aucune souscription
 * n'etait donc creee — ni pour celui qui depose, ni pour celui qui ne depose pas.
 *
 * Le frais disparaissait purement et simplement : absent du recu, absent de la
 * caisse, alors que le formulaire promettait « sinon le montant sera du ».
 *
 * Sur ISLG, deux categories obligatoires sont dans ce cas — le paquet de
 * ramettes et la chemise cartonnee. Le formulaire est corrige, mais les
 * etudiants deja inscrits gardent leur trou.
 *
 * Cette classe ne cree QUE ce qui manque, et ne touche jamais a une souscription
 * existante : un montant deja pose est une decision, on ne la revient pas.
 */
class SouscriptionsObligatoiresManquantes
{
    /**
     * @return array{total: int, inscriptions: int, lignes: array, applique: bool}
     */
    public function executer(bool $appliquer = false, ?int $anneeId = null): array
    {
        $categories = ESBTPFraisCategory::query()
            ->where('is_active', true)
            ->where('is_mandatory', true)
            ->get();

        if ($categories->isEmpty()) {
            return ['total' => 0, 'inscriptions' => 0, 'lignes' => [], 'applique' => false];
        }

        $inscriptions = ESBTPInscription::query()
            ->where('status', 'active')
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->with(['etudiant', 'classe'])
            ->get();

        $dejaSouscrit = ESBTPFraisSubscription::query()
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->get()
            ->groupBy('inscription_id')
            ->map(fn ($g) => $g->pluck('frais_category_id')->all());

        $lignes = [];
        $aCreer = [];

        foreach ($inscriptions as $inscription) {
            $possedees = $dejaSouscrit->get($inscription->id, []);

            foreach ($categories as $categorie) {
                if (in_array($categorie->id, $possedees, true)) {
                    continue;
                }

                $montant = $this->montantPour($categorie, $inscription);

                // Un frais obligatoire sans montant resolvable ne se cree pas :
                // on ne reclamerait rien de chiffrable, et une souscription a
                // zero se confondrait avec une exemption.
                if ($montant <= 0) {
                    continue;
                }

                $etudiant = $inscription->etudiant;

                $lignes[] = [
                    'inscription_id' => $inscription->id,
                    'etudiant' => $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : null,
                    'matricule' => $etudiant->matricule ?? null,
                    'classe' => $inscription->classe->name ?? null,
                    'categorie' => $categorie->name,
                    'categorie_id' => $categorie->id,
                    'montant' => $montant,
                    'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                ];

                $aCreer[] = [
                    'inscription_id' => $inscription->id,
                    'frais_category_id' => $categorie->id,
                    'amount' => $montant,
                ];
            }
        }

        if (! $appliquer || $aCreer === []) {
            return [
                'total' => count($aCreer),
                'inscriptions' => count(array_unique(array_column($aCreer, 'inscription_id'))),
                'lignes' => $lignes,
                'applique' => false,
            ];
        }

        // `created_by` est NOT NULL sans defaut sur cette table. Appelee depuis
        // l'API CLI la commande a un utilisateur authentifie ; lancee en console
        // elle n'en a pas, et l'insertion echouerait sur un 1364 — le meme defaut
        // que esbtp_paiements.type_paiement corrige plus tot aujourd'hui.
        $auteur = auth()->id() ?? \App\Models\User::query()->min('id');

        $crees = DB::transaction(function () use ($aCreer, $auteur): int {
            $n = 0;

            foreach ($aCreer as $ligne) {
                ESBTPFraisSubscription::create($ligne + [
                    'is_active' => true,
                    'subscribed_at' => now(),
                    'created_by' => $auteur,
                    'notes' => 'Souscription obligatoire manquante, creee par rattrapage',
                ]);
                $n++;
            }

            return $n;
        });

        Log::warning('[frais] rattrapage des souscriptions obligatoires manquantes', [
            'lignes' => $crees,
            'annee_id' => $anneeId,
        ]);

        return [
            'total' => $crees,
            'inscriptions' => count(array_unique(array_column($aCreer, 'inscription_id'))),
            'lignes' => $lignes,
            'applique' => true,
        ];
    }

    /**
     * Le montant a reclamer : la configuration si elle existe, le defaut sinon.
     *
     * Le meme ordre que partout ailleurs, moins la souscription — puisque c'est
     * precisement celle qui manque.
     */
    private function montantPour(ESBTPFraisCategory $categorie, ESBTPInscription $inscription): float
    {
        $configuration = ESBTPFraisConfiguration::getApplicableConfiguration(
            $categorie->id,
            $inscription->filiere_id,
            $inscription->niveau_id,
            $inscription->annee_universitaire_id
        );

        if ($configuration) {
            $statut = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

            return (float) $configuration->getMontantByStatus($statut);
        }

        return (float) ($categorie->default_amount ?? 0);
    }
}
