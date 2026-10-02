<?php

namespace Tests\Feature\Inscriptions;

use App\Domain\Inscriptions\RecensementDesRedoublants;
use App\Domain\Inscriptions\StatutRedoublant;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\ReeinscriptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le statut redoublant : déduit par le logiciel, confirmé ou corrigé par une
 * personne habilitée, jamais réécrit après qu'une personne a tranché.
 */
class StatutRedoublantTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $bts1;

    private ESBTPNiveauEtude $bts2;

    private ESBTPAnneeUniversitaire $anDernier;

    private ESBTPAnneeUniversitaire $cetteAnnee;

    private User $scolarite;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['admin.access', 'inscriptions.view', StatutRedoublant::PERMISSION] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Cache::flush();

        $this->scolarite = User::factory()->create(['name' => 'Agent Scolarité']);
        $this->scolarite->givePermissionTo(['admin.access', 'inscriptions.view', StatutRedoublant::PERMISSION]);

        $this->filiere = ESBTPFiliere::factory()->create();
        $this->bts1 = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 1', 'year' => 1]);
        $this->bts2 = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 2', 'year' => 2]);
        $this->anDernier = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2090-2091', 'start_date' => '2090-09-01', 'end_date' => '2091-07-31', 'is_current' => false,
        ]);
        $this->cetteAnnee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2091-2092', 'start_date' => '2091-09-01', 'end_date' => '2092-07-31', 'is_current' => false,
        ]);
    }

    public function test_le_recensement_deduit_toutes_les_annees_et_reprend_la_decision(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $repetee = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, [
            'type_inscription' => 'réinscription',
            'reinscription_observations' => "Redoublement - moyenne insuffisante\n[BATCH abc]",
        ]);
        $this->commeAvantLeStatut($repetee);

        $aBlanc = app(RecensementDesRedoublants::class)->executer(false);
        $this->assertFalse((bool) $repetee->fresh()->is_redoublant, 'À blanc, rien n\'est écrit.');
        $this->assertGreaterThanOrEqual(1, $aBlanc['a_poser']);

        app(RecensementDesRedoublants::class)->executer(true);
        $repetee->refresh();

        $this->assertTrue((bool) $repetee->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $repetee->redoublant_source);
        $this->assertSame('redoublement', $repetee->decision_reinscription);
        $this->assertTrue(app(StatutRedoublant::class)->aConfirmer($repetee));
    }

    public function test_le_recensement_ne_reecrit_jamais_ce_qu_une_personne_a_tranche(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $corrigee = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, [
            'is_redoublant' => false,
            'redoublant_source' => StatutRedoublant::SOURCE_CORRIGE,
            'redoublant_motif' => 'Redoublement validé ailleurs, dérogation du conseil',
        ]);
        \Illuminate\Support\Facades\DB::table('esbtp_inscriptions')->where('id', $corrigee->id)
            ->update(['is_redoublant' => false, 'redoublant_source' => StatutRedoublant::SOURCE_CORRIGE]);

        app(RecensementDesRedoublants::class)->executer(true);

        $this->assertFalse((bool) $corrigee->fresh()->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CORRIGE, $corrigee->fresh()->redoublant_source);
    }

    public function test_confirmer_garde_la_valeur_et_corriger_exige_un_motif(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts1, $this->anDernier);
        $inscription = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $statut = app(StatutRedoublant::class);
        $this->poser($inscription);

        $statut->etablir($inscription, $this->scolarite, false);
        $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $inscription->fresh()->redoublant_source);
        $this->assertSame($this->scolarite->id, (int) $inscription->fresh()->redoublant_confirme_par);

        try {
            $statut->etablir($inscription->fresh(), $this->scolarite, true, 'court');
            $this->fail('Changer la valeur sans motif suffisant doit être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('motif', $e->errors());
        }

        $statut->etablir($inscription->fresh(), $this->scolarite, true, 'Redouble suite au conseil de classe de juin');
        $this->assertTrue((bool) $inscription->fresh()->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CORRIGE, $inscription->fresh()->redoublant_source);

        app(RecensementDesRedoublants::class)->executer(true);
        $this->assertTrue((bool) $inscription->fresh()->is_redoublant, 'Une déduction ne réécrit pas une correction.');
    }

    public function test_changer_de_niveau_rouvre_la_confirmation(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $inscription = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $statut = app(StatutRedoublant::class);
        $this->poser($inscription);
        $statut->etablir($inscription->fresh('anneeUniversitaire'), $this->scolarite, true);
        $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $inscription->fresh()->redoublant_source);

        // N'importe quel écran qui déplace l'inscription : le modèle rouvre.
        $deplacee = $inscription->fresh();
        $deplacee->niveau_id = $this->bts1->id;
        $deplacee->save();

        $inscription->refresh();
        $this->assertFalse((bool) $inscription->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $inscription->redoublant_source);
        $this->assertNull($inscription->redoublant_confirme_par);
    }

    public function test_la_reinscription_par_une_personne_habilitee_vaut_confirmation(): void
    {
        User::factory()->create(['id' => 1]);
        $this->actingAs($this->scolarite);
        [$etudiant] = $this->etudiantEnClasse($this->bts2, $this->anDernier);
        $classeMemeNiveau = $this->classe($this->bts2, 'BTS2 R');

        app(ReeinscriptionService::class)->effectuerReinscription(
            etudiantId: $etudiant->id,
            nouvelleClasseId: $classeMemeNiveau->id,
            decision: 'redoublement',
            anneeUniversitaireId: $this->cetteAnnee->id,
            sendNotification: false,
            redoublant: true,
        );

        $nouvelle = ESBTPInscription::where('etudiant_id', $etudiant->id)->where('annee_universitaire_id', $this->cetteAnnee->id)->first();
        $this->assertTrue((bool) $nouvelle->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $nouvelle->redoublant_source);
        $this->assertSame('redoublement', $nouvelle->decision_reinscription);
    }

    public function test_sans_le_droit_la_reinscription_laisse_la_valeur_deduite(): void
    {
        User::factory()->create(['id' => 1]);
        $sansDroit = User::factory()->create();
        $this->actingAs($sansDroit);
        [$etudiant] = $this->etudiantEnClasse($this->bts1, $this->anDernier);

        app(ReeinscriptionService::class)->effectuerReinscription(
            etudiantId: $etudiant->id,
            nouvelleClasseId: $this->classe($this->bts2, 'BTS2 P')->id,
            decision: 'passage',
            anneeUniversitaireId: $this->cetteAnnee->id,
            sendNotification: false,
            redoublant: true,
        );

        $nouvelle = ESBTPInscription::where('etudiant_id', $etudiant->id)->where('annee_universitaire_id', $this->cetteAnnee->id)->first();
        $this->assertFalse((bool) $nouvelle->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $nouvelle->redoublant_source);
    }

    public function test_la_fiche_confirme_par_ajax_et_refuse_sans_le_droit(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $inscription = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->poser($inscription);

        $sansDroit = User::factory()->create();
        $sansDroit->givePermissionTo(['admin.access', 'inscriptions.view']);
        $this->actingAs($sansDroit)
            ->postJson(route('esbtp.inscriptions.redoublant.etablir', $inscription), ['valeur' => 1])
            ->assertForbidden();

        $this->actingAs($this->scolarite)
            ->postJson(route('esbtp.inscriptions.redoublant.etablir', $inscription), ['valeur' => 1])
            ->assertOk()
            ->assertJsonPath('statut.etat', 'confirme')
            ->assertJsonPath('statut.valeur', true);

        $this->actingAs($this->scolarite)
            ->postJson(route('esbtp.inscriptions.redoublant.etablir', $inscription), ['valeur' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motif');
    }

    public function test_la_confirmation_en_masse_ne_touche_que_ce_qui_attendait(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $aConfirmer = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->poser($aConfirmer);
        $nouvelArrivant = $this->inscrire(ESBTPEtudiant::factory()->create(), $this->bts1, $this->cetteAnnee);

        $this->actingAs($this->scolarite)
            ->postJson(route('esbtp.inscriptions.redoublant.confirmer-en-masse'), [
                'inscription_ids' => [$aConfirmer->id, $nouvelArrivant->id],
            ])
            ->assertOk()
            ->assertJsonPath('confirmees', 1)
            ->assertJsonPath('ignorees', 1);

        $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $aConfirmer->fresh()->redoublant_source);
        $this->assertTrue((bool) $aConfirmer->fresh()->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $nouvelArrivant->fresh()->redoublant_source, 'Un nouvel arrivant n\'a rien à confirmer.');
    }

    public function test_la_liste_filtre_les_statuts_a_confirmer(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => 'ZZREDOUBLE']);
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $inscription = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->poser($inscription);
        $autre = ESBTPEtudiant::factory()->create(['nom' => 'ZZNOUVEAU']);
        $this->inscrire($autre, $this->bts1, $this->cetteAnnee);

        $this->actingAs($this->scolarite)
            ->get(route('esbtp.inscriptions.index', ['annee' => $this->cetteAnnee->id, 'status' => 'all', 'redoublant' => 'a_confirmer']))
            ->assertOk()
            ->assertSee('ZZREDOUBLE')
            ->assertSee('Redoublant ?')
            ->assertDontSee('ZZNOUVEAU');
    }

    public function test_la_fiche_montre_le_statut_et_les_boutons_au_seul_habilite(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $inscription = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->poser($inscription);

        $this->actingAs($this->scolarite)
            ->get(route('esbtp.inscriptions.show', $inscription))
            ->assertOk()
            ->assertSee('statutRedoublantFiche', false)
            ->assertSee('Corriger');

        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo(['admin.access', 'inscriptions.view']);
        $this->actingAs($lecteur)
            ->get(route('esbtp.inscriptions.show', $inscription))
            ->assertOk()
            ->assertSee('statutRedoublantFiche', false)
            ->assertDontSee('<i class="fas fa-pen"></i> Corriger', false);
    }

    public function test_confirmer_avant_le_recensement_retient_la_vraie_deduction(): void
    {
        // En production la colonne valait « non » partout : confirmer tel quel
        // ne doit pas figer ce « non » faux comme une décision humaine.
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $inscription = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->commeAvantLeStatut($inscription);

        $this->assertTrue(app(StatutRedoublant::class)->pourAffichage($inscription->fresh())['valeur']);
        $this->assertTrue((bool) $inscription->fresh()->is_redoublant, 'La lecture recense : le bulletin dira la même chose.');

        $this->actingAs($this->scolarite)
            ->postJson(route('esbtp.inscriptions.redoublant.confirmer-en-masse'), ['inscription_ids' => [$inscription->id]])
            ->assertOk()
            ->assertJsonPath('confirmees', 1);

        $inscription->refresh();
        $this->assertTrue((bool) $inscription->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $inscription->redoublant_source);
    }

    public function test_le_pre_controle_compte_la_classe_et_ne_donne_le_lien_qu_a_qui_peut_l_ouvrir(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->bts2, $this->anDernier);
        $inscription = $this->inscrire($etudiant, $this->bts2, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->poser($inscription);
        $statut = app(StatutRedoublant::class);

        $constat = $statut->pourLePreControle($inscription->classe, $this->cetteAnnee->id, $this->scolarite);
        $this->assertSame(1, $constat['redoublants_a_confirmer']);
        $this->assertStringContainsString('classe='.$inscription->classe_id, $constat['redoublants_url']);

        $sansListe = User::factory()->create();
        $sansListe->givePermissionTo(StatutRedoublant::PERMISSION);
        $this->assertNull($statut->pourLePreControle($inscription->classe, $this->cetteAnnee->id, $sansListe)['redoublants_url']);
    }

    public function test_une_annee_passee_importee_apres_coup_met_a_jour_l_annee_suivante(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => 'ZZIMPORTE']);
        $cetteAnnee = $this->inscrire($etudiant, $this->bts1, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->assertFalse((bool) $cetteAnnee->fresh()->is_redoublant, 'Sans année passée, rien à redoubler.');

        // L'école importe son historique : l'élève était déjà en BTS 1.
        $this->inscrire($etudiant, $this->bts1, $this->anDernier);

        $this->assertTrue((bool) $cetteAnnee->fresh()->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $cetteAnnee->fresh()->redoublant_source);

        $this->actingAs($this->scolarite)
            ->get(route('esbtp.inscriptions.index', ['annee' => $this->cetteAnnee->id, 'status' => 'all', 'redoublant' => 'oui']))
            ->assertOk()
            ->assertSee('ZZIMPORTE')
            ->assertSee('Redoublant ?');
    }

    public function test_supprimer_l_annee_passee_retire_la_deduction(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $passee = $this->inscrire($etudiant, $this->bts1, $this->anDernier);
        $cetteAnnee = $this->inscrire($etudiant, $this->bts1, $this->cetteAnnee, ['type_inscription' => 'réinscription']);
        $this->assertTrue((bool) $cetteAnnee->fresh()->is_redoublant);

        $passee->delete();

        $this->assertFalse((bool) $cetteAnnee->fresh()->is_redoublant);
    }

    /** L'état d'une inscription créée avant ce statut : colonne à non, jamais recensée. */
    private function commeAvantLeStatut(ESBTPInscription $inscription): void
    {
        \Illuminate\Support\Facades\DB::table('esbtp_inscriptions')->where('id', $inscription->id)
            ->update(['is_redoublant' => false, 'redoublant_source' => null]);
    }

    /** La valeur déduite posée, comme le fait le recensement. */
    private function poser(ESBTPInscription $inscription): void
    {
        $inscription->forceFill([
            'is_redoublant' => app(StatutRedoublant::class)->deduire($inscription),
            'redoublant_source' => StatutRedoublant::SOURCE_DEDUIT,
        ])->save();
    }

    private function inscrire(ESBTPEtudiant $etudiant, ESBTPNiveauEtude $niveau, ESBTPAnneeUniversitaire $annee, array $plus = []): ESBTPInscription
    {
        $classe = $this->classe($niveau, 'C'.$niveau->id.'-'.$annee->id.'-'.$etudiant->id);

        return ESBTPInscription::factory()->create(array_merge([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'type_inscription' => 'première_inscription',
            'est_transfert' => false,
            'is_redoublant' => false,
            'redoublant_source' => null,
        ], $plus));
    }

    /** @return array{0: ESBTPEtudiant, 1: ESBTPClasse} */
    private function etudiantEnClasse(ESBTPNiveauEtude $niveau, ESBTPAnneeUniversitaire $annee): array
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = $this->inscrire($etudiant, $niveau, $annee);

        return [$etudiant, $inscription->classe];
    }

    private function classe(ESBTPNiveauEtude $niveau, string $nom): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'name' => $nom,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => true,
        ]);
    }
}
