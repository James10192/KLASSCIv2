<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPRegleAcademique;
use App\Models\User;
use App\Services\ReeinscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le redoublement n'existait qu'en apparence : les bulletins affichaient une
 * mention « Redoublant » alimentee par une colonne absente de la base. Ces
 * tests verrouillent la seule definition fiable du domaine — rester sur le
 * meme niveau d'etude — en executant reellement la reinscription.
 */
class MarquageRedoublementTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $premiereAnnee;

    private ESBTPNiveauEtude $deuxiemeAnnee;

    private ESBTPAnneeUniversitaire $anneeEnCours;

    private ESBTPAnneeUniversitaire $anneeSuivante;

    protected function setUp(): void
    {
        parent::setUp();

        // La factory des evaluations pointe en dur sur l'utilisateur 1.
        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));

        $this->filiere = ESBTPFiliere::factory()->create();
        $this->premiereAnnee = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 1ere ANNEE', 'year' => 1]);
        $this->deuxiemeAnnee = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 2eme ANNEE', 'year' => 2]);

        $this->anneeEnCours = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        $this->anneeSuivante = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    public function test_rester_sur_le_meme_niveau_marque_le_redoublement(): void
    {
        [$etudiant, $classeActuelle] = $this->etudiantInscritEn($this->deuxiemeAnnee);
        $memeNiveau = $this->classeDe($this->deuxiemeAnnee, 'BTS2 B');

        app(ReeinscriptionService::class)->effectuerReinscription(
            etudiantId: $etudiant->id,
            nouvelleClasseId: $memeNiveau->id,
            decision: 'redoublement',
            anneeUniversitaireId: $this->anneeSuivante->id,
            sendNotification: false,
        );

        $nouvelle = $this->derniereInscription($etudiant);

        $this->assertTrue(
            (bool) $nouvelle->is_redoublant,
            'Une reinscription sur le meme niveau doit etre marquee comme un redoublement.'
        );
        $this->assertSame($this->deuxiemeAnnee->id, (int) $nouvelle->niveau_id);
    }

    public function test_passer_au_niveau_superieur_ne_marque_pas_le_redoublement(): void
    {
        [$etudiant] = $this->etudiantInscritEn($this->premiereAnnee);
        $niveauSuperieur = $this->classeDe($this->deuxiemeAnnee, 'BTS2 A');

        app(ReeinscriptionService::class)->effectuerReinscription(
            etudiantId: $etudiant->id,
            nouvelleClasseId: $niveauSuperieur->id,
            decision: 'passage',
            anneeUniversitaireId: $this->anneeSuivante->id,
            sendNotification: false,
        );

        $nouvelle = $this->derniereInscription($etudiant);

        $this->assertFalse(
            (bool) $nouvelle->is_redoublant,
            'Un passage en annee superieure ne doit jamais etre marque comme un redoublement.'
        );
    }

    public function test_changer_de_classe_au_sein_du_meme_niveau_reste_un_redoublement(): void
    {
        // Une reorientation vers une autre classe du meme niveau reste un
        // redoublement : c'est le niveau qui compte, pas la classe.
        [$etudiant] = $this->etudiantInscritEn($this->deuxiemeAnnee);
        $autreFiliere = ESBTPFiliere::factory()->create();
        $autreClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $autreFiliere->id,
            'niveau_etude_id' => $this->deuxiemeAnnee->id,
            'is_active' => true,
        ]);

        app(ReeinscriptionService::class)->effectuerReinscription(
            etudiantId: $etudiant->id,
            nouvelleClasseId: $autreClasse->id,
            decision: 'redoublement',
            anneeUniversitaireId: $this->anneeSuivante->id,
            sendNotification: false,
        );

        $this->assertTrue((bool) $this->derniereInscription($etudiant)->is_redoublant);
    }

    public function test_analyser_une_situation_ne_cree_aucune_regle_en_base(): void
    {
        // Consulter la situation d'un etudiant est une lecture. Le service
        // creait auparavant une regle academique a chaque consultation d'un
        // couple niveau/filiere non configure.
        [$etudiant] = $this->etudiantInscritEn($this->deuxiemeAnnee);

        $this->assertSame(0, ESBTPRegleAcademique::count());

        try {
            app(ReeinscriptionService::class)
                ->analyserSituationEtudiant($etudiant->id, $this->anneeEnCours->name);
        } catch (\Throwable $e) {
            // L'analyse peut echouer faute de notes : seule compte l'absence
            // d'ecriture en base.
        }

        $this->assertSame(
            0,
            ESBTPRegleAcademique::count(),
            "Une lecture ne doit creer aucune regle academique : l'ecole reste seule a les definir."
        );
    }

    /** @return array{0: ESBTPEtudiant, 1: ESBTPClasse} */
    private function etudiantInscritEn(ESBTPNiveauEtude $niveau): array
    {
        $classe = $this->classeDe($niveau, 'Classe '.$niveau->id);
        $etudiant = ESBTPEtudiant::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->anneeEnCours->id,
            'status' => 'active',
        ]);

        return [$etudiant, $classe];
    }

    private function classeDe(ESBTPNiveauEtude $niveau, string $nom): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'name' => $nom,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => true,
        ]);
    }

    private function derniereInscription(ESBTPEtudiant $etudiant): ESBTPInscription
    {
        return ESBTPInscription::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $this->anneeSuivante->id)
            ->latest('id')
            ->firstOrFail();
    }
}
