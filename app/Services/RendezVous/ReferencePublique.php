<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class ReferencePublique
{
    public const LONGUEUR = 12;

    public function assurerCandidature(ESBTPCandidature $candidature): string
    {
        if (is_string($candidature->reference_publique) && $candidature->reference_publique !== '') {
            return $candidature->reference_publique;
        }

        return $this->attribuer($candidature);
    }

    public function assurerDemande(ESBTPReinscriptionDemande $demande): string
    {
        if (is_string($demande->reference_publique) && $demande->reference_publique !== '') {
            return $demande->reference_publique;
        }

        return $this->attribuer($demande);
    }

    public function formater(string $reference): string
    {
        $brut = strtoupper(preg_replace('/[^A-Z0-9]/', '', $reference) ?? '');

        return implode('-', str_split($brut, 4));
    }

    public function normaliser(string $reference): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $reference) ?? '');
    }

    private function attribuer(ESBTPCandidature|ESBTPReinscriptionDemande $porteur): string
    {
        for ($essai = 0; $essai < 8; $essai++) {
            $reference = $this->tirer();

            try {
                $porteur->forceFill(['reference_publique' => $reference])->save();

                return $reference;
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Impossible d\'émettre une référence de rendez-vous unique.');
    }

    private function tirer(): string
    {
        return strtoupper(Str::random(self::LONGUEUR));
    }
}
