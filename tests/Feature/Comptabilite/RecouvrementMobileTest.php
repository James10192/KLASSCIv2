<?php

namespace Tests\Feature\Comptabilite;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPRelance;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Recouvrement quotidien en mobile (issue #963, lot 2a, maquette
 * S['comptable:recouvrement']) : l'écran m-* est rendu à côté du DOM de
 * bureau pour une personne profilée « comptable », le bouton d'export ne
 * s'affiche qu'avec la permission des routes d'export, et « Fait » passe
 * par le même mark-done JSON que le bureau.
 */
class RecouvrementMobileTest extends TestCase
{
    use DatabaseTransactions;

    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['comptabilite.access', 'comptabilite.recouvrement.access', 'comptabilite.dashboard.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // comptabilite.access sans module.caisse.access : le profil mobile
        // résolu est « comptable » (cascade de MobileProfileResolver).
        $this->comptable = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->comptable->givePermissionTo(['comptabilite.access', 'comptabilite.recouvrement.access', 'comptabilite.dashboard.view']);

        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '1');
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_l_ecran_mobile_est_rendu_a_cote_du_dom_de_bureau(): void
    {
        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.recouvrement.index'));

        $reponse->assertOk()
            ->assertSee('has-m-shell m-profile-comptable', false)
            ->assertSee('m-only-desktop', false)
            ->assertSee('m-only-mobile m-screen rcm-screen', false)
            // App bar : titre, date du jour, retour vers le tableau de bord comptable.
            ->assertSee('Recouvrement')
            ->assertSee(route('esbtp.comptabilite.dashboard'), false)
            // Héro : la file du jour, jamais un seuil de jours écrit en dur.
            ->assertSee('File du jour')
            // Feuilles : fiche étudiant + exports (permission présente).
            ->assertSee('data-m-sheet="rcm-fiche"', false)
            ->assertSee('data-m-sheet="rcm-exports"', false)
            ->assertSee('aria-label="Exporter la file du jour"', false)
            ->assertSee(route('esbtp.comptabilite.recouvrement.export-excel'), false)
            // La fabrique Alpine est exposée sous garde, et le DOM de bureau reste là.
            ->assertSee("if (typeof window.recouvrement !== 'function')", false)
            ->assertSee('re-hero', false);
    }

    public function test_reglage_coupe_seul_le_dom_de_bureau_est_rendu(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.recouvrement.index'));

        $reponse->assertOk()
            ->assertDontSee('has-m-shell', false)
            ->assertDontSee('rcm-screen', false)
            ->assertDontSee('data-m-sheet="rcm-fiche"', false)
            ->assertSee('re-hero', false);
    }

    public function test_fait_passe_par_mark_done_en_json(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->comptable->id,
        ]);

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.recouvrement.mark-done'), [
                'inscription_id' => $inscription->id,
            ]);

        $reponse->assertOk()->assertJson(['success' => true]);

        $relance = ESBTPRelance::find($reponse->json('relance_id'));
        $this->assertNotNull($relance);
        $this->assertSame((int) $inscription->id, (int) $relance->inscription_id);
        $this->assertSame(ESBTPRelance::STATUT_ENVOYEE, $relance->statut);
    }

    public function test_une_intention_whatsapp_est_journalisee_meme_sans_numero_valide(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->comptable->id,
        ]);
        $inscription->etudiant->update(['telephone' => null]);

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.recouvrement.log-intent'), [
                'inscription_id' => $inscription->id,
                'channel' => 'whatsapp_deeplink',
                'message' => 'Bonjour, votre solde est en retard.',
            ]);

        // Le canal est indisponible : success=false et une raison lisible,
        // que l'écran mobile affiche en toast au lieu d'ouvrir un lien vide.
        $reponse->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error_reason', 'relance_id']);
    }
}
