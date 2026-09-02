<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\ApplicableFraisResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SouscriptionsObligatoiresManquantes
{
    public function __construct(private readonly ApplicableFraisResolver $resolver)
    {
    }

    /**
     * @param  array<int, int>|null  $inscriptionIds
     * @return array{
     *     total: int,
     *     inscriptions: int,
     *     lignes: array,
     *     lignes_retrait: array,
     *     total_ajouter: int,
     *     total_retirer: int,
     *     applique: bool
     * }
     */
    public function executer(bool $appliquer = false, ?int $anneeId = null, ?array $inscriptionIds = null): array
    {
        $inscriptions = ESBTPInscription::query()
            ->whereIn('status', ['active', 'en_attente'])
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->when($inscriptionIds, fn ($q) => $q->whereIn('id', $inscriptionIds))
            ->with(['etudiant', 'classe'])
            ->get();

        if ($inscriptions->isEmpty()) {
            return [
                'total' => 0,
                'total_ajouter' => 0,
                'total_retirer' => 0,
                'inscriptions' => 0,
                'lignes' => [],
                'lignes_retrait' => [],
                'applique' => false,
            ];
        }

        $dejaSouscrit = ESBTPFraisSubscription::query()
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->with('fraisCategory')
            ->get()
            ->groupBy('inscription_id');

        $lignes = [];
        $lignesRetrait = [];
        $aCreer = [];
        $aRetirer = [];

        foreach ($inscriptions as $inscription) {
            $subs = $dejaSouscrit->get($inscription->id, collect());
            $possedees = $subs->pluck('frais_category_id')->all();
            $voulues = $this->resolver->resolveMandatoryFeesForInscription($inscription)
                ->keyBy(fn (array $fee) => $fee['category']->id);
            $etudiant = $inscription->etudiant;
            $nom = $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : null;

            foreach ($voulues as $categoryId => $fee) {
                if (in_array($categoryId, $possedees, true)) {
                    continue;
                }
                $montant = (float) $fee['amount'];
                if ($montant <= 0) {
                    continue;
                }
                $categorie = $fee['category'];
                $lignes[] = [
                    'inscription_id' => $inscription->id,
                    'etudiant' => $nom,
                    'matricule' => $etudiant->matricule ?? null,
                    'classe' => $inscription->classe->name ?? null,
                    'categorie' => $categorie->name,
                    'categorie_id' => $categorie->id,
                    'montant' => $montant,
                    'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                    'action' => 'ajouter',
                ];
                $aCreer[] = [
                    'inscription_id' => $inscription->id,
                    'frais_category_id' => $categorie->id,
                    'amount' => $montant,
                ];
            }

            $paye = ESBTPPaiement::netPaidByCategory($inscription->id);

            foreach ($subs as $sub) {
                $categorie = $sub->fraisCategory;
                if (! $categorie || ! $categorie->is_mandatory) {
                    continue;
                }
                if ($voulues->has($categorie->id)) {
                    continue;
                }
                if ($sub->satisfied_in_kind) {
                    continue;
                }
                if ((float) ($paye[$categorie->id] ?? 0) > 0) {
                    continue;
                }
                $lignesRetrait[] = [
                    'inscription_id' => $inscription->id,
                    'etudiant' => $nom,
                    'matricule' => $etudiant->matricule ?? null,
                    'classe' => $inscription->classe->name ?? null,
                    'categorie' => $categorie->name,
                    'categorie_id' => $categorie->id,
                    'montant' => (float) $sub->amount,
                    'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                    'action' => 'retirer',
                    'motif' => ($categorie->audience ?? '') === 'nouveaux_etablissement'
                        ? 'Réservé aux nouveaux'
                        : 'Ne s\'applique plus',
                ];
                $aRetirer[] = $sub->id;
            }
        }

        $vide = [
            'total' => count($aCreer),
            'total_ajouter' => count($aCreer),
            'total_retirer' => count($aRetirer),
            'inscriptions' => count(array_unique(array_merge(
                array_column($aCreer, 'inscription_id'),
                array_column($lignesRetrait, 'inscription_id'),
            ))),
            'lignes' => $lignes,
            'lignes_retrait' => $lignesRetrait,
            'applique' => false,
        ];

        if (! $appliquer || ($aCreer === [] && $aRetirer === [])) {
            return $vide;
        }

        $auteur = auth()->id() ?? \App\Models\User::query()->min('id');

        DB::transaction(function () use ($aCreer, $aRetirer, $auteur): void {
            foreach ($aCreer as $ligne) {
                ESBTPFraisSubscription::create($ligne + [
                    'is_active' => true,
                    'subscribed_at' => now(),
                    'created_by' => $auteur,
                    'notes' => 'Régénération des frais obligatoires',
                ]);
            }
            if ($aRetirer !== []) {
                ESBTPFraisSubscription::query()->whereIn('id', $aRetirer)->delete();
            }
        });

        Log::warning('[frais] regeneration des souscriptions obligatoires', [
            'ajoutes' => count($aCreer),
            'retires' => count($aRetirer),
            'annee_id' => $anneeId,
        ]);

        $vide['applique'] = true;

        return $vide;
    }
}
