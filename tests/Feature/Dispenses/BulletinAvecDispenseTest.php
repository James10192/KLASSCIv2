<?php

namespace Tests\Feature\Dispenses;

use App\Domain\Dispenses\DispenseService;
use App\Models\ESBTPResultat;
use App\Models\ESBTPResultatMatiere;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * Ce qu'une dispense fait au bulletin.
 *
 * La question qui compte n'est pas « la ligne est-elle marquee » mais « la
 * moyenne est-elle juste ». Une matiere dispensee doit sortir du calcul
 * ENTIEREMENT : si son coefficient restait au denominateur, l'etudiant serait
 * puni d'avoir ete dispense.
 *
 * BTS uniquement.
 */
class BulletinAvecDispenseTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    /** @test */
    public function une_matiere_dispensee_sort_du_calcul_au_lieu_de_le_diluer(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        // Deux matieres au meme coefficient 2 : 12 et 8, donc une moyenne de 10.
        $forte = $this->matiereConfiguree();
        $faible = $this->matiereConfiguree();
        $this->noter($etudiant, $this->evaluationDe($forte), 12);
        $this->noter($etudiant, $this->evaluationDe($faible), 8);

        $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $forte->id, (int) $faible->id]
        );

        $service = app(BulletinService::class);

        $avant = $service->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );
        $this->assertEqualsWithDelta(10.0, (float) $avant['moyenneGlobale'], 0.001);

        // On dispense la matiere faible.
        app(DispenseService::class)->accorder(
            (int) $etudiant->id,
            (int) $faible->id,
            (int) $this->annee->id,
            null,
            'Validée lors du parcours antérieur',
            User::factory()->create(),
        );

        $apres = $service->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        // 12, et non 6 : le coefficient de la matiere dispensee a quitte le
        // denominateur en meme temps que sa note a quitte le numerateur.
        $this->assertEqualsWithDelta(12.0, (float) $apres['moyenneGlobale'], 0.001);
    }

    /** @test */
    public function la_matiere_dispensee_reste_au_bulletin_et_dit_pourquoi(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        $notee = $this->matiereConfiguree();
        $dispensee = $this->matiereConfiguree();
        $this->noter($etudiant, $this->evaluationDe($notee), 14);
        $this->noter($etudiant, $this->evaluationDe($dispensee), 9);

        $bulletin = $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $notee->id, (int) $dispensee->id]
        );

        app(DispenseService::class)->accorder(
            (int) $etudiant->id,
            (int) $dispensee->id,
            (int) $this->annee->id,
            'semestre1',
            'Dispensée par décision du conseil',
            User::factory()->create(),
        );

        app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $ligne = ESBTPResultatMatiere::query()
            ->where('bulletin_id', $bulletin->id)
            ->where('matiere_id', $dispensee->id)
            ->first();

        $this->assertNotNull($ligne, 'La matière dispensée doit rester au bulletin.');
        $this->assertSame(ESBTPResultatMatiere::STATUT_DISPENSE, $ligne->statut);
        $this->assertNull($ligne->moyenne);
        $this->assertNull($ligne->rang);
        $this->assertSame('Dispensée par décision du conseil', $ligne->motif_dispense);
        $this->assertFalse($ligne->estNotee());
        $this->assertSame('—', $ligne->moyenneLisible());

        // La matiere notee, elle, n'a pas bouge.
        $autre = ESBTPResultatMatiere::query()
            ->where('bulletin_id', $bulletin->id)
            ->where('matiere_id', $notee->id)
            ->first();
        $this->assertSame(ESBTPResultatMatiere::STATUT_NOTE, $autre->statut);
        $this->assertEqualsWithDelta(14.0, (float) $autre->moyenne, 0.001);
    }

    /**
     * @test
     *
     * Le rang des camarades se calcule sur `esbtp_resultats`. Laisser la ligne
     * d'un etudiant dispense y ferait compter quelqu'un que la matiere ne
     * concerne plus.
     */
    public function la_ligne_agregee_disparait_pour_que_le_rang_des_autres_soit_juste(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        $matiere = $this->matiereConfiguree();
        $this->noter($etudiant, $this->evaluationDe($matiere), 11);

        $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $matiere->id]
        );

        $service = app(BulletinService::class);
        $service->genererDonneesBulletin($etudiant->id, $this->classe->id, $this->annee->id, 'semestre1');

        $this->assertSame(1, ESBTPResultat::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->count());

        app(DispenseService::class)->accorder(
            (int) $etudiant->id,
            (int) $matiere->id,
            (int) $this->annee->id,
            null,
            'Dispensée après vérification du dossier',
            User::factory()->create(),
        );

        $service->genererDonneesBulletin($etudiant->id, $this->classe->id, $this->annee->id, 'semestre1');

        $this->assertSame(0, ESBTPResultat::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->count(), 'La ligne agrégée doit être retirée du dossier.');
    }

    /**
     * @test
     *
     * Non-regression : sans dispense et sans maquette, le bulletin ne change
     * pas. C'est la condition pour que ce lot soit sans effet sur les ecoles
     * qui n'ont rien saisi.
     */
    public function sans_dispense_ni_maquette_le_bulletin_est_inchange(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        $a = $this->matiereConfiguree();
        $b = $this->matiereConfiguree();
        $this->noter($etudiant, $this->evaluationDe($a), 12);
        $this->noter($etudiant, $this->evaluationDe($b), 8);

        $bulletin = $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $a->id, (int) $b->id]
        );

        $data = app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $this->assertEqualsWithDelta(10.0, (float) $data['moyenneGlobale'], 0.001);

        $lignes = ESBTPResultatMatiere::query()->where('bulletin_id', $bulletin->id)->get();
        $this->assertCount(2, $lignes);
        $this->assertTrue($lignes->every(fn ($l) => $l->statut === ESBTPResultatMatiere::STATUT_NOTE));
        $this->assertTrue($lignes->every(fn ($l) => $l->moyenne !== null));
    }
}
