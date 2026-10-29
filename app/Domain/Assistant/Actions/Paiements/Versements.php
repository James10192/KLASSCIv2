<?php

namespace App\Domain\Assistant\Actions\Paiements;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPPaiement;

/**
 * Comment Nanan retrouve un versement (par le numéro du reçu, ce que l'école
 * cite, ou par identifiant) et ce qui, s'il bouge, périme une proposition sur
 * ce versement.
 */
final class Versements
{
    /**
     * @return array{0: ?ESBTPPaiement, 1: ?string} le versement, ou la question à poser
     */
    public static function trouver(array $args, bool $supprime): array
    {
        $requete = ($supprime ? ESBTPPaiement::onlyTrashed() : ESBTPPaiement::query())->with('etudiant:id,nom,prenoms,matricule');
        $numero = trim((string) ($args['numero_recu'] ?? ''));

        if (! empty($args['paiement_id'])) {
            $paiement = $requete->whereKey((int) $args['paiement_id'])->first();
        } elseif ($numero !== '') {
            $trouves = $requete->whereRaw('UPPER(numero_recu) = ?', [mb_strtoupper($numero)])->limit(2)->get();
            $paiement = $trouves->count() === 1 ? $trouves->first() : null;
            if ($trouves->count() > 1) {
                return [null, "Plusieurs versements portent le reçu {$numero} : donne l'identifiant de celui visé."];
            }
        } else {
            return [null, 'Quel versement ? Donne le numéro du reçu.'];
        }

        if ($paiement) {
            return [$paiement, null];
        }

        $designe = $numero !== '' ? "reçu {$numero}" : 'versement #'.(int) $args['paiement_id'];
        $ailleurs = ($supprime ? ESBTPPaiement::query() : ESBTPPaiement::onlyTrashed())
            ->when(! empty($args['paiement_id']), fn ($q) => $q->whereKey((int) $args['paiement_id']))
            ->when(empty($args['paiement_id']), fn ($q) => $q->whereRaw('UPPER(numero_recu) = ?', [mb_strtoupper($numero)]))
            ->exists();

        return [null, match (true) {
            $ailleurs && $supprime => "Le {$designe} n'est pas supprimé : rien à restaurer.",
            $ailleurs => "Le {$designe} est supprimé : il se restaure d'abord.",
            default => "Aucun {$designe} : vérifie le numéro.",
        }];
    }

    /** Ce qui, s'il change entre la proposition et la validation, la rend caduque. */
    public static function etat(ESBTPPaiement $paiement): array
    {
        return [
            'id' => (int) $paiement->id,
            'montant' => (float) $paiement->montant,
            'status' => (string) $paiement->status,
            'supprime' => $paiement->trashed(),
            'avoir_disponible' => (float) $paiement->avoir_disponible,
            'rapproche' => (bool) $paiement->reconciliation_locked_at,
            'periode_close_jusqu_au' => (string) SettingsHelper::get('comptabilite.period_locked_until'),
        ];
    }
}
