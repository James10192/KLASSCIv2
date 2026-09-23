<?php

namespace Tests\Unit\RendezVous;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use PHPUnit\Framework\TestCase;

/**
 * AccueilRdv::DOSSIER_CLOS s'ecrit avec les constantes de la candidature et
 * s'applique aussi a la demande de reinscription. Si l'une des deux renomme un
 * statut, une demande close redeviendrait « non venue » sans rien dire.
 */
class DossierClosRdvTest extends TestCase
{
    public function test_les_statuts_clos_sont_les_memes_pour_la_candidature_et_la_demande(): void
    {
        $this->assertSame(ESBTPCandidature::STATUT_CONVERTIE, ESBTPReinscriptionDemande::STATUT_CONVERTIE);
        $this->assertSame(ESBTPCandidature::STATUT_REJETEE, ESBTPReinscriptionDemande::STATUT_REJETEE);
    }
}
