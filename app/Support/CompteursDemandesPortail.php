<?php

namespace App\Support;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Les compteurs « Demandes en ligne » et « Candidatures en ligne » de la barre
 * laterale, rendus par CHAQUE page du back-office.
 *
 * Le deploiement fait `pull` puis `migrate` : pendant quelques secondes le
 * code precede la table ET ses colonnes. Ces deux modeles portent une portee
 * globale sur `verification_contact` ; une requete lancee avant la migration
 * ferait tomber tout le back-office. Les gardes sont donc ici, dans le cache
 * court, et nulle part ailleurs : la portee globale, elle, reste sans sonde.
 */
class CompteursDemandesPortail
{
    public function reinscriptions(): int
    {
        return Cache::remember(ESBTPReinscriptionDemande::CLE_CACHE_EN_ATTENTE, 60, fn (): int => $this->pret('esbtp_reinscription_demandes')
            ? ESBTPReinscriptionDemande::enAttente()->count()
            : 0);
    }

    public function candidatures(): int
    {
        return Cache::remember(ESBTPCandidature::CLE_CACHE_EN_ATTENTE, 60, fn (): int => $this->pret('esbtp_candidatures')
            ? ESBTPCandidature::enAttente()->count()
            : 0);
    }

    private function pret(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'verification_contact');
    }
}
