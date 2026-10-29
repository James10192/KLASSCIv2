<?php

namespace Tests\Feature\Reinscription;

use App\Models\Setting;
use App\Models\User;
use App\Services\Reinscription\PortailReinscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les deux bornes de la fenetre de reinscription sont des dates saisies a la
 * main dans le formulaire des parametres.
 *
 * Ce formulaire est un seul <form> qui couvre toute la page — bulletins, PDF,
 * MailPulse, tronc commun. Un refus mal place y coutait cher : la validation
 * vivait au milieu d'une transaction ouverte, et un `return` en sortait sans
 * `commit` ni `rollBack`. Une seule date mal formee abandonnait donc en
 * silence tous les autres reglages de la page, verrous InnoDB retenus.
 *
 * Ces tests verrouillent les deux moities du contrat : la date invalide est
 * refusee, et ce refus ne coute rien aux reglages voisins.
 */
class BornesFenetreReinscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->administrateur());
        Cache::flush();

        Setting::updateOrCreate(['key' => 'bulletin_show_logo'], [
            'value' => '1', 'type' => 'boolean', 'group' => 'bulletin', 'is_active' => true,
        ]);
        Setting::updateOrCreate(['key' => PortailReinscriptionService::REGLAGE_FERMETURE], [
            'value' => '2026-07-31', 'type' => 'string', 'group' => 'scolarite', 'is_active' => true,
        ]);
    }

    /**
     * @dataProvider datesInvalides
     */
    public function test_une_borne_invalide_est_refusee(string $saisie): void
    {
        $this->enregistrer([
            'reinscriptions_en_ligne_fermeture' => $saisie,
            'bulletin_show_logo' => '1',
        ])->assertSessionHas('error');

        $this->assertSame(
            '2026-07-31',
            Setting::where('key', PortailReinscriptionService::REGLAGE_FERMETURE)->value('value'),
            'Une saisie refusee ne doit pas avoir ecrase la borne en place.'
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function datesInvalides(): array
    {
        return [
            // Ni Carbon ni strtotime ne levent sur celles-ci : elles reportent
            // en silence. « 2026-13-45 » devenait 2027-02-14, soit une saison
            // decalee de sept mois sans qu'aucun ecran ne le dise.
            'mois et jour hors bornes' => ['2026-13-45'],
            'jour inexistant du mois' => ['2026-02-30'],
            'format francais' => ['31/07/2026'],
            'texte libre' => ['fin juillet'],
        ];
    }

    public function test_un_refus_ne_touche_pas_aux_autres_reglages(): void
    {
        // Le coeur du sujet : le refus doit intervenir AVANT toute ecriture.
        $this->enregistrer([
            'reinscriptions_en_ligne_fermeture' => '2026-13-45',
            'bulletin_show_logo' => '0',
            'settings_save_display' => '1',
        ])->assertSessionHas('error');

        $this->assertSame(
            '1',
            Setting::where('key', 'bulletin_show_logo')->value('value'),
            "Un refus sur une date ne doit rien changer au reste de la page."
        );
    }

    public function test_une_borne_valide_est_enregistree(): void
    {
        $this->enregistrer([
            'reinscriptions_en_ligne_fermeture' => '2026-09-15',
            'bulletin_show_logo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            '2026-09-15',
            Setting::where('key', PortailReinscriptionService::REGLAGE_FERMETURE)->value('value')
        );
    }

    public function test_une_borne_peut_etre_videe(): void
    {
        // Vider la borne = « pas de date de fin ». La chaine vide n'est pas une
        // date invalide, elle doit passer.
        $this->enregistrer([
            'reinscriptions_en_ligne_fermeture' => '',
            'bulletin_show_logo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            '',
            Setting::where('key', PortailReinscriptionService::REGLAGE_FERMETURE)->value('value')
        );
    }

    /**
     * @param  array<string, string>  $champs
     */
    private function enregistrer(array $champs)
    {
        return $this->from(route('esbtp.settings.index'))
            ->put(route('esbtp.settings.update'), $champs);
    }

    private function administrateur(): User
    {
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']));

        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
