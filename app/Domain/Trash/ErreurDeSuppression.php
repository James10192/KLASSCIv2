<?php

namespace App\Domain\Trash;

use Illuminate\Database\QueryException;

/**
 * Traduit l'échec d'une suppression définitive en une phrase compréhensible.
 *
 * Renvoyer `$e->getMessage()` à l'écran avait deux défauts : la personne lisait
 * une erreur SQL qui ne lui disait pas quoi faire, et cette erreur exposait le
 * nom de la base, des tables et des contraintes de l'instance.
 */
class ErreurDeSuppression
{
    /**
     * Tables qui peuvent retenir une suppression, et ce qu'elles représentent
     * pour la personne devant l'écran.
     */
    private const RETENUES = [
        'esbtp_factures' => 'des factures',
        'esbtp_paiements' => 'des versements',
        'esbtp_notes' => 'des notes',
        'esbtp_bulletins' => 'des bulletins',
        'esbtp_attendances' => 'des présences',
        'esbtp_frais_subscriptions' => 'des souscriptions de frais',
        'esbtp_reliquats_details' => 'des reliquats',
        'esbtp_inscriptions' => 'des inscriptions',
        'esbtp_etudiants' => 'un dossier étudiant',
    ];

    /**
     * @param  string  $entite  Le sujet de la phrase, ex. « Cette inscription ».
     */
    public static function messageLisible(\Throwable $e, string $entite): string
    {
        $retenuePar = self::retenuePar($e);

        if ($retenuePar !== null) {
            return "{$entite} ne peut pas être supprimée : elle est encore rattachée à {$retenuePar}. "
                .'Supprimez-les d\'abord, ou passez par la suppression en cascade depuis la fiche de l\'étudiant.';
        }

        return "{$entite} n'a pas pu être supprimée : une erreur technique est survenue. "
            .'Le détail a été consigné dans le journal applicatif.';
    }

    /**
     * Nom lisible de la table qui retient la suppression, ou null si l'échec
     * n'est pas une violation de clé étrangère identifiable.
     */
    public static function retenuePar(\Throwable $e): ?string
    {
        if (! $e instanceof QueryException) {
            return null;
        }

        $message = $e->getMessage();

        // MySQL 1451 : « Cannot delete or update a parent row ».
        if (! str_contains($message, '1451') && ! str_contains($message, 'foreign key constraint fails')) {
            return null;
        }

        // MySQL nomme la table QUI RETIENT juste après « constraint fails », avec ou
        // sans préfixe de base : (`la_base`.`la_table`, CONSTRAINT `...`.
        // Chercher n'importe où dans le message attraperait la table dont on part,
        // elle aussi présente dans la requête.
        if (! preg_match('/constraint fails \((?:`[^`]+`\.)?`([^`]+)`/i', $message, $trouve)) {
            return 'd\'autres données';
        }

        return self::RETENUES[$trouve[1]] ?? 'd\'autres données';
    }
}
