<?php

namespace Tests\Unit\RendezVous;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\RendezVous\ContactsFamilleRdv;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * L'accueil du jour ne retrouvait pas des familles pourtant convoquees :
 * « DIABATE » ne trouvait pas « Diabaté », « NGUETTIA » pas « N'Guettia », et
 * le nom de l'eleve ne servait a rien quand c'est un parent qui avait reserve.
 */
class TexteRechercheAccueilTest extends TestCase
{
    public function test_accents_apostrophes_et_tirets_sont_retires(): void
    {
        $this->assertSame('nguettia diabate marie daniel grace', ContactsFamilleRdv::normaliser("N’Guettia  Diabaté Marie-Daniel GRÂCE"));
    }

    public function test_le_nom_et_le_matricule_de_l_eleve_sont_cherchables(): void
    {
        $eleve = new ESBTPEtudiant(['nom' => 'KOUAMÉ', 'prenoms' => 'Jean-Marc', 'matricule' => 'MESBTP22-0545']);
        $eleve->setRelation('parents', new Collection());
        $demande = new ESBTPReinscriptionDemande();
        $demande->setRelation('etudiant', $eleve);

        $resa = new ESBTPRdvReservation(['nom' => 'YAO', 'prenoms' => 'Adjoua', 'telephone' => '+2250707000000']);
        $resa->setRelation('candidature', null);
        $resa->setRelation('demande', $demande);

        $texte = app(ContactsFamilleRdv::class)->texteRecherche($resa);

        $this->assertStringContainsString('yao adjoua', $texte);
        $this->assertStringContainsString('kouame jean marc', $texte);
        $this->assertStringContainsString('mesbtp22 0545', $texte);
        $this->assertStringContainsString('+2250707000000', $texte);
    }
}
