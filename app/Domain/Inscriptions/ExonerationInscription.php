<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Services\ApplicableFraisResolver;
use OwenIt\Auditing\Models\Audit;
use App\Domain\Comptabilite\Souscriptions\PreuveExonerationSouscription;

/**
 * L'absence de paiement est autorisée si le barème effectif contient une
 * gratuité configurée OU si une exonération individuelle est auditée.
 * Un montant issu du catalogue par défaut ne prouve jamais une gratuité.
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

        // Le resolver lit le barème effectif (LMD : parcours/niveau, BTS :
        // filière/niveau, avec priorité à l'année de l'inscription).
        // NE JAMAIS déduire la présence du barème du seul montant calculé :
        // le fallback default_amount, même positif, n'est pas une configuration.
        $categories = $souscriptions->pluck('frais_category_id')->map(fn ($id) => (int) $id)->all();
        $montantsNormaux = [];
        $gratuiteConfiguree = false;
        foreach ($this->bareme->resolveMandatoryFeesForInscription($inscription) as $frais) {
            if (! $frais['configuration']) {
                return $refus('Barème manquant : un frais obligatoire ne possède pas de configuration applicable.');
            }
            if ($frais['configuration']->getMontantByStatus(
                $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
            ) === null) {
                return $refus('Montant non défini dans le barème applicable pour ce statut d’affectation.');
            }
            if ((float) $frais['amount'] < 0) {
                return $refus('Montant négatif invalide dans le barème applicable.');
            }
            $categoryId = (int) $frais['category']->id;
            if (! in_array($categoryId, $categories, true)) {
                return $refus('Un frais obligatoire est absent des souscriptions. Complétez les frais avant validation.');
            }
            $montantsNormaux[$categoryId] = (float) $frais['amount'];
            $gratuiteConfiguree = $gratuiteConfiguree || $montantsNormaux[$categoryId] === 0.0;
        }

        foreach ($souscriptions->where('satisfied_in_kind', true) as $depot) {
            if (! $depot->deposited_at || ! $depot->deposited_by) {
                return $refus('Un dépôt en nature n’est pas confirmé par un auteur et une date.');
            }
        }

        if ($souscriptions->contains(fn (ESBTPFraisSubscription $s) => (float) $s->amount < 0)) {
            return $refus('Une souscription présente un montant négatif non autorisé.');
        }

        $du = (float) $souscriptions->sum(fn (ESBTPFraisSubscription $s) => $s->chargedAmount());
        if ($du > 0) {
            return $refus('Il reste des souscriptions payantes : un paiement validé est requis.', $du);
        }

        // Un paiement en attente ne se transforme jamais implicitement en validé.
        if ($inscription->paiements()->where('status', 'en_attente')->exists()) {
            return $refus('Un paiement est en attente de validation. Traitez-le avant de valider l’inscription.');
        }

        // Seul un zéro sur barème normal positif est une exonération.
        // Pour un optionnel sans montant normal prouvé, conserver l'audit
        // conservateur de la PR #1632 : jamais de gratuité déduite par défaut.
        $aAuditer = $souscriptions->filter(fn (ESBTPFraisSubscription $s) => ! $s->satisfied_in_kind
            && ($montantsNormaux[(int) $s->frais_category_id] ?? INF) > 0);
        if ($aAuditer->isEmpty() && ! $gratuiteConfiguree) {
            return $refus('Une validation sans paiement exige une gratuité configurée ou une exonération monétaire explicite.');
        }
        if ($aAuditer->isNotEmpty()) {
            $audits = Audit::query()
                ->where('auditable_type', ESBTPFraisSubscription::class)
                ->whereIn('auditable_id', $aAuditer->pluck('id')->all())
                ->where('event', 'updated')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['auditable_id', 'auditable_type', 'event', 'new_values', 'tags', 'user_id']);

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

                if (! app(PreuveExonerationSouscription::class)->preuveValide($s, $preuve)) {
                    $nomFrais = $s->fraisCategory?->name ?? ('Catégorie #'.$s->frais_category_id);
                    return $refus('Exonération à reconfirmer pour « '.$nomFrais.' » : le montant est à 0 FCFA mais la preuve signée est insuffisante. Un utilisateur habilité doit reconfirmer ce montant inchangé avec un nouveau motif depuis la fiche étudiant ou inscription.');
                }
            }
        }

        return [
            'eligible' => true,
            'mode' => $aAuditer->isEmpty() ? 'configured_free' : 'explicit_waiver',
            'message' => $aAuditer->isEmpty()
                ? 'Gratuité configurée dans le barème applicable : aucun paiement n’est requis.'
                : 'Exonération individuelle confirmée et auditée : aucun paiement n’est requis.',
            'montant_du' => 0.0,
        ];
    }

    public function estApplicable(ESBTPInscription $inscription): bool
    {
        return $this->examiner($inscription)['eligible'];
    }
}
