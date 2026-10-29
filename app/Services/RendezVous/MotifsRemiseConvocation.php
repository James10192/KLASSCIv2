<?php

namespace App\Services\RendezVous;

/**
 * Le motif d'une convocation non remise, tel que l'ecole le lit, et sa
 * famille (rebond, suppression, autre) pour les compteurs du diagnostic.
 */
final class MotifsRemiseConvocation
{
    public static function lisible(string $code): string
    {
        return match (self::famille($code)) {
            'rebond' => 'L\'adresse a rebondi : elle n\'existe pas ou n\'accepte pas le courrier ('.$code.')',
            'suppression' => 'Le fournisseur a écarté l\'adresse (supprimée, plainte ou envoi annulé) ('.$code.')',
            default => 'Courriel non remis ('.$code.')',
        };
    }

    /** @return 'rebond'|'suppression'|'autre' */
    public static function famille(?string $code): string
    {
        $code = strtolower((string) $code);

        return match (true) {
            str_contains($code, 'bounce') => 'rebond',
            str_contains($code, 'suppress'), str_contains($code, 'complain'), str_contains($code, 'cancel') => 'suppression',
            default => 'autre',
        };
    }
}
