<?php

namespace Tests\Feature\Classes;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Services\LMD\FiliereMiroirLmd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Une entite LMD a toujours une filiere sur laquelle ancrer une classe.
 *
 * Ce test existe parce que l'ancrage tenait par un hasard d'identifiants :
 * on ecrivait l'id de la mention dans `esbtp_classes.filiere_id`, ce qui ne
 * marchait que tant que les deux suites d'identifiants coincidaient. USAT a
 * huit mentions pour cinq filieres : ses trois mentions d'agronomie n'avaient
 * aucune classe possible, et l'ecole ouvre en 2026-2027.
 */
class AncrageFiliereLmdTest extends TestCase
{
    use RefreshDatabase;

    private FiliereMiroirLmd $miroirs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->miroirs = app(FiliereMiroirLmd::class);
    }

    public function test_un_parcours_sans_filiere_recoit_un_reflet(): void
    {
        $parcours = $this->parcours('Productions Animales', 'PA', filiereId: null);

        $ancrage = $this->miroirs->pourParcours($parcours);

        $this->assertTrue($ancrage->exists);
        $this->assertSame('Productions Animales', $ancrage->name);
        $this->assertSame('PA', $ancrage->code);
        $this->assertSame($parcours->id, $ancrage->lmd_parcours_id);
        $this->assertTrue($ancrage->estMiroirLmd());

        // Le parcours pointe desormais vers son reflet : les lectures BTS
        // existantes, qui passent par parcours->filiere_id, le trouvent.
        $this->assertSame($ancrage->id, (int) $parcours->fresh()->filiere_id);
    }

    public function test_un_parcours_qui_a_deja_une_filiere_garde_la_sienne(): void
    {
        // Sur les instances mixtes, un parcours LMD pointe legitimement vers
        // une VRAIE filiere BTS equivalente. On ne doit pas la doubler.
        $reelle = ESBTPFiliere::create(['name' => 'Genie Civil', 'code' => 'GC', 'is_active' => true]);
        $parcours = $this->parcours('Batiment', 'BAT', filiereId: $reelle->id);

        $ancrage = $this->miroirs->pourParcours($parcours);

        $this->assertSame($reelle->id, $ancrage->id);
        $this->assertFalse($ancrage->estMiroirLmd());
        $this->assertSame(0, ESBTPFiliere::whereNotNull('lmd_parcours_id')->count());
    }

    public function test_appele_deux_fois_le_reflet_n_est_pas_duplique(): void
    {
        $parcours = $this->parcours('Economie', 'ECO', filiereId: null);

        $premier = $this->miroirs->pourParcours($parcours);
        $second = $this->miroirs->pourParcours($parcours->fresh());

        $this->assertSame($premier->id, $second->id);
        $this->assertSame(1, ESBTPFiliere::whereNotNull('lmd_parcours_id')->count());
    }

    public function test_une_mention_recoit_son_propre_reflet_pour_le_tronc_commun(): void
    {
        $mention = $this->mention('Sciences de la Terre', 'ST');

        $ancrage = $this->miroirs->pourMention($mention);

        $this->assertSame($mention->id, $ancrage->lmd_mention_id);
        $this->assertSame('Sciences de la Terre', $ancrage->name);
        $this->assertSame($ancrage->id, $this->miroirs->pourMention($mention->fresh())->id);
    }

    public function test_le_code_d_un_reflet_ne_heurte_pas_une_filiere_existante(): void
    {
        ESBTPFiliere::create(['name' => 'Droit BTS', 'code' => 'DROIT', 'is_active' => true]);
        $parcours = $this->parcours('Droit', 'DROIT', filiereId: null);

        $ancrage = $this->miroirs->pourParcours($parcours);

        $this->assertNotSame('DROIT', $ancrage->code);
        $this->assertStringStartsWith('DROIT', $ancrage->code);
    }

    public function test_les_reflets_sont_ecartes_des_ecrans_qui_choisissent_une_filiere_bts(): void
    {
        ESBTPFiliere::create(['name' => 'Genie Civil', 'code' => 'GC', 'is_active' => true]);
        $this->miroirs->pourParcours($this->parcours('Economie', 'ECO', filiereId: null));
        $this->miroirs->pourMention($this->mention('Droit', 'DR'));

        $this->assertSame(3, ESBTPFiliere::count());
        $this->assertSame(['Genie Civil'], ESBTPFiliere::horsMiroirLmd()->pluck('name')->all());
    }

    public function test_une_classe_peut_naitre_pour_un_parcours_sans_filiere(): void
    {
        // C'est LA panne d'USAT, reproduite : trois parcours d'agronomie n'ont
        // aucune filiere. La classe se creait alors avec filiere_id = NULL sur
        // une colonne NOT NULL, et l'ecran repondait 500.
        $parcours = $this->parcours('Productions Vegetales', 'PV', filiereId: null);

        $classe = ESBTPClasse::create([
            'name' => 'Licence 1 PV',
            'code' => 'L1PV',
            'filiere_id' => $this->miroirs->pourParcours($parcours)->id,
            'parcours_id' => $parcours->id,
            'niveau_etude_id' => $this->niveauLicence()->id,
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory()->create()->id,
            'places_totales' => 40,
        ]);

        $this->assertTrue($classe->exists);
        $this->assertNotNull($classe->filiere_id);
        $this->assertTrue(ESBTPFiliere::whereKey($classe->filiere_id)->exists());
    }

    public function test_un_reflet_efface_par_megarde_est_remis_en_service(): void
    {
        // Une ecole ne sait pas ce qu'est un reflet : elle peut l'effacer de sa
        // liste de filieres, le prenant pour un doublon. L'index unique compte
        // les lignes supprimees en douceur : sans reprise, la mention devenait
        // definitivement incapable de porter une classe.
        $mention = $this->mention('Sciences de la Terre', 'ST');
        $premier = $this->miroirs->pourMention($mention);
        $premier->delete();

        $repris = $this->miroirs->pourMention($mention->fresh());

        $this->assertSame($premier->id, $repris->id);
        $this->assertFalse($repris->trashed());
    }

    private function mention(string $nom, string $code): ESBTPLMDMention
    {
        $domaineId = DB::table('esbtp_lmd_domaines')->insertGetId([
            'name' => 'Domaine '.$code,
            'code' => 'D'.$code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ESBTPLMDMention::create([
            'name' => $nom,
            'code' => $code,
            'domaine_id' => $domaineId,
            'is_active' => true,
        ]);
    }

    private function niveauLicence(): ESBTPNiveauEtude
    {
        return ESBTPNiveauEtude::factory()->create([
            'name' => 'Licence 1',
            'libelle' => 'Licence 1',
            'type' => 'Licence',
            'year' => 1,
        ]);
    }

    private function parcours(string $nom, string $code, ?int $filiereId): ESBTPLMDParcours
    {
        return ESBTPLMDParcours::create([
            'name' => $nom,
            'code' => $code,
            'mention_id' => $this->mention($nom.' (mention)', 'M'.$code)->id,
            'filiere_id' => $filiereId,
            'is_active' => true,
        ]);
    }
}
