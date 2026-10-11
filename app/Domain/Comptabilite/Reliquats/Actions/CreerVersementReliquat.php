<?php

namespace App\Domain\Comptabilite\Reliquats\Actions;

use App\Enums\ModePaiement;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Models\User;
use App\Services\Caisse\CashSessionService;
use App\Services\MobileMoneyPaymentGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreerVersementReliquat
{
    /** Ne valide pas le règlement : la caisse conserve son workflow de validation. */
    public function execute(User $agent, int $reliquatId, float $montant, string $mode, ?string $note = null, ?string $requestKey = null): ESBTPPaiement
    {
        abort_unless(app(MobileMoneyPaymentGuard::class)->allowsMode($agent, $mode), 403);
        $canonique = ModePaiement::fromLegacy($mode)?->value;
        if ($canonique === null || $canonique === ModePaiement::AUTRE->value) {
            throw ValidationException::withMessages(['mode_paiement' => 'Mode de paiement non autorisé.']);
        }
        if ($montant <= 0 || round($montant, 2) !== $montant) {
            throw ValidationException::withMessages(['montant' => 'Le montant doit être positif, avec deux décimales au maximum.']);
        }

        app(CashSessionService::class)->assertEspecesAutorisees($agent, $canonique);

        return DB::transaction(function () use ($agent, $reliquatId, $montant, $canonique, $note, $requestKey) {
            $r = ESBTPReliquatDetail::query()->lockForUpdate()->findOrFail($reliquatId);
            $r->load(['inscriptionSource', 'inscriptionDestination', 'fraisSubscription.fraisCategory']);
            if (!$r->inscriptionSource || !$r->inscriptionDestination
                || (int) $r->inscriptionSource->etudiant_id !== (int) $r->inscriptionDestination->etudiant_id
                || !$r->fraisSubscription || (int) $r->fraisSubscription->inscription_id !== (int) $r->inscription_source_id) {
                throw ValidationException::withMessages(['reliquat_id' => 'Le rattachement de cette dette est incohérent : rapprochez le dossier.']);
            }

            // L'idempotence est contrôlée sous le même verrou que le solde.
            if ($requestKey) {
                $ancien = ESBTPPaiement::query()->where('reliquat_detail_id', $r->id)
                    ->where('created_by', $agent->id)
                    ->where('metadata->reliquat_request_key', $requestKey)->first();
                if ($ancien) {
                    if (abs((float) $ancien->montant - $montant) > 0.009
                        || ModePaiement::fromLegacy($ancien->mode_paiement)?->value !== $canonique) {
                        throw ValidationException::withMessages([
                            'request_key' => 'Une demande précédente utilise déjà cette clé pour un autre montant ou mode.',
                        ]);
                    }
                    return $ancien;
                }
            }

            if (ESBTPReliquatDetail::query()->where('frais_subscription_id', $r->frais_subscription_id)->count() > 1) {
                throw ValidationException::withMessages(['reliquat_id' => 'Plusieurs reports concernent cette dette. Vérification comptable requise.']);
            }

            $reserve = (float) ESBTPPaiement::query()->where('reliquat_detail_id', $r->id)
                ->where('type_paiement', 'reliquat')->where('status', 'en_attente')
                ->encaissements()->sum('montant');
            $disponible = max(0.0, round((float) $r->solde_restant - $reserve, 2));
            if ($montant > $disponible + 0.009) {
                throw ValidationException::withMessages([
                    'montant' => 'Disponible : '.number_format($disponible, 0, ',', ' ').' FCFA, règlements en attente déduits.',
                ]);
            }

            return ESBTPPaiement::create([
                'etudiant_id' => $r->inscriptionSource->etudiant_id,
                'inscription_id' => $r->inscription_destination_id,
                'annee_universitaire_id' => $r->inscriptionDestination->annee_universitaire_id,
                'frais_category_id' => $r->fraisSubscription->frais_category_id,
                'montant' => $montant,
                'mode_paiement' => $canonique,
                'date_paiement' => now()->toDateString(),
                'status' => 'en_attente',
                'nature' => 'encaissement',
                'type_paiement' => 'reliquat',
                'reliquat_detail_id' => $r->id,
                'motif' => $r->fraisSubscription->fraisCategory?->name ?? 'Reliquat',
                'numero_recu' => ESBTPPaiement::genererNumeroRecu(),
                'commentaire' => $note ?: 'Règlement de dette antérieure',
                'created_by' => $agent->id,
                'metadata' => $requestKey ? ['reliquat_request_key' => $requestKey] : null,
            ]);
        }, 3);
    }
}
