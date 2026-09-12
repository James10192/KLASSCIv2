<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le mode de composition de la moyenne doit etre reglable PAR L'ECOLE.
 *
 * Ces trois reglages ont ete crees en base, puis on leur a pose une categorie,
 * en affirmant les deux fois qu'ils etaient desormais visibles. Ils ne
 * l'etaient pas : l'onglet Bulletins est ecrit champ par champ, il ne se
 * construit pas depuis la table des reglages. Seul un appel d'API pouvait
 * changer le mode — donc un integrateur, jamais l'ecole.
 *
 * Ce fichier existe pour que la prochaine affirmation soit verifiee et non
 * declaree : il tient que la page PORTE les trois champs, et qu'elle les
 * ENREGISTRE.
 */
class CompositionMoyenneReglablesTest extends TestCase
{
    use RefreshDatabase;

    private const CLES = [
        'bulletin_moyenne_mode',
        'bulletin_bloc_general_coef',
        'bulletin_bloc_professionnel_coef',
    ];

    private function superAdmin(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'system.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function semer(): void
    {
        $defauts = [
            'bulletin_moyenne_mode' => 'ponderee',
            'bulletin_bloc_general_coef' => '1',
            'bulletin_bloc_professionnel_coef' => '1',
        ];

        foreach ($defauts as $cle => $valeur) {
            Setting::updateOrCreate(
                ['key' => $cle],
                ['value' => $valeur, 'type' => 'string', 'group' => 'bulletin', 'category' => 'bulletin', 'is_required' => false],
            );
        }
    }

    public function test_la_page_des_parametres_porte_les_trois_champs(): void
    {
        $this->semer();

        $reponse = $this->actingAs($this->superAdmin())->get(route('esbtp.settings.index'));
        $reponse->assertOk();

        foreach (self::CLES as $cle) {
            $reponse->assertSee('name="'.$cle.'"', false);
        }

        $reponse->assertSee('Composition de la moyenne', false);
    }

    public function test_le_mode_choisi_par_l_ecole_est_enregistre(): void
    {
        $this->semer();

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                'bulletin_moyenne_mode' => 'blocs',
                'bulletin_bloc_general_coef' => '1',
                'bulletin_bloc_professionnel_coef' => '2',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('blocs', Setting::where('key', 'bulletin_moyenne_mode')->value('value'));
        $this->assertSame('1', Setting::where('key', 'bulletin_bloc_general_coef')->value('value'));
        $this->assertSame('2', Setting::where('key', 'bulletin_bloc_professionnel_coef')->value('value'));
    }

    public function test_un_coefficient_non_numerique_est_refuse(): void
    {
        $this->semer();

        // Sans ce refus, « abc » devenait 0.0 a la lecture et le bloc
        // correspondant sortait du calcul : le bulletin n'aurait porte que la
        // moitie de ses matieres, sans le moindre message.
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                'bulletin_bloc_general_coef' => 'abc',
            ])
            ->assertSessionHasErrors('bulletin_bloc_general_coef');

        $this->assertSame('1', Setting::where('key', 'bulletin_bloc_general_coef')->value('value'));
    }

    public function test_un_mode_inconnu_est_refuse(): void
    {
        $this->semer();

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), [
                'settings_save_display' => '1',
                'bulletin_moyenne_mode' => 'moyenne_magique',
            ])
            ->assertSessionHasErrors('bulletin_moyenne_mode');

        $this->assertSame('ponderee', Setting::where('key', 'bulletin_moyenne_mode')->value('value'));
    }
}
