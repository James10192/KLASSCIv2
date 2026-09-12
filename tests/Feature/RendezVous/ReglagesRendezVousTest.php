<?php

namespace Tests\Feature\RendezVous;

use App\Models\Setting;
use App\Models\User;
use App\Services\RendezVous\ConfigurationRendezVous as Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les reglages du guichet s'enregistrent, et l'apercu dit la verite.
 *
 * Deux dangers distincts, et le premier a deja frappe ce depot deux fois.
 *
 * Une cle pointee que personne n'a declaree dans les listes du contrôleur
 * s'affiche a l'ecran, se laisse saisir, et n'est jamais enregistree : PHP
 * remplace le point par un underscore dans les donnees postees, et Laravel lit
 * dans le point un acces imbrique. L'ecole croit avoir regle son guichet ;
 * rien n'a bouge. C'etait la PR #591, puis le catalogue des pieces, puis la
 * barre d'onglets mobile.
 *
 * Le second danger est propre a cet ecran : l'apercu annonce un nombre de
 * places. S'il ne venait pas de la meme arithmetique que la grille reelle, il
 * annoncerait des creneaux que le portail ne proposerait jamais.
 */
class ReglagesRendezVousTest extends TestCase
{
    use RefreshDatabase;

    /** Ce qu'une ecole saisit, tel que le navigateur l'envoie. */
    private const JOURNEE = [
        Config::REGLAGE_DERNIER_JOUR => '2026-10-30',
        Config::REGLAGE_JOURS_OUVERTS => '1,2,3,4,5',
        Config::REGLAGE_OUVERTURE => '08:00',
        Config::REGLAGE_FERMETURE => '16:00',
        Config::REGLAGE_PAUSE_DEBUT => '12:00',
        Config::REGLAGE_PAUSE_FIN => '13:00',
        Config::REGLAGE_DUREE => '15',
        Config::REGLAGE_CAPACITE => '1',
    ];

    private function superAdmin(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'system.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $utilisateur = User::factory()->create();
        $utilisateur->assignRole($role);

        return $utilisateur;
    }

    /** La migration seme ces lignes ; on les remet a plat avant chaque scenario. */
    private function semerLesReglages(string $actif = '0'): void
    {
        Setting::updateOrCreate(
            ['key' => Config::REGLAGE_ACTIF],
            ['value' => $actif, 'type' => 'boolean', 'group' => 'scolarite', 'is_required' => false],
        );

        foreach (self::JOURNEE as $cle => $valeur) {
            Setting::updateOrCreate(
                ['key' => $cle],
                ['value' => '', 'type' => 'string', 'group' => 'scolarite', 'is_required' => false],
            );
        }
    }

    /** Le navigateur poste les cles pointees avec des underscores. */
    private function commeLeNavigateur(array $valeurs): array
    {
        $charge = ['settings_save_display' => '1'];

        foreach ($valeurs as $cle => $valeur) {
            $charge[str_replace('.', '_', $cle)] = $valeur;
        }

        return $charge;
    }

    public function test_la_grille_saisie_par_l_ecole_est_reellement_enregistree(): void
    {
        $this->semerLesReglages();

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->commeLeNavigateur(
                self::JOURNEE + [Config::REGLAGE_ACTIF => '1']
            ))
            ->assertSessionHasNoErrors();

        foreach (self::JOURNEE as $cle => $attendu) {
            $this->assertSame(
                $attendu,
                Setting::where('key', $cle)->value('value'),
                "Le reglage {$cle} n'a pas ete enregistre : il manque sans doute dans les listes du contrôleur."
            );
        }

        $this->assertSame('1', Setting::where('key', Config::REGLAGE_ACTIF)->value('value'));
    }

    public function test_decocher_la_prise_de_rendez_vous_la_ferme_vraiment(): void
    {
        $this->semerLesReglages('1');

        // Une case decochee n'est pas envoyee par le navigateur : c'est son
        // ABSENCE qui vaut « non ». Sans declaration cote contrôleur, cette
        // absence serait ignoree et le canal resterait ouvert.
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->commeLeNavigateur(self::JOURNEE))
            ->assertSessionHasNoErrors();

        $this->assertSame('0', Setting::where('key', Config::REGLAGE_ACTIF)->value('value'));
    }

    public function test_une_date_de_fin_qui_deborde_le_mois_est_refusee_a_la_saisie(): void
    {
        $this->semerLesReglages();

        // Le 31 fevrier n'existe pas. Accepte ici, il serait reporte au 3 mars
        // par une lecture permissive — ou refuse par la grille, laissant l'ecole
        // devant un guichet ferme sans explication.
        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->commeLeNavigateur(
                array_merge(self::JOURNEE, [Config::REGLAGE_DERNIER_JOUR => '2026-02-31'])
            ))
            // Le contrôleur des parametres refuse par un message de session, pas
            // par le sac de validation : il rend `back()->with('error', ...)`.
            ->assertSessionHas('error');

        $this->assertSame('', Setting::where('key', Config::REGLAGE_DERNIER_JOUR)->value('value'));
    }

    public function test_une_heure_illisible_est_refusee_a_la_saisie(): void
    {
        $this->semerLesReglages();

        $this->actingAs($this->superAdmin())
            ->put(route('esbtp.settings.update'), $this->commeLeNavigateur(
                array_merge(self::JOURNEE, [Config::REGLAGE_OUVERTURE => '8h'])
            ))
            ->assertSessionHas('error');

        $this->assertSame('', Setting::where('key', Config::REGLAGE_OUVERTURE)->value('value'));
    }

    public function test_l_apercu_rend_le_nombre_de_places_sans_rien_enregistrer(): void
    {
        $this->semerLesReglages();

        $reponse = $this->actingAs($this->superAdmin())
            ->postJson(route('esbtp.settings.rdv.apercu'), array_merge(self::JOURNEE, [
                Config::REGLAGE_ACTIF => '1',
                Config::REGLAGE_PREMIER_JOUR => '2026-09-14',
            ]));

        $reponse->assertOk()
            ->assertJson([
                'complete' => true,
                'problemes' => [],
                'creneaux_par_jour' => 28,
                'places_par_jour' => 28,
                'jours_de_reception' => 35,
                'places_sur_la_campagne' => 980,
            ]);

        // Un apercu n'ecrit rien : les reglages sont restes a leur valeur vide.
        $this->assertSame('', Setting::where('key', Config::REGLAGE_DERNIER_JOUR)->value('value'));
    }

    public function test_l_apercu_nomme_ce_qui_empeche_d_ouvrir(): void
    {
        $this->semerLesReglages();

        $reponse = $this->actingAs($this->superAdmin())
            ->postJson(route('esbtp.settings.rdv.apercu'), array_merge(self::JOURNEE, [
                Config::REGLAGE_PREMIER_JOUR => '2026-09-14',
                // Une pause qui couvre toute la journee. La grille ne doit pas
                // rendre zero creneau en silence : la vitrine l'afficherait
                // « aucune disponibilite », la famille conclurait « complet »,
                // et l'ecole ne saurait jamais pourquoi.
                Config::REGLAGE_PAUSE_DEBUT => '08:00',
                Config::REGLAGE_PAUSE_FIN => '16:00',
            ]));

        $reponse->assertOk()->assertJson(['complete' => false]);

        $this->assertNotEmpty($reponse->json('problemes'));
        $this->assertStringContainsString('aucun creneau', implode(' ', $reponse->json('problemes')));
    }

    public function test_l_apercu_demande_d_etre_habilite(): void
    {
        $this->semerLesReglages();

        $this->actingAs(User::factory()->create())
            ->postJson(route('esbtp.settings.rdv.apercu'), self::JOURNEE)
            ->assertForbidden();
    }
}
