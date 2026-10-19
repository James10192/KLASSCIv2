<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Services\ApplicableFraisResolver;
use OwenIt\Auditing\Models\Audit;

/**
 * L'absence de paiement n'est possible que pour des frais explicitement
 * exonérés, avec auteur + motif audités. Un zéro de barème est INCONNU.
 *
 * Aucun faux paiement de 0 FCFA n'est créé. On relit systématiquement la base
 * avant toute validation (un aperçu AJAX ne fait jamais autorité).
 */
final class ExonerationInscription
{
    public const TRACE = '[EXONERATION_INSCRIPTION_0_FCFA]';

    public function __construct(private readonly ApplicableFraisResolver $bareme)
    {
    }

    /** @return array{eligible: bool, mode: string, message: string, montant_du: float} */
    public function examiner(ESBTPInscription $inscription): array
    {
        $refus = static fn (string $message, float $montant = 0): array => [
            'eligible' => false, 'mode' => 'blocked', 'message' => $message, 'montant_du' => $montant,
        ];

        $souscriptions = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get();

        if ($souscriptions->isEmpty()) {
            return $refus('Aucune souscription active : les frais doivent être configurés et vérifiés.');
        }

        // Ne pas confondre catégorie obligatoire absente et gratuité.
        $categories = $souscriptions->pluck('frais_category_id')->map(fn ($id) => (int) $id)->all();
        foreach ($this->bareme->resolveMandatoryFeesForInscription($inscription) as $frais) {
            if ((float) $frais['amount'] <= 0) {
                return $refus('Le barème d’un frais obligatoire n’est pas défini pour cette inscription.');
            }
            if (! in_array((int) $frais['category']->id, $categories, true)) {
                return $refus('Un frais obligatoire est absent des souscriptions. Complétez les frais avant validation.');
            }
        }

        foreach ($souscriptions->where('satisfied_in_kind', true) as $depot) {
            if (! $depot->deposited_at || ! $depot->deposited_by) {
                return $refus('Un dépôt en nature n’est pas confirmé par un auteur et une date.');
            }
        }

        $du = (float) $souscriptions->sum(fn (ESBTPFraisSubscription $s) => $s->chargedAmount());
        if ($du > 0) {
            return $refus('Il reste des souscriptions payantes : un paiement validé est requis.', $du);
        }

        // Un paiement en attente ne se transforme jamais implicitement en validé.
        if ($inscription->paiements()->where('status', 'en_attente')->exists()) {
            return $refus('Un paiement est en attente de validation. Traitez-le avant de valider l’inscription.');
        }

        $aAuditer = $souscriptions->filter(fn (ESBTPFraisSubscription $s) => ! $s->satisfied_in_kind);
        if ($aAuditer->isEmpty()) {
            return $refus('Une validation sans paiement exige au moins une exonération monétaire explicite.');
        }
        if ($aAuditer->isNotEmpty()) {
            $audits = Audit::query()
                ->where('auditable_type', ESBTPFraisSubscription::class)
                ->whereIn('auditable_id', $aAuditer->pluck('id')->all())
                ->where('event', 'updated')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['auditable_id', 'new_values', 'tags']);

            foreach ($aAuditer as $s) {
                if ((float) $s->amount !== 0.0) {
                    return $refus('Le montant d’une souscription n’est pas explicitement nul.');
                }
                $preuve = $audits->first(function (Audit $audit) use ($s) {
                    if ((int) $audit->auditable_id !== (int) $s->id) {
                        return false;
                    }
                    $valeurs = $audit->new_values;

                    return (is_array($valeurs) && array_key_exists('amount', $valeurs))
                        || str_contains((string) $audit->tags, ESBTPFraisSubscription::TAG_EXONERATION);
                });

                if (! $preuve
                    || ! str_contains((string) $preuve->tags, ESBTPFraisSubscription::TAG_EXONERATION)
                    || ! str_contains((string) $s->notes, self::TRACE)
                    || ! preg_match('/utilisateur #\\d+/', (string) $s->notes)) {
                    return $refus('Un frais à 0 FCFA ne possède pas de dispense auditée. Confirmez cet ajustement avec un motif.');
                }
            }
        }

        return [
            'eligible' => true,
            'mode' => 'explicit_waiver',
            'message' => 'Toutes les souscriptions sont exonérées ou satisfaites en nature : aucun paiement n’est requis.',
            'montant_du' => 0.0,
        ];
    }

    public function estApplicable(ESBTPInscription $inscription): bool
    {
        return $this->examiner($inscription)['eligible'];
    }
}
