<?php

namespace App\Services\Verification;

use App\Enums\StatutVerificationContact;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Une demande ne reste pas masquee pour toujours.
 *
 * Passe le delai (48 h par defaut) depuis son MASQUAGE sans que la famille
 * confirme, elle redevient visible avec l'etat « verification expiree » :
 * l'ecole la voit, avec un badge « Contact non confirme », et decide. Une
 * confirmation tardive reste acceptee.
 *
 * Mise a jour CONDITIONNELLE (toujours masquee au moment d'ecrire) : une
 * verification aboutie pendant le passage n'est jamais ecrasee. Une demande
 * masquee sans ligne de verification (ecriture perdue) expire aussi.
 */
class ExpirationVerifications
{
    /** @return int le nombre de demandes rendues visibles */
    public function expirer(): int
    {
        $limite = now()->subHours((int) config('verification_contact.expiration_masquage_heures', 48));
        $n = 0;

        foreach ([ESBTPCandidature::class, ESBTPReinscriptionDemande::class] as $modele) {
            $exemple = new $modele;
            $masquees = StatutVerificationContact::valeursMasquees();

            $modele::sansFiltreVerification()
                ->whereIn('verification_contact', $masquees)
                ->where(fn ($q) => $q
                    ->whereExists(fn ($e) => $this->ligne($e, $exemple)->whereNull('v.verifie_at')
                        ->where(fn ($d) => $d->where('v.masquee_at', '<=', $limite)
                            ->orWhere(fn ($s) => $s->whereNull('v.masquee_at')->where('v.updated_at', '<=', $limite))))
                    ->orWhere(fn ($s) => $s->whereNotExists(fn ($e) => $this->ligne($e, $exemple))
                        ->where($exemple->qualifyColumn('updated_at'), '<=', $limite)))
                ->select('id')
                ->chunkById(200, function ($lot) use ($modele, $masquees, &$n) {
                    $n += $modele::sansFiltreVerification()
                        ->whereIn('id', $lot->pluck('id'))
                        ->whereIn('verification_contact', $masquees)
                        ->update(['verification_contact' => StatutVerificationContact::Expiree->value]);
                });

            Cache::forget($exemple->cleCacheCompteur());
        }

        if ($n > 0) {
            Log::info('Verifications de contact expirees : demandes rendues visibles', ['nombre' => $n]);
        }

        return $n;
    }

    private function ligne($requete, $exemple)
    {
        return $requete->selectRaw('1')->from('esbtp_verifications_contact as v')
            ->whereColumn('v.verifiable_id', $exemple->qualifyColumn('id'))
            ->where('v.verifiable_type', $exemple->getMorphClass());
    }
}
