<?php

namespace App\Enums;

/**
 * Modes de paiement canoniques KLASSCI.
 *
 * Source unique de vérité — utiliser via Rule::enum(ModePaiement::class)
 * dans les FormRequests.
 *
 * `carte`, `djamo` et `autre` figuraient dans config/payment_modes.php et dans
 * les ecrans de caisse, mais manquaient ici. Or c'est cette enum que lit la RECONCILIATION : un
 * encaissement par carte etait donc accepte au guichet puis invisible du
 * rapprochement — de l'argent recu qu'aucun comptage ne pouvait justifier.
 *
 * Back-compat : la DB esbtp_paiements stocke des strings libres (legacy).
 * Cette enum n'a PAS de migration de valeur — elle agit côté code uniquement
 * pour empêcher de nouvelles dérives. Les valeurs DB historiques (ex: 'Espèces',
 * 'mobile') passent toujours, mais les nouveaux saves doivent matcher.
 */
enum ModePaiement: string
{
    case ESPECES = 'especes';
    case MOBILE_MONEY = 'mobile_money';
    case VIREMENT = 'virement';
    case CARTE = 'carte';
    case CHEQUE = 'cheque';
    case WAVE = 'wave';
    case ORANGE_MONEY = 'orange_money';
    case MTN_MONEY = 'mtn_money';
    case MOOV_MONEY = 'moov_money';
    case DJAMO = 'djamo';

    /**
     * Celtiis Cash — le mobile money de SBIN SA, l'operateur public beninois.
     *
     * Absent tant que KLASSCI ne servait que la Cote d'Ivoire ; `ucao-benin` est
     * la premiere instance hors CI, et un mode manquant ne fait pas refuser
     * l'encaissement, il le rend INVISIBLE du rapprochement de caisse.
     *
     * Les autres operateurs beninois sont deja couverts et n'ont PAS besoin de
     * cases a eux : MTN Benin encaisse sous MoMo (`MTN_MONEY`) et Moov Africa
     * Benin sous Flooz (`MOOV_MONEY`, que `fromLegacy()` reconnait deja).
     *
     * Ne retirez aucun mode ivoirien en echange : l'enum est partage par les
     * huit instances. Un mode inutilise ne coute rien.
     */
    case CELTIIS_CASH = 'celtiis_cash';

    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ESPECES => 'Espèces',
            self::MOBILE_MONEY => 'Mobile Money (générique)',
            self::VIREMENT => 'Virement bancaire',
            self::CARTE => 'Carte bancaire',
            self::CHEQUE => 'Chèque',
            self::WAVE => 'Wave',
            self::ORANGE_MONEY => 'Orange Money',
            self::MTN_MONEY => 'MTN MoMo',
            self::MOOV_MONEY => 'Moov Money',
            self::DJAMO => 'Djamo',
            self::CELTIIS_CASH => 'Celtiis Cash',
            self::AUTRE => 'Autre',
        };
    }

    public function isDrawer(): bool
    {
        return $this === self::ESPECES;
    }

    public function icon(): string
    {
        return match ($this) {
            self::ESPECES => 'fa-money-bill-wave',
            self::CHEQUE => 'fa-money-check',
            self::VIREMENT => 'fa-university',
            self::CARTE => 'fa-credit-card',
            self::AUTRE => 'fa-question-circle',
            default => 'fa-mobile-screen',
        };
    }

    /**
     * @return array<int,string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /**
     * Pour `<x-au-select :options="$modes">`.
     *
     * @return array<string,string>
     */
    public static function selectOptions(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }
        return $out;
    }

    /**
     * Normalise une valeur libre legacy ('Espèces', 'ESP', 'mobile') vers une case canonique.
     * Retourne null si non reconnaissable (caller décide quoi faire).
     */
    public static function fromLegacy(?string $raw): ?self
    {
        if (!$raw) {
            return null;
        }
        $normalized = strtolower(trim($raw));
        $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);

        return match (true) {
            in_array($normalized, ['especes', 'esp', 'cash', 'liquide'], true) => self::ESPECES,
            str_contains($normalized, 'wave') => self::WAVE,
            str_contains($normalized, 'orange') => self::ORANGE_MONEY,
            str_contains($normalized, 'mtn') || str_contains($normalized, 'momo') => self::MTN_MONEY,
            str_contains($normalized, 'moov') || str_contains($normalized, 'flooz') => self::MOOV_MONEY,
            // Avant le repli 'mobile' : « Celtiis Mobile Money » y tomberait sinon.
            // Le mot « cash » de la marque ne peut pas, lui, faire confondre avec
            // les especes : ce controle-la est un `in_array` exact, pas un contains.
            str_contains($normalized, 'celtiis') => self::CELTIIS_CASH,
            str_contains($normalized, 'mobile') => self::MOBILE_MONEY,
            str_contains($normalized, 'virement') || str_contains($normalized, 'bank') => self::VIREMENT,
            str_contains($normalized, 'cheque') || str_contains($normalized, 'cheq') => self::CHEQUE,
            default => self::tryFrom($normalized),
        };
    }
}
