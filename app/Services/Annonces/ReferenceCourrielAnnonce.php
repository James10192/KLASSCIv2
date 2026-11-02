<?php

namespace App\Services\Annonces;

/**
 * Les anciens identifiants restent lisibles, jamais réécrits.
 * Pour les nouveaux envois la clé est qualifiée par établissement et conservée
 * dans mailpulse_request_id AVANT tout appel réseau ; aucune migration requise.
 * Attention : mailpulse_request_id est un repère d'idempotence lorsque v2.
 */
final class ReferenceCourrielAnnonce
{
    public static function ancienne(int $annonceId, int $livraisonId): string
    {
        return 'klassci-annonce-'.$annonceId.'-courriel-'.$livraisonId;
    }

    public static function nouvelle(string $tenant, int $annonceId, int $livraisonId): ?string
    {
        if (! self::tenantValide($tenant) || $annonceId < 1 || $livraisonId < 1) {
            return null;
        }

        return 'klassci-v2-'.$tenant.'-annonce-'.$annonceId.'-courriel-'.$livraisonId;
    }

    /**
     * Reference d'une NOUVELLE tentative explicitement autorisee.
     * La presence de cette valeur n'autorise jamais a envoyer : le journal
     * immutable, la permission effective et la preuve fournisseur decident.
     * Une tentative #1 reste en v2, pour proteger les historiques.
     */
    public static function tentative(string $tenant, int $annonceId, int $livraisonId, int $numero): ?string
    {
        // MailPulse's JS parser rejects integers beyond Number.MAX_SAFE_INTEGER.
        if (! self::tenantValide($tenant) || $annonceId < 1 || $livraisonId < 1 || $numero < 2
            || $annonceId > 9007199254740991 || $livraisonId > 9007199254740991
            || $numero > 9007199254740991) {
            return null;
        }

        $reference = 'klassci-v3-'.$tenant.'-annonce-'.$annonceId
            .'-courriel-'.$livraisonId.'-essai-'.$numero;

        return strlen($reference) <= 191 ? $reference : null;
    }

    /** @return array{annonce_id:int,livraison_id:int,tentative:int}|null */
    public static function extraireTentative(string $reference, string $tenant): ?array
    {
        if (! self::tenantValide($tenant) || strlen($reference) > 191
            || ! preg_match('/^klassci-v3-([a-z0-9][a-z0-9_-]{1,62})-annonce-([1-9][0-9]*)-courriel-([1-9][0-9]*)-essai-([2-9]|[1-9][0-9]+)$/D', $reference, $match)
            || ! hash_equals($tenant, $match[1])
            || ! self::identifiantsValides($match[2], $match[3])
            || ! self::identifiantsValides($match[4], $match[4])
            || (int) $match[2] > 9007199254740991
            || (int) $match[3] > 9007199254740991
            || (int) $match[4] > 9007199254740991) {
            return null;
        }

        return [
            'annonce_id' => (int) $match[2],
            'livraison_id' => (int) $match[3],
            'tentative' => (int) $match[4],
        ];
    }

    /**
     * Les anciennes tentatives retrying gardent leur clé ancienne : les
     * renommer provoquerait un deuxième envoi chez MailPulse.
     */
    public static function pourEnvoi(object $ligne, string $tenant): ?string
    {
        $nouvelle = self::nouvelle($tenant, (int) $ligne->annonce_id, (int) $ligne->id);
        if ($nouvelle === null) {
            return null;
        }
        $stockee = (string) ($ligne->mailpulse_request_id ?? '');
        // A v3 attempt may only be dispatched by EnvoyerTentativeAnnonceCourriel,
        // never through the historical delivery job.
        if (str_starts_with($stockee, 'klassci-v3-')) {
            return null;
        }
        if (str_starts_with($stockee, 'klassci-v2-')) {
            return hash_equals($nouvelle, $stockee) ? $stockee : null;
        }
        if ((int) ($ligne->attempts ?? 0) > 0) {
            return self::ancienne((int) $ligne->annonce_id, (int) $ligne->id);
        }

        return $nouvelle;
    }

    /** Quarantaine/recherche GET : ne devine jamais une nouvelle clé. */
    public static function pourRapprochement(object $ligne, string $tenant): ?string
    {
        $stockee = (string) ($ligne->mailpulse_request_id ?? '');
        if (str_starts_with($stockee, 'klassci-v3-')) {
            $essai = self::extraireTentative($stockee, $tenant);

            return $essai !== null
                && $essai['annonce_id'] === (int) $ligne->annonce_id
                && $essai['livraison_id'] === (int) $ligne->id ? $stockee : null;
        }
        if (str_starts_with($stockee, 'klassci-v2-')) {
            $attendue = self::nouvelle($tenant, (int) $ligne->annonce_id, (int) $ligne->id);

            return $attendue !== null && hash_equals($attendue, $stockee) ? $stockee : null;
        }

        return self::ancienne((int) $ligne->annonce_id, (int) $ligne->id);
    }

    /** @return array{annonce_id:int,livraison_id:int,v2:bool}|null */
    public static function extraire(string $reference, string $tenant): ?array
    {
        if (! self::tenantValide($tenant) || strlen($reference) > 191) {
            return null;
        }
        if (preg_match('/^klassci-v2-([a-z0-9][a-z0-9_-]{1,62})-annonce-([1-9][0-9]*)-courriel-([1-9][0-9]*)$/D', $reference, $m)) {
            if (! hash_equals($tenant, $m[1]) || ! self::identifiantsValides($m[2], $m[3])) {
                return null;
            }

            return ['annonce_id' => (int) $m[2], 'livraison_id' => (int) $m[3], 'v2' => true];
        }
        if (preg_match('/^klassci-annonce-([1-9][0-9]*)-courriel-([1-9][0-9]*)$/D', $reference, $m)
            && self::identifiantsValides($m[1], $m[2])) {
            return ['annonce_id' => (int) $m[1], 'livraison_id' => (int) $m[2], 'v2' => false];
        }

        return null;
    }
    private static function tenantValide(string $tenant): bool
    {
        return $tenant !== 'default'
            && (bool) preg_match('/^[a-z0-9][a-z0-9_-]{1,62}$/D', $tenant);
    }

    private static function identifiantsValides(string $annonce, string $livraison): bool
    {
        // Prevent integer overflow turning a forged reference into another
        // real row ID (PHP silently clamps huge numeric strings).
        return (string) (int) $annonce === $annonce
            && (string) (int) $livraison === $livraison;
    }

}
