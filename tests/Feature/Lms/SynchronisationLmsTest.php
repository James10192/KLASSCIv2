<?php

namespace Tests\Feature\Lms;

use App\Domain\Lms\Synchronisation\SynchronisationLms;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use App\Support\Lms\JetonServeurLms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Lot 2 de la liaison LMS : GET /api/lms/v2/sync rend « ce qui a change
 * depuis » un curseur, tous types confondus, suppressions comprises.
 */
class SynchronisationLmsTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private \App\Models\ESBTPEtudiant $eleve;

    private ESBTPMatiere $matiere;

    private string $jeton;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('identity.student', 'web');
        $this->monterLaClasse();
        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        User::findOrFail(1)->assignRole('superAdmin');
        \App\Helpers\InstallationHelper::flushCachedStatus();
        $this->annee->update(['is_current' => true]);
        $this->matiere = $this->matiereConfiguree();
        $this->eleve = $this->etudiantInscrit();

        $service = User::factory()->create(['username' => JetonServeurLms::COMPTE]);
        $this->jeton = $service->createToken('lms', [JetonServeurLms::SERVEUR, JetonServeurLms::LECTURE])->plainTextToken;
    }

    /** @test */
    public function seul_le_jeton_serveur_avec_la_lecture_synchronise(): void
    {
        $eleve = User::factory()->create();
        $eleve->givePermissionTo('identity.student');
        $sansLecture = User::factory()->create()->createToken('x', [JetonServeurLms::SERVEUR, JetonServeurLms::NOTES])->plainTextToken;

        $this->sync([], $eleve->createToken('x', ['lms:access'])->plainTextToken)->assertForbidden();
        $this->sync([], $sansLecture)->assertForbidden();
        $this->sync([], $this->createToken('*'))->assertForbidden();
    }

    /** @test */
    public function la_premiere_synchronisation_rend_tout_avec_la_forme_attendue(): void
    {
        $this->seance();
        $this->travel(1)->minutes();

        $data = $this->sync()->assertOk()->json('data');
        $parType = collect($data['changements'])->groupBy('type');

        $this->assertFalse($data['a_suivre']);
        $this->assertSame([$this->classe->id], $parType['classe']->pluck('id')->all());
        $this->assertContains($this->matiere->id, $parType['matiere']->pluck('id')->all());
        $this->assertSame([$this->eleve->id], $parType['etudiant']->pluck('id')->all());

        $inscription = $parType['inscription']->first();
        $this->assertTrue($inscription['donnees']['validee']);
        $this->assertSame($this->classe->id, $inscription['donnees']['classe_id']);

        // L'heure sort telle qu'en base, pas en date du jour (piege #14).
        $seance = $parType['seance']->first()['donnees'];
        $this->assertSame('08:00', $seance['heure_debut']);
        $this->assertSame('10:00', $seance['heure_fin']);
    }

    /** @test */
    public function sans_changement_le_meme_curseur_revient_et_l_etag_donne_304(): void
    {
        $this->travel(1)->minutes();
        $premier = $this->sync();
        $curseur = $premier->json('data.curseur');

        $second = $this->sync(['since' => $curseur])->assertOk();
        $this->assertSame([], $second->json('data.changements'));
        $this->assertSame($curseur, $second->json('data.curseur'));

        $this->app['auth']->forgetGuards();
        $this->withToken($this->jeton)
            ->withHeaders(['If-None-Match' => $second->headers->get('ETag')])
            ->getJson('/api/lms/v2/sync?since='.$curseur)
            ->assertStatus(304);
    }

    /** @test */
    public function les_pages_couvrent_tout_sans_doublon(): void
    {
        foreach (range(1, 3) as $i) {
            $this->etudiantInscrit();
        }
        $this->travel(1)->minutes();
        $complet = collect($this->sync()->json('data.changements'))->map(fn ($c) => $c['type'].':'.$c['id'])->all();

        $vus = [];
        $curseur = null;
        do {
            $page = $this->sync(array_filter(['since' => $curseur, 'limit' => 2]))->assertOk()->json('data');
            $this->assertLessThanOrEqual(2, count($page['changements']));
            foreach ($page['changements'] as $c) {
                $vus[] = $c['type'].':'.$c['id'];
            }
            $curseur = $page['curseur'];
        } while ($page['a_suivre']);

        $this->assertSame($complet, $vus);
    }

    /** @test */
    public function une_modification_apres_le_curseur_revient_seule(): void
    {
        $this->travel(1)->minutes();
        $curseur = $this->sync()->json('data.curseur');

        $this->classe->update(['name' => 'Classe renommee']);
        $this->travel(1)->minutes();

        $changements = $this->sync(['since' => $curseur])->json('data.changements');
        $this->assertCount(1, $changements);
        $this->assertSame('Classe renommee', $changements[0]['donnees']['nom']);
    }

    /** @test */
    public function un_compte_desactive_fait_repartir_l_eleve_inactif(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->eleve->update(['user_id' => $user->id]);
        $this->travel(1)->minutes();
        $curseur = $this->sync()->json('data.curseur');

        $user->update(['is_active' => false]);
        $this->travel(1)->minutes();

        $changements = $this->sync(['since' => $curseur])->json('data.changements');
        $this->assertSame(['etudiant'], array_column($changements, 'type'));
        $this->assertFalse($changements[0]['donnees']['actif']);
    }

    /** @test */
    public function une_suppression_douce_part_sans_donnees(): void
    {
        $this->travel(1)->minutes();
        $curseur = $this->sync()->json('data.curseur');

        $inscription = ESBTPInscription::firstOrFail();
        $inscription->delete();
        $this->travel(1)->minutes();

        $changements = $this->sync(['since' => $curseur])->json('data.changements');
        $this->assertSame([['type' => 'inscription', 'id' => $inscription->id, 'supprime' => true]],
            array_map(fn ($c) => array_intersect_key($c, array_flip(['type', 'id', 'supprime', 'donnees'])), $changements));
    }

    /** @test */
    public function une_purge_part_en_suppression_definitive_mais_pas_celles_d_avant_la_premiere_synchro(): void
    {
        $avant = $this->seance();
        $avant->forceDelete();
        $this->travel(1)->minutes();

        $premiere = $this->sync()->json('data');
        $this->assertNotContains(true, array_column($premiere['changements'], 'definitif'));

        $seance = $this->seance();
        $this->travel(1)->minutes();
        $curseur = $this->sync(['since' => $premiere['curseur']])->json('data.curseur');

        $seance->forceDelete();
        $this->travel(1)->minutes();

        $changements = $this->sync(['since' => $curseur])->json('data.changements');
        $this->assertSame([['type' => 'seance', 'id' => $seance->id, 'supprime' => true, 'definitif' => true]],
            array_map(fn ($c) => array_intersect_key($c, array_flip(['type', 'id', 'supprime', 'definitif'])), $changements));
    }

    /** @test */
    public function les_inscriptions_d_une_autre_annee_ne_partent_pas(): void
    {
        $autre = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->eleve->id, 'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $autre->id, 'status' => 'active', 'workflow_step' => 'etudiant_cree',
        ]);
        $this->travel(1)->minutes();

        $inscriptions = collect($this->sync(['types' => 'inscriptions'])->json('data.changements'));
        $this->assertSame([$this->annee->id], $inscriptions->pluck('donnees.annee_universitaire_id')->unique()->values()->all());
    }

    /** @test */
    public function les_lignes_trop_recentes_attendent_l_appel_suivant(): void
    {
        $this->travel(1)->minutes();
        $curseur = $this->sync()->json('data.curseur');

        $this->classe->update(['name' => 'Tout juste modifiee']);
        $this->assertSame([], $this->sync(['since' => $curseur])->json('data.changements'));

        $this->travel(SynchronisationLms::DECALAGE_SECONDES + 1)->seconds();
        $this->assertCount(1, $this->sync(['since' => $curseur])->json('data.changements'));
    }

    /** @test */
    public function les_demandes_invalides_sont_refusees_avec_une_raison(): void
    {
        $this->sync(['types' => 'classes,notes'])->assertStatus(422)->assertJsonFragment(['success' => false]);
        $this->sync(['since' => 'pas-un-curseur'])->assertStatus(422);

        $curseur = $this->sync()->json('data.curseur');
        $autre = ESBTPAnneeUniversitaire::factory()->create();
        $this->sync(['since' => $curseur, 'annee_universitaire_id' => $autre->id])->assertStatus(422);
    }

    private function sync(array $params = [], ?string $jeton = null): TestResponse
    {
        // Sanctum garde l'utilisateur d'une requete a l'autre dans un test.
        $this->app['auth']->forgetGuards();

        return $this->withToken($jeton ?? $this->jeton)->getJson('/api/lms/v2/sync?'.http_build_query($params));
    }

    private function createToken(string $droit): string
    {
        return User::factory()->create()->createToken('x', [$droit])->plainTextToken;
    }

    private function seance(): ESBTPSeanceCours
    {
        $emploi = \App\Models\ESBTPEmploiTemps::create([
            'titre' => 'Planning test LMS',
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 'semestre1',
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploi->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'annee_universitaire_id' => $this->annee->id,
            'jour' => 'lundi',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
        ]);
    }
}
