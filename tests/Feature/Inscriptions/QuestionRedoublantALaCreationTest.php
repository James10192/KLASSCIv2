<?php

namespace Tests\Feature\Inscriptions;

use App\Domain\Inscriptions\StatutRedoublant;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * « Redoublant ? » posé là où une inscription se crée : nouvelle inscription
 * (formulaire et « Accepter et inscrire », même enregistrement) et
 * réinscription d'une demande en ligne. Un motif manquant refuse AVANT toute
 * écriture.
 */
class QuestionRedoublantALaCreationTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        'admin.access', 'inscriptions.view', 'inscriptions.create',
        'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
        'reinscriptions.demandes.view', 'reinscriptions.demandes.process', 'students.view', 'classes.view',
    ];

    private User $agent;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $niveau;

    private int $numero = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:15:00');
        Mail::fake();
        Notification::fake();
        Cache::flush();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach ([...self::PERMISSIONS, 'paiements.create', StatutRedoublant::PERMISSION] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo([...self::PERMISSIONS, StatutRedoublant::PERMISSION]);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2026-2027', 'is_current' => true,
            'start_date' => '2026-09-01', 'end_date' => '2027-07-31']);
        $this->filiere = ESBTPFiliere::factory()->create(['name' => 'Génie civil']);
        $this->niveau = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 1', 'year' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_un_nouvel_eleve_declare_redoublant_sans_motif_n_est_pas_inscrit(): void
    {
        $this->actingAs($this->agent)->postJson(route('esbtp.inscriptions.store'), $this->nouvelEleve(['redoublant' => '1']))
            ->assertStatus(422)->assertJsonValidationErrors('redoublant_motif');

        $this->assertSame(0, ESBTPInscription::count());
    }

    public function test_un_nouvel_eleve_declare_redoublant_avec_motif_est_corrige_a_son_nom(): void
    {
        $this->actingAs($this->agent)->postJson(route('esbtp.inscriptions.store'), $this->nouvelEleve([
            'redoublant' => '1', 'redoublant_motif' => 'Redouble sa 1re année, venu du lycée de Bouaké',
        ]))->assertOk()->assertJsonPath('ok', true);

        $inscription = ESBTPInscription::sole();
        $this->assertTrue((bool) $inscription->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CORRIGE, $inscription->redoublant_source);
        $this->assertSame($this->agent->id, (int) $inscription->redoublant_confirme_par);
        $this->assertSame('Redouble sa 1re année, venu du lycée de Bouaké', $inscription->redoublant_motif);
    }

    public function test_repondre_non_confirme_la_proposition(): void
    {
        $this->actingAs($this->agent)->postJson(route('esbtp.inscriptions.store'), $this->nouvelEleve(['redoublant' => '0']))
            ->assertOk();

        $inscription = ESBTPInscription::sole();
        $this->assertFalse((bool) $inscription->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $inscription->redoublant_source);
    }

    public function test_sans_le_droit_de_confirmer_la_reponse_est_ignoree(): void
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(self::PERMISSIONS);

        $this->actingAs($agent)->postJson(route('esbtp.inscriptions.store'), $this->nouvelEleve(['redoublant' => '1']))
            ->assertOk();

        $inscription = ESBTPInscription::sole();
        $this->assertFalse((bool) $inscription->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $inscription->redoublant_source);
    }

    public function test_la_proposition_de_reinscription_donne_le_niveau_de_l_annee_d_avant(): void
    {
        $demande = $this->demandeAvecAnneePassee();

        $this->actingAs($this->agent)
            ->getJson(route('esbtp.reinscription-demandes.proposition', ['demande' => $demande, 'annee' => $this->annee->id]))
            ->assertOk()->assertJsonPath('niveau_avant', $this->niveau->id);
    }

    public function test_reinscrire_une_demande_en_changeant_la_proposition_exige_un_motif_sans_rien_reserver(): void
    {
        $demande = $this->demandeAvecAnneePassee();

        // Même niveau que l'an dernier : la proposition est « oui ».
        $this->actingAs($this->agent)->postJson(route('esbtp.reinscription-demandes.convertir', $demande), [
            'classe_id' => $this->classe('1A BTS bis')->id, 'decision' => 'passage', 'redoublant' => '0',
        ])->assertStatus(422)->assertJsonValidationErrors('redoublant_motif');

        $this->assertSame('en_attente', $demande->fresh()->statut);
        $this->assertSame(1, ESBTPInscription::where('etudiant_id', $demande->etudiant_id)->count());
    }

    public function test_reinscrire_une_demande_enregistre_la_reponse(): void
    {
        $demande = $this->demandeAvecAnneePassee();

        $this->actingAs($this->agent)->postJson(route('esbtp.reinscription-demandes.convertir', $demande), [
            'classe_id' => $this->classe('1A BTS bis')->id, 'decision' => 'redoublement', 'redoublant' => '1',
        ])->assertOk()->assertJsonPath('ok', true);

        $inscription = ESBTPInscription::findOrFail($demande->fresh()->inscription_id);
        $this->assertTrue((bool) $inscription->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $inscription->redoublant_source);
    }

    public function test_une_inscription_d_avant_non_finalisee_compte_comme_a_l_enregistrement(): void
    {
        // Pas encore finalisee : la fenetre ne la voit pas comme « quittee »,
        // mais l'enregistrement la compte. L'ecran doit proposer la meme chose.
        $demande = $this->demandeAvecAnneePassee(['workflow_step' => 'valide']);

        $this->actingAs($this->agent)
            ->getJson(route('esbtp.reinscription-demandes.proposition', ['demande' => $demande, 'annee' => $this->annee->id]))
            ->assertOk()->assertJsonPath('niveau_avant', $this->niveau->id);

        // « Oui » est la proposition : aucun motif n'est demande.
        $this->actingAs($this->agent)->postJson(route('esbtp.reinscription-demandes.convertir', $demande), [
            'classe_id' => $this->classe('1A BTS bis')->id, 'decision' => 'redoublement', 'redoublant' => '1',
        ])->assertOk();
    }

    public function test_sans_inscription_d_avant_la_proposition_est_non(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => 'BAMBA', 'prenoms' => 'Ali', 'matricule' => 'MAT-X']);
        $demande = ESBTPReinscriptionDemande::forceCreate([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $this->annee->id,
            'statut' => 'en_attente', 'consentement_at' => now(),
        ]);

        $this->actingAs($this->agent)
            ->getJson(route('esbtp.reinscription-demandes.proposition', ['demande' => $demande, 'annee' => $this->annee->id]))
            ->assertOk()->assertJsonPath('niveau_avant', null);
    }

    public function test_les_fenetres_posent_la_question_a_qui_peut_confirmer(): void
    {
        $this->actingAs($this->agent)->get(route('esbtp.demandes.index'))
            ->assertOk()->assertSee('"redoublant":true', false)->assertSee('Redoublant ?');
    }

    /* ─────────────── Données ─────────────── */

    private function nouvelEleve(array $valeurs = []): array
    {
        $n = ++$this->numero;

        return array_merge([
            'nom' => 'KONE'.$n, 'prenoms' => 'Awa', 'sexe' => 'F', 'date_naissance' => '2007-03-12',
            'telephone' => '+225 07 07 12 34 '.sprintf('%02d', $n), 'classe_id' => $this->classe('1A BTS '.$n)->id,
            'annee_universitaire_id' => $this->annee->id, 'duplicate_override' => 1,
        ], $valeurs);
    }

    private function demandeAvecAnneePassee(array $inscriptionPassee = []): ESBTPReinscriptionDemande
    {
        $passee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'is_current' => false,
            'start_date' => '2025-09-01', 'end_date' => '2026-07-31']);
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => 'YAO', 'prenoms' => 'Serge', 'matricule' => 'MAT-'.(++$this->numero)]);
        ESBTPInscription::factory()->create(array_merge([
            'etudiant_id' => $etudiant->id, 'classe_id' => $this->classe('1A BTS')->id,
            'filiere_id' => $this->filiere->id, 'niveau_id' => $this->niveau->id,
            'annee_universitaire_id' => $passee->id, 'status' => 'active',
        ], $inscriptionPassee));

        return ESBTPReinscriptionDemande::forceCreate([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $this->annee->id,
            'statut' => 'en_attente', 'consentement_at' => now(),
        ]);
    }

    private function classe(string $nom): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'name' => $nom, 'filiere_id' => $this->filiere->id, 'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id, 'places_totales' => 30, 'is_active' => true,
        ]);
    }
}
