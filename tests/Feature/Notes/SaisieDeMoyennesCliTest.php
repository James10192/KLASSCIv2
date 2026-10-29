<?php

namespace Tests\Feature\Notes;

use App\Http\Controllers\API\CLI\CLIMoyennesController;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * POST /api/cli/resultats/moyennes : la saisie de moyennes a distance, pour
 * traiter une reclamation d'eleve sans l'ecran « Modifier les moyennes ».
 */
class SaisieDeMoyennesCliTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private ESBTPEtudiant $eleve;

    private ESBTPMatiere $anglais;

    private ESBTPMatiere $physique;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
        $this->anglais = $this->matiereConfiguree();
        $this->physique = $this->matiereConfiguree();
        $this->eleve = $this->etudiantInscrit();
    }

    /** @test */
    public function la_route_existe_dans_le_groupe_cli(): void
    {
        $this->assertTrue(Route::has('api.cli.resultats.moyennes'));
    }

    /** @test */
    public function sans_dry_run_explicite_rien_n_est_ecrit(): void
    {
        $reponse = $this->appeler(['moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 15]]]);

        $this->assertSame(200, $reponse->getStatusCode());
        $data = $reponse->getData(true)['data'];
        $this->assertTrue($data['dry_run']);
        $this->assertSame('creee', $data['lignes'][0]['action']);
        $this->assertSame(0, ESBTPResultat::count());
    }

    /** @test */
    public function l_ecriture_cree_modifie_retire_et_laisse_l_inchange(): void
    {
        $this->resultat($this->physique, 0);
        $fixe = $this->matiereConfiguree();
        $this->resultat($fixe, 12);
        $retiree = $this->matiereConfiguree();
        $this->resultat($retiree, 0);

        $data = $this->appeler(['dry_run' => false, 'moyennes' => [
            ['matiere_id' => $this->anglais->id, 'moyenne' => 15],
            ['matiere_id' => $this->physique->id, 'moyenne' => 10.16],
            ['matiere_id' => $fixe->id, 'moyenne' => 12],
            ['matiere_id' => $retiree->id, 'moyenne' => null],
        ]])->getData(true)['data'];

        $this->assertSame(['creee', 'modifiee', 'inchangee', 'retiree'], array_column($data['lignes'], 'action'));
        $this->assertSame(15.0, $this->moyenne($this->anglais));
        $this->assertSame(10.16, $this->moyenne($this->physique));
        $this->assertSame(12.0, $this->moyenne($fixe));
        $this->assertNull($this->moyenne($retiree));
        // Retrait doux : la ligne d'avant peut revenir, l'audit garde la trace.
        $this->assertSame(1, ESBTPResultat::onlyTrashed()->where('matiere_id', $retiree->id)->count());
        // Coefficient de la combinaison configuree, pas un 1 par defaut.
        $this->assertSame(2.0, (float) ESBTPResultat::where('matiere_id', $this->anglais->id)->value('coefficient'));
    }

    /** @test */
    public function sans_motif_la_demande_est_refusee(): void
    {
        $this->expectException(ValidationException::class);
        $this->appeler(['motif' => 'court', 'moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 15]]]);
    }

    /** @test */
    public function un_eleve_non_inscrit_dans_la_classe_est_refuse(): void
    {
        $autre = ESBTPEtudiant::factory()->create();

        $reponse = $this->appeler(['etudiant_id' => $autre->id, 'dry_run' => false, 'moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 15]]]);

        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertSame(0, ESBTPResultat::count());
    }

    /** @test */
    public function un_jeton_en_lecture_seule_ne_peut_pas_ecrire(): void
    {
        $reponse = $this->appeler(['dry_run' => false, 'moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 15]]], ['cli:read']);

        $this->assertSame(403, $reponse->getStatusCode());
        $this->assertSame(0, ESBTPResultat::count());
    }

    /** @test */
    public function une_ecue_est_refusee_avant_toute_ecriture_meme_en_simulation(): void
    {
        $this->resultat($this->physique, 0);
        $ue = ESBTPUniteEnseignement::create(['name' => 'UE Ouvrages', 'code' => 'UE-TPGC641', 'credit' => 6, 'semestre' => 1, 'is_active' => true]);
        $ecue = ESBTPMatiere::factory()->create(['code' => 'TPGC641', 'unite_enseignement_id' => $ue->id]);

        $reponse = $this->appeler(['dry_run' => false, 'moyennes' => [
            ['matiere_id' => $this->physique->id, 'moyenne' => 14],
            ['matiere_id' => $ecue->id, 'moyenne' => 12],
        ]]);

        $this->assertSame(422, $reponse->getStatusCode());
        // Refus avant toute ecriture : la ligne valide n'a pas ete ecrite non plus.
        $this->assertSame(0.0, $this->moyenne($this->physique));
        $this->assertSame(422, $this->appeler(['moyennes' => [['matiere_id' => $ecue->id, 'moyenne' => 12]]])->getStatusCode());
    }

    /** @test */
    public function deux_moyennes_vivantes_sur_la_meme_matiere_bloquent_la_saisie(): void
    {
        // L'index unique inclut deleted_at : deux lignes vivantes peuvent coexister.
        $this->resultat($this->anglais, 4);
        $this->resultat($this->anglais, 6);

        $reponse = $this->appeler(['dry_run' => false, 'moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 15]]]);

        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertStringContainsString('dédoublonner', json_encode($reponse->getData(true), JSON_UNESCAPED_UNICODE));
        $this->assertSame([4.0, 6.0], ESBTPResultat::where('matiere_id', $this->anglais->id)->orderBy('id')->pluck('moyenne')->map(fn ($m) => (float) $m)->all());
    }

    /** @test */
    public function le_second_semestre_s_ecrit_sur_sa_propre_periode(): void
    {
        $this->resultat($this->anglais, 9);

        $this->appeler(['dry_run' => false, 'periode' => 'semestre2', 'moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 13]]]);

        $this->assertSame(9.0, (float) ESBTPResultat::where('matiere_id', $this->anglais->id)->where('periode', 'semestre1')->value('moyenne'));
        $this->assertSame(13.0, (float) ESBTPResultat::where('matiere_id', $this->anglais->id)->where('periode', 'semestre2')->value('moyenne'));
    }

    /** @test */
    public function une_classe_lmd_est_refusee(): void
    {
        $this->classe->update(['systeme_academique' => 'LMD']);

        $reponse = $this->appeler(['moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 15]]]);

        $this->assertSame(422, $reponse->getStatusCode());
    }

    /** @test */
    public function une_moyenne_retiree_peut_etre_saisie_de_nouveau(): void
    {
        $this->resultat($this->anglais, 0);
        $this->appeler(['dry_run' => false, 'moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => null]]]);

        $data = $this->appeler(['dry_run' => false, 'moyennes' => [['matiere_id' => $this->anglais->id, 'moyenne' => 15]]])->getData(true)['data'];

        $this->assertSame('creee', $data['lignes'][0]['action']);
        $this->assertSame(15.0, $this->moyenne($this->anglais));
        $this->assertSame(1, ESBTPResultat::onlyTrashed()->where('matiere_id', $this->anglais->id)->count());
    }

    /**
     * @param  array<string,mixed>  $surcharge
     * @param  array<int,string>  $droits
     */
    private function appeler(array $surcharge, array $droits = ['cli:admin']): JsonResponse
    {
        $charge = $surcharge + [
            'etudiant_id' => $this->eleve->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'motif' => 'Réclamation de l’élève validée par la direction des études',
        ];
        $requete = Request::create('/', 'POST', $charge);
        $requete->setUserResolver(fn () => new class($droits) extends User
        {
            /** @var array<int,string> */
            private array $droits;

            /** @param array<int,string> $droits */
            public function __construct(array $droits = [])
            {
                parent::__construct();
                $this->droits = $droits;
                $this->setRawAttributes(['id' => 1]);
            }

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->droits, true);
            }
        });

        return app(CLIMoyennesController::class)->enregistrer($requete, app(\App\Domain\Notes\SaisieDeMoyennes::class));
    }

    private function resultat(ESBTPMatiere $matiere, float $moyenne): void
    {
        ESBTPResultat::create([
            'etudiant_id' => $this->eleve->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'moyenne' => $moyenne,
            'coefficient' => 2,
        ]);
    }

    private function moyenne(ESBTPMatiere $matiere): ?float
    {
        $v = ESBTPResultat::where('etudiant_id', $this->eleve->id)->where('matiere_id', $matiere->id)->value('moyenne');

        return $v === null ? null : (float) $v;
    }
}
