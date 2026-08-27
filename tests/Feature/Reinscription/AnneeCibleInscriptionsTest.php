<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\Setting;
use App\Models\User;
use App\Services\Reinscription\PortailReinscriptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'annee visee par les inscriptions, decouplee de l'annee courante.
 *
 * La rentree arrive toujours avant que l'annee precedente soit close. Une ecole
 * qui n'a pas fini de saisir ses notes ne peut pas basculer `is_current` — les
 * ecrans de saisie filtrent dessus, sans selecteur d'annee. Sans ce decouplage,
 * elle devrait choisir entre finir ses bulletins et ouvrir sa rentree.
 *
 * C'est la situation d'ESBTP Abidjan en aout 2026, d'ou ces tests.
 */
class AnneeCibleInscriptionsTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPEtudiant $etudiant;

    private ESBTPAnneeUniversitaire $anneeEnCours;

    private ESBTPAnneeUniversitaire $anneeProchaine;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1])
            ->assignRole(Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']));

        Cache::flush();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        // Le cas reel : l'ecole est encore sur 2025-2026 parce qu'elle saisit
        // ses notes, mais elle veut ouvrir les inscriptions pour 2026-2027.
        $this->anneeEnCours = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);
        $this->anneeProchaine = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_current' => false,
        ]);

        $classe = ESBTPClasse::factory()->create([
            'name' => 'BTS2 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true,
        ]);

        $this->etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => 'CIBLE-0001', 'date_naissance' => '2004-03-15',
        ]);

        // Inscrit pour l'annee EN COURS : c'est un eleve de cette annee, qui
        // doit pouvoir se reinscrire pour la suivante.
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->anneeEnCours->id,
            'status' => 'active',
        ]);
    }

    private function portail(): PortailReinscriptionService
    {
        return app(PortailReinscriptionService::class);
    }

    private function viser(?int $anneeId): void
    {
        Setting::updateOrCreate(
            ['key' => PortailReinscriptionService::REGLAGE_ANNEE_CIBLE],
            ['value' => $anneeId === null ? '' : (string) $anneeId, 'type' => 'string', 'group' => 'scolarite', 'is_active' => true],
        );

        Cache::flush();
    }

    public function test_sans_reglage_la_cible_reste_l_annee_courante(): void
    {
        // Le comportement historique ne doit pas bouger pour les ecoles qui ne
        // configurent rien.
        $this->assertSame($this->anneeEnCours->id, $this->portail()->anneeCible()?->id);
    }

    public function test_le_reglage_deplace_la_cible_sans_toucher_a_l_annee_courante(): void
    {
        $this->viser($this->anneeProchaine->id);

        $this->assertSame($this->anneeProchaine->id, $this->portail()->anneeCible()?->id);

        // L'annee courante, elle, n'a pas bouge : la saisie des notes continue.
        $this->assertTrue($this->anneeEnCours->fresh()->is_current);
        $this->assertFalse($this->anneeProchaine->fresh()->is_current);
    }

    public function test_sans_reglage_l_eleve_de_l_annee_en_cours_n_est_pas_eligible(): void
    {
        // C'est le blocage qu'on leve : vise sur l'annee courante, l'eleve y est
        // deja inscrit, donc rien a reinscrire.
        $situation = $this->portail()->evaluer($this->etudiant);

        $this->assertNull($situation, "Sans annee suivante visee, il n'y a rien a reinscrire.");
    }

    public function test_avec_le_reglage_le_meme_eleve_devient_eligible(): void
    {
        $this->viser($this->anneeProchaine->id);

        $situation = $this->portail()->evaluer($this->etudiant);

        $this->assertNotNull($situation, 'Le decouplage doit rendre eligible un eleve de l\'annee en cours.');
        $this->assertTrue($situation->eligible());
        $this->assertSame('BTS2 A', $situation->classeActuelle());
        $this->assertSame($this->anneeProchaine->id, $situation->anneeCible->id);
    }

    public function test_la_demande_deposee_porte_l_annee_visee(): void
    {
        $this->viser($this->anneeProchaine->id);

        $demande = $this->portail()->deposer(
            $this->portail()->evaluer($this->etudiant),
            '196.207.1.42'
        );

        $this->assertNotNull($demande);
        $this->assertSame(
            $this->anneeProchaine->id,
            $demande->annee_universitaire_id,
            "La demande doit viser l'annee choisie, pas l'annee courante — sinon la conversion inscrirait l'eleve dans l'annee qu'il termine."
        );
    }

    public function test_une_annee_supprimee_fait_replier_sur_l_annee_courante(): void
    {
        // Un reglage qui pointe dans le vide ne doit pas casser le portail,
        // mais il ne doit pas non plus passer inapercu : le service journalise.
        $this->viser(999999);

        $this->assertSame($this->anneeEnCours->id, $this->portail()->anneeCible()?->id);
    }

    public function test_l_ecran_des_reglages_refuse_une_annee_inconnue(): void
    {
        $avant = (string) Setting::where('key', PortailReinscriptionService::REGLAGE_ANNEE_CIBLE)->value('value');

        $this->actingAs(User::find(1))
            ->post(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                'inscriptions_annee_cible' => '999999',
            ])
            ->assertSessionHas('error');

        $this->assertSame(
            $avant,
            (string) Setting::where('key', PortailReinscriptionService::REGLAGE_ANNEE_CIBLE)->value('value'),
            'Un refus ne doit rien enregistrer.'
        );
    }

    public function test_l_ecran_des_reglages_accepte_une_annee_existante(): void
    {
        $this->actingAs(User::find(1))
            ->post(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                'inscriptions_annee_cible' => (string) $this->anneeProchaine->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            (string) $this->anneeProchaine->id,
            (string) Setting::where('key', PortailReinscriptionService::REGLAGE_ANNEE_CIBLE)->value('value')
        );
    }
}
