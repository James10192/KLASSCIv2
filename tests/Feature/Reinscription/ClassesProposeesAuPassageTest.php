<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\Reinscription\ClassesDeReinscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La classe proposee a un etudiant admis se cherche sur l'annee suivante.
 *
 * En LMD l'annee est comptee en continu (Licence 1-3, Master 4-5, Doctorat
 * 6-8), et la classe suivante se cherche par parcours, puis par mention, puis
 * par filiere : `filiere_id` n'est en LMD que le reflet de la mention ou du
 * parcours de la classe, et deux classes d'une meme mention peuvent porter deux
 * reflets differents.
 */
class ClassesProposeesAuPassageTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPFiliere $filiere;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));
        $this->filiere = ESBTPFiliere::factory()->create();
    }

    public function test_une_licence_3_passe_en_master_1(): void
    {
        $licence3 = $this->classe('Licence', 3);
        $master1 = $this->classe('Master', 4);
        $this->classe('BTS', 1);

        $this->assertSame([$master1->id], $this->proposees($this->etudiantEn($licence3)));
    }

    public function test_un_master_1_passe_en_master_2(): void
    {
        $master1 = $this->classe('Master', 4);
        $master2 = $this->classe('Master', 5);

        $this->assertSame([$master2->id], $this->proposees($this->etudiantEn($master1)));
    }

    public function test_un_master_2_ne_se_voit_pas_proposer_une_premiere_annee(): void
    {
        $master2 = $this->classe('Master', 5);
        $this->classe('Licence', 1);
        $this->classe('BTS', 1);

        $this->assertSame([], $this->proposees($this->etudiantEn($master2)));
    }

    public function test_le_master_1_d_un_autre_reflet_de_la_meme_mention_est_propose(): void
    {
        // Cas UCAO : la Licence 3 et le Master 1 appartiennent a la meme
        // mention mais a deux parcours, donc a deux filieres reflets. Cherchee
        // par filiere_id, la proposition revenait vide.
        $mention = $this->mention();
        $parcoursLicence = $this->parcours($mention, 'LFIN');
        $parcoursMaster = $this->parcours($mention, 'MFIN');

        $licence3 = $this->classe('Licence', 3, $parcoursLicence, ESBTPFiliere::factory()->create());
        $master1 = $this->classe('Master', 4, $parcoursMaster, ESBTPFiliere::factory()->create());
        $this->classe('Master', 4, $this->parcours($this->mention('AUTRE'), 'MDROIT'), ESBTPFiliere::factory()->create());

        $this->assertSame([$master1->id], $this->proposees($this->etudiantEn($licence3)));
    }

    public function test_le_meme_parcours_l_emporte_sur_le_reste_de_la_mention(): void
    {
        $mention = $this->mention();
        $finance = $this->parcours($mention, 'FIN');

        $licence3 = $this->classe('Licence', 3, $finance, ESBTPFiliere::factory()->create());
        $masterFinance = $this->classe('Master', 4, $finance, ESBTPFiliere::factory()->create());
        $this->classe('Master', 4, $this->parcours($mention, 'AUDIT'), ESBTPFiliere::factory()->create());

        $this->assertSame([$masterFinance->id], $this->proposees($this->etudiantEn($licence3)));
    }

    public function test_la_proposition_part_de_la_derniere_annee_suivie_pas_d_un_ancien_cursus(): void
    {
        // Cas reel d'ESBTP Abidjan : un BTS 2 reste actif sur une annee
        // ancienne, puis l'etudiant fait sa Licence 3. Lue au hasard, l'ancienne
        // inscription lui faisait proposer une 2e annee de BTS.
        $bts2 = $this->classe('BTS', 2);
        $licence3 = $this->classe('Licence', 3);
        $master1 = $this->classe('Master', 4);
        $this->classe('BTS', 1);

        $etudiant = $this->etudiantEn($licence3, '2025-09-01');
        $this->etudiantEn($bts2, '2022-09-01', $etudiant);

        $this->assertSame([$master1->id], $this->proposees($etudiant));
        $this->assertSame($licence3->id, app(ClassesDeReinscription::class)->inscriptionQuittee($etudiant->id)?->classe_id);
    }

    public function test_un_dossier_non_finalise_n_est_pas_l_inscription_quittee(): void
    {
        // Memes conditions que la liste de reinscription : un dossier encore en
        // cours n'est pas une annee suivie.
        $licence3 = $this->classe('Licence', 3);
        $etudiant = $this->etudiantEn($licence3, '2025-09-01', null, 'paiement_en_attente');

        $this->assertNull(app(ClassesDeReinscription::class)->inscriptionQuittee($etudiant->id));
    }

    public function test_le_bts_garde_son_repli_vers_une_premiere_annee_d_un_autre_type(): void
    {
        // Comportement anterieur, que ce correctif ne doit pas toucher.
        $bts2 = $this->classe('BTS', 2);
        $licence1 = $this->classe('Licence', 1);

        $this->assertSame([$licence1->id], $this->proposees($this->etudiantEn($bts2)));
    }

    public function test_une_licence_1_deja_specialisee_ne_voit_que_son_parcours(): void
    {
        // Cas ESBTP Abidjan : L1 Batiment et L1 Travaux Publics sont distinctes
        // des l'entree. Etre en L1 n'ouvre aucun choix d'orientation.
        $mention = $this->mention('GC');
        $batiment = $this->parcours($mention, 'BU');
        $travauxPublics = $this->parcours($mention, 'TIR');

        $licence1 = $this->classe('Licence', 1, $batiment, ESBTPFiliere::factory()->create());
        $licence2Batiment = $this->classe('Licence', 2, $batiment, ESBTPFiliere::factory()->create());
        $this->classe('Licence', 2, $travauxPublics, ESBTPFiliere::factory()->create());

        $this->assertSame([$licence2Batiment->id], $this->proposees($this->etudiantEn($licence1)));
    }

    public function test_un_tronc_commun_propose_toutes_les_specialites_de_la_mention(): void
    {
        // Le tronc commun est un parcours qui ne continue pas l'annee suivante :
        // c'est ce fait, et aucun nom de specialite, qui ouvre le choix.
        $mention = $this->mention('PVA');
        $troncCommun = $this->parcours($mention, 'TC');

        $licence1 = $this->classe('Licence', 1, $troncCommun, ESBTPFiliere::factory()->create());
        $animales = $this->classe('Licence', 2, $this->parcours($mention, 'LPA'), ESBTPFiliere::factory()->create());
        $vegetales = $this->classe('Licence', 2, $this->parcours($mention, 'LPV'), ESBTPFiliere::factory()->create());

        $this->assertSame(
            collect([$animales->id, $vegetales->id])->sort()->values()->all(),
            $this->proposees($this->etudiantEn($licence1))
        );
    }

    public function test_une_specialite_rangee_dans_une_autre_mention_n_est_pas_devinee(): void
    {
        // Etat d'USAT en septembre 2026 : Productions animales et vegetales sont
        // deux mentions a cote de celle du tronc commun. Rien ne les relie au
        // tronc commun ; le code ne devine pas un lien par le domaine ou le nom.
        $troncCommun = $this->parcours($this->mention('PVA'), 'TC');
        $licence1 = $this->classe('Licence', 1, $troncCommun, ESBTPFiliere::factory()->create());
        $this->classe('Licence', 2, $this->parcours($this->mention('PA'), 'LPA'), ESBTPFiliere::factory()->create());
        $this->classe('Licence', 2, $this->parcours($this->mention('PV'), 'LPV'), ESBTPFiliere::factory()->create());

        $this->assertSame([], $this->proposees($this->etudiantEn($licence1)));
    }

    public function test_une_filiere_tronc_commun_bts_ne_s_ajoute_pas_a_une_classe_lmd(): void
    {
        $troncCommun = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $fille = ESBTPFiliere::factory()->create(['parent_id' => $troncCommun->id]);

        $licence1 = $this->classe('Licence', 1, null, $troncCommun);
        $licence2 = $this->classe('Licence', 2, null, $troncCommun);
        $this->classe('BTS', 2, null, $fille);
        $this->classe('Licence', 1, null, $fille);

        $this->assertSame([$licence2->id], $this->proposees($this->etudiantEn($licence1)));
    }

    /** @return list<int> */
    private function proposees(ESBTPEtudiant $etudiant): array
    {
        $classes = app(ClassesDeReinscription::class);
        $quittee = $classes->inscriptionQuittee($etudiant->id)?->classe;

        return $quittee ? $classes->pour($quittee, 'passage')->pluck('id')->sort()->values()->all() : [];
    }

    private function classe(string $type, int $annee, ?ESBTPLMDParcours $parcours = null, ?ESBTPFiliere $filiere = null): ESBTPClasse
    {
        $niveau = ESBTPNiveauEtude::factory()->create([
            'name' => "{$type} {$annee}",
            'type' => $type,
            'year' => $annee,
        ]);

        return ESBTPClasse::factory()->create([
            'filiere_id' => ($filiere ?? $this->filiere)->id,
            'niveau_etude_id' => $niveau->id,
            'parcours_id' => $parcours?->id,
            'is_active' => true,
        ]);
    }

    private function mention(string $code = 'GEST'): ESBTPLMDMention
    {
        $domaine = ESBTPLMDDomaine::create(['name' => "Domaine {$code}", 'code' => "D{$code}", 'is_active' => true]);

        return ESBTPLMDMention::create(['name' => "Mention {$code}", 'code' => $code, 'domaine_id' => $domaine->id, 'is_active' => true]);
    }

    private function parcours(ESBTPLMDMention $mention, string $code): ESBTPLMDParcours
    {
        return ESBTPLMDParcours::create(['name' => "Parcours {$code}", 'code' => $code, 'mention_id' => $mention->id, 'is_active' => true]);
    }

    private function etudiantEn(ESBTPClasse $classe, string $debutAnnee = '2025-09-01', ?ESBTPEtudiant $etudiant = null, string $etape = 'etudiant_cree'): ESBTPEtudiant
    {
        $etudiant ??= ESBTPEtudiant::factory()->create();
        $debut = \Carbon\Carbon::parse($debutAnnee);
        $annee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => $debut->year.'-'.($debut->year + 1),
            'start_date' => $debut->toDateString(),
            'end_date' => $debut->copy()->addMonths(10)->toDateString(),
        ]);

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => $etape,
        ]);

        return $etudiant;
    }
}
