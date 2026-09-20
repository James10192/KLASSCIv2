<?php

namespace Tests\Feature\Bulletin;

use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * L'ordre des matieres sur le bulletin.
 *
 * Le PDF officiel suivait l'ordre d'arrivee des notes : deux etudiants d'une
 * meme classe pouvaient recevoir des bulletins ordonnes differemment. La
 * garantie que ces tests protegent est double — les rangs poses sont respectes,
 * et TANT QU'AUCUN rang n'est pose rien ne bouge.
 */
class BulletinSubjectOrderTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase, SeedsConfiguredBulletin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    private function ordre(): BulletinSubjectOrder
    {
        return app(BulletinSubjectOrder::class);
    }

    /** Rattache une matiere a un combo, avec ou sans rang propre. */
    private function lierAuCombo(ESBTPMatiere $matiere, ?int $rang = null, ?ESBTPFiliere $filiere = null): void
    {
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => ($filiere ?? $this->filiere)->id,
            'niveau_etude_id' => $this->niveau->id,
            'ordre_bulletin' => $rang,
        ]);
    }

    public function test_les_rangs_poses_ordonnent_le_pdf_officiel(): void
    {
        $physique = $this->matiereConfiguree();
        $maths = $this->matiereConfiguree();
        $anglais = $this->matiereConfiguree();

        // Rangs volontairement inverses de l'ordre de creation.
        $this->lierAuCombo($maths, 1);
        $this->lierAuCombo($physique, 2);
        $this->lierAuCombo($anglais, 3);

        $etudiant = $this->etudiantInscrit();

        // Les notes arrivent dans un ordre qui n'est pas celui du bulletin :
        // c'est precisement ce que le tri doit corriger.
        foreach ([$anglais, $physique, $maths] as $matiere) {
            $this->noter($etudiant, $this->evaluationDe($matiere));
        }

        $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [$physique->id, $maths->id, $anglais->id]
        );

        $donnees = app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'semestre1'
        );

        self::assertSame(
            [$maths->id, $physique->id, $anglais->id],
            $this->matiereIds($donnees['resultatsGeneraux'])
        );
    }

    public function test_sans_aucun_rang_la_collection_revient_telle_quelle(): void
    {
        $a = $this->matiereConfiguree();
        $b = $this->matiereConfiguree();
        $c = $this->matiereConfiguree();

        // Rattachees au combo, mais sans aucun rang : personne n'a rien decide.
        foreach ([$a, $b, $c] as $matiere) {
            $this->lierAuCombo($matiere);
        }

        $lignes = collect([
            $c->id => (object) ['matiere_id' => $c->id, 'name' => 'C'],
            $a->id => (object) ['matiere_id' => $a->id, 'name' => 'A'],
            $b->id => (object) ['matiere_id' => $b->id, 'name' => 'B'],
        ]);

        $rangs = $this->ordre()->rankMapForClasse($this->classe->fresh());
        $triees = $this->ordre()->sort($lignes, $rangs);

        // Ni l'ordre ni les cles ne bougent : aucun bulletin distribue ne change.
        self::assertSame([$c->id, $a->id, $b->id], $triees->keys()->all());
        self::assertSame([null, null, null], array_values($rangs));
    }

    public function test_poser_un_seul_rang_range_les_autres_par_nom(): void
    {
        $zebre = $this->matiereConfiguree();
        $abeille = $this->matiereConfiguree();
        $loutre = $this->matiereConfiguree();
        $zebre->update(['name' => 'Zebre']);
        $abeille->update(['name' => 'Abeille']);
        $loutre->update(['name' => 'Loutre']);

        // Une seule place posee, sur la derniere de l'alphabet.
        $this->lierAuCombo($zebre, 1);
        $this->lierAuCombo($abeille);
        $this->lierAuCombo($loutre);

        $lignes = collect([
            $loutre->id => (object) ['matiere_id' => $loutre->id, 'name' => 'Loutre'],
            $zebre->id => (object) ['matiere_id' => $zebre->id, 'name' => 'Zebre'],
            $abeille->id => (object) ['matiere_id' => $abeille->id, 'name' => 'Abeille'],
        ]);

        $triees = $this->ordre()->sort($lignes, $this->ordre()->rankMapForClasse($this->classe->fresh()));

        // Comportement assume : des qu'une place est posee, les matieres sans
        // place passent de « ordre d'arrivee des notes » — qui n'est pas
        // reproductible d'un etudiant a l'autre — a l'ordre alphabetique.
        // C'est le prix d'un ordre stable, et il faut le savoir avant de poser
        // le premier rang.
        self::assertSame([$zebre->id, $abeille->id, $loutre->id], $triees->keys()->all());
    }

    public function test_le_rang_propre_au_combo_l_emporte_sur_l_ordre_general(): void
    {
        $premiere = $this->matiereConfiguree();
        $seconde = $this->matiereConfiguree();

        // L'ordre general dirait « seconde d'abord ».
        $premiere->update(['ordre_bulletin' => 90]);
        $seconde->update(['ordre_bulletin' => 10]);

        // Le combo dit l'inverse, et c'est lui qui doit gagner.
        $this->lierAuCombo($premiere, 1);
        $this->lierAuCombo($seconde, 2);

        $rangs = $this->ordre()->rankMapForClasse($this->classe->fresh());

        self::assertSame(1, $rangs[$premiere->id]);
        self::assertSame(2, $rangs[$seconde->id]);
    }

    public function test_l_ordre_general_sert_de_repli_et_zero_ne_vaut_pas_premier(): void
    {
        $avecOrdreGeneral = $this->matiereConfiguree();
        $sansRien = $this->matiereConfiguree();

        $avecOrdreGeneral->update(['ordre_bulletin' => 7]);
        // Toutes les matieres BTS existantes portent 0 : c'est « non defini »,
        // surtout pas « premiere du bulletin ».
        $sansRien->update(['ordre_bulletin' => 0]);

        $this->lierAuCombo($avecOrdreGeneral);
        $this->lierAuCombo($sansRien);

        $rangs = $this->ordre()->rankMapForClasse($this->classe->fresh());

        self::assertSame(7, $rangs[$avecOrdreGeneral->id]);
        self::assertNull($rangs[$sansRien->id]);
    }

    public function test_une_classe_de_specialite_retient_le_plus_petit_rang_de_ses_combos(): void
    {
        $specialite = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $this->filiere->id,
        ]);
        $classeSpecialite = ESBTPClasse::factory()->create([
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => 2,
        ]);

        // La meme matiere porte deux rangs : 4 sur la specialite, 2 sur le
        // tronc commun. La place que le tronc commun lui donnait est conservee.
        $this->lierAuCombo($matiere, 4, $specialite);
        $this->lierAuCombo($matiere, 2, $this->filiere);

        $rangs = $this->ordre()->rankMapForClasse($classeSpecialite->fresh());

        self::assertSame(2, $rangs[$matiere->id]);
    }

    /** @return list<int> */
    private function matiereIds(Collection $lignes): array
    {
        return $lignes->map(fn ($ligne) => (int) $ligne->matiere_id)->values()->all();
    }
}
