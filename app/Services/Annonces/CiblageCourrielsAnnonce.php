<?php

namespace App\Services\Annonces;

use App\Models\ESBTPAnnonce;
use App\Models\ESBTPEtudiant;
use Illuminate\Database\Eloquent\Builder;

/**
 * Une source unique pour le ciblage et le comptage.
 * Les conditions annee/classe/statut sont appliquees a la MEME inscription
 * pour eviter d'inclure un etudiant actif dans une autre classe.
 */
final class CiblageCourrielsAnnonce
{
    public const TOUTES = 'all';
    public const ACTIVES_ANNEE = 'active_current_year';

    public function etudiants(ESBTPAnnonce $annonce): Builder
    {
        $query = ESBTPEtudiant::query()->select([
            'esbtp_etudiants.id', 'esbtp_etudiants.user_id',
            'esbtp_etudiants.email', 'esbtp_etudiants.email_personnel',
        ])->with('user:id,email');

        if ($annonce->type === 'etudiant') {
            $query->whereIn('esbtp_etudiants.id', fn ($q) => $q->select('etudiant_id')
                ->from('esbtp_annonce_etudiant')->where('annonce_id', $annonce->id));
        } elseif (! in_array($annonce->type, ['general', 'classe'], true)) {
            return $query->whereRaw('1 = 0');
        }

        $scopeActif = $annonce->email_audience_scope === self::ACTIVES_ANNEE;
        $classe = $annonce->type === 'classe';
        if ($scopeActif && ! $annonce->email_target_year_id) {
            // Fail-closed : jamais d'envoi a toute l'ecole si annee inconnue.
            return $query->whereRaw('1 = 0');
        }

        if ($scopeActif || $classe) {
            // Pour les classes historiques, preserver le filtrage annee en cours.
            // Pour les campagnes nouvelles, utiliser l'annee photographiee.
            $anneeId = $scopeActif ? (int) $annonce->email_target_year_id : null;
            $classes = $classe ? $annonce->classes()->pluck('esbtp_classes.id')->all() : [];
            $query->whereHas('inscriptions', function (Builder $inscriptions) use ($scopeActif, $anneeId, $classe, $classes): void {
                if ($scopeActif) {
                    $inscriptions->where('annee_universitaire_id', $anneeId)
                        ->where('status', 'active');
                } elseif ($classe) {
                    $inscriptions->anneeEnCours();
                }
                if ($classe) {
                    $inscriptions->whereIn('classe_id', $classes);
                }
            });
        }

        return $query;
    }

    public static function adresse(ESBTPEtudiant $etudiant): ?string
    {
        foreach ([$etudiant->email_personnel, $etudiant->email, $etudiant->user?->email] as $candidate) {
            $email = strtolower(trim((string) $candidate));
            if (strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }
        return null;
    }
}
