<?php

namespace App\Services;

use App\Exceptions\AvoirForbiddenException;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvoirService
{
    public const KIND_CREDIT = 'credit';
    public const KIND_REFUND = 'refund';

    public function availableAmount(ESBTPPaiement $parent): float
    {
        return $parent->avoir_disponible;
    }

    public function issue(ESBTPPaiement $parent, float $montant, string $kind, string $motif, int $userId): ESBTPPaiement
    {
        $this->assertCanIssue($parent, $montant, $kind, $motif);

        return DB::transaction(function () use ($parent, $montant, $kind, $motif, $userId) {
            $numero = ESBTPPaiement::genererNumeroRecu('AV');

            return ESBTPPaiement::create([
                'inscription_id' => $parent->inscription_id,
                'etudiant_id' => $parent->etudiant_id,
                'annee_universitaire_id' => $parent->annee_universitaire_id,
                'frais_category_id' => $parent->frais_category_id,
                'categorie_id' => $parent->categorie_id,
                'montant' => $montant,
                'mode_paiement' => $kind === self::KIND_REFUND ? ($parent->mode_paiement ?: 'espèces') : 'avoir',
                'date_paiement' => now()->toDateString(),
                'status' => 'validé',
                'nature' => 'avoir',
                'avoir_kind' => $kind,
                'parent_paiement_id' => $parent->id,
                'numero_recu' => $numero,
                'numero_avoir' => $numero,
                'motif' => $motif,
                'created_by' => $userId,
                'validateur_id' => $userId,
                'date_validation' => now(),
            ]);
        });
    }

    private function assertCanIssue(ESBTPPaiement $parent, float $montant, string $kind, string $motif): void
    {
        if ($parent->isAvoir()) {
            throw new AvoirForbiddenException('Impossible d\'émettre un avoir sur un avoir.');
        }

        if ($parent->status !== 'validé') {
            throw new AvoirForbiddenException('Un avoir ne peut être émis que sur un paiement validé.');
        }

        if (! in_array($kind, [self::KIND_CREDIT, self::KIND_REFUND], true)) {
            throw new AvoirForbiddenException('Type d\'avoir invalide.');
        }

        if (mb_strlen(trim($motif)) < 5) {
            throw new AvoirForbiddenException('Le motif de l\'avoir est obligatoire (5 caractères minimum).');
        }

        if ($montant <= 0) {
            throw new AvoirForbiddenException('Le montant de l\'avoir doit être positif.');
        }

        $available = $this->availableAmount($parent);
        if ($montant - $available > 0.009) {
            throw new AvoirForbiddenException(
                'Montant supérieur au reliquat avoir disponible ('.number_format($available, 0, ',', ' ').' FCFA).'
            );
        }

        if ($parent->reconciliation_locked_at) {
            throw new AvoirForbiddenException('Ce paiement est verrouillé par une clôture de caisse.');
        }

        $periodLock = $this->periodLockMessage($parent);
        if ($periodLock) {
            throw new AvoirForbiddenException($periodLock);
        }
    }

    private function periodLockMessage(ESBTPPaiement $paiement): ?string
    {
        $lockedUntil = SettingsHelper::get('comptabilite.period_locked_until');
        if (! $lockedUntil) {
            return null;
        }

        try {
            $lockDate = Carbon::parse($lockedUntil)->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        $paiementDate = $paiement->date_paiement
            ? Carbon::parse($paiement->date_paiement)
            : ($paiement->created_at ?: now());

        if ($paiementDate->gt($lockDate)) {
            return null;
        }

        return sprintf(
            'Période comptable verrouillée jusqu\'au %s.',
            $lockDate->translatedFormat('d/m/Y')
        );
    }
}
