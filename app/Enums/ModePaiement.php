<?php

namespace App\Enums;

/**
 * Modes de paiement canoniques KLASSCI.
 *
 * Source unique de vérité — utiliser via Rule::enum(ModePaiement::class)
 * dans les FormRequests.
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
    case CHEQUE = 'cheque';
    case WAVE = 'wave';
    case ORANGE_MONEY = 'orange_money';
    case MTN_MONEY = 'mtn_money';
    case MOOV_MONEY = 'moov_money';

    public function label(): string
    {
        return match ($this) {
            self::ESPECES => 'Espèces',
            self::MOBILE_MONEY => 'Mobile Money (générique)',
            self::VIREMENT => 'Virement bancaire',
            self::CHEQUE => 'Chèque',
            self::WAVE => 'Wave',
            self::ORANGE_MONEY => 'Orange Money',
            self::MTN_MONEY => 'MTN MoMo',
            self::MOOV_MONEY => 'Moov Money',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ESPECES => 'fa-money-bill-wave',
            self::CHEQUE => 'fa-money-check',
            self::VIREMENT => 'fa-university',
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
            str_contains($normalized, 'mobile') => self::MOBILE_MONEY,
            str_contains($normalized, 'virement') || str_contains($normalized, 'bank') => self::VIREMENT,
            str_contains($normalized, 'cheque') || str_contains($normalized, 'cheq') => self::CHEQUE,
            default => self::tryFrom($normalized),
        };
    }
}
